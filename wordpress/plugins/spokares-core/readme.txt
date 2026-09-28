=== Spokane ARES core ===
Contributors: spokane-county-ares-acs
Requires at least: 7.1
Tested up to: 7.1
Requires PHP: 8.3
Stable tag: 0.1.1
License: GPL-2.0-or-later
Update URI: false

Events, the net rota, meetings, documents and the dynamic blocks for spokares.org.

== Description ==

The site plugin for spokares.org. It holds the data and the behaviour; the
`spokares` theme holds the look. Deactivating it hides the lists on the site;
the data stays in the database.

* Data: post types `spk_event` and `spk_document` (taxonomy `spk_doc_cat`),
  and the options `spk_rota`, `spk_nets`, `spk_radio`, `spk_meetings`,
  `spk_site`, `spk_tiles`.
* Screens: Net rota, Net details, Events (Add event, Regular meetings,
  Meeting rules), Documents (Add document, Hub tiles), Settings > ARES site,
  and the "Site tasks" dashboard widget.
* Blocks (PHP-only, no build step): `spokares/events`, `spokares/net`,
  `spokares/meetings`, `spokares/docs`, `spokares/toc`,
  `spokares/last-reviewed`, `spokares/asof`.
* Governance: the ARES Editor role, a shorter admin, and the server-side
  layout check that lets editors change words on Home, How it works and
  About but not the layout. Only Administrator and ARES Editor are offered
  as roles; only administrators delete or trash a page, edit or delete
  someone else's Media Library file, or make synced patterns; the Net
  details grant counts only while its holder is an ARES Editor.
* Stable document links: `/docs/<slug>/`.

The security floor (XML-RPC, headers, uploads, two-factor, SMTP, old URLs)
is the must-use plugin `spokares-hardening`, deployed next to this one.

== Where things live ==

* `spokares-core.php`: bootstrap, activation, version check.
* `inc/`: helpers, formatting, guard rails, data model, schedule engine,
  roles, blocks and their render functions, routes, files, governance, and
  one file per admin screen.
* `blocks/<name>/`: `block.json` + `render.php` for each block.
* `assets/js/`: `copy.js`, `doc-library.js`, `toc.js` (front-end modules);
  `admin-forms.js`, `editor-guard.js` (admin scripts). `assets/css/admin.css`.

The full contract is `wordpress/PLAN.md` §6; build notes are in
`wordpress/build-notes/plugin.md`.

== Changelog ==

= 0.1.1 =
* Fixes from the 2026-09-27 QA sweep (wordpress/qa/ISSUES.md).
* Settings > ARES site: the unused groups.io address fields are removed; the screen holds the meeting place only (QA-038).
* Net details: the repeater call sign (W7GBU) is administrators only, enforced on save (QA-039).

= 0.1.0 =
* First build.
