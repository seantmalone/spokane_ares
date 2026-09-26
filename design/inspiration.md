# Design inspiration survey: Spokane County ARES/RACES (ARES-ACS) redesign

Surveyed 2026-09-25. I checked 44 live sites with curl (headings, CTAs, fonts, colours, body text) and captured 41 of them as headless-Chrome screenshots, one at 1440x1000 and one at 1440x3600 (`<slug>.png` and `<slug>-tall.png` in `design/inspiration/`). For eight of them I also read the recruitment subpages (the join and volunteer pages).

Four sites couldn't be used. ocraces.org (OC RACES), kcacs.org (King County ACS) and kingcountysar.org all timed out. redcross.org blocks headless browsers, so I have its text but no screenshot. spokares.org itself also timed out today. The earlier wget mirror is in `research/raw/`.

---

## 1. The short version: patterns worth stealing

1. **Route people by audience right away.** Civil Air Patrol puts an "I am a [select] → GO" picker and a "Find your local squadron [ZIP]" box directly under the hero. Make Me A Firefighter opens with two doors: "Recruiting for your department?" and "Want to volunteer?". Spokane has four clear audiences, so build four doors: *I'm licensed* / *Not licensed yet* / *I'm a member* / *I'm an agency partner*.
2. **Say what you are in one plain sentence, and name who sponsors you.** Seattle ACS: "~150 trained volunteers serving the City of Seattle, sponsored by the Seattle Office of Emergency Management… licensed amateur radio operators and registered state emergency workers." WaEmcomm: "Whether they're called ARES, RACES or ACS, amateur radio teams… provide backup and/or alternate communications."
3. **Show the joining path as numbered, time-boxed steps.**
   - NSW SES: Apply → Meet with us → Criminal history check → Get started.
   - Team Rubicon: Register → Profile → Background check → TR101 → Get your grey shirt.
   - Crisis Text Line: "20-minute application → 15-hr self-paced training → first shift."
   - Seattle Mountain Rescue states the commitment outright ("respond to at least 5 missions per year, goal of 10").
4. **Offer a zero-commitment first step.** "Come listen or check in as a visitor" to the weekly net (Seattle ACS), "meetings open to guests" (BAMRU, Shoreline ACS), or sign up for one public-service event (Sacramento ARES: century ride, triathlon, State Fair station). Only ham groups have this. A newcomer can *hear* the team tonight at no cost.
5. **Give the unlicensed a path of their own.** LA County DCS: "Earning an entry-level FCC license isn't hard… like your first written driver's test. All the FCC exam questions are known ahead of time." HamStudy starts with a task ("Choose an exam to study for: Technician 2026–2030"). Seattle ACS runs a two-day Technician course and advertises it in a yellow announcement bar.
6. **Put credibility up front, and make it concrete.** Useful signals, with who does it:
   - A stats strip. BAMRU: "45+ yrs / 20+ ops a year / 50+ members / 16,000+ hrs". NSP: "30,000+ members / 1938 est."
   - The served agencies, by name.
   - Signed support agreements. Clark County lists its MOUs.
   - Awards. Pierce County was "Volunteer Group of the Year 2017 & 2006".
   - A media feature. Seattle ACS embeds a Seattle Channel video.
   - Accreditation. SMR is a founding member of the Mountain Rescue Association.
   - Named exercises. WaEmcomm cites Cascadia Rising.
7. **Show live operational status.**
   - Team Rubicon runs an alert bar reading "RESPONDING. Team Rubicon volunteers are responding to…".
   - Santa Clara has a "Service Interruptions" box (AA6BT repeater off-line).
   - NSW SES has a current-warnings panel.

   The status makes the site feel operational, not archival.
8. **Present nets as structured cards.** Each card shows day, time, frequency, offset, tone, repeater, EchoLink and preamble. The good examples are Sacramento's "Weekly Net" widget, FCARES ("Tuesday 7:15 pm, 147.570, PL 114.8") and Snohomish ("Tuesday 20:00, 146.925 −, 156.7, Granite Falls repeater").
9. **Keep the member hub separate, organized by task, and working offline.** Santa Clara's home page is a task dashboard: Calendar & Signups, Profile & Records, Training & Practice, Operations, Credentials. Every link has a one-line description ("Go Kit and Forms: things every operator should have"). It also registers a service worker that serves an offline copy, which is ideal for emcomm. CFA keeps "Members Online" in a utility bar, away from the public nav.
10. **Offer roles and a progression ladder.** NSW SES has a role catalog (flood rescue operator, community engagement, storm damage…). Pierce County has four qualification levels (Candidate → Home-based Operator → …). Santa Clara has a credentialing handbook with "Your Credential Progress". Visible growth motivates people and tells newcomers they don't need to be experts on day one.
11. **Use documentary photos of real members doing the work.** Show vests, EOC desks, comm vans and public events, across a range of ages. SF NERT's giant crowd photo, CFA's older women volunteers and Clark County's field slider all do this. NVFC's duotone treatment makes uneven volunteer photos look consistent. **Don't** lead with close-ups of radio gear. They speak only to people who are already hams.
12. **Give people something to belong to.** Team Rubicon has "Greyshirts". Snohomish ACS sells authorized jackets, hats and patches. People join an identity, not only a duty.

