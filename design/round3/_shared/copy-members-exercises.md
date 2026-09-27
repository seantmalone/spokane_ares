---
page: members/exercises.html
budget: 700 visible words in <main>
word_count: 387   # measured (README.md method)
h2_count: 5
ctas: 1 primary (WSDOT groups.io) plus 2 plain links (ARRL SET forms, public-service tips)
as_of: 2026-09-26 (no-JS fallback; JS recomputes from data.js)
source: round2/_shared/content-members-exercises.md, round2/_shared/assets/data.js
---

# Exercises & events: round-3 copy (edited)

How to read this file: only text between the COPY markers is visible, except lines starting with `> Build:`, which are notes. `[verify]` becomes `data-verify="true"` with no visible chip. `{#id}` is the element id. Inline `[text](target)` links sit on words already in the copy and add no words.

- `<title>`: Exercises & events | Spokane County ARES-ACS
- Meta description: What's next for members: exercises, what to bring, Winlink assignments and public-service events.
- No lede, breadcrumb or filter chips. The members sub-nav marks this page with `aria-current="page"`.

---

<!-- COPY -->

# Exercises & events

## Next up {#next-up}

**Bring to every exercise:** your [go-kit](documents.html#go-kits), charged batteries, a programmed handheld, and [ICS-213](documents.html#ics-213), [213RR](documents.html#ics-213rr), [214](documents.html#ics-214) and [309](documents.html#ics-309) forms.

### ARES-ACS / WSDOT exercise {#wsdot-2026}

> Build: `WSDOT` in the H3 is `<abbr title="Washington State Department of Transportation">`. `SPOKARES.events` id `wsdot-2026`. The status comes from the date: "Happening now" while today falls within start to end, otherwise "Starts Sat, Sep 26". This card is the page's primary action. Scope and assignments are not public, so the card says nothing more.

**Sat–Sun, Sep 26–27 · Happening now**

Extra form: [WSDOT 580-020, Road and Bridge Assessment](documents.html#wsdot-580-020)

[CTA: Exercise details on groups.io ↗ -> https://spokaneares-acs.groups.io/g/main] [verify]

### Simulated Emergency Test {#set-2026}

> Build: `SPOKARES.events` id `set-2026`, `tasks` as shown. Wrap EC, SEC, NTS, POTA and SOTA in `<abbr title>`: Emergency Coordinator, Section Emergency Coordinator, National Traffic System, Parks on the Air, Summits on the Air.

**Sat, Oct 3** · ARRL's national exercise. The Section asks you to:

1. Use Winlink to send a [WA State Field Situation Report](documents.html#wa-field-situation-report) to your EC and the SEC (AG7QP).
2. Send an NTS message to the Section Manager (KA7LJQ) and the SEC.
3. Take photos of your station and operation.
4. Make as many contacts as you can under POTA or SOTA rules.
5. Send an after-action report, with a participant list, to the SEC.

[ARRL SET forms ↗](https://www.arrl.org/simulated-emergency-test)

**After any exercise:** send your logs to the exercise lead. ACS members: get your yearly field operation signed off.

## Later this season {#upcoming}

> Build: `SPOKARES.events` where start is after the last Next-up card and `type` is not `public-service`. Two columns, one row each; link the title to the event's `links[0]` where data.js has one. DYFI gets `<abbr title="USGS Did You Feel It?">`. The ShakeOut row carries id `shakeout-2026`.

| When | What |
|---|---|
| Thu, Oct 15, 10:15 AM | [Great ShakeOut](https://www.shakeout.org/): send a [DYFI](https://earthquake.usgs.gov/data/dyfi/) report by Winlink, marked as an exercise [verify] |
| Sat, Oct 17, 9:00 AM–noon | SHARES training, for SHARES participants [verify] |
| Sat, Oct 24 | State COMMEX [verify] |
| Nov 18–25 | Washington operates as [W1AW/7](https://www.arrl.org/america250-was): optional shifts [verify] |
| Fri–Sat, Dec 4–5 | [SKYWARN Recognition Day](https://www.weather.gov/crh/skywarnrecognition) [verify] |
| Sat–Sun, Jan 23–24 | [Winter Field Day](https://winterfieldday.org/) |

## Winlink assignments {#winlink-assignments}

Answer on an ICS-213 unless another form is named. Send to NV2Z and AG7QP between 5:00 and 9:00 PM on the date shown. Radio preferred; Telnet OK.

| Date | Assignment | Form |
|---|---|---|
| Tue, Oct 13 | Did You Feel It report (ShakeOut practice) | DYFI |
| Tue, Oct 27 | A welfare message | Welfare Message / Quick Health & Welfare |
| Tue, Nov 10 | Where you expect to have Thanksgiving dinner | ICS-213 |
| Tue, Nov 24 | What you want Santa to bring you | ICS-213RR |
| Tue, Dec 8 | Justify to Santa that you were good this year | ICS-213 |
| Tue, Dec 22 | Your New Year's Eve plans | ICS-213 |

> Build: `SPOKARES.winlink.assignments` with date on or after today; `winlink.howTo` is already in 12-hour time.

## Public-service events {#public-service}

| Event | When | Volunteer through |
|---|---|---|
| Bloomsday | Sun, May 2, 2027 | NV2Z |
| Lilac Festival Armed Forces Torchlight Parade | Date not posted | NV2Z |
| Climb for the Cure | Sun, Jun 13, 2027 | NZ2S [verify] |
| Fun runs and bike races | As requested | NV2Z |

First event? Read [Preparing for public-service communications](documents.html#public-service-tips).

> Build: rows 1 and 3 from `SPOKARES.events` ids `bloomsday-2027` and `climb-2027` (`contact.callsign`); rows 2 and 4 are static. Call signs only.

## Past exercises {#past}

- 2025 · Field Day in Spokane Valley as W7GBU [verify]
- Oct 2022 · Rockford exercise
- Dec 2019 · "Hazard City" hospital deployment exercise [verify]
- Oct 2019 · Quarterly all-members exercise
- Jul 2019 · Kootenai County exercise
- Mar 2019 · EOC-to-EOC exercise: 20+ operators across the county
- Mar 2018 · Fifth-Saturday multi-county exercise
- Aug 2013 · EOC-to-EOC exercise

> Build: `SPOKARES.pastExercises` (round-3 data.js holds exactly these rows). Month and year only. Once the WSDOT exercise ends, add it at the top as "Sep 2026 · ARES-ACS / WSDOT exercise".

<!-- /COPY -->

---

## Round-3 edit pass (what changed and why)

- **Bring line:** the log sentence is cut; logs live in how-it-works.html#logs, and the form names link to their rows.
- **WSDOT card:** the sentence spelling out WSDOT is replaced by `<abbr>` in the H3.
- **SET task 1:** "(radio preferred, Telnet OK)" cut; the Winlink how-to line says it.
- **SKYWARN Recognition Day:** no location here (about.html#history holds "at NWS Spokane, every year since 1999"); the Dec 2017 Past row is cut.
- **Oct 13 Winlink row** reads "ShakeOut practice"; "marked as an exercise" appears once, on the ShakeOut row.
- **COMMEX** is just "State COMMEX".
- **Past** keeps one-off or notable exercises (8 rows). Cut: recurring rows that how-it-works.html#exercises already covers (Jun 2018 and 2012–13 Field Day, Oct 2021 and Oct 2004 SET, Dec 2017 SKYWARN Recognition Day); the 2025 Field Day stays as the most recent record.

## Left off this page (with where each fact lives now)

- Weekly nets, the GMRS net, simplex nights: how-it-works.html#nets and the hub rota. The 0730 Bunch: about.html#history. EWSEN nets: cut.
- Workshops, training meetings and the SCEM address: index.html#visit and the hub.
- Year at a glance: how-it-works.html#exercises.
- The 72-hour review: how-it-works.html#activation. Aug 2023 fires: how-it-works.html#activation (id `real-world`).
- Cut: the "Before, during and after" H2, the wellbeing quote, the ICS-213RR go-kit tip, the ShakeOut detail block, the Standing Order 1b tie-in, the webmaster photo request, the December-meeting note.
