#!/usr/bin/env bash
# DEV ONLY. Run the PHP integration tests (dev/tests/php/*-test.php) inside the
# running dev site and print PASS/FAIL per test.
#
#   wordpress/dev/tests/run-php.sh <PORT> [filter] [--list]
#
# filter: a case-insensitive substring of "<file>::<test name>", e.g. "rota"
# or "accounts-test.php::". --list prints the matching tests without running.
# The tests run in one request to /wp-admin/admin-post.php?action=spokares_dev_tests
# (dev/mu-plugins/spokares-dev-qa.php, loopback + local + SPOKARES_DEV only).
# Exit 0 when every test passed (or was skipped), 1 on any failure, 2 on usage
# or when the site is not answering.
set -uo pipefail
usage() { echo "usage: $0 <PORT> [filter] [--list]" >&2; exit 2; }
[[ $# -ge 1 && $1 =~ ^[0-9]+$ ]] || usage
PORT=$1; shift
FILTER=""; LIST=0
for arg in "$@"; do
  case $arg in
    --list) LIST=1 ;;
    -*) usage ;;
    *) FILTER=$arg ;;
  esac
done
URL=http://127.0.0.1:$PORT
DEBUG=/tmp/pg-$PORT-logs/debug.log
TMP=$(mktemp -d "${TMPDIR:-/tmp}/spokares-php-tests.XXXXXX") && [[ -n $TMP && -d $TMP ]] || { echo "run-php.sh: mktemp failed" >&2; exit 2; }
trap 'rm -rf "$TMP"' EXIT

curl -s -o /dev/null -m 10 "$URL/" || { echo "run-php.sh: nothing answering on $URL (start it with wordpress/dev/start.sh $PORT)" >&2; exit 2; }

enc=$(node -e 'process.stdout.write(encodeURIComponent(process.argv[1] || ""))' "$FILTER")
q="action=spokares_dev_tests&filter=$enc"
(( LIST )) && q="$q&list=1"
before=$( [[ -f $DEBUG ]] && wc -c <"$DEBUG" | tr -d ' ' || echo 0 )
code=$(curl -s -m 600 -o "$TMP/out.json" -w '%{http_code}' "$URL/wp-admin/admin-post.php?$q")

node - "$TMP/out.json" "$code" "$LIST" <<'EOF'
const fs = require('fs');
const [file, code, list] = process.argv.slice(2);
const body = fs.readFileSync(file, 'utf8');
let r;
try { r = JSON.parse(body); } catch {
  console.log(`FAIL  the test endpoint did not answer with JSON (HTTP ${code}). First lines:`);
  console.log(body.split('\n').slice(0, 15).map((l) => '      ' + l.slice(0, 200)).join('\n') || '      (empty body)');
  console.log('      Is dev/mu-plugins/spokares-dev-qa.php loaded? It is written by setup/mu.php at start; restart the site after adding it.');
  process.exit(1);
}
if (list === '1') {
  for (const t of r) console.log(t);
  console.log(`php tests: ${r.length} match`);
  process.exit(0);
}
for (const t of r.tests) {
  const tag = { pass: 'PASS', fail: 'FAIL', error: 'ERROR', skip: 'SKIP' }[t.status] || t.status;
  const info = t.status === 'pass' ? `  (${t.assertions} assertion${t.assertions === 1 ? '' : 's'}, ${t.ms} ms${t.message ? ', ' + t.message : ''})` : '';
  console.log(`${tag.padEnd(5)} ${t.file} :: ${t.name}${info}`);
  if (t.status !== 'pass') {
    if (t.message) console.log(`      ${t.message}${t.where ? `  [${t.where}]` : ''}`);
    for (const n of t.notices || []) console.log(`      notice: ${n}`);
    if (t.status !== 'skip' && t.output) console.log(`      output: ${t.output.replace(/\s+/g, ' ').slice(0, 300)}`);
  }
  if (t.restored && t.restored.error) console.log(`      clean-up: ${t.restored.error}`);
}
const c = r.counts;
console.log(`php tests: ${c.pass} passed, ${c.fail} failed, ${c.error} errors, ${c.skip} skipped in ${r.ms} ms (WordPress ${r.wp}, PHP ${r.php})${r.filter ? `, filter "${r.filter}"` : ''}${r.partial ? ' -- RUN CUT SHORT' : ''}`);
if (!r.tests.length) console.log('NOTE  no tests matched');
process.exit(r.ok ? 0 : 1);
EOF
status=$?

# PHP messages the run wrote to the debug log (tests fail on their own
# notices already; this also shows ones from outside a test).
if [[ -f $DEBUG ]]; then
  new=$(tail -c +"$((before + 1))" "$DEBUG" | grep -E 'PHP (Warning|Notice|Deprecated|Fatal error|Parse error|Recoverable fatal error)|WordPress database error' | head -n 20)
  if [[ -n $new ]]; then echo "NOTE  PHP messages in $DEBUG during the run:"; echo "$new" | sed 's/^/      /'; fi
fi
exit $status
