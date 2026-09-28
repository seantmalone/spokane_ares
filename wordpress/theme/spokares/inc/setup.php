<?php
/**
 * Theme setup: supports, the one stylesheet and the header script, the two
 * theme blocks, pattern categories, template descriptions. PLAN.md §6.7
 * (filters 5 and 8) and §6.2 (names).
 *
 * @package spokares
 */

defined( 'ABSPATH' ) || exit;

/**
 * Version string for a theme asset: the theme version in production, the
 * file's modification time on a local dev site (so edits show without a
 * version bump).
 *
 * @param string $relative_path Path inside the theme, e.g. 'assets/css/site.css'.
 * @return string
 */
function spokares_theme_asset_version( string $relative_path ): string {
	if ( 'local' === wp_get_environment_type() ) {
		$file = get_theme_file_path( $relative_path );
		if ( is_readable( $file ) ) {
			return SPOKARES_THEME_VERSION . '.' . (string) filemtime( $file );
		}
	}
	return SPOKARES_THEME_VERSION;
}

/**
 * Theme supports. Core block patterns are removed (filter 8); site.css is
 * also the editor stylesheet so the canvas looks like the front end.
 */
function spokares_theme_setup(): void {
	remove_theme_support( 'core-block-patterns' );
	// B's stylesheet does all the layout. WordPress's layout CSS (flow margins,
	// flex gaps, the 760px content column) is left out, on the front end and in
	// the editor canvas, where it would otherwise be printed after site.css.
	add_theme_support( 'disable-layout-styles' );
	add_theme_support( 'editor-styles' );
	add_editor_style( 'assets/css/site.css' );
	// Page excerpts feed <meta name="description"> (filter 5).
	add_post_type_support( 'page', 'excerpt' );
}
add_action( 'after_setup_theme', 'spokares_theme_setup' );

/**
 * Editor canvas: put the theme's stylesheet after WordPress's global styles,
 * the same order as on the front end. Without this, core rules of equal
 * specificity printed later in the canvas (the flow-layout margins, the
 * button element) override B's own spacing and buttons in the editor only.
 *
 * @param array $settings Block editor settings.
 * @return array
 */
function spokares_theme_editor_style_order( array $settings ): array {
	if ( empty( $settings['styles'] ) || ! is_array( $settings['styles'] ) ) {
		return $settings;
	}
	$theme = array();
	$other = array();
	foreach ( $settings['styles'] as $style ) {
		if ( is_array( $style ) && isset( $style['__unstableType'] ) && 'theme' === $style['__unstableType'] ) {
			$theme[] = $style;
		} else {
			$other[] = $style;
		}
	}
	$settings['styles'] = array_merge( $other, $theme );
	return $settings;
}
add_filter( 'block_editor_settings_all', 'spokares_theme_editor_style_order', 20 );

// No emoji detection script or emoji styles on the front end: the site uses
// no emoji, and they cost an inline script and a style block on every page.
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_enqueue_scripts', 'wp_enqueue_emoji_styles' );
remove_action( 'wp_print_styles', 'print_emoji_styles' );

// No patterns from the WordPress.org directory either: every pattern on this site is the theme's own.
add_filter( 'should_load_remote_block_patterns', '__return_false' );

/**
 * The one stylesheet, front end. Handle "spokares-site" (§6.2). Plus one
 * small deferred script for the header: it closes the phone menu when the
 * window grows past the 1020px breakpoint (assets/js/header.js).
 */
function spokares_theme_enqueue(): void {
	wp_enqueue_style(
		'spokares-site',
		get_theme_file_uri( 'assets/css/site.css' ),
		array(),
		spokares_theme_asset_version( 'assets/css/site.css' )
	);
	wp_enqueue_script(
		'spokares-header',
		get_theme_file_uri( 'assets/js/header.js' ),
		array(),
		spokares_theme_asset_version( 'assets/js/header.js' ),
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);
}
add_action( 'wp_enqueue_scripts', 'spokares_theme_enqueue' );

