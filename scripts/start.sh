#!/usr/bin/env bash
# ---------------------------------------------------------------
# Start the development stack.
#
#   ./scripts/start.sh            core services
#   ./scripts/start.sh --tools    also start phpMyAdmin
# ---------------------------------------------------------------
# shellcheck source=scripts/lib.sh
source "$(dirname "${BASH_SOURCE[0]}")/lib.sh"

load_env
require_docker

PROFILES=()
if [[ "${1:-}" == "--tools" ]]; then
	PROFILES=(--profile tools)
fi

say "Extracting WordPress core if needed"
"$PROJECT_ROOT/scripts/extract-core.sh"

step "Starting containers"
dc "${PROFILES[@]}" up -d --remove-orphans

wait_for_db

# Some Docker-in-Docker hosts drop traffic between containers; repair it
# before anything tries to reach the database.
if ! dc exec -T wordpress sh -c 'php -r "exit(@fsockopen(\"db\", 3306, \$e, \$s, 5) ? 0 : 1);"' >/dev/null 2>&1; then
	warn "the web container cannot reach the database container"
	"$PROJECT_ROOT/scripts/fix-docker-network.sh" || true
fi

URL="$(site_url)"
cat <<EOF

${C_BOLD}Development services${C_RESET}
  WordPress   $URL
  Mailpit     $(tool_url "$MAILPIT_UI_PORT")
$(if [[ ${#PROFILES[@]} -gt 0 ]]; then echo "  phpMyAdmin  $(tool_url "$PMA_PORT")"; fi)

Next: ${C_BOLD}./scripts/bootstrap.sh${C_RESET}
EOF
