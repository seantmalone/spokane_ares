# Round 3: placement map and page budgets

- **Prepared:** 2026-09-26, by the managing editor, for all three options (A Figure One, B Carry the Message, C Open Channel).
- **Why:** the owner's feedback on round 2: "All three of these have just way too much info. It's overload. Do another pass on each to simplify. Minimize repeating information unless it's really important."
- **Status:** this file overrides `round2/_shared/ia.md` §2 to §5 and the section lists in `round2/_shared/content-*.md` wherever they differ. The round-2 content files are still the **only source of facts and wording**. Round 3 cuts them down and never adds to them.
- **Edited 2026-09-26 (audit pass):** the repetition and loss audits were applied; where they conflicted, the fact kept ONE home. Rows and specs below reflect the edited `copy-*.md`. Measured counts are in `README.md`.
- **Unchanged from round 2:** `data.js` is the single source for dated items, and no-JS fallbacks match it as of 2026-09-26. The `[verify]` → `data-verify="true"` rule stays, with no visible chips. The "Never publish" and "Deliberately left off" lists (ia.md §8.6 and §8.7) still apply, as do the accessibility baseline (ia.md §9), each option's visual personality and the four sections.

## Targets at a glance

| Page | Round-2 words (A / B / C) | Round-3 budget | Max H2 | Other limits |
|---|---|---:|---:|---|
| `index.html` | 1,894 / 1,096 / 1,644 | **400** | 5 (plan uses 3) | ≤ 6 CTAs in `<main>`; ≤ 3,200px on desktop |
| `how-it-works.html` | 3,620 / 3,802 / 4,146 | **1,200** | 6 (plan uses 5) | B's story: ≤ 1 screen before practical content |
| `about.html` | 6,621 / 6,638 / 7,055 | **2,800** | 9 | TOC with 9 entries |
| `members/index.html` | 1,271 / 1,145 / 1,215 | **300** | 3 | a switchboard, with no explanations |
| `members/documents.html` | 2,413 / 2,467 / 2,264 | **900** | 7 | descriptions ≤ 8 words, or none |
| `members/exercises.html` | 1,986 / 2,098 / 2,554 | **700** | 5 | next events first |
| **Site** | 17,805 / 17,246 / 18,878 | **≈ 3,200 measured** (6,300 ceiling) | | |

Words are visible words inside `<main>`, counting table cells, figure labels and `<details>` contents. The section budgets below add up to less than each ceiling, which leaves each option about 10% for its own voice.

---

## 0. Editing rules

