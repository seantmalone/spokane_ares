# QA report: spokares.org WordPress build

Final verification, 2026-09-27. Dev site: WordPress 7.1.2, PHP 8.4.25, theme spokares, fresh in-memory Playground on port 9900. The same content as the 2026-09-27 baseline (port 9600). Open `qa/index.html` for the same report with the screenshot gallery. After the owner's decisions on QA-038, QA-039, QA-103, QA-104 (2026-09-27, release 0.1.1), the test suite and `ops/ci.sh` were run again on a fresh dev site on port 9533; the test counts below are from that run, and the crawl figures are from the final verification.

## At a glance

- **Coverage:** 8 user levels, 782 URLs (public pages, admin screens and forbidden probes), 731 screenshots in `qa/shots/final/<role>/`.
- **Issues:** 113 triaged; 111 fixed, 0 need a decision, 2 by design, 0 deferred. Every bug (61) has a regression test that failed before its fix.
- **Tests (0.1.1, port 9533):** checks.sh 50/50, PHP 308/308, e2e 143/143; run-all passed. `ops/ci.sh --no-zip`: 11 passed, 0 failed.
- **Crawl:** 0 errors (baseline 1), no access leaks, no PHP messages, console errors or overflow. The 17 warnings left are WordPress core screens and the intentional Site Editor warning (QA-091).

## Scope: roles and pages

| Role | Label | URLs | public | admin | forbidden | screenshots |
|---|---|---:|---:|---:|---:|---:|
| anonymous | Anonymous visitor | 96 | 27 | 0 | 69 | 52 |
| subscriber | Subscriber (core role) | 94 | 25 | 3 | 66 | 93 |
| contributor | Contributor (core role) | 94 | 25 | 9 | 60 | 93 |
| author | Author (core role) | 94 | 25 | 9 | 60 | 93 |
| core-editor | Editor (core role) | 94 | 25 | 15 | 54 | 93 |
| ares-editor | ARES Editor | 94 | 25 | 24 | 45 | 93 |
| ares-net | ARES Editor + Net details and Meeting rules grant | 94 | 25 | 26 | 43 | 93 |
| admin | Administrator | 122 | 25 | 86 | 11 | 121 |
| **All** | | **782** | 202 | 172 | 408 | **731** |

*public*: the six pages, 404, search, sign-in pages (visitor) and every same-origin page linked from them, at 1440 and at a true 390x844 phone. *admin*: every screen the role can open (menus, admin bar, Dashboard links and the known screens). *forbidden*: the known screens the role must not open, the plugin's admin-post actions without a nonce, and REST probes including one write probe.

## Automated findings, before and after (per role)

Baseline: `qa/runs/baseline/report.json` (2026-09-27 15:46). Final: `qa/runs/final/report.json`. Cells are *before → after*. The final crawl also runs checks the baseline didn't have (QA-109: overflow inside a column, Bulk actions policy, the editor page card menu, more matrix rows and a REST write probe), so a new finding type can appear with no "before".

| Role | Errors | Warnings | PHP messages | Console errors / JS exceptions | Failed requests | Overflow (page or inner) | A11y signals (warn+) | Policy / leak / lock errors |
|---|---:|---:|---:|---:|---:|---:|---:|---:|
| anonymous | 0 → 0 | 4 → 4 | 0 → 0 | 0 → 0 | 0 → 0 | 0 → 0 | 4 → 4 | 0 → 0 |
| subscriber | 0 → 0 | 0 → 0 | 0 → 0 | 0 → 0 | 0 → 0 | 0 → 0 | 0 → 0 | 0 → 0 |
| contributor | 0 → 0 | 0 → 0 | 0 → 0 | 0 → 0 | 0 → 0 | 0 → 0 | 0 → 0 | 0 → 0 |
| author | 0 → 0 | 0 → 0 | 0 → 0 | 0 → 0 | 0 → 0 | 0 → 0 | 0 → 0 | 0 → 0 |
| core-editor | 1 → 0 | 0 → 0 | 0 → 0 | 0 → 0 | 0 → 0 | 0 → 0 | 0 → 0 | 1 → 0 |
| ares-editor | 0 → 0 | 0 → 0 | 0 → 0 | 0 → 0 | 0 → 0 | 0 → 0 | 0 → 0 | 0 → 0 |
| ares-net | 0 → 0 | 0 → 0 | 0 → 0 | 0 → 0 | 0 → 0 | 0 → 0 | 0 → 0 | 0 → 0 |
| admin | 0 → 0 | 4 → 13 | 0 → 0 | 0 → 0 | 4 → 4 | 0 → 0 | 0 → 0 | 0 → 0 |
| **All** | 1 → 0 | 8 → 17 | 0 → 0 | 0 → 0 | 4 → 4 | 0 → 0 | 4 → 4 | 1 → 0 |

The admin's extra warnings are the Site Editor warning added on purpose by QA-091 (9 screens); see "Findings left" below.

**Forbidden-access outcomes** (before → after): denied = refused with 403/400/500; login = sent to sign-in; redirected = sent elsewhere (Dashboard); empty = a REST list answered 200 with nothing in it; LEAK = opened without the capability. The counts grow because the final crawl probes more screens (QA-109); every new probe ends refused or redirected.

| Role | denied | login | redirected | empty | LEAK | ERROR |
|---|---:|---:|---:|---:|---:|---:|
| anonymous | 22 → 23 | 40 → 46 | 0 → 0 | 0 → 0 | 0 → 0 | 0 → 0 |
| subscriber | 58 → 63 | 0 → 0 | 0 → 2 | 1 → 1 | 0 → 0 | 0 → 0 |
| contributor | 50 → 55 | 0 → 0 | 3 → 5 | 0 → 0 | 0 → 0 | 0 → 0 |
| author | 48 → 53 | 0 → 0 | 5 → 7 | 0 → 0 | 0 → 0 | 0 → 0 |
| core-editor | 42 → 47 | 0 → 0 | 5 → 7 | 0 → 0 | 0 → 0 | 0 → 0 |
| ares-editor | 33 → 38 | 0 → 0 | 5 → 7 | 0 → 0 | 0 → 0 | 0 → 0 |
| ares-net | 31 → 36 | 0 → 0 | 5 → 7 | 0 → 0 | 0 → 0 | 0 → 0 |
| admin | 11 → 11 | 0 → 0 | 0 → 0 | 0 → 0 | 0 → 0 | 0 → 0 |

### Access matrix: outcomes that changed (0)

None.

New matrix rows in the final crawl (QA-109):

| Screen | anonymous | subscriber | contributor | author | core-editor | ares-editor | ares-net | admin |
|---|---|---|---|---|---|---|---|---|
| categories | login | 403 | 403 | 403 | 403 | 403 | 403 | ok |
| tags | login | 403 | 403 | 403 | 403 | 403 | 403 | ok |
| pattern-categories | login | 403 | 403 | 403 | 403 | 403 | 403 | ok |
| patterns | login | 403 | 403 | 403 | 403 | 403 | 403 | ok |
| media-upload | login | → index.php | → index.php | → index.php | → index.php | → index.php | → index.php | ok |
| media-edit-hero | login | → index.php | → index.php | → index.php | → index.php | → index.php | → index.php | ok |
| rest-blocks-create | 401 | 403 | 403 | 403 | 403 | 403 | 403 | ok |

