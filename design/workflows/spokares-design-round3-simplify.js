export const meta = {
  name: 'spokares-design-round3-simplify',
  description: 'Simplify the three round-2 site options: one lean shared copy set with a fact-placement map, audited, then each option rebuilt to hard word/height budgets',
  phases: [
    { title: 'Plan', detail: 'fact placement map + page budgets' },
    { title: 'Copy', detail: '6 copywriters write lean page copy' },
    { title: 'Audit', detail: 'repetition auditor + loss auditor, then fixer' },
    { title: 'Rebuild', detail: 'per option: lead (chrome+Home) -> 3 page editors -> integrator measuring budgets' },
    { title: 'Review', detail: '3 judges: newcomer, member tasks, clarity editor' },
    { title: 'Present', detail: 'before/after gallery' },
  ],
}

const P = '/Users/sean/Projects/spokane_ares'
const D = `${P}/design`
const R2 = `${D}/round2`
const R3 = `${D}/round3`
const S = `${R3}/_shared`
const TODAY = args.today
const CHROME = `"/Applications/Google Chrome.app/Contents/MacOS/Google Chrome" --headless=new --disable-gpu --hide-scrollbars --virtual-time-budget=6000`
const MEASURE = `${P}/.venv/bin/python ${D}/tools/measure_pages.py`

const BUDGET = {
  'index.html': { words: 400, h2: 5, ctas: 6, desk: 3200, note: 'hero + at most 4 short sections; newcomer-first; one clear primary CTA (Join) and one secondary (How it works / Visit)' },
  'how-it-works.html': { words: 1200, h2: 6, note: 'operations explained once, plainly; B may keep a SHORT story but it cannot precede the practical content by more than one screen' },
  'about.html': { words: 2800, h2: 9, note: 'still the detailed reference with TOC, but each topic said once, tersely; use tables/lists instead of prose where possible' },
  'members/index.html': { words: 300, note: 'a switchboard: this week at a glance + links to every members sub-section; no explanations' },
  'members/documents.html': { words: 900, note: 'the library IS the page: compact table/list, descriptions <= 8 words or none, no intro essay' },
  'members/exercises.html': { words: 700, note: 'next events first (with what to bring), past exercises as a compact list' },
}

const GOAL = `PROJECT: spokares.org redesign for Spokane County ARES-ACS. Three round-2 design options exist in ${R2}/ (option-a-field-guide "Figure One", option-b-lines-down "Carry the Message", option-c-open-door "Open Channel"), each a six-page static mockup: index.html (Home), how-it-works.html, about.html (long-form with TOC), members/index.html, members/documents.html, members/exercises.html.
OWNER FEEDBACK (the reason for this pass): "All three of these have just way too much info. It's overload. Do another pass on each to simplify. Minimize repeating information unless it's really important."
BASELINE (measured): each option has ~17,000-19,000 visible words across six pages; About is 6,600-7,000 words (29,000-36,000px tall); How it works 3,600-4,100 words; Home 1,100-1,900 words with up to 27 CTAs; key facts (repeater settings, net time, join steps, county application, ICS-213, task books, WSDOT exercise, groups.io) each appear on 4-6 of 6 pages. Full numbers: ${R2}/_metrics-before.json.
TARGET: cut each option to roughly one third (~6,000 words total) with these HARD page budgets (visible words in <main>): ${JSON.stringify(BUDGET)}.
REPETITION RULE: every fact has ONE home. Other pages may carry at most a one-line pointer/link to it, and only where a visitor genuinely needs it there. The only things allowed to repeat freely are the primary nav (with the Join CTA) and the footer (which itself must be minimal: a few links, no re-stated facts/addresses/schedules beyond one contact line).
SIMPLIFICATION PRINCIPLES: say it once; lead with what the reader needs to do; cut preamble, reassurance and marketing filler; prefer short lists/tables to paragraphs; no section should exist just to link to another section; fewer, stronger CTAs; progressive disclosure (<details>) only for genuinely secondary reference detail, not as a way to hide bloat (hidden words still count toward the budget in spirit — keep them few).
KEEP: accuracy (real facts only from the existing content/research; never invent), each option's visual personality, accessibility, and the four-section structure. Keep the members sub-nav but trim it to sections that exist or will clearly exist.
Also apply these compatible round-2 must-fixes: newcomer-first Home (net-control/offset/tone details belong on members pages, not Home); no dead "#" links for "Training & task books" (point to the About section or drop it); write "8:00 PM" not "2000"; fix deep links that overshoot on direct load; members hub shows the next two events. Today is ${TODAY}.`

