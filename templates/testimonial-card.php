<?php
/**
 * Template: single testimonial card.
 *
 * Expected variables: $data (array from tm_get_testimonial_data), $atts (shortcode atts).
 *
 * @package Testimonials_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( empty( $data ) ) {
	return;
}

$alignment_class = isset( $atts['content_alignment'] ) && 'center' === $atts['content_alignment'] ? ' tm-align-center' : '';
$show_images      = isset( $atts['show_images'] ) ? $atts['show_images'] : true;
$show_ratings      = isset( $atts['show_ratings'] ) ? $atts['show_ratings'] : true;
?>
<div class="tm-testimonial-card<?php echo esc_attr( $alignment_class ); ?>" data-testimonial-id="<?php echo esc_attr( $data['id'] ); ?>">
	<?php if ( $show_ratings && $data['rating'] > 0 ) : ?>
		<?php echo tm_get_rating_html( $data['rating'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pre-escaped in helper. ?>
	<?php endif; ?>

	<div class="tm-testimonial-content">
		<?php echo wp_kses_post( $data['content'] ); ?>
	</div>

	<div class="tm-testimonial-author">
		<?php if ( $show_images ) : ?>
			<div class="tm-author-image">
				<?php if ( $data['has_image'] ) : ?>
					<?php echo wp_get_attachment_image( $data['image_id'], 'thumbnail', false, array( 'class' => 'tm-avatar', 'alt' => esc_attr( $data['name'] ) ) ); ?>
				<?php else : ?>
					<span class="tm-avatar tm-avatar-placeholder" aria-hidden="true">
						<?php echo esc_html( $data['name'] ? mb_substr( $data['name'], 0, 1 ) : '?' ); ?>
					</span>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<div class="tm-author-info">
			<?php if ( $data['website'] ) : ?>
				<a class="tm-author-name" href="<?php echo esc_url( $data['website'] ); ?>" target="_blank" rel="noopener noreferrer nofollow">
					<?php echo esc_html( $data['name'] ); ?>
				</a>
			<?php else : ?>
				<span class="tm-author-name"><?php echo esc_html( $data['name'] ); ?></span>
			<?php endif; ?>

			<?php if ( $data['title'] || $data['company'] ) : ?>
				<span class="tm-author-role">
					<?php
					echo esc_html( trim( $data['title'] . ( $data['title'] && $data['company'] ? ', ' : '' ) . $data['company'] ) );
					?>
				</span>
			<?php endif; ?>

			<?php if ( $data['location'] ) : ?>
				<span class="tm-author-location"><?php echo esc_html( $data['location'] ); ?></span>
			<?php endif; ?>
		</div>
	</div>
</div>
