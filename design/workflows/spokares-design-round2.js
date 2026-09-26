export const meta = {
  name: 'spokares-design-round2',
  description: 'Round 2: three multi-page site options (Home, How it works, About ARES & ACS, For Members) led by the round-1 leaders, judged and presented',
  phases: [
    { title: 'Prepare', detail: 'shared IA + real content + data file; per-option feedback briefs' },
    { title: 'Build', detail: 'per option: lead (design system + Home) -> 3 parallel page builders -> integrator' },
    { title: 'Review', detail: '5 judges incl. current-member task tests' },
    { title: 'Present', detail: 'round-2 gallery + REVIEW' },
  ],
}

const P = '/Users/sean/Projects/spokane_ares'
const D = `${P}/design`
const R2 = `${D}/round2`
const TODAY = args.today
const CHROME = `"/Applications/Google Chrome.app/Contents/MacOS/Google Chrome" --headless=new --disable-gpu --hide-scrollbars --virtual-time-budget=6000`

const GOAL = `PROJECT: redesign of https://www.spokares.org/ for Spokane County ARES-ACS (Amateur Radio Emergency Service + Spokane County Auxiliary Communications Service, which absorbed RACES) — volunteer amateur-radio emergency communications supporting Spokane County Emergency Management (SCEM) and served agencies. Desired feel: PROFESSIONAL, ENGAGING, EASY TO USE, motivating visitors to LEARN MORE and then SIGN UP; members must find what they need FAST. Today is ${TODAY}.

REQUIRED SITE STRUCTURE (owner's direction) — four main sections:
1. HOME (index.html): the newcomer's first step. High-level information; its main purpose is to draw people in, with clear calls to action (join / learn more / visit).
2. HOW IT WORKS (how-it-works.html): more detail on the organization, operations, and what members actually do (activations, nets, message handling, exercises, public-service events, roles).
3. ABOUT ARES & ACS (about.html): a longer, TEXT-HEAVY page for people who want all the details — still well formatted, easy-to-read structure, with a table of contents (sticky/side TOC on desktop, collapsible on mobile), clear headings, short paragraphs, tables/callouts where they help.
4. FOR MEMBERS (members/index.html + members/documents.html + members/exercises.html): public information that mostly matters to current members. Lots of resources, so it gets ITS OWN SECONDARY MENU (members sub-nav listing ALL members sub-sections, including ones not mocked up — link those to "#"), with a focus on clean layout and NAVIGATION EFFICIENCY (search/filter, quick links, "this week" at a glance). No login.

CONSTRAINTS: static HTML/CSS/JS mockups (no build step, no frameworks needing a server) that must work when opened via file:// — so NO fetch() of local JSON; shared data comes from assets/data.js which sets window.SPOKARES. Relative links only. Patterns must be implementable later in either WordPress (block theme) or a static site generator — so keep JS as progressive enhancement, avoid app-like SPA behaviour. Responsive (390px and 1440px), accessible (landmarks, skip link, visible focus, WCAG AA contrast, alt text, prefers-reduced-motion). Google Fonts allowed. Logos: any of the real logos may be used freely (ARES, ACS, SCEM, Spokane County ARES-ACS) — it's a mockup to be validated before launch. Imagery: use the real photos where they hold up, plus diagrams/illustration; where a photo is needed but missing, use a clearly marked frame with a short shot-list caption (e.g. "PHOTO NEEDED: members at a hospital drill, wide, landscape").
CONTENT RULES: use the real content prepared in ${R2}/_shared/ (never invent names, callsigns, frequencies, dates, statistics, testimonials). Uncertain facts carry data-verify="true" on their element (no visible TBD clutter); add one small footer line: "Mockup — content to be verified before launch". Do NOT show any public "alert condition" / "Condition Green" status widget. No personal phone numbers or personal emails anywhere.`

