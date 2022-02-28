<?php
/**
 * Plugin Name: FullPage for Elementor
 * Plugin URI: https://www.meceware.com/docs/fullpage-for-elementor/
 * Author: Mehmet Celik, Álvaro Trigo
 * Author URI: https://www.meceware.com/
 * Version: 1.7.1
 * Description: Create beautiful scrolling fullscreen web sites with Elementor and WordPress, fast and simple. Elementor addon of FullPage JS implementation.
 * Text Domain: mcw_fullpage_elementor
**/

/* Copyright 2019-2020 Mehmet Celik */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

if ( ! class_exists( 'McwFullPageElementorGlobals' ) ) {

  final class McwFullPageElementorGlobals {
    // Plugin version
    private static $version = '1.7.1';
    // FullPage JS version
    private static $fullPageVersion = '3.1.0';
    // Tag
    private static $tag = 'mcw-fullpage-elementor';
    // Plugin name
    private static $pluginName = 'FullPage for Elementor';

    private function __construct() { }

    public function __clone() {
      // Cloning instances of the class is forbidden
      _doing_it_wrong( __FUNCTION__, esc_html__( 'NOT ALLOWED', self::$tag ), self::$version );
    }

    public function __wakeup() {
      // Unserializing instances of the class is forbidden
      _doing_it_wrong( __FUNCTION__, esc_html__( 'NOT ALLOWED', self::$tag ), self::$version );
    }

    public static function Version() {
      return self::$version;
    }

    public static function Tag() {
      return self::$tag;
    }

    public static function Name() {
      return self::Translate( self::$pluginName );
    }

    public static function Url() {
      return trailingslashit( plugin_dir_url( __FILE__ ) );
    }

    public static function Dir() {
      return trailingslashit( plugin_dir_path( __FILE__ ) );
    }

    public static function File() {
      return __FILE__;
    }

    public static function FullPageVersion() {
      return self::$fullPageVersion;
    }

    public static function FullPageScriptName() {
      return self::$tag . '-fullpage';
    }

    public static function Translate( $str ) {
      return esc_html__( $str, self::$tag );
    }

    public static function GetExtensions() {
      $extensions = array(
        'continuous-horizontal' => array(
          'name' => 'Continuous Horizontal',
        ),
        'drag-and-move' => array(
          'name' => 'Drag and Move',
        ),
        'drop-effect' => array(
          'name' => 'Drop Effect',
        ),
        'fading-effect' => array(
          'name' => 'Fading Effect',
        ),
        'interlocked-slides' => array(
          'name' => 'Interlocked Slides',
        ),
        'offset-sections' => array(
          'name' => 'Offset Sections',
        ),
        'parallax' => array(
          'name' => 'Parallax',
        ),
        'reset-sliders' => array(
          'name' => 'Reset Sliders',
        ),
        'responsive-slides' => array(
          'name' => 'Responsive Slides',
        ),
        'scroll-horizontally' => array(
          'name' => 'Scroll Horizontally',
        ),
        'scroll-overflow-reset' => array(
          'name' => 'Scroll Overflow Reset',
        ),
      );

      // TODO: Array initialized for backward compatibility. With 25.10.2021, the array below can be initialized as array()
      $active = apply_filters( 'mcw-fullpage-extensions', array(
        'continuous-horizontal' => false,
        'drag-and-move' => false,
        'drop-effect' => false,
        'fading-effect' => false,
        'interlocked-slides' => false,
        'offset-sections' => false,
        'parallax' => false,
        'reset-sliders' => false,
        'responsive-slides' => false,
        'scroll-horizontally' => false,
        'scroll-overflow-reset' => false,
      ) );

      foreach ( $extensions as $key => $value ) {
        $extensions[ $key ]['id'] = 'mcw-fullpage-extension-' . $key;
        $extensions[ $key ]['active'] = array_key_exists( $key, $active ) ? $active[ $key ] : false;
      }

      return $extensions;
    }
  }

}

