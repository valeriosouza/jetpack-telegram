<?php

if( !function_exists('add_action') ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

if( did_action('jetpack_modules_loaded') ) {
	jetelegram_Sharing_Service::init();
} else {
	add_action( 'jetpack_modules_loaded', array( 'jetelegram_Sharing_Service', 'init' ) );
}

class jetelegram_Sharing_Service {
	static $instance;

	static function init() {
		if( !Jetpack::is_module_active('sharedaddy') ) {
			return false;
		}

		if( !self::$instance ) {
			self::$instance = new jetelegram_Sharing_Service;
		}

		return self::$instance;
	}

	function __construct() {
		add_filter( 'sharing_services', array( &$this, 'add_sharing_services' ) );
	}

	function add_sharing_services( $services ) {
		include_once jetelegram__PLUGIN_DIR . 'class.telegram-source.php';

		if( !array_key_exists( 'telegram', $services ) ) {
			$services['telegram'] = 'jetelegram_Share_Telegram';
		}

		return $services;
	}
}
