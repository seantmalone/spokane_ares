<?php
/**
 * Seed importer for spokares.org (PLAN.md §6.10). NOT shipped with the theme
 * or plugins; it lives in wordpress/dev/seed/ with data.json and extra.json.
 *
 * Writes the round-3 data (data.json, generated from data.js, plus extra.json)
 * through the §6.3 data schema only: the spk_doc_cat terms, spk_document and
 * spk_event posts with their spk_* meta, and the spk_* options.
 *
 * Modes:
 *   dev         everything published, every document privacy-checked, so the
 *               pages match Option B. Refused outside a `local` environment.
 *   production  every document is a Draft with no Privacy tick; events marked
 *               "Needs checking" are Drafts; meetings marked "Needs checking"
 *               are inactive; the alternate repeater is hidden; the workshop's
 *               Home extra is empty; rota rows before today are skipped.
 *
 * Usage:
 *   wp eval-file import.php production      (once, at launch; then setup/pages.php)
 *   wp eval-file import.php dev             (local only)
 *   wp eval-file import.php                 (local only: defaults to dev; refused elsewhere)
 *   From PHP (dev/setup/setup.php): define SPOKARES_DEV_IMPORT_LIBRARY, require
 *   this file, then call spokares_dev_import( 'dev' ).
 *
 * Idempotent: does nothing when the option spk_seeded is set. Items are also
 * looked up by slug before inserting, so a run that stopped half-way can be
 * repeated without duplicates.
 *
 * @package spokares-dev
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'spokares_dev_import' ) ) {

	/**
	 * Run the import.
	 *
	 * @param string $mode 'dev' or 'production'.
	 * @return array{ok:bool,log:string[]} Result and log lines.
	 */
	function spokares_dev_import( string $mode ): array {
		$log = array();

		if ( ! in_array( $mode, array( 'dev', 'production' ), true ) ) {
			return array(
				'ok'  => false,
				'log' => array( 'import: unknown mode "' . $mode . '" (use dev or production)' ),
			);
		}
		if ( 'dev' === $mode && 'local' !== wp_get_environment_type() ) {
			return array(
				'ok'  => false,
				'log' => array( 'import: dev mode publishes every document with the Privacy check ticked; it runs only in a local environment. Use production.' ),
			);
		}
		$seeded = get_option( 'spk_seeded' );
		if ( $seeded ) {
			return array(
				'ok'  => true,
				'log' => array( 'import: already seeded (spk_seeded = ' . $seeded . '); nothing to do' ),
			);
		}
		if ( ! post_type_exists( 'spk_event' ) || ! post_type_exists( 'spk_document' ) || ! taxonomy_exists( 'spk_doc_cat' ) ) {
			return array(
				'ok'  => true,
				'log' => array( 'import: SKIP spokares-core is not active (spk_event / spk_document / spk_doc_cat not registered)' ),
			);
		}

		$data  = spokares_dev_read_json( __DIR__ . '/data.json' );
		$extra = spokares_dev_read_json( __DIR__ . '/extra.json' );
		if ( ! $data || ! $extra ) {
			return array(
				'ok'  => false,
				'log' => array( 'import: data.json or extra.json missing or invalid (run node wordpress/dev/seed/convert.mjs)' ),
			);
		}

		$dev   = ( 'dev' === $mode );
		$today = wp_date( 'Y-m-d' );
		if ( $dev && function_exists( 'spokares_today' ) ) {
			$today = spokares_today(); // SPOKARES_TODAY in dev.
		}
		$log[] = 'import: mode ' . $mode . ', today ' . $today;

		// 1. Library sections (terms), in data order, with spk_order 1-7.
		$order = 0;
		foreach ( $data['documentCategories'] as $cat ) {
			++$order;
			$term = term_exists( $cat['id'], 'spk_doc_cat' );
			if ( ! $term ) {
				$term = wp_insert_term( $cat['label'], 'spk_doc_cat', array( 'slug' => $cat['id'] ) );
			}
			if ( is_wp_error( $term ) ) {
				$log[] = 'import: term ' . $cat['id'] . ' failed: ' . $term->get_error_message();
				continue;
			}
			update_term_meta( (int) $term['term_id'], 'spk_order', $order );
		}
		$log[] = 'import: ' . $order . ' library sections';

		// 2. Documents, in data order; menu_order 10, 20, ... within each section.
		// "Most used" follows data.js mostUsed with the §2.3 swap from
		// extra.json applied (ICS-214 in, ICS-309 out), so the library's Most
		// used filter holds the same forms as the hub tiles.
		$doc_ids     = array();
		$position    = array();
		$most_add    = array_map( 'strval', (array) ( $extra['mostUsed']['add'] ?? array() ) );
		$most_remove = array_map( 'strval', (array) ( $extra['mostUsed']['remove'] ?? array() ) );
		foreach ( $data['documents'] as $d ) {
			$section              = $d['category'];
			$position[ $section ] = ( $position[ $section ] ?? 0 ) + 10;
			$id                   = spokares_dev_upsert_post( 'spk_document', $d['id'], $d['title'], $dev ? 'publish' : 'draft', $position[ $section ] );
			if ( is_wp_error( $id ) ) {
				$log[] = 'import: document ' . $d['id'] . ' failed: ' . $id->get_error_message();
				continue;
			}
			$doc_ids[ $d['id'] ] = $id;
			wp_set_object_terms( $id, $section, 'spk_doc_cat', false );

			$howto = $d['howTo'] ?? ( $d['sid'] ?? null ); // sid: the FEMA Student ID how-to on fema-is-courses.
			$subs  = array();
			foreach ( (array) ( $d['links'] ?? array() ) as $l ) {
				$subs[] = array(
					'id'    => (string) $l['id'],
					'label' => (string) $l['label'],
					'title' => (string) ( $l['title'] ?? '' ),
					'url'   => (string) $l['href'],
				);
			}
			spokares_dev_meta(
				$id,
				array(
					'spk_source'       => empty( $d['href'] ) ? 'soon' : 'link',
					'spk_file'         => '',
					'spk_url'          => (string) ( $d['href'] ?? '' ),
					'spk_source_label' => (string) ( $d['source']['label'] ?? '' ),
					'spk_format'       => (string) ( $d['format'] ?? '' ),
					'spk_version'      => (string) ( $d['version'] ?? '' ),
					'spk_note'         => (string) ( $d['description'] ?? '' ),
					'spk_howto_label'  => (string) ( $howto['label'] ?? '' ),
					'spk_howto_url'    => (string) ( $howto['href'] ?? '' ),
					'spk_sublinks'     => $subs,
					'spk_most_used'    => ( ! empty( $d['mostUsed'] ) || in_array( (string) $d['id'], $most_add, true ) ) && ! in_array( (string) $d['id'], $most_remove, true ),
					'spk_keywords'     => implode( ', ', (array) ( $d['tags'] ?? array() ) ),
					'spk_privacy_ok'   => $dev,
					// Dev publishes with the Privacy check ticked, which the form
					// counts as a review (production: set when each is published).
					'spk_reviewed'     => $dev ? $today : '',
					'spk_needs_check'  => ! empty( $d['verify'] ),
				)
			);
		}
		$log[] = 'import: ' . count( $doc_ids ) . ' documents (' . ( $dev ? 'published, privacy-checked' : 'Draft, no Privacy tick' ) . ')';

		// 3. Events: the dated round-3 events, then the undated public-service rows.
		$overrides = (array) ( $extra['eventOverrides'] ?? array() );
		$keep_past = (array) ( $extra['keepPast']['ids'] ?? array() );
		$events    = array_merge( (array) $data['events'], (array) ( $extra['events'] ?? array() ) );
		$counts    = array(
			'publish' => 0,
			'draft'   => 0,
		);
		foreach ( $events as $e ) {
			$over       = (array) ( $overrides[ $e['id'] ] ?? array() );
			$needs      = ! empty( $e['verify'] ) || ! empty( $e['detailVerify'] );
			$status     = ( $dev || ! $needs ) ? 'publish' : 'draft';
			$mode_field = (string) ( $e['dateMode'] ?? 'date' );
			$id         = spokares_dev_upsert_post( 'spk_event', $e['id'], $e['title'], $status, (int) ( $e['menuOrder'] ?? 0 ) );
			if ( is_wp_error( $id ) ) {
				$log[] = 'import: event ' . $e['id'] . ' failed: ' . $id->get_error_message();
				continue;
			}
			++$counts[ $status ];
			$links = array();
			foreach ( (array) ( $e['links'] ?? array() ) as $l ) {
				$links[] = array(
					'label' => (string) $l['label'],
					'url'   => (string) $l['href'],
				);
			}
			$main_url   = (string) ( $e['detailsHref'] ?? '' );
			$main_label = '';
			if ( '' !== $main_url ) {
				$main_label = (string) ( $over['mainLabel'] ?? ( str_contains( $main_url, 'groups.io' ) ? 'Exercise details on groups.io' : 'Details' ) );
			}
			$all_day = ! empty( $e['allDay'] ) || empty( $e['time'] );
			spokares_dev_meta(
				$id,
				array(
					'spk_kind'         => (string) $e['type'],
					'spk_date_mode'    => $mode_field,
					'spk_start'        => 'date' === $mode_field ? (string) ( $e['start'] ?? '' ) : '',
					'spk_end'          => 'date' === $mode_field ? (string) ( $e['end'] ?? '' ) : '',
					'spk_time_start'   => $all_day ? '' : (string) $e['time'],
					'spk_time_end'     => $all_day ? '' : (string) ( $e['endTime'] ?? '' ),
					'spk_summary'      => (string) ( $over['summary'] ?? ( $e['summary'] ?? '' ) ),
					'spk_tasks'        => implode( "\n", (array) ( $e['tasks'] ?? array() ) ),
					'spk_main_url'     => $main_url,
					'spk_main_label'   => $main_label,
					'spk_links'        => $links,
					'spk_extra_doc'    => (int) ( $doc_ids[ $over['extraForm'] ?? ( $e['extraForm'] ?? '' ) ] ?? 0 ),
					'spk_contact_call' => (string) ( $e['contact']['callsign'] ?? '' ),
					'spk_keep_past'    => in_array( $e['id'], $keep_past, true ),
					'spk_precision'    => 'day',
					'spk_needs_check'  => $needs,
				)
			);
			spokares_dev_event_sort( $id );
		}

		// Past exercises: published exercise events kept in "Past exercises".
		foreach ( (array) $data['pastExercises'] as $p ) {
			$is_year = ( 4 === strlen( (string) $p['date'] ) );
			$start   = $is_year ? $p['date'] . '-01-01' : $p['date'] . '-01';
			$slug    = 'past-' . $p['date'] . '-' . sanitize_title( $p['title'] );
			$needs   = ! empty( $p['verify'] );
			$status  = ( $dev || ! $needs ) ? 'publish' : 'draft';
			$id      = spokares_dev_upsert_post( 'spk_event', $slug, $p['title'], $status, 0 );
			if ( is_wp_error( $id ) ) {
				$log[] = 'import: past exercise ' . $slug . ' failed: ' . $id->get_error_message();
				continue;
			}
			++$counts[ $status ];
			spokares_dev_meta(
				$id,
				array(
					'spk_kind'        => 'exercise',
					'spk_date_mode'   => 'date',
					'spk_start'       => $start,
					'spk_end'         => '',
					'spk_keep_past'   => true,
					'spk_precision'   => $is_year ? 'year' : 'month',
					'spk_needs_check' => $needs,
				)
			);
			spokares_dev_event_sort( $id );
		}
		$log[] = 'import: events ' . $counts['publish'] . ' published, ' . $counts['draft'] . ' Draft (' . count( $data['events'] ) . ' dated, ' . count( (array) ( $extra['events'] ?? array() ) ) . ' undated public service, ' . count( $data['pastExercises'] ) . ' past)';

		// 4. Options (§6.3 shapes; the plugin's register_setting sanitisers normalise them).
		$nets = array();
		foreach ( (array) $data['nets'] as $n ) {
			$nets[ $n['id'] ] = $n;
		}
		$nth = static fn( $rule ) => array_values( array_map( 'intval', (array) ( $rule['nth'] ?? array() ) ) );
		update_option(
			'spk_nets',
			array(
				'net_time'       => (string) ( $nets['weekly']['start'] ?? '20:00' ),
				'winlink_nth'    => $nth( $nets['winlink-nights']['rule'] ?? array() ),
				'simplex_nth'    => $nth( $nets['simplex-5th']['rule'] ?? array() ),
				'gmrs_nth'       => $nth( $nets['gmrs']['rule'] ?? array() ),
				'gmrs_time'      => (string) ( $nets['gmrs']['start'] ?? '' ),
				'winlink_howto'  => (string) $data['winlink']['howTo'],
				'open_slot_line' => (string) $data['rotaMeta']['openSlotAction'],
				'needs_check'    => ! empty( $nets['winlink-nights']['verify'] ) || ! empty( $nets['simplex-5th']['verify'] ) || ! empty( $nets['gmrs']['verify'] ),
			)
		);

		$radio = $data['radio'];
		update_option(
			'spk_radio',
			array(
				'primary'   => array(
					'call'   => (string) $radio['primary']['call'],
					'freq'   => (string) $radio['primary']['freq'],
					'offset' => (string) $radio['primary']['offset'],
					'tone'   => (string) $radio['primary']['tone'],
				),
				'alternate' => array(
					'freq'        => (string) $radio['alternate']['freq'],
					'offset'      => (string) $radio['alternate']['offset'],
					'tone'        => (string) $radio['alternate']['tone'],
					'show'        => $dev,
					'needs_check' => ! empty( $radio['alternate']['verify'] ),
				),
			)
		);

		$meeting_extra = (array) ( $extra['meetings'] ?? array() );
		$meetings      = array();
		foreach ( (array) $data['meetings'] as $m ) {
			$mx         = (array) ( $meeting_extra[ $m['id'] ] ?? array() );
			$needs      = ! empty( $m['verify'] );
			$meetings[] = array(
				'id'          => (string) $m['id'],
				'name'        => (string) $m['name'],
				'nth'         => $nth( $m['rule'] ),
				'weekday'     => (int) $m['rule']['weekday'],
				'start'       => (string) ( $m['start'] ?? '' ),
				'end'         => (string) ( $m['end'] ?? '' ),
				'time_text'   => (string) ( $mx['time_text'] ?? '' ),
				'home_extra'  => $dev ? (string) ( $mx['home_extra'] ?? '' ) : '',
				'skip_months' => array_values( array_map( 'intval', (array) ( $m['skipMonths'] ?? array() ) ) ),
				'show_home'   => ! empty( $mx['show_home'] ),
				'active'      => $dev || ! $needs,
				'needs_check' => $needs,
			);
		}
		update_option(
			'spk_meetings',
			array(
				'meetings' => $meetings,
				'changes'  => array(),
			)
		);

		$place = $data['org']['location'];
		update_option(
			'spk_site',
			array(
				'place' => array(
					'name'        => (string) $place['name'],
					'street'      => (string) $place['street'],
					'city'        => (string) $place['city'],
					'state'       => (string) $place['state'],
					'zip'         => (string) $place['zip'],
					'needs_check' => ! empty( $place['verify'] ),
				),
			)
		);

		// Rota: net control from `rota`, merged with the Winlink assignments.
		$blank = array(
			'state'   => 'tbd',
			'call'    => '',
			'note'    => '',
			'wl_task' => '',
			'wl_form' => '',
		);
		$rota  = array();
		foreach ( (array) $data['rota'] as $r ) {
			$row = $blank;
			if ( ! empty( $r['callsign'] ) ) {
				$row['state'] = 'call';
				$row['call']  = strtoupper( (string) $r['callsign'] );
			} elseif ( ! empty( $r['open'] ) ) {
				$row['state'] = 'open';
			}
			$rota[ $r['date'] ] = $row;
		}
		foreach ( (array) $data['winlink']['assignments'] as $a ) {
			$row            = $rota[ $a['date'] ] ?? $blank;
			$row['wl_task'] = (string) $a['task'];
			$row['wl_form'] = (string) $a['form'];

			$rota[ $a['date'] ] = $row;
		}
		$skipped = 0;
		if ( ! $dev ) {
			foreach ( array_keys( $rota ) as $ymd ) {
				if ( $ymd < $today ) {
					unset( $rota[ $ymd ] );
					++$skipped;
				}
			}
		}
		ksort( $rota );
		update_option( 'spk_rota', $rota );

		// Hub tiles: four slots from extra.json slugs.
		$tiles = array();
		foreach ( (array) ( $extra['tiles']['slots'] ?? array() ) as $slot ) {
			$tiles[] = array(
				'doc'   => (int) ( $doc_ids[ $slot['doc'] ] ?? 0 ),
				'label' => (string) $slot['label'],
				'icon'  => (string) $slot['icon'],
			);
		}
		update_option( 'spk_tiles', $tiles );

		$log[] = 'import: options spk_nets, spk_radio, spk_meetings (' . count( $meetings ) . '), spk_site, spk_rota (' . count( $rota ) . ' rows' . ( $skipped ? ', ' . $skipped . ' past rows skipped' : '' ) . '), spk_tiles (' . count( array_filter( wp_list_pluck( $tiles, 'doc' ) ) ) . ' of 4 set)';

		update_option( 'spk_db_version', '1' );
		update_option( 'spk_seeded', wp_date( 'Y-m-d' ) );
		if ( function_exists( 'spokares_purge_cache' ) ) {
			spokares_purge_cache();
		}
		$log[] = 'import: done (spk_seeded = ' . get_option( 'spk_seeded' ) . ')';

		return array(
			'ok'  => true,
			'log' => $log,
		);
	}

	/**
	 * Read a JSON file into an array.
	 *
	 * @param string $path File.
	 * @return array|null
	 */
	function spokares_dev_read_json( string $path ): ?array {
		if ( ! is_readable( $path ) ) {
			return null;
		}
		$json = json_decode( (string) file_get_contents( $path ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions -- local file.
		return is_array( $json ) ? $json : null;
	}

	/**
	 * Find a post of a type by slug, or insert it. An existing post keeps its
	 * status (a person may have published or trashed it since).
	 *
	 * @param string $type       Post type.
	 * @param string $slug       Slug (post_name).
	 * @param string $title      Title.
	 * @param string $status     Status for a new post.
	 * @param int    $menu_order Menu order.
	 * @return int|WP_Error
	 */
	function spokares_dev_upsert_post( string $type, string $slug, string $title, string $status, int $menu_order ) {
		$existing = get_posts(
			array(
				'post_type'        => $type,
				'name'             => $slug,
				'post_status'      => 'any',
				'numberposts'      => 1,
				'fields'           => 'ids',
				'suppress_filters' => true,
			)
		);
		if ( $existing ) {
			return (int) $existing[0];
		}
		return wp_insert_post(
			wp_slash(
				array(
					'post_type'   => $type,
					'post_status' => $status,
					'post_title'  => $title,
					'post_name'   => $slug,
					'menu_order'  => $menu_order,
				)
			),
			true
		);
	}

	/**
	 * Write meta by the §6.3 storage rules: true -> '1'; false, '' and empty
	 * arrays are deleted; everything else is stored as given.
	 *
	 * @param int   $post_id Post.
	 * @param array $meta    Key => value.
	 */
	function spokares_dev_meta( int $post_id, array $meta ): void {
		foreach ( $meta as $key => $value ) {
			if ( true === $value ) {
				$value = '1';
			}
			if ( false === $value || '' === $value || 0 === $value || array() === $value || null === $value ) {
				delete_post_meta( $post_id, $key );
				continue;
			}
			update_post_meta( $post_id, $key, $value );
		}
	}

	/**
	 * Event sort keys (§6.3). The plugin recomputes them on any date write;
	 * writing them here keeps the importer right on its own.
	 *
	 * @param int $post_id Event.
	 */
	function spokares_dev_event_sort( int $post_id ): void {
		$mode  = (string) get_post_meta( $post_id, 'spk_date_mode', true );
		$start = (string) get_post_meta( $post_id, 'spk_start', true );
		$end   = (string) get_post_meta( $post_id, 'spk_end', true );
		$dated = ( '' === $mode || 'date' === $mode ) && '' !== $start;
		update_post_meta( $post_id, 'spk_sort', $dated ? $start : '9999-12-31' );
		update_post_meta( $post_id, 'spk_end_sort', $dated ? ( '' !== $end ? $end : $start ) : '9999-12-31' );
	}
}

// WP-CLI entry point: `wp eval-file import.php [dev|production]` ($args comes from eval-file).
if ( defined( 'WP_CLI' ) && WP_CLI && ! defined( 'SPOKARES_DEV_IMPORT_LIBRARY' ) ) {
	$spokares_dev_mode = ( isset( $args ) && is_array( $args ) && isset( $args[0] ) ) ? (string) $args[0] : '';
	if ( '' === $spokares_dev_mode ) {
		if ( 'local' !== wp_get_environment_type() ) {
			WP_CLI::error( 'Refusing to run without a mode outside a local environment. Use: wp eval-file import.php production' );
		}
		$spokares_dev_mode = 'dev';
	}
	$spokares_dev_result = spokares_dev_import( $spokares_dev_mode );
	foreach ( $spokares_dev_result['log'] as $spokares_dev_line ) {
		WP_CLI::log( $spokares_dev_line );
	}
	if ( ! $spokares_dev_result['ok'] ) {
		WP_CLI::error( 'Import failed.' );
	}
	WP_CLI::success( 'Import finished (' . $spokares_dev_mode . ').' );
}
