<?php
/**
 * DEV ONLY sample tests: the QA accounts exist with the right roles and the
 * Net details grant sits only where qa-users.json puts it.
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

test(
	'every QA account exists with its role',
	function () {
		foreach ( accounts() as $key => $account ) {
			if ( 'anonymous' === $key ) {
				continue;
			}
			$user = get_user_by( 'id', user_id( $key ) );
			assert_true( $user instanceof \WP_User, $key );
			assert_contains( $account['role'], $user->roles, $key . ' role' );
		}
	}
);

test(
	'only the granted ARES Editor and the administrator may edit Net details',
	function () {
		$expected = array(
			'anonymous'   => false,
			'subscriber'  => false,
			'contributor' => false,
			'author'      => false,
			'core-editor' => false,
			'ares-editor' => false,
			'ares-net'    => true,
			'admin'       => true,
		);
		foreach ( $expected as $key => $can ) {
			as_role( $key );
			assert_same( $can, current_user_can( 'spokares_edit_net_details' ), $key );
		}
	}
);
