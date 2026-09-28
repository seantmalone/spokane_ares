// DEV ONLY. Regression test for QA-067: buttons, tags and short runs of text
// that site.css keeps on one line (white-space: nowrap on .btn and the core
// Button link, .tag, .nowrap, and the hub rota's row headings) ran past their
// card or column, and past the right edge of the screen, where
// .wp-site-blocks { overflow-x: clip } cut them off with no way to scroll to
// them. The page's scrollWidth stayed at the screen width, so the crawler's
// overflow check did not see it.
//
// Found on a fresh dev site (2026-09-27):
//   - WCAG 1.4.10 Reflow, 320 CSS px wide: on /members/exercises/ the first
//     Next up card's red "Exercise details on groups.io" button was 292px
//     wide in a ~250px card column; its right edge was at 329 on a 320px
//     screen, cut off at the screen edge. On /about/ the "Email the
//     Emergency Coordinator" button was 302px wide in the 288px column (right
//     edge at 318, 14px into the side gutter).
//   - WCAG 1.4.12 Text spacing (line-height 1.5, letter-spacing .12em,
//     word-spacing .16em, paragraph spacing 2em) at 320 and 390: the same
//     button, Home's "Optional" tag (right edge 369 at 320), About's "Email
//     the Emergency Coordinator" and "Get the ARES application" buttons (385
//     and 350 at 320), and the hub's rota table (328px in a 288px column: its
//     row headings, header cells and <time>s are all nowrap).
//   - The browser's default text size at 200% (Chrome's font-size setting,
//     32px), 390 wide: on /members/ the net bar's nowrap
//     "W7GBU 147.300 MHz," was 372px wide and ran 32px past the 358px bar;
//     Home's "Optional" tag was pushed off the screen (right edge at 447) by
//     its step's auto-width title column.
//
// Each test opens the six public pages (as a visitor, and once signed in as
// the ARES Editor: the CSS is the same for every role) and checks that every
// visible .btn, core Button link, .tag and .nowrap (plus the hub rota table
// under text spacing) stays inside every block box around it and inside the
// screen. A scroll container (overflow-x: auto/scroll) counts as the edge:
// what is inside it can be scrolled to. Nothing is saved.

const PAGES = ['/', '/how-it-works/', '/about/', '/members/', '/members/documents/', '/members/exercises/'];

// Things the check must find, so it cannot pass by measuring a page that
// lost the element the bug was about.
const MUST_SEE = {
  '/': ['.steps__item > .tag'],
  '/about/': ['.wp-block-button__link'],
  '/members/': ['.netbar .nowrap', 'table.hub-rota'],
  '/members/exercises/': ['.ex-cards .ex-action .btn'],
};

const TARGETS = '.btn, .wp-block-button__link, .tag, .nowrap';

// The WCAG 1.4.12 text-spacing override (the usual bookmarklet's rules).
const TEXT_SPACING = '* { line-height: 1.5 !important; letter-spacing: .12em !important; word-spacing: .16em !important; } p { margin-bottom: 2em !important; }';

/**
 * In the page: for every visible target, how far it (its box, and each line
 * of its visible text) reaches past the inner right edge of each block box
 * around it (up to .wp-site-blocks, or up to a scroll container) and past the
 * screen. Visually hidden text (.vh) is skipped: it is clipped to 1px on
 * purpose.
 */
function nowrapReach(targets, mustSee) {
  const vw = document.documentElement.clientWidth;
  const root = document.querySelector('.wp-site-blocks') || document.body;
  const name = (el) => el.tagName.toLowerCase()
    + (el.id ? `#${el.id}` : '')
    + (typeof el.className === 'string' && el.className.trim() ? `.${el.className.trim().split(/\s+/).join('.')}` : '');
  const hidden = (el) => !!el.closest('.vh, .screen-reader-text, [hidden]');
  const shown = (el) => {
    if (!el || hidden(el)) return false;
    const r = el.getBoundingClientRect();
    return r.width > 0 && r.height > 0 && getComputedStyle(el).visibility !== 'hidden';
  };
  const missing = mustSee.filter((s) => !shown(document.querySelector(s)));
  const problems = [];
  let checked = 0;
  for (const el of root.querySelectorAll(targets)) {
    if (!shown(el)) continue;
    checked++;
    let right = el.getBoundingClientRect().right;
    const walker = document.createTreeWalker(el, NodeFilter.SHOW_TEXT);
    for (let n = walker.nextNode(); n; n = walker.nextNode()) {
      if (!n.nodeValue.trim() || hidden(n.parentElement)) continue;
      const range = document.createRange();
      range.selectNodeContents(n);
      for (const rr of range.getClientRects()) right = Math.max(right, rr.right);
    }
    const words = [];
    const tw = document.createTreeWalker(el, NodeFilter.SHOW_TEXT);
    for (let n = tw.nextNode(); n; n = tw.nextNode()) if (!hidden(n.parentElement)) words.push(n.nodeValue);
    const what = `${name(el)} "${words.join('').replace(/\s+/g, ' ').trim().slice(0, 40)}" (${Math.round(el.getBoundingClientRect().width)}px wide, right edge at ${Math.round(right)})`;
    // Up to 1px over is sub-pixel rounding (Home's "Optional" tag sits 1.3px
    // over its grid cell at 320 and looks fine), not a cut-off.
    if (Math.round(right - vw) > 1) {
      problems.push(`${what} is past the right edge of the ${vw}px screen`);
      continue;
    }
    for (let a = el.parentElement; a && a !== document.documentElement; a = a.parentElement) {
      const cs = getComputedStyle(a);
      if (/^(auto|scroll)$/.test(cs.overflowX)) break;
      if (/^(inline|contents)$/.test(cs.display)) continue;
      const edge = a.getBoundingClientRect().right - parseFloat(cs.borderRightWidth || '0');
      if (Math.round(right - edge) > 1) {
        problems.push(`${what} is ${Math.round(right - edge)}px past ${name(a)} (inner right edge at ${Math.round(edge)})`);
        break;
      }
      if (a === root) break;
    }
  }
  return { vw, checked, missing, problems };
}

