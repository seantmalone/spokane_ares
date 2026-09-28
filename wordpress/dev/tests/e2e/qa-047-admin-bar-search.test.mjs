// DEV ONLY. Regression test for QA-047: the admin bar's Search box is never
// removed for non-administrators.
//
// spokares_admin_bar() (plugins/spokares-core/inc/admin-bar.php) trims the
// admin bar for anyone without manage_options (PLAN §4.2): it removes
// 'wp-logo', 'new-content', 'comments', 'customize', 'updates', 'search',
// 'site-editor' and 'edit-site'. It runs on admin_bar_menu at priority 999,
// but WordPress 7.1.2 adds the search node later, at 9999
// (WP_Admin_Bar::add_menus(), wp-includes/class-wp-admin-bar.php:652:
// wp_admin_bar_search_menu). remove_node( 'search' ) therefore finds nothing
// to remove, and every signed-in user sees the magnifier and its search form
// (form#adminbarsearch -> /?s=…) on every front-end page, while the logo and
// "+ New" (added at 10 and 70) are removed as intended.
//
// The PHP tests can't see this: wp_admin_bar_search_menu() returns early
// when is_admin(), and they run in admin-post.php. So this checks the real
// front end, at desktop width (core's CSS hides the box at 782px and below).
//
// Correct behaviour: for every non-administrator role, a front-end page's
// admin bar has no li#wp-admin-bar-search and no #adminbarsearch form.

const ROLES = ['subscriber', 'contributor', 'author', 'core-editor', 'ares-editor', 'ares-net'];
const PAGES = ['/members/', '/'];

export const tests = ROLES.map((role) => ({
  name: `${role}: the front-end admin bar has no Search box`,
  role,
  async run(t) {
    for (const path of PAGES) {
      await t.goto(path);
      await t.expectStatus(200);
      // Controls: the admin bar is drawn for this signed-in user, and the
      // plugin's trimming ran (the WordPress logo menu is gone), so the
      // search check below is meaningful.
      t.expect(await t.exists('#wpadminbar #wp-admin-bar-my-account'), `${path}: admin bar with the account menu (control)`).toBe(true);
      t.expect(await t.count('#wpadminbar #wp-admin-bar-wp-logo'), `${path}: WordPress logo menu (control, removed at priority 999)`).toBe(0);

      const search = await t.evaluate(() => {
        const li = document.querySelector('#wpadminbar li#wp-admin-bar-search');
        const form = document.querySelector('#wpadminbar form#adminbarsearch, #wpadminbar input[name="s"]');
        const box = li ? li.getBoundingClientRect() : null;
        return {
          li: !!li,
          form: !!form,
          shown: !!li && getComputedStyle(li).display !== 'none' && box.width > 0 && box.height > 0,
          action: form ? (form.closest('form')?.getAttribute('action') || '') : '',
        };
      });
      t.expect(search.li, `${path}: li#wp-admin-bar-search in the admin bar (shown: ${search.shown})`).toBe(false);
      t.expect(search.form, `${path}: admin bar search form (action "${search.action}")`).toBe(false);
    }
    t.expectNoPhpErrors();
  },
}));