phase('Plan')
const plan = await agent(`${GOAL}\n\nYou are the MANAGING EDITOR. Read the round-2 shared copy (${R2}/_shared/ia.md and content-*.md), skim each option's six pages in ${R2}/option-*/ (read the HTML; look at a few screenshots in shots/), ${R2}/REVIEW.md (must-fixes) and ${R2}/_metrics-before.json.\n\nWRITE ${S}/placement-map.md (mkdir -p ${S}) containing:\n1. The FACT PLACEMENT MAP: a table of every distinct fact/topic that currently appears on the site (net schedule & repeater settings, meetings & address, join steps, ARES vs ACS, legal basis, county application & background check, task books & levels, training path, activation flow, message handling/ICS-213/Winlink, exercises & events, public service events, served agencies, groups.io, documents, contacts/roles, history/W7GBU, IEVHFRA MOU, etc.) -> its ONE home (page#section), and any allowed one-line pointers elsewhere with a one-phrase justification. Anything that doesn't earn a place gets "CUT" with a reason.\n2. The simplified IA: primary nav, a MINIMAL footer spec, and a trimmed members sub-nav.\n3. For each of the six pages: purpose in one sentence, the section outline (h2s) with a word budget per section that sums within the page budget, and the single primary action.\nReturn a short summary (<=20 lines).`,
  { label: 'managing-editor', phase: 'Plan' })

phase('Copy')
const PAGES = Object.keys(BUDGET)
const copyName = p => `copy-${p.replace('/', '-').replace('.html', '')}.md`
const copies = await parallel(PAGES.map(p => () =>
  agent(`${GOAL}\n\nYou are a COPYWRITER. Write the lean copy for ONE page: ${p} (budget ${JSON.stringify(BUDGET[p])}). Follow ${S}/placement-map.md exactly (read it first): only include facts whose home is this page, plus the allowed one-line pointers. Source facts from the round-2 copy ${R2}/_shared/content-*.md and ${R2}/_shared/assets/data.js (and ${P}/research/organization.md if a fact needs checking) — never invent. Plain, direct, friendly-professional voice; short sentences; active verbs; no filler. Keep [verify] markers on uncertain facts.\nWRITE ${S}/${copyName(p)}: front matter with page, budget, and your word count; then the copy with h2s matching the outline, CTAs marked like [CTA: label -> target], and any list/table content. For documents/exercises, specify the data (which entries from data.js to show, which columns) rather than writing prose. Count your words (visible text only) and stay within budget. Return a 3-line summary incl. word count.`,
    { label: `copy:${p}`, phase: 'Copy' })))

phase('Audit')
const AUD = {
  type: 'object',
  properties: {
    findings: { type: 'array', items: { type: 'object', properties: {
      file: { type: 'string' }, issue: { type: 'string' }, fix: { type: 'string' },
      severity: { type: 'string', enum: ['must', 'should'] } }, required: ['file', 'issue', 'fix', 'severity'] } },
  },
  required: ['findings'],
}
const [rep, loss] = await parallel([
  () => agent(`${GOAL}\n\nYou are the REPETITION AUDITOR. Read ${S}/placement-map.md and all ${S}/copy-*.md. Find every place a fact/explanation appears more than the map allows, near-duplicate phrasing across pages, sections that exist only to point elsewhere, redundant CTAs, and any page over budget (count words). Be strict. Return findings.`,
    { label: 'audit:repetition', phase: 'Audit', schema: AUD }),
  () => agent(`${GOAL}\n\nYou are the LOSS AUDITOR (adversarial to the cuts). Compare the new lean copy (${S}/copy-*.md, map in ${S}/placement-map.md) against the round-2 copy (${R2}/_shared/content-*.md) and research (${P}/research/organization.md, ${P}/research/decisions.md "never publish" rules). Find anything IMPORTANT that was lost and must survive somewhere (e.g. what a newcomer must know to join, the ARES vs ACS distinction and background-check requirement, legal basis in one line, how to find the weekly net, what to bring to exercises, safety/"we're volunteers not first responders" framing, authoritative sources for forms), any factual errors introduced, and anything that now violates never-publish rules. Only flag what truly matters — do not argue to restore bloat. Return findings.`,
    { label: 'audit:loss', phase: 'Audit', schema: AUD }),
])
const findings = [...(rep ? rep.findings : []), ...(loss ? loss.findings : [])]
log(`Audit: ${findings.length} findings (${findings.filter(f => f.severity === 'must').length} must)`)
const fixedCopy = await agent(`${GOAL}\n\nYou are the MANAGING EDITOR again. Apply these audit findings to ${S}/copy-*.md (and ${S}/placement-map.md if the map changes). Resolve conflicts between the repetition and loss auditors by keeping the fact ONCE in its best home. Stay within budgets. Also create ${S}/assets/data.js by copying ${R2}/_shared/assets/data.js and trimming entries/fields no longer shown (keep the helper functions working; keep the frozen mockup date ${TODAY}). Finally write ${S}/README.md: the placement map summary, budgets, and per-page word counts. Return a <=15 line summary with final word counts per page.\n\nFINDINGS:\n${JSON.stringify(findings, null, 1)}`,
  { label: 'editor:apply-audit', phase: 'Audit' })

