<?php
/**
 * Regression tests for QA-012 (PLAN §5.3 uploads, §5.5, §8.3 #21): EXIF and
 * GPS data must not survive in any uploaded photo. The hardening plugin
 * (uploads.php spokares_hard_strip_exif(), on wp_handle_upload) re-saves JPEG
 * originals, but returns early for every other type, so a WebP or PNG from a
 * phone keeps its camera make, model and GPS position in the stored original,
 * which is public at its /wp-content/uploads/ address. WebP and PNG are on the
 * photo allowlist for every role that can upload (author, both editor roles,
 * administrator), so the same privacy reason applies to them.
 *
 * Each test builds a small image with GD and adds an EXIF block (camera make
 * and model, and a GPS IFD with a position and a map datum) where the format
 * keeps it: a JPEG APP1 segment, a PNG eXIf chunk, a WebP EXIF chunk (with the
 * VP8X EXIF flag). It then uploads the file as the block editor's Cover
 * Replace and media window do, through REST POST /wp/v2/media, and inspects
 * every stored file (the original and its sub-sizes) for any EXIF container
 * or for the marker strings, raw or hex-encoded (ImageMagick writes EXIF into
 * PNG text chunks as hex).
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

/**
 * Marker strings written into the EXIF block; none may be left on disk.
 */
function qa_012_markers(): array {
	return array(
		'make'  => 'QA012CamMake',
		'model' => 'QA012CamModel',
		'datum' => 'QA012GpsDatum',
	);
}

/**
 * A big-endian TIFF (EXIF) block: IFD0 with Make, Model and a GPS IFD pointer;
 * the GPS IFD with latitude, longitude and a map datum.
 */
function qa_012_tiff(): string {
	$markers = qa_012_markers();
	$make    = $markers['make'] . "\0";
	$model   = $markers['model'] . "\0";
	$datum   = $markers['datum'] . "\0";
	$entry   = static fn( int $tag, int $type, int $count, string $value4 ): string => pack( 'nnN', $tag, $type, $count ) . str_pad( $value4, 4, "\0" );

	$ifd0_off  = 8;
	$make_off  = $ifd0_off + 2 + 12 * 3 + 4;
	$model_off = $make_off + strlen( $make );
	$gps_off   = $model_off + strlen( $model );
	$pad       = $gps_off % 2 ? "\0" : '';
	$gps_off  += strlen( $pad );
	$lat_off   = $gps_off + 2 + 12 * 5 + 4;
	$lon_off   = $lat_off + 24;
	$datum_off = $lon_off + 24;

	$ifd0 = pack( 'n', 3 )
		. $entry( 0x010F, 2, strlen( $make ), pack( 'N', $make_off ) )
		. $entry( 0x0110, 2, strlen( $model ), pack( 'N', $model_off ) )
		. $entry( 0x8825, 4, 1, pack( 'N', $gps_off ) )
		. pack( 'N', 0 );
	$gps  = pack( 'n', 5 )
		. $entry( 0x0001, 2, 2, "N\0" )
		. $entry( 0x0002, 5, 3, pack( 'N', $lat_off ) )
		. $entry( 0x0003, 2, 2, "W\0" )
		. $entry( 0x0004, 5, 3, pack( 'N', $lon_off ) )
		. $entry( 0x0012, 2, strlen( $datum ), pack( 'N', $datum_off ) )
		. pack( 'N', 0 );

	return "MM\0\x2A" . pack( 'N', $ifd0_off ) . $ifd0 . $make . $model . $pad . $gps
		. pack( 'N6', 47, 1, 39, 1, 5000, 100 )
		. pack( 'N6', 117, 1, 25, 1, 3300, 100 )
		. $datum;
}

/**
 * A plain photo-like image from GD, encoded as $type.
 *
 * @param string $type jpeg, png or webp.
 */
function qa_012_gd_image( string $type ): string {
	$img = imagecreatetruecolor( 320, 240 );
	for ( $y = 0; $y < 240; $y += 8 ) {
		imagefilledrectangle( $img, 0, $y, 319, $y + 7, (int) imagecolorallocate( $img, $y % 256, 120, 255 - $y % 256 ) );
	}
	ob_start();
	if ( 'jpeg' === $type ) {
		imagejpeg( $img, null, 90 );
	} elseif ( 'png' === $type ) {
		imagepng( $img );
	} else {
		imagewebp( $img, null, 80 );
	}
	imagedestroy( $img );
	return (string) ob_get_clean();
}

/**
 * A JPEG with an EXIF APP1 segment after the JFIF APP0.
 */
function qa_012_jpeg(): string {
	$jpg  = qa_012_gd_image( 'jpeg' );
	$at   = 2;
	$app0 = unpack( 'n', substr( $jpg, 4, 2 ) )[1];
	if ( "\xFF\xE0" === substr( $jpg, 2, 2 ) ) {
		$at = 4 + $app0;
	}
	$payload = "Exif\0\0" . qa_012_tiff();
	return substr( $jpg, 0, $at ) . "\xFF\xE1" . pack( 'n', 2 + strlen( $payload ) ) . $payload . substr( $jpg, $at );
}

