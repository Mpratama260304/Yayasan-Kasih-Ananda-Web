#!/usr/bin/env bash
# ---------------------------------------------------------------
# Bootstrap the WordPress development site.
#
# Idempotent: safe to re-run. It never deletes content.
#
#   ./scripts/bootstrap.sh
#   ./scripts/bootstrap.sh --skip-plugins   (offline / flaky network)
# ---------------------------------------------------------------
# shellcheck source=scripts/lib.sh
source "$(dirname "${BASH_SOURCE[0]}")/lib.sh"

load_env
require_docker

SKIP_PLUGINS=0
[[ "${1:-}" == "--skip-plugins" ]] && SKIP_PLUGINS=1

URL="$(site_url)"

"$PROJECT_ROOT/scripts/extract-core.sh"

say "Ensuring containers are running"
dc up -d --remove-orphans >/dev/null
wait_for_db

# -------------------------------------------------------------------
step "1/8  WordPress installation"
# -------------------------------------------------------------------
if wp_installed; then
	ok "WordPress is already installed"

	# Gutenberg stores absolute URLs inside block markup, so changing the
	# site address without rewriting content leaves every inline image
	# broken. This matters in Codespaces, where the forwarded hostname
	# changes with the codespace.
	CURRENT_URL="$(wp option get home 2>/dev/null || echo '')"
	if [[ -n "$CURRENT_URL" && "$CURRENT_URL" != "$URL" ]]; then
		say "Site address changed: $CURRENT_URL → $URL"
		wp search-replace "$CURRENT_URL" "$URL" \
			--all-tables-with-prefix --skip-columns=guid --precise --quiet \
			>/dev/null 2>&1 || warn "URL rewrite reported a problem"
		ok "content URLs rewritten"
	fi

	wp_option home "$URL"
	wp_option siteurl "$URL"
	ok "site URL refreshed: $URL"
else
	: "${WP_ADMIN_USER:?WP_ADMIN_USER must be set in .env}"
	: "${WP_ADMIN_PASSWORD:?WP_ADMIN_PASSWORD must be set in .env}"
	: "${WP_ADMIN_EMAIL:?WP_ADMIN_EMAIL must be set in .env}"

	wp core install \
		--url="$URL" \
		--title="${WP_TITLE:-Yayasan Kasih Ananda}" \
		--admin_user="$WP_ADMIN_USER" \
		--admin_password="$WP_ADMIN_PASSWORD" \
		--admin_email="$WP_ADMIN_EMAIL" \
		--skip-email
	ok "WordPress installed for $URL"
fi

# -------------------------------------------------------------------
step "2/8  Language, timezone and locale"
# -------------------------------------------------------------------
wp language core install "${WP_LOCALE:-id_ID}" >/dev/null 2>&1 || warn "could not download the ${WP_LOCALE:-id_ID} language pack (offline?)"
wp site switch-language "${WP_LOCALE:-id_ID}" >/dev/null 2>&1 || wp_option WPLANG "${WP_LOCALE:-id_ID}"
wp_option timezone_string "${WP_TIMEZONE:-Asia/Jakarta}"
wp_option gmt_offset ''
wp_option date_format 'j F Y'
wp_option time_format 'H:i'
wp_option start_of_week 1
ok "locale ${WP_LOCALE:-id_ID}, timezone ${WP_TIMEZONE:-Asia/Jakarta}"

# -------------------------------------------------------------------
step "3/8  Permalinks and discussion settings"
# -------------------------------------------------------------------
wp rewrite structure '/berita/%postname%/' --hard >/dev/null 2>&1 || true
wp_option category_base 'topik'
wp_option tag_base 'tag'
ok "article permalinks: /berita/%postname%/"

# WP-CLI cannot detect mod_rewrite from outside Apache, so it refuses to
# write .htaccess. The rules are static, so write them here instead.
HTACCESS="$PROJECT_ROOT/wp/.htaccess"
if ! grep -q 'BEGIN WordPress' "$HTACCESS" 2>/dev/null; then
	cat > "$HTACCESS" <<-'RULES'
	# BEGIN WordPress
	<IfModule mod_rewrite.c>
	RewriteEngine On
	RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
	RewriteBase /
	RewriteRule ^index\.php$ - [L]
	RewriteCond %{REQUEST_FILENAME} !-f
	RewriteCond %{REQUEST_FILENAME} !-d
	RewriteRule . /index.php [L]
	</IfModule>
	# END WordPress
	RULES
	ok ".htaccess written"
else
	ok ".htaccess already present"
