<?php
/**
 * Minimal, dependency-free XLSX reader.
 *
 * An .xlsx file is a ZIP archive of XML parts. Rather than bundling a
 * large third-party library (PhpSpreadsheet) — which assumes Composer
 * autoloading and adds significant weight for a feature that only needs
 * to read simple tabular data — this class reads just what's needed:
 * the shared strings table and the first worksheet's rows/cells.
 *
 * Requires the `zip` and `simplexml` PHP extensions, both bundled with
 * PHP by default on virtually all shared hosts. If either is missing we
 * fail gracefully with a clear error rather than a fatal.
 *
 * @package Testimonials_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class TM_XLSX_Reader
 */
class TM_XLSX_Reader {

	/**
	 * @var ZipArchive
	 */
	private $zip;

	/**
	 * Shared strings table, indexed numerically as referenced by cells.
	 *
	 * @var string[]
	 */
	private $shared_strings = array();

	/**
	 * Relative path (inside the zip) to the first worksheet.
	 *
	 * @var string
	 */
	private $sheet_path = 'xl/worksheets/sheet1.xml';

	/**
	 * Whether the archive opened successfully.
	 *
	 * @var bool
	 */
	private $is_valid = false;

	/**
	 * Constructor.
	 *
	 * @param string $file_path Absolute path to the .xlsx file.
	 */
	public function __construct( $file_path ) {
		if ( ! class_exists( 'ZipArchive' ) || ! function_exists( 'simplexml_load_string' ) ) {
			return;
		}

		$this->zip = new ZipArchive();
		if ( true !== $this->zip->open( $file_path ) ) {
			return;
		}

		$this->is_valid = true;

		$this->load_shared_strings();
		$this->resolve_first_sheet_path();
	}

	/**
	 * Whether the file was successfully opened as a valid XLSX archive.
	 *
	 * @return bool
	 */
	public function is_valid() {
		return $this->is_valid;
	}

