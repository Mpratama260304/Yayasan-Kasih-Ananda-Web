<?php
/**
 * Editorial image sizes and media hygiene.
 *
 * @package YKA\Core
 */

declare( strict_types=1 );

namespace YKA\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the crops the portal and its structured data rely on.
 *
 * These live in the plugin rather than the theme so schema keeps
 * resolving the right derivatives if the visual theme is ever replaced.
 */
final class Images {

	public const SIZE_16_9   = 'yka-16x9';
	public const SIZE_4_3    = 'yka-4x3';
	public const SIZE_1_1    = 'yka-1x1';
	public const SIZE_SOCIAL = 'yka-social';
	public const SIZE_WIDE   = 'yka-wide';

	/**
	 * Hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'after_setup_theme', array( __CLASS__, 'register_sizes' ) );
		add_filter( 'image_size_names_choose', array( __CLASS__, 'size_names' ) );
		add_filter( 'big_image_size_threshold', array( __CLASS__, 'big_image_threshold' ) );
		add_filter( 'jpeg_quality', array( __CLASS__, 'jpeg_quality' ) );
		add_filter( 'wp_editor_set_quality', array( __CLASS__, 'jpeg_quality' ) );
		add_action( 'add_attachment', array( __CLASS__, 'prefill_alt_from_title' ) );
	}

	/**
	 * Registers image sizes.
	 *
	 * Crops are generous on the long edge so faces are not cut aggressively.
	 *
	 * @return void
	 */
	public static function register_sizes(): void {
		add_image_size( self::SIZE_WIDE, 1920, 900, true );
		add_image_size( self::SIZE_16_9, 1280, 720, true );
		add_image_size( self::SIZE_4_3, 1200, 900, true );
		add_image_size( self::SIZE_1_1, 1080, 1080, true );
		add_image_size( self::SIZE_SOCIAL, 1200, 630, true );
	}

	/**
	 * Exposes useful sizes in the block editor image size picker.
	 *
	 * @param array<string, string> $sizes Registered choices.
	 * @return array<string, string>
	 */
	public static function size_names( array $sizes ): array {
		return array_merge(
			$sizes,
			array(
				self::SIZE_16_9 => __( 'Editorial 16:9', 'yka-core' ),
				self::SIZE_4_3  => __( 'Editorial 4:3', 'yka-core' ),
				self::SIZE_1_1  => __( 'Persegi 1:1', 'yka-core' ),
			)
		);
	}

	/**
	 * Camera originals are huge; scale them down but keep plenty of detail.
	 *
	 * @param int $threshold Default threshold.
	 * @return int
	 */
	public static function big_image_threshold( $threshold ): int {
		unset( $threshold );
		return 2880;
	}

	/**
	 * Slightly higher than the WordPress default: photographs of people are
	 * the main content of this site.
	 *
	 * @param int $quality Default quality.
	 * @return int
	 */
	public static function jpeg_quality( $quality ): int {
		unset( $quality );
		return 84;
	}

	/**
	 * Gives freshly uploaded images a starting alt text from the filename.
	 *
	 * Editors are still expected to rewrite it — this only prevents images
	 * from being published with no alt attribute at all.
	 *
	 * @param int $attachment_id Attachment id.
	 * @return void
	 */
	public static function prefill_alt_from_title( int $attachment_id ): void {
		if ( ! wp_attachment_is_image( $attachment_id ) ) {
			return;
		}
		if ( '' !== (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) ) {
			return;
		}

		$title = get_the_title( $attachment_id );
		// Filenames like IMG_2481 or DSC00312 describe nothing useful.
		if ( '' === $title || preg_match( '/^(img|dsc|dscn|p|pxl|screenshot|photo|image)[\s_-]*\d+$/i', $title ) ) {
			return;
		}

		update_post_meta( $attachment_id, '_wp_attachment_image_alt', sanitize_text_field( $title ) );
	}

	/**
	 * Best available URL for a given crop, falling back sensibly.
	 *
	 * @param int    $attachment_id Attachment id.
	 * @param string $size          Registered size name.
	 * @return string
	 */
	public static function url( int $attachment_id, string $size ): string {
		if ( ! $attachment_id ) {
			return '';
		}
		$src = wp_get_attachment_image_src( $attachment_id, $size );
		if ( is_array( $src ) && ! empty( $src[0] ) ) {
			return (string) $src[0];
		}
		return (string) wp_get_attachment_url( $attachment_id );
	}
}
