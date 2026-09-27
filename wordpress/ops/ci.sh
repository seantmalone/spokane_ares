#!/usr/bin/env bash
# Release checks and release zips (PLAN.md §5.7, §6.11). Run on the admin's
# machine or in CI before tagging and again on the tag.
#
#   wordpress/ops/ci.sh [--tag=<tag>] [--offline] [--no-zip]
#
# --tag      check a clean export of that git tag (versions must equal the tag)
#            instead of the working tree, and name the zips after it
# --offline  skip the steps that need WordPress Playground to download things
#            (PHPCS through Plugin Check's copy, and Plugin Check)
# --no-zip   don't build the release zips
#
# Checks:
#   1. version agreement (8 strings; equal to the tag when --tag is given)
#   2. forbidden code in shipped PHP: nopriv, register_rest_route, $_REQUEST,
#      unserialize, eval(, extract(
#   3. every shipped PHP file starts with the ABSPATH guard
#   4. generated files are fresh (dev/seed/data.json, the redirect map)
#   5. JSON files parse; JavaScript files parse
#   6. PHP lint on 8.3 and 8.4 (local php if present, else Playground's php)
#   7. PHPCS WordPress-Extra via ops/phpcs.xml (+ PHPCompatibilityWP 8.3- when a
#      local phpcs has it; otherwise Plugin Check's bundled phpcs in Playground)
#   8. Plugin Check on spokares-core in Playground ("plugin_updater_detected" is
#      expected: Update URI: false is deliberate, §5.7)
# Then builds wordpress/dist/spokares-<v>.zip, spokares-core-<v>.zip,
# spokares-hardening-<v>.zip and SHA256SUMS. Exit 1 if any check failed.
set -uo pipefail
source "$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/lib.sh"

TAG=""; OFFLINE=0; ZIP=1
for arg in "$@"; do
  case $arg in
    --tag=*) TAG=${arg#--tag=} ;;
    --offline) OFFLINE=1 ;;
    --no-zip) ZIP=0 ;;
    *) echo "usage: $0 [--tag=<tag>] [--offline] [--no-zip]" >&2; exit 2 ;;
  esac
done

# No work folder, no checks: without it the PHPCS report is never written and
# an empty result could read as a pass.
WORK=$(mktemp -d "${TMPDIR:-/tmp}/spokares-ci.XXXXXX") && [[ -n $WORK && -d $WORK ]] || { echo "ci.sh: cannot create a work folder (mktemp failed; check TMPDIR)" >&2; exit 1; }
trap 'rm -rf "$WORK"' EXIT
PG="npx -y @wp-playground/cli@3.1.55"
CACHE=${SPOKARES_CACHE:-${TMPDIR:-/tmp}/spokares-cache}
mkdir -p "$CACHE"

if [[ -n $TAG ]]; then
  git -C "$SPK_ROOT" rev-parse -q --verify "refs/tags/$TAG" >/dev/null || spk_die "no git tag '$TAG'"
  SRC=$WORK/src
  mkdir -p "$SRC"
  git -C "$SPK_ROOT" archive --format=tar "$TAG" | tar -x -C "$SRC" || spk_die "git archive $TAG failed"
  WANT=$(spk_tag_version "$TAG")
  echo "ci.sh: checking tag $TAG (version $WANT), clean export in $SRC"
else
  SRC=$SPK_ROOT
  WANT=""
  echo "ci.sh: checking the working tree $SRC"
fi
W=$SRC/wordpress
SHIPPED=("$W/theme/spokares" "$W/plugins/spokares-core" "$W/mu-plugins")
for d in "${SHIPPED[@]}"; do [[ -d $d ]] || spk_die "missing $d"; done

# 1. Versions.
spk_check_versions "$SRC" "$WANT"
VERSION=${WANT:-$(spk_versions "$SRC" | awk -F'\t' 'NR == 1 { print $2 }')}

