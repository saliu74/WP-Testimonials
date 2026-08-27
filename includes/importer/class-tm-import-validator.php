<?php
/**
 * Column auto-detection and row-level validation for the spreadsheet
 * importer.
 *
 * @package Testimonials_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class TM_Import_Validator
 */
class TM_Import_Validator {

	/**
	 * The destination fields the importer understands, and the header
	 * name variants used to auto-detect a spreadsheet column mapping.
	 *
	 * @return array<string, array{label:string, required:bool, synonyms:string[]}>
	 */
	public static function destination_fields() {
		return array(
			'customer_name' => array(
				'label'    => __( 'Customer Name', 'testimonials-manager' ),
				'required' => true,
				'synonyms' => array( 'name', 'customer', 'client', 'customer name', 'client name', 'full name', 'author' ),
			),
			'content'       => array(
				'label'    => __( 'Testimonial Content', 'testimonials-manager' ),
				'required' => true,
				'synonyms' => array( 'testimonial', 'review', 'comment', 'feedback', 'testimonial text', 'review text', 'message', 'quote' ),
			),
			'customer_title' => array(
				'label'    => __( 'Customer Title', 'testimonials-manager' ),
				'required' => false,
				'synonyms' => array( 'position', 'job title', 'title', 'role' ),
			),
			'company'       => array(
				'label'    => __( 'Company', 'testimonials-manager' ),
				'required' => false,
				'synonyms' => array( 'company', 'business', 'organisation', 'organization', 'employer' ),
			),
			'location'      => array(
				'label'    => __( 'Location', 'testimonials-manager' ),
				'required' => false,
				'synonyms' => array( 'location', 'city', 'address', 'place' ),
			),
			'email'         => array(
				'label'    => __( 'Email', 'testimonials-manager' ),
				'required' => false,
				'synonyms' => array( 'email', 'e-mail', 'email address' ),
			),
			'website'       => array(
				'label'    => __( 'Website', 'testimonials-manager' ),
				'required' => false,
				'synonyms' => array( 'website', 'url', 'link', 'site' ),
			),
			'rating'        => array(
				'label'    => __( 'Rating', 'testimonials-manager' ),
				'required' => false,
				'synonyms' => array( 'rating', 'stars', 'score', 'review rating' ),
			),
			'date'          => array(
				'label'    => __( 'Date', 'testimonials-manager' ),
				'required' => false,
				'synonyms' => array( 'date', 'review date', 'submitted', 'submitted date' ),
			),
			'category'      => array(
				'label'    => __( 'Category', 'testimonials-manager' ),
				'required' => false,
				'synonyms' => array( 'category', 'categories', 'type', 'group' ),
			),
			'featured'      => array(
				'label'    => __( 'Featured', 'testimonials-manager' ),
				'required' => false,
				'synonyms' => array( 'featured', 'is featured', 'highlight' ),
			),
		);
	}

	/**
	 * Given the spreadsheet's header row, guess a destination field for
	 * each column. Administrators can always override in the UI.
	 *
	 * @param string[] $headers Header row cell values.
	 * @return array<int,string> Map of column index => destination field key (or '' if unmatched).
	 */
	public static function auto_detect_mapping( $headers ) {
		$fields  = self::destination_fields();
		$mapping = array();
		$used    = array();

		foreach ( $headers as $index => $header ) {
			$normalized = self::normalize_header( $header );
			$mapping[ $index ] = '';

			foreach ( $fields as $field_key => $field ) {
				if ( isset( $used[ $field_key ] ) ) {
					continue;
				}

				foreach ( $field['synonyms'] as $synonym ) {
					if ( self::normalize_header( $synonym ) === $normalized ) {
						$mapping[ $index ]  = $field_key;
						$used[ $field_key ] = true;
						break 2;
					}
				}
			}
		}

		return $mapping;
	}

	/**
	 * Normalize a header string for comparison: lowercase, trimmed,
	 * punctuation/underscore collapsed to a single space.
	 *
	 * @param string $header Raw header text.
	 * @return string
	 */
	private static function normalize_header( $header ) {
		$header = strtolower( trim( (string) $header ) );
		$header = preg_replace( '/[_\-\.]+/', ' ', $header );
		$header = preg_replace( '/\s+/', ' ', $header );
		return trim( $header );
	}

