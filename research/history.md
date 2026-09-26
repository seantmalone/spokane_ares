# Spokane County ARES-ACS / RACES: history of the organization and its website

- **Prepared:** 2026-09-25, from the local research corpus. No new web requests were made for this file.
- **Span:** the earliest dated content found (2000, inside the 2004 site) through 2026-09-25.
- **Conventions**
  - All paths are relative to `research/`.
  - A date shown as "seen" is the Wayback capture date or the page's own date. It is **not** proof that something started or ended on that day.
  - Text marked **INFERENCE** is our reasoning, not a statement found in a source.
  - People are listed by role, name (as printed in the source) and callsign only. No personal contact details are copied here, even where the old pages published them.
- **Main sources**
  - `content/archive/*.md`: 108 Wayback pages, 2004-2024. The front matter gives the capture timestamp.
  - `content/live/*.md`: 26 Joomla articles, with their published dates.
  - `wayback/cdx-spokares.txt`: 946 unique URLs, each with its **first** capture time (the file is URL-collapsed).
  - `wayback/manifest.tsv` and `wayback/snapshots/**`, including the Field Day 2012 and 2013 flyers and `StatusCode.pdf`.
  - `data/calendar-events.json`: 232 JCal Pro events.
  - `orgs/relationships.md` and `orgs/_backlog.md`: the org map.
  - `site-functionality-analysis.md`.
  - `sources/government/*`: CEMP 2021, ESF-2 (about 2014), COAD minutes.
  - `external/ag7qp/*`: ag7qp.com as captured 2026-09-25.
- **Not opened, on purpose:** `wayback/snapshots/spokares.org/Memberlist.pdf` (2015) and `wayback/snapshots/www.spokares.org/Downloads/ARES_20NET_20ROSTER_20FEB09.doc` (2009) are member lists and likely PII. The same goes for the 2026 roster PDF on ag7qp.com.

---

## 0. Key points

1. **The organization is older and steadier than its websites.**
   - The **Tuesday 2000 net on 147.30 MHz** runs continuously from the earliest capture (2004) to 2026.
   - So does the **3rd-Thursday monthly meeting**, first seen as "Thursday, 18 March 2004 ARES Monthly Meeting". **INFERENCE:** that date was the 3rd Thursday, and the pattern is explicit from 2017 onward.
   - Bloomsday, the Lilac/Armed Forces Torchlight Parade, SET, EOC-to-EOC exercises, SKYWARN Recognition Day and Field Day recur across 20+ years.
2. **The website has had five eras.**

   | Era | Dates | Platform / host |
   |---|---|---|
   | Static HTML | about 2004 to 2015 | Framesets and GIF menus; hosting donated by Sisna |
   | Wix interim | 2015-16 | Wix |
   | Joomla 3 | 2017 to 2026 | InMotion (cPanel) by 2023 at the latest; the 2017 host is unverified |
   | Compromised | 2026 | Joomla 3, still on InMotion |
   | Interim | 2026 | Temporary page on the SEC's ag7qp.com (Hostinger/Zyro) |

   Each migration dropped content and broke every old URL.
3. **The name drifted with the county program.** ARES/RACES (2004) → "ARES - RACES" (2017) → ACS introduced by the county in 2019 → "ARES-ACS", the site logo by early 2021 → "ARES/ACS/RACES" in the 2023 MOU → "ARES-ACS" in 2026. The county agency went from Spokane City/County DEM, to "Greater Spokane Emergency Management" (GSEM, 2017-2019), to SCEM after the City of Spokane left in 2020.
4. **The EC/RO role changed hands about four times in 25 years, but the site never kept up.**
   - The ARES/RACES Plan on the live site in 2026 is the "Master 7.13" text. It still names W7UWC as EC/RO, a 2013-era AEC list, and the ECC at 1618 N Rebecca.
5. **Both site generations decayed the same way.** One volunteer webmaster, documents exported from Word, features added and then abandoned, no owner or review date per page, member PII published, and a platform left unpatched after its end of life.

---

## 1. Website eras at a glance

| Era | Evidence window | Platform, host, maintainer | What it carried | How it ended |
|---|---|---|---|---|
| **Pre-domain content** | Content dated 2000-2003 inside the 2004 captures | Unknown | NCS principles (K7BFL, dated Dec 23, 2000), Plan attachment dated Oct 1, 2000, mobilization tree dated June 11, 2003, SET 2002 photos | **INFERENCE:** the material existed before the domain (registered 2004-03-10, per `orgs/relationships.md` §1). It may have been hosted elsewhere first. |
| **Static HTML (v1)** | First capture 2004-04-16; last new static URLs 2015-02-24/25 | Hand-built frameset site (`Staff.html` and `Calendar.html` are framesets) with GIF rollover menus (`MenuGraphics/*-On.gif`). Home page credit: "This space donated by Sisna of Eastern Washington and Northern Idaho". Webmaster "The WebSlave", Randall Jones KC7GKX (2004). | Plan, preamble, NCS principles, ICS orientation (a 1994 FEMA module), equipment lists, links, testing schedule, calendar, staff bios, mobilization tree, Pactor gateway how-to, repeater etiquette, photo galleries, Field Day flyers, alert levels, member list | Replaced by Wix (2015-16). The 2004-2015 files are only in Wayback. |
| **Wix interim** | 2015-16 | `aresraces.wix.com/spokane` (feed captured 2016-02-16). The root showed Wix on 2016-01-09. | Blog/feed items, e.g. Winter Field Day 2016 at Five Mile Prairie | Replaced by Joomla (2017). A Bonner County ARES page still links to the dead Wix URL. |
| **Joomla 3 (v2)** | First article 2017-05-08; "Welcome to the New Web Site" 2017-09-24; heavy capture Jul-Dec 2019 | Joomla 3, `js_wright` template. On InMotion (cPanel) per the current NS records, with cPanel hostnames captured from 2023. `orgs/_backlog.md` says InMotion "from 2017"; unverified. It uses about 15 third-party extensions. Webmaster and later EC: Asa Jay Laughton W7TSC, who wrote 24 of the 26 surviving articles. | Articles, JCal calendar and net-control rota, OSDownloads library, forum, gallery, maps, online application forms, news blog (2017-2019) | Decayed from 2020. Joomla 3 has been EOL since Aug 2023. Downloads and contact were broken by 2026. The site was hacked in 2026. |
| **Interim (ag7qp.com)** | Seen 2026-09-25; sitemap lastmod 2026-09-23 | Hostinger/Zyro site run by the EWA SEC, Frank Hutchison AG7QP | Net-control rota, meeting schedule, preambles, roster PDF, Winlink assignments, task-book guides | Still current |

**Sources**
- Static era: `content/archive/20040804-index-html.md` (WebSlave, Sisna credit), `wayback/snapshots/www.spokares.org/Staff.html` and `Calendar.html` (frameset text "your browser doesn't appear to support frames"), `wayback/cdx-spokares.txt`.
- Wix era: `orgs/relationships.md` §1, citing `web.archive.org/web/20160109192152/http://spokares.org/`, and §23, citing the Wix feed; `orgs/_backlog.md` (Bonner County link).
- Joomla era: `content/live/01-under-construction.md`, `content/archive/20190724-index-php-28-welcome-to-the-new-spokane-county-ares-races-we.md`, `site-functionality-analysis.md` §0 and §3.7.
- Interim: `external/ag7qp.com.html`, `external/ag7qp/sitemap.xml`.

**What the CDX shows** (new URLs by first-capture year, from `wayback/cdx-spokares.txt`)

