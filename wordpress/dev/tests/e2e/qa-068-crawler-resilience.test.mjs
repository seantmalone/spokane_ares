// DEV ONLY. Regression tests for QA-068: the QA crawler (dev/qa/crawl.mjs) and
// its Chrome driver (dev/lib/cdp.mjs) gave up or lied when Chrome misbehaved.
//
//   1. Browser.connect() waited a fixed 10 s for the DevTools WebSocket. Under
//      load (load average ~30) Chrome answers later than that, so every launch
//      attempt failed with "timeout after 10000 ms: DevTools WebSocket" and the
//      role was never crawled.
//   2. A role whose Chrome never started was recorded as one "(role)" error
//      record and the crawl still exited 0 ("1 URLs visited ... 1 errors").
//      The crawler's own contract is "exit 1 if it could not run".
//   3. When Chrome died mid-run, Page.goto() on the closed browser returned
//      { status: 0 } instead of throwing, so visit() never restarted Chrome and
//      every later URL was recorded as HTTP 0 "browser is closed": false errors.
//      dev/tests/README.md: Chrome "is restarted, and signed in again".
//
// These tests need the dev site but no browser of their own: each runs the real
// crawler as a child process with CHROME pointing at a stand-in (written to a
// temp folder) that starts the installed Chrome behind a small TCP proxy on
// its DevTools port. The stand-in can hold the DevTools handshake back for a
// while (a slow Chrome), exit at once (a Chrome that cannot start), or let the
// test SIGKILL the real Chrome in the middle of a crawl (a Chrome that dies).

import { spawn } from 'node:child_process';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { CHROME } from '../../lib/cdp.mjs';

const here = path.dirname(fileURLToPath(import.meta.url));
const CRAWL = path.resolve(here, '..', '..', 'qa', 'crawl.mjs');

/*
 * The stand-in Chrome. SPK_FAKE_CHROME_MODE=exit exits 1 at once; otherwise it
 * starts SPK_REAL_CHROME with the same arguments (its profile moved into
 * <profile>/real-profile, so the crawler's cleanup still removes it), puts a
 * TCP proxy in front of its DevTools port, and writes the proxy's port to the
 * crawler's DevToolsActivePort. The proxy forwards nothing until
 * SPK_FAKE_CHROME_DELAY_MS after that file appears (a Chrome too busy to
 * answer). It records { wrapper, chrome } pids in SPK_FAKE_CHROME_DIR and
 * exits when the real Chrome exits, as Chrome itself would.
 */
const FAKE_CHROME = `// Stand-in Chrome for dev/tests/e2e/qa-068-crawler-resilience.test.mjs.
import { spawn } from 'node:child_process';
import fs from 'node:fs';
import net from 'node:net';
import path from 'node:path';

const mode = process.env.SPK_FAKE_CHROME_MODE || 'proxy';
const dir = process.env.SPK_FAKE_CHROME_DIR;
const delay = Number(process.env.SPK_FAKE_CHROME_DELAY_MS || 0);
const args = process.argv.slice(2);
if (mode === 'exit') process.exit(1);
const udArg = args.find((a) => a.startsWith('--user-data-dir='));
const profile = udArg.slice('--user-data-dir='.length);
const realProfile = path.join(profile, 'real-profile');
fs.mkdirSync(realProfile, { recursive: true });
const child = spawn(process.env.SPK_REAL_CHROME, args.map((a) => (a === udArg ? '--user-data-dir=' + realProfile : a)), { stdio: 'ignore' });
fs.writeFileSync(path.join(dir, 'chrome-' + Date.now() + '-' + process.pid + '.json'), JSON.stringify({ wrapper: process.pid, chrome: child.pid }));
child.on('exit', (code) => process.exit(code ?? 1));
for (const s of ['SIGTERM', 'SIGINT', 'SIGHUP']) process.on(s, () => { try { child.kill('SIGKILL'); } catch {} process.exit(1); });
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
const realPortFile = path.join(realProfile, 'DevToolsActivePort');
while (!fs.existsSync(realPortFile)) await sleep(50);
await sleep(100);
const [realPort, wsPath] = fs.readFileSync(realPortFile, 'utf8').trim().split('\\n');
let readyAt = 0;
const server = net.createServer((client) => {
  client.pause();
  client.on('error', () => {});
  setTimeout(() => {
    const up = net.connect(Number(realPort), '127.0.0.1', () => { client.pipe(up); up.pipe(client); client.resume(); });
    up.on('error', () => client.destroy());
    up.on('close', () => client.destroy());
    client.on('close', () => up.destroy());
  }, Math.max(0, readyAt - Date.now()));
});
server.listen(0, '127.0.0.1', () => {
  readyAt = Date.now() + delay;
  const tmp = path.join(profile, 'DevToolsActivePort.tmp');
  fs.writeFileSync(tmp, server.address().port + '\\n' + wsPath + '\\n');
  fs.renameSync(tmp, path.join(profile, 'DevToolsActivePort'));
});
`;

