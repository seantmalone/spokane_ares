# QA issues: spokares.org WordPress build

Triage of the full role sweep on 2026-09-27: 10 evaluators (anonymous, subscriber, contributor, author, core Editor, ARES Editor, ARES Editor with the Net details grant, two administrator lanes, and accessibility/responsive) filed 142 raw reports. Duplicates with the same root cause are merged, giving **113 issues**. The machine-readable copy is `qa/issues.json` (same ids).

- **Kinds.** *bug*: wrong behaviour, errors, broken UI, security or permission flaws, data problems, and accessibility failures that block use. Every bug names the test runner for its regression test. *quality*: visual, copy, UX or accessibility improvements that aren't defects. *needs-decision*: an owner or EC choice comes first. *by-design*: intended per PLAN.md (section cited).
- **Status** after the fix pass, the final verification and the owner's decisions on QA-038, QA-039, QA-103 and QA-104 (all 2026-09-27; built in 0.1.1): `fixed` (with the regression test that covers it), `needs-decision` (an owner or EC choice comes first), `by-design` (intended per PLAN.md), `deferred` (with the reason) or `not reproduced`. Each issue ends with a **Resolution** paragraph. `qa/REPORT.md` and `qa/index.html` have the before and after crawl, the screenshots and the test suite.
- **Fix order.** Fix QA-005 (the e2e click helper) first, because front-end e2e tests depend on it; until then, emulate `prefers-reduced-motion: reduce` in your own tests. Groups `theme`, `plugin-admin`, `plugin-front`, `governance-security` and `dev-docs` work in parallel on their own files; `cross` runs last and may then edit the files its issues list.
- **Tests.** PHP tests go in `dev/tests/php/qa-<group>-<topic>-test.php`, browser tests in `dev/tests/e2e/qa-<group>-<topic>.test.mjs`, with `<group>` one of `theme`, `admin`, `front`, `security`, `dev`, `cross`. Name the issue id in the file header. Existing `prior-*` tests are assigned to the group that owns their code (see the ownership table) and must keep passing. `ops/ci.sh` must keep passing, and dev-only code stays under `dev/`.

## Summary

| Kind | Critical | High | Medium | Low | Total |
|---|---:|---:|---:|---:|---:|
| bug | 0 | 4 | 28 | 29 | **61** |
| quality | 0 | 0 | 5 | 43 | **48** |
| needs-decision | 0 | 0 | 2 | 0 | **2** |
| by-design | 0 | 0 | 0 | 2 | **2** |
| **Total** | 0 | 4 | 35 | 74 | **113** |

| Group | Issues | Bugs | Actionable (bug + quality) |
|---|---:|---:|---:|
| theme | 16 | 6 | 15 |
| plugin-admin | 53 | 29 | 51 |
| plugin-front | 10 | 5 | 10 |
| governance-security | 26 | 18 | 25 |
| dev-docs | 6 | 2 | 6 |
| cross | 2 | 1 | 2 |

### Status after the fix pass and the owner's decisions (2026-09-27)

| Kind | fixed | needs-decision | by-design | deferred | not reproduced | Total |
|---|---:|---:|---:|---:|---:|---:|
| bug | 61 | 0 | 0 | 0 | 0 | **61** |
| quality | 48 | 0 | 0 | 0 | 0 | **48** |
| needs-decision | 2 | 0 | 0 | 0 | 0 | **2** |
| by-design | 0 | 0 | 2 | 0 | 0 | **2** |
| **Total** | 111 | 0 | 2 | 0 | 0 | **113** |

Every bug has a regression test that failed before its fix and passes now. The kind column is the triage kind. The four items that waited on a decision (QA-038, QA-039, QA-103, QA-104) were decided by the owner on 2026-09-27 and are fixed as decided, each with PHP and browser regression tests that failed before the change.

## Fix groups and file ownership

Ownership doesn't overlap. A fixer edits only its own paths; if a fix needs another group's file, it reports that rather than editing it (the `cross` group picks such work up last).

| Group | Owns |
|---|---|
| theme | `theme/spokares/**`<br>`dev/tests/php/qa-theme-*-test.php`<br>`dev/tests/e2e/qa-theme-*.test.mjs` |
| plugin-admin | `plugins/spokares-core/inc/admin-*.php`<br>`plugins/spokares-core/inc/dashboard.php`<br>`plugins/spokares-core/inc/data.php`<br>`plugins/spokares-core/assets/css/admin.css`<br>`plugins/spokares-core/assets/js/admin-forms.js`<br>`dev/tests/php/prior-event-where-test.php`<br>`dev/tests/php/prior-save-box-test.php`<br>`dev/tests/php/prior-orphan-files-test.php`<br>`dev/tests/e2e/prior-event-where.test.mjs`<br>`dev/tests/e2e/prior-save-box.test.mjs`<br>`dev/tests/e2e/prior-orphan-files.test.mjs`<br>`dev/tests/e2e/prior-rota-callsign.test.mjs`<br>`dev/tests/php/qa-admin-*-test.php`<br>`dev/tests/e2e/qa-admin-*.test.mjs` |
| plugin-front | `plugins/spokares-core/inc/render.php`<br>`plugins/spokares-core/inc/blocks.php`<br>`plugins/spokares-core/inc/format.php`<br>`plugins/spokares-core/inc/schedule.php`<br>`plugins/spokares-core/inc/routes.php`<br>`plugins/spokares-core/inc/helpers.php`<br>`plugins/spokares-core/blocks/**`<br>`plugins/spokares-core/assets/js/copy.js`<br>`plugins/spokares-core/assets/js/doc-library.js`<br>`plugins/spokares-core/assets/js/toc.js`<br>`dev/tests/php/qa-front-*-test.php`<br>`dev/tests/e2e/qa-front-*.test.mjs` |
| governance-security | `plugins/spokares-core/inc/roles.php`<br>`plugins/spokares-core/inc/governance.php`<br>`plugins/spokares-core/inc/guards.php`<br>`plugins/spokares-core/inc/files.php`<br>`plugins/spokares-core/assets/js/editor-guard.js`<br>`plugins/spokares-core/spokares-core.php`<br>`plugins/spokares-core/readme.txt`<br>`mu-plugins/**`<br>`dev/tests/php/accounts-test.php`<br>`dev/tests/php/prior-page-guard-test.php`<br>`dev/tests/php/prior-page-template-test.php`<br>`dev/tests/php/prior-quick-edit-test.php`<br>`dev/tests/e2e/prior-quick-edit.test.mjs`<br>`dev/tests/e2e/prior-paste.test.mjs`<br>`dev/tests/php/qa-security-*-test.php`<br>`dev/tests/e2e/qa-security-*.test.mjs` |
| dev-docs | `dev/lib/**`<br>`dev/qa/**`<br>`dev/seed/**`<br>`dev/setup/**`<br>`dev/mu-plugins/**`<br>`dev/checks.sh`<br>`dev/shoot.sh`<br>`dev/shots.sh`<br>`dev/start.sh`<br>`dev/stop.sh`<br>`dev/shots.mjs`<br>`dev/blueprint.json`<br>`dev/README.md`<br>`dev/.gitignore`<br>`dev/tests/lib/**`<br>`dev/tests/run-all.sh`<br>`dev/tests/run-e2e.mjs`<br>`dev/tests/run-php.sh`<br>`dev/tests/README.md`<br>`dev/tests/php/framework-test.php`<br>`dev/tests/e2e/sample.test.mjs`<br>`dev/tests/e2e/prior-ci-gate.test.mjs`<br>`dev/tests/php/qa-dev-*-test.php`<br>`dev/tests/e2e/qa-dev-*.test.mjs`<br>`ops/**`<br>`tools/**`<br>`PLAN.md`<br>`EDITING-GUIDE.md`<br>`README.md`<br>`build-notes/**` |
| cross | `dev/tests/php/qa-cross-*-test.php`<br>`dev/tests/e2e/qa-cross-*.test.mjs` |

Paths are relative to `/Users/sean/Projects/spokane_ares/wordpress/`. `qa/` belongs to the QA lead.

## Index

