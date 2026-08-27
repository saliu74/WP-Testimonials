<?php
/**
 * Template: vertical list layout.
 *
 * Expected variables: $atts, $query.
 *
 * @package Testimonials_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div id="<?php echo esc_attr( $atts['id'] ); ?>" class="tm-testimonials tm-testimonial-list" data-tm-layout="list">
	<?php
	while ( $query->have_posts() ) :
		$query->the_post();
		$data = tm_get_testimonial_data( get_the_ID() );
		tm_get_template( 'testimonial-card.php', array( 'data' => $data, 'atts' => $atts ) );
	endwhile;
	?>
</div>
