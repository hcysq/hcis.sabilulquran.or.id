<?php
/**
 * Custom template tags for this theme
 *
 * @package YSQ
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('ysq_posted_on')) {
    function ysq_posted_on() {
        $time_string = '<time class="entry-date published updated" datetime="%1$s">%2$s</time>';

        $time_string = sprintf(
            $time_string,
            esc_attr(get_the_date(DATE_W3C)),
            esc_html(get_the_date())
        );

        printf(
            '<span class="posted-on">%s</span>',
            $time_string
        );
    }
}

if (!function_exists('ysq_posted_by')) {
    function ysq_posted_by() {
        printf(
            '<span class="byline">%s <a href="%s">%s</a></span>',
            esc_html__('oleh', 'ysq'),
            esc_url(get_author_posts_url(get_the_author_meta('ID'))),
            esc_html(get_the_author())
        );
    }
}
