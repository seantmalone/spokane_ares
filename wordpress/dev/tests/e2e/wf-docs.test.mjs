// DEV ONLY. Browser tests for the workflow review's document findings that
// need a real file upload (no PHP test can make one: is_uploaded_file()):
//
//   D2   A new file with a fresh "I checked the new file" tick sets Last
//        reviewed to today.
//   D11  The "The file was pulled from the web by X" banner clears once a new
//        file is accepted and published.
//   D6   "Take it off the site" takes the file off the web (its address is a
//        404) and Undo brings the document back Published with the same file
//        at the same address.
//   D9   The file box is printed enabled; the script still locks it until
//        the tick.
//   D8   A file that passes its own checks on a save refused for another box
//        (a never-publish word in the name) waits: the form says so, the
//        next Publish or Save uses it without choosing it again, and on a
//        live document the file on the site stays until then.
//   D1   The editor's form has the tick "Mark reviewed today when I save"
//        with "Last reviewed … by …" beside it, and no date box.
//
// Each run adds its own document (a new name each time) and leaves it in the
// Trash.

import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';

function pdf(name) {
  const dir = fs.mkdtempSync(path.join(os.tmpdir(), 'spokares-wf-docs-'));
  const file = path.join(dir, name);
  fs.writeFileSync(file, '%PDF-1.4\n1 0 obj << /Type /Catalog >> endobj\ntrailer << /Root 1 0 R >>\n%%EOF\n');
  return file;
}

async function chooseFile(t, file) {
  const { result } = await t.page.send('Runtime.evaluate', { expression: 'document.querySelector("#spk-upload")' });
  t.expect(!!result.objectId, 'the file box').toBe(true);
  await t.page.send('DOM.enable');
  await t.page.send('DOM.setFileInputFiles', { files: [file], objectId: result.objectId });
}

async function status(t, url) {
  return t.evaluate(async (u) => (await fetch(u, { cache: 'no-store', redirect: 'manual' })).status, url);
}

