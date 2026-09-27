<?php
/**
 * Title: About: body (contents and chapters)
 * Slug: spokares/about-body
 * Categories: spokares-sections
 * Inserter: no
 * Viewport Width: 1440
 * Description: The long read: the "On this page" contents (built from the chapter headings) beside the nine chapters.
 *
 * @package spokares
 */

defined( 'ABSPATH' ) || exit;
?>
<!-- wp:group {"lock":{"move":true,"remove":true},"className":"about-body"} -->
<div class="wp-block-group about-body"><!-- wp:group {"className":"wrap longread"} -->
<div class="wp-block-group wrap longread"><!-- wp:group {"className":"longread__aside"} -->
<div class="wp-block-group longread__aside"><!-- wp:spokares/toc {"lock":{"move":true,"remove":true}} /--></div>
<!-- /wp:group -->

<!-- wp:group {"className":"longread__main prose"} -->
<div class="wp-block-group longread__main prose">
<?php
require __DIR__ . '/about-ares-acs.php';
echo "\n";
require __DIR__ . '/about-legal-basis.php';
echo "\n";
require __DIR__ . '/about-who-we-serve.php';
echo "\n";
require __DIR__ . '/about-leadership.php';
echo "\n";
require __DIR__ . '/about-joining.php';
echo "\n";
require __DIR__ . '/about-training.php';
echo "\n";
require __DIR__ . '/about-licensing.php';
echo "\n";
require __DIR__ . '/about-history.php';
echo "\n";
require __DIR__ . '/about-contact.php';
?>
</div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