---

## 2. Eight best reference URLs

| # | URL | Why |
|---|-----|-----|
| 1 | https://www.ses.nsw.gov.au/volunteer (+ home) | Best recruitment structure in the set: role catalog, 3 pathways, 4-step visual process, "Apply to volunteer". A bold orange block overlaps a real team photo, with a pixel motif taken from the logo. |
| 2 | https://teamrubiconusa.org/ and /volunteer/ | Live "RESPONDING" bar, a slab-serif statement headline ("Disasters don't wait. Neither do we."), a named identity (Greyshirts), and a 5-step path that ends with getting the shirt. Documentary photography throughout. |
| 3 | https://www.gocivilairpatrol.com/ | The "I am a…" persona router and ZIP finder under the hero. One quantified claim ("90% of inland SAR"). Join menu split by persona. |
| 4 | https://www.seattlemountainrescue.org/join/ | Hero is a quote from someone they rescued. Candid about prerequisites, commitment and the timeline (info video → quiz → hike → interview), with a "notify me when applications reopen" signup. |
| 5 | https://www.crisistextline.org/volunteer/ | Copy framed around what the volunteer gains ("Free and Flexible… Training" chip), steps with time boxes, and the CTA "Begin Your Volunteer Application". |
| 6 | https://www.scc-ares-races.org/ | Best member-side information architecture of any emcomm group: task dashboard, repeater status box, credential progress, offline service worker. |
| 7 | https://www.seattleacs.org/ (+ /join) | Best emcomm recruiting content: sponsor clarity, a media-feature video, a visitor invitation to the net, and join steps that include a Technician class for the unlicensed. |
| 8 | https://scvsar.org/ | A clean, modern Washington peer with the same sheriff-affiliated model. Dark photo hero with two CTAs (Join / Donate), stats in the body copy, footer grouped by audience. A realistic quality baseline. |

Honourable mentions:
- **SF NERT** (sf-fire.org/nert): a FAQ written in newcomers' own words, plus the crowd photo.
- **BAMRU**: the stats strip.
- **LA County DCS**: licensing copy that takes the mystery out.
- **Marin RACES**: its self-qualifying question list.
- **Harris County ARES**: a quadrant map with the EC for each area, and "we are NOT first responders" expectation-setting.

---

## 3. Site-by-site notes

Each entry covers what works (✓), what doesn't (✗), and **one idea to steal**.

### A. Emcomm groups (ARES / ACS / RACES)

**Santa Clara County ARES/RACES/ACS**: https://www.scc-ares-races.org/ (`scc-ares-races.png`)
- ✓ The home page is a member dashboard of 8 cards, each with 3–5 deep links and a one-line description.
  - A "Service Interruptions" box sits under the header.
  - The calendar list uses date chips (SEP 27, OCT 10).
  - PDF icons mark documents.
  - The credentialing program is versioned (handbook v3.2, performance standards v2.2.1) and shows "Your Credential Progress".
  - It says "500+ volunteer radio operators".
  - It registers a service worker (`/offline.js`) that serves an offline copy.
- ✗ Almost no recruitment. "Getting Started / FCC License Info" is buried under About. The header is three seals with no people. Photos: none. Purple links on near-black background. System fonts.
- **Steal:** a repeater/service status box, plus offline (PWA) caching of the member essentials: net schedule, frequencies, forms, go-kit list.

**Seattle ACS**: https://www.seattleacs.org/ (`seattle-acs.png`, `-tall`)
- ✓ Yellow announcement bar ("Ham radio class and licensing opportunity! More info").
  - The intro names the sponsor (Seattle OEM), team size (~150), start year (1993) and member status (licensed hams *and* registered state emergency workers).
  - Embeds a Seattle Channel video, "How Seattle keeps talking when phones & power go out".
  - The Monday Night Net block invites visitors to listen or check in and gives the repeater and EchoLink details.
  - The join page is 4 steps: IS-100/200/700, a license of any class, an online application, and committing to nets. Unlicensed visitors get a 2-day Technician course and a list of study resources.