export const tests = [
  {
    name: 'admin: a new file sets Last reviewed to today and clears the "pulled" banner; Take it off the site and Undo keep the file',
    role: 'admin',
    timeout: 180000,
    async run(t) {
      const title = `WF docs ${Date.now()}`;
      await t.goto('/wp-admin/post-new.php?post_type=spk_document');
      await t.expectStatus(200);
      // D9: enabled in the HTML, locked by the script until the tick.
      const html = await t.evaluate(async () => (await (await fetch(location.href, { cache: 'no-store' })).text()));
      t.expect(/<input type="file" id="spk-upload"[^>]*\sdisabled/.test(html), 'the file box is disabled in the HTML').toBe(false);
      t.expect(await t.evaluate(() => document.querySelector('#spk-upload').disabled), 'the script locks it until the tick').toBe(true);

      await t.type('#title', title, { clear: true });
      await t.click('input[name="spk_section"][value="forms"]');
      await t.click('#spk-privacy');
      await chooseFile(t, pdf('wf-first.pdf'));
      await t.clickAndWait('input[name="publish"]');
      await t.waitFor('.spk-current-file a');
      const id = Number(new URL(await t.url()).searchParams.get('post'));
      t.expect(id, 'the new document').toBeGreaterThan(0);
      const today = await t.evaluate(() => (window.spokaresAdmin || {}).today || '');
      t.expect(today, 'today').toMatch(/^\d{4}-\d{2}-\d{2}$/);
      t.expect(await t.evaluate(() => document.querySelector('#spk-reviewed').value), 'the first file is a review').toBe(today);

      // An old review date, saved.
      await t.evaluate(() => { document.querySelector('details.spk-more').open = true; document.querySelector('#spk-reviewed').value = '2020-01-02'; });
      await t.clickAndWait('input[name="save"]');
      t.expect(await t.evaluate(() => document.querySelector('#spk-reviewed').value), 'set-up: an old date').toBe('2020-01-02');

      // D2: a replacement with the tick makes it today.
      await t.click('#spk-privacy');
      await chooseFile(t, pdf('wf-second.pdf'));
      await t.clickAndWait('input[name="save"]');
      t.expect(await t.text('.notice'), 'the save').toContain('The new file is on the site');
      t.expect(await t.evaluate(() => document.querySelector('#spk-reviewed').value), 'a replacement with the tick is a review').toBe(today);

      // D11: Pull, then a new file published clears the banner.
      await t.goto('/wp-admin/edit.php?post_type=spk_document');
      const pull = await t.evaluate((docId) => {
        const a = [...document.querySelectorAll('a')].find((x) => x.href.includes('action=spokares_pull_file') && x.href.includes(`post=${docId}`));
        return a ? a.href : '';
      }, id);
      t.expect(pull, 'the Pull link').toContain('_wpnonce=');
      await t.goto(pull);
      await t.goto(`/wp-admin/post.php?post=${id}&action=edit`);
      t.expect(await t.text('.spk-doc-form'), 'the banner after Pull').toContain('The file was pulled from the web by');
      await t.click('input[name="spk_source"][value="upload"]');
      await t.click('#spk-privacy');
      await chooseFile(t, pdf('wf-third.pdf'));
      await t.clickAndWait('input[name="publish"]');
      t.expect(await t.text('.spk-doc-form'), 'no banner once a new file is published').not.toContain('was pulled from the web');

      // D6: Take it off the site, then Undo.
      const fileUrl = await t.attr('.spk-current-file a', 'href');
      t.expect(await status(t, fileUrl), 'the file is on the web').toBe(200);
      await t.clickAndWait('#spk-trash-link');
      t.expect(await t.text('#message, .notice, .updated'), 'off the site').toContain('is off the site');
      t.expect(await status(t, fileUrl), 'the file is off the web').toBe(404);
      await t.clickAndWait('a[href*="doaction=undo"]');
      t.expect(await t.text('#message, .notice, .updated'), 'Undo').toContain('is back on the site');
      t.expect(await status(t, fileUrl), 'the same file is back at the same address').toBe(200);

      // Leave it in the Trash.
      await t.goto(`/wp-admin/post.php?post=${id}&action=edit`);
      await t.clickAndWait('#spk-trash-link');
      t.expectNoConsoleErrors();
    },
  },
  {
    name: 'editor: a file chosen on a refused save waits, and the next save uses it without choosing it again (D8, D1)',
    role: 'ares-editor',
    timeout: 180000,
    async run(t) {
      const stamp = Date.now();
      const title = `WF D8 ${stamp}`;
      await t.goto('/wp-admin/post-new.php?post_type=spk_document');
      await t.expectStatus(200);

      // Refused: a never-publish word in the name. The file itself is fine.
      await t.type('#title', `${title} hospital net`, { clear: true });
      await t.click('input[name="spk_section"][value="forms"]');
      await t.click('#spk-privacy');
      await chooseFile(t, pdf('wf-d8-first.pdf'));
      await t.clickAndWait('input[name="publish"]');
      await t.waitFor('.spk-doc-form');
      const id = Number(new URL(await t.url()).searchParams.get('post'));
      t.expect(id, 'the new document').toBeGreaterThan(0);
      t.expect(await t.text('.notice'), 'the refusal says the file waits').toContain('Your file wf-d8-first.pdf is uploaded and waiting');
      t.expect(await t.text('.spk-pending-file'), 'the form says so').toBe('Your file wf-d8-first.pdf is uploaded and waiting: fix the boxes outlined in red, then Publish (no need to choose it again).');
      t.expect(await t.exists('.spk-current-file'), 'not the document\'s file yet').toBe(false);
      t.expect(await t.evaluate(() => document.querySelector('#spk-privacy').required), 'no tick needed again').toBe(false);

      // Fixed: Publish with no file chosen.
      await t.type('#title', title, { clear: true });
      await t.clickAndWait('input[name="publish"]');
      t.expect(await t.text('.notice'), 'published').toContain('Published.');
      t.expect(await t.text('.spk-current-file'), 'the waiting file is the file').toContain('wf-d8-first.pdf');
      t.expect(await t.exists('.spk-pending-file'), 'nothing waits').toBe(false);
      const first = await t.attr('.spk-current-file a', 'href');
      t.expect(await status(t, first), 'on the web').toBe(200);

      // D1: the editor's review control.
      t.expect(await t.text('.spk-reviewed-line'), 'the line').toMatch(/^Last reviewed [A-Z][a-z]{2} \d{1,2}, \d{4} by .+\.$/);
      t.expect(await t.exists('input[name="spk_mark_reviewed"]'), 'the tick').toBe(true);
      t.expect(await t.exists('#spk-reviewed'), 'no date box for an editor').toBe(false);

      // Live: a new file on a refused Save waits; the live file stays.
      await t.type('#title', `${title} hospital net`, { clear: true });
      await t.click('#spk-privacy');
      await chooseFile(t, pdf('wf-d8-second.pdf'));
      await t.clickAndWait('input[name="save"]');
      t.expect(await t.text('.notice'), 'saved, except').toContain('Saved, except the document name');
      t.expect(await t.text('.spk-pending-file'), 'the new file waits').toContain('Your file wf-d8-second.pdf is uploaded and waiting: fix the boxes outlined in red, then Save');
      t.expect(await t.attr('.spk-current-file a', 'href'), 'the live file stays').toBe(first);
      t.expect(await status(t, first), 'and stays on the web').toBe(200);

      await t.type('#title', title, { clear: true });
      await t.clickAndWait('input[name="save"]');
      t.expect(await t.text('.notice'), 'swapped').toContain('The new file is on the site');
      t.expect(await t.text('.spk-current-file'), 'the new file').toContain('wf-d8-second.pdf');
      t.expect(await status(t, first), 'the old file is off the web').toBe(404);

      // Leave it in the Trash.
      await t.clickAndWait('#spk-trash-link');
      t.expectNoConsoleErrors();
    },
  },
];
