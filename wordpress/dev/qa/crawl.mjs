#!/usr/bin/env node
// DEV ONLY. QA crawl of the dev site as every user level (PLAN.md §4, §6.10).
// Node standard library only; drives the installed Chrome over the DevTools
// protocol (dev/lib/cdp.mjs).
//
//   node wordpress/dev/qa/crawl.mjs <PORT> <outdir> [--roles=a,b] [--shots=<dir>]
//        [--no-mobile] [--max-public=40] [--max-admin=100] [--match=a,b] [--merge] [--quiet]
//
// --match=a,b visits only URLs whose screen key or URL contains a or b (for
// re-checking a few screens after a fix, e.g. --match=editor-,net-rota).
// --merge folds the result into an existing <outdir>/report.json (same role
// and URL replaced, the rest kept) and rewrites summary.md from it.
// --summary-only rewrites <outdir>/summary.md from report.json and stops.
//
// Roles (dev/setup/qa-users.json): anonymous, subscriber, contributor, author,
// core-editor, ares-editor, ares-net, admin. Each role gets a fresh Chrome
// profile and signs in with ?dev_login= (anonymous stays signed out).
//
// For each role it visits:
//   public     the six pages, a 404, search results (hits and none), the sign-in
//              pages (anonymous) and every same-origin page linked from them;
//              /docs/<slug>/ and upload links are checked over HTTP (status and
//              Location, no browser); every #fragment link is checked against
//              the ids of the page it points to
//   admin      the Dashboard, every #adminmenu link (submenus too), admin-bar
//              and Dashboard links, and the known screens (custom screens,
//              events and documents lists / add / edit, library sections,
//              pages, the block editor for Home, How it works and About, media,
//              users, site editor, appearance...) the role's capabilities allow
//   forbidden the same known screens the role's capabilities do NOT allow, the
//              plugin's admin-post.php actions without a nonce, REST probes and
//              one REST write probe (POST /wp/v2/blocks, deleted again if it
//              gets through); each is classified as denied / login /
//              redirected / LEAK / ERROR
// For every URL: HTTP status, final URL, redirects, title, a 1440-wide
// full-page screenshot (public pages also a true 390x844 phone), console
// errors and uncaught exceptions, failed requests, new PHP messages in the
// logs during that request, horizontal overflow and content running out of
// its column, admin error notices, block editor validity and lock settings
// (and, for non-admins, the page card's ⋮ menu), Bulk actions and row actions
// against the site's policy, and basic accessibility signals.
//
// Writes <outdir>/report.json ({ role: [ {url, kind, status, shots, findings, ...} ] }),
// <outdir>/meta.json and <outdir>/summary.md. Screenshots go to
// <shots>/<role>/ (default <outdir>/shots). Exit 0 when the crawl ran (findings
// don't change the exit code), 1 if it could not run or a role could not be
// crawled (Chrome would not start for it, or died and could not be restarted;
// the report is still written), 2 on usage. Chrome gets SPOKARES_CHROME_CONNECT_MS
// (default 60000) per launch attempt to open DevTools (dev/lib/cdp.mjs).

import fs from 'node:fs';
import path from 'node:path';
import { Browser, BrowserClosedError, DESKTOP, PHONE, sleep, withTimeout } from '../lib/cdp.mjs';
import { domFacts, blockEditorFacts, siteEditorFacts } from '../lib/inspect.mjs';
import { accounts, account, baseUrl, siteUp, ids as fetchIds, unlockPosts, whoami, loginAs, phpLogs } from '../lib/site.mjs';

/* -------------------------------------------------------------- arguments */

const argv = process.argv.slice(2);
const port = argv.find((a) => /^\d+$/.test(a));
const outArg = argv.find((a) => !/^\d+$/.test(a) && !a.startsWith('--'));
const opt = (name, dflt) => {
  const hit = argv.find((a) => a === `--${name}` || a.startsWith(`--${name}=`));
  if (!hit) return dflt;
  return hit.includes('=') ? hit.slice(hit.indexOf('=') + 1) : true;
};
if (!port || !outArg) {
  console.error('usage: node dev/qa/crawl.mjs <PORT> <outdir> [--roles=a,b] [--shots=<dir>] [--no-mobile] [--max-public=40] [--max-admin=100] [--match=a,b] [--merge] [--quiet]');
  process.exit(2);
}
const OUT = path.resolve(outArg);
const SHOTS = path.resolve(opt('shots', path.join(OUT, 'shots')));
const MOBILE = !opt('no-mobile', false);
const MAX_PUBLIC = Number(opt('max-public', 40));
const MAX_ADMIN = Number(opt('max-admin', 100));
const QUIET = !!opt('quiet', false);
// --merge: add to <outdir>/report.json instead of replacing it (records with
// the same role and URL are replaced), and keep the roles' other screenshots.
const MERGE = !!opt('merge', false);
// --match=a,b: only visit URLs whose screen key or URL contains one of these.
const MATCH = String(opt('match', '')).split(',').map((m) => m.trim()).filter(Boolean);
const wanted = (key, url) => !MATCH.length || MATCH.some((m) => (key || '').includes(m) || (url || '').includes(m));
const BASE = baseUrl(port);
const ORIGIN = new URL(BASE).origin;
const ALL = accounts();
const ROLES = String(opt('roles', ALL.map((a) => a.key).join(','))).split(',').map((s) => s.trim()).filter(Boolean);
for (const r of ROLES) account(r); // throws on an unknown key
const LOGS = phpLogs(port);

fs.mkdirSync(OUT, { recursive: true });
fs.mkdirSync(SHOTS, { recursive: true });
const log = (...a) => { if (!QUIET) console.log(...a); };

/* ------------------------------------------------------------------- URLs */

/** Same-origin URL -> "/path?sorted=query" without nonces or the hash; null otherwise. */
function norm(u) {
  let x;
  try { x = new URL(u, BASE); } catch { return null; }
  if (x.origin !== ORIGIN) return null;
  x.hash = '';
  for (const p of ['_wpnonce', '_wp_http_referer', 'dev_login', 'dev_edit', 'today']) x.searchParams.delete(p);
  x.searchParams.sort();
  let p = x.pathname;
  if (p === '/wp-admin' || p === '/wp-admin/index.php') p = '/wp-admin/';
  return p + x.search;
}
const rel = (u) => { try { const x = new URL(u, BASE); return x.origin === ORIGIN ? x.pathname + x.search + x.hash : x.href; } catch { return u; } };

/** Links never followed: state-changing, sign-out, endpoints, other formats. */
function skipLink(href) {
  let x;
  try { x = new URL(href, BASE); } catch { return true; }
  if (x.searchParams.has('_wpnonce')) return true;
  if (/^(trash|delete|untrash|logout|spam|unspam|approve|unapprove|duplicate|lock|resetpassword|rp|postpass|confirmaction)$/.test(x.searchParams.get('action') || '')) return true;
  if (/\/wp-login\.php$/.test(x.pathname)) return true;
  if (/\/wp-admin\/(admin-post|admin-ajax|async-upload|load-scripts|load-styles)\.php$/.test(x.pathname)) return true;
  if (/^\/(wp-json|xmlrpc\.php|feed|comments\/feed)/.test(x.pathname) || x.searchParams.has('rest_route')) return true;
  if (x.pathname === '/wp-admin/export.php' && x.searchParams.has('download')) return true;
  return false;
}
const isFileLink = (href) => /\/wp-content\/uploads\/|\.(pdf|docx?|xlsx?|pptx?|zip|jpe?g|png|gif|webp|svg|txt|csv|ics)$/i.test(new URL(href, BASE).pathname);
const isDocsLink = (href) => /^\/docs\//.test(new URL(href, BASE).pathname);
const isAdminLink = (href) => /^\/wp-admin(\/|$)/.test(new URL(href, BASE).pathname);

function slugFor(u) {
  const x = new URL(u, BASE);
  let s = `${x.pathname}${x.search ? `-${x.search.slice(1)}` : ''}`
    .replace(/^\/wp-admin\/?/, 'admin-')
    .replace(/[^a-z0-9]+/gi, '-')
    .replace(/^-+|-+$/g, '')
    .toLowerCase();
  return (s || 'home').slice(0, 90);
}

/* ---------------------------------------------------------- what to visit */

const PUBLIC_SEEDS = [
  { key: 'home', url: '/' },
  { key: 'how', url: '/how-it-works/' },
  { key: 'about', url: '/about/' },
  { key: 'members', url: '/members/' },
  { key: 'documents', url: '/members/documents/' },
  { key: 'exercises', url: '/members/exercises/' },
  { key: '404', url: '/qa-no-such-page/', expectStatus: 404 },
  { key: 'search-net', url: '/?s=net' },
  { key: 'search-none', url: '/?s=zzqqxxnothing' },
  { key: 'login', url: '/wp-login.php', anonOnly: true },
  { key: 'lost-password', url: '/wp-login.php?action=lostpassword', anonOnly: true },
];

