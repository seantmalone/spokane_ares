<?php
/**
 * Editor governance (§4.3, §6.5): the server-side layout check (the real
 * lock), block editor settings and allowed blocks for non-admins, the editor
 * guard script, and the page slug/parent/status guard.
 *
 * "Non-admin" here means a user without edit_theme_options.
 *
 * @package spokares-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Block types the patterns use; the only ones non-admins may insert or keep.
 */
function spokares_allowed_block_types(): array {
	$core = array(
		'core/paragraph',
		'core/heading',
		'core/list',
		'core/list-item',
		'core/buttons',
		'core/button',
		'core/group',
		'core/cover',
		'core/spacer',
		'core/separator',
		'core/table',
		'core/quote',
		'core/image',
	);
	foreach ( spokares_block_names() as $name ) {
		$core[] = 'spokares/' . $name;
	}
	$core[] = 'spokares-theme/message-path';
	return $core;
}

/**
 * Is the current user held to the layout lock?
 */
function spokares_layout_locked_for_user(): bool {
	return is_user_logged_in() && ! current_user_can( 'edit_theme_options' );
}

/* ----------------------------------------------------------- the skeleton */

/**
 * Attributes editors may change, per block (plus metadata.noteId everywhere).
 * Replacing the Cover image also rewrites the values the Cover computes from
 * the new photo (isDark, the average-colour overlay, featured-image flag), so
 * those ride along with url and id; dimRatio stays locked at 0, so the
 * overlay stays invisible.
 */
function spokares_free_attributes(): array {
	return array(
		// overlayColor: when the photo's average colour equals a palette colour,
		// the editor stores that colour's slug here instead of customOverlayColor.
		'core/cover' => array( 'url', 'id', 'alt', 'focalPoint', 'sizeSlug', 'isDark', 'isUserOverlayColor', 'customOverlayColor', 'overlayColor', 'useFeaturedImage' ),
		'core/image' => array( 'id', 'sizeSlug', 'linkDestination' ),
	);
}

/**
 * The block skeleton of some content: for each block its name, its comment
 * attributes (sorted, minus the free ones) plus the id printed on its first
 * tag, and its inner blocks' skeletons.
 *
 * @param string $content Post content.
 * @return array<int,array{name:string,attrs:array,inner:array}>
 */
function spokares_block_skeleton( string $content ): array {
	return spokares_skeleton_nodes( parse_blocks( $content ) );
}

/**
 * Skeleton nodes of parsed blocks.
 *
 * @param array $blocks parse_blocks() output.
 */
