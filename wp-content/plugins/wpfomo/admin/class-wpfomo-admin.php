<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://wpdeveloper.net
 * @since      1.0.0
 *
 * @package    Wpfomo
 * @subpackage Wpfomo/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Wpfomo
 * @subpackage Wpfomo/admin
 * @author     WP Developer <support@wpdeveloper.net>
 */
class Wpfomo_Admin {

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
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/wpfomo-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_media();
		wp_enqueue_script( 'jquery-ui-datepicker' );
		wp_enqueue_script( 'wpfomo-repeater', plugin_dir_url( __FILE__ ) . 'js/wpfomo-repeater.js', array( 'jquery' ), $this->version, true );
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/wpfomo-admin.js', array( 'jquery' ), $this->version, true );

		// Count Previous Section
		$repeater = array(
			'start' => count( get_option( 'wpfomo_primary_text' ) )
		);
		wp_localize_script( 'wpfomo-repeater', 'repeater', $repeater );

	}

	/**
	 * Create an admin page
	 */
	public function create_wpfomo_admin_page() {

		add_menu_page(
			'WPFomo',
			'WPFomo',
			'manage_options',
			'wpfomo-settings-page',
			array( $this, 'wpfomo_settings_page' ),
			'',
			199
		);

	}
	/**
	 * Register Settings Options
	 */
	public function wpfomo_register_settings() {

		register_setting( 'wpfomo-settings-group', 'wpfomo_primary_text' );
		register_setting( 'wpfomo-settings-group', 'wpfomo_secondary_text' );
		register_setting( 'wpfomo-settings-group', 'wpfomo_link_text' );
		register_setting( 'wpfomo-settings-group', 'wpfomo_image_url' );
		register_setting( 'wpfomo-settings-group', 'wpfomo_image_url_src' );
		register_setting( 'wpfomo-settings-group', 'wpfomo_url' );
		register_setting( 'wpfomo-settings-group', 'wpfomo_user_template' );
		register_setting( 'wpfomo-settings-group', 'wpfomo_show_image' );

	}


	/**
	 * Settings Option Page
	 */
	public function wpfomo_settings_page() {

		$buyer_name 		= array_values( (array) get_option( 'wpfomo_primary_text' ) );
		$purchase_time 		= array_values( (array) get_option( 'wpfomo_secondary_text' ) );
		$product_name 		= array_values( (array) get_option( 'wpfomo_link_text' ) );
		$product_image 		= array_values( (array) get_option( 'wpfomo_image_url' ) );
		$product_image_src 	= array_values( (array) get_option( 'wpfomo_image_url_src' ) );
		$custom_url 		= array_values( (array) get_option( 'wpfomo_url' ) );
		$user_template    	= get_option( 'wpfomo_user_template' );
		$show_image    		= get_option( 'wpfomo_show_image' );
		// If No Image Uploaded
    	$default_image =  plugins_url( '/', __FILE__ ).'/images/wpfomo-logo.png';
    	// Check if user template is blank?
    	if( empty( $user_template ) ) : $user_template = '[primary_text] did something [link_text][secondary_text]'; else: $user_template; endif;
		?>

		<div class="wpfomo-settings-container">
			<h2 class="wpfomo-settings-title"><?php _e( 'WPFomo Settings', 'wpfomo' ); ?></h2><hr>
			<form action="options.php" method="post">
				<?php settings_fields( 'wpfomo-settings-group' ); ?>
	    		<?php do_settings_sections( 'wpfomo-settings-group' ); ?>
	    		<div class="wpfomo-template">
	    			<table class="form-table" style="max-width:600px;">
	    				<tr valign="top">
	    					<th scope="row"><?php _e( 'Template', 'wpfomo' ); ?></th>
	    					<td>
	    						<textarea name="wpfomo_user_template" class="widefat" rows="4"><?php echo esc_html__( $user_template, 'wpfomo' ); ?></textarea>
	    						<p class="description">Variables: [primary_text], [link_text], [secondary_text]</p>
	    					</td>
	    				</tr>
	    				<tr valign="top">
	    					<th scope="row">
	    						<?php _e( 'Show Image', 'wofomo' ); ?>
	    						<p class="description"><?php _e( 'This will show the thumbnail image.', 'wofomo' ); ?></p>
	    					</th>
	    					<td>
	    						<input type="checkbox" name="wpfomo_show_image" value="1" <?php checked( 1, get_option( 'wpfomo_show_image' ), true); ?> />
	    					</td>
	    				</tr>
	    				<tr valign="top">
	    					<td><input type="submit" name="submit" class="button wpfomo-save-btn" value="<?php _e( 'Save Settings', 'wpfomo' ); ?>" /></td>
	    				</tr>
	    			</table>
	    		</div>
	    		<div class="wpfomo-settings">
	    			<?php for( $i = 0; $i < count( $buyer_name ); $i++ ) { ?>
					<?php
					if ( !empty( $product_image[$i] ) ) {
					        $image_attributes = wp_get_attachment_image_src( $product_image[$i], array( 100, 100 ) );
					        $src = $image_attributes[0];
					        $value = $product_image[$i];
					    } else {
					        $src = $default_image;
					        $value = '';
					    }
					?>
					<div id="table-id-<?php echo $i; ?>">
		    			<h2><?php $fomo = $i + 1; echo "Fomo ". $fomo; ?></h2>
		    			<table class="form-table" style="max-width:600px;">
			    			<tr valign="top">
			    				<th scope="row"><?php _e( 'Primary Text', 'wpfomo' ); ?></th>
			    				<td><input type="text" class="widefat" placeholder="Someone" name="wpfomo_primary_text[<?php echo $i; ?>]" value="<?php echo esc_attr( $buyer_name[$i] ); ?>"></td>
			    			</tr>
			    			<tr valign="top">
			    				<th scope="row"><?php _e( 'Link Text', 'wpfomo' ); ?></th>
			    				<td><input type="text" class="widefat" placeholder="Something Cool" name="wpfomo_link_text[<?php echo $i; ?>]" value="<?php echo esc_attr( $product_name[$i] ); ?>"></td>
			    			</tr>
			    			<tr valign="top">
			    				<th scope="row"><?php _e( 'Link URL', 'wpfomo' ); ?></th>
			    				<td><input type="text" class="widefat" name="wpfomo_url[<?php echo $i; ?>]" value="<?php echo esc_url( $custom_url[$i] ); ?>"></td>
			    			</tr>
			    			<tr valign="top">
			    				<th scope="row"><?php _e( 'Upload Image', 'wpfomo' ); ?></th>
			    				<td>
			    					<div class="upload">
							            <img data-src="<?php echo esc_url( $default_image ); ?>" src="<?php echo esc_url( $src ); ?>" width="100px" height="100px" />
							            <div>
							                <input type="hidden" name="wpfomo_image_url[<?php echo $i; ?>]" id="wpfomo_image_url[<?php echo $i; ?>]" value="<?php echo esc_attr( intval( $value ) ); ?>" />
							                <button type="submit" class="upload_image_button button"><?php echo esc_html__( 'Upload', 'wpfomo' ) ?></button>
							                <button type="submit" class="remove_image_button button"><?php echo esc_html__( 'Delete', 'wpfomo' ); ?></button>
							            </div>
							        </div>
							    </td>
			    			</tr>
			    			<tr valign="top">
			    				<th scope="row"><?php _e( 'Secondary Text', 'wpfomo' ); ?></th>
			    				<td><input type="text" class="widefat" name="wpfomo_secondary_text[<?php echo $i; ?>]" value="<?php echo esc_attr( $purchase_time[$i] ); ?>"></td>
			    			</tr>
			    			<tr valign="top">
			    				<td scope="row"><input type="button" data-id="<?php echo $i; ?>" name="wpfomo_delete" class="button wpfomo-delete-btn button-danger del-btn" value="<?php _e( 'Delete Item', 'wpfomo' ); ?>" /></td>
			    			</tr>
			    			<tr valign="top">
			    				<td colspan="3" ><hr></td>
			    			</tr>
			    		</table>
		    		</div>
		    		<?php } ?>
		    		<div class="repeatable"></div>
		    		<p><input type="button" value="<?php _e( 'Add New Item', 'wpfomo' ); ?>" class="button wpfomo-add-btn add" /> <input type="submit" name="submit" class="button wpfomo-save-btn" value="<?php _e( 'Save Settings', 'wpfomo' ); ?>" /></p>
	    		</div>
			</form>
		</div>
		<?php
	}

}
