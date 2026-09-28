<?php
/**
 * Tests for the Documents and Most Used screens after the 2026-09-27 editor
 * review (ux/SPEC.md §3.6, §3.7):
 *
 * - the privacy tick is needed again for anything new going public (a new
 *   file, a new or changed web address), and not for a name or note edit;
 * - "Place in section" puts a document at the top, after another or at the
 *   end, and numbers the section 10, 20, 30 … without re-saving the others;
 * - every save of a document ends in one notice, and WordPress's own is
 *   dropped;
 * - a document that is one of the four Most Used buttons says so instead of
 *   offering "Also list under Most used";
 * - on Most Used, a different document gets no words (the site prints its
 *   name) unless words were typed, a swap keeps them, and each row's
 *   "Replace or change it" opens that document's form;
 * - the Documents list: five columns for editors, a Soon view, no "Mine",
 *   and site order by default.
 *
 * The document saves run as post.php does: edit_post() with the form's fields
 * in $_POST (and a chosen file in $_FILES), then redirect_post().
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

/**
 * A published, privacy-checked document made by the administrator. Signs the
 * administrator in; the caller signs in as the role it tests.
 *
 * @param string $title   Title.
 * @param string $source  upload, link or soon.
 * @param string $section Section slug.
 * @param int    $order   menu_order.
 */
function uxdoc_published( string $title, string $source, string $section = 'forms', int $order = 0 ): int {
	as_role( 'admin' );
	$id = create_post(
		array(
			'post_type'   => 'spk_document',
			'post_title'  => $title,
			'post_status' => 'publish',
			'menu_order'  => $order,
		)
	);
	wp_set_object_terms( $id, $section, 'spk_doc_cat', false );
	update_post_meta( $id, 'spk_source', $source );
	update_post_meta( $id, 'spk_privacy_ok', '1' );
	if ( 'link' === $source ) {
		update_post_meta( $id, 'spk_url', 'https://example.org/' . sanitize_title( $title ) );
		update_post_meta( $id, 'spk_source_label', 'example.org' );
	}
	if ( 'upload' === $source ) {
		$up = wp_upload_bits( 'uxdoc-' . bin2hex( random_bytes( 4 ) ) . '.pdf', null, "%PDF-1.4\n% spokares uxdoc file\n%%EOF\n" );
		if ( ! empty( $up['error'] ) ) {
			fail( 'wp_upload_bits: ' . $up['error'] );
		}
		$att = wp_insert_attachment(
			array(
				'post_title'     => $title . ' file',
				'post_mime_type' => 'application/pdf',
				'post_status'    => 'inherit',
			),
			$up['file'],
			$id,
			true
		);
		if ( is_wp_error( $att ) ) {
			fail( 'wp_insert_attachment: ' . $att->get_error_message() );
		}
		update_post_meta( $id, 'spk_file', (int) $att );
		update_post_meta( $id, 'spk_format', 'PDF' );
	}
	clean_post_cache( $id );
	return $id;
}

/**
 * The form's fields for a document as it is stored (what the browser sends
 * when nothing is changed, the privacy tick not ticked).
 *
 * @param int $id Document.
 */
function uxdoc_fields( int $id ): array {
	$post  = get_post( $id );
	$terms = get_the_terms( $id, 'spk_doc_cat' );
	return array(
		'post_title'       => html_entity_decode( $post->post_title, ENT_QUOTES, 'UTF-8' ),
		'spk_section'      => ( $terms && ! is_wp_error( $terms ) ) ? $terms[0]->slug : '',
		'spk_source'       => (string) get_post_meta( $id, 'spk_source', true ),
		'spk_url'          => (string) get_post_meta( $id, 'spk_url', true ),
		'spk_source_label' => (string) get_post_meta( $id, 'spk_source_label', true ),
		'spk_format'       => (string) get_post_meta( $id, 'spk_format', true ),
		'spk_version'      => (string) get_post_meta( $id, 'spk_version', true ),
		'spk_note'         => (string) get_post_meta( $id, 'spk_note', true ),
	);
}