function spokares_skeleton_nodes( array $blocks ): array {
	$free  = spokares_free_attributes();
	$nodes = array();
	foreach ( $blocks as $b ) {
		$name = $b['blockName'] ?? null;
		if ( null === $name ) {
			if ( '' === trim( (string) ( $b['innerHTML'] ?? '' ) ) ) {
				continue; // Whitespace between blocks.
			}
			$name = 'core/freeform';
		}
		$attrs = is_array( $b['attrs'] ?? null ) ? $b['attrs'] : array();
		foreach ( $free[ $name ] ?? array() as $key ) {
			unset( $attrs[ $key ] );
		}
		// The editor drops attributes equal to their default when it saves, so
		// a pattern that spells a default out must not count as a change.
		$type = WP_Block_Type_Registry::get_instance()->get_registered( $name );
		if ( $type && is_array( $type->attributes ) ) {
			foreach ( $attrs as $key => $value ) {
				if ( isset( $type->attributes[ $key ] ) && array_key_exists( 'default', $type->attributes[ $key ] ) && $type->attributes[ $key ]['default'] === $value ) {
					unset( $attrs[ $key ] );
				}
			}
		}
		if ( isset( $attrs['metadata'] ) && is_array( $attrs['metadata'] ) ) {
			unset( $attrs['metadata']['noteId'] );
			if ( ! $attrs['metadata'] ) {
				unset( $attrs['metadata'] );
			}
		}
		// Anchors of static blocks live in the HTML (the editor drops them from
		// the comment), and the HTML is what prints: the id on the first tag wins.
		$first = (string) ( $b['innerContent'][0] ?? $b['innerHTML'] ?? '' );
		if ( preg_match( '/^\s*<[a-z0-9]+\b[^>]*?\sid="([^"]*)"/i', $first, $m ) ) {
			$attrs['anchor'] = $m[1];
		}
		// The class on the first tag is what prints too: a class typed into the
		// HTML (not the comment) restyles the section on the front end.
		$classes = spokares_skeleton_classes( $name, $first );
		if ( $classes ) {
			$attrs['_class'] = $classes;
		}
		// Inline styles anywhere in the block's own HTML (not its inner blocks):
		// the patterns carry only the spacers' heights and the Cover's image
		// position, so any other style="" is a change of look, not of words.
		$styles = spokares_skeleton_styles( $name, (string) ( $b['innerHTML'] ?? '' ) );
		if ( $styles ) {
			$attrs['_style'] = $styles;
		}
		// A table's rows and cells live in its HTML, not in the comment. Its
		// shape (sections, rows, header or data cells) is layout: cell text is
		// free, but the Header/Footer section switches the editor still shows
		// in content-only mode, or an added row, would change it.
		if ( 'core/table' === $name ) {
			$attrs['_shape'] = spokares_table_shape( (string) ( $b['innerHTML'] ?? '' ) );
		}
		$attrs   = spokares_ksort_deep( $attrs );
		$nodes[] = array(
			'name'  => $name,
			'attrs' => $attrs,
			'inner' => spokares_skeleton_nodes( $b['innerBlocks'] ?? array() ),
		);
	}
	return $nodes;
}

/**
 * The class tokens of a block's first tag that count as layout, sorted.
 * WordPress's own "wp-…" classes are left out (a core update may change
 * them), and so are the classes that follow attributes editors may change:
 * the Cover's "is-light" (isDark, rewritten when the photo is replaced) and
 * the Image's "size-…" (sizeSlug).
 *
 * @param string $name  Block name.
 * @param string $first The block's HTML up to its first inner block.
 * @return string[]
 */
function spokares_skeleton_classes( string $name, string $first ): array {
	$tags = new WP_HTML_Tag_Processor( $first );
	if ( ! $tags->next_tag() ) {
		return array();
	}
	$class = $tags->get_attribute( 'class' );
	if ( ! is_string( $class ) || '' === trim( $class ) ) {
		return array();
	}
	$out = array();
	foreach ( preg_split( '/\s+/', trim( $class ) ) as $token ) {
		if ( str_starts_with( $token, 'wp-' ) ) {
			continue;
		}
		if ( 'core/cover' === $name && 'is-light' === $token ) {
			continue;
		}
		if ( 'core/image' === $name && str_starts_with( $token, 'size-' ) ) {
			continue;
		}
		$out[] = $token;
	}
	$out = array_values( array_unique( $out ) );
	sort( $out );
	return $out;
}

/**
 * Every inline style in a block's own HTML, as "TAG:style" entries in order.
 * The Cover's image and overlay styles follow attributes editors may change
 * (focal point, the overlay colour computed from a new photo), so they are
 * left out.
 *
 * @param string $name Block name.
 * @param string $html The block's own HTML (innerHTML, without inner blocks).
 * @return string[]
 */
function spokares_skeleton_styles( string $name, string $html ): array {
	if ( false === stripos( $html, 'style' ) ) {
		return array();
	}
	$out  = array();
	$tags = new WP_HTML_Tag_Processor( $html );
	while ( $tags->next_tag() ) {
		$style = $tags->get_attribute( 'style' );
		if ( null === $style ) {
			continue;
		}
		$tag = $tags->get_tag();
		if ( 'core/cover' === $name && ( 'IMG' === $tag || 'VIDEO' === $tag || ( 'SPAN' === $tag && $tags->has_class( 'wp-block-cover__background' ) ) ) ) {
			continue;
		}
		$out[] = $tag . ':' . preg_replace( '/\s+/', '', strtolower( is_string( $style ) ? $style : '' ) );
	}
	return $out;
}

