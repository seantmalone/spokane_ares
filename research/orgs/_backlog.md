# Related-orgs research backlog

`relationships.md` is a **light pass** (2026-09-25): built from 5 discovery agents' evidence, without per-org deep profiles or adversarial fact-checks. Use this backlog to go deeper on any org when needed.

## How to deep-profile an org later

The workflow script `research/tools/workflows/spokares-related-orgs.js` has a deep mode that skips discovery and runs profile + adversarial verify for only the orgs you pass. Ask Claude to run it with args like:

```json
{"today": "<YYYY-MM-DD>", "hierarchy_sketch": "<from _canonical-orgs.json>", "deep": [<org objects copied from _canonical-orgs.json .orgs>]}
```

Each deep profile is written to `research/orgs/<slug>.md` with a Verification section.

## Orgs and open questions

### Core

#### Spokane County ARES-ACS / RACES (subject organization) (`spokane-county-ares-acs-races`)
*subject-organization* — This is the organization whose site is being rebuilt. It serves as the hub entry that absorbs people, platform and history mentions about the group itself.

Affiliation and leadership (CONFIRMED): The spokares.org About page says the group is 'affiliated with the ARRL ... and Spokane Emergency Management'. The leadership page (last modified 2026-03-24) lists:
- EC and Website Admin: Asa Jay Laughton W7TSC. The Welcome Letter signs him as EC and RACES Officer. Wayback captures show him signing as EC/RO since at least 2019.
- AECs: Dan Croskrey NV2Z (Public Events; W7GBU-10 and Winlink); Mary Griffith K7MHG (Hospital Coordinator); Dave Carleton K7DSR (Logistics & Comms, also named training AEC); Frank Hutchison AG7QP (Net Manager).
- PIO: open.

Meetings and nets: The group meets at SCEM, 1121 W Gardner Ave. The current schedule on ag7qp.com is 1st Tue 5:30 PM staff, 2nd Sat 9 AM workshop, 3rd Thu 6 PM training and 4th Tue orientation. This conflicts with the older times on spokares.org and in the Welcome Letter. Nets:
- Tuesday 8 PM on the 147.30 W7GBU repeater.
- Simplex net on 5th Tuesdays.
- ACS GMRS net, 3rd Tuesday, on WRCY281.
- Twice-monthly Winlink assignments.

groups.io (CONFIRMED, 2026-09-25): 107 members and 2,882 topics. Subgroups are Digital-Modes, HamWan, Hospital-Coordination, Leadership, SHARES, Winlink and WinterFieldDay. The Spokane County ACS Field Operating Guide (ACSFOG) is distributed there.

Web history (CONFIRMED):
- The domain was registered 2004-03-10 and expires 2027-03-10.
- About 2004-2015: a static site on hosting donated by ASISNA.
- 2015-16: a Wix interim site.
- From 2017: Joomla 3, using the js_wright template by PA4RM and hosted on InMotion.
- Wayback on 2025-03-15 shows a '/lander' redirect.
- The EWA SEC says 'The spokares.org website was hacked'. The Downloads pages currently fail with an Alledia framework error.

Other facts:
- The ARES/RACES Plan on the site is 'Attachment 3 to ESF-2' of the old City/County CEMP. Its content dates from about 2001-2013, although the site shows it as published 2017 and modified 2018-11-21.
- IEVHFRA's Oct 2019 newsletter says 'the other group [ARES] has no bank account'.
- KXLY (2011) reported about 40 active members.
- Historical leaders include EC Bob Wiese KD7AMV/W7UWC and plan co-author Gordon Grove WA7LNC.
- INFERENCE: day-to-day coordination may have moved to AG7QP during the site outage.
- [ ] Who are the current EC, AECs and PIO as of fall 2026? Is W7TSC still the EC, RACES Officer and website admin, given that the temporary ag7qp.com page names Frank Hutchison AG7QP as contact?
- [ ] What official name should the rebuilt site use: 'Spokane County ARES-ACS', 'ARES/RACES' or 'ARES/ACS/RACES'? Does SCEM have a preference?
- [ ] Build a complete content inventory of spokares.org: every Joomla article, menu item, JCal Pro event category, OSDownloads file (currently broken) and the Wayback-only pages (2004-2024). Which items must migrate, and which are obsolete?
- [ ] What happened in the 2026 compromise? Who controls the domain (GoDaddy) and hosting (InMotion) credentials? Did the 2025 '/lander' capture mean the domain lapsed?
- [ ] Does the group have bylaws, officers, a bank account or any legal entity besides the W7GBU Support Group? Does IEVHFRA still act as its fiscal conduit?
- [ ] Which meeting schedule is authoritative? The current ag7qp.com times (3rd Thu 6 PM, and so on) conflict with the spokares.org and Welcome Letter times (7-9 PM, 3rd Thu 1900).
- [ ] Is the ARES/RACES Plan being revised or replaced by an ACS SOP? Which version should the new site publish, if any?
- [ ] What are the roster size and activity levels? Which member-only materials (roster PDF, ACSFOG, preambles) must sit behind a login rather than be published?
- [ ] What is the status of the HamWan subgroup project and the 2019 AREDN idea?
- [ ] Have the member presenters (Mel Ming N7GCO, K7PKT, AD7DD, WA7AQH) given permission to republish their slides and videos?

#### Spokane County ARES RACES Support Group (W7GBU club license, 147.30 repeater and W7GBU-10 Winlink gateway) (`w7gbu-support-group`)
*legal-regulatory* — This is the legal and FCC entity behind the group's on-air identity.

License (CONFIRMED, FCC ULS/callook):
- W7GBU is a CLUB license held by 'SPOKANE COUNTY ARES RACES SUPPORT GROUP'.
- Trustee: K7DSR David E. Carleton, who is also the AEC for Logistics & Comms.
- Previous call: KC7SOT.
- Granted 2017-02-15; EXPIRES 2027-02-15.

History (CONFIRMED, W7GBU History page, 2017): The Support Group was formed to obtain the call of the late Ira 'Dean' Bula W7GBU, with his widow Floy's support, after the county retired the old ARES call on 147.30. Bula ran the '7:30 Bunch' morning net, was active with the Spokane Dial Twisters and IEVHFRA, and worked the counter at Northwest Electronics.

Repeaters (RepeaterBook, reviewed 2025-03-20):
- 147.300+ PL 100 on Brownes Mountain. IACC record 1359, coordination to 2029-06-28, emergency power.
- 443.400+ UHF. IACC record 1253, to 2029-02-09.
- The ARES net (Tue 8 PM) and the weekday 0730 Bunch still run on 147.30.

W7GBU-10 Winlink gateway: managed by NV2Z. DISCREPANCY: the old Plan puts it at the Combined Communications Building on 145.090 simplex; K7BFL's 2016 table lists it on 145.01 via WR7VHF-3. A legacy W7GBU HF Pactor gateway on 3624 kHz has been 'not in operation' since 2006.

Stale content: the site's Repeater Etiquette article (2021) still names WA7AQH as trustee.

