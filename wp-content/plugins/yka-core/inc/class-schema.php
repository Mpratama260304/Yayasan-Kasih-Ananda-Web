<?php
/**
 * Structured data (JSON-LD).
 *
 * @package YKA\Core
 */

declare( strict_types=1 );

namespace YKA\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Describes what is genuinely on the page, and nothing else.
 *
 * Two modes, never both at once:
 *  - Rank Math active: its graph is extended through `rank_math/json_ld`.
 *  - Rank Math absent: a complete graph is emitted here.
 *
 * Every `@id` is derived from home_url(), so the graph moves cleanly from
 * development to staging to production.
 */
final class Schema {

	/**
	 * Hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		if ( Seo::rank_math_active() ) {
			add_filter( 'rank_math/json_ld', array( __CLASS__, 'extend_rank_math_graph' ), 20, 2 );
			return;
		}

		add_action( 'wp_head', array( __CLASS__, 'render_graph' ), 3 );
	}

	// -----------------------------------------------------------------
	// Entity builders
	// -----------------------------------------------------------------

	/**
	 * Stable id for the foundation entity.
	 *
	 * @return string
	 */
	public static function organization_id(): string {
		return home_url( '/#organization' );
	}

	/**
	 * The foundation as an EducationalOrganization node.
	 *
	 * @return array<string, mixed>
	 */
	public static function organization(): array {
		$node = array(
			'@type' => 'EducationalOrganization',
			'@id'   => self::organization_id(),
			'name'  => (string) Settings::get( 'org_name', get_bloginfo( 'name' ) ),
			'url'   => home_url( '/' ),
		);

		$alternate = (string) Settings::get( 'org_short_name' );
		if ( '' !== $alternate ) {
			$node['alternateName'] = $alternate;
		}

		$description = (string) Settings::get( 'org_description' );
		if ( '' !== $description ) {
			$node['description'] = Seo::trim_text( $description, 300 );
		}

		$founded = (string) Settings::get( 'org_founded' );
		if ( preg_match( '/^\d{4}$/', $founded ) ) {
			$node['foundingDate'] = $founded;
		}

		$logo_id = (int) get_theme_mod( 'custom_logo', 0 );
		if ( $logo_id ) {
			$node['logo']  = self::image_object( $logo_id, home_url( '/#logo' ) );
			$node['image'] = array( '@id' => home_url( '/#logo' ) );
		}

		$address = (string) Settings::get( 'address' );
		if ( '' !== $address ) {
			$node['address'] = array(
				'@type'          => 'PostalAddress',
				'streetAddress'  => $address,
				'addressCountry' => 'ID',
			);
		}

		$phone = (string) Settings::get( 'phone' );
		if ( '' !== $phone ) {
			$node['telephone'] = $phone;
		}

		$email = (string) Settings::get( 'email' );
		if ( '' !== $email ) {
			$node['email'] = $email;
		}

		// sameAs must only ever contain verified official profiles.
		$social = array_values( Settings::social_urls() );
		if ( $social ) {
			$node['sameAs'] = $social;
		}

		$sub = array();
		foreach ( Taxonomy::get_school_units() as $unit ) {
			$link = get_term_link( $unit );
			if ( ! is_wp_error( $link ) ) {
				$sub[] = array( '@id' => $link . '#school' );
			}
		}
		if ( $sub ) {
			$node['subOrganization'] = $sub;
		}

		return $node;
	}

