# How it works (`how-it-works.html`): draft copy

- **Job of the page:** the next level of detail for someone who is interested: how the organization operates and what members actually do. Activations, nets, message handling, exercises, public-service events and the roles people take.
- **Tone:** plain, concrete, calm. Show real procedure (ICS-213, ICS-309, net check-in order) without insider jargon; define each term the first time (tap-to-define glossary terms are welcome; definitions are in `content-about.md` §Glossary).
- **Conventions:** `[verify]` = `data-verify="true"` on that element. **(data)** = from `assets/data.js`. Anchors are fixed in `ia.md` §5.
- **Never here:** a live alert status; county, hospital, SHARES or 800 MHz channels; the Hospital Net's schedule; any simplex frequency; MNU or equipment locations.
- **Review before launch:** the scenario in §7 and the activation text in §3 need a PIO or SCEM read-through `[verify]` (decisions.md D12, D15).

---

## 0. Head and page intro

- **`<title>`:** How it works | Spokane County ARES-ACS
- **Meta description:** How Spokane County ARES-ACS volunteers are activated, how the Tuesday net runs, how messages are handled by voice and Winlink, and the exercises and events members take part in.

**H1:** How it works

**Lede:**
> Emergency radio only works if people have practiced it. Here's how our volunteers are called on, how a message moves, and what a normal month looks like.

**On this page (short jump list, optional):** The system · Who calls us · Activation · Nets · Messages · Exercises · Public events · Roles

---

## 1. The system in one picture (`#the-system`)

**H2:** The system in one picture

> A handheld or mobile radio, a repeater on a mountain, and a trained operator at the other end. That's the core. Everything else is a backup to it.

