#!/usr/bin/env bash
# ---------------------------------------------------------------
# Repair container-to-container networking.
#
# Some Docker-in-Docker hosts — GitHub Codespaces among them — end up with
# firewall rules in BOTH the nftables and the legacy iptables backends.
# Docker 29 writes its rules through iptables-nft, but the legacy table
# still enforces `-P FORWARD DROP` and only whitelists `docker0`. Traffic
# between containers on a compose bridge (br-*) is silently dropped, and
# WordPress reports "Error establishing a database connection".
#
# This script adds the two missing ACCEPT rules for bridge interfaces.
# It only touches the local development machine, needs passwordless sudo,
# and is a no-op when the rules already exist or networking already works.
#
#   ./scripts/fix-docker-network.sh
# ---------------------------------------------------------------
# shellcheck source=scripts/lib.sh
source "$(dirname "${BASH_SOURCE[0]}")/lib.sh"

require_docker

# --- is anything actually broken? ---------------------------------
probe() {
	docker run --rm --network "$1" alpine:latest \
		sh -c 'nc -z -w 3 "$0" 9999' "$2" >/dev/null 2>&1
}

NET="yka-netcheck-$$"
docker network create "$NET" >/dev/null 2>&1 || die "could not create a test network"

cleanup() {
	docker rm -f "yka-netcheck-srv-$$" >/dev/null 2>&1 || true
	docker network rm "$NET" >/dev/null 2>&1 || true
}
trap cleanup EXIT

docker run -d --rm --name "yka-netcheck-srv-$$" --network "$NET" alpine:latest \
	sh -c 'nc -lk -p 9999 -e echo ok' >/dev/null 2>&1

sleep 2

if probe "$NET" "yka-netcheck-srv-$$"; then
	ok "container-to-container networking already works"
	exit 0
fi

warn "containers cannot reach each other — applying the legacy iptables fix"

if ! sudo -n true 2>/dev/null; then
	cat >&2 <<-'MSG'

	  Passwordless sudo is not available, so this cannot be fixed automatically.
	  Run these two commands yourself, then start the stack again:

	    sudo iptables-legacy -I FORWARD 1 -i br+ -j ACCEPT
	    sudo iptables-legacy -I FORWARD 1 -o br+ -j ACCEPT

	MSG
	exit 1
fi

for DIRECTION in -i -o; do
	if ! sudo -n iptables-legacy -C FORWARD "$DIRECTION" br+ -j ACCEPT 2>/dev/null; then
		sudo -n iptables-legacy -I FORWARD 1 "$DIRECTION" br+ -j ACCEPT
	fi
done

if probe "$NET" "yka-netcheck-srv-$$"; then
	ok "networking repaired"
	exit 0
fi

die "networking is still broken — see docs/DEVELOPMENT.md, section 'Docker networking'"
