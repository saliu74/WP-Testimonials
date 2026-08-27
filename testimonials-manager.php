<?php
/**
 * Plugin Name:       Testimonials Manager
 * Plugin URI:        https://github.com/saliu74/testimonials-manager
 * Description:       Manage, import, and display customer testimonials with shortcodes for carousel, grid, and list layouts. Import from CSV or XLSX with column mapping and duplicate detection.
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Muideen Saliu
 * Author URI:        https://github.com/saliu74/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       testimonials-manager
 * Domain Path:       /languages
 *
 * @package Testimonials_Manager
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Core plugin constants.
 */
define( 'TM_VERSION', '1.0.0' );
define( 'TM_PLUGIN_FILE', __FILE__ );
define( 'TM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'TM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'TM_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'TM_MIN_PHP', '7.4' );
define( 'TM_MIN_WP', '5.8' );

/**
 * PHP version guard. Bail out gracefully on unsupported hosts instead of
 * throwing a fatal parse/runtime error on shared hosting with old PHP.
 */
if ( version_compare( PHP_VERSION, TM_MIN_PHP, '<' ) ) {
	add_action(
		'admin_notices',
		function () {
			echo '<div class="notice notice-error"><p>';
			printf(
				/* translators: 1: required PHP version, 2: current PHP version */
				esc_html__( 'Testimonials Manager requires PHP %1$s or higher. Your server is running PHP %2$s. The plugin has been disabled.', 'testimonials-manager' ),
				esc_html( TM_MIN_PHP ),
				esc_html( PHP_VERSION )
			);
			echo '</p></div>';
		}
	);
	return;
}

/**
 * Autoload plugin classes on demand (no Composer dependency required).
 *
 * Class file naming follows WordPress conventions:
 * TM_Some_Class -> class-tm-some-class.php located under includes/ or admin/.
 */
spl_autoload_register(
	function ( $class_name ) {
		if ( strpos( $class_name, 'TM_' ) !== 0 ) {
			return;
		}

		$file_name = 'class-' . strtolower( str_replace( '_', '-', $class_name ) ) . '.php';

		$locations = array(
			TM_PLUGIN_DIR . 'includes/',
			TM_PLUGIN_DIR . 'includes/importer/',
			TM_PLUGIN_DIR . 'admin/',
		);

		foreach ( $locations as $location ) {
			$path = $location . $file_name;
			if ( file_exists( $path ) ) {
				require_once $path;
				return;
			}
		}
	}
);

/**
 * Returns the single instance of the core plugin class, instantiating it
 * on first call.
 *
 * @return TM_Plugin
 */
function tm_testimonials_manager() {
	return TM_Plugin::instance();
}

// Boot the plugin once all plugins are loaded so we can safely detect
// third-party integrations (Elementor, page builders, etc.) if ever needed.
add_action(
	'plugins_loaded',
	function () {
		tm_testimonials_manager();
	}
);

/**
 * Activation hook: register CPT/taxonomy so rewrite rules are available to
 * flush, then flush rewrite rules and seed default settings.
 */
register_activation_hook( __FILE__, array( 'TM_Plugin', 'activate' ) );

/**
 * Deactivation hook: flush rewrite rules only. Never delete user content.
 */
register_deactivation_hook( __FILE__, array( 'TM_Plugin', 'deactivate' ) );
