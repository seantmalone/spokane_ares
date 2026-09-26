# Homepage concept specs: Spokane County ARES-ACS redesign

Art direction for 12 homepage concepts, written 2026-09-25. Sources: `design/content-brief.md` (facts and copy), `design/inspiration.md` (the 44-site survey) and `design/assets/` (photos and marks). Each concept is a separate bet on what gets a visitor to learn more and then sign up. They differ in tone, layout, color, type and funnel together; none is a recolor of another.

Suggested build location: `design/concepts/<slug>/index.html`, one self-contained page per concept, with a desktop screenshot at 1440 px and a mobile one at 390 px.

---

## Rules every concept follows

**Brand and content**
- Use the name "Spokane County ARES-ACS". "ARES/RACES" appears only as history. Never expand ACS as "Auxiliary Communications Service". Use "Auxiliary Communications" or just "ACS".
- Every homepage shows these five things somewhere:
  1. The current seal (`Spokane_County_ARES-ACS_logo_slightly_smaller_550x550.png`, at 200 px or less).
  2. A persistent "Join the team" or "Start volunteering" action.
  3. The Tuesday net: 8:00 PM, W7GBU, 147.300 MHz, +600 kHz, 100 Hz tone.
  4. One plain sentence naming Spokane County Emergency Management as the primary served agency.
  5. The meeting address in the footer: 1121 W. Gardner Ave, Spokane, WA 99260.
- Hard content rules from brief §11. Use no invented member counts, founding year, hours, testimonials, event names or frequencies.
  - Never show the net at 10 PM.
  - Show the Third Thursday meeting start time as "time to be confirmed", or give it a VERIFY tag.
  - Don't describe anyone as a "first responder", and don't claim 24/7 availability.
  - Don't put names or call signs on photos. Crop or blur the name badge in the parade-desk photo.
  - Use partner names as text only. The only exceptions are the county EM seal and the ARRL ARES emblem, each marked "with permission".
  - Leave the website hack unmentioned.
- Where a stats strip or quote would normally go, use an evidenced commitment number instead (1 net a month, 1 meeting a quarter, 1 field operation a year, a 72-hour go-kit). Otherwise use a visibly labeled placeholder such as "[member voice to be collected]".
- Leadership appears as role plus call sign, marked VERIFY. Don't use photos for it.

**Design hygiene** (these are the tells that make a page look generated)
- Write in sentence case throughout. Don't put tracked all-caps labels above headings. Don't join metadata with middle dots. Don't add "→" to button text.
- Monospace appears only where a concept calls for it for frequencies and call signs (Concepts 5, 8, 10 and 11). Every other concept uses the body face's tabular figures.
- Use numbered markers only for the join path and other true sequences.
- Keep motion to a single moment per page, plus responses to user actions. Respect `prefers-reduced-motion`.
- Build to a baseline: responsive down to 390 px, visible keyboard focus, WCAG AA contrast, and fonts loaded from Google Fonts.

**Assets**
- The hero photo is `IMG_6574.jpg` (tailgate go-box), the only high-resolution image. The 659 px photos (mast team, parade desk, tower sunset) need duotone, grain, or a size of 660 px or less.
- Never use `textLogoACS_dash.png`. Use `DEM_map.png` as a stand-in only.

---

## 1. County Standard
`county-standard`: civic-institutional

- **Thesis.** Credibility is what converts. If the site reads like a well-run public service, both audiences trust it. That means plain words, the sponsor named in the first line, and a clear door for each audience. Agencies trust it, and newcomers feel safe taking a first step.
- **Aesthetic.** Light and orderly, with the rigor of a US-government design system, warmed by real photography.
  - Palette:
    - seal navy #1D2F7B (header band, headline block)
    - signal red #C62128 (primary CTA only)
    - county amber #F4A51C (status, focus ring; from the county EM seal)
    - mist #EEF2F8 (panels)
    - ink #15203B
    - white #FFFFFF
  - Type: Public Sans throughout (800 for display, 400/600 for body). Frequencies use `tnum` figures, not mono.
  - Motif: the county outline from the seal, redrawn as a small SVG shape and used as list markers and section anchors. This echoes NSW SES's pixel squares.
  - Photo: the mast-team photo in a wide crop, with a navy headline block overlapping its lower-left edge.
- **Layout.** Full-bleed photo hero, then a router bar and stacked civic sections:
  1. A thin official strip: "A volunteer communications unit supporting Spokane County Emergency Management", with a "Members" link on the right.
  2. Header: seal, wordmark, and nav (About, What we do, Join, Calendar, Members), plus a red "Join the team" button.
  3. Hero photo (480 px tall) with the overlapping navy block. H1: "Trained volunteers. Backup communications. Ready when it counts." Below it, the supporting subline.
  4. A four-door router band: I'm a licensed ham / I'm not licensed yet / I'm a member / I represent an agency.
  5. "What we are": the plain-English explanation, then an ARES vs ACS comparison table (who can join, what you can do, requirements, who activates you).
  6. "What we do": six services in a two-column icon list.
  7. An alert-level strip (Green / Yellow / Orange / Red).
  8. The four-step path (0 to 3).
  9. An agenda list of upcoming events: Sept 29 net (NZ2S, simplex), Oct 3 SET, Oct 10 workshop, and Oct 15 Third Thursday (time VERIFY).
  10. "Who we serve", as captioned text.
  11. A footer grouped by audience.
