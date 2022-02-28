<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://wpdeveloper.net
 * @since      1.0.0
 *
 * @package    Wpfomo
 * @subpackage Wpfomo/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Wpfomo
 * @subpackage Wpfomo/public
 * @author     WP Developer <support@wpdeveloper.net>
 */
class Wpfomo_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/wpfomo-public.css', array(), $this->version, 'all' );
		 
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		wp_enqueue_script( 'wpfomo-public-script', plugin_dir_url( __FILE__ ) . 'js/wpfomo-public.js', array( 'jquery' ), $this->version, false );

		$product_images = get_option( 'wpfomo_image_url' );
		for( $i = 0; $i < count( $product_images ); $i++ ) {
			$img_src = wp_get_attachment_image_src( $product_images[$i], array(100, 100) );
			if( !empty( $img_src ) ) {
				$img_url[$i] = $img_src[0];
			}else {
				$img_url[$i] = plugins_url( '/', __FILE__ ).'/images/wpfomo-logo.png';
			}
			
		}

		$js_data = array(
			'buyer_name' 	=> get_option( 'wpfomo_primary_text' ),
			'purchase_time' => get_option( 'wpfomo_secondary_text' ),
			'product_name' 	=> get_option( 'wpfomo_link_text' ),
			'product_image' => $img_url,
			'custom_url' 	=> get_option( 'wpfomo_url' ),
		);
		wp_localize_script( 'wpfomo-public-script', 'settings', $js_data );

	}

}
