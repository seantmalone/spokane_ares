<?php
/**
 * Title: Home: hero
 * Slug: spokares/home-hero
 * Categories: spokares-sections
 * Inserter: no
 * Viewport Width: 1440
 * Description: Sunset photo, headline, two buttons and the agency line. Page setup swaps the theme photo for a Media Library copy.
 *
 * @package spokares
 */

defined( 'ABSPATH' ) || exit;
?>
<!-- wp:cover {"url":"<?php echo esc_url( get_theme_file_uri( 'assets/img/1920x1200sunset.jpg' ) ); ?>","dimRatio":0,"focalPoint":{"x":0.5,"y":0.8},"lock":{"move":true,"remove":true},"align":"full","className":"hero"} -->
<div class="wp-block-cover alignfull hero"><img class="wp-block-cover__image-background" alt="" src="<?php echo esc_url( get_theme_file_uri( 'assets/img/1920x1200sunset.jpg' ) ); ?>" style="object-position:50% 80%" data-object-fit="cover" data-object-position="50% 80%"/><span aria-hidden="true" class="wp-block-cover__background has-background-dim-0 has-background-dim"></span><div class="wp-block-cover__inner-container"><!-- wp:group {"className":"wrap hero__body"} -->
<div class="wp-block-group wrap hero__body"><!-- wp:heading {"level":1,"className":"display hero__title"} -->
<h1 class="wp-block-heading display hero__title">When other systems go down, Spokane County still has a voice.</h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"hero__lede"} -->
<p class="hero__lede">We’re licensed amateur radio volunteers who back up Spokane County Emergency Management’s communications. When phones, power or the internet fail, we pass messages by voice and radio email.</p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"className":"hero__actions"} -->
<div class="wp-block-buttons hero__actions"><!-- wp:button {"className":"btn--red btn--lg"} -->
<div class="wp-block-button btn--red btn--lg"><a class="wp-block-button__link wp-element-button" href="/#join">Join the team</a></div>
<!-- /wp:button -->

<!-- wp:button {"className":"btn--ghost btn--lg"} -->
<div class="wp-block-button btn--ghost btn--lg"><a class="wp-block-button__link wp-element-button" href="/how-it-works/">See how it works</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->

<!-- wp:paragraph {"className":"hero__agency"} -->
<p class="hero__agency">Represent an agency? <a href="/about/#for-agencies">How we work with agencies</a></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div></div>
<!-- /wp:cover -->
