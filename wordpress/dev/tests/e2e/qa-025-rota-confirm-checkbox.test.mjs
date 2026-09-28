// DEV ONLY. Regression test for QA-025: on the Net rota, a Note that holds a
// phone number is held back with a "This is a public agency number. Publish
// it." tick (.spk-confirm) under the Note box. The rule that stretches the
// Note text box, '.spk-rota-table .spk-note input { width: 100% }' (and its
// phone-width twin in the max-width: 782px block of assets/css/admin.css),
// also matched the checkbox inside .spk-confirm, so the tick was drawn as a
// wide empty bar (about 145x16 at desktop width, solid blue when ticked)
// instead of a normal square checkbox like the one on Regular meetings and
// the event form. The tests hold a note back the way an editor does (type
// it, Save rota) and measure the tick. Nothing is saved: the held row is
// not written.

const NOTE = 'Call 509-555-0101';

/** The first rota row's date. */
function firstDate(t) {
  return t.evaluate(() => document.querySelector('tr.spk-rota-row')?.dataset.date || '');
}

/** Type a phone-number note into one row and press Save rota. */
async function holdBackNote(t, date) {
  const sel = `tr.spk-rota-row[data-date="${date}"] td.spk-note input[type="text"]`;
  await t.waitFor(sel);
  await t.type(sel, NOTE, { clear: true });
  await t.clickAndWait('#spk-rota-form .spk-submit .button-primary');
}

/** The tick under that row's Note box: its box, its label and the note box. */
function tick(t, date) {
  return t.evaluate((d) => {
    const cell = document.querySelector(`tr.spk-rota-row[data-date="${d}"] td.spk-note`);
    const box = cell?.querySelector('.spk-confirm input[type="checkbox"]');
    if (!box) return null;
    const r = box.getBoundingClientRect();
    const label = cell.querySelector('.spk-confirm');
    return {
      width: Math.round(r.width),
      height: Math.round(r.height),
      cssWidth: getComputedStyle(box).width,
      label: label.textContent.trim(),
      labelWidth: Math.round(label.getBoundingClientRect().width),
      noteValue: cell.querySelector('input[type="text"]')?.value || '',
    };
  }, date);
}

/** Checks shared by both viewports. */
async function expectSquareTick(t, maxWidth) {
  await t.goto('/wp-admin/admin.php?page=spokares-rota');
  await t.expectStatus(200);
  const date = await firstDate(t);
  t.expect(date, 'first rota row').toBeTruthy();

  await holdBackNote(t, date);
  t.expect(await t.url(), 'back on the Net rota').toContain('page=spokares-rota');

  const got = await tick(t, date);
  t.expect(got, 'the "Publish it" tick under the held-back note').toBeTruthy();
  t.expect(got.label, 'tick label').toContain('public agency number');
  t.expect(got.noteValue, 'the held-back note is kept in the box').toBe(NOTE);
  t.expect(got.width, `tick checkbox width in px (css width ${got.cssWidth}, label ${got.labelWidth}px wide)`).not.toBeGreaterThan(maxWidth);
  t.expect(Math.abs(got.width - got.height), `tick checkbox is square (${got.width}x${got.height})`).not.toBeGreaterThan(2);
  t.expectNoConsoleErrors();
}

export const tests = [
  {
    name: 'ares-editor: the "Publish it" tick under a held-back rota note is a normal checkbox, not a wide bar',
    role: 'ares-editor',
    async run(t) {
      // WordPress draws admin checkboxes 16px wide at desktop width.
      await expectSquareTick(t, 24);
    },
  },
  {
    name: 'ares-editor (phone): the "Publish it" tick under a held-back rota note is a normal checkbox, not a full-width bar',
    role: 'ares-editor',
    viewport: 'phone',
    async run(t) {
      // Below 782px WordPress draws admin checkboxes 1.5625rem (25px) wide.
      await expectSquareTick(t, 32);
    },
  },
];
