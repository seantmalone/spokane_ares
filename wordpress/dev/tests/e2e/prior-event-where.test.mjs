// DEV ONLY. Regression test for a Fixer-round bug that shipped without a test
// (build-notes/plugin.md "Fixer round" › Events): the event form had no place
// for where an event happens, and after publishing the editor was not told
// where on the site the event now shows. Through the real form, as the ARES
// Editor: pick a kind (the form says where that kind shows), fill in Where,
// Publish, read the "On the site now" notice, find the place on Exercises &
// events, then move the test event to the Trash so the site is as it was.
// The save logic itself is in dev/tests/php/prior-event-where-test.php.

const TITLE = 'QA e2e Where training';
const SLUG = 'qa-e2e-where-training';
const WHERE = 'Spokane Valley Fire Station 8';
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

export const tests = [
  {
    name: 'ares-editor: an event published with Where says where it shows, and the place prints on Exercises & events',
    role: 'ares-editor',
    timeout: 180000,
    async run(t) {
      await t.goto('/wp-admin/post-new.php?post_type=spk_event');
      await t.expectStatus(200);
      await t.waitFor('#spk-where');
      await t.click('input[name="spk_kind"][value="training"]');
      await t.expectVisible('.spk-places[data-kinds="training"]');
      await t.expectText('.spk-places[data-kinds="training"]', 'Where it shows: Exercises & events › Later this season');
      await t.expectHidden('.spk-places[data-kinds="public-service"]');
      await t.expectVisible('label[for="spk-where"]');
      await t.expectText('label[for="spk-where"]', 'Where (optional)');

      await t.type('#title', TITLE);
      // 75 days after the site's today: past the hub's 60 days, so it lands in Later this season.
      await t.evaluate(() => {
        const d = new Date(`${window.spokaresAdmin.today}T12:00:00Z`);
        d.setUTCDate(d.getUTCDate() + 75);
        const start = document.querySelector('#spk-start');
        start.value = d.toISOString().slice(0, 10);
        start.dispatchEvent(new Event('change', { bubbles: true }));
        return start.value;
      });
      await t.type('#spk-where', WHERE);
      // Leaving the title starts WordPress's first autosave of a new post
      // (post.js), which greys out Save draft and Publish until it is done and
      // ignores clicks on them meanwhile. Wait for it, as a person would.
      await sleep(1500);
      await t.waitForFunction(() => !document.querySelector('#submitpost .disabled'), { timeout: 60000 });
      await t.clickAndWait('#submitpost input[name="publish"]');

      const editUrl = await t.url();
      let trashed = false;
      try {
        t.expect(editUrl, 'back on the event form after Publish').toContain('post.php?post=');
        await t.waitForText('On the site now:', { selector: '#wpbody-content' });
        const notices = await t.texts('.notice.spk-notice');
        const info = notices.find((n) => n.startsWith('On the site now:')) || '';
        t.expect(info, `the "where it shows" notice (notices: ${JSON.stringify(notices)})`).toContain('Later this season on Exercises & events');
        t.expect(info).toContain('Events never show on Home.');
        t.expect(await t.attr('#spk-where', 'value'), 'Where kept on the form').toBe(WHERE);

        await t.goto('/members/exercises/');
        await t.expectStatus(200);
        t.expect(await t.text(`tr#${SLUG}`), 'the event\'s row in Later this season').toContain(`(${WHERE})`);

        await t.goto(editUrl);
        await t.clickAndWait('#spk-trash-link');
        trashed = true;
        await t.waitForText('moved to the Trash', { selector: '#wpbody-content' });
        t.expectNoConsoleErrors();
      } finally {
        if (!trashed && /post\.php\?post=/.test(editUrl)) {
          // Take the test event off the site even when an expectation failed.
          await t.goto(editUrl).then(() => t.clickAndWait('#spk-trash-link')).catch(() => {});
        }
      }
    },
  },
];
