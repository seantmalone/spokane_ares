// DEV ONLY. Test for QA-094 (PLAN §5.5, §6.5 stable document links): the
// /docs/<slug>/ rule accepted only lowercase slugs, so /docs/ICS-213/ (as a
// person might type it from a printed form) was a 404 while /docs/ics-213/
// sent visitors on. Slugs are lowercase, so a link typed with capitals now
// goes where the lowercase one goes, and anything the lowercase link refuses
// (a "Soon" document, an unknown slug) is still a 404.

/** GET a path from the dev site without following redirects: { status, location }. */
async function head(t, path) {
  const r = await fetch(new URL(path, t.base), { redirect: 'manual' });
  await r.arrayBuffer();
  return { status: r.status, location: r.headers.get('location') || '' };
}

export const tests = [
  {
    name: '/docs/<slug>/ typed with capitals redirects exactly like the lowercase link',
    async run(t) {
      const lower = await head(t, '/docs/ics-213/');
      t.expect(lower.status, 'precondition: /docs/ics-213/ redirects').toBe(302);
      t.expect(lower.location, 'precondition: to an https URL').toMatch(/^https:\/\//);
      for (const path of ['/docs/ICS-213/', '/docs/Ics-213/', '/docs/ICS-213']) {
        const res = await head(t, path);
        t.expect(`${res.status} ${res.location}`, path).toBe(`302 ${lower.location}`);
      }
      t.expectNoPhpErrors();
    },
  },
  {
    name: '/docs/<slug>/ with capitals still 404s for a "Soon" document or an unknown slug',
    async run(t) {
      for (const path of ['/docs/ncs-principles/', '/docs/NCS-PRINCIPLES/', '/docs/NO-SUCH-DOCUMENT/']) {
        const res = await head(t, path);
        t.expect(res.status, path).toBe(404);
      }
      const page = await t.goto('/docs/NO-SUCH-DOCUMENT/');
      t.expect(page.status, 'the 404 page in the browser').toBe(404);
      await t.expectCount('h1', 1);
      t.expectNoConsoleErrors();
      t.expectNoPhpErrors();
    },
  },
];
