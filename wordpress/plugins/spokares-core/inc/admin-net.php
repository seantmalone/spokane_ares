<?php
/**
 * Net details (§3.4): repeater settings, the net time, which Tuesdays are
 * Winlink, simplex and GMRS nights, and the two sentences. A preview shows
 * every sentence these facts produce on the site.
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
 * Week checkboxes (1st-5th).
 *
 * @param string $name    Field name (without []).
 * @param int[]  $current Selected weeks.
 * @param string $legend  Legend.
 */
function spokares_week_boxes( string $name, array $current, string $legend ): void {
	echo '<fieldset class="spk-weeks"><legend class="screen-reader-text">' . esc_html( $legend ) . '</legend>';
	for ( $n = 1; $n <= 5; $n++ ) {
		printf(
			'<label><input type="checkbox" name="%1$s[]" value="%2$d" %3$s> %4$s</label> ',
			esc_attr( $name ),
			(int) $n,
			checked( in_array( $n, $current, true ), true, false ),
			esc_html( 5 === $n ? __( '5th', 'spokares-core' ) : spokares_ordinal( $n ) )
		);
	}
	echo '</fieldset>';
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
 * The preview lines for a set of net facts: what the site prints for them.
 * The site's own formatters and the Other nets view are run with these facts
 * in place of the stored ones, so the preview can't drift from the pages
 * (no ", ," for an empty GMRS time; the alternate row as How it works shows
 * it; a week ticked twice listed once).
 *
 * @param array $nets  spk_nets.
 * @param array $radio spk_radio.
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
		$p     = $radio['primary'];
		$a     = $radio['alternate'];
		$time  = spokares_fmt_time( $nets['net_time'] );
		$lines = array(
			// For members: the settings bar prints the time, then the bar line.
			'bar'       => trim( $time . ' ' . spokares_radio_line( 'bar' ) ),
			'copy'      => spokares_radio_line( 'copy' ),
			// How it works: the box prints the time, then the call sign and the display line.
			/* translators: %s: time. */
			'settings'  => sprintf( __( 'Every Tuesday, %s', 'spokares-core' ), $time ) . ' · ' . trim( $p['call'] . ' ' . spokares_radio_line( 'display' ) ),
			'alt'       => ( $a['show'] && '' !== $a['freq'] ) ? __( 'Alternate', 'spokares-core' ) . ' · ' . spokares_radio_line( 'alt-display' ) : __( '(not shown)', 'spokares-core' ),
			'from-home' => __( 'From home:', 'spokares-core' ) . ' ' . __( 'listen to the Tuesday net,', 'spokares-core' ) . ' ' . $time . ', '
				. ( '' !== $p['freq'] ? spokares_radio_line( 'freq' ) . ', ' : '' )
				. __( 'on any scanner or 2-meter radio. No license needed.', 'spokares-core' ),
			'winlink'   => '',
			'simplex'   => '',
			'gmrs'      => '',
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
 * Net details as the form shows them (field key => text), recorded in the
 * form when it is drawn. A save changes only the fields the editor changed,
 * so an older screen never puts back someone else's newer save (§8.2 #18,
 * as the rota and Regular meetings do).
 *
 * @param array $nets  spk_nets.
 * @param array $radio spk_radio.
 */
function spokares_net_form_state( array $nets, array $radio ): array {
	$p     = $radio['primary'];
	$a     = $radio['alternate'];
	$weeks = static function ( $picked ): string {
		$picked = array_values( array_unique( array_map( 'intval', (array) $picked ) ) );
		sort( $picked );
		return implode( ',', $picked );
	};
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
		'winlink_nth'    => $weeks( $nets['winlink_nth'] ),
		'simplex_nth'    => $weeks( $nets['simplex_nth'] ),
		'gmrs_nth'       => $weeks( $nets['gmrs_nth'] ),
		'gmrs_time'      => (string) $nets['gmrs_time'],
		'needs_check'    => $nets['needs_check'] ? '1' : '',
		'winlink_howto'  => trim( (string) preg_replace( '/\s+/', ' ', (string) $nets['winlink_howto'] ) ),
		'open_slot_line' => (string) $nets['open_slot_line'],
	);
}

