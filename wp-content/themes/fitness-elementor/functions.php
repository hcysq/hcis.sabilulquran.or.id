<?php
/**
 * Fitness Elementor functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package Fitness Elementor
 */

/* Enqueue script and styles */

function fitness_elementor_enqueue_google_fonts() {

	require_once get_theme_file_path( 'includes/wptt-webfont-loader.php' );

	wp_enqueue_style(
		'Epilogue',
		fitness_elementor_wptt_get_webfont_url( 'https://fonts.googleapis.com/css2?family=Epilogue:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap' ),
		array(),
		'1.0'
	);
	wp_enqueue_style(
		'Raleway',
		fitness_elementor_wptt_get_webfont_url( 'https://fonts.googleapis.com/css2?family=Raleway:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap' ),
		array(),
		'1.0'
	);
}
add_action( 'wp_enqueue_scripts', 'fitness_elementor_enqueue_google_fonts' );

if (!function_exists('fitness_elementor_enqueue_scripts')) {

	function fitness_elementor_enqueue_scripts() {

		wp_enqueue_style(
			'bootstrap-css',
			get_template_directory_uri() . '/assets/css/bootstrap.css',
			array(),'4.5.0'
		);

		wp_enqueue_style(
			'fontawesome-css',
			get_template_directory_uri() . '/assets/css/fontawesome-all.css',
			array(),'4.5.0'
		);

		wp_enqueue_style('fitness-elementor-style', get_stylesheet_uri(), array() );

		wp_enqueue_style(
			'fitness-elementor-responsive-css',
			get_template_directory_uri() . '/assets/css/responsive.css',
			array(),'2.3.4'
		);

		wp_enqueue_script(
			'fitness-elementor-navigation',
			get_template_directory_uri() . '/assets/js/navigation.js',
			FALSE,
			'1.0',
			TRUE
		);

		wp_enqueue_script(
			'fitness-elementor-script',
			get_template_directory_uri() . '/assets/js/script.js',
			array('jquery'),
			'1.0',
			TRUE
		);

		wp_enqueue_style( 'animate-css', esc_url(get_template_directory_uri()).'/assets/css/animate.css' );

		wp_enqueue_script( 'wow-js', esc_url(get_template_directory_uri()) . '/assets/js/wow.js', array('jquery') );

		require get_parent_theme_file_path( '/includes/color-setting/custom-color-control.php' );
		wp_add_inline_style( 'fitness-elementor-style',$fitness_elementor_theme_custom_setting_css );
		wp_style_add_data('fitness-elementor-style', 'rtl', 'replace');

		if ( is_singular() ) wp_enqueue_script( 'comment-reply' );

		$css = '';

		if ( get_header_image() ) :

			$css .=  '
				.header-image-box{
					background-image: url('.esc_url(get_header_image()).') !important;
					-webkit-background-size: cover !important;
					-moz-background-size: cover !important;
					-o-background-size: cover !important;
					background-size: cover !important;
					height: 550px;
				    display: flex;
				    align-items: center;
				}';

		endif;

		wp_add_inline_style( 'fitness-elementor-style', $css );

	}

	add_action( 'wp_enqueue_scripts', 'fitness_elementor_enqueue_scripts' );

}

/* Setup theme */

if (!function_exists('fitness_elementor_after_setup_theme')) {

	function fitness_elementor_after_setup_theme() {

		load_theme_textdomain( 'fitness-elementor', get_template_directory() . '/languages' );
		if ( ! isset( $content_width ) ) $content_width = 900;

		register_nav_menus( array(
			'main-menu' => esc_html__( 'Main menu', 'fitness-elementor' ),
		));

		add_theme_support( 'responsive-embeds' );
		add_theme_support( 'woocommerce' );
		add_theme_support( 'align-wide' );
		add_theme_support('title-tag');
		add_theme_support('automatic-feed-links');
		add_theme_support( 'wp-block-styles' );
		add_theme_support('post-thumbnails');
		add_theme_support( 'custom-background', array(
		  'default-color' => 'f3f3f3'
		));

		add_theme_support( 'custom-logo', array(
			'height'      => 120,
			'width'       => 240,
			'flex-height' => true,
		) );

		add_theme_support( 'custom-header', array(
			'default-image' => get_parent_theme_file_uri( '/assets/images/default-header-image.png' ),
			'width' => 1920,
			'flex-width' => true,
			'height' => 550,
			'flex-height' => true,
			'header-text' => false,
		));

		register_default_headers( array(
			'default-image' => array(
				'url'           => '%s/assets/images/default-header-image.png',
				'thumbnail_url' => '%s/assets/images/default-header-image.png',
				'description'   => __( 'Default Header Image', 'fitness-elementor' ),
			),
		) );

		add_theme_support( 'html5', array(
			'search-form',
			'comment-form',
			'comment-list',
			'gallery',
			'caption',
		) );

		add_editor_style( array( '/assets/css/editor-style.css' ) );

		global $pagenow;
		
		if (is_admin() && ('themes.php' == $pagenow) && isset( $_GET['activated'] )) {
			add_action('admin_notices', 'fitness_elementor_activation_notice');
		}

	}

	add_action( 'after_setup_theme', 'fitness_elementor_after_setup_theme', 999 );

}

