/*!
 * Spokane County ARES-ACS: round-2 mockup data (window.SPOKARES)
 * =============================================================================
 * THE SINGLE SOURCE for every "this week" card, next-net line, net-control
 * rota, meeting list, exercise/event list, Winlink assignment and document-
 * library widget in all three round-2 design options. Pages read from
 * window.SPOKARES and must not hard-code these values (except as a no-JS
 * fallback that matches this file as of meta.asOf).
 *
 * In production this file is REPLACED BY CMS DATA: the calendar feed (groups.io
 * ICS if public, else a role-owned Google Calendar; research/decisions.md D9)
 * and a document post type or data file (D10). Keep the shapes; swap the source.
 *
 * Loading: a plain <script src="…/data.js"></script>. No fetch(), so pages work
 * from file://. No dependencies. ES5 syntax on purpose.
 *
 * Conventions
 *   - Dates are ISO "YYYY-MM-DD", local (America/Los_Angeles). Times are 24 h
 *     "HH:MM" local. Show 12 h times to the public ("8:00 PM").
 *   - verify: true  => render the element with data-verify="true". Never print
 *     "verify"/"TBD" chips. The footer carries "Mockup — content to be verified
 *     before launch".
 *   - People: role + callsign only. Personal names appear ONLY in `rota`, exactly
 *     as published on ag7qp.com. No phone numbers, no personal e-mails.
 *   - src: where the fact comes from (paths relative to research/).
 *
 * Deliberately NOT in this file (do not add): the 1st-Tuesday staff meeting
 * (D13 #16), the Hospital Net or its channel (SCEM FOUO; facts.yaml
 * net.hospital), any simplex frequency (D13 #5), the W7GBU-10 frequency (D13 #6),
 * county/hospital/SHARES/800 MHz channels, the net roster, member counts, and
 * any "current alert level".
 *
 * Helpers (SPOKARES.fn): today(), parse(iso), iso(date), fmtDate(iso, style),
 * fmtTime("20:00"), nextNet(fromIso), upcoming({from, days, limit, types}),
 * nextMeetings(fromIso, n), documents({q, category, status}), docById(id).
 * =============================================================================
 */
