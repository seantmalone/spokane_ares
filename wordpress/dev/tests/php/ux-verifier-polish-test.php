<?php
/**
 * Tests for the verifier's last polish on the volunteer-editor screens
 * (ux/REPORT.md, "Fixed by the verifier"): no WordPress chrome left on the
 * lists editors use (Screen Options, the events list's bulk actions and row
 * ticks), the name check on Add an Event and the course links in Add a
 * Document's name check, a trashed draft event "is in the Trash", a partly
 * saved event is an amber notice like every other screen's, and the photo
 * window's plain words on a page for editors.
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

test(
	'editors get no Screen Options on the Exercises & Events, Documents and Page Text lists (or the two forms); administrators keep it',
	function () {
		foreach ( array( 'edit-spk_event', 'edit-spk_document', 'edit-page' ) as $id ) {
			$screen = \WP_Screen::get( $id );
			as_role( 'ares-net' );
			assert_false( apply_filters( 'screen_options_show_screen', true, $screen ), $id . ': no Screen Options for an editor' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- core's filter.
			as_role( 'admin' );
			assert_true( apply_filters( 'screen_options_show_screen', true, $screen ), $id . ': the administrator keeps it (control)' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- core's filter.
		}
	}
);

test(
	'the events list has no bulk actions and no row ticks for editors (Take it off the site is on the form); administrators keep both',
	function () {
		$core = array(
			'edit'  => 'Edit',
			'trash' => 'Move to Trash',
		);
		as_role( 'ares-editor' );
		assert_same( array(), apply_filters( 'bulk_actions-edit-spk_event', $core ), 'no bulk actions for an editor' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound, WordPress.NamingConventions.ValidHookName.UseUnderscores -- core's filter.
		$cols = apply_filters( 'manage_spk_event_posts_columns', array( 'cb' => '<input type="checkbox" />' ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- core's filter.
		assert_same( array( 'spk_when', 'title', 'spk_kind', 'spk_status' ), array_keys( $cols ), 'When, Event, Type of event, Where it shows: no tick column' );
		as_role( 'admin' );
		assert_same( array( 'trash' ), array_keys( apply_filters( 'bulk_actions-edit-spk_event', $core ) ), 'the administrator keeps Move to Trash (control)' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound, WordPress.NamingConventions.ValidHookName.UseUnderscores -- core's filter.
		$cols = apply_filters( 'manage_spk_event_posts_columns', array( 'cb' => '<input type="checkbox" />' ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- core's filter.
		assert_true( isset( $cols['cb'] ), 'the administrator keeps the tick column (control)' );
	}
);

test(
	'Add an Event checks the typed name against the events, with their dates; an Add left at the name (no type) is left out; Edit has no check',
	function () {
		as_role( 'ares-net' );
		$stub = create_post(
			array(
				'post_type'   => 'spk_event',
				'post_status' => 'draft',
				'post_title'  => 'Lilac parade stub',
			)
		);
		$new  = get_default_post_to_edit( 'spk_event', true );
		$was  = $GLOBALS['post'] ?? null;
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- the edit screen's global post, restored below.
		$GLOBALS['post'] = $new;
		try {
			$data = \spokares_event_name_check_data();
			ob_start();
			\spokares_event_form_fields( $new );
			$form = (string) ob_get_clean();
		} finally {
			// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- restore.
			$GLOBALS['post'] = $was;
		}
		assert_same( 'Already an event: %1$s (%2$s).', $data['already'] ?? '', 'the sentence' );
		assert_same( 'Open it', $data['openIt'] ?? '', 'the link' );
		$by_id = array();
		foreach ( $data['events'] as $row ) {
			$by_id[ $row[0] ] = $row;
		}
		$lilac = post_id( 'spk_event', 'lilac-torchlight-parade' );
		assert_same( 'Lilac Festival Armed Forces Torchlight Parade', $by_id[ $lilac ][1] ?? '', 'the parade is offered by name' );
		assert_same( 'Date not posted yet', $by_id[ $lilac ][2] ?? '', 'with its date as the list shows it' );
		assert_true( ! isset( $by_id[ $stub ] ), 'a draft with no type of event (an Add that went no further) is left out' );
		assert_true( ! isset( $by_id[ $new->ID ] ), 'the new event itself is not offered' );
		assert_contains( 'id="spk-name-match"', $form, 'the line under the name is on Add' );

		// Edit: no line, no data.
		$set = get_post( post_id( 'spk_event', 'set-2026' ) );
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- as above.
		$GLOBALS['post'] = $set;
		try {
			assert_same( array(), \spokares_event_name_check_data(), 'no name check on Edit' );
			ob_start();
			\spokares_event_form_fields( $set );
			assert_not_contains( 'spk-name-match', (string) ob_get_clean(), 'no line under the name on Edit' );
		} finally {
			// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- restore.
			$GLOBALS['post'] = $was;
		}
	}
);

test(
	'Add a Document\'s name check knows the course links under a document ("IS-100.c" under FEMA courses)',
	function () {
		as_role( 'ares-net' );
		$fema = post_id( 'spk_document', 'fema-is-courses' );
		$rows = array_values( array_filter( \spokares_document_site_list(), static fn( $d ) => $d['id'] === $fema ) );
		assert_count( 1, $rows, 'FEMA courses is in the list' );
		assert_contains( array( 'IS-100.c', 'Introduction to the Incident Command System' ), $rows[0]['links'], 'its IS-100.c course link, words and title' );
		foreach ( \spokares_document_site_list() as $d ) {
			assert_true( is_array( $d['links'] ), $d['title'] . ': links is a list' );
		}
	}
);

test(
	'a draft event taken off the list says it is in the Trash (it was never on the site); a published one is off the site',
	function () {
		as_role( 'ares-editor' );
		$draft = create_post(
			array(
				'post_type'   => 'spk_event',
				'post_status' => 'draft',
				'post_title'  => 'Copy of the SET',
			)
		);
		wp_trash_post( $draft );
		assert_same( '“Copy of the SET” is in the Trash.', \spokares_bulk_message( true, 'trashed', 1, array( $draft ) ), 'a draft' );
		$set = post_id( 'spk_event', 'set-2026' );
		wp_trash_post( $set );
		assert_same( '“Simulated Emergency Test” is off the site.', \spokares_bulk_message( true, 'trashed', 1, array( $set ) ), 'a published event' );
		wp_untrash_post( $set );
	}
);

test(
	'the photo window reads in plain words for editors on a page (Photos on the site, Upload a photo, This photo, Search photos); administrators keep WordPress\'s',
	function () {
		$screen = \WP_Screen::get( 'page' );
		$words  = array(
			'Media Library'      => 'Photos on the site',
			'Upload files'       => 'Upload a photo',
			'Attachment Details' => 'This photo',
			'Attachment details' => 'This photo',
			'Search media'       => 'Search photos',
		);
		as_role( 'ares-net' );
		remove_filter( 'gettext', 'spokares_photo_window_words', 10 );
		\spokares_photo_window_words_on( $screen );
		try {
			foreach ( $words as $wp => $ours ) {
				assert_same( $ours, translate( $wp, 'default' ), $wp ); // phpcs:ignore WordPress.WP.I18n -- the core string under test.
			}
			assert_same( 'Library', translate( 'Library', 'default' ), 'other words are untouched' ); // phpcs:ignore WordPress.WP.I18n -- as above.
			assert_same( 'Media Library', \spokares_photo_window_words( 'Media Library', 'Media Library', 'spokares-core' ), 'other text domains are untouched' );
		} finally {
			remove_filter( 'gettext', 'spokares_photo_window_words', 10 );
		}
		as_role( 'admin' );
		\spokares_photo_window_words_on( $screen );
		assert_false( has_filter( 'gettext', 'spokares_photo_window_words' ), 'not for an administrator (control)' );
		as_role( 'ares-net' );
		\spokares_photo_window_words_on( \WP_Screen::get( 'spk_event' ) );
		assert_false( has_filter( 'gettext', 'spokares_photo_window_words' ), 'not on the event form' );
	}
);
