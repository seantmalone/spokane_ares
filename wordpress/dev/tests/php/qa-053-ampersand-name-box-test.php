<?php
/**
 * Regression tests for QA-053 (PLAN §3.4 events and §3.5 documents, §5.2
 * DISALLOW_UNFILTERED_HTML): after an event or a document whose name has an
 * "&" is saved, the name box on its edit screen shows "&amp;". Nobody holds
 * unfiltered_html on this site, so core runs every post title through
 * wp_filter_kses() (title_save_pre), which stores "Q&A" as "Q&amp;A";
 * edit-form-advanced.php then prints the name box as
 * esc_attr( $post->post_title ) for get_post( $id, OBJECT, 'edit' ), whose
 * format_to_edit() escapes the stored entity again. So the box reads
 * "QA Q&amp;A "night" &amp; Winlink" (the site itself is right). It does not
 * compound on the next save, but to a volunteer it looks like the name was
 * corrupted.
 *
 * The saves run as post.php's "editpost" does them (edit_post() with the
 * event or document form's fields), and the name box is read the way a
 * browser reads core's <input id="title">. The tests hold for any fix
 * (store these titles as plain text, or decode them for the edit field) as
 * long as the box shows the name as typed, the site still shows it once
 * (not "&amp;"), and markup in a name is still not stored.
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

/**
 * A day this many days after the site's "today".
 *
 * @param int $days Days.
 */
function qa053_day( int $days ): string {
	return gmdate( 'Y-m-d', (int) strtotime( spokares_today() . ' +' . $days . ' days' ) );
}

/**
 * Save a post as post.php's "editpost" does: edit_post() with the form's
 * fields (the post's own hidden fields, the typed name, Publish/Update) plus
 * $fields (the plugin form's fields and nonce).
 *
 * @param int    $id     Post.
 * @param string $title  What is in the name box when the button is pressed.
 * @param array  $fields The plugin form's fields.
 */
function qa053_editpost( int $id, string $title, array $fields ): void {
	$post = get_post( $id );
	$form = array_merge(
		array(
			'action'               => 'editpost',
			'originalaction'       => 'editpost',
			'post_ID'              => (string) $id,
			'post_type'            => $post->post_type,
			'post_author'          => (string) $post->post_author,
			'original_post_status' => $post->post_status,
			'post_title'           => $title,
			'_wpnonce'             => wp_create_nonce( 'update-post_' . $id ),
		),
		'publish' === $post->post_status ? array( 'save' => 'Update' ) : array( 'publish' => 'Publish' ),
		$fields
	);
	$r    = call_request(
		'POST',
		array(),
		$form,
		static function () {
			return edit_post();
		}
	);
	assert_same( null, $r['die'], 'the save did not wp_die()' );
	assert_same( $id, (int) $r['returned'], 'edit_post() saved the post' );
	clean_post_cache( $id );
}

/**
 * The text a browser shows in the name box on the post's edit screen.
 * edit-form-advanced.php prints
 * <input type="text" name="post_title" … value="<?php echo esc_attr( $post->post_title ); ?>" id="title" …>
 * for $post = get_post( $post_id, OBJECT, 'edit' ) (post.php); the HTML
 * API reads the attribute back as a browser does.
 *
 * @param int $id Post.
 */
function qa053_name_box( int $id ): string {
	$post = get_post( $id, OBJECT, 'edit' );
	$html = '<input type="text" name="post_title" size="30" value="' . esc_attr( $post->post_title ) . '" id="title" spellcheck="true" autocomplete="off" />';
	$tags = new \WP_HTML_Tag_Processor( $html );
	if ( ! $tags->next_tag( 'input' ) ) {
		fail( 'could not read the name box' );
	}
	return (string) $tags->get_attribute( 'value' );
}

/**
 * Check that core still prints the name box the way qa053_name_box() reads it.
 */
function qa053_core_name_box_unchanged(): void {
	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- a local core file.
	$src = (string) file_get_contents( ABSPATH . 'wp-admin/edit-form-advanced.php' );
	assert_contains( 'name="post_title" size="30" value="<?php echo esc_attr( $post->post_title ); ?>" id="title"', $src, 'set-up: core prints the name box as this test reads it' );
}

/**
 * The event form's fields for a Training on a future day (no problems, so
 * Publish publishes), with a fresh nonce for the current user.
 */
function qa053_event_fields(): array {
	return array(
		'spokares_event_nonce' => wp_create_nonce( 'spokares_event_meta' ),
		'spk_kind'             => 'training',
		'spk_date_mode'        => 'date',
		'spk_start'            => qa053_day( 20 ),
		'spk_all_day'          => '1',
		'spk_summary'          => 'For QA-053',
	);
}

