# Shared helpers for wordpress/ops/*.sh (sourced, not run). PLAN.md §5.7-§5.9, §6.11.
# Bash 3.2 compatible (macOS /bin/bash).

SPK_OPS_DIR=$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)
SPK_ROOT=$(cd "$SPK_OPS_DIR/../.." && pwd)

spk_fails=0
spk_passes=0
spk_pass() { echo "PASS  $*"; spk_passes=$((spk_passes + 1)); }
spk_fail() { echo "FAIL  $*"; spk_fails=$((spk_fails + 1)); }
spk_skip() { echo "SKIP  $*"; }
spk_die()  { echo "$(basename "$0"): $*" >&2; exit 1; }

# Load wordpress/ops/ops.env (host aliases, URLs, paths). See ops.env.example.
spk_load_env() {
  local env=${SPOKARES_OPS_ENV:-$SPK_OPS_DIR/ops.env}
  [[ -f $env ]] || spk_die "missing $env (copy ops.env.example and fill it in)"
  # shellcheck disable=SC1090
  source "$env"
  SPOKARES_WP_PATH=${SPOKARES_WP_PATH:-public_html}
}

# Pick the target: sets SPK_SSH (ssh host alias) and SPK_URL.
spk_target() {
  case $1 in
    staging) SPK_SSH=${SPOKARES_STAGING_SSH:-}; SPK_URL=${SPOKARES_STAGING_URL:-} ;;
    production|live) SPK_SSH=${SPOKARES_LIVE_SSH:-}; SPK_URL=${SPOKARES_LIVE_URL:-} ;;
    *) spk_die "target must be staging or production, not '$1'" ;;
  esac
  [[ -n $SPK_SSH && -n $SPK_URL ]] || spk_die "ops.env has no SSH alias or URL for $1"
  SPK_URL=${SPK_URL%/}
}

# The version a tag names: v0.1.0, spokares-v0.1.0 and 0.1.0 all give 0.1.0.
spk_tag_version() { echo "$1" | sed -E 's/^.*[^0-9.]([0-9]+\.[0-9]+\.[0-9]+.*)$/\1/; s/^v//'; }

# Is $1 a git tag? Releases are tags; a manual CI deploy (workflow_dispatch)
# may name a branch or a commit instead.
spk_is_tag() { git -C "$SPK_ROOT" rev-parse -q --verify "refs/tags/$1" >/dev/null; }

# The name deploy.sh records in ~/.spokares-deployed-tag and check-live.sh
# expects there: the tag itself, or `git describe` of any other ref
# (v0.1.0-3-g1a2b3c4d5e6f, or the bare commit before the first tag).
spk_ref_label() {
  if spk_is_tag "$1"; then echo "$1"; return; fi
  git -C "$SPK_ROOT" describe --tags --always --abbrev=12 "$1^{commit}" 2>/dev/null || spk_die "no git tag, branch or commit '$1'"
}

# Export a ref's shipped files (and ops/) into a directory: a clean checkout
# of a tag (or, for a manual deploy, a branch or commit), whatever state the
# working tree is in.
spk_export_tag() {
  local tag=$1 dest=$2
  git -C "$SPK_ROOT" rev-parse -q --verify "$tag^{commit}" >/dev/null || spk_die "no git tag, branch or commit '$tag'"
  mkdir -p "$dest"
  git -C "$SPK_ROOT" archive --format=tar "$tag" \
    wordpress/theme/spokares wordpress/plugins/spokares-core wordpress/mu-plugins wordpress/ops \
    | tar -x -C "$dest" || spk_die "git archive of $tag failed"
}

# Print every version the release must agree on, one "label<TAB>value" per line.
spk_versions() {
  local r=$1
  printf 'theme style.css Version\t%s\n' "$(sed -nE 's/^[[:space:]]*Version:[[:space:]]*([^[:space:]]+).*/\1/p' "$r/wordpress/theme/spokares/style.css" | head -1)"
  printf 'SPOKARES_THEME_VERSION\t%s\n' "$(sed -nE "s/.*define\( *'SPOKARES_THEME_VERSION', *'([^']+)'.*/\1/p" "$r/wordpress/theme/spokares/functions.php" | head -1)"
  printf 'theme readme Stable tag\t%s\n' "$(sed -nE 's/^Stable tag:[[:space:]]*([^[:space:]]+).*/\1/p' "$r/wordpress/theme/spokares/readme.txt" | head -1)"
  printf 'plugin header Version\t%s\n' "$(sed -nE 's/^[[:space:]*]*Version:[[:space:]]*([^[:space:]]+).*/\1/p' "$r/wordpress/plugins/spokares-core/spokares-core.php" | head -1)"
  printf 'SPOKARES_CORE_VERSION\t%s\n' "$(sed -nE "s/.*define\( *'SPOKARES_CORE_VERSION', *'([^']+)'.*/\1/p" "$r/wordpress/plugins/spokares-core/spokares-core.php" | head -1)"
  printf 'plugin readme Stable tag\t%s\n' "$(sed -nE 's/^Stable tag:[[:space:]]*([^[:space:]]+).*/\1/p' "$r/wordpress/plugins/spokares-core/readme.txt" | head -1)"
  printf 'must-use header Version\t%s\n' "$(sed -nE 's/^[[:space:]*]*Version:[[:space:]]*([^[:space:]]+).*/\1/p' "$r/wordpress/mu-plugins/spokares-hardening.php" | head -1)"
  printf 'SPOKARES_HARDENING_VERSION\t%s\n' "$(sed -nE "s/.*define\( *'SPOKARES_HARDENING_VERSION', *'([^']+)'.*/\1/p" "$r/wordpress/mu-plugins/spokares-hardening.php" | head -1)"
}

# Check every version equals $2 (or each other, when $2 is empty). Uses spk_pass/spk_fail.
spk_check_versions() {
  local r=$1 want=$2 label value bad=0
  [[ -n $want ]] || want=$(spk_versions "$r" | awk -F'\t' 'NR == 1 { print $2 }')
  while IFS=$'\t' read -r label value; do
    if [[ $value != "$want" ]]; then
      spk_fail "version: $label is '${value:-missing}', expected $want"
      bad=1
    fi
  done < <(spk_versions "$r")
  (( bad )) || spk_pass "versions: all eight version strings are $want"
}
