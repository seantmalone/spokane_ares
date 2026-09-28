<?php
/**
 * Tests for the plugin-admin quality fixes of the QA crawl (qa/ISSUES.md):
 * the smaller admin-screen problems that had no regression test of their
 * own. Each test names its issue.
 *
 * - QA-034 Cancel or Move a Meeting lists only the meetings the site shows.
 * - QA-035 "Pull this file now" asks before it deletes.
 * - QA-036 A section that holds documents can't be deleted; a new section
 *   goes after the last one.
 * - QA-076 An account with no site tasks gets a sentence, not an empty Dashboard.
 * - QA-078 No command palette for anyone but administrators.
 * - QA-079 The Edit Media screen of one file is closed to non-admins.
 * - QA-080 Site tasks carry no number badges (they read as steps in order).
 * - QA-081 A file refused for the "I checked this file" tick says so under the chooser.
 * - QA-084 Holders of the Net Settings grant get a link to Meeting Schedule.
 * - QA-085 The GMRS time field is named.
 * - QA-086 An events search looks through every view.
 * - QA-087 Restore says the event came back as a draft.
 * - QA-088 The Needs checking line links to the filtered lists.
 * - QA-089 A refused Page owner is said in the Page review box.
 * - QA-090 No "Mark reviewed today" in the Trash view; no file tick without a file.
 * - QA-091 Opening Navigation in the Site Editor creates no menu.
 * - QA-093 Every Cancel or Move a Meeting row names its meeting for screen readers.
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

/**
 * Output of a callable, as the screen prints it.
 *
 * @param callable $render  Renderer.
 * @param mixed    ...$args Arguments.
 */
function qa_admin_render( callable $render, ...$args ): string {
	ob_start();
	$render( ...$args );
	return (string) ob_get_clean();
}

/**
 * The queued notices for the current user (and clear them).
 */
function qa_admin_notices(): array {
	$key     = 'spokares_notices_' . get_current_user_id();
	$notices = get_transient( $key );
	delete_transient( $key );
	return is_array( $notices ) ? $notices : array();
}

/**
 * A published document in Forms, made by an administrator.
 *
 * @param string $title  Title.
 * @param string $source upload, link or soon.
 */
function qa_admin_document( string $title, string $source = 'link' ): int {
	as_role( 'admin' );
	$id = create_post(
		array(
			'post_type'   => 'spk_document',
			'post_status' => 'publish',
			'post_title'  => $title,
		)
	);
	wp_set_object_terms( $id, 'forms', 'spk_doc_cat', false );
	update_post_meta( $id, 'spk_source', $source );
	update_post_meta( $id, 'spk_url', 'https://example.org/qa-admin' );
	update_post_meta( $id, 'spk_privacy_ok', '1' );
	return $id;
}

test(
	'QA-034 and QA-093: Cancel or Move a Meeting lists only shown meetings, and every row names its meeting',
	function () {
		as_role( 'ares-editor' );
		$html = qa_admin_render( '\spokares_meetings_page' );
		assert_not_contains( 'Winlink workshop on', $html, 'a meeting the site doesn’t show has rows' );
		assert_not_contains( 'Not listed', $html, 'no "Not listed: …" line about a meeting nobody sees' );
		preg_match_all( '#<tr class="spk-meeting-row[^"]*">\s*<th scope="row"[^>]*>(.*?)</th>#s', $html, $m );
		assert_true( count( $m[1] ) > 2, 'set-up: the table has rows' );
		foreach ( $m[1] as $th ) {
			assert_true( '' !== trim( wp_strip_all_tags( $th ) ), 'a row header is empty: ' . $th );
		}
	}
);

test(
	'QA-084: Cancel or Move a Meeting links Meeting Schedule for the grant holder only',
	function () {
		as_role( 'ares-net' );
		assert_matches( '#<a class="page-title-action" href="[^"]*page=spokares-meeting-rules">Meeting Schedule</a>#', qa_admin_render( '\spokares_meetings_page' ), 'grant holder: the h1 button to Meeting Schedule' );
		as_role( 'ares-editor' );
		$html = qa_admin_render( '\spokares_meetings_page' );
		assert_not_contains( 'page=spokares-meeting-rules', $html, 'an editor without the grant is linked to a screen they can’t open' );
		// Said once, in the screen's Help (R3): the regular times are the webmaster's.
		assert_contains( 'ask the webmaster', implode( ' ', \spokares_help_lines()['spokares-meetings'] ), 'an editor without the grant is told whom to ask' );
	}
);

