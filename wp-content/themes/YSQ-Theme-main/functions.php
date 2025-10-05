<?php
/**
 * YSQ Theme Functions
 *
 * @package YSQ
 */

if (!defined('ABSPATH')) {
    exit;
}

function ysq_setup() {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('custom-logo', array(
        'height'      => 80,
        'width'       => 200,
        'flex-height' => true,
        'flex-width'  => true,
    ));
    add_theme_support('custom-background', array(
        'default-color' => 'e8e8e8',
    ));
    add_theme_support('html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
    ));

    register_nav_menus(array(
        'primary'        => __('Primary Menu', 'ysq'),
        'footer-info'    => __('Footer Info Menu', 'ysq'),
        'footer-contact' => __('Footer Contact Menu', 'ysq'),
        'footer-col4'    => __('Footer Column 4 Menu', 'ysq'),
    ));
}
add_action('after_setup_theme', 'ysq_setup');

function ysq_enqueue_scripts() {
    wp_enqueue_style('ysq-style', get_stylesheet_uri(), array(), '1.2');
}
add_action('wp_enqueue_scripts', 'ysq_enqueue_scripts');

function ysq_get_current_year() {
    return date('Y');
}

function ysq_hex_to_rgba($color, $opacity = 1) {
    $opacity = max(0, min(1, floatval($opacity)));
    $sanitized = sanitize_hex_color($color);

    if (!$sanitized) {
        $sanitized = '#000000';
    }

    $sanitized = ltrim($sanitized, '#');

    if (strlen($sanitized) === 3) {
        $sanitized = $sanitized[0] . $sanitized[0] . $sanitized[1] . $sanitized[1] . $sanitized[2] . $sanitized[2];
    }

    $red = hexdec(substr($sanitized, 0, 2));
    $green = hexdec(substr($sanitized, 2, 2));
    $blue = hexdec(substr($sanitized, 4, 2));

    return sprintf('rgba(%d, %d, %d, %s)', $red, $green, $blue, $opacity);
}

