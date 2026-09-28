// DEV ONLY. Regression test for QA-027: on a phone (390x844) three of the
// plugin's edit forms ran past the right edge of the screen, so the page
// scrolled (or zoomed out) sideways and fields were cut off:
//
//   - Events > Meeting rules: every rule card (fieldset.spk-card.spk-rule)
//     was 436px wide (page 446px); the Jun/Dec "Skip these months" boxes and
//     the right edges of the Name / Time words / Extra words inputs were off
//     screen, and the administrator's "Needs checking" tick was cut short.
//   - An event's edit screen (SET 2026, an exercise): the "Main link" and
//     "More links" cards were 436-448px (page 458px), and the 400px "Extra
//     form" select ran past the edge.
//   - A document's edit screen (ICS-213) with "More options" open: the
//     "How to" link fieldset was 402px; with "Admin only" open, the "Extra
//     links" fieldset was 414px.
//
// Cause (assets/css/admin.css): a <fieldset> defaults to
// min-inline-size: min-content, and the fields inside have fixed widths
// (.regular-text is 25em = 400px, the Extra form select sizes to its longest
// title), so a fieldset cannot shrink below 400px + its padding. Net details
// (plain tables) fits at 390 and is the expected behaviour.
//
// Each test opens the screen at phone width as the account that uses it,
// opens the collapsed sections an editor would tap open, and checks that the
// page does not scroll sideways and that no field, label or card in the
// plugin's form reaches past the right edge of the screen. Nothing is saved.

/**
 * In the page: open the named <details>, then measure. Returns the viewport
 * width, the page's scroll width and every visible element inside `formSel`
 * whose right edge (in page coordinates) is past the viewport's right edge,
 * plus which of the `mustSee` selectors are missing or hidden (so the test
 * cannot pass by measuring a form that isn't there).
 */
function measure(formSel, mustSee) {
  const form = document.querySelector(formSel);
  if (!form) return { found: false, why: `${formSel} not found` };
  const vw = document.documentElement.clientWidth;
  const name = (el) => el.tagName.toLowerCase()
    + (el.id ? `#${el.id}` : '')
    + (typeof el.className === 'string' && el.className.trim() ? `.${el.className.trim().split(/\s+/).join('.')}` : '')
    + (el.getAttribute('name') ? `[name="${el.getAttribute('name')}"]` : '');
  const shown = (el) => {
    if (!el || el.closest('[hidden]')) return false;
    const r = el.getBoundingClientRect();
    return r.width > 0 && r.height > 0 && getComputedStyle(el).visibility !== 'hidden';
  };
  const missing = mustSee.filter((s) => !shown(document.querySelector(s)));
  const over = [];
  for (const el of form.querySelectorAll('fieldset, legend, p, div, label, input:not([type="hidden"]), select, textarea, button')) {
    if (!shown(el)) continue;
    const r = el.getBoundingClientRect();
    const right = Math.round(r.right + window.scrollX);
    if (right > vw + 1) over.push(`${name(el)} ${Math.round(r.width)}px wide, right edge at ${right}`);
  }
  return {
    found: true,
    vw,
    innerWidth: window.innerWidth,
    scrollWidth: document.documentElement.scrollWidth,
    missing,
    over,
  };
}

/**
 * Open a collapsed section (a <details>), as an editor does by tapping its
 * summary. It is opened in the page rather than with a mouse click: while the
 * bug is present the phone page is zoomed out to fit the too-wide form, and a
 * click at CSS-pixel coordinates can then miss the summary.
 */
async function openDetails(t, detailsSel) {
  t.expect(await t.exists(detailsSel), `${detailsSel} is on the page`).toBe(true);
  await t.evaluate((s) => { document.querySelector(s).open = true; }, detailsSel);
  await t.waitForFunction((s) => document.querySelector(s).open, { args: [detailsSel], timeout: 5000 });
}

