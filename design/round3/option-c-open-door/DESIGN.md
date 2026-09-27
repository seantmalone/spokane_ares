# Option C: Open Channel (round 3)

Design system for the Spokane County ARES-ACS site, option C. Round 3 exists because the owner said of round 2: "All three of these have just way too much info. It's overload. Do another pass on each to simplify. Minimize repeating information unless it's really important."

**Thesis, unchanged.** The easiest yes is "come visit", and belonging leads to joining. What changed is the volume. Open Channel is now a quiet site in dusk blue with one warm idea per page. On Home that idea is the two doorways: listen from home, or visit in person.

**Status (2026-09-26).** All six pages are rebuilt for round 3 and integrated. Measured counts are in `README.md` and `_metrics-after.json`.

| File | What it is |
|---|---|
| `assets/site.css` | The whole design system. Pages add at most a small page-scoped `<style>`. |
| `assets/site.js` | Progressive enhancement. Nothing essential needs it. |
| `assets/data.js` | Round-3 shared data, copied from `round3/_shared/assets/`. **Do not edit.** |
| `_chrome.html` | The canonical header, members sub-nav, footer and icon sprite. Copy them verbatim. |
| `_components.html` | The round-2 pattern library. Several of its patterns are retired (see "Retired" below). Check this file before you copy from it. |
| `index.html`, `how-it-works.html`, `about.html`, `members/*.html` | The six pages, round 3, finished. |
| `shots/` | Self-review screenshots. |

---

## 1. Simplification rules for page editors

Read this section before you touch a page. Where anything later in this file, in `_components.html` or in round-2 notes disagrees with it, this section wins.

### 1.1 Words

1. **Your copy file is the page.** Take every word from `round3/_shared/copy-<page>.md`. `placement-map.md` gives each fact one home. You may add a few words of connective voice. You may not add a fact, and you may not bring back a cut one.
2. **Stay inside the budget.** Budgets count visible words in `<main>`, including headings, table cells, button text and `<details>` contents.

   | Page | Words | H2s | Other limits |
   |---|---:|---:|---|
   | `index.html` | 400 | 5 | ≤ 6 CTAs, ≤ 3,200px tall at 1440 (built: 258 words, 3 H2s, 2,935px) |
   | `how-it-works.html` | 1,200 | 6 | one closing "Join the team" line |
   | `about.html` | 2,800 | 9 | 9-entry TOC |
   | `members/index.html` | 300 | 3 | a switchboard with no explanations |
   | `members/documents.html` | 900 | 7 + not-here | descriptions of 8 words or fewer, no lede |
   | `members/exercises.html` | 700 | 5 | next events first |

3. **Say it once.** Before you write a sentence, find its fact in `placement-map.md` §1. If this page is not the fact's home, you may give it one line with a link, and only if the map lists this page as an allowed pointer. Otherwise delete it.
4. **Lead with the action.** Put the H1, then the thing the reader came to do. Cut ledes that restate the H1. Cut "why it matters" bands, quotes, mission statements and reassurance ("No pressure!").
5. **Use lists and tables, not paragraphs.** A table cell holds 12 words or fewer, and a list item holds one line. Only About has paragraphs, and it keeps them short.
6. **Every section must hold content.** A section that only links elsewhere goes, for example "Ready to join?", "Already a member?", "On groups.io" or "Start where you are".
7. **Follow the house style.** Times are 12-hour ("8:00 PM", "9:00 AM–noon"), and "2000" is never written. People appear as call signs only, and names never appear. Uncertain facts carry `data-verify="true"` with no visible chip. There is no `href="#"`: an unbuilt target prints as plain text with "Soon".

### 1.2 Layout and components

8. **Only the chrome repeats.** That means the header, the members sub-nav and the footer from `_chrome.html`. Don't add strips, banners, "This week" cards, next-net lines, "copy radio settings" bars or closing CTA bands to any page.
9. **One bold thing per page.** Home's is the doorways. Choose yours from the page's own content: the settings box on How it works, the TOC on About, the rota on the hub. Keep everything else quiet: white or cloud bands, hairlines and left alignment.
10. **Use fewer, stronger actions.** Each section gets at most one filled button (`.btn--primary`). Other actions are `.link-action` or inline links on existing words. Seal red appears only on the header's Join button.
11. **Use pictures sparingly.** A section has at most one photo, and a page has at most one PHOTO NEEDED frame. Icons label a way in (a doorway, a tile). Don't decorate every list item with an icon.
12. **Use `<details>` only for real reference detail.** In practice that is About's TOC on phones. Hidden words still count. Round 2's FAQ and first-person question accordions are retired.
13. **There is no page motion.** The only animation left on the site is the live net lamp (members). Hover and focus states stay.
14. **Local navs stay small.** About keeps its TOC. Drop the How it works chip bar: the page is now five short sections, and a sticky bar adds chrome. If you keep it, give it five chips and no label.

