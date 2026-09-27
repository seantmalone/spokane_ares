# Option C: Open Channel

Round-2 design option for the Spokane County ARES-ACS site. It is led by the round-1 concept **Open Door (04)**. It has four sections (Home, How it works, About ARES & ACS, For Members) across six static pages, which work when opened from disk (`file://`).

Open `index.html` in a browser. No build step, server or login is needed.

## Concept

**"The easiest yes is 'come visit'. Belonging leads to joining."**

Open Channel keeps Open Door's warmth: real photos, a "This week" card with the next Tuesday net, and a "Three ways to visit" planner (Listen / Meeting / Workshop). It now opens on capability rather than a club greeting.

- The Home H1 is *"From Bloomsday to blackouts, we keep Spokane County connected."*
- The next sentence names Spokane County Emergency Management as the primary served agency.
- An "Agency partner" door sits one row below.

The look:
- **Type:** Bricolage Grotesque for headings, Atkinson Hyperlegible for text. Atkinson's slashed zero suits call signs and frequencies (NZ2S, 147.300).
- **Colour:** dusk blue and pine. Seal red appears once, on the header's "Join the team". Lamp yellow is only for "Tonight" / "On the air now".
- **Motion:** one moment per page.

Everything that changes week to week comes from `assets/data.js`: the next net and net control, the simplex, Winlink and GMRS weeks, meetings, exercises, the rota, Winlink assignments and the document library. The static HTML fallbacks show the standing pattern as of 2026-09-26.

## Pages

| Section | Page | What it does |
|---|---|---|
| 1. Home | `index.html` | For newcomers, with clear calls to action. From the top:<br>- a capability hero with one primary CTA ("Plan your first visit") and a text-weight "Ready to join?"<br>- the live This-week card, with Copy radio settings and Add to calendar<br>- an audience row: New to radio, Licensed ham, Member, Agency partner<br>- "Joining, in four steps" (0-3, with `?from=`) and "Here's what it takes"<br>- the three-door visit planner<br>- What we do<br>- Training with partners, from data.js<br>- Who we serve and the agency door |
| 2. How it works | `how-it-works.html` | "A month with the team": the operations in detail. A sticky "On this page" chip bar leads to:<br>- a month calendar drawn from data.js<br>- the net minute by minute, plus a "Tune in" card<br>- when the county calls: activation in five steps, the four readiness levels explained (no current level) and Standing Order 1b<br>- how a message moves (ICS-213, Winlink)<br>- exercises, public service and the drawn "Where we work" map<br>- "Find your spot" role cards, "Your first month" and newcomer questions |
| 3. About ARES & ACS | `about.html` | "Everything, in plain words": the long, text-heavy reference.<br>- A sticky side table of contents on desktop, with section groups and scroll-spy. On mobile it's a "Jump to section" disclosure.<br>- Every H2 opens with a two-sentence "The short version".<br>- Tables: ARES vs ACS, membership levels, training checklists, leadership (role and call sign).<br>- The legal basis in four layers, `#for-agencies`, the W7GBU story, history, the FAQ, a glossary and contact routes. |
| 4. For Members | `members/index.html` | The member desk:<br>- document search above the fold<br>- the full-width This-week card, with net control for the next four Tuesdays and open slots<br>- 8 "Most used" tiles and a grouped directory of every members section<br>- radio settings (amateur only), the rota, Winlink assignments, "Staying current", "Who to ask" and groups.io |
| | `members/documents.html` | The whole public library (74 entries). It has search, category chips with counts, a "Hide Coming back" toggle and a live count. Every row shows format, date or version, source badge and where it opens. `?q=`, `?cat=` and `#category` / `#doc-id` deep links work. |
| | `members/exercises.html` | "Next up" (the next two events), then an agenda of the next 5 months with month tabs, type filters and expandable rows. Also: Winlink assignments, before / during / after, the year at a glance, public-service events and past exercises. |

**The members menu** appears on every `members/*.html` page and lists all 11 sub-sections from `_shared/ia.md`. Six of them aren't mocked yet and link to `#`. groups.io is marked as external.
- **1280px and up:** a sticky two-row pill bar. The three groups (Operate, Prepare, Reference) are separated by wider gaps.
- **Below 1280px:** a sticky "Members menu: *current page*" disclosure. It opens to the whole grouped list, with the group names shown. It looks and behaves like About's "Jump to section".