phase('Prepare')
const OPTIONS = [
  { key: 'a', slug: 'option-a-field-guide', leader: 'Field Guide', r1dir: `${D}/concepts/09-field-guide` },
  { key: 'b', slug: 'option-b-lines-down', leader: 'When the Lines Go Down', r1dir: `${D}/concepts/12-lines-down` },
  { key: 'c', slug: 'option-c-open-door', leader: 'Open Door', r1dir: `${D}/concepts/04-open-door` },
]

const BRIEFS = {
  type: 'object',
  properties: {
    cross_cutting: { type: 'array', items: { type: 'string' }, description: 'fixes every option must apply (from judges across round 1)' },
    options: {
      type: 'array',
      items: {
        type: 'object',
        properties: {
          key: { type: 'string', enum: ['a', 'b', 'c'] },
          name: { type: 'string', description: 'a fresh name for the round-2 option' },
          keep: { type: 'array', items: { type: 'string' }, description: 'what the judges loved and must survive, specific (components, type, palette hex, illustration style, copy)' },
          fix: { type: 'array', items: { type: 'string' }, description: 'each specific judge criticism of the leader and how to fix it' },
          grafts: { type: 'array', items: { type: 'string' }, description: 'elements to borrow from other round-1 concepts (name the concept + element + where it goes in this option)' },
          section_approach: { type: 'object', properties: { home: { type: 'string' }, how_it_works: { type: 'string' }, about: { type: 'string' }, members: { type: 'string' } }, required: ['home', 'how_it_works', 'about', 'members'] },
          design_tokens: { type: 'string', description: 'palette (hex), type pairing, spacing/radius, illustration & photo treatment, motion' },
        },
        required: ['key', 'name', 'keep', 'fix', 'grafts', 'section_approach', 'design_tokens'],
      },
    },
  },
  required: ['cross_cutting', 'options'],
}

