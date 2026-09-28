// DEV ONLY. Browser tests for Page Text and the block editor for editors
// (UX spec §3.8, group 5), as the ARES Editor with the Net Settings grant:
//
// - the Page Text list shows Home, How it works and About ARES & ACS only,
//   with the Title column only;
// - the admin bar on the site says "Edit Page Text";
// - the editor opens with the settings sidebar closed (About: open, with the
//   Page review box), no ⋮ Options menu, no Unlink or Remove link on a
//   Button (the pencil stays), no list Indent/Outdent, no "Add note", no
//   heading-level switcher, and the Home photo's Replace menu offers
//   Choose a photo (core's Open Media Library) and Upload only;
// - a refused save shows the server's one sentence, a HEIC photo gets the
//   plain sentence, and a saved new photo leaves the editor clean (FC-20:
//   it used to ask "Leave site?" after "Saved").
//
// The server side (refusals, labels, what the editor is told) is in
// dev/tests/php/ux-page-text-editor-test.php. The photo test uploads one
// photo and puts Home's own photo back afterwards; nothing else is saved.

import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const here = path.dirname(fileURLToPath(import.meta.url));
const IMG = path.resolve(here, '../../../theme/spokares/assets/img');
const PHOTO = path.join(IMG, '1920x1200sunset.jpg');
// A photo unlike Home's own, so the Cover works out a new colour after the upload.
const OTHER = path.join(IMG, 'seal-ares-acs-400.png');

/** Open a page in the block editor and wait for it (autosave held off). */
async function openEditor(t, pageId) {
  await t.unlockPosts();
  await t.goto(`/wp-admin/post.php?post=${pageId}&action=edit`);
  await t.expectStatus(200);
  const facts = await t.blockEditor();
  t.expect(facts.ready, 'block editor ready').toBe(true);
  t.expect(facts.templateLock, 'the page is content-only for this role').toBe('contentOnly');
  await t.evaluate(() => { wp.data.dispatch('core/editor').updateEditorSettings({ autosaveInterval: 100000 }); return true; });
  await t.evaluate(() => new Promise((r) => setTimeout(r, 1200)));
}

/** Labels (aria-label, else the words) of the visible elements matching a selector. */
function visible(t, selector) {
  return t.evaluate((s) => [...document.querySelectorAll(s)]
    .filter((el) => { const r = el.getBoundingClientRect(); return r.width > 0 && r.height > 0 && getComputedStyle(el).visibility !== 'hidden'; })
    .map((el) => (el.getAttribute('aria-label') || el.innerText || '').trim().split('\n')[0]), selector);
}

/** Select the first content-only block of a type (in the page, not the template). */
async function select(t, name, nth = 0) {
  const ok = await t.evaluate((n, i) => {
    const be = wp.data.select('core/block-editor');
    const ids = be.getBlocksByName(n).filter((id) => be.getBlockEditingMode(id) === 'contentOnly');
    if (!ids[i]) return false;
    wp.data.dispatch('core/block-editor').selectBlock(ids[i]);
    return true;
  }, name, nth);
  t.expect(ok, `a content-only ${name} to select`).toBe(true);
  await t.evaluate(() => new Promise((r) => setTimeout(r, 900)));
}

/** Click a visible toolbar button by its words or label. */
function clickToolbar(t, label) {
  return t.evaluate((l) => {
    const b = [...document.querySelectorAll('.block-editor-block-toolbar button')].find((x) => (x.getAttribute('aria-label') || x.textContent.trim()) === l || x.textContent.trim() === l);
    if (b) b.click();
    return !!b;
  }, label);
}

