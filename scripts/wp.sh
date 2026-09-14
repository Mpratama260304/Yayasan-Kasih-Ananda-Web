#!/usr/bin/env bash
# ---------------------------------------------------------------
# Thin WP-CLI wrapper so you never have to remember the container name.
#
#   ./scripts/wp.sh plugin list
#   ./scripts/wp.sh post list --post_type=post
# ---------------------------------------------------------------
# shellcheck source=scripts/lib.sh
source "$(dirname "${BASH_SOURCE[0]}")/lib.sh"

load_env
require_docker
wp "$@"
