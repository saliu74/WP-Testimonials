<?php
/**
 * Spreadsheet import orchestration: upload, preview, column mapping,
 * batch processing, and progress tracking.
 *
 * @package Testimonials_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class TM_Importer
 */
class TM_Importer {

	const NONCE_ACTION   = 'tm_import_nonce';
	const CAPABILITY     = 'manage_options';
	const BATCH_SIZE     = 100;
	const TRANSIENT_TTL  = 6 * HOUR_IN_SECONDS;
	const UPLOAD_SUBDIR  = 'tm-imports';

	/**
	 * Constructor — registers AJAX endpoints. All of them require both a
	 * logged-in administrator (capability check) and a valid nonce.
	 */
	public function __construct() {
		add_action( 'wp_ajax_tm_import_upload', array( $this, 'ajax_upload' ) );
		add_action( 'wp_ajax_tm_import_start', array( $this, 'ajax_start' ) );
		add_action( 'wp_ajax_tm_import_batch', array( $this, 'ajax_process_batch' ) );
		add_action( 'wp_ajax_tm_import_status', array( $this, 'ajax_status' ) );
		add_action( 'wp_ajax_tm_import_cancel', array( $this, 'ajax_cancel' ) );
	}

	/**
	 * Get (and create, if missing) the protected upload directory used to
	 * hold in-progress import files. Files here are named with a random
	 * token, and the directory is locked down with .htaccess/web.config
	 * rules plus an empty index.php, matching WordPress's own conventions
	 * for keeping uploaded working files away from direct web access.
	 *
	 * @return string Absolute path with trailing slash, or '' on failure.
	 */
	private function get_upload_dir() {
		$wp_upload_dir = wp_upload_dir();
		if ( ! empty( $wp_upload_dir['error'] ) ) {
			return '';
		}

		$dir = trailingslashit( $wp_upload_dir['basedir'] ) . self::UPLOAD_SUBDIR;

		if ( ! file_exists( $dir ) ) {
			wp_mkdir_p( $dir );
		}

		$htaccess = $dir . '/.htaccess';
		if ( ! file_exists( $htaccess ) ) {
			file_put_contents( $htaccess, "Require all denied\nDeny from all\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		}

		$index_file = $dir . '/index.php';
		if ( ! file_exists( $index_file ) ) {
			file_put_contents( $index_file, "<?php\n// Silence is golden.\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		}

		return trailingslashit( $dir );
	}

	/**
	 * Common guard for every AJAX endpoint: capability + nonce.
	 *
	 * @return bool
	 */
	private function verify_request() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to do this.', 'testimonials-manager' ) ), 403 );
			return false;
		}

		check_ajax_referer( self::NONCE_ACTION, 'nonce' );

		return true;
	}

	/**
	 * Step 1: handle the file upload, validate it thoroughly, store it in
	 * the protected directory, and return a preview + auto-detected
	 * column mapping.
	 */
	public function ajax_upload() {
		if ( ! $this->verify_request() ) {
			return;
		}

		if ( empty( $_FILES['file'] ) || ! is_array( $_FILES['file'] ) ) {
			wp_send_json_error( array( 'message' => __( 'No file was uploaded.', 'testimonials-manager' ) ) );
		}

		$file = $_FILES['file']; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified above via verify_request().

		if ( UPLOAD_ERR_OK !== $file['error'] ) {
			wp_send_json_error( array( 'message' => __( 'The uploaded file could not be processed.', 'testimonials-manager' ) ) );
		}

		$max_size = apply_filters( 'tm_import_max_file_size', 20 * MB_IN_BYTES );
		if ( $file['size'] > $max_size ) {
			wp_send_json_error( array( 'message' => __( 'The uploaded file is too large.', 'testimonials-manager' ) ) );
		}

		$original_name = sanitize_file_name( $file['name'] );
		$ext           = strtolower( pathinfo( $original_name, PATHINFO_EXTENSION ) );

		if ( ! in_array( $ext, array( 'csv', 'xlsx' ), true ) ) {
			wp_send_json_error( array( 'message' => __( 'The selected file type is not supported. Please upload a CSV or XLSX file.', 'testimonials-manager' ) ) );
		}

		// Never trust the extension alone — verify against WordPress's
		// allowed-mimes list and a real file-content sniff.
		$filetype = wp_check_filetype_and_ext( $file['tmp_name'], $original_name, array(
			'csv'  => 'text/csv',
			'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
		) );

		$valid_csv_mimes  = array( 'text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel' );
		$valid_xlsx_mimes = array( 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip' );

		$mime_ok = ( 'csv' === $ext && in_array( $filetype['type'], $valid_csv_mimes, true ) )
			|| ( 'xlsx' === $ext && in_array( $filetype['type'], $valid_xlsx_mimes, true ) );

		// wp_check_filetype_and_ext() can return an empty type for CSV on
		// some servers with an incomplete fileinfo database; fall back to
		// a raw finfo sniff so legitimate CSV uploads are not blocked.
		if ( ! $mime_ok && function_exists( 'finfo_open' ) ) {
			$finfo        = finfo_open( FILEINFO_MIME_TYPE );
			$detected     = finfo_file( $finfo, $file['tmp_name'] );
			finfo_close( $finfo );
			$mime_ok = ( 'csv' === $ext && in_array( $detected, array_merge( $valid_csv_mimes, array( 'text/x-csv' ) ), true ) )
				|| ( 'xlsx' === $ext && in_array( $detected, $valid_xlsx_mimes, true ) );
		}

		if ( ! $mime_ok ) {
			wp_send_json_error( array( 'message' => __( 'The uploaded file does not appear to be a valid CSV or XLSX file.', 'testimonials-manager' ) ) );
		}

		$dir = $this->get_upload_dir();
		if ( ! $dir ) {
			wp_send_json_error( array( 'message' => __( 'The server could not create a secure upload location. Please check folder permissions.', 'testimonials-manager' ) ) );
		}

		$import_id   = wp_generate_password( 20, false, false );
		$stored_name = $import_id . '.' . $ext;
		$destination = $dir . $stored_name;

		if ( ! @move_uploaded_file( $file['tmp_name'], $destination ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_system_operations_move_uploaded_file
			wp_send_json_error( array( 'message' => __( 'The uploaded file could not be saved on the server.', 'testimonials-manager' ) ) );
		}

		// Build the preview + auto-detected mapping.
		if ( 'csv' === $ext ) {
			$preview     = TM_CSV_Reader::preview( $destination, 20 );
			$total_rows  = TM_CSV_Reader::count_rows( $destination );
			$headers     = $preview['headers'];
			$sample_rows = $preview['rows'];
		} else {
			$reader = new TM_XLSX_Reader( $destination );
			if ( ! $reader->is_valid() ) {
				@unlink( $destination ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.unlink_unlink
				wp_send_json_error( array( 'message' => __( 'The XLSX file could not be read. It may be corrupted or password protected.', 'testimonials-manager' ) ) );
			}
			$all_rows = $reader->read_all_rows();
			$reader->close();

			$headers     = array_shift( $all_rows );
			$total_rows  = count( $all_rows );
			$sample_rows = array_slice( $all_rows, 0, 20 );
		}

		if ( empty( $headers ) || 0 === $total_rows ) {
			@unlink( $destination ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.unlink_unlink
			wp_send_json_error( array( 'message' => __( 'The spreadsheet does not contain any valid rows.', 'testimonials-manager' ) ) );
		}

		$mapping_guess = TM_Import_Validator::auto_detect_mapping( $headers );

		set_transient(
			'tm_import_' . $import_id,
			array(
				'file_path'   => $destination,
				'ext'         => $ext,
				'headers'     => $headers,
				'total_rows'  => $total_rows,
				'offset'      => 0,
				'status'      => 'pending', // pending -> running -> complete|cancelled.
				'counts'      => array(
					'imported' => 0,
					'updated'  => 0,
					'skipped'  => 0,
					'failed'   => 0,
				),
				'errors'      => array(),
				'created_by'  => get_current_user_id(),
				'original_name' => $original_name,
			),
			self::TRANSIENT_TTL
		);

		wp_send_json_success(
			array(
				'import_id'      => $import_id,
				'headers'        => $headers,
				'sample_rows'    => $sample_rows,
				'total_rows'     => $total_rows,
				'mapping_guess'  => $mapping_guess,
				'destination_fields' => TM_Import_Validator::destination_fields(),
			)
		);
	}

	/**
	 * Step 2: administrator confirms column mapping + import options.
	 * We store both in the transient so every subsequent batch call is
	 * authoritative and can't be tampered with from the browser.
	 */
	public function ajax_start() {
		if ( ! $this->verify_request() ) {
			return;
		}

		$import_id = isset( $_POST['import_id'] ) ? sanitize_text_field( wp_unslash( $_POST['import_id'] ) ) : '';
		$state     = $import_id ? get_transient( 'tm_import_' . $import_id ) : false;

		if ( ! $state ) {
			wp_send_json_error( array( 'message' => __( 'This import session has expired. Please upload the file again.', 'testimonials-manager' ) ) );
		}

		if ( 'pending' !== $state['status'] ) {
			// Already started (e.g. duplicate click / page refresh) — just
			// report current progress instead of restarting from scratch.
			wp_send_json_success( $this->build_status_payload( $state ) );
		}

		$mapping_raw = isset( $_POST['mapping'] ) ? wp_unslash( $_POST['mapping'] ) : '{}'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$mapping     = json_decode( $mapping_raw, true );

		if ( ! is_array( $mapping ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid column mapping.', 'testimonials-manager' ) ) );
		}

		// Sanitize mapping: column index (string int) => destination field key.
		$valid_fields   = array_keys( TM_Import_Validator::destination_fields() );
		$clean_mapping  = array();
		$has_name       = false;
		$has_content    = false;

		foreach ( $mapping as $col_index => $field_key ) {
			$col_index = (int) $col_index;
			$field_key = sanitize_key( $field_key );

			if ( '' === $field_key ) {
				continue;
			}
			if ( ! in_array( $field_key, $valid_fields, true ) ) {
				continue;
			}

			$clean_mapping[ $col_index ] = $field_key;

			if ( 'customer_name' === $field_key ) {
				$has_name = true;
			}
			if ( 'content' === $field_key ) {
				$has_content = true;
			}
		}

		if ( ! $has_name || ! $has_content ) {
			wp_send_json_error( array( 'message' => __( 'Please map at least Customer Name and Testimonial Content before continuing.', 'testimonials-manager' ) ) );
		}

		$options = array(
			'duplicate_handling' => isset( $_POST['duplicate_handling'] ) && in_array( $_POST['duplicate_handling'], array( 'skip', 'import', 'update' ), true )
				? sanitize_key( $_POST['duplicate_handling'] )
				: 'skip',
			'status' => isset( $_POST['status'] ) && in_array( $_POST['status'], array( 'publish', 'draft', 'pending' ), true )
				? sanitize_key( $_POST['status'] )
				: TM_Settings::get_value( 'import_default_status', 'publish' ),
			'category_id' => isset( $_POST['category_id'] ) ? (int) $_POST['category_id'] : 0,
			'featured_mode' => isset( $_POST['featured_mode'] ) && in_array( $_POST['featured_mode'], array( 'all', 'none', 'spreadsheet' ), true )
				? sanitize_key( $_POST['featured_mode'] )
				: TM_Settings::get_value( 'import_default_featured', 'spreadsheet' ),
		);

		$state['mapping'] = $clean_mapping;
		$state['options'] = $options;
		$state['status']  = 'running';

		set_transient( 'tm_import_' . $import_id, $state, self::TRANSIENT_TTL );

		wp_send_json_success( $this->build_status_payload( $state ) );
	}

	/**
	 * Step 3: process the next batch of rows. The server — not the
	 * browser — is authoritative about which offset to process next, so a
	 * page refresh, double submission, or lost connection can never cause
	 * the same rows to be imported twice.
	 */
	public function ajax_process_batch() {
		if ( ! $this->verify_request() ) {
			return;
		}

		$import_id = isset( $_POST['import_id'] ) ? sanitize_text_field( wp_unslash( $_POST['import_id'] ) ) : '';
		$state     = $import_id ? get_transient( 'tm_import_' . $import_id ) : false;

		if ( ! $state ) {
			wp_send_json_error( array( 'message' => __( 'This import session has expired. Please upload the file again.', 'testimonials-manager' ) ) );
		}

		if ( 'cancelled' === $state['status'] ) {
			wp_send_json_error( array( 'message' => __( 'This import was cancelled.', 'testimonials-manager' ) ) );
		}

		if ( 'complete' === $state['status'] ) {
			wp_send_json_success( $this->build_status_payload( $state ) );
		}

		if ( 'running' !== $state['status'] ) {
			wp_send_json_error( array( 'message' => __( 'Please confirm the column mapping before starting the import.', 'testimonials-manager' ) ) );
		}

		$offset = (int) $state['offset'];

		if ( 'csv' === $state['ext'] ) {
			$rows = TM_CSV_Reader::read_batch( $state['file_path'], $offset, self::BATCH_SIZE );
		} else {
			// XLSX has no efficient random-access batch read with our
			// lightweight reader, so we cache the full parsed rows in the
			// transient the first time and slice per batch. This keeps
			// memory bounded to one parse instead of one parse per batch.
			if ( empty( $state['xlsx_rows_cached'] ) ) {
				$reader   = new TM_XLSX_Reader( $state['file_path'] );
				$all_rows = $reader->is_valid() ? $reader->read_all_rows() : array();
				$reader->close();
				array_shift( $all_rows ); // Drop header row.
				set_transient( 'tm_import_rows_' . $import_id, $all_rows, self::TRANSIENT_TTL );
				$state['xlsx_rows_cached'] = true;
			}
			$all_rows = get_transient( 'tm_import_rows_' . $import_id );
			$all_rows = is_array( $all_rows ) ? $all_rows : array();
			$rows     = array_slice( $all_rows, $offset, self::BATCH_SIZE );
		}

		$counts   = $state['counts'];
		$errors   = $state['errors'];
		$mapping  = $state['mapping'];
		$options  = $state['options'];

		foreach ( $rows as $i => $row ) {
			$row_number = $offset + $i + 2; // +1 for 0-index, +1 for header row.

			$mapped_row = array();
			foreach ( $mapping as $col_index => $field_key ) {
				$mapped_row[ $field_key ] = isset( $row[ $col_index ] ) ? $row[ $col_index ] : '';
			}

			$validated = TM_Import_Validator::validate_row( $mapped_row, $row_number );

			if ( $validated['errors'] ) {
				$errors = array_merge( $errors, $validated['errors'] );
			}

			if ( empty( $validated['data']['customer_name'] ) || empty( $validated['data']['content'] ) ) {
				$counts['failed']++;
				continue;
			}

			$result = TM_Import_Processor::process_row( $validated['data'], $options );
			if ( isset( $counts[ $result ] ) ) {
				$counts[ $result ]++;
			}
		}

		$new_offset      = $offset + count( $rows );
		$state['offset'] = $new_offset;
		$state['counts'] = $counts;
		// Cap stored errors so the transient/option table never grows
		// unbounded on a spreadsheet with thousands of bad rows.
		$state['errors'] = array_slice( $errors, 0, 500 );

		if ( $new_offset >= $state['total_rows'] || empty( $rows ) ) {
			$state['status'] = 'complete';
			// Clean up the uploaded file and cached rows once done.
			if ( file_exists( $state['file_path'] ) ) {
				@unlink( $state['file_path'] ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.unlink_unlink
			}
			delete_transient( 'tm_import_rows_' . $import_id );
		}

		set_transient( 'tm_import_' . $import_id, $state, self::TRANSIENT_TTL );

		wp_send_json_success( $this->build_status_payload( $state ) );
	}

	/**
	 * Report the current status of an import — used both by the active
	 * progress bar and to resume displaying progress after a page reload.
	 */
	public function ajax_status() {
		if ( ! $this->verify_request() ) {
			return;
		}

		$import_id = isset( $_POST['import_id'] ) ? sanitize_text_field( wp_unslash( $_POST['import_id'] ) ) : '';
		$state     = $import_id ? get_transient( 'tm_import_' . $import_id ) : false;

		if ( ! $state ) {
			wp_send_json_error( array( 'message' => __( 'This import session has expired.', 'testimonials-manager' ) ) );
		}

		wp_send_json_success( $this->build_status_payload( $state ) );
	}

	/**
	 * Cancel an in-progress import and clean up its uploaded file.
	 */
	public function ajax_cancel() {
		if ( ! $this->verify_request() ) {
			return;
		}

		$import_id = isset( $_POST['import_id'] ) ? sanitize_text_field( wp_unslash( $_POST['import_id'] ) ) : '';
		$state     = $import_id ? get_transient( 'tm_import_' . $import_id ) : false;

		if ( $state ) {
			if ( ! empty( $state['file_path'] ) && file_exists( $state['file_path'] ) ) {
				@unlink( $state['file_path'] ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.unlink_unlink
			}
			$state['status'] = 'cancelled';
			set_transient( 'tm_import_' . $import_id, $state, self::TRANSIENT_TTL );
			delete_transient( 'tm_import_rows_' . $import_id );
		}

		wp_send_json_success( array( 'cancelled' => true ) );
	}

	/**
	 * Build the JSON-serializable progress payload sent to the browser.
	 *
	 * @param array $state Internal transient state.
	 * @return array
	 */
	private function build_status_payload( $state ) {
		$total     = max( 1, (int) $state['total_rows'] );
		$processed = min( $total, (int) $state['offset'] );

		return array(
			'status'     => $state['status'],
			'processed'  => $processed,
			'total'      => $state['total_rows'],
			'percent'    => round( ( $processed / $total ) * 100, 1 ),
			'counts'     => $state['counts'],
			'errors'     => array_slice( $state['errors'], 0, 50 ),
			'error_count' => count( $state['errors'] ),
		);
	}
}