- ✗ A stock Google Sites template, lists of text links, and an embedded Google Calendar iframe. Lots of dead whitespace below the fold.
- **Steal:** "Tune in tonight: listen or check in as a visitor", plus a third-party media video as proof.

**Snohomish County ACS/ARES (WA7DEM)**: https://www.wa7dem.info/ (`snohomish-acs-wa7dem.png`)
- ✓ Tells the ACS+ARES merger story: "single contact for all emergency communications at the county level". Served agencies are named (DEM, member cities, county hospitals, Red Cross NW WA).
  - Net details are inline.
  - Good member resources: code plugs, radio manuals, quick reference guides, ACES/ICS/COML training.
  - The join page includes **"After I join, what do I do?"**: check into the Tuesday net, attend the monthly meeting, build a go-kit, respond through the Resource Net.
- ✗ The hero is a stock photo of Icom radios. Joining means print, sign and bring paper forms. The Google Sites embeds leave loading spinners.
- **Steal:** the "After I join, what do I do?" section, and the unified ACS+ARES story. Spokane has the same dual "ARES - ACS" brand.

**Clark County ARES/RACES (CCARES)**: https://www.ccareswa.org/ (`clark-ccares.png`)
- ✓ Tagline "When All Else Fails… Amateur Radio Works". Real field photos: orange ARES/RACES vests, a teenager taking part.
  - The nav has "Support Agreements" (MOUs), "CRESA" (the served agency), "Current River Conditions" and "Get Your Amateur Radio License".
- ✗ A login box in the home sidebar, a Drupal mini-calendar, and stale news (a newsletter from 2022). Announcements are in purple italics.
- **Steal:** a public "Support Agreements / Served Agencies" page as proof for partners.

**Pierce County ARES (District 5)**: https://piercecountyares.net/ (`pierce-ares.png`)
- ✓ Credibility line: "Pierce County Volunteer Group of the Year 2017 and 2006".
  - A 2026 membership program with four qualification levels (Candidate, Home-based Operator, …).
  - The sidebar collects net preambles, fillable check-in sheets and Winlink net instructions.
- ✗ Four rows of black nav (~25 items), mixing public pages with admin chores ("Forgot or Change Your Password", "Renew Emergency Worker ID"). Typos in the nav ("Emerrgency", "Articale"). Twenty Eleven theme.
- **Steal:** a visual ladder of qualification levels, and awards used as trust badges.

**Redmond ARES**: https://redmond-ares.org/ (`redmond-ares.png`)
- ✓ A "Why do we need ARES?" section in plain language: storms, fires, earthquakes, or an errant construction crew can cut power, internet, roads and bridges, and "we could be on our own for days… no way to call 911". A "GET INVOLVED" button at the top.
- ✗ The site builder's ad bar, thin grey Quicksand/Muli text, no photos of people, and ® symbols everywhere.
- **Steal:** a "what a bad day in Spokane looks like" scenario (Cascadia, wildfire, ice storm) to explain *why*.

**Shoreline ACS**: https://www.shorelineacs.org/ (`shoreline-acs.png`)
- ✓ Built with MkDocs Material, so it has built-in search, a left table of contents, and easy markdown upkeep.
  - A photo of the comm van at a community movie night.
  - "Visitors are always welcome at our monthly meeting", with the address.
  - Equipment, Comm Van and Dashboard pages.
- ✗ Reads like software documentation, with a weak pitch.
- **Steal:** docs-as-code for the member reference library, with fast full-text search. The comm van photo also makes the group tangible.

**Multnomah County ARES**: https://multnomahares.org/ (`multnomah-ares.png`)
- ✓ Calm serif typography and letter-spaced small-caps nav. The "Upcoming Activity" agenda uses grey date tabs with event and time underneath, which is very scannable. "We invite all interested parties to attend our monthly meetings and check into our regular nets."
- ✗ No images and no CTA. Looks like 2010.
- **Steal:** the agenda-list pattern for events. It beats a month grid on mobile.

