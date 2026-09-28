// DEV ONLY. Browser checks for the governance-security fixes that change what
// the block editor is told (QA-001, QA-010, QA-040, QA-042, QA-043, QA-046):
//
// - synced patterns, categories, tags and pattern categories became
//   administrators' only, the users routes show a non-administrator only
//   their own account, and the remote Pattern Directory route is gone. The
//   page editor must still load for every editing level with no REST request
//   refused (a 403 or 404 there is a console error for the editor, and a
//   refused /wp/v2/blocks would break the inserter);
// - the More menu no longer offers "Manage patterns" to editors (it led to
//   a screen that answers "Sorry, you are not allowed…"), and still does to
//   administrators;
// - the page card's ⋮ menu offers no Trash to a core Editor (it can't delete
//   pages any more), and still does to an administrator.
//
// Nothing is saved.

/** Open a page in the block editor and let its requests settle. */
async function openPage(t, pageId) {
  await t.unlockPosts();
  await t.goto(`/wp-admin/post.php?post=${pageId}&action=edit`);
  await t.expectStatus(200);
  const facts = await t.blockEditor();
  t.expect(facts.ready, 'block editor ready').toBe(true);
  // Let the editor's lazy requests (inserter data, entity permissions) run.
  await t.evaluate(() => new Promise((r) => setTimeout(r, 2500)));
  return facts;
}

/** Same-origin REST requests the editor made that failed (4xx/5xx or network). */
function failedRest(t) {
  return t.failedRequests
    .filter((r) => r.url && r.url.startsWith(t.base) && (r.url.includes('/wp-json/') || r.url.includes('rest_route=')))
    .map((r) => `${r.status || r.error} ${r.url.replace(t.base, '')}`);
}

/** Open the header's Options (⋮) menu and return its item labels and links. */
async function moreMenu(t) {
  const opened = await t.evaluate(() => {
    const btn = [...document.querySelectorAll('.editor-header button, .edit-post-header button')]
      .find((b) => (b.getAttribute('aria-label') || '') === 'Options');
    if (!btn) return false;
    btn.click();
    return true;
  });
  t.expect(opened, 'the Options (⋮) button in the editor header').toBe(true);
  await t.waitForFunction(() => [...document.querySelectorAll('[role="menu"] [role="menuitem"], [role="menu"] [role="menuitemcheckbox"]')]
    .some((el) => !el.closest('#wpadminbar')), { timeout: 10000 });
  return t.evaluate(() => [...document.querySelectorAll('[role="menu"] [role="menuitem"], [role="menu"] [role="menuitemcheckbox"]')]
    .filter((el) => !el.closest('#wpadminbar'))
    .map((el) => {
      const r = el.getBoundingClientRect();
      // The first line: some items add a description or a shortcut below.
      return { label: el.innerText.trim().split('\n')[0].trim(), href: el.getAttribute('href') || '', visible: r.width > 0 && r.height > 0 && getComputedStyle(el).display !== 'none' };
    }));
}

/** Open the page card's ⋮ Actions menu and return its item labels. */
async function pageCardActions(t) {
  const card = '.editor-post-card-panel .editor-all-actions-button';
  await t.waitFor(card, { visible: true, timeout: 8000 }).catch(async () => {
    await t.evaluate(() => {
      const ep = wp.data.dispatch('core/edit-post');
      if (ep && typeof ep.openGeneralSidebar === 'function') ep.openGeneralSidebar('edit-post/document');
      else wp.data.dispatch('core/interface').enableComplementaryArea('core', 'edit-post/document');
      return true;
    });
    await t.waitFor(card, { visible: true, timeout: 15000 });
  });
  await t.click(card);
  await t.waitForFunction(() => [...document.querySelectorAll('[role="menu"] [role="menuitem"]')]
    .some((el) => !el.closest('#wpadminbar')), { timeout: 10000 });
  return t.evaluate(() => [...document.querySelectorAll('[role="menu"] [role="menuitem"]')]
    .filter((el) => !el.closest('#wpadminbar'))
    .map((el) => el.innerText.trim()));
}

const editorLoads = (role) => ({
  name: `${role}: Home's block editor loads with no refused REST request and no "Manage patterns"`,
  role,
  timeout: 150000,
  async run(t) {
    await openPage(t, t.ids.pages.home);
    t.expect(failedRest(t), 'REST requests the editor made that failed').toEqual([]);
    const items = await moreMenu(t);
    t.expect(items.map((i) => i.label), 'Options menu (control: it opened)').toContain('Keyboard shortcuts');
    const manage = items.filter((i) => i.visible && (/post_type=wp_block/.test(i.href) || /Manage patterns/i.test(i.label)));
    t.expect(manage, 'a visible "Manage patterns" item (the pattern list is administrators\' only)').toEqual([]);
    t.expectNoPhpErrors();
  },
});

export const tests = [
  editorLoads('ares-editor'),
  editorLoads('ares-net'),
  editorLoads('core-editor'),
  {
    name: "admin: Home's block editor loads with no refused REST request and still offers Manage patterns (control)",
    role: 'admin',
    timeout: 150000,
    async run(t) {
      await openPage(t, t.ids.pages.home);
      t.expect(failedRest(t), 'REST requests the editor made that failed').toEqual([]);
      const items = await moreMenu(t);
      t.expect(items.filter((i) => i.visible).map((i) => i.label), 'Options menu').toContain('Manage patterns');
      t.expectNoPhpErrors();
    },
  },
  {
    name: "core-editor: About's page card ⋮ menu offers no Trash (QA-001)",
    role: 'core-editor',
    timeout: 150000,
    async run(t) {
      await openPage(t, t.ids.pages.about);
      const items = await pageCardActions(t);
      t.expect(items, 'page card ⋮ menu (control: it opened)').toContain('View');
      t.expect(items, `page card ⋮ menu items ${JSON.stringify(items)}`).not.toContain('Trash');
      t.expectNoPhpErrors();
    },
  },
  {
    name: "admin: About's page card ⋮ menu still offers Trash (control)",
    role: 'admin',
    timeout: 150000,
    async run(t) {
      await openPage(t, t.ids.pages.about);
      const items = await pageCardActions(t);
      t.expect(items, 'page card ⋮ menu').toContain('Trash');
      t.expectNoPhpErrors();
    },
  },
];
