<?php
/**
 * Template: carousel layout.
 *
 * Expected variables: $atts, $query.
 *
 * @package Testimonials_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$slide_count = $query->post_count;
?>
<div
	id="<?php echo esc_attr( $atts['id'] ); ?>"
	class="tm-testimonials tm-testimonial-carousel"
	data-tm-layout="carousel"
	data-autoplay="<?php echo $atts['autoplay'] ? 'true' : 'false'; ?>"
	data-interval="<?php echo esc_attr( $atts['interval'] ); ?>"
	data-slides-desktop="<?php echo esc_attr( $atts['slides_desktop'] ); ?>"
	data-slides-tablet="<?php echo esc_attr( $atts['slides_tablet'] ); ?>"
	data-slides-mobile="<?php echo esc_attr( $atts['slides_mobile'] ); ?>"
	role="region"
	aria-roledescription="carousel"
	aria-label="<?php esc_attr_e( 'Customer testimonials', 'testimonials-manager' ); ?>"
>
	<div class="tm-carousel-viewport">
		<div class="tm-carousel-track" role="presentation">
			<?php
			$index = 0;
			while ( $query->have_posts() ) :
				$query->the_post();
				$data = tm_get_testimonial_data( get_the_ID() );
				$index++;
				?>
				<div
					class="tm-carousel-slide"
					role="group"
					aria-roledescription="slide"
					aria-label="<?php echo esc_attr( sprintf( /* translators: 1: slide number, 2: total slides */ __( '%1$d of %2$d', 'testimonials-manager' ), $index, $slide_count ) ); ?>"
				>
					<?php tm_get_template( 'testimonial-card.php', array( 'data' => $data, 'atts' => $atts ) ); ?>
				</div>
			<?php endwhile; ?>
		</div>
	</div>

	<?php if ( $atts['arrows'] && $slide_count > 1 ) : ?>
		<button type="button" class="tm-carousel-arrow tm-carousel-prev" aria-label="<?php esc_attr_e( 'Previous testimonial', 'testimonials-manager' ); ?>">
			<span aria-hidden="true">&#10094;</span>
		</button>
		<button type="button" class="tm-carousel-arrow tm-carousel-next" aria-label="<?php esc_attr_e( 'Next testimonial', 'testimonials-manager' ); ?>">
			<span aria-hidden="true">&#10095;</span>
		</button>
	<?php endif; ?>

	<?php if ( $atts['autoplay'] && $slide_count > 1 ) : ?>
		<button type="button" class="tm-carousel-playpause" aria-pressed="true" aria-label="<?php esc_attr_e( 'Pause autoplay', 'testimonials-manager' ); ?>">
			<span class="tm-icon-pause" aria-hidden="true">&#10073;&#10073;</span>
		</button>
	<?php endif; ?>

	<?php if ( $atts['dots'] && $slide_count > 1 ) : ?>
		<div class="tm-carousel-dots" role="tablist" aria-label="<?php esc_attr_e( 'Slide navigation', 'testimonials-manager' ); ?>"></div>
	<?php endif; ?>

	<p class="screen-reader-text tm-carousel-live" aria-live="polite"></p>
</div>
