<?php

class Whizzie {

	public function __construct() {
		$this->init();
	}

	public function init()
	{
	
	}

	public static function fitness_elementor_setup_widgets(){

	set_theme_mod( 'fitness_elementor_header_phone_number', '+1234567890' );
	set_theme_mod( 'fitness_elementor_header_location', '45 Street Area , House Newyork City' );
	set_theme_mod( 'fitness_elementor_header_email', 'Fitnessgym@gmail.com' );
	set_theme_mod('fitness_elementor_social_links_settings', array(
		array(
			"link_text" => "fab fa-instagram",
			"link_url" => "www.instagram.com"
		),
		array(
			"link_text" => "fab fa-twitter",
			"link_url" => "www.twitter.com"
		),
		array(
			"link_text" => "fab fa-youtube",
			"link_url" => "www.youtube.com"
		),
		array(
			"link_text" => "fab fa-linkedin-in",
			"link_url" => "www.linkedin.com"
		)
	));

	$fitness_elementor_product_image_gallery = array();
	$fitness_elementor_product_ids = array();

	$fitness_elementor_product_category= array(
		'Product Category'       => array(
			'Product Title 01',
			'Product Title 02',
			'Product Title 03',
			'Product Title 04',
		),
	);

	$fitness_elementor_k = 1;
	foreach ( $fitness_elementor_product_category as $fitness_elementor_product_cats => $fitness_elementor_products_name ) { 
	// Insert porduct cats Start
	$content = 'This is sample product category';
	$fitness_elementor_parent_category	=	wp_insert_term(
	$fitness_elementor_product_cats, // the term
	'product_cat', // the taxonomy
		array(
		'description'=> $content,
		'slug' => str_replace( ' ', '-', $fitness_elementor_product_cats)
		)
	);

// -------------- create subcategory END -----------------

	$fitness_elementor_n=1;
	// create Product START
	foreach ( $fitness_elementor_products_name as $key => $fitness_elementor_product_title ) {
	$content = '
		<div class="main_content">
		<p>Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book.</p>
		</div>';

	// Create post object
	$fitness_elementor_my_post = array(
		'post_title'    => wp_strip_all_tags( $fitness_elementor_product_title ),
		'post_content'  => $content,
		'post_status'   => 'publish',
		'post_type'     => 'product',
		'post_category' => [$fitness_elementor_parent_category['term_id']]
	);

	// Insert the post into the database

	$fitness_elementor_uqpost_id = wp_insert_post($fitness_elementor_my_post);
	wp_set_object_terms( $fitness_elementor_uqpost_id, str_replace( ' ', '-', $fitness_elementor_product_cats), 'product_cat', true );

	$fitness_elementor_product_price = array('350','350','350','350');
	$fitness_elementor_product_regular_price = array('400','400','400','400');
	$fitness_elementor_product_sale_price = array('350','350','350','350');
	
	update_post_meta( $fitness_elementor_uqpost_id, '_regular_price', $fitness_elementor_product_regular_price[$fitness_elementor_n-1] );
	update_post_meta( $fitness_elementor_uqpost_id, '_price', $fitness_elementor_product_price[$fitness_elementor_n-1] );
	update_post_meta( $fitness_elementor_uqpost_id, '_sale_price', $fitness_elementor_product_sale_price[$fitness_elementor_n-1] );
	array_push( $fitness_elementor_product_ids,  $fitness_elementor_uqpost_id );

	// Now replace meta w/ new updated value array
	$fitness_elementor_image_url = get_template_directory_uri().'/assets/images/product/'.$fitness_elementor_product_cats.'/' . str_replace(' ', '_', strtolower($fitness_elementor_product_title)).'.png';
	$fitness_elementor_image_name  = $fitness_elementor_product_title.'.png';
	$fitness_elementor_upload_dir = wp_upload_dir();
	// Set upload folder
	$fitness_elementor_image_data = file_get_contents(esc_url($fitness_elementor_image_url));
	// Get image data
	$unique_file_name = wp_unique_filename($fitness_elementor_upload_dir['path'], $fitness_elementor_image_name);
	// Generate unique name
	$fitness_elementor_filename = basename($unique_file_name);
	// Create image file name
	// Check folder permission and define file location
	if (wp_mkdir_p($fitness_elementor_upload_dir['path'])) {
	$fitness_elementor_file = $fitness_elementor_upload_dir['path'].'/'.$fitness_elementor_filename;
	} else {
	$fitness_elementor_file = $fitness_elementor_upload_dir['basedir'].'/'.$fitness_elementor_filename;
	}
	
	file_put_contents($fitness_elementor_file, $fitness_elementor_image_data);
	// Check image file type
	$wp_filetype = wp_check_filetype($fitness_elementor_filename, null);
	// Set attachment data
	$attachment = array(
	'post_mime_type' => $wp_filetype['type'],
	'post_title'     => sanitize_file_name($fitness_elementor_filename),
	'post_type'      => 'product',
	'post_status'    => 'inherit',
	);

	// Create the attachment
	$fitness_elementor_attach_id = wp_insert_attachment($attachment, $fitness_elementor_file, $fitness_elementor_uqpost_id);

	// Define attachment metadata
	$attach_data = wp_generate_attachment_metadata($fitness_elementor_attach_id, $fitness_elementor_file);

	// Assign metadata to attachment
	wp_update_attachment_metadata($fitness_elementor_attach_id, $attach_data);
	if ( count( $fitness_elementor_product_image_gallery ) < 3 ) {
		array_push( $fitness_elementor_product_image_gallery, $fitness_elementor_attach_id );
	}
	// // And finally assign featured image to post
	set_post_thumbnail($fitness_elementor_uqpost_id, $fitness_elementor_attach_id);
	++$fitness_elementor_n;
	}
	// Create product END
	++$fitness_elementor_k;
	}
	// Add Gallery in first simple product and second variable product START
	$fitness_elementor_product_image_gallery = implode( ',', $fitness_elementor_product_image_gallery );
	foreach ( $fitness_elementor_product_ids as $fitness_elementor_product_id ) {
	update_post_meta( $fitness_elementor_product_id, 'fitness_elementor_product_image_gallery', $fitness_elementor_product_image_gallery );
	}
}

}
 