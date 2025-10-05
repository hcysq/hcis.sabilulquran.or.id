<?php

  $fitness_elementor_theme_custom_setting_css = '';

	// Global Color
	$fitness_elementor_theme_color = get_theme_mod('fitness_elementor_theme_color', '#C1E503');

	$fitness_elementor_theme_custom_setting_css .=':root {';
		$fitness_elementor_theme_custom_setting_css .='--primary-theme-color: '.esc_attr($fitness_elementor_theme_color ).'!important;';
	$fitness_elementor_theme_custom_setting_css .='}';

	// Scroll to top alignment
	$fitness_elementor_scroll_alignment = get_theme_mod('fitness_elementor_scroll_alignment', 'right');

    if($fitness_elementor_scroll_alignment == 'right'){
        $fitness_elementor_theme_custom_setting_css .='.scroll-up{';
            $fitness_elementor_theme_custom_setting_css .='right: 30px;!important;';
			$fitness_elementor_theme_custom_setting_css .='left: auto;!important;';
        $fitness_elementor_theme_custom_setting_css .='}';
    }else if($fitness_elementor_scroll_alignment == 'center'){
        $fitness_elementor_theme_custom_setting_css .='.scroll-up{';
            $fitness_elementor_theme_custom_setting_css .='left: calc(50% - 10px) !important;';
        $fitness_elementor_theme_custom_setting_css .='}';
    }else if($fitness_elementor_scroll_alignment == 'left'){
        $fitness_elementor_theme_custom_setting_css .='.scroll-up{';
            $fitness_elementor_theme_custom_setting_css .='left: 30px;!important;';
			$fitness_elementor_theme_custom_setting_css .='right: auto;!important;';
        $fitness_elementor_theme_custom_setting_css .='}';
    }

    // Related Product

	$fitness_elementor_show_related_product = get_theme_mod('fitness_elementor_show_related_product', true );

	if($fitness_elementor_show_related_product != true){
		$fitness_elementor_theme_custom_setting_css .='.related.products{';
			$fitness_elementor_theme_custom_setting_css .='display: none;';
		$fitness_elementor_theme_custom_setting_css .='}';
	}	