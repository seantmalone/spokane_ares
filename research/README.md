# spokares.org rebuild: research index (start here)

**Project.** This is a complete rebuild of https://www.spokares.org/, the website of **Spokane County ARES-ACS**. The group is one team of amateur-radio emergency volunteers who serve both as an ARRL ARES unit and as Spokane County Emergency Management's Auxiliary Communication System (ACS), which absorbed RACES.

**Why a rebuild.**
- The current site runs **Joomla 3**, which reached end of life in 2023, on InMotion.
- It was **hacked in 2026**.
- Its downloads and contact form return HTTP 500.
- The group's live information has moved temporarily to the EWA Section Emergency Coordinator's site, **ag7qp.com**.
- The new site will be hosted on **Verpex Enhance** reseller hosting. The platform (lean WordPress or a static generator) is still open, and it depends mainly on who will maintain the site.

**What this folder holds.** Everything learned so far:
- the old site's content and functions
- 22 years of Wayback history
- the document library
- the related organizations
- the hosting platform
- 12 homepage design concepts

`organization.md` explains what the group is, and `decisions.md` lists what must be decided and by whom.

**Status as of 2026-09-25:** the research phase is complete. No one in the group has been interviewed yet, and no rebuild code exists. All "current" facts come from public web pages and must be confirmed with leadership before publishing.

---

## Rules for anyone continuing this work

1. **Cite a source** (a file path or URL) for every fact. Mark your own reasoning as **INFERENCE**.
2. **Never invent** names, callsigns, frequencies, dates, member counts or times.
3. **Store no personal contact details** of individuals: no phone numbers, home addresses or personal e-mails. Role, name and callsign are fine.
4. **Do not open, download or copy rosters or member lists.** This covers `wayback/snapshots/spokares.org/Memberlist.pdf`, the Feb 2009 net roster `.doc`, and the roster PDFs on ag7qp.com.
5. **Treat all people and roles as "verify before publishing."**
6. **Do not re-crawl www.spokares.org from this machine.** The server answered 406 and then timed out (`site-functionality-analysis.md`, header). The host is also compromised.

---

## Start here: reading order

1. **This file.** Read the cheat-sheet below.
2. **`organization.md`**: what the group is, who leads it, the two authority chains, the legal basis, infrastructure, nets and meetings, the membership path and the web status.
3. **`decisions.md`**: 15 open decisions and blockers (D1-D15), each with owners, a recommendation and evidence, plus question lists for Verpex, AG7QP, W7TSC, SCEM and others. D13 includes the **"Never publish" rule** for SCEM FOUO material; D15 is the **page inventory** (slugs, sources, owners, review dates).
   - **`data/facts.yaml`**: the same facts as one machine-readable file (value, source, status, owner, last verified), keyed to D13.
   - **`redirects.csv`**: every known old URL with its 301 target, 410 or no rule (D15).
4. **`site-functionality-analysis.md`**: what the old site contains and does, and the requirements (M1-M9, S1-S6, D1-D5) and platform assessment for the new one.
5. **`content/interim-ag7qp/README.md`**: the 2026 content on ag7qp.com (the newest source), with the move/link triage and its stale items.
6. **`documents-inventory.md`**: every downloadable document, and the recovery plan for the 21 files that exist only on InMotion.
7. **`hosting/verpex-enhance.md`**: what Verpex Enhance can and can't do, verified by three independent skeptics.
8. **`../design/REVIEW.md`**: the 12 homepage concepts ranked, with the recommended hybrid. Then **`../design/content-brief.md`**, which holds the approved copy and the "facts not to invent" rules.
9. As needed:
   - **`history.md`**: the timeline, website eras, leadership over time, and lessons from how past sites decayed.
   - **`orgs/relationships.md`**: 25 related organizations and a people table.

---

## Key facts cheat-sheet

Details and sources are in `organization.md` unless another file is named.

1. **Name.**
   - In use today: "Spokane County ARES-ACS".
   - Legacy: "ARES/RACES".
   - The final name is undecided (`decisions.md` D6).
   - The ACS expansion varies: System, Systems, Service or Services. The group's seal says "Auxiliary Communications".
2. **Two hats, one team.**
   - **ARES** is an ARRL program, open to any licensed ham.
   - **ACS** is SCEM's program. It requires the county application and a **Sheriff's background check**, and lets members work in EOCs, ICPs, hospitals and shelters.
3. **Local leadership** (staff page, modified 2026-03-24; verify):
   - EC/RACES Officer and website admin: **W7TSC**.
   - AECs: **NV2Z** (public events, W7GBU-10, Winlink), **K7MHG** (hospitals), **K7DSR** (logistics and comms; W7GBU trustee) and **AG7QP** (net manager).
   - PIO: **open**.
4. **Above the unit:**
   - EWA Section Manager: **KA7LJQ**.
   - Section Emergency Coordinator: **AG7QP**, who also hosts the interim page.
   - District 9 DEC: **WA7EC**.
   - County: the Sheriff is Director of Emergency Management. The SCEM Communications Specialist owns ACS; the current holder is unknown.
5. **Weekly net:**
   - **Every Tuesday at 2000 on W7GBU, 147.300 MHz, +600 kHz, 100 Hz.**
   - The 2nd and 4th Tuesdays are Winlink nights.
   - A 5th Tuesday runs on simplex. **The simplex frequency is not verified.**
   - The alternate repeater is **146.880**, IEVHFRA's.
   - **Never show the net at 22:00.** Three JCal entries have that time in error.