/**
 * Field names for the Net details sentences.
 */
function spokares_net_field_names(): array {
	return array(
		'p_call'         => __( 'The repeater call sign', 'spokares-core' ),
		'p_freq'         => __( 'The frequency', 'spokares-core' ),
		'p_offset'       => __( 'The offset', 'spokares-core' ),
		'p_tone'         => __( 'The tone', 'spokares-core' ),
		'net_time'       => __( 'The net time', 'spokares-core' ),
		'a_freq'         => __( 'The alternate frequency', 'spokares-core' ),
		'a_offset'       => __( 'The alternate offset', 'spokares-core' ),
		'a_tone'         => __( 'The alternate tone', 'spokares-core' ),
		'a_show'         => __( '“Show on How it works”', 'spokares-core' ),
		'a_needs_check'  => __( 'The alternate repeater’s “Needs checking”', 'spokares-core' ),
		'winlink_nth'    => __( 'The Winlink nights', 'spokares-core' ),
		'simplex_nth'    => __( 'The simplex nights', 'spokares-core' ),
		'gmrs_nth'       => __( 'The GMRS net weeks', 'spokares-core' ),
		'gmrs_time'      => __( 'The GMRS net time', 'spokares-core' ),
		'needs_check'    => __( 'The nets’ “Needs checking”', 'spokares-core' ),
		'winlink_howto'  => __( 'The Winlink sentence', 'spokares-core' ),
		'open_slot_line' => __( 'The open-slot line', 'spokares-core' ),
	);
}

