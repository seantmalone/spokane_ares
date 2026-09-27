# Figure One: design system (round 2, option A)

Led by round-1 concept 09 "Field Guide". **Thesis:** explain the work with labelled figures, like plates in a field guide, then route each visitor to one first step. The site should read as a calm technical reference that a county emergency manager would trust and a curious newcomer would enjoy.

Files in this option:

| File | What it is |
|---|---|
| `assets/site.css` | The whole design system. Pages use only this plus, at most, a small page-scoped `<style>`. |
| `assets/site.js` | Shared progressive enhancement: menu, glossary terms, copy/calendar, every data widget, TOC scroll-spy, doc library, members tabs, trail, quiz, Fig. 1 motion. |
| `assets/kit.svg` | Source of truth for the illustration kit (`k-*`) and icons (`i-*`). Paste inline on every page. |
| `assets/data.js` | Shared data (`window.SPOKARES`). Copied from `_shared`; **never edit**. |
| `_chrome.html` | Canonical head, kit sprite, header (root and members variants), members index, footer (both variants), scripts. Copy verbatim. |
| `index.html` | Home, fully built. The reference implementation of most components. |
| `kit.html` | Visual reference of every kit part and icon, plus an example assembly. Not linked from the site. |

---

## 1. What changed from round 1, and why

| Judge criticism | Remedy in Figure One |
|---|---|
| Too soft for agencies; reads as edtech | Caveat removed. Annotations are technical-plate labels (Lexend 500, 13-15px, 1px leader lines, 22px numbered circles). Radii cut to 10/8/6. People are faceless silhouettes in wheat hi-vis vests. **Seal navy #24348A** is the structural colour: header rule, footer, agency door, table heads, Fig. numbers. Copy is plain declaratives. |
| Real members only as taped polaroids | Photo plates at useful sizes with plain captions (1px rule, 8px radius). The 659px photos never exceed native width. "From the field" on Home: two real plates and one PHOTO NEEDED plate. |
| Four cards crowd the hero; quiz twice | Hero = Fig. 1 plate (8 columns) with H1 and subline over its sky, one pine button, one outline button. The side column is the figure's key, not cards. The Tuesday tile became the slim This-week strip. The quiz exists once. |
| No agency path | "Where are you starting?" router with an **Agency partner** door (two sentences, one primary action, two links). Footer grouped by audience with an Agency partners column. |
| Member tasks thin | Full members section with its own index rail (desktop) and tab scroller (phones). No login anywhere. |
| SVG art hard to extend | A documented kit of ~25 `<symbol>` parts and 20 icons composed with `<use>`. Fig. 1 is 3.5 KB of markup. See section 6 and `kit.html`. |
| Round-1 content errors | Task Book 2024-08-09 requirements; "Apply to join" groups.io; county link on spokanecounty.gov; "no membership dues" carries `data-verify`. |
| "Email me my path" | Replaced by **Copy a link to my path** (`?from=` deep link). No email field anywhere. |

**Self-review against generic defaults.** The plan avoided: cream + serif + terracotta (we use cool paper, one sans, ponderosa/wheat/navy from the region and the seal); SaaS card kits (radii vary by hierarchy, no drop shadows, frames are 1px rules with Fig. caption bars); tracked all-caps eyebrows, middle-dot metadata and arrow glyphs (none; the only capitals are the mandated "PHOTO NEEDED" label). Boldness is spent in one place: the figure plates. Everything around them is quiet.

---

## 2. Tokens

### Colour

