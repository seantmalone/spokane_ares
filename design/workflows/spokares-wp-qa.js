export const meta = {
  name: 'spokares-wp-qa',
  description: 'QA every public and custom-admin page as every user role: screenshot, evaluate, document issues, write failing regression tests for bugs, fix, re-verify',
  phases: [
    { title: 'Infra', detail: 'role users, crawler, PHP + E2E test runners, baseline crawl' },
    { title: 'Evaluate', detail: 'per-role evaluators + cross-cutting a11y; backfill tests for prior bugs' },
    { title: 'Triage', detail: 'dedupe, classify, assign fix groups -> ISSUES.md' },
    { title: 'Reproduce', detail: 'each bug: reproduce + write a failing regression test' },
    { title: 'Fix', detail: 'parallel fixers by file ownership, then cross-cutting fixer' },
    { title: 'Verify', detail: 'full test suite + full re-crawl, before/after, report' },
  ],
}

const R = '/Users/sean/Projects/spokane_ares'
const W = `${R}/wordpress`
const QA = `${W}/qa`
const TODAY = args.today

const CONTEXT = `PROJECT: the spokares.org WordPress build in ${W}/ — block theme ${W}/theme/spokares, site plugin ${W}/plugins/spokares-core, must-use plugin ${W}/mu-plugins/spokares-hardening, dev environment ${W}/dev (start.sh <PORT>, stop.sh <PORT>, checks.sh <PORT> = 50 HTTP checks, shots.mjs = Chrome DevTools-protocol screenshots using Node's built-in WebSocket, dev-only mu-plugin dev/mu-plugins/spokares-dev-login.php with ?dev_login=1|editor), ops/ci.sh (static release gate). Decision record: ${W}/PLAN.md (esp. §2 design mapping, §3 content model, §4 roles/locking, §5 security, §6 contract, §8 critique responses). Build notes: ${W}/build-notes/*.md (incl. the "Fixer round" in plugin.md). Editing guide: ${W}/EDITING-GUIDE.md. Reference design: ${R}/design/round3/option-b-lines-down/ (shots/ = intended look).
LOCAL WORDPRESS: ${W}/dev/start.sh <PORT> boots a fresh in-memory WordPress 7.1.2 (PHP 8.4) in Playground with all real content (~40-60s); logs in /tmp/pg-<PORT>.log and /tmp/pg-<PORT>-logs/{setup.log,debug.log}. ALWAYS use only your assigned port and ALWAYS stop it when done (${W}/dev/stop.sh <PORT>). Headless Chrome occasionally hangs on its self-updater tab: always pass a fresh --user-data-dir under /tmp, use timeouts, and kill stray Chrome processes you started.
ROLES TO COVER ("every user level"): anonymous visitor; subscriber; contributor; author; editor (WordPress core Editor role); ares_editor (the site's volunteer role, dev login "editor"); ares_editor + the per-user "Net details and Meeting rules" grant (spokares_edit_net_details); administrator. The site intends only administrator + ARES Editor accounts, but core roles exist in WordPress, so they must be safe (no errors, no access to things they shouldn't touch, no layout-lock bypass).
RULES: dev/test-only code lives under ${W}/dev/ and must never ship (ops/ci.sh checks shipped code — keep it passing). WordPress coding standards; escape/sanitize; nonces + caps. No personal contact details. Today is ${TODAY}.`

