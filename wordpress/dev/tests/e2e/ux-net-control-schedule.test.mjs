// DEV ONLY. Browser tests for the Net Control Schedule and Net Settings
// screens after the 2026-09-27 editor review (ux/SPEC.md §3.2, §3.3, R7):
// "Show 13 more Tuesdays" shows them in place and keeps what was typed; the
// leave guard asks before unsaved changes are lost (not on Save); Undo and
// Redo sit in the title line; a frequency the save would refuse says why
// under its box; on a phone each Tuesday is a card and Save stays in view.
// What a test saves it undoes again.

const ROTA = '/wp-admin/admin.php?page=spokares-rota';
const NET = '/wp-admin/admin.php?page=spokares-net-details';

/** The schedule rows that show, and what the weeks fields hold. */
function shown(t) {
  return t.evaluate(() => ({
    rows: [...document.querySelectorAll('tr.spk-rota-row')].filter((r) => !r.hidden).map((r) => r.dataset.date),
    drawn: document.querySelectorAll('tr.spk-rota-row').length,
    weeks: [...document.querySelectorAll('#spk-rota-form input[name="weeks"], #spk-rota-undo input[name="weeks"]')].map((i) => i.value),
    more: document.querySelector('.spk-rota-more')?.textContent.replace(/\s+/g, ' ').trim() || '',
    link: !!document.querySelector('.spk-more-link'),
  }));
}

/** The first showing row that fits: 'tbd-plain' = Not posted yet with no Winlink boxes. */
function rowDate(t, which, skip = 0) {
  return t.evaluate((w, n) => {
    const rows = [...document.querySelectorAll('tr.spk-rota-row')].filter((r) => !r.hidden);
    const fits = rows.filter((r) => {
      const state = r.querySelector('input[type="radio"][name$="[state]"]:checked')?.value;
      return w === 'tbd-plain' ? state === 'tbd' && !r.querySelector('.spk-wl-task') : state === w;
    });
    return fits[n]?.dataset.date || '';
  }, which, skip);
}

/** The beforeunload dialogs Chrome has shown since the list was cleared. */
function leaveAsks(t) {
  return t.page.dialogs.filter((d) => d.type === 'beforeunload').length;
}

