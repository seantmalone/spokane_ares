<?php
/**
 * Regression tests for QA-009 (PLAN §5.5 "a file saved in a draft isn't at a
 * guessable address"; §4.1 core roles get no access to documents). A draft
 * document's file is uploaded through the Documents form with a random
 * suffix, attached to the document (post_parent) and stored in spk_file, so
 * its address should reach only people who can edit that document.
 *
 * Core's admin-ajax handlers only check upload_files, which Authors and core
 * Editors have:
 *
 * - query-attachments (the media window's list, no nonce) returns the
 *   draft's attachment with its url and uploadedTo = the draft;
 * - get-attachment&id=N (no nonce) returns the same for one attachment.
 *
 * And a core Editor has edit_post, read_post and delete_post on the
 * attachment itself (core maps them to edit_others_posts and friends), so
 * REST /wp/v2/media/N answers 200 with the file address and the attachment's
 * edit screen (post.php?post=N&action=edit) opens and shows it. The file is
 * then public at that address (HTTP 200, signed out).
 *
 * Correct behaviour: an Author or a core Editor (who can't edit the draft
 * document) never gets the draft's file address from these routes; an
 * administrator and an ARES Editor (who can) still do.
 *
 * The ajax handlers are fired as admin-ajax.php fires them: core's handler on
 * wp_ajax_<action> at priority 1, with wp_doing_ajax() true, so a guard at
 * priority 0 or an ajax_query_attachments_args filter takes effect.
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

/**
 * As an administrator, save a draft document with an uploaded PDF the way the
 * Documents form stores one (random suffix, attached to the document, and in
 * spk_file). Returns array( document ID, attachment ID, file URL ).
 */
function qa_009_draft_with_file(): array {
	as_role( 'admin' );
	$doc = create_post(
		array(
			'post_type'   => 'spk_document',
			'post_title'  => 'QA-009 draft with a file',
			'post_status' => 'draft',
		)
	);
	$up  = wp_upload_bits( 'qa-009-draft-' . bin2hex( random_bytes( 4 ) ) . '.pdf', null, "%PDF-1.4\n% spokares QA-009 draft file\n%%EOF\n" );
	if ( ! empty( $up['error'] ) ) {
		fail( 'wp_upload_bits: ' . $up['error'] );
	}
	$att = wp_insert_attachment(
		array(
			'post_title'     => 'QA-009 draft file',
			'post_mime_type' => 'application/pdf',
			'post_status'    => 'inherit',
		),
		$up['file'],
		$doc,
		true
	);
	if ( is_wp_error( $att ) ) {
		fail( 'wp_insert_attachment: ' . $att->get_error_message() );
	}
	update_post_meta( $doc, 'spk_file', (int) $att );
	$url = (string) wp_get_attachment_url( (int) $att );
	assert_not_same( '', $url, 'setup: the attachment has a URL' );
	as_anonymous();
	return array( $doc, (int) $att, $url );
}

/**
 * Fire a core admin-ajax action as admin-ajax.php would, for the current
 * user, and return the decoded JSON reply (null when none) plus the raw
 * call_request() result.
 *
 * @param string $action The ajax action (query-attachments, get-attachment).
 * @param array  $fields Posted fields besides action.
 */