# 2. Forbidden code (shipped PHP only).
hits=$(grep -rnE --include='*.php' 'nopriv|register_rest_route|\$_REQUEST|unserialize|(^|[^A-Za-z0-9_>:$])(eval|extract)[[:space:]]*\(' "${SHIPPED[@]}" || true)
if [[ -z $hits ]]; then spk_pass "forbidden-code grep is clean"; else spk_fail "forbidden code:"; echo "$hits" | sed "s|$SRC/||; s/^/      /"; fi

# 3. ABSPATH guard in every shipped PHP file.
missing=$(find "${SHIPPED[@]}" -name '*.php' -exec grep -L "defined( 'ABSPATH' ) || exit;" {} + || true)
if [[ -z $missing ]]; then spk_pass "every shipped PHP file has the ABSPATH guard"; else spk_fail "no ABSPATH guard:"; echo "$missing" | sed "s|$SRC/||; s/^/      /"; fi

# 4. Generated files.
if [[ ! -f $SRC/design/round3/_shared/assets/data.js ]]; then
  spk_skip "data.json freshness (design/round3/_shared/assets/data.js is not in this tree)"
elif [[ -f $W/dev/seed/convert.mjs ]]; then
  node "$W/dev/seed/convert.mjs" --check >/dev/null 2>&1 && spk_pass "dev/seed/data.json matches data.js" || spk_fail "dev/seed/data.json is stale: node wordpress/dev/seed/convert.mjs"
fi
if [[ ! -f $SRC/research/redirects.csv ]]; then
  spk_skip "redirect-map freshness (research/redirects.csv is not in this tree)"
elif [[ -f $W/tools/redirects/gen-redirect-map.mjs ]]; then
  node "$W/tools/redirects/gen-redirect-map.mjs" --check >/dev/null 2>&1 && spk_pass "redirect-map.php matches the CSVs" || spk_fail "redirect-map.php is stale: node wordpress/tools/redirects/gen-redirect-map.mjs"
fi

# 5. JSON and JavaScript parse.
bad=""
while IFS= read -r f; do
  node -e 'JSON.parse(require("fs").readFileSync(process.argv[1], "utf8"))' "$f" 2>/dev/null || bad="$bad ${f#$SRC/}"
done < <(find "${SHIPPED[@]}" "$W/dev" -name '*.json' -not -path '*/node_modules/*' 2>/dev/null)
[[ -z $bad ]] && spk_pass "every JSON file parses" || spk_fail "JSON does not parse:$bad"
bad=""
while IFS= read -r f; do
  if grep -qE '^[[:space:]]*(import|export)[[:space:]]' "$f"; then ext=mjs; else ext=cjs; fi
  cp "$f" "$WORK/check.$ext"
  node --check "$WORK/check.$ext" 2>/dev/null || bad="$bad ${f#$SRC/}"
done < <(find "${SHIPPED[@]}" -name '*.js' -not -path '*/node_modules/*')
[[ -z $bad ]] && spk_pass "every JavaScript file parses" || spk_fail "JavaScript does not parse:$bad"

