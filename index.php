<?php
/**
* Plugin Name: WPE Google Sign-In
* Description: Connect to the dashboard easily with Google Sign-In
* Text Domain: WpeGoogleSignIn
* Version: 1.1.0
* Author: Paul Balanche
**/

namespace WpeGoogleSignIn;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

error_reporting(E_ALL | E_STRICT);

/**
 * Define constants
 *
 */
define( __NAMESPACE__ . '\PLUGIN_NAME', 		'Google Sign-In' );
define( __NAMESPACE__ . '\PLUGIN_DIR_PATH',     plugin_dir_path( __FILE__ ) );
define( __NAMESPACE__ . '\PLUGIN_URL',          plugins_url('', __FILE__) . '/' );
define( __NAMESPACE__ . '\PLUGIN_ASSETS_URL' ,  PLUGIN_URL . 'assets' . '/' );
define( __NAMESPACE__ . '\PLUGIN_TEXTDOMAIN' ,  __NAMESPACE__ );

/**
 * Initialize plugin
 * 
 */
add_action( 'plugins_loaded', __NAMESPACE__ . '\_plugin_init' );
function _plugin_init() {

	// Load text domain
	load_plugin_textdomain( PLUGIN_TEXTDOMAIN, false, basename( dirname( __FILE__ ) ) . '/languages' );
	
	// Functions
	// require( PLUGIN_DIR_PATH . '/inc/functions/basic-functions.php' );

	// Plugin vendor autoloader
	require( PLUGIN_DIR_PATH . 'vendor/autoload.php' );

	// Load instances
	Options::getInstance();
	if( Options::getInstance()->get_json_client_secret() )
		SignIn::getInstance();
}