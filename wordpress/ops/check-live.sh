#!/usr/bin/env bash
# Compare the live site with a git tag and check it from outside (PLAN.md §5.8,
# §5.11 step 11). Run from the admin's machine monthly and after every deploy.
#
#   wordpress/ops/check-live.sh <tag> [--staging] [--no-ssh] [--resolve=<ip>] [--url=<url>]
#
# Before the DNS cutover, spokares.org still resolves to the OLD host:
#   --resolve=<ip>  send every curl check to <ip> under the site's own name
#                   (curl --resolve), and accept its self-signed certificate
#                   (-k); ops/DEPLOY-RUNBOOK.md has the address
#   --url=<url>     check another address instead of the ops.env URL, e.g.
#                   the Enhance preview domain
# <tag> may also be a branch or commit that deploy.sh deployed.
#
# 1. Over SSH, an `rsync -rcn --delete` dry run of the theme, the plugin and the
#    must-use plugin against a clean export of the tag: any listed file is a
#    difference (a changed, added or deleted file on the host). The host's
#    copy is compared with git, not with a manifest the site user could rewrite.
# 2. The file checks of the tag's weekly-check.sh, piped over SSH (not the
#    host's copy): no PHP under uploads, no unexpected plugins, themes,
#    mu-plugins or drop-ins.
# 3. curl checks from outside (no SSH needed; --no-ssh runs only these).
# Exit 1 if anything failed.
set -uo pipefail
source "$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/lib.sh"

usage() { echo "usage: $0 <tag> [--staging] [--no-ssh] [--resolve=<ip>] [--url=<url>]" >&2; exit 2; }
[[ $# -ge 1 && $1 != --* ]] || usage
TAG=$1; shift
TARGET=production; SSH_CHECKS=1; RESOLVE_IP=""; URL=""
for arg in "$@"; do
  case $arg in
    --staging) TARGET=staging ;;
    --no-ssh) SSH_CHECKS=0 ;;
    --resolve=*) RESOLVE_IP=${arg#--resolve=} ;;
    --url=*) URL=${arg#--url=} ;;
    *) usage ;;
  esac
done
spk_load_env
spk_target "$TARGET"
WP=$SPOKARES_WP_PATH
U=${URL:-$SPK_URL}
U=${U%/}