/** Save the page and wait for the save and the meta boxes (runs in the page). */
async function savePage() {
  const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
  const ed = wp.data.select('core/editor');
  const ep = wp.data.select('core/edit-post');
  await wp.data.dispatch('core/editor').savePost();
  const end = Date.now() + 30000;
  while ((ed.isSavingPost() || (ep && ep.isSavingMetaBoxes && ep.isSavingMetaBoxes())) && Date.now() < end) await sleep(100);
  await sleep(1500);
  return {
    failed: ed.didPostSaveRequestFail(),
    dirty: ed.isEditedPostDirty(),
    notices: wp.data.select('core/notices').getNotices().map((n) => ({ id: n.id, status: n.status, content: String(n.content) })),
  };
}

/** Choose a file for the Home photo through Replace › Upload (the real file input). */
async function uploadViaReplace(t, file) {
  await select(t, 'core/cover');
  t.expect(await clickToolbar(t, 'Replace'), 'the photo\'s Replace button').toBe(true);
  await t.waitFor('.block-editor-media-replace-flow__media-upload-menu input[type="file"]', { timeout: 10000 });
  const { result } = await t.page.send('Runtime.evaluate', { expression: 'document.querySelector(".block-editor-media-replace-flow__media-upload-menu input[type=file]")' });
  t.expect(!!result.objectId, 'the Upload file input').toBe(true);
  await t.page.send('DOM.enable');
  await t.page.send('DOM.setFileInputFiles', { files: [file], objectId: result.objectId });
}

