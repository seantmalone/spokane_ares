<?php
/**
 * Regression tests for workflow finding N5 (ux/workflows/content.py): the
 * typing a settings screen refused was held for 5 minutes and read once, so
 * a reload after "Saved, except…", or a short wait, lost it. Now it stays
 * until the next save of that screen, or for 24 hours.
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

/**
 * Seconds until a held-typing transient expires (0 when there is none).
 *
 * @param string $screen Screen slug.
 */
function wfn5_ttl( string $screen ): int {
	$timeout = (int) get_option( '_transient_timeout_spokares_retain_' . get_current_user_id() . '_' . $screen, 0 );
	return $timeout ? $timeout - time() : 0;
}

/**
 * Save Net Control Schedule rows; drop the notices.
 *
 * @param array $rows Date => fields.
 */
function wfn5_rota_save( array $rows ): void {
	post_form(
		'spokares_save_rota',
		array(
			'weeks' => '13',
			'rota'  => $rows,
		)
	);
	\spokares_unlock_option( 'spk_rota' );
	\spokares_opt_flush();
	delete_transient( 'spokares_notices_' . get_current_user_id() );
}

/**
 * The screen as the current user sees it.
 */
function wfn5_rota_screen(): string {
	$res = call_request(
		'GET',
		array( 'page' => 'spokares-rota' ),
		array(),
		static function () {
			\spokares_rota_page();
		}
	);
	return (string) $res['output'];
}

test(
	'Net Control Schedule: refused typing survives two views and lasts a day, then the next save clears it',
	function () {
		as_role( 'ares-editor' );
		$date = '';
		foreach ( array_slice( \spokares_rota_rows( 13 ), 1 ) as $row ) {
			if ( 'tbd' === $row['state'] && '' === $date ) {
				$date = $row['date'];
			}
		}
		$h = \spokares_rota_hash( null );
		wfn5_rota_save(
			array(
				$date => array(
					'h'     => $h,
					'state' => 'call',
					'call'  => 'Frank',
				),
			)
		);
		$ttl = wfn5_ttl( 'spokares-rota' );
		assert_true( $ttl > DAY_IN_SECONDS - 120 && $ttl <= DAY_IN_SECONDS, 'held for 24 hours (' . $ttl . ' s)' );
		assert_contains( 'value="Frank"', wfn5_rota_screen(), 'first view: the typing is in the box' );
		assert_contains( 'value="Frank"', wfn5_rota_screen(), 'a reload: still there' );
		assert_same( 'Frank', \spokares_retained( 'spokares-rota' )['values'][ $date ]['call'] ?? '', 'reading it does not clear it' );

		wfn5_rota_save(
			array(
				$date => array(
					'h'     => $h,
					'state' => 'call',
					'call'  => 'K7ABC',
				),
			)
		);
		assert_same( array(), \spokares_retained( 'spokares-rota' )['values'], 'the next save that holds nothing clears it' );
		assert_not_contains( 'value="Frank"', wfn5_rota_screen(), 'and the screen shows the saved row' );
	}
);

test(
	'Net Settings and Cancel or Move: refused typing stays until the next save of that screen',
	function () {
		as_role( 'ares-net' );
		$nets                       = \spokares_opt( 'spk_nets' );
		$form                       = static function (): array {
			ob_start();
			\spokares_net_details_page();
			preg_match( '/<form\b.*?<\/form>/is', (string) ob_get_clean(), $m );
			// The fields a browser sends (ux-net-control-schedule-test.php).
			return uxncs_browser_fields( $m[0] ?? '' );
		};
		$fields                     = $form();
		$fields['nets']['net_time'] = '25:99';
		post_form( 'spokares_save_net_details', $fields );
		\spokares_opt_flush();
		assert_same( '25:99', \spokares_retained( 'spokares-net-details' )['values']['net_time'] ?? '', 'held' );
		$fields = $form();
		assert_same( '25:99', $fields['nets']['net_time'] ?? '', 'the screen shows it' );
		assert_same( '25:99', \spokares_retained( 'spokares-net-details' )['values']['net_time'] ?? '', 'still held after the screen is drawn' );
		$fields['nets']['net_time'] = $nets['net_time'];
		post_form( 'spokares_save_net_details', $fields );
		\spokares_opt_flush();
		assert_same( array(), \spokares_retained( 'spokares-net-details' )['values'], 'cleared by the next save' );

		// The retain helper itself: empty values and errors clear.
		\spokares_retain( 'spokares-meetings', array( 'x' => array( 'note' => 'typed' ) ), array( 'x' => 'Problem.' ) );
		assert_same( 'Problem.', \spokares_retained( 'spokares-meetings' )['errors']['x'] ?? '', 'held' );
		post_form( 'spokares_save_meetings', array( 'm' => array() ) );
		\spokares_unlock_option( 'spk_meetings' );
		assert_same( array(), \spokares_retained( 'spokares-meetings' )['errors'], 'a Cancel or Move save clears it' );
	}
);