/** The known admin screens and the capability each needs ("cap" or "cap:id"). */
function adminScreens(I, roleKey) {
  const p = I.pages;
  const ev = I.events || {};
  const doc = I.documents || {};
  const u = I.users || {};
  const media = I.media || {};
  const edit = (id) => `/wp-admin/post.php?post=${id}&action=edit`;
  const other = roleKey === 'ares-editor' ? u['qa-subscriber'] : u.editor;
  const list = [
    { key: 'dashboard', url: '/wp-admin/', cap: 'read' },
    { key: 'profile', url: '/wp-admin/profile.php', cap: 'read' },
    { key: 'net-rota', url: '/wp-admin/admin.php?page=spokares-rota', cap: 'spokares_edit_rota', custom: true },
    { key: 'net-details', url: '/wp-admin/admin.php?page=spokares-net-details', cap: 'spokares_edit_net_details', custom: true },
    { key: 'meetings', url: '/wp-admin/admin.php?page=spokares-meetings', cap: 'spokares_edit_rota', custom: true },
    { key: 'meeting-rules', url: '/wp-admin/admin.php?page=spokares-meeting-rules', cap: 'spokares_edit_net_details', custom: true },
    { key: 'tiles', url: '/wp-admin/admin.php?page=spokares-tiles', cap: 'edit_spk_documents', custom: true },
    { key: 'site-settings', url: '/wp-admin/options-general.php?page=spokares-site', cap: 'manage_options', custom: true },
    { key: 'events', url: '/wp-admin/edit.php?post_type=spk_event', cap: 'edit_spk_events', custom: true },
    { key: 'event-add', url: '/wp-admin/post-new.php?post_type=spk_event', cap: 'edit_spk_events', custom: true },
    ev['set-2026'] && { key: 'event-edit', url: edit(ev['set-2026']), cap: `edit_post:${ev['set-2026']}`, custom: true, unlock: true },
    { key: 'documents', url: '/wp-admin/edit.php?post_type=spk_document', cap: 'edit_spk_documents', custom: true },
    { key: 'document-add', url: '/wp-admin/post-new.php?post_type=spk_document', cap: 'edit_spk_documents', custom: true },
    doc['ics-213'] && { key: 'document-edit', url: edit(doc['ics-213']), cap: `edit_post:${doc['ics-213']}`, custom: true, unlock: true },
    { key: 'doc-sections', url: '/wp-admin/edit-tags.php?taxonomy=spk_doc_cat&post_type=spk_document', cap: 'manage_options', custom: true },
    { key: 'pages', url: '/wp-admin/edit.php?post_type=page', cap: 'edit_pages' },
    { key: 'page-add', url: '/wp-admin/post-new.php?post_type=page', cap: 'manage_options' },
    p.home && { key: 'editor-home', url: edit(p.home), cap: `edit_post:${p.home}`, editor: true, unlock: true },
    p['how-it-works'] && { key: 'editor-how', url: edit(p['how-it-works']), cap: `edit_post:${p['how-it-works']}`, editor: true, unlock: true },
    p.about && { key: 'editor-about', url: edit(p.about), cap: `edit_post:${p.about}`, editor: true, unlock: true },
    p.members && { key: 'editor-members', url: edit(p.members), cap: `edit_post:${p.members}`, editor: true, unlock: true },
    { key: 'posts', url: '/wp-admin/edit.php', cap: 'manage_options' },
    { key: 'post-add', url: '/wp-admin/post-new.php', cap: 'manage_options' },
    // Categories and tags (posts are not used; PLAN §4.1: editors lack manage_categories).
    { key: 'categories', url: '/wp-admin/edit-tags.php?taxonomy=category', cap: 'manage_options' },
    { key: 'tags', url: '/wp-admin/edit-tags.php?taxonomy=post_tag', cap: 'manage_options' },
    { key: 'pattern-categories', url: '/wp-admin/edit-tags.php?taxonomy=wp_pattern_category&post_type=wp_block', cap: 'manage_options' },
    // Synced patterns: none for non-admins (PLAN §4.3 layer 6).
    { key: 'patterns', url: '/wp-admin/edit.php?post_type=wp_block', cap: 'manage_options' },
    { key: 'media', url: '/wp-admin/upload.php', cap: 'manage_options' },
    { key: 'media-add', url: '/wp-admin/media-new.php', cap: 'manage_options' },
    // The old uploader window: editors add photos through the block editor only (PLAN §4.1).
    { key: 'media-upload', url: '/wp-admin/media-upload.php', cap: 'manage_options' },
    // Edit Media for the Home hero, which an administrator uploaded: no editor
    // edits or deletes another user's file (PLAN §4.1, §5.5).
    media.hero && { key: 'media-edit-hero', url: edit(media.hero), cap: 'manage_options' },
    { key: 'comments', url: '/wp-admin/edit-comments.php', cap: 'manage_options' },
    { key: 'users', url: '/wp-admin/users.php', cap: 'list_users' },
    { key: 'user-new', url: '/wp-admin/user-new.php', cap: 'create_users' },
    other && { key: 'user-edit-other', url: `/wp-admin/user-edit.php?user_id=${other}`, cap: `edit_user:${other}` },
    { key: 'site-editor', url: '/wp-admin/site-editor.php', cap: 'edit_theme_options', siteEditor: true },
    { key: 'themes', url: '/wp-admin/themes.php', cap: 'switch_themes' },
    { key: 'customize', url: '/wp-admin/customize.php', cap: 'customize' },
    // A block theme has no Widgets or Menus screen: core refuses both for
    // everyone, administrators included ('do_not_allow' is never granted).
    { key: 'widgets', url: '/wp-admin/widgets.php', cap: 'do_not_allow', note: 'block theme: core refuses this screen for everyone' },
    { key: 'nav-menus', url: '/wp-admin/nav-menus.php', cap: 'do_not_allow', note: 'block theme: core refuses this screen for everyone' },
    { key: 'plugins', url: '/wp-admin/plugins.php', cap: 'activate_plugins' },
    { key: 'tools', url: '/wp-admin/tools.php', cap: 'manage_options' },
    { key: 'site-health', url: '/wp-admin/site-health.php', cap: 'view_site_health_checks' },
    { key: 'export', url: '/wp-admin/export.php', cap: 'export' },
    { key: 'import', url: '/wp-admin/import.php', cap: 'import' },
    { key: 'options-general', url: '/wp-admin/options-general.php', cap: 'manage_options' },
  ];
  return list.filter(Boolean);
}

/** The plugin's admin-post.php actions, tried by GET without a nonce: every role must be refused. */
function postActions(I) {
  const ev = I.events || {};
  const doc = I.documents || {};
  return [
    ['spokares_save_rota'], ['spokares_undo_rota'], ['spokares_save_net_details'], ['spokares_save_meetings'],
    ['spokares_save_meeting_rules'], ['spokares_save_tiles'], ['spokares_save_site'],
    ['spokares_pull_file', doc['ics-213'] ? `&post=${doc['ics-213']}` : ''],
    ['spokares_duplicate_event', ev['set-2026'] ? `&post=${ev['set-2026']}` : ''],
  ].map(([action, extra]) => ({ key: `post-${action.replace(/^spokares_/, '')}`, url: `/wp-admin/admin-post.php?action=${action}${extra || ''}`, kind: 'forbidden', source: 'admin-post', noShotOnLogin: true }));
}

