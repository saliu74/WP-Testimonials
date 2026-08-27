<?php
/**
 * The [testimonials] shortcode.
 *
 * @package Testimonials_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class TM_Shortcodes
 */
class TM_Shortcodes {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_shortcode( 'testimonials', array( $this, 'render' ) );
		add_action( 'wp_ajax_tm_load_grid_page', array( $this, 'ajax_load_grid_page' ) );
		add_action( 'wp_ajax_nopriv_tm_load_grid_page', array( $this, 'ajax_load_grid_page' ) );
	}

	/**
	 * Parse and normalize shortcode attributes against plugin defaults.
	 *
	 * @param array $atts Raw shortcode attributes.
	 * @return array Normalized attributes.
	 */
	public function parse_atts( $atts ) {
		$settings = TM_Settings::get();

		$atts = shortcode_atts(
			array(
				'layout'      => $settings['default_layout'],
				'limit'       => $settings['default_limit'],
				'category'    => $settings['default_category'],
				'featured'    => '', // '' = any, 'true' = only featured, 'false' = exclude featured.
				'rating'      => '', // minimum rating filter.
				'columns'     => $settings['grid_columns'],
				'pagination'  => $settings['grid_pagination'] ? 'true' : 'false',
				'orderby'     => $settings['default_orderby'],
				'order'       => $settings['default_order'],
				'autoplay'    => $settings['carousel_autoplay'] ? 'true' : 'false',
				'interval'    => $settings['carousel_interval'],
				'arrows'      => $settings['carousel_arrows'] ? 'true' : 'false',
				'dots'        => $settings['carousel_dots'] ? 'true' : 'false',
				'slides_desktop' => $settings['carousel_slides_desktop'],
				'slides_tablet'  => $settings['carousel_slides_tablet'],
				'slides_mobile'  => $settings['carousel_slides_mobile'],
				'show_images' => $settings['show_images'] ? 'true' : 'false',
				'show_ratings' => $settings['show_ratings'] ? 'true' : 'false',
				'id'          => '', // optional explicit wrapper id, auto-generated otherwise.
			),
			$atts,
			'testimonials'
		);

		// Normalize types.
		$atts['layout']     = in_array( $atts['layout'], array( 'grid', 'carousel', 'list' ), true ) ? $atts['layout'] : 'grid';
		$atts['limit']      = (int) $atts['limit'];
		$atts['columns']    = max( 1, min( 6, (int) $atts['columns'] ) );
		$atts['pagination'] = filter_var( $atts['pagination'], FILTER_VALIDATE_BOOLEAN );
		$atts['autoplay']   = filter_var( $atts['autoplay'], FILTER_VALIDATE_BOOLEAN );
		$atts['arrows']     = filter_var( $atts['arrows'], FILTER_VALIDATE_BOOLEAN );
		$atts['dots']       = filter_var( $atts['dots'], FILTER_VALIDATE_BOOLEAN );
		$atts['show_images'] = filter_var( $atts['show_images'], FILTER_VALIDATE_BOOLEAN );
		$atts['show_ratings'] = filter_var( $atts['show_ratings'], FILTER_VALIDATE_BOOLEAN );
		$atts['interval']   = max( 1000, (int) $atts['interval'] );
		$atts['slides_desktop'] = max( 1, (int) $atts['slides_desktop'] );
		$atts['slides_tablet']  = max( 1, (int) $atts['slides_tablet'] );
		$atts['slides_mobile']  = max( 1, (int) $atts['slides_mobile'] );
		$atts['rating']     = '' === $atts['rating'] ? 0 : max( 0, min( 5, (int) $atts['rating'] ) );
		$atts['orderby']    = in_array( $atts['orderby'], array( 'date', 'title', 'rating', 'menu_order', 'rand' ), true ) ? $atts['orderby'] : 'date';
		$atts['order']      = 'ASC' === strtoupper( $atts['order'] ) ? 'ASC' : 'DESC';
		$atts['category']   = sanitize_text_field( $atts['category'] );

		if ( '' === $atts['id'] ) {
			$atts['id'] = 'tm-' . substr( md5( wp_json_encode( $atts ) . wp_rand() ), 0, 8 );
		} else {
			$atts['id'] = sanitize_html_class( $atts['id'] );
		}

		return $atts;
	}

	/**
	 * Build a WP_Query args array from normalized shortcode attributes.
	 *
	 * @param array $atts       Normalized attributes.
	 * @param int   $paged      Page number for pagination.
	 * @return array
	 */
	public function build_query_args( $atts, $paged = 1 ) {
		$args = array(
			'post_type'      => TM_Post_Type::POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => $atts['limit'] > 0 ? $atts['limit'] : 6,
			'paged'          => max( 1, $paged ),
			'no_found_rows'  => ! $atts['pagination'],
			'ignore_sticky_posts' => true,
		);

		switch ( $atts['orderby'] ) {
			case 'rating':
				$args['meta_key'] = '_tm_rating'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				$args['orderby']  = 'meta_value_num';
				break;
			case 'rand':
				$args['orderby'] = 'rand';
				break;
			case 'title':
				$args['orderby'] = 'title';
				break;
			case 'menu_order':
				$args['orderby'] = 'menu_order date';
				break;
			default:
				$args['orderby'] = 'date';
				break;
		}
		$args['order'] = $atts['order'];

		$tax_query  = array();
		$meta_query = array();

		if ( ! empty( $atts['category'] ) ) {
			$tax_query[] = array(
				'taxonomy' => 'testimonial_category',
				'field'    => 'slug',
				'terms'    => array_map( 'trim', explode( ',', $atts['category'] ) ),
			);
		}

		if ( 'true' === $atts['featured'] || true === $atts['featured'] ) {
			$meta_query[] = array(
				'key'     => '_tm_featured',
				'value'   => '1',
				'compare' => '=',
			);
		} elseif ( 'false' === $atts['featured'] ) {
			$meta_query[] = array(
				'relation' => 'OR',
				array(
					'key'     => '_tm_featured',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => '_tm_featured',
					'value'   => '1',
					'compare' => '!=',
				),
			);
		}

		if ( $atts['rating'] > 0 ) {
			$meta_query[] = array(
				'key'     => '_tm_rating',
				'value'   => $atts['rating'],
				'compare' => '>=',
				'type'    => 'NUMERIC',
			);
		}

		if ( $tax_query ) {
			$args['tax_query'] = $tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		}
		if ( $meta_query ) {
			$args['meta_query'] = $meta_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		}

		/**
		 * Filter the final WP_Query args used by the [testimonials] shortcode.
		 *
		 * @param array $args WP_Query arguments.
		 * @param array $atts Normalized shortcode attributes.
		 */
		return apply_filters( 'tm_testimonials_query_args', $args, $atts );
	}

	/**
	 * Render the shortcode.
	 *
	 * @param array $atts Raw shortcode attributes.
	 * @return string
	 */
	public function render( $atts ) {
		$atts = $this->parse_atts( (array) $atts );

		$paged = 1;
		if ( $atts['pagination'] && 'grid' === $atts['layout'] ) {
			$paged = max( 1, (int) get_query_var( 'paged' ), (int) get_query_var( 'page' ) );
			if ( isset( $_GET[ 'tm_page_' . $atts['id'] ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$paged = max( 1, (int) $_GET[ 'tm_page_' . $atts['id'] ] );
			}
		}

		$query_args = $this->build_query_args( $atts, $paged );
		$query      = new WP_Query( $query_args );

		// Signal to TM_Assets that we need front-end CSS/JS enqueued. Since
		// shortcodes can be injected by widgets/page builders after the
		// `wp` hook has already decided what to enqueue, we hook enqueue
		// directly here as a safety net (TM_Assets also does content
		// scanning for the common case). Guarded because it's
		// theoretically possible for something to invoke the shortcode
		// before every module has finished bootstrapping.
		$assets = TM_Assets::instance();
		if ( $assets ) {
			$assets->mark_shortcode_used( $atts['layout'] );
		}

		ob_start();

		if ( ! $query->have_posts() ) {
			/**
			 * Filter the "no testimonials found" message.
			 *
			 * @param string $message Default message.
			 */
			$message = apply_filters( 'tm_no_testimonials_message', __( 'No testimonials found.', 'testimonials-manager' ) );
			echo '<p class="tm-no-testimonials">' . esc_html( $message ) . '</p>';
			wp_reset_postdata();
			return ob_get_clean();
		}

		switch ( $atts['layout'] ) {
			case 'carousel':
				tm_get_template( 'carousel.php', array( 'atts' => $atts, 'query' => $query ) );
				break;
			case 'list':
				tm_get_template( 'list.php', array( 'atts' => $atts, 'query' => $query ) );
				break;
			case 'grid':
			default:
				tm_get_template(
					'grid.php',
					array(
						'atts'       => $atts,
						'query'      => $query,
						'query_args' => $query_args,
						'paged'      => $paged,
					)
				);
				break;
		}

		wp_reset_postdata();

		return ob_get_clean();
	}

	/**
	 * AJAX handler for grid pagination without a full page reload. Falls
	 * back gracefully: if JS is disabled, the grid template's pagination
	 * links are plain URLs and work without this handler.
	 */
	public function ajax_load_grid_page() {
		check_ajax_referer( 'tm_frontend_nonce', 'nonce' );

		$atts_json = isset( $_POST['atts'] ) ? wp_unslash( $_POST['atts'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$paged     = isset( $_POST['paged'] ) ? max( 1, (int) $_POST['paged'] ) : 1;

		$decoded = json_decode( $atts_json, true );
		if ( ! is_array( $decoded ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid request.', 'testimonials-manager' ) ) );
		}

		// Re-parse through shortcode_atts to guarantee sanitized values —
		// never trust the JSON blob echoed back by the browser.
		$atts = $this->parse_atts( $decoded );
		$atts['id'] = isset( $decoded['id'] ) ? sanitize_html_class( $decoded['id'] ) : $atts['id'];

		$query_args = $this->build_query_args( $atts, $paged );
		$query      = new WP_Query( $query_args );

		ob_start();
		if ( $query->have_posts() ) {
			tm_get_template(
				'grid-items.php',
				array(
					'atts'  => $atts,
					'query' => $query,
				)
			);
		} else {
			echo '<p class="tm-no-testimonials">' . esc_html__( 'No testimonials found.', 'testimonials-manager' ) . '</p>';
		}
		$html = ob_get_clean();
		wp_reset_postdata();

		wp_send_json_success(
			array(
				'html'        => $html,
				'max_pages'   => (int) $query->max_num_pages,
				'paged'       => $paged,
			)
		);
	}
}
