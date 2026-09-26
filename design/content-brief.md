# Content brief: spokares.org redesign mockups

Built from the crawl of the current Joomla site (https://www.spokares.org/) plus the Eastern Washington Section Emergency Coordinator's site (AG7QP), which is where the group's *current* (2026) information lives. Every fact below cites its source. Designers: use this content as written. Anything marked **VERIFY** must be confirmed with the group before it is published on a live site. It is fine to show it in a mockup.

> **Revised 2026-09-25 (research critic pass).** §4 (net check-in order), §5 (join path, now taken from `research/organization.md` §10 and the ACS Task Book v2024-08-09), §10 (navigation: no login, no gated items, no public status strip) and §11 (new "never publish" rule for SCEM FOUO material) were rewritten, and the source key and context notes below were corrected. The corrections are listed in the errata table of `research/README.md`. The page list and slugs are now in `research/decisions.md` D15.

Source key
- **HOME** is `research/raw/_home.html`. **A-n** is `research/raw/by-id/article-n.html` (the Markdown copies are `research/content/live/`). `research/raw/` is git-ignored because it holds personal e-mails; cite it, don't copy from it.
- **CAL** is `research/data/calendar-events.json`, 232 events dated 2017-08 to 2026-09.
- **AG7QP-x** is `research/external/ag7qp/x.html` (raw HTML, personal contacts redacted) or `research/content/interim-ag7qp/x.md` (Markdown). **AG7QP-acs** is the ACS Task Book page, `acs-task-book`. These are the Section Emergency Coordinator's pages, current as of Sept 2026. (Earlier drafts pointed at `research/sources/government/ag7qp_x.html`, which were unredacted copies; they have since been redacted and git-ignored.)

Context worth knowing
- The current site runs Joomla 3 with a 2017-era template. Most articles were published 2017–2018 and never revised.
- The AG7QP home page says **"The spokares.org website was hacked"**. It now hosts a *temporary* Spokane County ARES-ACS page, which holds the current net-control roster, the calendar and the downloads (AG7QP-home, AG7QP-spokane-county-ares-acs).
- `research/site-functionality-analysis.md` exists (it was written shortly after this brief). Its §2b IA is reconciled with the design review in `research/decisions.md` D15.
- **Superseded:** an earlier version of this note told mockups to assume a gated members area and an events plugin, from the generic §5 of `research/hosting/verpex-enhance.md`. The current recommendation is **no site accounts** (members use groups.io, `research/decisions.md` D8), **one calendar source** (the groups.io calendar, D9) and a **plain public document library** with no gate (D10).

---

## 1. Names, acronyms, and what they mean

| Term | Expansion | Evidence |
|---|---|---|
| **ARES** | Amateur Radio Emergency Service | A-2, A-4, logo |
| **RACES** | Radio Amateur Civil Emergency Service | A-2, site title "Spokane County ARES / RACES" |
| **ACS** | Auxiliary Communications | The ACS seal reads "SPOKANE AUXILIARY COMMUNICATIONS". AG7QP writes "Auxiliary Communication System" and the MOU (A-68) writes "Auxiliary communications System". |
| **AUXCOM** | FEMA's term for Auxiliary Communications within the ICS Communications Unit | AG7QP-new-members-orientation |
| **ARRL** | American Radio Relay League, the national association for amateur radio, which runs ARES | A-4 |
| **DEM / SCEM** | Spokane County (Department of) Emergency Management | The county seal reads "Spokane County Emergency Management". Older pages say "Greater Spokane Emergency Management" or "GSEM". |
| **W7GBU** | The group's call sign and repeater ID, a memorial call for Ira "Dean" Bula | A-5 |
| **IEVHFRA** | Inland Empire VHF Radio Amateurs (the "VHF Club") | A-68, A-42 |
| **NWS / SKYWARN** | National Weather Service Spokane office; SKYWARN is NWS's spotter program | A-37 |

**Brand name to use in mockups: "Spokane County ARES-ACS".** This is what the current seal, the page headings and AG7QP use. "ARES / RACES" is the legacy name. It still appears in the site title and footer, but it should read as history.

> **Naming caution (VERIFY):** the task brief calls ACS "Auxiliary Communications **Service**". That exact wording appears nowhere in the sources. The safe choices are the seal's wording, "Auxiliary Communications", or plain "ACS". Do not invent a tagline expansion.

**Plain-English explanation (ready to use):**
> Spokane County ARES-ACS is one team of licensed amateur radio ("ham") operators who volunteer to provide backup communications when normal systems are overloaded or down. The team has two sides.
> - **ARES** (Amateur Radio Emergency Service) is a national ARRL program open to any licensed ham. ARES members can support community events, the National Weather Service and the Red Cross without an emergency being declared.
> - **ACS** (Auxiliary Communications) is Spokane County Emergency Management's own volunteer communications unit. It carries on the county's RACES program (Radio Amateur Civil Emergency Service, the FCC provision that lets hams operate for government during emergencies). ACS members have passed a county background check, so they can be activated by the county and can work inside emergency operations centers, incident command posts, hospitals and shelters where sensitive information is handled.
>
> You can join ARES only, or both. The two sides train and operate together, and the group's own welcome page says: "Even [if] you don't or can't qualify for ACS, our ARES group welcomes you."

Sources: A-2 §I.D, A-26, AG7QP-new-members-orientation ("ACS members have undergone a background check and can therefore operate in Emergency Operation Centers (EOC), Incident Command Posts (ICP), hospitals and shelters").

---

## 2. Mission and value proposition

### Existing lines (quote verbatim)
- **Mission (A-2, official ARES/RACES Plan):** "The mission of Spokane County ARES/RACES is to support and enhance the telecommunications needs of its served agencies with the versatile talents and flexible resources of trained and competent amateur radio operators, thereby serving the public interest in times of emergency or special need."
- **About (A-4 / HOME):** "Our group concentrates on emergency and civil communications to support the safety of Spokane County residents."
- **Why it matters (A-2 §III.B):** "volunteer radio communicators may be called upon to supplement existing systems when it is anticipated that those systems may become overloaded or disabled, or when it is necessary to supply communications services where no established links exist."
- **What radio can do (AG7QP-ares-acs, Standing Order 1b):** "Amateur radio can be particularly helpful because it can function even if other modes of communication cannot."
- **Welcome (A-41):** "We're excited you've joined us to make the Spokane area better able to deal with emergencies and disasters of all types."
- **Legacy (A-5, on Dean Bula W7GBU):** "He was a person who made a difference in Spokane by making others feel like they could make a difference themselves."
- **Relationships (AG7QP orientation):** "An emergency is a poor time to be handing out business cards."
- **Inclusive (A-26):** "Even [if] you don't or can't qualify for ACS, our ARES group welcomes you, and you can still volunteer for many events."

### PROPOSED headline options (new copy, not on the current site)
1. **PROPOSAL:** "When other systems go down, Spokane County still has a voice."
2. **PROPOSAL:** "From Bloomsday to blackouts, we keep Spokane County connected."
3. **PROPOSAL:** "Trained volunteers. Backup communications. Ready when it counts."
4. **PROPOSAL:** "Make a difference with a radio in your hand." Pair it with the Dean Bula quote above as the subline.
5. **PROPOSAL:** "Every Tuesday night we practice. The day it's real, we're ready."

PROPOSED supporting subline: "Licensed amateur radio volunteers supporting Spokane County Emergency Management, the National Weather Service, local hospitals and community events."

PROPOSED CTA labels:
- Primary: "Join the team" or "Start volunteering"
- Secondary: "Not licensed yet? Get on the air", "Listen to Tuesday's net", "See how it works"

---

## 3. What members do (all evidenced)

- **Emergency and disaster communications for Spokane County Emergency Management.** The county activates the group and obtains a state mission number, so members are covered under RCW 38.52 (A-2 §VI.A). Members staff the county **Radio Communications room and comm trailer** (A-3, AG7QP orientation). The county also has **Mobile Network Units (MNU)**, hospital stations and equipment caches (AG7QP orientation).
- **Hospital communications.** A Hospital Coordinator works with Greater Spokane area hospitals to keep backup radio and computer equipment in place and working (A-3). The team runs a Hospital Net after the regular net (AG7QP). **Do not publish its channel, frequency or schedule** (§11, SCEM FOUO rule). HIPAA training is required for the hospital team (A-41).
- **Public service events** (primary or backup comms): **Bloomsday**, the **Lilac Torchlight Parade** (also written "Armed Forces Torchlight Parade"), fun runs and bicycle races (A-3, A-4, A-26). The calendar also shows **Valleyfest** (2019) and a **Cherry Picker's Trot – Green Bluff** meeting (2019) (CAL).
- **SKYWARN weather spotting** for NWS Spokane. Many members are trained spotters. Spotting is encouraged but not required (A-37). Nets are activated in severe weather (A-4).
- **Digital messaging / Winlink.** The group runs the **W7GBU-10 Winlink gateway** (A-3). Members complete biweekly **Winlink assignments** on ICS-213 forms, sent to NV2Z and AG7QP (AG7QP-spokane-county-ares-acs). The site also covers packet radio, TNCs/modems and software (HOME nav).
- **Weekly net practice.** Members rotate as Net Control Station, and the Net Manager coaches new net controls (A-3). See §4.
- **Exercises and drills.** Examples include:
  - the annual **Simulated Emergency Test (SET)**, held Oct 3, 2026
  - an **ARES-ACS/WSDOT exercise**, Sept 26–27, 2026
  - the **Great ShakeOut** "Did You Feel It" message, Oct 15, 2026
  - a **State COMMEX**, Oct 24, 2026
  - a **SHARES** training meeting, Oct 17, 2026 (all AG7QP)

  Historic calendar entries include EOC-to-EOC 5th Saturday exercises, a Kootenai County exercise, a Hazard City deployment, a Rockford exercise and Field Day (CAL 2018–2022).
- **Training and workshops.** These cover:
  - **Second Saturday Workshops** (e.g. hands-on TNC setups, emergency power systems, mobile installs and cross-band repeating)
  - **Third Thursday training meetings** (e.g. shelter training)
  - **helicopter safety** with the Sheriff's Office (CAL, A-41)
- **Alert levels / status codes** (A-20). Show these as a 4-step visual that **explains** the levels. Never show a live or current level ("Status: Condition Green") on the public site: a stale badge could contradict county messaging during a real incident (`design/REVIEW.md`, "Public status indicators are a liability"). Real content:
  - **Condition Green (Inactive):** normal conditions; do a monthly battery check.
  - **Condition Yellow (Alert):** "Something might happen". Check go-kits and monitor frequencies.
  - **Condition Orange (Stand By!):** "Something has happened". Load the car and monitor NWS WXL86 (162.400) and W7GBU (147.300).
  - **Condition Red (Deploy!):** activation text sent, full nets start, check in with net control on W7GBU (147.300).
  - After the event: members are released, the level returns to Green, and an action review is held within 72 hours.

---

## 4. Weekly net, other nets, and meetings

### The weekly net (high confidence)
- **Every Tuesday, 8:00 PM local (2000 hrs)**, on the **W7GBU repeater, 147.300 MHz, +600 kHz offset, 100 Hz tone**.
- Evidence:
  - the welcome letter (A-41)
  - the net preamble, updated Apr 4, 2018 (A-8)
  - the Net Manager duties (A-3)
  - the ARES/RACES Plan (A-2)
  - the homepage widget "NZ2S - Simplex, Tue. 29 Sep, 2026 20:00" (HOME)
  - AG7QP's 2026 net list: "ARES, Spokane County … 147.3000 MHz +0.600 MHz 100 Hz Tuesday 8:00 PM"
  - the calendar: all 109 net-control entries fall on Tuesdays, and 103 of them are at 20:00 (CAL)
- It is a directed net that doubles as weekly emergency-operations training. Check-in order in the **current 2026-03-04 weekly script** (`research/content/interim-ag7qp/docs/net-preamble-2026-03-04.md`):
  1. officials: the Spokane County EC, Spokane County Emergency Management personnel, the AECs, ARRL District and Section officials, then officials from surrounding counties
  2. members. The script says "ordered alphabetically in groups by call sign suffix" but no longer lists the groups; net control works from the member roster ("Use the roster and confirm each call sign")
  3. visitors ("call sign, name and location")
  4. simplex, no-tone and cross-band stations
  5. listed traffic, then late or missed check-ins "Alpha through Zulu"

  The 5th-Tuesday **simplex** script calls members in five suffix groups (Alpha–Delta, Echo–India, Juliet–November, Oscar–Sierra, Tango–Zulu) and asks each station for a read-back of stations heard. The 3rd-Tuesday **GMRS** net calls members by county volunteer-number ranges (`docs/net-preamble-gmrs-2025-08-19.md`). The 2018 three-group order (A–F, G–M, N–Z; A-8) is **superseded**; don't show it. For a public page, "officials, members, then visitors" is enough.

  **Visitors are explicitly invited to check in** (A-8, and every 2026 script). This is a great "try it" CTA for curious hams.
- Net control rotates among members. The current roster on AG7QP is: Sep 22 KE7RAP, Sep 29 NZ2S, Oct 6 Open, Oct 13 AE7RJ, Oct 20 WA7LNC, Oct 27 Open. The calendar agrees for September 2026: KK7SAB, AE7RJ, WA7LNC, KE7RAP, NZ2S. Mockups may show a "Net control this week: NZ2S" widget built from this list.
- Some weeks run as a **simplex net**, e.g. "NZ2S - Simplex", Sept 29, 2026. Separate simplex and **GMRS** net scripts exist (AG7QP). **VERIFY** the simplex frequency before showing it. The 2017 plan lists 147.48 MHz simplex, which is old.
- Members are expected to check in **at least once a month** (AG7QP; ACS task book: "R-Monthly, E-Weekly").
- **Data anomaly:** the Sep 8, 15 and 22, 2026 calendar entries show 22:00. Every other source says 20:00. Treat these as data-entry errors and never display 10 PM.

### Other recurring nets (from AG7QP's 2026 list; VERIFY before publishing)
- **"0730 Bunch"**: Monday–Friday, 7:30 AM, on the same 147.300 repeater. This is the informal morning round table that Dean Bula (W7GBU) started as "The 7:30 Bunch" (A-5). Good history-to-present story hook.
- **Spokane County ACS GMRS net**: 3rd Tuesday, 7:30 PM, GMRS 7 (462.700 MHz, 141.3 Hz).
- **Washington State Emergency Net (WSEN)** on HF, 3.985 MHz LSB: Monday evening and Saturday 9 AM (A-2, AG7QP). The site links to it (HOME nav).

### Meetings and in-person events: Spokane County DEM building, **1121 W. Gardner Ave, Spokane, WA 99260**
The address comes from A-41, CAL, AG7QP orientation and DEM_map.png. The building is next to the Spokane County Courthouse, at N Jefferson St & W Gardner Ave.

| Event | Pattern | Time | Source / confidence |
|---|---|---|---|
| Monthly training meeting | **Third Thursday** of each month | **VERIFY:** the old site says 7–9 PM (A-27, A-41 "1900hrs"; CAL 2018–2020 at 19:00). The 2026 AG7QP calendar says **6–8 PM** ("Third Thursday Training Meeting, 1800-2000 at DEM", Oct 15, 2026). | Day is certain; time conflicts. Show "Third Thursdays" and confirm the hour. |
| Second Saturday Workshop | 2nd Saturday | 9:00 AM–12:00 PM | 34 calendar entries 2017–2020; AG7QP Oct 10, 2026. High confidence. |
| New Member Orientation | 4th Tuesday ("usually") | 6:00 PM, followed by a comm room tour and the 8 PM net | AG7QP-new-members-orientation ("usually"). **VERIFY**: the Sep–Oct 2026 AG7QP event list has no Oct 27 session; until confirmed, say "held regularly; contact us for the next date". |
| Staff meeting | 1st Tuesday (e.g. Oct 6, 2026, 5:30 PM) | n/a | Internal only; not for the public site. **Conflict:** AG7QP-home and AG7QP-contact list it under meetings "Open to the public"; ask the EC (`research/decisions.md` D13 #16). |

- The old location, **Spokane Fire Training Center, 1618 N Rebecca**, appears on 2017–2024 calendar entries. It is out of date, so do not use it.
- December has no meeting (CAL 2019: "NO MEETING for December"). **VERIFY** that this is still the rule.

---

## 5. How to join (steps and requirements)

**Rewritten 2026-09-25** from `research/organization.md` §10 and the current ACS Task Book (AG7QP-acs = `research/content/interim-ag7qp/acs-task-book.md`, version 2024-08-09). The earlier text mixed in 2018 rules from the Welcome Letter (A-41). Present it as a **3-step path with an optional 4th step**. Everything here is **VERIFY with leadership** before a live site.

**Step 0: Get licensed.** You need an FCC **Technician class license or higher** (A-2 §IV.A.1). If you are not licensed yet, see §6.

**Step 1: Join ARES (open to any licensed ham).**
- Fill out the **Spokane County ARES Membership Application**, version 2024-11-07. It includes an SCEM Emergency Communications Non-Disclosure Agreement (`research/content/interim-ag7qp/docs/ares-membership-application-2024-11-07.md`). The current copy is linked from AG7QP-spokane-county-ares-acs; the old spokares.org download (A-26) returns HTTP 500. Two versions are linked there (2024-11-07 and 2024-09-15); use 2024-11-07 until AG7QP confirms.
- **Where it goes:** the form names a person's personal e-mail. **Do not print it.** Show "bring it to a meeting, or send it to [role address]" and leave the address as a placeholder until the EC names the recipient (`research/decisions.md` D11, D13 #2).
- Get the **ARRL ARES Task Book** (a link to arrl.org's ARES pages; A-26 links only the generic page).
- **ARRL membership:** not required to join ARES (A-2). The **July 2025 ARRL ARES Plan requires it for the Intermediate level and above and for EC/AEC appointments** (`research/orgs/relationships.md` §4). Say both.

**Step 2: Get connected.**
- **Apply to join the groups.io group**, https://spokaneares-acs.groups.io/g/main. **A moderator approves each subscription**; the message archive, Files and Wiki are members-only (anonymous check of the group page, 2026-09-25; `research/organization.md` §12). Write "Apply to join our groups.io list; a moderator approves new members", not "subscribe".
- Check into the Tuesday net, and attend a Third Thursday meeting or a Second Saturday workshop.
- Do not link the groups.io calendar as a public calendar yet: anonymous visitors get the calendar page, but no public event feed has been confirmed (`research/decisions.md` D9).

**Step 3: Qualify for ACS (optional; lets the county call on you).**
1. Submit the **Spokane County Volunteer Emergency Worker Application** (FormCenter 276): https://www.spokanecounty.gov/FormCenter/Emergency-Management-31/Volunteer-Emergency-Worker-Application-276. The old spokanecounty.org link (A-26, HOME nav) now redirects to .gov; use the .gov URL.
2. Pass the **Sheriff's Office background check** (A-26, A-41; AG7QP-new-members-orientation).
3. The county assigns a **volunteer number** (A-41).
4. Attend **New Member Orientation**, "usually held on the 4th Tuesday of the month" (AG7QP-acs). See the VERIFY note in §4.
5. You are a **New Member** "for a period normally not to exceed one year" and work through the New Member requirements below (AG7QP-acs, `acs-task-book.md` line 21).
6. When all New Member requirements are complete you advance to **Radio Operator (RADO)** and "will receive an ACS identification card" (AG7QP-acs, `acs-task-book.md` line 71). Items are signed off by the EC or any AEC; the level is signed off by the EC.

> **Superseded, do not use:** the 2018 Welcome Letter (A-41) rule that the county ID card follows IS-100 + IS-700, the background check and a **six-month activity period**. The 2017 Plan's (A-2) minimums of 10 Tuesday nets, 2 meetings, 1 public-service event and 1 exercise a year are also superseded by the Task Book.

**New Member requirements: the ACS Task Book (AG7QP-acs, version 2024-08-09). This is the current list.**

Required once:
- FEMA **IS-100** (Intro to ICS)
- FEMA **IS-200** (Basic ICS for Initial Response)
- FEMA **IS-700** (Intro to NIMS)
- FEMA **IS-800** (National Response Framework)

  These are free online courses and need a FEMA Student ID from https://cdp.dhs.gov/femasid. Current versions are IS-100.c, IS-200.c, IS-700.b and IS-800.d (`research/organization.md` §11).
- **ARRL Basic EmComm** course (online)
- **HIPAA** training (covered at orientation)
- Register with **ALERT Spokane**, the county's public alert system. It moved to the **Regroup** platform in April 2026 (https://www.spokanecounty.gov/3007/Alert-Spokane, checked 2026-09-25; `research/orgs/relationships.md` Contradiction 12). The Task Book and orientation page still say "Code Red"; do not repeat that.
- Understand the organization, which you do by attending orientation

Required on an ongoing schedule:
- **Monthly:** check into the net (weekly is encouraged).
- **Quarterly:** attend a Third Thursday meeting or Second Saturday workshop (monthly is encouraged).
- **Yearly:**
  - Keep a **personal preparedness kit** (24-hour minimum, 72-hour preferred).
  - Keep a **radio deployment kit** (2 m / 70 cm capability for 24–72 hours; HF and Winlink encouraged).
  - Program the repeater and simplex channels into your HT.
  - Take part in **one field operation per year** (an activation, exercise or public service event).
- **Ongoing:** keep an **ICS-309** comm log during operations.

Encouraged:
- **SKYWARN** spotter training, every two years.

Optional (A-41, AG7QP orientation):
- Defensive driving (required only to drive county vehicles)
- Bloodborne pathogens
- Helicopter safety (Sheriff's Office)
- Adult First Aid/CPR/AED (through the county; required every two years at RADO)
- Red Cross Disaster Services and Shelter Fundamentals

> Exemptions "may be granted by ACS leaders on a case-by-case basis" (AG7QP-acs). Higher levels (RADO, S-RADO, Leadership) are summarised in `research/organization.md` §10; show them as "what comes next", not as entry requirements.

**Equipment:** members provide their own gear, and there are "no specific equipment requirements" (A-2). Station gear should be able to run for "at least 72 hours" (A-21). Members need transportation to events (A-2).

---

## 6. Training path for people who are not licensed yet

1. **Learn about it.** Listen to the Tuesday 8 PM net on 147.300 MHz, or come to a Third Thursday meeting or a Second Saturday workshop. Listening needs no license; the site does not say this explicitly, so it is a PROPOSAL framing.
2. **Take a license class.** Classes run "periodically through the year". They are typically led by **Jack Tiley, AD7FO**, with volunteer examiners, and are sponsored by **Spokane County Emergency Management, the VHF Club and local ARES-RACES**. Extra-class sessions are small (6–12 people) and run over two consecutive Saturdays, with testing after day 2 (A-42). The contact is the site contact form. **VERIFY** that classes are still offered this way.
3. **Take the exam.** These are local options listed on the site (A-11). **VERIFY all of them before publishing.**
   - **Mary Wiese, AA7RT**, is the primary tester for many local sessions. Tests usually follow the training classes.
   - **Washington Digital Radio Enthusiasts (WA7DRE)**, with the Laurel VEC, test generally on the **third Wednesday, 6 PM, at Action Recycling, 1027 E Marietta Ave**. Volunteers charge no fee, but the **FCC charges a $35 application fee**.
   - **KARS (Kootenai Amateur Radio Society)** tests on the 2nd Monday (not in December), 5:30 PM, at Kootenai County OEM, 1662 W Wyoming Ave, Hayden, ID, for $15.
   - **Online testing** is available through GLAARG on HamStudy (hamstudy.org/sessions/glaarg).
4. **Join ARES, then ACS.** Follow §5.
5. **Optional first step without a license: SKYWARN.** NWS spotter training is offered "either in person at a public session, or via an on-line course" (A-37): https://www.weather.gov/otx/Current_Spotter_Training

---

## 7. Partners and served agencies named in the sources

| Partner | Relationship | Source |
|---|---|---|
| **Spokane County Emergency Management** (DEM / SCEM; formerly "Greater Spokane Emergency Management") | Primary served agency; activates ACS; runs the background check and ID. The Sheriff is Director in the plan. | A-2, A-4, A-26, HOME logo |
| **ARRL** (American Radio Relay League) | The group is ARRL-affiliated; ARES is an ARRL program | A-4 |
| **National Weather Service, Spokane** | SKYWARN spotting and severe-weather nets | A-4, A-37 |
| **Greater Spokane area hospitals** | Backup comms; Hospital Coordinator; Hospital Net | A-3, AG7QP |
| **American Red Cross** | Service under the ARRL–Red Cross MOU; shelter training | A-2 §VI.A.4, A-41 |
| **Inland Empire VHF Radio Amateurs (IEVHFRA)** | Formal MOU (Rev. 5, May 2023): backup repeater 146.88, packet/Winlink nodes | A-68 |
| **Spokane County Sheriff's Office** | Helicopter safety training; volunteer ID | A-41, A-2 |
| **Washington State Emergency Management** / WSEN | State nets; mission numbers | A-2, HOME nav |
| **FEMA** | ICS/NIMS training; RACES framework | A-2, A-41 |
| **WSDOT** | Joint exercise, Sept 2026 | AG7QP |
| Event organizers: **Bloomsday**, **Lilac Torchlight Parade**, Valleyfest | Public-service comms | A-4, CAL |

Also named, but not served agencies:
- **Kootenai County** (joint exercise)
- **Stevens County ARES**
- **Washington Digital Radio Enthusiasts**, **KARS** and **Laurel VEC** (license testing)

Do **not** show partner logos other than the county EM seal and the ARRL ARES emblem, and use even those only with permission (see §11).

---

## 8. Leadership roles (VERIFY before publishing)

These come from A-3 (published 2017 and apparently updated since), A-37, A-41, A-42 and AG7QP (2026). Roles and call signs only. Titles on the source page include personal names, which should be confirmed before use.

| Role | Call sign | Notes |
|---|---|---|
| ARRL Section Manager (Eastern Washington) | KA7LJQ | Confirmed current by AG7QP, 2026 |
| ARRL Assistant Section Manager | AA7RT | Also SKYWARN primary representative and license test coordinator |
| ARRL Section Emergency Coordinator | AG7QP | Confirmed current; runs the temporary ARES-ACS page; also listed as Net Manager |
| County Emergency Coordinator & RACES Officer (and website admin) | W7TSC | From A-3/A-41/A-68 (2023). **VERIFY**: AG7QP's temp page names AG7QP as the contact |
| AEC – Public Events, W7GBU-10 Winlink Gateway manager & Winlink Coordinator | NV2Z | Confirmed active in 2026 (Winlink assignments) |
| AEC – Hospital Coordinator | K7MHG | |
| AEC – Logistics & Comms (also "AEC for training" in A-41) | K7DSR | |
| AEC – Net Manager | AG7QP | |
| Public Information Officer | *Open position* | "Open Position" on A-3; could be a recruiting hook |
| SKYWARN assistant | W7UWC | A-37 |
| Training certificate recipient | WA7AQH | A-41 (2018) |
| License class instructor | AD7FO | A-42 |

Mockup guidance: show a leadership grid of **role + call sign**. Use placeholder avatars, or the seal, instead of photos. Do not match people in the photos to call signs.

---

## 9. Key resources for current members

All of these exist on the current site or on AG7QP.

- **Net scripts:**
  - Net Preamble & Script for the repeater net (A-8; the current 2026 .docx is on AG7QP)
  - Simplex net script
  - GMRS net script
  - ~~Net roster (PDF, Aug 2026)~~ **Not a public resource.** It is a member list (README rule 4); it belongs in groups.io Files (`research/decisions.md` D14)
- **Forms:**
  - **ARRL Radiogram** form
  - **FEMA ICS Forms**, of which the minimum go-kit set is ICS-213 (message), ICS-213RR (resource request), ICS-214/214a (logs) and ICS-309 (comm log) (HOME Forms page, AG7QP)
  - Spokane County ARES registration form
  - County Volunteer Emergency Worker Application
- **Operations references:**
  - **Alert Levels / Status Codes** (A-20)
  - **Incident Command System** orientation text (A-10)
  - **Field Operations Guides**: AUXFOG and NIFOG from CISA SAFECOM, plus the **Spokane County ACS FOG** on Groups.io (members-only; link to groups.io, never republish its content: §11 FOUO rule)
  - **Eastern WA Standing Order 1b**: what to do if something happens and you have no instructions (AG7QP, Jan 2026)
  - **WSEN** link
  - Traffic Handling Tools (K7BFL)
- **Training:**
  - FEMA IS course links (IS-100, 200, 700, 800)
  - ARRL ARES Task Book
  - **ACS Task Book** (2024-08-09 .docx)
  - SKYWARN training links
  - Mentor program: Traffic Handler PDF + MP3 (A-25)
  - RATPAC videos
  - Presentations
- **Digital messaging:**
  - Winlink info (K7BFL)
  - Digital TNCs & modems
  - Software
  - Cables & drivers
  - Packet radio resources
  - Winlink assignment schedule
- **Readiness:**
  - Equipment lists: a Five-Hour Kit, an Overnight Kit, and a station equipment table (72-hour service) (A-21)
  - Go-kit guidance
  - ALERT Spokane registration (Regroup platform since April 2026)
- **Culture:**
  - The Radio Amateur Code (A-6)
  - Repeater Etiquette (A-7)
  - Principles of a Net Control Station (A-9)
  - W7GBU history (A-5)
- **Other:**
  - RFI resources (Mel Ming N7GCO)
  - Ham radio links
  - Groups.io calendar
  - ~~Member login~~ (dropped: no site accounts, `research/decisions.md` D8; members use groups.io)

---

## 10. PROPOSED navigation for the new site

**Revised 2026-09-25.** The page list, slugs, owners and review dates now live in `research/decisions.md` **D15**, which reconciles this section with `research/site-functionality-analysis.md` §2b and `design/REVIEW.md`. The old redirect targets in `research/redirects.csv` use the same slugs. Struck from the first version of this section: the **Login**, "**Members (some items gated)**", the net **roster** under Members, the "**Status: CONDITION GREEN**" strip and the **Terms** page (no site accounts, D8; no public status badge, REVIEW; a short disclaimer replaces Terms).

Keep the top level to five items plus Contact and one persistent CTA. Each audience needs one obvious door:

| Audience | Their door |
|---|---|
| Curious licensed hams | "Join" and "Nets & meetings" |
| Unlicensed people | "Get licensed" inside Join |
| Members | "Members", a link hub into groups.io |
| Agency partners | "About" → "Who we serve", and "Contact the emergency coordinator" |

```
[Seal] Spokane County ARES-ACS    About · Nets & meetings · Join · Library · Members    Contact    [Join the team]

Utility line (top or footer):
  Tuesday Net · 8:00 PM · W7GBU 147.300 (+) PL 100 · Net control: {from the calendar feed}
  (no status badge of any kind)

About                      /about/
  - Who we are (ARES vs ACS explained)
  - What we do (activities; alert levels explained)      /about/what-we-do/
  - How it works (the message path)                      /about/how-it-works/
  - Leadership                                           /about/leadership/
  - Who we serve and agreements (IEVHFRA MOU)            /about/partners/
  - The W7GBU story                                      /about/w7gbu/
  - History and archive                                  /about/history/
Nets & meetings            /nets/
  - Weekly net, Winlink nights, 5th-Tuesday simplex (no frequency until verified)
  - Meetings and where we meet (text address)
  - Upcoming (from the calendar feed) and a link to the groups.io calendar
  - Net control and preamble                             /nets/net-control/
  - Repeaters and etiquette                              /nets/repeaters/
Join (the conversion funnel)   /join/
  - Your path: 0 Get licensed → 1 Join ARES → 2 Get connected → 3 Qualify for ACS
  - Requirements and training (task book checklist)      /join/requirements/
  - Get licensed (classes and exam sessions)             /join/get-licensed/
  - New Member Orientation                               /join/orientation/
  - FAQ                                                  /join/#faq
Library                    /library/   (public, no gate)
  - Forms (links to FEMA ICS forms, ARRL Radiogram), net scripts, presentations, plans, membership documents
  - Go-kits and equipment                                /library/go-kits/
  - Digital, Winlink and RFI resources                   /library/resources/
Members                    /members/   (link hub; no login; nothing sensitive on the page)
  - groups.io: Files (task books, ACSFOG, roster), calendar, Winlink assignments, subgroups
Contact                    /contact/   (role addresses, agency door, meeting place)
Footer
  - County EM seal (with permission)
  - ARRL ARES affiliation
  - Meeting address with map
  - Groups.io link
  - Privacy (/privacy/) and a 3-line disclaimer
```

Homepage story order (PROPOSAL):
1. Hero with headline and two CTAs ("Join the team" and "Not licensed yet?").
2. The "Why it matters" line from the plan.
3. What we do, as 4–6 cards.
4. What the alert levels mean (explanation only; never the current level).
5. Your path to joining, as 3–4 steps.
6. Next events: Tuesday net, Third Thursday, Second Saturday, SET.
7. Partners.
8. The W7GBU legacy quote.
9. Members quick links.

---

## 11. Facts NOT to invent (hard rules for mockups)

- **No member counts, founding year, volunteer-hour totals, deployment counts or "years of service" numbers.** None appear in any source. Leave these out, or use a clearly labelled placeholder such as "[## members]".
- **No testimonials or quotes attributed to named people**, except the verbatim lines in §2. Do not invent member stories.
- **No frequencies other than the ones evidenced here:**
  - 147.300 MHz, +600 kHz, 100 Hz (W7GBU)
  - 146.88 MHz (IEVHFRA backup)
  - 162.400 MHz (NWS WXL86)
  - 3.985 MHz (WSEN)
  - GMRS 7, 462.700 MHz (VERIFY)

  Do not show a simplex frequency until it is verified.
- **Never show the net at 10 PM.** Those calendar entries are errors.
- **Do not state a meeting start time as fact.** Third Thursday is certain. 6 PM versus 7 PM is unresolved.
- **Do not use "Auxiliary Communications Service"** as the ACS expansion unless leadership confirms it.
- **Do not name people in photos or caption them with call signs.** The file names "Don-on-the-air" and "KD7GHZ" are unverified. One photo shows a readable name badge, which should be cropped or blurred on a live site.
- **Do not claim** any of the following:
  - 24/7 availability
  - response times
  - certifications the group itself holds
  - official endorsements
  - "first responder" status. AG7QP says explicitly: "Nothing in this order suggests that anyone becomes first responders … ARES is an auxiliary support function."
- **No dues or fees for membership.** None are mentioned. License exam fees belong to the FCC and the VEs.
- **No partner logos** except the county EM seal and the ARRL ARES emblem, and those only as affiliation marks with permission. The ARRL mark is ARRL's trademark, and the county seal belongs to the county.
- **Do not mention the website hack** on the public site.
- **Do not publish** phone numbers or email addresses of individuals. The old site obfuscated these. Use role addresses (`research/decisions.md` D11), with at most one form.
- **Never publish SCEM FOUO material** (rule in `research/decisions.md` D13, "Never publish"; SCEM to confirm). The SCEM non-disclosure agreement in the current ARES application says information from SCEM radios and computers, "which includes frequencies and operating procedures", is For Official Use Only and "shall not be released in any manner to the public". So the site shows **no** county, hospital, SCEM, SHARES or 800 MHz frequencies, channel numbers or talk-groups (including the Hospital Net's channel), **no** procedures from SCEM equipment or the ACSFOG, and **no** SHARES, Region 9 cache, MNU or hospital-station details. Only amateur facts are public: W7GBU 147.300 and 443.400, the 146.88 alternate, the net schedule, NWS 162.400 and WSEN 3.985 MHz. Describe county equipment only in general terms.
- **No rosters or member lists**, in the Library or anywhere else. The net roster belongs in groups.io Files.
- **Event names:** use only the ones listed (Bloomsday, Lilac Torchlight Parade, Valleyfest, SET and so on). Do not add others such as marathons or the county fair.

---

## 12. Assets (`/Users/sean/Projects/spokane_ares/design/assets/`, original filenames)

### Photos
| File | Size / orientation | Subject | Quality | Best use |
|---|---|---|---|---|
| `IMG_6574.jpg` | 3264×2448, 4:3 landscape, 2.6 MB | Portable go-box station on a pickup tailgate at a mountain overlook: rack case with radios, a digital interface, a laptop and yellow binders, with a forested valley and hazy blue sky behind. No people. Bright summer daylight. | **Best in the set.** Sharp, high resolution, modern. | **Hero image.** Crop to 16:9 or 21:9 from the lower two-thirds, or keep the sky for text overlay. Also good for "Field-ready" and Winlink/digital. Resize and compress before use. |
| `8091bb_cfac747e80b142cf8c06b2286c1c6e0b.jpg` | 659×372, ~16:9 landscape | Six volunteers raising an antenna mast in a foggy, frosty field. Four wear hi-vis yellow vests, and one vest reads "SPOKANE COUNTY AMATEUR RADIO EMERGENCY COMMUNICATIONS". | Low resolution, soft; strong content. | **Team / "Join us" card**, What We Do, Exercises. Keep at 660 px or smaller. It is the only image showing group teamwork and the vest branding. |
| `8091bb_6c9b42987e484f27a66a6a7748a7db9c.jpg` | 659×372, ~16:9 landscape | Close-up of an operator with headphones speaking into a hand mic. A parade "ARES Operators & Tac ID" route map (Spokane Falls Blvd) is pinned beside him, with a first-aid box and radio manuals. | Low resolution, sharp enough; warm, human. | **Public service events** card and "people" imagery. **His name badge is legible**, so crop or blur it before public use. |
| `8091bb_f2f27737b12847819b5e343bf18efa27.jpg` | 659×372, ~16:9 landscape | Silhouette of a quad antenna on a lattice tower against a vivid magenta, orange and violet sunset; dark foreground band. | Low resolution but graphic, and forgiving when scaled. | **Mood / section-divider background**, dark themes, the "About amateur radio" band. Works with a duotone or blur. Avoid full-width at 1440 px or more without treatment. |
| `1920x1200sunset.jpg` | 1920×1081, 16:9 landscape | Dusk sky in blue gradients with streaky clouds and a faint peach horizon, over a flat black landscape with power poles and bare trees. | Noisy phone shot, soft; fine as a background. | **Full-bleed hero background behind text.** The large calm sky gives lots of room for type, and the dark bottom band suits a CTA. Not a subject photo. |
| `Don-on-the-air.jpg` | 1600×1200, 4:3 landscape | An older operator in a purple cap and navy fleece on a folding chair, working a laptop and mobile radios set up on a vehicle bumper in a covered lot, with batteries on the ground. | Decent resolution; flash-lit, 2000s-era gear, dated look. | **Field station / digital messaging** illustration at medium size; "real people, real gear". Do not caption with a name. |
| `Operating-in-VAN_4.jpg` | 640×480, 4:3 landscape | Looking into a comm trailer labelled "CHEVROLET" on its rear header: several operators with headsets seated at desks, one standing at the door. | Poor: soft, low-contrast video still. | **Comm trailer** story ("inside the trailer"). Use small or with a heavy duotone treatment. |
| `KD7GHZ.jpg` | 301×200, landscape | An operator in a cap and camo shirt at an outdoor tabletop station (radios, laptop, water bottle) with a white church and trees behind, on a sunny day. | Tiny. | **Thumbnail only** (150 px or smaller), for example in an activity collage. Do not caption with the call sign. |

### Logos and maps
| File | Size | Description | Best use |
|---|---|---|---|
| `Spokane_County_ARES-ACS_logo_slightly_smaller_550x550.png` | 550×551, transparent | **Current seal.** A navy ring (about #24348a) reading "SPOKANE COUNTY" and "ARES – ACS" around a white field holding a navy Spokane County outline with a red border (about #d0202a), a white antenna/mountain icon and "W7GBU". | **Primary brand mark** for the header, favicon source and footer. Slightly soft and 950 KB, so use at 200 px or smaller and ideally redraw as SVG. The navy and red are the core brand palette. |
| `ACS_logo_1000x1000.png` | 1000×993, transparent | ACS seal: a dark purple-navy ring (about #302050) reading "SPOKANE AUXILIARY COMMUNICATIONS", a black antenna with signal waves, and "ACS" in red. Clean. | Secondary mark on the ACS explainer and Join path step 3. The old site's alt text wrongly says "RACES logo". |
| `ARES_logo.png` | 121×121, transparent | ARRL's national ARES emblem: a red ring reading "AMATEUR RADIO EMERGENCY SERVICE" around the ARRL diamond. | Small affiliation badge on the ARES explainer and Join step 1. It is ARRL's trademark; get a vector from ARRL for production. |
| `Spokane_County_ARES_RACES_logo_dash.png` | 121×121, transparent | **Legacy** version of the county seal reading "ARES-RACES". | History or "About" timeline only; not for new branding. |
| `textLogoACS_dash.png` | 450×125, transparent | Wordmark "Spokane County / ARES – ACS" in red with a blue drop shadow, in a WordArt style. | **Do not use.** Set the name in the chosen typeface instead. |
| `SCDEM_Logo.png` | 2400×2400, transparent | The **Spokane County Emergency Management** seal: an orange ring (about #f09010) on black, with quadrants showing a yellow radio tower, a storm and flood, a fire, and a warning triangle. High quality. | **Partner / "Who we serve" mark only**, with the county's permission. It must not read as the group's own logo. |
| `DEM_map.png` | 994×675 | A Google Maps screenshot pinning **1121 W Gardner Ave, Spokane, WA 99260**, near N Jefferson St and the Spokane County Courthouse (Map data ©2024 Google). | Reference only; there are licensing concerns about reusing it. For the live site, use an embedded map or a simple drawn locator. It is fine for mockups as a stand-in. |

### Palette cues from the marks
- Seal navy: about #24348a
- Signal red: about #d0202a
- ACS purple-navy: about #302050
- The county EM seal's orange (about #f09010) and black: good for alert and status accents
- Alert-level colors (Green, Yellow, Orange, Red) come from A-20 and give a natural functional palette
