// DEV ONLY. Regression test for QA-029: the sticky masthead (.site-header,
// 64px under 900px wide, 72px + 1px hairline above, and below the 32px
// admin bar when one is fixed) hid keyboard focus. site.css gave only [id]
// elements a scroll-margin-top; <html> had no scroll-padding-top, so Chrome
// took the whole viewport as the place a focused element can be seen:
//
// (a) Shift+Tab from the top of a page walks focus back up it. A link that
//     is already on screen is not scrolled, and one in the top 64-105px sat
//     under the header: on About ec@ / join@, "email it", "ARRL ARES Task
//     Book", "Go-kit guide", RCW / WAC; on Documents the ICS 213RR / 214 and
//     application rows and the "Reference" chip; on Exercises the ICS-213 /
//     213RR / 214 chips; on Home "How to get licensed".
// (b) On a phone, the About "On this page" button (toc.js) opens the
//     <details> list, calls scrollIntoView({ block: 'start' }) on it and
//     focuses its summary. With reduced motion that put the list at top 0,
//     so the focused summary (52px) was entirely under the 64px header and
//     the list looked cut off.
//
// Correct behaviour (WCAG 2.2 2.4.11 Focus Not Obscured, Minimum): a focused
// element is never entirely hidden by the masthead (or the admin bar), and
// after the button the "On this page" summary is focused and shown below the
// header. Every test emulates prefers-reduced-motion: reduce, which turns the
// theme's smooth scrolling off (site.css §3 and the print/motion block), so
// each position is read after the scroll has finished.

const PAGES = ['/', '/how-it-works/', '/members/documents/', '/members/exercises/'];

async function reducedMotion(t) {
  await t.page.send('Emulation.setEmulatedMedia', { features: [{ name: 'prefers-reduced-motion', value: 'reduce' }] });
}

/**
 * In the page, after a key press: the focused element, and whether it is
 * entirely covered by the site header or the admin bar. Samples a 3x5 grid
 * over the part of its box inside the viewport and asks which element is on
 * top at each point. Returns null for <body>, for an element that is already
 * visited (the walk has gone round), and skips elements in the header or
 * admin bar themselves, zero-size elements and ones outside the viewport.
 */
async function focusState() {
  await new Promise((r) => requestAnimationFrame(() => requestAnimationFrame(r)));
  const el = document.activeElement;
  window.qa029Seen = window.qa029Seen || new WeakSet();
  if (!el || el === document.body || el === document.documentElement || window.qa029Seen.has(el)) return null;
  window.qa029Seen.add(el);
  const header = document.querySelector('header.site-header');
  const bar = document.getElementById('wpadminbar');
  const onTop = (node) => !!node && ((header && header.contains(node)) || (bar && bar.contains(node)));
  const r = el.getBoundingClientRect();
  const label = `<${el.tagName.toLowerCase()}> "${(el.innerText || el.getAttribute('aria-label') || '').trim().replace(/\s+/g, ' ').slice(0, 50)}"`;
  const where = `top ${Math.round(r.top)}, bottom ${Math.round(r.bottom)}; header bottom ${header ? Math.round(header.getBoundingClientRect().bottom) : '?'}, scrollY ${Math.round(window.scrollY)}`;
  const info = { label, where, hidden: false, skipped: '' };
  if (onTop(el)) return { ...info, skipped: 'in the header' };
  const top = Math.max(r.top, 0);
  const bottom = Math.min(r.bottom, window.innerHeight);
  const left = Math.max(r.left, 0);
  const right = Math.min(r.right, window.innerWidth);
  if (bottom <= top || right <= left) return { ...info, skipped: 'no visible box' };
  let covered = 0;
  let points = 0;
  for (let i = 0; i < 3; i++) {
    for (let j = 0; j < 5; j++) {
      const x = left + ((right - left) * (i + 0.5)) / 3;
      const y = top + ((bottom - top) * (j + 0.5)) / 5;
      points++;
      if (onTop(document.elementFromPoint(x, y))) covered++;
    }
  }
  return { ...info, hidden: covered === points };
}

/** Shift+Tab from the top of `path` back round to the start; returns the stops that were hidden. */
async function shiftTabWalk(t, path) {
  await t.goto(path);
  await t.expectStatus(200);
  t.expect(await t.exists('header.site-header'), `${path} has the sticky masthead`).toBeTruthy();
  const hidden = [];
  let stops = 0;
  for (let i = 0; i < 250; i++) {
    await t.press('Shift+Tab');
    const s = await t.evaluate(focusState);
    if (!s) break;
    stops++;
    if (s.hidden) hidden.push(`${s.label} (${s.where})`);
  }
  t.expect(stops, `${path}: Shift+Tab reached the page's links`).toBeGreaterThan(10);
  return hidden;
}

