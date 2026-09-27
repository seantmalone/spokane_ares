# Editor experience and roles: the WordPress build of Option B

- **Prepared:** 2026-09-26, for the WordPress-theme plan of Option B, "Carry the Message".
- **Lens:** the people who keep the site current. Everything here serves one test: *Frank (AG7QP) can post next month's rota, add an exercise, swap a PDF and fix a sentence without help, and cannot break the layout doing it.*
- **Inputs:**
  - `design/round3/option-b-lines-down/` (pages, `DESIGN.md`, `README.md`) and `design/round3/_shared/` (`copy-*.md`, `placement-map.md`, `assets/data.js`). The data shapes below come from `data.js`.
  - `design/round3/REVIEW.md`.
  - `research/decisions.md` (D5, D8-D11, D13-D15) and `research/hosting/verpex-enhance.md`.
- **Current WordPress:** **7.1.2**. The API offers 7.1.2 and 7.0.6, and the minimum PHP is 7.4 (`api.wordpress.org/core/version-check/1.7/`, checked 2026-09-26). WordPress 7.0 shipped on 2026-05-20 and 7.1 on 2026-08-19 [S1, S2].
- **Markers:**
  - **[src]** = checked in WordPress or Gutenberg source code on 2026-09-26.
  - **[doc]** = official documentation.
  - **[verify]** = test in Playground during the build.
  - **INFERENCE** = our reasoning.
- **Not verified in Playground:** this lens was given no server port, so nothing here was clicked through in Playground. §9 lists what to check in the build.

---

## 0. Recommendations in brief

1. **Four of Frank's five routine jobs are plain forms, not the block editor.** Net rota, events, meeting dates and documents are fields and buttons in wp-admin. Only "change the words on a page" uses the block editor, and there the layout is locked. This is the strongest answer to D5's open question ("Is the block editor acceptable to AG7QP?"): he rarely has to use it.
2. **One custom role, "ARES Editor".** It is the built-in Editor role minus `unfiltered_html`, page deletion, category and comment management, plus one custom capability, `spokares_edit_rota`. It is defined in code in `spokares-core`, with no role-editor plugin. Themes, plugins, users, settings, the Site Editor and navigation stay admin-only, because the role has no `edit_theme_options` or `manage_options`.
3. **Four accounts:** two named ARES Editors (D5), one technical Administrator, and one break-glass Administrator for the EC. Everyone gets their own login and two-factor authentication. **Editors do not get Enhance "Collaborator" panel access.** A Collaborator has "unrestricted access to a specific website" [S14], which means files, database and SSO. This amends D5's "Collaborator access for editors".
4. **A trimmed menu:** Dashboard · Net rota · Events · Documents · Pages · Media · Profile. Posts, Comments, Tools and the admin-bar clutter are gone.
5. **The Dashboard is a "Site tasks" card** with the 5 jobs. Each job shows a status line, such as "Rota posted through Tue, Oct 27 · 2 open slots". Amber "needs attention" lines appear only when something is due.
6. **The net rota is one screen.** It shows the next 13 Tuesdays with dates pre-filled. The Simplex, Winlink-night and GMRS labels are computed automatically. Frank types call signs, ticks "Open", fills the Winlink assignment on Winlink nights, and clicks Save once.
7. **Events are structured records.** Dates and times use native pickers, so a "22:00" error like the one in JCal (D9) can't recur. **Past events archive themselves at render time, with no cron:** they drop off upcoming lists the day after they end, and past exercises move to "Past exercises" automatically.
8. **A document is a library entry.** It holds either an uploaded file or a link to the official source. The editor picks a fixed section, and format and size fill in automatically. A privacy tick-box must be checked before the entry publishes. Every entry gets a stable address (`/documents/<slug>/file`) that always serves the newest upload, so hub tiles and printed QR codes never break.
9. **Page layouts are locked in layers.** Section wrappers use `contentOnly`. Section wrappers and dynamic blocks carry `lock` attributes, and `canLockBlocks` is off for non-admins. `theme.json` offers presets only, and the allowed-block list is short. Header, footer, navigation and sub-nav are template parts that editors can't open. **Source check:** `contentOnly` alone is a *soft* lock in WordPress 7.x, because anyone can click "Edit pattern" (§5.2). The extra layers are what make the layout hard to break.
10. **About 1,100 lines of PHP in `spokares-core`,** with no build step. The field UI uses no ACF, SCF or events plugin. The editor UX needs only two third-party plugins: **Two Factor** and an **SMTP** plugin (for password resets, 2FA codes and Note mentions). That leaves room under D5's limit of 4 plugins.

---

## 1. Who edits what, and how often

| Job (from the brief) | How often | Who | Where in wp-admin | Editor used | Target time |
|---|---|---|---|---|---|
| Net-control rota (and Winlink assignments) | Monthly | Net Manager (AG7QP) | **Net rota** | Form | 3 min |
| Upcoming exercises and events | Several times a month in the season | Either editor | **Events** | Form | 3 min each |
| Meeting dates (cancel or move one; rarely change the pattern) | A few times a year | Either editor | **Events › Regular meetings** | Form | 1 min |
| Document uploads and replacements | Monthly or less | Either editor | **Documents** | Form + media upload | 3 min |
| Page text | Occasionally | Either editor; the EC signs off on About and legal text | **Pages** | Block editor, locked | 5 min |
| Net and repeater facts (net time, frequency, tone, Winlink rules) | Rarely | Net Manager | **Net rota › Net details** | Form | 1 min |
| Navigation, header, footer, theme, plugins, users, redirects | Rarely | Technical admin | Administrator menus | n/a | n/a |

What `data.js` holds and where each piece goes in WordPress:
- `rota` and `winlink.assignments` → Net rota.
- `events` and `pastExercises` → Events.
- `meetings` → Regular meetings.
- `documents` and `documentCategories` → Documents.
- `radio`, `nets` and `rotaMeta` → Net details.
- `org`, `links`, `roles` and `sectionRoles` → page content or admin settings.

---

