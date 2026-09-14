<?php
/**
 * Site header.
 *
 * @package YKA_Portal
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

$yka_org_name  = function_exists( 'yka_setting' ) ? (string) yka_setting( 'org_name', get_bloginfo( 'name' ) ) : (string) get_bloginfo( 'name' );
$yka_phone     = function_exists( 'yka_setting' ) ? (string) yka_setting( 'phone' ) : '';
$yka_email     = function_exists( 'yka_setting' ) ? (string) yka_setting( 'email' ) : '';
$yka_spmb_page = get_page_by_path( 'spmb' ) ?: get_page_by_path( 'ppdb' );
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<link rel="profile" href="https://gmpg.org/xfn/11" />
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="yka-skip-link" href="#yka-main"><?php esc_html_e( 'Lompat ke konten utama', 'yka-portal' ); ?></a>

<div class="yka-topbar">
	<div class="yka-container yka-container--wide yka-topbar__inner">
		<span class="yka-topbar__identity"><?php echo esc_html( $yka_org_name ); ?></span>

		<ul class="yka-topbar__links">
			<?php if ( '' !== $yka_phone ) : ?>
				<li><a href="tel:<?php echo esc_attr( (string) preg_replace( '/[^0-9+]/', '', $yka_phone ) ); ?>"><?php echo esc_html( $yka_phone ); ?></a></li>
			<?php endif; ?>
			<?php if ( '' !== $yka_email ) : ?>
				<li><a href="mailto:<?php echo esc_attr( $yka_email ); ?>"><?php echo esc_html( $yka_email ); ?></a></li>
			<?php endif; ?>
			<?php
			if ( has_nav_menu( 'utility' ) ) {
				yka_nav_menu(
					'utility',
					array(
						'depth'      => 1,
						'items_wrap' => '%3$s',
					)
				);
			}
			?>
		</ul>
	</div>
</div>

<header class="yka-header" role="banner">
	<div class="yka-container yka-container--wide yka-header__inner">

		<?php if ( has_custom_logo() ) : ?>
			<div class="yka-brand">
				<span class="yka-brand__logo"><?php the_custom_logo(); ?></span>
			</div>
		<?php else : ?>
			<a class="yka-brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
				<span class="yka-brand__text">
					<span class="yka-brand__name"><?php echo esc_html( $yka_org_name ); ?></span>
					<span class="yka-brand__sub"><?php esc_html_e( 'Portal Berita &amp; Dokumentasi', 'yka-portal' ); ?></span>
				</span>
			</a>
		<?php endif; ?>

		<nav class="yka-nav" aria-label="<?php esc_attr_e( 'Navigasi utama', 'yka-portal' ); ?>">
			<?php
			yka_nav_menu(
				'primary',
				array(
					'menu_class' => 'yka-nav__list',
					'menu_id'    => 'yka-primary-menu',
				)
			);
			?>
		</nav>

		<div class="yka-header__actions">
			<?php if ( $yka_spmb_page instanceof WP_Post ) : ?>
				<a class="yka-btn yka-btn--small yka-header-cta" href="<?php echo esc_url( (string) get_permalink( $yka_spmb_page ) ); ?>">
					<?php echo esc_html( get_the_title( $yka_spmb_page ) ); ?>
				</a>
			<?php endif; ?>

			<button
				type="button"
				class="yka-iconbtn yka-search-toggle"
				aria-expanded="false"
				aria-controls="yka-search-panel"
			>
				<?php echo yka_icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static inline SVG. ?>
				<span class="screen-reader-text"><?php esc_html_e( 'Buka pencarian', 'yka-portal' ); ?></span>
			</button>

			<button
				type="button"
				class="yka-iconbtn yka-nav-toggle"
				aria-expanded="false"
				aria-controls="yka-mobile-nav"
			>
				<span class="yka-nav-toggle__icon" aria-hidden="true"><?php echo yka_icon( 'menu' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static inline SVG. ?></span>
				<span class="screen-reader-text"><?php esc_html_e( 'Buka menu navigasi', 'yka-portal' ); ?></span>
			</button>
		</div>
	</div>

	<div class="yka-searchpanel" id="yka-search-panel" hidden>
		<div class="yka-container yka-container--wide yka-searchpanel__inner">
			<?php get_search_form(); ?>
		</div>
	</div>

	<div class="yka-mobile-nav" id="yka-mobile-nav" hidden>
		<div class="yka-container yka-container--wide">
			<nav aria-label="<?php esc_attr_e( 'Navigasi utama (ponsel)', 'yka-portal' ); ?>">
				<?php
				yka_nav_menu(
					'primary',
					array(
						'menu_class' => 'yka-mobile-nav__list',
						'menu_id'    => 'yka-mobile-menu',
					)
				);
				?>
			</nav>

			<?php if ( '' !== $yka_phone || '' !== $yka_email ) : ?>
				<div class="yka-mobile-nav__utility">
					<?php if ( '' !== $yka_phone ) : ?>
						<p><a href="tel:<?php echo esc_attr( (string) preg_replace( '/[^0-9+]/', '', $yka_phone ) ); ?>"><?php echo esc_html( $yka_phone ); ?></a></p>
					<?php endif; ?>
					<?php if ( '' !== $yka_email ) : ?>
						<p><a href="mailto:<?php echo esc_attr( $yka_email ); ?>"><?php echo esc_html( $yka_email ); ?></a></p>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
</header>

<main class="yka-main" id="yka-main">
