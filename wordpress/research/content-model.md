# spokares.org on WordPress: dynamic content model and admin UX

- **Prepared:** 2026-09-26, for the Option B ("Carry the Message") WordPress theme build.
- **Lens:** how each dynamic data set is stored, edited and rendered, and how simple the editing is for a non-technical volunteer editor. The theme architecture, security hardening and hosting set-up are covered by other research notes; this one touches them only where the content model depends on them.
- **Inputs:**
  - Design: `design/round3/option-b-lines-down/` (pages, `DESIGN.md`, `assets/site.js`), `design/round3/_shared/assets/data.js` (the data shapes), `design/round3/_shared/placement-map.md` (where each fact lives), `design/round3/REVIEW.md` (Option B fixes).
  - Decisions: `research/decisions.md` D5, D8, D9, D10, D11, D13 (never-publish rule), D14 (PII).
  - Hosting: `research/hosting/verpex-enhance.md` §4.2 to §4.5, §5.3.
  - Current editing habits: `research/content/interim-ag7qp/spokane-county-ares-acs.md` (the hand-typed rota, events and Winlink lists on ag7qp.com today).
  - Web research on 2026-09-26 (section 13).
- **Tags:** **[C]** confirmed in the cited source; **[I]** my inference or recommendation.
- **Status:** a recommendation. The owner, the EC and AG7QP decide. Section 12 lists what they need to settle.

---

## 0. The recommendation on one screen

**Build all five data sets natively in our own small plugin, `spokares-core`. Use no field-framework or events plugin.** The editor gets purpose-built screens: plain forms with labelled fields, date pickers and drop-downs. They can't break the page layout, and the monthly rota is one screen and one Save.

| # | Data set | Stored as | Edited on (admin menu → screen) | Why this shape |
|---|---|---|---|---|
| 1 | Events and exercises | Custom post type `spk_event`, fields in post meta (`spk_*`) | **Events → Add event** (a classic form, not the block editor). The list is sorted by date, with Upcoming and Past tabs and a **Duplicate** row action | Events are one-off records with a fixed shape. A list table gives sorting, drafts, trash and per-user edit history |
| 2 | Net-control rota (plus the Winlink assignments that share its Tuesdays) | One option, `spk_rota`, keyed by ISO date | **Net rota** is one spreadsheet-style screen. The Tuesdays are generated automatically and each gets its note (simplex, Winlink night, GMRS). The editor types call signs and saves. **Paste a list** accepts Frank's current "October 13 - AE7RJ, Name" format and drops the names | A month of rota takes about 3 clicks. As posts it would take about 25 |
| 3 | Recurring meetings and nets, plus one-off changes | One option, `spk_schedule`: rules (nth weekday, times, months to skip) and a "changes" list | **Schedule → Meetings & changes.** The top of the screen holds the frequent edit: cancel, move or re-time one date. The rules sit below and rarely change | The rules drive "Next: Sat, Oct 10" on Home and the hub, and the rota notes. One change row fixes every page |
| 4 | Document library | Custom post type `spk_document` plus taxonomy `spk_doc_cat` (the 7 categories) | **Documents → Add document**, with a choice of **Upload a file**, **Link to another site** or **Not available yet ("Soon")**. **Replace file** keeps a stable link at `/docs/<slug>/`. A bulk action marks documents reviewed today | Each row has a source, format, version, owner and review date (D10). Stable URLs survive file updates |
| 5 | Repeater and net settings, meeting place, key links | Options `spk_radio`, `spk_place`, `spk_links` | **Schedule → Nets & radio** (limited to named people; it carries a never-publish warning) | Change the tone once and it updates the settings box, the hub bar and its Copy button, the Home "listen" line, the diagram label and the calendar feed |

- **Front end.** The theme renders every list server-side with the Option B markup and classes (`.events`, `.date-slab`, `.hub-rota`, `.doc-table`, and so on), so `site.css` applies unchanged and the pages work without JavaScript [I]. Section 7 has the render contract.
- **Source of truth.** WordPress becomes the single source for everything the public site shows. This supersedes D9's groups.io-calendar option. groups.io stays the place for discussion, RSVPs, members-only files and optional reminders. The site publishes `/calendar.ics` so members can subscribe on their phones (section 8).
- **Plugin budget.** The content model uses **0 of the 4 plugin slots**. They stay free for SMTP, forms, 2FA and caching or redirects (section 10.3).
- **Fallback.** If no one can maintain PHP, **Secure Custom Fields** is the one field plugin worth its slot. It is free, maintained under the WordPress.org account, and includes the repeater field and options pages [C, S23]. It still cannot give the auto-generated Tuesday rota or rule-based meetings without code (section 3.4).

---

## 1. What Option B needs from data

Every dynamic element on the six round-3 pages, with the data set that feeds it. The "Render" column is the proposed block or helper (section 7).

| Page | Element (Option B) | Data set | Render |
|---|---|---|---|
| Home `#visit` | "From home: listen to the Tuesday net, 8:00 PM, 147.300 MHz" | 5 (radio), 3 (net time) | inline shortcode in page text |
| Home `#visit` | Meetings list with "Next: Sat, Oct 10" / "Next: Thu, Oct 15" | 3 | block `spokares/meetings` |
| Home, How it works, About | Message-path diagram label "147.300 MHz" (SVG) | 5 | theme pattern calls the PHP helper |
| How it works `#nets` | Settings box (W7GBU and alternate), Copy button | 5 | block `spokares/radio-box` |
| How it works `#nets` | "Other nets" (Winlink nights, 5th-Tuesday simplex, GMRS) | 3 | block `spokares/other-nets` |
| How it works `#alert-levels` | NOAA WXL86 162.400 MHz (Orange row) | 5 | inline shortcode |
| Members hub | "Schedule as of …" line | 2, 1 (last save) | block `spokares/asof` |
| Members hub `#this-week` | Next two exercises or trainings within 60 days, "Happening now" | 1 | block `spokares/upcoming` |
| Members hub `#this-week` | "Next meeting: Sat, Oct 10, 9:00 AM, Second Saturday Workshop" | 3 | block `spokares/next-meeting` |
| Members hub `#rota` | Net bar (8:00 PM, W7GBU settings, Copy) | 5, 3 | block `spokares/netbar` |
| Members hub `#rota` | 5 Tuesdays: date, net control or **Open** or "Not yet published", Note (Simplex / Winlink night / GMRS net 7:30 PM) | 2, 3 | block `spokares/rota` |
| Members hub `#quick-links` | 4 "Most used" tiles | 4 | block `spokares/quick-links` |
| Documents | Library: 7 groups, rows with format, version, note, source, how-to and sub-links; search and filter | 4 | block `spokares/doc-library` |
| Exercises `#next-up` | Cards for the next two exercises: date, status, tasks, extra form, details button | 1 | block `spokares/next-up` |
| Exercises `#upcoming` | "Later this season" table | 1 | block `spokares/event-table` |
| Exercises `#winlink-assignments` | Date / Assignment / Form table | 2 | block `spokares/winlink` |
| Exercises `#public-service` | Event / When ("Date not posted", "As requested") / Volunteer through (call sign) | 1 | block `spokares/public-service` |
| Exercises `#past` | 8 notable past exercises, month and year | 1 | block `spokares/past` |
| Site-wide | Calendar feed | 1, 2, 3 | `/calendar.ics` |