- **Signup funnel.** The router sends each audience to its own landing page, which has one CTA:
  - licensed → "Register for ARES"
  - unlicensed → "Find a license class"
  - agency → "Contact the emergency coordinator"

  The ARES/ACS table clears up the biggest confusion before anyone reaches the path. The path section repeats "Join the team", and the header button is always visible.
- **Member utility.** Moderate. Members reach their hub through the utility-strip link and router door 3. The agenda shows the next net and its net control. A footer column links net scripts, ICS forms and task books.
- **Signature interaction.** The four-door router re-emphasizes the page in place. Choosing "Not licensed yet" highlights step 0 and opens a license-class panel inline. Choosing "Licensed" highlights the ARES column of the table. The choice is remembered on the device.
- **References.** ses.nsw.gov.au/volunteer, gocivilairpatrol.com, designsystem.digital.gov, wastateares.org, seattleacs.org
- **Risk.** It could read as a competent government template with little emotional pull. Recruits would respect it but might not feel moved to act.

## 2. The Relay
`the-relay`: editorial magazine

- **Thesis.** People sign up for a story they believe in. Make the case for emergency radio in Spokane as a well-edited magazine feature, and explain every acronym in the margin. A curious newcomer then reads to the end and finds the next step waiting.
- **Aesthetic.** Calm and literate, like a quarterly from a serious institution.
  - Palette:
    - ACS aubergine ink #2A2150 (text and headlines; from the ACS seal)
    - lilac #8E74C0 (links and sidenote markers; a nod to the "Lilac City" and the Lilac Torchlight Parade)
    - cool paper #F6F7F9 (deliberately not cream)
    - fog #DDE2EA (panels)
    - signal red #C62128 (the single Join CTA)
  - Type: Newsreader, using optical sizes, for display (72–96 px, light) and body (20 px / 1.6). Hanken Grotesk for nav, captions and buttons.
  - Imagery: the tailgate photo as a magazine cover with white margins, not full-bleed. The tower-sunset photo appears mid-feature as an aubergine/lilac duotone spread.
- **Layout.** A single centered column (680 px of text) with a right-hand sidenote rail on desktop:
  1. Masthead: a small seal, the name set like a nameplate, slim nav, and a "Join" text button.
  2. Cover: "From Bloomsday to blackouts, we keep Spokane County connected.", a deck, and the cover photo with a factual caption.
  3. Feature, "Why a county keeps radio volunteers". It uses the plan's "supplement existing systems" line and "Amateur radio… can function even if other modes of communication cannot". It opens with a drop cap and has margin notes.
  4. The Dean Bula pull quote, verbatim.
  5. An "In this issue" contents grid: What we do / How to join / Get licensed / The W7GBU story / Calendar.
  6. A "This week on the air" column: Tuesday net, Third Thursday, Second Saturday.
  7. A closing "Your next step" box with three sized options.
  8. A colophon footer with the address, partners as text, and a Members link.
- **Signup funnel.** Mission-led reading. Sidenote links such as "How do I get licensed?" pull readers into the funnel mid-article. The end-of-feature box is a three-rung commitment ladder:
  1. Listen Tuesday (tonight-sized)
  2. Come to a meeting (an evening)
  3. Register for ARES (the commitment)

  "Join" stays in the sticky masthead.
- **Member utility.** Light by design. The masthead has a Members link and the week column shows nets and meetings. Resources live behind the Members link.
- **Signature interaction.** A margin glossary. Every acronym (ARES, ACS, RACES, W7GBU, net control, Winlink, ICS-213) gets a lilac underline, and its plain-English definition sits in the margin beside that line, in the style of Tufte sidenotes. On mobile, tapping the term expands the definition inline.
- **References.** multnomahares.org, redmond-ares.org, wastateares.org, edwardtufte.github.io/tufte-css, sf-fire.org/nert
- **Risk.** It asks for reading time. Skimmers, and members looking for a frequency, may bounce. It will drift into a blog unless it is edited ruthlessly.

## 3. Hi-Vis
`hi-vis`: bold athletic recruitment