/**
 * Click the Save box's button on the document's form, as post.php does:
 * edit_post(), then redirect_post(). Returns the call_request() result.
 *
 * @param int        $id     Document.
 * @param array      $fields The form's fields (uxdoc_fields() with changes).
 * @param string     $button 'save' (a published document), 'publish' or 'saveasdraft'.
 * @param array|null $file   A $_FILES['spk_upload'] entry, or null.
 */
function uxdoc_click( int $id, array $fields, string $button = 'save', ?array $file = null ): array {
	$post   = get_post( $id );
	$status = 'saveasdraft' === $button ? 'draft' : ( 'auto-draft' === $post->post_status ? 'draft' : $post->post_status );
	$fields = array_merge(
		array(
			'action'                  => 'editpost',
			'originalaction'          => 'editpost',
			'post_ID'                 => (string) $id,
			'post_type'               => 'spk_document',
			'post_author'             => (string) $post->post_author,
			'post_status'             => $status,
			'original_post_status'    => $post->post_status,
			$button                   => 'x',
			'_wpnonce'                => wp_create_nonce( 'update-post_' . $id ),
			'spokares_document_nonce' => wp_create_nonce( 'spokares_document_meta' ),
		),
		$fields
	);
	if ( 'auto-draft' === $post->post_status ) {
		$fields['auto_draft'] = '1';
	}
	// phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited, WordPress.Security.NonceVerification -- a simulated upload: the test sets $_FILES and puts the old one back below.
	$saved_files = $_FILES;
	$_FILES      = null === $file ? array() : array( 'spk_upload' => $file );
	try {
		$r = call_request(
			'POST',
			array(),
			$fields,
			static function () {
				$saved = edit_post();
				redirect_post( $saved );
			}
		);
	} finally {
		$_FILES = $saved_files;
	}
	// phpcs:enable WordPress.WP.GlobalVariablesOverride.Prohibited, WordPress.Security.NonceVerification
	assert_same( null, $r['die'], 'the save did not wp_die()' );
	clean_post_cache( $id );
	return $r;
}

/**
 * The notices queued for the current user, each as "type: text", then cleared.
 */
function uxdoc_notices(): array {
	$key     = 'spokares_notices_' . get_current_user_id();
	$notices = get_transient( $key );
	delete_transient( $key );
	return array_map( static fn( $n ) => (string) ( $n['type'] ?? '' ) . ': ' . (string) ( $n['text'] ?? '' ), is_array( $notices ) ? $notices : array() );
}

/**
 * A chosen PDF under the file box (not a real HTTP upload, so the form's
 * "didn't arrive" check refuses it once the tick lets it through).
 *
 * @param string $path The temporary file.
 */
