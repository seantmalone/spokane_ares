# Document library inventory (reconstructed)

- **Prepared:** 2026-09-25.
- **Scope:** every downloadable document that spokares.org has offered, from the 2004–2015 static site through the OSDownloads library of the Joomla 3 site. It also covers the documents now on the interim ag7qp.com pages that the rebuilt Library has to carry or link to.
- **Status today (checked 2026-09-25):** `/index.php/downloads/presentations` and the ARES application `routedownload` URL return **HTTP 500**. `/index.php/downloads/ics-forms`, the mentoring `routedownload` URLs, `/Memberlist.pdf` and `/NetPreamble.html` return **404**.

## Contents

1. [Sources and method](#1-sources-and-method)
2. [Key findings](#2-key-findings)
3. [Counts](#3-counts)
4. [Summary table](#4-summary-table)
5. [Per-document detail](#5-per-document-detail)
6. [Recovery plan](#6-recovery-plan)
7. [Open questions for leadership](#7-open-questions-for-leadership)
8. [Appendix A: other ag7qp.com assets (not Spokane library)](#appendix-a-other-ag7qpcom-assets-not-spokane-library)
9. [Appendix B: OSDownloads ID gaps](#appendix-b-osdownloads-id-gaps)
10. [Appendix C: verification log](#appendix-c-verification-log)

---

## 1. Sources and method

### Source abbreviations used below

| Tag | Source | Capture / fetch date |
|---|---|---|
| **W24-NET** | `research/wayback/snapshots/mail.spokares.org/index.php/downloads/net-preambles-and-scripts` | 2024-09-13 |
| **W24-PRES** | `research/wayback/snapshots/mail.spokares.org/index.php/downloads/presentations` | 2024-09-13 |
| **W24-FI** | `research/wayback/snapshots/mail.spokares.org/index.php/downloads/forms-and-information` | 2024-09-11 |
| **W24-GEN** | `research/wayback/snapshots/mail.spokares.org/index.php/downloads/general` | 2024-09-13 |
| **W19-NET** | `research/wayback/snapshots/www.spokares.org/index.php/downloads/net-preambles-and-scripts` | 2019-07-23 |
| **W19-PRES** | `research/wayback/snapshots/www.spokares.org/index.php/downloads/presentations` (and `spokares.org/.../presentations/category`, 2019-07-24) | 2019-07-23 |
| **W19-FI** | `research/wayback/snapshots/www.spokares.org/index.php/downloads/forms-and-information` | 2019-07-23 |
| **W19-GEN** | `research/wayback/snapshots/www.spokares.org/index.php/downloads/general` | 2019-07-23 |
| **W19-DT** | `research/wayback/snapshots/www.spokares.org/index.php/downloads/digital-traffic-information-and-files` | 2019-07-23 |
| **W19-ICS** | `research/wayback/snapshots/www.spokares.org/index.php/downloads/ics-forms` | 2019-07-23 |
| **W19-MENT** | `research/wayback/snapshots/www.spokares.org/index.php/downloads/mentoring` | 2019-07-23 |
| **W19-FILE** | OSDownloads per-file pages fetched from Wayback on 2026-09-25 (`web.archive.org/web/<ts>id_/…/file/…`). These are not saved in the repo, because their paths collide with the category-page files. They supply the "Downloaded: N" counters | 2019-07-24 to 2019-09-20 |
| **LEG-IDX** | Wayback copies of the static-site home page `index.html` (2008-01-31, 2008-11-20, 2010-04-15, 2010-09-28, 2013-04-27, 2013-12-06, 2015-09-27), fetched 2026-09-25 | as listed |
| **LEG-NP** | `NetPreamble.html` Wayback copies 2010-09-24 and 2015-05-22 (fetched), plus `research/wayback/snapshots/www.spokares.org/NetPreamble.html` (2008-04-09) and `Net_20Preamble_209.1.2007.html` (2008-01-28) | as listed |
| **CDX** | Wayback CDX API: `url=spokares.org&matchType=domain` (946 rows, the same as `research/wayback/cdx-spokares.txt`), plus targeted prefixes `spokares.org/media/com_osdownloads/*`, `spokares.org/images/pdf_files/*`, `spokares.org/Downloads/*`, `spokares.org/TrafficHandler*`, `spokares.org/NetPreamble*`, `spokares.org/*.zip` and `assets.zyrosite.com/AVLpnMOEDxUWzNvE/` | 2026-09-25 |
| **AG-SPK / AG-ACS / AG-ATB / AG-CTB / AG-ICS / AG-GK** | https://ag7qp.com/spokane-county-ares-acs, /ares-acs, /ares-task-book, /acs-task-book, /ics-213, /go-kits. All 33 sitemap pages were fetched once each (HTML only) with a 2 s delay | 2026-09-25 (site last published 2026-09-23) |
| **A-nn** | `research/content/live/nn-*.md` (live Joomla article nn) | crawl 2026-09-25 |

### Method

- The OSDownloads markup was parsed item by item. Each item is a `<div class="item_<ID>">` holding its title (`data-name`), description (`item_content`) and `routedownload` link.
- **The ID is the OSDownloads document primary key.** Use it to match rows in the database dump during recovery.
- File **formats and sizes are unknown** for every OSDownloads item. The listings never showed them, and no file body was archived.
- **The two rosters and the member list were not opened, downloaded or HEAD-requested** in this work. This covers `ARES_20NET_20ROSTER_20FEB09.doc`, `Memberlist.pdf`, the ag7qp net roster and the EWSEN VHF roster. Their sensitivity is judged from the title and link label only.
- Personal contact details found in any source are **not** reproduced here.

## 2. Key findings

1. **None of the 46 OSDownloads files is in the Wayback Machine.**
   - The CDX has the listing pages only. The `routedownload` captures (for example 2019-08-20 ICS 201 and 2021-05-05 Cross-Band Repeaters) are `text/html` "Thank you, click here" interstitials, not the files.
   - `media/com_osdownloads/*` has only CSS and JS.
   - **The InMotion account is the only known copy** for 33 items live in Sept 2024 and 13 items seen only in 2019. Authors and groups.io are secondary sources.
2. **Six legacy static-site binaries are in the Wayback Machine and saved locally** (`research/wayback/snapshots/`, 2009–2015).
   - Two of them are **PII** (`Memberlist.pdf`, `ARES NET ROSTER FEB09.doc`) and should not be carried forward.
   - The other four are superseded or historical.
3. **ag7qp.com already holds newer versions of the documents that matter most:**
   - net preambles for the repeater, simplex and GMRS nets (2025–26)
   - the ARES membership application (2024-11-07)
   - the ACS Task Book (2024-08-09)
   - the net roster (2026-08-04, PII)

   These should be **migrated from ag7qp, not recovered from InMotion**. The InMotion copies of these items are superseded.
4. **The four ICS forms (201/205/213/214) should become links to FEMA, not hosted files.**
   - FEMA now serves ICS 205 and 214 as **v3.1**. ICS 201 and 213 are still v3.
   - The old URL patterns for 205/214 (v3) return 404. The v3.1 URLs return 200.
5. **Broken or stale links on ag7qp.com that the rebuild should not copy:**
   - The **ICS-309** fillable form link (archive.arrl-nfl.org) returns **404**.
   - Two different ARES application versions are linked: 2024-09-15 on /ares-task-book and 2024-11-07 on /ares-acs.
6. **No download gate was ever switched on.** Every captured item, in both 2019 and 2024, has `data-require-email="0" data-require-agree="0" data-require-share="0"` (66 of 66 attributes). The OSDownloads pop-up markup was present but unused. This **corrects** the "Downloads were gated in 2019" line in `site-functionality-analysis.md` §3.2.
7. **The OSDownloads IDs run from 1 to 78, but only 46 are accounted for.** The 32 missing IDs (Appendix B) are deleted, unpublished, or in the Digital Traffic category, which has no 2024 capture. The database dump will settle which.
8. **Popularity signals** (OSDownloads "Downloaded" counters, W19-FILE):
   - ARES/RACES Membership Application: **280** (2019-09-20)
   - Welcome Letter: **119** (2019-09-20)
   - Lilac Torchlight Parade briefing: 93 (2019-08-26)
   - AUXCOMM Flyer: 17 (2019-07-24)

## 3. Counts

| Group | Items | Copy exists today? | Notes |
|---|---:|---|---|
| **OSD**: OSDownloads library (Joomla, 2017–2024) | **46** | 0 in Wayback. 6 have a newer or equivalent version elsewhere (4 on ag7qp, 2 as site articles); the 4 ICS forms have FEMA originals | 33 live in Sept 2024 (Net Preambles 5, Presentations 23, Forms & Info 2, General 3) + 13 seen only in 2019 (ICS 4, Net Preambles 4, Mentoring 2, Digital Traffic 1, Forms & Info 1, General 1) |
| **LEG**: static-site files (2004–2015) | **9** | 6 in Wayback (saved locally) | 2 are PII |
| **EXT**: external documents linked from spokares.org | **7** | 6 live, 1 dead (FEMA 2014 supply list, 403) | Not hosted by the org |
| **AG**: Spokane-relevant documents hosted on ag7qp.com | **9** | 9 live on assets.zyrosite.com. None archived in Wayback (CDX) | 1 is PII (net roster) |
| **FRM**: other standard forms linked from ag7qp ARES/ICS/go-kit pages | **5** | 4 live, 1 404 (ICS-309) | Third-party or agency hosted |
| **Total inventoried** | **76** | | Plus 17 non-Spokane ag7qp assets in Appendix A |

**Recommendations across the 76 items** (script-tallied from §4):

| Code | Meaning | Count |
|---|---|---:|
| **MIGRATE** | A usable current copy exists now (all 5 are on ag7qp.com); put it in the new Library | 5 |
| **LINK** | Replace with a link to the authoritative source (FEMA, ARRL, county, WSDOT, EWA Section, originator); do not host | 23 |
| **RECOVER → migrate** | The only known copy is on InMotion; recover it, then publish | 16 |
| **RECOVER → review** | Recover it, then decide; rights, sensitivity or currency need a human call | 5 |
| **RETIRE** | Do not carry forward (superseded, dated, or a duplicate of a page); archive offline if recovered | 26 |
| **MEMBERS-ONLY** | PII (the current net roster); groups.io Files only, never the public site | 1 |
| **Total** | | **76** |

- **Needs recovery from the InMotion backup:** **21** (16 + 5).
- **Sensitivity:**
  - **4 PII items:** OSD-47, LEG-5, LEG-9, AG-4. None was opened.
  - **2 legacy PDFs embed one individual's email:** LEG-4, LEG-6.
  - **6 items need a sensitivity or rights review:** OSD-60, 62, 61, 38, 10, 18.
- **Existing copies:**
  - **6 items have a Wayback file copy** (LEG-4 to LEG-9, saved locally).
  - **9 items are live on ag7qp.com.**
  - **None of the 46 OSDownloads files** has a copy outside InMotion. Six have a newer or equivalent version elsewhere: OSD-47/48/49/7 on ag7qp, and OSD-17/59 as articles. The four ICS forms have FEMA originals.

## 4. Summary table

Legend:
- **Last seen** is the last Wayback capture or live fetch showing the item listed or linked.
- **Copy?** can be: **WB** = file in Wayback and saved in `research/wayback/snapshots/`; **AG** = a current version on ag7qp.com; **none** = no known copy outside InMotion.
- **PII**: `—` none expected; **PII** = personal data; **email** = one individual's email embedded; **review** = operational or third-party sensitivity to check.

| # | Title (as listed) | Category | Last seen | Copy? | Authoritative elsewhere | PII | Recommendation |
|---|---|---|---|---|---|---|---|
| OSD-47 | Spokane County ARES - ACS Net Roster | Net Preambles & Scripts | 2024-09-13 | superseded by AG-4 | — | **PII** | RETIRE (superseded; do not recover to the public site) |
| OSD-48 | Spokane County ARES - ACS Net Preamble (Regular) | Net Preambles & Scripts | 2024-09-13 | superseded by AG-1 | — | — | RETIRE (superseded) |
| OSD-49 | Spokane County ARES - ACS Net Preamble (Simplex) | Net Preambles & Scripts | 2024-09-13 | superseded by AG-2 | — | — | RETIRE (superseded) |
| OSD-60 | Simplex Net Reports | Net Preambles & Scripts | 2024-09-13 | none | — | review | RECOVER → review |
| OSD-71 | Winlink Practice Exercises (alias "2022-…") | Net Preambles & Scripts | 2024-09-13 | none | — | — | RECOVER → migrate |
| OSD-1 | Net Preamble & Script - 147.300 Repeater | Net Preambles & Scripts | 2019-07-23 | none | — | — | RETIRE (superseded) |
| OSD-2 | Net Preamble & Script - 147.300 Repeater as W7GBU | Net Preambles & Scripts | 2019-07-23 | none | — | — | RETIRE (superseded) |
| OSD-3 | Net Preamble & Script - 146.880 Alternate Repeater | Net Preambles & Scripts | 2019-07-23 | none | — | — | RETIRE (superseded; see open question Q3) |
| OSD-4 | Net Preamble & Script - 146.880 Alternate Repeater as W7GBU | Net Preambles & Scripts | 2019-07-23 | none | — | — | RETIRE (superseded; see Q3) |
| OSD-75 | Five (5) Knots You Should Know | Presentations | 2024-09-13 | none | — | — | RECOVER → migrate |
| OSD-78 | Maintaining and Improving Safety on ARES Deployments (N7LL, 2023-01-19) | Presentations | 2024-09-13 | none | — | — | RECOVER → migrate |
| OSD-63 | Initial Damage Assessment Presentation | Presentations | 2024-09-13 | none | — | — | RECOVER → migrate |
| OSD-62 | Case Study: Derecho Storm Disaster | Presentations | 2024-09-13 | none | [Inference] possibly third-party | review | RECOVER → review |
| OSD-50 | Mel Ming's Radio Go-Kit Presentation | Presentations | 2024-09-13 | none | — | — | RECOVER → migrate |
| OSD-61 | Washington Guard Response to Cascadia Subduction Zone Event | Presentations | 2024-09-13 | none | [Inference] third-party (WA National Guard) | review | RECOVER → review |
| OSD-39 | Abbreviated IS-100C Overview | Presentations | 2024-09-13 | none | FEMA IS-100.c course | — | LINK (FEMA) |
| OSD-44 | Abbreviated IS-200C Overview | Presentations | 2024-09-13 | none | FEMA IS-200.c course | — | LINK (FEMA) |
| OSD-46 | Abbreviated IS-700 Review | Presentations | 2024-09-13 | none | FEMA IS-700.b course | — | LINK (FEMA) |
| OSD-45 | Abbreviated IS-800 Review | Presentations | 2024-09-13 | none | FEMA IS-800.d course | — | LINK (FEMA) |
| OSD-38 | Spokane County Introduction to 800MHz Radios - ACS | Presentations | 2024-09-13 | none | — | review | RECOVER → review |
| OSD-27 | Spokane County ARES - ACS Membership Requirements (2019) | Presentations | 2024-09-13 | superseded by AG-7 + EXT-1 | ARRL ARES Task Book | — | RETIRE (superseded) |
| OSD-26 | Intro to AREDN for Spokane County ACS | Presentations | 2024-09-13 | none | — | — | RECOVER → review |
| OSD-23 | Mel Ming's Emergency Modules presentation | Presentations | 2024-09-13 | none | — | — | RECOVER → migrate |
| OSD-25 | How to Power your Radio when the Lights Go Out (WA7AQH) | Presentations | 2024-09-13 | none | — | — | RECOVER → migrate |
| OSD-24 | A Guide to Anderson Powerpoles | Presentations | 2024-09-13 | none | ARRL "Anderson powerpole" PDF (linked by AG-ATB) | — | LINK (ARRL) |
| OSD-20 | Intro to Packet Communications - 101 (K7PKT) | Presentations | 2024-09-13 | none | — | — | RECOVER → migrate |
| OSD-11 | Scripting in Winlink 101 (SSDW 2018-01-13) | Presentations | 2024-09-13 | none | — | — | RECOVER → migrate |
| OSD-19 | Ardop 101 for Winlink | Presentations | 2024-09-13 | none | — | — | RECOVER → migrate |
| OSD-6 | Go-Kits - 2017 (Aaron Noack) | Presentations | 2024-09-13 | none | — | — | RECOVER → migrate |
| OSD-10 | 2017 Lilac Presentation - Todd Cady K7PKT | Presentations | 2024-09-13 | none | — | review | RETIRE (dated event) |
| OSD-5 | Cross-Band Repeaters - 2017 (AD7DD) | Presentations | 2024-09-13 | none | — | — | RECOVER → migrate |
| OSD-18 | Lilac Festival - Armed Forces Torchlight Parade briefing | Presentations | 2024-09-13 | none | — | review | RETIRE (dated event ops) |
| OSD-17 | Welcome Letter | Forms & Information | 2024-09-11 | text also in A-41 | — | — | RETIRE (fold into the Join page) |
| OSD-7 | ARES / RACES Membership Appplication [sic] | Forms & Information | 2024-09-11 | superseded by AG-5 | — | blank form: — | RETIRE (superseded by AG-5) |
| OSD-22 | AUXCOMM Flyer - June 2019 | Forms & Information | 2019-09-20 | none | — | — | RETIRE (expired event) |
| OSD-74 | Field Day 2022 | General | 2024-09-13 | none | — | — | RETIRE (dated event) |
| OSD-64 | GPS Coordinatges [sic] Conversion | General | 2024-09-13 | none | — | — | RECOVER → migrate |
| OSD-59 | Mel Ming - RFI Resources | General | 2024-09-13 | text in A-62 | — | — | RETIRE (duplicate of A-62) |
| OSD-12 | Electronics Flea Market - Tri Cities 2018 May | General | 2019-07-23 | none | — | — | RETIRE (expired third-party flyer) |
| OSD-21 | How to operate Packet using a SignaLink Integrated Sound Card | Digital Traffic Info & Files | 2019-07-23 | none | — | — | RECOVER → migrate |
| OSD-13 | ICS 201 - Incident Briefing | ICS - Forms | 2019-08-20 | none | **FEMA ICS 201 (v3)** | — | LINK (FEMA) |
| OSD-14 | ICS 205 - Incident Radio Communications Plan | ICS - Forms | 2019-08-20 | none | **FEMA ICS 205 (v3.1)** | — | LINK (FEMA) |
| OSD-15 | ICS 213 - General Message form | ICS - Forms | 2019-08-20 | none | **FEMA ICS 213 (v3)** | — | LINK (FEMA) |
| OSD-16 | ICS 214 - Activity Log | ICS - Forms | 2019-08-20 | none | **FEMA ICS 214 (v3.1)** | — | LINK (FEMA) |
| OSD-8 | Traffic Handler PDF (also static `TrafficHandler.pdf`) | Mentoring | 2019-07-23 (still linked from A-25 today, 404) | none | [Inference] possibly third-party NTS material | — | RECOVER → migrate |
| OSD-9 | Traffic Handler MP3 (also static `TrafficHandler.mp3`) | Mentoring | 2019-07-23 (still linked from A-25 today, 404) | none | as OSD-8 | — | RECOVER → migrate |
| LEG-1 | "Download NIMS files here!!" (`Downloads/NIMSFiles.zip`) | Static site | 2010-09-28 | none | FEMA NIMS resources | — | RETIRE |
| LEG-2 | Net Preamble PDF (`NetPreamble.pdf`) | Static site | 2008-04-09 | none | — | — | RETIRE |
| LEG-3 | Net Preamble "Zipped PDF" (`Net Preamble 9.1.2007.pdf`) | Static site | 2008-01-28 | none | — | — | RETIRE |
| LEG-4 | Net Preamble 3-2009 (`Net Preamble 3-2009.pdf`) | Static site | 2015-05-22 (link); file captured 2010-09-28 | **WB** | — | email | RETIRE (history only) |
| LEG-5 | ARES Net Roster Feb 2009 (`Downloads/ARES NET ROSTER FEB09.doc`) | Static site | 2015-05-22 (link); file captured 2010-09-28 | **WB** | — | **PII** | RETIRE (+ consider Wayback removal) |
| LEG-6 | Field Day 2012 (`FieldDay2012.pdf`) | Static site | 2013-04-27 | **WB** | — | email | RETIRE (history only) |
| LEG-7 | Field Day 2013 (`Field Day 2013.pdf`) | Static site | 2015-09-27 | **WB** | — | — | RETIRE (history only) |
| LEG-8 | Status Codes (`StatusCode.pdf`) | Static site | 2015-09-27 | **WB** | — | — | RETIRE (superseded by page A-20) |
| LEG-9 | Current Member List (`Memberlist.pdf`) | Static site | 2015-09-27 | **WB** | — | **PII** | RETIRE (+ consider Wayback removal) |
| EXT-1 | ARRL ARES Task Book (July 2024 .doc / fillable .pdf) | Join / training | 2026-09-25 | ARRL | **ARRL** | — | LINK (ARRL) |
| EXT-2 | Spokane County Volunteer Emergency Worker Application | Join | 2026-09-25 | county | **Spokane County (now spokanecounty.gov)** | — | LINK (county) |
| EXT-3 | ARRL Radiogram Form (weblink id 1) | Operations → Forms | 2026-09-25 | ARRL | **ARRL fillable PDF** | — | LINK (ARRL) |
| EXT-4 | FEMA ICS Forms, home page (weblink id 9) | Operations → Forms | 2026-09-25 | FEMA | **FEMA EMI ICS forms index** | — | LINK (FEMA) |
| EXT-5 | Mel Ming N7GCO RFI video, February 2021 (Dropbox .mp4) | Digital Messaging → RFI | 2026-09-25 | Dropbox | author's Dropbox | — | LINK (ask to mirror) |
| EXT-6 | RATPAC Video Presentations list (Google Sheets) | Downloads menu | 2026-09-25 | Google | RATPAC | — | LINK |
| EXT-7 | FEMA "Emergency Supply List" (`checklist_2014.pdf`) | Shelter-in-place page | 2019-07-23 | dead (403) | **Ready.gov /kit** | — | LINK (replace with Ready.gov) |
| AG-1 | Net Preamble / Script, 2026-03-04 (.docx) | Net operations | 2026-09-25 | **AG** | — | — | MIGRATE |
| AG-2 | Net Preamble / Script, Simplex Net, 2026-03-04 (.docx) | Net operations | 2026-09-25 | **AG** | — | — | MIGRATE |
| AG-3 | Net Preamble / Script, GMRS Net, 2025-08-19 (.docx) | Net operations | 2026-09-25 | **AG** | — | — | MIGRATE |
| AG-4 | ARES-ACS Net Roster, 2026-08-04 (.pdf) | Net operations | 2026-09-25 | **AG** (not downloaded) | — | **PII** | MEMBERS-ONLY (groups.io) |
| AG-5 | Spokane County ARES Membership Application, 2024-11-07 (.docx) | Join | 2026-09-25 | **AG** | — | blank form: — | MIGRATE |
| AG-6 | Spokane County ARES Membership Application, 2024-09-15 (.docx) | Join | 2026-09-25 | **AG** | — | — | RETIRE (superseded by AG-5) |
| AG-7 | ACS Task Book Requirements, version 2024-08-09 (.docx) | Join / qualify | 2026-09-25 | **AG** | — | — | MIGRATE |
| AG-8 | EWA Section Standing Order 1b, 2026-01-16 (.pdf) | Operations | 2026-09-25 | **AG** | **EWA Section (SEC AG7QP): the ag7qp copy is the source** | — | LINK (Section) |
| AG-9 | "I Am Safe" Messages Standard Operating Procedures, July 2023 (.docx) | Operations / Winlink | 2026-09-25 | **AG** | third-party originators (Seattle Hubs / Seattle ACS / RRI / Winlink team, per AG-ACS) | — | LINK |
| FRM-1 | ICS 213RR Resource Request Message | Forms | 2026-09-25 | FEMA | **FEMA ICS 213RR (v3)** | — | LINK (FEMA) |
| FRM-2 | ICS 214a Individual Log | Forms | 2026-09-25 | third-party copy | not on the FEMA EMI list; authoritative source **unverified** | — | LINK (find authoritative) |
| FRM-3 | ICS 309 Communications Log (fillable) | Forms | 2026-09-25 (**link 404**) | none at that URL | not on the FEMA EMI list; authoritative source **unverified** | — | LINK (find authoritative; fix dead link) |
| FRM-4 | ICS-309 Guidance (Hawaii ARES, SET 2022) | Forms / training | 2026-09-25 | Hawaii ARES | Hawaii ARES | — | LINK |
| FRM-5 | WSDOT Form 580-020, Road and Bridge Assessment | Forms | 2026-09-25 | WSDOT | **WSDOT** | — | LINK (WSDOT) |

**Counts by code** are in §3, script-tallied from this table: MIGRATE 5 · LINK 23 · RECOVER→migrate 16 · RECOVER→review 5 · RETIRE 26 · MEMBERS-ONLY 1 = 76.

## 5. Per-document detail

### 5.1 OSDownloads: Net Preambles and Scripts

- **Category description (W19-NET):** "These are the scripts typically read during a normal Net… The scripts contain recommendations for pauses and repeating calls as necessary."
- **URL pattern, 2024:** `https://{www.|mail.}spokares.org/index.php/downloads/net-preambles-and-scripts/routedownload/<alias>`
- **URL pattern, 2019:** `https://www.spokares.org/index.php/downloads/net-preambles-and-scripts/routedownload/net-preambles-and-scripts/<alias>`
- **Linked from:** article A-8 ("Download the current various Net Preambles, Scripts and Roster from the Downloads area") and the Operations → "Net Preamble" menu item, which both point here and both now return 500.

| # | Alias | Description (as published) and notes | Source |
|---|---|---|---|
| OSD-47 | `ares-net-roster` | No description. Title says roster, so **member data** [Inference: callsigns, names, possibly contact data]. Current successor is AG-4 | W24-NET |
| OSD-48 | `spokane-county-ares-acs-net-preamble-regular` | No description. Superseded by AG-1 (2026-03-04). The A-8 HTML text says "Preamble last updated: April 4, 2018" | W24-NET, A-8 |
| OSD-49 | `spokane-county-ares-acs-net-preamble-simplex` | No description. Superseded by AG-2 (2026-03-04) | W24-NET |
| OSD-60 | `simplex-net-reports` | No description. [Inference] check-in reports from simplex nets, so it may list callsigns and locations. Review before publishing; historical value only | W24-NET |
| OSD-71 | `2022-winlink-practice-exercises` | No description. Alias dates it to 2022. Migrate if the exercises are still used (ask the Winlink lead) | W24-NET |
| OSD-1 | `net-preamble-script-147-300-repeater` | "Preamble and script used for the weekly ARES / RACES net when performed from a location other than the Spokane County DEM Building or ARES / RACES mobile unit (the Van)." | W19-NET |
| OSD-2 | `net-preamble-script-147-300-repeater-as-w7gbu` | "…when performed from the Spokane County DEM Building or ARES / RACES mobile unit (the Van) as W7GBU." | W19-NET |
| OSD-3 | `net-preamble-script-146-880-alternate-repeater` | "…used with the ARES / RACES Alternate Repeater provided by the Inland Empire VHF Club. This is used when the primary repeater at 147.300 is not on the air…" (non-DEM location) | W19-NET |
| OSD-4 | `net-preamble-script-146-880-alternate-repeater-as-w7gbu` | As OSD-3, operating from the DEM building or the Van as W7GBU | W19-NET |

### 5.2 OSDownloads: Presentations

- **Category description (W19-PRES):** "These are various slide-decks, PowerPoint presentations and other material used during monthly meetings or other training sessions."
- **URL pattern, 2024:** `…/index.php/downloads/presentations/routedownload/<alias>`
- **URL pattern, 2019:** `…/presentations/routedownload/presentations/<alias>`
- **Date ranges:** "First seen 2019" items appear in W19-PRES (2019-07-23). All 23 appear in W24-PRES (2024-09-13).

| # | Alias | Description (as published) and notes | First seen | Source |
|---|---|---|---|---|
| OSD-75 | `five-5-knots-you-should-know` | No description | 2024 | W24-PRES |
| OSD-78 | `maintaining-and-improving-safety-on-ares-deployments` | "Presentation by David Oglesby, N7LL, AEC, on January 19, 2023" | 2024 | W24-PRES |
| OSD-63 | `initial-damage-assessment-presentation` | "How Spokane County ARES - ACS will react to and use the Initial Damage Assessment Winlink form". Check it against the current WA IDA/Winlink form before republishing | 2024 | W24-PRES |
| OSD-62 | `case-study-derecho-storm-disaster` | No description. [Inference] may be a third-party deck; confirm the author and rights | 2024 | W24-PRES |
| OSD-50 | `mel-ming-s-radio-go-kit-presentation` | No description. Confirm the permission Mel Ming gave for OSD-23 also covers this deck | 2024 | W24-PRES |
| OSD-61 | `washington-guard-response-to-cascadia-subduction-zone-event` | No description. [Inference] an agency briefing, so check distribution markings and permission; otherwise link to the originator | 2024 | W24-PRES |
| OSD-39 | `abbreviated-is-100c-overview` | "…geared as a 'refresher' for those who have already taken the course and is not intended to replace the on-line course by FEMA… condensed for a one-hour refresher." Link to FEMA IS-100.c. Keep the deck only if a trainer re-validates it | 2024 | W24-PRES |
| OSD-44 | `abbreviated-is-200c-overview` | No description. Link to FEMA IS-200.c | 2024 | W24-PRES |
| OSD-46 | `abbreviated-is-700-review` | No description. Link to FEMA IS-700.b | 2024 | W24-PRES |
| OSD-45 | `abbreviated-is-800-review` | No description. Link to FEMA IS-800 (the current code redirects to IS-800.d) | 2024 | W24-PRES |
| OSD-38 | `spokane-county-introduction-to-800mhz-radios-acs` | "Introductory presentation on the 800MHz radios used by many Spokane County agencies. ACS members who are called to duty may be required to use these radios…" **Review with SCEM** before any public posting, because it may show county radio-system details. Members-only if in doubt | 2024 | W24-PRES |
| OSD-27 | `spokane-county-ares-acs-membership-requirements` | "…outlines the new ARRL ARES requirements that came out in 2019 as well as the more specific requirements for Spokane County ACS." Superseded by the ACS Task Book 2024-08-09 (AG-7) and the ARRL ARES Task Book July 2024 (EXT-1) | 2024 | W24-PRES |
| OSD-26 | `intro-to-aredn-for-spokane-county-acs` | "…introduce the members… to the idea of building an AREDN network in Spokane County." Migrate only if the AREDN effort is current | 2024 | W24-PRES |
| OSD-23 | `mel-ming-s-emergency-modules-presentation` | "A comprehensive approach to preparing for emergencies… Mel has given his permission for us to host this content." | 2019 | W19-PRES, W24-PRES |
| OSD-25 | `how-to-power-your-radio-when-the-lights-go-out` | "A subset of information from SeaPac, put together for our local group by WA7AQH. A focus on battery technologies and solar power systems." | 2024 | W24-PRES |
| OSD-24 | `a-guide-to-anderson-powerpoles` | "…introduce you to the Anderson Powerpole concept and teach you how to successfully create your own cable connections." The ARRL PDF (https://www.arrl.org/files/file/Public%20Service/TrainingModules/Technical/Anderson%20powerpole.pdf, 200 on 2026-09-25) is already the reference on AG-ATB | 2019 | W19-PRES, W24-PRES, AG-ATB |
| OSD-20 | `intro-to-packet-communications-101` | "This presentation by Todd Cady, K7PKT, is an introduction to Packet communications…" | 2019 | W19-PRES, W24-PRES |
| OSD-11 | `scripting-in-winlink-101` | "From the 2018 01 13 Second Saturday Digital Workshop (SSDW)… a quick introduction to scripting for Winlink communications." | 2019 | W19-PRES, W24-PRES |
| OSD-19 | `ardop-101-for-winlink` | "A quick and basic introduction to Ardop Winlink…" | 2019 | W19-PRES, W24-PRES |
| OSD-6 | `go-kits-2017` | "A presentation by Aaron Noack on Go-Kits, quick-reaction packs, or Bug-Out bags" | 2019 | W19-PRES, W24-PRES |
| OSD-10 | `2017-lilac-presentation-todd-cady-k7pkt` | No description. A dated single-event briefing | 2019 | W19-PRES, W24-PRES |
| OSD-5 | `cross-band-repeaters-2017` | "A presentation given by Aaron Noack (AD7DD) on Cross-Band repeating. General theory, use cases and illustration of setup." The `routedownload` interstitial was captured 2021-05-05 (CDX) | 2019 | W19-PRES, W24-PRES |
| OSD-18 | `lilac-festival-armed-forces-torchlight-parade-briefing` | "…basics of radio operations for the Spokane, WA, Lilac Festival's Armed Forces Torchlight Parade… Frequencies are NOT given in the presentation." Downloaded 93 times by 2019-08-26 | 2019 | W19-PRES, W19-FILE |

### 5.3 OSDownloads: Forms and Information

- **Category description (W19-FI):** "Items such as membership application forms, brochures and other information ARES / RACES members may be interested in."
- **2019 site banner:** "The membership Welcome Letter and ARES application have been updated to better reflect requirements for obtaining the Spokane County ID card."

| # | Alias | Description and notes | Last-known URL | Source |
|---|---|---|---|---|
| OSD-17 | `welcome-letter` | "Membership welcome letter with links to various training." Downloaded 119 by 2019-09-20. The same letter exists as HTML article A-41 (2018 text), which lists only IS-100.C and IS-700.B. **Outdated** compared with the ACS Task Book (see the note at the end of `design/content-brief.md` §5) | `…/downloads/forms-and-information/routedownload/welcome-letter` | W24-FI, W19-FILE, A-41 |
| OSD-7 | `ares-races-membership-appplication` [sic] | "This form may be used to apply for membership in the ARES / RACES organization that serves the Spokane County Department of Emergency Management." Downloaded 280 by 2019-09-20, the most-used file. A-26 links it (now 500) | `…/routedownload/forms-and-information/ares-races-membership-appplication` (A-26) | W24-FI, W19-FILE, A-26 |
| OSD-22 | `auxcomm-flyer-june-2019` | No description. Downloaded 17. It relates to the archived post "AUXCOMM training Idaho June 2019" (`research/content/archive/20190724-index-php-53-auxcomm-training-idaho-june-2019.md`) | `…/routedownload/forms-and-information/auxcomm-flyer-june-2019` (captured 2019-09-20) | W19-FI, W19-FILE |

### 5.4 OSDownloads: General

| # | Alias | Notes | Last seen | Source |
|---|---|---|---|---|
| OSD-74 | `field-day-2022` | No description. A dated event document | 2024-09-13 | W24-GEN |
| OSD-64 | `gps-coordinatges-conversion` [sic] | No description. [Inference] a lat/long format conversion aid; generally useful | 2024-09-13 | W24-GEN |
| OSD-59 | `mel-ming-rfi-resources` | No description. The same title as HTML article A-62 ("Mel Ming's RFI Resources", 2021-02-20), which holds the full text and 6 external PDF references (Palomar and Audio Systems Group). Migrate the article, not the file | 2024-09-13 | W24-GEN, A-62 |
| OSD-12 | `electronics-flea-market-tri-cities-2018-may` | No description. Expired third-party event flyer | 2019-07-23 | W19-GEN |

### 5.5 OSDownloads: Digital Traffic Information and Files

| # | Alias | Description | Last seen | Source |
|---|---|---|---|---|
| OSD-21 | `how-to-operate-packet-using-a-signalink-integrated-sound-card` | "…a quick introduction to UZ7HO SoundModem software and its associated EasyTerm program for use as a packet station." The category was still in the 2024 menu, but its page was not captured in 2024 | 2019-07-23 | W19-DT |

### 5.6 OSDownloads: ICS - Forms (menu removed by 2024)

- **Category description (W19-ICS):** "These are the standard ICS Forms necessary for communications with served agencies. Members should become familiar with these forms and keep copies on-hand locally."
- **Replacement links:** FEMA index https://training.fema.gov/emiweb/is/icsresource/icsforms/. The PDFs below were all checked 200 on 2026-09-25, and the version numbers come from the FEMA page.

| # | Alias | Replace with |
|---|---|---|
| OSD-13 | `ics-201-incident-briefing` | https://training.fema.gov/EMIWeb/IS/ICSResource/assets/ICS%20Forms//ICS%20Form%20201,%20Incident%20Briefing%20(v3).pdf |
| OSD-14 | `ics-205-incident-radio-communications-plan` | https://training.fema.gov/EMIWeb/IS/ICSResource/assets/ICS%20Forms//ICS%20Form%20205,%20Incident%20Radio%20Communications%20Plan%20(v3.1).pdf |
| OSD-15 | `ics-213-general-message-form` | https://training.fema.gov/EMIWeb/IS/ICSResource/assets/ICS%20Forms//ICS%20Form%20213,%20General%20Message%20(v3).pdf |
| OSD-16 | `ics-214-activity-log` | https://training.fema.gov/EMIWeb/IS/ICSResource/assets/ICS%20Forms//ICS%20Form%20214,%20Activity%20Log%20(v3.1).pdf |

- **Link advice:** link the FEMA **index page** rather than deep PDF links. The deep links change with each form revision: the v3 URLs for 205/214 now 404.

### 5.7 OSDownloads: Mentoring (menu removed by 2024; still linked from A-25)

| # | Title | Last-known URLs | Notes |
|---|---|---|---|
| OSD-8 | Traffic Handler PDF | `https://www.spokares.org/index.php/downloads/mentoring/routedownload/mentoring/traffic-handler-pdf` (A-25, 404 today). Earlier: `http://spokares.org/TrafficHandler.pdf` (linked from `MentorPrograms.html`, captured 2015-02-24 and 2015-05-22; the file itself is **not** in CDX) | A-25 had 8,685 hits. [Inference] traffic-handling (NTS) training material; confirm authorship and rights on recovery |
| OSD-9 | Traffic Handler MP3 | `…/mentoring/routedownload/mentoring/traffic-handler-mp3`. Earlier: `http://spokares.org/TrafficHandler.mp3` (not in CDX) | The only audio file in the library. Check its size against the Verpex AUP before hosting; otherwise host it on groups.io or an external audio host |

### 5.8 Legacy static-site files (2004–2015)

| # | File / last-known URL | What it is | Where linked | Copy | Notes |
|---|---|---|---|---|---|
| LEG-1 | `http://www.spokares.org/Downloads/NIMSFiles.zip` | "Download NIMS files here!!" | Home page, 2008-01-31 to 2010-09-28 (LEG-IDX) | none (not in CDX) | 2008-era NIMS material, now obsolete. Point to current FEMA NIMS/IS courses |
| LEG-2 | `http://www.spokares.org/NetPreamble.pdf` | Net preamble as PDF | `NetPreamble.html`, 2008-04-09 | none | Superseded (by LEG-4, then OSD-48, then AG-1) |
| LEG-3 | `http://www.spokares.org/Net Preamble 9.1.2007.pdf` | Net preamble dated 2007-09-01 ("Zipped PDF file") | `Net Preamble 9.1.2007.html`, 2008-01-28 | none | Superseded |
| LEG-4 | `http://www.spokares.org/Net%20Preamble%203-2009.pdf` | One-page net preamble. PDF metadata: title "QST, QST, QST", created 2009-07-02 (Word 2007). Says 147.30 MHz with a 100 Hz tone | `NetPreamble.html`, 2010-09-24 to 2015-05-22 (LEG-NP) | **WB**: `research/wayback/snapshots/www.spokares.org/Net_20Preamble_203-2009.pdf` (135,161 B) | Contains **one individual's arrl.net email**. Redact it if the file is ever shown on a history page |
| LEG-5 | `http://www.spokares.org/Downloads/ARES%20NET%20ROSTER%20FEB09.doc` | Net roster, Feb 2009 ("Word Document") | `NetPreamble.html`, 2010-09-24 to 2015-05-22 | **WB**: `research/wayback/snapshots/www.spokares.org/Downloads/ARES_20NET_20ROSTER_20FEB09.doc` (63,488 B; **not opened**) | **PII**. Do not migrate. Consider asking the Internet Archive to exclude the URL (org decision). Consider deleting the local copy after that decision |
| LEG-6 | `http://www.spokares.org/FieldDay2012.pdf` | Field Day 2012 plan, 4 pp. Author metadata: Randall Jones; created 2012-06-11. Committee listed by name and callsign; goals, safety and schedule | Home page 2013-04-27 | **WB**: `research/wayback/snapshots/www.spokares.org/FieldDay2012.pdf` (327,288 B) | Contains **one individual's arrl.net email**. History only |
| LEG-7 | `http://spokares.org/Field%20Day%202013.pdf` | Field Day 2013 plan, 3 pp (Publisher 2007; created 2013-04-29) | Home page 2013-12-06 and 2015-09-27 | **WB**: `research/wayback/snapshots/spokares.org/Field_20Day_202013.pdf` (278,384 B) | No email or phone patterns found. History only |
| LEG-8 | `http://spokares.org/StatusCode.pdf` | ARES Alert Levels (Green/Yellow/Orange/Red), 1 p. Excel 2007 export, created 2013-09-24 | Home page 2013-12-06 and 2015-09-27 | **WB**: `research/wayback/snapshots/spokares.org/StatusCode.pdf` (302,620 B) | The same content lives on as article A-20. Generate a fresh printable from the new page instead |
| LEG-9 | `http://spokares.org/Memberlist.pdf` | "Current Member List" | Home page 2013-12-06 and 2015-09-27 | **WB**: `research/wayback/snapshots/spokares.org/Memberlist.pdf` (41,191 B; **not opened**) | **PII**. As LEG-5 |

- **Local copies:** the Wayback copies of LEG-4 to LEG-9 are also indexed in `research/content/index.md` ("archive-binary" rows).
- **Links that would republish PII:** `research/content/index.md` links LEG-5 and LEG-9 directly. Remove those two links from any page that gets published.

### 5.9 External documents linked from spokares.org (not hosted by the org)

| # | Title | Last-known URL (and current) | Linked from | Status 2026-09-25 |
|---|---|---|---|---|
| EXT-1 | ARRL ARES Task Book | spokares.org links `http://www.arrl.org/ares` (A-26 and the "ARRL ARES Task book" sidebar link). Direct files, as linked by AG-ACS and AG-ATB: `http://www.arrl.org/files/file/ARES%20Taskbook%20July%202024.doc` and `…July%202024.pdf` | A-26, sidebar | 200 (all three) |
| EXT-2 | Spokane County Volunteer Emergency Worker Application | `https://www.spokanecounty.org/FormCenter/Emergency-Management-31/Volunteer-Emergency-Worker-Application-276` now redirects to `https://www.spokanecounty.gov/FormCenter/Emergency-Management-31/Volunteer-Emergency-Worker-Application-276` | A-26 | 200. Use the .gov URL |
| EXT-3 | ARRL Radiogram Form | Joomla weblink `/index.php/operations/forms?task=weblink.go&id=1` (target not captured). Current ARRL fillable form: `https://www.arrl.org/files/media/Group/Fillable%20Radiogram%20Form.pdf` (linked by AG-GK) | Operations → Forms | 200 |
| EXT-4 | FEMA ICS Forms, home page | Joomla weblink `…/operations/forms?task=weblink.go&id=9`. Current: https://training.fema.gov/emiweb/is/icsresource/icsforms/ | Operations → Forms | 200 |
| EXT-5 | Mel Ming N7GCO RFI video, February 2021 (.mp4) | `https://www.dropbox.com/sh/8senze6ryl36qbm/AADWm1Qq19JE9BrsrJytC0N1a/Mel%20Ming%20N7GCO%20RFI%20February%202021.mp4?dl=0`, which now redirects to a Dropbox `scl/fo/…` URL | Digital Messaging → RFI menu | 200. Dropbox links are fragile; ask to mirror or link a YouTube copy |
| EXT-6 | RATPAC Video Presentations list | `https://docs.google.com/spreadsheets/d/e/2PACX-1vTVywG1s025_zsAaKrn8Lr_DPGT6tGV0SVsV5LEdBA91dFCYk_LP8S3kXhMcD9vXFM3Cg-q468jf6Nd/pubhtml` | Downloads menu ("RATPAC Videos"); also AG general-information | 200 |
| EXT-7 | FEMA "Emergency Supply List" | `https://www.fema.gov/media-library-data/1390846764394-dc08e309debe561d866b05ac84daf1ee/checklist_2014.pdf` | `/index.php/operations/shelter-in-place` (2019-07-23 capture) | **403 (dead)**. Replace with https://www.ready.gov/kit (200) |

### 5.10 Documents on ag7qp.com that the Library needs (Spokane / ARES-ACS / Task Book / ICS pages)

- **Common URL prefix:** `https://assets.zyrosite.com/AVLpnMOEDxUWzNvE/`
- **Wayback:** none of these assets is archived. The CDX has only `traffic.txt` under that prefix.

| # | Link label (page) | File (suffix after prefix) | Notes |
|---|---|---|---|
| AG-1 | "Preamble/Script" (AG-SPK) | `net-preamble---2026-03-04-J61T3W2cH82FGj2t.docx` | Current repeater-net script. Successor to OSD-48 and A-8. Ask AG7QP for the master and publish it as both PDF and DOCX |
| AG-2 | "Preamble/Script for Simplex Net" (AG-SPK) | `net-preamble---simplex--2026-03-04-9FtuWfxT8EFGythy.docx` | Successor to OSD-49 |
| AG-3 | "Preamble/Script for GMRS Net" (AG-SPK) | `net-preamble---gmrs-2025-08-19-xpP0rpmqZzfXF6F5.docx` | New since 2024; no spokares.org predecessor |
| AG-4 | "ARES-ACS Roster" (AG-SPK) | `net-roster---2026-08-04-t4N3kbUrEvrZOLej.pdf` | **PII. Not downloaded or opened.** Publicly reachable today. Recommend groups.io Files only, and suggest AG7QP remove the public link |
| AG-5 | "ARES Application Download" (AG-ACS) | `spokane-county-ares-membership-application---2024-11-07-mnlqEgVzp6I9JPDk.docx` | **Canonical application.** Successor to OSD-7. Publish it as a fillable PDF plus DOCX. A completed form is PII, so route submissions by email, not a web store |
| AG-6 | "application form here" (AG-ATB) | `spokane-county-ares-membership-application---2024-09-15-AzGez9VZg6I863zL.docx` | An older version is still linked. Ask AG7QP to point /ares-task-book at AG-5 |
| AG-7 | "ACS Task Book here" (AG-CTB); "ACS Task Book is a Word file" (AG-ACS) | `acs-task-book-requirements---version-2024-08-09-A1awllqgVnC3Dyjq.docx` | Current ACS qualification requirements. Supersedes OSD-27 |
| AG-8 | "( pdf )" beside "Standing Order 1b – What to do if something happens and you haven't received instructions from your EC or AHJ" (AG-ACS) | `ewa-sop-1b---2026-01-16-dyxpta3oxpjzPI4U.pdf` | An Eastern Washington **Section** document dated 2026-01-16. Link to the SEC's copy rather than re-hosting a copy that could drift |
| AG-9 | "Description/Operation Manual" under "I am Safe Messaging" (AG-ACS) | `ai-am-safea-messages-standard-operating-procedures---july-2023-vHF65YgBm7fvDZIk.docx` | Per AG-ACS, developed by the Seattle Emergency Communication Hubs, Seattle ACS, Radio Relay International and the Winlink Development Team. AG-ACS says to contact AG7QP for the full file set ("too large to put on this website"). Link to the originator; the originator URL is **not verified** (the search budget was exhausted) |

### 5.11 Other standard forms linked from ag7qp ARES/ICS/go-kit pages

| # | Title | URL | Status 2026-09-25 | Note |
|---|---|---|---|---|
| FRM-1 | ICS 213RR Resource Request Message (FEMA v3) | https://training.fema.gov/emiweb/is/icsresource/assets/ics%20forms/ics%20form%20213rr,%20resource%20request%20message%20(v3).pdf | 200 | Linked on AG-GK |
| FRM-2 | ICS 214a Individual Log | https://cleanriverscooperative.com/wp-content/uploads/2023/07/ICS_214a_Individual_Log-1.pdf | 200 | Third-party copy. 214a is not on the FEMA EMI list. Pick an authoritative source before linking |
| FRM-3 | ICS 309 Communications Log (fillable) | https://archive.arrl-nfl.org/wp-content/uploads/2019/07/ICS-309-Fillable-Form.pdf | **404** | Dead link on AG-GK. 309 is not on the FEMA EMI list. Find an authoritative source |
| FRM-4 | ICS-309 Guidance (Hawaii ARES, SET 2022) | https://old.hawaiiares.net/set2022/9.%20ICS-309%20Guidance.pdf | 200 | Linked on AG-CTB as instructions |
| FRM-5 | WSDOT Form 580-020, Road and Bridge Assessment | https://wsdot.wa.gov/publications/fulltext/forms/580-020.pdf | 200 | Agency-authoritative; linked on AG-GK |

---

## 6. Recovery plan

### Goal

Get the **21 files marked RECOVER** (16 → migrate, 5 → review) off InMotion before the account or domain lapses.

- **Deadline:** the domain expires 2027-03-10. [Inference] The hosting term may end sooner; confirm with the account holder.
- **Also capture while there:** the OSDownloads metadata (to settle the 32 unexplained IDs) and the legacy static files still on disk, which are not in Wayback: `NIMSFiles.zip`, `TrafficHandler.pdf`, `TrafficHandler.mp3`, `NetPreamble.pdf` and `Net Preamble 9.1.2007.pdf`.

### Step 1: Secure access and take a full backup (week 1)

1. **Find who holds the InMotion cPanel login.** Per `research/orgs/relationships.md`, the EC and website admin is Asa Jay Laughton W7TSC. The SEC, Frank Hutchison AG7QP, confirmed the hack.
2. **In cPanel → Backup (or Backup Wizard), generate a Full Account Backup.** At minimum, take a Home Directory backup plus a MySQL database backup of the Joomla database. Download both **off-host immediately**.
3. **Ask InMotion support** whether it holds **older automatic backups** from before the compromise, and for how long it keeps them. [Inference] The compromise date is unknown, so an older restore point is the cleanest source for unmodified files.
4. **Do not "restore" anything onto the live account.** This is extraction only.

### Step 2: Locate the files (offline, on the downloaded copy)

1. **Query the database dump.** Replace `jos_` with the site's real table prefix:
   ```sql
   -- all library rows, published or not, with category
   SELECT d.*, c.title AS category, c.path AS category_path
   FROM jos_osdownloads_documents d
   LEFT JOIN jos_categories c ON c.id = d.cate_id AND c.extension = 'com_osdownloads'
   ORDER BY d.id;
   ```
   - [Inference] The column names (`cate_id`, the file-path/URL columns, the `downloaded` counter) follow OSDownloads 1.x. Check them with `SHOW COLUMNS FROM jos_osdownloads_documents;` first.
   - Map every row to its OSD-ID in §4. The 32 IDs in Appendix B will show up here as deleted-but-present, unpublished, or in the Digital Traffic category.
2. **Find the stored files.** [Inference] OSDownloads keeps uploads under the Joomla root, typically `media/com_osdownloads/files/` (sometimes with hashed names). Use the path column from step 1 as the authority. Then sweep the whole home directory for every document type, which also catches the legacy static files and `images/pdf_files/`:
   ```sh
   find ./homedir -type f \( -iname '*.pdf' -o -iname '*.doc' -o -iname '*.docx' -o -iname '*.ppt' \
        -o -iname '*.pptx' -o -iname '*.pps*' -o -iname '*.xls*' -o -iname '*.mp3' -o -iname '*.zip' \
        -o -iname '*.odt' -o -iname '*.odp' \) -printf '%TY-%Tm-%Td %s %p\n' | sort
   ```
3. **Keep a manifest** (`osd_id, original_path, sha256, size, mime (file --mime-type), mtime`). Do **not** commit binaries or PII to the repo. Store the manifest next to this inventory once it contains no PII.

### Step 3: Handle as untrusted (the host was compromised)

1. **Copy only document files.** Never PHP, JS or `.htaccess`.
2. **Check that each file's MIME type matches its extension.** Quarantine any "PDF" that is really HTML or PHP.
3. **Scan everything** with ClamAV (`clamscan -r`).
4. **Check Office files for macros** with `oletools` (`olevba`, `mraptor`) before opening them. Open only in a sandbox or VM, with macros disabled.
5. **Re-export each keeper to PDF** (and keep the DOCX/PPTX master where volunteers need to edit it). Publish only the re-exported files, which also strips any injected content.
6. **Compare titles with this inventory.** A file that changed after 2024-09-13 (by mtime or by content) is suspect.

### Step 4: Triage using this inventory

- **RECOVER → migrate (16):**
  - Presentations: OSD-75, 78, 63, 50, 23, 25, 20, 11, 19, 6, 5
  - Other: OSD-71 (Winlink exercises), OSD-64 (GPS conversion), OSD-21 (SignaLink packet), OSD-8 and OSD-9 (Traffic Handler PDF/MP3)
  - For each: confirm the author and permission, add a one-line description and date, and review currency (the Winlink and Ardop items date from 2018).
- **RECOVER → review (5):**
  - OSD-60 Simplex Net Reports (callsign lists?)
  - OSD-62 Derecho case study (rights)
  - OSD-61 WA Guard Cascadia (agency briefing: distribution and permission)
  - OSD-38 800MHz radios (SCEM sign-off; members-only if it shows county system details)
  - OSD-26 AREDN (is it current?)
- **PII (OSD-47, and any other roster-like file found in the sweep):** move to groups.io Files (members-only) or delete. **Never upload to the new host.**
- **RETIRE:** these can be archived offline with the backup. Nothing needs to be republished.

### Step 5: Secondary recovery sources (for anything the backup lacks)

- **Authors:**
  - David Oglesby N7LL (OSD-78)
  - Todd Cady K7PKT (OSD-20, OSD-10)
  - Aaron Noack AD7DD (OSD-5, OSD-6)
  - WA7AQH (OSD-25)
  - Mel Ming N7GCO (OSD-23, OSD-50, EXT-5)
- **Groups.io:** https://spokaneares-acs.groups.io/g/main/files. The page responds 200; its contents are members-only and **not checked**.
- **AG7QP:** already holds the current preambles, application and task book, and the "I am Safe" file set.
- **Wayback:** holds nothing further. The CDX was exhausted for `spokares.org`, `media/com_osdownloads`, `images/pdf_files`, `Downloads/`, `TrafficHandler*`, `NetPreamble*` and `*.zip`.

### Step 6: Publish (new Library)

1. **Organise the Library by task, not by old OSDownloads category:**
   - Net operations: AG-1 to AG-3
   - Join & qualify: AG-5, AG-7, EXT-1, EXT-2
   - Forms: links only (EXT-3, EXT-4, OSD-13 to OSD-16, FRM-1, FRM-5, plus authoritative 214a/309 sources once found)
   - Training presentations: the recovered OSD items
   - Reference and SOPs: AG-8, AG-9, the A-20 printable, and A-62 as a page
2. **Give each entry** a title, one-line description, author and callsign where given, date or version, format and size, and a last-reviewed date. Use file names with ISO dates (for example `net-preamble-repeater-2026-03-04.pdf`).
3. **No email or "agree" gate** (none was ever active).
4. **Keep the site-wide total within the AUP assumption** in `site-functionality-analysis.md` (a small documents library). Measure actual sizes after recovery; the MP3 (OSD-9) is the likely outlier.

### Step 7: Redirects and link hygiene

1. **301** every old OSDownloads URL family to the matching new Library anchor, or to the FEMA/ARRL target for LINK items:
   - `/index.php/downloads/<cat>`
   - `/index.php/downloads/<cat>/category[/<cat>]`
   - `/index.php/downloads/<cat>/file/<cat>/<alias>`
   - `/index.php/downloads/<cat>/routedownload[/<cat>]/<alias>`
   - `/index.php/downloads/<cat>/<alias>`
2. **Priority redirects:**
   - `…/forms-and-information/routedownload/forms-and-information/ares-races-membership-appplication` (A-26; 280 downloads) → the AG-5 file
   - `…/mentoring/routedownload/mentoring/traffic-handler-{pdf,mp3}` (A-25; 8,685 article hits)
   - `…/downloads/net-preambles-and-scripts` (A-8, and the Operations menu)
3. **Legacy static URLs:**
   - `/NetPreamble.html` and `/StatusCode.pdf` → the Net page and the Alert Levels page.
   - `/Memberlist.pdf` and `/Downloads/ARES%20NET%20ROSTER%20FEB09.doc` → **410 Gone**, not a redirect.
4. **Fix the ag7qp.com items the rebuild must not copy:** the dead ICS-309 link (FRM-3), the stale application link (AG-6), and the public roster link (AG-4).

### Step 8: PII clean-up decisions (leadership)

1. **Decide whether to ask the Internet Archive to exclude** `spokares.org/Memberlist.pdf` and `www.spokares.org/Downloads/ARES%20NET%20ROSTER%20FEB09.doc`. [Inference] The Archive handles removal requests from site owners; confirm its current process.
2. **After that decision, delete the local copies** of LEG-5 and LEG-9 from `research/wayback/snapshots/`, and remove their links from `research/content/index.md`.
3. **Ask AG7QP to move the 2026 net roster (AG-4)** from the public page to groups.io.

## 7. Open questions for leadership

- **Q1: InMotion.** Who holds the cPanel credentials, when does the hosting term end, and do pre-compromise backups exist?
- **Q2: Master owners.** Who owns each current master (preambles, application, task book) going forward? AG7QP hosts them today.
- **Q3: Alternate-repeater preamble.** Is a 146.880 alternate-repeater preamble still needed? The IEVHFRA MOU (A-68, 2023-05-06) names 146.88 as the alternate, but the 2026 set on ag7qp has repeater, simplex and GMRS versions only (by link label).
- **Q4: Sensitive presentations.** May OSD-38 (800 MHz), OSD-61 (WA Guard) and OSD-18 (Lilac ops) be public, or are they members-only?
- **Q5: Refresher decks.** Should the abbreviated FEMA IS refresher decks (OSD-39, 44, 45, 46) be kept as local training aids alongside the FEMA links? A trainer would need to re-validate them against the current course versions.
- **Q6: Forms set.** Which ICS 214a and ICS 309 versions are authoritative for Spokane ACS (county, CISA/AUXCOMM or another source)? Also, should the ACS Field Operations Guide on groups.io (see `design/content-brief.md` §9) be linked from the Library?

---

## Appendix A: other ag7qp.com assets (not Spokane library)

These are hosted on ag7qp.com (assets.zyrosite.com) for the SEC's own pages (CERT, radio programming, EWA Section nets, licensing). They are **out of scope** for the Spokane Library. **Link to ag7qp.com if needed; do not copy.** There are 17 files:

- **General information:** `47-cfr-part-97-up-to-date-as-of-1-01-2026-…pdf`. The authoritative source is the eCFR: https://www.ecfr.gov/current/title-47/chapter-I/subchapter-D/part-97 (200).
- **CERT PTBs:**
  - `fema-nqs_cert-volunteer_draft-position-task-book-…pdf`
  - `nqs-cert-volunteer-ptb-word-version-…docx`
  - `fema-nqs_cert-team-leader_position-task-book-…pdf`
  - `nqs-cert-team-leader-ptb-word-version-…docx`
  - `evaluation-record-form-…docx`

  These are FEMA NQS documents, so FEMA is authoritative.
- **Programming radios:**
  - `manually-programming-your-baofeng-uv-5ra-…pdf`
  - `…uv-5ra---half-page-size-…pdf`
  - `baofeng-hts-…docx` (UV-82)
  - `manually-programming-yourtidradio-td-h3---half-page-size-…pdf`
  - `icom-ic-7300-cheat-sheet-…docx` ("Snohomish County Version")
  - `how-to-program-qyt-kt-8900-…docx`
  - `tyt-th-9800-…docx`
  - `yaesu-ftm-6000-one-pager-…docx`
- **Eastern Washington Section:**
  - `ewsen-vhf-net-preamble-your-call-sign-version-2024-12-04-…docx`
  - `ewsen-hf-net-preamble-your-call-sign-version-2025-10-28-…docx`
  - `vhf-roster---2025-10-28-…pdf`: **PII, not downloaded.**
- **Also linked from ag7qp (not zyrosite-hosted):**
  - three Dropbox license-study folders (Technician/General/Extra)
  - BSA Radio merit badge worksheet (usscouts.org)
  - WA DNR earthquake homeowners' guide
  - Winlink mapping forms PDF and a Florida Winlink SITREP PDF
  - ARRL repeater and license-renewal PDFs
  - a Google Sheet on the home page (W1AW/7 operator sign-up; not a library document)

## Appendix B: OSDownloads ID gaps

- **Known IDs (46):** 1–27, 38, 39, 44–50, 59–64, 71, 74, 75, 78.
- **Unexplained IDs (32):** 28–37, 40–43, 51–58, 65–70, 72, 73, 76, 77.
- **Possible explanations** [Inference]:
  - deleted items (the database may still hold the row)
  - unpublished items
  - items in *Digital Traffic Information and Files*, which was in the 2024 menu but not captured in 2024
  - items behind an access level the anonymous crawl could not see
- **The query in §6 Step 2 settles this.** Add any real documents found as new rows.

## Appendix C: verification log (2026-09-25)

**spokares.org today:**

| URL | Status |
|---|---|
| `/index.php/downloads/presentations` | 500 |
| `/index.php/downloads/forms-and-information/routedownload/forms-and-information/ares-races-membership-appplication` | 500 |
| `/index.php/downloads/mentoring/routedownload/mentoring/traffic-handler-pdf` | 404 |
| `/index.php/downloads/ics-forms` | 404 |
| `/Memberlist.pdf` | 404 |
| `/NetPreamble.html` | 404 |

**Authoritative targets:**

| Target | Status |
|---|---|
| FEMA ICS forms index | 200 |
| FEMA ICS 201 v3, 213 v3, 213RR v3 | 200 |
| FEMA ICS 205 v3.1, 214 v3.1 | 200 |
| FEMA ICS 205, 214, 309 at the old "(v3)" URL pattern | 404 |
| ARRL ARES Task Book July 2024 .doc and .pdf | 200 |
| ARRL Fillable Radiogram | 200 |
| ARRL Anderson powerpole PDF | 200 |
| arrl.org/ares | 200 |
| Spokane County VEW application (redirects .org → .gov) | 200 |
| Dropbox Mel Ming RFI mp4 (redirects to `scl/fo`) | 200 |
| RATPAC Google Sheet | 200 |
| FEMA `checklist_2014.pdf` | 403 |
| ready.gov/kit | 200 |
| eCFR Part 97 | 200 |
| FEMA IS-100.c, IS-200.c, IS-700.b, IS-800.d course pages | 200 (redirected) |
| winlink.org ICS forms page | 403 (bot-blocked; not verified) |
| fema.gov NIMS page | 403 (bot-blocked; not verified) |
| ICS 214a third-party copy | 200 |
| ICS-309 fillable (arrl-nfl archive) | **404** |
| Hawaii ARES 309 guidance | 200 |
| WSDOT 580-020 | 200 |
| groups.io files page | 200 (contents not checked) |

**Wayback CDX:**
- `spokares.org` domain: 946 rows; the file types are 5 `application/pdf` and 1 `application/msword`, and that is all.
- `assets.zyrosite.com/AVLpnMOEDxUWzNvE/`: 1 row (`traffic.txt`).
- No capture of any OSDownloads file body.

**Counting:** the §3 counts were produced by a script that parses the §4 table rows (76 rows: OSD 46, LEG 9, EXT 7, AG 9, FRM 5).
