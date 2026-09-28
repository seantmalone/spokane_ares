// DEV ONLY. Regression test for QA-021: on a document, the administrator's
// "Admin only › Link name (slug)" field (#spk-slug, name="post_name") was
// silently ignored. WordPress's own slug box (#slugdiv, with its own
// <input name="post_name" id="post_name">) was still rendered for admins,
// hidden, later in the form, so its old value won when the form was posted:
// "Saved." showed but the slug, /docs/<slug>/ and the row anchor on
// Documents & forms stayed the same, and a new draft lost its typed slug.
// The tests type into the field a person sees, save the way a person does,
// and then check the saved slug, the stable link and the row anchor.

/** GET a same-origin path without following redirects: 'redirect' or the status. */
function probe(t, path) {
  return t.evaluate(async (p) => {
    const r = await fetch(p, { redirect: 'manual', credentials: 'same-origin' });
    return r.type === 'opaqueredirect' ? 'redirect' : r.status;
  }, path);
}

/** Open the "Admin only" section and type a new Link name. */
async function typeSlug(t, slug) {
  await t.waitFor('details.spk-admin-only');
  await t.evaluate(() => { document.querySelector('details.spk-admin-only').open = true; });
  await t.expectVisible('#spk-slug');
  await t.type('#spk-slug', slug, { clear: true });
  t.expect(await t.evaluate(() => document.querySelector('#spk-slug').value), 'Link name as typed').toBe(slug);
}

/** Set a document's Link name back through the same form. */
async function restoreSlug(t, id, slug) {
  await t.goto(`/wp-admin/post.php?post=${id}&action=edit`);
  if (await t.evaluate(() => document.querySelector('#spk-slug')?.value) === slug) return;
  await typeSlug(t, slug);
  await t.clickAndWait('#submitpost input[name="save"]');
}

export const tests = [
  {
    name: 'admin: changing a published document\'s Link name changes its slug, /docs/ link and row anchor',
    role: 'admin',
    async run(t) {
      const id = t.ids.documents['ics-214'];
      t.expect(id, 'ICS 214 document id').toBeTruthy();
      const next = 'ics-214-activity-log-qa021';

      try {
        await t.goto(`/wp-admin/post.php?post=${id}&action=edit`);
        await t.expectStatus(200);
        await typeSlug(t, next);
        await t.clickAndWait('#submitpost input[name="save"]');

        t.expect(await t.url(), 'back on the edit screen').toContain(`post=${id}`);
        await t.expectVisible('#message.notice-success, .notice-success');
        t.expect(await t.evaluate(() => document.querySelector('#spk-slug')?.value), 'saved Link name').toBe(next);

        t.expect(await probe(t, `/docs/${next}/`), `/docs/${next}/`).toBe('redirect');
        t.expect(await probe(t, '/docs/ics-214/'), '/docs/ics-214/ (the old link)').toBe(404);

        await t.goto('/members/documents/');
        await t.expectStatus(200);
        t.expect(await t.exists(`tr[data-doc="${next}"]#${next}`), `row anchor #${next} on Documents & forms`).toBe(true);
        t.expect(await t.exists('tr#ics-214'), 'old row anchor #ics-214').toBe(false);
      } finally {
        // Put the slug back: other tests (qa-004) use tr#ics-214.
        await restoreSlug(t, id, 'ics-214').catch(() => {});
      }
    },
  },
  {
    name: 'admin: a new document keeps the Link name typed before Save draft',
    role: 'admin',
    async run(t) {
      const slug = 'qa021-typed-link-name';

      await t.goto('/wp-admin/post-new.php?post_type=spk_document');
      await t.expectStatus(200);
      await t.type('#title', 'QA-021 draft document', { clear: true });
      await typeSlug(t, slug);
      await t.clickAndWait('#submitpost input[name="saveasdraft"]');
      try {
        t.expect(await t.url(), 'on the saved draft\'s edit screen').toMatch(/post\.php\?post=\d+&action=edit/);
        t.expect(await t.evaluate(() => document.querySelector('#spk-slug')?.value), 'saved Link name').toBe(slug);
      } finally {
        // Trash the draft so later tests on this site don't see it.
        await t.evaluate(async () => {
          const href = document.querySelector('#spk-trash-link')?.href;
          if (href) await fetch(href, { credentials: 'same-origin' });
        }).catch(() => {});
      }
    },
  },
];
