# Round 2: shared information architecture

- **Prepared:** 2026-09-26, for the three round-2 design options.
- **Scope:** the site map, navigation, anchors, data contract and content rules that all three options share. The options should differ in design, not in structure or facts.
- **Owner's direction (overrides `research/decisions.md` D15 where they differ):** four main sections: Home, How it works, About ARES & ACS, For Members. D15's "Nets & meetings", "Join", "Library" and "Contact" are folded in as described in §7.
- **Sources:** `research/README.md`, `research/organization.md`, `research/decisions.md` (D8-D15, "Never publish"), `research/data/facts.yaml`, `research/documents-inventory.md`, `research/content/interim-ag7qp/*`, `research/content/live/*`, `design/content-brief.md`, `design/REVIEW.md`.

---

## 1. Page inventory

| # | File (relative to the option's root) | Section | Status | Copy source |
|---|---|---|---|---|
| 1 | `index.html` | Home | **Mocked** | `content-home.md` |
| 2 | `how-it-works.html` | How it works | **Mocked** | `content-how-it-works.md` |
| 3 | `about.html` | About ARES & ACS | **Mocked** | `content-about.md` |
| 4 | `members/index.html` | For Members: Overview ("This week") | **Mocked** | `content-members.md` |
| 5 | `members/documents.html` | For Members: Documents & downloads (and Forms, via `#forms`) | **Mocked** | `content-members-documents.md` |
| 6 | `members/exercises.html` | For Members: Exercises & events | **Mocked** | `content-members-exercises.md` |
| - | `members/nets.html`, `members/winlink.html`, `members/training.html`, `members/readiness.html`, `members/radio.html`, `members/contacts.html` | For Members sub-sections | **Not mocked: link to `#`** | (outlines in `content-members.md` §9) |
| - | Privacy and disclaimer | Footer | **Not mocked: link to `#`** | - |

All internal links are **relative**. From `members/*.html`, the top-level pages are `../index.html`, `../how-it-works.html`, `../about.html`. Shared data is `assets/data.js` (see §6).

---

## 2. Global header

```
[ARES-ACS seal] Spokane County ARES-ACS     Home · How it works · About ARES & ACS · For Members     [Join the team]
```

| Element | Spec |
|---|---|
| Brand | The current seal (`assets/img/seal-ares-acs-400.png`, from the 550 px original) plus the wordmark "Spokane County ARES-ACS" set in the option's typeface. Never use `textLogoACS_dash.png`. Links to `index.html`. |
| Primary nav | 4 items, in this order: **Home** (`index.html`), **How it works** (`how-it-works.html`), **About ARES & ACS** (`about.html`), **For Members** (`members/index.html`). Mark the current section with `aria-current="page"` (or `"true"` for a section parent). |
| Persistent CTA | **"Join the team"** → `index.html#join`. Visually distinct from the nav items; visible at every width (it may move into the bar at the top of the mobile menu, but must not be hidden behind the toggle). |
| Mobile (390 px) | Brand + Join CTA + a menu button (`aria-expanded`, `aria-controls`). The menu opens as a plain list; no mega-menu. |
| Optional utility line | "Tuesday net · 8:00 PM · W7GBU 147.300 MHz (+600 kHz, 100 Hz) · Net control: {from data.js}". Allowed on any page. **No status badge of any kind** (no "Condition Green"). |
| Skip link | "Skip to main content" → `#main`, first focusable element. |

**Label notes.** "About ARES & ACS" is the owner's name for section 3 and should be used as written, including the ampersand. "For Members" in the nav; "For members" in running text is fine.

---

## 3. Footer (audience-organised)

