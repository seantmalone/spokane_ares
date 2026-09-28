<?php
/**
 * Regression tests for QA-022 (PLAN §3 events and documents, §4 editing):
 * the plugin's Save box (admin-common.php, spokares_render_save_box()) names
 * its "Save draft", "Save as draft (takes it off the site)" and "Save as draft
 * (takes it off the lists)" buttons "saveasdraft". WordPress's redirect_post()
 * picks message 10 ("draft updated") or 1 only for "save" or "publish", so
 * every one of those clicks came back with message=4 and the notice
 * "Saved. See it on Exercises & events" (or "… Documents & forms") with a
 * link to the public list, while the Save box said "Draft: not on the site".
 * The notice must say the draft is not on the site
 * (spokares_event_messages() already has message 10, "Draft saved. Drafts
 * never show on the site.") and carry no link to the public page.
 *
 * The click runs as WordPress's post.php "editpost" does it: the Save box as
 * spokares_render_save_box() draws it (its hidden fields and the button the
 * editor clicks, found by its label), edit_post(), then redirect_post(). The
 * notice is the one edit-form-advanced.php shows for the redirect's message
 * number (post_updated_messages for the saved post).
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

/**
 * The fields the Save box sends when the button whose label matches
 * $button is clicked: its hidden inputs plus that button's name and value.
 * Runs as the current user (the buttons depend on the user's caps).
 *
 * @param int    $id     Post.
 * @param string $button Regex for the clicked button's label.
 */
function qa022_save_box_fields( int $id, string $button ): array {
	ob_start();
	spokares_render_save_box( get_post( $id ) );
	$html = (string) ob_get_clean();

	preg_match_all( '/<input\b[^>]*>/i', $html, $inputs );
	$fields  = array();
	$clicked = null;
	foreach ( $inputs[0] as $input ) {
		$attr = array();
		foreach ( array( 'type', 'name', 'value' ) as $key ) {
			$attr[ $key ] = preg_match( '/\s' . $key . '="([^"]*)"/i', $input, $m ) ? html_entity_decode( $m[1], ENT_QUOTES, 'UTF-8' ) : '';
		}
		if ( 'hidden' === strtolower( $attr['type'] ) && '' !== $attr['name'] ) {
			$fields[ $attr['name'] ] = $attr['value'];
		} elseif ( 'submit' === strtolower( $attr['type'] ) && preg_match( $button, $attr['value'] ) ) {
			$clicked = $attr;
		}
	}
	if ( null === $clicked ) {
		fail( 'the Save box has no button labelled ' . $button . ': ' . $html );
	}
	$fields[ $clicked['name'] ] = $clicked['value'];
	return $fields;
}

/**
 * Click a Save box button on the edit screen, as post.php's "editpost" does:
 * edit_post() with the form's fields, then redirect_post(). Returns the
 * call_request() result (redirect = the location the browser is sent to).
 *
 * @param int    $id     Post.
 * @param string $button Regex for the clicked button's label.
 * @param string $title  The title in the form.
 */
function qa022_click( int $id, string $button, string $title = '' ): array {
	$post   = get_post( $id );
	$fields = array_merge(
		array(
			'action'         => 'editpost',
			'originalaction' => 'editpost',
			'post_ID'        => (string) $id,
			'post_type'      => $post->post_type,
			'post_author'    => (string) $post->post_author,
			'post_title'     => '' !== $title ? $title : $post->post_title,
			'_wpnonce'       => wp_create_nonce( 'update-post_' . $id ),
		),
		qa022_save_box_fields( $id, $button )
	);
	if ( 'auto-draft' === $post->post_status ) {
		$fields['auto_draft'] = '1';
	}
	return call_request(
		'POST',
		array(),
		$fields,
		static function () {
			$saved = edit_post();
			redirect_post( $saved );
		}
	);
}

/**
 * The message number in a redirect_post() location.
 *
 * @param array $res call_request() result.
 */
