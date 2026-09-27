# Figure One: design system (option A, round 3)

**Thesis:** one labelled figure explains the work, and everything around it stays quiet. The site reads as a calm technical reference that a county emergency manager would trust and a curious newcomer would enjoy.

**Round 3 brief.** The owner's feedback on round 2: "All three of these have just way too much info. It's overload. Do another pass on each to simplify. Minimize repeating information unless it's really important." Round 2's full design notes are archived in `round2/option-a-field-guide/DESIGN.md`. Everything below describes round 3.

| File | What it is |
|---|---|
| `assets/site.css` | The whole design system. Pages add, at most, a small page-scoped `<style>` for one-off layout. |
| `assets/site.js` | Progressive enhancement: menu, glossary terms, copy buttons, data widgets, TOC scroll-spy, document library, deep-link landing, the Fig. 1 motion. |
| `assets/kit.svg` | Source of truth for the illustration kit (`k-*`) and icons (`i-*`). Pasted inline on every page. |
| `assets/data.js` | Round-3 shared data (`window.SPOKARES`), copied from `round3/_shared/assets/`. **Never edit.** |
| `_chrome.html` | The canonical head, kit sprite, header, members layout and footer. Copy it verbatim. |
| `index.html` | Home, rebuilt for round 3. It is the reference for the round-3 section patterns. |
| `kit.html` | Visual reference of every kit part and icon. Not linked from the site. |

---

## 0. Simplification rules (read these before you edit a page)

These rules turn the owner's feedback into checks. `round3/_shared/placement-map.md` is the authority, and the rules below say how to apply it in this option.

**Words**

1. **Take every word from `round3/_shared/copy-<page>.md`.** Don't expand the copy or add a fact. If the layout seems to need a label, reuse words that are already in the copy (Home's "From home" and "In person" come from its lead-ins). If no such words exist, drop the label.
2. **Give each fact one home.** Before you put a fact on a page, find its row in placement-map §1. If this page isn't its home, the most you may add is the one-line pointer the map allows, written as a link. Otherwise leave the fact out. Repeating a fact "for convenience" is the round-2 habit this round exists to remove.
3. **Budgets are hard limits.** Count the visible words in `<main>`. That includes headings, table cells, figure labels, button text, `<details>` contents and visually-hidden text.

   | Page | Words | Max H2 | Other |
   |---|---:|---:|---|
   | `index.html` | 400 | 5 | ≤ 6 CTAs; ≤ 3,200px at 1440 |
   | `how-it-works.html` | 1,200 | 6 | one closing Join line |
   | `about.html` | 2,800 | 9 | 9-entry TOC |
   | `members/index.html` | 300 | 3 | a switchboard |
   | `members/documents.html` | 900 | 7 | descriptions ≤ 8 words, or none |
   | `members/exercises.html` | 700 | 5 | next events first |

4. **Cut content instead of hiding it.** Don't move words into `<details>`, a visually-hidden span or a tooltip to get under a budget. `<details>` is only for real reference detail, such as About's phone TOC.
5. **Start with the action.** Don't add ledes, section intros, "why it matters" bands, summary boxes or reassurance unless the copy file has them.
6. **Every section must hold content.** Remove routers, audience doors, "At a glance", "Everything for members", "Ready to try it?" and any other section that only points somewhere else.
7. **Prefer lists and tables to paragraphs.** Keep a table cell to 12 words or fewer and a list item to one line.

**Look**

