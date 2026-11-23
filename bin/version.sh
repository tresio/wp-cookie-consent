#!/usr/bin/env bash
#
# Reads the plugin version from the file header and the S3CC_VERSION constant, and checks they agree.
#
# Usage:
#   bin/version.sh header
#   bin/version.sh const
#   bin/version.sh check [expected]

set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
MAIN="$ROOT/s3-cookie-consent.php"

header_version() {
	grep -m1 -E '^[[:space:]]*\*[[:space:]]*Version:' "$MAIN" | sed -E 's/.*Version:[[:space:]]*//' | tr -d '[:space:]'
}

const_version() {
	grep -m1 -E "define\( 'S3CC_VERSION'" "$MAIN" | sed -E "s/.*'S3CC_VERSION',[[:space:]]*'([^']+)'.*/\1/" | tr -d '[:space:]'
}

case "${1:-check}" in
	header) header_version ;;
	const) const_version ;;
	check)
		HEADER="$(header_version)"
		CONST="$(const_version)"
		if [[ -z "$HEADER" || -z "$CONST" ]]; then
			echo "::error::Could not parse the plugin version (header='$HEADER' const='$CONST')" >&2
			exit 1
		fi
		if [[ "$HEADER" != "$CONST" ]]; then
			echo "::error::Version mismatch: header says '$HEADER' but S3CC_VERSION says '$CONST'" >&2
			exit 1
		fi
		EXPECTED="${2:-}"
		if [[ -n "$EXPECTED" && "$HEADER" != "$EXPECTED" ]]; then
			echo "::error::Version mismatch: plugin says '$HEADER' but the tag says '$EXPECTED'" >&2
			exit 1
		fi
		echo "$HEADER"
		;;
	*)
		echo "usage: $0 {header|const|check [expected]}" >&2
		exit 64
		;;
esac