function fitness_elementor_activation_notice() {
	echo '<div class="notice notice-info wpele-activation-notice is-dismissible">';
		echo '<div class="notice-body">';
			echo '<div class="notice-content">';
				echo '<h2>'. esc_html__( 'Welcome to WPElemento', 'fitness-elementor' ) .'</h2>';
				echo '<p>'. esc_html__( 'Thank you for choosing Fitness Elementor theme .To setup the theme, please visit the get started page.', 'fitness-elementor' ) .'</p>';
				echo '<span><a href="'. esc_url( admin_url( 'themes.php?page=fitness_elementor_about' ) ) .'" class="button button-notice">'. esc_html__( 'GET STARTED', 'fitness-elementor' ) .'</a></span>';
				echo '<span><a href="'. esc_url( FITNESS_ELEMENTOR_BUY_NOW ) .'" class="button button-notice" target="_blank" >'. esc_html__( 'BUY NOW', 'fitness-elementor' ) .'</a></span>';
				echo '<span><a href="'. esc_url( FITNESS_ELEMENTOR_LIVE_DEMO ) .'" class="button button-notice" target="_blank" >'. esc_html__( 'DEMO', 'fitness-elementor' ) .'</a></span>';
			echo '</div>';
			echo '<div class="notice-icon">';
				echo '<img src="'.esc_url(get_template_directory_uri()).'/includes/getstart/images/get-logo.png ">';
			echo '</div>';
		echo '</div>';
	echo '</div>';
}

/* Get post comments */

if (!function_exists('fitness_elementor_comment')) :
    /**
     * Template for comments and pingbacks.
     *
     * Used as a callback by wp_list_comments() for displaying the comments.
     */
    function fitness_elementor_comment($comment, $args, $depth){

        if ('pingback' == $comment->comment_type || 'trackback' == $comment->comment_type) : ?>

            <li id="comment-<?php comment_ID(); ?>" <?php comment_class('media'); ?>>
            <div class="comment-body">
                <?php esc_html_e('Pingback:', 'fitness-elementor');
                comment_author_link(); ?><?php edit_comment_link(__('Edit', 'fitness-elementor'), '<span class="edit-link">', '</span>'); ?>
            </div>

        <?php else : ?>

        <li id="comment-<?php comment_ID(); ?>" <?php comment_class(empty($args['has_children']) ? '' : 'parent'); ?>>
            <article id="div-comment-<?php comment_ID(); ?>" class="comment-body media mb-4">
                <a class="pull-left" href="#">
                    <?php if (0 != $args['avatar_size']) echo get_avatar($comment, $args['avatar_size']); ?>
                </a>
                <div class="media-body">
                    <div class="media-body-wrap card">
                        <div class="card-header">
                            <h5 class="mt-0"><?php /* translators: %s: author */ printf('<cite class="fn">%s</cite>', get_comment_author_link() ); ?></h5>
                            <div class="comment-meta">
                                <a href="<?php echo esc_url(get_comment_link($comment->comment_ID)); ?>">
                                    <time datetime="<?php comment_time('c'); ?>">
                                        <?php /* translators: %s: Date */ printf( esc_html__('%1$s at %2$s','fitness-elementor'), esc_html( get_comment_date() ), esc_html( get_comment_time() ) ); ?>
                                    </time>
                                </a>
                                <?php edit_comment_link( __( 'Edit', 'fitness-elementor' ), '<span class="edit-link">', '</span>' ); ?>
                            </div>
                        </div>

                        <?php if ('0' == $comment->comment_approved) : ?>
                            <p class="comment-awaiting-moderation"><?php esc_html_e('Your comment is awaiting moderation.', 'fitness-elementor'); ?></p>
                        <?php endif; ?>

                        <div class="comment-content card-block">
                            <?php comment_text(); ?>
                        </div>

                        <?php comment_reply_link(
                            array_merge(
                                $args, array(
                                    'add_below' => 'div-comment',
                                    'depth' => $depth,
                                    'max_depth' => $args['max_depth'],
                                    'before' => '<footer class="reply comment-reply card-footer">',
                                    'after' => '</footer><!-- .reply -->'
                                )
                            )
                        ); ?>
                    </div>
                </div>
            </article>

            <?php
        endif;
    }
