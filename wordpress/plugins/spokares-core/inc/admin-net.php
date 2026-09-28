<?php
/**
 * Net Settings (ux/SPEC.md §3.3): the Tuesday net's time and repeater, the
 * alternate repeater, which Tuesdays are Winlink, simplex or GMRS nights,
 * and two lines of wording. Each section ends in what members see.
 *
 * @package spokares-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Offset choices.
 */
function spokares_offsets(): array {
	return array( '+600 kHz', '-600 kHz', '+5 MHz', '-5 MHz' );
}

/**
 * CTCSS tone choices, written as the site prints them ("100 Hz", "103.5 Hz").
 */
function spokares_tones(): array {
	$tones = array( 67.0, 69.3, 71.9, 74.4, 77.0, 79.7, 82.5, 85.4, 88.5, 91.5, 94.8, 97.4, 100.0, 103.5, 107.2, 110.9, 114.8, 118.8, 123.0, 127.3, 131.8, 136.5, 141.3, 146.2, 151.4, 156.7, 162.2, 167.9, 173.8, 179.9, 186.2, 192.8, 203.5, 210.7, 218.1, 225.7, 233.6, 241.8, 250.3 );
	return array_map( static fn( $t ) => ( floor( $t ) === $t ? (string) (int) $t : (string) $t ) . ' Hz', $tones );
}

/**
 * The amateur bands a club repeater or net frequency can be in, MHz (US
 * allocations: 10 m, 6 m, 2 m, 1.25 m, 70 cm, 33 cm). Anything outside them
 * (county, hospital, fire, GMRS, the 800 MHz band) is never published.
 *
 * @return array<int,array{0:float,1:float}>
 */
function spokares_amateur_bands(): array {
	return array(
		array( 28.0, 29.7 ),
		array( 50.0, 54.0 ),
		array( 144.0, 148.0 ),
		array( 219.0, 220.0 ),
		array( 222.0, 225.0 ),
		array( 420.0, 450.0 ),
		array( 902.0, 928.0 ),
	);
}

/**
 * Is this a frequency we may publish? Format 000.000, and inside an amateur
 * band (so never the 800 MHz public-safety band, hospital, fire or GMRS).
 *
 * @param string $freq Frequency.
 */
function spokares_freq_ok( string $freq ): bool {
	if ( ! preg_match( '/^\d{2,3}\.\d{3}$/', $freq ) ) {
		return false;
	}
	$f = (float) $freq;
	foreach ( spokares_amateur_bands() as $band ) {
		if ( $f >= $band[0] && $f <= $band[1] ) {
			return true;
		}
	}
	return false;
}

/**
 * The one line under a frequency the save refuses: the shape, or the band
 * (and what the site keeps instead). admin-forms.js says the same live.
 *
 * @param string $typed  Typed frequency.
 * @param string $stored The stored frequency.
 */
function spokares_freq_problem( string $typed, string $stored ): string {
	if ( ! preg_match( '/^\d{2,3}\.\d{3}$/', $typed ) ) {
		return __( 'Three digits after the point, like 147.300.', 'spokares-core' );
	}
	return '' !== $stored
		/* translators: %s: the stored frequency, e.g. "147.300". */
		? sprintf( __( 'Not an amateur frequency; the site keeps %s.', 'spokares-core' ), $stored )
		: __( 'Not an amateur frequency.', 'spokares-core' );
}

/**
 * A select for a list of values, keeping an unknown stored value selectable.
 *
 * @param string   $name    Field name.
 * @param string   $current Current value.
 * @param string[] $choices Choices.
 * @param string   $none    Label of the empty choice ('' for none).
 * @param string   $id      Element id.
 */
function spokares_select( string $name, string $current, array $choices, string $none, string $id ): void {
	if ( '' !== $current && ! in_array( $current, $choices, true ) ) {
		array_unshift( $choices, $current );
	}
	echo '<select name="' . esc_attr( $name ) . '" id="' . esc_attr( $id ) . '">';
	if ( '' !== $none ) {
		echo '<option value="">' . esc_html( $none ) . '</option>';
	}
	foreach ( $choices as $c ) {
		echo '<option value="' . esc_attr( $c ) . '"' . selected( $c, $current, false ) . '>' . esc_html( spokares_minus( $c ) ) . '</option>';
	}
	echo '</select>';
}

/**
 * The kinds of Tuesday net, as the Which Tuesdays selects name them.
 *
 * @return array<string,string> Value => label.
 */
function spokares_net_kinds(): array {
	return array(
		'regular' => __( 'Regular net', 'spokares-core' ),
		'winlink' => __( 'Winlink night', 'spokares-core' ),
		'simplex' => __( 'Starts on simplex', 'spokares-core' ),
		'gmrs'    => __( 'GMRS net', 'spokares-core' ),
	);
}

/**
 * What each Tuesday of the month is (1-5 => kind), as the schedule reads
 * the three week lists: a week in two lists goes to the first of simplex,
 * Winlink, GMRS (spokares_net_weeks()).
 *
 * @param array $nets spk_nets.
 * @return array<int,string>
 */
function spokares_net_week_kinds( array $nets ): array {
	$weeks = spokares_net_weeks( $nets );
	$out   = array();
	for ( $n = 1; $n <= 5; $n++ ) {
		$out[ $n ] = 'regular';
		foreach ( array( 'simplex', 'winlink', 'gmrs' ) as $kind ) {
			if ( in_array( $n, $weeks[ $kind ], true ) ) {
				$out[ $n ] = $kind;
				break;
			}
		}
	}
	return $out;
}

/**
 * Plain text of a piece of the site's HTML, as a reader sees it.
 *
 * @param string $html HTML.
 */