| ID | Severity | Kind | Group | Test | Status | Title |
|---|---|---|---|---|---|---|
| [QA-001](#qa-001) | high | bug | governance-security | php | fixed | Core Editor can permanently delete, or 'trash', How it works and About; trash renames the slug to &lt;slug>__trashed and 404s the page site-wide |
| [QA-002](#qa-002) | high | bug | governance-security | php | fixed | The Net details grant survives demotion or role removal, is offered on any core-role profile, and its holder then escapes the two-factor floor |
| [QA-003](#qa-003) | high | bug | plugin-admin | php | fixed | A held-back end time or last day on a published event still saves the new start, so the live event shows a half-applied change ('1:00 PM–noon') |
| [QA-004](#qa-004) | high | bug | theme | e2e | fixed | A long unbreakable word (a pasted link) in an event's short line or place, or a document note, breaks the Exercises and Documents layouts |
| [QA-005](#qa-005) | medium | bug | dev-docs | none | fixed | Dev E2E and crawler click helper misfires on public pages because the theme scrolls smoothly |
| [QA-006](#qa-006) | medium | bug | governance-security | php | fixed | Lost-password form reveals whether an account exists when the reset e-mail can't be sent |
| [QA-007](#qa-007) | medium | bug | governance-security | e2e | fixed | Signed-in wp-admin, admin-ajax and admin-post responses carry only 'frame-ancestors': core replaces the hardening Content-Security-Policy |
| [QA-008](#qa-008) | medium | bug | governance-security | e2e | fixed | Unknown URLs with three or more path segments get a permanent 301 to Home instead of a 404 |
| [QA-009](#qa-009) | medium | bug | governance-security | php | fixed | Draft documents' file addresses are listed to Authors and core Editors through admin-ajax query-attachments and get-attachment |
| [QA-010](#qa-010) | medium | bug | governance-security | php | fixed | Non-admins can create and publish synced patterns (wp_block) and open the Patterns screens |
| [QA-011](#qa-011) | medium | bug | governance-security | php | fixed | Core Editor can edit, crop or rotate, and delete other users' Media Library files, including the Home hero photo |
| [QA-012](#qa-012) | medium | bug | governance-security | php | fixed | GPS and camera EXIF are kept in uploaded WebP and PNG originals (only JPEG is stripped) |
| [QA-013](#qa-013) | medium | bug | governance-security | php | fixed | Add User offers WordPress's Editor, Author, Contributor and Subscriber roles, and defaults to Subscriber |
| [QA-014](#qa-014) | medium | bug | governance-security | php | fixed | An empty list item can be saved into Home's timeline (or any lead-in list), adding a blank stop to the public page |
| [QA-015](#qa-015) | medium | bug | plugin-admin | php | fixed | Net details accepts non-amateur frequencies (hospital, fire, GMRS) although the banner and error say 'amateur only' |
| [QA-016](#qa-016) | medium | bug | plugin-admin | php | fixed | Switching a document from 'Upload a file' to 'Soon' or 'Link' leaves the old file public, with no notice |
| [QA-017](#qa-017) | medium | bug | plugin-admin | php | fixed | Choosing a refused file type under 'Replace with' unpublishes the whole document |
| [QA-018](#qa-018) | medium | bug | plugin-admin | php | fixed | 'Undo' after Move to Trash (with 'Also remove its file') republishes the document without its file |
| [QA-019](#qa-019) | medium | bug | plugin-admin | php | fixed | Event 'Where' field: the 'This is a public agency number. Publish it.' tick is never remembered |
| [QA-020](#qa-020) | medium | bug | plugin-admin | php | fixed | Saving an event drops its 'Extra form' when that document is briefly unpublished |
| [QA-021](#qa-021) | medium | bug | plugin-admin | e2e | fixed | Documents: the administrator's 'Link name (slug)' field is silently ignored |
| [QA-022](#qa-022) | medium | bug | plugin-admin | php | fixed | 'Save draft' and 'Save as draft (takes it off the site)' say 'Saved. See it on Exercises & events' and link to something not on the site |
| [QA-023](#qa-023) | medium | bug | plugin-admin | php | fixed | Documents list: sorting by 'Reviewed' hides every document that has never been reviewed |
| [QA-024](#qa-024) | medium | bug | plugin-admin | php | fixed | Regular meetings: 'Moved to' accepts a past date or another scheduled date, and the meeting then disappears from Home |
| [QA-025](#qa-025) | medium | bug | plugin-admin | e2e | fixed | Net rota: the 'This is a public agency number. Publish it.' tick in the Note column is drawn as a wide bar, not a checkbox |
| [QA-026](#qa-026) | medium | bug | plugin-admin | e2e | fixed | Net rota: arrowing onto 'Call sign' pulls focus out of the radio group into the text box |
| [QA-027](#qa-027) | medium | bug | plugin-admin | e2e | fixed | Meeting rules, Event and Document edit forms run past the right edge on phones (fieldset min-content), cutting off fields and month boxes |
| [QA-028](#qa-028) | medium | bug | plugin-admin | e2e | fixed | Hub tiles and Regular meetings don't reflow on phones, and Net rota overflows between 783 and about 1100px |
| [QA-029](#qa-029) | medium | bug | theme | e2e | fixed | The sticky header hides focused links on Shift+Tab and the About 'On this page' target (no scroll padding for the masthead) |
| [QA-030](#qa-030) | medium | bug | theme | e2e | fixed | Home hero intro text falls below 4.5:1 contrast over the photo at phone and tablet widths |
| [QA-031](#qa-031) | medium | bug | theme | e2e | fixed | A page an administrator adds (e.g. the Privacy Policy) renders with no H1, no content column, and text flush to the window edge |
| [QA-032](#qa-032) | medium | bug | cross | e2e | fixed | Appearance › Fonts is offered to administrators, but every font upload or install fails ('not allowed to upload this file type') |
| [QA-033](#qa-033) | medium | quality | plugin-front | – | fixed | The 'Next up' card prints the event's list fragment as a sentence and repeats the title as a link (Great ShakeOut card, from Sep 28) |
| [QA-034](#qa-034) | medium | quality | plugin-admin | – | fixed | Regular meetings offers Winlink workshop dates, but cancelling or moving one changes nothing on the site |
| [QA-035](#qa-035) | medium | quality | plugin-admin | – | fixed | 'Pull this file now' permanently deletes the file with one click and no confirmation |
| [QA-036](#qa-036) | medium | quality | plugin-admin | – | fixed | Library sections: a new section jumps to the top, deleting one silently hides its documents, and slugs change without warning |
| [QA-037](#qa-037) | medium | quality | plugin-admin | – | fixed | Admin live previews chatter to screen readers: the Net details preview and the rota 'Shows' cells re-announce on every keystroke |
| [QA-038](#qa-038) | medium | needs-decision | plugin-admin | php | fixed | Settings › ARES site: the groups.io 'Main group' and 'Member files' addresses are not used anywhere |
| [QA-039](#qa-039) | medium | needs-decision | theme | php | fixed | Changing the repeater call sign in Net details leaves 'W7GBU' in How it works page text and the message-path diagram |
| [QA-040](#qa-040) | low | bug | governance-security | php | fixed | Signed-in users without list_users can list accounts over REST, including the administrator's login slug |
| [QA-041](#qa-041) | low | bug | governance-security | php | fixed | Every signed-in page sends a hash of the user's e-mail address to Gravatar |
| [QA-042](#qa-042) | low | bug | governance-security | php | fixed | Any edit_posts user can create post tags and pattern categories over REST; the core Editor can also manage categories and tags |
| [QA-043](#qa-043) | low | bug | governance-security | php | fixed | The remote Pattern Directory is still reachable over REST (the server fetches wordpress.org patterns on request) |
| [QA-044](#qa-044) | low | bug | governance-security | php | fixed | The file-deletion guard doesn't count trashed documents, so a restored document can point at a deleted file |
| [QA-045](#qa-045) | low | bug | governance-security | e2e | fixed | Paginated URLs of the single pages return 200 duplicates, and /page/2/ has a canonical pointing at a 404 |
| [QA-046](#qa-046) | low | bug | governance-security | e2e | fixed | Block editor page card offers 'Order' to editors; it says 'Order updated.' but the server discards it |
| [QA-047](#qa-047) | low | bug | plugin-admin | e2e | fixed | Admin-bar Search is never removed for non-admins: the removal runs before core adds the node |
| [QA-048](#qa-048) | low | bug | plugin-admin | php | fixed | Own profile via user-edit.php?user_id=&lt;self> skips the profile trim, and the hidden Website and Biography fields are still saved |
| [QA-049](#qa-049) | low | bug | plugin-admin | e2e | fixed | Profile trim is incomplete: an empty 'Personal Options' heading, or one Media Library 'Infinite Scrolling' row, for non-admins |
| [QA-050](#qa-050) | low | bug | plugin-admin | php | fixed | The Dashboard 'Welcome to WordPress!' panel still shows: the remove_action runs before core adds it |
| [QA-051](#qa-051) | low | bug | plugin-admin | php | fixed | Event and document text fields lose their backslashes on save (double unslash) |
| [QA-052](#qa-052) | low | bug | plugin-admin | php | fixed | An event end time with no start time is refused, but the form comes back with All day ticked and the time boxes hidden |
| [QA-053](#qa-053) | low | bug | plugin-admin | php | fixed | After saving an event or document whose name contains '&', the name box shows '&amp;' |
| [QA-054](#qa-054) | low | bug | plugin-admin | php | fixed | Save handlers don't enforce the forms' length limits or reject invalid times; bad values save under 'Saved.' |
| [QA-055](#qa-055) | low | bug | plugin-admin | php | fixed | Net rota: at 52 Tuesdays the link still says 'Show 13 more' and reloads the same page |
| [QA-056](#qa-056) | low | bug | plugin-admin | php | fixed | A confirmed Winlink sentence that contains a line break is flagged again on every later save |
| [QA-057](#qa-057) | low | bug | plugin-admin | php | fixed | The Net details preview doesn't match the site: ', ,' with no GMRS time, a refused call sign shown, the alternate line's wording, and empty bullets |
| [QA-058](#qa-058) | low | bug | plugin-admin | php | fixed | Meeting rules accepts an End time with no Start, and the meeting's time then disappears from Home and the hub |
| [QA-059](#qa-059) | low | bug | plugin-admin | php | fixed | Meeting rules and Net details silently overwrite someone else's newer save |
| [QA-060](#qa-060) | low | bug | plugin-admin | php | fixed | Events list 'When' column invents a day and weekday for month- or year-precision past events |
| [QA-061](#qa-061) | low | bug | plugin-front | php | fixed | The hub's 'Next meeting' line uses the recurring 'evenings' wording for a single date |
| [QA-062](#qa-062) | low | bug | plugin-front | php | fixed | 'Later this season' rows print ':.' or '..' when the short line already ends in punctuation and the event has a More link |
| [QA-063](#qa-063) | low | bug | plugin-front | php | fixed | 'Later this season' silently drops upcoming events beyond 12 |
| [QA-064](#qa-064) | low | bug | plugin-front | php | fixed | Changing the Winlink weeks leaves old assignments published on Tuesdays that are no longer Winlink nights |
| [QA-065](#qa-065) | low | bug | plugin-front | php | fixed | A week ticked in two net groups is advertised under both in 'Other nets', but the rota shows only one |
| [QA-066](#qa-066) | low | bug | theme | e2e | fixed | The phone menu, opened below 1020px, stays open after the window widens: Close overlaps 'Join the team' and the page stays scroll-locked |
| [QA-067](#qa-067) | low | bug | theme | e2e | fixed | Nowrap buttons, tags and spans are cut off at 320px, with WCAG text spacing, or at a 200% default text size |
| [QA-068](#qa-068) | low | bug | dev-docs | none | fixed | Crawler and CDP helper: a fixed 10 s DevTools connect timeout, a role that fails to start still exits 0, and a browser that dies mid-run turns every later URL into a false error |
| [QA-069](#qa-069) | low | quality | governance-security | – | fixed | Deleting a file that a page or document uses ends in a bare 'Error in deleting the attachment.' (HTTP 500 page, alert, or REST 500) |
| [QA-070](#qa-070) | low | quality | governance-security | – | fixed | The core Editor's unfiltered_html is removed only by the DISALLOW_UNFILTERED_HTML constant |
| [QA-071](#qa-071) | low | quality | governance-security | – | fixed | Links in page text aren't checked: a 'javascript:' button link saves as a broken relative link, and http:// links are accepted without a warning |
| [QA-072](#qa-072) | low | quality | governance-security | – | fixed | No way for an administrator to see who holds the Net details grant |
| [QA-073](#qa-073) | low | quality | governance-security | – | fixed | Forced two-factor setup redirects without saying why, and the profile page throws a JS exception from a refused REST call |
| [QA-074](#qa-074) | low | quality | governance-security | – | fixed | Media Library 'View' and attachment-page links go to 404 |
| [QA-075](#qa-075) | low | quality | governance-security | – | fixed | The fail-closed notice tells administrators, who are the webmaster, to 'Contact the webmaster' |
| [QA-076](#qa-076) | low | quality | plugin-admin | – | fixed | Users with no site tasks (Subscriber, Contributor, Author) land on a blank Dashboard that says 'Add boxes from the Screen Options menu', which has no boxes |
| [QA-077](#qa-077) | low | quality | plugin-admin | – | fixed | The Profile screen's Help tab describes options that are hidden for non-admins |
| [QA-078](#qa-078) | low | quality | plugin-admin | – | fixed | The command palette in a Subscriber's toolbar offers nothing useful and fires failing REST requests on every keystroke |
| [QA-079](#qa-079) | low | quality | plugin-admin | – | fixed | The Edit Media screen and the legacy media-upload.php library open for non-admins, with dead-end links |
| [QA-080](#qa-080) | low | quality | plugin-admin | – | fixed | Dashboard 'Site tasks' numbers are hard-coded, so the core Editor sees a single task labelled '5' |
| [QA-081](#qa-081) | low | quality | plugin-admin | – | fixed | Document upload refused for a missing Privacy tick: the text under the file chooser says 'Choose the file to upload.' although a file was chosen |
| [QA-082](#qa-082) | low | quality | plugin-admin | – | fixed | Meeting rules error state: the card's title goes blank, the notice doesn't name the meeting, and the card is striped pink instead of outlined |
| [QA-083](#qa-083) | low | quality | plugin-admin | – | fixed | Net details frequency fields are too narrow on phones: '146.880' shows as '146.88' |
| [QA-084](#qa-084) | low | quality | plugin-admin | – | fixed | Holders of the Net details grant are told to 'ask the webmaster' for Meeting rules and get no link to it |
| [QA-085](#qa-085) | low | quality | plugin-admin | – | fixed | Net details: the GMRS time field's only label is 'at', and the format hints aren't linked to their fields |
| [QA-086](#qa-086) | low | quality | plugin-admin | – | fixed | Events list search only looks inside the current view, so past events and drafts show 'No events found.' |
| [QA-087](#qa-087) | low | quality | plugin-admin | – | fixed | Restore from Trash turns an event into a Draft that the default list views don't show, and the message doesn't say so |
| [QA-088](#qa-088) | low | quality | plugin-admin | – | fixed | The Dashboard's 'N items marked Needs checking' line gives no way to find the items |
| [QA-089](#qa-089) | low | quality | plugin-admin | – | fixed | Page review box: stale after a block-editor Save (the tick stays on), and a refused Page owner is dropped silently |
| [QA-090](#qa-090) | low | quality | plugin-admin | – | fixed | Document trash controls: 'Mark reviewed today' is offered in the Trash view, and 'Also remove its file' shows for documents with no file |
| [QA-091](#qa-091) | low | quality | plugin-admin | – | fixed | The Site Editor lets admins save template, part and style overrides without a warning, and opening Navigation creates a stray published menu |
| [QA-092](#qa-092) | low | quality | plugin-admin | – | fixed | Plugin form errors aren't tied to their fields (no aria-invalid or aria-describedby), and focus starts on &lt;body> |
| [QA-093](#qa-093) | low | quality | plugin-admin | – | fixed | Regular meetings table: empty row headers for the second and later dates of each meeting |
| [QA-094](#qa-094) | low | quality | plugin-front | – | fixed | /docs/&lt;slug>/ links are case-sensitive: /docs/ICS-213/ is a 404 |
| [QA-095](#qa-095) | low | quality | plugin-front | – | fixed | With no Winlink, Simplex or GMRS weeks, How it works shows an empty 'Other nets' section |
| [QA-096](#qa-096) | low | quality | plugin-front | – | fixed | On /members/, grant holders see 'Edit this list in Net details' flush above the rota table, as if it edits the rota |
| [QA-097](#qa-097) | low | quality | plugin-front | – | fixed | The copy-confirmation toast creates its live region with the first message, so the first 'Copied' may not be announced |
| [QA-098](#qa-098) | low | quality | theme | – | fixed | The search results page never shows the query, and an empty result doesn't say nothing matched |
| [QA-099](#qa-099) | low | quality | theme | – | fixed | Without JavaScript the phone 'Menu' button is shown but does nothing |
| [QA-100](#qa-100) | low | quality | theme | – | fixed | Blocks an administrator adds at the root of Home, How it works or About render full-bleed with no side gutter |
| [QA-101](#qa-101) | low | quality | theme | – | fixed | Footer links run together in every editor canvas (Site Editor and pages edited with the template shown) |
| [QA-102](#qa-102) | low | quality | theme | – | fixed | The three members templates show raw slugs ('page-members', 'page-documents', 'page-exercises') with no description |
| [QA-103](#qa-103) | low | quality | theme | e2e | fixed | Header focus order below 1020px doesn't match the visual order (Menu is reached before 'Join the team') |
| [QA-104](#qa-104) | low | quality | theme | e2e | fixed | Members hub on phones: 'Tuesday net' is moved first with CSS order, so tab and heading order jump back and forth |
| [QA-105](#qa-105) | low | quality | theme | – | fixed | The About 'On this page' floating button covers content and part of focused links near the bottom of the screen |
| [QA-106](#qa-106) | low | quality | theme | – | fixed | Search-field placeholder text is 3.7:1 contrast |
| [QA-107](#qa-107) | low | quality | cross | – | fixed | A page's excerpt (its meta description) isn't checked for phone numbers or never-publish words |
| [QA-108](#qa-108) | low | quality | dev-docs | – | fixed | The library's 'Most used' filter disagrees with the hub's 'Most used' tiles (ICS-309 'Soon' listed, ICS-214 missing) |
| [QA-109](#qa-109) | low | quality | dev-docs | – | fixed | Crawler coverage gaps: overflow inside a column isn't detected, Draft events count as missing anchors, and several reachable screens and write probes aren't in the access matrix |
| [QA-110](#qa-110) | low | quality | dev-docs | – | fixed | Ops checks miss three things: the full CSP on /wp-admin/, database overrides of templates and styles, and the DISALLOW_UNFILTERED_HTML constant (plus the php.ini upload cap) |
| [QA-111](#qa-111) | low | quality | dev-docs | – | fixed | Docs out of date: build-notes claims the editor's Order and Trash are hidden, and EDITING-GUIDE has no webmaster recipe for adding a section or page |
| [QA-112](#qa-112) | low | by-design | governance-security | – | by-design | The 32 MB upload cap isn't enforced in code: a 40 MB file is accepted and stored in dev |
| [QA-113](#qa-113) | low | by-design | plugin-admin | – | by-design | Administrators are still offered Posts, Categories, Tags, Comments and '+ New › Post', and a published post goes to a 404 |

## Issues

### QA-001

**Core Editor can permanently delete, or 'trash', How it works and About; trash renames the slug to &lt;slug>__trashed and 404s the page site-wide**

| | |
|---|---|
| Kind | bug |
| Severity | high |
| Category | security |
| Roles | core-editor |
| Group | governance-security |
| Test | php |
| Status | fixed |
| Reported by | core-editor |
| Files | `plugins/spokares-core/inc/roles.php`<br>`plugins/spokares-core/inc/governance.php`<br>`plugins/spokares-core/assets/js/editor-guard.js` |

**Repro.** Sign in with ?dev_login=qa-core-editor. (a) REST DELETE /wp-json/wp/v2/pages/&lt;about id>?force=true with a REST nonce. (b) Pages › How it works › Trash (also bulk Move to Trash, the block editor page card ⋮ › Trash, or REST DELETE without force). Load /about/ and /how-it-works/.

**Expected.** PLAN §4.1: the six pages are fixed and no editor can delete a page. The request is refused (403 rest_cannot_delete, as for the ARES Editor), no Trash action is offered, and slug and status are unchanged.

**Actual.** (a) HTTP 200 {deleted:true}: About and all its revisions are gone, /about/ is 404, header and footer links are dead; only a backup restores it. (b) wp_trash_post() writes post_name how-it-works__trashed, then spokares_page_insert_guard forces status back to publish and copies the new slug: the page stays published at /how-it-works__trashed/ while /how-it-works/ is 404 (header, footer, Home hero button). Undo gives HTTP 500 'Error in restoring the item from Trash.'; the dashboard page-text scan loses the page; only an admin can rename it back. Root cause: spokares_map_meta_cap maps delete_post/delete_page only for the three members pages, and the core Editor has delete_pages, delete_others_pages and delete_published_pages. The 7.1.2 page-card ⋮ menu still offers Trash (removeEditorPanel('post-status') doesn't reach it).

**Evidence.**
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-core-editor/after-trash-pages-list.png`
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-core-editor/ui-trash-how-it-works-notice.png`
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-core-editor/ui-trash-undo-result.png`
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-core-editor/after-trash-how-it-works-404.png`
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-core-editor/editor-about-core-editor-card-actions.png`
- REST: DELETE /wp/v2/pages/63 -> 200 {status: publish, slug: how-it-works__trashed}

**Triage.** Merged the two core-editor reports (force delete; trash rename + Undo 500): one root cause, delete caps for the fixed pages aren't mapped for non-admins. Security flaw with unrecoverable data loss. Fix: map delete_post/delete_page for the six fixed pages (or every page) to manage_options for non-admins, refuse a non-admin page trash in pre_trash_post, and unregister the page-card move-to-trash action for non-admins in editor-guard.js. Test: as_role('core-editor') REST DELETE with and without force returns 403, and wp_trash_post() leaves slug and status unchanged.

**Resolution (fixed).** roles.php maps every page delete cap (delete_post/delete_page and the delete_*_pages primitives) to manage_options, and governance.php refuses a non-admin trash or delete of a page before WordPress renames the slug. REST DELETE now answers 403 for the core Editor, the Pages list offers no Trash, and the page card has no Trash because the REST Allow header no longer includes DELETE. Regression tests: `dev/tests/php/qa-001-fixed-page-delete-test.php`, `dev/tests/php/qa-security-governance-test.php`, `dev/tests/e2e/qa-security-editor-surface.test.mjs`.

### QA-002

**The Net details grant survives demotion or role removal, is offered on any core-role profile, and its holder then escapes the two-factor floor**

| | |
|---|---|
| Kind | bug |
| Severity | high |
| Category | security |
| Roles | admin, ares-net, subscriber |
| Group | governance-security |
| Test | php |
| Status | fixed |
| Reported by | ares-editor-net, admin-custom, admin-core |
| Files | `plugins/spokares-core/inc/roles.php`<br>`mu-plugins/spokares-hardening/two-factor.php` |

**Repro.** As admin open Users › qa-ares-net (has the grant). Change Role to Subscriber (profile, or users.php 'Change role to…'), or to '— No role for this site —'. Sign in as qa-ares-net and open admin.php?page=spokares-net-details and ?page=spokares-meeting-rules; change the frequency and save. Also open user-edit.php for qa-subscriber and qa-core-editor.

**Expected.** PLAN §4.1: the grant is for ARES Editors, set by an administrator. Losing the ARES Editor role ends it; the checkbox is offered only on ARES Editor profiles; anyone who can change public facts is under the two-factor floor (§5.4) and the weekly 2FA audit (§5.8).

**Actual.** WP_User::set_role() keeps the per-user cap spokares_edit_net_details. As Subscriber, or with no role (where /wp-admin/ is 403), both screens open (200) and save ('Saved. See it on How it works'), and /how-it-works/ then shows the new frequency; the menu shows Net rota › Net details and Events › Meeting rules. spokares_hard_2fa_missing() and ops/weekly-check.sh only cover edit_posts users, so the demoted account is password-only. The checkbox, with its ARES-Editor wording, is offered on Subscriber, Contributor, Author and core Editor profiles.

**Evidence.**
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-ares-editor-net/demoted-subscriber-net-details.png`
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-ares-editor-net/demoted-norole-net-details.png`
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-admin-custom/admin-user-edit-subscriber-grant.png`
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-admin-core/demoted-subscriber-with-grant-net-details.png`
- whoami after demotion: roles [subscriber], can {spokares_edit_net_details:true, edit_posts:false}

**Triage.** Merged three reports (ares-editor-net medium, admin-custom high, admin-core high) with one root cause in roles.php. High: the usual way to revoke an editor leaves a password-only account that can change the public repeater frequency. Fix in roles.php: honour the cap only for users who also hold ares_editor (user_has_cap filter) and/or strip it on set_user_role/remove_user_role; show the checkbox only on ARES Editor profiles. Once the grant needs the ARES Editor role (edit_posts) the 2FA floor applies; the fixer confirms two-factor.php needs no change. Keep dev/tests/php/accounts-test.php passing. Test: grant, set_role('subscriber'), user_can(…, 'spokares_edit_net_details') is false and the save handler refuses.

**Resolution (fixed).** The grant counts only for ARES Editors (user_has_cap filter), is stripped on set_user_role and remove_user_role, and the checkbox appears and saves only on ARES Editor profiles. The two-factor floor also covers spokares_edit_rota and spokares_edit_net_details. Regression tests: `dev/tests/php/qa-002-net-grant-demotion-test.php`, `dev/tests/php/qa-security-governance-test.php`.

### QA-003

**A held-back end time or last day on a published event still saves the new start, so the live event shows a half-applied change ('1:00 PM–noon')**

| | |
|---|---|
| Kind | bug |
| Severity | high |
| Category | bug |
| Roles | ares-editor, ares-net, admin |
| Group | plugin-admin |
| Test | php |
| Status | fixed |
| Reported by | ares-editor |
| Files | `plugins/spokares-core/inc/admin-events.php` |

**Repro.** As ARES Editor open a published event with Start 09:00 and End 12:00. Change Start to 13:00 and End to 12:30 and Update. Or, on a Nov 7–8 event, set First day 2026-11-14 and Last day 2026-11-09. Open /members/exercises/.

**Expected.** The notice says 'the event is still on the site as it was, but these changes were held back', so start and end (the time pair and the date pair) are held back together and the live event keeps 09:00–12:00 or Nov 7–8.

**Actual.** Only the field with the problem is held back (t_end maps only to spk_time_end, end only to spk_end); the new start is saved. The Later row shows 'Thu, Oct 8, 1:00 PM–noon' (stored 13:00/12:00). The date case stores Nov 14 → Nov 8 and shows a single day, Nov 14. In both cases the notice says nothing changed.

**Evidence.**
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-ares-editor/longurl-phone-members-exercises-.png`
- ev6.py: stored after reload 13:00 12:00 / 2026-11-14 2026-11-08, notice 'Saved, and the event is still on the site as it was, but these changes were held back…'

**Triage.** Wrong facts published while the UI says they weren't; breaks the §4.4 guarantee. Fix in spokares_save_event / spokares_event_field_meta: hold start and end, and t_start and t_end, as pairs. Test: published event, post the bad pair through the handler, assert both stored values are unchanged.

**Resolution (fixed).** spokares_event_field_meta() maps the date pair and the time pair together, so a held-back end also holds back its start and the live event keeps both old values. Regression test: `dev/tests/php/qa-003-held-pair-test.php`.

### QA-004

**A long unbreakable word (a pasted link) in an event's short line or place, or a document note, breaks the Exercises and Documents layouts**

| | |
|---|---|
| Kind | bug |
| Severity | high |
| Category | visual |
| Roles | anonymous, subscriber, contributor, author, core-editor, ares-editor, ares-net, admin |
| Group | theme |
| Test | e2e |
| Status | fixed |
| Reported by | ares-editor |
| Files | `theme/spokares/assets/css/site.css` |

**Repro.** As ARES Editor add a Training event whose Short line is one 90-character link (maxlength allows it) and publish; view /members/exercises/ at 1440 and 390. Give a document a long unbroken note and view /members/documents/.

**Expected.** Long words wrap inside their column or card (EDITING-GUIDE: 'You can't break the layout'); no horizontal page scroll.

**Actual.** Desktop: the .ex-later table is 888px wide in a 588px column and draws over 'Winlink assignments'. Phone: the Next-up card content is 700px in a 358px card and the Later row runs off screen; overflow-x:clip hides it, so document scrollWidth looks fine and the crawler misses it. Documents with a long note: scrollWidth 1496 at 1440 and 1077 at 390, so the page scrolls sideways. No overflow-wrap:anywhere or table-layout rule on .ex-later, .ex-table, .ex-card, .ex-where or the library rows.

**Evidence.**
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-ares-editor/exercises-pasted-link.png`
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-ares-editor/longurl-phone-members-exercises-.png`
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-ares-editor/docs-reference-after.png`
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-ares-editor/members-long-note.png`

**Triage.** Merged the ares-editor layout report with the overflow half of the length-limit report (the server-side limit half is QA-054). High: an ordinary editor action breaks a public page for every visitor. Theme-only CSS fix (overflow-wrap:anywhere on table cells, cards and library rows; min-width:0 on flex/grid children). Test (e2e): publish an event with a long token; at 390 and 1440 no element inside .ex-later, .ex-card or the library is wider than its container.

**Resolution (fixed).** site.css: body gets overflow-wrap:break-word, and the Exercises and Documents table cells get overflow-wrap:anywhere, so a pasted link wraps inside its column at 1440 and 390. Regression test: `dev/tests/e2e/qa-004-long-words.test.mjs`.

### QA-005

**Dev E2E and crawler click helper misfires on public pages because the theme scrolls smoothly**

| | |
|---|---|
| Kind | bug |
| Severity | medium |
| Category | tooling |
| Roles | anonymous |
| Group | dev-docs |
| Test | none |
| Status | fixed |
| Reported by | anonymous |
| Files | `dev/lib/cdp.mjs`<br>`dev/tests/run-e2e.mjs` |

**Repro.** In Node: p.goto(base + '/how-it-works/'); p.click('button[data-copy-text]'); read document.querySelector('.toast'). Repeat after Emulation.setEmulatedMedia prefers-reduced-motion: reduce.

**Expected.** Page.click() (and t.click / t.clickAndWait in dev/tests/run-e2e.mjs) clicks the requested element.

**Actual.** With default media there is no toast: the mouse event lands on whatever was under the pre-scroll coordinates (scrollY mid-animation). With reduced motion it works ('Copied: W7GBU 147.300 MHz, +600 kHz offset, 100 Hz tone'). site.css sets html{scroll-behavior:smooth}, el.scrollIntoView({block:'center'}) animates, and click() reads getBoundingClientRect() at once.

**Evidence.**
- clickbug.mjs: 'reduce false … (no toast) scrollY 611' vs 'reduce true … Copied: W7GBU 147.300 MHz…'

**Triage.** Dev-only, but medium because every front-end e2e regression test in this fix pass can fail falsely or click the wrong control. Fix first: emulate prefers-reduced-motion in Page.init, or scroll with behavior:'instant' and re-measure. No regression test needed beyond the existing runner self-tests.

**Resolution (fixed).** dev/lib/cdp.mjs: Page.click() scrolls with behavior "instant" and measures the element only after its box has stopped moving, so clicks land on below-the-fold elements despite the theme's smooth scrolling. Regression test: `dev/tests/e2e/qa-005-smooth-scroll-click.test.mjs`.

### QA-006

**Lost-password form reveals whether an account exists when the reset e-mail can't be sent**

| | |
|---|---|
| Kind | bug |
| Severity | medium |
| Category | security |
| Roles | anonymous |
| Group | governance-security |
| Test | php |
| Status | fixed |
| Reported by | anonymous |
| Files | `mu-plugins/spokares-hardening/enumeration.php` |

**Repro.** With mail failing (dev has none; production: an SMTP outage or the ~100/hour cap), POST wp-login.php?action=lostpassword with user_login=editor, then user_login=nosuchuser1 (stay under 5 per IP per hour).

**Expected.** PLAN §5.3: lost password always redirects to wp-login.php?checkemail=confirm, whether or not the account exists.

**Actual.** Unknown name: 302 to checkemail=confirm. Existing account: HTTP 200, the form again with 'Sign-in failed. Check your details and try again.' (also the wrong wording for this screen). Only invalid_email and invalidcombo are converted; the WP_Error retrieve_password() returns for a real user (retrieve_password_email_failure / no_password_reset) is not. debug.log: 'spokares-hardening: wp_mail failed: Could not instantiate mail function.'

**Evidence.**
- curl: lostpw editor 200 'Sign-in failed…' vs nosuchuser1 302 …checkemail=confirm
- `/tmp/pg-9611-logs/debug.log`

**Triage.** User enumeration contrary to §5.3. Fix: any lost-password error takes the same redirect. Test: with pre_wp_mail => __return_false, a request for an existing user takes the same redirect as one for an unknown user.

**Resolution (fixed).** enumeration.php: a lost-password POST with any error except an empty name redirects to checkemail=confirm, like an unknown name, so a mail failure or no_password_reset no longer reveals the account. Regression test: `dev/tests/php/qa-006-lostpassword-enumeration-test.php`.

### QA-007

**Signed-in wp-admin, admin-ajax and admin-post responses carry only 'frame-ancestors': core replaces the hardening Content-Security-Policy**

| | |
|---|---|
| Kind | bug |
| Severity | medium |
| Category | security |
| Roles | admin, ares-editor, core-editor, anonymous |
| Group | governance-security |
| Test | e2e |
| Status | fixed |
| Reported by | admin-core |
| Files | `mu-plugins/spokares-hardening/headers.php` |

**Repro.** curl -D - with an admin cookie: /wp-admin/, /wp-admin/options-general.php, post.php?post=62&action=edit, admin-ajax.php?action=heartbeat, admin-post.php (also signed out). Compare with /, /wp-login.php and REST.

**Expected.** PLAN §5.3: every admin response carries "frame-ancestors 'self'; base-uri 'self'; form-action 'self'; object-src 'none'".

**Actual.** Only "content-security-policy: frame-ancestors 'self';". headers.php sends at init priority 0 for is_admin() requests and its static $sent flag skips the later admin_init call; core's send_frame_options_header() (admin_init priority 10) then header()-replaces the CSP. base-uri, form-action and object-src are lost exactly where sessions are privileged. ops/check-live.sh only checks frame-ancestors (see QA-110).

**Evidence.**
- curl as admin: /wp-admin/* -> content-security-policy: frame-ancestors 'self';
- core 7.1.2 wp-includes/functions.php:7282 send_frame_options_header()

**Triage.** Security header regression against §5.3. Fix: send the full CSP after core (late admin_init) or remove core's send_frame_options_header for admin. Test (e2e): /wp-admin/ as admin and as ares-editor has base-uri, form-action and object-src in its CSP.

**Resolution (fixed).** headers.php unhooks core's send_frame_options_header and sends the full header set again at admin_init and login_init priority 99, so wp-admin, admin-ajax and admin-post carry all four CSP directives. Regression test: `dev/tests/e2e/qa-007-admin-csp.test.mjs`.

### QA-008

**Unknown URLs with three or more path segments get a permanent 301 to Home instead of a 404**

| | |
|---|---|
| Kind | bug |
| Severity | medium |
| Category | bug |
| Roles | anonymous |
| Group | governance-security |
| Test | e2e |
| Status | fixed |
| Reported by | anonymous |
| Files | `mu-plugins/spokares-hardening/redirects.php`<br>`mu-plugins/spokares-hardening/content-surface.php` |

**Repro.** curl -sI /members/documents/ics-213/, /members/exercises/set-2026/, /docs/ics-213/extra/, /about/x/y/, /component/content/article/12-foo.html, /images/stories/roster.pdf, /a/b/c/d/, /members/documents/foo/?q=x.

**Expected.** 404 (or the map's 410). PLAN §5.6: old and spam URLs never land on a real page.

**Actual.** HTTP 301 (X-Redirect-By: WordPress) to http://host/, query string kept. One- and two-segment unknown paths correctly 404. The request resolves to the static front page (probably the page-attachment rewrite, attachment=&lt;last segment>, which content-surface.php special-cases) and redirect_canonical() sends it to home_url('/'). Browsers and search engines cache the 301; guessed member URLs and deep Joomla links silently become Home.

**Evidence.**
- curl -sI /members/documents/ics-213/ -> 301 location http://127.0.0.1:9611/
- curl -sI /about/zzz/ -> 404 (control)

**Triage.** Wrong status for unknown URLs, against §5.6. The fixer confirms the rewrite path before changing it. Test (e2e): several 3+-segment unknown paths return 404 with no Location header.

**Resolution (fixed).** content-surface.php: a non-empty path that matched no rewrite rule is a 404 whatever the front controller sets as PHP_SELF, so deep unknown URLs no longer 301 to Home. Regression test: `dev/tests/e2e/qa-008-deep-unknown-404.test.mjs`.

### QA-009

**Draft documents' file addresses are listed to Authors and core Editors through admin-ajax query-attachments and get-attachment**

| | |
|---|---|
| Kind | bug |
| Severity | medium |
| Category | security |
| Roles | author, core-editor |
| Group | governance-security |
| Test | php |
| Status | fixed |
| Reported by | author |
| Files | `plugins/spokares-core/inc/files.php` |

**Repro.** As admin save a draft document with an uploaded PDF (Privacy ticked). As qa-author POST admin-ajax.php action=query-attachments&query[posts_per_page]=100 (no nonce needed) and action=get-attachment&id=&lt;id> (ids are sequential). Fetch the returned URL signed out.

**Expected.** A user who can't open Documents never learns a draft document's file URL; PLAN §5.5 relies on the random suffix. REST already refuses (GET /wp/v2/media/&lt;id> -> 403).

**Actual.** Both return the attachment with its url (…/uploads/2026/09/test-9001bfbf.pdf) and uploadedTo = the draft; the file is served to anyone (200). Same for qa-core-editor. Core checks only upload_files; the plugin has no ajax_query_attachments_args filter or get-attachment guard.

**Evidence.**
- `/private/tmp/claude-501/-Users-sean-Projects/4d6153be-1331-4cf4-9e9d-4432723cc013/scratchpad/eval-author/evidence/author-query-attachments.json`
- `/private/tmp/claude-501/-Users-sean-Projects/4d6153be-1331-4cf4-9e9d-4432723cc013/scratchpad/eval-author/evidence/author-get-attachment-76.json`
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-author/attachment-docfile-desktop.png`

**Triage.** Defeats the §5.5 unguessable-draft-file control. Fix in files.php: ajax_query_attachments_args excludes attachments whose parent is an spk_document the user can't edit, plus a priority-0 guard on wp_ajax_get-attachment. Test: as_role('author'), the ajax query doesn't return the draft document's attachment and get-attachment is refused.

**Resolution (fixed).** files.php hides the files of documents the user can't edit from query-attachments, get-attachment and the Media list view; the QA-011 mapping refuses the core Editor's REST and edit access. Regression test: `dev/tests/php/qa-009-draft-file-ajax-test.php`.

### QA-010

**Non-admins can create and publish synced patterns (wp_block) and open the Patterns screens**

| | |
|---|---|
| Kind | bug |
| Severity | medium |
| Category | security |
| Roles | contributor, author, core-editor, ares-editor, ares-net |
| Group | governance-security |
| Test | php |
| Status | fixed |
| Reported by | author, contributor, core-editor |
| Files | `mu-plugins/spokares-hardening/content-surface.php`<br>`plugins/spokares-core/inc/roles.php`<br>`plugins/spokares-core/assets/js/editor-guard.js` |

**Repro.** As qa-author (or qa-core-editor) POST /wp-json/wp/v2/blocks {title, status:publish, content with a phone number}. Open /wp-admin/edit.php?post_type=wp_block as contributor, author, core Editor and ARES Editor. In the page block editor open More (⋮) › Manage patterns.

**Expected.** PLAN §4.1 and §4.3 layer 6: only administrators create content and no one inserts patterns, so non-admins can't create wp_block or reach the Patterns screens.

**Actual.** Author: 201, pattern published; core Editor: same. The Patterns list opens (200) for contributor, author, core Editor and ARES Editor, with 'Import from JSON' and an 'Add Pattern' button that leads to a 403. Published patterns appear in every admin's inserter under 'My patterns' and the author keeps edit rights, so a pattern an admin inserts into a page could later be changed by the author, skipping the layout lock and the never-publish check. Core maps wp_block create_posts to publish_posts; the site remaps create_posts only for post and page. The editor More menu offers 'Manage patterns'.

**Evidence.**
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-author/patterns-list-desktop.png`
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-author/pattern-edit-desktop.png`
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-contributor/patterns-list-desktop.png`
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-core-editor/patterns-list.png`
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-core-editor/editor-about-core-editor-more-menu.png`

**Triage.** Merged author (medium), contributor (Patterns list by URL) and the wp_block half of the core-editor report. Fix: map every wp_block capability to manage_options for non-admins (register_post_type_args), hide 'Manage patterns' for non-admins, and check the page block editor still loads for ARES Editors without /wp/v2/blocks 403 console errors. The crawler gap is QA-109. Test: as author and core-editor, REST POST /wp/v2/blocks is refused and the wp_block edit_posts cap is false.

**Resolution (fixed).** Every wp_block cap except read maps to manage_options; non-admins get an empty GET /wp/v2/blocks (no 403 in the editor) and More › Manage patterns is hidden for layout-locked users. Regression tests: `dev/tests/php/qa-010-synced-patterns-test.php`, `dev/tests/e2e/qa-security-editor-surface.test.mjs`.

### QA-011

**Core Editor can edit, crop or rotate, and delete other users' Media Library files, including the Home hero photo**

| | |
|---|---|
| Kind | bug |
| Severity | medium |
| Category | security |
| Roles | core-editor |
| Group | governance-security |
| Test | php |
| Status | fixed |
| Reported by | core-editor |
| Files | `plugins/spokares-core/inc/roles.php`<br>`plugins/spokares-core/inc/files.php` |

**Repro.** As qa-core-editor open /wp-admin/post.php?post=&lt;hero id>&action=edit, Edit Image › rotate › Save (or admin-ajax action=image-editor&do=save&target=all); load /. REST DELETE /wp/v2/media/&lt;an unused file>?force=true.

**Expected.** As for the ARES Editor, other users' attachments are refused (403: no edit_others_posts). PLAN §4.1: no editor can delete a file.

**Actual.** The full Edit Media screen opens with Edit Image and Delete permanently. Rotating the hero succeeded; the Home hero &lt;img> lost srcset, sizes, width/height and fetchpriority, so phones download the 1920px original and the LCP hint is gone. Unused files can be deleted (delete_post true); the in-use guard stopped only the hero, with a bare HTTP 500 (QA-069).

**Evidence.**
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-core-editor/attachment-61-edit.png`
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-core-editor/media-upload-library.png`
- image-editor ajax: {success:true, msg:'Image saved'}

**Triage.** Permission flaw for the core Editor role (edit_others_posts, delete_others_posts). Fix: map edit_post/delete_post on attachments to manage_options for non-admins unless they own the file (and never for an in-use file). The screen redirects (post.php for attachments, media-upload.php) are QA-079 in plugin-admin. Test: as_role('core-editor'), current_user_can edit_post on the hero and delete_post on another user's file are false.

**Resolution (fixed).** roles.php: editing or deleting an attachment needs manage_options unless the user uploaded it, so the core Editor can no longer edit, crop, rotate or delete the Home hero or other people's files. Regression test: `dev/tests/php/qa-011-others-media-test.php`.

### QA-012

**GPS and camera EXIF are kept in uploaded WebP and PNG originals (only JPEG is stripped)**

| | |
|---|---|
| Kind | bug |
| Severity | medium |
| Category | security |
| Roles | author, core-editor, ares-editor, ares-net, admin |
| Group | governance-security |
| Test | php |
| Status | fixed |
| Reported by | author |
| Files | `mu-plugins/spokares-hardening/uploads.php` |

**Repro.** Upload a small WebP and a PNG carrying EXIF GPS (e.g. 47°39'30"N 117°25'W) and Make/Model through REST /wp/v2/media or the Cover's Replace; download the stored originals and read their EXIF.

**Expected.** No location data in any published photo; WebP and PNG are on the photo allowlist (§5.5).

**Actual.** JPEG is stripped. WebP and PNG keep the full EXIF block, GPS included, and are served publicly. spokares_hard_strip_exif returns early unless the type is image/jpeg.

**Evidence.**
- `/private/tmp/claude-501/-Users-sean-Projects/4d6153be-1331-4cf4-9e9d-4432723cc013/scratchpad/eval-author/evidence/gps.webp`
- `/private/tmp/claude-501/-Users-sean-Projects/4d6153be-1331-4cf4-9e9d-4432723cc013/scratchpad/eval-author/evidence/gps.png`
- `/private/tmp/claude-501/-Users-sean-Projects/4d6153be-1331-4cf4-9e9d-4432723cc013/scratchpad/eval-author/evidence/gps.jpg`

**Triage.** PLAN §5.3 only promises JPEG stripping, so this extends its scope; classed as a bug because the privacy reason behind §5.3 and §8.3 #21 applies equally to WebP and PNG, which §5.5 allows. Fix: re-save WebP through the image editor and drop PNG eXIf chunks. Test: sideload fixture WebP/PNG files with GPS; the stored files have no EXIF.

**Resolution (fixed).** uploads.php rewrites PNG and WebP originals without their metadata chunks (PNG eXIf/tEXt/zTXt/iTXt/tIME; WebP EXIF/XMP and the VP8X flags), pixels unchanged. Regression test: `dev/tests/php/qa-012-exif-webp-png-test.php`.

### QA-013

**Add User offers WordPress's Editor, Author, Contributor and Subscriber roles, and defaults to Subscriber**

| | |
|---|---|
| Kind | bug |
| Severity | medium |
| Category | security |
| Roles | admin |
| Group | governance-security |
| Test | php |
| Status | fixed |
| Reported by | core-editor, admin-core |
| Files | `plugins/spokares-core/inc/roles.php` |

**Repro.** As admin open Users › Add User and look at Role; check Settings › General › New User Default Role.

**Expected.** PLAN §4.1: the only accounts are Administrator and ARES Editor, so only those two are offered and ARES Editor is preselected.

**Actual.** The dropdown lists ARES Editor, Subscriber (selected; default_role is 'subscriber'), Contributor, Author, Editor, Administrator. 'Editor' is the natural pick for a volunteer editor, and that role carries the page-delete (QA-001), media (QA-011), pattern (QA-010) and category (QA-042) powers. The plugin has no editable_roles handling.

**Evidence.**
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-core-editor/admin-user-new-roles.png`
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/baseline/admin/user-new-desktop.png`

**Triage.** Merged core-editor (roles offered) and admin-core (default Subscriber). A one-click route to an unintended, over-powered account. Fix in roles.php: editable_roles keeps administrator and ares_editor (plus a user's current role on their own edit screen), and the role sync sets default_role to ares_editor. Test: as admin, get_editable_roles() keys are administrator and ares_editor; default_role is ares_editor.

**Resolution (fixed).** Add User offers Administrator and ARES Editor only (an existing account also keeps its current role and Subscriber, the demotion path); the default role is ares_editor. Regression tests: `dev/tests/php/qa-013-offered-roles-test.php`, `dev/tests/php/qa-security-governance-test.php`.

### QA-014

**An empty list item can be saved into Home's timeline (or any lead-in list), adding a blank stop to the public page**

| | |
|---|---|
| Kind | bug |
| Severity | medium |
| Category | bug |
| Roles | ares-editor, ares-net, core-editor |
| Group | governance-security |
| Test | php |
| Status | fixed |
| Reported by | ares-editor |
| Files | `plugins/spokares-core/inc/governance.php`<br>`plugins/spokares-core/assets/js/editor-guard.js` |

**Repro.** As ARES Editor open Home in the block editor, press Enter at the start or end of a 'What we do' timeline item and leave the new item empty, Save, view Home.

**Expected.** An empty item is refused, removed on save, or at least warned about, as items without bold first words are.

**Actual.** 'Page updated.' with only the bold-words warning; the saved HTML has &lt;li>&lt;/li> and Home shows a fifth timeline dot with no title or text. editor-guard.js missingLeadIn() skips empty items (if (words && …)); the server skeleton accepts a 'bare list item'.

**Evidence.**
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-ares-editor/home-empty-list-item.png`
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-ares-editor/editor-home-saved.png`

**Triage.** Broken public output from a normal keystroke. Fix: for non-admins the server refuses (or strips) empty core/list-item blocks, and editor-guard warns. Test: as_role('ares-editor'), a REST save of Home with an added empty &lt;li> is refused or stored without it.

**Resolution (fixed).** governance.php strips empty list items from layout-locked saves on every save path, and editor-guard.js warns after a save. Regression tests: `dev/tests/php/qa-014-empty-list-item-test.php`, `dev/tests/php/qa-security-governance-test.php`.

### QA-015

**Net details accepts non-amateur frequencies (hospital, fire, GMRS) although the banner and error say 'amateur only'**

| | |
|---|---|
| Kind | bug |
| Severity | medium |
| Category | security |
| Roles | ares-net, admin |
| Group | plugin-admin |
| Test | php |
| Status | fixed |
| Reported by | ares-editor-net |
| Files | `plugins/spokares-core/inc/admin-net.php` |

**Repro.** As qa-ares-net set Frequency to 155.340 (hospital HEAR) and save; the alternate to 154.280 (fire mutual aid); the primary to 462.550 (GMRS) or 999.999.

**Expected.** The banner says 'Amateur frequencies only. Never county, hospital, SHARES, 800 MHz or channel numbers.' A value outside the amateur bands is held back and outlined, as 147.3 and 851.012 are.

**Actual.** Each saves with 'Saved.' and is published at once on /members/, How it works and Home. spokares_freq_ok() checks only the ddd.ddd shape and refuses 806–870 MHz.

**Evidence.**
- POST radio[primary][freq]=155.340 -> 'Saved. See it on How it works'; bar '8:00 PM W7GBU 155.340 MHz'

**Triage.** PLAN §3.4 specifies only the regex, but the screen's own banner and the never-publish rule promise more. Fix: accept only amateur band ranges (e.g. 28–29.7, 50–54, 144–148, 222–225, 420–450, 902–928, 1240–1300 MHz). Test: post_form save_net_details with 155.340 holds the field back and keeps the stored value.

**Resolution (fixed).** spokares_freq_ok() requires the ddd.ddd shape and an amateur band (spokares_amateur_bands()); the JS preview mirrors the bands. Regression test: `dev/tests/php/qa-015-amateur-frequencies-test.php`.

### QA-016

**Switching a document from 'Upload a file' to 'Soon' or 'Link' leaves the old file public, with no notice**

| | |
|---|---|
| Kind | bug |
| Severity | medium |
| Category | security |
| Roles | ares-editor, ares-net, admin |
| Group | plugin-admin |
| Test | php |
| Status | fixed |
| Reported by | admin-custom |
| Files | `plugins/spokares-core/inc/admin-documents.php` |

**Repro.** Publish a document with an uploaded PDF; change 'Where the file is' to 'Not available yet (Soon)' or 'Link to another site'; Update; request the old file URL.

**Expected.** Taking a file down removes it from the web, as Replace and Trash do by default (§5.5, risk 8), or at least warns and offers removal.

**Actual.** Saved with no notice; the old file still returns 200. The form hides the upload area, so the editor can't see the file; only the admin 'Pull this file now' action removes it. The format stays 'PDF' after switching to a link, so the library shows a stale PDF badge.

**Evidence.**
- curl: old file after switching to Soon: 200
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-admin-custom/public-documents-new-section-first.png`

**Triage.** Contradicts §5.5 (old files leave the web). Fix in spokares_save_document: when the source leaves 'upload', remove the file through the existing files.php helper (respecting other uses) and reset the format, or warn. Test: publish an upload document, switch to Soon, the attachment is gone (or a notice is set) and the format is cleared.

**Resolution (fixed).** Switching a document away from Upload removes the old file through spokares_remove_document_file(); a shared file, or an unticked "Remove the old file", is kept with a warning that names why. Regression test: `dev/tests/php/qa-016-source-switch-file-test.php`.

### QA-017

**Choosing a refused file type under 'Replace with' unpublishes the whole document**

| | |
|---|---|
| Kind | bug |
| Severity | medium |
| Category | bug |
| Roles | ares-editor, ares-net, admin |
| Group | plugin-admin |
| Test | php |
| Status | fixed |
| Reported by | ares-editor |
| Files | `plugins/spokares-core/inc/admin-documents.php` |

**Repro.** Publish a document with an uploaded PDF (/docs/&lt;slug>/ gives 302). Choose a .php (or any type outside PDF/DOCX/XLSX/JPEG/PNG) under 'Replace with' and Update.

**Expected.** The replacement is refused and the document stays published with its current file, as happens for a mislabelled fake.pdf.

**Actual.** The document is demoted to Draft ('File not uploaded: use a PDF, DOCX, XLSX, JPEG or PNG file.' and 'Draft saved. Drafts never show on the site.'). /docs/&lt;slug>/ becomes 404, the row leaves the library, and a tiled document loses its hub tile. spokares_document_problems() treats any failed upload as a publishing problem even when a good file is attached.

**Evidence.**
- doc1.py: == php -> message=10 NOTICE error File not uploaded… STATUS Draft
- doc2.py: after bad replace: /docs 404 | in library False

**Triage.** A rejected replacement shouldn't take a live document down. Test: published upload document, post a refused replacement through the handler; status stays publish and spk_file is unchanged.

**Resolution (fixed).** A refused upload blocks publishing only when the document has no good file; the refusal is still reported. Regression test: `dev/tests/php/qa-017-refused-replacement-test.php`.

### QA-018

**'Undo' after Move to Trash (with 'Also remove its file') republishes the document without its file**

| | |
|---|---|
| Kind | bug |
| Severity | medium |
| Category | ux |
| Roles | ares-editor, ares-net, admin |
| Group | plugin-admin |
| Test | php |
| Status | fixed |
| Reported by | admin-custom |
| Files | `plugins/spokares-core/inc/admin-documents.php` |

**Repro.** Publish a document with an uploaded PDF. On its form click Move to Trash with 'Also remove its file from the web' ticked (the file URL is now 404). Click Undo on the list notice.

**Expected.** Undo isn't offered once the file is gone, or it restores the document as a Draft and says the file was removed and must be uploaded again.

**Actual.** The document comes back Published, source Upload, no file. The form shows 'Choose the file to upload.'; the library lists it as 'Soon' with a stale PDF badge; /docs/&lt;slug>/ is 404; nothing tells the user.

**Evidence.**
- curl: Undo -> 'Status: Published: on the site'; public row 'QA test upload doc PDF Soon'

**Triage.** Publishes a broken document without warning. Fix: on untrash of a document whose file was removed, restore as Draft (wp_untrash_post_status) with a notice. Test: trash with removal, wp_untrash_post(), status is draft.

**Resolution (fixed).** A wp_untrash_post_status filter (priority 20, after core's Undo filter) brings an upload document with no file back as a Draft, with a warning to upload the file again. Regression test: `dev/tests/php/qa-018-undo-trash-file-test.php`.

### QA-019

**Event 'Where' field: the 'This is a public agency number. Publish it.' tick is never remembered**

| | |
|---|---|
| Kind | bug |
| Severity | medium |
| Category | bug |
| Roles | ares-editor, ares-net, admin |
| Group | plugin-admin |
| Test | php |
| Status | fixed |
| Reported by | admin-custom |
| Files | `plugins/spokares-core/inc/admin-events.php` |

**Repro.** Edit a published event, set Where to 'Spokane DEM, 509-477-2204', Update (held back with the tick). Tick it and Update (saves). Update again with no change. Draft case: tick and Save draft, then Publish without ticking.

**Expected.** Once ticked, the Where text is confirmed (spk_confirmed hash, §4.4) and later saves don't flag it.

**Actual.** Every later save holds the place back again ('The place has a phone number…'), and a draft saved with the tick is demoted again on Publish. spokares_save_event() records confirmations only for title, summary, tasks, main_label and links (admin-events.php ~line 308), while spokares_event_problems() checks 'where'.

**Evidence.**
- admin-events.php:308 foreach ( array( 'title', 'summary', 'tasks', 'main_label', 'links' ) as $field )

**Triage.** Breaks the §4.4 confirm rule for one field. dev/tests/php/prior-event-where-test.php (the earlier Where fix) must keep passing. Test: save once with the tick, then a plain save has no problem and the event stays published.

**Resolution (fixed).** "where" is now in the list of confirmed fields that spokares_save_event() remembers. Regression tests: `dev/tests/php/qa-019-where-confirm-test.php`, `dev/tests/php/prior-event-where-test.php`.

### QA-020

**Saving an event drops its 'Extra form' when that document is briefly unpublished**

| | |
|---|---|
| Kind | bug |
| Severity | medium |
| Category | bug |
| Roles | ares-editor, ares-net, admin |
| Group | plugin-admin |
| Test | php |
| Status | fixed |
| Reported by | admin-custom |
| Files | `plugins/spokares-core/inc/admin-events.php` |

**Repro.** Draft the document used as the SET's Extra form; Update the SET event (any change); republish the document; reopen the event and /members/exercises/.

**Expected.** The reference is kept (Hub tiles keeps an unpublished choice, labelled 'not shown…') and the link returns when the document is republished.

**Actual.** The Extra form select lists only published documents, opens on '— none —', posts spk_extra_doc=0, and the reference is deleted for good; the SET card no longer links the Field Situation Report.

**Evidence.**
- curl: selected option while the doc is a draft: []; after republishing: still none
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/baseline/admin/event-edit-desktop.png`

**Triage.** Silent data loss. Fix: offer the stored document in the select (marked 'not shown') or don't overwrite when it isn't offered. Test: set spk_extra_doc, draft the document, save the event through the handler, the meta is kept.

**Resolution (fixed).** The Extra form select also offers a stored document that isn't published, labelled "(not shown: the document isn't published)", so an untouched Update keeps the reference. Regression test: `dev/tests/php/qa-020-extra-form-draft-doc-test.php`.

### QA-021

**Documents: the administrator's 'Link name (slug)' field is silently ignored**

| | |
|---|---|
| Kind | bug |
| Severity | medium |
| Category | bug |
| Roles | admin |
| Group | plugin-admin |
| Test | e2e |
| Status | fixed |
| Reported by | anonymous, admin-custom |
| Files | `plugins/spokares-core/inc/admin-common.php`<br>`plugins/spokares-core/inc/admin-documents.php` |

**Repro.** As admin edit a published document (e.g. RATPAC or ICS 214), change 'Admin only › Link name (slug)', Update. Also Add document with a typed slug and Save draft.

**Expected.** The slug, and so /docs/&lt;slug>/ and the row anchor, changes to the typed value, as the field's note promises.

**Actual.** The notice says 'Saved. See it on Documents & forms' but the slug is unchanged; a new draft's slug is dropped. The form has two inputs named post_name: #spk-slug and core's #post_name in #slugdiv, which is hidden but still rendered for admins and comes later, so its old value wins. spokares_replace_submitdiv() removes slugdiv only for non-admins.

**Evidence.**
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-admin-custom/scratch-document-slug-typed.png`
- slug.mjs: after Update {spk:'ratpac', core:'ratpac', notice:'Saved. See it on Documents & forms'}

**Triage.** Merged the anonymous-lane and admin-custom reports. Fix: remove slugdiv for everyone on spk_document. Test (e2e; the duplicate-name DOM is the bug): admin changes Link name, Updates, and the slug changes.

**Resolution (fixed).** Core's slug box is removed for everyone on documents, so #spk-slug is the only post_name field. The final verifier also fixed the side finding: the field's pattern [a-z0-9-]* was invalid under the browser's v flag (console error, no validation); it is now [a-z0-9\-]*. Regression tests: `dev/tests/e2e/qa-021-document-slug.test.mjs`, `dev/tests/e2e/qa-cross-pattern-attributes.test.mjs`.

### QA-022

**'Save draft' and 'Save as draft (takes it off the site)' say 'Saved. See it on Exercises & events' and link to something not on the site**

| | |
|---|---|
| Kind | bug |
| Severity | medium |
| Category | bug |
| Roles | ares-editor, ares-net, admin |
| Group | plugin-admin |
| Test | php |
| Status | fixed |
| Reported by | ares-editor |
| Files | `plugins/spokares-core/inc/admin-common.php`<br>`plugins/spokares-core/inc/admin-events.php`<br>`plugins/spokares-core/inc/admin-documents.php` |

**Repro.** Open a published event and click 'Save as draft (takes it off the site)'; or Add event › Save draft; or Add document › Save draft.

**Expected.** 'Draft saved. Drafts never show on the site.' (message 10, already defined in spokares_event_messages), with no link to the public page.

**Actual.** The redirect carries message=4: 'Saved. See it on Exercises & events' linking /members/exercises/#&lt;slug>, while the Save box says 'Draft: not on the site'. Documents show '…Documents & forms'. The draft buttons are name="saveasdraft"; core's redirect_post() chooses 10 or 1 only for $_POST['save'] or ['publish'].

**Evidence.**
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-ares-editor/event-unpublish-notice.png`
- ev7.py: -> …&message=4 NOTICE Saved. See it on Exercises & events STATUS Draft

**Triage.** Misleads the editor about what is public. Fix: a redirect_post_location filter maps saveasdraft to message 10 (or rename the buttons). Test: with $_POST['saveasdraft'] set for a draft event, the filtered location has message=10.

**Resolution (fixed).** A redirect_post_location filter gives any event or document that is a draft after the save message 10 ("Draft saved. Drafts never show on the site."). Regression test: `dev/tests/php/qa-022-save-draft-notice-test.php`.

### QA-023

**Documents list: sorting by 'Reviewed' hides every document that has never been reviewed**

| | |
|---|---|
| Kind | bug |
| Severity | medium |
| Category | bug |
| Roles | ares-editor, ares-net, admin |
| Group | plugin-admin |
| Test | php |
| Status | fixed |
| Reported by | admin-custom |
| Files | `plugins/spokares-core/inc/admin-documents.php` |

**Repro.** Save a draft document without a review date; open Documents › Drafts (2) and click the Reviewed column header.

**Expected.** The same items, sorted with 'Not yet' first (the ones most in need of review).

**Actual.** '1 item' under Drafts (2); All (40) shows 39. spokares_document_list_query() sets meta_key=spk_reviewed with orderby=meta_value (an INNER JOIN), and an empty date is deleted as meta on save.

**Evidence.**
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-admin-custom/admin-documents-drafts.png`
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-admin-custom/admin-documents-drafts-sorted-by-reviewed.png`

**Triage.** Items vanish from an admin list. Fix: a named meta_query clause with OR NOT EXISTS. Test: the list query ordered by spk_reviewed includes unreviewed documents.

**Resolution (fixed).** The Reviewed sort uses an OR meta_query ordered by a named NOT EXISTS clause, so never-reviewed documents stay listed and sort as oldest. Regression test: `dev/tests/php/qa-023-reviewed-sort-test.php`.

### QA-024

**Regular meetings: 'Moved to' accepts a past date or another scheduled date, and the meeting then disappears from Home**

| | |
|---|---|
| Kind | bug |
| Severity | medium |
| Category | bug |
| Roles | ares-editor, ares-net |
| Group | plugin-admin |
| Test | php |
| Status | fixed |
| Reported by | ares-editor |
| Files | `plugins/spokares-core/inc/admin-meetings.php` |

**Repro.** On Regular meetings set 'Moved to' for Second Saturday Workshop, Sat, Nov 14 to 2025-01-01 and Save; view Home with ?today=2026-10-20. Also move Third Thursday Nov 19 to 2026-10-15, itself a scheduled (and cancelled) date.

**Expected.** The same guard as event dates: the new date is today or later and not another occurrence; otherwise the row isn't saved, the typed date stays, outlined (§4.4).

**Actual.** 'Saved. See it on Home.' Home then says 'Next: Sat, Dec 12' with no mention of Nov 14; before that it shows 'Next: Thu, Oct 15 (moved from Nov 19)' though Oct 15 is cancelled. Only a date equal to the original, or an unparseable one, is refused, and the unparseable case says '“Moved to” needs a different date'.

**Evidence.**
- mt2.py: == moved to past: [success Saved. See it on Home]
- Home ?today=2026-10-20: 'Second Saturday Workshop … Next: Sat, Dec 12'

**Triage.** Missing validation publishes wrong meeting information. Test: post_form save_meetings with a past moved date; the row isn't saved and an error is set.

**Resolution (fixed).** A new Moved to date before today, or on one of the meeting's own dates, is refused: the row isn't saved, the typing is kept and the row is outlined. An unchanged past move doesn't block the save. Regression test: `dev/tests/php/qa-024-meeting-moved-date-test.php`.

### QA-025

**Net rota: the 'This is a public agency number. Publish it.' tick in the Note column is drawn as a wide bar, not a checkbox**

| | |
|---|---|
| Kind | bug |
| Severity | medium |
| Category | visual |
| Roles | ares-editor, ares-net, admin |
| Group | plugin-admin |
| Test | e2e |
| Status | fixed |
| Reported by | ares-editor |
| Files | `plugins/spokares-core/assets/css/admin.css` |

**Repro.** On Net rota type 'Call 509-555-0101' in a row's Note, Save rota, and look at the confirm box under that Note.

**Expected.** A normal 16×16 checkbox beside the words, as on Regular meetings and the event form.

**Actual.** The checkbox is 145×16px: an empty text-box-like bar, or a solid blue bar when ticked. '.spk-rota-table .spk-note input {width:100%}' (admin.css lines 64 and 128) also hits the checkbox inside .spk-confirm.

**Evidence.**
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-ares-editor/rota-after-save-errors.png`
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-ares-editor/rota-confirm-tick-checked.png`
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-ares-editor/meetings-confirm-tick.png`

**Triage.** A broken control on the path to publishing a confirmed number; editors may not recognise it as a tick. Fix: limit the rule to text inputs. Test (e2e): after a held-back note, the .spk-confirm checkbox is at most 24px wide.

**Resolution (fixed).** admin.css: the Note width rule is limited to input[type="text"], so the confirm tick is a normal checkbox. Regression test: `dev/tests/e2e/qa-025-rota-confirm-checkbox.test.mjs`.

### QA-026

**Net rota: arrowing onto 'Call sign' pulls focus out of the radio group into the text box**

| | |
|---|---|
| Kind | bug |
| Severity | medium |
| Category | accessibility |
| Roles | ares-editor, ares-net, admin |
| Group | plugin-admin |
| Test | e2e |
| Status | fixed |
| Reported by | a11y-responsive |
| Files | `plugins/spokares-core/assets/js/admin-forms.js` |

**Repro.** On Net rota focus the checked 'Open' radio on an Open row and press ArrowUp; on a 'Not posted yet' row press ArrowDown (wraps to Call sign).

**Expected.** Arrow keys move through Call sign / Open / Not posted yet and focus stays in the group (WCAG 3.2.2).

**Actual.** The change handler calls box.focus(), so focus jumps into the call-sign field and the next arrow key moves the caret. Passing 'Call sign' on the way to another option changes the row's state. The Winlink 'Form' select does the same on Windows when arrowing onto 'Other…'.

**Evidence.**
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-a11y-responsive/logs/out-rota.log`
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-a11y-responsive/interact/rota-focus-ares-net-390.png`

**Triage.** Keyboard users can't operate the radio group reliably (blocks use). Fix: move focus only on pointer selection, or not at all. Test (e2e): focus Open, press ArrowUp, activeElement is still a radio.

**Resolution (fixed).** admin-forms.js moves focus to the call-sign box only on a pointer choice; arrow keys stay in the radio group, and the Winlink "Other…" choice no longer steals focus. Regression test: `dev/tests/e2e/qa-026-rota-arrow-keys.test.mjs`.

### QA-027

**Meeting rules, Event and Document edit forms run past the right edge on phones (fieldset min-content), cutting off fields and month boxes**

| | |
|---|---|
| Kind | bug |
| Severity | medium |
| Category | visual |
| Roles | ares-editor, ares-net, admin |
| Group | plugin-admin |
| Test | e2e |
| Status | fixed |
| Reported by | ares-editor-net, a11y-responsive |
| Files | `plugins/spokares-core/assets/css/admin.css` |

**Repro.** Open Meeting rules (as qa-ares-net), an event edit screen and a document edit screen at 390×844.

**Expected.** Cards and fields fit inside 390px with no sideways scroll, as Net details does.

**Actual.** Meeting rules cards (fieldset.spk-card.spk-rule) are 436px, page 446px: the Jun and Dec 'Skip these months' boxes and the input edges are off screen, and 'Needs checking' shows as 'Ne…'. Event edit is 458px (Main link and More links URL inputs, the 400px Extra form select). Document edit '“How to” link' and 'Extra links' fieldsets are 402–414px. &lt;fieldset> defaults to min-inline-size:min-content and the inputs have fixed widths (regular-text is 25em).

**Evidence.**
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-ares-editor-net/phone-390-meeting-rules.png`
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-a11y-responsive/admin-admin/meeting-rules-390.png`
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-a11y-responsive/admin-admin/event-edit-390.png`
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-a11y-responsive/admin-admin/document-edit-390.png`

**Triage.** Merged ares-editor-net (Meeting rules, low) and a11y (all three forms, medium): same root cause. Fix: min-width:0 on the fieldsets and max-width:100% on inputs and selects at 782px and below. Test (e2e): scrollWidth &lt;= clientWidth at 390 on the three screens.

**Resolution (fixed).** admin.css: min-width:0 on the forms' fieldsets, max-width:100% on fields at 782px and below, and stacked link labels, so the forms fit a phone. Regression test: `dev/tests/e2e/qa-027-phone-form-width.test.mjs`.

### QA-028

**Hub tiles and Regular meetings don't reflow on phones, and Net rota overflows between 783 and about 1100px**

| | |
|---|---|
| Kind | bug |
| Severity | medium |
| Category | ux |
| Roles | ares-editor, ares-net, admin |
| Group | plugin-admin |
| Test | e2e |
| Status | fixed |
| Reported by | a11y-responsive |
| Files | `plugins/spokares-core/assets/css/admin.css`<br>`plugins/spokares-core/inc/admin-tiles.php`<br>`plugins/spokares-core/inc/admin-meetings.php` |

**Repro.** Open Hub tiles and Regular meetings at 390×844 (and 720) and Tab to 'Words for slot 1' or a Note field. Open Net rota at 1024×900.

**Expected.** Rows stack as labelled cards and fit the width, as Net rota does at 782px and below; focused fields stay on screen; the rota table fits its content area from 783 to 1100px.

**Actual.** Hub tiles is 994px wide at 390 and 720 (1113 at 1024); Regular meetings is 825px with its Note column off screen. Focused fields sit at x=489 and x=911 on a 390 screen, so the page pans and the slot/date context disappears (also at 720, i.e. 1440 at 200% zoom). Net rota at 1024 is 885px in an 842px area (document 1067px): Note inputs clipped, Winlink inputs truncated. Only .spk-rota-table has a stacked layout.

**Evidence.**
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-a11y-responsive/admin-ares-editor/tiles-390-fullwidth.png`
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-a11y-responsive/admin-ares-editor/meetings-390-fullwidth.png`
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-a11y-responsive/interact/tiles-focus-offscreen-ares-net-390.png`
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-a11y-responsive/admin-ares-net/rota-1024.png`

**Triage.** Merged the a11y reflow report (medium) with the a11y Net rota 1024px report (low): the same missing responsive rules. WCAG 1.4.10 failure on the editors' main screens. Test (e2e): scrollWidth &lt;= clientWidth at 390 for Hub tiles and Regular meetings and at 1024 for Net rota.

**Resolution (fixed).** Hub tiles and Regular meetings stack into labelled cards at 782px and below and use a fixed layout above; the rota uses a fixed layout from 783 to 1299px. Regression test: `dev/tests/e2e/qa-028-admin-table-reflow.test.mjs`.

### QA-029

**The sticky header hides focused links on Shift+Tab and the About 'On this page' target (no scroll padding for the masthead)**

| | |
|---|---|
| Kind | bug |
| Severity | medium |
| Category | accessibility |
| Roles | anonymous, subscriber, contributor, author, core-editor, ares-editor, ares-net, admin |
| Group | theme |
| Test | e2e |
| Status | fixed |
| Reported by | anonymous, a11y-responsive |
| Files | `theme/spokares/assets/css/site.css` |

**Repro.** (a) On /about/ (also /members/documents/, /members/exercises/, /, /how-it-works/) at 390 or 1440, press Shift+Tab repeatedly from the top. (b) At 390 on /about/ scroll about 3000px until the floating 'On this page' button shows, then activate it (reduced motion makes it deterministic).

**Expected.** Focused elements are never fully hidden by the 64/72px sticky masthead (WCAG 2.2 2.4.11), and the opened contents list's focused 'On this page' summary is visible below it.

**Actual.** (a) Chrome scrolls the focused element to the viewport top, under .site-header: on About at 390 ec@spokares.org (y=42), join@spokares.org (y=0), 'Online (GLAARG)', 'Go-kit guide'; Documents 'Reference' chip; Exercises 'ICS-213', '213RR', '214'; Home 'How to get licensed'. (b) toc.js runs det.scrollIntoView({block:'start'}) then summary.focus(); the details lands at top 0–1, fully under the 65px header, and the list looks cut off. The only offset is [id]{scroll-margin-top}, which doesn't apply to focus scrolling or to .toc-mobile (no id); html has no scroll-padding-top.

**Evidence.**
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-a11y-responsive/interact/focus-obscured-back-0-390.png`
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-a11y-responsive/interact/toc-fab-activated-reduce-390.png`
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-anonymous/about-fab-clicked-phone.png`
- widgets2.mjs: phone fab clicked {open:true, focus:'SUMMARY On this page', top:0}

**Triage.** Merged three reports (anonymous FAB, a11y FAB, a11y Shift+Tab) with one root cause. html{scroll-padding-top: calc(var(--mast-h) + var(--spk-bar) + 16px)} in site.css covers focus scrolling and scrollIntoView together, so the fix stays in the theme; toc.js (plugin-front) needs no change unless the test shows otherwise. Test (e2e): Shift+Tab on /about/ at 390 leaves the focused element's top below the header's bottom; activating the FAB leaves the summary visible.

**Resolution (fixed).** site.css: html gets scroll-padding-top of masthead + admin bar + 16px, so focused links and the About "On this page" target land below the sticky header. Regression test: `dev/tests/e2e/qa-029-sticky-header-focus.test.mjs`.

### QA-030

**Home hero intro text falls below 4.5:1 contrast over the photo at phone and tablet widths**

| | |
|---|---|
| Kind | bug |
| Severity | medium |
| Category | accessibility |
| Roles | anonymous, subscriber, contributor, author, core-editor, ares-editor, ares-net, admin |
| Group | theme |
| Test | e2e |
| Status | fixed |
| Reported by | a11y-responsive |
| Files | `theme/spokares/assets/css/site.css` |

**Repro.** Open / at 360, 390 and 768; sample the photo under each line of .hero__lede (#E4E9F2, 21.6px regular) with the text hidden.

**Expected.** At least 4.5:1 everywhere under normal-size text (WCAG 1.4.3).

**Actual.** Worst 5% of the background: 3.04:1 at 360, 2.94:1 at 390, 4.06:1 at 768 (1024 and 1440 pass). The overlay (.wp-block-cover.hero::before, a 90deg gradient .88→.62→.12) leaves the right third almost bare, and on phones the text spans the lighter sky (about rgb(83,130,176)). Editors can replace the photo, so a brighter image makes it worse. The reference design has the same overlay, so not a regression.

**Evidence.**
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-a11y-responsive/anonymous/home-390.png`
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-a11y-responsive/anonymous/home-360.png`
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-a11y-responsive/logs/out-bg.log`

**Triage.** WCAG AA failure on the page's most read text. Fix: a stronger or vertical overlay at 900px and below. Test (e2e): pixel-sample the background under the lede at 390 (as the evaluator's run-bgcontrast.mjs does), or assert the overlay opacity behind the lede meets a threshold.

**Resolution (fixed).** Below 1020px the hero overlay is at least .72 night across the photo, giving the lede 4.5:1 or better over any image; desktop keeps the reference gradient. Regression test: `dev/tests/e2e/qa-030-hero-lede-contrast.test.mjs`.

### QA-031

**A page an administrator adds (e.g. the Privacy Policy) renders with no H1, no content column, and text flush to the window edge**

| | |
|---|---|
| Kind | bug |
| Severity | medium |
| Category | visual |
| Roles | admin, anonymous |
| Group | theme |
| Test | e2e |
| Status | fixed |
| Reported by | admin-core |
| Files | `theme/spokares/templates/page.html`<br>`theme/spokares/templates/page-how-it-works.html (new)`<br>`theme/spokares/templates/page-about.html (new)` |

**Repro.** As admin add and publish a page with a heading and a paragraph (Pages › Add Page, or Settings › Privacy › Create); view it; open it in the block editor.

**Expected.** A plain page gets one H1 page title and the site's readable content column with the 16px phone gutter, and the admin can edit the title in the editor.

**Actual.** templates/page.html is only header, &lt;main> with post-content, and footer, because How it works and About carry their own section wrappers. The new page has no &lt;h1>; the h2 sits at x=0 and the paragraph runs from the window's left edge. In the editor (template-locked for pages) there is no title field ('No title · Page'). The footer promises 'Privacy (coming)', so the first such page will look broken.

**Evidence.**
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-admin-core/front-admin-new-page.png`
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-admin-core/editor-admin-new-page.png`
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-admin-core/privacy-created-editor.png`

**Triage.** Broken output for the supported way an administrator extends the site. Theme-only fix: move today's page.html to slug templates page-how-it-works.html and page-about.html, and make page.html a plain page (post-title H1 plus the .wrap body used by index.html); template-locked mode then shows the title. Keep dev/tests/php/prior-page-template-test.php passing. Test (e2e): a page created over REST by admin has one h1 and a body inside the content column; About and How it works render unchanged.

**Resolution (fixed).** page.html is now a plain page (title as H1 in the column); How it works and About keep the full-bleed shell through new slug templates that no one is offered in the Template picker. Regression tests: `dev/tests/e2e/qa-031-plain-page.test.mjs`, `dev/tests/php/qa-theme-templates-test.php`.

### QA-032

**Appearance › Fonts is offered to administrators, but every font upload or install fails ('not allowed to upload this file type')**

| | |
|---|---|
| Kind | bug |
| Severity | medium |
| Category | bug |
| Roles | admin |
| Group | cross |
| Test | e2e |
| Status | fixed |
| Reported by | admin-core |
| Files | `theme/spokares/theme.json`<br>`plugins/spokares-core/inc/admin-trim.php` |

**Repro.** As admin open Appearance › Fonts › Upload and upload a .woff2 (e.g. theme/spokares/assets/fonts/schibsted-grotesk-roman.woff2). Over REST: POST /wp/v2/font-families (201), then …/font-faces with the file as file-0 (400). Install Fonts behaves the same.

**Expected.** PLAN §4.3 layer 5: no font library, so Appearance › Fonts and the Styles › Typography font management are off (or, if kept, they work).

**Actual.** 400 rest_font_upload_invalid_file_type. The hardening upload_mimes filter (priority 99) replaces the list with jpg/png/webp/pdf and wp_check_filetype_and_ext() re-checks against get_allowed_mime_types(). The Fonts screen (added unconditionally at wp-admin/menu.php:240 in 7.1.2) and the Typography font controls stay visible; theme.json doesn't set settings.typography.fontLibrary to false.

**Evidence.**
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-admin-core/font-library-upload-tab.png`
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-admin-core/se-styles-typography.png`
- probe: famStatus 201, faceStatus 400

**Triage.** A feature PLAN says doesn't exist is offered and always fails. Needs theme.json (settings.typography.fontLibrary false) and removing or redirecting the Fonts submenu (admin-trim.php), so cross-group, handled last. Test (e2e): as admin there is no Appearance › Fonts link and font-library.php redirects or refuses.

**Resolution (fixed).** Appearance › Fonts is removed, font-library.php redirects away, and fontLibraryEnabled is false in the editor, so Styles has no Manage fonts. Regression test: `dev/tests/e2e/qa-032-font-library-off.test.mjs`.

### QA-033

**The 'Next up' card prints the event's list fragment as a sentence and repeats the title as a link (Great ShakeOut card, from Sep 28)**

| | |
|---|---|
| Kind | quality |
| Severity | medium |
| Category | copy |
| Roles | anonymous, subscriber, contributor, author, core-editor, ares-editor, ares-net, admin |
| Group | plugin-front |
| Test | none (not a bug) |
| Status | fixed |
| Reported by | anonymous |
| Files | `plugins/spokares-core/inc/render.php` |

**Repro.** Open /members/exercises/?today=2026-09-28 (live from tomorrow, when the WSDOT exercise ends) and read the second Next-up card; also ?today=2026-10-03 and 2026-10-11, where it is the first card.

**Expected.** A card that reads as a finished sentence, like the Later row 'Great ShakeOut: send a DYFI report by Winlink, marked as an exercise' with DYFI linked inline, and no link that repeats the title.

**Actual.** 'Great ShakeOut / Thu, Oct 15, 10:15 AM / send a DYFI report by Winlink, marked as an exercise' (lowercase fragment, no full stop), then separate lines 'Great ShakeOut ↗' and 'DYFI ↗'. spokares_render_event_card() prints spk_summary ('Short line for lists') unchanged and lists every spk_links entry; only spokares_later_rest() weaves labels in and skips the title link.

**Evidence.**
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-anonymous/seg/exercises-today-2026-09-28-desktop-0.png`
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-anonymous/seg/exercises-today-2026-09-28-phone-0.png`

**Triage.** Renders as the §6.4 next-up markup says (summary, then one line per more-link), so not a contract defect, but it reads badly from tomorrow. Fix in render.php: skip a more-link whose label equals the title and capitalise the card summary (or weave the labels as the Later row does). Rewording the seeded summary is the owner's alternative.

**Resolution (fixed).** The Next up card prints the short line as a sentence with its links woven in, and a More link that repeats the title is labelled with its site instead. Regression test: `dev/tests/php/qa-front-033-next-up-card-test.php`.

### QA-034

**Regular meetings offers Winlink workshop dates, but cancelling or moving one changes nothing on the site**

| | |
|---|---|
| Kind | quality |
| Severity | medium |
| Category | ux |
| Roles | ares-editor, ares-net |
| Group | plugin-admin |
| Test | none (not a bug) |
| Status | fixed |
| Reported by | ares-editor |
| Files | `plugins/spokares-core/inc/admin-meetings.php` |

**Repro.** On Events › Regular meetings tick Cancelled for 'Winlink workshop, Sat, Oct 10', add a note, Save; check Home and /members/.

**Expected.** Either the change shows on Home or the hub, or the screen doesn't offer meetings whose changes appear nowhere, or it says so.

**Actual.** 'Saved. See it on Home', but Home still says 'then a Winlink workshop, 12:30–3:30 PM' with 'Next: Sat, Oct 10', and the hub shows nothing. The winlink-workshop rule is active but 'On Home and the members hub' is off; the screen lists every active meeting while the public views show only Home meetings.

**Evidence.**
- mt1.py: == cancel [success Saved. See it on Home]; HOME and HUB unchanged

**Triage.** The screen misleads. Admin-screen fix: list only meetings shown on the site, or label the others 'Not shown on the site' and drop the 'See it on Home' link for them. Whether the Winlink workshop belongs on Home is a content decision.

**Resolution (fixed).** Regular meetings lists only meetings shown on Home, with a line naming the hidden ones. Regression test: `dev/tests/php/qa-admin-quality-test.php`.

### QA-035

**'Pull this file now' permanently deletes the file with one click and no confirmation**

| | |
|---|---|
| Kind | quality |
| Severity | medium |
| Category | ux |
| Roles | admin |
| Group | plugin-admin |
| Test | none (not a bug) |
| Status | fixed |
| Reported by | admin-custom |
| Files | `plugins/spokares-core/inc/admin-documents.php`<br>`plugins/spokares-core/assets/js/admin-forms.js` |

**Repro.** As admin hover a document with an uploaded file in Documents and click the red 'Pull this file now'.

**Expected.** A confirmation ('Delete this file from the web now? This can't be undone.') before an irreversible delete.

**Actual.** No dialog: the file is deleted (wp_delete_attachment with force) and the published document becomes a 'Soon' draft. admin-forms.js has no confirm for .spk-danger.

**Evidence.**
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-admin-custom/scratch-documents-pull-row.png`
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-admin-custom/scratch-documents-after-pull.png`

**Triage.** PLAN §5.5 defines the action but no confirmation; adding one is a safety improvement, not a defect. Admin-only.

**Resolution (fixed).** "Pull this file now" asks for confirmation ("can't be undone") before it deletes. Regression tests: `dev/tests/php/qa-admin-quality-test.php`, `dev/tests/e2e/qa-admin-quality.test.mjs`.

### QA-036

**Library sections: a new section jumps to the top, deleting one silently hides its documents, and slugs change without warning**

| | |
|---|---|
| Kind | quality |
| Severity | medium |
| Category | ux |
| Roles | admin |
| Group | plugin-admin |
| Test | none (not a bug) |
| Status | fixed |
| Reported by | admin-custom |
| Files | `plugins/spokares-core/inc/data.php`<br>`plugins/spokares-core/inc/admin-documents.php` |

**Repro.** Documents › Sections: add 'Maps & charts', put a published document in it, view /members/documents/; delete the section; check the document and the library; Quick Edit a section slug.

**Expected.** A new section can be placed in the order (the library sorts by term meta spk_order); deleting a section that has documents warns or moves them; changing a slug (a public anchor, e.g. /members/documents/#training used in About) is guarded or warned.

**Actual.** No spk_order field, so the new section gets 0 and shows first in the chips, the groups and the form's radios. After deletion the document stays Published but vanishes from the library (/docs/&lt;slug>/ still redirects), and its Section cell reads '— No tags'. Quick Edit changes slugs freely. Filtering through the Section column link leaves the dropdown on 'All sections'.

**Evidence.**
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-admin-custom/public-documents-new-section-first.png`
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-admin-custom/admin-library-sections.png`

**Triage.** Admin-only management gaps PLAN doesn't specify (§3.4 only lists Sections as an admin screen). Add spk_order on the term screens, set the taxonomy's no_terms label, warn before deleting a non-empty section or editing a slug.

**Resolution (fixed).** Sections get a Position field (new ones go last), a section holding documents can't be deleted (409), and the Edit screen warns that a slug change breaks #links. Regression test: `dev/tests/php/qa-admin-quality-test.php`.

### QA-037

**Admin live previews chatter to screen readers: the Net details preview and the rota 'Shows' cells re-announce on every keystroke**

| | |
|---|---|
| Kind | quality |
| Severity | medium |
| Category | accessibility |
| Roles | ares-editor, ares-net, admin |
| Group | plugin-admin |
| Test | none (not a bug) |
| Status | fixed |
| Reported by | a11y-responsive |
| Files | `plugins/spokares-core/inc/admin-net.php`<br>`plugins/spokares-core/inc/admin-rota.php`<br>`plugins/spokares-core/assets/js/admin-forms.js` |

**Repro.** With a screen reader (or a MutationObserver) type one character in Net details › Repeater call sign; on Net rota type a call sign letter by letter in a 'Not posted yet' row and Tab away.

**Expected.** The preview is available on demand, or only what changed is announced, once.

**Actual.** #spk-net-preview (aria-live=polite) wraps all eight preview lines and draw() rewrites every one per keystroke (8 mutations). Each of the 13 td.spk-shows cells is a polite live region rewritten on every input and again on blur ('KD7', 'KD7ABC', 'KD7ABC').

**Evidence.**
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-a11y-responsive/logs/out-rota.log`

**Triage.** Merged the two a11y reports (medium and low): same pattern in admin-forms.js. Noisy but doesn't block use, so quality. Set text only when it changes, debounce, or keep one summary status region.

**Resolution (fixed).** The Net details preview and rota Shows cells are no longer live regions; one hidden status line announces the rota change after typing pauses. Regression test: `dev/tests/e2e/qa-admin-quality.test.mjs`.

### QA-038

**Settings › ARES site: the groups.io 'Main group' and 'Member files' addresses are not used anywhere**

| | |
|---|---|
| Kind | needs-decision |
| Severity | medium |
| Category | bug |
| Roles | admin |
| Group | plugin-admin |
| Test | php and e2e |
| Status | fixed (owner decision 2026-09-27) |
| Reported by | admin-custom |
| Files | `plugins/spokares-core/inc/admin-site.php`<br>`plugins/spokares-core/inc/helpers.php`<br>`plugins/spokares-core/inc/render.php`<br>`theme/spokares/parts/footer.html`<br>`theme/spokares/parts/members-nav.html`<br>`theme/spokares/patterns/home-join.php` |

**Repro.** Clear or change both groups.io fields and Save; check /, /members/, /how-it-works/, /about/ and /members/documents/.

**Expected.** Links follow the settings (PLAN §3.2: the spk_site groups.io URLs feed 'meetings › home (address); event defaults').

**Actual.** Nothing changes. The address is hard-coded in footer.html, members-nav.html and home-join.php (as §6.7 and §6.9 specify), and render.php checks the literal host 'spokaneares-acs.groups.io' for the default button words. Only the defaults and the sanitiser read groupsio_main and groupsio_files.

**Evidence.**
- grep: groupsio only in inc/admin-site.php, inc/data.php:538-539, inc/helpers.php:188-189

**Triage.** PLAN is inconsistent: §3.2 says the settings drive event defaults, while §6.7/§6.9 hard-code the address in theme parts and a pattern. Owner choice: remove the two fields and say the links live in theme files, or make the parts and patterns read the setting (theme and plugin work). Until then, a one-line help text on the settings screen would stop admins thinking the site changed.

**Resolution (fixed).** Fixed per the owner's decision (2026-09-27): remove the fields. Settings › ARES site holds only the meeting place: the groups.io form section, its save and sanitising, the spk_site defaults and the dev seed's groups.io keys are gone (admin-site.php, data.php, helpers.php, dev/seed/import.php), and an older stored value's groupsio_main and groupsio_files are ignored on read and dropped on the next save. The screen's help says the groups.io links are page text and the theme's footer and members menu. groups.io links in page text are unchanged. Regression tests: `dev/tests/php/qa-038-groupsio-settings-removed-test.php`, `dev/tests/e2e/qa-038-groupsio-settings-removed.test.mjs`.

### QA-039

**Changing the repeater call sign in Net details leaves 'W7GBU' in How it works page text and the message-path diagram**

| | |
|---|---|
| Kind | needs-decision |
| Severity | medium |
| Category | bug |
| Roles | ares-net, admin, anonymous |
| Group | theme |
| Test | php and e2e |
| Status | fixed (owner decision 2026-09-27) |
| Reported by | ares-editor-net |
| Files | `theme/spokares/blocks/message-path/render.php`<br>`theme/spokares/patterns/how-opener.php`<br>`theme/spokares/patterns/how-activation.php`<br>`theme/spokares/patterns/how-messages.php` |

**Repro.** As qa-ares-net change the repeater call sign to K7XYZ and save; open /how-it-works/ signed out.

**Expected.** PLAN §3.4 'no net fact is page text' and the preview promises 'what the site will say': every repeater mention follows Net details, or the screen lists the places to edit by hand.

**Actual.** The settings box, Copy buttons and 'Other nets' say K7XYZ, but five mentions stay W7GBU: hero step 2 'to net control on W7GBU', the message-path SVG label 'W7GBU repeater' and its &lt;desc> (a theme block editors can't change), 'check in with net control on W7GBU', 'Monitor NOAA … and W7GBU', and 'If W7GBU is down…'. The page contradicts itself.

**Evidence.**
- pub.mjs after the save
- theme/spokares/patterns/how-opener.php:40, how-activation.php:51 and :132, how-messages.php:30, blocks/message-path/render.php:28 and :83

**Triage.** PLAN §3.1 #3 lists the net time, frequency, weeks, GMRS time and place as never page text, but not the call sign, which is also the club's own call. Owner/EC decision: make the call dynamic (message-path reads spk_radio; the page-text mentions would need editor edits or a dynamic inline), or treat W7GBU as fixed and make the Net details call field admin-only. Live page text is in the database, so pattern edits alone won't change it.

**Resolution (fixed).** Fixed per the owner's decision (2026-09-27): W7GBU is fixed (the club's call), and the Net details call-sign field is administrators only. A user with only the Net details grant sees the call sign read-only and not posted, with the hint 'The club's own call sign. Only an administrator can change it.' The save handler (admin-net.php) never updates the call for a non-administrator, and refuses a posted different one with 'The repeater call sign wasn't saved: only an administrator can change it.' while the other fields still save. Administrators can still change it. The How it works page text and message-path picture keep W7GBU. The two qa-057 refused-call-sign tests now sign in as an administrator, the only role that can still type a call sign; their assertions are unchanged. Regression tests: `dev/tests/php/qa-039-repeater-call-admin-only-test.php`, `dev/tests/e2e/qa-039-repeater-call-admin-only.test.mjs`.

### QA-040

**Signed-in users without list_users can list accounts over REST, including the administrator's login slug**

| | |
|---|---|
| Kind | bug |
| Severity | low |
| Category | security |
| Roles | subscriber, contributor, author, core-editor, ares-editor, ares-net |
| Group | governance-security |
| Test | php |
| Status | fixed |
| Reported by | subscriber, contributor |
| Files | `mu-plugins/spokares-hardening/enumeration.php` |

**Repro.** Sign in as qa-subscriber or qa-contributor, get a REST nonce (admin-ajax.php?action=rest-nonce), GET /wp-json/wp/v2/users?per_page=100, ?who=authors, and /wp/v2/users/1.

**Expected.** PLAN §5.3, no user enumeration: a user without list_users sees at most their own account (/users/me); other slugs (derived from logins) aren't listed.

**Actual.** 200 with id 1, slug 'admin', name, link /author/admin/ and avatar hashes. who=authors (contributor or ARES Editor) lists admin, qa-author, qa-contributor and qa-core-editor; /users/1 is 200. enumeration.php removes the users routes only when ! is_user_logged_in().

**Evidence.**
- curl as subscriber: /wp/v2/users?per_page=100 -> 200, 1 row: 1 admin admin
- curl as qa-contributor: who=authors -> admin, qa-author, qa-contributor, qa-core-editor

**Triage.** Merged the subscriber and contributor reports. Low because registration is off and sign-in is throttled with 2FA. Fix: for users without list_users limit the collection to themselves (rest_user_query) while keeping what the page editor's author panel needs; check the editor loads for ARES Editors without 403s. Test: as_role('contributor'), GET /wp/v2/users?who=authors doesn't contain the admin's slug.

**Resolution (fixed).** enumeration.php limits /wp/v2/users to the caller's own account for users without list_users and refuses other accounts by ID. One narrow exception answers the editor's own request for a published page author's display name, so page editing has no failed request. Regression tests: `dev/tests/php/qa-040-rest-users-enumeration-test.php`, `dev/tests/e2e/qa-security-editor-surface.test.mjs`.

### QA-041

**Every signed-in page sends a hash of the user's e-mail address to Gravatar**

| | |
|---|---|
| Kind | bug |
| Severity | low |
| Category | security |
| Roles | subscriber, contributor, author, core-editor, ares-editor, ares-net, admin |
| Group | governance-security |
| Test | php |
| Status | fixed |
| Reported by | subscriber |
| Files | `mu-plugins/spokares-hardening/hardening.php` |

**Repro.** Sign in with any role, load any public or wp-admin page, and search the HTML for gravatar.com.

**Expected.** The site avoids third-party requests (PLAN §2 #11 privacy: fonts self-hosted, no Google Fonts) and hides profile pictures (§4.2), so no e-mail-derived hash goes to secure.gravatar.com.

**Actual.** Four secure.gravatar.com/avatar/&lt;sha256-of-email> URLs per page (toolbar at 28 and 64px plus srcset); REST user objects carry avatar_urls; show_avatars is on.

**Evidence.**
- curl as subscriber: gravatar.com count = 4 on every page
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-subscriber/probe/front-adminbar-site-menu.png`

**Triage.** A privacy leak to a third party against the site's stated stance. Fix in the must-use plugin: pre_option_show_avatars returns 0 (or a local default via pre_get_avatar_data). Test: get_avatar_url() for a user contains no gravatar.com and show_avatars is off.

**Resolution (fixed).** Avatars are off (show_avatars 0) and pre_get_avatar_data returns an empty URL, so no page or REST answer points at gravatar.com. Regression test: `dev/tests/php/qa-041-gravatar-test.php`.

### QA-042

**Any edit_posts user can create post tags and pattern categories over REST; the core Editor can also manage categories and tags**

| | |
|---|---|
| Kind | bug |
| Severity | low |
| Category | security |
| Roles | contributor, author, core-editor, ares-editor, ares-net |
| Group | governance-security |
| Test | php |
| Status | fixed |
| Reported by | contributor, core-editor |
| Files | `mu-plugins/spokares-hardening/content-surface.php`<br>`plugins/spokares-core/inc/roles.php` |

**Repro.** As qa-contributor (or editor, the ARES Editor) with a REST nonce POST /wp-json/wp/v2/tags {name}, /wp/v2/wp_pattern_category {name}, and the same through /batch/v1. As qa-core-editor POST /wp/v2/categories and open edit-tags.php?taxonomy=category and ?taxonomy=post_tag.

**Expected.** 403 rest_cannot_create. PLAN §4.1: editors lack manage_categories and posts are unused, so only administrators add terms; the core Editor's Categories and Tags screens are refused, as they are for ARES Editors (403).

**Actual.** 201 every time (contributor tag, pattern category, batch tag; ARES Editor tag); the core Editor created a category and gets add/edit/delete on Categories and Tags (200). The terms persist and only an admin can remove them; nothing is public (tag archives 404). Core's terms controller accepts assign_terms for non-hierarchical taxonomies (post_tag assign_terms maps to edit_posts; wp_pattern_category's is edit_posts), and the core Editor has manage_categories.

**Evidence.**
- curl as qa-contributor: POST /wp/v2/tags -> 201
- POST /wp/v2/categories -> 403 (control)
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-core-editor/categories.png`

**Triage.** Merged the contributor report with the terms half of the core-editor patterns report. Database clutter only, but it breaks the role model. Fix: register_taxonomy_args for category, post_tag and wp_pattern_category maps manage/edit/delete/assign_terms to manage_options (check the page editor doesn't need them). Test: as contributor and ares-editor, POST /wp/v2/tags and /wp/v2/wp_pattern_category give rest_cannot_create 403; as core-editor POST /wp/v2/categories is refused.

**Resolution (fixed).** Term management maps to manage_options for categories, tags and pattern categories, and assigning tags and pattern categories does too; category assignment stays as core has it so the editor gets no 403. Regression tests: `dev/tests/php/qa-042-term-creation-test.php`, `dev/tests/e2e/qa-security-editor-surface.test.mjs`.

### QA-043

**The remote Pattern Directory is still reachable over REST (the server fetches wordpress.org patterns on request)**

| | |
|---|---|
| Kind | bug |
| Severity | low |
| Category | security |
| Roles | contributor, author, core-editor, ares-editor, ares-net |
| Group | governance-security |
| Test | php |
| Status | fixed |
| Reported by | contributor |
| Files | `mu-plugins/spokares-hardening/enumeration.php` |

**Repro.** As qa-contributor or editor, GET /wp-json/wp/v2/pattern-directory/patterns?per_page=1 with a REST nonce.

**Expected.** PLAN §4.3 layer 5: no remote patterns for anyone.

**Actual.** 200 with a wordpress.org pattern ('Mountain Intro Cards', id 1372808) fetched from api.wordpress.org. should_load_remote_block_patterns false (theme setup.php:80) stops the pattern registry, not this route.

**Evidence.**
- curl as qa-contributor: GET …/pattern-directory/patterns?per_page=1 -> 200 [{id:1372808,…}]

**Triage.** Contradicts §4.3; any edit_posts account can make the server call api.wordpress.org. Fix: unset the route in a rest_endpoints filter next to the users filter. Test: GET /wp/v2/pattern-directory/patterns is rest_no_route 404.

**Resolution (fixed).** The /wp/v2/pattern-directory routes are removed for everyone, so the server makes no call to wordpress.org. Regression tests: `dev/tests/php/qa-043-remote-pattern-directory-test.php`, `dev/tests/e2e/qa-security-editor-surface.test.mjs`.

### QA-044

**The file-deletion guard doesn't count trashed documents, so a restored document can point at a deleted file**

| | |
|---|---|
| Kind | bug |
| Severity | low |
| Category | bug |
| Roles | core-editor, admin |
| Group | governance-security |
| Test | php |
| Status | fixed |
| Reported by | core-editor |
| Files | `plugins/spokares-core/inc/files.php` |

**Repro.** As admin publish a document with an uploaded PDF and trash it without 'Also remove its file'. As core Editor (or admin) delete the attachment (REST DELETE /wp/v2/media/&lt;id>?force=true). Restore the document.

**Expected.** While a document that uses the file can still be restored, the file stays protected (files.php: only Replace, Trash-with-remove and Pull delete a used file).

**Actual.** Refused while the document was published; deleted once it was trashed; the restored draft's spk_file points at a deleted attachment (wp_get_attachment_url false). spokares_attachment_uses() queries post_status 'any', which excludes 'trash'.

**Evidence.**
- probe: live-delete=refused trashed-delete=deleted file_exists=false spk_file=70 url=false

**Triage.** Data-integrity gap in the deletion guard. Fix: include 'trash' in the statuses. Test: trash the document, wp_delete_attachment() is refused and the file still exists.

**Resolution (fixed).** spokares_attachment_uses() counts trashed documents, so a restorable document still protects its file. Regression test: `dev/tests/php/qa-044-trashed-doc-file-guard-test.php`.

### QA-045

**Paginated URLs of the single pages return 200 duplicates, and /page/2/ has a canonical pointing at a 404**

| | |
|---|---|
| Kind | bug |
| Severity | low |
| Category | bug |
| Roles | anonymous |
| Group | governance-security |
| Test | e2e |
| Status | fixed |
| Reported by | anonymous |
| Files | `mu-plugins/spokares-hardening/content-surface.php` |

**Repro.** curl /about/page/2/, /members/page/9/, /members/exercises/page/3/, /page/999/ and /?paged=2; read &lt;title> and rel=canonical on /page/2/.

**Expected.** 404: none of these pages paginate.

**Actual.** All 200 with the full page and titles like 'About ARES & ACS | Page 2 | …'. /page/2/ (also reached from /?paged=2 by a 301) prints &lt;link rel=canonical href=/2/>, which is 404.

**Evidence.**
- curl: /about/page/2/ 200, /page/999/ 200, /?paged=2 301 -> /page/2/?paged=2
- `/page/2/ canonical http://127.0.0.1:9611/2/ -> 404`

**Triage.** Duplicate content and a broken canonical. Fix in content-surface.php: 404 a singular page or the front page with page/paged > 1. Test (e2e): those URLs return 404.

**Resolution (fixed).** A singular or front-page request with paged > 1 (or page > 1 without <!--nextpage-->) is a 404 with no canonical and no "Page 2" title. Regression test: `dev/tests/e2e/qa-045-paged-singular-404.test.mjs`.

### QA-046

**Block editor page card offers 'Order' to editors; it says 'Order updated.' but the server discards it**

| | |
|---|---|
| Kind | bug |
| Severity | low |
| Category | ux |
| Roles | ares-editor, ares-net, core-editor |
| Group | governance-security |
| Test | e2e |
| Status | fixed |
| Reported by | core-editor |
| Files | `plugins/spokares-core/assets/js/editor-guard.js` |

**Repro.** As editor open About in the block editor, Page sidebar ⋮ › Order, type 7, Save; read menu_order over REST.

**Expected.** PLAN §4.3: page order is fixed for non-admins, so Order isn't offered (build-notes/plugin.md says the Status, Order and Trash rows are hidden).

**Actual.** A snackbar says 'Order updated.', but spokares_page_insert_guard keeps menu_order=2. removeEditorPanel('post-status'/'page-attributes') doesn't reach the 7.1.2 post-card actions menu (View, Rename, Order; plus Trash for the core Editor, see QA-001).

**Evidence.**
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-core-editor/editor-ares-editor-order-modal.png`
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-core-editor/editor-ares-editor-order-after.png`

**Triage.** A false success message. Fix: unregister the 'reorder-page' entity action for non-admins (Rename edits the title, which is page text, and may stay). The stale build-notes line is QA-111. Test (e2e): as ares-editor the page card menu has no 'Order'.

**Resolution (fixed).** The page type sent to layout-locked users drops page-attributes support, so the page card offers no Order; the server already kept menu_order. Regression tests: `dev/tests/e2e/qa-046-page-card-order.test.mjs`, `dev/tests/e2e/qa-security-editor-surface.test.mjs`.

### QA-047

**Admin-bar Search is never removed for non-admins: the removal runs before core adds the node**

| | |
|---|---|
| Kind | bug |
| Severity | low |
| Category | bug |
| Roles | subscriber, contributor, author, core-editor, ares-editor, ares-net |
| Group | plugin-admin |
| Test | e2e |
| Status | fixed |
| Reported by | subscriber, contributor, author |
| Files | `plugins/spokares-core/inc/admin-bar.php` |

**Repro.** Sign in as any non-admin (?dev_login=qa-subscriber, qa-contributor, qa-author or editor) and open /members/; look at the right of the toolbar or for li#wp-admin-bar-search.

**Expected.** spokares_admin_bar() removes 'search' (admin-bar.php:22) for users without manage_options, as in PLAN §4.2's trimmed toolbar.

**Actual.** The magnifier and search form are on every front-end page. Core adds the node at admin_bar_menu priority 9999 (class-wp-admin-bar.php:652) while spokares_admin_bar runs at 999; my-account-item (9991) and recovery-mode (9992) are also added later. It leads to the weak search page (QA-098).

**Evidence.**
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-subscriber/probe/front-adminbar-search.png`
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-contributor/front-members-desktop.png`
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-author/front-home-adminbar-crop.png`

**Triage.** Merged the subscriber, contributor and author reports. Fix: remove the node later (PHP_INT_MAX, or wp_before_admin_bar_render). A PHP test can't see it (wp_admin_bar_search_menu returns early when is_admin()), so e2e: as contributor and ares-editor, /members/ has 0 #wp-admin-bar-search.

**Resolution (fixed).** The admin-bar trim runs at PHP_INT_MAX, after core adds Search at 9999, so non-admins have no Search box. Regression test: `dev/tests/e2e/qa-047-admin-bar-search.test.mjs`.

### QA-048

**Own profile via user-edit.php?user_id=&lt;self> skips the profile trim, and the hidden Website and Biography fields are still saved**

| | |
|---|---|
| Kind | bug |
| Severity | low |
| Category | security |
| Roles | subscriber, contributor, author, core-editor, ares-editor, ares-net |
| Group | plugin-admin |
| Test | php |
| Status | fixed |
| Reported by | subscriber, ares-editor |
| Files | `plugins/spokares-core/inc/admin-trim.php`<br>`plugins/spokares-core/assets/css/admin.css` |

**Repro.** Sign in as qa-subscriber (ID 3), open /wp-admin/profile.php, then /wp-admin/user-edit.php?user_id=3. Also POST profile.php with url and description as an ARES Editor.

**Expected.** PLAN §4.2: whichever URL shows your own profile, only name, e-mail, password and Two-Factor show, and the hidden fields aren't accepted.

**Actual.** user-edit.php renders the same own-profile form with body class user-edit-php, and every trim rule is keyed to .spk-editor.profile-php, so Toolbar, Website, Biographical Info, Profile Picture (Gravatar) and 'About Yourself' show; the toolbar gains 'View User' → /author/&lt;slug>/ (404). Hiding is CSS-only, so a crafted POST stores user_url and description (verified: 'https://evil.example', 'Call me 509-555-1234').

**Evidence.**
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-subscriber/probe/subscriber-user-edit-self.png`
- pf1.py: url and description stored

**Triage.** Merged the subscriber user-edit report with the ares-editor CSS-only finding. A stored bio can carry a phone number the never-publish rules would refuse (author archives 404, so not public). Fix: send user-edit.php for yourself to profile.php for non-admins and drop user_url/description on personal_options_update for non-admins. Test: as ares-editor, a profile update with url and description leaves both empty.

**Resolution (fixed).** A non-admin opening their own user-edit.php is sent to profile.php, and their Website and Biography are never saved from a self-update. Regression test: `dev/tests/php/qa-048-own-profile-trim-test.php`.

### QA-049

**Profile trim is incomplete: an empty 'Personal Options' heading, or one Media Library 'Infinite Scrolling' row, for non-admins**

| | |
|---|---|
| Kind | bug |
| Severity | low |
| Category | visual |
| Roles | subscriber, contributor, author, core-editor, ares-editor, ares-net |
| Group | plugin-admin |
| Test | e2e |
| Status | fixed |
| Reported by | subscriber, contributor, author, core-editor, ares-editor |
| Files | `plugins/spokares-core/assets/css/admin.css` |

**Repro.** Open /wp-admin/profile.php as qa-subscriber or qa-contributor, and as qa-author, qa-core-editor or editor.

**Expected.** PLAN §4.2: only name, e-mail, password and Two-Factor options; no heading with nothing under it.

**Actual.** Subscriber and contributor: 'Personal Options' sits directly above 'Name' with an empty table (its only row, Toolbar, is display:none). Author, core Editor and ARES Editors: the section holds 'Infinite Scrolling: Disable infinite scrolling in the Media Library grid view', though Media is hidden and upload.php redirects them. admin.css (lines 108–118) hides the other rows but not tr.user-infinite-scrolling-wrap or the heading.

**Evidence.**
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/baseline/subscriber/profile-desktop.png`
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-contributor/profile-top-crop.png`
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/baseline/author/profile-desktop.png`
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/baseline/ares-editor/profile-desktop.png`

**Triage.** Merged five reports (subscriber, contributor, author, core-editor, ares-editor). Cosmetic, but a §4.2 non-conformance. Fix: hide .user-infinite-scrolling-wrap and the Personal Options h2 (e.g. h2:has(+ table .user-comment-shortcuts-wrap)). Test (e2e): as subscriber and ares-editor, profile.php shows neither 'Personal Options' nor 'Infinite Scrolling'.

**Resolution (fixed).** admin.css hides the Infinite Scrolling row and the empty Personal Options heading for non-admins. Regression test: `dev/tests/e2e/qa-049-profile-trim.test.mjs`.

### QA-050

**The Dashboard 'Welcome to WordPress!' panel still shows: the remove_action runs before core adds it**

| | |
|---|---|
| Kind | bug |
| Severity | low |
| Category | bug |
| Roles | admin |
| Group | plugin-admin |
| Test | php |
| Status | fixed |
| Reported by | admin-custom, admin-core |
| Files | `plugins/spokares-core/inc/admin-trim.php` |

**Repro.** Sign in as admin and open /wp-admin/.

**Expected.** admin-trim.php says 'Welcome, Quick Draft and News off for all' (PLAN §3.4), and the Dashboard shouldn't steer admins to 'Open site editor' or 'Edit styles' (§5.7).

**Actual.** #welcome-panel 'Welcome to WordPress!' with 'Add a new page', 'Open site editor' and 'Edit styles'. admin-trim.php:66 calls remove_action('welcome_panel', 'wp_welcome_panel') when the file loads; core adds the action later, in wp-admin/includes/admin-filters.php.

**Evidence.**
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/baseline/admin/dashboard-desktop.png`
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/baseline/admin/admin-welcome-0-desktop.png`

**Triage.** Merged the admin-custom and admin-core reports. Fix: remove it on admin_init or load-index.php. Test: after the admin hooks run, has_action('welcome_panel', 'wp_welcome_panel') is false.

**Resolution (fixed).** The welcome_panel removal runs on admin_init and load-index.php, after core adds it. Regression test: `dev/tests/php/qa-050-welcome-panel-test.php`.

### QA-051

**Event and document text fields lose their backslashes on save (double unslash)**

| | |
|---|---|
| Kind | bug |
| Severity | low |
| Category | bug |
| Roles | ares-editor, ares-net, admin |
| Group | plugin-admin |
| Test | php |
| Status | fixed |
| Reported by | ares-editor |
| Files | `plugins/spokares-core/inc/admin-events.php`<br>`plugins/spokares-core/inc/admin-documents.php` |

**Repro.** Add an event with the Short line 'Save to C:\ARES\forms' and the task 'Open C:\Winlink\Messages' and Publish; give a document the note 'Save in C:\ARES'.

**Expected.** Stored and shown as typed (the rota note and Winlink task keep backslashes).

**Actual.** Stored as 'Save to C:ARESforms', 'Open C:WinlinkMessages' and 'Save in C:ARES' and shown that way on the site. spokares_event_submitted() and spokares_document_submitted() already wp_unslash($_POST), and update_post_meta() unslashes again; Duplicate copies meta through update_post_meta too.

**Evidence.**
- ev3.py: spk_summary 'Save to C:ARESforms', spk_tasks 'Open C:WinlinkMessages'
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-ares-editor/docs-reference-after.png`

**Triage.** Data corruption. Fix: wp_slash() values passed to update_post_meta (save and Duplicate). Test: post the form with backslashes; the meta equals the typed text.

**Resolution (fixed).** Event and document saves, and Duplicate, pass wp_slash()ed values, so backslashes survive. Regression test: `dev/tests/php/qa-051-backslash-fields-test.php`.

### QA-052

**An event end time with no start time is refused, but the form comes back with All day ticked and the time boxes hidden**

| | |
|---|---|
| Kind | bug |
| Severity | low |
| Category | ux |
| Roles | ares-editor, ares-net |
| Group | plugin-admin |
| Test | php |
| Status | fixed |
| Reported by | ares-editor |
| Files | `plugins/spokares-core/inc/admin-events.php` |

**Repro.** Add event (Training, a date), untick All day, fill only End (18:00), Publish; look at the form; Publish again.

**Expected.** The time boxes are visible with the typed end time outlined, or the message says the start time is missing.

**Actual.** Saved as a draft with 'The end time must be after the start time.'; All day comes back ticked ($all_day = '' === t_start) and Start/End are hidden. Publishing again silently clears the end time and publishes an all-day event.

**Evidence.**
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-ares-editor/event-draft-end-only.png`

**Triage.** The error points at hidden fields and the retry silently drops input (§4.4: never throw away what was typed). Test: render the event form with a retained end time only; All day is unticked, and the message names the missing start.

**Resolution (fixed).** An end time with no start is reported as a missing start time; All day is ticked only when both times are empty, so the form keeps the typed end. Regression test: `dev/tests/php/qa-052-end-time-no-start-test.php`.

### QA-053

**After saving an event or document whose name contains '&', the name box shows '&amp;'**

| | |
|---|---|
| Kind | bug |
| Severity | low |
| Category | ux |
| Roles | ares-editor, ares-net, admin |
| Group | plugin-admin |
| Test | php |
| Status | fixed |
| Reported by | ares-editor |
| Files | `plugins/spokares-core/inc/admin-events.php`<br>`plugins/spokares-core/inc/admin-documents.php` |

**Repro.** Add an event named 'QA Q&A "night" & Winlink' (or a document 'QA Tips & Tricks') and Publish; read the name box.

**Expected.** The box shows the name as typed; the site already shows it correctly.

**Actual.** The box reads 'QA Q&amp;A "night" &amp; Winlink'. Titles are kses-filtered to &amp; under DISALLOW_UNFILTERED_HTML and core's title field escapes the stored value again. It doesn't compound on re-save.

**Evidence.**
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-ares-editor/event-title-amp-1.png`

**Triage.** Looks like corruption to a volunteer. Fix: store or show these two post types' titles as plain text (decode for the edit field). Test: after saving with '&', the value the edit form prints decodes to '&', not '&amp;'.

**Resolution (fixed).** An edit_post_title filter decodes entities for events and documents before core escapes the name box; stored titles stay kses-filtered. Regression test: `dev/tests/php/qa-053-ampersand-name-box-test.php`.

### QA-054

**Save handlers don't enforce the forms' length limits or reject invalid times; bad values save under 'Saved.'**

| | |
|---|---|
| Kind | bug |
| Severity | low |
| Category | bug |
| Roles | ares-editor, ares-net, admin |
| Group | plugin-admin |
| Test | php |
| Status | fixed |
| Reported by | ares-editor, ares-editor-net |
| Files | `plugins/spokares-core/inc/admin-rota.php`<br>`plugins/spokares-core/inc/admin-events.php`<br>`plugins/spokares-core/inc/admin-documents.php`<br>`plugins/spokares-core/inc/admin-net.php`<br>`plugins/spokares-core/inc/admin-meetings.php` |

**Repro.** Post the forms without the browser's maxlength/type checks: a 327-character rota note, a 400-character event Short line and 300-character Where, a 200-character document note, a 660-character Net details open-slot line, a 270-character meeting name; Net details nets[net_time]=25:00, '' and gmrs_time=7pm.

**Expected.** PLAN §4.4: a field that isn't saved keeps its stored value, is outlined, and gets a sentence; lengths are capped on the server as well as by maxlength (and '8 words or fewer' is enforced or warned).

**Actual.** All long values are stored in full (the open-slot line prints 659 characters on /members/). Invalid times are dropped silently under a green 'Saved.' with no outline. A 9-word document note is accepted. Only the version is trimmed (to 30).

**Evidence.**
- nd.mjs {nets[net_time]:25:00} -> 'Saved.'
- open-slot length on /members/: 659
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-ares-editor/members-long-note.png`

**Triage.** Merged the ares-editor and ares-editor-net validation reports (the layout half is QA-004). Only crafted requests or non-HTML5 clients reach it. Fix: server-side caps that match maxlength and hold back invalid times, with the §4.4 sentence. Test: post_form with an over-long field and 25:00; each is held back with an error and the stored value is unchanged.

**Resolution (fixed).** Every save handler enforces its field's maxlength and rejects invalid times, holding the field back (or drafting the item) with an error instead of "Saved." Regression test: `dev/tests/php/qa-054-length-limits-times-test.php`.

### QA-055

**Net rota: at 52 Tuesdays the link still says 'Show 13 more' and reloads the same page**

| | |
|---|---|
| Kind | bug |
| Severity | low |
| Category | ux |
| Roles | ares-editor, ares-net |
| Group | plugin-admin |
| Test | php |
| Status | fixed |
| Reported by | ares-editor |
| Files | `plugins/spokares-core/inc/admin-rota.php` |

**Repro.** Click 'Show 13 more' until 52 Tuesdays show, then click it again.

**Expected.** No 'Show 13 more' at the 52-week cap, and only 'Show 13 more' is the link (not the whole 'Showing N Tuesdays…' sentence).

**Actual.** The link text reads 'Showing 52 Tuesdays from this week. Show 13 more' and goes to weeks=52, the same page.

**Evidence.**
- rota4.py: more link at 52 [('…page=spokares-rota&weeks=52', 'Showing 52 Tuesdays from this week. Show 13 more')]

**Triage.** A dead link (admin-rota.php lines 184–191). Test: render the rota screen with weeks=52; the output has no 'Show 13 more' link.

**Resolution (fixed).** Only "Show 13 more" is a link, and only below 52 weeks; at the cap the screen says it is as far ahead as the rota goes. Regression test: `dev/tests/php/qa-055-rota-more-cap-test.php`.

### QA-056

**A confirmed Winlink sentence that contains a line break is flagged again on every later save**

| | |
|---|---|
| Kind | bug |
| Severity | low |
| Category | bug |
| Roles | ares-net, admin |
| Group | plugin-admin |
| Test | php |
| Status | fixed |
| Reported by | ares-editor-net |
| Files | `plugins/spokares-core/inc/admin-net.php` |

**Repro.** In Net details › 'How to answer a Winlink assignment' type two lines with a phone number on the second; Save (held back with the tick); tick it and Save ('Saved.'); change only the Tone and Save.

**Expected.** Once confirmed, unchanged text isn't asked about again, as with a single-line sentence.

**Actual.** Every later save gives 'The Winlink sentence wasn’t saved because it mentions a phone number.' The handler hashes the multi-line text, then collapses whitespace before saving; the form re-submits the collapsed text, whose hash never matches.

**Evidence.**
- confirm.mjs line-break case: steps 3 and 4 'error: The Winlink sentence wasn’t saved…'

**Triage.** Breaks the §4.4 confirm rule for multi-line text. Fix: hash the normalised text. Test: two post_form saves; the second has no error.

**Resolution (fixed).** Whitespace in the Winlink sentence is collapsed before the confirm check and hash, so a confirmed multi-line sentence stays confirmed. Regression test: `dev/tests/php/qa-056-multiline-winlink-confirm-test.php`.

### QA-057

**The Net details preview doesn't match the site: ', ,' with no GMRS time, a refused call sign shown, the alternate line's wording, and empty bullets**

| | |
|---|---|
| Kind | bug |
| Severity | low |
| Category | bug |
| Roles | ares-net, admin |
| Group | plugin-admin |
| Test | php |
| Status | fixed |
| Reported by | ares-editor-net |
| Files | `plugins/spokares-core/inc/admin-net.php`<br>`plugins/spokares-core/assets/js/admin-forms.js` |

**Repro.** In Net details clear the GMRS time with the 3rd week ticked; type 'Frank' as the call sign; compare the alternate preview line with /how-it-works/; untick every Winlink, Simplex and GMRS week and save.

**Expected.** PLAN §3.4: the preview prints what the site will say.

**Actual.** PHP and JS previews print 'ACS GMRS net: 3rd Tuesdays, , for county volunteers…' (site: '3rd Tuesdays, for…'). 'FRANK' shows in every line though the save refuses it and keeps W7GBU. The alternate shows the Copy text ('Alternate repeater 146.880 MHz, -600 kHz offset, 123 Hz tone') while the page shows 'Alternate | 146.880 MHz, −600 kHz, 123 Hz tone'. On load the server preview shows three empty bullets under 'Other nets' until an input event.

**Evidence.**
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-ares-editor-net/net-details-live-preview-edited.png`
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-ares-editor-net/net-details-after-save.png`
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-ares-editor-net/net-details-preview-no-weeks.png`

**Triage.** Merged two ares-editor-net reports: the preview formats separately from render.php. Fix: preview lines reuse the public formatters and admin-forms.js mirrors them; flag an invalid call as the rota's Shows column does. Test: spokares_net_preview_lines() with an empty GMRS time has no ', ,' and matches the public render.

**Resolution (fixed).** The Net details preview uses the site's own formatters and week precedence; the JS preview shows the stored value where the typed one would be refused. Regression tests: `dev/tests/php/qa-057-net-preview-test.php`, `dev/tests/e2e/qa-057-net-preview.test.mjs`. Since QA-039 only an administrator can type a call sign, so the refused-call-sign tests sign in as one (assertions unchanged).

### QA-058

**Meeting rules accepts an End time with no Start, and the meeting's time then disappears from Home and the hub**

| | |
|---|---|
| Kind | bug |
| Severity | low |
| Category | bug |
| Roles | ares-net, admin |
| Group | plugin-admin |
| Test | php |
| Status | fixed |
| Reported by | ares-editor-net |
| Files | `plugins/spokares-core/inc/admin-meetings.php` |

**Repro.** In Meeting rules clear Start for Second Saturday Workshop, keep End 11:00, Save.

**Expected.** PLAN §4.4 'end time after start time': held back with a sentence, like end-before-start.

**Actual.** 'Saved.' Home reads 'Second Saturday Workshop, then a Winlink workshop, 12:30–3:30 PM' and the hub 'Next meeting: Sat, Oct 10, Second Saturday Workshop'; the time is lost without a warning.

**Evidence.**
- mr.mjs {rules[0][start]:'', rules[0][end]:'11:00'} -> 'Saved. See it on Home'

**Triage.** Validation gap. Test: post_form save_meeting_rules with an empty start and an end holds the rule back.

**Resolution (fixed).** Meeting rules refuses an end time with no start (and times that aren't times), keeping the rule and the typing and outlining the card. Regression test: `dev/tests/php/qa-058-meeting-end-no-start-test.php`.

### QA-059

**Meeting rules and Net details silently overwrite someone else's newer save**

| | |
|---|---|
| Kind | bug |
| Severity | low |
| Category | bug |
| Roles | ares-net, admin |
| Group | plugin-admin |
| Test | php |
| Status | fixed |
| Reported by | ares-editor-net |
| Files | `plugins/spokares-core/inc/admin-meetings.php`<br>`plugins/spokares-core/inc/admin-net.php` |

**Repro.** Two users open Meeting rules. A changes the Workshop to 10:00–13:00 and saves; B, whose screen was opened earlier, changes only the Third Thursday time words and saves. Same on Net details (A changes the Tone; B the open-slot line).

**Expected.** The second save is refused or held for anything changed since that screen was opened, as Net rota and Regular meetings already do ('changed by someone else while you were editing').

**Actual.** Both get 'Saved.' and A's change is reverted without warning. Each form posts every value, and the handlers keep no originals or version hash.

**Evidence.**
- stale.mjs meeting rules: A's 10:00 AM–1:00 PM lost
- stale.mjs net details: A's 127.3 Hz lost

**Triage.** Lost updates, rare (few people hold the grant). PLAN §8.2 #18 required this for the rota; extending it here. Fix: carry original values and save only changed fields, refusing stale ones. Test: a save with stale originals keeps the other user's stored change.

**Resolution (fixed).** Net details and Meeting rules post what the form showed; only fields this editor changed are saved, and a field both people changed is held back with a "changed by someone else" error. Regression test: `dev/tests/php/qa-059-stale-settings-save-test.php`.

### QA-060

**Events list 'When' column invents a day and weekday for month- or year-precision past events**

| | |
|---|---|
| Kind | bug |
| Severity | low |
| Category | bug |
| Roles | ares-editor, ares-net, admin |
| Group | plugin-admin |
| Test | php |
| Status | fixed |
| Reported by | admin-custom |
| Files | `plugins/spokares-core/inc/admin-events.php` |

**Repro.** Open Events › Past.

**Expected.** '2025' for Field Day 2025 (precision year) and 'Oct 2022' for the Rockford exercise (precision month), as the public Past list shows.

**Actual.** 'Wed, Jan 1, 2025', 'Sat, Oct 1, 2022', 'Sun, Dec 1, 2019' and so on. The column formats spk_start with 'row' and ignores spk_precision; spokares_fmt_when 'past' already handles precision.

**Evidence.**
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-admin-custom/admin-events-past.png`

**Triage.** Shows a wrong date. Fix in spokares_event_column('spk_when'). Test: the column for a year-precision event prints '2025'.

**Resolution (fixed).** The Events list When column uses the public "past" format for month- and year-precision events ("2025", "Oct 2022"). Regression test: `dev/tests/php/qa-060-events-when-precision-test.php`.

### QA-061

**The hub's 'Next meeting' line uses the recurring 'evenings' wording for a single date**

| | |
|---|---|
| Kind | bug |
| Severity | low |
| Category | copy |
| Roles | anonymous, subscriber, contributor, author, core-editor, ares-editor, ares-net, admin |
| Group | plugin-front |
| Test | php |
| Status | fixed |
| Reported by | anonymous |
| Files | `plugins/spokares-core/inc/schedule.php` |

**Repro.** Open /members/?today=2026-10-11 (or 2027-03-15), when the Third Thursday meeting is next.

**Expected.** 'Thu, Oct 15, evening, Third Thursday training meeting' (the reference site.js prints 'Evening' for a dated item).

**Actual.** 'Next meeting: Thu, Oct 15, evenings, Third Thursday training meeting'. spokares_meeting_hub_line() reuses Home's recurring time words.

**Evidence.**
- dates.mjs 2026-10-11 members
- `/Users/sean/Projects/spokane_ares/design/round3/option-b-lines-down/assets/site.js line 159`

**Triage.** Differs from the wording reference (PLAN §6.0 rule 2). Test: the hub line for a time-words meeting uses the singular.

**Resolution (fixed).** spokares_time_words_once() turns "evenings" into "evening" for a single date on the hub; Home keeps the recurring wording. Regression test: `dev/tests/php/qa-061-hub-meeting-singular-test.php`.

### QA-062

**'Later this season' rows print ':.' or '..' when the short line already ends in punctuation and the event has a More link**

| | |
|---|---|
| Kind | bug |
| Severity | low |
| Category | copy |
| Roles | anonymous, subscriber, contributor, author, core-editor, ares-editor, ares-net, admin |
| Group | plugin-front |
| Test | php |
| Status | fixed |
| Reported by | ares-editor |
| Files | `plugins/spokares-core/inc/render.php` |

**Repro.** Add an exercise outside the next two, with the Short line 'County-wide drill. The EC asks you to:' (the seeded SET pattern) and a More link 'Forms'; view /members/exercises/.

**Expected.** '…The EC asks you to: Forms', or the lead-in dropped; no doubled punctuation.

**Actual.** 'QA Fall Drill: County-wide drill. The EC asks you to:. Forms ↗'. spokares_later_rest() always joins extra links with '. ', so a short line ending in '.' gives '..'.

**Evidence.**
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-ares-editor/exercises-after-trash-long.png`

**Triage.** Wrong punctuation in public output. Test: spokares_later_rest() with a summary ending ':' or '.' has no ':.' or '..'.

**Resolution (fixed).** Leftover More links are joined with a space when the short line already ends in punctuation, otherwise with ". ". Regression test: `dev/tests/php/qa-062-later-rest-punctuation-test.php`.

### QA-063

**'Later this season' silently drops upcoming events beyond 12**

| | |
|---|---|
| Kind | bug |
| Severity | low |
| Category | ux |
| Roles | anonymous, subscriber, contributor, author, core-editor, ares-editor, ares-net, admin |
| Group | plugin-front |
| Test | php |
| Status | fixed |
| Reported by | ares-editor |
| Files | `plugins/spokares-core/inc/schedule.php`<br>`plugins/spokares-core/inc/render.php` |

**Repro.** Publish more than 12 upcoming exercise, training or on-air events outside Next up; view /members/exercises/ and use 'View on site' in All events for the later ones.

**Expected.** PLAN §2.3 #14: 'An event must never vanish from Exercises because of where its date falls'. Every upcoming event is reachable, or the page says how many more there are.

**Actual.** Seeded State COMMEX, Washington W1AW/7, SKYWARN Recognition Day and Winter Field Day vanished once 12 earlier events existed. The editor sees 'Published, but no list shows it right now (it has ended, or the lists are full)', and 'See it on Exercises & events' goes to a missing anchor. The limit 12 is the block attribute from the members template (§3.5).

**Evidence.**
- `/Users/sean/Projects/spokane_ares/wordpress/qa/runs/eval-ares-editor/report.json (event-anchor info on /members/exercises/)`

**Triage.** PLAN conflicts with itself (§3.5 limit 12 vs §2.3 #14); the principle wins. Fix in schedule.php/render.php without changing the block attributes: print the rows beyond the limit or a closing 'and N more' row that keeps their anchors. Test: with 14 upcoming later events, every one has a row and anchor.

**Resolution (fixed).** "Later this season" is no longer cut at 12 (PLAN §2.3 #14 wins), so every upcoming event keeps its #anchor. Regression test: `dev/tests/php/qa-063-later-overflow-test.php`.

### QA-064

**Changing the Winlink weeks leaves old assignments published on Tuesdays that are no longer Winlink nights**

| | |
|---|---|
| Kind | bug |
| Severity | low |
| Category | bug |
| Roles | ares-net, admin, anonymous |
| Group | plugin-front |
| Test | php |
| Status | fixed |
| Reported by | ares-editor-net |
| Files | `plugins/spokares-core/inc/render.php`<br>`plugins/spokares-core/inc/schedule.php` |

**Repro.** In Net details change Winlink nights from 2nd+4th to 1st+3rd and GMRS to 2nd; save; open /members/ and /members/exercises/.

**Expected.** The Winlink assignments list agrees with the rota (or the save warns).

**Actual.** The rota marks Tue, Oct 13 'GMRS net, 6:45 PM' while Exercises still lists 'Tue, Oct 13 Did You Feel It report (ShakeOut practice)' and five more assignments on non-Winlink Tuesdays. The 'winlink' view lists every row with a wl_task without checking the row's kind.

**Evidence.**
- wl.mjs: exercises winlink table lists Oct 13, Oct 27, Nov 10, Nov 24, Dec 8, Dec 22 while the rota shows Oct 13 'GMRS net'

**Triage.** Contradictory public schedule. Fix: the winlink view lists only Winlink-night Tuesdays. Test: a stored wl_task on a non-Winlink Tuesday doesn't render in the winlink view.

**Resolution (fixed).** Winlink assignments lists a rota row only on a Tuesday that is a Winlink night now. Regression test: `dev/tests/php/qa-064-stale-winlink-weeks-test.php`.

### QA-065

**A week ticked in two net groups is advertised under both in 'Other nets', but the rota shows only one**

| | |
|---|---|
| Kind | bug |
| Severity | low |
| Category | ux |
| Roles | ares-net, admin, anonymous |
| Group | plugin-front |
| Test | php |
| Status | fixed |
| Reported by | ares-editor-net |
| Files | `plugins/spokares-core/inc/render.php` |

**Repro.** In Net details tick Winlink 1st–4th and GMRS 1st and 3rd (or Winlink 5th and Simplex 5th); save; compare How it works 'Other nets' with the /members/ rota.

**Expected.** The public lines follow the stated precedence ('A week ticked twice counts as simplex first, then Winlink, then GMRS'), or the save warns.

**Actual.** 'ACS GMRS net: 1st and 3rd Tuesdays' while those Tuesdays show 'Winlink night' in the rota; with the 5th in both, 'Winlink nights: 2nd, 4th and fifth Tuesdays' and 'Fifth Tuesdays: the net starts on simplex'. No warning on save.

**Evidence.**
- pub.mjs: other nets 'Winlink nights: 1st, 2nd, 3rd and 4th Tuesdays … ACS GMRS net: 1st and 3rd Tuesdays'

**Triage.** Inconsistent public schedule. Fix: the other-nets lines apply the rota's precedence. Test: render other-nets with overlapping weeks; each week is listed once, under its winning group.

**Resolution (fixed).** spokares_net_weeks() applies the rota precedence (simplex, Winlink, GMRS), and Other nets lists each week once under the group that wins it. Regression test: `dev/tests/php/qa-065-other-nets-precedence-test.php`.

### QA-066

**The phone menu, opened below 1020px, stays open after the window widens: Close overlaps 'Join the team' and the page stays scroll-locked**

| | |
|---|---|
| Kind | bug |
| Severity | low |
| Category | visual |
| Roles | anonymous, subscriber, contributor, author, core-editor, ares-editor, ares-net, admin |
| Group | theme |
| Test | e2e |
| Status | fixed |
| Reported by | anonymous |
| Files | `theme/spokares/assets/css/site.css`<br>`theme/spokares/parts/header.html` |

**Repro.** At 900px wide tap Menu, then widen to 1100px (a tablet rotated to landscape).

**Expected.** The overlay closes, or the header switches cleanly to the desktop nav and the page scrolls.

**Actual.** The drop-down stays open with the vertical list, 'Close' is drawn over 'Join the team' ('Join the teClose'), and html keeps has-modal-open with overflow:hidden, so the wheel doesn't scroll.

**Evidence.**
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-anonymous/menu-open-then-widen-1100.png`
- rotate.mjs: after widen {htmlCls:'has-modal-open', overflow:'hidden', open:true}

**Triage.** A side effect of the theme's 1020px menu breakpoint (PLAN risk 4). Fix in the theme (CSS that neutralises the open state and scroll lock at 1020px and up, or a small script that closes it on resize). Test (e2e): open at 900, resize to 1100; the page scrolls and Close is hidden.

**Resolution (fixed).** A small header.js closes the open phone menu through its own Close button when the window reaches 1020px, clearing the scroll lock and focus trap. Regression test: `dev/tests/e2e/qa-066-menu-resize.test.mjs`.

### QA-067

**Nowrap buttons, tags and spans are cut off at 320px, with WCAG text spacing, or at a 200% default text size**

| | |
|---|---|
| Kind | bug |
| Severity | low |
| Category | accessibility |
| Roles | anonymous, subscriber, contributor, author, core-editor, ares-editor, ares-net, admin |
| Group | theme |
| Test | e2e |
| Status | fixed |
| Reported by | a11y-responsive |
| Files | `theme/spokares/assets/css/site.css` |

**Repro.** Open /members/exercises/ at 320px (1280 at 400% zoom); inject the WCAG 1.4.12 spacing at 320 and 390 on /about/, / and /members/; set html{font-size:200%} on /members/ at 390.

**Expected.** Content reflows within 320 CSS px (1.4.10) and nothing is clipped with text spacing (1.4.12) or larger text.

**Actual.** 'Exercise details on groups.io ↗' (white-space:nowrap) is 292px in a 250px card, cut at the viewport edge (right=329) and unreachable because .wp-site-blocks has overflow-x:clip. With spacing: the exercises button at 390, About 'Email the Emergency Coordinator' and 'Get the ARES application', the Home 'Optional' tag and the hub rota table. At 200% text the net bar's &lt;span class=nowrap>W7GBU 147.300 MHz,&lt;/span> runs past the bar (right=406 at 390).

**Evidence.**
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-a11y-responsive/anonymous/exercises-320.png`
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-a11y-responsive/spacing/spacing-about-320.png`
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-a11y-responsive/spacing/text200-members-390.png`

**Triage.** Merged the two a11y nowrap reports; all fixable in site.css (let .btn, .tag and .nowrap wrap at narrow widths). The nowrap span is printed by render.php, but CSS can release it, so it stays in the theme. Test (e2e): at 320 no .btn, .tag or .nowrap extends past its container.

**Resolution (fixed).** Buttons, tags and .nowrap phrases may wrap when their column is narrower than them, the Join steps grid gives the title room, and the hub rota stacks below 360px. Regression test: `dev/tests/e2e/qa-067-nowrap-reflow.test.mjs`.

### QA-068

**Crawler and CDP helper: a fixed 10 s DevTools connect timeout, a role that fails to start still exits 0, and a browser that dies mid-run turns every later URL into a false error**

| | |
|---|---|
| Kind | bug |
| Severity | low |
| Category | tooling |
| Roles | anonymous, contributor |
| Group | dev-docs |
| Test | none |
| Status | fixed |
| Reported by | anonymous, contributor |
| Files | `dev/lib/cdp.mjs`<br>`dev/qa/crawl.mjs` |

**Repro.** Under parallel load (load average ~30) run node dev/qa/crawl.mjs &lt;PORT> qa/runs/&lt;name> --roles=anonymous (or contributor).

**Expected.** The launch retries or waits long enough; a role that can't start fails the run (non-zero exit); a dead browser is restarted and signed in again, as the README says.

**Actual.** 'role anonymous stopped: Error: timeout after 10000 ms: DevTools WebSocket', then '1 URLs visited … 1 errors' and exit 0. When Chrome's connection closed mid-run, every later URL was recorded as HTTP 0 ('browser is closed'): 112 false errors and 33 warnings; a re-run was clean. visit() restarts only when visitOnce() throws, and page.goto() doesn't throw on a closed browser.

**Evidence.**
- `/private/tmp/claude-501/-Users-sean-Projects/4d6153be-1331-4cf4-9e9d-4432723cc013/scratchpad/crawl.out`
- `/private/tmp/claude-501/-Users-sean-Projects/4d6153be-1331-4cf4-9e9d-4432723cc013/scratchpad/crawl.log`
- `/private/tmp/claude-501/-Users-sean-Projects/4d6153be-1331-4cf4-9e9d-4432723cc013/scratchpad/crawl2.log`

**Triage.** Merged the anonymous and contributor tooling reports. Dev-only; never shipped. Fix: configurable, longer connect timeout; treat a closed browser as a throw so the restart path runs; exit non-zero when a role never starts.

**Resolution (fixed).** cdp.mjs gives each Chrome launch 60 s (configurable) and fails fast on a dead browser; the crawler restarts Chrome and signs in again, and exits 1 when a role could not be crawled. Regression test: `dev/tests/e2e/qa-068-crawler-resilience.test.mjs`.

### QA-069

**Deleting a file that a page or document uses ends in a bare 'Error in deleting the attachment.' (HTTP 500 page, alert, or REST 500)**

| | |
|---|---|
| Kind | quality |
| Severity | low |
| Category | ux |
| Roles | author, core-editor, admin |
| Group | governance-security |
| Test | none (not a bug) |
| Status | fixed |
| Reported by | author, admin-core, core-editor |
| Files | `plugins/spokares-core/inc/files.php` |

**Repro.** As admin in Media › Library (list view) click 'Delete Permanently' on the Home hero, or in grid view open it and click 'Delete permanently'; or REST DELETE /wp/v2/media/&lt;in-use id>?force=true.

**Expected.** A refusal that says why and what to do ('This photo is used by Home. Replace it there first.'), with a way back and a 4xx status.

**Actual.** List view: HTTP 500 wp_die page 'Error in deleting the attachment.' with no reason or link back. Grid view: a JS alert with the same text. REST: HTTP 500 rest_cannot_delete 'The post cannot be deleted.' The guard itself is right (the file is kept).

**Evidence.**
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-admin-core/media-hero-delete-list.png`
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-author/attachment-delete-in-use-desktop.png`

**Triage.** Merged the author, admin-core and core-editor mentions. The guard works; only the message and status are poor. Fix: pre-check with spokares_attachment_uses() on post.php action=delete and the REST delete, and explain where the file is used.

**Resolution (fixed).** Deleting a file a page or document uses ends in a 409 page (or REST 409) that says where it is used, with a link back; the grid's alert explains why. Regression test: `dev/tests/php/qa-security-governance-test.php`.

### QA-070

**The core Editor's unfiltered_html is removed only by the DISALLOW_UNFILTERED_HTML constant**

| | |
|---|---|
| Kind | quality |
| Severity | low |
| Category | security |
| Roles | core-editor |
| Group | governance-security |
| Test | none (not a bug) |
| Status | fixed |
| Reported by | core-editor |
| Files | `plugins/spokares-core/inc/roles.php` |

**Repro.** Code review; with the constant set, a REST save as qa-core-editor with &lt;script>, &lt;img onerror>, &lt;iframe> and javascript: in Home's first paragraph is stripped by kses.

**Expected.** A single-site Editor never gets unfiltered_html, whatever wp-config.php contains.

**Actual.** The role still has unfiltered_html; current_user_can() is false only because the dev blueprint sets the constant. The skeleton checks the first tag's class and style attributes, not other markup in a paragraph, so on a server without the constant a core Editor could store script in Home, How it works or About. ops/weekly-check.sh doesn't check the constant (see QA-110).

**Evidence.**
- whoami: role caps include unfiltered_html; current_user_can('unfiltered_html') false only because of the constant

**Triage.** Defence in depth: PLAN §5.2 already requires DISALLOW_UNFILTERED_HTML. Fix: map_meta_cap sends unfiltered_html to do_not_allow for users without manage_options.

**Resolution (fixed).** unfiltered_html needs manage_options whatever wp-config.php says. Regression test: `dev/tests/php/qa-security-governance-test.php`.

### QA-071

**Links in page text aren't checked: a 'javascript:' button link saves as a broken relative link, and http:// links are accepted without a warning**

| | |
|---|---|
| Kind | quality |
| Severity | low |
| Category | ux |
| Roles | ares-editor, ares-net, core-editor |
| Group | governance-security |
| Test | none (not a bug) |
| Status | fixed |
| Reported by | ares-editor |
| Files | `plugins/spokares-core/assets/js/editor-guard.js`<br>`plugins/spokares-core/inc/governance.php` |

**Repro.** On Home select 'See how it works', set its link to javascript:alert(document.cookie), Save; or add a link to http://example.com/x in a paragraph and Save.

**Expected.** PLAN §4.4: web addresses are https only; the save is refused or a warning names the bad link.

**Actual.** 'Page updated.' kses strips the protocol, so the hero button becomes href="alert(document.cookie)" (a 404 relative link): safe, but it silently breaks the main call to action. http:// links save unchanged.

**Evidence.**
- Home HTML: &lt;a class="wp-block-button__link wp-element-button" href="alert(document.cookie)">See how it works&lt;/a>

**Triage.** §4.4's https rule is listed for data fields; page text is warn-only. Add link checks to the editor guard's save warning (and optionally the server).

**Resolution (fixed).** editor-guard.js warns after a save when a new or changed link doesn't start with https://, mailto:, /, # or ?. No automated test: checking it needs a page save, which browser tests don't roll back; checked once in the browser.

### QA-072

**No way for an administrator to see who holds the Net details grant**

| | |
|---|---|
| Kind | quality |
| Severity | low |
| Category | ux |
| Roles | admin |
| Group | governance-security |
| Test | none (not a bug) |
| Status | fixed |
| Reported by | ares-editor-net, admin-custom |
| Files | `plugins/spokares-core/inc/roles.php` |

**Repro.** As admin open Users and try to find which accounts may change Net details and Meeting rules.

**Expected.** Revoking access starts with finding the holders: a column, a filter, or a list on ARES site settings.

**Actual.** Users columns are Username | Name | Email | Role | Posts | Two-Factor; the grant shows only as a checkbox on each user's own edit screen.

**Evidence.**
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-ares-editor-net/admin-user-edit-grant-qa-ares-net.png`

**Triage.** Pairs with QA-002 (same file). Add a Users list column or a 'Net details' view.

**Resolution (fixed).** The Users screen has a "Net details" column for administrators. Regression test: `dev/tests/php/qa-security-governance-test.php`.

### QA-073

**Forced two-factor setup redirects without saying why, and the profile page throws a JS exception from a refused REST call**

| | |
|---|---|
| Kind | quality |
| Severity | low |
| Category | ux |
| Roles | admin, author |
| Group | governance-security |
| Test | none (not a bug) |
| Status | fixed |
| Reported by | admin-core |
| Files | `mu-plugins/spokares-hardening/two-factor.php` |

**Repro.** Staging-like boot (SPOKARES_DEV false): sign in as a user with no 2FA provider and click any admin menu item; watch the console on profile.php.

**Expected.** A notice saying why ('Set up two-factor sign-in to continue: tick Email or scan the app code, then Update Profile') and no errors on the setup page.

**Actual.** Every admin link silently 302s to profile.php#two-factor-options with no notice, the full menu still showing. On that page GET /wp-json/wp/v2/users/me?context=edit returns 403 (spokares_2fa_required) and raises an uncaught exception; wp-compression-test and heartbeat get 403. Setup itself works.

**Evidence.**
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-admin-core/staging-admin-no2fa-redirect.png`
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-admin-core/staging-admin-after-enable-email.png`

**Triage.** UX of the §5.4 enforcement floor. Add the notice; allow /wp/v2/users/me for the user's own profile, or accept the console error.

**Resolution (fixed).** The forced two-factor redirect shows a notice saying why; GET /wp/v2/users/me and the profile screen's own ajax calls are allowed. No automated test: enforcement is off on the dev site; code review only.

### QA-074

**Media Library 'View' and attachment-page links go to 404**

| | |
|---|---|
| Kind | quality |
| Severity | low |
| Category | ux |
| Roles | admin, author, core-editor |
| Group | governance-security |
| Test | none (not a bug) |
| Status | fixed |
| Reported by | admin-core, author |
| Files | `mu-plugins/spokares-hardening/content-surface.php` |

**Repro.** As admin open Media › Library (list view) and follow 'View' on 1920x1200sunset; or read 'Permalink' on an Edit Media screen.

**Expected.** No View link when attachment pages are deliberately off, or a link to the file itself.

**Actual.** The row action, 'View attachment page' in the details modal and the Edit Media permalink open a 404, because the must-use plugin 404s attachment pages on purpose (§5.5) but core still offers the links.

**Evidence.**
- curl as admin: GET /1920x1200sunset/ -> 404

**Triage.** Attachment pages off is by design; the links are the problem. An attachment_link filter returning the file URL fixes all of them.

**Resolution (fixed).** attachment_link returns the file URL, so Media "View" goes to the file; attachment pages still 404. Regression test: `dev/tests/php/qa-security-governance-test.php`.

### QA-075

**The fail-closed notice tells administrators, who are the webmaster, to 'Contact the webmaster'**

| | |
|---|---|
| Kind | quality |
| Severity | low |
| Category | copy |
| Roles | admin |
| Group | governance-security |
| Test | none (not a bug) |
| Status | fixed |
| Reported by | admin-core |
| Files | `mu-plugins/spokares-hardening/two-factor.php` |

**Repro.** Staging-like boot: as admin deactivate the Two Factor plugin and look at any admin screen.

**Expected.** An administrator's notice, e.g. 'Two-factor sign-in is off: editors are locked out of wp-admin. Reactivate Two Factor on the Plugins screen.', with a link.

**Actual.** 'Sign-in protection is off. Contact the webmaster. The Two-Factor plugin is not active, so editors are locked out of wp-admin until it is.' No link to Plugins.

**Evidence.**
- curl as admin after deactivation: &lt;div class="notice notice-error">…Contact the webmaster…

**Triage.** PLAN §5.3 fixes the editors' sentence, not the admins'. Copy fix.

**Resolution (fixed).** Administrators see "Sign-in protection is off…" with a link to reactivate Two Factor; editors' wording is unchanged. No automated test: the notice only shows outside dev; code review only.

### QA-076

**Users with no site tasks (Subscriber, Contributor, Author) land on a blank Dashboard that says 'Add boxes from the Screen Options menu', which has no boxes**

| | |
|---|---|
| Kind | quality |
| Severity | low |
| Category | ux |
| Roles | subscriber, contributor, author |
| Group | plugin-admin |
| Test | none (not a bug) |
| Status | fixed |
| Reported by | subscriber, contributor, author |
| Files | `plugins/spokares-core/inc/dashboard.php`<br>`plugins/spokares-core/inc/admin-trim.php` |

**Repro.** Open /wp-admin/ as qa-subscriber, qa-contributor or qa-author; open Screen Options and Help; repeat at 390×844.

**Expected.** A plain sentence for an account with nothing to do ('This account can't change anything on the site. Ask the webmaster.') or a link to Profile; no instruction the user can't follow; Help that matches the screen.

**Actual.** Two dashed placeholders 'Add boxes from the Screen Options menu'; Screen Options has only the generic 'Screen elements' paragraph; Help describes Activity, Quick Draft and WordPress Events and News, all removed; at 390 the page is blank under 'Dashboard'. spokares_trim_dashboard() removes every core widget for non-admins and spokares_dashboard_setup() adds 'Site tasks' only for rota, event or page editors.

**Evidence.**
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/baseline/subscriber/dashboard-desktop.png`
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-contributor/dashboard-screen-options.png`
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-subscriber/probe/dashboard-help.png`
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-author/dashboard-screen-options-desktop.png`

**Triage.** Merged the subscriber, contributor and author reports plus the Dashboard half of the subscriber help-tab report. Roles the site doesn't intend (§4.1), so quality.

**Resolution (fixed).** Accounts with no site tasks get a "Your account" widget, no Screen Options, no empty drop zones and a replaced Help tab. The final verifier spaced the widget's "Go to the site" link from its button. Regression test: `dev/tests/php/qa-admin-quality-test.php`.

### QA-077

**The Profile screen's Help tab describes options that are hidden for non-admins**

| | |
|---|---|
| Kind | quality |
| Severity | low |
| Category | copy |
| Roles | subscriber, contributor, author, core-editor, ares-editor, ares-net |
| Group | plugin-admin |
| Test | none (not a bug) |
| Status | fixed |
| Reported by | subscriber, contributor |
| Files | `plugins/spokares-core/inc/admin-trim.php`<br>`plugins/spokares-core/inc/admin-common.php` |

**Repro.** Open /wp-admin/profile.php as any non-admin (the ARES Editors included) and click Help.

**Expected.** Help that matches the trimmed screen (name, e-mail, password and Two-Factor), as the plugin's own screens do (spokares_screen_help).

**Actual.** Overview: 'You can change your password, turn on keyboard shortcuts, change the color scheme…, turn off the WYSIWYG (Visual) editor… You can hide the Toolbar… You can select the language…'; all but the password are hidden.

**Evidence.**
- curl as subscriber: profile.php #tab-panel-overview text

**Triage.** Merged the profile halves of the subscriber and contributor help reports (the Dashboard half is QA-076). Replace the profile help tab for non-admins.

**Resolution (fixed).** Non-admins' Profile help tab describes only what they see (name, e-mail, password, Two-Factor). Checked on screen.

### QA-078

**The command palette in a Subscriber's toolbar offers nothing useful and fires failing REST requests on every keystroke**

| | |
|---|---|
| Kind | quality |
| Severity | low |
| Category | ux |
| Roles | subscriber |
| Group | plugin-admin |
| Test | none (not a bug) |
| Status | fixed |
| Reported by | subscriber |
| Files | `plugins/spokares-core/inc/admin-bar.php` |

**Repro.** As qa-subscriber on /wp-admin/ click '⌘K' in the toolbar (or press Cmd/Ctrl+K), type 'add', 'page', 'site' and watch the Network panel.

**Expected.** Removed like the other trimmed toolbar items (PLAN §4.2) for users without edit_posts; at least no failing requests.

**Actual.** Only 'Go to: Dashboard' and 'View site'. Each keystroke fires /wp/v2/pages and /wp/v2/posts searches (400); opening it fires themes, templates and template-parts requests (403), shown as red errors in DevTools. Core palette behaviour; spokares_admin_bar() doesn't remove the 'command-palette' node.

**Evidence.**
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-subscriber/probe/dashboard-command-palette-add.png`
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-subscriber/probe/dashboard-command-palette.png`

**Triage.** Unintended role, cosmetic errors. Remove the node for users without edit_posts.

**Resolution (fixed).** Users without edit_posts get no command palette and no failing REST searches. Regression test: `dev/tests/php/qa-admin-quality-test.php`.

### QA-079

**The Edit Media screen and the legacy media-upload.php library open for non-admins, with dead-end links**

| | |
|---|---|
| Kind | quality |
| Severity | low |
| Category | ux |
| Roles | author, core-editor, ares-editor, ares-net |
| Group | plugin-admin |
| Test | none (not a bug) |
| Status | fixed |
| Reported by | author, core-editor |
| Files | `plugins/spokares-core/inc/admin-trim.php` |

**Repro.** As qa-author upload a JPEG over REST and open /wp-admin/post.php?post=&lt;id>&action=edit; as qa-core-editor open /wp-admin/media-upload.php?tab=library.

**Expected.** PLAN §4.2 ('Media is hidden and upload.php redirects editors to the Dashboard'): the per-item Media screen and media-upload.php are redirected too, or offer only working actions.

**Actual.** Edit Media opens (200) with 'Add Media File' (to media-new.php, silently redirected to the Dashboard), 'Permalink: …/gps/' (a 404 attachment page), an Attachment Attributes › Template box and 'Delete permanently'. media-upload.php lists every file with edit fields and Delete for the core Editor. spokares_trim_redirects() covers upload.php and media-new.php only.

**Evidence.**
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-author/attachment-edit-desktop.png`
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-core-editor/media-upload-library.png`

**Triage.** Merged the author report with the screen half of the core-editor media report (the capability half is QA-011). Extend spokares_trim_redirects() to post.php for attachments and to media-upload.php for non-admins; check the Cover's Replace (media modal) still works for ARES Editors.

**Resolution (fixed).** Non-admins are sent from media-upload.php and the attachment Edit Media screen to the Dashboard. Regression test: `dev/tests/php/qa-admin-quality-test.php`.

### QA-080

**Dashboard 'Site tasks' numbers are hard-coded, so the core Editor sees a single task labelled '5'**

| | |
|---|---|
| Kind | quality |
| Severity | low |
| Category | visual |
| Roles | core-editor |
| Group | plugin-admin |
| Test | none (not a bug) |
| Status | fixed |
| Reported by | core-editor |
| Files | `plugins/spokares-core/inc/dashboard.php` |

**Repro.** Sign in as qa-core-editor and open the Dashboard.

**Expected.** Tasks are numbered from 1, or unnumbered when there is only one.

**Actual.** The only section, 'Page text', has a yellow '5' badge: dashboard.php prints &lt;span class="spk-task__n">5&lt;/span> literally (line ~254, and the other numbers too).

**Evidence.**
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/baseline/core-editor/dashboard-desktop.png`

**Triage.** Only users missing some caps see gaps; ARES Editors see 1–5. Number the tasks as they are printed.

**Resolution (fixed).** Site tasks badges are numbered as printed. Regression test: `dev/tests/php/qa-admin-quality-test.php`.

### QA-081

**Document upload refused for a missing Privacy tick: the text under the file chooser says 'Choose the file to upload.' although a file was chosen**

| | |
|---|---|
| Kind | quality |
| Severity | low |
| Category | copy |
| Roles | ares-editor, ares-net |
| Group | plugin-admin |
| Test | none (not a bug) |
| Status | fixed |
| Reported by | ares-editor |
| Files | `plugins/spokares-core/inc/admin-documents.php` |

**Repro.** Add document, Upload a file, leave the Privacy check unticked, send a PDF (picked before unticking, or without JS), Publish.

**Expected.** The text under the field repeats the reason: tick the Privacy check first, then choose the file again.

**Actual.** The notices say 'File not uploaded: tick the Privacy check first…', but the red text under the chooser says 'Choose the file to upload.', because the form re-checks with no upload.

**Evidence.**
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-ares-editor/doc-draft-no-privacy.png`

**Triage.** Copy mismatch only (the notice is correct).

**Resolution (fixed).** A refused file keeps its reason under the file chooser for five minutes. Regression test: `dev/tests/php/qa-admin-quality-test.php`.

### QA-082

**Meeting rules error state: the card's title goes blank, the notice doesn't name the meeting, and the card is striped pink instead of outlined**

| | |
|---|---|
| Kind | quality |
| Severity | low |
| Category | visual |
| Roles | ares-net, admin |
| Group | plugin-admin |
| Test | none (not a bug) |
| Status | fixed |
| Reported by | ares-editor-net |
| Files | `plugins/spokares-core/inc/admin-meetings.php`<br>`plugins/spokares-core/assets/css/admin.css` |

**Repro.** In Meeting rules clear the Name of 'Third Thursday training meeting', set the Workshop's End before its Start, and Save.

**Expected.** The card keeps its stored name as title, the notice names the meeting, and the card is outlined in red as 'Saved, except what is outlined in red' promises.

**Actual.** The legend goes blank (a gap in the border), the notice reads 'This meeting wasn’t saved: it needs a name.', and .spk-row-error > * paints each child pink with white gaps; no red outline.

**Evidence.**
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-ares-editor-net/meeting-rules-errors.png`

**Triage.** Polish of the §4.4 error state.

**Resolution (fixed).** Meeting rules cards keep the stored name as their legend, the notice names the meeting, and an error card is outlined instead of striped. Checked on screen.

### QA-083

**Net details frequency fields are too narrow on phones: '146.880' shows as '146.88'**

| | |
|---|---|
| Kind | quality |
| Severity | low |
| Category | visual |
| Roles | ares-net, admin |
| Group | plugin-admin |
| Test | none (not a bug) |
| Status | fixed |
| Reported by | ares-editor-net |
| Files | `plugins/spokares-core/assets/css/admin.css`<br>`plugins/spokares-core/inc/admin-net.php` |

**Repro.** Open Net details at 390×844 and look at the two Frequency (MHz) fields.

**Expected.** The whole frequency is visible; it is the fact the editor is checking.

**Actual.** Both inputs are 70px wide (small-text); the alternate '146.880' is clipped to '146.88' (scrollWidth 72 > clientWidth 68).

**Evidence.**
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-ares-editor-net/phone-390-net-details-freq.png`

**Triage.** Widen the fields (e.g. 8ch plus padding).

**Resolution (fixed).** The frequency fields are 8em wide (max 100%); "147.300" shows in full at 390. Checked on screen.

### QA-084

**Holders of the Net details grant are told to 'ask the webmaster' for Meeting rules and get no link to it**

| | |
|---|---|
| Kind | quality |
| Severity | low |
| Category | copy |
| Roles | ares-net |
| Group | plugin-admin |
| Test | none (not a bug) |
| Status | fixed |
| Reported by | ares-editor-net |
| Files | `plugins/spokares-core/inc/admin-meetings.php`<br>`plugins/spokares-core/inc/admin-common.php`<br>`plugins/spokares-core/inc/dashboard.php` |

**Repro.** As qa-ares-net open Events › Regular meetings and its Help tab, then the Dashboard 'Meetings' task.

**Expected.** For a user who can open Meeting rules, the line links to it, as the Dashboard's Net rota task links 'Net details' only for grant holders.

**Actual.** The footer and Help say 'Meeting times and weeks are on Meeting rules (ask the webmaster).' to everyone; the Dashboard 'Meetings' task offers only 'Cancel or move a meeting'.

**Evidence.**
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/baseline/ares-net/meetings-desktop.png`
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/baseline/ares-net/dashboard-desktop.png`

**Triage.** The sentence is PLAN §3.4's wording for editors without the grant; varying it for grant holders is an improvement.

**Resolution (fixed).** Regular meetings and the Dashboard link grant holders to Meeting rules. Regression test: `dev/tests/php/qa-admin-quality-test.php`.

### QA-085

**Net details: the GMRS time field's only label is 'at', and the format hints aren't linked to their fields**

| | |
|---|---|
| Kind | quality |
| Severity | low |
| Category | accessibility |
| Roles | ares-net, admin |
| Group | plugin-admin |
| Test | none (not a bug) |
| Status | fixed |
| Reported by | ares-editor-net, a11y-responsive |
| Files | `plugins/spokares-core/inc/admin-net.php` |

**Repro.** Tab to the time input after the ACS GMRS net week boxes, and to 'Frequency (MHz)', with a screen reader.

**Expected.** A name like 'ACS GMRS net time' (WCAG 2.4.6/1.3.1; can be visually hidden) and hints such as 'Like 147.300 (three digits after the point).' linked with aria-describedby.

**Actual.** &lt;label for="spk-gmrs-time">at&lt;/label>, so it is announced 'at, time'; the &lt;p class="description"> hints aren't linked, although the frequency inputs use a pattern the hint explains.

**Evidence.**
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-a11y-responsive/logs/out-rota.log`

**Triage.** Merged the ares-editor-net and a11y reports. Minor labelling fix.

**Resolution (fixed).** The GMRS time field has a full screen-reader label and the format hints are tied to their fields with aria-describedby. Regression test: `dev/tests/php/qa-admin-quality-test.php`.

### QA-086

**Events list search only looks inside the current view, so past events and drafts show 'No events found.'**

| | |
|---|---|
| Kind | quality |
| Severity | low |
| Category | ux |
| Roles | ares-editor, ares-net, admin |
| Group | plugin-admin |
| Test | none (not a bug) |
| Status | fixed |
| Reported by | admin-custom |
| Files | `plugins/spokares-core/inc/admin-events.php` |

**Repro.** Open Events (default view Upcoming), search 'Rockford'.

**Expected.** The search finds the past 'Rockford exercise', or says which view it searched and links to the others with the search kept.

**Actual.** 'No events found.' under 'Search results for: Rockford'; it appears only in Past (spk_view=past&s=Rockford). The view links drop s= and there is no All view, so an admin may create a duplicate.

**Evidence.**
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-admin-custom/admin-events-search-past.png`

**Triage.** The views are per §3.4; searching across them is an improvement.

**Resolution (fixed).** An events search covers every status in any view, with a view link that says so. Regression test: `dev/tests/php/qa-admin-quality-test.php`.

### QA-087

**Restore from Trash turns an event into a Draft that the default list views don't show, and the message doesn't say so**

| | |
|---|---|
| Kind | quality |
| Severity | low |
| Category | ux |
| Roles | ares-editor, ares-net, admin |
| Group | plugin-admin |
| Test | none (not a bug) |
| Status | fixed |
| Reported by | admin-custom |
| Files | `plugins/spokares-core/inc/admin-events.php` |

**Repro.** Trash a published past event, open the Trash view, click Restore.

**Expected.** The message says the event is back as a Draft and off the site, with a way to publish it.

**Actual.** '1 event restored from the Trash. Edit event'; it is now a Draft (core's untrash-to-draft), gone from Past and Upcoming and visible only under Drafts. (List Undo restores the old status and works.)

**Evidence.**
- curl: after Restore, views 'Upcoming (12) | Past (7) | Drafts (1)'

**Triage.** Copy for spokares_bulk_message 'untrashed'.

**Resolution (fixed).** The untrash message says the events came back as drafts and where to find them. Regression test: `dev/tests/php/qa-admin-quality-test.php`.

### QA-088

**The Dashboard's 'N items marked Needs checking' line gives no way to find the items**

| | |
|---|---|
| Kind | quality |
| Severity | low |
| Category | ux |
| Roles | admin |
| Group | plugin-admin |
| Test | none (not a bug) |
| Status | fixed |
| Reported by | admin-custom, admin-core |
| Files | `plugins/spokares-core/inc/dashboard.php`<br>`plugins/spokares-core/inc/admin-events.php`<br>`plugins/spokares-core/inc/admin-documents.php` |

**Repro.** As admin read Site tasks › Needs attention ('24 items marked Needs checking.') and try to find them.

**Expected.** The count links to a filtered Events and Documents list, and names the settings screens that add to it (Net details, Meeting rules, ARES site).

**Actual.** Plain text. The Events and Documents 'Needs checking' columns can't be sorted or filtered, so the webmaster scans every list and screen.

**Evidence.**
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/baseline/admin/dashboard-desktop.png`

**Triage.** Merged the admin-custom and admin-core reports. Helps the §5.11 launch step 'clear every Needs checking item'.

**Resolution (fixed).** Needs attention links to filtered lists (spk_check=1) with counts. Regression test: `dev/tests/php/qa-admin-quality-test.php`.

### QA-089

**Page review box: stale after a block-editor Save (the tick stays on), and a refused Page owner is dropped silently**

| | |
|---|---|
| Kind | quality |
| Severity | low |
| Category | ux |
| Roles | ares-editor, ares-net, admin |
| Group | plugin-admin |
| Test | none (not a bug) |
| Status | fixed |
| Reported by | admin-custom |
| Files | `plugins/spokares-core/inc/admin-pages.php` |

**Repro.** In the block editor tick 'Mark reviewed today when I save' in the Page review box of About and Save; look at the box without reloading. Separately set a Page owner containing a phone number or e-mail and save.

**Expected.** After the save the box shows the new date and the tick clears; a refused owner gets a notice saying why.

**Actual.** After 'Page updated.' the box still says 'Last reviewed: not yet' and stays ticked, so each later Save marks the page reviewed again; the date shows only after a reload. spokares_save_page_review() drops an owner that trips spokares_check_text() without a notice.

**Evidence.**
- Chrome run: after save reviewBox '… Last reviewed: not yet …', cbStillTicked true

**Triage.** Minor; classic meta boxes aren't re-rendered after a block-editor save.

**Resolution (fixed).** A refused page owner is kept and outlined, and after a block-editor save the review box unticks and shows today's date. Regression test: `dev/tests/php/qa-admin-quality-test.php`.

### QA-090

**Document trash controls: 'Mark reviewed today' is offered in the Trash view, and 'Also remove its file' shows for documents with no file**

| | |
|---|---|
| Kind | quality |
| Severity | low |
| Category | ux |
| Roles | ares-editor, ares-net, admin |
| Group | plugin-admin |
| Test | none (not a bug) |
| Status | fixed |
| Reported by | admin-custom |
| Files | `plugins/spokares-core/inc/admin-documents.php` |

**Repro.** Open Documents › Trash and read Bulk actions; open a 'Link to another site' document and look under Move to Trash.

**Expected.** Only Restore and Delete permanently in Trash; the file checkbox only when there is an uploaded file.

**Actual.** Trash bulk actions include 'Mark reviewed today'; a link document shows a ticked 'Also remove its file from the web'.

**Evidence.**
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/baseline/admin/document-edit-desktop.png`

**Triage.** Polish.

**Resolution (fixed).** "Mark reviewed today" isn't offered in the Trash view, and "Also remove its file" shows only when there is a file. Regression test: `dev/tests/php/qa-admin-quality-test.php`.

### QA-091

**The Site Editor lets admins save template, part and style overrides without a warning, and opening Navigation creates a stray published menu**

| | |
|---|---|
| Kind | quality |
| Severity | low |
| Category | ux |
| Roles | admin |
| Group | plugin-admin |
| Test | none (not a bug) |
| Status | fixed |
| Reported by | admin-core |
| Files | `plugins/spokares-core/inc/admin-trim.php`<br>`plugins/spokares-core/inc/admin-common.php` |

**Repro.** As admin open Appearance › Editor: Templates, Header, Footer, Members sub-nav, Styles › Typography and Layout; open Navigation on a fresh site and list /wp/v2/navigation before and after.

**Expected.** PLAN §5.7: templates, parts and CSS are 'Never in the Site Editor (its edits override theme files)', so admins see that warning there; the Navigation screen doesn't create a menu that controls nothing.

**Actual.** No notice anywhere (the Welcome panel even links to 'Open site editor' and 'Edit styles', QA-050). A saved override would silently mask later deploys. Styles › Layout offers width controls that do nothing. Opening Navigation creates a published wp_navigation 'Navigation' listing all six pages, though the real menus are inline links in the parts.

**Evidence.**
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-admin-core/se-templates.png`
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-admin-core/se-styles-layout.png`
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-admin-core/se-navigation.png`

**Triage.** Merged the admin-core Site Editor and Navigation reports; the ops detection half is QA-110. Add a Site Editor notice (a snackbar/notice via the editor's notices store) that edits here override theme files.

**Resolution (fixed).** The Site Editor shows a warning that its saves override the theme, and opening Navigation creates no fallback menu. Regression test: `dev/tests/php/qa-admin-quality-test.php`.

### QA-092

**Plugin form errors aren't tied to their fields (no aria-invalid or aria-describedby), and focus starts on &lt;body>**

| | |
|---|---|
| Kind | quality |
| Severity | low |
| Category | accessibility |
| Roles | ares-editor, ares-net, admin |
| Group | plugin-admin |
| Test | none (not a bug) |
| Status | fixed |
| Reported by | a11y-responsive |
| Files | `plugins/spokares-core/inc/admin-common.php` |

**Repro.** On Net rota type 'hello there' in a Call sign box, Save rota, and inspect the held-back field.

**Expected.** The invalid field has aria-invalid="true" and aria-describedby pointing at its sentence (WCAG 3.3.1, 1.3.1).

**Actual.** It gets the spk-field-error class (red outline) and a sibling &lt;span class="spk-error-text"> with no id; aria-invalid and aria-describedby are null; after the redirect focus is on BODY. The page notice repeats the sentence. spokares_err_class()/spokares_err_text() are used on every plugin screen.

**Evidence.**
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-a11y-responsive/interact/rota-error-ares-net-1440.png`

**Triage.** Errors are identified in text (the notice), so improvement rather than failure. Add the ARIA links in the two helpers.

**Resolution (fixed).** Fields in error get aria-invalid and aria-describedby, and the first error notice takes focus. Regression test: `dev/tests/e2e/qa-admin-quality.test.mjs`.

### QA-093

**Regular meetings table: empty row headers for the second and later dates of each meeting**

| | |
|---|---|
| Kind | quality |
| Severity | low |
| Category | accessibility |
| Roles | ares-editor, ares-net, admin |
| Group | plugin-admin |
| Test | none (not a bug) |
| Status | fixed |
| Reported by | a11y-responsive |
| Files | `plugins/spokares-core/inc/admin-meetings.php` |

**Repro.** Open Regular meetings and navigate the table by rows with a screen reader.

**Expected.** Each row header names the meeting (visually hidden on repeat rows).

**Actual.** &lt;th scope="row"> is printed empty when $i > 0 (line ~94), so 11 of 15 rows announce a blank header; the inputs' own aria-labels do name the meeting and date.

**Evidence.**
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-a11y-responsive/logs/out-ax.log`

**Triage.** Minor; inputs are labelled.

**Resolution (fixed).** Every Regular meetings row header names its meeting (screen-reader-only on repeat rows). Regression test: `dev/tests/php/qa-admin-quality-test.php`.

### QA-094

**/docs/&lt;slug>/ links are case-sensitive: /docs/ICS-213/ is a 404**

| | |
|---|---|
| Kind | quality |
| Severity | low |
| Category | ux |
| Roles | anonymous |
| Group | plugin-front |
| Test | none (not a bug) |
| Status | fixed |
| Reported by | anonymous |
| Files | `plugins/spokares-core/inc/routes.php` |

**Repro.** curl -sI /docs/ICS-213/ and /docs/ics-213/.

**Expected.** The stable link works however it's typed from print or e-mail, or redirects to the lowercase form.

**Actual.** /docs/ics-213/ gives 302 to FEMA; /docs/ICS-213/ is 404. The rewrite rule only accepts ^docs/([a-z0-9-]+)/?$.

**Evidence.**
- curl: /docs/ICS-213/ 404, /docs/ics-213 302

**Triage.** Slugs are lowercase by design; accepting any case is a convenience.

**Resolution (fixed).** /docs/<Slug>/ with capitals resolves like the lower-case slug. Regression test: `dev/tests/e2e/qa-front-094-docs-any-case.test.mjs`.

### QA-095

**With no Winlink, Simplex or GMRS weeks, How it works shows an empty 'Other nets' section**

| | |
|---|---|
| Kind | quality |
| Severity | low |
| Category | visual |
| Roles | ares-net, admin, anonymous |
| Group | plugin-front |
| Test | none (not a bug) |
| Status | fixed |
| Reported by | ares-editor-net |
| Files | `plugins/spokares-core/inc/render.php` |

**Repro.** In Net details untick every week in all three rows and save; open /how-it-works/.

**Expected.** The section shows a sentence (or disappears).

**Actual.** The &lt;h3>Other nets&lt;/h3> heading (page text) sits above an empty how-row__body; the block returns '' outside the editor.

**Evidence.**
- HTML: &lt;h3 class="wp-block-heading">Other nets&lt;/h3>&lt;/div>&lt;div class="wp-block-group how-row__body …">&lt;/div>

**Triage.** Edge case. The heading is page text, so the block should print a line such as 'No other nets are scheduled.'

**Resolution (fixed).** With no other nets, the section says "No other nets are scheduled right now." Regression test: `dev/tests/php/qa-front-095-096-net-tails-test.php`.

### QA-096

**On /members/, grant holders see 'Edit this list in Net details' flush above the rota table, as if it edits the rota**

| | |
|---|---|
| Kind | quality |
| Severity | low |
| Category | ux |
| Roles | ares-net, admin |
| Group | plugin-front |
| Test | none (not a bug) |
| Status | fixed |
| Reported by | ares-editor-net |
| Files | `plugins/spokares-core/inc/blocks.php`<br>`plugins/spokares-core/inc/render.php` |

**Repro.** As qa-ares-net open /members/ at 1440 and 390.

**Expected.** The settings bar's edit link sits clearly with the bar, or reads 'Edit these settings in Net details'; the rota's own link belongs to the table.

**Actual.** 'Edit this list in Net details' sits directly above the rota's header row with no gap; the bar isn't a list and the rota is edited in Net rota.

**Evidence.**
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-ares-editor-net/ares-net/members-desktop.png`
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-ares-editor-net/ares-net/members-mobile-390.png`

**Triage.** Editor-only link placement and wording (§4.2 front-end edit links).

**Resolution (fixed).** Under the net bar the link reads "Edit these settings in Net details"; the rota keeps its own link. The final verifier also set the link 12px under the bar and 20px above the rota table (site.css), so it no longer sits on the table header. Regression test: `dev/tests/php/qa-front-095-096-net-tails-test.php`.

### QA-097

**The copy-confirmation toast creates its live region with the first message, so the first 'Copied' may not be announced**

| | |
|---|---|
| Kind | quality |
| Severity | low |
| Category | accessibility |
| Roles | anonymous, subscriber, contributor, author, core-editor, ares-editor, ares-net, admin |
| Group | plugin-front |
| Test | none (not a bug) |
| Status | fixed |
| Reported by | a11y-responsive |
| Files | `plugins/spokares-core/assets/js/copy.js` |

**Repro.** With a screen reader, load /how-it-works/ and press 'Copy W7GBU settings' once.

**Expected.** The status region exists before its text changes, so the first 'Copied: …' is announced.

**Actual.** copy.js toast() creates &lt;div role=status aria-live=polite>, appends it and sets its text in one step on the first click; regions added with their content are often not announced. Later copies work. DOM state confirmed; not checked with a real screen reader.

**Evidence.**
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-a11y-responsive/interact/copy-toast-390.png`

**Triage.** Create the empty region at init.

**Resolution (fixed).** The toast's live region is created empty at load, so the first "Copied" is announced. Regression test: `dev/tests/e2e/qa-front-097-toast-region.test.mjs`.

### QA-098

**The search results page never shows the query, and an empty result doesn't say nothing matched**

| | |
|---|---|
| Kind | quality |
| Severity | low |
| Category | ux |
| Roles | anonymous, subscriber, contributor, author, core-editor, ares-editor, ares-net, admin |
| Group | theme |
| Test | none (not a bug) |
| Status | fixed |
| Reported by | anonymous, author |
| Files | `theme/spokares/templates/search.html` |

**Repro.** Open /?s=zzqq and /?s=net (or use the admin-bar search).

**Expected.** The heading names the query ('Results for “net”'); an empty result says so ('Nothing matched “zzqq”.') and offers a search box and the document library search (site search covers only page text).

**Actual.** Both show only 'Search results'. With no results the page has only 'Try Home, How it works, About ARES & ACS or For members.' The query appears only in &lt;title>. 'ICS-213' or 'bloomsday' find only page text, never the library or events.

**Evidence.**
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-anonymous/search-none-desktop.png`
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-anonymous/search-net-desktop.png`
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/baseline/author/search-none-desktop.png`

**Triage.** Merged the anonymous and author reports. The search template matches §6.7's list of templates; the content is an improvement.

**Resolution (fixed).** Search results show "Search results for: …", a filled search box, a clear no-results sentence and a link to the library search. Regression test: `dev/tests/php/qa-theme-templates-test.php`.

### QA-099

**Without JavaScript the phone 'Menu' button is shown but does nothing**

| | |
|---|---|
| Kind | quality |
| Severity | low |
| Category | accessibility |
| Roles | anonymous, subscriber, contributor, author, core-editor, ares-editor, ares-net, admin |
| Group | theme |
| Test | none (not a bug) |
| Status | fixed |
| Reported by | anonymous |
| Files | `theme/spokares/parts/header.html`<br>`theme/spokares/assets/css/site.css` |

**Repro.** At 390px with scripts disabled, open / and tap 'Menu'.

**Expected.** Primary navigation reachable without JS (PLAN §3.1 wants pages complete without JS), e.g. links shown or the button hidden by a no-JS rule.

**Actual.** The button is visible and focusable but does nothing; no header links are visible. Footer links and the logo remain, and the library and hidden-until-JS Copy buttons degrade correctly.

**Evidence.**
- nojs.mjs: 'nojs after click open? false visible nav links 0'

**Triage.** Core Navigation behaviour; the footer still offers every page, so quality.

**Resolution (fixed).** Without scripts, Menu and Close are hidden and the four links show in a row under the brand. Checked with scripts disabled at 390 and 900.

### QA-100

**Blocks an administrator adds at the root of Home, How it works or About render full-bleed with no side gutter**

| | |
|---|---|
| Kind | quality |
| Severity | low |
| Category | visual |
| Roles | admin |
| Group | theme |
| Test | none (not a bug) |
| Status | fixed |
| Reported by | admin-custom |
| Files | `theme/spokares/assets/css/site.css` |

**Repro.** As admin insert a Paragraph after Home's last section in the block editor, Save, view Home at 1440 and 390.

**Expected.** New root content sits in the content width with the 16px gutter, or the webmaster has a documented recipe (a Group with the wrap class).

**Actual.** The paragraph spans the full window (0–1440, 0–390) with text touching the edge, in the editor and on the site. Every theme pattern is 'Inserter: no', so there's no styled section to insert. The ARES Editor lock still holds.

**Evidence.**
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-admin-custom/scratch-home-admin-root-paragraph-phone.png`
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-admin-custom/scratch-home-admin-root-paragraph-desktop.png`

**Triage.** Admin layout edits are allowed (§4.3) but mirrored in pattern files by hand (§5.7). A CSS default for bare root blocks inside post-content helps; the recipe goes into the docs (QA-111).

**Resolution (fixed).** Bare blocks at the root of Home, How it works and About sit in the content column (class page-sections), on the site and in the editor. Checked with injected blocks.

### QA-101

**Footer links run together in every editor canvas (Site Editor and pages edited with the template shown)**

| | |
|---|---|
| Kind | quality |
| Severity | low |
| Category | visual |
| Roles | admin, ares-editor, ares-net, core-editor |
| Group | theme |
| Test | none (not a bug) |
| Status | fixed |
| Reported by | admin-core |
| Files | `theme/spokares/assets/css/site.css` |

**Repro.** Open any template, the Footer part, or any page in the block editor and look at the footer.

**Expected.** Separate links 26px apart, as on the site.

**Actual.** 'How it worksAbout ARES & ACSFor Membersgroups.ioContactPrivacy (coming)': core's editor.css rule '.editor-styles-wrapper .wp-block-navigation .wp-block-navigation-item.wp-block { margin: revert }' (0,4,0) beats '.footer__nav > nav.footer__links .wp-block-navigation-item { margin-right: 26px }' (0,3,1).

**Evidence.**
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-admin-core/se-front-page-template.png`
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-admin-core/editor-admin-new-page.png`

**Triage.** Editor-only cosmetic. Raise the rule's specificity or use gap on the list.

**Resolution (fixed).** The footer item rule outranks the editor's margin reset, so footer links are spaced in every editor canvas. Checked in the editor.

### QA-102

**The three members templates show raw slugs ('page-members', 'page-documents', 'page-exercises') with no description**

| | |
|---|---|
| Kind | quality |
| Severity | low |
| Category | copy |
| Roles | admin |
| Group | theme |
| Test | none (not a bug) |
| Status | fixed |
| Reported by | admin-core |
| Files | `theme/spokares/theme.json` |

**Repro.** As admin open Appearance › Editor › Templates, and the For members page's Template row in the block editor.

**Expected.** Readable names and a line saying what each template is (e.g. 'For members (hub)': 'Everything on this page comes from this template').

**Actual.** The templates list, the document bar and the Template row show the file slugs; they are the only templates without a description.

**Evidence.**
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-admin-core/se-templates.png`
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/baseline/admin/editor-members-desktop.png`

**Triage.** Add titles and descriptions in theme.json.

**Resolution (fixed).** The members templates have readable names and descriptions. Regression test: `dev/tests/php/qa-theme-templates-test.php`.

### QA-103

**Header focus order below 1020px doesn't match the visual order (Menu is reached before 'Join the team')**

| | |
|---|---|
| Kind | quality |
| Severity | low |
| Category | accessibility |
| Roles | anonymous, subscriber, contributor, author, core-editor, ares-editor, ares-net, admin |
| Group | theme |
| Test | e2e and php |
| Status | fixed (owner decision 2026-09-27) |
| Reported by | a11y-responsive |
| Files | `theme/spokares/parts/header.html`<br>`theme/spokares/assets/css/site.css` |

**Repro.** Open any public page at 390, 768 or 1019px and Tab from the top.

**Expected.** Focus follows the visual order: brand, Join the team, Menu (WCAG 2.4.3, 1.3.2).

**Actual.** Tab goes Skip, brand (x=16), Menu (x=285), Join the team (x=155): CSS order puts Join (2) before the nav (3), but the DOM has the nav first.

**Evidence.**
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-a11y-responsive/logs/out-interact.log`
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-a11y-responsive/interact/menu-open-390.png`

**Triage.** Minor order mismatch; reorder in header.html and check the desktop layout.

**Resolution (fixed).** Fixed per the owner's decision (2026-09-27), in the markup for every browser. parts/header.html reads brand, a 'Join the team' button (Buttons masthead__actions), then the Navigation, whose four links are followed by a second 'Join the team' (Buttons site-header__actions). site.css no longer uses order or reading-flow. From 1020px the bar's own Join is not drawn, so the desktop header looks as before (0 differing pixels at 1024 and 1440). Below 1020px the bar reads brand, Join, Menu, and the open phone menu ends with its own Join. header.js closes the open menu when a same-page link (Join on Home) is chosen. DOM order equals drawn order and Tab order from 360 to 1440px, with the menu open, and without JavaScript. Regression tests: `dev/tests/php/qa-103-header-order-test.php`, `dev/tests/e2e/qa-103-header-order.test.mjs`.

### QA-104

**Members hub on phones: 'Tuesday net' is moved first with CSS order, so tab and heading order jump back and forth**

| | |
|---|---|
| Kind | quality |
| Severity | low |
| Category | accessibility |
| Roles | anonymous, subscriber, contributor, author, core-editor, ares-editor, ares-net, admin |
| Group | theme |
| Test | e2e and php |
| Status | fixed (owner decision 2026-09-27) |
| Reported by | a11y-responsive |
| Files | `theme/spokares/assets/css/site.css`<br>`theme/spokares/patterns/members-hub.php` |

**Repro.** Open /members/ at 390 (any width up to 900) and Tab past the document search.

**Expected.** Reading and focus order match the visual order: Tuesday net, then This week.

**Actual.** .hub-net{order:-1} shows Tuesday net above This week at 900px and below, but the DOM keeps This week first: focus goes Search, 'What to bring' (lower on the page), then back up to 'Copy radio settings'; headings read This week before Tuesday net.

**Evidence.**
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-a11y-responsive/anonymous/members-390.png`

**Triage.** Minor order mismatch. Changing the members pattern is admin/git work (PLAN §7.2 #11), which the theme group owns.

**Resolution (fixed).** Fixed per the owner's decision (2026-09-27), in the markup at every width. patterns/members-hub.php puts section.hub-net (Tuesday net) first in .hub-grid, before This week; the CSS order and reading-flow are gone. Phones look as before (0 differing pixels at 390 and 768). On desktop the two columns are mirrored, with the same section sizes: Tuesday net on the left (7fr), This week on the right (5fr). Headings read For members, Tuesday net, This week, Exercises, Most used, and Tab from the search goes into the net first. Regression tests: `dev/tests/php/qa-104-hub-order-test.php`, `dev/tests/e2e/qa-104-hub-order.test.mjs`.

### QA-105

**The About 'On this page' floating button covers content and part of focused links near the bottom of the screen**

| | |
|---|---|
| Kind | quality |
| Severity | low |
| Category | accessibility |
| Roles | anonymous, subscriber, contributor, author, core-editor, ares-editor, ares-net, admin |
| Group | theme |
| Test | none (not a bug) |
| Status | fixed |
| Reported by | a11y-responsive |
| Files | `theme/spokares/assets/css/site.css` |

**Repro.** At 390×844 Tab forward through /about/ (the FAB appears after the first screen), then scroll to the page end.

**Expected.** Fixed controls don't hide focused elements or permanent content.

**Actual.** The FAB (169×44, bottom right) covers part of links scrolled to the bottom edge ('Volunteer Emergency Worker Application' 3 of 5 points) and permanently covers the footer line at the page end. It is also tab stop 14 of 51, near the top of the DOM.

**Evidence.**
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-a11y-responsive/interact/focus-obscured-back-0-390.png`

**Triage.** Partly addressed by QA-029's scroll padding; add bottom padding (scroll-padding-bottom and footer space) while the FAB shows.

**Resolution (fixed).** Below 1024px pages with the contents button get scroll-padding-bottom and extra footer padding, so the button covers no focused link or the last footer line. Checked on About at 390.

### QA-106

**Search-field placeholder text is 3.7:1 contrast**

| | |
|---|---|
| Kind | quality |
| Severity | low |
| Category | accessibility |
| Roles | anonymous, subscriber, contributor, author, core-editor, ares-editor, ares-net, admin |
| Group | theme |
| Test | none (not a bug) |
| Status | fixed |
| Reported by | a11y-responsive |
| Files | `theme/spokares/assets/css/site.css` |

**Repro.** Inspect the placeholders of #hub-q ('ICS-213, net script, task book') and #doc-q.

**Expected.** Placeholder hint text meets 4.5:1.

**Actual.** ::placeholder #7B859A on white is 3.71:1; at 390 the placeholder is also cut off ('preamble, 213, task bool'). The fields have visible labels.

**Evidence.**
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-a11y-responsive/anonymous/documents-390.png`

**Triage.** Hint text only; use var(--off) #56627C.

**Resolution (fixed).** The search placeholder is var(--off), 6.1:1 on white.

### QA-107

**A page's excerpt (its meta description) isn't checked for phone numbers or never-publish words**

| | |
|---|---|
| Kind | quality |
| Severity | low |
| Category | security |
| Roles | ares-editor, ares-net, core-editor |
| Group | cross |
| Test | none (not a bug) |
| Status | fixed |
| Reported by | ares-editor |
| Files | `plugins/spokares-core/assets/js/editor-guard.js`<br>`plugins/spokares-core/inc/dashboard.php` |

**Repro.** As ARES Editor open About › Page panel › Edit excerpt (or REST POST /wp/v2/pages/64 {excerpt}), set 'Call 509-555-9999', Save; view the /about/ source and the Dashboard.

**Expected.** The same warning as page text ('This page may contain something we never publish…') and a Dashboard 'Needs attention' line.

**Actual.** Saved with no warning; /about/ prints &lt;meta name="description" content="Call 509-555-9999">; the Dashboard doesn't flag About. The editor guard and the dashboard scan read only post_content.

**Evidence.**
- curl /about/ -> &lt;meta name="description" content="Call 509-555-9999">

**Triage.** Extends the §4.4 page-text warning to the excerpt. Needs editor-guard.js (governance-security) and dashboard.php (plugin-admin), so cross.

**Resolution (fixed).** The excerpt gets the never-publish, phone and e-mail checks after each save (a warning, never a block), and the Dashboard flags it. Regression tests: `dev/tests/php/qa-107-excerpt-check-test.php`, `dev/tests/e2e/qa-107-excerpt-warning.test.mjs`.

### QA-108

**The library's 'Most used' filter disagrees with the hub's 'Most used' tiles (ICS-309 'Soon' listed, ICS-214 missing)**

| | |
|---|---|
| Kind | quality |
| Severity | low |
| Category | data |
| Roles | anonymous |
| Group | dev-docs |
| Test | none (not a bug) |
| Status | fixed |
| Reported by | anonymous |
| Files | `dev/seed/data.json`<br>`dev/seed/extra.json`<br>`dev/seed/import.php` |

**Repro.** Compare the 'Most used' tiles on /members/ with /members/documents/?section=most-used.

**Expected.** The same set, or at least all four hub tiles under the library's Most used. The review fix swapped ICS-309 for ICS-214 because 309 has no file.

**Actual.** Tiles: Net script, ICS-213, ICS-214, ACS Task Book. The library's Most used has 8 rows, including 'ICS 309 Communications Log' (Soon, no file) and not ICS-214. The swap was applied to spk_tiles only; the spk_most_used flags still follow data.js.

**Evidence.**
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-anonymous/members-desktop.png`

**Triage.** Seed data (also used by the production importer, §6.10). Apply the §2.3 tile swap to the most-used flags; admins can also change the flag in the Documents form.

**Resolution (fixed).** dev/seed/extra.json swaps ICS-309 for ICS-214 in Most used, so the library filter matches the hub tiles. Checked on a rebuilt site; checks.sh passes.

### QA-109

**Crawler coverage gaps: overflow inside a column isn't detected, Draft events count as missing anchors, and several reachable screens and write probes aren't in the access matrix**

| | |
|---|---|
| Kind | quality |
| Severity | low |
| Category | tooling |
| Roles | ares-editor, contributor, author, core-editor |
| Group | dev-docs |
| Test | none (not a bug) |
| Status | fixed |
| Reported by | ares-editor, author, contributor, core-editor |
| Files | `dev/lib/inspect.mjs`<br>`dev/qa/crawl.mjs` |

**Repro.** Run node dev/qa/crawl.mjs &lt;PORT> … --roles=ares-editor after the long-link event from QA-004 exists and some Draft events exist; compare the crawler's known screens with the screens found by hand.

**Expected.** Elements wider than their container are flagged; the event-anchor check expects published events only; the matrix covers the Patterns screens (edit.php?post_type=wp_block), attachment edit (post.php for attachments), media-upload.php, edit-tags.php, a POST /wp/v2/blocks probe, and the bulk 'trash' option and block-editor card actions, not only '.row-actions .trash a'.

**Actual.** 0 warnings for /members/exercises/ while the Later table overlaps the Winlink column (domFacts compares only document scrollWidth); Draft events (qa-no-kind, qa-backwards…) listed as missing anchors; QA-001, QA-010 and QA-011 weren't caught by the baseline crawl.

**Evidence.**
- `/Users/sean/Projects/spokane_ares/wordpress/qa/runs/eval-ares-editor/summary.md`
- `/Users/sean/Projects/spokane_ares/wordpress/qa/runs/eval-ares-editor/report.json`

**Triage.** Merged the ares-editor tooling report with the crawler-coverage notes in the author, contributor and core-editor reports. Dev-only.

**Resolution (fixed).** The crawler reports overflow inside a column, expects anchors for published events only, adds the missing screens and a REST write probe to the access matrix, checks Bulk actions and reads the editor page card menu. Used for the final crawl.

### QA-110

**Ops checks miss three things: the full CSP on /wp-admin/, database overrides of templates and styles, and the DISALLOW_UNFILTERED_HTML constant (plus the php.ini upload cap)**

| | |
|---|---|
| Kind | quality |
| Severity | low |
| Category | security |
| Roles | admin |
| Group | dev-docs |
| Test | none (not a bug) |
| Status | fixed |
| Reported by | admin-core, core-editor, author |
| Files | `ops/check-live.sh`<br>`ops/weekly-check.sh`<br>`ops/DEPLOY-RUNBOOK.md` |

**Repro.** Read ops/check-live.sh and ops/weekly-check.sh.

**Expected.** check-live.sh verifies every CSP directive on /, /wp-login.php and signed-in /wp-admin/ (QA-007); weekly-check.sh fails on wp_template, wp_template_part or wp_global_styles posts (PLAN §5.7 and risk 10 say to look for them) and when DISALLOW_UNFILTERED_HTML isn't defined (§5.2; QA-070); the launch checks record upload_max_filesize/post_max_size against the 32 MB cap (§5.3; QA-112).

**Actual.** check-live.sh only looks for frame-ancestors; neither script checks wp_template/wp_global_styles or the constant (weekly-check.sh lines 89–90 check only SPOKARES_DEV, SPOKARES_TODAY and WP_DEVELOPMENT_MODE).

**Evidence.**
- grep of ops/*.sh for wp_template|wp_global_styles|DISALLOW_UNFILTERED_HTML: no matches

**Triage.** Collects the ops halves of QA-007, QA-070, QA-091 and QA-112 so each is owned once.

**Resolution (fixed).** ops/check-live.sh checks every header and all four CSP directives on the admin paths and records the upload limits; ops/weekly-check.sh fails on the DISALLOW_* constants being off and on database template, part and style overrides.

### QA-111

**Docs out of date: build-notes claims the editor's Order and Trash are hidden, and EDITING-GUIDE has no webmaster recipe for adding a section or page**

| | |
|---|---|
| Kind | quality |
| Severity | low |
| Category | copy |
| Roles | admin, ares-editor |
| Group | dev-docs |
| Test | none (not a bug) |
| Status | fixed |
| Reported by | core-editor, admin-custom |
| Files | `build-notes/plugin.md`<br>`EDITING-GUIDE.md` |

**Repro.** Read build-notes/plugin.md (line ~141) and EDITING-GUIDE.md after this fix pass.

**Expected.** Docs match the build: which page-card actions editors see (QA-046, QA-001), how an admin adds a section (Group with the wrap class; QA-100) or a plain page (QA-031), and the 'You can't break the layout' promise matches QA-004's fix.

**Actual.** build-notes says removeEditorPanel('post-status') hides Status, Order and Trash, which the 7.1.2 page card still offers; EDITING-GUIDE only says adding a section is the webmaster's job.

**Evidence.**
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/eval-core-editor/editor-about-ares-editor-card-actions.png`

**Triage.** Update after the code fixes land.

**Resolution (fixed).** build-notes/plugin.md corrects the page card claim, EDITING-GUIDE.md has a webmaster appendix for adding a section or page, and PLAN.md §6.7 now describes the templates and the header offset as built (final verifier).

### QA-112

**The 32 MB upload cap isn't enforced in code: a 40 MB file is accepted and stored in dev**

| | |
|---|---|
| Kind | by-design |
| Severity | low |
| Category | security |
| Roles | author, ares-editor, core-editor, admin |
| Group | governance-security |
| Test | none (not a bug) |
| Status | by-design |
| Reported by | author |
| Files | `mu-plugins/spokares-hardening/uploads.php` |

**Repro.** Build a valid 40 MB PNG and POST it to /wp/v2/media as qa-author.

**Expected.** PLAN §5.3 and the uploads.php header promise a 32 MB cap.

**Actual.** 201: stored and served in full (41,943,394 bytes). spokares_hard_upload_size() only sets upload_size_limit, the UI/plupload hint.

**Evidence.**
- REST response for big.png: id 82, filesize 41943394; GET 200

**Triage.** By design: PLAN §5.3 says '32 MB cap (php.ini)', so the hard limit is the host's upload_max_filesize/post_max_size, not PHP code; Playground's php.ini isn't production's. Action is operational: record the Verpex values at launch (§5.10/§5.11), tracked in QA-110. A code check on wp_handle_upload_prefilter would be cheap defence in depth if the owner wants it.

**Resolution (by-design).** PLAN §5.3 puts the 32 MB cap in php.ini. The live values are recorded at launch through ops/check-live.sh and the runbook (QA-110).

### QA-113

**Administrators are still offered Posts, Categories, Tags, Comments and '+ New › Post', and a published post goes to a 404**

| | |
|---|---|
| Kind | by-design |
| Severity | low |
| Category | ux |
| Roles | admin |
| Group | plugin-admin |
| Test | none (not a bug) |
| Status | by-design |
| Reported by | admin-core |
| Files | `plugins/spokares-core/inc/admin-trim.php`<br>`mu-plugins/spokares-hardening/content-surface.php` |

**Repro.** As admin look at the menu and + New; publish a post (REST: 201) and open its link.

**Expected.** PLAN §4.1: posts aren't used and comments are off everywhere.

**Actual.** The post publishes and its 'View post' link is 404 (content-surface.php); Posts (with Categories, Tags) and Comments stay in the admin menu.

**Evidence.**
- `/Users/sean/Projects/spokane_ares/wordpress/qa/shots/baseline/admin/post-add-desktop.png`
- probe: POST /wp/v2/posts 201; GET /qa-admin-post/ 404

**Triage.** By design: PLAN §3.4 'Administrators see everything plus Documents › Sections and Settings › ARES site'; §4.1 trims the Posts menu for editors and 404s posts on the front end. Hiding the blog screens for admins too is optional polish for the owner.

**Resolution (by-design).** PLAN §3.4 gives administrators everything; posts 404 on the front end (§4.1). Hiding the blog screens for admins too is optional polish.
