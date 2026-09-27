# Option B: Carry the Message

This is the round-2 mockup for Spokane County ARES-ACS. It builds on the round-1 concept "When the Lines Go Down" (12), which was ranked 2nd with a composite score of 7.92.

**Thesis:** make people feel why the work matters, then ask.

Every page moves from night to daylight:

- **Night** frames the chrome and the story.
- **Daylight** carries the practical content.
- **A dawn gradient** is the hinge between the two. It is the only place peach appears.

The signature is the ICS-213 message-path diagram. It follows one request from a warming shelter, through net control on W7GBU 147.300 MHz, to the county EOC radio room and on to a hospital. Each hop is a volunteer role: a red message arc links four amber hop markers, and an "A scenario" pill is drawn inside the picture itself.

The red line appears in three more places:

- the section rule
- the line the Home join steps sit on
- About's contents marker

The site's one form, "Tell us you're interested", is styled as the ICS-213 paper card.

**Type and colour:**

- Crimson Pro for narration, headings and the long read.
- Schibsted Grotesk for the interface, tables and the members pages.
- Colours: night #0E1A33, slate #33415C, dawn #F3C29B, signal red #D0202A and amber #FFC857.

`DESIGN.md` covers the tokens, every component, the `site.js` hooks, and the rules the page builders followed.

## Pages

To view them, open any file directly (`file://`). There is no build step and no server.

| Section | File | What it is |
|---|---|---|
| 1. Home | `index.html` | A compact landing page for newcomers. The capability H1 names SCEM in the first viewport. The static message diagram leads into "Could you be the one on the radio?", which holds the 0-3 join path with a "Where are you starting from?" control (`?from=`) and the paper interest form. Then comes the dawn band ("Every Tuesday night we practice.", a live net card and "Coming up"), then "For served agencies". |
| 2. How it works | `how-it-works.html` | The story, as a sticky diagram beside six plain `<article data-state>` steps. The dawn payoff, "Radio still works. Because people practice.", leads into daylight chapters on what members actually do: the system, nets (with the run sheet and net card), activation and the alert levels (explained only), message handling, exercises, public service, where we work, "Every hop is a post", "What it takes", and a closing call to action. |
| 3. About ARES & ACS | `about.html` | "The long version": an editorial long read in 64ch Crimson. It has a sticky grouped contents list whose red line tracks the active chapter, Tufte sidenotes at 1200px and up, and the ARES vs ACS table. Also: the legal basis as a boxed callout, a structure diagram, leadership (role and call sign only), membership paths, the task books, `#for-agencies`, meetings, history, W7GBU, FAQ, glossary and contact. On phones the contents list is an open `<details>`, and a floating Contents button brings it back. |
| 4. For Members: hub | `members/index.html` | The operations desk. A night sidebar sub-nav lists every members section from `ia.md` (unmocked ones link to `#`), with a mini next-net readout. Also: search with live matches, "This week" (a radio card with 147.300, the settings grid, check-in order, Copy and Add to calendar, plus a desk panel of exercises, in-person meetings and the Winlink assignment), the open net-control strip, the rota with open slots, 8 quick links, "Staying current", a sections map, groups.io and "Who to ask". |
| 4. For Members: documents | `members/documents.html` | All 74 documents from `data.js` in 8 groups, with search, category chips (`?q=`, `?cat=`, `#forms`, `#doc-id`) and a result count in an `aria-live` region. "Most used" tiles sit above the library. Each row shows format, version or date, and a source badge, and the rows become cards below 1180px. The page ends with "Can't find it?" and a key to the source badges. |
| 4. For Members: exercises | `members/exercises.html` | A filterable vertical timeline with date slabs, grouped by month. Each exercise card gives What, Where, Bring, Sign up and Report to, plus Add to calendar. Below it: before/during/after steps, Winlink assignments, public-service events, the year at a glance, and past exercises. |

Shared files:

- `assets/site.css`: the whole design system.
- `assets/site.js`: progressive enhancement, one function per widget.
- `assets/data.js`: identical to `_shared`.
- `assets/img/`
- `_chrome.html`: the canonical header, footer, members sub-nav and SVG sprite. Every page copies these verbatim.

## How it answers the round-1 feedback

