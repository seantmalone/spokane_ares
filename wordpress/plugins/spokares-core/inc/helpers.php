<?php
/**
 * Shared helpers: dev switch, "today", option access with defaults, links,
 * icons, the cache hook and the editor-preview test.
 *
 * @package spokares-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * True only in the local dev environment: environment type `local` AND the
 * SPOKARES_DEV constant. Every dev-only switch goes through this.
 */
function spokares_is_dev(): bool {
	return 'local' === wp_get_environment_type() && defined( 'SPOKARES_DEV' ) && SPOKARES_DEV;
}

/**
 * Is this a valid Y-m-d calendar date?
 *
 * @param mixed $ymd Candidate.
 */
function spokares_is_ymd( $ymd ): bool {
	if ( ! is_string( $ymd ) || ! preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $ymd, $m ) ) {
		return false;
	}
	return checkdate( (int) $m[2], (int) $m[3], (int) $m[1] );
}

/**
 * Is this a valid H:i time (24-hour)?
 *
 * @param mixed $hhmm Candidate.
 */
function spokares_is_hhmm( $hhmm ): bool {
	return is_string( $hhmm ) && (bool) preg_match( '/^([01]\d|2[0-3]):[0-5]\d$/', $hhmm );
}

/**
 * Today in the site time zone (America/Los_Angeles), as Y-m-d.
 * Dev only: ?today=YYYY-MM-DD, then the SPOKARES_TODAY constant, override it.
 */
function spokares_today(): string {
	static $today = null;
	if ( null !== $today ) {
		return $today;
	}
	$today = wp_date( 'Y-m-d' );
	if ( spokares_is_dev() ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only demo date, dev environment only.
		$param = isset( $_GET['today'] ) ? sanitize_text_field( wp_unslash( $_GET['today'] ) ) : '';
		if ( spokares_is_ymd( $param ) ) {
			$today = $param;
		} elseif ( defined( 'SPOKARES_TODAY' ) && spokares_is_ymd( SPOKARES_TODAY ) ) {
			$today = SPOKARES_TODAY;
		}
	}
	return $today;
}

/**
 * Add (or subtract) days to a Y-m-d date. Pure calendar arithmetic in UTC,
 * so daylight saving never shifts a date.
 *
 * @param string $ymd  Date.
 * @param int    $days Days to add.
 */
function spokares_add_days( string $ymd, int $days ): string {
	$d = spokares_date_obj( $ymd );
	return $d ? $d->modify( ( $days >= 0 ? '+' : '' ) . $days . ' days' )->format( 'Y-m-d' ) : $ymd;
}

/**
 * A DateTimeImmutable at midnight UTC for a Y-m-d date, or null.
 *
 * @param string $ymd Date.
 */
function spokares_date_obj( string $ymd ): ?DateTimeImmutable {
	if ( ! spokares_is_ymd( $ymd ) ) {
		return null;
	}
	$d = DateTimeImmutable::createFromFormat( '!Y-m-d', $ymd, new DateTimeZone( 'UTC' ) );
	return $d ? $d : null;
}

/**
 * Whole days from $a to $b (positive when $b is later).
 *
 * @param string $a Date.
 * @param string $b Date.
 */
function spokares_days_between( string $a, string $b ): int {
	$da = spokares_date_obj( $a );
	$db = spokares_date_obj( $b );
	if ( ! $da || ! $db ) {
		return 0;
	}
	return (int) $da->diff( $db )->format( '%r%a' );
}

/**
 * Default values of every option the plugin owns (§6.3). Used when an option
 * is absent and to fill missing keys, so readers never meet an undefined index.
 */
