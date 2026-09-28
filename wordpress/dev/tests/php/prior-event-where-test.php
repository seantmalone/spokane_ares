<?php
/**
 * Regression tests for a Fixer-round bug that shipped without a test
 * (build-notes/plugin.md "Fixer round" › Events; PLAN §3.4): the event form
 * had no place for where an event happens, and after saving a published
 * event the editor was not told where on the site it now shows. The fix
 * (admin-events.php, render.php): an optional "Where" field (spk_where,
 * checked like the other text) printed on the Next up cards, Later this
 * season, the public-service rows and the hub list; the form says where each
 * kind shows; and a save of a published event queues a notice "On the site
 * now: … Events never show on Home."
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

/**
 * A new draft event for the test, as the current user.
 */
function prior_event_where_new(): int {
	return create_post(
		array(
			'post_type'   => 'spk_event',
			'post_status' => 'draft',
			'post_title'  => 'QA draft',
			'post_author' => get_current_user_id(),
		)
	);
}

/**
 * Save an event through its form, as the classic post.php save does: the
 * form's fields and nonce in $_POST, then wp_update_post().
 *
 * @param int    $id     Event.
 * @param array  $fields Form fields (post_title, spk_kind, spk_start, spk_where…).
 * @param string $status publish or draft (the button pressed).
 */
function prior_event_where_save( int $id, array $fields, string $status = 'publish' ): array {
	$post = array_merge(
		array(
			'spokares_event_nonce' => wp_create_nonce( 'spokares_event_meta' ),
			'spk_date_mode'        => 'date',
			'spk_all_day'          => '1',
		),
		$fields
	);
	return call_request(
		'POST',
		array(),
		$post,
		static function () use ( $id, $fields, $status ) {
			return wp_update_post(
				wp_slash(
					array(
						'ID'          => $id,
						'post_title'  => $fields['post_title'],
						'post_status' => $status,
					)
				),
				true
			);
		}
	);
}

/**
 * The notices queued for the current user (and clear them).
 */
function prior_event_where_notices(): array {
	$key     = 'spokares_notices_' . get_current_user_id();
	$notices = get_transient( $key );
	delete_transient( $key );
	return is_array( $notices ) ? $notices : array();
}

/**
 * A day this many days after the site's "today".
 *
 * @param int $days Days.
 */
function prior_event_where_day( int $days ): string {
	return gmdate( 'Y-m-d', (int) strtotime( spokares_today() . ' +' . $days . ' days' ) );
}

test(
	'the event form has a Where field, and says where each kind shows',
	function () {
		as_role( 'ares-editor' );
		$post = get_post( prior_event_where_new() );
		ob_start();
		spokares_event_form_top( $post );
		spokares_event_form_fields( $post );
		$html = (string) ob_get_clean();
		assert_matches( '/<label for="spk-where">Where \(optional\)<\/label>/', $html, 'label' );
		assert_matches( '/<input type="text" id="spk-where" name="spk_where"/', $html, 'field' );
		assert_contains( 'data-kinds="exercise">Where it shows: Exercises &amp; events › Later this season', $html, 'exercise: where it shows' );
		assert_contains( 'data-kinds="public-service">Where it shows: Exercises &amp; events › Public-service events. Never on Home.', $html, 'public service: where it shows' );
	}
);

test(
	'Where typed on the form is saved and shows on the Next up card and the hub list',
	function () {
		as_role( 'ares-editor' );
		$id = prior_event_where_new();
		$r  = prior_event_where_save(
			$id,
			array(
				'post_title' => 'QA Where exercise',
				'spk_kind'   => 'exercise',
				'spk_start'  => prior_event_where_day( 4 ),
				'spk_where'  => 'Spokane Valley Fire Station 8',
			)
		);
		expect_not_wp_error( $r['returned'], 'save' );
		clean_post_cache( $id );
		assert_same( 'publish', get_post_status( $id ), 'published' );
		assert_same( 'Spokane Valley Fire Station 8', get_post_meta( $id, 'spk_where', true ), 'stored' );

		$cards = spokares_render_events(
			array(
				'view'  => 'next-up',
				'types' => 'exercise',
				'limit' => 2,
			)
		);
		assert_matches( '/<article class="card ex-card[^"]*" id="qa-where-exercise">.*?<p class="ex-where"><span class="vh">Where: <\/span>Spokane Valley Fire Station 8<\/p>/s', $cards, 'Next up card' );
		$hub = spokares_render_events(
			array(
				'view'  => 'upcoming',
				'types' => 'exercise,training',
				'limit' => 2,
				'days'  => 60,
			)
		);
		assert_contains( 'QA Where exercise', $hub, 'hub list has the event' );
		assert_contains( '<span class="event__where"> · Spokane Valley Fire Station 8</span>', $hub, 'hub list' );
	}
);

