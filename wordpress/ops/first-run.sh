#!/usr/bin/env bash
# One-time site setup after the FIRST deploy (PLAN.md §5.11, §6.10 "Production
# seeding"). Run from the admin's machine after deploy.sh (whose post-deploy
# step activates the theme, spokares-core, Two-Factor and UpdraftPlus):
#
#   wordpress/ops/first-run.sh <tag|ref> <staging|production> --preview
#   wordpress/ops/first-run.sh <tag|ref> <staging|production> [--yes]
#   wordpress/ops/first-run.sh <tag|ref> <staging|production> --launch-cleanup [--yes]
#
# --preview (read-only; run it first): what is on the host now and what a run
#   would do. Nothing is uploaded or changed.
#
# Default: the site structure. From a clean export of <ref>, and nothing else
#   from dev/ (no dev login, no test code), it copies dev/seed/import.php,
#   data.json, extra.json and dev/setup/pages.php into a private temporary
#   folder in the SSH user's home (outside public_html), runs them with
#   wp eval-file, and deletes the folder whatever happens:
#   1. seed/import.php production: every document a Draft with no Privacy
#      tick, "Needs checking" events Drafts, and the rest of §6.10's
#      production rules (idempotent: does nothing once spk_seeded is set)
#   2. setup/pages.php: the six pages from the theme's patterns, the Home
#      hero in the Media Library, the static front page (idempotent:
#      existing pages are left alone). WordPress's sample content is kept.
#
# --launch-cleanup: the §5.11 / security-hosting.md §11 launch clean-up, a
#   separate decision (nothing is uploaded):
#   a. deletes WordPress's untouched sample content (Sample Page,
#      "Hello world!", the Privacy Policy draft)
#   b. deletes Akismet and Hello Dolly and every default theme except one
#      spare (twentytwentyfive), only while they are inactive
#   c. lets UpdraftPlus create its deny-all backup folder
#   d. adds security-hosting.md §4.4's two rewrite rules above
#      "# BEGIN WordPress" (no PHP under uploads; no direct wp-includes/*.php);
#      the previous .htaccess is kept as ~/.spokares-htaccess.before-first-run
#   e. writes ~/.spokares-weekly.env for weekly-check.sh if it is missing
#
# Every mode ends by printing the pages, the front page, the ares_editor role
# and the document counts. Safe to run again. Needs ops.env, like deploy.sh.
set -euo pipefail
source "$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/lib.sh"

usage() { echo "usage: $0 <tag|ref> <staging|production> [--preview | --launch-cleanup] [--yes]" >&2; exit 2; }
[[ $# -ge 2 ]] || usage
REF=$1; TARGET=$2; shift 2
YES=0; MODE=setup
for arg in "$@"; do
  case $arg in
    --yes) YES=1 ;;
    --preview) MODE=preview ;;
    --launch-cleanup) MODE=cleanup ;;
    *) usage ;;
  esac
done
spk_load_env
spk_target "$TARGET"
WP=$SPOKARES_WP_PATH

git -C "$SPK_ROOT" rev-parse -q --verify "$REF^{commit}" >/dev/null || spk_die "no git tag, branch or commit '$REF'"
WORK=$(mktemp -d "${TMPDIR:-/tmp}/spokares-first-run.XXXXXX")
REMOTE_DIR=""
cleanup() {
  rm -rf "$WORK"
  if [[ -n $REMOTE_DIR ]]; then
    ssh "$SPK_SSH" "rm -rf ~/'$REMOTE_DIR'" 2>/dev/null || echo "first-run.sh: could not remove ~/$REMOTE_DIR on $SPK_SSH; delete it by hand" >&2
  fi
}
trap cleanup EXIT

case $MODE in
  preview) echo "first-run.sh: PREVIEW of $TARGET ($SPK_SSH:$WP); nothing will be changed" ;;
  setup)   echo "first-run.sh: $REF -> $TARGET ($SPK_SSH:$WP): import (production mode) and the six pages" ;;
  cleanup) echo "first-run.sh: launch clean-up on $TARGET ($SPK_SSH:$WP): sample content, Akismet, Hello Dolly, spare themes, UpdraftPlus folder, .htaccess rules, ~/.spokares-weekly.env" ;;