endif; // ends check for fitness_elementor_comment()

if (!function_exists('fitness_elementor_widgets_init')) {

	function fitness_elementor_widgets_init() {

		register_sidebar(array(

			'name' => esc_html__('Sidebar','fitness-elementor'),
			'id'   => 'fitness-elementor-sidebar',
			'description'   => esc_html__('This sidebar will be shown next to the content.', 'fitness-elementor'),
			'before_widget' => '<div id="%1$s" class="sidebar-widget %2$s">',
			'after_widget'  => '</div>',
			'before_title'  => '<h4 class="title">',
			'after_title'   => '</h4>'

		));

		register_sidebar(array(

			'name' => esc_html__('Page Sidebar','fitness-elementor'),
			'id'   => 'sidebar-2',
			'description'   => esc_html__('This sidebar will be shown on pages.', 'fitness-elementor'),
			'before_widget' => '<div id="%1$s" class="sidebar-widget %2$s">',
			'after_widget'  => '</div>',
			'before_title'  => '<h4 class="title">',
			'after_title'   => '</h4>'

		));

		register_sidebar(array(

			'name' => esc_html__('Sidebar three','fitness-elementor'),
			'id'   => 'sidebar-3',
			'description'   => esc_html__('This sidebar will be shown on blog pages.', 'fitness-elementor'),
			'before_widget' => '<div id="%1$s" class="sidebar-widget %2$s">',
			'after_widget'  => '</div>',
			'before_title'  => '<h4 class="title">',
			'after_title'   => '</h4>'

		));
		
		register_sidebar(array(

			'name' => esc_html__('Footer sidebar 1','fitness-elementor'),
			'id'   => 'footer1-sidebar',
			'description'   => esc_html__('It appears in the footer 1.', 'fitness-elementor'),
			'before_widget' => '<aside id="%1$s" class="%2$s">',
			'after_widget'  => '</aside>',
			'before_title'  => '<h4 class="title">',
			'after_title'   => '</h4>'

		));

		register_sidebar(array(

			'name' => esc_html__('Footer sidebar 2','fitness-elementor'),
			'id'   => 'footer2-sidebar',
			'description'   => esc_html__('It appears in the footer 2.', 'fitness-elementor'),
			'before_widget' => '<aside id="%1$s" class="%2$s">',
			'after_widget'  => '</aside>',
			'before_title'  => '<h4 class="title">',
			'after_title'   => '</h4>'

		));

		register_sidebar(array(

			'name' => esc_html__('Footer sidebar 3','fitness-elementor'),
			'id'   => 'footer3-sidebar',
			'description'   => esc_html__('It appears in the footer 3.', 'fitness-elementor'),
			'before_widget' => '<aside id="%1$s" class="%2$s">',
			'after_widget'  => '</aside>',
			'before_title'  => '<h4 class="title">',
			'after_title'   => '</h4>'

		));

		register_sidebar(array(

			'name' => esc_html__('Footer sidebar 4','fitness-elementor'),
			'id'   => 'footer4-sidebar',
			'description'   => esc_html__('It appears in the footer 4.', 'fitness-elementor'),
			'before_widget' => '<aside id="%1$s" class="%2$s">',
			'after_widget'  => '</aside>',
			'before_title'  => '<h4 class="title">',
			'after_title'   => '</h4>'

		));

	}

	add_action( 'widgets_init', 'fitness_elementor_widgets_init' );

}

function fitness_elementor_the_breadcrumb() {
	if (!is_home()) {
		echo '<a href="';
		echo esc_url( home_url() );
		echo '">';
		bloginfo('name');
		echo "</a> >> ";
		if (is_category() || is_single()) {
			the_category(' , ');
			if (is_single()) {
				echo " >> ";
				the_title();
			}
		} elseif (is_page()) {
			the_title();
		}
	}
}