| Years | New URLs | What they are |
|---|---|---|
| 2004-05 | 26 HTML, 33 GIF/JPG | The static site's build-out |
| 2008-15 | About 13 HTML, 10 PDF/DOC, about 60 images | Additions: preambles, Plan versions, Field Day flyers, galleries |
| 2016 | 0 | Consistent with the Wix period |
| 2017 | 1 | `Graphics/2017 Field Day.png`, captured 2017-06-11 |
| 2018-19 | 408 `index.php` URLs plus Joomla assets | Peak: 110 new URLs in Jul 2019 alone |
| 2020-23 | Mostly asset-hash changes | Stagnation |
| 2024 | 186 | Mostly the `mail.spokares.org` mirror and webmail locales |
| 2025 | 14 | Asset-hash changes only (2025-06-10) |

**INFERENCE (2017 overlap):** the 2017 image sits under the old static `Graphics/` path. That suggests the legacy static files were carried onto the new Joomla host, or coexisted with it, in mid-2017.

---

## 2. Dated timeline

### 2.1 Before 2004 (dates printed inside the 2004 site)

| Date | Event | Source |
|---|---|---|
| 1928 | The Radio Amateur's Code (Paul M. Segal, W9EEA) is reproduced on every version of the site | `content/archive/20040416-thecode-html.md`, `content/live/06-the-radio-amateur-code.md` |
| 1984-08-03 | The FEMA-ARRL MOU cited as authority in every Plan version | `content/archive/20040416-theplan-html.md` §II |
| 1994 | Start of an informal ARES/RACES operating plan with county fire agencies (Fire District 9, DEM, Combined Communications Center) for major fire and rescue incidents | `content/archive/20040416-theplan-html.md`, Attachment 11 |
| Oct 1994 | "ICS Orientation Module 1" reference text, later posted as the site's ICS page | `content/archive/20040416-incidentcommand-html.md` |
| 1999 | The group starts running a SKYWARN Recognition Day station ("each year since 1999") | `content/archive/20190724-index-php-32-skywarn-2017-recap.md` |
| 2000-10-01 | Date on the Plan's telephone mobilization tree (Attachment 4) | `content/archive/20040416-theplan-html.md` |
| 2000-12-23 | "Principles of a Net Control Station", published by Don Felgenhauer K7BFL | `content/archive/20040416-netcontrol-html.md` |
| 2002 | SET 2002 photos (ladder tower, van operations, N7LVO packet antenna) | `content/archive/20040804-index-html.md`, `20080128-pictures-html.md` |
| 2002-11-05 | K7BFL-authored message form, dated "11/5/2002", reproduced in the 7.13 Plan | `content/archive/20150224-ares-plan-master-7-13-html.md` |
| 2003-06-11 | Telephone mobilization tree (callsign groups A-F) | `content/archive/20040416-tree-html.md` |
| Undated, pre-2004 | Repeater Etiquette essay: 147.30 carries "the RACES call W7GBU", licensed to Spokane County and maintained by DEM. It names Deputy Director Dave Byrnes, and N7VBW as RACES Officer and County EC. | `content/archive/20040804-repeateretiquette-html.md` |

### 2.2 2004-2014: the static site

| Date (seen) | Event | Source |
|---|---|---|
| 2004-03-10 | spokares.org registered | `orgs/relationships.md` §1 (RDAP) |
| 2004-04-16 | First Wayback capture. The site has the Plan (with an MS Word ZIP download), preamble, NCS principles, ICS module, equipment lists, links, testing schedule, the W7GBU page, the Pactor gateway page and the mobilization tree. | `wayback/cdx-spokares.txt`, `content/archive/20040416-*.md` |
| 2004 (Plan text) | The Plan names repeater **WC7AAT** on 147.30, HF Pactor gateway W7GBU-7 and packet node NQ7M. It places the EOC at the DEM building, 1121 W Gardner. **INFERENCE:** WC7AAT is probably the "old ARES call sign" that the county retired before W7GBU was adopted (see the W7GBU history article). | `content/archive/20040416-theplan-html.md`, `content/live/05-w7gbu-call-sign.md` |
| 2004 calendar (captured 2004-04-17) | March 18 monthly meeting; Frozen Flatlands bike race (Apr 4); Bloomsday (May 2); Armed Forces Day / Lilac Parade (May 15); EOC to EOC (May 29); Hoopfest and Field Day (Jun 26-27); Settlers Day (July); Tour de Loc, Spokane Hamfest and ARRL EWA Section Convention (Sep 25); SET (Oct 2) | `content/archive/20040417-calendars-html.md` |
| 2004-08-04 | Home page: "Remember the Tuesday night ARES net. 8:00pm on the 147.30+ (100Hz) Spokane County Department of Emergency Management Repeater"; "Hoopfest is **mission scrubbed!**" | `content/archive/20040804-index-html.md` |
| 2004-09 | Staff pages (SM, SEC, EC, AECs), each with generic ARRL role text | `content/archive/20040919-stafflist-html.md`, `20040922-aecs-html.md`, `20040925-ka7csp-html.md`, `20040926-kd7amv-html.md`, `20040929-wa7lnc-html.md` |
| 2005-01-23 | New Section Manager page (KB7HDX) | `content/archive/20050123-kb7hdx-html.md` |
| 2006 | Field Day 2006 photo gallery ("Don on WL2K", "Youth in Action", "New Members Taking Notice") | `content/archive/20080128-pictures-html.md` |
| 2006-10-04 | The HF Pactor/packet gateway (W7GBU) is noted as "not in operation" | `content/archive/20120207-aresplan25aug09-html.md` (Plan §8) |
| 2007-09-01 | Net preamble revision ("Net Preamble 9.1.2007.html"; members called in three suffix groups) | `wayback/snapshots/www.spokares.org/Net_20Preamble_209.1.2007.html` |
| 2008-01-28 | Pictures page; an "AE7RJ-Email" page shows an email as an image "to help reduce spam". **INFERENCE:** AE7RJ was a site contact in 2008. A Randall Jones AE7RJ is on the 2026 net rota; whether he is the 2004 webmaster KC7GKX is unconfirmed. | `content/archive/20080128-ae7rj-email-html.md`, `external/ag7qp/spokane-county-ares-acs.html` |
| 2008-11-20 | Staff pages updated: the EC is now shown as W7UWC; AA7RT is shown as DEC | `content/archive/20081120-w7uwc-html.md`, `20081120-aa7rt-html.md` |
| 2009-01-05 | Volunteer registration moves to a downloadable, fillable county form that "does away with the social security number" | `content/archive/20090105-regcards-html.md` |
| Feb/Mar 2009 | "ARES NET ROSTER FEB09.doc" and "Net Preamble 3-2009.pdf" posted publicly | `wayback/cdx-spokares.txt` |
| May 2009 | EOC-to-EOC exercise photo gallery (`Graphics/EOC2EOCMay09/`) | `wayback/cdx-spokares.txt` |
| 2009 | Field Day 2009 gallery: GOTA stations, Winlink station, PSK31 | `wayback/cdx-spokares.txt` (`Graphics/FieldDay2009/`) |
| 2009-08-25 | Plan revision "ARESPlan25Aug09". It moves the ECC to the DEM building at 1618 N Rebecca and puts the ARES position and the Winlink 2000 gateway W7GBU-10 (145.090) at the Combined Communications Building (CCB), 1620 N Rebecca. It adds 5th-Saturday EOC-to-EOC exercises with Camp Murray. | `content/archive/20120207-aresplan25aug09-html.md` |
| 2011 | KXLY reports about 40 active members and an ARES shelter station during the Valley View Fire | `orgs/relationships.md` §1, `orgs/_backlog.md` (Red Cross section) |
| 2012-06-23/24 | Field Day with the Inland Empire VHF Club at the Mt. Spokane ski lodge (5,000+ ft), as WR7VHF: three stations on emergency power, and a coordinating committee | `wayback/snapshots/www.spokares.org/FieldDay2012.pdf` |
| 2013-04-27 | Plan "Master 8.09" captured (a Word "Save as HTML" export; its `_files/` support folder returned 404s in 2014) | `content/archive/20130427-ares-plan-master-8-09-html.md`, `wayback/cdx-spokares.txt` |
| 2013-06-22/23 | Field Day with IEVHFRA again at Mt. Spokane, as WR7VHF. Adds a balloon-lifted long-wire antenna and a "Winlink 2000/RMS Express Demo". | `wayback/snapshots/spokares.org/Field_20Day_202013.pdf` |
| 2013-08-31 | EOC-to-EOC exercise photos (`Graphics/EOC2EOCAug2013/`) | `wayback/cdx-spokares.txt` |
| About 2013 | Photos from the Puyallup HamFest (year unknown) | `wayback/cdx-spokares.txt` |

