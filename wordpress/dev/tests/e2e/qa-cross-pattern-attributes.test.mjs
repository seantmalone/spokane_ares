// DEV ONLY. Regression test for a side finding of QA-021 and QA-053 (final
// verification, 2026-09-27): the document form's "Admin only › Link name
// (slug)" field had pattern="[a-z0-9-]*". Browsers compile a pattern
// attribute with the RegExp `v` flag, where an unescaped "-" at the end of a
// character class is a syntax error. Chrome then logs "Pattern attribute value
// [a-z0-9-]* is not a valid regular expression" and ignores the pattern, so the
// field had no browser-side check at all. The fix escapes the hyphen
// ([a-z0-9\-]*).
//
// Each test opens a plugin form that carries pattern attributes and checks
// that every one compiles the way the browser compiles it
// (new RegExp('^(?:' + p + ')$', 'v')) and that the browser really applies it:
// a value that breaks the rule reports validity.patternMismatch, a good one
// doesn't. Nothing is saved.

/** Every input[pattern] on the page that doesn't compile with the `v` flag. */
function badPatterns(t) {
  return t.evaluate(() => [...document.querySelectorAll('input[pattern]')]
    .map((el) => {
      const p = el.getAttribute('pattern');
      try {
        // eslint-disable-next-line no-new
        new RegExp(`^(?:${p})$`, 'v');
        return null;
      } catch (e) {
        return `#${el.id || el.name}: pattern "${p}" (${e.message})`;
      }
    })
    .filter(Boolean));
}

/** validity.patternMismatch for a field after setting its value (not typed, nothing sent). */
function mismatch(t, sel, value) {
  return t.evaluate((s, v) => {
    const el = document.querySelector(s);
    if (!el) return 'missing';
    const old = el.value;
    el.value = v;
    const m = el.validity.patternMismatch;
    el.value = old;
    return m;
  }, sel, value);
}

export const tests = [
  {
    name: 'admin: the document form\'s Link name pattern is valid and the browser applies it',
    role: 'admin',
    async run(t) {
      const id = t.ids.documents['ics-213'];
      t.expect(id, 'ICS 213 document id').toBeTruthy();
      await t.goto(`/wp-admin/post.php?post=${id}&action=edit`);
      await t.expectStatus(200);
      t.expect(await t.exists('#spk-slug[pattern]'), 'the Link name field has a pattern').toBe(true);
      t.expect(await badPatterns(t), 'pattern attributes that do not compile with the v flag').toEqual([]);
      t.expect(await mismatch(t, '#spk-slug', 'Bad Slug!'), '"Bad Slug!" breaks the Link name rule').toBe(true);
      t.expect(await mismatch(t, '#spk-slug', 'ics-213-message-form'), '"ics-213-message-form" is allowed').toBe(false);

      await t.goto('/wp-admin/post-new.php?post_type=spk_document');
      await t.expectStatus(200);
      t.expect(await badPatterns(t), 'pattern attributes on Add document').toEqual([]);
      t.expectNoConsoleErrors();
    },
  },
  {
    name: 'ares-net: the Net details frequency patterns are valid and the browser applies them',
    role: 'ares-net',
    async run(t) {
      await t.goto('/wp-admin/admin.php?page=spokares-net-details');
      await t.expectStatus(200);
      t.expect(await t.count('input[pattern]'), 'frequency fields with a pattern').toBeGreaterThan(0);
      t.expect(await badPatterns(t), 'pattern attributes that do not compile with the v flag').toEqual([]);
      t.expect(await mismatch(t, '#spk-p-freq', '146.52'), '"146.52" breaks the frequency rule').toBe(true);
      t.expect(await mismatch(t, '#spk-p-freq', '146.520'), '"146.520" is allowed').toBe(false);
      t.expectNoConsoleErrors();
    },
  },
];
