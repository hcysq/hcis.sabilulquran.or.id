</main>

<footer id="site-footer" class="ysq-footer">
    <?php
    $footer_defaults = [
        1 => [
            'title'   => 'Info',
            'content' => '<ul><li>Nasihat</li><li>Karir</li><li>Program Orang Tua Asuh</li><li>Pengaduan</li><li>Program Qurban</li></ul>',
        ],
        2 => [
            'title'   => 'Kontak',
            'content' => '<ul><li><a href="mailto:Email.Sabilulquran@gmail.com">Email Sabilulquran</a></li><li>Telp: 0851-7536-2016</li></ul>',
        ],
        3 => [
            'title'   => 'Lokasi',
            'content' => '<p>Tambahkan konten atau embed Google Maps</p>',
        ],
        4 => [
            'title'   => 'Lainnya',
            'content' => '<p>Tambahkan konten untuk kolom 4</p>',
        ],
    ];

    $footer_columns = [];

    foreach ($footer_defaults as $index => $defaults) {
        $title_mod   = 'footer_col_' . $index . '_title';
        $content_mod = 'footer_col_' . $index . '_content';
        $legacy_title_mod   = 'ysq_footer_col' . $index . '_title';
        $legacy_content_mod = 'ysq_footer_col' . $index . '_content';

        $title = get_theme_mod($title_mod, null);
        if (null === $title) {
            $title = get_theme_mod($legacy_title_mod, $defaults['title']);
        }
        $content = get_theme_mod($content_mod, null);
        if (null === $content) {
            $content = get_theme_mod($legacy_content_mod, $defaults['content']);
        }

        $title = is_string($title) ? $title : '';
        $content = is_string($content) ? $content : '';

        $footer_columns[$index] = [
            'title'   => $title !== '' ? $title : $defaults['title'],
            'content' => $content !== '' ? $content : $defaults['content'],
        ];
    }

    $footer_bottom_default = '&copy; 2025 Yayasan Sabilul Qur\'an &bull; HCIS v1.0';
    $footer_bottom_copy = get_theme_mod('footer_bottom_copy', null);
    if (null === $footer_bottom_copy) {
        $footer_bottom_copy = get_theme_mod('ysq_footer_copyright_text', $footer_bottom_default);
    }
    $footer_bottom_copy = is_string($footer_bottom_copy) ? $footer_bottom_copy : $footer_bottom_default;
    ?>

    <div class="ysq-footer__container">
        <div class="ysq-footer__grid">
            <?php foreach ($footer_columns as $column) : ?>
                <div class="ysq-footer__title"><?php echo esc_html($column['title']); ?></div>
            <?php endforeach; ?>

            <?php foreach ($footer_columns as $column) : ?>
                <div class="ysq-footer__content"><?php echo wp_kses_post($column['content']); ?></div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="footer-bottom">
        <div class="container">
            <p class="footer-copy"><?php echo wp_kses_post($footer_bottom_copy); ?></p>
        </div>
    </div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
