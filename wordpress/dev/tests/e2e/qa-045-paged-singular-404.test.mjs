// DEV ONLY. Regression test for QA-045: a paginated address of one of the six
// fixed pages must be a 404, not a 200 copy of the page. The pages are single
// pages with no <!--nextpage--> and no paginated Query Loop, so "page 2" of
// them does not exist.
//
// What happened: /about/page/2/, /how-it-works/page/2/, /members/page/9/,
// /members/exercises/page/3/, /members/documents/page/2/, /home/page/2/,
// /page/2/, /page/999/, /about/?paged=2 and /?page_id=<About>&paged=2 all
// answered 200 with the whole page, titled "… | Page N | …". /?paged=2 sent a
// 301 to /page/2/?paged=2, which was also a 200. The front page's /page/2/
// had <link rel="canonical" href="/2/">, and /2/ is a 404.
//
// Cause (WordPress core): the page rewrite rule "(.?.+?)/page/?([0-9]{1,})/?$"
// sets paged=N on a page, and WP::handle_404() only checks the "page" query var
// (for <!--nextpage-->), not "paged", so it never 404s. For a static front
// page, WP_Query moves paged into "page", so wp_get_canonical_url() adds "/2/"
// to the home URL, which no rewrite rule matches. Nothing in
// spokares-hardening/content-surface.php (which 404s the rest of the blog
// surface) covers paged singular or front-page requests.
//
// The probes are signed-out GETs that follow redirects by hand, as a crawler
// would. The final answer must be a 404. A redirect on the way is allowed
// (for example WordPress's /?paged=2 -> /page/2/), but no hop may be a 200.
// Controls check that the unpaginated pages and search still answer 200 with
// a canonical that points at themselves.

/** Signed-out GET that follows up to 5 redirects by hand; returns every hop. */
async function follow(base, path) {
  const hops = [];
  let url = base + path;
  for (let i = 0; i < 6; i++) {
    const r = await fetch(url, { redirect: 'manual', signal: AbortSignal.timeout(30000) });
    const body = r.status === 200 ? await r.text() : '';
    const loc = r.headers.get('location') || '';
    hops.push({
      url: url.replace(base, ''),
      status: r.status,
      location: loc.replace(base, ''),
      title: (body.match(/<title>([^<]*)<\/title>/i) || [])[1] || '',
      canonical: (body.match(/<link rel="canonical" href="([^"]*)"/i) || [])[1] || '',
    });
    if (r.status < 300 || r.status >= 400 || !loc) break;
    url = new URL(loc, url).href;
  }
  return hops;
}

// Page N (N > 1) of a single page. None of these exists.
const PAGED = [
  '/about/page/2/',
  '/how-it-works/page/2/',
  '/members/page/9/',
  '/members/exercises/page/3/',
  '/members/documents/page/2/',
  '/home/page/2/',
  '/page/2/',
  '/page/999/',
  '/?paged=2',
  '/about/?paged=2',
  '/members/exercises/?paged=2',
];

// The real pages: they must keep answering 200 with a self canonical.
const PAGES = ['/', '/about/', '/how-it-works/', '/members/', '/members/documents/', '/members/exercises/'];

export const tests = [
  {
    name: 'visitor: the six pages answer 200 with a canonical to themselves (control)',
    timeout: 120000,
    async run(t) {
      const wrong = [];
      for (const p of PAGES) {
        const hops = await follow(t.base, p);
        const last = hops[hops.length - 1];
        if (hops.length !== 1 || last.status !== 200 || last.canonical !== t.base + p) wrong.push(hops);
      }
      t.expect(wrong, 'pages that are not a direct 200 with a self canonical').toEqual([]);
    },
  },
  {
    name: 'visitor: search still answers 200 (control)',
    async run(t) {
      const hops = await follow(t.base, '/?s=net');
      t.expect(hops.map((h) => h.status), `status chain for /?s=net (${JSON.stringify(hops)})`).toEqual([200]);
    },
  },
  {
    name: 'visitor: page N of a single page is a 404, never a 200 copy of the page',
    timeout: 180000,
    async run(t) {
      const wrong = [];
      for (const p of PAGED) {
        const hops = await follow(t.base, p);
        const last = hops[hops.length - 1];
        if (last.status !== 404 || hops.some((h) => h.status === 200)) {
          wrong.push(hops.map((h) => `${h.url} ${h.status}${h.location ? ` -> ${h.location}` : ''}${h.title ? ` "${h.title}"` : ''}`).join(' => '));
        }
      }
      if (wrong.length) t.log(`not a 404:\n         ${wrong.join('\n         ')}`);
      t.expect(wrong.length, `paginated single-page URLs that are not a 404 (of ${PAGED.length})`).toBe(0);
    },
  },
  {
    name: 'visitor: /?page_id=<About>&paged=2 is a 404',
    async run(t) {
      const about = t.ids.pages.about;
      t.expect(about, 'About page id').toBeTruthy();
      const hops = await follow(t.base, `/?page_id=${about}&paged=2`);
      const last = hops[hops.length - 1];
      t.expect({ status: last.status, any200: hops.some((h) => h.status === 200) }, `chain ${JSON.stringify(hops)}`).toEqual({ status: 404, any200: false });
    },
  },
  {
    name: 'visitor: no paginated URL carries a canonical that is itself a 404',
    timeout: 180000,
    async run(t) {
      const broken = [];
      for (const p of PAGED) {
        for (const h of await follow(t.base, p)) {
          if (!h.canonical) continue;
          const target = await fetch(h.canonical, { redirect: 'manual', signal: AbortSignal.timeout(30000) });
          if (target.status !== 200) broken.push({ url: h.url, canonical: h.canonical.replace(t.base, ''), canonicalStatus: target.status });
        }
      }
      t.expect(broken, 'canonical links that do not answer 200').toEqual([]);
    },
  },
  {
    name: 'visitor: /page/2/ shows the 404 page at its own address, with no "Page 2" title',
    async run(t) {
      const nav = await t.goto('/page/2/');
      t.expect(nav.chain.map((c) => `${c.status} ${c.url} -> ${c.location}`), 'redirects before the page').toEqual([]);
      await t.expectStatus(404);
      t.expect(new URL(await t.url()).pathname, 'final path').toBe('/page/2/');
      await t.expectText('h1', 'Page not found');
      t.expect(await t.evaluate(() => document.title), 'document title').not.toContain('Page 2');
      t.expect(await t.attr('link[rel="canonical"]', 'href'), 'canonical link on the 404').toBe(null);
    },
  },
];
