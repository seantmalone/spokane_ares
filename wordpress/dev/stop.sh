#!/usr/bin/env bash
# Stop the dev site started by start.sh (PLAN.md §6.10).
#   wordpress/dev/stop.sh <PORT>
set -euo pipefail
[[ $# -eq 1 && $1 =~ ^[0-9]+$ ]] || { echo "usage: $0 <PORT>" >&2; exit 2; }
PORT=$1
pids=$(lsof -ti tcp:"$PORT" -sTCP:LISTEN 2>/dev/null || true)
if [[ -f /tmp/pg-$PORT.pid ]]; then
  pid=$(cat "/tmp/pg-$PORT.pid")
  kill -0 "$pid" 2>/dev/null && pids="$pids $pid"
  rm -f "/tmp/pg-$PORT.pid"
fi
if [[ -z ${pids// /} ]]; then
  echo "stop.sh: nothing listening on port $PORT"
  exit 0
fi
# shellcheck disable=SC2086
kill $pids 2>/dev/null || true
alive() { local p; for p in $pids; do kill -0 "$p" 2>/dev/null && return 0; done; return 1; }
for _ in $(seq 1 20); do
  if ! lsof -ti tcp:"$PORT" -sTCP:LISTEN >/dev/null 2>&1 && ! alive; then echo "stop.sh: stopped port $PORT"; exit 0; fi
  sleep 0.5
done
# shellcheck disable=SC2086
kill -9 $pids 2>/dev/null || true
# shellcheck disable=SC2046
kill -9 $(lsof -ti tcp:"$PORT" -sTCP:LISTEN) 2>/dev/null || true
echo "stop.sh: stopped port $PORT (forced)"