8. **Spend boldness in one place.** Each page gets at most one figure plate that earns its space by explaining something words can't. Everything else is type, space and hairlines. Don't give a page more than one tinted band. Use no cards for prose; bordered boxes are only for things a reader acts on.
9. **Be generous with space.** Sections sit 96px apart on desktop and 48px on phones. On a page with a few short sections, put the H2 in the left third and the content in the right two thirds (`.home-sec` + `.home-grid`, see §4).
10. **Figure labels count once.** Draw the labels in the SVG, mark the SVG `aria-hidden="true"`, and repeat the labels in an HTML key list (`.hero__key` on Home). The list is visually hidden on wide screens and shown under the art on narrow ones. The SVG gets no `<title>` or `<desc>`, because the list is the text alternative.
11. **Use one pine button per view.** Secondary buttons use the outline style. A link inside a sentence sits on words that are already there. No arrow glyphs, no tracked capitals, no middle-dot metadata.
12. **Keep glossary terms off Home.** Tap-to-define `.term` buttons add words, and the measure tool counts them as CTAs. Use them on About and How it works only, on first use only, with no more than 25 terms site-wide. Everywhere else, use `<abbr title>`.

**Facts and links**

13. **Use 12-hour times everywhere**, member tables included: "8:00 PM", "9:00 AM–noon", "7:30 PM". Never write "2000" or "1930".
14. **Show people as call signs only.** No personal names anywhere, the rota included.
15. **No `href="#"`.** Every document link comes from `data.js`. A record with `href: null` prints its title and "Soon". The sub-nav's "Task books" goes to `../about.html#training-path`.
16. **Deep links must land exactly on a cold load.** Every id in placement-map §2.4 has to exist. When the map keeps a retired id as an alias, add `<span class="alias" id="…"></span>` as the first child of a `.home-sec` section (or any `position: relative` section). The alias then lands exactly where the section does. Test with the snippet in §8.
17. **Only the chrome repeats.** Don't add strips, banners, next-net lines or repeated facts outside `<main>`. The footer is the only place for "Volunteers, not first responders. In an emergency, call 911."

**Before hand-off:** run `design/tools/measure_pages.py` on the option; screenshot desktop (1440) and a true 390px phone view (§8); and confirm there are no hits for `2000`, `1930` or `href="#"`.

---

## 1. Tokens (unchanged from round 2)

### Colour

| Token | Hex | Use | Contrast |
|---|---|---|---|
| `--pine` | #1F5A46 | Primary buttons, links, focus ring | 7.8:1 on paper |
| `--pine-deep` | #174536 | Hover | |
| `--wheat` | #E8B94A | Current-item bars, highlights, vests | basalt on it 7.3:1 |
| `--wheat-deep` / `--wheat-tint` | #C99A33 / #FBF1D6 | Field-note rule / field-note fill | |
| `--sky` / `--sky-tint` | #8CC1E8 / #E7F2FB | Illustration skies / the one tinted band per page | |
| `--basalt` / `--basalt-2` | #2C2F36 / #4A505C | Text and 2px strokes / secondary text (7.9:1) | |
| `--navy` | #24348A | Structure: header rule, footer, Fig. numbers, table heads, trail numbers | white 10.9:1 |
| `--rule` | #D3DCE4 | 1px frames and dividers | |
| `--paper` | #FBFCFD | Page background | |
| `--teal`, `--bark`, `--pine-mid` | | Illustration only, never text | |

No red except inside the seal. No traffic-light colours as status.

### Type

Lexend only (weights 400, 500, 600 and 700).

