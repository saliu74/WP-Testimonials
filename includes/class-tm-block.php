<?php
/**
 * A simple Gutenberg block wrapping the [testimonials] shortcode.
 *
 * Shortcode support remains the primary, mandatory API — this block is a
 * thin convenience layer for Gutenberg users and simply builds the
 * equivalent shortcode string server-side.
 *
 * @package Testimonials_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class TM_Block
 */
class TM_Block {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_block' ) );
	}

	/**
	 * Register the block type with a server-side render callback.
	 */
	public function register_block() {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		wp_register_script(
			'tm-block-editor',
			TM_PLUGIN_URL . 'admin/js/block.js',
			array( 'wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n', 'wp-block-editor' ),
			TM_VERSION,
			true
		);

		register_block_type(
			'testimonials-manager/testimonials',
			array(
				'editor_script'   => 'tm-block-editor',
				'render_callback' => array( $this, 'render' ),
				'attributes'      => array(
					'layout'   => array( 'type' => 'string', 'default' => TM_Settings::get_value( 'default_layout', 'grid' ) ),
					'limit'    => array( 'type' => 'number', 'default' => (int) TM_Settings::get_value( 'default_limit', 6 ) ),
					'category' => array( 'type' => 'string', 'default' => '' ),
					'featured' => array( 'type' => 'string', 'default' => '' ),
				),
			)
		);
	}

	/**
	 * Server-side render callback: convert block attributes into the
	 * equivalent [testimonials] shortcode and let TM_Shortcodes handle it,
	 * so there is exactly one rendering code path for both APIs.
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public function render( $attributes ) {
		$shortcode_atts = array();

		if ( ! empty( $attributes['layout'] ) ) {
			$shortcode_atts[] = 'layout="' . esc_attr( $attributes['layout'] ) . '"';
		}
		if ( ! empty( $attributes['limit'] ) ) {
			$shortcode_atts[] = 'limit="' . (int) $attributes['limit'] . '"';
		}
		if ( ! empty( $attributes['category'] ) ) {
			$shortcode_atts[] = 'category="' . esc_attr( $attributes['category'] ) . '"';
		}
		if ( ! empty( $attributes['featured'] ) ) {
			$shortcode_atts[] = 'featured="' . esc_attr( $attributes['featured'] ) . '"';
		}

		return do_shortcode( '[testimonials ' . implode( ' ', $shortcode_atts ) . ']' );
	}
}
