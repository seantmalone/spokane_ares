<?php
/**
 * Regression tests for QA-051 (PLAN §3.4, the Events and Documents admin
 * screens): text typed on those forms lost its backslashes on save. An event
 * Short line "Save to C:\ARES\forms" was stored as "Save to C:ARESforms", a
 * task "Open C:\Winlink\Messages" as "Open C:WinlinkMessages", and a document
 * note "Save in C:\ARES" as "Save in C:ARES". Duplicate did the same to the
 * copy's fields.
 *
 * The cause: spokares_event_submitted() and spokares_document_submitted()
 * wp_unslash( $_POST ), and update_post_meta() unslashes its value again, so
 * a single backslash is stripped. Duplicate passes get_post_meta()'s raw
 * value (and the source title) to update_post_meta() and wp_insert_post(),
 * which both expect slashed data. The rota and the other settings screens
 * keep backslashes; these forms must too: what the editor types is stored
 * as typed.
 *
 * The saves run as WordPress's post.php does it: edit_post() with the edit
 * form's fields in $_POST (slashed, as WordPress has them), which fires
 * wp_insert_post_data and save_post_{type}.
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

/**
 * A day this many days after the site's "today".
 *
 * @param int $days Days.
 */
function qa051_day( int $days ): string {
	return gmdate( 'Y-m-d', (int) strtotime( spokares_today() . ' +' . $days . ' days' ) );
}

/**
 * Click Save Draft on a post's edit screen with these form fields, as the
 * browser sends the form. Runs edit_post() as post.php does, as the current
 * user.
 *
 * @param int    $id     Post.
 * @param string $type   spk_event or spk_document.
 * @param array  $fields The form's own fields (unslashed, as typed).
 */
function qa051_save_draft( int $id, string $type, array $fields ): array {
	$nonce = 'spk_event' === $type
		? array( 'spokares_event_nonce' => wp_create_nonce( 'spokares_event_meta' ) )
		: array( 'spokares_document_nonce' => wp_create_nonce( 'spokares_document_meta' ) );
	$post  = array_merge(
		array(
			'action'               => 'editpost',
			'originalaction'       => 'editpost',
			'post_ID'              => (string) $id,
			'post_type'            => $type,
			'post_status'          => 'draft',
			'original_post_status' => 'draft',
			'save'                 => 'Save Draft',
			'_wpnonce'             => wp_create_nonce( 'update-post_' . $id ),
		),
		$nonce,
		$fields
	);
	$res   = call_request(
		'POST',
		array(),
		$post,
		static function () {
			return edit_post();
		}
	);
	assert_same( null, $res['die'], 'edit_post() did not wp_die()' );
	assert_same( null, $res['redirect'], 'edit_post() did not redirect' );
	assert_same( $id, (int) $res['returned'], 'edit_post() returned the post' );
	clean_post_cache( $id );
	return $res;
}

test(
	'an ARES Editor saving an event keeps the backslashes typed in its text fields',
	function () {
		as_role( 'ares-editor' );
		$id = create_post(
			array(
				'post_type'   => 'spk_event',
				'post_status' => 'draft',
				'post_title'  => 'QA051 draft',
				'post_author' => get_current_user_id(),
			)
		);
		qa051_save_draft(
			$id,
			'spk_event',
			array(
				'post_title'    => 'QA051 Winlink drill',
				'spk_kind'      => 'exercise',
				'spk_date_mode' => 'date',
				'spk_start'     => qa051_day( 10 ),
				'spk_all_day'   => '1',
				'spk_summary'   => 'Save to C:\ARES\forms',
				'spk_where'     => 'EOC room \ B',
				'spk_tasks'     => "Open C:\\Winlink\\Messages\nSend a check-in",
			)
		);
		assert_same( 'exercise', (string) get_post_meta( $id, 'spk_kind', true ), 'the form was saved by the plugin handler' );
		assert_same( 'QA051 Winlink drill', get_post_field( 'post_title', $id ), 'the title was saved' );
		assert_same( 'Save to C:\ARES\forms', (string) get_post_meta( $id, 'spk_summary', true ), 'Short description stored as typed' );
		assert_same( 'EOC room \ B', (string) get_post_meta( $id, 'spk_where', true ), 'Where stored as typed' );
		assert_same( "Open C:\\Winlink\\Messages\nSend a check-in", (string) get_post_meta( $id, 'spk_tasks', true ), 'task list stored as typed' );
	}
);