phase('Infra')
const INFRA = {
  type: 'object',
  properties: {
    crawl_cmd: { type: 'string' }, php_tests_cmd: { type: 'string' }, e2e_tests_cmd: { type: 'string' }, all_tests_cmd: { type: 'string' },
    baseline_report: { type: 'string', description: 'path to the baseline crawl report.json' },
    roles: { type: 'array', items: { type: 'object', properties: { key: { type: 'string' }, login_param: { type: 'string' }, url_count: { type: 'number' }, shots_dir: { type: 'string' } }, required: ['key', 'login_param', 'url_count', 'shots_dir'] } },
    automated_findings_summary: { type: 'string' },
    how_to_write_tests: { type: 'string', description: 'concise guide: file locations/naming, assertion API, how to act as a role, how to run one test' },
  },
  required: ['crawl_cmd', 'php_tests_cmd', 'e2e_tests_cmd', 'all_tests_cmd', 'baseline_report', 'roles', 'automated_findings_summary', 'how_to_write_tests'],
}
const infra = await agent(`${CONTEXT}\n\nYou are the QA INFRASTRUCTURE ENGINEER. Build, under ${W}/dev/ only (plus ${QA}/ for outputs):\n\n1. ROLE USERS: extend dev setup (dev/setup/setup.php) to create one dev user per role (keep existing admin + editor): e.g. qa-subscriber, qa-contributor, qa-author, qa-core-editor (core editor role), qa-ares-net (ares_editor + spokares_edit_net_details), all password "password", emails @example.invalid; extend the dev-login mu-plugin so ?dev_login=<one of these logins> signs in that user (still loopback/dev-only; keep ?dev_login=1 and =editor working).\n\n2. CRAWLER ${W}/dev/qa/crawl.mjs <PORT> <outdir> [--roles=a,b] (Node stdlib only; reuse/extract helpers from dev/shots.mjs): for each role, sign in via dev_login (anonymous = fresh profile), DISCOVER every reachable page: public pages (the six pages, 404, search results, /docs/<slug>/ samples, event anchors, any same-origin link found on public pages), and for logged-in roles every link in the rendered #adminmenu (incl. submenus), the dashboard, profile, each custom screen (Net rota, Net details, Regular meetings, Meeting rules, Hub tiles, ARES site settings), the Events and Documents list/add-new/edit-existing screens and document-category taxonomy screen, the block editor for Home/How it works/About, Pages list, Media, Users, and the Site Editor/Appearance screens — PLUS a "forbidden" list: every custom screen and key core screen attempted directly by roles that should NOT have access (record the response: proper denial vs error vs leak). For each role x URL capture: HTTP status + final URL, <title>, full-page screenshot at 1440 (public pages also 390x844 true mobile), console errors + uncaught exceptions, failed network requests (4xx/5xx assets), NEW PHP notices/warnings/fatals in /tmp/pg-<PORT>-logs/debug.log attributable to that request (diff the log per URL), horizontal overflow, WordPress admin error notices (.notice-error), block-editor invalid-content warnings, basic a11y signals (images without alt, empty links/buttons, missing h1 or multiple h1 on public pages, form fields without labels). Write <outdir>/report.json (role -> [{url, kind: public|admin|forbidden, status, shots, findings...}]) and <outdir>/summary.md. Robust: per-URL timeouts, continue on failure, kill Chrome at the end.\n\n3. TEST RUNNERS (for regression tests):\n   a) PHP integration tests: ${W}/dev/tests/php/*-test.php + a dev-only mu-plugin endpoint (loopback-only, dev-only like dev-login) that runs them INSIDE WordPress and returns JSON, + runner ${W}/dev/tests/run-php.sh <PORT> [filter] that prints PASS/FAIL per test and exits non-zero on failure. Tiny assertion API (assert_true, assert_same, assert_contains, expect_wp_error...), helpers to act as a role (wp_set_current_user with the dev users), to call save handlers / REST internally (rest_do_request), and to clean up created data.\n   b) Browser E2E tests: ${W}/dev/tests/e2e/*.test.mjs + runner ${W}/dev/tests/run-e2e.mjs <PORT> [filter] (Node stdlib + CDP, reuse crawl/shots helpers): API like loginAs(role), goto(path), click(selector), type(selector, text), evaluate(fn), waitFor(selector), expect helpers, screenshot on failure; each test file exports tests; runner prints PASS/FAIL, exits non-zero on failure.\n   c) ${W}/dev/tests/run-all.sh <PORT>: checks.sh + run-php.sh + run-e2e.mjs, summary, exit code.\n   Write 1-2 trivial passing sample tests per runner to prove they work. Document everything in ${W}/dev/tests/README.md.\n\n4. RUN a BASELINE: start on port 9600, run run-all.sh (must pass), then the crawler for ALL roles into ${QA}/runs/baseline/ (screenshots under ${QA}/shots/baseline/<role>/ — make the crawler take a --shots dir). Stop the server. Keep ops/ci.sh passing (run it with --offline --no-zip if the online steps are slow).\nReturn the structured result (roles = one entry per role with its dev login parameter and shots dir).`,
  { label: 'qa-infra', phase: 'Infra', schema: INFRA })

log(`Infra ready: ${infra.roles.map(r => `${r.key}(${r.url_count})`).join(', ')}`)