test(
	'Where shows on Later this season and on the public-service rows',
	function () {
		as_role( 'ares-editor' );
		$training = prior_event_where_new();
		prior_event_where_save(
			$training,
			array(
				'post_title' => 'QA Where training',
				'spk_kind'   => 'training',
				'spk_start'  => prior_event_where_day( 75 ),
				'spk_where'  => 'From your own station',
			)
		);
		$public = prior_event_where_new();
		prior_event_where_save(
			$public,
			array(
				'post_title' => 'QA Where parade',
				'spk_kind'   => 'public-service',
				'spk_start'  => prior_event_where_day( 20 ),
				'spk_where'  => 'Riverfront Park',
			)
		);
		assert_same( 'publish', get_post_status( $training ), 'training published' );
		assert_same( 'publish', get_post_status( $public ), 'public service published' );
		$later = spokares_render_events(
			array(
				'view'      => 'later',
				'types'     => 'exercise,training,on-air',
				'limit'     => 12,
				'cardTypes' => 'exercise',
				'cardLimit' => 2,
			)
		);
		assert_matches( '/<tr id="qa-where-training">.*?<span class="ex-where"> \(From your own station\)<\/span><\/td><\/tr>/s', $later, 'Later this season' );
		$rows = spokares_render_events( array( 'view' => 'public-service' ) );
		assert_matches( '/<tr id="qa-where-parade">.*?<span class="ex-where"> \(Riverfront Park\)<\/span><\/th>/s', $rows, 'public-service rows' );
	}
);

test(
	'saving a published event queues a notice saying where it shows now',
	function () {
		as_role( 'ares-editor' );
		$id = prior_event_where_new();
		prior_event_where_notices();
		prior_event_where_save(
			$id,
			array(
				'post_title' => 'QA Where exercise',
				'spk_kind'   => 'exercise',
				'spk_start'  => prior_event_where_day( 4 ),
				'spk_where'  => 'Spokane Valley Fire Station 8',
			)
		);
		$info = array_values( array_filter( prior_event_where_notices(), static fn( $n ) => 'info' === $n['type'] ) );
		assert_count( 1, $info, 'one "where it shows" notice' );
		assert_matches( '/^On the site now: .*a Next up card on Exercises & events.*This week on For members.*\. Events never show on Home\.$/', $info[0]['text'], 'notice' );

		// An update of the published event says it again.
		prior_event_where_save(
			$id,
			array(
				'post_title' => 'QA Where exercise',
				'spk_kind'   => 'exercise',
				'spk_start'  => prior_event_where_day( 4 ),
				'spk_where'  => 'Station 8',
			)
		);
		$texts = wp_list_pluck( prior_event_where_notices(), 'text' );
		assert_true( (bool) preg_grep( '/^On the site now: /', $texts ), 'notice after an update: ' . export( $texts ) );
		assert_same( 'Station 8', get_post_meta( $id, 'spk_where', true ), 'updated' );
	}
);

test(
	'saving a draft event queues no "On the site now" notice',
	function () {
		as_role( 'ares-editor' );
		$id = prior_event_where_new();
		prior_event_where_notices();
		prior_event_where_save(
			$id,
			array(
				'post_title' => 'QA Where draft',
				'spk_kind'   => 'training',
				'spk_start'  => prior_event_where_day( 30 ),
				'spk_where'  => 'Station 8',
			),
			'draft'
		);
		assert_same( 'draft', get_post_status( $id ), 'draft' );
		assert_same( 'Station 8', get_post_meta( $id, 'spk_where', true ), 'stored on a draft too' );
		$texts = wp_list_pluck( prior_event_where_notices(), 'text' );
		assert_false( (bool) preg_grep( '/^On the site now: /', $texts ), 'no "On the site now" notice: ' . export( $texts ) );
	}
);

test(
	'Where is checked like the other text: a phone number keeps a new event a Draft and names the place',
	function () {
		as_role( 'ares-editor' );
		$id = prior_event_where_new();
		prior_event_where_notices();
		prior_event_where_save(
			$id,
			array(
				'post_title' => 'QA Where phone',
				'spk_kind'   => 'training',
				'spk_start'  => prior_event_where_day( 30 ),
				'spk_where'  => 'Call 509-555-0142 for the room',
			)
		);
		clean_post_cache( $id );
		assert_same( 'draft', get_post_status( $id ), 'kept a Draft' );
		$errors = wp_list_pluck( array_filter( prior_event_where_notices(), static fn( $n ) => 'error' === $n['type'] ), 'text' );
		assert_count( 1, $errors, 'one problem notice' );
		assert_contains( 'The place has', (string) reset( $errors ), 'the notice names the Where field' );
	}
);