| Token | Hex | Use | Contrast |
|---|---|---|---|
| `--pine` | #1F5A46 | Primary buttons, links, focus ring | 7.8:1 on paper |
| `--pine-deep` | #174536 | Hover | |
| `--pine-mid` | #2E7359 | Illustration hills | illustration only |
| `--wheat` | #E8B94A | Highlights, quiz selection, "Start here", active tabs, vests | basalt on it 7.3:1 |
| `--wheat-deep` | #C99A33 | Wheat rules and strokes | |
| `--wheat-tint` | #FBF1D6 | Field notes, selected rows, quiz band | |
| `--sky` | #8CC1E8 | Illustration skies, PHOTO NEEDED border | |
| `--sky-tint` | #E7F2FB | This-week strip, panels, plate backgrounds | |
| `--basalt` | #2C2F36 | Text, 2px illustration strokes | |
| `--basalt-2` | #4A505C | Secondary text | 7.9:1 |
| `--teal` | #2A8C9A | Decorative strokes (radio waves) only | never text |
| `--teal-ink` | #1B6874 | Glossary underline, secondary links | 6.2:1 |
| `--navy` | #24348A | Structure: header rule, footer, agency door, table heads, Fig. numbers | white 10.9:1, wheat 5.9:1 |
| `--rule` | #D3DCE4 | 1px frames and dividers | |
| `--paper` | #FBFCFD | Page background | |
| `--bark` | #8A5A3B | Illustration only (trunks, trail posts) | |

No red except inside the seal. No traffic-light colours as status.

### Type

Lexend only (400/500/600/700, Google Fonts).

| Role | Setting |
|---|---|
| Display / H1 | 700, `clamp(2.25rem, 4vw, 3.5rem)`, line-height 1.08 |
| H2 | 600, `clamp(1.75rem, 3vw, 2.5rem)` |
| H3 | 600, 1.25rem |
| Body | 400, 17px / 1.65 (About prose 18px / 1.7, 68ch) |
| Lede | 19px / 1.55 |
| Captions | 14px; caption bars and figure labels 13-15px, weight 500 |
| Numbers | `font-variant-numeric: tabular-nums` (`.tnum`, `time`); never monospace |

### Space and shape

8px base: 4, 8, 16, 24, 32, 48, 72, 96 (`--s-1` to `--s-8`). Gutter `clamp(16px, 3vw, 32px)`. Max width 1240px. Radius: tiles 10, cards/panels 8, controls 6, chips 999. Focus: 3px pine outline, 2px paper gap; wheat on navy/pine surfaces (`.on-dark`).

### Motion

One moment per page. Home: the ICS-213 slip travels Fig. 1's arc once (1.6s ease-out, added by `site.js`); the static markup already rests it at the EOC, so no-JS and reduced-motion users see the end state. Quiz reactions crossfade in 150ms. No parallax, no scroll-reveal.

---

## 3. Chrome and page rules

Copy the blocks from `_chrome.html`. Rules:

1. **Header**: seal + wordmark (links to `index.html`), nav in this order: Home, How it works, About ARES & ACS, For Members; one filled CTA, "Join the team", always to `index.html#join`. Mark the current section with `aria-current` (`"page"`, or `"true"` for members sub-pages). At phone width the Join button stays in the bar and the menu button (`aria-expanded`) reveals a plain list.
2. **Members index** (`.mnav`) on every `members/*.html` page, listing every sub-section from `ia.md` §4. Mocked items link to their pages; the rest to `#`. `aria-current="page"` on the current item only.
3. **Footer**: grouped by audience (New to radio, Licensed hams, Members, Agency partners), affiliation marks, the 911 disclaimer (`data-verify`), Privacy and Disclaimer, and the line "Mockup — content to be verified before launch". Never a Terms link, never a login.
4. **Kit sprite**: paste `assets/kit.svg` right after `<body>` (between `<!-- kit:start -->` and `<!-- kit:end -->`). It must be inline for `<use href="#…">` to work from `file://`.
5. **Scripts**: `assets/data.js` then `assets/site.js`, both `defer`, at the end of `<body>`. The `<head>` one-liner in `_chrome.html` adds the `js` class and, when the URL has a `#hash`, turns smooth scrolling off so a deep link lands instantly; `site.js` (`hashLanding`) re-settles on the target after fonts and widgets load, then restores smooth scrolling.
6. **Paths**: relative only. From `members/`, prefix root assets and pages with `../`.
7. **One `h1` per page**, no skipped levels. Landmarks: `header`, `nav`, `main#main`, `footer`. Skip link first.
8. **Figure numbers restart on each page** and follow reading order (Home: Fig. 1 message path, Fig. 2 what it takes, Fig. 3 exam vs driving test, Fig. 4 locator). How it works starts at Fig. 2 per the brief because it continues Home's Fig. 1 story; About starts its own sequence with Fig. 3 "One county, two ways to serve" per the brief. Keep the brief's numbers where it names them.

