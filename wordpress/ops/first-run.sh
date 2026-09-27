#!/usr/bin/env bash
# One-time site setup after the FIRST deploy (PLAN.md §5.11, §6.10 "Production
# seeding"; research/security-hosting.md §11 steps 3 and 5). Run from the
# admin's machine, once, after deploy.sh (whose post-deploy step activates the
# theme, spokares-core, Two-Factor and UpdraftPlus):
#
#   wordpress/ops/first-run.sh <tag|ref> <staging|production> [--yes]
#
# From a clean export of <ref>, and nothing else from dev/ (no dev login, no
# test code), it copies dev/seed/import.php, data.json, extra.json and
# dev/setup/pages.php into a private temporary folder in the SSH user's home
# (outside public_html), runs them with wp eval-file, and deletes the folder
# whatever happens. Then, on the host:
#   1. seed/import.php production: every document a Draft with no Privacy
#      tick, "Needs checking" events Drafts, and the rest of §6.10's
#      production rules (idempotent: does nothing once spk_seeded is set)
#   2. setup/pages.php: the six pages from the theme's patterns, the Home hero
#      in the Media Library, the static front page; deletes WordPress's
#      untouched sample content (idempotent: existing pages are left alone)
#   3. deletes Akismet and Hello Dolly and every default theme except one
#      spare (twentytwentyfive), only while they are inactive (§5.11 step 3);
#      lets UpdraftPlus create its protected backup folder
#   4. adds security-hosting.md §4.4's two rewrite rules above
#      "# BEGIN WordPress" (no PHP under uploads; no direct wp-includes/*.php)
#   5. writes ~/.spokares-weekly.env for weekly-check.sh if it is missing
#   6. prints the result: pages, front page, the ares_editor role, documents
# Safe to run again. Needs ops.env, like deploy.sh.
set -euo pipefail
source "$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/lib.sh"

usage() { echo "usage: $0 <tag|ref> <staging|production> [--yes]" >&2; exit 2; }
[[ $# -ge 2 ]] || usage
REF=$1; TARGET=$2; shift 2
YES=0
for arg in "$@"; do
  case $arg in
    --yes) YES=1 ;;
    *) usage ;;
  esac
done
spk_load_env
spk_target "$TARGET"
WP=$SPOKARES_WP_PATH

git -C "$SPK_ROOT" rev-parse -q --verify "$REF^{commit}" >/dev/null || spk_die "no git tag, branch or commit '$REF'"
WORK=$(mktemp -d "${TMPDIR:-/tmp}/spokares-first-run.XXXXXX")
REMOTE_DIR=".spokares-first-run-$(date +%Y%m%d%H%M%S)-$$"
cleanup() {
  rm -rf "$WORK"
  ssh "$SPK_SSH" "rm -rf ~/'$REMOTE_DIR'" 2>/dev/null || echo "first-run.sh: could not remove ~/$REMOTE_DIR on $SPK_SSH; delete it by hand" >&2
}
trap cleanup EXIT

# Only these four files leave the machine.
mkdir -p "$WORK/upload/seed" "$WORK/upload/setup"
git -C "$SPK_ROOT" archive --format=tar "$REF" wordpress/dev/seed/import.php wordpress/dev/seed/data.json wordpress/dev/seed/extra.json wordpress/dev/setup/pages.php \
  | tar -x -C "$WORK" || spk_die "git archive of $REF failed (does it have dev/seed and dev/setup/pages.php?)"
cp "$WORK/wordpress/dev/seed/import.php" "$WORK/wordpress/dev/seed/data.json" "$WORK/wordpress/dev/seed/extra.json" "$WORK/upload/seed/"
cp "$WORK/wordpress/dev/setup/pages.php" "$WORK/upload/setup/"

echo "first-run.sh: $REF -> $TARGET ($SPK_SSH:$WP): import (production mode), pages, clean-up"
if [[ $TARGET == production ]] && (( ! YES )); then
  read -r -p "Run the one-time setup on PRODUCTION? Type 'production' to continue: " answer
  [[ $answer == production ]] || spk_die "cancelled"
fi

ssh "$SPK_SSH" "umask 077 && mkdir ~/'$REMOTE_DIR'"
rsync -rt -e ssh "$WORK/upload/" "$SPK_SSH:$REMOTE_DIR/"

# The host side, in one SSH session. Arguments: WordPress folder, temp folder.
ssh "$SPK_SSH" "bash -s -- '$WP' \"\$HOME/$REMOTE_DIR\"" <<'REMOTE'
set -euo pipefail
WP=$1; DIR=$2
trap 'rm -rf "$DIR"' EXIT
cd "$WP"
wp() { command wp "$@" < /dev/null; }   # the script is on stdin

wp plugin is-active spokares-core || { echo "first-run: spokares-core is not active; run deploy.sh first" >&2; exit 1; }
[[ $(wp option get stylesheet) == spokares ]] || { echo "first-run: the spokares theme is not active; run deploy.sh first" >&2; exit 1; }
ADMIN=$(wp user list --role=administrator --field=ID --orderby=ID --order=ASC | head -1)
[[ -n $ADMIN ]] || { echo "first-run: no administrator" >&2; exit 1; }