test(
	'QA-085: the GMRS time field has a name of its own',
	function () {
		as_role( 'ares-net' );
		$html = qa_admin_render( '\spokares_net_details_page' );
		assert_matches( '#<label for="spk-gmrs-time">GMRS net time</label>#', $html, 'GMRS time label' );
		assert_matches( '#id="spk-p-freq"[^>]*aria-describedby="spk-p-freq-hint"#', $html, 'the frequency hint is tied to its field' );
	}
);

test(
	'QA-035: "Pull this file now" asks before it deletes',
	function () {
		$doc = qa_admin_document( 'QA admin pull', 'upload' );
		update_post_meta( $doc, 'spk_file', 999999 );
		$actions = \spokares_document_row_actions( array(), get_post( $doc ) );
		assert_true( isset( $actions['pull'] ), 'set-up: an administrator is offered Pull this file now' );
		assert_matches( '#data-spk-ask="[^"]*can’t be undone#u', $actions['pull'], 'the link carries its question' );
	}
);

test(
	'QA-090: no "Mark reviewed today" in the Trash view, and no file tick for a document without a file',
	function () {
		as_role( 'ares-editor' );
		$r = call_request( 'GET', array( 'post_status' => 'trash' ), array(), static fn() => \spokares_document_bulk_actions( array( 'delete' => 'Delete permanently' ) ) );
		assert_false( isset( $r['returned']['spk_mark_reviewed'] ), 'Trash view offers Mark reviewed today' );
		$r = call_request( 'GET', array(), array(), static fn() => \spokares_document_bulk_actions( array() ) );
		assert_true( isset( $r['returned']['spk_mark_reviewed'] ), 'the other views keep Mark reviewed today' );

		$doc = qa_admin_document( 'QA admin link document' );
		as_role( 'admin' );
		global $wp_meta_boxes;
		$saved = $wp_meta_boxes;
		try {
			\spokares_document_boxes();
			$box  = $wp_meta_boxes['spk_document']['side']['high']['spokares_savebox'] ?? null;
			$html = $box ? qa_admin_render( $box['callback'], get_post( $doc ) ) : '';
		} finally {
			$wp_meta_boxes = $saved; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- restoring.
		}
		assert_contains( 'Take it off the site', $html, 'set-up: the Save box is drawn (a published document)' );
		assert_not_contains( 'Also remove its file', $html, 'a link document is offered to remove a file it doesn’t have' );
	}
);

test(
	'QA-036: a section that holds documents is not deleted; a new section goes after the last',
	function () {
		as_role( 'admin' );
		qa_admin_document( 'QA admin in Forms' );
		$forms = get_term_by( 'slug', 'forms', 'spk_doc_cat' );
		assert_true( $forms instanceof \WP_Term, 'set-up: the Forms section' );
		$die = expect_die( static fn() => wp_delete_term( $forms->term_id, 'spk_doc_cat' ), 409 );
		assert_contains( 'Move', $die['message'], 'the refusal says what to do' );
		assert_true( get_term( $forms->term_id, 'spk_doc_cat' ) instanceof \WP_Term, 'the section is still there' );

		$last = 0;
		foreach ( get_terms(
			array(
				'taxonomy'   => 'spk_doc_cat',
				'hide_empty' => false,
			)
		) as $t ) {
			$last = max( $last, (int) get_term_meta( $t->term_id, 'spk_order', true ) );
		}
		$r   = call_request(
			'POST',
			array(),
			array( 'spk_order' => '' ),
			static fn() => wp_insert_term( 'QA admin maps', 'spk_doc_cat' )
		);
		$new = (int) ( $r['returned']['term_id'] ?? 0 );
		assert_true( $new > 0, 'set-up: the section was made' );
		assert_true( (int) get_term_meta( $new, 'spk_order', true ) > $last, 'a new section sorts after the last one, not first' );
		$sections = \spokares_library_sections();
		assert_same( 'QA admin maps', (string) end( $sections ), 'the new section is the last in the library' );
	}
);

test(
	'QA-076 and QA-080: accounts without site tasks get a sentence; tasks carry no number badges',
	function () {
		foreach ( array( 'subscriber', 'contributor', 'author' ) as $role ) {
			as_role( $role );
			assert_false( \spokares_has_site_tasks(), $role . ' has no site tasks' );
			assert_contains( 'can’t change anything on the site', qa_admin_render( '\spokares_dashboard_no_tasks' ), $role . ': the Dashboard sentence' );
		}
		as_role( 'core-editor' );
		$html = qa_admin_render( '\spokares_dashboard_widget' );
		assert_true( substr_count( $html, '<section class="spk-task">' ) >= 1, 'set-up: the core Editor has a task' );
		assert_not_contains( 'spk-task__n', $html, 'a task carries a number badge' );
	}
);

