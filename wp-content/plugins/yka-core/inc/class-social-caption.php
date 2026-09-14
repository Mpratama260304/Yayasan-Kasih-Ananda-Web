<?php
/**
 * Social caption helper for editors.
 *
 * @package YKA\Core
 */

declare( strict_types=1 );

namespace YKA\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Builds a ready-to-paste caption for WhatsApp, Instagram or Facebook.
 *
 * Nothing is posted automatically and no external API credentials are
 * required: the editor copies the text and pastes it wherever it is needed.
 */
final class Social_Caption {

	/**
	 * Hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_box' ) );
	}

	/**
	 * Registers the editor panel.
	 *
	 * @return void
	 */
	public static function add_meta_box(): void {
		add_meta_box(
			'yka-social-caption',
			__( 'Teks Media Sosial', 'yka-core' ),
			array( __CLASS__, 'render' ),
			'post',
			'side',
			'low',
			array( '__block_editor_compatible_meta_box' => true )
		);
	}

	/**
	 * Composes the caption for a post.
	 *
	 * @param int $post_id Post id.
	 * @return string
	 */
	public static function build( int $post_id ): string {
		$post = get_post( $post_id );
		if ( ! $post instanceof \WP_Post ) {
			return '';
		}

		$title = trim( wp_strip_all_tags( get_the_title( $post ) ) );

		$summary = trim( Post_Meta::get( $post_id, Post_Meta::SOCIAL_TEXT ) );
		if ( '' === $summary ) {
			$summary = trim( wp_strip_all_tags( (string) $post->post_excerpt ) );
		}
		if ( '' === $summary ) {
			$summary = trim( wp_html_excerpt( wp_strip_all_tags( (string) $post->post_content ), 220, '…' ) );
		}

		$url = 'publish' === $post->post_status
			? (string) get_permalink( $post )
			: __( '[tautan tersedia setelah artikel terbit]', 'yka-core' );

		$parts = array( $title );

		if ( '' !== $summary ) {
			$parts[] = '';
			$parts[] = $summary;
		}

		$parts[] = '';
		$parts[] = __( 'Baca selengkapnya:', 'yka-core' );
		$parts[] = $url;

		$hashtags = self::hashtags( $post_id );
		if ( '' !== $hashtags ) {
			$parts[] = '';
			$parts[] = $hashtags;
		}

		/**
		 * Filters the generated social caption.
		 *
		 * @param string   $caption Caption text.
		 * @param \WP_Post $post    Post object.
		 */
		return (string) apply_filters( 'yka_social_caption', implode( "\n", $parts ), $post );
	}

	/**
	 * Builds the hashtag line: foundation tag, unit tag, then editor extras.
	 *
	 * @param int $post_id Post id.
	 * @return string
	 */
	public static function hashtags( int $post_id ): string {
		$tags = array( '#YayasanKasihAnanda' );

		$unit = Taxonomy::get_post_unit( $post_id );
		if ( $unit instanceof \WP_Term && Taxonomy::UNIT_YAYASAN !== $unit->slug ) {
			$slug = preg_replace( '/[^A-Za-z0-9]+/', '', ucwords( $unit->name ) );
			if ( $slug ) {
				$tags[] = '#' . $slug;
			}
		}

		$extra = Post_Meta::get( $post_id, Post_Meta::SOCIAL_TAGS );
		foreach ( preg_split( '/\s+/', $extra ) ?: array() as $item ) {
			$item = trim( $item );
			if ( '' === $item ) {
				continue;
			}
			$item = '#' . ltrim( $item, '#' );
			if ( ! in_array( $item, $tags, true ) ) {
				$tags[] = $item;
			}
		}

		return implode( ' ', $tags );
	}

	/**
	 * Renders the copy-to-clipboard panel.
	 *
	 * @param \WP_Post $post Current post.
	 * @return void
	 */
	public static function render( \WP_Post $post ): void {
		$caption = self::build( $post->ID );
		?>
		<div class="yka-social-caption">
			<label class="screen-reader-text" for="yka-social-caption-text">
				<?php esc_html_e( 'Teks media sosial siap salin', 'yka-core' ); ?>
			</label>
			<textarea id="yka-social-caption-text" rows="9" readonly class="yka-social-caption__text"><?php echo esc_textarea( $caption ); ?></textarea>
			<p>
				<button type="button" class="button button-secondary" data-yka-copy="#yka-social-caption-text">
					<?php esc_html_e( 'Salin teks', 'yka-core' ); ?>
				</button>
				<span class="yka-social-caption__status" role="status" aria-live="polite"></span>
			</p>
			<p class="description">
				<?php esc_html_e( 'Teks diperbarui setiap halaman dimuat ulang. Ubah ringkasan atau tagar pada panel Detail Dokumentasi bila perlu.', 'yka-core' ); ?>
			</p>
		</div>
		<?php
	}
}
