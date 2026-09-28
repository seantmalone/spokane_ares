<?php
/**
 * Regression tests for a Fixer-round bug that shipped without a test
 * (build-notes/plugin.md "Fixer round" › Pages; PLAN §4.3, §6.7): a
 * non-administrator could change a page's template, for example give About
 * the members template (which holds a whole members page) through the block
 * editor's Template panel, REST or Quick Edit. The fix
 * (governance.php) has two parts, both tested here:
 *  - theme_page_templates drops the three members templates for non-admins,
 *    so REST refuses them with 400;
 *  - the _wp_page_template meta is guarded on update, add and delete for
 *    non-admins, whatever path writes it.
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

/**
 * A page's stored template, read fresh ('' when none).
 *
 * @param int $id Page ID.
 */
function prior_page_template_of( int $id ): string {
	wp_cache_delete( $id, 'post_meta' );
	return (string) get_post_meta( $id, '_wp_page_template', true );
}

test(
	'the members templates are offered to an administrator but not to editors',
	function () {
		$about = get_post( page_id( 'about' ) );
		as_role( 'admin' );
		$admin = wp_get_theme()->get_page_templates( $about, 'page' );
		foreach ( array( 'page-members', 'page-documents', 'page-exercises' ) as $slug ) {
			assert_true( isset( $admin[ $slug ] ), 'admin sees ' . $slug . ' (otherwise the editor check below proves nothing)' );
		}
		foreach ( array( 'ares-editor', 'ares-net', 'core-editor' ) as $role ) {
			as_role( $role );
			$list = wp_get_theme()->get_page_templates( $about, 'page' );
			foreach ( array( 'page-members', 'page-documents', 'page-exercises' ) as $slug ) {
				assert_false( isset( $list[ $slug ] ), $role . ' is offered ' . $slug );
			}
		}
	}
);

test(
	'REST: an editor cannot give a page a members template (400, template unchanged)',
	function () {
		$id     = page_id( 'about' );
		$before = prior_page_template_of( $id );
		foreach ( array( 'ares-editor', 'core-editor' ) as $role ) {
			as_role( $role );
			$res = rest( 'POST', '/wp/v2/pages/' . $id, array( 'template' => 'page-members' ) );
			expect_wp_error( $res, 'rest_invalid_param', 400, $role );
			assert_same( $before, prior_page_template_of( $id ), $role . ': stored template' );
		}
	}
);

test(
	'an editor cannot change, add or delete the _wp_page_template meta of a page',
	function () {
		$id = page_id( 'about' );
		as_role( 'admin' );
		update_post_meta( $id, '_wp_page_template', 'page-exercises' );
		assert_same( 'page-exercises', prior_page_template_of( $id ), 'the administrator set it' );
		foreach ( array( 'ares-editor', 'core-editor' ) as $role ) {
			as_role( $role );
			assert_false( update_post_meta( $id, '_wp_page_template', 'default' ), $role . ': update to default refused' );
			assert_false( update_post_meta( $id, '_wp_page_template', 'page-members' ), $role . ': update to another template refused' );
			assert_false( delete_post_meta( $id, '_wp_page_template' ), $role . ': delete refused' );
			assert_same( 'page-exercises', prior_page_template_of( $id ), $role . ': stored template' );
		}

		// A page with no template: adding one is refused too.
		$how = page_id( 'how-it-works' );
		assert_same( '', prior_page_template_of( $how ), 'How it works has no template' );
		as_role( 'ares-editor' );
		assert_false( add_post_meta( $how, '_wp_page_template', 'page-members', true ), 'add refused' );
		assert_same( '', prior_page_template_of( $how ), 'How it works still has no template' );
	}
);

test(
	'an editor saving a page through the classic form or REST keeps its stored template',
	function () {
		$id = page_id( 'about' );
		as_role( 'admin' );
		update_post_meta( $id, '_wp_page_template', 'page-exercises' );
		foreach ( array( 'ares-editor', 'core-editor' ) as $role ) {
			as_role( $role );
			// The classic form (and Quick Edit) send page_template with every save.
			$saved = wp_update_post(
				array(
					'ID'            => $id,
					'page_template' => 'default',
				),
				true
			);
			expect_not_wp_error( $saved, $role . ': classic save goes through' );
			assert_same( 'page-exercises', prior_page_template_of( $id ), $role . ': classic save' );
			// REST with the default ("") template: allowed by core, kept by the guard.
			expect_not_wp_error( rest( 'POST', '/wp/v2/pages/' . $id, array( 'template' => '' ) ), $role . ': REST save goes through' );
			assert_same( 'page-exercises', prior_page_template_of( $id ), $role . ': REST save' );
		}
	}
);

test(
	'an editor saving a page with its template unchanged is not refused',
	function () {
		$id = page_id( 'how-it-works' );
		as_role( 'ares-editor' );
		expect_not_wp_error( rest( 'POST', '/wp/v2/pages/' . $id, array( 'template' => '' ) ) );
		$saved = wp_update_post(
			array(
				'ID'            => $id,
				'page_template' => 'default',
			),
			true
		);
		expect_not_wp_error( $saved );
		assert_contains( prior_page_template_of( $id ), array( '', 'default' ), 'still the default template' );
	}
);

test(
	'an administrator can still change a page template',
	function () {
		$id = page_id( 'about' );
		as_role( 'admin' );
		expect_not_wp_error( rest( 'POST', '/wp/v2/pages/' . $id, array( 'template' => 'page-members' ) ) );
		assert_same( 'page-members', prior_page_template_of( $id ), 'REST' );
		assert_true( delete_post_meta( $id, '_wp_page_template' ), 'delete' );
		assert_same( '', prior_page_template_of( $id ), 'deleted' );
	}
);
