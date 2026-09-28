# spokares.org on WordPress: plan and build contract (Option B, "Carry the Message")

- **Prepared:** 2026-09-26 by the WordPress architect, for the owner's direction: *"This is going to be a WordPress theme. Understand what parts will and won't work well as a theme, how dynamic content will work, what WordPress setup makes editing dynamic content simple... Build a plan, then redesign Option B to work well as a WordPress theme while keeping the feel."*
- **Revision 2 (same day):** rewritten after three reviews: WordPress core (read against the 7.1.2 source), volunteer editor, and security and hosting. Every "must" item is accepted. §8 records each response and the few places where this plan chose a different fix.
- **Owner decisions after the QA sweep (2026-09-27), built in 0.1.1:** QA-038, the groups.io addresses are no longer settings (Settings › ARES site holds only the meeting place; the links stay page text and theme parts); QA-039, W7GBU is the club's fixed call sign, so the Net details call-sign field is administrators only, enforced on save; QA-103, the header's markup order is its drawn order at every width (two "Join the team" buttons, §6.7 Parts); QA-104, the members hub has the Tuesday net first in the markup at every width (§6.9). None of the four uses CSS reordering. The sections below are updated to match.
- **Status:** this is the decision record, and §6 is the **build contract** for four parallel builders (theme, plugin, patterns, dev environment and ops), followed by an integration step (§6.13). Where this file and a research note disagree, this file wins, except that research `security-hosting.md` §9.2 and §11 are **binding** with the exceptions listed in §5.11. Where it and the round-3 design disagree, this file wins only where it says so (§2.2, §2.3).
- **Inputs (read for detail, not for decisions):**
  - Design: `design/round3/option-b-lines-down/` (six pages, `assets/site.css`, `assets/site.js`, `DESIGN.md`, `README.md`, `shots/`), `design/round3/_shared/` (`copy-*.md`, `placement-map.md`, `assets/data.js`), `design/round3/REVIEW.md`.
  - Research: `wordpress/research/{architecture,components,content-model,editor-ux,security-hosting}.md`, `wordpress/research/reference/{hardening.php,smtp.php}`, `wordpress/research/probes/`, `research/redirects.csv`.
  - Project: `research/decisions.md` (D1, D5, D8-D15), `research/hosting/verpex-enhance.md`, `research/organization.md`.
- **Platform facts (checked 2026-09-26):** WordPress **7.1.2** is current (7.0 shipped 2026-05-20, 7.1 on 2026-08-19); theme.json is still v3. Verpex Enhance offers PHP 5.6 to 8.5 (default 8.3); the web server is unconfirmed (probably the LiteSpeed family); WP-Cron is replaced by an hourly system cron; there is no Git deploy but SSH/rsync works.
- **Verified by this architect (headless Playground `php`, WordPress 7.1.2 / PHP 8.3):**
  - A template part keeps its `className` on the wrapper: `<header class="site-header wp-block-template-part">` (so the sticky header works).
  - Inline `navigation-link` blocks (no page `id`) never get `aria-current`, so a filter is needed.
  - `settings.spacing.blockGap: null` removes WordPress's flow-layout margins, so B's own spacing rules stay in charge.
  - WordPress 7.1 adds the class `wp-block-paragraph` to every paragraph.
  - `?cat=` is a reserved WordPress query variable. B's library used `?cat=`, so the WordPress build uses **`?section=`** instead.
  - Core Navigation's `overlay` attribute takes a template part ID in the form `"theme//slug"`; `navigation.php` says a custom overlay "is responsible for its own styling" (read in the 7.1.2 source).
  - Playground CLI sets `REMOTE_ADDR` to `127.0.0.1` for every request (read in `@php-wasm/universal`), so a loopback check works in dev.
- **Confirmed in the 7.1.2 source by the core review, so builders need not re-check:**
  - PHP-only (`autoRegister`) blocks get inspector controls automatically: string, number, integer and boolean attributes are marked at registration (`block-supports/auto-register.php`), and `postId` is passed to the preview.
  - Table cell `content` has role `"content"` (cells are editable in content-only mode).
  - `disableContentOnlyForUnsyncedPatterns` is honoured.
  - Navigation with `"overlayMenu":"always","hasIcon":false` prints a text "Menu" button.
  - `viewScriptModule` accepts a pre-registered module ID.
  - The `page_template_hierarchy` filter works with block templates (no longer needed; see §6.7).
- **Corrected by the core review (the first version of this plan was wrong here):**
  - A root `templateLock: 'contentOnly'` does **not** put the page's top-level blocks in content-only mode, and it allows inserting blocks at the root. The real lock is now a server-side check (§4.3).
  - A single `runPHP` step can't activate the theme and plugin and then import: `init` has already fired. The blueprint uses separate steps (§6.10).
  - Links in an editor preview of a PHP-only block can't be clicked. Hints are plain text (§6.4).

---

## 0. Decisions on one screen

| # | Decision | Choice | Why |
|---|---|---|---|
| 1 | Shape | **Block theme `spokares`** (the look), **site plugin `spokares-core`** (data, admin screens, dynamic blocks, editor governance) and **must-use plugin `spokares-hardening`** (security, SMTP, old-URL map) | The data survives a theme change, and deactivating the site plugin can't switch security off |
| 2 | WordPress floor | **Requires at least 7.1**, tested on 7.1.2; PHP 8.3 minimum, run on 8.4 | `blockVisibility.allowEditing` is a 7.1 key, and every probe ran on 7.1.2 |
| 3 | Build step | **None.** No npm, no bundler, no `node_modules` in any package | Volunteers can maintain plain PHP, JSON, CSS and small scripts |
| 4 | Plugins (D5 limit: 4) | 1 `spokares-core` · 2 **Two-Factor** · 3 **UpdraftPlus (free)** · 4 **empty** | SMTP, redirects, hardening and the calendar feed are our own code. The must-use plugin is our code split out, not a fourth slot |
| 5 | Source of truth for events and the rota (changes D9) | **WordPress.** Editors keep events, the rota, meetings and documents in wp-admin forms; the theme renders them | The owner's direction; groups.io's public feed was never confirmed |
| 6 | How lists render | **Seven server-rendered dynamic blocks** (`spokares/events`, `net`, `meetings`, `docs`, `toc`, `last-reviewed`, `asof`), most with a `view` attribute; complete without JavaScript. `data.js` is retired | Pages are right on first load; fewer blocks share one query and format layer |
| 7 | Editing | Four of the five routine jobs are **plain forms** (Net rota, Events, Regular meetings, Documents). Page text (Home, How it works, About only) uses the block editor; **the server refuses layout changes** from editors | The main editor (AG7QP) is a visual-builder user, not a coder |
| 8 | Members pages | **Theme templates** (`page-members`, `page-documents`, `page-exercises`) built from theme patterns. They have no page text to edit | They are headings plus lists; their structure deploys with git and needs no lock |
| 9 | Look | B's `site.css` is **ported nearly verbatim** into one theme stylesheet; B's class names ride on core blocks via `className`; plugin blocks print B's markup and classes | Keeps the feel with the least rewriting |
| 10 | Chrome | Header, footer, members sub-nav and menu overlay are **template parts** editors can't reach; links are hard-coded, root-relative | Chrome is fixed (DESIGN §0 rule 11) |
| 11 | Mobile menu | P0: core's **full-screen night overlay**. P1: try B's **night drop-down** through core Navigation's custom overlay template part; keep it if it passes the keyboard tests. Collapse at **1020px** | The 7.x overlay part lets the theme style the panel itself |
| 12 | Caching | **No page cache.** Every save calls `spokares_purge_cache()`, the one hook. No TTL, cron purge or `data-start`/`data-end` until a cache exists; if one is ever needed, server-level LiteSpeed driven by headers from our code (slot 4 stays empty) | Small site; date-driven content |
| 13 | Accounts and sign-in | Two named **ARES Editor** accounts, two Administrators. **E-mailed codes from the moment an account exists**; TOTP and printed backup codes set up **in person**; **no Enhance panel access for editors** (changes D5) | No password-only window; a lost phone doesn't lock anyone out |
| 14 | IA | Option B's **six pages** at `/`, `/how-it-works/`, `/about/`, `/members/`, `/members/documents/`, `/members/exercises/` | Round 3's simplified IA; D15's `redirects.csv` targets are re-pointed (EC to confirm) |
| 15 | Uploads | Photos (JPEG, PNG, WebP) only through the block editor's image button; documents (PDF, DOCX, XLSX, JPEG, PNG) **only through the Documents form, only on Save, only after the Privacy check**. DOCX/XLSX are opened and inspected. Replaced or trashed files are **deleted from the web by default** | A file must not be public before someone has checked it |
| 16 | Undo | Before saving: Ctrl+Z. After saving: the webmaster restores page revisions. Events and documents: Duplicate, Trash (restorable) and backups; no meta revisions | Simple rules an occasional editor can remember |
| 17 | Launch gate | AG7QP (or the first named editor) does the **five jobs on the dev site, unaided, with the one-page Editing guide**, inside the target times; every stumble is fixed first | Developer click tests don't prove a volunteer can do it |

---

## 1. Summary of the approach

### 1.1 Three pieces

```
wordpress/theme/spokares/                  THE LOOK (no data)
  theme.json            B's tokens: 19 colours, 2 self-hosted fonts, type scale, spacing; design controls off
  assets/css/site.css   B's site.css + page styles, ported to block markup (one file, front end and editor)
  templates/ parts/     page shells; the three members pages; header, footer, members sub-nav, menu overlay
  patterns/             Home, How it works and About sections and pages; the three members-page bodies
  blocks/               2 design-only PHP blocks: brand (seal lockup), message-path (the How it works figure)
  functions.php         enqueue, render filters (stack tables, list roles, external links, nav current state, no-break spaces)

wordpress/plugins/spokares-core/           THE DATA AND BEHAVIOUR (survives a theme change)
  post types            spk_event, spk_document (+ taxonomy spk_doc_cat)
  options               spk_rota, spk_nets, spk_radio, spk_meetings, spk_site, spk_tiles
  admin screens         Net rota, Net details, Events, Regular meetings, Meeting rules, Documents, Hub tiles, ARES site; "Site tasks"
  blocks/               7 PHP-only dynamic blocks that read the data and print B's markup
  governance            ARES Editor role, admin trimming, the server-side layout check, editor guard script
  routes                /docs/<slug>/ stable links (P1); calendar feed (P2)

wordpress/mu-plugins/spokares-hardening.php (+ spokares-hardening/)   THE FLOOR (can't be deactivated from wp-admin)
  hardening, user-enumeration blocks, security headers, upload checks, two-factor enforcement, SMTP, old-URL map (301/410)
```

### 1.2 Plugins: the final list