function spokares_net_plain( string $html ): string {
	$text = html_entity_decode( wp_strip_all_tags( $html ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	return trim( (string) preg_replace( '/\s+/u', ' ', str_replace( "\u{00A0}", ' ', $text ) ) );
}

/**
 * The "Members see" lines for a set of net facts: what the site prints for
 * them. The site's own formatters and the Other nets view are run with these
 * facts in place of the stored ones, so the lines can't drift from the pages
 * (no ", ," for an empty GMRS time; the alternate row as How it works shows
 * it; a week in two lists listed once).
 *
 * @param array $nets  spk_nets.
 * @param array $radio spk_radio.
 * @return array{bar:string,alt:string,winlink:string,simplex:string,gmrs:string}
 */
function spokares_net_preview_lines( array $nets, array $radio ): array {
	$use_nets  = static fn() => $nets;
	$use_radio = static fn() => $radio;
	add_filter( 'pre_option_spk_nets', $use_nets, PHP_INT_MAX );
	add_filter( 'pre_option_spk_radio', $use_radio, PHP_INT_MAX );
	spokares_opt_flush();
	try {
		$nets  = spokares_opt( 'spk_nets' );
		$radio = spokares_opt( 'spk_radio' );
		$a     = $radio['alternate'];
		$lines = array(
			// For members: the settings bar prints the time, then the bar line.
			'bar'     => implode( ' · ', array_filter( array( spokares_fmt_time( $nets['net_time'] ), spokares_radio_line( 'bar' ) ) ) ),
			// How it works: the settings box's Alternate row.
			'alt'     => ( $a['show'] && '' !== $a['freq'] ) ? __( 'Alternate', 'spokares-core' ) . ' · ' . spokares_radio_line( 'alt-display' ) : __( 'Not shown on How it works.', 'spokares-core' ),
			'winlink' => '',
			'simplex' => '',
			'gmrs'    => '',
		);
		// How it works › Other nets, as it prints: one bullet per group.
		preg_match_all( '#<li\b[^>]*>(.*?)</li>#s', spokares_render_net( array( 'view' => 'other-nets' ) ), $m );
		foreach ( $m[1] as $li ) {
			$text = spokares_net_plain( $li );
			if ( str_starts_with( $text, __( 'Winlink nights:', 'spokares-core' ) ) ) {
				$lines['winlink'] = $text;
			} elseif ( str_starts_with( $text, __( 'ACS GMRS net:', 'spokares-core' ) ) ) {
				$lines['gmrs'] = $text;
			} else {
				$lines['simplex'] = $text;
			}
		}
	} finally {
		remove_filter( 'pre_option_spk_nets', $use_nets, PHP_INT_MAX );
		remove_filter( 'pre_option_spk_radio', $use_radio, PHP_INT_MAX );
		spokares_opt_flush();
	}
	return array_map( static fn( $l ) => str_replace( "\u{00A0}", ' ', (string) $l ), $lines );
}

/**
 * Net Settings as the form shows them (field key => text), recorded in the
 * form when it is drawn. A save changes only the fields the editor changed,
 * so an older screen never puts back someone else's newer save (as the Net
 * Control Schedule and Meeting Schedule do). The week lists are as the
 * Which Tuesdays selects show them: each week in one list only.
 *
 * @param array $nets  spk_nets.
 * @param array $radio spk_radio.
 */
function spokares_net_form_state( array $nets, array $radio ): array {
	$p     = $radio['primary'];
	$a     = $radio['alternate'];
	$kinds = spokares_net_week_kinds( $nets );
	$weeks = static fn( string $kind ): string => implode( ',', array_keys( $kinds, $kind, true ) );
	return array(
		'p_call'         => strtoupper( trim( (string) $p['call'] ) ),
		'p_freq'         => (string) $p['freq'],
		'p_offset'       => (string) $p['offset'],
		'p_tone'         => (string) $p['tone'],
		'net_time'       => (string) $nets['net_time'],
		'a_freq'         => (string) $a['freq'],
		'a_offset'       => (string) $a['offset'],
		'a_tone'         => (string) $a['tone'],
		'a_show'         => $a['show'] ? '1' : '',
		'a_needs_check'  => $a['needs_check'] ? '1' : '',
		'winlink_nth'    => $weeks( 'winlink' ),
		'simplex_nth'    => $weeks( 'simplex' ),
		'gmrs_nth'       => $weeks( 'gmrs' ),
		'gmrs_time'      => (string) $nets['gmrs_time'],
		'needs_check'    => $nets['needs_check'] ? '1' : '',
		'winlink_howto'  => trim( (string) preg_replace( '/\s+/', ' ', (string) $nets['winlink_howto'] ) ),
		'open_slot_line' => (string) $nets['open_slot_line'],
	);
}

/**
 * The fields' names for the notices, in lower case ("Not saved: the
 * frequency (outlined in red).").
 */
function spokares_net_field_names(): array {
	return array(
		'p_call'         => __( 'the repeater call sign', 'spokares-core' ),
		'p_freq'         => __( 'the frequency', 'spokares-core' ),
		'p_offset'       => __( 'the offset', 'spokares-core' ),
		'p_tone'         => __( 'the tone', 'spokares-core' ),
		'net_time'       => __( 'the net time', 'spokares-core' ),
		'a_freq'         => __( 'the alternate frequency', 'spokares-core' ),
		'a_offset'       => __( 'the alternate offset', 'spokares-core' ),
		'a_tone'         => __( 'the alternate tone', 'spokares-core' ),
		'a_show'         => __( '“Show on How it works”', 'spokares-core' ),
		'a_needs_check'  => __( 'the alternate repeater’s “Needs checking”', 'spokares-core' ),
		'winlink_nth'    => __( 'which Tuesdays', 'spokares-core' ),
		'simplex_nth'    => __( 'which Tuesdays', 'spokares-core' ),
		'gmrs_nth'       => __( 'which Tuesdays', 'spokares-core' ),
		'gmrs_time'      => __( 'the GMRS net time', 'spokares-core' ),
		'needs_check'    => __( 'the nets’ “Needs checking”', 'spokares-core' ),
		'winlink_howto'  => __( 'the Winlink how-to wording', 'spokares-core' ),
		'open_slot_line' => __( 'the asking-for-volunteers line', 'spokares-core' ),
	);
}

/**
 * What a save changed, in the screen's order and its own words: "tone
 * 103.5 Hz (was 100 Hz)", "2nd Tuesday Winlink night (was regular net)",
 * "Winlink how-to wording changed".
 *
 * @param array $was       spokares_net_form_state() before the save.
 * @param array $now       The same after it.
 * @param array $was_weeks spokares_net_week_kinds() before the save.
 * @param array $now_weeks The same after it.
 * @return string[]
 */
function spokares_net_changes( array $was, array $now, array $was_weeks, array $now_weeks ): array {
	$none  = __( 'none', 'spokares-core' );
	$time  = static fn( string $v ): string => '' !== $v ? spokares_fmt_time( $v ) : $none;
	$mhz   = static fn( string $v ): string => '' !== $v ? $v . ' MHz' : $none;
	$plain = static fn( string $v ): string => '' !== $v ? spokares_minus( $v ) : $none;
	$tone  = static fn( string $v ): string => '' !== $v ? $v : __( 'no tone', 'spokares-core' );
	$kinds = spokares_net_kinds();
	$kind  = static fn( string $k ): string => in_array( $k, array( 'regular', 'simplex' ), true ) ? strtolower( $kinds[ $k ] ) : $kinds[ $k ];
	$out   = array();
	$pair  = static function ( string $key, string $label, callable $show ) use ( $was, $now, &$out ): void {
		if ( $was[ $key ] !== $now[ $key ] ) {
			/* translators: 1: a field's name, 2: its new value, 3: its old value. */
			$out[] = sprintf( __( '%1$s %2$s (was %3$s)', 'spokares-core' ), $label, $show( $now[ $key ] ), $show( $was[ $key ] ) );
		}
	};
	$pair( 'net_time', __( 'net time', 'spokares-core' ), $time );
	$pair( 'p_call', __( 'repeater call sign', 'spokares-core' ), $plain );
	$pair( 'p_freq', __( 'frequency', 'spokares-core' ), $mhz );
	$pair( 'p_offset', __( 'offset', 'spokares-core' ), $plain );
	$pair( 'p_tone', __( 'tone', 'spokares-core' ), $tone );
	if ( $was['a_show'] !== $now['a_show'] ) {
		$out[] = '1' === $now['a_show'] ? __( 'alternate repeater shown on How it works', 'spokares-core' ) : __( 'alternate repeater taken off How it works', 'spokares-core' );
	}
	$pair( 'a_freq', __( 'alternate frequency', 'spokares-core' ), $mhz );
	$pair( 'a_offset', __( 'alternate offset', 'spokares-core' ), $plain );
	$pair( 'a_tone', __( 'alternate tone', 'spokares-core' ), $tone );
	for ( $n = 1; $n <= 5; $n++ ) {
		if ( $was_weeks[ $n ] !== $now_weeks[ $n ] ) {
			/* translators: 1: "2nd", 2: the new kind of net, 3: the old one. */
			$out[] = sprintf( __( '%1$s Tuesday %2$s (was %3$s)', 'spokares-core' ), 5 === $n ? __( '5th', 'spokares-core' ) : spokares_ordinal( $n ), $kind( $now_weeks[ $n ] ), $kind( $was_weeks[ $n ] ) );
		}
	}
	$pair( 'gmrs_time', __( 'GMRS net time', 'spokares-core' ), $time );
	if ( $was['winlink_howto'] !== $now['winlink_howto'] ) {
		$out[] = __( 'Winlink how-to wording changed', 'spokares-core' );
	}
	if ( $was['open_slot_line'] !== $now['open_slot_line'] ) {
		$out[] = __( 'asking-for-volunteers line changed', 'spokares-core' );
	}
	if ( $was['a_needs_check'] !== $now['a_needs_check'] || $was['needs_check'] !== $now['needs_check'] ) {
		$out[] = __( '“Needs checking” changed', 'spokares-core' );
	}
	return $out;
}

/**
 * A field's hint, or the problem that replaces it (one line, same id, so
 * the field's aria-describedby reads whichever is there).
 *
 * @param array  $errors Field key => sentence.
 * @param string $key    Field key.
 * @param string $id     Element id.
 * @param string $hint   The hint.
 */
function spokares_net_hint( array $errors, string $key, string $id, string $hint ): void {
	$problem = isset( $errors[ $key ] );
	printf(
		'<p class="%1$s" id="%2$s" data-hint="%3$s">%4$s</p>',
		$problem ? 'spk-error-text' : 'description',
		esc_attr( $id ),
		esc_attr( $hint ),
		esc_html( $problem ? $errors[ $key ] : $hint )
	);
}

/**
 * A "Members see" row.
 *
 * @param string $key  The data-preview hook.
 * @param string $text The line.
 */
function spokares_net_members_see( string $key, string $text ): void {
	?>
	<tr class="spk-members-see">
		<th scope="row"><?php esc_html_e( 'Members see', 'spokares-core' ); ?></th>
		<td data-preview="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $text ); ?></td>
	</tr>
	<?php
}

/**
 * The Net Settings screen.
 */
function spokares_net_details_page(): void {
	if ( ! current_user_can( 'spokares_edit_net_details' ) ) {
		return;
	}
	$nets     = spokares_opt( 'spk_nets' );
	$radio    = spokares_opt( 'spk_radio' );
	$retained = spokares_retained( 'spokares-net-details' );
	$held     = $retained['values'];
	$errors   = $retained['errors'];
	$admin    = current_user_can( 'manage_options' );
	$v        = static fn( string $key, $stored ) => array_key_exists( $key, $held ) ? $held[ $key ] : $stored;
	$p        = $radio['primary'];
	$a        = $radio['alternate'];
	$lines    = spokares_net_preview_lines( $nets, $radio );
	$state    = spokares_net_form_state( $nets, $radio );
	$kinds    = spokares_net_week_kinds( $nets );
	$saved    = get_option( 'spk_net_saved', array() );
	$saved    = is_array( $saved ) ? spokares_saved_line( $saved ) : '';
	$freqhint = __( 'Three digits after the point, like 147.300.', 'spokares-core' );
	$others   = array_filter( array( $lines['winlink'], $lines['simplex'], $lines['gmrs'] ) );
	$week_err = array_values( array_intersect_key( $errors, array_flip( array( 'winlink_nth', 'simplex_nth', 'gmrs_nth' ) ) ) );
	?>
	<div class="wrap spk-screen spk-net">
		<h1 class="wp-heading-inline"><?php echo esc_html( spokares_screen_title( 'net-details' ) ); ?></h1>
		<a class="page-title-action" href="<?php echo esc_url( spokares_site_url( '/how-it-works/', 'weekly-net' ) ); ?>"><?php esc_html_e( 'View on site', 'spokares-core' ); ?></a>
		<?php if ( '' !== $saved ) : ?>
			<span class="spk-saved"><?php echo esc_html( $saved ); ?></span>
		<?php endif; ?>
		<hr class="wp-header-end">

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="spk-net-form" data-spk-guard<?php echo $held ? ' data-spk-dirty' : ''; ?>>
			<input type="hidden" name="action" value="spokares_save_net_details">
			<?php wp_nonce_field( 'spokares_save_net_details' ); ?>
			<input type="hidden" name="orig" value="<?php echo esc_attr( (string) wp_json_encode( $state ) ); ?>">

			<h2><?php esc_html_e( 'The Tuesday net', 'spokares-core' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="spk-net-time"><?php esc_html_e( 'Net time (required)', 'spokares-core' ); ?></label></th>
					<td><input type="time" id="spk-net-time" name="nets[net_time]" class="<?php echo esc_attr( trim( spokares_err_class( $errors, 'net_time' ) ) ); ?>" value="<?php echo esc_attr( (string) $v( 'net_time', $nets['net_time'] ) ); ?>" data-stored="<?php echo esc_attr( $nets['net_time'] ); ?>" required>
					<?php spokares_err_text( $errors, 'net_time' ); ?></td>
				</tr>
				<tr>
					<?php if ( $admin ) : ?>
						<th scope="row"><label for="spk-p-call"><?php esc_html_e( 'Repeater call sign', 'spokares-core' ); ?></label></th>
						<td><input type="text" id="spk-p-call" name="radio[primary][call]" class="regular-text<?php echo esc_attr( spokares_err_class( $errors, 'p_call' ) ); ?>" value="<?php echo esc_attr( (string) $v( 'p_call', $p['call'] ) ); ?>" data-stored="<?php echo esc_attr( $p['call'] ); ?>" maxlength="12" spellcheck="false" aria-describedby="spk-p-call-hint">
						<p class="description" id="spk-p-call-hint"><?php esc_html_e( 'The club’s own call sign. How it works also names it in its page text and in the message-path picture, which don’t follow this box.', 'spokares-core' ); ?></p>
					<?php else : ?>
						<?php // Plain text, not a box: only the webmaster changes the club's call (the save checks it too). ?>
						<th scope="row"><?php esc_html_e( 'Repeater call sign', 'spokares-core' ); ?></th>
						<td><span id="spk-p-call" class="spk-fixed" data-stored="<?php echo esc_attr( $p['call'] ); ?>"><?php echo esc_html( sprintf( /* translators: %s: the club's call sign. */ __( '%s (the webmaster changes this)', 'spokares-core' ), $p['call'] ) ); ?></span>
					<?php endif; ?>
					<?php spokares_err_text( $errors, 'p_call' ); ?></td>
				</tr>
				<tr>
					<th scope="row"><label for="spk-p-freq"><?php esc_html_e( 'Frequency (MHz) (required)', 'spokares-core' ); ?></label></th>
					<td><input type="text" id="spk-p-freq" name="radio[primary][freq]" class="<?php echo esc_attr( trim( 'small-text spk-freq' . spokares_err_class( $errors, 'p_freq' ) ) ); ?>" value="<?php echo esc_attr( (string) $v( 'p_freq', $p['freq'] ) ); ?>" inputmode="decimal" autocomplete="off" data-stored="<?php echo esc_attr( $p['freq'] ); ?>" aria-describedby="spk-p-freq-hint" required>
					<?php spokares_net_hint( $errors, 'p_freq', 'spk-p-freq-hint', $freqhint ); ?></td>
				</tr>
				<tr>
					<th scope="row"><label for="spk-p-offset"><?php esc_html_e( 'Offset', 'spokares-core' ); ?></label></th>
					<td><?php spokares_select( 'radio[primary][offset]', (string) $p['offset'], spokares_offsets(), '', 'spk-p-offset' ); ?>
					<?php spokares_err_text( $errors, 'p_offset' ); ?></td>
				</tr>
				<tr>
					<th scope="row"><label for="spk-p-tone"><?php esc_html_e( 'Tone', 'spokares-core' ); ?></label></th>
					<td><?php spokares_select( 'radio[primary][tone]', (string) $p['tone'], spokares_tones(), __( 'No tone', 'spokares-core' ), 'spk-p-tone' ); ?>
					<?php spokares_err_text( $errors, 'p_tone' ); ?></td>
				</tr>
				<?php spokares_net_members_see( 'bar', $lines['bar'] ); ?>
			</table>

			<h2><?php esc_html_e( 'Alternate repeater', 'spokares-core' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="spk-a-show"><?php esc_html_e( 'Show on How it works', 'spokares-core' ); ?></label></th>
					<td><input type="checkbox" name="radio[alternate][show]" id="spk-a-show" value="1" <?php checked( $a['show'] ); ?>>
					<?php if ( $admin ) : ?>
						<label class="spk-admin-tick"><input type="checkbox" name="radio[alternate][needs_check]" value="1" <?php checked( $a['needs_check'] ); ?>> <?php esc_html_e( 'Needs checking (webmaster only)', 'spokares-core' ); ?></label>
					<?php endif; ?>
					<?php spokares_err_text( $errors, 'a_show' ); ?>
					<?php spokares_err_text( $errors, 'a_needs_check' ); ?></td>
				</tr>
				<tr>
					<th scope="row"><label for="spk-a-freq"><?php esc_html_e( 'Frequency (MHz)', 'spokares-core' ); ?></label></th>
					<td><input type="text" id="spk-a-freq" name="radio[alternate][freq]" class="<?php echo esc_attr( trim( 'small-text spk-freq' . spokares_err_class( $errors, 'a_freq' ) ) ); ?>" value="<?php echo esc_attr( (string) $v( 'a_freq', $a['freq'] ) ); ?>" inputmode="decimal" autocomplete="off" data-stored="<?php echo esc_attr( $a['freq'] ); ?>" aria-describedby="spk-a-freq-hint">
					<?php spokares_net_hint( $errors, 'a_freq', 'spk-a-freq-hint', $freqhint ); ?></td>
				</tr>
				<tr>
					<th scope="row"><label for="spk-a-offset"><?php esc_html_e( 'Offset', 'spokares-core' ); ?></label></th>
					<td><?php spokares_select( 'radio[alternate][offset]', (string) $a['offset'], spokares_offsets(), __( '—', 'spokares-core' ), 'spk-a-offset' ); ?>
					<?php spokares_err_text( $errors, 'a_offset' ); ?></td>
				</tr>
				<tr>
					<th scope="row"><label for="spk-a-tone"><?php esc_html_e( 'Tone', 'spokares-core' ); ?></label></th>
					<td><?php spokares_select( 'radio[alternate][tone]', (string) $a['tone'], spokares_tones(), __( 'No tone', 'spokares-core' ), 'spk-a-tone' ); ?>
					<?php spokares_err_text( $errors, 'a_tone' ); ?></td>
				</tr>
				<?php spokares_net_members_see( 'alt', $lines['alt'] ); ?>
			</table>

			<h2><?php esc_html_e( 'Which Tuesdays', 'spokares-core' ); ?></h2>
			<fieldset class="spk-week-kinds<?php echo $week_err ? ' spk-field-error' : ''; ?>">
				<legend class="screen-reader-text"><?php esc_html_e( 'The net on each Tuesday of the month', 'spokares-core' ); ?></legend>
				<?php for ( $n = 1; $n <= 5; $n++ ) : ?>
					<span class="spk-week">
						<label for="spk-week-<?php echo (int) $n; ?>">
							<?php
							if ( 1 === $n ) {
								esc_html_e( '1st Tuesday', 'spokares-core' );
							} else {
								echo esc_html( 5 === $n ? __( '5th', 'spokares-core' ) : spokares_ordinal( $n ) ) . '<span class="screen-reader-text"> ' . esc_html__( 'Tuesday', 'spokares-core' ) . '</span>';
							}
							?>
						</label>
						<select id="spk-week-<?php echo (int) $n; ?>" name="nets[week][<?php echo (int) $n; ?>]">
							<?php foreach ( spokares_net_kinds() as $value => $label ) : ?>
								<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $value, $kinds[ $n ] ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</span>
				<?php endfor; ?>
				<?php if ( $week_err ) : ?>
					<span class="spk-error-text"><?php echo esc_html( $week_err[0] ); ?></span>
				<?php endif; ?>
			</fieldset>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="spk-gmrs-time"><?php esc_html_e( 'GMRS net time', 'spokares-core' ); ?></label></th>
					<td><input type="time" id="spk-gmrs-time" name="nets[gmrs_time]" class="<?php echo esc_attr( trim( spokares_err_class( $errors, 'gmrs_time' ) ) ); ?>" value="<?php echo esc_attr( (string) $v( 'gmrs_time', $nets['gmrs_time'] ) ); ?>" data-stored="<?php echo esc_attr( $nets['gmrs_time'] ); ?>">
					<?php spokares_err_text( $errors, 'gmrs_time' ); ?></td>
				</tr>
				<tr class="spk-members-see"<?php echo $others ? '' : ' hidden'; ?>>
					<th scope="row"><?php esc_html_e( 'Members see', 'spokares-core' ); ?></th>
					<td><ul class="spk-members-see-list">
						<?php foreach ( array( 'winlink', 'simplex', 'gmrs' ) as $key ) : ?>
							<li data-preview="<?php echo esc_attr( $key ); ?>"<?php echo '' === $lines[ $key ] ? ' hidden' : ''; ?>><?php echo esc_html( $lines[ $key ] ); ?></li>
						<?php endforeach; ?>
					</ul></td>
				</tr>
				<?php if ( $admin ) : ?>
				<tr>
					<th scope="row"><?php esc_html_e( 'Checking', 'spokares-core' ); ?></th>
					<td><label><input type="checkbox" name="nets[needs_check]" value="1" <?php checked( $nets['needs_check'] ); ?>> <?php esc_html_e( 'Needs checking (webmaster only): the Winlink, simplex and GMRS lines', 'spokares-core' ); ?></label>
					<?php spokares_err_text( $errors, 'needs_check' ); ?></td>
				</tr>
				<?php endif; ?>
			</table>

			<h2><?php esc_html_e( 'Wording on the site', 'spokares-core' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="spk-howto"><?php esc_html_e( 'How to answer a Winlink assignment', 'spokares-core' ); ?></label></th>
					<td><textarea id="spk-howto" name="nets[winlink_howto]" rows="3" class="large-text<?php echo esc_attr( spokares_err_class( $errors, 'winlink_howto' ) ); ?>" maxlength="300" aria-describedby="spk-howto-hint"><?php echo esc_textarea( (string) $v( 'winlink_howto', $nets['winlink_howto'] ) ); ?></textarea>
					<p class="description" id="spk-howto-hint"><?php esc_html_e( 'Shown above the Winlink assignments on the Exercises & events page.', 'spokares-core' ); ?></p>
					<?php spokares_err_text( $errors, 'winlink_howto' ); ?>
					<?php spokares_confirm_box( $errors, 'winlink_howto' ); ?></td>
				</tr>
				<tr>
					<th scope="row"><label for="spk-open"><?php esc_html_e( 'Asking for volunteers', 'spokares-core' ); ?></label></th>
					<td><input type="text" id="spk-open" name="nets[open_slot_line]" class="large-text<?php echo esc_attr( spokares_err_class( $errors, 'open_slot_line' ) ); ?>" maxlength="160" value="<?php echo esc_attr( (string) $v( 'open_slot_line', $nets['open_slot_line'] ) ); ?>" aria-describedby="spk-open-hint">
					<p class="description" id="spk-open-hint"><?php esc_html_e( 'Shown under the Net Control Schedule on the For members page; the “Volunteer needed” tags link to it.', 'spokares-core' ); ?></p>
					<?php spokares_err_text( $errors, 'open_slot_line' ); ?>
					<?php spokares_confirm_box( $errors, 'open_slot_line' ); ?></td>
				</tr>
			</table>

			<p class="submit spk-submit"><button type="submit" class="button button-primary button-large"><?php esc_html_e( 'Save', 'spokares-core' ); ?></button></p>
		</form>
	</div>
	<?php
}

/**
 * The screen's words for admin-forms.js (window.spokaresNet). The bands
 * stay in window.spokaresAdmin.
 *
 * @param string $hook Screen hook.
 */
function spokares_net_script_words( $hook ): void {
	if ( ! str_ends_with( (string) $hook, '_page_spokares-net-details' ) || ! wp_script_is( 'spokares-admin-forms', 'enqueued' ) ) {
		return;
	}
	wp_add_inline_script(
		'spokares-admin-forms',
		'window.spokaresNet = ' . wp_json_encode(
			array(
				'format'   => __( 'Three digits after the point, like 147.300.', 'spokares-core' ),
				/* translators: %s: the stored frequency, e.g. "147.300". */
				'band'     => __( 'Not an amateur frequency; the site keeps %s.', 'spokares-core' ),
				'bandNone' => __( 'Not an amateur frequency.', 'spokares-core' ),
				'alt'      => __( 'Alternate', 'spokares-core' ),
				'notShown' => __( 'Not shown on How it works.', 'spokares-core' ),
			)
		) . ';',
		'before'
	);
}
add_action( 'admin_enqueue_scripts', 'spokares_net_script_words', 20 );

/**
 * A "Publish it" tick for a settings field (shown after a phone/e-mail hit).
 *
 * @param array  $errors Errors.
 * @param string $field  Field key.
 */
function spokares_confirm_box( array $errors, string $field ): void {
	if ( empty( $errors[ $field . '-confirm' ] ) ) {
		return;
	}
	printf(
		'<label class="spk-confirm"><input type="checkbox" name="confirm[%1$s]" value="1"> %2$s</label>',
		esc_attr( $field ),
		esc_html( $errors[ $field . '-confirm' ] )
	);
}

/**
 * Check one settings sentence; on a problem hold it back (keep the stored
 * value), remember the typed text and the problem's one sentence.
 *
 * @param string $field     Field key.
 * @param string $typed     Typed text.
 * @param string $stored    Stored text.
 * @param array  $confirmed Confirmed hashes (updated).
 * @param array  $ticks     Confirm ticks from the form.
 * @param array  $held      Held values (updated).
 * @param array  $errors    Errors (updated).
 * @param int    $max       Characters allowed (the form's maxlength; 0 = no limit).
 * @return string The value to save.
 */
function spokares_settings_text( string $field, string $typed, string $stored, array &$confirmed, array $ticks, array &$held, array &$errors, int $max = 0 ): string {
	if ( $max && spokares_too_long( $typed, $max ) ) {
		$held[ $field ] = $typed;
		/* translators: %d: number of characters. */
		$errors[ $field ] = sprintf( __( 'Keep it to %d characters.', 'spokares-core' ), $max );
		return $stored;
	}
	$check = spokares_check_field( $typed, $field, $confirmed, ! empty( $ticks[ $field ] ) );
	if ( $check['block'] || $check['confirm'] ) {
		$held[ $field ]   = $typed;
		$errors[ $field ] = spokares_problem_sentence( $check );
		if ( ! $check['block'] ) {
			$errors[ $field . '-confirm' ] = spokares_confirm_label( $check['confirm'] );
		}
		return $stored;
	}
	if ( $check['confirmed'] ) {
		$confirmed[ $field ] = sha1( $typed );
	}
	return $typed;
}

/**
 * The "what the form showed" map posted back by a settings screen (null for
 * a form without one: every field is then saved, as before).
 *
 * @param mixed $raw Posted JSON (unslashed).
 */
function spokares_posted_orig( $raw ): ?array {
	if ( ! is_string( $raw ) || '' === $raw ) {
		return null;
	}
	$data = json_decode( $raw, true );
	if ( ! is_array( $data ) ) {
		return null;
	}
	$out = array();
	foreach ( $data as $key => $value ) {
		if ( is_scalar( $value ) ) {
			$out[ sanitize_key( (string) $key ) ] = (string) $value;
		}
	}
	return $out;
}

/**
 * Save Net Settings, then one notice that says what changed.
 */
function spokares_handle_save_net_details(): void {
	spokares_verify_form( 'spokares_save_net_details', 'spokares_edit_net_details' );
	$in_radio = isset( $_POST['radio'] ) && is_array( $_POST['radio'] ) ? wp_unslash( $_POST['radio'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitised field by field below.
	$in_nets  = isset( $_POST['nets'] ) && is_array( $_POST['nets'] ) ? wp_unslash( $_POST['nets'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitised field by field below.
	$ticks    = isset( $_POST['confirm'] ) && is_array( $_POST['confirm'] ) ? array_map( 'sanitize_text_field', array_filter( wp_unslash( $_POST['confirm'] ), 'is_scalar' ) ) : array();
	$orig     = spokares_posted_orig( isset( $_POST['orig'] ) ? wp_unslash( $_POST['orig'] ) : '' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- decoded and sanitised in spokares_posted_orig().
	$admin    = current_user_can( 'manage_options' );
	$nets     = spokares_opt( 'spk_nets' );
	$radio    = spokares_opt( 'spk_radio' );
	$was      = spokares_net_form_state( $nets, $radio );
	$was_week = spokares_net_week_kinds( $nets );
	$held     = array();
	$errors   = array();
	$names    = spokares_net_field_names();

	$pin   = is_array( $in_radio['primary'] ?? null ) ? $in_radio['primary'] : array();
	$ain   = is_array( $in_radio['alternate'] ?? null ) ? $in_radio['alternate'] : array();
	$weeks = static function ( $picked ): array {
		$picked = array_map( 'absint', array_filter( (array) $picked, 'is_scalar' ) );
		$picked = array_values( array_unique( array_filter( $picked, static fn( $n ) => $n >= 1 && $n <= 5 ) ) );
		sort( $picked );
		return $picked;
	};
	// Which Tuesdays: one select per week, so a week has one kind of net. A
	// form opened before the selects existed posts the three week lists.
	if ( is_array( $in_nets['week'] ?? null ) ) {
		$lists = array(
			'winlink' => array(),
			'simplex' => array(),
			'gmrs'    => array(),
		);
		for ( $n = 1; $n <= 5; $n++ ) {
			$kind = spokares_post_str( $in_nets['week'], (string) $n );
			if ( isset( $lists[ $kind ] ) ) {
				$lists[ $kind ][] = $n;
			}
		}
	} else {
		$lists = array(
			'winlink' => $weeks( $in_nets['winlink_nth'] ?? array() ),
			'simplex' => $weeks( $in_nets['simplex_nth'] ?? array() ),
			'gmrs'    => $weeks( $in_nets['gmrs_nth'] ?? array() ),
		);
	}

	// What was typed, field by field, in the form's shape.
	$typed = array(
		'p_call'         => sanitize_text_field( spokares_post_str( $pin, 'call' ) ),
		'p_freq'         => sanitize_text_field( spokares_post_str( $pin, 'freq' ) ),
		'p_offset'       => sanitize_text_field( spokares_post_str( $pin, 'offset' ) ),
		'p_tone'         => sanitize_text_field( spokares_post_str( $pin, 'tone' ) ),
		'net_time'       => sanitize_text_field( spokares_post_str( $in_nets, 'net_time' ) ),
		'a_freq'         => sanitize_text_field( spokares_post_str( $ain, 'freq' ) ),
		'a_offset'       => sanitize_text_field( spokares_post_str( $ain, 'offset' ) ),
		'a_tone'         => sanitize_text_field( spokares_post_str( $ain, 'tone' ) ),
		'a_show'         => ! empty( $ain['show'] ) ? '1' : '',
		'a_needs_check'  => ! empty( $ain['needs_check'] ) ? '1' : '',
		'winlink_nth'    => $lists['winlink'],
		'simplex_nth'    => $lists['simplex'],
		'gmrs_nth'       => $lists['gmrs'],
		'gmrs_time'      => sanitize_text_field( spokares_post_str( $in_nets, 'gmrs_time' ) ),
		'needs_check'    => ! empty( $in_nets['needs_check'] ) ? '1' : '',
		// Plain one-line text on the site: the line breaks go before it is
		// checked, so a confirmed sentence matches the text the screen shows.
		'winlink_howto'  => trim( (string) preg_replace( '/\s+/', ' ', sanitize_textarea_field( spokares_post_str( $in_nets, 'winlink_howto' ) ) ) ),
		'open_slot_line' => sanitize_text_field( spokares_post_str( $in_nets, 'open_slot_line' ) ),
	);
	$shown = array_map( static fn( $x ) => is_array( $x ) ? implode( ',', $x ) : (string) $x, $typed );

	$shown['p_call'] = strtoupper( trim( $shown['p_call'] ) );
	$conflicts       = array();

	// Save a field only when this editor changed it; a field someone else
	// changed after this screen was opened is kept, and the editor is told.
	$apply = static function ( string $key ) use ( $orig, $was, $shown, &$conflicts ): bool {
		if ( null === $orig || ! array_key_exists( $key, $orig ) ) {
			return true; // A form without the record: save as typed.
		}
		if ( $shown[ $key ] === $orig[ $key ] ) {
			return false; // Unchanged here: keep what is stored now.
		}
		if ( $was[ $key ] === $orig[ $key ] || $was[ $key ] === $shown[ $key ] ) {
			return true;
		}
		$conflicts[] = $key;
		return false;
	};

	// The Tuesday net. The call sign is the club's own call, which How it
	// works also names in page text: the webmaster's only. For anyone else
	// the screen shows it as text and posts nothing; a request that posts a
	// different one anyway is refused here, never saved.
	if ( $apply( 'net_time' ) ) {
		if ( spokares_is_hhmm( $typed['net_time'] ) ) {
			$nets['net_time'] = $typed['net_time'];
		} else {
			$held['net_time']   = $typed['net_time'];
			$errors['net_time'] = __( 'Type a time, like 8:00 PM.', 'spokares-core' );
		}
	}
	if ( $admin ) {
		if ( $apply( 'p_call' ) ) {
			$call = spokares_call_sign( $typed['p_call'] );
			if ( '' !== $call['call'] && ! $call['dropped'] ) {
				$radio['primary']['call'] = $call['call'];
			} else {
				$held['p_call']   = $typed['p_call'];
				$errors['p_call'] = __( 'Type one call sign, like W7GBU.', 'spokares-core' );
			}
		}
	} elseif ( '' !== $shown['p_call'] && $shown['p_call'] !== $was['p_call'] && ( null === $orig || ( $orig['p_call'] ?? null ) !== $shown['p_call'] ) ) {
		$errors['p_call'] = __( 'The webmaster changes the repeater call sign.', 'spokares-core' );
	}
	if ( $apply( 'p_freq' ) ) {
		if ( spokares_freq_ok( $typed['p_freq'] ) ) {
			$radio['primary']['freq'] = $typed['p_freq'];
		} else {
			$held['p_freq']   = $typed['p_freq'];
			$errors['p_freq'] = spokares_freq_problem( $typed['p_freq'], (string) $radio['primary']['freq'] );
		}
	}
	if ( $apply( 'p_offset' ) && ( in_array( $typed['p_offset'], spokares_offsets(), true ) || $typed['p_offset'] === $radio['primary']['offset'] ) ) {
		$radio['primary']['offset'] = $typed['p_offset'];
	}
	if ( $apply( 'p_tone' ) && ( '' === $typed['p_tone'] || in_array( $typed['p_tone'], spokares_tones(), true ) || $typed['p_tone'] === $radio['primary']['tone'] ) ) {
		$radio['primary']['tone'] = $typed['p_tone'];
	}

	// Alternate repeater.
	if ( $apply( 'a_show' ) ) {
		$radio['alternate']['show'] = '1' === $typed['a_show'];
	}
	if ( $admin && $apply( 'a_needs_check' ) ) {
		$radio['alternate']['needs_check'] = '1' === $typed['a_needs_check'];
	}
	if ( $apply( 'a_freq' ) ) {
		if ( '' === $typed['a_freq'] || spokares_freq_ok( $typed['a_freq'] ) ) {
			$radio['alternate']['freq'] = $typed['a_freq'];
		} else {
			$held['a_freq']   = $typed['a_freq'];
			$errors['a_freq'] = spokares_freq_problem( $typed['a_freq'], (string) $radio['alternate']['freq'] );
		}
	}
	if ( $apply( 'a_offset' ) && ( '' === $typed['a_offset'] || in_array( $typed['a_offset'], spokares_offsets(), true ) || $typed['a_offset'] === $radio['alternate']['offset'] ) ) {
		$radio['alternate']['offset'] = $typed['a_offset'];
	}
	if ( $apply( 'a_tone' ) && ( '' === $typed['a_tone'] || in_array( $typed['a_tone'], spokares_tones(), true ) || $typed['a_tone'] === $radio['alternate']['tone'] ) ) {
		$radio['alternate']['tone'] = $typed['a_tone'];
	}

	// Which Tuesdays, and the GMRS net time (a bad time is held back, never dropped).
	foreach ( array( 'winlink_nth', 'simplex_nth', 'gmrs_nth' ) as $k ) {
		if ( $apply( $k ) ) {
			$nets[ $k ] = $typed[ $k ];
		}
	}
	if ( $apply( 'gmrs_time' ) ) {
		if ( '' === $typed['gmrs_time'] || spokares_is_hhmm( $typed['gmrs_time'] ) ) {
			$nets['gmrs_time'] = $typed['gmrs_time'];
		} else {
			$held['gmrs_time']   = $typed['gmrs_time'];
			$errors['gmrs_time'] = __( 'Type a time, like 8:00 PM.', 'spokares-core' );
		}
	}
	if ( $admin && $apply( 'needs_check' ) ) {
		$nets['needs_check'] = '1' === $typed['needs_check'];
	}

	// Wording on the site.
	$confirmed = is_array( $nets['confirmed'] ?? null ) ? $nets['confirmed'] : array();
	$was_ok    = $confirmed;
	if ( $apply( 'winlink_howto' ) ) {
		$nets['winlink_howto'] = spokares_settings_text( 'winlink_howto', $typed['winlink_howto'], $nets['winlink_howto'], $confirmed, $ticks, $held, $errors, 300 );
	}
	if ( $apply( 'open_slot_line' ) ) {
		$nets['open_slot_line'] = spokares_settings_text( 'open_slot_line', $typed['open_slot_line'], $nets['open_slot_line'], $confirmed, $ticks, $held, $errors, 160 );
	}
	if ( $confirmed ) {
		$nets['confirmed'] = $confirmed;
	}
	foreach ( $conflicts as $key ) {
		$errors[ $key ] = __( 'Someone else changed this while you were editing. Check it and save again.', 'spokares-core' );
		if ( ! is_array( $typed[ $key ] ) ) {
			$held[ $key ] = $typed[ $key ];
		}
	}

	$changes = spokares_net_changes( $was, spokares_net_form_state( $nets, $radio ), $was_week, spokares_net_week_kinds( $nets ) );
	if ( $changes || $confirmed !== $was_ok ) {
		update_option( 'spk_radio', $radio );
		update_option( 'spk_nets', $nets );
		spokares_purge_cache();
	}
	if ( $changes ) {
		update_option( 'spk_net_saved', spokares_stamp(), false );
	}

	// One notice for the whole save.
	$bad  = array();
	$link = spokares_site_url( '/how-it-works/', 'weekly-net' );
	$see  = __( 'See it on the How it works page', 'spokares-core' );
	foreach ( array_keys( $errors ) as $key ) {
		if ( ! str_ends_with( (string) $key, '-confirm' ) ) {
			$bad[] = $names[ $key ] ?? (string) $key;
		}
	}
	$bad = spokares_and_list( array_values( array_unique( $bad ) ) );
	if ( $changes && '' === $bad ) {
		/* translators: %s: what changed, e.g. "tone 103.5 Hz (was 100 Hz); net time 7:30 PM (was 8:00 PM)". */
		spokares_add_notice( 'success', sprintf( __( 'Saved: %s.', 'spokares-core' ), implode( '; ', $changes ) ), $link, $see );
	} elseif ( $changes ) {
		/* translators: %s: the fields not saved, e.g. "the frequency". */
		spokares_add_notice( 'warning', sprintf( __( 'Saved, except %s (outlined in red).', 'spokares-core' ), $bad ), $link, $see );
	} elseif ( '' !== $bad ) {
		/* translators: %s: the fields not saved, e.g. "the frequency". */
		spokares_add_notice( 'error', sprintf( __( 'Not saved: %s (outlined in red).', 'spokares-core' ), $bad ) );
	} else {
		spokares_add_notice( 'info', __( 'Nothing changed, so nothing was saved.', 'spokares-core' ) );
	}
	if ( $errors ) {
		spokares_retain( 'spokares-net-details', $held, $errors );
	}
	spokares_redirect_to( 'spokares-net-details' );
}
add_action( 'admin_post_spokares_save_net_details', 'spokares_handle_save_net_details' );
