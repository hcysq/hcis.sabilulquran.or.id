<?php
/**
 * The header for our theme
 *
 * This is the template that displays all of the <head> section and everything up until <div id="content">
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package Fitness Elementor
 */
?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>

<meta http-equiv="Content-Type" content="<?php echo esc_attr(get_bloginfo('html_type')); ?>; charset=<?php echo esc_attr(get_bloginfo('charset')); ?>" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.2, user-scalable=yes" />

<?php wp_head(); ?>

</head>

<body <?php body_class(); ?>>

<?php
	if ( function_exists( 'wp_body_open' ) )
	{
		wp_body_open();
	}else{
		do_action('wp_body_open');
	}
?>

<a class="skip-link screen-reader-text" href="#content"><?php esc_html_e( 'Skip to content', 'fitness-elementor' ); ?></a>

<div class="topheader py-2">
	<div class="container">
		<div class="row">
		    <div class="col-lg-3 "></div>
			<div class="col-lg-7 col-md-10 col-sm-9  text-center align-self-center">
				<?php if ( get_theme_mod('fitness_elementor_header_phone_number') ) : ?>
					<a href="tel:<?php echo esc_url( get_theme_mod('fitness_elementor_header_phone_number' ) ); ?>"><span class="me-3"><i class="fas fa-phone me-2"></i><?php echo esc_html( get_theme_mod('fitness_elementor_header_phone_number' ) ); ?></span></a>
				<?php endif; ?>
				<?php if ( get_theme_mod('fitness_elementor_header_location') ) : ?>
					<span class="me-3"><i class="fas fa-map-marker-alt me-2"></i><?php echo esc_html( get_theme_mod('fitness_elementor_header_location' ) ); ?></span>
				<?php endif; ?>
				<?php if ( get_theme_mod('fitness_elementor_header_email') ) : ?>
					<a href="mailto:<?php echo esc_url( get_theme_mod('fitness_elementor_header_email' ) ); ?>"><span ><i class="fas fa-envelope me-2"></i><?php echo esc_html( get_theme_mod('fitness_elementor_header_email' ) ); ?></span></a>
				<?php endif; ?>
			</div>
			<div class="col-lg-2 col-md-2 col-sm-3 align-self-center">
				<?php $fitness_elementor_settings = get_theme_mod( 'fitness_elementor_social_links_settings' ); ?>
				<div class="social-links text-center text-md-end">
					<?php if ( is_array($fitness_elementor_settings) || is_object($fitness_elementor_settings) ){ ?>
				    	<?php foreach( $fitness_elementor_settings as $fitness_elementor_setting ) { ?>
					        <a href="<?php echo esc_url( $fitness_elementor_setting['link_url'] ); ?>" target="_blank">
				            	<i class="<?php echo esc_attr( $fitness_elementor_setting['link_text'] ); ?> me-2"></i>
					        </a>
				    	<?php } ?>
					<?php } ?>
				</div>
			</div>
		</div>
	</div>
</div>

<header id="site-navigation" class="header text-center text-md-start py-3">
	<div class="container-fluid">
		<div class="row">
			<div class="col-lg-3 col-md-3 col-sm-3 align-self-center position-relative">
				<div class="logo text-center mb-3 mb-md-0">
		    		<div class="logo-image">
		    			<?php the_custom_logo(); ?>
			    	</div>
					<div class="logo-content">
						<?php
							if ( get_theme_mod('fitness_elementor_display_header_title', true) == true ) :
								echo '<a href="' . esc_url(home_url('/')) . '" title="' . esc_attr(get_bloginfo('name')) . '">';
								echo esc_attr(get_bloginfo('name'));
								echo '</a>';
							endif;
							if ( get_theme_mod('fitness_elementor_display_header_text', false) == true ) :
								echo '<span>'. esc_attr(get_bloginfo('description')) . '</span>';
							endif;
						?>
					</div>
				</div>
		   	</div>
			<div class="col-lg-7 col-md-7 col-sm-6 align-self-center">
				<button class="menu-toggle my-3 py-2 px-3" aria-controls="top-menu" aria-expanded="false" type="button">
					<span aria-hidden="true"><?php esc_html_e( 'Menu', 'fitness-elementor' ); ?></span>
				</button>
				<nav id="main-menu" class="close-panal">
					<?php
						wp_nav_menu( array(
							'theme_location' => 'main-menu',
							'container' => 'false'
						));
					?>
					<button class="close-menu my-2 p-2" type="button">
						<span aria-hidden="true"><i class="fa fa-times"></i></span>
					</button>
				</nav>
			</div>
			<div class="col-lg-2 col-md-2 col-sm-3 align-self-center">
				<?php if ( get_theme_mod('fitness_elementor_search_enable', 'on' ) == true ) : ?>
					<div class="search-cont py-2 me-4 d-inline-block">
						<button type="button" class="search-cont-button"><i class="fas fa-search"></i></button>
					</div>
					<div class="outer-search">
						<div class="inner-search">
							<?php get_search_form(); ?>
						</div>
						<button type="button" class="closepop search-cont-button-close" >X</button>
					</div>
				<?php endif; ?>
				<?php if ( get_theme_mod('fitness_elementor_cart_box_enable', 'on' ) == true ) : ?>
					<?php if ( class_exists( 'woocommerce' ) ) {?>
						<a class="cart-customlocation" href="<?php if(function_exists('wc_get_cart_url')){ echo esc_url(wc_get_cart_url()); } ?>" title="<?php esc_attr_e( 'View Shopping Cart','fitness-elementor' ); ?>"><i class="fas fa-shopping-cart"></i></a>
					<?php }?>
				<?php endif; ?>
			</div>
		</div>
	</div>
</header>