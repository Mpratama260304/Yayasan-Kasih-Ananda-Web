<?php
/**
 * Template helpers.
 *
 * Global, `yka_`-prefixed functions that templates call. Keeping them here
 * rather than in the theme means the data layer survives a theme change.
 *
 * @package YKA\Core
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

use YKA\Core\Environment;
use YKA\Core\Images;
use YKA\Core\Post_Meta;
use YKA\Core\Schema;
use YKA\Core\Settings;
use YKA\Core\Social_Caption;
use YKA\Core\Taxonomy;
use YKA\Core\Unit_Meta;

/**
 * The education unit a post belongs to.
 *
 * @param int|WP_Post|null $post Post.
 * @return WP_Term|null
 */
function yka_get_post_unit( $post = null ): ?WP_Term {
	return Taxonomy::get_post_unit( $post );
}

/**
 * All education units in display order.
 *
 * @param bool $hide_empty Skip units without posts.
 * @return WP_Term[]
 */
function yka_get_units( bool $hide_empty = false ): array {
	return Taxonomy::get_units( $hide_empty );
}

/**
 * The three school units (the foundation itself excluded).
 *
 * @return WP_Term[]
 */
function yka_get_school_units(): array {
	return Taxonomy::get_school_units();
}

/**
 * A unit's institutional metadata value.
 *
 * @param int    $term_id Unit term id.
 * @param string $key     Meta key without the `yka_unit_` prefix.
 * @return string
 */
function yka_unit_meta( int $term_id, string $key ): string {
	return Unit_Meta::get( $term_id, 'yka_unit_' . $key );
}

/**
 * A foundation setting.
 *
 * @param string $key           Setting key.
 * @param mixed  $default_value Fallback.
 * @return mixed
 */
function yka_setting( string $key, $default_value = '' ) {
	return Settings::get( $key, $default_value );
}

/**
 * Official social profile URLs of the foundation.
 *
 * @return array<string, string>
 */
function yka_social_urls(): array {
	return Settings::social_urls();
}

/**
 * Whether this installation is the real production site.
 *
 * @return bool
 */
function yka_is_production(): bool {
	return Environment::is_production();
}

/**
 * A post's activity date, formatted for display.
 *
 * @param int|null $post_id Post id.
 * @return string
 */
function yka_activity_date( ?int $post_id = null ): string {
	return Post_Meta::activity_date_display( $post_id ?? (int) get_the_ID() );
}

/**
 * Reads one of the article metadata fields.
 *
 * @param string   $key     Field constant value.
 * @param int|null $post_id Post id.
 * @return string
 */
function yka_post_meta( string $key, ?int $post_id = null ): string {
	return Post_Meta::get( $post_id ?? (int) get_the_ID(), $key );
}

/**
 * The breadcrumb trail for the current view.
 *
 * @return array<int, array{name: string, url: string}>
 */
function yka_breadcrumb_items(): array {
	return Schema::breadcrumb_items();
}

/**
 * The social caption for an article.
 *
 * @param int $post_id Post id.
 * @return string
 */
function yka_social_caption( int $post_id ): string {
	return Social_Caption::build( $post_id );
}

/**
 * Related articles, preferring the same education unit.
 *
 * Runs at most two queries and never returns the current post.
 *
 * @param int $post_id Current post id.
 * @param int $count   How many to return.
 * @return WP_Post[]
 */
function yka_related_posts( int $post_id, int $count = 4 ): array {
	$base = array(
		'post_type'              => 'post',
		'post_status'            => 'publish',
		'posts_per_page'         => $count,
		'post__not_in'           => array( $post_id ),
		'ignore_sticky_posts'    => true,
		'no_found_rows'          => true,
		'update_post_meta_cache' => false,
	);

	$found = array();
	$unit  = yka_get_post_unit( $post_id );

	if ( $unit instanceof WP_Term ) {
		$query = new WP_Query(
			array_merge(
				$base,
				array(
					'tax_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
						array(
							'taxonomy' => Taxonomy::TAXONOMY,
							'field'    => 'term_id',
							'terms'    => $unit->term_id,
						),
					),
				)
			)
		);
		$found = $query->posts;
	}

	// Top up with recent articles from the same category, then anything recent.
	if ( count( $found ) < $count ) {
		$exclude    = array_merge( array( $post_id ), wp_list_pluck( $found, 'ID' ) );
		$categories = wp_get_post_categories( $post_id );

		$query = new WP_Query(
			array_merge(
				$base,
				array(
					'posts_per_page' => $count - count( $found ),
					'post__not_in'   => $exclude,
					'category__in'   => $categories ?: array(),
				)
			)
		);

		$found = array_merge( $found, $query->posts );
	}

	return array_slice( $found, 0, $count );
}

