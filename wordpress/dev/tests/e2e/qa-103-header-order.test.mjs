// DEV ONLY. Regression test for QA-103: below 1020px the header drew the
// brand, "Join the team", then Menu, but the markup was brand, Navigation
// (Menu first), then Join. site.css put Join first with flex `order`, and
// `reading-flow: flex-visual` made only Chrome follow that. Firefox, Safari
// and screen readers follow the markup, so focus went brand, Menu (x=285),
// then back left to Join (x=155) (WCAG 2.4.3, 1.3.2). Without JavaScript the
// links drop to a row under the bar, and the markup still had them before
// the bar's Join.
//
// Owner's decision (2026-09-27): fix it in the markup, for every browser.
// At every width the order of the header's focusable stops in the DOM must be
// the order they are drawn in (rows top to bottom, each row left to right),
// with no CSS reordering (flex/grid `order`, reversed directions, grid
// placement, reading-flow). A second "Join the team" inside the phone menu is
// allowed; the bar keeps its own. Desktop stays brand, four links, Join.
//
// This checks the DOM order, which every browser uses, rather than Chrome's
// Tab order alone: Chrome honours reading-flow, so its Tab order looked right
// before the fix. The Tab walk is checked as well.

const PHONE = ['brand', 'join', 'menu'];
const DESKTOP = ['brand', 'Home', 'How it works', 'About ARES & ACS', 'For Members', 'join'];
const WIDTHS = [
  { width: 360, height: 780, mobile: true, expect: PHONE },
  { width: 390, height: 844, mobile: true, expect: PHONE },
  { width: 768, height: 1024, mobile: false, expect: PHONE },
  { width: 1019, height: 900, mobile: false, expect: PHONE },
  { width: 1020, height: 900, mobile: false, expect: DESKTOP },
  { width: 1024, height: 900, mobile: false, expect: DESKTOP },
  { width: 1440, height: 900, mobile: false, expect: DESKTOP },
];

/**
 * In the page: the header's focusable stops that are drawn, in DOM order and
 * in the order they are drawn, and any CSS that reorders them. `scope` is the
 * part of the header to read (default: all of it).
 */
async function headerOrder(scope) {
  // With page scripts off (the no-JavaScript tests) frame callbacks never run.
  if (!matchMedia('(scripting: none)').matches) await new Promise((r) => requestAnimationFrame(() => requestAnimationFrame(r)));
  const header = document.querySelector('header.site-header');
  const root = scope ? header.querySelector(scope) : header;
  const drawn = (el) => {
    if (!el.getClientRects().length) return false;
    const r = el.getBoundingClientRect();
    if (r.width < 1 || r.height < 1) return false;
    if (getComputedStyle(el).visibility === 'hidden') return false;
    for (let n = el; n && n !== document.documentElement; n = n.parentElement) {
      if (Number(getComputedStyle(n).opacity) === 0) return false;
    }
    return true;
  };
  const label = (el) => {
    if (el.matches('a.brand')) return 'brand';
    if (el.matches('a[href$="#join"]')) return 'join';
    if (el.matches('.wp-block-navigation__responsive-container-open')) return 'menu';
    if (el.matches('.wp-block-navigation__responsive-container-close')) return 'close';
    return (el.textContent || '').trim().replace(/\s+/g, ' ');
  };
  const stops = [...root.querySelectorAll('a[href], button, input, select, textarea, [tabindex]')]
    .filter((el) => el.tabIndex >= 0 && !el.disabled && drawn(el))
    .map((el) => ({ el, label: label(el), r: el.getBoundingClientRect() }));

  // Drawn order: group into rows (a stop whose middle is below the current
  // row's bottom starts a new row), then left to right in each row.
  const rows = [];
  for (const s of [...stops].sort((a, b) => a.r.top - b.r.top)) {
    const row = rows[rows.length - 1];
    if (row && s.r.top + s.r.height / 2 < row.bottom) {
      row.items.push(s);
      row.bottom = Math.max(row.bottom, s.r.bottom);
    } else {
      rows.push({ bottom: s.r.bottom, items: [s] });
    }
  }
  const visual = rows.flatMap((row) => row.items.sort((a, b) => a.r.left - b.r.left)).map((s) => s.label);

  // CSS that makes the drawn order differ from the DOM order, on each stop
  // and every box between it and the header.
  const reorder = new Set();
  for (const s of stops) {
    for (let n = s.el; n && n !== header; n = n.parentElement) {
      const cs = getComputedStyle(n);
      const parent = n.parentElement ? getComputedStyle(n.parentElement) : null;
      const name = `${n.tagName.toLowerCase()}${n.classList.length ? '.' + [...n.classList].slice(0, 2).join('.') : ''}`;
      if (cs.order && cs.order !== '0') reorder.add(`${name} { order: ${cs.order} }`);
      if (cs.readingFlow && cs.readingFlow !== 'normal') reorder.add(`${name} { reading-flow: ${cs.readingFlow} }`);
      if (cs.readingOrder && cs.readingOrder !== '0') reorder.add(`${name} { reading-order: ${cs.readingOrder} }`);
      if (/flex/.test(cs.display) && /reverse/.test(cs.flexDirection)) reorder.add(`${name} { flex-direction: ${cs.flexDirection} }`);
      if (/flex/.test(cs.display) && cs.flexWrap === 'wrap-reverse') reorder.add(`${name} { flex-wrap: wrap-reverse }`);
      if (/grid/.test(cs.display) && /dense/.test(cs.gridAutoFlow)) reorder.add(`${name} { grid-auto-flow: ${cs.gridAutoFlow} }`);
      if (parent && /grid/.test(parent.display) && (cs.gridRowStart !== 'auto' || cs.gridColumnStart !== 'auto')) {
        reorder.add(`${name} { grid-area: ${cs.gridRowStart} / ${cs.gridColumnStart} }`);
      }
    }
  }
  return {
    width: window.innerWidth,
    dom: stops.map((s) => s.label),
    visual,
    reorder: [...reorder],
    at: stops.map((s) => `${s.label}@${Math.round(s.r.left)},${Math.round(s.r.top)}`).join(' '),
  };
}

