=== Spokane ARES core ===
Contributors: spokane-county-ares-acs
Requires at least: 7.1
Tested up to: 7.1
Requires PHP: 8.3
Stable tag: 0.3.1
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

= 0.3.1 =
* WP-CLI for the webmaster: `wp spokares rota show|set|paste`, `wp spokares meeting show|cancel|move|note|extra|clear`, `wp spokares event list|set-date|cancel`. Each write needs `--user`, runs the screen's own checks (`--confirm` stands for the "Publish it" tick), and stamps "Last saved" and the undo snapshot like the screen; `--dry-run` shows what would change (O1).
* Pages and documents share one review control: "Last reviewed … by …" and a "Mark reviewed today when I save" tick; editors no longer type review dates (D1).
* Documents: a file that passed its own checks waits, off the web, through a save refused for another box, so it isn't chosen again; unused waiting files go after 24 hours (D8).

= 0.3.0 =
* Fixes from the editor workflow review (wordpress/ux/workflows/index.html; finding ids in brackets).
* Net Control Schedule: Undo and Redo leave alone any Tuesday changed since that save and ask first when the save is someone else's or older than a day (N3). Paste the schedule, with a preview (N1). The two sentences under the schedule and the Winlink list are edited here, so every editor can change them (N7). Refused typing stays for a day, until the next save (N5).
* Net Settings: names Winlink assignments a week change takes off the site (N4); refuses an offset or tone not in the list (N8).
* Cancel or Move a Meeting: about six months of dates with Show more dates (M1); Add a one-off date for a potluck, an annual meeting or a hidden meeting, shown on Home and For members (M2).
* Events: a multi-day event with times saves and prints each time with its day (E1). A past date is asked about before publishing, not after (E2). Save draft says how many boxes need fixing (E6). Changing the type clears boxes it doesn't use, and says so (E5); so does clearing Cancelled (E4). Make a copy asks you to check the name and leaves Needs checking behind (E8). Where it shows reads the theme's list settings (E9).
* Dashboard: the next five events with direct links (E7); reminders for events undated over 30 days (E3), documents due for review, empty Most Used buttons (D12), About not reviewed in 12 months (P4), and sign-in by e-mailed codes only (G2).
* Documents: Take it off the site sets the file aside outside the web root, so Undo brings the document back whole (D6). A new checked file sets Last reviewed (D2). A Needs review view (D3); bulk review for administrators only, recording who (D4). Pull names the links it breaks, and its banner clears with a new file (D11). The file box works without JavaScript (D9); held typing is explained (D10); the Most used tick is renamed (D5).
* Page text: never-publish words are refused on save (P1). Editors can restore an earlier version when its layout matches (P2). New `wp spokares page export` for the webmaster (P3).
* Accounts: the Net grant records who gave it and when (G1). The weekly two-factor audit covers the same accounts as the enforcement (G5). Two-Factor rewording checked against 0.17.0.
* Ops: restore.sh and a Restore runbook section, host-backup.sh (pruned after 14 days), bump-version.sh, and a GitHub release per deployed tag.

= 0.2.0 =
* Volunteer-editor review (wordpress/ux/SPEC.md, REPORT.md): one plain vocabulary everywhere ("Net Control Schedule", "Net Settings", "Most Used", "Cancel or Move a Meeting", "Meeting Schedule"); menus regrouped around real tasks.
* Net Control Schedule: "No net" and "Volunteer needed" choices, typing is never lost (warns before leaving; "Show more" keeps entries), Undo/Redo say what they change.
* Meetings: a note without cancelling, and schedule changes that take effect on a date.
* Events and documents: Add and Edit show the same fields; duplicate warnings with "Open it"; one notice per save that says what changed and where it shows.
* Most Used buttons keep their words in step with the chosen document.

= 0.1.1 =
* Fixes from the 2026-09-27 QA sweep (wordpress/qa/ISSUES.md).
* Settings > ARES site: the unused groups.io address fields are removed; the screen holds the meeting place only (QA-038).
* Net details: the repeater call sign (W7GBU) is administrators only, enforced on save (QA-039).

= 0.1.0 =
* First build.