phase('Rebuild')
const OPTIONS = [
  { key: 'a', slug: 'option-a-field-guide', name: 'Figure One' },
  { key: 'b', slug: 'option-b-lines-down', name: 'Carry the Message' },
  { key: 'c', slug: 'option-c-open-door', name: 'Open Channel' },
]
const BUILT = {
  type: 'object',
  properties: { notes: { type: 'string' }, metrics_ok: { type: 'boolean' }, skill_used: { type: 'boolean' } },
  required: ['notes', 'metrics_ok', 'skill_used'],
}
const SKILL = `FIRST invoke the Skill tool with skill "frontend-design:frontend-design" and follow its guidance (simplicity and restraint are the brief this time).`
const shots = (dir, page, name) => `${CHROME} --screenshot=${dir}/shots/${name}-desktop-full.png --window-size=1440,3400 "file://${dir}/${page}"\n  ${CHROME} --screenshot=${dir}/shots/${name}-mobile.png --window-size=390,2600 "file://${dir}/${page}"`

const results = await pipeline(OPTIONS,
  o => {
    const src = `${R2}/${o.slug}`, dir = `${R3}/${o.slug}`
    return agent(`${GOAL}\n\n${SKILL}\n\nYou are the LEAD for option ${o.key.toUpperCase()} "${o.name}". Set up: mkdir -p ${dir} && rsync -a --exclude shots ${src}/ ${dir}/ && mkdir -p ${dir}/shots && cp ${S}/assets/data.js ${dir}/assets/data.js . Read ${S}/placement-map.md, ${S}/README.md, ${S}/copy-index.md, and the option's ${dir}/DESIGN.md.\nDO: (1) simplify the shared CHROME in ${dir}/_chrome.html and site-wide: minimal header (keep nav + Join), MINIMAL footer per the map, remove any every-page strips/banners that repeat facts unless the map explicitly allows them; update site.js/site.css only as needed (remove dead widgets). (2) Rebuild ${dir}/index.html to the new Home copy and budget (${JSON.stringify(BUDGET['index.html'])}) — keep the option's visual personality; fewer, larger, calmer sections; generous whitespace. (3) Update DESIGN.md with a "Simplification rules" section for page editors.\nVERIFY: ${MEASURE} ${dir} (Home must meet budget; other pages will be fixed by editors next). Shoot and LOOK: ${shots(dir, 'index.html', 'home')}\nReturn structured result (metrics_ok = Home within budget).`,
      { label: `lead:${o.key}`, phase: 'Rebuild', schema: BUILT })
  },
  (lead, o) => {
    const dir = `${R3}/${o.slug}`
    const common = `${GOAL}\n\n${SKILL}\n\nYou are a PAGE EDITOR for option ${o.key.toUpperCase()} "${o.name}" in ${dir}/. The lead already simplified the chrome and Home — read ${dir}/DESIGN.md (incl. "Simplification rules"), ${dir}/_chrome.html, ${dir}/index.html, ${S}/placement-map.md. Lead notes: ${lead ? lead.notes : 'n/a'}. RULES: do not edit site.css/site.js/data.js/_chrome.html/index.html (parallel editors); copy the new chrome verbatim into your pages; page-scoped styles in a <style> block. Rebuild your page(s) from the lean copy — do not just delete paragraphs from the old page; restructure so it reads calmly and each section earns its place. Verify with ${MEASURE} ${dir} and iterate until your page(s) meet budget.`
    return parallel([
      () => agent(`${common}\nPAGE: how-it-works.html <- ${S}/copy-how-it-works.md (budget ${JSON.stringify(BUDGET['how-it-works.html'])}). Shoot & look: ${shots(dir, 'how-it-works.html', 'how')}`,
        { label: `edit:${o.key}:how`, phase: 'Rebuild', schema: BUILT }),
      () => agent(`${common}\nPAGE: about.html <- ${S}/copy-about.md (budget ${JSON.stringify(BUDGET['about.html'])}). Keep the TOC but with <= 9 entries; make sure deep links land correctly on direct load. Shoot & look: ${shots(dir, 'about.html', 'about')}`,
        { label: `edit:${o.key}:about`, phase: 'Rebuild', schema: BUILT }),
      () => agent(`${common}\nPAGES: members/index.html <- ${S}/copy-members-index.md; members/documents.html <- ${S}/copy-members-documents.md; members/exercises.html <- ${S}/copy-members-exercises.md (budgets ${JSON.stringify({ hub: BUDGET['members/index.html'], docs: BUDGET['members/documents.html'], ex: BUDGET['members/exercises.html'] })}). Trim the members sub-nav per the map; it must be usable on a phone (all items reachable). Shoot & look: ${shots(dir, 'members/index.html', 'members')}\n  ${shots(dir, 'members/documents.html', 'documents')}\n  ${shots(dir, 'members/exercises.html', 'exercises')}`,
        { label: `edit:${o.key}:members`, phase: 'Rebuild', schema: BUILT }),
    ]).then(pages => ({ lead, pages }))
  },
  (built, o) => {
    const dir = `${R3}/${o.slug}`
    return agent(`${GOAL}\n\nYou are the INTEGRATOR for option ${o.key.toUpperCase()} in ${dir}/ (editors are done; you may edit any file). (1) Run ${MEASURE} ${dir} — every page must be within its word budget and Home within ${BUDGET['index.html'].desk}px desktop; key facts on <=2 pages unless the map allows otherwise. Fix and re-measure until it passes (or explain precisely why a limit can't be met). (2) Check all internal links resolve over file://, chrome identical with correct active states, no dead "#" links except external-future items clearly marked, mobile menu and members sub-nav work at 390px, no console errors, one h1 per page, focus visible. (3) Re-shoot every page (home, how, about, members, documents, exercises) with:\n  ${shots(dir, 'index.html', 'home')}\n  (same pattern for the other pages) and also ${CHROME} --screenshot=${dir}/shots/home-desktop-fold.png --window-size=1440,900 "file://${dir}/index.html". LOOK at every shot. (4) Save final metrics: ${MEASURE} --json ${dir} > ${dir}/_metrics-after.json and write ${dir}/README.md (what changed, before/after words per page from ${R2}/_metrics-before.json vs after, what was cut or moved). Return structured result.`,
      { label: `integrate:${o.key}`, phase: 'Rebuild', schema: BUILT })
  },
)
log(`Rebuilt ${results.filter(Boolean).length}/3 options; metrics ok: ${results.map((r, i) => `${OPTIONS[i].key}=${r ? r.metrics_ok : 'fail'}`).join(' ')}`)

