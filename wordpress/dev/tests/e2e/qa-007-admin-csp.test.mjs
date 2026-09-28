// DEV ONLY. Regression test for QA-007: PLAN §5.3 says every response,
// wp-admin included, carries the hardening Content-Security-Policy
//   frame-ancestors 'self'; base-uri 'self'; form-action 'self'; object-src 'none'
// The must-use plugin (mu-plugins/spokares-hardening/headers.php) sent it on
// wp-admin at init priority 0, and its "send once" flag then skipped its own
// admin_init hook. Core's send_frame_options_header() runs later on
// admin_init and header()-replaces the CSP with just "frame-ancestors 'self';",
// so signed-in wp-admin screens, admin-ajax.php and admin-post.php (signed
// in or out) lost base-uri, form-action and object-src. The front end, the
// sign-in page and REST kept the full policy.
//
// The headers are read with same-origin fetch() from inside the signed-in
// page (a same-origin response exposes Content-Security-Policy), and the
// policy on the wp-admin document itself is checked by what Chrome enforces:
// a <base> pointing at another origin must be refused by base-uri 'self'.

const DIRECTIVES = ["frame-ancestors 'self'", "base-uri 'self'", "form-action 'self'", "object-src 'none'"];

/** The CSP header (or null) of each path, fetched with this context's cookies. */
function cspOf(t, paths) {
  return t.evaluate(async (list) => {
    const out = {};
    for (const p of list) {
      try {
        const r = await fetch(p, { credentials: 'same-origin', redirect: 'follow', cache: 'no-store' });
        out[p] = { status: r.status, csp: r.headers.get('content-security-policy') };
      } catch (err) {
        out[p] = { status: 0, csp: null, error: String(err) };
      }
    }
    return out;
  }, paths);
}

/** Paths whose CSP lacks any §5.3 directive, as "path (status): header". */
function missingDirectives(found) {
  const bad = [];
  for (const [p, r] of Object.entries(found)) {
    const csp = (r.csp || '').toLowerCase();
    const lacks = DIRECTIVES.filter((d) => !csp.includes(d));
    if (lacks.length) bad.push(`${p} (${r.status}): "${r.csp}" lacks ${lacks.join(', ')}`);
  }
  return bad;
}

/** Does this document refuse a cross-origin <base>? (base-uri 'self' enforced) */
function baseUriEnforced(t) {
  return t.evaluate(() => {
    const before = document.baseURI;
    const base = document.createElement('base');
    base.href = 'https://example.invalid/';
    document.head.appendChild(base);
    const after = document.baseURI;
    base.remove();
    return { before, after, refused: after === before };
  });
}

function signedInTest(role, extra = []) {
  return {
    name: `${role}: wp-admin, admin-ajax and admin-post keep the full §5.3 Content-Security-Policy`,
    role,
    async run(t) {
      await t.goto('/wp-admin/profile.php');
      await t.expectStatus(200);

      // Control: the front end, the sign-in page and REST already carry the
      // full policy, so the fetch() reading works and the directives are right.
      const control = await cspOf(t, ['/', '/wp-json/']);
      t.expect(missingDirectives(control), 'front end and REST CSP (control)').toEqual([]);

      const found = await cspOf(t, [
        '/wp-admin/',
        '/wp-admin/profile.php',
        '/wp-admin/edit.php?post_type=page',
        '/wp-admin/admin-ajax.php?action=heartbeat',
        '/wp-admin/admin-post.php',
        ...extra,
      ]);
      t.expect(missingDirectives(found), `signed-in admin responses for ${role}`).toEqual([]);

      const base = await baseUriEnforced(t);
      t.expect(base.refused, `base-uri 'self' enforced on /wp-admin/profile.php (baseURI became ${base.after})`).toBe(true);
    },
  };
}

export const tests = [
  signedInTest('admin', ['/wp-admin/options-general.php', '/wp-admin/admin.php?page=spokares-rota']),
  signedInTest('ares-editor', ['/wp-admin/admin.php?page=spokares-rota']),
  signedInTest('core-editor'),
  {
    name: 'visitor: admin-ajax and admin-post keep the full §5.3 Content-Security-Policy when signed out',
    async run(t) {
      await t.goto('/');
      await t.expectStatus(200);
      const control = await cspOf(t, ['/', '/wp-login.php', '/wp-json/']);
      t.expect(missingDirectives(control), 'front end, sign-in and REST CSP (control)').toEqual([]);
      const found = await cspOf(t, [
        '/wp-admin/admin-ajax.php?action=heartbeat',
        '/wp-admin/admin-post.php',
        '/wp-admin/admin-post.php?action=spokares_save_rota',
      ]);
      t.expect(missingDirectives(found), 'signed-out admin-ajax / admin-post responses').toEqual([]);
    },
  },
];
