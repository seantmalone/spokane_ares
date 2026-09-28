<?php
/**
 * DEV ONLY sample tests for the framework itself: a save handler called
 * through post_form(), an internal REST request, and the clean-up between
 * tests. Copy these shapes for real regression tests.
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

test(
	'a subscriber cannot save the net rota (403 before the nonce)',
	function () {
		as_role( 'subscriber' );
		$r = post_form( 'spokares_save_rota', array( 'weeks' => '13' ) );
		assert_same( 403, $r['status'] );
		assert_contains( 'not allowed', (string) $r['die'] );
		assert_same( null, $r['redirect'] );
	}
);

test(
	'the ARES Editor gets the Home page over REST, a visitor does not',
	function () {
		$home = page_id( 'home' );
		as_role( 'ares-editor' );
		$ok = rest( 'GET', '/wp/v2/pages/' . $home, array( 'context' => 'edit' ) );
		assert_same( 200, $ok->get_status() );
		assert_same( $home, $ok->get_data()['id'] );
		as_anonymous();
		expect_wp_error( rest( 'GET', '/wp/v2/pages/' . $home, array( 'context' => 'edit' ) ), '', 401 );
	}
);

test(
	'data a test creates is there during the test',
	function () {
		as_role( 'admin' );
		$id = create_post(
			array(
				'post_type'  => 'spk_event',
				'post_title' => 'QA framework clean-up probe',
			)
		);
		update_option( 'spk_qa_probe', 'set' );
		assert_same( 'QA framework clean-up probe', get_the_title( $id ) );
		assert_same( 'set', get_option( 'spk_qa_probe' ) );
	}
);

test(
	'and is gone in the next test',
	function () {
		$left = get_posts(
			array(
				'post_type'   => 'spk_event',
				'post_status' => 'any',
				'title'       => 'QA framework clean-up probe',
				'fields'      => 'ids',
			)
		);
		assert_same( array(), $left );
		assert_false( get_option( 'spk_qa_probe' ) );
	}
);
