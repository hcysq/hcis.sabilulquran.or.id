</main>

<footer class="site-footer">
    <div class="footer-container">
        <div class="footer-grid" role="presentation">
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

            foreach ($footer_columns as $index => $column) {
                $title   = get_theme_mod($column['title_mod'], $column['title_default']);
                $content = get_theme_mod($column['content_mod'], $column['content_default']);

                $has_menu    = (4 === $index) && has_nav_menu('footer-col4');
                $has_content = '' !== trim($content) || (4 === $index && $has_menu);
                $has_title   = '' !== trim(wp_strip_all_tags($title));

                $title_classes = array(
                    'footer-grid__cell',
                    'footer-grid__cell--title',
                    'footer-grid__cell--col' . $index,
                );

                $content_classes = array(
                    'footer-grid__cell',
                    'footer-grid__cell--content',
                    'footer-grid__cell--col' . $index,
                );

                if (!$has_title) {
                    $title_classes[] = 'footer-grid__cell--empty';
                }

                if (!$has_content) {
                    $content_classes[] = 'footer-grid__cell--empty';
                }

                $title_classes   = array_map('sanitize_html_class', $title_classes);
                $content_classes = array_map('sanitize_html_class', $content_classes);
                ?>
                <div class="<?php echo esc_attr(implode(' ', $title_classes)); ?>"<?php echo $has_title ? '' : ' aria-hidden="true"'; ?>>
                    <?php if ($has_title) : ?>
                        <h3 class="footer-grid__title"><?php echo wp_kses_post($title); ?></h3>
                    <?php else : ?>
                        <span class="footer-grid__placeholder" aria-hidden="true"></span>
                    <?php endif; ?>
                </div>

                <div class="<?php echo esc_attr(implode(' ', $content_classes)); ?>"<?php echo $has_content ? '' : ' aria-hidden="true"'; ?>>
                    <?php if ($has_content) : ?>
                        <div class="footer-grid__content">
                            <?php
                            if (4 === $index) {
                                if ($has_menu) {
                                    echo wp_nav_menu(
                                        array(
                                            'theme_location' => 'footer-col4',
                                            'container'      => false,
                                            'menu_class'     => 'footer-menu',
                                            'depth'          => 1,
                                            'fallback_cb'    => '__return_empty_string',
                                            'echo'           => false,
                                        )
                                    );
                                }

                                if ('' !== trim($content)) {
                                    echo '<div class="footer-custom-content">' . wp_kses_post($content) . '</div>';
                                }
                            } else {
                                echo wp_kses_post($content);
                            }
                            ?>
                        </div>
                    <?php else : ?>
                        <div class="footer-grid__content footer-grid__content--empty" aria-hidden="true"></div>
                    <?php endif; ?>
                </div>
                <?php
            }
            ?>
        </div>
    </div>

    <div class="footer-bottom">
        <div class="footer-bottom-inner">
            <p><?php echo wp_kses_post(get_theme_mod('ysq_footer_ahu_text', 'AHU – 0033732. AH. 01. 04. Tahun 2015 | Tanggal 28 Desember 2015')); ?></p>
            <p>&copy; <?php echo ysq_get_current_year(); ?> <?php echo esc_html(get_theme_mod('ysq_footer_copyright_text', 'Yayasan Sabilul Qur\'an • HRIS Internal v1.2')); ?></p>
        </div>
    </div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
