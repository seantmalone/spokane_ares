// DEV ONLY. Regression test for workflow review G5: the weekly check's
// two-factor audit (ops/weekly-check.sh) looked only at users who can
// edit_posts, while the enforcement (mu-plugins spokares-hardening
// two-factor.php) also covers the rota and Net details capabilities. The
// audit now asks the must-use plugin's spokares_hard_2fa_user_can_change_site()
// (with the same rule spelled out as a fallback). ops/ is not mounted in the
// dev site, so this test reads the files; the PHP test
// wf-g2-2fa-reminder-test.php runs the function itself.
//
// No browser or site needed.

import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const here = path.dirname(fileURLToPath(import.meta.url));
const WP = path.resolve(here, '..', '..', '..');

function auditPhp() {
  const text = fs.readFileSync(path.join(WP, 'ops', 'weekly-check.sh'), 'utf8');
  const m = text.match(/no2fa=\$\(wp eval '([\s\S]*?)' 2>&1\)/);
  if (!m) throw new Error('the two-factor audit is not in ops/weekly-check.sh');
  return m[1];
}

export const tests = [
  {
    name: 'the weekly two-factor audit asks the enforcement which accounts count, with the same three capabilities as its fallback',
    async run(t) {
      const php = auditPhp();
      t.expect(php, 'asks the must-use plugin').toContain('spokares_hard_2fa_user_can_change_site');
      for (const cap of ['edit_posts', 'spokares_edit_rota', 'spokares_edit_net_details']) {
        t.expect(php, `fallback has ${cap}`).toContain(`user_can( $id, "${cap}" )`);
      }
      t.expect(php, 'no longer limited to edit_posts').not.toContain('"capability" => "edit_posts"');
      t.expect(php.includes("'"), 'no single quote inside the shell-quoted PHP').toBe(false);

      const mu = fs.readFileSync(path.join(WP, 'mu-plugins', 'spokares-hardening', 'two-factor.php'), 'utf8');
      const fn = mu.match(/function spokares_hard_2fa_user_can_change_site\([^)]*\)[^{]*\{([\s\S]*?)\n\}/);
      t.expect(Boolean(fn), 'the must-use plugin defines it').toBe(true);
      for (const cap of ['edit_posts', 'spokares_edit_rota', 'spokares_edit_net_details']) {
        t.expect(fn[1], `the enforcement has ${cap}`).toContain(`'${cap}'`);
      }
      t.expect(mu, 'the enforcement uses it').toMatch(/function spokares_hard_2fa_can_change_site\(\): bool \{\s*return spokares_hard_2fa_user_can_change_site\( get_current_user_id\(\) \);/);
    },
  },
];
