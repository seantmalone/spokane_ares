<?php
/**
 * Regression tests for workflow finding N1 (ux/workflows/content.py): every
 * month the schedule was typed again by hand from ag7qp.com or the e-mail.
 * Now the Net Control Schedule has "Paste the schedule": paste the lines,
 * Preview shows which Tuesdays would change and lists the lines it couldn't
 * read (never guessed), and "Save these Tuesdays" posts those rows to the
 * schedule's own save, so every per-row check and the clash check still run.
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

/**
 * Not posted yet Tuesdays among the next 13 (past the first).
 *
 * @param int $count How many.
 */
function wfn1_free( int $count ): array {
	$out = array();
	foreach ( array_slice( \spokares_rota_rows( 13 ), 1 ) as $row ) {
		if ( 'tbd' === $row['state'] && count( $out ) < $count ) {
			$out[] = $row['date'];
		}
	}
	if ( count( $out ) < $count ) {
		fail( 'set-up: not enough Not posted yet Tuesdays' );
	}
	return $out;
}

/**
 * A date in a PHP date() format.
 *
 * @param string $format Format.
 * @param string $ymd    Date.
 */
function wfn1_fmt( string $format, string $ymd ): string {
	return gmdate( $format, (int) strtotime( $ymd . ' 12:00:00 UTC' ) );
}

/**
 * The screen as the current user sees it.
 *
 * @param array $get Query args.
 */
function wfn1_screen( array $get = array() ): string {
	$res = call_request(
		'GET',
		array_merge( array( 'page' => 'spokares-rota' ), $get ),
		array(),
		static function () {
			\spokares_rota_page();
		}
	);
	return (string) $res['output'];
}

test(
	'the paste reader: dates in five shapes, call signs, open, no net and tbd; other lines listed with the reason',
	function () {
		list( $a, $b, $c, $d, $e ) = wfn1_free( 5 );
		$wed  = \spokares_add_days( $a, 1 );
		$far  = \spokares_add_days( \spokares_today(), 7 * 60 );
		$far  = \spokares_next_weekday( $far, 2 );
		$text = implode(
			"\n",
			array(
				'Net control for the coming weeks',
				wfn1_fmt( 'D M j', $a ) . ' NZ2S',
				wfn1_fmt( 'n/j', $b ) . ' - open',
				'',
				wfn1_fmt( 'F j, Y', $c ) . ' – no net',
				wfn1_fmt( 'Y-m-d', $d ) . ' tbd',
				wfn1_fmt( 'j M', $e ) . ': Tom K7ABC',
				wfn1_fmt( 'M j', $wed ) . ' AG7QP',
				wfn1_fmt( 'M j', $b ) . ' Frank',
				wfn1_fmt( 'M j, Y', $far ) . ' NZ2S',
				'Feb 30 NZ2S',
			)
		);
		$out  = \spokares_rota_parse_paste( $text );
		assert_same(
			array(
				$a => array( 'call', 'NZ2S' ),
				$b => array( 'open', '' ),
				$c => array( 'none', '' ),
				$d => array( 'tbd', '' ),
				$e => array( 'call', 'K7ABC' ),
			),
			array_map( static fn( $r ) => array( $r['state'], $r['call'] ), $out['rows'] ),
			'the Tuesdays read'
		);
		$why = wp_list_pluck( $out['bad'], 'why' );
		assert_same(
			array(
				'no date at the start',
				\spokares_fmt_date( $wed, 'day' ) . ' isn’t a Tuesday',
				'no call sign, “open”, “no net” or “tbd” after the date',
				\spokares_fmt_date( $far, 'long' ) . ' isn’t one of the Tuesdays on this screen',
				'not a real date',
			),
			$why,
			'the lines left out, each with why'
		);
		assert_same( wfn1_fmt( 'M j', $b ) . ' Frank', $out['bad'][2]['line'], 'the line as pasted' );
	}
);

