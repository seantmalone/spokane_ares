// DEV ONLY. Regression test for QA-005: the click helper of the dev browser
// driver (dev/lib/cdp.mjs Page.click, used by t.click and t.clickAndWait here
// and by the crawler) missed its element on the public pages. The theme sets
// html { scroll-behavior: smooth } (site.css §3, when the visitor has no
// reduced-motion preference, which is headless Chrome's default), so
// el.scrollIntoView() only starts an animation; Page.click measured the
// element straight away, got its pre-scroll position and pressed the mouse
// there: on nothing, or on some other control.
//
// These tests use the runner's defaults (no media emulation of their own) and
// the page as the theme serves it. Each target starts below the fold, so the
// helper has to scroll before it clicks. Correct behaviour: the requested
// element gets the click.

/** Record every click the document sees (capture phase), for the checks below. */
function recordClicks() {
  window.__qa005Clicks = [];
  document.addEventListener('click', (e) => {
    const el = e.target;
    window.__qa005Clicks.push({
      tag: el.tagName,
      cls: String(el.className || '').slice(0, 60),
      copy: !!(el.closest && el.closest('button[data-copy-text]')),
      x: e.clientX,
      y: e.clientY,
      scrollY: Math.round(window.scrollY),
    });
  }, true);
  return true;
}

/** Where sel is now, relative to the viewport. Runs in the page. */
function where(sel) {
  const el = document.querySelector(sel);
  if (!el) return null;
  const r = el.getBoundingClientRect();
  return { top: Math.round(r.top), bottom: Math.round(r.bottom), vh: window.innerHeight, scrollY: Math.round(window.scrollY) };
}

/** The Copy button test, shared by the desktop and phone cases. */
async function copyButtonClick(t) {
  const sel = 'button[data-copy-text]';
  await t.goto('/how-it-works/');
  await t.expectStatus(200);
  await t.expectVisible(sel);
  const before = await t.evaluate(where, sel);
  t.expect(before.top, 'precondition: the first Copy button starts below the fold, so the click helper must scroll').toBeGreaterThan(before.vh);
  const copyText = await t.attr(sel, 'data-copy-text');
  t.expect(copyText, 'the Copy button carries the radio settings').toBeTruthy();
  await t.evaluate(recordClicks);

  await t.click(sel);

  const clicks = await t.evaluate(() => window.__qa005Clicks);
  t.expect(clicks.length, `clicks the page saw (${JSON.stringify(clicks)})`).toBe(1);
  t.expect(clicks[0].copy, `click on the Copy button (it landed on <${clicks[0].tag.toLowerCase()}${clicks[0].cls ? ` class="${clicks[0].cls}"` : ''}> at (${clicks[0].x}, ${clicks[0].y}), scrollY ${clicks[0].scrollY})`).toBe(true);
  // The button's own handler ran: the toast shows the settings (either "Copied: …"
  // or, when the clipboard is refused, "Copy did not work here. The settings are: …").
  await t.waitForFunction((txt) => {
    const el = document.querySelector('.toast.is-on');
    return !!el && el.textContent.includes(txt);
  }, { timeout: 5000, args: [copyText] }).catch(() => { throw new Error('no toast after clicking the Copy button'); });
  t.expectNoConsoleErrors();
}

export const tests = [
  {
    name: 't.click on a Copy button below the fold shows the toast (visitor, desktop)',
    async run(t) {
      await copyButtonClick(t);
    },
  },
  {
    name: 't.click on a Copy button below the fold shows the toast (visitor, phone)',
    viewport: 'phone',
    async run(t) {
      await copyButtonClick(t);
    },
  },
  {
    name: 't.clickAndWait follows a footer link below the fold (visitor)',
    async run(t) {
      const sel = 'footer a[href="/about/"]';
      await t.goto('/how-it-works/');
      await t.expectStatus(200);
      await t.expectVisible(sel);
      const before = await t.evaluate(where, sel);
      t.expect(before.top, 'precondition: the footer link starts below the fold, so the click helper must scroll').toBeGreaterThan(before.vh);

      // A missed click opens nothing, and clickAndWait would wait its full
      // 60 s for a page; give up after 15 s with a clear message instead.
      let timer;
      const missed = new Promise((resolve) => { timer = setTimeout(() => resolve('missed'), 15000); });
      const result = await Promise.race([t.clickAndWait(sel).then(() => 'navigated'), missed]).finally(() => clearTimeout(timer));
      t.expect(result, 'clickAndWait on the footer "About ARES & ACS" link opened a page').toBe('navigated');
      t.expect(await t.url(), 'the page the click opened').toMatch(/\/about\/$/);
      await t.expectStatus(200);
    },
  },
];
