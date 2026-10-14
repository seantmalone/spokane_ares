#!/usr/bin/env bash
# Set the release version in all eight places ops/ci.sh checks (lib.sh
# spk_versions): the theme's style.css, SPOKARES_THEME_VERSION and readme.txt
# Stable tag; the plugin's header, SPOKARES_CORE_VERSION and readme.txt
# Stable tag; the must-use plugin's header and SPOKARES_HARDENING_VERSION.
#
#   wordpress/ops/bump-version.sh 0.3.0
#
# Then check: the script re-reads all eight and fails unless each equals the
# new version. It changes files only; commit and tag yourself.
set -euo pipefail
# shellcheck source=lib.sh
source "$(dirname "$0")/lib.sh"

[[ $# -eq 1 && $1 =~ ^[0-9]+\.[0-9]+\.[0-9]+([.-][0-9A-Za-z.]+)?$ ]] || spk_die "usage: $0 <version, e.g. 0.3.0>"
NEW=$1
R=$SPK_ROOT/wordpress

# sed -i differs on macOS and GNU; write through a temp file instead.
edit() {
  local file=$1 expr=$2 tmp
  tmp=$(mktemp "${TMPDIR:-/tmp}/bump.XXXXXX")
  sed -E "$expr" "$file" > "$tmp" && cat "$tmp" > "$file"
  rm -f "$tmp"
}

edit "$R/theme/spokares/style.css" "s/^([[:space:]]*Version:[[:space:]]*)[^[:space:]]+/\1$NEW/"
edit "$R/theme/spokares/functions.php" "s/(define\( *'SPOKARES_THEME_VERSION', *')[^']+'/\1$NEW'/"
edit "$R/theme/spokares/readme.txt" "s/^(Stable tag:[[:space:]]*)[^[:space:]]+/\1$NEW/"
edit "$R/plugins/spokares-core/spokares-core.php" "s/^([[:space:]*]*Version:[[:space:]]*)[^[:space:]]+/\1$NEW/; s/(define\( *'SPOKARES_CORE_VERSION', *')[^']+'/\1$NEW'/"
edit "$R/plugins/spokares-core/readme.txt" "s/^(Stable tag:[[:space:]]*)[^[:space:]]+/\1$NEW/"
edit "$R/mu-plugins/spokares-hardening.php" "s/^([[:space:]*]*Version:[[:space:]]*)[^[:space:]]+/\1$NEW/; s/(define\( *'SPOKARES_HARDENING_VERSION', *')[^']+'/\1$NEW'/"

spk_check_versions "$SPK_ROOT" "$NEW"
(( spk_fails == 0 )) || exit 1
echo "Now add a Changelog entry to plugins/spokares-core/readme.txt (and the theme's if it changed), commit, and tag v$NEW."