6. **Meetings** at **SCEM, 1121 W Gardner Ave**:

   | Meeting | When |
   |---|---|
   | Staff meeting | 1st Tuesday, 1730. **Internal until the EC says otherwise** (ag7qp calls it open to the public; `decisions.md` D13 #16) |
   | Workshop | 2nd Saturday, 0900-1200 |
   | Winlink workshop | 2nd Saturday, 1230-1530 |
   | Training | **3rd Thursday**; 1800 per ag7qp.com only, 1900 in every older source up to a 2025-09-18 listing. **Unconfirmed** (D13 #4) |
   | New Member Orientation | "Usually" 4th Tuesday, 1800; not confirmed as a standing session (D13 #17) |

7. **W7GBU** is a club license held by the "Spokane County ARES RACES Support Group".
   - Trustee: K7DSR.
   - **Expires 2027-02-15.** Renewal is allowed from about 2026-11-17 (callook.info, re-checked 2026-09-25).
8. **Domain.**
   - Registrar: **GoDaddy**.
   - **Expires 2027-03-10.**
   - Last changed 2026-08-12, for an unknown reason.
   - The account holder is unknown (RDAP, 2026-09-25).
9. **DNS and e-mail.**
   - NS: InMotion.
   - **MX = `0 spokares.org.`** Mail is live on InMotion. It is **not** a null MX, as an earlier brief said (dig, 2026-09-25).
   - SPF hard-codes an InMotion IP; a DKIM key exists at `default._domainkey`; there is **no DMARC** record (dig, 2026-09-25; `decisions.md` D3).
10. **Old site status.**
    - Joomla 3 with the `js_wright` template, 26 articles, about 15 extensions over its life.
    - **Downloads and the contact form return HTTP 500** (missing Alledia framework).
    - No visible spam today.
    - Hacked in 2026; the date and vector are unknown (`site-functionality-analysis.md`).
11. **Interim site.** ag7qp.com (Hostinger/Zyro, 33 pages, published 2026-09-23) is the **newest** source for:
    - the rota, events and Winlink assignments
    - the 2026 preambles
    - the application (2024-11-07)
    - the ACS Task Book (2024-08-09)
    - the orientation page

    It publicly links a roster PDF, which probably contains personal data (`content/interim-ag7qp/README.md`).
12. **Member hub.** groups.io `spokaneares-acs`:
    - 107 members and 7 subgroups.
    - In use since at least **2018-03**.
    - It is where the calendar and member files (the ACSFOG) live.
    - Joining needs **moderator approval**; the archive, Files and Wiki are members-only; the group still links www.spokares.org (anonymous check, 2026-09-25).
    - Whether its calendar and ICS feed are public is **still unverified**: ask the owner via `main+owner@SpokaneARES-ACS.groups.io` (D9, D13 #20).
13. **Document library.**
    - All 46 OSDownloads files exist **only on InMotion**; Wayback has none.
    - 21 are to be recovered, 23 become links to FEMA/ARRL/county, 5 are migrated from ag7qp, and 26 are retired.
    - Downloads were **never** gated (`documents-inventory.md`).
14. **Hosting target.** Verpex Enhance E30 ($219/yr at list price) (`hosting/verpex-enhance.md`):
    - PHP/MySQL containers; **WordPress is first-class** (auto-update toolkit).
    - Node is second-class, and there is **no Git deploy**.
    - The web server is unconfirmed (probably LiteSpeed).
    - The AUP bans download sites.
    - Verpex changes IPs often, so **use Enhance nameservers**.
15. **Recommended platform:** **lean WordPress** (4 plugins or fewer, no site accounts, the calendar on groups.io), with two named editors. Choose static only if two git-comfortable maintainers exist (`decisions.md` D5).
16. **Recommended design:** a hybrid.
    - The top three concepts: Field Guide 8.21, When the Lines Go Down 7.92, Open Door 7.83.
    - The hybrid combines Open Door's skeleton and "This week" card, Field Guide's Fig. 1 and quiz, and a short Lines Down message-path section.
    - Drop the public "Condition Green" badges (`../design/REVIEW.md`).
17. **Legal basis:**
    - 47 CFR Part 97 and **97.407** (RACES).
    - **RCW 38.52**.
    - **WAC 118-04** (amended effective 2025-12-27). Registration is the "prerequisite" for emergency-worker protection; coverage is tied to state **mission numbers**.
    - County CEMP (March 2021): ACS supports ESF-2, 5, 6, 8 and 9.
18. **The City of Spokane** has run its own emergency management since 2020. SCEM's inter-local agreement covers the unincorporated county, Spokane Valley and 11 other towns.
19. **Joining.**
    - Steps: the ARES application (includes an SCEM NDA), then the county Volunteer Emergency Worker Application (FormCenter 276), then the background check.
    - ACS Task Book levels: New Member → RADO (county ID card) → S-RADO → Leadership.
    - Ongoing minimums: a net each month, a meeting each quarter, and one field operation each year.
20. **Agreements.**
    - Only one written local agreement exists: the **IEVHFRA MOU, Rev. 5, 2023-05-06**. It covers 146.88 as backup and packet paths into Winlink.
    - National ARRL agreements (FEMA 2023, Red Cross 2021, NWS 1986) cover the rest.
21. **Continuity.**
    - The Tuesday net and the 3rd-Thursday meeting have run since 2004.
    - The SKYWARN Recognition Day station at NWS Spokane has run "since 1999".
    - Web eras: static 2004-15, Wix 2015-16, Joomla 2017-26 (`history.md`).
22. **Why past sites decayed:**
    - one maintainer each time
    - facts buried in long documents
    - extension sprawl
    - no redirects between eras
    - an end-of-life platform left running
    - public PII
    - credentials held by one person

    Each has a rule for the rebuild (`history.md` §10).
23. **Stale facts to avoid:**
    - ALERT Spokane is now **Regroup** (April 2026), not "Code Red".
    - The W7GBU trustee is **K7DSR**, not WA7AQH.
    - The FEMA MOA is from **2023**, not the 1984 MOU.
    - Current FEMA courses are IS-100.c, 200.c, 700.b and 800.d.
24. **Money and legal entity.** "ARES has no bank account" (IEVHFRA newsletter, 2019). The FCC-licensee Support Group is the only entity found.

---

## Artifact table

**Confidence key:**
- **V** = verified (independent re-check recorded).
- **S** = sourced (every claim cited, not re-verified).
- **L** = light pass (mixed confirmed/likely/speculative).
- **G** = generated or derived data (faithful to its source, but the source may be stale).
- **R** = raw capture.
- **!** = contains PII or unredacted contact details. Do not publish; do not commit.

All dates are 2026-09-25 unless stated.

### Synthesis documents (read these)

| Path | What it is | How it was produced | Confidence |
|---|---|---|---|
| `README.md` | This master index | Written from all files below | S |
| `organization.md` | What the organization is: identity, mission, roles, the two chains, legal basis, partners, infrastructure, nets, membership, channels, web status | Consolidated from the research corpus, plus 3 checks made while writing it: callook W7GBU, RDAP, dig | S; people rows are **unverified** |
| `decisions.md` | D1-D15 open decisions and blockers (D15 = page inventory), the "Never publish" rule, plus question lists | Synthesised from all research files | S (the recommendations are ours, not decided) |
| `history.md` | History of the organization and its websites from 2000 to 2026: eras, a dated timeline, naming, leadership, activities, locations, what to revive, lessons | Built from `content/*`, `wayback/*` and `orgs/*`. 19 INFERENCE marks. The rosters were not opened | S |
| `site-functionality-analysis.md` | Content inventory of the old site (26 articles, IA), functionality (calendar, library, login, contact, widgets, extensions), link audit, media, requirements, platform assessment | A quick (~20 min) desk analysis of the crawl, plus a light live and link check | S, with 2 errata (see below) |
| `documents-inventory.md` | 76 documents (OSDownloads, legacy, external, ag7qp), with a recommendation for each; recovery plan; leadership questions | Parsed the Wayback OSDownloads markup, CDX queries, and ag7qp pages. Appendix C holds the verification log | S/V |
| `hosting/verpex-enhance.md` | Verpex Enhance capabilities, limits, constraints, stack options, recommendation, questions for Verpex | 5 note files plus the public OpenAPI spec, then **3 independent skeptics** re-checked 29 claims (26 fully confirmed; `mail()` claim corrected) | **V** |
| `orgs/relationships.md` | Map of 25 related organizations (core, related, peripheral); a people table; 17 contradictions; unknowns | `tools/workflows/spokares-related-orgs.js` in light mode: 5 discovery lenses, then merge, then map | **L** (not deep-profiled) |
| `orgs/_backlog.md` | Per-org open questions, plus instructions for deep mode | The same workflow | L |
| `content/interim-ag7qp/README.md` | Triage of ag7qp.com (MOVE, PARTIAL, LINK, SKIP), diff against Joomla, stale items, Spokane facts | Hand analysis of the 33 converted pages | S |
| `../design/REVIEW.md` | 12 homepage concepts scored by 4 judge lenses; top 3, grafts, fix-before-ship list | Design workflow (see below) | S (judgement) |
| `../design/content-brief.md` | Copy source for design: names, mission lines, activities, nets, joining, partners, roles, navigation, **facts not to invent**, assets | Built from the crawl plus the ag7qp pages | S. **Partly stale**: see errata |
| `../design/concept-specs.md` | Art direction for the 12 concepts, plus the rules every concept follows | Written from the brief and the inspiration survey | S |
| `../design/inspiration.md` | Survey of 44 comparable sites and the patterns worth copying | curl plus headless-Chrome screenshots | S |

### Captured content and data

| Path | What it is | How it was produced | Date | Confidence |
|---|---|---|---|---|
| `content/index.md` | Index of every converted page | `tools/html_to_md.py` | 2026-09-25 | G |
| `content/live/*.md` (26) | Every live Joomla article as Markdown, with front matter (id, published date, hits, URL) | `tools/html_to_md.py` from `raw/by-id/` | Crawl 2026-09-25 | G. Faithful, but the content itself dates from 2017-2021 |
| `content/archive/*.md` (108) | Wayback pages from 2004-2024 as Markdown (capture timestamp in front matter) | `tools/html_to_md.py` from `wayback/manifest.tsv`; then `tools/redact_pii.py archive` | Captures 2004-2024 | G, **redacted 2026-09-25**: 63 personal phones/e-mails in 16 files, one home address and one pager number removed (front matter `redacted:` says where); the 2004 mobilization tree (a member list) replaced by a note. `html_to_md.py` now redacts on every run |
| `content/interim-ag7qp/*.md` (33) and `docs/*.md` (4) | All ag7qp.com pages, and redacted text of the 3 preambles and the ARES application | Sitemap fetch 1 s apart, then `tools/ag7qp_to_md.py`. The docx extraction method was not saved | Fetched 2026-09-25 (site published 2026-09-23) | G, **redacted** |
| `external/ag7qp/*.html` (33), `sitemap.xml`, `urls.txt`, `fetch-manifest.tsv` | Raw ag7qp HTML, **redacted in place**. The manifest records each original's sha256 and size and the redaction count | Fetch, then `ag7qp_to_md.py --redact-raw` | 2026-09-25 | R (redacted) |
| `external/ag7qp.com.html`, `external/ag7qp.com_spokane-county-ares-acs.html` | Earlier ag7qp captures | Discovery phase; **redacted in place** 2026-09-25 (`tools/redact_pii.py html`) | 2026-09-25 | R (redacted; **git-ignored**) |
| `external/redaction-manifest.tsv` | Original sha256, size and redaction count for the 12 early ag7qp captures redacted on 2026-09-25 | `tools/redact_pii.py html` | 2026-09-25 | G |
| `external/ag7qp-sitemap.xml` | Earlier copy of the ag7qp sitemap | Discovery phase | 2026-09-25 | R |
| `external/spokares-home-as-googlebot.html` | The spokares.org home page fetched as Googlebot, to check for cloaked spam (none found) | Discovery phase | 2026-09-25 | R |
| `sources/government/*` | Primary documents with text extractions: County CEMP (Mar 2021), ESF-2 (about 2014), WA EMD ESF-2 (2021, PDF only), WA EMD Logistics Comms Annex (about 2021), WA State RACES Plan (1995), WAC 118-04 (2025), COAD minutes (2016, 2017, 2023) and agenda (2024), SCEM Communications Specialist class spec (2024, PDF only) | Downloaded during org discovery. The `.txt` extractions were **redacted** on 2026-09-25 (29 phone numbers and e-mails of individuals, mostly agency staff and business contacts plus one personal e-mail; line numbers unchanged; logged in `external/redaction-manifest.tsv`). The PDFs are unmodified public county/state publications | Varies | R/V (primary sources) |
| `sources/government/ag7qp_*.html` (10) | Earlier ag7qp captures. **Redacted in place** 2026-09-25 (manifest above). The content brief's "AG7QP-x" key now points at `external/ag7qp/` instead | Discovery phase | 2026-09-25 | R (redacted; **git-ignored**) |
| `raw/www.spokares.org/`, `raw/spokares.org/` (788 files) | wget mirror of the live Joomla site | wget recursive mirror; `wget.log` records it, but the flags were not saved | 2026-09-25 19:34 | R **!** (from a **compromised host**; do not execute). Holds Joomla-cloaked personal e-mails (the article-11 and article-23 duplicates under `index.php/2-uncategorised/`). **Git-ignored** |
| `raw/by-id/article-N.html` (26) | Every Joomla article, fetched by id | Direct fetch per article id | 2026-09-25 | R **!** (`article-11.html` and `article-23.html` carry cloaked personal e-mails). **Git-ignored** |
| `raw/_home.html` | The home page, with navigation and modules | Direct fetch | 2026-09-25 | R |
| `wget.log` | Crawl log with HTTP errors | wget | 2026-09-25 | R |
| `pages-index.json` | 751 mirrored HTML files collapsed to **445** unique pages by main-content hash | `tools/dedup_pages.py` | 2026-09-25 | G |
| `data/calendar-events.json`, `.csv` | **232** JCal Pro events (2017-08 to 2026-09); NCS rota; categories | `tools/extract_events.py`. The `description` field is empty; real text is in `eventdesclarge` for 105 events | 2026-09-25 | G (partial: the ID high-water mark is 1613) |
| `wayback/cdx-spokares.txt` | **946** unique Wayback URLs for the domain: URL, MIME type, status, first capture | Wayback CDX API (domain match, URL-collapsed) | Queried 2026-09-25 | R |
| `wayback/to-fetch.txt`, `manifest.tsv`, `failures.txt` | 387 selected captures to fetch; 383 fetched (timestamp, URL, file); 4 failures | Fetch script not saved; the recipe is below | 2026-09-25 | R |
| `wayback/snapshots/` (245 files) | Raw Wayback bodies (HTML, plus 6 legacy PDF/DOC files) | As above | Captures 2004-2025 | R **!** Contains two member lists, `spokares.org/Memberlist.pdf` and `www.spokares.org/Downloads/ARES_20NET_20ROSTER_20FEB09.doc` (unopened), plus personal phones and e-mails in `TestingSchedule.html`, `Pactor.html`, `Tree.html`, `index.php/about/email-list` and the 2004 staff pages. **Git-ignored**; the redacted Markdown is `content/archive/` |
| `redirects.csv` | 1,188 old URL paths (from the CDX and the crawl) plus 3 host rules: status (301, 410, keep, none), proposed target slug (D15), matched rule, Joomla hits, first capture, sources, note | `tools/build_redirects.py` | 2026-09-25 | G. Targets are **proposed** until D15 is agreed |
| `data/facts.yaml` | 47 facts (names, people, repeaters, nets, meetings, joining, channels, DNS) with value, status, publish flag, sources, owner, last-verified date and D13 row | Seeded by hand from `organization.md` and `decisions.md`; Ruby's YAML parser loads it | 2026-09-25 | S. Nothing is confirmed by the group yet |
| `orgs/_canonical-orgs.json` | 25 canonical organizations (slug, aliases, URLs, tier, research questions), plus the hierarchy sketch. **The input for deep mode** | Workflow merge phase | 2026-09-25 | L |
| `hosting/notes-*.md` (5) | Raw research notes: Verpex product, Enhance docs, OpenAPI spec, changelog and community, independent reviews | Research agents | 2026-09-25 | Unverified inputs (use `verpex-enhance.md`) |
| `hosting/_enhance-oas3-api.yaml` | Local copy of the public Enhance orchd OpenAPI spec (v12.25.12) | Downloaded | 2026-09-25 | R |
| `../design/inspiration/` (87 PNG) | Screenshots of the surveyed sites (1440x1000 and 1440x3600) | Headless Chrome | 2026-09-25 | R |
| `../design/concepts/NN-slug/` (12) plus `index.html` gallery and `_test/` | Self-contained homepage concept pages, with desktop-fold, desktop-full and mobile screenshots | Built from the concept specs | 2026-09-25 | Mockups (contain "verify" chips) |
| `../design/assets/` (15) | Photos and logos from the old site: the ARES-ACS seal, ACS, ARES and SCDEM logos, slider photos | Copied from the crawl | – | Needs **consent** for identifiable people and **permission** for the county and ARRL marks |

### Tools

| Path | What it does | Usage (run from the repo root, `/Users/sean/Projects/spokane_ares`) |
|---|---|---|
| `tools/dedup_pages.py` | Groups the mirrored Joomla HTML into unique pages by main-content hash | `python3 research/tools/dedup_pages.py research/raw research/pages-index.json` (**INFERENCE:** it was run before `raw/by-id/` and `raw/_home.html` existed; a re-run now would also scan those) |
| `tools/extract_events.py` | Extracts JCal Pro event pages into JSON and CSV | `python3 research/tools/extract_events.py research/raw research/data/calendar-events` |
| `tools/html_to_md.py` | Converts `raw/by-id/` into `content/live/` and the Wayback manifest into `content/archive/`, and writes `content/index.md` | `.venv/bin/python research/tools/html_to_md.py` |
| `tools/redact_pii.py` | Redacts personal phones, e-mails and one home address with the `ag7qp_to_md.py` patterns (plus the "(208)555-..." form), and replaces the 2004 member phone tree with a note. `html_to_md.py` imports it | `.venv/bin/python research/tools/redact_pii.py archive` (rewrites `content/archive/*.md`) or `... html FILE...` (rewrites raw HTML, logs to `external/redaction-manifest.tsv`). Idempotent |
| `tools/build_redirects.py` | Builds `redirects.csv` from the CDX, `pages-index.json` and the article hit counters, using the D15 slugs in its tables | `python3 research/tools/build_redirects.py` |
| `tools/ag7qp_to_md.py` | Converts the Zyro pages to Markdown and redacts personal phones and e-mails (keeping 4 institutional mailboxes) | `.venv/bin/python research/tools/ag7qp_to_md.py [--redact-raw]`. It is safe to re-run on already-redacted HTML |
| `tools/workflows/spokares-related-orgs.js` | Workflow script: related-org discovery (5 lenses), then merge, then map (light mode); or profile plus adversarial verify (deep mode) | See "Related-orgs deep mode" below |

---

## How to regenerate or extend

**Environment.**
- `.venv/` at the repo root holds Python 3.14 with `beautifulsoup4` 4.15 and `markdownify` 1.2.3.
- To recreate it: `python3 -m venv .venv && .venv/bin/pip install beautifulsoup4 markdownify`.

### 1. Old-site crawl (done; do not repeat against the live host)

The live server now blocks this client, and it is compromised. The pipeline was:
1. wget mirror, into `raw/<host>/`.
2. Articles fetched by id, into `raw/by-id/`.
3. `dedup_pages.py`, producing `pages-index.json`.
4. `extract_events.py`, producing `data/calendar-events.*`.
5. `html_to_md.py`, producing `content/live/`.

**For the missing data** (library files, web-link targets, full JCal, access levels), use the **InMotion backup and DB dump** (`decisions.md` D1; `documents-inventory.md` §6), not a re-crawl.

### 2. Wayback capture

This recipe is **reconstructed**; the original fetch script was not saved.

1. **Inventory.** The line format of `cdx-spokares.txt` (`original mimetype statuscode timestamp`, one row per URL) matches:
   ```sh
   curl -s 'https://web.archive.org/cdx/search/cdx?url=spokares.org&matchType=domain&collapse=urlkey&fl=original,mimetype,statuscode,timestamp' > wayback/cdx-spokares.txt
   ```
   For targeted prefixes (e.g. `spokares.org/media/com_osdownloads/*`), use `url=<prefix>*` without `matchType`.
2. **Select** the captures to fetch as `<timestamp> <url>` lines, into `wayback/to-fetch.txt`.
3. **Fetch the raw original bytes** with the `id_` suffix, which adds no Wayback toolbar:
   ```sh
   curl -sL "https://web.archive.org/web/${ts}id_/${url}"
   ```
   - Save to `wayback/snapshots/<host>/<path>`. Escaped characters are kept, e.g. `_20` for a space.
   - Append `ts<TAB>url<TAB>snapshots/...` to `manifest.tsv`, and `FAIL <code> <url>` to `failures.txt`.
   - Pause 1-2 s between requests.
4. **Convert** with `.venv/bin/python research/tools/html_to_md.py`, which also rebuilds `content/index.md`.
5. **PII:** never fetch or open roster or member-list URLs. Check `documents-inventory.md` §4 first.

### 3. ag7qp.com interim capture

1. Fetch `https://ag7qp.com/sitemap.xml` into `external/ag7qp/sitemap.xml` and `urls.txt`.
2. Fetch each page 1 s apart into `external/ag7qp/<slug>.html`.
3. Run `.venv/bin/python research/tools/ag7qp_to_md.py --redact-raw`. This writes `content/interim-ag7qp/*.md`, redacts the raw HTML in place, and updates `fetch-manifest.tsv`.
4. **Do not download** `net-roster-*.pdf` or `vhf-roster-*.pdf`.
5. **Do not store `.docx` originals**; they contain personal e-mails. Keep only redacted text in `docs/`.
6. ag7qp.com changes weekly. Re-capture it before migration and diff against this capture.

### 4. Related-orgs deep mode

Source: `orgs/_backlog.md`. `relationships.md` is a light pass. To deep-profile and fact-check specific organizations, run `tools/workflows/spokares-related-orgs.js` with args like these:

```sh
jq '{today:"<YYYY-MM-DD>", hierarchy_sketch:.hierarchy_sketch,
     deep:[.orgs[] | select(.slug=="spokane-county-emergency-management" or .slug=="w7gbu-support-group")]}' \
   research/orgs/_canonical-orgs.json > /tmp/deep-args.json   # use your scratchpad dir
```

- Pass that JSON as the workflow's `args`. Deep mode skips discovery.
- For each organization it runs one **profile** agent, which writes `orgs/<slug>.md`, then one **adversarial verify** agent, which edits that file in place, marks unsupported claims "⚠️ unverified" and appends a `## Verification (<date>)` section.
- The slugs are the headings' backtick ids in `orgs/_backlog.md`.

**Suggested first deep profiles**, based on what blocks `decisions.md`:
- `spokane-county-emergency-management` (naming, approvals, ACS contact)
- `w7gbu-support-group` (renewal, repeater ownership)
- `ievhfra` (MOU, alternate repeater)
- `arrl-eastern-washington-section` (roles, SOP 1b)
- `arrl-ares-program` (levels, emblem rules)

A **light-mode** re-run (no `deep` arg) repeats discovery for all organizations and **overwrites `orgs/relationships.md`**. Copy the current file first.

### 5. Design workflow

The orchestration script is **not saved** in the repo. The steps, reconstructed from the artifacts:
1. **Inspiration survey:** 44 sites with curl, headless-Chrome screenshots at 1440 px (`../design/inspiration.md`, `inspiration/`).
2. **Content brief** from the crawl and ag7qp (`content-brief.md`).
3. **Specs** for 12 deliberately different concepts (`concept-specs.md`).
4. **Build** each concept as one self-contained `concepts/NN-slug/index.html`, with screenshots (desktop fold, desktop full, mobile 390 px) and the `concepts/index.html` gallery.
5. **Judge:** 4 lenses (prospective volunteer, served-agency partner, UX and maintainability, art director) on 6 criteria scored 1-10. The composite is the mean of the criterion means (`REVIEW.md`).

**Next round (INFERENCE):**
- Build one or two hybrids per `REVIEW.md` → "Recommendation".
- Apply the "Fix before any direction ships" list.
- Follow `content-brief.md` §11, and use the facts in `organization.md` and `decisions.md` D13.
- Re-judge with the same lenses.
- Refresh the content brief first (see errata).

### 6. Hosting research

- **Re-verify** prices, Enhance version and AUP text before purchase; they change.
- Ask Verpex the questions in `decisions.md`. Several can be answered in our own panel once the account exists.
- For Enhance panel or API automation (sites, DNS, mail, SFTP), this Claude Code setup has an **`ss:enhance-api` skill**.

---

## Errata: later findings that correct earlier files

| Earlier claim | Where | Correction | Evidence |
|---|---|---|---|
| Groups.io adopted "in 2019" | `site-functionality-analysis.md` §0 item 7, §3.3 | In use **by 2018-03** | `history.md` §7 (`content/archive/20190724-index-php-44-*.md`) |
| "Downloads were gated in 2019" | `site-functionality-analysis.md` §3.2; `history.md` §2.4 (2019 row) and §10 item 5 | **Gate markup present, never enabled.** All 66 gate flags are 0. All three passages now say so (2026-09-25) | `documents-inventory.md` §2 item 6 |
| "MX is a null record" | Session brief / workflow "established facts"; `history.md` §2.6 said "unresolved" | **`0 spokares.org.`**, so mail is live on InMotion. `site-functionality-analysis.md` was right. Also: DKIM key present at `default._domainkey`; **no DMARC**. `history.md` §2.6 and `decisions.md` D3 corrected 2026-09-25. **Whoever holds the workflow brief should correct it too** | `dig` against 1.1.1.1, 8.8.8.8, 9.9.9.9 and ns1.inmotionhosting.com, 2026-09-25 |
| "`site-functionality-analysis.md` does not exist" | `../design/content-brief.md` header | It exists; it was written minutes after the brief. Header corrected 2026-09-25 | File timestamps |
| Mockups should assume a gated members area and an events plugin | `../design/content-brief.md` header, taken from the hosting doc's generic §5 | The later recommendation is **no site accounts** and the **groups.io calendar**. Header corrected 2026-09-25 | `site-functionality-analysis.md` §7.2, `decisions.md` D8/D9 |
| "Features to carry over" include the member login, weather module and image slider | `hosting/verpex-enhance.md` §5 | Drop the login, slider and weather widget; keep NWS links | `site-functionality-analysis.md` §6 (D1, D4) |
| AG7QP pages cited as `sources/government/ag7qp_*.html` | `../design/content-brief.md` source key | Those files were **unredacted**. The key now points at `external/ag7qp/*.html` / `content/interim-ag7qp/*.md`, and the old files were redacted and git-ignored (2026-09-25) | `content/interim-ag7qp/README.md` item 12; `decisions.md` D14 |
| County ID card after IS-100 + IS-700, the background check and a **six-month activity period** | `../design/content-brief.md` §5 Step 3 item 6 (from the 2018 Welcome Letter, A-41) | The ACS Task Book v2024-08-09: the ID card is issued **on advancement to RADO**, after all New Member requirements; the New Member period is "normally not to exceed one year". §5 rewritten from `organization.md` §10 | `content/interim-ag7qp/acs-task-book.md` lines 21 and 71 |
| Alert Spokane is "the Code Red app" | `../design/content-brief.md` §5 (Training list) and §9; also the Task Book and orientation page on ag7qp | ALERT Spokane runs on **Regroup** since April 2026 | https://www.spokanecounty.gov/3007/Alert-Spokane (checked 2026-09-25); `orgs/relationships.md` Contradiction 12 |
| County application at `spokanecounty.org/FormCenter/...-276` | `../design/content-brief.md` §5 Step 3 | That URL redirects; link **`https://www.spokanecounty.gov/FormCenter/Emergency-Management-31/Volunteer-Emergency-Worker-Application-276`** | `site-functionality-analysis.md` §1a row 28 |
| "ARRL membership is **not** required" | `../design/content-brief.md` §5 Step 1 | True for joining; the **July 2025 ARRL ARES Plan** requires it for Intermediate level and above and for EC/AEC appointments. Say both | `orgs/relationships.md` §4 and Contradiction 8 |
| "Subscribe to the Groups.io list" | `../design/content-brief.md` §5 Step 2; `organization.md` §10 | **Apply; a moderator approves.** The archive, Files and Wiki are members-only | https://spokaneares-acs.groups.io/g/main (anonymous, 2026-09-25) |
| Net check-in by suffix groups A-F, G-M, N-Z | `organization.md` §9; `../design/content-brief.md` §4 | That is the 2018 preamble. The 2026-03-04 weekly script calls members from the roster; the simplex script uses five groups; the GMRS script uses volunteer-number ranges | `content/interim-ag7qp/docs/net-preamble-*.md` |
| Navigation with a Login, "Members (some items gated)", a "Status: CONDITION GREEN" strip and Terms | `../design/content-brief.md` §10 | Struck. The page list is `decisions.md` **D15** (no accounts, no public status, a disclaimer instead of Terms) | `decisions.md` D8, D15; `../design/REVIEW.md` |
| "Hospital Net on Channel 6" as a publishable fact | `organization.md` §3, §9; `history.md` §5; `orgs/*`; `content/interim-ag7qp/README.md` | The channel is **withheld**: SCEM radio frequencies and procedures are FOUO under the SCEM NDA. See the "Never publish" rule in `decisions.md` D13 | `content/interim-ag7qp/docs/ares-membership-application-2024-11-07.md` |
| W7GBU renewal window, grace period and fee marked INFERENCE | `decisions.md` D4 | Checked against 47 CFR 1.949(a), 97.19, 97.21, 1.1102 and 1.1116: window opens **2026-11-17**; no operation after expiry until renewed; **$35** fee (applying it to this club renewal is still an inference) | Cornell LII copies of the eCFR, read 2026-09-25 |
| Paths like `design/content-brief.md` in a file whose paths are "relative to `research/`" | `organization.md` header | The header now says `design/` means the repo-root folder (`../design/`), as `decisions.md` does | – |
| "see `design/content-brief.md` §6 note" | `documents-inventory.md` §5.3 (OSD-17) | The note is at the end of §5 | – |
| Links to the two roster binaries | `content/index.md` (former lines 120, 122) | Links removed; the rows say "withheld". `html_to_md.py` no longer links roster files | `decisions.md` D14 |
| Registrar "GoDaddy" (unsourced) | `orgs/relationships.md` unknowns, `orgs/_backlog.md` | **Confirmed** by RDAP: GoDaddy.com, LLC; transferred 2021-11-12; last changed 2026-08-12 | RDAP, 2026-09-25 |
| InMotion hosting "from 2017" | `orgs/_backlog.md` | Unverified. InMotion is evidenced from 2023 (cPanel hostnames) | `history.md` §1 |

---

## Known limitations of the research

1. **Nobody in the group has been asked anything yet.**
   - Every "current" role, time and frequency comes from web pages that may be stale. The staff page was last edited 2026-03-24, and ag7qp.com changes weekly.
   - Questions for each person are in `decisions.md`.
2. **The org map is a light pass.**
   - `orgs/relationships.md` comes from discovery evidence only, with confidence marked per claim.
   - No organization has been deep-profiled or adversarially fact-checked.
   - Only the hosting report was independently verified.
3. **The people tables are unverified.**
   - Some spellings conflict ("Croskrey"/"Croskey"; KA7LJQ/KJ7LJQ).
   - Some roles conflict: AG7QP is both SEC and Spokane AEC; the public contact could be W7TSC or AG7QP.
   - The SCEM Communications Specialist has not been identified.
4. **Files not recovered.**
   - None of the 46 OSDownloads files; their formats and sizes are unknown, and 32 item IDs are unexplained.
   - The 27 web-link targets.
   - The full JCal database (event IDs reach 1613; only 232 events were extracted).
   - Access levels: login-only content can't be ruled out without the DB.
   - Mailbox and forwarder lists.
   - All of these exist only on InMotion (`decisions.md` D1).
5. **The live site is partly broken, hacked, and blocking us.**
   - The data comes from the 2026-09-25 crawl, before the block.
   - The hack's date, vector and scope are unknown. No forensic analysis was done, and the crawled HTML comes from a compromised host.
   - Partial scope checks (2026-09-25): Safe Browsing clean; the two IPs are on none of five working blocklists. The `site:` search, Spamhaus/URIBL/SURBL and Search Console are **not done** (`decisions.md` D1).
6. **groups.io is members-only.** An anonymous check (2026-09-25) confirmed moderated subscriptions and members-only archive, Files and Wiki. The calendar feed, plan tier and owner are still unknown (`decisions.md` D9, D13 #20).
7. **ag7qp-hosted documents were only partly captured.**
   - Only text extractions of 4 documents exist.
   - The roster PDFs were deliberately not downloaded.
   - The web-search budget ran out before the origins of the "I Am Safe" procedure and the third-party presentations were traced.
8. **Wayback coverage gaps.**
   - Nothing new in 2016, and the Wix era is barely captured.
   - There are no library files at all.
   - Field Day records are missing for many years (`history.md` §9).
9. **The design work is at the concept stage.** The mockups contain "verify" chips, and the photo library is weak and low-resolution. The county seal and ARRL emblem have no permission yet.
10. **The hosting research uses public sources only.**
    - There is no panel access yet.
    - The web server kind, per-site limits, mail limits and backup retention are unconfirmed.
11. **PII hygiene (updated 2026-09-25).**
    - Done: `content/archive/` redacted; the 12 early ag7qp captures redacted in place; roster links removed from `content/index.md`; `.gitignore` excludes `raw/`, `wayback/snapshots/`, the early ag7qp captures, roster/member-list names and `.docx` files.
    - Still on disk (git-ignored, marked **!** above): `raw/` (cloaked personal e-mails) and `wayback/snapshots/` (the two member lists and the 2004 pages with personal contacts). The two rosters are deleted only after the EC's Wayback decision (`decisions.md` D14).
    - `research/` and `design/` are still untracked. Before the first commit, run `git status --ignored` and check nothing under `raw/` or `wayback/snapshots/` is staged.

---

## Known gaps (2026-09-25)

What is still open after the 2026-09-25 critic pass. The critic listed 24 items; the fixes made in this pass are in the errata table above and in `decisions.md` D1, D3, D4, D8, D9, D13 (rows 2, 4, 5, 7 and 16-20, plus the "Never publish" rule), D14 and D15. Everything below needs a person, a decision or a manual check.

| # | Gap | Where it stands | Suggested fix | Who |
|---|---|---|---|---|
| 1 | **Is the 1st-Tuesday 1730 staff meeting public?** ag7qp lists it under "Open to the public"; the content brief and `organization.md` say internal | Asked in `decisions.md` D13 #16. Marked internal in the cheat-sheet and `data/facts.yaml` (`publish: "no"`) until answered | Ask the EC; until then keep it off public pages | EC |
| 2 | **Third Thursday start time.** 1800 rests on ag7qp.com alone; 1900 is in every older source up to a Spokesman-Review listing for 2025-09-18 | The 2025 evidence is now in `organization.md` §9 and D13 #4 | Confirm with the EC/AG7QP before copy freeze; show "Third Thursdays" with no time until then | EC / AG7QP |
| 3 | **New Member Orientation as a standing 4th-Tuesday session.** Sources say "usually"; no Oct 27, 2026 session is listed | D13 #17 | Confirm. Until then the site says "held regularly; contact us for the next date" | EC / AG7QP |
| 4 | **Simplex frequency, and whether the 5th-Tuesday simplex net still runs** (JCal says 2026-09-29 "NZ2S - Simplex"; the ag7qp rota says plain "NZ2S"; next 5th Tuesday 2026-12-29) | D13 #5 | Ask AG7QP. The Nets page ships without a frequency until he answers | AG7QP |
| 5 | **Public contact and where applications go.** The application sends completed forms (with address, phones and emergency contact) to a personal e-mail; the scripts send changes to a personal e-mail or the dead contact form | D13 #2; "Questions for AG7QP" #8 now asks for a role address | The EC names recipients for `join@` and `ec@`; set up the forwarders (D3/D11) **before** the Join page goes live; AG7QP updates the application and scripts | EC, AG7QP |
| 6 | **The EC's official title:** "RACES Officer" vs "ACS Officer" (2026 scripts) vs "EC/RO" | D13 #18 (with #12, the written appointment) | Settle with SCEM | EC / SCEM |
| 7 | **No named SCEM contact.** The Communications Specialist who owns ACS is unknown, and D6 (name and seal), the RCW 38.52 volunteer-status text, the FOUO rule and the `/about/how-it-works/` review all need SCEM sign-off | Unchanged | Get a named SCEM contact through the EC first; record the name and the date of each answer in `decisions.md` | EC |
| 8 | **The "Never publish" (SCEM FOUO) rule is ours, not SCEM's** | Proposed in D13 and `../design/content-brief.md` §11; D13 #7 defaults to "do not publish" | The EC asks SCEM to confirm or amend it, then records the answer and date in D13 | EC / SCEM |
| 9 | **First-visit logistics at 1121 W Gardner Ave** (entrance, check-in or escort after hours, parking, accessibility) | D13 #19; `/nets/#where-we-meet` in D15 waits on it | Ask the EC/SCEM; add the answer to `data/facts.yaml` (`meeting.location`) and the Meetings copy | EC / SCEM |
| 10 | **Who holds and pays for the new hosting.** No one is named for the Verpex E30 reseller account ($219/yr list, `hosting/verpex-enhance.md` §2.1), the WordPress admin or the off-host backups; the group has no bank account | No D-item yet | Add a D-item: account holder (on a role e-mail), payer, second admin, term end, and a renewals-register entry beside the domain (D2) and W7GBU (D4). Ask Verpex Q10 (non-profit terms) | EC, project owner |
| 11 | **groups.io calendar feed.** The calendar page loads for anonymous users but shows no server-side events; `/g/main/ics/feed.ics` is 404. The owner's identity is unknown | Recorded in `organization.md` §12, D8, D9, D13 #20 | Write to the owner role address (`main+owner@SpokaneARES-ACS.groups.io`) asking whether the calendar is public and for its public ICS URL; settle D9 after the answer (fallback: a role-owned Google Calendar) | Project owner / EC |
| 12 | **Hack scope, remaining checks.** Safe Browsing is clean and five blocklists show nothing, but the `site:spokares.org` search was blocked, Spamhaus/URIBL/SURBL refused public-resolver queries, and Search Console needs a verified owner | Steps added to D1 and D3 | By hand: run `site:spokares.org` in a browser and compare with `redirects.csv` (unknown URLs → 410); check check.spamhaus.org for both IPs and the domain; verify a Search Console Domain property by DNS TXT and read Security & manual actions | Technical admin |
| 13 | **Workflow brief still says "MX is a null record".** Corrected in `history.md` §2.6, D3 and the errata, but the orchestrator's ESTABLISHED FACTS text lives outside this folder | Can't be edited from here | Replace it with: MX `0 spokares.org.`; SPF `v=spf1 +mx +a +ip4:70.39.251.92 ~all`; DKIM present; no DMARC. Also ask the EC about the SPF IP, which reverse-resolves to a host under `asajay.com` (D3) | Orchestrator / project owner |
| 14 | **The two roster binaries and the raw layers are still on disk.** `wayback/snapshots/` (Memberlist.pdf, the Feb 2009 roster, 2004 pages with personal contacts) and `raw/` (cloaked personal e-mails) are git-ignored, not deleted | D14 | After the EC's Wayback-exclusion decision, delete the two rosters. Never share this folder outside git (zip, cloud drive) without excluding `raw/` and `wayback/snapshots/`. Run `git status --ignored` before the first commit. Note that `content/index.md`'s other archive-binary links point into the ignored folder | EC, project owner |
| 15 | **Redirect targets are proposed, and the URL list may be incomplete.** `redirects.csv` uses the D15 slugs; the CDX and the crawl don't cover everything the InMotion DB knows (OSDownloads IDs, the full JCal, menu aliases) or any injected spam URLs | D15; `tools/build_redirects.py` | Agree D15, edit the script's tables and re-run; after the D1 backup, add DB-only URLs; add any `site:` finds as 410 | Editors, technical admin |
| 16 | **The page inventory (D15) is a recommendation.** The content brief §10 and the design hybrid now follow it, but nobody has approved it | D15 | The EC and the D5 editors approve or change it; then update `../design/content-brief.md` §10 and re-run the redirect script | EC, editors |
| 17 | **Net-script defects are fixed only in our notes.** The masters (on ag7qp.com) still hard-code NCS K7DSR, omit the simplex frequency, point to the roster and dead contact form, and route reports to personal e-mails | Listed in `organization.md` §9 and "Questions for AG7QP" #8 | AG7QP fixes the masters (or hands them over, D7); the site publishes only a public version (no roster, role address) | AG7QP |
| 18 | **W7GBU renewal: two points still INFERENCE.** That the trustee files the club vanity renewal himself in ULS, and that the $35 fee applies to it; the rule text was read from Cornell LII because ecfr.gov blocked automated access | D4 | The trustee checks both in ULS when he files (window opens 2026-11-17); if the trustee is changing, do that first through a Club Station Call Sign Administrator | Trustee K7DSR |
| 19 | **`data/facts.yaml` is not wired to anything yet** and duplicates prose in `organization.md` and the content brief | Seeded 2026-09-25 (47 facts) | Make the YAML the source: update it first when an answer arrives, then regenerate or edit the prose; have the build render nets, meetings and leadership from it (D5, D9) | Editors |
