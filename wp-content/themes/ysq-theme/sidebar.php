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

<aside class="widget-area">
    <?php
    if (is_active_sidebar('sidebar-1')) {
        dynamic_sidebar('sidebar-1');
    }
    ?>
</aside>