(function (root) {
  'use strict';

  var S = {};

  /* ------------------------------------------------------------------ meta */
  S.meta = {
    asOf: '2026-09-26',               // mockup "today" (a Saturday; day 1 of the WSDOT exercise)
    timezone: 'America/Los_Angeles',
    version: 'round2-shared-2026-09-26',
    footerNote: 'Mockup — content to be verified before launch',
    note: 'Replace with CMS data before launch (decisions.md D9, D10).'
  };

  /* ------------------------------------------------------------------- org */
  S.org = {
    name: 'Spokane County ARES-ACS',
    legacyName: 'Spokane County ARES/RACES',
    ares: 'Amateur Radio Emergency Service',
    acs: 'Auxiliary Communications',          // the seal's wording; final expansion is decisions.md D6
    acsVerify: true,
    races: 'Radio Amateur Civil Emergency Service',
    callsign: 'W7GBU',
    clubLicensee: 'Spokane County ARES RACES Support Group',
    trusteeCallsign: 'K7DSR',
    servedAgency: 'Spokane County Emergency Management (SCEM)',
    affiliation: 'An ARRL Amateur Radio Emergency Service (ARES) group',
    mission: 'The mission of Spokane County ARES/RACES is to support and enhance the telecommunications needs of its served agencies with the versatile talents and flexible resources of trained and competent amateur radio operators, thereby serving the public interest in times of emergency or special need.',
    missionSrc: 'content/live/02-ares-races-plan.md (unchanged since 2004)',
    short: 'Our group concentrates on emergency and civil communications to support the safety of Spokane County residents.',
    shortSrc: 'content/live/04-about-spokane-county-ares-acs.md',
    location: {
      name: 'Spokane County Emergency Management',
      aka: 'the DEM building',
      street: '1121 W Gardner Ave',
      city: 'Spokane', state: 'WA', zip: '99260',
      landmark: 'Next to the Spokane County Courthouse, near N Jefferson St and W Gardner Ave',
      firstVisit: null,                      // entrance, escort, parking, access: unknown (D13 #19)
      firstVisitVerify: true,
      mapImage: 'img/DEM_map.png',           // mockup stand-in only (Google Maps screenshot)
      src: 'facts.yaml meeting.location; design/assets/DEM_map.png'
    }
  };

  /* ----------------------------------------------------------------- radio */
  // Amateur facts only (decisions.md D13 "What stays public").
  S.radio = {
    primary:   { id: 'w7gbu-2m', call: 'W7GBU', freq: '147.300', unit: 'MHz', offset: '+600 kHz', tone: '100 Hz', site: 'Brownes Mountain', emergencyPower: true, emergencyPowerVerify: true, label: 'the Spokane County ACS Repeater', verify: false, src: 'facts.yaml repeater.primary; docs/net-preamble-2026-03-04.md' },
    uhf:       { id: 'w7gbu-70cm', call: 'W7GBU', freq: '443.400', unit: 'MHz', offset: '+', tone: null, note: 'Whether it is in regular ARES-ACS use is not confirmed.', verify: true, src: 'facts.yaml repeater.uhf' },
    alternate: { id: 'ievhfra', call: null, owner: 'Inland Empire VHF Radio Amateurs (IEVHFRA)', freq: '146.880', unit: 'MHz', offset: '-600 kHz', tone: '123 Hz', note: 'Named as the alternate in the 2023 IEVHFRA MOU and the 2026 net scripts.', verify: true, src: 'facts.yaml repeater.alternate' },
    nws:       { id: 'nws', call: 'WXL86', freq: '162.400', unit: 'MHz', label: 'NOAA Weather Radio, Spokane', src: 'content/live/20-ares-alert-levels-status-codes.md' },
    sectionHf: { id: 'ewsen-hf', freq: '3.985', unit: 'MHz', mode: 'LSB', label: 'WSEN / EWSEN HF (secondary 3.990 and 3.995)', src: 'interim-ag7qp/eastern-washington-section.md' },
    copyText: 'W7GBU 147.300 MHz, +600 kHz offset, 100 Hz tone'
  };

  /* ------------------------------------------------------------------ nets */
  // rule: { freq: 'weekly'|'monthly'|'weekdays', weekday: 0=Sun…6=Sat, nth: n or [n, n] }
  S.nets = [
    {
      id: 'weekly', name: 'Spokane County ARES-ACS Net', short: 'Tuesday net',
      rule: { freq: 'weekly', weekday: 2 }, ruleText: 'Every Tuesday',
      start: '20:00', preNet: '19:55',
      where: 'W7GBU, 147.300 MHz, +600 kHz, 100 Hz tone', radio: 'primary', alternate: 'alternate',
      kind: 'Directed net that doubles as weekly emergency-net training',
      visitors: 'Visitors are invited to check in after members: call sign (slowly, phonetically), name and location.',
      checkInOrder: ['Officials (EC, county Emergency Management staff, AECs, ARRL District and Section officials, officials from surrounding counties)', 'Members', 'Visitors', 'Simplex, no-tone and cross-band stations, then relays', 'Stations with listed traffic', 'Late or missed check-ins'],
      verify: false,
      src: 'facts.yaml net.weekly, net.checkin_order; docs/net-preamble-2026-03-04.md'
    },
    {
      id: 'winlink-nights', name: 'Winlink night', short: 'Winlink night',
      rule: { freq: 'monthly', weekday: 2, nth: [2, 4] }, ruleText: '2nd and 4th Tuesdays (during the weekly net)',
      start: '20:00',
      note: 'Have paper and pencil ready. Net control hands over to the Winlink lead for the assignment.',
      modifiesNet: 'weekly', verify: true,
      src: 'facts.yaml net.winlink_nights; docs/net-preamble-2026-03-04.md'
    },
    {
      id: 'simplex-5th', name: 'Fifth-Tuesday simplex net', short: 'Simplex net',
      rule: { freq: 'monthly', weekday: 2, nth: 5 }, ruleText: 'In months with a 5th Tuesday',
      start: '20:00',
      where: 'Starts on the ARES-ACS simplex frequency, then moves to W7GBU 147.300 for announcements, traffic and anyone who missed simplex.',
      frequency: null,                       // never publish until the Net Manager confirms (D13 #5)
      note: 'Have paper and pencil ready: each station reads back the stations it heard.',
      modifiesNet: 'weekly', verify: true,
      src: 'facts.yaml net.fifth_tuesday_simplex; docs/net-preamble-simplex-2026-03-04.md'
    },
    {
      id: 'gmrs', name: 'Spokane County ACS GMRS Net', short: 'GMRS net',
      rule: { freq: 'monthly', weekday: 2, nth: 3 }, ruleText: '3rd Tuesday',
      start: '19:30',
      where: 'WRCY281 GMRS repeater (GMRS channel 7, 462.700 MHz, +5 MHz, 141.3 Hz)',
      note: 'A GMRS license is needed to transmit. Listing the repeater publicly needs its owner’s OK.',
      verify: true,
      src: 'facts.yaml gmrs.net; docs/net-preamble-gmrs-2025-08-19.md'
    },
    {
      id: 'bunch-0730', name: '0730 Bunch', short: 'Morning round table',
      rule: { freq: 'weekdays' }, ruleText: 'Monday to Friday',
      start: '07:30',
      where: 'W7GBU, 147.300 MHz',
      kind: 'Informal morning round table started by Dean Bula, W7GBU ("the 7:30 Bunch")',
      verify: true,
      src: 'facts.yaml net.morning_roundtable; content/live/05-w7gbu-call-sign.md'
    },
    {
      id: 'ewsen', name: 'Eastern Washington Section Emergency Net (EWSEN)', short: 'Section nets (Mondays)',
      rule: { freq: 'weekly', weekday: 1 }, ruleText: 'Mondays',
      start: '18:15',
      where: 'VHF 1815 on the KBARA linked repeaters; HF 1845 on 3.985 MHz; Winlink check-in to EWA-WL2K, 1700-1900',
      scope: 'section', href: 'https://ag7qp.com/eastern-washington-section',
      verify: true,
      src: 'interim-ag7qp/eastern-washington-section.md'
    }
  ];

  /* ------------------------------------------------------------------ rota */
  // Net control for the Tuesday net, as published on ag7qp.com (seen 2026-09-25).
  // callsign null = open slot. Names are shown only here, exactly as published.
  S.rotaMeta = {
    reminder: 'Members should check in at least once each month.',
    openSlotAction: 'Open slot: tell the Net Manager (AG7QP) on the net or through groups.io if you can take it.',
    src: 'interim-ag7qp/spokane-county-ares-acs.md; data/calendar-events.json (Sep 2026)'
  };
  S.rota = [
    { date: '2026-09-22', callsign: 'KE7RAP', name: 'Bob Peterson' },
    { date: '2026-09-29', callsign: 'NZ2S', name: 'Jeff Banke', mode: 'simplex', modeVerify: true, note: '5th Tuesday. The old calendar lists this date as "NZ2S - Simplex"; the ag7qp rota lists plain "NZ2S".' },
    { date: '2026-10-06', callsign: null, name: null, open: true },
    { date: '2026-10-13', callsign: 'AE7RJ', name: 'Randall Jones', mode: 'winlink' },
    { date: '2026-10-20', callsign: 'WA7LNC', name: 'Gordon Grove' },
    { date: '2026-10-27', callsign: null, name: null, open: true, mode: 'winlink' }
  ];

  /* --------------------------------------------------------------- winlink */
  S.winlink = {
    howTo: 'Answer on an ICS-213 unless another form is named. Send to NV2Z and AG7QP between 1700 and 2100 on the date shown. Radio is preferred; Telnet is acceptable.',
    lead: { role: 'AEC, Public Events; W7GBU-10 gateway manager; Winlink coordinator', callsign: 'NV2Z' },
    src: 'interim-ag7qp/spokane-county-ares-acs.md',
    assignments: [
      { date: '2026-09-22', form: 'Quick Message', task: 'Identify steps, actions and precautions you should take in extreme cold.' },
      { date: '2026-10-13', form: 'Did You Feel It (DYFI) report', task: 'Submit a Did You Feel It report. Be sure to mark it as an exercise.' },
      { date: '2026-10-27', form: 'Welfare Message / Quick Health & Welfare', task: 'Submit a welfare message.' },
      { date: '2026-11-10', form: 'ICS-213', task: 'Report where you expect to have Thanksgiving dinner.' },
      { date: '2026-11-24', form: 'ICS-213RR', task: 'Request what you want Santa to bring you.' },
      { date: '2026-12-08', form: 'ICS-213', task: 'Give Santa a justification for your being good this year.' },
      { date: '2026-12-22', form: 'ICS-213', task: 'What are your New Year’s Eve plans?' }
    ]
  };

  /* --------------------------------------------------------------- meetings */
  // All at SCEM, 1121 W Gardner Ave. `next` is precomputed from 2026-09-26 for
  // static fallbacks; the helpers recompute from the rule.
  S.meetings = [
    {
      id: 'workshop', name: 'Second Saturday Workshop', short: 'Workshop',
      rule: { freq: 'monthly', weekday: 6, nth: 2 }, ruleText: '2nd Saturday',
      start: '09:00', end: '12:00', where: 'SCEM, 1121 W Gardner Ave',
      audience: 'Members and visitors', summary: 'Hands-on skills: radios, digital modes, power, mobile installs and more.',
      next: ['2026-10-10', '2026-11-14', '2026-12-12'],
      verify: false, src: 'facts.yaml meeting.workshop'
    },
    {
      id: 'winlink-workshop', name: 'Winlink workshop', short: 'Winlink workshop',
      rule: { freq: 'monthly', weekday: 6, nth: 2 }, ruleText: '2nd Saturday, after the general workshop',
      start: '12:30', end: '15:30', where: 'SCEM, 1121 W Gardner Ave',
      audience: 'Open to everyone', summary: 'Help getting Winlink (email over radio) set up and working.',
      next: ['2026-10-10', '2026-11-14', '2026-12-12'],
      verify: true, src: 'facts.yaml meeting.winlink_workshop; interim-ag7qp/winlink.md'
    },
    {
      id: 'third-thursday', name: 'Third Thursday training meeting', short: 'Training meeting',
      rule: { freq: 'monthly', weekday: 4, nth: 3 }, ruleText: '3rd Thursday',
      start: null, end: null, displayTime: 'Evening',   // 6 PM (ag7qp 2026) vs 7 PM (older sources): D13 #4
      where: 'SCEM, 1121 W Gardner Ave',
      audience: 'Members and visitors', summary: 'The monthly training meeting.',
      skipMonths: [12], skipNote: 'No December meeting was held in 2019; confirm whether that still applies (D13 #10).',
      next: ['2026-10-15', '2026-11-19', '2027-01-21'],
      verify: true, src: 'facts.yaml meeting.third_thursday, meeting.december'
    },
    {
      id: 'orientation', name: 'New Member Orientation', short: 'Orientation',
      rule: null, ruleText: 'Usually the 4th Tuesday',
      start: '18:00', where: 'SCEM, 1121 W Gardner Ave',
      display: 'Held regularly. Ask us for the next date.',
      summary: 'Why, what and how: the mission and structure, qualifying, HIPAA, then a tour of the county Comm Room and the 8 PM net.',
      next: [],                                 // not confirmed as a standing session; no Oct 27 session listed (D13 #17)
      verify: true, src: 'facts.yaml meeting.orientation; interim-ag7qp/new-members-orientation.md'
    }
  ];

  /* ----------------------------------------------------------------- events */
  // Non-recurring exercises, training and events. type: exercise | training |
  // public-service | on-air | section. scope: 'local' (ours) | 'section' | 'national'.
  S.events = [
    {
      id: 'wsdot-2026', title: 'Spokane County ARES-ACS / WSDOT exercise', type: 'exercise', scope: 'local',
      start: '2026-09-26', end: '2026-09-27', allDay: true,
      summary: 'A joint exercise with the Washington State Department of Transportation during National Preparedness Month.',
      detailVerify: true, verify: false,
      src: 'interim-ag7qp/spokane-county-ares-acs.md; orgs/relationships.md §15 (scope unknown)'
    },
    {
      id: 'set-2026', title: 'Simulated Emergency Test (SET)', type: 'exercise', scope: 'national',
      start: '2026-10-03', allDay: true,
      summary: 'ARRL’s annual national exercise. The Eastern Washington Section scenario and tasks come from the Section Emergency Coordinator.',
      tasks: [
        'Use Winlink (radio preferred, Telnet acceptable) to send a WA State Field Situation Report to your EC and to the Section Emergency Coordinator (AG7QP).',
        'Send a message through the National Traffic System to the Section Manager (KA7LJQ) and the Section Emergency Coordinator (AG7QP).',
        'Take pictures of your station and operation to share.',
        'Make as many contacts as you can using the rules of POTA and/or SOTA.',
        'Send an after-action report (AAR) with a list of participants to the Section Emergency Coordinator.'
      ],
      links: [
        { label: 'ARRL SET forms', href: 'https://www.arrl.org/simulated-emergency-test' },
        { label: 'Section SET instructions (ag7qp.com)', href: 'https://ag7qp.com/' }
      ],
      verify: false, src: 'interim-ag7qp/home.md; interim-ag7qp/spokane-county-ares-acs.md'
    },
    {
      id: 'shakeout-2026', title: 'Great ShakeOut: send a DYFI message', type: 'exercise', scope: 'national',
      start: '2026-10-15', time: '10:15',
      summary: 'Send a USGS “Did You Feel It?” (DYFI) report by Winlink at 10:15 AM.',
      links: [{ label: 'Great ShakeOut', href: 'https://www.shakeout.org/' }, { label: 'USGS Did You Feel It?', href: 'https://earthquake.usgs.gov/data/dyfi/' }],
      verify: false, src: 'interim-ag7qp/spokane-county-ares-acs.md; interim-ag7qp/home.md'
    },
    {
      id: 'shares-training-2026-10', title: 'SHARES training meeting', type: 'training', scope: 'local',
      start: '2026-10-17', time: '09:00', endTime: '12:00', where: 'SCEM, 1121 W Gardner Ave',
      summary: 'Training for SHARES participants. Details are shared with participants, not on this site.',
      verify: true, src: 'interim-ag7qp/spokane-county-ares-acs.md'
    },
    {
      id: 'commex-2026', title: 'State COMMEX', type: 'exercise', scope: 'state',
      start: '2026-10-24', allDay: true,
      summary: 'Washington’s statewide communications exercise.',
      note: 'May be the same event as the “Catastrophic Communications Exercise 2026” listed on ag7qp.com without a date.',
      verify: true, src: 'interim-ag7qp/spokane-county-ares-acs.md; interim-ag7qp/README.md stale item 7'
    },
    {
      id: 'w1aw7-2026', title: 'Washington operates as W1AW/7 (ARRL WAS-250)', type: 'on-air', scope: 'section', optional: true,
      start: '2026-11-18', end: '2026-11-25', allDay: true,
      summary: 'Optional. Washington operators sign up for shifts as W1AW/7 during the ARRL WAS-250 event.',
      links: [{ label: 'ARRL WAS-250', href: 'https://www.arrl.org/america250-was' }],
      verify: true, src: 'interim-ag7qp/home.md'
    },
    {
      id: 'srd-2026', title: 'SKYWARN Recognition Day', type: 'on-air', scope: 'national',
      start: '2026-12-04', end: '2026-12-05', allDay: true,
      where: 'NWS Spokane office, Airway Heights',
      summary: 'The group has run a station at the National Weather Service office in Spokane for SKYWARN Recognition Day every year since 1999.',
      links: [{ label: 'SKYWARN Recognition Day', href: 'https://www.weather.gov/crh/skywarnrecognition' }],
      verify: true, src: 'interim-ag7qp/home.md; content/live/37-*.md; history.md §5'
    },
    {
      id: 'wfd-2027', title: 'Winter Field Day', type: 'on-air', scope: 'national',
      start: '2027-01-23', end: '2027-01-24', allDay: true,
      summary: 'A winter operating event that tests portable and emergency-power stations. The group has a WinterFieldDay subgroup on groups.io.',
      links: [{ label: 'Winter Field Day', href: 'https://winterfieldday.org/' }],
      verify: false, src: 'interim-ag7qp/home.md; organization.md §12'
    },
    {
      id: 'bloomsday-2027', title: 'Bloomsday', type: 'public-service', scope: 'local',
      start: '2027-05-02', allDay: true,
      summary: 'One of the group’s signature public-service events: primary or backup communications for the race.',
      contact: { role: 'AEC, Public Events', callsign: 'NV2Z' },
      verify: false, src: 'interim-ag7qp/home.md; content/live/03-ares-acs-staff.md'
    },
    {
      id: 'climb-2027', title: 'Climb for the Cure', type: 'public-service', scope: 'local',
      start: '2027-06-13', allDay: true,
      summary: 'Public-service communications for the event.',
      contact: { role: 'Volunteer contact', callsign: 'NZ2S' },
      verify: true, src: 'interim-ag7qp/home.md; orgs/relationships.md §23'
    }
  ];

  // Recurring program (no fixed dates here; for "year at a glance").
  S.yearAtAGlance = [
    { month: 'January', items: ['Winter Field Day'] },
    { month: 'May', items: ['Bloomsday', 'Lilac Festival Armed Forces Torchlight Parade'], verify: true },
    { month: 'June', items: ['ARRL Field Day'], verify: true },
    { month: 'September', items: ['National Preparedness Month'] },
    { month: 'October', items: ['Simulated Emergency Test (first weekend)', 'Great ShakeOut “Did You Feel It?” message', 'State COMMEX'] },
    { month: 'December', items: ['SKYWARN Recognition Day station at NWS Spokane'] },
    { month: 'Every month', items: ['Tuesday net (Winlink nights on the 2nd and 4th Tuesdays)', 'Second Saturday Workshop and Winlink workshop', 'Third Thursday training meeting', 'GMRS net (3rd Tuesday)'] },
    { month: 'Months with a 5th Tuesday', items: ['Simplex net'] },
    { month: 'Months with a 5th Saturday', items: ['EOC-to-EOC exercises have been held on 5th Saturdays (2004, 2009, 2013, 2018, 2019)'], verify: true }
  ];

  // Past exercises and operations worth knowing (history.md §2, §5).
  S.pastExercises = [
    { date: '2025', title: 'Field Day in Spokane Valley as W7GBU', verify: true, src: 'orgs/relationships.md §3' },
    { date: '2023-08', title: 'Gray and Oregon Road fires (real incident)', summary: 'The group provided a mobile network unit for the incident command post and the Disaster Assistance Center.', verify: true, src: 'sources/government/spokane-COAD-minutes-2023-09-05.txt' },
    { date: '2022-10-29', title: 'Rockford exercise', src: 'data/calendar-events.json' },
    { date: '2021-10-30', title: 'Simulated Emergency Test', src: 'data/calendar-events.json' },
    { date: '2019-12-07', title: '“Hazard City” hospital deployment exercise', verify: true, src: 'history.md §2.4' },
    { date: '2019-10-05', title: 'Quarterly all-members exercise', src: 'history.md §2.4' },
    { date: '2019-07-17', title: 'Kootenai County exercise', src: 'data/calendar-events.json' },
    { date: '2019-03-30', title: 'EOC-to-EOC 5th Saturday exercise', summary: 'More than 20 operators at sites across the county; traffic with Stevens County; Winlink over HF and VHF packet; WA ICS-213RR resource requests to the State EOC at Camp Murray.', src: 'history.md §2.4' },
    { date: '2018-06-23', title: 'Field Day 2018', src: 'data/calendar-events.json' },
    { date: '2018-03-31', title: '5th Saturday multi-county exercise', summary: 'Stations at outlying city halls with portable HF, VHF and UHF, Winlink, and the secondary repeater.', src: 'history.md §2.4' },
    { date: '2017-12-02', title: 'SKYWARN Recognition Day station at NWS Spokane', summary: 'New members operated HF.', src: 'history.md §2.4' },
    { date: '2013-08-31', title: 'EOC-to-EOC exercise', src: 'history.md §5' },
    { date: '2012/2013', title: 'Field Day with the Inland Empire VHF Radio Amateurs on Mt. Spokane (as WR7VHF)', src: 'history.md §5' },
    { date: '2004-10-02', title: 'Simulated Emergency Test', src: 'history.md §5' }
  ];

  /* ------------------------------------------------------------- documents */
  // status: current      = our document; the public copy is hosted here (href '#' in the mockup)
  //         official     = link to the authoritative source (FEMA, ARRL, county, state…)
  //         section      = maintained by the EWA Section / ag7qp.com; we link, not copy
  //         page         = a page on this site (href '#' until built)
  //         recovering   = being recovered from the old site; not available yet
  //         groupsio     = members-only on groups.io; nothing sensitive is shown here
  //         historical   = kept for the record; not current guidance
  S.documentStatuses = {
    current:    { label: 'Available',        help: 'Our current version, hosted here.' },
    official:   { label: 'Official source',  help: 'Opens the issuing agency’s own copy, so you always get the current version.' },
    section:    { label: 'Section resource', help: 'Maintained by the Eastern Washington Section; opens ag7qp.com.' },
    page:       { label: 'Web page',         help: 'A page on this site.' },
    recovering: { label: 'Coming back',      help: 'Being recovered from the old website. Not available yet.' },
    groupsio:   { label: 'groups.io',        help: 'Members only. Opens groups.io; you need to be an approved member.' },
    historical: { label: 'Historical',       help: 'Kept for the record. Not current guidance.' }
  };

  S.documentCategories = [
    { id: 'net-ops',   label: 'Net operations',            blurb: 'Scripts and guides for the Tuesday net and its variants.' },
    { id: 'join',      label: 'Join & qualify',            blurb: 'Applications, task books and orientation.' },
    { id: 'forms',     label: 'Forms',                     blurb: 'ICS, state and ARRL forms, always from the official source.' },
    { id: 'training',  label: 'Training & presentations',  blurb: 'Courses, study aids and workshop decks.' },
    { id: 'readiness', label: 'Go-kits & readiness',       blurb: 'Kit lists, alert levels and alert registration.' },
    { id: 'digital',   label: 'Winlink & digital',         blurb: 'Email over radio, packet and digital modes.' },
    { id: 'reference', label: 'Reference & SOPs',          blurb: 'Standing orders, field guides, agreements and rules.' },
    { id: 'history',   label: 'History & archive',         blurb: 'Kept for the record.' }
  ];

  var FEMA_FORMS = 'https://training.fema.gov/emiweb/is/icsresource/icsforms/';
  var AG_SPOKANE = 'https://ag7qp.com/spokane-county-ares-acs';
  var GROUPS_FILES = 'https://spokaneares-acs.groups.io/g/main/files';

  // Fields: id, title, category, format, version (date or label), description,
  // href (null = no link yet; '#' = hosted here once built), status, source
  // {label, href}, author (callsign), owner (role), mostUsed, inv (inventory ref),
  // tags, verify, note.
  S.documents = [
    /* ---- Net operations */
    { id: 'net-preamble-weekly', title: 'Weekly net preamble and script', category: 'net-ops', format: 'DOCX', version: '2026-03-04',
      description: 'What net control reads each Tuesday, with pauses for cross-band stations and the Winlink-night inserts.',
      href: '#', status: 'current', source: { label: 'Current copy on ag7qp.com (interim)', href: AG_SPOKANE }, owner: 'Net Manager',
      mostUsed: true, inv: 'AG-1', tags: ['preamble', 'script', 'net control', 'NCS'],
      note: 'The public copy will send contact changes and net reports to a role address instead of a personal e-mail.', verify: true },
    { id: 'net-preamble-simplex', title: 'Simplex net preamble and script (5th Tuesdays)', category: 'net-ops', format: 'DOCX', version: '2026-03-04',
      description: 'Five call-sign groups, read-backs of stations heard, then the move to the repeater.',
      href: '#', status: 'current', source: { label: 'Current copy on ag7qp.com (interim)', href: AG_SPOKANE }, owner: 'Net Manager',
      inv: 'AG-2', tags: ['simplex', 'preamble', 'fifth Tuesday'], verify: true },
    { id: 'net-preamble-gmrs', title: 'ACS GMRS net preamble and script', category: 'net-ops', format: 'DOCX', version: '2025-08-19',
      description: 'The script for the 3rd-Tuesday GMRS net.',
      href: '#', status: 'current', source: { label: 'Current copy on ag7qp.com (interim)', href: AG_SPOKANE }, owner: 'Net Manager',
      inv: 'AG-3', tags: ['GMRS', 'preamble'], verify: true },
    { id: 'ncs-principles', title: 'Principles of a Net Control Station', category: 'net-ops', format: 'Web page', version: 'K7BFL, 2000; posted 2017',
      description: 'Keep track of stations, handle the highest precedence first, and what to do when a traffic net bogs down.',
      href: '#', status: 'page', author: 'K7BFL', owner: 'Net Manager', inv: 'A-9', tags: ['NCS', 'traffic', 'precedence'] },
    { id: 'repeater-etiquette', title: 'Repeater etiquette on W7GBU', category: 'net-ops', format: 'Web page', version: '2017; trustee corrected',
      description: 'How to identify, break in and keep the emergency repeater open and orderly.',
      href: '#', status: 'page', owner: 'Trustee (K7DSR)', inv: 'A-7', tags: ['repeater', 'etiquette', 'W7GBU'], verify: true },
    { id: 'net-roster', title: 'Net roster', category: 'net-ops', format: 'groups.io', version: null,
      description: 'The member roster net control uses. Members only; never posted on this site.',
      href: GROUPS_FILES, status: 'groupsio', inv: 'AG-4', tags: ['roster', 'check-in'] },

    /* ---- Join & qualify */
    { id: 'ares-application', title: 'Spokane County ARES Membership Application', category: 'join', format: 'DOCX', version: '2024-11-07',
      description: 'The ARES application. It includes the Spokane County Emergency Management non-disclosure agreement.',
      href: '#', status: 'current', source: { label: 'Current copy on ag7qp.com (interim)', href: 'https://ag7qp.com/ares-acs' }, owner: 'EC',
      mostUsed: true, inv: 'AG-5', tags: ['join', 'application', 'NDA'],
      note: 'A fillable PDF is planned. Bring it to a meeting or send it to the membership role address.', verify: true },
    { id: 'county-application', title: 'Volunteer Emergency Worker Application (for ACS)', category: 'join', format: 'Online form', version: 'Spokane County FormCenter 276',
      description: 'The county application for ACS. It starts the Sheriff’s Office background check.',
      href: 'https://www.spokanecounty.gov/FormCenter/Emergency-Management-31/Volunteer-Emergency-Worker-Application-276', status: 'official',
      source: { label: 'Spokane County', href: 'https://www.spokanecounty.gov/FormCenter/Emergency-Management-31/Volunteer-Emergency-Worker-Application-276' },
      inv: 'EXT-2', tags: ['ACS', 'county', 'background check'] },
    { id: 'acs-task-book', title: 'ACS Position Task Book', category: 'join', format: 'DOCX', version: '2024-08-09',
      description: 'New Member, Radio Operator (RADO), Senior Radio Operator (S-RADO) and Leadership requirements. Print it and keep it in a binder with your sign-offs.',
      href: '#', status: 'current', source: { label: 'Current copy and guide on ag7qp.com (interim)', href: 'https://ag7qp.com/acs-task-book' }, owner: 'EC',
      mostUsed: true, inv: 'AG-7', tags: ['task book', 'PTB', 'RADO', 'S-RADO'],
      note: 'The 2024 text still calls ALERT Spokane “Code Red”; it moved to Regroup in April 2026.', verify: true },
    { id: 'arrl-ares-task-book', title: 'ARRL ARES Position Task Book', category: 'join', format: 'PDF (fillable) / DOC', version: 'July 2024',
      description: 'The national ARES task book: Basic, Intermediate and Advanced levels.',
      href: 'https://www.arrl.org/files/file/ARES%20Taskbook%20July%202024.pdf', status: 'official', source: { label: 'ARRL', href: 'https://www.arrl.org/ares' },
      inv: 'EXT-1', tags: ['ARRL', 'task book', 'ARES'] },
    { id: 'arrl-ares-plan', title: 'ARRL ARES Plan', category: 'join', format: 'Web page', version: 'July 2025',
      description: 'The national ARES program plan: levels, training and appointments.',
      href: 'https://www.arrl.org/ares', status: 'official', source: { label: 'ARRL', href: 'https://www.arrl.org/ares' }, tags: ['ARRL', 'ARES'], verify: true },
    { id: 'orientation-guide', title: 'New Member Orientation guide', category: 'join', format: 'Web page', version: '2026',
      description: 'The orientation class in writing: the two organizations, qualifying, field guides and hands-on training.',
      href: 'https://ag7qp.com/new-members-orientation', status: 'section', source: { label: 'ag7qp.com', href: 'https://ag7qp.com/new-members-orientation' }, tags: ['orientation'] },
    { id: 'ares-task-book-helps', title: 'ARES Task Book helps (Section guide)', category: 'join', format: 'Web page', version: '2026',
      description: 'The Section Emergency Coordinator’s walk-through of the ARRL task book.',
      href: 'https://ag7qp.com/ares-task-book', status: 'section', source: { label: 'ag7qp.com', href: 'https://ag7qp.com/ares-task-book' }, tags: ['task book'],
      note: 'That page links an older (2024-09-15) application; use the 2024-11-07 version.' },
    { id: 'fema-sid', title: 'FEMA Student ID (SID) sign-up', category: 'join', format: 'Online form', version: null,
      description: 'You need a SID before you can take the FEMA IS course exams. Save it.',
      href: 'https://cdp.dhs.gov/femasid', status: 'official', source: { label: 'DHS / FEMA', href: 'https://cdp.dhs.gov/femasid' }, tags: ['FEMA', 'SID'] },

    /* ---- Forms (always the official source) */
    { id: 'ics-213', title: 'ICS 213 General Message', category: 'forms', format: 'PDF', version: 'v3',
      description: 'The standard message form. Also built into Winlink Express.',
      href: FEMA_FORMS, status: 'official', source: { label: 'FEMA ICS forms', href: FEMA_FORMS }, mostUsed: true, inv: 'OSD-15', tags: ['ICS', 'message', '213'],
      howTo: { label: 'How to write an ICS-213', href: 'https://ag7qp.com/ics-213' } },
    { id: 'ics-213rr', title: 'ICS 213RR Resource Request Message', category: 'forms', format: 'PDF', version: 'v3',
      description: 'For requesting resources. Tip: keep one filled out that lists your go-kit.',
      href: FEMA_FORMS, status: 'official', source: { label: 'FEMA ICS forms', href: FEMA_FORMS }, inv: 'FRM-1', tags: ['ICS', 'resource request', '213RR'] },
    { id: 'ics-214', title: 'ICS 214 Activity Log', category: 'forms', format: 'PDF', version: 'v3.1',
      description: 'Keep one during every exercise and activation (required at RADO).',
      href: FEMA_FORMS, status: 'official', source: { label: 'FEMA ICS forms', href: FEMA_FORMS }, inv: 'OSD-16', tags: ['ICS', 'log', '214'] },
    { id: 'ics-214a', title: 'ICS 214a Individual Log', category: 'forms', format: 'PDF', version: null,
      description: 'A personal activity log. Keep copies in your go-kit.',
      href: null, status: 'official', source: { label: 'Authoritative source being chosen', href: null }, inv: 'FRM-2', tags: ['ICS', 'log', '214a'], verify: true },
    { id: 'ics-309', title: 'ICS 309 Communications Log', category: 'forms', format: 'PDF', version: null,
      description: 'The radio operator’s message log, kept during all ACS radio operations. Winlink Express can generate it for you.',
      href: null, status: 'official', source: { label: 'Authoritative source being chosen', href: null }, mostUsed: true, inv: 'FRM-3', tags: ['ICS', 'comm log', '309'],
      howTo: { label: 'Generate an ICS-309 in Winlink Express (video)', href: 'https://www.youtube.com/watch?v=VmVJpoPmQvc' },
      extra: { label: 'ICS-309 guidance (Hawaii ARES)', href: 'https://old.hawaiiares.net/set2022/9.%20ICS-309%20Guidance.pdf' }, verify: true },
    { id: 'ics-205', title: 'ICS 205 Incident Radio Communications Plan', category: 'forms', format: 'PDF', version: 'v3.1',
      description: 'The blank form. Filled-in plans for real incidents are never posted publicly.',
      href: FEMA_FORMS, status: 'official', source: { label: 'FEMA ICS forms', href: FEMA_FORMS }, inv: 'OSD-14', tags: ['ICS', 'comms plan', '205'] },
    { id: 'ics-201', title: 'ICS 201 Incident Briefing', category: 'forms', format: 'PDF', version: 'v3',
      description: 'The initial incident briefing form.',
      href: FEMA_FORMS, status: 'official', source: { label: 'FEMA ICS forms', href: FEMA_FORMS }, inv: 'OSD-13', tags: ['ICS', 'briefing', '201'] },
    { id: 'arrl-radiogram', title: 'ARRL Radiogram (fillable)', category: 'forms', format: 'PDF', version: null,
      description: 'The National Traffic System message form.',
      href: 'https://www.arrl.org/files/media/Group/Fillable%20Radiogram%20Form.pdf', status: 'official', source: { label: 'ARRL', href: 'https://www.arrl.org/' },
      mostUsed: true, inv: 'EXT-3', tags: ['NTS', 'radiogram', 'traffic'] },
    { id: 'wa-ics-213rr', title: 'Washington State ICS-213RR', category: 'forms', format: 'PDF', version: null,
      description: 'The state resource-request form used in state exercises.',
      href: 'https://mil.wa.gov/asset/5ba420f505d91', status: 'official', source: { label: 'Washington Military Department', href: 'https://mil.wa.gov/' }, tags: ['state', '213RR'], verify: true },
    { id: 'wa-field-situation-report', title: 'Washington State Field Situation Report', category: 'forms', format: 'Winlink form', version: null,
      description: 'A snapshot of phones, power, water, broadcast and internet at your location. In Winlink Express under Templates, Standard Forms, WA State Forms. There is no official PDF.',
      href: 'https://winlink.org/', status: 'official', source: { label: 'Winlink', href: 'https://winlink.org/' }, tags: ['Winlink', 'state', 'situation report', 'SOP 1b'] },
    { id: 'wa-isnap', title: 'Washington State ISNAP (Incident Snapshot)', category: 'forms', format: 'Winlink form', version: null,
      description: 'Incident snapshot for counties and tribal nations. In Winlink Express; no official PDF.',
      href: 'https://winlink.org/', status: 'official', source: { label: 'Winlink', href: 'https://winlink.org/' }, tags: ['Winlink', 'state'] },
    { id: 'wsdot-580-020', title: 'WSDOT Form 580-020, Road and Bridge Assessment', category: 'forms', format: 'PDF', version: null,
      description: 'Washington State Department of Transportation road and bridge assessment form.',
      href: 'https://wsdot.wa.gov/publications/fulltext/forms/580-020.pdf', status: 'official', source: { label: 'WSDOT', href: 'https://wsdot.wa.gov/' }, inv: 'FRM-5', tags: ['WSDOT', 'assessment'] },
    { id: 'fema-ics-forms', title: 'All FEMA ICS forms', category: 'forms', format: 'Web page', version: null,
      description: 'FEMA’s index of every ICS form, always the current versions.',
      href: FEMA_FORMS, status: 'official', source: { label: 'FEMA', href: FEMA_FORMS }, inv: 'EXT-4', tags: ['ICS', 'FEMA'] },

    /* ---- Training & presentations */
    { id: 'is-100', title: 'FEMA IS-100.c: Introduction to the Incident Command System', category: 'training', format: 'Online course', version: 'IS-100.c',
      description: 'Free online. Required once for new members.', href: 'https://training.fema.gov/is/courseoverview.aspx?code=IS-100.c&lang=en', status: 'official', source: { label: 'FEMA EMI', href: 'https://training.fema.gov/is/' }, inv: 'OSD-39', tags: ['FEMA', 'ICS'] },
    { id: 'is-200', title: 'FEMA IS-200.c: Basic ICS for Initial Response', category: 'training', format: 'Online course', version: 'IS-200.c',
      description: 'Free online. Required once for new members.', href: 'https://training.fema.gov/is/courseoverview.aspx?code=IS-200.c&lang=en', status: 'official', source: { label: 'FEMA EMI', href: 'https://training.fema.gov/is/' }, inv: 'OSD-44', tags: ['FEMA', 'ICS'] },
    { id: 'is-700', title: 'FEMA IS-700.b: An Introduction to NIMS', category: 'training', format: 'Online course', version: 'IS-700.b',
      description: 'Free online. Required once for new members.', href: 'https://training.fema.gov/is/courseoverview.aspx?code=IS-700.b&lang=en', status: 'official', source: { label: 'FEMA EMI', href: 'https://training.fema.gov/is/' }, inv: 'OSD-46', tags: ['FEMA', 'NIMS'] },
    { id: 'is-800', title: 'FEMA IS-800.d: National Response Framework, An Introduction', category: 'training', format: 'Online course', version: 'IS-800.d',
      description: 'Free online. Required once for new members.', href: 'https://training.fema.gov/is/courseoverview.aspx?code=IS-800.d&lang=en', status: 'official', source: { label: 'FEMA EMI', href: 'https://training.fema.gov/is/' }, inv: 'OSD-45', tags: ['FEMA', 'NRF'] },
    { id: 'arrl-basic-emcomm', title: 'ARRL Basic EmComm course', category: 'training', format: 'Online course', version: null,
      description: 'Three modules, about 10 to 20 hours. Required once for new members.', href: 'https://learn.arrl.org/courses/67044', status: 'official', source: { label: 'ARRL Learning Center', href: 'https://learn.arrl.org/' }, tags: ['ARRL', 'EmComm'] },
    { id: 'arrl-intermediate-emcomm', title: 'ARRL Intermediate EmComm course', category: 'training', format: 'Online course', version: null,
      description: 'Required once at RADO. 35-question final; 74% to pass.', href: 'https://learn.arrl.org/courses/68230', status: 'official', source: { label: 'ARRL Learning Center', href: 'https://learn.arrl.org/' }, tags: ['ARRL', 'EmComm'] },
    { id: 'fema-study-helps', title: 'FEMA course study helps', category: 'training', format: 'Web page', version: null,
      description: 'Tips for passing the FEMA IS exams, including how to get extra time.', href: 'https://ag7qp.com/fema-course-study-helps', status: 'section', source: { label: 'ag7qp.com', href: 'https://ag7qp.com/fema-course-study-helps' }, tags: ['FEMA'] },
    { id: 'hipaa-video', title: 'HIPAA training video (12 minutes)', category: 'training', format: 'Video', version: null,
      description: 'Covers the HIPAA requirement if you miss it at orientation.', href: 'https://youtu.be/9ryH0P5MCdk', status: 'official', source: { label: 'YouTube (linked from the ACS Task Book)', href: 'https://youtu.be/9ryH0P5MCdk' }, tags: ['HIPAA'], verify: true },
    { id: 'skywarn-training', title: 'SKYWARN spotter training', category: 'training', format: 'Web page', version: null,
      description: 'NWS Spokane spotter classes, in person or online. Encouraged every two years.', href: 'https://www.weather.gov/otx/Current_Spotter_Training', status: 'official', source: { label: 'NWS Spokane', href: 'https://www.weather.gov/otx/' }, tags: ['SKYWARN', 'weather'] },
    { id: 'ratpac', title: 'RATPAC video presentations', category: 'training', format: 'Web page', version: null,
      description: 'A library of recorded emergency-communications presentations.', href: 'https://docs.google.com/spreadsheets/d/e/2PACX-1vTVywG1s025_zsAaKrn8Lr_DPGT6tGV0SVsV5LEdBA91dFCYk_LP8S3kXhMcD9vXFM3Cg-q468jf6Nd/pubhtml', status: 'official', source: { label: 'RATPAC', href: 'https://www.ratpac.us/' }, inv: 'EXT-6', tags: ['video'] },
    { id: 'anderson-powerpoles', title: 'A guide to Anderson Powerpoles', category: 'training', format: 'PDF', version: null,
      description: 'Make your own DC power connections.', href: 'https://www.arrl.org/files/file/Public%20Service/TrainingModules/Technical/Anderson%20powerpole.pdf', status: 'official', source: { label: 'ARRL', href: 'https://www.arrl.org/' }, inv: 'OSD-24', tags: ['power', 'Powerpole'] },
    { id: 'deck-safety-deployments', title: 'Maintaining and Improving Safety on ARES Deployments', category: 'training', format: 'Presentation', version: '2023-01-19',
      description: 'Presented January 19, 2023.', href: null, status: 'recovering', author: 'N7LL', inv: 'OSD-78', tags: ['safety', 'deployment'] },
    { id: 'deck-five-knots', title: 'Five knots you should know', category: 'training', format: 'Presentation', version: null,
      description: 'A presentation from the old library (no description was published).', href: null, status: 'recovering', inv: 'OSD-75', tags: ['knots', 'field'] },
    { id: 'deck-ida', title: 'Initial Damage Assessment and the Winlink IDA form', category: 'training', format: 'Presentation', version: null,
      description: 'How Spokane County ARES-ACS will react to and use the Initial Damage Assessment Winlink form.', href: null, status: 'recovering', inv: 'OSD-63', tags: ['IDA', 'Winlink', 'damage assessment'],
      note: 'To be checked against the current state form before it returns.' },
    { id: 'deck-radio-go-kit', title: 'Radio go-kit presentation', category: 'training', format: 'Presentation', version: null,
      description: 'A presentation on radio go-kits.', href: null, status: 'recovering', author: 'N7GCO', inv: 'OSD-50', tags: ['go-kit'] },
    { id: 'deck-emergency-modules', title: 'Emergency modules', category: 'training', format: 'Presentation', version: null,
      description: 'A comprehensive approach to preparing for emergencies. The author gave permission to host it.', href: null, status: 'recovering', author: 'N7GCO', inv: 'OSD-23', tags: ['preparedness', 'go-kit'] },
    { id: 'deck-power', title: 'How to power your radio when the lights go out', category: 'training', format: 'Presentation', version: null,
      description: 'Battery technologies and solar power for your station.', href: null, status: 'recovering', author: 'WA7AQH', inv: 'OSD-25', tags: ['power', 'battery', 'solar'] },
    { id: 'deck-packet-101', title: 'Intro to packet communications 101', category: 'training', format: 'Presentation', version: null,
      description: 'An introduction to packet radio.', href: null, status: 'recovering', author: 'K7PKT', inv: 'OSD-20', tags: ['packet', 'digital'] },
    { id: 'deck-cross-band', title: 'Cross-band repeaters', category: 'training', format: 'Presentation', version: '2017',
      description: 'Theory, use cases and setup.', href: null, status: 'recovering', author: 'AD7DD', inv: 'OSD-5', tags: ['cross-band', 'repeater'] },
    { id: 'deck-go-kits-2017', title: 'Go-kits (quick-reaction packs)', category: 'training', format: 'Presentation', version: '2017',
      description: 'Go-kits, quick-reaction packs and bug-out bags.', href: null, status: 'recovering', author: 'AD7DD', inv: 'OSD-6', tags: ['go-kit'] },
    { id: 'traffic-handler', title: 'Traffic Handler (mentor program, PDF and MP3)', category: 'training', format: 'PDF + MP3', version: null,
      description: 'Traffic-handling training material from the old mentor program.', href: null, status: 'recovering', inv: 'OSD-8, OSD-9', tags: ['traffic', 'NTS', 'mentor'], verify: true },

    /* ---- Go-kits & readiness */
    { id: 'go-kits', title: 'Go-kit guide: 2-6 hours, 12-24 hours, 24-72+ hours', category: 'readiness', format: 'Web page', version: '2026',
      description: 'What goes in a personal kit and a radio kit for short, longer and long deployments, including the forms to carry.',
      href: 'https://ag7qp.com/go-kits', status: 'section', source: { label: 'ag7qp.com', href: 'https://ag7qp.com/go-kits' }, tags: ['go-kit', 'kit list'] },
    { id: 'equipment-lists', title: 'Personal and station equipment lists', category: 'readiness', format: 'Web page', version: '2017; being refreshed',
      description: 'Five-hour and overnight kits, and station gear by assignment. Station gear should run for at least 72 hours.',
      href: '#', status: 'page', owner: 'AEC, Logistics & Comms', inv: 'A-21', tags: ['equipment', 'station'] },
    { id: 'alert-levels', title: 'Alert levels explained (Green, Yellow, Orange, Red)', category: 'readiness', format: 'Web page', version: '2017',
      description: 'What each level asks of members. A reference, not a live status.',
      href: '../how-it-works.html#alert-levels', status: 'page', owner: 'EC', inv: 'A-20', tags: ['alert levels', 'activation'] },
    { id: 'alert-spokane', title: 'Register with ALERT Spokane', category: 'readiness', format: 'Web page', version: 'Regroup platform since April 2026',
      description: 'The county’s public alert system. Registration is a New Member requirement.',
      href: 'https://www.spokanecounty.gov/3007/Alert-Spokane', status: 'official', source: { label: 'Spokane County', href: 'https://www.spokanecounty.gov/3007/Alert-Spokane' }, tags: ['alerts', 'Regroup'] },
    { id: 'ready-kit', title: 'Build a household emergency kit', category: 'readiness', format: 'Web page', version: null,
      description: 'Ready.gov’s supply list for home.', href: 'https://www.ready.gov/kit', status: 'official', source: { label: 'Ready.gov', href: 'https://www.ready.gov/kit' }, inv: 'EXT-7', tags: ['preparedness'] },
    { id: 'programming-radios', title: 'Radio programming cheat sheets', category: 'readiness', format: 'Web page', version: null,
      description: 'Keypad programming for common handhelds and mobiles. Bring your HT and its manual to orientation.',
      href: 'https://ag7qp.com/programming-radios', status: 'section', source: { label: 'ag7qp.com', href: 'https://ag7qp.com/programming-radios' }, tags: ['programming', 'HT'] },
    { id: 'gps-conversion', title: 'GPS coordinates conversion', category: 'readiness', format: 'Document', version: null,
      description: 'Converting between latitude and longitude formats.', href: null, status: 'recovering', inv: 'OSD-64', tags: ['GPS', 'coordinates'], verify: true },

    /* ---- Winlink & digital */
    { id: 'winlink-intro', title: 'Winlink: getting started', category: 'digital', format: 'Web page', version: null,
      description: 'Account setup, sending by Telnet, then by radio; plus Winlink’s mapping of Field Situation Reports.',
      href: 'https://ag7qp.com/winlink', status: 'section', source: { label: 'ag7qp.com', href: 'https://ag7qp.com/winlink' }, tags: ['Winlink', 'setup'] },
    { id: 'winlink-express', title: 'Winlink Express (software)', category: 'digital', format: 'Software', version: null,
      description: 'The Winlink client. Use the service code PUBLIC EMCOMM.', href: 'https://winlink.org/WinlinkExpress', status: 'official', source: { label: 'Winlink', href: 'https://winlink.org/' }, tags: ['Winlink', 'software'] },
    { id: 'winlink-mapping', title: 'Winlink map-enabled forms (guide)', category: 'digital', format: 'PDF', version: null,
      description: 'How reports sent to a callsign can be shown on a map in Winlink Express.', href: 'https://winlink.org/sites/default/files/RMSE_FORMS/winlink_mapping_enabled_forms.pdf', status: 'official', source: { label: 'Winlink', href: 'https://winlink.org/' }, tags: ['Winlink', 'mapping'], verify: true },
    { id: 'winlink-practice-2022', title: 'Winlink practice exercises', category: 'digital', format: 'Document', version: '2022',
      description: 'Winlink practice exercises from 2022.', href: null, status: 'recovering', inv: 'OSD-71', tags: ['Winlink', 'practice'] },
    { id: 'deck-winlink-scripting', title: 'Scripting in Winlink 101', category: 'digital', format: 'Presentation', version: '2018-01-13',
      description: 'From a Second Saturday digital workshop.', href: null, status: 'recovering', inv: 'OSD-11', tags: ['Winlink', 'scripting'] },
    { id: 'deck-ardop', title: 'ARDOP 101 for Winlink', category: 'digital', format: 'Presentation', version: null,
      description: 'A quick introduction to ARDOP for Winlink.', href: null, status: 'recovering', inv: 'OSD-19', tags: ['Winlink', 'ARDOP', 'HF'] },
    { id: 'signalink-packet', title: 'Packet with a SignaLink sound card (UZ7HO SoundModem)', category: 'digital', format: 'Document', version: null,
      description: 'SoundModem and EasyTerm as a packet station.', href: null, status: 'recovering', inv: 'OSD-21', tags: ['packet', 'SignaLink'] },
    { id: 'i-am-safe', title: '“I Am Safe” messaging: procedures', category: 'digital', format: 'DOCX', version: 'July 2023',
      description: 'Welfare messages from a disaster area. Developed by the Seattle Emergency Communication Hubs, Seattle ACS, Radio Relay International and the Winlink Development Team.',
      href: 'https://ag7qp.com/ares-acs', status: 'section', source: { label: 'ag7qp.com', href: 'https://ag7qp.com/ares-acs' }, inv: 'AG-9', tags: ['welfare', 'Winlink'], verify: true },

    /* ---- Reference & SOPs */
    { id: 'sop-1b', title: 'Eastern Washington Section Standing Order 1b', category: 'reference', format: 'PDF', version: '2026-01-16',
      description: 'What to do if something happens and you have no instructions from your EC or the agency having jurisdiction.',
      href: 'https://ag7qp.com/ares-acs', status: 'section', source: { label: 'EWA Section (ag7qp.com)', href: 'https://ag7qp.com/ares-acs' }, mostUsed: true, inv: 'AG-8', tags: ['SOP', 'self-activation', 'HF'] },
    { id: 'field-guides', title: 'AUXFOG and NIFOG field operations guides', category: 'reference', format: 'PDF / app', version: null,
      description: 'Free national field guides from CISA SAFECOM, as PDFs or phone apps.', href: 'https://www.cisa.gov/safecom/field-operations-guides', status: 'official', source: { label: 'CISA SAFECOM', href: 'https://www.cisa.gov/safecom/field-operations-guides' }, tags: ['FOG', 'AUXFOG', 'NIFOG'] },
    { id: 'acsfog', title: 'Spokane County ACS Field Operating Guide (ACSFOG)', category: 'reference', format: 'groups.io', version: null,
      description: 'Half-page and full-page versions, with phone-install instructions. Members only; never posted here.', href: GROUPS_FILES, status: 'groupsio', mostUsed: true, tags: ['ACSFOG', 'FOG'] },
    { id: 'ics-213-howto', title: 'How to write an ICS-213', category: 'reference', format: 'Web page', version: null,
      description: 'Block-by-block instructions, dates as MM/DD/YYYY and 24-hour times with L or Z.', href: 'https://ag7qp.com/ics-213', status: 'section', source: { label: 'ag7qp.com', href: 'https://ag7qp.com/ics-213' }, tags: ['ICS-213', 'message'] },
    { id: 'ievhfra-mou', title: 'MOU with the Inland Empire VHF Radio Amateurs (Rev. 5)', category: 'reference', format: 'Web page', version: '2023-05-06',
      description: 'Backup repeater (146.880), a second repeater for specialised nets, and packet paths into Winlink.', href: '../about.html#agreements', status: 'page', inv: 'A-68', tags: ['MOU', 'IEVHFRA', 'agreement'] },
    { id: 'part-97', title: '47 CFR Part 97 (Amateur Radio Service rules)', category: 'reference', format: 'Web page', version: 'current',
      description: 'The FCC rules, including 97.407 on RACES.', href: 'https://www.ecfr.gov/current/title-47/chapter-I/subchapter-D/part-97', status: 'official', source: { label: 'eCFR', href: 'https://www.ecfr.gov/current/title-47/chapter-I/subchapter-D/part-97' }, tags: ['FCC', 'rules', 'RACES'] },
    { id: 'public-service-tips', title: 'Preparing for public-service communications', category: 'reference', format: 'Web page', version: null,
      description: 'Nine tips for your first event shift.', href: 'https://ag7qp.com/preparing-for-public-service-communications', status: 'section', source: { label: 'ag7qp.com', href: 'https://ag7qp.com/preparing-for-public-service-communications' }, tags: ['events', 'public service'] },
    { id: 'rfi-resources', title: 'RFI resources', category: 'reference', format: 'Web page', version: '2021',
      description: 'Finding and fixing radio-frequency interference.', href: '#', status: 'page', author: 'N7GCO', inv: 'A-62', tags: ['RFI'] },
    { id: 'radio-amateur-code', title: 'The Radio Amateur’s Code', category: 'reference', format: 'Web page', version: '1928, modernised',
      description: 'Considerate, loyal, progressive, friendly, balanced, patriotic.', href: '#', status: 'page', inv: 'A-6', tags: ['code'] },

    /* ---- History & archive */
    { id: 'ares-races-plan', title: 'Spokane County ARES/RACES Plan (Master 7.13)', category: 'history', format: 'PDF', version: 'about 2013',
      description: 'The group’s last written plan. Kept for the record; names, structure and agreements are out of date.', href: null, status: 'historical', inv: 'A-2', tags: ['plan', 'history'], verify: true },
    { id: 'w7gbu-story', title: 'The W7GBU story', category: 'history', format: 'Web page', version: null,
      description: 'Why our call sign honors Ira “Dean” Bula.', href: '../about.html#w7gbu', status: 'page', inv: 'A-5', tags: ['W7GBU', 'history'] }
  ];

  /* ------------------------------------------------------------------ links */
  S.links = {
    groupsio: {
      main: 'https://spokaneares-acs.groups.io/g/main',
      files: GROUPS_FILES,
      calendar: 'https://spokaneares-acs.groups.io/g/main/calendar',   // members; not confirmed public (D9)
      subgroups: 'https://spokaneares-acs.groups.io/g/main/subgroups',  // verify
      subgroupNames: ['Digital-Modes', 'HamWan', 'Hospital-Coordination', 'Leadership', 'SHARES', 'Winlink', 'WinterFieldDay'],
      joinNote: 'Apply to join; a moderator approves each new member. The archive, Files and Wiki are members-only.'
    },
    county: {
      application: 'https://www.spokanecounty.gov/FormCenter/Emergency-Management-31/Volunteer-Emergency-Worker-Application-276',
      acsPage: 'https://www.spokanecounty.gov/1813/ACS-Auxiliary-Communication-Systems',
      scem: 'https://www.spokanecounty.gov/1951/About-Us',                // verify (.org link on ag7qp)
      alertSpokane: 'https://www.spokanecounty.gov/3007/Alert-Spokane'
    },
    arrl: {
      home: 'https://www.arrl.org/',
      ares: 'https://www.arrl.org/ares',
      aresTaskBook: 'https://www.arrl.org/files/file/ARES%20Taskbook%20July%202024.pdf',
      set: 'https://www.arrl.org/simulated-emergency-test',
      examSearch: 'https://www.arrl.org/find-an-amateur-radio-license-exam-session',
      youthGrant: 'https://www.arrl.org/youth-licensing-grant-program',
      learn: 'https://learn.arrl.org/',
      nwDivision: 'https://arrlnwdiv.org/'
    },
    section: {
      interim: AG_SPOKANE,
      ewa: 'https://ag7qp.com/eastern-washington-section',
      sop1b: 'https://ag7qp.com/ares-acs',
      licenses: 'https://ag7qp.com/licenses',
      licenseExams: 'https://ag7qp.com/license-exams',
      orientation: 'https://ag7qp.com/new-members-orientation',
      acsTaskBook: 'https://ag7qp.com/acs-task-book',
      aresTaskBook: 'https://ag7qp.com/ares-task-book',
      wsen: 'https://www.wastateares.org/washington-state-emergency-net'
    },
    fema: {
      sid: 'https://cdp.dhs.gov/femasid',
      icsForms: FEMA_FORMS,
      is: 'https://training.fema.gov/is/'
    },
    cisaFogs: 'https://www.cisa.gov/safecom/field-operations-guides',
    nws: { spokane: 'https://www.weather.gov/otx/', spotterTraining: 'https://www.weather.gov/otx/Current_Spotter_Training', skywarn: 'https://www.weather.gov/skywarn/', srd: 'https://www.weather.gov/crh/skywarnrecognition' },
    clubs: { ievhfra: 'https://www.vhfclub.org/', kbara: 'https://kbara.org/', sdxa: 'https://sdxa.org/' },
    licensing: {
      hamstudy: 'https://hamstudy.org/',
      glaarg: 'https://hamstudy.org/sessions/glaarg',
      wa7dre: 'http://wa7dre.org/testing/',
      kars: 'https://k7id.org/fcc-testing/',
      laurelVec: 'https://www.laurelvec.com/',
      fccFrn: 'https://apps.fcc.gov/cores/userLogin.do'
    },
    winlink: { home: 'https://winlink.org/', express: 'https://winlink.org/WinlinkExpress' },
    law: {
      part97: 'https://www.ecfr.gov/current/title-47/chapter-I/subchapter-D/part-97',
      s97_407: 'https://www.ecfr.gov/current/title-47/chapter-I/subchapter-D/part-97/subpart-E/section-97.407',
      rcw3852: 'https://app.leg.wa.gov/rcw/default.aspx?cite=38.52',
      wac11804: 'https://app.leg.wa.gov/wac/default.aspx?cite=118-04'    // verify
    },
    other: {
      readyKit: 'https://www.ready.gov/kit',
      shakeout: 'https://www.shakeout.org/',
      dyfi: 'https://earthquake.usgs.gov/data/dyfi/',
      winterFieldDay: 'https://winterfieldday.org/',
      america250: 'https://www.arrl.org/america250-was',
      ratpac: 'https://www.ratpac.us/'
    },
    // Proposed role addresses (decisions.md D11). NOT LIVE. Render with data-verify="true".
    contact: {
      join: 'mailto:join@spokares.org',
      ec: 'mailto:ec@spokares.org',
      webmaster: 'mailto:webmaster@spokares.org',
      verify: true,
      note: 'Proposed role addresses; the EC names recipients before launch.'
    }
  };

  /* ------------------------------------------------------------ leadership */
  // Role + callsign only (verify every row; staff page last edited 2026-03-24).
  S.roles = [
    { role: 'Emergency Coordinator (EC); website admin', callsign: 'W7TSC', note: 'Also the county’s RACES/ACS officer; the exact county title is being confirmed.', verify: true },
    { role: 'AEC, Public Events; W7GBU-10 Winlink gateway manager; Winlink coordinator', callsign: 'NV2Z', verify: true },
    { role: 'AEC, Hospital Coordinator', callsign: 'K7MHG', verify: true },
    { role: 'AEC, Logistics & Comms; W7GBU license trustee', callsign: 'K7DSR', verify: true },
    { role: 'AEC, Net Manager', callsign: 'AG7QP', note: 'Also the ARRL Eastern Washington Section Emergency Coordinator.', verify: true },
    { role: 'Public Information Officer', callsign: null, open: true, note: 'Open position.', verify: true },
    { role: 'SKYWARN representatives', callsign: 'AA7RT, W7UWC', verify: true }
  ];
  S.sectionRoles = [
    { role: 'ARRL Eastern Washington Section Manager', callsign: 'KA7LJQ', verify: true },
    { role: 'ARRL EWA Section Emergency Coordinator', callsign: 'AG7QP', verify: true },
    { role: 'ARES District 9 Emergency Coordinator', callsign: 'WA7EC', verify: true }
  ];

  /* =================================================================== helpers */
  var DAY = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
  var DAYLONG = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
  var MON = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
  var MONLONG = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

  function pad(n) { return (n < 10 ? '0' : '') + n; }
  function parse(iso) { var p = String(iso).split('-'); return new Date(+p[0], +p[1] - 1, +(p[2] || 1)); }
  function iso(d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); }
  function addDays(d, n) { var x = new Date(d.getFullYear(), d.getMonth(), d.getDate()); x.setDate(x.getDate() + n); return x; }
  function nthOfMonth(d) { return Math.floor((d.getDate() - 1) / 7) + 1; }
  function matchesRule(rule, d) {
    if (!rule) return false;
    if (rule.freq === 'weekdays') return d.getDay() >= 1 && d.getDay() <= 5;
    if (d.getDay() !== rule.weekday) return false;
    if (rule.freq === 'weekly') return true;
    if (rule.freq === 'monthly') {
      var n = nthOfMonth(d);
      return Object.prototype.toString.call(rule.nth) === '[object Array]' ? rule.nth.indexOf(n) !== -1 : n === rule.nth;
    }
    return false;
  }
  function find(list, key, val) { for (var i = 0; i < list.length; i++) { if (list[i][key] === val) return list[i]; } return null; }

  var fn = {};
  fn.parse = parse;
  fn.iso = iso;
  fn.today = function () {
    try {
      var m = /[?&]today=(\d{4}-\d{2}-\d{2})/.exec(root.location && root.location.search || '');
      if (m) return m[1];
    } catch (e) { /* ignore */ }
    return S.meta.asOf;
  };
  /* style: 'short' → "Tue, Sep 29"; 'long' → "Tuesday, September 29, 2026"; 'day' → "Sep 29"; 'dm' → {dow, day, mon} */
  fn.fmtDate = function (isoDate, style) {
    var d = parse(isoDate);
    if (style === 'long') return DAYLONG[d.getDay()] + ', ' + MONLONG[d.getMonth()] + ' ' + d.getDate() + ', ' + d.getFullYear();
    if (style === 'day') return MON[d.getMonth()] + ' ' + d.getDate();
    if (style === 'dm') return { dow: DAY[d.getDay()], day: d.getDate(), mon: MON[d.getMonth()], year: d.getFullYear() };
    return DAY[d.getDay()] + ', ' + MON[d.getMonth()] + ' ' + d.getDate();
  };
  /* "20:00" → "8:00 PM"; "09:00" → "9:00 AM"; "12:00" → "noon" */
  fn.fmtTime = function (hhmm) {
    if (!hhmm) return '';
    var p = hhmm.split(':'), h = +p[0], m = p[1];
    if (h === 12 && m === '00') return 'noon';
    return ((h % 12) || 12) + ':' + m + ' ' + (h < 12 ? 'AM' : 'PM');
  };
  fn.rotaFor = function (isoDate) { return find(S.rota, 'date', isoDate); };
  fn.winlinkFor = function (isoDate) { return find(S.winlink.assignments, 'date', isoDate); };

  /* The weekly net on a given Tuesday, with its variants and net control. */
  fn.netOn = function (isoDate) {
    var d = parse(isoDate);
    if (d.getDay() !== 2) return null;
    var n = nthOfMonth(d);
    var rota = fn.rotaFor(isoDate);
    var simplex = matchesRule(find(S.nets, 'id', 'simplex-5th').rule, d);
    var winlink = matchesRule(find(S.nets, 'id', 'winlink-nights').rule, d);
    return {
      date: isoDate, time: '20:00', type: 'net', id: 'net-' + isoDate,
      title: 'Tuesday net',
      mode: simplex ? 'simplex' : (winlink ? 'winlink' : 'repeater'),
      modeLabel: simplex ? 'Simplex net (5th Tuesday)' : (winlink ? 'Winlink night' : 'On the W7GBU repeater'),
      where: simplex ? find(S.nets, 'id', 'simplex-5th').where : S.nets[0].where,
      netControl: rota ? (rota.open ? null : rota.callsign) : undefined,   // undefined = not published yet
      netControlName: rota && !rota.open ? rota.name : null,
      open: !!(rota && rota.open),
      winlinkAssignment: winlink ? fn.winlinkFor(isoDate) : null,
      gmrsSameDay: n === 3,
      verify: simplex || winlink
    };
  };

  /* Next weekly net on or after `from` (default today). */
  fn.nextNet = function (from) {
    var d = parse(from || fn.today());
    for (var i = 0; i < 8; i++) { var x = addDays(d, i); if (x.getDay() === 2) return fn.netOn(iso(x)); }
    return null;
  };

  /* Next n dates for a meeting id, recomputed from its rule. */
  fn.nextMeetings = function (from, n) {
    var start = parse(from || fn.today()), out = [];
    for (var m = 0; m < S.meetings.length; m++) {
      var mt = S.meetings[m], dates = [];
      if (mt.rule) {
        for (var i = 0; i < 400 && dates.length < (n || 3); i++) {
          var x = addDays(start, i);
          if (mt.skipMonths && mt.skipMonths.indexOf(x.getMonth() + 1) !== -1) continue;
          if (matchesRule(mt.rule, x)) dates.push(iso(x));
        }
      }
      out.push({ id: mt.id, name: mt.name, dates: dates, start: mt.start, end: mt.end, displayTime: mt.displayTime || null, display: mt.display || null, verify: !!mt.verify });
    }
    return out;
  };

  /* Merged, sorted list of nets, meetings and events in a window.
     opts: { from: iso, days: 14, limit: 0 (no limit), types: ['net','gmrs','meeting','exercise','training','public-service','on-air'] } */
  fn.upcoming = function (opts) {
    opts = opts || {};
    var from = parse(opts.from || fn.today()), days = opts.days || 14, types = opts.types || null, items = [];
    function want(t) { return !types || types.indexOf(t) !== -1; }
    for (var i = 0; i < days; i++) {
      var x = addDays(from, i), xi = iso(x);
      if (want('net') && x.getDay() === 2) items.push(fn.netOn(xi));
      if (want('gmrs') && matchesRule(find(S.nets, 'id', 'gmrs').rule, x)) items.push({ date: xi, time: '19:30', type: 'gmrs', id: 'gmrs-' + xi, title: 'ACS GMRS net', where: find(S.nets, 'id', 'gmrs').where, verify: true });
      if (want('meeting')) {
        for (var m = 0; m < S.meetings.length; m++) {
          var mt = S.meetings[m];
          if (!mt.rule) continue;
          if (mt.skipMonths && mt.skipMonths.indexOf(x.getMonth() + 1) !== -1) continue;
          if (matchesRule(mt.rule, x)) items.push({ date: xi, time: mt.start, endTime: mt.end, displayTime: mt.displayTime || null, sortTime: mt.start || (mt.displayTime === 'Evening' ? '18:00' : null), type: 'meeting', id: mt.id + '-' + xi, title: mt.name, where: mt.where, verify: !!mt.verify });
        }
      }
    }
    var endIso = iso(addDays(from, days - 1)), fromIso = iso(from);
    for (var e = 0; e < S.events.length; e++) {
      var ev = S.events[e], evEnd = ev.end || ev.start;
      if (!want(ev.type)) continue;
      if (evEnd >= fromIso && ev.start <= endIso) {
        items.push({ date: ev.start, endDate: ev.end || null, time: ev.time || null, endTime: ev.endTime || null, type: ev.type, id: ev.id, title: ev.title, where: ev.where || null, summary: ev.summary, inProgress: ev.start < fromIso || (ev.start === fromIso && !!ev.end), verify: !!ev.verify });
      }
    }
    function key(x) { return x.sortTime || x.time || '00:00'; }
    items.sort(function (a, b) { return a.date === b.date ? key(a).localeCompare(key(b)) : (a.date < b.date ? -1 : 1); });
    return opts.limit ? items.slice(0, opts.limit) : items;
  };

  /* Document search/filter. q matches title, description, tags, version, id, inv. */
  fn.documents = function (opts) {
    opts = opts || {};
    var q = (opts.q || '').toLowerCase().replace(/^\s+|\s+$/g, ''), out = [];
    for (var i = 0; i < S.documents.length; i++) {
      var d = S.documents[i];
      if (opts.category && d.category !== opts.category) continue;
      if (opts.status && d.status !== opts.status) continue;
      if (opts.mostUsed && !d.mostUsed) continue;
      if (q) {
        var hay = [d.title, d.description, d.version, d.id, d.inv, d.format, (d.tags || []).join(' ')].join(' ').toLowerCase();
        var words = q.split(/\s+/), ok = true;
        for (var w = 0; w < words.length; w++) { if (hay.indexOf(words[w]) === -1) { ok = false; break; } }
        if (!ok) continue;
      }
      out.push(d);
    }
    return out;
  };
  fn.docById = function (id) { return find(S.documents, 'id', id); };

  S.fn = fn;
  root.SPOKARES = S;
})(typeof window !== 'undefined' ? window : this);
