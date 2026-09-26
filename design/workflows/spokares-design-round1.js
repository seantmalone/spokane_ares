export const meta = {
  name: 'spokares-design-concepts',
  description: 'Generate 12 highly varied homepage design concepts for the spokares.org rebuild, judge-panel rank them, build a gallery',
  phases: [
    { title: 'Brief', detail: 'inspiration survey + real-content brief' },
    { title: 'Direct', detail: 'art director defines 12 divergent concepts' },
    { title: 'Build', detail: 'one frontend-design builder per concept' },
    { title: 'Review', detail: '4 judges with distinct lenses score all concepts' },
    { title: 'Present', detail: 'ranked gallery page' },
  ],
}

const P = '/Users/sean/Projects/spokane_ares'
const D = `${P}/design`
const TODAY = args.today
const CHROME = `"/Applications/Google Chrome.app/Contents/MacOS/Google Chrome" --headless=new --disable-gpu --hide-scrollbars --virtual-time-budget=6000`
const GOAL = `GOAL: concepts for a complete redesign of https://www.spokares.org/ — Spokane County ARES/RACES (Amateur Radio Emergency Service / Radio Amateur Civil Emergency Service; the group also brands as "ARES - ACS", Auxiliary Communications Service), a volunteer amateur-radio emergency-communications group that supports Spokane County Emergency Management and served agencies. The desired feel: PROFESSIONAL, ENGAGING, EASY TO USE, MOTIVATING visitors to LEARN MORE and then SIGN UP (volunteer). Audiences: (1) licensed hams curious about emergency comms, (2) unlicensed people who might get licensed to serve, (3) current members needing nets/schedules/forms fast, (4) served-agency partners judging credibility. Do NOT be bound by the current site's look or structure.`

phase('Brief')
const [insp, brief] = await parallel([
  () => agent(`${GOAL}\n\nTASK: Survey the websites of similar organizations for design inspiration. Visit at least 15 real sites (use ToolSearch "select:WebSearch,WebFetch" and/or curl; you may take headless screenshots with: ${CHROME} --screenshot=<abs path>.png --window-size=1440,1000 "<url>" and view them with the Read tool — save them under ${D}/inspiration/). Cover a spread: county/city ARES, ACS and RACES groups known for good sites (e.g. in WA: Snohomish County ACS/DEM, King County, Pierce County ARES, Clark County ARES; elsewhere: Santa Clara County ARES/RACES, San Francisco ACS, Orange County RACES, Montgomery County ARES, etc.), ARRL/ARES national pages, and adjacent volunteer-recruitment orgs with strong design (CERT programs, search & rescue teams, volunteer fire departments, Red Cross volunteer pages, Team Rubicon, NWS SKYWARN, civil air patrol, mountain rescue). For each site note: URL, what works/doesn't (hero messaging, recruitment funnel, how they explain ARES/RACES, nets/calendar presentation, credibility signals, photography, typography/colour), and one idea worth stealing. Then synthesise cross-cutting patterns and anti-patterns (most ham sites are dated/cluttered — what the best ones do differently). WRITE ${D}/inspiration.md. Return a compact summary (<=40 lines) of the most useful patterns and the 8 best reference URLs with one-line reasons.`,
    { label: 'inspiration-survey', phase: 'Brief' }),
  () => agent(`${GOAL}\n\nTASK: Build an accurate CONTENT BRIEF for design mockups from our crawl of the current site, so every mockup uses REAL content (no invented names, frequencies, dates or claims). Sources (read them): ${P}/research/raw/_home.html (homepage), ${P}/research/raw/by-id/article-N.html for N in 3 (leadership/staff), 4 (about), 5 (W7GBU call sign history), 20 (status codes / alert levels), 26 (how to join), 27 (meeting location), 37 (SKYWARN), 41 (welcome letter & member requirements), 42 (amateur radio training), 11 (license test sessions), 68 (MOU with IEVHFRA), 23 (email list); ${P}/research/data/calendar-events.json (weekly net controllers & meetings — derive the regular net/meeting pattern: day of week, time, location; note most recent entries are Sept 2026); ${P}/research/site-functionality-analysis.md and ${P}/research/hosting/verpex-enhance.md if they exist. Extract main content from the Joomla HTML (the <div class="item-page"> area).\n\nWRITE ${D}/content-brief.md containing: org names/acronyms and a plain-English one-paragraph explanation of ARES vs RACES vs ACS; mission/value proposition lines (quote existing ones, then propose 5 sharper headline options clearly marked as proposals); what members do (activities, deployments, exercises, SKYWARN, digital messaging, nets); the regular weekly net (day/time/mode as evidenced) and meeting location (address); the join process steps and requirements (license, county volunteer emergency worker registration link https://www.spokanecounty.org/FormCenter/Emergency-Management-31/Volunteer-Emergency-Worker-Application-276, groups.io list https://spokaneares-acs.groups.io/, training expectations like FEMA IS courses); training path for unlicensed people; partner/served-agency names seen on the site; leadership roles (roles only + callsigns if shown — mark as "verify before publishing"); key resources (forms, net scripts, ICS); proposed navigation for a modern site; and a list of facts NOT to invent. Also COPY useful image assets into ${D}/assets/ (mkdir -p): logos from ${P}/research/raw/www.spokares.org/images/logos/ and photos from ${P}/research/raw/www.spokares.org/images/HomePageSlider/ (also ${P}/research/raw/www.spokares.org/images/DEM_map.png) — keep original filenames; view each photo with the Read tool and add an "Assets" section describing each file (subject, orientation, quality, best use). Return a compact summary (<=40 lines): key facts, headline options, and the asset list with one-line descriptions.`,
    { label: 'content-brief', phase: 'Brief' }),
])

