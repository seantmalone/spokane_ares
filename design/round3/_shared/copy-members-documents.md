---
page: members/documents.html
budget: 900 visible words in <main>; 7 category H2s + "Not posted here"; descriptions ≤ 8 words or none
word_count: 441 (measured, README.md method; visible text in <main>: H1, search label and button, chips, H2s, table headers, every cell, the not-here lines; placeholder, aria labels and JS-only states not counted)
h2_count: 8 (7 categories + Not posted here)
rows: 37 (one data.js record per row in round-3 data.js; the three net scripts share one row and one link, the four FEMA IS courses and the SID share one row, and the ICS-213 how-to sits inside the ICS 213 row)
ctas: 1 primary (Search, then open a row); every row title is its own link
spec: round3/_shared/placement-map.md §3.5, rules 0.1–0.9, facts #12, #13, #20, #32, #34, #37, #38, #40, #58–#63
data: round3/_shared/assets/data.js `SPOKARES.documents` (ids, titles, hrefs, versions, descriptions exactly as shown)
---

# Documents & forms: round-3 copy (edited)

How to read this file: only text between the COPY markers is visible, except lines starting with `> Build:`, which are notes. `[verify]` becomes `data-verify="true"` with no visible chip. `{#id}` is the element id.

- `<title>`: Documents & forms | Spokane County ARES-ACS
- Meta description: Net scripts, applications, task books, forms and guides for Spokane County ARES-ACS members.
- No lede, breadcrumb, intro essay, "Most used" H2, status key, History & archive, or "Last reviewed" note. The members sub-nav marks this page with `aria-current="page"`.

> Build: rows render from round-3 `SPOKARES.documents`, which now holds only the rows below, in this order. Each record's `description` is exactly the small text shown (≤ 8 words) or absent.
>
> Build, row format: **Title** (the link) · **Format** (`format` · `version`; empty for web pages) · **Source** (`source.label`, plus ↗ when external) · **Description** (small text under the title). Table on desktop; on phones each row collapses to title, then "Format · Source", then description.
>
> Build, links (rule 0.8): every record's `href` is the link to use today (no `#` anywhere). `hostedHere: true` marks our own documents that move to this site at launch; until then `href` is the interim ag7qp.com copy. A record with `href: null` prints the title as plain text and "Soon" in the Source cell. External links open in the same tab; the ↗ carries `aria-label` text "(opens [source])".
>
> Build, deep links: every row carries its data.js id; the FEMA row also puts each course link's `id` on the link. `documents.html#forms` (and every category id) pre-selects that chip; `documents.html?q=` pre-fills the search. `scroll-margin-top` = header + sub-nav height. Incoming links from other round-3 pages: `#net-scripts`, `#ares-application`, `#acs-task-book`, `#training`, `#ics-213`, `#ics-213rr`, `#ics-214`, `#ics-309`, `#wsdot-580-020`, `#wa-field-situation-report`, `#skywarn-training`, `#go-kits`, `#public-service-tips`.

---

<!-- COPY -->

# Documents & forms

> Build: `#search` is a `<form method="get" role="search">` with a field named `q` (no heading); placeholder attribute "preamble, 213, task book" (not counted). Without JS the chips are anchor links to the category H2s and "Most used" is hidden. With JS the chips filter and "Most used" shows the rows with `mostUsed: true`. JS-only states (not counted): live count "{n} documents" (`aria-live="polite"`); empty state "Nothing matches “{q}”." + [Clear search].

Search the library [CTA: Search -> documents.html?q=…]

All · Most used · Net operations · Join & task books · Forms · Training · Go-kits & readiness · Winlink & digital · Reference

## Net operations {#net-ops}

