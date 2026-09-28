<?php
/**
 * Tests for the Page Text changes of the volunteer-editor review (UX spec
 * §3.8, group 5): the page type's labels for editors, the Page review box on
 * About only, what the block editor is told about pages, and the server's
 * refusals of a page save, with their words ("the webmaster", never "an
 * administrator"). The two new refusals close holes the walkers found (PG-1,
 * PG-2): a button whose link was removed, and a Home photo taken away with
 * the Replace menu's Reset, both saved and broke the page.
 *
 * The screens themselves (the Pages list, the admin bar, the editor chrome)
 * are in dev/tests/e2e/ux-page-text-editor.test.mjs.
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

/**
 * Some page content with the first core/button's link (href) taken out, as
 * the toolbar's Unlink leaves it.
 *
 * @param string $content Page content.
 */
function uxpt_unlink_first_button( string $content ): string {
	$out = preg_replace( '#(<a class="wp-block-button__link[^"]*") href="[^"]*"#', '$1', $content, 1, $count );
	if ( 1 !== $count ) {
		fail( 'no linked button in the page' );
	}
	return (string) $out;
}

/**
 * Home's content with the Cover's photo taken away, as the Replace menu's
 * Reset leaves it (no url or id in the comment, no image in the HTML).
 *
 * @param string $content Home's content.
 */
function uxpt_reset_hero( string $content ): string {
	$blocks = parse_blocks( $content );
	$found  = false;
	foreach ( $blocks as $i => $b ) {
		if ( 'core/cover' !== ( $b['blockName'] ?? '' ) ) {
			continue;
		}
		unset( $blocks[ $i ]['attrs']['url'], $blocks[ $i ]['attrs']['id'], $blocks[ $i ]['attrs']['focalPoint'], $blocks[ $i ]['attrs']['backgroundType'] );
		$first                           = (string) $b['innerContent'][0];
		$blocks[ $i ]['innerContent'][0] = (string) preg_replace( '#<img class="wp-block-cover__image-background[^>]*/?>#', '', $first );
		$found                           = true;
		break;
	}
	if ( ! $found ) {
		fail( 'no Cover on Home' );
	}
	return serialize_blocks( $blocks );
}

/**
 * The page's stored content, fresh from the database.
 *
 * @param int $id Page ID.
 */
function uxpt_content( int $id ): string {
	clean_post_cache( $id );
	return (string) get_post( $id )->post_content;
}

test(
	'the refusals speak of the webmaster and say what to click (§3.8)',
	function () {
		$want = array(
			'layout'       => array( 'spokares_layout_locked', 'Not saved: only the webmaster can add, move or restyle parts of a page. Click the Undo arrow (top left) until that part is back, then Save.' ),
			'empty-button' => array( 'spokares_empty_button', 'Not saved: a button needs its words. Type them back, or click the Undo arrow (top left), then Save.' ),
			'button-link'  => array( 'spokares_button_link', 'Not saved: a button needs a link. Click the button, then the pencil in the box under it, paste the address and press Enter.' ),
			'hero-photo'   => array( 'spokares_hero_photo', 'Not saved: the Home photo can be replaced but not removed. Click the Undo arrow (top left), then Replace › Choose a photo.' ),
		);
		foreach ( $want as $why => $expected ) {
			$err = \spokares_layout_error( $why );
			assert_same( $expected[0], $err->get_error_code(), $why . ': code' );
			assert_same( $expected[1], $err->get_error_message(), $why . ': words' );
			assert_same( 403, $err->get_error_data()['status'] ?? 0, $why . ': status' );
			assert_not_contains( 'administrator', $err->get_error_message(), $why );
		}
		assert_same( array_column( $want, 1 ), array_values( \spokares_layout_refusals() ), 'the editor guard gets the same sentences' );
	}
);

test(
	'REST: an ARES Editor\'s save of a button with its link removed is refused; the page keeps its link',
	function () {
		foreach ( array( 'ares-editor', 'ares-net', 'core-editor' ) as $role ) {
			$home   = page_id( 'home' );
			$before = uxpt_content( $home );
			as_role( $role );
			$res = rest( 'POST', '/wp/v2/pages/' . $home, array( 'content' => uxpt_unlink_first_button( $before ) ) );
			$err = expect_wp_error( $res, 'spokares_button_link', 403, $role );
			assert_contains( 'a button needs a link', $err->get_error_message(), $role );
			assert_same( $before, uxpt_content( $home ), $role . ': Home as stored' );
		}
	}
);

test(
	'classic save: a button with its link removed keeps the stored page',
	function () {
		$about  = page_id( 'about' );
		$before = uxpt_content( $about );
		as_role( 'ares-editor' );
		$saved = wp_update_post(
			wp_slash(
				array(
					'ID'           => $about,
					'post_content' => uxpt_unlink_first_button( $before ),
				)
			),
			true
		);
		expect_not_wp_error( $saved, 'the save itself goes through' );
		assert_same( $before, uxpt_content( $about ), 'About as stored (the button keeps its link)' );
	}
);

