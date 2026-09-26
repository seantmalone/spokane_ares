# ag7qp.com interim content: capture and triage (fetched 2026-09-25)

Since spokares.org was hacked, **https://ag7qp.com/** has hosted Spokane County ARES-ACS's current information. It is the site of the Eastern Washington Section Emergency Coordinator, Frank Hutchison, AG7QP. The home page says: "The spokares.org website was hacked. This page acts as a temporary repository for general information for the Spokane County ARES-ACS group." (`home.md`). The Spokane page itself says it is "a temporary page … while the hacking attempt of spokares.org is dealt with" (`spokane-county-ares-acs.md`). That makes this capture the rebuild's primary migration source.

## What is here

| Path | What |
|---|---|
| `research/external/ag7qp/sitemap.xml`, `urls.txt` | Sitemap as fetched: 33 URLs, every `lastmod` = 2026-09-23T02:16:26Z. That is the site-wide publish time, not a per-page edit date. |
| `research/external/ag7qp/<slug>.html` (33 files) | Raw HTML of **every** sitemap page, fetched 1 s apart. The home page is `home.html`. **Redacted in place**: personal phone numbers and e-mail addresses are replaced with `[redacted: contact detail]`. |
| `research/external/ag7qp/fetch-manifest.tsv` | Per file: source URL, **sha256 and byte size of the original unredacted response**, and the redaction count. |
| `research/content/interim-ag7qp/<slug>.md` (33 files) | Clean Markdown. Front matter has `title`, `source_url`, `fetched: 2026-09-25`, `raw_html`, `nav_parent` and `site`. |
| `research/content/interim-ag7qp/docs/*.md` (4 files) | Redacted text extractions of the Spokane-owned documents linked from the site: the 3 net preambles and the ARES membership application. The original `.docx` files are **not** stored because they contain personal e-mail addresses. |
| `research/tools/ag7qp_to_md.py` | Converter and redactor. Run from the repo root with `.venv/bin/python research/tools/ag7qp_to_md.py [--redact-raw]`. It is safe to re-run on the redacted HTML. |

**Conversion notes**
- The site-wide header nav and footer are dropped. The footer carries the SEC's name, callsigns, phone and e-mail, and "© 2025".
- Zyro elements are read in desktop grid order. Web forms and the one embed on the home page become placeholders.
- Three pages lay out columns with runs of spaces: `nets-in-the-inland-northwest`, `pota-sota` and `programming-radios`. Those runs are rendered as ` | ` separators. They are not real tables, and empty columns collapse.
- Images have no alt text on the source, so the asset filename is used.
- The **roster PDFs were not downloaded**. That covers `net-roster---2026-08-04…pdf` (Spokane page) and `vhf-roster---2025-10-28…pdf` (EWSEN). The links are kept as-is, and the rosters may contain PII.
- Redaction keeps only these institutional mailboxes: `nws.spokane@noaa.gov`, `public.education@mil.wa.gov`, `vec@arrl.org` and `pendoreillecountyares@gmail.com`. The street address of one unverified class venue (possibly a residence) is also redacted, on `licenses.md`. Names and callsigns are kept.

## Site structure and triage

The ag7qp.com menu, taken from the header nav in `home.html`, is below. Classification codes:
- **MOVE**: Spokane-owned content that belongs on the new spokares.org.
- **PARTIAL**: move the Spokane-specific part and link the rest.
- **LINK**: Section-level or general content. Keep it on ag7qp.com and link to it.
- **SKIP**: not relevant to the rebuild.

