---
page: index.html
budget: { words: 400, h2: 5, ctas: 6, desk: 3200px }
word_count: { A: 272, B: 245, C: 243 }   # measured (README.md method); visible words in <main>, alt text excluded
h2_count: 3
cta_count: 6 (buttons and button-weight links); plus 2 inline links on existing words in step 2
sources: round2/_shared/content-home.md, content-about.md; assets/data.js (org.location, radio.primary, meetings)
---

# Home: lean copy (round 3, edited)

How to read this file: only the text between `<!-- COPY -->` and `<!-- /COPY -->` is visible. A line that starts with `{A}`, `{B}` or `{C}` is for that option only. Lines starting `> Build:` and HTML comments are builder notes. `[verify]` means `data-verify="true"` on that element, with no visible chip. **(data)** means the text renders from `data.js`; what's shown is the no-JS fallback for Sat, Sep 26, 2026.

- **`<title>`:** Spokane County ARES-ACS | Volunteer emergency radio for Spokane County
- **Meta description:** Licensed amateur radio volunteers who back up Spokane County Emergency Management's communications. Visitors welcome on the Tuesday 8:00 PM net.

<!-- COPY -->

{A} # When phones go quiet, radio still gets the message through.
{B} # When other systems go down, Spokane County still has a voice.
{C} # From Bloomsday to blackouts, we keep Spokane County connected.

We're licensed amateur radio volunteers who back up Spokane County Emergency Management's communications. When phones, power or the internet fail, we pass messages by voice and radio email.

[CTA: Join the team -> #join]
{A} [CTA: See how it works -> how-it-works.html]
{B} [CTA: See how it works -> how-it-works.html]
{C} [CTA: Plan your first visit -> #visit]

Represent an agency? [How we work with agencies →](about.html#for-agencies)

> Build: two buttons of clearly different weights. The agency line is small text under the buttons, in the first screen (map #29).

{A} 1 · Volunteer with a handheld radio
{A} 2 · W7GBU repeater passes it across the county
{A} 3 · County radio room logs the message
{A} Fig. 1 · The path of a message

> Build (A): Fig. 1 stays in the hero, labels only, not a link.

## What we do {#what-we-do}

- **County emergencies:** the radio room and communications trailer, when the county activates us.
- **Hospitals:** backup radio equipment, kept working.
- **Severe weather:** SKYWARN spotting for the National Weather Service.
- **Community events:** Bloomsday, fun runs and bike races.

> Build: in C these are the four photo-card texts. In B they are the four stops on the red line; B has **no scenario figure on Home** (the "one message" scenario lives only on how-it-works.html#follow-a-message, map #45), so B adds no figure labels here.

## Come visit {#visit}

> Build: keep `#this-week` as an alias id. C keeps #visit **below** #what-we-do so its "Plan your first visit" button still has a job; if C moves #visit directly under the hero, C's secondary button becomes "See how it works" like A and B.

**From home:** listen to the Tuesday net, 8:00 PM, 147.300 MHz, on any scanner or 2-meter radio. No license needed.

**In person** at Spokane County Emergency Management, 1121 W Gardner Ave, Spokane: [verify]

- Second Saturday Workshop, 9:00 AM–noon, then a Winlink workshop, 12:30–3:30 PM [verify]. Next: Sat, Oct 10
- Third Thursday training meeting, evenings [verify]. Next: Thu, Oct 15

> Build: the two "Next:" dates are **(data)**: `meetings` id `workshop` and id `third-thursday`, first `next` date (or `fn.nextMeetings()`). `[verify]` on the address block and on the Winlink clause.

## Join in four steps {#join}

> Build: keep `#what-it-takes` as an alias id.

0. **Get licensed.** You need an FCC Technician license or higher. [CTA: How to get licensed -> about.html#licensing]
1. **Join ARES.** One form. [CTA: How to apply -> about.html#ares-path]
2. **Get connected.** Check into the [Tuesday net](how-it-works.html#nets). Apply to join our [groups.io list ↗](https://spokaneares-acs.groups.io/g/main), the members' hub for files and the calendar; a moderator approves you.
3. **Qualify for ACS (optional).** A county application and background check. [CTA: The ACS path -> about.html#acs-path]
   What ACS asks: one net check-in a month, one meeting or workshop a quarter, one field operation a year, plus four free FEMA courses and ARRL Basic EmComm in year one.

<!-- /COPY -->

---

## Builder notes

- **CTAs (6):** Join the team; the secondary button; How we work with agencies; How to get licensed; How to apply; The ACS path. The two step-2 links are plain inline links on existing words (map rule 58), not buttons. Figures are not links.
- **Step 1 goes to `about.html#ares-path`,** not to the Documents row: that section holds the application button and says where to return it, which the Documents row does not.
- **Not on Home:** net control, offset, tone, simplex note, rota, dates of exercises or nets, the "not first responders" line (the footer carries it), Get directions, "Visitors are welcome" copy (the H2 says it), a scenario figure in B.
- **Where this differs from the placement map, and why:**
  - The agency line sits under the hero buttons (map #29: the agency's first-screen need) and no longer restates the request route; About#for-agencies is its home.
  - "What it takes" is labelled "What ACS asks" and sits under step 3, because the minimums come from the ACS task book.
  - The lede no longer says "staff the county's radio room"; the radio room is stated once, in #what-we-do.
