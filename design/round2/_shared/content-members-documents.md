# For Members: Documents & downloads (`members/documents.html`): draft copy

- **Job of the page:** the whole public library in one place, searchable and filterable, so a member finds the right, **current** script or form in seconds. No login and no e-mail gate (downloads were never gated; `documents-inventory.md` §2 item 6).
- **Data:** every entry comes from `SPOKARES.documents` in `assets/data.js` (74 entries in 8 categories). The tables below are generated from that file and are the no-JS fallback. **Do not edit the entries here; edit `data.js`.**
- **Principle (from decisions.md D10):** standard FEMA, ICS, ARRL and state forms **link to the official source**, so members always get the current version. The group's own documents (net scripts, application, ACS task book) are hosted here. Section-level guides stay on ag7qp.com and are linked.
- **Members menu:** as on every `members/*.html` page (`ia.md` §4). "Forms" in that menu points to `documents.html#forms` and should pre-select the Forms filter.
- **Conventions:** `[verify]` = `data-verify="true"` on the row. ★ = in the "Most used" set (`mostUsed: true`).

---

## 0. Head and intro

- **`<title>`:** Documents & downloads | Spokane County ARES-ACS
- **Meta description:** Net scripts, applications, task books, ICS and ARRL forms, go-kit guides and training material for Spokane County ARES-ACS.

**Breadcrumb:** For Members › Documents & downloads

**H1:** Documents & downloads

**Lede:**
> Scripts, forms, task books and guides. Official forms open at their source, so you always get the current version. Member-only files are on groups.io.

---

## 1. Search and filters (`#search`)

