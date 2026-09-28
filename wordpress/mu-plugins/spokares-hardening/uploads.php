<?php
/**
 * Uploads (§5.5): per-user type allowlists (photos only for editors in the
 * media window; administrators also PDF), SVG never, a 32 MB cap, EXIF and
 * GPS stripped from photo originals (JPEG, PNG and WebP), and the DOCX/XLSX
 * inspection the Documents form uses.
 *
 * spokares-core widens the list for one call (its Documents form) through the
 * `spokares_hard_upload_mimes` filter.
 *
 * @package spokares-hardening
 */

defined( 'ABSPATH' ) || exit;

/**
 * The upload allowlist for the current user.
 *
 * @param array            $mimes Core list.
 * @param int|WP_User|null $user  User.
 */
function spokares_hard_upload_mimes( $mimes, $user = null ) {
	unset( $mimes );
	$list    = array(
		'jpg|jpeg|jpe' => 'image/jpeg',
		'png'          => 'image/png',
		'webp'         => 'image/webp',
	);
	$user_id = $user instanceof WP_User ? $user->ID : ( is_numeric( $user ) ? (int) $user : get_current_user_id() );
	if ( $user_id && user_can( $user_id, 'manage_options' ) ) {
		$list['pdf'] = 'application/pdf';
	}
	/**
	 * The per-request allowlist (spokares-core widens it for the Documents form).
	 *
	 * @param array $list Extension pattern => mime.
	 */
	$list = (array) apply_filters( 'spokares_hard_upload_mimes', $list );
	// SVG never, whoever asks.
	foreach ( array_keys( $list ) as $ext ) {
		if ( preg_match( '/(^|\|)svgz?($|\|)/i', (string) $ext ) ) {
			unset( $list[ $ext ] );
		}
	}
	return $list;
}
add_filter( 'upload_mimes', 'spokares_hard_upload_mimes', 99, 2 );

/**
 * Refuse SVG even if something else allowed it.
 *
 * @param array  $data     File data.
 * @param string $file     Path.
 * @param string $filename Name.
 */
function spokares_hard_no_svg( $data, $file, $filename ) {
	unset( $file );
	if ( preg_match( '/\.svgz?$/i', (string) $filename ) ) {
		return array(
			'ext'             => false,
			'type'            => false,
			'proper_filename' => false,
		);
	}
	return $data;
}
add_filter( 'wp_check_filetype_and_ext', 'spokares_hard_no_svg', 99, 3 );

/**
 * 32 MB cap.
 *
 * @param int $bytes Current limit.
 */
function spokares_hard_upload_size( $bytes ) {
	return min( (int) $bytes, 32 * MB_IN_BYTES );
}
add_filter( 'upload_size_limit', 'spokares_hard_upload_size', 99 );

/**
 * Inspect a DOCX or XLSX before it is accepted. Refused: macros
 * (vbaProject.bin, a macroEnabled content type), embedded objects (oleObject*,
 * activeX*), and relationships that load something from outside the file
 * (attachedTemplate, oleObject, frame or subDocument with TargetMode="External").
 * Without ZipArchive the file is refused.
 *
 * @param string $path Temporary file path.
 * @return true|WP_Error
 */
