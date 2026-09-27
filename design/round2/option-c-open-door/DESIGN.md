# Option C: Open Channel

Round-2 design option for the Spokane County ARES-ACS site, led by the round-1 concept Open Door (04).

**Thesis.** The easiest yes is "come visit". Belonging leads to joining. Open Channel keeps Open Door's warmth (the door-tab visit planner, the This-week card, real photos, the first-person newcomer questions). It fixes the judges' main objection: the site now opens on capability, not a club greeting. An emergency manager lands on *"From Bloomsday to blackouts, we keep Spokane County connected."*, with Spokane County Emergency Management named in the next sentence and an "Agency partner" door one row below.

**Files**

| File | What it is |
|---|---|
| `assets/site.css` | The whole design system. Tokens, type, layout, chrome, every component. Other pages use only this, plus a small page-scoped `<style>`. |
| `assets/site.js` | Progressive enhancement for every page. Widgets render from `assets/data.js`. Nothing essential needs it. |
| `assets/data.js` | Copied unchanged from `_shared/assets/`. The single source for anything date-driven. **Do not edit.** |
| `_chrome.html` | The canonical header, members sub-nav, footer and icon sprite, to copy verbatim. The members-page versions (`../` paths) are in `<template>` blocks. |
| `_components.html` | A working pattern library that uses real content. Copy markup from here for How it works, About and the members pages. |
| `index.html` | Home, fully built. |
| `shots/` | Self-review screenshots. |

---

## 1. What changed from Open Door, and why

| Judge criticism (round 1) | Remedy in Open Channel |
|---|---|
| The hero was about socialising, not capability. | The H1 is the capability line. The lede names SCEM as the primary served agency. "Come say hello on Tuesday night. Visitors are welcome to check in." sits below as the invitation, in pine. An audience row directly under the hero includes **Agency partner**. |
| The peach card looked dated and off-brand. | Peach is gone. The This-week card is white with a 1px `--dusk-line` border, a `--dusk-deep` header band and a dusk date block. The only warm accent is **lamp** (`#F2C14E`), used only for "Tonight" / "On the air now". |
| More visible verify chips than any other concept. | None. Uncertain facts carry `data-verify="true"`. `?verify=1` outlines them for reviewers. |
| A public "Condition Green" badge. | Deleted. Alert levels are explained once, as a neutral numbered sequence (`.levels`), on How it works. |
| Weekly data needed one data file. | Everything date-driven comes from `data.js`: the card, the door-tab date chips, net control in the minute-by-minute, the simplex, Winlink and GMRS flags, the partners row, "first month" dates, the rota, the agenda and the library. |
| The RSVP needed a mail handler. | **Add to calendar** is now the planner's primary action. "Tell us you're coming" is optional and collapsed, and it's the site's only form. It has a honeypot, stores nothing on the host, is documented in an HTML comment for a form service, and shows a mock confirmation on submit. |
| The two hero buttons competed at equal weight. | There's one filled primary, "Plan your first visit", plus a text-weight "Ready to join? See the four steps". Red appears only on the header's "Join the team". |
| The page was about 10,800px and repeated itself. | Home is **5,214px** at 1440 wide (measured). Newcomer questions, alert levels and "first month" move to How it works, and the W7GBU story moves to About. Each module appears once. |
| Superseded content. | Check-in order is "officials, then members" (no call-sign groups). There's no simplex frequency. Net length is `data-verify`. The site says "Apply to join" (never "Subscribe"). The net roster is not linked on the card. |

**Grafts in use:**
- Tuesday 2000: the self-computing next net, "Copy radio settings", and "On the air now" from 8 to 9 PM.
- County Standard: the audience row, the agency door, the audience-grouped footer and the ARES vs ACS table pattern.
- Coverage: a drawn locator map (`#map-meet`) inside the Meeting and Workshop doors.
- Hi-Vis: the "Here's what it takes" figures, and the role cards with eligibility tags.
- Plain Signal: the 0-3 path with `?from=`.
- When the Lines Go Down: the flat four-hop `.hops` diagram, labelled as a scenario.
- The Relay: tap-to-define terms.
- Ops Board: the member desk and a library that says what each document is for.

### Decisions to know about

