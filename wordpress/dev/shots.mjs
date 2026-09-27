#!/usr/bin/env node
// Screenshots of the dev site (PLAN.md §6.10 "Screenshots"). Node standard
// library only: it drives the installed Google Chrome over the DevTools
// protocol with Node's built-in WebSocket (Node 22+), so there is nothing to
// install and full-page shots keep a real viewport (1440x900, or a true
// 390x844 phone), which keeps vh-sized sections the right height.
//
//   node wordpress/dev/shots.mjs <PORT> [outdir] [--only=front|admin]
//
// Writes <page>-desktop.png, <page>-mobile-390.png, members-mobile-390-editor.png
// and admin-<screen>.png, and prints one line per shot plus any browser
// console errors. Exit status 1 if a shot failed or the block editor reported
// invalid blocks. Sign-in uses the dev-only ?dev_login= switch.

import { spawn } from 'node:child_process';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const here = path.dirname(fileURLToPath(import.meta.url));
const args = process.argv.slice(2);
const port = args.find((a) => /^\d+$/.test(a));
if (!port) {
  console.error('usage: node shots.mjs <PORT> [outdir] [--only=front|admin]');
  process.exit(2);
}
const only = (args.find((a) => a.startsWith('--only=')) || '').slice(7);
const outdir = path.resolve(args.find((a) => !/^\d+$/.test(a) && !a.startsWith('--')) || path.join(here, 'shots'));
fs.mkdirSync(outdir, { recursive: true });
const base = `http://127.0.0.1:${port}`;
const CHROME = process.env.CHROME || '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';

const PAGES = [
  ['home', '/'],
  ['how', '/how-it-works/'],
  ['about', '/about/'],
  ['members', '/members/'],
  ['documents', '/members/documents/'],
  ['exercises', '/members/exercises/'],
];
const DESKTOP = { width: 1440, height: 900, mobile: false };
const PHONE = { width: 390, height: 844, mobile: true };

// Admin screens: [file, who, url or dev_edit spec, options].
const ADMIN = [
  ['dashboard', 'editor', '/wp-admin/'],
  ['net-rota', 'editor', '/wp-admin/admin.php?page=spokares-rota'],
  ['events', 'editor', '/wp-admin/edit.php?post_type=spk_event'],
  ['event-add', 'editor', '/wp-admin/post-new.php?post_type=spk_event'],
  ['event-set', 'editor', 'edit:spk_event:set-2026'],
  ['meetings', 'editor', '/wp-admin/admin.php?page=spokares-meetings'],
  ['documents', 'editor', '/wp-admin/edit.php?post_type=spk_document'],
  ['document-ics-213', 'editor', 'edit:spk_document:ics-213'],
  ['tiles', 'editor', '/wp-admin/admin.php?page=spokares-tiles'],
  ['editor-home', 'editor', 'edit:page:home', { blockEditor: true }],
  ['editor-how', 'editor', 'edit:page:how-it-works', { blockEditor: true }],
  ['editor-about', 'editor', 'edit:page:about', { blockEditor: true }],
  ['net-details', 'admin', '/wp-admin/admin.php?page=spokares-net-details'],
  ['meeting-rules', 'admin', '/wp-admin/admin.php?page=spokares-meeting-rules'],
];

/* ------------------------------------------------------------- CDP client */

let ws;
let nextId = 0;
const pending = new Map();
const listeners = new Set();

function send(method, params = {}, sessionId) {
  const id = ++nextId;
  const msg = { id, method, params };
  if (sessionId) msg.sessionId = sessionId;
  return new Promise((resolve, reject) => {
    pending.set(id, { resolve, reject, method });
    ws.send(JSON.stringify(msg));
  });
}

