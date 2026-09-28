// DEV ONLY. Regression tests for QA-039 (PLAN §3.4 Net details): the
// repeater call sign W7GBU is the club's own licence and How it works names
// it in page text and the message-path diagram, so the owner decided
// (2026-09-27) that only an administrator may change it. A user with just
// the Net Settings grant (qa-ares-net) sees it as plain text ("W7GBU (the
// webmaster changes this)", ux/SPEC.md §3.3), and a save that posts another
// call sign anyway (a field added in the browser) must still leave W7GBU on
// the site: the server refuses it.
// The PHP half is dev/tests/php/qa-039-repeater-call-admin-only-test.php.
// Nothing is left changed: a call sign that does get through is put back.

const SCREEN = '/wp-admin/admin.php?page=spokares-net-details';
const CALL = 'W7GBU';

/** The call-sign box as the page has it. */
function callBox(t) {
  return t.evaluate(() => {
    const el = document.querySelector('#spk-p-call');
    if (!el) return null;
    const hint = (el.getAttribute('aria-describedby') || '').split(/\s+/).filter(Boolean)
      .map((id) => document.getElementById(id)?.textContent || '').join(' ').replace(/\s+/g, ' ').trim();
    return { value: el.value, readOnly: el.readOnly, disabled: el.disabled, name: el.getAttribute('name'), tabIndex: el.tabIndex, hint };
  });
}

/** The "Members see" line for the Tuesday net. */
function barPreview(t) {
  return t.evaluate(() => (document.querySelector('#spk-net-form [data-preview="bar"]')?.textContent || '').replace(/\s+/g, ' ').trim());
}

/** The stored call sign, as How it works prints it in the settings box. */
async function siteCall(t) {
  await t.goto('/how-it-works/');
  await t.expectStatus(200);
  return t.evaluate(() => (document.querySelector('.netbox dt')?.textContent || '').trim());
}

/** Put W7GBU back as the administrator, if a test left another call sign. */
async function restoreCall(t) {
  if ((await siteCall(t)) === CALL) return;
  await t.loginAs('admin');
  await t.goto(SCREEN);
  await t.type('#spk-p-call', CALL, { clear: true });
  await t.clickAndWait('#spk-net-form .submit button[type="submit"]');
}

export const tests = [
  {
    name: 'ares-net: the repeater call sign is plain text that says who changes it, with no box to type in, and members see it',
    role: 'ares-net',
    async run(t) {
      await t.goto(SCREEN);
      await t.expectStatus(200);
      const shown = await t.evaluate(() => {
        const el = document.querySelector('#spk-p-call');
        return el ? { tag: el.tagName.toLowerCase(), text: el.textContent.replace(/\s+/g, ' ').trim() } : null;
      });
      t.expect(shown, 'the call sign on the screen').toBeTruthy();
      t.expect(shown.tag, 'the call sign is text, not a box').toBe('span');
      t.expect(shown.text, 'the call sign and who changes it').toBe(`${CALL} (the webmaster changes this)`);
      t.expect(await t.count('#spk-net-form [name="radio[primary][call]"]'), 'fields that post the call sign').toBe(0);
      t.expect(await barPreview(t), 'members see the call sign').toContain(CALL);
      t.expectNoConsoleErrors();
    },
  },
  {
    name: 'ares-net: a save that posts another call sign (a field added in the browser) leaves W7GBU on the site',
    role: 'ares-net',
    async run(t) {
      try {
        await t.goto(SCREEN);
        await t.expectStatus(200);
        await t.evaluate(() => {
          const el = document.createElement('input');
          el.type = 'hidden';
          el.name = 'radio[primary][call]';
          el.value = 'K7XYZ';
          document.querySelector('#spk-net-form').appendChild(el);
          return true;
        });
        await t.clickAndWait('#spk-net-form .submit button[type="submit"]');
        t.expect(await t.url(), 'back on Net Settings').toContain('page=spokares-net-details');
        t.expect(await t.text('#spk-p-call'), 'the call sign after the save').toContain(CALL);
        t.expect(await t.count('.spk-notice.notice-error'), 'an error notice says the call sign wasn\'t saved').toBe(1);
        t.expect(await t.text('.spk-notice.notice-error'), 'the notice').toContain('Not saved: the repeater call sign (outlined in red).');
        t.expect(await t.text('#spk-p-call ~ .spk-error-text'), 'the line under the call sign').toBe('The webmaster changes the repeater call sign.');
        t.expect(await siteCall(t), 'the call sign How it works prints after the save').toBe(CALL);
      } finally {
        await restoreCall(t);
      }
    },
  },
  {
    name: 'admin: the repeater call sign box stays editable and feeds the "Members see" line, but never a call sign the save refuses',
    role: 'admin',
    async run(t) {
      await t.goto(SCREEN);
      await t.expectStatus(200);
      const box = await callBox(t);
      t.expect(box.readOnly, 'the call-sign box is read-only for the administrator').toBe(false);
      t.expect(box.name, 'the administrator\'s box is posted with the save').toBe('radio[primary][call]');
      // A fifth-Tuesday simplex week, so the simplex line names the call sign too.
      await t.evaluate(() => {
        const week = document.querySelector('select[name="nets[week][5]"]');
        week.value = 'simplex';
        week.dispatchEvent(new Event('change', { bubbles: true }));
      });
      await t.type('#spk-p-call', 'k7abc', { clear: true });
      t.expect((await callBox(t)).value.toUpperCase(), 'the box after typing k7abc').toBe('K7ABC');
      t.expect(await barPreview(t), 'the preview after typing k7abc').toContain('K7ABC');

      await t.type('#spk-p-call', 'Frank', { clear: true });
      const lines = await t.evaluate(() => {
        const out = {};
        document.querySelectorAll('#spk-net-form [data-preview]').forEach((el) => { out[el.getAttribute('data-preview')] = el.textContent; });
        return out;
      });
      t.expect(lines.simplex || '', 'the simplex line names the repeater').toContain(CALL);
      for (const key of ['bar', 'simplex']) {
        t.expect((lines[key] || '').toUpperCase(), `the "${key}" preview after typing Frank (the save refuses it)`).not.toContain('FRANK');
      }
      t.expectNoConsoleErrors();
    },
  },
];