- **Thesis.** Treat joining as a challenge worth taking. Bold, direct recruiting copy and an honest, numbered commitment turn "maybe someday" into a concrete goal: earn your county volunteer ID.
- **Aesthetic.** A loud but disciplined recruiting poster for a serious team.
  - Palette:
    - safety yellow #FFD000 (the vest yellow; big blocks, primary button)
    - deep navy #13205C (text, duotone shadows)
    - reflective silver #C9CFD6 (the vest's reflective stripes, used as dividers and a progress bar)
    - white #FFFFFF
    - signal red #D0202A (alerts only)
  - Type: Big Shoulders Display 800/900 for poster headlines and huge numerals; Barlow 400/600 for body.
  - Photos: navy/yellow duotone with coarse halftone at hero scale. This masks the low resolution of the mast-team and parade photos.
- **Layout.** Full-bleed duotone hero, then poster-scale blocks:
  1. Header with a yellow "Start volunteering" button.
  2. Hero: the mast team photo. H1, huge and flush left: "Make a difference with a radio in your hand." Subline: the Dean Bula quote. Buttons: "Start volunteering" and "Not licensed yet? Get on the air" (outlined).
  3. A reflective-stripe band showing the commitment as four giant numerals: 1 net check-in a month, 1 meeting a quarter, 1 field operation a year, a 72-hour go-kit.
  4. "Pick your post": role tiles for net control, field/shelter operator, comm room and EOC, hospital station, Winlink and digital, public-event comms, and SKYWARN spotter.
  5. A horizontal stepper, "Four steps to your county volunteer ID":
     1. License. The class length is VERIFY.
     2. ARES registration (one evening).
     3. Orientation (4th Tuesday, 6 PM).
     4. IS-100/700 plus a six-month activity period, then the ID card.
  6. A yellow block, "Tuesday is practice", with the net details and "Listen this Tuesday".
  7. Partners as text, then the footer.
- **Signup funnel.** A challenge-led stepper. The honest commitment numbers up front answer "how much time?" before anyone asks. Each step's button opens the exact form or link. The ID card is the visible reward, and the CTA appears three times.
- **Member utility.** Minimal: a header Members link and the net block.
- **Signature interaction.** A reflective-stripe progress stepper. The silver bands fill yellow as the visitor ticks "I have this" for each step. It ends on a stylized card, clearly labeled as an illustration and not a real county ID, reading "Your next step: …". The state is stored on the device.
- **References.** teamrubiconusa.org/volunteer, makemeafirefighter.org, nsp.org, crisistextline.org/volunteer, seattlemountainrescue.org/join
- **Risk.** The sports-recruiting energy can feel overheated for an auxiliary radio unit and can imply first-responder status. The copy has to keep saying "auxiliary support".

## 4. Open Door
`open-door`: warm community

- **Thesis.** The easiest yes is "come visit". Lead with the three open doors that need no commitment: listen to Tuesday's net, drop into a meeting, or join a Saturday workshop. Make the first visit feel planned and welcoming, and membership follows belonging.
- **Aesthetic.** Bright and neighborly, but tidy enough for an agency.
  - Palette:
    - dusk blue #2F4E7E (from the sunset photo; headings and primary)
    - horizon peach #F2B48C (warm highlight panels)
    - pine #2E5B4F (secondary buttons)
    - cloud #F5F8FC (a cool white page)
    - ink #1B2433
    - seal red #C62128 (the Join button)
  - Type: Bricolage Grotesque 700 for display; Atkinson Hyperlegible for body. Atkinson was designed for low-vision readers, which suits an older membership.
  - Photos:
    - the parade operator (badge blurred), warm and human
    - KD7GHZ and Don as small thumbnails in a "scenes from the team" strip
    - the sunset softly behind the footer

    A 12 px radius applies to photos only.
- **Layout.** Split-screen hero, then an agenda-led scroll:
  1. Header with "Join the team".
  2. The hero split. On the left: "Come say hello on Tuesday night. Visitors are welcome to check in.", with "Plan your first visit" and "Join the team". On the right, a "This week" stack:
     - Tue Sept 29 net: 8 PM, 147.300, NZ2S, simplex week
     - Oct 10 Second Saturday, 9–noon
     - Oct 15 Third Thursday (time VERIFY)
     - Oct 27 New Member Orientation, 6 PM
  3. "Three ways to visit". Each covers what happens, how long it takes, and what to bring.
  4. "Questions newcomers ask", in first person. For example: "Do I need my own radio?", "I'm not licensed. Can I still come?", "How much time does it take?" and "Do I have to be an expert?"
  5. The W7GBU and 0730 Bunch story, with the Dean Bula quote.
  6. "Your first month after joining": net, orientation, Groups.io, task book.
  7. "Where we meet": the map stand-in, 1121 W Gardner, next to the courthouse.
  8. Footer.
- **Signup funnel.** Community-led. Visit, then RSVP (name, email, call sign optional, which visit), then orientation, then registration. Every section ends in "Plan your first visit". Join stays in the header for people who are ready.
- **Member utility.** Moderate. The "This week" stack doubles as a member quick reference, and the header has a Members link.
- **Signature interaction.** A first-visit planner. Pick Listen, Meeting or Workshop, and it shows:
  - the next date
  - a minute-by-minute of what happens (for example, orientation at 6 PM, then a comm room tour, then the 8 PM net)
  - where to go
  - add to calendar
  - an optional one-form "Tell us you're coming"
