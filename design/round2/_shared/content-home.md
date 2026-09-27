# Home (`index.html`): draft copy

- **Job of the page:** a newcomer's first step. Draw people in, show capability in one glance, and give clear calls to action: **Join**, **Learn more** (How it works), **Visit** (listen or come to a meeting).
- **Conventions:** `[verify]` = put `data-verify="true"` on that element; never show a visible chip. Items marked **(data)** come from `assets/data.js`; the text shown here is the no-JS fallback as of 2026-09-26. Headline alternates are offered so options can differ in voice; every fact is shared.
- **Section order is a recommendation.** Options may reorder sections 4-11, but sections 1-3 (hero, this week, what we do) stay in the first screen or two, and the join path (§8) must be reachable within one scroll-length of the hero via the header CTA and an in-page link.

---

## 0. Head

- **`<title>`:** Spokane County ARES-ACS | Volunteer emergency radio for Spokane County
- **Meta description:** Licensed amateur radio volunteers providing backup communications for Spokane County Emergency Management, the National Weather Service, area hospitals and community events. Visitors welcome on the Tuesday 8 PM net.

---

## 1. Hero

**Eyebrow:** Amateur Radio Emergency Service · Auxiliary Communications `[verify]` (the ACS expansion follows the seal; decisions.md D6)

**H1 (recommended):**
> From Bloomsday to blackouts, we keep Spokane County connected.

Alternates (pick one per option; do not stack them):
- "When other systems go down, Spokane County still has a voice."
- "Every Tuesday night we practice. The day it's real, we're ready."

**Lede:**
> Spokane County ARES-ACS is a team of licensed amateur radio volunteers. We provide backup communications for Spokane County Emergency Management, the National Weather Service, area hospitals and community events.

**Capability line (for agency visitors; keep it in the hero or directly under it):**
> When normal systems are overloaded or down, our members staff the county's radio room and communications trailer and pass messages by voice and by Winlink radio email.

**CTAs:**
- Primary button: **Join the team** → `#join`
- Secondary button: **See how it works** → `how-it-works.html`
- Text link under the buttons: **Not licensed yet? Start here** → `about.html#licensing`

Give the two buttons clearly different weights (the round-1 Open Door concern).

**Hero image:** `assets/img/IMG_6574-2000w.jpg` (go-kit station on a tailgate above a forested valley; no people). Alt: "A portable emergency radio station set up on a pickup tailgate at a mountain overlook: radios in a rack case, a laptop and a binder of forms." Alternatively a diagram-led hero built on Fig. 1 (§5). If an option wants people in the hero, use a **PHOTO NEEDED** frame (shot list S1 in `assets/README.md`), not a low-resolution crop.

**Scope line (small, under the lede or in the hero footer):**
> We're volunteers, not first responders. We back up the agencies that are.

Source: EWA Standing Order 1b, "Nothing in this order suggests that anyone becomes first responders … ARES is an auxiliary support function" (`interim-ag7qp/ares-acs.md`).

---

## 2. This week (`#this-week`) **(data)**

A compact card. It serves newcomers ("you can listen this Tuesday") and members ("who has net control"). No status badge.

**Heading:** This week

**Next net (from `SPOKARES.fn.nextNet()`):**
> **Tue, Sep 29 · 8:00 PM** · Tuesday net
> Net control: **NZ2S**
> Fifth Tuesday: the net starts on simplex, then moves to the W7GBU repeater. `[verify]`
> Visitors are welcome to check in after members.

**Radio settings row:**
> W7GBU · 147.300 MHz · +600 kHz · 100 Hz tone  [**Copy radio settings**]

- The copy button copies `SPOKARES.radio.copyText` and announces "Copied" in an `aria-live="polite"` region. Without JS, the settings are plain selectable text.
- Do not show a simplex frequency.

**Coming up (from `SPOKARES.fn.upcoming({days: 21, types: ['exercise','meeting','training']})`; merge same-day items such as the Oct 10 workshop and Winlink workshop into one row; show 4 rows):**
> - **Sat–Sun, Sep 26–27** · ARES-ACS / WSDOT exercise · *happening this weekend*
> - **Sat, Oct 3** · Simulated Emergency Test (SET)
> - **Sat, Oct 10** · Second Saturday Workshop, 9:00 AM–noon (Winlink workshop after)
> - **Thu, Oct 15** · Great ShakeOut “Did You Feel It?” message, 10:15 AM · Third Thursday training meeting, evening `[verify]`

**Links:** Plan your first visit → `#visit` · Members: this week → `members/index.html`

---

## 3. What we do (`#what-we-do`)

