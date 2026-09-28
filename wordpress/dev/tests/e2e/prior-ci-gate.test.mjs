// DEV ONLY. Regression test for a Fixer-round bug that shipped without a test
// (build-notes/plugin.md "Fixer round" › Other, "Release gate"; PLAN §6.11,
// §8.3 #20): the release gate ops/ci.sh could report a false pass. When
// mktemp failed (a bad TMPDIR) it carried on with no work folder, so PHPCS
// wrote no report and the empty result read as "no violations". The fix:
// ci.sh stops (exit 1) when it cannot make its work folder, and passes PHPCS
// only on proof that PHPCS ran: its JSON report exists and parses.
//
// This test needs no browser or site: it runs ops/ci.sh --offline --no-zip
// with stand-ins for phpcs, php and npx at the front of PATH (so the PHPCS
// step's outcome is chosen by the test and nothing is downloaded). The other
// checks run for real on the working tree, and only the PHPCS line and the
// exit code are asserted.

import { spawn } from 'node:child_process';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const here = path.dirname(fileURLToPath(import.meta.url));
const CI = path.resolve(here, '..', '..', '..', 'ops', 'ci.sh');

const FAKE_PHPCS = `#!/bin/sh
# Stand-in phpcs for dev/tests/e2e/prior-ci-gate.test.mjs.
json=""
for a in "$@"; do
  case $a in
    -i) echo "The installed coding standards are WordPress, WordPress-Core, WordPress-Docs and WordPress-Extra"; exit 0 ;;
    --report-json=*) json=\${a#--report-json=} ;;
  esac
done
case "$SPK_FAKE_PHPCS" in
  crash) echo "PHP Fatal error:  Allowed memory size of 134217728 bytes exhausted"; exit 255 ;;
  empty) : > "$json"; exit 0 ;;
  garbage) echo "Time: 1.2 secs; Memory: 8MB" > "$json"; exit 0 ;;
  nototals) printf '{"files":{}}' > "$json"; exit 0 ;;
  clean) printf '{"totals":{"errors":0,"warnings":0,"fixable":0},"files":{}}' > "$json"; exit 0 ;;
  errors) printf '{"totals":{"errors":3,"warnings":1,"fixable":0},"files":{}}' > "$json"; echo "/src/wordpress/plugins/spokares-core/x.php"; exit 2 ;;
esac
exit 3
`;

/** A folder of stand-ins: phpcs (above), php (reports no usable version), npx (no output). */
function makeShims() {
  const dir = fs.mkdtempSync(path.join(os.tmpdir(), 'spokares-ci-shims-'));
  fs.writeFileSync(path.join(dir, 'phpcs'), FAKE_PHPCS, { mode: 0o755 });
  // "php -r …" answers a version ci.sh doesn't lint with, so lint goes to npx.
  fs.writeFileSync(path.join(dir, 'php'), '#!/bin/sh\necho 0.0\n', { mode: 0o755 });
  // Playground (npx) is never downloaded: the lint step sees no output.
  fs.writeFileSync(path.join(dir, 'npx'), '#!/bin/sh\nexit 0\n', { mode: 0o755 });
  return dir;
}

/** Run ci.sh --offline --no-zip; resolves { code, out, err }. */
function runCi({ shims, tmpdir, phpcs = 'clean', timeout = 200000 }) {
  return new Promise((resolve) => {
    const child = spawn('bash', [CI, '--offline', '--no-zip'], {
      env: { ...process.env, PATH: `${shims}:${process.env.PATH}`, TMPDIR: tmpdir, SPK_FAKE_PHPCS: phpcs, SPOKARES_CACHE: path.join(shims, 'cache') },
      stdio: ['ignore', 'pipe', 'pipe'],
    });
    let out = '';
    let err = '';
    child.stdout.on('data', (d) => { out += d; });
    child.stderr.on('data', (d) => { err += d; });
    const timer = setTimeout(() => child.kill('SIGKILL'), timeout);
    child.on('close', (code) => { clearTimeout(timer); resolve({ code, out, err }); });
  });
}

const phpcsLines = (out) => out.split('\n').filter((l) => /^(PASS|FAIL|SKIP)\s+PHPCS/.test(l));

export const tests = [
  {
    name: 'ci.sh stops with exit 1 and runs no check when it cannot make its work folder',
    timeout: 240000,
    async run(t) {
      t.expect(fs.existsSync(CI), `ops/ci.sh at ${CI}`).toBe(true);
      const shims = makeShims();
      try {
        const missing = path.join(shims, 'no-such-dir', 'deeper');
        const r = await runCi({ shims, tmpdir: missing, phpcs: 'clean' });
        t.expect(r.code, `exit code (stdout: ${r.out.slice(-300)})`).toBe(1);
        t.expect(r.err, 'the reason on stderr').toContain('cannot create a work folder');
        t.expect(r.out.split('\n').filter((l) => /^(PASS|FAIL)\s/.test(l)), 'checks that ran anyway').toEqual([]);
        t.expect(r.out, 'a summary line').not.toMatch(/ci\.sh: \d+ passed/);
      } finally {
        fs.rmSync(shims, { recursive: true, force: true });
      }
    },
  },
  {
    name: 'ci.sh fails PHPCS when PHPCS left no usable report (crash, empty, not JSON, no totals)',
    timeout: 240000,
    async run(t) {
      const shims = makeShims();
      try {
        const runs = await Promise.all(['crash', 'empty', 'garbage', 'nototals'].map(async (phpcs) => {
          const tmpdir = fs.mkdtempSync(path.join(shims, `tmp-${phpcs}-`));
          return { phpcs, ...(await runCi({ shims, tmpdir, phpcs })) };
        }));
        for (const r of runs) {
          const lines = phpcsLines(r.out);
          t.expect(lines.some((l) => l.startsWith('FAIL  PHPCS did not run (no report)')), `${r.phpcs}: PHPCS lines ${JSON.stringify(lines)} (stderr: ${r.err.slice(-200)})`).toBe(true);
          t.expect(lines.some((l) => l.startsWith('PASS')), `${r.phpcs}: a PHPCS PASS`).toBe(false);
          t.expect(r.code, `${r.phpcs}: exit code`).toBe(1);
        }
      } finally {
        fs.rmSync(shims, { recursive: true, force: true });
      }
    },
  },
  {
    name: 'ci.sh passes PHPCS only on a parsed report with no errors, and fails it with errors',
    timeout: 240000,
    async run(t) {
      const shims = makeShims();
      try {
        const [clean, errors] = await Promise.all(['clean', 'errors'].map(async (phpcs) => {
          const tmpdir = fs.mkdtempSync(path.join(shims, `tmp-${phpcs}-`));
          return runCi({ shims, tmpdir, phpcs });
        }));
        t.expect(phpcsLines(clean.out), `clean report (stderr: ${clean.err.slice(-200)})`).toEqual(['PASS  PHPCS WordPress-Extra: 0 errors and 0 warnings']);
        t.expect(phpcsLines(errors.out)[0] || '', 'report with errors').toBe('FAIL  PHPCS WordPress-Extra: 3 errors and 1 warnings');
        t.expect(errors.code, 'exit code with PHPCS errors').toBe(1);
      } finally {
        fs.rmSync(shims, { recursive: true, force: true });
      }
    },
  },
];
