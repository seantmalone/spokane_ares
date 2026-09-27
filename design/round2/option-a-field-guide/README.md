# Option A: Figure One

Round 2, Spokane County ARES-ACS redesign. Led by round-1 concept 09 "Field Guide", which ranked first (composite 8.21).

## Concept

Most people have no mental picture of emergency radio. Figure One gives them one: labelled figures, like plates in a field guide, then one clear first step for each kind of visitor. The pictures carry the explaining. Around them the site is kept calm, technical and plain, so a county emergency manager can take it seriously and a curious newcomer still enjoys it.

- **Signature pieces:** Fig. 1 (handheld volunteer, W7GBU repeater, county EOC) as the Home hero; the five-question "Is this for me?" quiz; "The trail to joining" (posts 0 to 3); numbered figure plates on How it works; a numbered field-guide reference with a sticky index on About; a tabbed index rail for members.
- **Look:** Lexend only. Ponderosa pine #1F5A46 for actions, Palouse wheat #E8B94A for highlights, sky #8CC1E8 for figure skies, and seal navy #24348A as the structural colour (header rule, footer, agency band, table heads). Flat Inland Northwest scenery with faceless figures in hi-vis vests, drawn from a reusable symbol kit.
- **One motion moment:** on Home, the ICS-213 slip travels Fig. 1's arc once. With reduced motion or no JS it rests at the EOC.

## Pages

Open any page straight from disk (`file://`). No server and no build step.

| Section | File | What it is |
|---|---|---|
| 1. Home | `index.html` | The newcomer's first step. Fig. 1 hero with Join the team and Take the 2-minute quiz, the This-week strip, a four-door "Where are you starting?" router (including an Agency partner door), the quiz, the trail to joining beside "Here's what it takes", what the team does, licensing in plain English, three ways to visit, and From the field. |
| 2. How it works | `how-it-works.html` | Operations as numbered plates: Fig. 1 extended (the system and its backups), Fig. 2 following one message (a labelled scenario), Fig. 3 when the county calls (activation track and the four readiness levels, explained and never shown as a live status), Fig. 4 anatomy of a Tuesday net, Fig. 5 handling messages, Fig. 6 exercises and training (calendar from data.js), Fig. 7 public service. Then Where we work, Pick your post (role cards with eligibility tags), Fig. 8 what it takes, and a closing Join band. |
| 3. About ARES & ACS | `about.html` | "The complete field guide": 12 numbered sections with a sticky left index (only the current section unfolds), a sticky "Contents" bar on phones, glossary margin notes at 1200px and up, the five-row ARES vs ACS identification table, "In plain English" legal callouts, For agencies and partners, the W7GBU story, the FAQ, a glossary and the contact route. |
| 4. For Members: overview | `members/index.html` | This week at a glance (next net and net control, next exercise, next meeting, the Winlink assignment, open net-control slots, radio settings), search with live suggestions, six most-used tiles, every sub-section as a card, the rota, Winlink assignments, standing requirements, groups.io and who to ask. |
| For Members: documents | `members/documents.html` | All 74 library entries, searchable and filterable by category (`?q=` and `?cat=` in the URL, so a filtered view can be bookmarked). Each row has a format, date or version, source badge and a one-line "what it's for". `documents.html#forms` opens the Forms preset. |
| For Members: exercises | `members/exercises.html` | Upcoming exercises as plates (when, where, bring, log, how to take part), before/during/after, a month-by-month calendar with type filters, Winlink assignments, public service, the year at a glance and past exercises. |

The members menu lists every sub-section from `_shared/ia.md`. The six not built yet (Nets & net control, Winlink & digital, Repeaters & programming, Training & task books, Go-kits & readiness, Contacts & roles) link to `#`. On desktop it is a sticky left rail with icons and one-line hints. On phones it becomes a sticky tab bar under the header: the current tab scrolls into view and the edges fade to show that more tabs sit on either side.

