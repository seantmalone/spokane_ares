// DEV ONLY. Regression test for QA-032: PLAN §4.3 layer 5 says the site has
// no font library (the two self-hosted fonts come from theme.json, §8 row 11).
// WordPress 7.1.2 still offered it to administrators:
//   - Appearance › Fonts (wp-admin/menu.php adds font-library.php for anyone
//     with edit_theme_options, whatever the theme says) opened the Fonts
//     screen with Upload and Install tabs;
//   - Site Editor › Styles › Typography showed "Fonts" with "Manage fonts"
//     (the editor's fontLibraryEnabled setting defaults to true).
// Every upload or install from there failed: the must-use plugin's
// upload_mimes allowlist (jpg/png/webp, plus pdf for administrators) has no
// font types, so POST /wp/v2/font-families/<id>/font-faces answered 400
// rest_font_upload_invalid_file_type ("Sorry, you are not allowed to upload
// this file type.") after /wp/v2/font-families had already made the family.
//
// The correct behaviour is that the font library is not offered: no Fonts
// link, the Fonts screen doesn't open, and Styles › Typography has no font
// management. (Note for the fix: 7.1.2's theme.json schema has no
// settings.typography.fontLibrary key, so it is dropped; the editor setting
// is `fontLibraryEnabled`, from the block_editor_settings_all filter.)

export const tests = [
  {
    name: 'admin: Appearance has no Fonts link',
    role: 'admin',
    async run(t) {
      await t.goto('/wp-admin/');
      await t.expectStatus(200);
      // Control: the Appearance menu is there, so the check below is meaningful.
      t.expect(await t.exists('#menu-appearance a[href="themes.php"]'), 'Appearance › Themes link (control)').toBe(true);
      const fonts = await t.evaluate(() => [...document.querySelectorAll('#adminmenu a, #wpadminbar a')]
        .filter((a) => /font-library\.php/.test(a.getAttribute('href') || ''))
        .map((a) => `${a.textContent.trim()} -> ${a.getAttribute('href')}`));
      t.expect(fonts, 'admin menu and toolbar links to font-library.php').toEqual([]);
      t.expectNoPhpErrors();
    },
  },
  {
    name: 'admin: wp-admin/font-library.php does not open the Fonts screen',
    role: 'admin',
    async run(t) {
      const res = await t.goto('/wp-admin/font-library.php');
      const url = await t.url();
      const title = await t.evaluate(() => document.title);
      const opened = t.status === 200 && /\/wp-admin\/font-library\.php/.test(url);
      t.expect(opened, `Fonts screen opened (status ${t.status}, ${url}, "${title}", chain ${JSON.stringify(res?.chain || [])})`).toBe(false);
      t.expect([200, 403, 404], 'refused or redirected to a working screen').toContain(t.status);
      t.expectNoPhpErrors();
    },
  },
  {
    name: 'admin: Site Editor › Styles › Typography offers no font management',
    role: 'admin',
    timeout: 150000,
    async run(t) {
      await t.goto('/wp-admin/site-editor.php?p=%2Fstyles&section=%2Ftypography');
      await t.expectStatus(200);
      await t.waitForFunction(() => {
        const s = window.wp?.data?.select('core/editor')?.getEditorSettings?.();
        return !!s && Object.keys(s).length > 5;
      }, { timeout: 60000 });
      // The Typography screen has rendered once its "Font Sizes" group is there
      // (drawn with or without the font library).
      await t.waitForFunction(() => [...document.querySelectorAll('h2, h3')].some((h) => h.textContent.trim() === 'Font Sizes'), { timeout: 60000 });
      const found = await t.evaluate(() => ({
        enabled: window.wp.data.select('core/editor').getEditorSettings().fontLibraryEnabled,
        buttons: [...document.querySelectorAll('button')]
          .map((b) => (b.getAttribute('aria-label') || b.textContent || '').trim())
          .filter((x) => /^(Manage fonts|Add fonts|Upload fonts|Install fonts)$/i.test(x)),
        headings: [...document.querySelectorAll('h2, h3')].map((h) => h.textContent.trim()),
      }));
      t.expect(found.enabled === true || found.enabled === undefined, `editor fontLibraryEnabled is ${JSON.stringify(found.enabled)} (undefined means on)`).toBe(false);
      t.expect(found.buttons, 'font management buttons in Styles › Typography').toEqual([]);
      t.expect(found.headings, 'Styles › Typography headings').not.toContain('Fonts');
      t.expectNoPhpErrors();
    },
  },
];
