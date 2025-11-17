<?php
/**
 * YSQ Theme Functions
 *
 * @package YSQ
 */

if (!defined('ABSPATH')) {
    exit;
}

function ysq_sanitize_checkbox($checked) {
    return (bool) $checked;
}

function ysq_sanitize_select($input, $setting) {
    $input   = is_string($input) ? sanitize_key($input) : '';
    $control = $setting->manager->get_control($setting->id);

    if ($control && isset($control->choices[$input])) {
        return $input;
    }

    return $setting->default;
}

function ysq_sanitize_int_range($value, $setting) {
    $value = intval($value);
    $control = $setting->manager->get_control($setting->id);
    $attrs = $control && isset($control->input_attrs) ? $control->input_attrs : array();
    $min   = isset($attrs['min']) ? intval($attrs['min']) : $value;
    $max   = isset($attrs['max']) ? intval($attrs['max']) : $value;

    if ($value < $min) {
        return $min;
    }

    if ($value > $max) {
        return $max;
    }

    return $value;
}

function ysq_sanitize_font_stack($value) {
    $value = is_string($value) ? wp_strip_all_tags($value) : '';

    return $value !== '' ? $value : 'system-ui, "Segoe UI", Roboto, sans-serif';
}

function ysq_get_contrast_color($hex_color) {
    $hex = sanitize_hex_color($hex_color);
    if (!$hex) {
        return '#ffffff';
    }

    $hex = ltrim($hex, '#');
    $red = hexdec(substr($hex, 0, 2));
    $green = hexdec(substr($hex, 2, 2));
    $blue = hexdec(substr($hex, 4, 2));
    $luma = 0.299 * $red + 0.587 * $green + 0.114 * $blue;

    return $luma >= 186 ? '#0f172a' : '#ffffff';
}

