// DEV ONLY. The verifier's last polish on the volunteer-editor screens, in the
// browser (ux/REPORT.md, "Fixed by the verifier"): the name check on Add an
// Event, whose "Open it" leaves without "Leave site?" and without a stray
// draft; the course links in Add a Document's name check; the photo window's
// plain words; and no WordPress chrome on the lists and the Save box. The
// server side is in dev/tests/php/ux-verifier-polish-test.php. Nothing is
// saved.

/** Click "Open it" like a person (mouse down and up on the link) and wait for the event or document it opens. */
async function openIt(t) {
  t.page.dialogs = [];
  await t.clickAndWait('#spk-name-match a');
  await t.expectStatus(200);
}

/** The drafts of a post type, by name. */
async function drafts(t, type) {
  await t.goto(`/wp-admin/edit.php?post_type=${type}&post_status=draft`);
  return t.texts('#the-list .row-title');
}

export const tests = [
  {
    name: 'ares-net: Add an Event says when the name is already an event, and "Open it" goes there with no "Leave site?" and no draft left behind',
    role: 'ares-net',
    async run(t) {
      await t.goto('/wp-admin/post-new.php?post_type=spk_event');
      await t.expectStatus(200);
      const typed = 'Lilac parade';
      await t.type('#title', typed, { clear: true });
      await t.waitForText('Already an event:', { selector: '#spk-name-match' });
      t.expect(await t.text('#spk-name-match'), 'the line under the name').toBe('Already an event: Lilac Festival Armed Forces Torchlight Parade (Date not posted yet). Open it');
      await t.type('#title', `QA brand new event ${Date.now()}`, { clear: true });
      t.expect(await t.text('#spk-name-match'), 'no line for a new name').toBe('');
      await t.type('#title', typed, { clear: true });
      await t.waitForText('Already an event:', { selector: '#spk-name-match' });
      await openIt(t);
      t.expect(await t.evaluate(() => document.querySelector('#title')?.value), 'the event it opened').toBe('Lilac Festival Armed Forces Torchlight Parade');
      t.expect(t.page.dialogs.map((d) => d.type), 'no "Leave site?" on the way').toEqual([]);
      t.expect(await drafts(t, 'spk_event'), 'the typed name was not saved as a draft').not.toContain(typed);
      t.expectNoConsoleErrors();
    },
  },
  {
    name: 'ares-net: Add a Document finds a course link under a document ("IS-100.c, under FEMA courses"), and "Open it" leaves no draft',
    role: 'ares-net',
    async run(t) {
      await t.goto('/wp-admin/post-new.php?post_type=spk_document');
      await t.expectStatus(200);
      const typed = 'FEMA IS-100';
      await t.type('#title', typed, { clear: true });
      await t.waitForText('Already on the site:', { selector: '#spk-name-match' });
      t.expect(await t.text('#spk-name-match'), 'the line under the name').toBe('Already on the site: IS-100.c, under FEMA courses. Open it');
      await t.type('#title', 'IS-100.c: Introduction to the Incident Command System', { clear: true });
      t.expect(await t.text('#spk-name-match'), 'the course\'s title finds it too').toBe('Already on the site: IS-100.c, under FEMA courses. Open it');
      await openIt(t);
      t.expect(await t.evaluate(() => document.querySelector('#title')?.value), 'the document it opened').toBe('FEMA courses');
      t.expect(t.page.dialogs.map((d) => d.type), 'no "Leave site?" on the way').toEqual([]);
      t.expect(await drafts(t, 'spk_document'), 'no draft left behind').not.toContain('IS-100.c: Introduction to the Incident Command System');
      t.expectNoConsoleErrors();
    },
  },
  {
    name: 'ares-net: the lists have no Screen Options, the events list no bulk actions or row ticks, and the Save box no move or fold buttons',
    role: 'ares-net',
    async run(t) {
      for (const type of ['spk_event', 'spk_document', 'page']) {
        await t.goto(`/wp-admin/edit.php?post_type=${type}`);
        await t.expectStatus(200);
        t.expect(await t.exists('#show-settings-link'), `${type} list: Screen Options`).toBe(false);
      }
      await t.goto('/wp-admin/edit.php?post_type=spk_event');
      t.expect(await t.exists('#bulk-action-selector-top'), 'events list: bulk actions').toBe(false);
      t.expect(await t.count('#the-list input[type="checkbox"]'), 'events list: row ticks').toBe(0);
      t.expect(await t.count('#the-list tr'), 'the events are listed').toBeGreaterThan(5);
      await t.goto(`/wp-admin/post.php?post=${t.ids.events['set-2026']}&action=edit`);
      await t.expectHidden('#spokares_savebox .handle-actions');
      t.expect(await t.text('#spokares_savebox'), 'the Save box').toContain('Take it off the site');
    },
  },
  {
    name: 'ares-net: the photo window reads "Choose a photo", "Upload a photo", "Photos on the site" and "This photo", with no Edit Image',
    role: 'ares-net',
    timeout: 150000,
    async run(t) {
      await t.unlockPosts();
      await t.goto(`/wp-admin/post.php?post=${t.ids.pages.home}&action=edit`);
      await t.expectStatus(200);
      const facts = await t.blockEditor();
      t.expect(facts.ready, 'block editor ready').toBe(true);
      await t.evaluate(() => { wp.data.dispatch('core/editor').updateEditorSettings({ autosaveInterval: 100000 }); return true; });
      await t.evaluate(() => {
        const be = wp.data.select('core/block-editor');
        wp.data.dispatch('core/block-editor').selectBlock(be.getBlocksByName('core/cover')[0]);
        return true;
      });
      await t.evaluate(() => new Promise((r) => setTimeout(r, 900)));
      const clicked = await t.evaluate(() => {
        const b = [...document.querySelectorAll('.block-editor-block-toolbar button')].find((x) => x.innerText.trim() === 'Replace');
        if (b) b.click();
        return !!b;
      });
      t.expect(clicked, 'the photo\'s Replace button').toBe(true);
      await t.evaluate(() => new Promise((r) => setTimeout(r, 700)));
      const item = await t.evaluate(() => {
        const m = [...document.querySelectorAll('.block-editor-media-replace-flow__options [role="menuitem"], .block-editor-media-replace-flow__options .components-menu-item__button')].find((x) => x.innerText.trim() === 'Choose a photo');
        if (m) m.click();
        return !!m;
      });
      t.expect(item, 'Replace › Choose a photo').toBe(true);
      await t.waitFor('.media-modal', { visible: true, timeout: 15000 });
      await t.evaluate(() => new Promise((r) => setTimeout(r, 900)));
      const words = await t.evaluate(() => ({
        title: document.querySelector('.media-modal .media-frame-title h1')?.innerText.trim(),
        tabs: [...document.querySelectorAll('.media-modal .media-router [role="tab"]')].map((b) => b.innerText.trim()),
      }));
      t.expect(words.title, 'the window\'s title').toBe('Choose a photo');
      t.expect(words.tabs, 'the tabs').toEqual(['Upload a photo', 'Photos on the site']);
      // The side panel a picked photo shows (its template), and no Edit Image.
      const side = await t.evaluate(() => {
        const tpl = document.getElementById('tmpl-attachment-details')?.textContent || '';
        const probe = document.createElement('div');
        probe.className = 'media-modal';
        probe.innerHTML = '<div class="attachment-details"><a class="edit-attachment" href="#">x</a></div>';
        document.body.appendChild(probe);
        const shown = getComputedStyle(probe.querySelector('.edit-attachment')).display !== 'none';
        probe.remove();
        return { title: /<h2>\s*This photo/.test(tpl), wp: /Attachment Details/.test(tpl), alt: tpl.includes('Describe the photo in a few words'), editImage: shown };
      });
      t.expect(side, 'the side panel').toEqual({ title: true, wp: false, alt: true, editImage: false });
      await t.press('Escape');
      t.expectNoConsoleErrors();
    },
  },
];