### Baseline findings that are gone (1 distinct)

- error `policy` `/wp-admin/edit.php?post_type=page` (core-editor): &lt;role> offers Trash on the fixed pages (2 × .row-actions .trash a)

### Findings left in the final crawl (0 errors, 17 distinct warnings)

| Severity | Type | Where | Finding | Why it is left |
|---|---|---|---|---|
| warning | `a11y-h1` | `/wp-login.php` (anonymous) | 2 &lt;h1> elements: "Log In", "Powered by WordPress" | WordPress core sign-in markup (the "Powered by WordPress" logo is an h1, and there is no &lt;main>); same in the baseline, not site code. |
| warning | `a11y-h1` | `/wp-login.php?action=lostpassword` (anonymous) | 2 &lt;h1> elements: "Lost Password", "Powered by WordPress" | WordPress core sign-in markup (the "Powered by WordPress" logo is an h1, and there is no &lt;main>); same in the baseline, not site code. |
| warning | `a11y-landmark` | 2 URLs, e.g. `/wp-login.php` (anonymous) | no &lt;main> landmark | WordPress core sign-in markup (the "Powered by WordPress" logo is an h1, and there is no &lt;main>); same in the baseline, not site code. |
| warning | `editor-notice` | 9 URLs, e.g. `/wp-admin/site-editor.php` (admin) | editor warning notice: Changes saved here override the theme’s own files, so later theme updates stop showing on the site. Change the theme in its files instead, and leave this screen unsaved. | Intentional: the QA-091 warning on every Site Editor screen. |
| warning | `network` | `/wp-admin/options-connectors.php` (admin) | HTTP 404 for /wp-json/wp/v2/plugins/ai*… (4 requests) | WordPress 7.1.2 core: Settings › Connectors asks REST for AI plugins that aren't installed; same in the baseline, not site code. |

