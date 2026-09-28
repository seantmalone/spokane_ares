<?php
/**
 * Title: How it works: the weekly net
 * Slug: spokares/how-nets
 * Categories: spokares-sections
 * Inserter: no
 * Viewport Width: 1440
 * Description: Settings box (from Net Settings), the six-step run-sheet with the visitor cue, and the other nets (from Net Settings).
 *
 * @package spokares
 */

defined( 'ABSPATH' ) || exit;
?>
<!-- wp:group {"tagName":"section","lock":{"move":true,"remove":true},"className":"bg-dawn how-ch how-ch--first","anchor":"nets"} -->
<section id="nets" class="wp-block-group bg-dawn how-ch how-ch--first"><!-- wp:group {"className":"wrap"} -->
<div class="wp-block-group wrap"><!-- wp:group {"className":"how-row"} -->
<div class="wp-block-group how-row"><!-- wp:group {"className":"how-row__head"} -->
<div class="wp-block-group how-row__head"><!-- wp:heading -->
<h2 class="wp-block-heading">The weekly net</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>A net is an on-air meeting. Ours is a directed net: net control calls each group in turn, so everyone gets heard.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"how-row__body"} -->
<div class="wp-block-group how-row__body"><!-- wp:spokares/net {"view":"settings","lock":{"move":true,"remove":true}} /-->

<!-- wp:group -->
<div class="wp-block-group"><!-- wp:paragraph {"className":"how-label"} -->
<p class="how-label">What happens, in order</p>
<!-- /wp:paragraph -->

<!-- wp:list {"ordered":true,"className":"seq"} -->
<ol class="wp-block-list seq"><!-- wp:list-item -->
<li>Net control opens the net; emergency or priority traffic may break in at any time.</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>Officials check in.</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>Members check in.</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>Visitors check in.<!-- wp:list {"className":"seq__cue"} -->
<ul class="wp-block-list seq__cue"><!-- wp:list-item -->
<li><strong>Visiting?</strong> Wait for the call for visitors. Give your call sign slowly and phonetically, then your name and location. Not licensed? Just listen.</li>
<!-- /wp:list-item --></ul>
<!-- /wp:list --></li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>Simplex, no-tone and cross-band stations, then relays.</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>Traffic and late check-ins, then net control closes the net.</li>
<!-- /wp:list-item --></ol>
<!-- /wp:list --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"how-row","anchor":"other-nets"} -->
<div id="other-nets" class="wp-block-group how-row"><!-- wp:group {"className":"how-row__head"} -->
<div class="wp-block-group how-row__head"><!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Other nets</h3>
<!-- /wp:heading --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"how-row__body"} -->
<div class="wp-block-group how-row__body"><!-- wp:spokares/net {"view":"other-nets","lock":{"move":true,"remove":true}} /--></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></section>
<!-- /wp:group -->
