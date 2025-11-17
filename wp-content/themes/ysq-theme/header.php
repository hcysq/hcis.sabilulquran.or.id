<?php
$default_color_mode        = get_theme_mod('ysq_default_color_mode', 'system');
$header_behavior           = get_theme_mod('ysq_header_behavior', 'sticky');
$enable_color_mode_toggle  = get_theme_mod('ysq_enable_color_mode_toggle', false);
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?> data-default-color-mode="<?php echo esc_attr($default_color_mode); ?>">
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<header class="site-header site-header--behavior-<?php echo esc_attr($header_behavior); ?>" data-header-behavior="<?php echo esc_attr($header_behavior); ?>">
    <div class="header-container">
        <div class="site-branding">
            <div class="site-logo">
                <?php
                if (has_custom_logo()) {
                    the_custom_logo();
                } else { ?>
                    <a href="<?php echo esc_url(home_url('/')); ?>">
                        <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/logo.png'); ?>" alt="<?php bloginfo('name'); ?>">
                    </a>
                <?php } ?>
            </div>
            <h1 class="site-title">
                <?php echo esc_html(get_theme_mod('ysq_site_title', 'Sabilul Qur\'an • HRIS')); ?>
            </h1>
        </div>

        <?php
        $show_buttons         = get_theme_mod('ysq_show_header_buttons', true);
        $is_wp_user_logged_in = is_user_logged_in();

        $login_button_text = trim(get_theme_mod('ysq_login_button_text', 'Masuk Pegawai'));
        $login_button_url  = trim(get_theme_mod('ysq_login_button_url', home_url('/masuk')));

        $main_button_text = trim(get_theme_mod('ysq_main_site_button_text', 'Kembali ke Situs Utama'));
        $main_button_url  = trim(get_theme_mod('ysq_main_site_button_url', 'https://sabilulquran.or.id'));

        $identity = null;
        if (class_exists('HCISYSQ\\Auth') && method_exists('HCISYSQ\\Auth', 'current_identity')) {
            $identity = HCISYSQ\Auth::current_identity();
        }

        $is_hcis_logged_in = !empty($identity);

        $show_login_button = $show_buttons && !$is_wp_user_logged_in && !$is_hcis_logged_in && '' !== $login_button_text;
        $show_main_button  = $show_buttons && '' !== $main_button_text && '' !== $main_button_url;

        if ($show_login_button || $show_main_button || $enable_color_mode_toggle) :
        ?>
            <div class="header-actions">
                <?php if ($show_login_button || $show_main_button) : ?>
                    <nav class="main-navigation">
                        <?php if ($show_login_button) : ?>
                            <a href="<?php echo esc_url($login_button_url); ?>" class="btn-primary">
                                <?php echo esc_html($login_button_text); ?>
                            </a>
                        <?php endif; ?>

                        <?php if ($show_main_button) : ?>
                            <a href="<?php echo esc_url($main_button_url); ?>" target="_blank" rel="noopener noreferrer">
                                <?php echo esc_html($main_button_text); ?>
                            </a>
                        <?php endif; ?>
                    </nav>
                <?php endif; ?>

                <?php if ($enable_color_mode_toggle) : ?>
                    <button type="button" class="color-mode-toggle" data-color-mode-toggle aria-pressed="false" data-label-system="<?php esc_attr_e('Ikuti Sistem', 'ysq'); ?>" data-label-light="<?php esc_attr_e('Mode Terang', 'ysq'); ?>" data-label-dark="<?php esc_attr_e('Mode Gelap', 'ysq'); ?>">
                        <span class="color-mode-toggle__icon" aria-hidden="true"></span>
                        <span class="color-mode-toggle__label" data-color-mode-toggle-label><?php esc_html_e('Ikuti Sistem', 'ysq'); ?></span>
                    </button>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</header>

<?php
$marquee_items   = array();
$marquee_raw     = get_option('hcisysq_home_marquee_text', '');
$marquee_options = get_option('hcisysq_home_marquee_options', array());