function ysq_customize_register($wp_customize) {
    $wp_customize->add_section('ysq_typography_section', array(
        'title'    => __('Typography', 'ysq'),
        'priority' => 25,
    ));

    $wp_customize->add_setting('ysq_base_font_size', array(
        'default'   => '16',
        'transport' => 'refresh',
    ));

    $wp_customize->add_control('ysq_base_font_size', array(
        'label'       => __('Base Font Size (px)', 'ysq'),
        'description' => __('Ukuran font dasar untuk website (default: 16px)', 'ysq'),
        'section'     => 'ysq_typography_section',
        'type'        => 'number',
        'input_attrs' => array(
            'min'  => 12,
            'max'  => 24,
            'step' => 1,
        ),
    ));

    $wp_customize->add_setting('ysq_heading_font_size', array(
        'default'   => '24',
        'transport' => 'refresh',
    ));

    $wp_customize->add_control('ysq_heading_font_size', array(
        'label'       => __('Heading Font Size (px)', 'ysq'),
        'description' => __('Ukuran font untuk heading/judul (default: 24px)', 'ysq'),
        'section'     => 'ysq_typography_section',
        'type'        => 'number',
        'input_attrs' => array(
            'min'  => 18,
            'max'  => 40,
            'step' => 1,
        ),
    ));

    $wp_customize->add_section('ysq_header_section', array(
        'title'    => __('Header Settings', 'ysq'),
        'priority' => 30,
    ));

    $wp_customize->add_setting('ysq_header_bg_color', array(
        'default'   => '#ffffff',
        'transport' => 'refresh',
    ));

    if (class_exists('WP_Customize_Color_Control')) {
        $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'ysq_header_bg_color', array(
            'label'   => __('Header Background Color', 'ysq'),
            'section' => 'ysq_header_section',
        )));
    }

    $wp_customize->add_setting('ysq_header_bg_opacity', array(
        'default'   => '100',
        'transport' => 'refresh',
    ));

    $wp_customize->add_control('ysq_header_bg_opacity', array(
        'label'       => __('Header Background Opacity (%)', 'ysq'),
        'section'     => 'ysq_header_section',
        'type'        => 'range',
        'input_attrs' => array(
            'min'  => 0,
            'max'  => 100,
            'step' => 1,
        ),
    ));

    $wp_customize->add_section('ysq_branding_section', array(
        'title'    => __('Branding Card', 'ysq'),
        'priority' => 31,
    ));

    $wp_customize->add_setting('ysq_show_branding_card', array(
        'default'   => true,
        'transport' => 'refresh',
    ));

    $wp_customize->add_control('ysq_show_branding_card', array(
        'label'       => __('Show Branding Card', 'ysq'),
        'description' => __('Tampilkan atau sembunyikan card branding di header', 'ysq'),
        'section'     => 'ysq_branding_section',
        'type'        => 'checkbox',
    ));

    $wp_customize->add_setting('ysq_site_title', array(
        'default'   => 'Sabilul Qur\'an • HRIS',
        'transport' => 'refresh',
    ));

    $wp_customize->add_control('ysq_site_title', array(
        'label'   => __('Site Title', 'ysq'),
        'section' => 'ysq_branding_section',
        'type'    => 'text',
    ));

    $wp_customize->add_setting('ysq_brand_color', array(
        'default'   => '#175887',
        'transport' => 'refresh',
    ));

    if (class_exists('WP_Customize_Color_Control')) {
        $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'ysq_brand_color', array(
            'label'       => __('Brand Color', 'ysq'),
            'description' => __('Warna untuk card branding dan link', 'ysq'),
            'section'     => 'ysq_branding_section',
        )));
    }

    $wp_customize->add_setting('ysq_brand_color_opacity', array(
        'default'   => '100',
        'transport' => 'refresh',
    ));

    $wp_customize->add_control('ysq_brand_color_opacity', array(
        'label'       => __('Brand Color Opacity (%)', 'ysq'),
        'section'     => 'ysq_branding_section',
        'type'        => 'range',
        'input_attrs' => array(
            'min'  => 0,
            'max'  => 100,
            'step' => 1,
        ),
    ));

    $wp_customize->add_section('ysq_buttons_section', array(
        'title'    => __('Header Buttons', 'ysq'),
        'priority' => 35,
    ));

    $wp_customize->add_setting('ysq_show_header_buttons', array(
        'default'   => true,
        'transport' => 'refresh',
    ));

    $wp_customize->add_control('ysq_show_header_buttons', array(
        'label'       => __('Show Header Buttons Card', 'ysq'),
        'description' => __('Tampilkan atau sembunyikan card buttons di header', 'ysq'),
        'section'     => 'ysq_buttons_section',
        'type'        => 'checkbox',
    ));

    $wp_customize->add_setting('ysq_login_button_text', array(
        'default'   => 'Masuk Pegawai',
        'transport' => 'refresh',
    ));

    $wp_customize->add_control('ysq_login_button_text', array(
        'label'   => __('Login Button Text', 'ysq'),
        'section' => 'ysq_buttons_section',
        'type'    => 'text',
    ));

    $wp_customize->add_setting('ysq_login_button_url', array(
        'default'   => '/masuk',
        'transport' => 'refresh',
    ));

    $wp_customize->add_control('ysq_login_button_url', array(
        'label'   => __('Login Button URL', 'ysq'),
        'section' => 'ysq_buttons_section',
        'type'    => 'url',
    ));

    $wp_customize->add_setting('ysq_main_site_button_text', array(
        'default'   => 'Kembali ke Situs Utama',
        'transport' => 'refresh',
    ));

    $wp_customize->add_control('ysq_main_site_button_text', array(
        'label'   => __('Main Site Button Text', 'ysq'),
        'section' => 'ysq_buttons_section',
        'type'    => 'text',
    ));

    $wp_customize->add_setting('ysq_main_site_button_url', array(
        'default'   => 'https://sabilulquran.or.id',
        'transport' => 'refresh',
    ));

    $wp_customize->add_control('ysq_main_site_button_url', array(
        'label'   => __('Main Site Button URL', 'ysq'),
        'section' => 'ysq_buttons_section',
        'type'    => 'url',
    ));

    $wp_customize->add_section('ysq_footer_section', array(
        'title'    => __('Footer Settings', 'ysq'),
        'priority' => 40,
    ));

    $wp_customize->add_setting('ysq_footer_bg_color', array(
        'default'   => '#f8f9fa',
        'transport' => 'refresh',
    ));

    if (class_exists('WP_Customize_Color_Control')) {
        $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'ysq_footer_bg_color', array(
            'label'   => __('Footer Background Color', 'ysq'),
            'section' => 'ysq_footer_section',
        )));
    }

    $wp_customize->add_setting('ysq_footer_bg_opacity', array(
        'default'   => '100',
        'transport' => 'refresh',
    ));

    $wp_customize->add_control('ysq_footer_bg_opacity', array(
        'label'       => __('Footer Background Opacity (%)', 'ysq'),
        'section'     => 'ysq_footer_section',
        'type'        => 'range',
        'input_attrs' => array(
            'min'  => 0,
            'max'  => 100,
            'step' => 1,
        ),
    ));

    $wp_customize->add_setting('ysq_footer_bottom_bg_color', array(
        'default'   => '#2d5f5d',
        'transport' => 'refresh',
    ));

    if (class_exists('WP_Customize_Color_Control')) {
        $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'ysq_footer_bottom_bg_color', array(
            'label'   => __('Footer Bottom Background Color', 'ysq'),
            'section' => 'ysq_footer_section',
        )));
    }

    $wp_customize->add_setting('ysq_footer_bottom_bg_opacity', array(
        'default'   => '100',
        'transport' => 'refresh',
    ));

    $wp_customize->add_control('ysq_footer_bottom_bg_opacity', array(
        'label'       => __('Footer Bottom Background Opacity (%)', 'ysq'),
        'section'     => 'ysq_footer_section',
        'type'        => 'range',
        'input_attrs' => array(
            'min'  => 0,
            'max'  => 100,
            'step' => 1,
        ),
    ));

    $wp_customize->add_setting('ysq_footer_bottom_text_color', array(
        'default'   => '#ffffff',
        'transport' => 'refresh',
    ));

    if (class_exists('WP_Customize_Color_Control')) {
        $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'ysq_footer_bottom_text_color', array(
            'label'   => __('Footer Bottom Text Color', 'ysq'),
            'section' => 'ysq_footer_section',
        )));
    }

    $wp_customize->add_setting('ysq_footer_title_row_height', array(
        'default'   => '56',
        'transport' => 'refresh',
    ));

    $wp_customize->add_control('ysq_footer_title_row_height', array(
        'label'       => __('Title Row Min Height (px)', 'ysq'),
        'description' => __('Tinggi minimum baris judul footer. Gunakan 0 untuk mengikuti tinggi konten.', 'ysq'),
        'section'     => 'ysq_footer_section',
        'type'        => 'number',
        'input_attrs' => array(
            'min'  => 0,
            'max'  => 200,
            'step' => 1,
        ),
    ));

    $wp_customize->add_setting('ysq_footer_content_row_height', array(
        'default'   => '140',
        'transport' => 'refresh',
    ));

    $wp_customize->add_control('ysq_footer_content_row_height', array(
        'label'       => __('Content Row Min Height (px)', 'ysq'),
        'description' => __('Tinggi minimum baris konten footer. Gunakan 0 untuk mengikuti tinggi konten.', 'ysq'),
        'section'     => 'ysq_footer_section',
        'type'        => 'number',
        'input_attrs' => array(
            'min'  => 0,
            'max'  => 400,
            'step' => 1,
        ),
    ));

    $footer_columns = array(
        1 => array(
            'title_default'       => 'Info',
            'title_description'   => __('Support HTML', 'ysq'),
            'content_default'     => '<ul><li>Nasihat</li><li>Karir</li><li>Program Orang Tua Asuh</li><li>Pengaduan</li><li>Program Qurban</li></ul>',
            'content_description' => __('Support HTML. Gunakan <ul><li>, <p>, <a>, <img>, dll', 'ysq'),
        ),
        2 => array(
            'title_default'       => 'Kontak',
            'title_description'   => __('Support HTML', 'ysq'),
            'content_default'     => '<ul><li><a href="mailto:Email.Sabilulquran@gmail.com">Email Sabilulquran</a></li><li>Telp: 0851-7536-2016</li></ul>',
            'content_description' => __('Support HTML. Gunakan <ul><li>, <p>, <a>, <img>, dll', 'ysq'),
        ),
        3 => array(
            'title_default'       => 'Lokasi',
            'title_description'   => __('Support HTML', 'ysq'),
            'content_default'     => '<p>Tambahkan konten atau embed Google Maps</p>',
            'content_description' => __('Support HTML & iframe. Bisa untuk embed Google Maps', 'ysq'),
        ),
        4 => array(
            'title_default'       => 'Lainnya',
            'title_description'   => __('Support HTML', 'ysq'),
            'content_default'     => '<p>Tambahkan konten untuk kolom 4</p>',
            'content_description' => __('Support HTML. Gunakan <ul><li>, <p>, <a>, <img>, dll', 'ysq'),
        ),
    );

    foreach ($footer_columns as $index => $defaults) {
        $width_setting   = sprintf('ysq_footer_col%d_width', $index);
        $title_setting   = sprintf('ysq_footer_col%d_title', $index);
        $title_font      = sprintf('ysq_footer_col%d_title_font_size', $index);
        $title_weight    = sprintf('ysq_footer_col%d_title_font_weight', $index);
        $content_setting = sprintf('ysq_footer_col%d_content', $index);
        $font_setting    = sprintf('ysq_footer_col%d_font_size', $index);
        $content_weight  = sprintf('ysq_footer_col%d_content_font_weight', $index);
        $gap_setting     = sprintf('ysq_footer_col%d_content_gap', $index);
        $color_setting   = sprintf('ysq_footer_col%d_color', $index);
        $opacity_setting = sprintf('ysq_footer_col%d_opacity', $index);

        $wp_customize->add_setting($width_setting, array(
            'default'   => '25',
            'transport' => 'refresh',
        ));

        $wp_customize->add_control($width_setting, array(
            'label'       => sprintf(__('Column %d - Width (%%)', 'ysq'), $index),
            'description' => __('Lebar kolom dalam persen (total 4 kolom = 100%)', 'ysq'),
            'section'     => 'ysq_footer_section',
            'type'        => 'number',
            'input_attrs' => array(
                'min'  => 10,
                'max'  => 70,
                'step' => 5,
            ),
        ));

        $wp_customize->add_setting($title_setting, array(
            'default'   => $defaults['title_default'],
            'transport' => 'refresh',
        ));

        $wp_customize->add_control($title_setting, array(
            'label'       => sprintf(__('Column %d - Title', 'ysq'), $index),
            'description' => $defaults['title_description'],
            'section'     => 'ysq_footer_section',
            'type'        => 'textarea',
        ));

        $wp_customize->add_setting($title_font, array(
            'default'   => '18',
            'transport' => 'refresh',
        ));

        $wp_customize->add_control($title_font, array(
            'label'       => sprintf(__('Column %d - Title Font Size (px)', 'ysq'), $index),
            'section'     => 'ysq_footer_section',
            'type'        => 'number',
            'input_attrs' => array(
                'min'  => 12,
                'max'  => 32,
                'step' => 1,
            ),
        ));

        $wp_customize->add_setting($title_weight, array(
            'default'   => 'bold',
            'transport' => 'refresh',
        ));

        $wp_customize->add_control($title_weight, array(
            'label'   => sprintf(__('Column %d - Title Font Weight', 'ysq'), $index),
            'section' => 'ysq_footer_section',
            'type'    => 'select',
            'choices' => array(
                'normal' => __('Normal', 'ysq'),
                'bold'   => __('Bold', 'ysq'),
            ),
        ));

        $wp_customize->add_setting($content_setting, array(
            'default'   => $defaults['content_default'],
            'transport' => 'refresh',
        ));

        $wp_customize->add_control($content_setting, array(
            'label'       => sprintf(__('Column %d - Content', 'ysq'), $index),
            'description' => $defaults['content_description'],
            'section'     => 'ysq_footer_section',
            'type'        => 'textarea',
        ));

        $wp_customize->add_setting($font_setting, array(
            'default'   => '14',
            'transport' => 'refresh',
        ));

        $wp_customize->add_control($font_setting, array(
            'label'       => sprintf(__('Column %d - Font Size (px)', 'ysq'), $index),
            'section'     => 'ysq_footer_section',
            'type'        => 'number',
            'input_attrs' => array(
                'min'  => 10,
                'max'  => 24,
                'step' => 1,
            ),
        ));

        $wp_customize->add_setting($content_weight, array(
            'default'   => 'normal',
            'transport' => 'refresh',
        ));

        $wp_customize->add_control($content_weight, array(
            'label'   => sprintf(__('Column %d - Content Font Weight', 'ysq'), $index),
            'section' => 'ysq_footer_section',
            'type'    => 'select',
            'choices' => array(
                'normal' => __('Normal', 'ysq'),
                'bold'   => __('Bold', 'ysq'),
            ),
        ));

        $wp_customize->add_setting($gap_setting, array(
            'default'           => '12',
            'transport'         => 'refresh',
            'sanitize_callback' => 'absint',
        ));

        $wp_customize->add_control($gap_setting, array(
            'label'       => sprintf(__('Column %d - Content Gap (px)', 'ysq'), $index),
            'section'     => 'ysq_footer_section',
            'type'        => 'number',
            'input_attrs' => array(
                'min'  => 0,
                'max'  => 64,
                'step' => 1,
            ),
        ));

        $wp_customize->add_setting($color_setting, array(
            'default'   => '#333333',
            'transport' => 'refresh',
        ));

        if (class_exists('WP_Customize_Color_Control')) {
            $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, $color_setting, array(
                'label'   => sprintf(__('Column %d - Text Color', 'ysq'), $index),
                'section' => 'ysq_footer_section',
            )));
        }

        $wp_customize->add_setting($opacity_setting, array(
            'default'   => '100',
            'transport' => 'refresh',
        ));

        $wp_customize->add_control($opacity_setting, array(
            'label'       => sprintf(__('Column %d - Text Opacity (%%)', 'ysq'), $index),
            'section'     => 'ysq_footer_section',
            'type'        => 'range',
            'input_attrs' => array(
                'min'  => 0,
                'max'  => 100,
                'step' => 1,
            ),
        ));
    }

    $wp_customize->add_setting('ysq_footer_ahu_text', array(
        'default'   => 'AHU – 0033732. AH. 01. 04. Tahun 2015 | Tanggal 28 Desember 2015',
        'transport' => 'refresh',
    ));

    $wp_customize->add_control('ysq_footer_ahu_text', array(
        'label'       => __('Footer Bottom - Title', 'ysq'),
        'description' => __('Support HTML', 'ysq'),
        'section'     => 'ysq_footer_section',
        'type'        => 'textarea',
    ));

    $wp_customize->add_setting('ysq_footer_copyright_text', array(
        'default'   => 'Yayasan Sabilul Qur\'an • HRIS Internal v1.2',
        'transport' => 'refresh',
    ));

    $wp_customize->add_control('ysq_footer_copyright_text', array(
        'label'   => __('Footer Bottom - Copyright Text', 'ysq'),
        'section' => 'ysq_footer_section',
        'type'    => 'text',
    ));
}
add_action('customize_register', 'ysq_customize_register');

