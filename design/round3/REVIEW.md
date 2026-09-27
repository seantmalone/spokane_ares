# Round 3 site review: Spokane County ARES-ACS (simplified)

Reviewed 26 September 2026. The owner's feedback on round 2 was: "All three of these have just way too much info. It's overload. Do another pass on each to simplify. Minimize repeating information unless it's really important."

Round 3 cuts every page to its job and gives each fact one home. All three options now share one trimmed copy (`_shared/`), so they differ in layout and tone, not content.

- Gallery: `design/round3/index.html` (open from disk; every screenshot links to the live page)
- Round-2 gallery for comparison: `design/round2/index.html`
- Sites: `design/round3/option-c-open-door/index.html`, `design/round3/option-b-lines-down/index.html`, `design/round3/option-a-field-guide/index.html`

**Open Channel still leads** at 8.35, ahead of Carry the Message (8.24) and Figure One (7.86). The newcomer and editor judges picked Open Channel; the member judge picked Carry the Message. All 18 pages are within their budgets.

## Before and after

Visible words in `<main>` and rendered heights, measured by `design/tools/measure_pages.py`. Each cell is round 2 → round 3.

| | Budget | Open Channel | Carry the Message | Figure One |
|---|---:|---:|---:|---:|
| **Words, whole site** | 6,300 | 18,878 → **3,415** | 17,246 → **3,542** | 17,805 → **3,368** |
|   Home | 400 | 1,644 → **258** | 1,096 → **259** | 1,894 → **272** |
|   How it works | 1,200 | 4,146 → **870** | 3,802 → **909** | 3,620 → **864** |
|   About | 2,800 | 7,055 → **1,227** | 6,638 → **1,274** | 6,621 → **1,274** |
|   Members hub | 300 | 1,215 → **121** | 1,145 → **124** | 1,271 → **107** |
|   Documents | 900 | 2,264 → **531** | 2,467 → **549** | 2,413 → **450** |
|   Exercises | 700 | 2,554 → **408** | 2,098 → **427** | 1,986 → **401** |
| **Home height at 1440** | 3,200px | 5,214px → **2,935px** | 4,954px → **2,902px** | 6,100px → **2,825px** |
| **Home height on a phone** |  | 11,913px → **3,987px** | 9,452px → **3,511px** | 13,430px → **3,658px** |
| **All six pages at 1440** |  | 81,598px → **24,714px** | 80,111px → **24,487px** | 71,082px → **25,723px** |
| **Repeated 8-word phrases** |  | 462 → **0** | 444 → **6** | 621 → **7** |
| **Key-fact page mentions** |  | 48 → **29** | 49 → **29** | 44 → **29** |

### Where each key fact lives now

Same in all three options.

| Fact | Its one home | Pages, round 2 | Pages now | Other mentions (one-line pointers) |
|---|---|---:|---:|---|
| Repeater 147.300 MHz | How it works, Weekly net | 4–5 | 3 | Home “Listen” line; hub settings line |
| Net time, 8:00 PM | How it works, Weekly net | 6 | 3 | Home “Listen” line; hub settings line |
| Meeting address | Home, Come visit | 3–4 | 1 | None |
| ARES vs ACS | About, ARES and ACS | 1 | 1 (the tool reports 0; its pattern misses the new one-line definitions) | None |
| Join steps | Home, Join in four steps | 5–6 | 1 (the tool reports 5; its pattern also matches dates such as “2023”) | None |
| County application | About, ACS path | 4–6 | 3 | Home step 3; Documents row |
| ICS-213 | How it works, Messages | 5–6 | 4 | Hub tile; Documents row; Exercises “Bring” line |
| Task books | About, Training path | 5–6 | 3 | Hub tile; Documents row |
| WSDOT exercise | Exercises, Next up | 4–5 | 3 | Hub “next two”; Documents form row |
| groups.io | Home, Join step 2 | 5–6 | 4 | Hub rota line; Documents source cells; WSDOT button |

### Round-2 must-fixes, done in all three

- Home is newcomer-first: no net control, offset or tone
- “Task books” links to About, with no dead “#” links
- Times read “8:00 PM”, never “2000”
- Deep links land on a cold load at 1440 and 390
- The members hub shows the next two events

## Judge scores

Three judges scored each option from 1 to 10 on seven criteria:

