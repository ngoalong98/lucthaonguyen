<?php

if ( ! class_exists( 'McwFullPageCommonLocal' ) ) {

  final class McwFullPageCommonLocal {
    private static $localId = '-lv-state';
    private static $local = array();

    private function __construct() {}

    public static function GetState( $tag ) {
      if ( ! isset( self::$local[ $tag ] ) ) {
        self::$local[ $tag ] = get_option( $tag . self::$localId, false );
      }

      return self::$local[ $tag ];
    }

    public static function UpdateState( $tag, $val ) {
      if ( self::GetState( $tag ) !== $val ) {
        update_option( $tag . self::$localId, $val );
        self::$local[ $tag ] = $val;
      }

      return $val;
    }
  }

}