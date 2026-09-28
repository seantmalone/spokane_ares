#!/usr/bin/env node
// DEV ONLY. Browser end-to-end tests against the running dev site, Node
// standard library only (Chrome over the DevTools protocol, dev/lib/cdp.mjs).
//
//   node wordpress/dev/tests/run-e2e.mjs <PORT> [filter] [--list] [--headful]
//
// Runs every dev/tests/e2e/*.test.mjs. filter: a case-insensitive substring of
// "<file>::<test name>". Each test gets its own browser context (fresh
// cookies), signed in as its `role` (dev/setup/qa-users.json; default
// anonymous). A test fails when it throws, times out, or when PHP writes a
// warning, notice, deprecation or fatal to the dev logs while it runs (unless
// it sets allowPhpNotices). A failed test leaves a screenshot in
// wordpress/qa/e2e-failures/. Exit 0 when all pass, 1 on any failure, 2 on
// usage or when the site is not answering. See dev/tests/README.md.
//
// A test file:
//
//   export const tests = [
//     { name: 'the rota opens', role: 'ares-editor', async run(t) {
//         await t.goto('/wp-admin/admin.php?page=spokares-rota');
//         await t.expectText('h1', 'Net rota');
//     } },
//   ];

import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath, pathToFileURL } from 'node:url';
import { Browser, DESKTOP, PHONE, sleep, withTimeout } from '../lib/cdp.mjs';
import { domFacts, blockEditorFacts } from '../lib/inspect.mjs';
import { account, baseUrl, siteUp, ids as fetchIds, unlockPosts, whoami, loginAs, phpLogs, WP_DIR } from '../lib/site.mjs';

const here = path.dirname(fileURLToPath(import.meta.url));
const argv = process.argv.slice(2);
const port = argv.find((a) => /^\d+$/.test(a));
if (!port) {
  console.error('usage: node dev/tests/run-e2e.mjs <PORT> [filter] [--list]');
  process.exit(2);
}
const filter = (argv.find((a) => !/^\d+$/.test(a) && !a.startsWith('--')) || '').toLowerCase();
const LIST = argv.includes('--list');
const BASE = baseUrl(port);
const FAIL_DIR = path.join(WP_DIR, 'qa', 'e2e-failures');
const LOGS = phpLogs(port);

/* ------------------------------------------------------------ expectations */

class ExpectError extends Error {}

function show(v) {
  try { const s = JSON.stringify(v); return s === undefined ? String(v) : (s.length > 200 ? `${s.slice(0, 200)}…` : s); } catch { return String(v); }
}

/** expect(actual).toBe(x) / .toEqual / .toContain / .toMatch / .toBeTruthy / .toBeFalsy / .toBeGreaterThan / .toBeLessThan / .toHaveLength, and .not.* */
export function expect(actual, label = '') {
  const make = (negate) => {
    const check = (ok, what) => {
      if (negate ? ok : !ok) throw new ExpectError(`${label ? `${label}: ` : ''}expected ${show(actual)} ${negate ? 'not ' : ''}${what}`);
    };
    return {
      toBe: (x) => check(Object.is(actual, x), `to be ${show(x)}`),
      toEqual: (x) => check(JSON.stringify(actual) === JSON.stringify(x), `to equal ${show(x)}`),
      toContain: (x) => check(actual != null && (typeof actual === 'string' ? actual.includes(x) : [...actual].includes(x)), `to contain ${show(x)}`),
      toMatch: (re) => check(re.test(String(actual)), `to match ${re}`),
      toBeTruthy: () => check(!!actual, 'to be truthy'),
      toBeFalsy: () => check(!actual, 'to be falsy'),
      toBeGreaterThan: (n) => check(actual > n, `to be greater than ${n}`),
      toBeLessThan: (n) => check(actual < n, `to be less than ${n}`),
      toHaveLength: (n) => check(actual != null && actual.length === n, `to have length ${n}`),
    };
  };
  return { ...make(false), not: make(true) };
}

/* ------------------------------------------------------------ test context */