function waitFor(method, sessionId, ms = 60000) {
  return new Promise((resolve, reject) => {
    const timer = setTimeout(() => {
      listeners.delete(fn);
      reject(new Error(`timeout waiting for ${method}`));
    }, ms);
    const fn = (m) => {
      if (m.method === method && m.sessionId === sessionId) {
        clearTimeout(timer);
        listeners.delete(fn);
        resolve(m.params);
      }
    };
    listeners.add(fn);
  });
}

const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

// Never leave a headless Chrome behind: kill it on exit, on a signal, and
// keep going if whoever reads our output goes away (EPIPE).
let chromeProc = null;
const killChrome = () => { try { chromeProc?.kill('SIGKILL'); } catch {} };
process.on('exit', killChrome);
for (const sig of ['SIGINT', 'SIGTERM', 'SIGHUP']) process.on(sig, () => { killChrome(); process.exit(130); });
process.stdout.on('error', () => {});

async function launch() {
  const profile = fs.mkdtempSync(path.join(os.tmpdir(), 'spokares-shots-'));
  const chrome = spawn(
    CHROME,
    [
      '--headless=new',
      '--disable-gpu',
      '--hide-scrollbars',
      '--no-first-run',
      '--no-default-browser-check',
      '--disable-extensions',
      '--mute-audio',
      '--remote-debugging-port=0',
      `--user-data-dir=${profile}`,
      'about:blank',
    ],
    { stdio: 'ignore' }
  );
  chromeProc = chrome;
  const portFile = path.join(profile, 'DevToolsActivePort');
  for (let i = 0; i < 150 && !fs.existsSync(portFile); i++) await sleep(100);
  if (!fs.existsSync(portFile)) throw new Error('Chrome did not start (no DevToolsActivePort)');
  await sleep(100);
  const [devPort, wsPath] = fs.readFileSync(portFile, 'utf8').trim().split('\n');
  ws = new WebSocket(`ws://127.0.0.1:${devPort}${wsPath}`);
  await new Promise((resolve, reject) => {
    ws.onopen = resolve;
    ws.onerror = () => reject(new Error('DevTools WebSocket failed'));
  });
  ws.onmessage = (ev) => {
    const m = JSON.parse(ev.data);
    if (m.id && pending.has(m.id)) {
      const p = pending.get(m.id);
      pending.delete(m.id);
      m.error ? p.reject(new Error(`${p.method}: ${m.error.message}`)) : p.resolve(m.result);
    } else if (m.method) {
      for (const fn of [...listeners]) fn(m);
    }
  };
  return () => {
    try { ws.close(); } catch {}
    chrome.kill();
    setTimeout(() => fs.rmSync(profile, { recursive: true, force: true }), 500);
  };
}

async function evaluate(sessionId, expression, awaitPromise = true) {
  const r = await send('Runtime.evaluate', { expression, awaitPromise, returnByValue: true }, sessionId);
  if (r.exceptionDetails) throw new Error(`page script: ${r.exceptionDetails.exception?.description || r.exceptionDetails.text}`);
  return r.result.value;
}

/* ------------------------------------------------------------------ shots */

const contexts = {};
async function context(who) {
  if (!contexts[who]) contexts[who] = (await send('Target.createBrowserContext', { disposeOnDetach: true })).browserContextId;
  return contexts[who];
}

function withLogin(url, who) {
  if (who === 'public') return url;
  const sep = url.includes('?') ? '&' : '?';
  return `${url}${sep}dev_login=${who === 'admin' ? '1' : 'editor'}`;
}

let failures = 0;
const report = [];