### 2.3 2015-2016: end of the static site and the Wix interim

| Date (seen) | Event | Source |
|---|---|---|
| 2015-02-24 | Last static captures: Plan "Master 7.13", MentorPrograms (Traffic Handler PDF and MP3), `StatusCode.pdf` (the ARES Alert Levels, Green to Red), and a public `Memberlist.pdf`. The 7.13 Plan drops the Holiday Traffic Contest and changes "Telephone Mobilization Tree" to "Telephone/Text/email Mobilization". | `content/archive/20150224-*.md`, `wayback/snapshots/spokares.org/StatusCode.pdf`, `wayback/cdx-spokares.txt` |
| 2015-16 | Wix interim site at aresraces.wix.com/spokane | `orgs/relationships.md` §1 |
| 2016 | Winter Field Day at Five Mile Prairie (from the Wix feed) | `orgs/relationships.md` §23 |
| 2016-06-07 | County COAD minutes: "ARES/RACES and Amateur Radio": a ham class on June 22 and an on-air demo after the meeting | `sources/government/spokane-COAD-minutes-2016-06-07.txt` |

### 2.4 2017-2019: Joomla launch, the most active web period

| Date | Event | Source |
|---|---|---|
| 2017-02-15 | FCC grant date of the W7GBU club license held by the "Spokane County ARES RACES Support Group" (previous call KC7SOT; current trustee K7DSR). **INFERENCE:** the call was a county RACES license in 2004, so this grant probably marks a move to a club license rather than a first grant. Unverified. | `orgs/relationships.md` §2 (ULS), `content/archive/20040804-repeateretiquette-html.md` |
| 2017-05-08 | First Joomla article, "Under Construction" | `content/live/01-under-construction.md` |
| 2017-06-03/04 | Bulk load of core articles: Plan, staff, About, W7GBU history, Code, Etiquette, preamble, NCS, ICS, test sessions, Terms, Privacy | `content/live/02..13-*.md` |
| 2017-06-07 | Test post by the webmaster, noting that the editor could not upload photos | `content/archive/20190724-index-php-19-test-loading-photo.md` |
| 2017-06-11 | `Graphics/2017 Field Day.png` captured under the old static path | `wayback/cdx-spokares.txt` |
| 2017-07-22 | Articles added: Alert Levels (from `StatusCode.pdf`), equipment lists, email list, links, mentor programs, how to join | `content/live/20..26-*.md` |
| 2017-08-12 | First "Second Saturday Amateur Workshop" (digital/Winlink focus); JCal net-control rota begins (Aug 8) | `data/calendar-events.json` |
| 2017-09-05 | COAD minutes: ARES/RACES message-handling training, HamFest Sep 23 at University HS, license class Oct 7 | `sources/government/spokane-COAD-minutes-2017-09-05.txt` |
| 2017-09-24 | "Welcome to the New Web Site", by Asa Jay W7TSC. To-dos listed: SSL certificate, email, member logins, frequency list, feedback form. | `content/archive/20190724-index-php-28-welcome-to-the-new-spokane-county-ares-races-we.md` |
| 2017-10 | The October workshop moves to Oct 21 because of other county training at the Fire Training Center | `content/archive/20190724-index-php-29-date-change-october-digital-workshop.md` |
| 2017-11-25 | QRP Day on the Noontime Net (40 m), contributed by Lyle Loshbaugh | `content/archive/20190724-index-php-31-qrp-day-on-the-noontime-net.md` |
| 2017-12-01/02 | SKYWARN Recognition Day station at NWS Spokane (Airway Heights). New members operate HF. Recap by W7UWC and AA7RT. | `content/archive/20190724-index-php-32-skywarn-2017-recap.md` |
| 2018-01-13 | Winlink scripting workshop (the "Scripting in Winlink 101" deck) | `content/archive/20190724-index-php-35-2018-01-13-digital-workshop.md` |
| 2018-01-18 | "Go-kit Night" at the monthly meeting | `content/archive/20190724-index-php-36-go-kit-night.md` |
| 2018-02-27 / 03-16 | GSEM requires in-person helicopter training for deployable members; a session is held at the Sheriff's Training Center | `content/archive/20190724-index-php-40-helicopter-training.md` |
| 2018-03-02 | Welcome Letter and member requirements (IS-100/IS-700, background check, 6-month activity period) | `content/live/41-welcome-letter-and-member-requirements.md` |
| 2018-03-30/31 | 5th-Saturday multi-county exercise (outlying city halls, portable HF/VHF/UHF and Winlink, secondary repeater). The ICS plan and forms are in **Groups.io** Files. | `content/archive/20190724-index-php-44-2018-march-31-5th-saturday-exercise.md` |
| 2018-04-04 | Net preamble updated (adds +600 kHz offset and a crossband call) | `content/live/08-net-preamble-script.md` |
| 2018-04-07 | The workshop moves from the Spokane County Fire Training Center to the DEM building, 1121 W Gardner, and broadens beyond Winlink (ARDOP/VARA on Apr 14) | `content/archive/20190917-index-php-45-ares-amateur-workshop-digital-training-and-more.md` |
| 2018-04-14/15 | Four members attend the 20th Communications Academy in Seattle | `content/archive/20190826-index-php-46-2018-communications-academy.md` |
| 2018-04-17 | Post: "Current (2018-2019) ARES Monthly Meeting Location" (body not captured; alias "may-17-2018-meeting-location") | `content/archive/20190724-index-php-47-may-17-2018-meeting-location.md` |
| 2018-06-05 | First captures of the Joomla slider and logos: ARES, RACES, "Greater_Spokane_Emergency_Management", "Spokane_County_ARES_RACES_logo_dash", **Asisna.png** (the old host sponsor still credited). Slider images named `8091bb_<hash>.jpg`. **INFERENCE:** that is Wix media naming, so the images were carried over from the Wix site. | `wayback/cdx-spokares.txt` |
| 2018-06-23/24 | Field Day 2018 | `data/calendar-events.json` |
| 2019 | Online application (BreezingForms), Kunena forum (1 post), Event Gallery, FocalPoint maps test. OSDownloads carried download-gate markup (email plus "Tweet or Like"), but the gate was **present, never enabled**: all 66 gate flags are 0 (`documents-inventory.md` §2 item 6) | `content/archive/20190723-index-php-contact-ares-races-app-form.md`, `20190724-index-php-forum.md`, `20190820-index-php-operations-maps-testing.md`, `20190723-index-php-downloads-*.md` |
| 2019-03-30 | EOC-to-EOC 5th-Saturday exercise with 20+ operators at NWS, Sacred Heart, CCB (911 center), Fairchild AFB, the DEM building and high schools. Traffic with Stevens County; Pactor and VHF-packet Winlink from the ECC; WA ICS-213RR resource requests to Camp Murray. | `content/archive/20190826-index-php-51-2018-03-30-5th-saturday-exercise.md`, `20190826-index-php-52-recap-march-2019-exercise.md` |
| 2019-05-05 | AUXCOMM course announced (Hayden, ID, Jun 29-30) | `content/archive/20190724-index-php-53-auxcomm-training-idaho-june-2019.md` |
| 2019-06-19 | NWS radar outage (Jun 24 to Jul 5): call for spotters | `content/archive/20190724-index-php-54-attention-weather-spotters-2019.md` |
| 2019-07 to 09 | Kootenai County exercise (Jul 17), Cherry Picker's Trot (Jul meeting topic), Spokane Hamfest at University HS (Jul 28), Valleyfest (Sep 21) | `data/calendar-events.json`, `content/archive/20190826-index-php-calendars-calendar-jcalpro-31-other-events.md` |
| 2019-09-19 | "Mandatory Meeting: Membership Requirements Review" | `data/calendar-events.json` |
| 2019-09-21 | **"NEW - 2019 ARRL ARES and Spokane County ACS Requirements":** the county DEM implements an Auxiliary Communications System on the FEMA AUXCOMM model | `content/archive/20191021-index-php-55-new-2019-arrl-ares-and-spokane-county-acs-requi.md` |
| 2019-10-05 / 12-07 | Quarterly exercises: all members; "Hazard City" hospital deployment | `content/archive/20190826-index-php-calendars-calendar-jcalpro-28-upcoming-exercises.md` |
| 2019-10-23 | Spokesman-Review: the City of Spokane plans to manage its own emergencies | `orgs/relationships.md` §7 |
| 2019-11 | Spokane takes EWSEN Monday and WSEN District 9 net-control shifts | `data/calendar-events.json` |
| 2019-11-23 | **Last news posts** (December Fldigi workshop; SKYWARN Recognition Day Dec 6-7). The Joomla news blog ends here. | `content/archive/20191212-index-php-56-2019-december-ssaw.md`, `20191212-index-php-57-2019-skywarn-recognition-day.md` |

