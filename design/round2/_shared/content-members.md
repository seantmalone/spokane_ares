# For Members: Overview (`members/index.html`): draft copy

- **Job of the page:** the members' home base. Public information that mostly matters to current members, organised so a member finds what they need **fast**: this week at a glance, quick links to the top tasks, the net-control rota, the Winlink schedule, and a directory of every members sub-section. **No login.** Anything member-only stays on groups.io and is only linked.
- **Members menu:** every `members/*.html` page shows the secondary menu defined in `ia.md` §4 (Overview; Operate: Nets & net control, Winlink & digital, Repeaters & programming; Prepare: Exercises & events, Training & task books, Go-kits & readiness; Reference: Documents & downloads, Forms, Contacts & roles; groups.io ↗). Non-mocked items link to `#`.
- **Conventions:** **(data)** = rendered from `assets/data.js` (the text here is the no-JS fallback as of 2026-09-26). `[verify]` = `data-verify="true"`. Relative links from `members/` start with `../` for top-level pages.
- **Density:** this page can be denser than the public pages (tables, compact cards, 24-hour times are fine), but keep AA contrast and 44 px tap targets.

---

## 0. Head and page intro

- **`<title>`:** For Members | Spokane County ARES-ACS
- **Meta description:** This week's net and net control, exercises, Winlink assignments, scripts, forms and task books for Spokane County ARES-ACS members.

**Breadcrumb (optional):** Home › For Members

**H1:** For members

**Lede:**
> This week's net, exercises and assignments, plus the scripts, forms and task books you reach for. Everything here is public; member-only files stay on groups.io.

**Updated line (data: `SPOKARES.meta.asOf`):** Schedule as of Sat, Sep 26, 2026.

---

## 1. Search (`#search`)

A single field near the top, before the fold on mobile.

- **Label:** Search documents and forms
- **Placeholder:** e.g. "preamble", "ICS-213", "task book"
- **Button:** Search
- **Markup:** `<form action="documents.html" method="get" role="search">` with `name="q"`. Without JS it opens the library with the query; with JS it may show live matches from `SPOKARES.fn.documents({q})` under the field (top 5, then "See all results").
- **Popular searches (chips):** Net script · ICS-213 · ACS Task Book · Winlink · Go-kit · ARES application

---

## 2. This week (`#this-week`) **(data)**

**H2:** This week

Three to four compact cards, in this order. No status badge of any kind.

**Card 1: Next net** (`SPOKARES.fn.nextNet()`)
> **Tuesday net · Tue, Sep 29 · 2000**
> Net control: **NZ2S** (Jeff Banke)
> **Fifth Tuesday: simplex net.** Start on the ARES-ACS simplex frequency, then move to W7GBU for announcements, traffic and anyone who missed simplex. Have paper and pencil ready for read-backs. `[verify]`
> Links: Simplex script → `documents.html#net-preamble-simplex` · Weekly script → `documents.html#net-preamble-weekly`

Variants the card must handle (from the data):
- **Winlink night** (2nd/4th Tuesday): "Winlink night: paper and pencil ready. Assignment: {task}" and a link to the Winlink schedule (`#winlink`).
- **Open slot:** "Net control: **open**. Can you take it? Tell the Net Manager (AG7QP) on the net or through groups.io." (Styled as an invitation, not an error.)
- **Not yet published:** "Net control: see groups.io."
- **3rd Tuesday:** add "ACS GMRS net at 1930 before the main net." `[verify]`

**Card 2: This weekend / happening now** (`fn.upcoming({days: 7, types: ['exercise','training']})`)
> **Now through Sun, Sep 27 · ARES-ACS / WSDOT exercise**
> A joint exercise with the Washington State Department of Transportation. Log your activity on an ICS-214 and your messages on an ICS-309.
> Link: Exercise details → `exercises.html#upcoming`

**Card 3: Next Saturday** 
> **Sat, Oct 3 · Simulated Emergency Test (SET)**
> Winlink Field Situation Report to your EC and the SEC; an NTS message to the SM and SEC; photos; POTA/SOTA contacts; an AAR with participants.
> Link: SET tasks → `exercises.html#set-2026`

