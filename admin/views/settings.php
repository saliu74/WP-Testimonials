<?php
/**
 * Admin view: Testimonials → Settings.
 *
 * Expected variables: $settings (array), $categories (array of WP_Term).
 *
 * @package Testimonials_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap tm-wrap">
	<h1><?php esc_html_e( 'Testimonials Settings', 'testimonials-manager' ); ?></h1>

	<form method="post" action="">
		<?php wp_nonce_field( 'tm_save_settings', 'tm_settings_nonce' ); ?>

		<h2 class="title"><?php esc_html_e( 'General', 'testimonials-manager' ); ?></h2>
		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th><label for="tm_default_layout"><?php esc_html_e( 'Default layout', 'testimonials-manager' ); ?></label></th>
					<td>
						<select name="tm_testimonials_settings[default_layout]" id="tm_default_layout">
							<?php foreach ( array( 'grid' => __( 'Grid', 'testimonials-manager' ), 'carousel' => __( 'Carousel', 'testimonials-manager' ), 'list' => __( 'List', 'testimonials-manager' ) ) as $value => $label ) : ?>
								<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $settings['default_layout'], $value ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
						<p class="description"><?php esc_html_e( 'Used when a [testimonials] shortcode does not specify layout="...".', 'testimonials-manager' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><label for="tm_default_limit"><?php esc_html_e( 'Default number of testimonials', 'testimonials-manager' ); ?></label></th>
					<td><input type="number" min="1" max="100" name="tm_testimonials_settings[default_limit]" id="tm_default_limit" value="<?php echo esc_attr( $settings['default_limit'] ); ?>" class="small-text" /></td>
				</tr>
				<tr>
					<th><label for="tm_default_orderby"><?php esc_html_e( 'Default order by', 'testimonials-manager' ); ?></label></th>
					<td>
						<select name="tm_testimonials_settings[default_orderby]" id="tm_default_orderby">
							<?php foreach ( array( 'date' => __( 'Date', 'testimonials-manager' ), 'title' => __( 'Name', 'testimonials-manager' ), 'rating' => __( 'Rating', 'testimonials-manager' ), 'menu_order' => __( 'Manual order', 'testimonials-manager' ), 'rand' => __( 'Random', 'testimonials-manager' ) ) as $value => $label ) : ?>
								<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $settings['default_orderby'], $value ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
						<select name="tm_testimonials_settings[default_order]">
							<option value="DESC" <?php selected( $settings['default_order'], 'DESC' ); ?>><?php esc_html_e( 'Descending', 'testimonials-manager' ); ?></option>
							<option value="ASC" <?php selected( $settings['default_order'], 'ASC' ); ?>><?php esc_html_e( 'Ascending', 'testimonials-manager' ); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th><label for="tm_default_category"><?php esc_html_e( 'Default category', 'testimonials-manager' ); ?></label></th>
					<td>
						<select name="tm_testimonials_settings[default_category]" id="tm_default_category">
							<option value=""><?php esc_html_e( '— All categories —', 'testimonials-manager' ); ?></option>
							<?php foreach ( $categories as $category ) : ?>
								<option value="<?php echo esc_attr( $category->slug ); ?>" <?php selected( $settings['default_category'], $category->slug ); ?>><?php echo esc_html( $category->name ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Display', 'testimonials-manager' ); ?></th>
					<td>
						<label><input type="checkbox" name="tm_testimonials_settings[show_ratings]" value="1" <?php checked( $settings['show_ratings'], 1 ); ?> /> <?php esc_html_e( 'Show star ratings', 'testimonials-manager' ); ?></label><br />
						<label><input type="checkbox" name="tm_testimonials_settings[show_images]" value="1" <?php checked( $settings['show_images'], 1 ); ?> /> <?php esc_html_e( 'Show customer images', 'testimonials-manager' ); ?></label>
					</td>
				</tr>
			</tbody>
		</table>

		<h2 class="title"><?php esc_html_e( 'Grid', 'testimonials-manager' ); ?></h2>
		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th><label for="tm_grid_columns"><?php esc_html_e( 'Default columns', 'testimonials-manager' ); ?></label></th>
					<td><input type="number" min="1" max="6" name="tm_testimonials_settings[grid_columns]" id="tm_grid_columns" value="<?php echo esc_attr( $settings['grid_columns'] ); ?>" class="small-text" /></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Pagination', 'testimonials-manager' ); ?></th>
					<td><label><input type="checkbox" name="tm_testimonials_settings[grid_pagination]" value="1" <?php checked( $settings['grid_pagination'], 1 ); ?> /> <?php esc_html_e( 'Enable pagination by default', 'testimonials-manager' ); ?></label></td>
				</tr>
			</tbody>
		</table>

		<h2 class="title"><?php esc_html_e( 'Carousel', 'testimonials-manager' ); ?></h2>
		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th><?php esc_html_e( 'Autoplay', 'testimonials-manager' ); ?></th>
					<td><label><input type="checkbox" name="tm_testimonials_settings[carousel_autoplay]" value="1" <?php checked( $settings['carousel_autoplay'], 1 ); ?> /> <?php esc_html_e( 'Enable autoplay by default', 'testimonials-manager' ); ?></label></td>
				</tr>
				<tr>
					<th><label for="tm_carousel_interval"><?php esc_html_e( 'Autoplay interval (ms)', 'testimonials-manager' ); ?></label></th>
					<td><input type="number" min="1000" step="500" name="tm_testimonials_settings[carousel_interval]" id="tm_carousel_interval" value="<?php echo esc_attr( $settings['carousel_interval'] ); ?>" class="small-text" /></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Controls', 'testimonials-manager' ); ?></th>
					<td>
						<label><input type="checkbox" name="tm_testimonials_settings[carousel_arrows]" value="1" <?php checked( $settings['carousel_arrows'], 1 ); ?> /> <?php esc_html_e( 'Show arrows', 'testimonials-manager' ); ?></label><br />
						<label><input type="checkbox" name="tm_testimonials_settings[carousel_dots]" value="1" <?php checked( $settings['carousel_dots'], 1 ); ?> /> <?php esc_html_e( 'Show dots', 'testimonials-manager' ); ?></label>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Slides per view', 'testimonials-manager' ); ?></th>
					<td>
						<label><?php esc_html_e( 'Desktop', 'testimonials-manager' ); ?> <input type="number" min="1" max="6" name="tm_testimonials_settings[carousel_slides_desktop]" value="<?php echo esc_attr( $settings['carousel_slides_desktop'] ); ?>" class="small-text" /></label>
						<label><?php esc_html_e( 'Tablet', 'testimonials-manager' ); ?> <input type="number" min="1" max="4" name="tm_testimonials_settings[carousel_slides_tablet]" value="<?php echo esc_attr( $settings['carousel_slides_tablet'] ); ?>" class="small-text" /></label>
						<label><?php esc_html_e( 'Mobile', 'testimonials-manager' ); ?> <input type="number" min="1" max="2" name="tm_testimonials_settings[carousel_slides_mobile]" value="<?php echo esc_attr( $settings['carousel_slides_mobile'] ); ?>" class="small-text" /></label>
					</td>
				</tr>
			</tbody>
		</table>

		<h2 class="title"><?php esc_html_e( 'Appearance', 'testimonials-manager' ); ?></h2>
		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th><label for="tm_card_radius"><?php esc_html_e( 'Card border radius (px)', 'testimonials-manager' ); ?></label></th>
					<td><input type="number" min="0" max="40" name="tm_testimonials_settings[card_radius]" id="tm_card_radius" value="<?php echo esc_attr( $settings['card_radius'] ); ?>" class="small-text" /></td>
				</tr>
				<tr>
					<th><label for="tm_image_size"><?php esc_html_e( 'Customer image size (px)', 'testimonials-manager' ); ?></label></th>
					<td><input type="number" min="24" max="200" name="tm_testimonials_settings[image_size]" id="tm_image_size" value="<?php echo esc_attr( $settings['image_size'] ); ?>" class="small-text" /></td>
				</tr>
				<tr>
					<th><label for="tm_content_alignment"><?php esc_html_e( 'Content alignment', 'testimonials-manager' ); ?></label></th>
					<td>
						<select name="tm_testimonials_settings[content_alignment]" id="tm_content_alignment">
							<option value="left" <?php selected( $settings['content_alignment'], 'left' ); ?>><?php esc_html_e( 'Left', 'testimonials-manager' ); ?></option>
							<option value="center" <?php selected( $settings['content_alignment'], 'center' ); ?>><?php esc_html_e( 'Center', 'testimonials-manager' ); ?></option>
						</select>
					</td>
				</tr>
			</tbody>
		</table>

		<h2 class="title"><?php esc_html_e( 'Import defaults', 'testimonials-manager' ); ?></h2>
		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th><label for="tm_import_default_status"><?php esc_html_e( 'Default publishing status', 'testimonials-manager' ); ?></label></th>
					<td>
						<select name="tm_testimonials_settings[import_default_status]" id="tm_import_default_status">
							<option value="publish" <?php selected( $settings['import_default_status'], 'publish' ); ?>><?php esc_html_e( 'Published', 'testimonials-manager' ); ?></option>
							<option value="draft" <?php selected( $settings['import_default_status'], 'draft' ); ?>><?php esc_html_e( 'Draft', 'testimonials-manager' ); ?></option>
							<option value="pending" <?php selected( $settings['import_default_status'], 'pending' ); ?>><?php esc_html_e( 'Pending Review', 'testimonials-manager' ); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th><label for="tm_import_default_featured"><?php esc_html_e( 'Default featured handling', 'testimonials-manager' ); ?></label></th>
					<td>
						<select name="tm_testimonials_settings[import_default_featured]" id="tm_import_default_featured">
							<option value="spreadsheet" <?php selected( $settings['import_default_featured'], 'spreadsheet' ); ?>><?php esc_html_e( 'Use spreadsheet value', 'testimonials-manager' ); ?></option>
							<option value="all" <?php selected( $settings['import_default_featured'], 'all' ); ?>><?php esc_html_e( 'All featured', 'testimonials-manager' ); ?></option>
							<option value="none" <?php selected( $settings['import_default_featured'], 'none' ); ?>><?php esc_html_e( 'None featured', 'testimonials-manager' ); ?></option>
						</select>
					</td>
				</tr>
			</tbody>
		</table>

		<h2 class="title"><?php esc_html_e( 'Data', 'testimonials-manager' ); ?></h2>
		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th><?php esc_html_e( 'On uninstall', 'testimonials-manager' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="tm_testimonials_settings[delete_data_on_uninstall]" value="1" <?php checked( $settings['delete_data_on_uninstall'], 1 ); ?> />
							<?php esc_html_e( 'Delete all testimonials, categories, and settings when this plugin is deleted', 'testimonials-manager' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'Leave unchecked to keep your testimonials safe if you ever remove the plugin.', 'testimonials-manager' ); ?></p>
					</td>
				</tr>
			</tbody>
		</table>

		<?php submit_button( __( 'Save Settings', 'testimonials-manager' ) ); ?>
	</form>
</div>