/**
 * Does the content hold a button with no words? An empty button prints
 * nothing, so the call to action disappears from the page: that counts as
 * removing a block.
 *
 * @param string $content Post content.
 */
function spokares_has_empty_button( string $content ): bool {
	if ( ! str_contains( $content, 'wp:button' ) ) {
		return false;
	}
	$walk = static function ( array $blocks ) use ( &$walk ): bool {
		foreach ( $blocks as $b ) {
			if ( 'core/button' === ( $b['blockName'] ?? '' ) ) {
				$text = trim( html_entity_decode( wp_strip_all_tags( (string) ( $b['innerHTML'] ?? '' ) ), ENT_QUOTES, 'UTF-8' ) );
				if ( '' === $text ) {
					return true;
				}
			}
			if ( ! empty( $b['innerBlocks'] ) && $walk( $b['innerBlocks'] ) ) {
				return true;
			}
		}
		return false;
	};
	return $walk( parse_blocks( $content ) );
}

/**
 * The shape of a core/table's HTML: one entry per row, its section and its
 * cell tags, e.g. "thead:th,th,th" or "tbody:th,td,td".
 *
 * @param string $html The table block's HTML.
 * @return string[]
 */
function spokares_table_shape( string $html ): array {
	$rows    = array();
	$section = 'tbody';
	$tags    = new WP_HTML_Tag_Processor( $html );
	while ( $tags->next_tag() ) {
		$tag = $tags->get_tag();
		if ( in_array( $tag, array( 'THEAD', 'TBODY', 'TFOOT' ), true ) ) {
			$section = strtolower( $tag );
		} elseif ( 'TR' === $tag ) {
			$rows[] = array( $section );
		} elseif ( ( 'TH' === $tag || 'TD' === $tag ) && $rows ) {
			$rows[ count( $rows ) - 1 ][] = strtolower( $tag );
		}
	}
	return array_map(
		static function ( array $row ): string {
			$section = array_shift( $row );
			return $section . ':' . implode( ',', $row );
		},
		$rows
	);
}

/**
 * Sort an array's string keys recursively (lists keep their order).
 *
 * @param array $a Array.
 */
function spokares_ksort_deep( array $a ): array {
	foreach ( $a as $k => $v ) {
		if ( is_array( $v ) ) {
			$a[ $k ] = spokares_ksort_deep( $v );
		}
	}
	if ( ! array_is_list( $a ) ) {
		ksort( $a );
	}
	return $a;
}

/**
 * Do two skeleton lists match? Inside a core/list, items may be added,
 * removed or reordered, but every item must match one of the old items (with
 * the same tolerance) or be a bare list item.
 *
 * @param array $old Stored skeleton nodes.
 * @param array $new New skeleton nodes.
 */
function spokares_skeleton_equal( array $old, array $new ): bool {
	if ( count( $old ) !== count( $new ) ) {
		return false;
	}
	foreach ( $old as $i => $o ) {
		if ( ! spokares_skeleton_node_equal( $o, $new[ $i ] ) ) {
			return false;
		}
	}
	return true;
}

/**
 * Compare two skeleton nodes.
 *
 * @param array $o Old node.
 * @param array $n New node.
 */
