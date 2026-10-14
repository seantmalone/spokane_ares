<?php
/**
 * Regression tests for workflow finding N3 (ux/workflows/content.py):
 * Undo on the Net Control Schedule put back the last save's rows with no
 * check and no question. Rows changed since (another way, such as an edit
 * outside the screen) were overwritten without a word, and one click undid
 * someone else's save.
 *
 * Now each save records every row's resulting hash; Undo and Redo put back
 * only rows still exactly as that save left them and name the others in the
 * notice; and Undo of someone else's save, of one more than a day old, or of
 * a newer save than the screen showed asks first, on the page.
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

/**
 * The notices queued for the current user (and clear them).
 */
function wfn3_notices(): array {
	$key     = 'spokares_notices_' . get_current_user_id();
	$notices = get_transient( $key );
	delete_transient( $key );
	return is_array( $notices ) ? $notices : array();
}

/**
 * Two Not posted yet Tuesdays past the first week.
 */
function wfn3_dates(): array {
	$out = array();
	foreach ( array_slice( \spokares_rota_rows( 13 ), 1 ) as $row ) {
		if ( 'tbd' === $row['state'] && '' === $row['wl_task'] && count( $out ) < 2 ) {
			$out[] = $row['date'];
		}
	}
	if ( count( $out ) < 2 ) {
		fail( 'set-up: two Not posted yet Tuesdays' );
	}
	return $out;
}

/**
 * Save these Tuesdays as call signs (from Not posted yet) as the current user.
 *
 * @param array $calls Date => call sign.
 */
function wfn3_save( array $calls ): void {
	$rows = array();
	foreach ( $calls as $date => $call ) {
		$rows[ $date ] = array(
			'h'     => \spokares_rota_hash( null ),
			'state' => 'call',
			'call'  => $call,
		);
	}
	$res = post_form(
		'spokares_save_rota',
		array(
			'weeks' => '13',
			'rota'  => $rows,
		)
	);
	assert_same( null, $res['die'], 'the save was refused' );
	\spokares_unlock_option( 'spk_rota' );
	\spokares_opt_flush();
	wfn3_notices();
}

/**
 * Click Undo (the form posts what the screen showed), maybe after the question.
 *
 * @param array $fields Extra fields (confirm, prev_at).
 */
function wfn3_undo( array $fields = array() ): array {
	wfn3_notices();
	$res = post_form( 'spokares_undo_rota', array_merge( array( 'weeks' => '13' ), $fields ) );
	assert_same( null, $res['die'], 'Undo was refused' );
	\spokares_unlock_option( 'spk_rota' );
	\spokares_opt_flush();
	return array(
		'redirect' => (string) $res['redirect'],
		'notices'  => wfn3_notices(),
	);
}

/**
 * The screen as the current user sees it.
 *
 * @param array $get Query args.
 */
function wfn3_screen( array $get = array() ): string {
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
	'a save records each row as it left it, and Undo skips a row changed since and names it',
	function () {
		as_role( 'ares-editor' );
		list( $a, $b ) = wfn3_dates();
		wfn3_save(
			array(
				$a => 'K7ABC',
				$b => 'NZ2S',
			)
		);
		$prev = get_option( 'spk_rota_prev' );
		assert_same( \spokares_rota_hash( \spokares_opt( 'spk_rota' )[ $a ] ), $prev['after'][ $a ] ?? '', 'the hash of the row as the save left it' );

		// Changed since, outside the screen (an SSH edit writes no snapshot).
		$rota              = \spokares_opt( 'spk_rota' );
		$rota[ $b ]['call'] = 'AG7QP';
		update_option( 'spk_rota', $rota );
		\spokares_opt_flush();

		$res  = wfn3_undo();
		$rota = \spokares_opt( 'spk_rota' );
		assert_false( isset( $rota[ $a ] ), 'the untouched row is back as it was (Not posted yet)' );
		assert_same( 'AG7QP', $rota[ $b ]['call'] ?? '', 'the row changed since is left as it is' );
		assert_same( array( 'warning' ), wp_list_pluck( $res['notices'], 'type' ), 'one amber notice' );
		assert_same(
			'Undone: ' . \spokares_fmt_date( $a, 'day' ) . ' is back as it was. ' . \spokares_fmt_date( $b, 'day' ) . ' was changed since; left as is.',
			$res['notices'][0]['text'],
			'the notice names the row left alone'
		);

		// Redo puts back only the row the undo changed.
		$res = wfn3_undo();
		assert_same( 'K7ABC', \spokares_opt( 'spk_rota' )[ $a ]['call'] ?? '', 'Redo puts the saved row back' );
		assert_same( 'AG7QP', \spokares_opt( 'spk_rota' )[ $b ]['call'] ?? '', 'and leaves the other alone' );
		assert_same( array( 'Redone: ' . \spokares_fmt_date( $a, 'day' ) . ' is back as it was saved.' ), wp_list_pluck( $res['notices'], 'text' ), 'the Redo notice' );
	}
);