**Static, not modelled** (page text in the block editor): the "Bring to every exercise" line, the Winlink how-to sentence, the leadership table (changes about yearly), the ARES vs ACS definitions, the legal basis, History. [I]

---

## 2. Constraints that decide the design

1. **The editor.** The likely main editor (AG7QP) uses a no-code Hostinger builder today. He is comfortable with visual editing, not code (D5).
   - Frequent edits: the rota (monthly), exercises and events, meeting changes, document uploads, occasional page text.
   - He types the rota as free text today: "October 13 - AE7RJ, Randall Jones" [C, interim page].
   - **Design goal:** forms that cannot break layout, the fewest clicks, and his existing paste format accepted as it is.
2. **Plugin budget: 4 or fewer, including forms and SMTP** (D5). Every plugin is patched by the Enhance toolkit auto-updates, but each is also more attack surface on a site that was hacked before (D1, `verpex-enhance.md` §5.3).
3. **Never publish** (D13): county, hospital, SCEM, SHARES or public-safety frequencies, channels or talkgroups; ACSFOG procedures; station locations. **Today's interim event list mixes these in.** It carries "There will be a Hospital Net on Channel 6 following the regular net" and the 1st-Tuesday staff meeting, which D13 #16 keeps off public pages [C, interim page]. Structured fields with guard rails beat free text here [I].
4. **No personal names, phones or personal e-mails** (D14, `data.js` conventions). The rota shows call signs only.
5. **No site logins for members** (D8). Every dynamic item is public, so there is no gated data to model.
6. **Hosting.**
   - The web server is probably LiteSpeed, so page caching is LSCache [I, `verpex-enhance.md` §4.2].
   - WP-Cron runs hourly from the system cron [C, `verpex-enhance.md` §5.3].
   - Security must not depend on `.htaccess` [C, §4.2].
   - Uploaded files must be reachable via the domain (AUP 3.5), and the site must not become a file host (AUP 3.1) [C, §4.4].
7. **Current WordPress.** 7.1.2 is current (7.0 "Armstrong" on 2026-05-20, 7.1 "Mary Lou" on 2026-08-19) [C, S1-S3]. Relevant changes:
   - **7.0 PHP-only block registration.** `register_block_type()` with `'supports' => ['autoRegister' => true]` and a `render_callback` puts a block in the editor with no JavaScript build. The Inspector controls are generated from the attributes [C, S5]. This makes our dynamic blocks cheap.
   - **7.0 contentOnly locking.** It now applies more broadly to patterns [C, S4]. This helps the theme lock page layouts.
   - **7.1 iframed post editor.** It is now always iframed, including on sites with legacy meta boxes [C, S7]. Classic edit screens for post types without REST support are unaffected [I, S11].
   - **Real-time collaboration was not enabled in 7.1** [C, S6]. Two editors should not edit the same record at once. WordPress's post lock warning covers posts; our option screens need a "last saved by … at …" line (section 5.3) [I].

---

## 3. Options compared

### 3.1 Field frameworks (for events, documents and settings)

Data from the WordPress.org plugin API and vendor pages, checked 2026-09-26 [C, S21-S26].

| | **Native (our `spokares-core`)** | ACF free | ACF PRO | **Secure Custom Fields (SCF)** | Meta Box (free) / Meta Box Lite | Pods | CMB2 |
|---|---|---|---|---|---|---|---|
| Version, installs, tested | n/a | 6.8.10, 2M+, WP 7.1.2 | same core | 6.9.5, 100k+, WP 7.1.2 | 5.15.1, 500k+, WP 7.1.2 | 3.3.9.2, 100k+, WP 7.1.2 | 2.13.1, 300k+, WP 7.1.2 |
| Maintainer | us | WP Engine | WP Engine | WordPress.org account (fork of ACF) | Meta Box | Pods team (S. K. Clark) | CMB2 / J. Sternberg |
| Plugin slots used | **0** | 1 | 1 | 1 | 1 | 1 | 1 (0 if bundled, but then no auto-update) |
| Cost | none | none | **$49/yr** for 1 site | none | free; Settings Page and Group extensions **$49 each/yr** | none | none |
| Register CPTs in a UI | code | yes (free) | yes | yes | yes (Lite, with Builder) | yes | no (code) |
| Repeatable rows (links, sub-files, changes list) | ours (≈40 lines of JS) | **no** (Repeater is PRO) | yes | **yes** (free) | single-field clone free; **groups are premium** | repeatable single fields only | yes (repeatable groups) |
| Options/settings page | Settings API | **no** (PRO) | yes | **yes** | **premium** (MB Settings Page) | yes (free) | yes |
| Auto-generated Tuesday rota and nth-weekday rules | **yes** (built for it) | no | no (rows typed by hand) | no | no | no | no (code) |
| Field definitions live in | code (versioned) | DB, or Local JSON | DB/JSON | DB, or Local JSON | code or DB | DB | code |
| Package size (zip) | ~0.05 MB [I] | n/a | n/a | 5.5 MB | 1.4 MB | 2.7 MB | 0.9 MB |
| Recent security fixes (changelog) | our code only | 6.8.x hardening | same | 6.9.3–6.9.5 (Jul–Aug 2026) | minor escaping fixes | 3.3.9 "major security hardening" | 2.13.x sanitising fix (Wordfence report) |
| Editor-facing UX | exactly what we design | good, generic | good | good (ACF-identical) | good | busier | plain |
| Risk the editor changes a field definition | none (code) | admin menu can be hidden | same | same | same | same | none |

**Reading.**
- ACF free cannot hold the rota or any settings page without PRO [C, S22].
- Meta Box free cannot hold them without two premium extensions [C, S24].
- SCF is the only free framework with both repeaters and options pages [C, S23].
- Even SCF would give Frank a hand-typed list of date rows. It cannot generate the next 16 Tuesdays with their simplex, Winlink and GMRS notes, or compute "next meeting" from a rule. That logic is custom code anyway, so the framework adds a dependency without removing the code [I].

### 3.2 Events plugins

| | The Events Calendar (free) | Sugar Calendar Lite | Event Organiser | ICS Calendar (render the groups.io feed) | **Native `spk_event`** |
|---|---|---|---|---|---|
| Version, installs, tested | 6.17.5.1, 600k+, WP 7.1.2 | 3.14.0, 10k+, WP 7.1.2 | 3.12.10, 20k+, **tested to 7.0.0 only**; last update 2026-06-28 | 12.1.4.1, 10k+, WP 7.1.2 | n/a |
| Recurrence | **PRO only** | **Pro only** | free (complex rules, per-date exclusions) | whatever the source calendar does | not needed: nets and meetings are rules (item 3); events are one-offs |
| Weight | **19.3 MB zip**; venues, organisers, month/list/day views, REST endpoints | 8.3 MB; Stripe tickets, Zoom, registrations | small, single maintainer | small | none |
| Fits Option B's look | needs heavy template overrides of its views | same | moderate | limited styling | renders our own markup |
| Fields we need (type, sign-up route, extra form, tasks, call-sign contact, "keep in Past") | custom fields on top anyway | same | same | **no** (free-text feed) | built in |
| Security history (changelog) | several 2026 "hardened REST API permission checks" entries | "security improvements" entries | quiet | n/a | our code only |
| Plugin slot | 1 | 1 | 1 | 1, plus a live dependency on an external feed | 0 |