**Supporting files**
- `assets/site.css` is the design system.
- `assets/site.js` is progressive enhancement: one function per widget, ES5, no `fetch()`.
- `assets/data.js` is unchanged from `_shared`.
- `_chrome.html` has the canonical header, members menu, footer and icon sprite.
- `_components.html` is the pattern library.
- `DESIGN.md` covers tokens, components, widgets and page rules.

**Demo switches**
- `?today=YYYY-MM-DD` and `?now=YYYY-MM-DDTHH:MM` change the mock date. Examples: `?now=2026-09-29T20:15` shows "On the air now", and `?today=2026-10-20` shows "Tonight", WA7LNC and the GMRS row.
- `?from=unlicensed|licensed|ares|acs#join` sets the join path.
- `?verify=1` outlines every `[data-verify]` fact.

## How it answers the round-1 feedback

| Round-1 judge criticism of Open Door | Round-2 remedy |
|---|---|
| The hero was about socialising, not capability. An emergency manager landed on a club greeting. | The H1 is the capability line, and the next sentence names SCEM. The invitation ("Come say hello on Tuesday night…") sits below it in pine. An **Agency partner** door leads to `about.html#for-agencies`, and the footer is grouped by audience. |
| The peach card looked dated and off-brand. | Peach is gone. The This-week card is white with a dusk-deep header band. Lamp marks only the live net state. |
| It had more visible "verify" / "to confirm" chips than any other concept. | None are visible. 248 uncertain facts carry `data-verify="true"`, and `?verify=1` shows them to reviewers. The footer carries one line: "Mockup — content to be verified before launch". |
| It showed a public "Condition Green" badge. | Deleted. The four readiness levels are explained once, as a neutral numbered sequence on How it works (and in About), with no current level. |
| Weekly data needed one data file. | Everything date-driven renders from `data.js`: the card, date chips, net control, the simplex, Winlink and GMRS flags, partners, "first month" dates, the rota, the agenda and the library. Past items drop off. The WSDOT exercise shows "Happening now" on Sep 26. |
| The RSVP needed a mail handler. | "Add to calendar" (a weekly `.ics` built in the browser) is the planner's main action. "Tell us you're coming" is optional, has a honeypot and is the site's only form. |
| The two hero buttons competed at equal weight. | There's one filled primary and one text-weight secondary. Red appears only on the header Join button. |
| The page was about 10,800px and repeated itself. | Home is 5,214px at 1440 wide. The newcomer questions, alert levels and first month moved to How it works, and the W7GBU story moved to About. Each module appears once. |
| Superseded rules. | Check-in order is "officials, then members, then visitors". No simplex frequency is shown, and there's no 10 PM net. The site says "Apply to join" groups.io. ALERT Spokane is on Regroup. New-member requirements come from the 2024 ACS Task Book. |

The owner's four-section direction is met as follows:
- Home is short and CTA-led.
- How it works covers operations and what members do.
- About is the long, structured reference with a TOC.
- For Members has its own menu. Navigation efficiency comes from search above the fold, a This-week summary, 8 quick tiles, a grouped directory, and bookmarkable filtered views of the library and agenda.

## Integration pass (2026-09-26)

**What I checked**, with scripted headless-Chrome checks on all six pages at 1440 and 390, with JS on and off:
- Every relative link and `#anchor` resolves over `file://`.
- The header, footer and icon sprite are identical across pages, apart from `aria-current` and the `../` paths on members pages. The members menu is identical apart from its current item.
- `aria-current` is correct on every page.
- There are no console errors, exceptions or failed loads.
- Every page has one `h1`, no skipped heading levels, no duplicate ids, alt text on every image and no horizontal overflow.
- The skip link is the first tab stop. Focus shows a 3px ring.
- Text contrast is AA.
- There's no status widget and no visible TBD, verify, undefined or NaN text. The only e-mail addresses are the role addresses (join@, ec@, webmaster@). There are no phone numbers.
- Red is used once per page, and the footer mockup line appears on every page.
- The mobile menu works on all pages, including Escape and returning focus to the button.
- Scroll-spy highlights the right item for all 9 How-it-works chips and all 26 About sections, at both widths.

