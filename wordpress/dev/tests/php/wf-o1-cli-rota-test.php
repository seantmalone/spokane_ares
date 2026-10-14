<?php
/**
 * Regression tests for workflow finding O1 (ux/workflows/content.py), the
 * Net Control Schedule part: the webmaster entered the schedule over SSH
 * with `wp eval-file` and update_option(), which skipped the call-sign
 * check, the never-publish words and the phone tick, the clash check, the
 * "Last saved" stamp and the undo snapshot.
 *
 * Now `wp spokares rota set|paste|show` go through the screen's own check
 * and write (spokares_rota_check_rows(), spokares_rota_write_rows()). These
 * tests call the command functions with a stand-in WP_CLI
 * (dev/tests/lib/class-wp-cli.php); WP-CLI isn't loaded in the test request.
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

/**
 * Run a `wp spokares …` command function as WP-CLI would.
 *
 * @param string $command Command function, e.g. spokares_cli_rota_set.
 * @param array  $args    Positional arguments.
 * @param array  $assoc   Options.
 * @return array{exit:bool,out:array,text:string} exit: it stopped with an error.
 */
function wfo1r_cli( string $command, array $args, array $assoc = array() ): array {
	require_once dirname( __DIR__ ) . '/lib/class-wp-cli.php';
	require_once SPOKARES_CORE_DIR . 'inc/cli.php';
	\WP_CLI::$out = array();
	$exit         = false;
	try {
		$command( $args, $assoc );
	} catch ( \RuntimeException $e ) {
		if ( \WP_CLI::EXIT_CODE !== $e->getCode() ) {
			throw $e;
		}
		$exit = true;
	}
	$text = array();
	foreach ( \WP_CLI::$out as $o ) {
		$text[] = $o[0] . ': ' . ( is_string( $o[1] ) ? $o[1] : wp_json_encode( $o[1] ) );
	}
	return array(
		'exit' => $exit,
		'out'  => \WP_CLI::$out,
		'text' => implode( "\n", $text ),
	);
}

/**
 * Empty Not posted yet Tuesdays among the next 13 (past the first).
 *
 * @param int $count How many.
 */
function wfo1r_free( int $count ): array {
	$out = array();
	foreach ( array_slice( \spokares_rota_rows( 13 ), 1 ) as $row ) {
		if ( 'tbd' === $row['state'] && '' === $row['row_note'] . $row['wl_task'] . $row['wl_form'] && count( $out ) < $count ) {
			$out[] = $row['date'];
		}
	}
	if ( count( $out ) < $count ) {
		fail( 'set-up: not enough Not posted yet Tuesdays' );
	}
	return $out;
}

/**
 * The stored schedule, read fresh.
 */
function wfo1r_rota(): array {
	\spokares_opt_flush();
	wp_cache_delete( 'alloptions', 'options' );
	return \spokares_opt( 'spk_rota' );
}

test(
	'rota set: saves the Tuesday through the screen\'s check, with the Last saved stamp, the undo snapshot and a cache purge',
	function () {
		list( $d ) = wfo1r_free( 1 );
		$user      = as_role( 'ares-editor' );
		$purged    = did_action( 'spokares_cache_purged' );
		$r         = wfo1r_cli( 'spokares_cli_rota_set', array( $d, 'nz2s Frank' ), array( 'note' => 'Bring a pencil' ) );
		assert_false( $r['exit'], 'saved: ' . $r['text'] );
		assert_contains( 'success: Saved ' . \spokares_fmt_date( $d, 'day' ) . ': NZ2S (names aren’t posted).', $r['text'], 'the screen\'s "Saved" sentence, the dropped name said' );
		$row = wfo1r_rota()[ $d ];
		assert_same( array( 'call', 'NZ2S', 'Bring a pencil' ), array( $row['state'], $row['call'], $row['note'] ), 'the row' );
		$saved = get_option( 'spk_rota_saved' );
		assert_same( $user->ID, (int) $saved['by'], 'Last saved names the acting user' );
		assert_same( array( $d ), $saved['dates'], 'and the Tuesday' );
		$prev = get_option( 'spk_rota_prev' );
		assert_true( array_key_exists( $d, $prev['rows'] ) && null === $prev['rows'][ $d ], 'the undo snapshot has the row as it was (none)' );
		assert_same( \spokares_rota_hash( $row ), $prev['after'][ $d ], 'and the row as this save left it' );
		assert_true( did_action( 'spokares_cache_purged' ) > $purged, 'the cache was purged' );

		// Fields not given keep what the row has.
		$r = wfo1r_cli( 'spokares_cli_rota_set', array( $d, 'volunteer' ) );
		assert_false( $r['exit'], $r['text'] );
		$row = wfo1r_rota()[ $d ];
		assert_same( array( 'open', '', 'Bring a pencil' ), array( $row['state'], $row['call'], $row['note'] ), 'Volunteer needed; the note kept' );

		// The screen's Undo takes it back.
		$res = post_form( 'spokares_undo_rota', array() );
		assert_contains( 'spokares-rota', (string) $res['redirect'], 'undo redirects to the screen' );
		$row = wfo1r_rota()[ $d ];
		assert_same( array( 'call', 'NZ2S' ), array( $row['state'], $row['call'] ), 'Undo on the screen puts back the row the command replaced' );
	}
);

