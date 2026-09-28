// DEV ONLY. Regression test for QA-066: the phone menu, opened below the
// theme's 1020px breakpoint, stayed open when the window widened past it (a
// tablet rotated to landscape, or a desktop window made wider).
//
// The primary nav is core Navigation with overlayMenu "always"
// (parts/header.html); site.css §7 restyles core's overlay as B's drop-down
// under the bar and, at 1020px and up, shows the links in the bar only while
// the container is :not(.is-menu-open). Core's view script opens the menu by
// adding .is-menu-open to the container and has-modal-open to <html> (core
// CSS: html.has-modal-open { overflow: hidden }), and nothing undid either on
// a resize. So at 1100px after opening the menu at 900px:
//   - the night drop-down still hung under the bar with the four links;
//   - "Close" (moved into the Menu button's place) sat on top of the header's
//     "Join the team" button ("Join the teClose");
//   - <html> kept overflow: hidden, so the mouse wheel did not scroll the page.
//
// Correct behaviour: once the window is 1020px or wider the header is the
// desktop header, whichever way the fix gets there (closing the menu, or CSS
// that neutralises the open state): the four links sit in the bar, Close is
// not drawn over Join, and the page scrolls.

const NARROW = { width: 900, height: 800, mobile: false };
const WIDE = { width: 1100, height: 800, mobile: false };
const OPEN = 'nav.nav .wp-block-navigation__responsive-container-open';

/** The header's state, read in the page after two frames. */
async function headerState() {
  await new Promise((r) => requestAnimationFrame(() => requestAnimationFrame(r)));
  const shown = (el) => {
    if (!el) return false;
    const r = el.getBoundingClientRect();
    const cs = getComputedStyle(el);
    return r.width > 0 && r.height > 0 && cs.display !== 'none' && cs.visibility !== 'hidden' && Number(cs.opacity) > 0;
  };
  const box = (el) => { if (!el) return null; const r = el.getBoundingClientRect(); return { top: Math.round(r.top), bottom: Math.round(r.bottom), left: Math.round(r.left), right: Math.round(r.right) }; };
  const overlap = (a, b) => !!a && !!b && Math.min(a.right, b.right) - Math.max(a.left, b.left) > 0 && Math.min(a.bottom, b.bottom) - Math.max(a.top, b.top) > 0;
  const html = document.documentElement;
  const bar = document.querySelector('.masthead__bar');
  const container = document.querySelector('nav.nav .wp-block-navigation__responsive-container');
  const close = document.querySelector('nav.nav .wp-block-navigation__responsive-container-close');
  const join = document.querySelector('.site-header__join .wp-block-button__link');
  const links = [...document.querySelectorAll('nav.nav .wp-block-navigation-item > .wp-block-navigation-item__content')];
  const barBox = box(bar);
  const closeBox = box(close);
  const joinBox = box(join);
  // Is Close really drawn over Join? Ask which element is on top in the overlap.
  let closeOnJoin = false;
  if (shown(close) && overlap(closeBox, joinBox)) {
    const x = (Math.max(closeBox.left, joinBox.left) + Math.min(closeBox.right, joinBox.right)) / 2;
    const y = (Math.max(closeBox.top, joinBox.top) + Math.min(closeBox.bottom, joinBox.bottom)) / 2;
    const hit = document.elementFromPoint(x, y);
    closeOnJoin = !!hit && !!hit.closest('.wp-block-navigation__responsive-container-close');
  }
  return {
    width: window.innerWidth,
    menuOpen: !!container && container.classList.contains('is-menu-open'),
    hasModalOpen: html.classList.contains('has-modal-open'),
    htmlOverflow: getComputedStyle(html).overflowY,
    bodyOverflow: getComputedStyle(document.body).overflowY,
    closeShown: shown(close),
    closeBox,
    joinBox,
    closeOnJoin,
    barBox,
    // Links that are drawn but not inside the bar's row (i.e. in a drop-down under it).
    linksOutsideBar: links.filter((a) => shown(a)).filter((a) => { const r = a.getBoundingClientRect(); return r.top < barBox.top - 1 || r.bottom > barBox.bottom + 1; }).map((a) => a.textContent.trim()),
    linksShown: links.filter((a) => shown(a)).length,
    scrollY: Math.round(window.scrollY),
  };
}

