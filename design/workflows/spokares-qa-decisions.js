export const meta = {
  name: 'spokares-qa-decisions',
  description: 'Implement the owner decisions on QA-038/039/103/104 with regression tests, bump to 0.1.1, verify the full suite',
  phases: [
    { title: 'Implement', detail: 'plugin settings (038/039) and phone ordering (103/104) in parallel, disjoint files' },
    { title: 'Verify', detail: 'version 0.1.1, full suite, ci gate, ISSUES/REPORT update' },
  ],
}

const R = '/Users/sean/Projects/spokane_ares'
const W = `${R}/wordpress`

const CONTEXT = `PROJECT: the spokares.org WordPress build in ${W}/ (theme ${W}/theme/spokares, plugin ${W}/plugins/spokares-core, mu-plugin ${W}/mu-plugins/spokares-hardening). QA just finished: ${W}/qa/ISSUES.md, ${W}/qa/issues.json, ${W}/qa/REPORT.md. Test runners: ${W}/dev/tests/README.md (run-php.sh, run-e2e.mjs, run-all.sh <PORT>); dev site: ${W}/dev/start.sh <PORT> / stop.sh <PORT>. Decision record: ${W}/PLAN.md.
The OWNER DECIDED (directly, ${args.today}) on the four open QA items:
- QA-038: REMOVE the unused groups.io address fields from Settings > ARES site (settings registration, sanitizing, form, defaults, any seed/setup references, docs). groups.io links in page text stay as they are.
- QA-039: W7GBU is fixed (the club licence). Make the repeater CALL-SIGN field in Net details ADMIN-ONLY: users with only the Net details grant (spokares_edit_net_details) see it read-only and cannot change it — enforced SERVER-SIDE on save, not just in the form. Admins can still change it.
- QA-103: fix the phone header keyboard/reading order IN THE MARKUP for every browser: DOM order must equal visual order with no CSS order/flex-direction/grid-placement reordering; use a second "Join the team" button inside the phone menu if needed (the visible bar keeps its own). Desktop must look the same as now.
- QA-104: fix the members hub phone reading order IN THE MARKUP: put the Tuesday net first in the DOM at EVERY width (no CSS reordering), keeping the desktop hub looking as close to now as practical.
RULES: bugs get regression tests that FAIL before the change and PASS after (for 103/104 assert DOM order / focus order, which is browser-independent, not pixels). WordPress coding standards; escape/sanitize; nonces + caps. Never weaken existing tests. Keep ops/ci.sh passing. Today is ${args.today}.`

const RES = { type: 'object', properties: { items: { type: 'array', items: { type: 'object', properties: { id: { type: 'string' }, done: { type: 'boolean' }, tests: { type: 'array', items: { type: 'string' } }, notes: { type: 'string' } }, required: ['id', 'done', 'tests', 'notes'] } } }, required: ['items'] }

phase('Implement')
const [plugin, theme] = await parallel([
  () => agent(`${CONTEXT}\n\nYou own ONLY ${W}/plugins/spokares-core/inc/**, ${W}/plugins/spokares-core/assets/js/admin-*, ${W}/plugins/spokares-core/assets/css/admin*, ${W}/dev/seed/**, ${W}/dev/setup/**, and new test files under ${W}/dev/tests/{php,e2e}/ named qa-038-* / qa-039-*. Implement QA-038 and QA-039. Write the failing tests first, confirm they fail, implement, confirm they pass. Use ONLY port 9531 for your dev site and stop it when done. Return per-item results.`,
    { label: 'impl:plugin-038-039', phase: 'Implement', schema: RES }),
  () => agent(`${CONTEXT}\n\nInvoke the Skill tool with skill "frontend-design:frontend-design" before changing layout, and keep the Option B look. You own ONLY ${W}/theme/spokares/**, ${W}/plugins/spokares-core/blocks/** (members hub / net block markup if the hub order lives there), and new test files under ${W}/dev/tests/{php,e2e}/ named qa-103-* / qa-104-*. Implement QA-103 and QA-104. Update any existing tests that asserted the old CSS-reordering behaviour only if they encoded the now-rejected approach (explain each). Write the failing tests first, confirm they fail, implement, confirm they pass; then check the header and hub visually at 390, 768, 1024 and 1440 against the current look (screenshots via the dev scripts). Use ONLY port 9532 and stop it when done. Return per-item results.`,
    { label: 'impl:theme-103-104', phase: 'Implement', schema: RES }),
])

phase('Verify')
const verify = await agent(`${CONTEXT}\n\nYou are the VERIFIER (you may edit any file under ${W}/ to fix what you find). Implementer results: plugin ${JSON.stringify(plugin)}; theme ${JSON.stringify(theme)}.\n1. Bump the release version to 0.1.1 everywhere ops/ci.sh's version-agreement check reads (all 8 strings; read ci.sh to find them) so tag v0.1.1 will pass the gate.\n2. On a fresh dev site on port 9533: run dev/tests/run-all.sh (all must pass) and ops/ci.sh --no-zip (must pass; use --offline only if the online steps fail for network reasons, and say so). Fix any failure — never weaken or delete a regression test.\n3. Confirm the four decisions visually and behaviourally (screenshots of the header and members hub at 390 and 1440; the Net details screen as the grant-only user and as admin; Settings > ARES site without the groups.io fields).\n4. Update ${W}/qa/ISSUES.md, ${W}/qa/issues.json and ${W}/qa/REPORT.md: QA-038, QA-039, QA-103, QA-104 -> fixed per the owner's decision, with their test files; update the counts. Update PLAN.md / EDITING-GUIDE.md where they describe the changed behaviour.\nStop your server. Return a <=15 line summary: test counts per runner, ci result, what changed for each decision, files touched.`,
  { label: 'verify', phase: 'Verify' })

return { plugin, theme, verify }
