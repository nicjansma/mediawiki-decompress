<?php
/**
 * Decompress your wiki.
 *
 * Usage:
 *
 * Non-wikimedia
 * php decompress.php [options...]
 *
 * Wikimedia
 * php decompress.php <database> [options...]
 *
 * @file
 * @ingroup Maintenance ExternalStorage
 */

require_once __DIR__ . '/../Maintenance.php';

/**
 * Maintenance script that compress the text of a wiki.
 *
 * @ingroup Maintenance ExternalStorage
 */
class Decompress extends Maintenance {
	/**
	 * @todo document
	 */
	const LS_INDIVIDUAL = 0;
	const LS_CHUNKED = 1;

	public function __construct() {
		parent::__construct();
		$this->mDescription = 'Decompress your regvisions';
	}

	public function execute() {
		global $wgDBname;
		if ( !function_exists( "gzdeflate" ) ) {
			$this->error( "You must enable zlib support in PHP to compress old revisions!\n" .
				"Please see http://www.php.net/manual/en/ref.zlib.php\n", true );
		}

		$this->decompressOldPages();

		$this->output( "Done.\n" );
	}

	/**
	 * @todo document
	 * @param int $start
	 */
	private function decompressOldPages($start = 0) {
		$chunksize = 50;
		$this->output( "Starting from old_id 0...\n" );
		$dbw = wfGetDB( DB_MASTER );
		do {
			$res = $dbw->select(
				'text',
				array( 'old_id', 'old_flags', 'old_text' ),
				"old_id>=$start",
				__METHOD__,
				array( 'ORDER BY' => 'old_id', 'LIMIT' => $chunksize, 'FOR UPDATE' )
			);

			if ( $res->numRows() == 0 ) {
				break;
			}

			$last = $start;

			foreach ( $res as $row ) {
				# print "  {$row->old_id} - {$row->old_namespace}:{$row->old_title}\n";
				$this->decompressPage( $row, '' );
				$last = $row->old_id;
			}

			$start = $last + 1; # Deletion may leave long empty stretches
			$this->output( "$start...\n" );
		} while ( true );
	}

	/**
	 * @todo document
	 * @param stdClass $row
	 * @return bool
	 */
	private function decompressPage( $row ) {
		if ( false === strpos( $row->old_flags, 'gzip' )
			|| false !== strpos( $row->old_flags, 'object' )
		) {
			print "Already decompressed row {$row->old_id}\n";
			return false;
		}
		$dbw = wfGetDB( DB_MASTER );
		$flags = $row->old_flags ? str_replace('gzip', '', $row->old_flags) : '';
		$flags = str_replace(',,', ',', $flags);
		$decompressed = @gzinflate( $row->old_text );
		if ($decompressed === false) {
		    return false;
		}

		# Update text row
		$dbw->update( 'text',
			array( /* SET */
				'old_flags' => $flags,
				'old_text' => $decompressed
			), array( /* WHERE */
				'old_id' => $row->old_id
			), __METHOD__,
			array( 'LIMIT' => 1 )
		);

		return true;
	}
}

$maintClass = 'Decompress';
require_once RUN_MAINTENANCE_IF_MAIN;