1. **On Home the join path comes before the planner.** The brief's section list puts the planner first. But the cross-cutting rule says the join path must start within about 1,800px on desktop and about 3 screens on mobile, and the planner is about 1,100px tall on desktop and about 2,000px at 390. So the order is: hero, audience row, **Joining, in four steps** (with the two hats and "Here's what it takes"), **Three ways to visit**, What we do, then Training with partners and Who we serve. Join starts at about 1,060px on desktop and about 2,150px at 390. The hero's primary CTA still jumps straight to the planner.
2. **The header does not stick. Each page's local nav does**: the members sub-nav, the About TOC and the How it works chip bar, all at `top: 0`. There's one sticky thing per page, and scroll-padding is set per pattern with `html:has(...)`.
3. **Members sub-nav: two rows of pills at 1280px and up; a "Members menu" disclosure below that** (integration change, per ia.md §4). The ia.md labels are long, so eleven items can't fit on one line in the 1200px wrap. On wide screens the group names (Operate, Prepare, Reference) show only as wider gaps and as `aria-label`s. Below 1280px the same list sits inside a sticky `details.subnav-details` whose summary reads "Members menu" plus the current page. It opens to the whole grouped list with the group names printed, one column on phones and two from 700px. It replaced the earlier sideways-scrolling row, which showed only two or three of the eleven items at 390px. Site.js holds it open at 1280px and up. The HTML ships with `open`, so with scripts off it is a plain list, and it doesn't stick on small screens.
4. **about.html must include `id="for-agencies"`**, the "For agencies and partners" section from the Option C brief. The Home audience row and the footer link to it. It is in addition to ia.md's About anchors, not instead of them.
5. **Photos on Home.**
   - The parade operator photo (badge-blurred derivative) is the hero image.
   - "What we do" uses the comm trailer, a drawn hospital illustration (hospitals are never photographed), a PHOTO NEEDED frame for Bloomsday, and the go-box (IMG_6574).
   - The mast team and the bumper station are not used on Home; they are free for How it works.
   - Every photo with people carries `data-verify` (releases).
6. **Mobile screenshots.** In this Chrome build, headless `--window-size=390,…` lays the page out at 500px and then crops to 390px. `shots/home-mobile.png` is therefore rendered through a 390px iframe (scratch `frame.html` wrapper), which shows the true 390 layout. See §9.

---

## 2. Tokens

Everything is a custom property on `:root` in `site.css` §1. Never hard-code a colour in a page.

| Token | Hex | Use | Contrast |
|---|---|---|---|
| `--dusk` | #2F4E7E | H1/H2, primary buttons, links, date blocks | 7.9:1 on cloud; white on it 8.4:1 |
| `--dusk-deep` | #1E3557 | This-week band, footer, hover, focus ring on light | |
| `--dusk-tint` | #E3EAF4 | "The short version", active TOC/nav pill, door tabs at rest | ink on it 12.9:1 |
| `--dusk-line` | #B9C7DB | card borders, dashed photo frames | decorative |
| `--dusk-mist` | #EEF3F9 | quiet fills, hover rows | |
| `--pine` | #2E5B4F | secondary buttons, the invitation line, "Good to know", Happening now | white on it 7.7:1 |
| `--pine-tint` | #E4EFEA | callouts, ACS eligibility tags | |
| `--seal` | #C62128 | **header "Join the team" only** | white on it 5.8:1 |
| `--lamp` | #F2C14E | **only** "Tonight" / "On the air now" on dusk-deep, and the focus ring on dark | 7.4:1 on dusk-deep |
| `--cloud` | #F5F8FC | page background | |
| `--ink` / `--ink-2` | #1B2433 / #465163 | text / secondary text | ink-2 is 7.5:1 on white |
| `--line` | #D6DFEB | hairlines | |

Peach is removed. Don't use traffic-light colours for anything, and never for alert levels.

**Type**
- **Bricolage Grotesque** is the display face, at `opsz 96` and `wdth 90` (`font-stretch: 90%`). H1 is weight 700 at `clamp(2.5rem, …, 4.25rem)`/1.02. H2 is `clamp(1.75rem, …, 2.5rem)`. H3 is 1.25rem.
- Big numerals (`.display-num`) use `wdth 75`, weight 800. They appear in the step numbers 0-3, the figures, date blocks and the W7GBU block.
- **Atkinson Hyperlegible** is the body face: 18px/1.65, 16px for UI, 19px/1.7 on About (`.prose`). Its slashed zero is deliberate, because it reads as ham-native in NZ2S and 147.300. `tabular-nums` is on the body.
- Sentence case everywhere. No tracked all-caps eyebrows, no middle-dot metadata, no arrow glyphs in button text.

