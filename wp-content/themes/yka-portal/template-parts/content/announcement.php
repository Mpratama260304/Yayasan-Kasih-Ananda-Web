<?php
/**
 * A single announcement row.
 *
 * Expected args:
 *   post WP_Post
 *
 * @package YKA_Portal
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

$yka_post = ( $args['post'] ?? null ) instanceof WP_Post ? $args['post'] : get_post();

if ( ! $yka_post instanceof WP_Post ) {
	return;
}

$yka_activity = function_exists( 'yka_activity_date' ) ? yka_activity_date( $yka_post->ID ) : '';
$yka_excerpt  = yka_excerpt( $yka_post, 22 );
?>
<article class="yka-announce">
	<p class="yka-announce__date">
		<time datetime="<?php echo esc_attr( (string) get_the_date( DATE_W3C, $yka_post ) ); ?>">
			<?php echo esc_html( (string) get_the_date( 'j M Y', $yka_post ) ); ?>
		</time>
	</p>

	<div>
		<h3 class="yka-announce__title">
			<a href="<?php echo esc_url( (string) get_permalink( $yka_post ) ); ?>"><?php echo esc_html( get_the_title( $yka_post ) ); ?></a>
		</h3>

		<?php if ( '' !== $yka_excerpt ) : ?>
			<p class="yka-announce__excerpt"><?php echo esc_html( $yka_excerpt ); ?></p>
		<?php endif; ?>

		<?php if ( '' !== $yka_activity ) : ?>
			<p class="yka-announce__excerpt">
				<?php
				printf(
					/* translators: %s: date the activity takes place. */
					esc_html__( 'Tanggal kegiatan: %s', 'yka-portal' ),
					esc_html( $yka_activity )
				);
				?>
			</p>
		<?php endif; ?>
	</div>
</article>
