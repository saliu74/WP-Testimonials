<?php
/**
 * Template helper functions.
 *
 * These are plain functions (not class methods) so template files can call
 * them directly, matching common WordPress theme/plugin conventions.
 *
 * @package Testimonials_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Locate and load a template file, allowing themes to override it by
 * placing a file at wp-content/themes/your-theme/testimonials-manager/{name}.
 *
 * @param string $name Template file name, e.g. 'grid.php'.
 * @param array  $args Variables to extract() into the template's local scope.
 */
function tm_get_template( $name, $args = array() ) {
	$theme_override = locate_template( array( 'testimonials-manager/' . $name ) );
	$path           = $theme_override ? $theme_override : TM_PLUGIN_DIR . 'templates/' . $name;

	/**
	 * Filter the resolved template path.
	 *
	 * @param string $path Absolute path to the template file.
	 * @param string $name Template file name requested.
	 * @param array  $args Template arguments.
	 */
	$path = apply_filters( 'tm_template_path', $path, $name, $args );

	if ( ! file_exists( $path ) ) {
		return;
	}

	if ( ! empty( $args ) && is_array( $args ) ) {
		// Intentional extract(): templates are plugin-controlled files, not
		// user input, and this is the standard WordPress templating idiom.
		extract( $args ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract
	}

	include $path;
}

/**
 * Render the accessible star rating markup for a testimonial.
 *
 * @param int $rating Rating from 0-5.
 * @return string HTML.
 */
function tm_get_rating_html( $rating ) {
	$rating = max( 0, min( 5, (int) $rating ) );

	if ( 0 === $rating ) {
		return '';
	}

	$stars = '';
	for ( $i = 1; $i <= 5; $i++ ) {
		$stars .= $i <= $rating
			? '<span class="tm-star tm-star-filled" aria-hidden="true">★</span>'
			: '<span class="tm-star tm-star-empty" aria-hidden="true">☆</span>';
	}

	return sprintf(
		'<div class="tm-rating" role="img" aria-label="%s">%s</div>',
		esc_attr(
			sprintf(
				/* translators: 1: rating given, 2: rating scale max */
				__( 'Rated %1$d out of %2$d', 'testimonials-manager' ),
				$rating,
				5
			)
		),
		$stars
	);
}

/**
 * Build a normalized data array for a single testimonial post, used by all
 * layout templates so the card partial stays consistent everywhere.
 *
 * @param int|WP_Post $post Post ID or object.
 * @return array
 */
function tm_get_testimonial_data( $post ) {
	$post = get_post( $post );

	if ( ! $post ) {
		return array();
	}

	$categories = get_the_terms( $post->ID, 'testimonial_category' );
	$cat_names  = array();
	if ( $categories && ! is_wp_error( $categories ) ) {
		$cat_names = wp_list_pluck( $categories, 'name' );
	}

	$date_meta = get_post_meta( $post->ID, '_tm_testimonial_date', true );

	return array(
		'id'       => $post->ID,
		'content'  => apply_filters( 'the_content', $post->post_content ),
		'name'     => get_post_meta( $post->ID, '_tm_customer_name', true ),
		'title'    => get_post_meta( $post->ID, '_tm_customer_title', true ),
		'company'  => get_post_meta( $post->ID, '_tm_company', true ),
		'location' => get_post_meta( $post->ID, '_tm_location', true ),
		'website'  => get_post_meta( $post->ID, '_tm_website', true ),
		'rating'   => (int) get_post_meta( $post->ID, '_tm_rating', true ),
		'featured' => (bool) get_post_meta( $post->ID, '_tm_featured', true ),
		'date'     => $date_meta ? $date_meta : get_the_date( '', $post ),
		'categories' => $cat_names,
		'has_image'  => has_post_thumbnail( $post->ID ),
		'image_id'   => get_post_thumbnail_id( $post->ID ),
	);
}
