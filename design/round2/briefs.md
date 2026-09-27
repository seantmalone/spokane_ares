# Round 2 briefs: Spokane County ARES-ACS

Written 2026-09-26 from the round-1 judging in `design/REVIEW.md`, `design/concept-specs.md` and `design/inspiration.md`. I also reviewed the source and screenshots of the three leaders and of the graft sources (County Standard, Hi-Vis, Plain Signal, Coverage, Tuesday 2000, The Relay and Ops Board).

Each option keeps its leader's personality, fixes every criticism the judges made of that leader, and adds the strongest grafts. Each now spans the four sections the owner asked for:

1. **Home** (`index.html`): the newcomer's first step, with clear calls to action.
2. **How it works** (`how-it-works.html`): operations, and what members actually do.
3. **About ARES & ACS** (`about.html`): long-form, with a table of contents.
4. **For Members** (`members/index.html`, `members/documents.html`, `members/exercises.html`): its own sub-menu, built for navigation efficiency.

Facts, page copy, the IA and the members sub-menu come from `design/round2/_shared/` (`ia.md`, `content-*.md`, `assets/data.js`). Where these briefs name content, `_shared` wins on facts.

## The three options at a glance

| | A: Figure One | B: Carry the Message | C: Open Channel |
|---|---|---|---|
| Led by | Field Guide (09) | When the Lines Go Down (12) | Open Door (04) |
| Thesis | Explain it with labelled figures, then route each person to one first step | Make people feel why it matters, then ask | The easiest yes is "come visit"; belonging leads to joining |
| Home H1 | "When phones go quiet, radio still gets the message through." | "When other systems go down, Spokane County still has a voice." | "From Bloomsday to blackouts, we keep Spokane County connected." |
| Signature | Fig. 1 plate and the "Is this for me?" quiz | The ICS-213 message path, told as a scenario | "Three ways to visit" planner and the This-week card |
| How it works | Numbered figure plates (Fig. 2-7), static | Scrollytelling story, then daylight operations | "A month with the team": calendar-led and photo-rich |
| About | Numbered field-guide reference, left index TOC, margin notes | Editorial serif long read, Tufte sidenotes | Plain-language handbook, "The short version" boxes |
| Members nav | Left index rail with kit icons (tabs on mobile) | Night ops sidebar (Ops Board) | Horizontal sticky pill bar |
| Type | Lexend only (Caveat removed) | Crimson Pro + Schibsted Grotesk | Bricolage Grotesque + Atkinson Hyperlegible |
| Colour | Ponderosa #1F5A46, wheat #E8B94A, sky #8CC1E8, new seal navy #24348A | Night #0E1A33, slate, dawn #F3C29B, red #D0202A, amber #FFC857 | Dusk #2F4E7E, pine #2E5B4F, seal red #C62128, lamp #F2C14E (no peach) |
| Imagery | Flat vector plates from a reusable symbol kit, plus photo plates | SVG diagram language, night-blue duotone photos | Warm natural-colour photos and line icons |

## Cross-cutting fixes (every option)

