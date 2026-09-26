# Spokane County ARES-ACS / RACES: what it is

- **Prepared:** 2026-09-25. This consolidates the research corpus into one description of the organization, for the spokares.org rebuild.
- **Status of the facts:** names, callsigns, times and frequencies change often. Anything about **people** is marked **verify before publishing**, with the date it was seen. Guesses are marked **INFERENCE**.
- **Paths** are relative to `research/`, except paths that start with `design/`: those are the repo-root `design/` folder (`../design/` from this file), as in `decisions.md`. Deeper detail lives in:
  - `history.md`: the timeline since 2004
  - `orgs/relationships.md`: 25 related organizations
  - `content/interim-ag7qp/README.md`: the 2026 interim content
  - `design/content-brief.md` (`../design/content-brief.md`): approved copy and the "do not invent" rules
  - `data/facts.yaml`: the same facts as one machine-readable list with source, status, owner and last-verified date (the `decisions.md` D13 checklist)
- **Checks made while writing this file (2026-09-25):**
  - FCC data for W7GBU via callook.info
  - RDAP for spokares.org
  - DNS lookups (`dig` against 1.1.1.1)

  Results are quoted where used.
- **Privacy:** no personal phone numbers, e-mail addresses or home addresses appear here. The only street address is the county building where the group meets.

---

## 0. In one paragraph

Spokane County ARES-ACS is one team of licensed amateur-radio volunteers with two affiliations.

- **ARES:** the team is an **ARRL Amateur Radio Emergency Service unit**, part of the national ham-radio association's program.
- **ACS:** the same people are **Spokane County Emergency Management's (SCEM) Auxiliary Communication System**. ACS is the county's own volunteer radio unit. It absorbed the older county RACES program.

The team:
- runs a **weekly net every Tuesday at 8 PM on the W7GBU repeater (147.300 MHz)**
- trains monthly at SCEM, 1121 W Gardner Ave
- staffs the county's radio room, comm trailer and mobile network units
- supports hospitals, NWS Spokane (SKYWARN) and public events such as Bloomsday and the Lilac Torchlight Parade

It provides backup communications when normal systems are overloaded or down. It says plainly that it is **not** a first-responder organization.

Sources:
- `content/live/04-about-spokane-county-ares-acs.md`, `content/live/03-ares-acs-staff.md`
- `content/interim-ag7qp/ares-acs.md`, `content/interim-ag7qp/new-members-orientation.md`
- `sources/government/spokane-county-CEMP-2021-03.txt`

---

## 1. Identity and names

| Item | Value | Source |
|---|---|---|
| Name in current use (2026) | **"Spokane County ARES-ACS"**: ag7qp.com, the groups.io group name, the Joomla staff-page title and the group's seal | `content/interim-ag7qp/spokane-county-ares-acs.md`, `content/live/03-ares-acs-staff.md`, `history.md` §3 |
| Legacy name | "Spokane County ARES/RACES". It is still the spokares.org `<title>` and footer | `site-functionality-analysis.md` §2a |
| Other forms seen | "ARES - RACES - ACS" (Join page, 2024); "ARES/ACS/RACES" (IEVHFRA MOU, 2023); "ARES/ACS" (county page 1813 in 2020; COAD 2023-24) | `history.md` §3 |
| What "ACS" stands for | The sources disagree (details below) | `orgs/relationships.md` Contradiction 2 |
| On-air identity | **W7GBU**, a club callsign, used on the repeaters and the Winlink gateway (W7GBU-10) | §8 |
| Legal entity | The only one found is the **"Spokane County ARES RACES Support Group"**, the FCC club licensee of W7GBU. No nonprofit registration or bank account was found. IEVHFRA held a $300 donation "for ARES" in 2019 because "ARES has no bank account" | `orgs/relationships.md` §1, §2, §9 |
| Web and online | spokares.org (Joomla 3, **hacked in 2026**); interim page at https://ag7qp.com/spokane-county-ares-acs; member hub at https://spokaneares-acs.groups.io/g/main | §13 |

