# spokares.org: current content and functionality analysis (input to the rebuild)

- **Prepared:** 2026-09-25, as a quick (~20 min) desk analysis of the local crawl. It is not a full audit.
- **Scope:** the live Joomla 3.x site (template `js_wright`), plus Wayback captures of pages that no longer exist or are broken.
- **Inputs used** (all paths are relative to `research/`):
  - `raw/_home.html` (nav and modules), `raw/by-id/article-*.html` (the 26 live articles)
  - `raw/www.spokares.org/**` (weblink lists, login, JCal pages), `wget.log` (HTTP errors), `pages-index.json`
  - `data/calendar-events.json`
  - `wayback/snapshots/**` (2019 and Sept-2024 Downloads pages, old forms, forum, photos, maps), `wayback/cdx-spokares.txt`
  - `hosting/verpex-enhance.md` (the hosting research; its section 5 is summarized here and not repeated)
- **Live-site checks:** a handful only. The live server answered HEAD requests with **406** and then **stopped responding to this client** (timeouts on every URL, `robots.txt` included). A WAF or rate-limit ban is likely, so no further live requests were made. **Other workflows hitting www.spokares.org from this machine may be blocked for a while.**
- **Other outbound checks:** 34 external links were checked with `curl -L`. Results are in section 4. DNS was checked with `host`.

---

## 0. Key findings (TL;DR)

1. **The site is small.**
   - It has 26 Joomla articles. After merging and dropping, that is about 15 real pages, plus 6 link-list pages (27 web links) and a document library of about 33 files (Sept 2024).
   - Most pages were last edited between 2017 and 2021.
   - Only 7 articles have been modified since 2023: Leadership (2026-03-24), Meeting Location (2024-08), Join (2024-06), About (2023-10), Amateur Training and License Tests (both 2023-07), and the MOU (2023-05).
2. **One person maintains nearly everything.**
   - 24 of the 26 articles were written by "Asa Jay Laughton" (W7TSC, listed as "Emergency Coordinator & Website Admin"). One is by Frank Hutchison (AG7QP) and one by "Super User".
   - Evidence: the schema.org `author` metadata in `raw/by-id/article-*.html`.
3. **The only frequently edited data is the weekly Net Control rota.**
   - It lives in JCal Pro and surfaces only in the homepage module "ARES - ACS Weekly Net Controllers".
   - Entries are still being added: IDs 1609 to 1613 cover Sept 2026.
   - **3 of those 5 entries show 22:00 instead of the 20:00 net time**, which looks like a data-entry or timezone error (`data/calendar-events.json`).
   - The "Calendars" menu does not point at JCal at all. It points only at the **groups.io calendar**.
4. **The two features that need a server are the ones that are broken.**
   - **All 5 Downloads categories and every file link return HTTP 500.** The Joomlashack/Alledia framework class is missing.
   - **The contact form (ALFContact) returns 500.** Five articles send visitors to it.
   - The Join page's "download the ARES registration form" link is one of the broken file links.
   - Wayback shows the downloads were still working on **2024-09-13**, so the failure is recent.
   - The `osembed` asset hash changed between the Sept-2024 and Jun-2025 captures. [Inference] A Joomlashack extension update probably replaced the shared Alledia framework and broke OSDownloads.
5. **Wayback does not have the library files.** The OSDownloads files (about 33 in 2024) were never archived; the CDX lists only 5 old static PDFs. **The only copies are on the current host (InMotion Hosting).**
   - Take a full cPanel backup (files, database and mail) before anything else is changed or cancelled.
6. **Email is on the current host too.** The MX for `spokares.org` is `spokares.org` itself, the NS records are `ns1/ns2.inmotionhosting.com`, the SPF record is `v=spf1 +mx +a +ip4:70.39.251.92 ~all`, and there is a `webmail.spokares.org`. Mailbox and forwarder migration is in scope.
7. **Nothing visible sits behind the member login.**
   - The login is plain `com_users`. No menu item, article or download in the crawl or in Wayback is marked registered-only.
   - Member communication moved to **groups.io in 2019** (a Wayback announcement says "We now have a Groups.io site for ARES - RACES members"), and the group was later renamed from `spokaneares-races` (that domain no longer resolves) to `spokaneares-acs`.
8. **The site keeps abandoning features.**
   - Past features included a Kunena forum, an Event Gallery photos page, FocalPoint maps, two generations of online application forms (BreezingForms in 2019, RSForm Pro in 2024), a news blog of about 25 posts (2017 to 2019), and OSDownloads. All of them are now gone or broken.
   - The lesson for the rebuild: **every third-party extension became a liability.**
9. **Hack scope (added 2026-09-25; details in `decisions.md` D1).**
   - Google Safe Browsing (Transparency Report API): no unsafe content for `spokares.org` or `www.spokares.org`; data dated 2026-06-14.
   - The web IP 70.39.146.166 and the SPF IP 70.39.251.92 are not on SpamCop, Barracuda, PSBL, UCEPROTECT-1 or Mailspike. Spamhaus, URIBL and SURBL could not be queried from here.
   - **Not yet checked:** the indexed URLs (`site:spokares.org`; the automated searches were blocked) and Search Console security issues (needs a verified owner, via a DNS TXT record). Until then, "no spam in the served home page" is the only evidence the hack did not inject pages.

---

## 1. Content inventory

**Legend**
- **Type:** S = static info, R = reference document, L = list of links, F = form, D = data-driven.
- **Chars:** size of the extracted article body.
- **Modified:** the article's schema.org `dateModified` (in `raw/by-id/article-N.html`).
- **Hits:** the Joomla counter. It includes bots: the footer Privacy and Terms pages lead at 27k and 26k because they are linked from every page, so hits are **not** a reliable popularity signal.
- **Rec:** K = keep, M = merge, D = drop, R = rewrite.

### 1a. Pages reachable from the menu or footer