**LA County Disaster Communications Service (Sheriff's DCS)**: https://lacdcs.org/ (`lacdcs.png`)
- ✓ A header collage of the Sheriff comm truck, a tower, and night helicopter ops, all authentic and dramatic.
  - The lead article, "How to get a ham radio license!", uses the driver's-test analogy and notes that the exam questions are published in advance.
  - It ends with a numbered list: read the book → workshop → self-study online → exam (via Zoom) → **get active on nets** → helpful groups.
- ✗ A blog-first home page, 13px text, and a dated theme.
- **Steal:** reassuring licensing copy ("not hard; the questions are known ahead of time") and an ordered license path.

**Marin County RACES/ACS**: https://www.marinraces.org/wp/ (`marin-races.png`)
- ✓ Self-qualifying questions: "Interested in communications technologies? At least 14? Licensed? A Marin County resident? Looking for a way to help? … may be the place for you!"
  - The nav includes "After Action Reports" and "Field Operations Guide", which signal competence to agencies.
- ✗ Hero is an S-meter close-up with a translucent box listing three names. The questions are stacked with huge gaps. "Operates under the authority of Marin County Fire" is buried.
- **Steal:** turn the questions into an interactive "Is this for me?" quiz that routes to the right next step.

**NYC-ARECS**: https://www.nyc-arecs.org/ (`nyc-arecs.png`)
- ✓ Explains that RACES is an FCC protocol administered by DHS/FEMA, next to a wall of affiliation logos (RACES, FCC, DHS, FEMA, SKYWARN, NYC OEM Partners in Preparedness). Member photos in orange vests.
- ✗ Table layout circa 2005, bevelled image buttons, and a Donate button above the explanation.
- **Steal (the idea, not the look):** an affiliation strip with a one-line caption explaining each relationship.

**Loudoun County ARES**: https://lcares.org/ (`loudoun-ares.png`)
- ✓ Shows the call sign WA4RES as part of the brand. "Every licensed amateur… is eligible."
- ✗ Very thin: a single paragraph and Join/Register links.
- **Steal:** use the club call sign as a typographic brand element.

**Sacramento County ARES**: https://sacramentoares.org/ (`sacramento-ares.png`)
- ✓ Sidebar "Weekly Net" card: Mondays 7:00 PM, N6ICW 147.195 (+) 123.0 Hz, K6IS 145.190 (−) 162.2 Hz.
  - Posts carry ANNOUNCEMENT and EVENT tags.
  - Volunteer signups for public-service events (Century Challenge, triathlon, State Fair special-event station).
- ✗ A live clock in the header, a news-magazine template, and a placeholder graphic ("EVENT") instead of photos.
- **Steal:** a public-service event signup as the easiest first taste of the work.

**Colorado Section ARES**: https://coloradoares.org/ (`colorado-ares.png`)
- ✓ A section map of regions and districts, and a clear "Join ARES" entry.
- ✗ A mega-menu that dumps the whole org chart (Region 1 District 1…).
- **Steal:** a map-based "your team / your area". For Spokane, show county zones, served hospitals and EOCs, and repeater coverage.

**Harris County ARES (TX)**: https://stxd14ares.org/ (`harris-ares.png`)
- ✓ H1: "Serving Our Community Through Communications".
  - Honest expectation-setting: "ARES members are NOT 'First Responders' but do receive specialized FEMA, CERT and ARRL training."
  - Served agencies and public events are named.
  - A county map split into four quadrant units, each with its EC's name and call sign.
- ✗ Long text blocks, and leadership shown as a list.
- **Steal:** the "what you are / what you aren't" clarity, and a leadership-by-area map.

**Williamson County ARES (TN)**: https://wcares.org/ (`williamson-ares.png`)
- ✓ "Library & Special Interests": DMR, EmComm DIY, portable ops, QRP, satellite, SKYWARN, APRS, CW, fox hunts. Technician, General and Extra class pages. Field Day photo galleries.
- ✗ A featured-post hero that is just a SKYWARN logo. A red "Member Login" competes with "Join Us".
- **Steal:** skill and interest tracks as the hook for *already-licensed* hams. It makes the group feel like fun, not only duty.

**Foster City ARES**: https://fcares.org/ (`fcares.png`)
- ✓ The hero includes "**Next Meeting: September 14**". Real booth photo. Minimal nav: Weekly Radio Net / Monthly Meetings / More.
- ✗ A GoDaddy template, a cookie modal, and a product photo of a radio.
- **Steal:** "Next net / next meeting" live in the hero.

**Washington State ARES (WaEmcomm)**: https://www.wastateares.org/ (`wastate-ares.png`)
- ✓ The single best one-sentence explanation of ARES/RACES/ACS. Covers Cascadia Rising, EOC-to-EOC exercises, the State Comm Plan, and local contacts by county.
- ✗ Google Sites, text only.
- **Steal:** borrow that sentence. Link Spokane's part in statewide exercises as proof.

**Clallam County ARES**: https://clallamares.org/ (`clallam-ares.png`)
- The baseline. Plain HTML with the EC's name and call sign, meetings "in the basement of the county EOC", and "Getting Started in Public Service Pt 1/2" articles.
- **Steal:** an onboarding article series ("Your first 30 days").

**Not useful:** kcares.org and mdares.org returned JavaScript shells or placeholder pages. Their screenshots are kept only for completeness.

### B. National and state ham bodies

**ARRL ARES**: https://www.arrl.org/ares (`arrl-ares.png`)
- ✓ The canonical explanation: "When All Else Fails®… a station can be set up almost anywhere in minutes… a wire antenna in a tree". A "Find an ARES Group" pill button. The ARES Plan and the Standardized Training Plan Task Book (levels). The 90th-anniversary mark, "Ready · Responsive · Resilient".
- ✗ Portal chrome, store ads in the sidebar, and CTAs that are all PDFs.
- **Steal:** tie Spokane's local ladder to the ARRL Task Book levels so the national standard backs it. Use the ®-marked names correctly.

**ARRL Getting Licensed**: https://www.arrl.org/getting-licensed (`arrl-getting-licensed.png`)
- ✓ "Why should I get licensed?", the three license classes, a class finder and exam-session search.
- ✗ Dated.
- **Steal:** deep-link to the exam-session and class finders instead of re-explaining them.

**HamStudy.org**: https://hamstudy.org/ (`hamstudy.png`)
- ✓ Task-first ("Choose an exam to study for: Technician (2026–2030)"), four icon-led explainers (What is ham radio? / How do I get licensed? / Study tips / FAQ), "Find a Session", and it's free.
- **Steal:** make HamStudy the "Study free tonight" button in the unlicensed path.

### C. Adjacent volunteer and emergency organizations

**Team Rubicon**: https://teamrubiconusa.org/ (`team-rubicon.png`, `-tall`)
- ✓ A dismissible "RESPONDING…" status bar. Video hero with a heavy slab-serif headline, "Disasters don't wait. Neither do we."
  - Two split CTAs in the header: VOLUNTEER (charcoal) and DONATE (red #c32033).
  - Aerial documentary photography of real work.
  - The volunteer page, "Step Into the Arena as a Greyshirt", says "anyone can wear a grey shirt, no matter how much time you have to give" and lays out 5 steps (Roll Call → profile → background check → TR101 → shirt & deploy), with a volunteer quote.
- ✗ Donations dominate the home page, and the video hero rendered blank in headless.
- **Steal:** a live status bar, and a named member identity with a tangible reward at the end of the path (a vest or patch).

**Civil Air Patrol**: https://www.gocivilairpatrol.com/ (`civil-air-patrol.png`, `-tall`)
- ✓ A router bar: "I am a [select] GO" and "Find your local squadron [ZIP] GO".
  - The hero rotates through three pillars (Serving Communities / Saving Lives / Shaping Futures) with a strong fact: "90% of inland search and rescue in the U.S."
  - The Join menu is split by persona (Active Adult, Cadet, Friend) and includes Member Benefits.
- ✗ Carousel, dense nav, and a cookie wall.
- **Steal:** the persona router, which maps directly to Spokane's four audiences.

**Seattle Mountain Rescue**: https://www.seattlemountainrescue.org/ (`seattle-mountain-rescue.png`)
- ✓ The hero is a full-bleed rescue photo with a quote from someone they rescued ("…a bad day for me didn't become an even worse day for my family") and a "JOIN US!" button. "Founded in 1948", accredited by the MRA, Candid transparency seal.
  - /join spells out prerequisites, commitment (5–10 missions a year, 16 hrs of rigging), the process (info-session video → short quiz → hike → interview), the recruiting cycle, and "notify me".
- ✗ Carousel dots and a thin body.
- **Steal:** radical honesty about commitment, and a "notify me" signup for people who arrive between cycles.

**Bay Area Mountain Rescue Unit**: https://www.bamru.org/ (`bamru.png`, `-tall`)
- ✓ A stats stack in the hero (years, operations a year, members, hours). Sheriff affiliation and FEMA/Cal OES Type I status. The Get Involved section says "attend one of our meetings, which are open to guests" and admits the commitment is significant.
- ✗ Thin, letter-spaced type over a busy photo. Poor contrast.
- **Steal:** a 4-number credibility strip, set in a heavy face with enough contrast.

**Snohomish County Volunteer SAR**: https://scvsar.org/ (`scvsar-tall.png`)
- ✓ A modern utility-CSS look: dark landscape hero, bold sans H1, "Saving Lives in Snohomish County and Beyond", two CTAs (Join Our Team, outlined; Donate Now, filled sky blue).
  - "Who We Are" (300 members, 120 missions a year, 9,500 hours) sits beside "Join our team".
  - "In an emergency, always dial 911."
  - Footer columns: About / Resources / Get Involved / Members.
- ✗ Very little below the fold. The landscape photo shows no people.
- **Steal:** the footer grouped by audience, and a two-CTA hero.

**SF NERT (SF Fire)**: https://sf-fire.org/nert (`sf-nert.png`)
- ✓ A huge, diverse crowd photo of volunteers in green helmets and vests alongside fire officers. "Do the Most Good for the Most People!" and "Get Prepared. Get Involved!"
  - FAQ headings in the newcomer's own words: "Do I need any previous experience?", "I'm new. How do I get started?", "How long is training?", "I missed a class. Can I still graduate?"
  - A red strip: call 911 / text your ZIP for alerts.
- ✗ Part of a generic city fire site.
- **Steal:** a FAQ in first-person newcomer voice, and one big group photo that shows scale and diversity.

**CERT-LA (LAFD)**: https://www.cert-la.com/ (`cert-la.png`)
- ✓ Three persistent action buttons for the top tasks (CERT Calendar, Enroll in a Class, Schedule Training). A "LAFD CERT Statistics" block.
- ✗ Bevelled green buttons, a video hero stuck on its spinner, and clutter.
- **Steal:** top-task buttons that are always visible, done with restraint.

**NSW SES**: https://www.ses.nsw.gov.au/ and /volunteer (`nsw-ses.png`)
- ✓ A government design system: an orange (#f48703) headline block overlapping a real team photo, a pixel-square motif from the logo, navy #002664, and Public Sans-style type. A current-warnings panel.
  - Volunteer page: "Whatever your skills, experience or interests, join thousands…" Three pathways (Unit / Community Action Team / Spontaneous), individual role pages, and 4 steps. CTA "Apply to volunteer".
- ✗ Few testimonials or stats on the volunteer page.
- **Steal:** the role catalog and the graphic motif taken from the logo. For Spokane the roles could be net control, field/shelter operator, EOC station, Winlink/digital, public-event comms, trainer, logistics/tech.

**CFA Victoria (Volunteers and careers)**: https://www.cfa.vic.gov.au/volunteers-and-careers (`cfa-volunteers.png`)
- ✓ Localization ("Set your location for local info"). "Emergency? Dial 000" in the utility bar, with "Members Online" kept separate. A teal hero panel with quick-link buttons. Photos of older women volunteers, which breaks the stereotype.
- ✗ A location modal on load, and heavy navigation.
- **Steal:** a utility bar for members and emergencies, and casting photos of the people you actually want to recruit (retirees, women, younger hams).

**Crisis Text Line (Volunteer)**: https://www.crisistextline.org/volunteer/ (`crisis-text-line.png`)
- ✓ "Volunteer Virtually at Crisis Text Line", a "Free and Flexible Crisis Counselor Training" chip, and a single CTA: "Begin Your Volunteer Application".
  - The benefits are framed around the volunteer: skills, community, from home.
  - A 3-step journey with time boxes (20-min application → 15-hr training → first shift; 200-hr commitment), testimonials, and a table-of-contents card.
- ✗ Long SEO-style page.
- **Steal:** a time-boxed journey ("~2 weekends to your license → 1 evening to register → first net check-in").

**Make Me A Firefighter (NVFC)**: https://www.makemeafirefighter.org/ (`make-me-a-firefighter.png`)
- ✓ The entry is split by audience, with a ZIP-code opportunity finder and a big group photo. The headline stat is "Seven out of ten firefighters are volunteers."
- ✗ The split is delivered as a modal that blocks the page.
- **Steal:** a stat that reframes the ask. The Spokane version would be something like "When cell and internet fail, these N volunteers keep the county talking."

**NVFC**: https://www.nvfc.org/ (`nvfc.png`)
- ✓ Red duotone image tiles (Fire Service / EMS/Rescue / Programs) and "Volunteer Voices" stories.
- ✗ Carousel, cookie banner, four stacked utility bars.
- **Steal:** a duotone photo treatment to unify amateur photos of mixed quality.

**NWS SKYWARN**: https://www.weather.gov/skywarn/ (`skywarn.png`)
- ✓ Scale (350–400k trained spotters). A "Who is eligible and how do I get started?" section: "Training is free and typically lasts about 2 hours. You'll learn: …"
- ✗ A government template in 1990s style.
- **Steal:** a "What you'll learn / how long it takes" bullet list for each training. Also cross-promote NWS Spokane SKYWARN, which has natural overlap with ARES.

**Alpine Rescue Team**: https://alpinerescueteam.org/ (`alpine-rescue.png`)
- ✓ H1 over photo: "Dedicated to Saving Lives Through Search, Rescue, & Mountain Safety Education". "Responded since 1959… 24/7." Membership menu with "Prospective Members" and "Prospective Member FAQs". Public backcountry tips.
- **Steal:** a public-education strand (family comms plan, GMRS vs ham, "how to reach help when cell is down") that builds audience and trust.

**Rocky Mountain Rescue Group**: https://www.rockymountainrescue.org/ (`rocky-mountain-rescue.png`)
- ✓ "Serving Boulder County and beyond since 1947" in the masthead, on a clean Squarespace site.
- **Steal:** "Serving Spokane County since [year]" as a trust line (confirm the founding year).

**Mountain Rescue Association**: https://mra.org/ (`mra.png`)
- ✓ An "MRA Mission Data Dashboard" (a map of missions), plus "Who We Are" and "What We Do" videos.
- ✗ Sponsor walls and a dated layout.
- **Steal:** a public year-to-date activity dashboard (nets held, check-ins, events supported, volunteer hours).

**National Ski Patrol**: https://www.nsp.org/ (`nsp.png`)
- ✓ A stats strip: 30,000+ members / 400+ areas / est. 1938 / Congressionally Chartered. "Join us and help keep mountains safe!"
- **Steal:** a stats strip that includes an institutional fact (e.g., "Registered WA State Emergency Workers").

**US Coast Guard Auxiliary**: https://home.cgaux.org/wp/ (`cgaux.png`)
- ✓ "Who Can Join?" on the home page, mission cards showing the variety of roles (Surface Ops, AUXAIR, Public Education, Vessel Safety Checks, Health Services), and "Find a Flotilla".
- ✗ Heavy nav.
- **Steal:** answer "Who can join?" on the home page.

**American Red Cross (Become a Volunteer)**: https://www.redcross.org/volunteer/become-a-volunteer.html (text only; headless was blocked)
- ✓ "What It's Like to Volunteer", "Responds to Over 60,000 Disasters Annually", "Volunteer Opportunities for Different Skills and Interests", "Find your volunteer opportunity »", and a local site picked by ZIP.
- **Steal:** role matching by skills and interests.

**Local context: Spokane County Fire District 8 / District 4**: https://www.scfd8.org/ and https://scfd4.org/ (`scfd8.png`, `scfd4.png`)
- Generic WordPress sites in red and gold. Useful only to show the local served-agency visual language, which the new site should *complement* rather than imitate.

---

## 4. Patterns across the best sites

| Pattern | Best examples | Spokane application |
|---|---|---|
| Audience router | CAP "I am a…", MMAF split, SCVSAR footer | Four doors under the hero: Licensed ham / Not licensed yet / Member / Agency partner |
| Plain-language "what we are + who we serve" | Seattle ACS, WaEmcomm, Harris | One sentence naming Spokane County Emergency Management, plus a served-agency strip |
| "Why" scenario | Redmond, ARRL | "When the grid goes down in Spokane…": Cascadia, wildfire, ice storm |
| Transparent, time-boxed path | NSW SES, Team Rubicon, CTL, SMR | License → Register (IS-100/200/700, EMD card) → First net check-in → Task Book Level 1 |
| Zero-commitment first step | Seattle ACS net visitors, BAMRU guests, Sacramento events | "Listen tonight" net card with add-to-calendar; "Meetings open to guests" |
| Separate path for the unlicensed | LA DCS, Seattle ACS, HamStudy | "Get licensed" page: HamStudy link, class dates, VE session finder, Elmer (mentor) signup |
| Concrete credibility | BAMRU stats, NYC logo wall, Pierce awards, Clark MOUs, Seattle video | Stats strip, agency logos with captions, exercise history, press |
| Live status | Team Rubicon bar, SCC interruptions, NSW warnings | Activation status (Normal / Standby / Activated), repeater status, next net |
| Nets as data | Sacramento, FCARES, Snohomish | Mono/tabular cards: day, time, freq, offset, tone, repeater, net control, preamble, .ics |
| Member hub by task | SCC dashboard, CFA Members Online | "Members" utility link → task cards (Forms/ICS-213, Schedules, Code plugs, Go-kit, Credentials); offline PWA |
| Roles and progression | NSW SES roles, Pierce levels, SCC credentials, ARRL Task Book | A visual ladder with badges, and role cards so newcomers see where they fit |
| Newcomer FAQ in their words | SF NERT, Alpine | "Do I need my own radio?", "How much time?", "Do I have to be an expert?", "Is it safe?" |
| Documentary people photography | SF NERT, CFA, Team Rubicon, Clark | Members in vests at the EOC, events and the comm van; duotone treatment to unify |
| Belonging and identity | Greyshirts, Snohomish patches | Name the member identity; show the vest and patch as the "you're in" moment |

## 5. Anti-patterns (why most ham sites feel dated)

- **Nav sprawl that mixes public and member admin.** Pierce has ~25 items including password resets. Colorado dumps its whole org chart.
- **A header made of seals and logos with no people in it** (SCC three seals, Pierce two logos, NYC-ARECS logo wall as the design).
- **Hero images of radio gear** (Snohomish Icom rigs, Marin S-meter, FCARES product shot). These only speak to people who are already hams.
- **Blog-first home pages with stale dates** (Clark's 2022 newsletter, LA DCS). A dated post on the home page reads as "abandoned".
- **Acronym soup** (ARES®/RACES/ACS/DEC/EC/AEC/ICS/NTS/SET) with no glossary or plain-language first line.
- **Paper and PDF funnels.** Snohomish is print-sign-bring. ARRL's "Join ARES Today" is a PDF.
- **Login widgets and member chores on the public home page** (Clark sidebar login, Pierce nav).
- **Template debris:** site-builder ad bars (Redmond), live clocks (Sacramento), carousels (CAP, NVFC, SMR), cookie modals, Google Calendar iframes, loading spinners.
- **Poor contrast:** thin letter-spaced type over photos (BAMRU), purple links on near-black (SCC), grey 300-weight body (Redmond).
- **A donate-first ask** from groups that aren't donation-driven (NYC-ARECS). For Spokane, Donate should be tertiary at most.
- **No "what happens next"** after someone expresses interest. Snohomish's "After I join, what do I do?" is the exception.

## 6. Implications and seeds for the concept phase

**Home page skeleton the evidence supports:**
1. Status bar
2. Hero with a plain-language promise and two CTAs
3. Audience router (4 doors)
4. "Why it matters" scenario
5. Stats and agency strip
6. The path (4 steps)
7. Tonight's net card and upcoming events
8. Roles and ladder
9. Voices (member plus agency quote)
10. FAQ
11. Footer grouped by audience, with a utility "Members" link

**Visual cues that signal "professional emcomm"** (as opposed to "hobby club"):
- Navy or ink plus a hi-vis accent (safety orange #F48703-ish, hi-vis yellow, or ARES red), used sparingly.
- A strong condensed or slab display face (Team Rubicon, NSW SES), paired with a clean humanist sans for body text.
- **Tabular monospace for frequencies and call signs.** A ham-native detail that also improves scanning.

**Concept seeds** that explore different directions. Each is grounded in an observed reference:
- (a) "Civic design system": NSW SES / USWDS clarity
- (b) "Documentary": full-bleed member photography, Team Rubicon voice
- (c) "EOC status board": SCC dashboard crossed with a live ops panel
- (d) "Field manual": ICS form and checklist aesthetic
- (e) "Signal": waveform and spectrum motif derived from the ARES diamond
- (f) "Inland Northwest topo": Spokane terrain contours with repeater-coverage map
- (g) "Editorial": Multnomah-style serif calm, magazine storytelling
- (h) "Quiz-first": Marin questions turned into an interactive fit-finder
- (i) "Mission-brief dark mode": night-ops palette
- (j) "Community warmth": SF NERT crowd, friendly and approachable

**Content we'll need from the group** (flag early):
- Real photos (EOC, events, comm van/trailer, people of different ages and genders)
- Founding year and member count, activations and events per year, volunteer hours
- The served-agency list and any MOUs
- Full net schedule and repeater data
- License class and VE session dates
- The requirements (IS-100/200/700, Emergency Worker card, background check)
- 2–3 member quotes and one agency quote

---

### Screenshot index (`design/inspiration/`)
alpine-rescue, arrl-ares, arrl-getting-licensed, bamru, cert-la, cfa-volunteers, cgaux, civil-air-patrol, clallam-ares, clark-ccares, colorado-ares, crisis-text-line, fcares, hamstudy, harris-ares, lacdcs, loudoun-ares, make-me-a-firefighter, marin-races, mra, multnomah-ares, nsp, nsw-ses, nvfc, nyc-arecs, pierce-ares, redmond-ares, rocky-mountain-rescue, sacramento-ares, scc-ares-races, scfd4, scfd8, scvsar, seattle-acs, seattle-mountain-rescue, sf-nert, shoreline-acs, skywarn, snohomish-acs-wa7dem, team-rubicon, wastate-ares, williamson-ares. All have both hero and `-tall` shots except scvsar, which is tall-only. kcares and mdares were placeholder shells, kept only for completeness.