/** Check one reading of the header: DOM order = drawn order, nothing reordered. */
function expectInOrder(t, s, expected, what) {
  const info = `${what} at ${s.width}px: ${s.at}`;
  t.expect(s.reorder, `${what}: CSS reorders the header at ${s.width}px (${info})`).toEqual([]);
  t.expect(s.dom, `${what}: the DOM (reading and focus) order is not the drawn order (${info})`).toEqual(s.visual);
  if (expected) t.expect(s.dom, `${what}: the header's stops (${info})`).toEqual(expected);
}

/** Tab from the top of the page through the header; returns the stops focused, by label. */
async function tabThroughHeader(t) {
  const seen = [];
  for (let i = 0; i < 12; i++) {
    await t.press('Tab');
    const s = await t.evaluate(() => {
      const el = document.activeElement;
      if (!el || el === document.body) return null;
      if (el.matches('.skip-link, #wp-skip-link')) return { skip: true };
      if (!el.closest('header.site-header')) return { done: true };
      if (el.matches('a.brand')) return { label: 'brand' };
      if (el.matches('a[href$="#join"]')) return { label: 'join' };
      if (el.matches('.wp-block-navigation__responsive-container-open')) return { label: 'menu' };
      return { label: (el.textContent || '').trim().replace(/\s+/g, ' ') };
    });
    if (!s || s.done) break;
    if (!s.skip) seen.push(s.label);
  }
  return seen;
}

async function atWidth(t, view) {
  await t.setViewport({ width: view.width, height: view.height, mobile: view.mobile });
  await t.goto('/');
  await t.expectStatus(200);
  const s = await t.evaluate(headerOrder);
  expectInOrder(t, s, view.expect, 'header');
  const tabbed = await tabThroughHeader(t);
  t.expect(tabbed, `Tab through the header at ${view.width}px`).toEqual(view.expect);
  t.expectNoConsoleErrors();
}

/** Open the phone menu (click on Menu) and wait for it. */
async function openMenu(t) {
  await t.click('nav.nav .wp-block-navigation__responsive-container-open');
  await t.waitForFunction(() => {
    const c = document.querySelector('nav.nav .wp-block-navigation__responsive-container');
    return !!c && c.classList.contains('is-menu-open');
  }, { timeout: 5000 }).catch(() => { throw new Error('the Menu button did not open the menu'); });
}