- **Newcomer**: a curious resident or new ham: understand it in 10 seconds, find how to visit and join.
- **Current member**: an active volunteer on a phone: net control, settings, net script, ICS-213, next exercise, task books.
- **Editor**: say-it-once discipline: what repeats, what was lost, what is still too much.

| Rank | Option | Clarity | Repetition | Completeness | Ease of use | Motivation | Member efficiency | Overall | Composite | Top pick of |
|---:|---|---:|---:|---:|---:|---:|---:|---:|---:|---|
| 1 | Open Channel (C) | 8.2 | 8.8 | 8.2 | 8.5 | 8.3 | 8.3 | 8.1 | **8.35** | Newcomer, Editor |
| 2 | Carry the Message (B) | 8.0 | 8.8 | 8.0 | 7.8 | 8.3 | 8.5 | 8.2 | **8.24** | Current member |
| 3 | Figure One (A) | 8.2 | 8.3 | 8.0 | 7.7 | 7.5 | 7.7 | 7.7 | **7.86** | — |

The composite is the mean of the seven criterion scores and sets the rank.

## The options

### 1. Open Channel (option C): composite 8.35

Dusk blue, Bricolage Grotesque with Atkinson Hyperlegible, real photos. Open: `option-c-open-door/index.html`. Words: 18,878 → **3,415** (Home 258, How it works 870, About 1,227, Members hub 121, Documents 531, Exercises 408).

**What was cut or moved**

- **Home.** Hero, What we do, Come visit, Join in four steps. Cut the This-week card, audience row, visit planner and RSVP form, “Training with partners”, “Who we serve” and “Here’s what it takes”.
- **How it works.** Chip bar cut from 9 chips to 5. Role cards became an 8-row table. Cut the month calendar, the “Where we work” map, “Your first month” and the newcomer questions.
- **About.** 26 H2s down to 9. Cut the “short version” boxes, the FAQ and the glossary (terms now show as `<abbr>` titles). ARRL levels are three lines.
- **Members.** Hub is a switchboard with a 5-week rota. Documents went from 74 rows to 37. Exercises’ 5-month agenda is now a 6-row table.
- **Chrome.** Sub-nav went from 11 items (6 dead links) to 5. Footer: six links, Contact and the safety line; no address or schedule.

**What the judges said**

- **Newcomer (top pick):** Most inviting: a concrete local headline, a real operator photo, and “Plan your first visit” as a low-commitment second step.
- **Current member:** Most scannable hub (Now and Next badges, dashed open slots), but the header scrolls away and Home is the longest on a phone.
- **Editor (top pick):** Clearest wayfinding and zero repeated phrases. The placeholder photo tile and card grids add noise.

**Still to fix in this option**

- A visible “PHOTO NEEDED” tile sits in Home’s What we do. Use a real photo or drop the card to a text row. (3 judges)
- Home is the longest on a phone (3,987px); the hero photo sits below the buttons and pushes What we do down. (2 judges)
- Atkinson Hyperlegible’s slashed zeros (8:00 PM, 147.300) read as technical to non-hams. Use plain zeros in body text. (2 judges)
- The header is not sticky and the menu button is an unlabelled icon. B’s sticky, labelled “Menu” is the model.
- Activation is a 7-card grid and a truck photo pads Forms and logs; plain lists would be tighter.
- Every Documents category table repeats its header row.

### 2. Carry the Message (option B): composite 8.24

Night-to-dawn light, a red message line, amber numbered markers, Crimson Pro with Schibsted Grotesk. Open: `option-b-lines-down/index.html`. Words: 17,246 → **3,542** (Home 259, How it works 909, About 1,274, Members hub 124, Documents 549, Exercises 427).

**What was cut or moved**

- **Home.** Hero, What we do, Come visit, Join in four steps. Cut the “one message” figure (the story is told once, on How it works), the starting-point control, the interest form, the net card, the photo, “Coming up” and the agency band.
- **How it works.** The six-step scrolling story is now a one-screen opener: 4 hops and a static diagram. The diagram hides on phones, so the net starts at 836px.
- **About.** 26 sections down to 9. The ARES vs ACS table became four one-line definitions. Cut the FAQ, glossary, “At a glance” and the mission quote.
- **Members.** Hub drops the radio card, check-in order and 8 quick links. Documents went from 74 rows to 37; “Most used” is now a filter chip. Exercises opens with “Bring”.
- **Chrome.** The night utility bar is gone. Footer is 33 words. Sub-nav has 5 live items.