| Round-1 criticism (`design/REVIEW.md`) | What this option does |
|---|---|
| The join path started about 6,000px down (ease of use scored 6.0) | The story moved to How it works. At 1440 wide, Home is 4,954px and the join path starts at **1,631px**; at 390 it starts at about 2,200px, which is 2.6 screens. "Join the team" is in the hero and in the header on every page. At 1440×900 and 390×844, the first viewport shows the H1, the SCEM capability sentence, "Join the team" and "Follow one message through a night without phones". |
| Members had to skip the story on every visit | On every page, a night utility bar shows the next net from `data.js` (with net control and "On the air now" on Tuesdays 8-9 PM), a Copy radio settings button and a For Members link. For Members is in the primary nav. The members pages are light, dense and story-free, and have their own sub-nav. `index.html#members` opens the "already on the team" card. |
| The fear-first opening needed a PIO check, because a screenshot could circulate as a real alert | Home's H1 is a capability line. "It's 2 AM…" opens How it works only, after a plain intro. The "A scenario" pill is drawn inside the SVG, so any crop still says scenario, and every scenario block carries `data-verify` for PIO and SCEM review. There are no siren colours and no status widgets. |
| Scrollytelling is brittle to edit | Steps are plain `<article class="step" data-state>` blocks that an editor can reorder. There is one `<symbol>` diagram whose layers switch through CSS custom properties, and one IntersectionObserver. Below 900px, with reduced motion or with no JS, each step shows its own static diagram (see `how-desktop-full.png`). |
| Long empty dark stretches | Steps are capped at min(70vh, 660px), and every step has a visual: a diagram state, the ICS-213 card, a PHOTO NEEDED frame or an ICS-309 log line. |
| The practical half lost energy | The daylight chapters keep the story's visual language: a red-line section rule, amber markers for true sequences (net run sheet, alert levels, join steps) and big Crimson numerals for "What it takes". |
| Few real photos | IMG_6574 runs in full colour at dawn. The 659px stills are in night duotone at 660px or less, and the parade operator appears with the badge blurred. Missing scenes get at most two slate PHOTO NEEDED frames per page, each with a shot list. |
| Visible placeholders; alert strip on Home | The pages show no TBD, verify or bracket chips. Uncertain facts carry `data-verify="true"` (`?verify=1` outlines them), and every footer has one line: "Mockup — content to be verified before launch". There is no alert status anywhere: the levels are explained on How it works and About, with no "current" marker. |

The cross-cutting fixes from `briefs.md` are all in place:

- **Chrome:** one chrome on every page, with `aria-current` on the active section, a sticky members sub-nav, and a footer grouped by audience.
- **Join path:** the 0-3 path with How long / You need and one action per step. It links the real destinations: the ARES application, groups.io "Apply to join", spokanecounty.gov FormCenter 276, HamStudy and SKYWARN.
- **Terms:** tap-to-define terms on first use.
- **Next net:** Copy radio settings and a browser-built weekly .ics.
- **Contacts:** role addresses only (each carries `data-verify`). There are no personal phone numbers or emails, and no county, hospital or SHARES channels.
- **Hospital Net:** hop 4 is the "hospital team operator". The Hospital Net is never named, per `ia.md` §8.

## Integration pass (2026-09-26)

**Checks, all passing:**

- **Links:**
  - Every relative `href` and `src` on the 6 pages resolves over `file://`, including `#fragment` targets on other pages.
  - The only `#` links are the unmocked members sections and Privacy.
  - The `ia.md` §5 anchor map is complete on all pages. Home gained `#what-we-do`, `#what-it-takes`, `#w7gbu` and `#members`.
- **Chrome:** the header, footer, sprite and members sub-nav match `_chrome.html` byte for byte, apart from `aria-current`. The correct item is marked on each page, and all members pages mark For Members.
- **Browser checks:** checked in headless Chrome over the DevTools protocol at 1440 and at a true 390×844 mobile viewport.
  - There are no console errors or exceptions.
  - The widgets render from `data.js`.
  - Each page has one H1 and no skipped heading levels.
  - There are no duplicate ids and no broken ARIA references.
  - Every image has alt text.
  - The skip link is the first focusable element.
  - A computed-contrast sweep found no text below WCAG AA. The one flag is the hollow step "3" numeral, which is decorative and `aria-hidden`.
  - There is no horizontal scroll at 390.