- **References.** sf-fire.org/nert, bamru.org, seattleacs.org, wa7dem.info, shorelineacs.org
- **Risk.** The warmth can read as a hobby club. Agencies may miss the operational seriousness unless the served-agency section stays prominent.

## 5. Tuesday, 2000 Hours
`tuesday-2000`: tactical ops center, dark (member utility)

- **Thesis.** The Tuesday net is the heartbeat and the best recruiting tool, so make the live schedule the hero. A countdown to 8 PM and one-tap radio settings let a curious ham listen tonight, and members get their weekly essentials at a glance.
- **Aesthetic.** A night-ops console: calm, precise, and readable in a dark room.
  - Palette:
    - night navy #0C1730 (blue-leaning background, not black)
    - panel #15244A
    - radio-LCD amber #FFB547 (readouts, primary CTA)
    - the condition colors, strictly for status: green #3FB27F, yellow #F2C94C, orange #F2994A, red #E5484D
    - text #E8EDF7, muted #93A1BF
  - Type:
    - IBM Plex Sans Condensed for headings
    - IBM Plex Sans for body
    - IBM Plex Mono for frequencies, call signs and times (this is where mono is earned)
  - Imagery: the tower-sunset photo as a navy duotone backdrop, and the comm-trailer still as a small duotone inset.
- **Layout.** A dashboard hero, then a briefing:
  1. A status strip, "Condition Green: normal operations", with a legend link and a Members link.
  2. Header with an amber "Join the team".
  3. A three-panel console:
     - Next net: Tue 29 Sept 20:00 countdown, W7GBU 147.300 MHz, +600 kHz, 100 Hz, net control NZ2S, simplex week.
     - Listen tonight: visitors check in after members, with "Add to calendar" and "How to check in as a visitor".
     - Coming up: SET Oct 3, workshop Oct 10, Third Thursday Oct 15 (time VERIFY), orientation Oct 27.
  4. A briefing, "Every Tuesday night we practice. The day it's real, we're ready.", with a check-in order diagram: officials, then members in suffix groups (A–F, G–M, N–Z), then visitors, then simplex and cross-band stations.
  5. An alert-condition ladder showing what members do at each level.
  6. The join path as console rows.
  7. A net-control roster (Oct 6 open, Oct 13 AE7RJ, Oct 20 WA7LNC, Oct 27 open) with "Take an open slot" for members.
  8. An other-nets table (0730 Bunch, GMRS, WSEN), marked VERIFY.
  9. Footer.
- **Signup funnel.** Urgency from the clock: "tonight" is never more than seven days away. The sequence is listen, check in as a visitor, attend orientation (the 4th Tuesday, the same evening as a net), then register. The primary CTAs are "Check in as a visitor" and "Join the team".
- **Member utility.** Heavy. The next-net console, the roster with open slots, the preamble, simplex and GMRS scripts, the current condition and the other nets all sit at or near the fold.
- **Signature interaction.** A live net console:
  - A countdown to Tuesday 20:00 Pacific. It switches to "On the air now" from 20:00 to 21:00.
  - One-tap "Copy radio settings" and an .ics download.
  - This week's net control, drawn from the roster.
- **References.** scc-ares-races.org, sacramentoares.org, teamrubiconusa.org, fcares.org, wa7dem.info
- **Risk.** The dark console can feel insider-only and intimidating to unlicensed visitors, and it becomes ops-center cosplay if the chrome is overdone. A status that nobody maintains is worse than no status.

## 6. Plain Signal
`plain-signal`: calm Swiss minimal

- **Thesis.** The fastest route to sign-up is a path you can see all at once. Make the homepage itself the joining sequence, 0 to 3, in the International Typographic Style. Nothing is decorative; every element is either a step or a fact.
- **Aesthetic.** Calm, exact and confident.
  - Palette:
    - white #FFFFFF
    - pure black #000000 (type)
    - seal red #D0202A (step numerals and the one CTA)
    - steel #6B7280 (secondary text)
    - rule grey #E5E7EB
    - seal navy #24348A (links)
  - Type: Archivo only. Giant numerals are semi-condensed on the width axis at 800; display is 800 at 64 px; body is 400 at 18 px. Frequencies use tabular Archivo.
  - One photo only: the tailgate go-box in a strict 3:2 grid box.
  - Alignment: flush left, ragged right, on a 12-column grid, with numerals hanging in the first two columns.