Info-level notes (not problems): denial-status 14, rest-empty 1, a11y-duplicate-id 18, editor-ui 16, a11y-label 2. They are WordPress core behaviour, the same as in the baseline: the admin's first block-editor visit opens the Welcome Guide; core's meta-box markup repeats two ids; core answers some refusals (another user's profile, Widgets, Menus) with HTTP 500 instead of 403; a subscriber's REST navigation list is empty; the plupload file input has no label.

## Visual review of the final screenshots

I looked at the final screenshots on contact sheets: every public page and admin screen each role can reach (public pages at 1440 and 390), and the forbidden probes of the visitor, subscriber, core Editor and ARES Editor. I opened the ones below at full size, next to the baseline where one existed. Public pages at 1440 are unchanged from the baseline apart from a ~3px shift in the Winlink table's column widths (QA-004) and the darker search placeholder (QA-106). No screen was blank, broken or overflowing; forbidden probes end on a plain refusal page or the Dashboard. The admin block editor still opens with WordPress's Welcome Guide on a fresh site (info, same as the baseline).

| Issue | What the final screenshot shows | Screenshot |
|---|---|---|
| QA-001 | The core Editor's Pages list has no Bulk actions menu and no row Trash; the policy error from the baseline is gone. | [core-editor/pages-desktop.png](shots/final/core-editor/pages-desktop.png) |
| QA-030 | The Home hero at 390 has the darker overlay; the lede reads clearly over the sky. | [anonymous/home-mobile-390.png](shots/final/anonymous/home-mobile-390.png) |
| QA-032 | Appearance offers Themes and Editor only; Fonts is gone. | [admin/themes-desktop.png](shots/final/admin/themes-desktop.png) |
| QA-034, QA-093 | Regular meetings lists the Second Saturday Workshop and Third Thursday only, with "Not listed: Winlink workshop…" under the table. | [ares-editor/meetings-desktop.png](shots/final/ares-editor/meetings-desktop.png) |
| QA-036 | Library sections has a Position field and column. | [admin/doc-sections-desktop.png](shots/final/admin/doc-sections-desktop.png) |
| QA-041, QA-047 | Signed-in admin bars show a plain person icon (no Gravatar) and, for non-admins, no Search box. | [ares-editor/members-desktop.png](shots/final/ares-editor/members-desktop.png) |
| QA-049 | Non-admin Profile screens start at Name: no Personal Options heading and no Infinite Scrolling row. | [ares-editor/profile-desktop.png](shots/final/ares-editor/profile-desktop.png) |
| QA-050, QA-088 | The admin Dashboard has no Welcome panel; Needs attention links to Events, Documents, Net details, Meeting rules and ARES site settings with counts. | [admin/dashboard-desktop.png](shots/final/admin/dashboard-desktop.png) |
| QA-013 | Add User preselects ARES Editor. | [admin/user-new-desktop.png](shots/final/admin/user-new-desktop.png) |
| QA-072 | Users has a Net details column ("Yes (administrator)", "Yes", "—"). | [admin/users-desktop.png](shots/final/admin/users-desktop.png) |
| QA-076, QA-078 | Subscriber, Contributor and Author get the "Your account" widget, no Screen Options and no command palette. | [subscriber/dashboard-desktop.png](shots/final/subscriber/dashboard-desktop.png) |
| QA-080 | The core Editor's only Site task is numbered 1. | [core-editor/dashboard-desktop.png](shots/final/core-editor/dashboard-desktop.png) |
| QA-091 | Every Site Editor screen shows the "Changes saved here override the theme's own files" warning (reported by the crawler as an editor warning notice, by design). | [admin/site-editor-desktop.png](shots/final/admin/site-editor-desktop.png) |
| QA-096 | Under the net bar on /members/ the grant holder sees "Edit these settings in Net details", set apart from the rota table. | [ares-net/members-desktop.png](shots/final/ares-net/members-desktop.png) |
| QA-098 | Search shows "Search results for: “net”", a filled search box, a no-results sentence and the library link. | [anonymous/search-none-mobile-390.png](shots/final/anonymous/search-none-mobile-390.png) |
| QA-067 | On Home at 390 the "Optional" tag sits beside "Qualify for ACS" inside the column. | [anonymous/home-mobile-390.png](shots/final/anonymous/home-mobile-390.png) |
| QA-108 | The Most used tiles are Net script, ICS-213, ICS-214 and ACS Task Book. | [anonymous/members-desktop.png](shots/final/anonymous/members-desktop.png) |

Behaviour a screenshot can't show (keyboard focus, contrast measurements, long pasted links, menus after a resize, saves and refusals) is covered by the regression tests below, all of which pass.

## Fixed during the final verification

- **Invalid slug pattern (side finding of QA-021 and QA-053).** The document form's Link name field had `pattern="[a-z0-9-]*"`, which browsers compile with the `v` flag and reject ("Invalid character class"), so the field had no browser check and logged a console error. Now `[a-z0-9\-]*` (`plugins/spokares-core/inc/admin-documents.php`). New test `dev/tests/e2e/qa-cross-pattern-attributes.test.mjs` (2 tests): it failed on the old code and passes now.
- **QA-096 spacing.** The settings link under the hub's net bar sat flush on the rota table header. `site.css` now sets it 12px under the bar and 20px above the table. Checked in the re-crawled screenshot.
- **QA-076 spacing.** The "Your account" widget's "Go to the site" link touched its button. It now gets the same 12px gap as the Site tasks links (`dashboard.php`, `admin.css`).
- **Docs.** PLAN.md §6.7 described the old templates and header offset. It now covers `customTemplates`, the plain `page.html`, the How it works and About slug templates, `search.html` and the scroll-padding offset (QA-031, QA-029, QA-098, QA-102, QA-105). `dev/tests/README.md` lists the qa-* tests and the final run.


## Issues by kind, severity and status

| Kind | critical | high | medium | low | fixed | needs-decision | by-design | deferred | not-reproduced | Total |
|---|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|
| bug | 0 | 4 | 28 | 29 | 61 | 0 | 0 | 0 | 0 | **61** |
| quality | 0 | 0 | 5 | 43 | 48 | 0 | 0 | 0 | 0 | **48** |
| needs-decision | 0 | 0 | 2 | 0 | 2 | 0 | 0 | 0 | 0 | **2** |
| by-design | 0 | 0 | 0 | 2 | 0 | 0 | 2 | 0 | 0 | **2** |
| **Total** | 0 | 4 | 35 | 74 | 111 | 0 | 2 | 0 | 0 | **113** |

Full detail per issue (repro, expected, actual, evidence, triage and resolution): `qa/ISSUES.md`; machine-readable: `qa/issues.json`.

| ID | Sev. | Kind | Status | Title | Regression test(s) |
|---|---|---|---|---|---|
| QA-001 | high | bug | fixed | Core Editor can permanently delete, or 'trash', How it works and About; trash renames the slug to &lt;slug>__trashed and 404s the page site-wide | `qa-001-fixed-page-delete-test.php`<br>`qa-security-governance-test.php`<br>`qa-security-editor-surface.test.mjs` |
| QA-002 | high | bug | fixed | The Net details grant survives demotion or role removal, is offered on any core-role profile, and its holder then escapes the two-factor floor | `qa-002-net-grant-demotion-test.php`<br>`qa-security-governance-test.php` |
| QA-003 | high | bug | fixed | A held-back end time or last day on a published event still saves the new start, so the live event shows a half-applied change ('1:00 PM–noon') | `qa-003-held-pair-test.php` |
| QA-004 | high | bug | fixed | A long unbreakable word (a pasted link) in an event's short line or place, or a document note, breaks the Exercises and Documents layouts | `qa-004-long-words.test.mjs` |
| QA-005 | medium | bug | fixed | Dev E2E and crawler click helper misfires on public pages because the theme scrolls smoothly | `qa-005-smooth-scroll-click.test.mjs` |
| QA-006 | medium | bug | fixed | Lost-password form reveals whether an account exists when the reset e-mail can't be sent | `qa-006-lostpassword-enumeration-test.php` |
| QA-007 | medium | bug | fixed | Signed-in wp-admin, admin-ajax and admin-post responses carry only 'frame-ancestors': core replaces the hardening Content-Security-Policy | `qa-007-admin-csp.test.mjs` |
| QA-008 | medium | bug | fixed | Unknown URLs with three or more path segments get a permanent 301 to Home instead of a 404 | `qa-008-deep-unknown-404.test.mjs` |
| QA-009 | medium | bug | fixed | Draft documents' file addresses are listed to Authors and core Editors through admin-ajax query-attachments and get-attachment | `qa-009-draft-file-ajax-test.php` |
| QA-010 | medium | bug | fixed | Non-admins can create and publish synced patterns (wp_block) and open the Patterns screens | `qa-010-synced-patterns-test.php`<br>`qa-security-editor-surface.test.mjs` |
| QA-011 | medium | bug | fixed | Core Editor can edit, crop or rotate, and delete other users' Media Library files, including the Home hero photo | `qa-011-others-media-test.php` |
| QA-012 | medium | bug | fixed | GPS and camera EXIF are kept in uploaded WebP and PNG originals (only JPEG is stripped) | `qa-012-exif-webp-png-test.php` |
| QA-013 | medium | bug | fixed | Add User offers WordPress's Editor, Author, Contributor and Subscriber roles, and defaults to Subscriber | `qa-013-offered-roles-test.php`<br>`qa-security-governance-test.php` |
| QA-014 | medium | bug | fixed | An empty list item can be saved into Home's timeline (or any lead-in list), adding a blank stop to the public page | `qa-014-empty-list-item-test.php`<br>`qa-security-governance-test.php` |
| QA-015 | medium | bug | fixed | Net details accepts non-amateur frequencies (hospital, fire, GMRS) although the banner and error say 'amateur only' | `qa-015-amateur-frequencies-test.php` |
| QA-016 | medium | bug | fixed | Switching a document from 'Upload a file' to 'Soon' or 'Link' leaves the old file public, with no notice | `qa-016-source-switch-file-test.php` |
| QA-017 | medium | bug | fixed | Choosing a refused file type under 'Replace with' unpublishes the whole document | `qa-017-refused-replacement-test.php` |
| QA-018 | medium | bug | fixed | 'Undo' after Move to Trash (with 'Also remove its file') republishes the document without its file | `qa-018-undo-trash-file-test.php` |
| QA-019 | medium | bug | fixed | Event 'Where' field: the 'This is a public agency number. Publish it.' tick is never remembered | `qa-019-where-confirm-test.php`<br>`prior-event-where-test.php` |
| QA-020 | medium | bug | fixed | Saving an event drops its 'Extra form' when that document is briefly unpublished | `qa-020-extra-form-draft-doc-test.php` |
| QA-021 | medium | bug | fixed | Documents: the administrator's 'Link name (slug)' field is silently ignored | `qa-021-document-slug.test.mjs`<br>`qa-cross-pattern-attributes.test.mjs` |
| QA-022 | medium | bug | fixed | 'Save draft' and 'Save as draft (takes it off the site)' say 'Saved. See it on Exercises & events' and link to something not on the site | `qa-022-save-draft-notice-test.php` |
| QA-023 | medium | bug | fixed | Documents list: sorting by 'Reviewed' hides every document that has never been reviewed | `qa-023-reviewed-sort-test.php` |
| QA-024 | medium | bug | fixed | Regular meetings: 'Moved to' accepts a past date or another scheduled date, and the meeting then disappears from Home | `qa-024-meeting-moved-date-test.php` |
| QA-025 | medium | bug | fixed | Net rota: the 'This is a public agency number. Publish it.' tick in the Note column is drawn as a wide bar, not a checkbox | `qa-025-rota-confirm-checkbox.test.mjs` |
| QA-026 | medium | bug | fixed | Net rota: arrowing onto 'Call sign' pulls focus out of the radio group into the text box | `qa-026-rota-arrow-keys.test.mjs` |
| QA-027 | medium | bug | fixed | Meeting rules, Event and Document edit forms run past the right edge on phones (fieldset min-content), cutting off fields and month boxes | `qa-027-phone-form-width.test.mjs` |
| QA-028 | medium | bug | fixed | Hub tiles and Regular meetings don't reflow on phones, and Net rota overflows between 783 and about 1100px | `qa-028-admin-table-reflow.test.mjs` |
| QA-029 | medium | bug | fixed | The sticky header hides focused links on Shift+Tab and the About 'On this page' target (no scroll padding for the masthead) | `qa-029-sticky-header-focus.test.mjs` |
| QA-030 | medium | bug | fixed | Home hero intro text falls below 4.5:1 contrast over the photo at phone and tablet widths | `qa-030-hero-lede-contrast.test.mjs` |
| QA-031 | medium | bug | fixed | A page an administrator adds (e.g. the Privacy Policy) renders with no H1, no content column, and text flush to the window edge | `qa-031-plain-page.test.mjs`<br>`qa-theme-templates-test.php` |
| QA-032 | medium | bug | fixed | Appearance › Fonts is offered to administrators, but every font upload or install fails ('not allowed to upload this file type') | `qa-032-font-library-off.test.mjs` |
| QA-033 | medium | quality | fixed | The 'Next up' card prints the event's list fragment as a sentence and repeats the title as a link (Great ShakeOut card, from Sep 28) | `qa-front-033-next-up-card-test.php` |
| QA-034 | medium | quality | fixed | Regular meetings offers Winlink workshop dates, but cancelling or moving one changes nothing on the site | `qa-admin-quality-test.php` |
| QA-035 | medium | quality | fixed | 'Pull this file now' permanently deletes the file with one click and no confirmation | `qa-admin-quality-test.php`<br>`qa-admin-quality.test.mjs` |
| QA-036 | medium | quality | fixed | Library sections: a new section jumps to the top, deleting one silently hides its documents, and slugs change without warning | `qa-admin-quality-test.php` |
| QA-037 | medium | quality | fixed | Admin live previews chatter to screen readers: the Net details preview and the rota 'Shows' cells re-announce on every keystroke | `qa-admin-quality.test.mjs` |
| QA-038 | medium | needs-decision | fixed | Settings › ARES site: the groups.io 'Main group' and 'Member files' addresses are not used anywhere | `qa-038-groupsio-settings-removed-test.php`<br>`qa-038-groupsio-settings-removed.test.mjs` |
| QA-039 | medium | needs-decision | fixed | Changing the repeater call sign in Net details leaves 'W7GBU' in How it works page text and the message-path diagram | `qa-039-repeater-call-admin-only-test.php`<br>`qa-039-repeater-call-admin-only.test.mjs` |
| QA-040 | low | bug | fixed | Signed-in users without list_users can list accounts over REST, including the administrator's login slug | `qa-040-rest-users-enumeration-test.php`<br>`qa-security-editor-surface.test.mjs` |
| QA-041 | low | bug | fixed | Every signed-in page sends a hash of the user's e-mail address to Gravatar | `qa-041-gravatar-test.php` |
| QA-042 | low | bug | fixed | Any edit_posts user can create post tags and pattern categories over REST; the core Editor can also manage categories and tags | `qa-042-term-creation-test.php`<br>`qa-security-editor-surface.test.mjs` |
| QA-043 | low | bug | fixed | The remote Pattern Directory is still reachable over REST (the server fetches wordpress.org patterns on request) | `qa-043-remote-pattern-directory-test.php`<br>`qa-security-editor-surface.test.mjs` |
| QA-044 | low | bug | fixed | The file-deletion guard doesn't count trashed documents, so a restored document can point at a deleted file | `qa-044-trashed-doc-file-guard-test.php` |
| QA-045 | low | bug | fixed | Paginated URLs of the single pages return 200 duplicates, and /page/2/ has a canonical pointing at a 404 | `qa-045-paged-singular-404.test.mjs` |
| QA-046 | low | bug | fixed | Block editor page card offers 'Order' to editors; it says 'Order updated.' but the server discards it | `qa-046-page-card-order.test.mjs`<br>`qa-security-editor-surface.test.mjs` |
| QA-047 | low | bug | fixed | Admin-bar Search is never removed for non-admins: the removal runs before core adds the node | `qa-047-admin-bar-search.test.mjs` |
| QA-048 | low | bug | fixed | Own profile via user-edit.php?user_id=&lt;self> skips the profile trim, and the hidden Website and Biography fields are still saved | `qa-048-own-profile-trim-test.php` |
| QA-049 | low | bug | fixed | Profile trim is incomplete: an empty 'Personal Options' heading, or one Media Library 'Infinite Scrolling' row, for non-admins | `qa-049-profile-trim.test.mjs` |
| QA-050 | low | bug | fixed | The Dashboard 'Welcome to WordPress!' panel still shows: the remove_action runs before core adds it | `qa-050-welcome-panel-test.php` |
| QA-051 | low | bug | fixed | Event and document text fields lose their backslashes on save (double unslash) | `qa-051-backslash-fields-test.php` |
| QA-052 | low | bug | fixed | An event end time with no start time is refused, but the form comes back with All day ticked and the time boxes hidden | `qa-052-end-time-no-start-test.php` |
| QA-053 | low | bug | fixed | After saving an event or document whose name contains '&', the name box shows '&amp;' | `qa-053-ampersand-name-box-test.php` |
| QA-054 | low | bug | fixed | Save handlers don't enforce the forms' length limits or reject invalid times; bad values save under 'Saved.' | `qa-054-length-limits-times-test.php` |
| QA-055 | low | bug | fixed | Net rota: at 52 Tuesdays the link still says 'Show 13 more' and reloads the same page | `qa-055-rota-more-cap-test.php` |
| QA-056 | low | bug | fixed | A confirmed Winlink sentence that contains a line break is flagged again on every later save | `qa-056-multiline-winlink-confirm-test.php` |
| QA-057 | low | bug | fixed | The Net details preview doesn't match the site: ', ,' with no GMRS time, a refused call sign shown, the alternate line's wording, and empty bullets | `qa-057-net-preview-test.php`<br>`qa-057-net-preview.test.mjs` |
| QA-058 | low | bug | fixed | Meeting rules accepts an End time with no Start, and the meeting's time then disappears from Home and the hub | `qa-058-meeting-end-no-start-test.php` |
| QA-059 | low | bug | fixed | Meeting rules and Net details silently overwrite someone else's newer save | `qa-059-stale-settings-save-test.php` |
| QA-060 | low | bug | fixed | Events list 'When' column invents a day and weekday for month- or year-precision past events | `qa-060-events-when-precision-test.php` |
| QA-061 | low | bug | fixed | The hub's 'Next meeting' line uses the recurring 'evenings' wording for a single date | `qa-061-hub-meeting-singular-test.php` |
| QA-062 | low | bug | fixed | 'Later this season' rows print ':.' or '..' when the short line already ends in punctuation and the event has a More link | `qa-062-later-rest-punctuation-test.php` |
| QA-063 | low | bug | fixed | 'Later this season' silently drops upcoming events beyond 12 | `qa-063-later-overflow-test.php` |
| QA-064 | low | bug | fixed | Changing the Winlink weeks leaves old assignments published on Tuesdays that are no longer Winlink nights | `qa-064-stale-winlink-weeks-test.php` |
| QA-065 | low | bug | fixed | A week ticked in two net groups is advertised under both in 'Other nets', but the rota shows only one | `qa-065-other-nets-precedence-test.php` |
| QA-066 | low | bug | fixed | The phone menu, opened below 1020px, stays open after the window widens: Close overlaps 'Join the team' and the page stays scroll-locked | `qa-066-menu-resize.test.mjs` |
| QA-067 | low | bug | fixed | Nowrap buttons, tags and spans are cut off at 320px, with WCAG text spacing, or at a 200% default text size | `qa-067-nowrap-reflow.test.mjs` |
| QA-068 | low | bug | fixed | Crawler and CDP helper: a fixed 10 s DevTools connect timeout, a role that fails to start still exits 0, and a browser that dies mid-run turns every later URL into a false error | `qa-068-crawler-resilience.test.mjs` |
| QA-069 | low | quality | fixed | Deleting a file that a page or document uses ends in a bare 'Error in deleting the attachment.' (HTTP 500 page, alert, or REST 500) | `qa-security-governance-test.php` |
| QA-070 | low | quality | fixed | The core Editor's unfiltered_html is removed only by the DISALLOW_UNFILTERED_HTML constant | `qa-security-governance-test.php` |
| QA-071 | low | quality | fixed | Links in page text aren't checked: a 'javascript:' button link saves as a broken relative link, and http:// links are accepted without a warning | – |
| QA-072 | low | quality | fixed | No way for an administrator to see who holds the Net details grant | `qa-security-governance-test.php` |
| QA-073 | low | quality | fixed | Forced two-factor setup redirects without saying why, and the profile page throws a JS exception from a refused REST call | – |
| QA-074 | low | quality | fixed | Media Library 'View' and attachment-page links go to 404 | `qa-security-governance-test.php` |
| QA-075 | low | quality | fixed | The fail-closed notice tells administrators, who are the webmaster, to 'Contact the webmaster' | – |
| QA-076 | low | quality | fixed | Users with no site tasks (Subscriber, Contributor, Author) land on a blank Dashboard that says 'Add boxes from the Screen Options menu', which has no boxes | `qa-admin-quality-test.php` |
| QA-077 | low | quality | fixed | The Profile screen's Help tab describes options that are hidden for non-admins | – |
| QA-078 | low | quality | fixed | The command palette in a Subscriber's toolbar offers nothing useful and fires failing REST requests on every keystroke | `qa-admin-quality-test.php` |
| QA-079 | low | quality | fixed | The Edit Media screen and the legacy media-upload.php library open for non-admins, with dead-end links | `qa-admin-quality-test.php` |
| QA-080 | low | quality | fixed | Dashboard 'Site tasks' numbers are hard-coded, so the core Editor sees a single task labelled '5' | `qa-admin-quality-test.php` |
| QA-081 | low | quality | fixed | Document upload refused for a missing Privacy tick: the text under the file chooser says 'Choose the file to upload.' although a file was chosen | `qa-admin-quality-test.php` |
| QA-082 | low | quality | fixed | Meeting rules error state: the card's title goes blank, the notice doesn't name the meeting, and the card is striped pink instead of outlined | – |
| QA-083 | low | quality | fixed | Net details frequency fields are too narrow on phones: '146.880' shows as '146.88' | – |
| QA-084 | low | quality | fixed | Holders of the Net details grant are told to 'ask the webmaster' for Meeting rules and get no link to it | `qa-admin-quality-test.php` |
| QA-085 | low | quality | fixed | Net details: the GMRS time field's only label is 'at', and the format hints aren't linked to their fields | `qa-admin-quality-test.php` |
| QA-086 | low | quality | fixed | Events list search only looks inside the current view, so past events and drafts show 'No events found.' | `qa-admin-quality-test.php` |
| QA-087 | low | quality | fixed | Restore from Trash turns an event into a Draft that the default list views don't show, and the message doesn't say so | `qa-admin-quality-test.php` |
| QA-088 | low | quality | fixed | The Dashboard's 'N items marked Needs checking' line gives no way to find the items | `qa-admin-quality-test.php` |
| QA-089 | low | quality | fixed | Page review box: stale after a block-editor Save (the tick stays on), and a refused Page owner is dropped silently | `qa-admin-quality-test.php` |
| QA-090 | low | quality | fixed | Document trash controls: 'Mark reviewed today' is offered in the Trash view, and 'Also remove its file' shows for documents with no file | `qa-admin-quality-test.php` |
| QA-091 | low | quality | fixed | The Site Editor lets admins save template, part and style overrides without a warning, and opening Navigation creates a stray published menu | `qa-admin-quality-test.php` |
| QA-092 | low | quality | fixed | Plugin form errors aren't tied to their fields (no aria-invalid or aria-describedby), and focus starts on &lt;body> | `qa-admin-quality.test.mjs` |
| QA-093 | low | quality | fixed | Regular meetings table: empty row headers for the second and later dates of each meeting | `qa-admin-quality-test.php` |
| QA-094 | low | quality | fixed | /docs/&lt;slug>/ links are case-sensitive: /docs/ICS-213/ is a 404 | `qa-front-094-docs-any-case.test.mjs` |
| QA-095 | low | quality | fixed | With no Winlink, Simplex or GMRS weeks, How it works shows an empty 'Other nets' section | `qa-front-095-096-net-tails-test.php` |
| QA-096 | low | quality | fixed | On /members/, grant holders see 'Edit this list in Net details' flush above the rota table, as if it edits the rota | `qa-front-095-096-net-tails-test.php` |
| QA-097 | low | quality | fixed | The copy-confirmation toast creates its live region with the first message, so the first 'Copied' may not be announced | `qa-front-097-toast-region.test.mjs` |
| QA-098 | low | quality | fixed | The search results page never shows the query, and an empty result doesn't say nothing matched | `qa-theme-templates-test.php` |
| QA-099 | low | quality | fixed | Without JavaScript the phone 'Menu' button is shown but does nothing | – |
| QA-100 | low | quality | fixed | Blocks an administrator adds at the root of Home, How it works or About render full-bleed with no side gutter | – |
| QA-101 | low | quality | fixed | Footer links run together in every editor canvas (Site Editor and pages edited with the template shown) | – |
| QA-102 | low | quality | fixed | The three members templates show raw slugs ('page-members', 'page-documents', 'page-exercises') with no description | `qa-theme-templates-test.php` |
| QA-103 | low | quality | fixed | Header focus order below 1020px doesn't match the visual order (Menu is reached before 'Join the team') | `qa-103-header-order-test.php`<br>`qa-103-header-order.test.mjs` |
| QA-104 | low | quality | fixed | Members hub on phones: 'Tuesday net' is moved first with CSS order, so tab and heading order jump back and forth | `qa-104-hub-order-test.php`<br>`qa-104-hub-order.test.mjs` |
| QA-105 | low | quality | fixed | The About 'On this page' floating button covers content and part of focused links near the bottom of the screen | – |
| QA-106 | low | quality | fixed | Search-field placeholder text is 3.7:1 contrast | – |
| QA-107 | low | quality | fixed | A page's excerpt (its meta description) isn't checked for phone numbers or never-publish words | `qa-107-excerpt-check-test.php`<br>`qa-107-excerpt-warning.test.mjs` |
| QA-108 | low | quality | fixed | The library's 'Most used' filter disagrees with the hub's 'Most used' tiles (ICS-309 'Soon' listed, ICS-214 missing) | – |
| QA-109 | low | quality | fixed | Crawler coverage gaps: overflow inside a column isn't detected, Draft events count as missing anchors, and several reachable screens and write probes aren't in the access matrix | – |
| QA-110 | low | quality | fixed | Ops checks miss three things: the full CSP on /wp-admin/, database overrides of templates and styles, and the DISALLOW_UNFILTERED_HTML constant (plus the php.ini upload cap) | – |
| QA-111 | low | quality | fixed | Docs out of date: build-notes claims the editor's Order and Trash are hidden, and EDITING-GUIDE has no webmaster recipe for adding a section or page | – |
| QA-112 | low | by-design | by-design | The 32 MB upload cap isn't enforced in code: a 40 MB file is accepted and stored in dev | – |
| QA-113 | low | by-design | by-design | Administrators are still offered Posts, Categories, Tags, Comments and '+ New › Post', and a published post goes to a 404 | – |

## Needs a decision

None. The four items that waited on a decision were decided by the owner on 2026-09-27 and are fixed (next section).

## Owner decisions (2026-09-27)

Each is fixed as the owner decided, with regression tests that failed before the change and pass now (for QA-103 and QA-104 they check DOM and focus order, which every browser uses, and that no CSS reorders the header or the hub).

- **QA-038** (medium, plugin-admin): Settings › ARES site: the groups.io 'Main group' and 'Member files' addresses are not used anywhere. Fixed per the owner's decision (2026-09-27): remove the fields. Settings › ARES site holds only the meeting place: the groups.io form section, its save and sanitising, the spk_site defaults and the dev seed's groups.io keys are gone (admin-site.php, data.php, helpers.php, dev/seed/import.php), and an older stored value's groupsio_main and groupsio_files are ignored on read and dropped on the next save. The screen's help says the groups.io links are page text and the theme's footer and members menu. groups.io links in page text are unchanged. Tests: `dev/tests/php/qa-038-groupsio-settings-removed-test.php`, `dev/tests/e2e/qa-038-groupsio-settings-removed.test.mjs`.
- **QA-039** (medium, theme): Changing the repeater call sign in Net details leaves 'W7GBU' in How it works page text and the message-path diagram. Fixed per the owner's decision (2026-09-27): W7GBU is fixed (the club's call), and the Net details call-sign field is administrators only. A user with only the Net details grant sees the call sign read-only and not posted, with the hint 'The club's own call sign. Only an administrator can change it.' The save handler (admin-net.php) never updates the call for a non-administrator, and refuses a posted different one with 'The repeater call sign wasn't saved: only an administrator can change it.' while the other fields still save. Administrators can still change it. The How it works page text and message-path picture keep W7GBU. The two qa-057 refused-call-sign tests now sign in as an administrator, the only role that can still type a call sign; their assertions are unchanged. Tests: `dev/tests/php/qa-039-repeater-call-admin-only-test.php`, `dev/tests/e2e/qa-039-repeater-call-admin-only.test.mjs`.
- **QA-103** (low, theme): Header focus order below 1020px doesn't match the visual order (Menu is reached before 'Join the team'). Fixed per the owner's decision (2026-09-27), in the markup for every browser. parts/header.html reads brand, a 'Join the team' button (Buttons masthead__actions), then the Navigation, whose four links are followed by a second 'Join the team' (Buttons site-header__actions). site.css no longer uses order or reading-flow. From 1020px the bar's own Join is not drawn, so the desktop header looks as before (0 differing pixels at 1024 and 1440). Below 1020px the bar reads brand, Join, Menu, and the open phone menu ends with its own Join. header.js closes the open menu when a same-page link (Join on Home) is chosen. DOM order equals drawn order and Tab order from 360 to 1440px, with the menu open, and without JavaScript. Tests: `dev/tests/php/qa-103-header-order-test.php`, `dev/tests/e2e/qa-103-header-order.test.mjs`.
- **QA-104** (low, theme): Members hub on phones: 'Tuesday net' is moved first with CSS order, so tab and heading order jump back and forth. Fixed per the owner's decision (2026-09-27), in the markup at every width. patterns/members-hub.php puts section.hub-net (Tuesday net) first in .hub-grid, before This week; the CSS order and reading-flow are gone. Phones look as before (0 differing pixels at 390 and 768). On desktop the two columns are mirrored, with the same section sizes: Tuesday net on the left (7fr), This week on the right (5fr). Headings read For members, Tuesday net, This week, Exercises, Most used, and Tab from the search goes into the net first. Tests: `dev/tests/php/qa-104-hub-order-test.php`, `dev/tests/e2e/qa-104-hub-order.test.mjs`.