function makeContext({ browser, page, file, test, siteIds, logMarks }) {
  const t = {
    base: BASE,
    port: Number(port),
    page,
    ids: siteIds,
    role: test.role || 'anonymous',
    status: null,
    lastNav: null,
    expect,
    log: (...a) => console.log('      ', ...a),

    /** Sign this test's browser context in as a role key (anonymous: no-op). */
    async loginAs(role) { t.role = role; return loginAs(page, BASE, role); },
    /** Open a path (or full URL); returns { status, url, chain }. Sets t.status. */
    async goto(p, { timeout = 60000 } = {}) {
      const nav = await page.goto(/^https?:/.test(p) ? p : BASE + p, { timeout });
      t.status = nav.status;
      t.lastNav = nav;
      if (nav.timedOut) throw new ExpectError(`${p} did not finish loading in ${timeout / 1000} s`);
      return nav;
    },
    click: (sel, opts) => page.click(sel, opts),
    /** Click and wait for the page it opens. */
    async clickAndWait(sel, opts = {}) { await page.click(sel, { ...opts, navigation: true }); t.status = page.docResponse?.status ?? null; },
    type: (sel, text, opts) => page.type(sel, text, opts),
    press: (key) => page.press(key),
    evaluate: (fn, ...args) => page.evaluate(fn, ...args),
    waitFor: (sel, opts) => page.waitFor(sel, opts),
    waitForFunction: (fn, opts) => page.waitForFunction(fn, opts),
    waitForNavigation: (opts) => page.waitForNavigation(opts),
    /** Wait until the element (default body) contains text. */
    async waitForText(text, { selector = 'body', timeout = 15000 } = {}) {
      return page.waitForFunction((sel, txt) => { const el = document.querySelector(sel); return !!el && el.innerText.includes(txt); }, { timeout, args: [selector, text] });
    },
    text: (sel) => page.evaluate((s) => { const el = document.querySelector(s); return el ? el.innerText.trim() : null; }, sel),
    texts: (sel) => page.evaluate((s) => [...document.querySelectorAll(s)].map((el) => el.innerText.trim()), sel),
    count: (sel) => page.evaluate((s) => document.querySelectorAll(s).length, sel),
    exists: (sel) => page.evaluate((s) => !!document.querySelector(s), sel),
    attr: (sel, name) => page.evaluate((s, n) => document.querySelector(s)?.getAttribute(n) ?? null, sel, name),
    url: () => page.evaluate('location.href'),
    setViewport: (v) => page.setViewport(v === 'phone' ? PHONE : v === 'desktop' ? DESKTOP : v),
    /** Save a screenshot to wordpress/qa/e2e-failures/<file>--<name>.png (also taken on failure). */
    screenshot: (name) => page.screenshot(path.join(FAIL_DIR, `${slug(file)}--${slug(name)}.png`)),
    whoami: (caps = []) => whoami(page, BASE, caps),
    unlockPosts: () => unlockPosts(BASE),
    /** domFacts() of the current page: overflow, notices, a11y lists, links (dev/lib/inspect.mjs). */
    facts: (opts = {}) => page.evaluate(domFacts, { isPublic: true, vw: page.viewport.width, ...opts }),
    /** Wait for the block editor and return blockEditorFacts() (dev/lib/inspect.mjs). */
    blockEditor: (timeout = 60000) => page.evaluate(blockEditorFacts, timeout),
    /** Console errors, uncaught exceptions and failed requests seen so far. */
    get consoleErrors() { return page.consoleErrors; },
    get exceptions() { return page.exceptions; },
    get failedRequests() { return page.failedRequests; },
    /** New PHP log lines (warnings, notices, fatals) since the test started. */
    phpMessages: () => [...new Set(LOGS.flatMap((l, i) => l.phpSince(logMarks[i])))],

    async expectStatus(code) { expect(t.status, `HTTP status of ${t.lastNav?.url || 'the last page'}`).toBe(code); },
    async expectVisible(sel) { await page.waitFor(sel, { visible: true, timeout: 10000 }).catch(() => { throw new ExpectError(`${sel} is not visible`); }); },
    async expectHidden(sel) {
      const vis = await page.evaluate((s) => { const el = document.querySelector(s); if (!el) return false; const r = el.getBoundingClientRect(); return r.width > 0 && r.height > 0 && getComputedStyle(el).visibility !== 'hidden'; }, sel);
      if (vis) throw new ExpectError(`${sel} is visible`);
    },
    async expectText(sel, substring) {
      const txt = await t.text(sel);
      if (txt === null) throw new ExpectError(`${sel} not found`);
      expect(txt, sel).toContain(substring);
    },
    async expectCount(sel, n) { expect(await t.count(sel), `number of ${sel}`).toBe(n); },
    expectNoConsoleErrors() {
      const all = [...page.exceptions.map((e) => `uncaught: ${e.text}`), ...page.consoleErrors.map((e) => e.text)];
      if (all.length) throw new ExpectError(`console errors: ${all.slice(0, 5).join(' | ')}`);
    },
    expectNoFailedRequests() {
      const same = page.failedRequests.filter((r) => r.url.startsWith(BASE));
      if (same.length) throw new ExpectError(`failed requests: ${same.slice(0, 5).map((r) => `${r.status || r.error} ${r.url.replace(BASE, '')}`).join(' | ')}`);
    },
    expectNoPhpErrors() {
      const m = t.phpMessages();
      if (m.length) throw new ExpectError(`PHP messages: ${m.slice(0, 3).join(' | ')}`);
    },
  };
  return t;
}

const slug = (s) => String(s).replace(/\.test\.mjs$/, '').replace(/[^a-z0-9]+/gi, '-').replace(/^-|-$/g, '').toLowerCase().slice(0, 80);