test(
	'QA-078: no command palette for anyone but administrators',
	function () {
		foreach ( array( 'subscriber', 'ares-editor', 'ares-net' ) as $role ) {
			as_role( $role );
			add_action( 'admin_enqueue_scripts', 'wp_enqueue_command_palette_assets' );
			\spokares_no_command_palette();
			assert_false( has_action( 'admin_enqueue_scripts', 'wp_enqueue_command_palette_assets' ), 'the palette is still enqueued for ' . $role );
		}
		add_action( 'admin_enqueue_scripts', 'wp_enqueue_command_palette_assets' );
		as_role( 'admin' );
		\spokares_no_command_palette();
		assert_true( (bool) has_action( 'admin_enqueue_scripts', 'wp_enqueue_command_palette_assets' ), 'an administrator keeps the palette' );
	}
);

test(
	'QA-079: the Edit Media screen of one file sends a non-admin to the Dashboard',
	function () {
		as_role( 'admin' );
		$att = wp_insert_attachment(
			array(
				'post_title'     => 'QA admin file',
				'post_mime_type' => 'image/png',
				'post_status'    => 'inherit',
			),
			false,
			0,
			true
		);
		expect_not_wp_error( $att, 'set-up: attachment' );
		global $pagenow;
		$saved   = $pagenow;
		$pagenow = 'post.php'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- a simulated screen, restored below.
		try {
			as_role( 'author' );
			$r = call_request(
				'GET',
				array(
					'post'   => (string) $att,
					'action' => 'edit',
				),
				array(),
				'\spokares_trim_redirects'
			);
			assert_contains( 'wp-admin/index.php', (string) $r['redirect'], 'an Author opened Edit Media' );
			as_role( 'admin' );
			$r = call_request(
				'GET',
				array(
					'post'   => (string) $att,
					'action' => 'edit',
				),
				array(),
				'\spokares_trim_redirects'
			);
			assert_same( null, $r['redirect'], 'an administrator is sent away from Edit Media' );
		} finally {
			$pagenow = $saved; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- restoring.
		}
	}
);

