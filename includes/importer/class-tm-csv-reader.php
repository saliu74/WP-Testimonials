<?php
/**
 * Minimal CSV reader built on PHP's native fgetcsv() — no external library
 * required, so the importer works unmodified on any shared-hosting/cPanel
 * environment.
 *
 * @package Testimonials_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class TM_CSV_Reader
 */
class TM_CSV_Reader {

	/**
	 * Detect the delimiter used by a CSV file by sampling its first line.
	 *
	 * @param string $file_path Absolute path.
	 * @return string One of , ; \t |
	 */
	public static function detect_delimiter( $file_path ) {
		$handle = fopen( $file_path, 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fopen
		if ( ! $handle ) {
			return ',';
		}

		$first_line = fgets( $handle );
		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose

		$candidates = array( ',', ';', "\t", '|' );
		$best       = ',';
		$best_count = 0;

		foreach ( $candidates as $delimiter ) {
			$count = substr_count( (string) $first_line, $delimiter );
			if ( $count > $best_count ) {
				$best_count = $count;
				$best       = $delimiter;
			}
		}

		return $best;
	}

	/**
	 * Read the header row plus up to $max_preview_rows of data for a
	 * preview step. Handles a UTF-8 BOM if present.
	 *
	 * @param string $file_path Absolute path.
	 * @param int    $max_preview_rows Number of data rows to sample.
	 * @return array{headers: string[], rows: array[]}
	 */
	public static function preview( $file_path, $max_preview_rows = 20 ) {
		$delimiter = self::detect_delimiter( $file_path );
		$handle    = fopen( $file_path, 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fopen

		$headers = array();
		$rows    = array();

		if ( ! $handle ) {
			return array( 'headers' => $headers, 'rows' => $rows, 'delimiter' => $delimiter );
		}

		$first = true;
		$count = 0;

		while ( false !== ( $line = fgetcsv( $handle, 0, $delimiter ) ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fgetcsv,Squiz.PHP.DisallowMultipleAssignments
			if ( 1 === count( $line ) && null === $line[0] ) {
				continue; // Skip fully blank lines.
			}

			if ( $first ) {
				$headers = array_map( array( __CLASS__, 'clean_cell' ), $line );
				$first   = false;
				continue;
			}

			$rows[] = array_map( array( __CLASS__, 'clean_cell' ), $line );
			$count++;

			if ( $count >= $max_preview_rows ) {
				break;
			}
		}

		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose

		return array( 'headers' => $headers, 'rows' => $rows, 'delimiter' => $delimiter );
	}

	/**
	 * Count the total number of data rows (excluding header) without
	 * loading the whole file into memory — used to size the progress bar.
	 *
	 * @param string $file_path Absolute path.
	 * @return int
	 */
	public static function count_rows( $file_path ) {
		$delimiter = self::detect_delimiter( $file_path );
		$handle    = fopen( $file_path, 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fopen

		if ( ! $handle ) {
			return 0;
		}

		$count = -1; // Start at -1 to exclude the header row.
		while ( false !== fgetcsv( $handle, 0, $delimiter ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fgetcsv
			$count++;
		}

		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose

		return max( 0, $count );
	}

	/**
	 * Read a specific batch of data rows (0-indexed, excluding header),
	 * used by the batch import processor so we never load the whole file
	 * into memory at once.
	 *
	 * @param string $file_path Absolute path.
	 * @param int    $offset    Data row offset (0 = first row after header).
	 * @param int    $limit     Number of rows to read.
	 * @return array[]
	 */
	public static function read_batch( $file_path, $offset, $limit ) {
		$delimiter = self::detect_delimiter( $file_path );
		$handle    = fopen( $file_path, 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fopen

		$rows = array();

		if ( ! $handle ) {
			return $rows;
		}

		$row_index = -1; // header is row -1.
		$collected = 0;

		while ( false !== ( $line = fgetcsv( $handle, 0, $delimiter ) ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fgetcsv,Squiz.PHP.DisallowMultipleAssignments
			if ( 1 === count( $line ) && null === $line[0] ) {
				continue;
			}

			if ( -1 === $row_index ) {
				$row_index++;
				continue; // Skip header.
			}

			if ( $row_index >= $offset && $collected < $limit ) {
				$rows[] = array_map( array( __CLASS__, 'clean_cell' ), $line );
				$collected++;
			}

			$row_index++;

			if ( $collected >= $limit ) {
				break;
			}
		}

		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose

		return $rows;
	}

	/**
	 * Strip a UTF-8 BOM and trim whitespace from a single cell value.
	 *
	 * @param string|null $value Raw cell value.
	 * @return string
	 */
	private static function clean_cell( $value ) {
		if ( null === $value ) {
			return '';
		}
		$value = preg_replace( '/^\xEF\xBB\xBF/', '', $value );
		return trim( (string) $value );
	}
}
