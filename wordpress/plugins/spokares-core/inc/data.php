<?php
/**
 * Data model (§3.2, §6.3): post types spk_event and spk_document, taxonomy
 * spk_doc_cat, their meta, page meta, and the option sanitisers.
 *
 * @package spokares-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * The seven library sections, in order (slug => label).
 */
function spokares_doc_sections(): array {
	return array(
		'net-ops'   => __( 'Net operations', 'spokares-core' ),
		'join'      => __( 'Join & task books', 'spokares-core' ),
		'forms'     => __( 'Forms', 'spokares-core' ),
		'training'  => __( 'Training', 'spokares-core' ),
		'readiness' => __( 'Go-kits & readiness', 'spokares-core' ),
		'digital'   => __( 'Winlink & digital', 'spokares-core' ),
		'reference' => __( 'Reference', 'spokares-core' ),
	);
}

/**
 * Event kinds (value => label).
 */
function spokares_event_kinds(): array {
	return array(
		'exercise'       => __( 'Exercise', 'spokares-core' ),
		'training'       => __( 'Training', 'spokares-core' ),
		'on-air'         => __( 'On the air', 'spokares-core' ),
		'public-service' => __( 'Public service', 'spokares-core' ),
	);
}

/**
 * Register the post types, taxonomy and meta. Called on init and on activation.
 */
function spokares_register_data_model(): void {
	register_post_type(
		'spk_event',
		array(
			'labels'              => array(
				'name'                   => __( 'Events', 'spokares-core' ),
				'singular_name'          => __( 'Event', 'spokares-core' ),
				'menu_name'              => __( 'Events', 'spokares-core' ),
				'all_items'              => __( 'All events', 'spokares-core' ),
				'add_new'                => __( 'Add event', 'spokares-core' ),
				'add_new_item'           => __( 'Add event', 'spokares-core' ),
				'edit_item'              => __( 'Edit event', 'spokares-core' ),
				'new_item'               => __( 'New event', 'spokares-core' ),
				'view_item'              => __( 'View event', 'spokares-core' ),
				'search_items'           => __( 'Search events', 'spokares-core' ),
				'not_found'              => __( 'No events found.', 'spokares-core' ),
				'not_found_in_trash'     => __( 'No events in the trash.', 'spokares-core' ),
				'item_published'         => __( 'Event published.', 'spokares-core' ),
				'item_updated'           => __( 'Event updated.', 'spokares-core' ),
				'item_reverted_to_draft' => __( 'Event saved as a draft.', 'spokares-core' ),
			),
			'description'         => __( 'Exercises, training, on-air and public-service events for the members pages.', 'spokares-core' ),
			'public'              => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_nav_menus'   => false,
			'show_in_admin_bar'   => false,
			'show_in_rest'        => false,
			'menu_position'       => 4,
			'menu_icon'           => 'dashicons-calendar-alt',
			'capability_type'     => array( 'spk_event', 'spk_events' ),
			'map_meta_cap'        => true,
			'hierarchical'        => false,
			'supports'            => array( 'title' ),
			'has_archive'         => false,
			'rewrite'             => false,
			'query_var'           => false,
			'can_export'          => true,
			'delete_with_user'    => false,
		)
	);

	register_post_type(
		'spk_document',
		array(
			'labels'              => array(
				'name'               => __( 'Documents', 'spokares-core' ),
				'singular_name'      => __( 'Document', 'spokares-core' ),
				'menu_name'          => __( 'Documents', 'spokares-core' ),
				'all_items'          => __( 'All documents', 'spokares-core' ),
				'add_new'            => __( 'Add document', 'spokares-core' ),
				'add_new_item'       => __( 'Add document', 'spokares-core' ),
				'edit_item'          => __( 'Edit document', 'spokares-core' ),
				'new_item'           => __( 'New document', 'spokares-core' ),
				'view_item'          => __( 'View document', 'spokares-core' ),
				'search_items'       => __( 'Search documents', 'spokares-core' ),
				'not_found'          => __( 'No documents found.', 'spokares-core' ),
				'not_found_in_trash' => __( 'No documents in the trash.', 'spokares-core' ),
			),
			'description'         => __( 'The members document library.', 'spokares-core' ),
			'public'              => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_nav_menus'   => false,
			'show_in_admin_bar'   => false,
			'show_in_rest'        => false,
			'menu_position'       => 5,
			'menu_icon'           => 'dashicons-media-document',
			'capability_type'     => array( 'spk_document', 'spk_documents' ),
			'map_meta_cap'        => true,
			'hierarchical'        => false,
			'supports'            => array( 'title', 'page-attributes' ),
			'has_archive'         => false,
			'rewrite'             => false,
			'query_var'           => false,
			'can_export'          => true,
			'delete_with_user'    => false,
		)
	);

	register_taxonomy(
		'spk_doc_cat',
		array( 'spk_document' ),
		array(
			'labels'             => array(
				'name'          => __( 'Library sections', 'spokares-core' ),
				'singular_name' => __( 'Library section', 'spokares-core' ),
				'menu_name'     => __( 'Sections', 'spokares-core' ),
				'all_items'     => __( 'All sections', 'spokares-core' ),
				'edit_item'     => __( 'Edit section', 'spokares-core' ),
				'add_new_item'  => __( 'Add section', 'spokares-core' ),
				'search_items'  => __( 'Search sections', 'spokares-core' ),
				'not_found'     => __( 'No sections.', 'spokares-core' ),
				'no_terms'      => __( 'No section', 'spokares-core' ),
			),
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'show_in_nav_menus'  => false,
			'show_in_rest'       => false,
			'show_tagcloud'      => false,
			'show_in_quick_edit' => false,
			'show_admin_column'  => true,
			'hierarchical'       => false,
			'rewrite'            => false,
			'query_var'          => false,
			// The section is chosen with radios inside the document form (admin-documents.php).
			'meta_box_cb'        => false,
			'capabilities'       => array(
				'manage_terms' => 'manage_options',
				'edit_terms'   => 'manage_options',
				'delete_terms' => 'manage_options',
				'assign_terms' => 'edit_spk_documents',
			),
		)
	);

	register_term_meta(
		'spk_doc_cat',
		'spk_order',
		array(
			'type'              => 'integer',
			'single'            => true,
			'default'           => 0,
			'sanitize_callback' => 'absint',
			'auth_callback'     => static fn() => current_user_can( 'manage_options' ),
			'show_in_rest'      => false,
		)
	);

	spokares_register_post_meta();
}
add_action( 'init', 'spokares_register_data_model', 5 );