**H2:** What we do

**Intro:**
> One team of licensed amateur radio ("ham") operators, ready when phones, power or the internet can't be counted on, and busy the rest of the year.

Six cards (icon or small photo, title, one or two lines, optional link):

1. **Backup communications for the county**
   When Spokane County Emergency Management activates us, members staff the county's radio room and communications trailer and pass messages where normal links are overloaded, down or missing.
   Link: How activation works → `how-it-works.html#activation`
2. **Hospitals**
   A hospital team helps keep backup radio and computer equipment in place and working at Greater Spokane area hospitals.
3. **Weather spotting**
   Many members are trained SKYWARN spotters for the National Weather Service in Spokane, and nets start in severe weather. Spotting is encouraged, not required.
4. **Community events**
   Primary or backup communications for Bloomsday, the Lilac Festival Armed Forces Torchlight Parade, fun runs and bike races.
5. **Digital messaging**
   Winlink sends email-style messages by radio, with or without the internet. The group runs the W7GBU-10 Winlink gateway, and members practice with twice-monthly assignments.
6. **Training and exercises**
   A weekly net, monthly meetings and workshops, and exercises like the annual Simulated Emergency Test keep skills sharp.
   Link: See the exercises → `how-it-works.html#exercises`

Photo suggestions: card 1 `Operating-in-VAN_4.jpg` (small, duotone) or PHOTO NEEDED S3; card 4 `8091bb_6c9b...jpg` (blur the badge) or PHOTO NEEDED S5; card 6 `8091bb_cfac...jpg` (the antenna-mast team). Do **not** use a photo for the hospital card (OPSEC/HIPAA); use an icon.

---

## 4. Why it matters

A short band between sections. Use one quote, attributed.

> "Volunteer radio communicators may be called upon to supplement existing systems when it is anticipated that those systems may become overloaded or disabled, or when it is necessary to supply communications services where no established links exist."
> — Spokane County ARES/RACES Plan

Alternate:
> "Amateur radio can be particularly helpful because it can function even if other modes of communication cannot."
> — Eastern Washington Section, Standing Order 1b

---

## 5. How a message gets through (`#how-a-message-gets-through`)

**H2:** How a message gets through when phones are down

**Fig. 1 (diagram; three steps, left to right, or top to bottom on mobile):**
1. **A volunteer calls in** on a handheld or mobile radio, from a shelter, an event course or home.
2. **The W7GBU repeater on Brownes Mountain** hears the signal and passes it across the county, on emergency power. `[verify]` (the emergency-power detail)
3. **The county's radio room** copies the message, logs it, and hands it to the people who need it.

**Caption:**
> Every hop is a trained volunteer, and there are backups: an alternate repeater, simplex radio-to-radio, HF radio and Winlink.

**Link:** Follow one message, step by step → `how-it-works.html#follow-a-message`

Keep the diagram generic. Do not draw hospital, EOC or county radio channels.

---

## 6. Is this for me? (`#quiz`)

**H2:** Is this for me?

**Intro:**
> Five quick questions. You'll get one clear first step, licensed or not.

**Button:** Take the 2-minute quiz

The quiz is progressive enhancement: without JS, show the four routes (below) as a plain list under the heading "Four ways to start".

**Questions (tap answers; one per screen or all on one card):**

1. **Do you have an amateur radio license?** *An FCC license, Technician class or higher.*
   - Yes, Technician or higher → "Good. You could check into Tuesday's net this week."
   - I'm studying for it → "Keep going. Every exam question is published in advance."
   - Not yet → "Everyone starts here. Listening to the net needs no license."
2. **Where would you like to help?**
   - Out in the field → "Events, the comm room and the trailer all need operators."
   - From home → "Home stations check into the net and pass messages too."
   - I'm just curious for now → "Fair enough. Tuesday's net is an easy place to listen in."
3. **Would you be comfortable with a county background check?** *ACS members pass one so they can work inside emergency operations centers, hospitals and shelters.*
   - Yes, that's fine → "That opens ACS, so the county can call on you."
   - I'd rather not → "No problem. ARES welcomes you without one."
   - Not sure yet → "Start with ARES. You can add ACS when you're ready."
4. **What sounds most like you?**
   - Laptops and data modes → "You'll like Winlink: email by radio."
   - Voice on the radio → "Voice nets first. The digital side is there when you want it."
   - Watching the weather → "SKYWARN spotters report severe weather to the Weather Service."
5. **When are you usually free?**
   - Weekday evenings → "The net is Tuesdays at 8 PM. Training meetings are on third Thursdays."
   - Weekends → "Second Saturday Workshops run 9 AM to noon."
   - Both → "Then nets, workshops and events are all open to you."
   - It varies → "Checking into the net once a month keeps you active."