	/**
	 * Load the shared strings table (xl/sharedStrings.xml), if present.
	 * Supports both plain <t> text and rich-text runs (<r><t>).
	 */
	private function load_shared_strings() {
		$xml = $this->read_zip_entry( 'xl/sharedStrings.xml' );
		if ( ! $xml ) {
			return;
		}

		$sxml = @simplexml_load_string( $xml ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		if ( ! $sxml ) {
			return;
		}

		foreach ( $sxml->si as $si ) {
			if ( isset( $si->t ) ) {
				$this->shared_strings[] = (string) $si->t;
			} elseif ( isset( $si->r ) ) {
				$text = '';
				foreach ( $si->r as $run ) {
					$text .= isset( $run->t ) ? (string) $run->t : '';
				}
				$this->shared_strings[] = $text;
			} else {
				$this->shared_strings[] = '';
			}
		}
	}

	/**
	 * Determine which worksheet part corresponds to the first sheet, using
	 * workbook.xml + workbook.xml.rels (sheet order/ids don't always map
	 * 1:1 to sheet1.xml on files re-saved by third-party tools).
	 */
	private function resolve_first_sheet_path() {
		$workbook_xml = $this->read_zip_entry( 'xl/workbook.xml' );
		$rels_xml     = $this->read_zip_entry( 'xl/_rels/workbook.xml.rels' );

		if ( ! $workbook_xml || ! $rels_xml ) {
			return; // Fall back to the sheet1.xml default.
		}

		$workbook = @simplexml_load_string( $workbook_xml ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		$rels     = @simplexml_load_string( $rels_xml ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged

		if ( ! $workbook || ! $rels || ! isset( $workbook->sheets->sheet[0] ) ) {
			return;
		}

		$first_sheet   = $workbook->sheets->sheet[0];
		$r_attributes  = $first_sheet->attributes( 'http://schemas.openxmlformats.org/officeDocument/2006/relationships' );
		$relationship_id = isset( $r_attributes['id'] ) ? (string) $r_attributes['id'] : '';

		if ( ! $relationship_id ) {
			return;
		}

		foreach ( $rels->Relationship as $relationship ) {
			if ( (string) $relationship['Id'] === $relationship_id ) {
				$target = (string) $relationship['Target'];
				$target = ltrim( $target, '/' );
				$this->sheet_path = ( 0 === strpos( $target, 'xl/' ) ) ? $target : 'xl/' . $target;
				return;
			}
		}
	}

	/**
	 * Read a single entry out of the zip archive as a string.
	 *
	 * @param string $entry_path Path within the archive.
	 * @return string|false
	 */
	private function read_zip_entry( $entry_path ) {
		if ( ! $this->is_valid ) {
			return false;
		}
		$contents = $this->zip->getFromName( $entry_path );
		return false === $contents ? false : $contents;
	}

	/**
	 * Convert a spreadsheet column letter (A, B, ..., AA) to a zero-based
	 * numeric index.
	 *
	 * @param string $letters Column letters, e.g. "C".
	 * @return int
	 */
	private static function column_letters_to_index( $letters ) {
		$letters = strtoupper( $letters );
		$index   = 0;
		for ( $i = 0; $i < strlen( $letters ); $i++ ) {
			$index = $index * 26 + ( ord( $letters[ $i ] ) - 64 );
		}
		return $index - 1;
	}

	/**
	 * Parse a cell reference like "C15" into [column_index, row_number].
	 *
	 * @param string $ref Cell reference.
	 * @return array{0:int,1:int}
	 */
	private static function parse_cell_ref( $ref ) {
		preg_match( '/^([A-Z]+)(\d+)$/', $ref, $matches );
		if ( empty( $matches ) ) {
			return array( 0, 1 );
		}
		return array( self::column_letters_to_index( $matches[1] ), (int) $matches[2] );
	}

	/**
	 * Resolve a single <c> cell node to its display string value.
	 *
	 * @param SimpleXMLElement $cell Cell node.
	 * @return string
	 */
	private function resolve_cell_value( $cell ) {
		$type = isset( $cell['t'] ) ? (string) $cell['t'] : '';

		if ( 'inlineStr' === $type ) {
			return isset( $cell->is->t ) ? (string) $cell->is->t : '';
		}

		if ( ! isset( $cell->v ) ) {
			return '';
		}

		$raw = (string) $cell->v;

		if ( 's' === $type ) {
			$index = (int) $raw;
			return isset( $this->shared_strings[ $index ] ) ? $this->shared_strings[ $index ] : '';
		}

		if ( 'b' === $type ) {
			return '1' === $raw ? '1' : '0';
		}

		// Numeric (including dates, which we intentionally leave as the
		// underlying serial number — testimonial imports treat dates as
		// free text and administrators can adjust in the mapping step).
		return $raw;
	}

	/**
	 * Read every row of the first worksheet into a simple array-of-arrays,
	 * normalized so every row has the same number of columns as the header.
	 *
	 * @return array[] Each row is a zero-indexed array of string cell values.
	 */
	public function read_all_rows() {
		$xml = $this->read_zip_entry( $this->sheet_path );
		if ( ! $xml ) {
			return array();
		}

		$sxml = @simplexml_load_string( $xml ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		if ( ! $sxml || ! isset( $sxml->sheetData->row ) ) {
			return array();
		}

		$rows      = array();
		$max_cols  = 0;

		foreach ( $sxml->sheetData->row as $row ) {
			$row_values = array();

			foreach ( $row->c as $cell ) {
				$ref                = isset( $cell['r'] ) ? (string) $cell['r'] : '';
				list( $col_index )  = $ref ? self::parse_cell_ref( $ref ) : array( count( $row_values ) );
				$row_values[ $col_index ] = $this->resolve_cell_value( $cell );
			}

			if ( empty( $row_values ) ) {
				continue;
			}

			$max_cols = max( $max_cols, max( array_keys( $row_values ) ) + 1 );
			$rows[]   = $row_values;
		}

		// Normalize row widths and preserve column order, filling gaps with ''.
		foreach ( $rows as &$row_values ) {
			$normalized = array();
			for ( $i = 0; $i < $max_cols; $i++ ) {
				$normalized[] = isset( $row_values[ $i ] ) ? trim( (string) $row_values[ $i ] ) : '';
			}
			$row_values = $normalized;
		}
		unset( $row_values );

		return $rows;
	}

	/**
	 * Close the underlying zip handle.
	 */
	public function close() {
		if ( $this->is_valid && $this->zip ) {
			$this->zip->close();
		}
	}
}
