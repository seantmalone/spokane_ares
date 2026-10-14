<?php
/**
 * Regression tests for workflow review G1: the Net details grant is "only
 * for someone the Emergency Coordinator has named", but the site kept no
 * note of who gave it or when. Now the grant's save records the
 * administrator and the time (spk_net_granted_by, spk_net_granted_at), the
 * Users list's Net Settings column reads "Yes, by <name> on <date>", the
 * profile says "Given by …", and taking the grant away, or a role change,
 * clears the record.
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

/**
 * Save the grant box on an account's profile as the current user.
 *
 * @param int  $id   Account.
 * @param bool $tick The box is ticked.
 */
function wfg1_save( int $id, bool $tick ): void {
	$fields = array( 'spokares_grant_nonce' => wp_create_nonce( 'spokares_grant_' . $id ) );
	if ( $tick ) {
		$fields['spokares_net_details'] = '1';
	}
	call_request( 'POST', array(), $fields, static fn() => \spokares_profile_grant_save( $id ) );
	clean_user_cache( $id );
}

test(
	'giving the grant records who and when; the Users column and the profile say so',
	function () {
		$admin = as_role( 'admin' );
		$id    = user_id( 'ares-editor' );
		$start = time();
		wfg1_save( $id, true );
		assert_true( user_can( $id, 'spokares_edit_net_details' ), 'granted' );
		assert_same( $admin->ID, (int) get_user_meta( $id, 'spk_net_granted_by', true ), 'who' );
		$at = (int) get_user_meta( $id, 'spk_net_granted_at', true );
		assert_true( $at >= $start && $at <= time(), 'when' );

		$date = wp_date( (string) get_option( 'date_format' ), $at );
		assert_same( esc_html( 'Yes, by ' . $admin->display_name . ' on ' . $date ), \spokares_users_grant_cell( '', 'spokares_net_details', $id ), 'the Users column' );

		ob_start();
		\spokares_profile_grant_field( get_userdata( $id ) );
		$html = (string) ob_get_clean();
		assert_contains( esc_html( 'Given by ' . $admin->display_name . ' on ' . $date . '.' ), $html, 'the profile' );

		// Saving the profile again with the box still ticked keeps the first record.
		update_user_meta( $id, 'spk_net_granted_at', 1700000000 );
		wfg1_save( $id, true );
		assert_same( 1700000000, (int) get_user_meta( $id, 'spk_net_granted_at', true ), 're-saving doesn\'t restamp it' );
	}
);

test(
	'taking the grant away, or a role change, clears the record',
	function () {
		as_role( 'admin' );
		$id = user_id( 'ares-editor' );
		wfg1_save( $id, true );
		wfg1_save( $id, false );
		assert_false( user_can( $id, 'spokares_edit_net_details' ), 'taken away' );
		assert_same( '', get_user_meta( $id, 'spk_net_granted_by', true ), 'who: cleared' );
		assert_same( '', get_user_meta( $id, 'spk_net_granted_at', true ), 'when: cleared' );

		wfg1_save( $id, true );
		$user = new \WP_User( $id );
		$user->set_role( 'subscriber' );
		clean_user_cache( $id );
		assert_same( '', get_user_meta( $id, 'spk_net_granted_at', true ), 'demotion clears it' );
		$user = new \WP_User( $id ); // Fresh, as wp-admin loads it.
		$user->set_role( 'ares_editor' );
		clean_user_cache( $id );
		assert_false( user_can( $id, 'spokares_edit_net_details' ), 'an ARES Editor again has no grant' );
	}
);

test(
	'a grant with no record (given before this) reads plain "Yes"; a deleted administrator leaves the date',
	function () {
		as_role( 'admin' );
		$id = user_id( 'ares-net' );
		delete_user_meta( $id, 'spk_net_granted_by' );
		delete_user_meta( $id, 'spk_net_granted_at' );
		assert_same( 'Yes', \spokares_users_grant_cell( '', 'spokares_net_details', $id ), 'no record' );
		update_user_meta( $id, 'spk_net_granted_by', 999999 );
		update_user_meta( $id, 'spk_net_granted_at', 1791849600 );
		assert_same( esc_html( 'Yes, on ' . wp_date( (string) get_option( 'date_format' ), 1791849600 ) ), \spokares_users_grant_cell( '', 'spokares_net_details', $id ), 'the granter is gone' );
	}
);
