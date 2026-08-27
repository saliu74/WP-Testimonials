<?php
/**
 * WordPress admin menu and page dispatch.
 *
 * @package Testimonials_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class TM_Admin
 */
class TM_Admin {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_filter( 'plugin_action_links_' . TM_PLUGIN_BASENAME, array( $this, 'add_settings_link' ) );
		add_action( 'admin_notices', array( $this, 'maybe_show_settings_notice' ) );
	}

	/**
	 * Register the Testimonials admin menu and its submenus. WordPress
	 * automatically adds "All Testimonials" and "Add New" for the CPT and
	 * "Categories" for the taxonomy; we only add our two custom pages.
	 */
	public function register_menu() {
		add_submenu_page(
			'edit.php?post_type=' . TM_Post_Type::POST_TYPE,
			__( 'Import Testimonials', 'testimonials-manager' ),
			__( 'Import', 'testimonials-manager' ),
			'manage_options',
			'tm-testimonials-import',
			array( $this, 'render_import_page' )
		);

		add_submenu_page(
			'edit.php?post_type=' . TM_Post_Type::POST_TYPE,
			__( 'Testimonials Shortcodes', 'testimonials-manager' ),
			__( 'Shortcodes', 'testimonials-manager' ),
			'edit_posts',
			'tm-testimonials-shortcodes',
			array( $this, 'render_shortcodes_page' )
		);

		add_submenu_page(
			'edit.php?post_type=' . TM_Post_Type::POST_TYPE,
			__( 'Testimonials Settings', 'testimonials-manager' ),
			__( 'Settings', 'testimonials-manager' ),
			'manage_options',
			'tm-testimonials-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Add a "Settings" link on the Plugins screen.
	 *
	 * @param array $links Existing action links.
	 * @return array
	 */
	public function add_settings_link( $links ) {
		$settings_link = '<a href="' . esc_url( admin_url( 'edit.php?post_type=testimonial&page=tm-testimonials-settings' ) ) . '">' . esc_html__( 'Settings', 'testimonials-manager' ) . '</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}

	/**
	 * Render the Shortcodes reference page — ready-to-copy examples built
	 * from the site's actual categories and current testimonial count, so
	 * the examples are immediately usable rather than generic.
	 */
	public function render_shortcodes_page() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'testimonials-manager' ) );
		}

		$categories = get_terms( array( 'taxonomy' => 'testimonial_category', 'hide_empty' => false ) );
		if ( is_wp_error( $categories ) ) {
			$categories = array();
		}

		$counts       = wp_count_posts( TM_Post_Type::POST_TYPE );
		$published    = isset( $counts->publish ) ? (int) $counts->publish : 0;

		include TM_PLUGIN_DIR . 'admin/views/shortcodes.php';
	}

	/**
	 * Render the Import admin page.
	 */
	public function render_import_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'testimonials-manager' ) );
		}

		$categories = get_terms( array( 'taxonomy' => 'testimonial_category', 'hide_empty' => false ) );
		if ( is_wp_error( $categories ) ) {
			$categories = array();
		}

		include TM_PLUGIN_DIR . 'admin/views/import.php';
	}

	/**
	 * Render the Settings admin page.
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'testimonials-manager' ) );
		}

		if ( isset( $_POST['tm_settings_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tm_settings_nonce'] ) ), 'tm_save_settings' ) ) {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'You do not have permission to do this.', 'testimonials-manager' ) );
			}

			$raw_input = isset( $_POST['tm_testimonials_settings'] ) ? wp_unslash( $_POST['tm_testimonials_settings'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			$settings_obj = new TM_Settings();
			$clean = $settings_obj->sanitize( is_array( $raw_input ) ? $raw_input : array() );
			update_option( TM_Settings::OPTION_KEY, $clean );

			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved.', 'testimonials-manager' ) . '</p></div>';
		}

		$settings   = TM_Settings::get();
		$categories = get_terms( array( 'taxonomy' => 'testimonial_category', 'hide_empty' => false ) );
		if ( is_wp_error( $categories ) ) {
			$categories = array();
		}

		include TM_PLUGIN_DIR . 'admin/views/settings.php';
	}

	/**
	 * A dismissible admin notice on the All Testimonials screen. Before
	 * any testimonials exist it nudges toward creating/importing content;
	 * once testimonials exist, it instead points to the Shortcodes page —
	 * the plugin has no other UI surface that tells someone how to
	 * actually display what they've created.
	 */
	public function maybe_show_settings_notice() {
		$screen = get_current_screen();
		if ( ! $screen || 'edit-' . TM_Post_Type::POST_TYPE !== $screen->id ) {
			return;
		}

		if ( get_option( 'tm_dismissed_welcome_notice' ) ) {
			return;
		}

		$count = wp_count_posts( TM_Post_Type::POST_TYPE );
		$total = isset( $count->publish ) ? (int) $count->publish : 0;
		$total += isset( $count->draft ) ? (int) $count->draft : 0;

		$shortcodes_url = admin_url( 'edit.php?post_type=testimonial&page=tm-testimonials-shortcodes' );
		?>
		<div class="notice notice-info">
			<p>
				<?php if ( $total > 0 ) : ?>
					<?php
					printf(
						/* translators: %s: link to the Shortcodes page */
						wp_kses_post( __( 'Ready to display your testimonials? Grab a ready-to-use shortcode from the %s page and paste it into any page or post.', 'testimonials-manager' ) ),
						'<a href="' . esc_url( $shortcodes_url ) . '">' . esc_html__( 'Shortcodes', 'testimonials-manager' ) . '</a>'
					);
					?>
				<?php else : ?>
					<?php
					printf(
						/* translators: %s: link to the Import page */
						wp_kses_post( __( 'Welcome to Testimonials Manager! Add your first testimonial manually, or %s from a spreadsheet.', 'testimonials-manager' ) ),
						'<a href="' . esc_url( admin_url( 'edit.php?post_type=testimonial&page=tm-testimonials-import' ) ) . '">' . esc_html__( 'import in bulk', 'testimonials-manager' ) . '</a>'
					);
					?>
				<?php endif; ?>
			</p>
		</div>
		<?php
	}
}
