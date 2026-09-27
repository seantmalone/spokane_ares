# Option B: Carry the Message

Round 2 design system for the Spokane County ARES-ACS site. It is led by the round-1 concept "When the Lines Go Down" (12).

- **Files:** `assets/site.css` (the whole system), `assets/site.js` (progressive enhancement), `_chrome.html` (canonical head, sprite, header, members sub-nav, footer), `index.html` (Home, built), `assets/data.js` (shared data, copied unchanged), and `assets/img/` (copied unchanged).
- **Page builders:** read §5 "Rules" and §8 "Section guidance" before writing a line. Copy chrome from `_chrome.html` verbatim, between its `<!-- NAME:START -->` and `<!-- NAME:END -->` markers.

---

## 1. The idea

**Make people feel why it matters, then ask.**

Every page moves from **night to daylight**:

- **Night** frames the chrome (utility bar, masthead, footer, members sidebar) and the story.
- **Daylight** carries the practical content.
- A **dawn** gradient is the hinge between the two, and the only place peach appears.

The subject supplies the visual language: one ICS-213 message carried hop by hop across the county on a night without phones.

| Device | Meaning | Where it appears |
|---|---|---|
| **Red message line** `#D0202A` | The message | The diagram arc; the `.redline` section rule; the Home join path (the steps sit on the line); About's TOC active marker; library group rules |
| **Amber hop markers** `#FFC857` | A volunteer at a hop, or a step in a true sequence | Diagram markers 1-4; join-path numerals 0-3; alert levels 1-4; the "A scenario" pill; focus on dark |
| **ICS-213 paper** | White, 6px red top border, 4px radius, italic Crimson values | The story's message card, and the site's one form ("Tell us you're interested") |

**The one memorable thing** is the message-path diagram, with the paper form as its echo. Everything else stays quiet: hairlines rather than shadows, one filled CTA per section, and no decorative gradients outside the dawn.

**Voice:** Crimson Pro carries narration, headings and the long read. Schibsted Grotesk carries UI, labels, tables, forms and the members pages. The copy is plain and calm, in sentence case, with no eyebrows, no middle-dot metadata, no arrow glyphs and no ALL CAPS. The one exception is the literal "PHOTO NEEDED" label, which the README requires.

## 2. What changed from round 1 (judge feedback)

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
| `--night` | #0E1A33 | Header, utility, hero, story, footer, members sidebar | — |
| `--slate-2` | #1F2C47 | Story gradient, PHOTO NEEDED fill | — |
| `--slate` | #33415C | Agency band | mist on it 6.5:1 |
| `--peach` | #F3C29B | Dawn gradient and 4px top rules **only**; never a card fill | ink on it 10.7:1 |
| `--paper` | #F6F7FA | Light page background (members workspace) | — |
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
- **Chrome heights:** `--util-h` 44px, `--mast-h` 72px (64px under 900px). Every `[id]` gets `scroll-margin-top` to clear the sticky masthead.
- **Motion:** only on How it works. The diagram crossfades between states in 400ms (`--fade`) and the inactive story steps dim. Home has no scroll effects. `prefers-reduced-motion` removes all transitions and swaps the story for static small multiples.
- **Focus:** a 3px outline with a 2px gap. On dark surfaces it is amber with a night gap; on light surfaces it is ink with a white gap. It comes from the surface tone automatically.

## 4. Page skeleton

```
<head> … from _chrome.html §1 (fonts, assets/site.css, the inline 'js' class script)
<body class="page-how">                     members: class="members-page" data-root="../"
  [sprite]                                  _chrome §2, first thing in body
  [skip link + header]                      _chrome §3 (top-level) or §4 (members)
  <main id="main"> … </main>                members: _chrome §5 wraps main in .members-shell
  [footer]                                  _chrome §6 (top-level) or §7 (members variant, verbatim)
  <script src="assets/data.js"></script><script src="assets/site.js"></script>
```

- **Primary nav:** put `aria-current="page"` on the current section's link. Every members page marks "For Members". No classes are needed; CSS draws the amber bar from the attribute.
- **Members sub-nav:** put `aria-current="page"` on the current item. "Forms" is a filter preset of the library and never carries it.