**Search field**
- Label: **Search the library**
- Placeholder: Try "preamble", "213", "task book", "Winlink"
- Works without JS as `?q=` (the page reads the query and filters on load when JS is available; without JS it shows everything, grouped by category, and the browser's find still works).
- Live result count, announced politely: "12 documents match 'winlink'." (`aria-live="polite"`)
- Empty state: "Nothing matches '{q}'. Try a shorter word, or look on groups.io for member-only files." with links to **Clear search** and **groups.io files**.

**Filters** (chips or a segmented control; each is a real link or checkbox so it works as progressive enhancement):
- **Category:** All · Net operations · Join & qualify · Forms · Training & presentations · Go-kits & readiness · Winlink & digital · Reference & SOPs · History & archive
- **Show:** Available now (hides "Coming back") · Everything

**Sort (optional):** Category (default) · A–Z · Newest version first

**Deep links:** `documents.html#forms` selects the Forms category; `documents.html?q=ics` pre-fills the search; `documents.html#ics-213` scrolls to and highlights one entry.

---

## 2. Most used (`#most-used`)

**H2:** Most used

Eight large tiles (from `fn.documents({mostUsed: true})`), in this order:

1. **Weekly net preamble and script** · DOCX · 2026-03-04
2. **Spokane County ARES Membership Application** · DOCX · 2024-11-07
3. **ACS Position Task Book** · DOCX · 2024-08-09
4. **ICS 213 General Message** · FEMA · v3
5. **ICS 309 Communications Log** · generate it in Winlink Express
6. **ARRL Radiogram** · ARRL PDF
7. **Standing Order 1b** · EWA Section · 2026-01-16
8. **ACSFOG** · groups.io (members only)

---

## 3. Entry anatomy (every row or card)

Each document shows, in this order:
1. **Title** (a link when there's a target; plain text when "Coming back").
2. **One-line description.**
3. **Meta line:** format · version or date · author call sign (if any).
4. **Status badge** (text, not colour alone): see §5.
5. **Where it opens:** "Opens FEMA" / "Opens ag7qp.com" / "Opens groups.io (members only)" for anything off-site; a download icon plus format for files hosted here.
6. Optional **how-to link** (e.g. "How to write an ICS-213") and a **note** (e.g. "The 2024 text still says Code Red").

Accessible names: a download link reads "Download the weekly net preamble and script (DOCX, version 2026-03-04)". External links are identified as such in text.

---

## 4. The library

**H2 per category** (ids below). The intro line under each H2 is the category blurb.

### Net operations (`#net-ops`)

*Scripts and guides for the Tuesday net and its variants.*

| Title (`id`) | Description | Format | Version / date | Status | Link / source |
|---|---|---|---|---|---|
| **Weekly net preamble and script** (`net-preamble-weekly`) `[verify]` ★ | What net control reads each Tuesday, with pauses for cross-band stations and the Winlink-night inserts. The public copy will send contact changes and net reports to a role address instead of a personal e-mail. | DOCX | 2026-03-04 | Available | Hosted here (`#` in mockup); interim: Current copy on ag7qp.com (interim) |
| **Simplex net preamble and script (5th Tuesdays)** (`net-preamble-simplex`) `[verify]` | Five call-sign groups, read-backs of stations heard, then the move to the repeater. | DOCX | 2026-03-04 | Available | Hosted here (`#` in mockup); interim: Current copy on ag7qp.com (interim) |
| **ACS GMRS net preamble and script** (`net-preamble-gmrs`) `[verify]` | The script for the 3rd-Tuesday GMRS net. | DOCX | 2025-08-19 | Available | Hosted here (`#` in mockup); interim: Current copy on ag7qp.com (interim) |
| **Principles of a Net Control Station** (`ncs-principles`) | Keep track of stations, handle the highest precedence first, and what to do when a traffic net bogs down. By K7BFL. | Web page | K7BFL, 2000; posted 2017 | Web page | Hosted here (`#` in mockup) |
| **Repeater etiquette on W7GBU** (`repeater-etiquette`) `[verify]` | How to identify, break in and keep the emergency repeater open and orderly. | Web page | 2017; trustee corrected | Web page | Hosted here (`#` in mockup) |
| **Net roster** (`net-roster`) | The member roster net control uses. Members only; never posted on this site. | groups.io | — | groups.io | Link → https://spokaneares-acs.groups.io/g/main/files |

### Join & qualify (`#join`)

*Applications, task books and orientation.*

| Title (`id`) | Description | Format | Version / date | Status | Link / source |
|---|---|---|---|---|---|
| **Spokane County ARES Membership Application** (`ares-application`) `[verify]` ★ | The ARES application. It includes the Spokane County Emergency Management non-disclosure agreement. A fillable PDF is planned. Bring it to a meeting or send it to the membership role address. | DOCX | 2024-11-07 | Available | Hosted here (`#` in mockup); interim: Current copy on ag7qp.com (interim) |
| **Volunteer Emergency Worker Application (for ACS)** (`county-application`) | The county application for ACS. It starts the Sheriff’s Office background check. | Online form | Spokane County FormCenter 276 | Official source | Spokane County → https://www.spokanecounty.gov/FormCenter/Emergency-Management-31/Volunteer-Emergency-Worker-Application-276 |
| **ACS Position Task Book** (`acs-task-book`) `[verify]` ★ | New Member, Radio Operator (RADO), Senior Radio Operator (S-RADO) and Leadership requirements. Print it and keep it in a binder with your sign-offs. The 2024 text still calls ALERT Spokane “Code Red”; it moved to Regroup in April 2026. | DOCX | 2024-08-09 | Available | Hosted here (`#` in mockup); interim: Current copy and guide on ag7qp.com (interim) |
| **ARRL ARES Position Task Book** (`arrl-ares-task-book`) | The national ARES task book: Basic, Intermediate and Advanced levels. | PDF (fillable) / DOC | July 2024 | Official source | ARRL → https://www.arrl.org/files/file/ARES%20Taskbook%20July%202024.pdf |
| **ARRL ARES Plan** (`arrl-ares-plan`) `[verify]` | The national ARES program plan: levels, training and appointments. | Web page | July 2025 | Official source | ARRL → https://www.arrl.org/ares |
| **New Member Orientation guide** (`orientation-guide`) | The orientation class in writing: the two organizations, qualifying, field guides and hands-on training. | Web page | 2026 | Section resource | ag7qp.com → https://ag7qp.com/new-members-orientation |
| **ARES Task Book helps (Section guide)** (`ares-task-book-helps`) | The Section Emergency Coordinator’s walk-through of the ARRL task book. That page links an older (2024-09-15) application; use the 2024-11-07 version. | Web page | 2026 | Section resource | ag7qp.com → https://ag7qp.com/ares-task-book |
| **FEMA Student ID (SID) sign-up** (`fema-sid`) | You need a SID before you can take the FEMA IS course exams. Save it. | Online form | — | Official source | DHS / FEMA → https://cdp.dhs.gov/femasid |

### Forms (`#forms`)

*ICS, state and ARRL forms, always from the official source.*

| Title (`id`) | Description | Format | Version / date | Status | Link / source |
|---|---|---|---|---|---|
| **ICS 213 General Message** (`ics-213`) ★ | The standard message form. Also built into Winlink Express. How-to: How to write an ICS-213. | PDF | v3 | Official source | FEMA ICS forms → https://training.fema.gov/emiweb/is/icsresource/icsforms/ |
| **ICS 213RR Resource Request Message** (`ics-213rr`) | For requesting resources. Tip: keep one filled out that lists your go-kit. | PDF | v3 | Official source | FEMA ICS forms → https://training.fema.gov/emiweb/is/icsresource/icsforms/ |
| **ICS 214 Activity Log** (`ics-214`) | Keep one during every exercise and activation (required at RADO). | PDF | v3.1 | Official source | FEMA ICS forms → https://training.fema.gov/emiweb/is/icsresource/icsforms/ |
| **ICS 214a Individual Log** (`ics-214a`) `[verify]` | A personal activity log. Keep copies in your go-kit. | PDF | — | Official source | Authoritative source being chosen |
| **ICS 309 Communications Log** (`ics-309`) `[verify]` ★ | The radio operator’s message log, kept during all ACS radio operations. Winlink Express can generate it for you. How-to: Generate an ICS-309 in Winlink Express (video). | PDF | — | Official source | Authoritative source being chosen |
| **ICS 205 Incident Radio Communications Plan** (`ics-205`) | The blank form. Filled-in plans for real incidents are never posted publicly. | PDF | v3.1 | Official source | FEMA ICS forms → https://training.fema.gov/emiweb/is/icsresource/icsforms/ |
| **ICS 201 Incident Briefing** (`ics-201`) | The initial incident briefing form. | PDF | v3 | Official source | FEMA ICS forms → https://training.fema.gov/emiweb/is/icsresource/icsforms/ |
| **ARRL Radiogram (fillable)** (`arrl-radiogram`) ★ | The National Traffic System message form. | PDF | — | Official source | ARRL → https://www.arrl.org/files/media/Group/Fillable%20Radiogram%20Form.pdf |
| **Washington State ICS-213RR** (`wa-ics-213rr`) `[verify]` | The state resource-request form used in state exercises. | PDF | — | Official source | Washington Military Department → https://mil.wa.gov/asset/5ba420f505d91 |
| **Washington State Field Situation Report** (`wa-field-situation-report`) | A snapshot of phones, power, water, broadcast and internet at your location. In Winlink Express under Templates, Standard Forms, WA State Forms. There is no official PDF. | Winlink form | — | Official source | Winlink → https://winlink.org/ |
| **Washington State ISNAP (Incident Snapshot)** (`wa-isnap`) | Incident snapshot for counties and tribal nations. In Winlink Express; no official PDF. | Winlink form | — | Official source | Winlink → https://winlink.org/ |
| **WSDOT Form 580-020, Road and Bridge Assessment** (`wsdot-580-020`) | Washington State Department of Transportation road and bridge assessment form. | PDF | — | Official source | WSDOT → https://wsdot.wa.gov/publications/fulltext/forms/580-020.pdf |
| **All FEMA ICS forms** (`fema-ics-forms`) | FEMA’s index of every ICS form, always the current versions. | Web page | — | Official source | FEMA → https://training.fema.gov/emiweb/is/icsresource/icsforms/ |

### Training & presentations (`#training`)

*Courses, study aids and workshop decks.*

| Title (`id`) | Description | Format | Version / date | Status | Link / source |
|---|---|---|---|---|---|
| **FEMA IS-100.c: Introduction to the Incident Command System** (`is-100`) | Free online. Required once for new members. | Online course | IS-100.c | Official source | FEMA EMI → https://training.fema.gov/is/courseoverview.aspx?code=IS-100.c&lang=en |
| **FEMA IS-200.c: Basic ICS for Initial Response** (`is-200`) | Free online. Required once for new members. | Online course | IS-200.c | Official source | FEMA EMI → https://training.fema.gov/is/courseoverview.aspx?code=IS-200.c&lang=en |
| **FEMA IS-700.b: An Introduction to NIMS** (`is-700`) | Free online. Required once for new members. | Online course | IS-700.b | Official source | FEMA EMI → https://training.fema.gov/is/courseoverview.aspx?code=IS-700.b&lang=en |
| **FEMA IS-800.d: National Response Framework, An Introduction** (`is-800`) | Free online. Required once for new members. | Online course | IS-800.d | Official source | FEMA EMI → https://training.fema.gov/is/courseoverview.aspx?code=IS-800.d&lang=en |
| **ARRL Basic EmComm course** (`arrl-basic-emcomm`) | Three modules, about 10 to 20 hours. Required once for new members. | Online course | — | Official source | ARRL Learning Center → https://learn.arrl.org/courses/67044 |
| **ARRL Intermediate EmComm course** (`arrl-intermediate-emcomm`) | Required once at RADO. 35-question final; 74% to pass. | Online course | — | Official source | ARRL Learning Center → https://learn.arrl.org/courses/68230 |
| **FEMA course study helps** (`fema-study-helps`) | Tips for passing the FEMA IS exams, including how to get extra time. | Web page | — | Section resource | ag7qp.com → https://ag7qp.com/fema-course-study-helps |
| **HIPAA training video (12 minutes)** (`hipaa-video`) `[verify]` | Covers the HIPAA requirement if you miss it at orientation. | Video | — | Official source | YouTube (linked from the ACS Task Book) → https://youtu.be/9ryH0P5MCdk |
| **SKYWARN spotter training** (`skywarn-training`) | NWS Spokane spotter classes, in person or online. Encouraged every two years. | Web page | — | Official source | NWS Spokane → https://www.weather.gov/otx/Current_Spotter_Training |
| **RATPAC video presentations** (`ratpac`) | A library of recorded emergency-communications presentations. | Web page | — | Official source | RATPAC → https://docs.google.com/spreadsheets/d/e/2PACX-1vTVywG1s025_zsAaKrn8Lr_DPGT6tGV0SVsV5LEdBA91dFCYk_LP8S3kXhMcD9vXFM3Cg-q468jf6Nd/pubhtml |
| **A guide to Anderson Powerpoles** (`anderson-powerpoles`) | Make your own DC power connections. | PDF | — | Official source | ARRL → https://www.arrl.org/files/file/Public%20Service/TrainingModules/Technical/Anderson%20powerpole.pdf |
| **Maintaining and Improving Safety on ARES Deployments** (`deck-safety-deployments`) | Presented January 19, 2023. By N7LL. | Presentation | 2023-01-19 | Coming back | No link yet |
| **Five knots you should know** (`deck-five-knots`) | A presentation from the old library (no description was published). | Presentation | — | Coming back | No link yet |
| **Initial Damage Assessment and the Winlink IDA form** (`deck-ida`) | How Spokane County ARES-ACS will react to and use the Initial Damage Assessment Winlink form. To be checked against the current state form before it returns. | Presentation | — | Coming back | No link yet |
| **Radio go-kit presentation** (`deck-radio-go-kit`) | A presentation on radio go-kits. By N7GCO. | Presentation | — | Coming back | No link yet |
| **Emergency modules** (`deck-emergency-modules`) | A comprehensive approach to preparing for emergencies. The author gave permission to host it. By N7GCO. | Presentation | — | Coming back | No link yet |
| **How to power your radio when the lights go out** (`deck-power`) | Battery technologies and solar power for your station. By WA7AQH. | Presentation | — | Coming back | No link yet |
| **Intro to packet communications 101** (`deck-packet-101`) | An introduction to packet radio. By K7PKT. | Presentation | — | Coming back | No link yet |
| **Cross-band repeaters** (`deck-cross-band`) | Theory, use cases and setup. By AD7DD. | Presentation | 2017 | Coming back | No link yet |
| **Go-kits (quick-reaction packs)** (`deck-go-kits-2017`) | Go-kits, quick-reaction packs and bug-out bags. By AD7DD. | Presentation | 2017 | Coming back | No link yet |
| **Traffic Handler (mentor program, PDF and MP3)** (`traffic-handler`) `[verify]` | Traffic-handling training material from the old mentor program. | PDF + MP3 | — | Coming back | No link yet |

### Go-kits & readiness (`#readiness`)

*Kit lists, alert levels and alert registration.*

| Title (`id`) | Description | Format | Version / date | Status | Link / source |
|---|---|---|---|---|---|
| **Go-kit guide: 2-6 hours, 12-24 hours, 24-72+ hours** (`go-kits`) | What goes in a personal kit and a radio kit for short, longer and long deployments, including the forms to carry. | Web page | 2026 | Section resource | ag7qp.com → https://ag7qp.com/go-kits |
| **Personal and station equipment lists** (`equipment-lists`) | Five-hour and overnight kits, and station gear by assignment. Station gear should run for at least 72 hours. | Web page | 2017; being refreshed | Web page | Hosted here (`#` in mockup) |
| **Alert levels explained (Green, Yellow, Orange, Red)** (`alert-levels`) | What each level asks of members. A reference, not a live status. | Web page | 2017 | Web page | Link → ../how-it-works.html#alert-levels |
| **Register with ALERT Spokane** (`alert-spokane`) | The county’s public alert system. Registration is a New Member requirement. | Web page | Regroup platform since April 2026 | Official source | Spokane County → https://www.spokanecounty.gov/3007/Alert-Spokane |
| **Build a household emergency kit** (`ready-kit`) | Ready.gov’s supply list for home. | Web page | — | Official source | Ready.gov → https://www.ready.gov/kit |
| **Radio programming cheat sheets** (`programming-radios`) | Keypad programming for common handhelds and mobiles. Bring your HT and its manual to orientation. | Web page | — | Section resource | ag7qp.com → https://ag7qp.com/programming-radios |
| **GPS coordinates conversion** (`gps-conversion`) `[verify]` | Converting between latitude and longitude formats. | Document | — | Coming back | No link yet |

### Winlink & digital (`#digital`)

*Email over radio, packet and digital modes.*

| Title (`id`) | Description | Format | Version / date | Status | Link / source |
|---|---|---|---|---|---|
| **Winlink: getting started** (`winlink-intro`) | Account setup, sending by Telnet, then by radio; plus Winlink’s mapping of Field Situation Reports. | Web page | — | Section resource | ag7qp.com → https://ag7qp.com/winlink |
| **Winlink Express (software)** (`winlink-express`) | The Winlink client. Use the service code PUBLIC EMCOMM. | Software | — | Official source | Winlink → https://winlink.org/WinlinkExpress |
| **Winlink map-enabled forms (guide)** (`winlink-mapping`) `[verify]` | How reports sent to a callsign can be shown on a map in Winlink Express. | PDF | — | Official source | Winlink → https://winlink.org/sites/default/files/RMSE_FORMS/winlink_mapping_enabled_forms.pdf |
| **Winlink practice exercises** (`winlink-practice-2022`) | Winlink practice exercises from 2022. | Document | 2022 | Coming back | No link yet |
| **Scripting in Winlink 101** (`deck-winlink-scripting`) | From a Second Saturday digital workshop. | Presentation | 2018-01-13 | Coming back | No link yet |
| **ARDOP 101 for Winlink** (`deck-ardop`) | A quick introduction to ARDOP for Winlink. | Presentation | — | Coming back | No link yet |
| **Packet with a SignaLink sound card (UZ7HO SoundModem)** (`signalink-packet`) | SoundModem and EasyTerm as a packet station. | Document | — | Coming back | No link yet |
| **“I Am Safe” messaging: procedures** (`i-am-safe`) `[verify]` | Welfare messages from a disaster area. Developed by the Seattle Emergency Communication Hubs, Seattle ACS, Radio Relay International and the Winlink Development Team. | DOCX | July 2023 | Section resource | ag7qp.com → https://ag7qp.com/ares-acs |

### Reference & SOPs (`#reference`)

*Standing orders, field guides, agreements and rules.*

| Title (`id`) | Description | Format | Version / date | Status | Link / source |
|---|---|---|---|---|---|
| **Eastern Washington Section Standing Order 1b** (`sop-1b`) ★ | What to do if something happens and you have no instructions from your EC or the agency having jurisdiction. | PDF | 2026-01-16 | Section resource | EWA Section (ag7qp.com) → https://ag7qp.com/ares-acs |
| **AUXFOG and NIFOG field operations guides** (`field-guides`) | Free national field guides from CISA SAFECOM, as PDFs or phone apps. | PDF / app | — | Official source | CISA SAFECOM → https://www.cisa.gov/safecom/field-operations-guides |
| **Spokane County ACS Field Operating Guide (ACSFOG)** (`acsfog`) ★ | Half-page and full-page versions, with phone-install instructions. Members only; never posted here. | groups.io | — | groups.io | Link → https://spokaneares-acs.groups.io/g/main/files |
| **How to write an ICS-213** (`ics-213-howto`) | Block-by-block instructions, dates as MM/DD/YYYY and 24-hour times with L or Z. | Web page | — | Section resource | ag7qp.com → https://ag7qp.com/ics-213 |
| **MOU with the Inland Empire VHF Radio Amateurs (Rev. 5)** (`ievhfra-mou`) | Backup repeater (146.880), a second repeater for specialised nets, and packet paths into Winlink. | Web page | 2023-05-06 | Web page | Link → ../about.html#agreements |
| **47 CFR Part 97 (Amateur Radio Service rules)** (`part-97`) | The FCC rules, including 97.407 on RACES. | Web page | current | Official source | eCFR → https://www.ecfr.gov/current/title-47/chapter-I/subchapter-D/part-97 |
| **Preparing for public-service communications** (`public-service-tips`) | Nine tips for your first event shift. | Web page | — | Section resource | ag7qp.com → https://ag7qp.com/preparing-for-public-service-communications |
| **RFI resources** (`rfi-resources`) | Finding and fixing radio-frequency interference. By N7GCO. | Web page | 2021 | Web page | Hosted here (`#` in mockup) |
| **The Radio Amateur’s Code** (`radio-amateur-code`) | Considerate, loyal, progressive, friendly, balanced, patriotic. | Web page | 1928, modernised | Web page | Hosted here (`#` in mockup) |

### History & archive (`#history`)

*Kept for the record.*

| Title (`id`) | Description | Format | Version / date | Status | Link / source |
|---|---|---|---|---|---|
| **Spokane County ARES/RACES Plan (Master 7.13)** (`ares-races-plan`) `[verify]` | The group’s last written plan. Kept for the record; names, structure and agreements are out of date. | PDF | about 2013 | Historical | No link yet |
| **The W7GBU story** (`w7gbu-story`) | Why our call sign honors Ira “Dean” Bula. | Web page | — | Web page | Link → ../about.html#w7gbu |

---

## 5. Status key (`#status-key`)

A small legend, near the filters or at the end of the list. Labels come from `SPOKARES.documentStatuses`.

| Badge | Meaning |
|---|---|
| **Available** | Our current version, hosted here. |
| **Official source** | Opens the issuing agency's own copy (FEMA, ARRL, county, state), so you always get the current version. |
| **Section resource** | Maintained by the Eastern Washington Section; opens ag7qp.com. |
| **Web page** | A page on this site. |
| **Coming back** | Being recovered from the old website. Not available yet. |
| **groups.io** | Members only. Opens groups.io; you need to be an approved member. |
| **Historical** | Kept for the record. Not current guidance. |

---

## 6. Not here? (`#not-here`)

**H2:** Can't find it?

> Some things are deliberately **not** on this site:
> - **The net roster and the ACSFOG** are on groups.io Files, for members only.
> - **County, hospital, SHARES and 800 MHz radio details** are For Official Use Only under the county's non-disclosure agreement. They're in the ACSFOG, never here.
> - **Older presentations** from the previous website are being recovered. Ones that are ready are marked "Coming back".

Links: **groups.io Files** ↗ · **Tell us a link is broken** → webmaster@spokares.org `[verify]`

---

## 7. Page footer note for this page

> Every entry shows a version or date. Forms link to their official source. Last reviewed: {date} `[verify]`

---

## Editorial notes (not for the page)

- **Withheld from the public list pending review** (`documents-inventory.md` §6 Step 4, "RECOVER → review"): OSD-60 Simplex Net Reports (may list call signs and locations), OSD-62 Derecho case study (rights), OSD-61 WA Guard Cascadia briefing (agency permission), **OSD-38 Spokane County 800 MHz radios (SCEM FOUO; never public unless SCEM clears it in writing)**, OSD-26 AREDN (currency). Do not add them to `data.js` until cleared.
- **Retired** (not listed): superseded preambles and the old application (OSD-1-4, 7, 47-49; AG-6), the 2019 membership-requirements deck (OSD-27), the Welcome Letter (OSD-17, folded into About), dated event documents (OSD-10, 12, 18, 22, 74), the legacy static-site files (LEG-1 to LEG-9, including two rosters that must never be republished), and the RFI file duplicate (OSD-59, kept as a page).
- **FEMA refresher decks** (OSD-39, 44, 45, 46) are represented by links to the FEMA courses; keep the decks only if a trainer re-validates them (`documents-inventory.md` Q5).
- **Hosted-here entries** (`href: '#'`) currently live on ag7qp.com as `.docx` files that contain personal e-mail addresses; the mockup links the ag7qp **page**, not the file. Before launch, AG7QP supplies clean masters, published as PDF plus DOCX with ISO-dated file names (e.g. `net-preamble-weekly-2026-03-04.pdf`).
- **ICS-214a and ICS-309** have no authoritative source yet (`documents-inventory.md` Q6); the ag7qp ICS-309 link is dead (404). The entries point to how-to material until a source is chosen.

Sources: `research/documents-inventory.md` §3-§7; `research/decisions.md` D10, D13; `interim-ag7qp/go-kits.md`, `acs-task-book.md`, `ares-acs.md`, `ics-213.md`, `winlink.md`.