phase('Direct')
const SPEC = {
  type: 'object',
  properties: {
    concepts: {
      type: 'array',
      minItems: 12,
      items: {
        type: 'object',
        properties: {
          num: { type: 'number' },
          slug: { type: 'string', description: 'kebab-case, unique' },
          name: { type: 'string', description: 'evocative concept name' },
          thesis: { type: 'string', description: 'the one idea this concept bets on to make people learn more & sign up' },
          aesthetic: { type: 'string', description: 'visual direction: mood, colour palette (hex), typography pairing (Google Fonts), imagery treatment, texture' },
          layout: { type: 'string', description: 'layout paradigm & homepage section order; distinctive structural moves' },
          signup_funnel: { type: 'string', description: 'how the page moves a visitor from curiosity to sign-up (CTA placement, steps, proof)' },
          member_utility: { type: 'string', description: 'how members find nets/schedule/forms fast' },
          signature_interaction: { type: 'string', description: 'one memorable interaction or component (e.g. live net-status strip, interactive path quiz, map)' },
          references: { type: 'array', items: { type: 'string' }, description: 'inspiration URLs this draws on' },
          risk: { type: 'string', description: 'the main way this concept could fail' },
        },
        required: ['num', 'slug', 'name', 'thesis', 'aesthetic', 'layout', 'signup_funnel', 'member_utility', 'signature_interaction', 'references', 'risk'],
      },
    },
    variability_check: { type: 'string', description: 'why these 12 are genuinely different from each other (axes covered)' },
  },
  required: ['concepts', 'variability_check'],
}
const direction = await agent(`${GOAL}\n\nYou are the ART DIRECTOR. Define 12 HIGHLY VARIABLE homepage concepts. They must differ on multiple axes at once — not 12 colour swaps of one layout. Spread across e.g.: tone (civic-institutional, editorial magazine, bold athletic recruitment, warm community, tactical ops-center/dark, calm Swiss minimal, documentary photo-journalism, retro-radio/QSL-card heritage, illustrated/playful, data-forward live dashboard, map-first geographic, story/scrollytelling), layout paradigm (full-bleed hero, split-screen, bento grid, long-form scroll narrative, sidebar utility, card mosaic, single-column editorial, dashboard), colour (light, dark, high-contrast, muted, saturated), type (serif/sans/mono/display), and funnel strategy (quiz/pathfinder, stepper, testimonials-led, mission-led, urgency-led, community-led). Every concept must still feel PROFESSIONAL and credible to an emergency-management agency, be EASY TO USE, and drive LEARN MORE -> SIGN UP; at least 2 should lean hard into member utility (nets/schedules) while still recruiting. Name each concept's inspiration references.\n\nInspiration survey summary:\n${insp}\n\nContent brief summary (full brief at ${D}/content-brief.md; read it):\n${brief}\n\nAlso write the 12 specs to ${D}/concept-specs.md for the record. Return the structured specs (num 1..12).`,
  { label: 'art-director', phase: 'Direct', schema: SPEC })

log(`Art director defined ${direction.concepts.length} concepts`)

