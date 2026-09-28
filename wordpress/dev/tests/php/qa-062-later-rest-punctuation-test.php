<?php
/**
 * Regression tests for QA-062 (PLAN §2 "Later this season" rows, §3.4 the
 * event's "Short line for lists" and its More links).
 *
 * A "Later this season" row prints the event title, then ": " and the short
 * line, then any More link that is not already the title's link. The link is
 * joined to the short line with ". " whatever the short line ends in, so a
 * short line that already ends in punctuation gets a second mark:
 * "County-wide drill. The EC asks you to:" and a More link "Forms" print
 * "…The EC asks you to:. Forms", and "County-wide drill." prints
 * "County-wide drill.. Forms". The row should read "…asks you to: Forms" and
 * "County-wide drill. Forms".
 *
 * The events are saved through the event form as an ARES Editor, as the
 * volunteer types them, and the rows are read from the Later this season
 * block with the attributes the members-exercises pattern uses.
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
function qa062_day( int $days ): string {
	return gmdate( 'Y-m-d', (int) strtotime( \spokares_today() . ' +' . $days . ' days' ) );
}

/**
 * Publish an event through the event form as the current user, as post.php
 * saves it. Returns the event ID.
 *
 * @param string $title  Event name.
 * @param array  $fields Other form fields (spk_summary, spk_main_url, spk_links…).
 * @param int    $days   Days from today to the event (after the Next up cards).
 */
function qa062_event( string $title, array $fields, int $days = 100 ): int {
	$id   = create_post(
		array(
			'post_type'   => 'spk_event',
			'post_status' => 'draft',
			'post_title'  => 'QA draft',
			'post_author' => get_current_user_id(),
		)
	);
	$post = array_merge(
		array(
			'spokares_event_nonce' => wp_create_nonce( 'spokares_event_meta' ),
			'post_title'           => $title,
			'spk_kind'             => 'exercise',
			'spk_date_mode'        => 'date',
			'spk_all_day'          => '1',
			'spk_start'            => qa062_day( $days ),
		),
		$fields
	);
	$r    = call_request(
		'POST',
		array(),
		$post,
		static function () use ( $id, $title ) {
			return wp_update_post(
				wp_slash(
					array(
						'ID'          => $id,
						'post_title'  => $title,
						'post_status' => 'publish',
					)
				),
				true
			);
		}
	);
	expect_not_wp_error( $r['returned'], 'set-up: save ' . $title );
	clean_post_cache( $id );
	assert_same( 'publish', get_post_status( $id ), 'set-up: ' . $title . ' is published' );
	if ( isset( $fields['spk_summary'] ) ) {
		assert_same( $fields['spk_summary'], (string) get_post_meta( $id, 'spk_summary', true ), 'set-up: the short line is stored as typed' );
	}
	return $id;
}

/**
 * The Later this season block as the members-exercises pattern renders it.
 */
function qa062_later(): string {
	return \spokares_render_events(
		array(
			'view'      => 'later',
			'types'     => 'exercise,training,on-air',
			'limit'     => 12,
			'cardTypes' => 'exercise',
			'cardLimit' => 2,
		)
	);
}

/**
 * What a reader sees in markup: hidden "(opens …)" text dropped, tags
 * stripped, entities decoded, spaces collapsed.
 *
 * @param string $html Markup.
 */
function qa062_visible( string $html ): string {
	$html = (string) preg_replace( '/<span class="vh">.*?<\/span>/s', '', $html );
	$text = html_entity_decode( wp_strip_all_tags( $html ), ENT_QUOTES, 'UTF-8' );
	return trim( (string) preg_replace( '/\s+/u', ' ', str_replace( "\u{00A0}", ' ', $text ) ) );
}

/**
 * The visible text of an event's "What" cell in Later this season.
 *
 * @param int $id Event.
 */
function qa062_row( int $id ): string {
	$slug  = (string) get_post_field( 'post_name', $id );
	$later = qa062_later();
	if ( ! preg_match( '/<tr id="' . preg_quote( $slug, '/' ) . '"[^>]*>.*?<td>(.*?)<\/td><\/tr>/s', $later, $m ) ) {
		fail( 'set-up: Later this season has no row for ' . $slug . ': ' . $later );
	}
	return qa062_visible( $m[1] );
}

/**
 * spokares_later_rest() for an event, as the Later this season row calls it.
 *
 * @param int $id Event.
 */
function qa062_rest( int $id ): string {
	$ev = \spokares_event_data( $id );
	return qa062_visible( \spokares_later_rest( $ev, \spokares_event_title_target( $ev ) ) );
}

/**
 * Fail when a mark of punctuation is followed by the ". " join.
 *
 * @param string $text  Visible text.
 * @param string $label What is checked.
 */
function qa062_assert_no_double( string $text, string $label ): void {
	foreach ( array( ':.', '..', '!.', '?.', ';.' ) as $pair ) {
		assert_not_contains( $pair, $text, $label . ': doubled punctuation' );
	}
}