- **Mobile nav:** the mobile menu toggles `aria-expanded` and keeps Join visible. The members sub-nav scrolls sideways and keeps the current item in view.
- **Keyboard:** the focus ring is visible on dark (amber) and light (ink) surfaces.
- **Without JavaScript:** every page reads, with no empty widgets.
- **Demo URLs:**
  - `?today=2026-09-29&now=20:15` shows On the air now.
  - `?today=2026-10-02` shows the open net-control slot.
  - `?today=2026-10-17` shows the GMRS week.
  - `?from=` works for every value.
  - `?verify=1` outlines all 53 marks on About.
  - `?q=winlink` returns 14 matches.
  - `?today=2026-10-15` shows ShakeOut as Happening now.

**Fixes made:**

- `site.css`:
  - Buttons inside `.prose` keep their colours (before this, the red Join button in About rendered red text on red).
  - The dawn surface gets the white end-dot on `.redline`.
  - The members sub-nav scroller is `position: relative` at 900px and below (before this, the hidden group labels widened members pages to about 1,680px at 390).
  - Heading anchors in `.prose` stay grey rather than red.
  - The pages' own workarounds for the first three were removed.
- `site.js`:
  - `winlink-table` with `data-past="0"` no longer shows every past row.
  - The workshop merge in `upcoming` respects `data-format`.
  - `index.html#members` reveals the "already on the team" card.
- **How it works:** removed the duplicate HTML "A scenario" pill from the story status line. The pill drawn inside the diagram stays, and so does the one in the page opening.

## Screenshots (`shots/`)

| File | How it was taken |
|---|---|
| `*-desktop-fold.png` | Stock headless Chrome at `--window-size=1440,900`. |
| `*-desktop-full.png` | DevTools protocol: a 1440×900 viewport, capturing the first 4,200px beyond the viewport, with lazy images forced to load. This keeps `100vh` rules (the members shell height, story step height) at their real desktop values; a 4,200px-tall window stretches them. |
| `how-desktop-full.png`, `how-desktop-page.png` | These two are captured with `prefers-reduced-motion: reduce`, so every beat of the story shows its own diagram state in one static image. In the default mode the diagram is a single sticky figure that follows the reader, so a static full-page capture leaves the right column empty below the first step. `how-desktop-page.png` is the whole page at 50% scale. |
| `how-desktop-story.png` | The default scrolling behaviour at 1440×900, mid-story: step 4 beside the sticky diagram in its "send" state. |
| `about-desktop-mid.png` | About at 1440×900, scrolled to `#structure`, with the contents rail tracking the active chapter. |
| `*-mobile.png` | DevTools device emulation at a true 390×844 (mobile, touch), keeping the first 3,000px. Headless Chrome will not lay out a window narrower than about 500px, so `--window-size=390,…` gives a cropped 500px layout. |

## Known limitations

- **Length.** How it works is 19,582px at 1440 and about 31,500px at 390, and About is about 32,000px and about 48,800px. Both carry the full content files. The How chapter index, the "Skip to what members do" link and About's contents list and floating Contents button make them navigable. If How needs to be shorter, the first cuts are the "Every year" table (it overlaps the dated list) and "Most of the year looks like this". Home at 390 is about 9,400px. It stays within the three-screen target for the join path, but the dawn and agency bands stack long on phones.
- **Members sub-nav on phones.** It is a sticky horizontal scroller (the option brief's Ops Board pattern), not the `ia.md` "Members menu" disclosure. The group labels (Operate, Prepare, Reference) are visually hidden at that width.
- **Demo "now".** Date logic uses `SPOKARES.meta.asOf` (2026-09-26) or `?today=`. The clock time comes only from `?now=`, so "On the air now" needs both parameters to demo.
- **Interest form.** It is a mockup: with JS it shows a confirmation and sends nothing. Without JS it falls back to a `mailto:` to the proposed role address. Production needs a form handler, or a decision to keep the mailto.
- **Content to confirm before launch** (all marked `data-verify`):
  - the scenario copy, for PIO and SCEM review
  - role addresses
  - the Third Thursday time and the orientation rule
  - the GMRS net details on How it works, including WRCY281. The content file includes the repeater, but `data.js` notes that its owner must agree before it is listed, so the members pages leave it out.
  - IMG_6574 (a portable repeater is visible)
  - some of the exercise logistics inferred on the exercises page
- **Not mocked.** The six members sub-pages and Privacy link to `#` by design. Hosted document files link to `#` or to the interim ag7qp.com copies noted in the library.