/**
 * What each of the theme's page templates is for, shown under its name in
 * Appearance › Editor › Templates. theme.json ("customTemplates") names them
 * but has no description field.
 *
 * @return array<string, string> Template slug => description.
 */
function spokares_theme_template_descriptions(): array {
	return array(
		'page-members'      => __( 'The For members page (/members/): This week, the Tuesday net and the most used documents. Every list on it is edited in wp-admin.', 'spokares' ),
		'page-exercises'    => __( 'The Exercises & events page (/members/exercises/): next up, later this season, Winlink assignments, public service and past exercises, from Exercises & Events and the Net Control Schedule in wp-admin.', 'spokares' ),
		'page-documents'    => __( 'The Documents & forms page (/members/documents/): the document library, from Documents in wp-admin.', 'spokares' ),
		'page-how-it-works' => __( 'The How it works page: header, the page’s own full-width sections (its heading included), footer.', 'spokares' ),
		'page-about'        => __( 'The About ARES & ACS page: header, the page’s own head and long read with its contents, footer.', 'spokares' ),
	);
}

/**
 * Give one of the theme's templates its description when it has none.
 *
 * @param WP_Block_Template|null $template Template.
 * @return WP_Block_Template|null
 */
function spokares_theme_describe_template( $template ) {
	if ( ! $template instanceof WP_Block_Template || 'wp_template' !== $template->type || get_stylesheet() !== $template->theme || '' !== (string) $template->description ) {
		return $template;
	}
	$descriptions = spokares_theme_template_descriptions();
	if ( isset( $descriptions[ $template->slug ] ) ) {
		$template->description = $descriptions[ $template->slug ];
	}
	return $template;
}
add_filter( 'get_block_template', 'spokares_theme_describe_template' );

/**
 * The same for every template in a list (the Site Editor's Templates screen).
 *
 * @param WP_Block_Template[] $templates Templates.
 * @return WP_Block_Template[]
 */
function spokares_theme_describe_templates( $templates ) {
	if ( is_array( $templates ) ) {
		array_map( 'spokares_theme_describe_template', $templates );
	}
	return $templates;
}
add_filter( 'get_block_templates', 'spokares_theme_describe_templates' );

/**
 * The two design-only PHP blocks (block.json + render.php, autoRegister) and
 * the three pattern categories (§6.2).
 */
function spokares_theme_register(): void {
	register_block_type( get_theme_file_path( 'blocks/brand' ) );
	register_block_type( get_theme_file_path( 'blocks/message-path' ) );

	register_block_pattern_category( 'spokares-pages', array( 'label' => __( 'ARES: whole pages', 'spokares' ) ) );
	register_block_pattern_category( 'spokares-sections', array( 'label' => __( 'ARES: sections', 'spokares' ) ) );
	register_block_pattern_category( 'spokares-members', array( 'label' => __( 'ARES: members pages', 'spokares' ) ) );
}
add_action( 'init', 'spokares_theme_register' );

/**
 * Title separator: "How it works | Spokane County ARES-ACS" (filter 5).
 *
 * @return string
 */
function spokares_theme_title_separator(): string {
	return '|';
}
add_filter( 'document_title_separator', 'spokares_theme_title_separator' );

/**
 * Body class "spk-verify" when a logged-in page editor adds ?verify=1 (filter 6).
 * It outlines every .needs-verify element (B's data-verify reviewer aid).
 * Readers never get it: the class needs the edit_pages capability.
 *
 * @param string[] $classes Body classes.
 * @return string[]
 */
function spokares_theme_body_class( array $classes ): array {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only display toggle, no state change.
	$verify = isset( $_GET['verify'] ) ? sanitize_key( wp_unslash( $_GET['verify'] ) ) : '';
	if ( '1' === $verify && is_user_logged_in() && current_user_can( 'edit_pages' ) ) {
		$classes[] = 'spk-verify';
	}
	return $classes;
}
add_filter( 'body_class', 'spokares_theme_body_class' );
