#!/usr/bin/env bash
# Screenshots of the dev site (PLAN.md §6.10). Wrapper for shots.mjs.
#   wordpress/dev/shots.sh <PORT> [outdir] [--only=front|admin]
# Default outdir: wordpress/dev/shots/. Needs Node 22+ and Google Chrome
# (set CHROME=/path/to/chrome if it isn't in /Applications).
set -euo pipefail
[[ $# -ge 1 && $1 =~ ^[0-9]+$ ]] || { echo "usage: $0 <PORT> [outdir] [--only=front|admin]" >&2; exit 2; }
HERE=$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)
exec node "$HERE/shots.mjs" "$@"
