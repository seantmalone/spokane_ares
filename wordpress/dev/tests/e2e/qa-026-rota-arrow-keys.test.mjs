// DEV ONLY. Regression test for QA-026: on the Net rota, each row's
// Net control choice is a radio group (Call sign, Open, Not posted yet) with
// the call-sign text box between Call sign and Open. Arrow keys move through
// a radio group and choose each radio as they reach it, which fires "change".
// assets/js/admin-forms.js called box.focus() on every change to Call sign,
// so arrowing onto Call sign (ArrowUp from Open, or ArrowDown wrapping round
// from Not posted yet) pulled the focus out of the group into the text box:
// the next arrow key moved the caret instead of the choice, and a keyboard
// user could not get past Call sign (WCAG 3.2.2 On Input). The Winlink Form
// select did the same when the keyboard chose "Other…" (Windows and Linux
// Chrome change a closed select's value on ArrowDown; macOS opens the menu).
// Correct behaviour: arrows keep the focus in the group and keep moving
// through it; choosing Call sign still opens the box for typing. Nothing is
// saved here.

const ROTA = '/wp-admin/admin.php?page=spokares-rota';

/** The first row whose Net control state is `state`: its date, or ''. */
function rowWithState(t, state) {
  return t.evaluate((s) => {
    const row = [...document.querySelectorAll('tr.spk-rota-row')]
      .find((r) => r.querySelector('input[type="radio"][name$="[state]"]:checked')?.value === s);
    return row ? row.dataset.date : '';
  }, state);
}

/** Where the focus is, and the row's chosen state and call-sign box. */
function focusState(t, date) {
  return t.evaluate((d) => {
    const row = document.querySelector(`tr.spk-rota-row[data-date="${d}"]`);
    const a = document.activeElement;
    const box = row.querySelector('.spk-call');
    return {
      focusTag: a ? a.tagName.toLowerCase() : '',
      focusType: a && a.type ? a.type : '',
      focusValue: a && a.type === 'radio' ? a.value : '',
      focusClass: a ? a.className : '',
      focusInRow: !!(a && row.contains(a)),
      state: row.querySelector('input[type="radio"][name$="[state]"]:checked')?.value || '',
      boxReadOnly: box.readOnly,
      boxOff: box.classList.contains('is-off'),
    };
  }, date);
}

/** Put the keyboard focus on one radio of a row (without choosing it). */
function focusRadio(t, date, value) {
  return t.evaluate((d, v) => {
    const r = document.querySelector(`tr.spk-rota-row[data-date="${d}"] input[type="radio"][name$="[state]"][value="${v}"]`);
    r.scrollIntoView({ block: 'center' });
    r.focus();
    return document.activeElement === r;
  }, date, value);
}

/** Assert the focus is on the row's `value` radio and that radio is chosen. */
function expectOnRadio(t, got, value, step) {
  t.expect(
    `${got.focusTag}[type=${got.focusType}]${got.focusClass ? `.${got.focusClass}` : ''}`,
    `${step}: the focused element (the arrow key must keep the focus in the radio group, not move it into the call-sign box)`,
  ).toBe('input[type=radio]');
  t.expect(got.focusInRow, `${step}: the focus is still in the same row`).toBe(true);
  t.expect(got.focusValue, `${step}: the focused radio`).toBe(value);
  t.expect(got.state, `${step}: the chosen Net control state`).toBe(value);
}

