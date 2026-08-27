<?php
/**
 * Template: grid item cards only (no wrapper) — shared by the initial
 * server render and the AJAX pagination response so markup never drifts.
 *
 * Expected variables: $atts, $query.
 *
 * @package Testimonials_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! $query->have_posts() ) {
	echo '<p class="tm-no-testimonials">' . esc_html__( 'No testimonials found.', 'testimonials-manager' ) . '</p>';
	return;
}

while ( $query->have_posts() ) :
	$query->the_post();
	$data = tm_get_testimonial_data( get_the_ID() );
	tm_get_template( 'testimonial-card.php', array( 'data' => $data, 'atts' => $atts ) );
endwhile;
