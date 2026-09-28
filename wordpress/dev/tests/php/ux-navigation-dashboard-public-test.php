<?php
/**
 * Tests for the volunteer-editor review's navigation, Dashboard and public
 * wording (ux/SPEC.md §2, §3.1, §3.8, §3.9, §3.10, §4.1, §4.5, §4.7, §4.12):
 *
 * - the Dashboard's Net Control Schedule line reads 52 Tuesdays, and
 *   Volunteer needed and No net count as posted;
 * - spokares_problem_sentence() gives the four under-field sentences, and
 *   spokares_check_text() hits carry the text they matched;
 * - spokares_clean_url() adds https:// to "www.arrl.org/kids-day";
 * - the public Net control cells read Volunteer needed, No net and Not
 *   posted yet, a No net row's note is its own, and No net Tuesdays have no
 *   Winlink assignment;
 * - the library's Most used filter always holds the four Most Used buttons'
 *   documents;
 * - the admin bar's Update lists items, the menu and the block hints take
 *   their names from spokares_edit_screen();
 * - the browser tab names the site, not WordPress (sign-in, editors' screens).
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

/**
 * The next 52 Tuesdays.
 *
 * @return string[]
 */
function uxnav_tuesdays(): array {
	return wp_list_pluck( \spokares_rota_rows( 52 ), 'date' );
}

/**
 * Store Net Control Schedule rows (the framework restores the option).
 *
 * @param array $rows Y-m-d => row.
 */
function uxnav_set_rota( array $rows ): void {
	update_option( 'spk_rota', $rows );
	\spokares_opt_flush();
}

/**
 * The Site tasks widget as the current user sees it.
 */
function uxnav_dashboard(): string {
	ob_start();
	\spokares_dashboard_widget();
	return (string) ob_get_clean();
}

/**
 * Plain text of some HTML, spaces collapsed.
 *
 * @param string $html HTML.
 */