async function shot(name, who, url, view, opts = {}) {
  const file = path.join(outdir, `${name}.png`);
  const { targetId } = await send('Target.createTarget', { url: 'about:blank', browserContextId: await context(who) });
  const { sessionId } = await send('Target.attachToTarget', { targetId, flatten: true });
  const errors = [];
  const onMsg = (m) => {
    if (m.sessionId !== sessionId) return;
    if (m.method === 'Runtime.exceptionThrown') errors.push(m.params.exceptionDetails.exception?.description?.split('\n')[0] || m.params.exceptionDetails.text);
    if (m.method === 'Runtime.consoleAPICalled' && m.params.type === 'error') errors.push(m.params.args.map((a) => a.value ?? a.description ?? '').join(' ').slice(0, 200));
    if (m.method === 'Log.entryAdded' && m.params.entry.level === 'error' && !/favicon/.test(m.params.entry.url || '')) errors.push(`${m.params.entry.text} ${m.params.entry.url || ''}`.slice(0, 200));
  };
  listeners.add(onMsg);
  try {
    await send('Page.enable', {}, sessionId);
    await send('Runtime.enable', {}, sessionId);
    await send('Log.enable', {}, sessionId);
    await send('Emulation.setDeviceMetricsOverride', { width: view.width, height: view.height, deviceScaleFactor: 1, mobile: view.mobile }, sessionId);
    const loaded = waitFor('Page.loadEventFired', sessionId, 90000);
    const nav = await send('Page.navigate', { url: base + withLogin(url, who) }, sessionId);
    if (nav.errorText) throw new Error(nav.errorText);
    await loaded;
    // Redirect chains (dev login, dev_edit) end in one more load.
    for (let i = 0; i < 3; i++) {
      const state = await evaluate(sessionId, 'document.readyState + "|" + location.href');
      if (state.startsWith('complete') && !/dev_login=|dev_edit=/.test(state)) break;
      await waitFor('Page.loadEventFired', sessionId, 30000).catch(() => {});
    }
    await evaluate(sessionId, 'document.fonts ? document.fonts.ready.then(() => true) : true');

    let extra = '';
    if (opts.blockEditor) {
      extra = await prepareBlockEditor(sessionId);
    } else {
      // A full-page shot shows images below the fold, so load lazy images now
      // (in this browser only; the page itself is unchanged) and wait for them.
      await evaluate(sessionId, `(async () => {
        document.querySelectorAll('img[loading="lazy"]').forEach(i => { i.loading = 'eager'; });
        await Promise.all([...document.images].map(i => i.decode ? i.decode().catch(() => {}) : null));
        await Promise.all([...document.images].filter(i => !i.complete).map(i => new Promise(r => { i.onload = i.onerror = r; setTimeout(r, 5000); })));
        return true; })()`);
    }
    await sleep(opts.blockEditor ? 1200 : 500);

    const title = await evaluate(sessionId, 'document.title');
    const finalUrl = await evaluate(sessionId, 'location.href');
    let shotParams = { format: 'png' };
    let size = `${view.width}x${view.height}`;
    if (!opts.blockEditor) {
      const m = await send('Page.getLayoutMetrics', {}, sessionId);
      const h = Math.ceil(m.cssContentSize.height);
      shotParams = { format: 'png', captureBeyondViewport: true, clip: { x: 0, y: 0, width: view.width, height: h, scale: 1 } };
      size = `${view.width}x${h}`;
    }
    const { data } = await send('Page.captureScreenshot', shotParams, sessionId);
    fs.writeFileSync(file, Buffer.from(data, 'base64'));
    const line = `ok   ${path.basename(file)}  ${size}  ${finalUrl.replace(base, '')}  "${title}"${extra ? '  ' + extra : ''}`;
    report.push(line);
    console.log(line);
    for (const e of [...new Set(errors)]) console.log(`     console error: ${e}`);
  } catch (err) {
    failures++;
    console.log(`FAIL ${path.basename(file)}  ${err.message}`);
  } finally {
    listeners.delete(onMsg);
    await send('Target.closeTarget', { targetId }).catch(() => {});
  }
}