Screenshots from the 0.1.1 verification (port 9533): [qa038-ares-site-admin](shots/decisions-0.1.1/qa038-ares-site-admin.png) · [qa039-net-details-admin](shots/decisions-0.1.1/qa039-net-details-admin.png) · [qa039-net-details-ares-net-tampered-save](shots/decisions-0.1.1/qa039-net-details-ares-net-tampered-save.png) · [qa039-net-details-ares-net](shots/decisions-0.1.1/qa039-net-details-ares-net.png) · [qa103-header-1440](shots/decisions-0.1.1/qa103-header-1440.png) · [qa103-header-390-menu-open](shots/decisions-0.1.1/qa103-header-390-menu-open.png) · [qa103-header-390](shots/decisions-0.1.1/qa103-header-390.png) · [qa104-members-1440](shots/decisions-0.1.1/qa104-members-1440.png) · [qa104-members-390](shots/decisions-0.1.1/qa104-members-390.png).

## Test suite

| Runner | Passed | Total |
|---|---:|---:|
| `dev/checks.sh` (HTTP checks) | 50 | 50 |
| `dev/tests/run-php.sh` (PHP integration) | 308 | 308 |
| `dev/tests/run-e2e.mjs` (browser) | 143 | 143 |
| `ops/ci.sh --no-zip` (release gate) | 11 | 11 |

