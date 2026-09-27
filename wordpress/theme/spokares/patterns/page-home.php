<?php
/**
 * Title: Home (whole page)
 * Slug: spokares/page-home
 * Categories: spokares-pages
 * Post Types: page
 * Inserter: no
 * Viewport Width: 1440
 * Description: Hero, What we do, the dawn hinge, Come visit and Join in four steps. Page setup copies this into the Home page once.
 *
 * @package spokares
 */

defined( 'ABSPATH' ) || exit;

require __DIR__ . '/home-hero.php';
echo "\n";
require __DIR__ . '/home-what-we-do.php';
echo "\n";
require __DIR__ . '/dawn-hinge.php';
echo "\n";
require __DIR__ . '/home-visit.php';
echo "\n";
require __DIR__ . '/home-join.php';