/**
 * Share targets for an article.
 *
 * Plain URLs only — no third-party SDK is loaded anywhere on this site.
 *
 * @param int $post_id Post id.
 * @return array<int, array{id: string, label: string, url: string}>
 */
function yka_share_links( int $post_id ): array {
	$url   = (string) get_permalink( $post_id );
	$title = wp_strip_all_tags( get_the_title( $post_id ) );

	return array(
		array(
			'id'    => 'whatsapp',
			'label' => __( 'WhatsApp', 'yka-core' ),
			'url'   => 'https://api.whatsapp.com/send?text=' . rawurlencode( $title . "\n" . $url ),
		),
		array(
			'id'    => 'facebook',
			'label' => 'Facebook',
			'url'   => 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode( $url ),
		),
		array(
			'id'    => 'x',
			'label' => 'X',
			'url'   => 'https://twitter.com/intent/tweet?text=' . rawurlencode( $title ) . '&url=' . rawurlencode( $url ),
		),
	);
}

/**
 * A wa.me link for a stored WhatsApp number.
 *
 * @param string $number Raw number.
 * @param string $text   Optional prefilled message.
 * @return string
 */
function yka_whatsapp_url( string $number, string $text = '' ): string {
	$digits = preg_replace( '/\D/', '', $number ) ?? '';
	if ( '' === $digits ) {
		return '';
	}

	$url = 'https://wa.me/' . $digits;
	if ( '' !== $text ) {
		$url .= '?text=' . rawurlencode( $text );
	}

	return $url;
}

/**
 * The URL of a registered editorial crop.
 *
 * @param int    $attachment_id Attachment id.
 * @param string $size          Size name.
 * @return string
 */
function yka_image_url( int $attachment_id, string $size = 'large' ): string {
	return Images::url( $attachment_id, $size );
}

/**
 * Names of the editorial image sizes, so templates avoid magic strings.
 *
 * @return array<string, string>
 */
function yka_image_sizes(): array {
	return array(
		'wide'   => Images::SIZE_WIDE,
		'16x9'   => Images::SIZE_16_9,
		'4x3'    => Images::SIZE_4_3,
		'1x1'    => Images::SIZE_1_1,
		'social' => Images::SIZE_SOCIAL,
	);
}

/**
 * The article that should lead the homepage.
 *
 * Falls back to the newest published article so the hero is never empty.
 *
 * @return WP_Post|null
 */
function yka_featured_post(): ?WP_Post {
	$configured = (int) Settings::get( 'featured_post', 0 );

	if ( $configured ) {
		$post = get_post( $configured );
		if ( $post instanceof WP_Post && 'publish' === $post->post_status ) {
			return $post;
		}
	}

	$sticky = get_option( 'sticky_posts' );
	if ( is_array( $sticky ) && $sticky ) {
		$posts = get_posts(
			array(
				'post__in'            => $sticky,
				'post_type'           => 'post',
				'post_status'         => 'publish',
				'numberposts'         => 1,
				'ignore_sticky_posts' => true,
			)
		);
		if ( $posts ) {
			return $posts[0];
		}
	}

	$posts = get_posts(
		array(
			'post_type'           => 'post',
			'post_status'         => 'publish',
			'numberposts'         => 1,
			'ignore_sticky_posts' => true,
		)
	);

	return $posts ? $posts[0] : null;
}

/**
 * The taxonomy name, for templates that need it directly.
 *
 * @return string
 */
function yka_unit_taxonomy(): string {
	return Taxonomy::TAXONOMY;
}