/**
 * Open each page at the current size (after `prepare`, if given) and collect
 * every problem, so a failure lists them all rather than the first.
 */
async function sweep(t, label, { targets = TARGETS, prepare = null } = {}) {
  const out = [];
  for (const p of PAGES) {
    await t.goto(p);
    await t.expectStatus(200);
    if (prepare) await prepare(t);
    const m = await t.evaluate(nowrapReach, targets, MUST_SEE[p] || []);
    t.expect(m.missing, `${label} ${p}: elements the check needs`).toEqual([]);
    t.expect(m.checked, `${label} ${p}: buttons, tags and nowrap text measured`).toBeGreaterThan(0);
    for (const s of m.problems) out.push(`${label} ${p}: ${s}`);
  }
  return out;
}

async function addTextSpacing(t) {
  await t.evaluate((css) => {
    const s = document.createElement('style');
    s.id = 'qa-067-text-spacing';
    s.textContent = css;
    document.head.append(s);
  }, TEXT_SPACING);
  // Let the new styles apply and fonts re-lay out before measuring.
  await t.evaluate(() => new Promise((r) => requestAnimationFrame(() => requestAnimationFrame(r))));
}

function expectNone(t, problems) {
  t.expect(problems.length, `cut off: ${problems.slice(0, 12).join(' | ')}${problems.length > 12 ? ` | (+${problems.length - 12} more)` : ''}`).toBe(0);
}

const W320 = { width: 320, height: 800, mobile: true };
const W390 = { width: 390, height: 844, mobile: true };

export const tests = [
  {
    name: 'visitor (320 wide, WCAG 1.4.10): no button, tag or nowrap text on the six pages runs past its card, column or the screen',
    timeout: 120000,
    async run(t) {
      await t.setViewport(W320);
      expectNone(t, await sweep(t, '320'));
      t.expectNoConsoleErrors();
    },
  },
  {
    name: 'ares-editor (320 wide, WCAG 1.4.10): signed in, no button, tag or nowrap text on the six pages runs past its card, column or the screen',
    role: 'ares-editor',
    timeout: 120000,
    async run(t) {
      await t.setViewport(W320);
      expectNone(t, await sweep(t, '320 signed in'));
      t.expectNoConsoleErrors();
    },
  },
  {
    name: 'visitor (320 and 390 wide, WCAG 1.4.12 text spacing): buttons, tags, nowrap text and the hub rota table stay inside their boxes',
    timeout: 180000,
    async run(t) {
      const problems = [];
      await t.setViewport(W320);
      problems.push(...await sweep(t, '320 + text spacing', { targets: `${TARGETS}, table.hub-rota`, prepare: addTextSpacing }));
      await t.setViewport(W390);
      problems.push(...await sweep(t, '390 + text spacing', { targets: `${TARGETS}, table.hub-rota`, prepare: addTextSpacing }));
      expectNone(t, problems);
      t.expectNoConsoleErrors();
    },
  },
  {
    name: 'visitor (390 wide, 200% default text size): the net bar\'s nowrap settings and the other buttons and tags stay inside their boxes',
    timeout: 120000,
    async run(t) {
      await t.setViewport(W390);
      // The browser's own default font size (Settings > Appearance > Font
      // size), doubled: 1rem becomes 32px, as a reader who needs large text
      // would set it.
      await t.page.send('Page.setFontSizes', { fontSizes: { standard: 32, fixed: 26 } });
      await t.goto('/');
      const rootPx = await t.evaluate(() => getComputedStyle(document.documentElement).fontSize);
      t.expect(rootPx, 'the default text size is doubled').toBe('32px');
      expectNone(t, await sweep(t, '390 at 200% text'));
      t.expectNoConsoleErrors();
    },
  },
];
