<?php
/**
 * Admin view: Testimonials → Import.
 *
 * Expected variable: $categories (array of WP_Term).
 *
 * @package Testimonials_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap tm-wrap">
	<h1><?php esc_html_e( 'Import Testimonials', 'testimonials-manager' ); ?></h1>
	<p class="description"><?php esc_html_e( 'Import testimonials in bulk from a CSV or XLSX spreadsheet. Large files are processed in the background in small batches, so imports of thousands of rows will not time out.', 'testimonials-manager' ); ?></p>

	<div id="tm-import-app" class="tm-import-app" data-nonce="<?php echo esc_attr( wp_create_nonce( 'tm_import_nonce' ) ); ?>">

		<ol class="tm-import-steps" aria-hidden="true">
			<li class="tm-step is-active" data-step="upload"><?php esc_html_e( '1. Upload', 'testimonials-manager' ); ?></li>
			<li class="tm-step" data-step="mapping"><?php esc_html_e( '2. Preview & Map Columns', 'testimonials-manager' ); ?></li>
			<li class="tm-step" data-step="options"><?php esc_html_e( '3. Import Options', 'testimonials-manager' ); ?></li>
			<li class="tm-step" data-step="progress"><?php esc_html_e( '4. Import', 'testimonials-manager' ); ?></li>
		</ol>

		<!-- STEP 1: Upload -->
		<section class="tm-import-panel" data-panel="upload">
			<div class="tm-card">
				<h2><?php esc_html_e( 'Upload a spreadsheet', 'testimonials-manager' ); ?></h2>
				<p><?php esc_html_e( 'Supported formats: CSV, XLSX. Maximum file size 20MB.', 'testimonials-manager' ); ?></p>

				<div class="tm-dropzone" id="tm-dropzone" tabindex="0" role="button" aria-label="<?php esc_attr_e( 'Choose a spreadsheet file to upload', 'testimonials-manager' ); ?>">
					<p><?php esc_html_e( 'Drag and drop a file here, or click to choose a file', 'testimonials-manager' ); ?></p>
					<input type="file" id="tm-file-input" accept=".csv,.xlsx" class="screen-reader-text" />
				</div>

				<p id="tm-selected-file" class="tm-selected-file" aria-live="polite"></p>
				<p id="tm-upload-error" class="tm-error-text" role="alert"></p>

				<button type="button" class="button button-primary" id="tm-upload-btn" disabled>
					<?php esc_html_e( 'Upload & Preview', 'testimonials-manager' ); ?>
				</button>
				<span class="spinner tm-spinner" id="tm-upload-spinner"></span>
			</div>

			<div class="tm-card tm-card-secondary">
				<h2><?php esc_html_e( 'No spreadsheet yet?', 'testimonials-manager' ); ?></h2>
				<p><?php esc_html_e( 'Generate a small set of demo testimonials to explore the shortcodes and layouts. Demo entries are clearly tagged and can be bulk-deleted at any time.', 'testimonials-manager' ); ?></p>
				<button type="button" class="button" id="tm-generate-demo"><?php esc_html_e( 'Generate Demo Testimonials', 'testimonials-manager' ); ?></button>
				<p id="tm-demo-result" class="tm-demo-result" aria-live="polite"></p>
			</div>
		</section>

		<!-- STEP 2: Preview + Mapping -->
		<section class="tm-import-panel" data-panel="mapping" hidden>
			<div class="tm-card">
				<h2><?php esc_html_e( 'Preview & map columns', 'testimonials-manager' ); ?></h2>
				<p id="tm-mapping-summary"></p>
				<div class="tm-table-scroll">
					<table class="widefat tm-mapping-table" id="tm-mapping-table">
						<thead>
							<tr id="tm-mapping-header-row"></tr>
							<tr id="tm-mapping-select-row"></tr>
						</thead>
						<tbody id="tm-mapping-preview-body"></tbody>
					</table>
				</div>
				<p class="tm-error-text" id="tm-mapping-error" role="alert"></p>
				<p>
					<button type="button" class="button" id="tm-mapping-back"><?php esc_html_e( 'Back', 'testimonials-manager' ); ?></button>
					<button type="button" class="button button-primary" id="tm-mapping-next"><?php esc_html_e( 'Continue', 'testimonials-manager' ); ?></button>
				</p>
			</div>
		</section>

		<!-- STEP 3: Options -->
		<section class="tm-import-panel" data-panel="options" hidden>
			<div class="tm-card">
				<h2><?php esc_html_e( 'Import options', 'testimonials-manager' ); ?></h2>

				<table class="form-table">
					<tbody>
						<tr>
							<th><?php esc_html_e( 'Duplicate handling', 'testimonials-manager' ); ?></th>
							<td>
								<label><input type="radio" name="tm_duplicate_handling" value="skip" checked /> <?php esc_html_e( 'Skip duplicates', 'testimonials-manager' ); ?></label><br />
								<label><input type="radio" name="tm_duplicate_handling" value="update" /> <?php esc_html_e( 'Update existing testimonials', 'testimonials-manager' ); ?></label><br />
								<label><input type="radio" name="tm_duplicate_handling" value="import" /> <?php esc_html_e( 'Import duplicates anyway', 'testimonials-manager' ); ?></label>
								<p class="description"><?php esc_html_e( 'Duplicates are detected by comparing customer name and testimonial content, not the post title.', 'testimonials-manager' ); ?></p>
							</td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Publishing status', 'testimonials-manager' ); ?></th>
							<td>
								<select name="tm_status" id="tm_status">
									<option value="publish" <?php selected( TM_Settings::get_value( 'import_default_status' ), 'publish' ); ?>><?php esc_html_e( 'Published', 'testimonials-manager' ); ?></option>
									<option value="draft" <?php selected( TM_Settings::get_value( 'import_default_status' ), 'draft' ); ?>><?php esc_html_e( 'Draft', 'testimonials-manager' ); ?></option>
									<option value="pending" <?php selected( TM_Settings::get_value( 'import_default_status' ), 'pending' ); ?>><?php esc_html_e( 'Pending Review', 'testimonials-manager' ); ?></option>
								</select>
							</td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Category', 'testimonials-manager' ); ?></th>
							<td>
								<select name="tm_category_id" id="tm_category_id">
									<option value="0"><?php esc_html_e( '— None —', 'testimonials-manager' ); ?></option>
									<?php foreach ( $categories as $category ) : ?>
										<option value="<?php echo esc_attr( $category->term_id ); ?>"><?php echo esc_html( $category->name ); ?></option>
									<?php endforeach; ?>
								</select>
								<p class="description"><?php esc_html_e( 'Applied to every imported row that does not already specify a category column. Used alongside a mapped spreadsheet Category column if present.', 'testimonials-manager' ); ?></p>
							</td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Featured', 'testimonials-manager' ); ?></th>
							<td>
								<select name="tm_featured_mode" id="tm_featured_mode">
									<option value="spreadsheet" <?php selected( TM_Settings::get_value( 'import_default_featured' ), 'spreadsheet' ); ?>><?php esc_html_e( 'Use spreadsheet value', 'testimonials-manager' ); ?></option>
									<option value="all" <?php selected( TM_Settings::get_value( 'import_default_featured' ), 'all' ); ?>><?php esc_html_e( 'Mark all imported as Featured', 'testimonials-manager' ); ?></option>
									<option value="none" <?php selected( TM_Settings::get_value( 'import_default_featured' ), 'none' ); ?>><?php esc_html_e( 'None featured', 'testimonials-manager' ); ?></option>
								</select>
							</td>
						</tr>
					</tbody>
				</table>

				<p>
					<button type="button" class="button" id="tm-options-back"><?php esc_html_e( 'Back', 'testimonials-manager' ); ?></button>
					<button type="button" class="button button-primary" id="tm-start-import"><?php esc_html_e( 'Start Import', 'testimonials-manager' ); ?></button>
				</p>
			</div>
		</section>

		<!-- STEP 4: Progress -->
		<section class="tm-import-panel" data-panel="progress" hidden>
			<div class="tm-card">
				<h2 id="tm-progress-title"><?php esc_html_e( 'Importing testimonials…', 'testimonials-manager' ); ?></h2>

				<div class="tm-progress-bar-track" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" id="tm-progress-bar-wrap">
					<div class="tm-progress-bar-fill" id="tm-progress-bar-fill" style="width:0%;"></div>
				</div>
				<p id="tm-progress-text" aria-live="polite"></p>

				<div id="tm-progress-actions">
					<button type="button" class="button" id="tm-cancel-import"><?php esc_html_e( 'Cancel Import', 'testimonials-manager' ); ?></button>
				</div>

				<div id="tm-import-summary" hidden>
					<h3><?php esc_html_e( 'Import completed', 'testimonials-manager' ); ?></h3>
					<ul class="tm-summary-list">
						<li><?php esc_html_e( 'Imported:', 'testimonials-manager' ); ?> <strong id="tm-count-imported">0</strong></li>
						<li><?php esc_html_e( 'Updated:', 'testimonials-manager' ); ?> <strong id="tm-count-updated">0</strong></li>
						<li><?php esc_html_e( 'Skipped:', 'testimonials-manager' ); ?> <strong id="tm-count-skipped">0</strong></li>
						<li><?php esc_html_e( 'Failed:', 'testimonials-manager' ); ?> <strong id="tm-count-failed">0</strong></li>
					</ul>

					<div id="tm-error-report" hidden>
						<h4><?php esc_html_e( 'Row warnings/errors', 'testimonials-manager' ); ?></h4>
						<textarea id="tm-error-textarea" class="widefat" rows="8" readonly></textarea>
					</div>

					<p>
						<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=testimonial' ) ); ?>" class="button button-primary"><?php esc_html_e( 'View All Testimonials', 'testimonials-manager' ); ?></a>
						<button type="button" class="button" id="tm-import-another"><?php esc_html_e( 'Import Another File', 'testimonials-manager' ); ?></button>
					</p>
				</div>
			</div>
		</section>
	</div>
</div>