function spokares_option_defaults(): array {
	static $defaults = null;
	if ( null !== $defaults ) {
		return $defaults;
	}
	$defaults = array(
		'spk_rota'       => array(),
		'spk_rota_prev'  => array(),
		'spk_rota_saved' => array(),
		'spk_nets'       => array(
			'net_time'       => '20:00',
			'winlink_nth'    => array( 2, 4 ),
			'simplex_nth'    => array( 5 ),
			'gmrs_nth'       => array( 3 ),
			'gmrs_time'      => '19:30',
			'winlink_howto'  => 'Answer on an ICS-213 unless another form is named. Send to NV2Z and AG7QP between 5:00 and 9:00 PM on the date shown. Radio preferred; Telnet OK.',
			'open_slot_line' => 'Open slot? Tell the Net Manager (AG7QP) on the net or on groups.io.',
			'needs_check'    => true,
		),
		'spk_radio'      => array(
			'primary'   => array(
				'call'   => 'W7GBU',
				'freq'   => '147.300',
				'offset' => '+600 kHz',
				'tone'   => '100 Hz',
			),
			'alternate' => array(
				'freq'        => '146.880',
				'offset'      => '-600 kHz',
				'tone'        => '123 Hz',
				'show'        => true,
				'needs_check' => true,
			),
		),
		'spk_meetings'   => array(
			'meetings' => array(
				array(
					'id'          => 'workshop',
					'name'        => 'Second Saturday Workshop',
					'nth'         => array( 2 ),
					'weekday'     => 6,
					'start'       => '09:00',
					'end'         => '12:00',
					'time_text'   => '',
					'home_extra'  => 'then a Winlink workshop, 12:30–3:30 PM',
					'skip_months' => array(),
					'show_home'   => true,
					'active'      => true,
					'needs_check' => false,
				),
				array(
					'id'          => 'winlink-workshop',
					'name'        => 'Winlink workshop',
					'nth'         => array( 2 ),
					'weekday'     => 6,
					'start'       => '12:30',
					'end'         => '15:30',
					'time_text'   => '',
					'home_extra'  => '',
					'skip_months' => array(),
					'show_home'   => false,
					'active'      => true,
					'needs_check' => true,
				),
				array(
					'id'          => 'third-thursday',
					'name'        => 'Third Thursday training meeting',
					'nth'         => array( 3 ),
					'weekday'     => 4,
					'start'       => '',
					'end'         => '',
					'time_text'   => 'evenings',
					'home_extra'  => '',
					'skip_months' => array( 12 ),
					'show_home'   => true,
					'active'      => true,
					'needs_check' => true,
				),
			),
			'changes'  => array(),
		),
		'spk_site'       => array(
			'place' => array(
				'name'        => 'Spokane County Emergency Management',
				'street'      => '1121 W Gardner Ave',
				'city'        => 'Spokane',
				'state'       => 'WA',
				'zip'         => '99260',
				'needs_check' => true,
			),
		),
		'spk_tiles'      => array(
			array(
				'doc'   => 0,
				'label' => 'Net script',
				'icon'  => 'script',
			),
			array(
				'doc'   => 0,
				'label' => 'ICS-213',
				'icon'  => 'form',
			),
			array(
				'doc'   => 0,
				'label' => 'ICS-214',
				'icon'  => 'log',
			),
			array(
				'doc'   => 0,
				'label' => 'ACS Task Book',
				'icon'  => 'book',
			),
		),
	);
	return $defaults;
}

/**
 * Add the default options that are absent (never overwrites).
 */
function spokares_add_default_options(): void {
	foreach ( spokares_option_defaults() as $name => $value ) {
		if ( false === get_option( $name, false ) ) {
			// Rota history and saved stamps are small; nothing here needs autoload off.
			add_option( $name, $value );
		}
	}
}

/**
 * Read an option merged over its defaults (one level deep, and two levels for
 * the nested groups), so every key the code reads exists.
 *
 * @param string $name Option name.
 */
function spokares_opt( string $name ): array {
	// Read per request once: the rota and meeting code call this per row and
	// per date. The copy is dropped whenever the option is written.
	static $cache = array();
	if ( '' === $name ) {
		$cache = array();
		return array();
	}
	if ( isset( $cache[ $name ] ) ) {
		return $cache[ $name ];
	}
	$cache[ $name ] = spokares_opt_read( $name );
	return $cache[ $name ];
}

/**
 * Forget the per-request copies of the plugin's options (after a write).
 */
function spokares_opt_flush(): void {
	spokares_opt( '' );
}
foreach ( array( 'spk_rota', 'spk_rota_prev', 'spk_rota_saved', 'spk_nets', 'spk_radio', 'spk_meetings', 'spk_site', 'spk_tiles' ) as $spokares_opt_name ) {
	add_action( 'add_option_' . $spokares_opt_name, 'spokares_opt_flush' );
	add_action( 'update_option_' . $spokares_opt_name, 'spokares_opt_flush' );
	add_action( 'delete_option_' . $spokares_opt_name, 'spokares_opt_flush' );
}
unset( $spokares_opt_name );

