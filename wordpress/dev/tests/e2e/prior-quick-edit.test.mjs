// DEV ONLY. Regression test for a Fixer-round bug that shipped without a test
// (build-notes/plugin.md "Fixer round" › Pages): Quick Edit and bulk Edit on
// Pages let a non-administrator rename a fixed page and reach its slug, date,
// author, password, status and template. The fix hides both from editors and
// refuses core's admin-ajax "inline-save" for pages, so a crafted request
// (the nonce is still printed on the list screen) is refused too. The PHP
// side is in dev/tests/php/prior-quick-edit-test.php; this is the real screen
// and the real admin-ajax.php, where core's own handler is hooked.

function pagesListTest(role) {
  return {
    name: `${role}: Pages has no Quick Edit or bulk Edit, and a crafted Quick Edit save is refused`,
    role,
    async run(t) {
      const about = t.ids.pages.about;
      await t.goto('/wp-admin/edit.php?post_type=page');
      await t.expectStatus(200);
      await t.waitFor(`#post-${about}`);
      const title = await t.text(`#post-${about} .row-title`);
      t.expect(await t.count('#the-list button.editinline, #the-list .row-actions .inline'), 'Quick Edit links in the rows').toBe(0);
      t.expect(await t.count('select[name="action"] option[value="edit"], select[name="action2"] option[value="edit"]'), 'bulk Edit in the Bulk actions menu').toBe(0);

      const res = await t.evaluate(async (id, title) => {
        const nonce = document.querySelector('#_inline_edit')?.value || '';
        const body = new URLSearchParams({
          action: 'inline-save', _inline_edit: nonce, post_ID: String(id), post_type: 'page',
          post_title: title, post_name: 'about', _status: 'publish', screen: 'edit-page', post_view: 'list',
        });
        const r = await fetch(window.ajaxurl || '/wp-admin/admin-ajax.php', { method: 'POST', body, credentials: 'same-origin' });
        return { status: r.status, text: (await r.text()).replace(/\s+/g, ' ').slice(0, 300), hasNonce: !!nonce };
      }, about, 'QA renamed by Quick Edit');
      t.expect(res.hasNonce, 'the list prints the Quick Edit nonce, so the crafted request is otherwise valid').toBe(true);
      t.expect(res.status, `admin-ajax inline-save answered "${res.text}"`).toBe(403);
      t.expect(res.text).toContain('Quick Edit is off');

      await t.goto('/wp-admin/edit.php?post_type=page');
      t.expect(await t.text(`#post-${about} .row-title`), 'About keeps its title').toBe(title);
    },
  };
}

export const tests = [
  pagesListTest('core-editor'),
  pagesListTest('ares-editor'),
  {
    name: 'admin: Pages keeps Quick Edit and bulk Edit',
    role: 'admin',
    async run(t) {
      await t.goto('/wp-admin/edit.php?post_type=page');
      await t.expectStatus(200);
      t.expect(await t.count(`#post-${t.ids.pages.about} button.editinline`), 'Quick Edit on About').toBe(1);
      t.expect(await t.count('select[name="action"] option[value="edit"]'), 'bulk Edit').toBe(1);
    },
  },
];