phase('Review')
const JUDGES = [
  { key: 'newcomer', lens: 'NEWCOMER (curious Spokane resident, maybe licensed). Can you tell in 10 seconds what this is and what to do next? Does anything feel overwhelming or repetitive? Would you click Join or plan a visit?' },
  { key: 'member', lens: 'CURRENT MEMBER on a phone. Tasks by clicks from Home: this Tuesday\'s net control + repeater settings; the net script; the ICS-213 form; the next exercise and what to bring; task-book requirements. Is anything you need now missing or buried after the cuts?' },
  { key: 'editor', lens: 'CLARITY EDITOR (plain-language + information design). Judge overload, repetition across pages, section economy, scannability, and whether anything important was lost. Compare against round 2 (screenshots in ' + R2 + '/option-*/shots/).' },
]
const SCORE = {
  type: 'object',
  properties: {
    scores: { type: 'array', items: { type: 'object', properties: {
      key: { type: 'string', enum: ['a', 'b', 'c'] },
      clarity: { type: 'number', description: '1-10, 10 = calm, no overload' },
      repetition: { type: 'number', description: '1-10, 10 = nothing needlessly repeated' },
      completeness: { type: 'number', description: '1-10, 10 = nothing important lost' },
      ease_of_use: { type: 'number' }, motivation: { type: 'number', description: 'learn more / sign up pull' },
      member_efficiency: { type: 'number' }, overall: { type: 'number' },
      notes: { type: 'string' }, remaining_issues: { type: 'array', items: { type: 'string' } } },
      required: ['key', 'clarity', 'repetition', 'completeness', 'ease_of_use', 'motivation', 'member_efficiency', 'overall', 'notes', 'remaining_issues'] } },
    top_pick: { type: 'string', enum: ['a', 'b', 'c'] },
  },
  required: ['scores', 'top_pick'],
}
const listing = OPTIONS.map(o => `- ${o.key} "${o.name}": ${R3}/${o.slug}/ (pages + shots/ + README.md + _metrics-after.json)`).join('\n')
const verdicts = await parallel(JUDGES.map(j => () =>
  agent(`${GOAL}\n\nJUDGE — ${j.lens}\n\nReview all three simplified options across all six pages (look at shots with Read; read HTML where needed). Score 1-10 per criterion, calibrated across the set.\n${listing}`,
    { label: `judge:${j.key}`, phase: 'Review', schema: SCORE })))
