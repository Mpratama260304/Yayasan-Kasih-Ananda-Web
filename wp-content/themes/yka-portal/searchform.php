<?php
/**
 * Search form.
 *
 * @package YKA_Portal
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

$yka_field_id = wp_unique_id( 'yka-search-' );
?>
<form role="search" method="get" class="yka-searchform" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label class="screen-reader-text" for="<?php echo esc_attr( $yka_field_id ); ?>">
		<?php esc_html_e( 'Cari berita dan halaman', 'yka-portal' ); ?>
	</label>
	<input
		type="search"
		id="<?php echo esc_attr( $yka_field_id ); ?>"
		class="yka-searchform__input"
		name="s"
		value="<?php echo esc_attr( get_search_query() ); ?>"
		placeholder="<?php esc_attr_e( 'Cari kegiatan, pengumuman, prestasi…', 'yka-portal' ); ?>"
		autocomplete="off"
	/>
	<button type="submit" class="yka-btn">
		<?php esc_html_e( 'Cari', 'yka-portal' ); ?>
	</button>
</form>