test(
	'rota set: refused with the screen\'s sentences: a name, a phone number without --confirm, a never-publish word, a non-Tuesday',
	function () {
		list( $d ) = wfo1r_free( 1 );
		as_role( 'ares-editor' );
		$r = wfo1r_cli( 'spokares_cli_rota_set', array( $d, 'Frank' ) );
		assert_true( $r['exit'], 'a name is refused' );
		assert_contains( 'Net control: Type a call sign, like NZ2S, not a name.', $r['text'], 'the screen\'s sentence' );
		assert_false( isset( wfo1r_rota()[ $d ] ), 'nothing saved' );

		$r = wfo1r_cli( 'spokares_cli_rota_set', array( $d, 'NZ2S' ), array( 'note' => 'Call 509-555-1234' ) );
		assert_true( $r['exit'], 'a phone number needs --confirm' );
		assert_contains( 'Note: It has a phone number. Take it out, or tick the box if it’s a public agency number. (Add --confirm to tick that box.)', $r['text'], 'the sentence, and how to tick' );
		assert_contains( 'The rest was saved', $r['text'], 'Net control saved, the note held back (as on the screen)' );
		$row = wfo1r_rota()[ $d ];
		assert_same( array( 'NZ2S', '' ), array( $row['call'], $row['note'] ), 'the call sign saved without the note' );

		$r = wfo1r_cli(
			'spokares_cli_rota_set',
			array( $d, 'NZ2S' ),
			array(
				'note'    => 'Call 509-555-1234',
				'confirm' => true,
			)
		);
		assert_false( $r['exit'], '--confirm publishes it: ' . $r['text'] );
		$row = wfo1r_rota()[ $d ];
		assert_same( 'Call 509-555-1234', $row['note'], 'saved' );
		assert_same( sha1( 'Call 509-555-1234' ), $row['confirmed']['note'] ?? '', 'the confirmation stored, as the tick does' );

		$r = wfo1r_cli(
			'spokares_cli_rota_set',
			array( $d, 'NZ2S' ),
			array(
				'winlink' => 'Report the talkgroup',
				'confirm' => true,
			)
		);
		assert_true( $r['exit'], 'a never-publish word is refused, even with --confirm' );
		assert_contains( 'Winlink task: It mentions a talkgroup, which we never publish. Take it out.', $r['text'], 'the sentence' );
		assert_same( '', wfo1r_rota()[ $d ]['wl_task'], 'not saved' );

		$r = wfo1r_cli( 'spokares_cli_rota_set', array( \spokares_add_days( $d, 1 ), 'NZ2S' ) );
		assert_true( $r['exit'], 'a Wednesday is refused' );
		assert_contains( 'isn’t a Tuesday', $r['text'], 'with why' );
		$r = wfo1r_cli( 'spokares_cli_rota_set', array( 'next week', 'NZ2S' ) );
		assert_contains( 'Type the Tuesday as YYYY-MM-DD', $r['text'], 'a date in another shape' );
		$r = wfo1r_cli( 'spokares_cli_rota_set', array( \spokares_add_days( $d, 7 * 60 ), 'NZ2S' ) );
		assert_contains( 'isn’t one of the next 52 Tuesdays', $r['text'], 'too far ahead' );
	}
);