| Page (`<slug>.md`) | Nav parent | Class | Why / what to take |
|---|---|---|---|
| `spokane-county-ares-acs` | ARES-ACS | **MOVE** | The group's live page. It has the Tuesday net-control rota (Sep 22 – Oct 27, 2026), the Sep–Oct 2026 event list, the Winlink assignment schedule (22-Sep-26 → 22-Dec-26), and download links to the 3 preambles and the roster. These map to the new site's Nets, Calendar and Downloads pages. |
| `docs/net-preamble-2026-03-04` | (download) | **MOVE** | Current weekly-net script. It replaces Joomla article 8. |
| `docs/net-preamble-simplex-2026-03-04` | (download) | **MOVE** | Script for the 5th-Tuesday simplex net. There is no Joomla equivalent. |
| `docs/net-preamble-gmrs-2025-08-19` | (download) | **MOVE** | Script for the Spokane County ACS GMRS net. There is no Joomla equivalent. |
| `docs/ares-membership-application-2024-11-07` | (download) | **MOVE** | ARES application plus the SCEM non-disclosure agreement. It replaces the broken Joomla download. |
| `acs-task-book` | ARES-ACS | **MOVE** | "Spokane County Auxiliary Communication System (ACS) Position Task Book", covering the New Member, RADO, S-RADO and Leadership levels. It is the web version of `acs-task-book-requirements---version-2024-08-09.docx`, which has the same content and was not stored. |
| `new-members-orientation` | ARES-ACS | **MOVE** | The Spokane orientation class, "usually held the 4th Tuesday of each month" at 1800 at the DEM. It includes the ARES/ACS/AUXCOM explainer, qualifying, FOGs, the hands-on training list and 2 structure diagrams (`organizations-….jpg`, `spokane-structure-….jpg`). |
| `ares-acs` | (top) | **PARTIAL** | MOVE the "Spokane County EmComm" block: the ARES and ACS definitions, task-book links and join buttons (ACS county application, ARES application `.docx`). LINK "Standing Order 1b" (EWA Section SO, 2026-01-16) and "I am Safe Messaging", which are Section-level. |
| `home` | (top) | **PARTIAL** | MOVE the SCEM location and ARES-ACS meeting block, the Spokane lines of "Monthly Recurring Events" (2nd Sat workshop, 3rd Thu training) and the Spokane public-service events (Bloomsday 2027-05-02, Climb for the Cure 2027-06-13). LINK the SET 2026 instructions (Section), W1AW/7 (state) and the Radio Amateur's Code. SKIP the GoFundMe notice (it is for the Church ERC "Spokane Coordinating Council", not ARES-ACS). |
| `contact` | (top) | **PARTIAL** | MOVE the meeting block ("All meetings are held at the DEM – changes announced on the weekly ARES-ACS Net"). The contact form belongs to the SEC's site. |
| `nets-in-the-inland-northwest` | General Information | **PARTIAL** | MOVE the Spokane rows: ARES Spokane County, SCEM repeater 147.3000 +0.600 100 Hz, Tue 8:00 PM; "0730 Bunch", SCEM 147.3000, Mon–Fri 7:30 AM; ACS Spokane County GMRS 7 462.700 +5 MHz 141.3 Hz, 3rd Tue 7:30 PM. LINK the full regional list. |
| `emcomm-groups` | ARES-ACS | **PARTIAL / LINK** | Section directory by district and county. The **Spokane** entry lists "Asa Laughton, W7TSC, EC/RO", links `https://www.spokares.org/` and gives the DEM meeting schedule. Use it as a leadership cross-check. LINK the page for the other counties. |
| `winlink` | General Information | **PARTIAL** | LINK the how-to and the Winlink-mapping section. MOVE the Spokane fact: a Winlink workshop "every second Saturday from 12:30 to 3:30 PM" at SCEM, 1121 W. Gardner Ave. |
| `general-information` | (top) | **PARTIAL** | Reuse the Spokane "Sources of Reliable Information" subset (Alert Spokane/CodeRED, SREC 911 app, Spokane County EM, City of Spokane EM, NWS Spokane) on a public preparedness page. LINK the rest (Part 97, NTS, tool links). |
| `ares-task-book` | ARES-ACS | **LINK** | The SEC's guide to the ARRL ARES Position Task Book (July 2024), usable section-wide. It links an **older** Spokane application (2024-09-15), so the new site should link the 2024-11-07 form instead. |
| `go-kits` | ARES-ACS | **LINK** | Section-authored go-kit guide. It mentions "Current Spokane ACS-FOG", and the orientation and ACS task book point to it. |
| `ics-213`, `fema-course-study-helps` | ARES-ACS | **LINK** | General how-tos, referenced by both task books. |
| `eastern-washington-section` | ARES-ACS | **LINK** | EWA Section SM and SEC, the 3 Monday EWSEN nets, and the EWSEN preambles and roster. |
| `emergency-communications-organizations-in-the-inland-northwest` | (not in nav) | **LINK** | Regional explainer. It is useful for wording, e.g. "RACES (Radio Amateur Civil Emergency Services) is now incorporated into ACS", and has IEVHFRA/SDXA/KBARA/IE-GMRS blurbs. |
| `when-the-lights-go-dark-…` | (not in nav) | **LINK** | Public-facing radio-options article, a good "Get prepared" link. |
| `licenses`, `license-exams`, `license-renewals`, `gmrs-license`, `radio-servicesfirst-radio`, `programming-radios` | Licenses / General Information | **LINK** | Licensing classes (AD7FO syllabi, AG7QP-managed exams), VE sessions and radio help. These supersede Joomla articles 11 and 42, so link rather than duplicate. |
| `preparing-for-public-service-communications` | (not in nav) | **LINK** | 9 tips for public-service events. Could back an ARES "Public service events" page. |
| `cert`, `cert-ptbs`, `cert-go-kit` | CERT | **LINK** | Spokane County CERT is a separate SCEM volunteer program, not ARES-ACS. At most, link it from a partners section. |
| `disaster-resources`, `emcomm-resources`, `national-traffic-system`, `pota-sota`, `ham-radio-topics`, `bsa-radio-merit-badge` | various | **LINK / SKIP** | General or hobby content. `pota-sota` is a Spokane-area POTA list credited to NZ2S. |

