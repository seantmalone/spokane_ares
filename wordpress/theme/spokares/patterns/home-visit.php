<?php
/**
 * Title: Home: come visit
 * Slug: spokares/home-visit
 * Categories: spokares-sections
 * Inserter: no
 * Viewport Width: 1440
 * Description: From home (net time and frequency from Net Settings) and in person (place and meetings from ARES site and Meetings).
 *
 * @package spokares
 */

defined( 'ABSPATH' ) || exit;
?>
<!-- wp:group {"tagName":"section","lock":{"move":true,"remove":true},"className":"bg-dawn home-visit","anchor":"visit"} -->
<section id="visit" class="wp-block-group bg-dawn home-visit"><!-- wp:spacer {"height":"0px","className":"anchor-alias","anchor":"this-week"} -->
<div style="height:0px" aria-hidden="true" id="this-week" class="wp-block-spacer anchor-alias"></div>
<!-- /wp:spacer -->

<!-- wp:group {"className":"wrap"} -->
<div class="wp-block-group wrap"><!-- wp:heading {"className":"home-h2"} -->
<h2 class="wp-block-heading home-h2">Come visit</h2>
<!-- /wp:heading -->

<!-- wp:group {"className":"home-visit__grid"} -->
<div class="wp-block-group home-visit__grid"><!-- wp:spokares/net {"view":"from-home","lock":{"move":true,"remove":true},"className":"card card--dawn home-visit__air"} /-->

<!-- wp:group {"className":"home-visit__person"} -->
<div class="wp-block-group home-visit__person"><!-- wp:spokares/meetings {"lock":{"move":true,"remove":true}} /--></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></section>
<!-- /wp:group -->
