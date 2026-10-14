<?php
/**
 * Regression tests for workflow review P4: documents not reviewed in 12
 * months showed on the Dashboard, but About's "Last reviewed" (the Page
 * review box, _spk_reviewed) had no reminder. Now Needs attention lists
 * About when it was never marked reviewed or was last reviewed over 12
 * months ago, with a link to the About editor.
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

/**
 * The Dashboard widget's HTML for the current user.
 */
function wfp4_dashboard(): string {
	ob_start();
	\spokares_dashboard_widget();
	return (string) ob_get_clean();
}

test(
	'Needs attention names About when it was never reviewed, or over 12 months ago, with a link to its editor',
	function () {
		$about = page_id( 'about' );
		$title = 'About ARES &amp; ACS';
		$link  = 'post\.php\?post=' . $about . '&(amp;|\#038;)?action=edit">Review the page</a>';
		foreach ( array( 'ares-editor', 'admin' ) as $role ) {
			as_role( $role );
			delete_post_meta( $about, '_spk_reviewed' );
			assert_matches( '#<li>' . $title . ' hasn’t been marked reviewed yet\. <a href="[^"]*' . $link . '</li>#', wfp4_dashboard(), $role . ': never reviewed' );

			update_post_meta( $about, '_spk_reviewed', \spokares_add_days( \spokares_today(), -366 ) );
			$old = \spokares_fmt_date( \spokares_add_days( \spokares_today(), -366 ), 'mdy' );
			assert_matches( '#<li>' . $title . ' was last reviewed ' . preg_quote( $old, '#' ) . ', over 12 months ago\. <a href="[^"]*' . $link . '</li>#', wfp4_dashboard(), $role . ': over 12 months' );

			update_post_meta( $about, '_spk_reviewed', \spokares_add_days( \spokares_today(), -365 ) );
			$html = wfp4_dashboard();
			assert_not_contains( 'over 12 months ago', $html, $role . ': exactly 12 months is still fine' );
			assert_not_contains( 'marked reviewed yet', $html, $role );
		}
	}
);

test(
	'Home and How it works have no review line (they print no "Last reviewed")',
	function () {
		as_role( 'ares-editor' );
		update_post_meta( page_id( 'about' ), '_spk_reviewed', \spokares_today() );
		$html = wfp4_dashboard();
		assert_not_contains( 'reviewed yet', $html, 'no line for any page' );
		assert_not_contains( 'over 12 months ago', $html, 'no line for any page' );
	}
);