- **Layout.** The whole page is a stepper:
  1. A minimal header: the wordmark, text nav, and a red "Join" link. The seal appears small, in the footer.
  2. H1: "When other systems go down, Spokane County still has a voice." Below it, the two-line definition and a segmented control: "Where are you starting from?"
  3. Four step rows: 0 Get licensed, 1 Join ARES, 2 Get connected, 3 Qualify for ACS (optional). Each row gives a giant red numeral, what the step is, how long it takes, what you need, and one action. Step 3 expands to its six sub-steps.
  4. A three-column facts table:
     - Net: Tue 20:00, 147.300, +600, 100 Hz
     - Meetings: Third Thursday; Second Saturday 09:00–12:00; orientation on the 4th Tuesday at 18:00
     - Location
  5. Served agencies as a plain list, then the footer.
- **Signup funnel.** The stepper. The selector (Not licensed / Licensed / ARES member / ACS member) collapses the steps you've finished and puts your next action first. There is exactly one action per step.
- **Member utility.** Moderate. The facts table gives net and meeting data instantly, and there is a Members link.
- **Signature interaction.** The "Where are you starting from?" control reorders and collapses the step rows so your next action comes first. Numerals for completed steps dim. The state can be deep-linked (for example `?from=licensed`), so the net can read out a link and emails can share one.
- **References.** gov.uk (start pages), crisistextline.org/volunteer, ses.nsw.gov.au/volunteer, seattleacs.org/join, hamstudy.org
- **Risk.** It is austere. With almost no people imagery, it may feel cold and fail to create the emotional pull that gets people to volunteer.

## 7. In the Field
`in-the-field`: documentary photojournalism

- **Thesis.** Show the work, not the gear. A photo-led sequence of real members in real conditions (fog, parades, a comm trailer) proves the team is active. It lets visitors picture themselves in the frame, and every scene links to the role they would play there.
- **Aesthetic.** Documentary, honest and grounded.
  - Photos are converted to black and white with fine grain, which unifies the uneven 2017 photos. White type sits on them over a strong scrim.
  - Palette:
    - true black #000000 (photo bands)
    - white #FFFFFF
    - press red #C8102E (a 4 px caption rule and the CTA)
    - graphite #3A3F47 (secondary text)
    - fog #E9ECEF (panels)
  - Type: Libre Franklin 800 for headlines (the newspaper gothic); Source Serif 4 italic for captions.
  - Captions describe the activity and never name anyone, for example "Raising an antenna mast in freezing fog during a field exercise."
  - Empty frames are labeled "Photo to commission: EOC comm room", "…Bloomsday" and so on.
- **Layout.** A full-bleed photo stack, told scene by scene:
  1. A header that starts transparent and turns solid white.
  2. Hero: the mast team in black and white. H1: "Behind every message is a volunteer with a radio." CTA: "See where you'd fit".
  3. Scene: exercises (mast photo). Role: field station operator.
  4. Scene: public service (parade desk, badge cropped). Bloomsday and the Lilac Torchlight Parade. Role: event comms.
  5. Scene: inside the comm trailer (van still, small). Role: comm room and EOC.
  6. Scene: anywhere with a battery (tailgate go-box, then the Don bumper station). Role: Winlink and digital.
  7. Scene: severe weather (photo to commission). Role: SKYWARN.
  8. A role catalog of seven roles, each with what you'd do and the training needed.
  9. The join path and CTA.
  10. Partners and footer.
- **Signup funnel.** Proof-led. Each scene ends with "This role: …", which leads to the role catalog, then the path, then "Join the team". There are no testimonials; a labeled slot reads "Member voice: to be collected".
- **Member utility.** Low: a slim Members link, and the next net in the footer bar.
- **Signature interaction.** "Find yourself in the frame". Tapping a photo reveals labeled hotspots, such as the net-control desk, the go-box or the mast. Each hotspot names the role and its training and links to the role page. A keyboard-accessible list version sits beside each photo.
- **References.** teamrubiconusa.org, sf-fire.org/nert, cfa.vic.gov.au/volunteers-and-careers, nvfc.org, seattlemountainrescue.org
- **Risk.** It depends entirely on photography. The current set is small, dated and low-resolution, so without a commissioned shoot the concept looks thin.

## 8. Confirmed Contact
`confirmed-contact`: retro radio / QSL-card heritage

- **Thesis.** Ham radio already has a ritual for "you're in": the QSL card that confirms a contact. Borrow it. The site celebrates the W7GBU heritage and turns each first step into a card you collect, so the path feels like joining a tradition. The steps are listening, checking in, registering and becoming ACS-ready.
- **Aesthetic.** Crisp two-color printed ephemera, not grungy.
  - Palette:
    - card white #FFFFFF on a seal-navy desk #1E2E6E
    - signal red #D0202A (call-sign print, stamps)
    - airmail blue #9DB7E0 (secondary print)
    - typewriter ink #1C2238
    - postmark red #E36A6F
  - Type:
    - Zilla Slab 700 for big call-sign lettering and headlines
    - Courier Prime for the typed log fields (date, time, MHz, mode, tone)
    - Source Sans 3 for body
  - Photos appear as the picture side of a QSL card, with white borders. The tower sunset gets a halftone.
  - Cards sit on a strict grid. Only the hero card tilts, by 1.5°.
