#!/usr/bin/env bash
# Weekly integrity check, run ON THE HOST by an Enhance cron job (PLAN.md §5.8).
# deploy.sh copies it to ~/bin/weekly-check.sh. Cron command, for example:
#
#   SPOKARES_PING_URL=https://hc-ping.com/<uuid> ~/bin/weekly-check.sh
#
# (The ping URL lives in the cron command, not in WordPress.) Settings are read
# from ~/.spokares-weekly.env when it exists:
#
#   SPOKARES_WP_PATH=~/public_html
#   SPOKARES_ADMINS="<admin login 1> <admin login 2>"          # exactly these administrators
#   SPOKARES_EXPECTED_PLUGINS="spokares-core two-factor updraftplus"
#   SPOKARES_EXPECTED_THEMES="spokares <the spare default theme>"
#   SPOKARES_MU_EXTRA=""     # Enhance's own mu-plugins entries, recorded at setup
#   SPOKARES_DROPINS=""      # wp-content/*.php drop-ins besides index.php, recorded at setup
#   SPOKARES_ALERT_EMAIL=webmaster@spokares.org
#
# Success: pings SPOKARES_PING_URL (a dead-man service e-mails the admins when
# no success arrives within 8 days). Failure: pings <url>/fail with the summary
# and also tries wp_mail. Exit 1 on failure.
#
#   --find-only  only the file checks (used by check-live.sh over SSH)
#   --no-ping    don't ping or mail (a manual run)
set -uo pipefail

FIND_ONLY=0; PING=1
for arg in "$@"; do
  case $arg in
    --find-only) FIND_ONLY=1 ;;
    --no-ping) PING=0 ;;
    *) echo "usage: $0 [--find-only] [--no-ping]" >&2; exit 2 ;;
  esac
done
# shellcheck disable=SC1090
[[ -f ~/.spokares-weekly.env ]] && source ~/.spokares-weekly.env
WP_PATH=${SPOKARES_WP_PATH:-$HOME/public_html}
EXPECTED_PLUGINS=${SPOKARES_EXPECTED_PLUGINS:-spokares-core two-factor updraftplus}
EXPECTED_THEMES=${SPOKARES_EXPECTED_THEMES:-spokares}
ALERT=${SPOKARES_ALERT_EMAIL:-webmaster@spokares.org}

cd "$WP_PATH" 2>/dev/null || { echo "weekly-check: no WordPress folder at $WP_PATH" >&2; exit 1; }
problems=()
note() { problems+=("$*"); echo "FAIL  $*"; }
ok() { echo "ok    $*"; }

# Is every entry of <folder> in the allowed list? (index.php is always allowed.)
only_expected() { # only_expected <label> <folder> <allowed words...>
  local label=$1 dir=$2; shift 2
  local allowed=" index.php $* " extra=""
  [[ -d $dir ]] || { note "$label: $dir is missing"; return; }
  while IFS= read -r name; do
    [[ $allowed == *" $name "* ]] || extra="$extra $name"
  done < <(find "$dir" -mindepth 1 -maxdepth 1 -exec basename {} \; | sort)
  if [[ -z $extra ]]; then ok "$label: nothing unexpected"; else note "$label: unexpected:$extra"; fi
}

# --- file checks ----------------------------------------------------------------
php_uploads=$(find wp-content/uploads -type f \( -iname '*.php*' -o -iname '*.phtml' -o -iname '*.phar' -o -iname '*.pht' \) 2>/dev/null | head -20)
if [[ -z $php_uploads ]]; then ok "no PHP files under uploads/"; else note "PHP files under uploads/: $(echo $php_uploads)"; fi
only_expected "plugins/" wp-content/plugins $EXPECTED_PLUGINS
only_expected "themes/" wp-content/themes $EXPECTED_THEMES
only_expected "mu-plugins/" wp-content/mu-plugins spokares-hardening.php spokares-hardening ${SPOKARES_MU_EXTRA:-}
extra_php=""
while IFS= read -r f; do
  name=$(basename "$f")
  [[ " index.php ${SPOKARES_DROPINS:-} " == *" $name "* ]] || extra_php="$extra_php $name"
done < <(find wp-content -maxdepth 1 -type f -name '*.php')
if [[ -z $extra_php ]]; then ok "wp-content/*.php: only index.php and recorded drop-ins"; else note "unexpected wp-content/*.php:$extra_php"; fi

# --- WordPress checks ---------------------------------------------------------
if (( ! FIND_ONLY )); then
  if wp core verify-checksums --quiet >/dev/null 2>&1; then ok "core checksums"; else note "wp core verify-checksums failed"; fi
  if wp plugin verify-checksums --all --exclude=spokares-core --quiet >/dev/null 2>&1; then ok "plugin checksums"; else note "wp plugin verify-checksums failed"; fi
  if wp plugin is-active two-factor >/dev/null 2>&1; then ok "Two-Factor is active"; else note "Two-Factor is not active"; fi

  admins=$(wp user list --role=administrator --field=user_login 2>/dev/null | sort | tr '\n' ' ' | sed 's/ $//')
  want=$(echo "${SPOKARES_ADMINS:-}" | tr ' ' '\n' | grep -v '^$' | sort | tr '\n' ' ' | sed 's/ $//')
  if [[ -z $want ]]; then note "SPOKARES_ADMINS is not set in ~/.spokares-weekly.env (administrators now: $admins)"
  elif [[ $admins == "$want" ]]; then ok "administrators: $admins"
  else note "administrators are '$admins', expected '$want'"; fi

  no2fa=$(wp eval '
    if ( ! class_exists( "Two_Factor_Core" ) ) { echo "TWO-FACTOR-MISSING"; return; }
    foreach ( get_users( array( "capability" => "edit_posts", "fields" => "all" ) ) as $u ) {
      if ( ! Two_Factor_Core::get_enabled_providers_for_user( $u ) ) { echo $u->user_login, " "; }
    }' 2>&1)
  if [[ -z $no2fa ]]; then ok "every user who can edit has a Two-Factor method"; else note "users who can edit without Two-Factor: $no2fa"; fi

  devconst=$(wp eval 'foreach ( array( "SPOKARES_DEV", "SPOKARES_TODAY", "WP_DEVELOPMENT_MODE" ) as $c ) { if ( defined( $c ) && constant( $c ) ) { echo $c, " "; } }' 2>&1)
  if [[ -z $devconst ]]; then ok "no dev constants"; else note "dev constants defined: $devconst"; fi
fi

# --- result -------------------------------------------------------------------
if (( ${#problems[@]} == 0 )); then
  echo "weekly-check: all clear"
  if (( PING )) && [[ -n ${SPOKARES_PING_URL:-} ]]; then curl -fsS -m 10 --retry 3 -o /dev/null "$SPOKARES_PING_URL" || echo "weekly-check: success ping failed" >&2; fi
  exit 0
fi
summary=$(printf '%s\n' "${problems[@]}")
echo "weekly-check: ${#problems[@]} problem(s)"
if (( PING )); then
  [[ -n ${SPOKARES_PING_URL:-} ]] && curl -fsS -m 10 --retry 3 -o /dev/null --data-raw "$summary" "$SPOKARES_PING_URL/fail" || true
  SPK_SUMMARY="$summary" SPK_ALERT="$ALERT" wp eval 'wp_mail( getenv( "SPK_ALERT" ), "spokares.org weekly check FAILED", "The weekly integrity check found:\n\n" . getenv( "SPK_SUMMARY" ) . "\n\nSign in over SSH and follow the incident runbook." );' >/dev/null 2>&1 || true
fi
exit 1