function qa022_message( array $res ): string {
	assert_same( null, $res['die'], 'the save did not wp_die()' );
	if ( ! is_string( $res['redirect'] ) ) {
		fail( 'redirect_post() did not redirect' );
	}
	$query = array();
	wp_parse_str( (string) wp_parse_url( $res['redirect'], PHP_URL_QUERY ), $query );
	return (string) ( $query['message'] ?? '' );
}

/**
 * The notice edit-form-advanced.php shows for message $n on post $id.
 *
 * @param int    $id Post.
 * @param string $n  Message number from the redirect.
 */
function qa022_notice( int $id, string $n ): string {
	clean_post_cache( $id );
	$saved           = $GLOBALS['post'] ?? null;
	$GLOBALS['post'] = get_post( $id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- as the edit screen sets it.
	$messages        = apply_filters( 'post_updated_messages', array( 'post' => array() ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- core's filter.
	$GLOBALS['post'] = $saved; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- restoring.
	return (string) ( $messages[ get_post_type( $id ) ][ absint( $n ) ] ?? '' );
}

/**
 * The draft notice: message 10, "Draft saved. Drafts never show on the site.",
 * and no link to the public list.
 *
 * @param array  $res       call_request() result.
 * @param int    $id        Post.
 * @param string $list_path The public list's path.
 * @param string $label     What was clicked, for the failure text.
 */
function qa022_assert_draft_notice( array $res, int $id, string $list_path, string $label ): void {
	clean_post_cache( $id );
	assert_same( 'draft', get_post_status( $id ), $label . ': the post is a draft after the click (control)' );
	$n      = qa022_message( $res );
	$notice = qa022_notice( $id, $n );
	assert_same( '10', $n, $label . ': redirect ' . $res['redirect'] . ' shows notice "' . wp_strip_all_tags( $notice ) . '"' );
	assert_contains( 'Draft saved', $notice, $label . ': notice' );
	assert_not_contains( 'See it on', $notice, $label . ': a draft notice offers to show it on the site' );
	assert_not_contains( $list_path, $notice, $label . ': a draft notice links to the public list' );
}

/**
 * The Save box of a published item, as the current user sees it: one Save
 * button (name "save"), "Take it off the site" as the trash link, and no
 * "Save as draft (takes it off …)" control, "Status:" prefix or "Update"
 * (UX spec §3.9: the draft route off the site is gone by design).
 *
 * @param int    $id    Post.
 * @param string $label What is checked, for the failure text.
 */
function qa022_assert_published_box( int $id, string $label ): void {
	ob_start();
	spokares_render_save_box( get_post( $id ) );
	$html = (string) ob_get_clean();
	assert_contains( 'On the site', $html, $label . ': status line' );
	assert_not_contains( 'Status:', $html, $label . ': "Status:" prefix' );
	assert_matches( '#<input type="submit" name="save"[^>]*value="Save"#', $html, $label . ': the Save button' );
	assert_not_contains( 'value="Update"', $html, $label . ': an Update button' );
	assert_not_contains( 'name="saveasdraft"', $html, $label . ': a draft button on a published item' );
	assert_not_contains( 'Save as draft', $html, $label . ': "Save as draft (takes it off …)"' );
	assert_matches( '#<a class="submitdelete" id="spk-trash-link" href="[^"]*">Take it off the site</a>#', $html, $label . ': the trash link reads "Take it off the site"' );
	assert_not_contains( 'Move to Trash', $html, $label . ': "Move to Trash" on a published item' );
}

test(
	'a published event\'s Save box (ARES Editor) has Save and "Take it off the site", and no "Save as draft (takes it off the site)"',
	function () {
		$id = post_id( 'spk_event', 'set-2026' );
		assert_same( 'publish', get_post_status( $id ), 'set-2026 starts published' );
		as_role( 'ares-editor' );
		qa022_assert_published_box( $id, 'published event' );
	}
);

test(
	'"Save draft" on a new event (ARES Editor with the net grant) says Draft saved, with no link to Exercises & events',
	function () {
		as_role( 'ares-net' );
		$id = get_default_post_to_edit( 'spk_event', true )->ID;
		assert_same( 'auto-draft', get_post_status( $id ), 'Add event made an auto-draft (control)' );
		$res = qa022_click( $id, '/^Save draft$/', 'QA022 draft event' );
		qa022_assert_draft_notice( $res, $id, '/members/exercises/', 'new event Save draft' );
	}
);

test(
	'"Save draft" on a new document (administrator) says Draft saved, with no link to Documents & forms',
	function () {
		as_role( 'admin' );
		$id = get_default_post_to_edit( 'spk_document', true )->ID;
		assert_same( 'auto-draft', get_post_status( $id ), 'Add document made an auto-draft (control)' );
		$res = qa022_click( $id, '/^Save draft$/', 'QA022 draft document' );
		qa022_assert_draft_notice( $res, $id, '/members/documents/', 'new document Save draft' );
	}
);

test(
	'a published document\'s Save box (ARES Editor) has Save and "Take it off the site", and no "Save as draft (takes it off the lists)"',
	function () {
		$id = post_id( 'spk_document', 'ics-213' );
		assert_same( 'publish', get_post_status( $id ), 'ics-213 starts published' );
		as_role( 'ares-editor' );
		qa022_assert_published_box( $id, 'published document' );
	}
);

test(
	'a draft\'s Save box has Save draft (no browser checks) and Publish, the draft status line and "Move to Trash"; a new item has no trash link',
	function () {
		as_role( 'ares-editor' );
		$id = create_post(
			array(
				'post_type'   => 'spk_event',
				'post_status' => 'draft',
				'post_title'  => 'QA022 draft box',
			)
		);

		$links = array(
			array(
				'label' => 'Make a copy',
				'url'   => admin_url( 'admin.php?qa022=copy' ),
			),
			array(
				'label' => '',
				'url'   => 'https://example.org/',
			),
		);
		ob_start();
		spokares_render_save_box( get_post( $id ), $links );
		$html = (string) ob_get_clean();
		assert_contains( 'Not on the site (draft)', $html, 'draft status line' );
		assert_matches( '#<input type="submit" name="saveasdraft"[^>]*value="Save draft" formnovalidate>#', $html, 'Save draft skips the browser checks' );
		assert_matches( '#<input type="submit" name="publish"[^>]*value="Publish"#', $html, 'Publish' );
		assert_contains( '<p class="spk-savebox-link"><a href="' . esc_url( admin_url( 'admin.php?qa022=copy' ) ) . '">Make a copy</a></p>', $html, 'the caller\'s link, on its own line' );
		assert_same( 1, substr_count( $html, 'spk-savebox-link' ), 'a link without words is left out' );
		assert_matches( '#id="spk-trash-link"[^>]*>Move to Trash</a>#', $html, 'a draft goes to the Trash' );
		assert_true( strpos( $html, 'spk-savebox-link' ) < strpos( $html, 'spk-trash-link' ), 'the caller\'s links come before the trash link' );

		$new = get_default_post_to_edit( 'spk_document', true );
		ob_start();
		spokares_render_save_box( $new );
		$html = (string) ob_get_clean();
		assert_contains( 'Not saved yet', $html, 'new item status line' );
		assert_not_contains( 'spk-trash-link', $html, 'a new item has nothing to take off the site' );
		assert_contains( 'id="submitpost"', $html, 'the box keeps id="submitpost" (core post.js)' );
		assert_contains( 'name="original_post_status" value="auto-draft"', $html, 'hidden status fields' );
	}
);

test(
	'Save on a published event still says Saved with the link to Exercises & events (control)',
	function () {
		$id = post_id( 'spk_event', 'set-2026' );
		as_role( 'ares-editor' );
		$res = qa022_click( $id, '/^Save$/' );
		clean_post_cache( $id );
		assert_same( 'publish', get_post_status( $id ), 'still published' );
		$n      = qa022_message( $res );
		$notice = qa022_notice( $id, $n );
		assert_same( '1', $n, 'Save redirect ' . $res['redirect'] );
		assert_contains( 'See it on the Exercises &amp; events page', $notice, 'the published notice links to the list' );
		assert_contains( '/members/exercises/', $notice, 'link target' );
	}
);
