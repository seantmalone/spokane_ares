export const meta = {
  name: 'spokares-editor-ux',
  description: 'Walk every volunteer-editor journey step by step, audit fields/guidance/terminology/menus, design a clearer editor experience, implement it, and re-walk to verify',
  phases: [
    { title: 'Inventory', detail: 'journey catalogue + first-click tasks; full terminology/menu extraction' },
    { title: 'Walk', detail: '5 walkers: rota/net, events/meetings, documents/tiles, page text/dashboard, first-click findability' },
    { title: 'Design', detail: 'UX lead spec (vocabulary, menus, per-screen fixes), 2 critics, revision' },
    { title: 'Implement', detail: 'parallel by screen group with disjoint files, tests updated' },
    { title: 'Verify', detail: 'suite + ci, fresh re-walk and first-click retest, guide + report' },
  ],
}

const R = '/Users/sean/Projects/spokane_ares'
const W = `${R}/wordpress`
const UX = `${W}/ux`
const TODAY = args.today
const ROLE_LOGIN = 'qa-ares-net'

const CONTEXT = `PROJECT: the spokares.org WordPress build (${W}/): theme ${W}/theme/spokares, site plugin ${W}/plugins/spokares-core (admin screens in inc/admin-*.php, dashboard.php, guards.php, format.php, governance.php, roles.php; admin JS in assets/js/admin-forms.js and editor-guard.js), editing guide ${W}/EDITING-GUIDE.md, decision record ${W}/PLAN.md (§3 content model, §4 roles/locking/editor UX). Just deployed as v0.1.1 after a full QA pass (${W}/qa/ISSUES.md) — permissions are already validated, so this review is ONLY about the volunteer editor's experience.
OWNER'S BRIEF (verbatim): "Do a workflow review across user journeys for volunteer editors. Walk every step (but only as one user role since we validated permissions). Check fields, instructions, clarity. Foundationally, you need to think hard about this and ask 'does this make sense?' Does the create step have the right guidance? Does edit align with create? Are we using simple, clear terminology? Is it clear from the menus where to go for different types of changes? Make it great. Fix any identified issues."
OWNER'S TASTE: strongly dislikes information overload and repetition — prefer clear labels, sensible field order and good defaults over more help text; one short hint beats a paragraph; say each thing once.
ROLE: walk as the ARES Editor WITH the Net details grant (dev login ?dev_login=${ROLE_LOGIN}) — the superset of what volunteers can do. (A plain ARES Editor sees the same minus Net details/Meeting rules.)
PERSONA: "Frank" — a retired ham radio operator in his 70s, the Section Emergency Coordinator, who today maintains a site with Hostinger's no-code website builder; comfortable with email, Word, PDFs; NOT a WordPress user; edits monthly (net-control rota, upcoming exercises/events, meetings, documents), occasionally page text; sometimes on an iPad. He thinks in real-world terms ("Tom can't do net control on the 13th", "the SET exercise moved to Saturday", "here's the new net script"), not in WordPress terms (post types, blocks, taxonomies).
LOCAL WORDPRESS: ${W}/dev/start.sh <PORT> boots a fresh site with real content (~60s); ${W}/dev/stop.sh <PORT>. Sign in with http://127.0.0.1:<PORT>/wp-admin/?dev_login=${ROLE_LOGIN}. Browser automation helpers: ${W}/dev/tests/lib/ and ${W}/dev/tests/run-e2e.mjs (Chrome DevTools protocol, Node stdlib) and the crawler ${W}/dev/qa/crawl.mjs; screenshots with headless Chrome (fresh --user-data-dir under /tmp, timeouts, kill Chrome you start). Use ONLY your assigned port; always stop your server. Save screenshots under ${UX}/shots/<your-label>/ (git-ignored).
Tests: ${W}/dev/tests/README.md (run-all.sh <PORT>: checks.sh, run-php.sh, run-e2e.mjs). Release gate: ${W}/ops/ci.sh --no-zip. Today is ${TODAY}.`