if (!is_array($marquee_options)) {
    $marquee_options = array();
}

$marquee_speed   = isset($marquee_options['speed']) ? floatval($marquee_options['speed']) : 1.0;
$marquee_speed   = min(max($marquee_speed, 0.5), 3.0);
$marquee_gap     = isset($marquee_options['gap']) ? intval($marquee_options['gap']) : 32;
$marquee_gap     = min(max($marquee_gap, 8), 160);
$marquee_letters = isset($marquee_options['letter_spacing']) ? floatval($marquee_options['letter_spacing']) : 0;
$marquee_letters = min(max($marquee_letters, 0), 10);
$marquee_repeat  = isset($marquee_options['duplicates']) ? intval($marquee_options['duplicates']) : 2;
$marquee_repeat  = min(max($marquee_repeat, 1), 6);
$marquee_bg_raw  = isset($marquee_options['background']) ? trim((string) $marquee_options['background']) : '';
$marquee_bg_hex  = sanitize_hex_color($marquee_bg_raw);
$marquee_bg      = $marquee_bg_hex ? $marquee_bg_hex : ($marquee_bg_raw !== '' ? $marquee_bg_raw : 'rgba(255,255,255,0.5)');

if (is_string($marquee_raw)) {
    $marquee_raw = trim($marquee_raw);
}

if (!empty($marquee_raw)) {
    $marquee_allowed = array(
        'p'   => array(),
        'br'  => array(),
        'ul'  => array(),
        'ol'  => array(),
        'li'  => array(),
        'span'=> array('style' => true),
        'strong' => array(),
        'em'     => array(),
    );

    $marquee_html = wp_kses($marquee_raw, $marquee_allowed);

    if (preg_match_all('/<li[^>]*>(.*?)<\/li>/is', $marquee_html, $matches)) {
        foreach ($matches[1] as $segment) {
            $text = trim(wp_strip_all_tags($segment));
            if ($text !== '') {
                $marquee_items[] = html_entity_decode($text, ENT_QUOTES, get_bloginfo('charset'));
            }
        }
    }

    if (empty($marquee_items)) {
        $plain = trim(wp_strip_all_tags($marquee_html));
        if ($plain !== '') {
            $segments = preg_split('/\r\n|\r|\n/', $plain);
            if (is_array($segments)) {
                foreach ($segments as $segment) {
                    $segment = trim($segment);
                    if ($segment !== '') {
                        $marquee_items[] = $segment;
                    }
                }
            }

            if (empty($marquee_items)) {
                $marquee_items[] = $plain;
            }
        }
    }
}

if (!empty($marquee_items)) {
    $marquee_letter_value = number_format($marquee_letters, 1, '.', '');
    $marquee_style_parts  = array(
        '--marquee-background:' . $marquee_bg,
        '--marquee-gap:' . $marquee_gap . 'px',
        '--marquee-letter-spacing:' . $marquee_letter_value . 'px',
        '--marquee-speed:' . $marquee_speed,
    );
    $marquee_style_attr = implode(';', $marquee_style_parts) . ';';
}

if ((is_front_page() || is_home()) && !empty($marquee_items)) :
    ?>
    <div class="site-marquee" role="region" aria-label="<?php esc_attr_e('Informasi berjalan', 'ysq'); ?>" style="<?php echo esc_attr($marquee_style_attr); ?>">
        <div class="site-marquee__track" aria-live="polite">
            <?php for ($i = 0; $i < $marquee_repeat; $i++) : ?>
                <div class="site-marquee__segment"<?php echo $i > 0 ? ' aria-hidden="true"' : ''; ?>>
                    <?php foreach ($marquee_items as $item) : ?>
                        <span class="site-marquee__item"><?php echo esc_html($item); ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endfor; ?>
        </div>
    </div>
<?php endif; ?>

<main id="main-content" class="site-main" tabindex="-1">