/**
 * logo resizer
 */

 function fitness_elementor_logo_resizer() {

    $fitness_elementor_theme_logo_size_css = '';
	$fitness_elementor_theme_custom_logo_id = get_theme_mod( 'custom_logo' );
	$fitness_elementor_theme_logo_width = get_theme_mod( 'fitness_elementor_logo_resizer', 150 ); // Default to 200 if not set
	$fitness_elementor_theme_logo_height = $fitness_elementor_theme_logo_width * 0.5;

	if ( $fitness_elementor_theme_custom_logo_id ) {
		$fitness_elementor_theme_logo_data = wp_get_attachment_image_src( $fitness_elementor_theme_custom_logo_id, 'full' );
		$fitness_elementor_theme_logo_url = $fitness_elementor_theme_logo_data[0];
		$fitness_elementor_theme_original_width = $fitness_elementor_theme_logo_data[1];
		$fitness_elementor_theme_original_height = $fitness_elementor_theme_logo_data[2];
		$fitness_elementor_theme_aspect_ratio = $fitness_elementor_theme_original_height / $fitness_elementor_theme_original_width;
		$fitness_elementor_theme_logo_height = $fitness_elementor_theme_logo_width * $fitness_elementor_theme_aspect_ratio;
   
	}
	$fitness_elementor_theme_logo_size_css = '
		.custom-logo{
			height: '.esc_attr($fitness_elementor_theme_logo_height).'px !important;
			width: '.esc_attr($fitness_elementor_theme_logo_width).'px !important;
		}
	';
    wp_add_inline_style( 'fitness-elementor-style',$fitness_elementor_theme_logo_size_css );

}

add_action( 'wp_enqueue_scripts', 'fitness_elementor_logo_resizer' );


/**
 * Change number or products per row to 3
 */
add_filter('loop_shop_columns', 'fitness_elementor_loop_columns', 999);
if (!function_exists('fitness_elementor_loop_columns')) {
	function fitness_elementor_loop_columns() {
		return get_theme_mod( 'fitness_elementor_products_per_row', '3' ); 
	}
}

//Change number of products that are displayed per page (shop page)
add_filter( 'loop_shop_per_page', 'fitness_elementor_products_per_page' );
function fitness_elementor_products_per_page( $cols ) {
  	return  get_theme_mod( 'fitness_elementor_products_per_page',9);
}


function fitness_elementor_sanitize_phone_number( $phone ) {
	return preg_replace( '/[^\d+]/', '', $phone );
}


function fitness_elementor_customize_css() {
	?>
	<?php if ( 'right' == get_theme_mod( 'fitness_elementor_sale_badge_position', 'right' ) ) : ?>
		<style>
		.woocommerce ul.products li.product .onsale {
			left: auto; right: 10px;
		}
		</style>
	<?php elseif ( 'left' == get_theme_mod( 'fitness_elementor_sale_badge_position', 'right' ) ) : ?>
		<style>
		.woocommerce ul.products li.product .onsale{
			left: 10px; right: auto ;
		}
		</style>
	<?php endif; ?>

	<?php
}

add_action( 'wp_head', 'fitness_elementor_customize_css');

function fitness_elementor_enqueue_setting() {
	define('FITNESS_ELEMENTOR_FREE_THEME_DOC',__('https://preview.wpelemento.com/theme-documentation/fitness-elementor/','fitness-elementor'));
	define('FITNESS_ELEMENTOR_SUPPORT',__('https://wordpress.org/support/theme/fitness-elementor/','fitness-elementor'));
	define('FITNESS_ELEMENTOR_REVIEW',__('https://wordpress.org/support/theme/fitness-elementor/reviews/','fitness-elementor'));
	define('FITNESS_ELEMENTOR_BUY_NOW',__('https://www.wpelemento.com/products/fitness-wordpress-theme','fitness-elementor'));
	define('FITNESS_ELEMENTOR_LIVE_DEMO',__('https://preview.wpelemento.com/fitness-elementor/','fitness-elementor'));
	define('FITNESS_ELEMENTOR_THEME_BUNDLE',__('https://www.wpelemento.com/products/wordpress-theme-bundle','fitness-elementor'));

	require get_template_directory() .'/includes/tgm/tgm.php';
	require get_template_directory() . '/includes/customizer.php';
	load_template( trailingslashit( get_template_directory() ) . '/includes/go-pro/class-upgrade-pro.php' );

	/* Plugin Activation */
	require get_template_directory() . '/includes/getstart/plugin-activation.php';

	/* Implement the About theme page */
	require get_template_directory() . '/includes/getstart/getstart.php';

	require get_template_directory() . '/includes/product-create.php';

	if( class_exists( 'Whizzie' ) ) {
		$Whizzie = new Whizzie();
	}
}
add_action('after_setup_theme', 'fitness_elementor_enqueue_setting');

?>
