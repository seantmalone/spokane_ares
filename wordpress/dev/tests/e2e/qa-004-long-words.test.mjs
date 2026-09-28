// DEV ONLY. Regression test for QA-004: a long unbroken word (a pasted link)
// in an event's Short line or Where, or in a document's Short note, broke the
// Exercises & events and Documents layouts. The forms allow it (Short line
// maxlength 90, Where 80, note 60), and EDITING-GUIDE.md promises "You can't
// break the layout". Before the fix (site.css had no overflow-wrap on these
// cells and cards): the "Later this season" table grew past its ~590px
// column and was drawn over "Winlink assignments"; the Next up card's text
// ran out of the card at 1440 and on a phone (hidden by .wp-site-blocks'
// overflow-x: clip, so the page's scrollWidth looked fine); on a phone the
// document's row ran ~400px wide in the 358px library column.
//
// Through the real forms, as the ARES Editor (the account that writes these
// fields): publish the event (or save the note), then at 1440 and at 390 wide
// check that nothing in the event's row, the event's card or the document's
// row reaches past its column/card, and that the page does not scroll
// sideways. Each test puts the site back afterwards (events to the Trash, the
// note emptied), so later tests on the same site see the seeded content.

const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

// One pasted link each, as long as the form allows (Short line 90, Where 80,
// note 60). Chrome breaks a line after "-" or "?" but never after "/", ".",
// "_", "+", "=" or "&", so a link with no hyphen or "?" in it (most document
// and map links) is one unbreakable word. No phone-like digit runs and no @,
// so the phone/e-mail check doesn't hold the event back as a draft.
const SHORT = 'https://www.fema.gov/sites/default/files/documents/fema_nims_ics_forms_booklet_2026_v3.pdf';
const WHERE = 'https://www.google.com/maps/place/Spokane+Valley+Fire+Station+8+Training+Room';
const NOTE = 'https://training.fema.gov/emiweb/is/icsresource/icsforms/log';

/**
 * In the page: how far anything inside `itemSel` (or the box itself) reaches
 * past the right edge of `boxSel`'s inner (border-less) box, counting each
 * element's box and each visible text line. Visually hidden text (.vh) is
 * skipped: it is clipped to 1px on purpose.
 */
function reach(boxSel, itemSel) {
  const box = document.querySelector(boxSel);
  if (!box) return { found: false, why: `${boxSel} not found` };
  const items = itemSel ? [...box.querySelectorAll(itemSel)] : [box];
  if (!items.length) return { found: false, why: `${itemSel} not found in ${boxSel}` };
  const cr = box.getBoundingClientRect();
  const edge = cr.right - parseFloat(getComputedStyle(box).borderRightWidth || '0');
  const name = (el) => el.tagName.toLowerCase() + (el.id ? `#${el.id}` : '') + (typeof el.className === 'string' && el.className.trim() ? `.${el.className.trim().split(/\s+/).join('.')}` : '');
  const hidden = (el) => !!el.closest('.vh, .screen-reader-text, [hidden]');
  let worst = { over: 0, what: '' };
  const note = (right, what) => {
    const over = Math.round(right - edge);
    if (over > worst.over) worst = { over, what };
  };
  for (const item of items) {
    for (const el of [item, ...item.querySelectorAll('*')]) {
      if (el === box || hidden(el)) continue;
      const r = el.getBoundingClientRect();
      if (r.width === 0 && r.height === 0) continue;
      note(r.right, `${name(el)} (${Math.round(r.width)}px wide)`);
    }
    const walker = document.createTreeWalker(item, NodeFilter.SHOW_TEXT);
    for (let n = walker.nextNode(); n; n = walker.nextNode()) {
      if (!n.nodeValue.trim() || hidden(n.parentElement)) continue;
      const range = document.createRange();
      range.selectNodeContents(n);
      for (const rr of range.getClientRects()) note(rr.right, `text "${n.nodeValue.trim().slice(0, 40)}" in ${name(n.parentElement)}`);
    }
  }
  return {
    found: true,
    boxWidth: Math.round(cr.width),
    over: worst.over,
    what: worst.what,
    pageScroll: document.documentElement.scrollWidth,
    vw: window.innerWidth,
  };
}

