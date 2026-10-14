<?php
/**
 * Regression tests for workflow review P1: the never-publish list (a hospital
 * net, a channel number, SHARES channels, 800 MHz, a talkgroup) was refused
 * in every form but only warned about in page text, so the words went public.
 * Now a non-administrator's page save that adds them is refused with one
 * sentence naming what was found (REST), or keeps the stored page (any other
 * save). Phone numbers and personal e-mail addresses still only warn.
 * Administrators are not held to it, as with the layout lock (§4.3, §4.4).
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

/**
 * Some page content with words added to the end of its first paragraph.
 *
 * @param string $content Post content.
 * @param string $words   Words to add.
 */
function wfp1_add_words( string $content, string $words ): string {
	$at = strpos( $content, "</p>\n<!-- /wp:paragraph -->" );
	if ( false === $at ) {
		fail( 'the page has no paragraph to add words to' );
	}
	return substr( $content, 0, $at ) . ' ' . $words . substr( $content, $at );
}

/**
 * The page's stored content, fresh from the database.
 *
 * @param int $id Page ID.
 */
function wfp1_content( int $id ): string {
	clean_post_cache( $id );
	return (string) get_post( $id )->post_content;
}

test(
	'REST: an editor\'s page save that adds never-publish words is refused, naming what was found; the page is unchanged',
	function () {
		$cases = array(
			'Our Hospital Net meets on Tuesdays.' => 'Not saved: the page mentions a hospital net, which we never publish. Take out “Hospital Net”, then Save.',
			'Listen on channel 3.'                => 'Not saved: the page mentions a channel number, which we never publish. Take out “channel 3”, then Save.',
			'We use SHARES frequencies.'          => 'Not saved: the page mentions SHARES channels or frequencies, which we never publish. Take out “SHARES frequencies”, then Save.',
			'Ask on the county 800&nbsp;MHz talkgroup.' => 'Not saved: the page mentions 800 MHz and a talkgroup, which we never publish. Take out “800 MHz” and “talkgroup”, then Save.',
		);
		foreach ( array( 'ares-editor', 'ares-net', 'core-editor' ) as $role ) {
			foreach ( $cases as $words => $sentence ) {
				$about  = page_id( 'about' );
				$before = wfp1_content( $about );
				as_role( $role );
				$res = rest( 'POST', '/wp/v2/pages/' . $about, array( 'content' => wfp1_add_words( $before, $words ) ) );
				$err = expect_wp_error( $res, 'spokares_never_publish', 403, $role . ': ' . $words );
				assert_same( $sentence, $err->get_error_message(), $role . ': ' . $words );
				assert_same( $before, wfp1_content( $about ), $role . ': About as stored' );
			}
		}
	}
);

test(
	'REST: phone numbers and personal e-mail addresses still save (the editor warns after the save); role addresses are fine',
	function () {
		$home = page_id( 'home' );
		as_role( 'ares-editor' );
		foreach ( array( 'Call 509-555-0142.', 'Write to someone@example.com.', 'Ask the hospital@spokares.org desk.' ) as $words ) {
			$new = wfp1_add_words( wfp1_content( $home ), $words );
			expect_not_wp_error( rest( 'POST', '/wp/v2/pages/' . $home, array( 'content' => $new ) ), $words );
			assert_contains( $words, wfp1_content( $home ), $words . ': saved' );
		}
	}
);

test(
	'never-publish words already on the page (an administrator\'s) don\'t stop an editor saving other words; a second mention does',
	function () {
		$how = page_id( 'how-it-works' );
		as_role( 'admin' );
		$with = wfp1_add_words( wfp1_content( $how ), 'Hospital net details are never posted here.' );
		expect_not_wp_error( rest( 'POST', '/wp/v2/pages/' . $how, array( 'content' => $with ) ), 'an administrator may save them' );
		assert_contains( 'Hospital net details', wfp1_content( $how ), 'saved for the administrator' );

		as_role( 'ares-editor' );
		$res = rest( 'POST', '/wp/v2/pages/' . $how, array( 'content' => str_replace( 'never posted here', 'never posted on this site', wfp1_content( $how ) ) ) );
		expect_not_wp_error( $res, 'an edit that keeps the existing mention' );
		assert_contains( 'never posted on this site', wfp1_content( $how ), 'the edit saved' );

		$res = rest( 'POST', '/wp/v2/pages/' . $how, array( 'content' => wfp1_add_words( wfp1_content( $how ), 'Join the hospital net.' ) ) );
		expect_wp_error( $res, 'spokares_never_publish', 403, 'a second mention is new' );
	}
);

test(
	'classic save and autosave: new never-publish words keep the stored page',
	function () {
		$about  = page_id( 'about' );
		$before = wfp1_content( $about );
		as_role( 'ares-editor' );
		$saved = wp_update_post(
			wp_slash(
				array(
					'ID'           => $about,
					'post_content' => wfp1_add_words( $before, 'Use talk group 7.' ),
				)
			),
			true
		);
		expect_not_wp_error( $saved, 'the save itself goes through' );
		assert_same( $before, wfp1_content( $about ), 'About as stored' );

		$res = rest( 'POST', '/wp/v2/pages/' . $about . '/autosaves', array( 'content' => wfp1_add_words( $before, 'Use talk group 7.' ) ) );
		expect_not_wp_error( $res, 'an autosave is never refused' );
		$autosave = wp_get_post_autosave( $about, get_current_user_id() );
		assert_true( (bool) $autosave, 'an autosave was stored' );
		assert_not_contains( 'talk group', (string) $autosave->post_content, 'the autosave keeps the stored words' );
	}
);

test(
	'the editor guard warns after a save about phone numbers and e-mail only, and knows how refusals start',
	function () {
		global $current_screen, $typenow, $taxnow, $post;
		$saved = array( $current_screen, $typenow, $taxnow, $post );
		as_role( 'ares-editor' );
		try {
			set_current_screen( 'page' );
			// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- the editor screen's global post, as post.php sets it.
			$post = get_post( page_id( 'home' ) );
			wp_scripts()->add_data( 'spokares-editor-guard', 'before', array() );
			\spokares_enqueue_editor_guard();
			$before = wp_scripts()->get_data( 'spokares-editor-guard', 'before' );
			$cfg    = json_decode( (string) preg_replace( '/^window\.spokaresGuard = |;$/', '', is_array( $before ) ? (string) end( $before ) : '' ), true );
			assert_true( is_array( $cfg ), 'the config parses' );
			assert_same( array( 'a phone number', 'an e-mail address' ), array_column( $cfg['patterns'], 'what' ), 'patterns' );
			assert_same( 'Not saved:', $cfg['refusalHead'], 'refusalHead' );
			$js = (string) file_get_contents( SPOKARES_CORE_DIR . 'assets/js/editor-guard.js' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- a local plugin file.
			assert_contains( 'cfg.refusalHead', $js, 'the script uses it' );
		} finally {
			wp_dequeue_script( 'spokares-editor-guard' );
			// phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited -- restoring the globals this test changed.
			list( $current_screen, $typenow, $taxnow, $post ) = $saved;
			// phpcs:enable WordPress.WP.GlobalVariablesOverride.Prohibited
		}
	}
);