const [contentReport, briefs] = await parallel([
  () => agent(`${GOAL}\n\nTASK: prepare the SHARED information architecture, real page content and data that all three design options will use, so the options differ in design, not facts. Sources (read them): ${P}/research/README.md (start here, incl. "Known gaps"), ${P}/research/organization.md, ${P}/research/decisions.md (esp. D15 page inventory, D13 facts, "never publish" rules), ${P}/research/data/facts.yaml, ${P}/research/documents-inventory.md, ${P}/research/content/interim-ag7qp/*.md (current source of truth for nets/meetings/events/docs), ${P}/research/content/live/*.md, ${D}/content-brief.md, ${D}/REVIEW.md.\n\nWRITE into ${R2}/_shared/ (mkdir -p):\n1. ia.md — the site map: primary nav (Home, How it works, About ARES & ACS, For Members, plus a persistent Join CTA), footer nav, and the FULL members sub-menu (e.g. This week & nets, Net control rota, Documents & downloads, Exercises & events, Training & task books, Comms plan & frequencies, Forms, Contacts & roles, groups.io — decide the best set from the evidence); which items are mocked (members/index, documents, exercises) vs linked to "#".\n2. content-home.md, content-how-it-works.md, content-about.md, content-members.md, content-members-documents.md, content-members-exercises.md — publication-ready DRAFT COPY for each page: headings, body, CTAs, captions, microcopy; real facts only; mark uncertain facts with [verify]. content-about.md must be genuinely long and complete (what ARES is, what ACS/RACES is and why both exist in Spokane, the legal basis in plain English, who we serve, structure & roles, membership levels and requirements for each path, training path & task books, equipment expectations, how activations work, FAQ) — organised for a TOC. How-it-works: operations — activation flow, nets (weekly net, simplex/GMRS if evidenced), message handling (ICS-213, Winlink), exercises (SET, WSDOT exercise), public service events, roles members take. Members pages: document library entries from documents-inventory.md (title, category, format, updated date, source/authoritative link, status; standard FEMA/ICS forms link to official sources), exercises & events (upcoming from the interim content: e.g. the Sep 26-27 2026 WSDOT exercise, SET Oct 3-4 2026, meetings, net rota) and past exercises worth listing.\n3. assets/data.js — setting window.SPOKARES = { nets: [...], rota: [...upcoming net-control assignments with date & callsign as published on ag7qp...], meetings: [...recurring with rule + next dates computed from ${TODAY}...], events: [...upcoming exercises/events...], documents: [...library entries...], links: {...groups.io, county application, ARRL...} } with a comment header saying it is the single source for "this week"/rota/events widgets and would be replaced by CMS data later. Personal names are OK only as published callsign + name on the rota; no phones/emails.\n4. Copy ALL image assets from ${D}/assets/ into ${R2}/_shared/assets/img/ and write _shared/assets/README.md listing each image with subject/orientation/quality (view them with Read) and a shot list of MISSING photos the site needs.\nReturn a <=25 line summary: IA, members sub-menu, key facts used, and anything flagged [verify].`,
    { label: 'content-ia', phase: 'Prepare' }),
  () => agent(`${GOAL}\n\nTASK: turn the round-1 judge feedback into three concrete ROUND-2 BRIEFS. Read ${D}/REVIEW.md (rankings, per-concept judge strengths/weaknesses, recommendation & grafts), ${D}/concept-specs.md, ${D}/inspiration.md, and LOOK at the three leaders (open their index.html source and their screenshots shot-*.png with the Read tool): ${OPTIONS.map(o => `${o.leader} = ${o.r1dir}`).join('; ')}. Also skim the other concepts in ${D}/concepts/ that REVIEW.md recommends grafting from (County Standard's ARES vs ACS table & agency door, Hi-Vis's "Here's what it takes"/"Pick your post", Plain Signal's 0-3 join path with ?from= link, a light static Coverage map + interest form, Tuesday 2000's self-updating next-net line & "Copy radio settings", The Relay's tap-to-define glossary, Ops Board's member portal) — open their files to capture exactly what made them work.\n\nOption A is led by Field Guide, B by When the Lines Go Down, C by Open Door. Each option must keep its leader's distinct personality (so the three remain clearly different), fix every specific criticism the judges made of that leader (e.g. Field Guide too soft for agencies; Lines Down's join path ~6000px down & ease-of-use 6.0; Open Door's peach card & weekly-maintenance burden -> drive "this week" from data.js), and add the best grafts — while now spanning FOUR sections (Home, How it works, About ARES & ACS long-form with TOC, For Members hub + documents + exercises with its own sub-menu). Say how each option expresses each section (e.g. B's scrollytelling story belongs mostly on How it works; Home must get to the CTA fast). Keep design tokens concrete. Also list cross-cutting fixes all options must apply (remove Condition Green status, no visible TBD clutter, etc.). Write the briefs to ${R2}/briefs.md as well. Return the structured briefs.`,
    { label: 'feedback-briefs', phase: 'Prepare', schema: BRIEFS }),
])

log(`Shared content ready; briefs for ${briefs.options.length} options`)

phase('Build')
const BUILT = {
  type: 'object',
  properties: {
    pages: { type: 'array', items: { type: 'string' } },
    screenshots: { type: 'array', items: { type: 'string' } },
    notes: { type: 'string' },
    skill_used: { type: 'boolean' },
  },
  required: ['pages', 'screenshots', 'notes', 'skill_used'],
}
const shoot = (dir, page, name) => `  ${CHROME} --screenshot=${dir}/shots/${name}-desktop-fold.png --window-size=1440,900 "file://${dir}/${page}"\n  ${CHROME} --screenshot=${dir}/shots/${name}-desktop-full.png --window-size=1440,4200 "file://${dir}/${page}"\n  ${CHROME} --screenshot=${dir}/shots/${name}-mobile.png --window-size=390,3000 "file://${dir}/${page}"`
const SKILL = `FIRST invoke the Skill tool with skill "frontend-design:frontend-design" and follow its guidance (if unavailable, note it and hold the same bar).`

