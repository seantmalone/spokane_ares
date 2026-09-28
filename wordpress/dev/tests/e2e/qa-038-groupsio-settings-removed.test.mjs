// DEV ONLY. Regression test for QA-038 (PLAN §3.4 Settings › ARES site):
// the screen offered two groups.io addresses ("Main group", "Member files")
// that nothing on the site read, so changing them saved and changed nothing.
// The owner decided (2026-09-27) to remove them; the groups.io links in page
// text stay as they are. The PHP half (form, save, option, defaults, seed)
// is dev/tests/php/qa-038-groupsio-settings-removed-test.php.
// Nothing is saved.

export const tests = [
  {
    name: 'admin: Settings › ARES site has only the meeting place, no groups.io addresses',
    role: 'admin',
    async run(t) {
      await t.goto('/wp-admin/options-general.php?page=spokares-site');
      await t.expectStatus(200);
      const got = await t.evaluate(() => {
        const form = document.querySelector('.spk-site form');
        return {
          form: !!form,
          place: !!document.querySelector('input[name="site[place][name]"]'),
          groupsio: [...document.querySelectorAll('.spk-site input, .spk-site label')]
            .filter((el) => /groupsio|groups\.io|main group|member files/i.test(`${el.getAttribute('name') || ''} ${el.id} ${el.textContent}`))
            .map((el) => el.outerHTML.slice(0, 120)),
          headings: [...document.querySelectorAll('.spk-site h2')].map((h) => h.textContent.trim()),
        };
      });
      t.expect(got.form, 'the ARES site form').toBe(true);
      t.expect(got.place, 'the meeting place fields (control)').toBe(true);
      t.expect(got.groupsio, 'groups.io fields on the screen').toEqual([]);
      t.expect(got.headings.join(' | '), 'section headings').not.toMatch(/groups\.io/i);
      t.expectNoConsoleErrors();
    },
  },
  {
    name: 'anonymous: the groups.io links in page text are unchanged (control)',
    async run(t) {
      await t.goto('/');
      await t.expectStatus(200);
      const links = await t.evaluate(() => [...document.querySelectorAll('a[href*="groups.io"]')].map((a) => a.getAttribute('href')));
      t.expect(links.length, 'groups.io links on Home').toBeGreaterThan(0);
      t.expect(links.every((h) => h.startsWith('https://spokaneares-acs.groups.io/')), 'every Home groups.io link points at the club group').toBe(true);
    },
  },
];
