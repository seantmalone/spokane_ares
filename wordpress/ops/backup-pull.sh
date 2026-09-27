#!/usr/bin/env bash
# Monthly backup pull from the admin's machine (PLAN.md §5.9 layer 3).
#
#   wordpress/ops/backup-pull.sh [--staging]
#
# Over SSH: `wp db export - | gzip` into <backup dir>/db/spokares-db-<date>.sql.gz
# and `rsync -a` of wp-content/uploads/ into <backup dir>/uploads/. The host
# holds no credential for this copy, so a site takeover can't delete it.
# <backup dir> is SPOKARES_BACKUP_DIR in ops.env and must be on an encrypted
# volume (the dump holds password hashes and two-factor secrets).
# Nothing is pruned: delete old dumps by hand.
set -euo pipefail
source "$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/lib.sh"

TARGET=production
for arg in "$@"; do
  case $arg in
    --staging) TARGET=staging ;;
    *) echo "usage: $0 [--staging]" >&2; exit 2 ;;
  esac
done
spk_load_env
spk_target "$TARGET"
WP=$SPOKARES_WP_PATH
DEST=${SPOKARES_BACKUP_DIR:-}
[[ -n $DEST ]] || spk_die "set SPOKARES_BACKUP_DIR in ops.env"
mkdir -p "$DEST/db" "$DEST/uploads"
chmod 700 "$DEST"

# Encrypted at rest? (macOS: FileVault for the boot volume, or an encrypted disk.)
if command -v diskutil >/dev/null; then
  mount_point=$(df -P "$DEST" | awk 'NR == 2 { print $6 }')
  if [[ $mount_point == / || $mount_point == /System/Volumes/Data ]]; then
    grep -q 'FileVault is On' <<<"$(fdesetup status 2>/dev/null)" || echo "WARNING: FileVault is off; $DEST is not encrypted at rest." >&2
  else
    grep -qiE 'FileVault: +Yes|Encrypted: +Yes' <<<"$(diskutil info "$mount_point" 2>/dev/null)" || echo "WARNING: $mount_point does not report as encrypted." >&2
  fi
fi

STAMP=$(date +%F)
DUMP=$DEST/db/spokares-db-$TARGET-$STAMP.sql.gz
echo "backup-pull.sh: database from $SPK_SSH:$WP -> $DUMP"
ssh "$SPK_SSH" "cd '$WP' && wp db export --single-transaction - | gzip -9" > "$DUMP.part"
gzip -t "$DUMP.part" || spk_die "the database dump is not a valid gzip file ($DUMP.part kept)"
bytes=$(wc -c < "$DUMP.part" | tr -d ' ')
(( bytes > 1000 )) || spk_die "the database dump is suspiciously small ($bytes bytes; $DUMP.part kept)"
mv "$DUMP.part" "$DUMP"
chmod 600 "$DUMP"

echo "backup-pull.sh: uploads -> $DEST/uploads/"
rsync -a -e ssh "$SPK_SSH:$WP/wp-content/uploads/" "$DEST/uploads/"

echo "backup-pull.sh: done: $(du -sh "$DUMP" | cut -f1) database, $(du -sh "$DEST/uploads" | cut -f1) uploads."
echo "Keep a copy off this machine too, and restore it on an Enhance staging site twice a year (PLAN §5.9)."
