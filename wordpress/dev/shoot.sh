#!/usr/bin/env bash
# Alias of shots.sh (the name used in the build brief).
exec "$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/shots.sh" "$@"
