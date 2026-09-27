# Round 3 shared copy: read me first

**Why round 3 exists.** The owner's feedback on round 2 was: "All three of these have just way too much info. It's overload. Do another pass on each to simplify. Minimize repeating information unless it's really important." Round 3 cuts every page to its job and gives each fact **one home**.

**Status (2026-09-26).** This is the managing editor's copy, after the repetition and loss audits. Builders of options A (Figure One), B (Carry the Message) and C (Open Channel) take all wording from these files. Where the two audits conflicted, the fact stays **once**, in its best home.

## Files

| File | What it is |
|---|---|
| `placement-map.md` | The rules: one home per fact (§1, 73 rows), nav, footer and anchor map (§2), page specs (§3), roll-up (§4) |
| `copy-index.md`, `copy-how-it-works.md`, `copy-about.md` | Public pages |
| `copy-members-index.md`, `copy-members-documents.md`, `copy-members-exercises.md` | Members pages |
| `assets/data.js` | `window.SPOKARES`, trimmed from round 2 to what round 3 shows. Frozen date 2026-09-26. Helpers work unchanged: `today`, `fmtDate`, `fmtTime`, `netOn`, `nextNet`, `nextMeetings`, `upcoming`, `documents`, `docById` |

Round-2 `content-*.md` and `round2/_shared/assets/data.js` remain the archive and the source of every fact. Round 3 cuts; it never adds.

## Placement map in one screen

- **Only the chrome repeats.** The primary nav with **Join the team**, the members sub-nav (This week · Exercises & events · Documents & forms · Task books → About · groups.io ↗), and a footer of 35 words or fewer: links, one Contact link and "Volunteers, not first responders. In an emergency, call 911."
- **Home** is for newcomers. It covers what we do, how to visit and how to join in four steps. It has no net control, offset, tone or rota.
- **How it works** holds operations: the net and its settings, activation, readiness levels, message handling, the yearly rhythm (no dates) and roles.
- **About** is the reference, with a 9-entry TOC: ARES/ACS definitions, legal basis, partners and agreements, leadership, the two join paths, task books and kits, licensing, history, and the role addresses.
- **Members hub** is a switchboard: the next two exercises, the next meeting, the Tuesday-net rota with a copyable settings line, and 4 tiles.
- **Documents:** the library is the page. It has 37 rows with descriptions of 8 words or fewer, and no lede.
- **Exercises:** what's next and what to bring, then later this season, Winlink assignments, public service and 8 past exercises.
- **Pointers.** A fact may appear outside its home only as one line or link that the map allows (for example, Home's "Listen Tuesdays, 8:00 PM, 147.300 MHz").
- **Other rules:** 12-hour times ("8:00 PM", never "2000"), no `href="#"`, call signs only (no personal names, the rota included), and `[verify]` becomes `data-verify="true"` with no visible chip.

## Budgets and measured word counts

These are visible words in `<main>`, headings, table cells, figure labels and button text included.

| Page | Budget | Max H2 | A | B | C | H2 | CTAs |
|---|---:|---:|---:|---:|---:|---:|---|
| `index.html` | 400 | 5 | 272 | 245 | 243 | 3 | 6 (≤ 6); ≤ 3,200px desktop |
| `how-it-works.html` | 1,200 | 6 | 851 | 849 | 851 | 5 | 1 closing line |
| `about.html` | 2,800 | 9 | 1,181 | 1,181 | 1,181 | 9 | 2 buttons |
| `members/index.html` | 300 | 3 | 104 | 104 | 104 | 3 | Search + 6 links |
| `members/documents.html` | 900 | 7 + not-here | 441 | 441 | 441 | 8 | Search; row titles |
| `members/exercises.html` | 700 | 5 | 387 | 387 | 387 | 5 | 1 primary + 2 links |
| **Site** | **6,300** | | **3,236** | **3,207** | **3,207** | | |

For comparison, round 2 had 17,805 words for A, 17,246 for B and 18,878 for C. Round 3 is about 18% of that. The unused budget leaves room for each option's own visual voice. It is not room for new facts.

**How the words were counted.** A script counts the words between the `<!-- COPY -->` and `<!-- /COPY -->` markers in each `copy-*.md`. It includes lines tagged `{A}`, `{B}` or `{C}` only for their own option. It skips HTML comments, `> Build:` notes, `[verify]` markers, `{#id}`s and link targets. Built pages will be a little higher, because they add alt text in some options and data-driven states. Re-measure the built `<main>` before hand-off (see the placement map §4 checks).

## What this editing pass changed (summary)

- **About:** the ARES/ACS comparison table was replaced by two one-line definitions. The RACES line was corrected: the county's RACES *program* became part of ACS. The 97.407 exception was added. Partners are now a 4-line list, with the Red Cross and IEVHFRA under Agreements only. The ARRL levels are now one line. The FEMA Student ID tip, the 2021 timeline entry, the duplicate club-license line and the Contact extras were cut.
- **How it works:** each fact on this page is now stated once: the 8:00 PM time, net control, the Winlink practice and the alternate repeater. "When it was real" was folded into the activation intro. "ICS-213" and "Logs" were merged. The exercises list no longer repeats the net or public service. "Readiness levels" is now the site-wide term.
- **Home:** step 1 now goes to `about.html#ares-path`, which says where to return the application. Step 2 has inline links to the net and groups.io. The agency line is shorter. The lede no longer names the radio room. The "What ACS asks" line now mentions the year-one courses. B has no scenario figure on Home, so the story is told once, on How it works. C keeps #visit below #what-we-do so that "Plan your first visit" still has a job.
- **Hub:** the "Next net" line and the Winlink line were dropped. The rota is the one place for dated net facts, and its Winlink notes link to the assignments. There are now 4 tiles.
- **Documents:** cut the lede, the groups.io line and the webmaster mailto from "Not posted here", and the alert-levels row. The three net scripts are now one row. The go-kit title no longer has hour ranges.
- **Exercises:** cut the log sentence, the WSDOT sentence, the SRD location, the duplicate "marked as an exercise" wording and the Telnet note in SET task 1. Past exercises are down to 8 rows.
- **Placement map:** updated to match all of the above. Two internal conflicts were settled in favour of the "Remove from" column: groups.io is not listed on About#contact, and Documents has no webmaster mailto.

## Launch blockers raised in this pass

1. **Net scripts link (D14 #1).** The row links to `ag7qp.com/spokane-county-ares-acs`, the same page that publicly links the 2026-08-04 net roster PDF. The script files also carry a personal e-mail (D13 #2). Keep `data-verify` on the row. Before launch, AG7QP needs to remove the roster link and supply clean masters, or the row switches to "Soon".
2. **ARES application return address (D13 #2).** The interim form DOCX sends completed applications to a personal e-mail. `about.html#ares-path` says to hand it in at a meeting or email join@. That role address is still proposed, not live (D11).
3. **Legal basis.** The whole section still needs SCEM review before launch.