	/**
	 * A school unit as a School node related to the foundation.
	 *
	 * @param \WP_Term $unit Unit term.
	 * @return array<string, mixed>
	 */
	public static function school( \WP_Term $unit ): array {
		$link = get_term_link( $unit );
		$link = is_wp_error( $link ) ? home_url( '/' ) : (string) $link;

		$official = Unit_Meta::get( $unit->term_id, 'yka_unit_official_name' );

		$node = array(
			'@type'              => 'School',
			'@id'                => $link . '#school',
			'name'               => '' !== $official ? $official : $unit->name,
			'url'                => $link,
			'parentOrganization' => array( '@id' => self::organization_id() ),
		);

		$intro = Unit_Meta::get( $unit->term_id, 'yka_unit_intro' );
		if ( '' !== $intro ) {
			$node['description'] = Seo::trim_text( $intro, 300 );
		}

		$logo_id = (int) Unit_Meta::get( $unit->term_id, 'yka_unit_logo_id' );
		if ( $logo_id ) {
			$node['logo'] = self::image_object( $logo_id, $link . '#logo' );
		}

		$hero_id = (int) Unit_Meta::get( $unit->term_id, 'yka_unit_hero_id' );
		if ( $hero_id ) {
			$node['image'] = self::image_object( $hero_id, $link . '#primaryimage' );
		}

		$address = Unit_Meta::get( $unit->term_id, 'yka_unit_address' );
		if ( '' !== $address ) {
			$node['address'] = array(
				'@type'          => 'PostalAddress',
				'streetAddress'  => $address,
				'addressCountry' => 'ID',
			);
		}

		foreach ( array(
			'yka_unit_phone' => 'telephone',
			'yka_unit_email' => 'email',
		) as $meta_key => $property ) {
			$value = Unit_Meta::get( $unit->term_id, $meta_key );
			if ( '' !== $value ) {
				$node[ $property ] = $value;
			}
		}

		$same_as = Unit_Meta::get_social_urls( $unit->term_id );
		$legacy  = Unit_Meta::get( $unit->term_id, 'yka_unit_legacy_url' );
		if ( '' !== $legacy ) {
			$same_as[] = $legacy;
		}
		if ( $same_as ) {
			$node['sameAs'] = array_values( array_unique( $same_as ) );
		}

		return $node;
	}

	/**
	 * An ImageObject, exposing the crops search engines find useful.
	 *
	 * @param int    $attachment_id Attachment id.
	 * @param string $id            Node id.
	 * @return array<string, mixed>
	 */
	public static function image_object( int $attachment_id, string $id ): array {
		$src  = wp_get_attachment_image_src( $attachment_id, 'full' );
		$node = array(
			'@type'      => 'ImageObject',
			'@id'        => $id,
			'url'        => is_array( $src ) ? (string) $src[0] : (string) wp_get_attachment_url( $attachment_id ),
			'contentUrl' => is_array( $src ) ? (string) $src[0] : (string) wp_get_attachment_url( $attachment_id ),
		);

		if ( is_array( $src ) ) {
			$node['width']  = (int) $src[1];
			$node['height'] = (int) $src[2];
		}

		$caption = wp_get_attachment_caption( $attachment_id );
		if ( is_string( $caption ) && '' !== $caption ) {
			$node['caption'] = wp_strip_all_tags( $caption );
		}

		return $node;
	}

	/**
	 * The representative article images in 16:9, 4:3 and 1:1.
	 *
	 * Search engines prefer several aspect ratios of the same photograph;
	 * this never substitutes the organisation logo for a real photo.
	 *
	 * @param int $attachment_id Attachment id.
	 * @return string[]
	 */
	public static function article_image_urls( int $attachment_id ): array {
		if ( ! $attachment_id ) {
			return array();
		}

		$urls = array();
		foreach ( array( Images::SIZE_16_9, Images::SIZE_4_3, Images::SIZE_1_1 ) as $size ) {
			$url = Images::url( $attachment_id, $size );
			if ( '' !== $url ) {
				$urls[] = $url;
			}
		}

		return array_values( array_unique( $urls ) );
	}