### 2.5 2020-2025: the ARES-ACS era and slow decay

| Date | Event | Source |
|---|---|---|
| 2020 | The City of Spokane leaves joint emergency management. The Inter-Local Agreement now covers the unincorporated county, Spokane Valley and 11 other cities and towns, but not the City. | `orgs/relationships.md` §7, `sources/government/spokane-county-CEMP-2021-03.txt` |
| 2020 (capture 2020-09-27) | County page 1813 is titled "ARES/ACS" and links spokares.org. It is now titled "ACS" and no longer links the site. | `orgs/relationships.md` §7 |
| 2020 | JCal shows monthly meetings and workshops all year. **INFERENCE:** these are instances of recurring series set to run "until 2022", so they do not prove in-person meetings took place in 2020. One real one-off entry: "ARES SIMPLEX Net - N7GCO" on 2020-06-30. | `data/calendar-events.json`, `content/archive/20190826-index-php-calendars-calendar-jcalpro-30-regular-meetings-tra.md` |
| 2020 | Field Day 2020 images (`images/pdf_files/FD2020_1.png`, `FD2020_2.png`; first captured 2021-01-29; content not examined) | `wayback/cdx-spokares.txt` |
| 2021-01-29 | First capture of the **ARES-ACS logo**, ACS logo and SCDEM logo (the rebrand on the site) | `wayback/cdx-spokares.txt` |
| 2021-02-16/20 | New articles: a space-weather news link (by AG7QP) and Mel Ming N7GCO's RFI resources. After these, the only new article is the 2023 MOU. | `content/live/61-*.md`, `62-*.md`, `68-*.md` |
| 2021-03 | County CEMP: ACS is SCEM's amateur-radio capability and a supporting agency in ESF-2, 5, 6, 8 and 9 | `sources/government/spokane-county-CEMP-2021-03.txt` |
| 2021-10-30 | SET (entered as "Stimulated Exercise Test") | `data/calendar-events.json` |
| 2022 | Field Day 2022 document; 2022 Winlink practice exercises | `content/archive/20240913-index-php-downloads-general.md`, `20240913-index-php-downloads-net-preambles-and-scripts.md` |
| 2022-10-16 | Wayback records a burst of Joomla core paths (`/administrator/`, `/installation/`, `/cli/`, `/tmp/`...). The admin login page is publicly reachable. **INFERENCE:** this looks like an automated Joomla probe. | `wayback/cdx-spokares.txt`, `content/archive/20221016-administrator.md` |
| 2022-10-29 | "Rockford Exercise" (the last exercise in JCal) | `data/calendar-events.json` |
| 2023-01-19 | "Maintaining and Improving Safety on ARES Deployments", by David Oglesby N7LL, AEC | `content/archive/20240913-index-php-downloads-presentations.md` |
| 2023-05-06 | MOU with IEVHFRA, Revision #5 ("complete re-write"). It names 146.88 as the alternate repeater and W7TSC as the ARES point of contact. | `content/live/68-mou-with-ievhfra.md` |
| 2023 (Aug fires) | COAD, 2023-09-05: "Amateur Radio Services" (listed as "Fred Hutchnson, ARES/ACS", as printed; **INFERENCE:** Frank Hutchison AG7QP) provided a **mobile network unit for the ICP and the Disaster Assistance Center** in the Gray and Oregon Road fires | `sources/government/spokane-COAD-minutes-2023-09-05.txt` |
| 2023 | SCEM creates an Emergency Management Communications Specialist position to run ACS | `orgs/relationships.md` §7 |
| 2023-08 onward | cPanel subdomains captured (`cpanel.`, `webmail.`, `autodiscover.`) | `wayback/cdx-spokares.txt` |
| 2024-02-22 | New online ARES application (RSForm Pro with reCAPTCHA). It no longer exists. | `wayback/manifest.tsv`, `site-functionality-analysis.md` §1d |
| 2024-03-05 | COAD agenda: "ARES/ACS - Frank Hutchison" presentation | `sources/government/spokane-COAD-agenda-2024-03-05.txt` |
| 2024-06-10 / 08-10 | The Join page and the Meeting Location page are edited (3rd Thursday, 7-9 pm, DEM building) | `site-functionality-analysis.md` §1a, `content/live/27-meeting-location-map.md` |
| 2024-09-10..13 | The whole site is captured under `mail.spokares.org`. Downloads still work: about 33 files, including the "ARES - ACS" preambles, 23 presentations and Field Day 2022. New Member Orientation runs 4th Tuesdays at 18:00 and ends in the Comm Room for the 8 pm net. | `content/archive/20240910..13-*.md` |
| 2025-03-15 | Wayback shows only a `/lander` redirect. **INFERENCE:** this is 5 days after the domain's March 10 anniversary, so a lapse at renewal is possible. Unconfirmed. | `orgs/relationships.md` §1, `orgs/_backlog.md` |
| 2025-06-10 | Joomla assets are captured again (the site is back). The `osembed` hash has changed. **INFERENCE:** that update likely broke OSDownloads. | `wayback/cdx-spokares.txt`, `site-functionality-analysis.md` §0 |
| 2025 (Field Day) | Northwestern News (Aug 2025): "Spokane County ARES/ACS, W7GBU" Field Day in Spokane Valley | `orgs/relationships.md` §3 |

### 2.6 2026: compromise and the interim site

