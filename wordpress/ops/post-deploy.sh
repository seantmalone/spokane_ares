#!/usr/bin/env bash
# Post-deploy steps, run ON THE HOST in one SSH session (PLAN.md §5.7).
# deploy.sh pipes the deployed ref's copy of this file over SSH:
#
#   ssh <host> "SPOKARES_WP_PATH=public_html bash -s -- <label> [--flush-rewrites]" < post-deploy.sh
#
# Every step is idempotent, so running it after every deploy is safe:
#   1. permissions on our code: folders 755, files 644 (the rsync keeps no modes)
#   2. Two-Factor and UpdraftPlus: installed from wordpress.org and activated
#      if missing, with WordPress's own plugin auto-updates on (PLAN §5.7:
#      third-party code is patched with nobody in the loop)
#   3. the spokares theme and the spokares-core plugin active (a deploy means
#      "this code should run", even if someone deactivated it while troubleshooting)
#   4. before launch (while .htaccess still has the "spokares PRE-LAUNCH"
#      block), Settings > Reading "Discourage search engines" stays on
#   5. `wp rewrite flush` (with --flush-rewrites, or when this run activated
#      spokares-core) and `wp cache flush`
#   6. the label goes into ~/.spokares-deployed-tag
# No secrets are read or printed. Exit 1 on the first failed step.
set -euo pipefail

LABEL=${1:?usage: post-deploy.sh <label> [--flush-rewrites]}
FLUSH=0
[[ ${2:-} == --flush-rewrites ]] && FLUSH=1
WP_PATH=${SPOKARES_WP_PATH:-public_html}
cd "$WP_PATH" 2>/dev/null || { echo "post-deploy: no WordPress folder at $WP_PATH" >&2; exit 1; }
[[ -f wp-config.php ]] || { echo "post-deploy: no wp-config.php in $WP_PATH" >&2; exit 1; }
say() { echo "post-deploy: $*"; }
# This script arrives on bash's stdin: keep wp-cli from reading the rest of it.
wp() { command wp "$@" < /dev/null; }

# 1. Permissions.
ours="themes/spokares plugins/spokares-core mu-plugins/spokares-hardening"
(cd wp-content && find $ours -type d -exec chmod 755 {} + && find $ours -type f -exec chmod 644 {} + && chmod 644 mu-plugins/spokares-hardening.php)
[[ -f ~/bin/weekly-check.sh ]] && chmod 755 ~/bin/weekly-check.sh
say "permissions set (folders 755, files 644)"

# 2. Third-party plugins from wordpress.org.
for slug in two-factor updraftplus; do
  if ! wp plugin is-installed "$slug"; then
    wp plugin install "$slug" --activate
  elif ! wp plugin is-active "$slug"; then
    wp plugin activate "$slug"
  fi
  if [[ $(wp plugin auto-updates status "$slug" --field=status) != enabled ]]; then
    wp plugin auto-updates enable "$slug"
  fi
done
say "two-factor and updraftplus active, auto-updates on"

# 3. Our theme and plugin.
if [[ $(wp option get stylesheet) != spokares ]]; then
  wp theme activate spokares
fi
if ! wp plugin is-active spokares-core; then
  wp plugin activate spokares-core
  FLUSH=1
fi
say "theme $(wp option get stylesheet), spokares-core $(wp eval 'echo defined( "SPOKARES_CORE_VERSION" ) ? SPOKARES_CORE_VERSION : "NOT LOADED";'), hardening $(wp eval 'echo defined( "SPOKARES_HARDENING_VERSION" ) ? SPOKARES_HARDENING_VERSION : "NOT LOADED";')"

# 4. No indexing before launch.
if grep -q 'spokares PRE-LAUNCH' .htaccess 2>/dev/null; then
  if [[ $(wp option get blog_public) != 0 ]]; then
    wp option update blog_public 0
  fi
  say "pre-launch: search engines discouraged (blog_public 0; .htaccess sends X-Robots-Tag noindex)"
fi

# 5. Rewrite rules and caches.
if (( FLUSH )); then
  wp rewrite flush
fi
wp cache flush

# 6. Record what is deployed.
printf '%s %s\n' "$LABEL" "$(date -u +%Y-%m-%dT%H:%M:%SZ)" > ~/.spokares-deployed-tag
say "done: ~/.spokares-deployed-tag = $(cat ~/.spokares-deployed-tag)"