phase('Evaluate')
const ISSUES = {
  type: 'object',
  properties: {
    issues: { type: 'array', items: { type: 'object', properties: {
      title: { type: 'string' },
      severity: { type: 'string', enum: ['critical', 'high', 'medium', 'low'] },
      category: { type: 'string', enum: ['bug', 'security', 'accessibility', 'visual', 'copy', 'ux', 'performance'] },
      roles: { type: 'array', items: { type: 'string' } },
      url: { type: 'string' },
      steps: { type: 'string' }, expected: { type: 'string' }, actual: { type: 'string' },
      evidence: { type: 'array', items: { type: 'string' }, description: 'screenshot paths / log lines' },
      suspected_files: { type: 'array', items: { type: 'string' } },
    }, required: ['title', 'severity', 'category', 'roles', 'url', 'steps', 'expected', 'actual', 'evidence', 'suspected_files'] } },
    pages_reviewed: { type: 'number' },
    notes: { type: 'string' },
  },
  required: ['issues', 'pages_reviewed', 'notes'],
}
const EVALUATORS = [
  { key: 'anonymous', port: 9611, focus: 'ANONYMOUS VISITOR on all public pages at 1440 and 390: content correctness vs the reference design and the data, links (every link resolves), dynamic lists (events, rota, meetings, documents, net settings) showing correct/current data, 404 and search pages, /docs/ redirects, interactive widgets (menu, copy radio settings, document filter/search, About TOC), keyboard use, no leaks of drafts/private data, no admin bar/edit links.' },
  { key: 'subscriber', port: 9612, focus: 'SUBSCRIBER: what they see in wp-admin (should be minimal), profile screen, every forbidden screen attempted directly (proper denial, no PHP errors), front end while logged in (no edit links/tools they cannot use).' },
  { key: 'contributor', port: 9613, focus: 'CONTRIBUTOR: admin menu, what they can create/edit (posts? events? documents? should they?), forbidden screens, front end logged in.' },
  { key: 'author', port: 9614, focus: 'AUTHOR: admin menu, media uploads (allowed types — HTML/SVG/PDF rules), posts, access to custom screens, forbidden screens, front end logged in.' },
  { key: 'core-editor', port: 9615, focus: 'WORDPRESS CORE EDITOR role: can they bypass the page layout lock, change templates/passwords/status, edit members templates or theme patterns, reach custom screens, upload risky files, edit events/documents without the guard rails? Anything the ARES Editor is protected from but core Editor is not is a finding.' },
  { key: 'ares-editor', port: 9616, focus: 'ARES EDITOR (dev login "editor"): EVERY custom screen and editing workflow end to end — add/edit/trash events (each kind), rota for this and next month (Call sign/Open/Not posted, undo), meeting cancellations/moves, documents (upload with privacy tick, external URL, replace, trash), hub tiles, editing Home/How it works/About text in the block editor (and trying to break layout), profile — with VALID and INVALID inputs (empty, too long, bad dates, names in call-sign fields, phone numbers, HTML/script injection attempts). Verify every save shows the right notice and the front end updates. Denied screens (Net details without the grant) must fail gracefully with guidance.' },
  { key: 'ares-editor-net', port: 9617, focus: 'ARES EDITOR WITH THE NET-DETAILS GRANT: Net details and Meeting rules screens end to end with valid/invalid inputs (frequency/offset/tone formats, times, rules), live previews, that every public place updates, and that removing the grant (as admin) removes access cleanly.' },
  { key: 'admin-custom', port: 9618, focus: 'ADMINISTRATOR on every CUSTOM screen and content type: all of the above plus ARES site settings, Users > profile grant checkbox, document categories, the dashboard widget, admin-only layout editing of Home/How/About in the block editor (admins may change layout — does it still validate?), bulk actions on events/documents, trash/restore/delete permanently flows, list-table columns/sorting/filters.' },
  { key: 'admin-core', port: 9619, focus: 'ADMINISTRATOR on CORE screens as they interact with this build: Dashboard, Appearance (Site Editor, templates, patterns, styles, navigation), Plugins, Tools, Settings (general/reading/permalinks/media/privacy), Users list/add, Media, Pages list (incl. members pages that are theme templates), Two-Factor settings availability — errors, notices, broken UI, confusing states caused by our code, and anything the hardening mu-plugin breaks.' },
  { key: 'a11y-responsive', port: 9620, focus: 'CROSS-CUTTING ACCESSIBILITY + RESPONSIVE: public pages at 360/390/768/1024/1440 and the custom admin screens at 390 and 1440 — keyboard-only navigation (focus order, visible focus, skip link, menu toggle, TOC, filters, admin form controls incl. the rota radio/text combo), screen-reader semantics (landmarks, headings, labels, aria on dynamic widgets, live regions for notices), colour contrast (compute it), reduced motion, zoom 200%, horizontal scroll.' },
]
const evalPrompt = e => `${CONTEXT}\n\nYou are a QA EVALUATOR. FOCUS — ${e.focus}\n\nInfrastructure (already built): crawl: ${infra.crawl_cmd}; baseline crawl report: ${infra.baseline_report} (screenshots per role listed in it; roles: ${JSON.stringify(infra.roles)}); automated findings summary: ${infra.automated_findings_summary}\n\nDO: (1) Review the baseline screenshots and automated findings for the role(s) in your focus — LOOK at every relevant screenshot with the Read tool and judge quality (errors, broken layout, confusing UI, wrong/missing data, copy problems, visual regressions vs the reference design). (2) Start your own server on port ${e.port} and actively exercise the pages/flows in your focus (use the crawler with --roles, the E2E helpers, curl with a cookie jar after ?dev_login=..., and screenshots into ${QA}/shots/eval-${e.key}/). Check /tmp/pg-${e.port}-logs/debug.log after each action. (3) Report EVERY issue you find with precise repro steps, expected vs actual, evidence paths and suspected files — do NOT fix anything. Be thorough but only report real issues (not taste) and mark severity honestly. Stop your server when done.`
const backfillPrompt = `${CONTEXT}\n\nYou are the TEST BACKFILL ENGINEER. The previous review round fixed 9 must-fix bugs WITHOUT regression tests (see the "Fixer round" section in ${W}/build-notes/plugin.md and PLAN.md §8): page password/date/author/status guard for non-admins; page template changes by non-admins; Quick Edit / bulk edit on pages; Save box hidden via Screen Options; net-rota call-sign box unresponsive; multi-paragraph paste; event "Where" field + save notice; orphaned document attachments reachable by ID; CI release gate false pass. For each, check whether ${W}/dev/checks.sh already covers it; where not, write a regression test with the new runners (${infra.how_to_write_tests}) in its own file named dev/tests/{php,e2e}/prior-<short-name>-test.* . Each test must pass on the current code — and must FAIL if the fix were reverted (prove it by temporarily reverting the relevant lines in a scratch copy or by reasoning precisely about the assertion; restore everything). Use port 9621 only; stop it when done. Return a list of tests added and what each covers.`