/* ------------------------------------------------------------------ runner */

async function loadTests() {
  const dir = path.join(here, 'e2e');
  const files = fs.existsSync(dir) ? fs.readdirSync(dir).filter((f) => f.endsWith('.test.mjs')).sort() : [];
  const all = [];
  for (const f of files) {
    const mod = await import(pathToFileURL(path.join(dir, f)).href);
    let list = mod.tests || mod.default || [];
    if (!Array.isArray(list)) list = Object.entries(list).map(([name, v]) => (typeof v === 'function' ? { name, run: v } : { name, ...v }));
    for (const test of list) {
      if (!test || typeof test.run !== 'function' || !test.name) throw new Error(`${f}: every test needs a name and a run(t) function`);
      all.push({ file: f, ...test });
    }
  }
  return all.filter((x) => !filter || `${x.file}::${x.name}`.toLowerCase().includes(filter));
}

const tests = await loadTests();
if (LIST) {
  for (const x of tests) console.log(`${x.file}::${x.name}${x.role ? `  [${x.role}]` : ''}`);
  console.log(`e2e tests: ${tests.length} match`);
  process.exit(0);
}
if (!(await siteUp(BASE))) {
  console.error(`run-e2e.mjs: nothing answering on ${BASE} (start it with wordpress/dev/start.sh ${port})`);
  process.exit(2);
}
for (const x of tests) if (x.role) account(x.role); // unknown role keys fail fast
let siteIds = {};
try { siteIds = await fetchIds(BASE); } catch (err) { console.error(`run-e2e.mjs: ?dev_qa=ids failed (${err.message}); is dev/mu-plugins/spokares-dev-qa.php loaded?`); process.exit(2); }
fs.rmSync(FAIL_DIR, { recursive: true, force: true });

let browser = await Browser.launch();
const counts = { pass: 0, fail: 0, skip: 0 };
const t0 = Date.now();
for (const test of tests) {
  if (test.skip) { counts.skip++; console.log(`SKIP  ${test.file} :: ${test.name}${typeof test.skip === 'string' ? `  (${test.skip})` : ''}`); continue; }
  if (!(await browser.alive())) { await browser.close().catch(() => {}); browser = await Browser.launch(); }
  const started = Date.now();
  let page;
  let context;
  let error = null;
  const logMarks = LOGS.map((l) => l.mark());
  let t;
  try {
    context = await browser.newContext();
    page = await browser.newPage({ context, viewport: test.viewport === 'phone' ? PHONE : DESKTOP });
    t = makeContext({ browser, page, file: test.file, test, siteIds, logMarks });
    await withTimeout((async () => {
      if (test.role && test.role !== 'anonymous') await t.loginAs(test.role);
      page.resetCapture();
      await test.run(t);
    })(), test.timeout || 90000, `test "${test.name}"`);
    if (!test.allowPhpNotices) {
      await sleep(200);
      const php = t.phpMessages();
      if (php.length) throw new ExpectError(`PHP wrote ${php.length} message(s) during the test: ${php[0].replace(/^\[[^\]]+\]\s*/, '').slice(0, 300)}`);
    }
  } catch (err) {
    error = err;
  }
  const ms = Date.now() - started;
  if (!error) {
    counts.pass++;
    console.log(`PASS  ${test.file} :: ${test.name}  (${test.role || 'anonymous'}, ${ms} ms)`);
  } else {
    counts.fail++;
    console.log(`FAIL  ${test.file} :: ${test.name}  (${test.role || 'anonymous'}, ${ms} ms)`);
    console.log(`      ${error instanceof ExpectError ? error.message : error.stack?.split('\n').slice(0, 3).join('\n      ') || error}`);
    if (page) {
      try { console.log(`      at ${await page.evaluateT(5000, 'location.href')}`); } catch {}
      for (const e of page.exceptions.slice(0, 3)) console.log(`      uncaught: ${e.text}`);
      for (const e of page.consoleErrors.slice(0, 3)) console.log(`      console: ${e.text}`);
      try {
        const shot = await page.screenshot(path.join(FAIL_DIR, `${slug(test.file)}--${slug(test.name)}.png`), { fullPage: false });
        console.log(`      screenshot: ${shot.file}`);
      } catch {}
    }
  }
  if (page) await page.close().catch(() => {});
  if (context) await browser.send('Target.disposeBrowserContext', { browserContextId: context }, undefined, 5000).catch(() => {});
}
await browser.close();
console.log(`e2e tests: ${counts.pass} passed, ${counts.fail} failed, ${counts.skip} skipped in ${Math.round((Date.now() - t0) / 1000)} s${filter ? `, filter "${filter}"` : ''}`);
if (!tests.length) console.log('NOTE  no tests matched');
process.exit(counts.fail ? 1 : 0);