test(
	'rota set: needs an account that may edit the schedule; --dry-run says what would change and writes nothing',
	function () {
		list( $d ) = wfo1r_free( 1 );
		as_anonymous();
		$r = wfo1r_cli( 'spokares_cli_rota_set', array( $d, 'NZ2S' ) );
		assert_true( $r['exit'], 'no user: refused' );
		assert_contains( 'add --user=<login>', $r['text'], 'says how' );
		as_role( 'subscriber' );
		$r = wfo1r_cli( 'spokares_cli_rota_set', array( $d, 'NZ2S' ) );
		assert_true( $r['exit'], 'a subscriber: refused' );
		assert_contains( 'can’t make this change on the site’s screens either', $r['text'], 'says why' );
		assert_false( isset( wfo1r_rota()[ $d ] ), 'nothing saved' );

		as_anonymous();
		$stamp = get_option( 'spk_rota_saved' );
		$r     = wfo1r_cli( 'spokares_cli_rota_set', array( $d, 'NZ2S' ), array( 'dry-run' => true ) );
		assert_false( $r['exit'], $r['text'] );
		assert_contains( 'log: Would save ' . \spokares_fmt_date( $d, 'day' ) . ': NZ2S (was Not posted yet)', $r['text'], 'what would change' );
		assert_false( isset( wfo1r_rota()[ $d ] ), 'nothing saved' );
		assert_same( $stamp, get_option( 'spk_rota_saved' ), 'no stamp' );
		$r = wfo1r_cli( 'spokares_cli_rota_set', array( $d, 'Frank' ), array( 'dry-run' => true ) );
		assert_contains( 'warning: Would not save ' . \spokares_fmt_date( $d, 'day' ) . ', Net control: Type a call sign', $r['text'], 'a refusal is named in a dry run too' );
	}
);

test(
	'rota paste: reads the lines as Paste the schedule does, lists the ones it can\'t read, saves through the same check',
	function () {
		list( $a, $b ) = wfo1r_free( 2 );
		$file          = wp_tempnam( 'wfo1-paste' );
		file_put_contents( $file, implode( "\n", array( 'Net control for the coming weeks', gmdate( 'D M j', (int) strtotime( $a . ' 12:00 UTC' ) ) . ' NZ2S', gmdate( 'n/j', (int) strtotime( $b . ' 12:00 UTC' ) ) . ' - open' ) ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- a temporary test file.
		as_role( 'ares-net' );
		$r = wfo1r_cli( 'spokares_cli_rota_paste', array( $file ), array( 'dry-run' => true ) );
		assert_contains( 'warning: Not understood, so left out: “Net control for the coming weeks”: no date at the start', $r['text'], 'the line it couldn\'t read' );
		assert_contains( 'Would save ' . \spokares_fmt_date( $a, 'day' ) . ': NZ2S', $r['text'], 'the preview' );
		assert_false( isset( wfo1r_rota()[ $a ] ), 'a dry run saves nothing' );

		$r = wfo1r_cli( 'spokares_cli_rota_paste', array( $file ) );
		wp_delete_file( $file );
		assert_false( $r['exit'], $r['text'] );
		assert_contains( 'success: Saved ' . \spokares_fmt_date( $a, 'day' ) . ' and ' . \spokares_fmt_date( $b, 'day' ) . '.', $r['text'], 'both Tuesdays' );
		$rota = wfo1r_rota();
		assert_same( array( 'call', 'open' ), array( $rota[ $a ]['state'], $rota[ $b ]['state'] ), 'stored' );
		assert_same( array( $a, $b ), get_option( 'spk_rota_saved' )['dates'], 'one save, one stamp' );

		$r     = wfo1r_cli( 'spokares_cli_rota_show', array(), array() );
		$table = array_values( array_filter( $r['out'], static fn( $o ) => 'table' === $o[0] ) )[0][1];
		$byday = array_column( $table, 'net_control', 'date' );
		assert_same( 'NZ2S', $byday[ $a ] ?? '', 'show lists it' );
		assert_matches( '/^line: Last saved .* \(/', $r['text'], 'with the Last saved line' );
	}
);

test(
	'rota: the form still saves through the same check (a save from the screen and from the command store the same row)',
	function () {
		list( $a, $b ) = wfo1r_free( 2 );
		as_role( 'ares-editor' );
		post_form(
			'spokares_save_rota',
			array(
				'rota' => array(
					$a => array(
						'h'     => \spokares_rota_hash( null ),
						'state' => 'call',
						'call'  => 'k7abc',
						'note'  => 'Simplex check',
					),
				),
			)
		);
		\spokares_unlock_option( 'spk_rota' ); // The form's save keeps it to the end of the request.
		wfo1r_cli( 'spokares_cli_rota_set', array( $b, 'k7abc' ), array( 'note' => 'Simplex check' ) );
		$rota = wfo1r_rota();
		assert_same( $rota[ $a ], $rota[ $b ], 'the same row' );
	}
);