function spokares_skeleton_node_equal( array $o, array $n ): bool {
	if ( $o['name'] !== $n['name'] || $o['attrs'] !== $n['attrs'] ) {
		return false;
	}
	if ( 'core/list' !== $o['name'] ) {
		return spokares_skeleton_equal( $o['inner'], $n['inner'] );
	}
	$bare = array(
		'name'  => 'core/list-item',
		'attrs' => array(),
		'inner' => array(),
	);
	foreach ( $n['inner'] as $item ) {
		if ( $item === $bare ) {
			continue;
		}
		$ok = false;
		foreach ( $o['inner'] as $old_item ) {
			if ( spokares_skeleton_node_equal( $old_item, $item ) ) {
				$ok = true;
				break;
			}
		}
		if ( ! $ok ) {
			return false;
		}
	}
	return true;
}

/**
 * Would saving $new over $old change the layout?
 *
 * @param string $old Stored content.
 * @param string $new New content.
 */
function spokares_layout_changed( string $old, string $new ): bool {
	return ! spokares_skeleton_equal( spokares_block_skeleton( $old ), spokares_block_skeleton( $new ) );
}

/**
 * The layout-lock error.
 *
 * @param string $why 'layout' (default) or 'empty-button'.
 */
function spokares_layout_error( string $why = 'layout' ): WP_Error {
	$undo = __( 'Undo the last change (Ctrl+Z, or Cmd+Z on a Mac, or the curved Undo arrow at the top left) and save again, or ask the webmaster.', 'spokares-core' );
	if ( 'empty-button' === $why ) {
		return new WP_Error(
			'spokares_empty_button',
			__( 'A button can’t be empty: its words are the link people click. Type them back, or', 'spokares-core' ) . ' ' . lcfirst( $undo ),
			array( 'status' => 403 )
		);
	}
	return new WP_Error(
		'spokares_layout_locked',
		__( 'Layout changes need an administrator.', 'spokares-core' ) . ' ' . $undo,
		array( 'status' => 403 )
	);
}

/**
 * Would a non-admin's new page content be refused? Returns '' when it may be
 * saved, else 'layout' or 'empty-button'.
 *
 * @param string $old Stored content.
 * @param string $new New content.
 */
function spokares_page_content_problem( string $old, string $new ): string {
	if ( spokares_layout_changed( $old, $new ) ) {
		return 'layout';
	}
	if ( spokares_has_empty_button( $new ) && ! spokares_has_empty_button( $old ) ) {
		return 'empty-button';
	}
	return '';
}

/**
 * REST saves of a page by a non-admin: refuse a layout change.
 *
 * An autosave (/autosaves) is never refused: the autosave controller would
 * store the error object itself as an empty autosave, which then shows
 * "There is an autosave of this page…" with nothing in it. It keeps the
 * stored content instead, so the autosave holds no layout change. After a
 * refused save, the editor's old autosave of the page is deleted for the same
 * reason.
 *
 * @param stdClass|WP_Error $prepared Prepared post.
 * @param WP_REST_Request   $request  Request.
 */
function spokares_rest_layout_check( $prepared, $request ) {
	if ( is_wp_error( $prepared ) || ! spokares_layout_locked_for_user() ) {
		return $prepared;
	}
	if ( empty( $prepared->ID ) || ! isset( $prepared->post_content ) ) {
		return $prepared;
	}
	$stored = get_post( (int) $prepared->ID );
	if ( ! $stored || 'page' !== $stored->post_type ) {
		return $prepared;
	}
	$problem = spokares_page_content_problem( (string) $stored->post_content, (string) $prepared->post_content );
	if ( '' === $problem ) {
		return $prepared;
	}
	$route = $request instanceof WP_REST_Request ? (string) $request->get_route() : '';
	if ( str_ends_with( untrailingslashit( $route ), '/autosaves' ) ) {
		$prepared->post_content = $stored->post_content;
		return $prepared;
	}
	$autosave = wp_get_post_autosave( (int) $stored->ID, get_current_user_id() );
	if ( $autosave ) {
		wp_delete_post_revision( $autosave->ID );
	}
	return spokares_layout_error( $problem );
}
add_filter( 'rest_pre_insert_page', 'spokares_rest_layout_check', 10, 2 );

