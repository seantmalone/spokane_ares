<?php
/**
 * Regression tests for a Fixer-round bug that shipped without a test
 * (build-notes/plugin.md "Fixer round" › Events; review shot
 * shots/review-editor/19-event-save-box-hidden.png): an editor who unticked
 * "Save" in Screen Options (or whose Save box was folded) had no Save,
 * Publish or Update button left on the event or document form. The fix
 * (admin-trim.php): the Save box is never hidden (hidden_meta_boxes) or
 * folded (postbox_classes), and Screen Options is off on the event and
 * document forms for non-admins.
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

test(
	'the Save box stays shown on the event and document forms even when the editor\'s saved preferences hide it',
	function () {
		foreach ( array( 'ares-editor', 'core-editor', 'admin' ) as $role ) {
			$user = as_role( $role );
			foreach ( array( 'spk_event', 'spk_document' ) as $type ) {
				// What core's closed-postboxes / hidden-columns Ajax handlers store.
				update_user_meta( $user->ID, 'metaboxhidden_' . $type, array( 'spokares_savebox', 'submitdiv', 'spokares_checking' ) );
				$hidden = get_hidden_meta_boxes( \WP_Screen::get( $type ) );
				assert_not_contains( 'spokares_savebox', $hidden, $role . ' ' . $type . ': Save box hidden' );
				assert_contains( 'spokares_checking', $hidden, $role . ' ' . $type . ': other boxes may still be hidden' );
			}
		}
	}
);

test(
	'the Save box is never drawn folded on the event and document forms',
	function () {
		foreach ( array( 'ares-editor', 'core-editor', 'admin' ) as $role ) {
			$user = as_role( $role );
			foreach ( array( 'spk_event', 'spk_document' ) as $type ) {
				update_user_meta( $user->ID, 'closedpostboxes_' . $type, array( 'spokares_savebox', 'spokares_checking' ) );
				$classes = explode( ' ', postbox_classes( 'spokares_savebox', $type ) );
				assert_not_contains( 'closed', $classes, $role . ' ' . $type . ': Save box folded' );
				$other = explode( ' ', postbox_classes( 'spokares_checking', $type ) );
				assert_contains( 'closed', $other, $role . ' ' . $type . ': the preference itself is read (control)' );
			}
		}
	}
);

test(
	'Screen Options is off on the event and document forms for editors, on for administrators',
	function () {
		foreach ( array( 'spk_event', 'spk_document' ) as $type ) {
			$screen = \WP_Screen::get( $type );
			assert_same( 'post', $screen->base, $type . ' form screen' );
			foreach ( array( 'ares-editor', 'ares-net', 'core-editor' ) as $role ) {
				as_role( $role );
				assert_false( apply_filters( 'screen_options_show_screen', true, $screen ), $role . ' ' . $type ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- core's filter.
			}
			as_role( 'admin' );
			assert_true( apply_filters( 'screen_options_show_screen', true, $screen ), 'admin ' . $type ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- core's filter.
		}
	}
);
