<?php
/**
 * Sidebar Template
 *
 * @package YSQ
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<aside class="widget-area" role="complementary" aria-label="<?php esc_attr_e('Area sidebar', 'ysq'); ?>">
    <?php if (has_nav_menu('primary')) : ?>
        <nav class="sidebar-navigation" aria-label="<?php esc_attr_e('Navigasi tambahan', 'ysq'); ?>">
            <?php
            wp_nav_menu(
                array(
                    'theme_location' => 'primary',
                    'menu_class'     => 'sidebar-menu',
                    'container'      => false,
                    'depth'          => 1,
                    'fallback_cb'    => '__return_empty_string',
                )
            );
            ?>
        </nav>
    <?php endif; ?>

    <?php if (is_active_sidebar('sidebar-1')) : ?>
        <div class="widget-stack" aria-label="<?php esc_attr_e('Widget tambahan', 'ysq'); ?>">
            <?php dynamic_sidebar('sidebar-1'); ?>
        </div>
    <?php endif; ?>
</aside>
