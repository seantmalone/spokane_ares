// DEV ONLY. Regression test for QA-049: PLAN §4.2 says the profile screen for
// non-admins shows only name, e-mail, password and Two-Factor options.
// plugins/spokares-core/assets/css/admin.css (the `.spk-editor.profile-php`
// rules) hides the rows it knows about, but WordPress 7.1.2 left two things:
//   - subscriber and contributor: every row of the "Personal Options" table
//     is hidden (toolbar, keyboard shortcuts), yet the "Personal Options"
//     heading still shows, above an empty table;
//   - author, core Editor, ARES Editor (with or without the Net details grant):
//     the "Infinite Scrolling: Disable infinite scrolling in the Media Library
//     grid view" row (tr.user-infinite-scrolling-wrap, shown to anyone with
//     upload_files) still shows under "Personal Options", although Media is
//     hidden from them.
//
// The correct behaviour: no visible heading sits over a table with no visible
// rows, and no non-admin sees the Infinite Scrolling row. The Name, Contact
// Info (e-mail), Account Management and Two-Factor sections stay. Administrators
// keep the full screen (control).

const NON_ADMINS = ['subscriber', 'contributor', 'author', 'core-editor', 'ares-editor', 'ares-net'];

// Reads #your-profile: each heading, whether it shows, and the visible rows of
// the table right after it.
function readProfile() {
  const shows = (el) => !!el && el.getClientRects().length > 0 && getComputedStyle(el).visibility !== 'hidden';
  const form = document.querySelector('#your-profile');
  if (!form) return null;
  const sections = [...form.querySelectorAll(':scope > h2')].map((h) => {
    const next = h.nextElementSibling;
    const isTable = !!next && next.tagName === 'TABLE';
    const rows = isTable ? [...next.querySelectorAll(':scope > tbody > tr, :scope > tr')] : [];
    return {
      heading: h.textContent.trim(),
      headingShows: shows(h),
      isTable,
      visibleRows: rows.filter(shows).map((tr) => ({
        cls: tr.className.trim(),
        text: tr.textContent.replace(/\s+/g, ' ').trim().slice(0, 120),
      })),
    };
  });
  const infinite = [...form.querySelectorAll('tr')]
    .filter((tr) => tr.classList.contains('user-infinite-scrolling-wrap') || /infinite scrolling/i.test(tr.textContent))
    .filter(shows)
    .map((tr) => tr.textContent.replace(/\s+/g, ' ').trim());
  return { bodyClass: document.body.className, sections, infinite };
}

const nonAdminTests = NON_ADMINS.map((role) => ({
  name: `${role}: profile shows no empty "Personal Options" heading and no Infinite Scrolling row`,
  role,
  async run(t) {
    await t.goto('/wp-admin/profile.php');
    await t.expectStatus(200);
    const p = await t.evaluate(readProfile);
    t.expect(p, 'the profile form #your-profile').toBeTruthy();
    // Control: this is the trimmed (non-admin) profile the admin.css rules target.
    t.expect(p.bodyClass, 'body class').toContain('spk-editor');
    const visibleHeadings = p.sections.filter((s) => s.headingShows).map((s) => s.heading);
    for (const keep of ['Name', 'Account Management', 'Two-Factor Options']) {
      t.expect(visibleHeadings, `visible headings (control: "${keep}" stays)`).toContain(keep);
    }

    // The bug, part 1: a visible heading over a table whose rows are all hidden.
    const empty = p.sections
      .filter((s) => s.headingShows && s.isTable && s.visibleRows.length === 0)
      .map((s) => s.heading);
    t.expect(empty, 'visible headings over a table with no visible rows').toEqual([]);

    // The bug, part 2: the Media Library Infinite Scrolling row (Media is hidden).
    t.expect(p.infinite, 'visible "Infinite Scrolling" rows').toEqual([]);

    // Whatever else happens, "Personal Options" has nothing a non-admin should see.
    const personal = p.sections.find((s) => s.heading === 'Personal Options');
    if (personal) {
      t.expect(personal.visibleRows.map((r) => r.text), 'visible "Personal Options" rows').toEqual([]);
      t.expect(personal.headingShows, '"Personal Options" heading shows').toBe(false);
    }
    t.expectNoConsoleErrors();
    t.expectNoPhpErrors();
  },
}));

export const tests = [
  ...nonAdminTests,
  {
    // Control: the trim is for non-admins only. Administrators keep Personal
    // Options, with the Infinite Scrolling row and the colour scheme picker.
    name: 'admin: profile keeps "Personal Options" and its Infinite Scrolling row',
    role: 'admin',
    async run(t) {
      await t.goto('/wp-admin/profile.php');
      await t.expectStatus(200);
      const p = await t.evaluate(readProfile);
      t.expect(p, 'the profile form #your-profile').toBeTruthy();
      t.expect(p.bodyClass, 'body class').not.toContain('spk-editor');
      const personal = p.sections.find((s) => s.heading === 'Personal Options');
      t.expect(personal && personal.headingShows, '"Personal Options" heading shows for an administrator').toBe(true);
      const cls = personal.visibleRows.map((r) => r.cls).join(' ');
      t.expect(cls, 'Personal Options rows for an administrator').toContain('user-infinite-scrolling-wrap');
      t.expect(cls, 'Personal Options rows for an administrator').toContain('user-admin-color-wrap');
      t.expectNoConsoleErrors();
      t.expectNoPhpErrors();
    },
  },
];