const [evals, backfill] = await Promise.all([
  parallel(EVALUATORS.map(e => () => agent(evalPrompt(e), { label: `eval:${e.key}`, phase: 'Evaluate', schema: ISSUES }))),
  agent(backfillPrompt, { label: 'backfill-tests', phase: 'Evaluate' }),
])
const rawIssues = evals.flatMap((r, i) => r ? r.issues.map(x => ({ ...x, reporter: EVALUATORS[i].key })) : [])
log(`Evaluators reported ${rawIssues.length} raw issues across ${evals.filter(Boolean).reduce((a, r) => a + r.pages_reviewed, 0)} page reviews`)

phase('Triage')
const TRIAGE = {
  type: 'object',
  properties: {
    issues: { type: 'array', items: { type: 'object', properties: {
      id: { type: 'string', description: 'QA-001 ...' },
      title: { type: 'string' },
      kind: { type: 'string', enum: ['bug', 'quality', 'by-design', 'needs-decision'] },
      severity: { type: 'string', enum: ['critical', 'high', 'medium', 'low'] },
      category: { type: 'string' },
      roles: { type: 'array', items: { type: 'string' } },
      summary: { type: 'string', description: 'repro + expected vs actual, merged from reporters' },
      group: { type: 'string', enum: ['theme', 'plugin-admin', 'plugin-front', 'governance-security', 'dev-docs', 'cross'] },
      files: { type: 'array', items: { type: 'string' } },
      test_type: { type: 'string', enum: ['php', 'e2e', 'none'], description: 'for bugs: which runner a regression test belongs in' },
      rationale: { type: 'string', description: 'why this kind/group; for by-design cite PLAN.md' },
    }, required: ['id', 'title', 'kind', 'severity', 'category', 'roles', 'summary', 'group', 'files', 'test_type', 'rationale'] } },
    group_paths: { type: 'object', description: 'group -> list of path globs it owns (non-overlapping)' },
  },
  required: ['issues', 'group_paths'],
}
const triage = await agent(`${CONTEXT}\n\nYou are the QA LEAD. Triage these ${rawIssues.length} raw issues from 10 evaluators:\n${JSON.stringify(rawIssues)}\n\nDedupe (merge the same root cause), assign stable ids QA-001.. ordered by severity, and classify: bug (incorrect behaviour, errors/notices, broken UI, security/permission flaws, data problems, a11y failures that block use), quality (visual/copy/UX/a11y improvements that aren't defects), by-design (intended per PLAN.md — cite the section), needs-decision (owner/EC choice). Assign each actionable issue to ONE fix group with NON-OVERLAPPING file ownership: theme (${W}/theme/spokares/**), plugin-admin (plugin admin screens/forms/admin JS+CSS), plugin-front (plugin blocks, render.php, front-end JS/CSS), governance-security (roles, governance/lock, capabilities, ${W}/mu-plugins/**), dev-docs (${W}/dev/** except tests, ${W}/ops/**, docs .md), cross (needs files from 2+ groups — handled last). Inspect the actual file tree to set group_paths precisely. For bugs set test_type. WRITE ${QA}/ISSUES.md (summary table by kind/severity, then each issue: id, title, kind, severity, roles, repro, expected/actual, evidence, group, status "open") and ${QA}/issues.json. Return structured triage.`,
  { label: 'triage', phase: 'Triage', schema: TRIAGE })