| Slot | Plugin | Status | Reason |
|---|---|---|---|
| 1 | `spokares-core` (ours) | Required | Content types, admin screens, dynamic blocks, role, editor governance, `/docs/` links. Header `Update URI: false` so WordPress.org can never "update" it |
| 2 | **Two-Factor** (WordPress.org team, 0.16.0) | Required | TOTP, e-mailed codes and backup codes. Readme says "tested up to 6.9.9"; it ran cleanly on 7.1.2 in the security test. Our must-use plugin enforces it (the plugin can't) |
| 3 | **UpdraftPlus** (free) | Required | Scheduled off-host copies to a role-owned Google Drive (convenience layer; the copy an intruder can't touch is the SSH pull in §5.9). Never connect UpdraftCentral or UpdraftVault |
| 4 | *empty* | — | No page cache at launch. If the site ever measures slow on a LiteSpeed server, our code sends `X-LiteSpeed-Cache-Control` / `X-LiteSpeed-Purge` itself before any cache plugin is considered |
| — | `spokares-hardening` (ours, must-use) | Required | Not a slot: it is the security half of our own code, placed where wp-admin can't switch it off |

**Rejected:** events calendars (recurrence is paid, heavy, and fights B's look), ACF/SCF/Meta Box, security suites, form plugins, SMTP plugins, Redirection, TOC plugins, page builders, LiteSpeed Cache (CVE record; not needed). Check whether Enhance's one-click WordPress installer adds LiteSpeed Cache or other plugins; if so, remove them.

### 1.3 No build step

- Dynamic blocks are **PHP-only blocks** (`block.json` with `"supports": {"autoRegister": true}` plus `"render": "file:./render.php"`). WordPress 7.x shows a server-rendered preview in the editor and generates inspector controls from the attributes. Verified in `wordpress/research/probes/blk/toc/`.
- Front-end behaviour is three small **plain ES modules**: copy radio settings, filter the document library, highlight the About contents. No Interactivity API, no framework.
- Admin JavaScript is two plain scripts that use the `wp.*` globals: `spokares-editor-guard` (block editor: editing modes, never-publish warnings, Welcome Guide off; about 60 lines) and `spokares-admin-forms` (plugin screens: add/remove rows, show fields by event kind, unlock the file chooser, rota choices; about 120 lines).
- Admin forms use HTML5 inputs (`date`, `time`, `url`, `pattern`) for native pickers.
- Patterns are PHP files of block markup; styles are one CSS file; tokens are JSON.

### 1.4 How a page is put together

```
Home, How it works, About (editors change words)
  template (theme)                 header part → <main> → post content → footer part
    └─ post content (page)         sections copied from the theme's patterns at setup; layout checked by the server on save
          ├─ core blocks with B's classes   (text, lists, buttons, tables, cover image: editable words)
          ├─ spokares/* dynamic blocks      (facts from the plugin's data: not editable here)
          └─ spokares-theme/message-path    (the fixed figure)

For members, Documents & forms, Exercises & events (nothing to edit on the page)
  template page-members / page-documents / page-exercises (theme)
          header part → members sub-nav part → <main> → the members-body pattern → footer part
          the pattern holds headings, a few fixed lines and spokares/* blocks; the page itself has no content
```

---

## 2. What works well as a theme, and what doesn't

### 2.1 Every Option B component, mapped

Type key: **T** template or part · **P** pattern copied into page content (locked) · **M** members-body pattern inside a template (git only) · **D** dynamic block (plugin data) · **B** design-only theme block · **J** small front-end module · **C** CSS only.

| Page | Component (B) | WordPress implementation | Type | What editors can do |
|---|---|---|---|---|
| All | Masthead: seal, lockup, 4 links, red "Join the team", sticky | `parts/header.html`: `spokares-theme/brand` + core Navigation (inline links) + core Button; sticky on the part wrapper | T, B | Nothing |
| All | Mobile menu ("Menu" pill, Escape to close) | Core Navigation; P0 full-screen night overlay, P1 custom overlay part `navigation-overlay` styled as B's drop-down; inline at ≥1020px by CSS | T, C | Nothing |
| All | Skip link | Core's automatic block-theme skip link to `<main>`, restyled as B's `.skip-link` | C | — |
| All | Footer (33 words) | `parts/footer.html`: brand (footer variant) + core Navigation (overlay never) + 3 paragraphs | T | Nothing |
| Members | Sub-nav (5 items, wraps at 390) | `parts/members-nav.html`: core Navigation (overlay never) | T | Nothing |
| All | Current-page marker (amber bar) | Theme filter sets `aria-current="page"` on nav links by path; "For Members" matches `/members/` and below | C + filter | — |
| All | Deep-link landing | CSS `scroll-margin-top` (+ admin-bar offset at ≥601px). `hashLanding()` dropped; restore a 10-line module only if tests fail | C | — |
| Home | Hero: sunset photo, two gradients, display H1, lede, two buttons, agency line | Pattern: core Cover with the photo **from the Media Library** (id, `wp-image-N`, srcset) + Heading/Paragraph/Buttons; second gradient from CSS | P | Words, button text and links, the photo |
| Home | What we do: 4 stops on the red line | Pattern: core List with class `stops`; each item "**Label** text"; line, dots, end ring in CSS | P | Words; add or remove a stop ("bold the first words") |
| Home | Dawn hinge | Core Spacer with class `dawn-sky` | P, C | — |
| Home | Come visit: "From home" card | **`spokares/net` view `from-home`** (time and frequency from Net details) | D | Nothing here: Net details |
| Home | Come visit: "In person" address and meetings with "Next:" | **`spokares/meetings` view `home`** (address from ARES site, meetings from Regular meetings) | D | Nothing here: Regular meetings |
| Home | Join in four steps (0-3, dashed optional step, "Optional" pill, note) | Pattern: Group `steps` of Group `steps__item`; numbers are CSS counters from 0; pill is its own paragraph | P, C | Words and links |
| How | Opener on storm: H1, "It's 2 AM", scenario note, 4 hops, diagram, closing line | Pattern: groups + List `hops` (CSS-counter numerals) + **`spokares-theme/message-path`** | P, B | Words; the figure is fixed |
| How | Weekly net: settings box with Copy buttons | **`spokares/net` view `settings`** + copy module | D, J | Settings via Net details only |
| How | Run-sheet (6 steps) with the visitor cue | List `seq`; the cue is a nested list `seq__cue` inside item 4 | P | Words |
| How | Other nets (3 lines) | **`spokares/net` view `other-nets`** (weeks and GMRS time from Net details) | D | Nothing here: Net details |
| How | Activation: redline, lede, real-event line, 7-step `seq` | Separator `redline`, paragraphs, List `seq` | P | Words |
| How | Readiness levels (4 neutral cards) | Group `levels` of Group `level`; CSS counters; `role=list` added at render. WXL86 / 162.400 MHz stays page text (its only home) | P | Words |
| How | Messages, forms and logs, Winlink, training rhythm | Headings, paragraphs, List `lines`, Group `deflist` | P | Words |
| How | Roles table (stacks on phones) | Core Table with class `table table--stack`; `data-label` added at render | P | **Cell text only**; adding a row is admin work |
| How | Close: "Ready?" + the page's one red button | Group `how-close` + Button | P | Words |
| About | Page head on dawn + "Last reviewed" | Pattern head + **`spokares/last-reviewed`** (prints only when a review date is set) | P, D | Words; review date in the **Page review** box beside the page |
| About | Sticky TOC, phone `<details>`, "On this page" button | **`spokares/toc`** reads the page's H2 anchors + toc module (scroll-spy) | D, J | Nothing to maintain |
| About | 9 chapters: definitions, callouts, tables, join paths, task-book table, timeline, pullquote, contact | Chapter groups of core blocks (Group `defs`, Group `callout`, Table, List `seq`, List `years`, Quote `pullquote`) | P | Words, table cells |
| About | Structure figure (two chains merging into one team) | Group `chains`: two ordered lists + team group; merge curve and node in CSS | P, C | Labels |
| Hub | H1 + "Schedule as of…" | Template `page-members` → pattern `members-hub`: Heading + **`spokares/asof`** | T, M, D | Nothing |
| Hub | Document search | **`spokares/docs` view `search`** (GET form to the library). Live preview cut | M, D | — |
| Hub | This week: next 2 exercises with date slabs, "What to bring", next meeting (+ any cancellation) | **`spokares/events` view `upcoming`** + paragraph + **`spokares/meetings` view `next`** | M, D | Events; Regular meetings |
| Hub | Tuesday net: night settings bar + Copy, 5-week rota, open-slot line | **`spokares/net` views `bar` and `rota`** + copy module | M, D, J | Net rota |
| Hub | Most used: 4 tiles | **`spokares/docs` view `tiles`** (four slots on **Documents › Hub tiles**) | M, D | Hub tiles screen |
| Documents | Library: sticky rail (search, chips), 7 groups, count, empty state | **`spokares/docs` view `library`** + library module; server-side `?q=` and `?section=` without JS | M, D, J | Documents screen |
| Documents | Not posted here | Paragraph in the `members-documents` pattern | M | Nothing (admin, git) |
| Exercises | Bring line; Next up cards (red first card, Happening now, tasks, extra form, button); After line | Paragraphs in the `members-exercises` pattern + **`spokares/events` view `next-up`** | M, D | Events screen; the two lines are admin work |
| Exercises | Later this season | **`spokares/events` view `later`** | M, D | Events screen |
| Exercises | Winlink assignments | **`spokares/net` view `winlink`** (rows from the Net rota screen) | M, D | Net rota screen |
| Exercises | Public-service events + tips line | **`spokares/events` view `public-service`** + paragraph | M, D | Events screen |
| Exercises | Past exercises | **`spokares/events` view `past`** (events ticked "Keep in Past exercises" after they end) | M, D | One checkbox |

**Works better as WordPress than as static HTML:** the chrome (one copy instead of six), every weekly fact (one form feeds every page, including Home's "From home" card and How it works' "Other nets"), the About TOC (can't drift from headings), stacked table labels (added by the server, never typed), the members pages (structure in git, nothing to lock).

**Works, with care:** the locked page text on three pages (the lock is a server check plus an editor tidy-up, §4.3), the 1020px menu (CSS over core classes, re-test each major release), core blocks carrying B's class names (admins paste a pattern file's markup rather than build raw blocks), page text living in the database after setup (§5.7).

### 2.2 Design changes made to fit WordPress

| # | Change | Why | How the feel is kept |
|---|---|---|---|
| 1 | Mobile menu: P0 is core's **full-screen night overlay**; P1 rebuilds B's **drop-down panel** as core Navigation's custom overlay part | The overlay part is styled by the theme; a custom menu script would be more code | Same "Menu" text button, same 4 links, "Join the team" stays in the bar at every width; collapse still at 1020px |
| 2 | Message-path figure is a **fixed PHP block** showing one state (`relay`, ending at the EOC) | Editors can't edit SVG; SVG uploads are blocked; the multi-state system existed for the cut scrollytelling | Same drawing, same inline SVG and fonts, same "A scenario" pill in the picture |
| 3 | Numbered markers (join steps 0-3, hops, run-sheet, levels, task-book levels) come from **CSS counters**, not typed numbers | Core blocks can't hold B's `<span class="steps__n">` | Identical amber circles; renumbering follows reordering |
| 4 | B's rich list items become **core Lists** with a bold lead-in ("**Hospitals** Backup radio…"), or **Groups** where an item needs a heading and a link (steps, levels) | Core list items hold inline text only; Groups can't be `<ol>` | CSS turns the bold lead-in into B's label, and an item without one still looks right; a render filter restores `role="list"`/`"listitem"` on the step and level groups |
| 5 | `<dl>` definition lists on static pages become **Groups of paragraphs** (`defs`, `deflist`) | No core description-list block | Same two-column look; plugin output (the settings box) keeps real `<dl>` |
| 6 | Table captions become **visually hidden figcaptions** and stack labels are **added at render** | Core Table uses `<figure>` + `<figcaption>`; no per-cell attributes | Same stacked cards on phones, no labels to type |
| 7 | `data-verify` marks become **admin-only**: a "Needs checking (webmaster only)" box on events, documents and meetings, `needs_check` flags in the options, and a `needs-verify` class on page blocks outlined only for logged-in editors with `?verify=1` | Editors can't add data attributes; readers must never see verify chips | Nothing visible changes for readers |
| 8 | Tap-to-define terms, scrollytelling, the hub's live search preview, in-browser "Add to calendar", the generic chip filter and `hashLanding()` are **dropped** | Unused in round 3 or replaced by server rendering | None were visible in round 3 except the hub preview (the library page filters instantly) |
| 9 | The inline SVG **sprite** is gone; blocks print their own small inline icons | A body-level sprite doesn't exist in the editor iframe | Same icons (copy, search, menu, script, form, log, book) |
| 10 | Page-scoped `<style>` blocks and the members pages' repeated 47 lines move **into the one stylesheet** | Pages hold no CSS in WordPress | Same rules |
| 11 | Fonts are **self-hosted** woff2 through theme.json | No Google Fonts request (privacy page, CSP) | Same Crimson Pro and Schibsted Grotesk |
| 12 | `.search` is renamed **`.searchbar`** (and `.searchbar__field`) | WordPress adds `body.search` on search results, which B's `.search` flex rule would hit | Same look |
| 13 | The library's filter parameter is **`?section=`**, not `?cat=` | `cat` is a reserved WordPress query variable | Same behaviour, chips and deep links (`#forms`) |
| 14 | "Later this season" lists **every upcoming event that isn't public service and isn't already a Next-up card**; undated ones come last as "Date not posted"; rows read "**Title: summary**" | An event must never vanish from Exercises because of where its date falls | With round 3's data the rows are unchanged (6); the SHARES row reads "SHARES training: for SHARES participants" |
| 15 | Public-service rows sort **dated first, then undated** | Dates come from data now | Order becomes Bloomsday, Climb for the Cure, Torchlight Parade, Fun runs (was interleaved) |
| 16 | Abbreviations inside **event tasks** are plain text | Event fields are plain text so editors can't break markup | `<abbr>` stays in page text (patterns) where it survives editing (click-test) |
| 17 | The PIO row's "Open position" pill is **bold words followed by a link** in the About table, drawn as B's amber pill by CSS (`.roles td:last-child > strong:first-child:has(+ a)`) | Table cells are rich text; pills are inline markup editors can't keep, but bold is | Same amber "Open position" pill, then "Interested? Tell us." (fixer round) |
| 18 | `.nowrap` spans become **no-break spaces**, inserted **automatically at render** before AM, PM, MHz, kHz and Hz | RichText drops classes on `<strong>`; editors can't type U+00A0 | Same line-breaking, except in the readiness levels' narrow "what you do" cells (`level__do`), which are left out so "Stand by" keeps B's three lines |
| 19 | Members page `min-height` removed | It pushed the footer off tall screens (REVIEW) | Footer visible, as on public pages |
| 20 | "Mockup — content to be verified before launch" stays in the footer part **until the launch release**, then is deleted in the theme (git), not in the Site Editor | Site Editor edits override theme files | Unchanged until launch |
| 21 | The members pages' fixed lines ("Bring to every exercise", "After any exercise", "What to bring", "Not posted here", the tips line) live in **theme patterns inside templates** | Page-slug templates deploy with git and need no lock | Same words in the same places; changing them is admin work |
| 22 | Home's "From home" card, the "In person" address and How it works' "Other nets" lines are **printed from data** | One home per fact (placement map §1) | Same words |
| 23 | A cancelled or moved meeting is **shown**: Home prints "Next: Sat, Nov 14 (Oct 10 cancelled)" or "(moved from Oct 10)"; the hub adds "Sat, Oct 10: Second Saturday Workshop cancelled." for 14 days before | Nobody drives in for a cancelled meeting | New words, shown only when a date changes |
| 24 | Logged-in editors see a small "**Edit this list in Net rota**" link under each dynamic list, and an admin-bar menu **Update lists** | The main editor edits "on the page" in Hostinger today | Readers never see either |

### 2.3 Round-3 review fixes built in

| Fix (REVIEW.md) | Where it is built |
|---|---|
| About prints "Last reviewed" only once a date is set | `spokares/last-reviewed` prints nothing publicly without `_spk_reviewed`; the **Page review** box beside the page sets it |
| How it works: "Radio settings" jump link at the top | Opener pattern: a small link to `#weekly-net` right under the H1 (2 words; the only new copy in the patterns) |
| Remove "147.300 MHz" from the diagram | `spokares-theme/message-path` omits that label |
| Link "Open" rota slots to the "Open slot?" line | `spokares/net` view `rota`: Open is `<a class="tag tag--open" href="#open-slot">`; the line has `id="open-slot"` |
| The Simplex note points to the net script | Rota note "Simplex" links to `/members/documents/#net-scripts` |
| Put the Tuesday net first on phones | `.hub-net` is first in the members-hub markup at every width (QA-104; no CSS `order`): above This week on phones, the left column on desktop |
| Tiles open the files; no tile for a "Soon" item; swap ICS-309 for ICS-214 | `spokares/docs` view `tiles` links to `/docs/<slug>/`, skips "Soon" and unpublished documents; seed slots: Net script, ICS-213, ICS-214, ACS Task Book. The seed applies the same swap to the library's **Most used** flags (ICS 214 in, ICS 309 out), so the Most used filter lists every tile's document |
| Low-contrast small grey text on night | Theme CSS: small text on dark surfaces uses `--mist` (not `--mist-2`), including `.how-open__note` and `.hero__agency` |
| Red rule under every members H2 feels busy | Members H2s (`m-head`) get a hairline; the red rule stays on the library group heads (one CSS variable, reversible) |
| Members min-height pushes the footer off | Removed (§2.2 #19) |

### 2.4 Left for the owner (content, not theme work)

Round 3's rule is that every word comes from the copy files, so the builders do not change these: the empty-sky hero and abstract headline; "See how it works" versus a visit link; About's length (exam table, History); readiness levels and Standing Order 1b on a public page; "Join ARES: One form." being terse; the Third Thursday time; "no dues" on Home. On Home, How it works and About each is a page-text edit an editor can make later; on the members pages it is an admin change in git.

---

## 3. Dynamic content

### 3.1 Principles

1. **Editors fill in forms; blocks print pages.** No weekly fact is typed into page text.
2. **Everything is rendered on the server** for "today" in `America/Los_Angeles`, complete without JavaScript. Past events drop off; "Happening now" and "next" are computed at render.
3. **One home per fact** (placement map §1). Each block view reads one store. The net time, repeater frequency, net weeks, GMRS time and meeting place are never typed into page text; Home and How it works print them from the same options as the hub. NOAA's WXL86 / 162.400 MHz is page text because it has no other home.
4. **Rules plus exceptions, not typed dates.** Nets and meetings recur by rule (nth weekday); editors enter only exceptions (a cancelled or moved meeting, a note on one Tuesday).
5. **Call signs only, 12-hour times, no `href="#"`** (DESIGN §0 rule 12), enforced in code.
6. **Never throw away what an editor typed** (§4.4): a rejected field is held back and shown again, never silently dropped.

### 3.2 Data model

**Post types** (registered by the plugin; `public => false`, `publicly_queryable => false`, `show_ui => true`, `show_in_rest => false`, no `editor` and **no `revisions`** support, so WordPress shows a plain form, never the block editor):

| Post type | Label | Supports | Capability type | Menu |
|---|---|---|---|---|
| `spk_event` | Event / Events | `title` | `['spk_event','spk_events']`, `map_meta_cap => true` | "Events", `dashicons-calendar-alt` |
| `spk_document` | Document / Documents | `title`, `page-attributes` (menu order = position in section) | `['spk_document','spk_documents']`, `map_meta_cap => true` | "Documents", `dashicons-media-document` |

**Taxonomy** `spk_doc_cat` ("Library section"), on `spk_document`, `public => false`, `show_ui => true`, `show_admin_column => true`, `hierarchical => false`, one term per document, edited through a **custom radio list** (`meta_box_cb`), never WordPress's tag box. Caps: `manage_terms`/`edit_terms`/`delete_terms` → `manage_options`; `assign_terms` → `edit_spk_documents`. Term meta `spk_order` (int). Seeded terms, in order: `net-ops` Net operations · `join` Join & task books · `forms` Forms · `training` Training · `readiness` Go-kits & readiness · `digital` Winlink & digital · `reference` Reference.

The exact meta keys, option shapes and storage rules are in **§6.3** (the contract). In brief:

| Data | Stored in | Edited on | Read by (block › view) |
|---|---|---|---|
| Events and exercises | `spk_event` + `spk_*` post meta | Events › Add event | events › upcoming, next-up, later, public-service, past |
| Documents | `spk_document` + `spk_*` meta + `spk_doc_cat` | Documents › Add document | docs › library, search; `/docs/<slug>/` |
| The four hub tiles | option `spk_tiles` | Documents › Hub tiles | docs › tiles |
| Net control per Tuesday + Winlink assignment | option `spk_rota` (keyed by date) | Net rota | net › rota, winlink; dashboard |
| Net rules, Winlink how-to, open-slot line | option `spk_nets` | Net rota › Net details | net › rota notes, winlink, bar, settings, from-home, other-nets |
| Repeater settings | option `spk_radio` | Net rota › Net details | net › settings, bar, from-home |
| Meeting rules | option `spk_meetings['meetings']` | Events › Meeting rules | meetings › home, next |
| Meeting cancellations and moves | option `spk_meetings['changes']` | Events › Regular meetings | meetings › home, next |
| Meeting place | option `spk_site` | Settings › ARES site (admins) | meetings › home (address) |
| Page review date and owner | page meta `_spk_reviewed`, `_spk_owner` | Page review box on the page | last-reviewed |

**Not modelled:** leadership and contact tables, legal basis, definitions, history (page text on About); the members pages' fixed lines (theme patterns, §2.2 #21). They change rarely and have one home each.

### 3.3 Schedule rules and formatting (one helper set, used by every block and the dashboard)

- **Today:** `wp_date('Y-m-d')` in the site time zone. Only when `wp_get_environment_type() === 'local'` **and** the constant `SPOKARES_DEV` is true, the constant `SPOKARES_TODAY` or a `?today=YYYY-MM-DD` query parameter overrides it (B's demo parameter).
- **nth weekday:** `nth = floor((day - 1) / 7) + 1`; `5` means a fifth occurrence (the next fifth Tuesday is 2026-12-29).
- **Tuesday note** (port of `fn.netOn()`): 5th Tuesday → "Simplex"; else 2nd or 4th → "Winlink night"; else 3rd → "GMRS net, 7:30 PM". Nth lists and the GMRS time come from `spk_nets`.
- **Upcoming:** an event is upcoming while `end` (or `start`) ≥ today; **happening now** while `start` < today ≤ `end`, or `start` = today with an `end`; it leaves every upcoming list the day after it ends. Undated events (`not-posted`, `as-requested`) count as upcoming until trashed. Nothing is deleted automatically.
- **Ordinals in sentences:** 1 → "1st", 2 → "2nd", 3 → "3rd", 4 → "4th", 5 → "fifth"/"Fifth"; lists joined with "and" ("2nd and 4th Tuesdays").
- **Formatting** (never "2000", never "22:00"; a no-break space before AM, PM, MHz, kHz and Hz):

| Case | Output |
|---|---|
| Time | "8:00 PM", "9:00 AM", "noon" |
| Time range, same half of the day | "12:30–3:30 PM" |
| Time range, crossing noon | "9:00 AM–noon" |
| One day | "Sat, Oct 3" |
| One day with time | "Thu, Oct 15, 10:15 AM"; "Sat, Oct 17, 9:00 AM–noon" |
| Two days | "Sat–Sun, Sep 26–27"; across months "Mon–Tue, Nov 30–Dec 1" |
| Three days or more | "Nov 18–25" (no weekdays) |
| Year | Added (", 2027") only when the date is more than 183 days from today: "Sun, May 2, 2027" |
| Date slab | mon "Sep", day "26–27", dow "Sat–Sun" |
| Hub meta line | "All day", "All weekend" (Sat–Sun all-day), "10:15 AM", "9:00 AM–noon" |
| As-of | "Sat, Sep 26, 2026" |
| Past exercise | "Oct 2022" (day or month precision), "2025" (year precision) |
| Meeting change, short | "Oct 10" (used in "(Oct 10 cancelled)", "(moved from Oct 10)") |

### 3.4 Admin screens (what the editor sees)

**Menu for an ARES Editor, top to bottom** (order set with the `custom_menu_order` / `menu_order` filters; Net rota uses `dashicons-microphone`): Dashboard · **Net rota** (Net rota, Net details\*) · **Events** (All events, Add event, Regular meetings, Meeting rules\*) · **Documents** (All documents, Add document, Hub tiles) · Pages · Profile. \*Only for users granted `spokares_edit_net_details`. Posts, **Media**, Comments, Tools, Appearance, Plugins, Users and Settings are absent (by capability, `remove_menu_page`, and a redirect of `upload.php` to the Dashboard for non-admins). Administrators see everything plus **Documents › Sections** and **Settings › ARES site**.

**Every plugin screen:** a Help tab (3-5 lines) and visible help text under each field; every address field is labelled "Web address (copy it from your browser's address bar)"; the page's own Save button; after saving, a notice that links to the place on the site ("Saved. See it on Exercises & events").

**Dashboard: "Site tasks"** (the only widget for editors; Welcome, At a Glance, Activity, Quick Draft and News removed):

```
┌ Site tasks ──────────────────────────────────────────────────────────────┐
│ 1 Net rota     Posted through Tue, Oct 27 · 2 open slots (Oct 6, Oct 27)  │
│                [ Update the rota ]                                        │
│ 2 Events       Happening now: ARES-ACS / WSDOT exercise                   │
│                Next: Simulated Emergency Test, Sat, Oct 3 · {n} more      │
│                [ Add an event ]  All events                               │
│ 3 Meetings     Next: Second Saturday Workshop, Sat, Oct 10, 9:00 AM       │
│                [ Cancel or move a meeting ]                               │
│ 4 Documents    37 in the library · {n} not reviewed in 12 months          │
│                [ Add or replace a document ]  Hub tiles                   │
│ 5 Page text    Home · How it works · About ARES & ACS                     │
│ Needs attention (shown only when true, amber):                            │
│   ● The rota runs out in under 3 weeks.                                   │
│   ● {n} items marked "Needs checking".                                    │
│   ● How it works may contain something we never publish. Check it.       │
│ Stuck? webmaster@spokares.org                                             │
└──────────────────────────────────────────────────────────────────────────┘
```

**Net rota** (`admin.php?page=spokares-rota`, capability `spokares_edit_rota`):

```
Net rota                              Last saved Sat, Sep 26, 10:14 AM by Test Editor   [View on site]
Tuesday net, 8:00 PM, W7GBU 147.300 MHz. Call signs only, no names.

 Tuesday      This week is…   Net control                                            Shows              Winlink assignment                Form                 Note
 Tue, Sep 29  Simplex         (•) Call sign [NZ2S   ]  ( ) Open  ( ) Not posted yet   NZ2S                                                                      [        ]
 Tue, Oct 6   –               ( ) Call sign [       ]  (•) Open  ( ) Not posted yet   Open                                                                      [        ]
 Tue, Oct 13  Winlink night   (•) Call sign [AE7RJ  ]  ( ) Open  ( ) Not posted yet   AE7RJ              [Did You Feel It report (Sha…]    [DYFI            ▾]  [        ]
 Tue, Oct 20  GMRS net, 7:30  (•) Call sign [WA7LNC ]  ( ) Open  ( ) Not posted yet   WA7LNC                                                                    [        ]
 Tue, Oct 27  Winlink night   ( ) Call sign [       ]  (•) Open  ( ) Not posted yet   Open               [A welfare message            ]    [Welfare Message…▾]  [        ]
 … 13 Tuesdays from this week (link: "Show 13 more")
                                   [ Undo last save (Test Editor, Sat, Sep 26, 10:14 AM) ]      [ Save rota ]
▸ Paste a list instead (P2)
```

- **One choice per row:** Call sign / Open (ask for a volunteer) / Not posted yet. Typing in the box selects Call sign; choosing another option greys the box out (a disabled input isn't submitted). "Shows" repeats what the public will see: the call sign, "Open" or "Not yet published". A Tuesday with no stored row is "Not posted yet".
- **Call signs:** uppercased; the server stores the **first word that matches** the call-sign pattern (§4.4). If other words were dropped, the notice says so for that row ("Tue, Oct 13: saved AE7RJ only. One call sign per Tuesday; names are never stored."). If no word matches, **that row is not saved**, the typed text stays in the box with a red outline, and the notice says "Tue, Oct 6 not saved: call sign only, no names." A `<datalist>` suggests call signs used before.
- **Winlink** inputs appear only on Winlink-night rows. Form is a list: ICS-213, ICS-213RR, DYFI, Welfare Message / Quick Health & Welfare, Other (reveals a text box). "Note" is an exception note shown in the rota's Note column (e.g., "No net: county exercise").
- **Saves only the rows you changed.** Each row carries its original values in hidden fields. A changed row is written onto the stored option; an unchanged row is left alone. If someone else saved that same row after you opened the screen, their version is kept and yours comes back in the box with "Tue, Oct 13 was changed by {name} while you were editing. Your entry wasn't saved."
- **Undo last save** restores the rows changed by the most recent save, whoever made it; the button names who and when.
- Rows are generated from this week's Tuesday on; past rows drop off; stored rows older than 12 months are pruned on save.

**Net details** (`admin.php?page=spokares-net-details`, capability `spokares_edit_net_details`): a red banner "Amateur frequencies only. Never county, hospital, SHARES, 800 MHz or channel numbers." Fields: W7GBU call (**administrators only**, QA-039: W7GBU is the club's own call and How it works also names it in page text and the message-path picture, so a user with only the grant sees it read-only and not posted, and the save refuses a different one from them with "The repeater call sign wasn't saved: only an administrator can change it."), frequency (`^\d{2,3}\.\d{3}$`), offset (list), tone (list); alternate repeater frequency, offset, tone, "Show on How it works"; net time; Winlink-night weeks, fifth-Tuesday simplex weeks, GMRS weeks and time; Winlink how-to sentence; open-slot sentence. A **live preview** prints every sentence these facts produce: the hub bar, the copy line, Home's "From home" card and How it works' "Other nets" lines. Save asks for confirmation. Admins also see the "Needs checking (webmaster only)" flags. There is no list of hand-checked mentions any more: no net fact is page text.

**Events › Add event** (classic form; WordPress's Publish box is replaced by our own **Save** box with Save draft, Publish (**Update** once published) and Move to Trash; no Preview, no Visibility, no publish date):

```
Add event
┌ Kind (pick one first) ──────────────────────────────────────────┐   ┌ Save ─────────────────────┐
│ ( ) Exercise   ( ) Training   ( ) On the air   ( ) Public service │   │ Status: Draft             │
│ On the air: an on-air event members join from their own stations, │   │ [Save draft]  [Publish]   │
│ like SKYWARN Recognition Day.                                     │   │ Move to Trash             │
└───────────────────────────────────────────────────────────────────┘   └───────────────────────────┘
Event name [ Simulated Emergency Test                        ]            ┌ Checking ─────────────────┐
┌ When ─────────────────────────────────────────────────────────┐        │ [ ] Needs checking        │
│ (•) On a date   ( ) Date not posted yet   ( ) As requested*    │        │     (webmaster only)      │
│ First day [2026-10-03]   Last day [          ] (optional)      │        └───────────────────────────┘
│ [x] All day      Start [--:--]   End [--:--]                   │
└────────────────────────────────────────────────────────────────┘
Short line for lists (90 characters) [ ARRL’s national exercise. The Section asks you to: ]
What members do (one task per line)                                              (Exercise only)
Main link (shown as the button)                                                   (not Public service)
  Web address (copy it from your browser's address bar) [https://…]   Button words [Exercise details on groups.io]
More links (up to 3)                                                              (not Public service)
  Words [ARRL SET forms]   Web address (copy it from your browser's address bar) [https://www.arrl.org/…]   + Add a link
Extra form [— none — ▾ (published documents)]                                      (Exercise only)
Volunteer through (call sign) [NV2Z]                                              (Public service only)
After it ends: [x] Keep in Past exercises                                         (Exercise only; ticked by default)
Never publish: no county, hospital, SHARES or 800 MHz channels; no names, phones or e-mails.
```
\*"As requested" appears only for Public service.

- **Kind comes first, is required and has no default.** Fields that don't apply to the chosen kind are hidden; their stored values are kept but ignored when rendering. A save without a kind is kept as a Draft with "Pick a kind first."
- "All day" is ticked by default; unticking it shows Start and End. Ticking it again clears both times.
- List screen: views **Upcoming** (default, soonest first), **Past** (newest first), **Drafts**; columns When, Event name, Kind, Status (Upcoming / Happening now / Past / Draft), Needs checking; row actions Edit · **Duplicate** (draft copy, dates cleared) · Trash · View on site (`/members/exercises/#<slug>`). No Quick Edit; no bulk Edit.
- Draft events never render.

**Events › Regular meetings** (`admin.php?page=spokares-meetings`, capability `spokares_edit_rota`), the common job:

```
Regular meetings: cancel or move one date
 Second Saturday Workshop           Sat, Oct 10  [ ] Cancelled   Moved to [          ]   Note [                           ]
                                    Sat, Nov 14  [ ] Cancelled   Moved to [          ]   Note [                           ]   … next 4 dates
 Third Thursday training meeting    Thu, Oct 15  [ ] Cancelled   Moved to [          ]   Note [                           ]   …
                                                                                                       [ Save ]
Meeting times and weeks are on Meeting rules (ask the webmaster).
```
Saves only the rows that changed (same rule as the rota). Home shows "Next: Sat, Nov 14 (Oct 10 cancelled)"; the hub shows "Sat, Oct 10: Second Saturday Workshop cancelled." plus the note, from 14 days before the date.

**Events › Meeting rules** (`admin.php?page=spokares-meeting-rules`, capability `spokares_edit_net_details`; administrators by default), the rare job, with its own Save and a confirmation:

```
 Name | Week (1st–5th) | Day | Start | End | Time words ("evenings") | Extra on Home | Skip months | On Home | Active | Needs checking
```

**Documents › Add document** (same Save box as events; "Move to Trash" carries **[x] Also remove its file from the web**, ticked by default):

```
Add document
── Basics ──────────────────────────────────────────────────────────────────────────
Document name [ ACS Position Task Book                          ]
Library section (•) Join & task books  ( ) Forms  ( ) …            (required; pick one)
Where the file is  (•) Upload a file  ( ) Link to another site  ( ) Not available yet ("Soon")
[ ] Privacy check: no personal phones, home addresses, personal e-mails, member lists or rosters,
    and no county, hospital, SHARES or 800 MHz details.            (required to publish; tick it to unlock the file chooser)
  File [Choose file]  (it uploads when you click Save draft or Publish)
  After an upload:  Current file: acs-task-book-7f3a9c2b.docx · 84 KB · Open
                    Replace with [Choose file]   [x] Remove the old file from the web (recommended)
  For a link:       Web address (copy it from your browser's address bar) [https://…]
Version or date [2024-08-09]
Short note (8 words or fewer) [Print it; keep sign-offs in a binder.]
▸ More options (closed)
    Source shown on the page [ag7qp.com]   (filled in from the web address)
    Format [DOCX ▾]   (automatic for uploads)
    "How to" link: words [How to write one]   web address [https://…]
    [ ] Show under "Most used"
    Position in section (lower shows first) [30]
    Who keeps it current [Net Manager]    Last reviewed [2026-09-26]  [Mark reviewed today]
    [ ] Needs checking (webmaster only)
    Hub tile: slot 4, "ACS Task Book". Change tiles on Documents › Hub tiles.
▸ Admin only (closed; administrators only)
    Extra links (FEMA course codes): words, full title, web address (+ Add; ids are made from the words)
    Search words [task book, PTB, RADO, S-RADO]
    Link name (slug) [acs-task-book]
```

- The file chooser is a plain `<input type="file">` (`enctype="multipart/form-data"` via `post_edit_form_tag`), **not** the media window, so nothing uploads when a file is picked. It is disabled until Privacy check is ticked, and the server refuses a file without the tick (the rest of the form is kept, saved as Draft, "File not uploaded: tick the Privacy check first, then choose the file again.").
- Upload rules (types, inspection, random suffix, deletion of replaced files) are in §5.5.
- The slug is the row anchor and the stable link `/docs/<slug>/`; it is read-only for non-admins once published.
- List: columns Document, Section, Source (File / Link / Soon), Format, Version, Most used, Tile slot, Reviewed (red after 12 months), Needs checking; filter by section; bulk action **Mark reviewed today**; row actions Edit · View on site (`/members/documents/#<slug>`) and, for administrators, **Pull this file now** (§5.5). No Quick Edit, no bulk Edit, no Trash row action (trash from the edit screen, where the file box is).

**Documents › Hub tiles** (`admin.php?page=spokares-tiles`, capability `edit_spk_documents`):

```
Hub tiles: the four "Most used" tiles on For members
 Slot 1  Document [Net scripts: weekly, simplex and GMRS ▾]   Words [Net script   ]   Icon [script ▾]
 Slot 2  Document [ICS 213 General Message ▾]                 Words [ICS-213      ]   Icon [form ▾]
 Slot 3  Document [ICS 214 Activity Log ▾]                    Words [ICS-214      ]   Icon [log ▾]
 Slot 4  Document [ACS Position Task Book ▾]                  Words [ACS Task Book]   Icon [book ▾]
                                                                                   [ Save tiles ]
```
The lists offer published, privacy-checked documents that aren't "Soon". A document already in another slot shows "(now slot 2)", and choosing it swaps the two slots, so a tile can't silently disappear.

**Page review box** (block editor sidebar, pages only; a classic `side` meta box, so no build): "Last reviewed: Sep 26, 2026" (or "not yet") · Page owner [Emergency Coordinator] · **[ ] Mark reviewed today when I save**. It replaces the hover-only row action of the first plan.

**Settings › ARES site** (`admin.php?page=spokares-site`, `manage_options`): the meeting place (`spk_site`). The groups.io links are page text and theme parts (footer, members menu, Home's Join section), not settings (QA-038).

### 3.5 How each front-end list renders

| Block › view | Attributes (the values the patterns set; block defaults are in §6.4) | Query and logic | Used on |
|---|---|---|---|
| `events` › `upcoming` | `types` "exercise,training", `limit` 2, `days` 60 | Published, dated events of those kinds with `spk_end_sort` ≥ today and `spk_sort` ≤ today + days, ordered by start (an event in progress sorts first) | Hub "This week" |
| `events` › `next-up` | `types` "exercise", `limit` 2 | First N upcoming **dated** events of those kinds; first card red with a red button, others ghost | Exercises |
| `events` › `later` | `types` "exercise,training,on-air", `limit` 12, `cardTypes` "exercise", `cardLimit` 2 | Every published upcoming event of `types` **except** the ones `next-up` shows (computed from `cardTypes`/`cardLimit`, which must equal the next-up block's `types`/`limit`); dated by start, then undated by menu order and title, printed "Date not posted" | Exercises |
| `events` › `public-service` | — | Published public-service events: dated and not past, by date; then undated ("Date not posted" / "As requested") by menu order and title | Exercises |
| `events` › `past` | `limit` 8 | Exercise-kind events with `spk_keep_past` and `spk_end_sort` < today, newest first | Exercises |
| `net` › `rota` | `weeks` 5 | Tuesdays from today; call sign / Open / "Not yet published"; note from rules + row note; first row `is-next` | Hub |
| `net` › `bar` | — | `spk_radio.primary` + net time | Hub |
| `net` › `winlink` | `limit` 8 | `spk_rota` rows with a Winlink task, date ≥ today; first row `is-next`; how-to line from `spk_nets` | Exercises |
| `net` › `settings` | — | `spk_radio` (alternate only when `show`) + net time; Copy buttons | How it works |
| `net` › `from-home` | — | Net time + primary frequency, in B's sentence | Home |
| `net` › `other-nets` | — | Winlink, simplex and GMRS weeks and GMRS time, in B's three sentences | How it works |
| `meetings` › `home` | — | "In person at {place}:" + active meetings with `show_home`: the Home line + "Next: <date>" (+ change note) | Home |
| `meetings` › `next` | `meeting` "" (= soonest Home meeting) | Cancelled/moved Home-meeting dates in the next 14 days, then the next real date | Hub |
| `docs` › `library` | — | All published, privacy-checked docs by section (`spk_order`), then `menu_order`, title; server filters `?q=` (all words must match title, note, keywords, format, version, slug, sub-link labels) and `?section=` | Documents |
| `docs` › `search` | — | GET form to `/members/documents/` with `q` | Hub |
| `docs` › `tiles` | — | The four `spk_tiles` slots; a slot whose document isn't published, privacy-checked and a file or link is skipped | Hub |
| `toc` | `title` "On this page", `labels` "legal-basis=Legal basis;leadership=Leadership;history=History" | H2 blocks with anchors in this page's content, in order; `labels` overrides the link text | About |
| `last-reviewed` | — | This page's `_spk_reviewed` and `_spk_owner`; nothing publicly if no date | About |
| `asof` | — | Today, long format | Hub |

Markup for each view is fixed in §6.4.

### 3.6 Editing workflows (the five jobs; target times for the launch gate)

**Job 1: Post next month's rota** (monthly, **3 minutes**):
1. Dashboard › **Update the rota**. The next 13 Tuesdays are listed with their notes filled in.
2. For each Tuesday type a call sign (the row switches to Call sign), or choose **Open** or **Not posted yet**. On Winlink nights, type the assignment and pick the form.
3. **Save rota**, then **View on site**. Mistake? **Undo last save**.

**Job 2: Add or change an event** (**3 minutes**):
1. Dashboard › **Add an event** (or Events › Add event).
2. Pick the **Kind** first. Type the name as members will see it.
3. **When:** On a date; pick the first day (and the last day if it runs longer). All day is already ticked; untick it to add times.
4. Optional: the short line, one task per line, the main link (groups.io post) and more links, an extra form.
5. **Publish.** The notice links to `/members/exercises/#<slug>`. A yearly repeat: **Duplicate** last year's, change the date, Publish.

**Job 3: Cancel or move a meeting** (**1 minute**): Events › **Regular meetings** › tick **Cancelled** beside the date (or fill **Moved to**) › optional note › **Save**. Home and the hub show the change and the next real date.

**Job 4: Add or replace a document** (**3 minutes**):
1. Documents › **Add document**. Name; pick the section.
2. **Upload a file**, tick the **Privacy check**, then **Choose file**. Or **Link to another site** and paste the web address.
3. Optional: version, short note.
4. **Publish.** New version later: open it › **Replace with** › Choose file (leave "Remove the old file from the web" ticked) › change the version › **Update**. The link `/docs/<slug>/` never changes, and the old file is gone from the web.

**Job 5: Change words on a page** (**5 minutes**):
1. Pages › **Home** (or **Edit page** in the admin bar while viewing Home).
2. Click into a heading, paragraph, list item, button or table cell and type. Change a button's link from its toolbar. Replace the hero photo from the Cover's toolbar. In a list, **bold the first words** of a new item; **Shift+Enter** starts a new line inside an item.
3. **Ctrl+Z** undoes before you save. Then click the save button (its exact word in 7.1 is taken from the click-test screenshot for the guide).
4. If the page answers "Layout changes need an administrator", press Ctrl+Z until the moved or added section is back, then save again, or ask the webmaster. Restoring an older version is the webmaster's job.

**Change the repeater tone** (Net details users only): Net rota › **Net details** › change **Tone**; the preview shows every new sentence › **Save** (confirm). The hub bar, How it works, Home and both Copy buttons update. Nothing else to check.

### 3.7 Time, caching and freshness

- **No page cache at launch** (§1.2). Every page is computed on request for today.
- Every save of an event, document, rota, net details, meetings, tiles or site settings calls **`spokares_purge_cache()`**, which fires the action `spokares_cache_purged`. It is the one place a future cache is purged from.
- Deferred until a page cache actually exists: a TTL to local midnight on date-driven pages, a nightly purge, and `data-start`/`data-end` attributes for a client-side re-check. None are built now.
- Editors are logged in, so they are never served cached pages.

---

## 4. Editors, admin simplification and locking

### 4.1 Accounts and roles

| Account | WordPress role | Enhance panel | Sign-in |
|---|---|---|---|
| Technical admin | Administrator | Owner | TOTP on WordPress and panel; backup codes offline |
| EC break-glass (W7TSC or D5's choice) | Administrator (rarely used) | Second owner | TOTP; backup codes offline |
| Editor 1 (AG7QP, if he agrees) | **ARES Editor** | **None** | E-mailed codes from creation; TOTP and printed backup codes set up in person |
| Editor 2 (to be named) | **ARES Editor** | **None** | Same |
| Members, public | none (registration off; D8) | none | — |

**ARES Editor** (`ares_editor`), created in code on activation and re-synced on version change:
- Has: `read`; `upload_files` (photos through the block editor only; documents only through the Documents form, §5.5); `edit_posts` (the gate WordPress uses for the admin area and for editing one's own uploads); `edit_pages`, `edit_others_pages`, `edit_published_pages`, `publish_pages`; all `spk_event` and `spk_document` caps (edit, edit others, edit published, publish, delete, delete others, delete published, read private); `spokares_edit_rota`.
- Lacks: `edit_others_posts`, `edit_published_posts`, `publish_posts`, every `delete_*posts` and `delete_*pages` cap (so no editor can delete a file or a page), `unfiltered_html`, `manage_categories`, `manage_links`, `moderate_comments`, `edit_theme_options`, `manage_options`, users, plugins, themes, import, export.
- **No new pages or posts:** the `page` and `post` types' `create_posts` capability is mapped to `manage_options` (must-use plugin, `register_post_type_args`). The six pages are fixed; only administrators add a page.
- **Members pages aren't editable:** `map_meta_cap` maps `edit_post` for the pages at `members`, `members/documents` and `members/exercises` to `edit_theme_options`. They have no content; their templates hold everything.
- **Posts are not used.** The Posts menu is hidden, and the must-use plugin returns 404 on the front end for single posts and for post, category, tag, date and author archives.
- `spokares_edit_net_details` (Net details and Meeting rules) is granted **per user by an administrator**: a checkbox shown only on `edit_user_profile` (another user's profile, never your own) and saved only on `edit_user_profile_update` when `current_user_can( 'promote_users' )`. Default: administrators only, until the EC names someone. The grant covers the repeaters' frequency, offset and tone, not the club's call sign (W7GBU), which stays with administrators (QA-039).
- Administrators get every custom cap on activation.
- The slugs and parents of the six pages can't be changed by non-admins (a `wp_insert_post_data` guard).

### 4.2 Admin simplification

- Menu trimmed and ordered as in §3.4; labels are the words editors use ("Net rota", "Add document"). Media is hidden and `upload.php` redirects editors to the Dashboard.
- Admin bar for non-admins: no WordPress logo menu, no "+ New", no comments, no Customize. It keeps "Visit site" and "Edit page" (Home, How it works, About only), and adds **Update lists** on the front end: Net rota, Events, Regular meetings, Documents, Hub tiles (each shown only with the matching capability).
- Front end, logged-in editors only: each dynamic list ends with a small **"Edit this list in {screen}"** link (§6.4). Readers never see it.
- Block editor for non-admins: the Welcome Guide and the starter-pattern window are turned off (`spokares-editor-guard` sets the preferences); code editor off.
- Profile screen for non-admins: only name, e-mail, password and Two-Factor options; colour scheme, keyboard shortcuts, toolbar, website, biography and profile picture are hidden.
- Comments off everywhere (supports removed, closed, admin-bar bubble gone).
- Setup deletes the default Sample Page, the Privacy Policy draft and "Hello world!".
- Dynamic blocks in the editor show a plain-text hint "Edit in wp-admin › {screen}" inside the block (§6.4).

### 4.3 Locking page text (Home, How it works, About)

The core review showed that in 7.1.2 a root `templateLock: 'contentOnly'` leaves the page's **top-level** blocks (the hero Cover, every section Group) in default mode: they keep the Advanced panel (CSS class, anchor, HTML element), Cover overlay and height, and the visibility toolbar, and blocks can be inserted at the page root and inside content containers (Cover, Quote, Buttons, list items). `lock` attributes only stop moving and removing. So the lock is a **server check**, with the editor UI tidied on top.

| Layer | Mechanism | Owner |
|---|---|---|
| 1 Out of reach | Templates, parts, the menu overlay and the three members pages need `edit_theme_options`, which ARES Editors lack | theme + role |
| 2 **Server floor (the real lock)** | For users without `edit_theme_options`, `rest_pre_insert_page` (and `wp_insert_post_data` for non-REST saves, including revision restores) compares a **block skeleton** of the new content with the stored page. If it differs, REST returns `WP_Error( 'spokares_layout_locked', 'Layout changes need an administrator. Undo the last change (Ctrl+Z, or Cmd+Z on a Mac, or the curved Undo arrow at the top left) and save again, or ask the webmaster.', [ 'status' => 403 ] )`; a non-REST save keeps the stored content. The editor's text stays in the editor | plugin |
| 3 Editor tidy-up | `spokares-editor-guard` (plain `wp.data`, pages, non-admins; a copy of core's `DisableNonPageContentBlocks`): `setBlockEditingMode( '', 'disabled' )`, then `'contentOnly'` on each top-level block, re-applied when blocks load. Core's `blockEditor.__unstableCanInsertBlockType` filter allows only list items to be inserted (integration round 2), so no inserter appears inside the Cover or Buttons. Plus `block_editor_settings_all`: `templateLock` `'contentOnly'`, `disableContentOnlyForUnsyncedPatterns` true, `canLockBlocks` false, `codeEditingEnabled` false | plugin |
| 4 Block locks | Every top-level block of a page pattern and every dynamic or theme block carries `"lock":{"move":true,"remove":true}`. No Group `templateLock`, no `metadata.patternName`, no `wp:pattern` in page content | patterns |
| 5 Design controls off | theme.json turns off every colour, typography, spacing, dimension, layout, position, border, shadow, background, lightbox and block-visibility control (§6.7); `DISALLOW_UNFILTERED_HTML` strips per-block custom CSS; no Openverse, block directory, remote patterns, core patterns or font library (7.1.2's theme.json has no font-library switch, so the plugin removes Appearance › Fonts, sends `font-library.php` to Themes and sets the editor's `fontLibraryEnabled` false: QA-032) | theme + plugin + wp-config |
| 6 Short block list, no patterns | `allowed_block_types_all` for non-admins: the block types the patterns use plus `spokares/*` and `spokares-theme/message-path`. **Every theme pattern is `Inserter: no`**, so none can be inserted by anyone (page setup and the members templates read the registry directly, which `Inserter: no` doesn't affect). Nothing is unregistered: the members templates render `spokares/members-*` for logged-in editors too | plugin + patterns |
| 7 Undo | Ctrl+Z (Cmd+Z on a Mac) or the curved Undo arrow before saving; page revisions, restored by the webmaster | core |

**The skeleton** (one function, `spokares_block_skeleton( string $content ): array`):
- `parse_blocks()`, skipping `null`-name whitespace blocks; for each block: its name, its comment attributes (sorted by key) **minus** the ones editors may change, and its inner blocks' skeletons in order.
- Attributes editors may change: `core/cover` `url`, `id`, `alt`, `focalPoint`, `sizeSlug`; `core/image` `id`, `sizeSlug`, `linkDestination`; `metadata.noteId` on any block. Everything else counts: `className`, `anchor`, `lock`, `tagName`, `level`, `ordered`, `start`, layout, style and colour attributes, and **every** attribute of `spokares/*` and `spokares-theme/*` blocks (so a view or limit can't be changed).
- Text that WordPress stores in the HTML (paragraph, heading, list-item and button text, button links, table cells) is not in the comment attributes, so it is free to change. A table's **shape** is counted, though: its sections, rows and header/data cells, read from the HTML (integration round 2). In content-only mode 7.1.2 offers no row controls, but it still shows the Table's Header section and Footer section switches, which change the shape; adding a row stays admin work.
- What prints from the HTML counts too (fixer round): the **class on a block's first tag** (without WordPress's own `wp-…` classes, the Cover's `is-light` and the Image's `size-…`, which follow free attributes) and **every inline `style`** in a block's own HTML (the Cover's image and overlay styles excepted). A class or `<span style>` typed into the HTML is refused. For non-admins, kses also drops `position`, `top`, `right`, `bottom`, `left` and `z-index` from styles, and a button emptied of its words is refused (`spokares_empty_button`).
- Autosaves are never refused (the autosave controller would store the error as an empty autosave); an autosave with a layout change keeps the stored content instead, and a refused save deletes the editor's old autosave of the page.
- For non-admins the page itself is fixed: slug, parent, author, order, password, publish date, published status and template (`_wp_page_template`) keep their stored values whatever path saves (REST, the classic form, Quick Edit), and the members templates are not offered (`theme_page_templates`). Quick Edit and bulk Edit are off on Pages for editors, and core's handlers refuse them for pages, events and documents.
- Inside a `core/list`, the number and order of `core/list-item` children may change: every new item's skeleton must equal an item skeleton already in that list or a bare list item. So stops, lines, years and hops can be added or removed, but a nested cue list can't be added where none existed.

Editors can: change words in headings, paragraphs, list items, buttons and table cells; change link targets; replace the hero photo and its alt text; add or remove list items. They cannot save a change that adds, moves or deletes a section or block, changes a class, anchor, colour, size, spacing or a dynamic block's settings, or touches chrome. Administrators edit layout directly in the block editor (no lock applies to them) and mirror the change in the pattern file (§5.7). The first plan's per-page "Allow layout changes" switch is dropped.

### 4.4 Guard rails on data

**Never throw away what the editor typed.** On a **post form** (Events, Documents) a problem saves everything as a **Draft**, outlines the field in red and adds one sentence naming the field ("Saved as a draft, so it's off the site: the short line mentions a hospital net."). On a **settings screen** (Net rota, Net details, Regular meetings, Meeting rules, Hub tiles) the one field is **not saved** (it keeps its stored value), every other field is saved, and the form comes back with the typed text in the box, outlined, and the sentence (the submitted values are held for 5 minutes in a per-user transient across the redirect).

| Guard | Fields | Level | Behaviour |
|---|---|---|---|
| Call signs | rota Net control, "Volunteer through" | fix | Uppercase; store the **first word** matching `^[A-Z0-9]{1,3}[0-9][A-Z0-9]{0,4}(/[A-Z0-9]+)?$`; say when other words were dropped. No match: rota row not saved (text kept); event saved as Draft |
| Never-publish words | event short line, tasks, link words; rota notes and Winlink tasks; Net details sentences; meeting notes; document name, note and how-to words | block | Matches `hospital net`, `channel \d`, `SHARES (channel\|frequenc)`, `800 ?MHz`, `talkgroup` (case-insensitive): Draft / field held back, as above |
| Phone numbers | every free-text field | confirm | Only real 10-digit North American shapes: `(?<!\d)(?:\+?1[\s.-]?)?\(?[2-9]\d{2}\)?[\s.-]?[2-9]\d{2}[\s.-]?\d{4}(?!\d)` ("WSDOT 580-020", "ICS-213" and dates don't match). First time: Draft / field held back, with a tick beside the field **"This is a public agency number. Publish it."** Saving with the tick accepts it and records a hash of that field's text (`spk_confirmed`), so the same text doesn't ask again |
| E-mail addresses | every free-text field | confirm | Any address not ending `@spokares.org`: as for phone numbers, with **"This is a public agency address. Publish it."** |
| Dates and times | events, meetings | block | Last day ≥ first day; end time after start time; storage `Y-m-d`/`H:i`; display 12-hour |
| Web addresses | all | block | `https://` only (plus `mailto:` for role addresses); `esc_url_raw` with allowed protocols |
| Kind | events | block | Required, no default |
| Uploads | documents | gate | Privacy check first; types and inspection (§5.5) |
| Page text | Home, How it works, About (block editor): the blocks and the excerpt (the page's `<meta name="description">`) | warn | The never-publish, phone and e-mail patterns run in `spokares-editor-guard` on save and show a warning notice ("This page may contain something we never publish: …", and a second one for the excerpt, "This page's excerpt, the description search engines show, …"); saving is never blocked. The dashboard lists pages whose text or excerpt matches (QA-107) |
| Every write handler | forms, GET row actions (Duplicate, Pull this file now), bulk actions, meta boxes, Undo | — | Its own `current_user_can()` check and nonce (`check_admin_referer`; `wp_nonce_url` on GET links). `register_post_meta` auth callbacks don't protect `update_post_meta()` calls in our handlers |

The patterns live in one place (`spokares_check_text()`), and the editor guard script receives them as JSON.

---

## 5. Security, hosting, deployment, updates, backups

**Threat model in one line:** the old Joomla site was hacked through unpatched, abandoned extensions with one volunteer holding the keys. The defences are patching with nobody in the loop, a tiny codebase, strong sign-in, nothing public before it is checked, and backups an intruder can't reach.

### 5.1 Hosting fit (Verpex Enhance)

- PHP **8.4** (security support to 2028-12-31), OPcache on; bump PHP each December after a staging test.
- Web server unknown (probably LiteSpeed): **nothing depends on `.htaccess`** except two rewrite rules above `# BEGIN WordPress` (refuse PHP under uploads; refuse direct `wp-includes/*.php`), and `Options -Indexes` only if the uploads check (§5.5) finds a listable folder. Security headers are sent from PHP.
- Mail: ~100/hour cap, over-limit rejected. All mail (password resets, sign-in codes, notices) goes through **authenticated SMTP** from `website@spokares.org` (constants in `wp-config.php`); turn on DKIM; DMARC `p=none`, then `quarantine` after 4-6 clean weeks. No web form at launch (role `mailto:` links, D11).
- AUP: uploads are public at their URLs (AUP 3.5), and the library is a small club library, not a file host (AUP 3.1). Ask Verpex for a written OK (D10). Audio/video go to archive.org or YouTube.

### 5.2 `wp-config.php`

`DISALLOW_FILE_EDIT` true; `DISALLOW_UNFILTERED_HTML` true; `FORCE_SSL_ADMIN` true; `WP_ENVIRONMENT_TYPE` `production` (or `staging`); `WP_DEBUG` false; any debug log **outside** `public_html`; `SPOKARES_SMTP_HOST/PORT/USER/PASS`; `WP_POST_REVISIONS` 20; file mode 600. **Never** set `DISALLOW_FILE_MODS` or `AUTOMATIC_UPDATER_DISABLED` (they switch off auto-updates; tested). **Never** define `SPOKARES_DEV`, `SPOKARES_TODAY` or `WP_DEVELOPMENT_MODE` outside dev: the weekly check fails if any is present.

### 5.3 The must-use plugin `spokares-hardening`

Hardening and SMTP live in `wp-content/mu-plugins/`, so deactivating `spokares-core` (while troubleshooting, or after a failed deploy) can't switch them off. It is our code, deployed and checked like the rest (§5.7, §5.8). Modules (`spokares-hardening/*.php`, loaded by `spokares-hardening.php`):

- **hardening** (from the tested `reference/hardening.php`): XML-RPC refused (403) and application passwords off; comments and pings closed; generic login errors; login throttle (10 failures per IP in 15 minutes); no generator tag.
- **enumeration:** REST users routes 404 for anonymous users; users sitemap off; author archives and `?author=` 404; `oembed_response_data` drops `author_name` and `author_url`; feeds off (404) and feed links removed; **anonymous requests to `/wp/v2/*` get 401** (`rest_authentication_errors`; the front end uses no REST); lost password always redirects to `wp-login.php?checkemail=confirm` whether or not the account exists, and is throttled at 5 requests per IP per hour and 20 per hour site-wide (shared mail cap).
- **content surface:** single posts and post, category, tag, date and author archives 404; `post` and `page` `create_posts` → `manage_options`.
- **headers**, on `send_headers`, `login_init` and `admin_init`: `X-Content-Type-Options: nosniff`, `Referrer-Policy: strict-origin-when-cross-origin`, `X-Frame-Options: SAMEORIGIN`, `Permissions-Policy` (camera, microphone, geolocation off), CSP `frame-ancestors 'self'; base-uri 'self'; form-action 'self'; object-src 'none'`, HSTS starting at 1 day and raised to a year after a clean month. Ask Verpex about `nosniff` on files the web server serves directly.
- **uploads:** the allowlists and inspection in §5.5; SVG never; 32 MB cap (php.ini); EXIF and GPS data stripped from JPEG originals on upload (re-saved through `wp_get_image_editor()` after WordPress's own orientation fix).
- **two-factor:** §5.4.
- **smtp** (from the tested `reference/smtp.php`).
- **old URLs:** §5.6.
- **fail closed:** if Two-Factor is inactive, users without `manage_options` are refused wp-admin with "Sign-in protection is off. Contact the webmaster.", and administrators see a red notice on every screen.

### 5.4 Two-factor sign-in

- **Providers:** TOTP, Email and Backup codes only (the `two_factor_providers` filter removes the rest).
- **From creation:** on `user_register` the Email provider is enabled for the new user, so no account ever has a password-only window.
- **Enrolment in person:** an administrator sits with each editor, sets up TOTP on the editor's phone, and prints the backup codes onto the back page of that editor's copy of the Editing guide (§6.13; the codes are never stored in the repo). Email stays on as a second way in, so a lost phone doesn't lock anyone out.
- **Enforcement floor** for any user with `edit_posts` and no enabled provider: wp-admin screens redirect to the profile's Two-Factor section; REST requests are refused (`rest_authentication_errors`, 403) except the `two-factor/1.0/*` routes; `admin-ajax.php` and `async-upload.php` (which sets `DOING_AJAX`) are refused except Two-Factor's own actions.
- **Dev only:** enforcement and the Email-on-register step are skipped only when `wp_get_environment_type() === 'local'` **and** `SPOKARES_DEV` is true. Sign-in flows are tested on staging (§6.13 #18).
- Codes by e-mail share the ~100/hour mail cap; the lost-password throttle (§5.3) protects it.

### 5.5 Uploads and document exposure

- **Photos:** JPEG, PNG and WebP through the block editor's media window (the Cover's Replace). For non-admins that window accepts only those three types; administrators' Media Library also accepts PDF. DOCX and XLSX go only through the Documents form, for everyone.
- **Documents:** PDF, DOCX, XLSX, JPEG and PNG, **only through the Documents form**, only when it is saved, and only with the Privacy check ticked (§3.4). The server sets the allowlist for that one request.
- **DOCX/XLSX inspection:** open the file with `ZipArchive` and refuse it if it contains `vbaProject.bin`, `oleObject*`, `activeX*`, a `[Content_Types].xml` entry declaring `macroEnabled`, or any `*.rels` relationship of type `attachedTemplate`, `oleObject`, `frame` or `subDocument` with `TargetMode="External"`. If `ZipArchive` is missing, refuse DOCX/XLSX ("Upload a PDF instead, or ask the webmaster").
- **File names:** a document's stored file name gets a random 8-character suffix (`acs-task-book-7f3a9c2b.docx`), so a file saved in a draft isn't at a guessable address.
- **Replace and Trash** delete the old file from the web by default ("Remove the old file from the web", "Also remove its file from the web", both ticked): `wp_delete_attachment( $id, true )` when no other document's `spk_file` and no page (`wp-image-N` or its URL) uses it; otherwise the file is kept and the notice says where it is used.
- **Pull this file now** (administrators, a nonce'd row action): deletes the attachment at once, sets the document to "Soon" and Draft, and records who and when in the document's notice area.
- **Deletion guard:** `pre_delete_attachment` refuses to delete a file that a document or page uses, except through the three paths above (which clear the reference first). Editors have no delete caps anyway.
- **`/docs/<slug>/`** redirects (302) only when the document is published, privacy-checked and is an upload with a file or a link; the target is the stored **https** URL. Anything else, including drafts and "Soon" items, is 404.
- **Checks** (dev and live, §6.10, §6.11): `/docs/<draft-slug>/` returns 404; `/wp-content/uploads/2026/` is not listable (403 or 404, never an index); after Replace, the old file URL returns 404.
- **Residual:** a file saved with a draft document is reachable by anyone who has its exact random URL. The privacy check has been ticked before it exists.

### 5.6 Old URLs: 301 and 410 map (P1, launch clean-up)

After the hack, old Joomla URLs and spam URLs injected during it (`/index.php?option=com_content…`) would otherwise return 200 with the home page. D14 also wants 410 on the two roster URLs.
- `wordpress/tools/redirects/gen-redirect-map.mjs` (node, no dependencies) reads `research/redirects.csv` and `wordpress/tools/redirects/repoint.csv` and writes `wordpress/mu-plugins/spokares-hardening/redirect-map.php` (a PHP array: `'path?sorted-query' => [ status, target ]`).
- `repoint.csv` maps each of the 35 D15 targets in `redirects.csv` to Option B's six pages and anchors (placement map §2.4). A D15 page with no B equivalent points to the nearest page's top; `/privacy/` points to `/` until a privacy page exists. The plugin builder drafts it; the EC confirms it (D15).
- Rows: `301` → target; `410` (including both roster URLs) → 410; `none` → WordPress's 404; `host` rows are DNS/panel work, not PHP.
- Matching at `do_parse_request` priority 0: exact path, then path plus sorted query string. An unmatched request whose query contains `option=com_` or whose path starts `/index.php/` gets **410**.

### 5.7 Updates, releases and deployment

- **Third-party code:** Enhance toolkit auto-updates on for core (**major**), Two-Factor, UpdraftPlus and one spare default theme. About two weeks before each WordPress major, boot the dev environment with `--wp=<RC>`, run §6.13, and open Home, How it works and About as an administrator and save them once (so any block migration is stored before editors meet the layout check).
- **Our code** (never auto-updated): git tags only. On **every** tag the theme, plugin and must-use plugin carry the tag as `Version` (and their `SPOKARES_*_VERSION` constants). This matters: WordPress caches theme pattern headers by theme `Version`, so a new or renamed pattern file doesn't appear in production until it changes, and the version also busts `site.css`.
- **Checks before a tag** (`ops/ci.sh`, §6.11): PHPCS WordPress-Extra + PHPCompatibilityWP 8.3+, Plugin Check in Playground, a forbidden-code grep (`nopriv`, `register_rest_route`, `$_REQUEST`, `unserialize`, `eval(`, `extract(`), version agreement, and three release zips attached to the tag (`spokares-<v>.zip`, `spokares-core-<v>.zip`, `spokares-hardening-<v>.zip`) so a successor without git can use Plugins › Add New › Upload › Replace current (the must-use plugin goes up by SFTP or the file manager).
- **Staging per release:** clone production to an Enhance staging site, deploy, test, then **delete the staging site** (it holds real password hashes and TOTP secrets and may not get auto-updates). If a staging site must live longer, turn toolkit auto-updates on for it.
- **Deploy** (`ops/deploy.sh <tag> <staging|production>`): code only, with `rsync -rlptz --delay-updates` over SSH: the theme and plugin folders with `--delete`; in `mu-plugins/` only `spokares-hardening.php` and `spokares-hardening/` (with `--delete` inside that folder only, never at the `mu-plugins/` root); `tools/` is never deployed. Then `wp rewrite flush` if routes changed and `wp cache flush`. **Never use Enhance "push live"**: it would overwrite the editors' rota and events. Rollback = deploy the previous tag.
- **Both packages** carry `Update URI: false`; the plugin also filters `site_transient_update_themes` to drop `spokares`.

**Where structure lives, and how it changes:**

| What | Lives in | How it changes |
|---|---|---|
| Templates, parts, menu overlay, CSS, theme.json, the three members pages | theme files (git) | Edit in git, tag, deploy. **Never in the Site Editor** (its edits override theme files; "Reset" restores them) |
| Home, How it works, About | the page in the database (copied from the pattern at setup) | Words: editors. Layout: an administrator edits the page in the block editor and mirrors the change in the pattern file in the same release, so a fresh install matches. P2: `wp spokares page-sync <slug>` re-inserts a page from its pattern, keeps the old version as a revision, and lists the text edits to re-apply by hand |

### 5.8 Monitoring

- **Weekly, on the host** (`ops/weekly-check.sh`, an Enhance cron job, deployed to `~/bin/`): `wp core verify-checksums`; `wp plugin verify-checksums --all --exclude=spokares-core`; `wp plugin is-active two-factor`; the administrator list; **every user with `edit_posts` has an enabled Two-Factor provider**; none of `SPOKARES_DEV`, `SPOKARES_TODAY`, `WP_DEVELOPMENT_MODE` defined; `DISALLOW_UNFILTERED_HTML` and `DISALLOW_FILE_EDIT` on (§5.2); **no `wp_template` or `wp_template_part` post and no `wp_global_styles` post with styles or settings in it** (Site Editor copies that silently replace the theme's files; §5.7, risk 10); `find` checks: no `*.php*`/`*.phtml` under uploads, no folder in `plugins/` or `themes/` outside the expected list, nothing in `mu-plugins/` outside ours plus any Enhance files recorded at setup, no `wp-content/*.php` other than `index.php` and recorded drop-ins. **On success** it pings a dead-man service with `curl -fsS -m 10 "$SPOKARES_PING_URL"` (for example healthchecks.io; the URL lives in the cron command, not in WordPress); on failure it pings `/fail` with a summary and also tries `wp_mail`. **Silence alerts**: the service e-mails the admins when no success ping arrives within 8 days, so a stopped cron or broken mail can't look like "all clear".
- **Monthly and after each deploy, from the admin's machine** (`ops/check-live.sh <tag>`): over SSH, an `rsync -rcn --delete` dry run of the theme, plugin and must-use plugin against a checkout of the tag (any listed file is a difference), the same `find` checks, the upload cap read over SSH (a NOTE unless it is 32 MB, §5.3), and live `curl` checks (§5.11 step 11), including every §5.3 CSP directive on `/`, `/wp-login.php`, `/wp-admin/`, `admin-ajax.php` and `admin-post.php` (the last two run `admin_init` signed out). The host's copy of our code is compared with git, not with a manifest the site user could rewrite.
- External uptime and keyword monitor on `/members/` for the text **"Net control"** (printed by a dynamic block, so a WordPress major that breaks the plugin trips it); Search Console.

### 5.9 Backups

1. **Enhance snapshots** (retention to ask Verpex).
2. **UpdraftPlus:** database weekly (keep 8), uploads monthly (keep 3), to a role-owned Google Drive with 2FA. Convenience only: its Drive token lives in the database, so an intruder could read or delete those copies. Confirm `/wp-content/updraft/` returns 403 on the live server (OpenLiteSpeed ignores `.htaccess`); if it doesn't, move UpdraftPlus's backup directory outside the web root or ask Verpex.
3. **Monthly SSH pull** (`ops/backup-pull.sh`, run from the admin's machine): `ssh … 'wp db export - | gzip'` to a dated file and `rsync -a` of `uploads/`; kept encrypted at rest off the host. **The host holds no credential for this copy**, so a site takeover can't delete it. A git tag covers our code.
- **Restore drill** twice a year on an **Enhance staging site** with the must-use plugin active (Playground runs SQLite, so a pass there proves little about a MySQL restore). Delete the staging site afterwards.
- Options (rota, meetings, radio, tiles) live in the database, so the database copies cover them (a WXR export does not; a JSON export button is P2).

### 5.10 Ask Verpex

Web server kind (Apache, LiteSpeed or OpenLiteSpeed); whether `opcache.validate_timestamps` is on for the site's PHP (LSAPI/FPM): if it is off, PHP files rsync'd by `deploy.sh` don't load until PHP restarts, and `wp cache flush` doesn't reset the web server's OPcache; `expose_php=Off` (the must-use plugin also removes `X-Powered-By`); a web-server deny for `/readme.html` and `/license.txt` (they name the WordPress version, and core updates put them back); `disallowNonWpPhp`; how toolkit auto-updates run and whether staging sites get them; whether panel WordPress SSO bypasses 2FA; ModSecurity availability; Monarx alerts; SMTP ports from inside the container; snapshot retention; whether directory listing is off for `wp-content/uploads/`; `nosniff` on static files; whether `.htaccess` deny rules are honoured (for `wp-content/updraft/`); AUP OK for the library.

### 5.11 Binding checklists from research

- **`security-hosting.md` §9.2 (coding rules) binds every builder**, except: rule 3's `show_in_rest` with a schema (we set `show_in_rest => false`; no REST routes of our own); rule 11's custom-post-type block templates (our types use classic forms); rule 13's `@wordpress/scripts` build (no build step: plain scripts on `wp.*` globals). Rule 9's ICS escaping binds the P2 `/calendar.ics`.
- **`security-hosting.md` §11 (one-time setup) binds the launch**, with these changes: step 3 also means a username that is not "admin", and deleting Hello Dolly, Akismet and every theme except `spokares` and one spare default theme; step 5 deploys the must-use plugin too (§5.7); step 6 follows §5.4 (Email on from creation, TOTP and backup codes in person); step 8 restores on Enhance staging, not Playground; step 10 is §5.8 (dead-man ping and SSH comparison, not a manifest); step 11's live `curl` checks add: `/wp-content/uploads/x.php` 403, `/?author=1` 404, `/feed/` 404, `/wp-json/wp/v2/pages` 401 logged out, `/wp-json/oembed/1.0/embed?url=…` without `author_name`, the security headers on `/`, `/wp-login.php` and `/wp-admin/`, `/wp-content/uploads/2026/` not listable, `/wp-content/updraft/` 403, an old Joomla URL 301, `/index.php?option=com_x` 410, both roster URLs 410.
- **Launch additions:** run the importer in **production mode** (§6.10), then an administrator reviews and publishes every document (ticking its Privacy check) and clears or drafts every "Needs checking" item; the round-3 launch blockers are closed (§7.1 #11); the footer mock line is removed in the launch release; the launch gate is passed (§6.13).

---

## 6. BUILD CONTRACT

Four builders work in parallel, then the integration step (§6.13) runs. This section is the interface between them: names, data shapes, markup and class names. **Code against this section, not against each other's files.** If something here is impossible or wrong, do the closest workable thing, keep the published names unchanged, and list the deviation at the top of your final report.

### 6.0 Rules for every builder

1. **Write only inside your own directories** (§6.1). The design and research folders are read-only inputs.
2. **Words:** B's built pages (`design/round3/option-b-lines-down/*.html`, `members/*.html`) are the wording reference; they already apply `_shared/copy-*.md`. Where B's HTML and a copy file differ, B's HTML wins, except for the §2.3 fixes. **Data:** `_shared/assets/data.js`. Never invent a fact, a date, a name or a number. The only new visible words are those this file quotes.
3. **People:** call signs only; no personal names, phone numbers or personal e-mails anywhere (code, seed, test users, screenshots). Role addresses only (`join@`, `ec@`, `webmaster@spokares.org`).
4. **House style:** 12-hour times ("8:00 PM", "9:00 AM–noon"); en dash in ranges; no `href="#"`; no arrow glyphs in link text; sentence case; curly apostrophes as in B.
5. **URLs:** internal links in parts and patterns are root-relative (`/about/#licensing`); PHP uses `home_url()`.
6. **PHP:** research `security-hosting.md` §9.2 is binding (exceptions in §5.11). Every file starts with `defined( 'ABSPATH' ) || exit;` (except `block.json`/HTML). Escape late, sanitise early, `$wpdb->prepare()` for any direct SQL (avoid direct SQL). Prefix all functions, hooks and globals. No `eval`, `extract`, `unserialize`, `$_REQUEST`, remote fetches without timeouts, or file writes at runtime.
7. **Every write handler checks its own capability and nonce**: form posts, GET row actions (`wp_nonce_url` + `check_admin_referer`), bulk actions, meta boxes, Undo, Duplicate, Pull. `register_post_meta` auth callbacks don't cover `update_post_meta()` in our own handlers.
8. **Never throw away what an editor typed** (§4.4).
9. **Dev-only switches** (`SPOKARES_TODAY`, `?today=`, the two-factor skips, the dev login) require `wp_get_environment_type() === 'local'` **and** `defined( 'SPOKARES_DEV' ) && SPOKARES_DEV`.
10. **No build step:** no npm/composer dependencies, no `node_modules`, no bundler. JavaScript is plain ES modules (front end) or plain scripts using `wp.*` globals (admin). Node scripts in `dev/`, `ops/` and `tools/` use only Node's standard library.
11. **Local WordPress:** use only your assigned port; start with `wordpress/dev/start.sh <PORT>` once it exists (§6.10), otherwise the brief's command; always stop your server (`lsof -ti tcp:<PORT> | xargs kill`).
12. **Target:** WordPress 7.1.2 (floor **7.1**), PHP 8.3+ (test on 8.4), theme.json v3.

### 6.1 Ownership map

| Builder | Owns (writes only here) | Must not touch |
|---|---|---|
| **Theme** | `wordpress/theme/spokares/` **except** `patterns/`: `style.css`, `theme.json`, `functions.php`, `inc/*.php`, `templates/*.html`, `parts/*.html`, `blocks/brand/`, `blocks/message-path/`, `assets/css/`, `assets/fonts/`, `assets/img/`, `assets/js/` (only if needed), `screenshot.png`, `readme.txt` | `patterns/`, the plugins, `dev/`, `ops/` |
| **Plugin** | `wordpress/plugins/spokares-core/` (everything); `wordpress/mu-plugins/` (`spokares-hardening.php`, `spokares-hardening/`); `wordpress/tools/redirects/` | the theme, `dev/`, `ops/` |
| **Patterns** | `wordpress/theme/spokares/patterns/*.php` | everything else |
| **Dev environment and ops** | `wordpress/dev/` (everything); `wordpress/ops/` (everything) | theme, patterns, plugins |
| **Integration** (the architect, after the four) | `wordpress/guide/` (Editing guide and its screenshots); fixes go back to the owning builder | — |

Everything visual, including the CSS for the plugin's block markup and the `spk-edit-*` hints, belongs to the theme. The plugin ships **no front-end CSS** (admin CSS only). The plugin's front-end JavaScript is limited to the three modules in §6.4.

### 6.2 Shared names registry

| Thing | Name |
|---|---|
| Theme slug / text domain / prefix | `spokares` / `spokares` / functions `spokares_theme_*`, constant `SPOKARES_THEME_VERSION` |
| Plugin slug / text domain / prefix | `spokares-core` / `spokares-core` / functions `spokares_*`, constants `SPOKARES_CORE_VERSION`, `SPOKARES_CORE_DIR`, `SPOKARES_CORE_URL` |
| Must-use plugin | loader `spokares-hardening.php` + folder `spokares-hardening/`; text domain `spokares-hardening`; functions `spokares_hard_*`; constant `SPOKARES_HARDENING_VERSION` |
| Versions | all three start at `0.1.0` and always equal the repo tag; headers `Requires at least: 7.1`, `Requires PHP: 8.3`, `Tested up to: 7.1`, `Update URI: false` (theme and plugin) |
| Plugin blocks / category | `spokares/events`, `spokares/net`, `spokares/meetings`, `spokares/docs`, `spokares/toc`, `spokares/last-reviewed`, `spokares/asof` / block category slug `spokares`, title "ARES" (registered by the plugin) |
| Block views (`view` attribute) | events: `upcoming`, `next-up`, `later`, `public-service`, `past` · net: `bar`, `settings`, `rota`, `winlink`, `from-home`, `other-nets` · meetings: `home`, `next` · docs: `library`, `search`, `tiles` |
| Theme blocks | `spokares-theme/brand`, `spokares-theme/message-path`; category `theme` |
| Pattern namespace / categories | `spokares/*`; `spokares-pages` ("ARES: whole pages"), `spokares-sections` ("ARES: sections"), `spokares-members` ("ARES: members pages"), registered by the theme in `functions.php` |
| Pattern slugs | Sections: `home-hero`, `home-what-we-do`, `dawn-hinge`, `home-visit`, `home-join`, `how-opener`, `how-nets`, `how-activation`, `how-messages`, `how-exercises`, `how-roles`, `about-head`, `about-body`, `about-ares-acs`, `about-legal-basis`, `about-who-we-serve`, `about-leadership`, `about-joining`, `about-training`, `about-licensing`, `about-history`, `about-contact`. Pages: `page-home`, `page-how-it-works`, `page-about`. Members bodies (used by templates): `members-hub`, `members-documents`, `members-exercises`. All prefixed `spokares/`; file name = slug without the prefix (`patterns/home-hero.php`) |
| Templates | `index`, `front-page`, `page`, `page-members`, `page-documents`, `page-exercises`, `404`, `search` |
| Template parts (area) | `header` (header), `footer` (footer), `members-nav` (uncategorized), `navigation-overlay` (navigation-overlay; P1) |
| Post types | `spk_event`, `spk_document` |
| Taxonomy | `spk_doc_cat` (terms `net-ops`, `join`, `forms`, `training`, `readiness`, `digital`, `reference`) |
| Options | `spk_rota`, `spk_rota_prev`, `spk_rota_saved`, `spk_nets`, `spk_radio`, `spk_meetings`, `spk_site`, `spk_tiles`, `spk_db_version`, `spk_seeded` |
| Page meta | `_spk_reviewed`, `_spk_owner` |
| Attachment meta | `_spk_seed` (`hero` on the imported hero photo) |
| Role | `ares_editor` ("ARES Editor") |
| Capabilities | `spokares_edit_rota`, `spokares_edit_net_details`; CPT caps from `capability_type` `spk_event`/`spk_events` and `spk_document`/`spk_documents` |
| Admin pages | `spokares-rota` (Net rota), `spokares-net-details` (Net details), `spokares-meetings` (Regular meetings), `spokares-meeting-rules` (Meeting rules), `spokares-tiles` (Hub tiles), `spokares-site` (Settings › ARES site) |
| Dashboard widget | `spokares_site_tasks` |
| Layout-check error | code `spokares_layout_locked`, status 403, message "Layout changes need an administrator. Undo the last change (Ctrl+Z, or Cmd+Z on a Mac, or the curved Undo arrow at the top left) and save again, or ask the webmaster." An emptied button is refused with `spokares_empty_button`. |
| Front-end routes | `/docs/<slug>/` (query var `spk_doc`) · P2: `/calendar.ics` (query var `spk_ics`) |
| Library query params | `q` (search words, at most 100 characters), `section` (one of the 7 section slugs or `most-used`; anything else is ignored) |
| Script modules (front end) | `@spokares/copy`, `@spokares/doc-library`, `@spokares/toc` (plugin) |
| Admin script handles | `spokares-editor-guard` (block editor, pages), `spokares-admin-forms` (plugin screens) |
| Theme style handle | `spokares-site` (`assets/css/site.css`, front end + editor) |
| Body classes (theme) | `spk-verify` when a logged-in user with `edit_pages` adds `?verify=1` |
| Dev constants | `WP_ENVIRONMENT_TYPE` `local`, `SPOKARES_DEV` true, `WP_DEVELOPMENT_MODE` `theme`, `SPOKARES_TODAY` (`Y-m-d`); never outside dev |
| Dev users | `admin` / `password` (Administrator, exists in Playground); `editor` / `password` (role `ares_editor`, display name "Test Editor", e-mail `editor@example.invalid`) |
| Dev login | `?dev_login=1` (admin) or `?dev_login=editor`; dev only and loopback only |
| Importer | `dev/seed/import.php`, mode `dev` or `production` (§6.10) |

### 6.3 Data schema (plugin writes and reads; the importer writes exactly this)

**Storage conventions:** dates `Y-m-d`; times `H:i` 24-hour (`''` when none); booleans stored as `'1'`, and deleted (or `''`) when false; arrays stored as PHP arrays (WordPress serialises them); all meta `single => true`; `show_in_rest => false` everywhere; no meta revisions. Anchors are post slugs (`post_name`). Confirmed phone or e-mail text (§4.4) is stored as `field key => sha1( text )`: in post meta `spk_confirmed`, in a rota row's `confirmed` key, and in a `confirmed` key of `spk_nets`, `spk_meetings` or `spk_tiles`.

**`spk_event` meta**

| Key | Type | Values / rule | Notes |
|---|---|---|---|
| `spk_kind` | string | `exercise` \| `training` \| `on-air` \| `public-service` | required, no default |
| `spk_date_mode` | string | `date` \| `not-posted` \| `as-requested` | default `date`; `as-requested` only for `public-service`; the last two print "Date not posted" / "As requested" |
| `spk_start` | date | `Y-m-d` | required when mode is `date` |
| `spk_end` | date | `Y-m-d` or `''` | ≥ start |
| `spk_time_start` | time | `H:i` or `''` | `''` = all day (the "All day" tick clears both times) |
| `spk_time_end` | time | `H:i` or `''` | after start |
| `spk_summary` | string | plain text ≤ 90 chars | list line and card intro |
| `spk_tasks` | string | plain text, one task per line, ≤ 10 lines | card `<ol>` (exercise) |
| `spk_main_url` | string | https URL or `''` | the card's button; also links the title in "Later this season" |
| `spk_main_label` | string | default "Exercise details on groups.io" for groups.io URLs, else "Details" | button words |
| `spk_links` | array | "More links": `[ ['label'=>'ARRL SET forms','url'=>'https://…'], … ]`, ≤ 3 | card text links; the first links the "Later" title when there is no main link |
| `spk_extra_doc` | int | a published `spk_document` ID or 0 | "Extra form: …" (exercise) |
| `spk_contact_call` | string | call sign or `''` | "Volunteer through" (public service) |
| `spk_keep_past` | bool | | "Keep in Past exercises" (exercise) |
| `spk_precision` | string | `day` \| `month` \| `year` | default `day`; imported history uses month/year |
| `spk_needs_check` | bool | | admin-only flag, never shown to readers |
| `spk_confirmed` | array | field key → sha1 of the text an editor confirmed (§4.4) | internal |
| `spk_sort` | date | computed on save: `spk_start`, or `9999-12-31` if undated | order and queries |
| `spk_end_sort` | date | computed on save: `spk_end` or `spk_start`, or `9999-12-31` | "upcoming" = `spk_end_sort` ≥ today |

Menu order (`menu_order`) orders undated rows (public service and "later").

**`spk_document` meta** (plus `post_title`, `post_name` = row anchor and `/docs/<slug>/`, `menu_order` = position in its section, one `spk_doc_cat` term)

| Key | Type | Values / rule |
|---|---|---|
| `spk_source` | string | `upload` \| `link` \| `soon` |
| `spk_file` | int | attachment ID (when `upload`) |
| `spk_url` | string | https URL (when `link`) |
| `spk_source_label` | string | printed in the Source cell ("FEMA", "ag7qp.com", "groups.io, members") |
| `spk_format` | string | "PDF", "DOCX", "XLSX", "Online form", "Online course", "Online course, free", "Video", "Winlink form", "Software", "PDF or app", or `''` |
| `spk_version` | string | ≤ 30 chars ("v3", "2026-03-04; GMRS 2025-08-19", "July 2024") |
| `spk_note` | string | ≤ 8 words / 60 chars |
| `spk_howto_label`, `spk_howto_url` | string | optional "how to" link shown as the row note (a doc with both a note and a how-to shows the how-to) |
| `spk_sublinks` | array | admin-only: `[ ['id'=>'is-100','label'=>'IS-100.c','title'=>'Introduction to the Incident Command System','url'=>'https://…'], … ]` ≤ 6; `id` = `sanitize_title( label )` without dots when created in the form |
| `spk_most_used` | bool | the "Most used" filter chip |
| `spk_keywords` | string | admin-only: comma-separated search words |
| `spk_owner` | string | role or call sign |
| `spk_reviewed` | date | `Y-m-d` or `''` |
| `spk_privacy_ok` | bool | required to publish and to upload |
| `spk_needs_check` | bool | admin-only |
| `spk_confirmed` | array | as for events |

**Term meta** on `spk_doc_cat`: `spk_order` (int 1-7, order above).

**Options**

```php
spk_rota = [                                   // keyed by Tuesday date; a Tuesday with no row is 'tbd'
  '2026-09-29' => [ 'state' => 'call', 'call' => 'NZ2S',  'note' => '', 'wl_task' => '', 'wl_form' => '' ],
  '2026-10-06' => [ 'state' => 'open', 'call' => '',      'note' => '', 'wl_task' => '', 'wl_form' => '' ],
  '2026-10-13' => [ 'state' => 'call', 'call' => 'AE7RJ', 'note' => '', 'wl_task' => 'Did You Feel It report (ShakeOut practice)', 'wl_form' => 'DYFI' ],
  '2026-11-10' => [ 'state' => 'tbd',  'call' => '',      'note' => '', 'wl_task' => 'Where you expect to have Thanksgiving dinner', 'wl_form' => 'ICS-213' ],
  // state: 'call' | 'open' | 'tbd'. 'call' is non-empty only when state is 'call'. Optional 'confirmed' => [ field => sha1 ].
];
spk_rota_prev  = [ 'at' => '2026-09-26T10:14:00-07:00', 'by' => 2, 'rows' => [ '2026-10-13' => <row before that save> | null ] ];
                 // only the rows the most recent save changed; "Undo last save" writes them back
spk_rota_saved = [ 'at' => '2026-09-26T10:14:00-07:00', 'by' => 2 ];

spk_nets = [
  'net_time'       => '20:00',            // weekly net, every Tuesday (weekday fixed: Tuesday)
  'winlink_nth'    => [ 2, 4 ],
  'simplex_nth'    => [ 5 ],
  'gmrs_nth'       => [ 3 ],
  'gmrs_time'      => '19:30',
  'winlink_howto'  => 'Answer on an ICS-213 unless another form is named. Send to NV2Z and AG7QP between 5:00 and 9:00 PM on the date shown. Radio preferred; Telnet OK.',
  'open_slot_line' => 'Open slot? Tell the Net Manager (AG7QP) on the net or on groups.io.',
  'needs_check'    => true,               // B marks the three "Other nets" lines data-verify
];

spk_radio = [
  'primary'   => [ 'call' => 'W7GBU', 'freq' => '147.300', 'offset' => '+600 kHz', 'tone' => '100 Hz' ],
  'alternate' => [ 'freq' => '146.880', 'offset' => '-600 kHz', 'tone' => '123 Hz', 'show' => true, 'needs_check' => true ],
];
// Derived, never stored: display "147.300 MHz, +600 kHz, 100 Hz tone" (minus shown as U+2212);
// bar "W7GBU 147.300 MHz, +600 kHz, 100 Hz"; copy "W7GBU 147.300 MHz, +600 kHz offset, 100 Hz tone";
// alternate copy "Alternate repeater 146.880 MHz, -600 kHz offset, 123 Hz tone".

spk_meetings = [
  'meetings' => [
    [ 'id' => 'workshop', 'name' => 'Second Saturday Workshop', 'nth' => [ 2 ], 'weekday' => 6,
      'start' => '09:00', 'end' => '12:00', 'time_text' => '', 'home_extra' => 'then a Winlink workshop, 12:30–3:30 PM',
      'skip_months' => [], 'show_home' => true, 'active' => true, 'needs_check' => false ],
    [ 'id' => 'winlink-workshop', 'name' => 'Winlink workshop', 'nth' => [ 2 ], 'weekday' => 6,
      'start' => '12:30', 'end' => '15:30', 'time_text' => '', 'home_extra' => '',
      'skip_months' => [], 'show_home' => false, 'active' => true, 'needs_check' => true ],
    [ 'id' => 'third-thursday', 'name' => 'Third Thursday training meeting', 'nth' => [ 3 ], 'weekday' => 4,
      'start' => '', 'end' => '', 'time_text' => 'evenings', 'home_extra' => '',
      'skip_months' => [ 12 ], 'show_home' => true, 'active' => true, 'needs_check' => true ],
  ],
  'changes' => [   // [ 'date' => 'Y-m-d', 'meeting' => 'workshop', 'kind' => 'cancelled'|'moved', 'new_date' => 'Y-m-d'|'', 'note' => '' ]
  ],
];
// weekday: 0 = Sunday … 6 = Saturday.
// Home line = name + ", " + (time_text ?: range(start, end)) + (home_extra ? ", " + home_extra : "")
//   → "Second Saturday Workshop, 9:00 AM–noon, then a Winlink workshop, 12:30–3:30 PM"
//   → "Third Thursday training meeting, evenings"
// Hub line = date + ", " + (start ? time(start) : time_text) + ", " + name
//   → "Sat, Oct 10, 9:00 AM, Second Saturday Workshop"

spk_site = [    // the meeting place only (QA-038); an older stored value's groupsio_* keys are ignored on read and dropped on the next save
  'place' => [ 'name' => 'Spokane County Emergency Management', 'street' => '1121 W Gardner Ave', 'city' => 'Spokane',
               'state' => 'WA', 'zip' => '99260', 'needs_check' => true ],
];

spk_tiles = [    // exactly 4 slots, in order; 'doc' = spk_document post ID, or 0 for an empty slot
  [ 'doc' => <ID of net-scripts>,   'label' => 'Net script',    'icon' => 'script' ],
  [ 'doc' => <ID of ics-213>,       'label' => 'ICS-213',       'icon' => 'form' ],
  [ 'doc' => <ID of ics-214>,       'label' => 'ICS-214',       'icon' => 'log' ],
  [ 'doc' => <ID of acs-task-book>, 'label' => 'ACS Task Book', 'icon' => 'book' ],
];   // icon: script | form | log | book; a document appears in at most one slot

spk_db_version = '1';   spk_seeded = '2026-09-26';   // set by the importer
```

**Page meta:** `_spk_reviewed` (`Y-m-d` or absent), `_spk_owner` (string, e.g. "Emergency Coordinator"), saved by the Page review box (nonce + `edit_post`). **Attachment meta:** `_spk_seed` (`'hero'`).

**Plugin helper functions** (public, stable; the dashboard, blocks and the importer may call them):
`spokares_today(): string` · `spokares_fmt_time( string $hhmm ): string` · `spokares_fmt_date( string $ymd, string $style = 'short' ): string` · `spokares_fmt_when( WP_Post|int $event, string $style ): string` · `spokares_net_on( string $ymd ): array` · `spokares_rota_rows( int $weeks ): array` · `spokares_next_meetings( int $per_meeting = 1 ): array` · `spokares_events( string $view, array $args ): WP_Post[]` · `spokares_radio_line( string $variant ): string` · `spokares_link( string $url, string $label, string $class = '', string $source = '' ): string` · `spokares_icon( string $name ): string` · `spokares_call_sign( string $typed ): array` (`['call'=>…, 'dropped'=>bool]`, `call` '' when none matches) · `spokares_check_text( string $text ): array` (list of `['level'=>'block'|'confirm','what'=>…]`) · `spokares_block_skeleton( string $content ): array` · `spokares_is_editor_preview(): bool` · `spokares_purge_cache(): void`.

### 6.4 Plugin blocks: names, attributes, markup

**`block.json` shape** (one folder per block in `spokares-core/blocks/<name>/`; `inc/blocks.php` registers every folder with `register_block_type()`):

```json
{
  "$schema": "https://schemas.wp.org/trunk/block.json",
  "apiVersion": 3,
  "name": "spokares/net",
  "title": "Net facts",
  "category": "spokares",
  "icon": "microphone",
  "description": "The weekly net: settings, rota and Winlink. Edit it in Net rota and Net details.",
  "textdomain": "spokares-core",
  "attributes": {
    "view":  { "type": "string",  "default": "rota", "enum": [ "bar", "settings", "rota", "winlink", "from-home", "other-nets" ], "label": "What to show" },
    "weeks": { "type": "integer", "default": 5, "label": "Weeks (rota)" },
    "limit": { "type": "integer", "default": 8, "label": "Rows (Winlink)" }
  },
  "supports": { "autoRegister": true, "html": false, "reusable": false, "className": true },
  "usesContext": [ "postId" ],
  "render": "file:./render.php"
}
```

| Block (title) | Attributes (defaults) | Front-end module | "Edit" screen named in hints and links |
|---|---|---|---|
| `spokares/events` ("Events list") | `view` "upcoming"; `types` string "exercise,training"; `limit` int 2; `days` int 60; `cardTypes` string "exercise"; `cardLimit` int 2 | — | Events |
| `spokares/net` ("Net facts") | `view` "rota"; `weeks` int 5; `limit` int 8 | `@spokares/copy`, enqueued by `render.php` (`wp_enqueue_script_module`) only for `bar` and `settings` | Net rota (`rota`, `winlink`); Net details (`bar`, `settings`, `from-home`, `other-nets`) |
| `spokares/meetings` ("Meetings") | `view` "home"; `meeting` string "" | — | Regular meetings |
| `spokares/docs` ("Documents") | `view` "library" | `@spokares/doc-library`, enqueued by `render.php` only for `library` | Documents (`library`, `search`); Hub tiles (`tiles`) |
| `spokares/toc` ("On this page") | `title` string "On this page"; `labels` string "legal-basis=Legal basis;leadership=Leadership;history=History" | `@spokares/toc` via `viewScriptModule` | — |
| `spokares/last-reviewed` ("Last reviewed") | — | — | the Page review box |
| `spokares/asof` ("Schedule as of") | — | — | — |

**Rules common to all blocks:**
- The root is always `<div <?php echo get_block_wrapper_attributes( [ 'class' => 'is-view-' . $view ] ); ?>>` (e.g. `class="wp-block-spokares-net is-view-rota"`; blocks without views omit the class), containing B's markup exactly as below. An unknown view prints nothing publicly.
- External links: `spokares_link()` prints `<a class="ext" href="…">Label<span class="vh"> (opens {name})</span></a>`, where `{name}` comes from a small host map copied from B's link texts (for example `arrl.org` → "ARRL", `training.fema.gov` → "FEMA", `spokaneares-acs.groups.io` → "groups.io, members only"), or the host without `www`. Internal links are plain `<a>`.
- Icons are inline: `<svg class="icon" aria-hidden="true" focusable="false" viewBox="0 0 24 24">…</svg>`, paths copied from B's sprite (`i-copy`, `i-search`, `i-menu`, `i-script`, `i-form`, `i-log`, `i-book`).
- `needs-verify` is added to the element B marked `data-verify` when the matching `needs_check` flag is set (place, nets, alternate radio, meetings).
- **Editor preview** (`spokares_is_editor_preview()`: a REST block-renderer request, or the autoRegister preview, by a user who can edit): after the markup and **inside the wrapper**, print plain text `<p class="spk-edit-hint">Edit in wp-admin › {screen}</p>`. No link: the preview is wrapped in `useDisabled()`, so it can't be clicked. A view that prints nothing publicly (`last-reviewed` without a date, `past` or `tiles` with nothing to show) prints an editor-only placeholder sentence instead, so the block stays visible and selectable: "Last reviewed appears here once a date is set in the Page review box." / "Past exercises appear here after they end." / "Hub tiles: none set."
- **Front-end edit link:** for a logged-in user who can use the named screen (and not in the preview), append `<p class="spk-edit-link"><a href="{admin URL of the screen}">Edit this list in {screen}</a></p>` inside the wrapper. Not printed by `toc` or `asof`.
- Empty states print one short plain sentence inside the same wrapper (listed per view). Never an empty table.
- Escaping: every value late (`esc_html`, `esc_attr`, `esc_url`). The library reads `q` with `sanitize_text_field( wp_unslash() )`, cuts it to 100 characters, and accepts `section` only from the allowlist.
- **Not used in v1:** editor JavaScript for blocks (autoRegister generates the controls), Block Bindings (a binding covers a whole attribute, so it can't put a frequency inside a sentence), the Interactivity API, any public form handler, cache headers. Add none of them without changing this contract.

**Markup** (`{w}` = the wrapper `div`; `[ … ]` = only when present; `…` = repeats):

`spokares/events` › `upcoming` (empty: `<p class="m-line">Nothing scheduled in the next 60 days.</p>`, using `days`)
```html
<div {w}><ul class="events">
  <li class="event[ is-now]">
    <span class="date-slab[ date-slab--range]" aria-hidden="true"><span class="date-slab__mon">Sep</span><span class="date-slab__day">26–27</span><span class="date-slab__dow">Sat–Sun</span></span>
    <span class="event__body"><span class="event__title">ARES-ACS / WSDOT exercise</span><span class="event__meta"><span class="vh">Sat–Sun, Sep 26–27. </span>All weekend</span>[<span class="event__now"><span class="tag tag--now">Happening now</span></span>]</span>
  </li>
</ul></div>
```

`spokares/events` › `next-up` (empty: `<p class="m-line">No exercises scheduled yet.</p>`)
```html
<div {w}><div class="ex-cards">
  <article class="card ex-card[ card--red on the first]" id="{slug}">
    <h3>{title}</h3>
    <p class="ex-when"><time datetime="{start}">{when}</time>[ <span class="ex-status"><span class="tag tag--now">Happening now</span></span>]</p>
    [<p class="ex-summary">{summary}</p>]
    [<ol class="ex-tasks"><li>{task}</li>…</ol>]
    [<p class="ex-form">Extra form: <a href="/members/documents/#{doc-slug}">{doc title}</a></p>]
    [<p class="ex-link">{spokares_link(url, label)}</p> …one per "more link"]
    [<p class="ex-action"><a class="btn btn--red|btn--ghost ext" href="{main_url}">{main_label}<span class="vh"> (opens {name})</span></a></p>]
  </article>
</div></div>
```
The first card gets `card--red` and a `btn--red` button; later cards get `btn--ghost` (one filled button per page).

`spokares/events` › `later` (empty: `<p class="m-line">Nothing else scheduled yet.</p>`)
```html
<div {w}><table class="table ex-table ex-later">
  <caption class="vh">Exercises and on-air events later this season</caption>
  <thead class="vh"><tr><th scope="col">When</th><th scope="col">What</th></tr></thead>
  <tbody>
    <tr id="{slug}"><th scope="row">Thu, Oct 15, 10:15 AM</th><td>{title, linked to main_url, else links[0], via spokares_link}[: {summary}]</td></tr>…
    <tr id="{slug}"><th scope="row">Date not posted</th><td>…</td></tr>…   <!-- undated rows last -->
  </tbody>
</table></div>
```

`spokares/events` › `public-service` (empty: `<p class="m-line">No public-service events posted yet.</p>`)
```html
<div {w}><table class="table table--dense ex-table ex-public">
  <caption class="vh">Public-service events</caption>
  <thead><tr><th scope="col">Event</th><th scope="col">When</th><th scope="col">Volunteer through</th></tr></thead>
  <tbody><tr id="{slug}"><th scope="row">Bloomsday</th><td data-label="When">Sun, May 2, 2027</td><td data-label="Volunteer through">NV2Z</td></tr>…</tbody>
</table></div>
```

`spokares/events` › `past` (empty: nothing publicly; the editor placeholder in the preview)
```html
<div {w}><ul class="ex-past"><li><span class="ex-past__when">Oct 2022</span> <span>Rockford exercise</span></li>…</ul></div>
```

`spokares/net` › `winlink` (empty table replaced by `<p class="m-line">No assignments posted yet.</p>` after the how-to line)
```html
<div {w}>
  <p class="m-line ex-howto">{spk_nets.winlink_howto}</p>
  <table class="table table--dense ex-table ex-winlink">
    <caption class="vh">Winlink assignments</caption>
    <thead><tr><th scope="col">Date</th><th scope="col">Assignment</th><th scope="col">Form</th></tr></thead>
    <tbody><tr class="is-next"><th scope="row"><time datetime="2026-10-13">Tue, Oct 13</time></th><td data-label="Assignment">{wl_task}</td><td data-label="Form">{wl_form}</td></tr>…</tbody>
  </table>
</div>
```

`spokares/net` › `rota`
```html
<div {w}>
  <table class="table table--dense hub-rota">
    <caption class="vh">Net control, next five Tuesdays</caption>
    <thead><tr><th scope="col">Tuesday</th><th scope="col">Net control</th><th scope="col">Note</th></tr></thead>
    <tbody>
      <tr class="is-next"><th scope="row"><time datetime="2026-09-29">Tue, Sep 29</time></th><td data-label="Net control"><b>NZ2S</b></td><td data-label="Note"><a href="/members/documents/#net-scripts">Simplex</a></td></tr>
      <tr><th scope="row">…Tue, Oct 6…</th><td data-label="Net control"><a class="tag tag--open" href="#open-slot">Open</a></td><td data-label="Note"></td></tr>
      <tr><th scope="row">…Tue, Oct 13…</th><td data-label="Net control"><b>AE7RJ</b></td><td data-label="Note"><a href="/members/exercises/#winlink-assignments">Winlink night</a></td></tr>
      <tr><th scope="row">…Tue, Oct 20…</th><td data-label="Net control"><b>WA7LNC</b></td><td data-label="Note">GMRS net, 7:30 PM</td></tr>
      <!-- state 'tbd': <span class="rota-tbd">Not yet published</span>; a row note follows the rule note after "; " -->
    </tbody>
  </table>
  <p class="hub-open" id="open-slot">{spk_nets.open_slot_line}</p>
</div>
```
("five" in the caption follows `weeks`.)

`spokares/net` › `bar`
```html
<div {w}><div class="netbar dark">
  <p class="netbar__line"><span class="netbar__time">8:00 PM</span> <span class="netbar__set"><span class="nowrap">W7GBU 147.300 MHz,</span> <span class="nowrap">+600 kHz, 100 Hz</span></span></p>
  <button class="btn btn--light btn--sm" type="button" data-copy-text="W7GBU 147.300 MHz, +600 kHz offset, 100 Hz tone" hidden>{icon copy}Copy<span class="vh"> radio settings</span></button>
</div></div>
```

`spokares/net` › `settings`
```html
<div {w}><div class="netbox" id="weekly-net">
  <p class="netbox__when">Every Tuesday, 8:00 PM</p>
  <dl class="netbox__rows">
    <div><dt>W7GBU</dt><dd>147.300 MHz, +600 kHz, 100 Hz tone</dd><dd><button class="btn btn--ghost btn--sm netbox__copy" type="button" data-copy-text="W7GBU 147.300 MHz, +600 kHz offset, 100 Hz tone" hidden>{icon copy}Copy<span class="vh"> W7GBU settings</span></button></dd></div>
    [<div><dt>Alternate</dt><dd>146.880 MHz, −600 kHz, 123 Hz tone</dd><dd><button … data-copy-text="Alternate repeater 146.880 MHz, -600 kHz offset, 123 Hz tone" hidden>…Copy<span class="vh"> alternate settings</span></button></dd></div>]
  </dl>
</div></div>
```

`spokares/net` › `from-home` (the pattern gives this block `className` "card card--dawn home-visit__air", so the wrapper is the card)
```html
<div {w}><p><strong>From home:</strong> listen to the Tuesday net, <strong>8:00 PM</strong>, <strong>147.300 MHz</strong>, on any scanner or 2-meter radio. No license needed.</p></div>
```

`spokares/net` › `other-nets`
```html
<div {w}><ul class="lines[ needs-verify]">
  <li><strong>Winlink nights:</strong> 2nd and 4th Tuesdays; net control gives a <a href="/members/exercises/#winlink-assignments">Winlink assignment</a> during the net.</li>
  <li><strong>Fifth Tuesdays:</strong> the net starts on simplex, then moves to W7GBU.</li>
  <li><strong>ACS GMRS net:</strong> 3rd Tuesdays, 7:30 PM, for county volunteers with GMRS licenses.</li>
</ul></div>
```
Weeks from `winlink_nth`, `simplex_nth`, `gmrs_nth` (§3.3 ordinals), the time from `gmrs_time`, the call from `spk_radio.primary.call`; the fixed words are B's.

`spokares/meetings` › `home`
```html
<div {w}>
  <p[ class="needs-verify"]><strong>In person</strong> at Spokane County Emergency Management, 1121 W Gardner Ave, Spokane:</p>
  <ul class="meetings">
    <li><span>Second Saturday Workshop, 9:00 AM–noon, then a Winlink workshop, 12:30–3:30 PM</span><span class="meetings__next">Next: Sat, Oct 10</span></li>
    <li><span>Third Thursday training meeting, evenings</span><span class="meetings__next">Next: Thu, Oct 15</span></li>
  </ul>
</div>
```
The place line is `{name}, {street}, {city}` from `spk_site.place`. When the date that would have been next was cancelled: "Next: Sat, Nov 14 (Oct 10 cancelled)"; when it was moved: "Next: Sat, Oct 17 (moved from Oct 10)".

`spokares/meetings` › `next`
```html
<div {w}>
  [<p class="m-line hub-meeting-change">Sat, Oct 10: Second Saturday Workshop cancelled.[ {note}]</p>]   <!-- or "… moved to Sat, Oct 17." ; one per Home-meeting change in the next 14 days -->
  <p class="m-line hub-meeting"><b>Next meeting:</b> Sat, Oct 10, 9:00 AM, Second Saturday Workshop</p>
</div>
```

`spokares/docs` › `search`
```html
<div {w}><form class="searchbar hub-search" id="search" role="search" action="/members/documents/" method="get">
  <label class="hub-search__label" for="hub-q">Search documents and forms</label>
  <div class="searchbar__field">{icon search}<input id="hub-q" type="search" name="q" maxlength="100" placeholder="ICS-213, net script, task book" autocomplete="off"></div>
  <button class="btn btn--ink" type="submit">Search</button>
</form></div>
```

`spokares/docs` › `library`
```html
<div {w}><div class="lib" data-doc-library>
  <div class="lib__rail">
    <form class="searchbar lib__search" id="search" role="search" action="/members/documents/" method="get">
      <label class="lib__label" for="doc-q">Search the library</label>
      <div class="searchbar__field">{icon search}<input id="doc-q" type="search" name="q" maxlength="100" value="{esc_attr(q)}" placeholder="preamble, 213, task book" autocomplete="off"></div>
      [<input type="hidden" name="section" value="{esc_attr(section)}">]
      <button class="btn btn--ink" type="submit">Search</button>
    </form>
    <nav class="lib__cats" aria-label="Document categories"><ul class="chips">
      <li><a class="chip" href="/members/documents/"[ aria-current="true"]>All</a></li>
      <li><a class="chip" href="/members/documents/?section=most-used" data-section="most-used">Most used</a></li>
      <li><a class="chip" href="/members/documents/?section=net-ops" data-section="net-ops">Net operations</a></li> …7 sections
    </ul></nav>
  </div>
  <div class="lib__main">
    <p class="doc-count" aria-live="polite">Showing all 37 documents.</p>   <!-- or "Showing 4 of 37 documents." -->
    <div class="card doc-empty"[ hidden]><p>Nothing matches “<span data-doc-empty-q>{esc_html(q)}</span>”.</p><a class="btn btn--ghost btn--sm" href="/members/documents/" data-doc-clear>Clear search</a></div>
    <section class="doc-group" id="net-ops" data-section="net-ops"[ hidden]>
      <div class="doc-group__head"><h2>Net operations</h2></div>
      <table class="table doc-table">
        <caption class="vh">Net operations</caption>
        <thead class="vh"><tr><th scope="col">Document</th><th scope="col">Format</th><th scope="col">Source</th></tr></thead>
        <tbody>
          <tr id="{slug}" data-doc="{slug}" data-search="{lowercase haystack}"[ data-most-used][ hidden]>
            <td>{title cell}[<span class="doc-note">{note, or the how-to link}</span>]</td>
            <td data-label="Format">[<span class="fmt">PDF</span>][ <span class="doc-ver"><span class="nowrap">v3</span></span>]</td>
            <td data-label="Source">{source label, or <span class="tag tag--line">Soon</span>}</td>
          </tr>
        </tbody>
      </table>
    </section> …7 sections
  </div>
</div></div>
```
Title cell: `upload` → `<a class="doc-title" href="/docs/{slug}/">{title}</a>`; `link` → `spokares_link( url, title, 'doc-title', source_label )`; `soon` → `<span class="doc-title">{title}</span>`; sub-links (FEMA courses) → `<span class="doc-title">FEMA courses:</span> <span class="doc-links"><a id="is-100" class="nowrap" href="…" aria-label="IS-100.c, Introduction to the Incident Command System (opens FEMA)">IS-100.c</a>, …</span>`. Only published, privacy-checked documents are listed. The server applies `?q=` and `?section=` by marking non-matching rows and groups `hidden` and printing the right count; the page itself stays at `/members/documents/`.

`spokares/docs` › `tiles` (empty: nothing publicly; the editor placeholder in the preview)
```html
<div {w}><ul class="quick"><li><a class="tile" href="/docs/net-scripts/">{icon script}<span class="tile__label">Net script</span></a></li>…</ul></div>
```

`spokares/toc`
```html
<div {w}>
  <nav class="toc toc--side" aria-labelledby="toc-title"><p class="toc__title" id="toc-title">On this page</p><ol><li><a href="#ares-acs">ARES and ACS</a></li><li><a href="#legal-basis">Legal basis</a></li>…</ol><span class="toc__rail" aria-hidden="true"></span></nav>
  <details class="toc-mobile"><summary>On this page</summary><nav class="toc" aria-label="On this page"><ol>…same…</ol></nav></details>
  <button class="btn btn--ink contents-fab" type="button" hidden>{icon menu}On this page</button>
</div>
```
Walk `parse_blocks()` of the current post recursively; collect `core/heading` level 2 (default level) with an `anchor` attribute (or an `id=` in its HTML), in order.

`spokares/last-reviewed` (prints nothing publicly when `_spk_reviewed` is empty):
`<div {w}><p class="chapter-meta">Last reviewed <time datetime="2026-09-26">Sep 26, 2026</time>. Page owner: {_spk_owner}.</p></div>`

`spokares/asof`: `<div {w}><p class="page-head__meta">Schedule as of Sat, Sep 26, 2026.</p></div>`

**View modules** (plain ES modules in `spokares-core/assets/js/`, registered with `wp_register_script_module()` under the IDs in §6.2):
- `@spokares/copy`: un-hides `button[data-copy-text]`; on click copies with the Clipboard API (textarea fallback) and shows one global toast `<div class="toast is-on" role="status" aria-live="polite">Copied: {text}</div>` (port of `site.js` §9 and the toast helper). Failure text: "Copy did not work here. The settings are: {text}". Text is set with `textContent`.
- `@spokares/doc-library`: live filtering of rows by every word of the input against `data-search`; chips set `aria-current="true"` and filter by `data-section` (`most-used` uses `data-most-used`); updates `?q=`/`?section=` with `history.replaceState`; a `#<section-slug>` hash on load pre-selects that chip; count and empty state update; Clear resets. The query is written into the page with `textContent`, never `innerHTML`. Without JS the GET form and chip links do the same on the server.
- `@spokares/toc`: IntersectionObserver scroll-spy sets `aria-current="true"` on the active TOC link and sizes `.toc__rail`; shows `.contents-fab` on phones (<1024px) after the reader scrolls past the closed `details.toc-mobile`, and the button scrolls back to it and opens it.

### 6.5 Plugin: admin screens, governance and routes

- **Screens** (§3.4) and their capabilities: `spokares-rota` `spokares_edit_rota`; `spokares-net-details` `spokares_edit_net_details`; `spokares-meetings` `spokares_edit_rota`; `spokares-meeting-rules` `spokares_edit_net_details`; `spokares-tiles` `edit_spk_documents`; `spokares-site` `manage_options`. Each form posts to `admin-post.php` with its own action (`spokares_save_rota`, `spokares_undo_rota`, `spokares_save_net_details`, `spokares_save_meetings`, `spokares_save_meeting_rules`, `spokares_save_tiles`, `spokares_save_site`) and nonce of the same name, then redirects back with a notice (post/redirect/get). Event and document meta boxes save on `save_post_spk_event` / `save_post_spk_document` with nonces `spokares_event_meta` / `spokares_document_meta`; the Page review box with `spokares_page_review`. Row actions: `spokares_duplicate_event`, `spokares_pull_file` (GET, `wp_nonce_url`, capability checked per post).
- **Save semantics:** §4.4 (Draft or held-back field, retained input, confirm ticks); rota and meetings save changed rows only (§3.4).
- **Documents upload handler:** runs inside `save_post_spk_document` only when `spk_privacy_ok` is ticked in the same request; widens the upload allowlist for that call only; applies the random suffix; calls `media_handle_upload( 'spk_upload', $post_id )`; on Replace, deletes the old attachment per §5.5.
- **Editor governance** (pages; users without `edit_theme_options`): the `block_editor_settings_all` values in §4.3 layer 3; `allowed_block_types_all` = exactly the block types in §6.9's list; the skeleton check (§4.3 layer 2); enqueue `spokares-editor-guard` (editing modes, never-publish/phone/e-mail warnings on save using `spokares_check_text()` patterns passed as JSON, Welcome Guide and starter-pattern window off); `map_meta_cap` for the three members pages (§4.1); the Page review box.
- **Admin trimming:** §3.4 menu, §4.2 admin bar, profile fields, dashboard widgets, comments off, Quick Edit and bulk Edit removed on both post types, the Save box replacing `submitdiv` on both post types, `upload.php` redirect.
- **`/docs/<slug>/`:** rewrite rule `^docs/([a-z0-9-]+)/?$` → `spk_doc`; on `template_redirect`: published + privacy-checked + (upload with file, or link) → `wp_redirect( $url, 302 )` to the stored URL, which is re-checked to start with `https://` (it never comes from the request); else a 404 through the theme's `404` template.
- **Activation:** create/refresh the role and caps; add default options only if absent; register the post types and taxonomy directly, then flush rewrite rules (activation runs after `init`).

### 6.6 Must-use plugin (`wordpress/mu-plugins/`)

- `spokares-hardening.php`: header (Plugin Name "Spokane ARES hardening", Version = tag), `defined( 'ABSPATH' ) || exit;`, then `require` each file in `spokares-hardening/` in a fixed order: `hardening.php`, `enumeration.php`, `content-surface.php`, `headers.php`, `uploads.php`, `two-factor.php`, `smtp.php`, `redirects.php` (+ the generated `redirect-map.php`). Behaviour: §5.3-§5.6.
- It must work with the theme and `spokares-core` absent or inactive, and never fatal if Two-Factor is missing (fail closed as in §5.3).
- It exposes one filter the plugin uses: `spokares_hard_upload_mimes` (the per-request allowlist), and one function: `spokares_hard_inspect_office_file( string $path ): true|WP_Error`.
- `wordpress/tools/redirects/`: `gen-redirect-map.mjs`, `repoint.csv` (§5.6). Never deployed.

### 6.7 Theme: tokens, CSS, templates, parts, blocks, filters

**`theme.json` (v3)**
- `settings.color.palette` (slug → hex): `night` #0E1A33, `slate-2` #1F2C47, `slate` #33415C, `peach` #F3C29B, `day` #FFFFFF, `paper` #F6F7FA, `mist` #C5CEDF, `mist-2` #A9B4C9, `ink` #0E1A33, `ink-2` #3C4863, `off` #56627C, `rule` #D9DEE8, `field` #7D889E, `red` #D0202A, `red-hover` #B51C25, `red-ink` #B01A23, `amber` #FFC857, `amber-wash` #FFF4D6, `amber-ink` #6B4A00. `custom`, `customGradient`, `customDuotone`, `defaultPalette`, `defaultGradients`, `defaultDuotone`, **`background`, `text`, `link`, `heading`, `button`, `caption`** all `false`. Gradients (theme only): `dawn-sky`, `dawn`, `storm` with B's stops.
- `settings.typography`: `fontFamilies` `serif` ("Crimson Pro", Georgia, "Times New Roman", serif; roman and italic, `font-weight` "200 900") and `sans` ("Schibsted Grotesk", system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; "400 900"), each with `fontFace` `src` `file:./assets/fonts/<file>.woff2` and `font-display: swap`. `fontSizes` with `fluid: false` and B's exact values: `display` clamp(2.75rem, 1rem + 5.4vw, 5.5rem) · `h1` clamp(2.5rem, 1.35rem + 3.2vw, 4.25rem) · `h2` clamp(2rem, 1.2rem + 2.4vw, 3.25rem) · `h3` clamp(1.45rem, 1.2rem + .7vw, 1.875rem) · `narr` clamp(1.35rem, 1.1rem + .55vw, 1.75rem) · `lede` clamp(1.2rem, 1.05rem + .45vw, 1.5rem) · `prose` 1.25rem · `body` 1.0625rem · `small` .875rem. `customFontSize`, `defaultFontSizes`, `fontStyle`, `fontWeight`, `letterSpacing`, `lineHeight`, `textDecoration`, `textTransform`, `writingMode`, `dropCap`, **`textAlign`, `textColumns`, `textIndent`** `false`.
- `settings.spacing`: `spacingSizes` 10 → 8px, 20 → 16px, 30 → 24px, 40 → 40px, 50 → 64px, 60 → 96px, 70 → 128px; `customSpacingSize` false; `defaultSpacingSizes` false; `blockGap` **null**; `padding`, `margin` false; `units` ["px","rem","%","vw","vh"].
- `settings.layout`: `contentSize` 760px, `wideSize` 1240px, **`allowEditing` false, `allowCustomContentAndWideSize` false**. `useRootPaddingAwareAlignments` false; root padding 0 (B's `.wrap` does the gutters).
- **`settings.dimensions`**: every key the 7.1 schema lists (`aspectRatio`, `defaultAspectRatios`, `height`, `minHeight`, `width`) `false`. **`settings.position.sticky`** `false` (the sticky header is CSS).
- `settings.appearanceTools` false; `border` (`color`, `radius`, `style`, `width`) false; `shadow.defaultPresets` false; `background` (`backgroundImage`, `backgroundSize`) false; `lightbox.allowEditing` false; **`blockVisibility.allowEditing` false** (7.1 key).
- `settings.custom`: `radius` { panel 16px, card 12px, paper 4px, pill 999px, sm 8px }, `mast-h` 72px.
- `styles`: body background `paper`, text `ink`, font `sans` at `body` size, line-height 1.6. Everything else is in `site.css`.
- `templateParts`: `header` (area header), `footer` (area footer), `members-nav` (area uncategorized), `navigation-overlay` (area navigation-overlay, title "Menu"). `customTemplates` (QA-031, QA-102): readable names for the templates WordPress picks by slug: "For members: This week (hub)", "For members: Exercises & events", "For members: Documents & forms" (`postTypes` [page], offered to administrators only; `governance.php` removes them for editors), and "How it works page" and "About ARES & ACS page" (`postTypes` [], so no one is offered them). `inc/setup.php` adds a description to each through the `get_block_template(s)` filters, since theme.json has no description field.

**`assets/css/site.css`** (handle `spokares-site`, enqueued on the front end and with `add_editor_style()`):
- Port B's `site.css` section by section, **plus** the page-scoped `<style>` blocks from `how-it-works.html`, `about.html`, `members/index.html`, `members/documents.html`, `members/exercises.html` (one copy of the shared members block).
- Tokens: `:root { --night: var(--wp--preset--color--night); … --serif: var(--wp--preset--font-family--serif); --fs-h1: var(--wp--preset--font-size--h1); … }` so theme.json is the single source; keep B's variable names so the port is mechanical.
- Retarget selectors to block markup using the §6.8 vocabulary: e.g. `.btn` rules also apply to `.wp-block-button__link`; `.btn--ghost` means `.wp-block-button.btn--ghost > .wp-block-button__link` for core buttons and `a.btn.btn--ghost` for plugin output; `.stops > li > strong:first-child` is the stop label; `.steps > .steps__item::before` draws the counter; `.table` rules apply to `.wp-block-table.table > table` and to plugin `table.table`.
- **Lists without the bold lead-in still look right** (`stops`, `lines`, `years`, `hops`): an item with no leading `<strong>` renders as plain text in the label's position, with no empty label box or broken marker.
- Drop: tap-to-define (§14), story/scrollytelling (§24), round-2 leftovers not used in round 3 (`.timeline`, `.posts`, `.numerals`, `.compare`, `.faq`, `.glossary`, `.sidenote`, `.dropcap`, `.paper` form, `.status-key`, `.search-preview`, the utility bar), and `html.js`-dependent rules (JS-only UI uses the `hidden` attribute instead).
- Add: `.searchbar`/`.searchbar__field` (renamed from `.search`); members pages centre content through their outer `.members-page.wrap` group (this replaces B's `.workspace > *` rule); library chips are links, so style `.lib__cats .chip[aria-current="true"]` like B's `aria-pressed` state; `.spk-edit-hint` (editor preview; small, amber-wash); `.spk-edit-link` (front end, logged-in only; small, quiet, never competing with B's buttons); `.hub-meeting-change` (amber-wash line); `.anchor-alias` (`display:block; height:0; overflow:hidden`); `.spk-verify .needs-verify { outline: 2px dashed var(--amber-ink); outline-offset: 2px }`; the menu overlay (full-screen night at P0; B's drop-down at P1); the ≥1020px inline nav override; `.wp-block-spokares-docs.is-view-library + .lib__not-here` offset to the library's right column at ≥1024px; no CSS `order` on the hub (`.hub-net` is first in the markup, QA-104) or the header (QA-103); members `.m-head` hairline (red stays on `.doc-group__head`); contrast fix (§2.3).
- **Admin bar offset:** `--spk-bar: var(--wp-admin--admin-bar--height, 0px)`; `.site-header { position: sticky; top: var(--spk-bar) }`, `html { scroll-padding-top: calc(var(--mast-h) + var(--spk-bar) + 16px) }` and `[id] { scroll-margin-top: 8px }`, so an anchor still lands 24px under the masthead and a link reached with Tab or Shift+Tab, or the About "On this page" target, is never under the sticky header (QA-029, WCAG 2.4.11). **At 600px and below** (where the admin bar scrolls away) the admin-bar term is 0, so logged-in phones have no 46px gap. Pages with the contents button also get `scroll-padding-bottom` below 1024px (QA-105).
- Neutralise WordPress defaults that fight B: `.wp-block-cover` min-height/padding for `.hero`, `.wp-block-buttons` gap, `.wp-block-table` figure margins, list padding for classed lists, `figcaption` for `.has-vh-caption`.

**Templates** (all `<main>` elements hold the skip-link target):
```html
<!-- templates/front-page.html, page-how-it-works.html and page-about.html (the full-bleed shell; the page
     content holds its own bands and its own H1). page-sections puts a bare root block in the column (QA-100). -->
<!-- wp:template-part {"slug":"header","tagName":"header","className":"site-header"} /-->
<!-- wp:group {"tagName":"main","anchor":"main","layout":{"type":"default"}} -->
<main id="main" class="wp-block-group"><!-- wp:post-content {"layout":{"type":"default"},"className":"page-sections"} /--></main>
<!-- /wp:group -->
<!-- wp:template-part {"slug":"footer","tagName":"footer","className":"site-footer"} /-->

<!-- templates/page-members.html; page-documents.html and page-exercises.html are identical except the pattern slug
     (spokares/members-documents, spokares/members-exercises). No post-content block: these pages have no content. -->
<!-- wp:template-part {"slug":"header","tagName":"header","className":"site-header"} /-->
<!-- wp:group {"className":"members-shell","layout":{"type":"default"}} -->
<div class="wp-block-group members-shell">
  <!-- wp:template-part {"slug":"members-nav"} /-->
  <!-- wp:group {"tagName":"main","anchor":"main","className":"workspace","layout":{"type":"default"}} -->
  <main id="main" class="wp-block-group workspace"><!-- wp:pattern {"slug":"spokares/members-hub"} /--></main>
  <!-- /wp:group -->
</div>
<!-- /wp:group -->
<!-- wp:template-part {"slug":"footer","tagName":"footer","className":"site-footer"} /-->
```
- WordPress picks `page-members`, `page-documents`, `page-exercises`, `page-how-it-works` and `page-about` by page slug; the non-admin slug guard (§4.1) keeps them matched. No `page_template_hierarchy` filter; the Template picker offers editors nothing.
- `page.html` (QA-031) is the plain page any other page gets, such as a Privacy Policy an administrator adds: header, then `main.bg-day.plain-page` > Group `wrap` > Post Title (H1, `plain-page__title`) + Post Content (`plain-page__body prose`), then footer. Pages open template-locked, so the title is edited in the canvas.
- `index.html`: header, main with post-title (h1) + post-content, footer. `search.html` (QA-098): header, main with a Query Title H1 ("Search results for: …"), a Search block filled with the query, a Query Loop (inherit) of titles and excerpts with a no-results line that links the four pages, and a line pointing to the document library search and Exercises & events; footer. `404.html`: header, a `bg-day` section with H1 "Page not found" and one line "Try Home, How it works, About ARES & ACS or For members." (each a link), footer. These three templates hold the only new visible words in the theme.

**Parts**
- `header.html`: Group `masthead` > Group `wrap masthead__bar` > `spokares-theme/brand {"variant":"header"}` + Buttons (`className` "masthead__actions") > Button (`className` "btn--red masthead__join", text "Join the team", url `/#join`) + core Navigation (`className` "nav", `overlayMenu` "always", `hasIcon` false, `ariaLabel` "Primary", overlay colours night/white; at P1 also `"overlay":"spokares//navigation-overlay"`) with inline links Home `/`, How it works `/how-it-works/`, About ARES & ACS `/about/`, For Members `/members/` (`className` "is-section"), then, inside the Navigation, Buttons (`className` "site-header__actions") > Button (`className` "btn--red site-header__join", text "Join the team", url `/#join`). **Two Join buttons (QA-103, owner decision 2026-09-27)** so the markup order is the drawn order at every width, with no CSS `order`, reversed direction, grid placement or `reading-flow`: under 1020px the bar reads brand, the bar's Join, Menu, and the open menu ends with its own Join after the four links; from 1020px the bar's Join isn't drawn and the bar reads brand, the four links, the Navigation's Join (the same look as before). Change both buttons together.
- `navigation-overlay.html` (P1): Group `nav-panel bg-night` > Navigation Overlay Close block + core Navigation (`overlayMenu` "never", vertical, `ariaLabel` "Primary") with the same four links. CSS shows the open overlay as B's drop-down panel under the 72px bar (not full-screen), night surface, B's link sizes. Accept it only if Escape closes it, focus returns to the Menu button, Tab stays inside while open, and it matches B's mobile shots; otherwise keep the P0 full-screen overlay and record why. If the overlay replaces the Navigation's inline links, the ≥1020px CSS shows the part's list inline instead; builder's choice, documented.
- `members-nav.html`: core Navigation (`className` "member-nav", `overlayMenu` "never", `ariaLabel` "Members") with This week `/members/`, Exercises & events `/members/exercises/`, Documents & forms `/members/documents/`, Task books `/about/#training-path`, groups.io `https://spokaneares-acs.groups.io/g/main`.
- `footer.html`: Group `wrap` > Group `footer__bar` > brand (footer) + core Navigation (`className` "footer__links", `overlayMenu` "never", `ariaLabel` "Footer": How it works, About ARES & ACS, For Members, groups.io, Contact `/about/#contact`) + Paragraph `footer__privacy` "Privacy (coming)"; Group `footer__bottom` > Paragraph `footer__emergency` "Volunteers, not first responders. In an emergency, call 911." + Paragraph `footer__mock` "© Spokane County ARES-ACS. Mockup — content to be verified before launch".

**Theme blocks** (PHP-only, same `block.json` shape, registered from `functions.php`):
- `spokares-theme/brand`, attribute `variant` ("header" | "footer"). Header: `<a class="brand" href="/"><img src="{theme}/assets/img/seal-ares-acs-400.png" alt="" width="46" height="46"><span class="brand__name"><span>Spokane County</span> <strong>ARES-ACS</strong></span></a>`. Footer: `<a class="footer__seal" href="/"><img src="…" alt="Spokane County ARES-ACS home" width="44" height="44" loading="lazy"></a>`.
- `spokares-theme/message-path`, no attributes: `<figure class="msgpath-panel how-open__fig">` + the inline `<svg class="msgpath msgpath--to-eoc" data-state="relay" viewBox="0 0 560 400" role="img" aria-labelledby="mp-t mp-d">` with `<title id="mp-t">A scenario: one message, four hops</title>`, B's `<desc>`, the `<defs>` and the scene inlined (no `<use>` of a body sprite), and the extra EOC hop-4 marker from `how-it-works.html`. **Remove the `147.300 MHz` sub-label** under "W7GBU repeater". Keep the in-picture "A scenario" pill.

**Filters and helpers in `functions.php` / `inc/`:**
1. `render_block_core/table`: for tables whose class contains `table--stack`, add `data-label` to each body `td` from the header row (verified approach in the probe).
2. `render_block_core/group`: class token `steps` or `levels` → `role="list"`; `steps__item` or `level` → `role="listitem"`.
3. External links in core block output (`core/paragraph`, `core/list-item`, `core/heading`, `core/table`, `core/button`, `core/navigation-link`, `core/quote`): add class `ext` and append `<span class="vh"> (opens {host without www})</span>` to any `<a>` whose host is not the site's and which lacks `ext`. `mailto:` links are not external.
4. `render_block_core/navigation-link`: set `aria-current="page"` when the link path equals the request path; for a link with class `is-section`, also when the request path starts with it. Links with a `#` fragment are never current.
5. `document_title_separator` → `|`; add page `excerpt` support and print `<meta name="description">` from the excerpt when set.
6. Body class `spk-verify` for `?verify=1` + `edit_pages`.
7. Preload the two roman woff2 files; favicon fallback (`<link rel="icon">` to the seal) when no Site Icon is set.
8. `remove_theme_support( 'core-block-patterns' )`; register the three pattern categories.
9. **No-break spaces:** on `core/paragraph`, `core/heading`, `core/list-item`, `core/table`, `core/button` and `core/quote` output, replace the space between a digit and `AM`, `PM`, `MHz`, `kHz` or `Hz` with U+00A0, in text only (not inside tags).

**Fonts:** `assets/fonts/crimson-pro-roman.woff2`, `crimson-pro-italic.woff2`, `schibsted-grotesk-roman.woff2` (variable, Latin + Latin Extended), plus `OFL-crimson-pro.txt` and `OFL-schibsted-grotesk.txt`. Source: Google Fonts (OFL). Schibsted's italic is not used.

**Images:** copy `seal-ares-acs-400.png` and `1920x1200sunset.jpg` from B's `assets/img/` to `assets/img/`. The sunset file is the pattern preview and the source the page setup copies into the Media Library (§6.10). No other images are used in round 3.

**Theme JS:** none expected. Add `assets/js/theme.js` (a module) only if the cold-load deep-link test fails without it.

### 6.8 Class vocabulary for pattern markup

Patterns put B's class names on core blocks through `className` (the "Additional CSS class" field). The theme styles exactly these. Surfaces are classes, not section styles.

| Class (on block) | Meaning |
|---|---|
| `bg-night`, `bg-storm`, `bg-slate`, `bg-dawn`, `bg-day`, `bg-paper` (Group, section) | Surface: background + tone variables (B §2) |
| `wrap` (Group) | Centred site width, min(1240px, 100% − 64px); 16px gutters under 640px |
| `dawn-sky`, `dawn-sky how-dawn`, `dawn-sky about-sky` (Spacer, height 1px in markup) | The night-to-peach hinge; height from CSS |
| `anchor-alias` (Spacer with an `anchor`, height 0px) | A retired id kept as an alias at the top of a section |
| `redline` (Separator) | The red message line with amber node and ring |
| `hero` (Cover); `hero__body` (Group); `display hero__title` (Heading h1); `hero__lede`, `hero__agency` (Paragraph); `hero__actions` (Buttons) | Home hero |
| `btn--red`, `btn--ghost`, `btn--light`, `btn--ink`, `btn--lg`, `btn--sm` (Button) | Button look; a Button with no `btn--*` class is red |
| `has-textlink` (Paragraph) | Links inside are B's `.textlink` |
| `home-h2` (Heading); `home-do`, `home-visit`, `home-join` (section Groups); `home-visit__grid`, `home-visit__person`, `home-join__grid` (Groups); `card card--dawn home-visit__air` (on `spokares/net` view `from-home`) | Home layout |
| `stops` (List) | What we do: item = `<strong>Label</strong> text` |
| `steps` (Group) > `steps__item` / `steps__item steps__item--opt` (Group) > `steps__title` (Heading h3), `steps__text`, `steps__action`, `steps__note` (Paragraph), `tag tag--line` (Paragraph) | Join steps, counter from 0 |
| `card`, `card--dawn`, `card--red` (Group) | Cards |
| `how-open`, `how-ch`, `how-ch--first` (section Groups); `how-open__grid`, `how-open__text`, `how-open__side`, `how-row`, `how-row__head`, `how-row__body`, `how-close` (Groups); `how-open__line`, `how-open__note`, `how-open__jump`, `how-open__after`, `how-lede`, `how-real`, `how-label` (Paragraph) | How it works layout |
| `hops` (ordered List) | Opener hops: item = `<strong>Lead.</strong> text`, amber counter |
| `seq` (ordered List); `seq__cue` (nested List inside an item) | Run-sheet / activation / join paths: amber counter on a quiet rule; the cue box |
| `lines` (List) | Hairline one-liners: item = `<strong>Lead:</strong> text` (also printed by `spokares/net` view `other-nets`) |
| `levels` (Group) > `level` (Group) > `level__name`, `level__color`, `level__means`, `level__do` (Paragraph) | Readiness levels |
| `deflist deflist--2` (Group) > `deflist__item` (Group) > `deflist__term`, `deflist__def` (Paragraph) | Training rhythm (2-up) |
| `defs` (Group) > `def` (Group) > `def__term`, `def__expansion`, `def__text` (Paragraph) | About ARES/ACS definitions |
| `callout callout--plain`, `callout callout--legal` (Group); `callout__title` (Paragraph) | Callouts |
| `table`, `table--stack`, `table--dense`, `has-vh-caption`, plus a page hook (`how-roles`, `legal-table`, `roles`, `tb`, `exams`, `contacts`), `bleed` (Table) | Tables; caption visually hidden |
| `pullquote` (Quote) | Crimson italic with the red rule |
| `years` (ordered List) | Timeline: item = `<strong>1999</strong> text` |
| `chains` (Group) > `chains__cols` (Group) > `chain` (ordered List ×2); `chains__team` (Group) > Paragraph; `chains__caption` (Paragraph) | Structure figure |
| `about-head`, `about-body`, `longread`, `longread__aside`, `longread__main prose`, `chapter` (Groups); `lede`, `note`, `linkrow` (Paragraph) | About layout |
| `members-page` (Group, outermost in each members body); `page-head`, `hub-head`, `hub-grid`, `hub-week`, `hub-net`, `hub-most`, `ex-next`, `ex-grid`, `ex-section`, `lib__not-here` (Groups); `m-head` (Heading h2); `m-label` (Heading h3); `m-line`, `hub-bring`, `ex-bring`, `ex-after`, `ex-tips` (Paragraph) | Members layout |
| `needs-verify` (any block) | Was `data-verify` in B; outlined only in `spk-verify` mode |

Text conventions inside blocks: bold lead-ins use `<strong>`; line breaks inside one paragraph use `<br>`; no-break spaces before AM/PM/MHz are added by theme filter 9 (patterns may also contain them); `<abbr title>` is kept where B has it in page text.

### 6.9 Pattern catalogue

**File header** (every file in `patterns/`):
```php
<?php
/**
 * Title: Home: hero
 * Slug: spokares/home-hero
 * Categories: spokares-sections
 * Inserter: no
 * Viewport Width: 1440
 */
defined( 'ABSPATH' ) || exit;
?>
<!-- block markup -->
```
- **Every pattern is `Inserter: no`** (sections, pages and members bodies): nobody inserts theme patterns from the inserter. Administrators who restructure Home, How it works or About paste a section file's markup in the code editor (or use P2 `page-sync`).
- **Sections:** category `spokares-sections`.
- **Pages** (`page-home`, `page-how-it-works`, `page-about`): category `spokares-pages`, `Post Types: page`, **no `Block Types` header** (so they never appear in the new-page starter window). They build their content by including the section files (`<?php include __DIR__ . '/home-hero.php'; ?>`), so each section's markup exists once and the page pattern is fully expanded block markup (never `<!-- wp:pattern -->` references). Page setup reads them from the registry, which `Inserter: no` doesn't affect.
- **Members bodies** (`members-hub`, `members-documents`, `members-exercises`): category `spokares-members`; referenced by the theme's members templates with `<!-- wp:pattern {"slug":"spokares/members-hub"} /-->`. Not copied into pages; no locks needed.

**Markup rules:**
- **Block types allowed in patterns** (the plugin allows exactly these for editors): `core/paragraph`, `core/heading`, `core/list`, `core/list-item`, `core/buttons`, `core/button`, `core/group`, `core/cover`, `core/spacer`, `core/separator`, `core/table`, `core/quote`, `core/image`, every `spokares/*` block and `spokares-theme/message-path`. Nothing else (no Columns, Details, HTML).
- Valid, serialised block markup exactly as the block editor would save it (class order, `wp-block-*` classes, attribute JSON). Verify by opening each page in the editor as admin: no "This block contains unexpected or invalid content" notices.
- **Locks** (page and section patterns): every top-level block of a page (section Group, Cover, Spacer) and every dynamic or theme block carries `"lock":{"move":true,"remove":true}`. No `templateLock` on Groups; no `metadata.patternName`; no `wp:pattern` blocks.
- **Anchors:** use the block `anchor` attribute (it prints `id`) for every id in placement map §2.4, plus `open-slot` and `weekly-net` (printed by the plugin).
- **Images:** the hero Cover uses `<?php echo esc_url( get_theme_file_uri( 'assets/img/1920x1200sunset.jpg' ) ); ?>` for the inserter preview only, `alt=""`, `dimRatio` 0, focal point `{"x":0.5,"y":0.8}`. Page setup swaps in the Media Library copy (§6.10).
- **Dynamic blocks:** self-closing with an explicit `view`, e.g. `<!-- wp:spokares/net {"view":"settings","lock":{"move":true,"remove":true}} /-->`.
- `needs-verify` goes on the smallest block that held a `data-verify` element in B (plugin output handles its own).

**Tree notation below:** `group(section "classes" #anchor)` = core/group with `tagName` section; `L` = locked. Words: from the named B page, verbatim.

**Home** (`index.html`): `spokares/page-home` = hero + what-we-do + dawn + visit + join.
```
spokares/home-hero       L cover("hero", image sunset, align full)
                           group("wrap hero__body")
                             heading h1 ("display hero__title")  "When other systems go down, Spokane County still has a voice."
                             paragraph ("hero__lede")
                             buttons ("hero__actions") > button("btn--red btn--lg" → /#join) "Join the team"
                                                        > button("btn--ghost btn--lg" → /how-it-works/) "See how it works"
                             paragraph ("hero__agency")  "Represent an agency? <a href=/about/#for-agencies>How we work with agencies</a>"
spokares/home-what-we-do L group(section "bg-night home-do" #what-we-do)
                           group("wrap") > heading h2 ("home-h2") "What we do"
                                         > list("stops") ×4  "<strong>County emergencies</strong> The radio room and communications trailer, when the county activates us." …
spokares/dawn-hinge      L spacer("dawn-sky")                      (Home only; How it works and About carry their own spacer)
spokares/home-visit      L group(section "bg-dawn home-visit" #visit)
                           spacer("anchor-alias" #this-week)
                           group("wrap") > heading h2 ("home-h2") "Come visit"
                             group("home-visit__grid")
                               L spokares/net {"view":"from-home","className":"card card--dawn home-visit__air"}
                               group("home-visit__person") > L spokares/meetings {"view":"home"}
spokares/home-join       L group(section "bg-day home-join" #join)
                           spacer("anchor-alias" #what-it-takes)
                           group("wrap home-join__grid") > heading h2 ("home-h2") "Join in four steps"
                             group("steps")
                               group("steps__item") > heading h3 ("steps__title") "Get licensed" > p("steps__text") > p("steps__action has-textlink") link /about/#licensing
                               group("steps__item") > h3 "Join <abbr title="Amateur Radio Emergency Service">ARES</abbr>" > p "One form." > p action /about/#ares-path "How to apply"
                               group("steps__item") > h3 "Get connected" > p("steps__text") with links /how-it-works/#nets and groups.io
                               group("steps__item steps__item--opt") > h3 "Qualify for <abbr title="Auxiliary Communications">ACS</abbr>" > p("tag tag--line") "Optional"
                                   > p("steps__text") > p("steps__action has-textlink") /about/#acs-path "The ACS path" > p("steps__note") "<strong>What ACS asks:</strong> …"
```

**How it works** (`how-it-works.html`): `spokares/page-how-it-works` = opener + dawn + nets + activation + messages + exercises + roles.
```
spokares/how-opener     L group(section "bg-storm how-open" #follow-a-message)
                          group("wrap how-open__grid")
                            group("how-open__text")
                              heading h1 "How it works"
                              paragraph("how-open__jump has-textlink") "<a href=#weekly-net>Radio settings</a>"      ← REVIEW fix, the only new words
                              paragraph("how-open__line") "It’s 2 AM.<br>The power’s out, and so is cell service."
                              paragraph("how-open__note needs-verify") "A scenario, not a real event. It shows how the system is meant to work."
                              list ordered ("hops needs-verify") ×4 "<strong>The phones go quiet.</strong> Ice brings …"
                            group("how-open__side") > L spokares-theme/message-path > paragraph("how-open__after") "Radio still works. Because people practice."
                        L spacer("dawn-sky how-dawn")
spokares/how-nets       L group(section "bg-dawn how-ch how-ch--first" #nets)
                          group("wrap")
                            group("how-row") > group("how-row__head") > h2 "The weekly net" > p "A net is an on-air meeting. …"
                                             > group("how-row__body") > L spokares/net {"view":"settings"}
                                                 > group > p("how-label") "What happens, in order" > list ordered ("seq") ×6
                                                     (item 4: "Visitors check in." + nested list("seq__cue") ×1 "<strong>Visiting?</strong> Wait for the call for visitors. …")
                            group("how-row" #other-nets) > group("how-row__head") > h3 "Other nets"
                                             > group("how-row__body") > L spokares/net {"view":"other-nets"}
spokares/how-activation L group(section "bg-day how-ch" #activation) > group("wrap")
                            separator("redline")
                            group("how-row") > head: h2 "When the county calls"
                                             > body: p("how-lede has-textlink" #who-calls-us) · p("how-real needs-verify" #real-world) · list ordered ("seq") ×7 "<strong>Request.</strong> …"
                            group("how-row" #alert-levels) > head: h3 "Readiness levels" + p("needs-verify")
                                             > body: group("levels needs-verify") > 4 × group("level") > p("level__name") "Inactive" · p("level__color") "Green" · p("level__means") · p("level__do")
                            group("how-row" #standing-order-1b) > head: h3 "When no one has called yet" > body: p · p("has-textlink") external link "Read Standing Order 1b"
spokares/how-messages   L group(section "bg-day how-ch" #message-handling) > group("wrap")
                            separator("redline") · how-row(h2 "How a message moves" | p("how-lede"))
                            how-row #ics-213 (h3 "Forms and logs" | p · p · list("lines" #logs) ×2)
                            how-row #winlink (h3 "Winlink" | p)
spokares/how-exercises  L group(section "bg-day how-ch" #exercises) > group("wrap")
                            separator("redline") · how-row(h2 "Training and exercises" | group("deflist deflist--2") > 4 × group("deflist__item") > p("deflist__term") · p("deflist__def")
                                                      · p("has-textlink") "Dates: <a href=/members/exercises/>Exercises &amp; events</a>")
spokares/how-roles      L group(section "bg-day how-ch" #roles) > group("wrap")
                            separator("redline") · how-row(h2 "Roles members take" | table("table table--stack how-roles has-vh-caption") head + 8 rows, first column <th scope="row">, caption "Roles members take, and who each is open to"
                                                      · group("how-close") > p "Ready?" > buttons > button("btn--red btn--lg" → /#join) "Join the team")
```

**About** (`about.html`): `spokares/page-about` = head + body.
```
spokares/about-head     L spacer("dawn-sky about-sky")
                        L group(section "bg-dawn about-head") > group("wrap") > group("about-head__main")
                            h1 "About ARES &amp; ACS" · p("lede") "The details behind the team, for prospective members and partner agencies." · L spokares/last-reviewed
spokares/about-body     L group("about-body") > group("wrap longread")
                            group("longread__aside") > L spokares/toc {"labels":"legal-basis=Legal basis;leadership=Leadership;history=History"}
                            group("longread__main prose") > the 9 chapter patterns, in order
spokares/about-ares-acs       group(section "chapter") > spacer("anchor-alias" #what-is-ares) ×4 (#what-is-ares, #what-is-acs, #two-hats, #auxcomm)
                                > h2 #ares-acs "ARES and ACS" > group("defs") > 4 × group("def") > p("def__term") · [p("def__expansion")] · p("def__text")
                                > group("callout callout--plain" #what-we-are-not) > p("callout__title") "What we are not" > list ×4
spokares/about-legal-basis    group(section "chapter needs-verify") > h2 #legal-basis > table("table table--stack legal-table bleed has-vh-caption") > group("callout callout--legal") > p
spokares/about-who-we-serve   chapter > h2 #who-we-serve > list · h3 #agreements > list · h3 #for-agencies > p > buttons > button("btn--ghost needs-verify" → mailto:ec@spokares.org) "Email the Emergency Coordinator"
spokares/about-leadership     chapter > h2 #leadership "Organization and leadership"
                                > group("chains" #structure) > group("chains__cols") > list ordered ("chain") ×3 · list ordered ("chain") ×3 ("Spokane County Emergency Management<br>Director: the Sheriff")
                                                            > group("chains__team") > p "<strong>Emergency Coordinator + <abbr title="Assistant Emergency Coordinators">AECs</abbr></strong><br>One team"
                                                            > p("chains__caption needs-verify")
                                > table("table roles has-vh-caption needs-verify") 7 rows (PIO: "Open position. <a href=#contact>Interested? Tell us.</a>")
                                > table("table roles has-vh-caption needs-verify") 3 rows
spokares/about-joining        chapter > h2 #membership "Joining" > list ×2
                                > h3 #ares-path "ARES" > list ordered ("seq") ×1 > buttons > button("btn--red" → /members/documents/#ares-application) "Get the ARES application"
                                > list ordered ("seq", start 2) ×2 > p("note")
                                > h3 #acs-path "ACS (optional)" > list ordered ("seq") ×6
spokares/about-training       chapter > h2 #training-path > p > h3 #acs-task-book "ACS Position Task Book (2024-08-09)"
                                > table("table table--dense table--stack tb bleed has-vh-caption") 4 level rows (level cell "New Member" etc.; numerals by CSS counter)
                                > p("note") > p("linkrow") > h3 #arrl-task-book > p > p("linkrow") > h3 #equipment > list > p("linkrow")
spokares/about-licensing      chapter > h2 #licensing > p > h3 "Three ways to study" > list > h3 "Where to take the exam" > table("table table--stack exams bleed has-vh-caption needs-verify") > list
spokares/about-history        chapter > h2 #history "History and W7GBU" > h3 #w7gbu > p > quote("pullquote") > h3 "Timeline" > list ordered ("years") ×5 "<strong>1999</strong> …"
spokares/about-contact        chapter > h2 #contact > table("table contacts has-vh-caption needs-verify") 3 rows (mailto links) > p "<strong>In person:</strong> see <a href=/#visit>Come visit</a>."
```

**For members hub** (`members/index.html`): `spokares/members-hub` (inside `templates/page-members.html`)
```
group("members-page wrap")
    group("page-head hub-head") > group > h1 "For members" > spokares/asof
                                > spokares/docs {"view":"search"}
    group("hub-grid")           // QA-104: the Tuesday net first in the markup at every width, no CSS order;
                                //   phones: net above This week; desktop: net in the left 7fr column, This week in the right 5fr
      group(section "hub-net")  > h2("m-head" #rota) "Tuesday net" > spokares/net {"view":"bar"} > spokares/net {"view":"rota","weeks":5}
      group(section "hub-week") > h2("m-head" #this-week) "This week" > h3("m-label") "Exercises"
                                > spokares/events {"view":"upcoming","types":"exercise,training","limit":2,"days":60}
                                > p("hub-bring has-textlink") "<a href=/members/exercises/#next-up>What to bring</a>"
                                > spokares/meetings {"view":"next"}
    group(section "hub-most") > h2("m-head" #quick-links) "Most used" > spokares/docs {"view":"tiles"}
```

**Documents & forms** (`members/documents.html`): `spokares/members-documents` (inside `templates/page-documents.html`)
```
group("members-page wrap")
    group("page-head") > h1 "Documents &amp; forms"
    spokares/docs {"view":"library"}
    group(section "lib__not-here") > h2 #not-here "Not posted here" > p "County, hospital, SHARES and 800 MHz details are never posted here. Older workshop presentations are being recovered."
```

**Exercises & events** (`members/exercises.html`): `spokares/members-exercises` (inside `templates/page-exercises.html`)
```
group("members-page wrap")
    group("page-head") > h1 "Exercises &amp; events"
    group(section "ex-next") > h2("m-head" #next-up) "Next up"
        > p("m-line ex-bring") "<strong>Bring to every exercise:</strong> your <a href=/members/documents/#go-kits>go-kit</a>, … <a …#ics-309>309</a> forms."
        > spokares/events {"view":"next-up","types":"exercise","limit":2}
        > p("m-line ex-after") "<strong>After any exercise:</strong> send your logs to the exercise lead. ACS members: get your yearly field operation signed off."
    group("ex-grid")
      group(section "ex-section") > h2("m-head" #upcoming) "Later this season"
                                  > spokares/events {"view":"later","types":"exercise,training,on-air","limit":12,"cardTypes":"exercise","cardLimit":2}
      group(section "ex-section") > h2("m-head" #winlink-assignments) "Winlink assignments" > spokares/net {"view":"winlink","limit":8}
      group(section "ex-section") > h2("m-head" #public-service) "Public-service events" > spokares/events {"view":"public-service"}
                                  > p("m-line ex-tips") "First event? Read <a href=/members/documents/#public-service-tips>Preparing for public-service communications</a>."
      group(section "ex-section") > h2("m-head" #past) "Past exercises" > spokares/events {"view":"past","limit":8}
```

### 6.10 Dev environment (`wordpress/dev/`)

**Deliver first** (within the first few steps, so other builders can use it): `start.sh`, `stop.sh`, `blueprint.json`, `setup/mu.php`, `mu-plugins/spokares-dev-login.php`. Everything must **tolerate an incomplete theme or plugin** (skip a step with a log line rather than fail the boot).

**`start.sh <PORT> [--wp=<version>]`** (bash, `set -euo pipefail`; repo root derived from the script's location):
```
npx -y @wp-playground/cli@3.1.55 server --port=$PORT \
  --mount=$ROOT/wordpress/theme/spokares:/wordpress/wp-content/themes/spokares \
  --mount=$ROOT/wordpress/plugins/spokares-core:/wordpress/wp-content/plugins/spokares-core \
  --mount=$ROOT/wordpress/mu-plugins:/spokares-mu \
  --mount=$ROOT/wordpress/dev:/spokares-dev \
  --blueprint=$BLUEPRINT  > /tmp/pg-$PORT.log 2>&1 &
```
`$BLUEPRINT` is `dev/blueprint.json` with the `activateTheme` step dropped when `theme/spokares/style.css` is missing and the `activatePlugin` step dropped when `plugins/spokares-core/spokares-core.php` is missing (a node one-liner writes the filtered copy to `$TMPDIR`); `--wp=` replaces `preferredVersions.wp`. Then poll `curl -s -o /dev/null -w '%{http_code}' http://127.0.0.1:$PORT/` until 200/302 (timeout 180 s), print the URL and the two login links. `stop.sh <PORT>` kills the port. (If the extra mounts break the CLI, fall back to embedding the setup PHP in the blueprint and document it.)

**`blueprint.json`** (no `login` step, so front-end shots are logged out). Each step below is its own request:
1. `preferredVersions`: php `"8.4"`, wp **`"7.1.2"`** (pinned; `start.sh --wp=` overrides for release candidates).
2. `defineWpConfigConsts`: `WP_ENVIRONMENT_TYPE` "local", `SPOKARES_DEV` true, `WP_DEVELOPMENT_MODE` "theme" (no theme pattern cache in dev), `DISALLOW_FILE_EDIT` true, `DISALLOW_UNFILTERED_HTML` true, `SPOKARES_TODAY` "2026-09-26".
3. `setSiteOptions`: `blogname` "Spokane County ARES-ACS", `blogdescription` "Volunteer emergency radio for Spokane County", `timezone_string` "America/Los_Angeles", `date_format` "M j, Y", `time_format` "g:i A", `start_of_week` 0, `permalink_structure` "/%postname%/", `users_can_register` 0, `default_comment_status` "closed", `default_ping_status` "closed".
4. `installPlugin` two-factor from wordpress.org, activated.
5. `runPHP`: `require '/spokares-dev/setup/mu.php';` writes two one-line loaders into `/wordpress/wp-content/mu-plugins/`: `spokares-dev-login.php` (requires `/spokares-dev/mu-plugins/spokares-dev-login.php`) and `spokares-hardening.php` (requires `/spokares-mu/spokares-hardening.php` if it exists). Loaders keep live edits live.
6. `activateTheme` `spokares`.
7. `activatePlugin` `spokares-core/spokares-core.php` (its activation hook creates the role and default options).
8. `runPHP` (a new request, after `init` has run with the theme and plugin loaded): `require '/wordpress/wp-load.php'; require '/spokares-dev/setup/setup.php';` which creates the `editor` user (role `ares_editor`, or `editor` until the plugin is active; display name "Test Editor"; e-mail `editor@example.invalid`); runs `seed/import.php` in `dev` mode; runs `setup/pages.php`; deletes the Sample Page, the Privacy Policy draft and "Hello world!"; flushes rewrite rules.

**Seed** (`dev/seed/`):
- `data.json`: generated from `design/round3/_shared/assets/data.js` by `seed/convert.mjs` (node, no dependencies: evaluate the file with a stub `window`, drop `fn`, `JSON.stringify`). Commit the generated JSON.
- `extra.json`: facts the round-3 pages show that `data.js` holds only as page text, each with its source file: the two undated public-service rows (Lilac Festival Armed Forces Torchlight Parade, "Date not posted", NV2Z; Fun runs and bike races, "As requested", NV2Z) from `copy-members-exercises.md`; the SET short line "ARRL’s national exercise. The Section asks you to:"; the WSDOT main link (groups.io main) and its words "Exercise details on groups.io"; the hub tile slots (§2.3: `net-scripts` "Net script" script, `ics-213` "ICS-213" form, `ics-214` "ICS-214" log, `acs-task-book` "ACS Task Book" book); the same §2.3 swap for the Most used flags (`mostUsed`: `ics-214` added, `ics-309` removed); About's `_spk_reviewed` left empty and `_spk_owner` "Emergency Coordinator"; page excerpts from each `copy-*.md` "Meta description".
- `import.php <mode>`: `dev` or `production`. Under WP-CLI (`wp eval-file import.php production`) the mode is the first argument; it defaults to `dev` only when `wp_get_environment_type() === 'local'`, and **refuses to run anywhere else without an explicit mode**. Idempotent (skips when `spk_seeded` is set). It writes only through the §6.3 schema:
  - the 7 `spk_doc_cat` terms with `spk_order`; the 37 documents in `data.js` order (`menu_order` 10, 20, … within each section; `href` → `link` + `spk_url`, `null` → `soon`; `source.label`; `description` → `spk_note`; `howTo` → how-to fields; `links` → `spk_sublinks`; `sid` → how-to on `fema-is-courses`; `mostUsed` → `spk_most_used`, with `extra.json`'s `mostUsed` swap applied; `verify` → `spk_needs_check`; slug = `id`);
  - the 10 dated events + 2 undated public-service events (slug = `id`; `type` → `spk_kind`; `allDay`/`time`/`endTime`; `summary`; `tasks` joined by newlines; `detailsHref` → `spk_main_url` (+ words); `links` → `spk_links`; `extraForm` → the document's post ID; `contact.callsign` → `spk_contact_call`; `verify` or `detailVerify` → `spk_needs_check`; `spk_keep_past` '1' only on `wsdot-2026`); the 8 `pastExercises` as published exercise events with `spk_keep_past` '1' and `spk_precision` month/year (start = first day of the month or year);
  - `spk_rota` merged from `rota` (`callsign` → state `call`, `open` → state `open`) and `winlink.assignments`; `spk_nets`, `spk_radio`, `spk_meetings`, `spk_site` exactly as §6.3; `spk_tiles` from `extra.json` slugs.
  - **`dev` mode:** everything is published and `spk_privacy_ok` is '1', so the pages match B.
  - **`production` mode:** `spk_privacy_ok` is left empty and **every document is a Draft** (a person ticks the Privacy check and publishes each one; this includes the net-scripts link, D14 #1); every event with `spk_needs_check` is a Draft; meetings with `needs_check` are imported with `active` false; the alternate repeater with `show` false; the workshop's `home_extra` (a verify item in B) empty; rota rows dated before the run date are skipped. Other `needs_check` flags stay set and are cleared through the launch checklist (§5.11).
- **Production seeding:** `wp eval-file seed/import.php production`, then `wp eval-file setup/pages.php`, once at launch.

**Pages** (`setup/pages.php`; also runs under WP-CLI):

| Title | Slug | Parent | Content | Notes |
|---|---|---|---|---|
| Home | `home` | — | pattern `spokares/page-home`, hero swapped (below) | `show_on_front` page, `page_on_front` |
| How it works | `how-it-works` | — | pattern `spokares/page-how-it-works` | |
| About ARES & ACS | `about` | — | pattern `spokares/page-about` | `_spk_owner` "Emergency Coordinator" |
| For members | `members` | — | **empty** (template `page-members`) | |
| Documents & forms | `documents` | `members` | **empty** (template `page-documents`) | |
| Exercises & events | `exercises` | `members` | **empty** (template `page-exercises`) | |

- Read each page pattern's content from `WP_Block_Patterns_Registry::get_instance()->get_registered( $slug )['content']`; skip a page (with a log line) if its pattern isn't registered yet.
- **Hero photo:** once (found again by `_spk_seed` = `hero`), copy the theme's `assets/img/1920x1200sunset.jpg` into the Media Library (`media_handle_sideload` on a temp copy, alt ""). In Home's content, replace the theme file URL in the Cover's `url` attribute and in the `<img src>` with the attachment URL, add `"id":N` to the Cover's attributes and `wp-image-N` to the `<img>` class. The result must open without an invalid-block notice, and the front end must print `srcset`. No host name is baked in beyond the site's own uploads URL, and renaming the theme doesn't break it.
- Set excerpts from `extra.json`. No menus to create: navigation links are inline in the theme's parts.

**`mu-plugins/spokares-dev-login.php`:** acts only when `wp_get_environment_type() === 'local'` **and** `SPOKARES_DEV` is true **and** `$_SERVER['REMOTE_ADDR']` is `127.0.0.1` or `::1` (Playground always sets `127.0.0.1`). `?dev_login=1` logs in `admin`, `?dev_login=editor` logs in `editor`, then redirects to the same URL without the parameter. Never shipped (it lives only in `dev/`).

**Screenshots** (`shots.sh <PORT> [outdir]`, default `wordpress/dev/shots/`):
- Front end, logged out, for `/`, `/how-it-works/`, `/about/`, `/members/`, `/members/documents/`, `/members/exercises/`: desktop 1440 wide full page (`"/Applications/Google Chrome.app/Contents/MacOS/Google Chrome" --headless=new --disable-gpu --hide-scrollbars --virtual-time-budget=8000 --window-size=1440,<h> --screenshot=…`), and a **true 390** full page using `npx -y playwright@1 screenshot --channel chrome --viewport-size "390,844" --full-page <url> <file>` (uses the installed Chrome; no browser download). If Playwright fails, fall back to Chrome `--window-size=390,3400` and say the shot is approximate. Plus `/members/` at 390 **logged in as editor** (admin-bar gap check).
- Admin, through `?dev_login=editor` in a throwaway `--user-data-dir`: Dashboard, Net rota, Events list, the SET event edit screen, Regular meetings, Documents list, the ICS-213 document edit screen, Hub tiles, the Home page in the block editor (hero selected, sidebar open), and as admin: Net details, Meeting rules.
- Names: `<page>-desktop.png`, `<page>-mobile-390.png`, `admin-<screen>.png`.

**`checks.sh <PORT>`:** curl every page for 200; no PHP warnings/notices in `/tmp/pg-<PORT>.log`; one `<h1>` per page; no `href="#"`; all §2.4 anchors present; `/docs/ics-213/` returns 302; a draft document's `/docs/<slug>/` returns 404; `/xmlrpc.php` 403; logged out: `/wp-json/wp/v2/users` 404, `/wp-json/wp/v2/pages` 401, `/?author=1` 404, `/feed/` 404, `/wp-json/oembed/1.0/embed?url=<home>` has no `author_name`; `/index.php?option=com_content&view=article&id=11` 301; `/index.php?option=com_x` 410; `/Downloads/ARES%20NET%20ROSTER%20FEB09.doc` 410; a lost-password POST for an unknown user redirects to `checkemail=confirm`; `/members/` contains "Net control". (Directory listing and header checks run on the live server, §6.11.)

`dev/README.md`: how to start, log in, reset (restart), take shots, and run checks.

### 6.11 Ops scripts (`wordpress/ops/`, dev-environment builder, P1)

| Script | Runs where | Does |
|---|---|---|
| `ci.sh` | admin's machine or CI | PHPCS WordPress-Extra + PHPCompatibilityWP 8.3+ (§9.1 of the security note lists how to get PHP); Plugin Check in Playground; forbidden-code grep over our PHP (`nopriv`, `register_rest_route`, `$_REQUEST`, `unserialize`, `eval(`, `extract(`); versions of theme, plugin, must-use plugin and constants equal the tag; builds `dist/spokares-<v>.zip`, `dist/spokares-core-<v>.zip`, `dist/spokares-hardening-<v>.zip` (excluding `tools/`) to attach to the tag |
| `deploy.sh <tag> <staging\|production>` | admin's machine | §5.7: clean checkout of the tag; rsync of theme, plugin and our two must-use paths; copies `weekly-check.sh` to `~/bin/`; remote `wp rewrite flush` (when routes changed) and `wp cache flush`; never push-live |
| `weekly-check.sh` | host, weekly Enhance cron | §5.8 checks; success → `curl` the dead-man URL; failure → `curl …/fail` + `wp_mail` |
| `check-live.sh <tag>` | admin's machine, monthly and after deploys | §5.8 SSH comparison against the tag, `find` checks, and the §5.11 `curl` list |
| `backup-pull.sh` | admin's machine, monthly | §5.9 layer 3 |

### 6.12 Builder checklists

Priorities: **P0** needed for the integrated screenshots · **P1** needed before launch · **P2** after launch or if time allows (do not start P2 until P0 and P1 are done).

**Theme builder**
- P0: `style.css` header (Theme Name "Spokane County ARES-ACS", Text Domain `spokares`, Version 0.1.0, Requires at least 7.1, Requires PHP 8.3, Tested up to 7.1, Update URI false); `theme.json` (§6.7, every control listed off); fonts downloaded and wired; `site.css` port with the §6.8 vocabulary and §2.2/§2.3 changes; the eight templates and the header, footer and members-nav parts; `spokares-theme/brand` and `spokares-theme/message-path`; filters 1-5 and 8; P0 menu (full-screen night overlay, 1020px override); admin-bar offset.
- P1: filters 6, 7 and 9; the `navigation-overlay` part as B's drop-down (keep or fall back, §6.7); keyboard, Escape and focus tests for the menu; editor canvas check (site.css loads in the editor iframe; sections look like the front end); lists without bold; `.spk-edit-hint`, `.spk-edit-link`, `.hub-meeting-change`; `screenshot.png`.
- Accept when: with the dev environment, every page at 1440 and 390 matches B's `shots/` in structure, colour, type and spacing (differences only as listed in §2.2); no horizontal scroll at 390/360; every Tab stop shows B's focus ring; header sticky, with no gap for a logged-in editor at 390; menu collapses under 1020px; no console errors; the site still renders (plainly) with the plugin deactivated.

**Plugin builder**
- P0: bootstrap, constants, activation (§6.5); post types, taxonomy, meta registration; helpers and schedule engine (§3.3); the seven blocks with every view and the exact §6.4 markup; the three view modules; admin screens (Net rota with row choices and changed-rows save, Net details, Regular meetings, Meeting rules, Events form and list, Documents form and list with upload-on-save, Hub tiles, ARES site); `ares_editor` role; admin trimming; editor governance except the skeleton check (§6.5); `/docs/<slug>/`.
- P1: **the skeleton check (§4.3 layer 2)**; `spokares-editor-guard`; `spokares-admin-forms`; dashboard "Site tasks"; Duplicate; Undo last save; the Page review box; Mark reviewed today; guard rails with retained input and confirm ticks (§4.4); editor hints, placeholders, front-end edit links and the admin-bar menu; `spokares_purge_cache()`; Replace/Trash file removal, Pull this file now and the deletion guard; help tabs; `site_transient_update_themes` filter. **The must-use plugin** (§6.6: hardening, enumeration, content surface, headers, uploads with DOCX/XLSX inspection and EXIF stripping, two-factor, SMTP) and the **redirect map** (generator, re-point table draft, matcher).
- P2: rota paste box; `/calendar.ics` (research §9.2 rule 9 escaping); JSON export/import of options; `wp spokares page-sync <slug>`; LiteSpeed cache headers, only if a cache is ever added.
- Accept when: with seed data and `SPOKARES_TODAY` 2026-09-26, each view's output matches B's rendered markup for that date (hub: WSDOT "Happening now" + SET; rota NZ2S / Open / AE7RJ / WA7LNC / Open with Simplex, Winlink night, GMRS, Winlink night; Home "Next: Sat, Oct 10" and "Next: Thu, Oct 15", the From-home card and In-person line; How it works "Other nets" three lines; exercises cards, 6 later rows, 6 Winlink rows, 4 public-service rows, 8 past rows; library 37 rows in 7 groups); `?today=2026-10-02` (dev) shows an open slot first; the editor account sees exactly the §3.4 menu; every save redirects back with a notice; a REST save by the editor that moves a section is refused with `spokares_layout_locked`, while a text edit and an added list item are saved; `import.php production` leaves all 37 documents Draft with no privacy tick; deactivating `spokares-core` leaves XML-RPC, headers, the redirect map and 2FA enforcement working; no PHP notices with `WP_DEBUG` on.

**Patterns builder**
- P0: every pattern in §6.9 with B's words, anchors, classes, locks and views; the three page patterns built by inclusion; the three members bodies.
- P1: `needs-verify` on every former `data-verify` element; a word count per page within B's budgets (Home ≤ 400, How ≤ 1,200, About ≤ 2,800, hub ≤ 300, Documents ≤ 900, Exercises ≤ 700; target B's measured 259 / 909 / 1,274 / 124 / 549 / 427 ± 5%).
- Accept when: each page (and each members template) opens in the editor as admin with no invalid-block warnings; the front end has one H1 per page, every §2.4 anchor, no `href="#"`; no theme pattern appears in the inserter or the new-page starter window, for admin or editor, while page setup and the members templates still find them.

**Dev-environment and ops builder**
- P0: `start.sh` (step filtering, `--wp=`), `stop.sh`, `blueprint.json`, `setup/mu.php`, `setup/setup.php`, `setup/pages.php` (hero swap), `seed/convert.mjs`, `seed/data.json`, `seed/extra.json`, `seed/import.php` (both modes), the dev-login mu-plugin, `shots.sh`.
- P1: `checks.sh`; `README.md`; the §6.11 ops scripts.
- Accept when: a cold `start.sh` reaches 200 in under 3 minutes; the six pages exist at the right URLs with the front page set and the Home hero from the Media Library; the importer is idempotent and refuses to run outside local without a mode; the dev login refuses a non-loopback address; shots and checks run end to end.

### 6.13 Integration: click tests, Editing guide, launch gate

Run `start.sh`, `checks.sh`, `shots.sh`, then these by hand as **editor** (`?dev_login=editor`) unless noted. Every failure goes back to the owning builder.

1. **Lock UI (Home):** select the hero Cover: no Advanced panel (CSS class, anchor, HTML element), no colour, overlay, height or visibility controls. Look for a "+" at the end of the page and between sections: none. Try to insert a block into the Cover, a Quote and a Buttons group: not offered. No "Edit pattern" button; sections can't be moved or deleted.
2. **Server floor:** in the browser console, undo the tidy-up (`wp.data.dispatch('core/block-editor').setBlockEditingMode('', 'default')`), move a section and save: refused with "Layout changes need an administrator…", the text stays. A text-only change and an added list item save.
3. Text two levels deep is editable (a step title, a stop, a run-sheet item).
4. A table cell on About (leadership) and How it works (roles) can be edited; no row controls.
5. The hero photo can be replaced (the saved Cover keeps an `id` and `wp-image-N`); a button's text and URL can be changed; a stop can be added to What we do. **Paste from an e-mail and from Word** into a paragraph and a list item; record what Enter and Shift+Enter do, for the guide.
6. No code editor; no Custom CSS, colour, typography, spacing, dimension or visibility controls anywhere.
7. `<abbr>` in "Join ARES" survives editing the heading text and saving.
8. Dynamic blocks show a server preview with the plain-text "Edit in wp-admin › …" hint; About's last-reviewed shows its placeholder before a date is set; the editor can't change a dynamic block's settings (no controls, or the save is refused).
9. As **admin**: full editing (no lock, no layout check); pasting a section file's markup in the code editor works. For everyone: no theme patterns in the inserter. As editor: no Welcome Guide, no starter window.
10. Block Notes: record whether they work with comments off (not promised to editors).
11. Record the exact save-button word in 7.1.2 ("Save" or "Update") for the guide.
12. **Net rota:** "Frank AG7QP" stores AG7QP; "AE7RJ & NZ2S" stores AE7RJ with the notice; "Frank" alone isn't saved, the text stays, the message names the Tuesday; Open and Not posted yet show the right "Shows"; two browsers edit different rows and both are kept; the same row shows the conflict notice; Undo names the right save.
13. **Events:** Kind comes first and hides fields; a phone number in the short line saves a Draft with the confirm tick, and the tick publishes it; "hospital net" in a task saves a Draft with the field outlined and the text kept; publish shows it on the hub and Exercises; a Training event dated before the second Next-up card shows in Later this season; Duplicate; Trash.
14. **Meetings:** cancel Oct 10 with a note: Home "Next: Sat, Nov 14 (Oct 10 cancelled)", the hub's cancelled line; Meeting rules absent for the editor.
15. **Documents:** the file chooser is locked until Privacy check is ticked; nothing uploads until Publish; `/docs/<slug>/` 302s to the file; a Draft document's link is 404; Replace with the box ticked makes the old file URL 404; Trash with the box does the same; admin Pull this file now; a DOCX with a macro or an external template is refused; Media is absent; the editor can't delete a file in the media window.
16. **Hub tiles:** put slot 4's document into slot 2; the two swap.
17. **Front end:** logged in as editor, every list has its "Edit this list in …" link and the admin bar has Update lists; logged out, neither appears. At 390 logged in, no gap above the sticky header.
18. **Sign-in (on the release's staging site, not local):** a new editor account gets an e-mailed code at first login; TOTP enrolment and backup codes work on 7.1.2; with all providers removed from a test user, `/wp-json/wp/v2/pages`, `admin-ajax.php` and `async-upload.php` refuse it and wp-admin sends it to the profile.
19. Keyboard: menu opens, traps focus, Escape closes at 390; inline nav at ≥1020; copy buttons announce via the toast; TOC highlights the current chapter.
20. Cold-load deep links from another page land under the header: `/#join`, `/#visit`, `/about/#for-agencies`, `/about/#contact`, `/about/#licensing`, `/how-it-works/#activation`, `/how-it-works/#nets`, `/members/documents/#forms`.

**Editing guide** (deliverable of the integration step): `wordpress/guide/editing-guide.html`, one sheet printed on both sides (print CSS), with its screenshots in `wordpress/guide/shots/`. Written from real 7.1.2 screenshots taken during the click tests, with the **exact button words**. It follows research `editor-ux.md` §10's outline, updated for this plan: signing in (code from the phone app, or the e-mailed code; a backup code if both fail); the Site tasks card; the five jobs with their target times; "bold the first words" (a new item in What we do, the timeline or the message hops shows with no title without them; the editor warns after Save) and "Shift+Enter for a new line"; pasting: several paragraphs pasted into one paragraph arrive as one paragraph with line breaks (a notice says so), so paste one paragraph at a time into the paragraphs already there; where a published event shows (Later this season, then a Next up card and the members hub once it is one of the next two; never Home); Ctrl+Z before saving; what "Layout changes need an administrator" means; restores go to the webmaster; adding a table row is the webmaster's job; the never-publish list; house style. The back page has a box for that editor's printed backup codes, filled in at enrolment and never stored in the repo.

**Launch gate:** AG7QP (or the first named editor) sits at the dev site with the printed guide and no help and does the five jobs: rota **3 min**, event **3 min**, meeting **1 min**, document **3 min**, page text **5 min**. The integrator watches and writes down every stumble; each becomes a fix (screen, label or guide) before launch, and the changed jobs are tried again. Two-factor enrolment (§5.4) is done in person on the production site once the editor's account exists there.

---

## 7. Risks and open questions

### 7.1 Risks

| # | Risk | Effect | Mitigation |
|---|---|---|---|
| 1 | In 7.1.2 the root content-only lock doesn't lock top-level blocks (Advanced panel, Cover controls, root insertion stay open), and the editor tidy-up script relies on `setBlockEditingMode`, which could change in a later release | Editors could see controls they shouldn't, or try a layout change | The **server skeleton check is the lock** and doesn't depend on the editor UI; the script only hides controls; click tests 1-2 and 8 each release candidate |
| 2 | The skeleton check refuses a save after a core update migrates block markup on load | Editors blocked from saving a page | Before each major, an administrator opens and saves Home, How it works and About (§5.7); the error message names the webmaster; the check compares sorted attributes, not raw text |
| 3 | PHP-only blocks are new in 7.0 and the editor side uses an `__unstable` core global | A preview could break in a later release | Pre-release check with `--wp=<RC>`; fallback is a plain-JS edit function per block, no build |
| 4 | The 1020px menu override and the P1 drop-down depend on core Navigation class names and the overlay part | Crowded header or broken menu after a core update | Re-test each major; fallback is the P0 full-screen overlay, then core's 600px breakpoint plus a tighter bar |
| 5 | `<abbr>` may not survive RichText edits; pasting from Word or e-mail may bring odd markup | Lost abbreviation titles; messy text | Click tests 5 and 7; acceptable loss; the guide says how to paste |
| 6 | Two-Factor is "tested up to 6.9.9"; e-mailed codes depend on SMTP and the ~100/hour cap | Sign-in trouble | It ran on 7.1.2; 0.17 is in RC; TOTP plus printed backup codes as the other ways in; lost-password throttle protects the mail cap; fallback plugin WP 2FA (Melapress) in the same slot |
| 7 | Web server unknown | Cache choice, `.htaccess` behaviour, directory listing, `updraft/` exposure | Nothing depends on `.htaccess` beyond two rewrites; live checks for listing and `updraft/` 403 (§5.11); ask Verpex |
| 8 | A file saved with a draft document is reachable at its URL | A sensitive file exposed before publishing | Upload only after the Privacy check, only on Save; random file-name suffix; old files deleted on Replace/Trash; admin "Pull this file now"; D1 scanning for recovered files; sensitive items stay on groups.io |
| 9 | Custom code (a few thousand lines of PHP) with one technical maintainer | Bus factor | Small, standard APIs; seven blocks instead of seventeen; runbook; second admin; readable modules; no build; release zips for a successor without git |
| 10 | Admin edits in the Site Editor override theme files | Silent drift from git | Runbook: structural changes in git; "Reset" in the Site Editor; `check-live.sh` compares files, not database overrides, so `weekly-check.sh` fails on any `wp_template` or `wp_template_part` post and any `wp_global_styles` post that holds styles |
| 11 | Launch blockers from round 3 (unchanged) | Can't launch | Net-scripts link exposes the roster (D14 #1; production import keeps it a Draft); join@ not live; legal basis needs SCEM review; role addresses are proposals (D11) |
| 12 | Options (rota, meetings, radio, tiles) are not in WXR exports | Lost on a content-only migration | Database backups cover them; JSON export (P2) |
| 13 | Rules-plus-exceptions can't express an irregular one-off net | Wrong rota note | Per-Tuesday note field overrides the display |
| 14 | Home, How it works and About live in the database after setup, so a pattern change in git doesn't reach them | Pattern files and live pages drift | Rule in §5.7 (admin edits the page and mirrors the pattern in the same release); `page-sync` in P2 |
| 15 | Staging copies hold real password hashes and TOTP secrets | A forgotten staging site becomes a way in | Staging per release, deleted after; auto-updates on if it must live longer (§5.7) |

### 7.2 Decisions for the owner, the EC and AG7QP

1. **D9:** accept WordPress as the single source for the public events list, the rota, meetings and radio facts; groups.io keeps discussion, sign-ups and member files. AG7QP to confirm he'll keep the rota in the Net rota screen (the launch-gate session covers it), and whether he wants the paste box (P2).
2. **D5:** editors get WordPress accounts only, no Enhance "Collaborator" access; plugin list = `spokares-core`, Two-Factor, UpdraftPlus, slot 4 empty; our must-use plugin isn't a slot.
3. **D15:** keep Option B's six pages and confirm the re-point table that sends old URLs to them (§5.6).
4. **Net details and Meeting rules access:** who besides the admins may change repeater settings and meeting patterns (default: nobody until named).
5. **Uploads:** allow DOCX/XLSX for fill-in forms through the Documents form, inspected (recommended), or PDF only.
6. **Mobile menu:** B's drop-down if the P1 overlay part passes, otherwise the full-screen night overlay.
7. **Members headings:** accept the hairline under members H2s (red kept on library groups), per REVIEW.
8. **Alternate repeater:** keep it shown on How it works (as B does) until D13 #9 is answered, or hide it (one checkbox).
9. **Calendar feed (P2):** publish `/calendar.ics` with net control call signs, or not.
10. **Content items left open by REVIEW** (§2.4): hero image and headline, the second hero button, About length, public readiness levels, the Third Thursday time.
11. **Members pages' fixed lines** (Bring, After, What to bring, Not posted here, tips) become admin changes in git. Confirm that's acceptable, or name which should become form fields later.
12. **Launch review:** who reviews and publishes the 37 documents and clears the "Needs checking" items after the production import (an administrator, with the EC for anything sensitive).
13. **Ask Verpex** the questions in §5.10 before committing the host configuration.

---

## 8. Critique responses

Every "must" item is accepted. Where this plan chose among offered fixes, or went further, the reason is given.

### 8.1 WordPress core review

| # | Response |
|---|---|
| 1 Root lock doesn't hold | **Accepted.** §4.3 rewritten: the server skeleton check (`rest_pre_insert_page` + `wp_insert_post_data`) is the lock; `spokares-editor-guard` copies `DisableNonPageContentBlocks` as a P1 tidy-up. The skeleton also counts every attribute of our own blocks, so a view can't be changed. Risk 1 rewritten; click tests 1, 2 and 8 added |
| 2 One runPHP can't activate and import | **Accepted.** `activateTheme` and `activatePlugin` steps, then a separate `runPHP` with `wp-load.php` (§6.10); wp pinned to `"7.1.2"` |
| 3 Editor-hint link not clickable; empty render | **Accepted.** Plain text inside the wrapper; editor-only placeholders for views that print nothing (§6.4) |
| 4 Patterns in the inserter | **Accepted, the `Inserter: no` option, for every theme pattern** (pages without a `Block Types` header). Unregistering per user was rejected: the members templates render `spokares/members-*` patterns, so unregistering for editors would blank those pages for them. Admins paste section markup in the code editor |
| 5 `publish_pages` without `delete_pages` | **Accepted.** `page` (and `post`) `create_posts` → `manage_options`, in the must-use plugin |
| 6 Pages are one-time copies | **Accepted, the "better" option:** the three members pages are page-slug templates with theme patterns (§6.7, §6.9). Home, How it works and About stay locked post content with a written rule, and `page-sync` in P2 (§5.7) |
| 7 theme.json pickers | **Accepted** in full (§6.7); "Requires at least: 7.1" everywhere |
| 8 Hero image URL | **Accepted.** Page setup copies the JPEG into the Media Library and writes `id` and `wp-image-N` (§6.10) |
| 9 Meta revisions | **Dropped the claim.** No `revisions` support on either post type; Duplicate, Trash and backups are the undo (§0 #16) |
| 10 Drafts on option screens | **Accepted.** Settings screens hold back the one field and keep everything else; phone and e-mail shapes ask for confirmation (§4.4) |
| 11 One home per fact | **Accepted.** `net` views `from-home` and `other-nets`, and the place in `meetings` › `home`; the hand-check list is gone. Block Bindings stay out |
| 12 Pattern cache by Version | **Accepted.** Version = tag on every release (§5.7); `WP_DEVELOPMENT_MODE` `theme` in dev |
| 13 Navigation overlay part | **Accepted as a P1 try** with the full-screen overlay as P0 and fallback (§6.7) |
| 14 Over-engineering | **Accepted, both.** 17 blocks become 7 with `view`; TTL, nightly purge and `data-*` dates deferred, `spokares_purge_cache()` kept |
| 15 Dev login | **Accepted:** local + `SPOKARES_DEV` + loopback (Playground sets `127.0.0.1`) |
| 16 Admin bar on phones | **Accepted:** `top: 0` at 600px and below (§6.7) |

### 8.2 Volunteer-editor review

| # | Response |
|---|---|
| 1 Uploads before the privacy check; old files stay live | **Accepted.** Plain file chooser, locked until the Privacy check, uploads only on Save; Replace and Trash remove the old file by default (§3.4, §5.5) |
| 2 Call signs | **Accepted.** First word that matches; a row with none isn't saved and keeps the typed text; dropped extra words are reported, not silent |
| 3 Rejected saves | **Accepted,** merged with core #10: Draft + red outline + one sentence on post forms; field held back on settings screens; 10-digit phone shapes only (§4.4) |
| 4 Events that vanish | **Accepted.** `later` lists every upcoming non-public-service event not shown as a card, undated last; the seed still gives 6 rows |
| 5 Rota states | **Accepted.** One choice per row with "Shows"; `spk_rota` rows carry `state` |
| 6 No real-user test | **Accepted.** The Editing guide is a deliverable written from 7.1.2 screenshots; the launch gate uses the target times (§6.13) |
| 7 Two-factor setup | **Accepted,** merged with security #3: Email from account creation, TOTP and printed backup codes in person, profile trimmed (§5.4) |
| 8 Facts typed twice | **Accepted** (same as core #11), including the SCEM address |
| 9 Regular meetings | **Accepted.** Meeting rules is a separate screen behind `spokares_edit_net_details`; the note field is shown; cancellations are printed on Home and the hub |
| 10 Add document too long | **Accepted.** Basics first, "More options" closed, sub-links and search words admin-only, sub-link ids generated |
| 11 Hub tiles | **Accepted.** A Hub tiles screen with four slots that swap; tile meta removed from documents (`spk_tiles` option) |
| 12 Events form order | **Accepted.** Kind first, required, no default; fields hidden by kind; "Main link" + "More links"; address labels; our own Save box without Preview or Visibility |
| 13 Media and Pages menus | **Accepted.** Media hidden; editors have no delete caps; `pre_delete_attachment` guard; no "Add New Page" |
| 14 Lists that look broken | **Accepted.** CSS for items without bold; guide lines; paste click test |
| 15 Roles table rows | **Accepted.** "Cell text only; adding a row is admin work" (§2.1, guide) |
| 16 Where Frank looks | **Accepted.** Front-end "Edit this list in …" links for logged-in editors and an Update lists admin-bar menu |
| 17 Saving and undo | **Accepted.** Guide from screenshots, Ctrl+Z, restores by the webmaster; the Page review box replaces the hover row action; the save-button word is recorded in click test 11 |
| 18 Rota overwrites | **Accepted.** Changed rows only, with a conflict notice; Undo restores only the last save's rows and names who made it |
| 19 Small fixes | **Accepted, all ten** (§3.4, §4.2, §6.7 filter 9) |

### 8.3 Security and hosting review

| # | Response |
|---|---|
| 1 Importer in production | **Accepted, stricter:** production mode leaves every document a Draft without the privacy tick (not only uploads, because link targets like the net-scripts page are the exposure) and drafts or deactivates every flagged item (§6.10) |
| 2 Self-granted capability; handler checks | **Accepted.** The grant is shown and saved only on another user's profile behind `promote_users`; every handler has its own capability check and nonce (§6.0 rule 7). `_spk_allow_layout` is **removed** rather than guarded: administrators edit layout directly |
| 3 Password-only window | **Accepted.** Email provider on `user_register`; REST, admin-ajax and async-upload refused without 2FA except `two-factor/1.0` (§5.4) |
| 4 Old files stay public | **Accepted.** Delete on Replace/Trash, admin Pull, `/docs/` only for published + privacy-checked, listing and draft checks (§5.5) |
| 5 Redirect/410 map | **Accepted, moved to P1** in the must-use plugin, matched at `do_parse_request` (§5.6) |
| 6 Hardening in a normal plugin | **Accepted.** `spokares-hardening` must-use plugin, deployed and checked like the rest |
| 7 Monitoring | **Accepted.** SSH comparison against the tag from the admin's machine, `find` checks, Two-Factor and 2FA-coverage checks, dead-man ping (§5.8) |
| 8 Backups | **Accepted.** Monthly SSH pull the host has no credential for; `updraft/` 403 check; restore drill on Enhance staging. UpdraftPlus stays as a convenience layer |
| 9 Enumeration leaks | **Accepted,** all four (§5.3) |
| 10 Reset mail cap | **Accepted.** 5 per IP per hour, 20 per hour site-wide |
| 11 DOCX/XLSX | **Accepted:** ZipArchive inspection (fail closed without it) **and** DOCX/XLSX only through the Documents form |
| 12 Page text never-publish | **Accepted** as a non-blocking editor warning plus a dashboard line |
| 13 Research steps binding | **Accepted.** §9.2 and §11 named binding, with listed exceptions (§5.11) |
| 14 Staging | **Accepted.** Staging per release, deleted after; rsync only; never push-live |
| 15 Page creation | **Accepted** (same as core #5) |
| 16 Headers on login and admin | **Accepted;** static-file `nosniff` added to the Verpex questions |
| 17 Library input | **Accepted:** escaping, 100-character cap, section allowlist, `textContent` |
| 18 Environment-only dev switches | **Accepted:** every dev switch also needs `SPOKARES_DEV`; the weekly check fails if it is defined in production |
| 19 LiteSpeed headers | **Accepted.** Slot 4 stays empty; headers from our code if a cache is ever needed |
| 20 CI and release zips | **Accepted** (§6.11) |
| 21 Uptime keyword and EXIF | **Accepted:** keyword "Net control" on `/members/`; EXIF/GPS stripped from JPEG originals |