// Block editor: wait for the blocks, check they are all valid, select the
// hero Cover (Home) and open the block sidebar.
async function prepareBlockEditor(sessionId) {
  const ok = await evaluate(sessionId, `(async () => {
    for (let i = 0; i < 240; i++) {
      try { if (window.wp?.data?.select('core/block-editor').getBlocks().length) return true; } catch (e) {}
      await new Promise(r => setTimeout(r, 250));
    }
    return false; })()`);
  if (!ok) throw new Error('block editor did not load');
  // Pages open with the template shown (plugin: defaultRenderingMode), so the
  // canvas also waits for the template, its parts and the server previews.
  await evaluate(sessionId, `(async () => {
    for (let i = 0; i < 120; i++) {
      const doc = document.querySelector('iframe[name="editor-canvas"]')?.contentDocument;
      // Server previews (the brand in the header, the plugin's lists) show
      // "Loading…" until their REST render returns.
      const ready = doc && doc.querySelector('.wp-block-cover, .wp-block-post-content > *')
        && !doc.querySelector('.components-spinner, .block-editor-block-list__block.is-loading')
        && !/Loading…/.test(doc.body.innerText);
      if (ready) return true;
      await new Promise(r => setTimeout(r, 250));
    }
    return false; })()`);
  await sleep(1500);
  const info = await evaluate(sessionId, `(() => {
    const be = wp.data.select('core/block-editor');
    const all = [];
    // getBlocks(clientId) also reaches the page's own blocks inside the
    // template's Post Content block (controlled inner blocks).
    const walk = (id) => be.getBlocks(id).forEach(b => { all.push(b); walk(b.clientId); });
    walk('');
    const invalid = all.filter(b => b.isValid === false).map(b => b.name);
    const cover = all.find(b => b.name === 'core/cover') || null;
    if (cover) wp.data.dispatch('core/block-editor').selectBlock(cover.clientId);
    try { wp.data.dispatch('core/edit-post').openGeneralSidebar('edit-post/block'); } catch (e) {
      try { wp.data.dispatch('core/interface').enableComplementaryArea('core', 'edit-post/block'); } catch (e2) {}
    }
    const warnings = document.querySelectorAll('.block-editor-warning').length
      + (document.querySelector('iframe[name="editor-canvas"]')?.contentDocument?.querySelectorAll('.block-editor-warning').length || 0);
    const save = [...document.querySelectorAll('.editor-header button, .edit-post-header button')].map(b => b.textContent.trim()).filter(t => /^(Save|Update|Publish)$/.test(t));
    const guide = !!document.querySelector('.edit-post-welcome-guide, .components-guide');
    const mode = wp.data.select('core/editor').getRenderingMode?.() || '?';
    return { blocks: all.length, invalid, warnings, cover: !!cover, save, guide, mode };
  })()`);
  if (info.invalid.length || info.warnings) {
    failures++;
    return `INVALID BLOCKS: ${info.invalid.join(', ')} (${info.warnings} warnings)`;
  }
  return `${info.blocks} blocks, all valid; ${info.cover ? 'hero selected' : 'no hero on this page'}; save button "${info.save.join('/') || '?'}"; ${info.mode}; welcome guide ${info.guide ? 'SHOWN' : 'not shown'}`;
}

/* ------------------------------------------------------------------- main */

const stop = await launch();
try {
  if (only !== 'admin') {
    for (const [name, url] of PAGES) {
      await shot(`${name}-desktop`, 'public', url, DESKTOP);
      await shot(`${name}-mobile-390`, 'public', url, PHONE);
    }
    await shot('members-mobile-390-editor', 'editor', '/members/', PHONE);
  }
  if (only !== 'front') {
    for (const [name, who, spec, opts] of ADMIN) {
      const url = spec.startsWith('edit:') ? `/wp-admin/?dev_edit=${encodeURIComponent(spec.slice(5))}` : spec;
      await shot(`admin-${name}`, who, url, DESKTOP, opts || {});
    }
  }
} finally {
  stop();
}
console.log(`shots: ${report.length} written to ${outdir}${failures ? `, ${failures} problem(s)` : ''}`);
process.exit(failures ? 1 : 0);