**Verdict:** none of these earns a slot.
- Our recurrence need is narrow: an nth weekday of the month, skipped months, and exceptions. `data.js` already implements it in about 60 lines (`matchesRule`, `nextMeetings`, `netOn`) [C].
- Everything else is a one-off event with club-specific fields [I].

### 3.3 Editing effort per task (clicks, not counting typing)

Counted from the screen designs in section 5; the other columns are [I] estimates from each product's standard screens.

| Task | **Native (recommended)** | Rota as one post per Tuesday (any CPT or field plugin) | SCF/ACF PRO options page + repeater | TEC PRO (recurring net) | groups.io calendar |
|---|---|---|---|---|---|
| Enter next month's net control (5 Tuesdays) | **3** (open Net rota, click the first box, Tab through, Save). Paste mode: 4 | ≈25 (5 × Add, date picker, publish, back) | ≈20 (5 × Add row + date picker) + Save; dates typed by hand, no auto notes | ≈30 (edit each occurrence, "this event only") | ≈25 |
| Add one exercise | **≈8** (Add event, kind, date picker, how to take part, Publish) | same | same | ≈10 + venue/organiser | ≈8, but no structured fields |
| Re-use last year's SET or Field Day | **3** (Duplicate, change date, Publish) | needs a duplicate plugin | same | PRO "duplicate" | copy by hand |
| Cancel or move one meeting | **≈6** (Schedule, date, which meeting, "Cancelled", Save) | n/a | ≈6 | edit one occurrence | ≈5, but Home still shows the date unless the site reads it |
| Upload a new version of the net script | **≈6** (open doc, Replace file, upload, Update); the public link is unchanged | n/a | ≈6; link changes unless coded | n/a | groups.io only |
| Mark 10 documents reviewed | **3** (tick, bulk "Mark reviewed today", Apply) | n/a | 10 × edit | n/a | n/a |
| Change the repeater tone | **3** (Nets & radio, field, Save); about 6 places update | n/a | 3 | n/a | n/a |

### 3.4 Verdict

- **Native, in `spokares-core`.**
- CPTs and meta belong in a plugin, not a theme. WordPress recommends it so "user content remains portable even if the theme is changed" [C, S8]. The theme guidelines treat custom post types and non-design meta boxes as "plugin territory" [C, S9].
- Size estimate [I]: about 1,500 lines of PHP, about 150 of JS (media picker, add/remove row, paste preview) and about 150 of admin CSS.
- It uses only long-stable core APIs:
  - `register_post_type`
  - `register_post_meta`
  - `add_meta_box`
  - the Settings API
  - list-table hooks
  - `register_block_type`

---

## 4. The recommended model (`spokares-core`)

### 4.1 Where things live

| Concern | Lives in | Notes |
|---|---|---|
| Post types, taxonomy, meta, options, admin screens, validation, the rule engine, `/calendar.ics`, `/docs/<slug>/` redirects, dynamic blocks and shortcodes (data and query) | plugin `spokares-core` | Survives a theme change. Not on WordPress.org, so the Enhance toolkit won't auto-update it. Deploy by SFTP; it changes rarely [I] |
| Markup and CSS for each dynamic block | theme `spokares` (`parts/dynamic/*.php`), with a fallback copy in the plugin | The plugin's render callback calls `locate_template()` first. The look stays in the theme [I] |
| Page layouts, patterns, locked sections | theme | Theme-lens decision; contentOnly locking helps [C, S4, S14] |

**Conventions.**
- Meta keys use the `spk_` prefix without a leading underscore, so they stay usable with block bindings later [C, S13]. No post type enables the generic "Custom Fields" box, so editors never see raw keys.
- Dates are stored as `Y-m-d` strings and times as `H:i` 24-hour strings, both in site time.
- Site timezone: `America/Los_Angeles`.
- Readers always see 12-hour times ("8:00 PM"), never "2000" or "22:00" (`data.js` conventions, D9).

### 4.2 Events: `spk_event`

**Registration** [I]:
- `public => false`, `show_ui => true`, `show_in_menu => true`, `publicly_queryable => false`, `has_archive => false`, `exclude_from_search => true`.
  - Events have no single pages. Each renders in place on the exercises page, anchored by its slug (`#set-2026`), which matches the mockup IDs.
- `show_in_rest => false`. This gives the classic edit form, because WordPress uses the block editor only for REST-enabled types [C, S11], and it adds no public REST endpoint [I].
- `supports => ['title', 'revisions']`, with `revisions_enabled` on the meta, so edit history covers the fields [C, S10].
- `capability_type => ['spk_event', 'spk_events']` and `map_meta_cap => true`.
- Menu position just under Dashboard; icon `dashicons-calendar-alt`.

**Fields** (all registered with `register_post_meta`, `single => true`, and a `sanitize_callback`):

| Meta key | Type | Editor control (label) | Validation | Used by |
|---|---|---|---|---|
| (post_title) | string | **Event name** | required, 80 characters or fewer | all |
| `spk_type` | enum `exercise`, `training`, `public-service`, `on-air` | **What kind** (drop-down) | required | which list it appears in |
| `spk_when` | enum `date`, `not-posted`, `as-requested` | **When**: "On a date", "Date not posted yet", "As requested" (radio) | | public-service rows ("Date not posted", "As requested") |
| `spk_start` | `Y-m-d` | **Start date** (date picker) | required if `date` | all lists; sort |
| `spk_end` | `Y-m-d` | **End date** (optional) | on or after start | ranges ("Sat–Sun, Sep 26–27"), "Happening now" |
| `spk_all_day` | bool | **All day** (checked by default) | | time display |
| `spk_time_start`, `spk_time_end` | `H:i` | **Start time / End time** (time pickers) | end after start | "Thu, Oct 15, 10:15 AM" |
| `spk_status` | enum `on`, `cancelled`, `postponed` | **Status** | | shows a tag |
| `spk_place` | enum `scem`, `on-air`, `other` | **Where**: "SCEM (1121 W Gardner Ave)", "On the air / from home", "Other" | | card and feed |
| `spk_place_text` | string | shown when "Other" | 80 characters or fewer | |
| `spk_summary` | string | **Short line for lists** | 90 characters or fewer; plain text | "send a DYFI report by Winlink, marked as an exercise" |
| `spk_details` | limited HTML | **Details for the "Next up" card** (small visual editor: bold, link, bulleted and numbered list only) | `wp_kses` with `p`, `strong`, `em`, `a[href]`, `ol`, `ul`, `li`, `abbr[title]` | SET task list |
| `spk_bring` | string | **Also bring** (beyond the usual kit) | 120 characters or fewer | card |
| `spk_extra_doc` | int (a `spk_document` ID) | **Extra form** (drop-down of Documents) | must be a published document | WSDOT 580-020 |
| `spk_join` | enum `groupsio`, `callsign`, `link`, `none` | **How members take part** (radio) | | card button; "Volunteer through" |
| `spk_join_url`, `spk_join_label` | url, string | shown for `groupsio` (defaults to the group URL) or `link` | https only | "Exercise details on groups.io" |
| `spk_join_call` | string | shown for `callsign` | call-sign pattern (section 9) | "NV2Z" |
| `spk_links` | array of {label, url}, 3 or fewer | **More links** (rows) | https only | "ARRL SET forms" |
| `spk_keep_past` | bool | **After it ends, keep it in "Past exercises"** | | `#past` |
| `spk_precision` | enum `day`, `month`, `year` | hidden under "Advanced" (for imported history like "2025" or "Oct 2022") | | `#past` |
| `spk_sort` | `Y-m-d` | none (computed on save; undated items get `9999-12-31`) | | admin sort and queries |