/** Read-only REST probes and the capability each needs. */
function restProbes(I) {
  const p = I.pages;
  return [
    { key: 'rest-users-me', route: '/wp/v2/users/me', cap: 'read' },
    { key: 'rest-users', route: '/wp/v2/users?context=edit', cap: 'list_users' },
    { key: 'rest-settings', route: '/wp/v2/settings', cap: 'manage_options' },
    { key: 'rest-pages-edit', route: '/wp/v2/pages?context=edit&per_page=20', cap: 'edit_pages' },
    p.home && { key: 'rest-home-edit', route: `/wp/v2/pages/${p.home}?context=edit`, cap: `edit_post:${p.home}` },
    p.members && { key: 'rest-members-edit', route: `/wp/v2/pages/${p.members}?context=edit`, cap: `edit_post:${p.members}` },
    // Core lets anyone who can edit posts READ templates, parts, global styles
    // and navigation (the post editor needs them); writing needs edit_theme_options.
    { key: 'rest-templates', route: '/wp/v2/templates?context=edit', cap: 'edit_posts' },
    { key: 'rest-template-parts', route: '/wp/v2/template-parts?context=edit', cap: 'edit_posts' },
    { key: 'rest-global-styles', route: '/wp/v2/global-styles/themes/spokares?context=edit', cap: 'edit_posts' },
    { key: 'rest-navigation', route: '/wp/v2/navigation?context=edit', cap: 'edit_posts' },
    { key: 'rest-plugins', route: '/wp/v2/plugins', cap: 'activate_plugins' },
    { key: 'rest-media-edit', route: '/wp/v2/media?context=edit', cap: 'edit_posts' },
    { key: 'rest-posts-drafts', route: '/wp/v2/posts?status=draft&context=edit', cap: 'edit_posts' },
    // Write probe: creating a synced pattern is for administrators only (PLAN
    // §4.3 layer 6). A pattern the probe manages to create is deleted at once.
    {
      key: 'rest-blocks-create',
      route: '/wp/v2/blocks',
      method: 'POST',
      body: { title: `QA crawl probe ${Date.now()} (delete me)`, status: 'draft', content: '<!-- wp:paragraph --><p>QA crawl probe</p><!-- /wp:paragraph -->' },
      cleanup: '/wp/v2/blocks',
      cap: 'manage_options',
    },
  ].filter(Boolean);
}

/**
 * Site-specific checks on some screens for users who are not administrators:
 * [selector, what, how]. how 'shown' (default) counts rendered matches; how
 * 'present' counts every match (a Bulk actions <option> is never rendered
 * until the list opens).
 */
const bulk = (value) => `select[name="action"] option[value="${value}"], select[name="action2"] option[value="${value}"]`;
const POLICY = {
  pages: [
    ['.row-actions .trash a', 'offers Trash on the fixed pages'],
    ['a.page-title-action', 'offers "Add New Page"'],
    ['.row-actions .inline', 'offers Quick Edit on pages'],
    [bulk('trash'), 'offers "Move to Trash" in Bulk actions on the fixed pages (PLAN §4.1)', 'present'],
    [bulk('edit'), 'offers bulk Edit on pages (PLAN §4.3)', 'present'],
  ],
  events: [
    ['.row-actions .inline', 'offers Quick Edit on events'],
    [bulk('edit'), 'offers bulk Edit on events (PLAN §3.4)', 'present'],
  ],
  documents: [
    ['.row-actions .inline', 'offers Quick Edit on documents'],
    [bulk('edit'), 'offers bulk Edit on documents (PLAN §3.4)', 'present'],
    [bulk('trash'), 'offers "Move to Trash" in Bulk actions on documents (PLAN §3.4: trash from the edit screen, where the file box is)', 'present'],
  ],
};

/* ------------------------------------------------------------------ crawl */

const severityOf = { error: 0, warning: 1, info: 2 };

class RoleCrawl {
  constructor(roleKey, I) {
    this.roleKey = roleKey;
    this.acct = account(roleKey);
    this.I = I;
    this.dir = path.join(SHOTS, roleKey);
    this.records = [];
    this.seen = new Set();
    this.names = new Set();
    this.anchorRefs = [];
    this.pageIds = new Map();
    this.adminFound = new Map(); // norm -> source
    this.httpFound = new Map(); // norm -> source
    this.loginShot = false;
  }

  async open() {
    if (this.browser) await this.browser.close().catch(() => {});
    this.browser = await Browser.launch();
    const context = await this.browser.newContext();
    this.page = await this.browser.newPage({ context, viewport: DESKTOP });
    if (this.acct.login) await withTimeout(loginAs(this.page, BASE, this.roleKey), 60000, `sign in as ${this.acct.login}`);
  }

  /**
   * Restart Chrome (and sign in again) if it has died. Throws when it cannot
   * be restarted: the role then stops, and the crawl exits 1.
   */
  async ensureBrowser() {
    if (this.browser && !this.browser.closed) return;
    log(`  ! Chrome is gone: restarting it${this.acct.login ? ` and signing in again as ${this.acct.login}` : ''}`);
    try {
      await this.open();
    } catch (err) {
      throw new BrowserClosedError(`Chrome could not be restarted: ${err.message}`);
    }
  }

  async close() { if (this.browser) await this.browser.close().catch(() => {}); }

  shotName(e) {
    let n = e.kind === 'forbidden' ? `forbidden-${e.key || slugFor(e.url)}` : (e.key || slugFor(e.url));
    let i = 2;
    const stem = n;
    while (this.names.has(n)) n = `${stem}-${i++}`;
    this.names.add(n);
    return n;
  }

  async run() {
    if (!MERGE) fs.rmSync(this.dir, { recursive: true, force: true });
    fs.mkdirSync(this.dir, { recursive: true });
    await this.open();
    const screens = adminScreens(this.I, this.roleKey);
    const probes = restProbes(this.I);
    const caps = [...new Set([...screens.map((s) => s.cap), ...probes.map((p) => p.cap), 'edit_theme_options', 'manage_options'])];
    this.me = this.acct.login
      ? await whoami(this.page, BASE, caps)
      : { id: 0, login: '', roles: [], caps: [], can: Object.fromEntries(caps.map((c) => [c, false])) };
    this.isAdminUser = !!this.me.can.manage_options;
    this.layoutLocked = !this.me.can.edit_theme_options;
    log(`\n== ${this.roleKey} (${this.acct.login || 'signed out'}; roles ${this.me.roles.join(', ') || 'none'})`);

    // 1. Public pages, breadth first from the seeds.
    const queue = PUBLIC_SEEDS.filter((s) => !s.anonOnly || !this.acct.login).map((s) => ({ ...s, kind: 'public', source: 'seed' }));
    let publicCount = 0;
    while (queue.length && publicCount < MAX_PUBLIC) {
      const e = queue.shift();
      const n = norm(e.url);
      if (this.seen.has(n)) continue;
      this.seen.add(n);
      publicCount++;
      const facts = await this.visit(e);
      if (!facts) continue;
      for (const href of facts.links || []) {
        const hn = norm(href);
        if (!hn) continue;
        const h = new URL(href);
        if (h.hash.length > 1) this.anchorRefs.push({ from: e.url, page: hn, id: decodeURIComponent(h.hash.slice(1)) });
        if (skipLink(href)) continue;
        if (isDocsLink(href) || isFileLink(href)) { if (!this.httpFound.has(hn)) this.httpFound.set(hn, `link on ${e.url}`); continue; }
        if (isAdminLink(href)) { if (!this.adminFound.has(hn)) this.adminFound.set(hn, `link on ${e.url}`); continue; }
        if (!this.seen.has(hn) && !queue.some((q) => norm(q.url) === hn)) queue.push({ url: hn, kind: 'public', source: `link on ${e.url}` });
      }
      for (const href of facts.adminBar || []) {
        const hn = norm(href);
        if (!hn || skipLink(href)) continue;
        if (isAdminLink(href)) { if (!this.adminFound.has(hn)) this.adminFound.set(hn, 'admin bar'); } else if (!this.seen.has(hn) && !queue.some((q) => norm(q.url) === hn)) queue.push({ url: hn, kind: 'public', source: 'admin bar' });
      }
    }

    // 2. /docs/<slug>/ and file links: HTTP only.
    for (const [u, expect] of [['/docs/ics-213/', 302], ['/docs/ncs-principles/', 404], ['/docs/qa-no-such-document/', 404]]) {
      if (!this.httpFound.has(u)) this.httpFound.set(u, `seed (expect ${expect})`);
    }
    for (const [u, source] of [...this.httpFound].slice(0, 80)) await this.httpCheck(u, source);

    // 3. Admin screens and 4. forbidden attempts.
    const admin = [];
    const forbidden = [];
    for (const s of screens) {
      const can = !!this.me.can[s.cap];
      (can ? admin : forbidden).push({ ...s, kind: can ? 'admin' : 'forbidden', source: `known screen (${s.cap})` });
    }
    let adminCount = 0;
    const doAdmin = async (e) => {
      const n = norm(e.url);
      if (this.seen.has(n) || adminCount >= MAX_ADMIN) return;
      this.seen.add(n);
      adminCount++;
      const facts = await this.visit(e);
      if (!facts) return;
      if (e.key === 'dashboard' || e.source === 'admin menu') {
        for (const href of facts.adminMenu || []) {
          const hn = norm(href);
          if (hn && !skipLink(href) && !this.adminFound.has(hn)) this.adminFound.set(hn, 'admin menu');
        }
      }
      if (e.key === 'dashboard') {
        for (const href of [...(facts.links || []), ...(facts.adminBar || [])]) {
          const hn = norm(href);
          if (hn && isAdminLink(href) && !skipLink(href) && !this.adminFound.has(hn)) this.adminFound.set(hn, 'dashboard');
        }
      }
    };
    if (this.acct.login && MATCH.length && !wanted('dashboard', '/wp-admin/')) {
      // --match skipped the Dashboard: still read its menu, so matching menu links are found.
      await this.ensureBrowser();
      try {
        await this.page.goto(`${BASE}/wp-admin/`, { timeout: 60000 });
        const f = await this.page.evaluate(domFacts, { isPublic: false, vw: DESKTOP.width });
        for (const href of [...(f.adminMenu || []), ...(f.links || []), ...(f.adminBar || [])]) {
          const hn = norm(href);
          if (hn && isAdminLink(href) && !skipLink(href) && !this.adminFound.has(hn)) this.adminFound.set(hn, 'admin menu');
        }
      } catch (err) { log(`  ! could not read the admin menu: ${err.message}`); }
    }
    if (this.acct.login) {
      for (const e of admin) await doAdmin(e);
      for (const [u, source] of this.adminFound) {
        if (this.seen.has(u)) continue;
        await doAdmin({ url: u, kind: 'admin', source, editor: /\/post\.php\?.*action=edit/.test(u), unlock: /\/post\.php\?/.test(u) });
      }
    }
    for (const e of forbidden) {
      const n = norm(e.url);
      if (this.seen.has(n)) continue;
      this.seen.add(n);
      await this.visit({ ...e, noShotOnLogin: true });
    }
    for (const e of postActions(this.I)) await this.visit(e);
    await this.restChecks(probes);

    // 5. Anchors: every #fragment link must name an id on its page.
    for (const ref of this.anchorRefs) {
      const ids = this.pageIds.get(ref.page);
      if (!ids || ids.has(ref.id)) continue;
      const rec = this.records.find((r) => r.url === ref.from);
      if (rec && !rec.findings.some((f) => f.type === 'broken-anchor' && f.detail === `${ref.page}#${ref.id}`)) {
        rec.findings.push({ type: 'broken-anchor', severity: 'error', message: `links to ${ref.page}#${ref.id}, but that page has no element with id "${ref.id}"`, detail: `${ref.page}#${ref.id}` });
      }
    }
    const exIds = this.pageIds.get('/members/exercises/');
    const exRec = this.records.find((r) => r.url === '/members/exercises/');
    if (exIds && exRec) {
      // Only published events are listed: a Draft (or Pending, Private,
      // Scheduled) event has no row, so it is not "missing".
      const status = this.I.status?.events || {};
      const missing = Object.keys(this.I.events || {}).filter((slug) => !slug.startsWith('past-') && (status[slug] || 'publish') === 'publish' && !exIds.has(slug));
      if (missing.length) exRec.findings.push({ type: 'event-anchor', severity: 'info', message: `${missing.length} published upcoming or undated event(s) have no #anchor on the page (not listed, or listed without an id): ${missing.join(', ')}` });
    }
    await this.close();
    return this.records;
  }