---

## 4. Component catalogue

Class names in `site.css`, grouped as in the file. "Use" says when to reach for it.

### Layout
| Class | Use |
|---|---|
| `.wrap` | Centred container, 1240px + gutters. |
| `.sec`, `.sec--tight`, `.sec--flush-top` | Vertical section padding (72 / 48 / 0 top). |
| `.sec--sky`, `.sec--wheat`, `.sec--navy` | Section bands. Navy sections add `.on-dark` for link and focus colours. |
| `.sec-head` (+ `--row`) | H2 + one intro paragraph (62ch). `--row` puts a control beside the heading. |
| `.sec-end` | The single next action that ends a section. |
| `.g12` + `.c-3` … `.c-9` | 12-column grid from 1024px; spans collapse below. |
| `.cols` (`--min`) | Auto-fill grid for card lists. |

### Controls
| Class | Use |
|---|---|
| `.btn` + `--pine` / `--line` / `--wheat` / `--navy` / `--quiet`, `--sm`, `--block` | One pine button per view at most; outline for secondary; wheat on navy; quiet for tertiary. No arrow glyphs. |
| `.more` | A bold text link that is a block's next action. |
| `.ext` | External marker inside a link: icon + visually hidden "(opens groups.io)". |
| `.chips` / `.chip` | Filters (`aria-pressed`) and chip indexes (`aria-current`). |
| `.tag` (`--wheat`, `--pine`, `--navy`, `--line`) | Short labels: Optional, Start here, eligibility (Any member / ACS / No license needed to train). |
| `.badge` | Format: DOCX, PDF, Web page. |
| `.src--official` / `--arrl` / `--ours` / `--groupsio` / `--section` | Source badges: "Official: FEMA", "ARRL", "Spokane County ARES-ACS", "Members-only on groups.io", "Section resource". |
| `.icon` | Inline 20px icon: `<svg class="icon" aria-hidden="true"><use href="#i-docs"/></svg>`. |

### Figures and photos
| Class | Use |
|---|---|
| `.plate`, `.plate__art`, `.plate__body`, `.plate__cap` (`--top`), `.plate__num` | Every figure. Caption bar reads `<span class="plate__num">Fig. 2</span> Following one message`. |
| `.co` (HTML) / `.co-svg` (SVG) | Numbered 22px callouts. Only for true sequences. |
| `.fig-lbl` (`--sm`, `--map`), `.fig-lead`, `.fig-path` | SVG label text with a paper halo, 1px leader lines, the dotted message path. |
| `.fig-key` | Numbered list that reads a figure (the mobile caption list). |
| `.photo` (`--native` ≤660px, `--wide` 16:9) | Real photos with captions that describe the activity and never name anyone. Add `data-verify="true"` to people photos (releases). |
| `.photo-needed` | Missing photo: sky-tint frame, dashed sky border, camera icon, "PHOTO NEEDED: …" + shot list. Max two per page. |

### Content blocks
| Class | Use |
|---|---|
| `.tile` (+ `.tile__art`), `.card`, `.panel` (`--wheat`) | Illustrated tiles (10px), cards (8px), tinted panels. |
| `.note` | **Field note**: wheat-tint, left rule. Tips. |
| `.plain` | **In plain English**: sky-tint, navy rule. Legal basis, RACES under the FCC. |
| `.gloss` / `.term` / `.term-note` | Tap-to-define term (section 5). |
| `.table-wrap` + `.table` | Data tables: navy head, `.is-next` wheat row, `.is-past` muted. Always a `<caption>` and `scope`. |
| `.idtable` | ARES vs ACS identification table; `td[data-label]` stacks into labelled cards under 640px. Wrap each cell's content in one `<span class="cell">` (put `data-now-slot` on it) so mixed text and inline elements stay one grid item. A `caption.vh` stays 1px wide. |
| `.faq` | `<details>` questions. |

