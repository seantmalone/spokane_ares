// DEV ONLY. Regression test for QA-031: a page an administrator adds (a plain
// page with a heading and a paragraph, or the Privacy Policy from Settings ›
// Privacy › Create) must look like a page of this site: one h1 (its title),
// its text inside the site's content column with the gutter, and, in the block
// editor's template-shown mode, a title that can be edited.
//
// What happened: templates/page.html was only header, main > post-content,
// footer, because How it works and About carry their own h1 and full-width
// bands in their content. Every other page used it too, so a new page had no
// h1, its h2 and paragraphs started at x = 0 (flush to the window edge, on a
// phone too), and the editor, which opens pages with the template shown
// (governance.php), showed no title field. The theme offers no other template
// for a plain page (no customTemplates), so an administrator had no way out.
// The footer's "Privacy (coming)" promises exactly such a page.
//
// The last test is a guard for the fix: How it works and About must keep their
// own layout (one h1 from the page, full-width bands, no title block added).

const TITLE = 'QA-031 plain page';
const CONTENT = [
  '<!-- wp:heading -->',
  '<h2 class="wp-block-heading">Who we are</h2>',
  '<!-- /wp:heading -->',
  '',
  '<!-- wp:paragraph -->',
  '<p>A paragraph an administrator typed into a new page.</p>',
  '<!-- /wp:paragraph -->',
].join('\n');

/** REST call as the signed-in user (cookie + a fresh wp_rest nonce). */
function rest(t, method, route, body = null) {
  return t.evaluate(async (m, r, b) => {
    const nonce = await (await fetch('/wp-admin/admin-ajax.php?action=rest-nonce', { credentials: 'same-origin' })).text();
    const [p, q] = r.split('?');
    const res = await fetch(`/?rest_route=${encodeURIComponent(p)}${q ? `&${q}` : ''}`, {
      method: m,
      credentials: 'same-origin',
      headers: { 'X-WP-Nonce': nonce, 'Content-Type': 'application/json' },
      body: b ? JSON.stringify(b) : undefined,
    });
    return { status: res.status, json: await res.json().catch(() => null) };
  }, method, route, body);
}

/** Delete a page for good, signing the admin back in first if needed. */
async function removePage(t, id) {
  if (!id) return;
  const me = await t.whoami().catch(() => ({}));
  if (me.login !== 'admin') await t.loginAs('admin');
  else await t.goto('/wp-admin/');
  await rest(t, 'DELETE', `/wp/v2/pages/${id}?force=true`);
}

/** The h1s, the header's .wrap column and the page's own blocks, with their edges. */
function layout(t) {
  return t.evaluate(() => {
    const box = (el) => { const b = el.getBoundingClientRect(); return { l: Math.round(b.left), r: Math.round(b.right) }; };
    const wrap = document.querySelector('header.site-header .wrap');
    const blocks = [...document.querySelectorAll('main .wp-block-post-content > *')]
      .filter((el) => el.getBoundingClientRect().height > 0)
      .map((el) => ({ what: `${el.tagName.toLowerCase()} "${el.innerText.trim().slice(0, 30)}"`, ...box(el) }));
    const h1s = [...document.querySelectorAll('h1')];
    return {
      vw: document.documentElement.clientWidth,
      h1: h1s.map((h) => h.innerText.trim()),
      h1Box: h1s[0] ? { what: `h1 "${h1s[0].innerText.trim()}"`, ...box(h1s[0]) } : null,
      wrap: wrap ? box(wrap) : null,
      blocks,
    };
  });
}

/** One h1 (the title), and the title and every block inside the content column. */
async function expectPlainPage(t, title, label) {
  const f = await layout(t);
  const gutter = f.vw <= 640 ? 16 : 32;
  t.expect(f.h1, `${label}: the page's h1 elements`).toEqual([title]);
  t.expect(f.wrap, `${label}: the header's .wrap (the site column)`).toBeTruthy();
  t.expect(f.blocks.length, `${label}: blocks of the page's content`).toBeGreaterThan(0);
  for (const b of [f.h1Box, ...f.blocks]) {
    t.expect(b.l, `${label}: left edge of ${b.what} (column starts at ${f.wrap.l})`).toBeGreaterThan(f.wrap.l - 2);
    t.expect(b.r, `${label}: right edge of ${b.what} (column ends at ${f.wrap.r})`).toBeLessThan(f.wrap.r + 2);
    t.expect(b.l, `${label}: left gutter of ${b.what}`).toBeGreaterThan(gutter - 1);
    t.expect(f.vw - b.r, `${label}: right gutter of ${b.what}`).toBeGreaterThan(gutter - 1);
  }
}