  /**
   * Visit with a time limit; on trouble (a hang, or Chrome dying) restart
   * Chrome, sign in again and retry once. A Chrome that cannot be restarted
   * stops the role (the error goes up to the main loop) instead of turning
   * every later URL into a false error.
   */
  async visit(e) {
    if (!wanted(e.key, e.url)) return null;
    const limit = e.editor || e.siteEditor ? 180000 : 100000;
    for (let attempt = 1; attempt <= 2; attempt++) {
      await this.ensureBrowser();
      try {
        return await withTimeout(this.visitOnce(e), limit, `visit ${e.url}`);
      } catch (err) {
        log(`  ! ${e.url}: ${err.message}${attempt === 1 ? ' (restarting Chrome, retrying)' : ''}`);
        try { await this.open(); } catch (err2) { throw new Error(`Chrome could not be restarted after ${e.url} failed (${err.message}): ${err2.message}`); }
        if (attempt === 2) {
          this.records.push({
            role: this.roleKey, url: e.url, key: e.key || null, kind: e.kind, source: e.source, status: null, finalUrl: null,
            redirects: [], title: '', access: 'error', shots: {}, ms: 0,
            findings: [{ type: 'crawl', severity: 'error', message: `could not be visited: ${err.message}` }],
          });
          return null;
        }
      }
    }
    return null;
  }

  async visitOnce(e) {
    const page = this.page;
    const rec = {
      role: this.roleKey, url: e.url, key: e.key || null, kind: e.kind, source: e.source,
      expect: e.kind === 'forbidden' ? 'denied' : `HTTP ${e.expectStatus || 200}`,
      status: null, finalUrl: null, redirects: [], title: '', access: null, shots: {}, ms: 0, findings: [],
    };
    const add = (type, severity, message, extra = {}) => rec.findings.push({ type, severity, message, ...extra });
    await page.goto('about:blank', { idle: false, timeout: 15000 });
    if (e.unlock) await unlockPosts(BASE);
    page.resetCapture();
    const marks = LOGS.map((l) => l.mark());
    const t0 = Date.now();
    const nav = await page.goto(BASE + e.url, { timeout: 60000 });
    rec.status = nav.status;
    rec.finalUrl = rel(nav.url);
    rec.redirects = nav.chain.map((c) => ({ url: rel(c.url), status: c.status }));
    if (nav.timedOut) add('timeout', 'error', 'the page did not finish loading within 60 s');
    if (nav.error && nav.error !== 'net::ERR_ABORTED') add('navigation', 'error', `navigation error ${nav.error}`);

    let facts = null;
    let be = null;
    try {
      const kind = await page.evaluate(() => ({ be: !!document.body?.classList.contains('block-editor-page'), se: !!document.body?.classList.contains('site-editor-php') }));
      if (kind.be) be = await page.evaluate(blockEditorFacts, 90000);
      else if (kind.se) rec.siteEditor = await page.evaluate(siteEditorFacts, 60000);
      else await page.loadImages();
      await page.waitForIdle({ idleMs: 300, timeout: 5000 });
      facts = await page.evaluate(domFacts, { isPublic: e.kind === 'public', vw: DESKTOP.width });
    } catch (err) {
      add('inspect', 'error', `could not inspect the page: ${err.message}`);
    }
    rec.ms = Date.now() - t0;
    if (facts) rec.title = facts.title;

    // Access.
    rec.access = this.classify(e, nav, facts);
    if (facts?.isErrorPage) rec.denial = facts.dieText;
    this.accessFindings(e, rec, facts, add);

    // Screenshot (1440; full page except the editors, which fill the window).
    const name = this.shotName(e);
    const onLogin = facts?.isLoginPage && e.kind === 'forbidden';
    if (onLogin && e.noShotOnLogin && this.loginShot) {
      rec.shots.note = 'sign-in page (captured once per role)';
    } else {
      if (onLogin) this.loginShot = true;
      try {
        const s = await page.screenshot(path.join(this.dir, `${name}-desktop.png`), { fullPage: !(be || rec.siteEditor) });
        rec.shots.desktop = s.file;
        rec.height = s.height;
      } catch (err) { add('screenshot', 'warning', `desktop screenshot failed: ${err.message}`); }
    }

    // Findings from the page.
    if (facts) this.pageFindings(e, rec, facts, add, 'desktop');
    if (be) this.editorFindings(rec, be, add);
    if (rec.siteEditor && !rec.siteEditor.ready) add('site-editor', 'error', 'the Site Editor did not finish loading within 60 s');
    for (const n of rec.siteEditor?.notices || []) add('editor-notice', 'warning', `Site Editor error notice: ${n}`);
    if (facts && POLICY[e.key] && !this.isAdminUser && rec.access === 'ok') {
      for (const [sel, what, how = 'shown'] of POLICY[e.key]) {
        const n = await page.evaluate((s, all) => [...document.querySelectorAll(s)].filter((el) => all || el.getClientRects().length).length, sel, how === 'present').catch(() => 0);
        if (n) add('policy', 'error', `${this.roleKey} ${what} (${n} × ${sel})`);
      }
    }
    // The page card's ⋮ Actions menu in the block editor (7.1.2 lists View,
    // Rename, Order and, with delete rights, Move to trash): the page's order
    // is fixed for non-admins and no editor deletes a page (PLAN §4.1, §4.3).
    if (be?.ready && be.postType === 'page' && this.layoutLocked && rec.access === 'ok') {
      const items = await this.pageCardActions(page).catch(() => null);
      rec.blockEditor.cardActions = items;
      if (!items) add('editor-card', 'info', 'could not open the page card\'s ⋮ Actions menu to check it');
      else {
        if (items.some((x) => /^order$/i.test(x))) add('editor-card', 'error', `the page card's ⋮ menu offers Order to ${this.roleKey} (the server keeps the stored order, so it says "Order updated." and changes nothing; PLAN §4.3): ${items.join(', ')}`);
        if (items.some((x) => /trash|delete/i.test(x))) add('editor-card', 'error', `the page card's ⋮ menu offers to trash or delete the page to ${this.roleKey} (PLAN §4.1: no editor deletes a page): ${items.join(', ')}`);
      }
    }
    if (facts && e.kind === 'public') this.pageIds.set(norm(nav.url) || norm(e.url), new Set(facts.ids));

    // Phone layout for public pages.
    if (e.kind === 'public' && MOBILE && facts && !nav.timedOut) {
      try {
        await page.setViewport(PHONE);
        await page.goto(BASE + e.url, { timeout: 60000 });
        await page.loadImages();
        const m = await page.evaluate(domFacts, { isPublic: true, vw: PHONE.width });
        const s = await page.screenshot(path.join(this.dir, `${name}-mobile-390.png`), { fullPage: true });
        rec.shots.mobile = s.file;
        rec.mobileHeight = s.height;
        this.pageFindings(e, rec, m, add, 'mobile');
      } catch (err) {
        add('mobile', 'warning', `phone view failed: ${err.message}`);
      } finally {
        await page.setViewport(DESKTOP).catch(() => {});
      }
    }

    // Browser-side problems collected across both loads.
    const dedupe = (arr, keyFn) => [...new Map(arr.map((x) => [keyFn(x), x])).values()];
    for (const x of dedupe(page.exceptions, (x) => x.text)) add('js-exception', 'error', `uncaught: ${x.text}`, { detail: x.url });
    for (const x of dedupe(page.consoleErrors, (x) => x.text)) add('console-error', 'warning', `console error: ${x.text}`, { detail: x.url });
    for (const x of dedupe(page.failedRequests, (x) => x.url)) {
      const same = norm(x.url) !== null;
      if (/\/favicon\.ico$/.test(x.url)) { add('network', 'info', `favicon.ico ${x.status || x.error}`); continue; }
      // Same-origin failures are errors on public pages and the plugin's own
      // screens, warnings on core admin screens (often core probing for
      // something that isn't installed).
      const coreScreen = e.kind !== 'public' && !e.custom && !/spokares|spk_/.test(e.url);
      add(same ? 'network' : 'network-external', same && !coreScreen ? 'error' : 'warning', `${x.status ? `HTTP ${x.status}` : x.error} for ${x.type || 'request'} ${rel(x.url)}`);
    }
    for (const d of page.dialogs) add('dialog', 'warning', `JavaScript ${d.type} dialog: "${d.message}" (accepted)`);

    // New PHP messages in the logs since this URL started.
    const php = [...new Set(LOGS.flatMap((l, i) => l.phpSince(marks[i])))];
    for (const line of php.slice(0, 15)) {
      const sev = /Fatal|Parse error|Warning|database error/.test(line) ? 'error' : 'warning';
      add('php', sev, line.replace(/^\[[^\]]+\]\s*/, '').slice(0, 400));
    }
    if (php.length > 15) add('php', 'warning', `${php.length - 15} more PHP messages`);