/**
 * The Net details screen.
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
	?>
	<div class="wrap spk-screen spk-net">
		<h1 class="wp-heading-inline"><?php esc_html_e( 'Net details', 'spokares-core' ); ?></h1>
		<a class="page-title-action" href="<?php echo esc_url( spokares_site_url( '/how-it-works/', 'weekly-net' ) ); ?>"><?php esc_html_e( 'View on site', 'spokares-core' ); ?></a>
		<hr class="wp-header-end">
		<div class="spk-banner spk-banner--red" role="note"><?php esc_html_e( 'Amateur frequencies only. Never county, hospital, SHARES, 800 MHz or channel numbers.', 'spokares-core' ); ?></div>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-spk-confirm id="spk-net-form">
			<input type="hidden" name="action" value="spokares_save_net_details">
			<?php wp_nonce_field( 'spokares_save_net_details' ); ?>
			<input type="hidden" name="orig" value="<?php echo esc_attr( (string) wp_json_encode( $state ) ); ?>">

			<h2><?php esc_html_e( 'The repeater', 'spokares-core' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="spk-p-call"><?php esc_html_e( 'Repeater call sign', 'spokares-core' ); ?></label></th>
					<td>
					<?php if ( $admin ) : ?>
						<input type="text" id="spk-p-call" name="radio[primary][call]" class="regular-text<?php echo esc_attr( spokares_err_class( $errors, 'p_call' ) ); ?>" value="<?php echo esc_attr( (string) $v( 'p_call', $p['call'] ) ); ?>" data-preview-input="call" data-stored="<?php echo esc_attr( $p['call'] ); ?>" maxlength="12" spellcheck="false" aria-describedby="spk-p-call-hint">
						<p class="description" id="spk-p-call-hint"><?php esc_html_e( 'The club’s own call sign. How it works also names it in its page text and in the message-path picture, which don’t follow this box.', 'spokares-core' ); ?></p>
					<?php else : ?>
						<?php // Read-only and not posted: only an administrator can change the club's call (the save checks it too). ?>
						<input type="text" id="spk-p-call" class="regular-text<?php echo esc_attr( spokares_err_class( $errors, 'p_call' ) ); ?>" value="<?php echo esc_attr( $p['call'] ); ?>" data-preview-input="call" data-stored="<?php echo esc_attr( $p['call'] ); ?>" spellcheck="false" readonly aria-describedby="spk-p-call-hint">
						<p class="description" id="spk-p-call-hint"><?php esc_html_e( 'The club’s own call sign. Only an administrator can change it.', 'spokares-core' ); ?></p>
					<?php endif; ?>
					<?php spokares_err_text( $errors, 'p_call' ); ?></td>
				</tr>
				<tr>
					<th scope="row"><label for="spk-p-freq"><?php esc_html_e( 'Frequency (MHz)', 'spokares-core' ); ?></label></th>
					<td><input type="text" id="spk-p-freq" name="radio[primary][freq]" class="<?php echo esc_attr( trim( 'small-text spk-freq' . spokares_err_class( $errors, 'p_freq' ) ) ); ?>" value="<?php echo esc_attr( (string) $v( 'p_freq', $p['freq'] ) ); ?>" pattern="\d{2,3}\.\d{3}" inputmode="decimal" data-preview-input="freq" data-stored="<?php echo esc_attr( $p['freq'] ); ?>" aria-describedby="spk-p-freq-hint" required>
					<p class="description" id="spk-p-freq-hint"><?php esc_html_e( 'An amateur frequency, like 147.300 (three digits after the point).', 'spokares-core' ); ?></p>
					<?php spokares_err_text( $errors, 'p_freq' ); ?></td>
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
				<tr>
					<th scope="row"><label for="spk-net-time"><?php esc_html_e( 'Net time (every Tuesday)', 'spokares-core' ); ?></label></th>
					<td><input type="time" id="spk-net-time" name="nets[net_time]" class="<?php echo esc_attr( trim( spokares_err_class( $errors, 'net_time' ) ) ); ?>" value="<?php echo esc_attr( (string) $v( 'net_time', $nets['net_time'] ) ); ?>" data-stored="<?php echo esc_attr( $nets['net_time'] ); ?>" required>
					<?php spokares_err_text( $errors, 'net_time' ); ?></td>
				</tr>
			</table>

			<h2><?php esc_html_e( 'The alternate repeater', 'spokares-core' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="spk-a-freq"><?php esc_html_e( 'Frequency (MHz)', 'spokares-core' ); ?></label></th>
					<td><input type="text" id="spk-a-freq" name="radio[alternate][freq]" class="<?php echo esc_attr( trim( 'small-text spk-freq' . spokares_err_class( $errors, 'a_freq' ) ) ); ?>" value="<?php echo esc_attr( (string) $v( 'a_freq', $a['freq'] ) ); ?>" pattern="\d{2,3}\.\d{3}" inputmode="decimal" data-stored="<?php echo esc_attr( $a['freq'] ); ?>" aria-describedby="spk-a-freq-hint">
					<p class="description" id="spk-a-freq-hint"><?php esc_html_e( 'An amateur frequency, like 146.880, or empty.', 'spokares-core' ); ?></p>
					<?php spokares_err_text( $errors, 'a_freq' ); ?></td>
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
				<tr>
					<th scope="row"><?php esc_html_e( 'Show it', 'spokares-core' ); ?></th>
					<td><label><input type="checkbox" name="radio[alternate][show]" id="spk-a-show" value="1" <?php checked( $a['show'] ); ?>> <?php esc_html_e( 'Show on How it works', 'spokares-core' ); ?></label>
					<?php if ( $admin ) : ?>
						<br><label><input type="checkbox" name="radio[alternate][needs_check]" value="1" <?php checked( $a['needs_check'] ); ?>> <?php esc_html_e( 'Needs checking (webmaster only)', 'spokares-core' ); ?></label>
					<?php endif; ?>
					<?php spokares_err_text( $errors, 'a_show' ); ?>
					<?php spokares_err_text( $errors, 'a_needs_check' ); ?></td>
				</tr>
			</table>

			<h2><?php esc_html_e( 'Which Tuesdays', 'spokares-core' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Winlink nights', 'spokares-core' ); ?></th>
					<td><?php spokares_week_boxes( 'nets[winlink_nth]', $nets['winlink_nth'], __( 'Winlink nights', 'spokares-core' ) ); ?>
					<?php spokares_err_text( $errors, 'winlink_nth' ); ?></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Simplex nights', 'spokares-core' ); ?></th>
					<td><?php spokares_week_boxes( 'nets[simplex_nth]', $nets['simplex_nth'], __( 'Simplex nights', 'spokares-core' ) ); ?>
					<p class="description"><?php esc_html_e( 'The net starts on simplex, then moves to the repeater. A week ticked twice counts as simplex first, then Winlink, then GMRS.', 'spokares-core' ); ?></p>
					<?php spokares_err_text( $errors, 'simplex_nth' ); ?></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'ACS GMRS net', 'spokares-core' ); ?></th>
					<td><?php spokares_week_boxes( 'nets[gmrs_nth]', $nets['gmrs_nth'], __( 'GMRS net weeks', 'spokares-core' ) ); ?>
						<label for="spk-gmrs-time"><?php esc_html_e( 'at', 'spokares-core' ); ?> <span class="screen-reader-text"><?php esc_html_e( '(ACS GMRS net time)', 'spokares-core' ); ?></span></label> <input type="time" id="spk-gmrs-time" name="nets[gmrs_time]" class="<?php echo esc_attr( trim( spokares_err_class( $errors, 'gmrs_time' ) ) ); ?>" value="<?php echo esc_attr( (string) $v( 'gmrs_time', $nets['gmrs_time'] ) ); ?>" data-stored="<?php echo esc_attr( $nets['gmrs_time'] ); ?>">
						<?php spokares_err_text( $errors, 'gmrs_nth' ); ?>
						<?php spokares_err_text( $errors, 'gmrs_time' ); ?></td>
				</tr>
				<?php if ( $admin ) : ?>
				<tr>
					<th scope="row"><?php esc_html_e( 'Checking', 'spokares-core' ); ?></th>
					<td><label><input type="checkbox" name="nets[needs_check]" value="1" <?php checked( $nets['needs_check'] ); ?>> <?php esc_html_e( 'Needs checking (webmaster only): the Winlink, simplex and GMRS lines', 'spokares-core' ); ?></label>
					<?php spokares_err_text( $errors, 'needs_check' ); ?></td>
				</tr>
				<?php endif; ?>
			</table>

			<h2><?php esc_html_e( 'Sentences', 'spokares-core' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="spk-howto"><?php esc_html_e( 'How to answer a Winlink assignment', 'spokares-core' ); ?></label></th>
					<td><textarea id="spk-howto" name="nets[winlink_howto]" rows="3" class="large-text<?php echo esc_attr( spokares_err_class( $errors, 'winlink_howto' ) ); ?>" maxlength="300" aria-describedby="spk-howto-hint"><?php echo esc_textarea( (string) $v( 'winlink_howto', $nets['winlink_howto'] ) ); ?></textarea>
					<p class="description" id="spk-howto-hint"><?php esc_html_e( 'Shown above the Winlink assignments on Exercises & events, as one line.', 'spokares-core' ); ?></p>
					<?php spokares_err_text( $errors, 'winlink_howto' ); ?>
					<?php spokares_confirm_box( $errors, 'winlink_howto' ); ?></td>
				</tr>
				<tr>
					<th scope="row"><label for="spk-open"><?php esc_html_e( 'Open-slot line', 'spokares-core' ); ?></label></th>
					<td><input type="text" id="spk-open" name="nets[open_slot_line]" class="large-text<?php echo esc_attr( spokares_err_class( $errors, 'open_slot_line' ) ); ?>" maxlength="160" value="<?php echo esc_attr( (string) $v( 'open_slot_line', $nets['open_slot_line'] ) ); ?>" aria-describedby="spk-open-hint">
					<p class="description" id="spk-open-hint"><?php esc_html_e( 'Shown under the rota; the “Open” tags link to it.', 'spokares-core' ); ?></p>
					<?php spokares_err_text( $errors, 'open_slot_line' ); ?>
					<?php spokares_confirm_box( $errors, 'open_slot_line' ); ?></td>
				</tr>
			</table>

			<?php // No live region: the preview is read on demand, not announced on every keystroke. ?>
			<div class="spk-preview" id="spk-net-preview">
				<h2><?php esc_html_e( 'Preview: what the site will say', 'spokares-core' ); ?></h2>
				<dl>
					<dt><?php esc_html_e( 'For members: the settings bar', 'spokares-core' ); ?></dt><dd data-preview="bar"><?php echo esc_html( $lines['bar'] ); ?></dd>
					<dt><?php esc_html_e( 'The Copy buttons copy', 'spokares-core' ); ?></dt><dd data-preview="copy"><?php echo esc_html( $lines['copy'] ); ?></dd>
					<dt><?php esc_html_e( 'How it works: the settings box', 'spokares-core' ); ?></dt><dd data-preview="settings"><?php echo esc_html( $lines['settings'] ); ?></dd>
					<dt><?php esc_html_e( 'How it works: the alternate repeater', 'spokares-core' ); ?></dt><dd data-preview="alt"><?php echo esc_html( $lines['alt'] ); ?></dd>
					<dt><?php esc_html_e( 'Home: the “From home” card', 'spokares-core' ); ?></dt><dd data-preview="from-home"><?php echo esc_html( $lines['from-home'] ); ?></dd>
					<dt><?php esc_html_e( 'How it works: “Other nets”', 'spokares-core' ); ?></dt>
					<dd><ul>
						<?php foreach ( array( 'winlink', 'simplex', 'gmrs' ) as $key ) : ?>
							<li data-preview="<?php echo esc_attr( $key ); ?>"<?php echo '' === $lines[ $key ] ? ' hidden' : ''; ?>><?php echo esc_html( $lines[ $key ] ); ?></li>
						<?php endforeach; ?>
					</ul></dd>
				</dl>
			</div>

			<p class="submit"><button type="submit" class="button button-primary button-large"><?php esc_html_e( 'Save net details', 'spokares-core' ); ?></button></p>
		</form>
	</div>
	<?php
}

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
 * value), remember the typed text and the error.
 *
 * @param string $field     Field key.
 * @param string $typed     Typed text.
 * @param string $stored    Stored text.
 * @param array  $confirmed Confirmed hashes (updated).
 * @param array  $ticks     Confirm ticks from the form.
 * @param array  $held      Held values (updated).
 * @param array  $errors    Errors (updated).
 * @param string $label     Field name for the sentence.
 * @param int    $max       Characters allowed (the form's maxlength; 0 = no limit).
 * @return string The value to save.
 */