test(
	'QA-081: a file refused for the "I checked this file" tick says so under the chooser after the save',
	function () {
		as_role( 'ares-editor' );
		$id  = create_post(
			array(
				'post_type'   => 'spk_document',
				'post_status' => 'draft',
				'post_title'  => 'QA admin privacy',
				'post_author' => get_current_user_id(),
			)
		);
		$tmp = wp_tempnam( 'qa-admin' );
		file_put_contents( $tmp, "%PDF-1.4\n%%EOF\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- a throwaway temp file for the simulated upload.
		// phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited, WordPress.Security.NonceVerification -- a simulated upload, put back below.
		$saved_files = $_FILES;
		$_FILES      = array(
			'spk_upload' => array(
				'name'     => 'form.pdf',
				'type'     => 'application/pdf',
				'tmp_name' => $tmp,
				'error'    => UPLOAD_ERR_OK,
				'size'     => (int) filesize( $tmp ),
			),
		);
		try {
			call_request(
				'POST',
				array(),
				array(
					'spokares_document_nonce' => wp_create_nonce( 'spokares_document_meta' ),
					'original_post_status'    => 'draft',
					'post_title'              => 'QA admin privacy',
					'spk_section'             => 'forms',
					'spk_source'              => 'upload',
				),
				static fn() => wp_update_post(
					array(
						'ID'          => $id,
						'post_status' => 'publish',
					)
				)
			);
		} finally {
			$_FILES = $saved_files;
			wp_delete_file( $tmp );
		}
		// phpcs:enable WordPress.WP.GlobalVariablesOverride.Prohibited, WordPress.Security.NonceVerification
		$html = qa_admin_render( '\spokares_document_form_fields', get_post( $id ) );
		assert_contains( 'Tick “I checked this file” first, then choose the file again.', $html, 'the field says why the file wasn’t uploaded' );
		assert_not_contains( 'Tick “I checked this file”, then choose the file.', $html, 'the field asks for a file although one was chosen' );
	}
);

/**
 * The Events list (wp-admin/edit.php?post_type=spk_event&…) as the current
 * user: the IDs listed. The main query and the globals it sets are put back.
 *
 * @param array $args Query args after post_type.
 */
function qa_admin_event_list( array $args ): array {
	// phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited -- a simulated edit.php main query; every global is put back below.
	global $wp, $wp_query, $wp_the_query, $pagenow;
	$before       = $GLOBALS;
	$saved        = array( $wp, $wp_query, $wp_the_query, $pagenow );
	$wp           = clone $wp;
	$wp_the_query = new \WP_Query();
	$wp_query     = $wp_the_query;
	$pagenow      = 'edit.php';
	$ids          = array();
	$vars         = array();
	try {
		call_request(
			'GET',
			array_merge( array( 'post_type' => 'spk_event' ), $args ),
			array(),
			static function () {
				wp_edit_posts_query();
			}
		);
		$ids  = array_map( 'intval', wp_list_pluck( $GLOBALS['wp_query']->posts, 'ID' ) );
		$vars = array_keys( (array) $GLOBALS['wp_query']->query_vars );
	} finally {
		list( $wp, $wp_query, $wp_the_query, $pagenow ) = $saved;
		foreach ( array_merge( $vars, array( 'query_string', 'posts', 'post', 'request', 'more', 'single', 'authordata' ) ) as $key ) {
			if ( array_key_exists( $key, $before ) ) {
				$GLOBALS[ $key ] = $before[ $key ]; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- restoring core's globals.
			} else {
				unset( $GLOBALS[ $key ] );
			}
		}
	}
	// phpcs:enable WordPress.WP.GlobalVariablesOverride.Prohibited
	return $ids;
}

test(
	'QA-086 and QA-088: a search looks through every event; spk_check=1 lists only flagged events',
	function () {
		as_role( 'admin' );
		$past = post_id( 'spk_event', 'past-2022-10-rockford-exercise' );
		update_user_option( get_current_user_id(), 'edit_spk_event_per_page', 500 );
		assert_not_contains( $past, qa_admin_event_list( array() ), 'set-up: the past Rockford exercise is not in the Upcoming view' );
		assert_contains( $past, qa_admin_event_list( array( 's' => 'Rockford' ) ), 'a search from the Upcoming view finds the past exercise' );

		$flagged = qa_admin_event_list( array( 'spk_check' => '1' ) );
		assert_true( count( $flagged ) > 0, 'set-up: some events are marked Needs checking' );
		foreach ( $flagged as $id ) {
			assert_same( '1', (string) get_post_meta( $id, 'spk_needs_check', true ), 'spk_check=1 lists an event not marked' );
		}
		foreach ( \spokares_needs_check_places() as $place ) {
			if ( str_contains( $place['url'], 'post_type=spk_event' ) ) {
				assert_contains( 'spk_check=1', $place['url'], 'the Events link filters to Needs checking' );
			}
		}
	}
);

test(
	'QA-087: Restore says the event came back as a draft',
	function () {
		as_role( 'ares-editor' );
		// The list's notice after core's redirect (edit.php?post_type=spk_event&untrashed=1),
		// which doesn't name what it restored.
		$notice = static fn(): string => (string) ( call_request(
			'GET',
			array(
				'post_type' => 'spk_event',
				'untrashed' => '1',
			),
			array(),
			static fn() => apply_filters( 'bulk_post_updated_messages', array(), array( 'untrashed' => 1 ) ) // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- core's filter.
		)['returned']['spk_event']['untrashed'] ?? '' );
		$id     = create_post(
			array(
				'post_type'   => 'spk_event',
				'post_status' => 'draft',
				'post_title'  => 'QA admin restored',
			)
		);
		wp_trash_post( $id );
		wp_untrash_post( $id );
		assert_same( '“QA admin restored” is back as a draft.', $notice(), 'the restore message' );
		$set = post_id( 'spk_event', 'set-2026' );
		wp_trash_post( $set );
		wp_untrash_post( $set );
		assert_not_contains( 'as a draft', $notice(), 'an Undo that restored a published event says draft' );
	}
);

test(
	'QA-089: a refused Page owner is said in the Page review box, with the typing kept',
	function () {
		as_role( 'ares-editor' );
		$about = page_id( 'about' );
		$r     = call_request(
			'POST',
			array(),
			array(
				'spokares_page_review_nonce' => wp_create_nonce( 'spokares_page_review' ),
				'spk_page_owner'             => 'Call 509-555-0123',
			),
			static fn() => \spokares_save_page_review( $about )
		);
		assert_same( null, $r['die'], 'the save ran' );
		assert_not_contains( '509-555-0123', (string) get_post_meta( $about, '_spk_owner', true ), 'the owner with a phone number was stored' );
		$html = qa_admin_render( '\spokares_render_page_review', get_post( $about ) );
		assert_contains( 'The page owner wasn’t saved', $html, 'the box says the owner wasn’t saved' );
		assert_contains( 'value="Call 509-555-0123"', $html, 'the typed owner is kept in the box' );
	}
);

test(
	'QA-091: opening Navigation in the Site Editor creates no stray menu',
	function () {
		assert_false( (bool) apply_filters( 'wp_navigation_should_create_fallback', true ), 'the fallback menu is still created' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- core's filter.
	}
);