```
[seal] Spokane County ARES-ACS
Volunteer amateur radio emergency communications for Spokane County.
Formerly Spokane County ARES/RACES.
Spokane County Emergency Management · 1121 W Gardner Ave, Spokane, WA 99260

New here                     Members                         Agencies & partners
How it works                 For Members (this week)         Who we serve
About ARES & ACS             Documents & downloads           Agreements
Get licensed                 Exercises & events              How activation works
Visit a meeting              Forms                           Contact the emergency coordinator
Join the team                groups.io (members) ↗

An ARRL Amateur Radio Emergency Service group, serving Spokane County Emergency Management.
Our members are volunteers. We support, and do not replace, the county's emergency services. In an emergency, call 911.
Privacy · © Spokane County ARES-ACS
Mockup — content to be verified before launch
```

| Footer link | Target |
|---|---|
| How it works | `how-it-works.html` |
| About ARES & ACS | `about.html` |
| Get licensed | `about.html#licensing` |
| Visit a meeting | `index.html#visit` |
| Join the team | `index.html#join` |
| For Members (this week) | `members/index.html` |
| Documents & downloads | `members/documents.html` |
| Exercises & events | `members/exercises.html` |
| Forms | `members/documents.html#forms` |
| groups.io (members) | `https://spokaneares-acs.groups.io/g/main` (external; `rel="noopener"`, marked as external) |
| Who we serve | `about.html#who-we-serve` |
| Agreements | `about.html#agreements` |
| How activation works | `how-it-works.html#activation` |
| Contact the emergency coordinator | `about.html#contact` |
| Privacy | `#` |

- Affiliation marks (ARES emblem, ACS seal, SCEM seal) may sit in the footer as **affiliation** marks, with a caption such as "Affiliation marks shown with permission" `[verify]` (decisions.md D6). The SCEM seal must never read as the group's own logo.
- The footer line **"Mockup — content to be verified before launch"** appears once, small, on every page. It is the only visible sign of unverified content (see §8).
- The three-line disclaimer needs SCEM review before launch (`[verify]`).

---

## 4. For Members: the secondary menu

