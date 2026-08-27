<?php
/**
 * Registers the `testimonial` custom post type.
 *
 * @package Testimonials_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class TM_Post_Type
 */
class TM_Post_Type {

	const POST_TYPE = 'testimonial';

	/**
	 * Constructor — hooks registration into `init`.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( $this, 'admin_columns' ) );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( $this, 'render_admin_column' ), 10, 2 );
		add_filter( 'manage_edit-' . self::POST_TYPE . '_sortable_columns', array( $this, 'sortable_columns' ) );
		add_action( 'pre_get_posts', array( $this, 'sort_by_custom_column' ) );
		add_action( 'restrict_manage_posts', array( $this, 'category_filter_dropdown' ) );
		add_action( 'pre_get_posts', array( $this, 'filter_by_category' ) );
	}

	/**
	 * Register the `testimonial` custom post type.
	 */
	public function register_post_type() {
		$labels = array(
			'name'                  => _x( 'Testimonials', 'Post type general name', 'testimonials-manager' ),
			'singular_name'         => _x( 'Testimonial', 'Post type singular name', 'testimonials-manager' ),
			'menu_name'             => _x( 'Testimonials', 'Admin Menu text', 'testimonials-manager' ),
			'name_admin_bar'        => _x( 'Testimonial', 'Add New on Toolbar', 'testimonials-manager' ),
			'add_new'               => __( 'Add New', 'testimonials-manager' ),
			'add_new_item'          => __( 'Add New Testimonial', 'testimonials-manager' ),
			'new_item'              => __( 'New Testimonial', 'testimonials-manager' ),
			'edit_item'             => __( 'Edit Testimonial', 'testimonials-manager' ),
			'view_item'             => __( 'View Testimonial', 'testimonials-manager' ),
			'all_items'             => __( 'All Testimonials', 'testimonials-manager' ),
			'search_items'          => __( 'Search Testimonials', 'testimonials-manager' ),
			'not_found'             => __( 'No testimonials found.', 'testimonials-manager' ),
			'not_found_in_trash'    => __( 'No testimonials found in Trash.', 'testimonials-manager' ),
			'featured_image'        => __( 'Customer Image', 'testimonials-manager' ),
			'set_featured_image'    => __( 'Set customer image', 'testimonials-manager' ),
			'remove_featured_image' => __( 'Remove customer image', 'testimonials-manager' ),
			'use_featured_image'    => __( 'Use as customer image', 'testimonials-manager' ),
			'archives'              => __( 'Testimonial archives', 'testimonials-manager' ),
			'insert_into_item'      => __( 'Insert into testimonial', 'testimonials-manager' ),
			'uploaded_to_this_item' => __( 'Uploaded to this testimonial', 'testimonials-manager' ),
			'filter_items_list'     => __( 'Filter testimonials list', 'testimonials-manager' ),
			'items_list_navigation' => __( 'Testimonials list navigation', 'testimonials-manager' ),
			'items_list'            => __( 'Testimonials list', 'testimonials-manager' ),
		);

		$args = array(
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'menu_icon'          => 'dashicons-format-quote',
			'menu_position'      => 25,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'testimonial' ),
			'capability_type'    => 'post',
			'has_archive'        => false,
			'hierarchical'       => false,
			'supports'           => array( 'title', 'editor', 'thumbnail', 'revisions', 'author' ),
			'show_in_rest'       => true,
			'rest_base'          => 'testimonials',
			/**
			 * Testimonials are content fragments meant to be displayed via
			 * shortcode, not a public archive/search result by default. Sites
			 * that want a dedicated archive page can still query the post
			 * type directly; we simply keep it out of core WP search so
			 * short quotes don't clutter site search results.
			 */
			'exclude_from_search' => true,
		);

		/**
		 * Filter the testimonial post type registration args.
		 *
		 * @param array $args Post type arguments passed to register_post_type().
		 */
		$args = apply_filters( 'tm_testimonial_post_type_args', $args );

