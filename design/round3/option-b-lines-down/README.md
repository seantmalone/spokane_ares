# Option B: Carry the Message (round 3)

Round 3 is the simplification pass. The owner's feedback on round 2 was: "All three of these have just way too much info. It's overload. Do another pass on each to simplify. Minimize repeating information unless it's really important."

This round cuts every page to its job. Each fact now has one home, and every word comes from `../_shared/copy-*.md` under the rules in `../_shared/placement-map.md`. B keeps its look: night-to-dawn light, the red message line, amber numbered markers, Crimson Pro with Schibsted Grotesk. `DESIGN.md` covers the system, and its §0 lists the simplification rules. The round-2 version and its README are in `../../round2/option-b-lines-down/`.

To view the pages, open any file from disk (`file://`). There is no build step.

## Before and after

Visible words in `<main>`, measured by `design/tools/measure_pages.py`. "Before" is `round2/_metrics-before.json` and "after" is `_metrics-after.json`.

| Page | Words before | Words after | Change | Budget | H2s | CTAs (tool) | Height at 1440 (px) |
|---|---:|---:|---:|---:|---|---|---|
| Home `index.html` | 1,096 | **259** | −76% | 400 | 4 → 3 | 12 → 2 | 4,954 → 2,902 (≤ 3,200) |
| How it works | 3,802 | **909** | −76% | 1,200 | 13 → 5 | 13 → 3 | 19,582 → 5,206 |
| About ARES & ACS | 6,638 | **1,274** | −81% | 2,800 | 26 → 9 | 6 → 2 | 31,979 → 9,199 |
| Members hub | 1,145 | **124** | −89% | 300 | 7 → 3 | 8 → 2 | 4,522 → 1,202 |
| Documents & forms | 2,467 | **549** | −78% | 900 | 11 → 8 | 11 → 1 | 9,385 → 3,760 |
| Exercises & events | 2,098 | **427** | −80% | 700 | 6 → 5 | 22 → 1 | 9,689 → 2,218 |
| **Site** | **17,246** | **3,542** | **−79%** | 6,300 | | | 80,111 → 24,487 |

- **Repetition:** repeated 8-word phrases across pages fell from 444 to 6.
- **Key facts:** the number of pages that repeat each key fact fell, for example the net time from 6 pages to 3 and groups.io from 6 to 4.
- **Counts above 2 are allowed.** When a key fact shows on more than 2 pages, the extra mentions are one-line pointers that the placement map allows:
  - **Repeater and net time:** stated in full on How it works; Home's "listen Tuesdays, 8:00 PM, 147.300 MHz" and the hub's copyable rota line are pointers.
  - **ICS-213, task book and WSDOT:** each is explained on one page (How it works, About and Exercises). The other mentions are Documents rows, a hub "Most used" tile, the hub's next-two-events list, and the Exercises "Bring" and Winlink-form lines.
  - **groups.io:** explained once, in Home's step 2. The other mentions are the Documents Source cells, the hub's "Open slot?" line and the WSDOT card's link.
- **"Join steps" (5 pages) is a false positive.** The tool's pattern `0.?3` matches dates such as "2023" and "2013". The join steps appear only on Home.

## What changed

**Everywhere (the chrome)**
- The night utility bar is gone, along with the next-net line and "Copy radio settings" in the header.
- The footer is cut to 33 words: six links, the safety line and the mockup line.
- The members sub-nav is five items and has no dead `#` links. "Task books" goes to `about.html#training-path`.
- Times are 12-hour everywhere ("8:00 PM").
- Deep links land exactly under the header on a cold load.

**Home.** It is newcomer-first: a hero with Join (primary) and See how it works; What we do; Come visit; and Join in four steps.
- Cut: the "one message" figure (the story is told once, on How it works), the "Where are you starting from?" control, the interest form, the net card with offset, tone and net control, the photo, "Coming up", and the agency band. That band is now one line linking to About.

**How it works.** The six-step scrollytelling story became a one-screen opener: 4 hops with a static diagram. After it come five chapters: the net, activation, messages, training, and roles.
- Cut: "What members actually do", "Where we work", "Every hop is a post", "What it takes", the separate "When it was real" section, and the photo frames.
- Moved: public service and dated exercises now live on Exercises.

**About.** It has 9 sections instead of 26, with a 9-entry TOC.
- Cut: the ARES vs ACS table (now 4 one-line definitions), the FAQ, the glossary, "At a glance", the mission quote, the ARRL levels table (now one line) and the Standing Order 1b quote.
- Moved: meetings went to Home#visit, the join steps to Home#join, "What it takes" to the task-book table, and activation to How it works.

**Members hub.** It is now a switchboard: the next two exercises (WSDOT now, SET Oct 3), the next meeting, the Tuesday-net rota with one copyable settings line, and 4 tiles.
- Cut: the radio card, the check-in order, the sidebar readout, 8 quick links, "Staying current", the sections map, the groups.io section and "Who to ask".

**Documents.** The library is the page: 37 rows, down from 74, with descriptions of 8 words or fewer.
- Cut: the lede, the "Most used" block (now a filter chip), the status key and "Can't find it?".
- The three net scripts are now one row. "Not posted here" is 2 sentences.

**Exercises.** The page opens with the next event: "Bring" first, the WSDOT and SET cards, then one "After" line.
- It then lists later this season, Winlink assignments, public service and 8 past exercises.
- Cut: the chip filter, the timeline, before/during/after, the year at a glance (now undated on How it works) and the older past rows.

## Integration pass (2026-09-26)

- **Fixed: How it works on phones.** The story ran 1.3 screens before the net at 390×844. At 640px and below, the diagram is now hidden, so the net section starts at 836px; it was 1,114px. The hop list and the "A scenario" note still tell the story. Desktop is unchanged: the net section starts at 877px of a 900px screen.
- **Verified on all six pages, at 1440 and at a true 390:**
  - Every page is within its word budget, and Home is within the 3,200px height budget.
  - Each page has one H1 and no console errors.
  - No page scrolls horizontally.
  - Every internal link and id resolves over `file://`, and there are no `href="#"` links.
  - The header, footer and sub-nav are identical on every page, with the right item marked active.
  - The mobile menu opens and closes, and Escape closes it.
  - All 5 sub-nav items are visible at 390.
  - Every Tab stop shows a focus ring.
  - 33 deep links cold-loaded from Home land 0px off at both widths. The exception is a target near the bottom of a short page, which can only scroll as far as the page ends.

## Screenshots (`shots/`)

- **Stock headless shots:** `<page>-desktop-full.png` (1440×3400) and `<page>-mobile.png`, for `home`, `how`, `about`, `members`, `documents` and `exercises`, plus `home-desktop-fold.png` (1440×900). The stock 390 command lays pages out at about 500px and crops them, so use the next set for mobile review.
- **True 390 shots:** `<page>-mobile-390-true.png` is a full-page layout at a true 390×844. `home-` and `members-mobile-390-menu.png` show the open menu.
- **Full-length desktop shots:** `how-`, `about-` and `documents-desktop-all.png` capture the pages taller than 3,400px.

## Before launch

These carry over from `../_shared/README.md`:
- the net-scripts link (roster exposure)
- the ARES application return address (join@ is not live yet)
- SCEM review of the legal basis

Every `data-verify="true"` item also still needs checking.