export const tests = [
  {
    name: 'ares-editor: ArrowUp from "Volunteer needed" chooses the call-sign choice and keeps the focus in the radio group; the next ArrowUp reaches "Not posted yet"',
    role: 'ares-editor',
    async run(t) {
      await t.goto(ROTA);
      await t.expectStatus(200);
      await t.waitFor('tr.spk-rota-row input[type="radio"]');
      const date = await rowWithState(t, 'open');
      t.expect(date, 'a row whose Net control is "Volunteer needed"').toBeTruthy();

      t.expect(await focusRadio(t, date, 'open'), 'the "Volunteer needed" radio takes the focus').toBe(true);
      await t.press('ArrowUp');
      const first = await focusState(t, date);
      expectOnRadio(t, first, 'call', 'after ArrowUp from "Volunteer needed"');
      // Choosing Call sign still opens the box for typing (no focus needed).
      t.expect(first.boxReadOnly, 'after ArrowUp: the call-sign box is open for typing').toBe(false);
      t.expect(first.boxOff, 'after ArrowUp: the call-sign box is not greyed').toBe(false);

      // The group wraps: Call sign is first, so the next ArrowUp goes to the last radio.
      await t.press('ArrowUp');
      const second = await focusState(t, date);
      expectOnRadio(t, second, 'tbd', 'after a second ArrowUp');
      t.expectNoConsoleErrors();
    },
  },
  {
    name: 'ares-editor: ArrowDown from "Not posted yet" wraps to the call-sign choice and keeps the focus in the radio group; the next ArrowDown reaches "Volunteer needed"',
    role: 'ares-editor',
    async run(t) {
      await t.goto(ROTA);
      await t.expectStatus(200);
      await t.waitFor('tr.spk-rota-row input[type="radio"]');
      const date = await rowWithState(t, 'tbd');
      t.expect(date, 'a row whose Net control is "Not posted yet"').toBeTruthy();

      t.expect(await focusRadio(t, date, 'tbd'), 'the "Not posted yet" radio takes the focus').toBe(true);
      await t.press('ArrowDown');
      const first = await focusState(t, date);
      expectOnRadio(t, first, 'call', 'after ArrowDown from "Not posted yet"');
      t.expect(first.boxReadOnly, 'after ArrowDown: the call-sign box is open for typing').toBe(false);

      await t.press('ArrowDown');
      const second = await focusState(t, date);
      expectOnRadio(t, second, 'open', 'after a second ArrowDown');
      t.expectNoConsoleErrors();
    },
  },
  {
    name: 'ares-editor: choosing "Other…" in a Winlink Form select from the keyboard reveals the Form name box without moving the focus',
    role: 'ares-editor',
    async run(t) {
      await t.goto(ROTA);
      await t.expectStatus(200);
      await t.waitFor('tr.spk-rota-row .spk-form-select');
      const date = await t.evaluate(() => {
        const row = [...document.querySelectorAll('tr.spk-rota-row')].find((r) => {
          const s = r.querySelector('.spk-form-select');
          return s && s.value !== '__other';
        });
        return row ? row.dataset.date : '';
      });
      t.expect(date, 'a Winlink row whose Form is not "Other…"').toBeTruthy();
      const sel = `tr.spk-rota-row[data-date="${date}"] .spk-form-select`;

      await t.evaluate((q) => {
        const s = document.querySelector(q);
        s.scrollIntoView({ block: 'center' });
        s.focus();
      }, sel);
      // A real (trusted) ArrowDown reaches the page. Headless Chrome on macOS
      // answers it by opening the menu, not by changing the value, so the page
      // then does what Windows and Linux Chrome do for that key on a closed
      // select: move to the next option and fire input and change. No pointer
      // is involved.
      await t.press('ArrowDown');
      const got = await t.evaluate((q) => {
        const s = document.querySelector(q);
        const other = s.closest('td').querySelector('.spk-form-other');
        s.value = '__other';
        s.dispatchEvent(new Event('input', { bubbles: true }));
        s.dispatchEvent(new Event('change', { bubbles: true }));
        return {
          focused: document.activeElement === s,
          focus: `${document.activeElement.tagName.toLowerCase()}.${document.activeElement.className}`,
          otherHidden: other.hidden,
        };
      }, sel);
      t.expect(got.otherHidden, 'the Form name box is shown for "Other…"').toBe(false);
      t.expect(got.focus, 'the focused element after the keyboard chose "Other…" (it must stay on the select)').toBe('select.spk-form-select');
      t.expect(got.focused, 'the select keeps the focus').toBe(true);
      t.expectNoConsoleErrors();
    },
  },
];