## 5. Rules for page builders (do not change)

1. **Use only `site.css`.** A small page-scoped `<style>` is allowed for layout that belongs to one page. Do not restyle the chrome, the tokens, the buttons, the diagram or the focus ring.
2. **Chrome is verbatim.** The sprite, header, members sub-nav and footer come from `_chrome.html` and differ only in paths and `aria-current`. Never add "Member login", a Terms link or any alert-status badge.
3. **One filled CTA per section.** `.btn--red` is the primary. Use `.btn--ghost`, `.btn--light`, `.btn--ink` or `.textlink` for everything else. "Join the team" always links to `index.html#join` (from members pages, `../index.html#join`).
4. **Peach is never a card fill.** It appears only in `.dawn-sky`, `.bg-dawn` and 4px top rules (`.card--dawn`, `.netcard`, `.callout--plain`).
5. **Numbered or amber markers only for true sequences:** the message hops, the join steps, alert-level escalation and the net run-sheet.
6. **Everything that changes comes from data.js**, through a `data-widget` hook (§7). The static HTML inside the hook is the no-JS fallback and must match data.js as of 2026-09-26: the standing pattern ("Every Tuesday, 8:00 PM, W7GBU 147.300 MHz, +600 kHz, 100 Hz tone") plus a link to the groups.io calendar.
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
9. **Terms:** on each page, make the first use of ARES, ACS, RACES, EOC, ICS-213, net control, Winlink, SET and W7GBU a tap-to-define term (§6.9). ACS = "Auxiliary Communications".
10. **Photos:** use only the files in `assets/img/`, and follow the size and treatment rules in §6.8. Use at most two PHOTO NEEDED frames per page.
11. **Accessibility:**
    - one `h1`; no skipped heading levels
    - tables have a `caption` (it can be `.vh`) and `scope`
    - external links carry `class="ext"` plus a `.vh` "(opens site)" note
    - 44px targets
    - no horizontal scroll at 390px

## 6. Components

Class names are in `site.css`, and its header lists sections 1-31. The snippets show the minimum markup.

### 6.1 Utility bar and masthead (chrome)

- **Utility bar:** `.utility > .wrap.utility__bar`, containing `p.utility__net[data-widget="next-net-line"]`, `button.utility__copy[data-copy]` and `a.utility__members`.
- **Masthead:** `.masthead > .wrap.masthead__bar`, containing `.brand`, `nav.nav` (`button.nav__toggle[aria-expanded][aria-controls]` + `ul.nav__list#primary-nav`) and `a.btn.btn--red.site-header__join`.
- **Sticky behaviour:** `header.site-header` is sticky with a negative top, so the utility row scrolls away while the masthead stays. The header is solid night on every page; the round-1 transparent-over-hero header is dropped for consistency and a cheaper build.
- **Under 1020px:** the list collapses behind "Menu", and Join stays visible in the bar.
- **Without JS:** the list wraps under the bar.

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

**Members radio card** (`.radio-card[data-widget="net-card"][data-format="24"][data-nc-label="false"]`) is dark:

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

### 6.12 Join path

This lives on Home, but the pattern is documented for reuse:

- **Control:** `fieldset.seg` holds `.seg__options` and `label.seg__opt`, each with `input[name=from]` + `span`.
- **Path:** `ol.path` (vertical) or `ol.path.path--row` (horizontal on the red line, vertical under 1100px).
- **Step:** `li.path__step[data-step]` = `.path__num` + `.path__body` (`.path__head` with `h3.path__title`, `.path__flag`, `.path__done`; then `.path__desc`, `dl.path__facts` for How long / You need, and `p.path__action` with exactly one link).
- **Optional step:** `path__step--opt` draws a hollow numeral.
- **Containers:** `[data-join-path]` wraps the whole block; `[data-share]` with `[data-share-url]` and `[data-share-copy]`; `[data-step-keep]` holds the "already on the team" note.

### 6.13 Forms

