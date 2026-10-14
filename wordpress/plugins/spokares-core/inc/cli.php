<?php
/**
 * `wp spokares rota|meeting|event …` (workflow review O1): the webmaster's
 * content edits over SSH go through the same checks and the same writes as
 * the screens, instead of `wp eval-file` and update_option():
 *
 * - rota: spokares_rota_check_rows() and spokares_rota_write_rows(), as the
 *   Net Control Schedule's Save (call-sign check, clash check, length,
 *   never-publish words, the phone/e-mail tick, the "Last saved" stamp and
 *   the undo snapshot, cache purge); `paste` reads text as Paste the schedule.
 * - meeting: spokares_meetings_check() and spokares_meetings_write(), as
 *   Cancel or Move a Meeting (one-off dates included).
 * - event: spokares_event_problems() on the event's stored fields with the
 *   new dates (the event form's own checks). The form's save runs inside
 *   post.php, so these commands write only the When fields and the
 *   Cancelled tick (see `wp help spokares event`).
 *
 * A phone number or e-mail address needs --confirm (the screens' "Publish
 * it" tick). Every write needs an account: WP-CLI's global --user, which the
 * "Last saved" line names and whose capabilities are checked as on the
 * screens. --dry-run runs the checks and says what would change.
 *
 *     wp spokares rota show
 *     wp spokares rota set 2026-10-20 NZ2S --user=frank
 *     wp spokares rota paste schedule.txt --dry-run
 *     wp spokares meeting cancel workshop 2026-11-14 --note="Thanksgiving week" --user=frank
 *     wp spokares meeting extra other 2026-12-19 --name="Holiday potluck" --start=6pm --user=frank
 *     wp spokares event set-date 412 2026-11-07 2026-11-08 --user=frank
 *
 * Loaded only under WP-CLI (spokares-core.php); the dev tests require it
 * directly and call the command functions with a stand-in WP_CLI.
 *
 * @package spokares-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * The admin files the commands share with the screens. They are loaded only
 * in wp-admin (spokares-core.php), so a command loads them when it runs.
 */
function spokares_cli_load(): void {
	foreach ( array( 'admin-common', 'admin-rota', 'admin-net', 'admin-meetings', 'admin-events' ) as $file ) {
		require_once SPOKARES_CORE_DIR . 'inc/' . $file . '.php';
	}
}

/**
 * Refuse a write without an account that may make it on the screens.
 *
 * @param string $cap     Capability the screen checks.
 * @param int    $post_id For edit_post: the post.
 */
function spokares_cli_need_user( string $cap, int $post_id = 0 ): void {
	$user = wp_get_current_user();
	if ( ! $user->exists() ) {
		WP_CLI::error( 'Say who is making this change: add --user=<login>. The "Last saved" line names that account, and its permissions are checked as on the screens.' );
	}
	$ok = $post_id ? current_user_can( $cap, $post_id ) : current_user_can( $cap );
	if ( ! $ok ) {
		WP_CLI::error( sprintf( '%s (%s) can’t make this change on the site’s screens either. Use an account that can.', $user->display_name, $user->user_login ) );
	}
}

/**
 * A text option: --note=words, '' for --note= or a bare --note, null when
 * absent.
 *
 * @param array  $assoc Options.
 * @param string $key   Option.
 */
function spokares_cli_text( array $assoc, string $key ): ?string {
	if ( ! array_key_exists( $key, $assoc ) ) {
		return null;
	}
	return is_string( $assoc[ $key ] ) ? $assoc[ $key ] : '';
}

/**
 * A flag (--dry-run, --confirm).
 *
 * @param array  $assoc Options.
 * @param string $key   Flag.
 */
function spokares_cli_flag( array $assoc, string $key ): bool {
	return ! empty( $assoc[ $key ] ) && 'false' !== $assoc[ $key ];
}

/**
 * A date typed as YYYY-MM-DD, or an error.
 *
 * @param string $typed What was typed.
 * @param string $what  Its name in the error.
 */
function spokares_cli_date( string $typed, string $what ): string {
	if ( ! spokares_is_ymd( $typed ) ) {
		WP_CLI::error( sprintf( 'Type the %s as YYYY-MM-DD, like 2026-10-20 (not "%s").', $what, $typed ) );
	}
	return $typed;
}

/**
 * The problems a check found, as lines: "Oct 20, Note: It has a phone
 * number. …", with how to tick "Publish it" from here.
 *
 * @param array<string,string> $errors Field key => sentence.
 * @param callable             $where  Field key => "Oct 20, Note".
 * @return string[]
 */
function spokares_cli_problem_lines( array $errors, callable $where ): array {
	$lines = array();
	foreach ( $errors as $key => $sentence ) {
		$key = (string) $key;
		if ( str_ends_with( $key, '-confirm' ) ) {
			continue;
		}
		$line = trim( $where( $key ) . ': ' . $sentence, ': ' );
		foreach ( array_keys( $errors ) as $other ) {
			$field = substr( (string) $other, 0, -8 );
			if ( str_ends_with( (string) $other, '-confirm' ) && ( $key === $field || str_starts_with( $key, $field . '-' ) ) ) {
				$line .= ' (Add --confirm to tick that box.)';
				break;
			}
		}
		$lines[] = $line;
	}
	return array_values( array_unique( $lines ) );
}

/**
 * Print what was refused, then stop with an error (a non-zero exit, so a
 * script notices), or return when nothing was.
 *
 * @param string[] $lines   Problem lines.
 * @param bool     $partial Something else was saved.
 */
