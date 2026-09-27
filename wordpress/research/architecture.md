# spokares.org on WordPress: theme architecture for Option B ("Carry the Message")

- **Prepared:** 2026-09-26. **Lens:** theme architecture, one of several research notes for the WordPress build.
- **Inputs:** `design/round3/option-b-lines-down/` (DESIGN.md, site.css, site.js, data.js, the six pages), `design/round3/_shared/` (placement map, copy), `design/round3/REVIEW.md`, `research/decisions.md` (D5, D8-D11, D15), `research/hosting/verpex-enhance.md`.
- **Status:** a recommendation for the build. Platform choices still need the EC and editors to agree (D5).
- **Evidence tags** (used throughout):
  - **[V]** verified here in WordPress **7.1.2 / PHP 8.3.33**, run headless with the Playground CLI `php` command (no port, no PHP install). The probe is `wordpress/research/probes/wp-feature-probe.php`; run it with
    `npx -y @wp-playground/cli@3.1.55 php --mount=/Users/sean/Projects/spokane_ares/wordpress/research/probes:/test -- /test/wp-feature-probe.php`
  - **[S]** read in the WordPress 7.1.2 source (for example `wp-includes/js/dist/block-editor.js`) but **not click-tested** in a browser. §8.6 lists the click tests.
  - **[D]** stated in official docs, dev notes or release posts (§17).
  - **[I]** our inference or recommendation.

---

## 0. Recommendations on one screen

| # | Decision | Recommendation |
|---|---|---|
| 1 | WordPress floor | **WordPress 7.0 or later** (current: 7.1.2, 2026-09-22) [V][D]. 7.0 added PHP-only block registration, which removes the need for a JS build. Target PHP 8.3 and test on 8.4. |
| 2 | Theme type | **A block theme** (`spokares`) with `theme.json` v3, template parts, patterns and section styles. Not classic, not hybrid (§2). |
| 3 | Where data lives | **A small site plugin** (`spokares-core`) holds every content type, admin screen, dynamic block, bindings source, ICS feed and editor lock-down. The theme holds only design. Switching themes loses no data (§3). |
| 4 | How dynamic content works | **Rendered on the server by PHP-only dynamic blocks** (`block.json` + `render.php`, `supports.autoRegister`) that read the plugin's data. `data.js` is retired. The page is complete without JS; small ES modules add "now" badges, copy and filtering (§6). |
| 5 | Editing the weekly data | Add a top-level **"ARES" admin menu** with four plain forms: **Tuesday nets** (rota and Winlink, one screen), **Events & exercises**, **Documents**, **Site facts**. Add a dashboard **"Keep the site current"** widget that warns before the rota runs out (§6.3). |
| 6 | Build step | **None.** No `node_modules` and no bundler. PHP-only blocks, PHP patterns, JSON styles, plain ES modules, and at most about 20 lines of plain editor JS (§7). |
| 7 | Locking layout | Editors get **content-only editing of whole pages**: a root `templateLock: "contentOnly"` for users without `edit_theme_options`, the pattern "Edit pattern" escape turned off, no code editor, no lock UI, no custom colours or sizes, and no block visibility or custom CSS. Admins keep full control. Several parts must be **click-tested** first (§8). |
| 8 | Navigation | Core Navigation block in a header template part, with **inline links** (no `wp_navigation` ID coupling) and a 7.0 custom **overlay template part** styled night. Core collapses at 600px, not B's 1020px: use `overlayMenu: "always"` plus CSS at 1020px and up (§9). |
| 9 | About TOC | Core has **no TOC block** [V]. Use a server-rendered `spokares/toc` block that lists the page's H2 anchors (prototype [V]) plus a small scroll-spy module (§10). |
| 10 | Caching | With LiteSpeed: the **LiteSpeed Cache** plugin (tested on 7.1.2 [D]). Schedule blocks add a cache tag and a TTL that ends at the next local midnight; saving any schedule data purges the tag. Otherwise: Cache-Control headers only (§13). |
| 11 | Plugins | Stay **at or under D5's 4**: `spokares-core`, Two Factor, LiteSpeed Cache (only on LiteSpeed), and one reserved slot for a form (only if D11 adds one). SMTP, redirects, ICS and the TOC live in `spokares-core` (§14). |
| 12 | Source of truth for events (D9) | The owner's direction ("an easy interface to keep that list up to date from the WordPress admin") makes **WordPress** the source for the public schedule and the rota. The site publishes an ICS feed members can subscribe to. **D9 needs updating** (§6.10). |

---

## 1. WordPress today (checked 2026-09-26)

- **Releases.** 7.1.2 is current (released 2026-09-22). 7.1 "Mary Lou" came out on 2026-08-19 and 7.0 "Armstrong" on 2026-05-20. The core update API offers 7.1.2 with a PHP minimum of 7.4 [V: `api.wordpress.org/core/version-check/1.7`] [D]. Verpex runs PHP 8.3 by default (hosting §4.1).
- **theme.json is still version 3** in the 7.1 and trunk schemas [V].

**Features this build uses:**

| Feature | Since | How we use it |
|---|---|---|
| `theme.json` v3; `fontFace` with theme files (`file:./assets/fonts/...`) | 6.6 / 6.5 | Holds B's tokens; self-hosts Crimson Pro and Schibsted Grotesk |
| Section styles (block style variations with nested element and block styles, defined in `styles/*.json` with `blockTypes`) | 6.6 [D] | B's surfaces: night, slate, storm, dawn, day, paper |
| Block Bindings: server `register_block_bindings_source()`; editor `registerBlockBindingsSource()` (6.7), `getFieldsList` (6.9); `block_bindings_supported_attributes` filter (6.9); pattern overrides for custom blocks (7.0); List Item `content` bindable (7.1 [V]) | 6.5-7.1 [D] | Single-value "site facts" in editorial blocks (§6.5) |
| Script modules; Interactivity API; `viewScriptModule` in `block.json` | 6.5 [D] | Small front-end enhancements with no build |
| **PHP-only block registration** (`supports.autoRegister` plus a render callback); the editor shows a ServerSideRender preview and generates inspector controls from attributes | **7.0** [D][V][S] | Every dynamic block (§6.4) |
| Unsynced patterns and template parts open in contentOnly mode by default; "Edit pattern" / "Edit original"; `disableContentOnlyForUnsyncedPatterns` | 7.0 [D] | Part of the lock-down (§8) |
| Custom navigation overlays (`navigation-overlay` template-part area, Navigation Overlay Close block) | 7.0 [D] | B's night mobile menu |
| Block visibility by device; theme.json `blockVisibility.allowEditing` | 7.0 [D][V schema] | Turned **off** for editors |
| Per-block custom CSS, gated by `edit_css` | 7.0 [D] | Editors lack `edit_css` [V]; off for everyone with `DISALLOW_UNFILTERED_HTML` |
| Breadcrumbs, Icon, Heading blocks; Tabs and Playlist (7.1) | 7.0 / 7.1 [D][V] | Not needed in round 3 |
| Responsive styles and configurable breakpoints (`settings.viewport`); pseudo-states (`:hover`, `:focus-visible`) in theme.json | 7.1 [D][V schema] | Button hover and focus from theme.json |
| SVG Icon API (`wp_register_icon`) | 7.1 [D][V] | Optional, for the doc-type icons |
| Iframed post editor enforced for all blocks | 7.0 (API v3) / 7.1 [D] | Our blocks are `apiVersion` 3 |
| Notes on blocks (6.9); inline notes, rich text and @mentions (7.1) | 6.9 / 7.1 [D] | Replace the mockups' `data-verify` for page text |
| Visual revisions; font management page; AI client and Connectors (inactive without API keys) | 7.0 [D] | Revisions help editors undo. Leave Connectors unconfigured |