const OWNER_TERMS = `OWNER TERMINOLOGY DIRECTIVE (given directly by the owner mid-review, ${TODAY}): "'Rota' is a good example. Weird word, makes little sense. 'Net Control Schedule' makes sense."
- MANDATORY: retire the word "rota" from every piece of text a person sees — admin menu, screen titles, headings, labels, buttons, notices, help, dashboard, the editing guide, AND the public site (e.g. the members hub table/heading) — and use "Net Control Schedule" (the owner's exact term; use it verbatim for the menu item and screen title, and apply one consistent casing for it in running text).
- PRINCIPLE: treat this as the test for EVERY term: prefer plain, descriptive names a ham volunteer would say out loud ("Net Control Schedule", not "rota"); replace other jargon the same way (e.g. "tiles", "kind", "held back", WordPress words).
- Do NOT rename internal identifiers (option names such as spk_rota, PHP functions, CSS classes, block names, file names, test file names) — only user-facing text — so data, CSS and tests stay intact; update test assertions that check the visible wording.`

phase('Inventory')
const [journeys, glossary] = await parallel([
  () => agent(`${CONTEXT}\n\nTASK: build the JOURNEY CATALOGUE. From PLAN.md §3-4, EDITING-GUIDE.md, the admin screens' code, and the public site (what content editors can influence), list EVERY volunteer journey a Frank-type editor will do (create AND edit AND remove/undo variants), e.g.: post next month's net-control rota; swap one net controller; mark a net open; add each kind of event (exercise, public-service event, training, meeting, other kinds that exist); edit an event's date/time/place; cancel or postpone an event; remove a past or mistaken event; cancel/move one regular meeting; change a regular meeting's rule/time; add a document (file upload) in the right section; add a document that is a link to an official source; replace a document with a new version; retire/remove a document; reorder documents; change the members-hub quick tiles; change net details (frequency/offset/tone/time); edit a sentence on Home / How it works / About; change a photo; find help; set up / use two-factor; sign in/out; see "is my change live?". For each journey: real-world trigger in Frank's words, expected start point (menu path), steps, where the result shows on the public site, and success criteria. Also write 20 FIRST-CLICK TASKS — plain-language task statements Frank would have, with NO WordPress or menu words in them (e.g. "Randy is taking net control on October 13 instead of Tom"). Write ${UX}/journeys.md (mkdir -p ${UX}). Return the catalogue summary and the 20 tasks verbatim.`,
    { label: 'inventory:journeys', phase: 'Inventory' }),
  () => agent(`${CONTEXT}\n\nTASK: TERMINOLOGY & MENU INVENTORY. Start a site on port 9541, sign in as ${ROLE_LOGIN}, and extract EVERY piece of visible text the editor meets: the full admin menu tree (top-level + submenus, order, icons), admin bar, Dashboard widget(s), every screen's title, headings, field labels, placeholders, hints/help text, column headers, row actions, buttons, notices (success/error — trigger them), Help tabs, list-table views/filters, empty states, and the block-editor messages our code adds. Also list the PUBLIC site's names for the same things (page titles, section headings, labels like "Net control", "Exercises & events", "Documents & forms"). Build ${UX}/glossary.md: a table term -> where it appears (screen/element) -> what it means -> the public-site word for the same concept; then flag inconsistencies (two words for one thing, one word for two things), jargon (WordPress/internal words: post, slug, taxonomy, rota?, tiles, kind, held back, spk, draft vs publish wording), and menu-structure problems (things in surprising places, near-duplicate menu names such as "Regular meetings" vs "Meeting rules"). Also save the raw extraction as ${UX}/glossary-raw.json. Stop your server. Return a <=30 line summary of the biggest terminology and menu problems.`,
    { label: 'inventory:glossary', phase: 'Inventory' }),
])

