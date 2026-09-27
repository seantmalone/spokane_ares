# Option B: Carry the Message

Round 3 design system for the Spokane County ARES-ACS site. It is led by the round-1 concept "When the Lines Go Down" (12). Round 3 is the simplification pass: the owner found round 2 "way too much info", so every page is cut to its job and every fact has one home.

- **Files:** `assets/site.css` (the whole system), `assets/site.js` (progressive enhancement), `_chrome.html` (canonical head, sprite, header, members sub-nav, footer), the six round-3 pages (`index.html`, `how-it-works.html`, `about.html`, `members/*.html`), `assets/data.js` (round-3 shared data from `../_shared/assets/data.js`, unchanged), and `assets/img/` (unchanged).
- **Sources of truth:** `../_shared/placement-map.md` (where each fact lives, page budgets, anchors) and `../_shared/copy-*.md` (all wording). This file says how option B *looks*; those files say what a page *says*.
- **Page editors:** read **§0 "Simplification rules"** first, then §5 "Rules" and §8 "Section guidance". Copy chrome from `_chrome.html` verbatim.

---

## 0. Simplification rules (round 3: read before editing any page)

The owner's feedback on round 2: "All three of these have just way too much info. It's overload. Do another pass on each to simplify. Minimize repeating information unless it's really important." These rules turn that into editing practice for option B. They sit on top of the placement map and the copy files, and they win over anything later in this file.

1. **Say it once.** Every fact has one home (placement map §1). Anywhere else it gets at most one line or one inline link, and only where the map allows it. If you find yourself re-explaining the net, the join steps, ARES vs ACS, the task book or groups.io, link to the home instead.
2. **Take every word from `copy-*.md`.** Round 3 cuts; it never adds. A fact that is not in the copy file does not go on the page. Unused budget buys whitespace, not words. B's voice lives in type, the red line and the night-to-dawn light, not in extra sentences.
3. **Stay inside the budget.** Visible words in `<main>` count, including headings, table cells, button text, figure labels and `<details>` contents.

   | Page | Words | Max H2 | Also |
   |---|---:|---:|---|
   | `index.html` | 400 | 5 | ≤ 6 CTAs; ≤ 3,200px at 1440. **Built: 259 words, 3 H2, 2,902px** (all six pages: see §10) |
   | `how-it-works.html` | 1,200 | 6 | The story may not run more than one screen before `#nets` |
   | `about.html` | 2,800 | 9 | 9-entry TOC |
   | `members/index.html` | 300 | 3 | A switchboard, with no explanations |
   | `members/documents.html` | 900 | 7 + not-here | No lede; descriptions ≤ 8 words |
   | `members/exercises.html` | 700 | 5 | Next events first |

   Measure with `.venv/bin/python design/tools/measure_pages.py design/round3/option-b-lines-down`. The tool counts every `<button>` in `<main>` as a CTA, tap-to-define terms included (rule 9).