/**
 * Meta for events, documents and pages. show_in_rest is false everywhere (§6.3);
 * our own handlers still check capabilities and nonces themselves.
 */
function spokares_register_post_meta(): void {
	$text     = 'sanitize_text_field';
	$textarea = 'sanitize_textarea_field';
	$date     = 'spokares_sanitize_meta_date';
	$time     = 'spokares_sanitize_meta_time';
	$bool     = 'spokares_sanitize_meta_bool';
	$url      = 'spokares_sanitize_meta_url';
	$array    = 'spokares_sanitize_meta_array';

	$event    = array(
		'spk_kind'         => array( 'string', $text ),
		'spk_date_mode'    => array( 'string', $text ),
		'spk_start'        => array( 'string', $date ),
		'spk_end'          => array( 'string', $date ),
		'spk_time_start'   => array( 'string', $time ),
		'spk_time_end'     => array( 'string', $time ),
		'spk_summary'      => array( 'string', $text ),
		'spk_where'        => array( 'string', $text ),
		'spk_tasks'        => array( 'string', $textarea ),
		'spk_main_url'     => array( 'string', $url ),
		'spk_main_label'   => array( 'string', $text ),
		'spk_links'        => array( 'array', $array ),
		'spk_extra_doc'    => array( 'integer', 'absint' ),
		'spk_contact_call' => array( 'string', $text ),
		'spk_keep_past'    => array( 'string', $bool ),
		'spk_precision'    => array( 'string', $text ),
		'spk_needs_check'  => array( 'string', $bool ),
		'spk_confirmed'    => array( 'array', $array ),
		'spk_sort'         => array( 'string', $date ),
		'spk_end_sort'     => array( 'string', $date ),
	);
	$document = array(
		'spk_source'       => array( 'string', $text ),
		'spk_file'         => array( 'integer', 'absint' ),
		'spk_url'          => array( 'string', $url ),
		'spk_source_label' => array( 'string', $text ),
		'spk_format'       => array( 'string', $text ),
		'spk_version'      => array( 'string', $text ),
		'spk_note'         => array( 'string', $text ),
		'spk_howto_label'  => array( 'string', $text ),
		'spk_howto_url'    => array( 'string', $url ),
		'spk_sublinks'     => array( 'array', $array ),
		'spk_most_used'    => array( 'string', $bool ),
		'spk_keywords'     => array( 'string', $text ),
		'spk_owner'        => array( 'string', $text ),
		'spk_reviewed'     => array( 'string', $date ),
		'spk_privacy_ok'   => array( 'string', $bool ),
		'spk_needs_check'  => array( 'string', $bool ),
		'spk_confirmed'    => array( 'array', $array ),
		'spk_pulled'       => array( 'array', $array ),
	);

	foreach ( array(
		'spk_event'    => $event,
		'spk_document' => $document,
	) as $type => $keys ) {
		foreach ( $keys as $key => [ $kind, $sanitize ] ) {
			register_post_meta(
				$type,
				$key,
				array(
					'type'              => $kind,
					'single'            => true,
					'sanitize_callback' => $sanitize,
					'auth_callback'     => 'spokares_meta_auth',
					'show_in_rest'      => false,
				)
			);
		}
	}

	foreach ( array(
		'_spk_reviewed' => $date,
		'_spk_owner'    => $text,
	) as $key => $sanitize ) {
		register_post_meta(
			'page',
			$key,
			array(
				'type'              => 'string',
				'single'            => true,
				'sanitize_callback' => $sanitize,
				'auth_callback'     => 'spokares_meta_auth',
				'show_in_rest'      => false,
			)
		);
	}

	register_post_meta(
		'attachment',
		'_spk_seed',
		array(
			'type'              => 'string',
			'single'            => true,
			'sanitize_callback' => $text,
			'auth_callback'     => static fn() => current_user_can( 'manage_options' ),
			'show_in_rest'      => false,
		)
	);
}