const bugs = triage.issues.filter(i => i.kind === 'bug')
const actionable = triage.issues.filter(i => i.kind === 'bug' || i.kind === 'quality')
log(`Triage: ${triage.issues.length} issues — ${bugs.length} bugs, ${triage.issues.filter(i => i.kind === 'quality').length} quality, ${triage.issues.filter(i => i.kind === 'by-design').length} by-design, ${triage.issues.filter(i => i.kind === 'needs-decision').length} needs-decision`)

phase('Reproduce')
const REPRO = { type: 'object', properties: { id: { type: 'string' }, reproduced: { type: 'boolean' }, test_file: { type: 'string' }, fails_now: { type: 'boolean' }, notes: { type: 'string' } }, required: ['id', 'reproduced', 'test_file', 'fails_now', 'notes'] }
const repro = await parallel(bugs.map((b, i) => () =>
  agent(`${CONTEXT}\n\nYou are a BUG REPRODUCER (adversarial: try to prove the report wrong). Bug:\n${JSON.stringify(b, null, 1)}\n\nUsing ONLY port ${9700 + i} (stop it when done): reproduce the bug on a fresh dev site. If it does NOT reproduce, say so with evidence (reproduced=false, test_file=""). If it does, write a REGRESSION TEST that captures the correct behaviour, in its own file: ${W}/dev/tests/${b.test_type === 'e2e' ? 'e2e' : 'php'}/${b.id.toLowerCase()}-<short-name>${b.test_type === 'e2e' ? '.test.mjs' : '-test.php'} (use test_type "${b.test_type}"; switch runner if the other fits better and say why). How to write tests: ${infra.how_to_write_tests}\nRun just your test and CONFIRM IT FAILS on the current code for the right reason (fails_now=true). Do NOT fix the bug. Return structured result.`,
    { label: `repro:${b.id}`, phase: 'Reproduce', schema: REPRO })))
const reproMap = Object.fromEntries(repro.filter(Boolean).map(r => [r.id, r]))
const notRepro = bugs.filter(b => reproMap[b.id] && !reproMap[b.id].reproduced).map(b => b.id)
log(`Reproduced ${bugs.length - notRepro.length}/${bugs.length} bugs; not reproduced: ${notRepro.join(', ') || 'none'}`)