**Card 4: Next in person** (`fn.nextMeetings()`)
> **Sat, Oct 10 · Second Saturday Workshop, 0900–1200** · Winlink workshop 1230–1530 `[verify]`
> **Thu, Oct 15 · Third Thursday training meeting, evening** `[verify]`
> SCEM, 1121 W Gardner Ave.

**Below the cards (one line):**
> Open net-control slots: **Tue, Oct 6** and **Tue, Oct 27**. [Take a slot](#rota)

---

## 3. Radio settings strip

Compact, copyable, amateur facts only.

| | Frequency | Offset | Tone | Notes |
|---|---|---|---|---|
| **W7GBU** (primary) | 147.300 MHz | +600 kHz | 100 Hz | The Tuesday net and emergency operations |
| **Alternate** (IEVHFRA) | 146.880 MHz | −600 kHz | 123 Hz | If W7GBU is down or overloaded `[verify]` |
| **NOAA Weather Radio** | 162.400 MHz | — | — | WXL86, receive only |

**Button:** Copy W7GBU settings (copies `SPOKARES.radio.copyText`).

**Note (small):**
> County, hospital and SHARES channels are never posted here. They're in the ACSFOG on groups.io.

**Link:** Repeaters & programming → `#`

---

## 4. Quick links (`#quick-links`)

**H2:** Quick links

Eight tiles, maximum. Each: icon, label, one-line hint, format tag.

| # | Label | Hint | Target |
|---|---|---|---|
| 1 | **Net script** | Weekly preamble, 2026-03-04 · DOCX | `documents.html#net-preamble-weekly` |
| 2 | **Net control rota** | Who has the net, and open slots | `#rota` |
| 3 | **Winlink assignment** | Next: Oct 13, DYFI report | `#winlink` |
| 4 | **ICS-213 message** | FEMA form, plus how to fill it in | `documents.html#ics-213` |
| 5 | **ICS-309 comm log** | Keep it during every ACS operation | `documents.html#ics-309` |
| 6 | **ACS Task Book** | v2024-08-09 · DOCX | `documents.html#acs-task-book` |
| 7 | **Exercises & events** | WSDOT now · SET Oct 3 · COMMEX Oct 24 | `exercises.html` |
| 8 | **groups.io files** ↗ | ACSFOG, roster, exercise plans (members only) | `https://spokaneares-acs.groups.io/g/main/files` |

---

## 5. Net control rota (`#rota`) **(data: `SPOKARES.rota`)**

**H2:** Net control rota

**Intro:**
> Members take turns as net control for the Tuesday net. Members should check in at least once each month.

**Table** (caption: "Tuesday net control, September–October 2026"):

| Date | Net control | Notes |
|---|---|---|
| Tue, Sep 22 | KE7RAP, Bob Peterson | *(past)* |
| **Tue, Sep 29** | **NZ2S, Jeff Banke** | Fifth Tuesday: simplex `[verify]` |
| Tue, Oct 6 | **Open** | [I can take it](#rota-open) |
| Tue, Oct 13 | AE7RJ, Randall Jones | Winlink night |
| Tue, Oct 20 | WA7LNC, Gordon Grove | ACS GMRS net at 1930 `[verify]` |
| Tue, Oct 27 | **Open** | Winlink night · [I can take it](#rota-open) |

- Past rows are de-emphasised (or hidden behind "Show past weeks"). The next row is highlighted.
- Names appear only here, as published on the rota (`ia.md` §8.5).

**Taking a slot (`#rota-open`):**
> Open slot? Tell the Net Manager (AG7QP) on the Tuesday net, or post on groups.io. First time? The Net Manager coaches new net controls, and the script walks you through every step.

**After your net:**
> Send your net report (the net's duration and the call signs that checked in) as soon as you can, to the address in the current script. `[verify]` (a role address will replace the personal e-mail in the script)

**Links:** Weekly script → `documents.html#net-preamble-weekly` · Principles of a Net Control Station → `documents.html#ncs-principles` · Full rota and net schedule → `#` (Nets & net control)

---

## 6. Winlink assignments (`#winlink`) **(data: `SPOKARES.winlink`)**

**H2:** Winlink assignments

**How to respond (one line, prominent):**
> Answer on an **ICS-213** unless another form is named. Send to **NV2Z** and **AG7QP** between **1700 and 2100** on the date shown. Radio is preferred; Telnet is acceptable.

| Date | Assignment | Form |
|---|---|---|
| Tue, Sep 22 | Steps, actions and precautions for extreme cold | Quick Message *(past)* |
| **Tue, Oct 13** | **Submit a Did You Feel It report. Mark it as an exercise.** | DYFI |
| Tue, Oct 27 | Submit a welfare message | Welfare Message / Quick Health & Welfare |
| Tue, Nov 10 | Where you expect to have Thanksgiving dinner | ICS-213 |
| Tue, Nov 24 | What you want Santa to bring you | ICS-213RR |
| Tue, Dec 8 | Give Santa a justification for your being good this year | ICS-213 |
| Tue, Dec 22 | Your New Year's Eve plans | ICS-213 |

**Links:** Winlink: getting started → `documents.html#winlink-intro` · Winlink workshop: 2nd Saturdays, 1230–1530 `[verify]` · Winlink & digital → `#`

---

## 7. Your standing requirements (`#requirements`)

**H2:** Staying current

A static checklist (visual checkboxes are fine, but nothing is saved; this is a reference, not a tracker).

**Every month**
- Check into a net (weekly encouraged).

**Every quarter**
- Attend a Third Thursday meeting or a Second Saturday workshop (monthly encouraged).

**Every year**
- Take part in one field operation: an activation, an exercise or a public-service event.
- Check your personal kit (24 h minimum, 72 h preferred) and your radio kit (2 m / 70 cm for 24–72 h).
- Program the repeater and simplex channels into your handheld.
- **Radio Operators and up:** serve as net control at least once.
- **Senior Radio Operators:** send and receive Winlink; lead or assist an exercise, activation or event.

**Always**
- Keep an **ICS-309** during ACS radio operations; keep an **ICS-214** during exercises and activations (RADO and up).
- Keep your **ALERT Spokane** registration current (it now runs on Regroup) → `https://www.spokanecounty.gov/3007/Alert-Spokane`.

**Every two years**
- First Aid/CPR (RADO and up).
- SKYWARN spotter training (encouraged).

**Link:** Full task-book levels → `../about.html#acs-task-book` · Training & task books → `#`

Source: `interim-ag7qp/acs-task-book.md` (v2024-08-09).

---

## 8. Members sections directory (`#sections`)

**H2:** Everything for members

A card grid mirroring the members menu, so the page itself works as a map. One line each.

| Card | One-liner | Link |
|---|---|---|
| **Nets & net control** | Net schedule, the full rota, current scripts and the NCS guide. | `#` |
| **Winlink & digital** | Assignments, the Saturday workshop, setup and forms. | `#` |
| **Repeaters & programming** | W7GBU and alternate settings, programming cheat sheets, etiquette. | `#` |
| **Exercises & events** | What's coming, what to do, and after-action steps. | `exercises.html` |
| **Training & task books** | ACS and ARRL task books, FEMA and ARRL courses, orientation. | `#` |
| **Go-kits & readiness** | Kit lists, alert levels explained, ALERT Spokane. | `#` |
| **Documents & downloads** | Every script, form, guide and presentation, searchable. | `documents.html` |
| **Forms** | ICS, state and ARRL forms from their official sources. | `documents.html#forms` |
| **Contacts & roles** | Who does what, by role and call sign, and where things go. | `#` |

---

## 9. On groups.io (members only) (`#groups-io`)

**H2:** On groups.io

**Intro:**
> Our groups.io group is the member hub: the e-mail list, calendar, files and wiki. A moderator approves each new member, and the archive, files and wiki are members-only.

| What | Where |
|---|---|
| **Files:** the Spokane ACS Field Operating Guide (ACSFOG, half- and full-page), the net roster, exercise plans | Files ↗ `https://spokaneares-acs.groups.io/g/main/files` |
| **Calendar:** meetings, nets and events | Calendar ↗ `https://spokaneares-acs.groups.io/g/main/calendar` |
| **Subgroups:** Digital-Modes, HamWan, Hospital-Coordination, Leadership, SHARES, Winlink, WinterFieldDay (some are restricted) `[verify]` | Subgroups ↗ `https://spokaneares-acs.groups.io/g/main/subgroups` |
| **Not a member yet?** | Apply to join ↗ `https://spokaneares-acs.groups.io/g/main` |

**Note:**
> Never copy ACSFOG content, county channels or the roster onto a public site or social media. Channels and procedures are covered by the county's non-disclosure agreement, and the roster holds members' personal details.

---

## 10. Who to ask (`#help`)

**H2:** Who to ask

Compact, by task (role + call sign; role address). All `[verify]`.

| I want to… | Role | Call sign |
|---|---|---|
| Take a net-control slot, fix the rota | AEC, Net Manager | AG7QP |
| Ask about Winlink or an assignment | AEC, Public Events; Winlink coordinator | NV2Z |
| Volunteer for Bloomsday or the Torchlight Parade | AEC, Public Events | NV2Z |
| Ask about the radio room, trailer or programming | AEC, Logistics & Comms | K7DSR |
| Ask about the hospital team | AEC, Hospital Coordinator | K7MHG |
| Get a task-book level signed off | Emergency Coordinator | W7TSC |

**Role addresses:** join@spokares.org (applications, orientation) · ec@spokares.org (the emergency coordinator) · webmaster@spokares.org (this site) `[verify]`

**Something wrong on this page?** Tell webmaster@spokares.org `[verify]`.

---

## 11. Outlines for the non-mocked sub-sections (for later; not built in round 2)

- **Nets & net control (`members/nets.html`):** the full net table (weekly, Winlink nights, 5th-Tuesday simplex, GMRS, 0730 Bunch, EWSEN); the full rota with past weeks; how to take a slot; the three current scripts; the NCS guide (K7BFL); net reports; the minute-by-minute run of the net (from `content-how-it-works.md` §4).
- **Winlink & digital (`members/winlink.html`):** assignment schedule and how to respond; the 2nd-Saturday workshop; setup (Winlink Express, PUBLIC EMCOMM service code, Telnet first, then radio); WA State forms in Winlink; map-enabled forms; recovered decks (Scripting 101, ARDOP 101, SignaLink packet). No gateway frequency until NV2Z confirms.
- **Repeaters & programming (`members/radio.html`):** W7GBU 147.300 and 443.400 `[verify]`; the 146.880 alternate; the GMRS net repeater (once cleared); programming cheat sheets; repeater etiquette. A fixed notice: "County, hospital, SHARES and 800 MHz channels are in the ACSFOG on groups.io and are never posted here."
- **Training & task books (`members/training.html`):** ACS levels with sign-off rules; ARRL levels; FEMA SID and IS courses; ARRL EmComm; HIPAA; First Aid/CPR and defensive driving through SCEM; AUXCOMM, COMT, SHARES (for SHARES call-sign holders), field shelter; orientation; SKYWARN.
- **Go-kits & readiness (`members/readiness.html`):** go-kit tiers (2–6 h, 12–24 h, 24–72+ h); personal and station equipment lists; forms to carry; the ICS-213RR go-kit tip; alert levels explained (never a live status); ALERT Spokane; mental health note from the go-kit guide ("Watch out for your co-workers and yourself").
- **Contacts & roles (`members/contacts.html`):** the leadership table (role + call sign); "who to ask" by task; role addresses; where applications, net reports and Winlink responses go.

---

## Sources for this page

`interim-ag7qp/spokane-county-ares-acs.md` (rota, events, Winlink assignments), `acs-task-book.md`, `new-members-orientation.md`, `winlink.md`, `go-kits.md`, `docs/net-preamble-*.md`; `content/live/03-*.md`; `research/organization.md` §4, §8, §9, §12; `research/decisions.md` D8, D11, D13; `research/data/facts.yaml`; `design/REVIEW.md` (Ops Board → /members).