phase('Build')
const BUILD = {
  type: 'object',
  properties: {
    slug: { type: 'string' },
    dir: { type: 'string' },
    built: { type: 'boolean' },
    screenshots: { type: 'array', items: { type: 'string' } },
    self_review: { type: 'string', description: 'what you checked in the screenshots and what you fixed' },
    skill_used: { type: 'boolean', description: 'true if you invoked the frontend-design skill' },
  },
  required: ['slug', 'dir', 'built', 'screenshots', 'self_review', 'skill_used'],
}
const pad = n => String(n).padStart(2, '0')
const builds = await pipeline(direction.concepts,
  c => {
    const dir = `${D}/concepts/${pad(c.num)}-${c.slug}`
    return agent(`${GOAL}\n\nFIRST, invoke the Skill tool with skill "frontend-design:frontend-design" and follow its guidance for this build (if the Skill tool is unavailable, say so in self_review and proceed with the same high bar).\n\nBuild ONE high-fidelity homepage mockup for concept #${c.num}:\n${JSON.stringify(c, null, 1)}\n\nREAL CONTENT: read ${D}/content-brief.md and use its real facts, names of programs, net/meeting info, join steps and links. Never invent names, callsigns, frequencies, dates, statistics or testimonials; if the design needs one (e.g. a testimonial slot or a stat), use obviously-placeholder text like "[Member quote — to be collected]" or "[stat TBD]". Real photos/logos are in ${D}/assets/ (see the Assets section of the brief) — reference them with relative paths (../../assets/<file>); you may also use CSS/SVG illustration, gradients, patterns. Do not hotlink images from other sites.\n\nDELIVERABLE: ${dir}/index.html — a single self-contained static page (inline CSS/JS; Google Fonts allowed; no build step, no frameworks requiring a server — it must be deployable as static files on PHP/Apache-style shared hosting). Include at least: header/nav, hero with value proposition + primary "Join / Volunteer" CTA and secondary "Learn more" CTA, a clear explainer of what ARES/RACES/ACS do (plain English), a path/steps to join (including for people not yet licensed), member utility (next weekly net / meetings / quick links to forms & net scripts), credibility (served agencies/partners, affiliation with ARRL ARES & Spokane County Emergency Management), and a footer. Make the concept's signature interaction actually work. Fully responsive (test 390px and 1440px). Accessible: semantic landmarks, visible focus, WCAG AA contrast, alt text, respects prefers-reduced-motion. Put a small unobtrusive label somewhere (e.g. footer) "Concept ${pad(c.num)} — ${c.name}".\n\nSELF-REVIEW: render screenshots with headless Chrome and LOOK at them with the Read tool:\n  ${CHROME} --screenshot=${dir}/shot-desktop-fold.png --window-size=1440,900 "file://${dir}/index.html"\n  ${CHROME} --screenshot=${dir}/shot-desktop-full.png --window-size=1440,4200 "file://${dir}/index.html"\n  ${CHROME} --screenshot=${dir}/shot-mobile.png --window-size=390,3000 "file://${dir}/index.html"\n(ignore Chrome's task_policy_set stderr noise). Fix anything broken, cramped, low-contrast, overflowing, or generic-looking, then re-shoot. Make sure it looks like the concept spec and is distinct — not a default template. Return the structured result.`,
      { label: `build:${pad(c.num)}-${c.slug}`, phase: 'Build', schema: BUILD })
  },
)
const ok = direction.concepts.map((c, i) => ({ c, b: builds[i] })).filter(x => x.b && x.b.built)
log(`${ok.length}/${direction.concepts.length} concepts built`)
if (ok.length < direction.concepts.length) log(`Not built: ${direction.concepts.filter((c, i) => !(builds[i] && builds[i].built)).map(c => c.slug).join(', ')}`)

phase('Review')
const JUDGES = [
  { key: 'prospect', lens: 'You are a PROSPECTIVE VOLUNTEER: a newly licensed ham (and, alternately, an unlicensed but civic-minded Spokane resident). Judge: does this make me want to learn more, do I understand what I would actually do, is the path to sign up obvious and low-friction, does it feel welcoming and worth my time?' },
  { key: 'agency', lens: 'You are a SERVED-AGENCY PARTNER (Spokane County emergency manager / hospital emergency coordinator / Red Cross liaison). Judge: professionalism, credibility and trustworthiness, clarity of capability, would I be comfortable linking to this from an official county page, does anything feel amateurish or off-brand for emergency management?' },
  { key: 'ux', lens: 'You are a senior UX + ACCESSIBILITY reviewer who also thinks about MAINTAINABILITY by non-developer volunteers on shared PHP hosting. Judge: navigation clarity, information scent, mobile experience, readability/contrast, member task speed (find this week\'s net, a form, the meeting place), and how hard this design would be to implement and keep updated.' },
  { key: 'art', lens: 'You are an ART DIRECTOR / brand designer. Judge: visual craft, distinctiveness vs generic templates, typography, hierarchy, use of photography, coherence of the concept, and how memorable and engaging it is — while staying appropriate for an emergency-services volunteer organization.' },
]
const SCORES = {
  type: 'object',
  properties: {
    scores: {
      type: 'array',
      items: {
        type: 'object',
        properties: {
          slug: { type: 'string' },
          professional: { type: 'number', description: '1-10' },
          engaging: { type: 'number', description: '1-10' },
          ease_of_use: { type: 'number', description: '1-10' },
          learn_more: { type: 'number', description: '1-10 motivates learning more' },
          sign_up: { type: 'number', description: '1-10 motivates signing up' },
          overall: { type: 'number', description: '1-10 holistic, through your lens' },
          strengths: { type: 'string' },
          weaknesses: { type: 'string' },
        },
        required: ['slug', 'professional', 'engaging', 'ease_of_use', 'learn_more', 'sign_up', 'overall', 'strengths', 'weaknesses'],
      },
    },
    top_pick: { type: 'string' },
    notes: { type: 'string' },
  },
  required: ['scores', 'top_pick'],
}
const listing = ok.map(({ c, b }) => `- ${c.slug} ("${c.name}"): thesis: ${c.thesis}\n  files: ${b.dir}/index.html ; screenshots: ${b.dir}/shot-desktop-fold.png, ${b.dir}/shot-desktop-full.png, ${b.dir}/shot-mobile.png`).join('\n')
const verdicts = await parallel(JUDGES.map(j => () =>
  agent(`${GOAL}\n\n${j.lens}\n\nReview ALL of these ${ok.length} concepts side by side. For each, LOOK at all three screenshots with the Read tool (and skim index.html where useful, e.g. to check a signature interaction or mobile nav). Score every concept 1-10 on each criterion, calibrated ACROSS the set (use the full range; don't give everything 7-8). Be specific in strengths/weaknesses.\n\n${listing}`,
    { label: `judge:${j.key}`, phase: 'Review', schema: SCORES })))

