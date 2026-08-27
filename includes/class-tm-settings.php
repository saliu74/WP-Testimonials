<?php
/**
 * Plugin settings storage and defaults.
 *
 * @package Testimonials_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class TM_Settings
 *
 * All plugin-wide defaults live in a single option so shortcode attributes
 * only need to override what's different for a given placement.
 */
class TM_Settings {

	const OPTION_KEY = 'tm_testimonials_settings';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Default option values.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			// General.
			'default_layout'        => 'grid',
			'default_limit'         => 6,
			'default_orderby'       => 'date',
			'default_order'         => 'DESC',
			'default_category'      => '',
			'show_ratings'          => 1,
			'show_images'           => 1,

			// Grid.
			'grid_columns'          => 3,
			'grid_pagination'       => 1,

			// Carousel.
			'carousel_autoplay'     => 1,
			'carousel_interval'     => 5000,
			'carousel_arrows'      => 1,
			'carousel_dots'        => 1,
			'carousel_slides_desktop' => 3,
			'carousel_slides_tablet'  => 2,
			'carousel_slides_mobile'  => 1,

			// Appearance.
			'card_radius'           => 12,
			'image_size'            => 64,
			'content_alignment'     => 'left',

			// Import.
			'import_default_status'   => 'publish',
			'import_default_featured' => 'spreadsheet',

			// Data lifecycle.
			'delete_data_on_uninstall' => 0,
		);
	}

	/**
	 * Get the merged (saved + default) settings.
	 *
	 * @return array
	 */
	public static function get() {
		$saved = get_option( self::OPTION_KEY, array() );
		return wp_parse_args( $saved, self::defaults() );
	}

	/**
	 * Get a single setting value.
	 *
	 * @param string $key     Setting key.
	 * @param mixed  $fallback Fallback if not set.
	 * @return mixed
	 */
	public static function get_value( $key, $fallback = null ) {
		$settings = self::get();
		return isset( $settings[ $key ] ) ? $settings[ $key ] : $fallback;
	}

	/**
	 * Seed default settings on activation if the option does not yet exist.
	 */
	public function maybe_seed_defaults() {
		if ( false === get_option( self::OPTION_KEY, false ) ) {
			add_option( self::OPTION_KEY, self::defaults() );
		}
	}

	/**
	 * Register the settings option with a sanitize callback. The settings
	 * page itself is rendered by TM_Admin; this only wires up the storage.
	 */
	public function register_settings() {
		register_setting(
			'tm_testimonials_settings_group',
			self::OPTION_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize' ),
				'default'           => self::defaults(),
			)
		);
	}

	/**
	 * Sanitize the full settings array on save.
	 *
	 * @param array $input Raw posted values.
	 * @return array
	 */
	public function sanitize( $input ) {
		$defaults = self::defaults();
		$clean    = array();

		if ( ! is_array( $input ) ) {
			$input = array();
		}

		$clean['default_layout']   = in_array( $input['default_layout'] ?? '', array( 'grid', 'carousel', 'list' ), true ) ? $input['default_layout'] : $defaults['default_layout'];
		$clean['default_limit']    = max( 1, (int) ( $input['default_limit'] ?? $defaults['default_limit'] ) );
		$clean['default_orderby']  = in_array( $input['default_orderby'] ?? '', array( 'date', 'title', 'rating', 'menu_order', 'rand' ), true ) ? $input['default_orderby'] : $defaults['default_orderby'];
		$clean['default_order']    = 'ASC' === strtoupper( $input['default_order'] ?? '' ) ? 'ASC' : 'DESC';
		$clean['default_category'] = isset( $input['default_category'] ) ? sanitize_text_field( $input['default_category'] ) : '';
		$clean['show_ratings']     = empty( $input['show_ratings'] ) ? 0 : 1;
		$clean['show_images']      = empty( $input['show_images'] ) ? 0 : 1;

		$clean['grid_columns']    = max( 1, min( 6, (int) ( $input['grid_columns'] ?? $defaults['grid_columns'] ) ) );
		$clean['grid_pagination'] = empty( $input['grid_pagination'] ) ? 0 : 1;

		$clean['carousel_autoplay']       = empty( $input['carousel_autoplay'] ) ? 0 : 1;
		$clean['carousel_interval']       = max( 1000, (int) ( $input['carousel_interval'] ?? $defaults['carousel_interval'] ) );
		$clean['carousel_arrows']         = empty( $input['carousel_arrows'] ) ? 0 : 1;
		$clean['carousel_dots']           = empty( $input['carousel_dots'] ) ? 0 : 1;
		$clean['carousel_slides_desktop'] = max( 1, min( 6, (int) ( $input['carousel_slides_desktop'] ?? $defaults['carousel_slides_desktop'] ) ) );
		$clean['carousel_slides_tablet']  = max( 1, min( 4, (int) ( $input['carousel_slides_tablet'] ?? $defaults['carousel_slides_tablet'] ) ) );
		$clean['carousel_slides_mobile']  = max( 1, min( 2, (int) ( $input['carousel_slides_mobile'] ?? $defaults['carousel_slides_mobile'] ) ) );

		$clean['card_radius']       = max( 0, min( 40, (int) ( $input['card_radius'] ?? $defaults['card_radius'] ) ) );
		$clean['image_size']        = max( 24, min( 200, (int) ( $input['image_size'] ?? $defaults['image_size'] ) ) );
		$clean['content_alignment'] = in_array( $input['content_alignment'] ?? '', array( 'left', 'center' ), true ) ? $input['content_alignment'] : $defaults['content_alignment'];

		$clean['import_default_status']   = in_array( $input['import_default_status'] ?? '', array( 'publish', 'draft', 'pending' ), true ) ? $input['import_default_status'] : $defaults['import_default_status'];
		$clean['import_default_featured'] = in_array( $input['import_default_featured'] ?? '', array( 'all', 'none', 'spreadsheet' ), true ) ? $input['import_default_featured'] : $defaults['import_default_featured'];

		$clean['delete_data_on_uninstall'] = empty( $input['delete_data_on_uninstall'] ) ? 0 : 1;

		return $clean;
	}
}
