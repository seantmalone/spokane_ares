<?php
/**
 * Regression tests for workflow review E5 (ux/workflows/content.py; PLAN
 * §3.4 events, UX spec §3.4 "fields that don't apply to the type are
 * hidden"): change an Exercise to Public service and its tasks, links and
 * button stayed stored, hidden and unchecked; switch back and the old text,
 * or an old phone-number "Publish it" tick, came back.
 *
 * Now a save clears the boxes the chosen type doesn't use (the one map,
 * spokares_event_kind_fields(), that also writes the form's data-kinds),
 * drops their ticks, and the notice names them ("Cleared What members do
 * and More links: this type of event doesn’t use them."). "List under Past
 * exercises" is cleared without a word (it is ticked by default on Add). A
 * box the type doesn't use is not checked either, so an over-long hidden
 * Short description can't keep a Public service event off the site.
 *
 * The saves use the helpers of ux-events-meetings-test.php (uxevt_click(),
 * uxevt_event(), uxevt_form(), uxevt_day()).
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

/**
 * A published exercise with every exercise-only box filled in.
 */
function wfe5_exercise(): int {
	$docs = get_posts(
		array(
			'post_type'   => 'spk_document',
			'post_status' => 'publish',
			'numberposts' => 1,
			'fields'      => 'ids',
		)
	);
	if ( ! $docs ) {
		fail( 'set-up: no published document' );
	}
	return uxevt_event(
		'WF-E5 exercise',
		array(
			'spk_kind'       => 'exercise',
			'spk_start'      => uxevt_day( 30 ),
			'spk_summary'    => 'Countywide drill',
			'spk_tasks'      => "Check in on the repeater\nRelay the WF-E5 traffic",
			'spk_main_url'   => 'https://www.arrl.org/set',
			'spk_main_label' => 'SET details',
			'spk_links'      => array(
				array(
					'label' => 'Forms',
					'url'   => 'https://www.arrl.org/forms',
				),
			),
			'spk_extra_doc'  => (int) $docs[0],
			'spk_keep_past'  => '1',
		)
	);
}

test(
	'the form\'s data-kinds come from the one type map',
	function () {
		as_role( 'ares-editor' );
		$html = uxevt_form( wfe5_exercise() );
		foreach ( array(
			'spk-summary'   => 'summary',
			'spk-tasks'     => 'tasks',
			'spk-extra-doc' => 'extra_doc',
			'spk-contact'   => 'contact',
		) as $input => $field ) {
			$kinds = implode( ',', \spokares_event_kind_fields()[ $field ] );
			assert_matches( '#<div class="spk-field" data-kinds="' . preg_quote( $kinds, '#' ) . '">\s*<label for="' . $input . '"#', $html, $field );
		}
		assert_contains( '<fieldset class="spk-card spk-button" data-kinds="exercise,training,on-air">', $html, 'Button' );
		assert_contains( '<fieldset class="spk-card spk-links" data-kinds="exercise,training,on-air">', $html, 'More links' );
		assert_matches( '#<label class="spk-choice" data-kinds="public-service"><input type="radio" name="spk_date_mode" value="as-requested"#', $html, 'As requested' );
	}
);

test(
	'Exercise changed to Public service: the exercise-only boxes are cleared and named in the notice',
	function () {
		$id = wfe5_exercise();
		as_role( 'ares-editor' );
		$res = uxevt_click( $id, array( 'spk_kind' => 'public-service' ), 'save' );
		foreach ( array( 'spk_summary', 'spk_tasks', 'spk_main_url', 'spk_main_label', 'spk_links', 'spk_extra_doc', 'spk_keep_past' ) as $key ) {
			assert_false( metadata_exists( 'post', $id, $key ), $key . ' is cleared' );
		}
		assert_count( 1, $res['notices'], 'one notice' );
		assert_same( 'Saved. It shows under Public-service events on the Exercises & events page. Cleared Short description, What members do, Button, More links and Document members need: this type of event doesn’t use them.', $res['notices'][0]['text'], 'the notice names them' );

		// Switched back: nothing old comes back.
		uxevt_click( $id, array( 'spk_kind' => 'exercise' ), 'save' );
		$html = uxevt_form( $id );
		assert_not_contains( 'Countywide drill', $html, 'the old Short description' );
		assert_not_contains( 'WF-E5 traffic', $html, 'the old tasks' );
	}
);