**Space and shape**
- An 8px base: `--s1` to `--s7` = 8, 16, 24, 32, 48, 64, 88.
- The wrap is 1200px with `clamp(16px, 4vw, 40px)` gutters. Section rhythm is `--section-y` (44-68px).
- Radius:
  - cards and photos: `--r-card` 12px
  - This-week card: `--r-week` 14px
  - door tabs: `--r-door` 28px top corners, the signature shape
  - buttons and pills: 999px
  - inputs: 8px

**Focus.** On light backgrounds, a 3px `--dusk-deep` outline with a 2px white offset (a box-shadow fills the gap). On dark backgrounds (footer, week band, `.section--dusk`), the outline is lamp. Don't override it.

**Motion.** One moment per page: choosing a visit door slides and fades the panel in (`.panel-in`, 200ms). The lamp dot pulses only while the net is on the air. Both are off under `prefers-reduced-motion`.

---

## 3. Components (class names and use)

Section numbers refer to `site.css`. Working examples are in `_components.html`.

**Chrome (§7-9).** Copy it from `_chrome.html`.
- `.site-header`, `.brand`, `.site-nav` and `.nav-link[aria-current]`.
- `.btn--join` (header only) and `.menu-btn[aria-expanded][aria-controls]`.
- `nav.pillbar.subnav[data-widget="subnav"]` holds `details.subnav-details[open]`. Inside it are `summary.subnav-summary` ("Members menu" plus `.current`, the current page's name, which must match the current pill) and `.subnav-inner > .subnav-list`. The list holds `.subnav-group` items (`span.subnav-label[aria-hidden]` plus `ul[aria-label]`), and the current item is `.pill[aria-current="page"]`.
- `.site-footer` holds `.footer-top` (brand plus four audience columns), `.footer-affil`, `.footer-fine` and `.mockup-note`.

**Layout (§3).**
- `.wrap`, `.wrap--narrow`, `.section` with `--white`, `--cloud`, `--tint` or `--dusk`, and `.section-head`, `.section-head--row`, `.section-end`.
- `.split`, `--wide-left`, `--wide-right`, `.grid-2`, `.grid-3`, `.grid-4`, `.stack`, `--s`, `--l`.
- `.page-intro` (the band for an inner page's H1, lede and meta line) and `.crumbs`.

**Buttons (§6).**
- `.btn` with `--primary` (dusk), `--pine`, `--outline`, `--quiet`, `--on-dark`, `--small` and `--block`.
- `.link-action` is the text-weight CTA. `.link-list` is a row of links.
- External links: add `<svg class="icon ext"><use href="#i-external"/></svg>` and sr text such as "(opens groups.io)".

**Icons (§5).** Use `<svg class="icon" aria-hidden="true"><use href="#i-NAME"/></svg>`. The sprite is in `_chrome.html`, and every page includes it inline, because external sprites don't load from `file://`.
- Icons: headphones, people, tools, door, calendar, radio, copy, check, external, menu, close, search, pin, doc, form, hospital, weather, antenna, trailer, camera, chevron-down, chevron-up, link, shield, book, kit, clock, mail, message, map, flag, wave, star.
- `.icon-disc` (and `--pine`) is the round icon background.
- The sprite also holds `#map-meet`, the drawn locator map.

**Home patterns (§10-17).**
- The hero and invitation line: `.hero`, `.hero-grid`, `.invite`, `.hero-ctas` and `.hero-scope`.
- The audience row: `.audience`.
- The This-week card: `.week-card`, `.week-head` (h2 plus optional `.live`) and `.week-body[data-widget="week"]`. Inside it: `.net`, `.date-block`, `.net-title`, `.net-lines`, `.net-flag`, `.settings`, `.coming-list` and `.week-links`. The members variant is `.week-card--wide`.
- The visit planner: `.planner[data-widget="planner"]`, `.doors > li > a.door[data-door][href="#visit-x"]`, `.visit-panels > section.visit-panel#visit-x`, `.visit-grid`, `.chips[data-chips]`, `[data-slot="date|what|note|nc"]`, `.timeline` (with `li[data-when="simplex|winlink|gmrs"]` for conditional steps and `.tl-you` for "your cue"), and `.glance` (dl plus `.actions` with `a[data-ics="visit"]` and `a[data-gcal]`).
- The join path: `.path-picker[hidden]` with `.seg button[data-from]`, `.hats` / `.hat` / `.hat-quote`, `.join-steps > .join-step[data-step]` (`.step-num`, `.step-flag.next`, `.step-flag.done`, `dl.step-facts`, `.step-action`) and `.takes`.
- Figures: `.figures` (2x2) or `.figures--row` (4 across), with `.figure > .display-num + div > b + span`.
- What we do: `.work-grid > .work-item`, where `.media` is a `.photo-frame`, `.art` or `.photo-needed`.
- Partners and agencies: `.partners[data-widget="partners"]`, `.served-wrap`, `.served-lead`, `.served-list`, `.affil-line` and `.agency-door`.

**Photos and frames (§15).**
- `.photo` (figure) with `.photo-frame` (12px, cover) and ratios `.ratio-4x3`, `3x2`, `16x10`, `16x9` and `1x1`.
- `.art` is a dusk-tint illustration panel.
- `.photo-needed` holds the camera icon plus `<p><b>PHOTO NEEDED</b>shot list…</p>`. Use at most two per page.
- `.map-frame` is a figure with an SVG and a caption. Drawn maps use dusk, pine and cloud only. Never show hospitals, the cache, MNUs or equipment sites. A county map may show hospitals only as a hatched general area, plus the line "Sensitive sites are never shown".

**Cards and tiles (§16).**
- `.card` (with `--tint`, `--pine` and `--accent`).
- `.tiles > .tile` for members "Most used" (8 at most).
- `.dir-grid > .dir-card` for the members section directory.
- `.role-grid > .role-card` for "Find your spot", with `.elig-list > .elig` and `.elig--ares`, `--acs`, `--anyone` or `--open`.

**Callouts (§18).**
- `.short-version`, with `.sv-label` "The short version", opens every About H2.
- `.callout--good` ("Good to know", pine), `.callout--plain` ("In plain English"), `.callout--legal` and `.callout--not` (the "What we are not" box).
- `blockquote.quote` with `cite`, and `.pullquote`.

**Tables (§19).**
- `.table-wrap > table.data-table`, with a `<caption>` (and `<small>`), `th[scope]`, `tr.is-next` and `tr.is-past`.
- `.data-table--compare` is the ARES vs ACS table.
- `.table--stack` turns rows into labelled cards under 640px. Give every `<td>` a `data-label` and make the first cell `<th scope="row">`.

**Long form (§20), for About.**
- `.doc-layout` contains `nav.toc[data-widget="toc"]` and `article.prose`. The TOC is `details.toc-details[open] > summary` ("Jump to section", with `.current` and a chevron), then `.toc-panel` holding `.toc-title`, `ol.toc-list` (H3s nest as an inner `ol`) and `.toc-foot`.
- If `ol.toc-list` is left empty, site.js builds it from the article's `h2[id]` and `h3[id]`.
- `.h-anchor` headings get `a.anchor` "#". Put `.back-to-top` after each H2 section. Also available: `.callsign-block` (the big W7GBU), `.definition-list` (the glossary) and `.last-reviewed`.

**Disclosure (§21).**
- `.faq > details > summary` (the first-person question plus a chevron icon) and `.answer`. Answers are always in the HTML.
- `.disclosure` is the plain version.

**Terms (§22).** Markup:

```html
<button type="button" class="term" aria-expanded="false" aria-controls="t-ares">ARES</button><span class="term-def" id="t-ares" role="note"><b>ARES</b>Amateur Radio Emergency Service, the ARRL’s volunteer emergency program.</span>
```

- Without JS, the definition shows inline in parentheses. With JS, it opens as an inline dusk-tint note.
- Use a term on the first use on each page of ARES, ACS, RACES, EOC, ICS-213, net control, Winlink, SET and W7GBU. Take the definitions from content-about.md's Glossary.
- The ACS definition always says "Auxiliary Communications" and carries `data-verify`.

**Process (§23).**
- `.levels > .level` shows the four readiness levels as a neutral numbered sequence. Never add colour fills or a "current" marker.
- `.hops > .hop` (`.hop-icon` plus `.hop-body`) is the four-hop message path, with `.scenario-label` "A scenario. Not a real event." (`data-verify`) and `dl.sample-213`.
- `.steps-inline` shows activation in five steps.

**Members (§24-25).**
- Search: `.search-box` (a GET form to `documents.html` with `name="q"`) and `.popular`.
- The library: `.fchips > .fchip[aria-pressed]`, `.result-count[aria-live]`, `.empty-state`, `.doc-cat > h2 + p + ul.doc-list > li.doc-row#doc-id` (`.doc-icon`, `.doc-title`, `.doc-desc`, `.doc-meta` with `.badge--format`, and `.doc-go` with the source badge and where it opens).
- Source badges:
  - `.badge--official`: "Official: FEMA" or "ARRL"
  - `.badge--ours`: "Spokane County ARES-ACS"
  - `.badge--members`: "Members-only on groups.io"
  - `.badge--section`, `.badge--coming`, `.badge--history`
- The agenda: `.date-tabs > .date-tab`, `.agenda-month > h3 + ol.agenda > li.agenda-item > details > summary`, then `.date-block--sm`, `.agenda-title`, `.agenda-meta`, `.agenda-side` with a `.tag`, and `.agenda-body`.
- Also for members: `.rota`, `.radio-strip`, `.checklist` and the `.tag--now`, `--exercise`, `--training`, `--meeting`, `--net`, `--public-service`, `--on-air` and `--open` tags.

**Forms (§13).** `.field` with `label`, `.hint`, `.honeypot`, `.form-error[role=alert]`, `.form-done`, and `.rsvp` (details). There is only one form on the site, the RSVP. Don't add another.

---

## 4. JavaScript widgets (site.js)

Each widget is one function that finds its markup by `data-widget`. Each maps to one WordPress block or pattern, or one SSG partial. Put the static fallback inside the widget element. JS replaces or enhances it.

| `data-widget` | Where | Fallback to write in HTML | Options |
|---|---|---|---|
| `week` (on `.week-body`) | Home card, members hub | the standing pattern: Every Tuesday, 8:00 PM, W7GBU 147.300 MHz, +600 kHz, 100 Hz tone, plus the groups.io calendar link | `data-variant="home|members"`, `data-terms="true"` (first-use terms in the card), `data-clock="12|24"`, `data-limit` |
| `coming-up` (on a `ul.coming-list`) | anywhere | the standing monthly pattern | `data-days`, `data-limit`, `data-variant`, `data-clock` |
| `partners` (on `ul.partners`) | Home | items as of 2026-09-26 | `data-days` (90), `data-limit` (5) |
| `planner` | Home `#visit` | three stacked panels, with doors as anchor links | `data-default` |
| `rsvp` (on the form) | Home | a plain form | |
| `join-path` (on `#join`) | Home | steps without a highlight; the picker stays `hidden` | reads `?from=unlicensed|licensed|ares|acs` |
| `toc` (on `nav.toc`) | About | hand-written links, or an empty `ol` | `data-for="#article-id"` |
| `scrollspy` (on `nav.chipbar`) | How it works | plain anchor chips | |
| `doc-library` | members/documents.html | every entry, grouped by category with ids | reads `?q=`, `?cat=` and `#category` / `#doc-id`; `data-render="false"` on `[data-docs]` keeps hand-written rows |
| `doc-search` | members hub | the GET form | |
| `rota` (on `tbody`) | members hub | "See the rota on groups.io" | `data-names="true"` (names only here, as published), `data-past="false"` |
| `winlink` (on `tbody`) | members hub | "2nd and 4th Tuesdays" | |
| `agenda` | members/exercises.html | the standing pattern plus the groups.io calendar | `data-days`, `data-clock`; child hooks `[data-agenda-tabs]`, `[data-agenda-filters]`, `[data-agenda-list]` |

**Attribute helpers (any page)**
- `[data-next="net|workshop|winlink-workshop|third-thursday|winlink|event:<id>"]` with `data-format="short|long|day|range"` and `data-index` fills in the computed date. This is how "Your first month" uses relative labels ("Your first Tuesday") filled with real dates.
- `[data-netcontrol="0"]` gives the call sign for the next net (or `1` for the one after).
- `[data-slot="asof"]` gives the as-of date.
- `[data-copy="radio"]` copies `SPOKARES.radio.copyText`. It uses the clipboard API, falls back to a textarea, and reports to the nearest `.status-msg[role=status]` inside `[data-status-scope]`.
- `[data-ics="weekly-net"]` builds a weekly RRULE `.ics` in the browser. `[data-ics="event"]` does the same for one event (with `data-date`, `data-start`, `data-end`, `data-end-date`, `data-all-day`, `data-title` and `data-where`). Give each an `href` fallback (the groups.io calendar).
- `[data-visit="listen|meeting|workshop"]` (plus an optional `data-visit-date`) on any link to `index.html#visit` opens that door.
- `<body data-root="">` on top-level pages, or `"../"` on members pages. Widgets prefix their links with it.

**Time rules**
- The next net is the next Tuesday at 20:00 America/Los_Angeles from today. After 21:00 on a Tuesday it rolls to the next week.
- 20:00-21:00 on a Tuesday shows "On the air now". Earlier that Tuesday shows "Tonight, 8:00 PM".
- Events in progress (for example the WSDOT exercise on Sep 26-27) show "Happening now". Past items drop off.
- SHARES items and optional section events never appear in public lists.
- In the mockup, "today" is `SPOKARES.meta.asOf` (noon). For demos, use `?today=YYYY-MM-DD` or `?now=YYYY-MM-DDTHH:MM`. In production, delete `meta.asOf` and site.js uses the real Pacific clock.

**Other switches.** `?verify=1` outlines every `[data-verify]`. `?from=licensed#join` sets the join path.

---

## 5. Page-builder rules (what not to change)

1. Use `site.css` as is. A page may add one small `<style>` block for page-only layout, such as a grid tweak. Don't add colours, fonts, shadows or radii, and don't restyle components.
2. Copy the header, sub-nav, footer and icon sprite verbatim from `_chrome.html`. Change only the `aria-current` attributes, `<title>`, the meta description and the `../` prefixes.
3. Keep one `h1` per page and don't skip heading levels. Use the landmarks header, nav, main#main and footer. The skip link is the first focusable element.
4. Use only facts from `_shared/`. Give uncertain facts `data-verify="true"` on the smallest element. Show no visible TBD, verify or "to confirm" text.
5. **Never show:**
   - a current alert level
   - a simplex frequency
   - county, hospital, SHARES or 800 MHz channels
   - the Hospital Net
   - the staff meeting
   - the roster
   - personal phone numbers or emails
   - names on photos
   - the net at 10 PM
6. People appear as role plus call sign. Names appear only in the rota (`data-names="true"`), as published.
7. Red is used once, on the header's Join button. Lamp is used only for the live net state and the focus ring on dark. Pine marks the helpful secondary: the invitation, "Good to know", "Happening now" and the agency door.
8. Use at most two PHOTO NEEDED frames per page, from the shot list. Blur or crop any badge. Put `data-verify` on every photo with people in it.
9. Every section ends in one next action. A module appears once on the site; elsewhere, link to it.
10. JS is enhancement only. Check each page with scripts off: it must read and navigate.

## 6. Guidance for the other sections

**How it works, "A month with the team".**
- Start with a `.page-intro`. Put a sticky `nav.pillbar.chipbar[data-widget="scrollspy"]` directly after `</header>`. Use the ids from ia.md §5.
- Sections, in order:
  1. The weekly net: the full minute-by-minute from content-how-it-works §4, as a `.timeline` with `data-slot="nc"` or `[data-netcontrol]`. The radio settings with `data-copy`. Visitors' cue as `.tl-you`.
  2. When the county calls: `.steps-inline`, then `.levels` (four columns), then `.levels-after`.
  3. How a message moves: `.hops` with `.scenario-label`. Then Winlink through W7GBU-10 and the next assignment (`[data-next="winlink"]`), and the ICS-309.
  4. Exercises: a `.data-table` or the `coming-up` widget.
  5. Public service.
  6. Where we work: a drawn county map in `.map-frame` with "Sensitive sites are never shown".
  7. Find your spot: `.role-grid`.
  8. Your first month: a `.timeline` with `[data-next]` relative labels.
  9. Questions newcomers ask: `.faq`, first person.
- Photos available: the mast team, the bumper station and the tabletop station. Use at most two PHOTO NEEDED frames (EOC radio room; net control at a home station).

**About ARES & ACS, "Everything, in plain words".**
- Use `.doc-layout` with `nav.toc[data-widget="toc"]` on the left (sticky from 1024px; a "Jump to section" disclosure below that) and `article.prose` (19px/1.7, 70ch).
- Open every H2 with a `.short-version`.
- Include:
  - ARES vs ACS as `.data-table--compare.table--stack`
  - the membership levels and training requirements as a `.checklist`
  - `.callout--good` and `.callout--plain`
  - the legal basis in `.callout--legal`
  - the W7GBU story with `.callsign-block`
  - **`#for-agencies`**: the SCEM relationship, the agreements and the contact route
  - the FAQ as `.faq`, the glossary as `.definition-list`, and `.last-reviewed` (`data-verify`)
- The legacy ARES/RACES seal appears only in `#history`.

**For Members, "the member desk".**
- Every page has the sub-nav from `_chrome.html` (the For Members nav link gets `aria-current="true"`).
- Hub:
  - `#search` (`doc-search`)
  - `.week-card--wide` (`data-widget="week" data-variant="members"`)
  - `.tiles` (8 at most)
  - `.radio-strip`
  - `#rota` (`rota`, names on)
  - `#winlink`
  - `#requirements` (`.checklist`)
  - `#sections` (`.dir-grid`)
  - `#groups-io`
  - `#help` (`.table--stack`)
- Documents: the `doc-library` widget. Its static fallback lists all 74 entries grouped by category. To produce it, open the page with JS in Chrome and copy the rendered `[data-docs]` HTML, or run `--dump-dom`.
- Exercises: `#next-up`, then the `agenda` widget, then past exercises from `SPOKARES.pastExercises` in a `.data-table`.

---

## 7. Platform notes

| Pattern | WordPress block theme | Static site generator |
|---|---|---|
| This-week card, coming-up, partners row | One block pattern each, fed by the calendar source (groups.io ICS or a role-owned Google Calendar) | A partial rendered at build time from the data file. site.js may refresh it client-side. |
| Planner | A pattern with three door panels; the dates come from the same calendar feed | A partial |
| Join path | A static pattern; `?from=` is client-side only | A partial |
| Members sub-nav | A second registered menu location | A data-driven nav partial |
| About TOC | Generated from the H2s and H3s by a small block | Generated at build time |
| Document library | A "Document" post type (category taxonomy; format, version, source, status) | A data file with one entry per document |
| RSVP | A WPForms or Gravity Forms form with a honeypot and Akismet | Formspree, Basin or Netlify Forms. Replace `action`. |

## 8. Verification (self-review)

- Home at 1440 wide is 5,214px (measured). Join starts at about 1,060px on desktop and about 2,150px at 390. The first viewport at 1440x900 and 390x844 shows the H1, the SCEM sentence, the invitation, the primary and secondary CTAs, and the scope line.
- There is no horizontal scroll at 390.
- These states were checked:
  - Sat Sep 26 (WSDOT "Happening now", NZ2S with a fifth-Tuesday simplex flag)
  - `?now=2026-09-29T20:15` ("On the air now"; WSDOT drops off)
  - `?today=2026-10-20` ("Tonight", net control WA7LNC, third-Tuesday GMRS flag)
  - `?from=licensed` (step 0 done, step 1 highlighted)
  - `#visit-meeting` (the Meeting door with its map)
  - scripts off (doors become anchors, all three panels stack, the card shows the standing pattern)
- The rendered DOM has no duplicate ids, no "undefined", "NaN" or "TBD", and no status badge.

## 9. Screenshot commands

```sh
C="/Applications/Google Chrome.app/Contents/MacOS/Google Chrome"
"$C" --headless=new --disable-gpu --hide-scrollbars --virtual-time-budget=6000 --screenshot=shots/home-desktop-fold.png --window-size=1440,900 "file://$PWD/index.html"
"$C" --headless=new --disable-gpu --hide-scrollbars --virtual-time-budget=6000 --screenshot=shots/home-desktop-full.png --window-size=1440,4200 "file://$PWD/index.html"
```

Headless Chrome won't lay out narrower than 500px. For a true 390px mobile shot, load the page in a 390px-wide `<iframe>` (scrolling off) inside a wrapper page, and screenshot the wrapper with `--allow-file-access-from-files --window-size=390,3000`.
