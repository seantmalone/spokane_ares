// DEV ONLY. Browser tests for the event form and the Cancel or Move a
// Meeting screen after the volunteer-editor UX pass (ux/SPEC.md §3.4, §3.5).
// They cover what lives in assets/js/admin-events.js, which no PHP test can
// see: As requested only for Public service, the Cancelled tick only On a
// date, the live weekday, https:// added to a typed address, the Words on the
// button placeholder, the browser's required checks, and a meeting row's note
// cleared when its cancellation is taken back.
//
// Nothing is left changed: the event forms are never saved, and the one
// meeting change is saved and then taken back.

const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

/** Set a date box as a person picking a date would (input + change). */
function pickDate(t, sel, ymd) {
  return t.evaluate((s, v) => {
    const el = document.querySelector(s);
    el.value = v;
    el.dispatchEvent(new Event('input', { bubbles: true }));
    el.dispatchEvent(new Event('change', { bubbles: true }));
    return el.value;
  }, sel, ymd);
}

const shown = (t, sel) => t.evaluate((s) => {
  const el = document.querySelector(s);
  return !!el && !el.closest('[hidden]') && el.getClientRects().length > 0;
}, sel);

export const tests = [
  {
    name: 'ares-net: Add an Event: As requested only for Public service; Postponed hides the dates; a live weekday; https:// added; the button words follow the address',
    role: 'ares-net',
    async run(t) {
      await t.goto('/wp-admin/post-new.php?post_type=spk_event');
      await t.expectStatus(200);
      await t.waitFor('#spk-kind');
      await t.expectText('#spk-kind legend', 'Type of event (required)');
      await t.expectText('label.spk-title-label', 'Event name (required)');
      t.expect(await t.evaluate(() => document.querySelector('#title').required), 'the event name is required for Publish').toBe(true);
      t.expect(await t.exists('input[name="spk_cancelled"]'), 'a Cancelled tick on a new event').toBe(false);

      // As requested: Public service only; switching away picks Date not posted yet.
      t.expect(await shown(t, 'input[value="as-requested"]'), 'As requested before a type is picked').toBe(false);
      await t.click('input[name="spk_kind"][value="public-service"]');
      t.expect(await shown(t, 'input[value="as-requested"]'), 'As requested for Public service').toBe(true);
      t.expect(await shown(t, '#spk-summary'), 'Short description for Public service').toBe(false);
      await t.click('input[name="spk_date_mode"][value="as-requested"]');
      await t.click('input[name="spk_kind"][value="training"]');
      t.expect(await shown(t, 'input[value="as-requested"]'), 'As requested for Training or meeting').toBe(false);
      t.expect(await t.evaluate(() => document.querySelector('input[name="spk_date_mode"]:checked')?.value), 'the When choice after leaving Public service').toBe('not-posted');
      t.expect(await shown(t, '#spk-summary'), 'Short description for Training or meeting').toBe(true);

      // On a date shows the dates (First day required); Postponed hides them.
      await t.click('input[name="spk_date_mode"][value="date"]');
      t.expect(await shown(t, '#spk-start'), 'First day On a date').toBe(true);
      t.expect(await t.evaluate(() => document.querySelector('#spk-start').required), 'First day required On a date').toBe(true);
      await t.click('input[name="spk_date_mode"][value="postponed"]');
      t.expect(await shown(t, '#spk-start'), 'First day when Postponed').toBe(false);
      t.expect(await t.evaluate(() => document.querySelector('#spk-start').required), 'First day required when Postponed').toBe(false);
      await t.click('input[name="spk_date_mode"][value="date"]');

      // The weekday follows the date.
      await pickDate(t, '#spk-start', '2026-10-03');
      t.expect(await t.text('.spk-weekday[data-for="spk-start"]'), 'weekday after First day').toBe('Sat');
      await pickDate(t, '#spk-end', '2026-10-04');
      t.expect(await t.text('.spk-weekday[data-for="spk-end"]'), 'weekday after Last day').toBe('Sun');

      // All day hides the times; unticked shows them.
      t.expect(await shown(t, '#spk-t-start'), 'times while All day').toBe(false);
      await t.click('#spk-all-day');
      t.expect(await shown(t, '#spk-t-start'), 'times after unticking All day').toBe(true);

      // A web address typed without https:// gets it when the box is left.
      t.expect(await t.attr('#spk-main-label', 'placeholder'), 'Words on the button placeholder').toBe('Details');
      await t.type('#spk-main-url', 'www.arrl.org/kids-day');
      await t.click('#spk-main-label');
      t.expect(await t.evaluate(() => document.querySelector('#spk-main-url').value), 'https:// added').toBe('https://www.arrl.org/kids-day');
      await t.type('#spk-main-url', 'spokaneares-acs.groups.io/g/main/topic/1', { clear: true });
      t.expect(await t.attr('#spk-main-label', 'placeholder'), 'the groups.io placeholder').toBe('Exercise details on groups.io');
      await t.click('#spk-summary');
      t.expect(await t.evaluate(() => document.querySelector('#spk-main-url').value), 'https:// added').toBe('https://spokaneares-acs.groups.io/g/main/topic/1');

      // The browser stops Publish without a type (Save draft skips the check).
      const blocked = await t.evaluate(() => {
        document.querySelectorAll('input[name="spk_kind"]').forEach((r) => { r.checked = false; });
        document.querySelector('#title').value = 'UX e2e never saved';
        const form = document.querySelector('#post');
        const bad = form.querySelector('input:invalid, select:invalid, textarea:invalid');
        return { valid: form.checkValidity(), first: bad ? bad.name : '' };
      });
      t.expect(blocked.valid, 'the form is valid without a type').toBe(false);
      t.expect(blocked.first, 'the first field the browser stops on').toBe('spk_kind');
      t.expectNoConsoleErrors();
    },
  },
  {
    name: 'ares-editor: Edit Event: the Cancelled tick shows only On a date, with its one hint',
    role: 'ares-editor',
    async run(t) {
      await t.unlockPosts();
      const id = t.ids.events['set-2026'];
      t.expect(id, 'the SET 2026 event').toBeTruthy();
      await t.goto(`/wp-admin/post.php?post=${id}&action=edit`);
      await t.expectStatus(200);
      await t.waitFor('#spk-cancelled');
      t.expect(await shown(t, '#spk-cancelled'), 'Cancelled On a date').toBe(true);
      await t.expectText('#spk-cancelled-hint', 'Members see “Cancelled” until the date passes.');
      t.expect(await t.text('.spk-weekday[data-for="spk-start"]'), 'the weekday is drawn with the page').toBe('Sat');
      await t.click('input[name="spk_date_mode"][value="postponed"]');
      t.expect(await shown(t, '#spk-cancelled'), 'Cancelled when Postponed').toBe(false);
      await t.click('input[name="spk_date_mode"][value="date"]');
      t.expect(await shown(t, '#spk-cancelled'), 'Cancelled back On a date').toBe(true);
      t.expect(await t.exists('#spokares_checking'), 'the Webmaster check box for an editor').toBe(false);
      t.expect(await t.text('#spokares_savebox .spk-savebox-link a'), 'Make a copy in the Save box').toBe('Make a copy');
      t.expectNoConsoleErrors();
    },
  },
  {
    name: 'ares-editor: Cancel or Move a Meeting: unticking Cancelled clears the note that went with it, and the date is back on',
    role: 'ares-editor',
    timeout: 120000,
    async run(t) {
      await t.goto('/wp-admin/admin.php?page=spokares-meetings');
      await t.expectStatus(200);
      await t.expectText('h1', 'Cancel or Move a Meeting');
      const row = 'tr.spk-meeting-row:nth-of-type(2)';
      await t.waitFor(`${row} .spk-cancel`);
      const date = await t.evaluate((r) => document.querySelector(`${r} .spk-meeting-date`).textContent.trim(), row);

      // A note with no box ticked stays (a note on its own is a change of its own).
      await t.type(`${row} input.spk-note`, 'Room 2 this time');
      await t.click(`${row} .spk-cancel`);
      await t.click(`${row} .spk-cancel`);
      t.expect(await t.evaluate((r) => document.querySelector(`${r} input.spk-note`).value, row), 'a new note is not cleared').toBe('Room 2 this time');

      // Save it as a cancellation with a note.
      await t.click(`${row} .spk-cancel`);
      await t.clickAndWait('.spk-meetings form .button-primary');
      await t.waitForText(`Saved: ${date} cancelled.`, { selector: '#wpbody-content' });
      let restored = false;
      try {
        t.expect(await t.evaluate((r) => document.querySelector(`${r} .spk-cancel`).checked, row), 'cancelled after the save').toBe(true);
        // Unticking takes the cancellation back: its note goes with it.
        await t.click(`${row} .spk-cancel`);
        t.expect(await t.evaluate((r) => document.querySelector(`${r} input.spk-note`).value, row), 'the note is cleared').toBe('');
        await t.clickAndWait('.spk-meetings form .button-primary');
        await t.waitForText(`Saved: ${date} is back on.`, { selector: '#wpbody-content' });
        restored = true;
        const notices = await t.evaluate(() => [...document.querySelectorAll('#wpbody-content .notice')].filter((n) => n.getClientRects().length > 0).map((n) => n.textContent.trim()));
        t.expect(notices.length, `one notice (${JSON.stringify(notices)})`).toBe(1);
        t.expect(await t.text('.notice.spk-notice a'), 'the link').toBe('See it on the Home page');
        t.expectNoConsoleErrors();
      } finally {
        if (!restored) {
          // Leave the site as it was even when an expectation failed.
          await t.goto('/wp-admin/admin.php?page=spokares-meetings').catch(() => {});
          await t.evaluate((r) => {
            const c = document.querySelector(`${r} .spk-cancel`);
            c.checked = false;
            document.querySelector(`${r} input.spk-note`).value = '';
          }, row).catch(() => {});
          await t.clickAndWait('.spk-meetings form .button-primary').catch(() => {});
          await sleep(200);
        }
      }
    },
  },
];