/**
 * Every other save of a page (classic POST, Quick Edit, revision restore):
 * keep the stored content when a non-admin's save would change the layout.
 *
 * For non-admins the page itself is fixed too: its slug, parent, author,
 * order, password, publish date and published status are kept as stored, so
 * a page can't be put behind a password, scheduled, made private or moved.
 * (The block editor hides those controls from them; this is the floor.)
 *
 * @param array $data    Slashed post data.
 * @param array $postarr Raw post array.
 */
function spokares_page_insert_guard( $data, $postarr ) {
	if ( 'page' !== ( $data['post_type'] ?? '' ) || empty( $postarr['ID'] ) ) {
		return $data;
	}
	if ( ! is_user_logged_in() || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
		return $data;
	}
	$stored = get_post( (int) $postarr['ID'] );
	if ( ! $stored || 'page' !== $stored->post_type || 'auto-draft' === $stored->post_status ) {
		return $data;
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		$data['post_name']     = wp_slash( $stored->post_name );
		$data['post_parent']   = (int) $stored->post_parent;
		$data['post_author']   = (int) $stored->post_author;
		$data['menu_order']    = (int) $stored->menu_order;
		$data['post_password'] = wp_slash( $stored->post_password );
		if ( 'publish' === $stored->post_status ) {
			$data['post_status']   = 'publish';
			$data['post_date']     = $stored->post_date;
			$data['post_date_gmt'] = $stored->post_date_gmt;
		}
	}
	if ( spokares_layout_locked_for_user() && isset( $data['post_content'] ) ) {
		$new = wp_unslash( (string) $data['post_content'] );
		if ( '' !== spokares_page_content_problem( (string) $stored->post_content, $new ) ) {
			$data['post_content'] = wp_slash( $stored->post_content );
		}
	}
	return $data;
}
add_filter( 'wp_insert_post_data', 'spokares_page_insert_guard', 20, 2 );

/**
 * The three members pages' templates hold everything on those pages, so no
 * other page may use them. For non-admins they are not offered as page
 * templates at all (the REST check refuses them too).
 *
 * @param array $templates Template slug => name.
 */
function spokares_page_templates_for_editors( $templates ) {
	if ( ! is_array( $templates ) || ! spokares_layout_locked_for_user() ) {
		return $templates;
	}
	unset( $templates['page-members'], $templates['page-documents'], $templates['page-exercises'] );
	return $templates;
}
add_filter( 'theme_page_templates', 'spokares_page_templates_for_editors', 20 );

/**
 * A page's template is layout: a non-admin's save never changes it, whatever
 * path wrote it (the editor's Template panel, Quick Edit, REST). The stored
 * template is kept.
 *
 * @param mixed  $check      Short-circuit value (null to go on).
 * @param int    $object_id  Post ID.
 * @param string $meta_key   Meta key.
 * @param mixed  $meta_value New value (update/add) or value to delete.
 */
function spokares_page_template_guard( $check, $object_id, $meta_key, $meta_value = '' ) {
	if ( null !== $check || '_wp_page_template' !== $meta_key || ! spokares_layout_locked_for_user() ) {
		return $check;
	}
	if ( 'page' !== get_post_type( (int) $object_id ) ) {
		return $check;
	}
	$stored  = (string) get_post_meta( (int) $object_id, '_wp_page_template', true );
	$new     = is_scalar( $meta_value ) ? (string) $meta_value : '';
	$default = static fn( string $t ): bool => '' === $t || 'default' === $t;
	if ( 'delete_post_metadata' === current_filter() ) {
		return $default( $stored ) ? $check : false;
	}
	if ( $stored === $new || ( $default( $stored ) && $default( $new ) ) ) {
		return $check;
	}
	return false;
}
add_filter( 'update_post_metadata', 'spokares_page_template_guard', 10, 4 );
add_filter( 'add_post_metadata', 'spokares_page_template_guard', 10, 4 );
add_filter( 'delete_post_metadata', 'spokares_page_template_guard', 10, 4 );

