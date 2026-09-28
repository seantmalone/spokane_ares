// DEV ONLY. Test for QA-107 (PLAN §4.4, "Page text"): a page's excerpt is
// its <meta name="description"> (theme inc/head.php), so it is public text,
// but the editor guard (plugins/spokares-core/assets/js/editor-guard.js)
// scanned only the page's blocks after a save. An ARES Editor could set
// About's excerpt to "Call 509-555-9999" in the Page panel and save it with
// no warning.
//
// The excerpt is the search-engine description, the webmaster's (UX spec
// §3.8): editors no longer see the Excerpt panel, and the check of it is on
// the administrators' Dashboard (dev/tests/php/qa-107-excerpt-check-test.php).
// For editors the guard checks the page text after each save and names what
// it found; saving is never blocked.
//
// The test saves About twice (the phone number in its text, then the
// original text again), and puts the original back over REST if anything
// fails between.

const BAD = 'Call 509-555-9999 about the Tuesday net.';

/** Open About in the block editor as the test's role and wait for it. */
async function openAbout(t) {
  await t.unlockPosts();
  await t.goto(`/wp-admin/post.php?post=${t.ids.pages.about}&action=edit`);
  await t.expectStatus(200);
  const facts = await t.blockEditor();
  t.expect(facts.ready, 'block editor ready').toBe(true);
  t.expect(facts.templateLock, 'the page is content-only for this role').toBe('contentOnly');
  await t.evaluate(() => { wp.data.dispatch('core/editor').updateEditorSettings({ autosaveInterval: 100000 }); return true; });
}

/**
 * Put some words at the end of About's first paragraph (or put the original
 * back), save, and read the guard's notices once the save is over. Runs in
 * the page.
 */
async function saveText(words, original) {
  const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
  const be = wp.data.select('core/block-editor');
  const ed = wp.data.select('core/editor');
  const editPost = wp.data.select('core/edit-post');
  const id = be.getBlocksByName('core/paragraph').filter((i) => be.getBlockEditingMode(i) === 'contentOnly')[0];
  const before = String(be.getBlockAttributes(id).content);
  wp.data.dispatch('core/block-editor').updateBlockAttributes(id, { content: original === undefined ? `${before} ${words}` : original });
  await wp.data.dispatch('core/editor').savePost();
  const end = Date.now() + 30000;
  while ((ed.isSavingPost() || (editPost && editPost.isSavingMetaBoxes && editPost.isSavingMetaBoxes())) && Date.now() < end) await sleep(100);
  await sleep(600);
  const notices = wp.data.select('core/notices').getNotices();
  const content = (nid) => String(notices.find((n) => n.id === nid)?.content || '');
  return {
    before,
    failed: ed.didPostSaveRequestFail(),
    dirty: ed.isEditedPostDirty(),
    text: content('spokares-never-publish'),
    excerpt: content('spokares-never-publish-excerpt'),
  };
}

export const tests = [
  {
    name: 'ares-editor: About shows no excerpt (the webmaster\'s search-engine description); a phone number saved in its text brings the warning naming it; saving the clean text clears it',
    role: 'ares-editor',
    timeout: 150000,
    async run(t) {
      await openAbout(t);
      // The Page panel (About opens with it): no excerpt and no "Edit excerpt".
      await t.waitFor('.editor-post-card-panel', { visible: true, timeout: 15000 });
      const panel = await t.text('.editor-sidebar__panel');
      t.expect(panel, 'the Page panel').not.toContain('Edit excerpt');
      const excerpt = await t.evaluate(() => wp.data.select('core/editor').getEditedPostAttribute('excerpt'));
      t.expect(typeof excerpt === 'string' && excerpt.length > 10, `About has a description (control): ${JSON.stringify(excerpt)}`).toBe(true);
      t.expect(panel, 'the Page panel shows the description').not.toContain(excerpt.slice(0, 40));

      let original = null;
      let restored = false;
      try {
        const bad = await t.evaluate(saveText, BAD);
        original = bad.before;
        t.expect(bad.failed, 'the save went through (the check warns, it never blocks)').toBe(false);
        t.expect(bad.text, 'the page-text warning').toBe('On the site now: this page has what looks like a phone number (509-555-9999). If it isn’t public, take it out and Save.');
        t.expect(bad.excerpt, 'an excerpt warning (the description is the webmaster\'s)').toBe('');

        const back = await t.evaluate(saveText, '', original);
        restored = !back.failed && !back.dirty;
        t.expect(restored, `the original text saved again (${JSON.stringify(back)})`).toBe(true);
        t.expect(back.text, 'the page-text warning after saving the clean text').toBe('');
      } finally {
        if (!restored && original !== null) {
          await t.evaluate((o) => {
            const be = wp.data.select('core/block-editor');
            const id = be.getBlocksByName('core/paragraph').filter((i) => be.getBlockEditingMode(i) === 'contentOnly')[0];
            wp.data.dispatch('core/block-editor').updateBlockAttributes(id, { content: o });
            return wp.data.dispatch('core/editor').savePost().then(() => true, () => false);
          }, original).catch(() => {});
        }
      }
      t.expectNoConsoleErrors();
      t.expectNoPhpErrors();
    },
  },
];
