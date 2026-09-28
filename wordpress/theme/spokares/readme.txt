=== Spokane County ARES-ACS ===
Contributors: spokares
Requires at least: 7.1
Tested up to: 7.1
Requires PHP: 8.3
Stable tag: 0.1.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Block theme for spokares.org, built from round-3 design Option B, "Carry the Message".

== Description ==

The look of spokares.org: night to dawn to day, a red message line and amber
hop markers, Crimson Pro for voice and Schibsted Grotesk for the working parts.

This theme holds no data. Events, the net rota, meetings, documents and the
dynamic lists come from the spokares-core plugin; security settings come from
the spokares-hardening must-use plugin. Without the plugin the pages still
render, with the lists left out.

Where things live:

* theme.json: colours, fonts, type sizes, spacing; every design control in the editor is off.
* assets/css/site.css: the whole design system (front end and editor canvas).
* assets/js/header.js: closes the phone menu when the window widens past 1020px, or when a link in it goes to
  a section of the same page (the menu's Join on Home) (the only theme script).
* templates/: page shells. page-how-it-works, page-about, page-members, page-documents and page-exercises
  are chosen by page slug (How it works and About hold their own heading and full-width sections).
  page.html is the plain page (the title as the heading, text in the site column) for any other page
  an administrator adds, such as the Privacy Policy.
* parts/: header, footer, members sub-nav. Links are typed into the parts; edit them in git, never in the Site Editor.
  The header has two "Join the team" buttons so its markup order is its drawn order at every width (QA-103):
  the bar's (before the Navigation; shown under 1020px) and the Navigation's (after the four links; in the bar
  from 1020px, at the foot of the phone menu below that). Change both together.
* blocks/brand, blocks/message-path: two fixed design blocks (the seal lockup; the How it works drawing).
* patterns/: page sections (owned by the patterns builder).
* inc/: setup, head tags, and the render filters (table labels, list roles, external links, current page, no-break spaces,
  the search page's link to the document library).

Change structure in git and deploy a new tagged version. Edits made in the
Site Editor override the theme files; use "Reset" there to go back to them.

== Copyright ==

Spokane County ARES-ACS theme, (C) Spokane County ARES-ACS. GPLv2 or later.

Fonts (assets/fonts/), SIL Open Font License 1.1, subset to Latin and Latin Extended:
* Crimson Pro, Copyright 2018 The Crimson Pro Project Authors. See OFL-crimson-pro.txt.
* Schibsted Grotesk, Copyright 2023 The Schibsted-Grotesk Project Authors. See OFL-schibsted-grotesk.txt.

Images (assets/img/): the ARES-ACS seal and the Home sunset photo belong to Spokane County ARES-ACS.

== Changelog ==

= 0.1.1 =
* Fixes from the 2026-09-27 QA sweep (wordpress/qa/ISSUES.md).
* Header: the markup order is the drawn order at every width, with a second "Join the team" in the phone menu (QA-103).
* For members: the Tuesday net comes first in the markup at every width; on desktop it is the left column (QA-104).

= 0.1.0 =
* First build of Option B as a block theme.
