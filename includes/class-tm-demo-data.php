<?php
/**
 * Optional demo testimonial generator. Never runs automatically — only
 * triggered explicitly by an administrator from Testimonials → Import.
 *
 * @package Testimonials_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class TM_Demo_Data
 */
class TM_Demo_Data {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wp_ajax_tm_generate_demo_data', array( $this, 'ajax_generate' ) );
	}

	/**
	 * Sample content used to generate demo testimonials, clearly tagged so
	 * they're easy to identify and bulk-delete afterwards.
	 *
	 * @return array[]
	 */
	private function sample_data() {
		return array(
			array( 'name' => 'Aisha Bello', 'title' => 'Founder', 'company' => 'Bello & Co', 'location' => 'Lagos, Nigeria', 'rating' => 5, 'content' => '[Demo] The team delivered exactly what we needed, on time and within budget. Communication was excellent throughout the project.' ),
			array( 'name' => 'John Carter', 'title' => 'Operations Manager', 'company' => 'Carter Logistics', 'location' => 'Abuja, Nigeria', 'rating' => 4, 'content' => '[Demo] Very professional service from start to finish. A couple of minor delays, but the end result was worth it.' ),
			array( 'name' => 'Grace Okafor', 'title' => 'CEO', 'company' => 'Okafor Retail', 'location' => 'Minna, Nigeria', 'rating' => 5, 'content' => '[Demo] Outstanding attention to detail. They understood our vision and brought it to life better than we imagined.' ),
			array( 'name' => 'Michael Brown', 'title' => 'Product Manager', 'company' => 'Brown Tech', 'location' => 'Port Harcourt, Nigeria', 'rating' => 4, 'content' => '[Demo] Highly recommended. Responsive support and solid technical execution throughout our engagement.' ),
			array( 'name' => 'Fatima Yusuf', 'title' => 'Marketing Director', 'company' => 'Yusuf Ventures', 'location' => 'Kano, Nigeria', 'rating' => 5, 'content' => '[Demo] A pleasure to work with. They kept us informed at every step and exceeded our expectations.' ),
			array( 'name' => 'David Adeyemi', 'title' => 'Small Business Owner', 'company' => '', 'location' => 'Ibadan, Nigeria', 'rating' => 5, 'content' => '[Demo] Excellent service and professional support from beginning to end. I will definitely be back for future projects.' ),
		);
	}

	/**
	 * Generate a small set of demo testimonials for development/testing.
	 * Requires manage_options + a valid nonce, matching every other admin
	 * AJAX handler in the plugin.
	 */
	public function ajax_generate() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to do this.', 'testimonials-manager' ) ), 403 );
		}

		check_ajax_referer( 'tm_admin_nonce', 'nonce' );

		$created = 0;

		foreach ( $this->sample_data() as $item ) {
			$post_id = wp_insert_post(
				array(
					'post_type'    => TM_Post_Type::POST_TYPE,
					'post_title'   => $item['name'] . ' — ' . wp_trim_words( $item['content'], 6 ),
					'post_content' => $item['content'],
					'post_status'  => 'publish',
				),
				true
			);

			if ( is_wp_error( $post_id ) ) {
				continue;
			}

			update_post_meta( $post_id, '_tm_customer_name', $item['name'] );
			update_post_meta( $post_id, '_tm_customer_title', $item['title'] );
			update_post_meta( $post_id, '_tm_company', $item['company'] );
			update_post_meta( $post_id, '_tm_location', $item['location'] );
			update_post_meta( $post_id, '_tm_rating', $item['rating'] );
			update_post_meta( $post_id, '_tm_demo_content', '1' );

			$created++;
		}

		wp_send_json_success(
			array(
				/* translators: %d: number of demo testimonials created */
				'message' => sprintf( __( '%d demo testimonials created. Look for the "[Demo]" prefix — bulk-select and delete them any time from All Testimonials.', 'testimonials-manager' ), $created ),
				'created' => $created,
			)
		);
	}
}