	/**
	 * Breadcrumb trail for the current view.
	 *
	 * @return array<int, array{name: string, url: string}>
	 */
	public static function breadcrumb_items(): array {
		$items = array(
			array(
				'name' => __( 'Beranda', 'yka-core' ),
				'url'  => home_url( '/' ),
			),
		);

		$posts_page = (int) get_option( 'page_for_posts' );

		if ( is_singular( 'post' ) ) {
			if ( $posts_page ) {
				$items[] = array(
					'name' => get_the_title( $posts_page ),
					'url'  => (string) get_permalink( $posts_page ),
				);
			}

			$unit = Taxonomy::get_post_unit( get_the_ID() );
			if ( $unit instanceof \WP_Term ) {
				$link = get_term_link( $unit );
				if ( ! is_wp_error( $link ) ) {
					$items[] = array(
						'name' => $unit->name,
						'url'  => (string) $link,
					);
				}
			}

			$items[] = array(
				'name' => wp_strip_all_tags( get_the_title() ),
				'url'  => (string) get_permalink(),
			);

			return $items;
		}

		if ( is_page() ) {
			$ancestors = array_reverse( get_post_ancestors( (int) get_the_ID() ) );
			foreach ( $ancestors as $ancestor_id ) {
				$items[] = array(
					'name' => get_the_title( $ancestor_id ),
					'url'  => (string) get_permalink( $ancestor_id ),
				);
			}
			$items[] = array(
				'name' => wp_strip_all_tags( get_the_title() ),
				'url'  => (string) get_permalink(),
			);
			return $items;
		}

		if ( is_tax( Taxonomy::TAXONOMY ) || is_category() || is_tag() ) {
			if ( $posts_page ) {
				$items[] = array(
					'name' => get_the_title( $posts_page ),
					'url'  => (string) get_permalink( $posts_page ),
				);
			}
			$term = get_queried_object();
			if ( $term instanceof \WP_Term ) {
				$link    = get_term_link( $term );
				$items[] = array(
					'name' => $term->name,
					'url'  => is_wp_error( $link ) ? '' : (string) $link,
				);
			}
			return $items;
		}

		if ( is_search() ) {
			$items[] = array(
				'name' => sprintf(
					/* translators: %s: search term. */
					__( 'Hasil pencarian: %s', 'yka-core' ),
					get_search_query()
				),
				'url'  => (string) get_search_link(),
			);
		}

		return $items;
	}

	// -----------------------------------------------------------------
	// Rank Math mode
	// -----------------------------------------------------------------

	/**
	 * Adds YKA-specific nodes to the Rank Math graph without duplicating it.
	 *
	 * @param array<string, mixed> $data    Existing graph pieces.
	 * @param mixed                $jsonld  Rank Math JsonLD instance.
	 * @return array<string, mixed>
	 */
	public static function extend_rank_math_graph( $data, $jsonld ): array {
		unset( $jsonld );
		$data = is_array( $data ) ? $data : array();

		// Unit archive: describe the school itself.
		if ( is_tax( Taxonomy::TAXONOMY ) ) {
			$unit = get_queried_object();
			if ( $unit instanceof \WP_Term && Taxonomy::UNIT_YAYASAN !== $unit->slug ) {
				$data['ykaSchool'] = self::school( $unit );
			}
		}

		// Articles: make sure the representative photograph is present in
		// several aspect ratios, and that the unit is exposed as the section.
		if ( is_singular( 'post' ) ) {
			$thumbnail_id = (int) get_post_thumbnail_id();
			$images       = self::article_image_urls( $thumbnail_id );

			foreach ( $data as $key => $node ) {
				if ( ! is_array( $node ) || empty( $node['@type'] ) ) {
					continue;
				}

				$types = (array) $node['@type'];
				if ( ! array_intersect( $types, array( 'Article', 'NewsArticle', 'BlogPosting' ) ) ) {
					continue;
				}

				if ( $images ) {
					$data[ $key ]['image'] = $images;
				}

				$unit = Taxonomy::get_post_unit( get_the_ID() );
				if ( $unit instanceof \WP_Term ) {
					$data[ $key ]['articleSection'] = $unit->name;
				}

				$location = Post_Meta::get( (int) get_the_ID(), Post_Meta::LOCATION );
				if ( '' !== $location ) {
					$data[ $key ]['contentLocation'] = array(
						'@type' => 'Place',
						'name'  => $location,
					);
				}
			}
		}

		return $data;
	}

	// -----------------------------------------------------------------
	// Fallback mode
	// -----------------------------------------------------------------