- **Layout.** A card mosaic on the navy desk:
  1. Header in white on navy, with a red "Join the team".
  2. Hero, in two parts:
     - A giant QSL card. "W7GBU" appears in huge slab letters, then "Spokane County ARES-ACS". Its log fields read Freq 147.300 MHz, Offset +600 kHz, Tone 100 Hz, Tuesdays 2000 local, Mode FM. The remarks field reads "Visitors welcome."
     - Beside the card, the H1 "Your first contact with W7GBU is one Tuesday away.", the Dean Bula quote, and the CTAs.
  3. The W7GBU story card: a memorial call for Ira "Dean" Bula, and the 7:30 Bunch.
  4. "Cards to collect", which is the path: Heard (listen), Worked (check in as a visitor), Registered (ARES), ACS-ready. The back of each card says what it takes.
  5. What we do, as picture-side cards with captions.
  6. Nets and meetings as a typed log sheet.
  7. Served agencies as a printed list.
  8. A footer with a postmark-style address block.
- **Signup funnel.** Gamified collecting. "Collect your first card: listen this Tuesday" leads to a visitor check-in, then registration. A stack of collected cards shows progress.
- **Member utility.** Moderate: the log-sheet schedule, the net-control roster, and a Members link.
- **Signature interaction.** A QSL generator. Enter your call sign, or choose "Not licensed yet", to get a personalized digital QSL from W7GBU. Your next step is typed into the Remarks field. You can download or share it, and net control could later send one to real first-time visitors.
- **References.** lcares.org (call sign as brand), arrl.org/ares, hamgallery.com/qsl, wa7dem.info, qrz.com
- **Risk.** Nostalgia reads as a hobby club and as an inside joke to non-hams and agencies. The emergency role has to stay explicit on every card.

## 9. Field Guide
`field-guide`: illustrated, playful

- **Thesis.** Most people have no mental picture of emergency radio. Friendly technical illustrations and a two-minute "Is this for me?" quiz give them one, then send each person to the right first step. This matters most for people who aren't licensed yet.
- **Aesthetic.** An illustrated field guide to the Inland Northwest. Flat, precise vector scenes show a basalt ridge, ponderosa pines, and waves hopping from a handheld to the W7GBU repeater to the EOC. They carry field-guide annotations. The colors are saturated but grown-up.
  - Palette:
    - ponderosa green #1F5A46 (primary)
    - Palouse wheat #E8B94A (highlights, quiz selections)
    - sky #8CC1E8 (illustration skies, panels)
    - basalt #2C2F36 (text)
    - river teal #2A8C9A (links)
    - paper #FBFCFD (cool white)
  - Type: Lexend for headings and body. Caveat is used only for annotations inside illustrations.
- **Layout.** A bento grid:
  1. Header.
  2. The hero bento:
     - The large tile is the illustration "How a message gets through when phones are down", with the H1 "When phones go quiet, radio still gets the message through."
     - Small tiles: "Take the 2-minute quiz", "Tuesday net" and "Get licensed".
  3. The quiz, inline.
  4. An illustrated bento of the six services.
  5. "Licensing in plain English". It uses the driver's-test analogy (the questions are published in advance), lists the exam options, links HamStudy, and notes the FCC's $35 fee.
  6. The join path drawn as a trail with four trail markers.
  7. Partners and footer.
- **Signup funnel.** A quiz pathfinder with five tap-answer questions, based on Marin's self-qualifying list:
  - Are you licensed?
  - Do you want to help locally?
  - Are you comfortable with a background check?
  - Are you drawn to digital and tech?
  - Are you free on weekdays or weekends?

  Each result gives one route with one CTA and an optional "Email me my path":
  - Start with a license class
  - Register for ARES now
  - ARES + ACS
  - SKYWARN first
- **Member utility.** Low: a Tuesday-net tile and a Members link.
- **Signature interaction.** The "Is this for me?" quiz. Each answer gets an illustrated reaction. The result card shows your route, the time it takes, and the one button to press.
- **References.** marinraces.org/wp, hamstudy.org, lacdcs.org, weather.gov/skywarn, redcross.org/volunteer/become-a-volunteer.html
- **Risk.** Illustration can tip into childish and undercut agency credibility. Custom illustration is also a real cost, and it has to stay consistent over time.

## 10. Ops Board
`ops-board`: data-forward live dashboard, with a sidebar (member utility)

