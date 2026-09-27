# Option A: Figure One (round 3)

The owner found round 2 overloaded: "All three of these have just way too much info. It's overload. Do another pass on each to simplify. Minimize repeating information unless it's really important."

Round 3 cuts every page down to its job and gives each fact one home, following `round3/_shared/placement-map.md`. All wording comes from `round3/_shared/copy-*.md`, so no new facts were added. The look (Lexend, pine, wheat and navy, labelled figures) is unchanged. The design system is described in `DESIGN.md`.

Open any page straight from disk (`file://`). There's no server and no build step.

## Before and after

These are visible words in `<main>`, measured by `design/tools/measure_pages.py`. Before is `round2/_metrics-before.json` and after is `_metrics-after.json`.

| Page | Budget | Round 2 | Round 3 | Kept | H2s | CTAs | Desktop height |
|---|---:|---:|---:|---:|---|---|---|
| Home | 400 | 1,894 | **272** | 14% | 9 → 3 | 27 → 5 | 6,100 → 2,825px |
| How it works | 1,200 | 3,620 | **864** | 24% | 11 → 5 | 14 → 3 | 13,597 → 5,563px |
| About ARES & ACS | 2,800 | 6,621 | **1,274** | 19% | 14 → 9 | 14 → 2 | 29,332 → 9,151px |
| Members hub | 300 | 1,271 | **107** | 8% | 8 → 3 | 6 → 2 | 4,774 → 1,206px |
| Documents & forms | 900 | 2,413 | **450** | 19% | 11 → 7 | 10 → 1 | 9,626 → 3,871px |
| Exercises & events | 700 | 1,986 | **401** | 20% | 7 → 5 | 12 → 1 | 7,653 → 3,107px |
| **Site** | | **17,805** | **3,368** | **19%** | | | |

Repeated 8-word phrases across pages dropped from 621 to 7. All 7 are pointers the map allows: the hub repeats the next meeting and the copyable settings line.

## What changed

**Chrome**
- The members sub-nav went from 11 items (six were dead `#` links) to five live ones: This week, Exercises & events, Documents & forms, Task books (goes to `about.html#training-path`) and groups.io.
- The sub-nav now sits outside `<main>`.
- The footer went from four audience columns, an address and affiliation marks to one row of links, the safety line and the mockup line (34 words).

