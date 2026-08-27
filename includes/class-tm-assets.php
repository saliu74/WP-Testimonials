<?php
/**
 * Conditional asset loading for the front end and admin screens.
 *
 * @package Testimonials_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class TM_Assets
 *
 * Performance principle: never load carousel JS on a page that only shows
 * the grid or list layout, and never load any plugin CSS/JS on a page that
 * doesn't use the shortcode/block at all.
 */
class TM_Assets {

	/**
	 * Singleton instance (shortcodes/blocks call mark_shortcode_used()
	 * during content parsing, so this needs to be reachable statically).
	 *
	 * @var TM_Assets|null
	 */
	private static $instance = null;

	/**
	 * Layouts detected as "in use" on the current request.
	 *
	 * @var array
	 */
	private $layouts_used = array();

	/**
	 * Whether base testimonial styles are needed at all.
	 *
	 * @var bool
	 */
	private $needs_base = false;

	/**
	 * Constructor.
	 */
	public function __construct() {
		self::$instance = $this;

		add_action( 'wp', array( $this, 'scan_content_for_shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend' ), 20 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin' ) );
	}

	/**
	 * Get the running instance.
	 *
	 * @return TM_Assets
	 */
	public static function instance() {
		return self::$instance;
	}

	/**
	 * Proactively scan the queried post's content for the [testimonials]
	 * shortcode so we can decide, before wp_enqueue_scripts fires, whether
	 * to load assets — without relying on the shortcode having executed yet
	 * (some themes / page builders render content earlier or later).
	 */
	public function scan_content_for_shortcode() {
		if ( ! is_singular() ) {
			return;
		}

		$post = get_queried_object();
		if ( ! $post instanceof WP_Post ) {
			return;
		}

		if ( ! has_shortcode( $post->post_content, 'testimonials' ) && ! has_block( 'testimonials-manager/testimonials', $post ) ) {
			return;
		}

		$this->needs_base = true;

		// Best-effort layout sniff from the raw content so we can skip the
		// carousel JS bundle when only grid/list shortcodes are present.
		if ( false !== strpos( $post->post_content, 'carousel' ) || has_block( 'testimonials-manager/testimonials', $post ) ) {
			$this->layouts_used['carousel'] = true;
		}
		if ( false === strpos( $post->post_content, 'layout' ) ) {
			// No explicit layout attr anywhere -> could be the default, so
			// be safe and treat all layouts as potentially used.
			$this->layouts_used['grid']     = true;
			$this->layouts_used['carousel'] = true;
			$this->layouts_used['list']     = true;
		} else {
			if ( false !== strpos( $post->post_content, 'grid' ) ) {
				$this->layouts_used['grid'] = true;
			}
			if ( false !== strpos( $post->post_content, 'list' ) ) {
				$this->layouts_used['list'] = true;
			}
		}
	}

	/**
	 * Called by TM_Shortcodes::render() at the moment a shortcode actually
	 * executes — the authoritative signal, used as a fallback for content
	 * injected by widgets, page builders, or dynamic sources that the
	 * static content scan above cannot see.
	 *
	 * @param string $layout Layout used ('grid', 'carousel', 'list').
	 */
	public function mark_shortcode_used( $layout ) {
		$this->needs_base = true;
		$this->layouts_used[ $layout ] = true;

		// If we're past wp_enqueue_scripts already (e.g. widget rendered
		// during the_content late in the template), enqueue directly now.
		if ( did_action( 'wp_enqueue_scripts' ) ) {
			$this->enqueue_frontend();
		}
	}

	/**
	 * Enqueue front-end CSS/JS, conditionally based on detected usage.
	 */
	public function enqueue_frontend() {
		if ( ! $this->needs_base ) {
			return;
		}

		wp_enqueue_style( 'tm-testimonials', TM_PLUGIN_URL . 'public/css/testimonials.css', array(), TM_VERSION );

		if ( ! empty( $this->layouts_used['grid'] ) ) {
			wp_enqueue_style( 'tm-grid', TM_PLUGIN_URL . 'public/css/grid.css', array( 'tm-testimonials' ), TM_VERSION );
		}

		if ( ! empty( $this->layouts_used['carousel'] ) ) {
			wp_enqueue_style( 'tm-carousel', TM_PLUGIN_URL . 'public/css/carousel.css', array( 'tm-testimonials' ), TM_VERSION );
		}

		wp_enqueue_script( 'tm-testimonials', TM_PLUGIN_URL . 'public/js/testimonials.js', array(), TM_VERSION, true );

		wp_localize_script(
			'tm-testimonials',
			'tmTestimonials',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'tm_frontend_nonce' ),
				'i18n'    => array(
					'prev'    => __( 'Previous testimonial', 'testimonials-manager' ),
					'next'    => __( 'Next testimonial', 'testimonials-manager' ),
					'pause'   => __( 'Pause autoplay', 'testimonials-manager' ),
					'play'    => __( 'Play autoplay', 'testimonials-manager' ),
					'goTo'    => __( 'Go to slide %d', 'testimonials-manager' ),
					'loading' => __( 'Loading testimonials…', 'testimonials-manager' ),
				),
			)
		);
	}

	/**
	 * Enqueue admin CSS/JS only on plugin screens.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_admin( $hook ) {
		$screen = get_current_screen();

		$is_testimonial_screen = $screen && ( TM_Post_Type::POST_TYPE === $screen->post_type );
		$is_plugin_page        = isset( $_GET['page'] ) && 0 === strpos( sanitize_text_field( wp_unslash( $_GET['page'] ) ), 'tm-testimonials' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( ! $is_testimonial_screen && ! $is_plugin_page ) {
			return;
		}

		wp_enqueue_style( 'tm-admin', TM_PLUGIN_URL . 'admin/css/admin.css', array(), TM_VERSION );
		wp_enqueue_media();
		wp_enqueue_script( 'tm-admin', TM_PLUGIN_URL . 'admin/js/admin.js', array( 'jquery', 'wp-util' ), TM_VERSION, true );

		wp_localize_script(
			'tm-admin',
			'tmAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'tm_admin_nonce' ),
				'i18n'    => array(
					'chooseImage'    => __( 'Choose Customer Image', 'testimonials-manager' ),
					'useImage'       => __( 'Use this image', 'testimonials-manager' ),
					'uploading'      => __( 'Uploading…', 'testimonials-manager' ),
					'mappingRequired' => __( 'Please map at least Customer Name and Testimonial Content before continuing.', 'testimonials-manager' ),
					'confirmCancel'  => __( 'Cancel this import? No testimonials will be imported.', 'testimonials-manager' ),
				),
			)
		);
	}
}