/**
 * Meta auth: whoever may edit the post.
 *
 * @param bool   $allowed  Unused.
 * @param string $meta_key Unused.
 * @param int    $post_id  Post.
 */
function spokares_meta_auth( $allowed, $meta_key, $post_id ): bool {
	return current_user_can( 'edit_post', (int) $post_id );
}

/**
 * Sanitise a stored date.
 *
 * @param mixed $v Value.
 */
function spokares_sanitize_meta_date( $v ): string {
	$v = is_string( $v ) ? trim( $v ) : '';
	return spokares_is_ymd( $v ) ? $v : '';
}

/**
 * Sanitise a stored time.
 *
 * @param mixed $v Value.
 */
function spokares_sanitize_meta_time( $v ): string {
	$v = is_string( $v ) ? trim( $v ) : '';
	return spokares_is_hhmm( $v ) ? $v : '';
}

/**
 * Sanitise a stored boolean ('1' or '').
 *
 * @param mixed $v Value.
 */
function spokares_sanitize_meta_bool( $v ): string {
	return ( true === $v || '1' === $v || 1 === $v ) ? '1' : '';
}

/**
 * Sanitise a stored URL. Drafts may hold typed text that failed the check
 * (never thrown away, §4.4), so plain text is kept when it isn't a URL.
 *
 * @param mixed $v Value.
 */
function spokares_sanitize_meta_url( $v ): string {
	$v = is_string( $v ) ? trim( $v ) : '';
	if ( '' === $v ) {
		return '';
	}
	$clean = spokares_clean_url( $v, true );
	return '' !== $clean ? $clean : sanitize_text_field( $v );
}

/**
 * Sanitise an array of string maps (links, sub-links, confirmations).
 *
 * @param mixed $v Value.
 */