/** A temp folder with the stand-in Chrome (an executable shell script) and a pids/ folder. */
function makeFakeChrome() {
  const dir = fs.mkdtempSync(path.join(os.tmpdir(), 'spokares-qa068-'));
  fs.writeFileSync(path.join(dir, 'fake-chrome.mjs'), FAKE_CHROME);
  const sh = path.join(dir, 'fake-chrome.sh');
  fs.writeFileSync(sh, `#!/bin/sh\nexec "${process.execPath}" "${path.join(dir, 'fake-chrome.mjs')}" "$@"\n`, { mode: 0o755 });
  fs.mkdirSync(path.join(dir, 'pids'));
  return { dir, sh, pids: path.join(dir, 'pids') };
}

/** The { wrapper, chrome } pids the stand-in recorded, oldest first. */
function launched(fake) {
  return fs.readdirSync(fake.pids).sort().map((f) => JSON.parse(fs.readFileSync(path.join(fake.pids, f), 'utf8')));
}

/** Kill whatever the stand-ins started that is still running, and remove the folder. */
function cleanUp(fake) {
  for (const p of launched(fake)) {
    try { process.kill(-p.wrapper, 'SIGKILL'); } catch {}
    try { process.kill(p.chrome, 'SIGKILL'); } catch {}
  }
  fs.rmSync(fake.dir, { recursive: true, force: true });
}

/**
 * Run the crawler through the stand-in Chrome. onLine(line, child) sees each
 * stdout line. Resolves { code, out, err, report } (report: report.json or null).
 */
function runCrawl({ port, fake, args, env = {}, onLine = () => {}, timeout = 280000 }) {
  const out = path.join(fake.dir, 'crawl');
  return new Promise((resolve) => {
    const child = spawn(process.execPath, [CRAWL, String(port), out, `--shots=${path.join(out, 'shots')}`, '--no-mobile', ...args], {
      env: { ...process.env, CHROME: fake.sh, SPK_REAL_CHROME: CHROME, SPK_FAKE_CHROME_DIR: fake.pids, ...env },
      stdio: ['ignore', 'pipe', 'pipe'],
    });
    let stdout = '';
    let stderr = '';
    let partial = '';
    child.stdout.on('data', (d) => {
      stdout += d;
      partial += d;
      const lines = partial.split('\n');
      partial = lines.pop();
      for (const l of lines) { try { onLine(l, child); } catch {} }
    });
    child.stderr.on('data', (d) => { stderr += d; });
    const timer = setTimeout(() => child.kill('SIGKILL'), timeout);
    child.on('close', (code) => {
      clearTimeout(timer);
      let report = null;
      try { report = JSON.parse(fs.readFileSync(path.join(out, 'report.json'), 'utf8')); } catch {}
      resolve({ code, out: stdout, err: stderr, report });
    });
  });
}

const errorsOf = (rec) => rec.findings.filter((f) => f.severity === 'error').map((f) => f.message);
const brief = (recs) => JSON.stringify((recs || []).map((r) => ({ url: r.url, status: r.status, access: r.access, errors: errorsOf(r) })));

