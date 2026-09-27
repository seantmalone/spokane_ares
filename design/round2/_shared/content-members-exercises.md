# For Members: Exercises & events (`members/exercises.html`): draft copy

- **Job of the page:** everything a member needs to show up ready: what's next, what to do for each exercise, how to log and report afterwards, the rhythm of the year, and the exercises worth remembering.
- **Data:** `SPOKARES.events`, `SPOKARES.meetings`, `SPOKARES.nets`, `SPOKARES.winlink`, `SPOKARES.yearAtAGlance`, `SPOKARES.pastExercises`; lists via `SPOKARES.fn.upcoming()`. The text here is the no-JS fallback as of **Sat, Sep 26, 2026**.
- **Calendar of record:** the groups.io calendar (members). This page is kept from the same source until the calendar feed is wired in (decisions.md D9).
- **Never here:** the staff meeting, the Hospital Net, SHARES specifics, county channels, exercise plans or ICS-205s (those stay on groups.io).
- **Conventions:** `[verify]` = `data-verify="true"`. Members menu as on every `members/*.html` page.

---

## 0. Head and intro

- **`<title>`:** Exercises & events | Spokane County ARES-ACS
- **Meta description:** Upcoming exercises, training, meetings and public-service events for Spokane County ARES-ACS members, with what to do and how to report.

**Breadcrumb:** For Members › Exercises & events

**H1:** Exercises & events

**Lede:**
> Practice is most of what we do. Here's what's coming, what each exercise asks of you, and what to send in afterwards.

**Filter chips (progressive enhancement; each maps to an event `type`):** All · Exercises · Training & meetings · Nets · Public service · On-air events

**Add to calendar:** each dated item offers a small ".ics" link generated client-side (optional); the no-JS fallback is the plain date and time.

---

## 1. Next up (`#next-up`) **(data)**

Two large cards.

**Happening now**
> **Sat–Sun, Sep 26–27, 2026 · Spokane County ARES-ACS / WSDOT exercise**
> A joint exercise with the Washington State Department of Transportation during National Preparedness Month.
> **Bring:** your go-kit and a programmed handheld. **Log:** your activity on an ICS-214 and messages on an ICS-309. **Forms in the go-kit list:** ICS-213, ICS-213RR, and WSDOT Form 580-020 (Road and Bridge Assessment).
> Assignments and exercise details: groups.io. `[verify]` (scope and assignments are not public)

**Next Saturday**
> **Sat, Oct 3, 2026 · Simulated Emergency Test (SET)**
> ARRL's annual national exercise. See the [SET tasks](#set-2026).

---

## 2. Upcoming (`#upcoming`) **(data: `fn.upcoming({from:'2026-09-26', days: 260})`, filtered)**

A dated list grouped by month. Each row: date block · title · type tag · time · place · one line · link. Recurring nets appear in a collapsed "Also every week" line per month rather than as dozens of rows.

### September 2026
| Date | What | Type | Time / place | Notes |
|---|---|---|---|---|
| **Sat–Sun, Sep 26–27** | **ARES-ACS / WSDOT exercise** | Exercise | All weekend | Happening now |
| Tue, Sep 29 | Tuesday net: **simplex** (5th Tuesday) | Net | 2000 · simplex, then W7GBU | Net control NZ2S `[verify: simplex]` |

