// DEV ONLY. Regression test for a Fixer-round bug that shipped without a test
// (build-notes/plugin.md "Fixer round" › Rota and settings): on the Net rota,
// the call-sign box of an "Open" or "Not posted yet" row was dead, so an
// editor could not click into it to post a call sign. The fix
// (assets/js/admin-forms.js): the box is read-only and greyed, never
// disabled; clicking or tabbing into it opens it, typing picks "Call sign",
// and "Shows" uses the server's call-sign rule. Nothing is saved here: the
// test reads what the form would submit.

/** Rows whose state is Open or Not posted yet: [{ date, state }]. */
function offRows(t) {
  return t.evaluate(() => [...document.querySelectorAll('tr.spk-rota-row')].map((row) => ({
    date: row.dataset.date,
    state: row.querySelector('input[type="radio"][name$="[state]"]:checked')?.value || '',
  })).filter((r) => r.state === 'open' || r.state === 'tbd'));
}

/** The state of one row's box, radios, "Shows" cell and submitted values. */
function rowState(t, date) {
  return t.evaluate((d) => {
    const row = document.querySelector(`tr.spk-rota-row[data-date="${d}"]`);
    const box = row.querySelector('.spk-call');
    const data = new FormData(document.getElementById('spk-rota-form'));
    return {
      value: box.value,
      disabled: box.disabled,
      readOnly: box.readOnly,
      off: box.classList.contains('is-off'),
      focused: document.activeElement === box,
      state: row.querySelector('input[type="radio"][name$="[state]"]:checked')?.value || '',
      shows: row.querySelector('.spk-shows').textContent.trim(),
      bad: row.querySelector('.spk-shows').classList.contains('is-bad'),
      postedCall: data.get(`rota[${d}][call]`),
      postedState: data.get(`rota[${d}][state]`),
    };
  }, date);
}

export const tests = [
  {
    name: 'ares-editor: clicking the greyed call-sign box of an Open row opens it, and typing a call sign picks "Call sign"',
    role: 'ares-editor',
    async run(t) {
      await t.goto('/wp-admin/admin.php?page=spokares-rota');
      await t.expectStatus(200);
      await t.waitFor('tr.spk-rota-row .spk-call');
      const rows = await offRows(t);
      t.expect(rows.length, 'Open / Not posted yet rows on the rota').toBeGreaterThan(1);
      const { date } = rows[0];
      const sel = `tr.spk-rota-row[data-date="${date}"] .spk-call`;

      const before = await rowState(t, date);
      t.expect(before.disabled, 'box disabled (a disabled box takes no clicks and is not submitted)').toBe(false);
      t.expect(before.readOnly, 'box read-only while the row is not "Call sign"').toBe(true);
      t.expect(before.off, 'box greyed').toBe(true);

      // A real mouse click, then real typing into whatever has the focus.
      await t.click(sel);
      const opened = await rowState(t, date);
      t.expect(opened.focused, 'the click focuses the box').toBe(true);
      t.expect(opened.readOnly, 'the click opens the box for typing').toBe(false);
      t.expect(opened.off, 'the box is no longer greyed').toBe(false);
      await t.page.send('Input.insertText', { text: 'kd7abc' });

      const typed = await rowState(t, date);
      t.expect(typed.value.toUpperCase(), 'typed text').toBe('KD7ABC');
      t.expect(typed.state, 'typing picked the row state').toBe('call');
      t.expect(typed.shows, '"Shows"').toBe('KD7ABC');
      t.expect(typed.bad, '"Shows" flagged').toBe(false);
      t.expect(typed.postedState, 'submitted state').toBe('call');
      t.expect(String(typed.postedCall).toUpperCase(), 'submitted call sign').toBe('KD7ABC');
      t.expectNoConsoleErrors();
    },
  },
  {
    name: 'ares-editor: tabbing into a greyed call-sign box opens it; a name without a call sign shows "No call sign: not saved"',
    role: 'ares-editor',
    async run(t) {
      await t.goto('/wp-admin/admin.php?page=spokares-rota');
      await t.expectStatus(200);
      await t.waitFor('tr.spk-rota-row .spk-call');
      const rows = await offRows(t);
      t.expect(rows.length, 'Open / Not posted yet rows on the rota').toBeGreaterThan(1);
      const { date } = rows[1];
      // Focus the row's "Call sign" radio without choosing it, then Tab to the box.
      await t.evaluate((d) => {
        const radio = document.querySelector(`tr.spk-rota-row[data-date="${d}"] input[type="radio"][value="call"]`);
        radio.focus();
      }, date);
      await t.press('Tab');
      const opened = await rowState(t, date);
      t.expect(opened.focused, 'Tab reaches the box').toBe(true);
      t.expect(opened.readOnly, 'focus opens the box for typing').toBe(false);
      await t.page.send('Input.insertText', { text: 'Frank' });

      const typed = await rowState(t, date);
      t.expect(typed.value, 'typed text').toBe('Frank');
      t.expect(typed.state, 'typing picked the row state').toBe('call');
      t.expect(typed.shows, '"Shows" (the server\'s call-sign rule)').toBe('No call sign: not saved');
      t.expect(typed.bad, '"Shows" flagged').toBe(true);
      t.expectNoConsoleErrors();
    },
  },
];
