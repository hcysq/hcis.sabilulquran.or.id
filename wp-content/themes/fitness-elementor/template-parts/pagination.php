<?php if(get_theme_mod('fitness_elementor_show_pagination', true )== true): ?>
	<?php
		the_posts_pagination( array(
			'prev_text' => esc_html__( 'Previous page', 'fitness-elementor' ),
			'next_text' => esc_html__( 'Next page', 'fitness-elementor' ),
		) );
	?>		
<?php endif; ?>