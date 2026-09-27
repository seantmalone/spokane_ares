---
page: members/index.html
budget: 300 visible words in <main>; max 3 H2s
word_count: 104 (measured, README.md method; visible text in <main>: H1, updated line, search label and button, H2s, table headers, caption and cells, tile labels; the placeholder attribute is not counted)
h2_count: 3
ctas: 1 primary (Search) + 6 links (Copy, What to bring, 4 tiles); rota notes link where marked
spec: round3/_shared/placement-map.md §3.4, facts #5, #6, #8, #11, #15, #49, #53, #59, #60
data: round3/_shared/assets/data.js (meta.asOf, radio.copyText, rota, events, meetings, winlink); fallback text below is correct as of Sat, Sep 26, 2026
---

# For Members hub: lean copy (round 3, edited)

## Builder notes (not visible copy)

- **Chrome (not counted):** the primary nav with Join. The members sub-nav: This week (`index.html`) · Exercises & events (`exercises.html`) · Documents & forms (`documents.html`) · Task books (`../about.html#training-path`) · groups.io ↗ (`https://spokaneares-acs.groups.io/g/main`). The minimal footer.
- **(data)** means the item renders from data.js. The text shown here is the no-JS fallback. `[verify]` becomes `data-verify="true"`, with no visible chip.
- **Call signs only.** No personal names anywhere on the page, including the rota, until the owner decides (REVIEW "Two decisions" #2). Round-3 data.js no longer carries rota names.
- **12-hour times only.** Never write "2000".
- **Each dated net fact appears once:** the rota row is the only place that says who has net control and whether a Tuesday is simplex, Winlink or GMRS. There is no separate "Next net" line; the highlighted first rota row is the next net.
- **Not on this page:** the address, the alternate repeater, NOAA, the net's check-in order, what to bring, Winlink response rules and assignment text, standing requirements, who to ask, the groups.io explanation, the FOUO note and the net-report line. Each has its own home elsewhere.
- **Deep links:** set `scroll-margin-top` on `#search`, `#this-week`, `#rota` and `#quick-links` to the header height plus the sub-nav height. Give the data widgets a `min-height` so the page doesn't shift after it loads.

---

<!-- COPY -->

# For members

As of Sat, Sep 26

> Build: `meta.asOf` / `fn.today()`. Search (`#search`) is a `<form action="documents.html" method="get" role="search">` with a field named `q`; placeholder attribute "ICS-213, net script, task book" (not counted).

Search documents and forms [CTA: Search -> documents.html?q=…]

## This week {#this-week}

> Build: (data) `fn.upcoming({ types: ['exercise', 'training'], limit: 2 })`, then `fn.nextMeetings()` first item only.

**Exercises**

- Now through Sun, Sep 27 · ARES-ACS / WSDOT exercise
- Sat, Oct 3 · Simulated Emergency Test

[CTA: What to bring -> exercises.html#next-up]

**Next meeting:** Sat, Oct 10, 9:00 AM · Second Saturday Workshop

## Tuesday net {#rota}

8:00 PM · W7GBU 147.300 MHz, +600 kHz, 100 Hz [CTA: Copy -> copies radio.copyText]

> Build: (data) `rota` from today onward, 5 rows; the Note comes from `fn.netOn(date)`: `mode` simplex → "Simplex" [verify], winlink → "Winlink night" linked to `exercises.html#winlink-assignments`, `gmrsSameDay` → "GMRS net, 7:30 PM" [verify]. Highlight the first row (the next net). Style Open cells as invitations, not errors; an Open cell links to the line below the table.

| Tuesday | Net control | Note |
|---|---|---|
| Sep 29 | NZ2S | Simplex [verify] |
| Oct 6 | **Open** | |
| Oct 13 | AE7RJ | [Winlink night](exercises.html#winlink-assignments) |
| Oct 20 | WA7LNC | GMRS net, 7:30 PM [verify] |
| Oct 27 | **Open** | [Winlink night](exercises.html#winlink-assignments) |

Open slot? Tell the Net Manager (AG7QP) on the net or on groups.io.

## Most used {#quick-links}

> Build: four tiles, labels only (no hints, no format tags).

- [CTA: Net script -> documents.html#net-scripts]
- [CTA: ICS-213 -> documents.html#ics-213]
- [CTA: ICS-309 -> documents.html#ics-309]
- [CTA: ACS Task Book -> documents.html#acs-task-book]

<!-- /COPY -->

---

## Round-3 edit pass (what changed and why)

- The "Next net" line is gone: its date, net control and simplex note repeated the first rota row one screen apart. The copyable settings line now heads the rota, and the H2 is "Tuesday net".
- The This-week Winlink line is gone: the rota's "Winlink night" note links to the assignment instead, so Oct 13 appears once. The assignment wording lives on exercises.html only.
- The "Winlink assignments" and "groups.io Files" tiles are gone: the first repeated the rota link, the second repeated the always-visible sub-nav link. Four tiles remain.
- The Net script tile targets the merged `#net-scripts` row on Documents.
