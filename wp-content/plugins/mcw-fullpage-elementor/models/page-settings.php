<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

use Elementor\Controls_Manager as ControlsManager;

require_once McwFullPageElementorGlobals::Dir() . 'models/local.php';

if ( ! class_exists( 'McwFullPageElementorPageSettings' ) ) {

  class McwFullPageElementorPageSettings {
    // Tag name
    private $tag;
    // Group slug
    private $slug;
    private $tab;
    // FullPage wrapper class name
    private $wrapper = 'mcw-fp-wrapper';
    // Global variables
    protected $pageSettingsModel = array();
    private $pluginSettings = null;
    private $navigationCSS = null;
    private $extensions = null;

    public function __construct( $tag, $pluginSettings ) {
      require McwFullPageElementorGlobals::Dir() . 'models/navigation-css.php';

      // Get tag
      $this->tag = $tag;
      $this->slug = $this->GetId( 'fp-settings' );
      $this->tab = ControlsManager::TAB_SETTINGS;
      $this->pluginSettings = $pluginSettings;

      $this->navigationCSS = new McwFullPageElementorNavCSS();

      // Editor style and script
      add_action( 'elementor/editor/after_enqueue_styles', array( $this, 'OnElementorEditorAfterEnqueueStyles' ) );
      add_action( 'elementor/editor/after_enqueue_scripts', array( $this, 'OnElementorEditorAfterEnqueueScripts' ) );

      // Enqueue FullPage scripts and styles
      add_action( 'elementor/frontend/before_enqueue_scripts', array( $this, 'OnElementorBeforeEnqueueScripts' ) );
      add_action( 'elementor/frontend/before_enqueue_styles', array( $this, 'OnElementorBeforeEnqueueStyles' ) );
      add_action( 'elementor/frontend/after_enqueue_scripts', array( $this, 'OnElementorAfterEnqueueScripts' ) );
      add_action( 'elementor/frontend/after_enqueue_styles', array( $this, 'OnElementorAfterEnqueueStyles' ) );

      // Template redirect
      add_action( 'template_redirect', array( $this, 'OnTemplateRedirect' ) );
      // Template include
      add_filter( 'template_include', array( $this, 'OnTemplateInclude' ) );
      // Remove unwanted JS from header
      add_action( 'wp_print_scripts', array( $this, 'OnWpPrintScripts' ) );
      // Add extra body class
      add_filter( 'body_class', array( $this, 'OnBodyClass' ) );

      // Register controls
      add_action( 'elementor/controls/controls_registered', array( $this, 'OnElementorControlsRegistered' ), 10, 1 );

      // FullPage settings tab
      add_action( 'elementor/element/wp-post/document_settings/after_section_end', array( $this, 'OnElementorAfterSectionEnd' ), 10, 2 );
      add_action( 'elementor/element/wp-page/document_settings/after_section_end', array( $this, 'OnElementorAfterSectionEnd' ), 10, 2 );
      // TODO: add custom post types ? add_action( 'elementor/element/page/document_settings/after_section_end', array( $this, 'OnElementorAfterSectionEnd' ), 10, 2);

      // Section options
      add_action( 'elementor/element/section/section_layout/after_section_end', array( $this, 'OnElementorSectionAfterSectionEnd' ), 10, 2 );
      add_action( 'elementor/frontend/section/before_render', array( $this, 'OnElementorSectionBeforeRender' ), 10, 1 );

      add_filter( 'elementor/frontend/builder_content_data', array( $this, 'OnElementorBuilderContentData' ), 10, 2 );

      add_action( 'elementor/element/after_add_attributes', array( $this, 'OnAfterAddAttributes' ), 10, 1 );

      // Parse CSS
      add_action( 'elementor/element/parse_css', array( $this, 'OnElementorParseCSS' ), 10, 2 );
      add_action( 'elementor/css-file/post/parse', array( $this, 'OnElementorPostParseCSS' ), 10, 1 );

      add_filter( 'elementor/frontend/the_content', array( $this, 'OnElementorContent' ), 10, 1 );
    }

    public function OnElementorEditorAfterEnqueueStyles() {
      wp_enqueue_style(
        $this->tag . '-editor',
        McwFullPageElementorGlobals::Url() . 'assets/editor/editor.min.css',
        '',
        McwFullPageElementorGlobals::Version(),
        'all'
      );
    }

    public function OnElementorEditorAfterEnqueueScripts() {
      if ( ! ( $this->pluginSettings->GetLicenseKey() && $this->pluginSettings->GetLicenseState() ) ) {
        return;
      }

      wp_enqueue_script(
        $this->tag . '-editor-js',
        McwFullPageElementorGlobals::Url() . 'assets/editor/editor.min.js',
        array( 'jquery' ),
        McwFullPageElementorGlobals::Version(),
        true
      );
    }

    public function OnElementorBeforeEnqueueScripts() {
      if ( ! ( $this->pluginSettings->GetLicenseKey() && $this->pluginSettings->GetLicenseState() ) ) {
        return;
      }

      if ( \Elementor\Plugin::instance()->editor->is_edit_mode() || \Elementor\Plugin::instance()->preview->is_preview_mode() ) {
        wp_enqueue_script(
          $this->tag . '-frontend-js',
          McwFullPageElementorGlobals::Url() . 'assets/frontend/frontend.min.js',
          array( 'jquery', 'elementor-frontend' ),
          McwFullPageElementorGlobals::Version(),
          true
        );
      }
    }

    public function OnElementorBeforeEnqueueStyles() {
      if ( ! ( $this->pluginSettings->GetLicenseKey() && $this->pluginSettings->GetLicenseState() ) ) {
        return;
      }

      if ( \Elementor\Plugin::instance()->editor->is_edit_mode() || \Elementor\Plugin::instance()->preview->is_preview_mode() ) {
        wp_enqueue_style(
          $this->tag . '-frontend',
          McwFullPageElementorGlobals::Url() . 'assets/frontend/frontend.min.css',
          array(),
          McwFullPageElementorGlobals::Version(),
          'all'
        );
      }
    }

    public function OnElementorAfterEnqueueScripts() {
      if ( ! $this->IsFullPageEnabled( false ) ) {
        return;
      }

      if ( ! ( $this->pluginSettings->GetLicenseKey() && $this->pluginSettings->GetLicenseState() ) ) {
        return;
      }

      $dep = array();
      if ( $this->IsFieldEnabled( 'jquery' ) ) {
        $dep[] = 'jquery';
      }

      $easing = $this->GetFieldValue( 'easing', 'css3_ease' );
      if ( substr( $easing, 0, 3 ) === 'js_' ) {
        wp_enqueue_script(
          $this->tag . '-easing-js',
          McwFullPageElementorGlobals::Url() . 'fullpage/vendors/easings.min.js',
          $dep,
          '1.3',
          true
        );
        $dep[] = $this->tag . '-easing-js';
      }

      if ( $this->IsFieldEnabled( 'scroll-overflow' ) && ! $this->IsFieldEnabled( 'scroll-bar' ) ) {
        wp_enqueue_script(
          $this->tag . '-iscroll-js',
          McwFullPageElementorGlobals::Url() . 'fullpage/vendors/scrolloverflow.min.js',
          $dep,
          '2.0.6',
          true
        );
        $dep[] = $this->tag . '-iscroll-js';
      }

      // Add filter
      if ( has_filter( 'mcw-fullpage-enqueue' ) ) {
        $dep = apply_filters( 'mcw-fullpage-enqueue', $dep, $this );
      }

      // Add fullpage JS file
      $jsPath = ( ! McwFullPageCommonLocal::GetState( $this->tag ) || $this->IsFieldEnabled( 'enable-extensions' ) ) ? 'fullpage/fullpage.extensions.min.js' : 'fullpage/fullpage.min.js';

      wp_enqueue_script(
        McwFullPageElementorGlobals::FullPageScriptName() . '-js',
        McwFullPageElementorGlobals::Url() . $jsPath,
        $dep,
        McwFullPageElementorGlobals::FullPageVersion(),
        true
      );
    }

    public function OnElementorAfterEnqueueStyles() {
      if ( ! $this->IsFullPageEnabled() ) {
        return;
      }

      if ( ! ( $this->pluginSettings->GetLicenseKey() && $this->pluginSettings->GetLicenseState() ) ) {
        return;
      }

      wp_enqueue_style(
        McwFullPageElementorGlobals::FullPageScriptName(),
        McwFullPageElementorGlobals::Url() . 'fullpage/fullpage.min.css',
        array(),
        McwFullPageElementorGlobals::FullPageVersion(),
        'all'
      );

      $nav = $this->GetSectionNavStyle();
      if ( $nav ) {
        wp_enqueue_style(
          $this->tag . '-section-nav',
          McwFullPageElementorGlobals::Url() . 'fullpage/nav/section/' . $nav . '.min.css',
          array( McwFullPageElementorGlobals::FullPageScriptName() ),
          McwFullPageElementorGlobals::FullPageVersion(),
          'all'
        );
      }

      $nav = $this->GetSlideNavStyle();
      if ( $nav ) {
        wp_enqueue_style(
          $this->tag . '-slide-nav',
          McwFullPageElementorGlobals::Url() . 'fullpage/nav/slide/' . $nav . '.min.css',
          array( McwFullPageElementorGlobals::FullPageScriptName() ),
          McwFullPageElementorGlobals::FullPageVersion(),
          'all'
        );
      }
    }

    // Called by template_redirect action
    public function OnTemplateRedirect() {
      if ( ! $this->IsFullPageEnabled() ) {
        return;
      }

      if ( is_archive() ) {
        return;
      }

      if ( ! ( $this->pluginSettings->GetLicenseKey() && $this->pluginSettings->GetLicenseState() ) ) {
        return;
      }

      $path = $this->GetTemplatePath( true );

      if ( false === $path ) {
        return;
      }

      include $path;
      exit();
    }

    // Called by template_include filter
    public function OnTemplateInclude( $template ) {
      if ( ! $this->IsFullPageEnabled() ) {
        return $template;
      }

      if ( is_archive() ) {
        return $template;
      }

      if ( ! ( $this->pluginSettings->GetLicenseKey() && $this->pluginSettings->GetLicenseState() ) ) {
        return $template;
      }

      $path = $this->GetTemplatePath( false );

      if ( false === $path ) {
        return $template;
      }

      return $path;
    }

    // Remove unwanted JS from header
    // Called by wp_print_scripts action
    public function OnWpPrintScripts() {
      // Get post
      global $post;
      // Get global scripts
      global $wp_scripts;

      // Get post
      global $post;
      if ( ! ( isset( $post ) && is_object( $post ) ) ) {
        return;
      }

      // Check if fullpage is enabled
      if ( ! $this->IsFullPageEnabled() ) {
        return false;
      }

      // Check if remove theme js is enabled
      if ( $this->IsFieldEnabled( 'remove-theme-js' ) ) {
        // Error handling
        if ( isset( $wp_scripts ) && isset( $wp_scripts->registered ) ) {
          // Get theme URL
          $themeUrl = get_bloginfo( 'template_directory' );

          // Remove theme related scripts
          foreach ( $wp_scripts->registered as $key => $script ) {
            if ( isset( $script->src ) ) {
              if ( false !== stristr( $script->src, $themeUrl ) ) {
                // Remove theme js
                unset( $wp_scripts->registered[ $key ] );
                // Remove from queue
                if ( isset( $wp_scripts->queue ) ) {
                  $wp_scripts->queue = array_diff( $wp_scripts->queue, array( $key ) );
                  $wp_scripts->queue = array_values( $wp_scripts->queue );
                }
              }
            }
          }
        }
      }

      // Check if remove js is enabled
      $removeJS = array_filter( explode( ',', $this->GetFieldValue( 'remove-js', '' ) ) );
      if ( isset( $removeJS ) && is_array( $removeJS ) && ! empty( $removeJS ) ) {
        // Error handling
        if ( isset( $wp_scripts ) && isset( $wp_scripts->registered ) ) {
          // Remove scripts
          foreach ( $wp_scripts->registered as $key => $script ) {
            if ( isset( $script->src ) ) {
              foreach ( $removeJS as $remove ) {
                if ( ! isset( $remove ) ) {
                  continue;
                }
                // Trim js
                $remove = trim( $remove );
                // Check if script includes the removed JS
                if ( stristr( $script->src, $remove ) !== false ) {
                  // Remove js
                  unset( $wp_scripts->registered[ $key ] );
                  // Remove from queue
                  if ( isset( $wp_scripts->queue ) ) {
                    $wp_scripts->queue = array_diff( $wp_scripts->queue, array( $key ) );
                    $wp_scripts->queue = array_values( $wp_scripts->queue );
                  }
                }
              }
            }
          }
        }
      }
    }

    public function OnBodyClass( $classes ) {
      if ( ! $this->IsFullPageEnabled() ) {
        return $classes;
      }

      if ( ! ( $this->pluginSettings->GetLicenseKey() && $this->pluginSettings->GetLicenseState() ) ) {
        return $classes;
      }

      $extra = 'wp-';
      if ( ! McwFullPageCommonLocal::GetState( $this->tag ) ) {
        $classes[] = $extra . 'fullpage' . '-js';
      }

      if ( $this->IsFieldEnabled( 'nav-big', 'section-navigation' ) ) {
        $classes[] = 'fp-big-nav';
      }

      if ( $this->IsFieldEnabled( 'nav-big', 'slide-navigation' ) ) {
        $classes[] = 'fp-big-slide-nav';
      }

      return $classes;
    }

    public function OnElementorControlsRegistered( $page ) {
      require McwFullPageElementorGlobals::Dir() . 'models/controls/control-arrows.php';
      require McwFullPageElementorGlobals::Dir() . 'models/controls/nav-colors.php';
      require McwFullPageElementorGlobals::Dir() . 'models/controls/nav-tooltip-colors.php';
      require McwFullPageElementorGlobals::Dir() . 'models/controls/nav-section.php';
      require McwFullPageElementorGlobals::Dir() . 'models/controls/nav-slide.php';
      require McwFullPageElementorGlobals::Dir() . 'models/controls/scroll-overflow.php';

      // Control Arrows group
      $page->add_group_control(
        McwFullPageElementorControlArrowsControl::get_type(),
        new McwFullPageElementorControlArrowsControl(
          array(
            // navigation, navigationPosition
            $this->GetId( 'arrow-style' ) => array(
              'label' => McwFullPageElementorGlobals::Translate( 'Control Arrows Style' ),
              'type' => ControlsManager::SELECT,
              'label_block' => true,
              'options' => array(
                'off' => McwFullPageElementorGlobals::Translate( 'Default' ),
                'modern' => McwFullPageElementorGlobals::Translate( 'Modern' ),
              ),
              'default' => 'off',
              'description' => McwFullPageElementorGlobals::Translate( 'Determines the style of the control arrow.' ),
            ),
            // Main color
            $this->GetId( 'arrow-color-main' ) => array(
              'label' => McwFullPageElementorGlobals::Translate( 'Control Arrows Color' ),
              'type' => ControlsManager::COLOR,
              'alpha' => false,
              'description' => McwFullPageElementorGlobals::Translate( 'Determines the color of the control arrow.' ),
            ),
          )
        )
      );

      // Navigation colors group
      $page->add_group_control(
        McwFullPageElementorNavColorsControl::get_type(),
        new McwFullPageElementorNavColorsControl(
          array(
            // Main color
            $this->GetId( 'color-main' ) => array(
              'label' => McwFullPageElementorGlobals::Translate( 'Main Color' ),
              'type' => ControlsManager::COLOR,
              'alpha' => false,
              'description' => McwFullPageElementorGlobals::Translate( 'The color of bullets when this section is active.' ),
            ),
            // Hover color
            $this->GetId( 'color-hover' ) => array(
              'label' => McwFullPageElementorGlobals::Translate( 'Hover Color' ),
              'type' => ControlsManager::COLOR,
              'alpha' => false,
              'description' => McwFullPageElementorGlobals::Translate( 'The hover color of bullets when this section is active. This color may not be used in some of the navigation styles.' ),
            ),
            // Active color
            $this->GetId( 'color-active' ) => array(
              'label' => McwFullPageElementorGlobals::Translate( 'Active Color' ),
              'type' => ControlsManager::COLOR,
              'alpha' => false,
              'description' => McwFullPageElementorGlobals::Translate( 'The active color of bullets when this section is active. This color may not be used in some of the navigation styles.' ),
            ),
          )
        )
      );

      // Tooltip colors group
      $page->add_group_control(
        McwFullPageElementorNavTooltipColorsControl::get_type(),
        new McwFullPageElementorNavTooltipColorsControl(
          array(
            // Main color
            $this->GetId( 'color-tooltip-background' ) => array(
              'label' => McwFullPageElementorGlobals::Translate( 'Tooltip Background Color' ),
              'type' => ControlsManager::COLOR,
              'alpha' => false,
              'description' => McwFullPageElementorGlobals::Translate( 'The background color of the navigation tooltip.' ),
            ),
            // Hover color
            $this->GetId( 'color-tooltip-text' ) => array(
              'label' => McwFullPageElementorGlobals::Translate( 'Tooltip Text Color' ),
              'type' => ControlsManager::COLOR,
              'alpha' => false,
              'description' => McwFullPageElementorGlobals::Translate( 'The text color of the navigation tooltip.' ),
            ),
          )
        )
      );

      // Section Navigation Group
      $page->add_group_control(
        McwFullPageElementorNavSectionControl::get_type(),
        new McwFullPageElementorNavSectionControl(
          array(
            // navigation, navigationPosition
            $this->GetId( 'nav' ) => array(
              'label' => McwFullPageElementorGlobals::Translate( 'Section Navigation' ),
              'type' => ControlsManager::SELECT,
              'label_block' => true,
              'options' => array(
                'off' => McwFullPageElementorGlobals::Translate( 'Off' ),
                'left' => McwFullPageElementorGlobals::Translate( 'Left' ),
                'right' => McwFullPageElementorGlobals::Translate( 'Right' ),
              ),
              'default' => 'off',
              'description' => McwFullPageElementorGlobals::Translate( 'The position of navigation bullets.' ),
            ),
            // Section Navigation Style
            $this->GetId( 'nav-style' ) => array(
              'label' => McwFullPageElementorGlobals::Translate( 'Section Navigation Style' ),
              'type' => ControlsManager::SELECT,
              'label_block' => true,
              'options' => array(
                'default' => McwFullPageElementorGlobals::Translate( 'Default' ),
                'circles' => McwFullPageElementorGlobals::Translate( 'Circles' ),
                'circles-inverted' => McwFullPageElementorGlobals::Translate( 'Circles Inverted' ),
                'expanding-circles' => McwFullPageElementorGlobals::Translate( 'Expanding Circles' ),
                'filled-circles' => McwFullPageElementorGlobals::Translate( 'Filled Circles' ),
                'filled-circle-within' => McwFullPageElementorGlobals::Translate( 'Filled Circles Within' ),
                'multiple-circles' => McwFullPageElementorGlobals::Translate( 'Multiple Circles' ),
                'rotating-circles' => McwFullPageElementorGlobals::Translate( 'Rotating Circles' ),
                'rotating-circles2' => McwFullPageElementorGlobals::Translate( 'Rotating Circles 2' ),
                'squares' => McwFullPageElementorGlobals::Translate( 'Squares' ),
                'squares-border' => McwFullPageElementorGlobals::Translate( 'Squares Border' ),
                'expanding-squares' => McwFullPageElementorGlobals::Translate( 'Expanding Squares' ),
                'filled-squares' => McwFullPageElementorGlobals::Translate( 'Filled Squares' ),
                'multiple-squares' => McwFullPageElementorGlobals::Translate( 'Multiple Squares' ),
                'squares-to-rombs' => McwFullPageElementorGlobals::Translate( 'Squares to Rombs' ),
                'multiple-squares-to-rombs' => McwFullPageElementorGlobals::Translate( 'Multiple Squares to Rombs' ),
                'filled-rombs' => McwFullPageElementorGlobals::Translate( 'Filled Rombs' ),
                'filled-bars' => McwFullPageElementorGlobals::Translate( 'Filled Bars' ),
                'story-telling' => McwFullPageElementorGlobals::Translate( 'Story Telling' ),
                // Maybe add in the future 'crazy-text-effect' => McwFullPageElementorGlobals::Translate( 'Crazy Text Effect' ),
              ),
              'default' => 'default',
              'condition' => array( $this->GetId( 'nav' ) . '!' => 'off' ),
              'description' => McwFullPageElementorGlobals::Translate( 'The section navigation style.' ),
            ),
            // Main color
            $this->GetId( 'color-main' ) => array(
              'label' => McwFullPageElementorGlobals::Translate( 'Main Color' ),
              'type' => ControlsManager::COLOR,
              'condition' => array( $this->GetId( 'nav' ) . '!' => 'off' ),
              'alpha' => false,
              'description' => McwFullPageElementorGlobals::Translate( 'The color of bullets on sections.' ),
            ),
            // Hover color
            $this->GetId( 'color-hover' ) => array(
              'label' => McwFullPageElementorGlobals::Translate( 'Hover Color' ),
              'type' => ControlsManager::COLOR,
              'condition' => array( $this->GetId( 'nav' ) . '!' => 'off' ),
              'alpha' => false,
              'description' => McwFullPageElementorGlobals::Translate( 'The hover color of bullets on sections. This color may not be used in some of the navigation styles.' ),
            ),
            // Active color
            $this->GetId( 'color-active' ) => array(
              'label' => McwFullPageElementorGlobals::Translate( 'Active Color' ),
              'type' => ControlsManager::COLOR,
              'condition' => array( $this->GetId( 'nav' ) . '!' => 'off' ),
              'alpha' => false,
              'description' => McwFullPageElementorGlobals::Translate( 'The active color of bullets on sections. This color may not be used in some of the navigation styles.' ),
            ),
            // Tooltip Background
            $this->GetId( 'tooltip-background-color' ) => array(
              'label' => McwFullPageElementorGlobals::Translate( 'Tooltip Background Color' ),
              'type' => ControlsManager::COLOR,
              'condition' => array( $this->GetId( 'nav' ) . '!' => 'off' ),
              'description' => McwFullPageElementorGlobals::Translate( 'The background color of the navigation tooltip.' ),
            ),
            // Tooltip Color
            $this->GetId( 'tooltip-text-color' ) => array(
              'label' => McwFullPageElementorGlobals::Translate( 'Tooltip Text Color' ),
              'type' => ControlsManager::COLOR,
              'condition' => array( $this->GetId( 'nav' ) . '!' => 'off' ),
              'alpha' => false,
              'description' => McwFullPageElementorGlobals::Translate( 'The text color of the navigation tooltip.' ),
            ),
            // Show Active Tooltip
            $this->GetId( 'show-active-tooltip' ) => array(
              'label' => McwFullPageElementorGlobals::Translate( 'Show Active Tooltip' ),
              'type' => ControlsManager::SWITCHER,
              'default' => 'no',
              'condition' => array( $this->GetId( 'nav' ) . '!' => 'off' ),
              'description' => McwFullPageElementorGlobals::Translate( 'Shows a persistent tooltip for the actively viewed section if enabled.' ),
            ),
            // Clickable Tooltip
            $this->GetId( 'click-tooltip' ) => array(
              'label' => McwFullPageElementorGlobals::Translate( 'Clickable Tooltip' ),
              'type' => ControlsManager::SWITCHER,
              'default' => 'no',
              'condition' => array( $this->GetId( 'nav' ) . '!' => 'off' ),
              'description' => McwFullPageElementorGlobals::Translate( 'The tooltips for the sections are clickable if enabled.' ),
            ),
            // Bigger navigation styles
            $this->GetId( 'nav-big' ) => array(
              'label' => McwFullPageElementorGlobals::Translate( 'Bigger Navigation' ),
              'type' => ControlsManager::SWITCHER,
              'default' => 'no',
              'condition' => array( $this->GetId( 'nav' ) . '!' => 'off' ),
              'description' => McwFullPageElementorGlobals::Translate( 'Sets bigger navigation bullets.' ),
            ),
          )
        )
      );

      // Slide Navigation Group
      $page->add_group_control(
        McwFullPageElementorNavSlideControl::get_type(),
        new McwFullPageElementorNavSlideControl(
          array(
            // slidesNavigation, slidesNavPosition
            $this->GetId( 'nav' ) => array(
              'label' => McwFullPageElementorGlobals::Translate( 'Slides Navigation' ),
              'type' => ControlsManager::SELECT,
              'label_block' => true,
              'options' => array(
                'off' => McwFullPageElementorGlobals::Translate( 'Off' ),
                'top' => McwFullPageElementorGlobals::Translate( 'Top' ),
                'bottom' => McwFullPageElementorGlobals::Translate( 'Bottom' ),
              ),
              'default' => 'off',
              'description' => McwFullPageElementorGlobals::Translate( 'The position of navigation bar for sliders.' ),
            ),
            // Slide Navigation Style
            $this->GetId( 'nav-style' ) => array(
              'label' => McwFullPageElementorGlobals::Translate( 'Slide Navigation Style' ),
              'type' => ControlsManager::SELECT,
              'label_block' => true,
              'options' => array(
                'default' => McwFullPageElementorGlobals::Translate( 'Default' ),
                'circles' => McwFullPageElementorGlobals::Translate( 'Circles' ),
                'circles-inverted' => McwFullPageElementorGlobals::Translate( 'Circles Inverted' ),
                'expanding-circles' => McwFullPageElementorGlobals::Translate( 'Expanding Circles' ),
                'filled-circles' => McwFullPageElementorGlobals::Translate( 'Filled Circles' ),
                'filled-circle-within' => McwFullPageElementorGlobals::Translate( 'Filled Circles Within' ),
                'multiple-circles' => McwFullPageElementorGlobals::Translate( 'Multiple Circles' ),
                'rotating-circles' => McwFullPageElementorGlobals::Translate( 'Rotating Circles' ),
                'rotating-circles2' => McwFullPageElementorGlobals::Translate( 'Rotating Circles 2' ),
                'squares' => McwFullPageElementorGlobals::Translate( 'Squares' ),
                'squares-border' => McwFullPageElementorGlobals::Translate( 'Squares Border' ),
                'expanding-squares' => McwFullPageElementorGlobals::Translate( 'Expanding Squares' ),
                'filled-squares' => McwFullPageElementorGlobals::Translate( 'Filled Squares' ),
                'multiple-squares' => McwFullPageElementorGlobals::Translate( 'Multiple Squares' ),
                'squares-to-rombs' => McwFullPageElementorGlobals::Translate( 'Squares to Rombs' ),
                'multiple-squares-to-rombs' => McwFullPageElementorGlobals::Translate( 'Multiple Squares to Rombs' ),
                'filled-rombs' => McwFullPageElementorGlobals::Translate( 'Filled Rombs' ),
                'filled-bars' => McwFullPageElementorGlobals::Translate( 'Filled Bars' ),
                'story-telling' => McwFullPageElementorGlobals::Translate( 'Story Telling' ),
              ),
              'default' => 'default',
              'condition' => array( $this->GetId( 'nav' ) . '!' => 'off' ),
              'description' => McwFullPageElementorGlobals::Translate( 'The slide navigation style.' ),
            ),
            // Main color
            $this->GetId( 'color-main' ) => array(
              'label' => McwFullPageElementorGlobals::Translate( 'Main Color' ),
              'type' => ControlsManager::COLOR,
              'condition' => array( $this->GetId( 'nav' ) . '!' => 'off' ),
              'alpha' => false,
              'description' => McwFullPageElementorGlobals::Translate( 'The color of bullets on slides.' ),
            ),
            // Hover color
            $this->GetId( 'color-hover' ) => array(
              'label' => McwFullPageElementorGlobals::Translate( 'Hover Color' ),
              'type' => ControlsManager::COLOR,
              'condition' => array( $this->GetId( 'nav' ) . '!' => 'off' ),
              'alpha' => false,
              'description' => McwFullPageElementorGlobals::Translate( 'The hover color of bullets on slides. This color may not be used in some of the navigation styles.' ),
            ),
            // Active color
            $this->GetId( 'color-active' ) => array(
              'label' => McwFullPageElementorGlobals::Translate( 'Active Color' ),
              'type' => ControlsManager::COLOR,
              'condition' => array( $this->GetId( 'nav' ) . '!' => 'off' ),
              'alpha' => false,
              'description' => McwFullPageElementorGlobals::Translate( 'The active color of bullets on slides. This color may not be used in some of the navigation styles.' ),
            ),
            // Bigger navigation styles
            $this->GetId( 'nav-big' ) => array(
              'label' => McwFullPageElementorGlobals::Translate( 'Bigger Slide Navigation' ),
              'type' => ControlsManager::SWITCHER,
              'default' => 'no',
              'condition' => array( $this->GetId( 'nav' ) . '!' => 'off' ),
              'description' => McwFullPageElementorGlobals::Translate( 'Sets bigger slide navigation bullets.' ),
            ),
          )
        )
      );

      // Scroll Overflow Group
      $page->add_group_control(
        McwFullPageElementorScrollOverflowControl::get_type(),
        new McwFullPageElementorScrollOverflowControl(
          array(
            // scrollOverflow / scrollbars
            $this->GetId( 'scrollbars' ) => array(
              'label' => McwFullPageElementorGlobals::Translate( 'Show Scroll Overflow Scrollbars' ),
              'type' => ControlsManager::SWITCHER,
              'default' => 'yes',
              'description' => McwFullPageElementorGlobals::Translate( 'Shows scrollbar when the scrolling is enabled inside the sections.' ),
            ),
            // scrollOverflow / fadeScrollbars
            $this->GetId( 'fade' ) => array(
              'label' => McwFullPageElementorGlobals::Translate( 'Fade Scroll Overflow Scrollbars' ),
              'type' => ControlsManager::SWITCHER,
              'description' => McwFullPageElementorGlobals::Translate( 'Fades scrollbar when unused.' ),
            ),
            // scrollOverflow / interactive scrollbars
            $this->GetId( 'interactive' ) => array(
              'label' => McwFullPageElementorGlobals::Translate( 'Interactive Scroll Overflow Scrollbars' ),
              'type' => ControlsManager::SWITCHER,
              'default' => 'yes',
              'description' => McwFullPageElementorGlobals::Translate( 'Makes scrollbar draggable and user can interact with it.' ),
            ),
          )
        )
      );
    }

    public function OnElementorAfterSectionEnd( $page, $args ) {
      $this->AddFullPageTab( $page );

      if ( ! ( $this->pluginSettings->GetLicenseKey() && $this->pluginSettings->GetLicenseState() ) ) {
        return;
      }

      $this->AddNavigationTab( $page );
      $this->AddScrollingTab( $page );
      $this->AddDesignTab( $page );
      $this->AddEventsTab( $page );
      $this->AddExtensionsTab( $page );
      $this->AddCustomizationsTab( $page );
      $this->AddAdvancedTab( $page );
    }

    public function OnElementorSectionAfterSectionEnd( $page, $args ) {
      if ( ! ( $this->pluginSettings->GetLicenseKey() && $this->pluginSettings->GetLicenseState() ) ) {
        return;
      }

      $this->AddSectionTab( $page );
    }

    public function OnElementorSectionBeforeRender( $section ) {
      if ( ! $this->IsFullPageEnabled() ) {
        return;
      }

      if ( ! ( $this->pluginSettings->GetLicenseKey() && $this->pluginSettings->GetLicenseState() ) ) {
        return;
      }

      if ( $this->GetSectionValue( $section, 'section-no-render', true ) ) {
        return;
      }

      $attr = array();
      if ( ! $this->IsSectionInner( $section ) ) {
        $attr['class'][] = 'mcw-fp-section';

        if ( $this->IsSectionFieldEnabled( $section, 'section-is-slide' ) ) {
          $attr['class'][] = 'mcw-fp-section-slide';
        }

        if ( $this->GetSectionValue( $section, 'section-behaviour', 'full' ) === 'auto' ) {
          $attr['class'][] = 'fp-auto-height';
        }

        if ( $this->GetSectionValue( $section, 'section-behaviour', 'full' ) === 'responsive' ) {
          $attr['class'][] = 'fp-auto-height-responsive';
        }

        if ( $this->IsSectionFieldEnabled( $section, 'section-scrollbars' ) ) {
          $attr['class'][] = 'fp-noscroll';
        }

        if ( ! $this->IsFieldEnabled( 'disable-anchors' ) ) {
          $attr['data-anchor'] = $this->GetAnchor( $section );
        }

        $tooltip = trim( $this->GetSectionValue( $section, 'section-nav-tooltip', '' ) );
        if ( isset( $tooltip ) && ! empty( $tooltip ) ) {
          $attr['data-tooltip'] = $tooltip;
        }

        $offsetSections = $this->GetExtensionsInfo( 'offset-sections' );
        if ( $offsetSections['active'] && $this->IsFieldEnabled( 'enable-extensions' ) && $this->IsFieldEnabled( 'extension-offset-sections' ) ) {
          $attr['data-percentage'] = $this->GetSectionValue( $section, 'data-percentage', '100' );
          $attr['data-centered'] = $this->IsSectionFieldEnabled( $section, 'data-centered' ) ? 'true' : 'false';
        }

        $dropEffect = $this->GetExtensionsInfo( 'drop-effect' );
        if ( $dropEffect['active'] && $this->IsFieldEnabled( 'enable-extensions' ) && $this->IsFieldEnabled( 'extension-drop-effect' ) ) {
          $attr['data-drop'] = $this->GetSectionValue( $section, 'section-data-drop', 'all' );
        }
      } else {
        $attr['class'][] = 'mcw-fp-slide';
        if ( ! $this->IsFieldEnabled( 'disable-anchors' ) ) {
          $anchor = $this->GetSlideAnchor( $section );
          if ( $anchor ) {
            $attr['data-anchor'] = $this->GetSlideAnchor( $section );
          }
        }
      }

      $section->add_render_attribute( '_wrapper', $attr );
    }

    public function OnElementorBuilderContentData( $data, $postId ) {
      if ( ! empty( $data ) && ( get_the_ID() !== $postId ) ) {
        $data = \Elementor\Plugin::instance()->db->iterate_data(
          $data,
          function( $element ) {
            if ( 'section' === $element['elType'] ) {
              $element['settings'][ $this->GetId( 'section-no-render' ) ] = true;
            }

            return $element;
          }
        );
      }

      return $data;
    }

    public function OnAfterAddAttributes( $section ) {
      if ( $section->get_data( 'elType' ) !== 'section' ) {
        return;
      }

      if ( ! $this->IsFullPageEnabled() ) {
        return;
      }

      if ( ! ( $this->pluginSettings->GetLicenseKey() && $this->pluginSettings->GetLicenseState() ) ) {
        return;
      }

      if ( $this->GetSectionValue( $section, 'section-no-render', true ) ) {
        return;
      }

      // Check if the ID and and fullpage anchor are same, if they are, remove the ID parameter, just in case
      $anchor = $section->get_render_attributes( '_wrapper', 'data-anchor' );
      $id = $section->get_render_attributes( '_wrapper', 'id' );

      if ( $anchor && $id ) {
        $anchor = is_array( $anchor ) ? $anchor[0] : $anchor;
        $id = is_array( $id ) ? $id[0] : $id;

        if ( strcasecmp( $id, $anchor ) === 0 ) {
          $section->remove_render_attribute( '_wrapper', 'id' );
        }
      }

      // Remove responsive hide class names from the main section only
      if ( ! $this->IsSectionInner( $section ) ) {
        $classes = $section->get_render_attributes( '_wrapper', 'class' );
        if ( $classes && is_array( $classes ) ) {
          $classes = array_diff( $classes, array( 'elementor-hidden-desktop', 'elementor-hidden-tablet', 'elementor-hidden-phone' ) );
          $section->set_render_attribute( '_wrapper', 'class', $classes );
        }
      }
    }

    public function OnElementorParseCSS( $post, $section ) {
      if ( $post instanceof Dynamic_CSS ) {
        return;
      }

      if ( $section->get_data( 'elType' ) !== 'section' ) {
        return;
      }

      if ( ! $this->IsFullPageEnabled() ) {
        return;
      }

      if ( ! ( $this->pluginSettings->GetLicenseKey() && $this->pluginSettings->GetLicenseState() ) ) {
        return;
      }

      if ( $this->GetSectionValue( $section, 'section-no-render', true ) ) {
        return;
      }

      if ( $post->get_post_id() != get_the_ID() ) {
        return;
      }

      // Get section selector
      $selector = '.elementor-element-' . $section->get_data( 'id' );

      // Add boxed CSS for the section
      $layout = $section->get_settings_for_display( 'layout' );
      if ( $layout === 'boxed' ) {
        $width = $section->get_settings_for_display( 'content_width' );
        if ( is_array( $width ) && isset( $width['unit'] ) && isset( $width['size'] ) ) {
          $width = ( empty( $width['size'] ) ? '1140' : $width['size'] ) . $width['unit'];
          $css = "$selector.elementor-section-boxed:not(.mcw-fp-section-slide)>.fp-tableCell>.elementor-container,$selector.elementor-section-boxed:not(.mcw-fp-section-slide)>.fp-tableCell>.fp-scrollable>.fp-scroller>.elementor-container{max-width:$width}";
          $post->get_stylesheet()->add_raw_css( $css );
        }
      }

      if ( $this->IsSectionFieldEnabled( $section, 'section-full-height-col', false ) ) {
        $css = "$selector>.fp-tableCell>.elementor-container,$selector>.elementor-container{height:100%}";
        $post->get_stylesheet()->add_raw_css( $css );
      }

      if ( $this->IsSectionInner( $section ) ) {
        return;
      }

      $anchor = $this->GetAnchor( $section );
      if ( $this->IsSectionFieldEnabled( $section, 'group-section-nav-colors', 'section-element-navigation' ) ) {
        $css = $this->navigationCSS->GetCustomCSS(
          $this->GetSectionNavStyle(),
          "body[class*='fp-viewing-{$anchor}'] #fp-nav",
          $this->GetSectionValue( $section, 'color-main', '', 'section-element-navigation' ),
          $this->GetSectionValue( $section, 'color-active', '', 'section-element-navigation' ),
          $this->GetSectionValue( $section, 'color-hover', '', 'section-element-navigation' )
        );

        $post->get_stylesheet()->add_raw_css( $css );
      }

      if ( $this->IsSectionFieldEnabled( $section, 'group-section-nav-tooltip-colors', 'section-element-navigation-tooltip' ) && ( $this->GetSectionValue( $section, 'section-nav-tooltip', '' ) !== '' ) ) {
        $tooltipBackground = $this->GetSectionValue( $section, 'color-tooltip-background', '', 'section-element-navigation-tooltip' );
        $tooltipColor = $this->GetSectionValue( $section, 'color-tooltip-text', '', 'section-element-navigation-tooltip' );

        if ( ! empty( $tooltipBackground ) || ! empty( $tooltipColor ) ) {
          $css = sprintf(
            "body[class*='fp-viewing-%s'] #fp-nav ul li .fp-tooltip{%s%s}",
            $anchor,
            empty( $tooltipBackground ) ? '' : ( 'background-color:' . $tooltipBackground . ';' ),
            empty( $tooltipColor ) ? '' : ( 'color:' . $tooltipColor . ';' )
          );
          $post->get_stylesheet()->add_raw_css( $css );
        }
      }

      if ( $this->IsSectionFieldEnabled( $section, 'section-is-slide' ) ) {
        $slideCSS = sprintf( '%s.mcw-fp-section-slide > .elementor-container{max-width:100%% !important;height:100%%;}', $selector );
        $slideCSS .= sprintf( '%s.mcw-fp-section-slide > .elementor-container >.elementor-row>.elementor-column>.elementor-element-populated{padding: 0;}', $selector );
        $slideCSS .= sprintf( '%s.mcw-fp-section-slide .fp-slides{width:100%%;}', $selector );
        $post->get_stylesheet()->add_raw_css( $slideCSS );

        $slideCSS = $this->GetSlideCSS( $anchor, $section );

        $post->get_stylesheet()->add_raw_css( $slideCSS );
      }
    }

    public function OnElementorPostParseCSS( $post ) {
      if ( $post instanceof Dynamic_CSS ) {
        return;
      }

      if ( ! $this->IsFullPageEnabled() ) {
        return;
      }

      if ( ! ( $this->pluginSettings->GetLicenseKey() && $this->pluginSettings->GetLicenseState() ) ) {
        return;
      }

      if ( $post->get_post_id() != get_the_ID() ) {
        return;
      }

      $css = array();
      $css[] = sprintf( '.%s .fp-slide{position:relative;padding: 0;}', $this->wrapper );

      $sectionNav = $this->GetFieldValue( 'nav', 'off', 'section-navigation' );
      $slideNav = $this->GetFieldValue( 'nav', 'off', 'slide-navigation' );
      if ( ( 'off' !== $sectionNav ) || ( 'off' !== $slideNav ) ) {
        $css[] = '.fp-sr-only{display:none !important}';
      }

      if ( ( 'off' !== $sectionNav ) ) {
        $tooltipBackground = $this->GetFieldValue( 'tooltip-background-color', '', 'section-navigation' );
        $tooltipColor = $this->GetFieldValue( 'tooltip-text-color', '', 'section-navigation' );

        if ( ! empty( $tooltipBackground ) || ! empty( $tooltipColor ) ) {
          $css[] = sprintf(
            '#fp-nav ul li .fp-tooltip{%s%s}',
            empty( $tooltipBackground ) ? '' : ( 'background-color:' . $tooltipBackground . ';' ),
            empty( $tooltipColor ) ? '' : ( 'color:' . $tooltipColor . ';' )
          );
        }

        $css[] = $this->navigationCSS->GetCustomCSS(
          $this->GetSectionNavStyle(),
          '#fp-nav',
          $this->GetFieldValue( 'color-main', '', 'section-navigation' ),
          $this->GetFieldValue( 'color-active', '', 'section-navigation' ),
          $this->GetFieldValue( 'color-hover', '', 'section-navigation' )
        );
      }

      if ( ( 'off' !== $slideNav ) ) {
        $css[] = $this->navigationCSS->GetCustomCSS(
          $this->GetSlideNavStyle(),
          '.fp-slidesNav',
          $this->GetFieldValue( 'color-main', '', 'slide-navigation' ),
          $this->GetFieldValue( 'color-active', '', 'slide-navigation' ),
          $this->GetFieldValue( 'color-hover', '', 'slide-navigation' )
        );
      }

      if ( $this->GetFieldValue( 'vertical-alignment', 'center' ) === 'bottom' ) {
        $css[] = sprintf( '.%s .fp-tableCell{vertical-align:bottom}', $this->wrapper );
      }

      if ( $this->IsFieldEnabled( 'remove-theme-margins' ) ) {
        $css[] = '.fp-enabled .mcw_fp_nomargin,.fp-enabled .fp-section{margin:0 !important;width:100% !important;max-width:100% !important;border:none !important}.fp-enabled .mcw_fp_nomargin{padding:0 !important}';
      }

      if ( $this->IsFieldEnabled( 'fixed-theme-header' ) ) {
        $css[] = sprintf(
          '.fp-enabled %1$s{position:fixed!important;left:0!important;right:0!important;width:100%%!important;top:0!important;z-index:9999}.fp-enabled .admin-bar %1$s{top:32px!important}.fp-enabled .fp-section.fp-auto-height{padding-top:0!important}@media screen and (max-width:782px){.fp-enabled .admin-bar %1$s{top:46px!important}}',
          $this->GetFieldValue( 'fixed-theme-header-selector', 'header' )
        );
      }

      if ( $this->IsFieldEnabled( 'enable-extensions' ) ) {
        $extensions = $this->GetExtensionsInfo();

        if ( $extensions['parallax']['active'] && $this->IsFieldEnabled( 'extension-parallax' ) ) {
          $easing = $this->GetFieldValue( 'easing', 'css3_ease' );

          $css[] = sprintf(
            '.fp-enabled .mcw-fp-wrapper .fp-section, .fp-enabled .mcw-fp-wrapper .fp-slide{transition:background-position %sms %s !important;}',
            $this->GetFieldValue( 'scrolling-speed', '1000' ),
            'css3_' === substr( $easing, 0, 5 ) ? substr( $easing, 5, strlen( $easing ) ) : 'ease'
          );
        }

        if ( $extensions['fading-effect']['active'] && $this->IsFieldEnabled( 'extension-fading-effect' ) ) {
          $easing = $this->GetFieldValue( 'easing', 'css3_ease' );

          $css[] = sprintf(
            '.fp-enabled .mcw-fp-wrapper .fp-section, .fp-enabled .mcw-fp-wrapper .fp-slide{transition:all %sms %s !important;}',
            $this->GetFieldValue( 'scrolling-speed', '1000' ),
            'css3_' === substr( $easing, 0, 5 ) ? substr( $easing, 5, strlen( $easing ) ) : 'ease'
          );
          $css[] = '.mcw-fp-section-slide > .elementor-container{height: 100%;}.mcw-fp-section-slide > .elementor-container .elementor-widget-wrap{display: block;height: 100%;}';
        }
      }

      if ( $this->IsFieldEnabled( 'control-arrows' ) && $this->IsFieldEnabled( 'group-section-control-arrows-type', 'control-arrows-options' ) ) {
        $color = $this->GetFieldValue( 'arrow-color-main', '#FFFFFF', 'control-arrows-options' );
        if ( $this->GetFieldValue( 'arrow-style', 'off', 'control-arrows-options' ) === 'modern' ) {
          $css[] = '@media screen and (max-width:700px) {
            .fp-controlArrow.fp-next,
            .fp-controlArrow.fp-prev {
              display: none;
            }
          }

          .fp-controlArrow svg {
            padding: 5px;
            pointer-events: none;
          }

          .fp-controlArrow {
            width: 70px !important;
            height: 90px;
            z-index: 99;
            -webkit-appearance: none;
            background: 0 0;
            border: 0 !important;
            outline: 0;
          }

          .fp-controlArrow.fp-next {
            right: 35px;
          }

          .fp-controlArrow.fp-prev {
            left: 35px;
          }

          .fp-controlArrow.fp-next:focus polyline,.fp-controlArrow.fp-next:hover polyline,
          .fp-controlArrow.fp-prev:focus polyline,.fp-controlArrow.fp-prev:hover polyline {
            stroke-width: 6;
          }

          .fp-controlArrow.fp-next:active polyline, .fp-controlArrow.fp-prev:active polyline {
            stroke-width: 10;
            transition: all .1s ease-in-out;
          }

          .fp-controlArrow polyline {
            transition: all 250ms ease-in-out;
            stroke-width: 3;
          }';
        } else {
          $css[] = '.fp-controlArrow.fp-prev{border-right-color:' . $color . ';}.fp-controlArrow.fp-next{border-left-color:' . $color . ';}';
        }
      }

      if ( $this->IsFieldEnabled( 'hide-content-before-load' ) ) {
        $css[] = 'body{opacity: 0;transition: opacity 1s ease;margin-top:100vh}.fp-enabled body{opacity: 1;margin-top:0}';
      }

      if ( $this->IsFieldEnabled( 'custom-css-enable' ) ) {
        $css[] = trim( $this->GetFieldValue( 'custom-css', '' ) );
      }

      $css = implode( '', $css );
      if ( ! empty( $css ) ) {
        $post->get_stylesheet()->add_raw_css( $css );
      }
    }

    public function OnElementorContent( $content ) {
      if ( ! $this->IsFullPageEnabled() ) {
        return $content;
      }

      if ( ! ( $this->pluginSettings->GetLicenseKey() && $this->pluginSettings->GetLicenseState() ) ) {
        return $content;
      }

      $content = sprintf(
        '<div class="%s">%s</div><script type="text/javascript">%s</script>',
        $this->wrapper,
        $content,
        $this->GetFullPageJS( $content )
      );

      return $content;
    }

    public function IsFullPageExtensionEnabled( $extension = false ) {
      if ( ! $this->IsFullPageEnabled() ) {
        return false;
      }

      if ( ! ( $this->pluginSettings->GetLicenseKey() && $this->pluginSettings->GetLicenseState() ) ) {
        return false;
      }

      $enabled = $this->IsFieldEnabled( 'enable-extensions' );

      if ( false === $extension ) {
        return $enabled;
      }

      if ( ! $enabled ) {
        return $enabled;
      }

      return $this->IsFieldEnabled( 'extension-' . $extension );
    }

    private function GetId( $suffix, $parent = null ) {
      $prefix = '';
      if ( $parent ) {
        $prefix = $this->tag . '-' . $parent . '_';
      }
      return $prefix . $this->tag . '-' . $suffix;
    }

    private function GetPageSettingsModel( $postId ) {
      $manager = \Elementor\Core\Settings\Manager::get_settings_managers( 'page' );
      if ( isset( $manager ) ) {
        return $manager->get_model( $postId );
      }

      return null;
    }

    // Return the field value of the specified id
    private function GetFieldValue( $id, $default = null, $parent = null ) {
      // Get the post ID
      $postId = get_the_ID();

      // Check the model is taken
      if ( ! ( isset( $this->pageSettingsModel[ $postId ] ) && $this->pageSettingsModel[ $postId ] ) ) {

        // Just a precaution
        if ( ! $postId ) {
          return $default;
        }

        $this->pageSettingsModel[ $postId ] = $this->GetPageSettingsModel( $postId );

        if ( ! ( isset( $this->pageSettingsModel[ $postId ] ) && $this->pageSettingsModel[ $postId ] ) ) {
          return $default;
        }
      }

      $val = $this->pageSettingsModel[ $postId ]->get_settings( $this->GetId( $id, $parent ) );

      // Add filter
      if ( has_filter( $this->tag . '-field-' . $id ) ) {
        $val = apply_filters( $this->tag . '-field-' . $id, $val );
      }

      // Return field value or default
      return ( empty( $val ) ? $default : $val );
    }

    private function GetSectionValue( $section, $id, $default = null, $parent = null ) {
      if ( 'section-no-render' === $id || 'section-is-inner' === $id ) {
        return $section->get_settings_for_display( $this->GetId( $id ) );
      }

      if ( isset( $section ) && $section ) {
        $val = $section->get_settings_for_display( $this->GetId( $id, $parent ) );

        return ( empty( $val ) ? $default : $val );
      }

      return $default;
    }

    // Checks if specified setting is on (used for metabox checkboxes) and returns true or false
    private function IsFieldEnabled( $id, $parent = null ) {
      // Get field value
      $val = $this->GetFieldValue( $id, 'no', $parent );

      // Return true if field is on
      return isset( $val ) && ( 'yes' === $val );
    }

    private function IsSectionFieldEnabled( $section, $id, $parent = null ) {
      // Get field value
      $val = $this->GetSectionValue( $section, $id, 'no', $parent );

      // Return true if field is on
      return isset( $val ) && ( 'yes' === $val );
    }

    private function IsSectionInner( $section ) {
      // Get inner sections
      $isInner = $section->get_data( 'isInner' ) || $this->GetSectionValue( $section, 'section-is-inner', false );
      return isset( $isInner ) && $isInner;
    }

    // Returns true if the specified field is on
    private function IsFieldOn( $id, $parent = null ) {
      return $this->IsFieldEnabled( $id, $parent ) ? 'true' : 'false';
    }

    private function MinimizeJavascriptSimple( $js ) {
      return preg_replace( array( '/\s+\n/', '/\n\s+/', '/ +/', '/\{\s+/', '/\s+=\s+/', '/\s*;\s*/' ), array( '', '', ' ', '{', '=', ';' ), $js );
    }

    private function MinimizeJavascriptAdvanced( $js, $level = 3 ) {
      if ( $level <= 0 ) {
        return $js;
      }

      if ( $level >= 1 ) {
        // Remove single-line code comments
        $js = preg_replace ( '/^[\t ]*?\/\/.*\s?/m', '', $js );

        // Remove end-of-line code comments
        $js = preg_replace ( '/([\s;})]+)\/\/.*/m', '\\1', $js );

        // Remove multi-line code comments
        $js = preg_replace ( '/\/\*[\s\S]*?\*\//', '', $js );
      }

      if ( $level >= 2 ) {
        // Remove leading whitespace
        $js = preg_replace ( '/^\s*/m', '', $js );

        // Replace multiple tabs with a single space
        $js = preg_replace ( '/\t+/m', ' ', $js );
      }

      if ( $level >= 3 ) {
        // Remove newlines
        $js = preg_replace ( '/[\r\n]+/', '', $js );
      }

      // Return final minified JavaScript
      return trim( $js );
    }

    private function IsFullPageEnabled( $check = true ) {
      if ( $check ) {
        $doc = \Elementor\Plugin::$instance->documents->get_current();
        if ( isset( $doc ) && ! empty( $doc ) && $doc->get_main_id() !== get_the_ID() ) {
          return false;
        }
      }

      return $this->IsFieldEnabled( 'enabled' );
    }

    private function GetSectionNavStyle() {
      if ( $this->GetFieldValue( 'nav', 'off', 'section-navigation' ) !== 'off' ) {
        return $this->GetFieldValue( 'nav-style', 'default', 'section-navigation' );
      }

      return false;
    }

    private function GetSlideNavStyle() {
      if ( $this->GetFieldValue( 'nav', 'off', 'slide-navigation' ) !== 'off' ) {
        return $this->GetFieldValue( 'nav-style', 'default', 'slide-navigation' );
      }

      return false;
    }

    private function GetSlideCSS( $anchor, $section ) {
      if ( ! $section ) {
        return '';
      }

      $count = 0;
      $css = '';
      foreach ( $section->get_children() as $child ) {
        if ( $child->get_data( 'elType' ) === 'section' ) {
          if ( ! $this->GetSectionValue( $child, 'section-no-render', true ) ) {
            $isInner = $child->get_data( 'isInner' );
            $isInner = isset( $isInner ) && $isInner;

            if ( $isInner ) {
              if ( $this->IsSectionFieldEnabled( $child, 'group-section-nav-colors', 'section-element-navigation' ) ) {
                $slideAnchor = $this->GetSlideAnchor( $child );

                if ( ! $slideAnchor ) {
                  $slideAnchor = $count;
                }

                $css .= $this->navigationCSS->GetCustomCSS(
                  $this->GetSlideNavStyle(),
                  "body[class*='fp-viewing-{$anchor}-{$slideAnchor}'] .fp-slidesNav",
                  $this->GetSectionValue( $child, 'color-main', '', 'section-element-navigation' ),
                  $this->GetSectionValue( $child, 'color-active', '', 'section-element-navigation' ),
                  $this->GetSectionValue( $child, 'color-hover', '', 'section-element-navigation' )
                );
              }

              $count++;

              continue;
            }
          }
        }

        $css .= $this->GetSlideCSS( $anchor, $child );
      }

      return $css;
    }

    private function GetAnchor( $section ) {
      $anchor = trim( $this->GetSectionValue( $section, 'section-anchor', '' ) );
      if ( isset( $anchor ) && ! empty( $anchor ) ) {
        return preg_replace( '/\s+/', '_', $anchor );
      }

      return 'section-' . $section->get_id();
    }

    private function GetSlideAnchor( $section ) {
      $anchor = trim( $this->GetSectionValue( $section, 'slide-anchor', '' ) );
      if ( isset( $anchor ) && ! empty( $anchor ) ) {
        return preg_replace( '/\s+/', '_', $anchor );
      }

      return false;
    }

    private function GetTemplatePath( $redirect ) {
      // Get post
      global $post;
      if ( ! ( isset( $post ) && is_object( $post ) ) ) {
        return false;
      }

      // Check if fullpage is enabled
      if ( ! $this->IsFullPageEnabled() ) {
        return false;
      }

      // Check if template is enabled
      if ( ! $this->IsFieldEnabled( 'enable-template' ) ) {
        return false;
      }

      if ( $redirect ) {
        // Check if template redirect is enabled
        if ( ! $this->IsFieldEnabled( 'enable-template-redirect' ) ) {
          return false;
        }
      } else {
        if ( $this->IsFieldEnabled( 'enable-template-redirect' ) ) {
          return false;
        }
      }

      $path = trim( $this->GetFieldValue( 'template-path' ) );
      if ( '' === $path ) {
        $path = plugin_dir_path( dirname( __FILE__ ) ) . 'template/template.php';
      }

      // Add filter
      if ( has_filter( $this->tag . '-template' ) ) {
        $path = apply_filters( $this->tag . '-template', $path );
      }

      if ( empty( $path ) ) {
        return false;
      }

      return $path;
    }

    private function GetNotInstalledExtensionHtml( $label, $text = 'Not Installed' ) {
      return sprintf(
        '<div class="elementor-control-field mcw-fullpage-elementor-disabled">
          <div class="elementor-control-title">%s</div>
          <div class="elementor-control-input-wrapper">%s</div>
        </div>',
        $label,
        McwFullPageElementorGlobals::Translate( $text )
      );
    }

    private function GetExtensionsInfo( $extension = '' ) {
      if ( ! $this->extensions ) {
        $this->extensions = McwFullPageElementorGlobals::GetExtensions();
      }

      if ( empty( $extension ) ) {
        return $this->extensions;
      }

      return $this->extensions[ $extension ];
    }

    private function GetExtensionKey( $extensions, $ext ) {
      // @deprecated: Extension keys are not stored in the page anymore. Remove this on 25.10.2021.
      // @deprecated: The new code would only return $extensions[ $ext ]['key'];
      $key = $this->GetFieldValue( 'extension-' . $ext . '-key' );
      return $extensions[ $ext ]['key'] ? $extensions[ $ext ]['key'] : ( $key ? $key : '' );
    }

    private function AddSectionTab( $page ) {
      $page->start_controls_section(
        $this->GetId( 'tab-fullpage-section' ),
        array(
          'label' => McwFullPageElementorGlobals::Translate( 'FullPage' ),
          'tab' => ControlsManager::TAB_LAYOUT,
          'condition' => array(
            $this->GetId( 'section-no-render' ) => false,
          ),
        )
      );

      $page->add_control(
        $this->GetId( 'section-no-render' ),
        array(
          'type' => ControlsManager::HIDDEN,
          'default' => true,
        )
      );

      $page->add_control(
        $this->GetId( 'section-is-inner' ),
        array(
          'type' => ControlsManager::HIDDEN,
          'default' => false,
        )
      );

      $page->add_control(
        $this->GetId( 'enable-data-percentage' ),
        array(
          'type' => ControlsManager::HIDDEN,
          'default' => false,
        )
      );

      $page->add_control(
        $this->GetId( 'enable-data-drop' ),
        array(
          'type' => ControlsManager::HIDDEN,
          'default' => false,
        )
      );

      $page->add_control(
        $this->GetId( 'section-is-slide' ),
        array(
          'label' => McwFullPageElementorGlobals::Translate( 'Has Horizontal Slides?' ),
          'type' => ControlsManager::SWITCHER,
          'default' => 'no',
          'description' => McwFullPageElementorGlobals::Translate( 'Converts section to slide section when enabled.' ),
          'condition' => array(
            $this->GetId( 'section-is-inner' ) => false,
          ),
        )
      );

      $page->add_control(
        $this->GetId( 'section-behaviour' ),
        array(
          'label' => McwFullPageElementorGlobals::Translate( 'Section Behaviour' ),
          'type' => ControlsManager::SELECT,
          'options' => array(
            'full' => McwFullPageElementorGlobals::Translate( 'Full Height' ),
            'auto' => McwFullPageElementorGlobals::Translate( 'Auto Height' ),
            'responsive' => McwFullPageElementorGlobals::Translate( 'Responsive Auto Height' ),
          ),
          'default' => 'full',
          'condition' => array(
            $this->GetId( 'section-is-inner' ) => false,
          ),
        )
      );

      $page->add_control(
        $this->GetId( 'section-anchor' ),
        array(
          'label' => McwFullPageElementorGlobals::Translate( 'FullPage Anchor' ),
          'type' => ControlsManager::TEXT,
          'default' => '',
          'condition' => array(
            $this->GetId( 'section-is-inner' ) => false,
          ),
        )
      );

      $page->add_control(
        $this->GetId( 'slide-anchor' ),
        array(
          'label' => McwFullPageElementorGlobals::Translate( 'Slide Anchor' ),
          'type' => ControlsManager::TEXT,
          'default' => '',
          'condition' => array(
            $this->GetId( 'section-is-inner' ) => true,
          ),
        )
      );

      $page->add_control(
        $this->GetId( 'section-nav-tooltip' ),
        array(
          'label' => McwFullPageElementorGlobals::Translate( 'Navigation Tooltip' ),
          'type' => ControlsManager::TEXT,
          'default' => '',
          'condition' => array(
            $this->GetId( 'section-is-inner' ) => false,
          ),
        )
      );

      $page->add_control(
        $this->GetId( 'section-scrollbars' ),
        array(
          'label' => McwFullPageElementorGlobals::Translate( 'Disable Scroll Overflow' ),
          'type' => ControlsManager::SWITCHER,
          'default' => 'no',
          'condition' => array(
            $this->GetId( 'section-is-inner' ) => false,
          ),
        )
      );

      $page->add_control(
        $this->GetId( 'section-full-height-col' ),
        array(
          'label' => McwFullPageElementorGlobals::Translate( 'Full Height Columns' ),
          'type' => ControlsManager::SWITCHER,
          'default' => 'no',
        )
      );

      $page->add_control(
        $this->GetId( 'data-percentage' ),
        array(
          'label' => McwFullPageElementorGlobals::Translate( 'Data Percentage' ),
          'type' => ControlsManager::NUMBER,
          'min' => 0,
          'max' => 100,
          'step' => 1,
          'default' => '100',
          'condition' => array(
            $this->GetId( 'section-is-inner' ) => false,
            $this->GetId( 'enable-data-percentage' ) => true,
          ),
        )
      );

      $page->add_control(
        $this->GetId( 'data-centered' ),
        array(
          'label' => McwFullPageElementorGlobals::Translate( 'Data Centered' ),
          'type' => ControlsManager::SWITCHER,
          'default' => 'yes',
          'condition' => array(
            $this->GetId( 'section-is-inner' ) => false,
            $this->GetId( 'enable-data-percentage' ) => true,
          ),
        )
      );

      $page->add_control(
        $this->GetId( 'section-data-drop' ),
        array(
          'label' => McwFullPageElementorGlobals::Translate( 'Drop Effect Target' ),
          'type' => ControlsManager::SELECT,
          'options' => array(
            'all' => McwFullPageElementorGlobals::Translate( 'All' ),
            'up' => McwFullPageElementorGlobals::Translate( 'Up' ),
            'down' => McwFullPageElementorGlobals::Translate( 'Down' ),
          ),
          'default' => 'all',
          'condition' => array(
            $this->GetId( 'section-is-inner' ) => false,
            $this->GetId( 'enable-data-drop' ) => true,
          ),
        )
      );

      $page->add_group_control(
        McwFullPageElementorNavColorsControl::get_type(),
        array(
          'name' => $this->GetId( 'section-element-navigation' ),
          'label' => McwFullPageElementorGlobals::Translate( 'Navigation Colors' ),
        )
      );

      $page->add_group_control(
        McwFullPageElementorNavTooltipColorsControl::get_type(),
        array(
          'name' => $this->GetId( 'section-element-navigation-tooltip' ),
          'label' => McwFullPageElementorGlobals::Translate( 'Tooltip Colors' ),
          'condition' => array(
            $this->GetId( 'section-is-inner' ) => false,
            $this->GetId( 'section-nav-tooltip' ) . '!' => '',
          ),
        )
      );

      $page->end_controls_section();
    }

    private function AddFullPageTab( $page ) {
      $page->start_controls_section(
        $this->GetId( 'tab-activate' ),
        array(
          'label' => McwFullPageElementorGlobals::Translate( 'FullPage' ),
          'tab' => $this->tab,
        )
      );

      if ( ! ( $this->pluginSettings->GetLicenseKey() && $this->pluginSettings->GetLicenseState() ) ) {
        $page->add_control(
          $this->GetId( 'enabled-disabled' ),
          array(
            'label' => $this->GetNotInstalledExtensionHtml( McwFullPageElementorGlobals::Translate( 'Enable FullPage' ), 'Not Activated' ),
            'type' => ControlsManager::RAW_HTML,
          )
        );
      } else {
        $page->add_control(
          $this->GetId( 'enabled' ),
          array(
            'label' => McwFullPageElementorGlobals::Translate( 'Enable FullPage' ),
            'type' => ControlsManager::SWITCHER,
            'default' => 'no',
            'label_on' => McwFullPageElementorGlobals::Translate( 'Yes' ),
            'label_off' => McwFullPageElementorGlobals::Translate( 'No' ),
            'return_value' => 'yes',
          )
        );

        $page->add_control(
          'update-page',
          array(
            'label' => '<div class="elementor-update-preview mcw-fullpage-elementor-update-button"><div class="elementor-update-preview-button-wrapper"><button class="elementor-update-preview-button elementor-button elementor-button-success">' . McwFullPageElementorGlobals::Translate( 'Update Preview' ) . '</button></div></div>',
            'type' => ControlsManager::RAW_HTML,
          )
        );

        $page->add_control(
          $this->GetId( 'help' ),
          array(
            'label' => false,
            'type' => ControlsManager::RAW_HTML,
            'raw' => '<div class="mcw-fullpage-elementor-help"><a class="elementor-panel__editor__help__link" href="https://www.meceware.com/docs/fullpage-for-elementor/" target="_blank">
              ' . McwFullPageElementorGlobals::Translate( 'Need Help' ) . '<i class="eicon-help-o"></i></a>',
          )
        );
      }

      $page->end_controls_section();
    }

    private function AddNavigationTab( $page ) {
      $page->start_controls_section(
        $this->GetId( 'tab-navigation' ),
        array(
          'label' => McwFullPageElementorGlobals::Translate( 'Navigation' ),
          'tab' => $this->tab,
          'condition' => array( $this->GetId( 'enabled' ) => 'yes' ),
        )
      );

      $page->add_group_control(
        McwFullPageElementorNavSectionControl::get_type(),
        array(
          'name' => $this->GetId( 'section-navigation' ),
          'label' => McwFullPageElementorGlobals::Translate( 'Section Navigation' ),
        )
      );

      $page->add_group_control(
        McwFullPageElementorNavSlideControl::get_type(),
        array(
          'name' => $this->GetId( 'slide-navigation' ),
          'label' => McwFullPageElementorGlobals::Translate( 'Slide Navigation' ),
        )
      );

      $page->add_control(
        $this->GetId( 'control-arrows' ),
        array(
          'label' => McwFullPageElementorGlobals::Translate( 'Control Arrows' ),
          'type' => ControlsManager::SWITCHER,
          'default' => 'no',
          'description' => McwFullPageElementorGlobals::Translate( 'Determines whether to use control arrows for the slides to move right or left.' ),
        )
      );

      $page->add_group_control(
        McwFullPageElementorControlArrowsControl::get_type(),
        array(
          'name' => $this->GetId( 'control-arrows-options' ),
          'label' => McwFullPageElementorGlobals::Translate( 'Control Arrows Options' ),
          'condition' => array(
            $this->GetId( 'control-arrows' ) => 'yes',
          ),
        )
      );

      $page->add_control(
        $this->GetId( 'lock-anchors' ),
        array(
          'label' => McwFullPageElementorGlobals::Translate( 'Lock Anchors' ),
          'type' => ControlsManager::SWITCHER,
          'default' => 'no',
          'description' => McwFullPageElementorGlobals::Translate( 'Determines whether anchors in the URL will have any effect.' ),
        )
      );

      $page->add_control(
        $this->GetId( 'disable-anchors' ),
        array(
          'label' => McwFullPageElementorGlobals::Translate( 'Disable Anchors' ),
          'type' => ControlsManager::SWITCHER,
          'default' => 'no',
          'description' => McwFullPageElementorGlobals::Translate( 'Determines whether to enable or disable all section anchors.' ),
        )
      );

      $page->add_control(
        $this->GetId( 'animate-anchor' ),
        array(
          'label' => McwFullPageElementorGlobals::Translate( 'Animate Anchor' ),
          'type' => ControlsManager::SWITCHER,
          'default' => 'yes',
          'description' => McwFullPageElementorGlobals::Translate( 'Defines whether the load of the site when given anchor (#) will scroll with animation to its destination.' ),
        )
      );

      $page->add_control(
        $this->GetId( 'keyboard-scrolling' ),
        array(
          'label' => McwFullPageElementorGlobals::Translate( 'Keyboard Scrolling' ),
          'type' => ControlsManager::SWITCHER,
          'default' => 'yes',
          'description' => McwFullPageElementorGlobals::Translate( 'Defines if the content can be navigated using the keyboard.' ),
        )
      );

      $page->add_control(
        $this->GetId( 'record-history' ),
        array(
          'label' => McwFullPageElementorGlobals::Translate( 'Record History' ),
          'type' => ControlsManager::SWITCHER,
          'default' => 'yes',
          'description' => McwFullPageElementorGlobals::Translate( 'Defines whether to push the state of the site to the browsers history, so back button will work on section navigation.' ),
        )
      );

      $page->end_controls_section();
    }

    private function AddScrollingTab( $page ) {
      $page->start_controls_section(
        $this->GetId( 'tab-scrolling' ),
        array(
          'label' => McwFullPageElementorGlobals::Translate( 'Scrolling' ),
          'tab' => $this->tab,
          'condition' => array( $this->GetId( 'enabled' ) => 'yes' ),
        )
      );

      $page->add_control(
        $this->GetId( 'auto-scrolling' ),
        array(
          'label' => McwFullPageElementorGlobals::Translate( 'Auto Scrolling' ),
          'type' => ControlsManager::SWITCHER,
          'default' => 'yes',
          'description' => McwFullPageElementorGlobals::Translate( 'Defines whether to use the automatic scrolling or the normal one.' ),
        )
      );

      $page->add_control(
        $this->GetId( 'fit-to-section' ),
        array(
          'label' => McwFullPageElementorGlobals::Translate( 'Fit To Section' ),
          'type' => ControlsManager::SWITCHER,
          'default' => 'yes',
          'description' => McwFullPageElementorGlobals::Translate( 'Determines whether or not to fit sections to the viewport or not.' ),
        )
      );

      $page->add_control(
        $this->GetId( 'fit-to-section-delay' ),
        array(
          'label' => McwFullPageElementorGlobals::Translate( 'Fit To Section Delay' ),
          'type' => ControlsManager::NUMBER,
          'min' => 0,
          'step' => 100,
          'default' => '1000',
          'description' => McwFullPageElementorGlobals::Translate( 'The delay in miliseconds for section fitting.' ),
          'condition' => array(
            $this->GetId( 'fit-to-section' ) => 'yes',
          ),
        )
      );

      $page->add_control(
        $this->GetId( 'scroll-bar' ),
        array(
          'label' => McwFullPageElementorGlobals::Translate( 'Scroll Bar' ),
          'type' => ControlsManager::SWITCHER,
          'default' => 'no',
          'description' => McwFullPageElementorGlobals::Translate( 'Determines whether to use the scrollbar for the site or not.' ),
        )
      );

      $page->add_control(
        $this->GetId( 'scroll-overflow' ),
        array(
          'label' => McwFullPageElementorGlobals::Translate( 'Scroll Overflow' ),
          'type' => ControlsManager::SWITCHER,
          'default' => 'no',
          'condition' => array( $this->GetId( 'scroll-bar' ) . '!' => 'yes' ),
          'description' => McwFullPageElementorGlobals::Translate( 'Defines whether or not to create a scroll for the section in case the content is bigger than the height of it. (Disabled when Scrollbars are enabled.)' ),
        )
      );

      $page->add_group_control(
        McwFullPageElementorScrollOverflowControl::get_type(),
        array(
          'name' => $this->GetId( 'scroll-overflow-options' ),
          'label' => McwFullPageElementorGlobals::Translate( 'Scroll Overflow Options' ),
          'condition' => array(
            $this->GetId( 'scroll-bar' ) . '!' => 'yes',
            $this->GetId( 'scroll-overflow' ) => 'yes',
          ),
        )
      );

      $page->add_control(
        $this->GetId( 'big-sections-destination' ),
        array(
          'label' => McwFullPageElementorGlobals::Translate( 'Big Sections Destination' ),
          'type' => ControlsManager::SELECT,
          'options' => array(
            'default' => McwFullPageElementorGlobals::Translate( 'Default' ),
            'top' => McwFullPageElementorGlobals::Translate( 'Top' ),
            'bottom' => McwFullPageElementorGlobals::Translate( 'Bottom' ),
          ),
          'default' => 'default',
          'description' => McwFullPageElementorGlobals::Translate( 'Defines how to scroll to a section which size is bigger than the viewport.' ),
        )
      );

      $page->add_control(
        $this->GetId( 'continuous-vertical' ),
        array(
          'label' => McwFullPageElementorGlobals::Translate( 'Continuous Vertical' ),
          'type' => ControlsManager::SWITCHER,
          'default' => 'no',
          'description' => McwFullPageElementorGlobals::Translate( 'Determines vertical scrolling is continuous.' ),
          'condition' => array(
            $this->GetId( 'scroll-bar' ) . '!' => 'yes',
            $this->GetId( 'auto-scrolling' ) => 'yes',
          ),
        )
      );

      $page->add_control(
        $this->GetId( 'loop-bottom' ),
        array(
          'label' => McwFullPageElementorGlobals::Translate( 'Loop Bottom' ),
          'type' => ControlsManager::SWITCHER,
          'default' => 'no',
          'description' => McwFullPageElementorGlobals::Translate( 'Defines whether scrolling down in the last section should scroll to the first one or not.' ),
          'condition' => array(
            $this->GetId( 'scroll-bar' ) . '!' => 'yes',
            $this->GetId( 'auto-scrolling' ) => 'yes',
            $this->GetId( 'continuous-vertical' ) . '!' => 'yes',
          ),
        )
      );

      $page->add_control(
        $this->GetId( 'loop-top' ),
        array(
          'label' => McwFullPageElementorGlobals::Translate( 'Loop Top' ),
          'type' => ControlsManager::SWITCHER,
          'default' => 'no',
          'description' => McwFullPageElementorGlobals::Translate( 'Defines whether scrolling up in the first section should scroll to the last one or not.' ),
          'condition' => array(
            $this->GetId( 'scroll-bar' ) . '!' => 'yes',
            $this->GetId( 'auto-scrolling' ) => 'yes',
            $this->GetId( 'continuous-vertical' ) . '!' => 'yes',
          ),
        )
      );

      $page->add_control(
        $this->GetId( 'loop-slides' ),
        array(
          'label' => McwFullPageElementorGlobals::Translate( 'Loop Slides' ),
          'type' => ControlsManager::SWITCHER,
          'default' => 'yes',
          'description' => McwFullPageElementorGlobals::Translate( 'Defines whether horizontal sliders will loop after reaching the last or previous slide or not.' ),
        )
      );

      $page->add_control(
        $this->GetId( 'easing' ),
        array(
          'label' => McwFullPageElementorGlobals::Translate( 'Easing' ),
          'type' => ControlsManager::SELECT,
          'options' => array(
            'css3_ease' => McwFullPageElementorGlobals::Translate( 'CSS3 - Ease' ),
            'css3_linear' => McwFullPageElementorGlobals::Translate( 'CSS3 - Linear' ),
            'css3_ease-in' => McwFullPageElementorGlobals::Translate( 'CSS3 - Ease In' ),
            'css3_ease-out' => McwFullPageElementorGlobals::Translate( 'CSS3 - Ease Out' ),
            'css3_ease-in-out' => McwFullPageElementorGlobals::Translate( 'CSS3 - Ease In Out' ),
            'js_linear' => McwFullPageElementorGlobals::Translate( 'JS - Linear' ),
            'js_swing' => McwFullPageElementorGlobals::Translate( 'JS - Swing' ),
            'js_easeInQuad' => McwFullPageElementorGlobals::Translate( 'JS - Ease In Quad' ),
            'js_easeOutQuad' => McwFullPageElementorGlobals::Translate( 'JS - Ease Out Quad' ),
            'js_easeInOutQuad' => McwFullPageElementorGlobals::Translate( 'JS - Ease In Out Quad' ),
            // 'js_easeInCubic' => McwFullPageElementorGlobals::Translate( 'JS - Ease In Cubic' ),
            // 'js_easeOutCubic' => McwFullPageElementorGlobals::Translate( 'JS - Ease Out Cubic' ),
            'js_easeInOutCubic' => McwFullPageElementorGlobals::Translate( 'JS - Ease In Out Cubic' ),
            // 'js_easeInQuart' => McwFullPageElementorGlobals::Translate( 'JS - Ease In Quart' ),
            // 'js_easeOutQuart' => McwFullPageElementorGlobals::Translate( 'JS - Ease Out Quart' ),
            // 'js_easeInOutQuart' => McwFullPageElementorGlobals::Translate( 'JS - Ease In Out Quart' ),
            // 'js_easeInQuint' => McwFullPageElementorGlobals::Translate( 'JS - Ease In Quint' ),
            // 'js_easeOutQuint' => McwFullPageElementorGlobals::Translate( 'JS - Ease Out Quint' ),
            // 'js_easeInOutQuint' => McwFullPageElementorGlobals::Translate( 'JS - Ease In Out Quint' ),
            // 'js_easeInExpo' => McwFullPageElementorGlobals::Translate( 'JS - Ease In Expo' ),
            // 'js_easeOutExpo' => McwFullPageElementorGlobals::Translate( 'JS - Ease Out Expo' ),
            // 'js_easeInOutExpo' => McwFullPageElementorGlobals::Translate( 'JS - Ease In Out Expo' ),
            //'js_easeInSine' => McwFullPageElementorGlobals::Translate( 'JS - Ease In Sine' ),
            //'js_easeOutSine' => McwFullPageElementorGlobals::Translate( 'JS - Ease Out Sine' ),
            'js_easeInOutSine' => McwFullPageElementorGlobals::Translate( 'JS - Ease In Out Sine' ),
            // 'js_easeInCirc' => McwFullPageElementorGlobals::Translate( 'JS - Ease In Circ' ),
            // 'js_easeOutCirc' => McwFullPageElementorGlobals::Translate( 'JS - Ease Out Circ' ),
            'js_easeInOutCirc' => McwFullPageElementorGlobals::Translate( 'JS - Ease In Out Circ' ),
            // 'js_easeInElastic' => McwFullPageElementorGlobals::Translate( 'JS - Ease In Elastic' ),
            // 'js_easeOutElastic' => McwFullPageElementorGlobals::Translate( 'JS - Ease Out Elastic' ),
            // 'js_easeInOutElastic' => McwFullPageElementorGlobals::Translate( 'JS - Ease In Out Elastic' ),
            // 'js_easeInBack' => McwFullPageElementorGlobals::Translate( 'JS - Ease In Back' ),
            // 'js_easeOutBack' => McwFullPageElementorGlobals::Translate( 'JS - Ease Out Back' ),
            'js_easeInOutBack' => McwFullPageElementorGlobals::Translate( 'JS - Ease In Out Back' ),
            // 'js_easeInBounce' => McwFullPageElementorGlobals::Translate( 'JS - Ease In Bounce' ),
            // 'js_easeOutBounce' => McwFullPageElementorGlobals::Translate( 'JS - Ease Out Bounce' ),
            // 'js_easeInOutBounce' => McwFullPageElementorGlobals::Translate( 'JS - Ease In Out Bounce' ),
          ),
          'default' => 'css3_ease',
          'description' => McwFullPageElementorGlobals::Translate( 'Defines how to scroll to a section which size is bigger than the viewport. (Note: JS animations are slower.)' ),
        )
      );

      $page->add_control(
        $this->GetId( 'scrolling-speed' ),
        array(
          'label' => McwFullPageElementorGlobals::Translate( 'Scrolling Speed' ),
          'type' => ControlsManager::NUMBER,
          'min' => 0,
          'step' => 10,
          'default' => '1000',
          'description' => McwFullPageElementorGlobals::Translate( 'Speed in miliseconds for the scrolling transitions.' ),
        )
      );

      $page->end_controls_section();
    }

    private function AddDesignTab( $page ) {
      $page->start_controls_section(
        $this->GetId( 'tab-design' ),
        array(
          'label' => McwFullPageElementorGlobals::Translate( 'Design' ),
          'tab' => $this->tab,
          'condition' => array( $this->GetId( 'enabled' ) => 'yes' ),
        )
      );

      $page->add_control(
        $this->GetId( 'vertical-alignment' ),
        array(
          'label' => McwFullPageElementorGlobals::Translate( 'Vertical Alignment' ),
          'type' => ControlsManager::SELECT,
          'options' => array(
            'top' => McwFullPageElementorGlobals::Translate( 'Top' ),
            'center' => McwFullPageElementorGlobals::Translate( 'Center' ),
            'bottom' => McwFullPageElementorGlobals::Translate( 'Bottom' ),
          ),
          'default' => 'center',
          'description' => McwFullPageElementorGlobals::Translate( 'Determines the position of the content vertically in the section.' ),
        )
      );

      $page->add_control(
        $this->GetId( 'responsive-width' ),
        array(
          'label' => McwFullPageElementorGlobals::Translate( 'Responsive Width' ),
          'type' => ControlsManager::NUMBER,
          'min' => 0,
          'step' => 1,
          'default' => '0',
          'description' => McwFullPageElementorGlobals::Translate( 'Normal scroll will be used under the defined width in pixels. (autoScrolling: false)' ),
        )
      );

      $page->add_control(
        $this->GetId( 'responsive-height' ),
        array(
          'label' => McwFullPageElementorGlobals::Translate( 'Responsive Height' ),
          'type' => ControlsManager::NUMBER,
          'min' => 0,
          'step' => 1,
          'default' => '0',
          'description' => McwFullPageElementorGlobals::Translate( 'Normal scroll will be used under the defined height in pixels. (autoScrolling: false)' ),
        )
      );

      $page->add_control(
        $this->GetId( 'fixed-elements' ),
        array(
          'label' => McwFullPageElementorGlobals::Translate( 'Fixed Elements' ),
          'type' => ControlsManager::TEXT,
          'default' => '',
          'description' => McwFullPageElementorGlobals::Translate( 'Defines which elements will be taken off the scrolling structure of the plugin which is necessary when using the keep elements fixed with css. Enter comma seperated element selectors. (example: #element1, .element2)' ),
        )
      );

      $page->add_control(
        $this->GetId( 'normal-scroll-elements' ),
        array(
          'label' => McwFullPageElementorGlobals::Translate( 'Normal Scroll Elements' ),
          'type' => ControlsManager::TEXT,
          'default' => '',
          'description' => McwFullPageElementorGlobals::Translate( 'If you want to avoid the auto scroll when scrolling over some elements, this is the option you need to use. (useful for maps, scrolling divs etc.) Enter comma seperated element selectors. (example: #element1, .element2)' ),
        )
      );

      $page->add_control(
        $this->GetId( 'custom-css-enable' ),
        array(
          'label' => McwFullPageElementorGlobals::Translate( 'Custom CSS' ),
          'type' => ControlsManager::SWITCHER,
          'default' => 'yes',
        )
      );

      $page->add_control(
        $this->GetId( 'custom-css' ),
        array(
          'type' => ControlsManager::CODE,
          'language' => 'css',
          'seperator' => 'none',
          'show_label' => false,
          'default' => '',
          'condition' => array( $this->GetId( 'custom-css-enable' ) => 'yes' ),
        )
      );

      $page->end_controls_section();
    }

    private function AddEventsTab( $page ) {
      $page->start_controls_section(
        $this->GetId( 'tab-events' ),
        array(
          'label' => McwFullPageElementorGlobals::Translate( 'Events' ),
          'tab' => $this->tab,
          'condition' => array( $this->GetId( 'enabled' ) => 'yes' ),
        )
      );

      $events = array(
        'afterRender' => array(
          'enable' => 'after-render-enable',
          'field' => 'after-render',
          'description' => McwFullPageElementorGlobals::Translate( 'Fired just after the structure of the page is generated.' ),
        ),
        'afterResize' => array(
          'enable' => 'after-resize-enable',
          'field' => 'after-resize',
          'description' => McwFullPageElementorGlobals::Translate( 'Fired after resizing the browsers window.' ),
        ),
        'afterLoad' => array(
          'enable' => 'after-load-enable',
          'field' => 'after-load',
          'description' => McwFullPageElementorGlobals::Translate( 'Fired once the sections have been loaded, after the scrolling has ended.' ),
        ),
        'onLeave' => array(
          'enable' => 'on-leave-enable',
          'field' => 'on-leave',
          'description' => McwFullPageElementorGlobals::Translate( 'Fired once the user leaves a section.' ),
        ),
        'afterSlideLoad' => array(
          'enable' => 'after-slide-load-enable',
          'field' => 'after-slide-load',
          'description' => McwFullPageElementorGlobals::Translate( 'Fired once the slide of a section has been loaded, after the scrolling has ended.' ),
        ),
        'onSlideLeave' => array(
          'enable' => 'on-slide-leave-enable',
          'field' => 'on-slide-leave',
          'description' => McwFullPageElementorGlobals::Translate( 'Fired once the user leaves a slide to go another.' ),
        ),
        'afterResponsive' => array(
          'enable' => 'after-responsive-enable',
          'field' => 'after-responsive',
          'description' => McwFullPageElementorGlobals::Translate( 'Fired after normal mode is changed to responsive mode or responsive mode is changed to normal mode.' ),
        ),
        'afterReBuild' => array(
          'enable' => 'after-rebuild-enable',
          'field' => 'after-rebuild',
          'description' => McwFullPageElementorGlobals::Translate( 'Fired after manually re-building fullpage.js.' ),
        ),
        'Before FullPage' => array(
          'enable' => 'before-fullpage-enable',
          'field' => 'before-fullpage',
          'description' => McwFullPageElementorGlobals::Translate( 'The javascript code that runs right after document is ready and before fullpage is called.' ),
        ),
        'After FullPage' => array(
          'enable' => 'after-fullpage-enable',
          'field' => 'after-fullpage',
          'description' => McwFullPageElementorGlobals::Translate( 'The javascript code that runs right after document is ready and after fullpage is called.' ),
        ),
      );

      foreach ( $events as $name => $event ) {
        $page->add_control(
          $this->GetId( $event['enable'] ),
          array(
            'label' => McwFullPageElementorGlobals::Translate( $name ),
            'type' => ControlsManager::SWITCHER,
            'default' => 'no',
          )
        );

        $page->add_control(
          $this->GetId( $event['field'] ),
          array(
            'type' => ControlsManager::CODE,
            'language' => 'javascript',
            'seperator' => 'none',
            'show_label' => false,
            'condition' => array( $this->GetId( $event['enable'] ) => 'yes' ),
            'description' => $event['description'],
          )
        );
      }

      $page->end_controls_section();
    }

    private function AddExtensionsTab( $page ) {
      $page->start_controls_section(
        $this->GetId( 'tab-extensions' ),
        array(
          'label' => McwFullPageElementorGlobals::Translate( 'Extensions' ),
          'tab' => $this->tab,
          'condition' => array( $this->GetId( 'enabled' ) => 'yes' ),
        )
      );

      $page->add_control(
        $this->GetId( 'enable-extensions' ),
        array(
          'label' => McwFullPageElementorGlobals::Translate( 'Enable FullPage Extensions' ),
          'type' => ControlsManager::SWITCHER,
          'default' => 'no',
        )
      );

      $extensions = $this->GetExtensionsInfo();

      // Cards extension
      if ( false && $extensions['cards']['active'] ) {
        $page->add_control(
          $this->GetId( 'extension-cards' ),
          array(
            'label' => McwFullPageElementorGlobals::Translate( 'Cards' ),
            'type' => ControlsManager::SWITCHER,
            'default' => 'no',
            'separator' => 'before',
            'description' => McwFullPageElementorGlobals::Translate( 'Enables fullpage Cards extension.' ),
            'condition' => array(
              $this->GetId( 'enable-extensions' ) => 'yes',
            ),
          )
        );

        $page->add_control(
          $this->GetId( 'extension-cards-perspective' ),
          array(
            'label' => McwFullPageElementorGlobals::Translate( 'Perspective' ),
            'type' => ControlsManager::NUMBER,
            'min' => 0,
            'step' => 1,
            'default' => 100,
            'description' => McwFullPageElementorGlobals::Translate( 'Sets fullpage Cards extension perspective option.' ),
            'condition' => array(
              $this->GetId( 'enable-extensions' ) => 'yes',
              $this->GetId( 'extension-cards' ) => 'yes',
            ),
          )
        );

        $page->add_control(
          $this->GetId( 'extension-cards-fading-content' ),
          array(
            'label' => McwFullPageElementorGlobals::Translate( 'Fading Content' ),
            'type' => ControlsManager::SWITCHER,
            'default' => 'yes',
            'description' => McwFullPageElementorGlobals::Translate( 'Enables fullpage Cards extension fadingContent option.' ),
            'condition' => array(
              $this->GetId( 'enable-extensions' ) => 'yes',
              $this->GetId( 'extension-cards' ) => 'yes',
            ),
          )
        );

        $page->add_control(
          $this->GetId( 'extension-cards-fading-background' ),
          array(
            'label' => McwFullPageElementorGlobals::Translate( 'Fading Background' ),
            'type' => ControlsManager::SWITCHER,
            'default' => 'yes',
            'description' => McwFullPageElementorGlobals::Translate( 'Enables fullpage Cards extension fadingBackground option.' ),
            'condition' => array(
              $this->GetId( 'enable-extensions' ) => 'yes',
              $this->GetId( 'extension-cards' ) => 'yes',
            ),
          )
        );
      } elseif ( false ) {
        $page->add_control(
          $this->GetId( 'extension-cards-disabled' ),
          array(
            'label' => $this->GetNotInstalledExtensionHtml( McwFullPageElementorGlobals::Translate( 'Cards' ) ),
            'type' => ControlsManager::RAW_HTML,
            'separator' => 'before',
            'condition' => array(
              $this->GetId( 'enable-extensions' ) => 'yes',
            ),
          )
        );
      }

      // Continuous Horizontal extension
      if ( $extensions['continuous-horizontal']['active'] ) {
        $page->add_control(
          $this->GetId( 'extension-continuous-horizontal' ),
          array(
            'label' => McwFullPageElementorGlobals::Translate( 'Continuous Horizontal' ),
            'type' => ControlsManager::SWITCHER,
            'default' => 'no',
            'separator' => 'before',
            'description' => McwFullPageElementorGlobals::Translate( 'Enables fullpage Continuous Horizontal extension.' ),
            'condition' => array(
              $this->GetId( 'enable-extensions' ) => 'yes',
            ),
          )
        );
      } else {
        $page->add_control(
          $this->GetId( 'extension-continuous-horizontal-disabled' ),
          array(
            'label' => $this->GetNotInstalledExtensionHtml( McwFullPageElementorGlobals::Translate( 'Continuous Horizontal' ) ),
            'type' => ControlsManager::RAW_HTML,
            'separator' => 'before',
            'condition' => array(
              $this->GetId( 'enable-extensions' ) => 'yes',
            ),
          )
        );
      }

      // Drag And Move extension
      if ( $extensions['drag-and-move']['active'] ) {
        $page->add_control(
          $this->GetId( 'extension-drag-and-move' ),
          array(
            'label' => McwFullPageElementorGlobals::Translate( 'Drag And Move' ),
            'type' => ControlsManager::SWITCHER,
            'default' => 'no',
            'separator' => 'before',
            'description' => McwFullPageElementorGlobals::Translate( 'Enables fullpage Drag And Move extension.' ),
            'condition' => array(
              $this->GetId( 'enable-extensions' ) => 'yes',
            ),
          )
        );

        $page->add_control(
          $this->GetId( 'extension-drag-and-move-target' ),
          array(
            'label' => McwFullPageElementorGlobals::Translate( 'Drag And Move Target' ),
            'type' => ControlsManager::SELECT,
            'label_block' => true,
            'options' => array(
              'off' => McwFullPageElementorGlobals::Translate( 'Default' ),
              'vertical' => McwFullPageElementorGlobals::Translate( 'Vertical' ),
              'horizontal' => McwFullPageElementorGlobals::Translate( 'Horizontal' ),
              'fingersonly' => McwFullPageElementorGlobals::Translate( 'Fingers Only' ),
              'mouseonly' => McwFullPageElementorGlobals::Translate( 'Mouse Only' ),
            ),
            'default' => 'off',
            'description' => McwFullPageElementorGlobals::Translate( 'Defines Drag And Move extension target.' ),
            'condition' => array(
              $this->GetId( 'enable-extensions' ) => 'yes',
              $this->GetId( 'extension-drag-and-move' ) => 'yes',
            ),
          )
        );
      } else {
        $page->add_control(
          $this->GetId( 'extension-drag-and-move-disabled' ),
          array(
            'label' => $this->GetNotInstalledExtensionHtml( McwFullPageElementorGlobals::Translate( 'Drag And Move' ) ),
            'type' => ControlsManager::RAW_HTML,
            'separator' => 'before',
            'condition' => array(
              $this->GetId( 'enable-extensions' ) => 'yes',
            ),
          )
        );
      }

      // Drop Effect extension
      if ( $extensions['drop-effect']['active'] ) {
        $page->add_control(
          $this->GetId( 'extension-drop-effect' ),
          array(
            'label' => McwFullPageElementorGlobals::Translate( 'Drop Effect' ),
            'type' => ControlsManager::SWITCHER,
            'default' => 'no',
            'separator' => 'before',
            'description' => McwFullPageElementorGlobals::Translate( 'Enables fullpage Drop Effect extension.' ),
            'condition' => array(
              $this->GetId( 'enable-extensions' ) => 'yes',
            ),
          )
        );

        $page->add_control(
          $this->GetId( 'extension-drop-effect-target' ),
          array(
            'label' => McwFullPageElementorGlobals::Translate( 'Target' ),
            'type' => ControlsManager::SELECT,
            'label_block' => true,
            'options' => array(
              'off' => McwFullPageElementorGlobals::Translate( 'Default' ),
              'sections' => McwFullPageElementorGlobals::Translate( 'Sections' ),
              'slides' => McwFullPageElementorGlobals::Translate( 'Slides' ),
            ),
            'default' => 'off',
            'description' => McwFullPageElementorGlobals::Translate( 'Defines Drop Effect extension target.' ),
            'condition' => array(
              $this->GetId( 'enable-extensions' ) => 'yes',
              $this->GetId( 'extension-drop-effect' ) => 'yes',
            ),
          )
        );

        $page->add_control(
          $this->GetId( 'extension-drop-effect-speed' ),
          array(
            'label' => McwFullPageElementorGlobals::Translate( 'Speed' ),
            'type' => ControlsManager::NUMBER,
            'min' => 10,
            'max' => 10000,
            'step' => 10,
            'default' => 2300,
            'description' => McwFullPageElementorGlobals::Translate( 'Defines the speed in milliseconds for the drop animation effect since beginning to end.' ),
            'condition' => array(
              $this->GetId( 'enable-extensions' ) => 'yes',
              $this->GetId( 'extension-drop-effect' ) => 'yes',
            ),
          )
        );

        $page->add_control(
          $this->GetId( 'extension-drop-effect-color' ),
          array(
            'label' => McwFullPageElementorGlobals::Translate( 'Color' ),
            'type' => ControlsManager::COLOR,
            'alpha' => false,
            'default' => '#F82F4D',
            'description' => McwFullPageElementorGlobals::Translate( 'Defines color of the drop effect.' ),
            'condition' => array(
              $this->GetId( 'enable-extensions' ) => 'yes',
              $this->GetId( 'extension-drop-effect' ) => 'yes',
            ),
          )
        );

        $page->add_control(
          $this->GetId( 'extension-drop-effect-zindex' ),
          array(
            'label' => McwFullPageElementorGlobals::Translate( 'z-Index' ),
            'type' => ControlsManager::NUMBER,
            'min' => 99,
            'max' => 1000000,
            'step' => 1,
            'default' => 9999,
            'description' => McwFullPageElementorGlobals::Translate( 'Defines value assigned to the z-index property for the drop effect.' ),
            'condition' => array(
              $this->GetId( 'enable-extensions' ) => 'yes',
              $this->GetId( 'extension-drop-effect' ) => 'yes',
            ),
          )
        );
      } else {
        $page->add_control(
          $this->GetId( 'extension-drop-effect-disabled' ),
          array(
            'label' => $this->GetNotInstalledExtensionHtml( McwFullPageElementorGlobals::Translate( 'Drop Effect' ) ),
            'type' => ControlsManager::RAW_HTML,
            'separator' => 'before',
            'condition' => array(
              $this->GetId( 'enable-extensions' ) => 'yes',
            ),
          )
        );
      }

      // Fading Effect extension
      if ( $extensions['fading-effect']['active'] ) {
        $page->add_control(
          $this->GetId( 'extension-fading-effect' ),
          array(
            'label' => McwFullPageElementorGlobals::Translate( 'Fading Effect' ),
            'type' => ControlsManager::SWITCHER,
            'default' => 'no',
            'separator' => 'before',
            'description' => McwFullPageElementorGlobals::Translate( 'Enables fullpage Fading Effect extension.' ),
            'condition' => array(
              $this->GetId( 'enable-extensions' ) => 'yes',
            ),
          )
        );

        $page->add_control(
          $this->GetId( 'extension-fading-effect-target' ),
          array(
            'label' => McwFullPageElementorGlobals::Translate( 'Fading Effect Target' ),
            'type' => ControlsManager::SELECT,
            'label_block' => true,
            'options' => array(
              'off' => McwFullPageElementorGlobals::Translate( 'Default' ),
              'sections' => McwFullPageElementorGlobals::Translate( 'Sections' ),
              'slides' => McwFullPageElementorGlobals::Translate( 'Slides' ),
            ),
            'default' => 'off',
            'description' => McwFullPageElementorGlobals::Translate( 'Defines Fading Effect extension target.' ),
            'condition' => array(
              $this->GetId( 'enable-extensions' ) => 'yes',
              $this->GetId( 'extension-fading-effect' ) => 'yes',
            ),
          )
        );
      } else {
        $page->add_control(
          $this->GetId( 'extension-fading-effect-disabled' ),
          array(
            'label' => $this->GetNotInstalledExtensionHtml( McwFullPageElementorGlobals::Translate( 'Fading Effect' ) ),
            'type' => ControlsManager::RAW_HTML,
            'separator' => 'before',
            'condition' => array(
              $this->GetId( 'enable-extensions' ) => 'yes',
            ),
          )
        );
      }

      // Interlocked Slides extension
      if ( $extensions['interlocked-slides']['active'] ) {
        $page->add_control(
          $this->GetId( 'extension-interlocked-slides' ),
          array(
            'label' => McwFullPageElementorGlobals::Translate( 'Interlocked Slides' ),
            'type' => ControlsManager::SWITCHER,
            'default' => 'no',
            'separator' => 'before',
            'description' => McwFullPageElementorGlobals::Translate( 'Enables fullpage Interlocked Slides extension.' ),
            'condition' => array(
              $this->GetId( 'enable-extensions' ) => 'yes',
            ),
          )
        );
      } else {
        $page->add_control(
          $this->GetId( 'extension-interlocked-slides-disabled' ),
          array(
            'label' => $this->GetNotInstalledExtensionHtml( McwFullPageElementorGlobals::Translate( 'Interlocked Slides' ) ),
            'type' => ControlsManager::RAW_HTML,
            'separator' => 'before',
            'condition' => array(
              $this->GetId( 'enable-extensions' ) => 'yes',
            ),
          )
        );
      }

      // Offset Sections extension
      if ( $extensions['offset-sections']['active'] ) {
        $page->add_control(
          $this->GetId( 'extension-offset-sections' ),
          array(
            'label' => McwFullPageElementorGlobals::Translate( 'Offset Sections' ),
            'type' => ControlsManager::SWITCHER,
            'default' => 'no',
            'separator' => 'before',
            'description' => McwFullPageElementorGlobals::Translate( 'Enables fullpage Offset Sections extension.' ),
            'condition' => array(
              $this->GetId( 'enable-extensions' ) => 'yes',
            ),
          )
        );
      } else {
        $page->add_control(
          $this->GetId( 'extension-offset-sections-disabled' ),
          array(
            'label' => $this->GetNotInstalledExtensionHtml( McwFullPageElementorGlobals::Translate( 'Offset Sections' ) ),
            'type' => ControlsManager::RAW_HTML,
            'separator' => 'before',
            'condition' => array(
              $this->GetId( 'enable-extensions' ) => 'yes',
            ),
          )
        );
      }

      // Parallax extension
      if ( $extensions['parallax']['active'] ) {
        $page->add_control(
          $this->GetId( 'extension-parallax' ),
          array(
            'label' => McwFullPageElementorGlobals::Translate( 'Parallax' ),
            'type' => ControlsManager::SWITCHER,
            'default' => 'no',
            'separator' => 'before',
            'description' => McwFullPageElementorGlobals::Translate( 'Enables fullpage Parallax extension.' ),
            'condition' => array(
              $this->GetId( 'enable-extensions' ) => 'yes',
            ),
          )
        );

        $page->add_control(
          $this->GetId( 'extension-parallax-target' ),
          array(
            'label' => McwFullPageElementorGlobals::Translate( 'Parallax Target' ),
            'type' => ControlsManager::SELECT,
            'label_block' => true,
            'options' => array(
              'off' => McwFullPageElementorGlobals::Translate( 'Default' ),
              'sections' => McwFullPageElementorGlobals::Translate( 'Sections' ),
              'slides' => McwFullPageElementorGlobals::Translate( 'Slides' ),
            ),
            'default' => 'off',
            'description' => McwFullPageElementorGlobals::Translate( 'Defines Parallax extension target.' ),
            'condition' => array(
              $this->GetId( 'enable-extensions' ) => 'yes',
              $this->GetId( 'extension-parallax' ) => 'yes',
            ),
          )
        );

        $page->add_control(
          $this->GetId( 'extension-parallax-type' ),
          array(
            'label' => McwFullPageElementorGlobals::Translate( 'Type' ),
            'type' => ControlsManager::SELECT,
            'options' => array(
              'reveal' => McwFullPageElementorGlobals::Translate( 'Reveal' ),
              'cover' => McwFullPageElementorGlobals::Translate( 'Cover' ),
            ),
            'default' => 'reveal',
            'description' => McwFullPageElementorGlobals::Translate( 'Provides a way to choose if the current section will be above or below the destination one.' ),
            'condition' => array(
              $this->GetId( 'enable-extensions' ) => 'yes',
              $this->GetId( 'extension-parallax' ) => 'yes',
            ),
          )
        );

        $page->add_control(
          $this->GetId( 'extension-parallax-percentage' ),
          array(
            'label' => McwFullPageElementorGlobals::Translate( 'Percentage' ),
            'type' => ControlsManager::NUMBER,
            'min' => 0,
            'max' => 100,
            'step' => 1,
            'default' => 62,
            'description' => McwFullPageElementorGlobals::Translate( 'Provides a way to define the percentage of the parallax effect. Maximum value (100) will show completely static backgrounds.' ),
            'condition' => array(
              $this->GetId( 'enable-extensions' ) => 'yes',
              $this->GetId( 'extension-parallax' ) => 'yes',
            ),
          )
        );
      } else {
        $page->add_control(
          $this->GetId( 'extension-parallax-disabled' ),
          array(
            'label' => $this->GetNotInstalledExtensionHtml( McwFullPageElementorGlobals::Translate( 'Parallax' ) ),
            'type' => ControlsManager::RAW_HTML,
            'separator' => 'before',
            'condition' => array(
              $this->GetId( 'enable-extensions' ) => 'yes',
            ),
          )
        );
      }

      // Reset Sliders extension
      if ( $extensions['reset-sliders']['active'] ) {
        $page->add_control(
          $this->GetId( 'extension-reset-sliders' ),
          array(
            'label' => McwFullPageElementorGlobals::Translate( 'Reset Sliders' ),
            'type' => ControlsManager::SWITCHER,
            'default' => 'no',
            'separator' => 'before',
            'description' => McwFullPageElementorGlobals::Translate( 'Enables fullpage Reset Sliders extension.' ),
            'condition' => array(
              $this->GetId( 'enable-extensions' ) => 'yes',
            ),
          )
        );
      } else {
        $page->add_control(
          $this->GetId( 'extension-reset-sliders-disabled' ),
          array(
            'label' => $this->GetNotInstalledExtensionHtml( McwFullPageElementorGlobals::Translate( 'Reset Sliders' ) ),
            'type' => ControlsManager::RAW_HTML,
            'separator' => 'before',
            'condition' => array(
              $this->GetId( 'enable-extensions' ) => 'yes',
            ),
          )
        );
      }

      // Responsive Slides extension
      if ( $extensions['responsive-slides']['active'] ) {
        $page->add_control(
          $this->GetId( 'extension-responsive-slides' ),
          array(
            'label' => McwFullPageElementorGlobals::Translate( 'Responsive Slides' ),
            'type' => ControlsManager::SWITCHER,
            'default' => 'no',
            'separator' => 'before',
            'description' => McwFullPageElementorGlobals::Translate( 'Enables fullpage Responsive Slides extension.' ),
            'condition' => array(
              $this->GetId( 'enable-extensions' ) => 'yes',
            ),
          )
        );
      } else {
        $page->add_control(
          $this->GetId( 'extension-responsive-slides-disabled' ),
          array(
            'label' => $this->GetNotInstalledExtensionHtml( McwFullPageElementorGlobals::Translate( 'Responsive Slides' ) ),
            'type' => ControlsManager::RAW_HTML,
            'separator' => 'before',
            'condition' => array(
              $this->GetId( 'enable-extensions' ) => 'yes',
            ),
          )
        );
      }

      // Scroll Horizontally extension
      if ( $extensions['scroll-horizontally']['active'] ) {
        $page->add_control(
          $this->GetId( 'extension-scroll-horizontally' ),
          array(
            'label' => McwFullPageElementorGlobals::Translate( 'Scroll Horizontally' ),
            'type' => ControlsManager::SWITCHER,
            'default' => 'no',
            'separator' => 'before',
            'description' => McwFullPageElementorGlobals::Translate( 'Enables fullpage Scroll Horizontally extension.' ),
            'condition' => array(
              $this->GetId( 'enable-extensions' ) => 'yes',
            ),
          )
        );
      } else {
        $page->add_control(
          $this->GetId( 'extension-scroll-horizontally-disabled' ),
          array(
            'label' => $this->GetNotInstalledExtensionHtml( McwFullPageElementorGlobals::Translate( 'Scroll Horizontally' ) ),
            'type' => ControlsManager::RAW_HTML,
            'separator' => 'before',
            'condition' => array(
              $this->GetId( 'enable-extensions' ) => 'yes',
            ),
          )
        );
      }

      // Scroll Overflow Reset extension
      if ( $extensions['scroll-overflow-reset']['active'] ) {
        $page->add_control(
          $this->GetId( 'extension-scroll-overflow-reset' ),
          array(
            'label' => McwFullPageElementorGlobals::Translate( 'Scroll Overflow Reset' ),
            'type' => ControlsManager::SWITCHER,
            'default' => 'no',
            'separator' => 'before',
            'description' => McwFullPageElementorGlobals::Translate( 'Enables fullpage Scroll Overflow Reset extension.' ),
            'condition' => array(
              $this->GetId( 'enable-extensions' ) => 'yes',
            ),
          )
        );
      } else {
        $page->add_control(
          $this->GetId( 'extension-scroll-overflow-reset-disabled' ),
          array(
            'label' => $this->GetNotInstalledExtensionHtml( McwFullPageElementorGlobals::Translate( 'Scroll Overflow Reset' ) ),
            'type' => ControlsManager::RAW_HTML,
            'separator' => 'before',
            'condition' => array(
              $this->GetId( 'enable-extensions' ) => 'yes',
            ),
          )
        );
      }

      do_action( $this->tag . '-extension-controls' );

      $page->end_controls_section();
    }

    private function AddCustomizationsTab( $page ) {
      $page->start_controls_section(
        $this->GetId( 'tab-customizations' ),
        array(
          'label' => McwFullPageElementorGlobals::Translate( 'Customizations' ),
          'tab' => $this->tab,
          'condition' => array( $this->GetId( 'enabled' ) => 'yes' ),
        )
      );

      $page->add_control(
        $this->GetId( 'extra-parameters' ),
        array(
          'label' => McwFullPageElementorGlobals::Translate( 'Extra Parameters' ),
          'type' => ControlsManager::TEXT,
          'default' => '',
          'description' => McwFullPageElementorGlobals::Translate( 'If there are parameters you want to include, add these parameters (comma seperated). Example: parameter1:true, parameter2:15' ),
          'seperator' => 'after',
        )
      );

      $page->add_control(
        $this->GetId( 'video-autoplay' ),
        array(
          'label' => McwFullPageElementorGlobals::Translate( 'Video Autoplay' ),
          'type' => ControlsManager::SWITCHER,
          'default' => 'no',
          'description' => McwFullPageElementorGlobals::Translate( 'Enables playing the videos (HTML5 and Youtube) only when the section is in view and stops it otherwise.' ),
        )
      );

      $page->add_control(
        $this->GetId( 'video-keepplaying' ),
        array(
          'label' => McwFullPageElementorGlobals::Translate( 'Video Keep Playing' ),
          'type' => ControlsManager::SWITCHER,
          'default' => 'no',
          'description' => McwFullPageElementorGlobals::Translate( 'The videos keep playing even after section is changed.' ),
          'condition' => array(
            $this->GetId( 'video-autoplay' ) => 'yes',
          ),
        )
      );

      $page->add_control(
        $this->GetId( 'remove-theme-margins' ),
        array(
          'label' => McwFullPageElementorGlobals::Translate( 'Remove Theme Margins' ),
          'type' => ControlsManager::SWITCHER,
          'default' => 'no',
          'description' => McwFullPageElementorGlobals::Translate( 'Force to remove theme wrapper margins and paddings.' ),
        )
      );

      $page->add_control(
        $this->GetId( 'fixed-theme-header' ),
        array(
          'label' => McwFullPageElementorGlobals::Translate( 'Force Fixed Theme Header' ),
          'type' => ControlsManager::SWITCHER,
          'default' => 'no',
          'description' => McwFullPageElementorGlobals::Translate( 'Force to make theme header fixed on top.' ),
        )
      );

      $page->add_control(
        $this->GetId( 'fixed-theme-header-selector' ),
        array(
          'label' => McwFullPageElementorGlobals::Translate( 'Theme Header Selector' ),
          'type' => ControlsManager::TEXT,
          'default' => 'header',
          'description' => McwFullPageElementorGlobals::Translate( 'Theme header CSS selector. (Example: .header)' ),
          'condition' => array(
            $this->GetId( 'fixed-theme-header' ) => 'yes',
          ),
        )
      );

      $page->add_control(
        $this->GetId( 'fixed-theme-header-section' ),
        array(
          'label' => McwFullPageElementorGlobals::Translate( 'Is Header a Section?' ),
          'type' => ControlsManager::SWITCHER,
          'default' => 'no',
          'description' => McwFullPageElementorGlobals::Translate( 'Enable this option if you design the header using a section or the header is inside a section.' ),
          'condition' => array(
            $this->GetId( 'fixed-theme-header' ) => 'yes',
          ),
        )
      );

      $page->add_control(
        $this->GetId( 'fixed-theme-header-padding' ),
        array(
          'label' => McwFullPageElementorGlobals::Translate( 'Theme Header Padding' ),
          'type' => ControlsManager::SWITCHER,
          'default' => 'no',
          'description' => McwFullPageElementorGlobals::Translate( 'Add theme header height to sections as padding.' ),
          'condition' => array(
            $this->GetId( 'fixed-theme-header' ) => 'yes',
          ),
        )
      );

      $page->add_control(
        $this->GetId( 'move-footer' ),
        array(
          'label' => McwFullPageElementorGlobals::Translate( 'Show Theme Footer' ),
          'type' => ControlsManager::SWITCHER,
          'default' => 'no',
          'description' => McwFullPageElementorGlobals::Translate( 'Try to move the theme footer inside the last section.' ),
        )
      );

      $page->add_control(
        $this->GetId( 'move-footer-selector' ),
        array(
          'label' => McwFullPageElementorGlobals::Translate( 'Theme Footer Selector' ),
          'type' => ControlsManager::TEXT,
          'default' => 'footer',
          'description' => McwFullPageElementorGlobals::Translate( 'Theme footer CSS selector. (Example: .footer)' ),
          'condition' => array(
            $this->GetId( 'move-footer' ) => 'yes',
          ),
        )
      );

      $page->add_control(
        $this->GetId( 'elementor-animations' ),
        array(
          'label' => McwFullPageElementorGlobals::Translate( 'Enable Elementor Animations' ),
          'type' => ControlsManager::SWITCHER,
          'default' => 'no',
          'description' => McwFullPageElementorGlobals::Translate( 'Enable elementor animations.' ),
          'condition' => array(
            $this->GetId( 'scroll-bar' ) . '!' => 'yes',
          ),
        )
      );

      $page->add_control(
        $this->GetId( 'elementor-animations-reset' ),
        array(
          'label' => McwFullPageElementorGlobals::Translate( 'Reset Elementor Animations' ),
          'type' => ControlsManager::SWITCHER,
          'default' => 'no',
          'description' => McwFullPageElementorGlobals::Translate( 'Reset elementor animations on section/slide change.' ),
          'condition' => array(
            $this->GetId( 'scroll-bar' ) . '!' => 'yes',
            $this->GetId( 'elementor-animations' ) => 'yes',
          ),
        )
      );

      $page->add_control(
        $this->GetId( 'form-buttons-custom' ),
        array(
          'label' => McwFullPageElementorGlobals::Translate( 'Form Buttons' ),
          'type' => ControlsManager::SWITCHER,
          'default' => 'no',
          'description' => McwFullPageElementorGlobals::Translate( 'Apply a fix if the forms inside sections do not work.' ),
        )
      );

      $page->add_control(
        $this->GetId( 'hide-content-before-load' ),
        array(
          'label' => McwFullPageElementorGlobals::Translate( 'Hide Content Before FullPage' ),
          'type' => ControlsManager::SWITCHER,
          'default' => 'no',
          'description' => McwFullPageElementorGlobals::Translate( 'Hide content before FullPage load and show content when the page is loaded.' ),
        )
      );

      $page->end_controls_section();
    }

    private function AddAdvancedTab( $page ) {
      $page->start_controls_section(
        $this->GetId( 'tab-advanced' ),
        array(
          'label' => McwFullPageElementorGlobals::Translate( 'Advanced' ),
          'tab' => $this->tab,
          'condition' => array( $this->GetId( 'enabled' ) => 'yes' ),
        )
      );

      $page->add_control(
        $this->GetId( 'section-selector' ),
        array(
          'label' => McwFullPageElementorGlobals::Translate( 'Section Selector' ),
          'type' => ControlsManager::TEXT,
          'placeholder' => '.mcw-fp-section',
          'default' => '',
        )
      );

      $page->add_control(
        $this->GetId( 'slide-selector' ),
        array(
          'label' => McwFullPageElementorGlobals::Translate( 'Slide Selector' ),
          'type' => ControlsManager::TEXT,
          'placeholder' => '.mcw-fp-section-slide .mcw-fp-slide',
          'default' => '',
        )
      );

      $page->add_control(
        $this->GetId( 'jquery' ),
        array(
          'label' => McwFullPageElementorGlobals::Translate( 'Enable JQuery Dependency' ),
          'type' => ControlsManager::SWITCHER,
          'default' => 'no',
          'description' => McwFullPageElementorGlobals::Translate( 'Enables JQuery dependency.' ),
        )
      );

      $page->add_control(
        $this->GetId( 'enable-template' ),
        array(
          'label' => McwFullPageElementorGlobals::Translate( 'Enable Empty Page Template' ),
          'type' => ControlsManager::SWITCHER,
          'default' => 'yes',
          'description' => McwFullPageElementorGlobals::Translate( 'Defines if page will be redirected to the defined template. The template is independent from the theme and is an empty page template if not defined.' ),
        )
      );

      $page->add_control(
        $this->GetId( 'enable-template-redirect' ),
        array(
          'label' => McwFullPageElementorGlobals::Translate( 'Use Template Redirect' ),
          'type' => ControlsManager::SWITCHER,
          'default' => 'no',
          'description' => McwFullPageElementorGlobals::Translate( 'Defines if template will be redirected or included. If set, template will be redirected, otherwise template will be included. Play with this setting to see the best scenario that fits.' ),
          'condition' => array(
            $this->GetId( 'enable-template' ) => 'yes',
          ),
        )
      );

      $page->add_control(
        $this->GetId( 'template-path' ),
        array(
          'label' => McwFullPageElementorGlobals::Translate( 'Template Path' ),
          'type' => ControlsManager::TEXT,
          'default' => '',
          'description' => McwFullPageElementorGlobals::Translate( 'If you want to use your own template, put the template path and template name here. If left empty, an empty predefined page template will be used.' ),
          'condition' => array(
            $this->GetId( 'enable-template' ) => 'yes',
          ),
        )
      );

      $page->add_control(
        $this->GetId( 'remove-theme-js' ),
        array(
          'label' => McwFullPageElementorGlobals::Translate( 'Remove Theme JS' ),
          'type' => ControlsManager::SWITCHER,
          'default' => 'no',
          'description' => McwFullPageElementorGlobals::Translate( 'Remove theme javascript from output. Be aware, this might crash the page output if the theme has JS output on the head section.' ),
        )
      );

      $page->add_control(
        $this->GetId( 'remove-js' ),
        array(
          'label' => McwFullPageElementorGlobals::Translate( 'Remove JS' ),
          'type' => ControlsManager::TEXT,
          'default' => '',
          'description' => McwFullPageElementorGlobals::Translate( 'Remove specified javascript from output. Be aware, this might crash the page output. Write javascript names with comma in between.' ),
        )
      );

      $page->end_controls_section();
    }

    private function ImplodeParams( $parameters, $extras = '' ) {
      $paramStr = '';
      foreach ( $parameters as $key => $value ) {
        if ( isset( $value ) && ! empty( $value ) ) {
          if ( is_array( $value ) && isset( $value['raw'] ) ) {
            $paramStr .= $key . ':' . $value['raw'] . ',';
          } elseif ( 'false' === $value || 'true' === $value || is_numeric( $value ) ) {
            $paramStr .= $key . ':' . $value . ',';
          } else {
            $paramStr .= $key . ':"' . $value . '",';
          }
        }
      }
      $paramStr .= $extras;
      return '{' . rtrim( $paramStr, ',' ) . '}';
    }

    private function GetFullPageCustomizationScripts() {
      return array(

        // removeThemeMargins
        'removeThemeMargins' => 'function getParentsUntil(elem, parent, selector){
          if (!Element.prototype.matches) {
            Element.prototype.matches = Element.prototype.matchesSelector || Element.prototype.mozMatchesSelector || Element.prototype.msMatchesSelector || Element.prototype.oMatchesSelector || Element.prototype.webkitMatchesSelector || function(s) {
              var matches = (this.document || this.ownerDocument).querySelectorAll(s);
              var i = matches.length;
              while(--i >= 0 && matches.item(i) !== this) {}
              return i > -1;
            };
          }

          var parents = [];
          for (;elem && elem !== document; elem = elem.parentNode) {
            if (parent) {
              if (elem.matches(parent)) break;
            }

            if (selector) {
              if (elem.matches(selector)) {
                parents.push(elem);
              }
              break;
            }
            parents.push(elem);
          }
          return parents;
        }
        Array.prototype.forEach.call(getParentsUntil( document.querySelector(".' . $this->wrapper . '"), "body" ), function(el, i){
          if (el.classList) el.classList.add("mcw_fp_nomargin");
          else el.className += " mcw_fp_nomargin";
        });',

        // fixedThemeHeader
        'fixedThemeHeader' => 'function outerHeight(el) {
          if (!el) return 0;
          var height = el.offsetHeight;
          var style = getComputedStyle(el);
          return height + parseInt(style.marginTop) + parseInt(style.marginBottom);
        }
        function headerPadding(header, selector){
          var el = document.querySelectorAll(header);
          if (el.length == 0) return;
          var height = outerHeight(el[0]);

          [].slice.call( document.querySelectorAll(selector) ).forEach(function(eli){
            eli.style.paddingTop = height + "px";
          });

          [].slice.call( document.querySelectorAll(".fp-controlArrow") ).forEach(function(eli){
            eli.style.marginTop = ((height - outerHeight(eli)) / 2) + "px";
          });

          [].slice.call( document.querySelectorAll("#fp-nav") ).forEach(function(eli){
            eli.style.paddingTop = (height / 2) + "px";
          });

          fullpage_api.reBuild();

          return height;
        }',

        // moveThemeHeader
        'moveThemeHeader' => 'function moveArr() {
            var h = document.querySelectorAll(".mcw-fp-wrapper %s");
            var p = document.querySelector(".mcw-fp-wrapper .elementor");
            for (var i = h.length - 1; i >= 0; i--) {
                p.insertBefore(h[i], p.firstChild);
                h[i].classList.remove("mcw-fp-section");
            }
        }
        moveArr();
        document.querySelector(".mcw-fp-wrapper .elementor-inner").classList.add("mcw-fp-wrapper");
        document.querySelector(".mcw-fp-wrapper").classList.remove("mcw-fp-wrapper");',

        // moveFooter
        'moveFooter' => 'var f = document.querySelector("%s");
        if (f) {
          var d = document.createElement("div");
          d.classList.add("mcw-fp-section", "fp-footer-section", "fp-auto-height");'
          . ( $this->IsFieldEnabled( 'disable-anchors' ) ? '' : 'd.setAttribute("data-anchor", "footer");' ) .
          ' d.appendChild(f);
          document.querySelector(".mcw-fp-section").parentElement.appendChild(d);
        }',

        // videoAutoplay
        'videoAutoplay' => 'function videoAutoplay(element,keep){
            element = element ? element : document;
            var elements = element.querySelectorAll(\'video,audio,iframe[src*="youtube.com/embed/"]\');
            Array.prototype.forEach.call(elements, function(el, i){
              if (!el.hasAttribute("data-autoplay")) el.setAttribute("data-autoplay", "true");
              if (keep&&!el.hasAttribute("data-keepplaying")) el.setAttribute("data-keepplaying", "true");
            });
          };',

        // elementorAnimate
        'elementorAnimate' => 'function elementorGetParsed(element) {
          var parsed = {};
          var settings = element.getAttribute("data-settings");
          if ( /_?animation/.test( settings ) ) {
            try {
              parsed = JSON.parse(settings);
            }catch(e){};
          }

          return parsed;
        }

        function elementorGetAnimation(element, parsed) {
          if (!parsed) {
            parsed = elementorGetParsed(element);
          }

          return elementorFrontend.getCurrentDeviceSetting(parsed, "animation") || elementorFrontend.getCurrentDeviceSetting(parsed, "_animation");
        }

        function elementorAnimate( element ) {
          if (element && !element.classList.contains("animated")) {
            var parsed = elementorGetParsed(element);
            var animation = elementorGetAnimation(element, parsed);
            if ( !animation || "none" === animation ) {
              element.classList.remove("elementor-invisible");
              return;
            }

            var animationDelay = parsed._animation_delay || parsed.animation_delay || 0;
            setTimeout( function(){
              element.classList.remove("elementor-invisible");
              element.classList.add("animated");
              element.classList.add(animation);
            }, animationDelay );
          }
        }

        function elementorAnimateInner(element) {
          var inner = element.querySelectorAll("[data-settings]");
          for (var i = 0; i < inner.length; ++i) {
            elementorAnimate(inner[i]);
          }
        }

        function elementorAnimateAfterLoad(destination) {
          if (destination && destination.item) {
            elementorAnimate(destination.item);
            if (destination.item.querySelectorAll(".fp-slides").length === 0) {
              elementorAnimateInner(destination.item);
            } else {
              elementorAnimate(destination.item.querySelector(".fp-slides .fp-slide.active"));
              elementorAnimateInner(destination.item.querySelector(".fp-slides .fp-slide.active"));
            }
          }
        }

        function elementorAnimateAfterSlideLoad(destination) {
          elementorAnimate(destination.item);
          elementorAnimateInner(destination.item);
        }',

        // elementorAnimateReset
        'elementorAnimateReset' => 'function elementorAnimateReset(element) {
          if (element && element.classList.contains("animated")) {
            var animation = elementorGetAnimation(element);
            if ( animation && "none" !== animation ) {
              element.classList.remove(animation);
            }
            element.classList.remove("animated");
            element.classList.add("elementor-invisible")
          }
        }

        function elementorAnimateResetInner(element) {
          var inner = element.querySelectorAll("[data-settings]");
          for (var i = 0; i < inner.length; ++i) {
            elementorAnimateReset(inner[i]);
          }
        }

        function elementorAnimateResetAfterLoad(origin, destination) {
          if (origin && destination && origin.index !== destination.index) {
            elementorAnimateReset(origin.item);
            if (origin.item.querySelectorAll(".fp-slides").length === 0) {
              elementorAnimateResetInner(origin.item);
            } else {
              elementorAnimateReset(origin.item.querySelector(".fp-slides .fp-slide.active"));
              elementorAnimateResetInner(origin.item.querySelector(".fp-slides .fp-slide.active"));
            }
          }
        }

        function elementorAnimateOnSlideLeave(origin) {
          elementorAnimateReset(origin.item);
          elementorAnimateResetInner(origin.item);
        }',

        // overlayFix
        'overlayFix' => 'function overlayFix() {
          Array.prototype.forEach.call([".elementor-shape", ".elementor-background-overlay", ".elementor-background-video-container", ".elementor-background-slideshow"], function(item){
            var overlays = document.querySelectorAll(".fp-tableCell>" + item + ", .fp-scroller>" + item);
            for (var i = overlays.length - 1; i >= 0; i--) {
              var p = overlays[i];
              do {
                p = p.parentElement;
                if (p.classList.contains("fp-section") || p.classList.contains("fp-slide")) {
                  p.insertBefore(overlays[i], p.firstChild);
                  break;
                }
              }while(p && p.nodeType === 1);
            }
          });
        }',

        'clickTooltip' => 'function clickTooltip(){
          Array.prototype.forEach.call(document.querySelectorAll("#fp-nav ul li .fp-tooltip"), function(t, i){
            t.addEventListener("click", function(e) {
              if (event.target && event.target.parentElement && event.target.parentElement.tagName == "LI") {
                event.target.parentElement.querySelector("a").dispatchEvent(new MouseEvent("click", {
                  bubbles: true,
                  cancelable: true,
                  view: window
                }));
              }
            });
          });
        }'

      );
    }

    private function GetFullPageJS( $content ) {
      $customizationScripts = $this->GetFullPageCustomizationScripts();

      // FullPage parameters
      $parameters = array();
      $parameters['licenseKey'] = $this->pluginSettings->GetLicenseKey();

      $selector = $this->GetFieldValue( 'section-selector', '' );
      $parameters['sectionSelector'] = empty( $selector ) ? '.mcw-fp-section' : $selector;

      $selector = $this->GetFieldValue( 'slide-selector', '' );
      $parameters['slideSelector'] = empty( $selector ) ? '.mcw-fp-section-slide .mcw-fp-slide' : $selector;

      // Customizations
      {
        $extras = $this->GetFieldValue( 'extra-parameters', '' );

        $customizations = array(
          'before' => '',
          'after' => 'window.fullpage_api.wordpress={name:"elementor",version:"' . McwFullPageElementorGlobals::Version() . '"};',
          'afterRender' => '',
          'afterResize' => '',
          'afterLoad' => '',
          'onLeave' => '',
          'afterSlideLoad' => '',
          'onSlideLeave' => '',
        );

        if ( $this->IsFieldEnabled( 'fixed-theme-header' ) && $this->IsFieldEnabled( 'fixed-theme-header-section' ) ) {
          $selector = $this->GetFieldValue( 'fixed-theme-header-selector', 'header' );
          $customizations['before'] .= sprintf( $customizationScripts['moveThemeHeader'], $selector );
        }

        if ( $this->IsFieldEnabled( 'fixed-theme-header' ) && $this->IsFieldEnabled( 'fixed-theme-header-padding' ) ) {
          $selector = $this->GetFieldValue( 'fixed-theme-header-selector', 'header' );
          $headerPaddingScriptCall = sprintf( 'headerPadding("%s","%s")', $selector, '.mcw-fp-section:not(.mcw-fp-section-slide)' ) . ';';
          $headerPaddingScriptCall .= sprintf( 'headerPadding("%s","%s")', $selector, '.mcw-fp-section.mcw-fp-section-slide .mcw-fp-slide' ) . ';';
          $customizations['afterRender'] .= $headerPaddingScriptCall;
          $customizations['afterResize'] .= $headerPaddingScriptCall;

          $customizations['before'] .= sprintf( $customizationScripts['fixedThemeHeader'], $selector );
        }

        if ( $this->IsFieldEnabled( 'move-footer' ) ) {
          $selector = $this->GetFieldValue( 'move-footer-selector', 'footer' );
          $customizations['before'] .= sprintf( $customizationScripts['moveFooter'], $selector );
        }

        if ( $this->IsFieldEnabled( 'video-autoplay' ) || preg_match( '/data-settings="[^"].*[\b|:|&quot;]([^&]*youtube\.com.*?)[\b|:|&quot;][^"]"/', $content ) ) {
          $keep = $this->IsFieldEnabled( 'video-autoplay' ) && $this->IsFieldEnabled( 'video-keepplaying' );
          $customizations['before'] .= $customizationScripts['videoAutoplay'];
          $customizations['afterRender'] .= 'videoAutoplay(undefined,' . ( $keep ? 'true' : 'false' ) . ');';
          $customizations['afterLoad'] .= 'videoAutoplay(undefined,' . ( $keep ? 'true' : 'false' ) . ');';
        }

        if ( preg_match( '/class="[^"]*\b(elementor-background-overlay|elementor-shape)\b[^"]*"/', $content ) || preg_match( '/data-settings="[^"]*[\b|:|&quot;](background_slideshow_gallery|background_slideshow_loop|background_video_link)[\b|:|&quot;][^"]*"/', $content ) ) {
          $customizations['before'] .= $customizationScripts['overlayFix'];
          $customizations['afterRender'] .= 'overlayFix();';
        }

        if ( $this->IsFieldEnabled( 'remove-theme-margins' ) ) {
          $customizations['before'] .= $customizationScripts['removeThemeMargins'];
        }

        if ( ! $this->IsFieldEnabled( 'scroll-bar' ) ) {
          if ( $this->IsFieldEnabled( 'elementor-animations' ) || preg_match( '/data-settings="[^"]*[\b|:|&quot;](animation|_animation|animation_mobile)[\b|:|&quot;][^"]*"/', $content ) ) {
            $customizations['before'] .= $customizationScripts['elementorAnimate'];
            $customizations['afterLoad'] .= 'elementorAnimateAfterLoad(destination);';
            $customizations['afterSlideLoad'] .= 'elementorAnimateAfterSlideLoad(destination);';

            if ( $this->IsFieldEnabled( 'elementor-animations' ) && $this->IsFieldEnabled( 'elementor-animations-reset' ) ) {
              $customizations['before'] .= $customizationScripts['elementorAnimateReset'];
              $customizations['afterLoad'] .= 'elementorAnimateResetAfterLoad(origin, destination);';
              $customizations['onSlideLeave'] .= 'elementorAnimateOnSlideLeave(origin);';
            }
          }
        }

        if ( $this->IsFieldEnabled( 'control-arrows' ) && $this->IsFieldEnabled( 'group-section-control-arrows-type', 'control-arrows-options' ) && $this->GetFieldValue( 'arrow-style', 'off', 'control-arrows-options' ) === 'modern' ) {
          $color = $this->GetFieldValue( 'arrow-color-main', '#FFFFFF', 'control-arrows-options' );
          $customizations['afterRender'] .= 'var prev = document.querySelectorAll(".fp-controlArrow.fp-prev");
          for (var i = 0; i < prev.length; ++i) {
            prev[i].innerHTML = \'<svg width="60px" height="80px" viewBox="0 0 50 80" xml:space="preserve"><polyline fill="none" stroke="' . $color . '" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" points="45.63,75.8 0.375,38.087 45.63,0.375 "></polyline></svg>\';
          }
          var next = document.querySelectorAll(".fp-controlArrow.fp-next");
          for (var i = 0; i < next.length; ++i) {
            next[i].innerHTML = \'<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="60px" height="80px" viewBox="0 0 50 80" xml:space="preserve"><polyline fill="none" stroke="' . $color . '" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" points="0.375,0.375 45.63,38.087 0.375,75.8 "></polyline></svg>\';
          }';
        }
      }

      // Navigation parameters
      {
        $nav = $this->GetFieldValue( 'nav', 'off', 'section-navigation' );
        $parameters['navigation'] = ( 'off' === $nav ) ? 'false' : 'true';
        if ( 'off' !== $nav ) {
          $parameters['navigationPosition'] = ( 'right' === $nav ) ? 'right' : 'left';
          $parameters['showActiveTooltip'] = $this->IsFieldOn( 'show-active-tooltip', 'section-navigation' );
        }

        $nav = $this->GetFieldValue( 'nav', 'off', 'slide-navigation' );
        $parameters['slidesNavigation'] = ( 'off' === $nav ) ? 'false' : 'true';
        if ( 'off' !== $nav ) {
          // navigationPosition
          $parameters['slidesNavPosition'] = ( 'top' === $nav ) ? 'top' : 'bottom';
        }

        // controlArrows
        $parameters['controlArrows'] = $this->IsFieldOn( 'control-arrows' );
        // lockAnchors
        $parameters['lockAnchors'] = $this->IsFieldOn( 'lock-anchors' );
        // animateAnchor
        $parameters['animateAnchor'] = $this->IsFieldOn( 'animate-anchor' );
        // keyboardScrolling
        $parameters['keyboardScrolling'] = $this->IsFieldOn( 'keyboard-scrolling' );
        // recordHistory
        $parameters['recordHistory'] = $this->IsFieldOn( 'record-history' );

        if ( 'off' !== $nav && $this->IsFieldEnabled( 'click-tooltip', 'section-navigation' ) ) {
          $customizations['before'] .= sprintf( $customizationScripts['clickTooltip'], $selector );
          $customizations['afterRender'] .= 'clickTooltip();';
        }
      }

      // Scrolling parameters
      {
        // autoScrolling
        $parameters['autoScrolling'] = $this->IsFieldOn( 'auto-scrolling' );
        // fitToSection
        $parameters['fitToSection'] = $this->IsFieldOn( 'fit-to-section' );
        // fitToSectionDelay
        $parameters['fitToSectionDelay'] = $this->GetFieldValue( 'fit-to-section-delay', '1000' );
        // scrollBar
        $parameters['scrollBar'] = $this->IsFieldOn( 'scroll-bar' );

        if ( $this->IsFieldEnabled( 'scroll-bar' ) ) {
          $parameters['scrollOverflow'] = 'false';
        } else {
          // scrollOverflow
          $parameters['scrollOverflow'] = $this->IsFieldOn( 'scroll-overflow' );
          if ( $this->IsFieldEnabled( 'scroll-overflow' ) ) {
            // scrollOverflowOptions
            $parameters['scrollOverflowOptions'] = array(
              'raw' => $this->ImplodeParams(
                array(
                  'scrollbars' => $this->IsFieldOn( 'scrollbars', 'scroll-overflow-options' ),
                  'fadeScrollbars' => $this->IsFieldOn( 'fade', 'scroll-overflow-options' ),
                  'interactiveScrollbars' => $this->IsFieldOn( 'interactive', 'scroll-overflow-options' ),
                ) + ( $this->IsFieldEnabled( 'form-buttons-custom' ) ? array( 'click' => 'false' ) : array() )
              ),
            );
          }
        }

        // bigSectionsDestination
        $bigSectionsDestination = $this->GetFieldValue( 'big-sections-destination', 'default' );
        $parameters['bigSectionsDestination'] = ( 'default' !== $bigSectionsDestination ) ? $bigSectionsDestination : '';

        // continuousVertical, loopBottom, loopTop
        if ( ! $this->IsFieldEnabled( 'scroll-bar' ) && $this->IsFieldEnabled( 'auto-scrolling' ) && $this->IsFieldEnabled( 'continuous-vertical' ) ) {
          $parameters['continuousVertical'] = 'true';
          $parameters['loopBottom'] = 'false';
          $parameters['loopTop'] = 'false';
        } else {
          $parameters['continuousVertical'] = 'false';
          $parameters['loopBottom'] = $this->IsFieldOn( 'loop-bottom' );
          $parameters['loopTop'] = $this->IsFieldOn( 'loop-top' );
        }

        // loopHorizontal
        $parameters['loopHorizontal'] = $this->IsFieldOn( 'loop-slides' );
        // scrollingSpeed
        $parameters['scrollingSpeed'] = $this->GetFieldValue( 'scrolling-speed', '1000' );

        // css3, easingcss3, easing
        $easing = $this->GetFieldValue( 'easing', 'css3_ease' );
        if ( substr( $easing, 0, 5 ) === 'css3_' ) {
          $easing = substr( $easing, 5, strlen( $easing ) );
          $parameters['css3'] = 'true';
          $parameters['easingcss3'] = $easing;
        } else {
          $easing = substr( $easing, 3, strlen( $easing ) );
          $parameters['css3'] = 'false';
          $parameters['easing'] = $easing;
        }
      }

      // Design parameters
      {
        // verticalCentered
        $parameters['verticalCentered'] = $this->GetFieldValue( 'vertical-alignment', 'center' ) === 'top' ? 'false' : 'true';
        // responsiveWidth
        $parameters['responsiveWidth'] = $this->GetFieldValue( 'responsive-width', '0' );
        // responsiveHeight
        $parameters['responsiveHeight'] = $this->GetFieldValue( 'responsive-height', '0' );
        // paddingTop
        $parameters['paddingTop'] = array( 'raw' => '(typeof mcwPaddingTop!=="undefined"&&mcwPaddingTop)?mcwPaddingTop+"px":"0px"' );
        // paddingBottom
        $parameters['paddingBottom'] = '0px';
        // fixedElements
        $parameters['fixedElements'] = $this->GetFieldValue( 'fixed-elements', '' );
        // normalScrollElements
        $parameters['normalScrollElements'] = $this->GetFieldValue( 'normal-scroll-elements', '' );

        // Add $parameters['lazyLoading']
      }

      // Extension parameters
      if ( $this->IsFieldEnabled( 'enable-extensions' ) ) {
        $extensions = $this->GetExtensionsInfo();
        $extensionKeys = apply_filters( 'mcw-fullpage-extension-key', array() );

        foreach ( $extensions as $key => $value ) {
          $extensions[ $key ]['key'] = array_key_exists( $key, $extensionKeys ) ? $extensionKeys[ $key ] : '';
        }

        // Cards extension
        if ( false && $extensions['cards']['active'] && $this->IsFieldEnabled( 'extension-cards' ) ) {
          $parameters['cards'] = $this->IsFieldOn( 'extension-cards' );
          $parameters['cardsKey'] = $this->GetExtensionKey( $extensions, 'cards' );
          $parameters['cardsOptions'] = array(
            'raw' => $this->ImplodeParams(
              array(
                'perspective' => $this->GetFieldValue( 'extension-cards-perspective' ),
                'fadingContent' => $this->GetFieldValue( 'extension-cards-fading-content' ),
                'fadingBackground' => $this->GetFieldValue( 'extension-cards-fading-background' ),
              )
            ),
          );
        }

        // Continuous Horizontal extension
        if ( $extensions['continuous-horizontal']['active'] && $this->IsFieldEnabled( 'extension-continuous-horizontal' ) ) {
          $parameters['continuousHorizontal'] = $this->IsFieldOn( 'extension-continuous-horizontal' );
          $parameters['continuousHorizontalKey'] = $this->GetExtensionKey( $extensions, 'continuous-horizontal' );
        }

        // Drag And Move extension
        if ( $extensions['drag-and-move']['active'] && $this->IsFieldEnabled( 'extension-drag-and-move' ) ) {
          $dragAndMove = $this->GetFieldValue( 'extension-drag-and-move-target', 'off' );
          $parameters['dragAndMove'] = $dragAndMove === 'off' ? 'true' : $dragAndMove;
          $parameters['dragAndMoveKey'] = $this->GetExtensionKey( $extensions, 'drag-and-move' );
        }

        // Drop Effect extension
        if ( $extensions['drop-effect']['active'] && $this->IsFieldEnabled( 'extension-drop-effect' ) ) {
          $dropEffect = $this->GetFieldValue( 'extension-drop-effect-target', 'off' );
          $parameters['dropEffect'] = $dropEffect === 'off' ? 'true' : $dropEffect;
          $parameters['dropEffectKey'] = $this->GetExtensionKey( $extensions, 'drop-effect' );
          $parameters['dropEffectOptions'] = array(
            'raw' => $this->ImplodeParams(
              array(
                'speed' => $this->GetFieldValue( 'extension-drop-effect-speed' ),
                'color' => $this->GetFieldValue( 'extension-drop-effect-color' ),
                'zIndex' => $this->GetFieldValue( 'extension-drop-effect-zindex' ),
              )
            ),
          );
        }

        // Fading Effect extension
        if ( $extensions['fading-effect']['active'] && $this->IsFieldEnabled( 'extension-fading-effect' ) ) {
          $fadingEffect = $this->GetFieldValue( 'extension-fading-effect-target', 'off' );
          $parameters['fadingEffect'] = $fadingEffect === 'off' ? 'true' : $fadingEffect;
          $parameters['fadingEffectKey'] = $this->GetExtensionKey( $extensions, 'fading-effect' );
        }

        // Interlocked Slides extension
        if ( $extensions['interlocked-slides']['active'] && $this->IsFieldEnabled( 'extension-interlocked-slides' ) ) {
          $parameters['interlockedSlides'] = $this->IsFieldOn( 'extension-interlocked-slides' );
          $parameters['interlockedSlidesKey'] = $this->GetExtensionKey( $extensions, 'interlocked-slides' );
        }

        // Offset Sections extension
        if ( $extensions['offset-sections']['active'] && $this->IsFieldEnabled( 'extension-offset-sections' ) ) {
          $parameters['offsetSections'] = $this->IsFieldOn( 'extension-offset-sections' );
          $parameters['offsetSectionsKey'] = $this->GetExtensionKey( $extensions, 'offset-sections' );
        }

        // Parallax extension
        if ( $extensions['parallax']['active'] && $this->IsFieldEnabled( 'extension-parallax' ) ) {
          $parallax = $this->GetFieldValue( 'extension-parallax-target', 'off' );
          $parameters['parallax'] = $parallax === 'off' ? 'true' : $parallax;
          $parameters['parallaxKey'] = $this->GetExtensionKey( $extensions, 'parallax' );
          $parameters['parallaxOptions'] = array(
            'raw' => $this->ImplodeParams(
              array(
                'type' => $this->GetFieldValue( 'extension-parallax-type' ),
                'percentage' => $this->GetFieldValue( 'extension-parallax-percentage' ),
                'property' => 'translate',
              )
            ),
          );
        }

        // Reset Sliders extension
        if ( $extensions['reset-sliders']['active'] && $this->IsFieldEnabled( 'extension-reset-sliders' ) ) {
          $parameters['resetSliders'] = $this->IsFieldOn( 'extension-reset-sliders' );
          $parameters['resetSlidersKey'] = $this->GetExtensionKey( $extensions, 'reset-sliders' );
        }

        // Responsive Slides extension
        if ( $extensions['responsive-slides']['active'] && $this->IsFieldEnabled( 'extension-responsive-slides' ) ) {
          $parameters['responsiveSlides'] = $this->IsFieldOn( 'extension-responsive-slides' );
          $parameters['responsiveSlidesKey'] = $this->GetExtensionKey( $extensions, 'responsive-slides' );
        }

        // Scroll Horizontally extension
        if ( $extensions['scroll-horizontally']['active'] && $this->IsFieldEnabled( 'extension-scroll-horizontally' ) ) {
          $parameters['scrollHorizontally'] = $this->IsFieldOn( 'extension-scroll-horizontally' );
          $parameters['scrollHorizontallyKey'] = $this->GetExtensionKey( $extensions, 'scroll-horizontally' );
        }

        // Scroll Overflow Reset extension
        if ( $extensions['scroll-overflow-reset']['active'] && $this->IsFieldEnabled( 'extension-scroll-overflow-reset' ) ) {
          $parameters['scrollOverflowReset'] = $this->IsFieldOn( 'extension-scroll-overflow-reset' );
          $parameters['scrollOverflowResetKey'] = $this->GetExtensionKey( $extensions, 'scroll-overflow-reset' );
        }
      }

      // Events
      {
        $events = array(
          'afterRender' => array(
            'enable' => 'after-render-enable',
            'field' => 'after-render',
            'fn' => 'function(){%s}',
          ),
          'afterResize' => array(
            'enable' => 'after-resize-enable',
            'field' => 'after-resize',
            'fn' => 'function(width,height){%s}',
          ),
          'afterLoad' => array(
            'enable' => 'after-load-enable',
            'field' => 'after-load',
            'fn' => 'function(origin,destination,direction){%s}',
          ),
          'onLeave' => array(
            'enable' => 'on-leave-enable',
            'field' => 'on-leave',
            'fn' => 'function(origin,destination,direction){%s}',
          ),
          'afterSlideLoad' => array(
            'enable' => 'after-slide-load-enable',
            'field' => 'after-slide-load',
            'fn' => 'function(section,origin,destination,direction){%s}',
          ),
          'onSlideLeave' => array(
            'enable' => 'on-slide-leave-enable',
            'field' => 'on-slide-leave',
            'fn' => 'function(section,origin,destination,direction){%s}',
          ),
          'afterResponsive' => array(
            'enable' => 'after-responsive-enable',
            'field' => 'after-responsive',
            'fn' => 'function(isResponsive){%s}',
          ),
          'afterReBuild' => array(
            'enable' => 'after-rebuild-enable',
            'field' => 'after-rebuild',
            'fn' => 'function(){%s}',
          ),
        );

        foreach ( $events as $name => $event ) {
          $script = '';
          if ( $this->IsFieldEnabled( $event['enable'] ) ) {
            $script .= $this->MinimizeJavascriptAdvanced( $this->GetFieldValue( $event['field'], '' ) );
          }

          if ( isset( $customizations[ $name ] ) && ! empty( $customizations[ $name ] ) ) {
            $script .= $customizations[ $name ];
          }

          if ( ! empty( $script ) ) {
            $parameters[ $name ] = array( 'raw' => sprintf( $event['fn'], $script ) );
          }
        }

        // beforeFullPage event
        $beforeFullPage = $this->IsFieldEnabled( 'before-fullpage-enable' ) ? $this->GetFieldValue( 'before-fullpage', '' ) : '';
        // afterFullPage event
        $afterFullPage = $this->IsFieldEnabled( 'after-fullpage-enable' ) ? $this->GetFieldValue( 'after-fullpage', '' ) : '';
      }

      $script = $this->IsFieldEnabled( 'jquery' ) ?
        '(function runFullPage($){(function ready(fn){if (document.attachEvent?document.readyState==="complete":document.readyState!=="loading"){fn();}else{document.addEventListener("DOMContentLoaded",fn);}})(function(){%s%s%s});})(jQuery);' :
        '(function runFullPage(){(function ready(fn){if (document.attachEvent?document.readyState==="complete":document.readyState!=="loading"){fn();}else{document.addEventListener("DOMContentLoaded",fn);}})(function(){%s%s%s});})();';

      return $this->MinimizeJavascriptSimple(
        sprintf(
          $script,
          $customizations['before'] . $beforeFullPage,
          'new fullpage(".' . $this->wrapper . '",' . $this->ImplodeParams( $parameters, $extras ) . ');',
          $customizations['after'] . $afterFullPage
        )
      );
    }
  }

}
