// DEV ONLY. Browser tests for the workflow-review fixes on the net and
// meeting screens (ux/workflows/content.py N1, N7, M1, M2): what lives in
// assets/js/admin-forms.js or only shows in a browser. Nothing is saved.

export const tests = [
  {
    name: 'ares-editor: Cancel or Move: "Show more dates" shows a meeting\'s later dates in place and moves the focus to the first',
    role: 'ares-editor',
    async run(t) {
      await t.goto('/wp-admin/admin.php?page=spokares-meetings');
      await t.expectStatus(200);
      await t.waitFor('#spk-meetings-form');
      const before = await t.evaluate(() => document.querySelectorAll('tr.spk-meeting-row[hidden]').length);
      t.expect(before, 'later dates are hidden at first').toBeGreaterThan(0);
      // Type in a visible row first: showing more must keep it.
      await t.type('tr.spk-meeting-row:not([hidden]) input.spk-note', 'Kept', { clear: true });
      await t.click('a.spk-more-dates');
      const after = await t.evaluate(() => {
        const link = document.querySelector('a.spk-more-dates');
        const body = link ? link.closest('tbody') : document.querySelector('tbody.spk-meeting');
        return {
          hiddenHere: link ? -1 : body.querySelectorAll('tr.spk-meeting-row[hidden]').length,
          url: location.href,
          focus: document.activeElement && document.activeElement.closest('tr.spk-meeting-row') ? 'row' : String(document.activeElement && document.activeElement.tagName),
          typed: document.querySelector('tr.spk-meeting-row input.spk-note').value,
        };
      });
      t.expect(after.url, 'shown in place, no page load').not.toContain('dates=all');
      t.expect(after.focus, 'the focus moves to the first date shown').toBe('row');
      t.expect(after.typed, 'typing is kept').toBe('Kept');
      t.expect(await t.count('a.spk-more-dates'), 'one meeting\'s link is gone').toBeLessThan(await t.evaluate(() => document.querySelectorAll('tbody.spk-meeting').length));
      await t.expectVisible('#spk-extras');
      await t.expectVisible('#spk-extra-date');
      t.expectNoConsoleErrors();
    },
  },
  {
    name: 'ares-editor: Net Control Schedule: Paste the schedule is a closed box; the wording under the lists is on the screen',
    role: 'ares-editor',
    async run(t) {
      await t.goto('/wp-admin/admin.php?page=spokares-rota');
      await t.expectStatus(200);
      t.expect(await t.evaluate(() => document.querySelector('details.spk-paste').open), 'closed at first').toBe(false);
      await t.click('details.spk-paste > summary');
      await t.expectVisible('#spk-paste-text');
      await t.expectVisible('#spk-open');
      await t.expectVisible('#spk-howto');
      t.expectNoConsoleErrors();
    },
  },
];
