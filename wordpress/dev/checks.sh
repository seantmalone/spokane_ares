#!/usr/bin/env bash
# Checks against a running dev site (PLAN.md §6.10 "checks.sh").
#   wordpress/dev/checks.sh <PORT>
# Prints PASS/FAIL per check and exits 1 if anything failed. Directory
# listing and security-header checks on the real server live in ops/check-live.sh.
set -uo pipefail
[[ $# -eq 1 && $1 =~ ^[0-9]+$ ]] || { echo "usage: $0 <PORT>" >&2; exit 2; }
PORT=$1
URL=http://127.0.0.1:$PORT
LOG=/tmp/pg-$PORT.log
DEBUG=/tmp/pg-$PORT-logs/debug.log
TMP=$(mktemp -d "${TMPDIR:-/tmp}/spokares-checks.XXXXXX") && [[ -n $TMP && -d $TMP ]] || { echo "checks.sh: mktemp failed (check TMPDIR)" >&2; exit 2; }
trap 'rm -rf "$TMP"' EXIT
fails=0; passes=0

pass() { echo "PASS  $*"; passes=$((passes + 1)); }
fail() { echo "FAIL  $*"; fails=$((fails + 1)); }
status() { curl -s -o /dev/null -m 30 -w '%{http_code}' "$@"; }
location() { curl -s -o /dev/null -m 30 -w '%{redirect_url}' "$@"; }
expect_status() { # expect_status <code> <path> [label]
  local got; got=$(status "$URL$2")
  [[ $got == "$1" ]] && pass "$2 -> $got${3:+ ($3)}" || fail "$2 -> $got, expected $1${3:+ ($3)}"
}

curl -s -o /dev/null -m 10 "$URL/" || { echo "checks.sh: nothing answering on $URL (start it with start.sh $PORT)" >&2; exit 2; }

# --- the six pages: 200, one H1, no href="#", every §2.4 anchor -------------
declare -a PAGES=(
  "/|main what-we-do visit join this-week what-it-takes"
  "/how-it-works/|main follow-a-message nets weekly-net other-nets activation who-calls-us real-world alert-levels standing-order-1b message-handling ics-213 logs winlink exercises roles"
  "/about/|main ares-acs what-is-ares what-is-acs two-hats auxcomm what-we-are-not legal-basis who-we-serve agreements for-agencies leadership structure membership ares-path acs-path training-path acs-task-book arrl-task-book equipment licensing history w7gbu contact"
  "/members/|main search this-week rota quick-links open-slot"
  "/members/documents/|main search net-ops join forms training readiness digital reference not-here net-scripts ics-213 ics-309 go-kits public-service-tips ares-application wsdot-580-020"
  "/members/exercises/|main next-up wsdot-2026 set-2026 upcoming shakeout-2026 winlink-assignments public-service past"
)
for entry in "${PAGES[@]}"; do
  path=${entry%%|*}; anchors=${entry#*|}
  file=$TMP/page.html
  code=$(curl -s -m 30 -o "$file" -w '%{http_code}' "$URL$path")
  [[ $code == 200 ]] && pass "$path -> 200" || { fail "$path -> $code, expected 200"; continue; }
  h1=$(grep -o '<h1[ >]' "$file" | wc -l | tr -d ' ')
  [[ $h1 == 1 ]] && pass "$path has one <h1>" || fail "$path has $h1 <h1> elements"
  grep -q 'href="#"' "$file" && fail "$path contains href=\"#\"" || pass "$path has no href=\"#\""
  missing=""
  for a in $anchors; do grep -q "id=\"$a\"" "$file" || missing="$missing $a"; done
  [[ -z $missing ]] && pass "$path has all $(wc -w <<<"$anchors" | tr -d ' ') anchors" || fail "$path is missing anchors:$missing"
done

# --- setup results ---------------------------------------------------------
home=$(curl -s -m 30 "$URL/")
grep -q 'wp-block-cover__image-background wp-image-' <<<"$home" && grep -q 'srcset="[^"]*wp-content/uploads/' <<<"$home" \
  && pass "Home hero comes from the Media Library (wp-image-N + srcset)" || fail "Home hero is not the Media Library copy"
members=$(curl -s -m 30 "$URL/members/")
grep -q 'Net control' <<<"$members" && pass "/members/ contains \"Net control\" (uptime keyword)" || fail "/members/ lacks \"Net control\""
grep -q '<b>NZ2S</b>' <<<"$members" && pass "/members/ rota shows the seeded NZ2S row" || fail "/members/ rota lacks the seeded NZ2S row"
docs=$(curl -s -m 30 "$URL/members/documents/")
grep -q 'Showing all 37 documents' <<<"$docs" && pass "library shows all 37 documents" || fail "library does not show 37 documents"
grep -q 'spk-edit-link' <<<"$members$docs" && fail "logged-out pages show the editor-only edit links" || pass "no editor edit links when logged out"

# --- /docs/<slug>/ ---------------------------------------------------------
expect_status 302 /docs/ics-213/ "published link document"
loc=$(location "$URL/docs/ics-213/")
[[ $loc == https://* ]] && pass "/docs/ics-213/ redirects to an https URL ($loc)" || fail "/docs/ics-213/ redirects to '$loc'"
expect_status 404 /docs/ncs-principles/ "a \"Soon\" document"
expect_status 404 /docs/no-such-document/ "unknown slug"
echo "NOTE  a Draft document's /docs/<slug>/ -> 404 needs a Draft; the dev seed publishes every document (see README: production-mode check)"

# --- attachment pages: never a redirect to the file by its number (§5.5) ----
att=$(grep -oE 'wp-image-[0-9]+' <<<"$home" | head -1 | sed 's/wp-image-//')
if [[ -n $att ]]; then
  for q in "/?attachment_id=$att" "/?p=$att"; do
    got=$(curl -s -o /dev/null -m 30 -w '%{http_code} %{redirect_url}' "$URL$q")
    [[ $got == "404 " ]] && pass "$q -> 404, no redirect" || fail "$q -> '$got', expected 404 with no redirect"
  done
else
  fail "no wp-image-N on Home, so the attachment check can't run"
fi

# --- the must-use plugin, logged out ---------------------------------------
expect_status 403 /xmlrpc.php
expect_status 404 /wp-json/wp/v2/users "REST users"
expect_status 401 /wp-json/wp/v2/pages "anonymous /wp/v2/*"
expect_status 404 "/?author=1" "author enumeration"
expect_status 404 /feed/ "feeds off"
oembed=$(curl -s -m 30 "$URL/wp-json/oembed/1.0/embed?url=$URL/")
[[ -n $oembed && $oembed != *author_name* ]] && pass "oEmbed has no author_name" || fail "oEmbed exposes author_name (or is empty)"
expect_status 301 "/index.php?option=com_content&view=article&id=11" "old Joomla URL"
expect_status 410 "/index.php?option=com_x" "unknown Joomla URL"
expect_status 410 "/Downloads/ARES%20NET%20ROSTER%20FEB09.doc" "roster URL"
lp=$(curl -s -o /dev/null -m 30 -w '%{http_code} %{redirect_url}' -X POST \
  --data-urlencode 'user_login=nobody-by-this-name' --data 'redirect_to=&wp-submit=Get+New+Password' \
  "$URL/wp-login.php?action=lostpassword")
[[ $lp == 302*checkemail=confirm* ]] && pass "lost password for an unknown user -> checkemail=confirm" || fail "lost password for an unknown user -> $lp"

# --- dev switches -------------------------------------------------------------
dl=$(location "$URL/wp-admin/?dev_login=editor")
[[ $dl == */wp-admin/ ]] && pass "?dev_login=editor signs in and drops the parameter" || fail "?dev_login=editor -> '$dl'"
t=$(curl -s -m 30 "$URL/members/?today=2026-10-02" | tr '\n' ' ' | grep -o '<tr class="is-next">.*' | cut -c1-400)
grep -q 'tag--open' <<<"$t" && pass "?today=2026-10-02 puts the Oct 6 open slot first" || fail "?today=2026-10-02 does not start the rota with an open slot"

# --- PHP notices ----------------------------------------------------------------
# WordPress also writes informational lines here (e.g. "Automatic updates
# starting..."); only real PHP messages and database errors count.
PHP_RE='PHP (Warning|Notice|Deprecated|Fatal error|Parse error|Recoverable fatal error)|WordPress database error'
if grep -Eq "$PHP_RE" "$DEBUG" 2>/dev/null; then
  fail "PHP notices in $DEBUG:"; grep -E "$PHP_RE" "$DEBUG" | head -n 20 | sed 's/^/      /'
else
  pass "no PHP notices in $DEBUG"
  if [[ -s $DEBUG ]]; then echo "INFO  other lines in $DEBUG:"; head -n 5 "$DEBUG" | sed 's/^/      /'; fi
fi
if grep -Eiq 'PHP (Warning|Notice|Deprecated|Fatal|Parse)' "$LOG" 2>/dev/null; then
  fail "PHP messages in $LOG:"; grep -Ei 'PHP (Warning|Notice|Deprecated|Fatal|Parse)' "$LOG" | head -n 20 | sed 's/^/      /'
else
  pass "no PHP messages in $LOG"
fi
if grep -Eq 'FAILED|SKIP' "/tmp/pg-$PORT-logs/setup.log" 2>/dev/null; then
  fail "setup steps failed or were skipped:"; grep -E 'FAILED|SKIP' "/tmp/pg-$PORT-logs/setup.log" | sed 's/^/      /'
else
  pass "every setup step ran (/tmp/pg-$PORT-logs/setup.log)"
fi

echo "checks: $passes passed, $fails failed"
exit $(( fails > 0 ))
