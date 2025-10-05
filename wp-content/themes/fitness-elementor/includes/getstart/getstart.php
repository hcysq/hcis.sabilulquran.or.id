<?php
//about theme info
add_action( 'admin_menu', 'fitness_elementor_gettingstarted' );
function fitness_elementor_gettingstarted() {
	add_theme_page( esc_html__('Fitness Elementor', 'fitness-elementor'), esc_html__('Fitness Elementor', 'fitness-elementor'), 'edit_theme_options', 'fitness_elementor_about', 'fitness_elementor_mostrar_guide');
}

// Add a Custom CSS file to WP Admin Area
function fitness_elementor_admin_theme_style() {
	wp_enqueue_style('fitness-elementor-custom-admin-style', esc_url(get_template_directory_uri()) . '/includes/getstart/getstart.css');
	wp_enqueue_script('fitness-elementor-tabs', esc_url(get_template_directory_uri()) . '/includes/getstart/js/tab.js');
	wp_enqueue_style( 'font-awesome-css', get_template_directory_uri().'/assets/css/fontawesome-all.css' );
}
add_action('admin_enqueue_scripts', 'fitness_elementor_admin_theme_style');

// Changelog
if ( ! defined( 'FITNESS_ELEMENTOR_CHANGELOG_URL' ) ) {
    define( 'FITNESS_ELEMENTOR_CHANGELOG_URL', get_template_directory() . '/readme.txt' );
}

function fitness_elementor_changelog_screen() {	
	global $wp_filesystem;
	$changelog_file = apply_filters( 'fitness_elementor_changelog_file', FITNESS_ELEMENTOR_CHANGELOG_URL );
	if ( $changelog_file && is_readable( $changelog_file ) ) {
		WP_Filesystem();
		$changelog = $wp_filesystem->get_contents( $changelog_file );
		$changelog_list = fitness_elementor_parse_changelog( $changelog );
		echo wp_kses_post( $changelog_list );
	}
}

function fitness_elementor_parse_changelog( $content ) {
	$content = explode ( '== ', $content );
	$changelog_isolated = '';
	foreach ( $content as $key => $value ) {
		if (strpos( $value, 'Changelog ==') === 0) {
	    	$changelog_isolated = str_replace( 'Changelog ==', '', $value );
	    }
	}
	$changelog_array = explode( '= ', $changelog_isolated );
	unset( $changelog_array[0] );
	$changelog = '<div class="changelog">';
	foreach ( $changelog_array as $value) {
		$value = preg_replace( '/\n+/', '</span><span>', $value );
		$value = '<div class="block"><span class="heading">= ' . $value . '</span></div><hr>';
		$changelog .= str_replace( '<span></span>', '', $value );
	}
	$changelog .= '</div>';
	return wp_kses_post( $changelog );
}