test(
	'REST: an ARES Editor\'s save of Home with its photo removed (Reset) is refused; Home keeps its photo',
	function () {
		$home   = page_id( 'home' );
		$before = uxpt_content( $home );
		assert_false( \spokares_has_photoless_cover( $before ), 'Home has its photo (control)' );
		foreach ( array( 'ares-editor', 'ares-net' ) as $role ) {
			as_role( $role );
			$res = rest( 'POST', '/wp/v2/pages/' . $home, array( 'content' => uxpt_reset_hero( $before ) ) );
			$err = expect_wp_error( $res, 'spokares_hero_photo', 403, $role );
			assert_contains( 'can be replaced but not removed', $err->get_error_message(), $role );
			assert_same( $before, uxpt_content( $home ), $role . ': Home as stored' );
		}
	}
);

test(
	'a new Home photo, and a button given a new link, still save for an ARES Editor',
	function () {
		$home   = page_id( 'home' );
		$before = uxpt_content( $home );
		$hero   = (int) ( parse_blocks( $before )[0]['attrs']['id'] ?? 0 );
		$new    = create_post(
			array(
				'post_type'      => 'attachment',
				'post_mime_type' => 'image/jpeg',
				'post_status'    => 'inherit',
				'post_title'     => 'uxpt new hero',
			)
		);
		$url    = 'http://127.0.0.1/wp-content/uploads/uxpt-new-hero.jpg';
		$swap   = (string) preg_replace( '#"url":"[^"]*","id":' . $hero . '\b#', '"url":"' . $url . '","id":' . $new, $before, 1 );
		$swap   = (string) preg_replace( '#wp-image-' . $hero . '\b([^>]*?)src="[^"]*"#', 'wp-image-' . $new . '$1src="' . $url . '"', $swap, 1 );
		assert_not_same( $before, $swap, 'the photo was swapped in the content (control)' );
		$swap = (string) preg_replace( '#(<a class="wp-block-button__link[^"]*") href="[^"]*"#', '$1 href="/about/#joining"', $swap, 1 );
		as_role( 'ares-editor' );
		$res = rest( 'POST', '/wp/v2/pages/' . $home, array( 'content' => $swap ) );
		expect_not_wp_error( $res, 'the save' );
		$stored = uxpt_content( $home );
		assert_contains( $url, $stored, 'the new photo is stored' );
		assert_contains( 'href="/about/#joining"', $stored, 'the new link is stored' );
	}
);

test(
	'a button or a Cover that was already without a link or photo doesn\'t block other changes',
	function () {
		$old = '<!-- wp:buttons --><div class="wp-block-buttons"><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button">Soon</a></div><!-- /wp:button --></div><!-- /wp:buttons -->';
		$new = str_replace( 'Soon', 'Coming soon', $old );
		assert_true( \spokares_has_linkless_button( $old ), 'the stored button has no link (control)' );
		assert_same( '', \spokares_page_content_problem( $old, $new ), 'changing its words' );
		$cover = '<!-- wp:cover {"dimRatio":0} --><div class="wp-block-cover"><span aria-hidden="true" class="wp-block-cover__background has-background-dim-0 has-background-dim"></span><div class="wp-block-cover__inner-container"><!-- wp:paragraph --><p>Hi</p><!-- /wp:paragraph --></div></div><!-- /wp:cover -->';
		assert_true( \spokares_has_photoless_cover( $cover ), 'a Cover with no photo (control)' );
		assert_same( '', \spokares_page_content_problem( $cover, str_replace( '<p>Hi</p>', '<p>Hello</p>', $cover ) ), 'changing the words in it' );
	}
);

test(
	'labels: for editors the page type is Page Text, its edit link Edit Page Text, and a save says it is on the site; administrators keep WordPress\'s',
	function () {
		foreach ( array( 'ares-editor', 'ares-net', 'core-editor' ) as $role ) {
			as_role( $role );
			$labels = get_post_type_labels( get_post_type_object( 'page' ) );
			assert_same( 'Page Text', $labels->name, $role . ': name' );
			assert_same( 'Page Text', $labels->menu_name, $role . ': menu_name' );
			assert_same( 'Page Text', $labels->all_items, $role . ': all_items' );
			assert_same( 'Edit Page Text', $labels->edit_item, $role . ': edit_item (the admin bar)' );
			assert_same( 'Saved. It’s on the site now.', $labels->item_updated, $role . ': item_updated (the editor\'s notice)' );
		}
		as_role( 'admin' );
		$labels = get_post_type_labels( get_post_type_object( 'page' ) );
		assert_same( 'Pages', $labels->name, 'admin: name' );
		assert_same( 'Edit Page', $labels->edit_item, 'admin: edit_item' );
	}
);

