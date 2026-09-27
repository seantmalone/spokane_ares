# Option B "Carry the Message" as a WordPress block theme: component mapping

- **Prepared:** 2026-09-26. Lens: component mapping (every section, widget and device on the six round-3 Option B pages, and how each should exist in WordPress).
- **Inputs read:** `design/round3/option-b-lines-down/` (all six pages, `assets/site.css`, `assets/site.js`, `assets/data.js`, `DESIGN.md`, `README.md`, `shots/`), `design/round3/_shared/placement-map.md`, `design/round3/REVIEW.md` (Option B section), `research/decisions.md` (D5, D8-D11, D15), `research/hosting/verpex-enhance.md`.
- **WordPress baseline:** the current release is **7.1.2** (security release, 2026-09-22); 7.1 shipped 2026-08-19 and 7.0 on 2026-05-20 [S1][S2]. This plan targets **"Requires at least: 7.0"** because it relies on three 7.0 features: PHP-only dynamic blocks, patterns editable in `contentOnly` mode by default, and the navigation-overlay template part [S4][S5][S6]. Core's minimum PHP is now 7.4 [S3]; Verpex offers 8.3/8.4.
- **Classification key** used throughout:
  - **(a)** theme.json setting or style
  - **(b)** template or template part
  - **(c)** block pattern (locked = `contentOnly`, so text and images are editable but not the structure)
  - **(d)** plain editable page content (core blocks)
  - **(e)** dynamic block rendered from structured data
  - **(f)** small front-end interactivity
  - **(g)** does not translate well; simplify

---

## 0. Recommendations in brief

1. **Ship two pieces:**
   - a block theme, `spokares`, for the look
   - one small custom plugin, `spokares-core`, for the data: post types, the settings screen, and the dynamic blocks

   With this split the data survives a theme change, and the plugin count stays at 1 of the ≤ 4 allowed (D5).
2. **Build every dynamic widget as a PHP-only dynamic block** (`'supports' => ['autoRegister' => true]` plus a `render_callback`, WordPress 7.0+). The editor shows a server-rendered preview and generates the settings controls itself. There is **no JavaScript build step** and no npm [S4].
3. **Make every visual device a theme style**, on core blocks, rather than a custom block:
   - section styles (night, storm, dawn, day and paper surfaces)
   - block style variations: red line, stops, steps, hops, stacked tables, cards and callouts

   Pages are built from **locked patterns**. In 7.0, `contentOnly` is the default for patterns, so editors change words and images and the layout holds [S5].
4. **Move every fact that changes into structured admin screens,** never into page text:
   - **Tuesday nets:** a table with a net-control column and the Winlink assignment
   - **Events:** a custom post type
   - **Documents:** a custom post type plus a category taxonomy
   - **Net and meeting settings:** one options screen

   The six pages hold only standing prose.
5. **Remove the static site's JavaScript.** Every `data.js` widget now renders on the server: rota, upcoming, next meeting, as-of, Winlink and the library rows. The front end keeps four small Interactivity API behaviours:
   - copy the radio settings
   - filter the library
   - highlight the About contents entry
   - the mobile menu, which core already provides [S7]
6. **Self-host the fonts** through `theme.json` `fontFace` with `file:./assets/fonts/…` [S8]:
   - Crimson Pro (roman and italic)
   - Schibsted Grotesk (roman)

   Both are OFL variable fonts [S13]. Drop the Google Fonts `<link>`.
7. **Simplify or rebuild:**
   - The **message-path SVG** becomes a no-attribute PHP block that prints the inline SVG. SVG uploads are off in core, and a CSS-state `<symbol>` scene is not editor material.
   - **`<dl>` definition lists** become locked group patterns or two-column tables.
   - **`data-label` table stacking** comes from a `render_block` filter.
   - **The 1020px menu collapse** needs a CSS override, because core Navigation collapses at a hard-coded 600px (§6).
8. **Lock editing by role:**
   - Editors get the **Editor** role. It lacks `edit_theme_options`, so it cannot open the Site Editor, Global Styles, templates, navigation or the Font Library [S9][S14].
   - Add the theme.json color and font locks, turn off the code editor (`codeEditingEnabled`) and set `DISALLOW_UNFILTERED_HTML`.
9. **Refresh cached pages at midnight.** Server-rendered "next" and "Happening now" states go stale behind a page cache, so purge it once a day just after midnight Pacific and whenever data is saved (§8).

---

## 1. Architecture at a glance

```
wp-content/themes/spokares/            (the look; no data)
  theme.json                           tokens, fonts, element + block styles, locks        (a)
  styles/*.json                        section styles: night, storm, dawn, day, paper      (a)
  assets/fonts/*.woff2                 Crimson Pro (roman+italic), Schibsted Grotesk       (a)
  assets/css/theme.css                 pieces theme.json can't express (red line, stops,
                                       steps, stacked tables, hero gradients, 1020px menu)
  parts/header.html, footer.html,
        members-subnav.html,
        navigation-overlay.html        chrome                                               (b)
  templates/front-page.html, page.html,
        page-longread.html (About),
        page-members.html, page-documents.html, page-exercises.html
        (hierarchy by slug; all share members-subnav), 404.html                             (b)
  patterns/*.php                       every page section, contentOnly                     (c)

wp-content/plugins/spokares-core/      (the data; survives a theme change)
  post types: spk_event, spk_document (+ taxonomy spk_doc_cat)                             (e)
  settings: "Net & meetings" (radio settings, net rules, meeting rules, exceptions),
            "Tuesday nets" (net control + Winlink assignment per Tuesday)                  (e)
  blocks/ (PHP-only, autoRegister): net-settings, net-bar, rota, upcoming, next-up,
          later-season, winlink-assignments, public-service, past-exercises,
          meetings, next-meeting, asof, doc-library, doc-search, quick-links,
          toc, message-path                                                                (e)
  view modules (Interactivity API, plain ES modules, no build): copy, library, toc        (f)
  filters: stacked tables, external-link marker, aria-current, list semantics             (g)
  optional: /calendar.ics feed (members subscribe; replaces the in-browser .ics)
```

**Why the data lives in a plugin and not in the theme:** WordPress guidance puts post types in plugins so content survives a theme change. It also keeps the theme a pure design layer that we can review and update separately. **INFERENCE:** a one-plugin core, plus at most SMTP, redirects and cache, fits D5's "≤ 4 plugins".

---

## 2. Design tokens → theme.json (a)

### 2.1 Palette

Map every `site.css` token to a `settings.color.palette` entry (`--wp--preset--color--{slug}`). Set `defaultPalette: false`, `custom: false`, `customGradient: false` and `defaultGradients: false` so editors can pick only brand colours, and in locked patterns they see no colour controls at all [S10].

| Slug | Hex | Source token | Role |
|---|---|---|---|
| `night` | #0E1A33 | `--night` / `--ink` | Header, hero, footer, Home What we do, How it works opener; text on light |
| `slate-2` | #1F2C47 | `--slate-2` | Storm gradient |
| `slate` | #33415C | `--slate` | Dark panels |
| `peach` | #F3C29B | `--peach` | Dawn gradient and 4px top rules **only** |
| `day` | #FFFFFF | `--day` | Light sections, cards |
| `paper` | #F6F7FA | `--paper` | Members workspace, page background |
| `mist` / `mist-2` | #C5CEDF / #A9B4C9 | | Body and secondary text on night |
| `ink-2` / `off` | #3C4863 / #56627C | | Secondary text and captions on light |
| `rule` | #D9DEE8 | | Hairlines |
| `red` / `red-hover` / `red-ink` | #D0202A / #B51C25 / #B01A23 | | Message line, primary pill, links on white |
| `amber` / `amber-wash` / `amber-ink` | #FFC857 / #FFF4D6 / #6B4A00 | | Hop markers, "next" rows, focus on dark |