test(
	'Later this season: a short line ending in ":" with a More link reads "…asks you to: Forms", not "to:. Forms" (every role)',
	function () {
		as_role( 'ares-editor' );
		$id = qa062_event(
			'QA-062 County drill',
			array(
				'spk_summary'    => 'County-wide drill. The EC asks you to:',
				'spk_main_url'   => 'https://example.org/drill',
				'spk_main_label' => 'Drill details',
				'spk_links'      => array(
					array(
						'label' => 'Forms',
						'url'   => 'https://example.org/forms',
					),
				),
			)
		);
		foreach ( role_keys() as $role ) {
			as_role( $role );
			$row = qa062_row( $id );
			assert_contains( 'QA-062 County drill: County-wide drill. The EC asks you to:', $row, $role . ': the title and the short line' );
			assert_contains( 'Forms', $row, $role . ': the More link is kept' );
			qa062_assert_no_double( $row, $role );
			assert_contains( 'The EC asks you to: Forms', $row, $role . ': the More link follows the colon' );
		}
	}
);

test(
	'Later this season: a short line ending in "." with a More link reads "drill. Forms", not "drill.. Forms"',
	function () {
		as_role( 'ares-editor' );
		$id  = qa062_event(
			'QA-062 Full stop drill',
			array(
				'spk_summary'  => 'County-wide drill.',
				'spk_main_url' => 'https://example.org/drill',
				'spk_links'    => array(
					array(
						'label' => 'Forms',
						'url'   => 'https://example.org/forms',
					),
				),
			)
		);
		$row = qa062_row( $id );
		assert_contains( 'QA-062 Full stop drill: County-wide drill.', $row, 'the title and the short line' );
		qa062_assert_no_double( $row, 'row' );
		assert_matches( '/County-wide drill\. Forms$/', $row, 'one full stop before the More link' );
	}
);

test(
	'Later this season: a More link after the title\'s own More link does not double the short line\'s colon',
	function () {
		as_role( 'ares-editor' );
		$id  = qa062_event(
			'QA-062 Two links drill',
			array(
				'spk_summary' => 'County-wide drill. The EC asks you to:',
				'spk_links'   => array(
					array(
						'label' => 'Sign-up sheet',
						'url'   => 'https://example.org/sign-up',
					),
					array(
						'label' => 'Forms',
						'url'   => 'https://example.org/forms',
					),
				),
			)
		);
		$row = qa062_row( $id );
		assert_not_contains( 'Sign-up sheet', $row, 'set-up: the first More link is the title\'s link, not repeated' );
		qa062_assert_no_double( $row, 'row' );
		assert_contains( 'The EC asks you to: Forms', $row, 'the second More link follows the colon' );
	}
);

test(
	'spokares_later_rest(): a short line ending in ":", ".", "!" or "?" gets no second mark before the More link',
	function () {
		as_role( 'ares-editor' );
		$cases = array(
			'County-wide drill. The EC asks you to:' => 'County-wide drill. The EC asks you to: Forms',
			'County-wide drill.'                     => 'County-wide drill. Forms',
			'Bring a charged radio!'                 => 'Bring a charged radio! Forms',
			'Can you check in by Winlink?'           => 'Can you check in by Winlink? Forms',
		);
		$n     = 0;
		foreach ( $cases as $summary => $want ) {
			++$n;
			$id   = qa062_event(
				'QA-062 Ending ' . $n,
				array(
					'spk_summary'  => $summary,
					'spk_main_url' => 'https://example.org/drill',
					'spk_links'    => array(
						array(
							'label' => 'Forms',
							'url'   => 'https://example.org/forms',
						),
					),
				),
				100 + $n
			);
			$rest = qa062_rest( $id );
			qa062_assert_no_double( $rest, '"' . $summary . '"' );
			assert_same( ': ' . $want, $rest, '"' . $summary . '" + More link "Forms"' );
		}
	}
);

test(
	'Later this season: a short line without end punctuation still gets ". " before a More link (control)',
	function () {
		as_role( 'ares-editor' );
		$id  = qa062_event(
			'QA-062 Plain drill',
			array(
				'spk_summary'  => 'For SHARES participants',
				'spk_main_url' => 'https://example.org/drill',
				'spk_links'    => array(
					array(
						'label' => 'Forms',
						'url'   => 'https://example.org/forms',
					),
				),
			)
		);
		$row = qa062_row( $id );
		assert_same( 'QA-062 Plain drill: For SHARES participants. Forms', $row, 'row' );
	}
);

test(
	'Later this season: a short line ending in "." with no extra link is printed as typed (control)',
	function () {
		as_role( 'ares-editor' );
		$id  = qa062_event(
			'QA-062 No link drill',
			array(
				'spk_summary' => 'County-wide drill.',
			)
		);
		$row = qa062_row( $id );
		assert_same( 'QA-062 No link drill: County-wide drill.', $row, 'row' );
		$shakeout = qa062_row( post_id( 'spk_event', 'shakeout-2026' ) );
		assert_same( 'Great ShakeOut: send a DYFI report by Winlink, marked as an exercise', $shakeout, 'the imported ShakeOut row, with DYFI linked inside its line' );
	}
);
