// DEV ONLY. Regression tests for QA-057 (PLAN §3.4), the live half: the Net
// details preview ("what the site will say") is redrawn by
// assets/js/admin-forms.js netPreview() on every input, and that copy of the
// sentences did not match the site either:
//  - GMRS week ticked, GMRS time cleared: "ACS GMRS net: 3rd Tuesdays, , for
//    county volunteers …"; How it works prints "3rd Tuesdays, for county …".
//  - Typing a call sign the save refuses ("Frank") showed "FRANK" as the
//    repeater call sign in the bar, Copy, settings and simplex lines. (Only
//    an administrator can type a call sign since QA-039, so that test signs
//    in as one.)
//  - "How it works: the alternate repeater" showed the Copy-button wording
//    ("-600 kHz offset"), not the row How it works shows ("146.880 MHz,
//    −600 kHz, 123 Hz tone").
// The PHP half (the lines printed on load) is dev/tests/php/qa-057-net-preview-test.php.
// Nothing is saved: the tests only type into the form and read the preview.

const SCREEN = '/wp-admin/admin.php?page=spokares-net-details';

/** Every "Members see" line's text, no-break spaces as spaces. */
function previewTexts(t) {
  return t.evaluate(() => {
    const out = {};
    document.querySelectorAll('#spk-net-form [data-preview]').forEach((el) => {
      out[el.getAttribute('data-preview')] = el.textContent.replace(/ /g, ' ').replace(/\s+/g, ' ').trim();
    });
    return out;
  });
}

/** Set a field's value as a user would (clearing a time box has no typing), firing input and change. */
function setValue(t, sel, value) {
  return t.evaluate((s, v) => {
    const el = document.querySelector(s);
    el.value = v;
    el.dispatchEvent(new Event('input', { bubbles: true }));
    el.dispatchEvent(new Event('change', { bubbles: true }));
    return el.value;
  }, sel, value);
}

/**
 * Make exactly these weeks one kind of net on the "Which Tuesdays" selects
 * (the other weeks of that kind become regular nets), firing change.
 */
function tickWeeks(t, name, weeks) {
  return t.evaluate((n, w) => {
    const kind = n.replace(/_nth$/, '');
    for (let week = 1; week <= 5; week++) {
      const sel = document.querySelector(`select[name="nets[week][${week}]"]`);
      if (w.includes(week)) sel.value = kind;
      else if (sel.value === kind) sel.value = 'regular';
      sel.dispatchEvent(new Event('change', { bubbles: true }));
    }
  }, name, weeks);
}

export const tests = [
  {
    name: 'ares-net: clearing the GMRS time leaves no ", ," in the live preview',
    role: 'ares-net',
    async run(t) {
      await t.goto(SCREEN);
      await t.expectStatus(200);
      await t.waitFor('#spk-net-form [data-preview="gmrs"]');
      await tickWeeks(t, 'gmrs_nth', [3]);
      await setValue(t, '#spk-gmrs-time', '');
      const p = await previewTexts(t);
      t.expect(p.gmrs, 'live GMRS preview line with no GMRS time').not.toContain(', ,');
      t.expect(p.gmrs, 'live GMRS preview line (How it works prints this)').toBe('ACS GMRS net: 3rd Tuesdays, for county volunteers with GMRS licenses.');

      // With a time the line keeps it, as How it works does.
      await setValue(t, '#spk-gmrs-time', '19:30');
      const q = await previewTexts(t);
      t.expect(q.gmrs, 'live GMRS preview line with 7:30 PM').toBe('ACS GMRS net: 3rd Tuesdays, 7:30 PM, for county volunteers with GMRS licenses.');
      t.expectNoConsoleErrors();
    },
  },
  {
    // Administrators only: since QA-039 the call-sign box is read-only for a
    // user with just the Net details grant.
    name: 'admin: a call sign the save refuses ("Frank") is not shown as the repeater call sign in the live preview',
    role: 'admin',
    async run(t) {
      await t.goto(SCREEN);
      await t.expectStatus(200);
      await t.waitFor('#spk-p-call');
      await tickWeeks(t, 'simplex_nth', [5]);

      // A good call sign still flows into the preview.
      await t.type('#spk-p-call', 'k7abc', { clear: true });
      const good = await previewTexts(t);
      t.expect(good.bar, 'bar after typing k7abc').toContain('K7ABC');

      await t.type('#spk-p-call', 'Frank', { clear: true });
      const p = await previewTexts(t);
      for (const key of ['bar', 'simplex']) {
        t.expect((p[key] || '').toUpperCase(), `live "${key}" preview after typing Frank (the save refuses it)`).not.toContain('FRANK');
      }
      t.expectNoConsoleErrors();
    },
  },
  {
    name: 'ares-net: the alternate repeater preview prints the How it works row, on load and live',
    role: 'ares-net',
    async run(t) {
      // What How it works prints for the alternate repeater.
      await t.goto('/how-it-works/');
      await t.expectStatus(200);
      const site = await t.evaluate(() => {
        const dt = [...document.querySelectorAll('.netbox dt')].find((d) => d.textContent.trim() === 'Alternate');
        const dd = dt && dt.nextElementSibling;
        return dd ? dd.textContent.replace(/ /g, ' ').replace(/\s+/g, ' ').trim() : '';
      });
      t.expect(site, 'How it works Alternate row').toBe('146.880 MHz, −600 kHz, 123 Hz tone');

      await t.goto(SCREEN);
      await t.expectStatus(200);
      await t.waitFor('#spk-net-form [data-preview="alt"]');
      const onLoad = await previewTexts(t);
      t.expect(onLoad.alt, 'alternate preview on load').toContain(site);
      t.expect(onLoad.alt, 'alternate preview on load (Copy-button wording)').not.toContain('offset');

      // Any edit redraws the preview from the form.
      await setValue(t, '#spk-a-tone', '123 Hz');
      const live = await previewTexts(t);
      t.expect(live.alt, 'alternate preview after an edit').toContain(site);
      t.expect(live.alt, 'alternate preview after an edit (Copy-button wording)').not.toContain('offset');
      t.expectNoConsoleErrors();
    },
  },
];