test(
	'Paste the schedule › Preview shows what would change and saves nothing; Save these Tuesdays saves through the schedule’s checks',
	function () {
		as_role( 'ares-editor' );
		list( $a, $b, $c ) = wfn1_free( 3 );
		$before = \spokares_opt( 'spk_rota' );
		$text   = wfn1_fmt( 'D M j', $a ) . " NZ2S\n" . wfn1_fmt( 'n/j', $b ) . " open\n" . wfn1_fmt( 'n/j', $c ) . " tbd\nhello";
		$res    = post_form(
			'spokares_paste_rota',
			array(
				'weeks' => '13',
				'paste' => $text,
			)
		);
		assert_contains( 'paste=1', (string) $res['redirect'], 'back to the screen with the preview' );
		\spokares_opt_flush();
		assert_same( $before, \spokares_opt( 'spk_rota' ), 'Preview saves nothing' );
		assert_not_contains( 'spk-paste-preview', wfn1_screen(), 'no preview without ?paste' );

		$html = wfn1_screen( array( 'paste' => '1' ) );
		assert_contains( 'These Tuesdays would change:', $html, 'the heading' );
		assert_contains( esc_html( \spokares_fmt_date( $a, 'short' ) . ': NZ2S (was Not posted yet)' ), $html, 'the first Tuesday' );
		assert_contains( esc_html( \spokares_fmt_date( $b, 'short' ) . ': Volunteer needed (was Not posted yet)' ), $html, 'the second' );
		assert_contains( '1 Tuesday already matches.', $html, 'the unchanged one is counted, not listed' );
		assert_contains( esc_html( '“hello”: no date at the start' ), $html, 'the line not understood' );
		assert_matches( '#<details class="spk-more spk-paste" open>#', $html, 'the box stays open' );
		assert_contains( '>' . esc_textarea( $text ) . '</textarea>', $html, 'with the paste in it' );
		if ( ! preg_match( '#<form [^>]*class="spk-paste-save">.*?</form>#s', $html, $form ) ) {
			fail( 'no Save these form' );
		}
		assert_contains( '>Save these Tuesdays</button>', $form[0], 'the button' );

		// Someone saves the second Tuesday in the meantime: that row is refused by the clash check.
		$rota       = \spokares_opt( 'spk_rota' );
		$rota[ $b ] = array(
			'state'   => 'call',
			'call'    => 'AG7QP',
			'note'    => '',
			'wl_task' => '',
			'wl_form' => '',
		);
		update_option( 'spk_rota', $rota );
		\spokares_opt_flush();

		delete_transient( 'spokares_notices_' . get_current_user_id() );
		post_form( 'spokares_save_rota', uxncs_browser_fields( $form[0] ) );
		\spokares_unlock_option( 'spk_rota' );
		\spokares_opt_flush();
		$rota = \spokares_opt( 'spk_rota' );
		assert_same( 'NZ2S', $rota[ $a ]['call'] ?? '', 'the first Tuesday is saved' );
		assert_same( 'AG7QP', $rota[ $b ]['call'] ?? '', 'the clash keeps the other save' );
		$notices = get_transient( 'spokares_notices_' . get_current_user_id() );
		assert_same( 'Saved ' . \spokares_fmt_date( $a, 'day' ) . '. Not saved: ' . \spokares_fmt_date( $b, 'day' ) . ' (outlined in red).', $notices[0]['text'] ?? '', 'one notice, as for typing' );
		assert_false( get_transient( 'spokares_rota_paste_' . get_current_user_id() ), 'the paste is done with after the save' );
	}
);

test(
	'Preview with nothing pasted says so',
	function () {
		as_role( 'ares-editor' );
		delete_transient( 'spokares_notices_' . get_current_user_id() );
		$res = post_form( 'spokares_paste_rota', array( 'paste' => "  \n " ) );
		assert_not_contains( 'paste=1', (string) $res['redirect'], 'no preview' );
		$notices = get_transient( 'spokares_notices_' . get_current_user_id() );
		assert_same( 'Paste the schedule into the box first, then click Preview.', $notices[0]['text'] ?? '', 'the notice' );
	}
);