phase('Walk')
const FIND = {
  type: 'object',
  properties: {
    findings: { type: 'array', items: { type: 'object', properties: {
      journey: { type: 'string' },
      screen: { type: 'string' },
      step: { type: 'string' },
      problem: { type: 'string', description: 'what is confusing/wrong/missing, from Frank\'s point of view' },
      lens: { type: 'string', enum: ['create-guidance', 'edit-vs-create', 'terminology', 'menus-findability', 'field-design', 'feedback-messages', 'result-visibility', 'error-recovery', 'overload', 'other'] },
      severity: { type: 'string', enum: ['blocker', 'major', 'minor'] },
      proposed_fix: { type: 'string', description: 'concrete: exact new wording / field order / structure' },
      evidence: { type: 'array', items: { type: 'string' } },
    }, required: ['journey', 'screen', 'step', 'problem', 'lens', 'severity', 'proposed_fix', 'evidence'] } },
    journeys_walked: { type: 'array', items: { type: 'object', properties: { journey: { type: 'string' }, clicks: { type: 'number' }, succeeded: { type: 'boolean' }, verdict: { type: 'string' } }, required: ['journey', 'clicks', 'succeeded', 'verdict'] } },
    does_it_make_sense: { type: 'string', description: 'your honest overall answer for this area' },
  },
  required: ['findings', 'journeys_walked', 'does_it_make_sense'],
}
const RUBRIC = `For EVERY step ask "does this make sense to Frank?" and check: (1) CREATE GUIDANCE — does the empty form tell him what's needed, in what format, with sensible defaults, required fields obvious, examples only where they truly help? (2) EDIT vs CREATE — does editing an existing item look and behave like creating one (same fields, order, labels, rules, messages)? (3) TERMINOLOGY — plain words, matching the public site's words and the glossary; no WordPress/internal jargon; one term per concept. (4) MENUS — would he know where to go? (5) FIELD DESIGN — labels, order, grouping, input types (date/time pickers), validation timing and messages. (6) FEEDBACK — after saving, does he know it worked and where it shows? Can he see it? (7) ERROR RECOVERY — mistakes are easy to undo; errors say what to do. (8) OVERLOAD — anything to REMOVE? Screenshot every step into ${UX}/shots/<your-label>/ and cite paths. Propose concrete fixes with exact wording.`
const WALKERS = [
  { key: 'net', port: 9542, scope: 'Net-control rota (post next month, swap one controller, mark open/not posted, undo) and Net details (frequency/offset/tone/time, the admin-only call sign as he sees it), including on an iPad-width (768px) and phone (390px) screen.' },
  { key: 'events', port: 9543, scope: 'Events of EVERY kind: create, edit (date/time/place/details), cancel/postpone, delete, what happens to past events; and Regular meetings / Meeting rules (cancel or move one meeting, change a rule/time). Compare create vs edit screens side by side.' },
  { key: 'documents', port: 9544, scope: 'Documents: add a file (incl. the privacy check), add a link to an official source, choose the right section, replace with a new version, retire/remove, reorder; Hub tiles (what they are, how he would know they exist, editing them). Compare create vs edit.' },
  { key: 'pages', port: 9545, scope: 'Dashboard (first impression, the task widget), editing text on Home / How it works / About in the block editor (finding the page, what he can and can\'t change, the lock messages, changing a photo, saving/previewing), Profile & two-factor, Help tabs, signing in and out, and "is my change live?".' },
  { key: 'first-click', port: 9546, scope: `COLD FINDABILITY TEST. You get ONLY the 20 first-click task statements below and the Dashboard. For each: record your FIRST click (menu item), the full path, clicks to success, whether you succeeded, and where you hesitated. Do NOT read code or guides first — behave like Frank. Tasks:\n${'${TASKS}'}` },
]
const walks = await parallel(WALKERS.map(w => () => {
  const scope = w.scope.replace('${TASKS}', journeys)
  return agent(`${CONTEXT}\n\nJourney catalogue (from the inventory): see ${UX}/journeys.md. Glossary: ${UX}/glossary.md. Glossary summary:\n${glossary}\n\nYou are a JOURNEY WALKER in Frank's shoes. SCOPE: ${scope}\n\nUse ONLY port ${w.port}. ${RUBRIC}\nDo NOT fix anything. Stop your server when done.`,
    { label: `walk:${w.key}`, phase: 'Walk', schema: FIND })
}))
const findings = walks.flatMap((r, i) => r ? r.findings.map(f => ({ ...f, walker: WALKERS[i].key })) : [])
const firstClick = walks[WALKERS.findIndex(w => w.key === 'first-click')]
log(`Walkers: ${findings.length} findings (${findings.filter(f => f.severity === 'blocker').length} blocker, ${findings.filter(f => f.severity === 'major').length} major)`)