export const tests = [
  ...WIDTHS.map((view) => ({
    name: `visitor at ${view.width}px: the header's DOM order is its drawn order (${view.expect === PHONE ? 'brand, Join, Menu' : 'brand, four links, Join'}), with no CSS reordering`,
    async run(t) {
      await atWidth(t, view);
    },
  })),
  {
    name: 'ARES Editor at 390px (admin bar): the header reads brand, Join, Menu in the DOM',
    role: 'ares-editor',
    async run(t) {
      await t.setViewport({ width: 390, height: 844, mobile: true });
      await t.goto('/about/');
      await t.expectStatus(200);
      expectInOrder(t, await t.evaluate(headerOrder), PHONE, 'header, signed in');
    },
  },
  ...[390, 768].map((width) => ({
    name: `visitor at ${width}px, menu open: brand, Join, Close, the four links, then the menu's Join, in DOM and drawn order`,
    async run(t) {
      await t.setViewport({ width, height: 900, mobile: width < 700 });
      await t.goto('/about/');
      await t.expectStatus(200);
      await openMenu(t);
      const s = await t.evaluate(headerOrder);
      expectInOrder(t, s, ['brand', 'join', 'close', 'Home', 'How it works', 'About ARES & ACS', 'For Members', 'join'], 'header with the menu open');
      // The menu's own Join sits under the four links, inside the menu.
      const inMenu = await t.evaluate(() => {
        const a = document.querySelector('nav.nav .is-menu-open a[href$="#join"]');
        const last = [...document.querySelectorAll('nav.nav .is-menu-open .wp-block-navigation-item__content')].pop();
        return !!a && !!last && a.getBoundingClientRect().top >= last.getBoundingClientRect().bottom - 1;
      });
      t.expect(inMenu, 'the open menu ends with "Join the team", under the links').toBe(true);
      t.expectNoConsoleErrors();
    },
  })),
  {
    name: 'visitor at 390px on Home: "Join the team" in the open menu closes the menu and shows the Join section',
    async run(t) {
      await t.setViewport({ width: 390, height: 844, mobile: true });
      await t.page.send('Emulation.setEmulatedMedia', { features: [{ name: 'prefers-reduced-motion', value: 'reduce' }] });
      await t.goto('/');
      await t.expectStatus(200);
      await openMenu(t);
      await t.click('nav.nav .is-menu-open a[href$="#join"]');
      const s = await t.waitForFunction(() => {
        const c = document.querySelector('nav.nav .wp-block-navigation__responsive-container');
        const join = document.getElementById('join');
        if (!c || c.classList.contains('is-menu-open') || !join || location.hash !== '#join') return false;
        const header = document.querySelector('header.site-header').getBoundingClientRect();
        const top = join.getBoundingClientRect().top;
        return { modal: document.documentElement.classList.contains('has-modal-open'), top: Math.round(top), header: Math.round(header.bottom), scrollY: Math.round(window.scrollY) };
      }, { timeout: 5000 }).catch(() => null);
      t.expect(s, 'the menu closed and the page went to #join').toBeTruthy();
      t.expect(s.modal, `the page is still scroll-locked after the menu's Join (${JSON.stringify(s)})`).toBe(false);
      t.expect(s.scrollY, `the page scrolled down to the Join section (${JSON.stringify(s)})`).toBeGreaterThan(200);
      t.expect(s.top >= s.header - 2 && s.top < 400, `the Join section starts just under the header (${JSON.stringify(s)})`).toBe(true);
      t.expectNoConsoleErrors();
    },
  },
  ...[390, 768].map((width) => ({
    name: `visitor at ${width}px without JavaScript: brand and Join in the bar, then the links in a row under it, in DOM and drawn order`,
    async run(t) {
      await t.setViewport({ width, height: 900, mobile: width < 700 });
      await t.page.send('Emulation.setScriptExecutionDisabled', { value: true });
      try {
        await t.goto('/about/');
        await t.expectStatus(200);
        const noScript = await t.evaluate(() => matchMedia('(scripting: none)').matches);
        t.expect(noScript, 'the page sees (scripting: none)').toBe(true);
        const s = await t.evaluate(headerOrder);
        expectInOrder(t, s, ['brand', 'join', 'Home', 'How it works', 'About ARES & ACS', 'For Members'], 'header without JavaScript');
      } finally {
        await t.page.send('Emulation.setScriptExecutionDisabled', { value: false }).catch(() => {});
      }
    },
  })),
];