The members area gets its **own menu**, shown on every `members/*.html` page (desktop: a left rail or a sticky sub-bar; mobile: a "Members menu" disclosure at the top of the page, closed by default, plus the current item's name visible). It lists **every** members sub-section, including the ones not mocked.

| Group | Item | Target | Mocked? | What it holds |
|---|---|---|---|---|
| - | **Overview (this week)** | `members/index.html` | Yes | Next net and net control, this week's exercises and meetings, open net-control slots, the next Winlink assignment, quick links, standing requirements, search |
| Operate | **Nets & net control** | `#` (future `members/nets.html`) | No | Net schedule (weekly, Winlink nights, 5th-Tuesday simplex, GMRS), the full net-control rota and how to take a slot, current preambles, the NCS guide, how to send a net report |
| Operate | **Winlink & digital** | `#` (future `members/winlink.html`) | No | The Winlink assignment schedule and how to respond, the 2nd-Saturday Winlink workshop, setup links, Winlink forms (WA Field Situation Report, DYFI) |
| Operate | **Repeaters & programming** | `#` (future `members/radio.html`) | No | Amateur settings only: W7GBU 147.300 and 443.400, the 146.880 alternate, the GMRS net repeater (once cleared); programming cheat sheets; repeater etiquette. **County, hospital, SHARES and 800 MHz channels are never here** (they live in the ACSFOG on groups.io) |
| Prepare | **Exercises & events** | `members/exercises.html` | Yes | Upcoming exercises, training, meetings and public-service events; what to do for each; after-action steps; past exercises |
| Prepare | **Training & task books** | `#` (future `members/training.html`) | No | ACS Task Book levels and sign-off, ARRL ARES Task Book, FEMA IS and ARRL EmComm links, orientation, SKYWARN, HIPAA |
| Prepare | **Go-kits & readiness** | `#` (future `members/readiness.html`) | No | Go-kit guide, equipment lists, alert levels explained, ALERT Spokane registration |
| Reference | **Documents & downloads** | `members/documents.html` | Yes | The whole public library with search and filters |
| Reference | **Forms** | `members/documents.html#forms` | Yes (a filter preset of the library) | ICS 201/205/213/213RR/214/214a/309, WA State forms, WSDOT 580-020, ARRL Radiogram, all linked to official sources |
| Reference | **Contacts & roles** | `#` (future `members/contacts.html`) | No | Who does what (role and callsign), role e-mail addresses, where applications and net reports go |
| - | **groups.io ↗** | `https://spokaneares-acs.groups.io/g/main` | External | Member e-mail list, calendar, Files (ACSFOG, roster), Wiki, subgroups. Members-only; a moderator approves each new member |

**Why this set.** It follows what members actually do week to week, from the evidence: check into the Tuesday net and take net-control turns (`interim-ag7qp/spokane-county-ares-acs.md`), answer twice-monthly Winlink assignments (same), keep programmed radios and go-kits (`acs-task-book.md`), train and log task-book items (same), show up to exercises and events (same), and reach for scripts and forms (`documents-inventory.md` §6 Step 6, "organise the Library by task"). "Comms plan & frequencies" was renamed **Repeaters & programming** on purpose: an ICS-205 comms plan and county channels are FOUO material (decisions.md D13, "Never publish").

**Navigation-efficiency rules for the members area**
1. **Search** on the Overview and Documents pages. It is a plain `<form action="documents.html" method="get">` with a `q` field; JS filters the list in place when available (progressive enhancement).
2. **"This week" at a glance** at the top of the Overview, fed only by `data.js`.
3. **Quick links**: at most 8 top tasks on the Overview (see `content-members.md` §4).
4. Every document row shows **format, date/version, source and status** so members never open a file to find out whether it is current.
5. Stable deep links: `members/documents.html#forms`, `#net-ops`, `#join`, `#training`, `#readiness`, `#digital`, `#reference`, `#history`, and one `id` per document (the `id` in `data.js`, e.g. `#ics-213`).
6. The members menu works without JS (a plain list of links; the mobile disclosure may use `<details>`).

---

## 5. Anchor map (use these ids in every option)

**`index.html`**: `#main`, `#this-week`, `#what-we-do`, `#how-a-message-gets-through`, `#quiz`, `#visit`, `#join`, `#what-it-takes`, `#who-we-serve`, `#agencies`, `#w7gbu`, `#members`.

**`how-it-works.html`**: `#main`, `#the-system`, `#who-calls-us`, `#activation`, `#alert-levels`, `#standing-order-1b`, `#nets`, `#weekly-net`, `#other-nets`, `#message-handling`, `#ics-213`, `#logs`, `#winlink`, `#follow-a-message`, `#exercises`, `#public-service`, `#where-we-work`, `#roles`, `#real-world`.

**`about.html`** (every H2 is a TOC entry): `#at-a-glance`, `#what-is-ares`, `#what-is-acs`, `#two-hats`, `#auxcomm`, `#mission`, `#what-we-are-not`, `#legal-basis`, `#who-we-serve`, `#agreements`, `#structure`, `#leadership`, `#membership`, `#ares-path`, `#acs-path`, `#join-steps`, `#training-path`, `#acs-task-book`, `#arrl-task-book`, `#commitment`, `#equipment`, `#activation`, `#licensing`, `#where-we-meet`, `#w7gbu`, `#history`, `#faq`, `#glossary`, `#contact`.

**`members/index.html`**: `#main`, `#search`, `#this-week`, `#quick-links`, `#rota`, `#winlink`, `#requirements`, `#sections`, `#groups-io`, `#help`.

**`members/documents.html`**: `#main`, `#search`, `#most-used`, one id per category (`#net-ops`, `#join`, `#forms`, `#training`, `#readiness`, `#digital`, `#reference`, `#history`), one id per document, `#not-here`, `#status-key`.

**`members/exercises.html`**: `#main`, `#next-up`, `#upcoming`, `#set-2026`, `#shakeout-2026`, `#winlink-assignments`, `#year-at-a-glance`, `#after-action`, `#public-service`, `#past`.

---

## 6. Shared data (`assets/data.js`)

- **One file, one global:** `assets/data.js` sets `window.SPOKARES`. Load it with a plain `<script src>` (works from `file://`; **no `fetch()`**).
  - From an option's top-level pages: `<script src="../_shared/assets/data.js"></script>` if the option lives in `round2/<option>/` and references the shared folder, or `assets/data.js` if the option copies `_shared/assets/` into itself. Either is fine; **do not edit the copy**. Members pages add one more `../`.
- **It is the single source** for: next net, net-control rota, the 5th-Tuesday simplex and Winlink-night flags, the GMRS net, meetings and their next dates, exercises and events, Winlink assignments, the document library and external links. Pages must not hard-code these values in visible text except as the **no-JS fallback** (which must match `data.js` as of 2026-09-26).
- **Mockup "today" is frozen at `SPOKARES.meta.asOf` = 2026-09-26** (a Saturday, day 1 of the WSDOT exercise). Helpers accept an ISO date; `SPOKARES.fn.today()` returns `?today=YYYY-MM-DD` from the URL if present (for demos), otherwise `asOf`.
- **Helpers** (pure functions, no DOM): `SPOKARES.fn.upcoming({from, days, limit, types})`, `SPOKARES.fn.nextNet(from)`, `SPOKARES.fn.nextMeetings(from, n)`, `SPOKARES.fn.documents({q, category, status})`, `SPOKARES.fn.fmtDate(iso, style)`, `SPOKARES.fn.fmtTime("20:00")`. See the header comment in `data.js`.
- **`verify: true`** on any record means: render that element with `data-verify="true"`. Do not print "verify", "TBD" or "to be confirmed" chips.
- **Progressive enhancement:** every widget has a static HTML fallback (the content files give it). JS may replace the fallback with fresher computed content; nothing essential may require JS.

---

## 7. How D15 maps into the four sections

| D15 item | Where it lives now |
|---|---|
| `/` Home (This week, Fig. 1, join path 0-3, quiz, message-path teaser, partner line) | `index.html` (unchanged in spirit) |
| `/about/`, `/about/what-we-do/`, `/about/leadership/`, `/about/partners/`, `/about/w7gbu/`, `/about/history/` | `about.html` sections (`#what-is-ares` … `#history`) |
| `/about/how-it-works/` (message path) | `how-it-works.html` (expanded: activation, nets, message handling, exercises, public service, roles) |
| `/nets/` (public explanation of nets, meetings, where we meet) | `how-it-works.html#nets` (explanation) and `about.html#where-we-meet` (address, meetings); schedules and rota for members in `members/index.html` and the future `members/nets.html` |
| `/nets/net-control/`, `/nets/repeaters/` | Future `members/nets.html` and `members/radio.html` |
| `/join/`, `/join/requirements/`, `/join/get-licensed/`, `/join/orientation/` | `index.html#join` (the short path and CTAs) and `about.html#membership` … `#licensing` (the full detail). A standalone `join.html` can come later without changing anchors. |
| `/library/`, `/library/go-kits/`, `/library/resources/` | `members/documents.html` (public, ungated) and the future `members/readiness.html` / `members/winlink.html` |
| `/members/` (link hub into groups.io) | `members/index.html`, now a full public hub; groups.io stays the home of anything member-only (D8) |
| `/contact/` | `about.html#contact` and the footer's "Contact the emergency coordinator" |
| `/privacy/` | Footer link (`#` in the mockup) |

---

## 8. Content rules (apply to every page of every option)

1. **Facts only from `_shared/`.** Never invent names, callsigns, frequencies, dates, times, statistics, member counts, dues or testimonials. Quotes are verbatim and attributed as in the content files.
2. **Uncertain facts** are marked `[verify]` in the content files. In HTML, put `data-verify="true"` on the smallest element that holds the fact. No visible TBD chips. One footer line: "Mockup — content to be verified before launch".
3. **No public status widget.** No "Condition Green" or any "current alert level" on any page. The alert levels may be **explained** (How it works, About), never shown as a live state.
4. **No personal contact details.** No personal phone numbers or personal e-mails. Role addresses (`join@`, `ec@`, `webmaster@` spokares.org) are **proposals** from decisions.md D11 and not live; mark them `[verify]`.
5. **People.** Role plus callsign everywhere. Personal names appear **only** on the net-control rota, exactly as published on ag7qp.com (callsign plus name).
6. **Never publish (SCEM FOUO rule, decisions.md D13):** county, hospital, SCEM, SHARES or 800 MHz frequencies, channel numbers or talk-groups (including the Hospital Net's channel); procedures from SCEM equipment or the ACSFOG; SHARES station details, the Region 9 cache, MNU configurations or equipment/hospital-station locations; the 800 MHz deck.
7. **Deliberately left off public pages** (do not add them back): the 1st-Tuesday staff meeting (D13 #16), the Hospital Net and its schedule (`facts.yaml` `net.hospital`, publish "no"), any simplex frequency (D13 #5), the W7GBU-10 gateway frequency (D13 #6), the net roster (groups.io only), the website hack, member counts (groups.io's count changes; `facts.yaml`).
8. **Names:** "Spokane County ARES-ACS"; ACS = "Auxiliary Communications" (the seal's wording) `[verify]` (D6). "ARES/RACES" only as history. ALERT Spokane runs on **Regroup** (not Code Red). FEMA courses are IS-100.c, IS-200.c, IS-700.b and IS-800.d.
9. **Times** are local (Pacific). Show 12-hour times to the public ("8:00 PM"); 24-hour is fine in members' tables ("2000"). **Never show the net at 10 PM.**
10. **Third Thursday meeting:** show "Third Thursdays, evenings" with no start time (`[verify]`; 6 PM vs 7 PM, D13 #4). **New Member Orientation:** "held regularly; ask us for the next date" (D13 #17).
11. **Photos:** see `assets/README.md`. Never caption people with names or callsigns; blur the legible name badge; label missing photos with a visible "PHOTO NEEDED" frame and a shot-list caption.

---

## 9. Accessibility and build baseline (all options)

- Landmarks: one `header`, `nav` (primary; the members menu is a second `nav` with `aria-label="Members"`), `main#main`, `footer`. One `h1` per page; no skipped heading levels.
- Visible focus on every interactive element; WCAG AA contrast (4.5:1 text, 3:1 large text and UI).
- `prefers-reduced-motion: reduce` disables non-essential motion.
- Alt text for every meaningful image (suggested alt text is in `assets/README.md`); decorative images `alt=""`.
- Tables have `<caption>` or an accessible name, and `scope` on header cells.
- The About TOC: sticky side column at ≥ 1024 px; at phone width a `<details>` "On this page" disclosure (closed by default) directly under the H1. Optional scroll-spy highlights the current section (progressive enhancement).
- External links: indicate "external" visually and in text for screen readers (e.g. a visually hidden "(opens groups.io)").
- Responsive at 390 px and 1440 px; no horizontal scroll at 390 px; tap targets at least 44 × 44 px.
- No frameworks that need a server; no build step; Google Fonts allowed.

---

## 10. Platform notes (for later implementation)

| Pattern | WordPress (block theme) | Static site generator |
|---|---|---|
| "This week" card, next-net line, rota | One block pattern fed by the calendar source (groups.io ICS if public, else a role-owned Google Calendar; D9) | A partial rendered at build time from the same data file; optional client-side refresh |
| Document library | A "Document" post type with a Category taxonomy and fields (format, version, source URL, status, owner, reviewed) | A YAML/JSON data file, one entry per document |
| Members menu | A second registered menu location | A data-driven nav partial |
| About TOC | Generated from H2s by a small block or plugin | Generated from headings at build time |
| Exercises & events | Calendar feed plus an "Exercise" post type for instructions and AARs | Data file plus Markdown pages |