INFERENCES: The repeater hardware may be county-owned, since the old site called it the DEM repeater and ag7qp.com labels it 'SCEM'. No 501(c)(3) or state registration was found, so the Support Group looks like a licensing vehicle only.
- [ ] Who will renew W7GBU before 2027-02-15? Is K7DSR still the trustee?
- [ ] Who owns the 147.30 and 443.40 repeater hardware and the Brownes Mountain site (Support Group, SCEM or an individual)? Is there a site agreement?
- [ ] What are W7GBU-10's current frequency, site and host (145.01 or 145.090; is it still at the CCB/SREC building given the 2026-2028 911 transition)?
- [ ] Is the 443.400 repeater in active ARES use?
- [ ] Does the Support Group have officers, bylaws or a Washington nonprofit registration?
- [ ] Are the IACC coordination records current, and who maintains them?
- [ ] Which History and Repeater Etiquette content should carry over, and what trustee corrections does it need?

#### ARRL, The National Association for Amateur Radio (HQ, Board, national served-agency agreements) and ARRL Northwestern Division (`arrl-national-and-northwestern-division`)
*arrl-hierarchy* — ARRL is the national sponsor of ARES. The spokares.org About page says the group is 'affiliated with the ARRL', and the Spokane Plan calls ARES an activity of the ARRL through Field Services.

Officers and staff (CONFIRMED, arrl.org, fetched 2026-09-25):
- President: Rick Roderick K5UR.
- CEO: David Minster NA2AA.
- Emergency Communications-Field Services Director: Josh Johnston KE5MHV.
- Field Organization supervisor: Steve Ewald WV1X.
- The Board's EC-FSC committee sets ARES policy (chair: Scott Yonally N8SY).

National served-agency agreements that Spokane relies on (CONFIRMED):
- American Red Cross MOU, renewed April 2021.
- NWS SKYWARN MOU, 1986.
- FEMA MOA, 2023. The Spokane Plan still cites the 1984 FEMA MOU (outdated).
- Salvation Army/SATERN MOU.
- NVOAD membership.
- Radio Relay International MOU, 2025-07-18.

Northwestern Division (CONFIRMED): This is Spokane's ARRL division, with six sections including EWA. Director Mark Tharp KB7HDX and Vice Director Michael Sterba KG7HQ serve terms from Jan 2025. The division publishes Northwestern News; the Aug 2025 issue describes 'Spokane County ARES/ACS, W7GBU' Field Day in Spokane Valley. It hosts SEA-PAC (next June 4-6, 2027).

Policy tension: under the July 2025 ARES Plan, full ARRL membership is required for Intermediate level and above and for EC/AEC appointments. The Spokane Plan says membership is not required to join.
- [ ] Confirm ARRL officers, HQ emergency-management staff and Northwestern Division leadership close to the rebuild launch date.
- [ ] Which ARRL national agreements (current versions and URLs) should the rebuilt site cite in place of the Plan's 1984 FEMA MOU reference?
- [ ] What are ARRL's rules for using ARRL/ARES names and logos on a local ARES group website?
- [ ] How should the site explain the ARRL-membership requirement for ARES Intermediate and above, alongside open county ACS membership?
- [ ] Are there other Division newsletter or annual-report mentions of Spokane ARES (Field Day, SET, awards) worth linking?
- [ ] Is Spokane ARES listed in any ARRL group or ARES locator, and is that listing accurate?

#### ARRL ARES program: ARES Plan (July 2025), Task Book, reporting system, EmComm courses and national activities (SET, Field Day, PSHR) (`arrl-ares-program`)
*arrl-program* — This is the ARRL program behind the 'ARES' half of the group.

Levels under the July 2025 ARES Plan (CONFIRMED):
- Basic: FEMA IS-100/200/700/800, ARRL Basic EmComm and the Level 1 Task Book.
- Intermediate: adds Intermediate EmComm and full ARRL membership.
- Advanced: adds IS-230/240/241/242/244/288. Required for EC, AEC, DEC and SEC, plus the ARES Leadership course for leaders.

MISMATCHES on spokares.org:
- The Join page requires the ARES Task Book but links only the generic arrl.org/ares page.
- The Welcome Letter still lists only IS-100.C and IS-700.B.

Reporting: monthly EC reports go on Form 2 and SEC reports on Form 4 at ares.arrl.org. FSD-98 is the ARES registration form. The Spokane Net Manager's net statistics flow EC to SEC to the League.

Courses (CONFIRMED): EC-001 and EC-016 were replaced in May 2024 by Basic and Intermediate EmComm; Advanced EmComm is 'on hold'. ARRL credits Jack Tiley AD7FO 'of Spokane County ARES/RACES' with the 2011 EC-001 materials.

National activities:
- SET 2026 is Oct 3-4 (on the Spokane calendar Oct 3). The IEVHFRA MOU names SET as joint training.
- Field Day participation is documented for 2006-2025.
- ARES Connect (LIKELY retired June 2021): do not integrate.
- PSHR relevance is INFERRED only.
- [ ] Which ARES Plan requirements does Spokane actually enforce, and how should the site show ARES levels next to the stricter county ACS Task Book?
- [ ] What are the stable direct URLs for the current Task Book PDF and the ARES Plan?
- [ ] Does the Spokane EC file ARRL Form 2 monthly and a SET report? Are recent numbers public?
- [ ] What are Spokane's plans and results for SET 2026, and Field Day and Winter Field Day history, for the events/history section?
- [ ] Are there Spokane-area OES, PIO or other Field Organization appointees?
- [ ] Do members report to the PSHR?
- [ ] When will Advanced EmComm be available? This affects Advanced-level and ACS S-RADO wording.

#### ARRL Eastern Washington Section (EWA): SM/SEC office, ARES District 9, EWSEN nets and Standing Order 1b (`arrl-eastern-washington-section`)
*arrl-hierarchy* — Spokane's ARRL section. It covers 20 counties in ARES Districts 7, 8 and 9, and had 905 ARRL members in Nov 2024.

Officials (CONFIRMED, arrl.org, 2026-09-25):
- SM: C. Jo Whitney KA7LJQ of Yakima. She was appointed in 2021 and elected unopposed for terms starting Oct 2023 and Oct 2025. INFERENCE: her current term runs to Sept 2027. The SM appoints the county EC.
- SEC: Frank Hutchison AG7QP of Spokane Valley. spokares.org also lists him as Spokane AEC Net Manager. This dual role needs confirmation.
- ASMs include Mary Wiese AA7RT, who is also Spokane's SKYWARN lead and a VE.
- TC: Jack Tiley AD7FO, the former SM (2019-2021) and local license-class instructor.
- ACC: Harold Hepner AD7QJ.
- No STM is listed.

District 9: its DEC is C.M. 'Sam' Jenkins WA7EC, who is also Ferry County EC/RO. It maps directly to WA EMD Region 9.

EWSEN Monday nets (CONFIRMED):
- VHF at 1815 on the KBARA 147.36/147.38 system.
- HF at 1845 on 3985 kHz.
- Winlink check-ins to EWA-WL2K from 1700 to 1900.

Standing Order 1b (2026-01-16) is the section's self-activation SOP:
- Nets on HF 3985/3990/3995, then 40 m 7245/7250/7255, then KBARA.
- Winlink WA Field Situation Reports and DYFI reports go to the local EM, EC, DEC and SEC.
- It defers to the local AHJ.

