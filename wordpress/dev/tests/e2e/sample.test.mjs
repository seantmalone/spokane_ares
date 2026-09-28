// DEV ONLY sample browser tests: they prove the runner works and show the API
// (dev/tests/README.md). Copy these shapes for real regression tests.

export const tests = [
  {
    name: 'Home has one h1 and no console errors (visitor)',
    async run(t) {
      await t.goto('/');
      await t.expectStatus(200);
      await t.expectCount('h1', 1);
      t.expectNoConsoleErrors();
      t.expectNoFailedRequests();
    },
  },
  {
    name: 'the library search narrows the list as you type (visitor)',
    async run(t) {
      await t.goto('/members/documents/');
      await t.waitForText('Showing all 37 documents.');
      await t.type('#doc-q', 'ICS-213');
      await t.waitForFunction(() => /^Showing \d+ of 37 documents\.$/.test(document.body.innerText.match(/Showing[^\n]*documents\./)?.[0] || ''));
      const shown = await t.count('tr[data-doc]:not([hidden])');
      t.expect(shown, 'rows left').toBeGreaterThan(0);
      t.expect(shown, 'rows left').toBeLessThan(37);
    },
  },
  {
    name: 'the ARES Editor opens the Net Control Schedule from the admin menu',
    role: 'ares-editor',
    async run(t) {
      await t.goto('/wp-admin/');
      await t.clickAndWait('#adminmenu a[href="admin.php?page=spokares-rota"]');
      t.expect(await t.url()).toContain('page=spokares-rota');
      await t.expectText('.wrap h1', 'Net Control Schedule');
      await t.expectVisible('form input[name="action"][value="spokares_save_rota"] ~ *, .wrap form');
    },
  },
  {
    name: 'a subscriber is refused the Net Control Schedule screen',
    role: 'subscriber',
    async run(t) {
      await t.goto('/wp-admin/admin.php?page=spokares-rota');
      await t.expectStatus(403);
      t.expect(await t.exists('#error-page')).toBe(true);
    },
  },
];