| # | Title (article id) | Canonical URL (`/index.php/...`) | Menu | Type | Chars | Modified / signal | Hits | Quality / staleness flags | Rec |
|---|---|---|---|---|---|---|---|---|---|
| 1 | Home (blog of 1 article + modules) | `/` | Home | D | – | footer "© 2026" (auto) | – | h1 is "Home". Sidebar: net-controller module, an empty weather `div` (OpenWeatherMap JS), an **empty** "Upcoming Events" module, and a login form. NWS forecast iframe and hamqsl solar image | R, as a landing page |
| 2 | About Spokane County ARES - ACS (4) | `4-about-spokane-county-ares-races` | Home lead item | S | 806 | 2023-10-03 | 7,875 | Good concise mission text | M into Home/About |
| 3 | ARES - ACS Staff / Leadership (3) | `about/ares-races-leadership` | About | S (9 accordions, JW Tabs & Sliders) | 10,319 | **2026-03-24**, the most recently maintained page | 9,114 | Mostly generic ARRL role boilerplate. PIO is "Open Position". Links to the broken contact form | K; cut the boilerplate, keep name/role/call, review annually |
| 4 | The Radio Amateur Code (6) | `about/amateur-code` | About | S | 1,142 | 2017-06-03 | 5,907 | The 1928 Segal text | M into About/History |
| 5 | W7GBU Call Sign / History (5) | `about/w7gbu-history` | About | S + `floy.gif` | 5,291 | 2018-04-29 | 5,503 | Heritage content, still good | K |
| 6 | Meeting Location Map (27) | `about/meeting-location` | About | S (image map) | 322 | 2024-08-10 | 7,342 | **The address exists only inside the image** (`/images/DEM_map.png`); the text says only "DEM Building". 3rd Thursday, 7 to 9 pm | M into "Meetings & Nets"; add text address and a map link |
| 7 | MOU with IEVHFRA (68) | `about/mou-with-ievhfra` | About | R | 5,466 | 2023-05-06; "Revision #5"; the MOU itself says to revisit contacts annually | 2,724 | Names 146.88 as the alternate repeater | K (About/Library) |
| 8 | Traffic Handling Tools (K7BFL) | felge.us/tfctools.html | Operations | L (external) | – | 200 OK | – | – | K as a link |
| 9 | "Net Preamble" menu item | `downloads/net-preambles-and-scripts` | Operations | – | – | **HTTP 500** | – | Broken | R (see #33) |
| 10 | Incident Command System (10) | `operations/incident-command-system` | Operations | R | 29,289 | 2017-06-03; the text is **"ICS Orientation Module 1, October 1994"** | 7,206 | Obsolete, pre-NIMS | **D**; link FEMA IS-100 instead |
| 11 | Forms (weblinks ×2) | `operations/forms` | Operations | L | 150 | – | 772 / 504 | ARRL Radiogram; "FEMA ICS Forms" home page | M into Library |
| 12 | ARES Alert Levels / Status Codes (20) | `operations/status-codes` | Operations | S | 2,542 | 2017-07-22 | 4,503 | Core operational content, still valid | K |
| 13 | WSEN | wastateares.org/washington-state-emergency-net | Operations | L (external) | – | 200 | – | – | K |
| 14 | Field Operations Guides | dhs.gov/safecom/... | Operations | L (external) | – | Redirects to **cisa.gov** | – | – | K; update the URL |
| 15 | SKYWARN® & Weather Spotting (37) | `operations/skywarn-weather-spotting` | Operations | S | 1,646 | 2019-12-06 | 5,549 | Contact-form link broken; still lists Facebook reporting | K, refresh |
| 16 | Training & Courses (weblinks ×5) | `operations/training-courses` | Operations | L | 427 | – | 276 to 348 | IS-200.b and IS-800.c are **superseded** (now .c and .d) | M into "Member requirements" |
| 17 | Digital TNCs & Modems / Software / Cables & Drivers / Packet Radio (weblinks 4 + 3 + 8 + 5) | `digital-messaging/digital-messaging/*` | Technology > Digital Messaging | L | 190 to 400 each | – | 665 to 1,265 each | 2017-era gear (TinyTrak4, KPC3+, Pactor). **The 20 targets could not be resolved** (redirect IDs only; see section 4) | M into one "Digital & Winlink resources" page |
| 18 | Winlink Info by K7BFL | felge.us/digitalmessaging.html | Technology | L (external) | – | 200 | – | – | K |
| 19 | ARRL RFI Info; Mel Ming RFI video | arrl.org/...; **Dropbox** `.mp4` | Technology > RFI | L (external) | – | 200. The Dropbox legacy `/sh/` link now redirects to a new `/scl/` link | – | Fragile hosting for the video | K; move the video to YouTube or archive.org |
| 20 | Mel Ming's RFI Resources (62) | `digital-messaging/rfi-resources/mel-ming-rfi-resources` | Technology > RFI | R + L (13 links) | 5,180 | 2021-02-20 | 5,361 | Good content. Several links are `http://` | K (the RFI page) |
| 21 | Groups.io Calendar | spokaneares-acs.groups.io/g/main/calendar | Calendars | L (external) | – | 200 | – | **This is the only calendar in the menu** | K; make it the primary calendar |
| 22 | Downloads ×5 (Net Preambles, Presentations, Forms & Info, Digital Traffic, General) | `downloads/*` | Downloads | D (OSDownloads) | – | **All HTTP 500** | – | See section 3.2 for the reconstructed inventory | R as a Library |
| 23 | RATPAC Videos | Google Sheets `pubhtml` | Downloads | L (external) | – | 200 | – | – | K as a link |
| 24 | Member Login | `membership/member-login` | Membership | F (com_users) | 144 | – | – | Nothing member-only was found (section 3.3) | **D** |
| 25 | Amateur Radio Training (42) | `membership/amateur-training` | Membership | S | 1,124 | 2023-07-02 | 5,266 | Contact-form link broken; names individual instructors | M with #36 into "Get licensed" |
| 26 | Ham Radio Links (24) | `membership/ham-radio-links` | Membership | L (17) | 707 | 2020-11-07 | 5,679 | 15 of 17 links are `http://`. **ohr.com has no DNS, wrh.noaa.gov/otx returns 403**, and spaceflight.nasa.gov is legacy | M into Resources; prune |
| 27 | ARRL ARES Task book | arrl.org/ares | Membership | L (external) | – | 200, but it is the generic ARES page, not the task book | – | – | K; fix the target |
| 28 | Greater Spokane Emergency Management Application | spokanecounty.org/FormCenter/...-276 | Contact | L (external) | – | 200, redirected to **spokanecounty.gov** | – | **Critical join step** | K; update the domain |
| 29 | Welcome Letter and Member Requirements (41) | `contact/welcome-letter` | Contact | S | 4,420 | 2019-04-06 | **12,821** (highest non-footer) | See below the table | R into "Member requirements" |
| 30 | How to join ARES - RACES - ACS (26) | `contact/join-ares-races` | Contact | S + file link | 2,051 | 2024-06-10 | 9,874 | **The ARES application download returns 500.** The task-book link is generic | **K, top priority**; R |
| 31 | Privacy Policy (13) | `2-uncategorised/13-privacy-policy` | Footer | S | 9,444 | 2017-06-04 | 27,334 | Boilerplate about "Registered Users", cookies and "partners such as Google" | R, short and accurate |
| 32 | Terms and Conditions (12) | `2-uncategorised/12-terms-and-conditions` | Footer | S | 4,334 | 2018-01-02 | 25,927 | Boilerplate that **forbids copying or redistributing materials**, which contradicts sharing forms and scripts | D, or replace with a 3-line disclaimer |

Flags for the Welcome Letter (#29):
- FEMA IS-100.c and IS-700.b links use the dead `www.training.fema.gov` host.
- IS-200.b and IS-800.c redirect to the newer .c and .d versions.
- The L&I bloodborne-pathogens link returns 404.
- It has 3 links to the broken contact form.

### 1b. Hidden "Uncategorised" articles (not in any menu)

**How they are reached:** they appear only in the Joomla category listing `/index.php/2-uncategorised`, which is paginated (`?start=6..24`, 117 KB). That listing is reached through the "Category: Uncategorised" link on the footer Privacy and Terms pages.
- Evidence: `raw/www.spokares.org/index.php/about/amateur-code/2-uncategorised/13-privacy-policy.html` links to `/index.php/about/amateur-code/2-uncategorised`.
- No menu or article links to #33 to #40 below.
- Wayback shows they **used to be menu items** in 2019: `operations/repeater-etiquette`, `operations/net-preamble`, `operations/net-control-station`, `operations/equipment-lists`, `membership/testing-schedule`, `membership/elmer-resources-mentoring` and `about/email-list` (`wayback/manifest.tsv`).
- The menu items were removed, but the articles are still published. In practice they are orphaned (search engines may still index them).

| # | Title (id) | URL | Type | Chars | Modified / signal | Flags | Rec |
|---|---|---|---|---|---|---|---|
| 33 | Net Preamble & Script (8) | `2-uncategorised/8-net-preamble-script` | R | 4,709 | 2020-07-22; the text says "Preamble last updated: April 4, 2018" | **Two sources of truth**: Sept-2024 Downloads had newer "ARES - ACS Net Preamble (Regular)" and "(Simplex)" files. It uses the "ARES/RACES" name. Both of its links point to the broken Downloads page | M into one current preamble on the Net page, plus a PDF |
| 34 | ARES / RACES Plan (2) | `2-uncategorised/2-ares-races-plan` | R (governance) | **87,119** | 2018-11-21. It derives from the 2009 master (`wayback/snapshots/www.spokares.org/ARES_Plan_Master-8.09.html`, `ARESPlan25Aug09.html`) | Written as "Attachment 3 to ESF-2" of the City/County CEMP. Uses the pre-ACS structure and names. Very long HTML | K as a **PDF in the Library** only after the EC confirms the current county version; do not hand-convert it to HTML |
| 35 | Repeater Etiquette (7) | `2-uncategorised/7-repeater-etiquette` | S | 9,414 | 2021-05-18 | W7GBU on 147.30, trustee WA7AQH, ACS Officer W7TSC; refers to "DES". Needs a names/agency check | K in "Nets & Repeater"; shorten |
| 36 | Amateur Radio License Test Sessions (11) | `2-uncategorised/11-amateur-radio-license-test-sessions` | S + L | 3,479 | 2023-07-02 | **k7id.org VE link returns 404.** Lists third-party fees, times and personal emails that go stale quickly | M into "Get licensed"; link to ARRL exam search and HamStudy instead of copying schedules |
| 37 | Principles of a Net Control Station (9) | `2-uncategorised/9-principles-of-a-net-control-station` | R | 2,888 | 2017-07-22 | Dated ("replace hand copy with a typewriter") | M into the Net page ("NCS guide") |
| 38 | Equipment Lists (21) | `2-uncategorised/21-equipment-lists` | R | 6,701 | 2017-07-22 | Typos ("cloths", "pencels"); dated gear (RG-58, cigar-lighter adapters) | K as a "Go-kit & equipment" page; refresh |
| 39 | The ARES - ACS Email List (23) | `2-uncategorised/23-the-ares-races-email-list` | S | 624 | 2021-05-18 | **Broken**: the body ends in a bare "or"; the subscribe links were stripped | R as "Join our groups.io" instructions |
| 40 | Mentor Programs (25) | `2-uncategorised/25-mentor-programs` | L | 236 | 2018-09-09 | Both links (`downloads/mentoring/routedownload/traffic-handler-{pdf,mp3}`) return **404**; the category was deleted | D, unless the files are recovered from the server |
| 41 | Ham Radio Forms a Planet-Sized Space Weather Sensor Network (61) | `2-uncategorised/61-...` | L (1 news link, eos.org) | 253 | 2021-02-16 (author Frank Hutchison) | A news stub | D (or News) |
| 42 | Under Construction (1) | `2-uncategorised/1-under-construction` | S | 268 | 2017-09-24 | Says "This antenna field is currently under construction" | **D** |

### 1c. Duplication and URL noise

- The crawl found 751 HTML files, which collapse to 445 hash-unique pages (`pages-index.json`). 256 of those are JCal event or calendar views; most of the rest are the **same article repeated under every menu path**. For example, Privacy Policy exists under about 14 prefixes such as `about/amateur-code/2-uncategorised/13-privacy-policy`.
- The site also answers on `spokares.org`, `www.spokares.org` **and `mail.spokares.org`**. Wayback captured the whole Joomla site under `mail.` in Sept 2024, which is a vhost/DNS misconfiguration and duplicate content.
- Every article has "Print" and "Email this link to a friend" (`component/mailto`) actions. `mailto` is a known spam vector and was removed in Joomla 4.

### 1d. Content that existed before and is gone (Wayback)

| What | When | Evidence | Relevance |
|---|---|---|---|
| News/blog posts (IDs 19 to 57, about 25 posts: exercise recaps, meeting changes, SKYWARN, 2019 ACS requirements) | 2017 to 2019 | `wayback/manifest.tsv` (`index.php/28-…` through `57-…`) | Shows a news feature was used, then stopped. Announcements now happen on groups.io |
| Online contact form with a **role-based recipient list** (Feedback, EC W7TSC, AECs NV2Z, WB6JFH, W7BHP, WA7AQH, K7DSR) | 2019 | `wayback/snapshots/www.spokares.org/index.php/contact/contact-form` | Role routing is the requirement to preserve |
| Online ARES application, BreezingForms (about 40 fields: address, phones, **cell carrier**, emergency contact, equipment, modes, 4WD) | 2019 | `.../contact/ares-races-app-form` | Collected **PII on the web server** |
| Online ARES application, RSForm Pro + reCAPTCHA (similar fields, county dropdown) | Feb 2024 | `.../contact/rsform-ares-app` | Replaced the above; now gone. The Join page points to a PDF instead (broken) |
| Kunena forum (Welcome Mat, Suggestion Box, Winlink, Hamfest; about 1 topic each) | 2019 | `wayback/snapshots/spokares.org/index.php/forum` | Unused, then abandoned |
| Photos (Event Gallery: "Exercises", "Other Events") | 2019 | `.../index.php/photos` | Abandoned |
| "Important Locations Map" (FocalPoint: hospitals, EOCs, airports, digital repeaters, gateways) | 2019 | `.../operations/maps-testing` | An abandoned experiment. Interesting as a future idea, but not a requirement |
| A public `Memberlist.pdf` and a `.doc` net roster | 2009 to 2015 | `wayback/cdx-spokares.txt` | **Privacy lesson:** a member list was public and is now permanently archived |

---

## 2. Information architecture

### 2a. Current menu tree (from `raw/_home.html` `<ul class="menu nav">`)

```
Home (icon)
About ▾            ARES - ACS Leadership · Amateur Code · W7GBU History · Meeting Location · MOU with IEVHFRA
Operations ▾       Traffic Handling Tools (K7BFL)↗ · Net Preamble (→ Downloads, 500) · Incident Command System (ICS)
                   · Forms · Status Codes · WSEN↗ · Field Operations Guides↗ · SKYWARN® & Weather Spotting · Training & Courses
Technology ▾
  Digital Messaging ▸   Winlink Info by K7BFL↗ · Digital TNCs and Modems · Software · Cables and Drivers · Packet Radio Resources
  RFI Resources ▸       ARRL RFI Info↗ · Mel Ming N7GCO RFI Presentation (Video, Dropbox)↗ · Mel Ming - RFI Resources
Calendars ▾        Groups.io Calendar↗
Downloads ▾        Net Preambles and Scripts · Presentations · Forms and Information · Digital Traffic Information and Files · General   (ALL 500)
                   · RATPAC Videos (Google Sheet)↗
Membership ▾       Member Login · Amateur Training · Ham Radio Links · ARRL ARES Task book↗
Contact ▾          Greater Spokane Emergency Management Application↗ · Welcome Letter and Member Requirements · Join ARES / RACES
Footer             Privacy Policy · Terms and Conditions   (→ "Uncategorised" category → all hidden articles)
```

**Problems with the current menu**
- It has 8 top-level items, and every one is a `href="#"` dropdown heading. There are 3 levels under Technology.
- 10 of the 36 leaf entries leave the site (marked ↗).
- 6 entries are broken (all 5 Downloads items plus "Net Preamble").
- "Contact" holds no contact method, only join material.
- Branding is inconsistent: "ARES / RACES" in the title and footer, "ARES - ACS" in the header and module, "ARES - RACES - ACS" on the Join page, and "ARES/ACS/RACES" in the MOU. The agency appears as "Spokane County DEM", "DES", "Greater Spokane Emergency Management", "Spokane Emergency Management" and "GSEM". **A naming decision is needed before writing content.**

### 2b. Proposed simplified IA (6 top-level items, about 14 pages + a Library)

```
Home            who we are (from #2) · next net + NCS · next meeting · "Join us" CTA · quick links (groups.io, net info, NWS/solar)
About           About ARES / ACS / RACES (+ Amateur Code) · Leadership (#3) · W7GBU history (#5) · Partners & agreements (MOU #7)
Join            How to join (#30: steps, ARES app PDF, county application↗) · Member requirements & training (#29 + #16)
                · Get licensed (#25 + #36) · groups.io email list (#39)
Nets & Ops      Weekly net (schedule/rota, freq/tone, preamble #33, NCS guide #37) · Repeater & etiquette (#35, 146.88 alt)
                · Alert levels (#12) · Go-kit & equipment (#38) · SKYWARN (#15) · Digital/Winlink & RFI resources (#17–#20, #26 pruned)
Calendar        groups.io calendar (link/embed/ICS-rendered upcoming list) · meeting location (#6 with text address)
Library         Forms (ICS 201/205/213/214 → FEMA; ARRL radiogram) · Net scripts & roster* · Presentations · Plans (ARES/RACES Plan PDF #34) · Membership docs
Contact         role-based contact (EC, AECs, webmaster) · meeting place · groups.io
Footer          Privacy (short) · disclaimer · "Members: groups.io"
```

\* The **net roster** contains member data. Put it in groups.io Files, not the public Library (see 3.3).

---

## 3. Functionality inventory

### 3.1 Calendar and events (JCal Pro)

- **Extraction:** 232 unique events extracted (`data/calendar-events.json`). The event-ID high-water mark is **1613**, so the database holds far more events (recurring instances and ones the crawl never reached).
- **Descriptions:** the JSON has an empty `description` field. The real text is in `<div class="eventdesclarge">` and is present on **105 of 232** events. Most are repeated boilerplate such as "Monthly training opportunity…".
- **Volume by year** (as crawled): 2017: 19, 2018: 60, 2019: 78, 2020: 64, 2021: 3, 2022: 1, 2023: 1, 2024: 1, 2026: 5. **Use collapsed after 2020**, except for the rota.
- **Categories and patterns:**

| Category | n | Pattern | Location | Status |
|---|---|---|---|---|
| Weekly Net Controllers | 109 | **Every Tuesday 20:00 to 21:00.** One event per week titled with the NCS call sign (about 30 distinct operators; KE7RAP 23, W7HEE 12, N7MCB 10, NV2Z 10…). Occasional "Simplex" variants | usually none; some "Spokane Fire Training Center" | **The only live use.** Sept 2026 entries exist; 3 of 5 wrongly at 22:00 |
| Regular Meetings/Training | 100 | Monthly meeting **3rd Thursday 19:00** · "Second Saturday Amateur Workshop" 09:00 to 12:00 (2017 to 2020) · in 2020 "ARES Net" was also entered weekly here (duplicate category) · **New Member Orientation 4th Tuesday 18:00** (Sept 2024 to Jan 2025, Wayback) | **Spokane County DEM Building, 1121 W Gardner** (63 events); Spokane Fire Training Center, 1618 N Rebecca (17) | Lapsed except orientation (2024-25) |
| Upcoming Exercises | 7 | Quarterly / "5th Saturday" / SET (Oct), Sat 08:00 | – | Last one: Oct 2022 |
| Other Events | 5 | Field Day, Hamfest, Valleyfest, SKYWARN Recognition Day | – | 2017 to 2019 |
| EWSEN Monday Night Net Control | 6 | Mon 18:30 | – | Nov 2019 only |
| WSEN District 9 Net Control | 4 | Sat 09:00 | – | Nov 2019 only |
| Qualifications Training | 1 | Helicopter training | Sheriff's Training Center, 6011 N Chase Rd | 2018 |

- **Presentation:**
  - The homepage module shows the **next single** Net Controller.
  - The homepage "Upcoming Events" module is **empty**.
  - Month, flat, week, day and category views exist only as `component/jcalpro/...` URLs, with no menu item.
  - There is a per-event iCal export (`?format=ical`).
- **Who edits it:** not exposed in the HTML. It is presumably the EC or the "AEC - Net Manager" (AG7QP per the Leadership page). **Confirm.**
- **Implication:**
  - The real calendar is already groups.io (it is the Calendars menu target).
  - The site needs only (a) the weekly net facts, (b) this month's NCS rota, and (c) upcoming meetings, exercises and orientations.
  - **Recommendation: put all events, the rota included, in the groups.io calendar.** The site can link to it, embed it, or render an "upcoming" list from its ICS feed if the group exposes one.
  - Historical events (2017 to 2022) are not worth migrating. An optional archive can come from the JCal iCal export or a DB dump.

### 3.2 Document library (OSDownloads, broken). Reconstructed inventory

- **Sources:**
  - Sept 2024: `wayback/snapshots/mail.spokares.org/index.php/downloads/*`
  - 2019: `wayback/snapshots/{www.,}spokares.org/index.php/downloads/*`
- **Downloads were gated in 2019.** **[Corrected 2026-09-25: the gate markup was present but never enabled; all 66 gate flags are 0 (`documents-inventory.md` §2 item 6; README errata).]** OSDownloads showed an "enter e-mail + agree to Terms" (and "Tweet or Like") pop-up before a download. Drop that.

| Category (menu) | Files in Sept 2024 | Only in 2019 captures (probably deleted) |
|---|---|---|
| **Net Preambles and Scripts** | Spokane County ARES - ACS Net Preamble (Regular) · Net Preamble (Simplex) · **Net Roster** · Simplex Net Reports · (2022) Winlink Practice Exercises | Net Preamble & Script: 147.300 Repeater · 147.300 as W7GBU · 146.880 Alternate Repeater · 146.880 Alternate as W7GBU |
| **Presentations** (23) | Five (5) Knots You Should Know · Maintaining and Improving Safety on ARES Deployments (N7LL, 2023-01-19) · Initial Damage Assessment (Winlink form) · Case Study: Derecho Storm Disaster · Mel Ming's Radio Go-Kit · Washington Guard Response to Cascadia Subduction Zone Event · Abbreviated IS-100C Overview · IS-200C Overview · IS-700 Review · IS-800 Review · Spokane County Intro to 800MHz Radios - ACS · Spokane County ARES - ACS Membership Requirements (2019 ARRL + ACS) · Intro to AREDN for Spokane County ACS · Mel Ming's Emergency Modules · How to Power your Radio when the Lights Go Out · A Guide to Anderson Powerpoles · Intro to Packet Communications 101 · Scripting in Winlink 101 · Ardop 101 for Winlink · Go-Kits 2017 · 2017 Lilac Presentation (K7PKT) · Cross-Band Repeaters 2017 (AD7DD) · Lilac Festival Armed Forces Torchlight Parade briefing | – |
| **Forms and Information** | Welcome Letter · ARES / RACES Membership Application (sic "Appplication") | AUXCOMM Flyer, June 2019 |
| **General** | Field Day 2022 · GPS Coordinates Conversion · Mel Ming - RFI Resources | Electronics Flea Market, Tri-Cities May 2018 |
| **Digital Traffic Information and Files** | (no 2024 capture) | How to operate Packet using a SignaLink Integrated Sound Card |
| ICS - Forms (menu removed) | – | ICS 201, 205, 213, 214 (use the current FEMA/NIMS originals instead) |
| Mentoring (menu removed) | – | Traffic Handler PDF, Traffic Handler MP3 (still linked from article 25, 404) |

- **Total:** about 33 files live in 2024, plus about 13 older ones.
- **File formats and sizes are unknown.** Presentations are probably PPTX/PDF, and one MP3 exists.
- **None of the files are in Wayback**; the CDX has only 5 legacy PDFs from 2012 to 2015.
- **Action:** recover the OSDownloads storage folder and the `#__osdownloads_documents` table from the InMotion account (cPanel backup).

### 3.3 Member login: what is behind it?

- **Mechanism:** Joomla core `com_users` (`mod_login` in the sidebar plus the `membership/member-login` page with remind/reset).
- **What the crawl shows:**
  - No page, menu item or download showed an access-level marker.
  - The 2024 downloads had no login hints.
  - The 26 by-id articles are all public.
- **What is implied:**
  - The Privacy Policy refers to "Registered Users" and "name, date of birth, email, IP, username, avatar". That is generic Joomla boilerplate.
  - The 2019 forum needed accounts.
  - Menu items visible only to logged-in users **cannot be ruled out** (the crawl was anonymous). **Ask the admin**, or check `#__menu.access` and `#__content.access` in a DB dump.
- **Where members actually are:** groups.io (the email list, calendar, and probably files and wiki). The 2019 announcement said, "We now have a Groups.io site for ARES - RACES members" (`wayback/snapshots/.../downloads/*`).
- **Recommendation:**
  - **Drop site accounts.** Put member-only material (the net roster, contact lists, internal plans) in **groups.io Files or Wiki**.
  - This removes the only reason for auth, and the only store of personal data, from the website.

### 3.4 Contact and join flows

| Flow | Current | Status | Needed |
|---|---|---|---|
| General contact | ALFContact `component/alfcontact/?view=alfcontact&Itemid=197`, linked from articles 3, 37, 41 (×2) and 42 | **500** | A role-based way to reach the EC, AECs and webmaster without publishing personal emails |
| Join ARES | Article 30 → ARES registration PDF (OSDownloads) → "bring it to a meeting or email it to [protected address]" | **The PDF link returns 500** | Downloadable form plus an email address. Optionally an online form, but **don't store PII on the web host** |
| Join ACS (county) | External Spokane County "Volunteer Emergency Worker Application" (FormCenter 276) | 200 (now spokanecounty.**gov**) | Link only |
| After joining | Welcome Letter (#29): FEMA IS-100/700, background check, 6-month period, optional courses; "email certificates to WA7AQH" | Links partly stale | Rewrite as a checklist |
| Email list | Article 39 (broken "or"); groups.io | Broken text | Link to groups.io subscribe |
| Obfuscated emails | Joomla email-cloak ("This email address is being protected from spambots…") in at least 5 articles | Needs JS | Use role aliases (`ec@`, `webmaster@`) on Enhance mail with a simple mailto, or a form |

### 3.5 Widgets, embeds, feeds and search

| Feature | Implementation | Server-side need? | Recommendation |
|---|---|---|---|
| Weather conditions | `mod_weatheraholic` (jQuery flatWeather, **OpenWeatherMap**, needs an API key); renders an empty `#weatheraholic-119` | Key held in module config | Drop, or use a client-side NWS link/widget. The NWS iframe already covers it |
| NWS forecast | `com_wrapper` iframe to `forecast.weather.gov/MapClick.php?lat=47.6573&lon=-117.4123` (200) | No | Keep as a link or a smaller embed |
| Solar/propagation | `hamqsl.com/solar101vhf.php` image (http, redirects to https; no alt text) | No | Keep (https, add alt) |
| Image slider | `mod_jsshackslides` (Owl Carousel), 8 images | No | Replace with 1 or 2 static hero photos |
| Accordions | JoomlaWorks Tabs & Sliders (`plg_content_jw_ts`), Leadership only | No | Use native `<details>` or plain headings |
| Embeds | `plg_content_osembed`, `osyoutube` (Joomlashack/Alledia); loaded site-wide | No | Use native oEmbed (WP) or iframes |
| RSS/Atom | `?format=feed&type=rss` (homepage blog = 1 About item) | No | Drop, unless News is revived |
| Search | No search UI exposed | – | Optional. A small site doesn't need it (Pagefind for static, core for WP) |
| Print / Email-a-friend | `component/mailto`, print views | – | Drop |
| iCal | JCal per-event `?format=ical` | – | Replace with the groups.io ICS |

### 3.6 External integrations

- **groups.io** (`spokaneares-acs.groups.io/g/main`): the calendar, the email list, and the de-facto member hub. The old `spokaneares-races.groups.io` no longer resolves.
- **Dropbox** hosts the RFI video.
- **Google Sheets** holds the RATPAC video index.
- **Spokane County FormCenter** hosts the county volunteer application.
- **OpenWeatherMap, NWS and hamqsl** provide the widgets.
- **InMotion Hosting** runs the web, DNS and email (NS `ns1/ns2.inmotionhosting.com`, MX `spokares.org`, `webmail.spokares.org`).

### 3.7 Joomla extensions in use (from asset paths, markup comments and the Wayback CDX)

| Extension | Kind / vendor | Evidence | Status |
|---|---|---|---|
| Joomla 3.x core (`media/system`, `jui`, MooTools) | CMS | Every page. **Joomla 3 has been EOL since Aug 2023** | Unpatched platform |
| `js_wright` template (Wright framework, Bootstrap 2) | Joomlashack | `templates/js_wright` | Works; dated |
| JCal Pro (`com_jcalpro`, events and calendar modules) | Third-party | 6,742 URL refs | Works; only the rota is used |
| **OSDownloads** (`com_osdownloads`) | Joomlashack / **Alledia framework** | Wayback CSS/JS; `/downloads/*` | **BROKEN (500)** |
| **ALFContact** (`com_alfcontact`) | Third-party | 48 refs | **BROKEN (500)** |
| OSEmbed (`plg_content_osembed`), OSYouTube | Joomlashack / Alledia | Site-wide assets; the hash changed between 2024 and 2025 | Works. [Inference] Its update likely took the shared framework with it |
| JSShackSlides (`mod_jsshackslides`) | Joomlashack | Homepage | Works |
| Weatheraholic (`mod_weatheraholic`) | Third-party (OpenWeatherMap) | Homepage | Status unknown (client JS; needs a valid key) |
| JW Tabs & Sliders (`plg_content_jw_ts`) | JoomlaWorks | Leadership | Works |
| Web Links (`com_weblinks`), Wrapper, Users, Mailto, Breadcrumbs, Login, Custom HTML | Core (Weblinks is a separate package in J4+) | – | Works |
| JCE editor (`com_jce`) | Third-party | Wayback | Presumably still the admin editor |
| *Removed or abandoned:* BreezingForms, RSForm Pro (+ reCAPTCHA), Kunena, Event Gallery, FocalPoint | Various | Wayback 2019 and 2024 | Gone |

**Failure mode to design against:** of about 15 third-party extensions installed over the site's life, **2 are broken, 5 were abandoned, and the core has been EOL since 2023**. Any new platform should keep third-party add-ons to a bare minimum and prefer core features, plain files and external services the group already uses.

---

## 4. Link audit (light)

- **Totals:**
  - 68 unique outbound links in the 26 articles plus the homepage/nav, excluding Joomla footer boilerplate.
  - 27 more web links behind `weblink.go` redirects.
  - 4 `mailto:` addresses (personal addresses of third parties).
  - **34 of the 68 are `http://`.**
- **By type (approximate):**
  - Government and served agencies (Spokane County, FEMA, NWS, DHS/CISA, FCC, WA L&I): about 13
  - ARRL, ARES and WSEN: about 9
  - Local clubs and VE teams (IEVHFRA, SRG, WA7DRE, KARS, Laurel VEC, HamStudy, Handiham, USRACES, qsl.net/arocdc): about 10
  - Vendor and technical references (Palomar, DX Engineering, Arrow, KF7P, audiosystemsgroup, OHR, K7BFL, ON4WW, NK7Z, AC6V, QRZ): about 16
  - Media and social (YouTube, Facebook, Dropbox, Google Sheets, eos.org, NASA): about 6
  - Widgets (hamqsl, PA4RM, OpenWeatherMap, NWS): 4
  - groups.io: 1
- **Web-link targets (27) not resolved.** The live server returned 406 to HEAD and then blocked this client. Resolve them from `#__weblinks` in the DB dump during migration.

**Spot-check results** (34 checked with `curl -L`, 2026-09-25; full list in the scratch run). Anything not listed returned 200.

| Link | Where used | Result |
|---|---|---|
| `www.training.fema.gov/...IS-100.c` and `IS-700.b` | Welcome Letter | **Dead host** (timeout); `https://training.fema.gov/...` works |
| `training.fema.gov/...IS-200.b` and `IS-800.c` | Welcome Letter, Training & Courses | Redirects to the **IS-200.c / IS-800.d** pages (course versions superseded) |
| `lni.wa.gov/.../courseinfo.asp?P_ID=200` (bloodborne pathogens) | Welcome Letter | **404** |
| `k7id.org/article/VE Testing` | License Test Sessions | **404** |
| `ohr.com` (Oak Hills Research) | Ham Radio Links | **No DNS** |
| `wrh.noaa.gov/otx/` | Ham Radio Links | **403** (legacy NWS host; use weather.gov/otx) |
| `spokaneares-races.groups.io` | 2019 announcements | **No DNS** (group renamed) |
| `wireless.fcc.gov/uls/...` | Ham Radio Links | Redirects to fcc.gov, then 403 (bot block; update the URL) |
| `dhs.gov/safecom/field-operations-guides` | Menu | Redirects to **cisa.gov** (update) |
| `spokanecounty.org/...` (2 links) | Menu, Join, Links | Redirects to **spokanecounty.gov** (update) |
| Dropbox `/sh/…mp4` | Menu | Redirects to a new `/scl/` link (works; fragile) |
| `hamqsl.com` (http) | Homepage | Redirects to https |

These returned 200: spokaneares-acs groups.io calendar, wastateares.org (×2), felge.us (×2), arrl.org (×2), the Google Sheet, forecast.weather.gov, weather.gov/otx spotter training, the YouTube playlist, wa7dre.org, hamstudy, usraces.org, qsl.net/arocdc, spaceflight.nasa.gov, srgclub.org, vhfclub.org, and audiosystemsgroup PDF.

**Internal broken links**
- 6 menu items (5 Downloads plus "Net Preamble") return 500.
- The contact form returns 500 (linked 5 times).
- 3 file links return 500 or 404 (the ARES application and both mentoring files).
- The Email List article's links are stripped.

---

## 5. Media, assets and accessibility

**Assets worth carrying over** (`raw/www.spokares.org/images/`, 6.1 MB total)

| File | Size / px | Note |
|---|---|---|
| `logos/Spokane_County_ARES-ACS_logo_slightly_smaller_550x550.png` | **973 KB**, 550² (shown at 121 px) | The group's own logo. **Keep; get a vector (SVG) original** and export at web size |
| `logos/ACS_logo_1000x1000.png` | 408 KB, 1000² | ACS logo (its alt text wrongly says "RACES logo") |
| `logos/ARES_logo.png` | 21 KB, 121² | ARES® is an ARRL mark. Use the current ARRL-supplied artwork per ARRL guidance |
| `logos/SCDEM_Logo.png` | 428 KB, **2400²** shown at 121 px | County agency mark (alt says "Greater Spokane Emergency Management"). **Confirm the current agency name and logo and get permission** |
| `logos/textLogoACS_dash.png`, `Spokane_County_ARES_RACES_logo_dash.png` | 26 KB each | Wordmark and old logo. Replace with live text |
| `HomePageSlider/*` (8) | 31 KB to **2.7 MB** (`IMG_6574.jpg`, 3264×2448) | Real members and operations photos (van, net ops). Pick 2 or 3, resize, and **confirm consent for identifiable people** |
| `DEM_map.png` | 241 KB | Replace with a text address plus a map link |
| `floy.gif` | 27 KB | W7GBU history photo. Keep |
| `templates/js_wright/favicon.ico` | 4 KB | Regenerate from the logo |

**Accessibility and quality issues observed**
- **Alt text:** the slider alts are file names (`8091bb_6c9b…`, `IMG_6574`); a logo has wrong alt text; the solar image has none; the meeting address exists only inside an image.
- **Navigation:** all top-level items are `href="#"` dropdowns. The mobile toggle's text is literally "Mobile Menu". Menus go 3 levels deep.
- **Structure:** `lang="en-GB"` on a US site; an h1 of "Home"; sidebar headings jump between h3 and h4; a layout table for the solar widget; an iframe with `frameborder`.
- **Performance:** about 6 MB of images on the homepage (4 MB of slider images, including one 2.7 MB photo, and 1.9 MB of logos shown at 121 px); jQuery + MooTools + Bootstrap 2 + Owl Carousel; the whole JCal and weather JS loaded on every page.
- **Hidden email addresses** depend on JavaScript; there is no non-JS fallback.

---

## 6. Requirements distilled for the rebuild

### Must-have

| # | Requirement | Evidence |
|---|---|---|
| M1 | About 12 to 15 public info pages that a **non-developer** can edit (text, images, links). The edit rate is low: a handful of changes a year | Section 1: modified dates are 2017 to 2021 for most pages; only 7 articles edited since 2023 |
| M2 | A **working join path**: steps, the ARES application (PDF), the county application link, and member requirements. These are the highest-traffic real pages | Welcome Letter 12.8k hits, Join 9.9k; the PDF link is broken today |
| M3 | A **working contact path** routed by role (EC, AECs, webmaster) without exposing personal emails | 5 articles depend on the dead ALFContact; the 2019 form had a role dropdown |
| M4 | A **document library** of about 35 files in 4 or 5 categories that volunteers can add to and replace. Plain public files, no email gate | Section 3.2; every Downloads page currently returns 500 |
| M5 | **Weekly net info** (Tue 20:00, W7GBU 147.300 +600 kHz, 100 Hz, alt 146.88) plus the **current NCS rota**, updated about monthly by the net manager, with less error-prone entry than now | Section 3.1; Sept-2026 entries have wrong times |
| M6 | **Upcoming meetings, exercises and orientation** (3rd-Thursday meeting at 1121 W Gardner; 4th-Tuesday orientation) | Section 3.1; the homepage "Upcoming Events" is empty today |
| M7 | **Recover and move** the files, the DB (web links, rota), **email** (mailboxes and forwarders) and **DNS** off InMotion | Section 0 items 5 and 6 |
| M8 | **301 redirects** from the top old URLs (`/index.php/contact/join-ares-races`, `/index.php/about/*`, `/index.php/operations/*`, `/index.php/downloads/*`, `/index.php/2-uncategorised/2-ares-races-plan`, and so on) | Section 1c; external sites and search engines link to them |
| M9 | HTTPS, mobile-first, WCAG-reasonable, fast (images under 200 KB) | Section 5 |

### Should-have

| # | Requirement | Evidence |
|---|---|---|
| S1 | Weather and propagation **links or embeds** (NWS Spokane, hamqsl solar), with no API keys | Section 3.5 |
| S2 | 1 to 3 static hero photos instead of a carousel | Section 5 |
| S3 | Short "News/Announcements" posts, **or** a clear pointer to groups.io | The news blog of 2017 to 2019 died; announcements moved to groups.io |
| S4 | An ICS subscription or "next events" list sourced from the canonical calendar | JCal offered iCal; groups.io is canonical |
| S5 | A single canonical **net preamble** (HTML page plus printable PDF) | Two conflicting versions today (#33 vs the 2024 files) |
| S6 | An annual content-review checklist (Leadership, MOU contacts, FEMA course versions, links) | Stale links and course versions in section 4 |

### Drop

| # | Drop | Why |
|---|---|---|
| D1 | **Website member login and accounts** | Nothing member-only was found. groups.io is the member hub. Dropping it removes auth and PII from the site (section 3.3). Confirm with the admin first |
| D2 | Online application forms that **store PII** in the site DB | History of BreezingForms and RSForm plus a public Memberlist.pdf. Use the county's own application, the ARES PDF by email, or a hosted form |
| D3 | The 1994 ICS text, "Under Construction", the space-weather stub, the Terms & Conditions boilerplate, Mentor Programs (unless files are recovered), and the Uncategorised listing | Section 1 |
| D4 | Carousel, accordion plugin, OpenWeatherMap widget, print/email-a-friend, RSS, JCal views, web-link hit counters | Section 3.5 |
| D5 | Public **net roster** | Member personal data. Move it to groups.io Files |

### What genuinely needs server-side code

| Capability | Needs a server? | Options |
|---|---|---|
| Contact form that emails a role | **Yes**, if it is a form. Needs PHP plus authenticated SMTP (Enhance mail), or a third-party form service | **Or avoid it entirely** with role aliases (`ec@`, `aec@`, `webmaster@spokares.org`) as Enhance forwarders plus mailto links |
| Editable calendar / rota | Only if it is hosted on the site | **Offload to the groups.io calendar**, which the group already uses. The site links to it, embeds it, or renders its ICS (fetched server-side or at build time) |
| Member-only content | Only if kept on the site | **Offload to groups.io Files/Wiki** |
| Document library | No | Plain files plus an index page |
| Everything else | No | Static |

**Conclusion:** with the calendar and members offloaded, **the site is essentially static.** The only possible dynamic piece is one contact form.

---

## 7. Architecture and platform implications

The hosting constraints come from `hosting/verpex-enhance.md`:
- Enhance is PHP/MySQL shared hosting with SSH/SFTP, cron and Let's Encrypt.
- **Node.js is second-class** (package-gated, primary domain only, not redeployed on restore).
- **There is no Git deploy** (build off-server and rsync).
- The **web server kind is unknown**, so `.htaccess`-based protection is not guaranteed.
- **`mail()` is unreliable**, so use authenticated SMTP.
- The **AUP bans "download sites"** but allows a small documents library.
- **Enhance ships a WordPress toolkit with auto-updates**; its Joomla toolkit is users-only.

### 7.1 Option assessment against the evidence

| | (a) SSG (Hugo/Astro/Eleventy) + Git CMS, rsync deploy | (b) WordPress, lean | (c) Joomla 5/6 migration | (d) Grav / Kirby |
|---|---|---|---|---|
| **Covers M1 to M9?** | Yes, with the calendar and members offloaded. The contact path needs a roughly 50-line PHP + SMTP handler or role aliases. Redirects need `.htaccess` or generated stub pages (web-server dependent) | Yes. Core pages, media library for docs (no download-manager plugin needed), one form plugin + SMTP plugin, a redirect plugin or `.htaccess` | Yes, but JCal Pro, OSDownloads, ALFContact and `js_wright` must all be **replaced**, so it is a rebuild. Core Contact + SMTP; native ACL (not needed if D1) | Yes. Form plugin + SMTP; pages as files |
| **Non-dev editing** | **Weakest.** Editors need GitHub accounts plus Decap/Sveltia/Pages CMS (auth outside Verpex). Rota edits as YAML via the CMS UI | **Best.** The block editor is widely known; roles map cleanly; Enhance SSO/Collaborator access | Medium. The current admin knows J3, but the J4/5 admin is redesigned; small helper pool | Medium. Pleasant admin, very small community; Kirby is licensed |
| **Security / patch burden** (vs today's failure: an extension's shared library broke downloads and contact) | **Lowest.** No runtime to break or patch; build dependencies rot off-server instead (Hugo is a single binary, which is the least rot) | Moderate. Mitigated by Enhance **auto-updates**, ≤4 plugins from large vendors, 2FA and off-host backups. **Same failure class remains** (a plugin update can break a feature), but a smaller plugin set and core-first design shrink it | **Highest relative effort.** Manual updates, thin Enhance support, and the same "third-party extension for every feature" pattern that failed | Low to medium. File-based, but folder protection relies on the unknown web server |
| **Hosting fit on Enhance** | **Best.** Static files in `public_html`; needs only SSH/SFTP; build in GitHub Actions | **Best-supported app** (installer + toolkit); works on any web-server kind | OK (installer since 12.18); SEF needs rewrite support | Manual install; `.htaccess`/vhost dependence |
| **Migration effort** | About 15 pages → Markdown (1 to 2 days with rewrite); files → repo (after recovery); events → none (groups.io); redirects → config | About 15 pages → blocks (1 to 2 days); files → media library + Library page; events → none; redirects → plugin | Similar content effort, plus learning J5 and choosing new extensions; **no in-place 3→5 path** for these extensions | Similar to (a) |
| **Hand-off risk** | **High**: needs a git-comfortable maintainer to keep CI and deploy keys alive | **Lowest** | Medium | Medium to high |

### 7.2 Recommendation

**Build a lean WordPress site on Enhance, and push the dynamic features to services the group already runs.**

- **Calendar and net rota:** the **groups.io calendar**. The site shows the standing net facts plus a link or embed. Optionally an "upcoming" list from the group's ICS via one ICS-display plugin.
- **Members:** no site accounts. Member docs (roster, contact lists) go in **groups.io Files/Wiki**.
- **Documents:** the WordPress **core media library** plus one curated "Library" page per category. No download-manager plugin, no email gate.
- **Contact:** role forwarders on Enhance mail (`ec@`, `aec@`, `webmaster@`), plus **at most one** form plugin sending through **authenticated SMTP** (plus an SMTP plugin).
- **Plugin budget: ≤4** (form, SMTP, redirects, optional ICS display). Use a maintained block theme (no page builder, no commercial theme). Turn on Enhance auto-updates for core, plugins and theme.
- Use the **Verpex/Enhance nameservers**. Migrate mailboxes and forwarders from InMotion (Enhance can import a cPanel backup that includes email and DNS).

**Key tradeoff:** WordPress accepts a live PHP/DB attack surface and "plugin update breaks a feature" risk (the same class of failure as today) in exchange for **editing that non-developer successors can actually do**.
- The lean profile keeps the moving parts small: essentially core plus a form plugin.
- The site's history shows the bigger long-term risk is **nobody being able to maintain it** (one admin since 2017; features abandoned rather than fixed), not runtime rot.

**Choose (a) static instead** if at least two committed maintainers are comfortable with GitHub.
- It gives the lowest security and patch burden and the best portability, and the offloaded design above fits it perfectly.
- The only PHP left would be a small contact handler, or none if only role email aliases are used.

**Not (c) Joomla 5/6:** every extension the site relies on must be replaced anyway, the Enhance support is thinnest, and it repeats the extension-per-feature pattern that failed. Its one advantage, native ACL, is moot if member content moves to groups.io.

**Not (d):** no advantage over (a) or (b) for this content, and its protection rules depend on the unknown web server.

### 7.3 Groups.io offload: what the evidence supports

- **Supported:**
  - The Calendars menu already points only at groups.io.
  - Member announcements moved there in 2019.
  - The email list is groups.io.
  - The JCal calendar is used only for the rota.
- **Unverified:** whether the calendar and ICS are **publicly viewable** (the calendar URL returned 200 anonymously, which may be a public view or a login page), and whether the group plan includes Files/Wiki (a groups.io Premium feature). **Confirm with the group owner.**
- **Fallback if groups.io is unsuitable:** a Google Calendar owned by a role account (`calendar@spokares.org`), edited by the net manager and embedded on the site.

### 7.4 Must confirm before locking this in

**About the hosting** (these extend `hosting/verpex-enhance.md` §5.8):
1. The package includes the **WordPress installer** and toolkit auto-updates, SSH/SFTP, the email role and the DNS editor.
2. The **web server kind** (it decides `.htaccess` redirects and whether old-URL redirects can be rules or must come from a plugin).
3. **SMTP send limits**, and whether `mail()` is enabled (plan on SMTP regardless).
4. That a **cPanel import of email and DNS** from InMotion works, or a manual mailbox migration plan.
5. That the **AUP accepts a public library** of about 35 to 50 PDF/PPTX files (probably under 500 MB; confirm actual sizes after recovery).
6. The **backup schedule**, plus our own off-host backups.
7. **Collaborator access** for volunteer editors.

**About the organization and content**
1. **Is anything actually login-restricted today?** Check `access` columns in a DB dump, or ask W7TSC.
2. **Who edits the rota**, and will they use the groups.io calendar? Is the calendar/ICS public?
3. The **official naming**: "ARES - ACS" vs "ARES/RACES/ACS", and the agency's current name and logo (Spokane County Emergency Management vs "GSEM").
4. **Grab the InMotion cPanel full backup now.** It is the only source of the ~33 library files, the web-link targets, the JCal data and the mail.
5. Whether the **ARES/RACES Plan** (2009/2018 text) is still current or should be replaced by the county's current ESF-2 attachment.
6. Whether any online join form is wanted at all, given the county's own application and the privacy history.
