<?php
/**
 * Fires when the plugin is deleted from the Plugins screen.
 *
 * Data is only removed if the administrator has explicitly opted in via
 * the "Delete all plugin data on uninstall" setting. This file is only
 * ever executed by WordPress core (WP_UNINSTALL_PLUGIN is defined), so we
 * guard against direct access.
 *
 * @package Testimonials_Manager
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$settings = get_option( 'tm_testimonials_settings', array() );

$should_delete = ! empty( $settings['delete_data_on_uninstall'] );

if ( ! $should_delete ) {
	return;
}

// Delete all testimonial posts (and their meta/terms relationships).
$testimonial_ids = get_posts(
	array(
		'post_type'      => 'testimonial',
		'post_status'    => 'any',
		'numberposts'    => -1,
		'fields'         => 'ids',
		'suppress_filters' => true,
	)
);

foreach ( $testimonial_ids as $testimonial_id ) {
	wp_delete_post( $testimonial_id, true );
}

// Delete custom taxonomy terms.
$terms = get_terms(
	array(
		'taxonomy'   => 'testimonial_category',
		'hide_empty' => false,
		'fields'     => 'ids',
	)
);

if ( ! is_wp_error( $terms ) ) {
	foreach ( $terms as $term_id ) {
		wp_delete_term( $term_id, 'testimonial_category' );
	}
}

// Delete plugin options.
delete_option( 'tm_testimonials_settings' );
delete_option( 'tm_testimonials_db_version' );

// Delete any transients used for import progress tracking.
global $wpdb;
$wpdb->query(
	"DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_tm_import_%' OR option_name LIKE '_transient_timeout_tm_import_%'"
);