test(
	'a cleared box\'s "Publish it" tick goes too, so the same phone number is asked about again',
	function () {
		$id = uxevt_event(
			'WF-E5 phone',
			array(
				'spk_kind'  => 'training',
				'spk_start' => uxevt_day( 30 ),
			)
		);
		as_role( 'ares-editor' );
		$summary = 'Questions: county EOC 509-555-0142';
		uxevt_click(
			$id,
			array(
				'spk_summary' => $summary,
				'spk_confirm' => array( 'summary' => '1' ),
			),
			'save'
		);
		assert_same( $summary, (string) get_post_meta( $id, 'spk_summary', true ), 'confirmed and saved (control)' );
		assert_true( isset( get_post_meta( $id, 'spk_confirmed', true )['summary'] ), 'the tick is stored (control)' );

		$res = uxevt_click( $id, array( 'spk_kind' => 'public-service' ), 'save' );
		assert_contains( 'Cleared Short description: this type of event doesn’t use it.', $res['notices'][0]['text'], 'singular' );
		$confirmed = get_post_meta( $id, 'spk_confirmed', true );
		assert_false( is_array( $confirmed ) && isset( $confirmed['summary'] ), 'the Short description tick is dropped' );

		uxevt_click(
			$id,
			array(
				'spk_kind'    => 'training',
				'spk_summary' => $summary,
			),
			'save'
		);
		assert_same( '', (string) get_post_meta( $id, 'spk_summary', true ), 'the same number is asked about again, so not saved yet' );
		assert_contains( 'name="spk_confirm[summary]"', uxevt_form( $id ), 'the tick is offered again' );
	}
);

test(
	'Public service changed to Training: Volunteer through is cleared and named; a new Training says nothing about List under Past exercises',
	function () {
		$id = uxevt_event(
			'WF-E5 parade',
			array(
				'spk_kind'         => 'public-service',
				'spk_start'        => uxevt_day( 30 ),
				'spk_contact_call' => 'NZ2S',
			)
		);
		as_role( 'ares-editor' );
		$res = uxevt_click( $id, array( 'spk_kind' => 'training' ), 'save' );
		assert_same( '', (string) get_post_meta( $id, 'spk_contact_call', true ), 'cleared' );
		assert_contains( 'Cleared Volunteer through: this type of event doesn’t use it.', $res['notices'][0]['text'], 'named' );

		$new = get_default_post_to_edit( 'spk_event', true )->ID;
		$res = uxevt_click(
			$new,
			array(
				'post_title'    => 'WF-E5 new training',
				'spk_kind'      => 'training',
				'spk_date_mode' => 'date',
				'spk_start'     => uxevt_day( 30 ),
			),
			'publish'
		);
		assert_same( 'publish', get_post_status( $new ), 'published' );
		assert_not_contains( 'Cleared', $res['notices'][0]['text'], 'the default tick is cleared without a word' );
		assert_same( '', (string) get_post_meta( $new, 'spk_keep_past', true ), 'and not stored' );
	}
);

test(
	'a box the type doesn\'t use isn\'t checked: an over-long hidden Short description doesn\'t keep a Public service event off the site',
	function () {
		as_role( 'ares-editor' );
		$id  = get_default_post_to_edit( 'spk_event', true )->ID;
		$res = uxevt_click(
			$id,
			array(
				'post_title'    => 'WF-E5 run',
				'spk_kind'      => 'public-service',
				'spk_date_mode' => 'date',
				'spk_start'     => uxevt_day( 30 ),
				'spk_summary'   => str_repeat( 'x', 120 ),
			),
			'publish'
		);
		assert_same( 'publish', get_post_status( $id ), 'published: ' . export( $res['notices'] ) );
		assert_same( '', (string) get_post_meta( $id, 'spk_summary', true ), 'and the hidden text is cleared' );
	}
);