**How "ACS" is expanded in each source:**
- **County ACS page title:** "Auxiliary Communication **Systems**" (https://www.spokanecounty.gov/1813/ACS-Auxiliary-Communication-Systems).
- **ag7qp.com and the ACS Task Book:** "Auxiliary Communication **System**".
- **County volunteer application:** "Auxiliary Communication **Services**".
- **2021 CEMP:** three spellings: "Auxiliary Communication **Service**", "Auxiliary Communications **Service**" and "Auxiliary Communications **System**". See `sources/government/spokane-county-CEMP-2021-03.txt`, lines 1581, 2568 and 3558.
- **The group's own seal:** "SPOKANE AUXILIARY COMMUNICATIONS" (`design/content-brief.md` §1).

**Naming decision needed:** see `decisions.md` D6. Until it is settled:
- Use **"Spokane County ARES-ACS"**.
- Expand ACS as "Auxiliary Communications", the seal's wording.
- Use "ARES/RACES" only as history. This follows `design/content-brief.md` §1 and §11.

The county agency's name has also changed over time:
1. Spokane City/County DEM
2. "Greater Spokane Emergency Management" (GSEM, 2017-19)
3. **Spokane County Emergency Management (SCEM)**, after the City of Spokane left in 2020

The old site still says "GSEM" and "DES" in places (`history.md` §3, §10 item 10).

---

## 2. Mission and scope

- **Mission** (the ARES/RACES Plan, quoted verbatim; unchanged since 2004): "The mission of Spokane County ARES/RACES is to support and enhance the telecommunications needs of its served agencies with the versatile talents and flexible resources of trained and competent amateur radio operators, thereby serving the public interest in times of emergency or special need." Source: `content/live/02-ares-races-plan.md`, `history.md` §8.
- **Short form** (About page): "Our group concentrates on emergency and civil communications to support the safety of Spokane County residents." Source: `content/live/04-about-spokane-county-ares-acs.md`.
- **Scope limit** (EWA Section Standing Order 1b, 2026-01-16): "Nothing in this order suggests that anyone becomes first responders ... ARES is an auxiliary support function. We should always do what our local Emergency Management Agency requests we do." Source: `content/interim-ag7qp/ares-acs.md`.
- **The county's view of ACS** (CEMP 2021, ESF-2):
  - "Provide primary and auxiliary communication support to Spokane County departments and agencies."
  - "Serve as net control for Emergency Management tactical nets."
  - "Define and assign frequencies and talk-groups."

  Source: `sources/government/spokane-county-CEMP-2021-03.txt`, around line 2568.

---

## 3. What members do

| Activity | What it involves | Evidence |
|---|---|---|
| County emergency communications | Staff the SCEM Comm Room and comm trailer, and operate the Mobile Network Units (MNU-H and MNU-T), hospital stations and caches. ACS members are activated by SCEM, the Agency Having Jurisdiction (AHJ). In the Aug 2023 Gray and Oregon Road fires the group provided a **mobile network unit for the ICP and the Disaster Assistance Center** | `content/interim-ag7qp/new-members-orientation.md`, `acs-task-book.md`; `sources/government/spokane-COAD-minutes-2023-09-05.txt` |
| Hospital communications | The Hospital Coordinator AEC keeps radio and computer equipment working at Greater Spokane hospitals. A Hospital Net follows the regular net on some Tuesdays (e.g. 2026-10-06). **Its channel is not repeated here:** channels and procedures on SCEM radios are FOUO under the SCEM NDA, so they are never published (`decisions.md` D13, "Never publish"). HIPAA training is required | `content/live/03-ares-acs-staff.md`, `content/interim-ag7qp/spokane-county-ares-acs.md` |
| Public service events | Bloomsday and the Lilac/Armed Forces Torchlight Parade (the signature events, 2004 to 2027), plus fun runs and bike races. Past events include Valleyfest and Hoopfest | `history.md` §5, `orgs/relationships.md` §23 |
| SKYWARN | Weather spotting for NWS Spokane (OTX). The group has run a **SKYWARN Recognition Day station at the NWS office "each year since 1999"** | `content/live/37-skywarn-weather-spotting.md`, `history.md` §5 |
| Digital messaging | The Winlink gateway W7GBU-10, twice-monthly Winlink assignments, and workshops on Winlink, VARA and ARDOP | §8, §9 |
| Weekly net | A directed training net. Members rotate as Net Control Station (NCS). Visitors are invited to check in | §9, `content/live/08-net-preamble-script.md` |
| Exercises | SET (annual, October), EOC-to-EOC 5th-Saturday exercises with Camp Murray, State COMMEX, Great ShakeOut (a DYFI message by Winlink), and the WSDOT exercise (2026-09-26/27) | `history.md` §5, `content/interim-ag7qp/spokane-county-ares-acs.md` |
| Section-level duty | Standing Order 1b: in an emergency without instructions, self-activate on HF 3985/3990/3995 kHz, then 40 m WSEN frequencies, then the KBARA repeater system. Send WA Field Situation and DYFI reports by Winlink to the local EM, EC, DEC and SEC | `content/interim-ag7qp/ares-acs.md` |
| Community presence | Reports at Spokane COAD (2016, 2017, 2023, 2024) and the comm trailer at the IEVHFRA tailgate (June 2026) | `orgs/relationships.md` §9, §13 |
| Training others | License classes, run jointly by SCEM, the VHF Club (IEVHFRA) and ARES, and typically taught by AD7FO | `content/live/42-amateur-radio-training.md`, `content/interim-ag7qp/licenses.md` |

---

## 4. Structure and roles

**Every row is verify before publishing.** The "as of" date is when the source was seen or last modified.

### 4.1 Local leadership (the Spokane County unit)

| Role | Name, callsign | As of | Source | Notes |
|---|---|---|---|---|
| Emergency Coordinator (EC); RACES Officer; website admin | Asa Jay Laughton **W7TSC** | Staff page modified 2026-03-24; ag7qp `emcomm-groups` seen 2026-09-25 lists him as "EC/RO" | `content/live/03-ares-acs-staff.md`, `content/interim-ag7qp/emcomm-groups.md`, `content/live/41-*.md` | Signs as EC/RO since at least 2019-03. **INFERENCE:** day-to-day contact moved to AG7QP during the outage |
| AEC: Public Events, W7GBU-10 manager, Winlink coordinator | Dan Croskrey **NV2Z** | 2026-03-24 | same | Spelled "Croskey" on ag7qp home. Also IEVHFRA VP. Receives Winlink assignments (2026) |
| AEC: Hospital Coordinator | Mary Griffith **K7MHG** | 2026-03-24 | same | Not confirmed by ag7qp |
| AEC: Logistics & Comms (also "AEC for training") | Dave Carleton **K7DSR** | 2026-03-24; FCC 2026-09-25 | same; callook.info | **W7GBU trustee** per FCC. His GMRS call WRCY281 carries the GMRS net (INFERENCE: the repeater is his) |
| AEC: Net Manager | Frank Hutchison **AG7QP** | 2026-03-24 | same | Also the **EWA Section Emergency Coordinator**. Runs the interim page and the rota, receives applications and Winlink assignments |
| Public Information Officer | **Open** | 2026-03-24 | same | A recruiting hook, and a gap: design concepts need a PIO review |
| SKYWARN primary / assistant representative | Mary Wiese **AA7RT** / Bob Wiese **W7UWC** | Page modified 2019-12-06 | `content/live/37-*.md` | AA7RT is also an EWA ASM and a VE. W7UWC is a former EC |
| Training-certificate recordkeeper | **WA7AQH** | Welcome Letter (2018) | `content/live/41-*.md` | Probably stale |
| Net control operators, Sep-Oct 2026 | KE7RAP, NZ2S, AE7RJ, WA7LNC; KK7SAB in JCal (Sep 1) | 2026-09-25 | `content/interim-ag7qp/spokane-county-ares-acs.md`, `data/calendar-events.json` | Oct 6 and Oct 27 slots are "Open" |
| License-class instructor | John W. "Jack" Tiley **AD7FO** | 2026 | `content/interim-ag7qp/licenses.md` | Also EWA Technical Coordinator and a former SM |

**Gaps:**
- No source dates the handover from W7UWC to W7TSC (it happened between 2015 and 2019).
- No source shows a current written RACES Officer appointment.
- The current county **Communications Specialist**, the SCEM staff owner of ACS since 2023, is **not identified** in any source.

Sources: `history.md` §4, `orgs/relationships.md` people table.

### 4.2 Above the unit

| Level | Role | Name, callsign | As of | Source |
|---|---|---|---|---|
| ARRL EWA Section | Section Manager (elected; appoints the EC, or delegates that to the SEC) | C. Jo Whitney **KA7LJQ** (ag7qp `emcomm-groups` misprints "KJ7LJQ") | 2026-09-25 | arrl.org section page via `orgs/relationships.md` §5 |
| ARRL EWA Section | Section Emergency Coordinator | Frank Hutchison **AG7QP** | 2026-09-25 | same |
| ARRL EWA Section | Assistant Section Managers | W7RHE, KL7LL, AA7RT, NA5XX | 2026-09-25 | same |
| ARES District 9 (= WA EMD Region 9) | District EC | C.M. "Sam" Jenkins **WA7EC** (also Ferry County EC/RO) | 2026-09-25 | same |
| ARRL Northwestern Division | Director / Vice Director | Mark Tharp **KB7HDX** / Michael Sterba **KG7HQ** | terms from Jan 2025 | `orgs/relationships.md` §3 |
| Spokane County | Director of Emergency Management (the Sheriff) | Sheriff John Nowels | release dated 2026-08-01 | `orgs/relationships.md` §7 |
| SCEM | Deputy Director | Chandra Fox | release dated 2026-08-01 | same |
| SCEM | Emergency Management Communications Specialist ("develops, coordinates, and maintains" ACS and supervises its volunteers) | **Holder unknown** (position created 2023) | class spec | `sources/government/scem-comms-specialist-classspec-2024.pdf`, `orgs/relationships.md` §7 |

**Historical leaders** are in `history.md` §4:
- ECs: N7VBW, KI7QT, KD7AMV/W7UWC, W7TSC.
- AECs from 2004, 2009, 2013 and 2019.
- Field Day committees.
- Section officers since 2004.

---

## 5. Two authority chains, one set of volunteers

```
          FCC (47 CFR Part 97) licenses every operator and the W7GBU club station
                                          |
   ARES chain (amateur / ARRL)            |          ACS chain (government / AHJ)
   ---------------------------            |          ----------------------------
   ARRL (ARES Plan Jul 2025,              |          FEMA (ICS/NIMS) . CISA (AUXCOMM, SHARES)
     Task Book Jul 2024)                  |          WA Emergency Management Division
   Northwestern Division                  |            (RCW 38.52, WAC 118-04, mission numbers,
   Eastern Washington Section             |             EMD Comms Annex ~2021)
     (SM KA7LJQ, SEC AG7QP)               |          Spokane County EM (SCEM) - Sheriff is Director
   District 9 (DEC WA7EC)                 |            CEMP 2021: ACS supports ESF-2, 5, 6, 8, 9
   County EC W7TSC + AECs  <==== same people ====>  ACS (RACES folded in): EC also RACES Officer;
                                          |            Communications Specialist (county staff)
       registered with ARES                |          registered as county emergency workers
       (application to the EC)            |            (county application + Sheriff background check)
```

### Why both exist

| | **ARES** (ARRL) | **ACS** (county; formerly RACES) |
|---|---|---|
| Who sponsors it | ARRL, a private national association | Spokane County Emergency Management, a government agency |
| Who can join | Any licensed amateur. ARRL membership is not required to join, but the July 2025 ARES Plan requires it for Intermediate level and above and for EC/AEC appointments | Licensed amateurs who file the county volunteer application **and pass a Sheriff's Office background check** |
| Who activates it | ARES can serve community events, NWS/SKYWARN and the Red Cross "without approval from anyone" | Activated **by the AHJ (SCEM)**. The state mission number triggers emergency-worker coverage |
| Where members can serve | Public events, NWS, Red Cross, self-activation under SOP 1b | **EOCs, incident command posts, hospitals and shelters**, "areas where you will handle sensitive information". Members may use government equipment and frequencies |
| Qualification | ARRL ARES Task Book (Basic, Intermediate, Advanced levels) | ACS Position Task Book v2024-08-09 (New Member, RADO, S-RADO, Leadership); county ID card at RADO |
| Legal footing | Operates under normal Part 97 rules; national ARRL agreements with FEMA, Red Cross, NWS and others | Registered emergency workers under RCW 38.52 / WAC 118-04. RACES operation under 47 CFR 97.407 needs civil-defense certification (see §6) |

Sources:
- `content/interim-ag7qp/new-members-orientation.md` (quotes above), `ares-acs.md`
- County ACS page and application, via `orgs/relationships.md` §7
- `orgs/relationships.md` §3 and §4 (ARES Plan 2025)

**What this means in practice:**
- The group describes itself as operating "in similar fashion; often you can't tell which is which".
- The EC is appointed by the ARRL Section, and the same person is named RACES Officer by the county.
- The site must explain the "two hats" **once**, clearly, on About and Join. The history shows that renaming by search-and-replace caused confusion (`history.md` §10 item 10).
- **Policy tension to explain:** entry to ARES is open, but ARRL membership is required to advance. The old Spokane Plan says membership is not required (`orgs/relationships.md` Contradiction 8).

**AUXCOMM** is a separate idea. It is the federal (CISA) designation for trained Auxiliary Communicators (AUXC) in the ICS Communications Unit. ag7qp.com calls it a FEMA course; CISA runs it. Members took the course in 2019 (`orgs/relationships.md` §19).

---

## 6. Legal basis

| Layer | Instrument | What it does for this group | Source |
|---|---|---|---|
| Federal | **47 CFR Part 97** (Amateur Radio Service) | Licenses every member and the W7GBU club station. The old Plan cites Subpart E as its first authority and operates under ARES rules unless a national emergency requires RACES-only operation | `orgs/relationships.md` §24; `content/live/02-ares-races-plan.md` |
| Federal | **47 CFR 97.407** (RACES) | RACES stations and operators must be certified by the civil-defense organization. Traffic is limited to authorized civil-defense communications. Drills are capped at 1 hour a week, or 72 hours twice a year with approval | https://www.ecfr.gov/current/title-47/chapter-I/subchapter-D/part-97/subpart-E/section-97.407 (via `orgs/relationships.md` §24) |
| State | **RCW 38.52** | .010 defines an "emergency worker" as someone registered with a local EM organization who holds its ID card. .070 authorizes local and joint EM organizations, the basis of SCEM's inter-local agreement. .180 covers liability. .310 covers registration and coverage | https://app.leg.wa.gov/rcw/default.aspx?cite=38.52 (via `orgs/relationships.md` §24) |
| State | **WAC 118-04** (amended effective **2025-12-27**) | "Registration is a prerequisite for eligibility of emergency workers for benefits and legal protection." Registration is on Form EMD-024 or an equivalent; the county application is that equivalent. "Communications" is a defined class of emergency worker. Coverage is tied to **mission numbers** (WAC 118-04-240) | `sources/government/WAC-118-04-2025.txt` (118-04-080, the Communications class, 118-04-240) |
| State | WA EMD **Logistics Section Communications Annex** (about 2021) | "Supersedes all preceding State RACES Plans" (1995; a 2013 copy is online). Covers SHARES Winlink RMS stations, the SEOC radio room and the RADO task-book structure that the ACS Task Book mirrors | `sources/government/wa-emd-logistics-comms-annex-2021.txt`, `wa-state-RACES-plan-1995.txt` |
| County | **Spokane County CEMP, March 2021** | The Sheriff is Director of Emergency Management. ACS is a supporting agency in ESF-2 (communications), 5, 6, 8 and 9. The inter-local agreement covers the unincorporated county, Spokane Valley and 11 other municipalities, **not the City of Spokane** (since 2020) | `sources/government/spokane-county-CEMP-2021-03.txt` |
| County | SCC ch. 1.08 | **LIKELY:** creates the DEM with the Sheriff as director. Seen only in a search snippet | `orgs/relationships.md` §7 |
| National agreements (ARRL) | FEMA MOA (2023); American Red Cross MOU (renewed April 2021); NWS SKYWARN (1986); Salvation Army/SATERN; NVOAD; Radio Relay International (2025-07-18) | Allow ARES service to these agencies. The old Plan still cites the **1984** FEMA MOU; cite the 2023 MOA instead | `orgs/relationships.md` §3 |
| Local agreement | **MOU with IEVHFRA, Rev. 5, 2023-05-06** | See §8 | `content/live/68-mou-with-ievhfra.md` |

**Caution for the site:** any public "your status as a volunteer" text must be reviewed by SCEM and must not overstate coverage (`orgs/relationships.md` §24).

---

## 7. Served agencies and partners

| Partner | Relationship | Status | Source |
|---|---|---|---|
| **SCEM** (Sheriff's Office) | Primary served agency and AHJ. Runs ACS, the background check, the ID card and the equipment | Current | `orgs/relationships.md` §7 |
| **NWS Spokane (OTX)** | SKYWARN spotting, severe-weather nets, the SRD station at the NWS office | Current, since at least 1999 | §10 there |
| **Greater Spokane hospitals** (ESF-8: Deaconess is the primary DMCC, Sacred Heart the secondary) | Backup hospital radios, the Hospital Net, a Hospital-Coordination groups.io subgroup | Current | §14 there |
| **American Red Cross**, Greater Inland Northwest | Served through the national ARRL MOU. ESF-6 lead. Red Cross familiarity training is "under development". **No local MOU found** | Current (national) | §12 there |
| **Spokane COAD** | The group reports there (2016, 2017, 2023, 2024) | Current | §13 there |
| **IEVHFRA** ("VHF Club") | The only written inter-club agreement (MOU Rev. 5). Shares officers (NZ2S, NV2Z). Co-sponsors license classes | Current | §9 there |
| **WA EMD** | Mission numbers, EOC-to-EOC and State COMMEX, SHARES, Region 9 (led by SCEM) | Current | §8 there |
| **WSDOT** | Joint exercise, 2026-09-26/27 | One-off | §15 there |
| **Event organizers** (Bloomsday, Torchlight Parade, Climb for the Cure) | Public-service communications. No formal agreements | Current | §23 there |
| **Neighbouring county groups** (Stevens, Lincoln, Pend Oreille, Ferry; Kootenai ID) | Mutual aid under a Plan clause only | Peer | §18 there |
| **SCEM peer programs** (CERT, MRC, SAR, DART, MLST, HEART) | Same county application and background check | Sibling | §16 there |
| Historic only | SREC 911 / Combined Communications Building (old gateway site), fire agencies and DNR NE (old Plan annexes), Salvation Army (old Plan) | Do not present as standing agreements | §15 there |

**The City of Spokane has run its own emergency management office since 2020.** Bloomsday and the parade take place inside city limits. Whether ACS serves City incidents, and on what basis, is **unknown** (`orgs/relationships.md` §7, §15).

---

## 8. Infrastructure

| Asset | Facts | As of / verification | Source |
|---|---|---|---|
| **W7GBU club license** | CLUB license held by "SPOKANE COUNTY ARES RACES SUPPORT GROUP". Trustee **K7DSR** (David E. Carleton). Previous call KC7SOT. Granted **2017-02-15**. **Expires 2027-02-15**. Last FCC action 2024-03-18 (**INFERENCE:** probably the change of trustee from WA7AQH, which the Etiquette page still shows). Named for the late Ira "Dean" Bula W7GBU; the "7:30 Bunch" morning net is his legacy | **Re-checked on callook.info 2026-09-25: VALID** | callook.info/W7GBU; `content/live/05-w7gbu-call-sign.md`; `orgs/relationships.md` §2 |
| **147.300 MHz repeater** (primary) | +600 kHz, PL 100 Hz, Brownes Mountain, emergency power. IACC record 1359, coordination valid to 2029-06-28. Called the "Spokane County ACS Repeater" in the 2026 preamble and "SCEM" on ag7qp. **INFERENCE:** the hardware may be county-owned; ownership and site agreement are unknown | RepeaterBook reviewed 2025-03-20 | `orgs/relationships.md` §2; `content/interim-ag7qp/docs/net-preamble-2026-03-04.md` |
| **443.400 MHz repeater** | +, IACC record 1253, valid to 2029-02-09. Active ARES use is unknown | same | same |
| **146.880 MHz** (alternate) | IEVHFRA's repeater (146.880-, tone 123, Krell Hill per vhfclub.org). Named as the alternate in the MOU and the 2026 preambles | 2023 MOU; 2026 preamble | `content/live/68-*.md`, `orgs/relationships.md` §9 |
| 147.06 (N7BFS) | Called Spokane's "2nd alternate" in the old Plan; linked to Stevens 146.62 | Old Plan only; verify | `orgs/relationships.md` §18, §22 |
| **W7GBU-10 Winlink gateway** | Managed by NV2Z. **Frequency and site disputed:** the old Plan says 145.090 at the Combined Communications Building; K7BFL's 2016 table says 145.01 via WR7VHF-3. **Do not publish until NV2Z confirms.** The legacy HF Pactor gateway has been off air since 2006 | Unverified | `orgs/relationships.md` Contradiction 4 |
| **County equipment** | Comm Room at the DEM/SCEM building; comm trailer; MNU-H (hitch) and MNU-T (trailer); hospital stations; caches; Region 9 portable repeater cache | 2026 | `content/interim-ag7qp/acs-task-book.md`, `new-members-orientation.md` |
| **GMRS** | "WRCY281 GMRS repeater", listed as GMRS 7, 462.700 MHz (+5 MHz, 141.3 Hz) | 2025-08-19 preamble | `content/interim-ag7qp/docs/net-preamble-gmrs-2025-08-19.md`, `nets-in-the-inland-northwest.md` |
| **IEVHFRA MOU, Rev. 5 (2023-05-06)** | "MOU Regarding Emergency Operations On Repeater Systems in Spokane County". ARES/ACS/RACES is the primary emergency center on 147.30 W7GBU, activated by SCEM. IEVHFRA provides 146.88 as backup, a second repeater for specialised nets, and packet/UHF paths for ICS-213 and 213RR traffic into Winlink. Points of contact are W7TSC and the club president, **to be reviewed annually**. Both websites are supposed to post it; it was not found on vhfclub.org | 2023 | `content/live/68-mou-with-ievhfra.md`, `orgs/relationships.md` §9 |
| Shared or regional | The KBARA linked system (EWSEN VHF net; the SOP 1b fallback); the WR7VHF packet nodes and WR7VHF-10 RMS; SHARES (a subgroup; training on 2026-10-17); HamWAN (a subgroup; status unknown) | 2026 | `orgs/relationships.md` §17, §21 |

---

## 9. Nets and meetings

### Nets

| Net | When | Where | Confidence | Source |
|---|---|---|---|---|
| **Weekly ARES-ACS net** (directed training net) | **Every Tuesday, 2000 local** | **W7GBU, 147.300 MHz, +600 kHz, 100 Hz** | High: consistent 2004 to 2026 | `content/interim-ag7qp/nets-in-the-inland-northwest.md`, `docs/net-preamble-2026-03-04.md`, `history.md` §5 |
| Winlink nights | 2nd and 4th Tuesdays | Winlink | 2026 preamble | `docs/net-preamble-2026-03-04.md` |
| Simplex net | Replaces the repeater net in months with a **5th Tuesday** | **Simplex frequency not stated in any current source**; do not publish one | Frequency unverified | `docs/net-preamble-simplex-2026-03-04.md` |
| ACS GMRS net | 3rd Tuesday, 1930 | GMRS 7 / WRCY281 | 2025 preamble | §8 |
| "0730 Bunch" (informal round table) | Mon-Fri, 0730 | 147.300 | 2026 net list | `nets-in-the-inland-northwest.md`, `content/live/05-*.md` |
| Hospital Net | Following the regular net (e.g. 2026-10-06) | A hospital/SCEM radio channel, named on ag7qp.com. **Never publish it** (SCEM FOUO rule, `decisions.md` D13); members-only | Seen once | `spokane-county-ares-acs.md` |
| Winlink assignments | Twice a month, 1700-2100, on ICS-213; sent to NV2Z and AG7QP | Winlink | Schedule to 2026-12-22 | same |
| Section and state | EWSEN Mondays: VHF 1815 on KBARA, HF 1845 on 3985 kHz, Winlink check-in 1700-1900. WSEN on 3985 kHz | – | Link, don't restate | `orgs/relationships.md` §5, §11 |

**Check-in order** (the current weekly script, version 2026-03-04, `content/interim-ag7qp/docs/net-preamble-2026-03-04.md`):
1. Officials: the Spokane County EC, Spokane County Emergency Management personnel, the AECs, ARRL District and Section officials, officials from surrounding counties.
2. Members. The script still says check-in is "ordered alphabetically in groups by call sign suffix", but it no longer lists the groups: "Use the roster and confirm each call sign".
3. Visitors ("call sign, name and location").
4. Simplex, no-tone and cross-band stations, then relays.
5. Listed traffic, then late or missed check-ins "Alpha through Zulu".

**Variants in the other 2026 scripts:**
- The **5th-Tuesday simplex** script (2026-03-04) calls members in five suffix groups: Alpha-Delta, Echo-India, Juliet-November, Oscar-Sierra, Tango-Zulu. Each station reads back the stations it heard. The net then moves to the repeater and repeats the five groups for stations that missed simplex.
- The **ACS GMRS** script (2025-08-19) calls members by county volunteer-number range (100-300, 300-500, 500-700, 700-999).
- The 2018 preamble's three groups (A-F, G-M, N-Z; `content/live/08-net-preamble-script.md`) are **superseded**.

**Script defects to fix with AG7QP** (the scripts are the masters; `decisions.md` "Questions for AG7QP" #8):
- The simplex script hard-codes **"Net Control Station K7DSR"** in three places instead of `{INSERT CALL SIGN HERE}`.
- No script states the simplex frequency ("the Spokane County ARES-ACS simplex frequency").
- The simplex script sends stations to "a roster on the Spokane County ARES website", to "the contact form on the spokares.org website" (HTTP 500) and to a contact report "available on our website"; the weekly and GMRS scripts send changes to a personal e-mail. All should become a role address (D11).
- The simplex script's repeater section reads "meeting every Tuesday at 2000 hours local time on." with the repeater name missing; the weekly script has an unclosed "[the Spokane County ACS Repeater."; "locale time" is a typo.
- The GMRS volunteer-number ranges overlap at 300, 500 and 700, and the script says "groups by call sign suffix" before calling number ranges.
- The weekly script titles the EC "Emergency Coordinator and ACS Officer", while other sources say "RACES Officer" (open question, `decisions.md` D13 #18).

**The JCal calendar showed three September 2026 nets at 22:00. That is a data-entry error; never show the net at 10 PM** (`site-functionality-analysis.md` §3.1).

### Meetings (all at SCEM / "the DEM", 1121 W Gardner Ave, Spokane, WA 99260)

| Meeting | Pattern | Time (2026 source) | Conflict |
|---|---|---|---|
| Staff meeting | 1st Tuesday | 1730 | **Conflict:** `design/content-brief.md` §4 treats it as internal, but ag7qp `home.md` and `contact.md` list it under "ARES-ACS Meetings (Open to the public)". Keep it off public pages until the EC answers (`decisions.md` D13 #16) |
| Second Saturday Workshop | 2nd Saturday | 0900-1200 | – |
| Winlink workshop | 2nd Saturday, after the general workshop | 1230-1530 | – |
| Third Thursday Training Meeting | 3rd Thursday | **1800-2000** (ag7qp.com only) | **Single 2026 source.** 1900 (7-9 PM) is given by the Meeting Location page (edited 2024-08-10), the 2018 Welcome Letter, JCal 2018-20 and a Spokesman-Review calendar listing for **2025-09-18** (`orgs/relationships.md` Contradiction 3). **INFERENCE:** if 1800 is right, it changed within about 12 months. Confirm with the EC/AG7QP before copy freeze (`decisions.md` D13 #4) |
| New Member Orientation | 4th Tuesday ("usually") | 1800, then a Comm Room tour and the 2000 net | **Not confirmed as standing.** The sources say "usually"; the only dated sessions are Sep 2024-Jan 2025 (`site-functionality-analysis.md` §3.1); the ag7qp Sep-Oct 2026 event list has no Oct 27 session. Publish "held regularly; contact us for the next date" until confirmed (`decisions.md` D13 #17) |

- **December meeting:** there was none in 2019 ("NO MEETING for December"). Whether that is still the rule is unconfirmed.
- **Sources:** `content/interim-ag7qp/README.md` (diff table), `home.md`, `contact.md`, `winlink.md`, `new-members-orientation.md`, `design/content-brief.md` §4.
- **Where the group used to meet:** 1618 N Rebecca (ECC, 2009-15) and the Fire Training Center (2017-18). See `history.md` §6.

### Fall 2026 calendar (from ag7qp.com, seen 2026-09-25)

| Date | Event |
|---|---|
| Sep 26-27 | ARES-ACS / WSDOT exercise |
| Oct 3 | SET |
| Oct 6 | Staff meeting; Hospital Net after the regular net |
| Oct 10 | Workshop |
| Oct 15 | ShakeOut DYFI message at 1015; Third Thursday training |
| Oct 17 | SHARES training, 0900-1200 at DEM |
| Oct 24 | State COMMEX |
| Dec 4-5 | SRD |
| 2027-01-23/24 | Winter Field Day |
| 2027-05-02 | Bloomsday |
| 2027-06-13 | Climb for the Cure |

Sources: `content/interim-ag7qp/spokane-county-ares-acs.md`, `home.md`.

---

## 10. Membership path

### ARES registration and ACS registration are separate

| | ARES | ACS |
|---|---|---|
| Form | **Spokane County ARES Membership Application**, version 2024-11-07. It includes an SCEM non-disclosure agreement and is returned to AG7QP per the form. The ARRL registration form is FSD-98 | **Spokane County Volunteer Emergency Worker Application** (FormCenter 276; spokanecounty.org now redirects to .gov) |
| Check | None | **Sheriff's Office background check**; volunteer number assigned |
| Task book | ARRL ARES Position Task Book (July 2024) | ACS Position Task Book v2024-08-09 |
| Result | ARES member | ACS New Member, then RADO with a **county ID card** |

Sources:
- `content/interim-ag7qp/docs/ares-membership-application-2024-11-07.md`, `ares-acs.md`
- https://www.spokanecounty.gov/FormCenter/Emergency-Management-31/Volunteer-Emergency-Worker-Application-276
- `orgs/relationships.md` §4, §7

### Steps (synthesised; confirm with leadership)

0. **Get licensed.** Technician class or higher. There are local classes and VE sessions, and the FCC charges a $35 fee (`design/content-brief.md` §6).
1. **Join ARES:** file the ARES application and get the ARRL task book.
2. **Get connected:** apply to join the groups.io group (a moderator approves each subscription; §12), check into the Tuesday net, and attend a Third Thursday meeting or a Second Saturday workshop.
3. **Optional: qualify for ACS.** File the county application, pass the background check, attend orientation, then complete the New Member items.

The older Welcome Letter (2018) added two rules: IS-100 and IS-700 plus a **six-month activity period** before the county ID is issued (`content/live/41-*.md`).

"Even [if] you don't or can't qualify for ACS, our ARES group welcomes you" (`content/live/26-*.md`).

### Task-book levels

**ACS Position Task Book** (v2024-08-09; `content/interim-ag7qp/acs-task-book.md`). Items are signed off by the EC or any AEC; each level is signed off by the EC.

| Level | Key requirements |
|---|---|
| **New Member** (normally up to 1 year) | IS-100, 200, 700 and 800 (plus a FEMA Student ID); ARRL Basic EmComm; orientation; HIPAA; Alert Spokane registration; net check-in monthly (weekly encouraged); a meeting or workshop quarterly (monthly encouraged); personal kit (24 h minimum, 72 h preferred) and radio kit (2 m / 70 cm for 24-72 h); program the HT; ICS-309 logging; 1 field operation a year; SKYWARN training every two years (encouraged) |
| **RADO** (county ID card issued) | Net control once a year; ICS-214 logs; Comm Room and trailer competency; Intermediate EmComm; First Aid/CPR every two years; Red Cross familiarity (encouraged) |
| **S-RADO** | General class license; Winlink yearly; MNU setup; lead or assist an event yearly; AUXCOMM (encouraged); SHARES; field shelter; R9 radio cache; COMT (optional). Advanced EmComm is "not yet available" |
| **Leadership** | Adds IS-120, 230, 235, 240, 241, 242, 244 and 288 and related items |

**ARRL ARES levels** (ARES Plan, July 2025; `orgs/relationships.md` §4):
- **Basic:** IS-100, 200, 700 and 800, Basic EmComm and the Level 1 Task Book.
- **Intermediate:** adds Intermediate EmComm and full ARRL membership.
- **Advanced:** adds IS-230, 240, 241, 242, 244 and 288. Required for EC, AEC, DEC and SEC.

**Stale item:** the Task Book and orientation still describe Alert Spokane as the "Code Red" app. **ALERT Spokane moved to Regroup in April 2026** (`orgs/relationships.md` Contradiction 12).

---

## 11. Training expectations

**Ongoing minimums** (the ACS New Member level, maintained at every level):
- A net check-in each month.
- A meeting or workshop each quarter.
- One field operation each year: an activation, exercise or public-service event.
- Kits kept ready, with the HT programmed.

**Where the training happens:**
- Second Saturday workshops, some of them hands-on.
- The Winlink workshop.
- Third Thursday training.
- Orientation.
- County-arranged classes: First Aid/CPR, defensive driving (optional; only needed to drive county vehicles), AUXCOMM and COMT.
- Recurring exercises: SET, COMMEX and EOC-to-EOC.
- NWS spotter classes, at https://www.weather.gov/otx/Current_Spotter_Training.

**Current course names** are IS-100.c, IS-200.c, IS-700.b and IS-800.d. The ARRL Basic and Intermediate EmComm courses replaced EC-001 and EC-016 in May 2024. The old site's links still point to superseded versions (`orgs/relationships.md` §19, `site-functionality-analysis.md` §4).

**Useful extras:** the AUXFOG and NIFOG from CISA, and the Spokane **ACSFOG**, which is distributed on groups.io and is members-only (`new-members-orientation.md`).

---

## 12. Communications channels

| Channel | Use | Status (2026-09-25) | Source |
|---|---|---|---|
| **groups.io** (spokaneares-acs.groups.io/g/main) | Member e-mail list, calendar, files (the ACSFOG), wiki and announcements. Subgroups: Digital-Modes, HamWan, Hospital-Coordination, Leadership, SHARES, Winlink, WinterFieldDay | **107 members, 2,882 topics.** In use by 2018-03, not 2019. Renamed from `spokaneares-races` (the old name no longer resolves). **Anonymous check of the group page, 2026-09-25:** "Subscriptions to this group require approval from the moderators"; "Archive is visible to members only"; "Wiki is visible to members only" (so a Wiki exists); Files and Wiki redirect to the login page; posts from new users are moderated. The group information still links **www.spokares.org**. The owner is reachable at the group's role address `main+owner@SpokaneARES-ACS.groups.io`; the owner's identity is unknown. The `/calendar` page returns 200 to anonymous users, but its server HTML contains no events (they load by script), and a guessed `/g/main/ics/feed.ics` returns 404, so **a public calendar feed is still unconfirmed**: ask the owner (`decisions.md` D9) | `orgs/relationships.md` §1, `history.md` §7, `site-functionality-analysis.md` §7.3; https://spokaneares-acs.groups.io/g/main (checked 2026-09-25) |
| Weekly net | Announcements ("changes announced on the weekly ARES-ACS Net") | Current | `content/interim-ag7qp/contact.md` |
| ALERT Spokane (county) | Public alerting. Members must register (Task Book) | On **Regroup** since April 2026 | `orgs/relationships.md` §7 |
| E-mail on spokares.org | Unknown mailboxes and forwarders on InMotion. **MX = `0 spokares.org.`** (a priority-0 record pointing at the domain itself, **not** a null MX). SPF `v=spf1 +mx +a +ip4:70.39.251.92 ~all`. **DKIM** key published at `default._domainkey` (RSA). **No DMARC** record (`_dmarc` is NXDOMAIN, also at the InMotion nameserver). The SPF IP's reverse DNS is a host under `asajay.com` (**INFERENCE:** a server associated with the EC; ask him whether it sends mail for the domain before SPF is changed, `decisions.md` D3) | Live at InMotion (dig against 1.1.1.1, 8.8.8.8 and ns1.inmotionhosting.com, re-checked 2026-09-25) | §13 |
| Contact | Joomla contact form: HTTP 500. Interim: "contact Frank Hutchison, AG7QP" | Broken / interim | `site-functionality-analysis.md` §3.4 |
| Social | None owned by the group was confirmed. The Section has a Facebook group (backlog question) | – | `orgs/_backlog.md` |

---

## 13. Web presence: status on 2026-09-25

| Property | Status | Source |
|---|---|---|
| **spokares.org** | Joomla 3 (EOL since Aug 2023), template `js_wright`, on **InMotion** (NS `ns1/ns2.inmotionhosting.com`; A 70.39.146.166; `www.` and `mail.` point to the apex). **Hacked in 2026** per AG7QP; the date and vector are unknown. Served HTML shows no visible or cloaked spam. **Downloads (OSDownloads) and the contact form (ALFContact) return HTTP 500** (missing Alledia framework class). The live server started blocking this research client (406, then timeouts). The last Joomla edit was 2026-03-24, and JCal rota entries were added for Sep 2026 | `site-functionality-analysis.md` §0, `history.md` §2.6, `external/spokares-home-as-googlebot.html` |
| **Domain** | Registrar **GoDaddy.com, LLC**. Registered 2004-03-10. Transferred 2021-11-12. Last changed **2026-08-12** (what changed is not shown). **Expires 2027-03-10.** Status: client delete/renew/transfer/update prohibited (**INFERENCE:** GoDaddy's standard lock set). Who holds the account is **unknown** | RDAP (PIR), checked 2026-09-25 |
| **ag7qp.com** (interim) | Hostinger Website Builder (Zyro), run by SEC AG7QP. 33 pages, published 2026-09-23. Holds the rota, events, preambles, application, task books and orientation. Also publicly links a **net roster PDF (likely PII)** | `content/interim-ag7qp/README.md` |
| **groups.io** | Member hub (§12) | – |
| **felge.us** (K7BFL) | Traffic and Winlink references; HTTP only | `orgs/relationships.md` §21 |
| County page 1813 | Titled "ACS". **No longer links spokares.org** (it did in 2020) | `orgs/relationships.md` §7 |
| Inbound links to fix after launch | Bonner County (a dead Wix URL); wastateares.org local contacts; SRG links; ag7qp `emcomm-groups` (links the hacked site) | `orgs/relationships.md` §11, §18, §22 |
| Past web eras | Static 2004-2015 (hosting donated by Sisna/ASISNA), Wix 2015-16, Joomla 2017-2026 | `history.md` §1 |

---

## 14. Contradictions still open (resolve before publishing)

| Topic | Conflict | Owner | See |
|---|---|---|---|
| Public name / ACS expansion | ARES-ACS vs ARES/RACES vs ARES/ACS/RACES; System, Systems, Service or Services | EC + SCEM | `decisions.md` D6 |
| Third Thursday start time | 6 PM (ag7qp 2026, one source) vs 7 PM (older sources through a 2025-09-18 listing) | EC / Net Manager | `decisions.md` D13 #4 |
| Staff meeting: public or internal | "Open to the public" (ag7qp) vs internal (content brief) | EC | D13 #16 |
| EC's county title | "RACES Officer" (staff page, MOU) vs "ACS Officer" (2026 scripts) vs "EC/RO" (ag7qp); the county says RACES "is now incorporated into ACS" | EC + SCEM | D13 #18 |
| Simplex frequency | Not stated anywhere current | Net Manager | D13 |
| W7GBU-10 frequency and site | 145.090 (CCB) vs 145.01 (WR7VHF-3) | NV2Z | D13 |
| Day-to-day public contact | W7TSC (staff page) vs AG7QP (interim page) | W7TSC + AG7QP | D13 |
| AG7QP as Spokane AEC while also SEC | Both listed | AG7QP | D13 |
| Trustee on the Repeater Etiquette page | WA7AQH vs K7DSR (FCC wins) | Fix in content | – |
| Name spellings | "Croskrey" vs "Croskey"; "KA7LJQ" vs "KJ7LJQ" | Verify | – |
| ARRL membership | "Not required" (old Plan) vs required for Intermediate and above (2025 Plan) | Explain both | – |
| ALERT Spokane platform | Code Red vs Regroup (April 2026) | Fix in content | – |
| The Plan | Live "7.13" text (about 2013) vs the need for a current county ESF-2 attachment or ACS SOP | EC + SCEM | `decisions.md` D13 |

Full list: `orgs/relationships.md` (Contradictions & unknowns) and `content/interim-ag7qp/README.md` (stale items).
