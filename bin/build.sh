#!/usr/bin/env bash
#
# Builds dist/s3-cookie-consent.zip with a single s3-cookie-consent/ folder, as WordPress expects.
#
# Usage: bin/build.sh [version]

set -euo pipefail

SLUG="s3-cookie-consent"
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
VERSION="${1:-$("$ROOT/bin/version.sh" header)}"

cd "$ROOT"
rm -rf build dist
mkdir -p "build/$SLUG" dist
rsync -a --exclude-from="$ROOT/.distignore" ./ "build/$SLUG/"
( cd build && zip -rq "../dist/$SLUG.zip" "$SLUG" )
rm -rf build

echo "dist/$SLUG.zip ($VERSION)"
