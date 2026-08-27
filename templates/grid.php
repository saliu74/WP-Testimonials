<?php
/**
 * Template: grid layout wrapper.
 *
 * Expected variables: $atts, $query, $query_args, $paged.
 *
 * @package Testimonials_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div
	id="<?php echo esc_attr( $atts['id'] ); ?>"
	class="tm-testimonials tm-testimonial-grid tm-columns-<?php echo esc_attr( $atts['columns'] ); ?>"
	data-tm-layout="grid"
	<?php if ( $atts['pagination'] ) : ?>
	data-tm-pagination="true"
	data-tm-atts="<?php echo esc_attr( wp_json_encode( $atts ) ); ?>"
	<?php endif; ?>
>
	<div class="tm-grid-items">
		<?php tm_get_template( 'grid-items.php', array( 'atts' => $atts, 'query' => $query ) ); ?>
	</div>

	<?php if ( $atts['pagination'] && $query->max_num_pages > 1 ) : ?>
		<nav class="tm-grid-pagination" aria-label="<?php esc_attr_e( 'Testimonials pagination', 'testimonials-manager' ); ?>">
			<?php
			$base_url = remove_query_arg( 'tm_page_' . $atts['id'] );

			if ( $paged > 1 ) :
				?>
				<a href="<?php echo esc_url( add_query_arg( 'tm_page_' . $atts['id'], $paged - 1, $base_url ) ); ?>" class="tm-page-link tm-page-prev" data-tm-page="<?php echo esc_attr( $paged - 1 ); ?>">
					<?php esc_html_e( 'Previous', 'testimonials-manager' ); ?>
				</a>
			<?php endif; ?>

			<span class="tm-page-status">
				<?php
				printf(
					/* translators: 1: current page, 2: total pages */
					esc_html__( 'Page %1$d of %2$d', 'testimonials-manager' ),
					(int) $paged,
					(int) $query->max_num_pages
				);
				?>
			</span>

			<?php if ( $paged < $query->max_num_pages ) : ?>
				<a href="<?php echo esc_url( add_query_arg( 'tm_page_' . $atts['id'], $paged + 1, $base_url ) ); ?>" class="tm-page-link tm-page-next" data-tm-page="<?php echo esc_attr( $paged + 1 ); ?>">
					<?php esc_html_e( 'Next', 'testimonials-manager' ); ?>
				</a>
			<?php endif; ?>
		</nav>
	<?php endif; ?>
</div>