- **Publish status is the "verify" flag.** Unconfirmed events stay **Draft** or **Pending Review** and never render. The mockups' `data-verify` never reaches production [I].
- **Expiry is automatic.** An event drops out of the upcoming lists the day after its end date. Nothing is deleted.
- **No recurrence on events** [I]. The yearly repeats (SET, ShakeOut, Field Day) use **Duplicate**.

### 4.3 Net-control rota and Winlink assignments: option `spk_rota`

```text
spk_rota = {
  "2026-09-29": { "call": "NZ2S",  "open": false, "note": "" },
  "2026-10-06": { "call": "",      "open": true,  "note": "" },
  "2026-10-13": { "call": "AE7RJ", "open": false, "note": "",
                  "wl_task": "Did You Feel It report (ShakeOut practice)", "wl_form": "DYFI" },
  ...
}
spk_rota_saved = { "at": "2026-09-26T10:14:00-07:00", "by": <user ID> }
```

- **Three states per Tuesday**, matching `site.js`:
  - a call sign
  - **Open** (tick "Open, needs a volunteer")
  - blank, which renders "Not yet published"
- **Notes are computed, not typed.**
  - The Simplex, Winlink night and GMRS notes come from the net rules in `spk_schedule` (4.4), exactly as `fn.netOn()` does today.
  - The per-row `note` field is only for an exception, for example "No net: county exercise".
  - The Winlink columns appear only on Winlink-night rows.
- **Retention:** entries older than 12 months are pruned on save [I].
- **Why an option and not posts:** a month is 4 or 5 tiny values edited together. One form and one save is the natural unit (section 3.3).

### 4.4 Meetings, nets and one-off changes: option `spk_schedule`

```text
spk_schedule = {
  "weekly_net": { "weekday": 2, "time": "20:00" },
  "net_variants": [                       // drive the rota Note column and How it works "Other nets"
    { "id": "winlink-nights", "label": "Winlink night", "nth": [2,4], "weekday": 2, "link": "winlink-assignments" },
    { "id": "simplex-5th",   "label": "Simplex",       "nth": [5],   "weekday": 2, "link_doc": "net-scripts",
      "text": "Starts on simplex, then moves to W7GBU." },
    { "id": "gmrs",          "label": "GMRS net, 7:30 PM", "nth": [3], "weekday": 2, "time": "19:30" }
  ],
  "meetings": [
    { "id": "workshop", "name": "Second Saturday Workshop", "nth": [2], "weekday": 6, "start": "09:00", "end": "12:00",
      "place": "scem", "show_home": true, "active": true },
    { "id": "winlink-workshop", "name": "Winlink workshop", "nth": [2], "weekday": 6, "start": "12:30", "end": "15:30", ... },
    { "id": "third-thursday", "name": "Third Thursday training meeting", "nth": [3], "weekday": 4,
      "start": "", "end": "", "time_text": "Evening", "skip_months": [12], ... }
  ],
  "changes": [
    { "date": "2026-11-14", "series": "workshop", "kind": "moved", "new_date": "2026-11-21", "new_start": "", "new_end": "", "note": "" }
  ]
}
```

