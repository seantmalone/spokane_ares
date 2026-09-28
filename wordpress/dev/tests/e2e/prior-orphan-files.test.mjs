// DEV ONLY. Regression test for a Fixer-round bug that shipped without a test
// (build-notes/plugin.md "Fixer round" › Other and Documents; PLAN §5.5):
// uploaded files were reachable by their attachment number or name, which
// undoes the random file names, and a deleted document's file stayed public
// that way. The must-use plugin (content-surface.php) makes every attachment
// address a 404 that never redirects to the file. checks.sh covers
// /?attachment_id=N and /?p=N; this covers the other addresses, signed out.
// Deleting the file with its document is in
// dev/tests/php/prior-orphan-files-test.php.

/** Signed-out GET that does not follow redirects. */
async function probe(base, path) {
  const r = await fetch(base + path, { redirect: 'manual', signal: AbortSignal.timeout(30000) });
  return { path, status: r.status, location: r.headers.get('location') || '' };
}

export const tests = [
  {
    name: 'visitor: an upload is not reachable by its attachment number or name (404, no redirect)',
    role: 'admin', // only to look up the attachment's name; the probes are signed out
    timeout: 120000,
    async run(t) {
      // The Home hero is a Media Library image: its number is in wp-image-N.
      const home = await (await fetch(`${t.base}/`, { signal: AbortSignal.timeout(30000) })).text();
      const id = Number((home.match(/wp-image-(\d+)/) || [])[1] || 0);
      t.expect(id, 'the Home hero attachment number').toBeGreaterThan(0);
      const file = (home.match(/https?:\/\/[^"' ]+\/wp-content\/uploads\/[^"' ]+\.(?:jpe?g|png|webp)/) || [])[0] || '';
      t.expect(file, 'the hero file URL').toContain('/wp-content/uploads/');

      await t.goto('/wp-admin/');
      const media = await t.evaluate(async (n) => {
        const nonce = await (await fetch('/wp-admin/admin-ajax.php?action=rest-nonce', { credentials: 'same-origin' })).text();
        const r = await fetch(`/wp-json/wp/v2/media/${n}?context=edit&_wpnonce=${encodeURIComponent(nonce)}`, { credentials: 'same-origin' });
        return r.ok ? r.json() : { status: r.status };
      }, id);
      const slug = media.slug || '';
      t.expect(slug, `attachment ${id} name (REST: ${JSON.stringify(media).slice(0, 120)})`).toBeTruthy();

      const paths = [
        `/?page_id=${id}`,
        `/?attachment=${encodeURIComponent(slug)}`,
        `/${encodeURIComponent(slug)}/`,
        `/?p=${id}&attachment_id=${id}`,
        // A mistyped address WordPress would otherwise "guess" onto the attachment.
        `/${encodeURIComponent(slug.slice(0, Math.max(4, slug.length - 3)))}`,
      ];
      const results = [];
      for (const p of paths) results.push(await probe(t.base, p));
      const leaks = results.filter((r) => r.status !== 404 || r.location);
      t.expect(leaks, `attachment addresses that are not a plain 404 (all: ${JSON.stringify(results)})`).toEqual([]);
      // And never the file itself, even through a chain.
      const files = results.filter((r) => /\/wp-content\/uploads\//.test(r.location));
      t.expect(files, 'redirects to the upload').toEqual([]);
    },
  },
];
