// DEV ONLY. Test for QA-097 (accessibility of the Copy buttons' toast,
// assets/js/copy.js): the status region (role=status, aria-live=polite) was
// created, added to the page and given its text in one step on the first
// click, so a screen reader often missed the first "Copied: …". The region
// now exists, empty, as soon as the copy module starts, and a second copy of
// the same text still changes the region, so it is announced again.

async function regionState(t) {
  return t.evaluate(() => {
    const els = [...document.querySelectorAll('.toast')];
    const el = els[0];
    return {
      count: els.length,
      role: el?.getAttribute('role') || '',
      live: el?.getAttribute('aria-live') || '',
      text: el?.textContent ?? null,
      on: !!el?.classList.contains('is-on'),
    };
  });
}

export const tests = [
  {
    name: 'How it works: the status region is in the page, empty, before the first Copy (visitor)',
    async run(t) {
      await t.goto('/how-it-works/');
      await t.expectStatus(200);
      await t.expectVisible('button[data-copy-text]');
      const before = await regionState(t);
      t.expect(before.count, 'one .toast region before any click').toBe(1);
      t.expect(before.role, 'role').toBe('status');
      t.expect(before.live, 'aria-live').toBe('polite');
      t.expect(before.text, 'empty before the first copy').toBe('');
      t.expect(before.on, 'not shown before the first copy').toBe(false);

      // Record every change to the region's text from now on.
      await t.evaluate(() => {
        window.__qaToast = [];
        const el = document.querySelector('.toast');
        new MutationObserver(() => window.__qaToast.push(el.textContent)).observe(el, { childList: true, characterData: true, subtree: true });
      });

      const copyText = await t.attr('button[data-copy-text]', 'data-copy-text');
      await t.click('button[data-copy-text]');
      await t.waitForFunction(() => window.__qaToast.length >= 1, { timeout: 5000 });
      const after = await regionState(t);
      t.expect(after.count, 'still one region after the click (no second one added)').toBe(1);
      t.expect(after.text, 'the message names the settings').toContain(copyText);
      t.expect(after.on, 'shown').toBe(true);

      await t.click('button[data-copy-text]');
      await t.waitForFunction(() => window.__qaToast.length >= 2, { timeout: 5000 });
      const changes = await t.evaluate(() => window.__qaToast.slice());
      t.expect(changes[1], 'the second copy changes the region again').not.toBe(changes[0]);
      t.expect(changes[1].trim(), 'the second message says the same thing').toBe(changes[0].trim());
      t.expect((await regionState(t)).count, 'one region after two copies').toBe(1);
      t.expectNoConsoleErrors();
    },
  },
  {
    name: 'a page without Copy buttons gets no status region (visitor)',
    async run(t) {
      await t.goto('/about/');
      await t.expectStatus(200);
      t.expect((await regionState(t)).count, '.toast regions on About').toBe(0);
    },
  },
];
