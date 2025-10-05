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
    wp_enqueue_style('ysq-style', get_stylesheet_uri(), array(), '1.0');
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
        $content_setting = sprintf('ysq_footer_col%d_content', $index);
        $font_setting    = sprintf('ysq_footer_col%d_font_size', $index);
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
        'default'   => 'Yayasan Sabilul Qur\'an • HRIS Internal v1.0',
        'transport' => 'refresh',
    ));

    $wp_customize->add_control('ysq_footer_copyright_text', array(
        'label'   => __('Footer Bottom - Copyright Text', 'ysq'),
        'section' => 'ysq_footer_section',
        'type'    => 'text',
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

    $footer_bottom_bg         = get_theme_mod('ysq_footer_bottom_bg_color', '#2d5f5d');
    $footer_bottom_bg_opacity = get_theme_mod('ysq_footer_bottom_bg_opacity', '100');
    $footer_bottom_bg_rgba    = ysq_hex_to_rgba($footer_bottom_bg, floatval($footer_bottom_bg_opacity) / 100);

    $show_branding = get_theme_mod('ysq_show_branding_card', true);
    $show_buttons  = get_theme_mod('ysq_show_header_buttons', true);

    $footer_columns = array(
        1 => array(
            'width'      => get_theme_mod('ysq_footer_col1_width', '25'),
            'title_size' => get_theme_mod('ysq_footer_col1_title_font_size', '18'),
            'font_size'  => get_theme_mod('ysq_footer_col1_font_size', '14'),
            'color'      => get_theme_mod('ysq_footer_col1_color', '#333333'),
            'opacity'    => get_theme_mod('ysq_footer_col1_opacity', '100'),
        ),
        2 => array(
            'width'      => get_theme_mod('ysq_footer_col2_width', '25'),
            'title_size' => get_theme_mod('ysq_footer_col2_title_font_size', '18'),
            'font_size'  => get_theme_mod('ysq_footer_col2_font_size', '14'),
            'color'      => get_theme_mod('ysq_footer_col2_color', '#333333'),
            'opacity'    => get_theme_mod('ysq_footer_col2_opacity', '100'),
        ),
        3 => array(
            'width'      => get_theme_mod('ysq_footer_col3_width', '25'),
            'title_size' => get_theme_mod('ysq_footer_col3_title_font_size', '18'),
            'font_size'  => get_theme_mod('ysq_footer_col3_font_size', '14'),
            'color'      => get_theme_mod('ysq_footer_col3_color', '#333333'),
            'opacity'    => get_theme_mod('ysq_footer_col3_opacity', '100'),
        ),
        4 => array(
            'width'      => get_theme_mod('ysq_footer_col4_width', '25'),
            'title_size' => get_theme_mod('ysq_footer_col4_title_font_size', '18'),
            'font_size'  => get_theme_mod('ysq_footer_col4_font_size', '14'),
            'color'      => get_theme_mod('ysq_footer_col4_color', '#333333'),
            'opacity'    => get_theme_mod('ysq_footer_col4_opacity', '100'),
        ),
    );
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
        }

        .footer-bottom {
            background-color: <?php echo esc_attr($footer_bottom_bg_rgba); ?> !important;
        }

<?php foreach ($footer_columns as $index => $column) :
        $color_rgba = ysq_hex_to_rgba($column['color'], floatval($column['opacity']) / 100);
?>
        .footer-section:nth-child(<?php echo esc_attr($index); ?>) {
            flex: 0 0 <?php echo esc_attr($column['width']); ?>% !important;
            max-width: <?php echo esc_attr($column['width']); ?>% !important;
            font-size: <?php echo esc_attr($column['font_size']); ?>px !important;
            color: <?php echo esc_attr($color_rgba); ?> !important;
        }

        .footer-section:nth-child(<?php echo esc_attr($index); ?>) h3 {
            font-size: <?php echo esc_attr($column['title_size']); ?>px !important;
        }

        .footer-section:nth-child(<?php echo esc_attr($index); ?>) a,
        .footer-section:nth-child(<?php echo esc_attr($index); ?>) .footer-menu a {
            color: inherit;
        }
<?php endforeach; ?>

        @media (max-width: 768px) {
            .footer-section {
                flex: 0 0 100% !important;
                max-width: 100% !important;
            }
        }
    </style>
    <?php
}
add_action('wp_head', 'ysq_custom_styles');

