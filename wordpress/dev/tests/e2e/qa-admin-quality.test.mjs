// DEV ONLY. Browser tests for the plugin-admin quality fixes of the QA crawl
// that live in assets/js/admin-forms.js (no PHP test can see them):
//
//   QA-035  "Pull this file now" (data-spk-ask) asks before it deletes a file for good.
//   QA-037  The rota's Shows cells and the Net details preview are not live
//           regions that re-announce on every keystroke; one quiet status
//           line on the rota says the change once the typing stops.
//   QA-092  A field held back for a problem is aria-invalid and described by
//           its sentence; the error notice takes the focus after the save.
//
// Nothing is saved except a held-back rota note (a held row is not written).

export const tests = [
  {
    // The seed has no uploaded document files, so the row link is stood in for
    // by a link with the same marking (the PHP test checks the row prints it).
    name: 'admin: a link marked data-spk-ask (as "Pull this file now" is) asks first, and Cancel follows nothing',
    role: 'admin',
    async run(t) {
      await t.goto('/wp-admin/edit.php?post_type=spk_document');
      await t.expectStatus(200);
      const got = await t.evaluate(() => {
        const link = document.createElement('a');
        link.href = '/wp-admin/admin-post.php?action=spokares_qa_admin_never';
        link.className = 'spk-danger';
        link.setAttribute('data-spk-ask', 'Delete the file from the web now? This can’t be undone.');
        link.textContent = 'Pull this file now';
        document.querySelector('.wrap').appendChild(link);
        const asked = [];
        window.confirm = (msg) => { asked.push(String(msg)); return false; };
        const before = window.location.href;
        link.click();
        return { asked, same: window.location.href === before };
      });
      t.expect(got.asked.length, 'the browser asked before following the link').toBe(1);
      t.expect(got.asked[0], 'the question').toContain('can’t be undone');
      t.expect(got.same, 'Cancel keeps the page (nothing pulled)').toBe(true);
      t.expectNoConsoleErrors();
    },
  },
  {
    name: 'ares-editor: the rota announces a change once, on one status line, not from every Shows cell',
    role: 'ares-editor',
    async run(t) {
      await t.goto('/wp-admin/admin.php?page=spokares-rota');
      await t.expectStatus(200);
      const regions = await t.evaluate(() => ({
        liveCells: document.querySelectorAll('td.spk-shows[aria-live]').length,
        status: !!document.querySelector('#spk-rota-status[role="status"]'),
      }));
      t.expect(regions.liveCells, 'Shows cells that are live regions').toBe(0);
      t.expect(regions.status, 'one status line for screen readers').toBe(true);

      const date = await t.evaluate(() => document.querySelector('tr.spk-rota-row')?.dataset.date || '');
      await t.type(`tr.spk-rota-row[data-date="${date}"] .spk-call`, 'k7abc', { clear: true });
      await t.waitForFunction(() => (document.querySelector('#spk-rota-status')?.textContent || '').includes('K7ABC'), { timeout: 5000 });
      const said = await t.evaluate(() => document.querySelector('#spk-rota-status').textContent);
      t.expect(said, 'the status line names the Tuesday and what it shows').toMatch(/^Tue, .+ shows K7ABC on the site$/);
      t.expectNoConsoleErrors();
    },
  },
  {
    name: 'ares-net: the Net details preview is not a live region',
    role: 'ares-net',
    async run(t) {
      await t.goto('/wp-admin/admin.php?page=spokares-net-details');
      await t.expectStatus(200);
      const live = await t.evaluate(() => document.querySelectorAll('#spk-net-preview[aria-live], #spk-net-preview [aria-live]').length);
      t.expect(live, 'live regions in the preview').toBe(0);
      t.expectNoConsoleErrors();
    },
  },
  {
    name: 'ares-editor: a held-back rota note is aria-invalid, described by its sentence, and the error notice has the focus',
    role: 'ares-editor',
    async run(t) {
      await t.goto('/wp-admin/admin.php?page=spokares-rota');
      await t.expectStatus(200);
      const date = await t.evaluate(() => document.querySelector('tr.spk-rota-row')?.dataset.date || '');
      await t.type(`tr.spk-rota-row[data-date="${date}"] td.spk-note input[type="text"]`, 'Call 509-555-0102', { clear: true });
      await t.clickAndWait('#spk-rota-form .spk-submit .button-primary');
      const got = await t.evaluate((d) => {
        const field = document.querySelector(`tr.spk-rota-row[data-date="${d}"] td.spk-note input[type="text"]`);
        const ids = (field.getAttribute('aria-describedby') || '').split(' ').filter(Boolean);
        const said = ids.map((id) => document.getElementById(id)?.textContent || '').join(' ');
        const a = document.activeElement;
        return {
          invalid: field.getAttribute('aria-invalid'),
          said,
          focus: a ? `${a.tagName.toLowerCase()}.${a.className}` : '',
        };
      }, date);
      t.expect(got.invalid, 'aria-invalid on the held-back note').toBe('true');
      t.expect(got.said, 'the note is described by its sentence').toContain('mentions a phone number');
      t.expect(got.focus, 'the focus after the save').toContain('notice-error');
      t.expectNoConsoleErrors();
    },
  },
];