## 2. Accounts, roles and capabilities

### 2.1 Accounts

| Account | Role | Notes |
|---|---|---|
| Editor 1 (AG7QP, if he agrees; D5) | ARES Editor | Personal login, 2FA |
| Editor 2 (to be named; D5) | ARES Editor | Personal login, 2FA |
| Technical admin | Administrator | Updates, theme and plugin changes, users, redirects. The only person with Enhance panel access (Super Admin or Collaborator) |
| EC break-glass (W7TSC) | Administrator | Used only if the technical admin is unavailable; 2FA; password kept offline |

- Use no shared logins. The old site was hacked (D1), so each change should trace to one person. Page revisions record the author, and the rota screen records "last saved by".
- WordPress 7.0 removed Administrator and Editor from the "new user default role" selector [S3]. The default stays Subscriber, and nobody self-registers, because "Anyone can register" stays off.

### 2.2 The "ARES Editor" role

Built-in capabilities come first. The stock **Editor** role already cannot touch themes, plugins, users or settings. Its capability list is the `delete_*`, `edit_*` and `publish_*` capabilities for posts and pages, plus `manage_categories`, `manage_links`, `moderate_comments`, `read`, `read_private_*`, `upload_files`, and `unfiltered_html` on single sites [S4]. So the custom role is a trimmed Editor:

| Capability | ARES Editor | Why |
|---|---|---|
| `read`, `upload_files` | yes | wp-admin access and uploads |
| `edit_posts`, `edit_others_posts`, `edit_published_posts`, `publish_posts`, `delete_posts`, `delete_others_posts`, `delete_published_posts` | yes | Events and Documents use `capability_type => 'post'`, so these caps cover them. Media titles also need post caps. The Posts menu itself is hidden (§3) |
| `edit_pages`, `edit_others_pages`, `edit_published_pages`, `publish_pages` | yes | Page text |
| `delete_pages`, `delete_others_pages`, `delete_published_pages` | **no** | The six structural pages can't be trashed |
| `spokares_edit_rota` (custom) | yes | Net rota, Regular meetings and Net details screens |
| `unfiltered_html` | **no** | No raw scripts or iframes in content. In 7.x this also strips block-level custom CSS, which core gates on `edit_css`. `edit_css` maps to `unfiltered_html` [src: `wp-includes/capabilities.php` and `block-supports/custom-css.php` at 7.1.2] |
| `manage_categories`, `manage_links`, `moderate_comments` | **no** | The library sections are fixed, and the site has no comments |
| `edit_theme_options`, `manage_options`, `*_users`, `*_plugins`, `*_themes`, `import`, `export` | **no** | These gate the Site Editor, navigation, settings, users and plugins [S4] |

- **Implementation:** about 20 lines. Call `add_role( 'ares_editor', 'ARES Editor', $caps )` on plugin activation, and use a version check to re-sync the capabilities [S5].
- **Library sections:** register the taxonomy with `manage_terms`, `edit_terms` and `delete_terms` set to `manage_options`, and `assign_terms` set to `edit_posts`. Editors pick a section but can't invent one.
- **Protect the page slugs:** a small `wp_insert_post_data` guard keeps the slug and parent of the six structural pages unchanged for non-admins. Links, anchors and `redirects.csv` targets depend on those URLs.
- **Optional second role, "Net Manager":** `read` + `spokares_edit_rota` + event capabilities only. Use it if someone ever keeps only the rota.

### 2.3 Hardening that editors will notice (and a little they won't)