- **Thesis.** A site that members open every week stays alive. Build the homepage as a light, calm operations board (schedule, roster, status, task-book progress) with a permanent recruiting rail. Visitors see an active, organized unit, and members never hunt for a form.
- **Aesthetic.** The clarity of a bright control room, like a modern status page.
  - Palette:
    - board grey #F1F3F6
    - panel white #FFFFFF
    - seal navy #24348A (sidebar, headings)
    - data blue #2F6FDE (links)
    - status only: green #1F9D55, yellow #E0B000, orange #E8750A, red #D0202A
    - ink navy #1A2233
  - Type: Red Hat Display for headings, Red Hat Text for body, and Red Hat Mono for frequencies, call signs and times.
  - Imagery is minimal: the seal, and one small field photo in the recruiting rail.
- **Layout.** A persistent left sidebar with a dashboard main area:
  - The sidebar is organized by task: This week, Nets, Calendar, Forms, Task books, Go-kits, Status codes, Groups.io. Below them sits a separate "New here?" block with Join.
  - The main area:
    1. A status pill ("Condition Green", with a last-updated time) and search.
    2. Widgets:
       - Next net: Tue 29 Sept 20:00, 147.300 +600 100 Hz, NZ2S, simplex.
       - Net-control roster: Oct 6 open, 13 AE7RJ, 20 WA7LNC, 27 open. Includes "Take an open slot".
       - Upcoming: WSDOT exercise Sept 26–27, SET Oct 3, workshop Oct 10, SHARES Oct 17, State COMMEX Oct 24.
    3. A task-book tracker, and the Winlink assignment for this fortnight.
    4. The document library, where each item has a one-line description: net preamble, ICS-213, 213RR, 214, 309, Radiogram, ACS FOG, Standing Order 1b.
  - The right-hand public rail has "Not a member yet?", the plain-English sentence, a mini four-step path, "Join the team" and "Listen Tuesday".
- **Signup funnel.** Utility as social proof. Visitors see a living unit: a real schedule, open net-control slots, named exercises. The recruiting rail is always visible. "Preview what joining involves" opens the task-book tracker in read-only mode.
- **Member utility.** The most of any concept, because the member hub is the homepage. Nets, frequencies, forms and the go-kit list are cached for offline use (a PWA).
- **Signature interaction.** A task-book tracker: the 2024 ACS Task Book as an interactive checklist with a progress ring, stored on the device. Each item links to its FEMA or ARRL course and shows "what's next". Prospects can preview it.
- **References.** scc-ares-races.org, cfa.vic.gov.au/volunteers-and-careers, shorelineacs.org, mra.org, githubstatus.com
- **Risk.** Dense insider data can overwhelm newcomers and look like an intranet, and public recruiting gets crowded out. The status and roster must be kept current, or the site looks abandoned.

## 11. Coverage
`coverage`: map-first, geographic

- **Thesis.** Local is the hook. Put Spokane County itself on the homepage as a calm interactive map of where the team works: the DEM building, the hospitals it supports, event routes, and the repeater's reach. Visitors see their own county and neighbors, and agencies see the footprint.
- **Aesthetic.** Cartographic and Inland Northwest.
  - Palette:
    - map white #F4F6F8
    - contour taupe #B8B09F (hairline contours)
    - Spokane River blue #2E7DAF (water, links)
    - forest #3C6E47 (terrain)
    - seal navy #24348A (UI, labels)
    - signal red #D0202A (pins, CTA)
  - Type: Overpass, which descends from Highway Gothic and carries road-sign DNA. Overpass Mono for coordinates and frequencies.
  - The map is a vector county drawing with light hillshade. The tailgate overlook photo appears in a "field station" pin card.
- **Layout.** A full-viewport map hero with an overlay panel:
  1. The header, over the map.
  2. The map fills the screen. A left overlay card has:
     - the H1 "Your county. Your neighbors. On the air."
     - the subline
     - "Join the team" and "Listen Tuesday"

     Layer toggles:
     - Where we meet (1121 W Gardner)
     - Hospitals we support (a generic marker only)
     - Public events (Bloomsday and Lilac Torchlight Parade routes, illustrative)
     - Repeater W7GBU (illustrative coverage, VERIFY)
     - NWS Spokane (SKYWARN)
  3. "What happens at each pin" cards.
  4. "Is my home in reach?" ZIP lookup.
  5. The join path, a calendar strip, and captioned partners.
  6. Footer.
- **Signup funnel.** Place-led. You enter a ZIP or town and get a personal card:
  - your meeting place and rough distance
  - your net frequency
  - public events near you
  - one role suggestion

  The card ends in "Join the team". Proximity makes the ask personal, much as Civil Air Patrol's squadron finder does.
- **Member utility.** Moderate. A members-only layer covers staging and deployment sites and is hidden from the public. The next net and a Members link are also shown.
- **Signature interaction.** A layered county map. Each pin opens a card that says what volunteers do there and which role it needs, and the ZIP lookup personalizes the view.
- **References.** coloradoares.org, stxd14ares.org, mra.org, gocivilairpatrol.com, makemeafirefighter.org
- **Risk.** Map accuracy and security. Hospital stations and equipment caches must not be public. The coverage shading is unverified, and a heavy map can be slow and fiddly on phones.

