# Option C: Open Channel (round 3)

This is the round-3 simplification pass on option C. It answers the owner's feedback on round 2: "All three of these have just way too much info. It's overload. Do another pass on each to simplify. Minimize repeating information unless it's really important."

To view it, open `index.html` from disk. There's no build step and no server. The look (dusk blue, Bricolage Grotesque and Atkinson Hyperlegible, real photos) and the four sections are the same as round 2. The difference is the amount of content: each fact now has one home. `DESIGN.md` §1 lists the rules, and `../_shared/placement-map.md` says where each fact lives.

## Before and after

Word counts are visible words in `<main>`, measured by `design/tools/measure_pages.py`. "Before" comes from `../../round2/_metrics-before.json` and "after" from `_metrics-after.json`.

| Page | Words before | Words after | Budget | Cut | Height at 1440 (before → after) | H2s (before → after) |
|---|---:|---:|---:|---:|---|---|
| Home | 1,644 | **258** | 400 | 84% | 5,214 → **2,935px** (cap 3,200) | 6 → 3 |
| How it works | 4,146 | **870** | 1,200 | 79% | 15,427 → 4,844px | 11 → 5 |
| About ARES & ACS | 7,055 | **1,227** | 2,800 | 83% | 35,820 → 8,873px | 26 → 9 |
| Members hub | 1,215 | **121** | 300 | 90% | 5,597 → 1,316px | 9 → 3 |
| Documents & forms | 2,264 | **531** | 900 | 77% | 10,884 → 3,900px | 11 → 8 |
| Exercises & events | 2,554 | **408** | 700 | 84% | 8,656 → 2,846px | 6 → 5 |
| **Site** | **18,878** | **3,415** | 6,300 | **82%** | | |

- Every page is under its word, H2 and CTA budget.
- Home has 2 CTAs in `<main>` (it was 14): **Join the team** and **Plan your first visit**.
- Sentence-level repetition went from 462 repeated 8-word runs to **0**.

## What was cut or moved

- **Home** now has four parts: a hero, What we do, Come visit, and Join in four steps.
  - **Cut:** the This-week card, the audience row, the visit planner and RSVP form, "Training with partners", "Who we serve" and "Here's what it takes".
  - **Moved:** the net offset, tone and net control now appear only on How it works and the members hub.
- **How it works** has five sections: the weekly net, activation, message handling, training and roles.
  - **Cut:** the month calendar, the "Where we work" map, "Your first month" and the newcomer questions.
  - **Changed:** the role cards became an 8-row table. The chip bar went from 9 chips to 5. Dates and public service moved to Exercises.
- **About** went from 26 H2s to 9, with a 9-entry table of contents.
  - **Cut:** the "short version" boxes, the FAQ and the glossary. Glossary definitions now show as `<abbr>` titles.
  - **Shortened:** the ARES/ACS comparison table became four one-line definitions, and the ARRL levels table became three lines.
  - **Moved:** "Where we meet" now lives in Home's Come visit section.
- **Members hub** is now a switchboard: the next two events, the next meeting, the net settings line with a 5-week rota, and 4 tiles.
  - **Cut:** the grouped directory, the radio strip, "Staying current", "Who to ask" and the groups.io section.
  - **Moved:** Winlink assignments now live on Exercises.
- **Members sub-nav** went from 11 items (6 of them dead `#` links) to 5: This week, Exercises & events, Documents & forms, Task books (which links to `about.html#training-path`) and groups.io ↗.
- **Documents:** the library is the whole page. It went from 74 rows to 37, and no row description is longer than 8 words. The lede, the status key and the "Hide Coming back" toggle are gone.
- **Exercises:**
  - The 5-month agenda (with its tabs and filters) became a 6-row "Later this season" table.
  - The before, during and after lists became one "Bring" line and one "After" line.
  - "Year at a glance" is cut.
  - The past-exercises list is down to 8 rows.
- **Footer:** six links, one Contact link, the safety line and the mockup note. There's no address and no schedule.