- `kind` is one of `cancelled`, `moved`, `retimed` or `note`. A `moved` change also produces its new date in "next meeting" lists. Past changes stop showing on the screen after their date.
- **The rule engine** is a PHP port of `data.js` `matchesRule`, `nextMeetings`, `netOn` and `upcoming`, with the `changes` list applied [I].
  - `nth` 5 means the 5th weekday of the month, so the next 5th Tuesday is 2026-12-29 (D13 #5).
  - `skip_months` covers the unconfirmed "no December meeting" (D13 #10).
  - `time_text` covers "Evening" until D13 #4 is settled; once it is, the editor enters the time.
- **Hard-coded exclusions, not editable:**
  - The 1st-Tuesday staff meeting stays off unless D13 #16 is answered.
  - Net variants never take a frequency field, because simplex and GMRS details await D13 #5 and the owner's OK (`data.js` "deliberately NOT in this file").

### 4.5 Documents: `spk_document` plus `spk_doc_cat`

**Post type registration** [I]:
- `public => false` and `publicly_queryable => false`.
  - Rows render on the Documents page, anchored by slug (`#ics-213`).
  - Each document also gets a stable redirect at **`/docs/<slug>/`**: a 302 to the current file, the external URL, or the library row for "Soon" items.
  - Hub tiles and inline links use `/docs/<slug>/`, which also fixes the round-3 note that tiles "open library rows, not the files" (REVIEW.md, shared fix 2).
- `supports => ['title', 'page-attributes', 'revisions']`. `menu_order` is the position within a category.
- `show_in_rest => false`. Icon `dashicons-media-document`.

**Taxonomy** `spk_doc_cat`:
- `public => false`, `show_ui => true`, `show_admin_column => true`. Seeded with the 7 terms from `data.js`: net-ops, join, forms, training, readiness, digital, reference.
- Term meta `spk_order` sets the page order. The edit screen shows one category drop-down.
- Only administrators can add or rename terms (`manage_terms`, `edit_terms`, `delete_terms` set to `manage_options`; `assign_terms` set to `edit_spk_documents`).

**Fields:**

| Meta key | Control (label) | Notes / validation |
|---|---|---|
| (post_title) | **Document name** | 90 characters or fewer |
| `spk_source` | **Where the file is**: "Upload a file (hosted here)", "Link to another site", "Not available yet (shows "Soon")" | |
| `spk_file` | **Choose or upload file** (WordPress media picker, `wp_enqueue_media`) | Allowed types: PDF, DOCX, XLSX, PPTX. File-size cap about 25 MB [I]. Format and size are taken from the file |
| `spk_url` | **Web address** | https only (`esc_url_raw` with `['https']`) |
| `spk_source_label` | **Source shown on the page** | Pre-filled from the domain: fema.gov → "FEMA", arrl.org → "ARRL", groups.io → "groups.io, members", spokanecounty.gov → "Spokane County" |
| `spk_format` | **Format** (drop-down: PDF, DOCX, XLSX, Online form, Online course, Video, Winlink form, Software, Web page) | automatic for uploads |
| `spk_version` | **Version or date** ("v3", "2026-03-04", "July 2024") | 30 characters or fewer |
| `spk_note` | **Short note** (8 words or fewer) | 60 characters or fewer; plain text |
| `spk_howto_label`, `spk_howto_url` | **"How to" link** (optional) | "How to write one" |
| `spk_extra` | **Extra links or files** (up to 6 rows: label, full title, URL or file, version) | FEMA IS-100/200/700/800 links; the 3 net scripts once hosted here |
| `spk_most_used` | **Show as a "Most used" tile on the members page** | Only the first 4 by position render. The screen warns if more than 4 are ticked |
| `spk_tile_label`, `spk_tile_icon` | **Tile label** ("Net script") and **icon** (script, form, log, book) | shown when Most used is ticked |
| `spk_keywords` | **Search words** (comma-separated) | feeds the page's search |
| `spk_owner` | **Who keeps it current** (role or call sign) | D10 |
| `spk_reviewed` | **Last reviewed** (date) | D10. The list shows it red after 12 months |

**Upload hygiene** [I]:
- Restrict `upload_mimes` [C, S19] to PDF, DOCX, XLSX, PPTX, JPEG, PNG and WebP. No SVG, no executables.
- Cap the upload size. Large audio and video go to archive.org or YouTube (D10, AUP 3.1).
- Keep ISO-dated file names (D10); the upload screen shows the rule.
- New WordPress installs redirect attachment pages to the file (`wp_attachment_pages_enabled = 0`) [C, S18], so uploads create no thin pages.

### 4.6 Radio, place and key links: options `spk_radio`, `spk_place`, `spk_links`

| Option | Fields | Validation / guard |
|---|---|---|
| `spk_radio` | `primary` {call W7GBU, freq 147.300, offset +600 kHz, tone 100.0}; `alternate` {freq 146.880, offset −600 kHz, tone 123.0, `show` (bool; off until D13 #9 is answered)}; `noaa` {call WXL86, freq 162.400} | Frequency pattern `^\d{2,3}\.\d{3}$`. Offset and tone chosen from lists (standard CTCSS tones). A red banner: "Amateur frequencies only. Never county, hospital, SHARES, 800 MHz or channel numbers" (D13). The screen shows a live preview of the copy line: "W7GBU 147.300 MHz, +600 kHz offset, 100 Hz tone" |
| `spk_place` | name, street, city, state, ZIP (SCEM, 1121 W Gardner Ave, Spokane WA 99260) | used by Home `#visit`, meetings and the feed |
| `spk_links` | groups.io main URL, groups.io files URL, role addresses (join@, ec@, webmaster@; D11 proposals) | https or `mailto:` only; role addresses only, never personal |

---

## 5. Admin screens (exact)

### 5.1 Menu for the Editor role (top to bottom)

1. **Dashboard: Start here.** A custom widget with:
   - Five large buttons: **Update the net rota**, **Add an event**, **Add a document**, **Change a meeting date**, **Edit a page**.
   - **What the site shows now**: next net and its net control; the next 2 events; the next meeting.
   - **Needs attention**:
     - net control set for fewer than 3 weeks ahead
     - events in Draft
     - documents not reviewed in 12 months
     - "Soon" rows
     - "Most used" set to more than 4
   - The default WordPress news, Site Health and Quick Draft widgets are removed for editors [I].
2. **Net rota** (`dashicons-microphone`)
3. **Events**: All events · Add event
4. **Documents**: All documents · Add document · Categories (admins only)
5. **Schedule** (`dashicons-clock`): Meetings & changes · Nets & radio
6. **Pages** (native; the layout is locked by the theme)
7. **Media** (native; for photos. Documents upload through the Document form)

**Hidden:**
- Posts, unless the IA adds news.
- Comments, disabled site-wide.
- Tools.
- Appearance, Plugins, Users and Settings, which Editors don't have.

### 5.2 Events

**List** (`edit.php?post_type=spk_event`):
- Views: **Upcoming** (the default: `spk_sort` on or after today, ascending), **Past** (descending), **Drafts**.
- Columns:
  - Event name
  - What kind
  - **When** ("Sat, Oct 3"; "Sep 26–27"; "Date not posted"). **Sortable** by `spk_sort` via `manage_edit-spk_event_sortable_columns` [C, S16].
  - How to take part
  - Status
- A filter drop-down for "What kind".
- Row actions: Edit · **Duplicate** · Trash · **View on site** (goes to `/members/exercises/#<slug>`).
- Quick Edit: date, status and kind, through `quick_edit_custom_box` with a small script to pre-fill values [C, S16].

**Edit form** (classic screen, postboxes in this order; the last two are collapsed by default):

```text
Add event                                               ┌ Publish ─────────────┐
Event name [ Simulated Emergency Test               ]   │ Save draft  Preview  │
                                                        │ Status: Draft        │
┌ When ─────────────────────────────────────────────┐   │ [ Publish ]          │
│ (•) On a date  ( ) Date not posted yet  ( ) As requested  └──────────────────────┘
│ Start date [2026-10-03 📅]   End date [          📅]    ┌ What kind ───────────┐
│ [x] All day      Start time [--:--]  End time [--:--]  │ [Exercise        ▾]  │
└────────────────────────────────────────────────────┘   │ Status [On      ▾]   │
┌ Where ─────────────────────────────────────────────┐   └──────────────────────┘
│ (•) SCEM, 1121 W Gardner Ave  ( ) On the air / from home  ( ) Other [        ]
┌ Short line for lists (90 characters) ──────────────┐
│ [ ARRL's national exercise                         ]
┌ How members take part ─────────────────────────────┐
│ (•) Details on groups.io   [https://spokaneares-acs.groups.io/g/main]
│ ( ) Volunteer through call sign [      ]
│ ( ) A link   Label [        ] Address [https://   ]
│ ( ) Nothing needed
┌ Details for the "Next up" card ▸ (B I link • 1.) ──┐
┌ Extra form ▸ [— none — ▾]   Also bring ▸ [       ] ┐
┌ More links ▸  [label][https://…]  + Add link ──────┐
┌ After it ends ▸ [ ] Keep it in "Past exercises" ───┐
┌ Never publish ─────────────────────────────────────┐
│ No county, hospital, SHARES or 800 MHz channels.   │
│ No personal names, phone numbers or e-mails.       │
└────────────────────────────────────────────────────┘
```

### 5.3 Net rota (one screen)

```text
Net rota                                    Last saved Sat Sep 26, 10:14 AM by AG7QP
Tuesday net, 8:00 PM. Call signs only; no names. Blank = "Not yet published".

 Tuesday     This week (automatic)   Net control   Open?   Winlink assignment              Form
 ─────────── ─────────────────────── ───────────── ─────── ─────────────────────────────── ─────────
 Tue Sep 22  (last week, read-only)   KE7RAP
 Tue Sep 29  5th Tuesday: simplex     [NZ2S     ]   [ ]
 Tue Oct 6                            [         ]   [x]
 Tue Oct 13  Winlink night            [AE7RJ    ]   [ ]     [Did You Feel It report (…)  ]   [DYFI    ]
 Tue Oct 20  GMRS net 7:30 PM         [WA7LNC   ]   [ ]
 Tue Oct 27  Winlink night            [         ]   [x]     [A welfare message           ]   [Welfare…]
 …          (16 weeks; "Show 13 more weeks")
 Exception note per row: ▸ (e.g., "No net: county exercise")

 [ Save rota ]

 ▸ Paste a list instead
   [ October 13 - AE7RJ, Randall Jones                 ]
   [ October 27 - Open                                 ]
   [ Preview ]  →  shows the parsed rows, names removed, unknown lines flagged  →  [ Save ]
```

- **Call-sign input:**
  - Upper-cased as you type.
  - Validated against the pattern in section 9.
  - A `<datalist>` suggests call signs used before, so there is no typing twice and fewer typos.
- **The paste parser:**
  - Accepts "October 13 - AE7RJ, Name", "Oct 13 AE7RJ", "2026-10-13 AE7RJ", "13-Oct-26 …" (the Winlink list format) and "Open".
  - Keeps only the call sign, which enforces the no-names rule (D14).
  - Rejects any date that is not a Tuesday.
- **Concurrency:** the save posts the screen's load timestamp. If someone saved since, the screen says so and shows both versions instead of overwriting (7.1 has no real-time collaboration [C, S6]) [I].

### 5.4 Schedule → Meetings & changes

```text
Meetings & changes
1  Changes coming up                                  (the frequent edit)
   Date [2026-11-14]  Meeting [Second Saturday Workshop ▾]
   Change [Cancelled ▾ | Moved to… | New time… | Note only]  New date [   ]  New time [  ]–[  ]  Note [          ]
   (existing future changes listed; 3 blank rows; past ones drop off)
2  Regular meetings                                   (rarely changes)
   Name                             Week   Day        Start  End    Time text   Skip months   Show on Home  Active
   [Second Saturday Workshop    ]   [2nd▾] [Sat ▾]    [09:00][12:00] [        ] [          ]   [x]           [x]
   [Winlink workshop            ]   [2nd▾] [Sat ▾]    [12:30][15:30] …
   [Third Thursday training mtg ]   [3rd▾] [Thu ▾]    [     ][     ] [Evening ] [Dec       ]   [x]           [x]
   Preview: next 3 dates for each, with changes applied
[ Save ]
```

### 5.5 Schedule → Nets & radio (capability `spk_edit_radio`)

- The weekly net (day and time) and the three net variants: label, which weeks, time, optional link.
- The `spk_radio`, `spk_place` and `spk_links` fields from 4.6, with the never-publish banner and the copy-line preview.

### 5.6 Documents

**List:**
- Columns: Document · Category (filter drop-down) · **Source** (File / Link / Soon) · Format · Version · ★ Most used · **Reviewed** (sortable; red after 12 months) · Position.
- Quick Edit: Position (native for `page-attributes`), Most used and Reviewed.
- **Bulk actions** [C, S16]:
  - **Mark reviewed today**
  - **Move to category…**

**Edit:**
- The fields in 4.5. The source radio shows either the file picker or the URL field.
- A **Replace file** button beside the current file (name, size, upload date).
- The slug ("anchor") is read-only after publishing for non-admins. Changing it would break `#anchors` and `/docs/<slug>/` links [I].

### 5.7 Pages

- Page text is edited in the block editor. The theme's templates and patterns lock the layout (`template_lock` or contentOnly) [C, S14].
- Every dynamic block sits in the page with `lock: { move: true, remove: true }`. The editor sees a live server-rendered preview and a note "Edit this list in: Net rota" linking to the right screen [I].
- PHP-only blocks (WP 7.0) make these previews possible without a JS build [C, S5].

---

## 6. Roles and permissions

| Who | Role | Can edit |
|---|---|---|
| Technical admin (Verpex account holder) | Administrator | everything |
| Two named editors (D5; likely AG7QP plus one) | **Editor**, plus our capabilities `spk_edit_rota`, `spk_edit_schedule`, events and documents; `spk_edit_radio` for one of them | pages, events, documents, rota, schedule; radio only if granted |
| Optional: a rota helper | custom role **Net scheduler** (`read`, `spk_edit_rota`, events capabilities) | the rota and events only; no pages, no documents |

- **Set `DISALLOW_UNFILTERED_HTML` to true.** On a single site the Editor role otherwise has `unfiltered_html` [C, S15]. On a site that was hacked, editors should not be able to paste scripts [I].
- 2FA for every account and SSO through Enhance Collaborator access are for the security lens. See section 10.3 for the plugin-slot implication.

---

## 7. How the front end reads the data (render contract)

- **Server-rendered first.**
  - Every list is PHP output with Option B's existing markup and classes, so it works without JavaScript, is indexable, and is cached by LSCache.
  - `site.js` keeps only enhancements: copy buttons, doc search and filter, deep-link highlights.
  - The plugin prints one small inline `window.SPOKARES` object with only what the scripts need: the document search index and `meta.asOf`. Its shapes match `data.js` ("Keep the shapes; swap the source", `data.js` header) [C, I].
- **Blocks.** Registered with `autoRegister` (WP 7.0+) [C, S5]:

  | Block | Attributes |
  |---|---|
  | `spokares/rota` | `weeks` = 5 |
  | `spokares/upcoming` | `types`, `limit`, `days` |
  | `spokares/next-up` | |
  | `spokares/event-table` | |
  | `spokares/public-service` | |
  | `spokares/past` | |
  | `spokares/winlink` | |
  | `spokares/meetings` | |
  | `spokares/next-meeting` | `id` |
  | `spokares/other-nets` | |
  | `spokares/radio-box` | |
  | `spokares/netbar` | |
  | `spokares/doc-library` | |
  | `spokares/quick-links` | |
  | `spokares/asof` | |

  - Each has a plain PHP helper for templates, for example `spokares_radio('primary.freq')` and `spokares_next_meeting('workshop')`.
- **Inline values inside prose** such as "…8:00 PM, 147.300 MHz…" use shortcodes (`[spk_radio part="primary"]`, `[spk_net_time]`) placed by the theme's locked patterns, so editors don't have to type them.
  - Block bindings (`register_block_bindings_source`) can bind whole attributes, such as a paragraph's content or a button's URL [C, S13], but not a fragment inside a sentence. Shortcodes are the practical tool for fragments [I].
- **Review fixes** that the render layer applies once (REVIEW.md):
  - "Open" rota tags link to the "Open slot? Tell the Net Manager" line.
  - The "Simplex" note links to the net script.
  - Hub tiles link to `/docs/<slug>/`.
  - The rota comes first on phones.
- **"Today" and caching** [I]:
  - `today` comes from `current_datetime()` in site time.
  - Date-dependent output ("Happening now", the next net, "Next: Oct 10") goes stale in a page cache. So `spokares-core`:
    - purges the page cache on every save of the rota, schedule, events or documents (the `litespeed_purge_all` action when LSCache is active)
    - schedules a daily purge at 00:05 Pacific with `wp_schedule_event` (Enhance runs WP-Cron hourly, so this fires between 00:05 and about 01:05)
  - The hub's "as of" line shows the rota's last-saved date, not the time of the request.
- **Calendar feed `/calendar.ics`** (RFC 5545, `TZID=America/Los_Angeles`) [I, S33]:
  - weekly nets for the next 12 weeks, with net control in the description
  - meetings with changes applied
  - published events
  - only public data, the same data the pages already show
  - served with a 1-hour `Cache-Control` header

---

## 8. Source of truth: WordPress vs the groups.io calendar

**Recommendation: WordPress is the single source for the rota, events, meetings and radio facts shown on the site.** This supersedes D9 option 1. D9 needs updating, and AG7QP and the groups.io owner decide.

Why [C where cited, otherwise I]:
1. **The owner asked for it.** Events and the rota are rendered by the theme and kept up to date from WordPress admin.
2. **D9's precondition never cleared.** Whether groups.io publishes a public ICS feed is still unconfirmed (D13 #20), and the calendar page serves no events without script (`organization.md` §12).
   - groups.io calendars are available only in Premium, Enterprise and legacy Free groups [C, S31].
   - The owner chooses who can view them: public, members, or moderators [C, S31].
3. **Structure and safety.** groups.io events are free text. The site needs a type, a sign-up route, an extra form, call-sign-only contacts and "keep in Past", and it needs guards against FOUO text. The interim list already shows the risk ("Hospital Net on Channel 6") [C, interim page].
4. **No plugin and no runtime dependency.** Rendering a remote ICS feed would take a slot (for example ICS Calendar [C, S30]) plus an external fetch on a host whose IP changes often (`verpex-enhance.md` §4.5).

**How the two coexist without double entry:**
- **The site publishes `/calendar.ics`.** Members add it to Google, Apple or Outlook once and get every net, meeting and event, including who is net control.
- **Each event carries a groups.io link** ("Exercise details on groups.io") when there is sign-up or discussion. RSVPs and logistics stay there.
- **Optional mirroring, by hand and only for RSVP events.**
  - groups.io adds an event to the group calendar when the group's address is invited to an outside calendar event, and it e-mails members [C, S31].
  - Moderators can use this for the few exercises that need RSVPs. Do **not** mirror nets or meetings.
  - We found no documented way for a group calendar to subscribe to an outside feed, so automatic sync is not assumed [I].
- **Retire the JCal rota and the hand-typed ag7qp list at launch** (D7, D9). Afterwards, one screen is the only place the rota exists.

---

## 9. Guard rails (cheap, and they matter here)

| Guard | Where | Behaviour [I] |
|---|---|---|
| Call-sign format | rota, event contact, `spk_owner` | Upper-case; US pattern `^[AKNW][A-Z]?[0-9][A-Z]{1,3}$`, which matches W7GBU, NZ2S, AE7RJ and KA7LJQ. Reject anything with spaces (usually names) |
| Names stripped | rota paste | The parser keeps only the call sign |
| PII check | every free-text field | Refuse to save text with an e-mail address (other than the `@spokares.org` role addresses) or a phone number, and say why |
| Never-publish check | event summary, details and notes; document notes | A warning, and the item stays Draft until confirmed, if text matches `Hospital Net`, `channel \d`, `SHARES`, `800 MHz`, `talkgroup` or a MHz figure outside the `spk_radio` list |
| Dates | events, changes, rota | End on or after start; rota and variant dates must be Tuesdays; times are 24-hour in storage and 12-hour on display |
| URLs | all | https only (plus `mailto:` for role addresses); no `javascript:` |
| Uploads | documents | Allowed file types only, a size cap, no SVG |
| Nonces and capability checks | every save | Standard `check_admin_referer` and `current_user_can` on every handler [C, S34] |

---

## 10. Maintenance and security burden

### 10.1 Our code

- **Surface.** Admin-only write paths, no front-end forms, no REST endpoints for our types (`show_in_rest => false`), and output escaping on every field (`esc_html`, `esc_url`, `esc_attr`, `wp_kses`) [I, S34].
- **Churn.** The APIs used have been stable for years, so a WordPress upgrade is unlikely to break them [I]. 7.1's always-iframed post editor does not touch classic screens or settings pages [C, S7].
- **Updates.** No auto-update. The technical admin deploys by SFTP (`verpex-enhance.md` §4.1), and changes will be rare.
- **Runbook check.** Before each major WordPress release, clone to Enhance staging and click through the five screens (about 10 minutes) [I].

### 10.2 What a field or events plugin would add

- A monthly stream of security releases: SCF 6.9.3–6.9.5, Pods 3.3.9, TEC's repeated REST permission hardening [C, S23, S25, S27]. Auto-updates absorb them, but the surface remains.
- One of the four slots.
- Paid licences for ACF PRO or the Meta Box extensions, which a volunteer group must remember to renew [C, S22, S24].

### 10.3 Plugin budget left for the other lenses

Four slots remain. Candidates, for the security and hosting lenses to choose from:
- SMTP (FluentSMTP or WP Mail SMTP)
- a form plugin, only if D11 adds a form
- 2FA
- LiteSpeed Cache or Redirection

**Flag:** the WordPress.org **Two Factor** plugin was last updated 2026-03-27 and is tested only to WordPress 6.9.9 [C, S32]. Check before relying on it on 7.1.

---

## 11. Portability, backup and seeding

- **Posts and meta** (events, documents, categories) export through Tools → Export (WXR). **Options** (rota, schedule, radio, place, links) do not.
  - Each settings screen gets **Export JSON / Import JSON** buttons, and the plugin provides a `wp spokares export` WP-CLI command for off-host backups (D5 "off-host backups") [I].
  - A full database dump covers both anyway.
- **Seeding.** One `seed.json` generated from `_shared/assets/data.js` creates:
  - the 10 dated events, the 2 undated public-service rows on the exercises page (Torchlight Parade, "Fun runs and bike races") and the 8 past exercises
  - 37 documents in 7 categories
  - the rota, Winlink assignments, meetings, nets and radio settings

  It feeds the dev Playground blueprint (so local screenshots show real content) and the first production install. Items flagged `verify: true` import as **Draft** [I].
- **Leaving WordPress later:** the data is plain rows and JSON in documented shapes, the same shapes as `data.js` [I].

---

## 12. Decisions and questions

| # | Question | Who | Default until answered |
|---|---|---|---|
| 1 | Accept WordPress as the source of truth for the rota, events and meetings, and retire the groups.io-calendar option in D9? | AG7QP, groups.io owner, EC | Build as recommended; groups.io links per event |
| 2 | Will AG7QP use the rota screen or the paste box? A 10-minute test on the dev site settles it | AG7QP | build both |
| 3 | Who may edit radio settings (`spk_edit_radio`)? | EC | administrator plus one named editor |
| 4 | Is a "Net scheduler" role needed for a third volunteer? | AG7QP | not created |
| 5 | Should the ICS feed include net control call signs? | AG7QP | yes; they are already public on the hub |
| 6 | Retention for past events: keep all, or auto-trash non-"Past" events after 24 months? | EC | keep all |
| 7 | D13 #4, #5, #9, #10, #16 still gate the Third Thursday time, the simplex details, the alternate repeater, December and the staff meeting | EC, AG7QP | as configured in 4.4 and 4.6 (hidden or "Evening") |

---

## 13. Sources (web, checked 2026-09-26)

- **S1** WordPress core version API (current 7.1.2): https://api.wordpress.org/core/version-check/1.7/
- **S2** WordPress 7.0 (released 2026-05-20): https://wordpress.org/documentation/wordpress-version/version-7-0/
- **S3** WordPress 7.1 (released 2026-08-19): https://wordpress.org/documentation/wordpress-version/version-7-1/
- **S4** WordPress 7.0 Field Guide (contentOnly, block bindings, DataViews): https://make.wordpress.org/core/2026/05/14/wordpress-7-0-field-guide/
- **S5** PHP-only block registration (7.0 dev note): https://make.wordpress.org/core/2026/03/03/php-only-block-registration/
- **S6** WordPress 7.1 Field Guide (real-time collaboration "not enabled in the final release"): https://make.wordpress.org/core/2026/08/05/wordpress-7-1-field-guide/
- **S7** Iframed editor changes in 7.1 (always iframed, including with meta boxes): https://make.wordpress.org/core/2026/08/03/iframed-editor-changes-in-wordpress-7-1/
- **S8** Plugin Handbook, Registering custom post types ("put custom post types in a plugin rather than a theme"): https://developer.wordpress.org/plugins/post-types/registering-custom-post-types/
- **S9** Theme Handbook, required review items (plugin territory: CPTs, non-design meta boxes): https://make.wordpress.org/themes/handbook/review/required/
- **S10** `register_meta()` (`revisions_enabled`, `show_in_rest`): https://developer.wordpress.org/reference/functions/register_meta/ and https://developer.wordpress.org/reference/functions/register_post_meta/
- **S11** `use_block_editor_for_post_type()` (returns false when the type is not `show_in_rest`): https://developer.wordpress.org/reference/functions/use_block_editor_for_post_type/
- **S12** Block Editor Handbook, Meta Boxes (compatibility): https://developer.wordpress.org/block-editor/how-to-guides/metabox/
- **S13** Block Bindings API (custom sources; post-meta requirements): https://developer.wordpress.org/block-editor/reference-guides/block-api/block-bindings/ and https://developer.wordpress.org/reference/functions/register_block_bindings_source/
- **S14** Block templates and `template_lock` (`all`, `contentOnly`): https://developer.wordpress.org/block-editor/reference-guides/block-api/block-templates/
- **S15** Roles and Capabilities (Editor has `unfiltered_html` on single site): https://wordpress.org/documentation/article/roles-and-capabilities/
- **S16** List-table hooks: https://developer.wordpress.org/reference/hooks/manage_post_type_posts_columns/, https://developer.wordpress.org/reference/hooks/manage_this-screen-id_sortable_columns/, https://developer.wordpress.org/reference/hooks/quick_edit_custom_box/, https://developer.wordpress.org/reference/hooks/bulk_edit_custom_box/
- **S17** Settings API: https://developer.wordpress.org/plugins/settings/settings-api/
- **S18** Changes to attachment pages (`wp_attachment_pages_enabled`, 0 on new sites): https://make.wordpress.org/core/2023/10/16/changes-to-attachment-pages/
- **S19** `upload_mimes` filter: https://developer.wordpress.org/reference/hooks/upload_mimes/ · media picker `wp_enqueue_media()`: https://developer.wordpress.org/reference/functions/wp_enqueue_media/
- **S20** `wp_schedule_event()`: https://developer.wordpress.org/reference/functions/wp_schedule_event/
- **S21** WordPress.org plugin API (versions, installs, "tested up to", changelogs), queried per slug: `https://api.wordpress.org/plugins/info/1.2/?action=plugin_information&request[slug]=<slug>`
- **S22** ACF (PRO features: Repeater, Options Page, Flexible Content, Blocks): https://wordpress.org/plugins/advanced-custom-fields/ · ACF PRO pricing ($49/yr for 1 site): https://www.advancedcustomfields.com/pro/
- **S23** Secure Custom Fields: https://wordpress.org/plugins/secure-custom-fields/ · features (repeater, flexible content, clone): https://developer.wordpress.org/secure-custom-fields/features/ · repeater: https://developer.wordpress.org/secure-custom-fields/features/fields/repeater/ · options page tutorial: https://developer.wordpress.org/secure-custom-fields/tutorials/first-options-page/ · readme and changelog: https://raw.githubusercontent.com/WordPress/secure-custom-fields/trunk/readme.txt
- **S24** Meta Box: https://wordpress.org/plugins/meta-box/ · Meta Box Lite: https://metabox.io/lite/ · MB Settings Page ($49): https://metabox.io/plugins/mb-settings-page/ · MB Group ($49): https://metabox.io/plugins/meta-box-group/
- **S25** Pods: https://wordpress.org/plugins/pods/
- **S26** CMB2: https://wordpress.org/plugins/cmb2/
- **S27** The Events Calendar (recurring events need Events Calendar Pro): https://wordpress.org/plugins/the-events-calendar/
- **S28** Sugar Calendar Lite (recurring events are Pro): https://wordpress.org/plugins/sugar-calendar-lite/
- **S29** Event Organiser (free recurrence; tested to 7.0.0): https://wordpress.org/plugins/event-organiser/
- **S30** ICS Calendar (renders any public iCalendar feed): https://wordpress.org/plugins/ics-calendar/
- **S31** groups.io Help Center:
  - About group calendars (Premium, Enterprise and legacy Free only): https://groups.io/helpcenter/manual/membersmanual/calendars/calendars_about.htm
  - Subscribing: https://groups.io/helpcenter/manual/membersmanual/calendars/calendar_subscribing.htm
  - Adding events from outside invitations: https://groups.io/helpcenter/manual/membersmanual/calendars/calendar_events_from_outside_invitations.htm
  - Calendar settings (view and edit permissions): https://groups.io/helpcenter/manual/ownersmanual/groupsettings/settings_calendar.htm
- **S32** Two Factor (tested to 6.9.9): https://wordpress.org/plugins/two-factor/
- **S33** RFC 5545, iCalendar: https://www.rfc-editor.org/rfc/rfc5545
- **S34** WordPress security APIs (nonces, sanitising, escaping): https://developer.wordpress.org/apis/security/

**Local sources:**
- `research/decisions.md` (D5, D7, D8, D9, D10, D11, D13, D14)
- `research/hosting/verpex-enhance.md` (§4.1–§4.5, §5.3)
- `research/organization.md` §12
- `research/content/interim-ag7qp/spokane-county-ares-acs.md`
- `design/round3/_shared/assets/data.js`
- `design/round3/_shared/placement-map.md` (§1B, §1C, §1H, §1I)
- `design/round3/option-b-lines-down/` (pages, `assets/site.js`)
- `design/round3/REVIEW.md`