/**
 * A PNG with an eXIf chunk right after IHDR.
 */
function qa_012_png(): string {
	$png   = qa_012_gd_image( 'png' );
	$tiff  = qa_012_tiff();
	$chunk = pack( 'N', strlen( $tiff ) ) . 'eXIf' . $tiff . pack( 'N', crc32( 'eXIf' . $tiff ) );
	return substr( $png, 0, 33 ) . $chunk . substr( $png, 33 );
}

/**
 * A WebP in the extended format: VP8X (EXIF flag set), GD's image chunk(s),
 * then an EXIF chunk.
 */
function qa_012_webp(): string {
	$webp = qa_012_gd_image( 'webp' );
	if ( 'RIFF' !== substr( $webp, 0, 4 ) || 'WEBP' !== substr( $webp, 8, 4 ) ) {
		fail( 'GD did not write a RIFF WebP' );
	}
	$body = substr( $webp, 12 );
	if ( 'VP8X' === substr( $body, 0, 4 ) ) {
		fail( 'GD wrote an extended WebP; the fixture expects a simple one' );
	}
	$vp8x = 'VP8X' . pack( 'V', 10 ) . chr( 0x08 ) . "\0\0\0"
		. substr( pack( 'V', 320 - 1 ), 0, 3 ) . substr( pack( 'V', 240 - 1 ), 0, 3 );
	$tiff = qa_012_tiff();
	$exif = 'EXIF' . pack( 'V', strlen( $tiff ) ) . $tiff . ( strlen( $tiff ) % 2 ? "\0" : '' );
	$all  = 'WEBP' . $vp8x . $body . $exif;
	return 'RIFF' . pack( 'V', strlen( $all ) ) . $all;
}

/**
 * The chunk types of a PNG (with the tEXt/zTXt/iTXt keyword) or a WebP.
 *
 * @param string $bytes File contents.
 */
function qa_012_chunks( string $bytes ): array {
	$out  = array();
	$size = strlen( $bytes );
	if ( "\x89PNG\r\n\x1a\n" === substr( $bytes, 0, 8 ) ) {
		$pos = 8;
		while ( $pos + 8 <= $size ) {
			$len  = unpack( 'N', substr( $bytes, $pos, 4 ) )[1];
			$type = substr( $bytes, $pos + 4, 4 );
			if ( in_array( $type, array( 'tEXt', 'zTXt', 'iTXt' ), true ) ) {
				$data  = substr( $bytes, $pos + 8, min( $len, 80 ) );
				$type .= ':' . strstr( $data . "\0", "\0", true );
			}
			$out[] = $type;
			$pos  += 12 + $len;
		}
	} elseif ( 'RIFF' === substr( $bytes, 0, 4 ) && 'WEBP' === substr( $bytes, 8, 4 ) ) {
		$pos = 12;
		while ( $pos + 8 <= $size ) {
			$type = substr( $bytes, $pos, 4 );
			$len  = unpack( 'V', substr( $bytes, $pos + 4, 4 ) )[1];
			if ( 'VP8X' === $type && ( ord( $bytes[ $pos + 8 ] ) & 0x08 ) ) {
				$type .= ':exif-flag';
			}
			$out[] = $type;
			$pos  += 8 + $len + ( $len % 2 );
		}
	}
	return $out;
}

/**
 * What EXIF is left in a stored file: a list of findings, empty when clean.
 *
 * @param string $file Path.
 */
function qa_012_exif_left( string $file ): array {
	$found = array();
	$bytes = (string) file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- a local upload.
	foreach ( qa_012_markers() as $what => $marker ) {
		if ( false !== strpos( $bytes, $marker ) || false !== stripos( $bytes, bin2hex( $marker ) ) ) {
			$found[] = "$what marker '$marker'";
		}
	}
	if ( false !== strpos( $bytes, "Exif\0\0" ) ) {
		$found[] = 'an Exif APP1 header';
	}
	foreach ( qa_012_chunks( $bytes ) as $chunk ) {
		if ( in_array( $chunk, array( 'eXIf', 'EXIF', 'VP8X:exif-flag' ), true ) || preg_match( '/^(tEXt|zTXt|iTXt):.*(exif|raw profile)/i', $chunk ) ) {
			$found[] = "chunk $chunk";
		}
	}
	return $found;
}

/**
 * Upload $bytes as $filename through REST POST /wp/v2/media (as the block
 * editor's media window and the Cover block's Replace do) and return the
 * attachment ID. The framework deletes the attachment and its files.
 *
 * @param string $bytes    File contents.
 * @param string $filename File name.
 * @param string $mime     Content-Type.
 */