Supporting files: `assets/site.css` (the design system), `assets/site.js` (progressive enhancement, one function per widget), `assets/data.js` (shared data, byte-identical to `_shared`), `assets/kit.svg` (illustration kit source), `_chrome.html` (canonical header, members menu and footer to copy), `kit.html` (visual reference of the kit, not linked from the site), `DESIGN.md` (tokens, components, widget contracts, how to build a new figure).

## How it answers the round-1 feedback

| Judges said (Field Guide, round 1) | What changed |
|---|---|
| Caveat handwriting, rounded cards and cartoon figures read as edtech or a children's museum; too soft for a unit that works inside EOCs | Caveat is gone. Figure labels are Lexend 500 at 13-15px with 1px leader lines and numbered callouts. Radii cut to 10/8/6px. People are faceless silhouettes in hi-vis vests. Seal navy ties the site to the county seal, and the copy is plain declaratives. |
| Real members appear only as small taped polaroids | Real photos run as captioned plates at useful sizes, never wider than their native width (the parade operator's badge is blurred). Missing shots are clearly marked "PHOTO NEEDED" frames with a shot list. |
| Four cards crowd the hero, and the quiz appears twice | The hero is just Fig. 1, the H1, the capability sentence, one primary and one secondary button. The Tuesday tile became the slim This-week strip. The quiz appears once. |
| No agency contact path | An Agency partner door on Home (SCEM activates ACS under state mission numbers and RCW 38.52; members are background-checked; auxiliary support, not first response), a navy "For agencies and partners" section on About, and an audience-grouped footer with an Agency partners column. |
| Member tasks are thin | A full For Members section with its own menu, a this-week overview, a searchable document library and an exercises page. No login anywhere; groups.io stays the only members-only destination. |
| Custom SVG is hard for volunteers to extend | A documented kit of about 25 `<symbol>` parts and 20 icons, composed with `<use>`. DESIGN.md walks through assembling a new figure. |
| Kept, because all four judges praised it | Fig. 1, the quiz and its four routes, and "Licensing in plain English". |

Grafts from other round-1 concepts: the 0-3 join steps with `?from=` deep links (Plain Signal), the ICS-213 message path redrawn as a static plate (When the Lines Go Down), the ARES vs ACS table, the agency door and the grouped footer (County Standard), the layers diagram (Coverage), "Here's what it takes" and the role cards (Hi-Vis), tap-to-define terms (The Relay), the self-computing next net and "Copy radio settings" (Tuesday 2000), and the task-organised member index (Ops Board).

The round-1 fact errors are also fixed: New Member requirements come from the 2024-08-09 ACS Task Book, it says "Apply to join" groups.io (never "subscribe"), the county application links to spokanecounty.gov, the check-in order is officials, members, then visitors, and "Email me my path" is replaced by "Copy a link to my path".

## Integration pass

What the integrator checked and fixed after the page builders finished (details in DESIGN.md section 10):

- **Links:** a script parsed every href and src on the six pages. Every file and every `#anchor` resolves over `file://`, with no absolute paths and no duplicate ids.
- **Chrome:** the header, footer and kit sprite are identical on all six pages (after normalising `../`). `aria-current` is right everywhere: "page" on the current section, "true" for For Members on the two sub-pages, and the current item in the members menu.
- **Deep links:** cross-page anchors (footer, doors, members menu) landed short on long pages, for example `how-it-works.html#roles` by 279px on desktop and 674px on a phone. A shared fix in `site.js` now lands every tested anchor exactly at its scroll margin at 1440 and 390.
- **One site:** the members pages were each duplicating shared CSS (phone tables, headings, link rows, tab fade). That CSS now lives once in `site.css`, and a cascade side effect was fixed along the way: past-exercise records had been squeezed to 38% width on phones.
- **Phones:** the menu opens and closes (`aria-expanded`, Escape), and Join the team stays visible outside it. The members tab bar is sticky, scrolls the current tab into view and fades its edges in both directions.
- **Runtime:** no console errors, no failed loads and no horizontal scroll at 390, 768, 1024, 1280 or 1440. Every data widget renders from `data.js`, and the date demos below work. With JS off, every page reads and navigates, the menu shows as a plain list and every widget shows its static fallback.
- **Accessibility:** skip link, one header/nav/main/footer set, one h1 per page, no skipped heading levels, alt text on every image, no dangling ARIA references. Tabbing through the first 60 stops on each page showed a 3px focus ring every time (pine on light, wheat on navy). An automated contrast scan of text on solid backgrounds, at 1440 and 390, found nothing under AA.
- **Content rules:** no alert-status widget, no visible TBD or verify chips, no personal phones or emails (role addresses carry `data-verify`), no simplex frequency, no 10 PM net, the mockup line in every footer. 203 uncertain facts carry `data-verify="true"`. Add `?verify=1` to outline them.

## Demo URLs

- `index.html?today=2026-09-29&now=20:15`: the net is "On the air now".
- `members/index.html?today=2026-10-06`: "Tonight", with an open net-control slot.
- `index.html?from=licensed#join`: the trail marks post 1 "Start here".
- `members/documents.html?q=winlink` or `members/documents.html#forms`: a filtered library.
- Any page with `?verify=1`: outlines every fact still to be verified.

## Known limitations

- **Home runs long:** about 6,100px on desktop against the brief's ~5,500px guideline. The join trail starts at about 1,895px (target ~1,800), and at about 2,780px on a phone (3.3 screens against ~3). The owner wants Home kept high-level, so the recommended cut is to fold "Three ways to visit" into a short line. The strip's "Plan a first visit" link and About's "Where and when we meet" already cover it. `#visit` must stay on Home because the footer links to it.
- **Long reference pages on phones:** About is about 42,800px and How it works about 27,800px at 390px. The sticky Contents bar and the "On this page" chips are the way through. Next candidates to collapse on phones: About's glossary and partner table, and How it works' other-nets table.
- **Figure numbering restarts on each page.** How it works opens with "Fig. 1, extended" and About starts at Fig. 3, to match the brief. A reviewer may find that odd across pages.
- **GMRS repeater:** How it works lists "WRCY281 GMRS repeater (GMRS channel 7)" from `content-how-it-works.md` (marked `data-verify`). The members pages leave it off, because `data.js` notes that listing it needs the repeater owner's OK. Settle this once before launch.
- **Placeholders:** the six unbuilt members sub-sections, Privacy and Disclaimer link to `#`, and files marked "hosted here" have no real download yet. The role addresses (join@, ec@, webmaster@spokares.org) are proposals.
- **Photos:** people photos need releases (marked `data-verify`). There are four PHOTO NEEDED frames with shot lists: one each on Home and How it works, two on About.
- **Date:** the mockup's "today" is frozen at Sat, Sep 26, 2026 (the WSDOT exercise is "Happening now"); change it with `?today=`. Lexend loads from Google Fonts, so offline viewing falls back to a system sans.
- **Deliberate "Code Red" mentions:** About and Documents quote the 2024 Task Book's "Code Red" only to say ALERT Spokane has since moved to Regroup.

## Screenshots

In `shots/`, captured 2026-09-26 after the integration pass:

- `*-desktop-fold.png`: 1440x900, from the Chrome CLI.
- `*-desktop-full.png`: the top 4,200px at 1440 wide, from the Chrome CLI (not the whole page).
- `*-mobile.png`: the top 3,000px at a true 390px width. Headless Chrome on macOS won't make a window narrower than about 500px, so `--window-size=390` lays the page out at ~500px and crops it. These were captured with 390px device emulation over the DevTools Protocol instead.
- `about-desktop-mid.png`: About opened at `#who-we-serve` at 1440x900, showing the sticky index mid-page.