The contrast ratios in DESIGN.md §3 carry over unchanged.

**Gradients** (`settings.color.gradients`, theme-only):
- `dawn-sky`: night → slate → #5E5F80 → #A68A9A → #DDAE9E → peach
- `dawn-sky-from-slate`
- `dawn`: peach → #F8D6BD → #FFF3E9 → white
- `storm`: night 0-35% → slate-2 78% → slate

The hero's two overlay gradients are not presets. They live in `theme.css` on the hero style (§4.1).

### 2.2 Typography

**Font families.** Self-hosted through `fontFace`. Theme-bundled `src: ["file:./assets/fonts/…"]` is supported, as is a weight range for variable fonts ("300 800") [S8].

| Slug | Family | Files (subset latin + latin-ext → woff2) | Weights used |
|---|---|---|---|
| `serif` | "Crimson Pro", Georgia, serif | `CrimsonPro[wght].woff2`, `CrimsonPro-Italic[wght].woff2` (google/fonts ofl/crimsonpro; wght 200-900; OFL) [S13] | 400, 500, 600; italic 400/500 |
| `sans` | "Schibsted Grotesk", system-ui, sans-serif | `SchibstedGrotesk[wght].woff2` (ofl/schibstedgrotesk; wght 400-900; OFL) [S13] | 400, 500, 600, 700. No sans italic is used, so the italic file is skipped |

- Ship `OFL.txt` with each family in `assets/fonts/`.
- `font-display: swap`.
- Preload the two roman files. The hero H1 and body text use them on first paint.

**Font sizes** (`settings.typography.fontSizes`, `fluid: true`):
- Set `customFontSize: false` so editors cannot type sizes [S8].
- Use each `clamp()` range as the preset's `fluid.min`/`fluid.max`. WordPress computes its own clamp slope, so sizes between the ends will differ by a pixel or two, which is acceptable.

| Slug | Size (min → max) | Replaces |
|---|---|---|
| `display` | 2.75rem → 5.5rem | `.display`, the Home H1 only |
| `h1` | 2.5rem → 4.25rem | `h1` |
| `h2` | 2rem → 3.25rem | `h2` (members H2s use `m-h2`: 1.6rem → 2rem) |
| `h3` | 1.45rem → 1.875rem | `h3`, `.stop__label`, `.steps__title` |
| `narr` | 1.35rem → 1.75rem | `.narr`, `.hero__lede`, `.how-open__line` |
| `lede` | 1.2rem → 1.5rem | `.lede`, `.how-lede` |
| `prose` | 1.25rem (20px) | About body (`.prose`) |
| `body` | 1.0625rem (17px; 16px under 640) | body |
| `small` | 0.875rem | `.small`, captions |

**Element styles.** `styles.elements`:
- `h1`-`h3`: serif, weight 500, the preset size, line height 1.04 / 1.08 / 1.15, `letter-spacing` −.014em / −.01em / −.005em.
- `h4`: sans 600, 19px.
- body: sans 400, 17px/1.6.
- `link`: red-ink, 1.5px underline at .2em offset; on hover the underline grows to 3px.
- `caption`: small, `ink-2`.

Figures: keep `.tnum` as a paragraph style. Big display values (8:00 PM, 147.300) stay proportional lining figures, as DESIGN.md §3 notes.

### 2.3 Spacing, layout, radius, focus

**Spacing presets** (`settings.spacing.spacingSizes`), from the 8px base:

| Slug | Size |
|---|---|
| `10` | 8px |
| `20` | 16px |
| `30` | 24px |
| `40` | 40px |
| `50` | 64px |
| `60` | 96px (the `.section` padding) |
| `70` | 128px |

- Set `customSpacingSize: false`.

**Layout:**
- `wideSize: 1240px`, which is `.wrap`.
- `contentSize: 760px`, the How it works body column and roughly About's 64ch.
- Root padding of 32px, dropping to 16px under 640px, through `styles.spacing.padding` with `useRootPaddingAwareAlignments: true`. This keeps the 16px phone gutter the brief requires.

**Radius** as `settings.custom.radius`:

| Name | Radius |
|---|---|
| `panel` | 16px |
| `card` | 12px |
| `paper` | 4px |
| `pill` | 999px |
| `sm` | 8px |

The theme exposes these as `--wp--custom--radius--*` [S10]. Editors never set radius.

**Other custom values:**
- `settings.custom.mastH` holds `72px`, dropping to 64px under 900px in CSS. It feeds `[id] { scroll-margin-top: calc(var(--wp--custom--mast-h) + 24px) }`.
- **Focus:** a 3px outline with a 2px gap. It is amber on night surfaces and ink on light ones, set in `theme.css` against the section-style classes. WordPress 7.1 also adds `:focus-visible` states in theme.json for buttons [S2][S15], but the site needs it on every link.

**Buttons.** `styles.blocks.core/button`: pill shape, sans 600 16px, min-height 44px, padding 10px 22px, red fill.

