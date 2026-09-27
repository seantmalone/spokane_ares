/*!
 * Spokane County ARES-ACS: round-3 mockup data (window.SPOKARES)
 * =============================================================================
 * Trimmed from round2/_shared/assets/data.js to what the round-3 pages show
 * (round3/_shared/copy-*.md and placement-map.md). The round-2 file keeps the
 * full records, notes and inventory refs; nothing here adds a fact.
 *
 * THE SINGLE SOURCE for the members hub (this week, rota), Home's next-meeting
 * dates, the exercises page and the document library in all three options.
 * Pages must not hard-code these values except as a no-JS fallback that matches
 * this file as of meta.asOf.
 *
 * In production this file is REPLACED BY CMS DATA (calendar feed, D9; document
 * post type or data file, D10). Keep the shapes; swap the source.
 *
 * Loading: a plain <script src="…/data.js"></script>. No fetch(), so pages work
 * from file://. No dependencies. ES5 syntax on purpose.
 *
 * Conventions
 *   - Dates are ISO "YYYY-MM-DD", local (America/Los_Angeles). Times are 24 h
 *     "HH:MM" in data; always show 12 h to readers ("8:00 PM"), never "2000".
 *   - verify: true  => render the element with data-verify="true". Never print
 *     "verify"/"TBD" chips.
 *   - People: role + call sign only. No personal names (round 3 shows none, the
 *     rota included), no phone numbers, no personal e-mails.
 *   - No '#' hrefs. href null = no target yet: print the title and "Soon".
 *   - src: where the fact comes from (paths relative to research/).
 *
 * Deliberately NOT in this file (do not add): the 1st-Tuesday staff meeting,
 * the Hospital Net or its channel, any simplex frequency, the W7GBU-10
 * frequency, GMRS repeater details, county/hospital/SHARES/800 MHz channels,
 * the net roster, member counts, and any "current readiness level".
 *
 * Helpers (SPOKARES.fn): today(), parse(iso), iso(date), fmtDate(iso, style),
 * fmtTime("20:00"), netOn(iso), nextNet(fromIso), nextMeetings(fromIso, n),
 * upcoming({from, days, limit, types}), documents({q, category, mostUsed}),
 * docById(id).
 * =============================================================================
 */