| Date | Event | Source |
|---|---|---|
| 2026-03-04 | Net preamble version date, from a filename hosted on ag7qp.com's asset store | `orgs/relationships.md` §6 |
| 2026-03-24 | Last edit to the Joomla Leadership page. The admin was still in normal use then. | `site-functionality-analysis.md` §0 |
| 2026 (date unknown) | **spokares.org hacked.** AG7QP: "The spokares.org website was hacked. This page acts as a temporary repository..." The Spokane page: "while the hacking attempt of spokares.org is dealt with". | `external/ag7qp.com.html`, `external/ag7qp/spokane-county-ares-acs.html` |
| Aug 2026 | Fires; the BOCC declares a state of emergency. The group's role, if any, is not documented in this corpus. | `orgs/relationships.md` §7 |
| 2026-08-04 | Roster PDF date on ag7qp.com (not downloaded) | `orgs/relationships.md` §6 |
| Sept 2026 | JCal rota entries 1609-1613 cover Sept 2026. Three of them show 22:00 instead of 20:00. **INFERENCE:** someone still had Joomla admin access in late summer 2026. | `data/calendar-events.json`, `site-functionality-analysis.md` §3.1 |
| 2026-09-23 | ag7qp.com last published | `external/ag7qp/sitemap.xml` |
| 2026-09-25 | Crawl findings: Downloads and contact return 500 (Alledia class missing); no visible or cloaked spam; the live server starts blocking this client (406, then timeouts). MX: **`0 spokares.org.`**, so mail is live at InMotion. The later session brief's "MX is a null record" was **wrong** (a null MX would be `0 .`, RFC 7505). Re-checked with `dig` against 1.1.1.1, 8.8.8.8 and 9.9.9.9: SPF `v=spf1 +mx +a +ip4:70.39.251.92 ~all`, a DKIM key at `default._domainkey`, and **no DMARC** record | `site-functionality-analysis.md` §0 and §3.6; `decisions.md` D3 |
| Upcoming | WSDOT exercise (Sep 26-27), SET (Oct 3), ShakeOut (Oct 15), State COMMEX (Oct 24), SRD (Dec 4-5), Winter Field Day (2027-01-23/24). **W7GBU license expires 2027-02-15; the domain expires 2027-03-10.** | `external/ag7qp/spokane-county-ares-acs.html`, `external/ag7qp.com.html`, `orgs/relationships.md` §1-2 |

---

## 3. Naming over time

| Seen | Name used | Source |
|---|---|---|
| 2004 | "Spokane County ARES/RACES". The Plan says "the joint organization shall be called Spokane County ARES/RACES". The calendar header reads "Spokane A.R.E.S.". The net is the "Spokane County Amateur Radio Emergency Service Net". | `content/archive/20040416-theplan-html.md`, `20040417-calendarhead-html.md`, `20040416-preamble-html.md` |
| 2004-2015 | Served agency: "Spokane City/County Department of Emergency Management (DEM)". The Plan is "Attachment 3 to ESF-2 of the Spokane City/County CEMP". In 2004 the repeater was the "Spokane County Department of Emergency Management Repeater". | Plan versions 2004/2009/8.09/7.13, `content/archive/20040804-index-html.md`, `sources/government/spokane-ESF2-comms-circa2014.txt` |
| 2009 | The Plan says "Spokane County Emergency Management" and "Emergency Coordination Center (ECC)" alongside EOC | `content/archive/20120207-aresplan25aug09-html.md` |
| 2017-2019 | "Spokane County ARES / RACES" or "ARES - RACES". Served agency: "Greater Spokane Emergency Management" (GSEM; logo captured 2018). | `content/archive/2019*.md`, `wayback/cdx-spokares.txt` |
| 2019-09-21 | First site mention of "Spokane County ACS" (AUXCOMM-based) | `content/archive/20191021-index-php-55-*.md` |
| 2020 | County page titled "ARES/ACS" | `orgs/relationships.md` §7 |
| 2021-01 | "Spokane_County_ARES-ACS" logo on the site | `wayback/cdx-spokares.txt` |
| 2021-03 | CEMP: "Spokane County Emergency Management (SCEM)"; ACS spelled out three different ways (Service, Services, System) | `sources/government/spokane-county-CEMP-2021-03.txt`, `orgs/relationships.md` (contradiction 2) |
| 2023-05 | "Spokane County ARES/ACS/RACES" (MOU) | `content/live/68-mou-with-ievhfra.md` |
| 2024 | "ARES - RACES - ACS" (Join page), "ARES - ACS" (preambles), "ARES/ACS" (orientation) | `content/live/26-*.md`, `content/archive/20240910..13-*.md` |
| 2026 | "Spokane County ARES-ACS": ag7qp.com, the Leadership title "ARES - ACS Staff", and groups.io (renamed from `spokaneares-races` to `spokaneares-acs`). The site title and footer still say "ARES / RACES". | `external/ag7qp/spokane-county-ares-acs.html`, `content/live/03-ares-acs-staff.md`, `site-functionality-analysis.md` §0 |

**Two text-substitution artifacts show the rename was done by search-and-replace, not a rewrite:**
- The 2026 Repeater Etiquette refers to the "ACS Officer" and to "ARES - ACS" members' credentials under review "by the DES".
- The preamble article still says "ARES/RACES".

Sources: `content/live/07-repeater-etiquette.md`, `content/live/08-net-preamble-script.md`.

---

## 4. Leadership over time (roles, names and callsigns as printed; "seen" dates)

**The callsign is the reliable key.** Name spellings vary between sources ("Weise"/"Wiese"). One person's callsign changed: Bob Weise/Wiese, EC, is KD7AMV in 2004 and W7UWC from 2008. Treating them as one person is an **INFERENCE** from the matching name and role. AA7RT appears as Mary Moore (2004), Mary Qualtieri (2008, 2017) and Mary Wiese (2018 onward).

### County EC / RACES Officer (EC/RO)

| Seen | Holder | Source |
|---|---|---|
| Undated, pre-2004 | N7VBW, "RACES Officer ... as well as the county Emergency Coordinator" | `content/archive/20040804-repeateretiquette-html.md` |
| Plan text captured 2004-04 (content older) | KI7QT, Nathan Jeffries, EC/RO. The 2004 Pactor page calls him "DEC Spokane County ARES/RACES, Sysop W7GBU Gateway". | `content/archive/20040416-theplan-html.md`, `20040416-pactor-html.md` |
| 2004-04 to 2004-09 | Bob Wiese/Weise KD7AMV, EC (2004 calendar contact and staff page) | `content/archive/20040417-calendars-html.md`, `20040926-kd7amv-html.md` |
| 2008-11 to about 2015 | Robert L. Wiese W7UWC, EC/RO (staff page 2008; Plans 2009, 8.09 and 7.13) | `content/archive/20081120-w7uwc-html.md`, the Plan files |
| 2019-03 to 2026 | Asa Jay Laughton W7TSC: webmaster from 2017-09, signs as EC on 2019-03-29 and "EC/RO" on 2019-03-30, "Emergency Coordinator & Website Admin" on the 2019 and 2026 staff pages | `content/archive/20190826-index-php-51-*.md`, `20190826-index-php-52-*.md`, `content/live/03-ares-acs-staff.md` |
| 2026-09 | The interim page names Frank Hutchison AG7QP as contact. **INFERENCE:** day-to-day contact has shifted during the outage. | `external/ag7qp/spokane-county-ares-acs.html`, `orgs/relationships.md` §1 |

**Gap:** no source dates the EC handover from W7UWC to W7TSC. It happened between the last Plan capture (2015-02) and 2019-03.

### AECs and other county staff