phase('Design')
const SPEC = {
  type: 'object',
  properties: {
    groups: { type: 'array', items: { type: 'object', properties: {
      key: { type: 'string' },
      owned_paths: { type: 'array', items: { type: 'string' }, description: 'non-overlapping files this implementer may edit (tests excepted)' },
      changes: { type: 'array', items: { type: 'string' }, description: 'precise change list with exact wording' },
    }, required: ['key', 'owned_paths', 'changes'] } },
    summary: { type: 'string' },
  },
  required: ['groups', 'summary'],
}
const specDraft = await agent(`${CONTEXT}\n\n${OWNER_TERMS}\n\nYou are the UX LEAD. Inputs: journeys ${UX}/journeys.md; glossary ${UX}/glossary.md; walker findings (JSON):\n${JSON.stringify(findings)}\nFirst-click results: ${JSON.stringify(firstClick ? firstClick.journeys_walked : [])}\nWalkers' overall verdicts: ${JSON.stringify(walks.map((r, i) => r && { walker: WALKERS[i].key, verdict: r.does_it_make_sense }))}\n\nThink hard about the whole editor experience, not just the individual findings. WRITE ${UX}/SPEC.md:\n1. VOCABULARY: one plain term per concept, matched to the public site's words where possible (table: concept -> chosen term -> retired terms). No WordPress/internal jargon on editor-facing text.\n2. MENU STRUCTURE: the final admin menu for volunteers (labels, order, grouping, icons) designed so Frank's real-world tasks map to an obvious first click; justify with the first-click results. Keep it short.\n3. PER-SCREEN SPEC for every editor screen (Dashboard widget, Net rota, Net details, Events list/add/edit, Regular meetings/Meeting rules, Documents list/add/edit, Hub tiles, Pages/editor messages, Profile bits we control): field order and grouping, labels, input types, required markers, defaults, at most ONE short hint per field where needed, create/edit parity rules, success/error messages that say what happened and where to see it, empty states. Remove anything that doesn't earn its place.\n4. EDITING-GUIDE changes.\n5. Findings disposition: every walker finding -> accepted (with the change) / rejected (why).\nAlso return an implementation split into 4-5 groups with NON-OVERLAPPING owned files (inspect ${W}/plugins/spokares-core/inc/, assets/js/, and ${W}/EDITING-GUIDE.md; e.g. navigation+dashboard+help, rota+net+meetings, events, documents+tiles+site settings, page-editing messages+guide), each with a precise change list.`,
  { label: 'design:lead', phase: 'Design', schema: SPEC })
const CRITICS = [
  { key: 'frank', lens: `You ARE Frank (see PERSONA). Read ${UX}/SPEC.md as if seeing these screens described for the first time. Is every term one you'd use? Would you know where to click for each of your jobs? Is anything still confusing, missing, or over-explained? Is there too much text anywhere?` },
  { key: 'implementation', lens: `a senior WordPress developer who knows this codebase (read the relevant inc/ files and PLAN.md §3-4). Is the spec feasible without breaking data, guards, permissions or the layout lock? Does it keep create/edit parity truly consistent (same code paths)? What tests will need updating, and does any change risk weakening a QA regression test? Anything over-engineered?` },
]
const critiques = await parallel(CRITICS.map(c => () =>
  agent(`${CONTEXT}\n\n${OWNER_TERMS}\n\nYou are ${c.lens}\nGive concrete, prioritized critique (must/should) with exact alternatives. <=40 lines.`,
    { label: `critic:${c.key}`, phase: 'Design' })))
const spec = await agent(`${CONTEXT}\n\n${OWNER_TERMS}\n\nYou are the UX LEAD again. Revise ${UX}/SPEC.md to address these critiques (accept every "must" unless demonstrably wrong; note responses at the end):\n${CRITICS.map((c, i) => `### ${c.key}\n${critiques[i] || '(missing)'}`).join('\n\n')}\n\nPrevious split: ${JSON.stringify(specDraft.groups.map(g => ({ key: g.key, owned_paths: g.owned_paths })))}\nReturn the FINAL implementation split (non-overlapping owned files; precise change lists).`,
  { label: 'design:revise', phase: 'Design', schema: SPEC })