function uxnav_plain( string $html ): string {
	$text = html_entity_decode( wp_strip_all_tags( $html ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	return trim( (string) preg_replace( '/\s+/u', ' ', str_replace( "\u{00A0}", ' ', $text ) ) );
}

/**
 * A block as the site draws it for the current user.
 *
 * @param string $name  Block name without "spokares/".
 * @param array  $attrs Attributes.
 */
function uxnav_block( string $name, array $attrs ): string {
	return do_blocks( '<!-- wp:spokares/' . $name . ' ' . wp_json_encode( $attrs ) . ' /-->' );
}

/**
 * One row of the public Net Control Schedule table, by its Tuesday.
 *
 * @param string $html The rota view's HTML.
 * @param string $ymd  Tuesday.
 */
function uxnav_rota_row( string $html, string $ymd ): string {
	if ( ! preg_match( '#<tr[^>]*><th scope="row"><time datetime="' . preg_quote( $ymd, '#' ) . '">.*?</tr>#s', $html, $m ) ) {
		fail( 'no row for ' . $ymd . ' in: ' . $html );
	}
	return $m[0];
}

test(
	'Dashboard: Volunteer needed and No net count as posted over 52 Tuesdays; the gaps are named',
	function () {
		as_role( 'ares-net' );
		$t = uxnav_tuesdays();
		assert_count( 52, $t, 'set-up: 52 Tuesdays' );
		uxnav_set_rota(
			array(
				$t[0]  => array(
					'state' => 'call',
					'call'  => 'NZ2S',
				),
				$t[1]  => array( 'state' => 'open' ),
				$t[3]  => array(
					'state' => 'none',
					'note'  => 'Holiday break',
				),
				$t[20] => array( 'state' => 'open' ),
				$t[30] => array( 'state' => 'none' ),
			)
		);
		$sum = \spokares_rota_summary();
		assert_same( $t[30], $sum['through'], 'a No net Tuesday 30 weeks out is the last one posted (the old summary read 13)' );
		assert_same( array( $t[1], $t[20] ), $sum['open'], 'the Volunteer needed Tuesdays up to it' );
		assert_contains( $t[2], $sum['tbd'], 'a Tuesday nobody posted is Not posted yet' );
		assert_not_contains( $t[3], $sum['tbd'], 'a No net Tuesday is posted' );
		assert_not_contains( $t[30], $sum['tbd'], 'the last posted Tuesday itself' );
		assert_false( $sum['short'], 'posted 30 weeks ahead does not run out within 3 weeks' );

		$text = uxnav_plain( uxnav_dashboard() );
		assert_contains( 'Posted through ' . \spokares_fmt_date( $t[30], 'short' ) . ' · Volunteer needed: ' . \spokares_fmt_date( $t[1], 'day' ) . ' and ' . \spokares_fmt_date( $t[20], 'day' ) . ' · Not posted yet: ' . \spokares_fmt_date( $t[2], 'day' ) . ', ', $text, 'the status line' );
		assert_not_contains( 'runs out', $text, 'no Needs attention line' );
		assert_not_contains( 'open slot', $text, 'the retired "open slot" words' );

		// Only a No net Tuesday next week: posted, but it runs out soon.
		uxnav_set_rota( array( $t[1] => array( 'state' => 'none' ) ) );
		$sum = \spokares_rota_summary();
		assert_same( $t[1], $sum['through'], 'No net alone counts as posted' );
		assert_same( array(), $sum['open'], 'no Volunteer needed' );
		assert_same( array( $t[0] ), $sum['tbd'], 'the Tuesday before it is Not posted yet' );
		assert_true( $sum['short'], 'it runs out within 3 weeks' );
		$html = uxnav_dashboard();
		assert_matches( '#<li>The Net Control Schedule runs out in under 3 weeks\. <a href="[^"]*page=spokares-rota">Fill in more Tuesdays</a></li>#', $html, 'the Needs attention line and its link' );
		assert_true( strpos( $html, 'spk-attention' ) < strpos( $html, '<section class="spk-task">' ), 'Needs attention is printed above the sections' );

		uxnav_set_rota( array() );
		assert_contains( 'Nothing posted for the coming Tuesdays.', uxnav_plain( uxnav_dashboard() ), 'nothing posted' );
	}
);

test(
	'Dashboard: sections are named as their screens, with the spec\'s buttons and no number badges',
	function () {
		as_role( 'ares-net' );
		$html = uxnav_dashboard();
		preg_match_all( '#<section class="spk-task">\s*<h3>(.*?)</h3>#s', $html, $m );
		assert_same( array( 'Net Control Schedule', 'Exercises &amp; Events', 'Meetings', 'Documents', 'Page Text' ), $m[1], 'section headings' );
		assert_not_contains( 'spk-task__n', $html, 'number badges' );
		foreach ( array( 'Update the schedule', 'Add an event', 'Change or cancel an event', 'Cancel or move a meeting', 'Meeting Schedule', 'Add a document', 'Replace or change a document', 'Most Used', 'Net Settings' ) as $words ) {
			assert_contains( '>' . $words . '</a>', $html, 'grant holder: "' . $words . '"' );
		}
		assert_matches( '#Next: <a href="[^"]*post\.php\?post=\d+&(amp;|\#038;)?action=edit">[^<]+</a>, #', $html, 'the next event\'s name links to its form' );
		assert_matches( '#\d+ documents?#', uxnav_plain( $html ), '"{n} documents"' );
		assert_not_contains( 'in the library', $html, 'the retired "in the library"' );

		as_role( 'ares-editor' );
		$text = uxnav_plain( uxnav_dashboard() );
		assert_contains( 'Repeater and net times: ask the webmaster.', $text, 'plain editor: whom to ask' );
		assert_not_contains( 'Net Settings', $text, 'plain editor: no Net Settings link' );
		assert_not_contains( 'Meeting Schedule', $text, 'plain editor: no Meeting Schedule link' );
	}
);

test(
	'Dashboard: "{n} marked Soon" counts what the Soon view lists (a FEMA course shows its course links, not Soon); Page Text names what it found and links the page',
	function () {
		as_role( 'ares-net' );
		// What members see as "Soon": published, not ready yet, and no course links in its place.
		$soon    = 0;
		$courses = 0;
		$ids     = get_posts(
			array(
				'post_type'   => 'spk_document',
				'post_status' => 'publish',
				'numberposts' => -1,
				'fields'      => 'ids',
			)
		);
		foreach ( $ids as $id ) {
			if ( \spokares_document_is_soon( (int) $id ) ) {
				++$soon;
			} elseif ( 'soon' === (string) get_post_meta( $id, 'spk_source', true ) ) {
				++$courses;
			}
		}
		assert_true( $soon > 0, 'set-up: the seed has documents marked Soon' );
		assert_true( $courses > 0, 'set-up: the seed has a not-ready document whose course links show instead' );
		assert_same( $soon, \spokares_document_soon_count(), 'the Soon view lists the same documents' );
		assert_matches( '#<a href="[^"]*post_type=spk_document&(amp;|\#038;)?spk_source=soon">' . $soon . ' marked Soon</a>#', uxnav_dashboard(), 'the Soon link' );

		$how = page_id( 'how-it-works' );
		as_role( 'admin' );
		wp_update_post(
			array(
				'ID'           => $how,
				'post_content' => get_post( $how )->post_content . '<!-- wp:paragraph --><p>Call Frank at 509-555-0142.</p><!-- /wp:paragraph -->',
			)
		);
		clean_post_cache( $how );
		as_role( 'ares-net' );
		$html = uxnav_dashboard();
		assert_matches( '#<li>How it works has what looks like a phone number \(509-555-0142\)\. <a href="[^"]*post\.php\?post=' . $how . '&(amp;|\#038;)?action=edit">Open the page</a></li>#', $html, 'the page-text line' );
	}
);

test(
	'spokares_problem_sentence(): one sentence per problem; spokares_check_text() hits carry what they matched',
	function () {
		$cases = array(
			'Our hospital net meets on Tuesdays.' => 'It mentions a hospital net, which we never publish. Take it out.',
			'Call 509-555-0142.'                  => 'It has a phone number. Take it out, or tick the box if it’s a public agency number.',
			'Write to someone@example.com.'       => 'It has an e-mail address. Take it out, or tick the box if it’s a public agency address.',
			'Call 509-555-0142 or write someone@example.com' => 'It has a phone number and an e-mail address. Take them out, or tick the box if they’re public agency contacts.',
			'Hospital net: call 509-555-0142.'    => 'It mentions a hospital net, which we never publish. Take it out.',
			'Tuesday net at 8 PM.'                => '',
		);
		foreach ( $cases as $text => $sentence ) {
			assert_same( $sentence, \spokares_problem_sentence( \spokares_check_field( $text, 'note' ) ), $text );
		}
		assert_same( '', \spokares_problem_sentence( \spokares_check_field( 'Call 509-555-0142.', 'note', array(), true ) ), 'a ticked public agency number is no problem' );

		$hits = \spokares_check_text( 'Call   Frank at (509) 555-0142 today.' );
		assert_same( '(509) 555-0142', $hits[0]['match'] ?? null, 'the phone hit carries the text found' );
		$hits = \spokares_check_text( 'Our Hospital  Net' );
		assert_same( 'Hospital Net', $hits[0]['match'] ?? null, 'spaces collapsed' );
		$long = \spokares_check_text( 'mail ' . str_repeat( 'a', 80 ) . '@example.com' );
		assert_same( 60, mb_strlen( (string) ( $long[0]['match'] ?? '' ) ), 'the match is cut to 60 characters' );
	}
);

test(
	'spokares_clean_url(): an address typed without https:// gets it; other rules unchanged',
	function () {
		assert_same( 'https://www.arrl.org/kids-day', \spokares_clean_url( 'www.arrl.org/kids-day' ), 'www.arrl.org/kids-day' );
		assert_same( 'https://arrl.org', \spokares_clean_url( 'arrl.org' ), 'arrl.org' );
		assert_same( 'https://www.arrl.org:8443/x', \spokares_clean_url( 'www.arrl.org:8443/x' ), 'a port is not a scheme' );
		assert_same( 'https://www.arrl.org/', \spokares_clean_url( 'https://www.arrl.org/' ), 'https kept' );
		assert_same( '', \spokares_clean_url( 'http://www.arrl.org/' ), 'http still refused' );
		assert_same( '', \spokares_clean_url( 'Frank Smith' ), 'words are not an address' );
		assert_same( '', \spokares_clean_url( 'someone@example.com' ), 'an e-mail address is not a web address' );
		assert_same( '', \spokares_clean_url( 'javascript:alert(1)' ), 'another scheme is refused' );
		assert_same( 'mailto:webmaster@spokares.org', \spokares_clean_url( 'mailto:webmaster@spokares.org', true ), 'role mailto kept' );
	}
);

test(
	'public Net Control Schedule: Volunteer needed, No net (with only its own note) and Not posted yet; No net Tuesdays have no Winlink assignment',
	function () {
		$t   = uxnav_tuesdays();
		$wl  = '';
		$wl2 = '';
		foreach ( array_slice( $t, 2, 24 ) as $ymd ) {
			if ( 'winlink' === \spokares_net_on( $ymd )['kind'] ) {
				if ( '' === $wl ) {
					$wl = $ymd;
				} elseif ( '' === $wl2 ) {
					$wl2 = $ymd;
				}
			}
		}
		assert_true( '' !== $wl && '' !== $wl2, 'set-up: two Winlink nights' );
		uxnav_set_rota(
			array(
				$t[0] => array(
					'state' => 'none',
					'note'  => 'Holiday break',
				),
				$t[1] => array( 'state' => 'open' ),
				$wl   => array(
					'state'   => 'none',
					'wl_task' => 'QA none task',
				),
				$wl2  => array(
					'state'   => 'call',
					'call'    => 'NZ2S',
					'wl_task' => 'QA held task',
				),
			)
		);
		as_anonymous();
		$html = uxnav_block(
			'net',
			array(
				'view'  => 'rota',
				'weeks' => 26,
			)
		);
		$none = uxnav_rota_row( $html, $t[0] );
		assert_contains( '<span class="rota-tbd rota-none">No net</span>', $none, 'the No net cell' );
		assert_matches( '#<td data-label="Note"[^>]*>Holiday break</td>#', $none, 'the No net row\'s note is its own only' );
		assert_contains( '<a class="tag tag--open" href="#open-slot">Volunteer needed</a>', uxnav_rota_row( $html, $t[1] ), 'the Volunteer needed tag still links to the line under the table' );
		assert_contains( '<span class="rota-tbd">Not posted yet</span>', $html, 'a Tuesday not posted' );
		assert_not_contains( 'Not yet published', $html, 'the retired "Not yet published"' );
		assert_not_contains( '>Open<', $html, 'the retired "Open" tag' );

		$winlink = uxnav_block(
			'net',
			array(
				'view'  => 'winlink',
				'limit' => 20,
			)
		);
		assert_not_contains( 'QA none task', $winlink, 'a No net Tuesday lists no assignment' );
		assert_contains( 'QA held task', $winlink, 'a Winlink night with a call sign keeps its assignment (control)' );
	}
);

test(
	'a cancelled event keeps its Later this season row, with a Cancelled tag after When',
	function () {
		$attrs = array(
			'view'      => 'later',
			'types'     => 'exercise,training,on-air',
			'limit'     => 12,
			'cardTypes' => 'exercise',
			'cardLimit' => 2,
		);
		$pick  = null;
		foreach ( \spokares_events( 'later', $attrs ) as $ev ) {
			if ( 'date' === $ev['mode'] ) {
				$pick = $ev;
				break;
			}
		}
		assert_true( null !== $pick, 'set-up: a dated event under Later this season' );
		update_post_meta( $pick['id'], 'spk_cancelled', '1' );
		clean_post_cache( $pick['id'] );
		as_anonymous();
		$html = uxnav_block( 'events', $attrs );
		assert_matches( '#<tr id="' . preg_quote( $pick['slug'], '#' ) . '"[^>]*><th scope="row">[^<]+ <span class="tag tag--line">Cancelled</span></th>#', $html, 'the row stays, tagged after When' );
		assert_same( 1, substr_count( $html, '>Cancelled</span>' ), 'only the cancelled event is tagged' );
	}
);

test(
	'a meeting date with a note on its own: Home says it after the date, the For members page as a sentence',
	function () {
		$item = null;
		foreach ( \spokares_next_meetings( 1 ) as $x ) {
			if ( $x['meeting']['show_home'] && ! empty( $x['dates'][0] ) && ( ! $item || $x['dates'][0]['date'] < $item['dates'][0]['date'] ) ) {
				$item = $x;
			}
		}
		assert_true( null !== $item, 'set-up: a meeting shown on Home' );
		$date = $item['dates'][0]['date'];
		$opt  = get_option( 'spk_meetings' );

		$opt['changes'][] = array(
			'date'    => $date,
			'meeting' => $item['meeting']['id'],
			'kind'    => 'note',
			'note'    => 'Starts at 10:00 AM this time',
		);
		update_option( 'spk_meetings', $opt );
		\spokares_opt_flush();
		as_anonymous();
		$home = uxnav_plain( uxnav_block( 'meetings', array( 'view' => 'home' ) ) );
		assert_contains( 'Next: ' . \spokares_fmt_date( $date, 'short' ) . ' (Starts at 10:00 AM this time)', $home, 'Home' );
		$members = uxnav_plain( uxnav_block( 'meetings', array( 'view' => 'next' ) ) );
		if ( $date <= \spokares_add_days( \spokares_today(), 14 ) ) {
			assert_contains( \spokares_fmt_date( $date, 'short' ) . ': ' . $item['meeting']['name'] . '. Starts at 10:00 AM this time.', $members, 'For members, within 14 days' );
		} else {
			assert_not_contains( 'Starts at 10:00 AM', $members, 'For members lists changes within 14 days only' );
		}
	}
);

test(
	'the library\'s Most used filter always holds the four Most Used buttons\' documents',
	function () {
		$tiles = array_filter( array_map( 'absint', wp_list_pluck( array_slice( \spokares_opt( 'spk_tiles' ), 0, 4 ), 'doc' ) ) );
		assert_true( count( $tiles ) > 0, 'set-up: Most Used buttons are set' );
		foreach ( $tiles as $id ) {
			delete_post_meta( $id, 'spk_most_used' );
		}
		$most = array();
		$not  = array();
		foreach ( \spokares_library_documents() as $docs ) {
			foreach ( $docs as $doc ) {
				if ( $doc['most_used'] ) {
					$most[] = (int) $doc['id'];
				} elseif ( '1' !== (string) get_post_meta( $doc['id'], 'spk_most_used', true ) ) {
					$not[] = (int) $doc['id'];
				}
			}
		}
		foreach ( $tiles as $id ) {
			if ( null !== \spokares_public_document( $id ) ) {
				assert_contains( $id, $most, 'button document ' . get_the_title( $id ) . ' is under Most used without its tick' );
			}
		}
		assert_true( count( $not ) > 0, 'a document neither ticked nor a button stays out of Most used (control)' );
	}
);

test(
	'a document row prints its note, then its how-to link, each on its own line',
	function () {
		$id = post_id( 'spk_document', 'ics-213' );
		update_post_meta( $id, 'spk_note', 'QA short note' );
		update_post_meta( $id, 'spk_howto_label', 'QA how to' );
		update_post_meta( $id, 'spk_howto_url', 'https://example.org/how' );
		$post = get_post( $id );
		$cell = \spokares_document_title_cell( \spokares_document_data( $post ) );
		assert_matches( '#<span class="doc-note">QA short note</span><span class="doc-note"><a[^>]*href="https://example\.org/how"[^>]*>QA how to(<span class="vh">[^<]*</span>)?</a></span>#', $cell, 'note, then the how-to link' );
	}
);

test(
	'names come from spokares_edit_screen(): the admin bar\'s Update lists, the menu and the block hints',
	function () {
		assert_same( 'Net Control Schedule', \spokares_edit_screen( 'rota' )[0], 'rota' );
		assert_same( 'Meeting Schedule', \spokares_edit_screen( 'meeting-rules' )[0], 'the new meeting-rules key' );
		assert_contains( 'page=spokares-meeting-rules', \spokares_edit_screen( 'meeting-rules' )[1], 'its URL' );

		// The front-end admin bar (is_admin() reads the current screen).
		require_once ABSPATH . WPINC . '/class-wp-admin-bar.php';
		as_role( 'ares-net' );
		$saved                     = $GLOBALS['current_screen'] ?? null;
		$GLOBALS['current_screen'] = new class() { // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- a front-end request, restored below.
			/**
			 * Not an admin screen.
			 */
			public function in_admin(): bool {
				return false;
			}
		};
		try {
			$bar = new \WP_Admin_Bar();
			\spokares_admin_bar( $bar );
		} finally {
			$GLOBALS['current_screen'] = $saved; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- restoring.
		}
		$items = array();
		foreach ( array( 'rota', 'events', 'meetings', 'documents', 'tiles' ) as $key ) {
			$node = $bar->get_node( 'spokares-lists-' . $key );
			assert_true( null !== $node, 'Update lists › ' . $key );
			$screen = \spokares_edit_screen( $key );
			assert_same( $screen[0], $node->title, 'Update lists › ' . $key . ' title' );
			assert_same( $screen[1], $node->href, 'Update lists › ' . $key . ' link' );
			$items[] = $node->title;
		}
		assert_same( array( 'Net Control Schedule', 'Exercises & Events', 'Meetings', 'Documents', 'Most Used' ), $items, 'the items' );

		// The block hints in the editor preview.
		$saved_route                    = $GLOBALS['spokares_rest_route'] ?? null;
		$GLOBALS['spokares_rest_route'] = '/wp/v2/block-renderer/spokares/net'; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- the plugin's own global.
		try {
			assert_same( '<p class="spk-edit-hint">Change this on the Net Control Schedule screen.</p>', \spokares_block_tail( 'rota' ), 'grant holder, rota' );
			assert_same( '<p class="spk-edit-hint">Change this on the Net Settings screen.</p>', \spokares_block_tail( 'net-details' ), 'grant holder, net-details' );
			assert_same( '<p class="spk-edit-hint">Change this in the Page review box.</p>', \spokares_block_tail( 'page-review' ), 'page review' );
			as_role( 'ares-editor' );
			assert_same( '<p class="spk-edit-hint">The webmaster sets this.</p>', \spokares_block_tail( 'net-details' ), 'plain editor, net-details' );
		} finally {
			$GLOBALS['spokares_rest_route'] = $saved_route; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- restoring.
		}
	}
);

test(
	'the menu: Net Control Schedule, Exercises & Events, Meetings, Documents, Page Text in that order; the meeting screens under Meetings',
	function () {
		$order = \spokares_menu_order( array( 'index.php', 'separator1', 'edit.php?post_type=page', 'profile.php', 'edit.php?post_type=spk_document', 'spokares-meetings', 'edit.php?post_type=spk_event', 'spokares-rota' ) );
		assert_same( array( 'index.php', 'separator1', 'spokares-rota', 'edit.php?post_type=spk_event', 'spokares-meetings', 'edit.php?post_type=spk_document', 'edit.php?post_type=page', 'profile.php' ), $order, 'menu order' );

		global $menu, $submenu;
		$saved = array( $menu, $submenu );
		as_role( 'ares-net' );
		try {
			$menu    = array(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- rebuilt for the test, restored below.
			$submenu = array(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- rebuilt for the test, restored below.
			\spokares_admin_menu();
			$top = array();
			foreach ( $menu as $item ) {
				$top[ $item[2] ] = $item[0];
			}
			assert_same( 'Net Control Schedule', $top['spokares-rota'] ?? null, 'Net Control Schedule menu' );
			assert_same( 'Meetings', $top['spokares-meetings'] ?? null, 'Meetings menu' );
			assert_same( array( 'Cancel or Move a Meeting', 'Meeting Schedule' ), wp_list_pluck( $submenu['spokares-meetings'] ?? array(), 0 ), 'Meetings submenu' );
			assert_same( array( 'Net Control Schedule', 'Net Settings' ), wp_list_pluck( $submenu['spokares-rota'] ?? array(), 0 ), 'Net Control Schedule submenu' );
			assert_same( array( 'Most Used' ), wp_list_pluck( $submenu['edit.php?post_type=spk_document'] ?? array(), 0 ), 'Documents › Most Used' );
			assert_false( isset( $submenu['edit.php?post_type=spk_event'] ), 'no meeting screens under Exercises & Events' );
		} finally {
			list( $menu, $submenu ) = $saved; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- restoring.
		}
	}
);

test(
	'Help: a screen with no lines still gets the How to tab with only "Stuck?"; the lists have their own keys',
	function () {
		$lines = \spokares_help_lines();
		foreach ( array( 'spokares-meeting-rules', 'spk_event', 'spk_document_list' ) as $key ) {
			assert_same( array(), $lines[ $key ] ?? null, $key . ' has no lines' );
		}
		assert_count( 2, $lines['spk_event_list'] ?? array(), 'the events list has its two lines' );
		assert_count( 1, $lines['page_list'] ?? array(), 'the Page Text list has its line' );
		foreach ( $lines as $key => $list ) {
			foreach ( $list as $line ) {
				assert_false( (bool) preg_match( '/\brota\b|members hub|Hub tiles|Net details|Meeting rules/i', $line ), $key . ': a retired word in "' . $line . '"' );
			}
		}

		global $current_screen;
		$saved = $current_screen;
		try {
			set_current_screen( 'toplevel_page_spokares-meetings' );
			\spokares_screen_help( 'spokares-meeting-rules' );
			$tab = get_current_screen()->get_help_tab( 'spokares-help' );
			assert_true( is_array( $tab ), 'the How to tab is added' );
			assert_same( 'How to', $tab['title'], 'its title' );
			assert_not_contains( '<ul>', $tab['content'], 'no empty list' );
			assert_contains( 'Stuck?', $tab['content'], '"Stuck?"' );
		} finally {
			$current_screen = $saved; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- restoring.
		}
	}
);

test(
	'the plain Net Settings and Meeting Schedule refusals are two whole sentences',
	function () {
		foreach (
			array(
				'spokares-net-details'   => 'Net Settings (the repeater, the net times and which Tuesdays are Winlink, simplex or GMRS) are changed by the webmaster, or by someone the Emergency Coordinator has named. Ask the webmaster: webmaster@spokares.org.',
				'spokares-meeting-rules' => 'The Meeting Schedule (the regular meetings’ weeks, days and times) is changed by the webmaster, or by someone the Emergency Coordinator has named. Ask the webmaster: webmaster@spokares.org.',
			) as $page => $sentence
		) {
			as_role( 'ares-editor' );
			$res = call_request( 'GET', array( 'page' => $page ), array(), static fn() => \spokares_access_denied_message() );
			assert_same( 403, $res['status'], $page . ': refused' );
			assert_contains( esc_html( $sentence ), (string) $res['die'], $page );
		}
	}
);

test(
	'sign-in: the Two-Factor plugin\'s words are plain, and the phone app is asked for first once it is set up',
	function () {
		if ( ! class_exists( 'Two_Factor_Core' ) ) {
			skip( 'the Two-Factor plugin is not active' );
		}
		// phpcs:disable WordPress.WP.I18n.TextDomainMismatch -- the Two-Factor plugin's own strings, as it asks for them.
		assert_same( 'Use the code from my phone app', __( 'Use your authenticator app for time-based one-time passwords (TOTP)', 'two-factor' ), 'TOTP' );
		assert_same( 'E-mail me a code', __( 'Send a code to your email', 'two-factor' ), 'e-mail' );
		assert_same( 'Use a printed backup code', __( 'Use a recovery code', 'two-factor' ), 'backup codes' );
		foreach ( array( 'Authentication Code:', 'Verification Code:', 'Recovery Code:' ) as $label ) {
			assert_same( 'Code:', __( $label, 'two-factor' ), $label ); // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- the plugin's own labels.
		}
		assert_same( 'That code didn’t work. Wait for the next code in the app, then type it.', __( 'ERROR: Invalid verification code.', 'two-factor' ), 'one wrong code' );
		assert_same( 'Invalid verification code.', __( 'Invalid verification code.', 'default' ), 'other text domains are untouched (control)' );
		// phpcs:enable WordPress.WP.I18n.TextDomainMismatch

		foreach ( array( 'ares-net', 'admin' ) as $role ) {
			$id = user_id( $role );
			update_user_meta( $id, '_two_factor_enabled_providers', array( 'Two_Factor_Email', 'Two_Factor_Totp' ) );
			update_user_meta( $id, '_two_factor_provider', 'Two_Factor_Email' );
			update_user_meta( $id, '_two_factor_totp_key', 'ABCDEFGHIJKLMNOP' );
			assert_same( 'Two_Factor_Totp', get_user_meta( $id, '_two_factor_provider', true ), $role . ': setting up the phone app makes it the stored primary method' );
			update_user_meta( $id, '_two_factor_provider', 'Two_Factor_Email' );
			$primary = \Two_Factor_Core::get_primary_provider_for_user( $id );
			assert_same( 'admin' === $role ? 'Two_Factor_Email' : 'Two_Factor_Totp', $primary ? get_class( $primary ) : '', $role . ': the method asked for first (an administrator\'s own choice stands)' );
		}
	}
);

test(
	'profile: an editor\'s hidden Nickname follows First + Last name, and an empty one never stops the save',
	function () {
		require_once ABSPATH . 'wp-admin/includes/user.php';
		$user   = as_role( 'ares-net' );
		$fields = array(
			'first_name'   => 'Frank',
			'last_name'    => 'Tester',
			'nickname'     => '',
			'display_name' => $user->display_name,
			'email'        => $user->user_email,
		);
		$res    = call_request( 'POST', array(), $fields, static fn() => edit_user( $user->ID ) );
		assert_false( is_wp_error( $res['returned'] ), 'the save was refused: ' . ( is_wp_error( $res['returned'] ) ? $res['returned']->get_error_message() : '' ) );
		clean_user_cache( $user->ID );
		assert_same( 'Frank Tester', get_user_meta( $user->ID, 'nickname', true ), 'the nickname is the name' );

		$admin = as_role( 'admin' );
		$res   = call_request(
			'POST',
			array(),
			array(
				'first_name'   => 'Web',
				'last_name'    => 'Master',
				'nickname'     => 'webby',
				'display_name' => $admin->display_name,
				'email'        => $admin->user_email,
			),
			static fn() => edit_user( $admin->ID )
		);
		clean_user_cache( $admin->ID );
		assert_same( 'webby', get_user_meta( $admin->ID, 'nickname', true ), 'an administrator keeps the nickname typed (control)' );
	}
);

test(
	'an old bookmark of a meeting screen under Exercises & Events opens it at its new address',
	function () {
		global $pagenow;
		$saved = $pagenow;
		as_role( 'ares-net' );
		try {
			$pagenow = 'edit.php'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- the old address, restored below.
			foreach ( array( 'spokares-meetings', 'spokares-meeting-rules' ) as $page ) {
				$res = call_request(
					'GET',
					array(
						'post_type' => 'spk_event',
						'page'      => $page,
					),
					array(),
					static fn() => \spokares_old_meeting_urls()
				);
				assert_same( admin_url( 'admin.php?page=' . $page ), $res['redirect'], $page );
			}
			$res = call_request( 'GET', array( 'post_type' => 'spk_event' ), array(), static fn() => \spokares_old_meeting_urls() );
			assert_same( null, $res['redirect'], 'the events list itself stays (control)' );
		} finally {
			$pagenow = $saved; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- restoring.
		}
	}
);

test(
	'the browser tab names the site, not WordPress: the sign-in screen for everyone, the admin screens for editors',
	function () {
		$built = 'Log In &lsaquo; Spokane County ARES-ACS &#8212; WordPress';
		assert_same( 'Log In &lsaquo; Spokane County ARES-ACS', apply_filters( 'login_title', $built, 'Log In' ), 'sign-in' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- core's filter.

		$built = 'Net Control Schedule &lsaquo; Spokane County ARES-ACS &#8212; WordPress';
		foreach ( array( 'ares-editor', 'ares-net' ) as $role ) {
			as_role( $role );
			assert_same( 'Net Control Schedule &lsaquo; Spokane County ARES-ACS', apply_filters( 'admin_title', $built, 'Net Control Schedule' ), $role . ': an admin screen' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- core's filter.
		}
		as_role( 'admin' );
		assert_same( $built, apply_filters( 'admin_title', $built, 'Net Control Schedule' ), 'an administrator keeps WordPress\'s tab title (control)' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- core's filter.
	}
);