## Key facts that still appear on more than one page

The measuring script counts the pages whose text matches each fact's pattern. Where a fact matches more than 2 pages, the extra pages carry only a pointer that `placement-map.md` §1 allows.

| Fact | Pages | Home | Other pages (all pointers the map allows) |
|---|---:|---|---|
| 147.300 MHz / 8:00 PM net | 3 | How it works `#nets` | Home: "Listen… 8:00 PM, 147.300 MHz" (no offset or tone). Hub: the one copyable settings line (rows 5–6). |
| Join steps | 5 | Home `#join` | None. The other 4 pages are false matches: the pattern `0.?3` catches "2023", "2013" and "12:30–3:30". |
| County application | 3 | About `#acs-path` | Home step 3 (one line). The Documents library row. |
| ICS-213 | 4 | How it works `#ics-213` | A hub tile. The Documents row. On Exercises, the "Bring" line and the default Winlink reply form (rows 36, 47, 49, 59). |
| Task book | 3 | About `#training-path` | A hub tile. The Documents download row. |
| WSDOT exercise | 3 | Exercises `#next-up` | The hub's next-two events (must-fix). The Documents form row. |
| groups.io | 4 | Home step 2 | The hub's "Open slot?" line. Source cells on Documents. The Exercises details button (row 24). |

The meeting address now appears on 1 page (it was on 4).

## Round-2 must-fixes applied

- Home is newcomer-first: it has no net control, offset, tone or rota.
- There are no `href="#"` links. "Privacy (coming)" is plain text.
- Times are written as "8:00 PM". Nothing uses 24-hour times like "2000".
- Deep links land in the right place on a cold load. I tested 154 anchor and width pairs, including the aliases, at 1440 and at a true 390. All land within 4px, or at the bottom of a short page.
- The hub shows the next two events (WSDOT, now; SET, Oct 3) and the next meeting.

## Integration checks (2026-09-26)

- **Links:** every internal link and `#anchor` resolves over `file://`. The one exception is `documents.html#most-used`, a filter chip that is hidden until site.js runs and handles it.
- **Chrome:** the header, footer, icon sprite and members sub-nav are identical on all six pages, apart from `aria-current`. Active states are correct on every page.
- **Structure:** each page has one `h1` and no duplicate ids. There are no console errors and no failed loads.
- **Layout at 390:** there's no horizontal page scroll. The one sideways-scrolling row is the Documents filter chips, which is intended and fades at the edge.
- **Mobile menu:** it opens, and Escape closes it and returns focus to the button. The members sub-nav shows all 5 pills at 390.
- **Focus:** the skip link comes first, and focus shows as a 3px ring.
- **Scripts off:** all six pages still read and navigate.
- **Integration edit:** About's visible "[review date]" placeholder is now a quiet blank line, with "(date to come)" for screen readers. `DESIGN.md`'s status line is updated.

## Known limitations

- **About is still 8,873px tall at 1440.** It's the reference page, and its 1,227 words are well under the budget. The sticky table of contents handles navigation. Space is reserved below Contact so that `#contact` can scroll to the top.
- **Mobile screenshots use device emulation.** Headless Chrome won't lay out a window narrower than 500px: `--window-size=390,…` renders at 500px and crops, which cuts off the right edge. So the `*-mobile.png` files are taken with device emulation at a true 390px.
- **Launch blockers are unchanged:**
  - The net-scripts link
  - The ARES application's return address
  - The legal basis still needs review by SCEM (Spokane County Emergency Management)

  See `../_shared/README.md` for details. Uncertain facts carry `data-verify="true"`, with no visible chip.

## Screenshots (`shots/`)

- For `home`, `how`, `about`, `members`, `documents` and `exercises`:
  - `-desktop-full.png` is 1440×3400. Longer pages are cut off at 3400px.
  - `-mobile.png` is 390×2600.
- `home-desktop-fold.png` is 1440×900.
