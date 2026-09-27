---
page: how-it-works.html
budget: 1,200 visible words in <main>; max 6 H2
h2_count: 5
h3_count: 6
word_count: { A: 851, B: 849, C: 851 }   # measured (README.md method); B swaps the lede for the story and drops one H3
primary_action: "Join the team" -> index.html#join (closing line only)
sources: round2/_shared/content-how-it-works.md; round2/_shared/assets/data.js (S.nets, S.repeaters, doc rows); research/organization.md
placement: round3/_shared/placement-map.md §1 rows 5-8, 11, 25-26, 41-50, 52, 55; §3.2
---

<!--
CONVENTIONS
- Only the text between COPY markers is visible. {A}/{B}/{C} = that option only.
- [verify] = data-verify="true" on that element. No visible chip.
- [CTA: label -> target] = a button or prominent action link.
- [text](target) = an inline link on existing words (no new sentence).
- Expand on first use with <abbr title>: ARES, ACS, NWS, EOC, ICS, NOAA.
- Never on this page: a current readiness level, the simplex frequency, GMRS repeater details,
  hospital net details, meeting times or the address, join steps, dated exercises.
-->

<!-- <title>How it works | Spokane County ARES-ACS</title>
     meta description: How the Tuesday net runs, how the county activates volunteers, how a message moves, and how members train. -->

<!-- COPY -->

# How it works

{A,C} Emergency radio works only when people practice it, so most of what we do is practice.

{B} It's 2 AM. The power's out, and so is cell service.

<!-- B only: the "Follow one message" story figure, id="follow-a-message", pill inside the SVG. -->
{B} *A scenario, not a real event. It shows how the system is meant to work.* [verify: PIO/SCEM review]
{B} 1. **The phones go quiet.** Ice brings the lines down overnight. At a warming shelter, a resident's home oxygen concentrator has no power.
{B} 2. **A volunteer picks up the radio.** The shelter's ACS operator writes an ICS-213 and reads it, word for word, to net control on W7GBU.
{B} 3. **The radio room copies it.** Another volunteer copies the message, logs it on an ICS-309 and hands it to the county's Emergency Operations Center.
{B} 4. **The EOC acts on it.** The request moves through the agency's own channels. By morning it's handled, and every hop is on paper.

{B} Radio still works. Because people practice.

<!-- C: sticky "On this page" chips, 5 only: Net · Activation · Messages · Exercises · Roles -->

## The weekly net {#nets}

A net is an on-air meeting. Ours is a directed net: net control calls each group in turn, so everyone gets heard.

<!-- Settings box, id="weekly-net". Two copyable lines. -->

**Every Tuesday, 8:00 PM**

- **W7GBU:** 147.300 MHz · +600 kHz · 100 Hz tone
- **Alternate:** 146.880 MHz · −600 kHz · 123 Hz tone [verify]

**What happens, in order**

<!-- A: this list IS Fig. 2 (net anatomy bar); do not print it twice. -->

1. Net control opens the net; emergency or priority traffic may break in at any time.
2. Officials check in.
3. Members check in.
4. Visitors check in.
5. Simplex, no-tone and cross-band stations, then relays.
6. Traffic and late check-ins, then net control closes the net.

**Visiting?** Wait for the call for visitors. Give your call sign slowly and phonetically, then your name and location. Not licensed? Just listen.

### Other nets {#other-nets}

