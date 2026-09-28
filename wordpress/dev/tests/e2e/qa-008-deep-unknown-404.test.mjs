// DEV ONLY. Regression test for QA-008 (PLAN §5.6): an unknown URL with three
// or more path segments must be a plain 404, like a one- or two-segment one.
// It must never be a 301 to Home: old Joomla and spam URLs must not land on a
// real page, and browsers and search engines cache a 301.
//
// What happened: /a/b/c/, /members/documents/ics-213/,
// /component/content/article/12-foo.html and similar got
// "301 Moved Permanently" (X-Redirect-By: WordPress) to "/", with the query
// kept.
//
// Cause: no rewrite rule matches a 3+ segment path with no page behind it.
// The verbose page rule is rejected, and the post and attachment rules only
// take one or two segments. WP::parse_request() then keeps error=404, unless
// the request "is for itself" ($requested_file === trim( PHP_SELF )).
// Playground's internal redirect to index.php keeps the original URL, so it
// sets PHP_SELF to the request path, not "/index.php". WordPress drops the 404,
// the empty query becomes the static front page, and redirect_canonical()
// sends a 301 to "/". An Apache, LiteSpeed or nginx front controller sets
// PHP_SELF=/index.php and keeps the 404, so the dev site differs from
// production here.
//
// The test checks the HTTP result the way a visitor or crawler sees it: probes
// that do not follow redirects, plus one browser visit that must show the
// theme's 404 page at the same address.

/** Signed-out GET that does not follow redirects. */
async function probe(base, path) {
  const r = await fetch(base + path, { redirect: 'manual', signal: AbortSignal.timeout(30000) });
  return {
    path,
    status: r.status,
    location: r.headers.get('location') || '',
    by: r.headers.get('x-redirect-by') || '',
  };
}

// None of these are in redirect-map.php, so each must be WordPress's 404.
const DEEP_UNKNOWN = [
  '/a/b/c/',
  '/a/b/c/d/',
  '/about/x/y/',
  '/members/documents/ics-213/',
  '/members/exercises/set-2026/',
  '/members/documents/nonexistent-thing/',
  '/docs/ics-213/extra/',
  '/component/content/article/12-foo.html',
  '/images/stories/roster.pdf',
  '/a/b/c/?q=1',
];

// These already 404. They show the deep paths should behave the same way.
const SHALLOW_UNKNOWN = ['/zzz/', '/a/b/', '/about/x/'];

export const tests = [
  {
    name: 'visitor: unknown 1-2 segment URLs are a plain 404 (control)',
    timeout: 120000,
    async run(t) {
      const results = [];
      for (const p of SHALLOW_UNKNOWN) results.push(await probe(t.base, p));
      const wrong = results.filter((r) => r.status !== 404 || r.location);
      t.expect(wrong, `shallow unknown URLs that are not a plain 404 (all: ${JSON.stringify(results)})`).toEqual([]);
    },
  },
  {
    name: 'visitor: unknown URLs with 3+ segments are a plain 404, never a 301 to Home',
    timeout: 180000,
    async run(t) {
      const results = [];
      for (const p of DEEP_UNKNOWN) results.push(await probe(t.base, p));
      const wrong = results.filter((r) => r.status !== 404 || r.location);
      t.expect(wrong, `deep unknown URLs that are not a plain 404 (all: ${JSON.stringify(results)})`).toEqual([]);
    },
  },
  {
    name: 'visitor: a deep unknown URL shows the 404 page at its own address',
    async run(t) {
      const nav = await t.goto('/members/documents/ics-213/');
      t.expect(nav.chain.map((c) => `${c.status} ${c.url} -> ${c.location}`), 'redirects before the page').toEqual([]);
      await t.expectStatus(404);
      t.expect(new URL(await t.url()).pathname, 'final path').toBe('/members/documents/ics-213/');
      await t.expectText('h1', 'Page not found');
      await t.expectCount('h1', 1);
    },
  },
];