- **Fields:** `.field` (label + `.input`; `.input` is Crimson italic 20px, the paper-form voice; `.input--sans` for search-style fields), `.fieldset`, `.radios` (+ `.radios--row`), `.radio`, `.hp` (honeypot), `.form-note`, `.form-done`.
- **Paper form:** `.paper` with `.paper__head` and `.paper__body`; `.paper__row` (+ `.paper__row--3`); `.paper__actions`.
- **Search:** `.search` with `.search__field` (an icon + input) and `.search-preview` (live matches).
- **The interest form on Home is the only form on the site.** Its no-JS action is a mailto to the proposed role address (`data-verify`); nothing is stored on the host.

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
- `details.toc-mobile[data-toc-mobile][open]` holds a second `nav.toc[data-toc]`. It is **open by default**, per the option brief.
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
- **Status legend:** `dl.status-key`.

### 6.20 Members shell and sub-nav

- **Shell:** `.members-shell` holds `nav.member-nav` (night sidebar) and `main#main.workspace` (paper).
- **Sidebar:**
  - `.member-nav__net[data-widget="next-net-mini"]`: the mini next-net readout
  - `.member-nav__label`: group labels (Operate, Prepare, Reference)
  - `.member-nav__ext`: groups.io
- **Current item:** `a[aria-current="page"]` gets an amber rail.
- **900px and below:** the sidebar becomes a sticky horizontal scroller under the masthead, and JS scrolls the current item into view.
- **Workspace pieces:** `.page-head` (`.crumbs`, h1, `.lede`, `.page-head__meta[data-widget="asof"]`), `.workspace-section`, `.panel`.

### 6.21 Footer

The footer is grouped by audience: New to radio, Licensed hams, Members, Agency partners.

- **Brand column:** mission line, "Formerly Spokane County ARES/RACES", the meeting address, and the affiliation marks (ARES emblem, ACS seal, SCEM seal).
- **Bottom row:**
  - "A volunteer group, not an emergency service. In an emergency, call 911." (`data-verify`)
  - Privacy
  - ©
  - "Mockup — content to be verified before launch"

## 7. JavaScript hooks (site.js)

Every widget is one function and one hook, and maps to one block pattern or partial. Pages work with site.js absent.

| Hook | Renders | Options |
|---|---|---|
| `[data-widget="next-net-line"]` | Utility bar line. "Next net: Tuesday, Sep 29, 8:00 PM. Fifth Tuesday: starts on simplex, then W7GBU 147.300 MHz. Net control NZ2S." Reads "On the air now" Tuesday 20:00-21:00; after 21:00 it rolls to the next week | — |
| `[data-widget="net-card"]` | Slots: `label`, `when`, `netcontrol`, `note` (simplex / Winlink night + assignment / GMRS; hidden when empty) | `data-format="24"`, `data-nc-label="false"` |
| `[data-widget="next-net-mini"]` | Members sidebar readout. Slots: `main`, `sub`, `flag` | — |
| `[data-widget="upcoming"]` | `.event` rows; past items drop off; in-progress multi-day events read "Happening now" | `data-days`, `data-limit`, `data-types` (exercise, meeting, training, public-service, on-air, net, gmrs), `data-exclude` (id substrings, e.g. `shares`), `data-merge="workshops"`, `data-format`, `data-link` (relative to `data-root`) |
| `[data-widget="rota"]` on `<tbody>` or `<ul data-variant="list">` | Net-control rota. An open slot reads "Open, Volunteer on groups.io"; the next row gets `.is-next` | `data-weeks`, `data-past`, `data-names="true"` (names **only** on the members rota table) |
| `[data-widget="winlink-next"]` | Slots: `date`, `task`, `form` | — |
| `<tbody data-widget="winlink-table">` | Assignment rows | `data-past` |
| `[data-widget="asof"]` | "Schedule as of Sat, Sep 26, 2026." | — |
| `[data-widget="next-meeting"][data-id]` | Next date for a meeting id | — |
| `button[data-copy]` | Copies `SPOKARES.radio.copyText`, or `data-copy-text`, with a textarea fallback and a toast | — |
| `button[data-ics="weekly-net"]` / `[data-ics="event"][data-event-id]` | A .ics built in the browser (weekly RRULE, or one event) | — |
| `.term` | Tap-to-define | — |
| `[data-join-path]` | Segmented control, `?from=`, next-step highlight, copy link, syncs the form | — |
| `form[data-interest-form]` + `[data-form-done]` | Honeypot, validation, confirmation | — |
| `[data-story]` | The story (§6.16) | — |
| `nav[data-toc]`, `details[data-toc-mobile]`, `[data-contents-fab]` | TOC active section and the mobile Contents button | — |
| `[data-doc-library]` | Library filter (§6.19) | — |
| `form[data-doc-preview]` + `[data-preview-list]` | Live top-5 matches on the members hub | — |
| `[data-filter-scope]` with `button[data-filter]` and `[data-type]` items | Generic chip filter (exercises page) | — |