function spokares_cli_refuse( array $lines, bool $partial = false ): void {
	if ( ! $lines ) {
		return;
	}
	foreach ( $lines as $line ) {
		WP_CLI::warning( $line );
	}
	WP_CLI::error( $partial ? 'The rest was saved; the lines above were not.' : 'Nothing was saved.' );
}

/**
 * A table, or the --format WP-CLI was asked for.
 *
 * @param array    $assoc  Options.
 * @param array    $rows   Rows.
 * @param string[] $fields Columns.
 */
function spokares_cli_table( array $assoc, array $rows, array $fields ): void {
	$format = isset( $assoc['format'] ) && is_string( $assoc['format'] ) ? $assoc['format'] : 'table';
	WP_CLI\Utils\format_items( $format, $rows, $fields );
}

/* ------------------------------------------------------------------ rota */

/**
 * Show the schedule as the screen lists it, with the "Last saved" line.
 *
 * ## OPTIONS
 *
 * [--weeks=<n>]
 * : How many Tuesdays (13 to 52).
 * ---
 * default: 13
 * ---
 *
 * [--format=<format>]
 * : table, csv, json or yaml.
 * ---
 * default: table
 * ---
 *
 * @param array $args  Positional arguments.
 * @param array $assoc Options.
 */
function spokares_cli_rota_show( array $args, array $assoc ): void {
	spokares_cli_load();
	$weeks = max( 13, min( 52, absint( $assoc['weeks'] ?? 13 ) ) );
	$rows  = array();
	foreach ( spokares_rota_rows( $weeks ) as $r ) {
		$rows[] = array(
			'date'        => $r['date'],
			'net'         => $r['note'],
			'net_control' => spokares_rota_state_words( $r['state'], $r['call'] ),
			'note'        => $r['row_note'],
			'winlink'     => $r['wl_task'],
			'form'        => $r['wl_form'],
		);
	}
	$saved = get_option( 'spk_rota_saved', array() );
	$line  = is_array( $saved ) ? spokares_rota_saved_line( $saved ) : '';
	if ( '' !== $line && 'table' === ( $assoc['format'] ?? 'table' ) ) {
		WP_CLI::line( $line );
	}
	spokares_cli_table( $assoc, $rows, array( 'date', 'net', 'net_control', 'note', 'winlink', 'form' ) );
}

/**
 * Set one Tuesday's row, as typing it on the screen and clicking Save.
 * Fields not given keep what the row has now.
 *
 * ## OPTIONS
 *
 * <date>
 * : The Tuesday, YYYY-MM-DD (one of the next 52).
 *
 * <net-control>
 * : A call sign, or volunteer (Volunteer needed), none (No net) or tbd
 * (Not posted yet).
 *
 * [--note=<text>]
 * : The row's note (80 characters). --note= clears it.
 *
 * [--winlink=<task>]
 * : The Winlink task (120 characters). --winlink= clears it.
 *
 * [--form=<form>]
 * : The Winlink form, e.g. ICS-213RR. --form= is the usual ICS-213.
 *
 * [--confirm]
 * : The note or task has a public agency phone number or e-mail
 * address: publish it (the screen's "Publish it" tick).
 *
 * [--dry-run]
 * : Run the checks and say what would change; save nothing.
 *
 * ## EXAMPLES
 *
 *     wp spokares rota set 2026-10-20 NZ2S --user=frank
 *     wp spokares rota set 2026-10-27 none --note="Field Day" --user=frank
 *
 * @param array $args  Positional arguments.
 * @param array $assoc Options.
 */
function spokares_cli_rota_set( array $args, array $assoc ): void {
	spokares_cli_load();
	$ymd                  = spokares_cli_rota_tuesday( (string) ( $args[0] ?? '' ) );
	list( $state, $call ) = spokares_cli_rota_who( (string) ( $args[1] ?? '' ) );
	$dry                  = spokares_cli_flag( $assoc, 'dry-run' );
	if ( ! $dry ) {
		spokares_cli_need_user( 'spokares_edit_rota' );
		spokares_lock_option( 'spk_rota', array( 'spk_rota_saved', 'spk_rota_prev' ) );
	}
	$stored = spokares_opt( 'spk_rota' );
	$cur    = $stored[ $ymd ] ?? spokares_normalize_rota_row( array() );
	$row    = array(
		'h'       => spokares_rota_hash( $stored[ $ymd ] ?? null ),
		'state'   => $state,
		'call'    => $call,
		'note'    => spokares_cli_text( $assoc, 'note' ) ?? $cur['note'],
		'wl_task' => spokares_cli_text( $assoc, 'winlink' ) ?? $cur['wl_task'],
		'wl_form' => spokares_cli_text( $assoc, 'form' ) ?? $cur['wl_form'],
	);
	if ( spokares_cli_flag( $assoc, 'confirm' ) ) {
		$row['confirm_note']    = '1';
		$row['confirm_wl_task'] = '1';
	}
	if ( ( '' !== (string) spokares_cli_text( $assoc, 'winlink' ) || '' !== (string) spokares_cli_text( $assoc, 'form' ) ) && 'winlink' !== spokares_net_on( $ymd )['kind'] ) {
		WP_CLI::warning( sprintf( '%s isn’t a Winlink night, so the site doesn’t show its Winlink task.', spokares_fmt_date( $ymd, 'day' ) ) );
	}
	spokares_cli_rota_save( array( $ymd => $row ), $dry );
}

