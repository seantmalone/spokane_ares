// DEV ONLY. Regression test for QA-028: the ARES Editors' main table screens
// don't reflow. assets/css/admin.css has a stacked, labelled-card layout
// (below 783px) for the Net rota (.spk-rota-table) only. Hub tiles
// (.spk-tiles-table) and Regular meetings (.spk-meetings-table) stay as
// wide tables on a phone, and the Net rota is too wide just above that
// breakpoint. Measured on a fresh dev site, the same for ares-editor,
// ares-net and admin:
//
//   Hub tiles         390/720/782: table 994px, page 1004px wide
//                     783: 989   1024 and 1100: table 931px, page 1113px
//   Regular meetings  390/720/782: table 825px, page 835px (Note off screen)
//                     783: page 818px
//   Net rota          783: table 885px, page 943px
//                     1024: table 885px in an 822px wrap, page 1067px
//                     (Note inputs clipped). It fits at 1100 and up.
//
// On a phone, Tab to "Words for slot 1" (now "Words on button 1") or a meeting Note moves focus to a
// field far off the right edge, so the page pans sideways and the row's
// heading and slot disappear (WCAG 1.4.10 Reflow; 720px is 1440px at 200%
// zoom).
//
// Correct behaviour, checked here: at 390, 720, 783 and 1024px wide each of
// the three screens fits the window (no sideways scroll). Every field in the
// table is inside the window and its column name can still be seen: the
// column heading is on screen, or the cell has its own label (the rota's
// data-label ::before, or a visible <label>). On a phone, Tab to a field
// keeps it on screen and the page does not pan. Nothing is saved.

const SCREENS = {
  'Hub tiles': { path: '/wp-admin/edit.php?post_type=spk_document&page=spokares-tiles', table: '.spk-tiles-table' },
  'Regular meetings': { path: '/wp-admin/admin.php?page=spokares-meetings', table: '.spk-meetings-table' },
  'Net rota': { path: '/wp-admin/admin.php?page=spokares-rota', table: '.spk-rota-table' },
};

// 390 = a phone, 720 = 1440 at 200% zoom (both under WordPress's 782px
// breakpoint), 783 = just above it (admin menu open: editors keep its labels, UX spec §2), 1024 = a small
// laptop or a tablet on its side (admin menu open).
const WIDTHS = [
  { width: 390, height: 844, mobile: true },
  { width: 720, height: 900, mobile: false },
  { width: 783, height: 900, mobile: false },
  { width: 1024, height: 900, mobile: false },
];

/**
 * In the page: how the table sits in the window. Positions are page
 * coordinates (scroll offset added), so a panned page still counts.
 */
function layout(tableSel) {
  const de = document.documentElement;
  const vw = de.clientWidth;
  const table = document.querySelector(tableSel);
  if (!table) return { found: false };
  const px = (n) => Math.round(n);
  const visible = (el) => {
    if (el.closest('[hidden], .screen-reader-text')) return false;
    const cs = getComputedStyle(el);
    if (cs.display === 'none' || cs.visibility === 'hidden') return false;
    const r = el.getBoundingClientRect();
    return r.width > 0 && r.height > 0;
  };
  const box = (el) => {
    const r = el.getBoundingClientRect();
    return { left: r.left + window.scrollX, right: r.right + window.scrollX };
  };
  const onScreen = (b) => b.left >= -0.5 && b.right <= vw + 0.5;
  const heads = [...table.querySelectorAll('thead th')];
  // Visible words in an element, leaving out screen-reader-only text.
  const seenText = (el) => {
    let s = '';
    const walker = document.createTreeWalker(el, NodeFilter.SHOW_TEXT);
    for (let n = walker.nextNode(); n; n = walker.nextNode()) {
      if (n.parentElement.closest('.screen-reader-text, [hidden]')) continue;
      s += n.nodeValue;
    }
    return s.trim();
  };
  const cellLabel = (cell) => {
    const before = getComputedStyle(cell, '::before').content;
    if (before && !['none', 'normal', '""', "''"].includes(before)) return true;
    return [...cell.querySelectorAll('label, legend, .spk-label')].some((l) => visible(l) && seenText(l) !== '');
  };
  const offscreen = [];
  const unlabelled = [];
  const controls = [...table.querySelectorAll('input:not([type="hidden"]), select, textarea')].filter(visible);
  for (const c of controls) {
    const name = c.getAttribute('aria-label') || c.name;
    const b = box(c);
    if (!onScreen(b)) offscreen.push(`${name} (x ${px(b.left)}-${px(b.right)})`);
    const cell = c.closest('td, th');
    if (!cell) continue;
    const head = heads[cell.cellIndex];
    const headSeen = !!head && visible(head) && visible(table.tHead) && onScreen(box(head));
    if (!headSeen && !cellLabel(cell)) unlabelled.push(`${name} (column "${head ? head.textContent.trim() : '?'}")`);
  }
  return {
    found: true,
    vw,
    page: de.scrollWidth,
    table: px(table.getBoundingClientRect().width),
    wrap: px(document.querySelector('.wrap')?.getBoundingClientRect().width || 0),
    controls: controls.length,
    offscreen,
    unlabelled,
  };
}