**Demo parameters:**

- `?today=2026-09-29&now=20:15` shows "On the air now".
- `?today=2026-10-02` shows an open slot.
- `?from=licensed` highlights the next join step.
- `?verify=1` outlines every `[data-verify]`.
- `?q=` and `?cat=` pre-filter the library.

## 8. Section guidance for the other pages

### How it works (`how-it-works.html`, `body.page-how`)

- **Open:** H1 "How it works", then a one-line plain intro (the lede from `content-how-it-works.md`), the scenario pill with its note, and a "Skip to what members do" link to `#the-system` or `#nets`.
- **Story:** `section.story.bg-storm#follow-a-message[data-story]` (§6.16):
  1. "It's 2 AM. The power's out, and so is cell service." in `.story__open` style, state `calm`
  2. Power and phones fail, state `down`, with a rain mini
  3. A warming shelter needs help, state `shelter`, with the `.ics213` card
  4. A volunteer picks up the radio on W7GBU with net control, state `send`
  5. The EOC logs it on an ICS-309 (`.logline`) and relays it, state `relay`

  Then `.dawn-sky.dawn-sky--from-slate.dawn-sky--tall`, with the line "By morning, the message has been handled." and the dawn H2 "Radio still works. Because people practice." Add `data-verify` on the scenario block.
- **Hop 4 wording:**
  - **Name the role, not the net.** Hop 4 is the **hospital team operator**, who copies the county's follow-up at a hospital by radio. Do **not** write "the hospital net", and do not make it a term: `ia.md` §8.7 keeps the Hospital Net and its schedule off public pages.
  - **Channels:** never draw or name a hospital, EOC or county channel. The only frequency in the story is W7GBU 147.300 MHz.
- **Daylight chapters** under H2 "What members actually do", on `.bg-day` or `.bg-paper`, each opened by a `.redline`:
  - the weekly net (`#nets`, `#weekly-net`): the run-sheet as an `ol` with `.hop` markers, a `.netcard`, and the `#other-nets` table
  - activation (`#activation`, `#alert-levels` with `.levels`, `#standing-order-1b` with the `.pullquote`)
  - messages (`#message-handling`, `#ics-213`, `#logs`, `#winlink`, with the next assignment from `winlink-next`)
  - exercises (`#exercises`, with the `upcoming` widget)
  - public service (`#public-service`, with the parade-operator photo, badge blurred, ≤660px)
  - `#where-we-work`, `#real-world`
- **Close:**
  - "What it takes to be on the radio that night" as `ol.numerals` on `.bg-dawn`, with id `what-it-takes`
  - "Every hop is a post" as `ul.posts` (`#roles`), with eligibility tags
  - a closing CTA: one `.btn--red` "Join the team", with "Listen this Tuesday" and "Read the details" as text links
- **Photo frames:** PHOTO NEEDED for "EOC radio room during an exercise, wide" and "net control at a home station at night". Use the mast-team and comm-trailer stills as `.duo`.

### About ARES & ACS (`about.html`, `body.page-about`)

- **Shell:** the night header over a daylight page (`.bg-day`). A page head holds H1 "About ARES & ACS", the lede and `.chapter-meta` "Last reviewed …" (`data-verify`); the long read follows (§6.18).
- **Chapters:** every H2 id comes from `ia.md` §5.
- **Required extra anchor: `id="for-agencies"`.** Home and the footer link to `about.html#for-agencies`. Make it a short chapter ("For served agencies": request through SCEM, the agreements, "Contact the emergency coordinator" linking to `#contact`).
- **Components:**
  - the drop cap on chapter 1
  - sidenotes for acronyms and sources
  - the `.compare` table for ARES vs ACS, with emblem heads
  - `.callout--legal` for the legal basis
  - a `.pullquote` each for Dean Bula and Standing Order 1b
  - FAQ as `.faq details`
  - the glossary as `dl.glossary`