# 6. PHP lint on 8.3 and 8.4.
PHP_FILES=()
while IFS= read -r f; do PHP_FILES+=("$f"); done < <(find "${SHIPPED[@]}" "$W/dev" -name '*.php' | sort)
for v in 8.3 8.4; do
  if command -v php >/dev/null && [[ $(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;') == "$v" ]]; then
    out=$(for f in "${PHP_FILES[@]}"; do php -l "$f" 2>&1 | grep -v '^No syntax errors'; done)
  else
    # Anything but "No syntax errors" is a failure, except npm's and
    # Playground's own notices (seen on 4-CPU CI runners).
    vfs=(); for f in "${PHP_FILES[@]}"; do vfs+=("/src/${f#$SRC/}"); done
    out=$($PG php --php=$v --wordpress-install-mode=do-not-attempt-installing --skip-sqlite-setup --mount="$SRC:/src" -- -l "${vfs[@]}" 2>&1 | grep -vE '^No syntax errors|^$|^npm warn |default worker count has been reduced')
  fi
  if [[ -z $out ]]; then spk_pass "PHP $v lint: ${#PHP_FILES[@]} files, no syntax errors"; else spk_fail "PHP $v lint:"; echo "$out" | head -20 | sed 's/^/      /'; fi
done

# 7 and 8. PHPCS and Plugin Check.
BP=$WORK/plugin-check.json
cat > "$BP" <<'EOF'
{
 "preferredVersions": { "php": "8.4", "wp": "7.1.2" },
 "steps": [
  { "step": "installPlugin", "pluginData": { "resource": "wordpress.org/plugins", "slug": "plugin-check" }, "options": { "activate": true } },
  { "step": "activatePlugin", "pluginPath": "spokares-core/spokares-core.php" }
 ]
}
EOF
PG_WP=(--php=8.4 --wp=7.1.2 --blueprint="$BP" --mount="$W/plugins/spokares-core:/wordpress/wp-content/plugins/spokares-core" --mount="$SRC:/src" --mount="$WORK:/work")
phpcs_summary() { # phpcs_summary <summary report> <json report>
  # PASS only on proof that PHPCS ran: its JSON report (written even when
  # nothing is found) must exist and parse. A missing or empty report is a
  # FAIL, never "no violations".
  local totals errs warns line
  if [[ ! -s $2 ]] || ! totals=$(node -e 'const t = JSON.parse(require("fs").readFileSync(process.argv[1], "utf8")).totals; if (!t || typeof t.errors !== "number") process.exit(1); console.log(t.errors, t.warnings);' "$2" 2>/dev/null); then
    spk_fail "PHPCS did not run (no report):"
    [[ -s $1 ]] && grep -v Deprecated "$1" | head -10 | sed 's/^/      /'
    return
  fi
  read -r errs warns <<<"$totals"
  line="$errs errors and $warns warnings"
  if [[ $errs == 0 ]]; then spk_pass "PHPCS WordPress-Extra: $line"; else spk_fail "PHPCS WordPress-Extra: $line"; grep -v Deprecated "$1" | grep -E '^/|^[^ ]+\.php' | sed "s|/src/||; s|$SRC/||; s/^/      /" | head -40; fi
}
PHPCS_TARGETS=("${SHIPPED[@]}" "$W/dev")
if command -v phpcs >/dev/null; then
  std="$W/ops/phpcs.xml"
  if grep -q PHPCompatibilityWP <<<"$(phpcs -i)"; then std="$std,PHPCompatibilityWP"; else echo "NOTE  PHPCompatibilityWP not installed for the local phpcs; PHP compatibility is covered by the 8.3/8.4 lint"; fi
  phpcs --standard="$std" --runtime-set testVersion 8.3- --report=summary --report-full="$WORK/phpcs.txt" --report-json="$WORK/phpcs.json" "${PHPCS_TARGETS[@]}" > "$WORK/phpcs-summary.txt" 2>&1
  phpcs_summary "$WORK/phpcs-summary.txt" "$WORK/phpcs.json"
elif (( OFFLINE )); then
  spk_skip "PHPCS (--offline, and no local phpcs)"
else
  echo "NOTE  no local phpcs: using Plugin Check's bundled phpcs in Playground (WordPress-Extra; PHPCompatibilityWP is not bundled, the 8.3/8.4 lint covers syntax)"
  vfs=(); for d in "${PHPCS_TARGETS[@]}"; do vfs+=("/src/${d#$SRC/}"); done
  $PG php "${PG_WP[@]}" -- /wordpress/wp-content/plugins/plugin-check/vendor/squizlabs/php_codesniffer/bin/phpcs \
    -d error_reporting=24575 --standard=/src/wordpress/ops/phpcs.xml --report=summary --report-full=/work/phpcs.txt --report-json=/work/phpcs.json "${vfs[@]}" > "$WORK/phpcs-summary.txt" 2>&1
  phpcs_summary "$WORK/phpcs-summary.txt" "$WORK/phpcs.json"
fi
[[ -f $WORK/phpcs.txt ]] && cp "$WORK/phpcs.txt" "$CACHE/phpcs-report.txt" && echo "      full report: $CACHE/phpcs-report.txt"

if (( OFFLINE )); then
  spk_skip "Plugin Check (--offline)"
else
  [[ -s $CACHE/wp-cli.phar ]] || curl -fsSL -m 120 -o "$CACHE/wp-cli.phar" https://playground.wordpress.net/wp-cli.phar || spk_fail "could not download wp-cli.phar"
  cp "$CACHE/wp-cli.phar" "$WORK/wp-cli.phar" 2>/dev/null
  $PG php "${PG_WP[@]}" -- /work/wp-cli.phar --path=/wordpress plugin check spokares-core --format=csv > "$WORK/plugin-check.txt" 2>&1
  cp "$WORK/plugin-check.txt" "$CACHE/plugin-check-report.txt"
  errors=$(grep ',ERROR,' "$WORK/plugin-check.txt" | grep -v ',plugin_updater_detected,' || true)
  warnings=$(grep -c ',WARNING,' "$WORK/plugin-check.txt" || true)
  if ! grep -qE '^FILE:|Success: Checks complete' "$WORK/plugin-check.txt"; then
    spk_fail "Plugin Check did not run (see $CACHE/plugin-check-report.txt)"
  elif [[ -z $errors ]]; then
    spk_pass "Plugin Check: no errors (apart from the expected plugin_updater_detected); $warnings warnings in $CACHE/plugin-check-report.txt"
  else
    spk_fail "Plugin Check errors ($warnings warnings too; full list in $CACHE/plugin-check-report.txt):"
    echo "$errors" | cut -d, -f1-5 | sed 's/^/      /' | head -20
  fi
fi

# Release zips.
if (( ZIP )); then
  DIST=$SPK_ROOT/wordpress/dist
  mkdir -p "$DIST"
  printf '# Release zips built by ops/ci.sh; attach them to the git tag, never commit them.\n*\n' > "$DIST/.gitignore"
  EXCL=(-x '*.DS_Store' '*/.git*' '*.map' '*/node_modules/*' '*/phpcs.xml*' '*/composer.*' '*/package*.json')
  rm -f "$DIST/spokares-$VERSION.zip" "$DIST/spokares-core-$VERSION.zip" "$DIST/spokares-hardening-$VERSION.zip"
  (cd "$W/theme" && zip -qrX "$DIST/spokares-$VERSION.zip" spokares "${EXCL[@]}") &&
  (cd "$W/plugins" && zip -qrX "$DIST/spokares-core-$VERSION.zip" spokares-core "${EXCL[@]}") &&
  (cd "$W/mu-plugins" && zip -qrX "$DIST/spokares-hardening-$VERSION.zip" spokares-hardening.php spokares-hardening "${EXCL[@]}") &&
  (cd "$DIST" && shasum -a 256 "spokares-$VERSION.zip" "spokares-core-$VERSION.zip" "spokares-hardening-$VERSION.zip" > "SHA256SUMS-$VERSION") &&
  spk_pass "release zips in wordpress/dist/: spokares-$VERSION.zip, spokares-core-$VERSION.zip, spokares-hardening-$VERSION.zip (+ SHA256SUMS-$VERSION)" ||
  spk_fail "building the release zips"
  [[ -z $TAG ]] && echo "NOTE  built from the working tree, not a tag: attach zips to a release only from ci.sh --tag=<tag>"
fi

echo "ci.sh: $spk_passes passed, $spk_fails failed"
exit $(( spk_fails > 0 ))
