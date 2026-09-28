// DEV ONLY. Regression test for a Fixer-round bug that shipped without a test
// (build-notes/plugin.md "Fixer round" › Pages; PLAN §8.2 #14): pasting
// several paragraphs into a paragraph of a locked page did nothing at all,
// because core turns them into new paragraph blocks and the layout lock
// forbids new blocks. The fix (assets/js/editor-guard.js, onPaste): they
// arrive as one paragraph with line breaks (one line in a heading or a
// button), with a notice. Plain text splits on blank lines; Word and e-mail
// HTML is read for its paragraphs.
//
// The paste is a real "paste" ClipboardEvent on the paragraph in the editor
// canvas, carrying a DataTransfer like the clipboard's. Nothing is saved:
// the changed words are put back and autosave is held off, so the page and
// its autosave are as they were.

/** Open Home in the block editor as the test's role and wait for it. */
async function openHome(t) {
  await t.unlockPosts();
  await t.goto(`/wp-admin/post.php?post=${t.ids.pages.home}&action=edit`);
  await t.expectStatus(200);
  const facts = await t.blockEditor();
  t.expect(facts.ready, 'block editor ready').toBe(true);
  t.expect(facts.templateLock, 'the page is content-only for this role').toBe('contentOnly');
  await t.evaluate(() => { wp.data.dispatch('core/editor').updateEditorSettings({ autosaveInterval: 100000 }); return true; });
}

/**
 * Paste into the first content-only block of a type, then read the result and
 * put the block's words back. Runs in the page (t.evaluate).
 */
async function pasteInto(name, clip) {
  const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
  const be = wp.data.select('core/block-editor');
  const bd = wp.data.dispatch('core/block-editor');
  const ids = be.getBlocksByName(name).filter((id) => be.getBlockEditingMode(id) === 'contentOnly'
    && String(be.getBlockAttributes(id).content || '').replace(/<[^>]+>/g, '').trim().length > 3);
  if (!ids.length) return { error: `no content-only ${name} with words` };
  const id = ids[0];
  const original = be.getBlockAttributes(id).content;
  const text = String(original).replace(/<[^>]+>/g, '');
  const blocksBefore = be.getClientIdsWithDescendants().length;
  wp.data.dispatch('core/notices').removeNotice('spokares-paste');

  // Put the caret at the end of the block's words, as a click there would.
  bd.selectionChange(id, 'content', text.length, text.length);
  await sleep(300);
  const iframe = document.querySelector('iframe[name="editor-canvas"]');
  const doc = iframe ? iframe.contentDocument : document;
  const win = iframe ? iframe.contentWindow : window;
  const el = doc.querySelector(`[data-block="${id}"]`);
  if (!el) return { error: 'block element not found in the canvas' };
  const dt = new win.DataTransfer();
  Object.entries(clip).forEach(([type, value]) => dt.setData(type, value));
  const ev = new win.ClipboardEvent('paste', { clipboardData: dt, bubbles: true, cancelable: true });
  el.dispatchEvent(ev);
  await sleep(500);

  const after = String(be.getBlockAttributes(id)?.content ?? '');
  const notice = wp.data.select('core/notices').getNotices().find((n) => n.id === 'spokares-paste');
  const result = {
    after,
    defaultPrevented: ev.defaultPrevented,
    blocksBefore,
    blocksAfter: be.getClientIdsWithDescendants().length,
    stillThere: !!be.getBlock(id),
    notice: notice ? String(notice.content) : '',
  };
  // Put the words back so nothing is left to save.
  if (be.getBlock(id)) bd.updateBlockAttributes(id, { content: original });
  return result;
}

export const tests = [
  {
    name: 'ares-editor: plain text with blank lines pasted into a paragraph arrives as one paragraph with line breaks',
    role: 'ares-editor',
    timeout: 150000,
    async run(t) {
      await openHome(t);
      const r = await t.evaluate(pasteInto, 'core/paragraph', { 'text/plain': 'First pasted paragraph.\n\nSecond pasted\nparagraph, wrapped.\n\nThird pasted paragraph.' });
      t.expect(r.error, 'setup').toBe(undefined);
      t.expect(r.after, 'paragraph words').toContain('First pasted paragraph.<br>Second pasted paragraph, wrapped.<br>Third pasted paragraph.');
      t.expect(r.blocksAfter, 'blocks on the page (no new paragraphs)').toBe(r.blocksBefore);
      t.expect(r.stillThere, 'the paragraph is still there').toBe(true);
      t.expect(r.notice, 'notice').toContain('Pasted as one paragraph with line breaks');
      t.expectNoConsoleErrors();
    },
  },
  {
    name: 'ares-editor: Word/e-mail HTML paragraphs pasted into a paragraph arrive as one paragraph with line breaks',
    role: 'ares-editor',
    timeout: 150000,
    async run(t) {
      await openHome(t);
      const html = '<html><body><!--StartFragment--><p class="MsoNormal">Alpha pasted line.</p><p class="MsoNormal">Beta <b>pasted</b> line.</p><!--EndFragment--></body></html>';
      const r = await t.evaluate(pasteInto, 'core/paragraph', { 'text/html': html, 'text/plain': 'Alpha pasted line.\r\n\r\nBeta pasted line.' });
      t.expect(r.error, 'setup').toBe(undefined);
      t.expect(r.after, 'paragraph words').toContain('Alpha pasted line.<br>Beta pasted line.');
      t.expect(r.blocksAfter, 'blocks on the page (no new paragraphs)').toBe(r.blocksBefore);
      t.expect(r.notice, 'notice').toContain('Pasted as one paragraph with line breaks');
      t.expectNoConsoleErrors();
    },
  },
  {
    name: 'core-editor: several lines pasted into a heading arrive as one line',
    role: 'core-editor',
    timeout: 150000,
    async run(t) {
      await openHome(t);
      const r = await t.evaluate(pasteInto, 'core/heading', { 'text/plain': 'Heading line one\nHeading line two' });
      t.expect(r.error, 'setup').toBe(undefined);
      t.expect(r.after, 'heading words').toContain('Heading line one Heading line two');
      t.expect(r.after, 'heading words').not.toContain('<br');
      t.expect(r.blocksAfter, 'blocks on the page').toBe(r.blocksBefore);
      t.expect(r.notice, 'notice').toContain('Pasted as one line');
      t.expectNoConsoleErrors();
    },
  },
];