    // Chrome died during this visit: what was recorded is about the dead
    // browser, not the page. Throw, so visit() restarts Chrome and retries.
    if (page.browser.closed) {
      this.names.delete(name); // the retry takes the same screenshot name
      throw new BrowserClosedError(`Chrome went away while visiting ${e.url}`);
    }

    rec.findings.sort((a, b) => severityOf[a.severity] - severityOf[b.severity]);
    this.records.push(rec);
    const counts = ['error', 'warning'].map((s) => rec.findings.filter((f) => f.severity === s).length);
    log(`  ${String(rec.status).padEnd(3)} ${rec.kind.padEnd(9)} ${rec.access.padEnd(10)} ${e.url}${rec.finalUrl && norm(rec.finalUrl) !== norm(e.url) ? ` → ${rec.finalUrl}` : ''}${counts[0] || counts[1] ? `  [${counts[0]} errors, ${counts[1]} warnings]` : ''}`);
    return facts;
  }

  /**
   * Open the page card's ⋮ Actions menu in the block editor's Page sidebar
   * (opening the sidebar first if it is closed), read its items and close it
   * again. Saves nothing. Returns the item labels, or null if it didn't open.
   */
  async pageCardActions(page) {
    const card = '.editor-post-card-panel .editor-all-actions-button';
    const shown = await page.evaluate(async (sel) => {
      const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
      const vis = () => { const el = document.querySelector(sel); if (!el) return false; const r = el.getBoundingClientRect(); return r.width > 0 && r.height > 0; };
      if (!vis()) {
        try {
          const ep = wp.data.dispatch('core/edit-post');
          if (ep && typeof ep.openGeneralSidebar === 'function') ep.openGeneralSidebar('edit-post/document');
          else wp.data.dispatch('core/interface').enableComplementaryArea('core', 'edit-post/document');
        } catch (e) {}
        for (let i = 0; i < 40 && !vis(); i++) await sleep(250);
      }
      return vis();
    }, card);
    if (!shown) return null;
    await page.click(card, { timeout: 5000 });
    const items = await page.waitForFunction(() => {
      const list = [...document.querySelectorAll('[role="menu"] [role="menuitem"]')].filter((el) => !el.closest('#wpadminbar')).map((el) => el.innerText.trim()).filter(Boolean);
      return list.length ? list : null;
    }, { timeout: 10000 }).catch(() => null);
    await page.press('Escape').catch(() => {});
    return items;
  }

  /** ok | denied | login | redirected | not-found | error */
  classify(e, nav, facts) {
    const status = nav.status;
    if (!status && !facts) return 'error';
    const landed = norm(nav.url);
    if (facts?.criticalError) return 'error';
    if (facts?.isLoginPage && !/wp-login\.php/.test(e.url)) return 'login';
    // A wp_die() page is a controlled refusal whatever its status (core
    // answers some refusals with 500); a critical error is caught above.
    if (facts?.isErrorPage || status === 401 || status === 403) return 'denied';
    if (status >= 500) return 'error';
    if (status === 400) return 'denied';
    if (status === 404) return 'not-found';
    if (landed !== norm(e.url)) {
      const want = new URL(e.url, BASE);
      const got = new URL(nav.url, BASE);
      const same = want.pathname.replace(/index\.php$/, '') === got.pathname.replace(/index\.php$/, '')
        && ['page', 'post', 'post_type', 'taxonomy', 'user_id', 's'].every((k) => want.searchParams.get(k) === got.searchParams.get(k));
      if (!same) return 'redirected';
    }
    return 'ok';
  }

  accessFindings(e, rec, facts, add) {
    const where = rec.finalUrl && norm(rec.finalUrl) !== norm(e.url) ? ` (landed on ${rec.finalUrl})` : '';
    if (e.kind === 'forbidden') {
      if (rec.access === 'ok') add('access-leak', 'error', `${this.roleKey} can open ${e.url}, which needs ${e.cap || 'a nonce'} (HTTP ${rec.status}, "${rec.title}")`);
      else if (rec.access === 'error') add('access-error', 'error', `refusing ${e.url} failed with an error (HTTP ${rec.status})${facts?.dieText ? `: ${facts.dieText}` : ''}`);
      // A refusal sent as HTTP 500: a warning on the plugin's own screens,
      // info on core's (core answers some refusals that way).
      else if (rec.status >= 500) add('denial-status', e.custom || /spokares|spk_/.test(e.url) ? 'warning' : 'info', `denied with HTTP ${rec.status} instead of 403${facts?.dieText ? `: ${facts.dieText.slice(0, 120)}` : ''}`);
      else if (rec.access === 'not-found' && this.acct.login) add('denial-status', 'info', `denied as a 404${where}`);
      return;
    }
    if (e.kind === 'admin') {
      if (rec.access === 'denied' || rec.access === 'login') add('access-denied', 'error', `${this.roleKey} ${e.source.startsWith('known') ? `has ${e.cap}` : `was offered ${e.url} (${e.source})`} but is refused (HTTP ${rec.status})${facts?.dieText ? `: ${facts.dieText.slice(0, 160)}` : ''}`);
      else if (rec.access === 'error') add('http-error', 'error', `HTTP ${rec.status}${facts?.criticalError ? ' (WordPress critical error page)' : ''}`);
      else if (rec.access === 'not-found') add('http-status', 'error', `HTTP 404 for a screen the role was offered (${e.source})`);
      else if (rec.access === 'redirected') add('access-redirect', 'warning', `expected ${e.url}${where}`);
      return;
    }
    // public
    const want = e.expectStatus || 200;
    if (rec.status !== want) add('http-status', rec.status >= 500 || want === 200 ? 'error' : 'warning', `HTTP ${rec.status}, expected ${want}${where}`);
    if (facts?.criticalError) add('http-error', 'error', 'WordPress critical error page');
  }

  pageFindings(e, rec, f, add, view) {
    const custom = !!e.custom || /spokares|spk_/.test(e.url);
    const loud = e.kind === 'public' || custom;
    const tag = view === 'mobile' ? ' at 390 px' : '';
    if (f.overflow && f.overflow.scrollWidth > f.overflow.viewport + 1) {
      add('overflow', e.kind === 'public' ? 'error' : 'warning', `page scrolls sideways${tag}: ${f.overflow.scrollWidth} px wide in a ${f.overflow.viewport} px window${f.overflow.offenders.length ? `; starts at ${f.overflow.offenders.map((o) => `${o.el} (right ${o.right})`).join('; ')}` : ''}`, { view });
    }
    // Content running out of its column or cell (over its neighbours), which
    // the page-width check above can't see.
    if (f.innerOverflow?.length) {
      const list = f.innerOverflow.slice(0, 4).map((o) => `${o.child} sticks out of ${o.el} (${o.width} px wide) by ${o.by} px`);
      add('overflow-inner', loud ? 'warning' : 'info', `content wider than its box${tag}: ${list.join('; ')}${f.innerOverflow.length > 4 ? `; +${f.innerOverflow.length - 4} more` : ''}`, { view });
    }
    if (view === 'mobile') return; // the rest is the same at both widths
    if (e.kind === 'public' && rec.access === 'ok') {
      if (f.h1.length === 0) add('a11y-h1', 'warning', 'no <h1>');
      else if (f.h1.length > 1) add('a11y-h1', 'warning', `${f.h1.length} <h1> elements: ${f.h1.map((h) => `"${h}"`).join(', ')}`);
      if (!f.hasMain) add('a11y-landmark', 'warning', 'no <main> landmark');
      if (!f.lang) add('a11y-lang', 'warning', '<html> has no lang');
      if (f.hrefHash) add('dead-link', 'warning', `${f.hrefHash} link(s) with href="#"`);
    }
    const sev = loud ? 'warning' : 'info';
    const list = (arr) => `${arr.slice(0, 6).join('; ')}${arr.length > 6 ? `; +${arr.length - 6} more` : ''}`;
    if (f.a11y.imgNoAlt.length) add('a11y-img-alt', sev, `${f.a11y.imgNoAlt.length} image(s) without alt: ${list(f.a11y.imgNoAlt)}`);
    if (f.a11y.emptyLinks.length) add('a11y-empty-link', sev, `${f.a11y.emptyLinks.length} link(s) with no accessible name: ${list(f.a11y.emptyLinks)}`);
    if (f.a11y.emptyButtons.length) add('a11y-empty-button', sev, `${f.a11y.emptyButtons.length} button(s) with no accessible name: ${list(f.a11y.emptyButtons)}`);
    if (f.a11y.unlabeled.length) add('a11y-label', sev, `${f.a11y.unlabeled.length} form field(s) without a label: ${list(f.a11y.unlabeled)}`);
    if (f.a11y.duplicateIds.length) add('a11y-duplicate-id', loud ? 'warning' : 'info', `duplicate ids: ${list(f.a11y.duplicateIds)}`);
    for (const n of f.notices || []) {
      if (n.type === 'error') add('admin-notice', 'warning', `error notice: ${n.text}`);
      else if (n.type === 'warning') add('admin-notice', 'info', `warning notice: ${n.text}`);
    }
    if (rec.access === 'ok' && f.bodyTextLength < 20 && !f.isBlockEditor && !f.isSiteEditor && /html/.test(f.contentType)) add('blank', 'error', 'the page is (nearly) blank');
  }

  editorFindings(rec, be, add) {
    rec.blockEditor = be;
    if (be.lockedModal) add('post-locked', 'warning', 'the editor opened with the "someone else is editing" dialog');
    if (!be.ready) { add('block-editor', 'error', `the block editor did not load its blocks within 90 s`); return; }
    if (be.invalid?.length) add('block-invalid', 'error', `invalid blocks: ${be.invalid.join(', ')}`);
    if (be.warnings) add('block-warning', 'error', `${be.warnings} block warning(s): ${be.warningTexts.join(' | ')}`);
    for (const n of be.notices || []) add('editor-notice', n.status === 'error' ? 'error' : 'warning', `editor ${n.status} notice: ${n.text}`);
    if (this.layoutLocked && be.postType === 'page') {
      if (be.templateLock !== 'contentOnly') add('layout-lock', 'error', `templateLock is ${JSON.stringify(be.templateLock)}, expected "contentOnly" for a user without edit_theme_options`);
      if (be.codeEditingEnabled) add('layout-lock', 'error', 'the code editor is enabled for a user without edit_theme_options');
      if (be.canLockBlocks) add('layout-lock', 'warning', 'canLockBlocks is on for a user without edit_theme_options');
      if (be.inserterVisible) add('layout-lock', 'warning', 'the block inserter button is visible for a user without edit_theme_options');
      if (be.defaultModeBlocks?.length) add('layout-lock', 'warning', `${be.defaultModeBlocks.length} page block(s) in the default (full) editing mode: ${[...new Set(be.defaultModeBlocks)].slice(0, 8).join(', ')}`);
      if (be.welcomeGuide) add('editor-ui', 'warning', 'the Welcome Guide opened for a non-administrator (PLAN §4.2 turns it off)');
    } else if (be.welcomeGuide) {
      add('editor-ui', 'info', 'the Welcome Guide opened');
    }
  }

  /** /docs/ and file links: status and Location over HTTP with the role's cookies. */
  async httpCheck(u, source) {
    if (!wanted('', u)) return;
    const rec = { role: this.roleKey, url: u, key: null, kind: 'public', source: `http check (${source})`, status: null, finalUrl: null, redirects: [], title: '', access: null, shots: {}, ms: 0, findings: [] };
    const expect = (source.match(/expect (\d+)/) || [])[1];
    if (this.acct.login) await this.ensureBrowser(); // the cookies come from Chrome
    const t0 = Date.now();
    const marks = LOGS.map((l) => l.mark());
    try {
      const cookie = this.acct.login ? await this.page.cookieHeader(BASE + '/') : '';
      const r = await fetch(BASE + u, { redirect: 'manual', headers: cookie ? { cookie } : {}, signal: AbortSignal.timeout(30000) });
      rec.status = r.status;
      const loc = r.headers.get('location');
      rec.finalUrl = loc ? rel(loc) : u;
      rec.access = r.status >= 500 ? 'error' : r.status >= 400 ? 'not-found' : r.status >= 300 ? 'redirected' : 'ok';
      if (expect) {
        if (String(r.status) !== expect) rec.findings.push({ type: 'http-status', severity: 'error', message: `HTTP ${r.status}, expected ${expect}` });
      } else if (r.status >= 400) {
        rec.findings.push({ type: 'broken-link', severity: 'error', message: `linked (${source}) but answers HTTP ${r.status}` });
      }
      if (loc && /^http:\/\//.test(loc) && norm(loc) === null) rec.findings.push({ type: 'insecure-redirect', severity: 'warning', message: `redirects to a plain http address: ${loc}` });
      await r.body?.cancel().catch(() => {});
    } catch (err) {
      rec.access = 'error';
      rec.findings.push({ type: 'http-check', severity: 'error', message: err.message });
    }
    rec.ms = Date.now() - t0;
    for (const line of [...new Set(LOGS.flatMap((l, i) => l.phpSince(marks[i])))].slice(0, 10)) {
      rec.findings.push({ type: 'php', severity: /Fatal|Parse error|Warning|database error/.test(line) ? 'error' : 'warning', message: line.replace(/^\[[^\]]+\]\s*/, '').slice(0, 400) });
    }
    this.records.push(rec);
    log(`  ${String(rec.status).padEnd(3)} http      ${rec.access.padEnd(10)} ${u}${rec.finalUrl !== u ? ` → ${rec.finalUrl}` : ''}${rec.findings.length ? `  [${rec.findings.length} findings]` : ''}`);
  }

  /**
   * REST requests with the role's cookies and a REST nonce: the read-only
   * probes, and write probes (method POST) that must be refused to every role
   * without the capability. Anything a write probe creates is deleted again
   * at once (force), and a copy left behind is reported.
   */
  async restChecks(probes) {
    const todo = probes.filter((p) => wanted(p.key, `/wp-json${p.route}`));
    const marks = LOGS.map((l) => l.mark());
    let nonce = '';
    const prepare = async () => {
      await this.ensureBrowser();
      await this.page.goto(`${BASE}${this.acct.login ? '/wp-admin/profile.php' : '/'}`, { timeout: 60000 }).catch((err) => { if (err instanceof BrowserClosedError) throw err; });
      nonce = '';
      if (this.acct.login) {
        nonce = await this.page.evaluate(async (u) => { const r = await fetch(u, { credentials: 'same-origin' }); return r.ok ? (await r.text()).trim() : ''; }, `${BASE}/wp-admin/admin-ajax.php?action=rest-nonce`).catch(() => '');
      }
    };
    const call = (route, method, body) => this.page.evaluate(async (u, n, m, b) => {
      const headers = n ? { 'X-WP-Nonce': n } : {};
      if (b) headers['Content-Type'] = 'application/json';
      const res = await fetch(u, { method: m, credentials: 'same-origin', headers, body: b ? JSON.stringify(b) : undefined });
      let data = null;
      try { data = await res.json(); } catch (e) {}
      return { status: res.status, items: Array.isArray(data) ? data.length : null, code: data && !Array.isArray(data) ? data.code || '' : '', id: data && !Array.isArray(data) && Number.isInteger(data.id) ? data.id : 0 };
    }, `${BASE}/wp-json${route}`, nonce, method, body || null);
    if (todo.length) await prepare();
    for (const p of todo) {
      const can = !!this.me.can[p.cap];
      const method = p.method || 'GET';
      const rec = { role: this.roleKey, url: `${method === 'GET' ? '' : `${method} `}/wp-json${p.route}`, key: p.key, kind: can ? 'admin' : 'forbidden', source: `REST ${method === 'GET' ? 'probe' : 'write probe'} (${p.cap})`, status: null, finalUrl: null, redirects: [], title: '', access: null, shots: {}, ms: 0, findings: [] };
      const t0 = Date.now();
      try {
        let r;
        try {
          r = await call(p.route, method, p.body);
        } catch (err) {
          if (!this.page.browser.closed) throw err;
          await prepare(); // Chrome died during the probe: restart, sign in again, once more
          r = await call(p.route, method, p.body);
        }
        rec.status = r.status;
        rec.finalUrl = rec.url;
        rec.restCode = r.code;
        rec.items = r.items;
        rec.access = r.status >= 500 ? 'error' : [401, 403, 404].includes(r.status) ? 'denied' : r.status >= 400 ? 'denied' : 'ok';
        const did = method === 'GET' ? 'reads' : `can ${method}`;
        if (!can && rec.access === 'ok' && r.items === 0) rec.findings.push({ type: 'rest-empty', severity: 'info', message: `${this.roleKey} gets an empty 200 list from ${p.route} without ${p.cap} (nothing exposed; a refusal would be 401/403)` });
        else if (!can && rec.access === 'ok') rec.findings.push({ type: 'access-leak', severity: 'error', message: `${this.roleKey} ${did} ${p.route} without ${p.cap} (HTTP ${r.status}${r.items !== null ? `, ${r.items} items` : ''}${r.id ? `, created #${r.id}` : ''})` });
        if (rec.access === 'error') rec.findings.push({ type: 'http-error', severity: 'error', message: `REST ${method} ${p.route} answered HTTP ${r.status} ${r.code}` });
        if (can && rec.access === 'denied') rec.findings.push({ type: 'rest-denied', severity: 'info', message: `${this.roleKey} has ${p.cap} but REST ${method} ${p.route} answered ${r.status} ${r.code}` });
        if (method !== 'GET' && r.id && p.cleanup) {
          const d = await call(`${p.cleanup}/${r.id}?force=true`, 'DELETE').catch((err) => ({ status: 0, code: err.message }));
          if (d.status !== 200) rec.findings.push({ type: 'probe-cleanup', severity: 'warning', message: `the write probe created #${r.id} and could not delete it (HTTP ${d.status} ${d.code}); delete it by hand` });
        }
      } catch (err) {
        if (err instanceof BrowserClosedError) throw err; // Chrome is gone for good: the role stops
        rec.access = 'error';
        rec.findings.push({ type: 'http-check', severity: 'error', message: err.message });
      }
      rec.ms = Date.now() - t0;
      this.records.push(rec);
      log(`  ${String(rec.status).padEnd(3)} rest      ${String(rec.access).padEnd(10)} ${method === 'GET' ? '' : `${method} `}${p.route}${rec.findings.length ? `  [${rec.findings.map((f) => f.type).join(', ')}]` : ''}`);
    }
    const php = [...new Set(LOGS.flatMap((l, i) => l.phpSince(marks[i])))];
    const last = this.records[this.records.length - 1];
    for (const line of php.slice(0, 10)) last?.findings.push({ type: 'php', severity: /Fatal|Parse error|Warning|database error/.test(line) ? 'error' : 'warning', message: `during the REST probes: ${line.replace(/^\[[^\]]+\]\s*/, '').slice(0, 400)}` });
  }
}

/* ---------------------------------------------------------------- summary */

function summarize(report, meta) {
  const L = [];
  const rel2 = (f) => path.relative(OUT, f).split(path.sep).join('/');
  const esc = (s) => String(s ?? '').replace(/\|/g, '\\|').replace(/\n/g, ' ');
  L.push(`# QA crawl: ${path.basename(OUT)}`);
  L.push('');
  L.push(`${meta.started} to ${meta.finished}, ${meta.base} (WordPress ${meta.wp}, PHP ${meta.php}, theme ${meta.theme}). Screenshots: \`${meta.shots}\`. Full data: \`report.json\` (role → list of URLs, each with its findings).`);
  L.push('');
  L.push('## By role');
  L.push('');
  L.push('| Role | Signs in as | URLs | public | admin | forbidden | errors | warnings | info |');
  L.push('|---|---|---:|---:|---:|---:|---:|---:|---:|');
  for (const [role, recs] of Object.entries(report)) {
    const a = account(role);
    const n = (k) => recs.filter((r) => r.kind === k).length;
    const s = (sev) => recs.reduce((t, r) => t + r.findings.filter((f) => f.severity === sev).length, 0);
    L.push(`| ${role} | ${a.login ? `\`?dev_login=${a.dev_login}\`` : 'nobody'} | ${recs.length} | ${n('public')} | ${n('admin')} | ${n('forbidden')} | ${s('error')} | ${s('warning')} | ${s('info')} |`);
  }
  L.push('');

  // Access matrix over the known screens, admin-post actions and REST probes.
  const roles = Object.keys(report);
  const keys = [];
  for (const recs of Object.values(report)) for (const r of recs) if (r.key && !keys.includes(r.key) && (r.kind !== 'public')) keys.push(r.key);
  L.push('## Access matrix');
  L.push('');
  L.push('Known screens, admin-post actions (no nonce), REST probes and the REST write probe (`POST …`). `ok` = the screen opened; `403`/`400`/`500` = refused (a `wp_die()` page or an error status); `login` = sent to the sign-in page; `→ x` = redirected; `200 (empty)` = a REST list answered with nothing in it; **LEAK** = opened although the role lacks the capability; **ERROR** = failed. A trailing `(!)` marks an outcome that does not match the role\'s capabilities. Widgets and Menus are refused for everyone: a block theme has neither.');
  L.push('');
  L.push(`| Screen | ${roles.join(' | ')} |`);
  L.push(`|---|${roles.map(() => '---').join('|')}|`);
  for (const k of keys) {
    const cells = roles.map((role) => {
      const r = report[role].find((x) => x.key === k && x.kind !== 'public');
      if (!r) return '';
      let c;
      if (r.kind === 'forbidden' && r.access === 'ok') c = r.findings.some((f) => f.type === 'access-leak') ? `**LEAK ${r.status}**` : `${r.status} (empty)`;
      else if (r.access === 'error') c = `**ERROR ${r.status ?? ''}**`;
      else if (r.access === 'ok') c = 'ok';
      else if (r.access === 'login') c = 'login';
      else if (r.access === 'redirected') c = `→ ${esc((r.finalUrl || '').replace(/^\/wp-admin\//, '').slice(0, 28))}`;
      else c = String(r.status);
      const bad = r.findings.some((f) => /^access-|^http-error/.test(f.type) && f.severity === 'error');
      return bad && !c.startsWith('**') ? `${c} (!)` : c;
    });
    const any = Object.values(report).flat().find((r) => r.key === k && r.kind !== 'public');
    L.push(`| ${k} <sub>${esc(any.url.slice(0, 60))}</sub> | ${cells.join(' | ')} |`);
  }
  L.push('');

  // Findings, grouped by type+message+url across roles.
  for (const sev of ['error', 'warning']) {
    const groups = new Map();
    for (const [role, recs] of Object.entries(report)) {
      for (const r of recs) {
        for (const f of r.findings.filter((x) => x.severity === sev)) {
          const msg = f.message.replace(new RegExp(`\\b${role}\\b`, 'g'), '<role>');
          const k = `${f.type}|${msg}|${r.url}`;
          if (!groups.has(k)) groups.set(k, { type: f.type, message: msg, url: r.url, roles: [], shot: r.shots.desktop || r.shots.mobile || '' });
          const g = groups.get(k);
          if (!g.roles.includes(role)) g.roles.push(role);
        }
      }
    }
    const list = [...groups.values()].sort((a, b) => a.type.localeCompare(b.type) || a.url.localeCompare(b.url));
    L.push(`## ${sev === 'error' ? 'Errors' : 'Warnings'} (${list.length} distinct)`);
    L.push('');
    if (!list.length) { L.push('None.'); L.push(''); continue; }
    let lastType = '';
    for (const g of list) {
      if (g.type !== lastType) { L.push(`### ${g.type}`); L.push(''); lastType = g.type; }
      const who = g.roles.length === roles.length ? 'all roles' : g.roles.join(', ');
      L.push(`- \`${g.url}\` (${who}): ${g.message}${g.shot ? ` [shot](${rel2(g.shot)})` : ''}`);
    }
    L.push('');
  }
  const info = new Map();
  for (const recs of Object.values(report)) for (const r of recs) for (const f of r.findings) if (f.severity === 'info') info.set(f.type, (info.get(f.type) || 0) + 1);
  L.push('## Info');
  L.push('');
  L.push(info.size ? [...info].map(([t, n]) => `- ${t}: ${n}`).join('\n') : 'None.');
  L.push('');
  L.push('## URLs per role');
  L.push('');
  for (const [role, recs] of Object.entries(report)) {
    L.push(`<details><summary>${role}: ${recs.length} URLs</summary>`);
    L.push('');
    L.push('| Kind | URL | HTTP | Access | Final URL | Title | Findings |');
    L.push('|---|---|---:|---|---|---|---:|');
    for (const r of recs) {
      const f = r.findings.filter((x) => x.severity !== 'info').length;
      L.push(`| ${r.kind} | ${r.shots.desktop ? `[${esc(r.url.slice(0, 70))}](${rel2(r.shots.desktop)})` : esc(r.url.slice(0, 70))} | ${r.status ?? ''} | ${r.access ?? ''} | ${r.finalUrl && norm(r.finalUrl) !== norm(r.url) ? esc(r.finalUrl.slice(0, 50)) : ''} | ${esc(r.title.slice(0, 50))} | ${f || ''} |`);
    }
    L.push('');
    L.push('</details>');
    L.push('');
  }
  return L.join('\n');
}

/* ------------------------------------------------------------------- main */

if (opt('summary-only', false)) {
  // Rewrite summary.md from an existing report (after editing the crawler's summary code).
  const rep = JSON.parse(fs.readFileSync(path.join(OUT, 'report.json'), 'utf8'));
  const m = JSON.parse(fs.readFileSync(path.join(OUT, 'meta.json'), 'utf8'));
  fs.writeFileSync(path.join(OUT, 'summary.md'), summarize(rep, m));
  console.log(`crawl: rewrote ${path.join(OUT, 'summary.md')}`);
  process.exit(0);
}
if (!(await siteUp(BASE))) {
  console.error(`crawl.mjs: nothing answering on ${BASE} (start it with wordpress/dev/start.sh ${port})`);
  process.exit(1);
}
let I;
try { I = await fetchIds(BASE); } catch (err) {
  console.error(`crawl.mjs: the QA endpoint ?dev_qa=ids failed (${err.message}). Is dev/mu-plugins/spokares-dev-qa.php loaded? Restart the site after adding it.`);
  process.exit(1);
}
const missing = ALL.filter((a) => a.login && ROLES.includes(a.key) && !I.users[a.login]).map((a) => a.login);
if (missing.length) {
  console.error(`crawl.mjs: dev accounts missing: ${missing.join(', ')}. Restart the site (setup/setup.php creates them).`);
  process.exit(1);
}
const meta = {
  started: new Date().toISOString(), finished: null, base: BASE, port: Number(port), wp: I.wp, php: I.php, theme: I.theme,
  roles: ROLES.map((k) => { const a = account(k); return { key: k, login: a.login, dev_login: a.dev_login, role: a.role || null, label: a.label, shots: path.join(SHOTS, k) }; }),
  shots: SHOTS, out: OUT, mobile: MOBILE, maxPublic: MAX_PUBLIC, maxAdmin: MAX_ADMIN, match: MATCH,
};
let report = {};
let previous = null;
if (MERGE) {
  try {
    previous = JSON.parse(fs.readFileSync(path.join(OUT, 'report.json'), 'utf8'));
    const oldMeta = JSON.parse(fs.readFileSync(path.join(OUT, 'meta.json'), 'utf8'));
    meta.started = oldMeta.started;
    meta.roles = [...(oldMeta.roles || []).filter((r) => !ROLES.includes(r.key)), ...meta.roles];
    meta.merges = [...(oldMeta.merges || []), { at: new Date().toISOString(), roles: ROLES, match: MATCH }];
  } catch (err) {
    console.error(`crawl.mjs: --merge needs an existing report in ${OUT} (${err.message})`);
    process.exit(2);
  }
}
/** The report to write: this run's records folded into the previous report (--merge). */
function merged() {
  if (!previous) return report;
  const out = {};
  const order = [...new Set([...ALL.map((a) => a.key)].filter((k) => previous[k] || report[k]))];
  for (const role of order) {
    const fresh = report[role] || [];
    const urls = new Set(fresh.map((r) => r.url));
    out[role] = [...(previous[role] || []).filter((r) => !urls.has(r.url)), ...fresh];
  }
  return out;
}
/** Delete screenshots of replaced records that no record points at any more (--merge). */
function pruneShots(out) {
  if (!previous) return;
  const kept = new Set(Object.values(out).flat().flatMap((r) => [r.shots?.desktop, r.shots?.mobile]).filter(Boolean));
  for (const r of Object.values(previous).flat()) {
    for (const f of [r.shots?.desktop, r.shots?.mobile]) if (f && !kept.has(f)) fs.rmSync(f, { force: true });
  }
}
const t0 = Date.now();
const stopped = []; // roles whose crawl could not start or could not finish: the crawl exits 1
for (const role of ROLES) {
  const crawl = new RoleCrawl(role, I);
  try {
    report[role] = await crawl.run();
  } catch (err) {
    const started = !!crawl.me;
    stopped.push(`${role} (${started ? 'stopped' : 'never started'}: ${err.message.split('\n')[0]})`);
    console.error(`crawl.mjs: role ${role} ${started ? 'stopped' : 'could not start'}: ${err.stack || err.message}`);
    report[role] = [...crawl.records, { role, url: '(role)', key: null, kind: 'admin', source: 'crawl', status: null, finalUrl: null, redirects: [], title: '', access: 'error', shots: {}, ms: 0, findings: [{ type: 'crawl', severity: 'error', message: `the crawl of this role ${started ? 'stopped' : 'could not start'}: ${err.message}` }] }];
    await crawl.close();
  }
  meta.finished = new Date().toISOString();
  const out = merged();
  meta.urlCounts = Object.fromEntries(Object.entries(out).map(([k, v]) => [k, v.length]));
  fs.writeFileSync(path.join(OUT, 'report.json'), JSON.stringify(out, null, 1));
  fs.writeFileSync(path.join(OUT, 'meta.json'), JSON.stringify(meta, null, 1));
}
meta.seconds = Math.round((Date.now() - t0) / 1000);
const final = merged();
pruneShots(final);
fs.writeFileSync(path.join(OUT, 'meta.json'), JSON.stringify(meta, null, 1));
fs.writeFileSync(path.join(OUT, 'summary.md'), summarize(final, meta));
const tot = (sev) => Object.values(final).flat().reduce((t, r) => t + r.findings.filter((f) => f.severity === sev).length, 0);
console.log(`\ncrawl: ${Object.values(report).flat().length} URLs visited over ${ROLES.length} roles in ${meta.seconds}s${previous ? ` (merged: ${Object.values(final).flat().length} URLs in the report)` : ''}; report totals: ${tot('error')} errors, ${tot('warning')} warnings, ${tot('info')} info`);
console.log(`crawl: ${path.join(OUT, 'report.json')}, ${path.join(OUT, 'summary.md')}; shots in ${SHOTS}`);
if (stopped.length) {
  // The findings never change the exit code; a role that was not crawled does.
  console.error(`crawl.mjs: ${stopped.length} of ${ROLES.length} role(s) not crawled in full: ${stopped.join('; ')}`);
  process.exit(1);
}
process.exit(0);