function spokares_sanitize_meta_array( $v ): array {
	if ( ! is_array( $v ) ) {
		return array();
	}
	$out = array();
	foreach ( $v as $k => $item ) {
		$key = is_int( $k ) ? $k : sanitize_key( $k );
		if ( is_array( $item ) ) {
			$clean = array();
			foreach ( $item as $ik => $iv ) {
				$clean[ sanitize_key( $ik ) ] = 'url' === $ik ? spokares_sanitize_meta_url( $iv ) : sanitize_text_field( (string) $iv );
			}
			$out[ $key ] = $clean;
		} else {
			$out[ $key ] = sanitize_text_field( (string) $item );
		}
	}
	return $out;
}

/**
 * Compute the sort keys of an event from its dates (§6.3).
 *
 * @param int $post_id Event.
 */
function spokares_update_event_sort( int $post_id ): void {
	$ev   = spokares_event_data( $post_id );
	$sort = 'date' === ( $ev['mode'] ?? '' ) ? $ev['start'] : '9999-12-31';
	$end  = 'date' === ( $ev['mode'] ?? '' ) ? ( '' !== $ev['end'] ? $ev['end'] : $ev['start'] ) : '9999-12-31';
	update_post_meta( $post_id, 'spk_sort', $sort );
	update_post_meta( $post_id, 'spk_end_sort', $end );
}

/**
 * Keep an event's sort keys right whatever wrote its dates (the form, the
 * importer or WP-CLI). Runs after the meta is written.
 *
 * @param int    $meta_id  Unused.
 * @param int    $post_id  Post.
 * @param string $meta_key Key.
 */
function spokares_event_meta_changed( $meta_id, $post_id, $meta_key ): void {
	static $busy = false;
	if ( $busy || ! in_array( $meta_key, array( 'spk_start', 'spk_end', 'spk_date_mode' ), true ) ) {
		return;
	}
	if ( 'spk_event' !== get_post_type( (int) $post_id ) ) {
		return;
	}
	$busy = true;
	spokares_update_event_sort( (int) $post_id );
	$busy = false;
}
add_action( 'added_post_meta', 'spokares_event_meta_changed', 10, 3 );
add_action( 'updated_post_meta', 'spokares_event_meta_changed', 10, 3 );
add_action( 'deleted_post_meta', 'spokares_event_meta_changed', 10, 3 );

/**
 * Register the options as settings with shape-normalising sanitisers
 * (research §9.2 rule 10). The screens themselves post to admin-post.php.
 */
function spokares_register_settings(): void {
	$map = array(
		'spk_nets'     => 'spokares_sanitize_opt_nets',
		'spk_radio'    => 'spokares_sanitize_opt_radio',
		'spk_meetings' => 'spokares_sanitize_opt_meetings',
		'spk_site'     => 'spokares_sanitize_opt_site',
		'spk_tiles'    => 'spokares_sanitize_opt_tiles',
		'spk_rota'     => 'spokares_sanitize_opt_rota',
	);
	foreach ( $map as $option => $cb ) {
		register_setting(
			'spokares',
			$option,
			array(
				'type'              => 'array',
				'sanitize_callback' => $cb,
				'show_in_rest'      => false,
			)
		);
	}
}
add_action( 'init', 'spokares_register_settings' );

/**
 * Plain-text sanitiser for nested option values.
 *
 * @param mixed $v Value.
 */
function spokares_clean_str( $v ): string {
	return is_scalar( $v ) ? sanitize_text_field( (string) $v ) : '';
}

/**
 * Normalise spk_nets.
 *
 * @param mixed $v Value.
 */
