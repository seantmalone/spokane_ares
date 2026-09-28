// DEV ONLY. Test for QA-107 (PLAN §4.4, "Page text"): a page's excerpt is
// its <meta name="description"> (theme inc/head.php), so it is public text,
// but the editor guard (plugins/spokares-core/assets/js/editor-guard.js)
// scanned only the page's blocks after a save. An ARES Editor could set
// About's excerpt to "Call 509-555-9999" in the Page panel and save it with
// no warning.
//
// Now the guard scans the excerpt too and shows its own warning (the words
// come from governance.php, spokaresGuard.excerpt). Saving is never blocked.
// The Dashboard side is in dev/tests/php/qa-107-excerpt-check-test.php.
//
// The test saves About twice (the phone number, then the original excerpt
// again), and puts the original back over REST if anything fails between.

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
 * Set the excerpt as the Page panel does, save, and read the guard's notices
 * once the save (and the meta box save after it) is over. Runs in the page.
 */
async function saveExcerpt(excerpt) {
  const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
  const ed = wp.data.select('core/editor');
  const editPost = wp.data.select('core/edit-post');
  wp.data.dispatch('core/editor').editPost({ excerpt });
  await wp.data.dispatch('core/editor').savePost();
  const end = Date.now() + 30000;
  while ((ed.isSavingPost() || (editPost && editPost.isSavingMetaBoxes && editPost.isSavingMetaBoxes())) && Date.now() < end) await sleep(100);
  // The guard scans on the next tick after the save ends.
  await sleep(600);
  const notices = wp.data.select('core/notices').getNotices();
  const content = (id) => String(notices.find((n) => n.id === id)?.content || '');
  return {
    failed: ed.didPostSaveRequestFail(),
    dirty: ed.isEditedPostDirty(),
    saved: ed.getEditedPostAttribute('excerpt'),
    excerpt: content('spokares-never-publish-excerpt'),
    text: content('spokares-never-publish'),
  };
}

export const tests = [
  {
    name: 'ares-editor: a phone number saved in About\'s excerpt brings the excerpt warning; saving a clean excerpt clears it',
    role: 'ares-editor',
    timeout: 150000,
    async run(t) {
      await openAbout(t);
      const original = await t.evaluate(() => wp.data.select('core/editor').getEditedPostAttribute('excerpt'));
      t.expect(typeof original === 'string' && original.length > 10, `About has an excerpt (control): ${JSON.stringify(original)}`).toBe(true);
      let restored = false;
      try {
        const bad = await t.evaluate(saveExcerpt, BAD);
        t.expect(bad.failed, 'the save went through (the check warns, it never blocks)').toBe(false);
        t.expect(bad.saved, 'saved excerpt').toBe(BAD);
        t.expect(bad.excerpt, 'the excerpt warning').toContain('a phone number');
        t.expect(bad.excerpt, 'the excerpt warning says it is about the excerpt').toContain('excerpt');
        t.expect(bad.text, 'the page-text warning (About\'s text itself is clean)').toBe('');

        const back = await t.evaluate(saveExcerpt, original);
        restored = !back.failed && !back.dirty && back.saved === original;
        t.expect(restored, `the original excerpt saved again (${JSON.stringify(back)})`).toBe(true);
        t.expect(back.excerpt, 'the excerpt warning after saving a clean excerpt').toBe('');
      } finally {
        if (!restored) {
          await t.evaluate((id, excerpt) => wp.apiFetch({ path: `/wp/v2/pages/${id}`, method: 'POST', data: { excerpt } }).then(() => true, () => false), t.ids.pages.about, original).catch(() => {});
        }
      }
      t.expectNoConsoleErrors();
      t.expectNoPhpErrors();
    },
  },
];
