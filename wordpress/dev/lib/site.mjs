// DEV ONLY. Helpers for driving the dev site: the dev accounts (one per user
// level, dev/setup/qa-users.json), signing in with ?dev_login=, the QA
// endpoints of dev/mu-plugins/spokares-dev-qa.php, and reading the PHP logs.

import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const here = path.dirname(fileURLToPath(import.meta.url));
export const DEV_DIR = path.resolve(here, '..');
export const WP_DIR = path.resolve(DEV_DIR, '..');

/** The dev accounts, in qa-users.json order: [{ key, login, dev_login, role, label, caps }]. */
export function accounts() {
  const data = JSON.parse(fs.readFileSync(path.join(DEV_DIR, 'setup', 'qa-users.json'), 'utf8'));
  return data.accounts.map((a) => ({ ...a, dev_login: a.login ? (a.dev_login || a.login) : '' }));
}

/** One account by role key; throws with the known keys when unknown. */
export function account(key) {
  const all = accounts();
  const a = all.find((x) => x.key === key);
  if (!a) throw new Error(`unknown role "${key}" (known: ${all.map((x) => x.key).join(', ')})`);
  return a;
}

export const baseUrl = (port) => `http://127.0.0.1:${port}`;

/** Is the dev site answering? */
export async function siteUp(base) {
  try { const r = await fetch(`${base}/`, { redirect: 'manual', signal: AbortSignal.timeout(10000) }); return r.status > 0; } catch { return false; }
}

/** GET a QA endpoint (?dev_qa=...) from Node, signed out. */
export async function qa(base, what, params = {}) {
  const q = new URLSearchParams({ dev_qa: what, ...params });
  const r = await fetch(`${base}/?${q}`, { signal: AbortSignal.timeout(30000) });
  if (!r.ok) throw new Error(`?dev_qa=${what}: HTTP ${r.status}`);
  return r.json();
}

/** Post and term IDs by slug, user IDs by login. */
export const ids = (base) => qa(base, 'ids');

/** Remove every post edit lock (so the block editor never opens "locked"). */
export const unlockPosts = (base) => qa(base, 'unlock').catch(() => null);

/**
 * The page's signed-in user, fetched with the page's cookies: { id, login,
 * roles, caps, can }. caps: ['edit_pages', 'edit_post:62', ...] fills `can`.
 */
export async function whoami(page, base, caps = []) {
  return page.evaluate(async (url) => {
    const r = await fetch(url, { credentials: 'same-origin', cache: 'no-store' });
    return r.json();
  }, `${base}/?dev_qa=whoami&caps=${encodeURIComponent(caps.join(','))}`);
}

/**
 * Sign the page's context in as the account for a role key (anonymous: no-op)
 * through ?dev_login=, and check that it worked. Returns the account.
 */
export async function loginAs(page, base, key) {
  const a = account(key);
  if (!a.login) return a;
  const nav = await page.goto(`${base}/wp-admin/profile.php?dev_login=${encodeURIComponent(a.dev_login)}`, { idle: false });
  if (/dev_login=/.test(nav.url)) throw new Error(`dev login as ${a.login} did not redirect (HTTP ${nav.status} at ${nav.url})`);
  const me = await whoami(page, base);
  if (me.login !== a.login) throw new Error(`dev login as ${a.login} failed: signed in as "${me.login || 'nobody'}"`);
  return a;
}

/* ------------------------------------------------------------------- logs */

export const PHP_RE = /PHP (Warning|Notice|Deprecated|Fatal error|Parse error|Recoverable fatal error)|WordPress database error/;

/** Byte offsets into a log file, to read only what was written since a mark. */
export class LogTail {
  constructor(file) { this.file = file; }
  size() { try { return fs.statSync(this.file).size; } catch { return 0; } }
  mark() { return this.size(); }
  /** Lines written since mark (all of the file if it shrank). */
  since(mark) {
    const size = this.size();
    const from = size < mark ? 0 : mark;
    if (size <= from) return [];
    const fd = fs.openSync(this.file, 'r');
    try {
      const buf = Buffer.alloc(Math.min(size - from, 4 * 1024 * 1024));
      fs.readSync(fd, buf, 0, buf.length, from);
      return buf.toString('utf8').split('\n').filter(Boolean);
    } finally { fs.closeSync(fd); }
  }
  /** Only the PHP messages (warnings, notices, deprecations, fatals, DB errors). */
  phpSince(mark) { return this.since(mark).filter((l) => PHP_RE.test(l)); }
}

/** The dev site's two PHP logs: WordPress's debug.log and Playground's own. */
export function phpLogs(port) {
  return [new LogTail(`/tmp/pg-${port}-logs/debug.log`), new LogTail(`/tmp/pg-${port}.log`)];
}