const valid = verdicts.map((v, i) => v && { ...v, judge: JUDGES[i].key }).filter(Boolean)
const CRIT = ['clarity', 'repetition', 'completeness', 'ease_of_use', 'motivation', 'member_efficiency', 'overall']
const agg = OPTIONS.map(o => {
  const rows = valid.map(v => ({ judge: v.judge, s: v.scores.find(s => s.key === o.key) })).filter(r => r.s)
  const mean = k => rows.length ? rows.reduce((a, r) => a + (r.s[k] || 0), 0) / rows.length : 0
  return { key: o.key, slug: o.slug, name: o.name,
    crit: Object.fromEntries(CRIT.map(k => [k, Math.round(mean(k) * 10) / 10])),
    composite: Math.round(CRIT.reduce((a, k) => a + mean(k), 0) / CRIT.length * 100) / 100,
    top_picks: valid.filter(v => v.top_pick === o.key).map(v => v.judge),
    judges: rows.map(r => ({ judge: r.judge, notes: r.s.notes, remaining: r.s.remaining_issues })) }
}).sort((a, b) => b.composite - a.composite)

phase('Present')
const summary = await agent(`${GOAL}\n\nBuild the ROUND-3 (SIMPLIFIED) GALLERY at ${R3}/index.html (neutral, light/dark aware, relative links, works via file://). Data: judges' results ${JSON.stringify(agg)}; before metrics ${R2}/_metrics-before.json; after metrics ${R3}/option-*/_metrics-after.json. Show: a headline before/after table per option (total words, words per page, Home desktop height, repeated key facts), the judge scores, and per option: "Open this site" + links to all six pages, desktop/mobile screenshots (${R3}/<slug>/shots/), what was cut/moved (from its README.md), judge notes and remaining issues. Include a link to the round-2 gallery (../round2/index.html) for comparison. Also write ${R3}/REVIEW.md. Verify: ${CHROME} --screenshot=${R3}/_gallery.png --window-size=1440,2400 "file://${R3}/index.html" and look; fix. Return a <=20 line summary: before/after totals per option, judge ranking, top remaining issues.`,
  { label: 'gallery-r3', phase: 'Present' })

return { plan, copy_final: fixedCopy, ranking: agg.map(a => ({ key: a.key, name: a.name, composite: a.composite, crit: a.crit, top_picks: a.top_picks })), gallery: `${R3}/index.html`, summary }
