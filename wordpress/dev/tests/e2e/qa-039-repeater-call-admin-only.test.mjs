// DEV ONLY. Regression tests for QA-039 (PLAN §3.4 Net details): the
// repeater call sign W7GBU is the club's own licence and How it works names
// it in page text and the message-path diagram, so the owner decided
// (2026-09-27) that only an administrator may change it. A user with just
// the Net details grant (qa-ares-net) sees the box read-only, and a save
// that posts another call sign anyway (the box's readonly removed in the
// browser) must still leave W7GBU on the site: the server refuses it.
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

/** The preview's settings-bar line. */
function barPreview(t) {
  return t.evaluate(() => (document.querySelector('#spk-net-preview [data-preview="bar"]')?.textContent || '').replace(/\s+/g, ' ').trim());
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
    name: 'ares-net: the repeater call sign box is read-only, says why, and typing changes neither it nor the preview',
    role: 'ares-net',
    async run(t) {
      await t.goto(SCREEN);
      await t.expectStatus(200);
      const box = await callBox(t);
      t.expect(box, 'the call-sign box').toBeTruthy();
      t.expect(box.value, 'the box shows the stored call sign').toBe(CALL);
      t.expect(box.readOnly, 'the call-sign box is read-only for qa-ares-net').toBe(true);
      t.expect(box.disabled, 'the box is read-only, not disabled (it stays in the tab order and is read out)').toBe(false);
      t.expect(box.tabIndex, 'the box can take the focus').toBeGreaterThan(-1);
      t.expect(box.hint, 'the description tied to the box').toMatch(/administrator/i);

      await t.type('#spk-p-call', 'K7XYZ', { clear: true });
      const after = await callBox(t);
      t.expect(after.value, 'the box after typing K7XYZ').toBe(CALL);
      const bar = await barPreview(t);
      t.expect(bar, 'the preview after typing K7XYZ').toContain(CALL);
      t.expect(bar, 'the preview after typing K7XYZ').not.toContain('K7XYZ');
      t.expectNoConsoleErrors();
    },
  },
  {
    name: 'ares-net: a save that posts another call sign (readonly removed in the browser) leaves W7GBU on the site',
    role: 'ares-net',
    async run(t) {
      try {
        await t.goto(SCREEN);
        await t.expectStatus(200);
        await t.evaluate(() => {
          const el = document.querySelector('#spk-p-call');
          el.readOnly = false;
          el.setAttribute('name', 'radio[primary][call]');
          el.value = 'K7XYZ';
          return true;
        });
        await t.clickAndWait('#spk-net-form .submit button[type="submit"]');
        t.expect(await t.url(), 'back on Net details').toContain('page=spokares-net-details');
        const box = await callBox(t);
        t.expect(box.value, 'the box after the save').toBe(CALL);
        t.expect(await t.count('.spk-notice.notice-error'), 'an error notice says the call sign wasn\'t saved').toBeGreaterThan(0);
        t.expect(await siteCall(t), 'the call sign How it works prints after the save').toBe(CALL);
      } finally {
        await restoreCall(t);
      }
    },
  },
  {
    name: 'admin: the repeater call sign box stays editable and feeds the preview, but never a call sign the save refuses',
    role: 'admin',
    async run(t) {
      await t.goto(SCREEN);
      await t.expectStatus(200);
      const box = await callBox(t);
      t.expect(box.readOnly, 'the call-sign box is read-only for the administrator').toBe(false);
      t.expect(box.name, 'the administrator\'s box is posted with the save').toBe('radio[primary][call]');
      // A fifth-Tuesday simplex week, so the simplex line names the call sign too.
      await t.evaluate(() => {
        document.querySelectorAll('input[name="nets[simplex_nth][]"]').forEach((b) => {
          b.checked = b.value === '5';
          b.dispatchEvent(new Event('change', { bubbles: true }));
        });
      });
      await t.type('#spk-p-call', 'k7abc', { clear: true });
      t.expect((await callBox(t)).value.toUpperCase(), 'the box after typing k7abc').toBe('K7ABC');
      t.expect(await barPreview(t), 'the preview after typing k7abc').toContain('K7ABC');

      await t.type('#spk-p-call', 'Frank', { clear: true });
      const lines = await t.evaluate(() => {
        const out = {};
        document.querySelectorAll('#spk-net-preview [data-preview]').forEach((el) => { out[el.getAttribute('data-preview')] = el.textContent; });
        return out;
      });
      for (const key of ['bar', 'copy', 'settings', 'simplex']) {
        t.expect((lines[key] || '').toUpperCase(), `the "${key}" preview after typing Frank (the save refuses it)`).not.toContain('FRANK');
      }
      t.expectNoConsoleErrors();
    },
  },
];