**Not in core:**
- A Table of Contents block. `core/table-of-contents` is still `__experimental` and Gutenberg-only [V].
- Real-time collaboration: "not enabled in the final release" of 7.1 [D].
- React 19, deferred [D].

**Implication [I]:** the plugin header says `Requires at least: 7.0`. Enhance's toolkit keeps core auto-updated (hosting §5.3), so check every new major release against `--wp=<version>` in Playground before it lands (§15).

---

## 2. Theme type: block theme plus site plugin

| | **Block theme** (recommended) | Hybrid (classic PHP templates + theme.json) | Classic theme |
|---|---|---|---|
| What editors see | Block editor; the canvas uses the theme's real fonts and colours from theme.json (WYSIWYG, close to Hostinger's builder) | Same editor; fidelity depends on a hand-kept editor stylesheet | Same as hybrid |
| Who changes the chrome (header, footer, sub-nav) | Admins, in the Site Editor, without code. Editors cannot: the Editor role lacks `edit_theme_options` [V] | Only a developer editing PHP | Developer |
| One source for design tokens | theme.json drives the front end, the editor and preset CSS variables | theme.json for the editor, PHP/CSS for templates | Duplicated CSS |
| Menus | Navigation block, admin-only | Classic menus, admin-only | Classic menus |
| Dynamic data | Dynamic blocks placed in templates and pages | Template tags and shortcodes; editors can't see or place them | Same |
| No build step | Yes (§7) | Yes | Yes |
| Lock-down tools | theme.json setting locks, template locks, section styles | Partial | Weak |
| Future maintainer | Standard WordPress practice from 2024 on | Uncommon mix | Legacy |