### 1.3 Retired: don't use these

The CSS and JS for these are deleted. If markup for them remains on your page, it renders unstyled until you remove it.

- **Home-only:** the visit planner (`.planner`, `.doors`, `.door`, `.visit-panel`, `.chip`, `.glance`, `[data-visit]`), the RSVP form (`.rsvp`, `.field`), the join-path picker (`.path-picker`, `.seg`, `?from=`), the two-hats row (`.hats`), the audience row (`.audience`), the partners row (`.partners`), the hero invite and scope lines, and the old `.join-steps` cards.
- **Site-wide (the placement map cuts their content):** "Here's what it takes" (`.takes`, `.figures`), the members-menu disclosure (`.subnav-details`), and the audience-grouped footer (`.footer-top`, `.footer-col`, `.footer-affil`).
- **Still in the CSS but cut by the map, so don't reuse them:** `.short-version` openers on About (they restate the section), `.faq`, `.role-grid` / `.role-card` (roles are an 8-row table now), `.dir-grid` (the sub-nav is the directory), `.radio-strip`, and `.week-card--wide`. Delete their CSS once no page uses them.

### 1.4 What to reach for, page by page

| Page | Build it from |
|---|---|
| How it works | `.page-intro` (H1 plus a one-sentence lede). **#nets:** a `.card--tint` settings box (`.freq`, optional `[data-copy="radio"]` button), the 6 steps as a `.timeline`, and "Other nets" as a 3-item list. **#activation:** the 7 steps as `.timeline` or an `<ol>`, then readiness levels as `.levels` (4 neutral rows, never coloured, never "current"). **#message-handling:** `.hops` with `.scenario-label`. **#roles:** a `.data-table.table--stack` with 8 rows. Close with one `.link-action` "Join the team". |
| About | `.doc-layout` + `nav.toc` (9 entries; let site.js build the list from `h2[id]`) + `article.prose`. Use `.callout--not` for "What we are not", `.callout--legal` under the legal table, `.data-table` for tables, `.callsign-block` once in #w7gbu, and `.last-reviewed` under the H1. No `.short-version`. |
| Members hub | H1 + as-of line + `.search-box` (a GET form to `documents.html`). **This week:** the next two exercises and the next meeting as a short list with `.date-block--sm`. **Tuesday net:** one copyable settings line (`[data-copy="radio"]`), then the `rota` widget in a `.data-table` (columns Tuesday, Net control, Note). **Most used:** 4 `.tiles`. |
| Documents | H1, `.search-box` and `.fchips`, then the `doc-library` widget (it renders from `data.js`), then "Not posted here" (2 sentences). No lede, no status key. |
| Exercises | **#next-up:** the "Bring" line, then one `.card` each for WSDOT and SET. **#upcoming:** a static `.data-table` (6 rows). **#winlink-assignments:** the `winlink` widget. **#public-service:** a 4-row table. **#past:** an 8-row table. The `agenda` widget is heavier than this page now needs; if you don't use it, delete it from site.js. |

### 1.5 Hand-off checks

Run each of these before you hand a page back.

```sh
/Users/sean/Projects/spokane_ares/.venv/bin/python /Users/sean/Projects/spokane_ares/design/tools/measure_pages.py /Users/sean/Projects/spokane_ares/design/round3/option-c-open-door
```

- Every page is within the words, H2 and CTA limits in 1.1.
- `grep -c 'href="#"'` returns 0 on every page, and no "2000"-style time appears.
- Cold-load deep links land exactly (see §6): `about.html#for-agencies`, `#contact`, `how-it-works.html#activation`, `members/documents.html#forms`.
- There is no horizontal scroll at 390px (use the iframe method in §7), and scripts off still read and navigate.

---

## 2. Tokens

Everything is a custom property on `:root` in `site.css` §1. Never hard-code a colour in a page.

| Token | Hex | Use |
|---|---|---|
| `--dusk` | #2F4E7E | H1/H2, primary buttons, links, numerals, date blocks |
| `--dusk-deep` | #1E3557 | footer, hover, focus ring on light |
| `--dusk-tint` / `--dusk-mist` | #E3EAF4 / #EEF3F9 | quiet fills, current nav pill |
| `--dusk-line` | #B9C7DB | card and doorway borders |
| `--pine` / `--pine-tint` | #2E5B4F / #E4EFEA | the helpful secondary: the "From home" doorway icon, "Good to know", "Happening now" |
| `--seal` | #C62128 | **header "Join the team" only** |
| `--lamp` | #F2C14E | only the live net state, and the focus ring on dark |
| `--cloud` / `--white` | #F5F8FC / #FFFFFF | page / cards and alternating bands |
| `--ink` / `--ink-2` | #1B2433 / #465163 | text / secondary text (7.5:1 on white) |
| `--line` | #D6DFEB | hairlines |