/**
 * Read a pasted schedule, as Paste the schedule › Preview › Save these:
 * one line per Tuesday ("Tue Oct 20 NZ2S", "10/27 open", "Nov 3 no
 * net"). Lines it can't read are listed and left out. Only Net control
 * changes; each row keeps its note and Winlink task.
 *
 * ## OPTIONS
 *
 * [<file>]
 * : A text file, or - (the default) to read standard input.
 *
 * [--dry-run]
 * : Say what would change (the screen's preview); save nothing.
 *
 * ## EXAMPLES
 *
 *     wp spokares rota paste schedule.txt --dry-run
 *     wp spokares rota paste schedule.txt --user=frank
 *
 * @param array $args  Positional arguments.
 * @param array $assoc Options.
 */
function spokares_cli_rota_paste( array $args, array $assoc ): void {
	spokares_cli_load();
	$file = (string) ( $args[0] ?? '-' );
	$text = '-' === $file ? file_get_contents( 'php://stdin' ) : ( is_readable( $file ) ? file_get_contents( $file ) : false ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- the file the webmaster named, or standard input.
	if ( false === $text ) {
		WP_CLI::error( sprintf( 'Could not read %s.', $file ) );
	}
	// As the screen's Preview takes it.
	$text = mb_substr( sanitize_textarea_field( (string) $text ), 0, 10000, 'UTF-8' );
	if ( '' === trim( $text ) ) {
		WP_CLI::error( 'The schedule is empty: one line per Tuesday, like "Tue Oct 20 NZ2S".' );
	}
	$paste = spokares_rota_parse_paste( $text );
	foreach ( $paste['bad'] as $b ) {
		WP_CLI::warning( sprintf( 'Not understood, so left out: “%1$s”: %2$s', $b['line'], $b['why'] ) );
	}
	$dry = spokares_cli_flag( $assoc, 'dry-run' );
	if ( ! $dry ) {
		spokares_cli_need_user( 'spokares_edit_rota' );
		spokares_lock_option( 'spk_rota', array( 'spk_rota_saved', 'spk_rota_prev' ) );
	}
	list( $changes, $same ) = spokares_rota_paste_changes( $paste );
	if ( $same ) {
		/* translators: %d: number of Tuesdays. */
		WP_CLI::log( sprintf( _n( '%d Tuesday already matches.', '%d Tuesdays already match.', $same, 'spokares-core' ), $same ) );
	}
	if ( ! $changes ) {
		if ( ! $dry ) {
			spokares_unlock_option( 'spk_rota' );
		}
		WP_CLI::success( 'Nothing to change: the schedule already says that.' );
		return;
	}
	spokares_cli_rota_save( spokares_rota_paste_rows( $changes ), $dry );
}

/**
 * Check rows as the screen's Save does, then save them (or say what
 * would change).
 *
 * @param array $posted Date => row as the form posts it.
 * @param bool  $dry    Only say what would change.
 */
function spokares_cli_rota_save( array $posted, bool $dry ): void {
	$before = spokares_opt( 'spk_rota' );
	$check  = spokares_rota_check_rows( $posted );
	$lines  = spokares_cli_problem_lines(
		$check['errors'],
		static function ( string $key ): string {
			$fields = array(
				'call'    => 'Net control',
				'row'     => '',
				'note'    => 'Note',
				'wl_task' => 'Winlink task',
				'wl_form' => 'Form',
			);
			$where  = spokares_fmt_date( substr( $key, 0, 10 ), 'day' );
			$field  = $fields[ substr( $key, 11 ) ] ?? '';
			return '' !== $field ? $where . ', ' . $field : $where;
		}
	);
	$dates  = array_keys( $check['prev'] );
	if ( $dry ) {
		foreach ( $dates as $ymd ) {
			WP_CLI::log( sprintf( 'Would save %1$s: %2$s (was %3$s)', spokares_fmt_date( $ymd, 'day' ), spokares_cli_rota_row_words( $check['stored'][ $ymd ] ?? null ), spokares_cli_rota_row_words( $before[ $ymd ] ?? null ) ) );
		}
		if ( ! $dates && ! $lines ) {
			WP_CLI::log( 'Nothing would change.' );
		}
		foreach ( $lines as $line ) {
			WP_CLI::warning( 'Would not save ' . $line );
		}
		WP_CLI::success( 'Dry run: nothing was saved.' );
		return;
	}
	$saved = spokares_rota_write_rows( $check );
	spokares_unlock_option( 'spk_rota' );
	if ( $saved ) {
		WP_CLI::success( sprintf( 'Saved %s.', spokares_rota_dates_phrase( $saved, $check['cut'] ) ) );
	} elseif ( ! $lines ) {
		WP_CLI::success( 'Nothing changed, so nothing was saved.' );
	}
	spokares_cli_refuse( $lines, (bool) $saved );
}

/**
 * A row in words: "NZ2S; note: Bring a pencil; Winlink: …".
 *
 * @param array|null $row Stored row (null: Not posted yet).
 */
function spokares_cli_rota_row_words( ?array $row ): string {
	$row   = spokares_normalize_rota_row( $row ?? array() );
	$words = array( spokares_rota_state_words( $row['state'], $row['call'] ) );
	foreach ( array(
		'note'    => 'note',
		'wl_task' => 'Winlink',
		'wl_form' => 'form',
	) as $key => $name ) {
		if ( '' !== $row[ $key ] ) {
			$words[] = $name . ': ' . $row[ $key ];
		}
	}
	return implode( '; ', $words );
}

/**
 * A Tuesday the screen lists (the next 52), or an error.
 *
 * @param string $typed What was typed.
 */
function spokares_cli_rota_tuesday( string $typed ): string {
	$ymd = spokares_cli_date( $typed, 'Tuesday' );
	if ( 2 !== spokares_weekday( $ymd ) ) {
		WP_CLI::error( sprintf( '%s isn’t a Tuesday.', spokares_fmt_date( $ymd, 'long' ) ) );
	}
	if ( ! in_array( $ymd, wp_list_pluck( spokares_rota_rows( 52 ), 'date' ), true ) ) {
		WP_CLI::error( sprintf( '%s isn’t one of the next 52 Tuesdays, which the schedule lists.', spokares_fmt_date( $ymd, 'long' ) ) );
	}
	return $ymd;
}

/**
 * Net control as typed: [state, call sign as typed].
 *
 * @param string $typed A call sign, volunteer, none or tbd.
 * @return array{0:string,1:string}
 */
function spokares_cli_rota_who( string $typed ): array {
	$word = strtolower( trim( $typed ) );
	if ( in_array( $word, array( 'volunteer', 'open', 'volunteer needed' ), true ) ) {
		return array( 'open', '' );
	}
	if ( in_array( $word, array( 'none', 'no net' ), true ) ) {
		return array( 'none', '' );
	}
	if ( in_array( $word, array( 'tbd', 'not posted yet' ), true ) ) {
		return array( 'tbd', '' );
	}
	if ( '' === $word ) {
		WP_CLI::error( 'Say who has net control: a call sign, volunteer, none or tbd.' );
	}
	return array( 'call', $typed );
}

/* --------------------------------------------------------------- meeting */

/**
 * List each meeting's coming dates (about six months) with their
 * changes, and the one-off dates.
 *
 * ## OPTIONS
 *
 * [--format=<format>]
 * : table, csv, json or yaml.
 * ---
 * default: table
 * ---
 *
 * @param array $args  Positional arguments.
 * @param array $assoc Options.
 */
function spokares_cli_meeting_show( array $args, array $assoc ): void {
	spokares_cli_load();
	$data  = spokares_opt( 'spk_meetings' );
	$names = wp_list_pluck( $data['meetings'], 'name', 'id' );
	$rows  = array();
	foreach ( array_filter( $data['meetings'], 'spokares_meeting_shown' ) as $m ) {
		foreach ( spokares_meeting_admin_dates( $m ) as $d ) {
			$c      = spokares_meeting_change( $m['id'], $d );
			$status = 'on';
			if ( $c && 'cancelled' === $c['kind'] ) {
				$status = 'cancelled';
			} elseif ( $c && 'moved' === $c['kind'] ) {
				$status = 'moved to ' . $c['new_date'];
			}
			$rows[] = array(
				'meeting' => $m['id'],
				'name'    => $m['name'],
				'date'    => $d,
				'status'  => $status,
				'time'    => '',
				'note'    => $c ? (string) $c['note'] : '',
			);
		}
	}
	foreach ( spokares_meeting_extras() as $c ) {
		$rows[] = array(
			'meeting' => $c['meeting'],
			'name'    => spokares_meeting_extra_name( $c, $names ),
			'date'    => $c['date'],
			'status'  => 'one-off',
			'time'    => spokares_fmt_time_range( (string) $c['start'], (string) $c['end'] ),
			'note'    => (string) $c['note'],
		);
	}
	usort( $rows, static fn( $a, $b ) => strcmp( $a['date'] . $a['meeting'], $b['date'] . $b['meeting'] ) );
	spokares_cli_table( $assoc, $rows, array( 'meeting', 'name', 'date', 'status', 'time', 'note' ) );
}

/**
 * Cancel one date of a meeting (the Cancelled tick).
 *
 * ## OPTIONS
 *
 * <meeting>
 * : The meeting's id or name.
 *
 * <date>
 * : One of its dates, YYYY-MM-DD.
 *
 * [--note=<text>]
 * : Shown with the date (120 characters). Default: the date's note now.
 *
 * [--confirm]
 * : The note has a public agency phone number or e-mail address: publish it.
 *
 * [--dry-run]
 * : Run the checks and say what would change; save nothing.
 *
 * @param array $args  Positional arguments.
 * @param array $assoc Options.
 */
function spokares_cli_meeting_cancel( array $args, array $assoc ): void {
	spokares_cli_load();
	list( $mid, $d, $cur ) = spokares_cli_meeting_rule_date( $args );
	spokares_cli_meeting_save(
		array(
			$mid => array(
				$d => array(
					'cancelled' => '1',
					'moved'     => '',
					'note'      => spokares_cli_text( $assoc, 'note' ) ?? (string) ( $cur['note'] ?? '' ),
				),
			),
		),
		array(),
		array(),
		$assoc
	);
}

/**
 * Move one date of a meeting to another day (Moved to).
 *
 * ## OPTIONS
 *
 * <meeting>
 * : The meeting's id or name.
 *
 * <date>
 * : One of its dates, YYYY-MM-DD.
 *
 * <new-date>
 * : The day it moves to, YYYY-MM-DD (today or later).
 *
 * [--note=<text>]
 * : Shown with the date (120 characters). Default: the date's note now.
 *
 * [--confirm]
 * : The note has a public agency phone number or e-mail address: publish it.
 *
 * [--dry-run]
 * : Run the checks and say what would change; save nothing.
 *
 * @param array $args  Positional arguments.
 * @param array $assoc Options.
 */
function spokares_cli_meeting_move( array $args, array $assoc ): void {
	spokares_cli_load();
	list( $mid, $d, $cur ) = spokares_cli_meeting_rule_date( $args );
	spokares_cli_meeting_save(
		array(
			$mid => array(
				$d => array(
					'cancelled' => '',
					'moved'     => spokares_cli_date( (string) ( $args[2] ?? '' ), 'new date' ),
					'note'      => spokares_cli_text( $assoc, 'note' ) ?? (string) ( $cur['note'] ?? '' ),
				),
			),
		),
		array(),
		array(),
		$assoc
	);
}

/**
 * Set the note shown with one date (the meeting stays on, or stays
 * cancelled or moved). An empty note removes it.
 *
 * ## OPTIONS
 *
 * <meeting>
 * : The meeting's id or name.
 *
 * <date>
 * : One of its dates, YYYY-MM-DD.
 *
 * <note>
 * : The note (120 characters), e.g. "Starts at 10:00 AM this time".
 *
 * [--confirm]
 * : The note has a public agency phone number or e-mail address: publish it.
 *
 * [--dry-run]
 * : Run the checks and say what would change; save nothing.
 *
 * @param array $args  Positional arguments.
 * @param array $assoc Options.
 */
function spokares_cli_meeting_note( array $args, array $assoc ): void {
	spokares_cli_load();
	list( $mid, $d, $cur ) = spokares_cli_meeting_rule_date( $args );
	spokares_cli_meeting_save(
		array(
			$mid => array(
				$d => array(
					'cancelled' => $cur && 'cancelled' === $cur['kind'] ? '1' : '',
					'moved'     => $cur && 'moved' === $cur['kind'] ? (string) $cur['new_date'] : '',
					'note'      => (string) ( $args[2] ?? '' ),
				),
			),
		),
		array(),
		array(),
		$assoc
	);
}

/**
 * Put a date back as the schedule has it: a cancelled or moved date is
 * back on, a note is removed, a one-off date is removed.
 *
 * ## OPTIONS
 *
 * <meeting>
 * : The meeting's id or name (other for another meeting's one-off date).
 *
 * <date>
 * : The date, YYYY-MM-DD.
 *
 * [--dry-run]
 * : Say what would change; save nothing.
 *
 * @param array $args  Positional arguments.
 * @param array $assoc Options.
 */
function spokares_cli_meeting_clear( array $args, array $assoc ): void {
	spokares_cli_load();
	$mid = spokares_cli_meeting_id( (string) ( $args[0] ?? '' ), true );
	$d   = spokares_cli_date( (string) ( $args[1] ?? '' ), 'date' );
	foreach ( spokares_opt( 'spk_meetings' )['changes'] as $c ) {
		if ( 'extra' === $c['kind'] && $c['meeting'] === $mid && $c['date'] === $d ) {
			spokares_cli_meeting_save( array(), array( $mid . '|' . $d ), array(), $assoc );
			return;
		}
	}
	list( $mid, $d ) = spokares_cli_meeting_rule_date( $args );
	spokares_cli_meeting_save(
		array(
			$mid => array(
				$d => array(
					'cancelled' => '',
					'moved'     => '',
					'note'      => '',
				),
			),
		),
		array(),
		array(),
		$assoc
	);
}

/**
 * Add a one-off date (Add a one-off date): an extra date of a meeting, or
 * of another meeting with its own name.
 *
 * ## OPTIONS
 *
 * <meeting>
 * : The meeting's id or name, or other (then --name is needed).
 *
 * <date>
 * : The date, YYYY-MM-DD (today or later, within a year).
 *
 * [--name=<name>]
 * : Another meeting's name (80 characters).
 *
 * [--start=<time>]
 * : Start time, like 18:00 or 6:00 PM.
 *
 * [--end=<time>]
 * : End time (needs a start).
 *
 * [--note=<text>]
 * : Shown with the date (120 characters), e.g. "Holiday potluck".
 *
 * [--confirm]
 * : The name or note has a public agency phone number or e-mail
 * address: publish it.
 *
 * [--dry-run]
 * : Run the checks and say what would change; save nothing.
 *
 * @param array $args  Positional arguments.
 * @param array $assoc Options.
 */
function spokares_cli_meeting_extra( array $args, array $assoc ): void {
	spokares_cli_load();
	$extra = array(
		'meeting' => spokares_cli_meeting_id( (string) ( $args[0] ?? '' ), true ),
		'date'    => spokares_cli_date( (string) ( $args[1] ?? '' ), 'date' ),
		'name'    => (string) spokares_cli_text( $assoc, 'name' ),
		'start'   => spokares_cli_meeting_time( (string) spokares_cli_text( $assoc, 'start' ) ),
		'end'     => spokares_cli_meeting_time( (string) spokares_cli_text( $assoc, 'end' ) ),
		'note'    => (string) spokares_cli_text( $assoc, 'note' ),
	);
	if ( spokares_cli_flag( $assoc, 'confirm' ) ) {
		$extra['confirm'] = '1';
	}
	spokares_cli_meeting_save( array(), array(), $extra, $assoc );
}

/**
 * Check as the screen's Save does, then save (or say what would change).
 *
 * @param array $posted Meeting => date => row as the form posts it (the
 *                      hash is added here).
 * @param array $remove One-off dates to remove.
 * @param array $extra  A one-off date to add.
 * @param array $assoc  Options (dry-run, confirm).
 */
function spokares_cli_meeting_save( array $posted, array $remove, array $extra, array $assoc ): void {
	$dry = spokares_cli_flag( $assoc, 'dry-run' );
	if ( ! $dry ) {
		spokares_cli_need_user( 'spokares_edit_rota' );
		spokares_lock_option( 'spk_meetings' );
	}
	// The clash hash of each date as it is now (as the screen would show it).
	foreach ( $posted as $mid => $rows ) {
		foreach ( $rows as $d => $row ) {
			$posted[ $mid ][ $d ]['h'] = spokares_change_hash( spokares_meeting_change( (string) $mid, (string) $d ) );
			if ( spokares_cli_flag( $assoc, 'confirm' ) ) {
				$posted[ $mid ][ $d ]['confirm'] = '1';
			}
		}
	}
	$check = spokares_meetings_check( $posted, $remove, $extra );
	$lines = spokares_cli_problem_lines(
		$check['errors'],
		static function ( string $key ): string {
			if ( str_starts_with( $key, 'extra' ) ) {
				return 'extra-name' === $key ? 'The one-off date, Name' : ( 'extra-note' === $key ? 'The one-off date, Note' : 'The one-off date' );
			}
			list( $mid, $rest ) = array_pad( explode( '|', $key, 2 ), 2, '' );
			$d                  = substr( $rest, 0, 10 );
			return $mid . ' ' . spokares_fmt_date( $d, 'short' ) . ( str_ends_with( $key, '-note' ) ? ', Note' : '' );
		}
	);
	$done  = $check['done'];
	ksort( $done );
	if ( $dry ) {
		if ( $done ) {
			WP_CLI::log( sprintf( 'Would save: %s.', implode( '; ', $done ) ) );
		} elseif ( ! $lines ) {
			WP_CLI::log( 'Nothing would change.' );
		}
		foreach ( $lines as $line ) {
			WP_CLI::warning( 'Would not save ' . $line );
		}
		WP_CLI::success( 'Dry run: nothing was saved.' );
		return;
	}
	spokares_meetings_write( $check );
	spokares_unlock_option( 'spk_meetings' );
	if ( $done ) {
		WP_CLI::success( sprintf( 'Saved: %s.', implode( '; ', $done ) ) );
	} elseif ( ! $lines ) {
		WP_CLI::success( 'Nothing changed, so nothing was saved.' );
	}
	spokares_cli_refuse( $lines, (bool) $done );
}

/**
 * The meeting and one of its dates the screen lists (from today, about
 * six months, or a later date with a change), and its change now.
 *
 * @param array $args <meeting> <date>.
 * @return array{0:string,1:string,2:array|null}
 */
function spokares_cli_meeting_rule_date( array $args ): array {
	$mid = spokares_cli_meeting_id( (string) ( $args[0] ?? '' ), false );
	$d   = spokares_cli_date( (string) ( $args[1] ?? '' ), 'date' );
	foreach ( spokares_opt( 'spk_meetings' )['meetings'] as $m ) {
		if ( $m['id'] === $mid && ! in_array( $d, spokares_meeting_admin_dates( $m ), true ) ) {
			WP_CLI::error( sprintf( '%1$s isn’t one of the coming dates of %2$s (see wp spokares meeting show). For a date of its own, use wp spokares meeting extra.', spokares_fmt_date( $d, 'long' ), $m['name'] ) );
		}
	}
	return array( $mid, $d, spokares_meeting_change( $mid, $d ) );
}

/**
 * A meeting by id or name (case doesn't matter), or an error naming them.
 * The screen changes dates only of the meetings the site shows; a one-off
 * date may go under any meeting, or under "other".
 *
 * @param string $typed   What was typed.
 * @param bool   $one_off For a one-off date (any meeting, or other).
 */
function spokares_cli_meeting_id( string $typed, bool $one_off ): string {
	$data  = spokares_opt( 'spk_meetings' );
	$list  = $one_off ? $data['meetings'] : array_values( array_filter( $data['meetings'], 'spokares_meeting_shown' ) );
	$typed = trim( $typed );
	if ( $one_off && in_array( strtolower( $typed ), array( 'other', spokares_meeting_other_id() ), true ) ) {
		return spokares_meeting_other_id();
	}
	$names = array();
	foreach ( $list as $m ) {
		if ( $m['id'] === $typed || 0 === strcasecmp( $m['name'], $typed ) || sanitize_key( $typed ) === $m['id'] ) {
			return $m['id'];
		}
		$names[] = $m['id'] . ' (' . $m['name'] . ')';
	}
	if ( $one_off ) {
		$names[] = 'other';
	}
	WP_CLI::error( sprintf( 'No meeting called "%1$s". Use one of: %2$s.', $typed, implode( ', ', $names ) ) );
	return '';
}

/**
 * A time as the form's time box sends it (HH:MM), from "18:00", "6:00 PM"
 * or "6pm"; anything else as typed, for the check to refuse.
 *
 * @param string $typed What was typed.
 */
function spokares_cli_meeting_time( string $typed ): string {
	$typed = trim( $typed );
	if ( preg_match( '/^(\d{1,2})(?::(\d{2}))?\s*([ap])\.?m\.?$/i', $typed, $m ) && (int) $m[1] >= 1 && (int) $m[1] <= 12 ) {
		$h = (int) $m[1] % 12 + ( 'p' === strtolower( $m[3] ) ? 12 : 0 );
		return sprintf( '%02d:%02d', $h, (int) ( $m[2] ?? 0 ) );
	}
	if ( preg_match( '/^(\d):(\d{2})$/', $typed, $m ) ) {
		return '0' . $typed;
	}
	return $typed;
}

/* ----------------------------------------------------------------- event */

/*
 * The event form saves inside post.php (its checks run on the whole form as
 * the editor submits it), so these commands don't replay it. They run the
 * form's own checker, spokares_event_problems(), on the event's stored
 * fields with the new dates (the date order, the times on those dates, a
 * date that has passed needing --confirm), and write only the When fields
 * or the Cancelled tick. The words of an event are edited in its form.
 */

/**
 * List events: the coming and current ones (or every one with --all).
 *
 * ## OPTIONS
 *
 * [--all]
 * : Past events too.
 *
 * [--format=<format>]
 * : table, csv, json or yaml.
 * ---
 * default: table
 * ---
 *
 * @param array $args  Positional arguments.
 * @param array $assoc Options.
 */
function spokares_cli_event_list( array $args, array $assoc ): void {
	spokares_cli_load();
	$query = array(
		'post_type'      => 'spk_event',
		'post_status'    => array( 'publish', 'future', 'draft', 'pending', 'private' ),
		'posts_per_page' => -1,
		'meta_key'       => 'spk_sort', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- a WP-CLI listing.
		'orderby'        => 'meta_value',
		'order'          => 'ASC',
		'no_found_rows'  => true,
	);
	if ( ! spokares_cli_flag( $assoc, 'all' ) ) {
		$query['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- a WP-CLI listing.
			array(
				'key'     => 'spk_end_sort',
				'value'   => spokares_today(),
				'compare' => '>=',
			),
		);
	}
	$kinds = spokares_event_kinds();
	$rows  = array();
	foreach ( get_posts( $query ) as $post ) {
		$ev     = spokares_event_data( $post );
		$rows[] = array(
			'id'        => $post->ID,
			'status'    => $post->post_status,
			'when'      => spokares_cli_event_when( $ev ),
			'kind'      => $kinds[ $ev['kind'] ] ?? $ev['kind'],
			'title'     => html_entity_decode( $post->post_title, ENT_QUOTES, 'UTF-8' ),
			'cancelled' => $ev['cancelled'] ? 'cancelled' : '',
		);
	}
	spokares_cli_table( $assoc, $rows, array( 'id', 'status', 'when', 'kind', 'title', 'cancelled' ) );
}

/**
 * Set an event's first (and last) day. Its times, words and status stay.
 *
 * ## OPTIONS
 *
 * <id>
 * : The event's ID (wp spokares event list).
 *
 * <first>
 * : The first day, YYYY-MM-DD.
 *
 * [<last>]
 * : The last day of an event over several days. Left out: a one-day event.
 *
 * [--confirm]
 * : The date has passed and is right (the form's "The date is right" tick).
 *
 * [--dry-run]
 * : Run the checks and say what would change; save nothing.
 *
 * @param array $args  Positional arguments.
 * @param array $assoc Options.
 */
function spokares_cli_event_set_date( array $args, array $assoc ): void {
	spokares_cli_load();
	$post  = spokares_cli_event_post( (string) ( $args[0] ?? '' ) );
	$first = spokares_cli_date( (string) ( $args[1] ?? '' ), 'first day' );
	$last  = isset( $args[2] ) ? spokares_cli_date( (string) $args[2], 'last day' ) : '';
	$dry   = spokares_cli_flag( $assoc, 'dry-run' );
	if ( ! $dry ) {
		spokares_cli_need_user( 'edit_post', $post->ID );
	}
	$v          = spokares_event_form_values( $post, false );
	$was_words  = spokares_cli_event_when( spokares_event_data( $post ) );
	$v['mode']  = 'date';
	$v['start'] = $first;
	$v['end']   = $last === $first ? '' : $last;
	if ( spokares_cli_flag( $assoc, 'confirm' ) ) {
		$v['confirm'] = array( 'past' );
	}
	$confirmed = get_post_meta( $post->ID, 'spk_confirmed', true );
	$confirmed = is_array( $confirmed ) ? $confirmed : array();
	$result    = spokares_event_problems( $v, $confirmed, spokares_event_was( $post ) );
	$sentences = spokares_event_problem_sentences( $result );
	$lines     = array();
	$others    = array();
	foreach ( $sentences as $field => $sentence ) {
		$name = spokares_event_field_name( (string) $field );
		if ( in_array( $field, array( 'start', 'end', 't_start', 't_end' ), true ) ) {
			$past    = isset( $result['confirm'][ $field ] ) && spokares_event_is_past_hit( $result['confirm'][ $field ] );
			$lines[] = $name . ': ' . $sentence . ( $past ? ' (Add --confirm to tick that box.)' : '' );
		} else {
			$others[] = $name . ': ' . $sentence;
		}
	}
	foreach ( $others as $line ) {
		WP_CLI::warning( 'The event form also says (not changed here): ' . $line );
	}
	spokares_cli_refuse( $lines );

	$end  = spokares_event_spans_days( $v ) ? $v['end'] : '';
	$same = 'date' === (string) get_post_meta( $post->ID, 'spk_date_mode', true ) && (string) get_post_meta( $post->ID, 'spk_start', true ) === $first && (string) get_post_meta( $post->ID, 'spk_end', true ) === $end;
	$now  = spokares_fmt_date_range( $first, $end );
	if ( $same ) {
		WP_CLI::success( 'Nothing changed, so nothing was saved.' );
		return;
	}
	if ( $dry ) {
		WP_CLI::log( sprintf( 'Would save %1$s: %2$s (was %3$s)', html_entity_decode( $post->post_title, ENT_QUOTES, 'UTF-8' ), $now, $was_words ) );
		WP_CLI::success( 'Dry run: nothing was saved.' );
		return;
	}
	update_post_meta( $post->ID, 'spk_date_mode', 'date' );
	update_post_meta( $post->ID, 'spk_start', $first );
	if ( '' !== $end ) {
		update_post_meta( $post->ID, 'spk_end', $end );
	} else {
		delete_post_meta( $post->ID, 'spk_end' );
	}
	// "The date is right": these dates are not asked about again (as the form).
	if ( spokares_cli_flag( $assoc, 'confirm' ) ) {
		$confirmed['past'] = spokares_event_past_hash( $v );
		update_post_meta( $post->ID, 'spk_confirmed', $confirmed );
	}
	spokares_update_event_sort( $post->ID );
	clean_post_cache( $post->ID );
	spokares_purge_cache();
	WP_CLI::success( sprintf( 'Saved %1$s: %2$s (was %3$s).', html_entity_decode( $post->post_title, ENT_QUOTES, 'UTF-8' ), $now, $was_words ) );
}

/**
 * Call an event off (the form's Cancelled tick), or with --undo put it
 * back on. Only an event on a date can be called off.
 *
 * ## OPTIONS
 *
 * <id>
 * : The event's ID (wp spokares event list).
 *
 * [--undo]
 * : Untick Cancelled: the event is on again.
 *
 * [--dry-run]
 * : Say what would change; save nothing.
 *
 * @param array $args  Positional arguments.
 * @param array $assoc Options.
 */
function spokares_cli_event_cancel( array $args, array $assoc ): void {
	spokares_cli_load();
	$post = spokares_cli_event_post( (string) ( $args[0] ?? '' ) );
	$dry  = spokares_cli_flag( $assoc, 'dry-run' );
	if ( ! $dry ) {
		spokares_cli_need_user( 'edit_post', $post->ID );
	}
	$on    = ! spokares_cli_flag( $assoc, 'undo' );
	$title = html_entity_decode( $post->post_title, ENT_QUOTES, 'UTF-8' );
	$ev    = spokares_event_data( $post );
	if ( $on && 'date' !== $ev['mode'] ) {
		WP_CLI::error( sprintf( '%s has no date, so it can’t be called off. Give it a date first (wp spokares event set-date).', $title ) );
	}
	if ( $on === $ev['cancelled'] ) {
		WP_CLI::success( 'Nothing changed, so nothing was saved.' );
		return;
	}
	$words = $on ? 'cancelled' : 'on again';
	if ( $dry ) {
		WP_CLI::log( sprintf( 'Would save %1$s: %2$s.', $title, $words ) );
		WP_CLI::success( 'Dry run: nothing was saved.' );
		return;
	}
	if ( $on ) {
		update_post_meta( $post->ID, 'spk_cancelled', '1' );
	} else {
		delete_post_meta( $post->ID, 'spk_cancelled' );
	}
	clean_post_cache( $post->ID );
	spokares_purge_cache();
	WP_CLI::success( sprintf( 'Saved %1$s: %2$s.', $title, $words ) );
}

/**
 * An event by ID, or an error.
 *
 * @param string $typed What was typed.
 */
function spokares_cli_event_post( string $typed ): WP_Post {
	$post = ctype_digit( $typed ) ? get_post( (int) $typed ) : null;
	if ( ! $post || 'spk_event' !== $post->post_type || in_array( $post->post_status, array( 'trash', 'auto-draft' ), true ) ) {
		WP_CLI::error( sprintf( 'No event with ID "%s" (see wp spokares event list).', $typed ) );
	}
	return $post;
}

/**
 * An event's When in words.
 *
 * @param array $ev spokares_event_data().
 */
function spokares_cli_event_when( array $ev ): string {
	switch ( $ev['mode'] ?? 'date' ) {
		case 'postponed':
			return 'Postponed';
		case 'not-posted':
			return 'Date not posted yet';
		case 'as-requested':
			return 'As requested';
	}
	$when = spokares_fmt_date_range( (string) $ev['start'], (string) $ev['end'] );
	return '' !== $when ? $when : 'No date';
}

if ( defined( 'WP_CLI' ) && WP_CLI && class_exists( 'WP_CLI' ) ) {
	foreach ( array(
		'rota show'      => 'spokares_cli_rota_show',
		'rota set'       => 'spokares_cli_rota_set',
		'rota paste'     => 'spokares_cli_rota_paste',
		'meeting show'   => 'spokares_cli_meeting_show',
		'meeting cancel' => 'spokares_cli_meeting_cancel',
		'meeting move'   => 'spokares_cli_meeting_move',
		'meeting note'   => 'spokares_cli_meeting_note',
		'meeting clear'  => 'spokares_cli_meeting_clear',
		'meeting extra'  => 'spokares_cli_meeting_extra',
		'event list'     => 'spokares_cli_event_list',
		'event set-date' => 'spokares_cli_event_set_date',
		'event cancel'   => 'spokares_cli_event_cancel',
	) as $spokares_cli_name => $spokares_cli_callback ) {
		WP_CLI::add_command( 'spokares ' . $spokares_cli_name, $spokares_cli_callback );
	}
	unset( $spokares_cli_name, $spokares_cli_callback );
}