**Why a block theme:**
- Editors work in the block editor with any theme type, and they never see the Site Editor. So the theme type mostly decides the admin's and maintainer's experience [V][I].
- A block theme gives B's tokens one home (theme.json) that reaches the editor canvas.
- Header, footer and members sub-nav become three template parts instead of verbatim copies (B's `_chrome.html`).
- Section styles map one-to-one onto B's "surfaces set tone" model (DESIGN.md §3).
- PHP-only dynamic blocks can be placed in templates and in pages.

**Risks and mitigations [I]:**
1. **Admins drift the design in Global Styles.** theme.json disables custom colours, sizes and spacing (§8.2), and the runbook says to use "Reset" in Styles.
2. **Site Editor edits are stored in the database and override the theme files.** The runbook explains "Reset template". Structural changes go in the theme files, not the Site Editor.
3. **The Navigation block's fixed 600px breakpoint** (§9).

---

## 3. Separation of concerns

| Lives in | What | Why |
|---|---|---|
| **Theme `spokares`** | theme.json (tokens, fonts, presets, locks); templates and template parts; page and section patterns; section and block styles; chrome CSS; styling of the plugin's blocks; purely decorative blocks such as the message-path figure | Presentation only. Replacing it changes the look, not the data |
| **Plugin `spokares-core`** | Post types, taxonomy, settings (rota, facts); admin screens; dynamic blocks and their baseline CSS; bindings sources; the schedule engine; ICS feeds; editor lock-down; cache purging; SMTP; redirects; hardening; seed importer | The data and behaviour survive a theme switch. Editors' work is never locked inside a theme |
| **Database** | Pages (block content), events, documents, options, media | Backed up; exportable (WXR covers post types; options travel in DB dumps) |
| **`wp-config.php`** | `DISALLOW_FILE_EDIT`, `DISALLOW_UNFILTERED_HTML`, SMTP credentials, `WP_ENVIRONMENT_TYPE` | Secrets stay out of the DB and the repo |

**Boundary rules [I]:**
- Plugin blocks output semantic HTML with `spk-` classes and ship baseline CSS. The CSS uses theme.json preset variables with fallbacks, for example `var(--wp--preset--color--red, #D0202A)`, so the blocks stay readable under any theme.
- The theme refines their look with `wp_enqueue_block_style( 'spokares/upcoming', … )`.
- A **design-only** block, such as the inline-SVG message-path figure, may live in the theme. If the theme changes it disappears, and no data goes with it.

---

## 4. File layout (proposed)

```
wordpress/theme/spokares/                    (block theme; no build)
  style.css                  header only + tiny base
  theme.json                 v3: tokens, fonts, presets, locks, element/block styles
  functions.php              ~150 lines: enqueue, block styles, render filters (table data-labels, heading anchors)
  templates/  index.html  page.html  front-page.html  page-about.html  page-members.html  404.html
  parts/      header.html  footer.html  members-subnav.html  nav-overlay.html
  patterns/   *.php          page skeletons (home, how-it-works, about, members hub/docs/exercises) + sections
  styles/sections/*.json     section styles: night, slate, storm, dawn, day, paper
  styles/blocks/*.json       block style variations (button: ghost/light/ink/text-link; separator: message-line; …)
  assets/css/                chrome.css (masthead, footer, sub-nav), components/*.css (loaded per block)
  assets/fonts/              crimson-pro-*.woff2, schibsted-grotesk-*.woff2
  assets/img/                logos, seal, message-path-*.svg (theme-owned art)
  assets/js/theme.js         tiny module: deep-link landing correction
  blocks/message-path/       (optional) design-only PHP block: inline SVG, attribute "state"

wordpress/plugins/spokares-core/             (site plugin; no build; Requires at least: 7.0; Update URI: false)
  spokares-core.php          bootstrap
  inc/  post-types.php  settings-nets.php  settings-facts.php  schedule.php  bindings.php
        editor-governance.php  admin-dashboard.php  ics.php  cache.php  smtp.php  redirects.php
        security.php  seed.php
  blocks/<name>/  block.json (supports.autoRegister: true, apiVersion 3)  render.php  style.css  view.js (module, optional)
  admin/  media-picker.js (≈20 lines, wp.media)   editor-bindings.js (≈20 lines, plain JS)
  seed/  seed.json           one-time conversion of round-3 data.js (same shapes)
  tests/ *.php               run with the Playground CLI `php` command
```

---

## 5. Carrying B's design system into the theme

### 5.1 Tokens to theme.json

| B token (site.css §1) | theme.json |
|---|---|
| The 17 colours (`--night` … `--amber-ink`) | `settings.color.palette`. Set `custom`, `customGradient`, `defaultPalette`, `defaultGradients` and `defaultDuotone` to `false`. Add a Night duotone preset (B's `.duo`) and gradient presets dawn (night to peach), storm (night to slate) and dawn-bg (peach to white) |
| `--serif` Crimson Pro 400/500/600 + italic; `--sans` Schibsted Grotesk 400-700 | `settings.typography.fontFamilies`, with self-hosted woff2 `fontFace` (the mockups load Google Fonts) |
| `--fs-display` `clamp(2.75rem, 1rem + 5.4vw, 5.5rem)`, `--fs-h1` `clamp(2.5rem, 1.35rem + 3.2vw, 4.25rem)`, h2, h3, narr, lede, prose 1.25rem, body 1.0625rem, small .875rem | `settings.typography.fontSizes`, with the exact clamps (`fluid: false`). Set `customFontSize: false` and `defaultFontSizes: false` |
| `--s1`…`--s7` (8, 16, 24, 40, 64, 96, 128) | `settings.spacing.spacingSizes`; `customSpacingSize: false` |
| `.wrap` = min(1240px, 100% − 64px), 16px gutters under 640px | `layout.wideSize` 1240px; root padding `clamp(16px, 4vw, 32px)` with `useRootPaddingAwareAlignments`. Prose measure (64ch) via the long-read template |
| Radii, `--mast-h`, `--fade`, focus ring | `settings.custom` (becomes `--wp--custom--*`); focus styles through 7.1 pseudo-states and CSS |
| h1-h4, body, links, buttons | `styles.elements` and `styles.blocks` |

### 5.2 Surfaces to section styles

- B's surfaces (`.bg-night`, `.bg-slate`, `.bg-storm`, `.bg-dawn`, `.bg-day`, `.bg-paper`) each set a background and tone variables (`--fg`, `--muted`, `--link`, `--line`, `--focus`).
- Each becomes a **section style** in `styles/sections/*.json` with `blockTypes: ["core/group", "core/cover", "core/columns"]` [D 6.6].
- Each style sets its colours, its `elements.link` and `elements.heading` colours, and a `css` string that declares the tone variables.
- Components keep reading the variables, exactly as in site.css §2. The nearest surface still wins.

### 5.3 Components

| B component | WordPress form | Owner |
|---|---|---|
| Masthead (sticky, seal, nav, Join) | `parts/header.html`: Group (night) > site logo/title + Navigation + Buttons (red). Sticky offset needs `var(--wp-admin--admin-bar--height, 0px)` for logged-in users | Theme |
| Footer (33 words) | `parts/footer.html` | Theme (admin-edited) |
| Members sub-nav | `parts/members-subnav.html` with a second Navigation block | Theme |
| `.btn--red/ghost/light/ink`, `.textlink` | Button block styles (red is the default) | Theme |
| `.redline` (message line with amber node and ring) | Separator style "Message line" | Theme |
| `ul.stops` (What we do) | List style "Stops": `<strong>` label, then the line | Theme |
| `ol.steps` (join steps, activation track) | Group style "Steps", whose child Groups (h3 + paragraph + link paragraph) are numbered with CSS counters. Child style "Optional step" draws the dashed segment and ring. The "Optional" pill is its own paragraph with a "Tag" style | Theme |
| `.dawn-sky` hinge | Group or Spacer with the dawn section style | Theme |
| Hero with the sunset photo | Cover block with a night overlay. The image is editable (Cover `url` is content-role [V]) | Theme pattern |
| Cards, callouts, pullquote, `.panel` | Group, Quote and Pullquote block styles | Theme |
| `.table`, `.table--stack` | Table styles "Dense" and "Stack". A PHP `render_block_core/table` filter adds `data-label` to each cell from the header row, so stacking needs no JS [V] | Theme |
| Photos, `.duo`, PHOTO NEEDED | Image block with the Night duotone preset; Image style "Small" (660px cap). PHOTO NEEDED frames are pre-launch only | Theme |
| Message-path figure (How it works opener, static `relay` state) | Theme block `spokares-theme/message-path`: PHP-only, inline SVG, attribute `state`. An `<img>` SVG would lose the web fonts in its labels | Theme |
| Net card, radio line + Copy, events with date slabs, rota, Winlink, meetings "Next:", doc library, search preview, most-used tiles, TOC | Dynamic blocks (§6.4) | Plugin (+ theme CSS) |
| Long read (TOC column + 64ch prose) | `templates/page-about.html`: Columns > (sticky `spokares/toc`) + `post-content` | Theme + plugin |

### 5.4 CSS

- site.css (73 KB, 31 sections) splits three ways:
  1. **Tokens** go to theme.json.
  2. **Chrome and surfaces** go to `assets/css/chrome.css`, loaded everywhere and small.
  3. **Component CSS** loads per block with `wp_enqueue_block_style()`. Block themes already load only the CSS of the core blocks on the page.
- Selectors must be rewritten to target block markup (`.wp-block-group.is-style-steps`) instead of hand-written classes [I].
- Page-scoped `<style>` blocks disappear into the theme's CSS.

### 5.5 Fonts

- Self-host woff2 files for Crimson Pro (roman and italic; 400/500/600) and Schibsted Grotesk (400-700) through theme.json `fontFace` [D].
- Preload the two roman files.
- This drops the Google Fonts origin, which helps privacy (no third-party request, a point for the privacy page) and speed.
- The 7.0 font management page is admin-only [D], and editors cannot add fonts.

---

## 6. Dynamic content

### 6.1 Principles [I]

1. Editors fill in **forms**, never markup. Pages show the data through **dynamic blocks**.
2. **Everything factual is rendered on the server** and complete without JS. This retires round 3's "no-JS fallback must match data.js" rule, along with the mockups' `?today=` and `?verify=1` switches.
3. **Only time-of-view states** run in JS: "Happening now" and "On the air now" are toggled from `data-start` and `data-end` ISO attributes. This keeps page caching correct (§6.7).
4. **One home per fact** (placement map §1) still holds. Each dynamic block reads one store.

### 6.2 Data model (data.js to WordPress)

| data.js | WordPress storage | Admin screen (ARES menu) | Edited by | Rendered by |
|---|---|---|---|---|
| `rota` + `rotaMeta` | option `spk_nets` (rows keyed by ISO date) | **Tuesday nets** | Net Manager (Editor) | `spokares/rota`; the Note column is computed |
| `winlink.assignments` + `howTo` | the same option (form and task on Winlink Tuesdays) | **Tuesday nets** (fields show only on 2nd and 4th Tuesdays) | Winlink coordinator / Net Manager | `spokares/winlink` |
| `nets` rules; `radio` | option `spk_facts` | **Site facts** › Net & repeater | Admin (rarely changes) | `spokares/net-card`, bindings |
| `meetings` (rules, times, skip months) | `spk_facts` › Meetings, plus a "Cancelled dates" list | **Site facts** › Meetings | Editor | `spokares/meetings`, bindings |
| `events` | post type `spk_event` (not public: no single pages) | **Events & exercises**, a classic edit screen: title plus one meta box | Editor | `spokares/upcoming` (list and table layouts) |
| `documents` + `documentCategories` | post type `spk_doc` + taxonomy `spk_doc_cat` (7 seeded terms; only admins can add terms) | **Documents** | Editor | `spokares/doc-library`, `doc-search`, `quick-docs` |
| `org.location`, `links`, `contact` | `spk_facts` › Address, Links, Role addresses (with a "live" toggle) | **Site facts** | Admin | bindings, `spokares/contact` (if needed, §8.5) |
| `roles`, `sectionRoles` | `spk_facts` › Leadership rows (role, call sign, open) | **Site facts** | Admin | `spokares/roles` (if needed, §8.5) |
| `pastExercises` | page content (a List on the Exercises page) | Pages | Editor | core blocks |
| `meta.asOf` | the current date in the site timezone (America/Los_Angeles) | n/a | n/a | `spokares/asof` |

**Field rules, enforced on save [I]:**
- **Call signs:** `^[A-Z0-9]{1,3}[0-9][A-Z0-9]{0,4}$`, uppercased automatically. This keeps names off the rota, per the placement map "call signs only" rule.
- **Times:** HTML5 `type=time` inputs, shown to readers in 12-hour form ("8:00 PM").
- **Document descriptions:** at most 8 words, with a live counter.
- **Uploads:** allowlisted MIME types (§14).

**"Needs checking"** is an admin-visible checkbox on events and documents. It replaces `verify: true` and `data-verify`, and is never shown to readers. For page text, use **Notes** (6.9+).

### 6.3 Admin experience

- **One top-level menu, "ARES"**, placed right under Dashboard:
  - **Tuesday nets.** One table of the next 12 Tuesdays, each already labelled from the rules (Simplex, Winlink night, GMRS 7:30 PM). Per row: a call sign box, an "Open slot" tick, and on Winlink nights a form box and a task box. One Save button. Past rows drop off by themselves. This is the monthly edit.
  - **Events & exercises.** A list table (Date, Title, Type, Needs checking) sorted by date, with "Upcoming" and "Past" views. The edit screen has a title plus the fields: type, start and end dates, all day, times, summary, tasks (one per line), links (`Label | URL` per line), where, contact call sign and optional.
  - **Documents.** A list grouped by category. Edit fields: category, file (a "Choose file" button, or a link), format (detected from the file), version, a description of at most 8 words, source, most used, a how-to link and search keywords.
  - **Site facts.** Net and repeater, meetings, address, links and role addresses, and leadership rows. Admin-only except the meetings panel.
- **The dashboard widget "Keep the site current"** shows:
  - "Rota filled to Tue Oct 27: add November" (turns amber when fewer than 3 future weeks are filled)
  - the next 3 events
  - the count of "Needs checking" items
  - links to the four screens
- **Clutter off for Editors:** Comments disabled site-wide; Posts hidden (the IA has no news, D15); Tools hidden.

### 6.4 Dynamic blocks (plugin; PHP-only, no editor JS)

Each block is a folder holding `block.json` (`"supports": {"autoRegister": true}`), `render.php` and `style.css`, plus `view.js` where noted. Registration from `block.json` with autoRegister, a `render` file and a server preview in the editor: [V] (probe 1), [S] (the editor uses `useServerSideRender` with the `postId` context).

| Block | Attributes (inspector controls auto-generated) | Used on | Replaces |
|---|---|---|---|
| `spokares/net-card` | `variant` (public, members-line) | How it works `#nets`; hub `#rota` copy line | `[data-widget="net-card"]`, `button[data-copy]`, `[data-ics="weekly-net"]` |
| `spokares/upcoming` | `types`, `limit`, `days`, `layout` (list, table), `mergeWorkshops`, `excludeIds` | Hub "This week"; Exercises `#next-up`, `#upcoming`, `#public-service` | `[data-widget="upcoming"]` |
| `spokares/rota` | `weeks` | Hub `#rota` (the "Open" tag links to the open-slot line, a REVIEW fix) | `[data-widget="rota"]` |
| `spokares/winlink` | `variant` (next, table), `past` | Exercises `#winlink-assignments` | `winlink-next`, `winlink-table` |
| `spokares/meetings` | `ids`, `show` (next date) | Home `#visit` "In person" | `[data-widget="next-meeting"]` |
| `spokares/asof` | none | Hub page head | `[data-widget="asof"]` |
| `spokares/doc-library` | none | Documents page | `[data-doc-library]` (server-side `?q=` and `?cat=`; view.js filters live) |
| `spokares/doc-search` | none | Hub search box (a GET form to the Documents page; view.js shows the top-5 preview) | `form[data-doc-preview]` |
| `spokares/quick-docs` | `limit` (4) | Hub "Most used" (links go **straight to the files**, a REVIEW shared fix) | static tiles |
| `spokares/toc` | `title` | About (in the template) | `nav[data-toc]`, `details[data-toc-mobile]`, the Contents button |

**Limits [D]:**
- Auto-generated controls cover simple attribute types; the dev note's example uses string, integer, boolean and enum. Attributes with the `local` role, or of unsupported types, get no control.
- Anything richer needs a small plain-JS edit function, still without a build (§7).

### 6.5 Block Bindings (single facts inside editorial blocks)

- **Sources:**
  - `spokares/fact`, for example `{"key":"repeater.line"}` → "W7GBU 147.300 MHz, +600 kHz, 100 Hz"; `link.groupsio`, `email.join`, `address.oneline`.
  - `spokares/schedule`, for example `{"key":"next-meeting","id":"workshop"}` → "Next: Sat, Oct 10".
- **Status:** custom source rendering on the front end is verified [V] (probe 3).
- **Where to use them:**
  - Buttons: groups.io and county-application URLs.
  - A paragraph or list item that is only the fact: the address line, the settings line.
  - **Not** mid-sentence. A binding replaces the whole attribute. Sentences like Home's "listen to the Tuesday net, 8:00 PM, 147.300 MHz" stay editable text, and the Site facts screen lists where they appear so a frequency change is checked by hand [I].
- **Editor display:** about 20 lines of plain JS call `wp.blocks.registerBlockBindingsSource({ name, getValues })` and read a JSON copy of the facts added with `wp_add_inline_script`. `canUserEditValue` returns false, so bound text shows as connected and read-only [D].
- **Stop editors unbinding:** `edit_block_binding` maps to `edit_post` by default [S: `wp-includes/capabilities.php`]. The plugin's `map_meta_cap` filter should require `manage_options` for it [I].

### 6.6 Schedule engine

- `inc/schedule.php` ports `SPOKARES.fn`: `net_on($date)`, `next_net()`, `next_meetings($n)`, `upcoming($opts)`, plus nth-weekday rules and skip months.
- It uses `wp_timezone()` and `wp_date()`, and always formats time in 12-hour form ("8:00 PM", "9:00 AM–noon").
- Unit checks live in `tests/*.php` and run with the same Playground `php` command, with no PHP install [V: method].

### 6.7 Time, caching and correctness

1. **Server output is filtered by day:** past events drop off, "next" rows are marked, and multi-day events show "Happening now".
2. **Each schedule block** calls `do_action('litespeed_tag_add', 'spk_schedule')` and `do_action('litespeed_control_set_ttl', <seconds to next local midnight>)` [D: LSCWP API]. It also sends a matching `Cache-Control: max-age` for any other cache. Without the plugin, the hooks do nothing.
3. **Saving any event, document or settings screen** calls `do_action('litespeed_purge', 'spk_schedule')`. A daily cron event at 00:05 Pacific does the same. Enhance runs WP-Cron hourly from system cron (hosting §5.3).
4. **Hour-level states** ("On the air now" on Tuesday 8:00-9:00 PM) are applied by the block's `view.js` from data attributes. Without JS they are simply absent.

### 6.8 Calendar files (replacing the browser-built .ics)

- Feeds (rewrite rules or query variables):
  - `/calendar/net.ics` (weekly RRULE with a VTIMEZONE)
  - `/calendar/events.ics` (subscribable)
  - `/calendar/event/<id>.ics`
- "Add to calendar" becomes a plain link that works without JS, and members can subscribe to the feed in any calendar app [I].

### 6.9 Seeding and the dev loop

- Convert round-3 `data.js` once into `seed/seed.json`, keeping the shapes. `seed.php` imports it on first activation or from the dev blueprint, which gives Playground and launch the same content.
- Page content comes from the theme's page patterns (`patterns/page-*.php`), placed at setup.
- Theme images that editors may replace (hero, photos) are sideloaded into the Media Library at seed time [I].

### 6.10 Effect on D9

- D9 recommended the groups.io calendar as the single source.
- The owner now wants the list maintained from WordPress admin. So **WordPress is the source** for public events and the rota, and the site publishes `events.ics`. Member-only items stay on groups.io.
- Ask AG7QP to confirm he will keep the rota in WordPress instead of hand-typed lists on ag7qp.com. **Update D9 accordingly** [I].

---

## 7. No build step: feasibility

| Piece | Build-free approach | Status |
|---|---|---|
| Dynamic block registration and editor preview | `block.json` + `render.php` + `supports.autoRegister` (WordPress 7.0+) | [V] registration and render; [S] the editor uses ServerSideRender and auto-generated controls |
| Block styles and section styles | `styles/**/*.json` partials and `register_block_style()` in PHP | [D] |
| Patterns | `patterns/*.php` headers (Title, Slug, Categories, Inserter: no, Block Types) | [D] |
| Bindings editor display | ≈20 lines using the `wp.blocks` global | [D] |
| Admin forms | PHP meta boxes and Settings API pages, with HTML5 `date`, `time`, `url` and `pattern` inputs (native pickers) | standard WordPress |
| File picker for Documents | ≈20 lines of plain JS with `wp.media` | standard WordPress |
| Front-end behaviour | ES modules through `viewScriptModule`; WordPress prints the import map | [D] |
| Interactivity API | Optional. Import `@wordpress/interactivity` in a plain module, no JSX. Not needed for our widgets; plain modules are easier for volunteers to read | [D][I] |
| Rich custom editor UI (if autoRegister controls are ever not enough) | A plain `wp.element.createElement` edit function plus `wp.serverSideRender`. Verbose, but no build | [D][I] |
| **Avoid** | React admin screens with DataViews/DataForm, and custom RichText formats with complex UI. Both effectively need a build | [I] |

**Verdict:** feasible for the whole scope. Nothing in the theme or plugin needs npm.

**The one caveat:** the editor side of autoRegister relies on `window.__unstableAutoRegisterBlocks` inside core [S]. The public contract is the PHP flag documented in the 7.0 dev note, but re-run the §8.6 editor checks after each major update.

---

## 8. Locking layout: editors change text, links and images only

### 8.1 Target

**An Editor (Frank, AG7QP) can:**
- change the words of headings, paragraphs and list items
- change button text and links
- replace images and alt text
- add or remove list items
- leave Notes
- use the ARES screens
- upload documents

**An Editor cannot:**
- add, move or delete sections or paragraphs on the locked pages
- change colours, fonts, spacing or visibility
- touch the header, footer or menus
- open the code editor or custom CSS
- unbind facts

**Admins** can do everything.

### 8.2 Layers

| Layer | Mechanism | Evidence |
|---|---|---|
| Whole-page content-only | `block_editor_settings_all`: when the user lacks `edit_theme_options` and the page is a locked page (all pages by default; an admin-only "Allow layout changes" meta turns it off per page), set `$settings['templateLock'] = 'contentOnly'`. The root lock is read as `state.settings.templateLock` [S], and Groups inherit it through InnerBlocks | [S] |
| No "Edit pattern" escape | Also set `disableContentOnlyForUnsyncedPatterns = true` (or strip `metadata.patternName` when seeding). In 7.1, any block with a pattern name, or any Group carrying its *own* `templateLock: "contentOnly"`, becomes a "section" with an **Edit pattern** button that has **no capability check** [S: `isSectionBlockCandidate`, `EditSectionButton`]. Under a root content-only lock, descendant Groups are not sections [S] | [S][D] |
| No synced patterns in page content | `core/block` is always a section ("Edit original") [S], and Editors can edit `wp_block` posts | [S] |
| No lock UI | `$settings['canLockBlocks'] = false` for non-admins (the default is `true`) [S] | [S] |
| No code editor | `$settings['codeEditingEnabled'] = false` for non-admins | [S] (setting present in `editor.js`) |
| Design controls off | theme.json: `appearanceTools: false`; `color.custom`, `customGradient`, `defaultPalette`, `defaultGradients` false; `typography.customFontSize`, `defaultFontSizes`, `fontStyle`, `letterSpacing`, `textTransform`, `lineHeight` false; `spacing.customSpacingSize: false` with padding and margin off; `border`, `shadow`, `background` and `dimensions` off; `blockVisibility.allowEditing: false`; `lightbox.allowEditing: false` | [D][V schema] |
| No per-block CSS | `edit_css` gate: Editors lack it [V]. `define('DISALLOW_UNFILTERED_HTML', true)` removes `unfiltered_html`, which Editors **have by default** [V], and `edit_css` from everyone | [V][D] |
| Smaller inserter (for any unlocked page) | `allowed_block_types_all`: paragraph, heading, list, image, buttons, table, details, quote, separator and our blocks | [D] |
| No outside content | Remove the block directory, `remove_theme_support('core-block-patterns')`, `should_load_remote_block_patterns` false, Openverse off, `fontLibraryEnabled` false in the editor | [D][S] |
| Facts can't be unbound | `map_meta_cap` for `edit_block_binding` requires `manage_options` | [S][I] |
| No template picker | Templates are assigned automatically: `page-about.html` by slug, and the members pages through a `page_template_hierarchy` filter [V] (probe 4). Declare no `customTemplates` | [V] |
| Chrome out of reach | The header, footer and menus need `edit_theme_options`, which Editors lack [V] | [V] |

### 8.3 Why not rely on patterns' default content-only mode?

- In 7.0 and later, unsynced patterns *open* content-only, but anyone can double-click or press **Edit pattern** to get full editing [D][S]. That is guidance, not a lock.
- The root-level lock plus `disableContentOnlyForUnsyncedPatterns` removes that escape for Editors [S].

### 8.4 What editors lose, and the fix [I]

- Adding a new paragraph or section to a locked page becomes an admin job (a two-minute task), or the admin ticks "Allow layout changes" on that page for a while.
- This is the cost of "hard to break". List items can still be added, because List and List Item are both content blocks and 7.0 allows children to be inserted [D].

### 8.5 Known gap: table cells

- In core `block.json`, `core/table` marks **only `caption`** as content-role [V] (probe 6). Paragraph, heading, list-item, button, image, cover, details (summary) and quote are all content-role [V].
- So under a content-only lock, table **cells may not be editable**. This must be click-tested.
- About has six small tables and How it works has one.
- **If cells are locked:**
  1. **Data tables** move to plugin blocks fed by Site facts: leadership (both tables), exam venues, and contact role addresses. They are data anyway, and the call-sign validation applies.
  2. **Static reference tables** (legal basis, ACS task book, roles members take) are admin-edited. The legal basis needs SCEM review regardless.

### 8.6 Click tests for the build (Editor account, Playground `?dev_login=1` for admin only)

1. On a locked page, an Editor can edit text in a Group two levels deep. The toolbar shows no **Edit pattern** or **Modify** and no lock icon. The block inserter does not appear between sections.
2. A page seeded from a pattern (with and without `metadata.patternName`) shows no Edit pattern button to an Editor.
3. **Table cells:** editable or not under the root content-only lock (§8.5).
4. A Cover image can be replaced. Button URL and text can be edited. A new List item can be added.
5. The Options menu has no "Code editor". Advanced shows no "Custom CSS" and no block visibility control.
6. A bound paragraph shows the fact read-only and cannot be disconnected.
7. An autoRegister block shows its server preview. Its controls show for an Admin and not for an Editor inside a locked page.
8. An Admin sees full editing on the same page.

---

## 9. Navigation

- **Header** (`parts/header.html`): brand link (seal + "Spokane County / ARES-ACS"), a core Navigation block, and a separate red "Join the team" button. Join stays visible in the bar at every width, per B §6.1.
  - Write the navigation links **inline**, with relative URLs and no `ref`, so the theme doesn't depend on a `wp_navigation` post ID from one install [I].
  - Admins edit links in the Site Editor. Editors cannot, and that is intended [V].
- **Breakpoint:** the core Navigation block's `overlayMenu` is `mobile` (collapses under **600px**), `always` or `never`. The breakpoint is hard-coded in its CSS (min-width 600px / 782px) and is not tied to 7.1's `settings.viewport` [V] (probe 7). B collapses under **1020px**. Options:
  - **A (recommended):** `overlayMenu: "always"`, plus theme CSS at 1020px and up that shows the menu inline and hides the toggle. Test keyboard use, focus and Escape at both sizes.
  - **B:** accept 600px, and check that the four links plus Join fit from 600 to 1020px (they probably don't below about 760px) [I].
- **Mobile overlay:** a 7.0 custom overlay, `parts/nav-overlay.html`, registered in theme.json `templateParts` with `area: "navigation-overlay"`. It uses a night surface and includes the Navigation Overlay Close block [D]. The core runtime (Interactivity API) handles focus trapping and Escape, which replaces site.js §1.
- **Members sub-nav:** `parts/members-subnav.html`, included only by `page-members.html` (auto-assigned, §8.2).
  - Its five items include the external groups.io link, with B's `.ext` marker and the `.vh` "(opens groups.io, members only)" note.
- **Current-page marking:**
  - Core marks the link to the current page. Confirm it prints `aria-current="page"`.
  - "For Members" must also light up on child pages. If core doesn't mark ancestors, add a small `render_block_core/navigation-link` filter that sets `aria-current="page"` when the current page is a descendant of the link target, since B's CSS keys off the attribute [I].
- **Footer links:** plain links in `parts/footer.html` (admin-edited).

---

## 10. Table of contents for About

| Option | Verdict |
|---|---|
| Core `table-of-contents` block | Not in core, only Gutenberg's experimental list [V]. Don't depend on it |
| A TOC plugin (for example Easy Table of Contents, 600k installs [D]) | Uses the plugin budget; generic markup and JS; B's styling fights it. No |
| A hand-kept List of anchor links | Drifts as soon as a heading changes. No |
| **`spokares/toc` server block** | **Yes.** On render, it parses the current page's blocks (`parse_blocks`, recursing into Groups), collects H2s with anchors, and prints `nav.toc` for the desktop column and a closed `details.toc-mobile` for phones. Prototype verified [V] (probe 2) |

**Details:**
- **Anchors:** the nine ids from placement map §2.4 are seeded in the page content. The anchor attribute is not content-role, so Editors in content-only mode can rename a heading without breaking its id [I; click-test].
- **Fallback:** if a heading has no anchor, a `render_block_core/heading` filter adds a slug id.
- **`view.js` (module):**
  - IntersectionObserver scroll-spy puts the red rule on the active item.
  - The phone "Contents" button reopens the details.
  - Deep-link landing correction (ported from `hashLanding()`).
- **Template:** `page-about.html` holds the sticky column (core `position.sticky` support), `post-content`, and a `render_block` filter for the `.h-anchor` link and "Back to top" after each chapter. Editors never handle any of this.

---

## 11. What happens to site.js (1,037 lines) and data.js (37 KB)

| site.js section | New home |
|---|---|
| 1 Primary nav / mobile menu | Core Navigation block (deleted from our code) |
| 3 Net card, 5 Upcoming, 6 Rota, 7 Winlink, 8 Next meeting / as-of | PHP render in plugin blocks. Only a small `view.js` for "now" badges |
| 9 Copy radio settings | `net-card/view.js` (Clipboard API with the textarea fallback and a toast) |
| 10 Add to calendar (.ics in the browser) | Server `.ics` endpoints (§6.8). No JS |
| 11 Tap-to-define `.term` | **Drop or simplify** (§12). A plain-JS format type is possible later |
| 14 Story / scrollytelling | Cut. Round 3 already uses the static `relay` figure |
| 15 TOC active section + Contents button | `spokares/toc` `view.js` |
| 16 Documents library (`?q=`, `?cat=`) | Server-side filtering by GET. `view.js` filters live and announces the count |
| 17 Search preview (hub) | `doc-search/view.js`, reading a small JSON index printed by the block (37 docs), with no REST call |
| 18 Generic chip filter | Delete (round 3 doesn't need it) |
| 19 Deep-link landing | `theme.js` (tiny). Less needed once fonts are self-hosted and preloaded |
| 20 `?verify=1` aid | Delete. Use "Needs checking" and Notes |
| data.js | Retired. Replaced by `seed.json` (import once), then the database |

Expected result: a few small modules loaded only on pages that use them [I].

---

## 12. What works well as a theme, and what doesn't

| Option B feature | Verdict | Treatment |
|---|---|---|
| Night-to-dawn-to-day surfaces; red line; amber markers; Crimson + Schibsted | **Works well** | Section styles, block styles and theme.json tokens (§5) |
| Fixed chrome (header, sub-nav, footer) | **Works better** | Template parts, out of editors' reach |
| One data source for weekly items | **Works better** | Database + dynamic blocks. No more no-JS fallback drift |
| Home hero, What we do, Come visit, Join steps | Works | Locked page content built from patterns. Cover image editable |
| How it works opener figure (static `relay`) | Works | Theme's inline-SVG block. The scrollytelling variant stays cut |
| Tables that stack on phones | Works | Table style + PHP `data-label` filter [V]. Cell editing to be verified (§8.5) |
| Members hub, Documents, Exercises | **Works well** | Almost entirely plugin blocks. Editors maintain data, not pages |
| About long read with sticky TOC | Works | `spokares/toc` + template. The **sidenote rail** (`span.sidenote` floated into a third column) has no core equivalent: use core **Footnotes** or drop sidenotes (round 3 says "sparingly") |
| Inline markup inside editable text (`<abbr title>`, `.term` buttons, `.tag` spans, `data-verify` spans) | **Doesn't work well** | Core RichText keeps only registered formats. Custom inline elements are brittle when editors retype text [I]. Keep them out of editable text: pills become their own paragraphs; `data-verify` becomes Notes and "Needs checking"; `<abbr>` only where an admin maintains it. Tap-to-define: drop for launch, or register one plain-JS "Definition" format later |
| Inline SVG sprite at the top of `<body>` (`<use href="#i-copy">`) | Doesn't fit | Blocks print their own small inline SVGs, or use the 7.1 SVG Icon API. A body-level sprite is not present in the editor iframe |
| `?today=` / `?now=` demo parameters | Dev only | Honour them only when `WP_ENVIRONMENT_TYPE` is `local`, for QA |
| Page-scoped `<style>` blocks | Doesn't fit | Move into theme CSS |
| "Mockup — content to be verified" footer line | Remove at launch | |
| Members area "without login" | Works | Public pages under `/members/` (D8). Optionally `noindex`; nothing sensitive is on them anyway |

---

## 13. Performance and caching on LiteSpeed

- **The web server is still unconfirmed** (hosting §4.2), and that decides the cache.
- **If it is LiteSpeed (Enterprise or OpenLiteSpeed):**
  - Install **LiteSpeed Cache** (7.9.1, tested on 7.1.2, 7M installs [D: plugin API]). Use:
    - public page cache on
    - default TTL of a week (604800 s is the plugin default [D]), shortened per page by our TTL-to-midnight hook
    - its automatic purge on publish or update
    - our `spk_schedule` tag purges (§6.7)
  - Leave **off:**
    - Cache Logged-in Users, so editors always see fresh pages
    - CSS/JS minify and combine: block themes already load per-block CSS, and combining risks breakage
    - QUIC.cloud and image optimisation (an external account)
    - ESI: not needed, and OpenLiteSpeed has no ESI [D]
  - Its "Scheduled Purge URLs" setting [D] is a no-code backup for the daily purge.
- **If it is Nginx or Apache:** no page-cache plugin. The plugin's `Cache-Control` headers and a small site are enough. Verpex's "WordPress caching" toggle, if present, gets the same TTL-to-midnight headers [I].
- **Asset budget [I]:**
  - No jQuery on the front end.
  - Chrome CSS is small; component CSS loads per block.
  - Two preloaded woff2 fonts.
  - The Cover hero gets `fetchpriority` from core; images below the fold lazy-load (core).
  - 7.1 processes images in the browser, with AVIF and HEIC support [D].
  - Core's speculative-loading prefetch stays on its default.
  - Script modules load only on pages whose blocks need them.
- **Cron:** Enhance runs WP-Cron hourly through system cron. The daily purge at 00:05 therefore fires within the hour, and TTL-to-midnight covers the gap [I].

---

## 14. Plugins and security posture (architecture level)

**Plugin set (at most 4, per D5):**

| Slot | Plugin | Why |
|---|---|---|
| 1 | `spokares-core` (ours) | Data, blocks, lock-down, ICS, SMTP (`phpmailer_init` with wp-config constants), redirects (`redirects.csv` turned into a PHP map at deploy; no `.htaccess` dependence, hosting §4.2), hardening |
| 2 | **Two Factor** (0.16.0, from WordPress.org's own team; "tested up to 6.9.9" [D]) | 2FA for every account, since the old site was hacked. **Test it on 7.1 before relying on it** |
| 3 | LiteSpeed Cache | Only if the server is LiteSpeed |
| 4 | (reserved) | A form plugin only if D11 adds a form. There is none at launch (role mailto links) |

**Not needed:**
- An events plugin, ACF/SCF (native meta boxes are enough; Secure Custom Fields is the fallback if richer field UIs are wanted), a TOC plugin, an SMTP plugin, Redirection.
- A page builder, a commercial theme.

**Hardening built into the architecture [I]:**
- **wp-config:** `DISALLOW_FILE_EDIT`, `DISALLOW_UNFILTERED_HTML` [V: Editors otherwise hold `unfiltered_html`], `FORCE_SSL_ADMIN`.
- **Plugin header** `Update URI: false`, so a same-named wordpress.org plugin can never "update" ours.
- **Turned off:** XML-RPC; application passwords (unless needed); comments; author archives and unauthenticated `/wp/v2/users`.
- **Security headers** sent from PHP (`send_headers`), not `.htaccess`: `X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy`, `X-Frame-Options: SAMEORIGIN`, and CSP in report-only mode first.
- **Upload allowlist:** PDF, DOCX, XLSX, PPTX, JPG, PNG, WebP, AVIF. No SVG, HTML or archives. This fits AUP 3.5 and 3.1 (hosting §4.4).
- **Accounts:**
  - Two Editors and one or two Admins.
  - No registration; no member accounts (D8).
  - Volunteers get Enhance "Collaborator" access to the panel, not the reseller login.
- **The never-publish rules** (DESIGN §5.8) appear as help text on the Documents and Site facts screens. There is deliberately no field for the net roster, simplex or county channels, or personal contacts.

---

## 15. Build and development workflow

- **Local:** the Playground CLI `server` command with the theme and plugin mounted, as in the build brief.
  - The DB is rebuilt from `wordpress/dev/blueprint.json` on every start.
  - The blueprint:
    - sets the timezone to `America/Los_Angeles` and pretty permalinks
    - activates the theme and plugin
    - runs the seed importer
    - creates the pages from the page patterns and sets the front page
  - It has **no login step**, so screenshots are logged-out.
- **Headless checks:** the Playground `php` command runs PHP scripts inside a booted WordPress with no port [V]. Use it for the schedule-engine tests and the feature probe.
- **Dev-only helpers:** `wordpress/dev/mu-plugins` (for example `?dev_login=1`) is never deployed.
- **Deploy:**
  - Enhance has no Git deploy (hosting §4.1). Ship the two folders over SFTP/rsync, or as zip uploads by an admin.
  - Bump the theme and plugin versions for cache-busting, then purge the cache.
  - Keep the repo as the source of truth. Site Editor edits to templates should be copied back or reset (§2).
- **Before each WordPress major update:** start Playground with `--wp=<next version>`, then run the probe and the §8.6 click tests.
- **Suggested build order [I]:**
  1. theme.json tokens and fonts
  2. header, footer and sub-nav parts
  3. section and block styles
  4. `spokares-core` data model, admin screens and seed
  5. schedule engine and dynamic blocks
  6. page patterns and the locked pages
  7. TOC, navigation overlay
  8. caching hooks
  9. hardening
  10. §8.6 click tests and screenshots at 1440 and a true 390

---

## 16. Risks and open questions

| Risk / question | Effect | Mitigation / owner |
|---|---|---|
| PHP-only blocks are new (7.0) and use an `__unstable` global internally [S] | An editor preview could regress in a future release | Re-run the probe and §8.6 per major release; fallback is a plain-JS edit function (§7) |
| The content-only lock behaviour is derived from source [S] | If the root lock doesn't hold, editors could restructure pages | Click-test §8.6 before launch |
| Table cells not editable under content-only [V attributes] | Editors can't update About tables | §8.5 plan |
| The core nav breakpoint (600px) differs from B's (1020px) [V] | Header crowding from 600 to 1020px | §9 option A + testing |
| Two Factor not yet marked tested on 7.x [D] | 2FA plugin compatibility | Test in Playground; alternatives exist |
| Web server unknown (hosting §5.8 Q2) | Cache plugin choice | Read it from the Enhance panel first |
| D9 changes: WordPress becomes the event and rota source | The Net Manager's workflow changes | Confirm with AG7QP; offer the ICS feed |
| Editors cannot add paragraphs to locked pages | Small edits need an admin | "Allow layout changes" per page; runbook |
| Option B's inline devices (`.term`, `<abbr>`, pills) | Lost or brittle in the editor | Simplify, as in §12 |

---

## 17. Sources

**WordPress releases and dev notes:**
- 7.1 release: https://wordpress.org/news/2026/08/mary-lou/
- 7.1 field guide: https://make.wordpress.org/core/2026/08/05/wordpress-7-1-field-guide/
- 7.0 release: https://wordpress.org/news/2026/05/armstrong/
- 7.0 field guide: https://make.wordpress.org/core/2026/05/14/wordpress-7-0-field-guide/
- Release feed (7.1.2 on 2026-09-22): https://wordpress.org/news/category/releases/
- Core version API: https://api.wordpress.org/core/version-check/1.7/
- PHP-only block registration: https://make.wordpress.org/core/2026/03/03/php-only-block-registration/
- Pattern editing in 7.0: https://make.wordpress.org/core/2026/03/15/pattern-editing-in-wordpress-7-0/
- Pattern overrides for custom blocks: https://make.wordpress.org/core/2026/03/16/pattern-overrides-in-wp-7-0-support-for-custom-blocks/
- Navigation overlays: https://make.wordpress.org/core/2026/03/04/customisable-navigation-overlays-in-wordpress-7-0/
- Block visibility: https://make.wordpress.org/core/2026/03/15/block-visibility-in-wordpress-7-0/
- Per-block custom CSS: https://make.wordpress.org/core/2026/03/15/custom-css-for-individual-block-instances-in-wordpress-7-0/
- Iframed editor (7.0): https://make.wordpress.org/core/2026/02/24/iframed-editor-changes-in-wordpress-7-0/
- Iframed editor (7.1): https://make.wordpress.org/core/2026/08/03/iframed-editor-changes-in-wordpress-7-1/
- Responsive styles and viewports (7.1): https://make.wordpress.org/core/2026/08/05/responsive-block-styles-and-configurable-viewports-in-wordpress-7-1/
- SVG Icon API: https://make.wordpress.org/core/2026/07/24/registering-and-rendering-svg-icons-in-wordpress-7-1/
- Section styles (6.6): https://make.wordpress.org/core/2024/06/24/section-styles/
- Script modules (6.5): https://make.wordpress.org/core/2024/03/04/script-modules-in-6-5/

**Developer reference:**
- Block Bindings API: https://developer.wordpress.org/block-editor/reference-guides/block-api/block-bindings/
- `block.json` metadata: https://developer.wordpress.org/block-editor/reference-guides/block-api/block-metadata/
- Block templates and locking: https://developer.wordpress.org/block-editor/reference-guides/block-api/block-templates/
- Block Locking API: https://developer.wordpress.org/block-editor/how-to-guides/curating-the-editor-experience/block-locking/
- Curating the editor: https://developer.wordpress.org/block-editor/how-to-guides/curating-the-editor-experience/
- theme.json: https://developer.wordpress.org/themes/global-settings-and-styles/
- theme.json schema (version const 3): https://schemas.wp.org/trunk/theme.json and https://schemas.wp.org/wp/7.1/theme.json
- Patterns: https://developer.wordpress.org/themes/patterns/
- Template hierarchy: https://developer.wordpress.org/themes/templates/template-hierarchy/
- Interactivity API: https://developer.wordpress.org/block-editor/reference-guides/interactivity-api/
- Gutenberg TOC block (experimental): https://github.com/WordPress/gutenberg/blob/trunk/packages/block-library/src/table-of-contents/block.json

**LiteSpeed Cache:**
- Settings: https://docs.litespeedtech.com/lscache/lscwp/cache/
- API hooks (`litespeed_tag_add`, `litespeed_control_set_ttl`, `litespeed_purge`, `litespeed_purge_all`, `litespeed_purge_post`, `litespeed_purge_url`): https://docs.litespeedtech.com/lscache/lscwp/api/

**Plugin metadata** (WordPress.org plugin API, 2026-09-26): https://api.wordpress.org/plugins/info/1.2/?action=plugin_information&slug=two-factor (also litespeed-cache, redirection, fluent-smtp, wp-mail-smtp, advanced-custom-fields, secure-custom-fields, easy-table-of-contents)

**Local evidence:**
- `wordpress/research/probes/wp-feature-probe.php` and `probes/blk/toc/`: output reproduced in §1, §6 and §8. Run on 2026-09-26 against WordPress 7.1.2 / PHP 8.3.33.
- Source reads [S], all WordPress 7.1.2:
  - `wp-includes/js/dist/block-editor.js`: `isSectionBlockCandidate`, `EditSectionButton`, `getTemplateLock`, `canLockBlockType`
  - `wp-includes/js/dist/block-library.js`: autoRegister → `useServerSideRender`
  - `wp-includes/blocks.php`: `_wp_enqueue_auto_register_blocks`
  - `wp-includes/capabilities.php`: `edit_block_binding`
  - `wp-admin/edit-form-blocks.php`: `templateLock` only set from the post type when a template exists

**Project files:**
- `research/decisions.md` (D5, D8-D11, D15)
- `research/hosting/verpex-enhance.md` (§1, §4, §5)
- `design/round3/option-b-lines-down/DESIGN.md`, `assets/site.css`, `assets/site.js`, `assets/data.js`
- `design/round3/_shared/placement-map.md`, `copy-about.md`
- `design/round3/REVIEW.md`
