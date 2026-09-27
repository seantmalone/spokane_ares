<?php
/**
 * The seven dynamic blocks (§6.4): category, registration, the three view
 * modules, and the shared pieces every render.php uses (wrapper, editor hint,
 * front-end edit link).
 *
 * @package spokares-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Block folder names.
 */
function spokares_block_names(): array {
	return array( 'events', 'net', 'meetings', 'docs', 'toc', 'last-reviewed', 'asof' );
}

/**
 * The "ARES" block category.
 *
 * @param array $categories Categories.
 */
function spokares_block_category( $categories ) {
	foreach ( $categories as $c ) {
		if ( 'spokares' === ( $c['slug'] ?? '' ) ) {
			return $categories;
		}
	}
	array_unshift(
		$categories,
		array(
			'slug'  => 'spokares',
			'title' => __( 'ARES', 'spokares-core' ),
			'icon'  => null,
		)
	);
	return $categories;
}
add_filter( 'block_categories_all', 'spokares_block_category' );

/**
 * Register the front-end script modules before the blocks (the TOC block
 * names its module in block.json).
 */
function spokares_register_modules(): void {
	$base = SPOKARES_CORE_URL . 'assets/js/';
	wp_register_script_module( '@spokares/copy', $base . 'copy.js', array(), spokares_asset_version( 'assets/js/copy.js' ) );
	wp_register_script_module( '@spokares/doc-library', $base . 'doc-library.js', array(), spokares_asset_version( 'assets/js/doc-library.js' ) );
	wp_register_script_module( '@spokares/toc', $base . 'toc.js', array(), spokares_asset_version( 'assets/js/toc.js' ) );
}
add_action( 'init', 'spokares_register_modules', 5 );

/**
 * Register every block folder.
 */
function spokares_register_blocks(): void {
	foreach ( spokares_block_names() as $name ) {
		register_block_type( SPOKARES_CORE_DIR . 'blocks/' . $name );
	}
}
add_action( 'init', 'spokares_register_blocks' );

/**
 * The admin screens the hints and edit links name: key => [label, URL, capability].
 *
 * @param string $key Screen key.
 */
function spokares_edit_screen( string $key ): ?array {
	$screens = array(
		'rota'        => array( __( 'Net rota', 'spokares-core' ), admin_url( 'admin.php?page=spokares-rota' ), 'spokares_edit_rota' ),
		'net-details' => array( __( 'Net details', 'spokares-core' ), admin_url( 'admin.php?page=spokares-net-details' ), 'spokares_edit_net_details' ),
		'events'      => array( __( 'Events', 'spokares-core' ), admin_url( 'edit.php?post_type=spk_event' ), 'edit_spk_events' ),
		'meetings'    => array( __( 'Regular meetings', 'spokares-core' ), admin_url( 'admin.php?page=spokares-meetings' ), 'spokares_edit_rota' ),
		'documents'   => array( __( 'Documents', 'spokares-core' ), admin_url( 'edit.php?post_type=spk_document' ), 'edit_spk_documents' ),
		'tiles'       => array( __( 'Hub tiles', 'spokares-core' ), admin_url( 'admin.php?page=spokares-tiles' ), 'edit_spk_documents' ),
		'page-review' => array( __( 'Page review box', 'spokares-core' ), '', 'edit_pages' ),
	);
	return $screens[ $key ] ?? null;
}

/**
 * Open a block's wrapper: <div class="wp-block-spokares-x is-view-y …">.
 *
 * @param string $view View name ('' for blocks without views).
 */
function spokares_block_open( string $view = '' ): string {
	$extra = '' !== $view ? array( 'class' => 'is-view-' . sanitize_html_class( $view ) ) : array();
	return '<div ' . get_block_wrapper_attributes( $extra ) . '>';
}

/**
 * What goes at the end of a block, inside the wrapper: the plain-text editor
 * hint in the preview, or (front end) the "Edit this list in …" link for a
 * logged-in user who can use that screen.
 *
 * @param string $screen    Screen key ('' for none).
 * @param bool   $with_link Print the front-end link.
 */