export const tests = [
  {
    name: 'ares-net: Page Text lists Home, How it works and About ARES & ACS, Title only',
    role: 'ares-net',
    async run(t) {
      await t.goto('/wp-admin/edit.php?post_type=page');
      await t.expectStatus(200);
      t.expect(await t.text('.wrap h1'), 'the list\'s title').toBe('Page Text');
      t.expect(await t.texts('#the-list .row-title'), 'the pages listed').toEqual(['Home', 'How it works', 'About ARES & ACS']);
      t.expect(await t.texts('.wp-list-table thead th, .wp-list-table thead td'), 'the columns').toEqual(['Title']);
      t.expect(await t.count('#the-list input[type="checkbox"]'), 'row checkboxes').toBe(0);
      t.expect(await t.count('#filter-by-date, select[name="m"]'), 'the date filter').toBe(0);
      t.expect(await t.count('.subsubsub li'), 'the All | Published line').toBe(0);
      t.expect(await t.text('#the-list'), 'the rows').not.toContain('Front Page');
      t.expect(await visible(t, '#posts-filter .search-box'), 'the search box').toEqual([]);
      t.expect(await visible(t, '#adminmenu .wp-menu-name'), 'the menu').toContain('Page Text');
      t.expectNoConsoleErrors();
    },
  },
  {
    name: 'ares-net: the admin bar on the site says "Edit Page Text"',
    role: 'ares-net',
    async run(t) {
      await t.goto('/about/');
      await t.expectStatus(200);
      t.expect(await t.text('#wp-admin-bar-edit > a'), 'the admin bar\'s edit link').toBe('Edit Page Text');
    },
  },
  {
    name: 'ares-net: Home opens with the sidebar closed and no Options menu; Buttons, lists, headings and the photo offer only what saves',
    role: 'ares-net',
    timeout: 180000,
    async run(t) {
      await openEditor(t, t.ids.pages.home);
      t.expect(await t.evaluate(() => wp.data.select('core/interface').getActiveComplementaryArea('core')), 'the settings sidebar').toBe(null);
      const header = await visible(t, '.editor-header button');
      t.expect(header, 'the header (control)').toContain('Save');
      t.expect(header, 'the header').not.toContain('Options');

      await select(t, 'core/button');
      t.expect(await visible(t, '.block-editor-block-toolbar button'), 'a Button\'s toolbar').not.toContain('Unlink');
      const box = await visible(t, '.block-editor-link-control button');
      t.expect(box, 'the link box under a Button').toContain('Edit link');
      t.expect(box, 'the link box under a Button').not.toContain('Remove link');

      await select(t, 'core/list-item');
      const item = await visible(t, '.block-editor-block-toolbar button');
      t.expect(item, 'a list item\'s toolbar (control)').toContain('Bold');
      t.expect(item, 'a list item\'s toolbar').not.toContain('Indent');
      t.expect(item, 'a list item\'s toolbar').not.toContain('Outdent');
      t.expect(await clickToolbar(t, 'Options'), 'the list item\'s ⋮ menu').toBe(true);
      await t.evaluate(() => new Promise((r) => setTimeout(r, 700)));
      const menu = await visible(t, '[role="menu"] [role="menuitem"]');
      t.expect(menu.join(' | '), 'the block menu (control)').toContain('Add after');
      t.expect(menu.join(' | '), 'the block menu').not.toContain('Add note');
      await t.press('Escape');

      await select(t, 'core/heading', 1);
      t.expect(await t.count('.block-editor-block-toolbar .block-editor-block-switcher__toggle'), 'the heading-level switcher').toBe(0);
      t.expect(await visible(t, '.block-editor-block-toolbar button'), 'a heading\'s toolbar').not.toContain('Change level');

      await select(t, 'core/cover');
      t.expect(await clickToolbar(t, 'Replace'), 'the photo\'s Replace button').toBe(true);
      await t.evaluate(() => new Promise((r) => setTimeout(r, 700)));
      t.expect(await visible(t, '.block-editor-media-replace-flow__options [role="menuitem"], .block-editor-media-replace-flow__options .components-menu-item__button'), 'the Replace menu').toEqual(['Choose a photo', 'Upload']);
      t.expectNoConsoleErrors();
    },
  },
  {
    name: 'ares-net: About opens with the Page review box (no placeholder) and no excerpt or Content list',
    role: 'ares-net',
    timeout: 150000,
    async run(t) {
      await openEditor(t, t.ids.pages.about);
      t.expect(await t.evaluate(() => wp.data.select('core/interface').getActiveComplementaryArea('core')), 'the settings sidebar').toBe('edit-post/document');
      await t.waitFor('#spk-owner', { visible: true, timeout: 15000 });
      t.expect(await t.attr('#spk-owner', 'placeholder'), 'the Page owner placeholder').toBe(null);
      // Panels (Content …) and meta boxes (Page review) have their own headings.
      await t.waitFor('.editor-sidebar__panel .hndle', { visible: true, timeout: 15000 });
      const panels = await visible(t, '.editor-sidebar__panel .components-panel__body-title, .editor-sidebar__panel .hndle');
      t.expect(panels, 'the sidebar\'s panels').toContain('Page review');
      t.expect(panels, 'the sidebar\'s panels').not.toContain('Content');
      t.expect(await t.text('.editor-sidebar__panel'), 'the Page panel').not.toContain('Edit excerpt');
      t.expectNoConsoleErrors();
    },
  },
  {
    name: 'ares-net: a Button saved without its link is refused with one plain sentence (no "Updating failed.")',
    role: 'ares-net',
    timeout: 150000,
    async run(t) {
      await openEditor(t, t.ids.pages.home);
      const orig = await t.evaluate(() => {
        const be = wp.data.select('core/block-editor');
        const id = be.getBlocksByName('core/button').filter((i) => be.getBlockEditingMode(i) === 'contentOnly')[0];
        const url = be.getBlockAttributes(id).url;
        wp.data.dispatch('core/block-editor').updateBlockAttributes(id, { url: undefined });
        return { id, url };
      });
      const r = await t.evaluate(savePage);
      t.expect(r.failed, 'the save was refused').toBe(true);
      const save = r.notices.find((n) => n.id === 'editor-save');
      t.expect(save && save.content, 'the save notice').toBe('Not saved: a button needs a link. Click the button, then the pencil in the box under it, paste the address and press Enter.');
      // Put the link back; nothing was stored.
      await t.evaluate((o) => { wp.data.dispatch('core/block-editor').updateBlockAttributes(o.id, { url: o.url }); return true; }, orig);
    },
  },
  {
    name: 'ares-net: a HEIC photo gets one plain sentence',
    role: 'ares-net',
    timeout: 150000,
    async run(t) {
      const dir = fs.mkdtempSync(path.join(os.tmpdir(), 'spokares-heic-'));
      const heic = path.join(dir, 'IMG_7001.HEIC');
      fs.copyFileSync(PHOTO, heic);
      try {
        await openEditor(t, t.ids.pages.home);
        await uploadViaReplace(t, heic);
        await t.waitForFunction(() => wp.data.select('core/notices').getNotices().some((n) => n.status === 'error' && /JPEG, PNG or WebP/.test(String(n.content))), { timeout: 20000 }).catch(() => {});
        const errors = await t.evaluate(() => wp.data.select('core/notices').getNotices().filter((n) => n.status === 'error').map((n) => String(n.content)));
        t.expect(errors, 'the error notices').toEqual(['Photos must be JPEG, PNG or WebP. On an iPad, pick the photo from Photo Library, which converts it.']);
      } finally {
        fs.rmSync(dir, { recursive: true, force: true });
      }
    },
  },
  {
    name: 'ares-net: a new Home photo saves and leaves the editor clean (no "Leave site?" after "Saved")',
    role: 'ares-net',
    timeout: 180000,
    async run(t) {
      await openEditor(t, t.ids.pages.home);
      const before = await t.evaluate(() => {
        const be = wp.data.select('core/block-editor');
        const id = be.getBlocksByName('core/cover')[0];
        return { id, attrs: be.getBlockAttributes(id) };
      });
      await uploadViaReplace(t, OTHER);
      await t.waitForFunction((was) => {
        const be = wp.data.select('core/block-editor');
        const a = be.getBlockAttributes(be.getBlocksByName('core/cover')[0]);
        return a.id && a.id !== was && /\/wp-content\/uploads\//.test(a.url || '');
      }, { timeout: 60000, args: [before.attrs.id] });
      // The Cover works out the new photo's colour after the upload.
      await t.evaluate(() => new Promise((r) => setTimeout(r, 2500)));
      try {
        const r = await t.evaluate(savePage);
        t.expect(r.failed, 'the save').toBe(false);
        t.expect(r.notices.find((n) => n.id === 'editor-save')?.content, 'the saved notice').toBe('Saved. It’s on the site now.');
        t.expect(r.dirty, 'unsaved changes right after the save').toBe(false);
        await t.evaluate(() => new Promise((r2) => setTimeout(r2, 2000)));
        t.expect(await t.evaluate(() => wp.data.select('core/editor').isEditedPostDirty()), 'unsaved changes two seconds later').toBe(false);
      } finally {
        // Put Home's own photo back.
        await t.evaluate((b) => {
          const keep = ['url', 'id', 'alt', 'focalPoint', 'sizeSlug', 'isDark', 'isUserOverlayColor', 'customOverlayColor', 'overlayColor', 'useFeaturedImage'];
          const attrs = {};
          keep.forEach((k) => { attrs[k] = b.attrs[k]; });
          wp.data.dispatch('core/block-editor').updateBlockAttributes(wp.data.select('core/block-editor').getBlocksByName('core/cover')[0], attrs);
          return true;
        }, before);
        const back = await t.evaluate(savePage);
        t.expect(back.failed, 'putting Home\'s photo back').toBe(false);
      }
      t.expectNoConsoleErrors();
    },
  },
  {
    name: 'admin: the editor keeps its Options menu (control)',
    role: 'admin',
    timeout: 150000,
    async run(t) {
      await t.unlockPosts();
      await t.goto(`/wp-admin/post.php?post=${t.ids.pages.home}&action=edit`);
      const facts = await t.blockEditor();
      t.expect(facts.ready, 'block editor ready').toBe(true);
      await t.evaluate(() => new Promise((r) => setTimeout(r, 1200)));
      t.expect(await visible(t, '.editor-header button'), 'the header').toContain('Options');
    },
  },
];
