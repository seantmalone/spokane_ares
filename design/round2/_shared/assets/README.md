# Round 2 shared assets

- **`data.js`**: the single data source for all three options (see its header comment and `../ia.md` §6).
- **`img/`**: every image from `design/assets/` (15 originals, unchanged filenames), plus 5 web-ready derivatives made on 2026-09-26. Each image below was viewed before it was described.

**Rules for every option**
1. **People:** never caption anyone with a name or call sign. Several file names contain a first name or a call sign (`Don-on-the-air.jpg`, `KD7GHZ.jpg`); rename them in production and never surface the file name as a caption or alt text.
2. **Consent:** identifiable people need a photo release before launch (`design/REVIEW.md`). Mark every people photo `data-verify="true"`.
3. **OPSEC:** no photo may show readable frequencies, channel labels, radio displays, screens, whiteboards, rosters, name badges, plates, hospital or EOC interiors, or equipment locations (decisions.md D13 "Never publish").
4. **Logos:** the ARES-ACS seal is the group's mark. The ACS seal, ARES emblem and SCEM seal are **affiliation marks** only (permission pending, D6), and the SCEM seal must never look like the group's own logo.
5. **Missing photos:** use a clearly marked frame: a neutral box with the label **PHOTO NEEDED** and a one-line shot-list caption, e.g. "PHOTO NEEDED: members at a hospital drill, wide, landscape". Use the shot IDs below (S1-S14). Never fake a photo with a stock image.

---

## Photos

| File | Size / orientation | Subject | Quality | Best use | Suggested alt text | Cautions |
|---|---|---|---|---|---|---|
| `IMG_6574.jpg` (original, 2.6 MB) and **`IMG_6574-2000w.jpg`** (derivative, 2000×1500, ~0.9 MB) | 3264×2448, 4:3 landscape | A portable station on a black pickup's tailgate at a mountain overlook: a silver-framed case (labelled as a repeater), a rugged rack case with radios and a digital interface, hand mics, a laptop, yellow legal pads and a binder, a water bottle. Forested valley and hazy hills behind; bright summer daylight; no people. | **Best in the set.** Sharp, modern, high resolution. | Home hero; "field-ready"; Winlink/digital; How it works "the system". Crop 16:9 or 21:9 from the lower two-thirds, or keep the sky and treeline for a text overlay. Use the 2000w derivative on the web. | "A portable emergency radio station set up on a pickup tailgate at a mountain overlook: radios in a rack case, a laptop and a binder of forms." | The left case appears to be a **portable repeater**. Region 9 radio-cache equipment is on the never-publish list, so confirm with SCEM that this photo is fine to show `[verify]`. |
| `8091bb_cfac747e80b142cf8c06b2286c1c6e0b.jpg` | 659×372, ~16:9 landscape | Six volunteers raising an antenna mast on an arched frame in a foggy, frosty field. Four wear hi-vis vests; one vest reads "SPOKANE COUNTY AMATEUR RADIO EMERGENCY COMMUNICATIONS". Winter morning, bare trees. | Low resolution and soft; strong content (teamwork, branding). | Team / "Join us" card, exercises, "Here's what it takes". Display at 660 px wide or less (330 CSS px at 2x). | "Volunteers in hi-vis vests raise an antenna mast in a frosty field." | Faces are identifiable: release needed. |
| `8091bb_6c9b42987e484f27a66a6a7748a7db9c.jpg` and **`operator-headset-badge-blurred.jpg`** (derivative) | 659×372, ~16:9 landscape | Close-up of an operator in headphones speaking into a hand mic. A parade "ARES Operators & Tac ID" route map is pinned beside him, with a first-aid box, radio manuals and a battery meter. Warm, human. | Low resolution; sharp enough at small sizes. | Public-service events; net control; "people" imagery. **Use the blurred derivative only.** | "An operator in headphones speaks into a hand microphone beside a parade route map." | The original shows a **legible name badge**; the derivative blurs it. Face identifiable: release needed. |
| `8091bb_f2f27737b12847819b5e343bf18efa27.jpg` | 659×372, ~16:9 landscape | Silhouette of a quad antenna on a lattice tower against a vivid magenta, orange and violet sunset; black foreground band. | Low resolution, but graphic and forgiving. | Mood band, section divider, dark backgrounds (duotone or blur). Avoid full-bleed at 1440 px without treatment. | Decorative in most uses (`alt=""`); otherwise "An antenna tower silhouetted against a sunset." | None. |
| `1920x1200sunset.jpg` | 1920×1081, 16:9 landscape | Dusk sky in blue gradients with streaky clouds and a faint peach horizon, over a flat black landscape with power poles and bare trees. | Noisy phone shot, soft; fine as a background. | Full-bleed background behind text; the dark bottom band suits a CTA. Not a subject photo. | Decorative (`alt=""`). | None. |
| `Don-on-the-air.jpg` | 1600×1200, 4:3 landscape | An older operator in a purple cap and navy fleece on a red folding chair, a laptop on his lap, with mobile radios and a second laptop on a vehicle bumper under a covered parking area; batteries on the ground. | Decent resolution; flash-lit; 2000s-era gear looks dated. | Field station / digital messaging at medium size; "real people, real gear". | "A volunteer runs a battery-powered radio and laptop station off a vehicle bumper." | **Do not caption with a name** (the file name has one). Release needed. Rename in production. |
| `Operating-in-VAN_4.jpg` | 640×480, 4:3 landscape | Looking into the open rear of a communications trailer marked "CHEVROLET": several operators in headsets at desks, one standing at the door. | Poor: a soft, low-contrast video still. | "Inside the trailer" at small size, or with a heavy duotone. | "Volunteers operating radios inside a communications trailer." | Nothing legible on the whiteboard at this size; keep it small. Releases for faces. |
| `KD7GHZ.jpg` | 301×200, landscape | An operator in a cap and camo shirt at an outdoor tabletop station (radios, laptop, water bottle) with a white church and trees behind; sunny. | Tiny. | Thumbnail only (150 px or less), e.g. in a collage. | "A volunteer operates a tabletop radio station outdoors." | **Do not caption with the call sign** (the file name is one). Rename in production. |

