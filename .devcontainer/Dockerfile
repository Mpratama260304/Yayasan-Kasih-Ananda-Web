# Base image for the dev container.
#
# The image is used unchanged except for one repair. Every Microsoft
# devcontainer image built on Debian ships an apt source for Yarn:
#
#   /etc/apt/sources.list.d/yarn.list -> https://dl.yarnpkg.com/debian stable
#
# Yarn rotated that repository's signing key, and the key baked into the image
# no longer verifies it. `apt-get update` therefore ends with
#
#   E: The repository 'https://dl.yarnpkg.com/debian stable InRelease' is not
#      signed.
#
# and exits 100. Dev container features run `apt-get update` before installing
# anything, so Docker-in-Docker — and with it the whole container build —
# fails before the workspace ever appears. Codespaces then drops the session
# into a bare Alpine recovery container.
#
# Removing the stale source fixes the build. Yarn is not used by this project:
# there is no build step, and npm handles the only dependency (Playwright).

FROM mcr.microsoft.com/devcontainers/php:1-8.3-bookworm

# Removed before any feature installs, so every later `apt-get update` starts
# from sources that still verify. Files under sources.list.d belong to Yarn
# alone and go entirely; the shared sources.list only loses matching lines.
RUN set -eux; \
	grep -rl 'dl\.yarnpkg\.com' /etc/apt/sources.list.d/ 2>/dev/null | xargs -r rm -f; \
	if [ -f /etc/apt/sources.list ]; then \
		sed -i '/dl\.yarnpkg\.com/d' /etc/apt/sources.list; \
	fi; \
	apt-get update; \
	rm -rf /var/lib/apt/lists/*