1. Four sections, one chrome. Every page carries the same header. The seal links to index.html, and the primary nav reads, in this order: Home, How it works, About ARES & ACS, For Members. A persistent "Join the team" button is the only filled CTA in the header. Mark the active section with aria-current (all members/* pages mark For Members). "Join the team" on every page points to index.html#join, where the 0-3 path lives. At 390px the Join button stays visible outside the collapsed menu; the menu button uses aria-expanded.
2. Members sub-nav. It lists every members sub-section exactly as named in _shared/ia.md. Mocked pages link to members/index.html, documents.html and exercises.html; the rest link to "#" but look real. groups.io is marked as an external link, and no "Member login" appears anywhere (no site accounts, decisions D8). The sub-nav is sticky on desktop, usable at 390px without opening the main menu, and marks the current item with aria-current="page". Any member resource is at most 2 clicks from any page (For Members, then the item).
3. No public alert status. Delete every "Condition Green", "normal operations", "current level" or status pill, strip and badge. Alert levels appear only as an explanation ("What the alert levels mean": Green inactive, Yellow alert, Orange stand by, Red deploy, what members do at each, then the after-action review within 72 hours). That explanation lives on How it works or About and in the members quick reference, never with a highlighted current level.
4. No visible review clutter. Pages show no "verify", "TBD", "to confirm", "[bracketed placeholder]", "pending permission" or "Photo credit" chips. Uncertain facts carry data-verify="true" on the smallest wrapping element: the Third Thursday start time, the orientation date rule, the 0730 Bunch, the GMRS net, "no membership dues", role addresses, leadership call signs, the disclaimer and the scenario copy. Optional reviewer aid: ?verify=1 adds a dashed outline to [data-verify], hidden by default. Every footer carries one small line: "Mockup — content to be verified before launch".
5. One data source for everything that changes. Every date-dependent element renders from window.SPOKARES in assets/data.js:
   - the next net, computed as the next Tuesday at 20:00 America/Los_Angeles from today's date (never hardcoded)
   - net control from the rota, shown by call sign only ("open" renders as "Net control slot open: volunteer on groups.io")
   - simplex and GMRS week flags, the next meeting and workshop, coming-up events, exercises, the Winlink assignment due, and document dates

   Past items drop off automatically. A multi-day event in progress reads "Happening now": the Sep 26-27 WSDOT exercise is live on 2026-09-26, so test it. Tuesday 20:00-21:00 reads "On the air now". The no-JS fallback is the standing pattern in static HTML (Every Tuesday, 8:00 PM, W7GBU 147.300 MHz, +600 kHz, 100 Hz tone) plus a link to the groups.io calendar. Each widget is one self-contained function that maps to one WordPress block or pattern, or one SSG partial.
6. Correct the round-1 fact errors and superseded rules. All three leaders had some.
   - No "six months of activity" or "IS-100 + IS-700" ID rule. New Member requirements come from the 2024-08-09 ACS Task Book: IS-100, 200, 700 and 800, ARRL Basic EmComm, HIPAA, ALERT Spokane registration (now on Regroup, not "Code Red") and orientation, leading to RADO and the ACS ID card.
   - Say "Apply to join our groups.io list; a moderator approves new members". Never "Subscribe".
   - Net check-in order is "officials, members, then visitors". Drop the A-F/G-M/N-Z groups.
   - The county application links to spokanecounty.gov (FormCenter 276), not .org.
   - Orientation reads "held regularly; contact us for the next date" unless _shared says otherwise.
   - The Third Thursday time is data-verify. Never show the net at 10 PM. Show no simplex frequency.
   - No Terms page (Privacy plus a disclaimer instead) and no public net roster PDF (the member list stays in groups.io Files).
7. Never publish (the SCEM FOUO rule).
   - No county, hospital, SCEM, SHARES or 800 MHz frequencies, channel numbers or talk-groups. The Hospital Net's channel stays off the site.
   - No ACSFOG content; link to groups.io only.
   - No SHARES, cache, MNU or hospital-station details.
   - No personal phone numbers or emails. Contact routes are role addresses or an about.html#contact section (data-verify).
   - No names or call signs on photos. Crop or blur the parade operator's name badge.
8. Agency credibility in every option.
   - The first viewport of Home names Spokane County Emergency Management in plain text and states the capability in one sentence.
   - Give agencies an explicit door ("Agency partner" or "For served agencies") that leads to about.html#for-agencies, with "Contact the emergency coordinator" and the agreements (IEVHFRA MOU, the ARRL and Red Cross agreement).
   - State the scope plainly: "an auxiliary support function; members are not first responders".
   - Group the footer by audience (New to radio, Licensed hams, Members, Agency partners). Include the meeting address (1121 W. Gardner Ave, Spokane, WA 99260), groups.io, Privacy, "A volunteer group, not an emergency service. In an emergency, call 911." (data-verify, for SCEM review) and "Formerly Spokane County ARES/RACES".
9. Home gets to the ask fast.
   - At 1440x900 and 390x844, the first viewport shows the H1, the capability sentence, the primary CTA and one secondary CTA.
   - The join path starts within about 2 desktop screens (about 1,800px) and about 3 mobile screens.
   - Desktop Home is no longer than about 5,500px.
   - Every section ends in one next action, and no module repeats on a page.
10. One canonical join path, on the Plain Signal model: 0 Get licensed, 1 Join ARES, 2 Get connected, 3 Qualify for ACS (optional). Each step has "How long", "You need" and exactly one action. ?from=unlicensed|licensed|ares|acs highlights the next step and offers "Copy link to this path". Link the real destinations: the ARES application (2024-11-07; where to send it is data-verify), groups.io main, spokanecounty.gov FormCenter 276, HamStudy and NWS Spokane SKYWARN training.
11. Acronyms. On each page, the first use of ARES, ACS, RACES, EOC, ICS-213, net control, Winlink, SET and W7GBU is a tap-to-define term, on The Relay's pattern: <button class="term" aria-expanded aria-controls> plus a note. Without JS the definition is still visible, inline or in the margin. Each option styles it its own way. ACS expands to "Auxiliary Communications", never "Auxiliary Communications Service".
12. Radio settings, one tap. Every next-net card offers "Copy radio settings" (147.300 MHz, +600 kHz, 100 Hz tone) and "Add to calendar", on Tuesday 2000's pattern. Copy uses the clipboard API, falls back to a textarea and reports in a status message. Add to calendar builds a weekly .ics (RRULE) in the browser, with no download from a server.
13. About page readability.
   - Measure 60-75ch. Body at least 17px (at least 18px for Atkinson), line-height 1.6-1.75.
   - A clear H2/H3 hierarchy with anchor links on the headings.
   - A sticky side TOC at 1024px and up, with active-section highlight from one IntersectionObserver. On mobile, a collapsible TOC (a <details> element).
   - "Back to top" after each H2.
   - County Standard's five-row ARES vs ACS table, which stacks into labelled cards at 390px.
   - Callouts for "in plain English" and the legal basis, the FAQ as <details>, and a "Last reviewed" line (data-verify).
14. Members documents library.
   - Search box, category filter chips, a format badge, an updated date, a one-line "what it's for" and a source badge on every entry. Source badges: "Official: FEMA", "ARRL", "Spokane County ARES-ACS" or "Members-only on groups.io".
   - Standard ICS forms link to FEMA.
   - Without JS, every entry shows, grouped by category with anchors.
   - ?q= and ?cat= in the URL, so members can bookmark or share a filtered view. Announce the result count via aria-live.
15. Imagery and marks.
   - Use real photos only where they hold up at the rendered size. IMG_6574 can run full width; the 659px photos stay at 660px or less, or get a treatment.
   - Missing shots get a frame labelled "PHOTO NEEDED: ..." with a short shot list: EOC radio room during an exercise, wide; net control at a home station; volunteers at a Bloomsday aid station; members at a hospital drill, wide, landscape. Use at most two per page, styled in each option's system so they read as intentional.
   - Use the real marks (the seal, the ACS seal, the ARRL ARES emblem, the SCEM seal) without any "pending permission" text.
   - Never use textLogoACS_dash.png. The legacy ARES/RACES seal appears only in About's history section.
16. Accessibility, build and design hygiene.
   - Skip link; header, nav, main and footer landmarks; one h1 per page; a visible 3px focus ring; WCAG AA contrast, including text over photos; 44px targets; alt text that describes the activity.
   - Respect prefers-reduced-motion. No horizontal scroll at 390px. Tabs, chips and filters all work from the keyboard.
   - JS is strictly progressive enhancement: every page reads and navigates with JS off. No fetch() of local files. Relative links only. Google Fonts only.
   - Carry over the round-1 hygiene: sentence case; no tracked all-caps eyebrow labels; no middle-dot metadata strings; no arrow glyphs in button text; numbered markers only for true sequences; one motion moment per page.

## Option A: Figure One (led by Field Guide (09))

Round-1 source: `design/concepts/09-field-guide/`. Build in `design/round2/option-a-*/`.

### Keep
- Fig. 1, the hero plate: a handheld volunteer (1) reaches the W7GBU repeater at 147.300 MHz, "up high so it hears far" (2), which reaches the county EOC (3). Keep the numbered callouts and the caption bar "Fig. 1 How a message gets through when phones are down". On mobile, keep the numbered three-line caption list under the figure.
- H1 "When phones go quiet, radio still gets the message through.", with the subline naming Spokane County Emergency Management, the National Weather Service, local hospitals and community events.
- The five-question "Is this for me?" quiz, as structured:
  - Questions: licensed? help locally? background check OK? drawn to digital? when free?
  - A reaction note and a small illustration for each answer, and trail-blaze progress.
  - A result card with "How long", "First step", one button, an "Along the way" list and "Retake the quiz".
  - Four routes: Start with a license class, Register for ARES now, ARES + ACS, SKYWARN first.
- "Licensing in plain English":
  - the driver's-test analogy, and "every question in the pool is published in advance"
  - the Practice (HamStudy) / Take a class / Pass the exam trio
  - the $35 FCC fee
  - the "Where to take the exam" list, and the Fig. 2 drawing comparing the car test with the ham radio exam
- The ARES and ACS "field marks" cards, side by side, with the ARRL ARES emblem and the ACS seal. ARES: no background check; can help without an emergency being declared. ACS: has passed a county background check; can work inside EOCs, ICPs, hospitals and shelters.
- "The trail to joining" with posts 0-3 (step 3 tagged Optional), the drawn locator map (Gardner Ave, Jefferson St, courthouse) instead of the Google screenshot, and "Why W7GBU?" with the Dean Bula quote.
- The palette and voice: ponderosa #1F5A46, Palouse wheat #E8B94A, sky #8CC1E8, basalt #2C2F36, river teal #2A8C9A, paper #FBFCFD, Lexend throughout. Flat vector Inland Northwest scenery (basalt ridge, ponderosa pines, a wheat foreground). The calm, friendly microcopy ("Everyone starts here. Listening to the net needs no license.").

### Fix: each judge criticism and its remedy
- "Too soft for agencies; reads as edtech or a children's museum" (the Caveat handwriting, 28px rounded cards, cartoon figures):
  - Remove Caveat entirely. Annotations become technical-plate labels in Lexend 500 at 13-15px, with 1px leader lines and numbered 22px circle callouts.
  - Cut the radii: tiles 28px to 10px, panels 8px, controls 6px.
  - Draw people as faceless silhouettes in wheat hi-vis vests, not cartoons.
  - Add seal navy #24348A as the structural colour (the header's bottom rule, the footer, the agency band, table heads) to tie the site to the seal.
  - Write copy as plain declaratives.
- "Real members appear only as small taped polaroids": drop the tape-and-polaroid treatment.
  - Show real photos as captioned "plates" at a useful size: IMG_6574 wide on How it works, the mast-team photo at its native width of 660px or less, the parade operator with the badge blurred.
  - Add a "From the field" row on Home: two real plates plus one PHOTO NEEDED plate.
- "Four cards crowd the hero, and the quiz appears twice":
  - The hero becomes Fig. 1, the H1, the subline, one primary button (Join the team, to #join) and one secondary (Take the 2-minute quiz, to #quiz).
  - The three side tiles go. The Tuesday-net tile becomes one slim "This week" strip under the hero.
  - The quiz exists once, as an inline section; everything else links to it.
- "No agency contact path": add a four-door "Where are you starting?" router: Not licensed yet / Licensed ham / Member / Agency partner.
  - The agency door answers in two sentences: SCEM activates ACS under state mission numbers and RCW 38.52; ACS members are background-checked; this is auxiliary support, not first response.
  - It links to "Contact the emergency coordinator", about.html#for-agencies and how-it-works.html#activation.
- "Member tasks are thin": build a full For Members section (hub, documents, exercises) with its own index rail. Home's This-week strip carries net control and a For Members link. Remove "Member log in" and the public "Net control roster PDF".
- "The custom SVG illustrations (about 32 KB) are hard for volunteers to extend": build a documented illustration kit.
  - About 12 <symbol> parts: handheld operator, base-station operator, repeater on a ridge, EOC building, hospital, shelter, comm trailer, laptop/Winlink, storm cloud, event flag, pine, ground line.
  - A 24px grid, 2px basalt strokes, four fills, composed with <use>.
  - DESIGN.md shows how to assemble a new figure. Fig. 1 stays at 15 KB or less.
- Round-1 content errors in the quiz and the trail:
  - Replace the "six months of activity" and "IS-100 and IS-700" ACS rule with the current Task Book list.
  - "Subscribe to the Groups.io list" becomes "Apply to join".
  - The county link moves to .gov.
  - "There are no membership dues" gets data-verify.
- "Email me my path" needs a mail handler and collects email addresses. Replace it with "Copy a link to my path", which writes ?from= so the trail highlights the right step, plus an optional link to the contact route. The quiz has no email field.

### Grafts
- Plain Signal: the 0-3 steps with "How long" and "You need" columns and the ?from= deep link, rendered as A's trail markers in Home's #join section. The quiz result sets ?from= so the trail shows "Start here" on the right post.
- When the Lines Go Down: the ICS-213 message path, each hop a named volunteer role (shelter radio operator, then net control on W7GBU, then the EOC radio room, then the hospital team operator). Redraw it as a static four-panel plate, "Fig. 2 Following one message", with the white ICS-213 paper card, labelled "A scenario", on How it works.
- County Standard: the five-row ARES vs ACS table (Who runs it / Who can join / What you can do / What it takes / Who activates you), styled as a field-guide identification table on About. Also its agency door on Home and its audience-grouped footer.
- Coverage: the isometric County, ARES and ACS layer diagram, redrawn flat in A's style as "Fig. 3 One county, two ways to serve", on About, with a thumbnail on How it works. Keep its caution: hospitals are never pinned.
- Hi-Vis: "Here's what it takes" as a calm four-figure plate (1 net check-in a month, 1 meeting a quarter, 1 field operation a year, a 72-hour go-kit), beside the trail on Home and in full on How it works. "Pick your post" becomes seven role cards on How it works, each with an eligibility tag (Any member / ACS / No license needed to train) and a kit spot-illustration.
- The Relay: the tap-to-define glossary, styled as field-guide margin notes: a teal #1B6874 dotted underline, a margin note on About at 1200px and up, and an inline expansion on tap everywhere else.
- Tuesday, 2000 Hours: the self-computing next-net line and "Copy radio settings", in Home's This-week strip and on the members hub.
- Ops Board: the task-organised member index and the document library with a one-line "what it's for" on each entry, on For Members.

### Section approach
#### Home

An explained front door, no longer than about 5,500px.

1. Header: the seal, then Home / How it works / About ARES & ACS / For Members, and a pine "Join the team".
2. Hero: the Fig. 1 plate across 8 columns, with the H1 and subline over its sky. Buttons: "Join the team" (pine, to #join) and "Take the 2-minute quiz" (outline).
3. This-week strip, one full-width row in sky-tint: the next net and net control from data.js, the simplex or GMRS flag, Copy radio settings, the next meeting, and a For Members link.
4. "Where are you starting?": four doors, including the agency door, each with one action.
5. The quiz, once.
6. "The trail to joining": markers 0-3 with How long / You need and ?from= highlighting, with the "Here's what it takes" plate beside it.
7. "What the team does": six illustrated tiles (county comm room and trailer, hospitals, public events, SKYWARN, Winlink, exercises), each linking to its anchor on How it works.
8. "Licensing in plain English", condensed.
9. From the field: two photo plates, one PHOTO NEEDED plate and the served-agency line.
10. Footer, grouped by audience.

#### How it works

A numbered run of figure plates that explain operations, readable top to bottom, with an "On this page" chip index at the top.

- Fig. 2 Following one message: the static four-panel ICS-213 path, labelled "A scenario".
- Fig. 3 When the county calls: the activation flow (SCEM request, mission number, activation text, net control on W7GBU, assignments, release, after-action review within 72 hours), then the four alert levels explained with what members do. No current level.
- Fig. 4 Anatomy of a Tuesday net: preamble, officials, members, visitors, traffic, with the radio-settings card.
- Fig. 5 Message handling: ICS-213, the ICS-309 log, the ARRL Radiogram, Winlink through W7GBU-10, and the biweekly assignment.
- Fig. 6 Exercises and training: SET, the WSDOT exercise, the State COMMEX, Second Saturday workshops, Third Thursday meetings, with the upcoming list from data.js.
- Fig. 7 Public service: Bloomsday and the Lilac Torchlight Parade.
- Then "Pick your post", "Here's what it takes", and a closing band (Join the team / Take the quiz).

#### About ARES & ACS

"The complete field guide": a long-form reference in numbered sections (1 What ARES-ACS is ... 12 Questions).

- A sticky left index TOC mirrors the section numbers and titles; a wheat bar marks the active item.
- Body: Lexend 400 at 18px/1.7 in a 68ch column, with a right margin for glossary notes and small plates at 1200px and up.
- Content: the Fig. 3 layers diagram, the ARES vs ACS identification table, and a training-ladder plate (New Member, RADO, S-RADO, leadership).
- Callouts: "In plain English" boxes for RCW 38.52 and for RACES under the FCC, and "Field note" boxes (wheat-tint, left rule) for tips.
- Also the W7GBU and Dean Bula story, a "For agencies and partners" section with the contact route, the FAQ as <details>, and back-to-top links.
- Mobile: a sticky "Contents" bar that expands to the numbered list.

#### For Members

The members' field kit.

- Navigation: a sticky left index rail on desktop, like the tabbed index of a field guide, listing every sub-section from ia.md with a small kit icon and a one-line hint. At 390px it becomes a sticky horizontal scroller of labelled tabs under the header, with the active tab scrolled into view.
- Hub: a "This week" plate (the next net and net control, the simplex or GMRS flag, Copy radio settings; the next meeting or workshop; the next exercise; the current Winlink assignment and its due date), then a six-tile "Most used" grid (net preamble, ICS-213, Radiogram, ACS Task Book, go-kit lists, groups.io), then every sub-section as a card with a one-line description.
- Documents: search, category chips and list rows with a format badge, an updated date and a source badge.
- Exercises: upcoming exercises as small plates (date block, what, where, what to bring, how to sign up), then past exercises in a compact table.

### Design tokens

**Palette**

- Ponderosa #1F5A46: primary buttons, links (7.8:1 on paper). Pine-deep #174536: hover. Pine-mid #2E7359: illustration hills.
- Palouse wheat #E8B94A: highlights, quiz selection, the "start here" post, vests in figures (basalt on it 7.3:1). Wheat-tint #FBF1D6: field-note callouts.
- Sky #8CC1E8: illustration skies. Sky-tint #E7F2FB: the This-week strip and panels.
- Basalt #2C2F36: text. Basalt-2 #4A505C: secondary text (7.9:1).
- River teal #2A8C9A: decorative strokes. Teal-ink #1B6874: glossary underline and secondary links (6.2:1).
- New, seal navy #24348A: the structural colour for the footer, agency band, table heads and the header's bottom rule (white on it 10.9:1, wheat on it 5.9:1).
- Rule #D3DCE4, paper #FBFCFD, bark #8A5A3B (illustration only). No red except in the seal.

**Type**

- Lexend only. Caveat removed.
- Display 700 at clamp(2.25rem, 4vw, 3.5rem), line-height 1.08. H2 600 at clamp(1.75rem, 3vw, 2.5rem). H3 600 at 1.25rem.
- Body 400 (up from 350) at 17px/1.65; About 18px/1.7.
- Figure labels 500 at 13-15px. Frequencies and times use tabular-nums, not mono.

**Space and shape**

- 8px base: 4, 8, 16, 24, 32, 48, 72, 96. Gutter clamp(16px, 3vw, 32px). Max width 1240px. About column 68ch.
- Radius: tiles 10px, cards and panels 8px, buttons and inputs 6px, chips 999px.
- Figure plates: a 1px rule frame and a "Fig. n" caption bar in Lexend 600 at 13px.

**Illustration**

- Flat vector Inland Northwest plates, 2px basalt strokes, palette fills only, faceless figures in wheat vests.
- Numbered 22px callouts with 1px leader lines.
- Built from the documented symbol kit; each plate 15 KB or less.

**Photos**

- Natural colour in a plate frame (1px rule, 8px radius), captioned below in Lexend 14px. Captions describe the activity and never name anyone.
- The 659px photos are never shown wider than 660px.
- PHOTO NEEDED plates: sky-tint fill, a 2px dashed #8CC1E8 border, a small camera glyph and the caption.

Focus: a 3px #1F5A46 outline with a 2px paper offset; wheat on navy or pine surfaces.

Motion: one moment on Home. The ICS-213 slip travels Fig. 1's arc once (1.6s ease-out); with reduced motion it rests at the EOC. Quiz reactions crossfade in 150ms. No parallax.

## Option B: Carry the Message (led by When the Lines Go Down (12))

Round-1 source: `design/concepts/12-lines-down/`. Build in `design/round2/option-b-*/`.

### Keep
- The ICS-213 message path: the shelter radio operator (1), net control on W7GBU 147.300 (2), the EOC radio room operator (3) and the hospital team operator on the hospital net (4). Each hop is labelled with its volunteer role. Keep the red #D0202A message arc, the amber #FFC857 hop markers, and the line "Every hop on this path is a trained volunteer, not a machine."
- The sticky diagram beside step narration, and the reduced-motion static version.
- The white ICS-213 paper card: red top border, italic serif field values, and the note "A scenario. The message is illustrative."
- The amber "A scenario" pill on every scenario frame, and "Not a real event. It shows how the system is meant to work."
- Crimson Pro narration at 22-28px with Schibsted Grotesk for UI. The night #0E1A33, slate #33415C, dawn #F3C29B, daylight palette.
- The dawn payoff "Radio still works. Because people practice.", leading straight into "Every Tuesday night we practice." and the net card (When / Repeater / Settings).
- The Standing Order 1b quote, the "Most of the year looks like this" list, and the honest note that ARES-ACS is an auxiliary support function and its volunteers are not first responders.
- "Could you be the one on the radio?" as the join heading, the red pill "Join the team", and the tailgate go-kit photo as the dawn image.

### Fix: each judge criticism and its remedy
- "The join path starts about 6,000px down; ease of use 6.0": the story leaves Home.
  - Home becomes a compact landing page, no longer than about 4,500px.
  - "Join the team" sits in the hero, and the 0-3 path starts within about 1,600px.
  - The full scrollytelling moves to How it works.
- "Members have to skip the story on every visit": members never pass through it.
  - A night utility bar on every page shows the next net (from data.js) and a For Members link.
  - For Members is in the primary nav.
  - The members pages are light, dense and story-free.
- "The fear-first opening needs a PIO check; a screenshot could circulate as a real alert":
  - Home's H1 becomes a capability line: "When other systems go down, Spokane County still has a voice."
  - "It's 2 AM. The power's out ..." opens How it works only, after a one-line plain intro.
  - The "A scenario" pill sits in that hero and is drawn inside the SVG diagram itself, so any crop still says scenario.
  - The scenario block carries data-verify for PIO and SCEM review. No siren colours and no alert-style banners.
- "Scrollytelling is brittle for a non-developer to edit":
  - Steps are plain <article data-state> blocks (a heading plus paragraphs) that any editor can reorder.
  - One inline SVG diagram switches layers from a data-state attribute on the figure.
  - One IntersectionObserver in 60 lines of JS or fewer. No scroll-jacking and no scroll-linked animation.
  - Below 900px and under reduced motion, each step shows its own static small-multiple diagram inline.
  - Document the pattern as one WordPress block pattern or SSG partial.
- "Long empty dark stretches between story steps": cap each step's min-height at about 70vh (down from 92-100vh), and give every step a visual (the diagram state, the ICS-213 card, a photo or a log line).
- "The practical second half loses energy": after dawn, switch to daylight sections that keep the diagram language. The red message line becomes a section rule, amber numbered markers mark sequences, and big Crimson numerals carry the commitment figures, so the practical content keeps the story's voice.
- "Few real photos":
  - IMG_6574 in full colour as the dawn payoff.
  - The mast-team and comm-trailer stills in a night-blue duotone at 660px or less.
  - The parade operator, badge blurred, on public service.
  - Night-slate PHOTO NEEDED frames for the missing scenes: the EOC radio room during an exercise, and net control at a home station at night.
- Visible placeholders: "[Start time to be confirmed]" and "[Use pending permission ...]" become data-verify. "Subscribe to the Groups.io list" becomes "Apply to join", and the county link moves to .gov. The alert-level strip leaves Home. The dawn peach is never a card fill: the "Not licensed yet?" panel becomes a daylight card with a peach top rule.

### Grafts
- Tuesday, 2000 Hours: the self-computing next-net line as a slim night utility bar on every page, for example "Next net: Tuesday, Sep 29, 8:00 PM on W7GBU 147.300 MHz. Net control NZ2S." It reads "On the air now" from 8 to 9 PM Tuesday and has Copy radio settings. Its full settings grid (Repeater / Frequency / Offset / Tone / Net control / This week) goes on the members hub.
- Plain Signal: the 0-3 path with How long / You need, plus the "Where are you starting from?" segmented control and the ?from= link, as Home's "Could you be the one on the radio?" section, with the numerals in amber Crimson Pro on night.
- Coverage: its short interest form, "Tell us you're interested" (name, email, optional call sign, "Where are you on the route?" radios), beside the join path on Home. It is the one form on the site: a honeypot, and nothing stored on the host.
- County Standard: the ARES vs ACS table on About (a Schibsted table with the ARES emblem and ACS seal in the column heads), its agency door as Home's slate "For served agencies" band with "Contact the emergency coordinator", and the audience-grouped footer.
- Hi-Vis: "Here's what it takes" as four big Crimson numerals on a dawn band on How it works, titled "What it takes to be on the radio that night". "Pick your post" becomes "Every hop is a post": the four story roles plus field and shelter operator, Winlink and digital, public-event comms and SKYWARN spotter, each with an eligibility tag.
- The Relay: tap-to-define terms in the story narration (ICS-213, net control, EOC, W7GBU, hospital net) with an amber dotted underline, Tufte sidenotes on About, and The Relay's editorial long-read typography for About.
- Ops Board: the member portal (night sidebar navigation, next-net card, rota table with open slots, Winlink assignment widget, document library table) as the For Members section.
- Confirmed Contact: the W7GBU heritage (the repeater in the story carries Dean Bula's memorial call) as About's closing chapter, linked from hop 2 in the story.

### Section approach
#### Home

A short, cinematic landing page that reaches the ask fast, no longer than about 4,500px.

1. Night utility bar: the next net from data.js, Copy radio settings, For Members.
2. Header on night: the seal, the nav, the red pill "Join the team".
3. Hero on the 1920 dusk photo: H1 "When other systems go down, Spokane County still has a voice." (Crimson Pro 500), then a plain sentence naming SCEM, the NWS, hospitals and events. Actions: the red pill "Join the team" and the text link "Follow one message through a night without phones" (to how-it-works.html).
4. "One message, four volunteers": the diagram in its final state (static, no scroll effect) with the four role hops and the scenario pill, plus "Read the whole night".
5. "Could you be the one on the radio?": the 0-3 path with the segmented control and ?from=, and the interest form beside it.
6. Dawn band: "Every Tuesday night we practice.", the net card, the next three events from data.js and the go-kit photo.
7. The slate "For served agencies" band.
8. Footer on night.

#### How it works

The story lives here, followed by the operations manual.

- Opening: a one-line plain intro, the scenario pill and a "Skip to what members do" link.
- The scrollytelling:
  1. "It's 2 AM. The power's out, and so is cell service."
  2. Power and phones fail.
  3. A warming shelter needs help (the ICS-213 card).
  4. A volunteer picks up the radio (W7GBU, net control).
  5. The EOC logs it on an ICS-309 and relays it to the hospital net.
  6. Dawn: "Radio still works. Because people practice."
- Then daylight chapters in the same visual language, under "What members actually do":
  - the anatomy of the weekly net
  - activations, and what each alert level asks of members (explanation only)
  - message handling: ICS-213, ICS-309, Radiogram, Winlink through W7GBU-10, and the biweekly assignment
  - exercises from data.js: SET, the WSDOT exercise, the State COMMEX
  - public service: Bloomsday and the Lilac Torchlight Parade
- Then "Every hop is a post", the "What it takes" numerals and a closing red CTA.

#### About ARES & ACS

An editorial long read, "The long version": a daylight page under the night header.

- Body: Crimson Pro 400 at 20px/1.6 in a 64ch column, with a drop cap on chapter 1. Schibsted Grotesk for tables, labels and the TOC.
- A sticky left TOC; a 3px red rule marks the active chapter. Tufte sidenotes at 1200px and up for acronyms and sources. Pull quotes in Crimson italic (Dean Bula, Standing Order 1b).
- Chapters:
  - what the team is
  - ARES, ACS and RACES, and why both programs exist
  - the legal basis in plain English (RCW 38.52, state mission numbers, RACES under the FCC), as a boxed callout
  - who we serve (SCEM, the NWS, hospitals, the Red Cross, the IEVHFRA MOU), with the SCEM seal and the ARRL ARES emblem
  - structure and roles
  - membership paths and requirements, as tables
  - training and task books
  - equipment
  - how activations work
  - for agencies, with the contact route
  - the W7GBU story
  - FAQ, as <details>
- Mobile: the TOC is an open-by-default <details> list at the top, plus a floating "Contents" button that reopens it.

#### For Members

The operations desk.

- Navigation: a night-blue left sidebar (the Ops Board pattern) lists every sub-section from ia.md, with groups.io marked external and a mini next-net readout at the top. The workspace to its right is light. At 900px and below, the sidebar becomes a sticky horizontal scroller under the header.
- Hub:
  - a dark radio card: a big 147.300, the settings grid, net control, the simplex or GMRS flag, Copy radio settings and Add to calendar
  - the next four weeks of net control from the rota, with open slots flagged "Volunteer on groups.io"
  - the Winlink assignment due, and the next meeting and exercise
  - quick-link tiles
- Documents: a dense table (Document / What it's for / Format / Updated / Source) with search and category chips. Rows collapse to cards at 390px.
- Exercises: a vertical timeline with date slabs. Each exercise shows what, where, what to bring, how to sign up and which role to report to. Past exercises follow.

### Design tokens

**Palette**

- Night #0E1A33: header, utility bar, hero, story, footer and the members sidebar.
- Slate-2 #1F2C47 and storm slate #33415C: the story gradient and the agency band (mist on slate 6.5:1).
- Dawn peach #F3C29B: only in the dawn gradient and as a 4px top rule, never a card fill (ink on it 10.7:1).
- Daylight #FFFFFF. Paper #F6F7FA for light page backgrounds.
- Mist #C5CEDF: body text on night (10.9:1).
- Ink #0E1A33: text on light. Ink-2 #3C4863: secondary (9.1:1). Off #56627C: captions (6.1:1). Rule #D9DEE8.
- Signal red #D0202A: the message arc and the primary pill CTA (white on it 5.4:1). Red-ink #B01A23: red text and links on white (7.0:1).
- Amber #FFC857: hop markers, the scenario pill, and the focus ring on dark (11.2:1 on night).

**Type**

- Crimson Pro:
  - display 500: Home H1 at clamp(2.75rem, 1rem + 5.4vw, 5.5rem)/1.04
  - story narration 400 at clamp(1.35rem, 1.1rem + .55vw, 1.75rem)/1.45
  - About body 400 at 20px/1.6, drop cap on chapter 1
  - pull quotes in italic at 28px
- Schibsted Grotesk: UI, nav, buttons, labels, tables, the TOC, and members-page body (400/500/600 at 16-17px/1.55). Tabular-nums for frequencies, times and call signs.

**Space and shape**

- 8px base: 8, 16, 24, 40, 64, 96, 128. Wrap min(1240px, 100% - 64px); gutters 32px on desktop, 16px on mobile.
- Story steps at most 70vh tall. About column 64ch, plus a 260px sidenote rail at 1200px and up.
- Radius: panels and the diagram 16px, cards 12px, the ICS-213 paper 4px, pills 999px.

**Imagery**

- Diagram language: mist 1px landscape lines, rain hatching for the storm state, a red 3px message arc, amber numbered hop markers in 28px circles, and the "A scenario" pill drawn inside the SVG.
- Photos: the low-res mast-team and comm-trailer stills in a night-blue duotone (#0E1A33 to #C5CEDF) at 660px or less; IMG_6574 in full colour at dawn; the parade operator in natural colour with the badge blurred.
- PHOTO NEEDED frames: slate #1F2C47 fill, a 1px dashed mist border, and the caption in Schibsted 14px mist.

Focus: on dark, a 3px amber outline with a 6px night halo; on light, a 3px #0E1A33 outline with a 2px white offset.

Motion: only on How it works. The diagram crossfades states in 400ms as steps enter, driven by one IntersectionObserver. The header turns solid after the hero. Under reduced motion and below 900px: static small multiples, no transitions. Home has no scroll effects.

## Option C: Open Channel (led by Open Door (04))

Round-1 source: `design/concepts/04-open-door/`. Build in `design/round2/option-c-*/`.

### Keep
- The "Three ways to visit" planner:
  - the rounded-top door tabs (Listen / Meeting / Workshop), each with an icon and a when-line
  - date chips and the minute-by-minute timeline
  - the "At a glance" box (How long / Where / What to bring / License needed?)
  - Add to calendar (.ics and Google Calendar), and the optional "Tell us you're coming"
- The content model of the "This week" card: the next net with its date block (Tue / 29 / Sep), W7GBU 147.300 MHz, +600 kHz, 100 Hz tone, net control, the simplex week and "Visitors are welcome to check in"; a "Coming up" list of three; a row of member quick links.
- "Come say hello on Tuesday night." and "Visitors are welcome to check in." kept as the invitation line.
- "Questions newcomers ask", in the first person: "Do I need my own radio?", "I'm not licensed. Can I still come?", "How much time does it take?", "Do I have to be an expert?" and "What happens in a real emergency?"
- The "Training with partners this fall" row, as agency evidence. ARES and ACS side by side with the emblem and seal, plus the line "Even [if] you don't or can't qualify for ACS ...". The served-agencies row led by the SCEM seal.
- Atkinson Hyperlegible for body text (the UX judge's top readability score). Its slashed zero reads as ham-native in call signs like NZ2S and in 147.300. Bricolage Grotesque for display. Dusk blue #2F4E7E, pine #2E5B4F, seal red #C62128, cloud #F5F8FC and ink #1B2433.
- Warm, natural-colour real photos with a 12px radius, the big W7GBU call-sign type block, the "Your first month after joining" idea (rewritten, see Fix) and the correct mobile order (hero, then card, then sections).

### Fix: each judge criticism and its remedy
- "The hero is about socialising, not capability; an emergency manager lands on a club greeting":
  - The H1 becomes the capability line "From Bloomsday to blackouts, we keep Spokane County connected.", followed by a sentence naming SCEM as the primary served agency.
  - "Come say hello on Tuesday night. Visitors are welcome to check in." sits under it as the invitation.
  - An audience row right under the hero includes "Agency partner".
- "The peach card looks dated and off-brand": remove peach #F2B48C entirely.
  - The This-week card becomes white, with a 1px #B9C7DB border and a dusk-deep #1E3557 header band carrying a white title.
  - A live "Tonight" or "On the air now" state shows in lamp #F2C14E. The date block is dusk blue.
- "More visible verify and to-confirm chips than any other concept": zero visible chips. Uncertain facts use data-verify only.
- "A public Condition Green badge in the hero": deleted. Alert levels are explained only on How it works.
- "Weekly data needs one data file": everything date-driven renders from the data.js recurrence rules, rota and events. That covers the This-week card, the door-tab date chips, the net-control name in the minute-by-minute, the simplex and GMRS flags, the "Training with partners" row, the "Your first month" dates and the members hub. "Your first month" uses relative labels ("Your first Tuesday", "Your first workshop") filled with the next computed dates.
- "The RSVP needs a mail handler with spam protection": Add to calendar becomes the planner's primary action. "Tell us you're coming" stays optional and is the site's single form: name, email, optional call sign, which visit, and "Where are you on the route?". It has a honeypot, stores nothing on the host, is documented for a form service, and shows a mock confirmation on submit.
- "The two hero buttons compete at equal weight": one filled primary, "Plan your first visit" (dusk), plus a text-weight secondary, "Ready to join? See the four steps". The red "Join the team" lives only in the header.
- "The page is long (about 10,800px) and repeats itself": Home stays under about 5,000px. The newcomer questions, alert levels and "first month" move to How it works, and the W7GBU story moves to About. Each module appears once on the site, with links to it from elsewhere.
- Superseded content: the planner's "three groups: Alpha to Foxtrot ..." becomes "officials, members, then visitors". The typical net length becomes data-verify, and the simplex frequency is left out. "Subscribe to Groups.io" becomes "Apply to join". The "Net roster" link comes off the card.

### Grafts
- Tuesday, 2000 Hours: the self-computing next net and "Copy radio settings" inside the This-week card, with "On the air now" from 8 to 9 PM Tuesday.
- County Standard: the audience row (New to radio / Licensed ham / Member / Agency partner) under the hero, the ARES vs ACS table on About, the agency door, and the audience-grouped footer.
- Coverage, as a light static drawn map:
  - "Where we meet" (1121 W Gardner, next to the courthouse; drawn, not Google) in the planner's Meeting and Workshop tabs.
  - "Where we work" on How it works: the county outline, the downtown event area, NWS Spokane, hospitals as a hatched general area, and "Sensitive sites are never shown".
  - Coverage's interest form merges into "Tell us you're coming".
- Hi-Vis: "Here's what it takes" as four figures in Bricolage, answering "How much time does it take?" (on How it works) and beside Home's join steps. "Pick your post" becomes "Find your spot": photo-and-icon role cards with eligibility tags, on How it works.
- Plain Signal: the 0-3 path with How long / You need / one action and the ?from= deep link, as Home's "Joining, in four steps".
- When the Lines Go Down: the four-hop ICS-213 message path as a flat diagram with dusk-blue line icons (no scrollytelling, no night), labelled "A scenario", as "How a message moves" on How it works.
- The Relay: "What's that?" tap-to-define terms (a dotted dusk underline with an inline expansion) on How it works and About.
- Ops Board: the task-organised hub and the document library with a one-line "what it's for", on For Members.

### Section approach
#### Home

A warm, practical, agenda-led front door, no longer than about 5,000px.

1. Header: the seal, the nav, the red "Join the team".
2. Split hero. On the left: H1 "From Bloomsday to blackouts, we keep Spokane County connected." (Bricolage 700), a capability sentence naming SCEM, the invitation "Come say hello on Tuesday night. Visitors are welcome to check in.", the primary "Plan your first visit" and the text secondary to the join steps. On the right: the parade-operator photo (badge blurred), overlapped by the white This-week card from data.js.
3. The audience row: four links, each with a one-line hint.
4. The "Three ways to visit" planner: door tabs, date chips, minute-by-minute, at a glance, add to calendar and the optional RSVP.
5. "What we do": four photo cards (the county comm room and trailer; hospitals; public events; weather and Winlink), each linking to How it works.
6. "Joining, in four steps" (0-3, with ?from=) and the "Here's what it takes" figures.
7. "Training with partners this fall" (a data row), the served-agencies line and "Contact the emergency coordinator".
8. Footer, grouped by audience.

#### How it works

"A month with the team": practical, calendar-led and photo-rich, with a sticky "On this page" chip bar at the top on desktop.

- The weekly net: the minute-by-minute of a net night, the radio settings, and how visitors check in.
- When the county calls: activation in five steps, then the alert levels explained in four columns. No current level.
- How a message moves: the flat four-hop ICS-213 diagram, labelled "A scenario"; Winlink through W7GBU-10 and the biweekly assignment; ICS-309 logs.
- Exercises and training from data.js: SET, the WSDOT exercise, the State COMMEX, workshops and meetings.
- Public service: Bloomsday, the Lilac Torchlight Parade and Valleyfest.
- Where we work: the static drawn county map.
- Find your spot: roles with eligibility tags.
- Your first month after joining, with relative dates.
- Questions newcomers ask: first person, as <details>.

#### About ARES & ACS

"Everything, in plain words": a handbook page.

- A left sticky TOC, "On this page", lists the H2s and reveals the H3s of the active section; the active item sits in a dusk-tint pill.
- Body: Atkinson Hyperlegible at 19px/1.7 in a 70ch column, with Bricolage H2 and H3.
- Every H2 opens with a "The short version" box (two sentences, dusk-tint), then the detail.
- Tables: ARES vs ACS, the membership levels, and the training requirements as a checklist. "Good to know" callouts in pine-tint.
- Also the W7GBU story with the big call-sign block, "For agencies and partners" (the SCEM relationship, agreements and contact route), and the FAQ as <details>.
- Mobile: a sticky "Jump to section" disclosure button under the header.

#### For Members

The member desk.

- Navigation: a sticky horizontal sub-nav bar directly under the header, with a pill link for every sub-section from ia.md and groups.io marked with an external icon. At 390px it scrolls sideways, with edge fades and the active pill scrolled into view.
- Hub:
  - the This-week card at full width: the next net with settings and Copy, net control for the next four Tuesdays with open slots, the simplex and GMRS weeks, the Winlink assignment, and the next meeting, workshop and exercise
  - a "Find a document" search box that submits to documents.html?q=
  - "Most used" tiles, then the sub-section cards
- Documents: search, category chips and list rows (icon, title, what it's for, format, updated date, source badge).
- Exercises: an agenda list with date tabs (the Multnomah pattern) that expands to details (what, where, what to bring, how to sign up), then past exercises.

### Design tokens

**Palette**

- Dusk blue #2F4E7E: headings, primary buttons, links (7.9:1 on cloud; white on it 8.4:1). Dusk-deep #1E3557: the This-week card's header band, the footer, hover.
- Dusk-tint #E3EAF4: the "short version" boxes and the active TOC pill (ink on it 12.9:1). Dusk-line #B9C7DB: card borders and dashed photo frames.
- Pine #2E5B4F: secondary buttons and "Good to know" accents (white on it 7.7:1). Pine-tint #E4EFEA: callouts.
- Seal red #C62128: the header's "Join the team" only (white on it 5.8:1). Seal-deep #A21A20: hover.
- Lamp #F2C14E: the "Tonight" and "On the air now" state on dusk-deep (7.4:1), and the focus ring on dark.
- Cloud #F5F8FC: page. White #FFFFFF: cards. Ink #1B2433: text. Ink-2 #465163: secondary (7.5:1). Line #D6DFEB.
- Peach #F2B48C and peach-tint are removed.

**Type**

- Bricolage Grotesque:
  - display 700-800 at opsz 96, wdth 90; H1 at clamp(2.5rem, 5vw, 4.25rem)/1.02 with -0.02em letter-spacing
  - H2 700 at clamp(1.75rem, 3vw, 2.5rem); H3 700 at 1.25rem
- Atkinson Hyperlegible: body 400/700 at 18px/1.65, About at 19px/1.7, UI at 16px. Keep its slashed zero for frequencies and call signs; use tabular-nums where supported.

**Space and shape**

- 8px base: 8, 16, 24, 32, 48, 64, 88. Wrap 1200px with clamp(16px, 4vw, 40px) gutters. About column 70ch.
- Radius: cards and photos 12px, the This-week card 14px, buttons 999px, inputs 8px. The door tabs keep their 28px top corners (the signature shape).

**Imagery**

- Warm, natural-colour real photos with a 12px radius and captions that describe the activity. The parade operator (badge blurred) is the hero photo. The mast team, the go-box, the bumper station and the comm trailer (small) appear in "What we do".
- Icons: 1.75px dusk line icons on a 24px grid (headphones, meeting, tools, door, calendar, radio).
- The static drawn county locator map in dusk, pine and cloud.
- PHOTO NEEDED frames: dusk-tint fill, a 2px dashed dusk-line border, a camera icon and the caption.

Focus: on light, a 3px #1E3557 outline with a 2px white offset; on dark, lamp #F2C14E.

Motion: one moment. Choosing a visit door slides and fades the planner panel in 200ms. The "On the air now" dot pulses only while the net is live, and never under reduced motion.