PARTLY STALE: District 7/8 DEC listings (for example Koehler KI4EZU). DISCREPANCY: sources disagree on whether Columbia County is in District 8 or 9.
- [ ] When was Frank Hutchison appointed SEC? Does he still also serve as Spokane AEC Net Manager, or is the spokares.org listing stale?
- [ ] Is the District 9 DEC (WA7EC) current? What does the DEC expect from Spokane (reports, net duty)?
- [ ] Is the EWA Section Traffic Manager post vacant?
- [ ] What does the section require of county groups (monthly reports, EWSEN check-ins, SET tasks)? How should Standing Order 1b be summarized or linked on the Spokane site?
- [ ] Does the section have an official web presence besides ag7qp.com and the Facebook group?
- [ ] Which district is Columbia County in (8 or 9)?
- [ ] Should the rebuilt site list Mary Wiese AA7RT and Jack Tiley AD7FO in their section roles, local roles, or both?

#### ag7qp.com: EWA SEC's 'EmComm Inland Northwest' hub, hosting the temporary Spokane County ARES-ACS webpage (`ag7qp-com-temporary-spokane-site`)
*platform/tool* — CRITICAL for the rebuild (CONFIRMED). This Zyro/Hostinger site is run by Frank Hutchison AG7QP; its sitemap lastmod is 2026-09-23. It states: 'The spokares.org website was hacked. This page acts as a temporary repository for general information for the Spokane County ARES-ACS group.'

It currently hosts Spokane's live operational content:
- The Sept-Oct 2026 Tuesday net-control roster (KE7RAP, NZ2S, AE7RJ, WA7LNC and others).
- Net preambles: main 2026-03-04, simplex, and GMRS 2025-08-19.
- The ARES-ACS roster PDF (2026-08-04) and the Winlink assignment schedule.
- The ARES membership application (2024-11-07) and the ARES and Spokane ACS Task Book guides.
- New Member Orientation.
- The Fall 2026 calendar: WSDOT exercise 9/26-27, SET 10/3, ShakeOut 10/15, SHARES training 10/17, State COMMEX 10/24.

It also carries section-level material: District 9 EC contacts, an Inland Northwest nets list, license-exam and class pages, CERT pages and Standing Order 1b.

STALE items include Alert Spokane described as 'Code Red'.
- [ ] Inventory every Spokane-specific page and zyrosite asset (docx/pdf) on ag7qp.com, with dates.
- [ ] Which content should move to the rebuilt spokares.org, and which should stay on the section-level site? Will the temporary page redirect after launch?
- [ ] Does AG7QP permit copying the content? Who edits which pages going forward?
- [ ] Which currently public documents (roster PDF with contact data, Winlink addresses) should become members-only on the new site?
- [ ] Which stale items need correcting during migration (Alert Spokane/CodeRED, meeting times)?

#### Spokane County Emergency Management (SCEM) and its Auxiliary Communication System (ACS) volunteer program (`spokane-county-emergency-management`)
*government/served-agency* — SCEM is the primary served agency and the Agency Having Jurisdiction.

ACS program (CONFIRMED): ACS is SCEM's own volunteer program. The county ACS page says members may use government equipment and frequencies and that 'RACES ... is now incorporated into ACS'. The county page (ID 1813) was renamed from 'ARES/RACES' to 'ARES/ACS' (by 2020) to 'ACS' (current). The 2020 version linked to spokares.org; the current one does not.

Joining ACS requires the ARES application, the county Volunteer Emergency Worker Application and a Sheriff's Office background check. IS-100 and IS-700 are required before a county ID is issued. The ACS Task Book (v2024-08-09) sets requirements beyond ARES.