/** Turn the mouse wheel over the middle of the page; returns scrollY afterwards. */
async function wheelScrolls(t) {
  await t.evaluate(() => { window.scrollTo({ top: 0, behavior: 'instant' }); return true; });
  const x = Math.round(t.page.viewport.width / 2);
  const y = Math.round(t.page.viewport.height * 0.75);
  await t.page.send('Input.dispatchMouseEvent', { type: 'mouseMoved', x, y });
  await t.page.send('Input.dispatchMouseEvent', { type: 'mouseWheel', x, y, deltaX: 0, deltaY: 500 });
  return t.waitForFunction(() => (window.scrollY > 50 ? Math.round(window.scrollY) : false), { timeout: 3000 }).catch(() => 0);
}

/** Open the menu at 900px, widen to 1100px, and check the header and scrolling. */
async function openThenWiden(t, path) {
  await t.setViewport(NARROW);
  await t.goto(path);
  await t.expectStatus(200);

  // Preconditions: at 900px the bar shows Menu, and the wheel scrolls the page.
  await t.expectVisible(OPEN);
  const before = await wheelScrolls(t);
  t.expect(before, `precondition: the wheel scrolls ${path} at 900px before the menu is opened`).toBeGreaterThan(50);
  await t.evaluate(() => { window.scrollTo({ top: 0, behavior: 'instant' }); return true; });

  await t.click(OPEN);
  await t.waitForFunction(() => {
    const c = document.querySelector('nav.nav .wp-block-navigation__responsive-container');
    return !!c && c.classList.contains('is-menu-open');
  }, { timeout: 5000 }).catch(() => { throw new Error('the Menu button did not open the menu at 900px'); });
  const narrow = await t.evaluate(headerState);
  t.expect(narrow.closeShown, `precondition: Close shows while the menu is open at 900px (${JSON.stringify(narrow)})`).toBe(true);

  // The tablet turns to landscape.
  await t.setViewport(WIDE);
  await t.waitForFunction(() => window.innerWidth === 1100, { timeout: 5000 });
  await new Promise((r) => setTimeout(r, 300));
  const s = await t.evaluate(headerState);
  const info = JSON.stringify(s);

  t.expect(s.closeOnJoin, `at 1100px "Close" is drawn over "Join the team" (${info})`).toBe(false);
  t.expect(s.linksOutsideBar, `at 1100px the nav links hang in a drop-down under the bar instead of sitting in it (${info})`).toHaveLength(0);
  t.expect(s.linksShown, `at 1100px the four nav links show in the bar (${info})`).toBe(4);
  t.expect(s.htmlOverflow, `at 1100px <html> still has the menu's scroll lock (${info})`).not.toBe('hidden');
  const after = await wheelScrolls(t);
  t.expect(after, `at 1100px the mouse wheel scrolls ${path} (${info})`).toBeGreaterThan(50);
  t.expectNoConsoleErrors();
}

export const tests = [
  {
    name: 'visitor, Home: the menu opened at 900px gives way to the desktop header at 1100px, and the page scrolls',
    async run(t) {
      await openThenWiden(t, '/');
    },
  },
  {
    name: 'visitor, About: the menu opened at 900px gives way to the desktop header at 1100px, and the page scrolls',
    async run(t) {
      await openThenWiden(t, '/about/');
    },
  },
  {
    name: 'ARES Editor, admin bar: the menu opened at 900px gives way to the desktop header at 1100px, and the page scrolls',
    role: 'ares-editor',
    async run(t) {
      await openThenWiden(t, '/');
      t.expect(await t.exists('#wpadminbar'), 'the signed-in editor sees the admin bar').toBeTruthy();
    },
  },
];