### Long-form (About)
| Class | Use |
|---|---|
| `.page-head`, `.lede`, `.meta-line`, `.crumbs`, `.last-reviewed` | Page header. |
| `.chip-index` | "On this page" chip row (How it works). |
| `.doc-layout` | Side index + article grid (≥1024px). |
| `.toc` (`data-toc`) | Sticky numbered index; active item gets the wheat bar. |
| `.toc-bar` (`data-toc`, `<details>`) | Phone "Contents" bar, sticky, shows the current section. |
| `.prose` (+ `.prose--margin`) | 18px/1.7 in 68ch. `--margin` puts glossary notes and `.margin-plate` figures in a 200px right margin at ≥1200px. |
| `.h-num`, `.h-anchor`, `.backtop` | Section numbers in H2s, hover anchor links, "Back to top" after each H2. |

### Data widgets (markup + `site.js`)
| Class | Use |
|---|---|
| `.strip` | Home This-week strip. |
| `.netcard` | Rendered by `[data-widget="net-card"]`. |
| `.dateblock` (`--range`) | Month/day/weekday block. |
| `.events` / `.event` (`--now`) | Upcoming list. |
| `.onair`, `.now-tag`, `.status-msg` | "On the air now", "Happening now", live-region status line. |

### Home modules (reusable)
`.hero*`, `.doors` / `.door` (`--agency`), `.quiz` / `.qcard` / `.result` / `.blazes` / `.routes`, `.join-grid` / `.trail` / `.trail__step` / `.trail__post` / `.trail__meta` / `.trail__act` / `.from-picker`, `.takes`, `.fieldmarks` / `.fm`, `.team`, `.lic-visit` / `.trio` / `.exam` / `.visits` / `.locator`, `.field-grid` / `.serve` / `.dean`. How it works may reuse `.takes` (in full), `.fieldmarks`, `.trail` and `.team`.

### Members
| Class | Use |
|---|---|
| `.m-layout`, `.m-main` | Rail + content grid. |
| `.mnav` (`__title`, `__group`, `__group-label`, `__link`, `__label`, `__hint`, `__link--ext`) | Index rail ≥1024px; sticky tab scroller below. On phones the current tab rests 48px in, so the previous tab peeks through a left fade; `site.js` sets `data-fade` (`l`, `r`, `lr`) on the list so hidden tabs are always hinted. |
| `.m-h2`, `.m-links`, `.crumbs` | Members section headings (about 26px, denser than public H2s), a row of follow-on links (44px tap height on phones), breadcrumbs on sub-pages (32/44px tap height). |
| `.table.tstack` (`--swap`, `--wide`) | Phone tables (≤640px) become compact records: column 1 (date) on the left, 2 and 3 on the right. `--swap` puts column 3 first; `--wide` stacks every cell with its `data-label`. Scope desktop-only cell padding on pages to `min-width: 641px`, or it will beat these rules. |
| `.week` | 2x2 This-week plate (put it inside `.plate`). |
| `.quick` | Most-used tiles (max 8): icon, label, one-line hint. |
| `.secdir` | Every sub-section as a card with a one-line description. |
| `.doclib`, `.doclib__tools`, `.doclib__count`, `.docgroup`, `.doclist`, `.doc` (`__title`, `__for`, `__meta`, `__date`, `__links`), `.doclib__empty` | Document library. |
| `.explates` / `.explate` (`--now`) | Upcoming exercise plates (date block, what, where, what to bring, how to sign up). Past exercises go in a `.table`. |
| `.searchbox`, `.suggest` | Search field with icon; live suggestions. |

### Utilities
`.vh` (visually hidden), `.js-only`, `.no-js`, `[hidden]`, `.nowrap`, `.mt-0` … `.mt-5`, `.muted`, `.tnum`. `?verify=1` outlines every `[data-verify="true"]`.

---

## 5. Widgets and hooks (`site.js`)

Every widget is one function and one block/partial. Put the **static fallback** in the markup; JS replaces or annotates it. Dates come from `SPOKARES.fn.today()` (frozen at 2026-09-26; `?today=` overrides). The time of day is the real Pacific clock when the real date matches, or `?now=HH:MM` for demos. Tuesday 20:00-21:00 renders "On the air now"; after 21:00 the next net rolls to next week.

