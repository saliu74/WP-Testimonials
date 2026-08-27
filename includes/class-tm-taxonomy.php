<?php
/**
 * Registers the `testimonial_category` taxonomy.
 *
 * @package Testimonials_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class TM_Taxonomy
 */
class TM_Taxonomy {

	const TAXONOMY = 'testimonial_category';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_taxonomy' ) );
	}

	/**
	 * Register the non-hierarchical-by-default testimonial category
	 * taxonomy. Administrators can freely create their own terms —
	 * nothing is hard-coded.
	 */
	public function register_taxonomy() {
		$labels = array(
			'name'                       => _x( 'Testimonial Categories', 'taxonomy general name', 'testimonials-manager' ),
			'singular_name'              => _x( 'Testimonial Category', 'taxonomy singular name', 'testimonials-manager' ),
			'search_items'               => __( 'Search Categories', 'testimonials-manager' ),
			'all_items'                  => __( 'Categories', 'testimonials-manager' ),
			'parent_item'                => __( 'Parent Category', 'testimonials-manager' ),
			'parent_item_colon'          => __( 'Parent Category:', 'testimonials-manager' ),
			'edit_item'                  => __( 'Edit Category', 'testimonials-manager' ),
			'update_item'                => __( 'Update Category', 'testimonials-manager' ),
			'add_new_item'               => __( 'Add New Category', 'testimonials-manager' ),
			'new_item_name'              => __( 'New Category Name', 'testimonials-manager' ),
			'menu_name'                  => __( 'Categories', 'testimonials-manager' ),
			'not_found'                  => __( 'No categories found.', 'testimonials-manager' ),
			'popular_items'              => __( 'Popular Categories', 'testimonials-manager' ),
			'separate_items_with_commas' => __( 'Separate categories with commas', 'testimonials-manager' ),
		);

		$args = array(
			'hierarchical'      => true,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'query_var'         => true,
			'rewrite'           => array( 'slug' => 'testimonial-category' ),
		);

		/**
		 * Filter the testimonial_category taxonomy registration args.
		 *
		 * @param array $args Taxonomy arguments.
		 */
		$args = apply_filters( 'tm_testimonial_taxonomy_args', $args );

		register_taxonomy( self::TAXONOMY, array( TM_Post_Type::POST_TYPE ), $args );
	}
}