const results = await pipeline(OPTIONS,
  // Stage 1: option lead — design system, shared chrome, Home
  (o) => {
    const b = briefs.options.find(x => x.key === o.key)
    const dir = `${R2}/${o.slug}`
    return agent(`${GOAL}\n\n${SKILL}\n\nYou are the LEAD DESIGNER for round-2 option ${o.key.toUpperCase()} ("${b.name}"), led by round-1 concept "${o.leader}" (${o.r1dir} — read its index.html and look at its screenshots).\nBRIEF:\n${JSON.stringify(b, null, 1)}\nCROSS-CUTTING FIXES:\n${JSON.stringify(briefs.cross_cutting)}\n\nShared inputs: ${R2}/_shared/ia.md, content-*.md, assets/data.js, assets/img/ (+ README.md), and ${R2}/briefs.md.\n\nBUILD in ${dir}/ (mkdir -p ${dir}/assets ${dir}/members ${dir}/shots):\n1. Copy ${R2}/_shared/assets/data.js -> ${dir}/assets/data.js and ${R2}/_shared/assets/img/ -> ${dir}/assets/img/.\n2. ${dir}/assets/site.css — the option's full DESIGN SYSTEM: tokens (CSS custom properties), typography scale, layout grid, header/primary nav (with persistent Join CTA and a mobile menu), members sub-nav component, footer, buttons, cards, callouts, tables, TOC component, doc-list/filter component, event list, forms, illustration/photo-frame treatments, focus styles, reduced-motion. Other pages will be built by OTHER agents using ONLY this CSS (plus small page-scoped <style> blocks), so make it complete and document it.\n3. ${dir}/assets/site.js — progressive enhancement shared by all pages: mobile menu, "this week"/next-net/rota/event widgets rendered from window.SPOKARES, TOC active-section highlighting, doc-library filter/search, copy-to-clipboard radio settings, etc. Everything must degrade gracefully without JS.\n4. ${dir}/_chrome.html — the canonical header and footer markup (with primary nav links: index.html, how-it-works.html, about.html, members/index.html; from members/ pages the paths are ../) and the members sub-nav markup, for the page builders to copy verbatim. Include notes on the active-state class.\n5. ${dir}/index.html — the HOME page, fully built with real content from content-home.md.\n6. ${dir}/DESIGN.md — the option's design rationale, tokens, component list with class names and usage, and page-builder rules (what not to change).\nSELF-REVIEW: shoot and LOOK (Read) at the screenshots, fix, re-shoot:\n${shoot(dir, 'index.html', 'home')}\n(ignore Chrome task_policy_set stderr noise). Return structured result.`,
      { label: `lead:${o.key}`, phase: 'Build', schema: BUILT })
  },
  // Stage 2: three page builders in parallel, using the lead's system
  (lead, o) => {
    const b = briefs.options.find(x => x.key === o.key)
    const dir = `${R2}/${o.slug}`
    const common = `${GOAL}\n\n${SKILL}\n\nYou are a PAGE BUILDER for round-2 option ${o.key.toUpperCase()} ("${b.name}"). The lead designer already built the design system — READ FIRST: ${dir}/DESIGN.md, ${dir}/assets/site.css, ${dir}/assets/site.js, ${dir}/_chrome.html, and ${dir}/index.html (look at ${dir}/shots/home-*.png). Option brief:\n${JSON.stringify({ name: b.name, section_approach: b.section_approach, keep: b.keep, grafts: b.grafts }, null, 1)}\nRULES: do NOT edit site.css, site.js, data.js, _chrome.html or index.html (other builders run in parallel). Copy the header/footer from _chrome.html verbatim (set the correct active state and relative paths). Page-specific styles go in a <style> block in your page; page-specific scripts in a <script> in your page. Use real content from ${R2}/_shared/. Lead's notes: ${lead ? lead.notes : '(lead failed — follow DESIGN.md)'}\n`
    return parallel([
      () => agent(`${common}\nBUILD ${dir}/how-it-works.html from ${R2}/_shared/content-how-it-works.md — the operations deep-dive (${b.section_approach.how_it_works}). SELF-REVIEW:\n${shoot(dir, 'how-it-works.html', 'how')}\nLook at the shots, fix, re-shoot. Return structured result.`,
        { label: `page:${o.key}:how`, phase: 'Build', schema: BUILT }),
      () => agent(`${common}\nBUILD ${dir}/about.html from ${R2}/_shared/content-about.md — the long-form, text-heavy page with a table of contents (${b.section_approach.about}). Prioritise READABILITY: comfortable measure (60-75ch), strong heading hierarchy, sticky side TOC with active-section highlight on desktop, collapsible TOC on mobile, "back to top", scannable callouts/tables (e.g. ARES vs ACS comparison), anchor links on headings. SELF-REVIEW:\n${shoot(dir, 'about.html', 'about')}\nAlso shoot a mid-page view to check the sticky TOC: ${CHROME} --screenshot=${dir}/shots/about-desktop-mid.png --window-size=1440,900 "file://${dir}/about.html#<an-anchor-in-the-middle>". Look, fix, re-shoot. Return structured result.`,
        { label: `page:${o.key}:about`, phase: 'Build', schema: BUILT }),
      () => agent(`${common}\nBUILD the FOR MEMBERS section — three pages sharing the members sub-nav: ${dir}/members/index.html (hub: this week at a glance — next net & net control from data.js, next meeting/exercise, quick links to the most-used resources, every sub-section as a clear entry point), ${dir}/members/documents.html (document library: categories, search/filter, file type & updated date, official-source badges for FEMA/ICS forms), ${dir}/members/exercises.html (upcoming exercises & events with details/what-to-bring/sign-up routes, plus past exercises). Content: ${R2}/_shared/content-members*.md and data.js. Approach: ${b.section_approach.members}. Optimise NAVIGATION EFFICIENCY: a member should reach any resource in <=2 clicks; sub-nav visible and usable on mobile (e.g. horizontal scroller or select). Paths from members/ use ../assets/... SELF-REVIEW:\n${shoot(dir, 'members/index.html', 'members')}\n${shoot(dir, 'members/documents.html', 'documents')}\n${shoot(dir, 'members/exercises.html', 'exercises')}\nLook, fix, re-shoot. Return structured result.`,
        { label: `page:${o.key}:members`, phase: 'Build', schema: BUILT }),
    ]).then(pages => ({ lead, pages }))
  },
  // Stage 3: integrator — cross-page consistency
  (built, o) => {
    const dir = `${R2}/${o.slug}`
    return agent(`${GOAL}\n\nYou are the INTEGRATOR for round-2 option ${o.key.toUpperCase()} in ${dir}/. Pages: index.html, how-it-works.html, about.html, members/index.html, members/documents.html, members/exercises.html. Builder notes: ${JSON.stringify(built ? [built.lead && built.lead.notes, ...(built.pages || []).map(p => p && p.notes)] : [])}\n\nCHECK AND FIX: (1) every nav/footer/sub-nav link resolves between pages over file:// (write a quick script that parses hrefs and checks files exist); (2) header/footer identical across pages with correct active states; (3) visual consistency (spacing, type, colours) — pages must feel like ONE site; (4) mobile menu and members sub-nav work at 390px; (5) no console errors (use ${CHROME} --enable-logging=stderr --v=0 --dump-dom "file://..." or similar to confirm widgets render from data.js); (6) accessibility basics (skip link, landmarks, one h1, alt text, focus visible, contrast); (7) cross-cutting rules: no Condition Green status, no visible TBD clutter, footer mockup line present, no personal phones/emails. You MAY edit any file in the option now (builders are done). Re-shoot ALL pages to ${dir}/shots/ using the same names (home, how, about, members, documents, exercises × desktop-fold/desktop-full/mobile), e.g.:\n${shoot(dir, 'index.html', 'home')}\nLOOK at every final shot. Write ${dir}/README.md: option name, concept summary, page list, how it addresses round-1 feedback, known limitations. Return structured result (pages = the 6 page paths, screenshots = all final shots).`,
      { label: `integrate:${o.key}`, phase: 'Build', schema: BUILT })
  },
)