const valid = verdicts.filter(Boolean)
const CRIT = ['professional', 'engaging', 'ease_of_use', 'learn_more', 'sign_up', 'overall']
const agg = ok.map(({ c, b }) => {
  const rows = valid.map((v, ji) => ({ judge: JUDGES[ji].key, s: v.scores.find(s => s.slug === c.slug) })).filter(r => r.s)
  const mean = k => rows.length ? rows.reduce((a, r) => a + (r.s[k] || 0), 0) / rows.length : 0
  const crit = Object.fromEntries(CRIT.map(k => [k, Math.round(mean(k) * 10) / 10]))
  const composite = Math.round((CRIT.reduce((a, k) => a + mean(k), 0) / CRIT.length) * 100) / 100
  return { num: c.num, slug: c.slug, name: c.name, thesis: c.thesis, dir: b.dir, crit, composite,
    judges: rows.map(r => ({ judge: r.judge, overall: r.s.overall, strengths: r.s.strengths, weaknesses: r.s.weaknesses })),
    top_picks: valid.filter(v => v.top_pick === c.slug).length }
}).sort((a, b) => b.composite - a.composite || b.top_picks - a.top_picks)
agg.forEach((a, i) => { a.rank = i + 1 })
log(`Ranking: ${agg.map(a => `${a.rank}. ${a.slug} (${a.composite})`).join(' | ')}`)

phase('Present')
const gallery = await agent(`${GOAL}\n\nBuild the REVIEW GALLERY for the owner to browse locally in a browser. Ranked results (JSON, already computed — do not re-rank):\n${JSON.stringify(agg)}\n\nJudge top picks & notes:\n${JSON.stringify(valid.map((v, i) => ({ judge: JUDGES[i].key, top_pick: v.top_pick, notes: v.notes })))}\n\nConcept specs: ${D}/concept-specs.md\n\nWRITE ${D}/concepts/index.html: a polished, restrained gallery page (neutral design so it doesn't compete with the concepts; light/dark aware). Top: short intro (what this is, the 4 judge lenses, scoring method = mean of 6 criteria across 4 judges). Then a ranked list: for each concept — rank, name, composite score, per-criterion mini-bars, thesis, a large clickable desktop-fold screenshot and a mobile screenshot thumbnail (relative paths like ${'`'}NN-slug/shot-desktop-fold.png${'`'}), an "Open full concept" link to NN-slug/index.html (open in new tab), and a collapsible "Judges' notes" with each judge's strengths/weaknesses. Include a compact comparison table of all concepts x criteria near the top. Use relative links only (it will be opened via file://). Then also WRITE ${D}/REVIEW.md with the same ranking, scores table, per-concept summary of judge feedback, and a closing "Recommendation" section: which 2-3 directions to carry forward and which specific elements from lower-ranked concepts are worth grafting in. Verify the gallery renders: ${CHROME} --screenshot=${D}/concepts/_gallery.png --window-size=1440,2400 "file://${D}/concepts/index.html" and look at it with Read; fix issues. Return a <=25 line summary: the ranking with one-line reason each, and the recommendation.`,
  { label: 'gallery', phase: 'Present' })

return { ranking: agg.map(a => ({ rank: a.rank, slug: a.slug, name: a.name, composite: a.composite, crit: a.crit, top_picks: a.top_picks })), gallery: `${D}/concepts/index.html`, summary: gallery, judges_ok: valid.length }