function spokares_settings_text( string $field, string $typed, string $stored, array &$confirmed, array $ticks, array &$held, array &$errors, string $label, int $max = 0 ): string {
	if ( $max && spokares_too_long( $typed, $max ) ) {
		$held[ $field ]   = $typed;
		$errors[ $field ] = sprintf(
			/* translators: 1: field name, 2: number of characters. */
			__( '%1$s wasn’t saved: it is longer than %2$d characters.', 'spokares-core' ),
			$label,
			$max
		);
		return $stored;
	}
	$check = spokares_check_field( $typed, $field, $confirmed, ! empty( $ticks[ $field ] ) );
	if ( $check['block'] || $check['confirm'] ) {
		$what             = $check['block'] ? $check['block'] : wp_list_pluck( $check['confirm'], 'what' );
		$held[ $field ]   = $typed;
		$errors[ $field ] = sprintf(
			/* translators: 1: field name, 2: what was found. */
			__( '%1$s wasn’t saved because it mentions %2$s.', 'spokares-core' ),
			$label,
			spokares_and_list( $what )
		);
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
 * Save Net details.
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
		'winlink_nth'    => $weeks( $in_nets['winlink_nth'] ?? array() ),
		'simplex_nth'    => $weeks( $in_nets['simplex_nth'] ?? array() ),
		'gmrs_nth'       => $weeks( $in_nets['gmrs_nth'] ?? array() ),
		'gmrs_time'      => sanitize_text_field( spokares_post_str( $in_nets, 'gmrs_time' ) ),
		'needs_check'    => ! empty( $in_nets['needs_check'] ) ? '1' : '',
		// Plain one-line text on the site: the line breaks go before it is
		// checked, so a confirmed sentence matches the text the screen shows.
		'winlink_howto'  => trim( (string) preg_replace( '/\s+/', ' ', sanitize_textarea_field( spokares_post_str( $in_nets, 'winlink_howto' ) ) ) ),
		'open_slot_line' => sanitize_text_field( spokares_post_str( $in_nets, 'open_slot_line' ) ),
	);
	$shown = array_map( static fn( $x ) => is_array( $x ) ? implode( ',', $x ) : (string) $x, $typed );

	$shown['p_call'] = strtoupper( trim( $shown['p_call'] ) );
	$now             = spokares_net_form_state( $nets, $radio );
	$conflicts       = array();

	// Save a field only when this editor changed it; a field someone else
	// changed after this screen was opened is kept, and the editor is told.
	$apply = static function ( string $key ) use ( $orig, $now, $shown, &$conflicts ): bool {
		if ( null === $orig || ! array_key_exists( $key, $orig ) ) {
			return true; // A form without the record: save as typed.
		}
		if ( $shown[ $key ] === $orig[ $key ] ) {
			return false; // Unchanged here: keep what is stored now.
		}
		if ( $now[ $key ] === $orig[ $key ] || $now[ $key ] === $shown[ $key ] ) {
			return true;
		}
		$conflicts[] = $key;
		return false;
	};

	// Primary repeater. The call sign is the club's own call, which How it
	// works also names in page text: administrators only. For anyone else
	// the form shows it read-only and doesn't post it; a request that posts
	// a different one anyway is refused here, never saved.
	if ( $admin ) {
		if ( $apply( 'p_call' ) ) {
			$call = spokares_call_sign( $typed['p_call'] );
			if ( '' !== $call['call'] && ! $call['dropped'] ) {
				$radio['primary']['call'] = $call['call'];
			} else {
				$held['p_call']   = $typed['p_call'];
				$errors['p_call'] = __( 'The repeater call sign wasn’t saved: type one call sign, like W7GBU.', 'spokares-core' );
			}
		}
	} elseif ( '' !== $shown['p_call'] && $shown['p_call'] !== $now['p_call'] && ( null === $orig || ( $orig['p_call'] ?? null ) !== $shown['p_call'] ) ) {
		$errors['p_call'] = __( 'The repeater call sign wasn’t saved: only an administrator can change it.', 'spokares-core' );
	}
	if ( $apply( 'p_freq' ) ) {
		if ( spokares_freq_ok( $typed['p_freq'] ) ) {
			$radio['primary']['freq'] = $typed['p_freq'];
		} else {
			$held['p_freq']   = $typed['p_freq'];
			$errors['p_freq'] = __( 'The frequency wasn’t saved: type an amateur frequency like 147.300.', 'spokares-core' );
		}
	}
	if ( $apply( 'p_offset' ) && ( in_array( $typed['p_offset'], spokares_offsets(), true ) || $typed['p_offset'] === $radio['primary']['offset'] ) ) {
		$radio['primary']['offset'] = $typed['p_offset'];
	}
	if ( $apply( 'p_tone' ) && ( '' === $typed['p_tone'] || in_array( $typed['p_tone'], spokares_tones(), true ) || $typed['p_tone'] === $radio['primary']['tone'] ) ) {
		$radio['primary']['tone'] = $typed['p_tone'];
	}

	// Alternate repeater.
	if ( $apply( 'a_freq' ) ) {
		if ( '' === $typed['a_freq'] || spokares_freq_ok( $typed['a_freq'] ) ) {
			$radio['alternate']['freq'] = $typed['a_freq'];
		} else {
			$held['a_freq']   = $typed['a_freq'];
			$errors['a_freq'] = __( 'The alternate frequency wasn’t saved: type an amateur frequency like 146.880, or leave it empty.', 'spokares-core' );
		}
	}
	if ( $apply( 'a_offset' ) && ( '' === $typed['a_offset'] || in_array( $typed['a_offset'], spokares_offsets(), true ) || $typed['a_offset'] === $radio['alternate']['offset'] ) ) {
		$radio['alternate']['offset'] = $typed['a_offset'];
	}
	if ( $apply( 'a_tone' ) && ( '' === $typed['a_tone'] || in_array( $typed['a_tone'], spokares_tones(), true ) || $typed['a_tone'] === $radio['alternate']['tone'] ) ) {
		$radio['alternate']['tone'] = $typed['a_tone'];
	}
	if ( $apply( 'a_show' ) ) {
		$radio['alternate']['show'] = '1' === $typed['a_show'];
	}
	if ( $admin && $apply( 'a_needs_check' ) ) {
		$radio['alternate']['needs_check'] = '1' === $typed['a_needs_check'];
	}

	// Nets: the times must be times (a bad one is held back, never dropped).
	if ( $apply( 'net_time' ) ) {
		if ( spokares_is_hhmm( $typed['net_time'] ) ) {
			$nets['net_time'] = $typed['net_time'];
		} else {
			$held['net_time']   = $typed['net_time'];
			$errors['net_time'] = __( 'The net time wasn’t saved: type a time, like 8:00 PM.', 'spokares-core' );
		}
	}
	if ( $apply( 'gmrs_time' ) ) {
		if ( '' === $typed['gmrs_time'] || spokares_is_hhmm( $typed['gmrs_time'] ) ) {
			$nets['gmrs_time'] = $typed['gmrs_time'];
		} else {
			$held['gmrs_time']   = $typed['gmrs_time'];
			$errors['gmrs_time'] = __( 'The GMRS net time wasn’t saved: type a time, like 7:30 PM, or leave it empty.', 'spokares-core' );
		}
	}
	foreach ( array( 'winlink_nth', 'simplex_nth', 'gmrs_nth' ) as $k ) {
		if ( $apply( $k ) ) {
			$nets[ $k ] = $typed[ $k ];
		}
	}
	if ( $admin && $apply( 'needs_check' ) ) {
		$nets['needs_check'] = '1' === $typed['needs_check'];
	}
	$confirmed = is_array( $nets['confirmed'] ?? null ) ? $nets['confirmed'] : array();
	if ( $apply( 'winlink_howto' ) ) {
		$nets['winlink_howto'] = spokares_settings_text( 'winlink_howto', $typed['winlink_howto'], $nets['winlink_howto'], $confirmed, $ticks, $held, $errors, $names['winlink_howto'], 300 );
	}
	if ( $apply( 'open_slot_line' ) ) {
		$nets['open_slot_line'] = spokares_settings_text( 'open_slot_line', $typed['open_slot_line'], $nets['open_slot_line'], $confirmed, $ticks, $held, $errors, $names['open_slot_line'], 160 );
	}
	if ( $confirmed ) {
		$nets['confirmed'] = $confirmed;
	}
	foreach ( $conflicts as $key ) {
		$errors[ $key ] = sprintf(
			/* translators: %s: field name, e.g. "The tone". */
			__( '%s was changed by someone else while you were editing, so your change wasn’t saved. Check it and save again.', 'spokares-core' ),
			$names[ $key ] ?? $key
		);
		if ( ! is_array( $typed[ $key ] ) ) {
			$held[ $key ] = $typed[ $key ];
		}
	}

	update_option( 'spk_radio', $radio );
	update_option( 'spk_nets', $nets );
	spokares_purge_cache();

	foreach ( $errors as $key => $message ) {
		if ( ! str_ends_with( (string) $key, '-confirm' ) ) {
			spokares_add_notice( 'error', $message );
		}
	}
	spokares_add_notice(
		$errors ? 'warning' : 'success',
		$errors ? __( 'Saved, except the fields outlined in red.', 'spokares-core' ) : __( 'Saved.', 'spokares-core' ),
		spokares_site_url( '/how-it-works/', 'weekly-net' ),
		__( 'See it on How it works', 'spokares-core' )
	);
	if ( $errors ) {
		spokares_retain( 'spokares-net-details', $held, $errors );
	}
	spokares_redirect_to( 'spokares-net-details' );
}
add_action( 'admin_post_spokares_save_net_details', 'spokares_handle_save_net_details' );