## 12. When the Lines Go Down
`lines-down`: story, scrollytelling

- **Thesis.** Most people never picture the failure. Walk them through one plausible bad night: an ice storm takes out power and cell service. Show step by step how a message still gets from a shelter to the EOC by radio. Then reveal that the people who make it work practice every Tuesday, and that the reader could be one of them.
- **Aesthetic.** Cinematic but sober. The palette moves from night to dawn as you scroll:
  - night #0E1A33, then storm slate #33415C, then dawn peach #F3C29B, then daylight #FFFFFF
  - the message path in signal red #D0202A, with an amber highlight #FFC857

  Type: Crimson Pro for narration (a large 28 px serif); Schibsted Grotesk for UI, diagram labels and CTAs. Imagery:
  - the 1920 dusk sky as the opening frame
  - SVG diagrams for the message path
  - the tailgate go-box at dawn

  The scenario is always labeled "A scenario", never presented as history.
- **Layout.** Narrative steps on the left, with a sticky diagram on the right:
  1. Full-screen dusk: "It's 2 AM. The power's out, and so is cell service." A "Skip the story" link sits top right.
  2. The normal network greys out.
  3. A shelter needs to send an ICS-213 message to the EOC.
  4. A licensed volunteer with a go-kit sends it through W7GBU on 147.300.
  5. The EOC comm room logs it and relays it to the hospital net.
  6. Dawn: "Radio still works. Because people practice." The team photo, then "Every Tuesday night we practice."
  7. "Could you be the one on the radio?": the four-step path, "Not licensed yet?" and "Listen this Tuesday".
  8. Alert levels, partners and footer.
- **Signup funnel.** Scenario-led. Stakes first, then capability, then the invitation, with the CTA placed where motivation peaks. A persistent "Join the team" pill and the skip link serve impatient visitors.
- **Member utility.** Low on the homepage: a "Member tools" header link, and net details in the footer.
- **Signature interaction.** A sticky message-path diagram. As you scroll, an ICS-213 travels from a shelter handheld through the W7GBU repeater to the EOC and on to the hospital net. Each hop is labeled with the volunteer role that makes it happen. With reduced motion, it becomes a static stepped diagram.
- **References.** redmond-ares.org, arrl.org/ares, seattleacs.org (the "keeps talking when phones & power go out" video), pudding.cool, wastateares.org
- **Risk.** A fear-based dramatization can feel manipulative or overstate what the team can do. Scrollytelling is also costly to build and maintain in WordPress, and it frustrates people who want quick facts.

---

## Variability matrix

| # | Concept | Tone | Layout paradigm | Color | Type | Funnel |
|---|---|---|---|---|---|---|
| 1 | County Standard | civic-institutional | photo hero with overlap block and router bar | light navy/red/amber | Public Sans | persona router |
| 2 | The Relay | editorial magazine | single column with sidenote rail | light aubergine/lilac | Newsreader serif + Hanken | mission-led reading |
| 3 | Hi-Vis | bold recruitment | full-bleed duotone poster, horizontal stepper | saturated high-contrast yellow/navy | Big Shoulders condensed + Barlow | challenge stepper, ID reward |
| 4 | Open Door | warm community | split-screen hero, agenda | muted warm dusk blue/peach | Bricolage + Atkinson Hyperlegible | visit first, RSVP |
| 5 | Tuesday, 2000 Hours | tactical ops, dark | console dashboard hero | dark navy + amber LCD | IBM Plex Condensed/Mono | listen tonight (clock urgency) |
| 6 | Plain Signal | Swiss minimal | typographic stepper page | white/black/red | Archivo (width axis) | where-are-you stepper |
| 7 | In the Field | documentary | full-bleed photo stack, scenes | monochrome + press red | Libre Franklin + Source Serif captions | proof and role-led |
| 8 | Confirmed Contact | QSL heritage | card mosaic on a desk | two-color print on navy | Zilla Slab + Courier Prime | collectible cards |
| 9 | Field Guide | illustrated | bento grid | saturated Palouse/pine | Lexend + Caveat annotations | quiz pathfinder |
| 10 | Ops Board | data dashboard | sidebar + widgets + recruit rail | light grey + status colors | Red Hat Display/Text/Mono | utility as social proof |
| 11 | Coverage | map-first | full-viewport map + overlay | cartographic neutrals | Overpass + Overpass Mono | place-led ZIP lookup |
| 12 | When the Lines Go Down | scrollytelling | sticky-diagram narrative | night to dawn gradient | Crimson Pro + Schibsted Grotesk | scenario-led |

The concepts that lean hard into member utility are 5 (Tuesday, 2000 Hours) and 10 (Ops Board). Concepts 4, 6, 8 and 11 carry moderate member utility. Each proposal headline from the brief appears in one concept only; concepts 4, 7, 8, 9, 10, 11 and 12 use new headline copy that makes no factual claims.