| Role | Setting |
|---|---|
| H1 | 700, `clamp(2.25rem, 4vw, 3.5rem)`, line-height 1.08 |
| H2 | 600, `clamp(1.75rem, 3vw, 2.5rem)` |
| H3 | 600, 1.25rem |
| Body / lede | 17px / 1.65; lede 19px / 1.55 |
| Dial reading (Home #visit) | 600, `clamp(2.25rem, 3.6vw, 3rem)`, tabular numbers |
| Captions and figure labels | 13–15px, weight 500 |
| Numbers | `font-variant-numeric: tabular-nums` (`.tnum`, `time`), never monospace |

### Space, shape and motion

The spacing scale is 8px-based: 4, 8, 16, 24, 32, 48, 72, 96 (`--s-1` to `--s-8`). Gutter `clamp(16px, 3vw, 32px)`, max width 1240px. Radii: tiles 10, cards and panels 8, controls 6, chips 999. Focus: a 3px pine outline with a 2px paper gap, wheat on navy.

There is one motion moment on the whole site: on Home, the ICS-213 slip travels Fig. 1's arc once (1.6s ease-out). The static markup rests it at the EOC, so visitors without JS or with reduced motion see the end state. No parallax, no scroll-reveal and no hover animations on cards.

---

## 2. Chrome (copy from `_chrome.html`; see placement-map §2)

1. **Header:** the seal and "Spokane County ARES-ACS" (no tagline), then the nav in this order: Home, How it works, About ARES & ACS, For Members. It has one filled button, "Join the team", which always goes to `index.html#join` and is visible at every width. `aria-current` marks the current section. Below 1100px the menu button reveals the nav as a plain list.
2. **Members layout:** `<div class="wrap m-layout">`, then `<nav class="mnav">`, then `<main id="main" class="m-main">`. The sub-nav sits outside `<main>`, so the skip link lands past it and its words don't count toward the budget. It has exactly five items: This week, Exercises & events, Documents & forms, Task books (`../about.html#training-path`), groups.io. At 1024px and up it is a sticky rail. Below that it is always visible and never sticky: one row on tablets, a two-column index on phones with labels only. It uses no JS.
3. **Footer (≤ 35 words):** the seal; the links How it works, About ARES & ACS, For Members, groups.io and Contact (→ `about.html#contact`), plus "Privacy (coming)" as plain text; then the safety line; then "© Spokane County ARES-ACS. Mockup — content to be verified before launch". It has no address, no audience columns and no affiliation marks.
4. **Kit sprite:** paste `assets/kit.svg` right after `<body>`, between `<!-- kit:start -->` and `<!-- kit:end -->`. It must be inline for `<use href="#…">` to work from `file://`.
5. **Scripts:** `assets/data.js` then `assets/site.js`, both `defer`, at the end of `<body>`. The head one-liner adds the `js` class and turns smooth scrolling off when the URL has a hash, and `hashLanding()` re-settles once fonts and widgets have loaded.
6. **Paths are relative.** From `members/`, prefix root assets and pages with `../`.
7. **Structure:** one `h1` per page and no skipped levels. Landmarks are `header`, `nav`, `main#main` and `footer`, with the skip link first.

---

## 3. What changed in round 3, and why

| Round 2 | Round 3 | Why |
|---|---|---|
| The Home hero was a Fig. 1 plate with a four-step side key, a backups note and a scope line | One full-width plate: H1, lede, two buttons, the agency line, and Fig. 1 with three short labels | The key restated the figure, the backups belong on How it works, and the scope line is the footer's |
| This-week strip with net control, offset, tone and "Add to calendar" | Removed. Home's #visit has the frequency and time only | Newcomer-first (must-fix 5). The rota and settings live on members pages and How it works |
| "Where are you starting?" doors, the quiz, and the trail's `?from=` picker | Removed | Routers repeat the join steps and the nav (map row 71) |
| The join trail beside a four-figure "What it takes" plate, with ACS requirements under it | Four posts (0–3), one action each; "What ACS asks" as one field note under step 3 | One home per fact: the requirements live in About#acs-task-book |
| ARES/ACS field marks, six team tiles, licensing block, three ways to visit with a map, photos, who we serve, the W7GBU story | Four one-liners with small kit vignettes; Come visit as a dial reading plus two dated meetings | Each of those facts has its home on About or How it works |
| Footer with four audience columns, address, affiliation marks and a Disclaimer link | One row of links, the safety line, the mockup line | Placement-map §2.2 |
| A sub-nav of 11 items (six dead `#` links) with hints and group labels, as a sticky tab scroller on phones | Five items with no hints, and no JS on phones | Must-fix 11; §2.3 |

Measured results (2026-09-26): Home went from 1,894 words, 9 H2s and 27 CTAs at 6,100px to 272 words, 3 H2s and 5 counted CTAs at 2,825px. `site.css` went from 1,088 to about 810 lines and `site.js` from 799 to 467.

---

## 4. Component catalogue

These are the class names in `site.css`. **Retiring** means the class is still in the CSS but the round-3 copy has no use for it. Don't use it in new work, and delete it when the last page stops using it.

### Layout
| Class | Use |
|---|---|
| `.wrap` | The centred container, 1240px plus gutters. |
| `.home-sec` + `.home-grid` | A round-3 section: 96px vertical padding, with the H2 in the left third and the content in the right two thirds (stacked below 1024px). Add `.sec--sky` for the page's one band. |
| `.alias` | An empty span holding a retired id at the top of its new section (rule 16). |
| `.sec`, `.sec--tight`, `.sec-head`, `.sec-end`, `.g12` / `.c-*`, `.cols` | Older section and grid helpers. They are fine for long pages; keep `.sec-head` without an intro paragraph unless the copy has one. |

### Controls
| Class | Use |
|---|---|
| `.btn` + `--pine` / `--line` / `--wheat` / `--quiet`, `--sm` | One pine button per view; outline for secondary; wheat on navy. |
| `.more` | A bold text link that is a block's single next action (Home's agency link). |
| `.ext` | External marker: icon plus visually hidden "(opens groups.io)". |
| `.chips` / `.chip`, `.tag`, `.badge`, `.src--*` | Filters (Documents), short labels ("Optional"), format badges, source badges. |