const ok = OPTIONS.map((o, i) => ({ o, r: results[i], b: briefs.options.find(x => x.key === o.key) })).filter(x => x.r)
log(`${ok.length}/3 options built`)

phase('Review')
const JUDGES = [
  { key: 'prospect', lens: 'PROSPECTIVE VOLUNTEER (newly licensed ham, and alternately an unlicensed civic-minded Spokane resident). Tasks: from Home, understand what this is in 10 seconds; find what members actually do; find how to join if you are NOT licensed; decide whether to come to a meeting and find where/when.' },
  { key: 'agency', lens: 'SERVED-AGENCY PARTNER (Spokane County emergency manager / hospital emergency coordinator / Red Cross liaison). Tasks: judge credibility and professionalism across all pages; find the ARES vs ACS distinction and the legal/authority basis in About; decide if you would link to this from an official county page.' },
  { key: 'member', lens: 'CURRENT MEMBER (active ARES-ACS volunteer, on a phone in the car and on a laptop at home). Tasks, timed by clicks: find who is net control this Tuesday and the repeater settings; download the net preamble/script; find the ICS-213 form; find details of the next exercise and what to bring; find the task-book requirements. Judge the members sub-nav and navigation efficiency above all.' },
  { key: 'ux', lens: 'senior UX + ACCESSIBILITY + MAINTAINABILITY reviewer (implementation later in WordPress or a static generator by volunteer maintainers). Judge navigation clarity, information scent, long-form readability of About (TOC, measure, hierarchy), mobile, contrast, keyboard/focus, and how hard each option would be to implement and keep updated.' },
  { key: 'art', lens: 'ART DIRECTOR / brand designer. Judge visual craft, coherence across all six pages, distinctiveness, typography, use of photography/illustration, and memorability — appropriate for an emergency-services volunteer organisation.' },
]
const SCORE = {
  type: 'object',
  properties: {
    scores: {
      type: 'array',
      items: {
        type: 'object',
        properties: {
          key: { type: 'string', enum: ['a', 'b', 'c'] },
          professional: { type: 'number' }, engaging: { type: 'number' }, ease_of_use: { type: 'number' },
          learn_more: { type: 'number' }, sign_up: { type: 'number' }, member_efficiency: { type: 'number' },
          feedback_addressed: { type: 'number', description: '1-10: did it fix the round-1 criticisms of its leader?' },
          overall: { type: 'number' },
          section_home: { type: 'number' }, section_how: { type: 'number' }, section_about: { type: 'number' }, section_members: { type: 'number' },
          task_results: { type: 'string', description: 'for each lens task: success? clicks? friction?' },
          strengths: { type: 'string' }, weaknesses: { type: 'string' },
          must_fix: { type: 'array', items: { type: 'string' } },
        },
        required: ['key', 'professional', 'engaging', 'ease_of_use', 'learn_more', 'sign_up', 'member_efficiency', 'feedback_addressed', 'overall', 'section_home', 'section_how', 'section_about', 'section_members', 'task_results', 'strengths', 'weaknesses', 'must_fix'],
      },
    },
    top_pick: { type: 'string', enum: ['a', 'b', 'c'] },
    best_elements_to_combine: { type: 'string' },
  },
  required: ['scores', 'top_pick', 'best_elements_to_combine'],
}
const listing = ok.map(({ o, b }) => `- option ${o.key} "${b.name}" (led by ${o.leader}): ${R2}/${o.slug}/ — pages index.html, how-it-works.html, about.html, members/index.html, members/documents.html, members/exercises.html; screenshots in ${R2}/${o.slug}/shots/ (home|how|about|members|documents|exercises)-(desktop-fold|desktop-full|mobile).png; README.md explains how it addressed round-1 feedback`).join('\n')
const verdicts = await parallel(JUDGES.map(j => () =>
  agent(`${GOAL}\n\nYou are a judge. LENS: ${j.lens}\n\nReview ALL options side by side, across ALL six pages each. LOOK at the screenshots (Read tool) and read the HTML where needed to test interactions/links. Round-1 context for "feedback_addressed": ${D}/REVIEW.md and ${R2}/briefs.md. Score each option 1-10 on every criterion, calibrated across the set (use the range). Be concrete: name pages, sections, components.\n\n${listing}`,
    { label: `judge:${j.key}`, phase: 'Review', schema: SCORE })))