fi

# Institutional site: no public comment moderation burden.
wp_option default_comment_status 'closed'
wp_option default_ping_status 'closed'
wp_option default_pingback_flag 0
wp_option comment_registration 1
wp_option posts_per_page 12
wp_option posts_per_rss 20
wp_option rss_use_excerpt 1
wp_option blogdescription 'Portal berita dan dokumentasi Yayasan Kasih Ananda'
wp_option image_default_size 'large'
wp_option image_default_link_type 'none'
ok "comments closed, feeds preserved"

# -------------------------------------------------------------------
step "4/8  Search engine visibility guard"
# -------------------------------------------------------------------
# Development and staging must never be indexable. YKA Core enforces
# this at runtime too; this only keeps the stored option consistent.
if [[ "${WP_ENVIRONMENT_TYPE:-local}" == "production" ]]; then
	wp_option blog_public 1
	ok "environment=production → indexing allowed"
else
	wp_option blog_public 0
	ok "environment=${WP_ENVIRONMENT_TYPE:-local} → indexing discouraged"
fi

# -------------------------------------------------------------------
step "5/8  YKA theme and core plugin"
# -------------------------------------------------------------------
wp theme activate yka-portal >/dev/null 2>&1 \
	&& ok "theme 'YKA Portal' active" \
	|| warn "could not activate yka-portal (is wp-content/themes/yka-portal present?)"

wp plugin activate yka-core >/dev/null 2>&1 \
	&& ok "plugin 'YKA Core' active" \
	|| warn "could not activate yka-core"

# Bundled defaults we do not want on an institutional site.
wp plugin deactivate hello akismet >/dev/null 2>&1 || true
wp theme delete twentytwentythree twentytwentyfour >/dev/null 2>&1 || true

# -------------------------------------------------------------------
step "6/8  Supporting plugins"
# -------------------------------------------------------------------
if [[ "$SKIP_PLUGINS" -eq 1 ]]; then
	warn "skipping plugin downloads (--skip-plugins)"
else
	install_plugin() {
		local slug="$1" label="$2"
		if wp plugin is-installed "$slug" >/dev/null 2>&1; then
			wp plugin activate "$slug" >/dev/null 2>&1 || true
			ok "$label already installed"
		elif wp plugin install "$slug" --activate >/dev/null 2>&1; then
			ok "$label installed and activated"
		else
			warn "$label could not be installed — the site still works without it. See docs/DEVELOPMENT.md"
		fi
	}
	install_plugin seo-by-rank-math "Rank Math SEO"
	install_plugin wpvivid-backuprestore "WPvivid Backup & Migration"

	for extra in ${WP_EXTRA_PLUGINS:-}; do
		install_plugin "$extra" "$extra"
	done
fi

# Rank Math stays silent on the frontend until its wizard has run, which
# would leave the site without meta descriptions, Open Graph tags or schema.
if wp plugin is-active seo-by-rank-math >/dev/null 2>&1; then
	wp eval-file /scripts/php/rank-math-config.php || warn "Rank Math configuration step reported a problem"
fi

# -------------------------------------------------------------------
step "7/8  Site structure (pages and menus)"
# -------------------------------------------------------------------
wp eval-file /scripts/php/site-structure.php || warn "site structure step reported a problem"

# Articles should never be bylined with a raw login name.
if [[ "$(wp user get "$WP_ADMIN_USER" --field=display_name 2>/dev/null || true)" == "$WP_ADMIN_USER" ]]; then
	wp user update "$WP_ADMIN_USER" --display_name='Redaksi Yayasan Kasih Ananda' >/dev/null 2>&1 || true
	ok "author display name set (create individual staff accounts before launch)"
fi

# -------------------------------------------------------------------
step "8/8  Flushing rewrite rules"
# -------------------------------------------------------------------
wp rewrite flush --hard >/dev/null
wp cache flush >/dev/null 2>&1 || true
ok "rewrite rules flushed"

wait_for_http "$URL" "WordPress" || true

cat <<EOF

${C_BOLD}${C_GREEN}Bootstrap complete.${C_RESET}

  Site        $URL
  Admin       $URL/wp-admin/
  Mailpit     $(tool_url "$MAILPIT_UI_PORT")

  Admin user  ${WP_ADMIN_USER}
  Password    (the value of WP_ADMIN_PASSWORD in your local .env)

Add demo articles for development:  ${C_BOLD}./scripts/seed.sh${C_RESET}
Run project checks:                 ${C_BOLD}./scripts/check.sh${C_RESET}
EOF
