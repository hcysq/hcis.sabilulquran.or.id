<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<header class="site-header">
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
        $is_admin_logged_in   = function_exists('ysq_admin_is_logged_in') && ysq_admin_is_logged_in();
        $is_wp_user_logged_in = is_user_logged_in();

        $login_button_text = trim(get_theme_mod('ysq_login_button_text', 'Masuk Pegawai'));
        $login_button_url  = trim(get_theme_mod('ysq_login_button_url', home_url('/masuk')));

        $main_button_text = trim(get_theme_mod('ysq_main_site_button_text', 'Kembali ke Situs Utama'));
        $main_button_url  = trim(get_theme_mod('ysq_main_site_button_url', 'https://sabilulquran.or.id'));

        $show_login_button = $show_buttons && !$is_wp_user_logged_in && !$is_admin_logged_in && '' !== $login_button_text;
        $show_main_button  = $show_buttons && '' !== $main_button_text && '' !== $main_button_url;
        $is_homepage       = is_front_page() || is_home();

        if ($is_homepage && ($show_login_button || $show_main_button)) :
        ?>
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
    </div>
</header>

<main class="site-main">
