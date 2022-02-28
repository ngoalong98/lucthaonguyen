<?php

/* Copyright 2019-2020 Mehmet Celik */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

require_once McwFullPageElementorGlobals::Dir() . 'models/local.php';

if ( ! class_exists( 'McwFullPageCommonServer' ) ) {

  class McwFullPageCommonServer {

    // Plugin name
    private $pluginName = '';
    // Plugin tag/slug
    private $tag = '';
    private $key = '';
    private $version = '';
    private $pluginFile = '';
    // Cache hours
    private $cache = 12;
    private $upgradeTransientId = '-upgrade';
    private $remoteId = '-lv-remote';
    private $server = 'aHR0cHM6Ly93d3cubWVjZXdhcmUuY29tL3NlcnZlci8=';
    // Forces update check
    private $forceCheck = false;

    public function __construct( $name, $tag, $key, $version, $fullPath ) {
      $this->pluginName = $name;
      $this->tag = $tag;
      $this->key = $key;
      $this->version = $version;
      $this->upgradeTransientId = $tag . $this->upgradeTransientId;
      $this->remoteId = $tag . $this->remoteId;

      $this->pluginFile = plugin_basename( wp_normalize_path( $fullPath ) );

      add_filter( 'plugins_api', array( $this, 'OnPluginsApi' ), 20, 3 );
      add_filter( 'site_transient_update_plugins', array( $this, 'OnUpdatePlugins' ) );
      add_action( 'upgrader_process_complete', array( $this, 'OnUpgraderProcessComplete' ), 10, 2 );
      add_filter( 'plugin_row_meta', array( $this, 'OnPluginRowMeta' ), 10, 3 );
      add_action( 'uninstall_' . $this->pluginFile, array( $this, 'OnUninstall' ) );

      $this->CheckForceUpdates();
    }

    public function OnPluginsApi( $result, $action, $args ) {
      // Do nothing if this is not about getting plugin information
      if ( 'plugin_information' !== $action ) {
        return $result;
      }

      // Do nothing if it is not our plugin
      if ( ! isset( $args->slug ) || ( $this->tag !== $args->slug ) ) {
        return $result;
      }

      // Get remote
      $remote = $this->GetRemote();

      if ( $remote ) {
        $result = new stdClass();
        $result->name = isset( $remote->name ) ? $remote->name : $this->pluginName;
        $result->slug = $this->tag;
        $result->version = isset( $remote->version ) ? $remote->version : $this->version;
        $result->tested = isset( $remote->tested ) ? $remote->tested : null;
        $result->requires = isset( $remote->requires ) ? $remote->requires : null;
        $result->author = isset( $remote->author ) ? $this->getFormattedAuthor( $remote->author, isset( $remote->author_homepage ) ? $remote->author_homepage : '' ) : '';
        $result->author_profile = isset( $remote->author_homepage ) ? $remote->author_homepage : null;
        $result->download_link = isset( $remote->download_url ) ? $remote->download_url : null;
        $result->trunk = isset( $remote->download_url ) ? $remote->download_url : null;
        $result->last_updated = isset( $remote->last_updated ) ? $remote->last_updated : null;

        $result->sections = array();
        if ( isset( $remote->sections ) ) {
          foreach ( get_object_vars( $remote->sections ) as $key => $value ) {
            $result->sections[ $key ] = $value;
          }

          // in case you want the screenshots tab, use the following HTML format for its content:
          // <ol><li><a href="IMG_URL" target="_blank" rel="noopener noreferrer"><img src="IMG_URL" alt="CAPTION" /></a><p>CAPTION</p></li></ol>
          if ( isset( $remote->sections->screenshots ) && ! empty( $remote->sections->screenshots ) ) {
            $result->sections['screenshots'] = $remote->sections->screenshots;
          }
        }

        if ( isset( $remote->banners ) ) {
          $result->banners = array(
            'low' => isset( $remote->banners->low ) ? $remote->banners->low : '',
            'high' => isset( $remote->banners->high ) ? $remote->banners->high : '',
          );
        }

        return $result;
      }

      // Shouldn't come here but just in case, better than showing nothing
      $result = new stdClass();
      $result->name = $this->pluginName;
      $result->slug = $this->tag;
      $result->version = $this->version;
      $result->sections = array();

      return $result;
    }

    public function OnUpdatePlugins( $transient ) {
      if ( ! $transient ) {
        return $transient;
      }

      if ( ! $this->forceCheck ) {
        if ( empty( $transient->checked ) ) {
          return $transient;
        }
      }

      // Get remote
      $remote = $this->GetRemote( $this->forceCheck );

      if ( $remote ) {
        if ( $this->CanUpdate( $remote ) ) {
          $result = new stdClass();
          $result->slug = $this->tag;
          $result->plugin = $this->pluginFile;
          $result->new_version = isset( $remote->version ) ? $remote->version : null;
          $result->tested = isset( $remote->tested ) ? $remote->tested : null;
          $result->package = isset( $remote->download_url ) ? $remote->download_url : null;
          $result->url = isset( $remote->homepage ) ? $remote->homepage : null;
          $result->icons = array();
          if ( isset( $remote->icons ) ) {
            foreach ( $remote->icons as $key => $val ) {
              $result->icons[ $key ] = $val;
            }
          }
          $transient->response[ $result->plugin ] = $result;
        }
      }

      $this->forceCheck = false;

      return $transient;
    }

    public function OnUpgraderProcessComplete( $upgrader_object, $options ) {
      if ( ( 'update' === $options['action'] ) && ( 'plugin' === $options['type'] ) ) {
        // just clean the cache when new plugin version is installed
        delete_transient( $this->upgradeTransientId );
      }
    }

    public function OnUninstall() {
      remove_filter( 'plugins_api', array( $this, 'OnPluginsApi' ), 20 );
      remove_filter( 'site_transient_update_plugins', array( $this, 'OnUpdatePlugins' ) );
      remove_filter( 'upgrader_process_complete', array( $this, 'OnUpgraderProcessComplete' ), 10 );
      remove_filter( 'plugin_row_meta', array( $this, 'OnPluginRowMeta' ), 10 );
    }

    public function OnPluginRowMeta( $pluginMeta, $pluginFile, $pluginData = array() ) {
      if ( ( $pluginFile === $this->pluginFile ) ) {
        if ( ! isset( $pluginData['slug'] ) ) {
          // View details link
          $pluginMeta[] = sprintf(
            '<a href="%s" class="thickbox open-plugin-details-modal" aria-label="%s" data-title="%s">%s</a>',
            esc_url( network_admin_url( 'plugin-install.php?tab=plugin-information&plugin=' . rawurlencode( $this->tag ) . '&TB_iframe=true&width=600&height=550' ) ),
            esc_attr( sprintf( esc_html__( 'More information about %s', $this->tag ), $pluginData['Name'] ) ),
            esc_attr( $pluginData['Name'] ),
            esc_html__( 'View details', $this->tag )
          );
        }

        // Check for updates link
        $linkUrl = wp_nonce_url(
          add_query_arg(
            array(
              'mcw_updates' => 1,
              'slug' => $this->tag,
            ),
            self_admin_url( 'plugins.php' )
          ),
          'mcw_updates'
        );

        $pluginMeta[] = sprintf( '<a href="%s">%s</a>', esc_attr( $linkUrl ), esc_html__( 'Check for updates', $this->tag ) );
      }

      return $pluginMeta;
    }

    public function CheckForceUpdates() {
      if ( current_user_can( 'update_plugins' ) ) {
        $shouldCheck = isset( $_GET['mcw_updates'], $_GET['slug'] ) && ( $_GET['slug'] === $this->tag ) && check_admin_referer( 'mcw_updates' );
        if ( $shouldCheck ) {
          $this->forceCheck = true;
        }
      }
    }

    public function Deactivate() {
      $this->GetRemote( true, array( 'deactivate' => true ) );
    }

    public function GetRemoteBase( $args ) {
      $url = add_query_arg( $args, $this->decode( $this->server ) );

      $getRemote = wp_remote_get(
        $url,
        array(
          'timeout' => 15,
          'headers' => array(
            'Accept' => 'application/json',
          ),
        )
      );

      if ( ! is_wp_error( $getRemote ) && isset( $getRemote['response']['code'] ) && ( 200 === $getRemote['response']['code'] ) && ( ! empty( $getRemote['body'] ) ) ) {
        return $getRemote;
      }

      return null;
    }

    public function GetRemote( $force = false, $extra = null ) {
      // Trying to get from cache first
      $remote = get_transient( $this->upgradeTransientId );
      if ( ( false === $remote ) || $force ) {
        preg_match( '/^\d+(\.\d+)*/', phpversion(), $phpVersion );

        $args = array(
          'action' => 'get_metadata',
          'slug' => $this->tag,
          'key' => $this->key,
          'installed_version' => $this->version,
          'php' => $phpVersion[0],
          'locale' => get_locale(),
          'api' => 'v2',
        );

        if ( $extra ) {
          $args = array_merge( $args, $extra );
        }

        $remote = $this->GetRemoteBase( $args );

        if ( $remote ) {
          set_transient( $this->upgradeTransientId, $remote, $this->cache * HOUR_IN_SECONDS );
          update_option( $this->remoteId, $remote );
        } else {
          $remote = get_option( $this->remoteId, false );
          set_transient( $this->upgradeTransientId, $remote, 6 * HOUR_IN_SECONDS );
        }
      }

      if ( $remote ) {
        $remote = json_decode( $remote['body'] );
      }

      return $remote;
    }

    public function CanUpdate( $remote = null ) {
      if ( ! $remote ) {
        $remote = $this->GetRemote();
        if ( ! $remote ) {
          return false;
        }
      }

      return version_compare( $this->version, isset( $remote->version ) ? $remote->version : $this->version, '<' ) && version_compare( isset( $remote->requires ) ? $remote->requires : '', get_bloginfo( 'version' ), '<' );
    }

    public function GetRemoveVersion() {
      $remote = $this->GetRemote();

      return $remote->version;
    }

    public function GetRemoteLicense() {
      $remote = $this->GetRemote();

      $result = false;
      if ( $remote && ! empty( $this->key ) && isset( $remote->license_status ) ) {
        $result = $remote->license_status;
      }

      return McwFullPageCommonLocal::UpdateState( $this->tag, $result );
    }

    public function GetRemoteErrorMessage() {
      $remote = $this->GetRemote();

      if ( $remote && isset( $remote->license_error ) ) {
        return $remote->license_error;
      }

      return '';
    }

    public function GetRemoteExtensionKey( $tag ) {
      $remote = $this->GetRemote( $this->forceCheck );
      $this->forceCheck = false;

      $result = '';
      if ( $remote && isset( $remote->activationKey ) ) {
        $result = $remote->activationKey;
      }

      return McwFullPageCommonLocal::UpdateState( $this->tag . '-key', $result );
    }

    private function Decode( $input ) {
      $codes = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=';

      // Strip tags first
      $input = wp_strip_all_tags( $input );

      // Check if input is null
      if ( null === $input ) {
        return '';
      }

      // Check if the string is valid
      if ( strlen( $input ) % 4 !== 0 ) {
        return '';
      }

      $decoded[] = ( ( strlen( $input ) * 3 ) / 4 ) - ( strrpos( $input, '=' ) > 0 ? ( strlen( $input ) - strrpos( $input, '=' ) ) : 0 );
      $inChars = str_split( $input );
      $j = 0;
      $b = array();
      $count = count( $inChars );
      for ( $i = 0; $i < $count; $i += 4 ) {
        $b[0] = strpos( $codes, $inChars[ $i ] );
        $b[1] = strpos( $codes, $inChars[ $i + 1 ] );
        $b[2] = strpos( $codes, $inChars[ $i + 2 ] );
        $b[3] = strpos( $codes, $inChars[ $i + 3 ] );
        $decoded[ $j++ ] = ( ( $b[0] << 2 ) | ( $b[1] >> 4 ) );

        if ( $b[2] < 64 ) {
          $decoded[ $j++ ] = ( ( $b[1] << 4 ) | ( $b[2] >> 2 ) );
          if ( $b[3] < 64 ) {
            $decoded[ $j++ ] = ( ( $b[2] << 6 ) | $b[3] );
          }
        }
      }

      $decodedStr = '';
      $count = count( $decoded );
      for ( $i = 0; $i < $count; $i++ ) {
        $decodedStr .= chr( $decoded[ $i ] );
      }

      return $decodedStr;
    }

    private function getFormattedAuthor( $author, $homepage ) {
      if ( ! empty( $homepage ) ) {
        return sprintf( '<a href="%s">%s</a>', $homepage, $author );
      }

      return $author;
    }
  }
}