/**
 * Measure one container at the current width; returns a sentence per problem
 * (something reaches past its right edge, or the page scrolls sideways).
 */
async function outside(t, where, boxSel, itemSel) {
  const m = await t.evaluate(reach, boxSel, itemSel || '');
  t.expect(m.found, `${where}: ${m.why || ''}`).toBe(true);
  const out = [];
  if (m.over > 1) out.push(`${where}: ${m.over}px past the right edge of ${boxSel} (${m.boxWidth}px wide), worst ${m.what}`);
  if (m.pageScroll > m.vw) out.push(`${where}: the page scrolls sideways (scrollWidth ${m.pageScroll} at ${m.vw})`);
  return out;
}

/** Fail with every problem found, not just the first. */
function expectNone(t, problems) {
  t.expect(problems.length, `layout broken: ${problems.join(' | ')}`).toBe(0);
}

/** Publish an event through the real form; returns its edit URL. */
async function publishEvent(t, { kind, title, days }) {
  await t.goto('/wp-admin/post-new.php?post_type=spk_event');
  await t.expectStatus(200);
  await t.waitFor('#spk-where');
  await t.click(`input[name="spk_kind"][value="${kind}"]`);
  await t.type('#title', title);
  await t.evaluate((add) => {
    const d = new Date(`${window.spokaresAdmin.today}T12:00:00Z`);
    d.setUTCDate(d.getUTCDate() + add);
    const start = document.querySelector('#spk-start');
    start.value = d.toISOString().slice(0, 10);
    start.dispatchEvent(new Event('change', { bubbles: true }));
  }, days);
  // Pasted, as an editor would (Input.insertText, so maxlength applies).
  await t.type('#spk-summary', SHORT);
  await t.type('#spk-where', WHERE);
  t.expect(await t.attr('#spk-summary', 'maxlength'), 'the Short description\'s maxlength').toBe(String(SHORT.length));
  t.expect(await t.evaluate(() => document.querySelector('#spk-summary').value), 'the Short description as pasted').toBe(SHORT);
  t.expect(await t.evaluate(() => document.querySelector('#spk-where').value), 'Where as pasted').toBe(WHERE);
  // Wait for WordPress's first autosave of a new post (it greys out Publish).
  await sleep(1500);
  await t.waitForFunction(() => !document.querySelector('#submitpost .disabled'), { timeout: 60000 });
  await t.clickAndWait('#submitpost input[name="publish"]');
  const editUrl = await t.url();
  t.expect(editUrl, 'back on the event form after Publish').toContain('post.php?post=');
  await t.waitForText('It shows under', { selector: '#wpbody-content' });
  return editUrl;
}

async function trashEvent(t, editUrl) {
  if (!/post\.php\?post=/.test(editUrl || '')) return;
  await t.setViewport('desktop');
  await t.goto(editUrl).then(() => t.clickAndWait('#spk-trash-link')).catch(() => {});
}

const slugOf = (title) => title.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');

