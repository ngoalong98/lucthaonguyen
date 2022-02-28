<?php

/**
 * Provide a public-facing view for the plugin
 *
 * This file is used to markup the public-facing aspects of the plugin.
 *
 * @link       https://wpdeveloper.net
 * @since      1.0.0
 *
 * @package    Wpfomo
 * @subpackage Wpfomo/public/partials
 */


function wpfomo_add_content() {
	$wpfomo_show_image = get_option( 'wpfomo_show_image' );
	if( 1 == $wpfomo_show_image ) {
		$wpfomo_image = '<div class="wpfomo-product-thumb-container"><img src="" class="wpfomo-product-thumb"></div>';
	}else {
		$wpfomo_image = '';
	}
    echo '<div id="wpfomo">'.
			$wpfomo_image.
			'<div class="wpfomo-content-wrapper">'.
				do_shortcode( get_option( 'wpfomo_user_template' ) ).
			'</div>'.
		'</div>';
		}
add_action('wp_footer', 'wpfomo_add_content');

/**
 * Primary Text Shortcode
 */
function wpfomo_primary_text_shortcode( $atts ) {

	return '<p><span class="wpfomo-buyer-name"></span>';

}
add_shortcode( 'primary_text', 'wpfomo_primary_text_shortcode' );

/**
 * Link Text Shortcode
 */
function wpfomo_link_text_shortcode( $atts ) {

	return '</p><a href="#" target="_blank" class="wpfomo-product-name"></a>';

}
add_shortcode( 'link_text', 'wpfomo_link_text_shortcode' );

/**
 * Secondary Text Shortcode
 */
function wpfomo_secondary_text_shortcode( $atts ) {

	return '<div class="time">
		<span class="wpfomo-secondary-text"></span>
	</div>';

}
add_shortcode( 'secondary_text', 'wpfomo_secondary_text_shortcode' );
