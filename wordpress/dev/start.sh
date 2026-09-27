#!/usr/bin/env bash
# Start the spokares.org dev site in WordPress Playground (PLAN.md §6.10).
#
#   wordpress/dev/start.sh <PORT> [--wp=<version>] [--php=<version>]
#                          [--blueprint=<file>] [--mount=<host>:<vfs>]...
#
# --blueprint replaces dev/blueprint.json (e.g. a test blueprint that adds
# steps); --mount adds a mount (repeatable). Both are for tests.
#
# Mounts the theme, spokares-core, the must-use plugin and wordpress/dev into a
# fresh in-memory site, runs dev/blueprint.json (theme and plugin steps are
# dropped when their files are missing), waits for HTTP 200/302 and prints the
# URL and the two dev sign-in links. The database is rebuilt on every start.
#
# Logs:  /tmp/pg-<PORT>.log         Playground's own output
#        /tmp/pg-<PORT>-logs/        setup.log (blueprint steps), debug.log (PHP notices)
# Stop:  wordpress/dev/stop.sh <PORT>
set -euo pipefail

usage() { echo "usage: $0 <PORT> [--wp=<version>] [--php=<version>] [--blueprint=<file>] [--mount=<host>:<vfs>]..." >&2; exit 2; }
[[ $# -ge 1 && $1 =~ ^[0-9]+$ ]] || usage
PORT=$1; shift
WP_OVERRIDE=""; PHP_OVERRIDE=""; SOURCE_BLUEPRINT=""; EXTRA_MOUNTS=()
for arg in "$@"; do
  case $arg in
    --wp=*) WP_OVERRIDE=${arg#--wp=} ;;
    --php=*) PHP_OVERRIDE=${arg#--php=} ;;
    --blueprint=*) SOURCE_BLUEPRINT=${arg#--blueprint=} ;;
    --mount=*) EXTRA_MOUNTS+=("$arg") ;;
    *) usage ;;
  esac
done

HERE=$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)
ROOT=$(cd "$HERE/../.." && pwd)
THEME=$ROOT/wordpress/theme/spokares
PLUGIN=$ROOT/wordpress/plugins/spokares-core
MU=$ROOT/wordpress/mu-plugins
LOG=/tmp/pg-$PORT.log
LOGDIR=/tmp/pg-$PORT-logs
TMP=${TMPDIR:-/tmp}
TMP=${TMP%/}
BLUEPRINT=$TMP/spokares-blueprint-$PORT.json

if lsof -nP -iTCP:"$PORT" -sTCP:LISTEN >/dev/null 2>&1; then
  echo "start.sh: port $PORT is already in use. Stop it first: $HERE/stop.sh $PORT" >&2
  exit 1
fi

mkdir -p "$THEME" "$PLUGIN" "$MU"   # a missing folder would break the mount
rm -rf "$LOGDIR"; mkdir -p "$LOGDIR"
: > "$LOGDIR/debug.log"

# Filter the blueprint: drop activateTheme/activatePlugin when the code is
# missing, apply --wp/--php, and print the versions to pass to the CLI.
read -r PHP_VER WP_VER < <(
  THEME_OK=$([[ -f $THEME/style.css ]] && echo 1 || echo 0) \
  PLUGIN_OK=$([[ -f $PLUGIN/spokares-core.php ]] && echo 1 || echo 0) \
  WP_OVERRIDE="$WP_OVERRIDE" PHP_OVERRIDE="$PHP_OVERRIDE" \
  node -e '
    const fs = require("fs");
    const [src, out] = process.argv.slice(1);
    const bp = JSON.parse(fs.readFileSync(src, "utf8"));
    bp.steps = bp.steps.filter((s) => {
      if (s.step === "activateTheme" && process.env.THEME_OK !== "1") { console.error("start.sh: theme/spokares/style.css missing: skipping activateTheme"); return false; }
      if (s.step === "activatePlugin" && process.env.PLUGIN_OK !== "1") { console.error("start.sh: plugins/spokares-core/spokares-core.php missing: skipping activatePlugin"); return false; }
      return true;
    });
    bp.preferredVersions = bp.preferredVersions || {};
    if (process.env.WP_OVERRIDE) bp.preferredVersions.wp = process.env.WP_OVERRIDE;
    if (process.env.PHP_OVERRIDE) bp.preferredVersions.php = process.env.PHP_OVERRIDE;
    fs.writeFileSync(out, JSON.stringify(bp, null, 1));
    console.log(bp.preferredVersions.php || "8.4", bp.preferredVersions.wp || "latest");
  ' "${SOURCE_BLUEPRINT:-$HERE/blueprint.json}" "$BLUEPRINT"
)

echo "start.sh: WordPress $WP_VER, PHP $PHP_VER, port $PORT (log $LOG)"
# --workers=1: with several workers a write in one (a sign-in, a deleted file)
# is not seen by the others at once (plugin build notes).
nohup npx -y @wp-playground/cli@3.1.55 server \
  --port="$PORT" \
  --php="$PHP_VER" \
  --wp="$WP_VER" \
  --workers="${SPOKARES_PG_WORKERS:-1}" \
  --mount="$THEME:/wordpress/wp-content/themes/spokares" \
  --mount="$PLUGIN:/wordpress/wp-content/plugins/spokares-core" \
  --mount="$MU:/spokares-mu" \
  --mount="$HERE:/spokares-dev" \
  --mount="$LOGDIR:/spokares-log" \
  ${EXTRA_MOUNTS[@]+"${EXTRA_MOUNTS[@]}"} \
  --blueprint="$BLUEPRINT" > "$LOG" 2>&1 &
PID=$!
echo "$PID" > "/tmp/pg-$PORT.pid"
disown "$PID" 2>/dev/null || true

URL=http://127.0.0.1:$PORT
deadline=$((SECONDS + ${SPOKARES_PG_TIMEOUT:-180}))
code=000
while (( SECONDS < deadline )); do
  if ! kill -0 "$PID" 2>/dev/null; then
    echo "start.sh: Playground exited during boot. Last lines of $LOG:" >&2
    tail -n 30 "$LOG" >&2
    exit 1
  fi
  # The CLI answers HTTP while the blueprint is still running, so also wait
  # for its "Ready!" line (printed once the last blueprint step is done).
  if grep -q '^Ready!' "$LOG" 2>/dev/null; then
    code=$(curl -s -o /dev/null -m 10 -w '%{http_code}' "$URL/" || true)
    if [[ $code == 200 || $code == 302 ]]; then break; fi
  fi
  sleep 2
done
if [[ $code != 200 && $code != 302 ]]; then
  echo "start.sh: no 200/302 from $URL/ after ${SPOKARES_PG_TIMEOUT:-180}s (last: $code). See $LOG" >&2
  exit 1
fi

echo "start.sh: ready in ${SECONDS}s: $URL/"
echo "  admin:  $URL/wp-admin/?dev_login=1"
echo "  editor: $URL/wp-admin/?dev_login=editor"
echo "  (or sign in at $URL/wp-login.php as admin/password or editor/password)"
if [[ -s $LOGDIR/setup.log ]]; then
  echo "--- setup log ($LOGDIR/setup.log)"
  cat "$LOGDIR/setup.log"
fi
if grep -qE 'FAILED|SKIP' "$LOGDIR/setup.log" 2>/dev/null; then
  echo "start.sh: WARNING: some setup steps failed or were skipped (see above)." >&2
fi
if [[ -s $LOGDIR/debug.log ]]; then
  echo "start.sh: WARNING: PHP notices during boot, see $LOGDIR/debug.log" >&2
fi
