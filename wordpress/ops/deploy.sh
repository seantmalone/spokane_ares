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
# then the tag's ops/post-deploy.sh runs on the host in one SSH session:
# permissions, Two-Factor and UpdraftPlus installed/active, the theme and
# spokares-core active, "discourage search engines" kept on before launch,
# `wp rewrite flush` (with --flush-rewrites: when routes, post types or the
# /docs/ rule changed) and `wp cache flush`, and the tag recorded in
# ~/.spokares-deployed-tag. tools/ and dev/ are never deployed.
#
# <tag> may also be a branch or a commit (the GitHub Actions manual deploy):
# the versions must then agree with each other instead of with a tag, and
# ~/.spokares-deployed-tag records `git describe` of the ref.
# --yes skips the "type production" prompt (CI).
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

LABEL=$(spk_ref_label "$TAG")
if spk_is_tag "$TAG"; then
  VERSION=$(spk_tag_version "$TAG")
  spk_check_versions "$WORK" "$VERSION"
  (( spk_fails == 0 )) || spk_die "the tag's version strings don't all equal $VERSION; fix, re-tag, and run ops/ci.sh --tag=<tag> first"
else
  spk_check_versions "$WORK" ""
  (( spk_fails == 0 )) || spk_die "the version strings in $TAG don't agree; fix them and run ops/ci.sh first"
  VERSION=$(spk_versions "$WORK" | awk -F'\t' 'NR == 1 { print $2 }')
  echo "NOTE  $TAG is not a tag: deploying commit $LABEL (version strings say $VERSION)"
fi

echo "deploy.sh: $TAG (version $VERSION) -> $TARGET ($SPK_SSH:$WP, $SPK_URL)$( ((DRY)) && echo ', DRY RUN')"
if [[ $TARGET == production ]] && (( ! DRY && ! YES )); then
  read -r -p "Deploy $TAG to PRODUCTION? Type 'production' to continue: " answer
  [[ $answer == production ]] || spk_die "cancelled"
fi

# No -p/--chmod: macOS ships openrsync, which has no --chmod. Permissions are
# set by one remote chmod pass in ops/post-deploy.sh (folders 755, files 644),
# whatever the local file modes are.
RSYNC=(rsync -rltz --delay-updates --exclude=.DS_Store --itemize-changes -e ssh)
(( DRY )) && RSYNC+=(-n)

# One check (and, for a real run, the folders rsync can't create two levels deep).
pre="test -f '$WP/wp-config.php'"
(( DRY )) || pre="$pre && mkdir -p '$WP/wp-content/mu-plugins' ~/bin"
ssh "$SPK_SSH" "$pre" || spk_die "no WordPress at $SPK_SSH:$WP"

echo "--- must-use plugin"
"${RSYNC[@]}" "$W/mu-plugins/spokares-hardening.php" "$SPK_SSH:$WP/wp-content/mu-plugins/"
"${RSYNC[@]}" --delete "$W/mu-plugins/spokares-hardening/" "$SPK_SSH:$WP/wp-content/mu-plugins/spokares-hardening/"
echo "--- plugin spokares-core"
"${RSYNC[@]}" --delete "$W/plugins/spokares-core/" "$SPK_SSH:$WP/wp-content/plugins/spokares-core/"
echo "--- theme spokares"
"${RSYNC[@]}" --delete "$W/theme/spokares/" "$SPK_SSH:$WP/wp-content/themes/spokares/"
echo "--- ~/bin/weekly-check.sh"
if (( DRY )); then
  "${RSYNC[@]}" "$W/ops/weekly-check.sh" "$SPK_SSH:bin/weekly-check.sh" 2>/dev/null || echo "(dry run: ~/bin doesn't exist yet; a real run creates it)"
else
  "${RSYNC[@]}" "$W/ops/weekly-check.sh" "$SPK_SSH:bin/weekly-check.sh"
fi

if (( DRY )); then
  echo "deploy.sh: dry run finished; nothing was changed."
  exit 0
fi

echo "--- post-deploy (on the host, one SSH session)"
flush_arg=""
(( FLUSH )) && flush_arg=--flush-rewrites
ssh "$SPK_SSH" "SPOKARES_WP_PATH='$WP' bash -s -- '$LABEL' $flush_arg" < "$W/ops/post-deploy.sh" || spk_die "post-deploy steps failed on $SPK_SSH (the code is in place; fix and re-run the deploy)"
echo "deploy.sh: $TAG is on $TARGET. Next: wordpress/ops/check-live.sh $TAG$([[ $TARGET == staging ]] && echo ' --staging')"
