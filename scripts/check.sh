#!/usr/bin/env bash
# ---------------------------------------------------------------
# Project quality checks.
#
#   ./scripts/check.sh           everything that is available
#   ./scripts/check.sh --lint    static checks only (no running site)
#   ./scripts/check.sh --http    site checks only
#
# Only custom YKA code is linted. WordPress core and third-party
# plugins are deliberately excluded.
# ---------------------------------------------------------------
# shellcheck source=scripts/lib.sh
source "$(dirname "${BASH_SOURCE[0]}")/lib.sh"

# A check runner must report every finding, not stop at the first one.
# lib.sh enables `set -e` for the task scripts; it is wrong here.
set +e

load_env

MODE="${1:-all}"
FAILED=0
PASSED=0

result() {
	if [[ "$1" -eq 0 ]]; then
		ok "$2"
		PASSED=$(( PASSED + 1 ))
	else
		warn "$2"
		FAILED=$(( FAILED + 1 ))
	fi
}

# Runs a command and records the outcome without tripping `set -e`.
assert() {
	local label="$1"
	shift
	if "$@" >/dev/null 2>&1; then
		result 0 "$label"
	else
		result 1 "$label"
	fi
}

CUSTOM_PATHS=( "wp-content/themes/yka-portal" "wp-content/plugins/yka-core" "scripts/php" "config/wp" )

# -------------------------------------------------------------------
if [[ "$MODE" != "--http" ]]; then
step "PHP syntax (custom code only)"
# -------------------------------------------------------------------
SYNTAX_ERRORS=0
FILE_COUNT=0
while IFS= read -r -d '' file; do
	FILE_COUNT=$(( FILE_COUNT + 1 ))
	if ! OUT="$(php -d error_reporting=E_ALL -l "$file" 2>&1)"; then
		printf '%s\n' "$OUT" >&2
		SYNTAX_ERRORS=$(( SYNTAX_ERRORS + 1 ))
	fi
done < <(find "${CUSTOM_PATHS[@]}" -name '*.php' -type f -print0 2>/dev/null)
result "$SYNTAX_ERRORS" "PHP syntax: $FILE_COUNT file(s) checked, $SYNTAX_ERRORS error(s)"

# -------------------------------------------------------------------
step "Forbidden patterns"
# -------------------------------------------------------------------
# Hard-coded environment URLs break staging → production migration.
HARDCODED="$(grep -rEn 'https?://(yayasankasihananda\.(com|us\.ci)|localhost:[0-9]+|[a-z0-9-]+\.app\.github\.dev)' \
	--include='*.php' --include='*.css' --include='*.js' "${CUSTOM_PATHS[@]}" 2>/dev/null \
	| grep -vE '@link|@see|Plugin URI|Theme URI|Author URI|^\s*\*|//\s' || true)"
if [[ -n "$HARDCODED" ]]; then
	printf '%s\n' "$HARDCODED" >&2
	result 1 "hard-coded environment URLs found in custom code"
else
	result 0 "no hard-coded environment URLs"
fi

# Comment lines are stripped first: a security check that fires on prose
# teaches people to ignore it.
DANGEROUS="$(grep -rEn '\b(eval|create_function|shell_exec|passthru|proc_open|popen)\s*\(' \
	--include='*.php' "${CUSTOM_PATHS[@]}" 2>/dev/null \
	| grep -vE ':[0-9]+:[[:space:]]*(\*|//|#)' || true)"
if [[ -n "$DANGEROUS" ]]; then
	printf '%s\n' "$DANGEROUS" >&2
	result 1 "dangerous PHP function calls found"
else
	result 0 "no dangerous PHP function calls"
fi

# `wp eval-file` scripts run under WP-CLI where direct echo is fine;
# template files must escape everything they print.
UNESCAPED="$(grep -rEn '<\?=' --include='*.php' "${CUSTOM_PATHS[@]}" 2>/dev/null || true)"
if [[ -n "$UNESCAPED" ]]; then
	printf '%s\n' "$UNESCAPED" >&2
	result 1 "short echo tags found — use explicit escaping functions"
else
	result 0 "no short echo tags"
fi

# -------------------------------------------------------------------
step "WordPress Coding Standards"
# -------------------------------------------------------------------
if [[ -x vendor/bin/phpcs ]]; then
	if vendor/bin/phpcs --report=summary --runtime-set ignore_warnings_on_exit 1; then
		result 0 "PHPCS passed"
	else
		result 1 "PHPCS reported errors (run: vendor/bin/phpcbf to auto-fix what it can)"
	fi
else
	warn "PHPCS not installed — run: composer install"
fi

