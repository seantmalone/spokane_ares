# Round 2 site review: Spokane County ARES-ACS

Reviewed 26 September 2026. Round 1 compared twelve homepage concepts. Round 2 takes the three leaders (Field Guide, When the Lines Go Down and Open Door) and grows each into a six-page site across the four sections the owner asked for. Each option keeps its leader's personality, fixes what the round-1 judges criticized and borrows the best ideas from the other concepts. All three use the same real content from `_shared/` and open straight from disk.

- Gallery to browse the options: `design/round2/index.html` (open it in a browser; every screenshot links to the live page)
- Option briefs: `design/round2/briefs.md`
- Sites: `design/round2/option-c-open-door/index.html`, `design/round2/option-a-field-guide/index.html`, `design/round2/option-b-lines-down/index.html`

**It's close.** The composites are 8.10, 8.09 and 8.06, so the ranking comes down to hundredths of a point. The judges split three ways: the prospective-volunteer and agency judges picked Figure One, the member and UX judges picked Open Channel, and the art director picked Carry the Message. Open Door was third in round 1; its successor now ranks first.

## The four sections every option builds

| # | Section | Files | Purpose |
|---:|---|---|---|
| 1 | Home | `index.html` | The newcomer's first step: high-level, draws people in, clear calls to action. |
| 2 | How it works | `how-it-works.html` | The organization, operations and what members actually do. |
| 3 | About ARES & ACS | `about.html` | The long, text-heavy reference, with a table of contents. |
| 4 | For Members | `members/*.html` | Hub, documents and exercises: public information members care about, with its own menu and fast navigation. |

## How the options were judged

| Judge | Lens | Top pick |
|---|---|---|
| Prospective volunteer | A newly licensed ham, or an unlicensed resident. Tasks: understand it in 10 seconds, find what members do, find how to join without a license, find where and when to visit. | Figure One |
| Served-agency partner | County emergency manager, hospital coordinator or Red Cross liaison. Tasks: judge credibility, find ARES vs ACS and the legal basis, decide whether to link from a county page. | Figure One |
| Current member | New this round. An active volunteer on a phone in the car or a laptop at home, timed by clicks: net control and settings, the net script, the ICS-213, the next exercise and what to bring, task-book requirements. | Open Channel |
| UX, accessibility and upkeep | Navigation and information scent, About's readability, mobile, contrast and keyboard, and how hard each option is for volunteers to build and keep current in WordPress or a static generator. | Open Channel |
| Art director | Visual craft, coherence across all six pages, distinctiveness, typography, photography and illustration, and fit for an emergency-services volunteer group. | Carry the Message |

Each judge rated every option from 1 to 10 on eight criteria (professional, engaging, ease of use, makes you want to learn more, makes you want to sign up, member efficiency, how well it addressed the round-1 feedback, and overall) and on each of the four sections. Every score is the mean of the five judges, rounded to one decimal. The composite is the mean of the eight criterion means and sets the rank.

## Scores

### By criterion

| Rank | Option | Professional | Engaging | Ease of use | Learn more | Sign up | Member efficiency | Feedback addressed | Overall | Composite |
|---:|---|---:|---:|---:|---:|---:|---:|---:|---:|---:|
| 1 | Open Channel (C, led by Open Door) | 7.6 | 7.2 | **8.7** | 7.5 | **8.5** | **8.4** | **9.0** | 7.9 | **8.10** |
| 2 | Figure One (A, led by Field Guide) | 7.5 | 8.2 | 7.5 | **9.0** | 8.4 | 7.9 | 8.1 | **8.1** | 8.09 |
| 3 | Carry the Message (B, led by When the Lines Go Down) | **8.5** | **9.0** | 6.6 | 8.8 | 7.9 | 7.5 | 8.7 | 7.5 | 8.06 |

Bold marks the best score in each column.

### By section

| Option | Home | How it works | About ARES & ACS | For Members |
|---|---:|---:|---:|---:|
| Open Channel | **8.0** | 8.0 | 8.3 | **8.3** |
| Figure One | 7.6 | **8.3** | **8.6** | 7.9 |
| Carry the Message | 7.9 | 7.6 | 7.9 | 7.7 |

### Each judge's overall score

| Option | Prospect | Agency | Member | UX | Art |
|---|---:|---:|---:|---:|---:|
| Open Channel | 8.5 | 8.0 | 8.6 (pick) | 8.0 (pick) | 6.5 |
| Figure One | 9.0 (pick) | 9.0 (pick) | 7.7 | 7.0 | 8.0 |
| Carry the Message | 7.5 | 7.0 | 7.5 | 7.0 | 8.5 (pick) |

### Current-member task tests

Timed from Home by the current-member judge; the phone-menu row adds the UX judge's test.

| Task | Open Channel | Figure One | Carry the Message |
|---|---|---|---|
| Net control and repeater settings | Success, 0 clicks | Success, 0 clicks | Success, 0 clicks from any page (desktop) |
| Net preamble (script) | Success, 2 clicks | Success, 3 clicks | Success, 3 clicks |
| ICS-213 form | Success, 2 clicks | Success, 3 clicks (2 via footer) | Success, 3 clicks (2 via footer) |
| Next exercise and what to bring | Mostly, 1 click | Success, 2 clicks | Success, 2 clicks |
| Task-book requirements | Partial, 2–3 clicks | Partial, 2–3 clicks | Partial, 2–3 clicks |
| Members menu on a phone | Works, 2 taps to any section | Weak, swipe and tap | Weak, swipe and tap |

### By the numbers

| Measure | Brief target | Open Channel | Figure One | Carry the Message |
|---|---|---|---|---|
| Home length, desktop / phone | ≤ 5,500px desktop | 5,214 / 11,661px | 6,100 / 13,141px | 4,954 / ~9,400px |
| Join path starts, desktop / phone | ≤ 1,800px / 3 screens | 1,062 / 2,121px | 1,895 / 2,839px | 1,631 / 2,209px |
| Agency door on Home (desktop) |  | ~900px, first screen | ~1,090px | ~3,900px, bottom band only |
| How it works length, desktop / phone |  | 15,400 / ~23,300px | 13,600 / ~27,800px | 19,582 / ~31,550px |
| About length, desktop / phone |  | 35,800 / ~52,200px | 29,300 / ~42,800px | ~32,000 / ~48,800px |
| About contents on desktop |  | 31 grouped links; scrolls inside | 12 numbered sections; only the active one unfolds | 26 grouped entries; scrolls inside |
| Members menu on a phone | ia.md §4 disclosure | “Members menu” disclosure, all 11 grouped | Tab scroller, ~1.5 of 11 visible | Sideways scroller, ~1–2.5 of 11 visible |
| Cold-load link to about.html#for-agencies | Lands on the heading | Overshoots ~960px (desktop), ~235px (phone) | Lands exactly | Overshoots 464px (desktop), ~1,287px (phone) |

## The options

### 1. Open Channel (option C): composite 8.10

Led by round-1 **Open Door (04)**, which ranked 3rd in round 1 (composite 7.83). Top pick of the current member and ux, accessibility and upkeep judges.

*The easiest yes is “come visit”. Belonging leads to joining.* Keeps Open Door's warmth (real photos, the This-week card, the “Three ways to visit” planner) but now opens on capability and names SCEM in the first sentence. Bricolage Grotesque headings, Atkinson Hyperlegible text. Dusk blue and pine; seal red only on the header’s Join button.

- **Open this site:** [`option-c-open-door/index.html`](option-c-open-door/index.html)
- **Pages:** [Home](option-c-open-door/index.html) · [How it works](option-c-open-door/how-it-works.html) · [About ARES & ACS](option-c-open-door/about.html) · [Members hub](option-c-open-door/members/index.html) · [Documents](option-c-open-door/members/documents.html) · [Exercises](option-c-open-door/members/exercises.html)
- **Screenshots:** [Home](option-c-open-door/shots/home-desktop-fold.png) ([phone](option-c-open-door/shots/home-mobile.png)) · [How it works](option-c-open-door/shots/how-desktop-fold.png) ([phone](option-c-open-door/shots/how-mobile.png)) · [About ARES & ACS](option-c-open-door/shots/about-desktop-fold.png) ([phone](option-c-open-door/shots/about-mobile.png)) · [Members hub](option-c-open-door/shots/members-desktop-fold.png) ([phone](option-c-open-door/shots/members-mobile.png)) · [Documents](option-c-open-door/shots/documents-desktop-fold.png) ([phone](option-c-open-door/shots/documents-mobile.png)) · [Exercises](option-c-open-door/shots/exercises-desktop-fold.png) ([phone](option-c-open-door/shots/exercises-mobile.png))
- **Judges' overall:** Prospect 8.5, Agency 8.0, Member 8.6, UX 8.0, Art 6.5

![Open Channel Home, first screen at 1440 by 900](option-c-open-door/shots/home-desktop-fold.png)

#### How it addressed round-1 feedback

| Round-1 judges said | What changed |
|---|---|
| The hero was about socializing; an emergency manager landed on a club greeting. | Capability H1 (“From Bloomsday to blackouts, we keep Spokane County connected.”), the next sentence names SCEM, the invitation moves below it, an Agency partner door sits one row down and the footer is grouped by audience. |
| The peach card looked dated and off-brand. | Peach is gone. The This-week card is white with a dusk-blue header band; lamp yellow marks only the live net. |
| More visible “verify” chips than any other concept. | None visible. 248 uncertain facts carry data-verify, ?verify=1 outlines them, and one footer line says it is a mockup. |
| A public “Condition Green” badge. | Deleted. The four readiness levels are explained once, with no current level. |
| Weekly content needed a single data source. | Everything date-driven (the card, net control, rota, agenda, library) renders from data.js, and past items drop off. |
| The RSVP needed a mail handler, and two hero buttons competed. | Add to calendar (.ics) is the planner's main action and the RSVP is optional. One filled primary button; red appears only on the header Join. |
| About 10,800px long and repetitive. | Home is 5,214px. Newcomer questions, alert levels and “first month” moved to How it works; the W7GBU story moved to About. |
| Superseded rules in the copy. | Check-in order is officials, members, visitors; no simplex frequency; no 10 PM net; “Apply to join” groups.io; ALERT Spokane on Regroup; 2024 ACS Task Book. |

Condensed from `option-c-open-door/README.md`. Feedback addressed: 9.0.

#### Current-member task results

| Task | Result | What happened |
|---|---|---|
| Net control and repeater settings | Success, 0 clicks | The Home This-week card shows net control, W7GBU 147.300 MHz, offset and tone above the fold; about one screen of scroll on a phone. The hub adds net control for the next four Tuesdays. |
| Net preamble (script) | Success, 2 clicks | Home card “Net script” to documents.html#net-preamble-weekly, then Download DOCX. The hub has “Weekly script” under Copy radio settings. |
| ICS-213 form | Success, 2 clicks | Home card “Forms” to the ICS 213 row (official FEMA source). The hub also has a Popular “ICS-213” chip; ?q=213 returns 4 matches. |
| Next exercise and what to bring | Mostly, 1 click | Home card “Exercises” to Next up. The WSDOT card has Bring / Log / Forms / Sign up, but the SET (Oct 3) card has no Bring row, and the hub's “Coming up” shows only one exercise, so SET is missing on Sep 26. |
| Task-book requirements | Partial, 2–3 clicks | “Training & task books” is a dead “#”. You recover through the hub's “Staying current”, then “Full task-book levels”, or the Popular “ACS Task Book” chip. |
| Members menu on a phone | Works, 2 taps to any section | A sticky “Members menu: current page” disclosure; one tap lists all 11 sections under Operate / Prepare / Reference. The only one of the three that follows ia.md §4. |

#### Judges' notes

<details><summary><b>Prospective volunteer</b>: overall 8.5</summary>

**Must fix**