/**
 * Read an option merged over its defaults (see spokares_opt()).
 *
 * @param string $name Option name.
 */
function spokares_opt_read( string $name ): array {
	$defaults = spokares_option_defaults();
	$default  = $defaults[ $name ] ?? array();
	$value    = get_option( $name, $default );
	if ( ! is_array( $value ) ) {
		$value = $default;
	}
	switch ( $name ) {
		case 'spk_nets':
			$value = array_merge( $default, $value );
			foreach ( array( 'winlink_nth', 'simplex_nth', 'gmrs_nth' ) as $k ) {
				$value[ $k ] = array_values( array_filter( array_map( 'intval', (array) $value[ $k ] ), static fn( $n ) => $n >= 1 && $n <= 5 ) );
			}
			$value['net_time']  = spokares_is_hhmm( $value['net_time'] ) ? $value['net_time'] : $default['net_time'];
			$value['gmrs_time'] = spokares_is_hhmm( $value['gmrs_time'] ) ? $value['gmrs_time'] : '';
			break;
		case 'spk_radio':
			$value['primary']   = array_merge( $default['primary'], is_array( $value['primary'] ?? null ) ? $value['primary'] : array() );
			$value['alternate'] = array_merge(
				array(
					'freq'        => '',
					'offset'      => '',
					'tone'        => '',
					'show'        => false,
					'needs_check' => false,
				),
				is_array( $value['alternate'] ?? null ) ? $value['alternate'] : array()
			);
			break;
		case 'spk_meetings':
			$meetings = array();
			foreach ( (array) ( $value['meetings'] ?? array() ) as $m ) {
				if ( is_array( $m ) && ! empty( $m['id'] ) ) {
					$meetings[] = spokares_normalize_meeting( $m );
				}
			}
			$changes = array();
			foreach ( (array) ( $value['changes'] ?? array() ) as $c ) {
				if ( is_array( $c ) && spokares_is_ymd( $c['date'] ?? '' ) && ! empty( $c['meeting'] ) ) {
					$changes[] = array_merge(
						array(
							'kind'     => 'cancelled',
							'new_date' => '',
							'note'     => '',
						),
						$c
					);
				}
			}
			$confirmed = isset( $value['confirmed'] ) ? (array) $value['confirmed'] : array();
			$value     = array(
				'meetings'  => $meetings,
				'changes'   => $changes,
				'confirmed' => $confirmed,
			);
			break;
		case 'spk_site':
			// Only the meeting place; an older stored value's groups.io
			// addresses are not settings any more and are left out.
			$value = array(
				'place' => array_merge( $default['place'], is_array( $value['place'] ?? null ) ? $value['place'] : array() ),
			);
			break;
		case 'spk_tiles':
			$tiles = array();
			for ( $i = 0; $i < 4; $i++ ) {
				$slot    = isset( $value[ $i ] ) && is_array( $value[ $i ] ) ? $value[ $i ] : array();
				$tiles[] = array(
					'doc'   => absint( $slot['doc'] ?? 0 ),
					'label' => (string) ( $slot['label'] ?? '' ),
					'icon'  => in_array( $slot['icon'] ?? '', array( 'script', 'form', 'log', 'book' ), true ) ? $slot['icon'] : 'form',
				);
			}
			$tiles['confirmed'] = isset( $value['confirmed'] ) ? (array) $value['confirmed'] : array();
			$value              = $tiles;
			break;
		case 'spk_rota':
			$rows = array();
			foreach ( $value as $ymd => $row ) {
				if ( spokares_is_ymd( (string) $ymd ) && is_array( $row ) ) {
					$rows[ (string) $ymd ] = spokares_normalize_rota_row( $row );
				}
			}
			ksort( $rows );
			$value = $rows;
			break;
	}
	return $value;
}

/**
 * One meeting rule with every key present.
 *
 * @param array $m Stored meeting.
 */