**What I fixed**
1. **Members menu on phones and tablets.** The sideways-scrolling row showed only 2-3 of its 11 items at 390px. It's now the sticky "Members menu" disclosure that `ia.md` §4 specifies. The same markup gives the two-row bar at 1280px and up, and with scripts off it's a plain open list that doesn't stick. The Documents page's Forms filter updates the menu's current item.
2. **The members menu no longer shifts between pages.** The current pill turned bold and wider, which pushed the other pills along. The current state is now shown by the filled pill alone. The How it works chips use a dusk ring instead of bold, so they don't jitter while scroll-spying.
3. **Scroll-spy.** A deep link to `how-it-works.html#exercises` highlighted "Messages", because the previous section's edge was still in the band. The spy now picks the last section that has passed the reading line, and it re-checks after instant jumps (deep links, back button, reduced motion). The About page's own patch for this moved into `site.js`.
4. **Members "Coming up" is in date order.** It was out of order on any date after the WSDOT exercise. This is now fixed in `site.js`, and the hub's own workaround was removed.
5. **Shared fixes the builders had patched page by page, now in `site.css` / `site.js`:**
   - stacked-table captions on phones, and the compare-table row-head width
   - the About text width (37em, about 70 characters a line)
   - the agency panel's heading margin inside the prose
   - `.week-head h3`
   - hiding copy buttons without JS
   - the groups.io screen-reader text widening the page on iOS
   - re-centring the current pill after the web font loads
6. The "On this page" row on **Exercises** now uses the same chips as How it works, via a new shared `.chip-row`.
7. The header with scripts off no longer shows a dead menu button, and the nav list sits under the brand and Join button.
8. Out-of-month dates in the How it works calendar went from 1.6:1 to AA contrast.
9. On About at phone width there's now space between "Jump to section" and the first heading.

## Known limitations

- **Length.**
  - About is 35,800px at 1440 and about 52,000px at 390. That's by design for the full reference; the sticky TOC and "The short version" boxes carry navigation.
  - How it works is 15,400px at 1440 and about 23,300px at 390, with mobile folds closed.
  - Home is 5,214px, inside the 5,500px cap but a little over the brief's "about 5,000px".
- **Home order.** The join path comes before the visit planner (the brief listed them the other way round), so the join path starts within about 1,060px on desktop. The hero CTA still jumps straight to the planner.
- **The members menu is two rows (103px) when sticky at 1280px and up.** The 11 names from `ia.md` are too long for one row.
- **Not mocked.** Six members sub-sections (Nets & net control, Winlink & digital, Repeaters & programming, Training & task books, Go-kits & readiness, Contacts & roles) and Privacy link to `#`. Several library rows say "Page coming" or "Coming back".
- **Mock behaviour.**
  - The RSVP form shows a mock confirmation. It needs a form service before launch; the notes are in the HTML comment.
  - "Today" is frozen at `SPOKARES.meta.asOf` (2026-09-26). Delete it in production to use the real Pacific clock.
- **Content to verify** (all marked `data-verify`, none visible):
  - role e-mail addresses, which are proposals
  - leadership call signs
  - the Third Thursday start time
  - the GMRS net and repeater
  - the 146.880 alternate
  - the disclaimer, scenario copy and photo releases
- **Photos.**
  - There are four PHOTO NEEDED frames: Home has one (Bloomsday aid station), How it works has two (net control at a home station; operator on a race course) and About has one (kit flat-lay).
  - Hospitals are illustrated, never photographed.
- **Out of scope.** Valleyfest (named in the brief) is left out because it isn't in `_shared/`.
- **Testing.** Tested only in headless Chrome, not in Safari, Firefox or on a real iOS device. Headless Chrome won't lay out a window narrower than 500px, so the `*-mobile.png` shots use DevTools device emulation at a true 390px.
- **Maintenance.** The members pages are now edited directly. The builder's scratch `build.py` is no longer the source of truth.

## Screenshots (`shots/`)

- For each of `home`, `how`, `about`, `members`, `documents` and `exercises`:
  - `-desktop-fold.png`: 1440×900
  - `-desktop-full.png`: 1440×4200
  - `-mobile.png`: 390×3000
- States:
  - `home-on-air.png` (`?now=2026-09-29T20:15`)
  - `site-menu-open-mobile.png`
  - `members-menu-open-mobile.png`
  - `about-mobile-toc.png` (a deep link to `#acs-task-book`, with "Jump to section" open)
- `archive-builders/` holds the builders' self-review shots from before integration. They show the old scrolling members row.