1. **One home per fact.** Every fact below has exactly one home (page#section). Elsewhere it gets **at most one line with a link**, and only where the table below allows it.
2. **Only the chrome repeats:** the primary nav with its Join button, the members sub-nav on members pages and the footer. Nothing else repeats. There is no utility bar and no next-net line in the chrome.
3. **A section has to hold content.** No section exists only to link to other sections, so these all go: "Ready to try it?", "Already a member?", "Everything for members", "On groups.io", "Start where you are" and "At a glance".
4. **Lead with the action.** Each page has one primary action. Cut preamble, reassurance, mission quotes, "why it matters" bands and summary boxes that restate the section below them.
5. **Use lists and tables, not paragraphs.** A table cell holds ≤ 12 words; a list item holds one line.
6. **Use `<details>` only for real reference detail.** Its words count toward the budget. The only planned uses are About's phone TOC and the members sub-nav on phones (optional).
7. **Write times in 12-hour form everywhere, member tables included:** "8:00 PM", "9:00 AM–noon". Never write "2000", and never put a bare 24-hour time next to a date. The slashed zero is for frequencies and call signs only.
8. **No dead `#` links.** In round-3 `data.js` every document `href` is the link to use today (hosted-here documents point at their interim ag7qp.com copy and carry `hostedHere: true`). `href: null` prints the title as plain text with "Soon". Never link to a mocked page that doesn't exist.
9. **People are shown as call signs.** No personal names anywhere in round 3, the rota included, pending the owner's decision in REVIEW "Two decisions" #2 (round-3 `data.js` carries no names).

---

## 1. Fact placement map

**Legend.** *Home* is the one place the fact is stated in full. *Pointers* are the only other places it may appear, each as one line or one clause with a link, and each with the reason it's needed there. *Remove from* lists where round 2 repeats the fact today; builders delete it there.

### 1A. Identity and scope

| # | Fact / topic | ONE home | Allowed pointers (why) | Remove from |
|---|---|---|---|---|
| 1 | Who we are: licensed ham volunteers who back up Spokane County Emergency Management (SCEM) communications | `index.html` hero lede (SCEM named in the first sentence) | none (meta descriptions only) | About lede and At a glance; footer tagline and "ARRL group serving SCEM" line; How it works lede |
| 2 | What volunteers do: county radio room and trailer, hospitals, SKYWARN spotting, community events, radio email | `index.html#what-we-do` (4 one-liners; A's Fig. 1 sits in the hero; B draws its red line through the one-liners; C uses photo cards) | none | B's How it works "What members actually do"; How it works "Where we work"; About "What ARES is" bullets |
| 3 | What we are **not**: first responders, 911, a public alert service, a source of official incident information | `about.html#ares-acs`, the "What we are not" callout | Footer: "Volunteers, not first responders. In an emergency, call 911." (a safety line, the one fact the footer keeps; wording from SOP 1b) | Home hero scope line; FAQ "Am I a first responder?"; the SOP 1b quote in How it works |
| 4 | Former name "ARES/RACES": RACES is the FCC rule (97.407); the county's RACES *program* is now part of ACS | `about.html#ares-acs`, one line | none | footer "Formerly…"; the year (History 2019 "county sets up ACS" holds it) |

### 1B. The net and radio settings

| # | Fact / topic | ONE home | Allowed pointers (why) | Remove from |
|---|---|---|---|---|
| 5 | The Tuesday net: every Tuesday at 8:00 PM, what a directed net is, check-in order, how a visitor checks in | `how-it-works.html#nets` | `index.html#visit`: "Listen … 8:00 PM, 147.300 MHz … No license needed." (the newcomer's easiest first step). `index.html#join` step 2: inline link on "Tuesday net". `members/index.html#rota`: dated instances (net control and the Note), no separate next-net line. | About (At a glance, Where we meet, Contact, FAQ, glossary); exercises calendar net rows and "Also every week"; Home This-week card; B's utility bar; footer |
| 6 | Repeater settings: W7GBU 147.300 MHz, +600 kHz, 100 Hz; alternate 146.880 MHz, −600 kHz, 123 Hz `[verify]` | `how-it-works.html#nets` settings box | `members/index.html#rota`: one copyable line heading the rota, W7GBU only (the fact members use most) | Home (offset and tone, per must-fix 1); About agreements (the frequency), glossary "Offset / tone"; hub radio strip rows for the alternate and NOAA; exercises "Before" list; B's utility bar |
| 7 | NOAA Weather Radio WXL86, 162.400 MHz | `how-it-works.html#alert-levels`, the Orange row | none | hub radio strip |
| 8 | Other nets: Winlink nights (2nd and 4th Tuesdays, during the net); 5th-Tuesday simplex start; ACS GMRS net (3rd Tuesday, 7:30 PM) `[verify]` | `how-it-works.html#nets`, "Other nets" (3 lines) | hub `#rota` Note column: "Simplex", "Winlink night" (links to assignments), "GMRS net, 7:30 PM" | exercises month tables and year-at-a-glance; Home This-week card |
| 9 | 0730 Bunch (weekday mornings) | `about.html#history`, one clause in the W7GBU story | none | How it works "rest of the month" table |
| 10 | EWSEN Section nets (Mondays; 3.985 MHz and others) | **CUT** | none | How it works; exercises "Also every week". *Reason:* they are the Section's nets, and SOP 1b's document covers HF. |
| 11 | Net-control rota, open slots, how to take a slot | `members/index.html#rota` | `how-it-works.html#roles`, Net control row: "Run the net in turn; the Net Manager coaches first-timers." (explains the role, with no dates) | Home This-week card (A prints a name there); exercises "Notes" column |
| 12 | Net scripts (weekly, simplex, GMRS) and the NCS guide | `members/documents.html#net-scripts` (one row, one link while all three sit on the interim page) | hub quick link "Net script" | hub This-week script links; three separate script rows; "(5th Tuesdays)" in the title |
| 13 | Repeater etiquette (ID every 10 minutes, how to break in) | `members/documents.html#repeater-etiquette` (row) | none | the How it works "Pause for the repeater" paragraph |
| 14 | Net report after your net | **CUT** | none | hub rota. *Reason:* the script already says it, and its address is still a personal e-mail `[verify]`. |

### 1C. Visiting and meetings

| # | Fact / topic | ONE home | Allowed pointers (why) | Remove from |
|---|---|---|---|---|
| 15 | Meetings: Second Saturday Workshop (9:00 AM–noon); Winlink workshop (12:30–3:30 PM) `[verify]`; Third Thursday training meeting (evenings) `[verify]`; address: SCEM, 1121 W Gardner Ave, Spokane | `index.html#visit` | hub `#this-week`: the next meeting (data). `about.html#contact`: "In person: see Come visit" (link). `how-it-works.html#exercises`: "a workshop and a training meeting" (link, no times). | About "Where and when we meet" (the whole H2); footer address; hub card address; exercises month rows for workshops and meetings; How it works "Regular training" times; FAQ "net vs meeting" |
| 16 | Parking, entrance, after-hours access | none (no text until confirmed) | none | none |
| 17 | New Member Orientation ("held regularly; ask us for the next date") | `about.html#acs-path`, one step | none | How it works "Regular training"; About meetings table; Contact table row |

### 1D. Joining and membership

| # | Fact / topic | ONE home | Allowed pointers (why) | Remove from |
|---|---|---|---|---|
| 18 | The join path, steps 0 to 3, short, with one action each | `index.html#join` | header "Join the team" (chrome); the closing line on How it works | About "Joining, step by step" (the whole H2); join mentions on the hub, Documents and Exercises; C's "Your first month after joining" |
| 19 | ARES path in detail: the 2024-11-07 application with its SCEM NDA; hand it in at a meeting or send it to join@ `[verify]`; the ARRL ARES Task Book; ARRL membership not needed to join but needed for Intermediate and up and for EC and AEC appointments | `about.html#ares-path` | `index.html#join` step 1: "How to apply" → `about.html#ares-path` (that section says where to return the form; the Documents row does not). `members/documents.html#ares-application` (library row, no description). | "What the application asks" (CUT, it's in the form); FAQ on ARRL membership; the "Paperwork" row in the ARES/ACS table; the printed join@ address in step 2 (the mailto sits on "email it"; #contact prints it) |
| 20 | ACS path in detail: county Volunteer Emergency Worker Application (FormCenter 276), Sheriff's Office background check, volunteer number, orientation, New Member period of up to a year, then Radio Operator and the ID card | `about.html#acs-path` | `index.html#join` step 3: "A county application and background check" (links to the ACS path). `members/documents.html#county-application` (library row). | FAQ "background check"; How it works "Who calls us" paragraph A; A's quiz question 3; "personal exemptions" quote (CUT); the visible "FormCenter 276" (About and Documents; it is in the URL) |
| 21 | Cost: no dues `[verify]` | `about.html#membership`, one line | none | the membership callout and FAQ "Does it cost anything?" (the FCC $35 fee lives in #licensing only) |
| 22 | Gear to join: members use their own radios, any brand, and you need nothing to visit | `about.html#membership`, one line | none | FAQ "Do I need my own radio?"; Equipment intro paragraph |
| 23 | What it takes: 1 net check-in a month, 1 meeting or workshop a quarter, 1 field operation a year, kits ready | `about.html#acs-task-book` (the requirement rows themselves) | `index.html#join`: one line, "What ACS asks: … plus four free FEMA courses and ARRL Basic EmComm in year one" (newcomers ask this before they join) | About "What it takes, month to month" (the whole H2, and the business-card quote); How it works "Here's what it takes"; the hub's "Staying current" (the whole H2); the 4 tiles on Home |
| 24 | groups.io: apply to join, a moderator approves you; it is the members' hub for files and the calendar | `index.html#join`, one clause in step 2 (inline link on "groups.io list") | footer link; members sub-nav link; the Source cell of the roster and ACSFOG rows | hub "On groups.io" (the whole H2) and the "groups.io Files" tile; About join-steps, FAQ and Contact; the Documents lede and #not-here line; repeated "see groups.io" lines on exercises (keep only on the card that needs it) |

### 1E. Organization, law and partners

| # | Fact / topic | ONE home | Allowed pointers (why) | Remove from |
|---|---|---|---|---|
| 25 | ARES vs ACS: two one-line definitions (sponsor and who can join); AUXCOMM is a qualification, not an organization; ACS = "Auxiliary Communications" `[verify]` | `about.html#ares-acs` (one H2 replacing five: What ARES is, What ACS is, Two hats, AUXCOMM, What we are not) | `how-it-works.html#activation`: "The county activates ACS members; ARES members serve events and the NWS without a call-out." (link "ARES and ACS"; needed to explain who gets called) | About At a glance; FAQ "both?"; A's ARES/ACS figure pair on Home; the ACS name-spellings note (CUT); the ARES/ACS comparison table (CUT: who calls → legal-basis callout; where you serve → HIW#roles; task books → #training-path; ID card → #acs-path step 6); "We are one team…" (the #structure figure says it) |
| 26 | Legal basis: Part 97; 97.407 (RACES); RCW 38.52 and WAC 118-04 (registration is a prerequisite for benefits and protection; coverage tied to a state mission number); county CEMP 2021 (ACS supports ESF-2) `[verify]` | `about.html#legal-basis` (a 4-row table plus the "What this means for you" callout, which is About's one statement that ARES-only activities are not county activations) | `how-it-works.html#activation`, the mission-number step (a link; it's a process step) | the "Legal footing" row in the ARES/ACS table; glossary "Mission number"; How it works "Who calls us" mission paragraph; CEMP extras (the 11 cities, the City of Spokane office): CUT |
| 27 | Served agencies and partners: SCEM (primary; activates ACS), NWS Spokane (SKYWARN), area hospitals (backup communications), event organizers | `about.html#who-we-serve` (a 4-line list; Red Cross and IEVHFRA are in #agreements) | `index.html`: the one-line agency pointer (see #29) | Home "Who we serve" list; affiliation-mark rows; footer ARRL/SCEM line; event names, radio-room and background-check clauses in the partner list. Rows CUT: COAD, WA EMD, neighbouring counties `[verify]`, the CERT note |
| 28 | Agreements: IEVHFRA MOU Rev. 5 (May 6, 2023), which gives a backup repeater and packet paths into Winlink; national ARRL agreements (FEMA 2023, Red Cross 2021, NWS SKYWARN 1986) | `about.html#agreements` (inside #who-we-serve) | none | the Documents MOU row; the who-we-serve Red Cross and IEVHFRA rows; "co-sponsors local license classes"; "War of the Worlds", annual contacts, SATERN/NVOAD/RRI (CUT); the How it works "under a written agreement" clause |
| 29 | How agencies request support: through SCEM; contact ec@ `[verify]` | `about.html#for-agencies` (inside #who-we-serve, 1 sentence plus a mailto) | `index.html`: "Represent an agency? How we work with agencies →" (the agency judge's first-screen need) | Home agency panel or band; FAQ; How it works agency note |
| 30 | Structure: the ARRL chain and the county chain meeting in one team; the EC also serves as RACES/ACS officer `[verify]`; the Sheriff directs Emergency Management | `about.html#leadership`, a diagram with labels only | none | the How it works roles table "Leadership" row |
| 31 | Leadership roles and call signs (unit and Section); PIO open position | `about.html#leadership` table | `members/index.html#rota`: "tell the Net Manager (AG7QP)" (task-specific). Exercises public service: "volunteer through NV2Z" (task-specific). | the hub's "Who to ask" (the whole H2); How it works PIO row |

### 1F. Training, readiness and licensing

| # | Fact / topic | ONE home | Allowed pointers (why) | Remove from |
|---|---|---|---|---|
| 32 | ACS Position Task Book (v2024-08-09): 4 levels, requirements, sign-off by the EC or an AEC | `about.html#acs-task-book` (one compact table: level, then its requirements as a comma list) | members sub-nav "Task books" (this replaces the dead "Training & task books" link); the `members/documents.html#acs-task-book` download row | the hub's "Staying current"; How it works "Training on the way" column; the Documents task-book description |
| 33 | ARRL ARES levels (Basic, Intermediate, Advanced), stated as the same courses as the ACS levels; EC/AEC appointment rules | `about.html#arrl-task-book` (one line); the appointment rules in `#ares-path` only | none | FAQ; the 3-row levels table |
| 34 | Courses: FEMA IS-100.c, 200.c, 700.b and 800.d; the FEMA SID; ARRL EmComm; HIPAA; First Aid/CPR | named in the task-book table; the course **links** and the SID tip go in `members/documents.html#training` (FEMA row) | none | Home "what it takes" footnote; FAQ; How it works "county-arranged classes" |
| 35 | Kits: a personal kit (24 h minimum, 72 h preferred) and a radio kit (2 m/70 cm for 24 to 72 h) | `about.html#equipment` (3 lines inside #training-path; its go-kit guide link goes to `members/documents.html#go-kits`, titled "Go-kit guide" with no hour ranges) | exercises "Bring" line (an event action) | Home tiles; hub "Staying current"; the station "72 hours" and transportation lines from 2013 and 2017 (CUT, dated `[verify]`) |
| 36 | Forms to carry; the ICS-213RR go-kit tip | forms to carry: `members/exercises.html#next-up`, the "Bring" line | none | About Equipment; the ICS-213RR tip (CUT everywhere) |
| 37 | Field guides: AUXFOG, NIFOG, ACSFOG (members only) | `members/documents.html#reference` rows | none | About Equipment; glossary |
| 38 | ALERT Spokane (runs on Regroup) | `members/documents.html#alert-spokane` row | `about.html` "What we are not": "For public alerts, register with ALERT Spokane." (a link; public safety) | the hub "Always" list; the task-book "Code Red" footnote (keep a ≤ 8-word note on the documents row) |
| 39 | Getting licensed: the exam analogy, 3 ways to study, exam sessions table `[verify]`, fees (exam fee plus the FCC's $35), what to bring, under 18 | `about.html#licensing` | `index.html#join` step 0: "Get licensed" (link) | A's Home "Licensing in plain English"; FAQ; A's quiz |
| 40 | SKYWARN training (NWS; no license needed) | the course link: `members/documents.html#skywarn-training` | `about.html#licensing`: "Before you're licensed: listen, visit, or take SKYWARN training." `how-it-works.html#roles`: the SKYWARN row. | Home what-we-do card detail; task-book "E" row detail |

### 1G. Operations

| # | Fact / topic | ONE home | Allowed pointers (why) | Remove from |
|---|---|---|---|---|
| 41 | Activation flow: request, mission number, activation message, check in on W7GBU, assignment or staging, release, 72-hour review | `how-it-works.html#activation` (a 7-step track) | none | About "How activations work" (the whole H2); exercises "After" 72-hour line; the "After the event" paragraph (merged into the steps) |
| 42 | The four readiness levels (explained, never shown as live). Site-wide term: "Readiness levels" (id stays `#alert-levels`) | `how-it-works.html#alert-levels` (4 rows: level, then what members do) | none | About activation list; the Documents "Alert levels explained" row (it only pointed back) |
| 43 | Standing Order 1b: safety first, stay home, report by Winlink (WA Field Situation Report) | `how-it-works.html#standing-order-1b` (2 lines plus the document link) | `members/documents.html#sop-1b` row | About activation; FAQ "Will I be sent somewhere dangerous?"; the HF net and KBARA detail (CUT, it's in the SOP); exercises ShakeOut "same as SOP 1b" line |
| 44 | When it was real: August 2023, Gray and Oregon Road fires, a mobile network unit for the ICP and the Disaster Assistance Center `[verify]` | `how-it-works.html#activation`, one sentence in the intro (id `real-world`, no H3) | none (REVIEW #6 asked to move it to Home; the owner's cut outranks that) | About History 2023 clause; exercises "Past" row |
| 45 | Follow one message (a labelled scenario, 4 steps) `[verify: PIO]` | `how-it-works.html#follow-a-message` | none. (A's Home Fig. 1 is a generic 3-label path, not this scenario; B's Home has no scenario figure; C has none.) | A's Home "Step by step" prose column; B's Home "One message, four volunteers" figure; B's 6-step story plus its separate dawn H2 |
| 46 | Backup paths: alternate repeater, simplex, HF, Winlink | `how-it-works.html#message-handling`, one line under the figure | none | Home figure caption; the settings box "under a written agreement" clause |
| 47 | ICS-213, precedence, logs (ICS-309, ICS-214) | `how-it-works.html#message-handling` | Documents form rows | glossary; A's "Related forms" block (keep it as documents rows) |
| 48 | Winlink: what it is, built-in forms, maps, the W7GBU-10 gateway (NV2Z) | `how-it-works.html#winlink` | `index.html` lede: "radio email" (a phrase only) | Home "Digital messaging" card; hub "Winlink" hints; the ICS-213 and Field Situation Report names in #winlink (named in #ics-213 and #standing-order-1b) |
| 49 | Winlink assignments: the schedule, and how to respond (ICS-213 unless another form is named; send to NV2Z and AG7QP; 5–9 PM; radio preferred, Telnet OK) | `members/exercises.html#winlink-assignments` | `how-it-works.html#other-nets`: the Winlink-nights line links "Winlink assignment" here. Hub `#rota`: the "Winlink night" note links here. | the hub's "Winlink assignments" (the whole H2), This-week Winlink line and tile; the #winlink "practice twice a month" line; "(radio preferred, Telnet OK)" in SET task 1 |
| 50 | Roles members take (8 roles, who each is open to; "All members" for unrestricted rows) | `how-it-works.html#roles` (an 8-row table: role, what you do, open to; no preamble) | none | C's role cards plus "Seven more roles"; A's post cards; B's "Every hop is a post" duplicates; the "Training on the way" column; the PIO and Leadership rows (they're in About#leadership) |
| 51 | Where we work (radio room, trailer, hospitals, shelters, event courses, NWS office, home stations) | **CUT** | none | How it works "Where we work". *Reason:* the roles table already names every place. |

### 1H. Exercises and events

| # | Fact / topic | ONE home | Allowed pointers (why) | Remove from |
|---|---|---|---|---|
| 52 | The yearly rhythm (weekly net; monthly workshop and meeting; October SET, ShakeOut, COMMEX; Field Day and Winter Field Day; SKYWARN Recognition Day; EOC-to-EOC; joint exercises), with **no dates** | `how-it-works.html#exercises` (4 items: monthly, October, with partners, on the air) | none | exercises "The year at a glance" (the whole H2); How it works dated exercise table; C's "A month with the team" calendar; the "Every week" and public-service items |
| 53 | Dated exercises and events: WSDOT (Sep 26–27), SET (Oct 3), ShakeOut (Oct 15, 10:15 AM), SHARES training (Oct 17) `[verify]`, COMMEX (Oct 24), W1AW/7 (Nov 18–25), SKYWARN Recognition Day (Dec 4–5), Winter Field Day (Jan 23–24) | `members/exercises.html#next-up` and `#upcoming` (SKYWARN Recognition Day with no location) | hub `#this-week`: the **next two** (must-fix 12) | Home "Coming up"; A's How it works "Coming up"; C's Home "Training with partners this fall" |
| 54 | SET 2026 tasks (Section request) | `members/exercises.html#set-2026` (a card) | none | hub card 3 text |
| 55 | Public-service events: Bloomsday (May 2, 2027), Torchlight Parade (date not posted), Climb for the Cure (Jun 13, 2027) `[verify]`; volunteer through NV2Z, or NZ2S for Climb | `members/exercises.html#public-service` | `index.html#what-we-do`: the community-events line (a capability, naming Bloomsday). `how-it-works.html#roles`: the public-event operator row (link on the role name). | How it works "Public-service events" (the whole H2) and #exercises item; About who-we-serve event names; the shift tips (CUT; they're in the `public-service-tips` document row) |
| 56 | Past exercises | `members/exercises.html#past` (8 one-off or notable rows; recurring Field Day, SET and SRD rows cut except the 2025 Field Day) | none | About History exercise details (2019 EOC-to-EOC with 20+ operators lives in Past only) |
| 57 | After an exercise: send your logs to the exercise lead; ACS members get the yearly field operation signed off | `members/exercises.html#next-up`, one line | none | the "Before, during and after" H2 (CUT); the wellbeing quote (CUT); the Bring line's log sentence (logs live in HIW#logs) |

### 1I. Member tools

| # | Fact / topic | ONE home | Allowed pointers (why) | Remove from |
|---|---|---|---|---|
| 58 | The document library | `members/documents.html` | members sub-nav; hub quick links (≤ 6); inline links on form names elsewhere (a link on existing words, not a new sentence) | none |
| 59 | "Most used" set | `members/index.html#quick-links` (4 tiles: Net script, ICS-213, ICS-309, ACS Task Book) | a "Most used" filter chip on Documents (no words beyond the chip) | the Documents "Most used" H2 |
| 60 | Search | `members/documents.html#search` | hub search field (a GET form to Documents) | Documents "Popular" chips beyond 4 |
| 61 | What is deliberately not on the site (county, hospital, SHARES and 800 MHz details are FOUO) | `members/documents.html#not-here` ("Not posted here"), 2 sentences | none | the hub radio note; the hub groups.io note; "The roster and ACSFOG are on groups.io" (their rows say it) |
| 62 | "Coming back" presentations (17 items) | **CUT from the list** | `#not-here`: "Older workshop presentations are being recovered." | Documents training, digital and readiness tables |
| 63 | Status key (7 badges) | **CUT** | none | Documents "What the badges mean". *Reason:* each row names its source in plain words ("FEMA ↗", "ag7qp.com ↗", "groups.io, members ↗", "DOCX"). |

### 1J. Contacts, history and extras

| # | Fact / topic | ONE home | Allowed pointers (why) | Remove from |
|---|---|---|---|---|
| 64 | Role addresses: join@, ec@, webmaster@ `[verify]` | `about.html#contact` (3-row table, row labels Joining / Agencies and partners / This website; no preamble) | footer "Contact" link (the one contact line); the ec@ mailto in `#for-agencies` (the agency's action); the join@ mailto on "email it" in `#ares-path` (address not printed) | hub "Who to ask" addresses; Documents "Report a broken link" and Exercises webmaster lines |
| 65 | W7GBU story (Dean Bula, the 7:30 Bunch that still meets weekday mornings, how the group got the call) | `about.html#w7gbu` (inside #history); the current club license in the 2017 timeline entry only; the trustee in the leadership table only | none | Home "Why W7GBU?"; the Documents `w7gbu-story` row; A's footer quote; "W7GBU is our club station" in the Part 97 row; "The group holds that club license today" |
| 66 | History timeline | `about.html#history` (5 entries: 1999, 2004, 2017, 2019, 2026) | none | the SRD "since 1999" note and the "at NWS Spokane" location on exercises, HIW and the who-we-serve list (keep it in History only); the 2021 CEMP entry (it is in #legal-basis) |
| 67 | Mission statement (2004 plan) and "why it matters" quotes | **CUT** | none | About "Our mission"; Home quote band. *Reason:* the Home lede says the same in plain words, and the plan is historical. |
| 68 | Affiliation marks (ARES emblem, ACS seal, SCEM seal) | **CUT** until SCEM gives written permission | none | footer; Home |
| 69 | Glossary | **CUT as a visible section** | its definitions power `<abbr title>` and A's tap-to-define (≤ 25 terms, in JS or attributes) | About "Glossary" H2 |
| 70 | FAQ | **CUT** | none | About "FAQ"; C's "Questions newcomers ask". *Reason:* every answer repeats a section. The two answers that don't (cost, gear) move to #membership (rows 21 and 22). |
| 71 | Quiz, audience routers, "Start where you are" | **CUT** | none | A's Home quiz and four doors; C's audience row; About routers. *Reason:* the 0 to 3 join steps and the nav already route people. |
| 72 | B's "Tell us you're interested" form | **CUT** | none | B's Home. *Reason:* it's a second join mechanism with no handler, and join@ is in #contact. |
| 73 | "Last reviewed / page owner" line | `about.html` under the H1 (≤ 8 words) | none | none |

---

## 2. Simplified IA

### 2.1 Primary nav (every page)

```
[seal] Spokane County ARES-ACS     Home · How it works · About ARES & ACS · For Members     [Join the team]
```

- No change from ia.md §2 except the following. **No utility bar or next-net line** (B's night bar and the "Copy radio settings" in the header are CUT). A's lockup tagline is optional and ≤ 5 words.
- "Join the team" goes to `index.html#join` and is visible at every width.

### 2.2 Footer (minimal, every page, ≤ 35 words)

```
[seal] Spokane County ARES-ACS
How it works · About ARES & ACS · For Members · groups.io ↗ · Contact · Privacy
Volunteers, not first responders. In an emergency, call 911.
© Spokane County ARES-ACS · Mockup — content to be verified before launch
```

| Link | Target |
|---|---|
| How it works | `how-it-works.html` |
| About ARES & ACS | `about.html` |
| For Members | `members/index.html` |
| groups.io ↗ | `https://spokaneares-acs.groups.io/g/main` (external, marked) |
| Contact (the one contact line) | `about.html#contact` |
| Privacy | plain text "Privacy (coming)" until a page exists (no `#`) |

**Cut from the footer:** the audience columns, the address, "Formerly ARES/RACES", the ARRL/SCEM sentence, affiliation marks, and the Join, Visit, Forms, Agreements and Activation links.

### 2.3 Members sub-nav (every `members/*.html` page)

| Item | Target | Why it stays |
|---|---|---|
| This week | `members/index.html` | the switchboard |
| Exercises & events | `members/exercises.html` | exists |
| Documents & forms | `members/documents.html` (`#forms` goes to the Forms category) | exists; the old separate "Forms" item is merged in |
| Task books | `../about.html#training-path` | replaces the dead "Training & task books" (must-fix 11) |
| groups.io ↗ | `https://spokaneares-acs.groups.io/g/main` | the member-only hub |

- **CUT from the sub-nav (all dead `#`):** Nets & net control, Winlink & digital, Repeaters & programming, Go-kits & readiness, Contacts & roles. Their content already has a home: the net goes to How it works#nets and the hub, Winlink to Exercises, repeaters to How it works#nets, go-kits to About#equipment and Documents#readiness, contacts to About#leadership and #contact. Add them back only when the pages are built.
- Five short labels with no hints and no group labels (Operate / Prepare / Reference go). The menu is **always visible**, including at 390px, where it wraps to two lines or scrolls with all 5 visible and no disclosure needed. Desktop layout is the option's choice: A keeps its left rail, B and C use one row.
- B's sidebar next-net readout is CUT, because it repeats the hub.

### 2.4 Anchor map (round 3)

Internal links may target only these ids. A retired id listed below must not be linked. Keep a retired id as an alias on its new target only when the "Alias" column says so.

| Page | Ids (H2 ids in **bold**) | Retired ids → new target (Alias?) |
|---|---|---|
| `index.html` | `#main`, **`#what-we-do`**, **`#visit`**, **`#join`** | `#this-week` → `#visit` (alias); `#how-a-message-gets-through` → `#what-we-do`; `#what-it-takes` → `#join`; `#who-we-serve`, `#agencies` → `about.html#who-we-serve`; `#quiz`, `#w7gbu`, `#members` gone |
| `how-it-works.html` | **`#nets`** (`#weekly-net`, `#other-nets`), **`#activation`** (`#who-calls-us`, `#real-world` on the intro sentence, `#alert-levels`, `#standing-order-1b`), **`#message-handling`** (`#follow-a-message`, `#ics-213` on the "Forms and logs" H3, `#logs` on its list, `#winlink`), **`#exercises`**, **`#roles`** | `#the-system` → `#message-handling`; `#public-service` → `members/exercises.html#public-service`; `#where-we-work` gone |
| `about.html` | **`#ares-acs`** (aliases `#what-is-ares`, `#what-is-acs`, `#two-hats`, `#what-we-are-not`), **`#legal-basis`**, **`#who-we-serve`** (`#for-agencies`, `#agreements`), **`#leadership`** (`#structure`), **`#membership`** (`#ares-path`, `#acs-path`), **`#training-path`** (`#acs-task-book`, `#arrl-task-book`, `#equipment`), **`#licensing`**, **`#history`** (`#w7gbu`), **`#contact`** | `#at-a-glance`, `#mission`, `#faq`, `#glossary` gone; `#auxcomm` → `#ares-acs`; `#join-steps` → `index.html#join`; `#commitment` → `#acs-task-book`; `#activation` → `how-it-works.html#activation`; `#where-we-meet` → `index.html#visit` |
| `members/index.html` | `#search`, **`#this-week`**, **`#rota`** (the "Tuesday net" H2), **`#quick-links`** | `#winlink` → `exercises.html#winlink-assignments`; `#requirements` → `../about.html#acs-task-book`; `#sections`, `#groups-io`, `#help` gone |
| `members/documents.html` | `#search`, **`#net-ops`**, **`#join`**, **`#forms`**, **`#training`**, **`#readiness`**, **`#digital`**, **`#reference`**, one id per document row (round-3 data.js ids), **`#not-here`** | `#most-used` → filter chip; `#net-preamble-weekly`, `-simplex`, `-gmrs` → `#net-scripts`; `#alert-levels` → `../how-it-works.html#alert-levels` (row cut); `#history`, `#status-key` gone |
| `members/exercises.html` | **`#next-up`** (`#wsdot-2026`, `#set-2026`), **`#upcoming`** (`#shakeout-2026` on its row), **`#winlink-assignments`**, **`#public-service`**, **`#past`** | `#year-at-a-glance`, `#after-action` gone (after-action is one line in #next-up) |

### 2.5 Site-wide must-fixes carried into round 3

1. **Deep links land exactly on a cold load.** Port A's `hashLanding()` (`option-a-field-guide/assets/site.js` lines 755–771) to B and C. Set `scroll-margin-top` on every `[id]` to the sticky header height, plus the sub-nav height on members pages. Reserve space for data.js widgets with `min-height` so content above a target doesn't grow after landing. Test `about.html#for-agencies`, `#contact`, `how-it-works.html#activation` and `members/documents.html#forms` from another page.
2. Use 12-hour times everywhere ("8:00 PM"); see rule 0.7.
3. No `#` links (rule 0.8). The sub-nav "Task books" goes to About.
4. The hub shows the **next two** events (WSDOT now and SET Oct 3 on 2026-09-26), plus the next meeting.
5. Home is newcomer-first: no net control, offset, tone, simplex note, rota or member links in Home's `<main>`.
6. In the rota, every other place shows the call sign only (rule 0.9).

---

## 3. Page specs

Word budgets are per page; the "Words" column is the **measured** count of the edited `copy-*.md` (option A unless noted), headings included. "CTA" means a button or button-weight link in `<main>`; inline links on existing words are not CTAs. The copy files are the source of truth for wording; this section says what each page holds and why.

### 3.1 Home: `index.html` (budget 400; measured A 272 · B 245 · C 243)

- **Purpose:** in 10 seconds, tell a newcomer what the team does and give them one easy way to try it and one clear way to join.
- **Primary action:** **Join the team** → `#join`. **Secondary:** A and B **See how it works** → `how-it-works.html`; C **Plan your first visit** → `#visit`, and C keeps #visit below #what-we-do so the button has a job (if C moves #visit under the hero, C uses "See how it works").
- **Height:** ≤ 3,200px on desktop.

| Order | Section (H2) | id | Content | Words |
|---|---|---|---|---:|
| 1 | *Hero (H1, not H2)* | – | The option's H1. Lede: licensed volunteers who back up SCEM's communications; when phones, power or the internet fail, we pass messages by voice and radio email (the radio room is named once, in #what-we-do). Two buttons. Agency line: "Represent an agency? How we work with agencies →". A only: Fig. 1, 3 labels plus caption. | A 81 · B/C 54 |
| 2 | What we do | `#what-we-do` | Four one-liners: county radio room and trailer; hospitals; SKYWARN; community events (Bloomsday). C: photo cards. B: the four stops on its red line, **no scenario figure** (the scenario lives on How it works only). | 39 |
| 3 | Come visit | `#visit` | From home: listen Tuesdays 8:00 PM, 147.300 MHz, no license. In person at SCEM, 1121 W Gardner Ave: workshop (+ Winlink workshop `[verify]`), next Oct 10 (data); Third Thursday meeting, evenings `[verify]`, next Oct 15 (data). | 58 |
| 4 | Join in four steps | `#join` | 0 Get licensed → About#licensing. 1 Join ARES, one form → **How to apply** (About#ares-path, which says where to return the form). 2 Get connected: inline links on "Tuesday net" (→ HIW#nets) and "groups.io list"; a moderator approves you. 3 Qualify for ACS (optional) → The ACS path. "What ACS asks: … plus four free FEMA courses and ARRL Basic EmComm in year one." | 94 |

- **CTAs (6):** Join; the secondary; How we work with agencies; How to get licensed; How to apply; The ACS path.
- **CUT from Home:** This-week card with net control, offset, tone or simplex; Coming up; the quiz; audience and door routers; "Here's what it takes" tiles; licensing block; W7GBU; who-we-serve list and affiliation marks; the agency panel or band; the "Already a member?" strip; the final CTA band; the why-it-matters quote; B's interest form and B's "one message" figure; C's "Training with partners"; the scope line (the footer carries it); "Open to any licensed ham" (step 0 says it); "so the county can call on you".

### 3.2 How it works: `how-it-works.html` (budget 1,200; measured A/C 851 · B 849)

- **Purpose:** explain once, plainly, how the team operates.
- **Primary action:** **Join the team** → `index.html#join`, as one closing line.

| Order | Section (H2) | id | Content | Words |
|---|---|---|---|---:|
| – | *H1 plus lede* | – | A/C: one-sentence lede. B: the story replaces the lede (opener, the 4-step "Follow one message" figure, one transition line). | A 19 · B 128 |
| 1 | The weekly net | `#nets` | Directed net in one sentence. Settings box (8:00 PM stated here only): W7GBU; "Alternate:" 146.880 `[verify]`. 6 steps (no time in step 1). Visitor cue ending "Not licensed? Just listen." `#other-nets`: 3 lines; the Winlink-nights line carries the only link to the assignments. | 154 |
| 2 | When the county calls | `#activation` | Who calls us, one line + link "ARES and ACS". The August 2023 sentence (id `real-world`, no H3). 7-step track. `#alert-levels` H3 "Readiness levels": 4 rows, "This site never shows a current level." `#standing-order-1b`: 2 lines + link. | 255 |
| 3 | How a message moves | `#message-handling` | A/C: `#follow-a-message` H3, pill + 4 steps. Backups line. H3 "Forms and logs" (`#ics-213`): ICS-213, precedence, `#logs` list. `#winlink`: 3 sentences (state and USGS forms built in; W7GBU-10 gateway), no assignments line. | A 242 · B 131 |
| 4 | Training and exercises | `#exercises` | 4 items with no dates (monthly; October; with partners; on the air, SRD with no location) + link to Exercises & events. | 52 |
| 5 | Roles members take | `#roles` | 8-row table, no preamble. Net control cell carries "in turn; the Net Manager coaches first-timers". "All members" for unrestricted rows. Public-event operator links to Exercises#public-service. Closing "Ready? Join the team". | 129 |

- **CUT (in addition to round-3 plan):** "Members take turns…" as its own line; the #winlink practice line; "When it was real" H3; separate "Logs" H3; "Every week" and public-service items in #exercises; roles preamble.

### 3.3 About ARES & ACS: `about.html` (budget 2,800; measured 1,181)

- **Purpose:** the reference for people who want the details, each topic said once, in lists and tables.
- **Primary action:** **Get the ARES application** (in `#ares-path`). Agencies: the ec@ mailto in `#for-agencies`.
- **TOC:** 9 entries, sticky side column at ≥ 1024px, closed `<details>` on phones.

| # | Section (H2) | id | Content | Words |
|---|---|---|---|---:|
| – | *H1, lede, reviewed line, TOC* | – | One-sentence lede; "Last reviewed …, page owner" `[verify]`; 9-entry TOC | 42 |
| 1 | ARES and ACS | `#ares-acs` | 4 one-line definitions: ARES (ARRL; any licensed ham), ACS (SCEM's unit; ARES members who pass the background check), RACES (FCC rule 97.407; the county program is now part of ACS, hence "ARES/RACES"), AUXCOMM. "What we are not" callout, 4 bullets with the ALERT Spokane link. **No comparison table.** | 119 |
| 2 | The legal basis | `#legal-basis` | 4-row table (97.407 row: "…one hour a week (more with approval)"; State row: "Emergency-worker registration and coverage"). Callout: protection needs registration + mission number; ARES-only activities are not county activations. All `[verify]`. | 98 |
| 3 | Who we serve | `#who-we-serve` | 4-line partner list (SCEM primary, activates ACS; NWS, SKYWARN; hospitals, backup communications; event organizers). `#agreements`: IEVHFRA MOU (named here only) and national ARRL agreements incl. Red Cross. `#for-agencies`: one sentence + button. | 80 |
| 4 | Organization and leadership | `#leadership` | `#structure` figure, labels only, and caption. 7 unit rows (PIO open) and 3 Section rows. | 107 |
| 5 | Joining | `#membership` | No dues `[verify]`; own radios. `#ares-path`: 3 steps (mailto on "email it"), ARRL-membership and EC/AEC appointment rules (stated here only). `#acs-path`: 6 steps, no "FormCenter 276". | 145 |
| 6 | Training and task books | `#training-path` | Sign-off rule. `#acs-task-book` 4-row table. "FEMA courses are free online: course links" (the SID tip is on Documents). `#arrl-task-book`: one line mapping ARRL levels to the same courses + PDF link. `#equipment`: 2 kit lines + link to the Documents go-kit row. | 240 |
| 7 | Getting licensed | `#licensing` | Analogy; 3 ways to study; exam table `[verify]`; FCC fee, bring, under 18, before you're licensed. | 175 |
| 8 | History and W7GBU | `#history` | `#w7gbu` story (the 7:30 Bunch "which still meets weekday mornings"; no "holds that license today") and one quote. Timeline, 5 entries (1999 SRD at NWS Spokane; 2004; 2017 club license + workshops; 2019 ACS; 2026). | 158 |
| 9 | Contact | `#contact` | 3-row table: Joining / Agencies and partners / This website `[verify]`. "In person: see Come visit." | 17 |

- **CUT (in addition to round-3 plan):** the ARES/ACS comparison table and "We are one team…"; "W7GBU is our club station"; the partner table's Red Cross and IEVHFRA rows, event names and radio-room/background-check clauses; "co-sponsors local license classes"; "Partners can reach the Emergency Coordinator directly"; the printed join@ in #ares-path; the ARRL levels table; the SID tip; the 2021 timeline entry; the Contact preamble, "and orientation dates" and the groups.io bullet.

### 3.4 For Members hub: `members/index.html` (budget 300; measured 104)

- **Purpose:** a switchboard: this week at a glance, the net rota, and the top documents one tap away.
- **Primary action:** **Search documents**. The sub-nav supplies the section links.

| Order | Section | id | Content (no-JS fallback as of Sat, Sep 26, 2026) | Words |
|---|---|---|---|---:|
| – | *H1, as-of line, search* | `#search` | "For members" · "As of Sat, Sep 26" · search field and button | 12 |
| 1 | This week (H2) | `#this-week` | **Exercises (next two):** WSDOT now through Sun, Sep 27; SET Sat, Oct 3 → "What to bring". **Next meeting:** Sat, Oct 10, 9:00 AM, Second Saturday Workshop. | 30 |
| 2 | Tuesday net (H2) | `#rota` | Copyable line "8:00 PM · W7GBU 147.300 MHz, +600 kHz, 100 Hz". 5-row rota (Tuesday, Net control, Note), first row highlighted as the next net: Sep 29 NZ2S Simplex; Oct 6 Open; Oct 13 AE7RJ Winlink night (link); Oct 20 WA7LNC GMRS net, 7:30 PM; Oct 27 Open, Winlink night (link). "Open slot? Tell the Net Manager (AG7QP) on the net or on groups.io." | 53 |
| 3 | Most used (H2) | `#quick-links` | 4 tiles: Net script · ICS-213 · ICS-309 · ACS Task Book | 9 |

- **CUT (in addition to round-3 plan):** the separate "Next net" line (its date, net control and variant repeated rota row 1); the This-week Winlink line (the rota note links to assignments); the Winlink-assignments and groups.io Files tiles; the next-net variant phrases.

### 3.5 Documents & forms: `members/documents.html` (budget 900; measured 441)

- **Purpose:** the library is the page.
- **Primary action:** **Search or filter, then open the document.**
- **Row format:** Title (link) · format and version · source ("FEMA ↗", "ag7qp.com ↗", "groups.io, members ↗") · optional description ≤ 8 words. "Soon" marks a row with no target. Round-3 `data.js` holds exactly these 37 rows (round-2 `data.js` keeps the full archive).

| Section | id | Rows (round-3 data.js ids) | Words |
|---|---|---|---:|
| *H1, search, chips* | `#search` | H1; search field; chips: All, Most used, 7 categories. **No lede.** | 21 |
| Net operations | `#net-ops` | `net-scripts` (one row, one link, `[verify]`; launch blocker D14 #1), `ncs-principles` (Soon), `repeater-etiquette` (Soon), `net-roster` (groups.io) | 38 |
| Join & task books | `#join` | `ares-application`, `county-application` (format "Online form"), `acs-task-book`, `arrl-ares-task-book`, `arrl-ares-plan`, `orientation-guide` | 56 |
| Forms | `#forms` | `ics-213` (+ how-to link), `ics-213rr`, `ics-214`, `ics-309` (Soon + video), `arrl-radiogram`, `wa-ics-213rr`, `wa-field-situation-report`, `wsdot-580-020`, `fema-ics-forms` | 92 |
| Training | `#training` | `fema-is-courses` (4 course links + the SID tip, its one home), `arrl-basic-emcomm`, `arrl-intermediate-emcomm`, `hipaa-video`, `skywarn-training`, `ratpac`, `anderson-powerpoles` | 90 |
| Go-kits & readiness | `#readiness` | `go-kits` ("Go-kit guide", no hour ranges), `alert-spokane`, `programming-radios` | 27 |
| Winlink & digital | `#digital` | `winlink-intro`, `winlink-express`, `winlink-mapping`, `i-am-safe` | 45 |
| Reference | `#reference` | `sop-1b`, `field-guides`, `acsfog` (groups.io), `public-service-tips` | 52 |
| Not posted here | `#not-here` | "County, hospital, SHARES and 800 MHz details are never posted here. Older workshop presentations are being recovered." | 20 |

- **CUT (in addition to round-3 plan):** the lede; "The roster and ACSFOG are on groups.io"; the webmaster "Report a broken link" mailto (map #64: the footer's Contact link reaches About#contact); the `alert-levels` row; separate script rows; "(5th Tuesdays)"; "FormCenter 276"; hour ranges in the go-kit title.

### 3.6 Exercises & events: `members/exercises.html` (budget 700; measured 387)

- **Purpose:** what's next and what to bring, then the season, the Winlink schedule, public-service sign-ups and a short past list.
- **Primary action:** **Get ready for the next exercise** (on 2026-09-26, the WSDOT card's "Exercise details on groups.io ↗" `[verify]`).

| Order | Section (H2) | id | Content | Words |
|---|---|---|---|---:|
| – | *H1* | – | "Exercises & events" (no lede) | 2 |
| 1 | Next up | `#next-up` | **Bring to every exercise** (1 line; no log sentence). **WSDOT card** (`<abbr>` in the H3, no spelled-out sentence): dates and status, extra form, groups.io button. **SET card:** Sat, Oct 3; 5 Section tasks (task 1 without "radio preferred"); ARRL SET forms link. **After** (1 line). | 135 |
| 2 | Later this season | `#upcoming` | 6 rows: ShakeOut (the one "marked as an exercise"), SHARES training, "State COMMEX", W1AW/7, SKYWARN Recognition Day (no location), Winter Field Day. | 57 |
| 3 | Winlink assignments | `#winlink-assignments` | How to respond (12-hour times), 6-row table; Oct 13 reads "Did You Feel It report (ShakeOut practice)". | 98 |
| 4 | Public-service events | `#public-service` | 4-row table + first-event tips link. | 46 |
| 5 | Past exercises | `#past` | 8 rows: 2025 Field Day; Oct 2022 Rockford; Dec 2019 Hazard City; Oct 2019 all-members; Jul 2019 Kootenai; Mar 2019 EOC-to-EOC (20+ operators); Mar 2018 multi-county; Aug 2013 EOC-to-EOC. | 49 |

- **CUT (in addition to round-3 plan):** the Bring line's log sentence; the WSDOT spelled-out sentence; SRD location; "Washington's statewide exercise"; Past rows Jun 2018 and 2012–13 Field Day, Oct 2021 and Oct 2004 SET, Dec 2017 SRD.

---

## 4. Roll-up (measured, edited copy)

| Page | Budget | Measured (A / B / C) | H2s | Primary action |
|---|---:|---:|---:|---|
| Home | 400 | 272 / 245 / 243 | 3 | Join the team |
| How it works | 1,200 | 851 / 849 / 851 | 5 | Join the team (closing line) |
| About ARES & ACS | 2,800 | 1,181 | 9 | Get the ARES application |
| Members hub | 300 | 104 | 3 | Search documents |
| Documents & forms | 900 | 441 | 7 + not-here | Search or filter, then open |
| Exercises & events | 700 | 387 | 5 | Get ready for the next exercise |
| **Site** | 6,300 | **3,236 / 3,207 / 3,207** (≈ 18% of round 2) | | |

Builders add each option's own voice within the remaining budget, but not facts: a fact that is not in `copy-*.md` does not go on the page.

**Checks before hand-off (per option):** visible words in `<main>` per page ≤ budget; key facts (repeater settings, net time, join steps, county application, ICS-213, task book, WSDOT, groups.io) stated in full on exactly one page each, with pointers only where §1 allows them; zero `href="#"`; no "2000"-style times; cold-load landing within 4px for every id in §2.4; Home ≤ 3,200px on desktop with ≤ 6 CTAs in `<main>`; the footer line reads "Volunteers, not first responders. In an emergency, call 911."