phase('Implement')
const DONE = { type: 'object', properties: { changes_done: { type: 'array', items: { type: 'string' } }, not_done: { type: 'array', items: { type: 'string' } }, tests_touched: { type: 'array', items: { type: 'string' } }, notes: { type: 'string' } }, required: ['changes_done', 'not_done', 'tests_touched', 'notes'] }
const impl = await parallel(spec.groups.map((g, gi) => () =>
  agent(`${CONTEXT}\n\n${OWNER_TERMS}\n\nYou are IMPLEMENTER "${g.key}". Read ${UX}/SPEC.md (final). You may edit ONLY these files: ${JSON.stringify(g.owned_paths)} — other implementers are editing other files in parallel. If a change needs another file, don't edit it: list it under not_done with the exact change.\nYour change list:\n${g.changes.map(c => '- ' + c).join('\n')}\n\nTESTS: many E2E/PHP tests assert on-screen wording. Where a test asserts wording YOU changed, update that assertion to the new wording with the Edit tool (targeted replacements only — other implementers may be editing other lines of the same test files; never rewrite a whole test file). Never remove or weaken a behavioural check. Add tests for new behaviour (e.g. create/edit parity, new messages). Use ONLY port ${9551 + gi}; run the tests that cover your screens (and ideally run-all.sh) before finishing; stop your server. Screenshot your screens after the change into ${UX}/shots/after-${g.key}/ and LOOK at them.`,
    { label: `impl:${g.key}`, phase: 'Implement', schema: DONE })))
const leftovers = impl.filter(Boolean).flatMap((r, i) => r.not_done.map(n => `[${spec.groups[i].key}] ${n}`))
let cross = null
if (leftovers.length) {
  cross = await agent(`${CONTEXT}\n\n${OWNER_TERMS}\n\nYou are the CROSS-CUTTING IMPLEMENTER (you may edit any file under ${W}/ now; the others are done). Apply these leftover changes from ${UX}/SPEC.md:\n${leftovers.join('\n')}\nSame test rules (update wording assertions, never weaken behaviour checks). Use port 9560; stop it when done.`,
    { label: 'impl:cross', phase: 'Implement', schema: DONE })
}

phase('Verify')
const verify = await agent(`${CONTEXT}\n\n${OWNER_TERMS}\n\nYou are the VERIFIER. Confirm no visible "rota" remains anywhere (admin, public site, guide) — grep rendered HTML of every editor screen and public page. Implementation results: ${JSON.stringify(impl)} ${cross ? 'Cross: ' + JSON.stringify(cross) : ''}\n1. On a fresh site on port 9570: run ${W}/dev/tests/run-all.sh 9570 and ${W}/ops/ci.sh --no-zip — both must pass; fix failures (never weaken a regression test; wording updates are fine).\n2. FRESH RE-WALK as Frank (you have NOT seen the old screens — don't read the old screenshots first): redo the 20 first-click tasks from ${UX}/journeys.md and walk every journey end to end; record first click, clicks, success, hesitations; screenshot every editor screen into ${UX}/shots/final/. Then compare with the before results (first-click: ${JSON.stringify(firstClick ? firstClick.journeys_walked : [])}). Fix any remaining small issues (any file under ${W}/), re-run affected tests.\n3. Update ${W}/EDITING-GUIDE.md to match the new screens and words exactly (keep it short and friendly; refresh its screenshots: copy the relevant final shots into ${W}/shots/final/ with the file names the guide uses, or update the references) and PLAN.md §4 where the editor UX changed.\n4. Write ${UX}/REPORT.md and ${UX}/index.html (neutral, light/dark aware, relative links, file://): the brief; vocabulary table (old -> new); menu before/after; first-click before/after table; per-journey clicks before/after; per-screen before/after screenshots (before shots are in ${UX}/shots/walk-* dirs, after in ${UX}/shots/final/); findings disposition; test results. Verify it renders (headless Chrome screenshot, look at it).\nStop your server. Return a <=25 line summary: the biggest changes, first-click success before/after, clicks per key journey before/after, test results, anything left open.`,
  { label: 'verify', phase: 'Verify' })

return { findings_count: findings.length, spec: spec.summary, impl, verify }
