<?php
/**
 * Regression tests for workflow findings N4 and N8 (ux/workflows/content.py)
 * on Net Settings:
 *
 * N4: moving the Winlink weeks took stored Winlink assignments off the
 * Exercises & events page without a word. Now the notice names every future
 * Tuesday whose assignment drops off, as Meeting Schedule names the changes
 * a pattern change removes.
 *
 * N8: an offset or tone that isn't in the list (only a hand-made request
 * can send one) was ignored without saying so. Now it is refused with a
 * sentence, like every other field.
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

/**
 * Open Net Settings as the current user, change the fields, Save; return
 * the notices and the held errors.
 *
 * @param callable $change Form fields => changed fields.
 */
function wfn4_save( callable $change ): array {
	\spokares_opt_flush();
	ob_start();
	\spokares_net_details_page();
	preg_match( '/<form\b.*?<\/form>/is', (string) ob_get_clean(), $m );
	// The fields a browser sends (ux-net-control-schedule-test.php).
	$fields = $change( uxncs_browser_fields( $m[0] ?? '' ) );
	delete_transient( 'spokares_notices_' . get_current_user_id() );
	$res = post_form( 'spokares_save_net_details', $fields );
	assert_same( null, $res['die'], 'the save was refused' );
	\spokares_opt_flush();
	$notices = get_transient( 'spokares_notices_' . get_current_user_id() );
	delete_transient( 'spokares_notices_' . get_current_user_id() );
	return array(
		'notices' => is_array( $notices ) ? $notices : array(),
		'errors'  => \spokares_retained( 'spokares-net-details' )['errors'],
	);
}

/**
 * The next future Tuesdays in a week of the month.
 *
 * @param int $n     Week of the month (1-5).
 * @param int $count How many.
 */
function wfn4_tuesdays( int $n, int $count ): array {
	$out = array();
	foreach ( \spokares_rota_rows( 30 ) as $row ) {
		if ( \spokares_nth( $row['date'] ) === $n && $row['date'] > \spokares_today() && count( $out ) < $count ) {
			$out[] = $row['date'];
		}
	}
	return $out;
}

test(
	'N4: a save that stops a week being a Winlink night names each stored assignment that drops off the site',
	function () {
		as_role( 'ares-net' );
		// Start: only the 2nd Tuesday is a Winlink night.
		$nets                = \spokares_opt( 'spk_nets' );
		$nets['winlink_nth'] = array( 2 );
		$nets['simplex_nth'] = array( 5 );
		$nets['gmrs_nth']    = array();
		update_option( 'spk_nets', $nets );
		$dates = wfn4_tuesdays( 2, 2 );
		$third = wfn4_tuesdays( 3, 1 )[0];
		$rota  = array();
		foreach ( $dates as $d ) {
			$rota[ $d ] = array(
				'state'   => 'open',
				'call'    => '',
				'note'    => '',
				'wl_task' => 'Send a welfare message to AG7QP',
				'wl_form' => '',
			);
		}
		// A No net Tuesday's assignment is never listed, so it isn't named.
		$none          = wfn4_tuesdays( 2, 3 )[2];
		$rota[ $none ] = array(
			'state'   => 'none',
			'call'    => '',
			'note'    => '',
			'wl_task' => 'Not shown',
			'wl_form' => '',
		);
		update_option( 'spk_rota', $rota );
		\spokares_opt_flush();
		assert_contains( 'Send a welfare message', \spokares_render_net( array( 'view' => 'winlink' ) ), 'set-up: the assignments are listed' );

		// Make the 3rd Tuesday the Winlink night instead.
		$out  = wfn4_save(
			static function ( array $f ): array {
				$f['nets']['week'][2] = 'regular';
				$f['nets']['week'][3] = 'winlink';
				return $f;
			}
		);
		$days = \spokares_fmt_date( $dates[0], 'day' ) . ' and ' . \spokares_fmt_date( $dates[1], 'day' );
		assert_count( 1, $out['notices'], 'one notice' );
		assert_same( 'warning', $out['notices'][0]['type'], 'amber, so it is read' );
		assert_contains( $days . ' are no longer Winlink nights, so their Winlink assignments are off the Exercises & events page. They are still on the Net Control Schedule: clear them or move them there.', $out['notices'][0]['text'], 'the notice names them' );
		assert_not_contains( \spokares_fmt_date( $none, 'day' ), $out['notices'][0]['text'], 'a No net Tuesday is not named' );
		assert_not_contains( \spokares_fmt_date( $third, 'day' ), $out['notices'][0]['text'], 'a new Winlink night is not named' );
		assert_same( 'Send a welfare message to AG7QP', \spokares_opt( 'spk_rota' )[ $dates[0] ]['wl_task'], 'the assignment is kept in the schedule' );
		assert_not_contains( 'Send a welfare message', \spokares_render_net( array( 'view' => 'winlink' ) ), 'and is off the site, as the notice says' );

		// A save that leaves the Winlink weeks alone names nothing.
		$out = wfn4_save(
			static function ( array $f ): array {
				$f['nets']['net_time'] = '19:30';
				return $f;
			}
		);
		assert_same( 'success', $out['notices'][0]['type'] ?? '', 'a plain save' );
		assert_not_contains( 'Winlink night', $out['notices'][0]['text'] ?? '', 'no Winlink sentence' );
	}
);

test(
	'N8: an offset or tone not in the list is refused with a sentence, and the stored one stays',
	function () {
		as_role( 'ares-net' );
		$radio = \spokares_opt( 'spk_radio' );
		$out   = wfn4_save(
			static function ( array $f ): array {
				$f['radio']['primary']['offset']   = '+1 MHz';
				$f['radio']['alternate']['tone']   = '99 Hz';
				$f['radio']['alternate']['offset'] = '+600 kHz';
				return $f;
			}
		);
		$now   = \spokares_opt( 'spk_radio' );
		assert_same( $radio['primary']['offset'], $now['primary']['offset'], 'the stored offset stays' );
		assert_same( $radio['alternate']['tone'], $now['alternate']['tone'], 'the stored alternate tone stays' );
		assert_same( '+600 kHz', $now['alternate']['offset'], 'a listed value is saved' );
		assert_same( 'Pick an offset from the list.', $out['errors']['p_offset'] ?? '', 'the offset sentence' );
		assert_same( 'Pick a tone from the list.', $out['errors']['a_tone'] ?? '', 'the tone sentence' );
		assert_same( 'warning', $out['notices'][0]['type'] ?? '', 'saved, except' );
		assert_same( 'Saved, except the offset and the alternate tone (outlined in red).', $out['notices'][0]['text'] ?? '', 'the notice names both' );

		ob_start();
		\spokares_net_details_page();
		$html = (string) ob_get_clean();
		assert_contains( '<span class="spk-error-text">Pick an offset from the list.</span>', $html, 'the sentence under the offset' );
	}
);