function spokares_hard_inspect_office_file( string $path ) {
	$fallback = __( 'Upload a PDF instead, or ask the webmaster.', 'spokares-hardening' );
	if ( ! class_exists( 'ZipArchive' ) ) {
		/* translators: %s: advice. */
		return new WP_Error( 'spokares_no_zip', sprintf( __( 'Word and Excel files can’t be checked on this server. %s', 'spokares-hardening' ), $fallback ) );
	}
	$zip = new ZipArchive();
	if ( true !== $zip->open( $path, ZipArchive::RDONLY ) ) {
		/* translators: %s: advice. */
		return new WP_Error( 'spokares_bad_office', sprintf( __( 'This isn’t a Word or Excel file that can be opened. %s', 'spokares-hardening' ), $fallback ) );
	}
	$bad   = '';
	$total = 0;
	for ( $i = 0; $i < $zip->numFiles; $i++ ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- ZipArchive property.
		$stat = $zip->statIndex( $i );
		if ( ! $stat ) {
			continue;
		}
		$name   = (string) $stat['name'];
		$base   = strtolower( basename( $name ) );
		$total += (int) $stat['size'];
		if ( 'vbaproject.bin' === $base || str_starts_with( $base, 'oleobject' ) || str_starts_with( $base, 'activex' ) ) {
			$bad = __( 'it contains macros or embedded objects', 'spokares-hardening' );
			break;
		}
		if ( '[content_types].xml' === strtolower( $name ) ) {
			$xml = (string) $zip->getFromIndex( $i, 2 * MB_IN_BYTES );
			if ( false !== stripos( $xml, 'macroEnabled' ) ) {
				$bad = __( 'it is a macro-enabled file', 'spokares-hardening' );
				break;
			}
		}
		if ( str_ends_with( strtolower( $name ), '.rels' ) ) {
			$xml = (string) $zip->getFromIndex( $i, 2 * MB_IN_BYTES );
			if ( preg_match_all( '/<Relationship\b[^>]*>/i', $xml, $m ) ) {
				foreach ( $m[0] as $rel ) {
					if ( preg_match( '/Type="[^"]*\/(attachedTemplate|oleObject|frame|subDocument)"/i', $rel ) && preg_match( '/TargetMode="External"/i', $rel ) ) {
						$bad = __( 'it loads a template or document from outside the file', 'spokares-hardening' );
						break 2;
					}
				}
			}
		}
	}
	$zip->close();
	if ( '' === $bad && $total > 200 * MB_IN_BYTES ) {
		$bad = __( 'it unpacks to an unusually large size', 'spokares-hardening' );
	}
	if ( '' !== $bad ) {
		/* translators: 1: the reason, 2: advice. */
		return new WP_Error( 'spokares_office_refused', sprintf( __( 'This file was refused because %1$s. %2$s', 'spokares-hardening' ), $bad, $fallback ) );
	}
	return true;
}

/**
 * Inspect every DOCX/XLSX upload, whatever path it takes.
 *
 * @param array $file $_FILES entry.
 */
function spokares_hard_prefilter( $file ) {
	if ( ! is_array( $file ) || ! empty( $file['error'] ) ) {
		return $file;
	}
	$ext = strtolower( pathinfo( (string) ( $file['name'] ?? '' ), PATHINFO_EXTENSION ) );
	if ( in_array( $ext, array( 'docx', 'xlsx', 'docm', 'xlsm', 'dotx', 'dotm' ), true ) ) {
		if ( in_array( $ext, array( 'docm', 'xlsm', 'dotm' ), true ) ) {
			$file['error'] = __( 'Macro-enabled files can’t be uploaded.', 'spokares-hardening' );
			return $file;
		}
		$ok = spokares_hard_inspect_office_file( (string) ( $file['tmp_name'] ?? '' ) );
		if ( is_wp_error( $ok ) ) {
			$file['error'] = $ok->get_error_message();
		}
	}
	return $file;
}
add_filter( 'wp_handle_upload_prefilter', 'spokares_hard_prefilter' );

/**
 * Strip EXIF and GPS data from a photo original. A JPEG is turned upright
 * (WordPress's own orientation fix) and saved again through the GD editor,
 * which keeps no metadata. A PNG or WebP keeps its pixels exactly: only its
 * metadata chunks are taken out (spokares_hard_strip_png_meta(),
 * spokares_hard_strip_webp_meta()), so a lossless file stays lossless.
 *
 * @param array $upload Upload result: file, url, type.
 */
function spokares_hard_strip_exif( $upload ) {
	if ( ! is_array( $upload ) || empty( $upload['file'] ) ) {
		return $upload;
	}
	$type = (string) ( $upload['type'] ?? '' );
	if ( 'image/png' === $type || 'image/webp' === $type ) {
		spokares_hard_strip_chunks( (string) $upload['file'], $type );
		return $upload;
	}
	if ( 'image/jpeg' !== $type ) {
		return $upload;
	}
	if ( ! class_exists( 'WP_Image_Editor_GD' ) ) {
		require_once ABSPATH . WPINC . '/class-wp-image-editor.php';
		require_once ABSPATH . WPINC . '/class-wp-image-editor-gd.php';
	}
	if ( ! WP_Image_Editor_GD::test() ) {
		return $upload; // No GD: nothing we can re-encode with; recorded in the build notes.
	}
	$only_gd = static fn() => array( 'WP_Image_Editor_GD' );
	add_filter( 'wp_image_editors', $only_gd, 99 );
	$editor  = wp_get_image_editor( $upload['file'] );
	remove_filter( 'wp_image_editors', $only_gd, 99 );
	if ( is_wp_error( $editor ) ) {
		return $upload;
	}
	if ( method_exists( $editor, 'maybe_exif_rotate' ) ) {
		$editor->maybe_exif_rotate();
	}
	$editor->set_quality( 90 );
	$editor->save( $upload['file'], 'image/jpeg' );
	return $upload;
}
add_filter( 'wp_handle_upload', 'spokares_hard_strip_exif', 5 );