**Home** (hero, What we do, Come visit, Join in four steps)
- Home is now for newcomers only.
- Net control, offset and tone moved to How it works and the members hub.
- The agency panel is now a one-line link to About#for-agencies.
- Requirements are now one "What ACS asks" line that points to About.
- Cut: the quiz, the four-door router, the This-week strip, the six team tiles, "Licensing in plain English" (it's on About), the W7GBU story, who we serve, the photos, the quote band and the affiliation marks.

**How it works** (the weekly net, when the county calls, how a message moves, training and exercises, roles)
- Eight figure plates are now one (Fig. 2, Follow one message).
- The settings box is the only place with the offset and tone.
- Dated exercises and public service moved to Exercises & events. The yearly rhythm stays here as four lines with no dates.
- "What it takes" moved to About.
- Cut: Where we work, the Section nets and the repeater-etiquette paragraph (now a Documents row). "When it was real" is now one sentence.

**About** (nine sections instead of 12)
- ARES and ACS are now four one-line definitions plus "What we are not".
- The legal basis is a four-row table with one callout.
- Who we serve is four lines, then the agreements and the agency contact.
- Also here: leadership, the two join paths, one task-book table, licensing, history and the three role addresses.
- Cut: the FAQ, the glossary section, the ARES vs ACS comparison table, the mission statement, the ARRL levels table (now one line) and the 2021 timeline entry.
- Moved: "Where and when we meet" to Home#visit, "Joining, step by step" to Home#join, and "How activations work" to How it works#activation.

**Members hub**
- The hub is a switchboard with four parts:
  - Search.
  - This week: the next two exercises (WSDOT and SET) and the next meeting.
  - The Tuesday net: one copyable settings line and a five-row rota, call signs only.
  - Four most-used tiles.
- Cut: the section cards (the sub-nav does their job), the separate next-net line, the Winlink assignments (now on Exercises), the standing requirements (now on About), the groups.io section and "Who to ask" (now About#leadership and #contact).

**Documents & forms**
- The library is the page: search, filter chips (including "Most used"), then 37 rows in seven category tables. Each description is 8 words or fewer.
- Cut: the lede, the status key and the alert-levels row. The 17 "coming back" presentations are now one line under "Not posted here". Three net-script rows are now one.

**Exercises & events**
- Next up comes first: what to bring, the WSDOT card, the SET card and one "After" line.
- Then: later this season (six rows), Winlink assignments (their only home), public service (four rows) and eight past exercises.
- Cut: the month calendar, the year at a glance and the before/during/after section.

## Integration checks (2026-09-26)

**Budgets and repetition**
- Every page is under its word, H2 and CTA budget. Home is 2,825px on desktop, under the 3,200px limit.
- Key facts that appear on more than two pages are all pointers the map allows:
  - Repeater and net time (3 pages): How it works (home), plus the Home#visit "Listen" line and the hub's settings line.
  - County application (3): About (home), plus Home step 3 and the Documents row.
  - ICS-213 (4): How it works (home), plus the Documents row, the hub tile, and the Exercises "Bring" line and Winlink rules.
  - Task book (3): About (home), plus the Documents row and the hub tile.
  - WSDOT (3): Exercises (home), plus the hub's next-two list and the WSDOT 580-020 form row.
  - groups.io (4): Home step 2 (home), plus the hub's rota line, the Documents source cells and the WSDOT card button.
- "Join steps = 5" is a false positive. The tool's `0.?3` pattern matches years and dates ("2023", "2013", "2026-03-04"). The join steps appear only on Home.

**Links and chrome**
- Every internal `href` and `src` resolves over `file://`, and every `#anchor` exists.
- There are no `href="#"` links, no duplicate ids and no absolute paths.
- The header, footer, members sub-nav and kit sprite are identical on all pages and match `_chrome.html`.
- `aria-current` is correct everywhere. It is "page" on the current page and "true" on For Members from its two sub-pages.

**Behavior**
- Deep links: all 69 anchor-map ids land within 4px of their scroll margin on a cold load, at both 1440 and 390.
- The phone menu opens and closes and sets `aria-expanded`. Escape closes it and returns focus. Join stays visible outside the menu.
- All five sub-nav items are visible at 390, 768 and 1024.
- There is no horizontal scroll at 390, 768, 1024 or 1440.

**Quality**
- No console errors or failed loads, with JS on or off.
- Each page has one `h1`, and no heading levels are skipped.
- Focus rings show on each of the first 40 tab stops on every page, at 1440 and 390.
- Times are 12-hour only, with no "2000" anywhere. No `undefined`, TBD or visible verify chips appear.
- 68 uncertain facts carry `data-verify="true"`. Add `?verify=1` to any URL to outline them.

## Screenshots (`shots/`)

- `*-desktop-full.png`: 1440 × 3400. Pages taller than 3,400px (How it works and About) are cut off at the bottom.
- `home-desktop-fold.png` and `about-desktop-fold.png`: 1440 × 900.
- `*-mobile.png`: 390 × 2600, captured with a true 390px viewport.
  - Why not the standard command: `--window-size=390,…` lays pages out at 500px on macOS (measured `innerWidth` = 500) and crops the image to 390. Text runs off the right edge, which makes a false problem.
  - How they were captured: through the DevTools protocol (`Emulation.setDeviceMetricsOverride` at 390 × 2600).
- `about-mobile-full.png`: the whole About page at 390.

## Demo URLs

- `members/index.html?today=2026-10-06`: the hub on a later date (the next two exercises become ShakeOut and COMMEX).
- `members/exercises.html?today=2026-10-03`: SET is "Happening now", and WSDOT drops off.
- `members/documents.html?q=winlink` or `members/documents.html#forms`: a filtered library.
- Any page with `?verify=1`: outlines every fact that still needs checking.

## Known limitations

- **Launch blockers** (from `round3/_shared/README.md`):
  - The net-scripts link goes to a page that also links the net roster.
  - The ARES application's return address is interim.
  - The legal-basis section needs SCEM review.
  - The role addresses (join@, ec@, webmaster@) are proposals.
- **About's contents list is in the page twice:** as a rail on desktop and as a closed disclosure on phones. The tool counts both, which adds 30 words; the page is still well under budget.
- **About's Contact section has a minimum height**, so `about.html#contact` can land at the top. That leaves some empty space above the footer.
- **The rota shows the five rows in `data.js`.** With a later `?today=`, past rows are dimmed rather than replaced.
- **Fonts:** Lexend loads from Google Fonts. Offline, pages fall back to a system sans.
