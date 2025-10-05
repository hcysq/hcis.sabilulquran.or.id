<?php
	
require get_template_directory() . '/includes/tgm/class-tgm-plugin-activation.php';

/**
 * Recommended plugins.
 */
function fitness_elementor_register_recommended_plugins() {
	$plugins = array(
		array(
			'name'             => __( 'Kirki Customizer Framework', 'fitness-elementor' ),
			'slug'             => 'kirki',
			'required'         => false,
			'force_activation' => false,
		),
		array(
			'name'             => __( 'WPElemento Importer', 'fitness-elementor' ),
			'slug'             => 'wpelemento-importer',
			'required'         => false,
			'force_activation' => false,
		),
		array(
			'name'             => __( 'YITH WooCommerce Quick View', 'fitness-elementor' ),
			'slug'             => 'yith-woocommerce-quick-view',
			'required'         => false,
			'force_activation' => false,
		),
	);
	$config = array();
	fitness_elementor_tgmpa( $plugins, $config );
}
add_action( 'tgmpa_register', 'fitness_elementor_register_recommended_plugins' );