esac
if [[ $MODE != preview && $TARGET == production ]] && (( ! YES )); then
  read -r -p "Run this on PRODUCTION? Type 'production' to continue: " answer
  [[ $answer == production ]] || spk_die "cancelled"
fi

DIR_ARG=-
if [[ $MODE == setup ]]; then
  # Only these four files leave the machine.
  mkdir -p "$WORK/upload/seed" "$WORK/upload/setup"
  git -C "$SPK_ROOT" archive --format=tar "$REF" wordpress/dev/seed/import.php wordpress/dev/seed/data.json wordpress/dev/seed/extra.json wordpress/dev/setup/pages.php \
    | tar -x -C "$WORK" || spk_die "git archive of $REF failed (does it have dev/seed and dev/setup/pages.php?)"
  cp "$WORK/wordpress/dev/seed/import.php" "$WORK/wordpress/dev/seed/data.json" "$WORK/wordpress/dev/seed/extra.json" "$WORK/upload/seed/"
  cp "$WORK/wordpress/dev/setup/pages.php" "$WORK/upload/setup/"
  REMOTE_DIR=".spokares-first-run-$(date +%Y%m%d%H%M%S)-$$"
  ssh "$SPK_SSH" "umask 077 && mkdir ~/'$REMOTE_DIR'"
  rsync -rt -e ssh "$WORK/upload/" "$SPK_SSH:$REMOTE_DIR/"
  DIR_ARG="\$HOME/$REMOTE_DIR"
fi

# The host side, in one SSH session. Arguments: mode, WordPress folder, temp folder (or -).
ssh "$SPK_SSH" "bash -s -- '$MODE' '$WP' \"$DIR_ARG\"" <<'REMOTE'
set -euo pipefail
MODE=$1; WP=$2; DIR=$3
[[ $DIR != - ]] && trap 'rm -rf "$DIR"' EXIT
cd "$WP"
wp() { command wp "$@" < /dev/null; }   # the script is on stdin
yesno() { if "$@" >/dev/null 2>&1; then echo yes; else echo no; fi; }

wp plugin is-active spokares-core || { echo "first-run: spokares-core is not active; run deploy.sh first" >&2; exit 1; }
[[ $(wp option get stylesheet) == spokares ]] || { echo "first-run: the spokares theme is not active; run deploy.sh first" >&2; exit 1; }
ADMIN=$(wp user list --role=administrator --field=ID --orderby=ID --order=ASC | head -1)
[[ -n $ADMIN ]] || { echo "first-run: no administrator" >&2; exit 1; }

if [[ $MODE == preview ]]; then
  echo "--- now on the host"
  echo "theme: $(wp option get stylesheet); spokares-core active: yes; administrators: $(wp user list --role=administrator --format=count)"
  seeded=$(wp option get spk_seeded 2>/dev/null || true)
  echo "spk_seeded: ${seeded:-not set} (import would $([[ -n $seeded ]] && echo 'do nothing' || echo 'run in production mode: documents as Drafts'))"
  for path in home how-it-works about members members/documents members/exercises; do
    id=$(wp eval "\$p = get_page_by_path( '$path', OBJECT, 'page' ); echo \$p ? \$p->ID . ' ' . \$p->post_status : '';")
    echo "page /$path/: ${id:-missing (would be created)}"
  done
  echo "front page: show_on_front=$(wp option get show_on_front), page_on_front=$(wp option get page_on_front)"
  echo "sample content: $(wp eval 'echo get_page_by_path( "sample-page" ) ? "Sample Page present" : "no Sample Page";'), $(wp post list --post_type=post --format=count) post(s) (kept by the default run)"
  echo "plugins: $(wp plugin list --field=name | tr '\n' ' ')"
  echo "themes: $(wp theme list --field=name | tr '\n' ' ')"
  echo ".htaccess spokares hardening block: $(yesno grep -q '^# BEGIN spokares hardening' .htaccess); PRE-LAUNCH block: $(yesno grep -q 'spokares PRE-LAUNCH' .htaccess)"
  echo "wp-content/updraft: $(yesno test -d wp-content/updraft); ~/.spokares-weekly.env: $(yesno test -f ~/.spokares-weekly.env)"
  echo "ares_editor role: $(yesno wp role exists ares_editor); blog_public: $(wp option get blog_public)"
  echo "--- default run: 1 import (production mode), 2 the six pages; sample content kept"
  echo "--- --launch-cleanup: sample content, Akismet, Hello Dolly, twentytwentythree/-four deleted; UpdraftPlus folder; .htaccess rules; weekly env"
  exit 0
