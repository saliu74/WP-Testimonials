<?php
/**
 * Meta boxes for the testimonial edit screen.
 *
 * @package Testimonials_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class TM_Meta_Boxes
 *
 * Registers and saves the custom fields for a testimonial: customer name,
 * title, company, location, email, website, rating, category-adjacent
 * featured flag, and date override.
 */
class TM_Meta_Boxes {

	const NONCE_ACTION = 'tm_save_testimonial_meta';
	const NONCE_NAME   = 'tm_testimonial_meta_nonce';

	/**
	 * Fields saved as post meta. Key => sanitize callback.
	 *
	 * @var array
	 */
	private $fields;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->fields = array(
			'_tm_customer_name'  => 'sanitize_text_field',
			'_tm_customer_title' => 'sanitize_text_field',
			'_tm_company'        => 'sanitize_text_field',
			'_tm_location'       => 'sanitize_text_field',
			'_tm_email'          => 'sanitize_email',
			'_tm_website'        => 'esc_url_raw',
			'_tm_rating'         => array( $this, 'sanitize_rating' ),
			'_tm_featured'       => array( $this, 'sanitize_checkbox' ),
			'_tm_testimonial_date' => 'sanitize_text_field',
		);

		add_action( 'add_meta_boxes', array( $this, 'register_meta_boxes' ) );
		add_action( 'save_post_' . TM_Post_Type::POST_TYPE, array( $this, 'save' ), 10, 2 );
		add_action( 'add_meta_boxes_' . TM_Post_Type::POST_TYPE, array( $this, 'lower_priority_content_note' ) );
	}

	/**
	 * Placeholder hook (kept for extensibility) — no-op today.
	 */
	public function lower_priority_content_note() {}

	/**
	 * Register meta boxes on the testimonial edit screen.
	 */
	public function register_meta_boxes() {
		add_meta_box(
			'tm_customer_details',
			__( 'Customer Details', 'testimonials-manager' ),
			array( $this, 'render_customer_details' ),
			TM_Post_Type::POST_TYPE,
			'normal',
			'high'
		);

		add_meta_box(
			'tm_rating_featured',
			__( 'Rating & Display', 'testimonials-manager' ),
			array( $this, 'render_rating_featured' ),
			TM_Post_Type::POST_TYPE,
			'side',
			'default'
		);
	}

	/**
	 * Render the customer detail fields (name, title, company, location,
	 * email, website).
	 *
	 * @param WP_Post $post Current post.
	 */
	public function render_customer_details( $post ) {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );

		$name    = get_post_meta( $post->ID, '_tm_customer_name', true );
		$title   = get_post_meta( $post->ID, '_tm_customer_title', true );
		$company = get_post_meta( $post->ID, '_tm_company', true );
		$location = get_post_meta( $post->ID, '_tm_location', true );
		$email   = get_post_meta( $post->ID, '_tm_email', true );
		$website = get_post_meta( $post->ID, '_tm_website', true );
		$date    = get_post_meta( $post->ID, '_tm_testimonial_date', true );
		?>
		<table class="form-table tm-meta-table">
			<tbody>
				<tr>
					<th><label for="tm_customer_name"><?php esc_html_e( 'Customer Name', 'testimonials-manager' ); ?> <span class="tm-required">*</span></label></th>
					<td><input type="text" id="tm_customer_name" name="tm_customer_name" class="regular-text" value="<?php echo esc_attr( $name ); ?>" required /></td>
				</tr>
				<tr>
					<th><label for="tm_customer_title"><?php esc_html_e( 'Customer Title', 'testimonials-manager' ); ?></label></th>
					<td><input type="text" id="tm_customer_title" name="tm_customer_title" class="regular-text" value="<?php echo esc_attr( $title ); ?>" placeholder="<?php esc_attr_e( 'e.g. CEO', 'testimonials-manager' ); ?>" /></td>
				</tr>
				<tr>
					<th><label for="tm_company"><?php esc_html_e( 'Company / Business Name', 'testimonials-manager' ); ?></label></th>
					<td><input type="text" id="tm_company" name="tm_company" class="regular-text" value="<?php echo esc_attr( $company ); ?>" placeholder="<?php esc_attr_e( 'e.g. ABC Limited', 'testimonials-manager' ); ?>" /></td>
				</tr>
				<tr>
					<th><label for="tm_location"><?php esc_html_e( 'Location', 'testimonials-manager' ); ?></label></th>
					<td><input type="text" id="tm_location" name="tm_location" class="regular-text" value="<?php echo esc_attr( $location ); ?>" placeholder="<?php esc_attr_e( 'e.g. Lagos, Nigeria', 'testimonials-manager' ); ?>" /></td>
				</tr>
				<tr>
					<th><label for="tm_email"><?php esc_html_e( 'Email', 'testimonials-manager' ); ?></label></th>
					<td>
						<input type="email" id="tm_email" name="tm_email" class="regular-text" value="<?php echo esc_attr( $email ); ?>" />
						<p class="description"><?php esc_html_e( 'Never displayed on the front end. Stored for your own records only.', 'testimonials-manager' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><label for="tm_website"><?php esc_html_e( 'Website', 'testimonials-manager' ); ?></label></th>
					<td><input type="url" id="tm_website" name="tm_website" class="regular-text" value="<?php echo esc_attr( $website ); ?>" placeholder="https://" /></td>
				</tr>
				<tr>
					<th><label for="tm_testimonial_date"><?php esc_html_e( 'Testimonial Date', 'testimonials-manager' ); ?></label></th>
					<td>
						<input type="date" id="tm_testimonial_date" name="tm_testimonial_date" value="<?php echo esc_attr( $date ); ?>" />
						<p class="description"><?php esc_html_e( 'Optional — defaults to the post publish date if left blank.', 'testimonials-manager' ); ?></p>
					</td>
				</tr>
			</tbody>
		</table>
		<p class="description">
			<?php esc_html_e( 'Use the main content editor above for the testimonial text itself, and the Featured Image box for the customer photo.', 'testimonials-manager' ); ?>
		</p>
		<?php
	}

	/**
	 * Render the rating + featured side meta box.
	 *
	 * @param WP_Post $post Current post.
	 */
	public function render_rating_featured( $post ) {
		$rating   = (int) get_post_meta( $post->ID, '_tm_rating', true );
		$featured = (bool) get_post_meta( $post->ID, '_tm_featured', true );
		?>
		<p>
			<label for="tm_rating"><strong><?php esc_html_e( 'Rating', 'testimonials-manager' ); ?></strong></label><br />
			<fieldset>
				<legend class="screen-reader-text"><?php esc_html_e( 'Star rating, 1 to 5', 'testimonials-manager' ); ?></legend>
				<?php for ( $i = 5; $i >= 1; $i-- ) : ?>
					<label style="display:inline-block;margin-right:6px;">
						<input type="radio" name="tm_rating" value="<?php echo esc_attr( $i ); ?>" <?php checked( $rating, $i ); ?> />
						<?php echo esc_html( $i ); ?>★
					</label>
				<?php endfor; ?>
				<label style="display:inline-block;">
					<input type="radio" name="tm_rating" value="0" <?php checked( $rating, 0 ); ?> />
					<?php esc_html_e( 'None', 'testimonials-manager' ); ?>
				</label>
			</fieldset>
		</p>
		<p>
			<label>
				<input type="checkbox" name="tm_featured" value="1" <?php checked( $featured, true ); ?> />
				<?php esc_html_e( 'Mark as Featured', 'testimonials-manager' ); ?>
			</label>
			<br />
			<span class="description"><?php esc_html_e( 'Featured testimonials can be shown exclusively using [testimonials featured="true"].', 'testimonials-manager' ); ?></span>
		</p>
		<?php
	}

	/**
	 * Sanitize the rating field to an integer between 0 and 5.
	 *
	 * @param mixed $value Raw value.
	 * @return int
	 */
	public function sanitize_rating( $value ) {
		$value = (int) $value;
		return max( 0, min( 5, $value ) );
	}

	/**
	 * Sanitize a checkbox field to '1' or ''.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public function sanitize_checkbox( $value ) {
		return ! empty( $value ) ? '1' : '';
	}

	/**
	 * Save testimonial meta fields with full capability, nonce, and
	 * autosave/revision guards.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 */
	public function save( $post_id, $post ) {
		if ( ! isset( $_POST[ self::NONCE_NAME ] ) || ! wp_verify_nonce( wp_unslash( $_POST[ self::NONCE_NAME ] ), self::NONCE_ACTION ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		foreach ( $this->fields as $meta_key => $sanitizer ) {
			$form_key = ltrim( $meta_key, '_' );

			if ( ! isset( $_POST[ $form_key ] ) ) {
				// Unchecked checkboxes are simply absent from $_POST.
				if ( '_tm_featured' === $meta_key ) {
					update_post_meta( $post_id, $meta_key, '' );
				}
				continue;
			}

			$raw   = wp_unslash( $_POST[ $form_key ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			$clean = is_callable( $sanitizer ) ? call_user_func( $sanitizer, $raw ) : sanitize_text_field( $raw );

			update_post_meta( $post_id, $meta_key, $clean );
		}
	}
}