/**
 * Inline styles that can lift text out of the page (position, offsets,
 * stacking) are never kept in a non-admin's save. The layout check refuses
 * new styles in page text anyway; this is the second net.
 *
 * @param string[] $props Allowed CSS properties.
 */
function spokares_safe_css_for_editors( $props ) {
	if ( ! is_array( $props ) || ! spokares_layout_locked_for_user() ) {
		return $props;
	}
	return array_values( array_diff( $props, array( 'position', 'top', 'right', 'bottom', 'left', 'z-index' ) ) );
}
add_filter( 'safe_style_css', 'spokares_safe_css_for_editors', 20 );

/* ----------------------------------------------------- editor for editors */

/**
 * Block editor settings for non-admins editing a page.
 *
 * @param array                   $settings Settings.
 * @param WP_Block_Editor_Context $context  Context.
 */
function spokares_editor_settings( $settings, $context ) {
	if ( ! spokares_layout_locked_for_user() ) {
		return $settings;
	}
	$settings['codeEditingEnabled'] = false;
	$settings['canLockBlocks']      = false;
	if ( isset( $context->post ) && $context->post instanceof WP_Post && 'page' === $context->post->post_type ) {
		$settings['templateLock']                          = 'contentOnly';
		$settings['disableContentOnlyForUnsyncedPatterns'] = true;
	}
	return $settings;
}
add_filter( 'block_editor_settings_all', 'spokares_editor_settings', 20, 2 );

/**
 * Pages open with the template shown (header, footer, the page's own sections
 * in between), for everyone: the canvas then looks like the site, and the page
 * title no longer sits above Home's hero. Header and footer can't be edited
 * there. A user who switches "Show template" off keeps that choice.
 *
 * @param array                   $settings Settings.
 * @param WP_Block_Editor_Context $context  Context.
 */
function spokares_editor_rendering_mode( $settings, $context ) {
	if ( isset( $context->post ) && $context->post instanceof WP_Post && 'page' === $context->post->post_type ) {
		$settings['defaultRenderingMode'] = 'template-locked';
	}
	return $settings;
}
add_filter( 'block_editor_settings_all', 'spokares_editor_rendering_mode', 20, 2 );

/**
 * With the template shown, the header and footer Navigation blocks ask for the
 * site's menus (published and draft, edit context). Editors may not read menus
 * that way, so core answered 400/403: a console error on every page edit. The
 * theme's menus are inline links in the template parts and no menu posts are
 * used, so editors get an empty list instead.
 *
 * @param mixed           $result  Response so far (null).
 * @param WP_REST_Server  $server  Server.
 * @param WP_REST_Request $request Request.
 */
function spokares_navigation_list_for_editors( $result, $server, $request ) {
	unset( $server );
	if ( null !== $result || 'GET' !== $request->get_method() || '/wp/v2/navigation' !== $request->get_route() ) {
		return $result;
	}
	if ( ! is_user_logged_in() || current_user_can( 'edit_theme_options' ) ) {
		return $result;
	}
	$response = new WP_REST_Response( array(), 200 );
	$response->header( 'X-WP-Total', '0' );
	$response->header( 'X-WP-TotalPages', '0' );
	return $response;
}
add_filter( 'rest_pre_dispatch', 'spokares_navigation_list_for_editors', 10, 3 );

/**
 * Welcome Guide and the starter-pattern window off for editors, stored with
 * their preferences before the editor loads. (The editor guard also switches
 * them off, but only after the editor has started, so on a first visit the
 * guide could open first.)
 */