test(
	'an ARES Editor saving a document keeps the backslashes typed in its text fields',
	function () {
		as_role( 'ares-editor' );
		$id = create_post(
			array(
				'post_type'   => 'spk_document',
				'post_status' => 'draft',
				'post_title'  => 'QA051 document',
				'post_author' => get_current_user_id(),
			)
		);
		qa051_save_draft(
			$id,
			'spk_document',
			array(
				'post_title'  => 'QA051 Winlink form',
				'spk_section' => 'forms',
				'spk_source'  => 'soon',
				'spk_version' => 'v2 \ 2026',
				'spk_note'    => 'Save in C:\ARES',
			)
		);
		assert_same( 'soon', (string) get_post_meta( $id, 'spk_source', true ), 'the form was saved by the plugin handler' );
		assert_same( 'Save in C:\ARES', (string) get_post_meta( $id, 'spk_note', true ), 'note stored as typed' );
		assert_same( 'v2 \ 2026', (string) get_post_meta( $id, 'spk_version', true ), 'version stored as typed' );
	}
);

test(
	'Make a copy copies an event\'s backslashes as they are stored',
	function () {
		as_role( 'ares-editor' );
		$src = create_post(
			array(
				'post_type'   => 'spk_event',
				'post_status' => 'publish',
				'post_title'  => 'QA051 drill \ Winlink',
				'post_author' => get_current_user_id(),
			)
		);
		assert_same( 'QA051 drill \ Winlink', get_post_field( 'post_title', $src ), 'the source title is stored with its backslash' );
		// Stored as a correct save leaves them (update_post_meta() takes slashed data).
		update_post_meta( $src, 'spk_kind', 'exercise' );
		update_post_meta( $src, 'spk_start', qa051_day( 12 ) );
		update_post_meta( $src, 'spk_summary', wp_slash( 'Save to C:\ARES\forms' ) );
		update_post_meta( $src, 'spk_tasks', wp_slash( 'Open C:\Winlink\Messages' ) );
		assert_same( 'Save to C:\ARES\forms', (string) get_post_meta( $src, 'spk_summary', true ), 'the source Short description is stored with its backslashes' );

		$res = get_action( 'spokares_duplicate_event', array( 'post' => $src ), array( 'nonce_action' => 'spokares_duplicate_event_' . $src ) );
		assert_same( null, $res['die'], 'Make a copy did not wp_die()' );
		assert_matches( '/post\.php\?post=\d+&action=edit/', (string) $res['redirect'], 'Make a copy redirected to the copy' );
		preg_match( '/post=(\d+)/', (string) $res['redirect'], $m );
		$copy = (int) $m[1];
		assert_not_same( $src, $copy, 'a new event' );
		clean_post_cache( $copy );

		assert_same( 'exercise', (string) get_post_meta( $copy, 'spk_kind', true ), 'the copy has the fields' );
		assert_same( 'Save to C:\ARES\forms', (string) get_post_meta( $copy, 'spk_summary', true ), 'copy\'s Short description as stored' );
		assert_same( 'Open C:\Winlink\Messages', (string) get_post_meta( $copy, 'spk_tasks', true ), 'copy\'s task list as stored' );
		assert_same( 'QA051 drill \ Winlink', get_post_field( 'post_title', $copy ), 'copy\'s title as stored' );
	}
);