- **W7GBU:** the `#w7gbu` chapter closes the story. The legacy ARES/RACES seal appears only in `#history`.

### For Members (`members/index.html`, `documents.html`, `exercises.html`; `body.members-page` with `data-root="../"`)

- **Shell:** the members header variant plus the members shell (`_chrome.html` §4-§5). These pages are light, dense and story-free.
- **Hub:**
  - search (`data-doc-preview`) at the top
  - a `.radio-card` (`data-format="24" data-nc-label="false"`)
  - a rota `.panel` with `tbody[data-widget=rota][data-names=true][data-past=1]`
  - the Winlink next assignment
  - the next meeting and exercise via `upcoming`
  - `ul.quick` tiles (≤8)
  - "Staying current" `.checklist`
  - a sections directory
  - groups.io and "Who to ask" tables
- **Documents:** §6.19, with a Most-used `ul.quick` above the library.
- **Exercises:**
  - chips (`data-filter-scope`)
  - `ol.timeline` with date slabs; each exercise shows What, Where, Bring, Sign up and Report to
  - SET and ShakeOut detail
  - the Winlink table
  - the year at a glance
  - after-action steps
  - past exercises
- **Dates:** 24-hour times are fine on members pages.

## 9. Content decisions in Home

- **H1:** "When other systems go down, Spokane County still has a voice." The first viewport names Spokane County Emergency Management, the NWS, hospitals and events, and states the capability.
- **The ask:** "Could you be the one on the radio?" with the Plain Signal 0-3 path, one action per step:
  - HamStudy and SKYWARN sit in the "Not licensed yet?" card.
  - Step 1 links the ARES application (2024-11-07) through `members/documents.html#ares-application`; where to send it is `data-verify`.
  - Step 3 links spokanecounty.gov FormCenter 276.
- **groups.io:** "Apply to join our groups.io list; a moderator approves new members". Never "Subscribe".
- **`data-verify` on Home:**
  - the scenario note and narration
  - "Local exam sessions run monthly"
  - join@spokares.org
  - the interest form (role address)
  - the Third Thursday evening time
  - the meeting address block
  - the Winlink workshop time
  - IMG_6574 (portable repeater visible; SCEM to confirm)
  - the footer disclaimer

## 10. QA notes

**Home measurements** (`index.html`):

| Viewport | What was measured | Result |
|---|---|---|
| 1440 | Total page height | 4,954px |
| 1440 | Top of the join section | 1,631px |
| 1440 | First viewport (900px) | Shows the H1, the capability sentence, "Join the team" and the secondary link |
| 390 | Top of the join section | ~2,200px (2.6 screens) |
| 390 | Horizontal scroll | None |

At 1440 the page runs about 450px over the brief's ~4,500 target, and stays under the 5,500 cross-cutting cap.

**Screenshots:**

- Current headless Chrome will not size a window below ~500px, so `--window-size=390,…` actually lays the page out at ~500px and crops it. The round-1 pages crop the same way today.
- The 390px shot (`shots/home-mobile.png`) was therefore taken through the DevTools protocol with `Emulation.setDeviceMetricsOverride` (390×844, mobile), capturing the first 3,000px.
- Desktop shots use the stock command.

**Integration pass (2026-09-26).** These fixes now live in the shared files, and the page-level workarounds were removed:

- `site.css`:
  - `.prose a.btn--*` keep their button colours.
  - `.bg-dawn .redline::after` is white.
  - `.member-nav__inner` is `position: relative; top: auto` at 900px and below, which stops the hidden group labels widening the page.
  - `.prose a.h-anchor` stays `--off` grey.
- `site.js`:
  - `winlink-table[data-past="0"]` shows no past rows.
  - The `upcoming` workshop merge honours `data-format`.
  - `index.html#members` reveals the "already on the team" card.
- **Story status line:** it no longer carries its own "A scenario" pill. The pill drawn inside the SVG covers it.

For the checks run and the screenshot method, see `README.md`.