function ysq_admin_start_session() {
    if (!session_id()) {
        session_start();
    }
}
add_action('init', 'ysq_admin_start_session', 1);

function ysq_admin_ensure_default_credentials() {
    if (false === get_option('ysq_admin_username', false)) {
        update_option('ysq_admin_username', 'administrator');
    }

    if (false === get_option('ysq_admin_password_hash', false)) {
        update_option('ysq_admin_password_hash', wp_hash_password('05SekarKemuning'));
    }
}
add_action('after_switch_theme', 'ysq_admin_ensure_default_credentials');

function ysq_admin_get_username() {
    $stored = get_option('ysq_admin_username', 'administrator');

    if (!is_string($stored) || '' === trim($stored)) {
        return 'administrator';
    }

    return $stored;
}

function ysq_admin_get_password_hash() {
    $hash = get_option('ysq_admin_password_hash', '');

    if (!is_string($hash) || '' === $hash) {
        $hash = wp_hash_password('05SekarKemuning');
        update_option('ysq_admin_password_hash', $hash);
    }

    return $hash;
}

function ysq_admin_is_logged_in() {
    return !empty($_SESSION['ysq_admin_logged_in']);
}

function ysq_register_announcement_cpt() {
    $labels = array(
        'name'          => __('Pengumuman', 'ysq'),
        'singular_name' => __('Pengumuman', 'ysq'),
    );

    $args = array(
        'labels'              => $labels,
        'public'              => false,
        'show_ui'             => false,
        'show_in_nav_menus'   => false,
        'show_in_rest'        => false,
        'supports'            => array('title', 'editor'),
        'capability_type'     => 'post',
        'map_meta_cap'        => true,
        'publicly_queryable'  => false,
        'exclude_from_search' => true,
        'rewrite'             => false,
    );

    register_post_type('ysq_announcement', $args);
}
add_action('init', 'ysq_register_announcement_cpt');

