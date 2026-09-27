#!/usr/bin/env bash
# Deploy a tagged release of our code (PLAN.md §5.7, §6.11).
#
#   wordpress/ops/deploy.sh <tag> <staging|production> [--dry-run] [--flush-rewrites] [--yes]
#
# Code only, from a clean export of the tag (never the working tree):
#   theme/spokares/        -> wp-content/themes/spokares/         (--delete)
#   plugins/spokares-core/ -> wp-content/plugins/spokares-core/   (--delete)
#   mu-plugins/spokares-hardening.php and spokares-hardening/ -> wp-content/mu-plugins/
#       (--delete only inside spokares-hardening/, never at the mu-plugins root)
#   ops/weekly-check.sh    -> ~/bin/weekly-check.sh
# then `wp rewrite flush` (only with --flush-rewrites: when routes, post types
# or the /docs/ rule changed) and `wp cache flush`, and records the tag in
# ~/.spokares-deployed-tag. tools/ and dev/ are never deployed.
#
# Never use Enhance "push live": it copies the database and would overwrite the
# editors' rota, events and documents. Rollback = deploy the previous tag.
# Afterwards: wordpress/ops/check-live.sh <tag> [--staging].
set -euo pipefail
source "$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/lib.sh"

usage() { echo "usage: $0 <tag> <staging|production> [--dry-run] [--flush-rewrites] [--yes]" >&2; exit 2; }
[[ $# -ge 2 ]] || usage
TAG=$1; TARGET=$2; shift 2
DRY=0; FLUSH=0; YES=0
for arg in "$@"; do
  case $arg in
    --dry-run) DRY=1 ;;
    --flush-rewrites) FLUSH=1 ;;
    --yes) YES=1 ;;
    *) usage ;;
  esac
done

spk_load_env
spk_target "$TARGET"
WP=$SPOKARES_WP_PATH

WORK=$(mktemp -d "${TMPDIR:-/tmp}/spokares-deploy.XXXXXX")
trap 'rm -rf "$WORK"' EXIT
spk_export_tag "$TAG" "$WORK"
W=$WORK/wordpress

VERSION=$(spk_tag_version "$TAG")
spk_check_versions "$WORK" "$VERSION"
(( spk_fails == 0 )) || spk_die "the tag's version strings don't all equal $VERSION; fix, re-tag, and run ops/ci.sh --tag=<tag> first"

echo "deploy.sh: $TAG (version $VERSION) -> $TARGET ($SPK_SSH:$WP, $SPK_URL)$( ((DRY)) && echo ', DRY RUN')"
if [[ $TARGET == production ]] && (( ! DRY && ! YES )); then
  read -r -p "Deploy $TAG to PRODUCTION? Type 'production' to continue: " answer
  [[ $answer == production ]] || spk_die "cancelled"
fi

# No -p/--chmod: macOS ships openrsync, which has no --chmod. Permissions are
# set by one remote chmod pass below (folders 755, files 644), whatever the
# local file modes are.
RSYNC=(rsync -rltz --delay-updates --exclude=.DS_Store --itemize-changes -e ssh)
(( DRY )) && RSYNC+=(-n)

ssh "$SPK_SSH" "test -f '$WP/wp-config.php'" || spk_die "no WordPress at $SPK_SSH:$WP"

echo "--- must-use plugin"
"${RSYNC[@]}" "$W/mu-plugins/spokares-hardening.php" "$SPK_SSH:$WP/wp-content/mu-plugins/"
"${RSYNC[@]}" --delete "$W/mu-plugins/spokares-hardening/" "$SPK_SSH:$WP/wp-content/mu-plugins/spokares-hardening/"
echo "--- plugin spokares-core"
"${RSYNC[@]}" --delete "$W/plugins/spokares-core/" "$SPK_SSH:$WP/wp-content/plugins/spokares-core/"
echo "--- theme spokares"
"${RSYNC[@]}" --delete "$W/theme/spokares/" "$SPK_SSH:$WP/wp-content/themes/spokares/"
echo "--- ~/bin/weekly-check.sh"
(( DRY )) || ssh "$SPK_SSH" 'mkdir -p ~/bin'
"${RSYNC[@]}" "$W/ops/weekly-check.sh" "$SPK_SSH:bin/weekly-check.sh"

if (( DRY )); then
  echo "deploy.sh: dry run finished; nothing was changed."
  exit 0
fi

ours="themes/spokares plugins/spokares-core mu-plugins/spokares-hardening"
ssh "$SPK_SSH" "cd '$WP/wp-content' && find $ours -type d -exec chmod 755 {} + && find $ours -type f -exec chmod 644 {} + && chmod 644 mu-plugins/spokares-hardening.php && chmod 755 ~/bin/weekly-check.sh"
remote="cd '$WP'"
(( FLUSH )) && remote="$remote && wp rewrite flush"
remote="$remote && wp cache flush && printf '%s %s\n' '$TAG' \"\$(date -u +%Y-%m-%dT%H:%M:%SZ)\" > ~/.spokares-deployed-tag"
ssh "$SPK_SSH" "$remote"
echo "deploy.sh: $TAG is on $TARGET. Next: wordpress/ops/check-live.sh $TAG$([[ $TARGET == staging ]] && echo ' --staging')"