| Hook | Markup contract | No-JS fallback |
|---|---|---|
| `[data-menu-toggle]` | Button with `aria-controls="site-nav"`. | Nav shows as a list. |
| `.term[aria-controls]` + `.term-note#id` | `<span class="gloss"><button type="button" class="term" aria-expanded="true" aria-controls="g-ares">ARES</button><span class="term-note" id="g-ares" role="note">…</span></span>`. First use on each page of ARES, ACS, RACES, EOC, ICS-213, net control, Winlink, SET, W7GBU. ACS = "Auxiliary Communications" (never "…Service"). | Definition shows inline in brackets. |
| `[data-copy="radio"]`, `[data-copy="path"]`, `[data-copy-text]` | Button; put a `.status-msg[role=status]` inside the nearest `[data-copy-scope]`. Clipboard API, textarea fallback. Wrap label in `<span data-label>`. | Hide with `.js-only`; the settings stay as selectable text. |
| `[data-ics="weekly-net"]` | Button. Builds a weekly `.ics` (RRULE, America/Los_Angeles) in the browser. | `.js-only`. |
| `[data-widget="this-week-strip"]` | Slots: `as-of`, `net-when`, `net-control`, `net-flag`, `now-next`. | "Every Tuesday, 8:00 PM" + groups.io calendar link. |
| `[data-widget="net-card"]` (`data-names="true"` on members pages) | Empty or fallback container. Renders date, settings, net control, simplex/Winlink/GMRS flags, the Winlink assignment, Copy + Add to calendar. | Standing pattern text. |
| `[data-widget="next-net-line"]` | Inline span. | Standing pattern. |
| `[data-widget="coming-up"]` (`data-days`, `data-limit`, `data-types`) | Container → `.events` list with date blocks. SHARES items are dropped on public widgets. | A sentence with the monthly pattern. |
| `[data-widget="meetings-next"]` (`data-ids`), `[data-next-meeting="workshop"]` | List, or an inline "Next: Sat, Oct 10". | Rule text ("2nd Saturday"). |
| `[data-widget="rota"]` (`data-names="true"`) | A `<tbody>`. Open slots render "Open / I can take it" linking `#rota-open`. Names only on members pages. | One row: "See groups.io". |
| `[data-widget="winlink-next"]`, `[data-widget="winlink-schedule"]` | Container / `<tbody>`. | "Twice a month" text. |
| `[data-start][data-end]` inside `[data-past="hide"\|"dim"]` | Any static dated item. Past items hide or get `.is-past`; live ones get `.is-now` and a "Happening now" tag in `[data-now-slot]`. | Everything shows. |
| `[data-doclib]` | See markup in section 4. Rows `li.doc#{data id}[data-cat][data-tags]`; groups `section.docgroup#{category}[data-cat]`; chips `button.chip[data-cat]`; `[data-doclib-count]`, `[data-doclib-empty]`, `[data-doclib-reset]`. Reads/writes `?q=` and `?cat=`. | Form submits `?q=`; every entry shows grouped by category. |
| `form[data-doc-suggest]` + `[data-suggest-out]` | Search form (`action="documents.html"`) on the members hub; shows top 5 matches from `data.js`. | Submits to the library. |
| `[data-toc]` | `.toc` and `.toc-bar` link lists (`href="#id"`); one IntersectionObserver sets `.is-active` + `aria-current="true"`. | Plain links. |
| `.mnav` | Current tab scrolled into view on phones; `data-fade` edge cue. | Plain links; right-edge fade only. |
| any `page.html#id` (`hashLanding`) | Nothing to add: `[id]` has `scroll-margin-top`. Settles the landing once fonts and data widgets have changed the layout; skips if the reader has already scrolled. | The browser's own jump. |
| `[data-trail]` + `[data-from]` chips + `[data-trail-share]` | `?from=unlicensed\|licensed\|ares\|acs` marks posts 0/1/2/3 "Start here" and earlier posts "Done". Same-page `?from=` links (doors) update in place. | Trail shows unmarked. |
| `[data-quiz]` + `<script type="application/json" id="quiz-data">` | Questions, routes and "along the way" lines live in the page as JSON (CMS-editable). On phones the quiz waits behind "Start the quiz". | "Four ways to start" route list. |
| `#fig1-slip[data-path]` | The Fig. 1 motion moment. | Rests at the EOC. |

---

## 6. Illustration kit: how to build a new figure

