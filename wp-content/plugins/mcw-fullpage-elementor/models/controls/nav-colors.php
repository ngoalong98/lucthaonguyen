<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class McwFullPageElementorNavColorsControl extends \Elementor\Group_Control_Base {
	// Fields (derived var)
	protected static $fields;

	public function __construct( $fields ) {
		self::$fields = $fields;
	}

	public static function get_type() {
		return McwFullPageElementorGlobals::Tag() . '-group-nav-colors';
	}

	protected function init_fields() {
		return self::$fields;
	}

	protected function get_default_options() {
		return array(
			'popover' => array(
				'starter_title' => McwFullPageElementorGlobals::Translate( 'Navigation Colors' ),
				'starter_name' => McwFullPageElementorGlobals::Tag() . '-group-section-nav-colors',
				'starter_value' => 'yes',
			),
		);
	}
}