function spokares_sanitize_opt_nets( $v ): array {
	$v   = is_array( $v ) ? $v : array();
	$def = spokares_option_defaults()['spk_nets'];
	$out = array();
	foreach ( array( 'winlink_nth', 'simplex_nth', 'gmrs_nth' ) as $k ) {
		$out[ $k ] = array_values( array_unique( array_filter( array_map( 'intval', (array) ( $v[ $k ] ?? $def[ $k ] ) ), static fn( $n ) => $n >= 1 && $n <= 5 ) ) );
	}
	$out['net_time']       = spokares_is_hhmm( $v['net_time'] ?? '' ) ? $v['net_time'] : $def['net_time'];
	$out['gmrs_time']      = spokares_is_hhmm( $v['gmrs_time'] ?? '' ) ? $v['gmrs_time'] : '';
	$out['winlink_howto']  = spokares_clean_str( $v['winlink_howto'] ?? '' );
	$out['open_slot_line'] = spokares_clean_str( $v['open_slot_line'] ?? '' );
	$out['needs_check']    = ! empty( $v['needs_check'] );
	if ( ! empty( $v['confirmed'] ) && is_array( $v['confirmed'] ) ) {
		$out['confirmed'] = array_map( 'spokares_clean_str', $v['confirmed'] );
	}
	return $out;
}

/**
 * Normalise spk_radio.
 *
 * @param mixed $v Value.
 */
function spokares_sanitize_opt_radio( $v ): array {
	$v = is_array( $v ) ? $v : array();
	$p = is_array( $v['primary'] ?? null ) ? $v['primary'] : array();
	$a = is_array( $v['alternate'] ?? null ) ? $v['alternate'] : array();
	return array(
		'primary'   => array(
			'call'   => strtoupper( spokares_clean_str( $p['call'] ?? '' ) ),
			'freq'   => preg_match( '/^\d{2,3}\.\d{3}$/', (string) ( $p['freq'] ?? '' ) ) ? (string) $p['freq'] : '',
			'offset' => spokares_clean_str( $p['offset'] ?? '' ),
			'tone'   => spokares_clean_str( $p['tone'] ?? '' ),
		),
		'alternate' => array(
			'freq'        => preg_match( '/^\d{2,3}\.\d{3}$/', (string) ( $a['freq'] ?? '' ) ) ? (string) $a['freq'] : '',
			'offset'      => spokares_clean_str( $a['offset'] ?? '' ),
			'tone'        => spokares_clean_str( $a['tone'] ?? '' ),
			'show'        => ! empty( $a['show'] ),
			'needs_check' => ! empty( $a['needs_check'] ),
		),
	);
}

/**
 * Normalise spk_meetings.
 *
 * @param mixed $v Value.
 */
function spokares_sanitize_opt_meetings( $v ): array {
	$v        = is_array( $v ) ? $v : array();
	$meetings = array();
	foreach ( (array) ( $v['meetings'] ?? array() ) as $m ) {
		if ( ! is_array( $m ) || empty( $m['id'] ) ) {
			continue;
		}
		$m               = spokares_normalize_meeting( $m );
		$m['id']         = sanitize_key( $m['id'] );
		$m['name']       = spokares_clean_str( $m['name'] );
		$m['time_text']  = spokares_clean_str( $m['time_text'] );
		$m['home_extra'] = spokares_clean_str( $m['home_extra'] );
		$meetings[]      = $m;
	}
	$changes = array();
	foreach ( (array) ( $v['changes'] ?? array() ) as $c ) {
		if ( ! is_array( $c ) || ! spokares_is_ymd( $c['date'] ?? '' ) || empty( $c['meeting'] ) ) {
			continue;
		}
		$kind      = in_array( $c['kind'] ?? '', array( 'cancelled', 'moved' ), true ) ? $c['kind'] : 'cancelled';
		$changes[] = array(
			'date'     => $c['date'],
			'meeting'  => sanitize_key( $c['meeting'] ),
			'kind'     => $kind,
			'new_date' => 'moved' === $kind && spokares_is_ymd( $c['new_date'] ?? '' ) ? $c['new_date'] : '',
			'note'     => spokares_clean_str( $c['note'] ?? '' ),
		);
	}
	$out = array(
		'meetings' => $meetings,
		'changes'  => $changes,
	);
	if ( ! empty( $v['confirmed'] ) && is_array( $v['confirmed'] ) ) {
		$out['confirmed'] = array_map( 'spokares_clean_str', $v['confirmed'] );
	}
	return $out;
}

/**
 * Normalise spk_site.
 *
 * @param mixed $v Value.
 */