function ysq_adjust_color_brightness($hex_color, $percent = 0) {
    $hex = sanitize_hex_color($hex_color);

    if (!$hex) {
        return '#175887';
    }

    $percent = max(-100, min(100, floatval($percent)));
    $hex = ltrim($hex, '#');

    $red = hexdec(substr($hex, 0, 2));
    $green = hexdec(substr($hex, 2, 2));
    $blue = hexdec(substr($hex, 4, 2));

    $adjust = function ($channel) use ($percent) {
        if ($percent === 0) {
            return intval($channel);
        }

        $channel = intval($channel);
        $delta   = ($percent / 100) * (255 - $channel);

        if ($percent < 0) {
            $delta = ($percent / 100) * $channel;
        }

        return max(0, min(255, intval(round($channel + $delta))));
    };

    $red   = $adjust($red);
    $green = $adjust($green);
    $blue  = $adjust($blue);

    return sprintf('#%02x%02x%02x', $red, $green, $blue);
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
    wp_enqueue_style('ysq-style', get_stylesheet_uri(), array(), '1.4');
    wp_enqueue_style(
        'ysq-footer',
        get_stylesheet_directory_uri() . '/assets/css/ysq-footer.css',
        array('ysq-style'),
        '1.1'
    );
    wp_enqueue_script('ysq-theme', get_template_directory_uri() . '/assets/js/theme.js', array(), '1.1.0', true);
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
        'default'           => '16',
        'transport'         => 'refresh',
        'sanitize_callback' => 'absint',
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
        'default'           => '24',
        'transport'         => 'refresh',
        'sanitize_callback' => 'absint',
    ));

    $wp_customize->add_setting('ysq_font_stack', array(
        'default'           => 'system-ui, "Segoe UI", Roboto, sans-serif',
        'transport'         => 'refresh',
        'sanitize_callback' => 'ysq_sanitize_font_stack',
    ));

    $wp_customize->add_control('ysq_font_stack', array(
        'label'       => __('Font Stack', 'ysq'),
        'description' => __('Contoh: system-ui, "Segoe UI", Roboto, sans-serif', 'ysq'),
        'section'     => 'ysq_typography_section',
        'type'        => 'text',
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
        'default'           => '#ffffff',
        'transport'         => 'refresh',
        'sanitize_callback' => 'sanitize_hex_color',
    ));

    if (class_exists('WP_Customize_Color_Control')) {
        $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'ysq_header_bg_color', array(
            'label'   => __('Header Background Color', 'ysq'),
            'section' => 'ysq_header_section',
        )));
    }

    $wp_customize->add_setting('ysq_header_bg_opacity', array(
        'default'           => '100',
        'transport'         => 'refresh',
        'sanitize_callback' => 'absint',
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

    $wp_customize->add_setting('ysq_header_behavior', array(
        'default'           => 'sticky',
        'transport'         => 'refresh',
        'sanitize_callback' => 'ysq_sanitize_select',
    ));

    $wp_customize->add_control('ysq_header_behavior', array(
        'label'       => __('Header Behavior', 'ysq'),
        'description' => __('Atur apakah header selalu menempel di atas atau statis.', 'ysq'),
        'section'     => 'ysq_header_section',
        'type'        => 'select',
        'choices'     => array(
            'sticky'            => __('Sticky solid', 'ysq'),
            'sticky_transparent'=> __('Sticky transparan dengan blur', 'ysq'),
            'static'            => __('Statis (tidak mengikuti scroll)', 'ysq'),
        ),
    ));

    $wp_customize->add_section('ysq_branding_section', array(
        'title'    => __('Branding Card', 'ysq'),
        'priority' => 31,
    ));

    $wp_customize->add_setting('ysq_show_branding_card', array(
        'default'           => true,
        'transport'         => 'refresh',
        'sanitize_callback' => 'ysq_sanitize_checkbox',
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

    $wp_customize->add_setting('ysq_primary_color', array(
        'default'           => '#175887',
        'transport'         => 'refresh',
        'sanitize_callback' => 'sanitize_hex_color',
    ));

    if (class_exists('WP_Customize_Color_Control')) {
        $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'ysq_primary_color', array(
            'label'       => __('Primary Brand Color', 'ysq'),
            'description' => __('Digunakan untuk tombol dan tautan utama.', 'ysq'),
            'section'     => 'ysq_branding_section',
        )));
    }

    $wp_customize->add_setting('ysq_brand_color', array(
        'default'           => '#175887',
        'transport'         => 'refresh',
        'sanitize_callback' => 'sanitize_hex_color',
    ));

    if (class_exists('WP_Customize_Color_Control')) {
        $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'ysq_brand_color', array(
            'label'       => __('Brand Color', 'ysq'),
            'description' => __('Warna untuk card branding dan link', 'ysq'),
            'section'     => 'ysq_branding_section',
        )));
    }

    $wp_customize->add_setting('ysq_brand_color_opacity', array(
        'default'           => '100',
        'transport'         => 'refresh',
        'sanitize_callback' => 'absint',
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
        'default'           => true,
        'transport'         => 'refresh',
        'sanitize_callback' => 'ysq_sanitize_checkbox',
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
        'default'           => '#f8f9fa',
        'transport'         => 'refresh',
        'sanitize_callback' => 'sanitize_hex_color',
    ));

    if (class_exists('WP_Customize_Color_Control')) {
        $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'ysq_footer_bg_color', array(
            'label'   => __('Footer Background Color', 'ysq'),
            'section' => 'ysq_footer_section',
        )));
    }

    $wp_customize->add_setting('ysq_footer_bg_opacity', array(
        'default'           => '100',
        'transport'         => 'refresh',
        'sanitize_callback' => 'absint',
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
        'default'           => '#2f7e20',
        'transport'         => 'refresh',
        'sanitize_callback' => 'sanitize_hex_color',
    ));

    if (class_exists('WP_Customize_Color_Control')) {
        $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'ysq_footer_bottom_bg_color', array(
            'label'   => __('Footer Bottom Background Color', 'ysq'),
            'section' => 'ysq_footer_section',
        )));
    }

    $wp_customize->add_setting('ysq_footer_bottom_bg_opacity', array(
        'default'           => '100',
        'transport'         => 'refresh',
        'sanitize_callback' => 'absint',
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
        'default'           => '#ffffff',
        'transport'         => 'refresh',
        'sanitize_callback' => 'sanitize_hex_color',
    ));

    if (class_exists('WP_Customize_Color_Control')) {
        $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'ysq_footer_bottom_text_color', array(
            'label'   => __('Footer Bottom Text Color', 'ysq'),
            'section' => 'ysq_footer_section',
        )));
    }

    $wp_customize->add_setting('ysq_footer_columns_count', array(
        'default'           => 4,
        'transport'         => 'refresh',
        'sanitize_callback' => 'ysq_sanitize_int_range',
    ));

    $wp_customize->add_control('ysq_footer_columns_count', array(
        'label'       => __('Jumlah Kolom Footer', 'ysq'),
        'description' => __('Atur 1-4 kolom informasi yang tampil.', 'ysq'),
        'section'     => 'ysq_footer_section',
        'type'        => 'number',
        'input_attrs' => array(
            'min' => 1,
            'max' => 4,
        ),
    ));

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

    $wp_customize->add_section('ysq_color_mode_section', array(
        'title'    => __('Mode Warna', 'ysq'),
        'priority' => 45,
    ));

    $wp_customize->add_setting('ysq_enable_color_mode_toggle', array(
        'default'           => false,
        'transport'         => 'refresh',
        'sanitize_callback' => 'ysq_sanitize_checkbox',
    ));

    $wp_customize->add_control('ysq_enable_color_mode_toggle', array(
        'label'       => __('Tampilkan tombol mode gelap', 'ysq'),
        'description' => __('Menambahkan toggle light/dark di header situs.', 'ysq'),
        'section'     => 'ysq_color_mode_section',
        'type'        => 'checkbox',
    ));

    $wp_customize->add_setting('ysq_default_color_mode', array(
        'default'           => 'system',
        'transport'         => 'refresh',
        'sanitize_callback' => 'ysq_sanitize_select',
    ));

    $wp_customize->add_control('ysq_default_color_mode', array(
        'label'   => __('Default mode warna', 'ysq'),
        'section' => 'ysq_color_mode_section',
        'type'    => 'select',
        'choices' => array(
            'system' => __('Ikuti preferensi perangkat', 'ysq'),
            'light'  => __('Selalu terang', 'ysq'),
            'dark'   => __('Selalu gelap', 'ysq'),
        ),
    ));
}
add_action('customize_register', 'ysq_customize_register');

