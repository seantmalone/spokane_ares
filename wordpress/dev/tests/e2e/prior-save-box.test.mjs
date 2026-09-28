// DEV ONLY. Regression test for a Fixer-round bug that shipped without a test
// (build-notes/plugin.md "Fixer round" › Events; review shot
// shots/review-editor/19-event-save-box-hidden.png): an editor who hid or
// folded the "Save" box had no Save, Publish or Update button left on the
// event or document form. Here the editor's preferences are stored the way
// WordPress stores them (admin-ajax "closed-postboxes", with the nonce the
// form prints), then the form must still show an open Save box with its
// buttons, and no Screen Options tab. The filters themselves are tested in
// dev/tests/php/prior-save-box-test.php.

/** Store the editor's postbox preferences for a screen, as postbox.js does. */
function storePrefs(t, screen, closed, hidden) {
  return t.evaluate(async (page, c, h) => {
    const nonce = document.querySelector('#closedpostboxesnonce')?.value || '';
    const body = new URLSearchParams({ action: 'closed-postboxes', closedpostboxesnonce: nonce, page, closed: c, hidden: h });
    const r = await fetch(window.ajaxurl || '/wp-admin/admin-ajax.php', { method: 'POST', body, credentials: 'same-origin' });
    return { status: r.status, hasNonce: !!nonce };
  }, screen, closed, hidden);
}

function saveBoxTest(type) {
  return {
    name: `ares-editor: ${type} form keeps an open Save box after it was hidden and folded`,
    role: 'ares-editor',
    async run(t) {
      const form = `/wp-admin/post-new.php?post_type=${type}`;
      await t.goto(form);
      await t.expectStatus(200);
      await t.waitFor('#spokares_savebox');
      const stored = await storePrefs(t, type, 'spokares_savebox', 'spokares_savebox');
      t.expect(stored.hasNonce, 'the form prints the postbox nonce').toBe(true);
      t.expect(stored.status, 'closed-postboxes saved').toBe(200);

      try {
        await t.goto(form);
        await t.expectStatus(200);
        await t.expectVisible('#spokares_savebox');
        t.expect(await t.evaluate(() => document.querySelector('#spokares_savebox').className), 'Save box classes').not.toMatch(/\b(closed|hide-if-js)\b/);
        await t.expectVisible('#submitpost input[name="saveasdraft"]');
        await t.expectVisible('#submitpost input[name="publish"]');
        t.expect(await t.exists('#screen-options-link-wrap, #show-settings-link'), 'Screen Options tab').toBe(false);
        t.expect(await t.exists('#adv-settings input.hide-postbox-tog[value="spokares_savebox"]'), 'a "Save" toggle in Screen Options').toBe(false);
      } finally {
        // Put the editor's preferences back for later tests on this site.
        await storePrefs(t, type, '', '').catch(() => {});
      }
    },
  };
}

export const tests = [
  saveBoxTest('spk_event'),
  saveBoxTest('spk_document'),
];