Run with `wordpress/dev/tests/run-all.sh <PORT>` on a fresh `dev/start.sh` site (these counts: port 9533, release 0.1.1, 2026-09-27). Test files: 98 (59 PHP, 39 e2e).

| Runner | Test file | Tests | Covers |
|---|---|---:|---|
| php | `accounts-test.php` | 2/2 | the dev accounts and the Net details grant |
| php | `framework-test.php` | 4/4 | the PHP test framework itself |
| php | `prior-event-where-test.php` | 6/6 | Fixer-round bug 7 (Event "Where" field and save notice); QA-019 |
| php | `prior-orphan-files-test.php` | 4/4 | Fixer-round bug 8 (Orphaned document files reachable by ID) |
| php | `prior-page-guard-test.php` | 4/4 | Fixer-round bug 1 (Page password, date, author and status) |
| php | `prior-page-template-test.php` | 6/6 | Fixer-round bug 2 (Page template) |
| php | `prior-quick-edit-test.php` | 6/6 | Fixer-round bug 3 (Quick Edit / bulk Edit on Pages) |
| php | `prior-save-box-test.php` | 3/3 | Fixer-round bug 4 (Save box hidden via Screen Options) |
| php | `qa-001-fixed-page-delete-test.php` | 6/6 | QA-001 |
| php | `qa-002-net-grant-demotion-test.php` | 11/11 | QA-002 |
| php | `qa-003-held-pair-test.php` | 4/4 | QA-003 |
| php | `qa-006-lostpassword-enumeration-test.php` | 4/4 | QA-006 |
| php | `qa-009-draft-file-ajax-test.php` | 4/4 | QA-009 |
| php | `qa-010-synced-patterns-test.php` | 5/5 | QA-010 |
| php | `qa-011-others-media-test.php` | 9/9 | QA-011 |
| php | `qa-012-exif-webp-png-test.php` | 6/6 | QA-012 |
| php | `qa-013-offered-roles-test.php` | 7/7 | QA-013 |
| php | `qa-014-empty-list-item-test.php` | 7/7 | QA-014 |
| php | `qa-015-amateur-frequencies-test.php` | 6/6 | QA-015 |
| php | `qa-016-source-switch-file-test.php` | 3/3 | QA-016 |
| php | `qa-017-refused-replacement-test.php` | 4/4 | QA-017 |
| php | `qa-018-undo-trash-file-test.php` | 3/3 | QA-018 |
| php | `qa-019-where-confirm-test.php` | 3/3 | QA-019 |
| php | `qa-020-extra-form-draft-doc-test.php` | 2/2 | QA-020 |
| php | `qa-022-save-draft-notice-test.php` | 5/5 | QA-022 |
| php | `qa-023-reviewed-sort-test.php` | 3/3 | QA-023 |
| php | `qa-024-meeting-moved-date-test.php` | 6/6 | QA-024 |
| php | `qa-038-groupsio-settings-removed-test.php` | 8/8 | QA-038 |
| php | `qa-039-repeater-call-admin-only-test.php` | 11/11 | QA-039 |
| php | `qa-040-rest-users-enumeration-test.php` | 5/5 | QA-040 |
| php | `qa-041-gravatar-test.php` | 3/3 | QA-041 |
| php | `qa-042-term-creation-test.php` | 8/8 | QA-042 |
| php | `qa-043-remote-pattern-directory-test.php` | 3/3 | QA-043 |
| php | `qa-044-trashed-doc-file-guard-test.php` | 6/6 | QA-044 |
| php | `qa-048-own-profile-trim-test.php` | 6/6 | QA-048 |
| php | `qa-050-welcome-panel-test.php` | 2/2 | QA-050 |
| php | `qa-051-backslash-fields-test.php` | 3/3 | QA-051 |
| php | `qa-052-end-time-no-start-test.php` | 6/6 | QA-052 |
| php | `qa-053-ampersand-name-box-test.php` | 3/3 | QA-053 |
| php | `qa-054-length-limits-times-test.php` | 12/12 | QA-054 |
| php | `qa-055-rota-more-cap-test.php` | 4/4 | QA-055 |
| php | `qa-056-multiline-winlink-confirm-test.php` | 3/3 | QA-056 |
| php | `qa-057-net-preview-test.php` | 6/6 | QA-057 |
| php | `qa-058-meeting-end-no-start-test.php` | 6/6 | QA-058 |
| php | `qa-059-stale-settings-save-test.php` | 5/5 | QA-059 |
| php | `qa-060-events-when-precision-test.php` | 6/6 | QA-060 |
| php | `qa-061-hub-meeting-singular-test.php` | 4/4 | QA-061 |
| php | `qa-062-later-rest-punctuation-test.php` | 6/6 | QA-062 |
| php | `qa-063-later-overflow-test.php` | 4/4 | QA-063 |
| php | `qa-064-stale-winlink-weeks-test.php` | 2/2 | QA-064 |
| php | `qa-065-other-nets-precedence-test.php` | 5/5 | QA-065 |
| php | `qa-103-header-order-test.php` | 3/3 | QA-103 |
| php | `qa-104-hub-order-test.php` | 2/2 | QA-104 |
| php | `qa-107-excerpt-check-test.php` | 4/4 | QA-107 |
| php | `qa-admin-quality-test.php` | 14/14 | QA-034, QA-035, QA-036, QA-076, QA-078, QA-079, QA-080, QA-081, QA-084, QA-085, QA-086, QA-087, QA-088, QA-089, QA-090, QA-091, QA-093 |
| php | `qa-front-033-next-up-card-test.php` | 6/6 | QA-033 |
| php | `qa-front-095-096-net-tails-test.php` | 5/5 | QA-095, QA-096 |
| php | `qa-security-governance-test.php` | 10/10 | QA-001, QA-002, QA-013, QA-014, QA-069, QA-070, QA-072, QA-074 |
| php | `qa-theme-templates-test.php` | 4/4 | QA-031, QA-098, QA-102 |
| e2e | `prior-ci-gate.test.mjs` | 3/3 | Fixer-round bug 9 (CI release gate false pass) |
| e2e | `prior-event-where.test.mjs` | 1/1 | Fixer-round bug 7 (Event "Where" field and save notice) |
| e2e | `prior-orphan-files.test.mjs` | 1/1 | Fixer-round bug 8 (Orphaned document files reachable by ID) |
| e2e | `prior-paste.test.mjs` | 3/3 | Fixer-round bug 6 (Multi-paragraph paste) |
| e2e | `prior-quick-edit.test.mjs` | 3/3 | Fixer-round bug 3 (Quick Edit / bulk Edit on Pages) |
| e2e | `prior-rota-callsign.test.mjs` | 2/2 | Fixer-round bug 5 (Rota call-sign box dead) |
| e2e | `prior-save-box.test.mjs` | 2/2 | Fixer-round bug 4 (Save box hidden via Screen Options) |
| e2e | `qa-004-long-words.test.mjs` | 3/3 | QA-004 |
| e2e | `qa-005-smooth-scroll-click.test.mjs` | 3/3 | QA-005 |
| e2e | `qa-007-admin-csp.test.mjs` | 4/4 | QA-007 |
| e2e | `qa-008-deep-unknown-404.test.mjs` | 3/3 | QA-008 |
| e2e | `qa-021-document-slug.test.mjs` | 2/2 | QA-021 |
| e2e | `qa-025-rota-confirm-checkbox.test.mjs` | 2/2 | QA-025 |
| e2e | `qa-026-rota-arrow-keys.test.mjs` | 3/3 | QA-026 |
| e2e | `qa-027-phone-form-width.test.mjs` | 5/5 | QA-027 |
| e2e | `qa-028-admin-table-reflow.test.mjs` | 4/4 | QA-028 |
| e2e | `qa-029-sticky-header-focus.test.mjs` | 6/6 | QA-029 |
| e2e | `qa-030-hero-lede-contrast.test.mjs` | 5/5 | QA-030 |
| e2e | `qa-031-plain-page.test.mjs` | 3/3 | QA-031 |
| e2e | `qa-032-font-library-off.test.mjs` | 3/3 | QA-032 |
| e2e | `qa-038-groupsio-settings-removed.test.mjs` | 2/2 | QA-038 |
| e2e | `qa-039-repeater-call-admin-only.test.mjs` | 3/3 | QA-039 |
| e2e | `qa-045-paged-singular-404.test.mjs` | 6/6 | QA-045 |
| e2e | `qa-046-page-card-order.test.mjs` | 4/4 | QA-046 |
| e2e | `qa-047-admin-bar-search.test.mjs` | 6/6 | QA-047 |
| e2e | `qa-049-profile-trim.test.mjs` | 7/7 | QA-049 |
| e2e | `qa-057-net-preview.test.mjs` | 3/3 | QA-057 |
| e2e | `qa-066-menu-resize.test.mjs` | 3/3 | QA-066 |
| e2e | `qa-067-nowrap-reflow.test.mjs` | 4/4 | QA-067 |
| e2e | `qa-068-crawler-resilience.test.mjs` | 3/3 | QA-068 |
| e2e | `qa-103-header-order.test.mjs` | 13/13 | QA-103 |
| e2e | `qa-104-hub-order.test.mjs` | 7/7 | QA-104 |
| e2e | `qa-107-excerpt-warning.test.mjs` | 1/1 | QA-107 |
| e2e | `qa-admin-quality.test.mjs` | 4/4 | QA-035, QA-037, QA-092 |
| e2e | `qa-cross-pattern-attributes.test.mjs` | 2/2 | QA-021 |
| e2e | `qa-front-094-docs-any-case.test.mjs` | 2/2 | QA-094 |
| e2e | `qa-front-097-toast-region.test.mjs` | 2/2 | QA-097 |
| e2e | `qa-security-editor-surface.test.mjs` | 6/6 | QA-001, QA-010, QA-040, QA-042, QA-043, QA-046 |
| e2e | `sample.test.mjs` | 4/4 | the e2e runner itself (samples) |

