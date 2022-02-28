<?php

/**
 * @link              https://wpdeveloper.net
 * @since             1.1.0
 * @package           Wpfomo
 *
 * @wordpress-plugin
 * Plugin Name:       WPFomo
 * Plugin URI:        https://wpdeveloper.net/wpfomo
 * Description:       Show fomo notification on WordPress site.
 * Version:           1.1.0
 * Author:            WPDeveloper
 * Author URI:        https://wpdeveloper.net
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wpfomo
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'WPFOMO_VERSION', '1.1.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-wpfomo-activator.php
 */
function activate_wpfomo() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wpfomo-activator.php';
	Wpfomo_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-wpfomo-deactivator.php
 */
function deactivate_wpfomo() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wpfomo-deactivator.php';
	Wpfomo_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_wpfomo' );
register_deactivation_hook( __FILE__, 'deactivate_wpfomo' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-wpfomo.php';

include_once plugin_dir_path( __FILE__ ) . '/includes/notificationx-installer.php';
new Nx_Installer('');


/**
 * Admin Notices
 */


function wpfomo_admin_notice() {
  if ( current_user_can( 'install_plugins' ) && !class_exists( 'NotificationX' ) ) {
    global $current_user ;
    $user_id = $current_user->ID;
    /* Check that the user hasn't already clicked to ignore the message */
    if ( ! get_user_meta($user_id, 'wpfomo_ignore_notice110') ) {
      echo '<div class="notice notice-info updated" style="display: flex; align-items: center; justify-content: space-between;">';
      printf(__('<p><strong>WPFomo</strong> is outdated! We have released better plugin <a href="https://notificationx.com" target="_blank">NotificationX</a> with with most advanced features. <button id="nx-installer-btn" class="button button-primary">Install Now!</button></p>
        <p><a href="%1$s" style="text-decoration: none;"><span class="dashicons dashicons-dismiss"></span></a></p>'),  admin_url( '/?wpfomo_nag_ignore=0' ));
      echo "</div>";
    }
  }
}
add_action('admin_notices', 'wpfomo_admin_notice');


/**
 * Nag Ignore
 */
function wpfomo_nag_ignore() {
  global $current_user;
        $user_id = $current_user->ID;
        /* If user clicks to ignore the notice, add that to their user meta */
        if ( isset($_GET['wpfomo_nag_ignore']) && '0' == $_GET['wpfomo_nag_ignore'] ) {
             add_user_meta($user_id, 'wpfomo_ignore_notice110', 'true', true);
  }
}
add_action('admin_init', 'wpfomo_nag_ignore');


/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_wpfomo() {

	$plugin = new Wpfomo();
	$plugin->run();

}
run_wpfomo();
