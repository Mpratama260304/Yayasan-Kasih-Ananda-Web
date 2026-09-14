#!/usr/bin/env bash
# ---------------------------------------------------------------
# Extract the supplied WordPress core archive into ./wp
#
# ./wp is git-ignored: WordPress core is never committed. The archive
# shipped with the repository is the single source of truth for which
# core version this project runs on.
# ---------------------------------------------------------------
# shellcheck source=scripts/lib.sh
source "$(dirname "${BASH_SOURCE[0]}")/lib.sh"

load_env

WP_VERSION="${WP_VERSION:-7.1}"

if [[ -f "$PROJECT_ROOT/wp/wp-includes/version.php" ]]; then
	VER="$(grep -oP "(?<=\\\$wp_version = ')[^']+" "$PROJECT_ROOT/wp/wp-includes/version.php" || echo '?')"
	ok "WordPress core already present in ./wp (version $VER)"
	exit 0
fi

ARCHIVE="$(ls -1 "$PROJECT_ROOT"/wordpress-*.zip 2>/dev/null | sort -V | tail -1 || true)"

# The archive is git-ignored, so a fresh clone will not have it. Fetch the
# pinned version rather than failing: the project must be reconstructable
# from the repository alone.
if [[ -z "$ARCHIVE" ]]; then
	ARCHIVE="$PROJECT_ROOT/wordpress-${WP_VERSION}.zip"
	say "No local WordPress archive found — downloading version ${WP_VERSION}"
	if ! curl -fSL --retry 3 -o "$ARCHIVE" "https://wordpress.org/wordpress-${WP_VERSION}.zip"; then
		rm -f "$ARCHIVE"
		die "could not download WordPress ${WP_VERSION} — place wordpress-${WP_VERSION}.zip in the repository root manually"
	fi
	ok "downloaded $(basename "$ARCHIVE")"
fi

say "Extracting $(basename "$ARCHIVE") into ./wp"
rm -rf "$PROJECT_ROOT/.wp-extract"
mkdir -p "$PROJECT_ROOT/.wp-extract"
unzip -q "$ARCHIVE" -d "$PROJECT_ROOT/.wp-extract"

if [[ -d "$PROJECT_ROOT/.wp-extract/wordpress" ]]; then
	mv "$PROJECT_ROOT/.wp-extract/wordpress" "$PROJECT_ROOT/wp"
else
	mv "$PROJECT_ROOT/.wp-extract" "$PROJECT_ROOT/wp"
fi
rm -rf "$PROJECT_ROOT/.wp-extract"

# Directories WordPress needs to be able to write to.
mkdir -p "$PROJECT_ROOT/wp/wp-content/uploads" \
         "$PROJECT_ROOT/wp/wp-content/upgrade" \
         "$PROJECT_ROOT/wp/wp-content/languages"

# Bind-mount targets must exist before Docker mounts over them.
mkdir -p "$PROJECT_ROOT/wp/wp-content/themes/yka-portal" \
         "$PROJECT_ROOT/wp/wp-content/plugins/yka-core"

chmod -R a+rwX "$PROJECT_ROOT/wp/wp-content" 2>/dev/null || true

VER="$(grep -oP "(?<=\\\$wp_version = ')[^']+" "$PROJECT_ROOT/wp/wp-includes/version.php" || echo '?')"
ok "WordPress $VER extracted to ./wp"