test(
	'the Page review box is on About only (the page that prints its review line)',
	function () {
		assert_true( \spokares_page_has_review_box( get_post( page_id( 'about' ) ) ), 'About' );
		foreach ( array( 'home', 'how-it-works' ) as $path ) {
			assert_false( \spokares_page_has_review_box( get_post( page_id( $path ) ) ), $path );
		}
		as_role( 'ares-editor' );
		global $wp_meta_boxes;
		foreach ( array(
			'about'        => true,
			'home'         => false,
			'how-it-works' => false,
		) as $path => $has ) {
			unset( $wp_meta_boxes['page']['side']['default']['spokares_page_review'] );
			\spokares_page_review_box( get_post( page_id( $path ) ) );
			assert_same( $has, ! empty( $wp_meta_boxes['page']['side']['default']['spokares_page_review'] ), $path . ': the box is added' );
		}
		unset( $wp_meta_boxes['page']['side']['default']['spokares_page_review'] );
		ob_start();
		\spokares_render_page_review( get_post( page_id( 'about' ) ) );
		$html = (string) ob_get_clean();
		assert_matches( '#<input type="text" id="spk-owner"#', $html, 'the Page owner field' );
		assert_not_contains( 'placeholder=', $html, 'a placeholder in the box' );
		assert_contains( 'Mark reviewed today when I save', $html, 'the tick' );
	}
);

test(
	'REST: the block editor is told editors\' pages have no title, page attributes or notes (no Rename, Order or "Add note"); administrators\' pages have them',
	function () {
		foreach ( array( 'ares-editor', 'ares-net' ) as $role ) {
			as_role( $role );
			$supports = (array) ( rest( 'GET', '/wp/v2/types/page', array( 'context' => 'edit' ) )->get_data()['supports'] ?? array() );
			assert_false( isset( $supports['title'] ), $role . ': title' );
			assert_false( isset( $supports['page-attributes'] ), $role . ': page-attributes' );
			assert_false( ! empty( $supports['editor'][0]['notes'] ), $role . ': editor notes' );
			assert_true( ! empty( $supports['editor'] ), $role . ': the editor itself' );
			assert_true( post_type_supports( 'page', 'title' ), $role . ': the page type itself keeps its title' );
		}
		as_role( 'admin' );
		$supports = (array) ( rest( 'GET', '/wp/v2/types/page', array( 'context' => 'edit' ) )->get_data()['supports'] ?? array() );
		assert_true( ! empty( $supports['title'] ), 'admin: title' );
		assert_true( ! empty( $supports['page-attributes'] ), 'admin: page-attributes' );
	}
);

test(
	'the editor guard gets the after-save sentences, the photo sentence and the refusals, and knows where the review box is',
	function () {
		global $current_screen, $typenow, $taxnow, $post;
		$saved = array( $current_screen, $typenow, $taxnow, $post );
		as_role( 'ares-net' );
		try {
			foreach ( array(
				'about' => true,
				'home'  => false,
			) as $path => $box ) {
				set_current_screen( 'page' );
				// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- the editor screen's global post, as post.php sets it.
				$post = get_post( page_id( $path ) );
				wp_scripts()->add_data( 'spokares-editor-guard', 'before', array() );
				\spokares_enqueue_editor_guard();
				$before = wp_scripts()->get_data( 'spokares-editor-guard', 'before' );
				$json   = (string) preg_replace( '/^window\.spokaresGuard = |;$/', '', is_array( $before ) ? (string) end( $before ) : '' );
				$cfg    = json_decode( $json, true );
				assert_true( is_array( $cfg ), $path . ': the config parses' );
				assert_same( $box, $cfg['reviewBox'], $path . ': reviewBox' );
				assert_same( 'On the site now: this page has what looks like %1$s (%2$s). If it isn’t public, take it out and Save.', $cfg['message'], 'message' );
				assert_same( 'A link shows its web address instead of words: “%s”. Select the words people should click, then add the link.', $cfg['addressLink'], 'addressLink' );
				assert_same( 'Photos must be JPEG, PNG or WebP. On an iPad, pick the photo from Photo Library, which converts it.', $cfg['photoType'], 'photoType' );
				assert_same( 'Describe the photo in a few words', $cfg['altLabel'], 'altLabel' );
				assert_same( array_values( \spokares_layout_refusals() ), $cfg['refusals'], 'refusals' );
				assert_false( isset( $cfg['excerpt'] ), 'an excerpt check for editors' );
				wp_dequeue_script( 'spokares-editor-guard' );
			}
		} finally {
			wp_dequeue_script( 'spokares-editor-guard' );
			// phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited -- restoring the globals this test changed.
			list( $current_screen, $typenow, $taxnow, $post ) = $saved;
			// phpcs:enable WordPress.WP.GlobalVariablesOverride.Prohibited
		}
	}
);