| Seen | Holders | Source |
|---|---|---|
| Plan, 2004 capture | AECs: KE7PI Joe Qualtieri, WB6JFH David Harper, AA7RT Mary Moore, KJ7YX Scott Lasater, N7UTG David Holten | `content/archive/20040416-theplan-html.md` |
| 2004-09 | AECs: WB6JFH, KE7PI, AA7RT. The AECs page heading says "Assistant District Emergency Coordinators". Webmaster: KC7GKX, Randall Jones. | `content/archive/20040922-aecs-html.md`, `20040804-index-html.md` |
| 2008-11 | AA7RT Mary Qualtieri, DEC | `content/archive/20081120-aa7rt-html.md` |
| Plan 2009-08-25 | AECs: KE7PI, WB6JFH, AD7FO Jack Tiley, KG8ZJ Charles Greeson (as printed; the 2003 tree and the 2012/2013 flyers print **KG8ZK**), KC7HFL Lyle Loshbaugh, KD7GHZ Mike Carey | `content/archive/20120207-aresplan25aug09-html.md` |
| Plan 8.09 (2013 capture) | AECs: KE7PI, WB6JFH, KG8ZJ | `content/archive/20130427-ares-plan-master-8-09-html.md` |
| Plan 7.13 (2015 capture; also the text live in 2026) | AECs: WB6JFH, KD7DQC Richard Tuftelund, KF7FKW Matt Arter | `content/archive/20150224-ares-plan-master-7-13-html.md`, `content/live/02-ares-races-plan.md` |
| Field Day committees | 2012: KD7GHZ Michael Carey, K7DWB Dave Baird, WA7BAA Don Vogel, KE7RAP Bob Peterson, KG8ZK Charles Greeson. 2013: KD7GHZ, W7UWC, KE7RAP, KG8ZK. | Field Day PDFs (see §2.2) |
| 2011 | PIO Gary Roth | `orgs/relationships.md` (people table, KXLY) |
| 2019-08 staff page | AECs: NV2Z Dan Croskrey (Public Events and W7GBU-10), W7BHP Richard Snell (Hospitals), WA7AQH Del Morissette (Digital Messaging and Recruiting; "working to be the W7GBU Trustee"), K7DSR Dave Carleton (Logistics and Training), K7EFR Nik Reed (Net Manager). Also: PIO Gary Roth KE7IAT; Publications Lynn Lambert KI7JVQ; Assistant Net Manager Lyle Loshbaugh KC7HFL; NWS Liaison Robert Wiese W7UWC. The 2019 contact form also routed messages to AEC WB6JFH. | `content/archive/20190826-index-php-calendar-jcalpro-location-3-spokane-county-dem-bui.md`, `site-functionality-analysis.md` §1d |
| 2019 mentors ("Elmers") | AD7FO, WB6JFH, K7BFL, K7DSR | `content/archive/20190723-index-php-membership-elmer-resources-mentoring.md` |
| 2023-01 | N7LL David Oglesby, AEC | `content/archive/20240913-index-php-downloads-presentations.md` |
| 2026 staff page (edited 2026-03-24) | AECs: NV2Z (Public Events, W7GBU-10, Winlink), K7MHG Mary Griffith (Hospitals), K7DSR (Logistics and Comms), AG7QP (Net Manager). PIO open. W7GBU trustee per FCC: K7DSR. SKYWARN representatives (page from 2018): AA7RT, with W7UWC assisting. | `content/live/03-ares-acs-staff.md`, `content/live/37-skywarn-weather-spotting.md`, `orgs/relationships.md` §2 |
| 2026-09 net-control rota | KE7RAP, NZ2S, AE7RJ, WA7LNC | `external/ag7qp/spokane-county-ares-acs.html` |

### ARRL section level (Eastern Washington)

| Seen | Section Manager | SEC | Other | Source |
|---|---|---|---|---|
| 2004 | Kyle Pugh KA7CSP | Gordon Grove WA7LNC | N7VBW Glenn Moore, DEC NE Washington; NQ7M Pat Dockrey, ASEC; K7BFL and N7LAX, OES | `content/archive/20040919-stafflist-html.md`, `20040416-theplan-html.md` |
| 2005-01 | Mark J. Tharp KB7HDX | – | – | `content/archive/20050123-kb7hdx-html.md` |
| 2009-2013 Plans | – | WA7LNC (also ARO Washington State) | WA7RF Rob Fisher, DEC Lincoln County; NQ7M; K7BFL (OES, STM); K7VOT Jim Jenkins, OES | Plan files |
| 2019-08 | Jack Tiley AD7FO | Rob Fisher WA7RF | – | `content/archive/20190826-index-php-calendar-jcalpro-location-3-*.md` |
| 2026 | C Jo Whitney KA7LJQ | Frank Hutchison AG7QP | ASM: Mary Wiese AA7RT (and others); Division Director: KB7HDX | `content/live/03-ares-acs-staff.md`, `orgs/relationships.md` (people table) |

**Continuity worth telling:** several people span two decades, e.g. WA7LNC (SEC in 2004, net control in 2026), WB6JFH (AEC 2004-2019), AA7RT and W7UWC (2004-2026), and KE7RAP (Field Day 2012, net control 2017-2026).

---

## 5. Notable activities, exercises, deployments and Field Days

| Type | Documented instances (date seen) | Sources |
|---|---|---|
| **Weekly net** | Tuesday 2000 on 147.30 (100 Hz): 2004, 2007, 2009, 2018 and 2026 preambles. The member call-in groups changed: A-F/G-L/M-S/T-Z in 2004, A-H/I-Q/R-Z in 2007, A-F/G-M/N-Z in 2018. | `content/archive/20040416-preamble-html.md`, `wayback/snapshots/www.spokares.org/Net_20Preamble_209.1.2007.html`, `content/live/08-*.md` |
| **Other nets** | QRP Day on the Noontime Net (2017); EWSEN and WSEN District 9 NCS shifts (Nov 2019); simplex net (2020-06-30; now 5th Tuesdays); GMRS net (3rd Tuesday); Hospital Net (2026; channel withheld under the SCEM FOUO rule, `decisions.md` D13) | `data/calendar-events.json`, `orgs/relationships.md` §1, `external/ag7qp/spokane-county-ares-acs.html` |
| **SET** | 2002 (photos), 2004-10-02, 2021-10-30, 2026-10-03 (scheduled). The Plan calls SET "the largest event sponsored by Spokane County ARES/RACES itself". | `content/archive/20040804-index-html.md`, `20040417-calendars-html.md`, `data/calendar-events.json`, `content/archive/20040416-theplan-html.md` |
| **EOC-to-EOC / 5th Saturday** | 2004-05-29; May 2009 (photos); 2013-08-31 (photos); 2018-03-31 (multi-county); 2019-03-30 (20+ operators, 6 site types, Camp Murray) | §2 above |
| **Other exercises** | Kootenai County (2019-07-17); quarterly all-members (2019-10-05); Hazard City hospital deployment (2019-12-07/08); Rockford (2022-10-29); WSDOT (2026-09-26/27); State COMMEX (2026-10-24) | `data/calendar-events.json`, `external/ag7qp/spokane-county-ares-acs.html` |
| **Field Day** | 2004 (planned), 2006 (photos), 2009 (photos), 2012 and 2013 (joint with IEVHFRA at Mt. Spokane as WR7VHF), 2017 (image), 2018 (calendar), 2020 (images), 2022 (document), 2025 (Spokane Valley, as W7GBU). Winter Field Day 2016 (Five Mile Prairie) and 2027 (scheduled). | §2 above, `orgs/relationships.md` §3 and §23 |
| **SKYWARN Recognition Day** | Every year "since 1999" at NWS Spokane (Airway Heights); recaps or announcements for 2017 and 2019; 2026-12-04/05 scheduled | `content/archive/20190724-index-php-32-*.md`, `20191212-index-php-57-*.md` |
| **Public service** | Bloomsday and the Lilac/Armed Forces Torchlight Parade (2004 to 2027; a parade briefing deck from 2017); Frozen Flatlands, Hoopfest (scrubbed in 2004), Settlers Day and Tour de Loc (2004); Cherry Picker's Trot and Valleyfest (2019); Climb for the Cure (2027) | `content/archive/20040417-calendars-html.md`, `data/calendar-events.json`, `orgs/relationships.md` §23 |
| **Real incidents** | Fire and rescue dispatch linking since 1994 (Plan); Valley View Fire shelter station (2011, KXLY); Gray and Oregon Road fires (Aug 2023: mobile network unit for the ICP and DAC) | `content/archive/20040416-theplan-html.md`, `orgs/_backlog.md`, `sources/government/spokane-COAD-minutes-2023-09-05.txt` |
| **Training** | License classes by AD7FO (2016-2023); Second Saturday workshops (from 2017-08-12, still running in 2026); Communications Academy (2018); helicopter safety (2018); AUXCOMM (2019); membership-requirements review (2019); New Member Orientation (from 2024-09) | §2 above |
| **Digital capability** | Packet-to-Pactor gateway W7GBU-7 (2004) → gateway off the air (2006-10-04) → Winlink 2000 W7GBU-10 at the CCB (2009) → Winlink workshops and ARDOP/VARA (2017-18) → HF Pactor and VHF packet from the ECC in an exercise (2019) → biweekly Winlink assignments (2026) | `content/archive/20040416-pactor-html.md`, the Plan files, `content/archive/2019*-45-*.md`, `external/ag7qp/spokane-county-ares-acs.html` |
| **Formal traffic** | Holiday Traffic Contest (Dec-Jan), in the 2004, 2009 and 8.09 Plans; dropped from 7.13 | Plan files |