1. Make the hero This-week card newcomer-first ('Listen Tuesday 8 PM on 147.300, no license needed', plus the next meeting); move net control, offset/tone, the simplex note and the 'For members' links to the members hub
2. Add a short plain-English licensing block on Home, or send the 'New to radio' door to licensing as well as listening (consider A's quiz)
3. Replace the PHOTO NEEDED card that leads 'Find your spot' (Net control) and the one in Home's 'What we do' with a real photo or a drawn plate
4. Add one 'why it matters' moment on Home, such as B's static 'One message, four volunteers' diagram, so engagement isn't only logistics
5. Trim mobile Home (11,661px); change 'Tuesday net, 2000' on the members hub to '8:00 PM'

**Task results**

- (1) Understand it in 10 seconds: SUCCESS, 0 clicks. The capability H1, a sentence naming SCEM, 'Come say hello on Tuesday night. Visitors are welcome to check in.' and a real operator photo. Friction: the This-week card that fills the right half of the hero speaks to members (Net control NZ2S, +600 kHz, 100 Hz tone, the Fifth Tuesday simplex note, 'For members: Net script / Forms').
- (2) What members actually do: SUCCESS, 1 click. It is the best in the set. Home 'What we do' cards link to How it works, which has the 'A month with the team' October calendar, the net minute by minute ('Visitors: this is your cue'), 'Find your spot' (11 role cards with eligibility tags plus a 'Not sure yet? Drop into a workshop' card), 'Your first month after joining' with dated steps (your first Tuesday Sep 29, first workshop Oct 10, first Winlink assignment Oct 13, first meeting Oct 15, orientation) and 'Questions newcomers ask'.
- (3) Join if not licensed: SUCCESS, 1-2 clicks. The 'New to radio' door ('Listen in on Tuesday night. No license needed.') goes to the Listen tab. 'Ready to join? See the four steps' goes to #join at 1,062px, the earliest in the set. The 'Not licensed' chip marks step 0 'Your next step'. Licensing detail is only on about.html#licensing, and there is no quiz or plain-English licensing block on Home.
- (4) Meeting where and when: SUCCESS, 2 clicks. It is the best in the set. The primary hero CTA 'Plan your first visit' goes to the planner; the Meeting tab gives date chips (Thu Oct 15, Nov 19, Jan 21), arrive / training / meet the people / ask about joining, At a glance (how long, where, what to bring: nothing, license needed: no), Add to calendar and Google Calendar, a drawn map with Get directions, and an optional 'Tell us you're coming'.

**Strengths.** It makes the lowest-friction ask and gives the most practical answers to the questions a prospect actually has. 'Plan your first visit' as the single primary CTA, with the three-door planner, removes first-visit anxiety better than anything else. 'Your first month after joining' and 'Questions newcomers ask' ('I'm not licensed. Can I still come?', 'How much time does it take?') are exactly what someone on the fence needs. The join path is the earliest on Home (1,062px), with How long / You need and one action per step, plus the ARES/ACS mini-explainer and 'our ARES group welcomes you'. About's 'The short version' box on every H2 is the most skimmable long read. Every round-1 criticism is addressed: peach gone, Condition Green gone, zero visible chips, one primary CTA, Home cut to 5,214px. The members hub is efficient (search first, next four net controls, open slots, 'Most used') and its mobile 'Members menu' disclosure shows every section.

**Weaknesses.** It is the least stirring option. It explains the logistics well but not why the work matters. There is no message diagram on Home and no quiz. The hero This-week card puts member jargon at the top of the newcomer's first page. The unlicensed visitor has to go to About for licensing. On Home, 'What we do' includes a PHOTO NEEDED card and a line-drawing card, and on How it works the first 'Find your spot' card (Net control) is a PHOTO NEEDED placeholder, which weakens the pitch. The Bricolage condensed headlines are loud. Mobile Home is still 11,661px. On the members pages, the two-row desktop pill bar has uneven group gaps, and 'Tuesday net, 2000' is ambiguous.

</details>

<details><summary><b>Served-agency partner</b>: overall 8.0</summary>

**Must fix**

1. Fix cold-load anchor landing on about.html#for-agencies (~960px overshoot desktop) and #contact (~640px)
2. Replace the close-up headset hero photo with a wider operational shot (comm trailer / EOC exercise) and tone down the condensed display on agency-facing headings
3. Collapse the 26-item About TOC to H2 groups that expand (as A does) so it fits 900px without inner scroll
4. Add the county's steps (request, mission number) explicitly to 'Activation in five steps'
5. Show net control by call sign only in the rota; use one time format on members pages
6. Tidy the two-row members pill bar at 1280px+ (uneven group gaps)

**Task results**

- Task 1, credibility: pass. It is practical and trustworthy, and the best Home for agency discovery. The 'Agency partner' door is in the audience row at 894px (first screen), with 1,986px to it on mobile. 'Training with partners this fall' lists dated exercises with WSDOT, ARRL SET, USGS ShakeOut, State COMMEX and NWS SKYWARN Day. A closing 'Represent an agency or an event?' panel carries 'Contact the emergency coordinator'. How it works adds a 'When it was real' card for the August 2023 Gray/Oregon Road fires with the trailer photo. The tone holds it back: the Bricolage condensed display and the close-up headset hero photo read more community club than EOC partner.
- Task 2, ARES vs ACS and legal basis: 2 clicks (About, then 'Start with what you need: Agency partner', or the TOC 'Rules and partners' group). It has the 8-row side-by-side table. The legal basis opens with a 'short version' and then numbered four layers. 'For agencies and partners' is its own H2 and the richest agency copy in the set: the relationship with SCEM, ESF-2, what we bring, agreements.
- Task 3, link from a county page: yes, after one fix. A cold load of about.html#for-agencies overshoots by about 960px on desktop (the heading and short version scroll off; it lands near 'What we bring') and about 235px at 390. #contact overshoots by about 640px, so the ec@ row is off-screen. The 26-item sticky TOC needs an inner scroll at 900px.

**Strengths.** Every round-1 criticism from the agency judge is fixed. The capability H1 plus a sentence naming SCEM as the primary served agency replaced the club greeting. There is no status badge, zero visible chips, one primary CTA, and Home is 5,214px. It gives agencies the earliest door, the best live evidence of partner exercises on Home, and the real-deployment card on How it works. About's 'The short version' box under every H2 suits a busy coordinator, and the TOC grouping ('Rules and partners': legal basis, who we serve, for agencies, agreements) is the most logical. How it works has 'Activation in five steps' and the readiness levels explained with no current level. The members This-week card with the next four Tuesdays is useful.

**Weaknesses.** It is the least authoritative-looking of the three: heavy condensed display type, pill-everything UI, and a soft 659px close-up portrait as the hero. The cold-load deep-link overshoot hits exactly the link a county page would use. About is 35,800px with a flat 26-item TOC. The activation steps are written from the member's side and don't show the county's mission-number step as a distinct stage. The members desktop sub-nav wraps to two uneven rows. Members pages mix 24-hour times ('Tuesday net, 2000', '0900–1200'). Volunteer names appear in the rota.

</details>

<details><summary><b>Current member</b>: overall 8.6 (top pick)</summary>

**Must fix**

1. members/index.html This-week 'Coming up': show the next 2 exercises, not only F.upcoming()[0]. On 2026-09-26, SET Oct 3 is missing.
2. members/exercises.html Next up: add a 'Bring' row to the SET card ('A Winlink station (radio preferred; Telnet acceptable) and a camera or phone for photos'), as A and B have.
3. Point 'Training & task books' (pill bar and hub directory) at an interim target (members/index.html#requirements or about.html#acs-task-book) instead of a dead '#'.
4. Desktop pill bar: show the Operate/Prepare/Reference labels, or cut it to one row, so the 103px sticky bar scans faster.
5. Exercises agenda: open the next 1-2 items by default so 'what to bring' needs no extra tap.

**Task results**

- Start = Home.
- (1) Net control + repeater settings: SUCCESS, 0 clicks, 0 scroll on desktop. The Home hero This-week card shows 'Net control: NZ2S', 'W7GBU 147.300 MHz, +600 kHz, 100 Hz tone', Copy radio settings and Add to calendar above the fold. On a phone it is 0 clicks and about 1 screen of scroll. The hub adds 'Net control, next four Tuesdays' with open slots.
- (2) Net preamble: SUCCESS, 2 clicks. The Home card's 'For members: Net script' goes to documents.html#net-preamble-weekly, then 'Download DOCX'. From the hub, the 'Weekly script' link sits right under Copy radio settings.
- (3) ICS-213: SUCCESS, 2 clicks. Home card 'Forms' goes to documents.html#forms, then the ICS 213 row (Official: FEMA). The hub also has the Popular 'ICS-213' chip, and ?q=213 returns exactly 4 matches.
- (4) Next exercise + what to bring: MOSTLY. 1 click (Home card 'Exercises') reaches Next up, where the WSDOT card has Bring/Log/Forms/Sign up. Friction: the SET (Oct 3) card has no Bring row (the kit is buried in the collapsed 'Your 5 tasks'). The hub's This-week 'Coming up' shows only one exercise, so SET is missing on Sep 26 (site.js weekCard pushes only F.upcoming(...)[0]).
- (5) Task-book requirements: PARTIAL, 2-3 clicks. The obvious 'Training & task books' pill is a dead '#'. You recover by scrolling the hub to 'Staying current' and then 'Full task-book levels' (about.html#acs-task-book), or through the Popular 'ACS Task Book' chip.
- Phone nav: 'Members menu: current page' is a sticky disclosure. One tap shows all 11 items under Operate/Prepare/Reference labels, so any section is 2 taps away. This is the best mobile sub-nav of the three and the only one that follows ia.md §4.

**Strengths.** It gets a member to the answer fastest from the front door. The Home This-week card carries net control, settings, Copy and a member shortcut row (Net script / Forms / Exercises / This week in full), all above the fold. The mobile Members menu disclosure has group labels and a current-page label. The hub This-week card combines the next net, the next four Tuesdays of net control with open slots, and script links in one card, and 8 Most used tiles sit right under it. Documents has search, chips with counts, a 'Hide Coming back' toggle, a sticky 'What the labels mean' aside, and a 'Download DOCX' or 'Opens training.fema.gov' line on every row. Exercises has a two-up Next up, then an agenda with month tabs (with item counts) and type filters. The How it works October calendar and the 'Tune in' card also serve members. About's 'The short version' boxes are the easiest long read to skim.

**Weaknesses.** The desktop members pill bar wraps to two rows (103px sticky) with no visible group labels or hints, so it is harder to scan than A's rail. The hub's This-week 'Coming up' drops SET, the next future exercise, on the mockup date. The SET Next-up card has no 'Bring' row. Agenda rows are collapsed by default, so each detail costs an extra tap. 'Training & task books' is a dead '#' in the pill bar and the hub directory. The visual register is warm and civic but less distinctive than B.

</details>

<details><summary><b>UX, accessibility and upkeep</b>: overall 8.0 (top pick)</summary>

**Must fix**

1. Reduce Home's visit planner to a compact 'Come visit' card (Listen / Meeting / Workshop, each with a when-line and Add to calendar) and move the minute-by-minute timeline and RSVP to How it works. That gets mobile Home well under 11,661px.
2. About: collapse each TOC group except the active one (A's pattern) so the 31-link list needs no inner scroll, and narrow the text column to 75 characters a line or less (now about 86 at 19px).
3. Limit the slashed zero to frequencies and call signs: wrap those in a class that uses a slashed-zero face, and use a plain zero for dates and times.
4. Fold the large page scripts (How 12.6KB, Exercises 11KB) into named site.js widgets, and make the doc library either filter the static DOM or render from data.js, not both, so there is one source.
5. Add a signature explainer to Home: a compact static four-hop message diagram or Fig. 1 in 'What we do', linking to How it works.
6. Make the desktop members pill bar one row at 1280px and up (shorter labels or an overflow 'More'), or show the Operate / Prepare / Reference group labels so the two rows read as intentional.

**Task results**

- T1 newcomer to the join path: 1 click on 'Ready to join? See the four steps'. #join is at 1,062px on desktop, the earliest of the three, and at 2,121px on a phone (2.5 screens). The primary hero CTA is 'Plan your first visit' (the planner), and the red 'Join the team' is in the header. Home is 5,214px, inside the 5,500 cap.
- T2 agency: the 'Agency partner' audience tile sits right under the hero (about 945px) and leads to about.html#for-agencies, then Contact, so 2 clicks, with good scent.
- T3 member to the ICS-213 form: 2 clicks (For Members, then the Popular chip or Most used). Hub search submits to documents.html?q=.
- T4 member on a phone, Documents to Exercises: 2 taps. The sticky 'Members menu: Documents & downloads' disclosure opens to all 11 items grouped Operate / Prepare / Reference with the current item highlighted. This is the best result in the set.
- T5 reader to 'The legal basis': 1 click on desktop. The 31-link grouped TOC scrolls internally (868 of 1,436px) but follows the active item. On a phone it is 2 taps via the sticky 'Jump to section' bar, which shows the current section.
- T6: ?q=winlink gives 14, ?cat=forms gives 13, aria-live works, aria-pressed chips, a 'Hide Coming back' toggle, and every row states its destination ('Opens training.fema.gov'). Exercises: month tabs plus type filters plus expandable agenda rows make the fastest scan of the season.
- Keyboard: skip link first, 3px rings everywhere.
- Contrast: 0 failures across 3,800+ text nodes at both widths.
- JS off: all pages read; the header shows no dead menu button.

**Strengths.** The best-balanced information architecture for all four audiences. Home has a capability H1 naming SCEM, one filled primary CTA, the white This-week card (live next net, net control, Copy radio settings, Add to calendar, Coming up, member quick links) and an audience row with Agency partner directly under the hero. Members navigation is the most efficient. On desktop it is a sticky pill bar with every ia.md item and groups.io marked external. On a phone it is a 'Members menu: `<current page>`' disclosure listing all 11 items grouped, which the same markup delivers at both sizes and which is a plain list with JS off. The hub This-week card includes net control for the next four Tuesdays with open slots. The exercises agenda (month tabs with counts, type filters, expandable rows, 'Next up' cards) is the clearest scheduling view. How it works has a sticky 'On this page' chip bar with scroll-spy, and the month calendar turns into an agenda list at 390. On About, 'The short version' box opening every H2 is the best skim aid in the set for a text-heavy page, and the 'Start with what you need' router sits at the top. Its components (photo cards, pills, tables, `<details>`) map most directly onto WordPress core blocks for volunteer maintainers.

**Weaknesses.** Home carries the full 'Three ways to visit' planner (door tabs, date chips, minute-by-minute timeline, At a glance box, RSVP), which is heavy and app-like for a high-level first step, and mobile Home runs 11,661px. Home also lacks a signature explanatory visual: the message-path diagram lives only on How it works, so learn-more pull is weaker than A or B. The About TOC lists 31 links and needs an inner scrollbar on desktop. Body measure is about 86 characters a line at Atkinson 19px (target 60-75), and About is the longest page in the set (35,820px desktop, 52,193px at 390). The header is not sticky, and the desktop members pill bar takes two rows (103px sticky). Atkinson's default slashed zero shows on every date and time ('2Ø26', '8:ØØ PM', '9:ØØ AM'), not only frequencies and call signs, which reads oddly for newcomers. It has the heaviest JS (site.js 1,129 lines plus 12.6KB inline on How and 11KB on Exercises), and the doc library re-renders from data.js over a static copy, so two sources can drift.

</details>

<details><summary><b>Art director</b>: overall 6.5</summary>

**Must fix**

1. Restrict the slashed zero to frequencies and call signs: set dates, times and addresses in Bricolage or a plain-zero numeral style (or swap to a body face with a plain-zero option). As it stands, '2Ø26' and '1121 W Gardner… 9926Ø' read as code.
2. Commit to one signature visual device beyond the door tabs, for example adopting the message-path diagram as a real drawn plate, and upgrade the flat four-circle 'How a message moves' row, which undersells the organisation's core idea.
3. Unify the imagery language: don't mix a line drawing, a PHOTO NEEDED frame and photos in one 'What we do' row, and pick either large line illustrations or icon tiles for 'Find your spot'. Redraw the county map with real care or cut it.
4. Recompose the Home hero so the operator photo isn't mostly covered by the This-week card (a larger, better-resolved photo, or the card beside it rather than over it).
5. Vary About's rhythm: keep 'The short version' boxes for the major H2s only, or lighten them to a rule and a lead paragraph so a 35,800px page has hierarchy.
6. Slim the desktop members pill bar to one row (shorter labels or a 'More' overflow). Show net control by call sign only in the rota table, per the brief.

**Task results**

- 5-second fold test: pass on content, weaker on identity. The dusk Bricolage H1 'From Bloomsday to blackouts...', the SCEM sentence, the pine invitation line, the 'Plan your first visit' pill and the This-week card overlapping the parade-operator photo are all clear. It reads as a friendly civic or SaaS template rather than an emergency communications unit, and the only face on Home is half covered by the card.
- Coherence across 6 pages: good at the component level (dusk-deep header bands, pill chips, icon-in-circle tiles, 'The short version' boxes), but the imagery mixes three languages: photos, large line illustrations (the Hospitals card, the Find your spot house and storm drawings) and small icon cards, sometimes in the same row (Home 'What we do' puts a photo, a line drawing, a PHOTO NEEDED frame and a photo side by side).
- Imagery: the photos are warm and natural, which is right, but low-res and cramped. 'How a message moves' is a flat four-circle icon row, the weakest version of the signature diagram, and the 'Where we work' county map is crude.
- Long-read typography: Atkinson 19px in about 70ch reads well, but a 'The short version' box opens every H2, so the page becomes a repeating tint-box pattern. The big 'W7GBU' call-sign block is a memorable moment.
- Interactions: the door tabs switch the planner (Meeting shows date chips, a timeline, At a glance and the drawn locator map), and the mobile 'Members menu' disclosure works. There is no horizontal overflow at 390 on all 6 pages and no console errors.
- Friction: Atkinson's slashed zero appears in every date, time and address ('2Ø26', '9:ØØ AM', '9926Ø').

**Strengths.** It is the most practical and legible system, and it fixed every round-1 criticism cleanly: peach and status badges are gone, there are zero visible chips, the capability H1 names SCEM, and there is one filled CTA. The This-week card, white with a dusk-deep header band, is a well-made reusable component that serves Home and the members hub. The 'Three ways to visit' planner is the best-crafted interactive piece in the set: rounded-top door tabs, date chips, a minute-by-minute timeline, an At a glance box and a drawn locator map. The members section is efficient: search above the fold, a three-column This-week card with net control for the next four Tuesdays, eight Most used tiles, and document rows that state where each link opens ('Opens training.fema.gov'). The About handbook adds a big dusk 'W7GBU' call-sign type block and a clean history timeline. The mobile 'Members menu' disclosure is the most robust small-screen sub-nav of the three.

**Weaknesses.** It has the least distinctive art direction. Condensed heavy Bricolage in dusk blue, pill buttons and icon-in-a-circle cards are a widely used modern civic/SaaS kit, and nothing would be remembered a day later except the door tabs. The Atkinson slashed zero, lovely in '147.300' and 'NZ2S', turns every date, time and street address into code-looking text and adds visual noise across all pages (the exercises agenda is full of 'Ø9ØØ–12ØØ'). Illustration and photo languages are unresolved: line drawings, icon tiles, photos and PHOTO NEEDED frames mix within single rows, and the county map and four-hop message row look provisional next to A's and B's diagrams. The hero composition hides the one real face behind the card. On desktop the members two-row sticky pill bar (about 103px) is visually heavy. About's repeated tint boxes flatten the hierarchy of a 35,800px page.

</details>

### 2. Figure One (option A): composite 8.09

Led by round-1 **Field Guide (09)**, which ranked 1st in round 1 (composite 8.21). Top pick of the prospective volunteer and served-agency partner judges.

*Most people have no mental picture of emergency radio. Give them one.* Labelled figures, like plates in a field guide, do the explaining; around them the site stays calm and plain, then offers one clear first step for each kind of visitor. Lexend throughout. Pine for actions, wheat for highlights, sky for figure skies and seal navy as the structural colour; flat Inland Northwest scenery from a reusable symbol kit.

- **Open this site:** [`option-a-field-guide/index.html`](option-a-field-guide/index.html)
- **Pages:** [Home](option-a-field-guide/index.html) · [How it works](option-a-field-guide/how-it-works.html) · [About ARES & ACS](option-a-field-guide/about.html) · [Members hub](option-a-field-guide/members/index.html) · [Documents](option-a-field-guide/members/documents.html) · [Exercises](option-a-field-guide/members/exercises.html)
- **Screenshots:** [Home](option-a-field-guide/shots/home-desktop-fold.png) ([phone](option-a-field-guide/shots/home-mobile.png)) · [How it works](option-a-field-guide/shots/how-desktop-fold.png) ([phone](option-a-field-guide/shots/how-mobile.png)) · [About ARES & ACS](option-a-field-guide/shots/about-desktop-fold.png) ([phone](option-a-field-guide/shots/about-mobile.png)) · [Members hub](option-a-field-guide/shots/members-desktop-fold.png) ([phone](option-a-field-guide/shots/members-mobile.png)) · [Documents](option-a-field-guide/shots/documents-desktop-fold.png) ([phone](option-a-field-guide/shots/documents-mobile.png)) · [Exercises](option-a-field-guide/shots/exercises-desktop-fold.png) ([phone](option-a-field-guide/shots/exercises-mobile.png))
- **Judges' overall:** Prospect 9.0, Agency 9.0, Member 7.7, UX 7.0, Art 8.0

![Figure One Home, first screen at 1440 by 900](option-a-field-guide/shots/home-desktop-fold.png)

#### How it addressed round-1 feedback

| Round-1 judges said | What changed |
|---|---|
| Caveat handwriting, rounded cards and cartoon figures read as edtech, too soft for a unit that works inside EOCs. | Caveat is gone. Lexend labels with leader lines and numbered callouts, radii cut to 10/8/6px, faceless figures in hi-vis vests, seal navy as the structural colour and plain declarative copy. |
| Real members appeared only as small taped polaroids. | Real photos run as captioned plates at native size (the parade operator's badge is blurred). Missing shots are PHOTO NEEDED frames with a shot list. |
| Four cards crowded the hero, and the quiz appeared twice. | The hero is Fig. 1, the H1, the capability sentence and two buttons. The Tuesday tile became a slim This-week strip. The quiz appears once. |
| No agency contact path. | An Agency partner door on Home (RCW 38.52, state mission numbers, background checks), a navy “For agencies and partners” section on About and an Agency partners column in the footer. |
| Member tasks were thin. | A full For Members section with its own menu, a this-week overview, a searchable library and an exercises page. |
| Custom SVG is hard for volunteers to extend. | A documented kit of about 25 symbols and 20 icons composed with `<use>`; DESIGN.md walks through building a new figure. |
| Kept, because all four judges praised them. | Fig. 1, the quiz and its four routes, and “Licensing in plain English”. The builder flags that Home still runs 6,100px against a 5,500px guideline. |

Condensed from `option-a-field-guide/README.md`. Feedback addressed: 8.1.

#### Current-member task results

| Task | Result | What happened |
|---|---|---|
| Net control and repeater settings | Success, 0 clicks | The This-week strip sits right under the Fig. 1 hero (about 100px of scroll on desktop, about 1.3 screens on a phone). The hub repeats it with Copy / Add, the Fifth Tuesday flag and alternates. |
| Net preamble (script) | Success, 3 clicks | For Members, then “Scripts: weekly” or the Most used “Net preamble” tile, then the interim copy on ag7qp.com. No script link on the Home strip. |
| ICS-213 form | Success, 3 clicks (2 via footer) | For Members, then the Popular “ICS-213” chip or Most used tile, then the FEMA link. The footer “Forms” link makes it 2; ?q=213 shows 4 of 74. |
| Next exercise and what to bring | Success, 2 clicks | For Members, then “What to bring and how to sign up”. Best of the three: every plate has When / Bring / Log / Forms to carry / Assignments, and SET has its own Bring row. |
| Task-book requirements | Partial, 2–3 clicks | The rail item “Training & task books” is a dead “#”. Recover through the hub's “Staying current”, “Full task-book levels” or the Popular “ACS Task Book” chip. |
| Members menu on a phone | Weak, swipe and tap | A sticky horizontal tab bar shows about 1.5 of 11 tabs at 390px, so members swipe blind to find Forms or Documents. (On desktop, its left rail with icons and hints is the best members menu of the three.) |

#### Judges' notes

<details><summary><b>Prospective volunteer</b>: overall 9.0 (top pick)</summary>

**Must fix**

1. Shorten Home, especially on mobile (13,141px): move 'Three ways to visit' up beside the trail or fold it into the This-week strip, and trim 'What the team does' to 3-4 tiles
2. Give the Third Thursday meeting a what-happens list, what to bring and Add to calendar (borrow C's Meeting tab)
3. How it works: lead with Fig. 2 (the message scenario) or the roles, and move the Radio settings box (offset/tone/alternate) lower
4. Show net control by call sign only on members/index.html (the hub card and the rota show 'Jeff Banke')
5. Add a 'Your first month after joining' block (C's pattern) so an unlicensed prospect sees what happens after step 0

**Task results**

- (1) Understand it in 10 seconds: SUCCESS, 0 clicks. It is the best in the set. The first viewport at 1440 and at 390 holds the Fig. 1 plate (handheld volunteer, W7GBU repeater, county EOC), the H1, a subline naming SCEM, NWS, hospitals and events, and a 'Fig. 1 step by step' caption.
- (2) What members actually do: SUCCESS, 0-1 clicks. On Home, 'Where are you starting?' and 'What the team does' (6 illustrated tiles at about 2,939px) link to anchors on How it works. There, 'Pick your post' has 8 illustrated role cards with Any member / ACS / No-license tags and an 'On the way' line. Friction: How it works opens with a Radio settings box (offset, tone, alternate 146.880) before a newcomer knows why they would need it.
- (3) Join if not licensed: SUCCESS, 1 click. It is the best in the set. The 'Not licensed yet' door goes to index.html?from=unlicensed#join, and post 0 shows 'Start here'. 'Licensing in plain English' sits on Home itself: the driver's-test analogy, HamStudy, classes, the $35 fee, where to take the exam, and SKYWARN before you're licensed. I tested the quiz: 'Not yet' leads to 'Start with a license class' with How long, First step, Along the way, 'Copy a link to my path', a mini trail and 'Or listen in first'.
- (4) Meeting where and when: SUCCESS, 1 click, but it sits deep. The This-week strip link 'Plan a first visit' goes to #visit, which is at 3,909px on desktop and 9,151px on a phone. There it says 'Third Thursdays, evenings. Visitors are welcome. Next: Thu, Oct 15' with a drawn locator map (Fig. 4), the address and Get directions. It has no add-to-calendar for the meeting and doesn't say what happens at one.

**Strengths.** It is the clearest explainer for someone with no mental picture of emergency radio. Fig. 1 does the work in seconds, and the Fig. 2 'Following one message' plate on How it works, labelled as a scenario, shows a trained person at each hop. It is the only option that fully serves the unlicensed resident on Home: the four-door router, the working 5-question quiz with a routed result, the 0-3 trail with ?from= highlighting, and 'Licensing in plain English' in place. 'Here's what it takes' (1 net a month, 1 meeting a quarter, 1 field operation a year, 2 kits) beside the trail is an honest commitment statement. The tone problem from round 1 is solved: Caveat is gone, seal navy gives structure, the radii are tighter, and the photos run as real captioned plates. About's 'Start where you are' box and its margin glossary help a curious newcomer. The members left rail with icons and one-line hints makes every section scannable.

**Weaknesses.** Home is too long for a first step. It is 6,100px at 1440 against a 5,500px cap and 13,141px at 390, with about 10 modules, and 'Three ways to visit' is buried second-to-last. How it works (13,600px) is dense, and its first plate talks in radio settings. Meeting info is thinner than C's: no what-to-expect, no what-to-bring, no calendar button. The faceless vest figures still lean slightly toward an explainer or edtech feel, but this is acceptable now. On the members pages, the hub and rota show 'NZ2S Jeff Banke'. The name comes from _shared, but the brief says net control is shown by call sign only. The members tab bar on phones hides most sub-sections off-screen.

</details>

<details><summary><b>Served-agency partner</b>: overall 9.0 (top pick)</summary>

**Must fix**

1. Cut Home toward ~5,500px (desktop 6,100, mobile 13,141): fold 'Three ways to visit' into one line and condense licensing so the agency door and trail sit higher
2. Show net control by call sign only on the members hub and rota (currently NZ2S Jeff Banke, Bob Peterson, Gordon Grove, Randall Jones)
3. Make figure numbering unique site-wide (Home Fig. 3 and About Fig. 3 are different drawings)
4. Surface the August 2023 Gray/Oregon Road fires deployment on Home beside the Agency partner door as proof of real service
5. Relabel the footer 'Affiliations' seal row (e.g. 'Served agencies') or get SCEM's written OK for the implied affiliation

**Task results**

- Task 1, credibility across six pages: pass. The chrome is consistent (seal, navy structural rule, pine Join). There is no status widget, no visible verify chips, and only role addresses. The footer carries 'A volunteer group, not an emergency service. In an emergency, call 911.' Copy is plain and declarative throughout. The Caveat and cartoon register is gone, though the flat hi-vis silhouettes and the wheat quiz band still feel a little explainer-like.
- Task 2, ARES vs ACS and legal basis: 2 clicks (nav About, then index '2 Two hats, one team'). That section has the Fig. 3 layers diagram (ACS layer over ARES layer over county ground), the 5-row 'How to tell ARES and ACS apart' table with a navy head, and a 'Legal footing' line directly under it (RCW 38.52, WAC 118-04, FCC 97.407). Section 3 opens with four-layer cards (47 CFR 97, 97.407, RCW 38.52/WAC 118-04, the 2021 county CEMP), 'In plain English' callouts, and eCFR and Legislature links. Home's Agency partner door already states RCW 38.52, state mission numbers and background checks in two sentences, 1,277px down.
- Task 3, link from a county page: yes. On a cold first load about.html#for-agencies lands exactly (heading at 16px on desktop, 68px at 390). 'Contact the emergency coordinator' lands on the role-address table showing ec@. Friction: Home is 6,100px on desktop and 13,141px on mobile, and figure numbering restarts on each page.

**Strengths.** The best agency answer in the set. About reads as a reference manual: 12 numbered sections, and a sticky index that only expands the current section, so at 539px it fits a 900px screen. A 'Start where you are' router at the top includes 'From an agency or event?'. A navy 'For agencies and partners' panel has rows for Government agencies, Event organizers and Other partners, plus an 'Email the emergency coordinator' button. How it works Fig. 3 'When the county calls' has the only activation track that shows the county's steps explicitly: request, mission number, activation message, check-in, assignments, release, then review within 72 hours. It is followed by the readiness levels, explained and never shown as a live status. Fig. 2 is a static, clearly labelled ICS-213 scenario. It is the only option whose deep links land correctly on a cold load. A commissioner would understand Fig. 1 at a glance.

**Weaknesses.** Home runs long and recruiting-heavy: router, quiz, trail, 'what the team does', licensing, three ways to visit, and from the field. The friendly flat illustrations are still one step softer than an EOC partner would pick. Figure numbers collide across pages: Home Fig. 3 is the driver's-test drawing, About Fig. 3 is the layers diagram. The members hub prints volunteer names next to call signs ('NZ2S Jeff Banke' plus three others in the rota), against the call-sign-only rule. A PHOTO NEEDED frame sits on Home. The footer labels the SCEM seal as an 'Affiliation', which implies a formal relationship that needs county sign-off.

</details>

<details><summary><b>Current member</b>: overall 7.7</summary>

**Must fix**

1. Replace the 390px horizontal members tab bar with the ia.md §4 'Members menu: current page' disclosure (or add an 'All sections' button). Today only about 1.5 of 11 tabs are visible.
2. Add member shortcuts (Net script, Forms, Exercises) to the Home This-week strip next to 'For Members'.
3. Point 'Training & task books' at an interim target (members/index.html#requirements or about.html#acs-task-book) instead of '#'.
4. Show the call sign only in the This-week net card. Keep names to the rota table (ia.md §8.5).
5. Trim Home toward 5,500px: fold 'Three ways to visit' into one line, as the README proposes.

**Task results**

- Start = Home.
- (1) Net control + settings: SUCCESS, 0 clicks. It is in the This-week strip right under the Fig. 1 hero (about 100px of scroll on desktop; about 1.3 screens on a phone, after the hero and the Fig. 1 step list). The hub fold repeats it with Copy/Add, the Fifth Tuesday flag and alternates.
- (2) Net preamble: SUCCESS, 3 clicks: For Members, then 'Scripts: weekly' in This week (or the Most used 'Net preamble' tile), then 'Current copy on ag7qp.com (interim)'. The Home strip has no direct script link.
- (3) ICS-213: SUCCESS, 3 clicks: For Members, then the Popular 'ICS-213' chip or Most used tile, then the FEMA link. It is 2 clicks through the footer 'Forms'. ?q=213 correctly shows '4 of 74'.
- (4) Next exercise + what to bring: SUCCESS, 2 clicks: For Members, then 'What to bring and how to sign up' (exercises.html#next-up). This is the best answer of the three: each plate has When/Bring/Log/Forms to carry/Assignments, and SET has its own Bring row and task list.
- (5) Task-book requirements: PARTIAL, 2-3 clicks. The rail item 'Training & task books' is a dead '#'. You recover through the hub 'Staying current' (scroll), 'Full task-book levels' or 'Download the ACS Task Book', or the Popular 'ACS Task Book' chip.
- Phone nav: a sticky horizontal tab bar that shows only about 1.5 of 11 tabs at 390px ('Overview (this week)' | 'Nets & net cont…'). A member in the car has to swipe blind to find Forms or Documents.

**Strengths.** It has the best desktop members menu. The sticky left index rail has icons, group labels and a one-line hint under each item ('What's next and what to bring', 'Task books, courses, sign-off'), so members know what is behind each link before they click. The hub fold is the densest of the three: search with Popular chips, then This week with next net plus net control, settings, Copy/Add, weekly and simplex script links, the next exercise with a 'What to bring' link, the next meeting, the Winlink assignment, open slots, and primary/alternate/NOAA frequencies. The exercise plates set the standard for 'what to bring'. The How it works figure plates are very clear, and About's numbered index unfolds only the current section.

**Weaknesses.** The mobile members tab bar hides about 85% of the menu and ignores ia.md's 'Members menu' disclosure. Home is the longest and busiest (about 6,100px: strip, four doors, quiz, trail, team tiles, licensing, visits, field). Members must pass it and there is no member shortcut row. The illustration register still reads a bit soft next to B and C. The This-week card shows 'NZ2S Jeff Banke', a personal name outside the rota table, which ia.md §8.5 limits to the rota. 'Training & task books' is a dead '#'.

</details>

<details><summary><b>UX, accessibility and upkeep</b>: overall 7.0</summary>

**Must fix**

1. Cut Home to 5,500px or less: turn 'Three ways to visit' into one line linking to about.html#meet, and reduce 'Licensing in plain English' to a teaser that links to About section 9. #join must start at 1,800px or less on desktop (now 1,895) and within 3 screens on a phone (now 2,839px, 3.4 screens).
2. Replace the phone members tab scroller (about 1.5 of 12 items visible) with a sticky 'Members menu: `<current page>`' disclosure that lists all 11 sections grouped as Operate / Prepare / Reference.
3. Fix the 1px overflow on nav.mnav (272 vs 271px, overflow:auto) that renders a horizontal scrollbar under the members rail at 1440.
4. In the members This-week card, show net control by call sign only ('NZ2S'); keep 'Jeff Banke' to the rota table per ia.md section 8.5.
5. Move the page `<style>` blocks (How 403 lines, About 309, Exercises 154) into site.css components so each section maps to one WordPress pattern or SSG partial.
6. Give long pages a persistent route back to the main nav (a sticky slim header, or the members rail's For Members link on About and How).

**Task results**

- Scripted in headless Chrome at 1440x900 and a true 390x844.
- T1 newcomer to the join path: 1 click (hero 'Join the team' to #join), but #join sits at 1,895px on desktop (brief ~1,800) and 2,839px on mobile (3.4 screens against a target of 3). Home is 6,100px (cap 5,500), and 13,141px at 390.
- T2 agency to 'Contact the emergency coordinator': 1 click from the navy Agency partner door at about 1,090px on Home. This is the best-placed agency door of the three.
- T3 member to the ICS-213 form: 2 clicks (For Members, then the Popular chip or Most used tile). Hub search submits to documents.html?q= with live suggestions.
- T4 member on a phone, Documents to Exercises: the sticky tab scroller shows about 1.5 of 12 tabs, so it takes a swipe hunt plus a tap. That is friction.
- T5 reader to 'The legal basis' on About: 1 click on desktop, because the 12-item numbered TOC fits at 568px with no inner scroll and only the active section unfolds. On a phone it takes 2 taps via the sticky 'Contents: `<current section>`' bar.
- T6 filtered library link: ?q=winlink gives 14 of 74, ?cat=forms gives 13, aria-live announces the count, chips use aria-pressed.
- Keyboard: skip link is the first stop, and the 40 tab stops on Home and the members hub all had a visible 3px ring.
- JS off: every page reads. The only contrast flag is the disabled 'Next question' button, which WCAG exempts. The mobile menu toggles aria-expanded, Escape closes it and returns focus, and Join stays visible.

**Strengths.** About is the best long-form page in the set. It has 12 numbered sections (you can say 'see section 3'), and the sticky left index unfolds only the active section's H3s, so it never needs an inner scrollbar. There are 12 back-to-top links, and glossary margin notes at 1200px and up. On a phone, a sticky 'Contents: `<current section>`' bar tells you where you are. Lexend 18/1.7 runs about 79 characters a line, the tightest measure of the three. Fig. 1 on Home and the Fig. 2-7 plates on How it works explain the system faster than any photo, and the 'On this page' chip index on How it works gives good information scent. The four-door 'Where are you starting?' router puts the agency path about 1,090px down with a direct 'Contact the emergency coordinator' button. The desktop members rail, with an icon and a one-line hint per item ('Kit lists, alert levels explained'), has the richest information scent of any members menu. The Staying current checklist and Who to ask table are genuinely useful. data.js is byte-identical to _shared, and the quiz config is separate inline JSON, which makes it editable.

**Weaknesses.** Home fails the owner's 'high-level first step' direction. It stacks hero, This-week strip, four doors, quiz, trail plus what-it-takes, what the team does, Licensing in plain English, Three ways to visit and From the field: 6,100px on desktop and 13,141px on a phone, the longest in the set. The members sub-nav on phones is a sideways tab scroller showing about 1.5 of 12 items. nav.mnav overflows by 1px (scrollWidth 272 vs clientWidth 271, overflow:auto), which draws a horizontal scrollbar under the rail at 1440 on systems with visible scrollbars. The header is not sticky, so on the 29,332px About (42,786px at 390) and the 13,597px How it works, the For Members and Join links scroll away. The members This-week card shows 'NZ2S Jeff Banke'; ia.md section 8.5 limits personal names to the rota table. Maintenance: new explanations need someone to compose SVG from the 25-symbol kit, and there are per-page `<style>` blocks of 403 lines (How) and 309 lines (About) plus page scripts on 5 of 6 pages.

</details>

<details><summary><b>Art director</b>: overall 8.0</summary>

**Must fix**

1. Make figure numbering coherent site-wide (per-page prefixes, or number only figures that are cross-referenced). Fig. 1 and Fig. 3 currently mean different things on different pages.
2. Cut Home from 6,100px toward 5,000px: fold 'Three ways to visit' into one line, and let two or three sections run unboxed on open ground so the bordered-card grid stops flattening the hierarchy.
3. Give Fig. 1's callout labels clean ground (sky or white chips with leader lines) instead of halo text over the ridge and pines.
4. Bring one real-photo plate much higher (e.g. under the Home doors or opening How it works), and commission the shoot. The illustration shouldn't be the only picture of people until 4,700px.
5. Redraw the quiz progress 'blazes' as actual trail-blaze marks or a slim progress bar. The empty rectangles read as unchecked boxes.
6. Show net control by call sign only in the members This-week card (currently 'NZ2S Jeff Banke') and in the rota table, per the brief.

**Task results**

- 5-second fold test (1440x900): pass. The Fig. 1 plate, the H1, a sentence naming SCEM, pine 'Join the team' and outline 'Take the 2-minute quiz' all sit in the first viewport, and the illustration makes the system legible at once.
- Coherence across all 6 pages: strong. There is one chrome, a navy header rule and footer, Lexend throughout, 'Fig. n' caption bars and kit icons from Home to Exercises (the Exercises 'Before, during and after' plate uses the same kit), and the members rail icons match the Home door illustrations.
- Imagery: real photos (mast team, parade operator, tailgate go-kit) are shown at native size as captioned plates, but they are few and sit low on the page ('From the field' is about 4,700px down). PHOTO NEEDED frames are styled intentionally (sky-tint, dashed), though About's entrance frame is large and empty.
- Long-read typography: Lexend 18px in a 68ch column with navy section numerals, margin glossary notes and a wheat-highlighted sticky index. It reads well, but the wide Lexend letterforms get tiring over 29,000px.
- Interactions tested: the quiz works (5 answers produce a result card with How long, First step, 'Copy a link to my path' and a mini trail marking 'Start here'). ?from=licensed marks the trail. There is no horizontal overflow at 390 on all 6 pages and no console errors.
- Friction: Home is 6,100px of mostly equal-weight bordered boxes, and figure numbers restart on every page.

**Strengths.** This option has the most memorable single image of the three: the Fig. 1 plate (faceless volunteer in a wheat vest, the W7GBU repeater on a basalt butte, the county EOC, pines and a Palouse wheat foreground) is locally rooted and explains the system in five seconds. The illustration kit is applied with real discipline across all six pages: the door spot-illustrations, the six 'What the team does' tiles, Fig. 2 'Following one message', the seven-step activation track in Fig. 3, the segmented Fig. 4 anatomy-of-a-net bar with 'Visitors' in wheat, the Fig. 3 layers diagram on About, and the exercises before/during/after plate. Seal navy #24348A now gives it an official spine (header rule, table heads, footer), and the navy 'Agency partner' door with its wheat 'Contact the emergency coordinator' button is the best agency moment in the set. Moving from Caveat to Lexend-only removed the children's-museum tone. About reads like a real field-guide reference, with numbered sections, an 'At a glance' table under a navy head bearing the seal, and margin notes. The members rail (icon, label and one-line hint, grouped Operate/Prepare/Reference) is the most scannable members menu of the three.

**Weaknesses.** Home is too busy for a front door. From the This-week strip, the four doors, the wheat quiz band, the trail, 'Here's what it takes', six tiles, licensing, 'Three ways to visit' plus a map, and 'From the field', nearly everything is a 1px-bordered box at similar weight, so the page has no rhythm or rest. Figure numbering is incoherent across the site: Fig. 1 appears on Home, How it works ('Fig. 1, extended') and Exercises, and Fig. 3 is the licensing drawing on Home but the layers diagram on About. The recurring vest figure drifts toward cute through repetition: a vest person is in almost every tile. Real people are still visually secondary, with photos under 660px and pushed to the bottom of Home. In Fig. 1, labels such as 'a handheld radio' and 'receives the message' sit over hills and trees and rely on text halos. The quiz 'trail blazes' render as five empty rectangles that read as checkboxes. Lexend-only typography is competent but has no second voice for emotion: it explains well but never moves you.

</details>

### 3. Carry the Message (option B): composite 8.06

Led by round-1 **When the Lines Go Down (12)**, which ranked 2nd in round 1 (composite 7.92). Top pick of the art director judge.

*Make people feel why the work matters, then ask.* Every page moves from night to daylight. The signature is the ICS-213 message-path diagram: one request from a warming shelter, through net control on W7GBU, to the EOC and a hospital, each hop a volunteer role. Crimson Pro for narration and the long read, Schibsted Grotesk for the interface. Night, dawn, signal red and amber, with a red message line as the recurring device.

- **Open this site:** [`option-b-lines-down/index.html`](option-b-lines-down/index.html)
- **Pages:** [Home](option-b-lines-down/index.html) · [How it works](option-b-lines-down/how-it-works.html) · [About ARES & ACS](option-b-lines-down/about.html) · [Members hub](option-b-lines-down/members/index.html) · [Documents](option-b-lines-down/members/documents.html) · [Exercises](option-b-lines-down/members/exercises.html)
- **Screenshots:** [Home](option-b-lines-down/shots/home-desktop-fold.png) ([phone](option-b-lines-down/shots/home-mobile.png)) · [How it works](option-b-lines-down/shots/how-desktop-fold.png) ([phone](option-b-lines-down/shots/how-mobile.png)) · [About ARES & ACS](option-b-lines-down/shots/about-desktop-fold.png) ([phone](option-b-lines-down/shots/about-mobile.png)) · [Members hub](option-b-lines-down/shots/members-desktop-fold.png) ([phone](option-b-lines-down/shots/members-mobile.png)) · [Documents](option-b-lines-down/shots/documents-desktop-fold.png) ([phone](option-b-lines-down/shots/documents-mobile.png)) · [Exercises](option-b-lines-down/shots/exercises-desktop-fold.png) ([phone](option-b-lines-down/shots/exercises-mobile.png))
- **Judges' overall:** Prospect 7.5, Agency 7.0, Member 7.5, UX 7.0, Art 8.5

![Carry the Message Home, first screen at 1440 by 900](option-b-lines-down/shots/home-desktop-fold.png)

#### How it addressed round-1 feedback

| Round-1 judges said | What changed |
|---|---|
| The join path started about 6,000px down (ease of use 6.0). | The story moved to How it works. Home is 4,954px and the join path starts at 1,631px (2.6 phone screens). Join is in the hero and the header. |
| Members had to skip the story on every visit. | A night utility bar on every page shows the next net from data.js, with net control, Copy radio settings and For Members. The members pages are story-free. |
| The fear-first opening needed a PIO check; a screenshot could circulate as a real alert. | Home's H1 is a capability line. “It's 2 AM…” opens How it works only. The “A scenario” pill is drawn inside the SVG, so any crop still says scenario. No siren colours or status widgets. |
| Scrollytelling is brittle to edit. | Steps are plain `<article data-state>` blocks with one symbol diagram and one IntersectionObserver. Below 900px, with reduced motion or without JS, each step shows its own static diagram. |
| Long empty dark stretches; the practical half lost energy. | Steps are capped at min(70vh, 660px) and each has a visual. The daylight chapters keep the red-line rule, amber markers and big Crimson numerals. |
| Few real photos. | IMG_6574 runs in full colour at dawn; other stills run in night duotone at native size; at most two PHOTO NEEDED frames per page. |
| Visible placeholders and an alert strip on Home. | No visible chips; uncertain facts carry data-verify; no alert status anywhere. |

Condensed from `option-b-lines-down/README.md`. Feedback addressed: 8.7.

#### Current-member task results

| Task | Result | What happened |
|---|---|---|
| Net control and repeater settings | Success, 0 clicks from any page (desktop) | The night utility bar shows the next net, W7GBU 147.300 and net control on every page; offset and tone need Copy or the hub. On a phone the bar drops net control: 1 tap plus a screen of scroll to the radio card. |
| Net preamble (script) | Success, 3 clicks | For Members, then the Popular “Net script” link, then the row. Quick links sit about 1,450px down on desktop (about 2,900px on a phone), below the radio card. |
| ICS-213 form | Success, 3 clicks (2 via footer) | For Members, then Popular “ICS-213”, then the FEMA link. |
| Next exercise and what to bring | Success, 2 clicks | For Members, then “What to bring”. Timeline cards give What / Where / Bring / Sign up / Report to, and SET has a Bring row. |
| Task-book requirements | Partial, 2–3 clicks | Two dead “#” links (the sidebar and “Staying current”). “Full task-book levels” goes to about.html#acs-task-book. |
| Members menu on a phone | Weak, swipe and tap | A sideways scroller with the group labels hidden, showing about 1 to 2.5 of 11 items, under about 122px of sticky chrome. |

#### Judges' notes

<details><summary><b>Prospective volunteer</b>: overall 7.5</summary>

**Must fix**

1. Put the next Third Thursday date (and a what-to-expect line) in Home's 'Come in person' and 'Coming up', and add a 'Listen Tuesday / Visit a meeting' secondary action near the top
2. Put people or the message diagram in the Home hero instead of empty dusk sky
3. Remove jargon from the public utility bar ('Fifth Tuesday: starts on simplex', net-control call sign), and label the mobile 'Copy' button 'Copy radio settings'
4. Change 'Tue, Sep 29, 2000' in the members sidebar and hub to '8:00 PM' (or '20:00 PT') so it doesn't read as a year
5. How it works on mobile: merge the near-identical early story diagrams and shorten the 31,500px page; add short-version summaries on About

**Task results**

- (1) Understand it in 10 seconds: PARTIAL, 0 clicks. The capability H1 and the SCEM sentence are in the first viewport, but the hero picture is an empty dusk sky with no people, radio or diagram. The thing that explains it, 'One message, four volunteers', starts at 724px. Above the logo, the utility bar leads with insider copy ('Fifth Tuesday: starts on simplex, then W7GBU 147.300 MHz. Net control NZ2S.'). On a phone it reads 'Tue, Sep 29, 8:00 PM. Copy For Members', and 'Copy' means nothing to a newcomer.
- (2) What members actually do: SUCCESS, 0 clicks. It is strong: the Home diagram names 4 volunteer roles (shelter radio operator, net control, EOC radio room operator, hospital team operator). How it works tells the 6-step story, then 'What members actually do' and 'Every hop is a post' (9 role cards). Friction: How is 19,582px on desktop and about 31,500px on mobile, and on phones the first story diagrams look nearly identical.
- (3) Join if not licensed: SUCCESS, 1-2 clicks. Hero 'Join the team' goes to #join at 1,631px. The 'Not licensed yet' segment works (step 0 gets 'Your next step' and a copyable ?from= link). The 'Not licensed yet?' card gives HamStudy and SKYWARN. The interest form defaults to 'Not licensed yet'. 'Licensing in plain English' leaves Home for about.html#licensing.
- (4) Meeting where and when: WEAK. There is no visit or listen CTA near the top. The dawn band (about 2,950px) says 'Third Thursday training meetings are in the evening' with the address and Get directions. I checked the rendered text: Home never states the next meeting date, and 'Coming up' stops at the Oct 10 workshop. To find Oct 15 you have to go to About or the members exercises page.

**Strengths.** It has the most emotional pull and the most polished craft in the set. Crimson Pro with the night-to-dawn palette looks credible and adult. The Home 'One message, four volunteers' diagram, with the scenario pill drawn inside the SVG, makes 'what would I do' concrete in one glance. The How it works story ('It's 2 AM...' through 'Radio still works. Because people practice.') is the most persuasive reason to care in any option, and it is now off Home, as round 1 asked. 'Could you be the one on the radio?' is the most motivating join heading. The ICS-213-styled 'Tell us you're interested' form is the lowest-effort way to raise a hand. The About structure diagram (ARRL chain vs county chain) is excellent, and the members radio card is the fastest net readout in the set.

**Weaknesses.** For a prospect deciding whether to show up, it asks for commitment ('Join the team') more than a visit, and it never gives a date for the next meeting on Home. The hero spends the first viewport on sky. The utility bar puts member jargon on every public page. How it works and About are very long: About announces 'About 26 minutes from start to finish', which deters rather than invites, and it has no short-version summaries. On the members pages, the sidebar's 'Tue, Sep 29, 2000' reads as the year 2000. Real photos are still scarce: a sky, a go-kit and PHOTO NEEDED frames.

</details>

<details><summary><b>Served-agency partner</b>: overall 7.0</summary>

**Must fix**

1. Fix cold-load anchor landing (port A's site.js fix): about.html#for-agencies overshoots 464px desktop / ~1,287px mobile and #contact hides ec@
2. Add an agency door near the top of Home (currently 4,143px desktop, 7,376px mobile) with 'Contact the emergency coordinator'
3. Move 'For served agencies' in the About TOC from 'Training and service' to 'Authority and organization'; fix scroll-spy lag
4. Add an activation track (request, mission number, activation, check-in, release, 72-hour review) to 'From a quiet month to a deployment'
5. Replace 'Tue, Sep 29, 2000' on members pages with 8:00 PM (reads as the year 2000)
6. Get PIO review of the H1 'Spokane County still has a voice' and the Home rain/'Cell service out' diagram
7. Show net control by call sign only in the rota

**Task results**

- Task 1, credibility: mostly pass. It is the most premium-looking option: editorial Crimson serif, night chrome, a well-labelled scenario with the 'A scenario' pill drawn inside the SVG, and no status widget. Concerns an EM or PIO would raise. The H1 'Spokane County still has a voice' can read as speaking for the county. The Home rain and 'Cell service out' diagram needs PIO sign-off. Red-ink links in the legal chapter look alert-like. Members pages print 'Tue, Sep 29, 2000', which reads as the year 2000.
- Task 2, ARES vs ACS and legal basis: 2 clicks (About, then TOC 'Two hats, one team'). This has the most complete comparison table, 8 rows including Paperwork, Qualification, ID and Legal footing. The legal chapter has a 'What this means for you' box and a numbered 'four layers' panel, and the 'What we are not' box (not a public alert service, not a source of official incident information) is excellent.
- Task 3, link from a county page: not until fixed. A cold load of about.html#for-agencies overshoots by 464px on desktop and about 1,287px at 390. On a phone it lands in 'Where and when we meet' beside a PHOTO NEEDED frame, so the agency section is missed entirely. about.html#contact overshoots by about 550px and hides the ec@ address. The Home 'For served agencies' band sits at 4,143px on desktop and 7,376px on mobile, with no earlier agency door.

**Strengths.** The strongest persuasion for elected officials and partners. Home's 'One message, four volunteers' static diagram shows the capability in one picture. The How it works story (ICS-213 card, ICS-309 log line, every hop a named volunteer role) makes the value felt and stays labelled as a scenario. About is a handsome long read, with a Last reviewed/page owner line, sidenotes, and the best 8-row ARES vs ACS table. The members ops sidebar with its big 147.300 radio card reads as a disciplined unit. The footer is grouped by audience and has the 911 disclaimer.

**Weaknesses.** The agency path is the weakest of the three: the only Home door is at the bottom. The TOC files 'For served agencies' under 'Training and service', and scroll-spy lags a section behind (it showed 'Two hats' while reading AUXCOMM and Mission). How it works has no activation step flow: no request, mission number or release sequence, only 'Who calls us' cards plus the readiness levels. How it works is 19,582px and About 31,979px. The night-and-red palette on every page's chrome keeps a slightly dramatic register for a partner audience. 24-hour times are ambiguous on the members pages. Volunteer names appear in the rota.

</details>

<details><summary><b>Current member</b>: overall 7.5</summary>

**Must fix**

1. Keep net control in the phone utility bar ('Tue 8:00 PM, NCS NZ2S, Copy').
2. Replace the 900px-and-below horizontal members scroller with the ia.md 'Members menu' disclosure, with group labels visible.
3. Move Quick links (Net script, ICS-213, ACS Task Book) above or beside the radio card, and add a 'Weekly script' link inside the card.
4. members/documents.html below 1180px: compress rows to one-line meta (format, date, source) like A and C, instead of stacked label/value cards.
5. Fix both dead 'Training & task books' links (sidebar and Staying current) with an interim target.

**Task results**

- Start = Home.
- (1) Net control + settings: SUCCESS on desktop, 0 clicks from ANY page. The night utility bar reads 'Next net: Tuesday, Sep 29, 8:00 PM … W7GBU 147.300 MHz. Net control NZ2S.' Offset and tone need the Copy button or the hub (1 click). On a phone the utility bar shrinks to 'Tue, Sep 29, 8:00 PM. Copy. For Members' and drops net control, so it is 1 tap plus about 1 screen of scroll to the big 147.300 radio card.
- (2) Net preamble: SUCCESS, 3 clicks: For Members, then the Popular 'Net script' link, then the row. The Quick links tile sits about 1,450px down on desktop and about 2,900px on a phone, below the radio card, check-in order and exercises panel. There is no script link in the radio card.
- (3) ICS-213: SUCCESS, 3 clicks: For Members, then Popular 'ICS-213', then the FEMA link. It is 2 clicks through the footer 'Forms'.
- (4) Next exercise + what to bring: SUCCESS, 2 clicks: For Members, then 'What to bring' (exercises.html). The timeline cards give What/Where/Bring/Sign up/Report to, and SET has a Bring row. The Home 'See all exercises and events' link is about 3,700px down.
- (5) Task-book requirements: PARTIAL, 2-3 clicks, with two dead ends: the sidebar 'Training & task books' and the 'Training & task books' link under Staying current are both '#'. 'Full task-book levels' goes to about.html#acs-task-book.
- Phone nav: a horizontal scroller with the group labels hidden, showing about 2.5 of 11 items.

**Strengths.** The site-wide utility bar puts next net and net control on every page. The members sidebar shows a mini next-net readout, so a member never has to hunt for 'who's NCS'. The radio card is the richest net reference of the three: settings grid, check-in order, and the alternate and NOAA frequencies. The exercises timeline interleaves each month's nets with the exercises, with Report-to and Add to calendar on each. The Home fixes are the most complete (the story moved to How it works, join at 1,631px), and it is the most professional and moving of the three for newcomers and agencies.

**Weaknesses.** It has the slowest member layout. The huge 147.300 card pushes Quick links (net script, ICS-213, task book) far below the fold, especially on a phone. The mobile utility bar drops net control. The mobile sub-nav is a label-less sideways scroller. On a phone each documents row becomes a tall label/value stack (about 250px per document), which makes the library slow to scan. The dark sidebar and chrome add visual weight to utility pages. Both 'Training & task books' links are dead '#'.

</details>

<details><summary><b>UX, accessibility and upkeep</b>: overall 7.0</summary>

**Must fix**

1. How it works: open with a one-screen 'What members do' summary and a sticky chapter index (Nets, Activation, Messages, Exercises, Public service, Roles), and move the story below it or cut it to 4 steps. The page is 19,582px on desktop and 31,553px on a phone.
2. Stop dimming story text: inactive .step at opacity .5 puts the ICS-213 card at 2.51:1. Dim only the diagram layer, or keep text at 0.85 opacity or higher.
3. Members on phones: replace the sideways sub-nav scroller (about 1 item visible) with a 'Members menu: `<current page>`' disclosure, and cut the sticky chrome (65px header plus 57px sub-nav).
4. Documents at 390: collapse table rows to compact rows (title, one-line purpose, then format, date and source badges on one line) instead of stacked label/value pairs. The page is 22,250px.
5. About on phones: collapse the 26-item contents list by default and add a sticky 'Contents: `<current chapter>`' bar. Group the desktop TOC so only the active group expands (no inner scrollbar).
6. Add an 'Agency partner' door near the hero (an audience row or utility-bar link); the served-agencies band is about 3,900px down Home.

**Task results**

- T1 newcomer to the join path: 1 click on the hero's red pill. #join sits at 1,631px on desktop and 2,209px on a phone (2.6 screens). Home is 4,954px, the shortest desktop Home and the only one inside the ~5,000 target. Next to the 0-3 path, the interest form removes a step.
- T2 agency to 'Contact the emergency coordinator': 1 click, but the slate 'For served agencies' band starts about 3,900px down, and there is no audience door near the hero.
- T3 member to the ICS-213 form: 2 clicks. For Members is also in the night utility bar on every page, so it is reachable without the main nav.
- T4 member on a phone, Documents to Exercises: a sideways scroller shows about 1 item, under a sticky header (65px) plus sub-nav (57px), which is about 122px of chrome at 844px tall.
- T5 reader to 'The legal basis': 1 click on desktop, where the 26-item grouped TOC scrolls internally (772 of 1,000px) and does keep the active item in view. On a phone the contents list is an open-by-default `<details>` that takes about 1.3 screens before the first paragraph; after that a floating Contents button reopens it.
- T6 library: ?q=winlink gives 14, ?cat=forms gives 13, aria-live works, chips use aria-pressed.
- Keyboard: skip link first, rings visible on dark (amber) and light.
- Contrast: inactive story steps are opacity .5, which drops the ICS-213 card to 2.51:1 and its field values to 3.35:1 until the step becomes active. Under reduced motion, steps render at full opacity, each with its own static diagram (verified).
- JS off: all pages read.

**Strengths.** The most focused newcomer Home: capability H1 naming SCEM, one red Join pill and one text link in the first viewport, the static four-hop 'One message, four volunteers' diagram with the 'A scenario' pill drawn inside the SVG, then straight into 'Could you be the one on the radio?' with the ?from= segmented control. The site-wide night utility bar (next net, net control, Copy radio settings, For Members) is the best member convenience in the set: it puts this week's net on every page with zero clicks. The desktop documents library is a true table (Document / What it's for / Format / Version or date / Source) and the most scannable view of 74 entries. The About long read in Crimson 20px, with Tufte sidenotes and the ARES vs ACS table, reads like a quality editorial. It has the leanest JS (site.js 545 lines), and story steps are plain `<article data-state>` blocks that editors can reorder. The reduced-motion and below-900px fallback of static small multiples is exemplary.

**Weaknesses.** How it works fails the owner's brief ('more detail on the organization, operations, and what members actually do'). A reader has to pass about 3,500px of 2 AM scrollytelling before any operational content. The page is 19,582px on desktop and 31,553px on a phone, the longest How in the set, and the 'Skip to what members do' link is the only shortcut. Dimming inactive steps to 50% opacity creates AA failures in transit. The members area is chrome-heavy (utility bar, night header and night sidebar), and on phones it falls back to a one-item-visible sideways scroller. Documents at 390 become label/value cards about 150px tall each (22,250px page, against 15,949 for A). The About TOC has 26 flat H2 entries that need an inner scrollbar, back-to-top appears only 6 times, and the measure is about 82 characters. The agency door sits far down Home. The bespoke SVG state machine driven by CSS custom properties (the 26 style attributes are the diagram layers) and the dark/dawn gradients are the hardest pieces in the set for a volunteer to extend.

</details>

<details><summary><b>Art director</b>: overall 8.5 (top pick)</summary>

**Must fix**

1. Replace 'Tue, Sep 29, 2000' in the members sidebar and radio card with '8:00 PM' (or put the 24-hour time in its own labelled cell). As written it reads as the year 2000.
2. Give the Home hero a subject. Commission a dusk shot with an operator, antenna or radio room silhouette, or bring the message diagram into the first viewport. The empty sky carries mood but not the work.
3. Carry the diagram vocabulary into the How it works daylight chapters (a mini hop diagram for Nets, a drawn activation track, a drawn 'Where we work'). After the dawn band the page is almost all text and tables.
4. Refine the signature diagram's drawing: recognisable silhouettes for the shelter, EOC and hospital and a truer Brownes Mountain profile, so the one image everyone remembers has craft detail to match the typography.
5. Reserve red for the message line and primary CTAs. Change the document-title links in the members library (and elsewhere) to ink with a red hover state.
6. Lighten or re-grade the night-blue duotone on the comm-trailer photo, which reads muddy. Show net control by call sign only in the rota table, per the brief.

**Task results**

- 5-second fold test: pass, and the strongest of the three. The night utility bar, a full-bleed dusk photo, the Crimson Pro H1 'When other systems go down, Spokane County still has a voice.', a subline naming SCEM, the red pill Join and an amber-underlined 'Follow one message through a night without phones' read as a serious public-service publication, not a club.
- Coherence across 6 pages: excellent. The night, dawn and daylight system holds everywhere: About opens on a dawn gradient under the night chrome, the members pages are daylight with a night sidebar, and the red line appears as the section rule, the join-path spine, About's TOC tracker and the ICS-213 card's top border.
- Imagery: the message-path diagram is the signature and is well built (rain hatching, red arc, amber hops, 'A scenario' pill drawn inside the SVG). The Home hero photo is sky and bare trees only, with no people or radio. The comm-trailer duotone is muddy, and the tailgate go-kit and bumper-station photos are natural colour. PHOTO NEEDED frames in night slate read as intentional.
- Long-read typography: Crimson 20px in a 64ch column, grouped contents with a red active line, peach-ruled sidenotes, red-ruled italic pull quotes and a two-chain structure diagram. It is the most readable and most elegant long read.
- Interactions: the How it works sticky diagram swaps states beside the steps (how-desktop-story.png, with the legend dimming future hops), and the reduced-motion version shows static small multiples. There is no horizontal overflow at 390 on all 6 pages and no console errors.
- Friction: the members sidebar and radio card render 'Tue, Sep 29, 2000', which reads as the year 2000.

**Strengths.** It has the most coherent and distinctive brand system: Crimson Pro narration with Schibsted Grotesk for UI, and the night #0E1A33, dawn peach, signal red and amber palette tell the thesis (night, then practice, then dawn) through colour alone. The red message line is used as a genuine brand device, not decoration. On Home, the 0-3 join steps sit on it as amber Crimson numerals linked by a red line that turns dashed before the optional step 3. That is the most beautiful join path in the set. The interest form styled as an ICS-213 paper card ties sign-up to the work itself. The How it works story is now editable and bounded: steps are capped, each has a visual, and the dawn payoff 'Radio still works. Because people practice.' lands. The About page is the best long read here, with the FCC → ARES chain / County chain → EC structure diagram, the ARES vs ACS table with emblems in the column heads, and the night 'For served agencies' card. The members radio card with a huge Crimson '147.300 MHz' is the single most memorable members component in any option. This option gives agencies the most confidence: nothing is cute, and the scenario pill is baked into the diagram.

**Weaknesses.** The hero photo is mood without subject: an empty dusk sky, and no people appear on Home until the go-kit photo, which has no people either. Home is heavy and dark on phones (about 9,400px at 390, mostly night). After the dawn, the How it works daylight chapters (Nets, the readiness levels, Handling messages, Exercises) drop the illustration language and become Crimson headings, hairline tables and text. They are handsome but visually flat compared with A's figure plates. The diagram's buildings are generic rectangles, so the signature plate would benefit from more drawn detail: Brownes Mountain, recognisable EOC, shelter and hospital silhouettes. Red is overused in the members library, where every document title is red-underlined, turning the table into red noise and diluting the 'red = message and primary action' rule. 'Tue, Sep 29, 2000' is a legibility bug in the members chrome.

</details>

## Recommendation

**Build round 3 on Open Channel (C).** Keep its structure and member tools, then graft in Figure One's figures and About navigation and Carry the Message's message diagram and reference pieces.

### Why Open Channel

- It ranks first (8.10) and has the best Home and For Members section scores (8.0 and 8.3), the two sections your direction is most specific about.
- Its Home is closest to “a high-level first step with clear calls to action”: 5,214px, one filled primary button, the join path at 1,062px and the Agency partner door in the first screen.
- It is the only option whose phone members menu works (the ia.md “Members menu” disclosure), and its hub puts search, this week, the next four net controls and Most used above the fold. The member and UX judges both picked it.
- It fixed its round-1 criticisms most completely (feedback addressed 9.0), and its parts (photo cards, pills, tables, `<details>`) map most directly onto WordPress core blocks.
- Its gaps are engagement (7.2), learn-more pull (7.5) and a forgettable look (art 6.5). Those are exactly where A and B lead, and they can be grafted in. A's and B's gaps are structural: A's long, module-stacked Home and phone tab bar; B's story-first How it works, bottom-only agency door and heavy members chrome.

The margin is hundredths of a point, so this is a judgment call, not a clear win. The strongest alternative is to build on Figure One (A), the prospect and agency judges' pick and the best How it works (8.3) and About (8.6). That route means cutting about 1,000px from Home and rebuilding the phone members menu. The art director's route (B as the brand frame) gives the most distinctive and professional look (8.5) but needs the most restructuring: How it works, the agency path and the members pages.

### What to keep and what to graft

**Keep from C, Open Channel**

- The capability hero with one filled primary button, and the red Join in the header.
- The This-week card (made newcomer-first on Home) and the audience row with the Agency partner door.
- “Joining, in four steps” with ?from=, “Here's what it takes” and “Training with partners this fall”.
- How it works: “A month with the team” calendar, the sticky “On this page” chips, the net minute by minute, “Find your spot”, “Your first month after joining” and “Questions newcomers ask”.
- About: the “Start with what you need” router, the “Rules and partners” TOC group, the richer “For agencies and partners” body and “The short version” (for major H2s only).
- For Members: the hub (search first, next four net controls with open slots, Most used), the phone “Members menu” disclosure, document rows that say where they open, “Hide Coming back”, and the exercises agenda with month tabs.

**Graft from A, Figure One**

- The figure kit as the one illustration language across the site, replacing C's mix of line drawings, icon tiles and the flat four-circle message row. Bring DESIGN.md's recipe for building a new figure.
- Fig. 1 as a compact explainer on Home, in or just below “What we do”.
- About's navigation: numbered sections, a sticky index that unfolds only the current group (no inner scrollbar), the phone “Contents: current section” bar and back-to-top links.
- How it works: the seven-step “When the county calls” track (request, mission number, activation, check-in, assignments, release, 72-hour review) and the Fig. 4 anatomy-of-a-net bar.
- About: the Fig. 3 layers diagram, the “In plain English” legal callouts and the navy “For agencies and partners” panel with “Email the emergency coordinator”.
- The site.js anchor-landing fix, the only one that lands cross-page links correctly on a cold load.
- Members on desktop: the left rail with icons, group labels and a one-line hint per item, in place of C's two-row pill bar. Reuse the hints in the phone disclosure and the hub directory.
- Exercise plates with a Bring row on every exercise (When / Bring / Log / Forms to carry / Assignments).
- “Licensing in plain English” as a short Home teaser into About, and the five-question quiz as a linked secondary path rather than a Home section.

**Graft from B, Carry the Message**

- The static “One message, four volunteers” diagram (four named volunteer roles, the scenario pill drawn inside the SVG) as Home's why-it-matters moment, redrawn in A's kit.
- A slim next-net line on every page with a For Members link. Keep it plain on public pages (“Tuesday net, 8:00 PM, visitors welcome”); show net control and offset/tone on members pages, including on phones.
- About: the 8-row ARES vs ACS table, the “What we are not” box, the ARRL-chain vs county-chain structure diagram and the “Last reviewed / page owner” line.
- Documents on desktop as a true table (Document / What it's for / Format / Version or date / Source), collapsing to C's compact rows on phones.
- Its engineering restraint: one lean site.js, reorderable `<article>` blocks, and static small multiples under reduced motion.
- Optional: a four-step version of the “It's 2 AM” story with its dawn line, placed after How it works' operational summary, never before it.

### Two decisions for the owner

1. **Visual register.** The recommendation keeps C's warm civic look, tones down its heavy condensed headings and makes A's figures the signature device. If you want the most distinctive and most “official” look instead, B's Crimson Pro and night-to-dawn palette can be carried over C's structure. That is the art director's advice, and B scored highest on professional (8.5) and engaging (9.0).
2. **Names on the net-control rota.** ia.md allows personal names only on the rota table, as published on ag7qp.com, and call sign only everywhere else. The agency and art judges asked for call signs only on the rota too. All three options currently print names on the rota, and A also prints one in its This-week card.

### Consolidated must-fix list for round 3

Merged from all fifteen judge reviews and applied to the recommended build. Brackets show which judges raised each item.

**Home**

1. Make the Home This-week card newcomer-first (“Listen Tuesday 8:00 PM on 147.300, no license needed”, plus the next meeting). Move net control, offset/tone, the simplex note and member links to the members hub and the members-facing next-net line. [Prospect, Member]
2. Shrink the visit planner on Home to a compact “Come visit” card (Listen / Meeting / Workshop, each with a when-line and Add to calendar). Move the minute-by-minute timeline and RSVP to How it works. Keep Home at or under 5,500px and bring the phone version well under 11,661px. [UX, Prospect, Agency, Art]
3. Add one why-it-matters explainer on Home: B's static message diagram drawn in A's kit, linking to How it works. [Prospect, UX, Art, Agency]
4. Add a short plain-English licensing block on Home, or send the “New to radio” door to licensing as well as listening. [Prospect]
5. Replace PHOTO NEEDED frames that lead a row (Home “What we do”, the Net control card in “Find your spot”) with a real photo or a drawn plate. Recompose the hero so the card doesn't cover the only face, using a wider operational shot (comm trailer, EOC exercise). Commission a photo shoot. [Prospect, Agency, Art]
6. Move the “When it was real” card (August 2023 Gray/Oregon Road fires) up to Home beside the agency door. [Agency]

**How it works and About**

7. Fix cold-load anchor landing by porting A's site.js fix. In C, about.html#for-agencies overshoots by about 960px and #contact by about 640px, hiding the ec@ row. [Agency]
8. About contents: numbered groups where only the active group unfolds, so there is no inner scrollbar at 900px. Narrow the text column to 75 characters a line or less (about 86 now). Keep “The short version” for major H2s only. [Agency, UX, Art]
9. Add the county's steps (request, mission number, release, 72-hour review) to “Activation in five steps”. [Agency]
10. Once A's figures come in, number figures uniquely across the site. [Agency, Art]

**For Members**

11. Point “Training & task books” at an interim target (members/index.html#requirements or about.html#acs-task-book) instead of “#”. All three options have this dead link. [Member]
12. Hub “Coming up”: show the next two exercises, not only upcoming()[0], so SET (Oct 3) appears on Sep 26. Give every exercise card a Bring row, and open the next one or two agenda rows by default. [Member]
13. Desktop members menu: A's left rail with group labels and hints, or a one-row bar at 1280px and up. Keep the phone disclosure. [Member, UX, Art, Agency]

**Site-wide**

14. Write “8:00 PM” wherever a time sits next to a date: “Tuesday net, 2000” and “Tue, Sep 29, 2000” read as a year. Keep 24-hour times only in table columns, per ia.md rule 9. [Prospect, Agency, Member, Art]
15. Limit the slashed zero to frequencies and call signs, and use plain zeros in dates, times and addresses (“2Ø26”, “9926Ø”). [UX, Art]
16. Show call signs only outside the rota table (ia.md rule 5). [Member, UX, Agency, Art]
17. Tone down the heavy condensed display type on agency-facing headings. [Agency, Art]
18. Fold the large page scripts (How 12.6KB, Exercises 11KB) into named site.js widgets, and render the document library from one source (data.js or the static page, not both). [UX]

**Before launch**

19. Get sign-offs: SCEM approval for any seal row labelled “Affiliation” (or relabel it “Served agencies”), PIO review of scenario copy and diagrams, photo releases, and the proposed role addresses. [Agency]

### What each judge would combine

<details><summary><b>Prospective volunteer</b> (top pick: Figure One)</summary>

Use A as the newcomer spine: the Fig. 1 hero (it explains the whole thing in 10 seconds), the four-door 'Where are you starting?' router with its Agency partner door, the 5-question quiz with a routed result and ?from= deep link, 'Licensing in plain English' on Home (driver's-test analogy, HamStudy, classes, the $35 fee, exam sites, SKYWARN before a license), the 0-3 trail beside 'Here's what it takes', and the illustrated 'Pick your post' cards. Replace A's buried 'Three ways to visit' with C's planner, triggered by C's single primary CTA 'Plan your first visit'. The Meeting tab has date chips, the arrive / training / meet the people sequence, At a glance (what to bring: nothing; license needed: no), Add to calendar, the drawn map and the optional 'Tell us you're coming'. On How it works, add C's 'Your first month after joining' (dated steps) and 'Questions newcomers ask', and C's 'A month with the team' calendar as the operations overview. On About, use C's 'The short version' box on every H2 together with A's 'Start where you are' router and margin glossary. From B, take the static 'One message, four volunteers' diagram (four named volunteer roles, scenario pill inside the SVG) as a Home section just after the hero. Also take the 'It's 2 AM' story as the opener of How it works, with its dawn line 'Radio still works. Because people practice.'; B's 'Could you be the one on the radio?' join heading; the ICS-213-styled 'Tell us you're interested' form (with 'Where are you on the route?' radios) beside the trail; and the ARRL-chain vs county-chain structure diagram on About. For members: C's hub layout (search first, next four net controls with open slots, Most used) and its mobile 'Members menu' disclosure, A's desktop left rail with icons and one-line hints, and B's big radio-settings card. Guard rails: keep jargon (net control call sign, offset/tone, the simplex note) off the public hero and utility bar, and write times as '8:00 PM', not '2000'.

</details>

<details><summary><b>Served-agency partner</b> (top pick: Figure One)</summary>

For the served-agency reader, build on A and graft in C's Home agency moves and B's best reference pieces.

From A (base):
- About's structure: 12 numbered sections, the compact sticky index that expands only the current section, and the 'Start where you are' router with an agency line.
- Section 2's Fig. 3 layers diagram, followed straight away by the identification table and the 'Legal footing' line.
- Section 3's four-layer legal cards with 'In plain English' callouts and eCFR and Legislature links.
- The navy 'For agencies and partners' panel with 'Email the emergency coordinator'.
- How it works Fig. 3's seven-step activation track (request, mission number, activation, check-in, assignments, release, 72-hour review).
- The Home agency-door copy (RCW 38.52, mission numbers, background check, auxiliary support, not first response).
- A's site.js anchor-landing fix, the only one that lands cross-site links correctly on a cold load.

From C:
- Put the Agency partner door in the first screen of Home.
- Add the dated 'Training with partners this fall' row as live proof of readiness.
- Close Home with the 'Represent an agency or an event?' panel.
- Move the 'When it was real' August 2023 Gray/Oregon Road fires card (with the comm-trailer photo) up to Home.
- Give every About H2 a 'The short version' box, and group the TOC as 'Rules and partners'.
- Use C's richer 'For agencies and partners' H2 body (relationship with SCEM, ESF-2, what we bring, agreements).

From B:
- The 8-row ARES vs ACS table, which adds Paperwork, Qualification, ID and Legal footing to A's 5 rows.
- The 'What we are not' box (not a public alert service, not a source of official incident information).
- The static 'One message, four volunteers' diagram with the 'A scenario' pill drawn inside the SVG, as the one-picture capability explainer for commissioners.
- The 'Last reviewed / page owner' line.

Avoid B's bottom-only agency band, its TOC placement of the agency section, and an H1 that could read as speaking for the county. Across all options, show net control by call sign only and get SCEM sign-off on any seal labelled 'Affiliation'.

</details>

<details><summary><b>Current member</b> (top pick: Open Channel)</summary>

Build on C's members architecture: (1) the Home This-week card at the fold with the member shortcut row (Net script / Forms / Exercises / This week in full); (2) the mobile 'Members menu: current page' disclosure with Operate/Prepare/Reference labels; (3) the hub This-week card with the four-Tuesday mini-rota and script links under Copy radio settings; (4) documents with the 'Hide Coming back' toggle and the labels aside; (5) the exercises month tabs with counts. Graft in B's site-wide night utility bar, with net control kept on phones, and B's sidebar mini next-net readout on every members page. Use A's desktop left index rail (icons, group labels and a one-line hint per item) instead of C's two-row pill bar at 1024px and up. Use A's exercise plates (When/Bring/Log/Forms to carry/Assignments) with a Bring row for every exercise, plus A's hub link 'What to bring and how to sign up'. Add B's radio-card check-in order and alternate/NOAA frequencies to the future Nets & net control page. In every option, point 'Training & task books' at an interim target (members/index.html#requirements, or about.html#acs-task-book) instead of a dead '#', and make the hub's 'next exercise' list show at least the next two exercises, so SET appears while WSDOT is 'Happening now'.

</details>

<details><summary><b>UX, accessibility and upkeep</b> (top pick: Open Channel)</summary>

Use C (Open Channel) as the skeleton, then graft in the rest.

From C:
- The header and single red Join button.
- The capability hero with one filled primary CTA.
- The white This-week card.
- The audience row with Agency partner under the hero.
- The How it works sticky 'On this page' chip bar with scroll-spy, and the calendar that turns into an agenda at 390px.
- The 'The short version' box at the top of every About H2.
- The members pill bar on desktop.
- Above all, the 'Members menu: `<current page>`' disclosure on phones, which lists all 11 items grouped Operate / Prepare / Reference. It is the only members menu in the set that works at 390px.
- The exercises agenda: month tabs with counts, type filters, expandable rows and 'Next up' cards.
- The documents rows that state their destination ('Opens training.fema.gov'), plus the 'What the labels mean' sidebar.

From A (Figure One):
- The About TOC pattern: 12 numbered top-level sections where only the active section's H3s unfold, so there is no inner scrollbar. Also its sticky mobile 'Contents: `<current section>`' bar, back-to-top after every section, and the tighter measure (Lexend 18/1.7, about 79 characters a line).
- Fig. 1 as a compact explainer on Home.
- The four-door 'Where are you starting?' agency card with a direct 'Contact the emergency coordinator' button.
- The one-line hints under each members rail item ('Kit lists, alert levels explained'), reused as descriptions in C's disclosure and in the hub's 'Everything for members' directory.
- Optionally, the quiz as a linked secondary path rather than a Home section.

From B (Carry the Message):
- The site-wide slim next-net utility bar (next net, net control, Copy radio settings, For Members). It gives members this week's net on every page with no clicks.
- The static 'One message, four volunteers' diagram, with the scenario pill drawn inside the SVG, as Home's learn-more hook.
- The desktop documents library as a true table (Document / What it's for / Format / Version or date / Source), collapsing to C's compact rows on phones.
- Its engineering restraint: a 545-line site.js, reorderable `<article data-state>` blocks, and static small multiples under reduced motion.

Leave behind:
- A's long, module-stacked Home.
- B's story-first How it works, its 50% opacity step dimming and its heavy dark members chrome.
- C's full visit planner on Home, and the doc library that both renders from data.js and ships a static copy.

</details>

<details><summary><b>Art director</b> (top pick: Carry the Message)</summary>

Use B as the brand frame. Keep its type pairing (Crimson Pro narration and headings, Schibsted Grotesk UI), the night, dawn and daylight palette, and the red message line as the one recurring brand device: section rule, join-path spine, TOC tracker, and the ICS-213 top border on the interest form. Keep B's Home hero structure (capability H1 naming SCEM, red 'Join the team', 'Follow one message...'), but put a person or radio in the hero photo and move the message-path diagram closer to the fold. Keep B's 0-3 join path on the red line, its ICS-213 paper-card interest form, the How it works scrollytelling with the dawn payoff, the About long read (grouped TOC, sidenotes, the FCC/ARES/County structure diagram) and the members radio card with the big '147.300 MHz'. Graft A's explanatory figure system into B's daylight chapters, redrawn in B's line-and-night vocabulary: the Fig. 4 anatomy-of-a-net segmented bar, the seven-step activation track, the Fig. 3 County, ARES and ACS layers diagram and the before/during/after exercise plate, plus A's documented symbol-kit method so volunteers can extend them. Also from A: the navy 'Agency partner' door with 'Contact the emergency coordinator' in the Home audience row, the 'Is this for me?' quiz and its result card with 'Copy a link to my path', and the members left rail with an icon and one-line hint per item. From C: the This-week card content model (net control for the next four Tuesdays plus Coming up), member search above the fold, a compact version of the 'Three ways to visit' planner with its rounded door tabs, document rows that say where each link opens ('Opens training.fema.gov') with the 'Hide Coming back' toggle, the mobile 'Members menu' disclosure, and the big 'W7GBU' call-sign type block for About's W7GBU story. Across the merged system: show net control by call sign only, avoid 'Sep 29, 2000' date/time collisions, use one illustration language, and commission a photo shoot so real members appear above the fold.

</details>