## Logos and marks

| File | Size | Description | Use | Cautions |
|---|---|---|---|---|
| `Spokane_County_ARES-ACS_logo_slightly_smaller_550x550.png` and **`seal-ares-acs-400.png`** (derivative) | 550×551 (400×400) | **Current seal:** navy ring (about #24348a) reading "SPOKANE COUNTY" and "ARES – ACS" around a white field with a navy county outline bordered in red (about #d0202a), a white antenna/mountain icon and "W7GBU". | Primary brand mark: header, footer, favicon source. Use at 200 px or less; redraw as SVG for production. | The original is 950 KB and slightly soft: use the derivative. |
| `ACS_logo_1000x1000.png` and **`ACS_logo-500.png`** (derivative) | 1000×993 (500×497) | **ACS seal:** dark purple-navy ring (about #302050) reading "SPOKANE AUXILIARY COMMUNICATIONS", a black antenna with signal waves, "ACS" in red. Clean. | Secondary mark beside ACS explanations (About `#what-is-acs`, join step 3). | The old site's alt text wrongly said "RACES logo". Alt: "Spokane Auxiliary Communications (ACS) seal". |
| `ARES_logo.png` | 121×121 | ARRL's national ARES emblem: red ring reading "AMATEUR RADIO EMERGENCY SERVICE" around the ARRL diamond. | Small affiliation badge (ARES explanations, footer). | ARRL trademark: permission and a vector from ARRL before launch (D6). |
| `SCDEM_Logo.png` and **`SCDEM_Logo-600.png`** (derivative) | 2400×2400 (600×600) | **Spokane County Emergency Management** seal: orange ring (about #f09010) on black; quadrants with a yellow radio tower, storm and flood, fire, and a warning triangle. High quality. | "Who we serve" and footer **partner** mark only. | County permission needed (D6). Must never read as the group's logo. |
| `Spokane_County_ARES_RACES_logo_dash.png` | 121×121 | **Legacy** county seal reading "ARES-RACES". | History section only. | Not for current branding. |
| `textLogoACS_dash.png` | 450×125 | WordArt-style wordmark "Spokane County / ARES – ACS" in red with a blue drop shadow. | **Do not use.** Set the name in the option's typeface. | — |

## Map

| File | Size | Description | Use | Cautions |
|---|---|---|---|---|
| `DEM_map.png` | 994×675 | Google Maps screenshot pinning 1121 W Gardner Ave, Spokane, WA 99260: N Jefferson St, W Gardner Ave, the Spokane County Courthouse to the south (Map data ©2024 Google). | Mockup stand-in for "Where we meet". Prefer a simple drawn locator (courthouse, Jefferson, Gardner). | Google's map imagery can't be reused on the live site; use an embed or a drawn map. |

## Palette cues (from the marks)

- Seal navy ~#24348a · Signal red ~#d0202a · ACS purple-navy ~#302050 · SCEM orange ~#f09010 with black.
- Don't use traffic-light green/yellow/orange/red as a "status" treatment anywhere (no public alert status).

---

## Shot list: photos the site needs (none of these exist yet)

Every shot needs releases for identifiable people and an **OPSEC review** (no readable frequencies, screens, channel labels, whiteboards, rosters, badges or plates). Anything inside a hospital, the EOC or the county radio room also needs **SCEM (and hospital) approval**; for hospitals, prefer an illustration. Minimum 2400 px on the long edge for hero shots; 1600 px otherwise.

| ID | Frame caption for the placeholder ("PHOTO NEEDED: …") | Where it's used | Orientation | Notes |
|---|---|---|---|---|
| **S1** | members at a field station during an exercise, headsets and forms, antenna mast behind, wide | Home hero (people version), How it works top | Landscape 16:9 / 21:9 | Daylight; two or three people working, faces visible; the single most important shot |
| **S2** | net control at a home station on a Tuesday night, logbook and handheld, warm light | Home "This week", How it works `#weekly-net` | Landscape 3:2 | Shows that the net is run from ordinary homes |
| **S3** | volunteers working inside the county communications trailer, wide | What we do, `#where-we-work` | Landscape 3:2 | No readable screens or radio displays; SCEM approval |
| **S4** | the communications trailer deployed outdoors with its mast up, exterior | How it works `#where-we-work`, Roles | Landscape 3:2 | Exterior only; no plates; no configuration close-ups |
| **S5** | an operator in a hi-vis vest on a race course with runners passing, wide | Public-service events, Home "What we do" | Landscape 3:2 | Event organizer permission (e.g. Bloomsday) |
| **S6** | an operator with a handheld along the Torchlight Parade route at night | Public-service events | Landscape 3:2 | Low light; crowds out of focus |
| **S7** | a Second Saturday workshop: members programming handhelds or crimping Powerpoles around a table | Home "Three ways to visit", About `#where-we-meet` | Landscape 3:2 | Include newcomers and a range of ages |
| **S8** | a first-time visitor being welcomed at a Third Thursday meeting | Home `#visit`, About FAQ | Landscape 3:2 | Friendly, candid; the "come say hello" promise |
| **S9** | Winlink on a laptop: radio, interface and a sample ICS-213 on screen, close-up | How it works `#winlink`, Members Winlink | Landscape 4:3 | Screen shows only a **sample** form; no real addresses |
| **S10** | a personal kit and a radio go-kit laid out flat, overhead | About `#equipment`, Members readiness | Square 1:1 and 4:3 | Easy to shoot; high value for members pages |
| **S11** | a SKYWARN spotter watching a building storm sky with a handheld | What we do (weather), About | Landscape 3:2 | Safe vantage point; no chasing imagery |
| **S12** | the SKYWARN Recognition Day station at the NWS Spokane office | Exercises, History | Landscape 3:2 | NWS permission |
| **S13** | a Winter Field Day or Field Day station: tent, generator and antenna, wide | Exercises, History | Landscape 16:9 | Snow version for Winter Field Day |
| **S14** | the entrance members use at Spokane County Emergency Management, 1121 W Gardner Ave, exterior | About `#where-we-meet`, Home `#visit` | Landscape 3:2 | Pairs with first-visit directions (still unknown, D13 #19); county permission |

**Not needed:** portraits of leaders (the leadership table is role + call sign only), photos of hospital interiors or stations, radio-room close-ups, repeater-site access or security details.