export const tests = [
  {
    name: 'the crawler still crawls a role when Chrome takes 15 s to answer on its DevTools port (machine under load)',
    // The crawler reports PHP messages itself; this test is about the tooling.
    allowPhpNotices: true,
    timeout: 300000,
    async run(t) {
      const fake = makeFakeChrome();
      try {
        const r = await runCrawl({
          port: t.port,
          fake,
          args: ['--roles=anonymous', '--match=qa-no-such-page'],
          env: { SPK_FAKE_CHROME_DELAY_MS: '15000' },
        });
        const recs = r.report?.anonymous || [];
        t.expect(r.err, 'crawler stderr').not.toMatch(/timeout after 10000 ms: DevTools WebSocket/);
        t.expect(recs.some((x) => x.url === '(role)'), `a "role stopped" record (records: ${brief(recs)}; stderr: ${r.err.slice(-300)})`).toBe(false);
        const rec = recs.find((x) => x.url === '/qa-no-such-page/');
        t.expect(!!rec, `a record for /qa-no-such-page/ (records: ${brief(recs)})`).toBe(true);
        t.expect(rec.status, 'HTTP status of /qa-no-such-page/').toBe(404);
        t.expect(r.code, 'crawler exit code').toBe(0);
      } finally {
        cleanUp(fake);
      }
    },
  },
  {
    name: 'the crawl exits 1 when a role cannot start Chrome at all',
    allowPhpNotices: true,
    timeout: 300000,
    async run(t) {
      const fake = makeFakeChrome();
      try {
        const r = await runCrawl({
          port: t.port,
          fake,
          args: ['--roles=contributor', '--match=qa-no-such-page'],
          env: { SPK_FAKE_CHROME_MODE: 'exit' },
        });
        t.expect(r.err, 'crawler stderr names the failed start').toMatch(/contributor/);
        t.expect(r.code, `crawler exit code (stdout: ${r.out.slice(-300)}; stderr: ${r.err.slice(-300)})`).toBe(1);
      } finally {
        cleanUp(fake);
      }
    },
  },
  {
    name: 'when Chrome dies mid-crawl it is restarted and signed in again, and later URLs are not recorded as HTTP 0 errors',
    allowPhpNotices: true,
    timeout: 300000,
    async run(t) {
      const fake = makeFakeChrome();
      let killedPid = null;
      try {
        // contributor, three URLs: /?s=net, /?s=zzqqxxnothing (public) and
        // /wp-admin/profile.php (admin: opens only when signed in). Chrome is
        // killed as soon as the first URL is logged.
        const r = await runCrawl({
          port: t.port,
          fake,
          args: ['--roles=contributor', '--match=search,profile'],
          onLine: (line) => {
            if (killedPid || !/^\s+\S+\s+public\s/.test(line)) return;
            const first = launched(fake)[0];
            if (!first) return;
            killedPid = first.chrome;
            process.kill(first.chrome, 'SIGKILL');
          },
        });
        t.expect(killedPid, 'the test killed Chrome after the first URL').toBeTruthy();
        const recs = r.report?.contributor || [];
        t.expect(recs.length, `records (stdout: ${r.out.slice(-600)})`).toBeGreaterThan(2);
        for (const rec of recs) {
          const errs = errorsOf(rec);
          t.expect(errs.filter((m) => /browser is closed|browser connection closed/.test(m)), `${rec.url}: errors from the dead browser (all records: ${brief(recs)})`).toEqual([]);
          t.expect(rec.status, `${rec.url}: HTTP status (all records: ${brief(recs)})`).toBeGreaterThan(0);
          t.expect(rec.access, `${rec.url}: access`).not.toBe('error');
        }
        const none = recs.find((x) => x.url === '/?s=zzqqxxnothing');
        t.expect(none?.status, '/?s=zzqqxxnothing status').toBe(200);
        const profile = recs.find((x) => x.url === '/wp-admin/profile.php');
        t.expect(profile?.access, `/wp-admin/profile.php access (signed in again after the restart: ${brief([profile])})`).toBe('ok');
        t.expect(r.code, 'crawler exit code').toBe(0);
      } finally {
        cleanUp(fake);
      }
    },
  },
];