function ysq_add_admin_notice($type, $message) {
    if (!isset($_SESSION['ysq_admin_notices'])) {
        $_SESSION['ysq_admin_notices'] = array();
    }

    $_SESSION['ysq_admin_notices'][] = array(
        'type'    => $type,
        'message' => $message,
    );
}

function ysq_get_admin_notices() {
    if (!isset($_SESSION['ysq_admin_notices'])) {
        return array();
    }

    $notices = $_SESSION['ysq_admin_notices'];
    unset($_SESSION['ysq_admin_notices']);

    return $notices;
}

function ysq_admin_redirect($url = '') {
    if (wp_doing_ajax()) {
        return;
    }

    if (empty($url)) {
        $referer = wp_get_referer();
        if ($referer) {
            $url = $referer;
        } else {
            $url = home_url('/');
        }
    }

    wp_safe_redirect($url);
    exit;
}

function ysq_handle_admin_requests() {
    if (is_admin()) {
        return;
    }

    ysq_admin_ensure_default_credentials();

    if (isset($_GET['ysq_admin_logout']) && ysq_admin_is_logged_in()) {
        unset($_SESSION['ysq_admin_logged_in']);
        ysq_add_admin_notice('success', __('Anda telah keluar dari sesi administrator.', 'ysq'));
        ysq_admin_redirect();
    }

    if (isset($_POST['ysq_admin_login'])) {
        if (!isset($_POST['ysq_admin_login_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['ysq_admin_login_nonce'])), 'ysq_admin_login')) {
            ysq_add_admin_notice('error', __('Sesi login tidak valid. Silakan coba lagi.', 'ysq'));
            ysq_admin_redirect();
        }

        $username = isset($_POST['ysq_admin_username']) ? sanitize_text_field(wp_unslash($_POST['ysq_admin_username'])) : '';
        $password = isset($_POST['ysq_admin_password']) ? wp_unslash($_POST['ysq_admin_password']) : '';

        if ($username === ysq_admin_get_username() && wp_check_password($password, ysq_admin_get_password_hash())) {
            $_SESSION['ysq_admin_logged_in'] = true;
            ysq_add_admin_notice('success', __('Login administrator berhasil.', 'ysq'));
        } else {
            ysq_add_admin_notice('error', __('Username atau password administrator salah.', 'ysq'));
        }

        ysq_admin_redirect();
    }

    if (!ysq_admin_is_logged_in()) {
        return;
    }

    if (isset($_POST['ysq_save_announcement'])) {
        if (!isset($_POST['ysq_save_announcement_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['ysq_save_announcement_nonce'])), 'ysq_save_announcement')) {
            ysq_add_admin_notice('error', __('Sesi tidak valid saat menyimpan pengumuman.', 'ysq'));
            ysq_admin_redirect();
        }

        $announcement_id = isset($_POST['ysq_announcement_id']) ? absint($_POST['ysq_announcement_id']) : 0;
        $title            = isset($_POST['ysq_announcement_title']) ? sanitize_text_field(wp_unslash($_POST['ysq_announcement_title'])) : '';
        $content          = isset($_POST['ysq_announcement_content']) ? wp_kses_post(wp_unslash($_POST['ysq_announcement_content'])) : '';

        if ('' === $title) {
            ysq_add_admin_notice('error', __('Judul pengumuman wajib diisi.', 'ysq'));
            ysq_admin_redirect();
        }

        $post_data = array(
            'post_title'   => $title,
            'post_content' => $content,
            'post_status'  => 'publish',
            'post_type'    => 'ysq_announcement',
        );

        $redirect_target = '';

        if ($announcement_id > 0) {
            $existing = get_post($announcement_id);
            if ($existing && 'ysq_announcement' === $existing->post_type) {
                $post_data['ID'] = $announcement_id;
                $result = wp_update_post($post_data, true);
                if (is_wp_error($result)) {
                    ysq_add_admin_notice('error', __('Pengumuman tidak dapat diperbarui. Silakan coba lagi.', 'ysq'));
                } else {
                    ysq_add_admin_notice('success', __('Pengumuman berhasil diperbarui.', 'ysq'));
                    $referer         = wp_get_referer();
                    $redirect_target = remove_query_arg('ysq_edit', $referer ? $referer : home_url('/'));
                }
            } else {
                ysq_add_admin_notice('error', __('Pengumuman tidak ditemukan.', 'ysq'));
            }
        } else {
            $result = wp_insert_post($post_data, true);
            if (is_wp_error($result)) {
                ysq_add_admin_notice('error', __('Pengumuman baru gagal disimpan.', 'ysq'));
            } else {
                ysq_add_admin_notice('success', __('Pengumuman baru berhasil ditambahkan.', 'ysq'));
                $referer         = wp_get_referer();
                $redirect_target = remove_query_arg('ysq_edit', $referer ? $referer : home_url('/'));
            }
        }

        if ('' === $redirect_target) {
            ysq_admin_redirect();
        }

        ysq_admin_redirect($redirect_target);
    }

    if (isset($_POST['ysq_delete_announcement'])) {
        $announcement_id = absint($_POST['ysq_delete_announcement']);
        $nonce_field     = isset($_POST['ysq_delete_announcement_nonce']) ? sanitize_text_field(wp_unslash($_POST['ysq_delete_announcement_nonce'])) : '';

        if (!$announcement_id || !wp_verify_nonce($nonce_field, 'ysq_delete_announcement_' . $announcement_id)) {
            ysq_add_admin_notice('error', __('Sesi tidak valid saat menghapus pengumuman.', 'ysq'));
            ysq_admin_redirect();
        }

        $existing = get_post($announcement_id);
        if ($existing && 'ysq_announcement' === $existing->post_type) {
            wp_delete_post($announcement_id, true);
            ysq_add_admin_notice('success', __('Pengumuman berhasil dihapus.', 'ysq'));
            $referer = wp_get_referer();
            $target  = remove_query_arg('ysq_edit', $referer ? $referer : home_url('/'));
            ysq_admin_redirect($target);
        } else {
            ysq_add_admin_notice('error', __('Pengumuman tidak ditemukan.', 'ysq'));
        }

        ysq_admin_redirect();
    }

    if (isset($_POST['ysq_update_credentials'])) {
        if (!isset($_POST['ysq_update_credentials_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['ysq_update_credentials_nonce'])), 'ysq_update_credentials')) {
            ysq_add_admin_notice('error', __('Sesi tidak valid saat memperbarui kredensial.', 'ysq'));
            ysq_admin_redirect();
        }

        $new_username  = isset($_POST['ysq_new_username']) ? sanitize_text_field(wp_unslash($_POST['ysq_new_username'])) : '';
        $new_password  = isset($_POST['ysq_new_password']) ? wp_unslash($_POST['ysq_new_password']) : '';
        $confirm_pass  = isset($_POST['ysq_confirm_password']) ? wp_unslash($_POST['ysq_confirm_password']) : '';

        if ('' === $new_username) {
            ysq_add_admin_notice('error', __('Username administrator tidak boleh kosong.', 'ysq'));
            ysq_admin_redirect();
        }

        update_option('ysq_admin_username', $new_username);

        if ('' !== $new_password || '' !== $confirm_pass) {
            if ($new_password !== $confirm_pass) {
                ysq_add_admin_notice('error', __('Konfirmasi password tidak sama.', 'ysq'));
                ysq_admin_redirect();
            }

            update_option('ysq_admin_password_hash', wp_hash_password($new_password));
        }

        ysq_add_admin_notice('success', __('Pengaturan akun administrator berhasil disimpan.', 'ysq'));
        ysq_admin_redirect();
    }
}
add_action('init', 'ysq_handle_admin_requests', 20);

function ysq_custom_styles() {
    $base_font_size     = get_theme_mod('ysq_base_font_size', '16');
    $heading_font_size  = get_theme_mod('ysq_heading_font_size', '24');

    $header_bg          = get_theme_mod('ysq_header_bg_color', '#ffffff');
    $header_bg_opacity  = get_theme_mod('ysq_header_bg_opacity', '100');
    $header_bg_rgba     = ysq_hex_to_rgba($header_bg, floatval($header_bg_opacity) / 100);

    $brand_color        = get_theme_mod('ysq_brand_color', '#175887');
    $brand_color_op     = get_theme_mod('ysq_brand_color_opacity', '100');
    $brand_color_rgba   = ysq_hex_to_rgba($brand_color, floatval($brand_color_op) / 100);

    $footer_bg          = get_theme_mod('ysq_footer_bg_color', '#f8f9fa');
    $footer_bg_opacity  = get_theme_mod('ysq_footer_bg_opacity', '100');
    $footer_bg_rgba     = ysq_hex_to_rgba($footer_bg, floatval($footer_bg_opacity) / 100);

    $footer_bottom_bg         = get_theme_mod('ysq_footer_bottom_bg_color', '#2d5f5d');
    $footer_bottom_bg_opacity = get_theme_mod('ysq_footer_bottom_bg_opacity', '100');
    $footer_bottom_bg_rgba    = ysq_hex_to_rgba($footer_bottom_bg, floatval($footer_bottom_bg_opacity) / 100);
    $footer_bottom_text_color = get_theme_mod('ysq_footer_bottom_text_color', '#ffffff');

    $show_branding = get_theme_mod('ysq_show_branding_card', true);
    $show_buttons  = get_theme_mod('ysq_show_header_buttons', true);

    $title_row_height   = absint(get_theme_mod('ysq_footer_title_row_height', '56'));
    $content_row_height = absint(get_theme_mod('ysq_footer_content_row_height', '140'));

    $footer_columns = array();

    for ($i = 1; $i <= 4; $i++) {
        $width           = get_theme_mod('ysq_footer_col' . $i . '_width', '25');
        $title_size      = get_theme_mod('ysq_footer_col' . $i . '_title_font_size', '18');
        $title_weight    = get_theme_mod('ysq_footer_col' . $i . '_title_font_weight', 'bold');
        $font_size       = get_theme_mod('ysq_footer_col' . $i . '_font_size', '14');
        $content_weight  = get_theme_mod('ysq_footer_col' . $i . '_content_font_weight', 'normal');
        $content_gap     = get_theme_mod('ysq_footer_col' . $i . '_content_gap', '12');
        $color           = get_theme_mod('ysq_footer_col' . $i . '_color', '#333333');
        $opacity         = get_theme_mod('ysq_footer_col' . $i . '_opacity', '100');

        $width_value      = is_numeric($width) ? floatval($width) : 25;
        $title_size_value = is_numeric($title_size) ? floatval($title_size) : 18;
        $font_size_value  = is_numeric($font_size) ? floatval($font_size) : 14;
        $content_gap_value = is_numeric($content_gap) ? floatval($content_gap) : 12;
        $opacity_value    = is_numeric($opacity) ? floatval($opacity) : 100;

        $footer_columns[$i] = array(
            'width'          => max(0, min(100, $width_value)),
            'title_size'     => max(0, $title_size_value),
            'title_weight'   => in_array($title_weight, array('normal', 'bold'), true) ? $title_weight : 'bold',
            'font_size'      => max(0, $font_size_value),
            'content_weight' => in_array($content_weight, array('normal', 'bold'), true) ? $content_weight : 'normal',
            'content_gap'    => max(0, $content_gap_value),
            'color'          => $color,
            'opacity'        => max(0, min(100, $opacity_value)),
        );
    }
    ?>
    <style type="text/css">
        body {
            font-size: <?php echo esc_attr($base_font_size); ?>px !important;
        }

        h1,
        h2,
        h3,
        h4,
        h5,
        h6 {
            font-size: <?php echo esc_attr($heading_font_size); ?>px !important;
        }

        .site-header {
            background-color: <?php echo esc_attr($header_bg_rgba); ?> !important;
        }

        .site-branding {
            <?php if (!$show_branding) : ?>
            display: none !important;
            <?php endif; ?>
        }

        .main-navigation {
            <?php if (!$show_buttons) : ?>
            display: none !important;
            <?php endif; ?>
        }

        .site-title {
            color: <?php echo esc_attr($brand_color_rgba); ?> !important;
        }

        .main-navigation a.btn-primary {
            background-color: <?php echo esc_attr($brand_color_rgba); ?> !important;
            border-color: <?php echo esc_attr($brand_color_rgba); ?> !important;
        }

        a {
            color: <?php echo esc_attr($brand_color_rgba); ?>;
        }

        .site-footer {
            background-color: <?php echo esc_attr($footer_bg_rgba); ?> !important;
            --footer-title-row-height: <?php echo esc_attr($title_row_height > 0 ? $title_row_height . 'px' : 'auto'); ?>;
            --footer-content-row-height: <?php echo esc_attr($content_row_height > 0 ? $content_row_height . 'px' : 'auto'); ?>;
<?php foreach ($footer_columns as $index => $column) :
    $color_rgba = ysq_hex_to_rgba($column['color'], max(0, min(1, $column['opacity'] / 100)));
?>
            --footer-col<?php echo esc_attr($index); ?>-width: <?php echo esc_attr($column['width']); ?>%;
            --footer-col<?php echo esc_attr($index); ?>-title-font-size: <?php echo esc_attr($column['title_size']); ?>px;
            --footer-col<?php echo esc_attr($index); ?>-title-font-weight: <?php echo esc_attr($column['title_weight']); ?>;
            --footer-col<?php echo esc_attr($index); ?>-content-font-size: <?php echo esc_attr($column['font_size']); ?>px;
            --footer-col<?php echo esc_attr($index); ?>-content-font-weight: <?php echo esc_attr($column['content_weight']); ?>;
            --footer-col<?php echo esc_attr($index); ?>-content-gap: <?php echo esc_attr($column['content_gap']); ?>px;
            --footer-col<?php echo esc_attr($index); ?>-text-color: <?php echo esc_attr($color_rgba); ?>;
<?php endforeach; ?>
        }

        .footer-bottom {
            background-color: <?php echo esc_attr($footer_bottom_bg_rgba); ?> !important;
        }

        .footer-bottom,
        .footer-bottom a {
            color: <?php echo esc_attr($footer_bottom_text_color); ?> !important;
        }
    </style>
    <?php
}
add_action('wp_head', 'ysq_custom_styles');

function ysq_remove_unused_dashboard_cards_script() {
    if (is_admin()) {
        return;
    }

    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var titlesToRemove = ['kontak utama', 'profil ringkas'];
        var cards = document.querySelectorAll('.dashboard-card');

        if (!cards.length) {
            return;
        }

        Array.prototype.forEach.call(cards, function (card) {
            var heading = card.querySelector('.card-header h2, h2, h3');

            if (!heading) {
                return;
            }

            var text = heading.textContent || '';

            if (titlesToRemove.indexOf(text.trim().toLowerCase()) !== -1 && card.parentNode) {
                card.parentNode.removeChild(card);
            }
        });
    });
    </script>
    <?php
}
add_action('wp_footer', 'ysq_remove_unused_dashboard_cards_script', 20);

