<?php
/**
 * Title: Members: exercises and events
 * Slug: spokares/members-exercises
 * Categories: spokares-members
 * Inserter: no
 * Viewport Width: 1440
 * Description: Used by the page-exercises template. Cards and tables come from Events and Net rota in wp-admin; the Bring, After and tips lines are theme text.
 *
 * @package spokares
 */

defined( 'ABSPATH' ) || exit;
?>
<!-- wp:group {"className":"members-page wrap"} -->
<div class="wp-block-group members-page wrap"><!-- wp:group {"className":"page-head"} -->
<div class="wp-block-group page-head"><!-- wp:heading {"level":1} -->
<h1 class="wp-block-heading">Exercises &amp; events</h1>
<!-- /wp:heading --></div>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","className":"ex-next"} -->
<section class="wp-block-group ex-next"><!-- wp:heading {"className":"m-head","anchor":"next-up"} -->
<h2 id="next-up" class="wp-block-heading m-head">Next up</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"m-line ex-bring"} -->
<p class="m-line ex-bring"><strong>Bring to every exercise:</strong> your <a href="/members/documents/#go-kits">go-kit</a>, charged batteries, a programmed handheld, and <a href="/members/documents/#ics-213">ICS-213</a>, <a href="/members/documents/#ics-213rr">213RR</a>, <a href="/members/documents/#ics-214">214</a> and <a href="/members/documents/#ics-309">309</a> forms.</p>
<!-- /wp:paragraph -->

<!-- wp:spokares/events {"view":"next-up","types":"exercise","limit":2} /-->

<!-- wp:paragraph {"className":"m-line ex-after"} -->
<p class="m-line ex-after"><strong>After any exercise:</strong> send your logs to the exercise lead. ACS members: get your yearly field operation signed off.</p>
<!-- /wp:paragraph --></section>
<!-- /wp:group -->

<!-- wp:group {"className":"ex-grid"} -->
<div class="wp-block-group ex-grid"><!-- wp:group {"tagName":"section","className":"ex-section"} -->
<section class="wp-block-group ex-section"><!-- wp:heading {"className":"m-head","anchor":"upcoming"} -->
<h2 id="upcoming" class="wp-block-heading m-head">Later this season</h2>
<!-- /wp:heading -->

<!-- wp:spokares/events {"view":"later","types":"exercise,training,on-air","limit":12,"cardTypes":"exercise","cardLimit":2} /--></section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","className":"ex-section"} -->
<section class="wp-block-group ex-section"><!-- wp:heading {"className":"m-head","anchor":"winlink-assignments"} -->
<h2 id="winlink-assignments" class="wp-block-heading m-head">Winlink assignments</h2>
<!-- /wp:heading -->

<!-- wp:spokares/net {"view":"winlink","limit":8} /--></section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","className":"ex-section"} -->
<section class="wp-block-group ex-section"><!-- wp:heading {"className":"m-head","anchor":"public-service"} -->
<h2 id="public-service" class="wp-block-heading m-head">Public-service events</h2>
<!-- /wp:heading -->

<!-- wp:spokares/events {"view":"public-service"} /-->

<!-- wp:paragraph {"className":"m-line ex-tips"} -->
<p class="m-line ex-tips">First event? Read <a href="/members/documents/#public-service-tips">Preparing for public-service communications</a>.</p>
<!-- /wp:paragraph --></section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","className":"ex-section"} -->
<section class="wp-block-group ex-section"><!-- wp:heading {"className":"m-head","anchor":"past"} -->
<h2 id="past" class="wp-block-heading m-head">Past exercises</h2>
<!-- /wp:heading -->

<!-- wp:spokares/events {"view":"past","limit":8} /--></section>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
