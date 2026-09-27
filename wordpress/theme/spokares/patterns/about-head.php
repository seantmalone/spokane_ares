<?php
/**
 * Title: About: page head
 * Slug: spokares/about-head
 * Categories: spokares-sections
 * Inserter: no
 * Viewport Width: 1440
 * Description: The dawn strip, then the title, lede and "Last reviewed" line (shown only once a review date is set in the Page review box).
 *
 * @package spokares
 */

defined( 'ABSPATH' ) || exit;
?>
<!-- wp:spacer {"height":"1px","lock":{"move":true,"remove":true},"className":"dawn-sky about-sky"} -->
<div style="height:1px" aria-hidden="true" class="wp-block-spacer dawn-sky about-sky"></div>
<!-- /wp:spacer -->

<!-- wp:group {"tagName":"section","lock":{"move":true,"remove":true},"className":"bg-dawn about-head"} -->
<section class="wp-block-group bg-dawn about-head"><!-- wp:group {"className":"wrap"} -->
<div class="wp-block-group wrap"><!-- wp:group {"className":"about-head__main"} -->
<div class="wp-block-group about-head__main"><!-- wp:heading {"level":1} -->
<h1 class="wp-block-heading">About ARES &amp; ACS</h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"lede"} -->
<p class="lede">The details behind the team, for prospective members and partner agencies.</p>
<!-- /wp:paragraph -->

<!-- wp:spokares/last-reviewed {"lock":{"move":true,"remove":true}} /--></div>
<!-- /wp:group --></div>
<!-- /wp:group --></section>
<!-- /wp:group -->