**What the judges said**

- **Newcomer:** Best How it works: the 2 AM story fits one screen, then goes straight to the net. Home’s empty-sky hero and abstract headline read slower.
- **Current member (top pick):** Best on a phone: sticky labelled Menu, the most compact hub (2,165px) with column headers, filter chips that wrap.
- **Editor:** Best at saying it once; the members pages are the most economical. Red H2 rules plus amber markers feel busy.

**Still to fix in this option**

- About prints “Last reviewed Sep 26, 2026” although the legal basis still awaits SCEM review. Leave it blank until the owner reviews. (2 judges)
- On a phone the 2 AM story still takes about one screen before the net settings. Add a “Radio settings” jump link at the top. (2 judges)
- Home’s hero is mostly empty sky and the headline is abstract, so the subhead has to explain what the group is.
- Small grey text on dark navy (hero sub-lines, story captions) is low contrast.
- “See how it works” sends newcomers to the operations page; a visit link would be gentler.
- 147.300 MHz appears in the story diagram and again in the settings card just below it.
- “Open” rota slots are not linked to the “Open slot? Tell the Net Manager” line.
- A red rule under every H2 plus amber markers on most lists is decorative repetition.

### 3. Figure One (option A): composite 7.86

Lexend; pine, wheat and navy; labelled illustrated figures instead of photos. Open: `option-a-field-guide/index.html`. Words: 17,805 → **3,368** (Home 272, How it works 864, About 1,274, Members hub 107, Documents 450, Exercises 401).

**What was cut or moved**

- **Home.** Hero with Fig. 1, What we do, Come visit, Join in four steps. Cut the quiz, the four-door router, the This-week strip, six team tiles, the W7GBU story, photos, the quote band and affiliation marks.
- **How it works.** Eight figure plates became one (Fig. 2, Follow one message). The settings box is the only place with offset and tone.
- **About.** 12 sections down to 9. Cut the FAQ, glossary, ARES vs ACS table, mission statement and the ARRL levels table.
- **Members.** Sub-nav moved outside `<main>`. Hub is search, this week, the net rota and 4 tiles. Exercises dropped the month calendar and year at a glance.
- **Chrome.** Footer went from four audience columns, an address and affiliation marks to one row of links (34 words).

**What the judges said**

- **Newcomer:** Fastest to understand: Fig. 1 shows the message path at the fold. No people, and the second button leads to the densest page.
- **Current member:** Every task is as few taps as in B and C; last on phone ergonomics (non-sticky header, icon-only menu, 3-row sub-nav).
- **Editor:** Calm and roomy; the figures do the explaining. It tells the message path twice and puts the most button weight on Home.

**Still to fix in this option**

- The message path is told twice: Fig. 1 on Home and Fig. 2 on How it works. Keep one. (2 judges)
- “See how it works” sends newcomers to the densest page; “Plan your first visit” would be gentler.
- No photos of real people, which lowers warmth and trust.
- On phones the members sub-nav is a 3-row pill grid (about 150px) above every members page.
- Header is not sticky and the menu is icon-only; About runs about 13,000px on a phone.
- Home has 3 outlined step buttons on top of the 2 hero buttons: the most button weight of the three.
- Solid navy header rows on 7 Documents tables and the Exercises tables add weight; Exercises is one long column.

## Shared fixes: do once, in the copy

The judges found these in all three options. They come from `_shared/`, so one edit fixes every option.

**Members pages**

1. The hub’s “Most used” ICS-309 tile dead-ends on a “Soon” row with no form. Swap in ICS-214 or drop it.
2. Hub tiles open library rows, not the files, so the net script and ICS-213 take an extra tap. Link the tiles to the files.
3. Sep 29 is a simplex night, but the rota note just says “Simplex”, with no link to the net script, the only place the simplex frequency is.
4. On a phone the Tuesday net sits below the fold, under search and exercises, and the Net script tile is at the bottom. Put the net first.
5. “Task books” in the sub-nav goes to About, while the “ACS Task Book” tile goes to the DOCX row.

**Public pages**