export const tests = [
  {
    name: 'ares-editor: a pasted link as a Training event\'s Short line and Where stays inside Later this season (1440 and 390)',
    role: 'ares-editor',
    timeout: 180000,
    async run(t) {
      const title = 'QA e2e long word training';
      const row = `tr#${slugOf(title)}`;
      let editUrl = '';
      try {
        // 75 days out: a Training event lands in "Later this season".
        editUrl = await publishEvent(t, { kind: 'training', title, days: 75 });

        await t.setViewport('desktop');
        await t.goto('/members/exercises/');
        await t.expectStatus(200);
        t.expect(await t.text(row), 'the event\'s row in Later this season').toContain(SHORT);
        const problems = [
          ...await outside(t, '1440 wide, the Later this season table', '.wp-block-spokares-events.is-view-later', 'table.ex-later'),
          ...await outside(t, '1440 wide, the event\'s row', '.wp-block-spokares-events.is-view-later', row),
        ];

        await t.setViewport('phone');
        await t.goto('/members/exercises/');
        await t.expectStatus(200);
        problems.push(
          ...await outside(t, '390 wide, the Later this season table', '.wp-block-spokares-events.is-view-later', 'table.ex-later'),
          ...await outside(t, '390 wide, the event\'s row', '.wp-block-spokares-events.is-view-later', row),
        );
        expectNone(t, problems);
        t.expectNoConsoleErrors();
      } finally {
        await trashEvent(t, editUrl);
      }
    },
  },
  {
    name: 'ares-editor: a pasted link as an Exercise\'s Short line and Where stays inside its Next up card (1440 and 390)',
    role: 'ares-editor',
    timeout: 180000,
    async run(t) {
      const title = 'QA e2e long word exercise';
      const card = `article#${slugOf(title)}`;
      let editUrl = '';
      try {
        // Two days out: before the SET, so it is one of the two Next up cards.
        editUrl = await publishEvent(t, { kind: 'exercise', title, days: 2 });

        await t.setViewport('desktop');
        await t.goto('/members/exercises/');
        await t.expectStatus(200);
        t.expect(await t.exists(`.ex-cards > ${card}`), 'the exercise is a Next up card').toBe(true);
        const problems = [
          ...await outside(t, '1440 wide, the Next up card', card, ''),
          ...await outside(t, '1440 wide, the Next up cards', '.wp-block-spokares-events.is-view-next-up', '.ex-cards'),
        ];

        await t.setViewport('phone');
        await t.goto('/members/exercises/');
        await t.expectStatus(200);
        problems.push(
          ...await outside(t, '390 wide, the Next up card', card, ''),
          ...await outside(t, '390 wide, the Next up cards', '.wp-block-spokares-events.is-view-next-up', '.ex-cards'),
        );
        expectNone(t, problems);
        t.expectNoConsoleErrors();
      } finally {
        await trashEvent(t, editUrl);
      }
    },
  },
  {
    name: 'ares-editor: a pasted link as a document\'s Short note stays inside the Documents library (1440 and 390)',
    role: 'ares-editor',
    timeout: 180000,
    async run(t) {
      const id = t.ids.documents?.['ics-214'];
      t.expect(id, 'ICS 214 document id').toBeTruthy();
      const edit = `/wp-admin/post.php?post=${id}&action=edit`;
      const row = 'tr#ics-214';
      await t.goto(edit);
      await t.expectStatus(200);
      await t.waitFor('#spk-note');
      t.expect(await t.attr('#spk-note', 'maxlength'), 'the note\'s maxlength').toBe(String(NOTE.length));
      const before = await t.attr('#spk-note', 'value');
      let changed = false;
      try {
        await t.type('#spk-note', NOTE, { clear: true });
        await t.clickAndWait('#submitpost input[name="save"]');
        changed = true;
        t.expect(await t.attr('#spk-note', 'value'), 'the note was saved').toBe(NOTE);

        await t.setViewport('desktop');
        await t.goto('/members/documents/');
        await t.expectStatus(200);
        t.expect(await t.text(`${row} .doc-note`), 'the note shows in the row').toBe(NOTE);
        const problems = [
          ...await outside(t, '1440 wide, the library table', '.lib__main', '.doc-table'),
          ...await outside(t, '1440 wide, the document\'s row', '.lib__main', row),
        ];

        await t.setViewport('phone');
        await t.goto('/members/documents/');
        await t.expectStatus(200);
        problems.push(
          ...await outside(t, '390 wide, the library table', '.lib__main', '.doc-table'),
          ...await outside(t, '390 wide, the document\'s row', '.lib__main', row),
        );
        expectNone(t, problems);
        t.expectNoConsoleErrors();
      } finally {
        if (changed) {
          await t.setViewport('desktop');
          await t.goto(edit)
            .then(() => t.type('#spk-note', before || '', { clear: true }))
            .then(() => t.clickAndWait('#submitpost input[name="save"]'))
            .catch(() => {});
        }
      }
    },
  },
];
