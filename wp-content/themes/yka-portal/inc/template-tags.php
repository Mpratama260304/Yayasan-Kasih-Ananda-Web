<?php
/**
 * Template tags.
 *
 * Every function here escapes what it prints. Templates call these rather
 * than assembling markup inline.
 *
 * @package YKA_Portal
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * The education unit relevant to the current request.
 *
 * @return WP_Term|null
 */
function yka_current_unit(): ?WP_Term {
	if ( ! function_exists( 'yka_unit_taxonomy' ) ) {
		return null;
	}

	if ( is_tax( yka_unit_taxonomy() ) ) {
		$term = get_queried_object();
		return $term instanceof WP_Term ? $term : null;
	}

	if ( is_singular( 'post' ) ) {
		return yka_get_post_unit( get_queried_object_id() );
	}

	return null;
}

/**
 * Short CSS key for a unit slug, used for the per-unit accent colour.
 *
 * @param string $slug Term slug.
 * @return string
 */
function yka_unit_key( string $slug ): string {
	if ( str_starts_with( $slug, 'sd-' ) ) {
		return 'sd';
	}
	if ( str_starts_with( $slug, 'smp-' ) ) {
		return 'smp';
	}
	if ( str_starts_with( $slug, 'smk-' ) ) {
		return 'smk';
	}
	return 'yayasan';
}

/**
 * Renders the unit stamp — this site's recurring identity device.
 *
 * @param WP_Term|null         $unit Unit term.
 * @param array<string, mixed> $args link: bool, overlay: bool, short: bool.
 * @return void
 */
function yka_unit_stamp( ?WP_Term $unit, array $args = array() ): void {
	if ( ! $unit instanceof WP_Term ) {
		return;
	}

	$args = wp_parse_args(
		$args,
		array(
			'link'    => true,
			'overlay' => false,
			'short'   => false,
		)
	);

	$label = $unit->name;
	if ( $args['short'] ) {
		$short = yka_unit_meta( $unit->term_id, 'short_name' );
		$label = '' !== $short ? $short : $unit->name;
	}

	$classes = array( 'yka-unit-stamp', 'yka-unit-stamp--' . yka_unit_key( $unit->slug ) );
	if ( $args['overlay'] ) {
		$classes[] = 'yka-unit-stamp--overlay';
	}

	$class_attr = esc_attr( implode( ' ', $classes ) );

	if ( $args['link'] ) {
		$link = get_term_link( $unit );
		if ( ! is_wp_error( $link ) ) {
			printf(
				'<a class="%s" href="%s">%s</a>',
				$class_attr, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
				esc_url( $link ),
				esc_html( $label )
			);
			return;
		}
	}

	printf(
		'<span class="%s">%s</span>',
		$class_attr, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
		esc_html( $label )
	);
}

/**
 * The metadata line above a headline: unit, category, date.
 *
 * @param int|WP_Post|null     $post Post.
 * @param array<string, mixed> $args show_unit, show_category, show_date, short_unit.
 * @return void
 */