	/**
	 * Emits the full graph when no SEO plugin is providing one.
	 *
	 * @return void
	 */
	public static function render_graph(): void {
		$graph = array( self::organization(), self::website_node() );

		if ( is_tax( Taxonomy::TAXONOMY ) ) {
			$unit = get_queried_object();
			if ( $unit instanceof \WP_Term && Taxonomy::UNIT_YAYASAN !== $unit->slug ) {
				$graph[] = self::school( $unit );
			}
		}

		if ( is_singular( 'post' ) ) {
			$graph[] = self::article_node();
		}

		$breadcrumbs = self::breadcrumb_items();
		if ( count( $breadcrumbs ) > 1 ) {
			$graph[] = self::breadcrumb_node( $breadcrumbs );
		}

		$payload = array(
			'@context' => 'https://schema.org',
			'@graph'   => array_values( array_filter( $graph ) ),
		);

		printf(
			"<script type=\"application/ld+json\">%s</script>\n",
			wp_json_encode( $payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE )
		);
	}

	/**
	 * WebSite node with the native WordPress search action.
	 *
	 * @return array<string, mixed>
	 */
	private static function website_node(): array {
		return array(
			'@type'           => 'WebSite',
			'@id'             => home_url( '/#website' ),
			'url'             => home_url( '/' ),
			'name'            => (string) Settings::get( 'org_name', get_bloginfo( 'name' ) ),
			'inLanguage'      => (string) get_bloginfo( 'language' ),
			'publisher'       => array( '@id' => self::organization_id() ),
			'potentialAction' => array(
				array(
					'@type'       => 'SearchAction',
					'target'      => array(
						'@type'       => 'EntryPoint',
						'urlTemplate' => home_url( '/?s={search_term_string}' ),
					),
					'query-input' => 'required name=search_term_string',
				),
			),
		);
	}

	/**
	 * Article node for the current post.
	 *
	 * Documentation of a past school activity is editorial content, so it is
	 * a NewsArticle — never an Event.
	 *
	 * @return array<string, mixed>
	 */
	private static function article_node(): array {
		$post_id = (int) get_the_ID();
		$node    = array(
			'@type'            => 'NewsArticle',
			'@id'              => get_permalink( $post_id ) . '#article',
			'headline'         => Seo::trim_text( wp_strip_all_tags( get_the_title( $post_id ) ), 110 ),
			'description'      => Seo::description(),
			'datePublished'    => (string) get_post_time( 'c', true, $post_id ),
			'dateModified'     => (string) get_post_modified_time( 'c', true, $post_id ),
			'inLanguage'       => (string) get_bloginfo( 'language' ),
			'mainEntityOfPage' => array( '@id' => (string) get_permalink( $post_id ) ),
			'publisher'        => array( '@id' => self::organization_id() ),
			'isPartOf'         => array( '@id' => home_url( '/#website' ) ),
		);

		$author_id = (int) get_post_field( 'post_author', $post_id );
		if ( $author_id ) {
			$node['author'] = array(
				'@type' => 'Person',
				'@id'   => get_author_posts_url( $author_id ) . '#author',
				'name'  => get_the_author_meta( 'display_name', $author_id ),
				'url'   => get_author_posts_url( $author_id ),
			);
		}

		$images = self::article_image_urls( (int) get_post_thumbnail_id( $post_id ) );
		if ( $images ) {
			$node['image'] = $images;
		}

		$unit = Taxonomy::get_post_unit( $post_id );
		if ( $unit instanceof \WP_Term ) {
			$node['articleSection'] = $unit->name;
		}

		$location = Post_Meta::get( $post_id, Post_Meta::LOCATION );
		if ( '' !== $location ) {
			$node['contentLocation'] = array(
				'@type' => 'Place',
				'name'  => $location,
			);
		}

		return $node;
	}

	/**
	 * BreadcrumbList node.
	 *
	 * @param array<int, array{name: string, url: string}> $items Trail.
	 * @return array<string, mixed>
	 */
	private static function breadcrumb_node( array $items ): array {
		$elements = array();
		$position = 1;

		foreach ( $items as $item ) {
			$element = array(
				'@type'    => 'ListItem',
				'position' => $position,
				'name'     => $item['name'],
			);
			if ( '' !== $item['url'] ) {
				$element['item'] = $item['url'];
			}
			$elements[] = $element;
			++$position;
		}

		return array(
			'@type'           => 'BreadcrumbList',
			'@id'             => ( Seo::canonical_url() ?: home_url( '/' ) ) . '#breadcrumb',
			'itemListElement' => $elements,
		);
	}
}
