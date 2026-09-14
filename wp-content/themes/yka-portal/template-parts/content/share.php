<?php
/**
 * Share controls.
 *
 * Plain links plus two progressive enhancements: the Web Share API where
 * the browser offers it, and clipboard copy. No third-party SDK is loaded,
 * so no social network can track a reader who never clicks.
 *
 * @package YKA_Portal
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

$yka_post_id = (int) get_the_ID();

if ( ! $yka_post_id || ! function_exists( 'yka_share_links' ) ) {
	return;
}

$yka_links = yka_share_links( $yka_post_id );
$yka_url   = (string) get_permalink( $yka_post_id );
$yka_title = wp_strip_all_tags( get_the_title( $yka_post_id ) );
?>
<section
	class="yka-share yka-prose"
	data-yka-share
	data-url="<?php echo esc_url( $yka_url ); ?>"
	data-title="<?php echo esc_attr( $yka_title ); ?>"
>
	<h2 class="yka-share__title"><?php esc_html_e( 'Bagikan artikel ini', 'yka-portal' ); ?></h2>

	<ul class="yka-share__list">
		<?php foreach ( $yka_links as $yka_link ) : ?>
			<li>
				<a
					class="yka-share__btn"
					href="<?php echo esc_url( $yka_link['url'] ); ?>"
					target="_blank"
					rel="noopener nofollow"
				>
					<?php echo yka_icon( $yka_link['id'], array( 'size' => 17 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static inline SVG. ?>
					<span><?php echo esc_html( $yka_link['label'] ); ?></span>
				</a>
			</li>
		<?php endforeach; ?>

		<li>
			<button type="button" class="yka-share__btn" data-yka-copy-link>
				<?php echo yka_icon( 'link', array( 'size' => 17 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static inline SVG. ?>
				<span><?php esc_html_e( 'Salin tautan', 'yka-portal' ); ?></span>
			</button>
		</li>

		<li hidden data-yka-native-share>
			<button type="button" class="yka-share__btn" data-yka-share-native>
				<?php echo yka_icon( 'share', array( 'size' => 17 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static inline SVG. ?>
				<span><?php esc_html_e( 'Bagikan', 'yka-portal' ); ?></span>
			</button>
		</li>
	</ul>

	<span class="yka-share__status" role="status" aria-live="polite" data-yka-share-status></span>
</section>
