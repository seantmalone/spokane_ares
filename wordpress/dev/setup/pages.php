<?php
/**
 * Page setup for spokares.org (PLAN.md §6.10 "Pages"). NOT shipped.
 *
 * Creates the six pages once (found again by path, so it is idempotent):
 *   Home (/), How it works, About ARES & ACS  <- the theme's whole-page patterns
 *   For members, Documents & forms, Exercises & events  <- empty; theme templates
 * copies the theme's sunset photo into the Media Library once (_spk_seed = hero)
 * and points Home's hero Cover at it (url, "id":N, wp-image-N), sets the static
 * front page, the page excerpts (meta descriptions) and About's page owner.
 * Under WP-CLI it also deletes WordPress's untouched sample content.
 *
 * Usage:
 *   wp eval-file pages.php --user=<an administrator>    (production, after seed/import.php)
 *   From PHP (dev/setup/setup.php): define SPOKARES_DEV_IMPORT_LIBRARY, require
 *   this file, then call spokares_dev_setup_pages().
 *
 * Tolerates an incomplete theme: a page whose pattern isn't registered yet is
 * skipped with a log line.
 *
 * @package spokares-dev
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'spokares_dev_setup_pages' ) ) {

	/**
	 * Create the pages.
	 *
	 * @return array{ok:bool,log:string[]}
	 */
	function spokares_dev_setup_pages(): array {
		$log   = array();
		$extra = array();
		$path  = dirname( __DIR__ ) . '/seed/extra.json';
		if ( is_readable( $path ) ) {
			$extra = (array) json_decode( (string) file_get_contents( $path ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions -- local file.
		}
		$page_extra = (array) ( $extra['pages'] ?? array() );

		$pages = array(
			array( 'home', 'Home', '', 'spokares/page-home' ),
			array( 'how-it-works', 'How it works', '', 'spokares/page-how-it-works' ),
			array( 'about', 'About ARES & ACS', '', 'spokares/page-about' ),
			array( 'members', 'For members', '', '' ),
			array( 'documents', 'Documents & forms', 'members', '' ),
			array( 'exercises', 'Exercises & events', 'members', '' ),
		);

		$registry = WP_Block_Patterns_Registry::get_instance();
		$ids      = array();
		foreach ( $pages as $spec ) {
			list( $slug, $title, $parent_slug, $pattern ) = $spec;

			$full_path = $parent_slug ? $parent_slug . '/' . $slug : $slug;

			$existing = get_page_by_path( $full_path, OBJECT, 'page' );
			if ( $existing ) {
				$ids[ $slug ] = (int) $existing->ID;
				$log[]        = 'pages: /' . $full_path . '/ exists (ID ' . $existing->ID . '), left as it is';
				continue;
			}

			$content = '';
			if ( $pattern ) {
				if ( ! $registry->is_registered( $pattern ) ) {
					$log[] = 'pages: SKIP /' . $full_path . '/ (pattern ' . $pattern . ' is not registered; is the spokares theme active?)';
					continue;
				}
				$registered = $registry->get_registered( $pattern );
				$content    = (string) ( $registered['content'] ?? '' );
				if ( '' === trim( $content ) ) {
					$log[] = 'pages: SKIP /' . $full_path . '/ (pattern ' . $pattern . ' is empty)';
					continue;
				}
				if ( 'home' === $slug ) {
					$swap    = spokares_dev_hero_swap( $content );
					$content = $swap['content'];
					$log[]   = $swap['log'];
				}
			}

			$parent_id = 0;
			if ( $parent_slug ) {
				$parent_id = (int) ( $ids[ $parent_slug ] ?? 0 );
				if ( ! $parent_id ) {
					$log[] = 'pages: SKIP /' . $full_path . '/ (parent /' . $parent_slug . '/ missing)';
					continue;
				}
			}

			// wp_slash(): wp_insert_post() unslashes, and the block serializer
			// writes "--" inside comment JSON as --; without the slash
			// the backslashes are lost (card--dawn would become cardu002du002ddawn).
			$id = wp_insert_post(
				wp_slash(
					array(
						'post_type'    => 'page',
						'post_status'  => 'publish',
						'post_title'   => $title,
						'post_name'    => $slug,
						'post_parent'  => $parent_id,
						'post_content' => $content,
						'post_excerpt' => (string) ( $page_extra[ $slug ]['excerpt'] ?? '' ),
						'menu_order'   => count( $ids ),
					)
				),
				true
			);
			if ( is_wp_error( $id ) ) {
				$log[] = 'pages: /' . $full_path . '/ failed: ' . $id->get_error_message();
				continue;
			}
			$ids[ $slug ] = (int) $id;
			$log[]        = 'pages: created /' . $full_path . '/ (ID ' . $id . ( $pattern ? ', from ' . $pattern . ', ' . strlen( $content ) . ' bytes' : ', empty: theme template' ) . ')';

			$owner    = (string) ( $page_extra[ $slug ]['owner'] ?? '' );
			$reviewed = (string) ( $page_extra[ $slug ]['reviewed'] ?? '' );
			if ( '' !== $owner ) {
				update_post_meta( $id, '_spk_owner', $owner );
			}
			if ( '' !== $reviewed ) {
				update_post_meta( $id, '_spk_reviewed', $reviewed );
			}
		}

		if ( ! empty( $ids['home'] ) ) {
			update_option( 'show_on_front', 'page' );
			update_option( 'page_on_front', $ids['home'] );
			$log[] = 'pages: front page = Home (ID ' . $ids['home'] . ')';
		}

		return array(
			'ok'  => true,
			'log' => $log,
		);
	}

	/**
	 * Delete WordPress's sample content (PLAN.md §4.2): the Sample Page and
	 * "Hello world!" only while nobody has edited them, and the Privacy Policy
	 * page only while it is still the untouched draft.
	 *
	 * @return string[] Log lines.
	 */
	function spokares_dev_remove_sample_content(): array {
		$log = array();
		foreach ( array(
			array( 'sample-page', 'page' ),
			array( 'hello-world', 'post' ),
		) as $spec ) {
			$post = get_page_by_path( $spec[0], OBJECT, $spec[1] );
			if ( ! $post ) {
				continue;
			}
			if ( $post->post_modified_gmt !== $post->post_date_gmt ) {
				$log[] = 'pages: kept ' . $spec[1] . ' ' . $spec[0] . ' (edited since install)';
				continue;
			}
			wp_delete_post( $post->ID, true );
			$log[] = 'pages: deleted the sample ' . $spec[1] . ' ' . $spec[0];
		}
		$privacy = (int) get_option( 'wp_page_for_privacy_policy' );
		if ( $privacy && 'draft' === get_post_status( $privacy ) ) {
			$post = get_post( $privacy );
			if ( $post && $post->post_modified_gmt === $post->post_date_gmt ) {
				wp_delete_post( $privacy, true );
				update_option( 'wp_page_for_privacy_policy', 0 );
				$log[] = 'pages: deleted the Privacy Policy draft';
			}
		}
		return $log;
	}

	/**
	 * Copy the theme's sunset photo into the Media Library once and point the
	 * Home hero Cover at it: replace the theme URL (comment "url" and <img src>),
	 * add "id":N after the url and wp-image-N to the image class. That exact
	 * edit is what the block editor itself saves (checked by the patterns
	 * builder), so the page opens without an invalid-block notice and the
	 * front end prints srcset.
	 *
	 * @param string $content Home page content from the pattern.
	 * @return array{content:string,log:string}
	 */
	function spokares_dev_hero_swap( string $content ): array {
		$theme_url = esc_url( get_theme_file_uri( 'assets/img/1920x1200sunset.jpg' ) );
		if ( 2 !== substr_count( $content, $theme_url ) ) {
			return array(
				'content' => $content,
				'log'     => 'pages: hero left on the theme photo (expected the theme URL twice in the pattern, found ' . substr_count( $content, $theme_url ) . ')',
			);
		}

		$att_id = spokares_dev_hero_attachment();
		if ( is_wp_error( $att_id ) ) {
			return array(
				'content' => $content,
				'log'     => 'pages: hero left on the theme photo (' . $att_id->get_error_message() . ')',
			);
		}
		$att_url = (string) wp_get_attachment_url( $att_id );

		$new = str_replace( $theme_url, $att_url, $content );
		$new = str_replace( '"url":"' . $att_url . '",', '"url":"' . $att_url . '","id":' . $att_id . ',', $new, $n_id );
		$new = str_replace( 'class="wp-block-cover__image-background"', 'class="wp-block-cover__image-background wp-image-' . $att_id . '"', $new, $n_class );
		if ( 1 !== $n_id || 1 !== $n_class ) {
			return array(
				'content' => $content,
				'log'     => 'pages: hero left on the theme photo (unexpected Cover markup: id ' . $n_id . ', class ' . $n_class . ')',
			);
		}
		return array(
			'content' => $new,
			'log'     => 'pages: hero uses Media Library image ' . $att_id . ' (' . wp_basename( $att_url ) . ')',
		);
	}

	/**
	 * The hero photo's attachment ID: found by _spk_seed = hero, or copied
	 * from the theme into the Media Library.
	 *
	 * @return int|WP_Error
	 */
	function spokares_dev_hero_attachment() {
		$found = get_posts(
			array(
				'post_type'   => 'attachment',
				'post_status' => 'inherit',
				'meta_key'    => '_spk_seed', // phpcs:ignore WordPress.DB.SlowDBQuery -- one-off setup query.
				'meta_value'  => 'hero', // phpcs:ignore WordPress.DB.SlowDBQuery -- one-off setup query.
				'numberposts' => 1,
				'fields'      => 'ids',
			)
		);
		if ( $found ) {
			return (int) $found[0];
		}

		$source = get_theme_file_path( 'assets/img/1920x1200sunset.jpg' );
		if ( ! is_readable( $source ) ) {
			return new WP_Error( 'spokares_dev_no_photo', 'theme photo assets/img/1920x1200sunset.jpg not found' );
		}
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$tmp = wp_tempnam( '1920x1200sunset.jpg' );
		if ( ! $tmp || ! copy( $source, $tmp ) ) {
			return new WP_Error( 'spokares_dev_copy', 'could not copy the theme photo to a temporary file' );
		}
		$att_id = media_handle_sideload(
			array(
				'name'     => '1920x1200sunset.jpg',
				'tmp_name' => $tmp,
			),
			0
		);
		if ( is_wp_error( $att_id ) ) {
			wp_delete_file( $tmp );
			return $att_id;
		}
		update_post_meta( $att_id, '_wp_attachment_image_alt', '' );
		update_post_meta( $att_id, '_spk_seed', 'hero' );
		return (int) $att_id;
	}
}

// WP-CLI entry point: `wp eval-file pages.php --user=<admin>`.
if ( defined( 'WP_CLI' ) && WP_CLI && ! defined( 'SPOKARES_DEV_IMPORT_LIBRARY' ) ) {
	$spokares_dev_result = spokares_dev_setup_pages();
	foreach ( array_merge( $spokares_dev_result['log'], spokares_dev_remove_sample_content() ) as $spokares_dev_line ) {
		WP_CLI::log( $spokares_dev_line );
	}
	flush_rewrite_rules( false );
	WP_CLI::success( 'Pages set up.' );
}
