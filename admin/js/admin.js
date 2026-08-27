/**
 * Testimonials Manager — admin script.
 *
 * Drives the Import wizard (upload -> column mapping -> options -> batch
 * progress) using jQuery + the WordPress AJAX API. No build step, no
 * framework dependency — this runs as-is on any WordPress install.
 */
/* global jQuery, tmAdmin */
( function ( $ ) {
	'use strict';

	if ( typeof tmAdmin === 'undefined' ) {
		return;
	}

	$( function () {
		var $app = $( '#tm-import-app' );
		if ( ! $app.length ) {
			return;
		}

		var nonce = $app.data( 'nonce' );
		var state = {
			importId: null,
			mapping: {},
			destinationFields: {},
			headers: [],
			pollTimer: null,
			cancelled: false
		};

		var $fileInput = $( '#tm-file-input' );
		var $dropzone = $( '#tm-dropzone' );
		var $uploadBtn = $( '#tm-upload-btn' );
		var $selectedFile = $( '#tm-selected-file' );
		var $uploadError = $( '#tm-upload-error' );
		var $uploadSpinner = $( '#tm-upload-spinner' );

		var pendingFile = null;

		/**
		 * Move the visible wizard step + panel.
		 */
		function goToStep( stepName ) {
			$( '.tm-step' ).removeClass( 'is-active' );
			$( '.tm-step[data-step="' + stepName + '"]' ).addClass( 'is-active' );
			$( '.tm-import-panel' ).attr( 'hidden', true );
			$( '.tm-import-panel[data-panel="' + stepName + '"]' ).removeAttr( 'hidden' );
		}

		function ajax( action, data ) {
			return $.ajax( {
				url: tmAdmin.ajaxUrl,
				method: 'POST',
				data: $.extend( { action: action, nonce: nonce }, data || {} )
			} );
		}

		/* -------------------------------------------------------------
		 * Step 1: Upload
		 * ----------------------------------------------------------- */

		$dropzone.on( 'click', function () {
			$fileInput.trigger( 'click' );
		} );
		$dropzone.on( 'keydown', function ( e ) {
			if ( 'Enter' === e.key || ' ' === e.key ) {
				e.preventDefault();
				$fileInput.trigger( 'click' );
			}
		} );
		$dropzone.on( 'dragover', function ( e ) {
			e.preventDefault();
			$dropzone.addClass( 'is-dragover' );
		} );
		$dropzone.on( 'dragleave', function () {
			$dropzone.removeClass( 'is-dragover' );
		} );
		$dropzone.on( 'drop', function ( e ) {
			e.preventDefault();
			$dropzone.removeClass( 'is-dragover' );
			var files = e.originalEvent.dataTransfer.files;
			if ( files && files.length ) {
				setSelectedFile( files[ 0 ] );
			}
		} );
		$fileInput.on( 'change', function () {
			if ( this.files && this.files.length ) {
				setSelectedFile( this.files[ 0 ] );
			}
		} );

		function setSelectedFile( file ) {
			var ext = file.name.split( '.' ).pop().toLowerCase();
			$uploadError.text( '' );

			if ( 'csv' !== ext && 'xlsx' !== ext ) {
				$uploadError.text( 'Please choose a CSV or XLSX file.' );
				$uploadBtn.prop( 'disabled', true );
				pendingFile = null;
				return;
			}

			pendingFile = file;
			$selectedFile.text( file.name + ' (' + Math.round( file.size / 1024 ) + ' KB)' );
			$uploadBtn.prop( 'disabled', false );
		}

		$uploadBtn.on( 'click', function () {
			if ( ! pendingFile ) {
				return;
			}

			$uploadError.text( '' );
			$uploadBtn.prop( 'disabled', true );
			$uploadSpinner.addClass( 'is-active' );

			var formData = new FormData();
			formData.append( 'action', 'tm_import_upload' );
			formData.append( 'nonce', nonce );
			formData.append( 'file', pendingFile );

			$.ajax( {
				url: tmAdmin.ajaxUrl,
				method: 'POST',
				data: formData,
				processData: false,
				contentType: false
			} ).done( function ( response ) {
				$uploadSpinner.removeClass( 'is-active' );

				if ( ! response.success ) {
					$uploadError.text( response.data && response.data.message ? response.data.message : 'Upload failed.' );
					$uploadBtn.prop( 'disabled', false );
					return;
				}

				var data = response.data;
				state.importId = data.import_id;
				state.headers = data.headers;
				state.destinationFields = data.destination_fields;
				state.totalRows = data.total_rows;

				renderMappingStep( data );
				goToStep( 'mapping' );
			} ).fail( function () {
				$uploadSpinner.removeClass( 'is-active' );
				$uploadError.text( 'Upload failed. Please try again.' );
				$uploadBtn.prop( 'disabled', false );
			} );
		} );

		/* -------------------------------------------------------------
		 * Demo data
		 * ----------------------------------------------------------- */

		$( '#tm-generate-demo' ).on( 'click', function () {
			var $btn = $( this );
			var $result = $( '#tm-demo-result' );
			$btn.prop( 'disabled', true );
			$result.text( 'Generating…' );

			// This endpoint is protected by the 'tm_admin_nonce' action
			// (see TM_Demo_Data::ajax_generate()), not the import wizard's
			// 'tm_import_nonce' — use tmAdmin.nonce explicitly rather than
			// the wizard's shared `nonce` variable.
			$.ajax( {
				url: tmAdmin.ajaxUrl,
				method: 'POST',
				data: { action: 'tm_generate_demo_data', nonce: tmAdmin.nonce }
			} ).done( function ( response ) {
				$btn.prop( 'disabled', false );
				if ( response.success ) {
					$result.text( response.data.message );
				} else {
					$result.text( response.data && response.data.message ? response.data.message : 'Something went wrong.' );
				}
			} ).fail( function () {
				$btn.prop( 'disabled', false );
				$result.text( 'Something went wrong.' );
			} );
		} );

		/* -------------------------------------------------------------
		 * Step 2: Mapping
		 * ----------------------------------------------------------- */

		function renderMappingStep( data ) {
			var $headerRow = $( '#tm-mapping-header-row' ).empty();
			var $selectRow = $( '#tm-mapping-select-row' ).empty();
			var $body = $( '#tm-mapping-preview-body' ).empty();

			$( '#tm-mapping-summary' ).text(
				data.total_rows + ' data row(s) detected. Showing a preview of the first ' + data.sample_rows.length + '.'
			);

			data.headers.forEach( function ( header, index ) {
				$headerRow.append( $( '<th>' ).text( header || '(Column ' + ( index + 1 ) + ')' ) );

				var $select = $( '<select>' )
					.attr( 'data-col-index', index )
					.addClass( 'tm-mapping-select' );

				$select.append( $( '<option>' ).val( '' ).text( '— Ignore this column —' ) );

				Object.keys( data.destination_fields ).forEach( function ( fieldKey ) {
					var field = data.destination_fields[ fieldKey ];
					var label = field.label + ( field.required ? ' *' : '' );
					$select.append( $( '<option>' ).val( fieldKey ).text( label ) );
				} );

				var guess = data.mapping_guess[ index ] || '';
				$select.val( guess );

				$selectRow.append( $( '<th>' ).append( $select ) );
			} );

			data.sample_rows.forEach( function ( row ) {
				var $tr = $( '<tr>' );
				row.forEach( function ( cell ) {
					$tr.append( $( '<td>' ).addClass( 'tm-cell-truncate' ).attr( 'title', cell ).text( cell ) );
				} );
				$body.append( $tr );
			} );
		}

		$( '#tm-mapping-back' ).on( 'click', function () {
			goToStep( 'upload' );
		} );

		$( '#tm-mapping-next' ).on( 'click', function () {
			var mapping = {};
			var hasName = false;
			var hasContent = false;

			$( '.tm-mapping-select' ).each( function () {
				var $select = $( this );
				var colIndex = $select.data( 'col-index' );
				var value = $select.val();
				if ( value ) {
					mapping[ colIndex ] = value;
					if ( 'customer_name' === value ) {
						hasName = true;
					}
					if ( 'content' === value ) {
						hasContent = true;
					}
				}
			} );

			var $error = $( '#tm-mapping-error' );

			if ( ! hasName || ! hasContent ) {
				$error.text( tmAdmin.i18n.mappingRequired );
				return;
			}

			$error.text( '' );
			state.mapping = mapping;
			goToStep( 'options' );
		} );

		/* -------------------------------------------------------------
		 * Step 3: Options
		 * ----------------------------------------------------------- */

		$( '#tm-options-back' ).on( 'click', function () {
			goToStep( 'mapping' );
		} );

		$( '#tm-start-import' ).on( 'click', function () {
			var $btn = $( this );
			$btn.prop( 'disabled', true );

			var options = {
				import_id: state.importId,
				mapping: JSON.stringify( state.mapping ),
				duplicate_handling: $( 'input[name="tm_duplicate_handling"]:checked' ).val(),
				status: $( '#tm_status' ).val(),
				category_id: $( '#tm_category_id' ).val(),
				featured_mode: $( '#tm_featured_mode' ).val()
			};

			ajax( 'tm_import_start', options ).done( function ( response ) {
				$btn.prop( 'disabled', false );

				if ( ! response.success ) {
					window.alert( response.data && response.data.message ? response.data.message : 'Could not start import.' ); // eslint-disable-line no-alert
					return;
				}

				goToStep( 'progress' );
				resetProgressUI();
				updateProgressUI( response.data );
				pollBatch();
			} ).fail( function () {
				$btn.prop( 'disabled', false );
				window.alert( 'Could not start import.' ); // eslint-disable-line no-alert
			} );
		} );

		/* -------------------------------------------------------------
		 * Step 4: Progress
		 * ----------------------------------------------------------- */

		function resetProgressUI() {
			state.cancelled = false;
			$( '#tm-import-summary' ).attr( 'hidden', true );
			$( '#tm-progress-actions' ).show();
			$( '#tm-progress-title' ).text( 'Importing testimonials…' );
		}

		function updateProgressUI( data ) {
			var percent = data.percent || 0;
			$( '#tm-progress-bar-fill' ).css( 'width', percent + '%' );
			$( '#tm-progress-bar-wrap' ).attr( 'aria-valuenow', percent );
			$( '#tm-progress-text' ).text( data.processed + ' / ' + data.total + ' (' + percent + '%)' );

			if ( 'complete' === data.status ) {
				onImportComplete( data );
			}
		}

		function pollBatch() {
			if ( state.cancelled ) {
				return;
			}

			ajax( 'tm_import_batch', { import_id: state.importId } ).done( function ( response ) {
				if ( ! response.success ) {
					$( '#tm-progress-text' ).text( response.data && response.data.message ? response.data.message : 'An error occurred.' );
					return;
				}

				updateProgressUI( response.data );

				if ( 'running' === response.data.status && ! state.cancelled ) {
					state.pollTimer = setTimeout( pollBatch, 150 );
				}
			} ).fail( function () {
				// Transient network hiccup — retry after a short delay
				// rather than abandoning the import.
				state.pollTimer = setTimeout( pollBatch, 2000 );
			} );
		}

		function onImportComplete( data ) {
			$( '#tm-progress-actions' ).hide();
			$( '#tm-progress-title' ).text( 'Import completed' );

			$( '#tm-count-imported' ).text( data.counts.imported );
			$( '#tm-count-updated' ).text( data.counts.updated );
			$( '#tm-count-skipped' ).text( data.counts.skipped );
			$( '#tm-count-failed' ).text( data.counts.failed );

			if ( data.errors && data.errors.length ) {
				$( '#tm-error-report' ).removeAttr( 'hidden' );
				var extra = data.error_count > data.errors.length
					? '\n… and ' + ( data.error_count - data.errors.length ) + ' more.'
					: '';
				$( '#tm-error-textarea' ).val( data.errors.join( '\n' ) + extra );
			}

			$( '#tm-import-summary' ).removeAttr( 'hidden' );
		}

		$( '#tm-cancel-import' ).on( 'click', function () {
			if ( ! window.confirm( tmAdmin.i18n.confirmCancel ) ) { // eslint-disable-line no-alert
				return;
			}

			state.cancelled = true;
			if ( state.pollTimer ) {
				clearTimeout( state.pollTimer );
			}

			ajax( 'tm_import_cancel', { import_id: state.importId } ).always( function () {
				goToStep( 'upload' );
				resetWizard();
			} );
		} );

		$( '#tm-import-another' ).on( 'click', function () {
			goToStep( 'upload' );
			resetWizard();
		} );

		function resetWizard() {
			state.importId = null;
			state.mapping = {};
			pendingFile = null;
			$fileInput.val( '' );
			$selectedFile.text( '' );
			$uploadBtn.prop( 'disabled', true );
		}
	} );
} )( jQuery );
