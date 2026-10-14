#!/usr/bin/env bash
# Restore a backup-pull.sh copy (PLAN.md §5.9) to a site, from the admin's machine.
#
#   wordpress/ops/restore.sh <db .sql.gz> <staging|production> [--uploads=<dir>] [--delete-uploads] [--dry-run] [--yes]
#
#   <db .sql.gz>      a dump from backup-pull.sh (<backup dir>/db/spokares-db-*.sql.gz),
#                     or an UpdraftPlus database file (*-db.gz) downloaded from S3
#   --uploads=<dir>   also rsync this folder (e.g. <backup dir>/uploads) into
#                     wp-content/uploads/; without it uploads are left alone
#   --delete-uploads  with --uploads: delete files on the site that the copy lacks
#   --dry-run         check everything and print the plan; change nothing
#   --yes             don't ask you to type the target's name
#
# Order: check the dump locally -> on the host, export the current database
# to ~/backups/pre-restore-<time>.sql.gz (pruned after 14 days) -> import the
# dump -> on staging, rewrite the live URL to the staging URL -> uploads ->
# flush caches and rewrite rules -> print what the site now holds.
# Then run check-live.sh against the target. The runbook's "Restore" section
# says which copy to pick.
set -euo pipefail
source "$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/lib.sh"

usage() { echo "usage: $0 <db .sql.gz> <staging|production> [--uploads=<dir>] [--delete-uploads] [--dry-run] [--yes]" >&2; exit 2; }
[[ $# -ge 2 ]] || usage
DUMP=$1 TARGET=$2; shift 2
UPLOADS="" DELETE=0 DRY=0 YES=0
for arg in "$@"; do
  case $arg in
    --uploads=*) UPLOADS=${arg#--uploads=} ;;
    --delete-uploads) DELETE=1 ;;
    --dry-run) DRY=1 ;;
    --yes) YES=1 ;;
    *) usage ;;
  esac
done
spk_load_env
spk_target "$TARGET"
WP=$SPOKARES_WP_PATH

[[ -f $DUMP ]] || spk_die "no such file: $DUMP"
gzip -t "$DUMP" 2>/dev/null || spk_die "$DUMP is not a valid gzip file"
gunzip -c "$DUMP" | head -c 200000 | grep -q 'CREATE TABLE' || spk_die "$DUMP doesn't look like a database dump (no CREATE TABLE near the start)"
if [[ -n $UPLOADS ]]; then
  [[ -d $UPLOADS ]] || spk_die "no such folder: $UPLOADS"
  UPLOADS=${UPLOADS%/}/
elif (( DELETE )); then
  spk_die "--delete-uploads needs --uploads=<dir>"
fi

echo "restore.sh: target $TARGET ($SPK_SSH:$WP, $SPK_URL)"
echo "  database: $DUMP ($(du -h "$DUMP" | cut -f1), dated $(date -r "$DUMP" +%F 2>/dev/null || stat -c %y "$DUMP" | cut -d' ' -f1))"
echo "  uploads:  ${UPLOADS:-left as they are}$( (( DELETE )) && echo ' (files the copy lacks are deleted)')"
[[ $TARGET == staging ]] && echo "  URLs:     ${SPOKARES_LIVE_URL:-?} -> $SPK_URL (wp search-replace)"
ssh "$SPK_SSH" "cd '$WP' && wp core is-installed" || spk_die "WordPress doesn't answer on $SPK_SSH:$WP"
if (( DRY )); then
  echo "restore.sh: dry run, nothing changed."
  exit 0
fi
if (( ! YES )); then
  read -r -p "This replaces the whole $TARGET database. Type '$TARGET' to go on: " answer
  [[ $answer == "$TARGET" ]] || spk_die "stopped"
fi

STAMP=$(date +%Y%m%d-%H%M%S)
echo "restore.sh: saving the current database on the host first"
ssh "$SPK_SSH" "umask 077 && mkdir -p ~/backups && cd '$WP' && wp db export --single-transaction - | gzip -9 > ~/backups/pre-restore-$STAMP.sql.gz && find ~/backups -maxdepth 1 -name '*.sql.gz' -mtime +14 -delete && ls -l ~/backups/pre-restore-$STAMP.sql.gz"

echo "restore.sh: importing"
gunzip -c "$DUMP" | ssh "$SPK_SSH" "cd '$WP' && wp db import -"

if [[ $TARGET == staging && -n ${SPOKARES_LIVE_URL:-} && ${SPOKARES_LIVE_URL%/} != "$SPK_URL" ]]; then
  echo "restore.sh: pointing the copy at $SPK_URL"
  ssh "$SPK_SSH" "cd '$WP' && wp search-replace '${SPOKARES_LIVE_URL%/}' '$SPK_URL' --all-tables-with-prefix --skip-columns=guid --report-changed-only"
fi

if [[ -n $UPLOADS ]]; then
  echo "restore.sh: uploads"
  args=(-a)
  (( DELETE )) && args+=(--delete)
  rsync "${args[@]}" -e ssh "$UPLOADS" "$SPK_SSH:$WP/wp-content/uploads/"
fi

echo "restore.sh: flushing caches and rewrite rules"
ssh "$SPK_SSH" "cd '$WP' && wp cache flush && wp rewrite flush && echo \"site: \$(wp option get siteurl); documents: \$(wp post list --post_type=spk_document --post_status=publish --format=count) published; events: \$(wp post list --post_type=spk_event --post_status=publish --format=count) published; Net Control Schedule rows: \$(wp eval 'echo count( (array) get_option( \"spk_rota\", array() ) );')\""
echo "restore.sh: done. The pre-restore copy is ~/backups/pre-restore-$STAMP.sql.gz on the host."
echo "Next: wordpress/ops/check-live.sh <deployed tag>$([[ $TARGET == staging ]] && echo ' --staging'), then sign in and open the five jobs' screens."