| Style | Look |
|---|---|
| default | red fill |
| `ghost` | outline (core's "outline" renamed) |
| `light` | white |
| `ink` | ink fill |

- Sizes `lg` and `sm` are variations too.
- 7.1 adds hover, focus and active states for Button blocks in theme.json and in the editor, which replaces `.btn--red:hover` [S2].

### 2.4 Section styles (the tone system) (a)

B's "surface sets tone" system (`.bg-night`, `.bg-dawn`…, DESIGN.md §3 "Surfaces set tone") maps onto **section styles**, which are block style variations for Group that also restyle nested elements and blocks. Define them in `styles/*.json` with a `blockTypes` array, or under `styles.blocks.core/group.variations` [S10].

| Section style (Group) | Background | Nested text / links / headings | Focus |
|---|---|---|---|
| `night` | night | white text, mist muted, white links with a mist underline, amber on hover | amber, night gap |
| `storm` | the storm gradient | as night | amber |
| `slate` | slate | as night | amber |
| `dawn` | the dawn gradient | ink text, red-ink links | ink, white gap |
| `day` | white | as dawn | ink |
| `paper` | paper | as dawn | ink |

**The tone variables stay in CSS.** The tone *variables* (`--fg`, `--muted`, `--link`, `--line`, `--focus`) are what let tags, `.hop`, tables and buttons adapt to any surface. Section styles cannot declare arbitrary CSS variables, so `theme.css` keeps a short block keyed on the variation classes (`.is-style-night, .is-style-storm { --fg: #fff; … }`). **INFERENCE:** that is about 30 lines of the current §2, reused as is.

### 2.5 Block style variations (a)

Register with `register_block_style()` in PHP, or in theme.json. Inside locked patterns editors never see the style picker; in free content they see only the variations marked "free" below.

| Block | Variation | Replaces | Free for editors? |
|---|---|---|---|
| `core/separator` | `redline` | `.redline` (3px red, amber node, amber ring) | yes |
| `core/group` | `dawn-sky` (+ `-short`, `-from-slate`) | `.dawn-sky` strip | no (in patterns) |
| `core/group` | `card`, `card-dawn`, `card-red`, `panel` | `.card*`, `.panel` | `card` yes |
| `core/group` | `callout-plain`, `callout-legal` | `.callout--*` | yes |
| `core/group` | `stops` / `stop` | Home What we do (§4.1) | no |
| `core/group` | `steps` / `step` / `step-optional` | Home Join in four steps | no |
| `core/group` | `levels` / `level` | Readiness levels | no |
| `core/group` | `defs` (+ row `def`) | About `.defs`, How it works `.deflist--2` | no |
| `core/group` | `chains` | About structure figure | no |
| `core/list` (ordered) | `hops` | `.hops` (story, 28px amber numerals) | yes |
| `core/list` (ordered) | `seq` | `.seq` (net run-sheet, activation) and `.jsteps` | yes |
| `core/list` | `lines` | `.lines` (hairline one-liners) | yes |
| `core/list` | `years` | About timeline `.years` | no |
| `core/table` | `stack` | `.table--stack` (labelled cards ≤ 640px) | yes |
| `core/table` | `dense`, `plain` | `.table--dense`, the headerless doc tables | yes |
| `core/paragraph` | `lede`, `narr`, `note`, `small`, `cue` (amber wash), `real` (red left rule), `action` (textlink) | the same classes | `lede`, `note` and `action` yes |
| `core/heading` | `m-head` | members H2 with the 3px red underline | no |
| `core/quote` / `core/pullquote` | default restyled | `.pullquote` (Crimson italic, red left rule) | yes |
| `core/cover` | `night-hero` | `.hero` | no |

**Number markers use CSS counters,** not typed numerals:
- `ol.is-style-hops > li::before { content: counter(list-item) }`, styled as the amber `.hop`.
- The Home join steps start at 0: core List's `start` attribute holds it, or the steps group sets `counter-reset: step -1`.

Editors then renumber by reordering, never by typing numbers.

---

## 3. Chrome: templates and parts (b)

| Option B piece | WordPress home | Notes |
|---|---|---|
| **Masthead** (`header.site-header`): seal + "Spokane County / ARES-ACS", 4 links, red "Join the team"; sticky; night at .92 with `backdrop-filter` | `parts/header.html`: a Group with `position: sticky` (core Group supports sticky position [S16]) in section style `night`. Inside it: Site Logo (the seal, 46px, white disc), a Site Title / tagline lockup, a Navigation block with **4 fixed links**, and a Buttons block holding "Join the team" | **Links are hard-coded in the part, with no `wp_navigation` post,** so nobody can edit the menu by accident. **Active state:** core marks `aria-current="page"` only when a link carries that page's `id` and it matches the queried page [S17]. Hard-coded links in a part have no id, and "For Members" must stay active on all three members pages. So one `render_block_core/navigation-link` filter compares the link path with the request path (**prefix match for `/members/`**). The amber underline is CSS on `[aria-current]`, as today |
| **Mobile menu** ("Menu" pill, dropdown panel, Esc to close) | Core Navigation overlay (Interactivity API [S7]), plus the 7.0 `navigation-overlay` template part: `parts/navigation-overlay.html` with the Navigation Overlay Close block, section style `night`, 4 links at 18px and the Join button [S6] | **Two gaps.** (1) Core collapses at **600px** (`$break-small`, hard-coded in `navigation/style.scss`; `overlayMenu` is only `mobile`/`always`/`never`) [S18]. B collapses at **1020px**, because the brand, 4 links and Join do not fit between 600 and 1020. Fix: about 12 lines of `theme.css` that show the toggle and hide the inline list below 1020px (**INFERENCE:** works, but depends on core class names; re-test on each major release). (2) 7.0 overlays are **always full-screen** [S6], while B drops a panel under the bar. Accept the full-screen night overlay: it stays on the night surface, and a full-screen menu is familiar on phones |
| **Skip link** | Core adds it automatically for block themes (`wp_enqueue_block_template_skip_link`) [S19] | Needs a `<main>` in every template (`tagName: main`) |
| **Members sub-nav** (white row, 5 items, amber underline on the current one, wraps to 2 rows at 390) | `parts/members-subnav.html`: a Navigation block with **overlay `never`** (it must always show and wrap), links hard-coded (This week, Exercises & events, Documents & forms, Task books → `/about/#training-path`, groups.io, marked external) | Only the three members templates use this part: `page-members.html`, `page-documents.html` and `page-exercises.html`. The template hierarchy picks each one by page slug, so editors never choose a template. The same `aria-current` filter handles it |
| **Footer** (seal; 6 links; safety line; © + mockup line) | `parts/footer.html` in section style `night`: Site Logo, a Navigation with fixed links, and 2 paragraphs | "Privacy (coming)" stays plain text until the page exists. **Remove the "Mockup — content to be verified" line at launch.** The safety line keeps its SCEM review flag (a verify class; see §7) |
| **Page skeletons** | `front-page.html`: header, then `<main>` holding post-content, then footer. Home's sections are patterns inside the Home page content (editable text). `page.html`: How it works and any future page. `page-longread.html`: About (dawn head + TOC rail + prose column; §4.3). `page-members.html`: the paper workspace + sub-nav | Page-specific `<style>` blocks (How it works 127 lines, About 118, members 47-67) become `theme.css` sections, loaded per block with `wp_enqueue_block_style` where possible so pages load only what they use |
| **Deep-link landing** (`scroll-margin-top`, `hashLanding()`) | CSS `[id] { scroll-margin-top: … }` in `theme.css` | `hashLanding()` existed because `data.js` widgets and web fonts changed heights after load. Server rendering removes the first cause, and self-hosted, preloaded fonts reduce the second. **Drop the script;** re-test the 8 cold-load deep links in DESIGN.md §10 and bring back a 10-line module only if a landing is off |
| **Favicon** | Site Icon (Settings › General) using the seal | Editors cannot change it (it needs `manage_options`) |

---

## 4. Page-by-page component inventory

The **Editor experience** column says what Frank (or any Editor) actually does. "Locked" means a `contentOnly` pattern: text and images are editable, structure and styles are not [S5].

### 4.1 Home (`/`)

| # | Component (Option B) | Class | WordPress implementation | Editor experience | Keep the feel by… |
|---|---|---|---|---|---|
| 1 | **Hero**: sunset photo, night gradients, display H1, Crimson lede, Join (red) + See how it works (ghost), agency line | c | Pattern `hero`: **Cover** (image `1920x1200sunset.jpg`, focal point 50%/80%) in style `night-hero`, holding a Heading (H1, size `display`), a Paragraph (`narr`), Buttons (default + `ghost`, `lg`) and a Paragraph (small, mist) with the agency link | Change the words, swap the photo (the focal point is kept). Can't add blocks or recolour | `night-hero` adds the second gradient layer (`::before`, both linear gradients from `site.css` §11) and `saturate(.9)`; min-height `clamp(520px, 100svh − 232px, 640px)` in CSS. Cover supports only one overlay, so the second must come from CSS |
| 2 | **What we do**: 4 "stops" on one horizontal red line with amber dots and an end ring; vertical under 900px | c + g | Pattern `what-we-do`: Group `night`, then an H2, then Group `stops`, which holds 4 × Group `stop` (Paragraph with the label in bold serif h3 size, and a Paragraph with the text) | Edit the 4 labels and lines | The line, dots and ring are pseudo-elements on `.is-style-stops`, as `site.css` §20 has them today. A small `render_block` filter adds `role="list"`/`"listitem"` so screen readers still hear a list of 4 (Group cannot be `ul`) |
| 3 | **Dawn hinge** (`.dawn-sky`, decorative) | c | A Group in style `dawn-sky` (fixed height, gradient preset), with no content, inside the page's section patterns | Not selectable (no content role) | Same gradient stops |
| 4 | **Come visit**: dawn surface; white peach-topped card "From home… 8:00 PM, 147.300 MHz"; "In person at SCEM, 1121 W Gardner Ave"; 2 meetings, each with "Next: Sat, Oct 10" | c + e | Pattern `visit`: Group `dawn`, then an H2, then Columns 5/7. The left column is Group `card-dawn` with a paragraph (lede size). The right column is a paragraph plus the **`spokares/meetings` block** (list of meetings, each "Next: …" computed from the meeting rules) | Edit the "From home" sentence and the address line. **Meeting names, times and next dates come from the "Net & meetings" screen** (§5) | Same card and list styles. `data-verify` becomes the verify class on the address and meeting lines until confirmed |
| 5 | **Join in four steps**: H2 left; `ol.steps` right with 56px amber numerals 0-3 on a vertical red line, the segment into the optional step dashed, the optional step as a ring, tag "Optional", textlinks, and the "What ACS asks" note | c + g | Pattern `join-steps`: Group `day`, then Columns 4/8, then Group `steps` holding 4 × Group `step`; the last one is `step-optional` (an H3, a Paragraph, a Paragraph `action` with the link, and an optional "Optional" tag paragraph and note) | Edit step titles, lines and links | CSS counter from 0, red rail, dashed segment and ring marker, as `site.css` §20. The `role="list"` filter again, plus a screen-reader "Step N:" prefix added by the same filter. The id `#join` (alias `#what-it-takes`) lives in the pattern's HTML anchor |

**Home count:** 4 locked patterns and 1 dynamic block. Nothing on Home changes weekly except "Next:", which is automatic.

### 4.2 How it works (`/how-it-works/`)

| # | Component | Class | WordPress implementation | Editor experience | Notes |
|---|---|---|---|---|---|
| 1 | **Opener** on storm: H1, "It's 2 AM…" line, scenario note, 4-hop `ol.hops`, the **message-path diagram** (right), "Radio still works. Because people practice." | c + e + g | Pattern `how-opener`: Group `storm`, then Columns 5/7. Left: H1, a Paragraph (`narr`), a Paragraph (`small`, the scenario note), and an ordered List `hops`. Right: the **`spokares/message-path` block** (a no-attribute PHP block that prints the inline SVG with `<title>`/`<desc>`; in-SVG "A scenario" pill), then a Paragraph (italic serif) | The story text and the 4 hops can be edited. The diagram can't; it is a fixed illustration | See §7 for why the diagram is a PHP block. **Phones:** hide the diagram under 640px as today, either with the 7.0 **block visibility** control (hidden by CSS, still in the DOM) [S20] or with the existing CSS. Apply REVIEW fixes here: drop "147.300 MHz" from the diagram label (it duplicates the settings box), and add a small "Radio settings" jump link to `#nets` at the top |
| 2 | **Dawn hinge** | c | Group `dawn-sky` (short) | none | |
| 3 | **The weekly net** (`#nets`): H2 + one sentence (left rail); **settings box** (white, peach top rule, "Every Tuesday, 8:00 PM", W7GBU row and alternate row, each with a Copy button); "What happens, in order" 6-step `.seq` with the visitor cue | c + e + f | Pattern `how-chapter` (a 4/8 rail: heading column + body column), holding the **`spokares/net-settings` block** (rows from the settings screen; each row's Copy button uses the `copy` view module) and an ordered List `seq`. The visitor cue is a Paragraph `cue` inside item 4. **INFERENCE:** a list item cannot hold a paragraph block, so the cue becomes the next line in the same list item | Edit the run-sheet lines. **Frequencies, offsets and tones come only from the settings screen**, so they cannot drift between this box and the hub | `#weekly-net` is emitted by the block |
| 4 | **Other nets** (`#other-nets`): 3 one-liners (Winlink nights, Fifth Tuesdays, GMRS) | d | An H3 and a List `lines` | Edit freely | These describe rules; the rules themselves live in settings (§5). **INFERENCE:** keeping the prose static is simpler than generating it, and the rules change rarely |
| 5 | **When the county calls** (`#activation`): redline, H2, lede with textlink, "real" sentence (red left rule), 7-step `.seq` | c/d | Pattern `how-chapter`, containing a Separator `redline`, Paragraph `lede`, Paragraph `real` and an ordered List `seq` | Edit freely inside the rail | The ids `#who-calls-us` and `#real-world` are set on the paragraphs in the pattern |
| 6 | **Readiness levels** (`#alert-levels`): 4 cards with hop numeral, name + colour word, "means", "do" | c | Pattern `levels`: Group `levels` holding 4 × Group `level` | Edit words only | Neutral, with no current level (a never-publish rule). 2×2 on phones as today |
| 7 | **When no one has called yet** (`#standing-order-1b`) | d | An H3, a Paragraph and a Paragraph `action` (external link) | Edit freely | The external-link filter adds the `.ext` icon and "(opens site)" |
| 8 | **How a message moves**, **Forms and logs** (`#ics-213`, `#logs`), **Winlink** | d | A redline, H2/H3s, Paragraphs and a List `lines` inside the chapter rail pattern | Edit freely | Links to document rows use `/members/documents/#ics-213` (§4.5) |
| 9 | **Training and exercises** (`#exercises`): 4 undated items in 2 columns (`.deflist--2`) | c | Group `defs` (2-up) holding 4 × Group `def` (bold label + text) | Edit words | No dates here; dates live on Exercises (placement map) |
| 10 | **Roles members take** (`#roles`): an 8-row table, stacking on phones | d | Core **Table** in style `stack`, with a head row. Pattern markup can keep `<th scope="row">` in the first column (core table cells carry `tag` and `scope`) [S16] | Edit cells, add rows (new rows get `td`) | `data-label` is added at render (§7) |
| 11 | **Close**: "Ready?" + Join the team (the page's one red button) | c | Pattern `close-cta` | Edit the words | |

### 4.3 About ARES & ACS (`/about/`)

**Template.** `page-longread.html` provides:
- a short `dawn-sky` strip
- the dawn page head: H1 = post title, the lede from the excerpt (or a Paragraph block), and "Last reviewed…"
- a 3-column long-read grid: TOC rail (232/220px), then post-content (64ch), then the empty sidenote rail at 1200px and up

| # | Component | Class | WordPress implementation | Editor experience | Notes |
|---|---|---|---|---|---|
| 1 | **Page head** + "Last reviewed Sep 26, 2026. Page owner: Emergency Coordinator." | b + e | Template. "Last reviewed" is a **`spokares/reviewed` meta field**, shown only when set (2 judges: leave it blank until the owner reviews) | The editor sets a "Reviewed on" date in the page sidebar | Fixes the REVIEW item "a date the page asserts without a source" |
| 2 | **Contents**: sticky side list with the red rail growing to the current chapter; `details.toc-mobile` closed on phones; the "On this page" floating button | e + f | **`spokares/toc` block** in the template's rail. It reads the page's H2 blocks that have anchors (`parse_blocks`) and prints both the side `<nav>` and the mobile `<details>` (core Details markup), so **the TOC can never drift from the headings**. The `toc` view module (Interactivity API, IntersectionObserver) sets the active link, the rail height and the floating button | Nothing to maintain: add or rename an H2 and the TOC follows | Without JS: plain jump links and a closed `<details>` |
| 3 | **ARES and ACS**: 4 definitions (`dl.defs`, term with a small expansion) + the "What we are not" callout | c | Pattern `defs` (Group `defs` holding 4 × Group `def`: Paragraph term, Paragraph small, Paragraph definition) + Group `callout-plain` with a List | Edit words | Keep the retired ids as aliases (`#what-is-ares`, `#two-hats`…): empty anchors in the pattern, or one redirect snippet in `toc.js`. **INFERENCE:** anchors in a Group are simplest |
| 4 | **Legal basis**: a 4-row table (external links) + `callout-legal` | d + c | Table `stack` + Group `callout-legal` | Edit | The whole section stays flagged for SCEM review |
| 5 | **Who we serve**, **Agreements**, **How we work with agencies** (ghost button `mailto:ec@`) | d | Lists, H3s, Buttons (`ghost`) | Edit | Role address only |
| 6 | **Organization and leadership**: the **structure figure** (two chains, drawn merge into "Emergency Coordinator + AECs, one team") + 2 call-sign tables | c + g | Pattern `chains`: Columns (2) of List `chain`, then Group `chains-team`. The merge curve is a CSS background SVG (data URI) on `chains-team::before`, and the amber node is a pseudo-element. The tables are Table `plain` | Edit chain labels and table rows | See §7. Call signs only, never names |
| 7 | **Joining**: bullets; ARES path (3 steps, one red button); ACS path (6 steps) | c/d | Ordered List `seq` (replaces `.jsteps`); the red button is a Buttons block between list 1 and 2 | Edit | The "ARES application" button links to the document row or file |
| 8 | **Training and task books**: task-book table (level column with hop numeral), notes, link rows; ARRL levels; Equipment | d | Table `stack` + `dense`. The level numeral comes from a CSS counter on the first column; the red "bleed" into the rail is CSS on the `.is-style-stack` table inside `.longread` | Edit cells | |
| 9 | **Getting licensed**: exam table + bullets | d | Table `stack`, Lists | Edit | REVIEW suggests trimming the exam table; a content decision |
| 10 | **History and W7GBU**: story, `pullquote`, timeline `ol.years` | d/c | Paragraph, Pullquote, List `years` | Edit | |
| 11 | **Contact**: 3-row role-address table | d | Table `plain` | Edit | Addresses are proposals (D11) until live |
| 12 | Back-to-top links, drop cap, sidenotes | g | **Not used in round 3** (grep: 0 occurrences). Leave them out | | |

### 4.4 For Members hub (`/members/`)

**Template.** `page-members.html` (Documents and Exercises use sibling templates of the same shape): the members sub-nav, then a paper workspace, then post-content. The hub content is one locked pattern made mostly of dynamic blocks; there is almost no free text.

| # | Component | Class | WordPress implementation | Editor experience | Notes |
|---|---|---|---|---|---|
| 1 | H1 "For members" + "Schedule as of Sat, Sep 26, 2026." | e | H1 + **`spokares/asof`** (prints today's date in the site time zone) | none | Needs the daily cache purge (§8) |
| 2 | **Document search** (label, field, Search) + live top-5 preview | e + f | **`spokares/doc-search`**: a GET form to `/members/documents/?q=` | none | **Simplify:** drop the live preview (`form[data-doc-preview]`). The library page filters instantly anyway, so this saves a second script. **INFERENCE:** one extra page load is acceptable |
| 3 | **This week**: the next 2 exercises with date slabs and "Happening now"; "What to bring" link; "Next meeting: Sat, Oct 10, 9:00 AM, Second Saturday Workshop" | e | **`spokares/upcoming`** (attributes: `types` = exercise,training; `limit` = 2; `days` = 60; `link`) + **`spokares/next-meeting`** (attribute `meeting`) | Add or edit **Events** in wp-admin; the hub updates itself | Server logic ports `SPOKARES.fn.upcoming()`: in progress = start < today ≤ end; items drop off after their end date |
| 4 | **Tuesday net**: night `netbar` "8:00 PM \| W7GBU 147.300 MHz, +600 kHz, 100 Hz" + Copy; rota table (Tuesday / Net control / Note), the next row amber, "Open" tags; "Open slot? Tell the Net Manager (AG7QP)…" | e + f | **`spokares/net-bar`** (from settings, with a Copy button using the `copy` module) + **`spokares/rota`** (attribute `weeks` = 5). The Note column is **computed** from the net rules (Simplex on 5th Tuesdays, Winlink night on the 2nd and 4th, GMRS 7:30 PM on the 3rd), as `fn.netOn()` does. The open-slot line is block output | **Monthly edit: the "Tuesday nets" screen** (§5): type a call sign next to each date; leave it blank for "Open" | REVIEW fixes: link each "Open" tag to the open-slot line (`#open-slot`); the Simplex note links to the net-script row; on phones put the net **first** (CSS `order` in the hub grid) |
| 5 | **Most used**: 4 icon tiles (Net script, ICS-213, ICS-309, ACS Task Book) | e | **`spokares/quick-links`**: documents flagged "Most used", in admin order, with an icon per document category (inline SVG in the plugin; §7) | Tick "Most used" on a document | SHARED REVIEW fixes: link each tile to the **file** (not the row), and don't tile a "Soon" item (ICS-309 today) |

### 4.5 Documents & forms (`/members/documents/`)

| # | Component | Class | WordPress implementation | Editor experience | Notes |
|---|---|---|---|---|---|
| 1 | **Library rail**: search field + Search; category chips (All, Most used, 7 categories), sticky on wide screens | e + f | **`spokares/doc-library`** prints the rail and the groups. The chips are links to `#net-ops`… (they work without JS) | Categories are `spk_doc_cat` terms, with a term-order field | The chip counts come from the data |
| 2 | **Groups**: 7 `.doc-group` sections (H2 on the red rule), each a headerless table: title link (external marker) + ≤ 8-word note + "How to write one" sub-link / format badge + version / source; "Soon" tag when there is no link; row ids = document ids | e | Same block. One row per `spk_document`: title, category, format, version, note, link **or** uploaded file, source label, how-to link, sub-links (FEMA course list), "Most used", search tags, verify | **Documents › Add New**: fill a short form; upload a PDF or paste a link | **The row id is the anchor** that deep links use (`#ics-213`, `#net-scripts`…). Store it as a separate, rarely edited "Anchor id" field (defaulting to the slug), so a title edit never breaks the links from How it works, About and the hub |
| 3 | **Filtering**: live text match + category, `?q=`/`?cat=` in the URL, count "Showing all 37 documents." (aria-live), empty state + Clear | f | The `library` view module (Interactivity API store; `data-wp-bind--hidden` on rows and groups; the count in `data-wp-text`). **No-JS fallback:** the GET form reloads with `?q=`, and the block filters on the server with the same matching rules as `fn.documents()` | none | One behaviour, 2 paths, same results |
| 4 | **Not posted here** (`#not-here`), 2 sentences | d | H2 + Paragraph after the block | Edit | Never-publish list stays in the plugin README and the editor runbook |

**Uploads vs links.** Most of the 37 rows are external links today; `hostedHere` items move to this site at launch (D7/D10). Uploaded files go through the Media Library, so each file stays reachable at its own URL. **INFERENCE:** that satisfies AUP 3.5 ("visible and accessible by visiting that domain"). Verpex's written OK is still needed (D10).

### 4.6 Exercises & events (`/members/exercises/`)

| # | Component | Class | WordPress implementation | Editor experience | Notes |
|---|---|---|---|---|---|
| 1 | **Next up** (`#next-up`): "Bring to every exercise…" line; 2 cards (the next event red-topped, "Happening now", extra form link, **red** "Exercise details on groups.io" button; the SET card with its 5-task list and ARRL link); "After any exercise…" line | d + e | Paragraph (Bring), then **`spokares/next-up`** (`limit` = 2, `types` = exercise), then Paragraph (After). Each card = event title, dates, "Happening now", the event's **body content** (paragraphs and a list: the tasks), the extra form (a related document), and the details button (URL field). The first card gets `card-red` | **Events › Add New**: title, dates, type, an optional details link and extra form; write tasks in the body (Paragraph/List only) | Card ids = the event anchor id (`#wsdot-2026`) |
| 2 | **Later this season** (`#upcoming`): 6 rows "Thu, Oct 15, 10:15 AM \| Great ShakeOut: send a DYFI report…" | e | **`spokares/later-season`**: events after the Next up ones, of types exercise, training and on-air; each row's text is the event's **summary** field (short rich text with links) | Same Events screen | 12-hour times, as in `fmtTime()` |
| 3 | **Winlink assignments** (`#winlink-assignments`): how-to line + a Date / Assignment / Form table, the next row amber | e | **`spokares/winlink-assignments`** (from the "Tuesday nets" rows that carry an assignment) + the how-to text from settings | Type the assignment and form on the same Tuesday row as net control | One screen for everything that happens on a Tuesday |
| 4 | **Public-service events** (`#public-service`): Event / When / Volunteer through (call sign) + tips link | e + d | **`spokares/public-service`**: events of type public-service. The **"When" can be a date or text** ("Date not posted", "As requested"), so the event has an optional date plus a "date text" field. A Paragraph for the tips link | Events screen | Call signs only |
| 5 | **Past exercises** (`#past`): 8 rows, month + year (or year only) | e | **`spokares/past-exercises`**: events with "Show in past exercises" ticked, after their end date, newest first. A **date precision** field (year / month / day) prints "2025" or "Oct 2022" without inventing a day | Tick one box when adding a notable exercise; it moves from Next up to Past on its own | The 8 historic rows are imported with their known precision only |

---

## 5. The structured data behind the dynamic blocks (e)

This is the WordPress replacement for `data.js`. The shapes follow `data.js` so the copy and the placement map still apply. The UI details belong to the admin-setup lens; here are the entities and which blocks read them.

| Entity | Storage | Fields (from `data.js`) | Edited how often, by whom | Read by |
|---|---|---|---|---|
| **Radio settings** | option `spokares_radio` | primary {call, freq, offset, tone}, alternate {freq, offset, tone, verify}, NWS {call, freq}, copy text | Rarely; admin | net-settings, net-bar |
| **Net rules** | option `spokares_nets` | weekly (weekday, start), winlink-nights (nth 2,4), simplex (nth 5, where text), GMRS (nth 3, 19:30) | Rarely | rota (Note column), winlink rows |
| **Tuesday nets** | option `spokares_tuesdays` (keyed by date) **or** a hidden `spk_net` post type (one post per Tuesday) | date, net-control call sign (blank = Open), Winlink task, Winlink form, note override | **Monthly: the Net Manager** | rota, winlink-assignments |
| **Meetings** | option `spokares_meetings` | name, rule (nth weekday), start/end or display time ("Evening"), skip months, **exceptions (cancelled or moved dates)**, verify | Rarely; plus an exception now and then | meetings (Home), next-meeting (hub) |
| **Events** | post type `spk_event` (not publicly queryable, so there are no single event pages) | title, type (exercise / training / on-air / public-service), start, end, time, end time, all-day, date text, date precision, summary (short rich text), body (tasks), details URL, extra form (→ document), volunteer-through call sign, show in past, anchor id, verify | **Several times a month** | upcoming, next-up, later-season, public-service, past-exercises, optional ICS feed |
| **Documents** | post type `spk_document` + taxonomy `spk_doc_cat` | title, category, format, version, note (≤ 8 words), link or file, source label, how-to link, sub-links, most used, tags, anchor id, verify | **A few times a year**; uploads | doc-library, doc-search, quick-links |
| **Leadership, contacts, legal table, exam table** | page content (d) | n/a | Rarely | About |

**Decisions this creates for the plan:**
- **D9 changes.** D9 recommended the groups.io calendar feed as the single source. The owner now wants **WordPress admin as the place events and the rota are kept**. This design makes WordPress the source of truth, and the plugin can publish `/calendar.ics` for members to subscribe to, replacing the round-2 in-browser `.ics`. groups.io stays the member hub for files and discussion.
- **Rules plus exceptions, not typed dates.**
  - Meetings and net variants recur. The rule engine (a PHP port of `matchesRule()`/`nextMeetings()`/`netOn()`) computes the dates, and editors enter only **exceptions**.
  - This removes the "22:00" class of error D9 found.
  - All dates use `wp_date()` in `America/Los_Angeles` (Settings › General).
  - Times are shown only in 12-hour form ("8:00 PM", "noon").
- **Why not Query Loop?** Core Query Loop can show a post type, but "upcoming only", "in progress", date slabs and meta ordering need a `query_loop_block_query_vars` filter plus a block variation anyway [S11]. Purpose-built PHP blocks are simpler, and each has 1 to 4 attributes that the editor exposes on its own [S4].
- **Why not a plugin like an events calendar?** It would bring front-end templates, CSS and settings screens to fight, and it would take a plugin slot. The event model needed here is about 15 fields.

**PHP-only block catalogue.** Every block uses `supports.autoRegister` and a render callback; editors can set only the attributes shown [S4].

| Block | Attributes (editor controls) | Output |
|---|---|---|
| `spokares/upcoming` | types (string), limit (int), days (int), link (string) | `ul.events` with date slabs, "Happening now" |
| `spokares/next-up` | limit (int), types (string) | Event cards (first `card-red`) |
| `spokares/later-season` | limit (int) | Table rows (When / What) |
| `spokares/public-service` | none | 3-column table |
| `spokares/past-exercises` | limit (int) | `ul.ex-past` |
| `spokares/rota` | weeks (int), past (int) | Rota table with `.is-next`, Open tags, notes |
| `spokares/winlink-assignments` | past (int) | How-to line + table |
| `spokares/net-settings` | show alternate (bool) | Settings box + Copy buttons |
| `spokares/net-bar` | none | Night bar + Copy |
| `spokares/meetings` | none | Home in-person list with "Next:" |
| `spokares/next-meeting` | meeting (enum) | One line |
| `spokares/asof` | none | "Schedule as of …" |
| `spokares/doc-library` | none | Rail + 7 groups + empty state |
| `spokares/doc-search` | none | Hub GET form |
| `spokares/quick-links` | limit (int) | 4 tiles |
| `spokares/toc` | none | Side nav + mobile details + floating button |
| `spokares/message-path` | none | Inline SVG diagram |

**Limitation:** PHP-only blocks have no InnerBlocks and no on-canvas rich text [S4]. That is fine here, because every editable word lives in the post types and settings, not in the block.

**Editing a data field inline** (for example, binding a Paragraph to an event's meta) is possible with **Block Bindings** (`core/post-meta`, editable in the editor when the meta is `show_in_rest`) [S12]. It is not needed: events have no single pages. Keep bindings in reserve.

---

## 6. Front-end interactivity (f)

| Behaviour (site.js hook) | Needed in WordPress? | Implementation | Without JS |
|---|---|---|---|
| Mobile menu toggle, Esc, click-outside (`primaryNav`) | Yes | **Core Navigation overlay** (already built on the Interactivity API; focus handling in core) + the 7.0 overlay template part [S6][S7] | Core's overlay needs JS. **INFERENCE:** acceptable; B's no-JS "wrapped row" fallback is lost. If it matters, add a `<noscript>` style that shows the list |
| Copy radio settings + toast (`button[data-copy]`) | Yes (How it works ×2, hub ×1) | `copy` view module: an Interactivity API store with `actions.copy` using `navigator.clipboard`, a textarea fallback and an `aria-live` toast. Buttons get `data-wp-on--click` [S7] | The settings text is visible next to every button, so nothing is lost; the button is hidden without JS |
| Library filter, `?q=`/`?cat=`, count, empty state (`[data-doc-library]`) | Yes | `library` view module (§4.5) | Server-side filtering with the same GET parameters |
| Hub live search preview (`form[data-doc-preview]`) | **No, cut** | Plain GET form to the library | Works |
| About TOC active link, rail growth, contents button (`nav[data-toc]`) | Yes (About only) | `toc` view module, one IntersectionObserver | Plain links, closed `<details>` |
| `hashLanding()` | **Probably no** | Server rendering removes the late height changes; re-test the 8 deep links | CSS `scroll-margin-top` |
| Rota / upcoming / next meeting / as-of / Winlink widgets | **No JS** | Server-rendered (§5) | Same output |
| Tap-to-define `.term` | **No, cut** | Not used on any round-3 page (0 occurrences). Use `<abbr title>` in pattern text | n/a |
| Scrollytelling `[data-story]` | **No, cut** | Round 3 already replaced it with a static opener | n/a |
| `.ics` downloads (`data-ics`) | Optional | Round 3 shows no Add-to-calendar button. Offer a server-side `/calendar.ics` subscription feed instead | Link |
| Demo parameters `?today=`, `?now=`, `?verify=1` | Dev only | `?verify=1` becomes admin-only CSS (logged-in users with `edit_pages`) that outlines `.needs-verify`. `?today=` becomes a `SPOKARES_TODAY` constant that works only when `WP_ENVIRONMENT_TYPE` is `local` | n/a |

**Script modules.** View modules are plain ES modules registered with `viewScriptModule` / `wp_register_script_module`, and they import `@wordpress/interactivity` through the core import map [S7][S16]. **There is no bundler.** About 3 small files replace the 474-line `site.js`.

---

## 7. What does not translate well, and the WordPress-friendly alternative (g)

| Option B device | Why it is a poor fit | Alternative that keeps the feel |
|---|---|---|
| **Message-path diagram**: a 560×400 `<symbol>` with rain pattern, gradients, hop markers and SVG text, switched by CSS custom properties per `data-state`; a `msgpath--to-eoc` override; hidden under 640px | Editors cannot edit SVG. Core blocks the SVG MIME type on upload. An `<img src=.svg>` would lose the page fonts for its labels. The state system existed for scrollytelling, which round 3 dropped | **`spokares/message-path` PHP block** that prints one fixed state (relay to EOC) inline, with `<title>`/`<desc>` and the scene defs inline (no sprite). Labels keep the self-hosted fonts. Drop the unused states and minis. Remove "147.300 MHz" from the label (REVIEW). Changing the picture is a developer task, which is right for a signature illustration |
| **SVG icon sprite** (`#i-copy`, `#i-search`, tile icons) | A per-page sprite injected in the body; `<use href>` | Inline the few SVGs in the PHP blocks that use them (copy, search, 4 tile icons). WordPress 7.1 also has a public **SVG Icon API** (`wp_register_icon_collection()`, `wp_register_icon()`) and a core Icon block [S2]; use them only if editors ever need to place icons |
| **`file://` data.js + client rendering** | No server; the facts were duplicated as no-JS fallbacks | Server-rendered dynamic blocks (§5). The no-JS fallback problem disappears |
| **Tone variables per surface** (`--fg`, `--muted`, `--link`, `--line`, `--focus`) | Section styles can't declare arbitrary custom properties | Section styles for the colours editors see, plus a 30-line `theme.css` block that sets the tone variables on `.is-style-night`, `-storm`, `-dawn`, `-day` and `-paper` (§2.4) |
| **`.table--stack` with `data-label` on every `<td>`** (153 labels) | Core Table has no per-cell data attributes | A `render_block_core/table` filter (HTML Tag Processor) for tables in style `stack`: read the header cells, then add `data-label` to each body cell. Editors never type labels. A `<caption class="vh">` also becomes output from the filter when "Caption" is empty |
| **`<dl>` definition lists** (`.defs`, `.deflist--2`, netbox rows) | Core has no description-list block | Locked Group patterns (`defs`/`def`) for About and Training. The netbox is block output, so it keeps real `<dl>` markup |
| **`ol.steps` / `ul.stops` / `ol.levels`**: list semantics with rich items (H3 + text + link + tag + note) | Core List items can't hold headings or paragraphs; Group can't be `ol`/`li` | Group patterns with style variations, CSS-counter numerals, and a `render_block` filter that adds `role="list"`/`"listitem"` and a screen-reader "Step N:" prefix. The visual is unchanged |
| **Structure figure** (`figure.chains`: 2 chains with vertical connectors, SVG merge curve, amber node, team box) | Bespoke CSS/SVG layout | Locked `chains` pattern: Columns of List `chain` (connector line via `::before`) + Group `chains-team` with the merge curve as a CSS data-URI background and the node as a pseudo-element. Editors change labels only. **Fallback if it proves fragile:** two short lists and one sentence, "Both chains meet in one team: the Emergency Coordinator and AECs" |
| **`<abbr title>` in headings and text** (Home, How it works, Exercises) | Core RichText has no abbreviation format | Keep `<abbr>` in the **pattern source**. RichText keeps unrecognised inline tags as an "unknown" format, so they survive text edits (**INFERENCE; verify in the build**). Editors don't add new ones; that's acceptable. Round 3's "at most 3 terms" rule becomes moot |
| **`data-verify="true"`** (71 flags) | A pre-launch QA device; editors can't add data attributes | Replace it with a `needs-verify` class on blocks in the pattern source (admin-only outline with `?verify=1`), plus a **verify checkbox** on events and documents. Before launch, grep the exported content for `needs-verify` and clear each one. At launch, remove the footer mockup line |
| **Page-scoped `<style>` blocks** (members pages repeat the same 47 lines "on purpose") | Duplication; not editor material | One `theme.css` with per-component sections; per-block styles through `wp_enqueue_block_style()` so pages load only what they use |
| **Hero double gradient + `saturate()`** | Cover supports one overlay | Cover style `night-hero` (§4.1) |
| **1020px menu collapse; drop-down menu panel** | Core Navigation collapses at a fixed 600px; 7.0 overlays are full-screen only [S6][S18] | CSS override for the breakpoint; accept a full-screen night overlay |
| **`html.js` class and the inline head script** | Themes shouldn't print inline head scripts for this | Drop them. The only JS-dependent CSS was the nav (now core) and the TOC (it hides the floating button until the module runs) |
| **`?today=` / frozen `asOf` date** | Mockup-only | Real server date, with a dev-only override (§6) |
| **Relative `data-root` paths** (`../`) | WordPress uses absolute permalinks | Use `home_url()` in the blocks and root-relative links in the patterns |

---

## 8. Hosting-related gotchas that touch components

- **Page cache vs. date logic.** Verpex probably runs LiteSpeed, so LSCache is the likely cache [hosting §3.1, §4.2]. Rota "next", "Happening now", "Schedule as of" and items dropping off after they end all change at **midnight Pacific**. Two ways to keep them right:
  - **Purge on schedule:** purge on every save of an event, document or settings screen, and once a day at 00:05 Pacific. Run it from a **server cron job**, not WP-Cron: cached pages don't trigger WP-Cron.
  - **Short cache on the time-sensitive pages:** give the Home, hub and Exercises pages a public TTL of 1 hour or less.

  The exact LSCache purge hook needs checking once the cache plugin is chosen [unverified].
- **No `.htaccess` assumptions.** The external-link marker, `data-label` and aria-current all run as PHP render filters, which work on every web server kind.
- **Fonts and privacy.** Self-hosting removes the Google Fonts request. That suits the "no tracking" privacy line (D15 `/privacy/`) and removes a third-party dependency.
- **Uploads.** Documents upload through the Media Library, with ISO-dated filenames (D10). SVG uploads stay off: the diagram and icons live in code.

---

## 9. Editing guard-rails that follow from this mapping

These are summarised here. The admin-setup lens owns the details.

- **Roles.**
  - Frank and the second editor get **Editor**. It has no `edit_theme_options`, so there is no Site Editor, Global Styles, template, navigation or Font Library access. `wp_font_family` requires `edit_theme_options` [S9][S14].
  - The technical admin keeps **Administrator**.
- **Patterns.** Every page section is a `contentOnly` pattern; that is the 7.0 default for patterns [S5]. Do **not** set `disableContentOnlyForUnsyncedPatterns`.
- **Post types.**
  - `spk_event` uses `template` + `template_lock: 'insert'` (Paragraph + List only) for the body [S21].
  - `spk_document` has no body, only the meta box.
- **Allowed blocks.** Use `allowed_block_types_all` to limit Pages to core text blocks, the theme patterns and the `spokares/*` blocks. No Custom HTML, Code or Shortcode blocks for Editors.
- **No raw HTML.**
  - Set `codeEditingEnabled` to false for non-admins (through `block_editor_settings_all`).
  - Set `DISALLOW_UNFILTERED_HTML` true. Editors have `unfiltered_html` on single sites by default [S9], which matters on a site that was hacked.
  - Set `DISALLOW_FILE_EDIT` true.
- **theme.json locks.** The custom colour, gradient, font-size and spacing controls are off, and there is no default palette (§2).

---

## 10. Carry-over fixes to build in, not re-litigate

From `design/round3/REVIEW.md`, Option B's "Still to fix" and the SHARED lists. Each is cheap in the WordPress build.

1. About "Last reviewed": print it only when the reviewed-on field is set (§4.3).
2. How it works: add a "Radio settings" jump link to `#nets` at the top; drop 147.300 from the diagram.
3. Hub: link each "Open" rota tag to the open-slot line; link the Simplex note to the net-script row; put the Tuesday net first on phones; point tiles at files; no tile for a "Soon" item.
4. **Contrast:** raise the small grey text on night (the hero agency line and the opener note) from `mist-2` to `mist`.
5. Members H2 red underline plus amber markers "feel busy". **Proposal:** keep the red `m-head` rule only on the library groups, and give other members H2s a hairline. **INFERENCE:** the owner decides; it is one style-variation swap.
6. Members `min-height` pushes the footer off tall screens. Drop `min-height` on `.members-shell` (the template controls it).
7. **Launch blockers (unchanged):**
   - the net-scripts link exposes the roster
   - join@ is not live
   - SCEM review of the legal basis
   - the role addresses are proposals
8. **IA note:** D15 proposed 21 pages; Option B round 3 has 6. Use B's six slugs (`/`, `/how-it-works/`, `/about/`, `/members/`, `/members/documents/`, `/members/exercises/`). Re-point `redirects.csv` targets to these, plus anchors. **INFERENCE:** the EC confirms.

---

## 11. Sources

- [S1] WordPress release list and dates, including 7.1.2 (2026-09-22), 7.1 (2026-08-19) and 7.0 (2026-05-20): https://wordpress.org/news/category/releases/. Current version via https://api.wordpress.org/core/version-check/1.7/ (checked 2026-09-26: 7.1.2).
- [S2] WordPress 7.1 "Mary Lou" (theme.json breakpoints, pseudo-states, Button states, Tabs block, SVG Icon API `wp_register_icon()`, fully iframed post editor): https://wordpress.org/news/2026/08/mary-lou/ ; field guide https://make.wordpress.org/core/2026/08/05/wordpress-7-1-field-guide/
- [S3] WordPress 7.0 "Armstrong" and field guide (minimum PHP 7.4; new blocks; font management page): https://wordpress.org/news/2026/05/armstrong/ ; https://make.wordpress.org/core/2026/05/14/wordpress-7-0-field-guide/ ; https://make.wordpress.org/core/2026/01/09/dropping-support-for-php-7-2-and-7-3/
- [S4] PHP-only block registration (`supports.autoRegister`, auto-generated inspector controls for string/integer/boolean/enum, no InnerBlocks or rich text): https://make.wordpress.org/core/2026/03/03/php-only-block-registration/ ; confirmed in core source `_wp_enqueue_auto_register_blocks()` (`wp-includes/blocks.php`, branch 7.1, "@since 7.0.0").
- [S5] Pattern editing in 7.0 (`contentOnly` default for unsynced patterns and template parts; `disableContentOnlyForUnsyncedPatterns`; `"role": "content"`): https://make.wordpress.org/core/2026/03/15/pattern-editing-in-wordpress-7-0/
- [S6] Customisable navigation overlays (`navigation-overlay` template part area, Navigation Overlay Close block, always full-screen in 7.0): https://make.wordpress.org/core/2026/03/04/customisable-navigation-overlays-in-wordpress-7-0/
- [S7] Interactivity API reference (stable since 6.5; `supports.interactivity`, `viewScriptModule`; used by core Navigation, Search, Query, File): https://developer.wordpress.org/block-editor/reference-guides/interactivity-api/ ; 7.0 changes: https://make.wordpress.org/core/2026/02/23/changes-to-the-interactivity-api-in-wordpress-7-0/
- [S8] theme.json typography: `fontFace`, `src: "file:./…"`, variable weight ranges, fluid sizes, `customFontSize: false`: https://developer.wordpress.org/themes/global-settings-and-styles/settings/typography/
- [S9] Roles and capabilities (Editor capability list; `unfiltered_html` for Editors on single site; `edit_theme_options` = Site Editor access): https://wordpress.org/documentation/article/roles-and-capabilities/ (last modified 2026-09-02).
- [S10] Global settings and styles (theme.json v3; palette/gradients; spacing presets; `settings.custom` → `--wp--custom--*`; block style variations and `styles/*.json` partials with `blockTypes`; `settings.color.custom`, `defaultPalette`): https://developer.wordpress.org/block-editor/how-to-guides/themes/global-settings-and-styles/
- [S11] Extending the Query Loop block (`query_loop_block_query_vars`, `rest_{$post_type}_query`, variations with `namespace`): https://developer.wordpress.org/block-editor/how-to-guides/block-tutorial/extending-the-query-loop-block/
- [S12] Block Bindings API (supported blocks and attributes; `core/post-meta` needs `show_in_rest`; `register_block_bindings_source()`; editor `setValues`): https://developer.wordpress.org/block-editor/reference-guides/block-api/block-bindings/
- [S13] Font sources and licenses: google/fonts `ofl/crimsonpro` (`CrimsonPro[wght].ttf`, `CrimsonPro-Italic[wght].ttf`, wght 200-900, OFL) and `ofl/schibstedgrotesk` (`SchibstedGrotesk[wght].ttf`, wght 400-900, OFL): https://github.com/google/fonts/tree/main/ofl/crimsonpro ; https://github.com/google/fonts/tree/main/ofl/schibstedgrotesk
- [S14] `wp_font_family` post type capabilities all map to `edit_theme_options`: `wp-includes/post.php`, wordpress-develop branch 7.1: https://github.com/WordPress/wordpress-develop/blob/7.1/src/wp-includes/post.php
- [S15] Block visibility and pseudo-state notes in the 7.0 and 7.1 field guides (above).
- [S16] Core block metadata, WordPress 7.1 (Group `supports.position.sticky`; Table cell `tag`/`scope` attributes; List `start`; Separator default styles; core block list incl. `navigation-overlay-close`, `icon`, `breadcrumbs`, `tabs`, `accordion`, `details`): https://github.com/WordPress/wordpress-develop/tree/7.1/src/wp-includes/blocks ; block.json `render` (6.1) and `viewScriptModule` (6.5): https://developer.wordpress.org/block-editor/reference-guides/block-api/block-metadata/
- [S17] `navigation-link` render: `aria-current="page"` only when `attributes.id` matches the queried object: https://github.com/WordPress/wordpress-develop/blob/7.1/src/wp-includes/blocks/navigation-link.php
- [S18] Navigation overlay breakpoint: `navigation/style.scss` uses `@include break-small` for `.wp-block-navigation__responsive-container-open:not(.always-shown)`, and `$break-small: 600px` in `base-styles/_breakpoints.scss`; `overlayMenu` default `"mobile"` in `navigation/block.json`: https://github.com/WordPress/gutenberg/blob/trunk/packages/block-library/src/navigation/style.scss ; https://github.com/WordPress/gutenberg/blob/trunk/packages/base-styles/_breakpoints.scss
- [S19] Block-theme skip link: `wp_enqueue_block_template_skip_link()` in `wp-includes/theme-templates.php` (branch 7.1).
- [S20] Block visibility in 7.0 (`metadata.blockVisibility.viewport`; hidden by CSS, still in the DOM): https://make.wordpress.org/core/2026/03/15/block-visibility-in-wordpress-7-0/
- [S21] Block templates and `template_lock` values (`all`, `insert`, `contentOnly`, false) for post types: https://developer.wordpress.org/block-editor/reference-guides/block-api/block-templates/ ; the editor reads `template_lock` from the post-type object (`wp-admin/edit-form-blocks.php`, branch 7.1).
- Project sources: `design/round3/option-b-lines-down/{DESIGN.md,README.md,assets/site.css,assets/site.js,assets/data.js,*.html,members/*.html,shots/}`, `design/round3/_shared/placement-map.md`, `design/round3/REVIEW.md`, `research/decisions.md`, `research/hosting/verpex-enhance.md`.