**Routing rule:** Q1 = "Not yet" or "studying" → **License**. Q1 = yes and Q3 = "fine" → **ARES + ACS**. Q1 = yes and Q3 ≠ "fine" → **ARES**. Q1 = "Not yet" and Q4 = "weather" → also show **SKYWARN** as a second, smaller suggestion.

**The four routes (result cards; also the no-JS list):**
- **Start with a license.** "Study the published questions at your own pace, take a local class if you'd like a teacher, then sit the exam." Button: **Licensing in plain English** → `about.html#licensing`
- **Join ARES.** "Open to any licensed ham. One form, and your first Tuesday net can be this week." Button: **Get the ARES application** → `members/documents.html#ares-application`
- **Join ARES, then qualify for ACS.** "ARES first, then the county application and background check, so the county can call on you." Button: **See the ACS path** → `about.html#acs-path`
- **Train as a weather spotter.** "SKYWARN spotter training from the National Weather Service needs no license." Button: **SKYWARN spotter training** → `https://www.weather.gov/otx/Current_Spotter_Training`

Each result also offers: "Or listen in first, this Tuesday at 8 PM on 147.300 MHz" → `#visit`.

---

## 7. Three ways to visit (`#visit`)

**H2:** Three ways to visit

**Intro:**
> None of them asks for a commitment.

1. **Listen to the net**
   *Tuesdays, 8:00 PM, from home.*
   Tune any radio or scanner that receives the 2-meter band to **147.300 MHz**. Listening needs no license. Licensed? Net control calls for visitors after members: give your call sign slowly and phonetically, your name and your location.
2. **Come to a training meeting**
   *Third Thursdays, evenings,* `[verify]` *at Spokane County Emergency Management.*
   The monthly training meeting. Visitors are welcome.
3. **Drop into a workshop**
   *Second Saturdays, 9:00 AM–noon, same place.*
   Hands-on sessions. A Winlink workshop follows from 12:30 to 3:30 PM, open to everyone. `[verify]`

**Where:** Spokane County Emergency Management, 1121 W Gardner Ave, Spokane, WA 99260, next to the Spokane County Courthouse (near N Jefferson St). `[verify: parking, which door, after-hours access]` (show no text for these until confirmed; put `data-verify` on the address block).

**Next dates (data):** Workshop Sat, Oct 10 · Training meeting Thu, Oct 15 · Workshop Sat, Nov 14.

**Map:** a simple drawn locator (courthouse, N Jefferson St, W Gardner Ave) is preferred. `assets/img/DEM_map.png` is a Google Maps screenshot: acceptable as a mockup stand-in only.

**Link:** Get directions → `https://www.google.com/maps/search/?api=1&query=1121+W+Gardner+Ave+Spokane+WA+99260`

---

## 8. Your path to joining (`#join`)

This is the target of the global **Join the team** CTA.

**H2:** Your path to joining

**Intro:**
> Four steps. Start at 0 if you're not licensed yet, or at 1 if you are. You can stop at ARES, or go on to ACS.

| Step | What | You need | How long | Action(s) |
|---|---|---|---|---|
| **0** | **Get licensed** | Nothing yet | Study at your own pace; local exam sessions run monthly `[verify]` | **Licensing in plain English** → `about.html#licensing` |
| **1** | **Join ARES** | An FCC Technician license or higher | One form | **Get the ARES application** → `members/documents.html#ares-application` · ARRL ARES Task Book → `https://www.arrl.org/files/file/ARES%20Taskbook%20July%202024.pdf` |
| **2** | **Get connected** | Your call sign | A Tuesday evening | **Apply to join our groups.io list** → `https://spokaneares-acs.groups.io/g/main` · **Check into the net** → `#visit` |
| **3** | **Qualify for ACS** *(optional)* | The county application and a background check | New Member period, normally up to a year | **County volunteer application** → county FormCenter 276 · **What ACS asks of you** → `about.html#acs-path` |

**Step text (for card layouts):**
- **0. Get licensed.** You need an FCC Technician license or higher. Every exam question is published in advance, and local classes and exam sessions can get you there.
- **1. Join ARES.** Open to any licensed ham. Fill out the Spokane County ARES Membership Application and bring it to a meeting, or send it to join@spokares.org `[verify]`. ARRL membership isn't required to join.
- **2. Get connected.** Apply to join our groups.io list (a moderator approves new members), check into the Tuesday net, and come to a Third Thursday meeting or a Second Saturday workshop.
- **3. Qualify for ACS (optional).** Submit the county's Volunteer Emergency Worker Application and pass a Sheriff's Office background check. Then attend New Member Orientation and work toward Radio Operator, when you receive your ACS ID card. This lets the county call on you.

