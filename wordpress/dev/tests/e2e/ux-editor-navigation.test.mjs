// DEV ONLY. The volunteer editor's navigation and Dashboard (ux/SPEC.md §2,
// §3.1, §3.9), in the browser:
//   - the menu names each job the way Frank says it, in the Dashboard's
//     order, and keeps its labels at an iPad-landscape width (WordPress's
//     "auto-fold" would shrink it to bare icons between 783px and 960px);
//   - the Site tasks box can't be folded or moved: no handle buttons, and a
//     click on its heading leaves it open;
//   - the account menu has no "Howdy," and there is no command palette;
//   - the profile has no Nickname, Additional Capabilities or Primary Method
//     row, and names the display name for what it does.

const NARROW = { width: 900, height: 800, mobile: false };

export const tests = [
  {
    name: 'ares-net: the menu reads Dashboard, Net Control Schedule, Exercises & Events, Meetings, Documents, Page Text, Profile, and keeps its labels at 900px',
    role: 'ares-net',
    async run(t) {
      await t.setViewport(NARROW);
      await t.goto('/wp-admin/');
      await t.expectStatus(200);
      const menu = await t.evaluate(() => {
        const items = [...document.querySelectorAll('#adminmenu > li.menu-top')].map((li) => li.querySelector('.wp-menu-name')?.textContent.trim() || '');
        const name = document.querySelector('#adminmenu .wp-menu-name');
        return {
          items,
          autoFold: document.body.classList.contains('auto-fold'),
          labelShown: !!name && getComputedStyle(name).display !== 'none' && name.getBoundingClientRect().width > 40,
          width: Math.round(document.querySelector('#adminmenuwrap')?.getBoundingClientRect().width || 0),
        };
      });
      t.expect(menu.items, 'the menu').toEqual(['Dashboard', 'Net Control Schedule', 'Exercises & Events', 'Meetings', 'Documents', 'Page Text', 'Profile']);
      t.expect(menu.autoFold, 'body.auto-fold (the menu folds to icons below 960px)').toBe(false);
      t.expect(menu.labelShown, `menu labels shown at 900px (menu ${menu.width}px wide)`).toBe(true);

      // Meetings opens Cancel or Move a Meeting, with Meeting Schedule under it.
      await t.setViewport('desktop');
      await t.goto('/wp-admin/admin.php?page=spokares-meetings');
      await t.expectStatus(200);
      t.expect(await t.texts('#toplevel_page_spokares-meetings .wp-submenu a'), 'the Meetings submenu').toEqual(['Cancel or Move a Meeting', 'Meeting Schedule']);
      t.expect(await t.texts('#menu-posts-spk_event .wp-submenu a'), 'no meeting screens under Exercises & Events').not.toContain('Meeting Schedule');
      t.expectNoPhpErrors();
    },
  },
  {
    name: 'ares-editor: the Site tasks box has no fold or move buttons, and a click on its heading leaves it open',
    role: 'ares-editor',
    async run(t) {
      await t.goto('/wp-admin/');
      await t.expectStatus(200);
      await t.expectVisible('#spokares_site_tasks .spk-tasks');
      await t.expectHidden('#spokares_site_tasks .handle-actions');
      await t.click('#spokares_site_tasks .postbox-header h2').catch(() => {});
      await new Promise((r) => setTimeout(r, 300));
      t.expect(await t.evaluate(() => document.querySelector('#spokares_site_tasks').classList.contains('closed')), 'the box folded after a click on its heading').toBe(false);
      await t.expectVisible('#spokares_site_tasks .spk-tasks');
      t.expect(await t.count('.spk-task__n'), 'number badges').toBe(0);
      // The Documents buttons line up when they wrap (flex row, no left margin).
      const left = await t.evaluate(() => [...document.querySelectorAll('#spokares_site_tasks .spk-task__actions')].map((p) => {
        const cs = getComputedStyle(p);
        return { display: cs.display, margins: [...p.querySelectorAll('a')].map((a) => getComputedStyle(a).marginLeft) };
      }));
      for (const row of left) {
        t.expect(row.display, 'a button row').toBe('flex');
        t.expect(row.margins.filter((m) => m !== '0px'), 'links with a left margin in a button row').toEqual([]);
      }
      t.expectNoConsoleErrors();
      t.expectNoPhpErrors();
    },
  },
  {
    name: 'ares-net: no "Howdy," and no command palette, in wp-admin and on the site; the footer and the browser tab have no WordPress credit',
    role: 'ares-net',
    async run(t) {
      for (const path of ['/wp-admin/', '/members/']) {
        await t.goto(path);
        await t.expectStatus(200);
        const name = await t.text('#wp-admin-bar-my-account > .ab-item');
        t.expect(name, `${path}: the account menu`).toBe('QA Net Details');
        t.expect(await t.count('#wp-admin-bar-command-palette'), `${path}: the command palette item`).toBe(0);
      }
      await t.goto('/wp-admin/');
      t.expect((await t.text('#wpfooter')) || '', 'the footer').not.toContain('WordPress');
      t.expect(await t.evaluate(() => document.title), 'the browser tab').toBe('Dashboard ‹ Spokane County ARES-ACS');
      t.expect(await t.evaluate(() => typeof window.wp?.commands), 'the command palette script').not.toBe('object');
    },
  },
  {
    name: 'ares-net: the profile has no Nickname, Additional Capabilities or Primary Method row; "Name shown to other editors"',
    role: 'ares-net',
    async run(t) {
      await t.goto('/wp-admin/profile.php');
      await t.expectStatus(200);
      const p = await t.evaluate(() => {
        const shows = (el) => !!el && el.getClientRects().length > 0 && getComputedStyle(el).visibility !== 'hidden';
        return {
          nickname: shows(document.querySelector('tr.user-nickname-wrap')),
          nicknameInput: !!document.querySelector('#nickname'),
          display: document.querySelector('label[for="display_name"]')?.textContent.trim() || '',
          caps: [...document.querySelectorAll('#your-profile h2')].filter(shows).map((h) => h.textContent.trim()),
          primary: shows(document.querySelector('.two-factor-primary-method-table')),
        };
      });
      t.expect(p.nickname, 'the Nickname row shows').toBe(false);
      t.expect(p.nicknameInput, 'the nickname field is still in the form (control: it is kept)').toBe(true);
      t.expect(p.display, 'the display name label').toBe('Name shown to other editors');
      t.expect(p.caps, 'visible profile headings').not.toContain('Additional Capabilities');
      t.expect(p.primary, 'the Primary Method row shows').toBe(false);
      t.expectNoConsoleErrors();
      t.expectNoPhpErrors();
    },
  },
];
