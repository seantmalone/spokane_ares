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
// invalid blocks. Sign-in uses the dev-only ?dev_login= switch. The Chrome
// driver is lib/cdp.mjs (shared with qa/crawl.mjs and tests/run-e2e.mjs).

import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { Browser, DESKTOP, PHONE, sleep } from './lib/cdp.mjs';

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

const PAGES = [
  ['home', '/'],
  ['how', '/how-it-works/'],
  ['about', '/about/'],
  ['members', '/members/'],
  ['documents', '/members/documents/'],
  ['exercises', '/members/exercises/'],
];

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

/* ------------------------------------------------------------------ shots */

// The CDP driver lives in lib/cdp.mjs (shared with qa/crawl.mjs and
// tests/run-e2e.mjs). One browser context per signed-in user.
let browser;
const contexts = {};
async function context(who) {
  if (!contexts[who]) contexts[who] = await browser.newContext();
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
  let page;
  try {
    page = await browser.newPage({ context: await context(who), viewport: view });
    const nav = await page.goto(base + withLogin(url, who), { timeout: 90000 });
    if (nav.error && nav.error !== 'net::ERR_ABORTED') throw new Error(nav.error);
    // Redirect chains (dev login, dev_edit) end in one more load.
    for (let i = 0; i < 3; i++) {
      const state = await page.evaluate('document.readyState + "|" + location.href');
      if (state.startsWith('complete') && !/dev_login=|dev_edit=/.test(state)) break;
      await page.waitForEvent('Page.loadEventFired', { timeout: 30000 }).catch(() => {});
    }
    await page.evaluate('document.fonts ? document.fonts.ready.then(() => true) : true');

    let extra = '';
    if (opts.blockEditor) {
      extra = await prepareBlockEditor(page);
    } else {
      // A full-page shot shows images below the fold, so load lazy images now
      // (in this browser only; the page itself is unchanged) and wait for them.
      await page.loadImages();
    }
    await sleep(opts.blockEditor ? 1200 : 500);

    const title = await page.evaluate('document.title');
    const finalUrl = await page.evaluate('location.href');
    const s = await page.screenshot(file, { fullPage: !opts.blockEditor });
    const line = `ok   ${path.basename(file)}  ${s.width}x${s.height}  ${finalUrl.replace(base, '')}  "${title}"${extra ? '  ' + extra : ''}`;
    report.push(line);
    console.log(line);
    const errors = [...page.exceptions.map((e) => e.text), ...page.consoleErrors.map((e) => `${e.text} ${e.url || ''}`.trim())].filter((e) => !/favicon/.test(e));
    for (const e of [...new Set(errors)]) console.log(`     console error: ${e.slice(0, 200)}`);
  } catch (err) {
    failures++;
    console.log(`FAIL ${path.basename(file)}  ${err.message}`);
  } finally {
    await page?.close();
  }
}

// Block editor: wait for the blocks, check they are all valid, select the
// hero Cover (Home) and open the block sidebar.
async function prepareBlockEditor(page) {
  const ok = await page.evaluate(`(async () => {
    for (let i = 0; i < 240; i++) {
      try { if (window.wp?.data?.select('core/block-editor').getBlocks().length) return true; } catch (e) {}
      await new Promise(r => setTimeout(r, 250));
    }
    return false; })()`);
  if (!ok) throw new Error('block editor did not load');
  // Pages open with the template shown (plugin: defaultRenderingMode), so the
  // canvas also waits for the template, its parts and the server previews.
  await page.evaluate(`(async () => {
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
  const info = await page.evaluate(`(() => {
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

browser = await Browser.launch();
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
  await browser.close();
}
console.log(`shots: ${report.length} written to ${outdir}${failures ? `, ${failures} problem(s)` : ''}`);
process.exit(failures ? 1 : 0);