**Footnote line:**
> Can't or don't want to do ACS? "Even [if] you don't or can't qualify for ACS, our ARES group welcomes you."

(Quote from the group's join page, `content/live/26-*.md`.)

Optional (Plain Signal graft): a "Where are you starting from?" control that highlights the right step and updates a shareable `index.html?from=licensed#join` link. Progressive enhancement only.

---

## 9. Here's what it takes (`#what-it-takes`)

**H2:** Here's what it takes

**Intro:**
> Honest numbers, from the county's ACS task book. ARES and ACS members train side by side.

Four tiles:
- **1 net check-in a month.** Weekly is encouraged.
- **1 meeting or workshop a quarter.** Monthly is encouraged.
- **1 field operation a year.** An activation, an exercise or a public-service event.
- **Two kits, ready.** A personal kit for at least 24 hours (72 preferred) and a radio kit that runs on 2 meters and 70 centimeters for 24 to 72 hours.

**Footnote:**
> New members also complete four free online FEMA courses and the ARRL Basic EmComm course in their first year. Full details: Training path → `about.html#training-path`

Source: ACS Position Task Book v2024-08-09 (`interim-ag7qp/acs-task-book.md`).

---

## 10. Who we serve (`#who-we-serve`)

**H2:** Who we serve

**Primary line (agency credibility; plain text, no government-banner styling):**
> **Spokane County Emergency Management** is our primary served agency. It activates ACS members and runs the county application, background check and ID cards.

List:
- **National Weather Service, Spokane:** SKYWARN weather spotting and severe-weather nets.
- **Greater Spokane area hospitals:** backup radio and computer equipment kept ready.
- **American Red Cross:** service under the national ARRL–Red Cross agreement.
- **Community event organizers:** Bloomsday, the Torchlight Parade, fun runs and bike races.

**Partner line:**
> We're an ARRL Amateur Radio Emergency Service group, and we share a backup repeater and packet paths with the Inland Empire VHF Radio Amateurs under a written agreement.

**Affiliation marks:** ARES emblem, ACS seal, SCEM seal, labelled as affiliation marks `[verify]` (permission, decisions.md D6).

**Link:** Who we serve and our agreements → `about.html#who-we-serve`

---

## 11. For agencies and event organizers (`#agencies`)

A small, clearly separate "door" (County Standard graft).

**H2 (or H3 in a side panel):** Represent an agency or an event?

> Government agencies request amateur radio support through **Spokane County Emergency Management**, which activates the team. Event organizers can reach our public-events coordinator.

- **Contact the emergency coordinator** → `about.html#contact`
- **How activation works** → `how-it-works.html#activation`
- **Our agreements** → `about.html#agreements`

---

## 12. Why W7GBU? (`#w7gbu`)

**H2:** Why W7GBU?

> Our call sign honors Ira "Dean" Bula, a Spokane ham who mentored a generation of newcomers and started the weekday morning round table known as the 7:30 Bunch. It still meets on the same repeater that carries his call.

> "He was a person who made a difference in Spokane by making others feel like they could make a difference themselves."
> — from the group's history of W7GBU

**Link:** The W7GBU story → `about.html#w7gbu`

(The present-tense "still meets" relies on the 2026 net list's "0730 Bunch"; `[verify]`.)

---

## 13. Already a member? (`#members`)

A slim strip, not a big section.

> **Already a member?** Net control, this week's schedule, scripts and forms are on the members pages.

Links: **For Members** → `members/index.html` · **Documents & downloads** → `members/documents.html` · **Exercises & events** → `members/exercises.html` · **groups.io** ↗

---

## 14. Final call to action (before the footer)

**Heading:** Tuesday at 8 PM, on 147.300. Come say hello.

**Buttons:** **Join the team** → `#join` · **See how it works** → `how-it-works.html`

---

## Sources for this page

`content/live/04-*.md` (About), `05-*.md` (W7GBU), `26-*.md` (join), `02-*.md` (Plan quote); `interim-ag7qp/spokane-county-ares-acs.md` (rota, events), `ares-acs.md` (SOP 1b, ARES/ACS definitions), `acs-task-book.md`, `new-members-orientation.md`, `winlink.md`, `home.md`; `research/organization.md` §3, §7-§10; `data/facts.yaml`; `design/REVIEW.md` (hybrid and grafts); `design/content-brief.md` §2 (headlines).