Parts (`k-*`, see `kit.html`): handheld operator, base-station operator, repeater on a ridge, EOC, hospital, shelter, comm trailer, laptop/Winlink, storm cloud, event flag, pine, ground line, plus waves, ICS-213 slip, ID card, question cards, go-kit, trail sign, calendar, moon, sun, car, test sheet, cell tower, no-signal badge.

**Rules**
- 24px grid; place parts at whole units and at native size or clean multiples (native size keeps strokes at 2px).
- Strokes: 2px basalt (`#2C2F36`), round caps and joins. Fills: paper, sky, wheat, pine, plus ink for silhouettes. Scenery only: range `#79A9CF`, hill `#2E7359`, dark meadow `#174536`, rock `#4A505C`, bark.
- People are faceless ink silhouettes in wheat vests. Never draw readable screens, radio displays, channel labels, badges or plates. Never pin a real hospital, cache or equipment location.
- Each plate ≤ 15 KB of markup (Fig. 1 is 3.5 KB; the kit is shared).
- Keep the story in the middle half of the viewBox: at phone width the plate crops the sides (`preserveAspectRatio="xMidYMax slice"` with a fixed height).

**Steps**
1. Pick a viewBox close to the display size (Fig. 1 uses `0 26 800 268` for an ~816px column, so 1 unit ≈ 1px and labels render at 14px).
2. Backdrop, back to front: distant range path, the ridge or buildings, hills, foreground.
3. Parts with `<use href="#k-…" x y width height/>`.
4. The dotted path: `<path class="fig-path" d="…"/>`.
5. Callouts: `<g class="co-svg" transform="translate(x y)"><circle r="11"/><text>1</text></g>`, a `<path class="fig-lead">` to the subject, and `<text class="fig-lbl">` labels (hide labels and leaders under 720px and rely on the `.fig-key` list).
6. `role="img"` with `<title>` and `<desc>` that narrate the numbered steps; wrap in `.plate` with a `.plate__cap` "Fig. n" caption bar.

Edit `assets/kit.svg` to add or change a part, then re-paste it into every page (the block between `<!-- kit:start -->` and `<!-- kit:end -->`).

---

## 7. Content rules (all pages)

- Facts only from `_shared/`. Uncertain facts get `data-verify="true"` on the smallest element; no visible TBD/verify chips.
- **No public alert status** anywhere. Alert levels are explained (Green inactive, Yellow alert, Orange stand by, Red deploy, then the review within 72 hours) as a neutral numbered process, never with a current level.
- Never publish county, hospital, SCEM, SHARES or 800 MHz channels, the Hospital Net, any simplex frequency, the W7GBU-10 frequency, the roster, or ACSFOG content. No personal phones or emails; role addresses carry `data-verify`.
- Names appear only on the members rota. Photos are captioned by activity.
- Net check-in order is officials, members, then visitors. Never show the net at 10 PM. Third Thursday has no start time. Orientation is "held regularly; ask us for the next date".
- Sentence case. No tracked capitals (except the PHOTO NEEDED label), no middle-dot metadata, no arrow glyphs, numbered markers only for real sequences.

---

## 8. Page-builder notes

**How it works** (`how-it-works.html`, as built): `page-head` + `.chip-index` ("On this page"). Then a run of `.plate` figures, top to bottom: Fig. 1 extended, the system as a four-step chain with lettered backups; Fig. 2 Following one message (four static panels, "A scenario", `data-verify`, and the white ICS-213 card); Fig. 3 When the county calls (who calls us, the seven-stop activation track, the four readiness levels as a neutral ramp, Standing Order 1b); Fig. 4 Anatomy of a Tuesday net (with `[data-widget="net-card"]`); Fig. 5 Handling messages (ICS-213 block by block, logs, Winlink); Fig. 6 Exercises and training (month calendar + `[data-widget="coming-up"]`); Fig. 7 Public-service events (the parade-net photo; IMG_6574 moved to Where we work because it shows a mountain-overlook station). Then Where we work, "Pick your post" role tiles with eligibility `.tag`s, Fig. 8 Here's what it takes in full, and a closing `.sec--navy.on-dark` band with Join the team (`.btn--wheat`) and Take the quiz (`index.html#quiz`). The Fig. 3 layers thumbnail links to About.