### Backfilled tests for the Fixer-round bugs

Nine regression tests are in place, one set per Fixer-round bug (`build-notes/plugin.md`). All of them pass on the current code, and each one failed when its fix was reverted in a scratch copy of the build (never the real tree). Before this, `checks.sh` covered only part of bug 8 (orphaned attachments) and nothing of the other eight.

| # | Bug | Test files (`dev/tests/…`) | What they check | Result with the fix reverted |
|---|---|---|---|---|
| 1 | Page password, date, author and status | `php/prior-page-guard-test.php` (4 tests) | The ARES Editor and the core Editor, through REST and wp_update_post, cannot change About's password, date, author or published status (draft, pending, private, future, future date alone), slug, parent or order. An admin still can. | The 3 editor tests fail (e.g. the password becomes "qa-secret"); the admin check still passes. |
| 2 | Page template | `php/prior-page-template-test.php` (6 tests) | The three members templates are offered to admins only. REST gives editors 400 rest_invalid_param. Editors cannot update, add or delete _wp_page_template through classic or REST saves; saving with the template unchanged still works; admins can change it. | Reverting the template-list filter fails 2 tests; reverting the meta guard fails 2 others. |
| 3 | Quick Edit / bulk Edit on Pages | `php/prior-quick-edit-test.php` (6 tests), `e2e/prior-quick-edit.test.mjs` (3 tests) | No Quick Edit link or bulk Edit for editors; admins keep both. A crafted Quick Edit save is refused with 403 and the title stays; crafted bulk Edit is refused with 403; events and documents are refused for everyone. The e2e test sends the crafted request to the real admin-ajax.php with the nonce printed on the Pages list. | About was renamed "QA renamed by Quick Edit" and the answer was a 500 instead of 403; bulk Edit was not refused; the e2e test fails too. |
| 4 | Save box hidden via Screen Options | `php/prior-save-box-test.php` (3 tests), `e2e/prior-save-box.test.mjs` (2 tests) | The Save box is never hidden or folded, and Screen Options is off for editors on the event and document forms. The e2e test stores hidden and folded preferences the way WordPress does, reloads, and checks the box and its buttons are visible. | All PHP tests fail; the e2e test reports "#spokares_savebox is not visible". |
| 5 | Rota call-sign box dead | `e2e/prior-rota-callsign.test.mjs` (2 tests) | On an Open row the box is greyed, read-only and not disabled. A real click or Tab opens it, typing picks "Call sign", "Shows" updates, and the form would submit the call sign. Typing "Frank" shows "No call sign: not saved". Nothing is saved. | Tested two ways (a disabled box, and a box with no click/focus opening); both tests fail each way. |
| 6 | Multi-paragraph paste | `e2e/prior-paste.test.mjs` (3 tests) | A real paste event on Home: plain text and Word-style HTML become one paragraph with line breaks; lines pasted into a heading become one line; no new blocks; the notice appears. Nothing is saved. | Without the paste handler the paste does nothing, which is the original bug. All 3 fail. |
| 7 | Event "Where" field and save notice | `php/prior-event-where-test.php` (6 tests), `e2e/prior-event-where.test.mjs` (1 test) | The form has the Where field and the "Where it shows" line for each kind. Where is saved and printed on the Next up card, the hub list, Later this season and the public-service rows. Publishing queues "On the site now: … Events never show on Home."; a draft save does not. A phone number in Where keeps a new event a Draft. The e2e test fills the real form, reads the notice, checks the public page, then trashes the event. | Removing the field fails 5 PHP tests plus the e2e; removing the notice fails 2 plus the e2e; removing the text check fails the phone test. |
| 8 | Orphaned document files reachable by ID | `php/prior-orphan-files-test.php` (4 tests), `e2e/prior-orphan-files.test.mjs` (1 test) | Deleting a document permanently, or WordPress's 30-day Trash clean-up, removes its attachment and file; a file shared with another document stays until the last one goes. The e2e test checks ?page_id=N, ?attachment=&lt;slug>, /&lt;slug>/ and ?p=N&attachment_id=N all give a plain 404. | 3 PHP tests fail. With the must-use plugin's part reverted, four addresses redirect to the file. |
| 9 | CI release gate false pass | `e2e/prior-ci-gate.test.mjs` (3 tests, no browser) | Runs ops/ci.sh --offline --no-zip with fake phpcs, php and npx programs. A bad TMPDIR stops it with exit 1 before any check. A PHPCS crash, empty report, non-JSON report or report without totals fails PHPCS; only a parsed clean report passes. | With the mktemp check removed and a text-only PHPCS summary, all fail, and the unchecked run tried writing to /check.cjs. |

Notes: in bug 8 the mistyped-address probe also returns 404 with the fix reverted, so it is a safety check, not a regression check. In bug 9 the first two tests are the ones that catch the bug. The new bug found while backfilling (the "Publish it" tick beside Where was never remembered) became QA-019 and is fixed.

## Screenshots

The gallery is in `qa/index.html`. Full images: 
[anonymous](shots/final/anonymous/) · [subscriber](shots/final/subscriber/) · [contributor](shots/final/contributor/) · [author](shots/final/author/) · [core-editor](shots/final/core-editor/) · [ares-editor](shots/final/ares-editor/) · [ares-net](shots/final/ares-net/) · [admin](shots/final/admin/)