---

## 6. Meeting and operating locations over time

| Seen | Location | Use | Source |
|---|---|---|---|
| 2004 | DEM building, **1121 W Gardner**: the EOC with a dedicated ARES/RACES position (HF/VHF/UHF and packet) and rooftop antennas | EOC operations | `content/archive/20040416-theplan-html.md` Att. 9 |
| 2004 | A member's home in the Mead area hosted the Pactor gateway (address published in the Plan; **not reproduced here**, and redacted from `content/archive/20040416-theplan-html.md` on 2026-09-25) | Gateway | same |
| 2009-2015 Plans | ECC at the DEM building, **1618 N Rebecca**. "No operating position nor antenna systems" there; the ARES position is at the **Combined Communications Building, 1620 N Rebecca**, with a tower. | ECC and radio room | `content/archive/20120207-aresplan25aug09-html.md`, `20150224-*.md` |
| 2012-2013 | Mt. Spokane ski lodge (Mt. Spokane State Park) | Field Day with IEVHFRA | Field Day PDFs |
| 2017 to Apr 2018 | Spokane County Fire Training Center, 1618 N Rebecca | Second Saturday workshops and license classes; JCal also puts the 2018-01-18 meeting there | `content/archive/20190724-index-php-29-*.md`, `20190724-index-php-34-*.md`, `data/calendar-events.json` |
| 2018-04 onward | **DEM building, 1121 W Gardner** | Workshops moved here on 2018-04-07. Monthly meetings are listed here in 2017-2020 JCal. (**INFERENCE:** the 2017 JCal descriptions are shared recurring-series text and may have been edited later, so the exact date the meetings moved is uncertain.) A 2019 article prints "2111 East Gardner", which is **probably a typo** for 1121 W Gardner. | `content/archive/20190917-index-php-45-*.md`, `20191021-index-php-55-*.md`, `data/calendar-events.json` |
| 2018 | Spokane County Sheriff's Training Center, Newman Lake | Helicopter training | `content/archive/20190724-index-php-40-helicopter-training.md` |
| Recurring | NWS Spokane office, Airway Heights | SKYWARN Recognition Day station | `content/archive/20190724-index-php-32-*.md` |
| 2024-09 | DEM "Comm Room" | New Member Orientation, 4th Tuesday 18:00, then the 8 pm net | `content/archive/20240910-*.md` |
| 2024-08 (page) | DEM building | 3rd Thursday, 7-9 pm | `content/live/27-meeting-location-map.md` |
| 2026 | **SCEM, 1121 W Gardner Ave** | Staff meeting 1st Tuesday 5:30 PM; workshop 2nd Saturday 9 AM; training 3rd Thursday 6 PM (earlier sources say 7 PM); orientation 4th Tuesday 6 PM | `external/ag7qp.com.html`, `external/ag7qp/spokane-county-ares-acs.html` |

The **3rd-Thursday monthly meeting** is traceable from 2004-03-18 to 2026, and the **Tuesday 2000 net** from 2004 to 2026.

---

## 7. Affiliations and relationships over time

| Seen | Change | Source |
|---|---|---|
| 2004 | Dual structure: an ARES unit (ARRL) plus RACES registration with the Spokane City/County DEM under the Sheriff as Director. The Plan is Attachment 3 to the City/County CEMP's ESF-2. The RACES Officer is "regional RACES coordinator of Washington State ARES/RACES District D" (Ferry, Stevens, Pend Oreille, Lincoln and Whitman). | `content/archive/20040416-theplan-html.md` |
| 2004 | Served-agency attachments: Red Cross, Salvation Army, NWS, DNR Northeast, EMS and fire agencies | same, Attachments 9-15 |
| 2004 | Web hosting donated by Sisna / ASISNA. `images/logos/Asisna.png` is still on the Joomla site in 2018. **INFERENCE:** the sponsorship may have continued into the Joomla era; the host at that time is unverified. | `content/archive/20040804-index-html.md`, `wayback/cdx-spokares.txt` |
| 2009+ | The Plan lists state districts 1-9 (replacing "District D"); Spokane is in District 9 | `content/archive/20120207-aresplan25aug09-html.md` |
| 2012-2013 | Joint Field Day with the Inland Empire VHF Club (station WR7VHF) | Field Day PDFs |
| 2016-2024 | Reports to the Spokane COAD (2016, 2017, 2023, 2024) | `sources/government/spokane-COAD-*.txt` |
| 2017-02-15 | W7GBU held as a club license by the Spokane County ARES RACES Support Group | `orgs/relationships.md` §2 |
| 2017-2019 | The county agency brands itself "Greater Spokane Emergency Management" (GSEM) | `content/archive/2019*.md` |
| By 2018-03 | **Groups.io** adopted for member email, events and files. This corrects the "2019" date in `site-functionality-analysis.md`: a post published 2018-03-30 already links Groups.io events and Files. | `content/archive/20190724-index-php-44-*.md` |
| 2019-09 | The county creates **ACS** on the FEMA AUXCOMM model. RACES is later "incorporated into ACS". | `content/archive/20191021-index-php-55-*.md`, `orgs/relationships.md` §7 |
| 2019-10 | IEVHFRA newsletter: ARES "has no bank account"; the club takes a $300 donation for ARES | `orgs/relationships.md` §9 |
| 2020 | **The City of Spokane leaves** joint emergency management. SCEM's Inter-Local Agreement no longer covers the City. | `orgs/relationships.md` §7, `sources/government/spokane-county-CEMP-2021-03.txt` |
| 2021-03 | CEMP makes ACS a supporting agency in ESF-2, 5, 6, 8 and 9 | `sources/government/spokane-county-CEMP-2021-03.txt` |
| 2023-05-06 | MOU with IEVHFRA, Rev. 5 (earlier revisions existed; the dates are unknown) | `content/live/68-mou-with-ievhfra.md` |
| 2023 | SCEM Communications Specialist position (staff owner of ACS) | `orgs/relationships.md` §7 |
| 2026 | The EWA SEC hosts the group's interim web presence | `external/ag7qp.com.html` |

---

## 8. Historical content worth reviving

