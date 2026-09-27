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
 * Is this a frequency we may publish? Format 000.000, and never the 800 MHz
 * public-safety band.
 *
 * @param string $freq Frequency.
 */
function spokares_freq_ok( string $freq ): bool {
	if ( ! preg_match( '/^\d{2,3}\.\d{3}$/', $freq ) ) {
		return false;
	}
	$f = (float) $freq;
	return ! ( $f >= 806 && $f < 870 );
}

/**
 * A select for a list of values, keeping an unknown stored value selectable.
 *
 * @param string   $name    Field name.
 * @param string   $current Current value.
 * @param string[] $choices Choices.
 * @param string   $empty   Label of the empty choice ('' for none).
 * @param string   $id      Element id.
 */
function spokares_select( string $name, string $current, array $choices, string $empty, string $id ): void {
	if ( '' !== $current && ! in_array( $current, $choices, true ) ) {
		array_unshift( $choices, $current );
	}
	echo '<select name="' . esc_attr( $name ) . '" id="' . esc_attr( $id ) . '">';
	if ( '' !== $empty ) {
		echo '<option value="">' . esc_html( $empty ) . '</option>';
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
 * The preview lines for a set of net facts (plain text, as the site prints them).
 *
 * @param array $nets  spk_nets.
 * @param array $radio spk_radio.
 */
function spokares_net_preview_lines( array $nets, array $radio ): array {
	$p     = $radio['primary'];
	$a     = $radio['alternate'];
	$time  = spokares_fmt_time( $nets['net_time'] );
	$freq  = '' !== $p['freq'] ? $p['freq'] . ' MHz' : '';
	$lines = array(
		'bar'       => trim( $time . ' ' . trim( $p['call'] . ' ' . implode( ', ', array_filter( array( $freq, spokares_minus( $p['offset'] ), $p['tone'] ) ) ) ) ),
		'copy'      => trim( $p['call'] . ' ' . implode( ', ', array_filter( array( $freq, '' !== $p['offset'] ? $p['offset'] . ' offset' : '', '' !== $p['tone'] ? $p['tone'] . ' tone' : '' ) ) ) ),
		// The box prints the time, then the call sign and the display line.
		'settings'  => sprintf( /* translators: %s: time. */ __( 'Every Tuesday, %s', 'spokares-core' ), $time ) . ' · ' . trim( $p['call'] . ' ' . implode( ', ', array_filter( array( $freq, spokares_minus( $p['offset'] ), '' !== $p['tone'] ? $p['tone'] . ' tone' : '' ) ) ) ),
		'from-home' => sprintf(
			/* translators: 1: time, 2: frequency. */
			__( 'From home: listen to the Tuesday net, %1$s, %2$s, on any scanner or 2-meter radio. No license needed.', 'spokares-core' ),
			$time,
			$freq
		),
		'alt'       => ( $a['show'] && '' !== $a['freq'] ) ? __( 'Alternate repeater', 'spokares-core' ) . ' ' . implode( ', ', array_filter( array( $a['freq'] . ' MHz', '' !== $a['offset'] ? $a['offset'] . ' offset' : '', '' !== $a['tone'] ? $a['tone'] . ' tone' : '' ) ) ) : __( '(not shown)', 'spokares-core' ),
		'winlink'   => $nets['winlink_nth'] ? sprintf( /* translators: %s: weeks. */ __( 'Winlink nights: %s Tuesdays; net control gives a Winlink assignment during the net.', 'spokares-core' ), spokares_ordinal_list( $nets['winlink_nth'] ) ) : '',
		'simplex'   => $nets['simplex_nth'] ? sprintf( /* translators: 1: weeks, 2: call sign. */ __( '%1$s Tuesdays: the net starts on simplex, then moves to %2$s.', 'spokares-core' ), spokares_ordinal_list( $nets['simplex_nth'], true ), $p['call'] ) : '',
		'gmrs'      => $nets['gmrs_nth'] ? sprintf( /* translators: 1: weeks, 2: time. */ __( 'ACS GMRS net: %1$s Tuesdays, %2$s, for county volunteers with GMRS licenses.', 'spokares-core' ), spokares_ordinal_list( $nets['gmrs_nth'] ), spokares_fmt_time( $nets['gmrs_time'] ) ) : '',
	);
	return array_map( static fn( $l ) => str_replace( "\u{00A0}", ' ', (string) $l ), $lines );
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
	?>
	<div class="wrap spk-screen spk-net">
		<h1 class="wp-heading-inline"><?php esc_html_e( 'Net details', 'spokares-core' ); ?></h1>
		<a class="page-title-action" href="<?php echo esc_url( spokares_site_url( '/how-it-works/', 'weekly-net' ) ); ?>"><?php esc_html_e( 'View on site', 'spokares-core' ); ?></a>
		<hr class="wp-header-end">
		<div class="spk-banner spk-banner--red" role="note"><?php esc_html_e( 'Amateur frequencies only. Never county, hospital, SHARES, 800 MHz or channel numbers.', 'spokares-core' ); ?></div>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-spk-confirm id="spk-net-form">
			<input type="hidden" name="action" value="spokares_save_net_details">
			<?php wp_nonce_field( 'spokares_save_net_details' ); ?>

			<h2><?php esc_html_e( 'The repeater', 'spokares-core' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="spk-p-call"><?php esc_html_e( 'Repeater call sign', 'spokares-core' ); ?></label></th>
					<td><input type="text" id="spk-p-call" name="radio[primary][call]" class="regular-text<?php echo esc_attr( spokares_err_class( $errors, 'p_call' ) ); ?>" value="<?php echo esc_attr( (string) $v( 'p_call', $p['call'] ) ); ?>" data-preview-input="call" maxlength="12" spellcheck="false">
					<?php spokares_err_text( $errors, 'p_call' ); ?></td>
				</tr>
				<tr>
					<th scope="row"><label for="spk-p-freq"><?php esc_html_e( 'Frequency (MHz)', 'spokares-core' ); ?></label></th>
					<td><input type="text" id="spk-p-freq" name="radio[primary][freq]" class="<?php echo esc_attr( trim( 'small-text spk-freq' . spokares_err_class( $errors, 'p_freq' ) ) ); ?>" value="<?php echo esc_attr( (string) $v( 'p_freq', $p['freq'] ) ); ?>" pattern="\d{2,3}\.\d{3}" inputmode="decimal" data-preview-input="freq" required>
					<p class="description"><?php esc_html_e( 'Like 147.300 (three digits after the point).', 'spokares-core' ); ?></p>
					<?php spokares_err_text( $errors, 'p_freq' ); ?></td>
				</tr>
				<tr>
					<th scope="row"><label for="spk-p-offset"><?php esc_html_e( 'Offset', 'spokares-core' ); ?></label></th>
					<td><?php spokares_select( 'radio[primary][offset]', (string) $p['offset'], spokares_offsets(), '', 'spk-p-offset' ); ?></td>
				</tr>
				<tr>
					<th scope="row"><label for="spk-p-tone"><?php esc_html_e( 'Tone', 'spokares-core' ); ?></label></th>
					<td><?php spokares_select( 'radio[primary][tone]', (string) $p['tone'], spokares_tones(), __( 'No tone', 'spokares-core' ), 'spk-p-tone' ); ?></td>
				</tr>
				<tr>
					<th scope="row"><label for="spk-net-time"><?php esc_html_e( 'Net time (every Tuesday)', 'spokares-core' ); ?></label></th>
					<td><input type="time" id="spk-net-time" name="nets[net_time]" value="<?php echo esc_attr( $nets['net_time'] ); ?>" required></td>
				</tr>
			</table>

			<h2><?php esc_html_e( 'The alternate repeater', 'spokares-core' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="spk-a-freq"><?php esc_html_e( 'Frequency (MHz)', 'spokares-core' ); ?></label></th>
					<td><input type="text" id="spk-a-freq" name="radio[alternate][freq]" class="<?php echo esc_attr( trim( 'small-text spk-freq' . spokares_err_class( $errors, 'a_freq' ) ) ); ?>" value="<?php echo esc_attr( (string) $v( 'a_freq', $a['freq'] ) ); ?>" pattern="\d{2,3}\.\d{3}" inputmode="decimal">
					<?php spokares_err_text( $errors, 'a_freq' ); ?></td>
				</tr>
				<tr>
					<th scope="row"><label for="spk-a-offset"><?php esc_html_e( 'Offset', 'spokares-core' ); ?></label></th>
					<td><?php spokares_select( 'radio[alternate][offset]', (string) $a['offset'], spokares_offsets(), __( '—', 'spokares-core' ), 'spk-a-offset' ); ?></td>
				</tr>
				<tr>
					<th scope="row"><label for="spk-a-tone"><?php esc_html_e( 'Tone', 'spokares-core' ); ?></label></th>
					<td><?php spokares_select( 'radio[alternate][tone]', (string) $a['tone'], spokares_tones(), __( 'No tone', 'spokares-core' ), 'spk-a-tone' ); ?></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Show it', 'spokares-core' ); ?></th>
					<td><label><input type="checkbox" name="radio[alternate][show]" id="spk-a-show" value="1" <?php checked( $a['show'] ); ?>> <?php esc_html_e( 'Show on How it works', 'spokares-core' ); ?></label>
					<?php if ( $admin ) : ?>
						<br><label><input type="checkbox" name="radio[alternate][needs_check]" value="1" <?php checked( $a['needs_check'] ); ?>> <?php esc_html_e( 'Needs checking (webmaster only)', 'spokares-core' ); ?></label>
					<?php endif; ?></td>
				</tr>
			</table>

			<h2><?php esc_html_e( 'Which Tuesdays', 'spokares-core' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Winlink nights', 'spokares-core' ); ?></th>
					<td><?php spokares_week_boxes( 'nets[winlink_nth]', $nets['winlink_nth'], __( 'Winlink nights', 'spokares-core' ) ); ?></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Simplex nights', 'spokares-core' ); ?></th>
					<td><?php spokares_week_boxes( 'nets[simplex_nth]', $nets['simplex_nth'], __( 'Simplex nights', 'spokares-core' ) ); ?>
					<p class="description"><?php esc_html_e( 'The net starts on simplex, then moves to the repeater. A week ticked twice counts as simplex first, then Winlink, then GMRS.', 'spokares-core' ); ?></p></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'ACS GMRS net', 'spokares-core' ); ?></th>
					<td><?php spokares_week_boxes( 'nets[gmrs_nth]', $nets['gmrs_nth'], __( 'GMRS net weeks', 'spokares-core' ) ); ?>
						<label for="spk-gmrs-time"><?php esc_html_e( 'at', 'spokares-core' ); ?></label> <input type="time" id="spk-gmrs-time" name="nets[gmrs_time]" value="<?php echo esc_attr( $nets['gmrs_time'] ); ?>"></td>
				</tr>
				<?php if ( $admin ) : ?>
				<tr>
					<th scope="row"><?php esc_html_e( 'Checking', 'spokares-core' ); ?></th>
					<td><label><input type="checkbox" name="nets[needs_check]" value="1" <?php checked( $nets['needs_check'] ); ?>> <?php esc_html_e( 'Needs checking (webmaster only): the Winlink, simplex and GMRS lines', 'spokares-core' ); ?></label></td>
				</tr>
				<?php endif; ?>
			</table>

			<h2><?php esc_html_e( 'Sentences', 'spokares-core' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="spk-howto"><?php esc_html_e( 'How to answer a Winlink assignment', 'spokares-core' ); ?></label></th>
					<td><textarea id="spk-howto" name="nets[winlink_howto]" rows="3" class="large-text<?php echo esc_attr( spokares_err_class( $errors, 'winlink_howto' ) ); ?>" maxlength="300"><?php echo esc_textarea( (string) $v( 'winlink_howto', $nets['winlink_howto'] ) ); ?></textarea>
					<p class="description"><?php esc_html_e( 'Shown above the Winlink assignments on Exercises & events.', 'spokares-core' ); ?></p>
					<?php spokares_err_text( $errors, 'winlink_howto' ); ?>
					<?php spokares_confirm_box( $errors, 'winlink_howto' ); ?></td>
				</tr>
				<tr>
					<th scope="row"><label for="spk-open"><?php esc_html_e( 'Open-slot line', 'spokares-core' ); ?></label></th>
					<td><input type="text" id="spk-open" name="nets[open_slot_line]" class="large-text<?php echo esc_attr( spokares_err_class( $errors, 'open_slot_line' ) ); ?>" maxlength="160" value="<?php echo esc_attr( (string) $v( 'open_slot_line', $nets['open_slot_line'] ) ); ?>">
					<p class="description"><?php esc_html_e( 'Shown under the rota; the “Open” tags link to it.', 'spokares-core' ); ?></p>
					<?php spokares_err_text( $errors, 'open_slot_line' ); ?>
					<?php spokares_confirm_box( $errors, 'open_slot_line' ); ?></td>
				</tr>
			</table>

			<div class="spk-preview" id="spk-net-preview" aria-live="polite">
				<h2><?php esc_html_e( 'Preview: what the site will say', 'spokares-core' ); ?></h2>
				<dl>
					<dt><?php esc_html_e( 'For members: the settings bar', 'spokares-core' ); ?></dt><dd data-preview="bar"><?php echo esc_html( $lines['bar'] ); ?></dd>
					<dt><?php esc_html_e( 'The Copy buttons copy', 'spokares-core' ); ?></dt><dd data-preview="copy"><?php echo esc_html( $lines['copy'] ); ?></dd>
					<dt><?php esc_html_e( 'How it works: the settings box', 'spokares-core' ); ?></dt><dd data-preview="settings"><?php echo esc_html( $lines['settings'] ); ?></dd>
					<dt><?php esc_html_e( 'How it works: the alternate repeater', 'spokares-core' ); ?></dt><dd data-preview="alt"><?php echo esc_html( $lines['alt'] ); ?></dd>
					<dt><?php esc_html_e( 'Home: the “From home” card', 'spokares-core' ); ?></dt><dd data-preview="from-home"><?php echo esc_html( $lines['from-home'] ); ?></dd>
					<dt><?php esc_html_e( 'How it works: “Other nets”', 'spokares-core' ); ?></dt>
					<dd><ul>
						<li data-preview="winlink"><?php echo esc_html( $lines['winlink'] ); ?></li>
						<li data-preview="simplex"><?php echo esc_html( $lines['simplex'] ); ?></li>
						<li data-preview="gmrs"><?php echo esc_html( $lines['gmrs'] ); ?></li>
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
 * @return string The value to save.
 */
function spokares_settings_text( string $field, string $typed, string $stored, array &$confirmed, array $ticks, array &$held, array &$errors, string $label ): string {
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
 * Save Net details.
 */
function spokares_handle_save_net_details(): void {
	spokares_verify_form( 'spokares_save_net_details', 'spokares_edit_net_details' );
	$in_radio = isset( $_POST['radio'] ) && is_array( $_POST['radio'] ) ? wp_unslash( $_POST['radio'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitised field by field below.
	$in_nets  = isset( $_POST['nets'] ) && is_array( $_POST['nets'] ) ? wp_unslash( $_POST['nets'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitised field by field below.
	$ticks    = isset( $_POST['confirm'] ) && is_array( $_POST['confirm'] ) ? array_map( 'sanitize_text_field', array_filter( wp_unslash( $_POST['confirm'] ), 'is_scalar' ) ) : array();
	$admin    = current_user_can( 'manage_options' );
	$nets     = spokares_opt( 'spk_nets' );
	$radio    = spokares_opt( 'spk_radio' );
	$held     = array();
	$errors   = array();

	$pin = is_array( $in_radio['primary'] ?? null ) ? $in_radio['primary'] : array();
	$ain = is_array( $in_radio['alternate'] ?? null ) ? $in_radio['alternate'] : array();

	// Primary repeater.
	$typed_call = sanitize_text_field( spokares_post_str( $pin, 'call' ) );
	$call       = spokares_call_sign( $typed_call );
	if ( '' !== $call['call'] && ! $call['dropped'] ) {
		$radio['primary']['call'] = $call['call'];
	} else {
		$held['p_call']   = $typed_call;
		$errors['p_call'] = __( 'The repeater call sign wasn’t saved: type one call sign, like W7GBU.', 'spokares-core' );
	}
	$freq = sanitize_text_field( spokares_post_str( $pin, 'freq' ) );
	if ( spokares_freq_ok( $freq ) ) {
		$radio['primary']['freq'] = $freq;
	} else {
		$held['p_freq']   = $freq;
		$errors['p_freq'] = __( 'The frequency wasn’t saved: type an amateur frequency like 147.300.', 'spokares-core' );
	}
	$offset = sanitize_text_field( spokares_post_str( $pin, 'offset' ) );
	if ( in_array( $offset, spokares_offsets(), true ) || $offset === $radio['primary']['offset'] ) {
		$radio['primary']['offset'] = $offset;
	}
	$tone = sanitize_text_field( spokares_post_str( $pin, 'tone' ) );
	if ( '' === $tone || in_array( $tone, spokares_tones(), true ) || $tone === $radio['primary']['tone'] ) {
		$radio['primary']['tone'] = $tone;
	}

	// Alternate repeater.
	$afreq = sanitize_text_field( spokares_post_str( $ain, 'freq' ) );
	if ( '' === $afreq || spokares_freq_ok( $afreq ) ) {
		$radio['alternate']['freq'] = $afreq;
	} else {
		$held['a_freq']   = $afreq;
		$errors['a_freq'] = __( 'The alternate frequency wasn’t saved: type an amateur frequency like 146.880, or leave it empty.', 'spokares-core' );
	}
	$aoff = sanitize_text_field( spokares_post_str( $ain, 'offset' ) );
	if ( '' === $aoff || in_array( $aoff, spokares_offsets(), true ) || $aoff === $radio['alternate']['offset'] ) {
		$radio['alternate']['offset'] = $aoff;
	}
	$atone = sanitize_text_field( spokares_post_str( $ain, 'tone' ) );
	if ( '' === $atone || in_array( $atone, spokares_tones(), true ) || $atone === $radio['alternate']['tone'] ) {
		$radio['alternate']['tone'] = $atone;
	}
	$radio['alternate']['show'] = ! empty( $ain['show'] );
	if ( $admin ) {
		$radio['alternate']['needs_check'] = ! empty( $ain['needs_check'] );
	}

	// Nets.
	$time = sanitize_text_field( spokares_post_str( $in_nets, 'net_time' ) );
	if ( spokares_is_hhmm( $time ) ) {
		$nets['net_time'] = $time;
	}
	$gtime = sanitize_text_field( spokares_post_str( $in_nets, 'gmrs_time' ) );
	if ( '' === $gtime || spokares_is_hhmm( $gtime ) ) {
		$nets['gmrs_time'] = $gtime;
	}
	foreach ( array( 'winlink_nth', 'simplex_nth', 'gmrs_nth' ) as $k ) {
		$weeks      = array_map( 'absint', array_filter( (array) ( $in_nets[ $k ] ?? array() ), 'is_scalar' ) );
		$nets[ $k ] = array_values( array_unique( array_filter( $weeks, static fn( $n ) => $n >= 1 && $n <= 5 ) ) );
		sort( $nets[ $k ] );
	}
	if ( $admin ) {
		$nets['needs_check'] = ! empty( $in_nets['needs_check'] );
	}
	$confirmed              = is_array( $nets['confirmed'] ?? null ) ? $nets['confirmed'] : array();
	$nets['winlink_howto']  = spokares_settings_text( 'winlink_howto', sanitize_textarea_field( spokares_post_str( $in_nets, 'winlink_howto' ) ), $nets['winlink_howto'], $confirmed, $ticks, $held, $errors, __( 'The Winlink sentence', 'spokares-core' ) );
	$nets['open_slot_line'] = spokares_settings_text( 'open_slot_line', sanitize_text_field( spokares_post_str( $in_nets, 'open_slot_line' ) ), $nets['open_slot_line'], $confirmed, $ticks, $held, $errors, __( 'The open-slot line', 'spokares-core' ) );
	// Plain one-line text on the site.
	$nets['winlink_howto'] = trim( (string) preg_replace( '/\s+/', ' ', $nets['winlink_howto'] ) );
	if ( $confirmed ) {
		$nets['confirmed'] = $confirmed;
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