**Diagram (Fig. 1, extended from the home page):**
- **Field station** (shelter, event course, home) → **W7GBU repeater, 147.300 MHz, Brownes Mountain** → **County radio room** (net control and message logging) → **Served agency** (the people who act on the message).
- Backup paths drawn as dashed lines: **Alternate repeater, 146.880 MHz** (the Inland Empire VHF Radio Amateurs' repeater, under a written agreement) · **Simplex** (radio to radio, no repeater) · **HF radio** (long distance, e.g. the Section net on 3.985 MHz) · **Winlink** (email by radio).

**Caption:**
> Every arrow is a person. Net control keeps the frequency orderly, operators copy messages word for word, and everything is logged.

**Settings box:**
> **W7GBU** · 147.300 MHz · +600 kHz offset · 100 Hz tone · on emergency power `[verify]`
> **Alternate:** 146.880 MHz · −600 kHz · 123 Hz tone `[verify]`

---

## 2. Who calls us (`#who-calls-us`)

**H2:** Who calls us, and when

> The same volunteers wear two hats. Which hat they're wearing decides who can call on them.

Three columns or cards:

**A. The county (ACS)**
> Spokane County Emergency Management is the agency having jurisdiction. When it needs amateur radio support, it activates ACS members, the volunteers who have passed the county background check. Only activated ACS members work inside emergency operations centers, incident command posts, hospitals and shelters, where sensitive information is handled.
>
> Before volunteers serve a government agency, the county obtains a state **mission number**. That number is what ties an activation to Washington's emergency-worker protections. `[verify]`

**B. Community partners (ARES)**
> ARES members don't need a declared emergency. They can serve community events, run training and support the National Weather Service "without approval from anyone," and they support the Red Cross under a national ARRL agreement.

**C. When nobody has called yet**
> If something big happens and there are no instructions, the Eastern Washington Section's **Standing Order 1b** applies. It's an information-gathering routine, not a deployment: no one is asked to leave home or travel. See [Standing Order 1b](#standing-order-1b).

**Agency note (small print, for partners):**
> Agencies request support through Spokane County Emergency Management. `[verify]`

Sources: `interim-ag7qp/new-members-orientation.md` (ACS vs ARES quotes); `content/live/02-ares-races-plan.md` (mission numbers, requests through EM; 2013 text); `sources/government/WAC-118-04-2025.txt` via `organization.md` §6.

---

## 3. From a quiet month to a deployment (`#activation`)

**H2:** From a quiet month to a deployment

> Members follow four readiness levels. They describe what to do. They are **not** a public status, and this site never shows a "current" level.

(Design note: present the four levels as a neutral sequence or a numbered process, not as a traffic-light badge or anything that looks like a live indicator. No "you are here" marker.)

### The four levels (`#alert-levels`)

1. **Green: Inactive.** Normal conditions; nothing expected.
   - Normal amateur radio operations.
   - Keep a monthly battery check and recharge schedule.
2. **Yellow: Alert.** Something might happen: a tornado, severe thunderstorm, flooding or ice storm.
   - Check your radios, go-kits and supplies. Batteries must be charged.
   - Stay tuned to the assigned frequencies and watch National Weather Service statements.
   - Members may get an e-mail notice. There's no activation timeframe yet.
3. **Orange: Stand by.** Something has happened, and full activation is likely.
   - Load the car, including your go-kits, and be ready to go to an assignment or a staging area.
   - Monitor NOAA Weather Radio WXL86 (162.400 MHz) and the W7GBU repeater (147.300 MHz).
   - Everyone on the repeater: keep traffic short and leave long pauses for deployment instructions.
4. **Red: Deploy.** Full emergency communications.
   - An activation message goes to members registered for notices. `[verify]` (the current call-out tool)
   - Nets start full operation. Available members check in with net control on W7GBU (147.300).
   - After checking in, go to your assignment or a staging area.

**After the event:**
> Served agencies and the emergency coordinator release members. Once every station is secured and home, the level returns to Green. Leaders hold an action review within 72 hours.

Source: `content/live/20-ares-alert-levels-status-codes.md` (2017). `[verify]` that the text still matches current practice.

### Standing Order 1b: if something happens and no one has called (`#standing-order-1b`)

> The Eastern Washington Section's standing order (January 16, 2026) tells every ARES, AUXCOMM and RACES group what to do when normal communications may be disrupted and there are no instructions yet.

- **Safety first.** Personal and family safety come before anything else. No one is asked to leave home or travel.
- **Follow higher authority** when it's present. Nothing in the order overrides an assignment from your emergency management agency.
- **Gather and report information.** The first station on starts an HF net on 3.985 MHz, takes a roll call, and asks stations to send a **Washington State Field Situation Report** by Winlink to their local emergency management, their EC, their District EC and the Section Emergency Coordinator. For an earthquake, add a USGS **Did You Feel It?** report; for weather, a weather report to the National Weather Service.
- **Then widen the net** on 40 meters and on the KBARA linked repeaters, on a set check-in schedule.

> "Nothing in this order suggests that anyone becomes first responders if a disaster strikes. ARES is an auxiliary support function."

**Link:** Read Standing Order 1b (EWA Section) → `https://ag7qp.com/ares-acs`

Source: `interim-ag7qp/ares-acs.md`.

---

## 4. Nets: practice every week (`#nets`)

**H2:** Nets: practice every week

> A "net" is an organized on-air meeting run by a **net control station** (NCS). Ours is a directed net: net control calls each group in turn, so everyone gets heard. It's how we keep radios, antennas and habits in working order.

### The Tuesday net (`#weekly-net`)

**Fact box (data):**
> **Every Tuesday · 8:00 PM**
> **W7GBU · 147.300 MHz · +600 kHz · 100 Hz tone**
> Alternate if the repeater is down: 146.880 MHz `[verify]`
> **Next:** Tue, Sep 29 · Net control NZ2S · 5th Tuesday: simplex `[verify]`

**What happens, in order** (from the current script, version 2026-03-04):
1. **7:55 PM** Net control announces that the net starts in about five minutes. On Winlink nights: "get paper and pencil."
2. **8:00 PM** Net control opens the net and reads its purpose: coordination for the group, and a weekly emergency-net training session to test equipment and check propagation.
3. **"Stations with emergency or priority traffic may break in at any time."**
4. **Officials check in:** the county emergency coordinator, county Emergency Management staff, the assistant emergency coordinators, ARRL District and Section officials, and officials from surrounding counties.
5. **Members check in.**
6. **Visitors check in:** call sign (slowly and phonetically), name and location.
7. **Simplex, no-tone and cross-band stations,** heard on the repeater's output, then any relays.
8. **Listed traffic** is handled, then late or missed check-ins, "Alpha through Zulu."
9. **Winlink nights only:** the Winlink assignment is given.
10. Last call for traffic, and net control closes the net.

**Visitors, this is your cue:**
> Licensed and curious? Wait for the call for visiting stations, then give your call sign, name and location. That's it. Not licensed? Listen along: any radio or scanner that receives 147.300 MHz will do.

**Net control rotates.** Members take turns as net control, and the Net Manager coaches anyone taking a first turn. At the ACS Radio Operator level, serving as net control once a year is a requirement.

**Pause for the repeater.** Net control leaves gaps so the repeater can drop and cross-band stations can break in. On W7GBU generally: identify at the start, every ten minutes and when you clear, and break in with just your call sign ("emergency traffic" if it is). `[verify]` (the etiquette page is from 2017)

### The rest of the month (`#other-nets`)

| Net | When | Where | Notes |
|---|---|---|---|
| **Winlink night** | 2nd and 4th Tuesdays, during the 8 PM net `[verify]` | W7GBU | A Winlink assignment is given on the net; responses go by radio email that evening (see [Winlink](#winlink)). |
| **Simplex net** | In months with a 5th Tuesday, 8 PM `[verify]` | Starts on the ARES-ACS simplex frequency, then moves to W7GBU | No repeater: each station reads back the stations it heard, which shows who can reach whom without it. |
| **ACS GMRS net** | 3rd Tuesday, 7:30 PM `[verify]` | WRCY281 GMRS repeater (GMRS channel 7) `[verify]` | For county volunteers with GMRS licenses. |
| **0730 Bunch** | Weekdays, 7:30 AM `[verify]` | W7GBU, 147.300 | An informal round table started by Dean Bula, W7GBU. |
| **Section nets (EWSEN)** | Mondays | VHF 6:15 PM on the KBARA linked repeaters; HF 6:45 PM on 3.985 MHz; Winlink check-ins 5:00 to 7:00 PM `[verify]` | Run by the Eastern Washington Section → `https://ag7qp.com/eastern-washington-section` |

(Never print a simplex frequency: no current source states it; decisions.md D13 #5.)

---

## 5. Handling messages (`#message-handling`)

**H2:** Handling messages

> In an emergency, a message that is garbled, lost or unrecorded is worse than no message. So we use the same forms the agencies use, read them word for word, and log everything.

### The ICS-213 general message (`#ics-213`)

> The **ICS-213** is the standard message form of the Incident Command System. Anyone can start one. It travels by voice, on paper or inside Winlink.

Show a stylised, clearly **sample** ICS-213 (not a real message) with its blocks labelled:
- **To** and **From:** name and position (at least first initial and last name).
- **Subject**, **Date** (MM/DD/YYYY) and **Time** (24-hour, with L for local or Z for UTC when it matters).
- **Message:** as short as it can be.
- **Approved by:** name, signature and position.
- **Reply:** the recipient answers on the same form.

**Related forms (one line each, linking to the library):**
- **ICS-213RR:** resource requests. Tip from the task book: keep one filled out that lists your go-kit; it helps with any claim for lost or damaged equipment. `[verify]`
- **ARRL Radiogram:** the National Traffic System's message format.

**Precedence:** messages are handled in order: **Emergency, Priority, Welfare, Routine**. With equal precedence, net control clears the stations with the fewest messages first.

### Logs (`#logs`)

- **ICS-309 communications log:** every message sent or received, kept by the radio operator during all ACS radio operations. Winlink Express can build it automatically.
- **ICS-214 activity log:** what you did and when, kept during exercises and activations.

### Winlink: email by radio (`#winlink`)

> **Winlink** sends email-style messages, with attachments and forms, over radio. It works with or without the internet, and it can use the internet (Telnet) when that's all you have.

- **Forms built in:** ICS-213, the Washington State Field Situation Report (phones, power, water, broadcast and internet at your location), USGS "Did You Feel It?" reports and more.
- **Maps:** reports sent to a call sign can be plotted on a map in Winlink Express, so an emergency manager sees the picture at a glance instead of reading a hundred e-mails.
- **Our gateway:** the group runs the **W7GBU-10** Winlink gateway, managed by the AEC for public events (NV2Z).
- **Practice:** twice a month, members get a Winlink assignment and answer it on the form named, usually an ICS-213, sent to NV2Z and AG7QP between 5 and 9 PM. Radio is preferred; Telnet is acceptable.
- **Help:** a Winlink workshop follows the Second Saturday Workshop, 12:30 to 3:30 PM, open to everyone. `[verify]`

**Next assignment (data):** Tue, Oct 13 · Submit a Did You Feel It report (mark it as an exercise).

---

## 6. Follow one message (`#follow-a-message`)

**H2:** Follow one message

**Label (must stay visible and adjacent to the story):** *A scenario. Not a real event. It shows how the system is meant to work.* `[verify: PIO/SCEM review]`

A condensed, four-step version of the round-1 "When the Lines Go Down" story. Keep it short (no scrollytelling required; a stepped diagram or four cards is enough) and keep "Skip the story" if it is animated.

1. **The phones go quiet.** Ice brings the lines down overnight. Cell towers run on batteries, then fall silent. At a warming shelter, a resident's home oxygen concentrator has no power, and the shelter manager needs to reach the county's Emergency Operations Center.
2. **A volunteer picks up the radio.** The ACS member assigned to the shelter writes an ICS-213: *To: EOC Logistics. From: Shelter Manager. Subject: Resident needs oxygen.* They call net control on the W7GBU repeater and read it word for word.
3. **The radio room copies it.** In the county radio room, another volunteer copies the message, logs it on an ICS-309 and hands it to the EOC.
4. **The EOC acts on it.** The request goes where it needs to go, through the agency's own channels. By morning the message has been handled, and every hop is on paper.

**Close:**
> None of it depends on luck. It depends on volunteers who practiced on ordinary Tuesdays, so a real night feels familiar.

(Round 1 continued the story onto "the hospital net". That hop is dropped here because the Hospital Net's details are not public; the EOC "acts on it" instead.)

---

## 7. Exercises and training (`#exercises`)

**H2:** Exercises and training

> Most of our year is practice. Some of it is big and public; most of it is a Tuesday night or a Saturday morning.

**Regular training:**
- **Second Saturday Workshops,** 9:00 AM to noon at Spokane County Emergency Management: hands-on sessions such as digital-mode setups, emergency power, mobile installs and cross-band repeating. The Winlink workshop follows at 12:30.
- **Third Thursday training meetings,** evenings `[verify]`. Recent topics have included shelter training and go-kits. `[verify]` (topics are from 2018-19 records)
- **New Member Orientation:** the mission and structure, qualifying, HIPAA, then a tour of the county Comm Room and the 8 PM net. Held regularly; ask us for the next date. `[verify]`
- **County-arranged classes:** First Aid/CPR, and optional defensive driving (needed only to drive county vehicles); AUXCOMM and COMT for senior members.

**Exercises (recurring):**

| Exercise | What it is | When |
|---|---|---|
| **Simulated Emergency Test (SET)** | ARRL's national exercise. The old county plan called it "the largest event sponsored by Spokane County ARES/RACES itself." | Every October; **Sat, Oct 3, 2026** |
| **Great ShakeOut** | Members send a USGS "Did You Feel It?" report by Winlink. | October; **Thu, Oct 15, 2026, 10:15 AM** |
| **State COMMEX** | Washington's statewide communications exercise. `[verify]` | **Sat, Oct 24, 2026** |
| **EOC-to-EOC exercises** | Stations at sites across the county pass traffic to emergency operations centers and to the State EOC at Camp Murray. Held on 5th Saturdays in past years. `[verify]` | As scheduled |
| **Joint exercises** | With partner agencies, such as this year's exercise with the Washington State Department of Transportation. | **Sep 26–27, 2026** |
| **Winlink assignments** | Short practice messages on the 2nd and 4th Tuesdays. | Twice a month |

**On-air events:** ARRL Field Day and Winter Field Day (portable, off-grid stations), and **SKYWARN Recognition Day**, when the group has run a station at the National Weather Service office in Spokane every year since 1999 `[verify]`.

**Link:** Members: full exercise calendar → `members/exercises.html`

---

## 8. Public-service events (`#public-service`)

**H2:** Public-service events

> Race courses and parade routes are where most members first work a real net: many stations, a busy frequency and a net control keeping order. The county plan treated these events as primary training for working alongside fire and law enforcement.

**Signature events:** **Bloomsday** (next: Sun, May 2, 2027) and the **Lilac Festival Armed Forces Torchlight Parade**. Also fun runs, bicycle races and **Climb for the Cure** (Sun, June 13, 2027). `[verify: Climb for the Cure]`

**What a shift looks like (adapted from the Section's tips):**
- Dress in layers and bring water, sunscreen and any medication.
- Bring charged and spare batteries, a pen and a small notepad.
- Check in on the talk-in frequency as you arrive, so you can be directed to parking.
- A speaker-mic or headset helps. Don't use VOX: it picks up crowd noise and ties up the frequency.
- If you have a spare radio, bring it for a volunteer who has none.
- You're part of a team. Net control will answer your questions.

**How to volunteer:** the AEC for public events (NV2Z) coordinates members for Bloomsday and the parade; the volunteer contact for Climb for the Cure is NZ2S. `[verify]`

Source: `interim-ag7qp/preparing-for-public-service-communications.md`, `home.md`; `content/live/03-*.md`; `content/live/02-*.md`.

---

## 9. Where we work (`#where-we-work`)

**H2:** Where we work

> In general terms, because some of these places handle sensitive information:

- **The county radio room** at Spokane County Emergency Management.
- **The county communications trailer and Mobile Network Units,** set up where an incident needs them.
- **Area hospitals,** where backup radio and computer equipment is kept ready.
- **Shelters, emergency operations centers and incident command posts** (ACS members).
- **Event courses** across Spokane.
- **The National Weather Service office** in Spokane, for SKYWARN Recognition Day.
- **Home stations** all over the county, for nets, Winlink and Standing Order 1b reports.

(Design note: do not show a map with hospital, cache or equipment locations. A generic county outline with icons is fine.)

---

## 10. Roles members take (`#roles`)

**H2:** Pick your post

> Everyone starts by checking into the net. From there, people find the work that suits them.

| Role | What you do | Open to | Training on the way |
|---|---|---|---|
| **Net control station** | Run a net: call check-ins, keep order, move traffic. | ARES and ACS (required yearly at ACS Radio Operator level) | Coached by the Net Manager; the NCS guide |
| **Field or shelter radio operator** | Pass messages from a shelter, staging area or event post. | ACS for shelters; ARES and ACS for events | ICS-213 and ICS-309; a go-kit |
| **Radio room operator** | Staff the county radio room during an activation. | ACS | Comm Room and trailer familiarization, then competency (a Radio Operator requirement) |
| **Hospital team** | Keep backup hospital communications ready. | ACS; HIPAA training required | Orientation; HIPAA |
| **Public-event operator** | Work a race course or parade route. | ARES and ACS | A short-term go-kit; event briefing |
| **SKYWARN spotter** | Report severe weather to the National Weather Service. | Anyone who takes the training | NWS spotter training (no license needed) |
| **Winlink and digital operator** | Send forms and reports by radio email. | ARES and ACS (yearly at ACS Senior Radio Operator) | Winlink workshop; assignments |
| **Mobile units and logistics** | Set up the trailer and Mobile Network Units, keep equipment and programming ready. | ACS (Senior Radio Operator) | MNU setup and take-down |
| **Public Information Officer** | Tell the group's story to the media and the public. | **Open position** | ARRL PR-101 (optional) |
| **Leadership (AEC)** | Plan training, lead exercises and coordinate events. | ACS Leadership level; ARRL membership required for ARES appointments | FEMA IS-120, 230, 235, 240, 241, 242, 244, 288 |

**CTA under the table:** **Join the team** → `index.html#join` · **Training path** → `about.html#training-path`

Sources: `interim-ag7qp/acs-task-book.md`; `content/live/03-ares-acs-staff.md`; `research/organization.md` §3-§4, §10.

---

## 11. When it was real (`#real-world`)

**H2:** When it was real

> During the Gray and Oregon Road fires in August 2023, the group provided a mobile network unit for the incident command post and the Disaster Assistance Center. `[verify]`

Source: Spokane COAD minutes, 2023-09-05 (`sources/government/spokane-COAD-minutes-2023-09-05.txt`). Keep this factual and short; no numbers beyond what the source says.

---

## 12. Next steps (page end)

**Heading:** Ready to try it?

Three actions:
- **Listen this Tuesday:** 8 PM, W7GBU 147.300 MHz → `index.html#visit`
- **Join the team** → `index.html#join`
- **Read the details:** membership, training, the legal basis → `about.html`

---

## Sources for this page

`interim-ag7qp/docs/net-preamble-2026-03-04.md`, `net-preamble-simplex-2026-03-04.md`, `net-preamble-gmrs-2025-08-19.md`; `interim-ag7qp/ares-acs.md` (SOP 1b), `ics-213.md`, `winlink.md`, `go-kits.md`, `new-members-orientation.md`, `acs-task-book.md`, `spokane-county-ares-acs.md`, `eastern-washington-section.md`, `preparing-for-public-service-communications.md`; `content/live/02-*.md`, `07-*.md`, `09-*.md`, `20-*.md`, `37-*.md`; `research/organization.md` §3, §5, §8, §9; `research/history.md` §5; `design/REVIEW.md` (Lines Down).