function yka_meta_line( $post = null, array $args = array() ): void {
	$post = get_post( $post );
	if ( ! $post instanceof WP_Post ) {
		return;
	}

	$args = wp_parse_args(
		$args,
		array(
			'show_unit'     => true,
			'show_category' => true,
			'show_date'     => true,
			'short_unit'    => false,
		)
	);

	$pieces = array();

	if ( $args['show_unit'] ) {
		$unit = yka_get_post_unit( $post );
		if ( $unit instanceof WP_Term ) {
			ob_start();
			yka_unit_stamp( $unit, array( 'short' => (bool) $args['short_unit'] ) );
			$pieces[] = (string) ob_get_clean();
		}
	}

	if ( $args['show_category'] ) {
		$categories = get_the_category( $post->ID );
		if ( $categories ) {
			$pieces[] = sprintf(
				'<a class="yka-meta__category" href="%s">%s</a>',
				esc_url( (string) get_category_link( $categories[0]->term_id ) ),
				esc_html( $categories[0]->name )
			);
		}
	}

	if ( $args['show_date'] ) {
		$pieces[] = sprintf(
			'<time datetime="%s">%s</time>',
			esc_attr( (string) get_the_date( DATE_W3C, $post ) ),
			esc_html( (string) get_the_date( '', $post ) )
		);
	}

	if ( ! $pieces ) {
		return;
	}

	echo '<p class="yka-meta">';
	foreach ( $pieces as $index => $piece ) {
		if ( $index > 0 ) {
			echo '<span class="yka-meta__sep" aria-hidden="true">·</span>';
		}
		// Each piece is assembled from escaped values above.
		echo $piece; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	echo '</p>';
}

/**
 * Renders an article's image, or a restrained branded placeholder.
 *
 * Always a real `<img>` element so search engines can find it.
 *
 * @param int|WP_Post|null     $post Post.
 * @param string               $size Image size.
 * @param array<string, mixed> $args unit_overlay: bool, eager: bool, sizes: string.
 * @return void
 */
function yka_story_image( $post = null, string $size = 'yka-card', array $args = array() ): void {
	$post = get_post( $post );
	if ( ! $post instanceof WP_Post ) {
		return;
	}

	$args = wp_parse_args(
		$args,
		array(
			'unit_overlay' => false,
			'eager'        => false,
			'sizes'        => '',
		)
	);

	$unit = $args['unit_overlay'] ? yka_get_post_unit( $post ) : null;

	if ( ! has_post_thumbnail( $post ) ) {
		echo '<div class="yka-story__figure yka-story__figure--empty" aria-hidden="true"><span>';
		echo esc_html( get_bloginfo( 'name' ) );
		echo '</span></div>';
		return;
	}

	$attr = array(
		'class'    => 'yka-story__img',
		'decoding' => 'async',
	);

	if ( $args['eager'] ) {
		$attr['loading']       = 'eager';
		$attr['fetchpriority'] = 'high';
	} else {
		$attr['loading'] = 'lazy';
	}

	if ( '' !== $args['sizes'] ) {
		$attr['sizes'] = $args['sizes'];
	}

	echo '<div class="yka-story__figure">';
	echo wp_kses_post( get_the_post_thumbnail( $post, $size, $attr ) );

	if ( $unit instanceof WP_Term ) {
		yka_unit_stamp(
			$unit,
			array(
				'overlay' => true,
				'short'   => true,
				'link'    => false,
			)
		);
	}

	echo '</div>';
}

/**
 * Renders one story in a given shape.
 *
 * Variants: lead, secondary, compact, headline, row.
 *
 * @param int|WP_Post|null     $post    Post.
 * @param string               $variant Shape.
 * @param array<string, mixed> $args    Overrides.
 * @return void
 */
function yka_story( $post = null, string $variant = 'secondary', array $args = array() ): void {
	$post = get_post( $post );
	if ( ! $post instanceof WP_Post ) {
		return;
	}

	$defaults = array(
		'lead'      => array(
			'image'   => true,
			'size'    => 'yka-16x9',
			'excerpt' => true,
			'eager'   => true,
			'sizes'   => '(min-width: 1040px) 620px, 100vw',
		),
		'secondary' => array(
			'image'   => true,
			'size'    => 'yka-4x3',
			'excerpt' => true,
			'eager'   => false,
			'sizes'   => '(min-width: 1040px) 380px, (min-width: 760px) 50vw, 100vw',
		),
		'compact'   => array(
			'image'   => true,
			'size'    => 'yka-thumb',
			'excerpt' => false,
			'eager'   => false,
			'sizes'   => '96px',
		),
		'headline'  => array(
			'image'   => false,
			'size'    => '',
			'excerpt' => false,
			'eager'   => false,
			'sizes'   => '',
		),
		'row'       => array(
			'image'   => true,
			'size'    => 'yka-4x3',
			'excerpt' => true,
			'eager'   => false,
			'sizes'   => '(min-width: 680px) 280px, 100vw',
		),
	);

	$config = wp_parse_args( $args, $defaults[ $variant ] ?? $defaults['secondary'] );

	printf( '<article class="yka-story yka-story--%s">', esc_attr( $variant ) );

	if ( $config['image'] ) {
		yka_story_image(
			$post,
			(string) $config['size'],
			array(
				'unit_overlay' => in_array( $variant, array( 'lead', 'row' ), true ),
				'eager'        => (bool) $config['eager'],
				'sizes'        => (string) $config['sizes'],
			)
		);
	}

	echo '<div class="yka-story__body">';

	yka_meta_line(
		$post,
		array(
			'show_unit'     => ! in_array( $variant, array( 'lead', 'row' ), true ),
			'show_category' => in_array( $variant, array( 'lead', 'row' ), true ),
			'short_unit'    => 'compact' === $variant,
		)
	);

	printf(
		'<h3 class="yka-story__title"><a href="%s">%s</a></h3>',
		esc_url( (string) get_permalink( $post ) ),
		esc_html( get_the_title( $post ) )
	);

	if ( $config['excerpt'] ) {
		$excerpt = yka_excerpt( $post, 'lead' === $variant ? 40 : 24 );
		if ( '' !== $excerpt ) {
			printf( '<p class="yka-story__excerpt">%s</p>', esc_html( $excerpt ) );
		}
	}

	echo '</div></article>';
}

/**
 * A plain-text excerpt of a given length, falling back to the content.
 *
 * @param int|WP_Post|null $post  Post.
 * @param int              $words Word budget.
 * @return string
 */
function yka_excerpt( $post = null, int $words = 24 ): string {
	$post = get_post( $post );
	if ( ! $post instanceof WP_Post ) {
		return '';
	}

	$text = trim( (string) $post->post_excerpt );

	if ( '' === $text ) {
		$text = (string) $post->post_content;
		$text = (string) preg_replace( '/<!--\s*wp:(block|shortcode).*?-->.*?<!--\s*\/wp:\1\s*-->/s', '', $text );
		$text = strip_shortcodes( $text );
		$text = excerpt_remove_blocks( $text );
	}

	$text = wp_strip_all_tags( $text, true );
	$text = trim( (string) preg_replace( '/\s+/u', ' ', $text ) );

	return '' === $text ? '' : wp_trim_words( $text, $words, '…' );
}

/**
 * Renders a section heading with an optional note and "see all" link.
 *
 * @param string               $title Heading text.
 * @param array<string, mixed> $args  note, link, link_label, level, id.
 * @return void
 */
function yka_section_head( string $title, array $args = array() ): void {
	$args = wp_parse_args(
		$args,
		array(
			'note'       => '',
			'link'       => '',
			'link_label' => __( 'Lihat semua', 'yka-portal' ),
			'level'      => 2,
			'id'         => '',
		)
	);

	$level = max( 2, min( 4, (int) $args['level'] ) );
	$tag   = 'h' . $level;

	echo '<div class="yka-section-head">';
	echo '<div>';
	printf(
		'<%1$s class="yka-section-head__title"%2$s>%3$s</%1$s>',
		esc_attr( $tag ),
		'' !== $args['id'] ? ' id="' . esc_attr( (string) $args['id'] ) . '"' : '',
		esc_html( $title )
	);
	if ( '' !== $args['note'] ) {
		printf( '<p class="yka-section-head__note">%s</p>', esc_html( (string) $args['note'] ) );
	}
	echo '</div>';

	if ( '' !== $args['link'] ) {
		printf(
			'<a class="yka-section-head__link" href="%s">%s</a>',
			esc_url( (string) $args['link'] ),
			esc_html( (string) $args['link_label'] )
		);
	}

	echo '</div>';
}

/**
 * An empty state. Never leaves a blank panel and never invents content.
 *
 * @param string $message Primary message.
 * @param string $hint    Optional secondary line.
 * @return void
 */
function yka_empty_state( string $message, string $hint = '' ): void {
	echo '<div class="yka-empty">';
	printf( '<p>%s</p>', esc_html( $message ) );
	if ( '' !== $hint ) {
		printf( '<p>%s</p>', esc_html( $hint ) );
	}
	echo '</div>';
}

/**
 * Breadcrumb navigation.
 *
 * Rank Math owns breadcrumb schema when it is active; this only renders the
 * visible trail so the two never emit competing BreadcrumbList markup.
 *
 * @return void
 */
function yka_breadcrumbs(): void {
	if ( is_front_page() ) {
		return;
	}

	if ( function_exists( 'rank_math_the_breadcrumbs' ) && class_exists( '\RankMath\Helper' ) ) {
		echo '<nav class="yka-breadcrumb" aria-label="' . esc_attr__( 'Remah roti', 'yka-portal' ) . '">';
		rank_math_the_breadcrumbs();
		echo '</nav>';
		return;
	}

	if ( ! function_exists( 'yka_breadcrumb_items' ) ) {
		return;
	}

	$items = yka_breadcrumb_items();
	if ( count( $items ) < 2 ) {
		return;
	}

	echo '<nav class="yka-breadcrumb" aria-label="' . esc_attr__( 'Remah roti', 'yka-portal' ) . '"><ol>';

	$last = count( $items ) - 1;
	foreach ( $items as $index => $item ) {
		echo '<li>';
		if ( $index === $last || '' === $item['url'] ) {
			printf( '<span aria-current="page">%s</span>', esc_html( $item['name'] ) );
		} else {
			printf( '<a href="%s">%s</a>', esc_url( $item['url'] ), esc_html( $item['name'] ) );
		}
		echo '</li>';
	}

	echo '</ol></nav>';
}

/**
 * Accessible numbered pagination.
 *
 * @param WP_Query|null $query Query to paginate.
 * @return void
 */
function yka_pagination( ?WP_Query $query = null ): void {
	global $wp_query;
	$query = $query ?? $wp_query;

	if ( ! $query instanceof WP_Query || (int) $query->max_num_pages < 2 ) {
		return;
	}

	$links = paginate_links(
		array(
			'total'     => (int) $query->max_num_pages,
			'current'   => max( 1, (int) get_query_var( 'paged' ) ),
			'mid_size'  => 1,
			'end_size'  => 1,
			'type'      => 'list',
			'prev_text' => __( '← Sebelumnya', 'yka-portal' ),
			'next_text' => __( 'Berikutnya →', 'yka-portal' ),
		)
	);

	if ( ! $links ) {
		return;
	}

	echo '<nav class="yka-pagination" aria-label="' . esc_attr__( 'Navigasi halaman', 'yka-portal' ) . '">';
	echo '<div class="nav-links">' . wp_kses_post( str_replace( array( '<ul class=\'page-numbers\'>', '</ul>', '<li>', '</li>' ), '', $links ) ) . '</div>';
	echo '</nav>';
}

/**
 * Inline SVG icons.
 *
 * A tiny set of hand-written paths. No icon library is loaded, and icons are
 * only used where they carry meaning — never as decoration beside a heading.
 *
 * @param string               $name Icon name.
 * @param array<string, mixed> $args size, class.
 * @return string
 */
function yka_icon( string $name, array $args = array() ): string {
	$size = (int) ( $args['size'] ?? 20 );

	$paths = array(
		'search'   => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
		'menu'     => '<path d="M4 7h16M4 12h16M4 17h16"/>',
		'close'    => '<path d="M6 6l12 12M18 6 6 18"/>',
		'link'     => '<path d="M10 13a5 5 0 0 0 7.07 0l2.83-2.83a5 5 0 0 0-7.07-7.07l-1.41 1.41"/><path d="M14 11a5 5 0 0 0-7.07 0L4.1 13.83a5 5 0 0 0 7.07 7.07l1.41-1.41"/>',
		'share'    => '<circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="m8.6 13.5 6.8 4M15.4 6.5l-6.8 4"/>',
		'chevron'  => '<path d="m6 9 6 6 6-6"/>',
		'phone'    => '<path d="M5 4h4l2 5-2.5 1.5a12 12 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2Z"/>',
		'mail'     => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
		'pin'      => '<path d="M12 21s7-5.5 7-11a7 7 0 1 0-14 0c0 5.5 7 11 7 11Z"/><circle cx="12" cy="10" r="2.5"/>',
		'external' => '<path d="M14 4h6v6"/><path d="M20 4 10 14"/><path d="M18 13v6a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h6"/>',
	);

	// Brand marks need fills rather than strokes.
	$filled = array(
		'whatsapp'  => '<path d="M12.04 2a9.9 9.9 0 0 0-8.5 14.96L2 22l5.2-1.5A9.9 9.9 0 1 0 12.04 2Zm0 1.8a8.1 8.1 0 1 1-4.1 15.07l-.3-.17-3.08.89.9-3-.2-.31A8.1 8.1 0 0 1 12.05 3.8Zm-3.6 4.02c-.17 0-.45.07-.69.32-.24.25-.9.88-.9 2.15s.92 2.5 1.05 2.67c.13.17 1.8 2.87 4.45 3.9 2.2.86 2.65.69 3.13.65.48-.05 1.55-.63 1.77-1.25.22-.62.22-1.15.15-1.26-.06-.1-.24-.17-.5-.3-.27-.13-1.56-.77-1.8-.86-.24-.09-.42-.13-.6.13-.17.26-.68.86-.83 1.03-.16.18-.31.2-.57.07-.27-.13-1.12-.41-2.13-1.32a8.02 8.02 0 0 1-1.48-1.83c-.15-.26-.02-.4.12-.53.12-.12.26-.31.4-.46.13-.16.17-.27.26-.45.09-.17.04-.33-.02-.46-.07-.13-.6-1.43-.82-1.96-.2-.5-.4-.43-.55-.44h-.47Z"/>',
		'facebook'  => '<path d="M22 12.06C22 6.5 17.52 2 12 2S2 6.5 2 12.06c0 5.02 3.66 9.18 8.44 9.94v-7.03H7.9v-2.9h2.54V9.85c0-2.52 1.5-3.91 3.77-3.91 1.1 0 2.24.2 2.24.2v2.46h-1.26c-1.24 0-1.63.78-1.63 1.57v1.89h2.78l-.45 2.9h-2.33V22c4.78-.76 8.44-4.92 8.44-9.94Z"/>',
		'x'         => '<path d="M17.53 3h3.04l-6.64 7.59L21.75 21h-5.9l-4.62-6.04L5.94 21H2.9l7.1-8.12L2.4 3h6.05l4.18 5.52L17.53 3Zm-1.07 16.17h1.69L7.6 4.74H5.79l10.67 14.43Z"/>',
		'instagram' => '<path d="M12 2.16c3.2 0 3.58.01 4.85.07 1.17.05 1.8.25 2.23.41.56.22.96.48 1.38.9.42.42.68.82.9 1.38.16.42.36 1.06.41 2.23.06 1.27.07 1.65.07 4.85s-.01 3.58-.07 4.85c-.05 1.17-.25 1.8-.41 2.23-.22.56-.48.96-.9 1.38-.42.42-.82.68-1.38.9-.42.16-1.06.36-2.23.41-1.27.06-1.65.07-4.85.07s-3.58-.01-4.85-.07c-1.17-.05-1.8-.25-2.23-.41-.56-.22-.96-.48-1.38-.9a3.8 3.8 0 0 1-.9-1.38c-.16-.42-.36-1.06-.41-2.23C2.17 15.58 2.16 15.2 2.16 12s.01-3.58.07-4.85c.05-1.17.25-1.8.41-2.23.22-.56.48-.96.9-1.38.42-.42.82-.68 1.38-.9.42-.16 1.06-.36 2.23-.41C8.42 2.17 8.8 2.16 12 2.16Zm0 1.84c-3.15 0-3.5.01-4.74.07-1.14.05-1.76.24-2.17.4-.55.21-.94.47-1.35.88-.41.41-.67.8-.88 1.35-.16.41-.35 1.03-.4 2.17-.06 1.24-.07 1.59-.07 4.74s.01 3.5.07 4.74c.05 1.14.24 1.76.4 2.17.21.55.47.94.88 1.35.41.41.8.67 1.35.88.41.16 1.03.35 2.17.4 1.24.06 1.59.07 4.74.07s3.5-.01 4.74-.07c1.14-.05 1.76-.24 2.17-.4.55-.21.94-.47 1.35-.88.41-.41.67-.8.88-1.35.16-.41.35-1.03.4-2.17.06-1.24.07-1.59.07-4.74s-.01-3.5-.07-4.74c-.05-1.14-.24-1.76-.4-2.17a3.6 3.6 0 0 0-.88-1.35 3.6 3.6 0 0 0-1.35-.88c-.41-.16-1.03-.35-2.17-.4-1.24-.06-1.59-.07-4.74-.07Zm0 3.13a4.87 4.87 0 1 1 0 9.74 4.87 4.87 0 0 1 0-9.74Zm0 8.03a3.16 3.16 0 1 0 0-6.32 3.16 3.16 0 0 0 0 6.32Zm6.2-8.22a1.14 1.14 0 1 1-2.27 0 1.14 1.14 0 0 1 2.27 0Z"/>',
		'youtube'   => '<path d="M21.58 7.19a2.51 2.51 0 0 0-1.77-1.78C18.25 5 12 5 12 5s-6.25 0-7.81.41A2.51 2.51 0 0 0 2.42 7.2C2 8.76 2 12 2 12s0 3.24.42 4.81a2.51 2.51 0 0 0 1.77 1.78C5.75 19 12 19 12 19s6.25 0 7.81-.41a2.51 2.51 0 0 0 1.77-1.78C22 15.24 22 12 22 12s0-3.24-.42-4.81ZM10 15.02V8.98L15.2 12 10 15.02Z"/>',
		'tiktok'    => '<path d="M16.6 5.82A4.28 4.28 0 0 1 15.54 3h-3.09v12.4a2.59 2.59 0 1 1-1.8-2.47V9.77a5.95 5.95 0 1 0 5.15 5.9V9.4a7.4 7.4 0 0 0 4.32 1.38V7.7a4.3 4.3 0 0 1-3.52-1.88Z"/>',
	);

	if ( isset( $filled[ $name ] ) ) {
		return sprintf(
			'<svg class="yka-icon%s" width="%d" height="%d" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false">%s</svg>',
			isset( $args['class'] ) ? ' ' . esc_attr( (string) $args['class'] ) : '',
			$size,
			$size,
			$filled[ $name ]
		);
	}

	if ( ! isset( $paths[ $name ] ) ) {
		return '';
	}

	return sprintf(
		'<svg class="yka-icon%s" width="%d" height="%d" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">%s</svg>',
		isset( $args['class'] ) ? ' ' . esc_attr( (string) $args['class'] ) : '',
		$size,
		$size,
		$paths[ $name ]
	);
}

/**
 * Renders the page's LCP image with priority hints that survive core.
 *
 * WordPress decides `loading` and `fetchpriority` itself through
 * wp_get_loading_optimization_attributes(), and it drops a caller-supplied
 * `fetchpriority` in several situations. For the hero we know better than
 * the heuristic: it is always the largest contentful paint, so the hints
 * are re-applied to the finished markup.
 *
 * @param int                  $attachment_id Attachment id.
 * @param string               $size          Image size.
 * @param array<string, mixed> $attr          Extra attributes.
 * @return void
 */
function yka_lcp_image( int $attachment_id, string $size = 'yka-wide', array $attr = array() ): void {
	if ( ! $attachment_id ) {
		return;
	}

	$html = (string) wp_get_attachment_image(
		$attachment_id,
		$size,
		false,
		wp_parse_args(
			$attr,
			array(
				'decoding' => 'async',
				'sizes'    => '100vw',
			)
		)
	);

	if ( '' === $html ) {
		return;
	}

	$html = str_replace( array( ' loading="lazy"', ' fetchpriority="low"', ' fetchpriority="auto"' ), '', $html );

	if ( ! str_contains( $html, 'fetchpriority=' ) ) {
		$html = (string) preg_replace( '/<img /', '<img fetchpriority="high" ', $html, 1 );
	}
	if ( ! str_contains( $html, 'loading=' ) ) {
		$html = (string) preg_replace( '/<img /', '<img loading="eager" ', $html, 1 );
	}

	echo wp_kses(
		$html,
		array(
			'img' => array(
				'src'           => true,
				'srcset'        => true,
				'sizes'         => true,
				'alt'           => true,
				'width'         => true,
				'height'        => true,
				'class'         => true,
				'id'            => true,
				'style'         => true,
				'title'         => true,
				'loading'       => true,
				'decoding'      => true,
				'fetchpriority' => true,
			),
		)
	);
}

/**
 * Formats a placeholder for information the organisation has not supplied.
 *
 * Development placeholders must look like placeholders. Inventing a
 * plausible address or phone number would be far worse than showing a gap.
 *
 * @param string $label What is missing.
 * @return string
 */
function yka_placeholder( string $label ): string {
	return sprintf(
		'<span class="yka-placeholder">[%s]</span>',
		esc_html( $label )
	);
}