function spokares_editor_preferences_default(): void {
	if ( ! spokares_layout_locked_for_user() ) {
		return;
	}
	global $wpdb;
	$user_id = get_current_user_id();
	$key     = $wpdb->get_blog_prefix() . 'persisted_preferences';
	$prefs   = get_user_meta( $user_id, $key, true );
	$prefs   = is_array( $prefs ) ? $prefs : array();
	$want    = array(
		'core/edit-post' => array(
			'welcomeGuide'         => false,
			'welcomeGuideTemplate' => false,
		),
		'core'           => array( 'enableChoosePatternModal' => false ),
	);
	$changed = false;
	foreach ( $want as $scope => $values ) {
		foreach ( $values as $name => $value ) {
			if ( ! isset( $prefs[ $scope ] ) || ! is_array( $prefs[ $scope ] ) ) {
				$prefs[ $scope ] = array();
			}
			if ( ! array_key_exists( $name, $prefs[ $scope ] ) || $value !== $prefs[ $scope ][ $name ] ) {
				$prefs[ $scope ][ $name ] = $value;
				$changed                  = true;
			}
		}
	}
	if ( $changed ) {
		$prefs['_modified'] = gmdate( 'Y-m-d\TH:i:s.000\Z' );
		update_user_meta( $user_id, $key, $prefs );
	}
}
add_action( 'load-post.php', 'spokares_editor_preferences_default' );
add_action( 'load-post-new.php', 'spokares_editor_preferences_default' );

/**
 * Only the pattern block types for non-admins.
 *
 * @param bool|string[]           $allowed Allowed types.
 * @param WP_Block_Editor_Context $context Context.
 */
function spokares_allowed_blocks( $allowed, $context ) {
	unset( $context );
	if ( ! spokares_layout_locked_for_user() ) {
		return $allowed;
	}
	return spokares_allowed_block_types();
}
add_filter( 'allowed_block_types_all', 'spokares_allowed_blocks', 20, 2 );

/**
 * The editor guard script: content-only editing modes, list items as the only
 * insertable block, never-publish/phone/e-mail warnings after a save, Welcome
 * Guide and starter patterns off.
 */
function spokares_enqueue_editor_guard(): void {
	if ( ! spokares_layout_locked_for_user() ) {
		return;
	}
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen || 'page' !== $screen->post_type ) {
		return;
	}
	wp_enqueue_script(
		'spokares-editor-guard',
		SPOKARES_CORE_URL . 'assets/js/editor-guard.js',
		array( 'wp-blocks', 'wp-data', 'wp-dom-ready', 'wp-hooks', 'wp-notices', 'wp-preferences', 'wp-editor', 'wp-block-editor', 'wp-rich-text' ),
		spokares_asset_version( 'assets/js/editor-guard.js' ),
		true
	);
	$patterns = array();
	foreach ( spokares_text_patterns() as $p ) {
		$patterns[] = array(
			're'   => $p['re'],
			'what' => $p['what'],
		);
	}
	wp_add_inline_script(
		'spokares-editor-guard',
		'window.spokaresGuard = ' . wp_json_encode(
			array(
				'patterns' => $patterns,
				'allowed'  => '[A-Z0-9._%+-]+@spokares\\.org\\b',
				/* translators: %s: what was found, e.g. "a phone number". */
				'message'  => __( 'This page may contain something we never publish: %s. Check it before you leave.', 'spokares-core' ),
				'pasted'   => __( 'Pasted as one paragraph with line breaks: new paragraphs can’t be added to this page. To keep them apart, paste one paragraph at a time into the paragraphs that are already there.', 'spokares-core' ),
				'pastedOn' => __( 'Pasted as one line: a heading or a button holds one line of words.', 'spokares-core' ),
				// Lists whose items start with bold words that print as the item's title.
				'leadIns'  => array( 'stops', 'years', 'hops' ),
				/* translators: %s: the first words of the list item. */
				'noLeadIn' => __( 'A list item has no bold first words, so it shows with no title: “%s”. Select its first words and press Ctrl+B (Cmd+B on a Mac), then Save.', 'spokares-core' ),
			)
		) . ';',
		'before'
	);
}
add_action( 'enqueue_block_editor_assets', 'spokares_enqueue_editor_guard' );