const valid = verdicts.map((v, i) => v && { ...v, judge: JUDGES[i].key }).filter(Boolean)
const CRIT = ['professional', 'engaging', 'ease_of_use', 'learn_more', 'sign_up', 'member_efficiency', 'feedback_addressed', 'overall']
const SECT = ['section_home', 'section_how', 'section_about', 'section_members']
const r1 = n => Math.round(n * 10) / 10
const agg = ok.map(({ o, b }) => {
  const rows = valid.map(v => ({ judge: v.judge, s: v.scores.find(s => s.key === o.key) })).filter(r => r.s)
  const mean = k => rows.length ? rows.reduce((a, r) => a + (r.s[k] || 0), 0) / rows.length : 0
  return {
    key: o.key, slug: o.slug, name: b.name, leader: o.leader,
    crit: Object.fromEntries(CRIT.map(k => [k, r1(mean(k))])),
    sections: Object.fromEntries(SECT.map(k => [k, r1(mean(k))])),
    composite: Math.round(CRIT.reduce((a, k) => a + mean(k), 0) / CRIT.length * 100) / 100,
    top_picks: valid.filter(v => v.top_pick === o.key).map(v => v.judge),
    judges: rows.map(r => ({ judge: r.judge, overall: r.s.overall, task_results: r.s.task_results, strengths: r.s.strengths, weaknesses: r.s.weaknesses, must_fix: r.s.must_fix })),
  }
}).sort((a, b) => b.composite - a.composite || b.top_picks.length - a.top_picks.length)
agg.forEach((a, i) => { a.rank = i + 1 })
log(`Round 2: ${agg.map(a => `${a.rank}. ${a.name} (${a.composite})`).join(' | ')}`)

