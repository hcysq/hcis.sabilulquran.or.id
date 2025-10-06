</main>

<footer id="site-footer" class="ysq-footer">
    <div class="ysq-footer__container">
        <?php
        $footer_columns = array(
            1 => array(
                'title_mod'       => 'ysq_footer_col1_title',
                'content_mod'     => 'ysq_footer_col1_content',
                'title_default'   => 'Info',
                'content_default' => '<ul><li>Nasihat</li><li>Karir</li><li>Program Orang Tua Asuh</li><li>Pengaduan</li><li>Program Qurban</li></ul>',
            ),
            2 => array(
                'title_mod'       => 'ysq_footer_col2_title',
                'content_mod'     => 'ysq_footer_col2_content',
                'title_default'   => 'Kontak',
                'content_default' => '<ul><li><a href="mailto:Email.Sabilulquran@gmail.com">Email Sabilulquran</a></li><li>Telp: 0851-7536-2016</li></ul>',
            ),
            3 => array(
                'title_mod'       => 'ysq_footer_col3_title',
                'content_mod'     => 'ysq_footer_col3_content',
                'title_default'   => 'Lokasi',
                'content_default' => '<p>Tambahkan konten atau embed Google Maps</p>',
            ),
            4 => array(
                'title_mod'       => 'ysq_footer_col4_title',
                'content_mod'     => 'ysq_footer_col4_content',
                'title_default'   => 'Lainnya',
                'content_default' => '<p>Tambahkan konten untuk kolom 4</p>',
            ),
        );

        $footer_data = array();

        foreach ($footer_columns as $index => $column) {
            $raw_title   = get_theme_mod($column['title_mod'], $column['title_default']);
            $raw_content = get_theme_mod($column['content_mod'], $column['content_default']);

            $title        = is_string($raw_title) ? $raw_title : '';
            $content      = is_string($raw_content) ? $raw_content : '';
            $has_title    = '' !== trim(wp_strip_all_tags($title));
            $has_menu     = (4 === $index) && has_nav_menu('footer-col4');
            $menu_markup  = '';

            if ($has_menu) {
                $menu_markup = wp_nav_menu(
                    array(
                        'theme_location' => 'footer-col4',
                        'container'      => false,
                        'menu_class'     => 'ysq-footer__menu',
                        'depth'          => 1,
                        'fallback_cb'    => '__return_empty_string',
                        'echo'           => false,
                    )
                );
            }

            $has_content = '' !== trim($content) || '' !== $menu_markup;

            $footer_data[$index] = array(
                'title'       => $title,
                'content'     => $content,
                'menu_markup' => $menu_markup,
                'has_title'   => $has_title,
                'has_content' => $has_content,
            );
        }
        ?>

        <div class="ysq-footer__grid" role="presentation">
            <?php foreach ($footer_data as $index => $column) :
                $title_classes = array(
                    'ysq-footer__title',
                    'ysq-footer__title--col' . $index,
                );

                if (!$column['has_title']) {
                    $title_classes[] = 'ysq-footer__title--empty';
                }
                ?>
                <div class="<?php echo esc_attr(implode(' ', array_map('sanitize_html_class', $title_classes))); ?>"<?php echo $column['has_title'] ? '' : ' aria-hidden="true"'; ?>>
                    <?php if ($column['has_title']) : ?>
                        <span class="ysq-footer__title-text"><?php echo esc_html($column['title']); ?></span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <?php foreach ($footer_data as $index => $column) :
                $content_classes = array(
                    'ysq-footer__content',
                    'ysq-footer__content--col' . $index,
                );

                if (!$column['has_content']) {
                    $content_classes[] = 'ysq-footer__content--empty';
                }
                ?>
                <div class="<?php echo esc_attr(implode(' ', array_map('sanitize_html_class', $content_classes))); ?>"<?php echo $column['has_content'] ? '' : ' aria-hidden="true"'; ?>>
                    <?php if ($column['has_content']) : ?>
                        <?php
                        if ('' !== $column['menu_markup']) {
                            echo $column['menu_markup']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                        }

                        if ('' !== trim($column['content'])) {
                            echo '<div class="ysq-footer__custom-content">' . wp_kses_post($column['content']) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                        }
                        ?>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="ysq-footer__bottom">
        <div class="ysq-footer__bottom-inner">
            <p class="ysq-footer__bottom-text"><?php echo wp_kses_post(get_theme_mod('ysq_footer_ahu_text', 'AHU – 0033732. AH. 01. 04. Tahun 2015 | Tanggal 28 Desember 2015')); ?></p>
            <p class="ysq-footer__bottom-text">&copy; <?php echo ysq_get_current_year(); ?> <?php echo esc_html(get_theme_mod('ysq_footer_copyright_text', 'Yayasan Sabilul Qur\'an • HRIS Internal v1.2')); ?></p>
        </div>
    </div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