# Every curl below goes through CURL: plain, or pinned to RESOLVE_IP.
CURL=(curl -s)
if [[ -n $RESOLVE_IP ]]; then
  [[ $RESOLVE_IP =~ ^[0-9a-fA-F.:]+$ ]] || spk_die "--resolve wants an IP address, not '$RESOLVE_IP'"
  h=${U#*://}; h=${h%%/*}; h=${h%%:*}
  CURL+=(-k --resolve "$h:443:$RESOLVE_IP" --resolve "$h:80:$RESOLVE_IP")
  echo "NOTE  curl checks go to $RESOLVE_IP for $h (--resolve), certificate not verified (-k): for use before the DNS cutover only"
fi

WORK=$(mktemp -d "${TMPDIR:-/tmp}/spokares-check-live.XXXXXX") && [[ -n $WORK && -d $WORK ]] || { echo "check-live.sh: cannot create a work folder (mktemp failed; check TMPDIR)" >&2; exit 1; }
trap 'rm -rf "$WORK"' EXIT
echo "check-live.sh: $TARGET ($U) against $TAG"

# --- 1 and 2: over SSH ---------------------------------------------------------
if (( SSH_CHECKS )); then
  spk_export_tag "$TAG" "$WORK"
  W=$WORK/wordpress
  compare() { # compare <label> <local path> <remote path> [rsync args...]
    local label=$1 src=$2 dst=$3; shift 3
    local out
    out=$(rsync -rcn --itemize-changes --exclude=.DS_Store -e ssh "$@" "$src" "$SPK_SSH:$WP/$dst" 2>&1)
    if [[ $? -ne 0 ]]; then spk_fail "$label: rsync failed: $out"; return; fi
    out=$(echo "$out" | grep -v '^\.[df] ' | grep -v '^$' || true)   # unchanged entries start with '.'
    if [[ -z $out ]]; then spk_pass "$label matches $TAG"; else spk_fail "$label differs from $TAG:"; echo "$out" | head -30 | sed 's/^/      /'; fi
  }
  compare "theme spokares" "$W/theme/spokares/" wp-content/themes/spokares/ --delete
  compare "plugin spokares-core" "$W/plugins/spokares-core/" wp-content/plugins/spokares-core/ --delete
  compare "mu-plugins/spokares-hardening.php" "$W/mu-plugins/spokares-hardening.php" wp-content/mu-plugins/
  compare "mu-plugins/spokares-hardening/" "$W/mu-plugins/spokares-hardening/" wp-content/mu-plugins/spokares-hardening/ --delete
  deployed=$(ssh "$SPK_SSH" 'cat ~/.spokares-deployed-tag 2>/dev/null' || true)
  label=$(spk_ref_label "$TAG")
  [[ ${deployed%% *} == "$label" ]] && spk_pass "~/.spokares-deployed-tag says $deployed" || echo "NOTE  ~/.spokares-deployed-tag says '${deployed:-nothing}', expected $label"

  out=$(ssh "$SPK_SSH" "SPOKARES_WP_PATH='$WP' bash -s -- --find-only --no-ping" < "$W/ops/weekly-check.sh" 2>&1)
  if [[ $? -eq 0 ]]; then spk_pass "host file checks (tag's weekly-check.sh --find-only)"; else spk_fail "host file checks:"; echo "$out" | grep -v '^ok ' | sed 's/^/      /'; fi

  # The upload cap (PLAN §5.3: 32 MB, set in php.ini). WP-CLI reads the CLI's
  # php.ini, which can differ from the web server's (a .user.ini, say), so a
  # mismatch is a NOTE to confirm on Media > Add New, not a failure.
  cap=$(ssh "$SPK_SSH" "cd '$WP' && wp eval 'echo ini_get( \"upload_max_filesize\" ), \" \", ini_get( \"post_max_size\" ), \" \", wp_max_upload_size();'" 2>/dev/null || true)
  read -r cap_upload cap_post cap_bytes <<<"$cap"
  if [[ $cap_bytes == 33554432 ]]; then
    spk_pass "upload cap 32 MB (upload_max_filesize=$cap_upload, post_max_size=$cap_post; CLI php.ini)"
  else
    echo "NOTE  upload cap: upload_max_filesize=${cap_upload:-?}, post_max_size=${cap_post:-?}, wp_max_upload_size=${cap_bytes:-?} bytes (CLI php.ini); PLAN §5.3 wants 32 MB. Confirm the web value on Media > Add New (\"Maximum upload file size: 32 MB\")"
  fi
else
  spk_skip "SSH comparison and host file checks (--no-ssh)"
fi

# --- 3: from outside -------------------------------------------------------------
code() { "${CURL[@]}" -o /dev/null -m 30 -w '%{http_code}' "$@"; }
expect() { # expect <codes> <path> [label]
  local got; got=$(code "$U$2")
  [[ " $1 " == *" $got "* ]] && spk_pass "$2 -> $got${3:+ ($3)}" || spk_fail "$2 -> $got, expected $1${3:+ ($3)}"
}
expect 200 / "home"
expect 200 /members/ "members hub"
hub=$("${CURL[@]}" -m 30 "$U/members/")   # not piped into grep -q: with pipefail, curl's SIGPIPE would fail the check
grep -q 'Net control' <<<"$hub" && spk_pass "/members/ shows \"Net control\"" || spk_fail "/members/ lacks \"Net control\" (is spokares-core active?)"
expect 403 /xmlrpc.php
expect 404 /wp-json/wp/v2/users "REST users, logged out"
expect 401 /wp-json/wp/v2/pages "anonymous REST"
expect 404 "/?author=1" "author enumeration"
expect 404 /feed/ "feeds off"
oembed=$("${CURL[@]}" -m 30 "$U/wp-json/oembed/1.0/embed?url=$U/")
[[ -n $oembed && $oembed != *author_name* ]] && spk_pass "oEmbed has no author_name" || spk_fail "oEmbed exposes author_name (or is empty)"
expect 403 /wp-content/uploads/x.php "no PHP under uploads (.htaccess rule)"
listing=$("${CURL[@]}" -m 30 -w '\n%{http_code}' "$U/wp-content/uploads/$(date +%Y)/")
lcode=$(echo "$listing" | tail -1)
if [[ $lcode == 403 || $lcode == 404 ]] || ! grep -qi 'Index of' <<<"$listing"; then spk_pass "/wp-content/uploads/$(date +%Y)/ is not listable ($lcode)"; else spk_fail "/wp-content/uploads/$(date +%Y)/ is listable"; fi
expect 403 /wp-content/updraft/ "UpdraftPlus folder"
expect 301 "/index.php?option=com_content&view=article&id=11" "old Joomla URL"
expect 410 "/index.php?option=com_x" "unknown Joomla URL"
expect 410 "/Downloads/ARES%20NET%20ROSTER%20FEB09.doc" "roster URL"
expect 410 /Memberlist.pdf "roster URL"
expect 404 /docs/no-such-document/ "unknown document link"
# Attachment pages never lead to a file by its number (§5.5): the hero photo's
# attachment id (from Home's wp-image-N) must give 404, with no redirect, by
# ?attachment_id= and by ?p=. An orphaned document file is reached the same way.
att=$("${CURL[@]}" -m 30 "$U/" | grep -oE 'wp-image-[0-9]+' | head -1 | sed 's/wp-image-//')
if [[ -n $att ]]; then
  for q in "/?attachment_id=$att" "/?p=$att"; do
    got=$("${CURL[@]}" -o /dev/null -m 30 -w '%{http_code} %{redirect_url}' "$U$q")
    [[ $got == "404 " ]] && spk_pass "$q -> 404, no redirect (attachment pages off)" || spk_fail "$q -> $got, expected 404 with no redirect"
  done
else
  spk_fail "no wp-image-N on Home: cannot check /?attachment_id= (is the hero from the Media Library?)"
fi
rm_code=$(code "$U/readme.html")
[[ $rm_code == 200 ]] && echo "NOTE  /readme.html -> 200: it names the WordPress version (deny it at the web server; ops/README.md)" || spk_pass "/readme.html -> $rm_code"
powered=$("${CURL[@]}" -o /dev/null -D - -m 30 "$U/" | tr -d '\r' | grep -i '^x-powered-by:' || true)
[[ -z $powered ]] && spk_pass "no X-Powered-By header on /" || spk_fail "X-Powered-By is sent: $powered"
# Every header and every CSP directive of PLAN §5.3, on the front end, the
# sign-in page, and wp-admin. Signed out, /wp-admin/ redirects before
# admin_init, so admin-ajax.php and admin-post.php (which run admin_init for
# anyone) stand in for the signed-in screens: core's send_frame_options_header()
# on admin_init is what cut the policy down to frame-ancestors (QA-007).
# The runbook's go-live list checks one signed-in screen by hand.
CSP_DIRECTIVES=("frame-ancestors 'self'" "base-uri 'self'" "form-action 'self'" "object-src 'none'")
for path in / /wp-login.php /wp-admin/ "/wp-admin/admin-ajax.php?action=spokares-header-check" "/wp-admin/admin-post.php?action=spokares-header-check"; do
  h=$("${CURL[@]}" -o /dev/null -D - -m 30 "$U$path" | tr -d '\r')
  missing=""
  grep -qi '^x-content-type-options: *nosniff' <<<"$h" || missing="$missing X-Content-Type-Options"
  grep -qi '^referrer-policy: *strict-origin-when-cross-origin' <<<"$h" || missing="$missing Referrer-Policy"
  grep -qi '^x-frame-options: *sameorigin' <<<"$h" || missing="$missing X-Frame-Options"
  grep -qi '^permissions-policy:' <<<"$h" || missing="$missing Permissions-Policy"
  csp=$(grep -i '^content-security-policy:' <<<"$h")   # every policy sent is enforced, so any may carry a directive
  for d in "${CSP_DIRECTIVES[@]}"; do
    grep -qiF "$d" <<<"$csp" || missing="$missing CSP($d)"
  done
  [[ $U == https://* ]] && { grep -qi '^strict-transport-security:' <<<"$h" || missing="$missing HSTS"; }
  [[ -z $missing ]] && spk_pass "security headers on $path" || spk_fail "security headers missing on $path:$missing"
done
lp=$("${CURL[@]}" -o /dev/null -m 30 -w '%{http_code} %{redirect_url}' -X POST \
  --data-urlencode "user_login=nobody-$(date +%s)" --data 'redirect_to=&wp-submit=Get+New+Password' "$U/wp-login.php?action=lostpassword")
[[ $lp == 302*checkemail=confirm* ]] && spk_pass "lost password for an unknown user -> checkemail=confirm" || spk_fail "lost password for an unknown user -> $lp"

echo "check-live.sh: $spk_passes passed, $spk_fails failed"
exit $(( spk_fails > 0 ))