/** Open a screen at phone width and check the plugin's form fits the screen. */
async function expectFits(t, { path, formSel, mustSee = [], open = [] }) {
  const size = await t.evaluate(() => ({ w: window.screen.width, h: window.screen.height }));
  t.expect(size.w, 'the test runs at phone width').toBe(390);

  await t.goto(path);
  await t.expectStatus(200);
  await t.waitFor(formSel);
  for (const d of open) await openDetails(t, d);

  const m = await t.evaluate(measure, formSel, mustSee);
  t.expect(m.found, `${path}: ${m.why || ''}`).toBe(true);
  t.expect(m.missing, `${path}: fields that should be on screen`).toEqual([]);

  const problems = [];
  if (m.scrollWidth > m.vw) problems.push(`the page scrolls sideways: scrollWidth ${m.scrollWidth} at ${m.vw} wide (window.innerWidth ${m.innerWidth})`);
  if (m.over.length) problems.push(`${m.over.length} element(s) past the right edge (${m.vw}px): ${m.over.slice(0, 8).join('; ')}`);
  t.expect(problems.length, `${path} at ${m.vw}px wide: ${problems.join(' | ')}`).toBe(0);

  t.expectNoConsoleErrors();
}

const RULES = '/wp-admin/admin.php?page=spokares-meeting-rules';
// The first rule card's fields, and its last two "Skip these months" boxes.
const RULE_FIELDS = [
  'fieldset.spk-rule',
  '#spk-rule-0-name',
  '#spk-rule-0-tt',
  '#spk-rule-0-hx',
  'fieldset.spk-rule fieldset.spk-months input[value="6"]',
  'fieldset.spk-rule fieldset.spk-months input[value="12"]',
];

export const tests = [
  {
    name: 'ares-net (phone): Meeting Schedule cards and fields fit a 390px screen',
    role: 'ares-net',
    viewport: 'phone',
    async run(t) {
      await expectFits(t, { path: RULES, formSel: '.spk-rules form', mustSee: RULE_FIELDS });
    },
  },
  {
    name: 'admin (phone): Meeting Schedule cards, fields and the "Needs checking" tick fit a 390px screen',
    role: 'admin',
    viewport: 'phone',
    async run(t) {
      await expectFits(t, {
        path: RULES,
        formSel: '.spk-rules form',
        mustSee: [...RULE_FIELDS, 'input[name="rules[0][needs_check]"]'],
      });
    },
  },
  {
    name: 'ares-editor (phone): an exercise\'s edit form (Button, More links, Document members need) fits a 390px screen',
    role: 'ares-editor',
    viewport: 'phone',
    async run(t) {
      await t.unlockPosts();
      const id = t.ids.events['set-2026'];
      t.expect(id, 'the SET 2026 event').toBeTruthy();
      await expectFits(t, {
        path: `/wp-admin/post.php?post=${id}&action=edit`,
        formSel: '.spk-event-form',
        mustSee: ['#spk-main-url', '#spk-main-label', 'input[name="spk_links[0][url]"]', '#spk-extra-doc'],
      });
    },
  },
  {
    name: 'ares-editor (phone): a document\'s edit form with More options open ("How to" link) fits a 390px screen',
    role: 'ares-editor',
    viewport: 'phone',
    async run(t) {
      await t.unlockPosts();
      const id = t.ids.documents['ics-213'];
      t.expect(id, 'the ICS-213 document').toBeTruthy();
      await expectFits(t, {
        path: `/wp-admin/post.php?post=${id}&action=edit`,
        formSel: '.spk-doc-form',
        open: ['.spk-doc-form details.spk-more:not(.spk-admin-only)'],
        mustSee: ['input[name="spk_howto_label"]', 'input[name="spk_howto_url"]'],
      });
    },
  },
  {
    name: 'admin (phone): a document\'s edit form with More options and Admin only open ("Extra links") fits a 390px screen',
    role: 'admin',
    viewport: 'phone',
    async run(t) {
      await t.unlockPosts();
      const id = t.ids.documents['ics-213'];
      t.expect(id, 'the ICS-213 document').toBeTruthy();
      await expectFits(t, {
        path: `/wp-admin/post.php?post=${id}&action=edit`,
        formSel: '.spk-doc-form',
        open: ['.spk-doc-form details.spk-more:not(.spk-admin-only)', '.spk-doc-form details.spk-admin-only'],
        mustSee: ['input[name="spk_howto_url"]', 'input[name="spk_sublinks[0][title]"]', 'input[name="spk_sublinks[0][url]"]'],
      });
    },
  },
];