4. **Lead with the action.** Each page has one primary action and at most one filled `.btn--red` in `<main>` (Home's is in the hero). Everything else is a `.textlink`, a `.btn--ghost` or an inline link. Cut preambles, reassurance, "why it matters" bands, mission quotes, summary boxes and closing CTA bands.
5. **Every section must hold content.** Delete any section that only points somewhere else ("Ready to try it?", "Already a member?", "Everything for members", "On groups.io", agency bands, "Coming up" teasers).
6. **Use lists and tables, not paragraphs.** A table cell holds ≤ 12 words, and a list item holds one line. Use prose only where a step needs a full sentence.
7. **Use fewer, larger, calmer sections.** Aim for three to five sections per page, each with one idea and generous padding (`.section`, 96px). Put things side by side only when they really are parallel. Keep a photo only if it tells the reader something; a round-3 page needs one at most.
8. **B's devices keep their meaning.**
   - The red line is the message.
   - Amber numbered markers mark true sequences only: the join steps, the activation track, the readiness levels and the net run-sheet.
   - Peach appears only at the dawn hinge and in 4px top rules.
   - A page has at most one night-to-dawn-to-day progression.
   - Spend boldness in one place per page.
9. **Use tap-to-define sparingly.** A page gets at most three `.term` buttons, and only for words the reader needs in order to go on (on How it works: net control, ICS-213, Winlink). Use `<abbr title>` in headings and on Home. There is no glossary section.
10. **Use `<details>` only for reference, never to hide bloat.** The only planned uses are About's phone TOC and, optionally, the members sub-nav. Hidden words still count toward the budget.
11. **The chrome is fixed.** The header, members sub-nav and footer come from `_chrome.html`. Do not add utility bars, next-net strips, a "Copy radio settings" button in the header, per-page banners or footer facts.
12. **House style:**
    - Use 12-hour times everywhere, members tables included: "8:00 PM", "9:00 AM–noon". Never write "2000".
    - No `href="#"`. A document with no target prints its title as plain text with "Soon".
    - Show call signs only, never personal names.
    - `[verify]` becomes `data-verify="true"`, with no visible chip.
    - No arrow glyphs in link text; the `.ext` icon marks external links.
    - Every id you link to must be listed in placement map §2.4.
13. **Check before you hand off:**
    - Run the measure tool.
    - Look at the page at 1440 and at a true 390px viewport. Use DevTools device emulation: `--window-size=390` lays the page out at about 500px and then crops it.
    - Cold-load two of your page's deep links from another page and confirm each lands just under the header (`hashLanding()` in `site.js` handles web fonts and data widgets).

## 1. The idea

**Make people feel why it matters, then ask.**

Every page moves from **night to daylight**:

- **Night** frames the chrome (masthead, footer), Home's first two sections and the How it works story.
- **Daylight** carries the practical content.
- A **dawn** gradient is the hinge between the two, and the only place peach appears.

The subject supplies the visual language: one ICS-213 message carried hop by hop across the county on a night without phones.

| Device | Meaning | Where it appears |
|---|---|---|
| **Red message line** `#D0202A` | The message | The diagram arc; the `.redline` section rule; Home's What we do stops and join steps (§6.12); About's TOC active marker; library group rules |
| **Amber hop markers** `#FFC857` | A volunteer at a hop, or a step in a true sequence | Diagram markers 1-4; Home join steps 0-3; readiness levels 1-4; the "A scenario" pill; focus on dark. Plain amber dots (no numbers) mark Home's What we do stops, which are not a sequence |
| **ICS-213 paper** | White, 6px red top border, 4px radius, italic Crimson values | The story's message card (round 3 cut the Home interest form) |

**The one memorable thing** is the message-path diagram on How it works. Home has no diagram in round 3, so there the red line itself carries the idea: four stops strung on it at night, then the join steps hanging from it in daylight. Everything else stays quiet: hairlines rather than shadows, one filled CTA per page, and no decorative gradients outside the dawn.

**Voice:** Crimson Pro carries narration, headings and the long read. Schibsted Grotesk carries UI, labels, tables, forms and the members pages. The copy is plain and calm, in sentence case, with no eyebrows, no middle-dot metadata, no arrow glyphs and no ALL CAPS. The one exception is the literal "PHOTO NEEDED" label, which the README requires.

## 2. What changed from round 1 (judge feedback; round-2 history)

Round 3 later removed the utility bar, Home's scenario figure, join-path control and interest form, and the members sidebar (see §0 and §9).

| Round-1 criticism | Remedy in this option |
|---|---|
| Join path ~6,000px down | Home is a compact landing page: 4,954px at 1440 (see §10); the join path starts at **1,631px**; "Join the team" is in the hero and the header |
| Members skip the story every visit | Night utility bar on every page (next net from data.js, Copy radio settings, For Members); For Members in the primary nav; the members pages are light and story-free |
| Fear-first opening needs a PIO check | Home H1 is a capability line. "It's 2 AM…" opens How it works only. The "A scenario" pill is **drawn inside the SVG**, so any crop still says scenario. Scenario copy carries `data-verify` |
| Scrollytelling brittle to edit | Plain `<article class="step" data-state>` blocks. One `<symbol>` diagram, switched by `data-state`. One IntersectionObserver (~20 lines). Static small multiples below 900px and under reduced motion |
| Long empty dark stretches | Steps are at most 70vh, and every step has a visual |
| Practical half loses energy | Daylight sections keep the diagram language: red rule, amber markers, big Crimson numerals |
| Few photos | IMG_6574 in full colour at dawn; the 659px stills as night duotone (`.duo`, ≤660px); PHOTO NEEDED frames on slate |
| Visible placeholders | None. Uncertain facts carry `data-verify="true"`; one footer line reads "Mockup — content to be verified before launch"; `?verify=1` outlines them |
| Alert-level strip on Home | Removed. Alert levels are **explained** on How it works and in members reference only (`.levels`, neutral, no colours) |

## 3. Tokens

### Colour (CSS custom properties on `:root`)

| Token | Hex | Use | Contrast |
|---|---|---|---|
| `--night` | #0E1A33 | Header, hero, Home What we do, story, footer | — |
| `--slate-2` | #1F2C47 | Story gradient, PHOTO NEEDED fill | — |
| `--slate` | #33415C | Dark panels (Home's agency band was cut in round 3) | mist on it 6.5:1 |
| `--peach` | #F3C29B | Dawn gradient and 4px top rules **only**; never a card fill | ink on it 10.7:1 |
| `--paper` | #F6F7FA | Light page background (members workspace; the sub-nav row is white) | — |
| `--mist` | #C5CEDF | Body text on night | 10.9:1 |
| `--mist-2` | #A9B4C9 | Secondary text on night | 8.2:1 |
| `--ink` | #0E1A33 | Text on light | — |
| `--ink-2` | #3C4863 | Secondary text on light | 9.1:1 |
| `--off` | #56627C | Captions on light | 6.1:1 |
| `--rule` | #D9DEE8 | Hairlines on light | — |
| `--field` | #7D889E | Input borders (non-text) | 3.6:1 |
| `--red` | #D0202A | Message arc, primary pill (`--red-hover` #B51C25) | white on it 5.4:1 |
| `--red-ink` | #B01A23 | Red text and links on white | 7.0:1 |
| `--amber` | #FFC857 | Hop markers, scenario pill, focus on dark | 11.2:1 on night |
| `--amber-wash` | #FFF4D6 | "Next" rows and flags on light | — |
| `--amber-ink` | #6B4A00 | Amber-family text on light | 8.3:1 |

**Surfaces set tone.** A surface class sets the background and a set of tone variables: `--fg`, `--muted`, `--faint`, `--link`, `--line`, `--focus`, `--focus-gap` and `--term-line`. Components read those variables, so they work on night or day without modifiers, and the nearest surface wins.

- **Dark surfaces:** `.bg-night`, `.bg-slate-2`, `.bg-slate`, `.bg-storm` (night to slate), `.dark`, `.card--night`, `.radio-card`.
- **Light surfaces:** `.bg-day`, `.bg-paper`, `.bg-dawn` (peach to white), `.light`, `.card`, `.paper`, `.workspace`.

### Type

Load both families from Google Fonts. The link is in `_chrome.html`.

| Token / class | Setting |
|---|---|
| `.display` (`--fs-display`) | Crimson Pro 500, clamp(2.75rem, 1rem + 5.4vw, 5.5rem)/1.04. Home H1 only |
| `h1`, `.h1` | Crimson 500, clamp(2.5rem … 4.25rem)/1.04 |
| `h2`, `.h2` | Crimson 500, clamp(2rem … 3.25rem)/1.08 |
| `h3`, `.h3` | Crimson 500, clamp(1.45rem … 1.875rem)/1.15 |
| `h4`, `.h4` | Schibsted 600, 19px/1.3 (UI headings, card titles) |
| `.narr` | Crimson 400, 22-28px/1.45 (story narration) |
| `.lede` | Crimson 400, 19-24px/1.45 |
| `.prose` | Crimson 400, **20px/1.62**, 64ch (About body) |
| body | Schibsted 400, 17px/1.6 (16px under 640px) |
| `.small`, `.caption` | 14px |

Figures: `.tnum` gives tabular figures for number columns. Big display values (8:00 PM, 147.300) use proportional lining figures, because Schibsted's tabular set also spaces out ":" and ".".

### Space, shape, motion

- **Spacing:** 8px base (`--s1` to `--s7` = 8, 16, 24, 40, 64, 96, 128). `.wrap` = min(1240px, 100% − 64px), with 16px gutters under 640px.
- **Radius:** panels and the diagram 16px, cards 12px, paper 4px, pills 999px.
- **Chrome height:** `--mast-h` 72px (64px under 900px). The masthead is the only sticky element (the members sub-nav scrolls away), so every `[id]` on every page gets `scroll-margin-top: calc(var(--mast-h) + 24px)`. There is no `--util-h` in round 3.
- **Motion:** only on How it works. The diagram crossfades between states in 400ms (`--fade`) and the inactive story steps dim. Home has no scroll effects. `prefers-reduced-motion` removes all transitions and swaps the story for static small multiples.
- **Focus:** a 3px outline with a 2px gap. On dark surfaces it is amber with a night gap; on light surfaces it is ink with a white gap. It comes from the surface tone automatically.

## 4. Page skeleton

```
<head> … from _chrome.html §1 (fonts, assets/site.css, the inline 'js' + deep-link script)
<body class="page-how">                     members: class="members-page" data-root="../"
  [sprite]                                  _chrome §2, first thing in body
  [skip link + header]                      _chrome §3 (top-level) or §4 (members)
  <main id="main"> … </main>                members: _chrome §5 wraps main in .members-shell
  [footer]                                  _chrome §6 (top-level) or §7 (members variant, verbatim)
  <script src="assets/data.js"></script><script src="assets/site.js"></script>
```

- **Primary nav:** put `aria-current="page"` on the current section's link. Every members page marks "For Members". No classes are needed; CSS draws the amber bar from the attribute.
- **Members sub-nav:** put `aria-current="page"` on "This week", "Exercises & events" or "Documents & forms". "Task books" (to `../about.html#training-path`) and "groups.io" never carry it.

## 5. Rules for page builders (do not change)

1. **Use only `site.css`.** A small page-scoped `<style>` is allowed for layout that belongs to one page. Do not restyle the chrome, the tokens, the buttons, the diagram or the focus ring.
2. **Chrome is verbatim.** The sprite, header, members sub-nav and footer come from `_chrome.html` and differ only in paths and `aria-current`. Never add "Member login", a Terms link, any alert-status badge, or a utility or next-net strip.
3. **At most one filled CTA in `<main>`.** `.btn--red` is the primary (§0 rule 4). Use `.btn--ghost`, `.btn--light`, `.btn--ink` or `.textlink` for everything else. "Join the team" always links to `index.html#join` (from members pages, `../index.html#join`).
4. **Peach is never a card fill.** It appears only in `.dawn-sky`, `.bg-dawn` and 4px top rules (`.card--dawn`, `.netcard`, `.callout--plain`).
5. **Numbered or amber markers only for true sequences:** the message hops, the join steps, alert-level escalation and the net run-sheet.
6. **Everything that changes comes from data.js**, through a `data-widget` hook (§7). The static HTML inside the hook is the no-JS fallback and must match data.js as of 2026-09-26 (for example Home's "Next: Sat, Oct 10").
7. **Uncertain facts:** add `data-verify="true"` on the smallest element that holds the fact, and never show "TBD" or "verify". The footer line is already in the chrome.
8. **Never publish:**
   - county, hospital, SHARES or 800 MHz channels
   - the Hospital Net or its schedule
   - any simplex frequency, or the W7GBU-10 frequency
   - ACSFOG content (link to groups.io only)
   - personal phones or emails (role addresses only, with `data-verify`)
   - names or call signs on photos
   - the 1st-Tuesday staff meeting
   - the net roster (it lives in groups.io Files)
9. **Terms:** at most three tap-to-define terms per page (§6.9 and §0 rule 9). Use `<abbr title>` in headings and on Home. ACS = "Auxiliary Communications" (`data-verify`).
10. **Photos:** use only the files in `assets/img/`, and follow the size and treatment rules in §6.8. Use at most one photo or PHOTO NEEDED frame per page.
11. **Accessibility:**
    - one `h1`; no skipped heading levels
    - tables have a `caption` (it can be `.vh`) and `scope`
    - external links carry `class="ext"` plus a `.vh` "(opens site)" note
    - 44px targets
    - no horizontal scroll at 390px

## 6. Components

Class names are in `site.css`, and its header lists sections 1-31. The snippets show the minimum markup.

### 6.1 Masthead (chrome)

- **Markup:** `header.site-header > .masthead > .wrap.masthead__bar`, containing `.brand`, `nav.nav` (`button.nav__toggle[aria-expanded][aria-controls]` + `ul.nav__list#primary-nav`) and `a.btn.btn--red.site-header__join`.
- **Behaviour:** sticky at `top: 0`, and solid night on every page. It is the only sticky element on the site.
- **Under 1020px:** the list collapses behind "Menu", and Join stays visible in the bar. Under 380px the brand name is visually hidden and the seal carries the brand.
- **Without JS:** the list wraps under the bar.
- **Retired in round 3:** the utility bar (next net, Copy radio settings, For Members). The net lives on `how-it-works.html#nets`, the settings line on the members hub, and For Members in the nav.

### 6.2 Buttons and links

- `.btn` (pill, min 44px), with modifiers `.btn--red`, `.btn--ghost`, `.btn--light`, `.btn--ink`, `.btn--lg`, `.btn--sm` and `.btn--quiet`. Icons: `<svg class="icon" aria-hidden="true"><use href="#i-copy"/></svg>`.
- `.textlink`: the secondary action beside a pill (amber underline on dark, red-ink on light).
- `.ext`: the external-link marker, drawn as a small mask icon, not a glyph.

### 6.3 Pills, tags, badges

- `.scenario-pill`: amber on dark, auto-tinted on light. Pair it with `.scenario-note` ("Not a real event. It shows how the system is meant to work.") inside `.scenario-line`.
- `.tag`: eligibility, type or "Optional", in sentence case. Modifiers:
  - `.tag--line` (dashed)
  - `.tag--amber` ("Your next step")
  - `.tag--open` (open net-control slot, styled as an invitation)
  - `.tag--now` ("Happening now")
- `.hop`: a 28px amber numbered circle. `.hop--ring` is the outline version.
- `.fmt`: the format badge (DOCX, PDF, Web page).
- `.src`: the source badge, with modifiers:
  - `.src--official`: "Official: FEMA", "ARRL", "Spokane County"
  - `.src--ours`: "Spokane County ARES-ACS"
  - `.src--section`: EWA Section / ag7qp.com
  - `.src--groupsio`: "Members-only on groups.io"
  - `.src--page`, `.src--recovering`, `.src--historical`

### 6.4 Section rules and surfaces

- `.redline` (`<div class="redline" role="presentation"></div>`): the message line as a divider, with an amber node at the start and an amber ring at the end.
- `.rule`: a plain hairline.
- `.dawn-sky` is the night-to-peach strip. Modifiers: `--from-slate` (after the story gradient), `--tall`, and `.dawn-sky__line` for one italic line ("By morning, the message has been handled."). Follow it with a `.bg-dawn` section.

### 6.5 Layout

- `.wrap` and `.wrap--narrow`
- `.section` (96px) and `.section--tight` (64px)
- `.section-head`
- `.split` (5/7), `.split--7-5`, `.split--even`, `.split--center`
- `.grid-2`, `.grid-3`, `.grid-4`
- `.cluster`, `.stack`, `.flow`, `.flow-s`
- `.sticky`, `.measure` (64ch)

All splits stack under 900px.

### 6.6 Cards, panels, callouts, quotes

- `.card` (white, hairline, 12px). Modifiers: `.card--dawn` (peach top rule), `.card--red`, `.card--night`. Title: `.card__title`.
- `.panel` (members hub box) with `.panel__head`.
- `.tile` inside `ul.quick`: the members quick links, with an icon, `.tile__label` and `.tile__hint`. Four columns, two under 1100px, one under 640px.
- `.callout`: add `.callout--plain` for "In plain English" or `.callout--legal` for the boxed legal basis; `.callout--night` is the dark variant. Title: `.callout__title`.
- `.quote`, and `.pullquote` (Crimson italic 28px with a red left rule; for Dean Bula and Standing Order 1b).
- `.deflist` (+ `.deflist--2`), `.checklist` (visual boxes only), `.marks` (affiliation marks).

### 6.7 Net card, radio card

**Public net card** (`.netcard`): white with a peach top rule. The `.netcard__grid` holds When / Repeater / Settings, followed by `.netcard__next` and `.netcard__actions`:

```html
<div class="netcard" data-widget="net-card">
  <dl class="netcard__grid"><div><dt>When</dt><dd>8:00 PM<small>Every Tuesday</small></dd></div>…</dl>
  <div class="netcard__next">
    <p><b data-slot="label">The net:</b> <span data-slot="when">every Tuesday, 8:00 PM</span>. <span data-slot="netcontrol">…groups.io calendar link…</span>.</p>
    <p class="netcard__note" data-slot="note" hidden></p>
  </div>
  <div class="netcard__actions">
    <button class="btn btn--ink btn--sm" type="button" data-copy>…Copy radio settings</button>
    <button class="btn btn--ghost btn--sm" type="button" data-ics="weekly-net">…Add to calendar</button>
  </div>
</div>
```

**Members radio card** (`.radio-card[data-widget="net-card"][data-nc-label="false"]`) is dark. Round 3's hub uses a one-line copyable settings row above the rota instead; keep this card only if the hub editor needs it:

- `.radio-card__freq` shows a big Crimson "147.300" with `<small>MHz</small>`.
- A `.settings-grid` `<dl>` holds Repeater, Frequency, Offset, Tone, Net control (`data-slot="netcontrol"`), and Next or This week (`data-slot="when"`).
- `p.flag[data-slot="note"]` carries the simplex, Winlink or GMRS flag.
- The same two buttons follow.

### 6.8 Photos and PHOTO NEEDED

- **`figure.photo`** with `img` and `figcaption`:
  - IMG_6574 (`IMG_6574-2000w.jpg`) may run large, in full colour.
  - Every people photo, and IMG_6574 (the portable-repeater question), gets `data-verify="true"`.
- **`.duo`**: a night-blue duotone wrapper for the 659px stills (`8091bb_cfac…` mast team, `Operating-in-VAN_4.jpg` comm trailer), capped at 660px: `<div class="duo"><img …></div>`. `.photo--small` also caps width at 660px.
- **Parade operator:** use `operator-headset-badge-blurred.jpg` only, in natural colour, and at most 660px wide.
- **`.photo-needed`** (add `.photo-needed--wide` for 16:9): `<div class="photo-needed"><b>PHOTO NEEDED</b> EOC radio room during an exercise, wide</div>`. Use the shot list in `assets/img/README.md`.

### 6.9 Tap-to-define term

```html
<button class="term" type="button" aria-expanded="false" aria-controls="t-eoc">EOC</button><span class="term-def" id="t-eoc" role="note">Emergency Operations Center: where the county coordinates its response.</span>
```

- **Behaviour:** without JS the definition shows inline in parentheses. With JS it opens under the line on tap, and Escape closes it.
- **Ids:** must be unique per page (`t-…`).
- **Definitions:** take them from `content-about.md` §Glossary.
- **Placement:** do not put a term inside a heading. It can sit inside a `<b>` label.

### 6.10 Tables

- `.table` (Schibsted 15.5px) with `.table--dense`. Row states: `.is-next` (amber wash with an amber rail) and `.is-past` (faint).
- **`.table--stack`:** rows become labelled cards at 640px and below. Put `data-label` on every `<td>`; the first cell stays unlabelled as the row title.
- **`.compare`:** the About ARES vs ACS table, with emblems in the column heads. It stacks into labelled cards at 640px; give each `<td>` a `data-label` of "ARES" or "ACS".
- Wrap wide tables in `.table-wrap`.

### 6.11 Events, timeline

**Events list** (`ul.events[data-widget="upcoming"]`):

- Each row: `.event` = `.date-slab` (`.date-slab__mon`, `__day`, `__dow`; `--range` for multi-day) + `.event__body` (`.event__title`, `.event__meta`).
- A multi-day event in progress gets `.tag--now` "Happening now".

**Timeline** (`ol.timeline`, members exercises):

- Each `li` = a `.date-slab` + a `.timeline__card` (h3, then a `dl.timeline__facts` with What / Where / Bring / Sign up / Report to).
- `li.is-now` highlights the slab.
- Put `.month-head` above each month group.

### 6.12 Stops and steps (Home)

Both hang on the red message line. `site.css` §20.

**What we do: `ul.stops`** (on night). Four stops on one horizontal line with plain amber dots, never numbers, because they are not a sequence. The line ends in the amber ring. Under 900px it turns vertical and the stops hang off its left side.

```html
<ul class="stops">
  <li class="stop"><strong class="stop__label">Hospitals</strong><span class="stop__text">Backup radio equipment, kept working.</span></li>
</ul>
```

**Join in four steps: `ol.steps`** (on day). A true sequence, so each step gets an amber numbered hop marker (56px, Crimson numeral) on a vertical red line. The segment into the optional step is dashed, and the optional step's marker is a ring. Each step has a title, one line and at most one `.textlink` action. `.steps__note` holds a small, rule-topped note (Home: "What ACS asks").

```html
<ol class="steps">
  <li class="steps__item">
    <span class="steps__n" aria-hidden="true">1</span>
    <div>
      <div class="steps__head"><h3 class="steps__title"><span class="vh">Step 1: </span>Join <abbr title="Amateur Radio Emergency Service">ARES</abbr></h3></div>
      <p class="steps__text">One form.</p>
      <p class="steps__action"><a class="textlink" href="about.html#ares-path">How to apply</a></p>
    </div>
  </li>
  <li class="steps__item steps__item--opt">…<span class="tag tag--line">Optional</span>…<p class="steps__note">…</p></li>
</ol>
```

- `ol.steps` suits any short true sequence on a light surface, such as How it works' activation track.
- Round 3 retired the round-2 `.path`, `.seg` segmented control, `?from=` highlighting and "already on the team" card, with their JS.

### 6.13 Forms and search

- **Fields:** `.field` (label + `.input`; `.input` is Crimson italic 20px, the paper-form voice; `.input--sans` for search-style fields), `.fieldset`, `.form-note`.
- **Search:** `.search` with `.search__field` (an icon + input) and `.search-preview` (live matches).
- **There are no other forms.** Round 3 cut the Home interest form (placement map #72), and with it `.paper`, `.radios`, `.hp` and `.form-done`. People join through `index.html#join`, and join@ is listed once, on `about.html#contact`.

### 6.14 ICS-213 paper and ICS-309 log line

```html
<div class="ics213" role="group" aria-label="Example ICS-213 message, part of the scenario">
  <div class="ics213__head"><b>ICS-213</b><span>General message</span></div>
  <dl><div class="half"><dt>To</dt><dd>EOC Logistics</dd></div><div class="half"><dt>From</dt><dd>Shelter Manager</dd></div>
      <div class="full"><dt>Subject</dt><dd>Resident needs oxygen</dd></div>
      <div class="full"><dt>Message</dt><dd>One resident uses home oxygen. Concentrator has no power. Request transport or supply.</dd></div></dl>
  <p class="ics213__foot">A scenario. The message is illustrative.</p>
</div>
```

`.logline` is an ICS-309 row on a dark panel: `.logline__head` + a table with Time / From / To / Subject. Put the subject in `<em>` so it renders in italic Crimson.

### 6.15 Message-path diagram

The scene exists once, as `<symbol id="msgpath-scene">` in the sprite. Each instance is:

```html
<svg class="msgpath" data-state="relay" viewBox="0 0 560 400" role="img" aria-labelledby="…"><title>…</title><desc>…</desc><use href="#msgpath-scene"/></svg>
```

- **States (cumulative):**
  - `calm`: power on, cell up
  - `down`: lines down, cell out, rain
  - `shelter`: shelter outlined, hop 1
  - `send`: red arc shelter → W7GBU → EOC, hops 1-2, EOC outlined
  - `relay`: EOC → hospital arc, hops 3-4
  - `dawn`: rain off, peach glow
  - no attribute = `relay`
- **Mechanism:** each state sets custom properties (`--mp-*`) that inherit into the `<use>` copy, so the layers switch without JS.
- **In the picture:** the "A scenario" pill is drawn in the top-right, the place labels are SVG text, and the four amber hop markers are drawn at the hops.
- **Wrapper:** use `figure.msgpath-panel`. Beside or below it, `ol.msgpath-legend` (+ `.msgpath-legend--2`) lists the four roles, and `.msgpath-caption` reads "The red line is the message. Each amber number is a volunteer with a radio."
- **Mini instances** (story steps): `svg.msgpath.msgpath--mini[data-state]` inside `div.story__mini[aria-hidden="true"]`. The labels scale up for phones.

### 6.16 Story (How it works)

This is the WordPress block pattern / SSG partial. Each step is an `article` with a heading, paragraphs, one visual and one mini diagram:

```html
<section class="story bg-storm" id="follow-a-message" data-story aria-labelledby="story-title">
  <div class="wrap">
    <div class="story__grid">
      <div class="story__steps">
        <article class="step" data-state="down" data-status="Power lines down. Cell service out." data-lit="0">
          <h3>First the power goes. Then the phones.</h3>
          <p>…narration…</p>
          <!-- one visual: .ics213 | .logline | .photo | .duo | .photo-needed (optional) -->
          <div class="story__mini" aria-hidden="true"><svg class="msgpath msgpath--mini" data-state="down" viewBox="0 0 560 400"><use href="#msgpath-scene"/></svg></div>
        </article>
        …
      </div>
      <figure class="story__figure">
        <div class="msgpath-panel">
          <p class="msgpath-status"><span data-story-status>…</span></p>  <!-- no HTML pill here: the SVG draws "A scenario" in the picture -->
          <svg class="msgpath" data-story-figure data-state="calm" viewBox="0 0 560 400" role="img" aria-labelledby="…"><title>…</title><desc>…</desc><use href="#msgpath-scene"/></svg>
        </div>
        <ol class="msgpath-legend msgpath-legend--2"><li data-hop="1">…</li>…</ol>
        <figcaption class="msgpath-caption">A scenario. Every hop on this path is a trained volunteer, not a machine.</figcaption>
      </figure>
    </div>
  </div>
</section>
```

- **Step attributes:**
  - `data-state`: the diagram state for the step.
  - `data-status`: the status line shown above the diagram.
  - `data-lit`: how many legend hops light up.
- **Editing:** editors can reorder or add steps without touching JS.
- **Scrolly mode:** runs only with JS, at 900px and up, and with motion allowed. In that mode the steps are at most 70vh, the figure is sticky, and the minis are hidden. Otherwise each step shows its own mini.

### 6.17 Numerals, posts, alert levels

- **`ol.numerals`** ("What it takes", on `.bg-dawn`): each `li` has `.numerals__n` (a big Crimson numeral in red-ink), `.numerals__label` and `.numerals__sub`.
- **`ul.posts`** ("Every hop is a post"): each `li.post` has a `.post__head` (optional `.hop` for the four story roles + h3), a `p`, and `.tags` with `.tag` eligibility labels.
- **`ol.levels`** (alert levels, **explained only**): each `li.level` has a `.level__head` (`.hop` + `p.level__name`, with the level word in `<small>`) followed by a `ul`. The levels have no colours and no "current" marker.

### 6.18 Long read (About)

**Layout:** `div.longread` holds `aside` (TOCs) and `div.longread__main.prose`. Columns:

| Width | Columns |
|---|---|
| Under 1024px | One column |
| 1024px and up | 232px TOC + text |
| 1200px and up | 220px TOC + 64ch text + 260px sidenote rail |

**Prose elements:**

- `.dropcap` on the first paragraph of chapter 1.
- `h2[id]` with `a.h-anchor`, and `a.back-to-top` after each chapter.
- `span.sidenote[role=note]` inside a paragraph: it floats into the rail at 1200px and up, and shows inline below that.
- `.callout--legal`, `.pullquote`, `.compare`.
- `.faq` > `details` / `summary`.
- `dl.glossary`.
- `.chapter-meta` for "Last reviewed" (`data-verify`).

**TOC:**

- `nav.toc.toc--side[data-toc]` with `.toc__title` and an `ol` of links. The active link gets a 3px red rule.
- `details.toc-mobile[data-toc-mobile]` holds a second `nav.toc[data-toc]`. In round 3 it is **closed by default** on phones (placement map §3.3).
- `button.btn.btn--ink.contents-fab[data-contents-fab][hidden]` reopens it on phones.

### 6.19 Documents library

```html
<div data-doc-library>
  <form class="search" role="search" data-doc-search action="documents.html" method="get">…input name="q"…</form>
  <ul class="chips"><li><button class="chip" type="button" data-cat-chip="" aria-pressed="true">All</button></li><li><button class="chip" type="button" data-cat-chip="forms" aria-pressed="false">Forms <span class="chip__n">14</span></button></li>…</ul>
  <p class="doc-count" aria-live="polite" data-doc-count></p>
  <section class="doc-group" id="forms" data-doc-group>
    <div class="doc-group__head"><h2>Forms</h2><p>…blurb…</p></div>
    <table class="table table--stack doc-table"><caption class="vh">Forms</caption><thead>…Document / What it's for / Format / Updated / Source…</thead>
      <tbody><tr id="ics-213" data-doc="ics-213"><td><a class="doc-title ext" href="…">ICS 213 General Message<span class="vh"> (opens FEMA)</span></a><span class="doc-note">…</span></td><td data-label="For">…</td><td data-label="Format"><span class="fmt">PDF</span></td><td data-label="Updated">v3</td><td data-label="Source"><span class="src src--official">Official: FEMA</span></td></tr></tbody></table>
  </section>
  <div class="card doc-empty" data-doc-empty hidden>Nothing matches “<span data-doc-empty-q></span>”. … <a href="#" data-doc-clear>Clear search</a></div>
</div>
```

- **Rows:** one per document, generated from `SPOKARES.documents`. The row `id` and `data-doc` are the data.js `id`.
- **Filtering:** JS filters with `SPOKARES.fn.documents`, reads and writes `?q=` and `?cat=`, pre-selects Forms on `#forms`, and announces the count.
- **Status legend:** cut in round 3 (placement map #63). Each row names its source in plain words ("FEMA", "ag7qp.com", "groups.io, members").

### 6.20 Members shell and sub-nav

```html
<div class="members-shell">
  <nav class="member-nav" aria-label="Members">
    <ul class="wrap member-nav__list">
      <li><a href="index.html" aria-current="page">This week</a></li>
      <li><a href="exercises.html">Exercises &amp; events</a></li>
      <li><a href="documents.html">Documents &amp; forms</a></li>
      <li><a href="../about.html#training-path">Task books</a></li>
      <li><a class="ext" href="https://spokaneares-acs.groups.io/g/main" rel="noopener">groups.io<span class="vh"> (opens groups.io, members only)</span></a></li>
    </ul>
  </nav>
  <main id="main" class="workspace">…</main>
</div>
```

- **Layout:** one white row under the night masthead, at every width. The current item gets an amber underline. The row is not sticky.
- **Phones:** at 390 it wraps to two rows with all five items visible. There is no scroller and no disclosure.
- **Workspace:** direct children of `.workspace` are centred at the site width (`var(--wrap)`), on paper.
- **Workspace pieces:** `.page-head` (`.crumbs` are optional, since the sub-nav already says where you are), `h1`, `.page-head__meta[data-widget="asof"]`, `.workspace-section`, `.panel`.
- **Retired:** the night sidebar, the group labels (Operate / Prepare / Reference), the next-net readout, and the dead `#` items (Nets, Winlink, Repeaters, Go-kits, Contacts).

### 6.21 Footer

The footer is minimal (placement map §2.2), with 33 visible words:

- **Top row:** the seal (a link home), then How it works, About ARES & ACS, For Members, groups.io, Contact (to `about.html#contact`, the one contact line) and "Privacy (coming)" as plain text.
- **Bottom row:** "Volunteers, not first responders. In an emergency, call 911." (`data-verify`), and "© Spokane County ARES-ACS. Mockup — content to be verified before launch".
- **Never add** the address, the meeting schedule, a mission line, "Formerly ARES/RACES", affiliation marks, audience columns, or Join, Forms or Agreements links.

## 7. JavaScript hooks (site.js)

Every widget is one function and one hook, and maps to one block pattern or partial. Pages work with site.js absent.

| Hook | Renders | Options |
|---|---|---|
| `[data-widget="net-card"]` | Slots: `label`, `when`, `netcontrol`, `note` (simplex / Winlink night + assignment / GMRS; hidden when empty). Reads "On the air now" Tuesday 8:00-9:00 PM | `data-nc-label="false"` |
| `[data-widget="upcoming"]` | `.event` rows; past items drop off; in-progress multi-day events read "Happening now" | `data-days`, `data-limit`, `data-types` (exercise, meeting, training, public-service, on-air, net, gmrs), `data-exclude` (id substrings, e.g. `shares`), `data-merge="workshops"`, `data-link` (relative to `data-root`) |
| `[data-widget="rota"]` on `<tbody>` or `<ul data-variant="list">` | Net-control rota: Tuesday, call sign (or an "Open" tag), and a Note from data.js ("Simplex", "Winlink night" linked to `members/exercises.html#winlink-assignments`, "GMRS net, 7:30 PM"). The next row gets `.is-next`. Call signs only | `data-weeks` (default 5), `data-past` |
| `[data-widget="winlink-next"]` | Slots: `date`, `task`, `form` | — |
| `<tbody data-widget="winlink-table">` | Assignment rows | `data-past` |
| `[data-widget="asof"]` | "Schedule as of Sat, Sep 26, 2026." | — |
| `[data-widget="next-meeting"][data-id]` | Next date for a meeting id | — |
| `button[data-copy]` | Copies `SPOKARES.radio.copyText`, or `data-copy-text`, with a textarea fallback and a toast | — |
| `button[data-ics="weekly-net"]` / `[data-ics="event"][data-event-id]` | A .ics built in the browser (weekly RRULE, or one event) | — |
| `.term` | Tap-to-define | — |
| `[data-story]` | The story (§6.16) | — |
| `nav[data-toc]`, `details[data-toc-mobile]`, `[data-contents-fab]` | TOC active section and the mobile Contents button | — |
| `[data-doc-library]` | Library filter (§6.19) | — |
| `form[data-doc-preview]` + `[data-preview-list]` | Live top-5 matches on the members hub | — |
| `[data-filter-scope]` with `button[data-filter]` and `[data-type]` items | Generic chip filter (exercises page; round 3 does not need it) | — |
| *(automatic)* `hashLanding()` | On a cold load with `#id`, waits for fonts, images and widgets, then corrects the landing to the element's `scroll-margin-top`, unless the reader has scrolled. Needs the head one-liner from `_chrome.html` | — |

**Times:** every hook prints 12-hour times ("8:00 PM", "5:00 PM to 9:00 PM"). The round-2 `data-format="24"` option is gone.

**Demo parameters:**

- `?today=2026-09-29&now=20:15` shows "On the air now".
- `?today=2026-10-02` shows an open slot.
- `?verify=1` outlines every `[data-verify]`.
- `?q=` and `?cat=` pre-filter the library.

## 8. Section guidance for the other pages (round 3)

Wording, order, ids and budgets come from `../_shared/copy-<page>.md` and placement map §3. This section only says which B components to use. Anything not listed is cut.

### How it works (`how-it-works.html`, `body.page-how`; 1,200 words, 5 H2)

- **Open (≤ one screen at 1440):** H1 "How it works", then B's story in place of the lede: the opener line, the four-step "Follow one message" figure and one transition line (copy file, B lines). Build it as one static `figure.msgpath-panel` (`data-state="relay"`) with the 4-hop `.msgpath-legend`, on night, followed by a short `.dawn-sky`. The scrollytelling `[data-story]` is optional, and only if all its steps together stay within one screen at 1440. Keep the in-SVG "A scenario" pill and `data-verify` on the scenario. On phones (≤ 640px) the figure is hidden so the story also fits one screen at 390×844; the hop list and the scenario note carry it there.
- **Daylight chapters** on `.bg-day` or `.bg-paper`, each opened by a `.redline`:
  1. `#nets`: one sentence, a settings box (`.netcard` without the Add to calendar button), a 6-step run-sheet (`ol.steps` or `.hop` list), and `#other-nets` as 3 lines.
  2. `#activation`: the intro with the August 2023 sentence (`id="real-world"`), a 7-step track (`ol.steps`), `#alert-levels` as `.levels`, and `#standing-order-1b` as 2 lines plus the link, with no pull quote.
  3. `#message-handling`: backups line, `#ics-213` "Forms and logs", `#logs` list, `#winlink` 3 sentences.
  4. `#exercises`: 4 items with no dates, plus the link.
  5. `#roles`: the 8-row `.table.table--stack`.
- **Close:** one line, "Ready? Join the team" (a `.btn--red` is allowed here, as the page's one filled CTA).
- **Cut:** the dawn H2 "Radio still works…", `ol.numerals` "What it takes", `ul.posts` "Every hop is a post", public service, "Where we work", the separate "When it was real", the photo frames and the ICS-309 log line.

### About ARES & ACS (`about.html`, `body.page-about`; 2,800 words, 9 H2)

- **Shell:** the long read (§6.18) with the 9-entry TOC: a sticky side column at ≥ 1024px and a closed `details.toc-mobile` on phones. The page head holds the H1, a one-sentence lede and `.chapter-meta` "Last reviewed…" (`data-verify`).
- **Components:**
  - `#ares-acs`: 4 one-line definitions as a `.deflist`, plus the "What we are not" `.callout--plain`.
  - `#legal-basis`: a 4-row `.table` plus a `.callout--legal`.
  - `#leadership`: the structure figure plus tables.
  - `#acs-task-book`: one compact table.
  - `#licensing`: the exam `.table`.
  - `#w7gbu`: one `.pullquote`.
  - `#contact`: a 3-row table.
  - The drop cap on chapter 1 is optional. Sidenotes only for sources, and sparingly.
- **Cut:** the `.compare` table, FAQ, glossary, "At a glance", "Where and when we meet", "Joining, step by step", "What it takes", "How activations work", the mission quote and the Standing Order 1b pull quote.

### For Members (`members/*.html`; `body.members-page`, `data-root="../"`)

Members pages are light, dense and story-free, with no lede paragraphs.

- **Hub** (`members/index.html`, 300 words, 3 H2):
  - H1, then an as-of line (`data-widget="asof"`), then search (`form[data-doc-preview]`).
  - `#this-week`: the next two exercises (`ul.events[data-widget="upcoming"][data-types="exercise"][data-limit="2"]`) and the next meeting (`[data-widget="next-meeting"]`).
  - `#rota` "Tuesday net": one copyable line ("8:00 PM · W7GBU 147.300 MHz, +600 kHz, 100 Hz" + `button[data-copy]`), then `table.table` with `tbody[data-widget="rota"][data-weeks="5"]` and the "Open slot?" line.
  - `#quick-links`: 4 `ul.quick` tiles with no hints.
- **Documents** (900 words): search and chips (All, Most used, 7 categories), then 7 `.doc-group`s. Each is a compact `.table--stack` with Title (link), Format and version, and Source; descriptions ≤ 8 words sit in `.doc-note`. `#not-here` is 2 sentences. No lede, no status key and no Most-used section.
- **Exercises** (700 words):
  - `#next-up`: the "Bring" line, then the WSDOT and SET `.card`s, then the "After" line.
  - `#upcoming`: 6 rows (`.events` or a `.table`).
  - `#winlink-assignments`: `tbody[data-widget="winlink-table"]`.
  - `#public-service`: a 4-row table.
  - `#past`: 8 rows.
  - No chip filter, no timeline and no year-at-a-glance.
- **Times:** 12-hour on members pages too. Round 2 allowed "2000" there; round 3 does not.

## 9. Content decisions in Home (round 3)

The copy is `../_shared/copy-index.md` (B lines), placed exactly as the placement map §3.1 says. Home is for newcomers.

| Order | Section | Surface | B treatment |
|---|---|---|---|
| 1 | Hero (H1) | Night, sunset photo | Display H1 "When other systems go down, Spokane County still has a voice."; the lede; **Join the team** (`.btn--red`) and **See how it works** (`.btn--ghost`); the small agency line linking `about.html#for-agencies` |
| 2 | What we do (`#what-we-do`) | Night | The four one-liners as `ul.stops` on one red line. No scenario figure: the story is told once, on How it works |
| – | Dawn hinge | `.dawn-sky` | The page's one night-to-day turn |
| 3 | Come visit (`#visit`, alias `#this-week`) | `.bg-dawn` | "From home" in a white `.card--dawn`; "In person" with the address and two meetings, each with "Next:" from data.js (`[data-widget="next-meeting"]`) |
| 4 | Join in four steps (`#join`, alias `#what-it-takes`) | Day | H2 on the left, `ol.steps` on the right; step 3 is optional, with the "What ACS asks" note |

- **CTAs (6):** Join the team, See how it works, How we work with agencies, How to get licensed, How to apply, The ACS path. The step-2 links ("Tuesday net" and "groups.io list") are inline links on existing words. The measure tool counts the 2 buttons (it does not count `.textlink`).
- **`data-verify` on Home:** the address block, the Winlink workshop clause, "evenings" (Third Thursday), the ACS expansion `<abbr>`, and the footer safety line.
- **Cut from Home (round 2 → 3):**
  - the utility bar and the scenario figure "One message, four volunteers"
  - the "Where are you starting from?" control and the interest form
  - the quote and "Not licensed yet?" cards
  - the net card (offset, tone, net control, Copy, Add to calendar)
  - the photo, "Coming up" and the agency band with "Who we serve"
  - the scope line (the footer carries it)

## 10. QA notes (round 3)

**Home**, measured with `design/tools/measure_pages.py` on 2026-09-26:

| Check | Budget | Result |
|---|---|---|
| Visible words in `<main>` | 400 | **259** |
| H2s | 5 | **3** |
| CTAs by the tool (buttons) / by the map (button-weight links) | 6 | **2 / 6** |
| Height at 1440 | ≤ 3,200px | **2,902px** |
| Height at 390 | — | 3,381px in a true 390 viewport (the tool's 390 iframe reports 3,511px) |
| First viewport at 1440×900 | — | H1, lede, both buttons and the agency line; "What we do" starts above the fold |
| Horizontal scroll at 390 and 360 | none | none |
| Net control, offset, tone, rota, "2000", `href="#"` on Home | none | none |

**Chrome, site-wide:**

- Every page has the new header, footer and (members) sub-nav, and no utility bar. There are no JS errors on any of the six pages.
- Deep links cold-loaded from another page (`index.html#join`, `#visit`, `about.html#for-agencies`, `#contact`, `#licensing`, `how-it-works.html#activation`, `#nets`, `members/documents.html#forms`) land within 0px of their `scroll-margin-top` at 1440 and at 390.
- All six pages are rebuilt (integrated 2026-09-26). Words in `<main>`: Home 259, How it works 909, About 1,274, hub 124, Documents 549, Exercises 427 (site 3,542; round 2 was 17,246). Every page is within budget; `_metrics-after.json` has the full run.
- Integrator QA on all six pages at 1440 and a true 390: one H1 each, no console errors, no horizontal scroll, every internal link and id resolves over `file://`, no `href="#"`, identical chrome with the right active states, the mobile menu opens and closes (Escape too), all five sub-nav items visible at 390 (two rows), a 3px focus ring on every Tab stop, and 33 deep links cold-loaded from Home land at their `scroll-margin-top` (0px off) unless the page is too short to scroll that far.
- **How it works on phones (≤ 640px):** the message-path figure is hidden so the story stays within one screen; `#nets` starts at 836px in a 390×844 viewport (1,114px with the figure). The hop list tells the same four steps and the scenario note stays above it. At 1440 the figure stays and `#nets` starts at 877px.

**Screenshots:**

- `shots/<page>-desktop-full.png` (1440×3400) and `shots/<page>-mobile.png` come from the stock headless commands, for `home`, `how`, `about`, `members`, `documents` and `exercises`; `shots/home-desktop-fold.png` is 1440×900. The stock `--window-size=390` command lays the page out at about 500px and crops it, so its right edge is cut off.
- `shots/<page>-mobile-390-true.png` is a true 390×844 full-page layout (DevTools device emulation). Use it for mobile review. `home-` and `members-mobile-390-menu.png` show the open menu.
- `shots/how-`, `about-` and `documents-desktop-all.png` are full-length 1440 captures of the pages taller than 3,400px.
