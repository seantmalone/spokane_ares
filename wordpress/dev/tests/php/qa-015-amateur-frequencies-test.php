<?php
/**
 * Regression tests for QA-015 (PLAN §3.4, §5 "Never publish"): Net details
 * says "Amateur frequencies only. Never county, hospital, SHARES, 800 MHz or
 * channel numbers." and its error says "type an amateur frequency like
 * 147.300", but spokares_freq_ok() only checks the 000.000 shape and the
 * 806–870 MHz band. A hospital frequency (155.340 HEAR), a fire mutual-aid
 * frequency (154.280), a GMRS channel (462.550) or a number in no band at
 * all (999.999) saved with "Saved." and was printed on the members' hub
 * bar, How it works and Home. A frequency outside the amateur bands must be
 * held back like 147.3 or 851.012: the stored value stays, the typed text
 * is kept for the form, the field is outlined and the notice is not a plain
 * "Saved.".
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

/**
 * Start from the seeded amateur settings (147.300 / 146.880, PLAN §6), so
 * "the stored value is unchanged" means the amateur value survived, whatever
 * the site holds now. The framework restores the option after the test.
 */
function qa015_baseline(): array {
	$radio                      = \spokares_opt( 'spk_radio' );
	$radio['primary']['freq']   = '147.300';
	$radio['alternate']['freq'] = '146.880';
	$radio['alternate']['show'] = true;
	update_option( 'spk_radio', $radio );
	return \spokares_opt( 'spk_radio' );
}

/**
 * The Net details form fields as the screen posts them, with the primary
 * and alternate frequencies replaced.
 *
 * @param string $freq  Primary frequency to save.
 * @param string $afreq Alternate frequency to save.
 */
function qa015_net_fields( string $freq, string $afreq ): array {
	$radio = \spokares_opt( 'spk_radio' );
	$nets  = \spokares_opt( 'spk_nets' );
	return array(
		'radio' => array(
			'primary'   => array(
				'call'   => $radio['primary']['call'],
				'freq'   => $freq,
				'offset' => $radio['primary']['offset'],
				'tone'   => $radio['primary']['tone'],
			),
			'alternate' => array(
				'freq'        => $afreq,
				'offset'      => $radio['alternate']['offset'],
				'tone'        => $radio['alternate']['tone'],
				'show'        => $radio['alternate']['show'] ? '1' : '',
				'needs_check' => $radio['alternate']['needs_check'] ? '1' : '',
			),
		),
		'nets'  => array(
			'net_time'       => $nets['net_time'],
			'gmrs_time'      => $nets['gmrs_time'],
			'winlink_nth'    => $nets['winlink_nth'],
			'simplex_nth'    => $nets['simplex_nth'],
			'gmrs_nth'       => $nets['gmrs_nth'],
			'needs_check'    => $nets['needs_check'] ? '1' : '',
			'winlink_howto'  => $nets['winlink_howto'],
			'open_slot_line' => $nets['open_slot_line'],
		),
	);
}

/**
 * Save Net details as the current user and return what the save left
 * behind: the stored radio option, the held input, and the notices.
 *
 * @param string $freq  Primary frequency.
 * @param string $afreq Alternate frequency.
 * @param string $msg   Message prefix.
 */
function qa015_save( string $freq, string $afreq, string $msg ): array {
	delete_transient( 'spokares_notices_' . get_current_user_id() );
	$res = post_form( 'spokares_save_net_details', qa015_net_fields( $freq, $afreq ) );
	assert_same( null, $res['die'], $msg . ': save refused' );
	assert_contains( 'page=spokares-net-details', (string) $res['redirect'], $msg . ': redirect back to the screen' );

	$key     = 'spokares_notices_' . get_current_user_id();
	$notices = get_transient( $key );
	delete_transient( $key );
	return array(
		'radio'    => \spokares_opt( 'spk_radio' ),
		'retained' => \spokares_retained( 'spokares-net-details' ),
		'notices'  => is_array( $notices ) ? $notices : array(),
	);
}

/**
 * Assert that a frequency field was held back: stored value unchanged,
 * typed value kept, an error for the field, and no plain "Saved." notice.
 *
 * @param array  $out    What qa015_save() returned.
 * @param string $field  'p_freq' or 'a_freq'.
 * @param string $typed  What was typed.
 * @param string $stored The value stored before the save.
 * @param string $msg    Message prefix.
 */