function uxdoc_pdf( string $path ): array {
	file_put_contents( $path, "%PDF-1.4\n%%EOF\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- a throwaway temp file for the simulated upload.
	return array(
		'name'     => 'Net script v4.pdf',
		'type'     => 'application/pdf',
		'tmp_name' => $path,
		'error'    => UPLOAD_ERR_OK,
		'size'     => (int) filesize( $path ),
	);
}

/**
 * The HTML a function prints.
 *
 * @param callable $callback Function.
 * @param mixed    ...$args  Arguments.
 */
function uxdoc_render( callable $callback, ...$args ): string {
	ob_start();
	$callback( ...$args );
	return (string) ob_get_clean();
}

test(
	'the button line replaces "Also list under Most used" for a Most Used document, and editors get no Webmaster check',
	function () {
		as_role( 'ares-net' );
		$tiles  = \spokares_opt( 'spk_tiles' );
		$button = (int) $tiles[0]['doc'];
		assert_true( $button > 0, 'set-up: button 1 has a document' );
		$html = uxdoc_render( '\spokares_document_form_fields', get_post( $button ) );
		assert_contains( 'It’s button 1 on <a href="' . esc_url( admin_url( 'admin.php?page=spokares-tiles' ) ) . '">the Most Used screen</a>.', $html, 'the button line' );
		assert_not_contains( 'Also list under Most used', $html, 'the tick beside the button line' );

		$other = post_id( 'spk_document', 'ics-309' );
		$html  = uxdoc_render( '\spokares_document_form_fields', get_post( $other ) );
		assert_contains( 'Also list under Most used', $html, 'a document that is no button has the tick' );
		assert_not_contains( 'the Most Used screen', $html, 'a document that is no button has no button line' );

		global $wp_meta_boxes;
		$saved = $wp_meta_boxes;
		try {
			\spokares_document_boxes();
			assert_false( isset( $wp_meta_boxes['spk_document']['side']['default']['spokares_checking'] ), 'an editor gets the Webmaster check box' );
			as_role( 'admin' );
			\spokares_document_boxes();
			assert_true( isset( $wp_meta_boxes['spk_document']['side']['default']['spokares_checking'] ), 'the administrator keeps the Webmaster check box' );
		} finally {
			$wp_meta_boxes = $saved; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- restoring.
		}
	}
);

test(
	'Edit: a new file needs a fresh "I checked the new file" tick; without it the file is refused and the live file stays',
	function () {
		$doc  = uxdoc_published( 'UXDOC net script', 'upload' );
		$file = absint( get_post_meta( $doc, 'spk_file', true ) );
		as_role( 'ares-net' );
		$html = uxdoc_render( '\spokares_document_form_fields', get_post( $doc ) );
		assert_contains( 'I checked the new file:', $html, 'a document with a file asks about the new file' );
		assert_matches( '#<input type="checkbox" name="spk_privacy_ok" value="1" id="spk-privacy"[^>]*>#', $html, 'set-up: the tick' );
		assert_false( (bool) preg_match( '#id="spk-privacy"[^>]*checked#', $html ), 'the tick for a new file starts unticked' );

		$tmp = wp_tempnam( 'uxdoc' );
		try {
			uxdoc_notices();
			uxdoc_click( $doc, uxdoc_fields( $doc ), 'save', uxdoc_pdf( $tmp ) );
			assert_same( array( 'warning: Saved, except the new file (outlined in red).' ), uxdoc_notices(), 'one notice says the new file was not saved' );
			assert_same( 'publish', get_post_status( $doc ), 'the document stays on the site' );
			assert_same( $file, absint( get_post_meta( $doc, 'spk_file', true ) ), 'the live file stays' );
			assert_same( '1', (string) get_post_meta( $doc, 'spk_privacy_ok', true ), 'the live file stays checked' );
			$html = uxdoc_render( '\spokares_document_form_fields', get_post( $doc ) );
			assert_contains( 'Tick “I checked this file” first, then choose the file again.', $html, 'the line under the file box' );

			// With the tick the file gets past the privacy gate (and is then
			// refused only because a test file isn't a real HTTP upload).
			uxdoc_click( $doc, array_merge( uxdoc_fields( $doc ), array( 'spk_privacy_ok' => '1' ) ), 'save', uxdoc_pdf( $tmp ) );
			uxdoc_notices();
			$held = \spokares_retained( 'spk_document_' . $doc );
			assert_contains( 'didn’t arrive', (string) ( $held['errors']['upload'] ?? '' ), 'with the tick, the file passed the privacy check' );
		} finally {
			wp_delete_file( $tmp );
		}
	}
);

test(
	'Edit: a changed web address needs a fresh tick; a name or note edit needs none',
	function () {
		$doc = uxdoc_published( 'UXDOC online form', 'link' );
		$old = (string) get_post_meta( $doc, 'spk_url', true );
		as_role( 'ares-editor' );

		uxdoc_notices();
		uxdoc_click( $doc, array_merge( uxdoc_fields( $doc ), array( 'spk_note' => 'Use the fillable version.' ) ) );
		assert_same( 'Use the fillable version.', (string) get_post_meta( $doc, 'spk_note', true ), 'a note edit needs no tick' );
		assert_same( array( 'success: Saved.' ), uxdoc_notices(), 'one plain Saved notice' );

		uxdoc_click( $doc, array_merge( uxdoc_fields( $doc ), array( 'spk_url' => 'www.example.org/new-form' ) ) );
		assert_same( $old, (string) get_post_meta( $doc, 'spk_url', true ), 'a new address without the tick does not go live' );
		assert_same( 'publish', get_post_status( $doc ), 'the document stays on the site with its old address' );
		assert_same( array( 'warning: Saved, except the web address (outlined in red).' ), uxdoc_notices(), 'one notice names the address' );
		$html = uxdoc_render( '\spokares_document_form_fields', get_post( $doc ) );
		assert_contains( 'value="www.example.org/new-form"', $html, 'the typed address comes back in the box' );
		assert_contains( 'Check the page, then tick this box.', $html, 'the line under the tick' );

		uxdoc_click(
			$doc,
			array_merge(
				uxdoc_fields( $doc ),
				array(
					'spk_url'        => 'www.example.org/new-form',
					'spk_privacy_ok' => '1',
				)
			)
		);
		assert_same( 'https://www.example.org/new-form', (string) get_post_meta( $doc, 'spk_url', true ), 'with the tick the new address is saved, https:// added' );
		assert_same( array( 'success: Saved.' ), uxdoc_notices(), 'Saved' );
	}
);

test(
	'Add: a link published without the tick stays a draft with one notice; Soon needs no tick',
	function () {
		as_role( 'ares-net' );
		$id = get_default_post_to_edit( 'spk_document', true )->ID;
		uxdoc_notices();
		uxdoc_click(
			$id,
			array(
				'post_title'  => 'UXDOC new link',
				'spk_section' => 'forms',
				'spk_source'  => 'link',
				'spk_url'     => 'https://example.org/uxdoc-new',
			),
			'publish'
		);
		assert_same( 'draft', get_post_status( $id ), 'not published without the tick' );
		assert_same( array( 'error: Not published yet: tick “I checked the page it links to”, then click Publish.' ), uxdoc_notices(), 'one notice says what to do' );
		assert_same( '', (string) get_post_meta( $id, 'spk_privacy_ok', true ), 'the address is not marked checked' );

		$soon = get_default_post_to_edit( 'spk_document', true )->ID;
		$r    = uxdoc_click(
			$soon,
			array(
				'post_title'  => 'UXDOC not ready',
				'spk_section' => 'forms',
				'spk_source'  => 'soon',
			),
			'publish'
		);
		assert_same( 'publish', get_post_status( $soon ), 'a Soon document publishes without a tick' );
		assert_same( '1', (string) get_post_meta( $soon, 'spk_privacy_ok', true ), 'nothing is published, so it is stored as checked' );
		$said = uxdoc_notices();
		assert_count( 1, $said, 'one notice' );
		assert_same( 'success: Published.', $said[0], 'Published' );
		assert_not_contains( 'message=', (string) $r['redirect'], 'WordPress\'s own notice is dropped (one notice per save)' );
	}
);

test(
	'Place in section: at the top, after another or at the end, numbered 10, 20, 30 … without re-saving the others',
	function () {
		as_role( 'admin' );
		$term = wp_insert_term( 'UXDOC place', 'spk_doc_cat', array( 'slug' => 'uxdoc-place' ) );
		expect_not_wp_error( $term, 'set-up: a section' );
		$a        = uxdoc_published( 'UXDOC A', 'link', 'uxdoc-place', 5 );
		$b        = uxdoc_published( 'UXDOC B', 'link', 'uxdoc-place', 7 );
		$c        = uxdoc_published( 'UXDOC C', 'link', 'uxdoc-place', 40 );
		$modified = get_post_field( 'post_modified_gmt', $b );
		$order    = static fn( int $id ): int => (int) get_post_field( 'menu_order', $id );

		as_role( 'ares-editor' );
		$html = uxdoc_render( '\spokares_document_form_fields', get_post( $c ) );
		assert_matches( '#<option value="' . $b . '"\s+selected=\'selected\'>After “UXDOC B”</option>#', $html, 'C shows its place: after B' );

		uxdoc_click( $c, array_merge( uxdoc_fields( $c ), array( 'spk_place' => 'top' ) ) );
		assert_same( array( 10, 20, 30 ), array( $order( $c ), $order( $a ), $order( $b ) ), 'At the top: C, A, B' );
		assert_same( $modified, get_post_field( 'post_modified_gmt', $b ), 'B was renumbered directly, not saved again' );

		uxdoc_click( $a, array_merge( uxdoc_fields( $a ), array( 'spk_place' => (string) $b ) ) );
		assert_same( array( 10, 20, 30 ), array( $order( $c ), $order( $b ), $order( $a ) ), 'After “UXDOC B”: C, B, A' );

		// A new document goes to the end by default, draft or not.
		$new = get_default_post_to_edit( 'spk_document', true )->ID;
		uxdoc_click(
			$new,
			array(
				'post_title'  => 'UXDOC new',
				'spk_section' => 'uxdoc-place',
				'spk_source'  => 'soon',
				'spk_place'   => 'end',
			),
			'saveasdraft'
		);
		assert_same( 40, $order( $new ), 'a new draft goes at the end' );
		uxdoc_notices();
	}
);

test(
	'the privacy tick, the file box and Place in section are the same on Add and Edit (create = edit)',
	function () {
		as_role( 'ares-net' );
		$add    = uxdoc_render( '\spokares_document_form_fields', get_default_post_to_edit( 'spk_document', true ) );
		$edit   = uxdoc_render( '\spokares_document_form_fields', get_post( post_id( 'spk_document', 'ics-309' ) ) );
		$labels = static function ( string $html ): array {
			preg_match_all( '#<(?:label|legend)[^>]*>\s*(?:<input[^>]*>)?\s*([^<]+)#', $html, $m );
			return array_values( array_filter( array_map( 'trim', $m[1] ) ) );
		};
		assert_same( $labels( $add ), $labels( $edit ), 'the same labels in the same order' );
		assert_contains( 'Document name (required)', uxdoc_render( '\spokares_document_title_label', get_post( post_id( 'spk_document', 'ics-309' ) ) ), 'the name has a visible label' );
		assert_contains( 'Section (required)', $add, 'Section is marked required' );
		assert_not_contains( 'Basics', $add, 'no Basics heading' );
		assert_contains( 'Not ready yet (shows “Soon”)', $add, 'the Soon choice' );
	}
);

test(
	'Most Used: a different document with its old words gets no words; typed words and a swap are kept; one notice',
	function () {
		as_role( 'ares-editor' );
		$tiles = array_slice( \spokares_opt( 'spk_tiles' ), 0, 4 );
		$docs  = array_keys( \spokares_tile_candidates() );
		$new   = 0;
		foreach ( $docs as $id ) {
			if ( ! in_array( $id, array_map( 'intval', wp_list_pluck( $tiles, 'doc' ) ), true ) ) {
				$new = (int) $id;
				break;
			}
		}
		assert_true( $new > 0, 'set-up: a document that is no button' );
		$rows = static function ( array $change ) use ( $tiles ): array {
			$out = array();
			for ( $i = 0; $i < 4; $i++ ) {
				$out[ $i ] = array_merge(
					array(
						'orig'  => (string) $tiles[ $i ]['doc'],
						'doc'   => (string) $tiles[ $i ]['doc'],
						'label' => $tiles[ $i ]['label'],
						'icon'  => $tiles[ $i ]['icon'],
					),
					$change[ $i ] ?? array()
				);
			}
			return array( 'tiles' => $out );
		};

		uxdoc_notices();
		post_form( 'spokares_save_tiles', $rows( array( 0 => array( 'doc' => (string) $new ) ) ) );
		$saved = \spokares_opt( 'spk_tiles' );
		assert_same( $new, (int) $saved[0]['doc'], 'button 1 has the new document' );
		assert_same( '', $saved[0]['label'], 'the old words went with the old document' );
		assert_same( $tiles[1]['label'], $saved[1]['label'], 'button 2 is untouched' );
		assert_same( array( 'success: Saved.' ), uxdoc_notices(), 'one notice' );

		update_option( 'spk_tiles', $tiles );
		post_form(
			'spokares_save_tiles',
			$rows(
				array(
					0 => array(
						'doc'   => (string) $new,
						'label' => 'RR form',
					),
				)
			)
		);
		assert_same( 'RR form', \spokares_opt( 'spk_tiles' )[0]['label'], 'words typed with the new document are kept' );

		update_option( 'spk_tiles', $tiles );
		post_form( 'spokares_save_tiles', $rows( array( 0 => array( 'doc' => (string) $tiles[2]['doc'] ) ) ) );
		$saved = \spokares_opt( 'spk_tiles' );
		assert_same( (int) $tiles[2]['doc'], (int) $saved[0]['doc'], 'swap: button 1 has button 3\'s document' );
		assert_same( $tiles[2]['label'], $saved[0]['label'], 'swap: its words came with it' );
		assert_same( (int) $tiles[0]['doc'], (int) $saved[2]['doc'], 'swap: button 3 has button 1\'s document' );
		assert_same( $tiles[0]['label'], $saved[2]['label'], 'swap: and its words' );
		uxdoc_notices();
	}
);

test(
	'Most Used: each row\'s "Replace or change it" opens the chosen document\'s form; the screen speaks of buttons',
	function () {
		as_role( 'ares-editor' );
		$tiles            = \spokares_opt( 'spk_tiles' );
		$saved            = $GLOBALS['title'] ?? null;
		$GLOBALS['title'] = 'Most Used'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- the screen's title, as admin.php sets it.
		try {
			$html = uxdoc_render( '\spokares_tiles_page' );
		} finally {
			$GLOBALS['title'] = $saved; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- restoring.
		}
		assert_contains( '<h1 class="wp-heading-inline">Most Used</h1>', $html, 'the screen\'s name' );
		assert_contains( 'The four buttons under Most used on the For members page, left to right.', $html, 'the one line under it' );
		for ( $i = 0; $i < 4; $i++ ) {
			$doc = (int) $tiles[ $i ]['doc'];
			assert_contains( '<a class="spk-tile-edit" href="' . esc_url( admin_url( 'post.php?post=' . $doc . '&action=edit' ) ) . '">Replace or change it', $html, 'button ' . ( $i + 1 ) . '\'s link' );
			assert_contains( 'aria-label="Words on button ' . ( $i + 1 ) . '"', $html, 'button ' . ( $i + 1 ) . '\'s words are named' );
		}
		assert_matches( '#<option value="' . (int) $tiles[1]['doc'] . '"[^>]*data-edit="' . preg_quote( esc_attr( admin_url( 'post.php?post=' . (int) $tiles[1]['doc'] . '&action=edit' ) ), '#' ) . '"#', $html, 'the choices carry their form\'s address for the link' );
		assert_contains( '(now button 2)', $html, 'a document that is another button says which' );
		assert_contains( 'data-spk-guard', $html, 'the form asks before leaving with changes' );
		$text = wp_strip_all_tags( $html );
		foreach ( array( 'slot', 'tile', 'hub' ) as $word ) {
			assert_false( (bool) preg_match( '/\b' . $word . 's?\b/i', $text ), 'the screen says "' . $word . '"' );
		}
	}
);

test(
	'the Documents list: five columns for editors, a Soon view, no "Mine", the line above the table, and site order',
	function () {
		as_role( 'ares-editor' );
		assert_same( array( 'cb', 'title', 'taxonomy-spk_doc_cat', 'spk_source', 'spk_version', 'spk_reviewed' ), array_keys( \spokares_document_columns( array() ) ), 'editor columns' );
		$r = call_request(
			'GET',
			array( 'post_type' => 'spk_document' ),
			array(),
			static fn() => \spokares_document_views(
				array(
					'all'     => '<a href="#" class="current" aria-current="page">All</a>',
					'mine'    => '<a href="#">Mine</a>',
					'publish' => '<a href="#">Published</a>',
					'trash'   => '<a href="#">Trash</a>',
				)
			)
		);
		assert_same( array( 'all', 'publish', 'spk_soon', 'trash' ), array_keys( $r['returned'] ), 'views for an editor' );
		assert_contains( 'spk_source=soon', $r['returned']['spk_soon'], 'the Soon view' );
		assert_contains( 'Click a name to change it, give it a new file or take it off the site.', $r['output'], 'the line above the table' );
		as_role( 'admin' );
		assert_contains( 'spk_check', array_keys( \spokares_document_columns( array() ) ), 'the administrator keeps Needs checking' );

		// Site order: section order, then place, then name.
		$posts = get_posts(
			array(
				'post_type'   => 'spk_document',
				'post_status' => 'any',
				'numberposts' => 100,
				'orderby'     => 'title',
				'order'       => 'DESC',
			)
		);
		$q     = new \WP_Query();
		$q->set( 'spk_site_order', true );
		$ids  = wp_list_pluck( \spokares_document_site_order( $posts, $q ), 'ID' );
		$want = wp_list_pluck( \spokares_document_site_list(), 'id' );
		assert_same( array_map( 'intval', $want ), array_map( 'intval', $ids ), 'the list is in site order' );
	}
);

test(
	'Most Used: words with a phone number are held back with one notice and the box the sentence mentions; ticking it saves them',
	function () {
		as_role( 'ares-editor' );
		$tiles = array_slice( \spokares_opt( 'spk_tiles' ), 0, 4 );
		$rows  = static function ( string $words, bool $tick ) use ( $tiles ): array {
			$out = array();
			for ( $i = 0; $i < 4; $i++ ) {
				$out[ $i ] = array(
					'orig'  => (string) $tiles[ $i ]['doc'],
					'doc'   => (string) $tiles[ $i ]['doc'],
					'label' => 0 === $i ? $words : $tiles[ $i ]['label'],
					'icon'  => $tiles[ $i ]['icon'],
				);
			}
			if ( $tick ) {
				$out[0]['confirm'] = '1';
			}
			return array( 'tiles' => $out );
		};
		uxdoc_notices();
		post_form( 'spokares_save_tiles', $rows( 'Call 509-555-0142', false ) );
		assert_same( $tiles[0]['label'], \spokares_opt( 'spk_tiles' )[0]['label'], 'the words are not saved' );
		assert_same( array( 'warning: Saved, except button 1’s words (outlined in red).' ), uxdoc_notices(), 'one notice' );
		$saved            = $GLOBALS['title'] ?? null;
		$GLOBALS['title'] = 'Most Used'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- the screen's title, as admin.php sets it.
		try {
			$html = uxdoc_render( '\spokares_tiles_page' );
		} finally {
			$GLOBALS['title'] = $saved; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- restoring.
		}
		assert_contains( 'It has a phone number. Take it out, or tick the box if it’s a public agency number.', $html, 'the line under the words' );
		assert_contains( 'name="tiles[0][confirm]"', $html, 'the box the line mentions' );
		assert_contains( 'value="Call 509-555-0142"', $html, 'the typed words come back' );
		assert_contains( 'data-spk-dirty', $html, 'the form counts as changed until saved' );

		post_form( 'spokares_save_tiles', $rows( 'Call 509-555-0142', true ) );
		assert_same( 'Call 509-555-0142', \spokares_opt( 'spk_tiles' )[0]['label'], 'ticked: the words are saved' );
		assert_same( array( 'success: Saved.' ), uxdoc_notices(), 'Saved' );
		post_form( 'spokares_save_tiles', $rows( 'Call 509-555-0142', false ) );
		assert_same( array( 'success: Saved.' ), uxdoc_notices(), 'the same words need no tick the next time' );
	}
);