function spokares_block_tail( string $screen, bool $with_link = true ): string {
	$s = '' !== $screen ? spokares_edit_screen( $screen ) : null;
	if ( ! $s ) {
		return '';
	}
	if ( spokares_is_editor_preview() ) {
		return '<p class="spk-edit-hint">' . esc_html(
			/* translators: %s: admin screen name, e.g. "Net rota". */
			sprintf( __( 'Edit in wp-admin › %s', 'spokares-core' ), $s[0] )
		) . '</p>';
	}
	if ( $with_link && '' !== $s[1] && is_user_logged_in() && current_user_can( $s[2] ) ) {
		return '<p class="spk-edit-link"><a href="' . esc_url( $s[1] ) . '">' . esc_html(
			/* translators: %s: admin screen name, e.g. "Net rota". */
			sprintf( __( 'Edit this list in %s', 'spokares-core' ), $s[0] )
		) . '</a></p>';
	}
	return '';
}

/**
 * A view that prints nothing publicly still shows a sentence in the editor
 * preview, so the block stays visible and selectable.
 *
 * @param string $sentence Placeholder text.
 * @param string $screen   Screen key for the hint.
 */
function spokares_block_placeholder( string $sentence, string $screen = '' ): string {
	if ( ! spokares_is_editor_preview() ) {
		return '';
	}
	return spokares_block_open() . '<p class="spk-edit-hint">' . esc_html( $sentence ) . '</p>' . ( '' !== $screen ? spokares_block_tail( $screen ) : '' ) . '</div>';
}

/**
 * Allowed HTML for echoing a render function's output. Everything inside was
 * escaped when it was built; this is a second net for the few tags used.
 */
function spokares_block_kses(): array {
	static $allowed = null;
	if ( null !== $allowed ) {
		return $allowed;
	}
	$common  = array(
		'class'       => true,
		'id'          => true,
		'hidden'      => true,
		'aria-hidden' => true,
		'aria-label'  => true,
		'aria-live'   => true,
		'role'        => true,
		'style'       => true,
	);
	$allowed = array_merge(
		spokares_icon_kses(),
		array(
			'div'     => $common + array( 'data-doc-library' => true ),
			'p'       => $common,
			'span'    => $common + array( 'data-doc-empty-q' => true ),
			'b'       => $common,
			'strong'  => $common,
			'a'       => $common + array(
				'href'           => true,
				'aria-current'   => true,
				'data-section'   => true,
				'data-doc-clear' => true,
			),
			'ul'      => $common,
			'ol'      => $common,
			'li'      => $common,
			'dl'      => $common,
			'dt'      => $common,
			'dd'      => $common,
			'table'   => $common,
			'caption' => $common,
			'thead'   => $common,
			'tbody'   => $common,
			'tr'      => $common + array(
				'data-doc'       => true,
				'data-search'    => true,
				'data-most-used' => true,
			),
			'th'      => $common + array( 'scope' => true ),
			'td'      => $common + array( 'data-label' => true ),
			'time'    => $common + array( 'datetime' => true ),
			'h2'      => $common,
			'h3'      => $common,
			'article' => $common,
			'section' => $common + array( 'data-section' => true ),
			'nav'     => $common + array( 'aria-labelledby' => true ),
			'details' => $common,
			'summary' => $common,
			'form'    => $common + array(
				'action' => true,
				'method' => true,
			),
			'label'   => $common + array( 'for' => true ),
			'input'   => $common + array(
				'type'         => true,
				'name'         => true,
				'value'        => true,
				'maxlength'    => true,
				'placeholder'  => true,
				'autocomplete' => true,
			),
			'button'  => $common + array(
				'type'           => true,
				'data-copy-text' => true,
			),
			'abbr'    => $common + array( 'title' => true ),
		)
	);
	return $allowed;
}

/**
 * Echo a render function's HTML through the block allowlist.
 *
 * @param string $html Markup built from escaped values.
 */
function spokares_block_echo( string $html ): void {
	echo wp_kses( $html, spokares_block_kses() );
}
