<?php
/**
 * Accessible search form.
 *
 * @package YSQ
 */

if (!defined('ABSPATH')) {
    exit;
}

$unique_id = uniqid('ysq-search-');
?>

<form role="search" method="get" class="search-form" action="<?php echo esc_url(home_url('/')); ?>">
    <label for="<?php echo esc_attr($unique_id); ?>" class="search-form__label">
        <?php esc_html_e('Cari kata kunci', 'ysq'); ?>
    </label>
    <div class="search-form__controls">
        <input type="search" id="<?php echo esc_attr($unique_id); ?>" class="search-form__input" name="s" value="<?php echo esc_attr(get_search_query()); ?>" placeholder="<?php esc_attr_e('Masukkan kata kunci...', 'ysq'); ?>" />
        <button type="submit" class="btn-primary search-form__submit">
            <?php esc_html_e('Cari', 'ysq'); ?>
        </button>
    </div>
</form>
