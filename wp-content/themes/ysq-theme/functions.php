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

function ysq_get_compiled_asset($filename = 'main.css') {
    static $manifest = null;

    $dist_path = trailingslashit(get_template_directory()) . 'dist';
    $dist_uri  = trailingslashit(get_template_directory_uri()) . 'dist';
    $manifest_path = trailingslashit($dist_path) . 'manifest.json';

    if ($manifest === null) {
        $manifest = array();

        if (file_exists($manifest_path)) {
            $contents = file_get_contents($manifest_path);
            $decoded  = json_decode($contents, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $manifest = $decoded;
            }
        }
    }

    $candidates = array();

    if (isset($manifest[$filename])) {
        $relative   = ltrim($manifest[$filename], '/');
        $candidates[] = array(
            'path' => trailingslashit($dist_path) . $relative,
            'uri'  => trailingslashit($dist_uri) . $relative,
        );
    }

    $candidates[] = array(
        'path' => trailingslashit($dist_path) . $filename,
        'uri'  => trailingslashit($dist_uri) . $filename,
    );

    foreach ($candidates as $candidate) {
        if (!empty($candidate['path']) && file_exists($candidate['path'])) {
            $version = filemtime($candidate['path']);

            return array(
                'uri'     => $candidate['uri'],
                'version' => $version ? (string) $version : null,
            );
        }
    }

    $fallback_path = get_stylesheet_directory() . '/style.css';
    $fallback_version = file_exists($fallback_path) ? filemtime($fallback_path) : wp_get_theme()->get('Version');

    return array(
        'uri'     => get_stylesheet_uri(),
        'version' => $fallback_version ? (string) $fallback_version : null,
    );
}

function ysq_enqueue_scripts() {
    $stylesheet = ysq_get_compiled_asset('main.css');

    wp_enqueue_style('ysq-style', $stylesheet['uri'], array(), $stylesheet['version']);

    $script_path = get_template_directory() . '/assets/js/theme.js';
    $script_version = file_exists($script_path) ? filemtime($script_path) : wp_get_theme()->get('Version');

    wp_enqueue_script('ysq-theme', get_template_directory_uri() . '/assets/js/theme.js', array(), $script_version, true);
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
        'default'   => '#2f7e20',
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

    $footer_content_defaults = array(
        1 => array(
            'title'   => 'Info',
            'content' => '<ul><li>Nasihat</li><li>Karir</li><li>Program Orang Tua Asuh</li><li>Pengaduan</li><li>Program Qurban</li></ul>',
        ),
        2 => array(
            'title'   => 'Kontak',
            'content' => '<ul><li><a href="mailto:Email.Sabilulquran@gmail.com">Email Sabilulquran</a></li><li>Telp: 0851-7536-2016</li></ul>',
        ),
        3 => array(
            'title'   => 'Lokasi',
            'content' => '<p>Tambahkan konten atau embed Google Maps</p>',
        ),
        4 => array(
            'title'   => 'Lainnya',
            'content' => '<p>Tambahkan konten untuk kolom 4</p>',
        ),
    );

    foreach ($footer_content_defaults as $index => $defaults) {
        $title_setting = sprintf('footer_col_%d_title', $index);
        $content_setting = sprintf('footer_col_%d_content', $index);

        $wp_customize->add_setting($title_setting, array(
            'default'           => $defaults['title'],
            'transport'         => 'refresh',
            'sanitize_callback' => 'sanitize_text_field',
        ));

        $wp_customize->add_control($title_setting, array(
            'label'   => sprintf(__('Kolom %d - Judul', 'ysq'), $index),
            'section' => 'ysq_footer_section',
            'type'    => 'text',
        ));

        $wp_customize->add_setting($content_setting, array(
            'default'           => $defaults['content'],
            'transport'         => 'refresh',
        ));

        $wp_customize->add_control($content_setting, array(
            'label'       => sprintf(__('Kolom %d - Konten', 'ysq'), $index),
            'description' => __('Mendukung HTML dasar seperti <p>, <ul>, <li>, dan tautan.', 'ysq'),
            'section'     => 'ysq_footer_section',
            'type'        => 'textarea',
        ));
    }

    $wp_customize->add_setting('footer_bottom_copy', array(
        'default'           => '&copy; 2025 Yayasan Sabilul Qur\'an &bull; HCIS v1.0',
        'transport'         => 'refresh',
        'sanitize_callback' => 'wp_kses_post',
    ));

    $wp_customize->add_control('footer_bottom_copy', array(
        'label'       => __('Teks Footer Bawah', 'ysq'),
        'description' => __('Gunakan teks singkat untuk copyright atau informasi lisensi.', 'ysq'),
        'section'     => 'ysq_footer_section',
        'type'        => 'textarea',
    ));
}
add_action('customize_register', 'ysq_customize_register');

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

    $footer_bottom_bg         = get_theme_mod('ysq_footer_bottom_bg_color', '#2f7e20');
    $footer_bottom_bg_opacity = get_theme_mod('ysq_footer_bottom_bg_opacity', '100');
    $footer_bottom_bg_rgba    = ysq_hex_to_rgba($footer_bottom_bg, floatval($footer_bottom_bg_opacity) / 100);
    $footer_bottom_text_color = get_theme_mod('ysq_footer_bottom_text_color', '#ffffff');

    $show_branding = get_theme_mod('ysq_show_branding_card', true);
    $show_buttons  = get_theme_mod('ysq_show_header_buttons', true);

    $header_bg_half     = ysq_hex_to_rgba($header_bg, 0.5);
    ?>
    <style type="text/css">
        :root {
            --ysq-header-bg: <?php echo esc_attr($header_bg_rgba); ?>;
            --ysq-header-bg-transparent: <?php echo esc_attr($header_bg_half); ?>;
        }

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

        .ysq-footer {
            background-color: <?php echo esc_attr($footer_bg_rgba); ?> !important;
        }

        .footer-bottom {
            background-color: <?php echo esc_attr($footer_bottom_bg_rgba); ?> !important;
        }

        .footer-bottom,
        .footer-bottom a,
        .footer-copy {
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


/**
 * Memuat file CSS kustom di semua halaman login WordPress.
 */
function ysq_custom_login_stylesheet() {
    wp_enqueue_style( 'ysq-custom-login', get_stylesheet_directory_uri() . '/css/custom-login-style.css' );
}
add_action( 'login_enqueue_scripts', 'ysq_custom_login_stylesheet' );

/**
 * Mengubah URL logo di halaman login agar mengarah ke halaman utama situs.
 */
function ysq_login_logo_url() {
    return home_url();
}
add_filter( 'login_headerurl', 'ysq_login_logo_url' );

/**
 * Mengubah teks 'title' pada logo di halaman login.
 */
function ysq_login_logo_url_title() {
    return get_bloginfo( 'name' );
}
add_filter( 'login_headertext', 'ysq_login_logo_url_title' );