function ysq_custom_styles() {
    $base_font_size     = get_theme_mod('ysq_base_font_size', '16');
    $heading_font_size  = get_theme_mod('ysq_heading_font_size', '24');
    $font_stack         = get_theme_mod('ysq_font_stack', 'system-ui, "Segoe UI", Roboto, sans-serif');

    $header_bg          = get_theme_mod('ysq_header_bg_color', '#ffffff');
    $header_bg_opacity  = get_theme_mod('ysq_header_bg_opacity', '100');
    $header_bg_rgba     = ysq_hex_to_rgba($header_bg, floatval($header_bg_opacity) / 100);

    $brand_color        = get_theme_mod('ysq_brand_color', '#175887');
    $brand_color_op     = get_theme_mod('ysq_brand_color_opacity', '100');
    $brand_color_rgba   = ysq_hex_to_rgba($brand_color, floatval($brand_color_op) / 100);

    $primary_color      = get_theme_mod('ysq_primary_color', '#175887');
    $primary_hover      = ysq_adjust_color_brightness($primary_color, -15);
    $primary_contrast   = ysq_get_contrast_color($primary_color);

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
            --ysq-font-base: <?php echo esc_attr($font_stack); ?>;
            --ysq-font-size-base: <?php echo esc_attr($base_font_size); ?>px;
            --ysq-heading-base-size: <?php echo esc_attr($heading_font_size); ?>px;
            --ysq-header-bg: <?php echo esc_attr($header_bg_rgba); ?>;
            --ysq-header-bg-transparent: <?php echo esc_attr($header_bg_half); ?>;
            --ysq-color-primary: <?php echo esc_attr($primary_color); ?>;
            --ysq-color-primary-contrast: <?php echo esc_attr($primary_contrast); ?>;
            --ysq-color-primary-strong: <?php echo esc_attr($primary_hover); ?>;
            --ysq-footer-background: <?php echo esc_attr($footer_bg_rgba); ?>;
            --ysq-footer-bottom-background: <?php echo esc_attr($footer_bottom_bg_rgba); ?>;
            --ysq-footer-bottom-text: <?php echo esc_attr($footer_bottom_text_color); ?>;
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