function spokares_sanitize_opt_site( $v ): array {
	$v = is_array( $v ) ? $v : array();
	$p = is_array( $v['place'] ?? null ) ? $v['place'] : array();
	// Only the meeting place: older keys (the groups.io addresses) are dropped.
	return array(
		'place' => array(
			'name'        => spokares_clean_str( $p['name'] ?? '' ),
			'street'      => spokares_clean_str( $p['street'] ?? '' ),
			'city'        => spokares_clean_str( $p['city'] ?? '' ),
			'state'       => spokares_clean_str( $p['state'] ?? '' ),
			'zip'         => spokares_clean_str( $p['zip'] ?? '' ),
			'needs_check' => ! empty( $p['needs_check'] ),
		),
	);
}

/**
 * Normalise spk_tiles (exactly four slots, plus an optional confirmed map).
 *
 * @param mixed $v Value.
 */
function spokares_sanitize_opt_tiles( $v ): array {
	$v   = is_array( $v ) ? $v : array();
	$out = array();
	for ( $i = 0; $i < 4; $i++ ) {
		$slot      = is_array( $v[ $i ] ?? null ) ? $v[ $i ] : array();
		$out[ $i ] = array(
			'doc'   => absint( $slot['doc'] ?? 0 ),
			'label' => spokares_clean_str( $slot['label'] ?? '' ),
			'icon'  => in_array( $slot['icon'] ?? '', array( 'script', 'form', 'log', 'book' ), true ) ? $slot['icon'] : 'form',
		);
	}
	if ( ! empty( $v['confirmed'] ) && is_array( $v['confirmed'] ) ) {
		$out['confirmed'] = array_map( 'spokares_clean_str', $v['confirmed'] );
	}
	return $out;
}

/**
 * Normalise spk_rota (keyed by Tuesday date).
 *
 * @param mixed $v Value.
 */
function spokares_sanitize_opt_rota( $v ): array {
	$v   = is_array( $v ) ? $v : array();
	$out = array();
	foreach ( $v as $ymd => $row ) {
		if ( ! spokares_is_ymd( (string) $ymd ) || ! is_array( $row ) ) {
			continue;
		}
		$norm  = spokares_normalize_rota_row( $row );
		$clean = array( 'state' => $norm['state'] );
		foreach ( array( 'call', 'note', 'wl_task', 'wl_form' ) as $k ) {
			$clean[ $k ] = spokares_clean_str( $norm[ $k ] );
		}
		$clean['call'] = strtoupper( $clean['call'] );
		if ( ! empty( $row['confirmed'] ) && is_array( $row['confirmed'] ) ) {
			$clean['confirmed'] = array_map( 'spokares_clean_str', $row['confirmed'] );
		}
		$out[ (string) $ymd ] = $clean;
	}
	ksort( $out );
	return $out;
}

/**
 * Purge on any change to the lists' options.
 */
foreach ( array( 'spk_rota', 'spk_nets', 'spk_radio', 'spk_meetings', 'spk_site', 'spk_tiles' ) as $spokares_opt ) {
	add_action( 'update_option_' . $spokares_opt, 'spokares_purge_cache' );
	add_action( 'add_option_' . $spokares_opt, 'spokares_purge_cache' );
}
unset( $spokares_opt );

/**
 * Purge when an event or document changes status or content.
 *
 * @param int     $post_id Post.
 * @param WP_Post $post    Post.
 */
function spokares_purge_on_save( $post_id, $post ): void {
	if ( $post instanceof WP_Post && in_array( $post->post_type, array( 'spk_event', 'spk_document' ), true ) ) {
		spokares_purge_cache();
	}
}
add_action( 'save_post', 'spokares_purge_on_save', 99, 2 );
add_action( 'deleted_post', 'spokares_purge_on_save', 99, 2 );
add_action( 'trashed_post', static fn( $id ) => spokares_purge_on_save( $id, get_post( $id ) ), 99 );

/**
 * Comments off everywhere: supports removed from posts and pages.
 */
function spokares_no_comments(): void {
	foreach ( array( 'post', 'page', 'attachment' ) as $type ) {
		remove_post_type_support( $type, 'comments' );
		remove_post_type_support( $type, 'trackbacks' );
	}
}
add_action( 'init', 'spokares_no_comments', 100 );