/** Open one screen at each width; return one sentence per problem. */
async function reflowProblems(t, screen) {
  const { path, table } = SCREENS[screen];
  const problems = [];
  for (const view of WIDTHS) {
    await t.setViewport(view);
    await t.goto(path);
    await t.expectStatus(200);
    const m = await t.evaluate(layout, table);
    t.expect(m.found, `${screen} ${table} at ${view.width}px`).toBe(true);
    t.expect(m.controls, `${screen} fields found at ${view.width}px`).toBeGreaterThan(0);
    const at = `${screen} at ${view.width}px`;
    if (m.page > m.vw) problems.push(`${at}: page is ${m.page}px wide in a ${m.vw}px window (table ${m.table}px, wrap ${m.wrap}px)`);
    if (m.offscreen.length) problems.push(`${at}: ${m.offscreen.length} field(s) outside the window, e.g. ${m.offscreen.slice(0, 2).join('; ')}`);
    if (m.unlabelled.length) problems.push(`${at}: ${m.unlabelled.length} field(s) with no column name on screen, e.g. ${m.unlabelled.slice(0, 2).join('; ')}`);
  }
  t.expectNoConsoleErrors();
  return problems;
}

/** Fail with every problem listed (an expect label is printed in full). */
function expectNoProblems(t, problems) {
  t.expect(problems.length, `reflow problems:\n        ${problems.join('\n        ')}\n      count`).toBe(0);
}

/**
 * On the phone: focus the "View on site" link, then press Tab (as a keyboard
 * user does) until the field that `markSel` finds has focus. Returns where
 * it ended up and whether the page panned.
 */
async function tabTo(t, markSel) {
  const name = await t.evaluate((sel) => {
    const el = document.querySelector(sel);
    if (!el) return null;
    el.setAttribute('data-qa028-target', '1');
    document.querySelector('.wrap .page-title-action')?.focus();
    window.scrollTo(0, 0);
    return el.getAttribute('aria-label') || el.name;
  }, markSel);
  t.expect(name, `field ${markSel}`).toBeTruthy();
  for (let i = 0; i < 40; i++) {
    await t.press('Tab');
    const hit = await t.evaluate(() => !!document.activeElement?.hasAttribute('data-qa028-target'));
    if (hit) break;
  }
  const got = await t.evaluate(() => {
    const el = document.activeElement;
    const r = el.getBoundingClientRect();
    return {
      reached: el.hasAttribute('data-qa028-target'),
      left: Math.round(r.left + window.scrollX),
      right: Math.round(r.right + window.scrollX),
      scrollX: Math.round(window.scrollX),
      pan: Math.round(window.visualViewport ? window.visualViewport.pageLeft : window.scrollX),
      vw: document.documentElement.clientWidth,
    };
  });
  t.expect(got.reached, `Tab reaches "${name}"`).toBe(true);
  return { name, ...got };
}

export const tests = [
  {
    name: 'ares-editor: Hub tiles fit the window at 390, 720, 783 and 1024px, every field on screen with its column name',
    role: 'ares-editor',
    timeout: 120000,
    async run(t) {
      expectNoProblems(t, await reflowProblems(t, 'Hub tiles'));
    },
  },
  {
    name: 'ares-editor: Regular meetings fit the window at 390, 720, 783 and 1024px, every field on screen with its column name',
    role: 'ares-editor',
    timeout: 120000,
    async run(t) {
      expectNoProblems(t, await reflowProblems(t, 'Regular meetings'));
    },
  },
  {
    name: 'ares-editor: Net rota fits the window at 390, 720, 783 and 1024px, every field on screen with its column name',
    role: 'ares-editor',
    timeout: 120000,
    async run(t) {
      expectNoProblems(t, await reflowProblems(t, 'Net rota'));
    },
  },
  {
    name: 'ares-editor (phone): Tab to "Words on button 1" and to a meeting Note keeps the field on screen without panning',
    role: 'ares-editor',
    viewport: 'phone',
    async run(t) {
      const problems = [];
      const check = (screen, f) => {
        if (f.right > f.vw || f.left < 0) problems.push(`${screen}: focused "${f.name}" is at x ${f.left}-${f.right} on a ${f.vw}px screen`);
        if (f.scrollX > 0 || f.pan > 0) problems.push(`${screen}: focusing "${f.name}" panned the page ${Math.max(f.scrollX, f.pan)}px sideways`);
      };

      await t.goto(SCREENS['Hub tiles'].path);
      await t.expectStatus(200);
      check('Hub tiles', await tabTo(t, '.spk-tiles-table input[aria-label="Words on button 1"]'));

      await t.goto(SCREENS['Regular meetings'].path);
      await t.expectStatus(200);
      check('Regular meetings', await tabTo(t, '.spk-meetings-table input[aria-label^="Note for"]'));

      t.expectNoConsoleErrors();
      expectNoProblems(t, problems);
    },
  },
];