function spokares_normalize_meeting( array $m ): array {
	$m = array_merge(
		array(
			'id'          => '',
			'name'        => '',
			'nth'         => array(),
			'weekday'     => 6,
			'start'       => '',
			'end'         => '',
			'time_text'   => '',
			'home_extra'  => '',
			'skip_months' => array(),
			'show_home'   => false,
			'active'      => true,
			'needs_check' => false,
		),
		$m
	);

	$m['nth']         = array_values( array_filter( array_map( 'intval', (array) $m['nth'] ), static fn( $n ) => $n >= 1 && $n <= 5 ) );
	$m['skip_months'] = array_values( array_filter( array_map( 'intval', (array) $m['skip_months'] ), static fn( $n ) => $n >= 1 && $n <= 12 ) );
	$m['weekday']     = max( 0, min( 6, (int) $m['weekday'] ) );
	$m['start']       = spokares_is_hhmm( $m['start'] ) ? $m['start'] : '';
	$m['end']         = spokares_is_hhmm( $m['end'] ) ? $m['end'] : '';
	$m['show_home']   = (bool) $m['show_home'];
	$m['active']      = (bool) $m['active'];
	$m['needs_check'] = (bool) $m['needs_check'];
	return $m;
}

/**
 * One rota row with every key present.
 *
 * @param array $row Stored row.
 */
function spokares_normalize_rota_row( array $row ): array {
	$row          = array_merge(
		array(
			'state'   => 'tbd',
			'call'    => '',
			'note'    => '',
			'wl_task' => '',
			'wl_form' => '',
		),
		$row
	);
	$row['state'] = in_array( $row['state'], array( 'call', 'open', 'tbd' ), true ) ? $row['state'] : 'tbd';
	if ( 'call' !== $row['state'] ) {
		$row['call'] = '';
	}
	foreach ( array( 'call', 'note', 'wl_task', 'wl_form' ) as $k ) {
		$row[ $k ] = (string) $row[ $k ];
	}
	return $row;
}

/**
 * A short lock around the read-change-write of an option (the Net rota and
 * Regular meetings saves), so two saves at the same moment can't both read
 * the old rows and one lose the other's change. The lock is a row in the
 * options table added with INSERT IGNORE, which is atomic; a lock older than
 * 30 seconds is taken over. Once the lock is held, the option is read fresh
 * from the database. After about 5 seconds of waiting the save goes on
 * without it (returns false) rather than failing.
 *
 * @param string $name  Option name.
 * @param array  $also  Other options to re-read fresh (stamps kept beside it).
 */