function qa015_assert_held( array $out, string $field, string $typed, string $stored, string $msg ): void {
	$which = 'p_freq' === $field ? 'primary' : 'alternate';
	assert_same( $stored, $out['radio'][ $which ]['freq'], $msg . ': non-amateur ' . $typed . ' was saved as the ' . $which . ' frequency' );
	assert_true( isset( $out['retained']['errors'][ $field ] ), $msg . ': no error for ' . $field . ' (field not outlined)' );
	assert_same( $typed, $out['retained']['values'][ $field ] ?? null, $msg . ': typed ' . $typed . ' not kept for the form' );
	$texts = wp_list_pluck( $out['notices'], 'text' );
	assert_not_contains( 'Saved.', $texts, $msg . ': plain "Saved." notice for a held-back frequency' );
}

test(
	'a hospital frequency (155.340) is held back as the repeater frequency',
	function () {
		foreach ( array( 'ares-net', 'admin' ) as $role ) {
			as_role( $role );
			$before = qa015_baseline();
			$out    = qa015_save( '155.340', $before['alternate']['freq'], $role );
			qa015_assert_held( $out, 'p_freq', '155.340', $before['primary']['freq'], $role );
			assert_not_contains( '155.340', \spokares_render_net( array( 'view' => 'from-home' ) ), $role . ': Home "From home" card prints the hospital frequency' );
			assert_not_contains( '155.340', \spokares_render_net( array( 'view' => 'bar' ) ), $role . ': members hub bar prints the hospital frequency' );
		}
	}
);

test(
	'a fire mutual-aid frequency (154.280) is held back as the alternate frequency',
	function () {
		foreach ( array( 'ares-net', 'admin' ) as $role ) {
			as_role( $role );
			$before = qa015_baseline();
			$out    = qa015_save( $before['primary']['freq'], '154.280', $role );
			qa015_assert_held( $out, 'a_freq', '154.280', $before['alternate']['freq'], $role );
			assert_not_contains( '154.280', \spokares_render_net( array( 'view' => 'settings' ) ), $role . ': How it works settings box prints the fire frequency' );
		}
	}
);

test(
	'GMRS (462.550), public-safety UHF (460.025) and no-band (999.999) primaries are held back',
	function () {
		as_role( 'ares-net' );
		foreach ( array( '462.550', '460.025', '999.999' ) as $freq ) {
			$before = qa015_baseline();
			$out    = qa015_save( $freq, $before['alternate']['freq'], $freq );
			qa015_assert_held( $out, 'p_freq', $freq, $before['primary']['freq'], $freq );
		}
	}
);

test(
	'the held-back non-amateur frequency is outlined on the Net details screen',
	function () {
		as_role( 'ares-net' );
		$before = qa015_baseline();
		delete_transient( 'spokares_notices_' . get_current_user_id() );
		post_form( 'spokares_save_net_details', qa015_net_fields( '155.340', $before['alternate']['freq'] ) );
		ob_start();
		\spokares_net_details_page();
		$html = (string) ob_get_clean();
		assert_matches( '/<input[^>]*id="spk-p-freq"[^>]*class="[^"]*spk-field-error/', $html, 'frequency field not outlined' );
		assert_matches( '/<input[^>]*id="spk-p-freq"[^>]*value="155\.340"/', $html, 'typed frequency not kept in the field' );
		assert_contains( 'amateur frequency', $html, 'the "type an amateur frequency" sentence is missing' );
	}
);

test(
	'control: amateur repeater frequencies on 2 m, 1.25 m and 70 cm still save',
	function () {
		as_role( 'ares-net' );
		qa015_baseline();
		foreach ( array( array( '146.940', '147.260' ), array( '224.500', '146.880' ), array( '444.100', '442.525' ), array( '147.300', '' ) ) as $pair ) {
			$out = qa015_save( $pair[0], $pair[1], $pair[0] . '/' . $pair[1] );
			assert_same( $pair[0], $out['radio']['primary']['freq'], $pair[0] . ': amateur primary not saved' );
			assert_same( $pair[1], $out['radio']['alternate']['freq'], $pair[1] . ': amateur alternate not saved' );
			assert_count( 0, $out['retained']['errors'], $pair[0] . '/' . $pair[1] . ': unexpected field errors' );
		}
	}
);

test(
	'control: the 800 MHz band and a bad shape are still held back',
	function () {
		as_role( 'ares-net' );
		foreach ( array( '851.012', '147.3' ) as $freq ) {
			$before = qa015_baseline();
			$out    = qa015_save( $freq, $before['alternate']['freq'], $freq );
			qa015_assert_held( $out, 'p_freq', $freq, $before['primary']['freq'], $freq );
		}
	}
);
