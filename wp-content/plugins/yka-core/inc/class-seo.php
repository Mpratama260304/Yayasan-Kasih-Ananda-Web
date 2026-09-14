<?php
/**
 * SEO metadata: Rank Math integration with a self-sufficient fallback.
 *
 * @package YKA\Core
 */

declare( strict_types=1 );

namespace YKA\Core;

defined( 'ABSPATH' ) || exit;

/**
 * There must be exactly one owner of canonical, robots, title and Open Graph
 * metadata on any given page.
 *
 * When Rank Math is active it owns all of it and this class only enriches
 * what Rank Math cannot know: which education unit a story belongs to, and
 * which crop of the featured photograph to hand to social networks.
 *
 * When Rank Math is absent the site must still be technically complete, so
 * the same tags are emitted here — and only then.
 */
final class Seo {

	/**
	 * Hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		if ( self::rank_math_active() ) {
			add_filter( 'rank_math/frontend/description', array( __CLASS__, 'filter_description' ) );
			add_action( 'rank_math/opengraph/facebook', array( __CLASS__, 'add_article_og_tags' ), 50 );
			return;
		}

		// Fallback mode — Rank Math is not installed or is deactivated.
		add_filter( 'document_title_parts', array( __CLASS__, 'title_parts' ), 20 );
		add_filter( 'document_title_separator', array( __CLASS__, 'title_separator' ) );
		add_action( 'wp_head', array( __CLASS__, 'render_fallback_meta' ), 2 );
		add_filter( 'wp_robots', array( __CLASS__, 'robots' ), 20 );
	}

	/**
	 * Whether Rank Math is active on this request.
	 *
	 * Feature detection rather than a plugin-file check, so the site never
	 * fatals if the plugin is renamed, disabled or updated.
	 *
	 * @return bool
	 */
	public static function rank_math_active(): bool {
		return defined( 'RANK_MATH_VERSION' ) || class_exists( '\RankMath\Helper' );
	}

	// -----------------------------------------------------------------
	// Shared building blocks
	// -----------------------------------------------------------------

	/**
	 * The best description for the current view.
	 *
	 * @return string
	 */
	public static function description(): string {
		if ( is_singular() ) {
			$post = get_queried_object();
			if ( $post instanceof \WP_Post ) {
				$text = trim( (string) $post->post_excerpt );
				if ( '' === $text ) {
					$text = wp_strip_all_tags( (string) $post->post_content );
				}
				return self::trim_text( $text, 160 );
			}
		}

		if ( is_tax( Taxonomy::TAXONOMY ) ) {
			$term = get_queried_object();
			if ( $term instanceof \WP_Term ) {
				$intro = Unit_Meta::get( $term->term_id, 'yka_unit_intro' );
				if ( '' !== $intro ) {
					return self::trim_text( $intro, 160 );
				}
				return self::trim_text( (string) $term->description, 160 );
			}
		}

		if ( is_category() || is_tag() ) {
			$term = get_queried_object();
			if ( $term instanceof \WP_Term && '' !== trim( (string) $term->description ) ) {
				return self::trim_text( (string) $term->description, 160 );
			}
		}

		if ( is_front_page() ) {
			$intro = (string) Settings::get( 'org_description' );
			if ( '' !== $intro ) {
				return self::trim_text( $intro, 160 );
			}
		}

		return self::trim_text( (string) get_bloginfo( 'description' ), 160 );
	}

	/**
	 * Attachment id of the image that best represents the current view.
	 *
	 * @return int
	 */
	public static function share_image_id(): int {
		if ( is_singular() && has_post_thumbnail() ) {
			return (int) get_post_thumbnail_id();
		}

		if ( is_tax( Taxonomy::TAXONOMY ) ) {
			$term = get_queried_object();
			if ( $term instanceof \WP_Term ) {
				$hero = (int) Unit_Meta::get( $term->term_id, 'yka_unit_hero_id' );
				if ( $hero ) {
					return $hero;
				}
			}
		}

		$hero = (int) Settings::get( 'hero_image', 0 );
		if ( $hero ) {
			return $hero;
		}

		return (int) get_theme_mod( 'custom_logo', 0 );
	}

