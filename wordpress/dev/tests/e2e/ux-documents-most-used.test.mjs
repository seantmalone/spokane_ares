// DEV ONLY. The document form's and the Most Used screen's script
// (assets/js/admin-documents.js; ux/SPEC.md §3.6, §3.7), in the browser, as
// the ARES Editor with the Net Settings grant. Nothing is saved.

export const tests = [
  {
    name: 'ares-net: Most Used: a new document empties its words and moves the "Replace or change it" link; one already a button swaps',
    role: 'ares-net',
    async run(t) {
      await t.goto('/wp-admin/admin.php?page=spokares-tiles');
      await t.expectStatus(200);
      t.expect(await t.text('h1'), 'the screen\'s name').toBe('Most Used');
      const before = await t.evaluate(() => [...document.querySelectorAll('tr.spk-tile-row')].map((r) => ({
        doc: r.querySelector('.spk-tile-doc').value,
        words: r.querySelector('.spk-tile-label').value,
        edit: r.querySelector('.spk-tile-edit').getAttribute('href'),
      })));
      t.expect(before.length, 'four buttons').toBe(4);
      for (const row of before) {
        t.expect(row.edit, 'each row links to its document\'s form').toContain(`post.php?post=${row.doc}&action=edit`);
      }

      // A document that is no button: the words go, the name is the placeholder.
      const pick = await t.evaluate((taken) => {
        const sel = document.querySelector('tr.spk-tile-row .spk-tile-doc');
        const o = [...sel.options].find((x) => x.value !== '0' && !taken.includes(x.value));
        sel.value = o.value;
        sel.dispatchEvent(new Event('change', { bubbles: true }));
        const row = sel.closest('tr');
        return {
          id: o.value,
          title: o.getAttribute('data-title'),
          words: row.querySelector('.spk-tile-label').value,
          placeholder: row.querySelector('.spk-tile-label').placeholder,
          edit: row.querySelector('.spk-tile-edit').getAttribute('href'),
          hidden: row.querySelector('.spk-tile-edit').hidden,
        };
      }, before.map((r) => r.doc));
      t.expect(pick.words, 'the old words are emptied').toBe('');
      t.expect(pick.placeholder, 'the placeholder is the document\'s name').toBe(pick.title);
      t.expect(pick.edit, 'the link follows the choice').toContain(`post.php?post=${pick.id}&action=edit`);
      t.expect(pick.hidden, 'the link shows').toBe(false);

      // "— none —": no link.
      const none = await t.evaluate(() => {
        const sel = document.querySelector('tr.spk-tile-row .spk-tile-doc');
        sel.value = '0';
        sel.dispatchEvent(new Event('change', { bubbles: true }));
        return sel.closest('tr').querySelector('.spk-tile-edit').hidden;
      });
      t.expect(none, 'no link without a document').toBe(true);

      // Button 3's document in button 2: the two swap, words and all.
      const swap = await t.evaluate((docs) => {
        const rows = [...document.querySelectorAll('tr.spk-tile-row')];
        const sel = rows[1].querySelector('.spk-tile-doc');
        sel.value = docs[2];
        sel.dispatchEvent(new Event('change', { bubbles: true }));
        return rows.map((r) => ({ doc: r.querySelector('.spk-tile-doc').value, words: r.querySelector('.spk-tile-label').value }));
      }, before.map((r) => r.doc));
      t.expect(swap[1].doc, 'button 2 has button 3\'s document').toBe(before[2].doc);
      t.expect(swap[1].words, 'with its words').toBe(before[2].words);
      t.expect(swap[2].doc, 'button 3 has button 2\'s document').toBe(before[1].doc);
      t.expect(swap[2].words, 'with its words').toBe(before[1].words);
      t.expectNoConsoleErrors();
    },
  },
  {
    name: 'ares-net: Add a Document: the name check, the privacy tick and the file chooser, "n left", Place in section and https://',
    role: 'ares-net',
    async run(t) {
      await t.goto('/wp-admin/post-new.php?post_type=spk_document');
      await t.expectStatus(200);
      t.expect(await t.text('label.spk-title-label'), 'the name\'s label').toBe('Document name (required)');
      t.expect(await t.evaluate(() => document.querySelector('#title').required), 'the name is required for Publish').toBe(true);

      // A name that is already a document says so, with a link to it.
      await t.type('#title', 'ics-309 communications log', { clear: true });
      await t.waitForText('Already a document:', { selector: '#spk-name-match' });
      t.expect(await t.text('#spk-name-match'), 'the line under the name').toBe('Already a document: ICS 309 Communications Log (Soon). Open it');
      const docs = await t.evaluate(() => window.spokaresDocs.docs);
      const ics309 = docs.find((d) => d[1] === 'ICS 309 Communications Log');
      t.expect(await t.attr('#spk-name-match a', 'href'), 'Open it').toContain(`post=${ics309[0]}`);
      await t.type('#title', 'Net script (weekly)', { clear: true });
      await t.waitForText('Net scripts', { selector: '#spk-name-match' });
      t.expect(await t.text('#spk-name-match'), 'words that start the words of a name').toBe('Already a document: Net scripts: weekly, simplex and GMRS. Open it');
      // (WordPress autosaves a typed name as a draft, so the name is new each run.)
      await t.type('#title', `QA brand new form ${Date.now()}`, { clear: true });
      t.expect(await t.text('#spk-name-match'), 'no line for a new name').toBe('');

      // The chooser is locked until "I checked this file" is ticked.
      t.expect(await t.evaluate(() => document.querySelector('#spk-upload').disabled), 'chooser locked').toBe(true);
      await t.click('#spk-privacy');
      t.expect(await t.evaluate(() => document.querySelector('#spk-upload').disabled), 'chooser open once ticked').toBe(false);
      // A new source needs a new tick: switching away unticks and locks again.
      await t.click('input[name="spk_source"][value="link"]');
      const link = await t.evaluate(() => ({
        uploadBox: document.querySelector('.spk-upload').hidden,
        linkBox: document.querySelector('.spk-link').hidden,
        fileTick: document.querySelector('#spk-privacy').checked,
        fileTickSent: !document.querySelector('#spk-privacy').disabled,
        chooser: document.querySelector('#spk-upload').disabled,
      }));
      t.expect(link, 'Link shows its own box; the file tick is off and not sent').toEqual({ uploadBox: true, linkBox: false, fileTick: false, fileTickSent: false, chooser: true });

      // https:// is added; the site name follows; the tick unticks when the address changes.
      await t.type('#spk-url', 'www.arrl.org/ares', { clear: true });
      await t.click('#spk-label');
      t.expect(await t.evaluate(() => document.querySelector('#spk-url').value), 'https:// added').toBe('https://www.arrl.org/ares');
      t.expect(await t.evaluate(() => document.querySelector('#spk-label').placeholder), 'Site name shown placeholder').toBe('ARRL');
      await t.click('#spk-privacy-link');
      await t.type('#spk-url', '/kids-day');
      t.expect(await t.evaluate(() => document.querySelector('#spk-privacy-link').checked), 'a changed address needs a new tick').toBe(false);

      // "{n} left" only in the last ten characters.
      await t.type('#spk-note', 'x'.repeat(45), { clear: true });
      t.expect(await t.text('#spk-note-left'), 'nothing with 15 left').toBe('');
      await t.type('#spk-note', 'x'.repeat(7));
      t.expect(await t.text('#spk-note-left'), 'the count near the end').toBe('8 left');

      // Place in section follows the section and resets to "At the end".
      await t.click('input[name="spk_section"][value="forms"]');
      const place = await t.evaluate(() => {
        const s = document.querySelector('#spk-place');
        return { value: s.value, first: s.options[0].textContent, second: s.options[1].textContent, n: s.options.length };
      });
      t.expect(place.value, 'At the end by default').toBe('end');
      t.expect(place.first, 'At the top first').toBe('At the top');
      t.expect(place.second, 'then the section\'s documents').toMatch(/^After “.+”$/);
      t.expect(place.n, 'every Forms document').toBeGreaterThan(5);
      t.expectNoConsoleErrors();
    },
  },
];
