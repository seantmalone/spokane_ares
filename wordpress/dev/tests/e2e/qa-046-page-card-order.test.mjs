// DEV ONLY. Regression test for QA-046: PLAN §4.3 says that for non-admins
// "the page itself is fixed: slug, parent, author, order, … keep their stored
// values whatever path saves", and the block editor hides those controls from
// them (spokares_page_insert_guard is only the floor). assets/js/editor-guard.js
// removes the old Page attributes panel ('page-attributes'), but WordPress
// 7.1.2 also offers Order from the page card at the top of the Page sidebar:
// its ⋮ Actions menu lists View, Rename and Order (and Trash for the core
// Editor role, QA-001). As an ARES Editor, About › ⋮ › Order › 7 › Save sent
// PUT /wp/v2/pages/<about> { menu_order: 7 }, the server kept menu_order 2
// (spokares_page_insert_guard), and the editor still said "Order updated.".
//
// The correct behaviour: the page card's Actions menu offers no Order to an
// ARES Editor (with or without the net-details grant) or a core Editor, and
// still offers it to an administrator. Nor does it offer them Rename (UX
// spec §3.8): the page's heading is in its text, and the title only names
// the browser tab and search results. These tests only open the menu; they
// save nothing.

/** Open a page in the block editor and return the labels of its page card's ⋮ Actions menu. */
async function pageCardActions(t, pageId) {
  await t.unlockPosts();
  await t.goto(`/wp-admin/post.php?post=${pageId}&action=edit`);
  await t.expectStatus(200);
  const facts = await t.blockEditor();
  t.expect(facts.ready, 'block editor ready').toBe(true);

  // The Page tab of the settings sidebar holds the page card; open it if the
  // sidebar is closed or showing the Block tab.
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

  // The menu is drawn in a popover outside the sidebar; the toolbar's own
  // role="menu" lists are left out. Late in a full run the first press has
  // sometimes left the button closed (the sidebar redrawn as it was pressed),
  // so a closed button is pressed again, up to three times in all; an open one
  // is never pressed again (that would close it).
  const menuShown = () => t.waitForFunction(() => [...document.querySelectorAll('[role="menu"] [role="menuitem"]')]
    .some((el) => !el.closest('#wpadminbar')), { timeout: 6000 }).then(() => true, () => false);
  let shown = false;
  for (let press = 0; press < 3 && !shown; press++) {
    if (await t.attr(card, 'aria-expanded') !== 'true') await t.click(card);
    shown = await menuShown();
  }
  t.expect(shown, `the page card ⋮ menu opened (button aria-expanded ${await t.attr(card, 'aria-expanded')})`).toBe(true);
  return t.evaluate(() => [...document.querySelectorAll('[role="menu"] [role="menuitem"]')]
    .filter((el) => !el.closest('#wpadminbar'))
    .map((el) => el.innerText.trim()));
}

const noOrderFor = (role, label) => ({
  name: `${role}: About's page card ⋮ menu offers no Order or Rename (${label})`,
  role,
  timeout: 150000,
  async run(t) {
    const items = await pageCardActions(t, t.ids.pages.about);
    // Control: this is the page card's menu, and it opened.
    t.expect(items, 'page card ⋮ menu items (control)').toContain('View');
    t.expect(items, `page card ⋮ menu items ${JSON.stringify(items)}: Order is offered, but the server keeps a non-admin's menu_order (spokares_page_insert_guard), so saving it says "Order updated." and changes nothing`).not.toContain('Order');
    t.expect(items, `page card ⋮ menu items ${JSON.stringify(items)}: Rename (the page title is the webmaster's)`).not.toContain('Rename');
    t.expectNoPhpErrors();
  },
});

export const tests = [
  noOrderFor('ares-editor', 'ARES Editor'),
  noOrderFor('ares-net', 'ARES Editor with the net-details grant'),
  noOrderFor('core-editor', 'core Editor role'),
  {
    name: "admin: About's page card ⋮ menu still offers Order and Rename (control)",
    role: 'admin',
    timeout: 150000,
    async run(t) {
      const items = await pageCardActions(t, t.ids.pages.about);
      t.expect(items, 'page card ⋮ menu items').toContain('Order');
      t.expect(items, 'page card ⋮ menu items').toContain('Rename');
      t.expectNoPhpErrors();
    },
  },
];