1. How it works still carries member detail on a public page: the readiness table, Standing Order 1b, ICS-309 and ICS-214.
2. Home’s “What ACS asks” line restates the task-book requirements that live on About.
3. “Join ARES: One form.” is too terse; where to return the form is only on About.
4. “Third Thursday training meeting, evenings” has no time, so a newcomer cannot plan the visit.
5. “No dues” and “no gear needed to visit” now appear only on About, not on Home.
6. About is still 8,900 to 9,200px at 1440 with 9 H2s. The exam-venue table and History could be trimmed.
7. About’s “Last reviewed” line is a visible blank in A and C and an unsourced date in B. Hide it until the owner signs off.

**Launch blockers (unchanged)**

- The net-scripts link goes to a page that also links the net roster PDF.
- The ARES application’s return address is interim; join@ is not live.
- The legal-basis section needs review by Spokane County Emergency Management (SCEM).
- The role addresses (join@, ec@, webmaster@) are proposals.

## Judges' full notes

### Open Channel

**Newcomer.** C is the most inviting for a curious newcomer. The headline 'From Bloomsday to blackouts, we keep Spokane County connected' is concrete and local. A real photo of an operator at a radio shows what this is right away. The CTA pair is ideal for newcomers: Join the team (primary) and Plan your first visit (#visit). The second is a low-commitment next step that matches the brief's 'Join + Visit'. 'Come visit' uses two clear cards: From home (8:00 PM, 147.300 MHz, no license needed) and In person (address plus two dated events). The join steps are a clean numbered list. C also has the best wayfinding: How it works has a sticky chip bar (Weekly net, Activation, Messages, Exercises, Roles), About has a 'Jump to section' control on phones, and the members sub-nav uses clear pills. Its README reports 0 repeated sentences, and the message story is told once. The members hub rota cards are glanceable, though they are taller than a table on mobile. Two things detract. A visible 'PHOTO NEEDED' placeholder tile sits in Home's 'What we do' grid, the first thing a newcomer scrolls to. And Atkinson Hyperlegible's slashed zeros (8:ØØ PM, 147.3ØØ) look technical and slightly odd in body text. Content density on How it works and About matches A and B, since the copy is shared.

- A visible 'PHOTO NEEDED' placeholder on Home's 'What we do' looks unfinished to a newcomer. Use a real photo or an illustration as in the Hospitals tile.
- Slashed zeros in times and frequencies (8:ØØ PM, 147.3ØØ MHz) add visual noise for non-hams. Use a numeral style without slashed zeros for body text.
- On mobile, the hero photo sits below the CTAs and pushes 'What we do' down. Consider shrinking it or cropping it tighter.
- How it works still carries member-level detail (readiness table, Standing Order 1b, ICS-309/214, 8-row roles table) that can overwhelm a newcomer.
- About shows a visible blank 'Last reviewed ______' line.
- 'Third Thursday training meeting, evenings' gives no time, so the 'Plan your first visit' promise stops short of a plannable time.

**Current member.** C has the same content and tap counts as A and B. For scanning 'this week' at a glance, its hub is the strongest. The WSDOT row is labelled 'Now', this Tuesday's rota row has a filled 'Next' badge, and open slots are dashed green, so net control and an open slot stand out immediately. How it works has sticky section chips (Weekly net / Activation / Messages / Exercises), which suits members hopping around on a phone. About has a sticky 'Jump to section' dropdown, and #training-path lands cleanly on a readable table. It also has the fewest repeated phrases of the three (0). It falls behind B on phone ergonomics: the header isn't sticky and the menu button is an unlabelled icon. It also falls behind on Home: at 3,987px it is the longest Home on a phone, and it shows a dashed 'PHOTO NEEDED' placeholder card in What we do, which looks unfinished next to the other options. The real operator photo and 'Plan your first visit' secondary CTA give newcomers the warmest pull of the three.

- Home shows a visible 'PHOTO NEEDED' placeholder card and is the longest Home on a phone (3,987px). Use a finished image or drop the card to a text row
- Header scrolls away and the hamburger is icon-only. A labelled, sticky 'Menu' (as in B) would help members moving between pages
- SHARED: the ICS-309 'Most used' tile dead-ends on a 'Soon' row with no form
- SHARED: hub tiles go to library rows rather than to the files, adding a tap for the net script and ICS-213
- SHARED: the Sep 29 'Simplex' note gives no pointer to the net script, where the simplex frequency lives
- SHARED: the Net script tile is at the bottom of the hub instead of next to the rota and settings
- SHARED: on a phone the Tuesday net sits below the fold, under the full-width search box and exercises
- SHARED: the 'Task books' sub-nav link (goes to About) and the 'ACS Task Book' tile (goes to the DOCX row) lead different places

**Editor.** C has the clearest wayfinding. A 5-chip in-page nav on How it works lets a reader jump straight to Weekly net, Activation, Messages, Exercises or Roles. Home's secondary CTA is the lowest-commitment next step: 'Plan your first visit'. The hub's Tuesday rota as cards with Next and Open states is the most scannable members view of the three. It keeps a little more meaning than A or B: glossary terms survive as abbr titles, and the ARRL levels are 3 bullets. The real photo of an operator gives newcomers a human pull. Weaknesses: the visible 'PHOTO NEEDED' tile on Home is noise. Activation is a 7-card grid, and a truck photo sits in Forms and logs, where plain lists would be tighter. Each Documents category table repeats its header row. The slashed/dotted zeros in times and frequencies look technical to newcomers.

- A visible dashed 'PHOTO NEEDED' placeholder tile on Home draws the eye
- The 7 activation steps are a 2-row card grid instead of a compact list, and the truck photo adds height to Forms and logs
- Each Documents category table repeats its Document/Format/Source header row
- Slashed/dotted zeros in times and frequencies ('8:00 PM', '147.300') read as technical to newcomers
- The How it works chip bar is one more navigation layer, useful but extra chrome
- Shared: About is still about 8,900px, with 9 H2s and many H3 blocks
- Shared: the readiness table and Standing Order 1b on How it works are member-operational detail on a public page
- Shared: Home's 'What ACS asks' duplicates the task-book requirements on About
- Shared: 'Join ARES: One form.' is too terse, because the return instructions appear only on About#ares-path
- Shared: the About header shows a visible 'Last reviewed ______' placeholder

### Carry the Message

**Newcomer.** Home is as lean as A's (259 words, 2 CTAs), but the 10-second read is weaker. The headline 'When other systems go down, Spokane County still has a voice' is more abstract. The hero image is a mostly empty dusk sky, so the subhead has to explain what the group is. The small grey text on dark navy is harder to read. 'What we do' as a red line with four stops is elegant but sparse. 'Come visit' in a white card on the dawn gradient and the four-step timeline are clear. B has the strongest How it works for a newcomer. The '2 AM' four-hop scenario and its diagram fit in one desktop screen, end with 'Radio still works. Because people practice.', and then go straight into the practical net section. The story is told once on the site (not repeated on Home), so B has the best repetition discipline. Members pages are the most compact and efficient: the Exercises page is two columns and only 2,218px tall, Documents has a left filter rail, and the hub's net bar is high-contrast. The night theme makes a strong mood but can feel heavy and dramatic to a casual visitor. About hard-codes 'Last reviewed Sep 26, 2026' (today), which is flagged data-verify but reads like an invented fact.

- The Home hero photo carries no information (empty sky), and the headline is abstract. A newcomer relies on the subhead to learn what this is.
- There is low-contrast small grey text on dark backgrounds (hero sub-lines, How it works opener captions).
- On mobile, the How it works story still precedes the net by about one screen, and the page runs roughly 7,400px at 390.
- About's 'Last reviewed Sep 26, 2026' is a date the page asserts without a source. It should stay blank until the owner reviews.
- Like A, the secondary CTA sends newcomers to the operations-heavy How it works page instead of a visit.
- The dark and dramatic tone may read as intense rather than welcoming to a curious resident.

**Current member.** B has the same content and tap counts as A and C, and it is the best of the three for a member on a phone. The header is sticky and has a labelled 'Menu' button, so you can reach navigation from anywhere. The hub is the most compact (2,165px) and the only one that shows the 'Tuesday / Net control / Note' column headers on a phone, so 'who is net control this Tuesday' needs no inference. The settings sit in a high-contrast dark box with a Copy button. On Documents, the filter chips wrap, so all nine categories show without sideways scrolling, and there's a 'Showing all 37 documents' count. About has a floating 'On this page' button for the long reference page. Deep links land cleanly under the sticky header, and target rows are highlighted. Against it: How it works opens with the 2 AM story, about one phone screen before the settings box. It is inside the budget, but a member who lands on How it works rather than #nets has to scroll past it (the hub covers W7GBU, so this is minor). About also shows 'Last reviewed Sep 26, 2026' even though the legal basis is still pending SCEM review. That claims a review that hasn't happened; A and C leave the date blank. For a newcomer, the dark photo hero and red-line 'What we do' still pull well at 259 words.

- About prints 'Last reviewed Sep 26, 2026' while the legal basis is still awaiting SCEM review. Leave it blank as A and C do
- How it works: the 2 AM story takes about 800px on a phone before the settings box. It's fine for newcomers, but consider a small 'Radio settings' jump link at the top for members
- 'Open' rota slots aren't linked to the 'Open slot? Tell the Net Manager' line (A and C link them)
- SHARED: the ICS-309 'Most used' tile dead-ends on a 'Soon' row with no form
- SHARED: hub tiles go to library rows rather than to the files, adding a tap for the net script and ICS-213
- SHARED: the Sep 29 rota note 'Simplex' gives no pointer to the net script, the only place the simplex frequency is
- SHARED: the Net script tile is at the bottom of the hub instead of next to the rota and settings
- SHARED: on a phone the Tuesday net is below the fold, under search and exercises
- SHARED: the 'Task books' sub-nav link (goes to About) and the 'ACS Task Book' tile (goes to the DOCX row) lead different places

**Editor.** B is the best example of 'say it once'. The message story appears only once, at the top of How it works, as a single screen of story plus diagram, and the net settings start at about 877px. Home carries no figure. The visitor tip sits inline next to step 4 of the net order, which is good information design. The members pages are the most economical of the three. Exercises is a 2-column grid of 2,218px, the shortest. Documents has the lightest table styling (no boxes, no repeated header rows) and a filter sidebar. The hub says the next meeting in one line. Weaknesses: a red rule under every H2 plus amber markers throughout feel a little busy. 147.300 MHz appears in the story diagram just above the settings card. B has the highest word total (3,542, a negligible gap). Motivation is strongest here: the dark hero line and the '2 AM' scenario make the purpose felt without adding bulk.

- A red underline on every H2 plus amber numbered markers on most lists add decorative repetition
- 147.300 MHz appears in the story diagram and again in the settings card just below it
- The 2-column Exercises grid squeezes the Winlink assignments table text on desktop
- On the members pages, main's min-height pushes the footer below the fold on tall viewports (the footer is missing from the desktop shots). This is cosmetic
- Highest total words of the three (3,542), though still about 20% of round 2
- Shared: About is still about 9,200px, with 9 H2s and many sub-blocks. The exam-venue table and History could be trimmed or collapsed
- Shared: the readiness-levels table and Standing Order 1b are member detail on a public page
- Shared: Home's 'What ACS asks' line repeats the task-book requirements
- Shared: 'Join ARES: One form.' leaves the reader without the key action detail
- Shared: the About header shows a visible 'Last reviewed ______' placeholder

### Figure One

**Newcomer.** Newcomer lens: A is the fastest to understand. At the desktop fold you see the headline 'When phones go quiet, radio still gets the message through', a one-sentence subhead, Join (primary) and See how it works (secondary), plus the Fig. 1 drawing that shows the message path in 3 labels. You can tell what the group is and what to do in under 10 seconds. The rest of Home is calm and quick to scan: four illustrated 'What we do' items, 'Come visit' with a large 147.300 MHz / Tuesdays 8:00 PM and two dated in-person events, then 'Join in four steps' with one button per step. The page is 272 words and 2,825px tall. The single-column pages are well spaced, and About's numbered sections with a sticky TOC make a 1,274-word reference page manageable. The members hub is a clean switchboard (search, this week, the net rota table, 4 tiles). Documents' chip filters and compact tables work well. The copy is shared with B and C, so the three differ mainly in layout and tone. A's one repetition-in-spirit: the message path is told twice, as Fig. 1 on Home and Fig. 2's four panels on How it works. Motivation is a little lower than C because there are no people in it (everything is illustrated). Its secondary CTA also sends a curious newcomer to How it works, which is still an ~860-word operations page with readiness levels, Standing Order 1b and ICS-309/214, where a lower-commitment 'visit' would be gentler.

- Home's secondary CTA 'See how it works' sends newcomers to the densest newcomer-facing page. 'Plan your first visit' (#visit) would be gentler.
- The message path appears twice (Home Fig. 1 and How it works Fig. 2). Consider making Fig. 1 the only telling, or dropping Fig. 2's panels to a line.
- How it works is still member-operational for a newcomer: readiness-level table, Standing Order 1b, precedence, ICS-309/214 logs. It could move behind a <details> or to members pages.
- No photos of real people, which lowers warmth and trust.
- About shows a visible blank 'Last reviewed ______' line, which looks unfinished.
- 'Third Thursday training meeting, evenings' has no time, so a newcomer can't actually plan to attend.

**Current member.** I judged this as a current member on a phone (tested with a real 390x844 emulation and deep links). The content is almost word for word the same across all three options. Each is 3,368 to 3,542 words, well under every page budget, and every fact has one home. So the ranking comes down to how easy each is to use on a phone. All five member tasks are still findable in A, with the same tap counts as B and C: menu, For Members, then about one scroll to net control NZ2S for Sep 29 and 'W7GBU 147.300, +600 kHz, 100 Hz' (2 taps). Net script and ICS-213 take 4 taps: a hub tile lands on the library row (highlighted), then you tap the file. Next exercise: WSDOT 'Happening now' and SET Oct 3 are on the hub, and 'What to bring' goes to the Bring line (3 taps). Task-book requirements: the 'Task books' sub-nav link goes to about#training-path, which lands correctly with a readable Once/Regularly list (3 taps). A is last because of phone ergonomics, not content. The header isn't sticky and the menu button is an unlabelled icon. The members sub-nav is a 2-column pill grid three rows deep that sits on top of every members page. About is the tallest of the three on a phone (about 13,000px). The Fig. 1 hero is attractive, but it pushes Home content down.

- Members sub-nav on phones is a 2-column pill grid three rows tall (~150px) above every members page, which pushes the Tuesday net further below the fold
- Header scrolls away and the hamburger is icon-only, so deep in About (~13,000px on a phone) the only way to get around is the sticky TOC or a long scroll back up
- SHARED: the hub's 'Most used' ICS-309 tile goes to a 'Soon' row with no form. A most-used tile that dead-ends should be dropped or swapped for ICS-214
- SHARED: hub tiles land on library rows, not on the files, so the net script and ICS-213 are 4 taps from Home. Link the tiles straight to the file
- SHARED: this Tuesday (Sep 29) is a simplex-start night, and the rota note just says 'Simplex' with no link to the net script, the only place the frequency lives
- SHARED: the Net script tile sits at the bottom of the hub, far from the rota and settings where net control needs it
- SHARED: on a phone the Tuesday net settings are below the fold, under the search box and exercises. On net night they are the member's top need
- SHARED: 'Task books' in the sub-nav leaves the members section for About, while the 'ACS Task Book' tile goes to the DOCX row. Two destinations for nearly the same label

**Editor.** Huge improvement on round 2: the site went from 17,805 to 3,368 words, every page is under its budget, and Home is newcomer-first with 3 H2s. All three options share the same _shared copy, so the differences between them come from presentation. A is calm and roomy. The illustrations explain the idea without extra words: Fig. 1 on Home shows the path of a message, and the What we do icons do the same job. Weaknesses: A repeats the message-path idea as a second figure (Fig. 2 on How it works). Home has the most button-weight CTAs (2 in the hero plus 3 outlined step buttons). Heavy navy table headers repeat across the Documents and Exercises tables. A also has the longest How it works (5,563px) and a single-column Exercises page (3,107px). Nothing important was lost beyond what all three dropped.

- Home Fig. 1 (the path of a message) and How it works Fig. 2 (Follow one message) show the same idea twice
- Home join steps use 3 outlined buttons on top of the 2 hero buttons, which is more CTA weight than B or C
- Solid navy header rows on each of the 7 Documents category tables and on the Exercises tables add visual weight
- The hub's 'What to bring' link sits orphaned under the SET entry
- Exercises is one long column (3,107px) where it could be a compact grid
- Shared: About is still about 9,150px on desktop, with 9 H2s and around a dozen H3 blocks (legal table, 2 leadership tables, task-book table, exam-venue table, timeline)
- Shared: 'When the county calls' (7 activation steps, the readiness table and Standing Order 1b) is operational member detail on a public page
- Shared: Home's 'What ACS asks' line restates the task-book requirements that live on About#training-path
- Shared: 'Join ARES: One form.' is too terse to act on, because where to return the form appears only on About
- Shared: the About header shows a visible 'Last reviewed ______' placeholder
- Shared: answers newcomers need (no dues, no gear needed to visit) now appear only on About#joining, not on Home
