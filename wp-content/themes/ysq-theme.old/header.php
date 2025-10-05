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

        <nav class="main-navigation">
            <a href="<?php echo esc_url(get_theme_mod('ysq_login_button_url', home_url('/masuk'))); ?>" class="btn-primary">
                <?php echo esc_html(get_theme_mod('ysq_login_button_text', 'Masuk Pegawai')); ?>
            </a>
            <a href="<?php echo esc_url(get_theme_mod('ysq_main_site_button_url', 'https://sabilulquran.or.id')); ?>" target="_blank">
                <?php echo esc_html(get_theme_mod('ysq_main_site_button_text', 'Kembali ke Situs Utama')); ?>
            </a>
        </nav>
    </div>
</header>

<main class="site-main">
