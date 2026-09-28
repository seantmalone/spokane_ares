// DEV ONLY. Regression test for a Fixer-round bug that shipped without a test
// (build-notes/plugin.md "Fixer round" › Rota and settings): on the Net rota,
// the call-sign box of an "Open" or "Not posted yet" row was dead, so an
// editor could not click into it to post a call sign. The fix
// (assets/js/admin-forms.js): the box is read-only and greyed, never
// disabled; clicking or tabbing into it opens it, typing picks "Call sign",
// and the red line under the box uses the server's call-sign rule. Since the
// 2026-09-27 review (ux/SPEC.md §3.2) only Volunteer needed and No net grey
// the box; a Not posted yet row's box is white. Nothing is saved here: the
// test reads what the form would submit.

/** Rows on screen whose box is greyed (Volunteer needed or No net): [{ date, state }]. */
function offRows(t) {
  return t.evaluate(() => [...document.querySelectorAll('tr.spk-rota-row:not([hidden])')].map((row) => ({
    date: row.dataset.date,
    state: row.querySelector('input[type="radio"][name$="[state]"]:checked')?.value || '',
  })).filter((r) => r.state === 'open' || r.state === 'none'));
}

/** The state of one row's box, radios, red line and submitted values. */
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
      line: (() => { const l = row.querySelector('.spk-call-check'); return l && !l.hidden ? l.textContent.trim() : ''; })(),
      bad: box.getAttribute('aria-invalid') === 'true',
      postedCall: data.get(`rota[${d}][call]`),
      postedState: data.get(`rota[${d}][state]`),
    };
  }, date);
}

export const tests = [
  {
    name: 'ares-editor: clicking the greyed call-sign box of a Volunteer needed row opens it, and typing a call sign picks it',
    role: 'ares-editor',
    async run(t) {
      await t.goto('/wp-admin/admin.php?page=spokares-rota');
      await t.expectStatus(200);
      await t.waitFor('tr.spk-rota-row .spk-call');
      const rows = await offRows(t);
      t.expect(rows.length, 'Volunteer needed rows on the schedule').toBeGreaterThan(1);
      const { date } = rows[0];
      const sel = `tr.spk-rota-row[data-date="${date}"] .spk-call`;

      const before = await rowState(t, date);
      t.expect(before.disabled, 'box disabled (a disabled box takes no clicks and is not submitted)').toBe(false);
      t.expect(before.readOnly, 'box read-only while the row is Volunteer needed').toBe(true);
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
      t.expect(typed.line, 'the red line under the box').toBe('');
      t.expect(typed.bad, 'the box marked invalid').toBe(false);
      t.expect(typed.postedState, 'submitted state').toBe('call');
      t.expect(String(typed.postedCall).toUpperCase(), 'submitted call sign').toBe('KD7ABC');
      t.expectNoConsoleErrors();
    },
  },
  {
    name: 'ares-editor: tabbing into a greyed call-sign box opens it; a name without a call sign shows "Type a call sign, like NZ2S, not a name."',
    role: 'ares-editor',
    async run(t) {
      await t.goto('/wp-admin/admin.php?page=spokares-rota');
      await t.expectStatus(200);
      await t.waitFor('tr.spk-rota-row .spk-call');
      const rows = await offRows(t);
      t.expect(rows.length, 'Volunteer needed rows on the schedule').toBeGreaterThan(1);
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
      t.expect(typed.line, 'the red line under the box (the server\'s call-sign rule)').toBe('Type a call sign, like NZ2S, not a name.');
      t.expect(typed.bad, 'the box marked invalid').toBe(true);

      // A call sign after the name clears the line (the save keeps the call sign only).
      await t.page.send('Input.insertText', { text: ' NZ2S' });
      const fixed = await rowState(t, date);
      t.expect(fixed.line, 'the red line once the text has a call sign').toBe('');
      t.expect(fixed.bad, 'the box marked invalid once the text has a call sign').toBe(false);
      t.expectNoConsoleErrors();
    },
  },
  {
    name: 'ares-editor: a Not posted yet row\'s call-sign box is white and open for typing; No net greys it and the Winlink boxes',
    role: 'ares-editor',
    async run(t) {
      await t.goto('/wp-admin/admin.php?page=spokares-rota');
      await t.expectStatus(200);
      const date = await t.evaluate(() => [...document.querySelectorAll('tr.spk-rota-row:not([hidden])')]
        .find((r) => r.querySelector('input[value="tbd"]').checked && r.querySelector('.spk-wl-task'))?.dataset.date || '');
      t.expect(date, 'a Not posted yet Winlink row').toBeTruthy();
      const white = await rowState(t, date);
      t.expect(white.readOnly, 'the box of a Not posted yet row is read-only').toBe(false);
      t.expect(white.off, 'the box of a Not posted yet row is greyed').toBe(false);

      await t.click(`tr.spk-rota-row[data-date="${date}"] input[type="radio"][value="none"]`);
      const grey = await t.evaluate((d) => {
        const row = document.querySelector(`tr.spk-rota-row[data-date="${d}"]`);
        const off = (el) => !!el && el.classList.contains('is-off');
        return { box: off(row.querySelector('.spk-call')), task: off(row.querySelector('.spk-wl-task')), form: off(row.querySelector('.spk-form-select')), note: off(row.querySelector('.spk-note input[type="text"]')) };
      }, date);
      t.expect(grey, 'No net greys the box and the Winlink boxes, not the Note').toEqual({ box: true, task: true, form: true, note: false });
      t.expectNoConsoleErrors();
    },
  },
];