### Figures and photos
| Class | Use |
|---|---|
| `.plate`, `.plate__art`, `.plate__cap`, `.plate__num` | Every figure. Caption bar: `<span class="plate__num">Fig. 2</span> Title`. |
| `.co` / `.co-svg`, `.fig-lbl`, `.fig-lead`, `.fig-path`, `.fig-key` | Numbered callouts (real sequences only), SVG labels with a halo, leader lines, the dotted message path, the HTML key list. |
| `.photo`, `.photo-needed` | Captioned photos (captioned by activity, never by name), and the PHOTO NEEDED frame (at most one per page). |

### Home patterns (reusable elsewhere)
| Class | Use |
|---|---|
| `.hero`, `.hero__plate`, `.hero__copy`, `.hero__lede`, `.hero__agency`, `.hero__fig`, `.hero__art`, `.hero__key`, `.hero__cap` | The Fig. 1 plate. The art is a fixed-height SVG (300px, 220px on phones) with `preserveAspectRatio="xMidYMax slice"` in a 1200-unit viewBox, so labels stay about 15px at every width and phones crop the scenery, not the story. The labels show at 900px and up; below that the key list shows instead. |
| `.does`, `.does__scene`, `.does__art` | A short list in which each item stands on a 2px ground line with a 216×96 kit vignette. Two columns; on phones the vignette sits left at half size. |
| `.visit`, `.dial`, `.meets` | A dial reading (frequency plus time) and a list of dated items, each led by a `.dateblock`. |
| `.trail`, `.trail__step`, `.trail__post`, `.trail__body`, `.trail__note` | A numbered path: a signpost number, an H3, one line and one outline button at the right. The dotted path stops at the last post. Use it only for real sequences (Home #join; About's ARES and ACS paths may reuse it). |

### Content blocks
| Class | Use |
|---|---|
| `.note` | A field note: wheat tint with a left rule. At most one per section. |
| `.plain` | "In plain English": sky tint with a navy rule (About#legal-basis). |
| `.table-wrap` + `.table` (+ `.tstack` on phones) | Data tables: navy head, a `.is-next` wheat row, `.is-past` muted. Always has a `<caption>` and `scope`. |
| `.tile`, `.card`, `.panel` | Only for things a reader acts on. Not for prose. |
| **Retiring:** `.faq`, `.idtable`, `.secdir`, `.chip-index` | FAQ, the ARES vs ACS table, the section directory and the "On this page" chip row are all cut by the map. |

### Long-form (About), members, forms, utilities
`.doc-layout`, `.toc` (`data-toc`), `.toc-bar`, `.prose`, `.h-num`, `.h-anchor`, `.backtop`, `.last-reviewed`, `.page-head`, `.crumbs`. Members: `.m-layout`, `.m-main`, `.mnav`, `.m-h2`, `.m-links`, `.week`, `.quick` (four tiles, labels only), `.doclib*` / `.doc*`, `.explates` / `.explate`, `.searchbox`, `.suggest`. Utilities: `.vh`, `.js-only`, `.no-js`, `.nowrap`, `.mt-*`, `.muted`, `.tnum`. Add `?verify=1` to a URL to outline every `[data-verify="true"]`.

---

## 5. Widgets and hooks (`site.js`)

Put the **static fallback** in the markup, correct as of 2026-09-26; JS replaces or annotates it. Dates come from `SPOKARES.fn.today()` (frozen at 2026-09-26; `?today=` overrides it).

| Hook | Contract | No-JS fallback |
|---|---|---|
| `[data-menu-toggle]` | A button with `aria-controls="site-nav"`. | The nav shows as a list. |
| `.term[aria-controls]` + `.term-note` | Tap-to-define (About and How it works only; rule 12). | The definition shows inline. |
| `[data-copy="radio"]`, `[data-copy-text]` | A button with `<span data-label>`, and a `.status-msg[role=status]` in the nearest `[data-copy-scope]`. | Hide it with `.js-only`; the text stays selectable. |
| `[data-next-meeting-block="workshop"]` | `<time class="dateblock">` containing a `.vh` "Next:", `__mon`, `__day` and `__dow` (Home #visit). | The static date. |
| `[data-next-meeting]`, `[data-widget="meetings-next"]` | An inline "Next: Sat, Oct 10", or a list. | The rule text. |
| `[data-widget="coming-up"]` (`data-days`, `data-limit`, `data-types`) | `.events` with date blocks (for example the hub's next two exercises: `data-types="exercise,training" data-limit="2"`). | Static items. |
| `[data-widget="rota"]` | A `<tbody>`. Call signs only; the notes are "Simplex", "Winlink night" (linked to `exercises.html#winlink-assignments`) and "GMRS net, 7:30 PM"; Open links to `#rota-open`; the next net gets `.is-next`. | The five static rows. |
| `[data-widget="winlink-schedule"]` | A `<tbody>` (Exercises). | Static rows. |
| `[data-start][data-end]` in `[data-past="hide"\|"dim"]` | Past items hide or dim; live ones get "Happening now" in `[data-now-slot]`. | Everything shows. |
| `[data-doclib]`, `form[data-doc-suggest]` | The document library filter (`?q=`, `?cat=`) and hub suggestions. | The form submits `?q=`. |
| `[data-toc]` | Scroll-spy for `.toc` and `.toc-bar`. | Plain links. |
| any `page.html#id` | `hashLanding()` settles the landing after fonts and widgets load. | The browser's own jump. |
| `#fig1-slip[data-path]` | The Fig. 1 motion moment. | It rests at the EOC. |

**Removed in round 3** (don't bring them back): the This-week strip, the net card, the next-net line, the `.ics` calendar file, the Winlink "next assignment" line, the quiz, the join-trail `?from=` router with its "copy link to my path", and the sub-nav tab scroller.

---

## 6. Illustration kit: how to build a new figure

The parts (`k-*`, see `kit.html`) are: handheld operator, base-station operator, repeater on a ridge, EOC, hospital, shelter, comm trailer, laptop, storm cloud, event flag, pine, ground, waves, ICS-213 slip, ID card, go-kit, calendar, cell tower, no-signal badge, and a few more.

- Place parts on a 24px grid, at native size or clean multiples, so strokes stay 2px basalt. The fills are paper, sky, wheat and pine, plus ink for silhouettes.
- People are faceless ink silhouettes in wheat vests. Never draw readable screens, radio displays, channel labels or badges, and never pin a real hospital or equipment location.
- Keep the story in the middle half of the viewBox so phones can crop the sides. Fig. 1 uses `viewBox="0 26 1200 274"` with the story between x 208 and 960.
- Draw labels as `.fig-lbl` with `.fig-lead` leaders and `.co-svg` numbers. Make the SVG `aria-hidden` and give the labels an HTML key list (rule 10).
- Keep each plate under 15 KB of markup. Edit `assets/kit.svg` to change a part, then re-paste it into every page.

---

## 7. Content rules (all pages)

- Take facts only from `round3/_shared/` (and the round-2 archive it cites). Uncertain facts get `data-verify="true"` on the smallest element, with no visible chip.
- Never show a public alert status. Readiness levels are explained on How it works and never shown as live.
- Never publish county, hospital, SCEM, SHARES or 800 MHz channels, the Hospital Net, any simplex frequency, the W7GBU-10 frequency, the roster or ACSFOG content. No personal phones or emails; role addresses carry `data-verify`.
- The net's check-in order is officials, members, then visitors. Third Thursday has no start time ("evenings"). Orientation is "held regularly; ask us for the next date".
- Use sentence case, and numbered markers only for real sequences.

---

## 8. Page-builder notes and checks

Each page takes its words from its copy file and its section list from placement-map §3.

- **Home (`index.html`, done):** a hero plate, then What we do (`.does`), Come visit (`.sec--sky`, `.visit`, `#this-week` alias), and Join in four steps (`.trail`, `#what-it-takes` alias). 272 words, 3 H2s, 5 buttons plus one text link, 2,825px at 1440.
- **How it works:** an H1 and a one-sentence lede, then five H2s (#nets, #activation, #message-handling, #exercises, #roles). The settings box on #nets is a small bordered panel with two copyable lines (`data-copy-text`); it is the only place for the offset and tone. If a figure is used, use one: "Follow one message", with four steps and labels only. The page ends with one closing line linking to `index.html#join`, not a navy band.
- **About:** keep `.doc-layout` with the 9-entry `.toc` and the `.toc-bar`. Use a table or list per topic. The ARES and ACS paths may reuse `.trail`. Use no FAQ, no glossary section and no identification table.
- **Members hub:** H1, the "As of" line, search, This week (the next two exercises via `coming-up`, then the next meeting), Tuesday net (the copyable line plus the `rota` table), and four `.quick` tiles with labels only.
- **Documents:** the library is the page. No lede and no "Most used" H2 (use a chip). Rows have a title, format and version, source, and a description of 8 words or fewer.
- **Exercises:** Next up (the Bring line, the WSDOT card, the SET card, the After line), then Later this season, Winlink assignments (`winlink-schedule`), Public service and Past (8 rows, `.table.tstack`).

**Measure:** `/Users/sean/Projects/spokane_ares/.venv/bin/python design/tools/measure_pages.py design/round3/option-a-field-guide`. The tool counts `<button>` elements as CTAs, so glossary buttons and copy buttons count.

**Phone screenshots:** headless Chrome on macOS won't lay out narrower than about 500px. For a true 390px view, load the page in a 390px-wide `<iframe>` inside a 500px window (the Home shots were checked this way).

**Deep-link test:** load `page.html#id` in a 1440px or 390px iframe. After about 3 seconds, `el.getBoundingClientRect().top` should equal its `scroll-margin-top`: 16px here, and 68px for About's prose on phones. On 2026-09-26, Home's `#join`, `#visit`, `#this-week`, `#what-it-takes` and `#what-we-do` all landed at exactly 16px at both widths.