**Rule of thumb (inference):** anything written *about the Spokane County group* moves to spokares.org, and so does anything the group *operates*: its nets, meetings, scripts, forms, task book and orientation. The SEC's teaching pages and section directories stay on ag7qp.com and get linked, so they have one maintainer.

## What is newer than the old Joomla site (topic-level diff vs `research/content/live/*.md`)

| Topic | Joomla (article, published) | ag7qp.com (2026) | Verdict |
|---|---|---|---|
| Meeting schedule | Art. 27 (2017-09-24): "Third Thursday … 7 pm to 9 pm". Art. 41 (2018-03-02): 3rd Thu 1900. | 1st Tue 5:30 PM staff meeting; 2nd Sat 9:00 AM workshop; 3rd Thu 6:00 PM training; 4th Tue 6:00 PM new-member orientation; 2nd Sat 12:30–3:30 PM Winlink workshop. All at the DEM (`home`, `contact`, `emcomm-groups`, `new-members-orientation`, `winlink`, `spokane-county-ares-acs`). | **ag7qp newer.** The training night moved from 7 PM to 6 PM, and 3 meetings were added. |
| Weekly net script | Art. 8 (2017-06-03): "Spokane County Amateur Radio Emergency Service Net … ARES/RACES Repeater … 147.30 MHz". | 2026-03-04 `.docx`: "Spokane County ARES-ACS Net" on "the Spokane County ACS Repeater", with an alternate repeater. Winlink nights are the 2nd and 4th Tuesdays. In months with a 5th Tuesday the net runs on the simplex frequency. Adds "Department of Emergency Management Personnel" to the officials call. The simplex script names 147.300 MHz as primary and 146.880 MHz as alternate. | **ag7qp newer.** RACES→ACS wording, alternate repeater, simplex and Winlink cadence. |
| Other nets | none | GMRS net: 3rd Tue 1930 on the "WRCY281 GMRS repeater" (preamble), listed as GMRS 7 462.700 / 141.3 Hz (`nets…`). "0730 Bunch" Mon–Fri on 147.300. Hospital Net following the regular net (Oct 6, 2026); its channel is given on ag7qp.com but is SCEM FOUO material, so do not republish it (`../../decisions.md` D13). | **New.** |
| Net-control rota and calendar | JCal Pro calendar (`research/data/calendar-events.json`). Its latest entries are Sep 2026 NCS assignments (Sep 1 KK7SAB … Sep 29 "NZ2S – Simplex"). | Rota continues into October: Oct 6 Open, Oct 13 AE7RJ, Oct 20 WA7LNC, Oct 27 Open. Also Sep 26–27 WSDOT exercise, Oct 3 SET, Oct 6 staff meeting, Oct 10 workshop, Oct 15 ShakeOut and training, Oct 17 SHARES training, Oct 24 State COMMEX. | **ag7qp newer.** Sep 22 and Sep 29 agree in both. |
| Winlink program | Art. 3: NV2Z is "W7GBU-10 Manager & Winlink Coordinator". | Biweekly Winlink assignments on ICS-213 to NV2Z and AG7QP, 22-Sep-26 → 22-Dec-26. A Winlink how-to and a 2nd-Saturday workshop. | **New.** |
| Joining | Art. 26 (2017-07-22): "ARES – RACES – ACS", ARES form via a (now broken) OSDownloads link, county application link. | ARES application `.docx` 2024-11-07 (with the SCEM NDA). Same county FormCenter application. Orientation page. "RACES … is now incorporated into ACS". | **ag7qp newer.** |
| Training requirements | Art. 41 (2018): IS-100.C and IS-700.B required; IS-200/800 recommended; defensive driving, BBP, SKYWARN, helicopter safety and HIPAA optional. | ACS Task Book v2024-08-09 (`acs-task-book`): New Member requires IS-100/200/700/800, ARRL Basic EmComm, monthly net check-in, quarterly meetings, kits, HT programming, Alert Spokane, HIPAA and ICS-309 logging; then RADO, S-RADO and Leadership. ARES PTB guide (ARRL July 2024). | **ag7qp newer.** It replaces the 2018 welcome letter. |
| About / what ARES and ACS are | Art. 4 (2017-06-03): short blurb. | `ares-acs` and `new-members-orientation` explain ARES vs ACS vs AUXCOM. ACS members are background-checked, can work in EOC/ICP/hospitals/shelters, and are activated by the AHJ. | **ag7qp newer.** |
| Leadership / staff | Art. 3 (2017-06-03, apparently updated since): SM KA7LJQ, ASM AA7RT, SEC AG7QP, EC W7TSC, AECs NV2Z, K7MHG, K7DSR, Net Manager AG7QP, PIO open. | No staff page. `emcomm-groups` lists the Spokane EC/RO as W7TSC. `eastern-washington-section` lists SM KA7LJQ and SEC AG7QP. The Spokane page names AG7QP as the contact. | **Joomla is more detailed.** ag7qp confirms only SM, SEC and EC. The AEC roster is **not** confirmed by ag7qp. |
| Equipment lists | Art. 21 (2017-07-22). | `go-kits`: kits by duration (2–6 h, 12–24 h, 24–72+ h), plus WA State Winlink forms and the ACS-FOG. | **ag7qp newer** (LINK). |
| License classes and testing | Art. 11 and 42 (2017): AA7RT as tester, AD7FO classes, KARS old URL. | `license-exams` and `licenses`: WA7DRE/Laurel VEC, KARS (new URL), GLAARG online, ARRL youth $5, AD7FO classes managed by AG7QP; class dates Jan–May 2026. | **ag7qp newer** (LINK), but its dates are now stale (see below). |
| Links | Art. 24 (2017). | `general-information`: much larger and current. | **ag7qp newer** (LINK). |
| Radio Amateur's Code | Art. 6 (2017). | `home`: modernized wording ("IARU Radio Society", "their"). | Equivalent; use the newer wording. |
| Joomla-only topics | ARES/RACES Plan (art. 2), W7GBU call sign (5), repeater etiquette (7), NCS principles (9), ICS primer (10), alert levels (20), e-mail list (23), mentor programs (25), SKYWARN (37), RFI (62), MOU with IEVHFRA (68), terms and privacy (12, 13). | Not on ag7qp. **W7GBU is not mentioned anywhere on ag7qp.com.** `docs/*` refer to "the Spokane County ACS Repeater". | Old content is still the only source. Keep it or retire it deliberately. |

