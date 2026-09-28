#!/usr/bin/env bash
# DEV ONLY. Every dev check and test against a running dev site, in order:
#   1. dev/checks.sh          HTTP checks (pages, anchors, hardening, PHP log since boot)
#   2. dev/tests/run-php.sh   PHP integration tests inside WordPress
#   3. dev/tests/run-e2e.mjs  browser end-to-end tests
#
#   wordpress/dev/tests/run-all.sh <PORT>
#
# checks.sh runs first: it fails on any PHP notice in the debug log since the
# site started, so it must see the log before the tests add to it. Every step
# runs even if an earlier one failed. Exit 0 only when all three pass.
set -uo pipefail
[[ $# -eq 1 && $1 =~ ^[0-9]+$ ]] || { echo "usage: $0 <PORT>" >&2; exit 2; }
PORT=$1
HERE=$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)
DEV=$(cd "$HERE/.." && pwd)

curl -s -o /dev/null -m 10 "http://127.0.0.1:$PORT/" || { echo "run-all.sh: nothing answering on port $PORT (start it with $DEV/start.sh $PORT)" >&2; exit 2; }

names=(); codes=(); secs=()
step() { # step <name> <command...>
  local name=$1; shift
  echo; echo "=== $name"
  local t0=$SECONDS
  "$@"
  local rc=$?
  names+=("$name"); codes+=("$rc"); secs+=("$((SECONDS - t0))")
}

step "checks.sh (HTTP checks)" "$DEV/checks.sh" "$PORT"
step "run-php.sh (PHP integration tests)" "$HERE/run-php.sh" "$PORT"
step "run-e2e.mjs (browser tests)" node "$HERE/run-e2e.mjs" "$PORT"

echo; echo "=== summary"
fail=0
for i in "${!names[@]}"; do
  if [[ ${codes[$i]} == 0 ]]; then r=PASS; else r="FAIL (exit ${codes[$i]})"; fail=1; fi
  printf '%-6s %-40s %4ss\n' "${r%% *}" "${names[$i]}" "${secs[$i]}"
done
if (( fail )); then echo "run-all: FAILED"; else echo "run-all: all passed"; fi
exit $fail