export const tests = [
  {
    name: 'admin: a page added in Pages shows its title as the one h1 and its text in the column (admin and visitor, desktop and phone)',
    role: 'admin',
    timeout: 120000,
    async run(t) {
      await t.goto('/wp-admin/');
      const made = await rest(t, 'POST', '/wp/v2/pages', { title: TITLE, status: 'publish', content: CONTENT });
      t.expect(made.status, 'creating the page over REST').toBe(201);
      const { id, link } = made.json;
      try {
        await t.goto(link);
        await t.expectStatus(200);
        await expectPlainPage(t, TITLE, 'admin, desktop');

        await t.setViewport('phone');
        await t.goto(link);
        await expectPlainPage(t, TITLE, 'admin, phone');

        // Sign out through the admin bar and look again as a visitor.
        const logout = await t.attr('#wp-admin-bar-logout a', 'href');
        t.expect(logout, 'the admin bar\'s Log Out link').toBeTruthy();
        await t.goto(logout);
        t.expect((await t.whoami()).id || 0, 'signed out').toBe(0);
        await t.goto(link);
        await t.expectStatus(200);
        await expectPlainPage(t, TITLE, 'visitor, phone');
        await t.setViewport('desktop');
        await t.goto(link);
        await expectPlainPage(t, TITLE, 'visitor, desktop');
      } finally {
        await t.setViewport('desktop');
        await removePage(t, id).catch(() => {});
      }
    },
  },
  {
    name: 'admin: Settings › Privacy › Create gives a page with an editable title in the block editor, shown as the one h1',
    role: 'admin',
    timeout: 150000,
    async run(t) {
      await t.goto('/wp-admin/options-privacy.php');
      await t.expectStatus(200);
      await t.clickAndWait('#create-page');
      const id = Number(((await t.url()).match(/[?&]post=(\d+)/) || [])[1] || 0);
      t.expect(id, 'the new Privacy Policy page opens in the editor').toBeGreaterThan(0);
      try {
        const be = await t.blockEditor();
        t.expect(be.ready, 'the block editor loaded').toBe(true);
        t.expect(be.mode, 'pages open with the template shown').toBe('template-locked');
        const title = await t.evaluate(() => {
          const doc = document.querySelector('iframe[name="editor-canvas"]')?.contentDocument;
          const el = doc?.querySelector('.wp-block-post-title');
          return el ? { tag: el.tagName, editable: el.getAttribute('contenteditable'), text: el.textContent.trim() } : null;
        });
        t.expect(title, 'a title (Post Title block) in the editor canvas').toBeTruthy();
        t.expect(title.editable, 'the title can be edited in the canvas').toBe('true');
        t.expect(title.text, 'the title in the canvas').toBe('Privacy Policy');

        const pub = await rest(t, 'POST', `/wp/v2/pages/${id}`, { status: 'publish' });
        t.expect(pub.status, 'publishing the Privacy Policy over REST').toBe(200);
        await t.setViewport('phone');
        await t.goto(pub.json.link);
        await t.expectStatus(200);
        await expectPlainPage(t, 'Privacy Policy', 'Privacy Policy, phone');
      } finally {
        await t.setViewport('desktop');
        await removePage(t, id).catch(() => {});
      }
    },
  },
  {
    name: 'visitor: How it works and About keep their own h1 and full-width bands (no title block added)',
    async run(t) {
      for (const path of ['/how-it-works/', '/about/']) {
        await t.goto(path);
        await t.expectStatus(200);
        const f = await t.evaluate(() => {
          const first = document.querySelector('main .wp-block-post-content > *');
          const b = first?.getBoundingClientRect();
          return {
            vw: document.documentElement.clientWidth,
            h1: document.querySelectorAll('h1').length,
            h1InContent: document.querySelectorAll('main .wp-block-post-content h1').length,
            titleBlocks: document.querySelectorAll('.wp-block-post-title').length,
            first: b ? { l: Math.round(b.left), w: Math.round(b.width) } : null,
          };
        });
        t.expect(f.h1, `${path}: h1 elements`).toBe(1);
        t.expect(f.h1InContent, `${path}: the h1 is the page's own (in its content)`).toBe(1);
        t.expect(f.titleBlocks, `${path}: Post Title blocks`).toBe(0);
        t.expect(f.first, `${path}: first band of the content`).toBeTruthy();
        t.expect(f.first.l, `${path}: first band starts at the window edge`).toBe(0);
        t.expect(f.first.w, `${path}: first band spans the window`).toBe(f.vw);
      }
    },
  },
];