async function expectNoHiddenFocus(t, paths) {
  const report = [];
  for (const p of paths) {
    const hidden = await shiftTabWalk(t, p);
    if (hidden.length) report.push(`${p} at ${t.page.viewport.width}px:\n        ${hidden.join('\n        ')}`);
  }
  t.expect(report.length, `focused elements entirely hidden under the sticky masthead on Shift+Tab\n      ${report.join('\n      ')}\n     `).toBe(0);
  t.expectNoConsoleErrors();
}

export const tests = [
  {
    name: 'Shift+Tab up About never hides the focused link under the masthead (visitor, phone)',
    viewport: 'phone',
    async run(t) {
      await reducedMotion(t);
      await expectNoHiddenFocus(t, ['/about/']);
    },
  },
  {
    name: 'Shift+Tab up About never hides the focused link under the masthead (visitor, desktop)',
    async run(t) {
      await reducedMotion(t);
      await expectNoHiddenFocus(t, ['/about/']);
    },
  },
  {
    name: 'Shift+Tab up Home, How it works, Documents and Exercises never hides the focused link (visitor, phone)',
    viewport: 'phone',
    timeout: 180000,
    async run(t) {
      await reducedMotion(t);
      await expectNoHiddenFocus(t, PAGES);
    },
  },
  {
    name: 'Shift+Tab up Home, How it works, Documents and Exercises never hides the focused link (visitor, desktop)',
    timeout: 180000,
    async run(t) {
      await reducedMotion(t);
      await expectNoHiddenFocus(t, PAGES);
    },
  },
  {
    name: 'Shift+Tab up About and Documents never hides the focused link under the admin bar and masthead (ARES Editor, desktop)',
    role: 'ares-editor',
    timeout: 120000,
    async run(t) {
      await reducedMotion(t);
      await t.goto('/about/');
      t.expect(await t.exists('#wpadminbar'), 'precondition: the signed-in editor sees the admin bar').toBeTruthy();
      await expectNoHiddenFocus(t, ['/about/', '/members/documents/']);
    },
  },
  {
    name: 'the About "On this page" button shows its focused summary below the masthead (visitor, phone, reduced motion)',
    viewport: 'phone',
    async run(t) {
      await reducedMotion(t);
      await t.goto('/about/');
      await t.expectStatus(200);
      await t.expectVisible('details.toc-mobile summary');
      await t.evaluate(() => { window.scrollTo({ top: 3000, behavior: 'instant' }); return true; });
      await t.waitForFunction(() => {
        const b = document.querySelector('.wp-block-spokares-toc .contents-fab');
        return !!b && !b.hidden && !b.classList.contains('is-parked') && getComputedStyle(b).visibility === 'visible';
      }, { timeout: 5000 }).catch(() => { throw new Error('the "On this page" button did not appear after scrolling 3000px down About'); });

      await t.click('.wp-block-spokares-toc .contents-fab');

      const s = await t.evaluate(async () => {
        await new Promise((r) => requestAnimationFrame(() => requestAnimationFrame(r)));
        const det = document.querySelector('details.toc-mobile');
        const sum = det.querySelector('summary');
        const h = document.querySelector('header.site-header').getBoundingClientRect();
        const r = sum.getBoundingClientRect();
        const hit = document.elementFromPoint(r.left + r.width / 2, r.top + r.height / 2);
        return {
          open: det.open,
          focused: document.activeElement === sum,
          top: Math.round(r.top),
          bottom: Math.round(r.bottom),
          headerBottom: Math.round(h.bottom),
          scrollY: Math.round(window.scrollY),
          hitHeader: !!(hit && hit.closest('header.site-header')),
        };
      });
      const where = `summary top ${s.top}, bottom ${s.bottom}; header bottom ${s.headerBottom}; scrollY ${s.scrollY}`;
      t.expect(s.open, 'the button opened the contents list').toBe(true);
      t.expect(s.focused, 'the button moved focus to the "On this page" summary').toBe(true);
      t.expect(s.hitHeader, `the masthead is drawn over the focused summary (${where})`).toBe(false);
      t.expect(s.top, `the focused summary starts below the masthead (${where})`).not.toBeLessThan(s.headerBottom);
      t.expectNoConsoleErrors();
    },
  },
];
