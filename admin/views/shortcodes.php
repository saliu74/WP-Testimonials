<?php
/**
 * Admin view: Testimonials → Shortcodes.
 *
 * A ready-to-copy reference so administrators/content editors always have
 * an obvious place to find the shortcode needed to actually display
 * testimonials on the front end — the plugin has no other UI for this.
 *
 * Expected variables: $categories (array of WP_Term), $published (int).
 *
 * @package Testimonials_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$first_category_slug = ! empty( $categories ) ? $categories[0]->slug : '';

$examples = array(
	array(
		'title'       => __( 'Default', 'testimonials-manager' ),
		'description' => __( 'Uses whatever layout and defaults are set on the Settings page.', 'testimonials-manager' ),
		'shortcode'   => '[testimonials]',
	),
	array(
		'title'       => __( 'Homepage carousel', 'testimonials-manager' ),
		'description' => __( 'A featured, autoplaying carousel — ideal for a homepage or landing page.', 'testimonials-manager' ),
		'shortcode'   => '[testimonials layout="carousel" limit="6" featured="true" autoplay="true"]',
	),
	array(
		'title'       => __( 'Full testimonials page (grid + pagination)', 'testimonials-manager' ),
		'description' => __( 'A paginated grid — ideal for a dedicated "Testimonials" or "Reviews" page.', 'testimonials-manager' ),
		'shortcode'   => '[testimonials layout="grid" limit="12" columns="3" pagination="true"]',
	),
	array(
		'title'       => __( 'Filtered by category', 'testimonials-manager' ),
		'description' => __( 'Only testimonials in a specific category.', 'testimonials-manager' ),
		'shortcode'   => $first_category_slug
			? '[testimonials layout="grid" category="' . $first_category_slug . '"]'
			: '[testimonials layout="grid" category="customers"]',
	),
	array(
		'title'       => __( 'Sidebar / compact list', 'testimonials-manager' ),
		'description' => __( 'A simple vertical list — useful for sidebars or long-form service pages.', 'testimonials-manager' ),
		'shortcode'   => '[testimonials layout="list" limit="5"]',
	),
);
?>
<div class="wrap tm-wrap">
	<h1><?php esc_html_e( 'Testimonials Shortcodes', 'testimonials-manager' ); ?></h1>

	<?php if ( 0 === $published ) : ?>
		<div class="notice notice-warning inline">
			<p>
				<?php
				printf(
					/* translators: %s: link to the Import page */
					wp_kses_post( __( 'You don\'t have any published testimonials yet. %s or add one manually before placing a shortcode.', 'testimonials-manager' ) ),
					'<a href="' . esc_url( admin_url( 'edit.php?post_type=testimonial&page=tm-testimonials-import' ) ) . '">' . esc_html__( 'Import some from a spreadsheet', 'testimonials-manager' ) . '</a>'
				);
				?>
			</p>
		</div>
	<?php endif; ?>

	<p class="description">
		<?php esc_html_e( 'Copy any shortcode below and paste it into a page or post using a Shortcode block (or the Classic Editor). You can also use the Testimonials block in the block editor, which offers the same options through a visual panel.', 'testimonials-manager' ); ?>
	</p>

	<div class="tm-shortcode-examples">
		<?php foreach ( $examples as $example ) : ?>
			<div class="tm-card tm-shortcode-example">
				<h2><?php echo esc_html( $example['title'] ); ?></h2>
				<p><?php echo esc_html( $example['description'] ); ?></p>
				<div class="tm-shortcode-row">
					<code class="tm-shortcode-code"><?php echo esc_html( $example['shortcode'] ); ?></code>
					<button type="button" class="button tm-copy-shortcode" data-shortcode="<?php echo esc_attr( $example['shortcode'] ); ?>">
						<?php esc_html_e( 'Copy', 'testimonials-manager' ); ?>
					</button>
				</div>
			</div>
		<?php endforeach; ?>
	</div>

	<div class="tm-card">
		<h2><?php esc_html_e( 'All available attributes', 'testimonials-manager' ); ?></h2>
		<table class="widefat striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Attribute', 'testimonials-manager' ); ?></th>
					<th><?php esc_html_e( 'Values', 'testimonials-manager' ); ?></th>
					<th><?php esc_html_e( 'Description', 'testimonials-manager' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<tr><td><code>layout</code></td><td>grid | carousel | list</td><td><?php esc_html_e( 'Display layout.', 'testimonials-manager' ); ?></td></tr>
				<tr><td><code>limit</code></td><td><?php esc_html_e( 'number', 'testimonials-manager' ); ?></td><td><?php esc_html_e( 'How many testimonials to show per page/view.', 'testimonials-manager' ); ?></td></tr>
				<tr><td><code>category</code></td><td><?php esc_html_e( 'category slug(s), comma-separated', 'testimonials-manager' ); ?></td><td>
					<?php
					if ( ! empty( $categories ) ) {
						$slugs = wp_list_pluck( $categories, 'slug' );
						printf(
							/* translators: %s: comma-separated list of category slugs */
							esc_html__( 'Filter by category. Your current categories: %s', 'testimonials-manager' ),
							esc_html( implode( ', ', $slugs ) )
						);
					} else {
						esc_html_e( 'Filter by category. You have not created any categories yet.', 'testimonials-manager' );
					}
					?>
				</td></tr>
				<tr><td><code>featured</code></td><td>true | false</td><td><?php esc_html_e( 'Only featured, or exclude featured.', 'testimonials-manager' ); ?></td></tr>
				<tr><td><code>rating</code></td><td>1-5</td><td><?php esc_html_e( 'Minimum star rating.', 'testimonials-manager' ); ?></td></tr>
				<tr><td><code>columns</code></td><td>1-6</td><td><?php esc_html_e( 'Grid columns on desktop (auto-reduces on tablet/mobile).', 'testimonials-manager' ); ?></td></tr>
				<tr><td><code>pagination</code></td><td>true | false</td><td><?php esc_html_e( 'Grid pagination.', 'testimonials-manager' ); ?></td></tr>
				<tr><td><code>orderby</code></td><td>date | title | rating | menu_order | rand</td><td><?php esc_html_e( 'Sort field.', 'testimonials-manager' ); ?></td></tr>
				<tr><td><code>order</code></td><td>ASC | DESC</td><td><?php esc_html_e( 'Sort direction.', 'testimonials-manager' ); ?></td></tr>
				<tr><td><code>autoplay</code></td><td>true | false</td><td><?php esc_html_e( 'Carousel autoplay.', 'testimonials-manager' ); ?></td></tr>
				<tr><td><code>interval</code></td><td><?php esc_html_e( 'milliseconds', 'testimonials-manager' ); ?></td><td><?php esc_html_e( 'Carousel autoplay speed.', 'testimonials-manager' ); ?></td></tr>
				<tr><td><code>arrows</code> / <code>dots</code></td><td>true | false</td><td><?php esc_html_e( 'Carousel navigation controls.', 'testimonials-manager' ); ?></td></tr>
				<tr><td><code>slides_desktop</code> / <code>slides_tablet</code> / <code>slides_mobile</code></td><td><?php esc_html_e( 'number', 'testimonials-manager' ); ?></td><td><?php esc_html_e( 'Carousel slides visible per breakpoint.', 'testimonials-manager' ); ?></td></tr>
				<tr><td><code>show_images</code> / <code>show_ratings</code></td><td>true | false</td><td><?php esc_html_e( 'Toggle customer photos / star ratings.', 'testimonials-manager' ); ?></td></tr>
			</tbody>
		</table>
		<p class="description" style="margin-top:12px;">
			<?php
			printf(
				/* translators: %s: link to the Settings page */
				wp_kses_post( __( 'Any attribute you omit falls back to the default set on the %s page.', 'testimonials-manager' ) ),
				'<a href="' . esc_url( admin_url( 'edit.php?post_type=testimonial&page=tm-testimonials-settings' ) ) . '">' . esc_html__( 'Settings', 'testimonials-manager' ) . '</a>'
			);
			?>
		</p>
	</div>
</div>