echo "--- 1. import, production mode"
wp eval-file "$DIR/seed/import.php" production --user="$ADMIN"

echo "--- 2. pages"
wp eval-file "$DIR/setup/pages.php" --user="$ADMIN"

echo "--- 3. extras"
for p in akismet hello; do
  if wp plugin is-installed "$p"; then
    if wp plugin is-active "$p"; then echo "kept plugin $p (active)"; else wp plugin delete "$p"; fi
  fi
done
for t in twentytwentythree twentytwentyfour; do
  if wp theme is-installed "$t"; then
    if [[ $(wp option get stylesheet) == "$t" || $(wp option get template) == "$t" ]]; then echo "kept theme $t (active)"; else wp theme delete "$t"; fi
  fi
done

echo "--- 3b. UpdraftPlus backup folder"
# UpdraftPlus makes wp-content/updraft/ (with its deny-all .htaccess) on first
# use; make it now so check-live.sh's 403 check tests the real folder.
wp eval 'global $updraftplus; if ( is_object( $updraftplus ) && method_exists( $updraftplus, "backups_dir_location" ) ) { echo "backup folder: ", str_replace( ABSPATH, "", $updraftplus->backups_dir_location() ), "\n"; } else { echo "UpdraftPlus is not active\n"; }'
ls -a wp-content/updraft 2>/dev/null | tr '\n' ' '; echo

echo "--- 4. .htaccess rewrite rules"
if grep -q '^# BEGIN spokares hardening' .htaccess; then
  echo ".htaccess already has the spokares hardening block"
else
  grep -q '^# BEGIN WordPress' .htaccess || { echo "first-run: no '# BEGIN WordPress' in .htaccess" >&2; exit 1; }
  cp -p .htaccess ~/.spokares-htaccess.before-first-run
  awk '/^# BEGIN WordPress/ && !done {
    print "# BEGIN spokares hardening"
    print "# research/security-hosting.md 4.4: no PHP under uploads (web shells), no direct wp-includes/*.php."
    print "<IfModule mod_rewrite.c>"
    print "RewriteEngine On"
    print "RewriteRule ^wp-content/uploads/.*\\.(php[0-9]?|phtml|phar)$ - [F,L,NC]"
    print "RewriteRule ^wp-includes/[^/]+\\.php$ - [F,L]"
    print "</IfModule>"
    print "# END spokares hardening"
    print ""
    done = 1
  } { print }' .htaccess > .htaccess.spokares-new
  cat .htaccess.spokares-new > .htaccess && rm -f .htaccess.spokares-new
  echo "added the spokares hardening block (previous copy: ~/.spokares-htaccess.before-first-run)"
fi

echo "--- 5. ~/.spokares-weekly.env"
if [[ -f ~/.spokares-weekly.env ]]; then
  echo "exists, left as it is"
else
  admins=$(wp user list --role=administrator --field=user_login | tr '\n' ' ' | sed 's/ $//')
  mu_extra=$(find wp-content/mu-plugins -mindepth 1 -maxdepth 1 -exec basename {} \; 2>/dev/null | grep -vxE 'index\.php|spokares-hardening(\.php)?' | tr '\n' ' ' | sed 's/ $//' || true)
  dropins=$(find wp-content -maxdepth 1 -type f -name '*.php' -exec basename {} \; | grep -vx 'index\.php' | tr '\n' ' ' | sed 's/ $//' || true)
  ( umask 077; cat > ~/.spokares-weekly.env <<ENV
# weekly-check.sh settings (PLAN.md §5.8), written by ops/first-run.sh on $(date -u +%Y-%m-%d).
SPOKARES_WP_PATH=\$HOME/$WP
SPOKARES_ADMINS="$admins"
SPOKARES_EXPECTED_PLUGINS="spokares-core two-factor updraftplus"
SPOKARES_EXPECTED_THEMES="spokares twentytwentyfive"
SPOKARES_MU_EXTRA="$mu_extra"
SPOKARES_DROPINS="$dropins"
SPOKARES_ALERT_EMAIL=webmaster@spokares.org
ENV
  )
  echo "written (mode 600); administrators, mu-plugins extras and drop-ins recorded as they are now"
fi

echo "--- 6. result"
wp post list --post_type=page --post_status=any --fields=ID,post_title,post_name,post_parent,post_status --orderby=menu_order --order=ASC
echo "front page: show_on_front=$(wp option get show_on_front), page_on_front=$(wp option get page_on_front)"
wp role exists ares_editor
echo "documents: $(wp post list --post_type=spk_document --post_status=any --format=count) in all, $(wp post list --post_type=spk_document --post_status=publish --format=count) published"
echo "events: $(wp post list --post_type=spk_event --post_status=any --format=count) in all, $(wp post list --post_type=spk_event --post_status=draft --format=count) drafts"
echo "blog_public: $(wp option get blog_public)"
wp cache flush
REMOTE
echo "first-run.sh: done. Next: wordpress/ops/check-live.sh $REF --resolve=<server IP> (before cutover)"