(function (root) {
  'use strict';

  var S = {};

  /* ------------------------------------------------------------------ meta */
  S.meta = {
    asOf: '2026-09-26',               // frozen mockup "today" (a Saturday; day 1 of the WSDOT exercise)
    timezone: 'America/Los_Angeles',
    version: 'round3-shared-2026-09-26',
    footerNote: 'Mockup — content to be verified before launch'
  };

  /* ------------------------------------------------------------------- org */
  S.org = {
    name: 'Spokane County ARES-ACS',
    callsign: 'W7GBU',
    location: {                                // Home #visit (plain text, data-verify on the block)
      name: 'Spokane County Emergency Management',
      street: '1121 W Gardner Ave',
      city: 'Spokane', state: 'WA', zip: '99260',
      verify: true,
      src: 'facts.yaml meeting.location'
    }
  };

  /* ----------------------------------------------------------------- radio */
  // Shown on how-it-works.html#nets (settings box, Orange readiness row) and
  // the hub's copyable line. Amateur facts only (decisions.md D13).
  S.radio = {
    primary:   { call: 'W7GBU', freq: '147.300', unit: 'MHz', offset: '+600 kHz', tone: '100 Hz', src: 'facts.yaml repeater.primary' },
    alternate: { freq: '146.880', unit: 'MHz', offset: '-600 kHz', tone: '123 Hz', verify: true, src: 'facts.yaml repeater.alternate' },
    nws:       { call: 'WXL86', freq: '162.400', unit: 'MHz', label: 'NOAA Weather Radio', src: 'content/live/20-ares-alert-levels-status-codes.md' },
    copyText: 'W7GBU 147.300 MHz, +600 kHz offset, 100 Hz tone'
  };

  /* ------------------------------------------------------------------ nets */
  // rule: { freq: 'weekly'|'monthly', weekday: 0=Sun…6=Sat, nth: n or [n, n] }
  // Only the nets the helpers need. (0730 Bunch: About#history text; EWSEN: cut.)
  S.nets = [
    { id: 'weekly', name: 'Tuesday net', rule: { freq: 'weekly', weekday: 2 }, start: '20:00',
      where: 'W7GBU, 147.300 MHz, +600 kHz, 100 Hz tone', verify: false, src: 'facts.yaml net.weekly' },
    { id: 'winlink-nights', name: 'Winlink night', rule: { freq: 'monthly', weekday: 2, nth: [2, 4] }, start: '20:00',
      modifiesNet: 'weekly', verify: true, src: 'facts.yaml net.winlink_nights' },
    { id: 'simplex-5th', name: 'Simplex net', rule: { freq: 'monthly', weekday: 2, nth: 5 }, start: '20:00',
      where: 'Starts on simplex, then moves to W7GBU.',   // no frequency until the Net Manager confirms (D13 #5)
      modifiesNet: 'weekly', verify: true, src: 'facts.yaml net.fifth_tuesday_simplex' },
    { id: 'gmrs', name: 'ACS GMRS net', rule: { freq: 'monthly', weekday: 2, nth: 3 }, start: '19:30',
      where: null,                                        // repeater details stay off the site (owner's OK needed)
      verify: true, src: 'facts.yaml gmrs.net' }
  ];

  /* ------------------------------------------------------------------ rota */
  // Net control for the Tuesday net (ag7qp.com, seen 2026-09-25), from asOf
  // onward. Call signs only. callsign null + open: true = open slot. The Note
  // column (Simplex / Winlink night / GMRS) comes from fn.netOn(), not from here.
  S.rotaMeta = {
    openSlotAction: 'Open slot? Tell the Net Manager (AG7QP) on the net or on groups.io.',
    src: 'interim-ag7qp/spokane-county-ares-acs.md; data/calendar-events.json (Sep 2026)'
  };
  S.rota = [
    { date: '2026-09-29', callsign: 'NZ2S' },
    { date: '2026-10-06', callsign: null, open: true },
    { date: '2026-10-13', callsign: 'AE7RJ' },
    { date: '2026-10-20', callsign: 'WA7LNC' },
    { date: '2026-10-27', callsign: null, open: true }
  ];

  /* --------------------------------------------------------------- winlink */
  // members/exercises.html#winlink-assignments (the only place the tasks appear).
  S.winlink = {
    howTo: 'Answer on an ICS-213 unless another form is named. Send to NV2Z and AG7QP between 5:00 and 9:00 PM on the date shown. Radio preferred; Telnet OK.',
    src: 'interim-ag7qp/spokane-county-ares-acs.md',
    assignments: [
      { date: '2026-10-13', form: 'DYFI', task: 'Did You Feel It report (ShakeOut practice)' },
      { date: '2026-10-27', form: 'Welfare Message / Quick Health & Welfare', task: 'A welfare message' },
      { date: '2026-11-10', form: 'ICS-213', task: 'Where you expect to have Thanksgiving dinner' },
      { date: '2026-11-24', form: 'ICS-213RR', task: 'What you want Santa to bring you' },
      { date: '2026-12-08', form: 'ICS-213', task: 'Justify to Santa that you were good this year' },
      { date: '2026-12-22', form: 'ICS-213', task: 'Your New Year’s Eve plans' }
    ]
  };

  /* --------------------------------------------------------------- meetings */
  // Home #visit and the hub's "Next meeting". All at SCEM, 1121 W Gardner Ave.
  // `next` is precomputed from 2026-09-26 for no-JS fallbacks; the helpers
  // recompute from the rule. (Orientation is About#acs-path text, not data.)
  S.meetings = [
    { id: 'workshop', name: 'Second Saturday Workshop', rule: { freq: 'monthly', weekday: 6, nth: 2 },
      start: '09:00', end: '12:00', next: ['2026-10-10', '2026-11-14', '2026-12-12'],
      verify: false, src: 'facts.yaml meeting.workshop' },
    { id: 'winlink-workshop', name: 'Winlink workshop', rule: { freq: 'monthly', weekday: 6, nth: 2 },
      start: '12:30', end: '15:30', next: ['2026-10-10', '2026-11-14', '2026-12-12'],
      verify: true, src: 'facts.yaml meeting.winlink_workshop' },
    { id: 'third-thursday', name: 'Third Thursday training meeting', rule: { freq: 'monthly', weekday: 4, nth: 3 },
      start: null, end: null, displayTime: 'Evening',    // 6 PM vs 7 PM unresolved (D13 #4)
      skipMonths: [12],                                  // no December meeting? (D13 #10)
      next: ['2026-10-15', '2026-11-19', '2027-01-21'],
      verify: true, src: 'facts.yaml meeting.third_thursday' }
  ];

  /* ----------------------------------------------------------------- events */
  // type: exercise | training | public-service | on-air. Titles and tasks are
  // exactly as shown on members/exercises.html and the hub.
  S.events = [
    { id: 'wsdot-2026', title: 'ARES-ACS / WSDOT exercise', type: 'exercise',
      start: '2026-09-26', end: '2026-09-27', allDay: true,
      extraForm: 'wsdot-580-020', detailsHref: 'https://spokaneares-acs.groups.io/g/main', detailVerify: true,
      verify: false, src: 'interim-ag7qp/spokane-county-ares-acs.md' },
    { id: 'set-2026', title: 'Simulated Emergency Test', type: 'exercise',
      start: '2026-10-03', allDay: true,
      tasks: [
        'Use Winlink to send a WA State Field Situation Report to your EC and the SEC (AG7QP).',
        'Send an NTS message to the Section Manager (KA7LJQ) and the SEC.',
        'Take photos of your station and operation.',
        'Make as many contacts as you can under POTA or SOTA rules.',
        'Send an after-action report, with a participant list, to the SEC.'
      ],
      links: [{ label: 'ARRL SET forms', href: 'https://www.arrl.org/simulated-emergency-test' }],
      verify: false, src: 'interim-ag7qp/home.md; interim-ag7qp/spokane-county-ares-acs.md' },
    { id: 'shakeout-2026', title: 'Great ShakeOut', type: 'exercise',
      start: '2026-10-15', time: '10:15',
      summary: 'send a DYFI report by Winlink, marked as an exercise',
      links: [{ label: 'Great ShakeOut', href: 'https://www.shakeout.org/' }, { label: 'DYFI', href: 'https://earthquake.usgs.gov/data/dyfi/' }],
      verify: true, src: 'interim-ag7qp/spokane-county-ares-acs.md' },
    { id: 'shares-training-2026-10', title: 'SHARES training', type: 'training',
      start: '2026-10-17', time: '09:00', endTime: '12:00',
      summary: 'for SHARES participants',
      verify: true, src: 'interim-ag7qp/spokane-county-ares-acs.md' },
    { id: 'commex-2026', title: 'State COMMEX', type: 'exercise',
      start: '2026-10-24', allDay: true,
      verify: true, src: 'interim-ag7qp/spokane-county-ares-acs.md' },
    { id: 'w1aw7-2026', title: 'Washington operates as W1AW/7', type: 'on-air', optional: true,
      start: '2026-11-18', end: '2026-11-25', allDay: true,
      summary: 'optional shifts',
      links: [{ label: 'W1AW/7', href: 'https://www.arrl.org/america250-was' }],
      verify: true, src: 'interim-ag7qp/home.md' },
    { id: 'srd-2026', title: 'SKYWARN Recognition Day', type: 'on-air',
      start: '2026-12-04', end: '2026-12-05', allDay: true,
      links: [{ label: 'SKYWARN Recognition Day', href: 'https://www.weather.gov/crh/skywarnrecognition' }],
      verify: true, src: 'interim-ag7qp/home.md' },
    { id: 'wfd-2027', title: 'Winter Field Day', type: 'on-air',
      start: '2027-01-23', end: '2027-01-24', allDay: true,
      links: [{ label: 'Winter Field Day', href: 'https://winterfieldday.org/' }],
      verify: false, src: 'interim-ag7qp/home.md' },
    { id: 'bloomsday-2027', title: 'Bloomsday', type: 'public-service',
      start: '2027-05-02', allDay: true, contact: { callsign: 'NV2Z' },
      verify: false, src: 'interim-ag7qp/home.md' },
    { id: 'climb-2027', title: 'Climb for the Cure', type: 'public-service',
      start: '2027-06-13', allDay: true, contact: { callsign: 'NZ2S' },
      verify: true, src: 'interim-ag7qp/home.md; orgs/relationships.md §23' }
  ];

  // members/exercises.html#past, exactly these rows (month and year shown).
  S.pastExercises = [
    { date: '2025', title: 'Field Day in Spokane Valley as W7GBU', verify: true, src: 'orgs/relationships.md §3' },
    { date: '2022-10', title: 'Rockford exercise', src: 'data/calendar-events.json' },
    { date: '2019-12', title: '“Hazard City” hospital deployment exercise', verify: true, src: 'history.md §2.4' },
    { date: '2019-10', title: 'Quarterly all-members exercise', src: 'history.md §2.4' },
    { date: '2019-07', title: 'Kootenai County exercise', src: 'data/calendar-events.json' },
    { date: '2019-03', title: 'EOC-to-EOC exercise: 20+ operators across the county', src: 'history.md §2.4' },
    { date: '2018-03', title: 'Fifth-Saturday multi-county exercise', src: 'history.md §2.4' },
    { date: '2013-08', title: 'EOC-to-EOC exercise', src: 'history.md §5' }
  ];

  /* ------------------------------------------------------------- documents */
  // members/documents.html: exactly the rows shown, in page order.
  // Fields: id, title, category, format, version, description (the ≤ 8-word
  // small text shown, or absent), href (the link to use today; null = "Soon"),
  // source {label, href} (label printed in the Source cell; null = "Soon"),
  // hostedHere (ours; moves to this site at launch), mostUsed, howTo, links,
  // tags (search only), verify.
  S.documentCategories = [
    { id: 'net-ops',   label: 'Net operations' },
    { id: 'join',      label: 'Join & task books' },
    { id: 'forms',     label: 'Forms' },
    { id: 'training',  label: 'Training' },
    { id: 'readiness', label: 'Go-kits & readiness' },
    { id: 'digital',   label: 'Winlink & digital' },
    { id: 'reference', label: 'Reference' }
  ];

  var AG = { label: 'ag7qp.com' };
  var FEMA_FORMS = 'https://training.fema.gov/emiweb/is/icsresource/icsforms/';
  var AG_SPOKANE = 'https://ag7qp.com/spokane-county-ares-acs';   // launch blocker: roster PDF linked on this page (D14 #1)
  var GROUPS_FILES = 'https://spokaneares-acs.groups.io/g/main/files';
  var GROUPS_SRC = { label: 'groups.io, members', href: GROUPS_FILES };
  function src(label, href) { return { label: label, href: href }; }

  S.documents = [
    /* ---- Net operations */
    { id: 'net-scripts', title: 'Net scripts: weekly, simplex and GMRS', category: 'net-ops', format: 'DOCX', version: '2026-03-04; GMRS 2025-08-19',
      href: AG_SPOKANE, source: src(AG.label, AG_SPOKANE), hostedHere: true, mostUsed: true, verify: true,
      files: [   // split into three links once hosted here
        { id: 'net-preamble-weekly', label: 'weekly', version: '2026-03-04' },
        { id: 'net-preamble-simplex', label: 'simplex', version: '2026-03-04' },
        { id: 'net-preamble-gmrs', label: 'GMRS', version: '2025-08-19' }
      ],
      tags: ['preamble', 'script', 'net control', 'NCS', 'simplex', 'fifth Tuesday', 'GMRS'] },
    { id: 'ncs-principles', title: 'Principles of a Net Control Station', category: 'net-ops', format: null, version: null,
      href: null, source: null, tags: ['NCS', 'traffic', 'precedence'] },
    { id: 'repeater-etiquette', title: 'Repeater etiquette on W7GBU', category: 'net-ops', format: null, version: null,
      description: 'How to identify and break in.', href: null, source: null, verify: true, tags: ['repeater', 'etiquette'] },
    { id: 'net-roster', title: 'Net roster', category: 'net-ops', format: null, version: null,
      href: GROUPS_FILES, source: GROUPS_SRC, tags: ['roster', 'check-in'] },

    /* ---- Join & task books */
    { id: 'ares-application', title: 'Spokane County ARES Membership Application', category: 'join', format: 'DOCX', version: '2024-11-07',
      href: 'https://ag7qp.com/ares-acs', source: src(AG.label, 'https://ag7qp.com/ares-acs'), hostedHere: true, mostUsed: true, verify: true,
      tags: ['join', 'application', 'NDA'] },
    { id: 'county-application', title: 'Volunteer Emergency Worker Application (for ACS)', category: 'join', format: 'Online form', version: null,
      href: 'https://www.spokanecounty.gov/FormCenter/Emergency-Management-31/Volunteer-Emergency-Worker-Application-276',
      source: src('Spokane County', 'https://www.spokanecounty.gov/'), tags: ['ACS', 'county', 'background check', 'FormCenter 276'] },
    { id: 'acs-task-book', title: 'ACS Position Task Book', category: 'join', format: 'DOCX', version: '2024-08-09',
      description: 'Print it; keep sign-offs in a binder.',
      href: 'https://ag7qp.com/acs-task-book', source: src(AG.label, 'https://ag7qp.com/acs-task-book'), hostedHere: true, mostUsed: true, verify: true,
      tags: ['task book', 'PTB', 'RADO', 'S-RADO'] },
    { id: 'arrl-ares-task-book', title: 'ARRL ARES Position Task Book', category: 'join', format: 'PDF', version: 'July 2024',
      href: 'https://www.arrl.org/files/file/ARES%20Taskbook%20July%202024.pdf', source: src('ARRL', 'https://www.arrl.org/ares'), tags: ['ARRL', 'task book'] },
    { id: 'arrl-ares-plan', title: 'ARRL ARES Plan', category: 'join', format: null, version: null,
      href: 'https://www.arrl.org/ares', source: src('ARRL', 'https://www.arrl.org/ares'), verify: true, tags: ['ARRL', 'ARES'] },
    { id: 'orientation-guide', title: 'New Member Orientation guide', category: 'join', format: null, version: null,
      href: 'https://ag7qp.com/new-members-orientation', source: src(AG.label, 'https://ag7qp.com/new-members-orientation'), tags: ['orientation'] },

    /* ---- Forms (always the official source) */
    { id: 'ics-213', title: 'ICS 213 General Message', category: 'forms', format: 'PDF', version: 'v3',
      href: FEMA_FORMS, source: src('FEMA', FEMA_FORMS), mostUsed: true,
      howTo: { label: 'How to write one', href: 'https://ag7qp.com/ics-213' }, tags: ['ICS', 'message', '213'] },
    { id: 'ics-213rr', title: 'ICS 213RR Resource Request Message', category: 'forms', format: 'PDF', version: 'v3',
      href: FEMA_FORMS, source: src('FEMA', FEMA_FORMS), tags: ['ICS', 'resource request', '213RR'] },
    { id: 'ics-214', title: 'ICS 214 Activity Log', category: 'forms', format: 'PDF', version: 'v3.1',
      href: FEMA_FORMS, source: src('FEMA', FEMA_FORMS), tags: ['ICS', 'log', '214'] },
    { id: 'ics-309', title: 'ICS 309 Communications Log', category: 'forms', format: null, version: null,
      href: null, source: null, mostUsed: true, verify: true,      // no authoritative source chosen yet
      howTo: { label: 'Generate it in Winlink Express (video)', href: 'https://www.youtube.com/watch?v=VmVJpoPmQvc' }, tags: ['ICS', 'comm log', '309'] },
    { id: 'arrl-radiogram', title: 'ARRL Radiogram (fillable)', category: 'forms', format: 'PDF', version: null,
      description: 'National Traffic System message form.',
      href: 'https://www.arrl.org/files/media/Group/Fillable%20Radiogram%20Form.pdf', source: src('ARRL', 'https://www.arrl.org/'), mostUsed: true, tags: ['NTS', 'radiogram', 'traffic'] },
    { id: 'wa-ics-213rr', title: 'Washington State ICS-213RR', category: 'forms', format: 'PDF', version: null,
      description: 'Used in state exercises.',
      href: 'https://mil.wa.gov/asset/5ba420f505d91', source: src('WA Military Dept.', 'https://mil.wa.gov/'), verify: true, tags: ['state', '213RR'] },
    { id: 'wa-field-situation-report', title: 'Washington State Field Situation Report', category: 'forms', format: 'Winlink form', version: null,
      description: 'Winlink Express: Templates › Standard Forms › WA State Forms.',
      href: 'https://winlink.org/', source: src('Winlink', 'https://winlink.org/'), tags: ['Winlink', 'state', 'situation report', 'SOP 1b'] },
    { id: 'wsdot-580-020', title: 'WSDOT Form 580-020, Road and Bridge Assessment', category: 'forms', format: 'PDF', version: null,
      href: 'https://wsdot.wa.gov/publications/fulltext/forms/580-020.pdf', source: src('WSDOT', 'https://wsdot.wa.gov/'), tags: ['WSDOT', 'assessment'] },
    { id: 'fema-ics-forms', title: 'All FEMA ICS forms', category: 'forms', format: null, version: null,
      href: FEMA_FORMS, source: src('FEMA', FEMA_FORMS), tags: ['ICS', 'FEMA'] },

    /* ---- Training */
    { id: 'fema-is-courses', title: 'FEMA courses', category: 'training', format: 'Online course, free', version: null,
      href: null, source: src('FEMA', 'https://training.fema.gov/is/'),
      links: [
        { id: 'is-100', label: 'IS-100.c', title: 'Introduction to the Incident Command System', href: 'https://training.fema.gov/is/courseoverview.aspx?code=IS-100.c&lang=en' },
        { id: 'is-200', label: 'IS-200.c', title: 'Basic ICS for Initial Response', href: 'https://training.fema.gov/is/courseoverview.aspx?code=IS-200.c&lang=en' },
        { id: 'is-700', label: 'IS-700.b', title: 'An Introduction to NIMS', href: 'https://training.fema.gov/is/courseoverview.aspx?code=IS-700.b&lang=en' },
        { id: 'is-800', label: 'IS-800.d', title: 'National Response Framework, An Introduction', href: 'https://training.fema.gov/is/courseoverview.aspx?code=IS-800.d&lang=en' }
      ],
      sid: { id: 'fema-sid', label: 'Get a FEMA Student ID (SID) first', href: 'https://cdp.dhs.gov/femasid' },
      tags: ['FEMA', 'ICS', 'NIMS', 'NRF', 'IS-100', 'IS-200', 'IS-700', 'IS-800', 'SID', 'student ID'] },
    { id: 'arrl-basic-emcomm', title: 'ARRL Basic EmComm course', category: 'training', format: 'Online course', version: null,
      description: 'Three modules, about 10 to 20 hours.',
      href: 'https://learn.arrl.org/courses/67044', source: src('ARRL', 'https://learn.arrl.org/'), tags: ['ARRL', 'EmComm'] },
    { id: 'arrl-intermediate-emcomm', title: 'ARRL Intermediate EmComm course', category: 'training', format: 'Online course', version: null,
      description: '35-question final; 74% to pass.',
      href: 'https://learn.arrl.org/courses/68230', source: src('ARRL', 'https://learn.arrl.org/'), tags: ['ARRL', 'EmComm'] },
    { id: 'hipaa-video', title: 'HIPAA training video (12 minutes)', category: 'training', format: 'Video', version: null,
      description: 'If you missed it at orientation.',
      href: 'https://youtu.be/9ryH0P5MCdk', source: src('YouTube', 'https://youtu.be/9ryH0P5MCdk'), verify: true, tags: ['HIPAA'] },
    { id: 'skywarn-training', title: 'SKYWARN spotter training', category: 'training', format: null, version: null,
      description: 'In person or online. Encouraged every two years.',
      href: 'https://www.weather.gov/otx/Current_Spotter_Training', source: src('NWS Spokane', 'https://www.weather.gov/otx/'), tags: ['SKYWARN', 'weather'] },
    { id: 'ratpac', title: 'RATPAC video presentations', category: 'training', format: null, version: null,
      href: 'https://docs.google.com/spreadsheets/d/e/2PACX-1vTVywG1s025_zsAaKrn8Lr_DPGT6tGV0SVsV5LEdBA91dFCYk_LP8S3kXhMcD9vXFM3Cg-q468jf6Nd/pubhtml',
      source: src('RATPAC', 'https://www.ratpac.us/'), tags: ['video'] },
    { id: 'anderson-powerpoles', title: 'A guide to Anderson Powerpoles', category: 'training', format: 'PDF', version: null,
      description: 'Make your own DC power connections.',
      href: 'https://www.arrl.org/files/file/Public%20Service/TrainingModules/Technical/Anderson%20powerpole.pdf', source: src('ARRL', 'https://www.arrl.org/'), tags: ['power', 'Powerpole'] },

    /* ---- Go-kits & readiness */
    { id: 'go-kits', title: 'Go-kit guide', category: 'readiness', format: null, version: null,
      href: 'https://ag7qp.com/go-kits', source: src(AG.label, 'https://ag7qp.com/go-kits'), tags: ['go-kit', 'kit list'] },
    { id: 'alert-spokane', title: 'Register with ALERT Spokane', category: 'readiness', format: null, version: null,
      description: 'Now on Regroup; task book says “Code Red.”',
      href: 'https://www.spokanecounty.gov/3007/Alert-Spokane', source: src('Spokane County', 'https://www.spokanecounty.gov/3007/Alert-Spokane'), tags: ['alerts', 'Regroup'] },
    { id: 'programming-radios', title: 'Radio programming cheat sheets', category: 'readiness', format: null, version: null,
      href: 'https://ag7qp.com/programming-radios', source: src(AG.label, 'https://ag7qp.com/programming-radios'), tags: ['programming', 'HT'] },

    /* ---- Winlink & digital */
    { id: 'winlink-intro', title: 'Winlink: getting started', category: 'digital', format: null, version: null,
      description: 'Account setup, then Telnet, then radio.',
      href: 'https://ag7qp.com/winlink', source: src(AG.label, 'https://ag7qp.com/winlink'), tags: ['Winlink', 'setup'] },
    { id: 'winlink-express', title: 'Winlink Express (software)', category: 'digital', format: 'Software', version: null,
      description: 'Service code: PUBLIC EMCOMM.',
      href: 'https://winlink.org/WinlinkExpress', source: src('Winlink', 'https://winlink.org/'), tags: ['Winlink', 'software'] },
    { id: 'winlink-mapping', title: 'Winlink map-enabled forms (guide)', category: 'digital', format: 'PDF', version: null,
      href: 'https://winlink.org/sites/default/files/RMSE_FORMS/winlink_mapping_enabled_forms.pdf', source: src('Winlink', 'https://winlink.org/'), verify: true, tags: ['Winlink', 'mapping'] },
    { id: 'i-am-safe', title: '“I Am Safe” messaging: procedures', category: 'digital', format: 'DOCX', version: 'July 2023',
      description: 'Welfare messages from a disaster area.',
      href: 'https://ag7qp.com/ares-acs', source: src(AG.label, 'https://ag7qp.com/ares-acs'), verify: true, tags: ['welfare', 'Winlink'] },

    /* ---- Reference */
    { id: 'sop-1b', title: 'Eastern Washington Section Standing Order 1b', category: 'reference', format: 'PDF', version: '2026-01-16',
      description: 'What to do when you have no instructions.',
      href: 'https://ag7qp.com/ares-acs', source: src(AG.label, 'https://ag7qp.com/ares-acs'), mostUsed: true, tags: ['SOP', 'self-activation'] },
    { id: 'field-guides', title: 'AUXFOG and NIFOG field operations guides', category: 'reference', format: 'PDF or app', version: null,
      href: 'https://www.cisa.gov/safecom/field-operations-guides', source: src('CISA', 'https://www.cisa.gov/safecom/field-operations-guides'), tags: ['FOG', 'AUXFOG', 'NIFOG'] },
    { id: 'acsfog', title: 'Spokane County ACS Field Operating Guide (ACSFOG)', category: 'reference', format: null, version: null,
      href: GROUPS_FILES, source: GROUPS_SRC, mostUsed: true, tags: ['ACSFOG', 'FOG'] },
    { id: 'public-service-tips', title: 'Preparing for public-service communications', category: 'reference', format: null, version: null,
      description: 'Nine tips for your first event shift.',
      href: 'https://ag7qp.com/preparing-for-public-service-communications', source: src(AG.label, 'https://ag7qp.com/preparing-for-public-service-communications'), tags: ['events', 'public service'] }
  ];

  /* ------------------------------------------------------------------ links */
  // Only the links round-3 pages print (outside the document rows).
  S.links = {
    groupsio: { main: 'https://spokaneares-acs.groups.io/g/main', files: GROUPS_FILES },
    county: {
      application: 'https://www.spokanecounty.gov/FormCenter/Emergency-Management-31/Volunteer-Emergency-Worker-Application-276',
      alertSpokane: 'https://www.spokanecounty.gov/3007/Alert-Spokane'
    },
    arrl: {
      aresTaskBook: 'https://www.arrl.org/files/file/ARES%20Taskbook%20July%202024.pdf',
      set: 'https://www.arrl.org/simulated-emergency-test'
    },
    section: { sop1b: 'https://ag7qp.com/ares-acs', licenses: 'https://ag7qp.com/licenses' },
    licensing: {
      hamstudy: 'https://hamstudy.org/',
      glaarg: 'https://hamstudy.org/sessions/glaarg',
      wa7dre: 'http://wa7dre.org/testing/',
      kars: 'https://k7id.org/fcc-testing/'
    },
    law: {
      part97: 'https://www.ecfr.gov/current/title-47/chapter-I/subchapter-D/part-97',
      s97_407: 'https://www.ecfr.gov/current/title-47/chapter-I/subchapter-D/part-97/subpart-E/section-97.407',
      rcw3852: 'https://app.leg.wa.gov/rcw/default.aspx?cite=38.52',
      wac11804: 'https://app.leg.wa.gov/wac/default.aspx?cite=118-04'    // verify
    },
    // Proposed role addresses (decisions.md D11). NOT LIVE. Render with data-verify="true".
    contact: {
      join: 'mailto:join@spokares.org',
      ec: 'mailto:ec@spokares.org',
      webmaster: 'mailto:webmaster@spokares.org',
      verify: true
    }
  };

  /* ------------------------------------------------------------ leadership */
  // about.html#leadership. Role labels as shown; every row data-verify="true".
  S.roles = [
    { role: 'Emergency Coordinator; website admin', callsign: 'W7TSC', verify: true },
    { role: 'AEC, Public Events; Winlink coordinator', callsign: 'NV2Z', verify: true },
    { role: 'AEC, Hospital Coordinator', callsign: 'K7MHG', verify: true },
    { role: 'AEC, Logistics & Comms; W7GBU license trustee', callsign: 'K7DSR', verify: true },
    { role: 'AEC, Net Manager', callsign: 'AG7QP', verify: true },
    { role: 'Public Information Officer', callsign: null, open: true, verify: true },
    { role: 'SKYWARN representatives', callsign: 'AA7RT, W7UWC', verify: true }
  ];
  S.sectionRoles = [
    { role: 'ARRL Section Manager', callsign: 'KA7LJQ', verify: true },
    { role: 'Section Emergency Coordinator', callsign: 'AG7QP', verify: true },
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
  /* The frozen mockup date, unless ?today=YYYY-MM-DD is in the URL. */
  fn.today = function () {
    try {
      var m = /[?&]today=(\d{4}-\d{2}-\d{2})/.exec(root.location && root.location.search || '');
      if (m) return m[1];
    } catch (e) { /* ignore */ }
    return S.meta.asOf;
  };
  /* style: 'short' → "Tue, Sep 29"; 'long' → "Tuesday, September 29, 2026"; 'day' → "Sep 29"; 'dm' → {dow, day, mon, year} */
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

  /* The weekly net on a given Tuesday: variant (for the rota Note) and net control. */
  fn.netOn = function (isoDate) {
    var d = parse(isoDate);
    if (d.getDay() !== 2) return null;
    var rota = fn.rotaFor(isoDate);
    var simplex = matchesRule(find(S.nets, 'id', 'simplex-5th').rule, d);
    var winlink = matchesRule(find(S.nets, 'id', 'winlink-nights').rule, d);
    var gmrs = matchesRule(find(S.nets, 'id', 'gmrs').rule, d);
    return {
      date: isoDate, time: '20:00', type: 'net', id: 'net-' + isoDate,
      title: 'Tuesday net',
      mode: simplex ? 'simplex' : (winlink ? 'winlink' : 'repeater'),
      note: simplex ? 'Simplex' : (winlink ? 'Winlink night' : (gmrs ? 'GMRS net, 7:30 PM' : '')),
      where: simplex ? find(S.nets, 'id', 'simplex-5th').where : S.nets[0].where,
      netControl: rota ? (rota.open ? null : rota.callsign) : undefined,   // undefined = not published yet
      open: !!(rota && rota.open),
      winlinkAssignment: winlink ? fn.winlinkFor(isoDate) : null,
      gmrsSameDay: gmrs,
      verify: simplex || winlink || gmrs
    };
  };

  /* Next weekly net on or after `from` (default today). */
  fn.nextNet = function (from) {
    var d = parse(from || fn.today());
    for (var i = 0; i < 8; i++) { var x = addDays(d, i); if (x.getDay() === 2) return fn.netOn(iso(x)); }
    return null;
  };

  /* Next n dates for each meeting, recomputed from its rule. */
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
      out.push({ id: mt.id, name: mt.name, dates: dates, start: mt.start, end: mt.end, displayTime: mt.displayTime || null, verify: !!mt.verify });
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
      if (want('gmrs') && matchesRule(find(S.nets, 'id', 'gmrs').rule, x)) items.push({ date: xi, time: '19:30', type: 'gmrs', id: 'gmrs-' + xi, title: 'ACS GMRS net', verify: true });
      if (want('meeting')) {
        for (var m = 0; m < S.meetings.length; m++) {
          var mt = S.meetings[m];
          if (!mt.rule) continue;
          if (mt.skipMonths && mt.skipMonths.indexOf(x.getMonth() + 1) !== -1) continue;
          if (matchesRule(mt.rule, x)) items.push({ date: xi, time: mt.start, endTime: mt.end, displayTime: mt.displayTime || null, sortTime: mt.start || (mt.displayTime === 'Evening' ? '18:00' : null), type: 'meeting', id: mt.id + '-' + xi, title: mt.name, verify: !!mt.verify });
        }
      }
    }
    var endIso = iso(addDays(from, days - 1)), fromIso = iso(from);
    for (var e = 0; e < S.events.length; e++) {
      var ev = S.events[e], evEnd = ev.end || ev.start;
      if (!want(ev.type)) continue;
      if (evEnd >= fromIso && ev.start <= endIso) {
        items.push({ date: ev.start, endDate: ev.end || null, time: ev.time || null, endTime: ev.endTime || null, type: ev.type, id: ev.id, title: ev.title, summary: ev.summary || null, inProgress: ev.start < fromIso || (ev.start === fromIso && !!ev.end), verify: !!ev.verify });
      }
    }
    function key(x) { return x.sortTime || x.time || '00:00'; }
    items.sort(function (a, b) { return a.date === b.date ? key(a).localeCompare(key(b)) : (a.date < b.date ? -1 : 1); });
    return opts.limit ? items.slice(0, opts.limit) : items;
  };

  /* Document search/filter. q matches title, description, tags, format, version, id and sub-link labels. */
  fn.documents = function (opts) {
    opts = opts || {};
    var q = (opts.q || '').toLowerCase().replace(/^\s+|\s+$/g, ''), out = [];
    for (var i = 0; i < S.documents.length; i++) {
      var d = S.documents[i];
      if (opts.category && d.category !== opts.category) continue;
      if (opts.mostUsed && !d.mostUsed) continue;
      if (q) {
        var extra = [];
        var subs = (d.links || []).concat(d.files || []);
        for (var s = 0; s < subs.length; s++) extra.push(subs[s].label, subs[s].title, subs[s].id);
        var hay = [d.title, d.description, d.version, d.id, d.format, (d.tags || []).join(' '), extra.join(' ')].join(' ').toLowerCase();
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
