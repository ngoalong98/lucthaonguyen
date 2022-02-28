<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

if ( ! class_exists( 'McwFullPageElementorNavCSS' ) ) {

  class McwFullPageElementorNavCSS {
    public function __construct() {
    }

    private function hexToRgba( $hex, $alpha = false ) {
      $hex = str_replace( '#', '', $hex );

      if ( strlen( $hex ) === 6 ) {
        $hex = array( $hex[0] . $hex[1], $hex[2] . $hex[3], $hex[4] . $hex[5] );
      } elseif ( strlen( $hex ) === 3 ) {
        $hex = array( $hex[0] . $hex[0], $hex[1] . $hex[1], $hex[2] . $hex[2] );
      } else {
        return 'rgb(0,0,0)';
      }

      $hex = array_map( 'hexdec', $hex );

      if ( $alpha ) {
        return 'rgba(' . implode( ',', $hex ) . ',' . $alpha . ')';
      }

      return 'rgb(' . implode( ',', $hex ) . ')';
    }

    public function GetCustomCSS( $style, $selector, $main, $active, $hover ) {
      $css = '';

      switch ( $style ) {
        case 'default':
          if ( isset( $main ) && ! empty( $main ) ) {
            $css .= "{$selector} ul li a span{background:{$main};}";
          }
          if ( isset( $active ) && ! empty( $active ) ) {
            $css .= "{$selector} ul li a.active span{background:{$active};}";
          }
          if ( isset( $hover ) && ! empty( $hover ) ) {
            $css .= "{$selector} ul li a:not(.active):hover span{background:{$hover};}";
          }
          break;

        case 'circles':
          if ( isset( $main ) && ! empty( $main ) ) {
            $css .= "{$selector} ul li a:not(.active) span{background:transparent;border:2px solid {$main};}";
          }
          if ( isset( $active ) && ! empty( $active ) ) {
            $css .= "{$selector} ul li a.active span{background:{$active};border:2px solid {$active};}";
          }
          if ( isset( $hover ) && ! empty( $hover ) ) {
            $css .= "{$selector} ul li:hover a:not(.active) span{background:{$hover};background:{$this->hexToRgba( $hover, 0.4 )};}";
          }
          break;

        case 'circles-inverted':
          if ( isset( $main ) && ! empty( $main ) ) {
            $css .= "{$selector} ul li a span{border-color:{$main};background:{$this->hexToRgba( $main, 0.5 )};}";
          }
          if ( isset( $active ) && ! empty( $active ) ) {
            $css .= "{$selector} ul li a.active span,{$selector} ul li:hover a.active span{background:transparent;box-shadow:0 0 0 2px {$this->hexToRgba( $active, 1 )};}";
          }
          if ( isset( $hover ) && ! empty( $hover ) ) {
            $css .= "{$selector} ul li:hover a:not(.active) span{background:{$hover};}";
          }
          break;

        case 'crazy-text-effect':  // TODO: crazy-text-effect will be fixed. CSS is not right.
          if ( isset( $main ) && ! empty( $main ) ) {
            $css .= "{$selector} ul li a span::before{background:{$main};}";
          }
          if ( isset( $active ) && ! empty( $active ) ) {
            $css .= "{$selector} ul li a.active span::before{background:{$active};}";
          }
          if ( isset( $hover ) && ! empty( $hover ) ) {
            $css .= "{$selector} ul li:hover a:not(.active) span::before{background:{$hover};}";
          }
          break;

        case 'expanding-circles':
          if ( isset( $main ) && ! empty( $main ) ) {
            $css .= "{$selector} ul li a span::before{box-shadow:inset 0 0 0 10px {$main};}";
          }
          if ( isset( $active ) && ! empty( $active ) ) {
            $css .= "{$selector} ul li a.active span::before{box-shadow:inset 0 0 0 10px {$active};}";
          }
          if ( isset( $hover ) && ! empty( $hover ) ) {
            $css .= "{$selector} ul li a:not(.active) span:focus::before,{$selector} ul li a:not(.active) span:hover::before{box-shadow:inset 0 0 0 10px {$hover};}";
          }
          break;

        case 'expanding-squares':
          if ( isset( $main ) && ! empty( $main ) ) {
            $css .= "{$selector} ul li a:not(.active) span::before{box-shadow: inset 0 0 0 9px {$main};}";
          }
          if ( isset( $active ) && ! empty( $active ) ) {
            $css .= "{$selector} ul li a.active span::before{box-shadow:inset 0 0 0 1px {$active};}";
          }
          if ( isset( $hover ) && ! empty( $hover ) ) {
            $css .= "{$selector} ul li a:not(.active) span:focus::before,{$selector} ul li a:not(.active) span:hover::before{box-shadow:inset 0 0 0 9px {$hover};}";
          }
          break;

        case 'filled-bars':
          if ( isset( $main ) && ! empty( $main ) ) {
            $css .= "{$selector} ul li a span{background:{$main};}";
          }
          if ( isset( $active ) && ! empty( $active ) ) {
            $css .= "{$selector} ul li a span::before{background:{$active};}";
          }
          if ( isset( $hover ) && ! empty( $hover ) ) {
            $css .= "{$selector} ul li:hover a:not(.active) span{background:{$hover};}";
          }
          break;

        case 'filled-circle-within':
          if ( isset( $main ) && ! empty( $main ) ) {
            $css .= "{$selector} ul li a:not(.active) span{background:transparent;box-shadow:0 0 0 2px {$this->hexToRgba( $main, 1 )} inset;}";
          }
          if ( isset( $active ) && ! empty( $active ) ) {
            $css .= "{$selector} ul li a.active span{background:transparent;box-shadow:0 0 0 18px {$this->hexToRgba( $active, 1 )} inset;}";
          }
          if ( isset( $hover ) && ! empty( $hover ) ) {
            $css .= "{$selector} ul li:hover a:not(.active) span{background:#fff;background:{$this->hexToRgba( $hover, 0.4 )};}";
          }
          break;

        case 'filled-circles':
          if ( isset( $main ) && ! empty( $main ) ) {
            $css .= "{$selector} ul li a span{background:{$main};}#fp-nav ul li a span::before{background:{$main};}";
          }
          if ( isset( $active ) && ! empty( $active ) ) {
            $css .= "{$selector} ul li a.active span::before{background:{$active};}";
          }
          if ( isset( $hover ) && ! empty( $hover ) ) {
            $css .= "{$selector} ul li:hover a:not(.active) span{background:{$hover};}";
          }
          break;

        case 'filled-rombs':
          if ( isset( $main ) && ! empty( $main ) ) {
            $css .= "{$selector} ul li a span{background:{$main};}";
          }
          if ( isset( $active ) && ! empty( $active ) ) {
            $css .= "{$selector} ul li a.active span::before{background:{$active};}";
          }
          if ( isset( $hover ) && ! empty( $hover ) ) {
            $css .= "{$selector} ul li:hover a:not(.active) span{background:{$hover};}";
          }
          break;

        case 'filled-squares':
          if ( isset( $main ) && ! empty( $main ) ) {
            $css .= "{$selector} ul li a:not(.active) span,{$selector} ul li a:not(.active) span::before{background:{$main};}";
          }
          if ( isset( $active ) && ! empty( $active ) ) {
            $css .= "{$selector} ul li a.active span,{$selector} ul li a.active span::before{background:{$active};}";
          }
          if ( isset( $hover ) && ! empty( $hover ) ) {
            $css .= "{$selector} ul li:hover a:not(.active) span,{$selector} ul li:hover a:not(.active) span::before{background:{$hover};}";
          }
          break;

        case 'multiple-circles':
          if ( isset( $main ) && ! empty( $main ) ) {
            $css .= "{$selector} ul li a:not(.active) span::before{background:{$main};}{$selector} ul li a span:after{box-shadow:inset 0 0 0 3px {$main};}";
          }
          if ( isset( $active ) && ! empty( $active ) ) {
            $css .= "{$selector} ul li a.active span::after{box-shadow:inset 0 0 0 3px {$active};background: {$active};}";
          }
          if ( isset( $hover ) && ! empty( $hover ) ) {
            $css .= "{$selector} ul li:hover a:not(.active) span::before{background:{$hover};}";
          }
          break;

        case 'multiple-squares-to-rombs':
          if ( isset( $main ) && ! empty( $main ) ) {
            $css .= "{$selector} ul li a span::before{background:{$main};}{$selector} ul li a:not(.active) span::after{box-shadow:inset 0 0 0 3px {$main};}";
          }
          if ( isset( $active ) && ! empty( $active ) ) {
            $css .= "{$selector} ul li a.active span::after{box-shadow:inset 0 0 0 3px {$active};}";
          }
          if ( isset( $hover ) && ! empty( $hover ) ) {
            $css .= "{$selector} ul li:hover a:not(.active) span::before{background:{$hover};}";
          }
          break;

        case 'multiple-squares':
          if ( isset( $main ) && ! empty( $main ) ) {
            $css .= "{$selector} ul li a:not(.active) span::before{background:{$main};}{$selector} ul li a:not(.active) span:after{box-shadow:inset 0 0 0 3px {$main};}";
          }
          if ( isset( $active ) && ! empty( $active ) ) {
            $css .= ".fp-slidesNav ul li a span::before{background: {$active};}{$selector} ul li a.active span::after{box-shadow:inset 0 0 0 3px {$active};}";
          }
          if ( isset( $hover ) && ! empty( $hover ) ) {
            $css .= "{$selector} ul li:hover a:not(.active) span::before{background:{$hover};}";
          }
          break;

        case 'rotating-circles':
          if ( isset( $main ) && ! empty( $main ) ) {
            $css .= "{$selector} ul li a:not(.active) span{background:{$this->hexToRgba( $main, 0.2 )};}";
          }
          if ( isset( $active ) && ! empty( $active ) ) {
            $css .= "{$selector} ul li a.active span{background:{$active};}";
          }
          if ( isset( $hover ) && ! empty( $hover ) ) {
            $css .= "{$selector} ul li:hover a:not(.active) span{background-color:{$this->hexToRgba( $hover, 0.6 )};}";
          }
          break;

        case 'rotating-circles2':
          if ( isset( $main ) && ! empty( $main ) ) {
            $css .= "{$selector} ul li a:not(.active) span{background:{$this->hexToRgba( $main, 0.2 )};}";
          }
          if ( isset( $active ) && ! empty( $active ) ) {
            $css .= "{$selector} ul li a.active span{background:{$active};}";
          }
          if ( isset( $hover ) && ! empty( $hover ) ) {
            $css .= "{$selector} ul li:hover a:not(.active) span{background-color:{$this->hexToRgba( $hover, 0.6 )};}";
          }
          break;

        case 'squares-border':
          if ( isset( $main ) && ! empty( $main ) ) {
            $css .= "{$selector} ul li a:not(.active) span{border-color:{$this->hexToRgba( $main, 0.6 )};}";
          }
          if ( isset( $active ) && ! empty( $active ) ) {
            $css .= "{$selector} ul li a.active span{background:{$active};}";
          }
          if ( isset( $hover ) && ! empty( $hover ) ) {
            $css .= "{$selector} ul li:hover a:not(.active) span{background-color:{$this->hexToRgba( $hover, 0.4 )};}";
          }
          break;

        case 'squares-to-rombs':
          if ( isset( $main ) && ! empty( $main ) ) {
            $css .= "{$selector} ul li a:not(.active) span{background:transparent;border-color:{$this->hexToRgba( $main, 0.3 )};}";
          }
          if ( isset( $active ) && ! empty( $active ) ) {
            $css .= "{$selector} ul li a.active span{background:{$active};}";
          }
          if ( isset( $hover ) && ! empty( $hover ) ) {
            $css .= "{$selector} ul li:hover a:not(.active) span{background-color:{$this->hexToRgba( $hover, 0.5 )};}";
          }
          break;

        case 'squares':
          if ( isset( $main ) && ! empty( $main ) ) {
            $css .= "{$selector} ul li a:not(.active) span{background:{$main};}";
          }
          if ( isset( $active ) && ! empty( $active ) ) {
            $css .= "{$selector} ul li a.active span{background:{$active};}";
          }
          if ( isset( $hover ) && ! empty( $hover ) ) {
            $css .= "{$selector} ul li:hover a:not(.active) span{background:{$this->hexToRgba( $hover, 0.5 )};}";
          }
          break;

        case 'story-telling':
          if ( isset( $main ) && ! empty( $main ) ) {
            $css .= "{$selector} ul li:not(:last-child) a:not(.active) span::before{background:{$main};}";
          }
          if ( isset( $active ) && ! empty( $active ) ) {
            $css .= "{$selector} ul li a.active span:after{border-color:{$active};}";
          }
          if ( isset( $hover ) && ! empty( $hover ) ) {
            $css .= "{$selector} ul li a:not(.active):focus span::after,{$selector} ul li a:not(.active):hover span::after{border-color:{$hover};}";
          }
          break;

        default:
          break;
      }

      return $css;
    }
  }

}