| Document | Format | Source |
|---|---|---|
| [Net scripts: weekly, simplex and GMRS](https://ag7qp.com/spokane-county-ares-acs) [verify] | DOCX · 2026-03-04; GMRS 2025-08-19 | ag7qp.com ↗ |
| Principles of a Net Control Station | | Soon |
| Repeater etiquette on W7GBU [verify] — *How to identify and break in.* | | Soon |
| Net roster | | groups.io, members ↗ |

> Build: row id `net-scripts`, one link (all three scripts sit on the same interim ag7qp.com page). At launch, when the scripts are hosted here, the title splits into three file links from the record's `files[]`. The retired ids `net-preamble-weekly`, `-simplex`, `-gmrs` are not linked from anywhere. **Launch blocker (D14 #1):** the interim ag7qp.com page links the 2026-08-04 net roster PDF beside the scripts, and the DOCX files carry a personal e-mail (D13 #2). Keep `data-verify` on the row; before launch, either AG7QP removes the roster link and supplies clean masters (Questions for AG7QP #4, #7) or the row switches to "Soon".

## Join & task books {#join}

| Document | Format | Source |
|---|---|---|
| Spokane County ARES Membership Application [verify] | DOCX · 2024-11-07 | ag7qp.com ↗ |
| Volunteer Emergency Worker Application (for ACS) | Online form | Spokane County ↗ |
| ACS Position Task Book [verify] — *Print it; keep sign-offs in a binder.* | DOCX · 2024-08-09 | ag7qp.com ↗ |
| ARRL ARES Position Task Book | PDF · July 2024 | ARRL ↗ |
| ARRL ARES Plan [verify] | | ARRL ↗ |
| New Member Orientation guide | | ag7qp.com ↗ |

> Build: no descriptions on the applications: what they involve, and where to return the ARES application, lives in about.html#ares-path and #acs-path.

## Forms {#forms}

| Document | Format | Source |
|---|---|---|
| ICS 213 General Message — *[How to write one ↗](https://ag7qp.com/ics-213)* | PDF · v3 | FEMA ↗ |
| ICS 213RR Resource Request Message | PDF · v3 | FEMA ↗ |
| ICS 214 Activity Log | PDF · v3.1 | FEMA ↗ |
| ICS 309 Communications Log [verify] — *[Generate it in Winlink Express (video) ↗](https://www.youtube.com/watch?v=VmVJpoPmQvc)* | | Soon |
| ARRL Radiogram (fillable) — *National Traffic System message form.* | PDF | ARRL ↗ |
| Washington State ICS-213RR [verify] — *Used in state exercises.* | PDF | WA Military Dept. ↗ |
| Washington State Field Situation Report — *Winlink Express: Templates › Standard Forms › WA State Forms.* | Winlink form | Winlink ↗ |
| WSDOT Form 580-020, Road and Bridge Assessment | PDF | WSDOT ↗ |
| All FEMA ICS forms | | FEMA ↗ |

> Build: `ics-309` has no authoritative source yet, so its title is plain text and the how-to video is the row's only link.

## Training {#training}

| Document | Format | Source |
|---|---|---|
| FEMA courses: IS-100.c · IS-200.c · IS-700.b · IS-800.d — *[Get a FEMA Student ID (SID) first ↗](https://cdp.dhs.gov/femasid)* | Online course, free | FEMA ↗ |
| ARRL Basic EmComm course — *Three modules, about 10 to 20 hours.* | Online course | ARRL ↗ |
| ARRL Intermediate EmComm course — *35-question final; 74% to pass.* | Online course | ARRL ↗ |
| HIPAA training video (12 minutes) [verify] — *If you missed it at orientation.* | Video | YouTube ↗ |
| SKYWARN spotter training — *In person or online. Encouraged every two years.* | | NWS Spokane ↗ |
| RATPAC video presentations | | RATPAC ↗ |
| A guide to Anderson Powerpoles — *Make your own DC power connections.* | PDF | ARRL ↗ |

> Build: record `fema-is-courses`; "FEMA courses:" is plain text and each course code is its own link from `links[]` (ids `is-100`, `is-200`, `is-700`, `is-800`, each with an `aria-label` carrying the full course title). The SID link is `sid` (id `fema-sid`). This row is the one home of the SID tip. Who must take which course lives in about.html#acs-task-book.

## Go-kits & readiness {#readiness}

| Document | Format | Source |
|---|---|---|
| Go-kit guide | | ag7qp.com ↗ |
| Register with ALERT Spokane — *Now on Regroup; task book says “Code Red.”* | | Spokane County ↗ |
| Radio programming cheat sheets | | ag7qp.com ↗ |

## Winlink & digital {#digital}

| Document | Format | Source |
|---|---|---|
| Winlink: getting started — *Account setup, then Telnet, then radio.* | | ag7qp.com ↗ |
| Winlink Express (software) — *Service code: PUBLIC EMCOMM.* | Software | Winlink ↗ |
| Winlink map-enabled forms (guide) [verify] | PDF | Winlink ↗ |
| “I Am Safe” messaging: procedures [verify] — *Welfare messages from a disaster area.* | DOCX · July 2023 | ag7qp.com ↗ |

## Reference {#reference}

| Document | Format | Source |
|---|---|---|
| Eastern Washington Section Standing Order 1b — *What to do when you have no instructions.* | PDF · 2026-01-16 | ag7qp.com ↗ |
| AUXFOG and NIFOG field operations guides | PDF or app | CISA ↗ |
| Spokane County ACS Field Operating Guide (ACSFOG) | | groups.io, members ↗ |
| Preparing for public-service communications — *Nine tips for your first event shift.* | | ag7qp.com ↗ |

> Build: what SOP 1b says lives in how-it-works.html#standing-order-1b; this row only names it.

## Not posted here {#not-here}

County, hospital, SHARES and 800 MHz details are never posted here. Older workshop presentations are being recovered.

<!-- /COPY -->

---

## Round-3 edit pass (what changed and why)

- **Lede cut.** The Source column already says where each row opens.
- **#not-here** keeps only what the library can't show: the FOUO line and the recovery line. "The roster and ACSFOG are on groups.io" repeated two rows; the webmaster mailto is cut (map #64 "Remove from" wins; the footer's Contact link reaches about.html#contact).
- **Net scripts** are one row with one link (all three sit on the same interim page), and "(5th Tuesdays)" is gone (how-it-works.html#other-nets says it).
- **Alert levels row cut.** It only pointed back to how-it-works.html#alert-levels ("Readiness levels" is now the site-wide term).
- **Go-kit guide** has no hour ranges in its title; About#equipment links to this row, and the kit hours live there.
- **County application** format no longer prints "FormCenter 276".
