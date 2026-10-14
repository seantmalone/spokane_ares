<?php
/**
 * Regression tests for the workflow review's finding D11 (ux/workflows,
 * "Pull this file now skips the 'in use' check, and its banner never
 * clears"; PLAN §5.5).
 *
 * Replace and Trash keep a file another document or page uses; Pull, the
 * emergency tool, deletes it anyway. That stays (it is for a file that must
 * leave the web now), but the question before it and the notice after it
 * now name the documents and pages whose links will break. The "The file was
 * pulled from the web by X" banner is cleared when a new file is accepted
 * and published: that needs a real HTTP upload, so it is tested in the
 * browser (dev/tests/e2e/wf-docs.test.mjs); here a save without a new file
 * keeps the banner.
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

/**
 * A published upload document whose file another document also uses.
 * Returns array( document, the other document, attachment ).
 */
function wf_d11_shared(): array {
	list( $doc, $att ) = qa_018_published_upload( 'admin', 'WF-D11 net script' );
	$other             = create_post(
		array(
			'post_type'  => 'spk_document',
			'post_title' => 'WF-D11 net script copy',
		)
	);
	update_post_meta( $other, 'spk_file', $att );
	return array( $doc, $other, $att );
}

test(
	'Pull this file now asks with the documents and pages that use the file, and only then',
	function () {
		list( $doc ) = wf_d11_shared();
		as_role( 'admin' );
		$actions = \spokares_document_row_actions( array(), get_post( $doc ) );
		assert_matches( '#data-spk-ask="[^"]*It is also used by WF-D11 net script copy: their links to it will stop working\.#u', $actions['pull'], 'the question names the other document' );

		list( $alone ) = qa_018_published_upload( 'admin', 'WF-D11 alone' );
		as_role( 'admin' );
		$actions = \spokares_document_row_actions( array(), get_post( $alone ) );
		assert_not_contains( 'also used by', $actions['pull'], 'a file nothing else uses gets the plain question' );
		assert_matches( '#data-spk-ask="[^"]*can’t be undone#u', $actions['pull'], 'the plain question is unchanged' );
	}
);

test(
	'after Pull the notice names the documents whose links no longer work',
	function () {
		list( $doc, , $att ) = wf_d11_shared();
		as_role( 'admin' );
		delete_transient( 'spokares_notices_' . get_current_user_id() );
		get_action( 'spokares_pull_file', array( 'post' => $doc ), array( 'nonce_action' => 'spokares_pull_file_' . $doc ) );
		clean_post_cache( $att );
		assert_same( null, get_post( $att ), 'Pull still deletes the file' );
		$queued = get_transient( 'spokares_notices_' . get_current_user_id() );
		$queued = is_array( $queued ) ? $queued : array();
		assert_count( 1, $queued, 'one notice' );
		assert_same( 'warning', $queued[0]['type'], 'a warning, not a plain success' );
		assert_same( 'The file is off the web and the document is a draft marked “Soon”. Its links on WF-D11 net script copy no longer work: fix them there.', $queued[0]['text'], 'the notice' );
		assert_true( is_array( get_post_meta( $doc, 'spk_pulled', true ) ), 'who and when are recorded' );
	}
);

test(
	'the "pulled" banner stays after a save without a new file',
	function () {
		list( $doc ) = qa_018_published_upload( 'admin', 'WF-D11 banner' );
		as_role( 'admin' );
		get_action( 'spokares_pull_file', array( 'post' => $doc ), array( 'nonce_action' => 'spokares_pull_file_' . $doc ) );
		$fields                = uxdoc_fields( $doc );
		$fields['spk_version'] = 'v2';
		uxdoc_click( $doc, $fields, 'publish' );
		assert_true( is_array( get_post_meta( $doc, 'spk_pulled', true ) ), 'no new file: the banner stays' );
		$html = uxdoc_render( '\spokares_document_form_fields', get_post( $doc ) );
		assert_contains( 'The file was pulled from the web by', $html, 'the banner' );
	}
);