phase('Present')
const summary = await agent(`${GOAL}\n\nBuild the ROUND-2 REVIEW GALLERY for the owner to browse locally. Ranked results (already computed; do not re-rank):\n${JSON.stringify(agg)}\nJudges' combination advice:\n${JSON.stringify(valid.map(v => ({ judge: v.judge, top_pick: v.top_pick, best_elements_to_combine: v.best_elements_to_combine })))}\n\nWRITE ${R2}/index.html — a neutral, polished gallery (light/dark aware, relative links only, works via file://). Top: what round 2 is (3 options led by the round-1 leaders, the four-section structure, the 5 judge lenses incl. the current-member task tests, scoring = mean of 8 criteria across judges). A comparison table (options × 8 criteria) and a section-scores table (options × Home / How it works / About / Members). Then per option: rank, name, "led by", composite, a prominent "Open this site" button (-> <slug>/index.html, new tab) plus direct links to all six pages, a strip of desktop-fold screenshots for all six pages (clickable to the page) and mobile thumbnails, "How it addressed round-1 feedback" (from its README.md), the member-task results, and collapsible judges' notes with must-fix lists. Close with "Recommendation": which option to build on, which elements to graft from the others, and the consolidated must-fix list. Also WRITE ${R2}/REVIEW.md with the same content. Verify the gallery: ${CHROME} --screenshot=${R2}/_gallery.png --window-size=1440,2400 "file://${R2}/index.html" and look at it; fix issues. Return a <=25 line summary: ranking with one-line reason each, recommendation, top must-fixes.`,
  { label: 'gallery-r2', phase: 'Present' })

return { ranking: agg.map(a => ({ rank: a.rank, key: a.key, name: a.name, leader: a.leader, composite: a.composite, crit: a.crit, sections: a.sections, top_picks: a.top_picks })), gallery: `${R2}/index.html`, summary, judges_ok: valid.length, content: contentReport }