	/**
	 * Validate and sanitize a single mapped row.
	 *
	 * @param array $mapped_row Associative array keyed by destination field.
	 * @param int   $row_number 1-based spreadsheet row number, for error messages.
	 * @return array{data:array, errors:string[]}
	 */
	public static function validate_row( $mapped_row, $row_number ) {
		$errors = array();
		$data   = array();

		$name = isset( $mapped_row['customer_name'] ) ? trim( wp_strip_all_tags( $mapped_row['customer_name'] ) ) : '';
		if ( '' === $name ) {
			$errors[] = sprintf(
				/* translators: %d: spreadsheet row number */
				__( 'Row %d: Customer Name is required.', 'testimonials-manager' ),
				$row_number
			);
		}
		$data['customer_name'] = sanitize_text_field( $name );

		$content = isset( $mapped_row['content'] ) ? trim( $mapped_row['content'] ) : '';
		if ( '' === $content ) {
			$errors[] = sprintf(
				/* translators: %d: spreadsheet row number */
				__( 'Row %d: Testimonial Content is required.', 'testimonials-manager' ),
				$row_number
			);
		}
		$data['content'] = wp_kses_post( $content );

		$data['customer_title'] = isset( $mapped_row['customer_title'] ) ? sanitize_text_field( $mapped_row['customer_title'] ) : '';
		$data['company']        = isset( $mapped_row['company'] ) ? sanitize_text_field( $mapped_row['company'] ) : '';
		$data['location']       = isset( $mapped_row['location'] ) ? sanitize_text_field( $mapped_row['location'] ) : '';

		$email = isset( $mapped_row['email'] ) ? sanitize_email( $mapped_row['email'] ) : '';
		if ( ! empty( $mapped_row['email'] ) && '' === $email ) {
			$errors[] = sprintf(
				/* translators: %d: spreadsheet row number */
				__( 'Row %d: Email address is not valid and was ignored.', 'testimonials-manager' ),
				$row_number
			);
		}
		$data['email'] = $email;

		$website = isset( $mapped_row['website'] ) ? trim( $mapped_row['website'] ) : '';
		if ( $website && ! preg_match( '#^https?://#i', $website ) ) {
			$website = 'https://' . $website;
		}
		$data['website'] = $website ? esc_url_raw( $website ) : '';

		if ( isset( $mapped_row['rating'] ) && '' !== trim( (string) $mapped_row['rating'] ) ) {
			$rating_raw = preg_replace( '/[^0-9.]/', '', (string) $mapped_row['rating'] );
			$rating     = round( (float) $rating_raw );

			if ( $rating < 0 || $rating > 5 ) {
				$errors[] = sprintf(
					/* translators: %d: spreadsheet row number */
					__( 'Row %d: Rating must be between 1 and 5.', 'testimonials-manager' ),
					$row_number
				);
				$rating = max( 0, min( 5, $rating ) );
			}
			$data['rating'] = (int) $rating;
		} else {
			$data['rating'] = 0;
		}

		$data['date'] = '';
		if ( isset( $mapped_row['date'] ) && '' !== trim( (string) $mapped_row['date'] ) ) {
			$timestamp = strtotime( $mapped_row['date'] );
			if ( false === $timestamp ) {
				$errors[] = sprintf(
					/* translators: %d: spreadsheet row number */
					__( 'Row %d: Date could not be understood and was ignored.', 'testimonials-manager' ),
					$row_number
				);
			} else {
				$data['date'] = gmdate( 'Y-m-d', $timestamp );
			}
		}

		$data['category'] = isset( $mapped_row['category'] ) ? sanitize_text_field( $mapped_row['category'] ) : '';

		$featured_raw   = isset( $mapped_row['featured'] ) ? strtolower( trim( (string) $mapped_row['featured'] ) ) : '';
		$data['featured'] = in_array( $featured_raw, array( '1', 'true', 'yes', 'y' ), true );

		return array(
			'data'   => $data,
			'errors' => $errors,
		);
	}
}