**About ARES & ACS** (`about.html`): `.doc-layout` with `.toc` (numbered, `data-toc`) and `.toc-bar` (phones) and `article.prose.prose--margin`. H2s carry `.h-num` and `.h-anchor`, each section ends with `.backtop`. Include `id="for-agencies"` (Home's agency door and the footer link to it) as well as every id in `ia.md` §5. Use `.plain` for RCW 38.52 and RACES under the FCC, `.note` for tips, `.idtable` for the five-row ARES vs ACS table, `.faq` for questions, `.last-reviewed` (`data-verify`). The legacy ARES/RACES seal appears only in the history section.

**For Members** (`members/*.html`): `.m-layout` + `.mnav` from `_chrome.html`. Hub: `.page-head`, search (`form[data-doc-suggest]`), a `.plate` with `.week` (net card, coming up, Winlink assignment, next meeting), `.quick` (six most-used), the rota table, `.secdir`. Documents: `[data-doclib]` with every entry static, grouped by category with anchors; `#forms` is a category group. Exercises: `.explates[data-past="hide"]` with `data-start`/`data-end` on each plate, then past exercises in a `.table`.

**Don't change**: tokens, chrome, nav labels and order, the kit sprite (except via `kit.svg`), the Join target, the footer groups and mockup line. Page-scoped `<style>` is for one-off layout only; if a pattern repeats, it belongs in `site.css`.

---

## 9. Measurements and known trade-offs (Home, 2026-09-26)

- Desktop 1440: first viewport holds the H1, the capability sentence, both CTAs and the figure; the join trail starts at about 1,895px; the page is about 6,100px. That is over the ~5,500px guideline: the brief's module list plus the kept visit map, field marks and W7GBU story needed the room. The cheapest further cut would be moving "Three ways to visit" into the strip's "Plan a first visit" link and About.
- Phone 390: first viewport holds the H1, capability sentence and both CTAs; the join trail starts at about 2,840px (about 3.4 screens), after the figure key, strip, doors and a collapsed quiz. The figure key's backup note and scope line are hidden under 720px (the scope line is repeated in the agency door).
- No horizontal scroll at 390px. Works from `file://` with no `fetch()`. Reads and navigates with JS off.
- Screenshots: `shots/home-desktop-fold.png` and `shots/home-desktop-full.png` come from the Chrome CLI commands in the brief. `shots/home-mobile.png` was captured with true 390px device emulation (Chrome DevTools Protocol), because headless Chrome on macOS enforces a ~500px minimum window width, so the CLI's `--window-size=390,…` lays the page out at ~500px and crops it.
- Demo the date logic with `?today=2026-09-29&now=20:15` (On the air now), `?today=2026-10-06` (open net-control slot), `?today=2026-10-13` (Winlink night), and `?from=licensed#join` (trail highlighting). Add `?verify=1` to outline every fact still to be checked.

---

## 10. Integration pass (2026-09-26)

What the integrator changed after the page builders finished, so later edits start from the right place:

- **Shared patterns moved into `site.css`** from the members pages' `<style>` blocks, where they were duplicated three times: `.m-h2`, `.m-links`, breadcrumb tap heights, the tab-scroller fade, and the whole `.tstack` phone-table block. Also from How it works: `.idtable caption.vh { width: 1px }`. The pages now keep only one-off layout.
- **Cascade fix**: with `.tstack` now loading before page styles, the pages' desktop cell padding (`.hub-2up .table td`, `.ex-2up .table td`, `.past td…`) is scoped to `min-width: 641px`. That also stopped the past-exercises records being squeezed to 38% width on phones.
- **Deep links**: `hashLanding()` in `site.js` plus the head one-liner replace About's page-only fix. Every cross-page anchor (footer, doors, members rail) now lands exactly at its scroll margin at 1440 and 390. Before, `how-it-works.html#roles` landed 279px short on desktop and 674px short on a phone.
- **Scroll-spy**: `toc()` gained a rAF scroll check, so PageDown, End and long jumps no longer leave a stale highlight.
- **Members tab bar**: `scroll-padding-inline` so the current tab rests 48px in, and `data-fade` edge cues in both directions.