| Item | Versions and dates | Where it survives | Suggested use in the rebuild | Caution |
|---|---|---|---|---|
| **Spokane County ARES/RACES Plan** | 2004 (ThePlan.html plus a Word ZIP, not archived); 2009-08-25; "Master 8.09" (captured 2013); "Master 7.13" (captured 2015, still the live article in 2026) | `content/archive/20040416-theplan-html.md`, `20120207-*`, `20130427-*`, `20150224-*`, `content/live/02-ares-races-plan.md` | A "Plan history" page with a version table. Publish the texts as dated PDFs labelled **historical**. Reuse the mission statement, which is unchanged since 2004. | Out of date: names, addresses, the 1984 MOU, the pre-ACS structure. The 2004 text has a member's home address, so redact it. |
| **Field Day record** | 2006 and 2009 galleries; 2012 and 2013 flyers (IEVHFRA joint); 2017, 2018, 2020, 2022 and 2025 mentions | `wayback/snapshots/.../FieldDay2012.pdf`, `Field_20Day_202013.pdf`; photos in Wayback (`Graphics/FieldDay2006pics/`, `Graphics/FieldDay2009/`); `images/pdf_files/FD2020_*.png` | A year-by-year "Field Day through the years" page. Ask for 2014-2025 scores and photos. | Photo permissions. Filenames carry callsigns, which can serve as captions. |
| **Exercise galleries and recaps** | SET 2002; EOC-to-EOC May 2009 and Aug 2013; the March 2019 recap; 2018 Communications Academy | Wayback image URLs (`Graphics/Set2002Pics/`, `Graphics/EOC2EOCMay09/`, `Graphics/EOC2EOCAug2013/`); `content/archive/20190826-index-php-52-*.md`, `-46-*.md` | Seed a "News / after-action" archive. The March 2019 recap is a model for short AAR posts. | Keep the operational details generic ("DO NOT GIVE FREQUENCIES OUT OVER THE AIR"). |
| **SKYWARN Recognition Day heritage** | Since 1999; recaps 2017 and 2019 | `content/archive/20190724-index-php-32-*.md`, `20191212-index-php-57-*.md` | A credibility line and a recurring event page | Remove the personal email in the 2019 post |
| **W7GBU / Dean Bula story** | 2004 page (image only); full article 2017 | `content/live/05-w7gbu-call-sign.md` | Keep as the "About / Heritage" anchor. Add the WC7AAT → W7GBU note once confirmed. | Fix the trustee on the Etiquette page (K7DSR per FCC) |
| **K7BFL "Principles of a Net Control Station"** | 2000-12-23 | `content/archive/20040416-netcontrol-html.md` | Keep, credited and dated, inside the NCS guide | The typewriter-era advice needs a light edit |
| **Holiday Traffic Contest** | In the 2004, 2009 and 8.09 Plans; dropped in 7.13 | Plan files | A candidate annual activity (NTS / Winlink practice) | Leadership decision |
| **Alert Levels (Green to Red)** | PDF by 2015; article 2017 | `wayback/snapshots/spokares.org/StatusCode.pdf`, `content/live/20-*.md` | Keep. It is core operational content. | Check that "activation text" still matches the current call-out tool |
| **Mentor program: Traffic Handler PDF and MP3** | 2015 (static); 2017 (Joomla, now 404) | Not in Wayback; possibly on the InMotion server | Recover from the cPanel backup and republish | Author permission |
| **Presentations library** | 2017-2023 (23 decks) | InMotion only (OSDownloads storage) | A Library page | Presenter permissions (N7GCO, K7PKT, AD7DD, WA7AQH, N7LL) |
| **Public-service event list** | 2004 calendar; 2019 JCal | `content/archive/20040417-calendars-html.md`, `data/calendar-events.json` | A "What we support" page showing how long the group has served each event | No personal contacts |
| **2004 mobilization-tree concept** | 2000/2003 | `content/archive/20040417-instructions-html.md` | History only. It illustrates how call-outs worked before text and email alerting. | **Do not republish the tree itself** (it is a member list) |

**Do not revive:** `Memberlist.pdf` (2015), the Feb 2009 net roster, the 2003 and 2000 mobilization trees, the 2019 and 2024 online application forms (PII on the web host), the 1994 ICS module (superseded by FEMA IS-100), and the gated-download flow.

---

## 9. Gaps and open questions (history-specific)

- **The compromise:** the exact date, the vector, and whether credentials were rotated. `external/ag7qp.com.html` gives no date.
- **Hosting moves:** when hosting moved from Sisna to InMotion; who registered the domain in 2004; what caused the 2025-03-15 `/lander`.
- **EC handover:** when W7UWC handed over to W7TSC (between 2015 and 2019), and the current RACES Officer appointment.
- **WC7AAT:** whether it is the retired ARES call and when W7GBU replaced it on 147.30 (both appear in 2004 captures).
- **Missing Field Day years:** Field Day results for years with no record (2005, 2007-2008, 2010-2011, 2014-2016, 2019, 2021, 2023-2024).
- **COVID-era activity (2020-2021):** the calendar cannot confirm it.
- **Pre-2004 site:** whether a site existed before the domain, and at what URL.

---

## 10. Lessons for the rebuild: what made past sites decay

1. **One maintainer, no successor.**
   - Evidence: in 2004 one "WebSlave" (KC7GKX) ran the site. In the Joomla era, 24 of 26 articles are by one person (W7TSC), who was also EC by 2019. The news blog stopped with the post of 2019-11-23 (**INFERENCE:** as EC duties grew). The interim site again depends on one volunteer (AG7QP).
   - **Rule:** name at least two editors with credentials. Choose a platform a non-programmer can edit. Keep a written runbook.
2. **Facts buried in long documents go stale silently.**
   - Evidence: in 2026 the live Plan (7.13) still lists the circa-2013 chain of command and the 1618 N Rebecca ECC. Repeater Etiquette names a trustee the FCC no longer shows. Meeting times disagree across three sources (7 PM vs 6 PM).
   - **Rule:** keep leadership, meetings, nets and the rota as single small data sources that each page reads. Put a "last reviewed" date and an owner on every page. Label historical documents as historical.
3. **Word-exported HTML and file-attachment sprawl.**
   - Evidence: each Plan was a Word "Save as HTML" export. Its `_files/` folder was already 404 in 2014. The Plan was re-pasted into Joomla as an 87 KB article.
   - **Rule:** publish long documents as dated PDFs in a library, not as hand-converted HTML.
4. **Platform churn with no migration or redirects.**
   - Evidence: static → Wix → Joomla → ag7qp.com. Every hop dropped content (the Plan versions, the Field Day and exercise galleries, the mentor MP3) and broke URLs. The Wix URL is still linked from another county's page. 2004-era filenames were still being requested in 2024.
   - **Rule:** before launch, inventory the old content, keep 301 redirects for the known old URLs, and keep a history/archive section so old material has a home.
5. **Extension sprawl and abandoned features.**
   - Evidence: about 15 extensions. Forum, gallery, maps, two application-form generations and a news blog were abandoned. OSDownloads and ALFContact broke (500). OSDownloads' email plus "Tweet or Like" gate markup was present but never enabled (all 66 gate flags 0; `documents-inventory.md` §2 item 6), so it added code and no function.
   - **Rule:** use core features only. Add a feature only when someone owns it. Downloads are plain links.
6. **An end-of-life platform left running.**
   - Evidence: Joomla 3 has been EOL since Aug 2023. The admin login was publicly reachable (probed in 2022). An extension update (by 2025-06) broke the library. The site was hacked in 2026.
   - **Rule:** use managed updates, off-site backups, least-privilege accounts and MFA. Replace a platform before it reaches end of life.
7. **Member PII on the public web.**
   - Evidence: a public `Memberlist.pdf` (2015) and a net roster `.doc` (2009) are now permanently archived. The 2004 Plan published a member's home address. The 2004-2008 pages published personal emails and phone numbers. The 2019 and 2024 online forms stored applicant PII on the web host.
   - **Rule:** no rosters, home addresses or personal contacts on the site. Keep member-only material on groups.io. Use role email aliases.
8. **Infrastructure knowledge held by individuals.**
   - Evidence: donated hosting (Sisna), a possible domain lapse (2025 `/lander`), a duplicate `mail.spokares.org` vhost, unknown registrar and host credentials, and a W7GBU license (2027-02-15) and domain (2027-03-10) that both expire soon.
   - **Rule:** keep a shared credentials and renewals register, auto-renew with two contacts, and add a yearly checklist item.
9. **The website stopped being where things happen.**
   - Evidence: Groups.io took over announcements, events and files by 2018. The site's calendar then decayed except for the rota, and the site became a second, conflicting source of truth.
   - **Rule:** let groups.io own dynamic member content. The site holds the stable public facts and links or embeds the groups.io calendar.
10. **Rename by search-and-replace.**
    - Evidence: the ARES/RACES → ARES-ACS change left mixed names, "ACS Officer ... by the DES" artifacts, and GSEM labels after SCEM.
    - **Rule:** choose one public name with SCEM's input, rewrite the affected pages rather than substituting words, and explain the ARES and ACS "two hats" once, on About.
