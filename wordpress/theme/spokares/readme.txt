=== Spokane County ARES-ACS ===
Contributors: spokares
Requires at least: 7.1
Tested up to: 7.1
Requires PHP: 8.3
Stable tag: 0.1.0
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
* templates/: page shells. page-members, page-documents and page-exercises are chosen by page slug.
* parts/: header, footer, members sub-nav. Links are typed into the parts; edit them in git, never in the Site Editor.
* blocks/brand, blocks/message-path: two fixed design blocks (the seal lockup; the How it works drawing).
* patterns/: page sections (owned by the patterns builder).
* inc/: setup, head tags, and the render filters (table labels, list roles, external links, current page, no-break spaces).

Change structure in git and deploy a new tagged version. Edits made in the
Site Editor override the theme files; use "Reset" there to go back to them.

== Copyright ==

Spokane County ARES-ACS theme, (C) Spokane County ARES-ACS. GPLv2 or later.

Fonts (assets/fonts/), SIL Open Font License 1.1, subset to Latin and Latin Extended:
* Crimson Pro, Copyright 2018 The Crimson Pro Project Authors. See OFL-crimson-pro.txt.
* Schibsted Grotesk, Copyright 2023 The Schibsted-Grotesk Project Authors. See OFL-schibsted-grotesk.txt.

Images (assets/img/): the ARES-ACS seal and the Home sunset photo belong to Spokane County ARES-ACS.

== Changelog ==

= 0.1.0 =
* First build of Option B as a block theme.
