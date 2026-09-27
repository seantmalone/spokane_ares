<?php
/**
 * Title: Members: hub (This week)
 * Slug: spokares/members-hub
 * Categories: spokares-members
 * Inserter: no
 * Viewport Width: 1440
 * Description: Used by the page-members template. Every list comes from wp-admin: Events, Regular meetings, Net rota, Net details and Hub tiles.
 *
 * @package spokares
 */

defined( 'ABSPATH' ) || exit;
?>
<!-- wp:group {"className":"members-page wrap"} -->
<div class="wp-block-group members-page wrap"><!-- wp:group {"className":"page-head hub-head"} -->
<div class="wp-block-group page-head hub-head"><!-- wp:group -->
<div class="wp-block-group"><!-- wp:heading {"level":1} -->
<h1 class="wp-block-heading">For members</h1>
<!-- /wp:heading -->

<!-- wp:spokares/asof /--></div>
<!-- /wp:group -->

<!-- wp:spokares/docs {"view":"search"} /--></div>
<!-- /wp:group -->

<!-- wp:group {"className":"hub-grid"} -->
<div class="wp-block-group hub-grid"><!-- wp:group {"tagName":"section","className":"hub-week"} -->
<section class="wp-block-group hub-week"><!-- wp:heading {"className":"m-head","anchor":"this-week"} -->
<h2 id="this-week" class="wp-block-heading m-head">This week</h2>
<!-- /wp:heading -->

<!-- wp:heading {"level":3,"className":"m-label"} -->
<h3 class="wp-block-heading m-label">Exercises</h3>
<!-- /wp:heading -->

<!-- wp:spokares/events {"view":"upcoming","types":"exercise,training","limit":2,"days":60} /-->

<!-- wp:paragraph {"className":"hub-bring has-textlink"} -->
<p class="hub-bring has-textlink"><a href="/members/exercises/#next-up">What to bring</a></p>
<!-- /wp:paragraph -->

<!-- wp:spokares/meetings {"view":"next"} /--></section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","className":"hub-net"} -->
<section class="wp-block-group hub-net"><!-- wp:heading {"className":"m-head","anchor":"rota"} -->
<h2 id="rota" class="wp-block-heading m-head">Tuesday net</h2>
<!-- /wp:heading -->

<!-- wp:spokares/net {"view":"bar"} /-->

<!-- wp:spokares/net {"view":"rota","weeks":5} /--></section>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","className":"hub-most"} -->
<section class="wp-block-group hub-most"><!-- wp:heading {"className":"m-head","anchor":"quick-links"} -->
<h2 id="quick-links" class="wp-block-heading m-head">Most used</h2>
<!-- /wp:heading -->

<!-- wp:spokares/docs {"view":"tiles"} /--></section>
<!-- /wp:group --></div>
<!-- /wp:group -->
