<?php
/*
 * Plugin Name: Telegram Sharing Button for Jetpack
 * Plugin URI: http://valeriosouza.com.br/portfolio/telegram-sharing-button-for-jetpack/
 * Description: Add Telegram button to Jetpack Sharing
 * Version: 1.1.3
 * Author: Valerio Souza, WordLab Academy
 * Author URI: http://www.valeriosouza.com.br
 * License: GPLv3 or later
 * Text Domain: jetpack-telegram
 * Domain Path: /languages/
 * GitHub Branch: beta
 * GitHub Plugin URI: https://github.com/valeriosouza/jetpack-telegram
*/

if( !function_exists('add_action') ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

if( version_compare( get_bloginfo('version'), '3.8', '<' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	deactivate_plugins( __FILE__ );
}

add_action( 'admin_init', 'jt_check_dependencies' );

//Check if jetpack is active.
function jt_check_dependencies() {
	if ( ! is_plugin_active( 'jetpack/jetpack.php' ) ) {
		add_action( 'admin_notices', 'jt_dependencies_notice' );
		deactivate_plugins( __FILE__ );

		//I used it to not appear "plugin active" message! We can discuss a better way to do this.
		unset( $_GET['activate'] );
	}
}

//Show error notice if jetpack is NOT active.
function jt_dependencies_notice() {
    ?>
    <div class="error">
        <p><strong><?php _e( 'Jetpack has NOT been activated! You need to install and activate the Jetpack plugin to work sharing with Telegram.', 'jetpack-telegram' ); ?></strong></p>
    </div>
    <?php
}

define( 'jetelegram__PLUGIN_DIR',  plugin_dir_path( __FILE__ ) );
define( 'jetelegram__PLUGIN_URL',  plugin_dir_url( __FILE__ ) );
define( 'jetelegram__PLUGIN_FILE', __FILE__ );
define( 'jetelegram__VERSION',     '1.1.3' );

add_action( 'init', array( 'Jetpack_Telegram_Pack', 'init' ) );

class Jetpack_Telegram_Pack {
	static $instance;

	private $data;

	static function init() {
		if( !self::$instance ) {
			if( did_action('plugins_loaded') ) {
				self::plugin_textdomain();
			} else {
				add_action( 'plugins_loaded', array( __CLASS__, 'plugin_textdomain' ) );
			}

			self::$instance = new Jetpack_Telegram_Pack;
		}
		return self::$instance;
	}

	private function __construct() {

		global $current_user ;
	    $user_id = $current_user->ID;

		add_action( 'wp_enqueue_scripts',    	array( &$this, 'register_assets' ) );
		add_action( 'admin_enqueue_scripts', 	array( &$this, 'admin_menu_assets' ) );
		if ( ! get_user_meta($user_id, 'telegram_ignore_notice') ) {
			add_action( 'admin_notices', 		 array( &$this, 'plugin_donate_notice' ) );
			add_action( 'admin_notices', 		 array( &$this, 'plugin_faq_notice' ) );
		}
		register_deactivation_hook( __FILE__,	array( &$this, 'update' ));

		if( did_action('plugins_loaded') ) {
			$this->require_services();
		} else {
			add_action( 'plugins_loaded', array( &$this, 'require_services' ) );
		}
		add_filter( 'plugin_row_meta', array( &$this, 'plugin_row_donate' ), 10, 2 );
		add_filter( 'plugin_row_meta', array( &$this, 'plugin_row_more' ), 10, 2 );
	}

	static function update() {
		global $current_user;
	    $user_id = $current_user->ID;
		delete_user_meta($user_id, 'telegram_ignore_notice');
	}

	static function remove_notice() {
		global $current_user;
	    $user_id = $current_user->ID;
	    if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) { 
			add_user_meta($user_id, 'telegram_ignore_notice', '1');
		}
		die();
	}

	function register_assets() {
		if( get_option('sharedaddy_disable_resources') ) {
			return;
		}

		if( !Jetpack::is_module_active('sharedaddy') ) {
			return;
		}
		wp_enqueue_script( 'jetpack-telegram', jetelegram__PLUGIN_URL . 'assets/js/count.js', array('jquery','sharing-js'), jetelegram__VERSION, true );
		wp_enqueue_style( 'jetpack-telegram', jetelegram__PLUGIN_URL . 'assets/css/style.css', array(), jetelegram__VERSION );
	}

	function admin_menu_assets( $hook ) {
		if( $hook == 'settings_page_sharing' ) {
			wp_enqueue_style( 'jetpack-telegram', jetwhats__PLUGIN_URL . 'assets/css/style.css', array('sharing', 'sharing-admin'), jetwhats__VERSION );
			wp_enqueue_script( 'jetpack-telegram', jetwhats__PLUGIN_URL . 'assets/js/main.js', array('jquery','sharing-js'), jetwhats__VERSION, true );
			wp_localize_script( 'jetpack-telegram', 'remove_notice', array(
				'ajax_url' => admin_url( 'admin-ajax.php' )
			));
		}
	}

	function require_services() {
		if( class_exists('Jetpack') ) {
			require_once( jetelegram__PLUGIN_DIR . 'includes/class.telegram-service.php' );
		}
	}

	static function plugin_textdomain() {
		//load_plugin_textdomain( 'jetpack-telegram', false, dirname( plugin_basename( jetelegram__PLUGIN_FILE ) ) . '/languages/' );
		$locale = get_locale();

		load_plugin_textdomain( 'jetpack-telegram', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	function plugin_row_donate( $links, $file ) {
		if( plugin_basename( jetelegram__PLUGIN_FILE ) === $file ) {
			$links[] = sprintf(
				'<a href="%s" style="color:#ffa100;font-weight:bold;">%s</a>',
				esc_url('http://wordlab.com.br/donate/?utm_source=plugin&utm_medium=donate&utm_campaign=jetpack-telegram'),
				__( 'Donate', 'jetpack-telegram' )
			);
		}
		return $links;
	}
	function plugin_row_more( $links, $file ) {
		if( plugin_basename( jetelegram__PLUGIN_FILE ) === $file ) {
			$links[] = sprintf(
				'<a target="_blank" href="%s" style="color:#ffa100;font-weight:bold;">%s</a>',
				esc_url('http://valeriosouza.com.br/project-tag/jetpack-addons/?utm_source=plugin&utm_medium=more-addons&utm_campaign=jetpack-telegram'),
				__( 'More add-ons', 'jetpack-telegram' )
			);
		}
		return $links;
	}

	/* Display a notice that can be dismissed */

	function plugin_donate_notice() {
	        echo '<div class="notice notice-info notice-donate is-dismissible"><p>';
	        printf('%s<a target="_blank" href="%s">%s</a>.',__('Like the plugin Telegram Sharing Button for Jetpack? Develop free plugins takes work! Be my boss and make a ', 'jetpack-telegram'), 'http://wordlab.com.br/donate/?utm_source=plugin&utm_medium=donate-notice&utm_campaign=jetpack-telegram', __('donation of any amount', 'jetpack-telegram'));
	        echo "</p></div>";
	}

	function plugin_faq_notice() {
	        echo '<div class="notice notice-info notice-faq is-dismissible"><p>';
	        printf('%s<a target="_blank" href="%s">%s</a>.',__('Button does not appear on the front end? ', 'jetpack-telegram'), 'https://wordpress.org/plugins/telegram-sharing-button-for-jetpack/faq/', __('Please, read the FAQ', 'jetpack-telegram'));
	        echo "</p></div>";
	}
}
