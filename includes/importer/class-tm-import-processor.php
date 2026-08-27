<?php
/**
 * Turns a validated, mapped spreadsheet row into a `testimonial` post.
 *
 * @package Testimonials_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class TM_Import_Processor
 */
class TM_Import_Processor {

	/**
	 * Compute a stable duplicate-detection hash from normalized customer
	 * name + testimonial content (not the post title, which admins can
	 * freely rename after import).
	 *
	 * @param string $name    Customer name.
	 * @param string $content Testimonial content.
	 * @return string
	 */
	public static function compute_hash( $name, $content ) {
		$normalize = function ( $text ) {
			$text = wp_strip_all_tags( (string) $text );
			$text = strtolower( $text );
			$text = preg_replace( '/\s+/', ' ', $text );
			return trim( $text );
		};

		return md5( $normalize( $name ) . '|' . $normalize( $content ) );
	}

	/**
	 * Find an existing testimonial post ID by its stored import hash.
	 *
	 * @param string $hash Duplicate-detection hash.
	 * @return int 0 if none found.
	 */
	public static function find_existing_by_hash( $hash ) {
		$existing = get_posts(
			array(
				'post_type'      => TM_Post_Type::POST_TYPE,
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => '_tm_import_hash', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'     => $hash, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'no_found_rows'  => true,
			)
		);

		return $existing ? (int) $existing[0] : 0;
	}

	/**
	 * Process one validated row: insert, update, or skip depending on the
	 * chosen duplicate-handling strategy.
	 *
	 * @param array $row     Validated/sanitized row data (see TM_Import_Validator::validate_row()).
	 * @param array $options {
	 *     Import-wide options.
	 *
	 *     @type string $duplicate_handling 'skip'|'import'|'update'.
	 *     @type string $status             'publish'|'draft'|'pending'.
	 *     @type int    $category_id        Term ID to assign to all rows, or 0.
	 *     @type string $featured_mode      'all'|'none'|'spreadsheet'.
	 * }
	 * @return string One of 'imported', 'updated', 'skipped', 'failed'.
	 */
	public static function process_row( $row, $options ) {
		if ( empty( $row['customer_name'] ) || empty( $row['content'] ) ) {
			return 'failed';
		}

		$hash          = self::compute_hash( $row['customer_name'], $row['content'] );
		$existing_id   = self::find_existing_by_hash( $hash );
		$duplicate_mode = isset( $options['duplicate_handling'] ) ? $options['duplicate_handling'] : 'skip';

		if ( $existing_id && 'skip' === $duplicate_mode ) {
			return 'skipped';
		}

		$post_status = in_array( $options['status'], array( 'publish', 'draft', 'pending' ), true ) ? $options['status'] : 'publish';

		$postarr = array(
			'post_type'    => TM_Post_Type::POST_TYPE,
			'post_title'   => $row['customer_name'] . ' — ' . wp_trim_words( wp_strip_all_tags( $row['content'] ), 6 ),
			'post_content' => $row['content'],
			'post_status'  => $post_status,
		);

		if ( ! empty( $row['date'] ) ) {
			$postarr['post_date']     = $row['date'] . ' 00:00:00';
			$postarr['post_date_gmt'] = get_gmt_from_date( $row['date'] . ' 00:00:00' );
		}

		if ( $existing_id && 'update' === $duplicate_mode ) {
			$postarr['ID'] = $existing_id;
			$post_id       = wp_update_post( $postarr, true );
			$result_status = 'updated';
		} else {
			$post_id       = wp_insert_post( $postarr, true );
			$result_status = 'imported';
		}

		if ( is_wp_error( $post_id ) || ! $post_id ) {
			return 'failed';
		}

		update_post_meta( $post_id, '_tm_import_hash', $hash );
		update_post_meta( $post_id, '_tm_customer_name', $row['customer_name'] );
		update_post_meta( $post_id, '_tm_customer_title', $row['customer_title'] );
		update_post_meta( $post_id, '_tm_company', $row['company'] );
		update_post_meta( $post_id, '_tm_location', $row['location'] );
		update_post_meta( $post_id, '_tm_email', $row['email'] );
		update_post_meta( $post_id, '_tm_website', $row['website'] );
		update_post_meta( $post_id, '_tm_rating', $row['rating'] );

		if ( ! empty( $row['date'] ) ) {
			update_post_meta( $post_id, '_tm_testimonial_date', $row['date'] );
		}

		// Featured status, per the chosen import-wide mode.
		switch ( $options['featured_mode'] ) {
			case 'all':
				update_post_meta( $post_id, '_tm_featured', '1' );
				break;
			case 'none':
				update_post_meta( $post_id, '_tm_featured', '' );
				break;
			case 'spreadsheet':
			default:
				update_post_meta( $post_id, '_tm_featured', $row['featured'] ? '1' : '' );
				break;
		}

		// Category: prefer an explicit spreadsheet value (row-level),
		// otherwise fall back to the single category chosen for the whole
		// import, if any.
		$category_name = ! empty( $row['category'] ) ? $row['category'] : '';

		if ( $category_name ) {
			$term = term_exists( $category_name, 'testimonial_category' );
			if ( ! $term ) {
				$term = wp_insert_term( $category_name, 'testimonial_category' );
			}
			if ( ! is_wp_error( $term ) && isset( $term['term_id'] ) ) {
				wp_set_object_terms( $post_id, (int) $term['term_id'], 'testimonial_category', false );
			}
		} elseif ( ! empty( $options['category_id'] ) ) {
			wp_set_object_terms( $post_id, (int) $options['category_id'], 'testimonial_category', false );
		}

		return $result_status;
	}
}