//guidline for about theme
function fitness_elementor_mostrar_guide() { 
	//custom function about theme customizer
	$fitness_elementor_return = add_query_arg( array()) ;
	$fitness_elementor_theme = wp_get_theme( 'fitness-elementor' );
?>

    <div class="top-head">
		<div class="top-title">
			<h2><?php esc_html_e( 'Fitness Elementor', 'fitness-elementor' ); ?></h2>
		</div>
		<div class="top-right">
			<span class="version"><?php esc_html_e( 'Version', 'fitness-elementor' ); ?>: <?php echo esc_html($fitness_elementor_theme['Version']);?></span>
		</div>
    </div>

    <div class="inner-cont">

	    <div class="tab-sec">
	    	<div class="tab">
				<button class="tablinks" onclick="fitness_elementor_open_tab(event, 'wpelemento_importer_editor')"><?php esc_html_e( 'Setup With Elementor', 'fitness-elementor' ); ?></button>
				<button class="tablinks" onclick="fitness_elementor_open_tab(event, 'setup_customizer')"><?php esc_html_e( 'Setup With Customizer', 'fitness-elementor' ); ?></button>
				<button class="tablinks" onclick="fitness_elementor_open_tab(event, 'changelog_cont')"><?php esc_html_e( 'Changelog', 'fitness-elementor' ); ?></button>
			</div>

			
			<div id="wpelemento_importer_editor" class="tabcontent open">
				<?php if(!class_exists('WPElemento_Importer_ThemeWhizzie')){
					$plugin_ins = Fitness_Elementor_Plugin_Activation_WPElemento_Importer::get_instance();
					$fitness_elementor_actions = $plugin_ins->recommended_actions;
					?>
					<div class="fitness-elementor-recommended-plugins ">
							<div class="fitness-elementor-action-list">
								<?php if ($fitness_elementor_actions): foreach ($fitness_elementor_actions as $key => $fitness_elementor_actionValue): ?>
										<div class="fitness-elementor-action" id="<?php echo esc_attr($fitness_elementor_actionValue['id']);?>">
											<div class="action-inner plugin-activation-redirect">
												<h3 class="action-title"><?php echo esc_html($fitness_elementor_actionValue['title']); ?></h3>
												<div class="action-desc"><?php echo esc_html($fitness_elementor_actionValue['desc']); ?></div>
												<?php echo wp_kses_post($fitness_elementor_actionValue['link']); ?>
											</div>
										</div>
									<?php endforeach;
								endif; ?>
							</div>
					</div>
				<?php }else{ ?>
					<div class="tab-outer-box">
						<h3><?php esc_html_e('Welcome to WPElemento Theme!', 'fitness-elementor'); ?></h3>
						<p><?php esc_html_e('Click on the quick start button to import the demo.', 'fitness-elementor'); ?></p>
						<div class="info-link">
							<a  href="<?php echo esc_url( admin_url('admin.php?page=wpelementoimporter-wizard') ); ?>"><?php esc_html_e('Quick Start', 'fitness-elementor'); ?></a>
						</div>
					</div>
				<?php } ?>
			</div>

			<div id="setup_customizer" class="tabcontent">
				<div class="tab-outer-box">
				  	<div class="lite-theme-inner">
						<h3><?php esc_html_e('Theme Customizer', 'fitness-elementor'); ?></h3>
						<p><?php esc_html_e('To begin customizing your website, start by clicking "Customize".', 'fitness-elementor'); ?></p>
						<div class="info-link">
							<a target="_blank" href="<?php echo esc_url( admin_url('customize.php') ); ?>"><?php esc_html_e('Customizing', 'fitness-elementor'); ?></a>
						</div>
						<hr>
						<h3><?php esc_html_e('Help Docs', 'fitness-elementor'); ?></h3>
						<p><?php esc_html_e('The complete procedure to configure and manage a WordPress Website from the beginning is shown in this documentation .', 'fitness-elementor'); ?></p>
						<div class="info-link">
							<a href="<?php echo esc_url( FITNESS_ELEMENTOR_FREE_THEME_DOC ); ?>" target="_blank"><?php esc_html_e('Documentation', 'fitness-elementor'); ?></a>
						</div>
						<hr>
						<h3><?php esc_html_e('Need Support?', 'fitness-elementor'); ?></h3>
						<p><?php esc_html_e('Our dedicated team is well prepared to help you out in case of queries and doubts regarding our theme.', 'fitness-elementor'); ?></p>
						<div class="info-link">
							<a href="<?php echo esc_url( FITNESS_ELEMENTOR_SUPPORT ); ?>" target="_blank"><?php esc_html_e('Support Forum', 'fitness-elementor'); ?></a>
						</div>
						<hr>
						<h3><?php esc_html_e('Reviews & Testimonials', 'fitness-elementor'); ?></h3>
						<p> <?php esc_html_e('All the features and aspects of this WordPress Theme are phenomenal. I\'d recommend this theme to all.', 'fitness-elementor'); ?></p>
						<div class="info-link">
							<a href="<?php echo esc_url( FITNESS_ELEMENTOR_REVIEW ); ?>" target="_blank"><?php esc_html_e('Review', 'fitness-elementor'); ?></a>
						</div>
						<hr>
						<div class="link-customizer">
							<h3><?php esc_html_e( 'Link to customizer', 'fitness-elementor' ); ?></h3>
							<div class="first-row">
								<div class="row-box">
									<div class="row-box1">
										<span class="dashicons dashicons-buddicons-buddypress-logo"></span><a href="<?php echo esc_url( admin_url('customize.php?autofocus[control]=custom_logo') ); ?>" target="_blank"><?php esc_html_e('Upload your logo','fitness-elementor'); ?></a>
									</div>
									<div class="row-box2">
										<span class="dashicons dashicons-menu"></span><a href="<?php echo esc_url( admin_url('customize.php?autofocus[panel]=nav_menus') ); ?>" target="_blank"><?php esc_html_e('Menus','fitness-elementor'); ?></a>
									</div>
								</div>
							
								<div class="row-box">
									<div class="row-box1">
										<span class="dashicons dashicons-align-center"></span><a href="<?php echo esc_url( admin_url('customize.php?autofocus[section]=header_image') ); ?>" target="_blank"><?php esc_html_e('Header Image','fitness-elementor'); ?></a>
									</div>
									<div class="row-box2">
										<span class="dashicons dashicons-screenoptions"></span><a href="<?php echo esc_url( admin_url('customize.php?autofocus[panel]=widgets') ); ?>" target="_blank"><?php esc_html_e('Footer Widget','fitness-elementor'); ?></a>
									</div>
								</div>
							</div>
						</div>
				  	</div>
				</div>
			</div>

			<div id="changelog_cont" class="tabcontent">
				<div class="tab-outer-box">
					<?php fitness_elementor_changelog_screen(); ?>
				</div>
			</div>
			
		</div>

		<div class="inner-side-content">
			<h2><?php esc_html_e('Premium Theme', 'fitness-elementor'); ?></h2>
			<div class="tab-outer-box">
				<img src="<?php echo esc_url(get_template_directory_uri()); ?>/screenshot.png" alt="" />
				<h3><?php esc_html_e('Fitness WordPress Theme', 'fitness-elementor'); ?></h3>
				<div class="iner-sidebar-pro-btn">
					<span class="premium-btn"><a href="<?php echo esc_url( FITNESS_ELEMENTOR_BUY_NOW ); ?>" target="_blank"><?php esc_html_e('Buy Premium', 'fitness-elementor'); ?></a>
					</span>
					<span class="demo-btn"><a href="<?php echo esc_url( FITNESS_ELEMENTOR_LIVE_DEMO ); ?>" target="_blank"><?php esc_html_e('Live Demo', 'fitness-elementor'); ?></a>
					</span>
					<span class="doc-btn"><a href="<?php echo esc_url( FITNESS_ELEMENTOR_THEME_BUNDLE ); ?>" target="_blank"><?php esc_html_e('Theme Bundle', 'fitness-elementor'); ?></a>
					</span>
				</div>
				<hr>
				<div class="premium-coupon">
					<div class="premium-features">
						<h3><?php esc_html_e('premium Features', 'fitness-elementor'); ?></h3>
						<ul>
							<li><?php esc_html_e( 'Multilingual', 'fitness-elementor' ); ?></li>
							<li><?php esc_html_e( 'Drag and drop features', 'fitness-elementor' ); ?></li>
							<li><?php esc_html_e( 'Zero Coding Required', 'fitness-elementor' ); ?></li>
							<li><?php esc_html_e( 'Mobile Friendly Layout', 'fitness-elementor' ); ?></li>
							<li><?php esc_html_e( 'Responsive Layout', 'fitness-elementor' ); ?></li>
							<li><?php esc_html_e( 'Unique Designs', 'fitness-elementor' ); ?></li>
						</ul>
					</div>
					<div class="coupon-box">
						<h3><?php esc_html_e('Use Coupon Code', 'fitness-elementor'); ?></h3>
						<a class="coupon-btn" href="<?php echo esc_url( FITNESS_ELEMENTOR_BUY_NOW ); ?>" target="_blank"><?php esc_html_e('UPGRADE NOW', 'fitness-elementor'); ?></a>
						<div class="coupon-container">
							<h3><?php esc_html_e( 'elemento20', 'fitness-elementor' ); ?></h3>
						</div>
					</div>
				</div>
			</div>	
		</div>
	</div>

<?php } ?>