		register_post_type( self::POST_TYPE, $args );
	}

	/**
	 * Define the columns shown on the All Testimonials list table.
	 *
	 * @param array $columns Existing columns.
	 * @return array
	 */
	public function admin_columns( $columns ) {
		$new_columns = array();

		foreach ( $columns as $key => $label ) {
			if ( 'title' === $key ) {
				$new_columns['tm_customer'] = __( 'Customer', 'testimonials-manager' );
				$new_columns['tm_excerpt']  = __( 'Testimonial', 'testimonials-manager' );
				continue;
			}
			if ( 'date' === $key ) {
				$new_columns['tm_category'] = __( 'Category', 'testimonials-manager' );
				$new_columns['tm_rating']   = __( 'Rating', 'testimonials-manager' );
				$new_columns['tm_featured'] = __( 'Featured', 'testimonials-manager' );
			}
			$new_columns[ $key ] = $label;
		}

		unset( $new_columns['title'] );

		return $new_columns;
	}

	/**
	 * Render custom admin list-table columns.
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id Post ID.
	 */
	public function render_admin_column( $column, $post_id ) {
		switch ( $column ) {
			case 'tm_customer':
				$name  = get_post_meta( $post_id, '_tm_customer_name', true );
				$title = get_post_meta( $post_id, '_tm_customer_title', true );
				$company = get_post_meta( $post_id, '_tm_company', true );

				echo '<strong><a class="row-title" href="' . esc_url( get_edit_post_link( $post_id ) ) . '">' . esc_html( $name ? $name : __( '(no name)', 'testimonials-manager' ) ) . '</a></strong>';

				if ( $title || $company ) {
					echo '<br /><span class="tm-meta-sub">' . esc_html( trim( $title . ( $title && $company ? ', ' : '' ) . $company ) ) . '</span>';
				}
				break;

			case 'tm_excerpt':
				$content = get_post_field( 'post_content', $post_id );
				echo esc_html( wp_trim_words( wp_strip_all_tags( $content ), 18 ) );
				break;

			case 'tm_category':
				$terms = get_the_terms( $post_id, 'testimonial_category' );
				if ( $terms && ! is_wp_error( $terms ) ) {
					$names = wp_list_pluck( $terms, 'name' );
					echo esc_html( implode( ', ', $names ) );
				} else {
					echo '&#8212;';
				}
				break;

			case 'tm_rating':
				$rating = (int) get_post_meta( $post_id, '_tm_rating', true );
				if ( $rating > 0 ) {
					echo esc_html( str_repeat( '★', $rating ) . str_repeat( '☆', 5 - $rating ) );
				} else {
					echo '&#8212;';
				}
				break;

			case 'tm_featured':
				$featured = get_post_meta( $post_id, '_tm_featured', true );
				echo $featured ? '<span class="dashicons dashicons-star-filled" style="color:#e1a600;" aria-label="' . esc_attr__( 'Featured', 'testimonials-manager' ) . '"></span>' : '&#8212;';
				break;
		}
	}

	/**
	 * Mark rating/customer columns as sortable.
	 *
	 * @param array $columns Sortable columns.
	 * @return array
	 */
	public function sortable_columns( $columns ) {
		$columns['tm_customer'] = 'tm_customer';
		$columns['tm_rating']   = 'tm_rating';
		return $columns;
	}

	/**
	 * Handle sorting for the custom sortable columns above.
	 *
	 * @param WP_Query $query Current query.
	 */
	public function sort_by_custom_column( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( self::POST_TYPE !== $query->get( 'post_type' ) ) {
			return;
		}

		$orderby = $query->get( 'orderby' );

		if ( 'tm_customer' === $orderby ) {
			$query->set( 'meta_key', '_tm_customer_name' );
			$query->set( 'orderby', 'meta_value' );
		} elseif ( 'tm_rating' === $orderby ) {
			$query->set( 'meta_key', '_tm_rating' );
			$query->set( 'orderby', 'meta_value_num' );
		}
	}

	/**
	 * Add a category filter dropdown above the list table.
	 */
	public function category_filter_dropdown() {
		global $typenow;

		if ( self::POST_TYPE !== $typenow ) {
			return;
		}

		$selected = isset( $_GET['tm_category'] ) ? sanitize_text_field( wp_unslash( $_GET['tm_category'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		wp_dropdown_categories(
			array(
				'show_option_all' => __( 'All Categories', 'testimonials-manager' ),
				'taxonomy'        => 'testimonial_category',
				'name'            => 'tm_category',
				'orderby'         => 'name',
				'selected'        => $selected,
				'hierarchical'    => false,
				'depth'           => 1,
				'value_field'     => 'slug',
			)
		);
	}

	/**
	 * Apply the category filter dropdown to the query.
	 *
	 * @param WP_Query $query Current query.
	 */
	public function filter_by_category( $query ) {
		global $pagenow, $typenow;

		if ( 'edit.php' !== $pagenow || self::POST_TYPE !== $typenow || ! $query->is_main_query() ) {
			return;
		}

		if ( empty( $_GET['tm_category'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		$query->set(
			'tax_query',
			array(
				array(
					'taxonomy' => 'testimonial_category',
					'field'    => 'slug',
					'terms'    => sanitize_text_field( wp_unslash( $_GET['tm_category'] ) ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				),
			)
		);
	}
}