# -------------------------------------------------------------------
step "Asset sanity"
# -------------------------------------------------------------------
for f in wp-content/themes/yka-portal/style.css \
         wp-content/themes/yka-portal/theme.json \
         wp-content/themes/yka-portal/functions.php \
         wp-content/plugins/yka-core/yka-core.php; do
	assert "present: $f" test -f "$f"
done

if command -v node >/dev/null 2>&1 && [[ -f wp-content/themes/yka-portal/theme.json ]]; then
	assert "theme.json is valid JSON" \
		node -e "JSON.parse(require('fs').readFileSync('wp-content/themes/yka-portal/theme.json','utf8'))"
fi
fi

# -------------------------------------------------------------------
if [[ "$MODE" != "--lint" ]]; then
step "Live site checks"
# -------------------------------------------------------------------
URL="$(local_url)"
if ! curl -ksSf -o /dev/null --max-time 10 "$URL" 2>/dev/null; then
	warn "site is not responding at $URL — skipping HTTP checks (run ./scripts/start.sh)"
else
	check_url() {
		local path="$1" expect="$2" label="$3" code
		code="$(curl -ks -o /dev/null -w '%{http_code}' --max-time 20 "${URL}${path}" || echo 000)"
		if [[ "$code" == "$expect" ]]; then
			result 0 "$label → HTTP $code"
		else
			result 1 "$label → HTTP $code (expected $expect)"
		fi
	}

	check_url "/" 200 "homepage"
	check_url "/berita/" 200 "news archive"
	check_url "/unit/smp-kasih-ananda-1/" 200 "unit archive"
	check_url "/unit-pendidikan/" 200 "unit index page"
	check_url "/prestasi/" 200 "achievements page"
	check_url "/pengumuman/" 200 "announcements page"
	check_url "/galeri/" 200 "gallery page"
	check_url "/kontak/" 200 "contact page"
	check_url "/topik/kegiatan/" 200 "category archive"
	check_url "/?s=kegiatan" 200 "search"
	check_url "/feed/" 200 "RSS feed"
	check_url "/robots.txt" 200 "robots.txt"
	check_url "/halaman-yang-tidak-ada-sama-sekali/" 404 "404 status"

	HTML="$(curl -ks --max-time 20 "$URL" || true)"

	count_tag() { printf '%s' "$HTML" | grep -oF "$1" | wc -l | tr -d ' '; }
	assert_count() {
		local label="$1" needle="$2" max="$3" found
		found="$(count_tag "$needle")"
		if [[ "$found" -le "$max" ]]; then
			result 0 "$label (found $found)"
		else
			result 1 "$label (found $found, expected at most $max)"
		fi
	}

	assert_count "no duplicate <title>" '<title' 1
	assert_count "no duplicate canonical" 'rel="canonical"' 1
	assert_count "no duplicate meta description" 'name="description"' 1
	assert_count "no duplicate og:title" 'property="og:title"' 1
	assert_count "no duplicate og:image" 'property="og:image"' 1
	assert_count "no duplicate twitter:card" 'name="twitter:card"' 1
	assert_count "no duplicate robots meta" 'name="robots"' 1
	assert_count "single JSON-LD block" 'application/ld+json' 1

	assert "JSON-LD structured data present" \
		grep -q 'application/ld+json' <<<"$HTML"
	assert "document language is Indonesian" \
		grep -qE 'lang="id(-ID)?"' <<<"$HTML"
	assert "skip-to-content link present" \
		grep -q 'yka-skip-link' <<<"$HTML"
	assert "hero image carries fetchpriority" \
		grep -q 'fetchpriority="high"' <<<"$HTML"

	# Non-production must never be indexable.
	if [[ "${WP_ENVIRONMENT_TYPE:-local}" != "production" ]]; then
		assert "non-production emits noindex" \
			grep -qiE 'name="robots"[^>]*noindex' <<<"$HTML"
		assert "robots.txt disallows crawling" \
			bash -c "curl -ks --max-time 10 '$URL/robots.txt' | grep -q 'Disallow: /'"
	fi

	if command -v docker >/dev/null 2>&1 && docker info >/dev/null 2>&1; then
		ERRORS="$(dc exec -T wordpress sh -c 'test -f /var/www/html/wp-content/debug.log && tail -100 /var/www/html/wp-content/debug.log' 2>/dev/null | grep -cE 'PHP (Fatal|Parse) error' || true)"
		if [[ "${ERRORS:-0}" -eq 0 ]]; then
			result 0 "no fatal PHP errors in debug.log"
		else
			result 1 "$ERRORS fatal PHP error(s) in debug.log"
		fi
	fi
fi
fi

# -------------------------------------------------------------------
printf '\n%s%s passed, %s failed%s\n' "$C_BOLD" "$PASSED" "$FAILED" "$C_RESET"
[[ "$FAILED" -eq 0 ]] || exit 1
