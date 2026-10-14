#!/usr/bin/env bash
# A quick database copy ON THE HOST before a hand edit over SSH (wp eval-file,
# wp option patch, a one-off step from the runbook), from the admin's machine.
#
#   wordpress/ops/host-backup.sh [--staging] [label]
#
# Writes ~/backups/db-<time>[-label].sql.gz on the host (folder 700, file 600)
# and deletes copies there older than 14 days: the dumps hold password hashes
# and two-factor secrets, so they mustn't pile up on the server. For a copy
# that survives the host, use backup-pull.sh.
set -euo pipefail
source "$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/lib.sh"

TARGET=production LABEL=""
for arg in "$@"; do
  case $arg in
    --staging) TARGET=staging ;;
    -*) echo "usage: $0 [--staging] [label]" >&2; exit 2 ;;
    *) [[ $arg =~ ^[A-Za-z0-9_-]+$ ]] || spk_die "label: letters, digits, - and _ only"; LABEL=-$arg ;;
  esac
done
spk_load_env
spk_target "$TARGET"
WP=$SPOKARES_WP_PATH
STAMP=$(date +%Y%m%d-%H%M%S)
ssh "$SPK_SSH" "umask 077 && mkdir -p ~/backups && chmod 700 ~/backups && cd '$WP' && wp db export --single-transaction - | gzip -9 > ~/backups/db-$STAMP$LABEL.sql.gz && gzip -t ~/backups/db-$STAMP$LABEL.sql.gz && pruned=\$(find ~/backups -maxdepth 1 -name '*.sql.gz' -mtime +14 -print -delete | wc -l) && echo \"host-backup.sh: ~/backups/db-$STAMP$LABEL.sql.gz (\$(du -h ~/backups/db-$STAMP$LABEL.sql.gz | cut -f1)); \$pruned older copies deleted\""
echo "To undo the edit: ssh $SPK_SSH \"cd $WP && gunzip -c ~/backups/db-$STAMP$LABEL.sql.gz | wp db import -\""