if ( ! class_exists( 'McwFullPageElementor' ) ) {

  final class McwFullPageElementor {
    private $tag;
    // Plugin name
    private $pluginName;

    // Elementor constants
    private $elementorMinimumVersion = '2.7.3';
    private $elementorFilePath = 'elementor/elementor.php';

    // Notice user meta
    private $noticeMeta = '-license-user-meta';

    // Object instance
    private static $instance = null;
    private $pluginSettings = null;

    public function __clone() {
      // Cloning instances of the class is forbidden
      _doing_it_wrong( __FUNCTION__, esc_html__( 'NOT ALLOWED', McwFullPageElementorGlobals::Tag() ), McwFullPageElementorGlobals::Version() );
    }

    public function __wakeup() {
      // Unserializing instances of the class is forbidden
      _doing_it_wrong( __FUNCTION__, esc_html__( 'NOT ALLOWED', McwFullPageElementorGlobals::Tag() ), McwFullPageElementorGlobals::Version() );
    }

    private function __construct() {
      // Get tag
      $this->tag = McwFullPageElementorGlobals::Tag();
      $this->pluginName = McwFullPageElementorGlobals::Name();
      // Init Plugin
      add_action( 'plugins_loaded', array( $this, 'OnPluginsLoaded' ), 100 );
    }

    public static function instance() {
      if ( is_null( self::$instance ) ) {
        self::$instance = new self();
      }

      return self::$instance;
    }

    public function OnPluginsLoaded() {
      // Check if elementor is installed/activated
      if ( ! did_action( 'elementor/loaded' ) ) {
        add_action( 'admin_notices', array( $this, 'OnAdminNoticeElementorMissing' ) );
        return;
      }

      // Check the elementor version
      if ( ! version_compare( ELEMENTOR_VERSION, $this->elementorMinimumVersion, '>=' ) ) {
        add_action( 'admin_notices', array( $this, 'OnAdminNoticeFailedElementorVersion' ) );
        return;
      }

      require_once McwFullPageElementorGlobals::Dir() . 'models/plugin-settings.php';
      $this->pluginSettings = new McwFullPageElementorPluginSettings( $this->tag );

      // Enqueue admin scripts
      add_action( 'admin_enqueue_scripts', array( $this, 'OnAdminEnqueueScripts' ) );

      // Admin notices
      add_action( 'admin_notices', array( $this, 'OnAdminNotices' ) );
      add_action( 'wp_ajax_' . $this->tag . '-admin-notice', array( $this, 'OnAjaxAdminNoticeRequest' ) );

      // Add elementor init action
      add_action( 'elementor/init', array( $this, 'OnElementorInit' ) );
    }

    // Either Elementor is not activated or not installed
    public function OnAdminNoticeElementorMissing() {
      $screen = get_current_screen();
      if ( isset( $screen->parent_file ) && 'plugins.php' === $screen->parent_file && 'update' === $screen->id ) {
        return;
      }

      $url = '';
      $message = '';
      $button = '';

      if ( $this->IsElementorInstalled() ) {
        if ( ! current_user_can( 'activate_plugins' ) ) {
          return;
        }

        $url = wp_nonce_url( 'plugins.php?action=activate&amp;plugin=' . $this->elementorFilePath . '&amp;plugin_status=all&amp;paged=1&amp;s', 'activate-plugin_' . $this->elementorFilePath );
        $message = sprintf( McwFullPageElementorGlobals::Translate( '%s requires Elementor plugin activated.' ), '<strong>' . McwFullPageElementorGlobals::Name() . '</strong>' );
        $button = McwFullPageElementorGlobals::Translate( 'Activate Elementor Now' );
      } else {
        $url = wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=elementor' ), 'install-plugin_elementor' );
        $message = sprintf( McwFullPageElementorGlobals::Translate( '%s requires Elementor plugin installed and activated.' ), '<strong>' . McwFullPageElementorGlobals::Name() . '</strong>' );
        $button = McwFullPageElementorGlobals::Translate( 'Install Elementor Now' );
      }

      ?>
      <div class="error" style="display:flex;align-items:center;">
        <div class="mcw-fp-img-wrap" style="display:flex;align-items:center;padding:0.7em;">
          <img src="<?php echo McwFullPageElementorGlobals::Url() . 'assets/logo/logo-32.png'; ?>">
        </div>
        <div class="mcw-fp-img-text">
          <p>
            <?php echo $message; ?>
          </p>
          <p><a href="<?php echo $url; ?>" class="button-primary"><?php echo $button; ?></a></p>
        </div>
      </div>
      <?php
    }

    // Elementor version check notice
    public function OnAdminNoticeFailedElementorVersion() {
      if ( ! current_user_can( 'update_plugins' ) ) {
        return;
      }

      $url = wp_nonce_url( self_admin_url( 'update.php?action=upgrade-plugin&plugin=' ) . $this->elementorFilePath, 'upgrade-plugin_' . $this->elementorFilePath );
      $message = sprintf( McwFullPageElementorGlobals::Translate( '%s requires Elementor version at least %s. Please update Elementor to continue!' ), McwFullPageElementorGlobals::Name(), $this->elementorMinimumVersion );
      $button = McwFullPageElementorGlobals::Translate( 'Update Elementor Now' );

      ?>
      <div class="error" style="display:flex;align-items:center;">
        <div class="mcw-fp-img-wrap" style="display:flex;align-items:center;padding:0.7em;">
          <img src="<?php echo McwFullPageElementorGlobals::Url() . 'assets/logo/logo-32.png'; ?>">
        </div>
        <div class="mcw-fp-img-text">
          <p>
            <?php echo $message; ?>
          </p>
          <p><a href="<?php echo $url; ?>" class="button-primary"><?php echo $button; ?></a></p>
        </div>
      </div>
      <?php
    }

    public function OnAdminEnqueueScripts() {
      if ( $this->pluginSettings->GetLicenseState() ) {
        return;
      }

      wp_enqueue_script( $this->tag . '-notice-js', McwFullPageElementorGlobals::Url() . 'assets/notice/notice.min.js', array( 'jquery', 'common' ), McwFullPageElementorGlobals::Version(), true );
      wp_localize_script(
        $this->tag . '-notice-js',
        'McwFullPageElementor',
        array(
          'nonce'   => wp_create_nonce( $this->tag . '-admin-notice-nonce' ),
          'ajaxurl' => admin_url( 'admin-ajax.php' ),
        )
      );
    }

    public function OnAdminNotices() {
      // Do not show in the settings page.
      if ( $this->pluginSettings->IsSettingsPage() ) {
        return;
      }

      if ( $this->pluginSettings->GetLicenseState() ) {
        return;
      }

      // Check the user transient.
      $currentUser = wp_get_current_user();
      $meta = get_user_meta( $currentUser->ID, $this->noticeMeta, true );
      if ( isset( $meta ) && ! empty( $meta ) && ( $meta > new DateTime( 'now' ) ) ) {
        return;
      }

      // No license is given
      $message = '';
      $url = menu_page_url( $this->tag, false );
      $button = esc_html__( 'Activate Now!', $this->tag );
      $inner = '';
      // No license is given
      if ( ! $this->pluginSettings->GetLicenseKey() ) {
        $message = sprintf( esc_html__( '%s plugin requires the license key to be activated. Please enter your license key!', $this->tag ), '<strong>' . $this->pluginName . '</strong>' );
        $inner = 'class="' . $this->tag . '-notice notice notice-info is-dismissible" data-notice="no-license"';
      } else {
        $message = sprintf( esc_html__( '%s plugin is NOT activated. Please check your license key!', $this->tag ), '<strong>' . $this->pluginName . '</strong>' );
        $inner = 'class="' . $this->tag . '-notice notice notice-error is-dismissible" data-notice="no-active-license"';
      }

      ?>
      <div <?php echo $inner; ?> style="display:flex;align-items:center;">
        <div class="mcw-fp-img-wrap" style="display:flex;align-items:center;padding:0.7em;">
          <img src="<?php echo McwFullPageElementorGlobals::Url() . 'assets/logo/logo-32.png'; ?>">
        </div>
        <div class="mcw-fp-img-text">
          <p>
            <?php echo $message; ?>
          </p>
          <p><a href="<?php echo $url; ?>" class="button-primary"><?php echo $button; ?></a></p>
        </div>
      </div>
      <?php
    }

    public function OnAjaxAdminNoticeRequest() {
      $notice = sanitize_text_field( $_POST['notice'] );

      check_ajax_referer( $this->tag . '-admin-notice-nonce', 'nonce' );

      if ( ( 'no-license' === $notice ) || ( 'no-active-license' === $notice ) ) {
        $currentUser = wp_get_current_user();
        update_user_meta( $currentUser->ID, $this->noticeMeta, ( new DateTime( 'now' ) )->modify( '+12 hours' ) );
      }

      wp_die();
    }

    // On Elementor Initialize, include files
    public function OnElementorInit() {
      require_once McwFullPageElementorGlobals::Dir() . 'models/page-settings.php';
      new McwFullPageElementorPageSettings( $this->tag, $this->pluginSettings );
    }

    // Returns true if Elementor is installed
    private function IsElementorInstalled() {
      $installed_plugins = get_plugins();
      return isset( $installed_plugins[ $this->elementorFilePath ] );
    }
  }

}

McwFullPageElementor::instance();
