<?php
/**
 * Regression tests for three small findings of the workflow review on the
 * document form (ux/workflows):
 *
 * - D9 "Without JavaScript a file can't be uploaded": the file box was
 *   printed disabled until the script unlocked it. It is now printed enabled
 *   (the script still locks it until "I checked this file" is ticked, and
 *   the save still refuses a file without the tick).
 * - D10 "After 'Saved, except…' the form shows text the site doesn't have":
 *   one line above the form now says the red boxes show the typing and the
 *   site still shows the saved version.
 * - D5 "'Most used' means two different things": the per-document tick is
 *   now "Show under the Most used filter" (the data key, spk_most_used, is
 *   the same).
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

test(
	'D9: the file box is printed enabled; a file without the tick is still refused',
	function () {
		$doc  = uxdoc_published( 'WF-D9 upload', 'upload' );
		$html = uxdoc_render( '\spokares_document_form_fields', get_post( $doc ) );
		assert_matches( '#<input type="file" id="spk-upload" name="spk_upload"[^>]*>#', $html, 'the file box' );
		preg_match( '#<input type="file" id="spk-upload"[^>]*>#', $html, $m );
		assert_false( (bool) preg_match( '#\sdisabled[\s>=]#', $m[0] ), 'the file box is not disabled in the HTML: ' . $m[0] );
		$why = \spokares_document_upload_problem(
			array(
				'error'    => UPLOAD_ERR_OK,
				'tmp_name' => '',
				'name'     => 'x.pdf',
			),
			false
		);
		assert_same( 'Tick “I checked this file” first, then choose the file again.', $why, 'the save refuses a file without the tick' );
	}
);

test(
	'D10: held typing on a published document gets one line above the form, and only then',
	function () {
		$doc = uxdoc_published( 'WF-D10 held', 'link' );
		as_role( 'ares-editor' );
		$line = 'The boxes outlined in red show what you typed. The site still shows the saved version.';
		assert_not_contains( $line, uxdoc_render( '\spokares_document_form_fields', get_post( $doc ) ), 'no held typing: no line' );

		// "hospital net" in the note is held back on a published document.
		$fields             = uxdoc_fields( $doc );
		$fields['spk_note'] = 'Use on the hospital net';
		uxdoc_click( $doc, $fields );
		assert_same( array( 'warning: Saved, except the short note (outlined in red).' ), uxdoc_notices(), 'set-up: the note was held' );
		\spokares_document_retained( $doc, true ); // A new request reads the held typing afresh.
		$html = uxdoc_render( '\spokares_document_form_fields', get_post( $doc ) );
		assert_contains( $line, $html, 'the line' );
		assert_true( strpos( $html, $line ) < strpos( $html, 'name="spk_section"' ), 'above the boxes' );
	}
);

test(
	'D5: the per-document tick reads "Show under the Most used filter" and still saves spk_most_used',
	function () {
		$doc  = post_id( 'spk_document', 'ics-309' );
		$html = uxdoc_render( '\spokares_document_form_fields', get_post( $doc ) );
		assert_contains( '<input type="checkbox" name="spk_most_used" value="1" > Show under the Most used filter</label>', $html, 'the tick' );
	}
);