function spokares_lock_option( string $name, array $also = array() ): bool {
	global $wpdb;
	$key  = $name . '_lock';
	$held = false;
	for ( $i = 0; $i < 25 && ! $held; $i++ ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- the atomic insert is the lock itself.
		$held = (bool) $wpdb->query( $wpdb->prepare( "INSERT IGNORE INTO {$wpdb->options} (option_name, option_value, autoload) VALUES (%s, %s, 'off')", $key, (string) time() ) );
		if ( $held ) {
			break;
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- the lock's age, never cached.
		$since = (int) $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", $key ) );
		if ( $since && time() - $since > 30 ) {
			spokares_unlock_option( $name );
			continue;
		}
		usleep( 200000 );
	}
	if ( $held ) {
		register_shutdown_function( 'spokares_unlock_option', $name );
	}
	foreach ( array_merge( array( $name ), $also ) as $option ) {
		wp_cache_delete( $option, 'options' );
	}
	wp_cache_delete( 'alloptions', 'options' );
	wp_cache_delete( 'notoptions', 'options' );
	spokares_opt_flush();
	return $held;
}

/**
 * Release the lock taken by spokares_lock_option().
 *
 * @param string $name Option name.
 */
function spokares_unlock_option( string $name ): void {
	global $wpdb;
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- the lock row is never cached.
	$wpdb->delete( $wpdb->options, array( 'option_name' => $name . '_lock' ) );
}

/**
 * Version string for one of the plugin's scripts or styles: the plugin version
 * in production; on a local dev site the file's modification time is added, so
 * an edited script is never served from the browser cache (as the theme does).
 *
 * @param string $relative_path Path inside the plugin, e.g. 'assets/js/editor-guard.js'.
 */
function spokares_asset_version( string $relative_path ): string {
	if ( 'local' === wp_get_environment_type() ) {
		$file = SPOKARES_CORE_DIR . $relative_path;
		if ( is_readable( $file ) ) {
			return SPOKARES_CORE_VERSION . '.' . (string) filemtime( $file );
		}
	}
	return SPOKARES_CORE_VERSION;
}

/**
 * The one place a future page cache is purged from. Called on every save.
 */
function spokares_purge_cache(): void {
	/**
	 * Fires after any list the site prints was changed.
	 */
	do_action( 'spokares_cache_purged' );
}

/**
 * Names for external hosts, copied from B's link texts ("(opens ARRL)").
 * Anything not listed prints the host without "www.".
 */
function spokares_host_names(): array {
	return array(
		'arrl.org'                  => 'ARRL',
		'learn.arrl.org'            => 'ARRL',
		'training.fema.gov'         => 'FEMA',
		'spokaneares-acs.groups.io' => 'groups.io, members only',
		'earthquake.usgs.gov'       => 'USGS',
		'weather.gov'               => 'the National Weather Service',
		'youtube.com'               => 'YouTube',
		'youtu.be'                  => 'YouTube',
		'cdp.dhs.gov'               => 'the FEMA SID site',
		'mil.wa.gov'                => 'WA Military Dept.',
		'winlink.org'               => 'Winlink',
		'spokanecounty.gov'         => 'Spokane County',
		'wsdot.wa.gov'              => 'WSDOT',
		'cisa.gov'                  => 'CISA',
	);
}

/**
 * The host of a URL without "www.", lowercased ('' when none).
 *
 * @param string $url URL.
 */
function spokares_url_host( string $url ): string {
	$host = wp_parse_url( $url, PHP_URL_HOST );
	if ( ! is_string( $host ) ) {
		return '';
	}
	$host = strtolower( $host );
	return str_starts_with( $host, 'www.' ) ? substr( $host, 4 ) : $host;
}

/**
 * Is this URL on another site? mailto: and relative links are not external.
 *
 * @param string $url URL.
 */
function spokares_is_external( string $url ): bool {
	$host = spokares_url_host( $url );
	if ( '' === $host ) {
		return false;
	}
	return spokares_url_host( home_url( '/' ) ) !== $host;
}

/**
 * The readable name for an external URL's site.
 *
 * @param string $url URL.
 */
function spokares_host_name( string $url ): string {
	$host = spokares_url_host( $url );
	$map  = spokares_host_names();
	return $map[ $host ] ?? $host;
}

/**
 * A link. External: class "ext" and a visually hidden "(opens {name})", where
 * {name} is $source when given, else the host map, else the host.
 *
 * @param string $url    Target.
 * @param string $label  Link text (plain text; escaped here).
 * @param string $class  Extra classes.
 * @param string $source Name of the site, overriding the host map.
 */
function spokares_link( string $url, string $label, string $class = '', string $source = '' ): string {
	$external = spokares_is_external( $url );
	$classes  = trim( $class . ( $external ? ' ext' : '' ) );
	$html     = '<a' . ( '' !== $classes ? ' class="' . esc_attr( $classes ) . '"' : '' ) . ' href="' . esc_url( $url ) . '">' . spokares_text( $label );
	if ( $external ) {
		$name  = '' !== $source ? $source : spokares_host_name( $url );
		$html .= '<span class="vh"> ' . esc_html(
			/* translators: %s: the name of another website, e.g. "ARRL". */
			sprintf( __( '(opens %s)', 'spokares-core' ), $name )
		) . '</span>';
	}
	return $html . '</a>';
}

/**
 * Escape plain text for HTML and put a no-break space before AM, PM, MHz, kHz
 * and Hz when a digit precedes them (house style; never "8:00<br>PM").
 *
 * @param string $text Plain text.
 */
function spokares_text( string $text ): string {
	return spokares_nbsp( esc_html( $text ) );
}

/**
 * Insert U+00A0 between a digit and AM, PM, MHz, kHz or Hz. Safe on escaped
 * text (no tags are touched because escaped text has none).
 *
 * @param string $text Text.
 */
function spokares_nbsp( string $text ): string {
	return (string) preg_replace( '/(\d) (AM|PM|MHz|kHz|Hz)\b/u', "$1\u{00A0}$2", $text );
}

/**
 * Inline SVG icons, paths copied from B's sprite.
 *
 * @param string $name copy | search | menu | script | form | log | book.
 */
function spokares_icon( string $name ): string {
	$paths = array(
		'copy'   => '<rect x="8.5" y="8.5" width="11.5" height="11.5" rx="2"/><path d="M15.5 8.5V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v7.5a2 2 0 0 0 2 2h2.5"/>',
		'search' => '<circle cx="11" cy="11" r="6.5"/><path d="M16 16l4.5 4.5"/>',
		'menu'   => '<path d="M4 7h16M4 12h16M4 17h16"/>',
		'script' => '<path d="M6 3h8l4 4v14H6z"/><path d="M14 3v4h4M9 12h6M9 15h6M9 18h4"/>',
		'form'   => '<rect x="4" y="3" width="16" height="18" rx="1.5"/><path d="M4 8h16M9.5 8v13M12.5 12h4.5M12.5 16h4.5"/>',
		'log'    => '<rect x="4.5" y="4" width="15" height="16.5" rx="1.5"/><path d="M4.5 9h15M4.5 14h15M9.5 4v16.5"/>',
		'book'   => '<path d="M5 5.5A2.5 2.5 0 0 1 7.5 3H19v14H7.5A2.5 2.5 0 0 0 5 19.5z"/><path d="M5 19.5A2.5 2.5 0 0 0 7.5 22H19v-5"/>',
	);
	if ( ! isset( $paths[ $name ] ) ) {
		return '';
	}
	// The paths are constants above; nothing user-supplied reaches this string.
	return '<svg class="icon" aria-hidden="true" focusable="false" viewBox="0 0 24 24">' . $paths[ $name ] . '</svg>';
}

/**
 * Allowed markup for spokares_icon() output, for wp_kses() callers.
 */
function spokares_icon_kses(): array {
	$shape = array(
		'd'      => true,
		'x'      => true,
		'y'      => true,
		'width'  => true,
		'height' => true,
		'rx'     => true,
		'cx'     => true,
		'cy'     => true,
		'r'      => true,
	);
	return array(
		'svg'    => array(
			'class'       => true,
			'aria-hidden' => true,
			'focusable'   => true,
			'viewbox'     => true,
		),
		'path'   => $shape,
		'rect'   => $shape,
		'circle' => $shape,
	);
}

/**
 * The current REST route, recorded when a REST request dispatches.
 *
 * @param mixed           $response Unused (passed through).
 * @param array           $handler  Unused.
 * @param WP_REST_Request $request  Request.
 */
function spokares_note_rest_route( $response, $handler, $request ) {
	if ( $request instanceof WP_REST_Request ) {
		$GLOBALS['spokares_rest_route'] = $request->get_route();
	}
	return $response;
}
add_filter( 'rest_request_before_callbacks', 'spokares_note_rest_route', 10, 3 );

/**
 * Is this block render the editor's server-side preview (the block-renderer
 * REST route that autoRegister blocks use), for someone who can edit?
 */
function spokares_is_editor_preview(): bool {
	$route = isset( $GLOBALS['spokares_rest_route'] ) ? (string) $GLOBALS['spokares_rest_route'] : '';
	if ( '' === $route || ! str_starts_with( $route, '/wp/v2/block-renderer/' ) ) {
		return false;
	}
	return current_user_can( 'edit_posts' );
}

/**
 * URL of a site page by path (root-relative in the contract; home_url in PHP).
 *
 * @param string $path     Path such as "/members/exercises/".
 * @param string $fragment Optional anchor without "#".
 */
function spokares_site_url( string $path, string $fragment = '' ): string {
	return home_url( $path ) . ( '' !== $fragment ? '#' . rawurlencode( $fragment ) : '' );
}

/**
 * A user's display name for "by {name}" lines (never an e-mail address).
 *
 * @param int $user_id User.
 */
function spokares_user_name( int $user_id ): string {
	$user = $user_id ? get_userdata( $user_id ) : false;
	return $user ? $user->display_name : __( 'someone', 'spokares-core' );
}

/**
 * Our theme is never updated from WordPress.org, even if a theme there
 * shares its slug (§5.7). The theme also carries "Update URI: false".
 *
 * @param mixed $transient The update_themes site transient.
 */
function spokares_no_theme_updates( $transient ) {
	if ( is_object( $transient ) ) {
		foreach ( array( 'response', 'no_update', 'checked' ) as $key ) {
			if ( isset( $transient->{$key} ) && is_array( $transient->{$key} ) ) {
				unset( $transient->{$key}['spokares'] );
			}
		}
	}
	return $transient;
}
add_filter( 'site_transient_update_themes', 'spokares_no_theme_updates' );