/**
 * Rewrite a PNG or WebP file without its metadata chunks. The file is left
 * as it is when it can't be read as one (the upload checks decide about it).
 *
 * @param string $file Path.
 * @param string $type image/png or image/webp.
 */
function spokares_hard_strip_chunks( string $file, string $type ): void {
	if ( ! is_readable( $file ) || ! wp_is_writable( $file ) ) {
		return;
	}
	$bytes = (string) file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- a local upload being cleaned in place.
	$clean = 'image/png' === $type ? spokares_hard_strip_png_meta( $bytes ) : spokares_hard_strip_webp_meta( $bytes );
	if ( null !== $clean && $clean !== $bytes ) {
		file_put_contents( $file, $clean ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- rewriting the upload in place, before WordPress reads it.
	}
}

/**
 * A PNG without its metadata chunks: eXIf (EXIF and GPS), the text chunks
 * (tEXt, zTXt, iTXt: XMP and "Raw profile type exif" live there) and tIME.
 * The image, its transparency and its colour chunks (iCCP, sRGB, gAMA,
 * cHRM) stay byte for byte. Null when the bytes aren't a well-formed PNG.
 *
 * @param string $bytes File contents.
 */
function spokares_hard_strip_png_meta( string $bytes ): ?string {
	if ( "\x89PNG\r\n\x1a\n" !== substr( $bytes, 0, 8 ) ) {
		return null;
	}
	$drop = array( 'eXIf', 'tEXt', 'zTXt', 'iTXt', 'tIME' );
	$out  = substr( $bytes, 0, 8 );
	$size = strlen( $bytes );
	$pos  = 8;
	while ( $pos + 12 <= $size ) {
		$len  = unpack( 'N', substr( $bytes, $pos, 4 ) )[1];
		$type = substr( $bytes, $pos + 4, 4 );
		$end  = $pos + 12 + $len;
		if ( $end > $size ) {
			return null;
		}
		if ( ! in_array( $type, $drop, true ) ) {
			$out .= substr( $bytes, $pos, 12 + $len );
		}
		$pos = $end;
		if ( 'IEND' === $type ) {
			break;
		}
	}
	return $out;
}

/**
 * A WebP without its EXIF and XMP chunks, with the VP8X header's EXIF and
 * XMP flags cleared and the RIFF size corrected. The image (VP8, VP8L,
 * ALPH, animation) and ICC profile stay byte for byte. Null when the bytes
 * aren't a well-formed WebP.
 *
 * @param string $bytes File contents.
 */
function spokares_hard_strip_webp_meta( string $bytes ): ?string {
	if ( 'RIFF' !== substr( $bytes, 0, 4 ) || 'WEBP' !== substr( $bytes, 8, 4 ) ) {
		return null;
	}
	$body = '';
	$size = min( strlen( $bytes ), 8 + unpack( 'V', substr( $bytes, 4, 4 ) )[1] );
	$pos  = 12;
	while ( $pos + 8 <= $size ) {
		$type = substr( $bytes, $pos, 4 );
		$len  = unpack( 'V', substr( $bytes, $pos + 4, 4 ) )[1];
		$end  = $pos + 8 + $len + ( $len % 2 );
		if ( $pos + 8 + $len > $size ) {
			return null;
		}
		$chunk = substr( $bytes, $pos, $end - $pos );
		if ( 'VP8X' === $type && $len >= 1 ) {
			// Flags byte: 0x08 EXIF, 0x04 XMP.
			$chunk[8] = chr( ord( $chunk[8] ) & ~0x0C );
		}
		if ( 'EXIF' !== $type && 'XMP ' !== $type ) {
			$body .= $chunk;
		}
		$pos = $end;
	}
	return 'RIFF' . pack( 'V', 4 + strlen( $body ) ) . 'WEBP' . $body;
}
