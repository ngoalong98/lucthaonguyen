<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'McwFullPageUpgraderSkinHelper' ) )
{
  require_once( ABSPATH . 'wp-admin/includes/file.php' );
  require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
  require_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );

  class McwFullPageUpgraderSkinHelper extends \WP_Upgrader_Skin
  {
    public function feedback( $string, ...$args ) {
      /* no output */
    }

    public function header() {
      /* no output */
    }
  }
}