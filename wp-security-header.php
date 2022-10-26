<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://wwdh.de
 * @since             1.0.0
 * @package           Wp_Security_Header
 *
 * @wordpress-plugin
 * Plugin Name:       WP Security Header
 * Plugin URI:        https://www.hummelt-werbeagentur.de/
 * Description:       Create header, content security policy (CSP) and permissions policy. Automatically create nonce for script-src.
 * Version:           1.0.0
 * Author:            Jens Wiecker
 * Author URI:        https://wwdh.de
 * License:           MIT License
 * Text Domain:       wp-security-header
 * Domain Path:       /languages
 * Requires PHP:      7.4
 * Requires at least: 5.4
 * Tested up to:      6.0.3
 * Stable tag:        1.0.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Plugin Database-Version.
 */
const WP_SECURITY_HEADER_DB_VERSION = '1.0.0';
/**
 * PHP minimum requirement for the plugin.
 */
const WP_SECURITY_HEADER_MIN_PHP_VERSION = '7.4';
/**
 * WordPress minimum requirement for the plugin.
 */
const WP_SECURITY_HEADER_MIN_WP_VERSION = '5.6';
/**
 * PLUGIN SLUG.
 */
define('WP_SECURITY_HEADER_SLUG_PATH', plugin_basename(__FILE__));
/**
 * PLUGIN Basename.
 */
define('WP_SECURITY_HEADER_BASENAME', plugin_basename(__DIR__));
/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-wp-security-header-activator.php
 */
function activate_wp_security_header() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wp-security-header-activator.php';
	Wp_Security_Header_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-wp-security-header-deactivator.php
 */
function deactivate_wp_security_header() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wp-security-header-deactivator.php';
	Wp_Security_Header_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_wp_security_header' );
register_deactivation_hook( __FILE__, 'deactivate_wp_security_header' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-wp-security-header.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_wp_security_header() {
	$plugin = new Wp_Security_Header();
	$plugin->run();
}
run_wp_security_header();