## Stale, inconsistent or risky items spotted

1. **References to the dead site in current documents.** The simplex preamble (2026-03-04) points members to "a roster on the Spokane County ARES website", "the contact form on the spokares.org website" and a contact report "available on our website". The Joomla contact form returns HTTP 500. `emcomm-groups` still links the Spokane group to `https://www.spokares.org/`, the hacked site. Fix these at cutover.
2. **Preamble text defects** (2026-03-04 docs). The weekly script has an unclosed bracket ("on [the Spokane County ACS Repeater."). The simplex continuation reads "meeting every Tuesday at 2000 hours local time on." with the repeater missing. Neither script states the **simplex frequency**, so it is still unverified (see `design/content-brief.md`, net section).
3. **Two versions of the ARES application.** `ares-acs` links 2024-11-07 and `ares-task-book` links 2024-09-15.
4. **Callsign mismatch for the Section Manager.** `emcomm-groups` (Yakima) has "Jo Whitney … KJ7LJQ", while `eastern-washington-section` has "KA7LJQ", as does Joomla art. 3. KJ7LJQ is probably a typo (inference; not verified).
5. **Name spelling.** `home` has "Bloomsday Contact Dan Croskey", while Joomla art. 3 has "Dan Croskrey **NV2Z**". Verify before publishing.
6. **Wrong link.** `home` "Monthly Recurring Events" links "1st Thursday – SDXA meeting" to `https://kbara.org/`. The SDXA site is `https://sdxa.org/`, per `emergency-communications-organizations…` and `nets…`.
7. **Event mismatch.** `home` lists "October TBD – Catastrophic Communications Exercise 2026", while `spokane-county-ares-acs` lists "October 24 – State COMMEX". These are possibly the same event (inference).
8. **Past-dated content still shown.** On `licenses`, the class schedule (Jan 31, Feb 21, Feb 28 "not held", May 2, 2026) is past, and "Extra class will start September 24" and "FREE Live Online Classes Oct 4 – Nov 22" give no year. On `cert`, "CERT Program Manager Course – August 11-13" gives no year and is past. The footer says "© 2025". The Sep 22 NCS slot and the Sep 22 Winlink assignment on the Spokane page are past, as expected for a rolling list.
9. **Rota gaps.** The Oct 6 and Oct 27, 2026 net-control slots are "Open".
10. **Data-quality nits.** `nets…` gives the tone "77.09 Hz" three times, which is probably 77.0 Hz (inference). The county application appears under both `spokanecounty.org` (`ares-acs`) and `spokanecounty.gov` (`cert`) FormCenter URLs.
11. **Home page omissions.** The home and contact meeting blocks list only 3 meetings. They omit the 4th-Tuesday orientation and the 2nd-Saturday Winlink workshop, which are stated on other pages.
12. **Unredacted duplicates elsewhere in the repo.** `research/sources/government/ag7qp_*.html` (10 files) and `research/external/ag7qp.com.html` / `ag7qp.com_spokane-county-ares-acs.html` are byte-identical to today's *unredacted* originals (sha256 matches `fetch-manifest.tsv`). They contain personal phone numbers and e-mail addresses. Delete them or redact them with the same tool.

## Spokane facts this capture adds or confirms (for the rebuild)

- **Weekly net:** Tuesday 2000 local, "Spokane County ACS Repeater". The simplex preamble gives the primary as 147.300 MHz and the alternate as 146.880 MHz. `nets…` gives 147.3000 +0.600, 100 Hz. The 146.880 repeater is IEVHFRA's (`emergency-communications-organizations…`), which fits the 2023 MOU (Joomla art. 68; inference). The net runs on simplex in months with a 5th Tuesday, and the 2nd and 4th Tuesdays are Winlink nights (`docs/net-preamble-2026-03-04`, `docs/net-preamble-simplex-2026-03-04`).
- **GMRS net:** 3rd Tuesday 1930 on the WRCY281 GMRS repeater (`docs/net-preamble-gmrs-2025-08-19`). WRCY281 is K7DSR's GMRS call (`emcomm-resources`), so the repeater is presumably his (inference).
- **Meetings:** see the diff table above. All are at the DEM, 1121 W. Gardner Ave, Spokane, WA 99260.
- **Contacts named on ag7qp for Spokane:** AG7QP ("For additional information … contact Frank Hutchison, AG7QP"); EC/RO W7TSC (`emcomm-groups`). Membership applications go to AG7QP (application form).
