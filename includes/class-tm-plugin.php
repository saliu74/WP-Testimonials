<?php
/**
 * Core plugin bootstrap.
 *
 * @package Testimonials_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class TM_Plugin
 *
 * Singleton responsible for wiring up every plugin module. Kept intentionally
 * thin: each concern (CPT, taxonomy, meta boxes, shortcodes, settings,
 * importer, admin UI, assets) lives in its own class so the codebase stays
 * easy to navigate and extend.
 */
final class TM_Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var TM_Plugin|null
	 */
	private static $instance = null;

	/**
	 * @var TM_Post_Type
	 */
	public $post_type;

	/**
	 * @var TM_Taxonomy
	 */
	public $taxonomy;

	/**
	 * @var TM_Meta_Boxes
	 */
	public $meta_boxes;

	/**
	 * @var TM_Shortcodes
	 */
	public $shortcodes;

	/**
	 * @var TM_Settings
	 */
	public $settings;

	/**
	 * @var TM_Assets
	 */
	public $assets;

	/**
	 * @var TM_Importer
	 */
	public $importer;

	/**
	 * @var TM_Admin
	 */
	public $admin;

	/**
	 * @var TM_Demo_Data
	 */
	public $demo_data;

	/**
	 * @var TM_Block
	 */
	public $block;

	/**
	 * Retrieve (and lazily create) the singleton instance.
	 *
	 * @return TM_Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor. Private — use instance().
	 */
	private function __construct() {
		$this->includes();
		$this->init_hooks();
	}

	/**
	 * Load helper files that are not autoloaded classes (e.g. functional
	 * template helpers used inside templates/*.php).
	 */
	private function includes() {
		require_once TM_PLUGIN_DIR . 'includes/tm-template-functions.php';
	}

	/**
	 * Wire up modules. Every module registers its own WordPress hooks in
	 * its constructor, keeping this method a simple manifest.
	 */
	private function init_hooks() {
		add_action( 'init', array( $this, 'load_textdomain' ) );

		$this->settings   = new TM_Settings();
		$this->post_type  = new TM_Post_Type();
		$this->taxonomy   = new TM_Taxonomy();
		$this->meta_boxes = new TM_Meta_Boxes();
		$this->shortcodes = new TM_Shortcodes();
		$this->assets     = new TM_Assets();
		$this->importer   = new TM_Importer();
		$this->demo_data  = new TM_Demo_Data();

		if ( is_admin() ) {
			$this->admin = new TM_Admin();
		}

		if ( function_exists( 'register_block_type' ) ) {
			$this->block = new TM_Block();
		}
	}

	/**
	 * Load plugin translations.
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'testimonials-manager', false, dirname( TM_PLUGIN_BASENAME ) . '/languages' );
	}

	/**
	 * Activation callback: register CPT/taxonomy (so rewrite rules exist to
	 * flush), seed default settings, then flush rewrite rules once.
	 */
	public static function activate() {
		// Ensure classes are available even though autoload only registers
		// on plugin load — activation can run before plugins_loaded.
		if ( ! class_exists( 'TM_Post_Type' ) ) {
			require_once TM_PLUGIN_DIR . 'includes/class-tm-post-type.php';
		}
		if ( ! class_exists( 'TM_Taxonomy' ) ) {
			require_once TM_PLUGIN_DIR . 'includes/class-tm-taxonomy.php';
		}
		if ( ! class_exists( 'TM_Settings' ) ) {
			require_once TM_PLUGIN_DIR . 'includes/class-tm-settings.php';
		}

		$post_type = new TM_Post_Type();
		$post_type->register_post_type();

		$taxonomy = new TM_Taxonomy();
		$taxonomy->register_taxonomy();

		$settings = new TM_Settings();
		$settings->maybe_seed_defaults();

		flush_rewrite_rules();

		update_option( 'tm_testimonials_db_version', TM_VERSION );
	}

	/**
	 * Deactivation callback. Testimonials, categories, and settings are
	 * intentionally preserved — only rewrite rules are flushed.
	 */
	public static function deactivate() {
		flush_rewrite_rules();
	}
}