| Setting | Where | Effect on editors |
|---|---|---|
| **Two Factor** plugin (by the WordPress.org team): TOTP app plus 10 backup codes; email codes as a fallback | Plugin | A 6-digit code at login. v0.16.0 was updated 2026-03-27 and is "tested up to" 6.9.9, **not yet 7.x** [S6]. **[verify]** it on 7.1 before relying on it |
| `DISALLOW_FILE_EDIT` = true | `wp-config.php` | None. It removes the theme and plugin file editors [S7] |
| `DISALLOW_UNFILTERED_HTML` = true | `wp-config.php` | None for editors. It removes `unfiltered_html` and `edit_css` from everyone, admins included [src]. Custom CSS belongs in the theme |
| No application passwords for non-admins | `wp_is_application_passwords_available_for_user` filter [S8] | The Profile screen loses a confusing section |
| XML-RPC off | `xmlrpc_enabled` filter [S9] | None |
| Allowed uploads: PDF, DOCX, XLSX, PPTX, JPG, PNG, WebP, AVIF and HEIC (7.1 adds native HEIC and AVIF [S2]); SVG, HTML and ZIP blocked | `upload_mimes` filter [S10] | A clear error for anything else |
| Upload size capped around 20 MB (INFERENCE; Enhance's default is 1000 MB, `verpex-enhance.md` §3.1) | Enhance php.ini editor | Keeps the library a library (AUP: no file hosting) |
| No Enhance panel access for editors | Enhance Users | Editors never see files, databases or backups |

**AI:** WordPress 7.0 adds an AI Client, but core ships no provider, and nothing runs unless an administrator configures one under **Settings › Connectors** [S11]. Install no AI provider plugin. If one ever appears, block prompts site-wide with the `wp_ai_client_prevent_prompt` filter [S11].

---

## 3. The admin editors see

### 3.1 Menu

- **ARES Editor:** Dashboard · **Net rota** (Rota, Net details) · **Events** (All events, Add event, Regular meetings) · **Documents** (All documents, Add document) · Pages · Media · Profile.
- **Administrator:** all of the above, plus the normal WordPress menus.

How the menu is trimmed:
- **By capability first:** Appearance, Plugins, Users, Settings and Tools' import and export disappear automatically for the role (§2.2).
- **Then `remove_menu_page()` for non-admins** [S12]:
  - `edit.php` (Posts: the site has no blog)
  - `edit-comments.php`
  - `tools.php`
- **Comments are off everywhere:** remove comment and trackback support from all post types, close them, and drop the admin-bar bubble. About 15 lines, with no "Disable Comments" plugin.
- **Admin bar for non-admins:** remove the WordPress logo menu, "+ New › Post", comments and Customize. Keep "Visit site" and "Edit page".
- **Menu order:** use `custom_menu_order` and `menu_order` so the routine jobs come first.
- **Menu labels are the words Frank would use:** "Net rota", not "Rota settings"; "Add document", not "Add New Document".

### 3.2 Dashboard: the "Site tasks" card

Remove the Welcome panel, At a Glance, Activity, Quick Draft and WordPress Events & News. Administrators keep Site Health. Add one full-width widget with `wp_add_dashboard_widget()` [S13]. The wireframe below uses today's real data from `data.js` (2026-09-26):

```
┌ Site tasks ───────────────────────────────────────────────────────────────┐
│ ① Net rota      Posted through Tue, Oct 27 · 2 open slots (Oct 6, Oct 27) │
│                 [ Update the rota ]                                        │
│ ② Events        Happening now: ARES-ACS / WSDOT exercise                   │
│                 Next: Simulated Emergency Test, Sat, Oct 3 · 9 more       │
│                 [ Add an event ]  All events                               │
│ ③ Meetings      Next: Second Saturday Workshop, Sat, Oct 10, 9:00 AM       │
│                 [ Cancel or move a meeting ]                               │
│ ④ Documents     37 in the library                                          │
│                 [ Add or replace a document ]                              │
│ ⑤ Page text     Home · How it works · About ARES & ACS · (more pages)      │
│ ─────────────────────────────────────────────────────────────────────────  │
│ Needs attention (only shown when true)                                     │
│   ● The rota runs out in under 3 weeks.                                    │
│   ● 2 pages haven't been marked reviewed in 12 months.                     │
│ Stuck? Editing guide · Email the webmaster (role address, D11)             │
└────────────────────────────────────────────────────────────────────────────┘
```

- **Styling:** the amber numbered markers echo Option B's design (DESIGN.md). About 30 lines of admin CSS.
- **Status lines:** computed by the same PHP helpers that render the front end (next net, upcoming events, next meetings), so the dashboard and the site can't disagree.
- **"Needs attention" rules** (INFERENCE; tune after launch):
  - the rota covers fewer than 3 future Tuesdays
  - an upcoming event has no date and isn't marked "date not posted"
  - a page or document hasn't been "marked reviewed" in 12 months (history.md §10: every page needs an owner and a review date)

### 3.3 Help in place

- **Visible help text under each field** (`<p class="description">`), written as instructions (§4). WordPress 7.1 adds `wp_get_tooltip()` and `wp_get_toggletip()` for meta boxes [S2]. Use toggletips only for secondary detail, because a novice doesn't find hidden help.
- **A "Help" tab on each custom screen** (`WP_Screen::add_help_tab()`) with 3-5 lines and a link to the editing guide [S15].
- **An "After saving" notice with a direct link to the result**, for example "Saved. See it on Exercises & events ↗". The link targets the row anchor (`/members/exercises/#set-2026`), and Option B's deep-link highlight shows the row.

### 3.4 Login screen (optional)

- **Branding:** show the group's seal and "Spokane County ARES-ACS · Editors only", pending D6 permissions (`login_headerurl`, `login_head`). About 10 lines.
- **Admin colour scheme:** an optional "Night" scheme (`wp_admin_css_color`) in Option B's navy, red and amber makes the admin feel like the site. This is cosmetic; do it last.

---

## 4. Structured content screens

**Common rules for all four:**
- The post types are registered **without `editor` support**. WordPress then shows the classic form screen, not the block editor: `use_block_editor_for_post_type()` returns false when a type lacks `editor` support [src: `wp-includes/post.php` at 7.1.2].
- Fields are native meta boxes (`add_meta_box()`) [S16] using HTML inputs: `type="date"` and `type="time"` give native pickers with no JavaScript.
- Values are stored as ISO dates and 24-hour times, as in `data.js`. The site always shows 12-hour times, following `data.js`'s conventions.
- **Quick Edit is removed** (`post_row_actions`) [S17].
- **The core "Publish immediately / Edit date" control is hidden** on Events and Documents. Otherwise an editor may schedule the *post* for the event date, and the event would stay invisible until then. This is a classic WordPress trap.
- The post types have `public => false` and `show_ui => true`. There are no thin single-event pages, and the lists live on the Members pages as in the design.

### 4.1 Net rota: one screen for the month

```
Net rota                                                    [ View on site ↗ ]
Tuesday net · 8:00 PM · W7GBU 147.300 MHz            (from Net details)
Leave Net control empty if it isn't posted yet. Tick Open to ask for a volunteer.

 Tuesday      This week is…          Net control   Open  Winlink assignment        Form
 Tue, Sep 29  Simplex (5th Tuesday)  [NZ2S     ]   [ ]
 Tue, Oct 6   –                      [         ]   [x]
 Tue, Oct 13  Winlink night          [AE7RJ    ]   [ ]   [Did You Feel It report…] [DYFI    ]
 Tue, Oct 20  GMRS net 7:30 PM too   [WA7LNC   ]   [ ]
 Tue, Oct 27  Winlink night          [         ]   [x]   [A welfare message      ] [Welfare…]
 Tue, Nov 3   –                      [         ]   [ ]
 …  (13 Tuesdays: through Tue, Dec 22)
                                        Last saved by <editor> on <date> · Undo last save
                                                                    [ Save rota ]
```

- **Rows come from the calendar:** the next 13 Tuesdays starting this week, about a quarter, which reaches the last Winlink assignment in `data.js` (Dec 22). Past Tuesdays drop off by themselves. Stored values older than 90 days are pruned on save.
- **"This week is…" labels are computed** from the Net details rules (5th Tuesday = simplex; 2nd and 4th = Winlink night; 3rd = GMRS at 7:30 PM), the same logic as `fn.netOn()`. The Winlink inputs appear only on Winlink-night rows.
- **Net control:**
  - The input takes **call signs only**, which enforces round 3's "no personal names" rule.
  - Input is uppercased on save.
  - A light pattern check warns but never blocks: "That doesn't look like a call sign. Saved anyway."
  - A `<datalist>` suggests call signs used before, with no JavaScript.
- **Three row states** match `data.js`:
  - a call sign
  - Open (an invitation on the site)
  - empty (not posted, so the site doesn't show it)
- **Storage:** one option, `spokares_rota`, keyed by ISO date. Keep the previous value in `spokares_rota_prev` for the one-click "Undo last save". Options have no revisions, and this is the cheap substitute.
- **Help tab:** "The first row is highlighted on the Members page as the next net. Open slots show as invitations with 'Open slot? Tell the Net Manager (AG7QP) on the net or on groups.io.'" That line is `rotaMeta.openSlotAction`, editable in Net details.
- **Net details** (sub-screen, same capability) is the single home of:
  - the repeater settings (frequency, offset, tone) and the copy line
  - the net time
  - the Winlink rules (which Tuesdays; the how-to line "Answer on an ICS-213 … Radio preferred; Telnet OK.")
  - the open-slot line

  These are `data.js` `radio`, `nets`, `winlink.howTo` and `rotaMeta`. A warning at the top reads: "These appear on several pages. Change them only when the net itself changes." Saving asks for confirmation.

### 4.2 Events

**Fields.** Labels and help text are written as they will appear to the editor.

| Field | Input | Help text (shown under the field) | `data.js` key |
|---|---|---|---|
| Event name | Title | "As members will see it, e.g. *Simulated Emergency Test*." | `title` |
| Kind | Radio: Exercise · Training · On the air · Public service | "Public service events list separately, with who to volunteer through." | `type` |
| Date | Date picker (required unless "Date not posted" is ticked) | "The first day. This is the event's date, not the day you post it." | `start` |
| Last day | Date picker (optional) | "Only for events longer than one day." | `end` |
| Times | Two time pickers (optional) | "Leave empty for all-day events. Times show as 8:00 PM on the site." | `time`, `endTime` |
| Date not posted yet | Checkbox, plus "What to show instead" text (e.g. *As requested*) | "For events we support but whose date isn't set." | Public-service static rows |
| One-line summary | Text, counter at 15 words | "Optional. e.g. *send a DYFI report by Winlink, marked as an exercise*." | `summary` |
| What members do | Textarea, one task per line | "Optional numbered list, e.g. the SET tasks." | `tasks` |
| Links | Up to 3 × (label, URL) | "Official pages only. The first link also makes the name clickable." | `links` |
| Extra form | Dropdown of Documents | "Adds 'Extra form: …' to the card, e.g. WSDOT 580-020." | `extraForm` |
| Details link | URL | "Where members get the full brief, e.g. the groups.io post." | `detailsHref` |
| Volunteer through | Call sign | "Public service only. Call sign, no name." | `contact.callsign` |
| Optional event | Checkbox | "Shows 'optional'." | `optional` |
| After it ends, list under Past exercises | Checkbox, on by default for Exercise | "Adds '*Sep 2026 · ARES-ACS / WSDOT exercise*' to Past exercises." | `pastExercises` |
| Unconfirmed (admin only) | Checkbox | Renders `data-verify="true"`; never a visible chip | `verify` |

**The list screen:**
- **Columns:** When ("Sat, Oct 3"), Name, Kind, Status. Status is computed: "Upcoming", "Happening now" or "Past".
- **Views:** "Upcoming" is the default, sorted soonest first. "Past" is sorted newest first. The views come from `views_edit-{type}` plus `pre_get_posts` [S18, S19].

**Past events archive themselves (no cron, nothing deleted):**
- "Today" is `wp_date( 'Y-m-d' )` in the site time zone [S20]. **Set Settings › General › Timezone to "Los Angeles", not "UTC-8"**, so daylight saving time is handled.
- **Upcoming** means the end date, or the date if there is no end date, is on or after today. The dynamic blocks query this at render time. Nothing depends on WP-Cron, which Enhance runs as an hourly system cron at a random minute [S21].
- **"Happening now"** means start ≤ today ≤ end. That is the WSDOT card's state today.
- **Past Exercise-kind events** with the checkbox on move to the top of "Past exercises" as "Mon YYYY · Name". This is exactly what `copy-members-exercises.md` asks for ("Once the WSDOT exercise ends, add it at the top").
- The historical rows are imported as Exercise events with a month- or year-only date. `data.js` `pastExercises` has entries such as "2019-12" and "2025". A hidden "date precision" value (day / month / year) controls how they print.
- **Other past events simply leave the site** and stay in the admin "Past" view. Nobody has to delete anything.
- **Page cache:** date-driven output changes at midnight with no edit.
  - The dynamic blocks set a short cache lifetime with `do_action( 'litespeed_control_set_ttl', 3600 )`.
  - Saving any rota, event, meeting or document fires `do_action( 'litespeed_purge_all' )`.
  - Both are LiteSpeed Cache API hooks that are harmless when the plugin is absent [S22]. Without them, editors see "I saved it but the site didn't change" and lose trust. INFERENCE: this matters because the web server is probably LiteSpeed (`verpex-enhance.md` §3.1).

**Phase 2 (nice to have):**
- A "Copy to next year" row action creates a draft with the dates moved on a year. Useful for SET, Field Day, Winter Field Day and SKYWARN Recognition Day. About 30 lines.
- A logged-in "show drafts" preview of the Exercises page. About 10 lines.

### 4.3 Regular meetings (Events › Regular meetings)

This covers the `data.js` `meetings` records: the Second Saturday Workshop, the Winlink workshop and the Third Thursday training meeting.
- **The pattern:**
  - Week: 1st-5th or last, plus the weekday.
  - Start and end time, or a text time such as "Evening". The Third Thursday time is unresolved (D13 #4).
  - Months with no meeting: checkboxes, e.g. December (D13 #10).
- **The next 4 dates**, each with **Cancelled** ☐ and **Moved to** [date]. Cancelling is the common job, and it's one tick.
- **Help:** "Home and the Members page show the next date automatically. Tick Cancelled if a meeting is off; the next one shows instead."
- The meeting place (the SCEM address, marked verify in `data.js`) lives in Net details or page text, not per meeting.

### 4.4 Documents: adding a PDF and seeing it in the library

**Adding a new document (the flow Frank follows):**
1. **Dashboard › "Add or replace a document"**, or **Documents › Add document**.
2. **Title:** "Exactly as it should appear, e.g. *ACS Position Task Book*."
3. **Library section:** radio, required. Net operations · Join & task books · Forms · Training · Go-kits & readiness · Winlink & digital · Reference. These are the `documentCategories`.
4. **The file, one of two ways:**
   - **Upload here:** "Upload or choose file" opens the standard media window. The editor drags in the PDF and clicks "Use this file". **Format** (PDF or DOCX) and **size** fill in automatically.
   - **Link to the official source instead:** URL plus "Source name" (e.g. *FEMA*, *ARRL*). This covers 23 of 76 inventory items (D10), such as the FEMA ICS forms.
   - Leaving both empty shows "Soon", as `href: null` does in `data.js`.
5. **Version or date** (optional): "e.g. *2024-08-09* or *v3*."
6. **Short description** (optional; counter stops at 8 words): "e.g. *Print it; keep sign-offs in a binder.*"
7. **Most used** ☐ ("adds it to the Most used filter"). **Members-hub tile** ☐ ("up to 4; replaces the oldest tile"). The second answers REVIEW's shared fix #1, which is about hub tile choice.
8. **Privacy check** ☐, required to publish: "This file has no personal phone numbers, home addresses, personal e-mails, member lists or rosters, and no county, hospital, SHARES or 800 MHz channel details." If it's unticked, the entry saves as a draft with a red notice. The wording comes from the never-publish list in `data.js` and D14. The net-script DOCX files carry a personal e-mail (`copy-members-documents.md`, D13 #2).
9. **Publish.** The notice reads "Saved. See it on Documents & forms ↗", linking `/members/documents/#<slug>` with the row highlighted.

**Other behaviour:**
- **Uploads are public at once.** A file in Media can be reached at its web address even while the library entry is a draft. The upload button repeats this in its help text: "Uploading makes the file public at its web address, even before you publish." AUP 3.5 also requires every uploaded file to be reachable via the domain (`verpex-enhance.md` §4.4). Anything private belongs on groups.io (D8).
- **Replacing a file:** open the entry, click **Replace file**, upload the new one, update the Version, and click **Update**.
  - The **stable link** `/documents/<slug>/file` redirects to the newest file. Hub tiles, event "Extra form" links and printed QR codes point there. This answers REVIEW's shared fix #2: tiles open the file, not the row.
  - An optional "Delete the old file" tick-box removes the previous attachment.
- **Slugs** are set once from the title and not shown for editing. Imported entries keep the `data.js` ids (`ics-213`, `acs-task-book`, `net-scripts` …), so every existing deep link (`documents.html#ics-213`) keeps working.
- **Order within a section:** a "Sort order" number (`menu_order`; "Lower shows first; most entries can stay at 10"). Drag-to-reorder would need a plugin or custom JavaScript; it's not worth it for 37 rows.
- **Last reviewed:** a list-screen row action, **"Mark reviewed today"**, sets a date. D10 asks for date, version, owner and last-reviewed date on every entry.
- **File-name tip in the help tab:** "Name files like `2026-10-01-acs-task-book.pdf` before uploading" (D10: ISO-dated file names). "Prefer PDF unless members must fill in the file (the application, the task book)."

---

## 5. Page text: locked layouts

### 5.1 What lives where

| Page (Option B) | Template or template part (admin only) | Page content: what editors edit | Dynamic blocks (locked; data from §4) |
|---|---|---|---|
| All pages | Header, primary nav, members sub-nav, footer | n/a | n/a |
| Home | Page template | Hero headline, lede, 2 buttons, agency line; the 4 "What we do" stops; "Come visit" text; the 4 join steps | Next meeting dates in "Come visit" |
| How it works | Page template; the static opener diagram is a theme asset | The chapter text | The radio settings box (Net details) |
| About ARES & ACS | Page template | The 9 sections; the leadership table (roles and call signs) | "On this page" TOC, built from the H2s so nobody maintains it |
| Members hub | Page template | Almost nothing (the H1) | Search, This week, Tuesday net rota, hub tiles |
| Documents & forms | Page template | The "Not posted here" lines | Search, chips and the whole library |
| Exercises & events | Page template | The "Bring to every exercise" line, the "After any exercise" line, the "First event?" line | Next up cards, later this season, Winlink assignments, public service, past exercises |

**Dynamic blocks:** WordPress 7.0's **PHP-only block registration** (`'supports' => [ 'autoRegister' => true ]`) builds these server-rendered blocks with no JavaScript build step. The editor shows a live server-rendered preview [S23]. When the preview renders for someone in the editor, the block adds a one-line note, for example "Change this list in **Net rota** →", so "where do I edit this?" always has an answer on the canvas.

### 5.2 The lock, in layers

The block-locking documentation says `contentOnly` "hides all design tools, while still allowing for the ability to edit the content of the blocks", and that non-content blocks become un-editable and nothing new can be inserted. It also says the toolbar's "Modify" toggle cannot be removed programmatically [S24]. In 7.x that toggle is **"Edit pattern"**, and unsynced patterns now default to contentOnly [S25].

Current Gutenberg source shows what the button does:
- `EditSectionButton` renders for any section block whenever `canEditBlock()` is true.
- Entering the section sets every block inside to editing mode `default`.
- `getTemplateLock()` treats a contentOnly lock "that's in the process of being edited" as `false`.

[src: `block-toolbar/edit-section-button.jsx`, `store/reducer.js`, `store/selectors.js`, Gutenberg trunk 2026-09.] **So `contentOnly` guides editors but does not stop them.** The layers:

| Layer | What it does | Mechanism |
|---|---|---|
| 1. Chrome in templates | Header, nav, sub-nav, footer and page templates can't be opened by editors | No `edit_theme_options` [S4] |
| 2. `contentOnly` section wrappers | Everyday view: click text, type; no design panels, no inserter | `"templateLock":"contentOnly"` on each section Group in the theme's patterns [S24] |
| 3. Hard floor | Even after "Edit pattern", sections and dynamic blocks can't be moved or removed, and editors can't unlock them | `"lock":{"move":true,"remove":true}` on section Groups and every dynamic block; `$settings['canLockBlocks'] = current_user_can( 'edit_theme_options' )` [S24, S26]. Block-level lock "takes priority over" templateLock [S27] |
| 4. Presets only | Inside a section, only on-system choices exist | `theme.json`: custom colour, gradients, duotone, font sizes, spacing, borders and shadows off; Option B's palette and type scale as presets [S28]. Editor settings for non-admins: `codeEditingEnabled` false, and the new 7.1 settings `responsiveEditingEnabled` and `blockStatesEditingEnabled` false [S29] |
| 5. Short block list | "Edit pattern" mode can only insert prose blocks | `allowed_block_types_all` for non-admins: paragraph, heading, list, list item, buttons, button, image, table, details, quote [S30] |
| 6. Undo | Anything else is one click back | Page revisions; 7.0 redesigned the revisions screen [S1] |

**Also consider:**
- 7.0 **block visibility** lets editors hide a block per device, and it hides with CSS only [S31]. A block hidden on phones by accident is hard for Frank to notice. Consider turning off `supports.visibility` for all blocks via `register_block_type_args` [S32]. **[verify]**
- **`lock.edit` on a section Group** hides the "Edit pattern" button entirely, because `canEditBlock()` returns `!lock.edit`. It might also freeze the text inside, so **[verify]** before using it. If it leaves inner text editable, it closes the soft-lock gap completely.

### 5.3 Preview, review and notes

- **Pages** use the core **Preview** button and drafts. Editors can "Save draft" on a page they're unsure about and ask the other editor.
- **Events, Documents and Rota** have no drafts-with-preview. The after-save notice links to the exact row (§3.3), and draft events never show. A logged-in draft preview is phase 2.
- **Notes:** WordPress 7.1's Notes support @mentions and notes on a span of text [S2]. They let the EC leave "SCEM to confirm this sentence" on About without e-mail. Mention e-mails need the SMTP plugin. **[verify]** which roles can add and see Notes.
- **"Last reviewed":**
  - A Pages list column plus a **"Mark reviewed today"** row action.
  - The page prints "Last reviewed …" **only after someone has marked it.** REVIEW asks for no invented date: B's About currently prints "Sep 26, 2026".
  - It is never set from the modified date.

---

## 6. What happens automatically

| Thing | Rule | Editor action needed |
|---|---|---|
| The next net, the highlighted rota row, the rota rows shown | Computed from today and the rota option | None |
| Simplex, Winlink-night and GMRS labels | Computed from Net details rules | None |
| Past events | Leave upcoming lists the day after they end; past exercises go to "Past exercises" | None |
| "Happening now" | start ≤ today ≤ end | None |
| Next meeting dates | Pattern minus cancelled dates | Tick "Cancelled" when needed |
| Old rota rows | Pruned 90 days after their date | None |
| Page cache | Short TTL on date-driven blocks; purge on save | None |
| Stale data warnings | Dashboard "Needs attention" | Act on the amber line |

---

## 7. What won't work well, and the trade-offs

1. **`contentOnly` is soft (§5.2).** The layered lock makes the layout hard to break, not impossible. A determined editor can still rewrite text inside a section in "Edit pattern" mode. Revisions are the safety net.
2. **The classic form screens look older** than the block editor, and 7.1 now fully iframes the post editor [S2, S33]. For structured data, forms are simpler and harder to break.
   - The DataViews and DataForm screens in 7.0 and 7.1 [S34] are the future path, but they need a JavaScript build. Not now.
   - [verify] that plain meta boxes on non-block post types are unaffected by the iframe change. The note speaks only of the block editor.
3. **Nothing reorders by drag.** Document order is a number.
4. **The rota option has no revision history,** only "Undo last save". Page revisions and event revisions are built in. Post-meta revisions exist since 6.4 via `revisions_enabled` [S35]. [verify] that they work with meta-box saves.
5. **Two Factor isn't marked tested on 7.x** [S6]. If it fails, the fallback is strong passwords plus Enhance-side login protection. Enhance's wp-admin IP lockdown works on Apache only (`verpex-enhance.md` §4.2).
6. **This shifts D9's source of truth.** The owner's direction (WordPress renders events from an admin list) makes WordPress the calendar source for the rota and events, not the groups.io calendar. groups.io remains the member hub (D8). If the group wants events in the groups.io calendar or on phones, an `.ics` feed from `spokares-core` is a later add-on (INFERENCE; not researched whether groups.io can subscribe to it).
7. **Hosting access changes D5.** Collaborator panel access is too broad for content editors (§2.1).
8. **`data-verify` flags** are a pre-launch QA device. Keep them admin-only, and drop them once D13 facts are confirmed.

---

## 8. Implementation sketch (all in `wordpress/plugins/spokares-core/`; the theme holds patterns, templates and `theme.json`)

Content types and roles live in the plugin, not the theme, so a theme change never strands the data.

| File | Does | Approx. PHP lines |
|---|---|---:|
| `spokares-core.php` | Bootstrap, activation (role and capabilities, default options), version-based re-sync | 40 |
| `inc/roles.php` | `ares_editor` role; `map_meta_cap` guard for structural pages | 50 |
| `inc/admin-trim.php` | Menus, admin bar, comments off, dashboard cleanup, menu order, Quick Edit and date-control removal | 90 |
| `inc/dashboard.php` | "Site tasks" widget and "Needs attention" rules | 90 |
| `inc/rota.php` | Net rota screen, Net details screen, validation, undo, purge | 170 |
| `inc/events.php` | Event post type, meta boxes, columns and views, past/upcoming queries | 180 |
| `inc/meetings.php` | Regular meetings screen and next-dates helper | 90 |
| `inc/documents.php` | Document post type, section taxonomy, file/link fields, privacy gate, stable `/file` redirect, "Mark reviewed" | 180 |
| `inc/editor-guardrails.php` | `block_editor_settings_all`, `allowed_block_types_all`, `register_block_type_args`, slug guard | 50 |
| `inc/security.php` | Application passwords, XML-RPC, `upload_mimes` | 30 |
| `inc/helpers.php` | Date and time formatting (12-hour), `netOn`, next meetings, upcoming. **Shared by the dashboard and the front-end blocks** | 120 |
| `assets/admin.css` | Amber markers, rota table, notices | ~80 CSS |

- **Total:** about 1,100 lines of PHP plus about 80 lines of CSS. There is no editor JavaScript, because the dynamic blocks use PHP-only registration.
- **Dev only (never shipped):** a `wordpress/dev/mu-plugins/` auto-login for Playground screenshots, per the workflow brief.

---

## 9. Verify in Playground during the build

1. With `canLockBlocks` false and `lock.move` / `lock.remove` on sections, can an ARES Editor in "Edit pattern" mode remove a section or a dynamic block? (Expected: no.)
2. Does `"lock":{"edit":true}` on a contentOnly Group hide "Edit pattern" but keep its text editable?
3. Does `register_block_type_args` → `supports.visibility = false` remove the 7.0 visibility controls?
4. Does Two Factor 0.16.0 work on 7.1.2 (TOTP, backup codes, email codes over SMTP)?
5. Do classic meta-box screens for non-block post types render and save normally in 7.1?
6. Can an ARES Editor see and add Notes? Do @mentions e-mail?
7. With DISALLOW_UNFILTERED_HTML, does the editor still work normally for ARES Editor, and is block custom CSS stripped?
8. Does `revisions_enabled` post meta produce event revisions from meta-box saves?
9. A timezone test: put the dev site in Los Angeles time, then check "Happening now" and "Past" across a midnight boundary using a filter to override "today". This is a dev-only hook mirroring `data.js`'s `?today=`.

---

## 10. EDITING-GUIDE.md: outline (one printed page, both sides, for Frank)

**Title:** *Keeping spokares.org current: the 5 jobs.* Every step names the button words exactly as they appear on screen. Use large type and a screenshot thumbnail per job.

1. **Log in.**
   - Bookmark `spokares.org/wp-admin`. Enter your own username, password and the 6-digit code from your phone app.
   - Lost your phone? Use a backup code, then tell the webmaster.
   - Never share your login. Log out on shared computers.
2. **Your Dashboard.** The "Site tasks" card has 5 buttons. Amber dots mean something is due.
3. **Job 1: Post next month's rota (monthly, 3 minutes).**
   - Click **Net rota**.
   - Type call signs (no names). Tick **Open** for an empty slot. Fill the Winlink assignment on Winlink nights.
   - Click **Save rota**, then **View on site**.
   - Made a mistake? Use **Undo last save**.
4. **Job 2: Add or change an event.**
   - Click **Events › Add event**. Fill in name, kind, date and (optionally) times, then click **Publish**.
   - The date is the event's date, not today's.
   - Past events archive themselves; don't delete them.
   - Event off? Move it to the trash, or set "Date not posted yet".
5. **Job 3: Cancel or move a meeting.** Click **Events › Regular meetings**, tick **Cancelled** (or set **Moved to**), then **Save**.
6. **Job 4: Add or replace a document.**
   - Click **Documents › Add document**. Enter the title and pick the section.
   - Either upload the file or link to the official source. Tick the privacy box, then click **Publish**.
   - To replace a file: open the entry, click **Replace file**, change the version, and click **Update**.
   - Remember: anything uploaded is public immediately.
7. **Job 5: Change words on a page.**
   - Click **Pages**, open the page, click into the text and type. Click **Preview**, then **Update**.
   - You can't move sections or change colours. That's on purpose.
   - Need a new section? Ask the webmaster.
   - To undo: open **Revisions** and restore.
8. **Never publish.** The roster or member lists; personal phones, e-mails or home addresses; county, hospital, SHARES or 800 MHz channels; the Hospital Net channel; simplex and GMRS repeater details until approved; a "current readiness level". These come from the list in `data.js` and D14.
9. **House style.**
   - 12-hour times ("8:00 PM", never "2000").
   - Call signs, not names.
   - Dates like "Sat, Oct 3".
   - Say each fact once and link to its home page.
10. **When stuck.** Email the webmaster at the role address (D11). Include what you clicked and a screenshot. Nothing you do in these 5 jobs can break the site's layout.

---

## 11. Decisions this touches

- **D5:** editors get WordPress accounts, not Enhance Collaborator access. Two named editors plus two administrators. The plugin budget has room: Two Factor, SMTP, redirects (or panel/`.htaccess`), and caching if needed.
- **D9:** WordPress becomes the source for the rota and events, per the owner's direction. Add a line to D9 and ask AG7QP to confirm that he'll keep the rota here instead of in groups.io or on ag7qp.com.
- **D10:** library entries carry version, source, last-reviewed and the privacy gate. Media uploads are public, per AUP 3.5.
- **D11:** an SMTP plugin is needed even with no contact form, for password resets, 2FA e-mail codes and Note mentions.
- **D13:** the admin-only "Unconfirmed" flags carry the verify items until they're confirmed.

---

## Sources

- **S1** WordPress 7.0 "Armstrong" announcement (2026-05-20), https://wordpress.org/news/2026/05/wordpress-7-0-armstrong/ ; 7.0 Field Guide, https://make.wordpress.org/core/2026/05/14/wordpress-7-0-field-guide/
- **S2** WordPress 7.1 "Mary Lou" announcement (2026-08-19), https://wordpress.org/news/2026/08/mary-lou/ ; 7.1 Field Guide, https://make.wordpress.org/core/2026/08/05/wordpress-7-1-field-guide/
- **S3** 7.0 Field Guide (default-role selector change), as S1
- **S4** Roles and Capabilities (updated 2026-09-02), https://wordpress.org/documentation/article/roles-and-capabilities/
- **S5** `add_role()`, https://developer.wordpress.org/reference/functions/add_role/
- **S6** Two Factor plugin metadata, https://api.wordpress.org/plugins/info/1.0/two-factor.json (v0.16.0; updated 2026-03-27; tested 6.9.9; author WordPress.org) and https://wordpress.org/plugins/two-factor/
- **S7** wp-config constants, https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
- **S8** https://developer.wordpress.org/reference/hooks/wp_is_application_passwords_available_for_user/
- **S9** https://developer.wordpress.org/reference/hooks/xmlrpc_enabled/
- **S10** https://developer.wordpress.org/reference/hooks/upload_mimes/
- **S11** "Introducing the AI Client in WordPress 7.0", https://make.wordpress.org/core/2026/03/24/introducing-the-ai-client-in-wordpress-7-0/
- **S12** https://developer.wordpress.org/reference/functions/remove_menu_page/
- **S13** https://developer.wordpress.org/reference/functions/wp_add_dashboard_widget/
- **S14** Enhance user roles ("Collaborator: unrestricted access to a specific website"), https://enhance.com/docs/account/end-user/managing-user.html
- **S15** https://developer.wordpress.org/reference/classes/wp_screen/add_help_tab/
- **S16** https://developer.wordpress.org/reference/functions/add_meta_box/ ; https://developer.wordpress.org/reference/functions/register_post_type/
- **S17** https://developer.wordpress.org/reference/hooks/post_row_actions/
- **S18** https://developer.wordpress.org/reference/hooks/views_this-screen-id/
- **S19** https://developer.wordpress.org/reference/hooks/manage_post_type_posts_columns/
- **S20** https://developer.wordpress.org/reference/functions/wp_timezone/
- **S21** Enhance WP-Cron ("replaces the default WP-Cron for a System Cron which triggers the script at every hour at a random minute"), https://enhance.com/docs/wordpress/admin/wordpress-crons.html
- **S22** LiteSpeed Cache API hooks (`litespeed_purge_all`, `litespeed_control_set_ttl`, `litespeed_purge_post`), https://docs.litespeedtech.com/lscache/lscwp/api/
- **S23** "PHP-only block registration" (7.0), https://make.wordpress.org/core/2026/03/03/php-only-block-registration/
- **S24** Block Locking API, https://developer.wordpress.org/block-editor/how-to-guides/curating-the-editor-experience/block-locking/ (Gutenberg `docs/how-to-guides/curating-the-editor-experience/block-locking.md`)
- **S25** "Pattern Editing in WordPress 7.0" (unsynced patterns default to contentOnly; `disableContentOnlyForUnsyncedPatterns`), https://make.wordpress.org/core/2026/03/15/pattern-editing-in-wordpress-7-0/
- **S26** https://developer.wordpress.org/reference/hooks/block_editor_settings_all/ ; Gutenberg `packages/block-editor/src/store/selectors.js` (`canLockBlockType` reads `settings.canLockBlocks`; `canEditBlock` returns `!lock.edit`)
- **S27** Gutenberg `docs/reference-guides/block-api/block-templates.md` ("Block-level lock takes priority over the `templateLock` feature")
- **S28** theme.json settings, https://developer.wordpress.org/block-editor/how-to-guides/themes/global-settings-and-styles/
- **S29** Disable editor functionality (`codeEditingEnabled`, `responsiveEditingEnabled`, `blockStatesEditingEnabled`), https://developer.wordpress.org/block-editor/how-to-guides/curating-the-editor-experience/disable-editor-functionality/
- **S30** https://developer.wordpress.org/reference/hooks/allowed_block_types_all/
- **S31** "Block Visibility in WordPress 7.0", https://make.wordpress.org/core/2026/03/15/block-visibility-in-wordpress-7-0/
- **S32** https://developer.wordpress.org/reference/hooks/register_block_type_args/
- **S33** "Iframed Editor Changes in WordPress 7.1", https://make.wordpress.org/core/2026/08/03/iframed-editor-changes-in-wordpress-7-1/
- **S34** "DataViews, DataForm, et al. in WordPress 7.0", https://make.wordpress.org/core/2026/03/04/dataviews-dataform-et-al-in-wordpress-7-0/
- **S35** "Framework for storing revisions of Post Meta in 6.4", https://make.wordpress.org/core/2023/10/24/framework-for-storing-revisions-of-post-meta-in-6-4/
- **Source code checked 2026-09-26:**
  - WordPress 7.1.2: `wp-includes/capabilities.php` (`edit_css` and `unfiltered_html` mapping; DISALLOW_UNFILTERED_HTML), `wp-includes/block-supports/custom-css.php` (block CSS stripped without `edit_css`) and `wp-includes/post.php` (`use_block_editor_for_post_type`), from https://github.com/WordPress/WordPress/tree/7.1.2
  - Gutenberg trunk: `packages/block-editor/src/components/block-toolbar/{index,edit-section-button}.jsx`, `store/{reducer,selectors,private-selectors}.js`, from https://github.com/WordPress/gutenberg
- **Plugin metadata, WordPress.org API, 2026-09-26:**
  - Secure Custom Fields 6.9.5 (updated 2026-08-20, tested 7.1.2); rejected for now in favour of native meta boxes
  - LiteSpeed Cache 7.9.1
  - WP Mail SMTP 4.9.0; FluentSMTP 2.4.0
  - Redirection 5.10.1
- **Project files:** `research/decisions.md` (D1, D5, D8-D11, D13-D15), `research/hosting/verpex-enhance.md` (§3.1, §4.2-§4.4), `design/round3/_shared/assets/data.js`, `design/round3/_shared/copy-members-*.md`, `design/round3/REVIEW.md`, `design/round3/option-b-lines-down/README.md`