Authority and county plan (CONFIRMED):
- The 2021 CEMP makes the Sheriff the Director of Emergency Management and lists ACS as a supporting agency in ESF-2, 5, 6, 8 and 9.
- A county Communications Specialist post (Sheriff's department, created 2023) 'develops, coordinates, and maintains' ACS, supervises ACS volunteers and coordinates radio volunteers across Homeland Security Region 9. SCEM is the Region 9 lead.
- Current officials: Sheriff John Nowels and Deputy Director Chandra Fox (Aug 1, 2026 release). The BOCC declared a state of emergency for the Aug 2026 fires.
- The Inter-Local Agreement covers Spokane Valley and 11 other municipalities plus the unincorporated county. It does NOT cover the City of Spokane.

HISTORICAL: Emergency management was a joint city-county operation (GSEM) until the City left in 2020. The old Plan was Attachment 3 to ESF-2 of the City/County CEMP. SCC 1.08 (LIKELY) names the old 1618 N Rebecca office.

Other: ALERT Spokane moved to Regroup in April 2026. SCEM also runs the INW LECC/EAS; the old Plan's idea of using EAS to call out ARES is SPECULATIVE. County-owned equipment (comm room, trailer, MNUs, hospital radios, caches) is what ACS members train on. Stale spokares.org labels still say 'Greater Spokane Emergency Management Application' and 'GSEM'.
- [ ] Who is SCEM's current Communications Specialist or ACS coordinator? What are SCEM's expectations for the rebuilt site (approval, branding, county links, required disclaimers)?
- [ ] What is ACS's official name: 'Auxiliary Communication System', 'Systems' or 'Services'?
- [ ] Is there a CEMP or ESF-2 annex newer than March 2021? Does a current ACS SOP or plan replace the 2001-2013 ARES/RACES Plan?
- [ ] Is there a current written RACES Officer or ACS lead appointment (W7TSC)?
- [ ] How are activations, mission numbers, volunteer ID cards and emergency-worker registration handled in practice today?
- [ ] Which equipment does SCEM own (147.30 repeater, comm room, comm trailer, MNUs, hospital radios, Region 9 caches), and where is it located?
- [ ] Does ACS serve the City of Spokane since the 2020 split, and on what basis?
- [ ] What stable URLs should the site use for the county application and ACS page? Which stale GSEM references need correcting?

#### Washington Emergency Management Division (WA EMD, Washington Military Department): State EOC/AUXCOMM, RACES planning and Homeland Security Region 9 (`washington-emergency-management-division`)
*government/served-agency* — State-level authority and served agency (CONFIRMED).

Mission numbers: the Spokane Plan requires SCEM to obtain a mission number from the state before ARES/RACES serves government agencies. The mission number is what triggers RCW 38.52 emergency-worker coverage.

RACES planning:
- The 1995 State RACES Plan requires local EM to appoint a RACES Officer in writing, keep a local RACES plan and register participants as emergency workers.
- It was superseded by the EMD Logistics Section Communications Annex (about 2021), which 'supersedes all preceding State RACES Plans'.
- DISCREPANCY: wastateares.org still hosts a Nov 2013 State RACES Plan, and the Spokane Plan cites the state plan without a version.
- The Annex covers 12 SHARES Winlink RMS stations, a 220 MHz network, the SEOC radio room and state RADO position task books. The Spokane ACS Task Book uses the same RADO/S-RADO structure.

Exercises: the State EOC AUXCOMM team at Camp Murray (W7EMD) runs EOC-to-EOC exercises on 5th Saturdays. Spokane joined them historically, for example sending WA ICS-213RR by Winlink in March 2019. A State COMMEX is on the Spokane calendar for Oct 24, 2026.

Regions: EMD's 9 regions map directly to the 9 ARES districts. Region 9 (10 counties plus the Kalispel and Spokane Tribes) is led by SCEM. The 'R9 Radio Cache' portable repeaters appear in the ACS Task Book (the owning agency is INFERRED).

Forms and guides: WA ICS-213RR and the WA State Field Situation Report (2026 SET). WAFOG v1.10 (May 2026) exists per search, but its URL returned 404. The SCIP was updated in 2023/2024. AUXC/COMT courses are requested through the SWIC (LIKELY).
- [ ] What is the current authoritative state document for RACES/AUXCOMM (the Communications Annex version), and what does it require of county programs like Spokane ACS?
- [ ] Is a Spokane ACS or ARES plan on file with EMD? Should the rebuilt site cite the Annex rather than the 1995 or 2013 RACES Plan?
- [ ] How does the mission-number process work today, and what can volunteers be told about coverage?
- [ ] What is the current EOC-to-EOC and State COMMEX schedule, and how does Spokane take part?
- [ ] Who manages the Region 9 radio cache, and are Spokane members credentialed as state RADOs?
- [ ] What are the current URLs for the WA ICS-213RR, the Field Situation Report Winlink template and WAFOG?
- [ ] Who is the State EOC radio room or AUXCOMM contact (the spreadsheet lists Scott Dakers W7SGD)?

#### Inland Empire VHF Radio Amateurs (IEVHFRA, 'VHF Club', WR7VHF) (`ievhfra`)
*local-ham-club* — The group's closest formal partner.

The MOU (CONFIRMED): 'MOU Regarding Emergency Operations On Repeater Systems in Spokane County', Revision #5 dated 2023-05-06. Points of contact were W7TSC for ARES and president@vhfclub.org for IEVHFRA. Under it:
- ARES/ACS/RACES is the primary emergency center, activated by SCEM, using 147.30 W7GBU.
- IEVHFRA provides the 146.88 backup, a second repeater for specialized nets such as weather, and packet/UHF paths for ICS-213 and WA ICS-213RR over Winlink.
- The MOU says POCs should be revisited annually and that both websites will post it; it was not found on vhfclub.org.

CURRENT: the March 2026 Spokane preamble names 146.880 as the 'alternate Spokane County ACS repeater'.

Club systems:
- Repeaters: 146.880- (tone 123, Krell Hill), 147.340+, 444.600+, 444.900+.
- Packet nodes: WR7VHF-3 (145.010) and WR7VHF-4 (145.090).
- WR7VHF-10 RMS on a 440.125 9600-baud backbone.
- The club has been ARRL-affiliated since 1987. Its license is valid to 2031 (trustee AD7QJ).

Other ties:
- The club co-sponsors license classes with SCEM and ARES (Jack Tiley AD7FO).
- Its Oct 2019 newsletter says IEVHFRA took a $300 donation 'for ARES' because ARES has no bank account.
- Officers overlap with ARES: NZ2S is president and an ARES net control; NV2Z is VP and an ARES AEC; K7DSR was 2019 president. The ARES-ACS comm trailer appeared at the June 2026 tailgate.
- It sponsors Climb for the Cure and co-sponsors the Spokane Hamfest.
- [ ] Is MOU Rev 5 (2023-05-06) still in force? Have any revisions or annual POC reviews happened since?
- [ ] Who are IEVHFRA's current officers and MOU POC?
- [ ] Does IEVHFRA still hold funds for ARES? What is the arrangement?
- [ ] What are the current status and frequencies of the WR7VHF packet nodes and WR7VHF-10 (these matter for the Winlink instructions on the new site)?
- [ ] What is the 2026-27 schedule for joint license classes and testing?
- [ ] Will vhfclub.org post the MOU and cross-link the rebuilt spokares.org?

#### National Weather Service Spokane (NWS OTX) and SKYWARN (`nws-spokane-skywarn`)
*government/served-agency* — A long-standing served-agency partner (CONFIRMED).

On spokares.org:
- The homepage says the group activates severe-weather nets to assist NWS Spokane.
- The SKYWARN page (2018) names Mary Wiese AA7RT primary SKYWARN representative and Bob Wiese W7UWC assistant. It links a STALE July 2016 training playlist. The Ham Radio Links page uses a dead wrh.noaa.gov URL.
- The Status Codes page tells members to monitor WXL86 (162.400) and W7GBU.

Plan and MOU: Plan Attachment 13 treats spotting as non-emergency ARES activity under the ARRL-NOAA MOU. It says DEM authorization and a mission number are needed only for field observers or for staffing the ARES station at the NWS office.

SKYWARN Recognition Day: the group has run an SRD station inside the NWS office in Airway Heights every year since 1999 (2017 and 2019 recaps). SRD 2026 is Dec 4-5. NWS was an exercise site in March 2019.

Current: the ACS Task Book encourages biennial spotter training. NWS OTX ran virtual spotter classes in spring 2026; fall 2026 is TBD.
- [ ] Who is the current NWS OTX warning coordination meteorologist or SKYWARN contact, and how do they want amateur spotter reports received?
- [ ] Is there still an ARES/ACS station or antenna at the NWS Spokane office? What are the SRD 2026 plans?
- [ ] Are Mary Wiese AA7RT and Bob Wiese W7UWC still the SKYWARN representatives?
- [ ] When are the fall 2026 and spring 2027 spotter classes, and what are the current training links to replace the 2016 playlist and dead wrh.noaa.gov link?
- [ ] Does the group still activate formal severe-weather nets, and on which repeater (147.30 or IEVHFRA 146.88 per the MOU)?

### Related

#### Washington State ARES / WaEmcomm (wastateares.org), Washington State Emergency Net (WSEN) and ARRL Western Washington Section (`washington-state-ares-waemcomm-wsen`)
*arrl-hierarchy* — wastateares.org (CONFIRMED) is the statewide ARES/RACES/AUXCOMM information hub, a Google Site. spokares.org links it ('Washington State ARES/RACES') and its WSEN page.

What it is and hosts:
- It is not a chartered ARRL body. INFERENCE: it is maintained mainly by Western Washington leadership, since corrections go to N7SS.
- Local Contacts lists the Spokane EC as W7TSC under Region 9 (DEC WA7EC). It states the 9 EMD regions = 9 ARES districts mapping.
- Also hosted: ARRL forms, an EC/RO monthly Google Form, the state comm plan (SCIP, ESF-2), and documents including the WA RACES Plan (Nov 2013), AUXFOG and NIFOG.
- The training recommendations are STALE (EC-001/EC-016, old FEMA versions) and accept Oregon ACES Basic.

WSEN (CONFIRMED):
- Frequencies: 3985 kHz LSB, alternate 7245.
- Schedule: Monday regional/county sessions at 1830 PT; Saturday statewide at 0900 PT.
- Net manager: Michael Ditmore W7HUT.
- The Spokane Plan names it the first 'Significant HF Network'.
- A 2019 spokares.org calendar assigned WSEN District 9 net-control slots to Spokane (W7TSC) and Stevens (WD7K).

WWA Section (per the state spreadsheet and NW Division): holds Districts 1-6. SM Bob Purdom AD7LJ; SEC Frank Wolfe NM7R. Its 501(c)(3) serves WWA teams only.
- [ ] Does Spokane still provide WSEN District 9 net control, and on what schedule?
- [ ] Is the Spokane and District 9 information on wastateares.org Local Contacts current? Who can correct it?
- [ ] Should the rebuilt site link WSEN on wastateares.org, the section material on ag7qp.com, or both?
- [ ] Are any statewide ARES/AUXCOMM requirements or reports (the EC/RO monthly form) expected of Spokane beyond the ARRL forms?
- [ ] Which state documents on wastateares.org are current, and which are superseded (2013 RACES Plan versus the EMD Annex)?

#### American Red Cross, Greater Inland Northwest Chapter (and the ARRL-Red Cross MOU) (`american-red-cross-inland-northwest`)
*government/served-agency* — The main NGO served agency.

Current ties (CONFIRMED):
- The county ACS page names 'response partners such as the Red Cross'.
- The Welcome Letter recommends Red Cross Disaster Service and Shelter Fundamentals.
- The ACS Task Book includes a 'Red Cross Familiarity Training' that is still under development.
- The 2021 CEMP makes the Inland Northwest Chapter the ESF-6 (Mass Care) lead, with ACS supporting.
- The Red Cross and ARES/ACS both reported at the Sept 2023 COAD.

HISTORICAL (Plan Attachment 14): ARES has 'a traditional bond of service to Red Cross'. The chapter office was then at 315 W Nora, with operators at shelters and ERVs. ARES may serve the Red Cross without EM authorization under the ARRL-Red Cross MOU, renewed April 2021. KXLY (2011) reported an ARES shelter station during the Valley View Fire relaying information to the Red Cross.

INFERENCE: any local MOU would now need the EWA SM's or SEC's signature. The chapter page returned 403 to automated fetches, so the current address is unverified.
- [ ] What are the current chapter name, address and disaster program contact?
- [ ] Does any local MOU or written agreement exist between the chapter and Spokane ARES/ACS?
- [ ] Do ARES/ACS operators still staff shelter or ERV communications? Does the chapter have its own Disaster Services Technology radio volunteers?
- [ ] Is the Red Cross familiarity training in the ACS Task Book available, and how is it scheduled?

#### Spokane COAD, the VOAD network and faith-based emcomm partners (Salvation Army/SATERN, LDS Emergency Response Communicators) (`voluntary-and-faith-based-partners`)
*partner-volunteer-org* — Spokane COAD (CONFIRMED participation): ARES/RACES/ACS gave reports at COAD meetings in June 2016, Sept 2017 and Sept 5, 2023. At the 2023 meeting it reported providing a mobile network unit for the ICP and the Disaster Assistance Center in the Gray and Oregon Road fires. The March 2024 agenda lists 'ARES/ACS - Frank Hutchison'. COAD documents are in SCEM's AgendaCenter, and the 2021 CEMP names COAD the ESF-6 coordinator with ACS supporting.

VOADs: ARRL is an NVOAD member, and the 2025 ARES Plan encourages local VOAD membership.

HISTORICAL (Plan Attachment 15): the Salvation Army (222 E Indiana) had 'traditional bonds of service', and EWAVOAD was a route to extra service requests. Whether EWAVOAD exists today is unverified. ARRL has a SATERN MOU (2018), but no local SATERN activity was found.

LDS ERC Spokane Coordinating Council (LIKELY; linked by people, not by agreement):
- ag7qp.com lists ERC nets on HF, KBARA, 146.500, GMRS 7 and a Winlink net 'addressed to AG7QP & K7DSR-1'.
- K7DSR (ARES AEC and W7GBU trustee) runs NAWN training, an equipment GoFundMe and an ERC convention on 2026-11-14.
- Stake and ward nets use the IEVHFRA and KARS repeaters.
- No MOU was found.
- [ ] Is Spokane ARES/ACS a formal Spokane COAD member? Who represents it, and how often does COAD meet?
- [ ] Does an Inland Northwest or Eastern Washington VOAD exist today, and does ARES/ACS take part?
- [ ] Is there any current Salvation Army or SATERN activity in Spokane involving ARES/ACS?
- [ ] How do ARES/ACS and the LDS ERC network coordinate (joint nets, shared GMRS/Winlink practice, recruitment)? Should the public site mention it?

#### Greater Spokane hospitals and health/medical partners (hospital radio net, SRHD, EMS, regional healthcare coalition) (`spokane-healthcare-and-hospitals`)
*government/served-agency* — Current hospital work (CONFIRMED):
- AEC Hospital Coordinator Mary Griffith K7MHG 'works with all Greater Spokane area hospitals to ensure they have radio and computer equipment in place and operational'.
- There is a Hospital-Coordination groups.io subgroup, and the Oct 6, 2026 calendar has a 'Hospital Net' (channel withheld: SCEM FOUO rule, `../decisions.md` D13).
- HIPAA training is required for the Hospital Team (Welcome Letter) and for all new ACS members (Task Book).
- County radios are placed at hospitals.

HISTORICAL (Plan Attachment 10):
- Operators were to back up the HEAR system at Deaconess, Sacred Heart, Holy Family, Valley, the VA and St. Luke's, all with ARES/RACES antennas.
- One operator was to go to the MedStar comm center atop Sacred Heart.
- Seven operators were to shadow MCI positions for Spokane County EMS.
- Sacred Heart was a site in the March 2019 exercise.

County plan: the 2021 CEMP ESF-8 names Deaconess the primary DMCC and Sacred Heart the secondary, SRHD the ESF-8 lead, and the EMS & Trauma Care Council the EMS lead, with ACS supporting. REDi is also listed.

LIKELY/INDIRECT: SRHD left the REDi coalition in March 2023, and WA DOH and NWHRN took over its functions. No direct SRHD, INHS or coalition agreement with ARES/ACS was found. INFERENCE: the hospital names in the Plan predate mergers.
- [ ] Which hospitals currently have ACS/ARES radio stations or antennas, and who are their emergency-management contacts?
- [ ] How often does the Hospital Net run? (Do **not** research or record its channel, frequency or talk-group: SCEM FOUO rule, `../decisions.md` D13.)
- [ ] Is there a written agreement with any hospital or healthcare coalition, and which coalition now covers Region 9?
- [ ] Does the MCI or EMS support role in the old Plan still exist? Does the MedStar/INHS comm center link still apply?
- [ ] Where do members take the required HIPAA training?

#### Other local and state government served agencies and exercise partners (SREC 911/Combined Communications Building, fire agencies, City of Spokane OEM, WSDOT, WA DNR Northeast, Fairchild AFB) (`other-government-served-agencies`)
*government/served-agency* — SREC 911 and the Combined Communications Building: the old Plan put the ARES/RACES operating position and the W7GBU-10 gateway at the CCB, and the CCB could page ARES leaders. The CCB was an exercise site in 2019. The 2021 CEMP lists SREC as an ESF-2 supporting agency with ACS. A Dec 2025 ILA starts a 2026-2028 transition to a separate City PSAP, which may affect the site.

Fire agencies (HISTORICAL, Plan Attachment 11): an informal operating plan dating from 1994, under Fire District 9 and DEM leadership, linked dispatch, command posts, staging and the Fire Resource Center. In the 2023 fires ARES/ACS supplied a mobile network unit.

WA DNR Northeast (Attachment 12): Spokane and Stevens ARES were to work jointly from Colville on project fires.

City of Spokane OEM: a separate office since about 2020, in the Mayor's Office (Director Sarah Nuss, LIKELY per the city site). The City is outside the county CEMP, yet many public-service events take place inside city limits.

WSDOT (CONFIRMED on the calendar): 'Spokane County ARES-ACS/WSDOT Exercise', Sept 26-27, 2026. The scope is unknown.

Fairchild AFB: a March 2019 exercise site; the 2001 Plan listed its hospital.
- [ ] Is there still an ARES/ACS operating position or gateway at the CCB/SREC building, and how does the 911 split affect it?
- [ ] What is the relationship between ACS and the City of Spokane OEM today? Is there a request path or MOU for city incidents?
- [ ] What were the scope and outcome of the Sept 2026 WSDOT exercise? Is there a standing arrangement?
- [ ] Are the fire-agency and DNR Northeast operating plans from the old Plan still valid, or should they be treated as historical?
- [ ] Does Fairchild AFB have any current relationship?

#### SCEM peer volunteer programs (CERT, Medical Reserve Corps, Search & Rescue, DART, MLST, HEART, SCOPE/SIRT) (`scem-peer-volunteer-programs`)
*partner-volunteer-org* — Peer programs under the same SCEM umbrella (CONFIRMED).
- The county Volunteer Emergency Worker Application lists ACS alongside SAR, MLST, HEART, DART, MRC and CERT. All share the Sheriff's background check and the emergency-worker framework.
- The CEMP lists ACS next to MRC and HEART in ESF-6 and ESF-8, and next to SAR in ESF-9.
- MLST can 'establish temporary mobile communication networks', which overlaps with ACS's MNUs and trailer; joint operations are INFERENCE.
- CERT is 'expanding in 2026 with volunteer leadership and county support'. ag7qp.com hosts Join Spokane County CERT pages, CERT PTBs and a Serve Washington CERT course notice. INFERENCE: ARES/ACS leaders are helping build CERT, and the ACS GMRS net may serve community comms.
- The 2014 ESF-6 lists SCOPE's SIRT with ARES/RACES. A 'S.C.O.P.E. ERC' table appeared beside the ARES-ACS trailer at the June 2026 IEVHFRA tailgate; its nature is unclear.
- MRC ran Bloomsday aid stations in 2016.
- [ ] How does SCEM present ACS among its volunteer programs, so the rebuilt navigation and wording can match?
- [ ] What role does ACS play in CERT communications (GMRS net, training)? Who leads Spokane CERT in 2026?
- [ ] Do ACS and MLST or SAR train or deploy together, for example with shared mobile comm assets?
- [ ] What is 'S.C.O.P.E. ERC', and is SIRT still active?

#### Kamiak Butte Amateur Repeater Association (KBARA) linked repeater system (`kbara`)
*repeater-org* — A Spokane-based association (PO Box 30801, Spokane 99223) that supports a linked multi-state VHF network (CONFIRMED).

System (kbara.org, Dec 2025):
- 147.38 N7WRQ (Mica Peak, tone 100).
- 147.36 N7WRR (Stensgar Mt).
- 147.02 (Lookout Pass), 147.28 (Walla Walla), 147.32 (Moscow), 223.90 and 444.35.
- Links: IRLP 9075, EchoLink KB7ARA-R, AllStar 53587.

Role for Spokane ARES:
- It carries the EWSEN VHF section net (Mondays 1815).
- EWA Standing Order 1b names it the third self-activation path.
- The Spokane Plan lists it as network resource #19, including 146.74 Colfax.

Other: KBARA co-sponsors the Spokane Hamfest (talk-in 147.38) and ran its own 2025 Field Day. It has been ARRL-affiliated since 1998, has 85 members and filed its last annual report 2026-09-11. LDS ERC nets also use it.
- [ ] What are the current system map, links and emergency-power status? Is 146.74 Colfax still part of it?
- [ ] Is there any formal agreement for emergency use of the system by EWA ARES or Spokane ACS?
- [ ] Who are KBARA's current contacts, and should the rebuilt site link kbara.org?

#### Neighboring county ARES/emcomm groups: EWA District 9 (and Walla Walla/Columbia) and North Idaho (Kootenai, Bonner, ARRL Idaho Section) (`neighboring-county-ares-groups`)
*neighboring-ares* — These are Spokane's mutual-aid and net peers. The Plan provides for mutual aid with adjoining Eastern Washington and North Idaho counties, as authorized by each county's EM and coordinated through Spokane DEM.

District 9 (EC names per the SEC's EmComm Groups page, 2026):
- Stevens is the closest operational neighbor. Its 146.62 K7JAR repeater links to the 147.06 Lookout Point machine, which is Spokane's 2nd alternate. Stevens sent traffic to Spokane in the March 2019 exercise. Spokane and Stevens were to support DNR Colville jointly.
- Lincoln: a member supported Spokane's 2017 SRD. W7OHI operates Davenport packet/Winlink nodes. lincares.org is stale (2011).
- Pend Oreille: the Plan's W7ORR 147.12 listing is STALE. Current nets use 146.44 simplex, 145.470 and 147.140.
- Ferry: EC/RO Sam Jenkins WA7EC is also the District 9 DEC.
- Adams and Asotin have ECs. Garfield is vacant, and Whitman has no organized group.
- Walla Walla and Columbia (EC Mikel Potts KB7POT) are grouped under District 8 by ag7qp.com, a DISCREPANCY. The wwares.org Winlink training link is dead.

North Idaho (ARRL Idaho Section, SM Dan Marler K7REX):
- Kootenai County ARES/RACES is hosted by the Kootenai County Sheriff OEM (KC7ODP, renewed 2025-12-02). It runs a Friday VHF net, 1st-Monday meetings and quarterly COMMEXes (2026-09-26 and 2026-11-14). DISCREPANCY: 147.080 or 147.280.
- KARS (K7ID) runs the VE sessions spokares.org lists, at Kootenai OEM in Hayden, and the Northwest Traffic Net.
- Bonner ARES links Spokane to a dead Wix URL (aresraces.wixsite.com/spokane).
- The EWSEN roll call includes 'any Idaho counties'.
- Latah appeared only in the 2018 exercise plan.
- [ ] Are any written mutual-aid agreements with neighboring counties in effect, or is it only the Plan clause?
- [ ] Confirm the 2026 ECs for each District 9 county and for Kootenai and Bonner.
- [ ] Does Spokane still coordinate District 9 activities (WSEN District 9 net, joint exercises)? Are there joint exercises with Kootenai's 2026 COMMEXes?
- [ ] Which neighbor repeaters and nets should the rebuilt site list, after correcting the Plan's stale entries (W7ORR, the Kootenai frequency)?
- [ ] Ask Bonner ARES, KARS and Stevens ARES to update their links to the rebuilt spokares.org.

#### Federal emergency-management and emergency-communications programs: FEMA (ICS/NIMS training, ICS forms, ARRL MOA) and CISA (SAFECOM FOGs, AUXCOMM/COMU, SHARES) (`federal-fema-cisa-emcomm-programs`)
*training-body* — FEMA (CONFIRMED):
- The Welcome Letter requires IS-100 and IS-700 before a county volunteer ID is issued and recommends IS-200 and IS-800. It cites old versions (.C/.B).
- The Operations > Training page links obsolete versions (IS-100.b, 700.a, 200.b, 800.b). Current versions are IS-100.c, 200.c, 700.b and 800.d.
- The ACS Task Book requires all four plus a FEMA SID. Its Leadership level adds IS-120, 230, 235, 240, 241, 242, 244 and 288.
- spokares.org links FEMA ICS forms and hosts an obsolete 1994 ICS orientation text.
- ARRL lists a 2023 FEMA MOA; the Spokane Plan cites the 1984 MOU.

CISA (CONFIRMED):
- The Operations menu 'Field Operations Guides' link (dhs.gov) now redirects to cisa.gov. Orientation recommends AUXFOG and NIFOG.
- The ACS Task Book lists AUXCOMM (arranged by the AHJ) and COMT for S-RADO, and COML and INCM for Leadership. It mislabels AUXCOMM as a FEMA course.
- The county Communications Specialist must obtain AUXCOMM and/or COML certification within a year of hire.
- Members took the DHS AUXCOMM class in Hayden, ID in June 2019.

SHARES: the ACS Task Book says 'Pass data over Government system (only those that have a SHARES call sign)'. There is a SHARES groups.io subgroup (9 members) and a SHARES Training Meeting at DEM on 2026-10-17. WA EMD runs 12 SHARES Winlink RMS stations. INFERENCE: some members or SCEM hold SHARES credentials.
- [ ] Which FEMA IS versions and links should the site list? Should the 1994 ICS text be replaced?
- [ ] What is the current CISA URL set for NIFOG, AUXFOG and eAUXFOG to replace the dhs.gov link?
- [ ] How many members hold AUXC, COMT or COML credentials? When is the next AUXCOMM course (via the WA SWIC or a neighbor state)?
- [ ] What is the SHARES station setup (SCEM or member stations), and what can be said publicly without exposing restricted SHARES information?
- [ ] Should the rebuilt site cite the ARRL-FEMA 2023 MOA in place of the 1984 MOU?

#### ARRL National Traffic System (NTS 2.0) and regional traffic nets (WARTS, Washington State Net, NTS Region 7, Western/Pacific Area, Radio Relay International) (`nts-and-traffic-nets`)
*net* — How the group relates to NTS (CONFIRMED):
- The Spokane EC's duties on spokares.org include a welfare-traffic plan 'utilizing the National Traffic System'.
- The Plan defines an NTS Liaison Station and requires ARRL radiogram forms.
- The 2026 SET asks for an NTS radiogram to the SM and SEC.

Nets named in the Plan as 'ARRL National Traffic System Liaison' nets:
- WARTS: 3970 kHz daily 1800. DISCREPANCY: wartsnet.net and search results give 3975 kHz (and 1730). Net Manager: Rick W7RNB.
- WSN: 3563 kHz CW. Its page is on K7BFL's felge.us (2015; net manager N7YRT, possibly STALE).

The path above: Region 7 (DRN7, 3925/7235, affiliated with both NTS and RRI) and the Western Area/PAN.
- LIKELY per nts2.arrl.org: elected Area Staff Chairs were restored in 2025 and the Pacific Area was renamed Western Area.
- Other listed nets: the Northwest Traffic Net (KARS 146.98; NTS status SPECULATIVE) and the Noontime Net (posted 2017).

RRI signed an MOU with ARRL in July 2025 and co-developed 'I Am Safe', which the EWA SEC points to. HAZARD: K7BFL's page links radio-relay.org, which is now a hijacked gambling domain; the real site is radiorelay.org.
- [ ] Does Spokane currently have an NTS liaison station or traffic handlers? Who are they?
- [ ] What are WARTS's current frequency and time (3970 or 3975; 1800 or 1730)? Is the WSN manager current?
- [ ] Is the EWA Section Traffic Manager post vacant, and who receives PSHR reports?
- [ ] Has Spokane adopted RRI's 'I Am Safe' for welfare messaging?
- [ ] Which traffic-handling links (felge.us tfctools, ARRL NTS, RRI) should carry over, with the hijacked radio-relay.org link removed?

#### Winlink (ARSFI) and the Eastern Washington/North Idaho digital network (felge.us K7BFL resources, packet nodes, HamWAN/AREDN) (`winlink-and-regional-digital-network`)
*platform/tool* — Winlink is the group's primary digital mode (CONFIRMED):
- The W7GBU-10 gateway and Winlink coordinator NV2Z.
- Twice-monthly Winlink assignments to NV2Z and AG7QP (ICS-213, ICS-213RR, DYFI).
- Winlink required at the ACS S-RADO level.
- The IEVHFRA MOU's packet-to-Winlink example, a Winlink groups.io subgroup (20 members) and 2019 'Scripting in Winlink' and 'Ardop 101' presentations.
- ARSFI is the 501(c)(3) that runs Winlink.

felge.us (CONFIRMED): Spokane's Don Felgenhauer K7BFL runs it, and the spokares.org menus link it ('Winlink Info by K7BFL', 'Traffic Handling Tools'). It hosts:
- The EWA ARES/RACES Digital SOP (2011).
- EWA/North Idaho packet-node and gateway maps (with AL1Q, July 2022) and the gateway table (March 2016, stale).
- Traffic tools (revised Nov 2023) and the WSN/PAN/IMN net pages.
- The site is HTTP-only.

The July 2022 diagram shows gateways and nodes at AD7DD-10, NV2Z-10 (VARA FM), K7BFL-10/-11, K7SRG-8, W7OHI, K7TJ-9 (EWARG) and W7BFI-7. It also notes gaps between some nodes.

Newer projects:
- The HamWan groups.io subgroup (8 members) is 'standing up and maintaining the HamWan system for Spokane County ARES - ACS'. Its status is unknown.
- AREDN was presented in 2019 ('Intro to AREDN for Spokane County ACS'); no deployment was found.

The EWA SEC also lists national Winlink practice nets (ETO, GLAWN, Winlink Wednesday).
- [ ] What is the current list of Spokane-area Winlink gateways and packet nodes (callsign, frequency, mode, site)? Is W7GBU-10 on 145.01 or 145.090?
- [ ] Is the 2011 EWA Digital SOP still in force, or is a revision planned?
- [ ] Will K7BFL keep felge.us online? Can its content be mirrored or linked from the rebuilt HTTPS site?
- [ ] What are the HamWAN project's status, sites and affiliation (the Puget Sound HamWAN model, or an EWARG/SRECS Beacon Hill link)? Was AREDN dropped?
- [ ] Where should the site point members for Winlink templates (WA Field Situation Report, ICS-213RR) and practice nets?

#### Spokane-area ham clubs, repeater owners, frequency coordinator, hamfest and VE testing teams (`spokane-area-clubs-repeaters-ve`)
*local-ham-club* — The wider local amateur ecosystem. None of these has a formal ARES agreement (only IEVHFRA does).

Repeater resources listed in the Plan:
- SRG 147.20 wide-area. SRG is on spokares.org's links page and links back. Trustee AK2O is also an IEVHFRA director.
- NW Tri-State 147.24. The Plan's W7HCJ reference is STALE; the call is now WA7HWD.
- The 'Agilent Spokane ARC' 145.21 and 443.475, now under W7TRF (INFERENCE: same repeaters).
- 147.06 N7BFS, the 2nd alternate, linked to Stevens 146.62.
- EWARG's KC7AAD-4 packet node. EWARG is a digital club, possibly tied to HamWAN (INFERENCE).

Testing:
- The spokares.org test-sessions page (2023) and ag7qp.com name WA7DRE as the Laurel VEC team: 3rd Wed, 6 PM, Action Recycling.
- A 2019 IEVHFRA newsletter mentions W5YI-VEC sessions.
- An ARRL VEC team is associated with KARS and with AA7RT historically.

Other clubs and events:
- SDXA links through people (Mel Ming N7GCO, Lincoln EC AD7XG) and co-exhibited at the 2026 tailgate.
- The Spokane Hamfest / ARRL WA State Convention is club-run (KBARA, SDXA, IEVHFRA, WA7DRE, Palouse Hills ARC, NW Tri-State). The last confirmed date was 2025-04-26 at Horizon MS; spokanehamfest.org failed DNS on 2026-09-25. ARES is not a sponsor.
- The Spokane Dial Twisters appear only in the W7GBU history (probably defunct).

IACC (CONFIRMED) coordinates EWA and North Idaho repeaters. It is linked from arrl.org's EWA page and holds records 1359 and 1253 for W7GBU.
- [ ] Which repeaters should the rebuilt site list as current ARES/ACS alternates? Correct the Plan's stale entries (W7HCJ, Agilent, WA6HSL, W7ORR) and confirm 147.20's private status.
- [ ] What are the current VE session schedules and contacts (WA7DRE/Laurel, KARS, sessions at Tiley classes now run by AG7QP)?
- [ ] When is the next Spokane Hamfest, and does ARES/ACS exhibit or recruit there?
- [ ] Are IACC coordinations current for W7GBU 147.30 and 443.40, and who is the contact?
- [ ] Does EWARG's network or the SRG sites play a role in the Spokane HamWAN build-out?

#### Public-service events and recurring non-ARRL exercises (Bloomsday, Lilac Torchlight Parade, Climb for the Cure, Valleyfest, Winter Field Day, Great ShakeOut/USGS DYFI, Communications Academy, RATPAC) (`public-service-events-and-exercises`)
*public-service-event* — Public-service events:
- Bloomsday and the Armed Forces/Lilac Torchlight Parade are the group's signature events (CONFIRMED). AEC Public Events NV2Z coordinates them. The Plan calls them primary training for working with fire and law enforcement, and a Torchlight Parade briefing was among the old downloads. Bloomsday 2027 is May 2 (volunteers go to NV2Z, per ag7qp.com).
- Climb for the Cure, 2027-06-13: contact NZ2S; IEVHFRA is a sponsor.
- Valleyfest appeared on the 2019 calendar.

Recurring exercises and training outside ARRL:
- Winter Field Day: a groups.io subgroup; 2016 at Five Mile Prairie; next 2027-01-23/24.
- Great ShakeOut (Oct 15, 2026): send a DYFI Winlink message. EWA SOP 1b also calls for DYFI reports.
- Communications Academy: four members attended in 2018.
- RATPAC: its video list is linked from the Downloads menu.

No formal agreements with event organizers were found.
- [ ] Who are the current contacts at the Lilac Bloomsday Association and the Spokane Lilac Festival? Are there written agreements, waivers or briefing packets?
- [ ] How do volunteers sign up for events, and should the rebuilt site carry a public events calendar or a signup link?
- [ ] What photos and after-action reports exist for these events, and what permissions apply?
- [ ] Is Winter Field Day 2027 planned, and where?
- [ ] Who maintains the RATPAC sheet link, and should it stay?

#### Legal and regulatory framework: FCC Part 97 (incl. 47 CFR 97.407 RACES), RCW 38.52 and the WAC 118-04 emergency-worker program (`legal-regulatory-framework`)
*legal-regulatory* — FCC (CONFIRMED): The FCC licenses every member and the W7GBU club station. The Plan cites Part 97 Subpart E as its first authority and operates under ARES rules unless a national communications emergency requires RACES-only operation. 47 CFR 97.407 (last amended 2010) requires that RACES stations and operators be certified by a civil defense organization, restricts RACES traffic and limits drills (1 hr/week, or 72 hrs twice a year with approval). The Ham Radio Links ULS link is outdated.

RCW 38.52 (CONFIRMED):
- .010 defines an 'emergency worker' as registered with a local EM organization and holding its ID card.
- .070 authorizes local and joint EM organizations, which is the basis for SCEM's ILA.
- .180 covers liability; the county application cites it for vehicle use.
- .310 covers registration and coverage.
- The Plan's mission-number rule exists to secure RCW 38.52 coverage.

WAC 118-04 (CONFIRMED): registration is a 'prerequisite for eligibility ... for benefits and legal protection'. Workers register on EMD-024 or an equivalent; the county Volunteer Application is that equivalent. There is a 'Communications' class of emergency worker, and training missions are covered. The chapter was amended effective 2025-12-27, so site copy must cite current text.

L&I: the county Volunteer Policies page references L&I coverage. The Welcome Letter's L&I bloodborne-pathogens link is dead.

usraces.org is a private organization, not an authority.
- [ ] How should the site accurately describe volunteer legal status (registered emergency worker, mission-number coverage, L&I coverage) under current WAC 118-04 and RCW 38.52 text?
- [ ] Does ACS operate under 47 CFR 97.407 RACES rules in practice, and has SCEM certified members as RACES operators? How should the 'RACES' label be used?
- [ ] What is the replacement for the dead L&I bloodborne-pathogens course link, and is that training still recommended?
- [ ] What current FCC ULS/license-lookup URL should replace the outdated wireless.fcc.gov link?

### Peripheral

#### Third-party resources linked from spokares.org (digital-mode hardware/software vendors, RFI references, widgets, general ham references) (`spokares-external-resource-links`)
*platform/tool* — This umbrella covers link targets only. There is no organizational relationship.

CONFIRMED link targets:
- The Digital Messaging menus (Software, Cables & Drivers, TNCs & Modems, Packet Radio Resources) link vendors and tools. The group's own how-to covers UZ7HO SoundModem with a SignaLink.
- The RFI Resources page (2021-02-20) by Mel Ming N7GCO of Cheney, an SDXA presenter, cites K9YC, Palomar, KF7P, DX Engineering/Fair-Rite, NK7Z, ON4WW and the ARRL RFI Book. It also links his Dropbox RFI video.
- Every page embeds the HamQSL solar widget and an OpenWeatherMap weather module.
- Ham Radio Links points to AC6V, QRZ, Handiham, usraces.org and similar references.

Known link problems (CONFIRMED):
- Dead or broken: Prolific 404, Farallon 404, the Tera Term osdn URL, and the Dropbox URL (redirects).
- Bot-gated: K4ABT (ohiopacket.org).
- Moved: the FTDI link.

HAZARDS: putty.org is NOT the official PuTTY site, so keep the chiark URL. jcalpro.com now belongs to an unrelated company.
- [ ] Run a full link check of every outbound link on spokares.org. Which links should the rebuild keep, replace (Tera Term GitHub, FTDI new URL, W1HKJ .org) or drop?
- [ ] Does Mel Ming N7GCO permit rehosting his RFI page and video?
- [ ] Should the weather widget be replaced with NWS Spokane sources? What are HamQSL's widget attribution terms?
- [ ] Which vendor links are endorsements the group wants to keep, and which would be better replaced with neutral guidance?