fi

if [[ $MODE == setup ]]; then
  echo "--- 1. import, production mode"
  wp eval-file "$DIR/seed/import.php" production --user="$ADMIN"

  echo "--- 2. pages (sample content kept)"
  SPK_SETUP_DIR=$DIR wp eval '
    define( "SPOKARES_DEV_IMPORT_LIBRARY", true );
    require getenv( "SPK_SETUP_DIR" ) . "/setup/pages.php";
    $r = spokares_dev_setup_pages();
    foreach ( $r["log"] as $line ) { WP_CLI::log( $line ); }
    flush_rewrite_rules( false );
  ' --user="$ADMIN"
fi

if [[ $MODE == cleanup ]]; then
  echo "--- a. sample content"
  wp eval '
    foreach ( array( array( "sample-page", "page" ), array( "hello-world", "post" ) ) as $s ) {
      $p = get_page_by_path( $s[0], OBJECT, $s[1] );
      if ( ! $p ) { continue; }
      if ( $p->post_modified_gmt !== $p->post_date_gmt ) { WP_CLI::log( "kept " . $s[0] . " (edited since install)" ); continue; }
      wp_delete_post( $p->ID, true ); WP_CLI::log( "deleted the sample " . $s[1] . " " . $s[0] );
    }
    $pp = (int) get_option( "wp_page_for_privacy_policy" );
    if ( $pp && "draft" === get_post_status( $pp ) && get_post( $pp )->post_modified_gmt === get_post( $pp )->post_date_gmt ) {
      wp_delete_post( $pp, true ); update_option( "wp_page_for_privacy_policy", 0 ); WP_CLI::log( "deleted the Privacy Policy draft" );
    }
  ' --user="$ADMIN"

  echo "--- b. extras"
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

  echo "--- c. UpdraftPlus backup folder"
  wp eval 'global $updraftplus; if ( is_object( $updraftplus ) && method_exists( $updraftplus, "backups_dir_location" ) ) { echo "backup folder: ", str_replace( ABSPATH, "", $updraftplus->backups_dir_location() ), "\n"; } else { echo "UpdraftPlus is not active\n"; }'

  echo "--- d. .htaccess rewrite rules"
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

  echo "--- e. ~/.spokares-weekly.env"
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
fi

echo "--- result"
wp post list --post_type=page --post_status=any --fields=ID,post_title,post_name,post_parent,post_status --orderby=menu_order --order=ASC
echo "front page: show_on_front=$(wp option get show_on_front), page_on_front=$(wp option get page_on_front)"
echo "ares_editor role: $(yesno wp role exists ares_editor)"
echo "documents: $(wp post list --post_type=spk_document --post_status=any --format=count) in all, $(wp post list --post_type=spk_document --post_status=publish --format=count) published"
echo "events: $(wp post list --post_type=spk_event --post_status=any --format=count) in all, $(wp post list --post_type=spk_event --post_status=draft --format=count) drafts"
echo "blog_public: $(wp option get blog_public)"
wp cache flush
REMOTE
echo "first-run.sh: done ($MODE)."