function qa_009_ajax( string $action, array $fields ): array {
	require_once ABSPATH . 'wp-admin/includes/ajax-actions.php';
	$core = 'wp_ajax_' . str_replace( '-', '_', $action );
	add_action( 'wp_ajax_' . $action, $core, 1 ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- core's admin-ajax hook, hooked as admin-ajax.php does.
	add_filter( 'wp_doing_ajax', '__return_true' );
	try {
		$r = call_request(
			'POST',
			array(),
			array_merge( array( 'action' => $action ), $fields ),
			static function () use ( $action ) {
				do_action( 'wp_ajax_' . $action ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- core's admin-ajax hook, fired as admin-ajax.php does.
			}
		);
	} finally {
		remove_filter( 'wp_doing_ajax', '__return_true' );
		remove_action( 'wp_ajax_' . $action, $core, 1 );
	}
	$json = json_decode( (string) $r['output'], true );
	return array( is_array( $json ) ? $json : null, $r );
}

/**
 * The attachments a query-attachments reply lists, as ID => URL (empty when
 * the reply is a refusal).
 *
 * @param array|null $json Decoded reply.
 */
function qa_009_listed( ?array $json ): array {
	$out = array();
	if ( ! $json || empty( $json['success'] ) || ! is_array( $json['data'] ?? null ) ) {
		return $out;
	}
	foreach ( $json['data'] as $a ) {
		if ( is_array( $a ) && isset( $a['id'] ) ) {
			$out[ (int) $a['id'] ] = (string) ( $a['url'] ?? '' );
		}
	}
	return $out;
}

/**
 * Fail if a reply (decoded JSON) carries the file's URL or names the draft as
 * the file's parent anywhere.
 *
 * @param mixed  $json  Decoded reply.
 * @param string $url   The draft's file URL.
 * @param string $label Message prefix.
 */
function qa_009_assert_no_url( $json, string $url, string $label ): void {
	$flat = (string) wp_json_encode( $json, JSON_UNESCAPED_SLASHES );
	assert_not_contains( $url, $flat, $label . ': the reply must not carry the draft\'s file address' );
	assert_not_contains( wp_basename( $url ), $flat, $label . ': the reply must not carry the draft\'s file name' );
}

test(
	'an Author and a core Editor are not shown a draft document\'s file by admin-ajax query-attachments',
	function () {
		list( $doc, $att, $url ) = qa_009_draft_with_file();
		foreach ( array( 'author', 'core-editor' ) as $role ) {
			as_role( $role );
			assert_true( current_user_can( 'upload_files' ), $role . ' has upload_files (so core\'s own check lets the request through)' );
			assert_false( current_user_can( 'edit_post', $doc ), $role . ' cannot edit the draft document' );
			list( $json, $r ) = qa_009_ajax(
				'query-attachments',
				array(
					'query' => array(
						'posts_per_page' => 100,
						'orderby'        => 'date',
						'order'          => 'DESC',
					),
				)
			);
			assert_true( null !== $json, $role . ': query-attachments answered with JSON (got: ' . substr( (string) $r['output'], 0, 120 ) . ')' );
			$listed = qa_009_listed( $json );
			assert_false( isset( $listed[ $att ] ), $role . ': query-attachments lists the draft\'s attachment #' . $att . ' (' . ( $listed[ $att ] ?? '' ) . ')' );
			qa_009_assert_no_url( $json, $url, $role . ' query-attachments' );

			// The other query keys core accepts from the request: the media
			// window's "Uploaded to this post" filter, a search for the name,
			// and asking for the attachment by number (post__in wins over
			// post__not_in in WP_Query).
			list( $json ) = qa_009_ajax( 'query-attachments', array( 'query' => array( 'post_parent' => $doc ) ) );
			qa_009_assert_no_url( $json, $url, $role . ' query-attachments post_parent=' . $doc );
			list( $json ) = qa_009_ajax( 'query-attachments', array( 'query' => array( 's' => 'qa-009-draft' ) ) );
			qa_009_assert_no_url( $json, $url, $role . ' query-attachments s=qa-009-draft' );
			list( $json ) = qa_009_ajax( 'query-attachments', array( 'query' => array( 'post__in' => array( $att ) ) ) );
			qa_009_assert_no_url( $json, $url, $role . ' query-attachments post__in=' . $att );
		}
	}
);

test(
	'an Author and a core Editor get no draft document\'s file from admin-ajax get-attachment',
	function () {
		list( , $att, $url ) = qa_009_draft_with_file();
		foreach ( array( 'author', 'core-editor' ) as $role ) {
			as_role( $role );
			list( $json, $r ) = qa_009_ajax( 'get-attachment', array( 'id' => $att ) );
			$ok               = is_array( $json ) && ! empty( $json['success'] );
			assert_false( $ok, $role . ': get-attachment&id=' . $att . ' succeeded (url: ' . ( $ok ? (string) ( $json['data']['url'] ?? '' ) : '' ) . ')' );
			qa_009_assert_no_url( $json, $url, $role . ' get-attachment' );
			assert_not_contains( $url, str_replace( '\\/', '/', (string) $r['output'] ), $role . ': get-attachment output' );
		}
	}
);

test(
	'a core Editor can\'t read a draft document\'s file over REST or open its attachment edit screen',
	function () {
		list( $doc, $att, $url ) = qa_009_draft_with_file();
		as_role( 'author' );
		$res = rest( 'GET', '/wp/v2/media/' . $att );
		assert_true( $res->is_error(), 'author: REST GET /wp/v2/media/' . $att . ' is refused (it already is)' );

		as_role( 'core-editor' );
		assert_false( current_user_can( 'edit_post', $doc ), 'core-editor cannot edit the draft document' );
		$res = rest( 'GET', '/wp/v2/media/' . $att );
		assert_true( $res->is_error(), 'core-editor: REST GET /wp/v2/media/' . $att . ' answered ' . $res->get_status() . ' with source_url ' . (string) ( $res->get_data()['source_url'] ?? '' ) );
		qa_009_assert_no_url( $res->get_data(), $url, 'core-editor REST GET /wp/v2/media/' . $att );

		$list = rest(
			'GET',
			'/wp/v2/media',
			array(
				'per_page' => 100,
				'parent'   => array( $doc ),
			)
		);
		qa_009_assert_no_url( $list->get_data(), $url, 'core-editor REST GET /wp/v2/media?parent=' . $doc );
		$list = rest( 'GET', '/wp/v2/media', array( 'per_page' => 100 ) );
		qa_009_assert_no_url( $list->get_data(), $url, 'core-editor REST GET /wp/v2/media' );

		// post.php?post=N&action=edit opens for anyone with edit_post on the
		// attachment, and shows its file address.
		assert_false( current_user_can( 'edit_post', $att ), 'core-editor: edit_post on the draft\'s attachment (its edit screen shows the file address)' );
	}
);

test(
	'an administrator and an ARES Editor still see a draft document\'s file in the media window routes',
	function () {
		list( $doc, $att, $url ) = qa_009_draft_with_file();
		foreach ( array( 'admin', 'ares-editor' ) as $role ) {
			as_role( $role );
			assert_true( current_user_can( 'edit_post', $doc ), $role . ' can edit the draft document' );
			list( $json ) = qa_009_ajax( 'query-attachments', array( 'query' => array( 'posts_per_page' => 100 ) ) );
			$listed       = qa_009_listed( $json );
			assert_true( isset( $listed[ $att ] ), $role . ': query-attachments lists the draft\'s attachment' );
			assert_same( $url, $listed[ $att ] ?? '', $role . ': with its URL' );
			list( $json ) = qa_009_ajax( 'get-attachment', array( 'id' => $att ) );
			assert_true( is_array( $json ) && ! empty( $json['success'] ), $role . ': get-attachment succeeds' );
			assert_same( $url, (string) ( $json['data']['url'] ?? '' ), $role . ': get-attachment URL' );
		}
	}
);