- **Winlink nights:** 2nd and 4th Tuesdays; net control gives a [Winlink assignment](members/exercises.html#winlink-assignments) during the net. [verify]
- **Fifth Tuesdays:** the net starts on simplex, then moves to W7GBU. [verify]
- **ACS GMRS net:** 3rd Tuesdays, 7:30 PM, for county volunteers with GMRS licenses. [verify]

## When the county calls {#activation}

<!-- id="who-calls-us" on this paragraph -->

The county activates ACS members; ARES members serve events and the National Weather Service without a call-out. [ARES and ACS](about.html#ares-acs)

<!-- id="real-world" on this sentence (no H3) -->

It has happened for real: in the Gray and Oregon Road fires of August 2023, the group provided a mobile network unit for the incident command post and the Disaster Assistance Center. [verify]

<!-- A: this list IS Fig. 3 (activation track). B/C: a numbered track. -->

1. **Request.** The county needs radio support.
2. **Mission number.** The county gets a state mission number, which ties the activation to Washington's [emergency-worker protections](about.html#legal-basis). [verify]
3. **Activation message.** It goes to members registered for notices. [verify]
4. **Check in.** Available members check in with net control on W7GBU.
5. **Assignment.** Go to your assignment or a staging area.
6. **Release.** Served agencies and the emergency coordinator release members.
7. **Review.** Once every station is secured and home, leaders hold an action review within 72 hours.

### Readiness levels {#alert-levels}

Four levels tell members what to do. This site never shows a current level. [verify: 2017 text, confirm with EC]

<!-- Neutral table or sequence. No traffic-light badge, no "you are here" marker. -->

| Level | Means | What members do |
|---|---|---|
| Green: Inactive | Normal conditions | Normal operations. Check and recharge batteries monthly. |
| Yellow: Alert | Something might happen | Check radios, go-kits and supplies. Watch National Weather Service statements. |
| Orange: Stand by | Something has happened | Load your go-kit. Monitor NOAA Weather Radio WXL86 (162.400 MHz) and W7GBU. |
| Red: Deploy | Full activation | Follow the activation steps above. |

### When no one has called yet {#standing-order-1b}

If communications fail and there are no instructions, the Eastern Washington Section's Standing Order 1b applies. Safety comes first: no one is asked to leave home or travel. Then send a Washington State Field Situation Report by Winlink.

[Read Standing Order 1b ↗](https://ag7qp.com/ares-acs)

## How a message moves {#message-handling}

{A,C} ### Follow one message {#follow-a-message}

{A,C} *A scenario, not a real event. It shows how the system is meant to work.* [verify: PIO/SCEM review]

<!-- A: this is Fig. 4. B: these steps are the story figure at the top of the page instead. -->

{A,C} 1. **The phones go quiet.** Ice brings the lines down overnight. At a warming shelter, a resident's home oxygen concentrator has no power.
{A,C} 2. **A volunteer picks up the radio.** The shelter's ACS operator writes an ICS-213 and reads it, word for word, to net control on W7GBU.
{A,C} 3. **The radio room copies it.** Another volunteer copies the message, logs it on an ICS-309 and hands it to the county's Emergency Operations Center.
{A,C} 4. **The EOC acts on it.** The request moves through the agency's own channels. By morning it's handled, and every hop is on paper.

**If W7GBU is down,** messages move by the alternate repeater, simplex (radio to radio), HF radio or Winlink.

### Forms and logs {#ics-213}

The [ICS-213](members/documents.html#ics-213) is the Incident Command System's standard message form. It travels by voice, on paper or inside Winlink. Keep it short: To, From, Subject, Date, Time, the message and who approved it. The reply goes on the same form.

**Precedence:** Emergency, then Priority, Welfare and Routine.

<!-- id="logs" on this list -->

- **[ICS-309](members/documents.html#ics-309) communications log:** every message sent or received. Winlink Express can build it.
- **[ICS-214](members/documents.html#ics-214) activity log:** what you did and when.

### Winlink {#winlink}

Winlink is email by radio, with forms and attachments. It works with or without the internet. State and USGS forms are built in; reports can be plotted on a map. The group runs its own gateway, W7GBU-10.

## Training and exercises {#exercises}

<!-- No dates here. Dates live on members/exercises.html. -->

- **Every month:** [a workshop and a training meeting](index.html#visit).
- **October:** the Simulated Emergency Test, the Great ShakeOut and the State COMMEX. [verify: COMMEX]
- **With partners:** joint exercises with other agencies, and EOC-to-EOC exercises across the county.
- **On the air:** ARRL Field Day, Winter Field Day and SKYWARN Recognition Day.

[Dates: Exercises & events](members/exercises.html)

## Roles members take {#roles}

<!-- Table cells <= 12 words. On phones: stacked rows. -->

| Role | What you do | Open to |
|---|---|---|
| Net control | Run the net in turn; the Net Manager coaches first-timers. | All members |
| Field or shelter operator | Pass messages from a shelter, staging area or event post. | ACS for shelters; all members for events |
| Radio room operator | Staff the county radio room during an activation. | ACS |
| Hospital team | Keep backup hospital communications ready. | ACS, with HIPAA training |
| [Public-event operator](members/exercises.html#public-service) | Work a race course or parade route. | All members |
| SKYWARN spotter | Report severe weather to the National Weather Service. | Anyone with [NWS spotter training](members/documents.html#skywarn-training); no license needed |
| Winlink operator | Send forms and reports by radio email. | All members |
| Mobile units and logistics | Set up the trailer and mobile units; keep equipment ready. | ACS Senior Radio Operators |

Ready? [CTA: Join the team -> index.html#join]

<!-- /COPY -->

<!--
BUILD NOTES (not page copy)

Paths: all links are relative to how-it-works.html, which sits at the site root.

Option A (Figure One): Fig. 2 = net anatomy (the 6 "What happens" steps as labels),
  Fig. 3 = activation track (the 7 steps), Fig. 4 = follow one message (the 4 steps).
  The figure replaces the list; never print both. Fig. 1 stays on Home only.

Option B (Carry the Message): the story opens the page, directly after the H1, replacing the lede
  with "It's 2 AM. The power's out, and so is cell service." Then the pill and the 4 scenario steps
  as the figure (id="follow-a-message", pill inside the SVG, <= 100 words, one screen). Figure labels:
  1 Shelter operator · 2 Net control · 3 County radio room · 4 EOC. No hospital hop (hospital net
  details are not public). Then the one transition line "Radio still works. Because people practice."
  (not an H2). #nets must begin within one viewport height below the story. In #message-handling,
  there is no "Follow one message" H3; the section starts at "If W7GBU is down". B's Home has no
  scenario figure, so the story is told once, here.

Option C (Open Channel): sticky chips, 5 only (see top). No role cards, no "Seven more roles",
  no month calendar, no "Your first month", no FAQ.

Round-3 edit pass (repetition audit):
- "8:00 PM" is in the settings header only; step 1 has no time.
- The visitor cue no longer repeats the frequency; the settings box says "Alternate:" (the backups
  line below the scenario says "If W7GBU is down").
- "Members take turns as net control…" moved into the Net control roles row (was a separate line).
- The Winlink-assignments link lives on the #other-nets line only; #winlink no longer repeats it and
  no longer names the ICS-213 or the Field Situation Report (named in #ics-213 and #standing-order-1b).
- "When it was real" is no longer an H3; its sentence sits in the #activation intro with id="real-world".
- "The ICS-213" and "Logs" merged into one H3, "Forms and logs" (#ics-213; #logs on the list).
- #exercises: "Every week: the Tuesday net" and the public-service item are cut (the net is #nets; public
  service is the roles row, linked to members/exercises.html#public-service). SKYWARN Recognition Day
  carries no location here (About#history holds it).
- #roles: preamble cut; "Open to" says "All members" instead of "ARES and ACS".
- Readiness levels: this is the site-wide term (id stays #alert-levels).

CUT (do not rebuild): "The system in one picture" H2; "Who calls us" 3 columns; the 10-step net;
  the "rest of the month" table (0730 Bunch -> About#history; EWSEN cut); repeater etiquette
  paragraph (-> Documents row); ICS-213RR go-kit tip; ARRL Radiogram line (Documents row);
  SOP 1b HF net / KBARA / 40 m detail and first-responder quote; Winlink "help" workshop times;
  meeting times and New Member Orientation; county-arranged classes; dated exercise table;
  public-service H2, shift tips and volunteer contacts (-> members/exercises.html#public-service);
  "Where we work"; PIO and Leadership role rows (-> About#leadership); "Training on the way"
  column; "Ready to try it?" band.
-->