test(
	'Undo with every row changed since changes nothing and says so',
	function () {
		as_role( 'ares-editor' );
		list( $a ) = wfn3_dates();
		wfn3_save( array( $a => 'K7ABC' ) );
		$rota              = \spokares_opt( 'spk_rota' );
		$rota[ $a ]['note'] = 'Typed over SSH';
		update_option( 'spk_rota', $rota );
		\spokares_opt_flush();
		$res = wfn3_undo();
		assert_same( 'Typed over SSH', \spokares_opt( 'spk_rota' )[ $a ]['note'] ?? '', 'the row is left as it is' );
		assert_same( array( 'Nothing undone. ' . \spokares_fmt_date( $a, 'day' ) . ' was changed since; left as is.' ), wp_list_pluck( $res['notices'], 'text' ), 'the notice' );
	}
);

test(
	'Undo of someone else’s save asks first, on the page; Keep it changes nothing; Undo it undoes',
	function () {
		as_role( 'ares-net' );
		list( $a ) = wfn3_dates();
		wfn3_save( array( $a => 'K7ABC' ) );
		$who = \spokares_user_name( user_id( 'ares-net' ) );

		as_role( 'ares-editor' );
		$html = wfn3_screen();
		assert_matches( '#<a class="spk-undo" href="[^"]*undo=ask[^"]*">Undo</a>#', $html, 'Undo is a link to the question, not a submit' );
		assert_not_contains( 'spk-undo-ask', $html, 'no question until Undo is clicked' );

		// A request straight to the handler is sent to the question too.
		$res = wfn3_undo( array( 'prev_at' => (string) get_option( 'spk_rota_prev' )['at'] ) );
		assert_contains( 'undo=ask', $res['redirect'], 'the handler asks first' );
		assert_same( array(), $res['notices'], 'no notice' );
		assert_same( 'K7ABC', \spokares_opt( 'spk_rota' )[ $a ]['call'] ?? '', 'nothing undone yet' );

		$html = wfn3_screen( array( 'undo' => 'ask' ) );
		assert_contains( 'spk-undo-ask', $html, 'the question is on the page' );
		assert_contains( esc_html( 'Undo the save ' . $who . ' made on ' ), $html, 'it names who made the save' );
		assert_contains( esc_html( 'Someone else made that save.' ), $html, 'and why it asks' );
		assert_matches( '#<button type="submit" form="spk-rota-undo" name="confirm" value="1" class="button button-primary">Undo it</button>#', $html, 'Undo it submits the undo form' );
		assert_matches( '#<a class="button" href="[^"]*page=spokares-rota[^"]*">Keep it</a>#', $html, 'Keep it goes back to the screen' );
		assert_not_contains( 'confirm()', $html, 'no browser dialog' );

		$res = wfn3_undo(
			array(
				'confirm' => '1',
				'prev_at' => (string) get_option( 'spk_rota_prev' )['at'],
			)
		);
		assert_false( isset( \spokares_opt( 'spk_rota' )[ $a ] ), 'undone after Undo it' );
		assert_same( array( 'Undone: ' . \spokares_fmt_date( $a, 'day' ) . ' is back as it was.' ), wp_list_pluck( $res['notices'], 'text' ), 'the notice' );
	}
);

test(
	'Undo of my own save asks first when it is more than a day old, or when a newer save came after the screen was opened',
	function () {
		as_role( 'ares-editor' );
		list( $a ) = wfn3_dates();
		wfn3_save( array( $a => 'K7ABC' ) );
		$prev = get_option( 'spk_rota_prev' );
		assert_same( '', \spokares_rota_undo_reason( $prev ), 'my own fresh save: no question' );
		assert_matches( '#<button type="submit" form="spk-rota-undo" class="button-link spk-undo">Undo</button>#', wfn3_screen(), 'Undo submits at once' );

		$res = wfn3_undo( array( 'prev_at' => 'a different save' ) );
		assert_contains( 'undo=ask', $res['redirect'], 'a newer save than the screen showed asks first' );
		assert_same( 'K7ABC', \spokares_opt( 'spk_rota' )[ $a ]['call'] ?? '', 'nothing undone' );

		$prev['at'] = wp_date( 'c', time() - DAY_IN_SECONDS - 60 );
		update_option( 'spk_rota_prev', $prev, false );
		assert_same( 'It is more than a day old.', \spokares_rota_undo_reason( $prev ), 'the reason' );
		$res = wfn3_undo( array( 'prev_at' => $prev['at'] ) );
		assert_contains( 'undo=ask', $res['redirect'], 'an old save asks first' );
		assert_matches( '#<a class="spk-undo" href="[^"]*undo=ask#', wfn3_screen(), 'the title line links to the question' );
	}
);

test(
	'a snapshot from before the hashes were kept still undoes as it did',
	function () {
		as_role( 'ares-editor' );
		list( $a ) = wfn3_dates();
		wfn3_save( array( $a => 'K7ABC' ) );
		$prev = get_option( 'spk_rota_prev' );
		unset( $prev['after'] );
		update_option( 'spk_rota_prev', $prev, false );
		wfn3_undo();
		assert_false( isset( \spokares_opt( 'spk_rota' )[ $a ] ), 'undone' );
	}
);
