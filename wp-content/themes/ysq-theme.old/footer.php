</main>

<footer class="site-footer">
    <div class="footer-container">
        <div class="footer-info">
            <div class="footer-section">
                <h3><?php echo wp_kses_post(get_theme_mod('ysq_footer_col1_title', 'Info')); ?></h3>
                <div class="footer-content">
                    <?php echo wp_kses_post(get_theme_mod('ysq_footer_col1_content', '<ul><li>Nasihat</li><li>Karir</li><li>Program Orang Tua Asuh</li><li>Pengaduan</li><li>Program Qurban</li></ul>')); ?>
                </div>
            </div>

            <div class="footer-section">
                <h3><?php echo wp_kses_post(get_theme_mod('ysq_footer_col2_title', 'Kontak')); ?></h3>
                <div class="footer-content">
                    <?php echo wp_kses_post(get_theme_mod('ysq_footer_col2_content', '<ul><li><a href="mailto:Email.Sabilulquran@gmail.com">Email Sabilulquran</a></li><li>Telp: 0851-7536-2016</li></ul>')); ?>
                </div>
            </div>

            <div class="footer-section">
                <h3><?php echo wp_kses_post(get_theme_mod('ysq_footer_col3_title', 'Lokasi')); ?></h3>
                <div class="footer-content">
                    <?php echo wp_kses_post(get_theme_mod('ysq_footer_col3_content', '<p>Tambahkan konten atau embed Google Maps</p>')); ?>
                </div>
            </div>

            <div class="footer-section footer-section-menu">
                <h3><?php echo wp_kses_post(get_theme_mod('ysq_footer_col4_title', 'Lainnya')); ?></h3>
                <div class="footer-content">
                    <?php
                    if (has_nav_menu('footer-col4')) {
                        wp_nav_menu(array(
                            'theme_location' => 'footer-col4',
                            'container'      => false,
                            'menu_class'     => 'footer-menu',
                            'depth'          => 1,
                        ));
                    }

                    $footer_col4_content = get_theme_mod('ysq_footer_col4_content', '<p>Tambahkan konten untuk kolom 4</p>');

                    if (!empty($footer_col4_content)) {
                        echo '<div class="footer-custom-content">';
                        echo wp_kses_post($footer_col4_content);
                        echo '</div>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <div class="footer-bottom">
        <div class="footer-copyright">
            <p><?php echo wp_kses_post(get_theme_mod('ysq_footer_ahu_text', 'AHU – 0033732. AH. 01. 04. Tahun 2015 | Tanggal 28 Desember 2015')); ?></p>
            <p>&copy; <?php echo ysq_get_current_year(); ?> <?php echo esc_html(get_theme_mod('ysq_footer_copyright_text', 'Yayasan Sabilul Qur\'an • HRIS Internal v1.0')); ?></p>
        </div>
    </div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