export const tests = [
  {
    name: 'ares-net: "Show 13 more Tuesdays" shows the next 13 in place, keeps what was typed, and at 52 says that\'s as far ahead as you can post',
    role: 'ares-net',
    async run(t) {
      await t.goto(ROTA);
      await t.expectStatus(200);
      const before = await shown(t);
      t.expect(before.drawn, 'every Tuesday of the year is on the page').toBe(52);
      t.expect(before.rows.length, 'Tuesdays showing').toBe(13);
      t.expect(before.more, 'the link').toBe('Show 13 more Tuesdays');

      const date = await rowDate(t, 'tbd-plain');
      const note = `tr.spk-rota-row[data-date="${date}"] td.spk-note input[type="text"]`;
      await t.type(note, 'Field Day week', { clear: true });
      const url = await t.url();
      await t.click('.spk-more-link');
      const once = await shown(t);
      t.expect(await t.url(), 'the page did not reload').toBe(url);
      t.expect(once.rows.length, 'Tuesdays showing after one click').toBe(26);
      t.expect(once.weeks, 'the weeks fields (Save and Undo keep them showing)').toEqual(['26', '26']);
      t.expect(await t.evaluate((s) => document.querySelector(s).value, note), 'the typing is kept').toBe('Field Day week');

      await t.click('.spk-more-link');
      await t.click('.spk-more-link');
      const all = await shown(t);
      t.expect(all.rows.length, 'Tuesdays showing after three clicks').toBe(52);
      t.expect(all.link, 'the link at 52').toBe(false);
      t.expect(all.more, 'the words at 52').toBe('That’s as far ahead as you can post.');
      t.expect(all.weeks, 'the weeks fields at 52').toEqual(['52', '52']);
      t.expectNoConsoleErrors();
    },
  },
  {
    name: 'ares-net: leaving with unsaved changes asks first, Save does not; Undo and Redo sit in the title line and say what they did',
    role: 'ares-net',
    timeout: 120000,
    async run(t) {
      await t.goto(ROTA);
      await t.expectStatus(200);
      const date = await rowDate(t, 'tbd-plain');
      const note = `tr.spk-rota-row[data-date="${date}"] td.spk-note input[type="text"]`;

      // An unsaved change, then a menu click: the browser asks.
      await t.click(note);
      await t.page.send('Input.insertText', { text: 'No net: Thanksgiving week' });
      t.page.dialogs.length = 0;
      await t.clickAndWait('#adminmenu a[href="index.php"]');
      t.expect(leaveAsks(t), '"Leave site?" after a menu click with an unsaved change').toBe(1);

      // The same change saved: Save does not ask.
      await t.goto(ROTA);
      await t.click(note);
      await t.page.send('Input.insertText', { text: 'No net: Thanksgiving week' });
      t.page.dialogs.length = 0;
      await t.clickAndWait('#spk-rota-form .spk-savebar .button-primary');
      t.expect(leaveAsks(t), '"Leave site?" on Save').toBe(0);
      const saved = await t.texts('.spk-notice p');
      t.expect(saved.length, 'one notice').toBe(1);
      t.expect(saved[0], 'the notice names the Tuesday').toMatch(/^Saved [A-Z][a-z]{2} \d{1,2}\. The For members page lists the next five Tuesdays\. See it on the For members page$/);
      t.expect(await t.evaluate((d) => document.querySelector(`tr.spk-rota-row[data-date="${d}"]`).classList.contains('spk-row-changed'), date), 'the saved row is tinted').toBe(true);
      t.expect(await t.text('.spk-saved'), 'the title line').toMatch(/^Last saved .+ by .+ \([A-Z][a-z]{2} \d{1,2}\) · Undo$/);

      // Undo with another unsaved change: the browser asks, then the Tuesday is back.
      const other = await rowDate(t, 'tbd-plain', 1);
      await t.click(`tr.spk-rota-row[data-date="${other}"] td.spk-note input[type="text"]`);
      await t.page.send('Input.insertText', { text: 'x' });
      t.page.dialogs.length = 0;
      await t.clickAndWait('.spk-saved .spk-undo');
      t.expect(leaveAsks(t), '"Leave site?" on Undo with an unsaved change').toBe(1);
      const undone = await t.texts('.spk-notice p');
      t.expect(undone.length, 'one notice after Undo').toBe(1);
      t.expect(undone[0], 'the Undo notice').toMatch(/^Undone: [A-Z][a-z]{2} \d{1,2} is back as it was\. See it on the For members page$/);
      t.expect(await t.evaluate((s) => document.querySelector(s).value, note), 'the note is gone again').toBe('');
      t.expect(await t.text('.spk-saved'), 'the title line after Undo').toMatch(/^Undone .+ \([A-Z][a-z]{2} \d{1,2}\) · Redo$/);

      // Redo, then Undo again, so the dev site is left as it was.
      await t.clickAndWait('.spk-saved .spk-undo');
      t.expect((await t.texts('.spk-notice p'))[0], 'the Redo notice').toMatch(/^Redone: [A-Z][a-z]{2} \d{1,2} is back as it was saved\./);
      t.expect(await t.evaluate((s) => document.querySelector(s).value, note), 'the note is back').toBe('No net: Thanksgiving week');
      await t.clickAndWait('.spk-saved .spk-undo');
      t.expect(await t.evaluate((s) => document.querySelector(s).value, note), 'the note is gone again').toBe('');

      // Nothing unsaved: leaving doesn't ask.
      t.page.dialogs.length = 0;
      await t.clickAndWait('#adminmenu a[href="index.php"]');
      t.expect(leaveAsks(t), '"Leave site?" with nothing unsaved').toBe(0);
      t.expectNoConsoleErrors();
    },
  },
  {
    name: 'ares-net: Net Settings asks before unsaved changes are lost, and a frequency the save would refuse says why in place of its hint',
    role: 'ares-net',
    async run(t) {
      await t.goto(NET);
      await t.expectStatus(200);
      const hint = () => t.evaluate(() => {
        const el = document.querySelector('#spk-p-freq-hint');
        return { text: el.textContent.trim(), error: el.classList.contains('spk-error-text'), invalid: document.querySelector('#spk-p-freq').getAttribute('aria-invalid') };
      });
      t.expect(await t.attr('#spk-p-freq', 'pattern'), 'the frequency box has no pattern (no browser bubble)').toBe(null);
      t.expect(await hint(), 'the hint').toEqual({ text: 'Three digits after the point, like 147.300.', error: false, invalid: null });

      await t.type('#spk-p-freq', '155.340', { clear: true });
      t.expect(await hint(), 'a hospital frequency').toEqual({ text: 'Not an amateur frequency; the site keeps 147.300.', error: true, invalid: 'true' });
      t.expect(await t.text('[data-preview="bar"]'), 'members see the stored frequency').toContain('147.300 MHz');

      await t.type('#spk-p-freq', '147.3', { clear: true });
      t.expect((await hint()).text, 'a short frequency, while the line shows').toBe('Three digits after the point, like 147.300.');
      t.expect((await hint()).error, 'the line is red').toBe(true);

      await t.type('#spk-p-freq', '146.940', { clear: true });
      t.expect(await hint(), 'a good frequency brings the hint back').toEqual({ text: 'Three digits after the point, like 147.300.', error: false, invalid: null });
      t.expect(await t.text('[data-preview="bar"]'), 'members see the new frequency').toContain('146.940 MHz');

      t.page.dialogs.length = 0;
      await t.clickAndWait('#adminmenu a[href="index.php"]');
      t.expect(leaveAsks(t), '"Leave site?" with an unsaved frequency').toBe(1);
      t.expectNoConsoleErrors();
    },
  },
  {
    name: 'ares-editor (phone): each Tuesday is a card headed by its date at 16px, and Save stays at the bottom of the window',
    role: 'ares-editor',
    viewport: 'phone',
    async run(t) {
      await t.goto(ROTA);
      await t.expectStatus(200);
      const got = await t.evaluate(() => {
        const row = document.querySelector('tr.spk-rota-row');
        const bar = document.querySelector('.spk-savebar').getBoundingClientRect();
        return {
          head: getComputedStyle(document.querySelector('.spk-rota-table thead')).display,
          card: getComputedStyle(row).display,
          day: getComputedStyle(row.querySelector('.spk-day')).fontSize,
          netLabel: getComputedStyle(row.querySelector('td.spk-nc'), '::before').content,
          barBottom: Math.round(bar.bottom),
          barTop: Math.round(bar.top),
          vh: window.innerHeight,
          page: document.documentElement.scrollWidth,
          vw: document.documentElement.clientWidth,
        };
      });
      t.expect(got.head, 'the column headings').toBe('none');
      t.expect(got.card, 'a row').toBe('block');
      t.expect(got.day, 'the date').toBe('16px');
      t.expect(['none', 'normal'].includes(got.netLabel), `no "Net control" label on the card (${got.netLabel})`).toBe(true);
      t.expect(got.barBottom <= got.vh && got.barTop < got.vh, `Save is in the window at the top of the page (bar ${got.barTop}-${got.barBottom}, window ${got.vh})`).toBe(true);
      t.expect(got.page, 'no sideways scroll').toBe(got.vw);
      t.expectNoConsoleErrors();
    },
  },
];