function qa_012_upload( string $bytes, string $filename, string $mime ): int {
	$request = new \WP_REST_Request( 'POST', '/wp/v2/media' );
	$request->set_header( 'Content-Type', $mime );
	$request->set_header( 'Content-Disposition', 'attachment; filename="' . $filename . '"' );
	$request->set_body( $bytes );
	$res = rest_ensure_response( rest_do_request( $request ) );
	expect_not_wp_error( $res, 'REST upload of ' . $filename );
	assert_same( 201, $res->get_status(), 'REST upload of ' . $filename );
	return (int) $res->get_data()['id'];
}

/**
 * Every stored file of an attachment: the original (and the pre-scaling
 * original, if any) first, then the sub-sizes.
 *
 * @param int $id Attachment ID.
 */
function qa_012_files( int $id ): array {
	$file  = (string) get_attached_file( $id );
	$files = array( 'original' => $file );
	$orig  = wp_get_original_image_path( $id );
	if ( $orig && $orig !== $file ) {
		$files['pre-scaling original'] = $orig;
	}
	$meta = (array) wp_get_attachment_metadata( $id );
	foreach ( (array) ( $meta['sizes'] ?? array() ) as $size => $data ) {
		$files[ "size $size" ] = dirname( $file ) . '/' . $data['file'];
	}
	return $files;
}

/**
 * Upload a fixture as $role and assert no stored file keeps EXIF.
 *
 * @param string $role     Role key.
 * @param string $bytes    File contents.
 * @param string $filename File name.
 * @param string $mime     Content-Type.
 */
function qa_012_assert_stripped( string $role, string $bytes, string $filename, string $mime ): void {
	$markers = qa_012_markers();
	assert_true( false !== strpos( $bytes, $markers['datum'] ), 'fixture carries the GPS block' );
	as_role( $role );
	$id = qa_012_upload( $bytes, $filename, $mime );
	assert_same( $mime, get_post_mime_type( $id ), 'stored type' );
	foreach ( qa_012_files( $id ) as $label => $path ) {
		assert_true( file_exists( $path ), "$label exists ($path)" );
		assert_same( array(), qa_012_exif_left( $path ), "$role upload $filename: EXIF/GPS left in the $label (" . basename( $path ) . ')' );
	}
}

test(
	'fixture check: the JPEG fixture\'s EXIF block is readable (GPS, make, model)',
	function () {
		if ( ! function_exists( 'exif_read_data' ) ) {
			skip( 'no exif extension' );
		}
		$tmp = wp_tempnam( 'qa-012.jpg' );
		file_put_contents( $tmp, qa_012_jpeg() ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- a temp fixture.
		$exif = @exif_read_data( $tmp ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- a bad block is the failure reported below.
		wp_delete_file( $tmp );
		assert_true( is_array( $exif ), 'exif_read_data parsed the fixture' );
		assert_same( 'QA012CamMake', $exif['Make'] ?? null, 'Make' );
		assert_same( 'QA012CamModel', $exif['Model'] ?? null, 'Model' );
		assert_same( 'N', $exif['GPSLatitudeRef'] ?? null, 'GPSLatitudeRef' );
		assert_same( array( '47/1', '39/1', '5000/100' ), $exif['GPSLatitude'] ?? null, 'GPSLatitude' );
		assert_same( 'QA012GpsDatum', $exif['GPSMapDatum'] ?? null, 'GPSMapDatum' );
	}
);

test(
	'control: a JPEG uploaded by an ARES Editor keeps no EXIF or GPS',
	function () {
		qa_012_assert_stripped( 'ares-editor', qa_012_jpeg(), 'qa-012-gps.jpg', 'image/jpeg' );
	}
);

test(
	'a PNG uploaded by an ARES Editor keeps no EXIF or GPS (eXIf chunk)',
	function () {
		qa_012_assert_stripped( 'ares-editor', qa_012_png(), 'qa-012-gps.png', 'image/png' );
	}
);

test(
	'a WebP uploaded by an ARES Editor keeps no EXIF or GPS (EXIF chunk)',
	function () {
		qa_012_assert_stripped( 'ares-editor', qa_012_webp(), 'qa-012-gps.webp', 'image/webp' );
	}
);

test(
	'a PNG or WebP uploaded by an administrator keeps no EXIF or GPS',
	function () {
		qa_012_assert_stripped( 'admin', qa_012_png(), 'qa-012-admin-gps.png', 'image/png' );
		qa_012_assert_stripped( 'admin', qa_012_webp(), 'qa-012-admin-gps.webp', 'image/webp' );
	}
);

test(
	'a PNG or WebP uploaded by an author, a core Editor or an ARES Editor with the net grant keeps no EXIF or GPS',
	function () {
		foreach ( array( 'author', 'core-editor', 'ares-net' ) as $role ) {
			qa_012_assert_stripped( $role, qa_012_png(), "qa-012-$role-gps.png", 'image/png' );
			qa_012_assert_stripped( $role, qa_012_webp(), "qa-012-$role-gps.webp", 'image/webp' );
		}
	}
);