phase('Fix')
const toFix = actionable.filter(i => !notRepro.includes(i.id))
const GROUPS = ['theme', 'plugin-admin', 'plugin-front', 'governance-security', 'dev-docs']
const FIXED = { type: 'object', properties: { results: { type: 'array', items: { type: 'object', properties: { id: { type: 'string' }, status: { type: 'string', enum: ['fixed', 'partially-fixed', 'deferred'] }, notes: { type: 'string' }, test_passes: { type: 'boolean' } }, required: ['id', 'status', 'notes', 'test_passes'] } } }, required: ['results'] }
const fixPrompt = (group, items, port, owned) => `${CONTEXT}\n\nYou are the FIXER for group "${group}". You may ONLY edit files in: ${JSON.stringify(owned)} (other fixers are editing other areas in parallel). Issues to fix:\n${JSON.stringify(items.map(i => ({ ...i, regression_test: reproMap[i.id] ? reproMap[i.id].test_file : null })), null, 1)}\n\nFor each BUG: make its regression test pass (run it with the runners; see ${W}/dev/tests/README.md) without weakening the test. For QUALITY issues: fix them cleanly, consistent with PLAN.md and the Option B design. If a fix truly needs a file outside your ownership, don't edit it — mark the issue "deferred" with the needed change described precisely (it will go to the cross-cutting fixer). Use only port ${port}; at the end run ${infra.all_tests_cmd.replace(/\d{4}/, String(port))} (or the equivalent with port ${port}) and ensure you broke nothing. Stop your server. Return per-issue results.`
const groupRuns = await parallel(GROUPS.map((g, gi) => () => {
  const items = toFix.filter(i => i.group === g)
  if (!items.length) return Promise.resolve({ results: [] })
  return agent(fixPrompt(g, items, 9801 + gi, (triage.group_paths && triage.group_paths[g]) || [g]), { label: `fix:${g}`, phase: 'Fix', schema: FIXED })
}))
const groupResults = groupRuns.filter(Boolean).flatMap(r => r.results)
const deferred = groupResults.filter(r => r.status !== 'fixed').map(r => r.id)
const crossItems = toFix.filter(i => i.group === 'cross' || deferred.includes(i.id))
let crossRun = { results: [] }
if (crossItems.length) {
  crossRun = await agent(`${fixPrompt('cross', crossItems, 9810, ['anything under ' + W])}\nNotes from group fixers on deferred items: ${JSON.stringify(groupResults.filter(r => deferred.includes(r.id)))}`,
    { label: 'fix:cross', phase: 'Fix', schema: FIXED }) || { results: [] }
}
const fixResults = [...groupResults.filter(r => !crossItems.some(c => c.id === r.id)), ...crossRun.results]
log(`Fix pass: ${fixResults.filter(r => r.status === 'fixed').length} fixed, ${fixResults.filter(r => r.status !== 'fixed').length} not fully fixed`)

phase('Verify')
const final = await agent(`${CONTEXT}\n\nYou are the FINAL VERIFIER. Using port 9900 only (stop it at the end):\n1. Run the full suite (${infra.all_tests_cmd} with port 9900) and ops/ci.sh --no-zip (or --offline if needed). Every regression test must pass. If anything fails, fix it (you may edit any file) and re-run until green — never weaken or delete a regression test to make it pass.\n2. Re-crawl ALL roles into ${QA}/runs/final/ (shots ${QA}/shots/final/<role>/) and compare against ${QA}/runs/baseline/report.json: new errors, notices, console errors, failed requests, overflow, a11y signals, forbidden-access outcomes. Fix regressions. LOOK at the final screenshots for every page each role can reach and confirm the fixed issues visually.\n3. Update ${QA}/ISSUES.md and ${QA}/issues.json: status per issue (fixed + test file / not reproduced / deferred with reason / by-design / needs-decision), using: repro results ${JSON.stringify(repro.filter(Boolean))}; fix results ${JSON.stringify(fixResults)}.\n4. Write ${QA}/REPORT.md and ${QA}/index.html (neutral, light/dark aware, relative links, opens via file://): scope (roles x pages covered, counts), before/after automated-findings table per role, issues by kind/severity with status, a per-role screenshot gallery of the final crawl (thumbnails linking to full images), the test suite summary (counts per runner, list of regression tests and the issue each covers, incl. backfilled prior-bug tests: ${JSON.stringify(backfill)}), and the open needs-decision items. Verify it renders with headless Chrome and look at it.\nReturn a <=25 line summary: coverage, issue counts by kind/severity and status, tests added, anything still failing or deferred, and the needs-decision list.`,
  { label: 'final-verify', phase: 'Verify' })

return { roles: infra.roles, counts: { raw: rawIssues.length, triaged: triage.issues.length, bugs: bugs.length, not_reproduced: notRepro }, fixes: fixResults, summary: final }