**Type.**
- **Bricolage Grotesque** is the display face (`opsz 96`, `font-stretch: 90%`; numerals at 75%). The H1 is `clamp(2.5rem, …, 4.25rem)`.
- **Atkinson Hyperlegible** is the body face at 18px/1.65. Its slashed zero is deliberate, so frequencies set large (Home's `147.300 MHz`) stay in Atkinson.
- Use sentence case. Don't use tracked all-caps eyebrows, middle-dot metadata or arrow glyphs in link text.

**Space and shape.**
- Space runs on an 8px base (`--s1` to `--s7`). The wrap is 1200px with `clamp(16px, 4vw, 40px)` gutters, and section rhythm is `--section-y`.
- Radii: cards and photos 12px, and buttons and pills 999px.
- **The door silhouette** is 28px top corners over 12px bottom corners (`--r-door` / `--r-card`). Use it only for the Home hero photo and the two doorways.

**Focus.** On light backgrounds the focus ring is a 3px `--dusk-deep` outline with a 2px white offset. On dark it is a lamp outline. Don't override either.

---

## 3. Chrome (copy from `_chrome.html`)

- **Head.** The one-liner swaps `no-js` for `js`, and it sets `scroll-behavior: auto` when the URL has a `#hash`. Copy it exactly: the deep-link fix depends on it.
- **Header.** It holds the brand, four nav links (`aria-current="page"` on the current one) and `.btn--join` "Join the team" → `index.html#join`. It is not sticky. There's no utility bar and no next-net line.
- **Members sub-nav** (`members/*.html`, directly after `</header>`). It is `nav.pillbar.subnav > .wrap > ul.subnav-list` with five pills, in this order:
  1. This week
  2. Exercises & events
  3. Documents & forms
  4. Task books → `../about.html#training-path`
  5. groups.io ↗

  The current pill carries `aria-current="page"`. The bar is sticky from 641px, where it is one row. On phones it wraps to two rows (checked at 390 and 360) and scrolls away. There's no disclosure and there are no group labels.
- **Footer.**
  - `.footer-main` holds the brand plus six links: How it works, About, For Members, groups.io ↗, Contact → `about.html#contact`, and "Privacy (coming)" as plain text.
  - `.footer-fine` holds the safety line ("Volunteers, not first responders. In an emergency, call 911.") and "© Spokane County ARES-ACS. Mockup — content to be verified before launch".
  - That is about 34 words. It has no address, schedule, affiliation marks or audience columns.

## 4. Components in use

- **Layout:** `.wrap`, `.section` (`--white`, `--cloud`, `--tint`, `--dusk`), `.section-head` (on an H2 by itself is fine), `.split`, `.grid-2/3/4`, `.stack` and `.page-intro`.
- **Buttons:** `.btn--primary` (one per section at most), `.btn--outline` (the secondary), `.link-action` (text-weight action) and `.ext` (the external marker plus sr text).
- **Home:**
  - `.hero` / `.hero-grid` / `.hero-ctas` / `.hero-agency` / `.hero-photo`
  - `.work-grid > .work-item` (media + h3 + one line; `.media` is a `.photo-frame`, `.art` or `.photo-needed`)
  - `.doorways > .doorway` (`.doorway-head`, `.doorway-lead`, `.doorway-big`, `.doorway-note`, `.doorway-where`, `ul.meetings` with `time.date-block[data-next-block]`)
  - `ol.steps[start=0] > li.step` (`.step-num.display-num`, `.step-body`, `.step-go`)
- **Data:** `.date-block` (and `--sm`), `.data-table` inside `.table-wrap` (`.table--stack` under 640px; give each cell a `data-label`), `.tag`, `.freq` and `.callsign`.
- **Long form:** `.doc-layout`, `nav.toc`, `.prose`, `.h-anchor`, `.callsign-block`, `.last-reviewed`, and `.callout` (`--not`, `--legal`, `--good`, `--plain`).
- **Process:** `.timeline`, `.levels`, `.hops` and `.steps-inline`.
- **Members:** `.search-box`, `.fchips`, `.doc-list` / `.doc-row`, `.badge--*`, `.tiles` and `.rota`.
- **Photos:** `.photo-frame` with a ratio class, and `.art` for subjects we must not photograph (hospitals). `.photo-needed` is limited to one per page. Every photo with people carries `data-verify` (releases).

## 5. JavaScript (site.js)

Widgets find their markup by `data-widget`, and the static fallback lives inside the element.

| `data-widget` | Where | Notes |
|---|---|---|
| `toc` | About | builds the list from `h2[id]` if `ol.toc-list` is empty; scroll-spy |
| `scrollspy` | How it works chip bar | only if the editor keeps the bar |
| `doc-library` / `doc-search` | Documents / hub search | reads `?q=`, `?cat=`, `#category`, `#doc-id` |
| `rota` (on a `tbody`) | hub | round 3: call signs only; Note = "Simplex" / "Winlink night" (links to assignments) / "GMRS net, 7:30 PM" |
| `winlink` (on a `tbody`) | Exercises | |
| `agenda` | Exercises | optional (see 1.4) |
| `week`, `coming-up` | none in round 3 | the old "This week" card; delete them once the hub rebuild confirms they're unused |

**Attribute helpers**
- `[data-next="net|workshop|winlink-workshop|third-thursday|winlink|event:<id>"]` with `data-format` fills in a date.
- `[data-next-block="…"]` fills a `.date-block`'s `.num` and `.mon` and sets `datetime`. Home uses it.
- `[data-copy="radio"]` copies `SPOKARES.radio.copyText`.
- `[data-ics]` builds a calendar file.
- `.term` gives tap-to-define. Use it sparingly: every `<button>` counts as a CTA in the measure tool.
- `?verify=1` outlines `[data-verify]`.
- `?today=YYYY-MM-DD` and `?now=YYYY-MM-DDTHH:MM` are demo clocks.

**Time.** `time()` is always 12-hour. Old `'24'` arguments are ignored.

**Retired.** `planner`, `rsvp`, `partners`, `join-path`, `subnav` and `[data-visit]` are gone. `SPO.syncSubnav()` remains as a no-op so older page scripts don't fail.

## 6. Deep links

`hashLanding()`, ported from option A, runs after fonts and images load and corrects the landing unless the reader has already scrolled. Targets are held clear of sticky bars by `scroll-padding-top` on `html`:

| Page | `scroll-padding-top` |
|---|---|
| Default | 16px |
| Members pages | 80px (16px on phones, where the sub-nav isn't sticky) |
| How it works, with the chip bar | 84px |

About's `.prose` sections add a 24px `scroll-margin-top`. Don't add extra `scroll-margin-top` on members rows: the old 90px values double-counted and have been removed.

Measured cold loads (target top in px, iframe at 1440 × 900):

| Link | Top (px) |
|---|---:|
| `index.html#join`, `#visit`, `#this-week`, `#what-we-do` | 16 |
| `about.html#for-agencies`, `#contact` | 40 |
| `how-it-works.html#activation` | 84 |
| `members/exercises.html#next-up` | 80 |

Retired Home ids are `#visit-listen`, `#visit-meeting`, `#visit-workshop`, `#who-we-serve`, `#w7gbu` and `#members`. Don't link to them. The aliases `#this-week` (to `#visit`) and `#what-it-takes` (to `#join`) are wrapper `div`s, so they land exactly on their section.

## 7. Screenshots

```sh
C="/Applications/Google Chrome.app/Contents/MacOS/Google Chrome"
"$C" --headless=new --disable-gpu --hide-scrollbars --virtual-time-budget=6000 --screenshot=shots/home-desktop-full.png --window-size=1440,3400 "file://$PWD/index.html"
```

Headless Chrome won't lay out narrower than 500px: `--window-size=390,…` renders at 500 and crops. For a true 390px shot, load the page in a 390px-wide `<iframe>` inside a wrapper page and screenshot the wrapper with `--allow-file-access-from-files`. `shots/home-mobile.png` is made that way.

## 8. Platform notes

| Pattern | WordPress block theme | Static site generator |
|---|---|---|
| Doorway dates, hub "This week", rota | a block pattern fed by the calendar source | a partial rendered from the data file; site.js may refresh it |
| Members sub-nav | a second registered menu | a data-driven nav partial |
| About TOC | generated from H2s | generated at build time |
| Document library | a "Document" post type | one data-file entry per document |

---

## Round 2 → round 3, in brief

| Round 2 | Round 3 |
|---|---|
| Home 1,644 words, 5,214px | Home 258 words, 2,935px |
| Home: hero with a This-week card, audience row, join path with two hats and "what it takes", a three-door planner with timelines and an RSVP, photo cards with links, partner exercises | Home: hero, what we do (4 one-liners), two doorways, four steps |
| Header, eleven-item members menu with a disclosure, audience-grouped footer with address and marks | Header, five-pill sub-nav, a footer with one fact |
| Members times in 24-hour ("2000") | 12-hour everywhere |