	/**
	 * Shortens text to a whole word near the limit.
	 *
	 * @param string $text  Source text.
	 * @param int    $limit Character budget.
	 * @return string
	 */
	public static function trim_text( string $text, int $limit ): string {
		$text = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( $text ) ) ?? '' );
		if ( '' === $text || mb_strlen( $text ) <= $limit ) {
			return $text;
		}
		return rtrim( mb_substr( $text, 0, $limit ), " \t\n\r\0\x0B.,;:-" ) . '…';
	}

	// -----------------------------------------------------------------
	// Rank Math integration
	// -----------------------------------------------------------------

	/**
	 * Supplies a description when Rank Math has nothing better.
	 *
	 * @param string $description Rank Math description.
	 * @return string
	 */
	public static function filter_description( $description ): string {
		$description = (string) $description;
		return '' !== trim( $description ) ? $description : self::description();
	}

	/**
	 * Adds the education unit as the article section.
	 *
	 * Rank Math already selects the featured image for og:image and emits
	 * matching width and height, so the image is deliberately left alone:
	 * substituting a different crop here would desynchronise the dimensions
	 * and break previews on Facebook and WhatsApp. The site-wide fallback
	 * image is configured instead, in Settings::sync_share_image().
	 *
	 * @param mixed $og Rank Math OpenGraph instance.
	 * @return void
	 */
	public static function add_article_og_tags( $og ): void {
		if ( ! is_singular( 'post' ) || ! is_object( $og ) || ! method_exists( $og, 'tag' ) ) {
			return;
		}

		$unit = Taxonomy::get_post_unit( get_the_ID() );
		if ( $unit instanceof \WP_Term ) {
			$og->tag( 'article:section', $unit->name );
		}
	}

	// -----------------------------------------------------------------
	// Fallback mode
	// -----------------------------------------------------------------

	/**
	 * Title template when Rank Math is not present.
	 *
	 * @param array<string, string> $parts Title parts.
	 * @return array<string, string>
	 */
	public static function title_parts( $parts ): array {
		$parts = is_array( $parts ) ? $parts : array();
		$name  = (string) Settings::get( 'org_name', get_bloginfo( 'name' ) );

		if ( is_front_page() ) {
			return array(
				'title'   => $name,
				'tagline' => __( 'Portal Resmi', 'yka-core' ),
			);
		}

		unset( $parts['tagline'] );
		$parts['site'] = $name;

		return $parts;
	}

	/**
	 * Title separator.
	 *
	 * @param string $sep Default separator.
	 * @return string
	 */
	public static function title_separator( $sep ): string {
		unset( $sep );
		return '|';
	}

	/**
	 * Robots directives in fallback mode.
	 *
	 * Environment::force_noindex() runs later and overrides all of this on
	 * non-production sites.
	 *
	 * @param array<string, mixed> $robots Robots directives.
	 * @return array<string, mixed>
	 */
	public static function robots( $robots ): array {
		$robots = is_array( $robots ) ? $robots : array();

		// Internal search results are not landing pages.
		if ( is_search() || is_author() || is_date() ) {
			$robots['noindex'] = true;
			$robots['follow']  = true;
			unset( $robots['index'] );
			return $robots;
		}

		if ( ! isset( $robots['noindex'] ) ) {
			$robots['max-image-preview'] = 'large';
			$robots['max-snippet']       = -1;
		}

		return $robots;
	}

	/**
	 * Emits canonical, description and social tags when no SEO plugin does.
	 *
	 * @return void
	 */
	public static function render_fallback_meta(): void {
		$canonical   = self::canonical_url();
		$description = self::description();
		$image_id    = self::share_image_id();
		$image_url   = $image_id ? Images::url( $image_id, Images::SIZE_SOCIAL ) : '';
		$site_name   = (string) Settings::get( 'org_name', get_bloginfo( 'name' ) );
		$title       = wp_get_document_title();

		echo "\n<!-- YKA Core metadata (Rank Math not active) -->\n";

		if ( '' !== $canonical ) {
			printf( "<link rel=\"canonical\" href=\"%s\" />\n", esc_url( $canonical ) );
		}
		if ( '' !== $description ) {
			printf( "<meta name=\"description\" content=\"%s\" />\n", esc_attr( $description ) );
		}

		printf( "<meta property=\"og:locale\" content=\"%s\" />\n", esc_attr( str_replace( '-', '_', (string) get_bloginfo( 'language' ) ) ) );
		printf( "<meta property=\"og:site_name\" content=\"%s\" />\n", esc_attr( $site_name ) );
		printf( "<meta property=\"og:type\" content=\"%s\" />\n", esc_attr( is_singular( 'post' ) ? 'article' : 'website' ) );
		printf( "<meta property=\"og:title\" content=\"%s\" />\n", esc_attr( $title ) );

		if ( '' !== $description ) {
			printf( "<meta property=\"og:description\" content=\"%s\" />\n", esc_attr( $description ) );
		}
		if ( '' !== $canonical ) {
			printf( "<meta property=\"og:url\" content=\"%s\" />\n", esc_url( $canonical ) );
		}

		if ( '' !== $image_url ) {
			printf( "<meta property=\"og:image\" content=\"%s\" />\n", esc_url( $image_url ) );
			printf( "<meta property=\"og:image:width\" content=\"1200\" />\n" );
			printf( "<meta property=\"og:image:height\" content=\"630\" />\n" );

			$alt = (string) get_post_meta( $image_id, '_wp_attachment_image_alt', true );
			if ( '' !== $alt ) {
				printf( "<meta property=\"og:image:alt\" content=\"%s\" />\n", esc_attr( $alt ) );
			}
			echo "<meta name=\"twitter:card\" content=\"summary_large_image\" />\n";
		} else {
			echo "<meta name=\"twitter:card\" content=\"summary\" />\n";
		}

		printf( "<meta name=\"twitter:title\" content=\"%s\" />\n", esc_attr( $title ) );
		if ( '' !== $description ) {
			printf( "<meta name=\"twitter:description\" content=\"%s\" />\n", esc_attr( $description ) );
		}
		if ( '' !== $image_url ) {
			printf( "<meta name=\"twitter:image\" content=\"%s\" />\n", esc_url( $image_url ) );
		}

		if ( is_singular( 'post' ) ) {
			$post = get_queried_object();
			if ( $post instanceof \WP_Post ) {
				printf( "<meta property=\"article:published_time\" content=\"%s\" />\n", esc_attr( (string) get_post_time( 'c', true, $post ) ) );
				printf( "<meta property=\"article:modified_time\" content=\"%s\" />\n", esc_attr( (string) get_post_modified_time( 'c', true, $post ) ) );

				$unit = Taxonomy::get_post_unit( $post );
				if ( $unit instanceof \WP_Term ) {
					printf( "<meta property=\"article:section\" content=\"%s\" />\n", esc_attr( $unit->name ) );
				}
			}
		}
	}

	/**
	 * Canonical URL for the current view.
	 *
	 * Always derived from WordPress APIs so it follows the site to staging
	 * and production without any hard-coded hostname.
	 *
	 * @return string
	 */
	public static function canonical_url(): string {
		if ( is_front_page() ) {
			return home_url( '/' );
		}
		if ( is_singular() ) {
			return (string) get_permalink();
		}
		if ( is_home() ) {
			$posts_page = (int) get_option( 'page_for_posts' );
			return $posts_page ? (string) get_permalink( $posts_page ) : home_url( '/' );
		}
		if ( is_category() || is_tag() || is_tax() ) {
			$term = get_queried_object();
			if ( $term instanceof \WP_Term ) {
				$link = get_term_link( $term );
				return is_wp_error( $link ) ? '' : (string) $link;
			}
		}
		if ( is_search() ) {
			return (string) get_search_link();
		}
		if ( is_author() ) {
			$author = get_queried_object();
			return $author instanceof \WP_User ? (string) get_author_posts_url( $author->ID ) : '';
		}

		return '';
	}
}