/**
 * The document form's fields for a Forms-section link with the Privacy
 * check ticked (no problems, so Publish publishes), with a fresh nonce.
 */
function qa053_document_fields(): array {
	return array(
		'spokares_document_nonce' => wp_create_nonce( 'spokares_document_meta' ),
		'spk_section'             => 'forms',
		'spk_source'              => 'link',
		'spk_url'                 => 'https://example.org/qa-053.pdf',
		'spk_privacy_ok'          => '1',
	);
}

/**
 * Publish a new draft of $type named $typed through its form, check the
 * name box, press Update with the box as shown, and check it again; then
 * check the site shows the name once.
 *
 * @param string   $role   Role key.
 * @param string   $type   spk_event or spk_document.
 * @param string   $typed  The name as typed.
 * @param callable $fields Returns the plugin form's fields.
 */
function qa053_check_round_trip( string $role, string $type, string $typed, callable $fields ): void {
	as_role( $role );
	$label = $role . ' ' . $type;
	$id    = create_post(
		array(
			'post_type'   => $type,
			'post_status' => 'draft',
			'post_title'  => 'QA-053 draft',
			'post_author' => get_current_user_id(),
		)
	);

	qa053_editpost( $id, $typed, $fields() );
	assert_same( 'publish', get_post_status( $id ), $label . ': set-up: Publish published it' );
	assert_same( $typed, qa053_name_box( $id ), $label . ': after Publish the name box shows the name as typed' );

	// Update again with the name box exactly as it was shown.
	qa053_editpost( $id, qa053_name_box( $id ), $fields() );
	assert_same( 'publish', get_post_status( $id ), $label . ': set-up: still published after Update' );
	assert_same( $typed, qa053_name_box( $id ), $label . ': after Update the name box still shows the name as typed' );

	// The site shows the name once: "&" (as &amp; or &#038;), never "&amp;" as text.
	$shown = html_entity_decode( get_the_title( $id ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	assert_not_contains( '&amp;', $shown, $label . ': the site does not show "&amp;"' );
	assert_contains( 'Q&A', $shown, $label . ': the site shows the "&"' );
}

test(
	'an event named with "&" shows the name as typed in the name box after Publish and after Update',
	function () {
		qa053_core_name_box_unchanged();
		foreach ( array( 'ares-editor', 'ares-net', 'admin' ) as $role ) {
			qa053_check_round_trip( $role, 'spk_event', 'QA Q&A "night" & Winlink', __NAMESPACE__ . '\qa053_event_fields' );
		}
	}
);

test(
	'a document named with "&" shows the name as typed in the name box after Publish and after Update',
	function () {
		qa053_core_name_box_unchanged();
		foreach ( array( 'ares-editor', 'ares-net', 'admin' ) as $role ) {
			qa053_check_round_trip( $role, 'spk_document', 'QA Tips & Tricks Q&A', __NAMESPACE__ . '\qa053_document_fields' );
		}
	}
);

test(
	'markup in an event or document name is still not stored or printed (the fix must not drop the title filtering)',
	function () {
		// Typed tags, and typed entities that a decode-on-save fix would turn into tags.
		$names = array(
			'QA <script>alert(1)</script><img src=x onerror=alert(2)> & more',
			'QA &lt;script&gt;alert(3)&lt;/script&gt; &lt;img src=x onerror=alert(4)&gt; & more',
		);
		foreach ( array( 'ares-editor', 'ares-net', 'admin' ) as $role ) {
			foreach ( array(
				'spk_event'    => __NAMESPACE__ . '\qa053_event_fields',
				'spk_document' => __NAMESPACE__ . '\qa053_document_fields',
			) as $type => $fields ) {
				foreach ( $names as $i => $typed ) {
					as_role( $role );
					$id = create_post(
						array(
							'post_type'   => $type,
							'post_status' => 'draft',
							'post_title'  => 'QA-053 draft',
							'post_author' => get_current_user_id(),
						)
					);
					qa053_editpost( $id, $typed, $fields() );
					$label = $role . ' ' . $type . ' name ' . ( $i + 1 );
					foreach ( array( get_post_field( 'post_title', $id, 'raw' ), get_the_title( $id ) ) as $title ) {
						assert_not_contains( '<script', strtolower( $title ), $label . ': no <script> in the stored or shown name' );
						assert_not_contains( '<img', strtolower( $title ), $label . ': no <img> in the stored or shown name' );
					}
				}
			}
		}
	}
);