### October 2026
| Date | What | Type | Time / place | Notes |
|---|---|---|---|---|
| **Sat, Oct 3** | **Simulated Emergency Test (SET)** | Exercise | All day | [SET tasks](#set-2026) |
| Tue, Oct 6 | Tuesday net | Net | 2000 · W7GBU | Net control **open**: can you take it? |
| Sat, Oct 10 | Second Saturday Workshop | Training | 0900–1200 · SCEM | |
| Sat, Oct 10 | Winlink workshop | Training | 1230–1530 · SCEM | Open to everyone `[verify]` |
| Tue, Oct 13 | Tuesday net: **Winlink night** | Net | 2000 · W7GBU | Net control AE7RJ · Assignment: DYFI report (mark as exercise) |
| **Thu, Oct 15** | **Great ShakeOut: send a DYFI message** | Exercise | **1015** · from wherever you are | [Details](#shakeout-2026) |
| Thu, Oct 15 | Third Thursday training meeting | Training | Evening · SCEM | `[verify: start time]` |
| Sat, Oct 17 | SHARES training meeting | Training | 0900–1200 · SCEM | For SHARES participants `[verify]` |
| Tue, Oct 20 | ACS GMRS net | Net | 1930 · WRCY281 GMRS repeater | `[verify]` |
| Tue, Oct 20 | Tuesday net | Net | 2000 · W7GBU | Net control WA7LNC |
| **Sat, Oct 24** | **State COMMEX** | Exercise | All day | Washington's statewide communications exercise `[verify]` |
| Tue, Oct 27 | Tuesday net: **Winlink night** | Net | 2000 · W7GBU | Net control **open** · Assignment: welfare message |

### November 2026
| Date | What | Type | Time / place | Notes |
|---|---|---|---|---|
| Tue, Nov 10 | Winlink night | Net | 2000 · W7GBU | Assignment: Thanksgiving dinner location (ICS-213) |
| Sat, Nov 14 | Second Saturday Workshop + Winlink workshop | Training | 0900–1200; 1230–1530 · SCEM | |
| Tue, Nov 17 | ACS GMRS net | Net | 1930 | `[verify]` |
| Wed–Wed, Nov 18–25 | Washington operates as W1AW/7 (ARRL WAS-250) | On-air (optional) | Sign up for a shift | Section/state activity `[verify]` |
| Thu, Nov 19 | Third Thursday training meeting | Training | Evening · SCEM | `[verify: start time]` |
| Tue, Nov 24 | Winlink night | Net | 2000 · W7GBU | Assignment: ICS-213RR to Santa |

### December 2026
| Date | What | Type | Time / place | Notes |
|---|---|---|---|---|
| **Fri–Sat, Dec 4–5** | **SKYWARN Recognition Day** | On-air | NWS Spokane office, Airway Heights | The group's station there has run every year since 1999 `[verify]` |
| Tue, Dec 8 | Winlink night | Net | 2000 · W7GBU | Assignment: justification to Santa (ICS-213) |
| Sat, Dec 12 | Second Saturday Workshop + Winlink workshop | Training | 0900–1200; 1230–1530 · SCEM | |
| Tue, Dec 15 | ACS GMRS net | Net | 1930 | `[verify]` |
| Tue, Dec 22 | Winlink night | Net | 2000 · W7GBU | Assignment: New Year's Eve plans (ICS-213) |
| Tue, Dec 29 | Tuesday net: **simplex** (5th Tuesday) | Net | 2000 · simplex, then W7GBU | `[verify]` |

*December training meeting:* none is listed (there was no December meeting in 2019) `[verify]`.

### 2027
| Date | What | Type | Notes |
|---|---|---|---|
| **Sat–Sun, Jan 23–24** | **Winter Field Day** | On-air | The WinterFieldDay subgroup on groups.io coordinates it |
| **Sun, May 2** | **Bloomsday** | Public service | Volunteer through the AEC for public events, **NV2Z** |
| **Sun, Jun 13** | **Climb for the Cure** | Public service | Volunteer contact **NZ2S** `[verify]` |

**Also every week (collapsed line under each month):** Tuesday net at 2000 on W7GBU · 0730 Bunch weekday mornings `[verify]` · EWSEN Section nets on Mondays.

---

## 3. Simulated Emergency Test: October 3, 2026 (`#set-2026`)

**H2:** Simulated Emergency Test: Sat, Oct 3

> The SET is the ARRL's annual national exercise, held on the first weekend of October. The Eastern Washington Section Emergency Coordinator sets this year's scenario and asks every group to do the following. (This year's scenario, posted by the SEC, is lighthearted, but the skills are the same as for a real deployment.)

**Your tasks** (from the Section's 2026 SET request):
1. **Winlink** (radio preferred; Telnet acceptable):
   - Send a **Washington State Field Situation Report** to your EC and to the Section Emergency Coordinator (AG7QP).
   - Send a message through the **National Traffic System** to the Section Manager (KA7LJQ) and the Section Emergency Coordinator (AG7QP).
2. **Take pictures** of your station and operation to share.
3. **Make as many contacts as you can** using the rules of POTA and/or SOTA.
4. **Send an after-action report (AAR)** with a list of participants to the Section Emergency Coordinator.
5. Have fun.

**Links:** ARRL SET forms → `https://www.arrl.org/simulated-emergency-test` · Section SET instructions → `https://ag7qp.com/` · WA State Field Situation Report (in Winlink Express) → `documents.html#wa-field-situation-report`

**Local plans** (where to be, who is net control) are on groups.io. `[verify]`

Source: `interim-ag7qp/home.md`.

---

## 4. Great ShakeOut: October 15, 2026 (`#shakeout-2026`)

**H2:** Great ShakeOut: Thu, Oct 15, 10:15 AM

> At **10:15 AM**, send a USGS **"Did You Feel It?" (DYFI)** report by Winlink. It's the same report Standing Order 1b asks for after a real earthquake, so this is practice for the day it counts.

- Use the DYFI form in Winlink Express. Clearly mark it as an exercise. `[verify]` (the "mark as exercise" instruction is from the Oct 13 Winlink assignment)
- Two days earlier, **Tue, Oct 13**, the Winlink-night assignment is also a DYFI report: a good dry run.

**Links:** Great ShakeOut → `https://www.shakeout.org/` · USGS Did You Feel It? → `https://earthquake.usgs.gov/data/dyfi/` · Standing Order 1b → `https://ag7qp.com/ares-acs`

---

## 5. Winlink assignments (`#winlink-assignments`) **(data)**

Same table as `content-members.md` §6, repeated here because assignments are exercises too.

> Answer on an **ICS-213** unless another form is named. Send to **NV2Z** and **AG7QP** between **1700 and 2100** on the date shown. Radio is preferred; Telnet is acceptable.

| Date | Assignment | Form |
|---|---|---|
| Tue, Oct 13 | Submit a Did You Feel It report. Mark it as an exercise. | DYFI |
| Tue, Oct 27 | Submit a welfare message | Welfare Message / Quick Health & Welfare |
| Tue, Nov 10 | Where you expect to have Thanksgiving dinner | ICS-213 |
| Tue, Nov 24 | What you want Santa to bring you | ICS-213RR |
| Tue, Dec 8 | Give Santa a justification for your being good this year | ICS-213 |
| Tue, Dec 22 | Your New Year's Eve plans | ICS-213 |

---

## 6. The year at a glance (`#year-at-a-glance`) **(data: `SPOKARES.yearAtAGlance`)**

A 12-month strip or list. Recurring program, not fixed dates:

| When | What |
|---|---|
| **Every month** | Tuesday net (Winlink nights on the 2nd and 4th Tuesdays) · Second Saturday Workshop and Winlink workshop · Third Thursday training meeting · ACS GMRS net on the 3rd Tuesday `[verify]` |
| **Months with a 5th Tuesday** | Simplex net `[verify]` |
| **Months with a 5th Saturday** | EOC-to-EOC exercises have been held on 5th Saturdays (2004, 2009, 2013, 2018, 2019) `[verify]` |
| **January** | Winter Field Day |
| **May** | Bloomsday · Lilac Festival Armed Forces Torchlight Parade `[verify: month]` |
| **June** | ARRL Field Day `[verify]` |
| **September** | National Preparedness Month |
| **October** | Simulated Emergency Test (first weekend) · Great ShakeOut DYFI message · State COMMEX |
| **December** | SKYWARN Recognition Day station at NWS Spokane |

---

## 7. Before, during and after (`#after-action`)

**H2:** Before, during and after

**Before**
- Check the assignment and plan on groups.io; know your net control and your alternate (146.880 MHz if W7GBU is down).
- Charge batteries; check your go-kit against the kit list; program your handheld.
- Carry forms: ICS-213, ICS-213RR, ICS-214/214a, ICS-309, ARRL Radiogram → [Forms](documents.html#forms)
- Tip: fill out an ICS-213RR that describes your go-kit, and give it to whoever authorizes your deployment. `[verify]`

**During**
- Check in with net control on arrival and when you leave.
- Log every message on an **ICS-309** and your activity on an **ICS-214**.
- Keep transmissions short; leave gaps for priority traffic.
- Look after yourself and the people around you: rest, eat, drink water. "Don't hesitate to admit when things are getting to be too much."

**After**
- Send your logs and your part of the report as the exercise lead asks.
- Leaders hold an **action review within 72 hours** of the end of an event.
- For the SET, an **after-action report with a participant list** goes to the Section Emergency Coordinator.
- ACS members: note the date and get the sign-off for your task book's **yearly field operation**.

Sources: `content/live/20-*.md` (72-hour review); `interim-ag7qp/go-kits.md`, `acs-task-book.md`, `home.md`.

---

## 8. Public-service events (`#public-service`)

**H2:** Public-service events

> Race courses and parade routes are where most of us first work a busy net. They count as your yearly field operation.

| Event | When | Volunteer through |
|---|---|---|
| **Bloomsday** | Sun, May 2, 2027 | AEC, Public Events (NV2Z) |
| **Lilac Festival Armed Forces Torchlight Parade** | Date not yet posted | AEC, Public Events (NV2Z) |
| **Climb for the Cure** | Sun, Jun 13, 2027 | NZ2S `[verify]` |
| Fun runs and bike races | As requested | AEC, Public Events (NV2Z) |

**First event? Read these first:** Preparing for public-service communications → `documents.html#public-service-tips`

---

## 9. Past exercises and operations (`#past`) **(data: `SPOKARES.pastExercises`)**

**H2:** Past exercises worth knowing

> Real incidents and the exercises that prepared for them. Photos and after-action notes are welcome for any of these: send them to webmaster@spokares.org `[verify]`.

| When | What | Notes |
|---|---|---|
| Sep 2026 | ARES-ACS / WSDOT exercise | This weekend |
| 2025 | Field Day in Spokane Valley as W7GBU | `[verify]` |
| **Aug 2023** | **Gray and Oregon Road fires (real incident)** | The group provided a mobile network unit for the incident command post and the Disaster Assistance Center. `[verify]` |
| Oct 2022 | Rockford exercise | |
| Oct 2021 | Simulated Emergency Test | |
| Dec 2019 | "Hazard City" hospital deployment exercise | `[verify]` |
| Oct 2019 | Quarterly all-members exercise | |
| Jul 2019 | Kootenai County exercise | |
| **Mar 2019** | **EOC-to-EOC 5th Saturday exercise** | More than 20 operators at sites across the county; traffic with Stevens County; Winlink over HF and VHF packet; WA ICS-213RR resource requests to the State EOC at Camp Murray. |
| Jun 2018 | Field Day | |
| Mar 2018 | 5th Saturday multi-county exercise | Stations at outlying city halls with portable HF, VHF and UHF, Winlink, and the secondary repeater. |
| Dec 2017 | SKYWARN Recognition Day station at NWS Spokane | New members operated HF. |
| Aug 2013 | EOC-to-EOC exercise | |
| 2012, 2013 | Field Day with the Inland Empire VHF Radio Amateurs on Mt. Spokane (as WR7VHF) | |
| Oct 2004 | Simulated Emergency Test | |

(Keep entries generic: no exercise frequencies, no equipment or hospital-station locations.)

---

## Sources for this page

`interim-ag7qp/spokane-county-ares-acs.md` (events, rota, Winlink assignments), `home.md` (SET tasks, upcoming events), `go-kits.md`, `acs-task-book.md`, `eastern-washington-section.md`, `preparing-for-public-service-communications.md`; `content/live/20-*.md`; `research/history.md` §2, §5; `research/organization.md` §3, §9; `research/data/calendar-events.json`; `sources/government/spokane-COAD-minutes-2023-09-05.txt` (via `history.md`).
