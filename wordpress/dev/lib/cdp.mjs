// DEV ONLY. A small Chrome DevTools-protocol driver, Node standard library
// only (Node 22+ has a built-in WebSocket). Shared by dev/shots.mjs,
// dev/qa/crawl.mjs and dev/tests/run-e2e.mjs.
//
//   const browser = await Browser.launch();          // fresh profile under /tmp
//   const context = await browser.newContext();      // isolated cookies
//   const page = await browser.newPage({ context, viewport: DESKTOP });
//   const nav = await page.goto('http://127.0.0.1:9400/');   // { status, url, chain, ... }
//   await page.click('#submit', { navigation: true });
//   await page.screenshot('/tmp/x.png');
//   await browser.close();                            // kills Chrome's whole process group
//
// Every Chrome this module starts is killed when Node exits (normally, on a
// signal or on an uncaught error), and its profile folder is deleted.
//
// A loaded machine can make Chrome slow to open its DevTools port, so
// Browser.launch() waits up to SPOKARES_CHROME_CONNECT_MS (default 60000) for
// each attempt, and tries three times. When Chrome dies, everything waiting
// on it fails at once with a BrowserClosedError (browser.closed is true), and
// Page.goto() throws one too rather than answering "HTTP 0", so a caller can
// restart Chrome instead of recording false errors.

import { spawn, execFileSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';

export const CHROME = process.env.CHROME || '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
export const DESKTOP = { width: 1440, height: 900, mobile: false };
export const PHONE = { width: 390, height: 844, mobile: true };
export const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
/** How long one launch attempt waits for Chrome's DevTools port and WebSocket (ms). */
export const CONNECT_MS = Math.max(1000, Number(process.env.SPOKARES_CHROME_CONNECT_MS) || 60000);

/** Thrown when Chrome has gone away (crashed, killed or closed). */
export class BrowserClosedError extends Error {}

/** Reject after ms with a clear message; clears its timer either way. */
export function withTimeout(promise, ms, what) {
  let timer;
  return Promise.race([
    promise.finally(() => clearTimeout(timer)),
    new Promise((_, reject) => { timer = setTimeout(() => reject(new Error(`timeout after ${ms} ms: ${what}`)), ms); }),
  ]);
}

/* --------------------------------------------------------- process safety */

const liveBrowsers = new Set();
function killAll() { for (const b of liveBrowsers) b.kill(); }
process.on('exit', killAll);
for (const sig of ['SIGINT', 'SIGTERM', 'SIGHUP']) {
  process.on(sig, () => { killAll(); process.exit(130); });
}
process.on('uncaughtException', (err) => { console.error(err); killAll(); process.exit(1); });
process.stdout.on('error', () => {}); // keep going if whoever reads our output goes away (EPIPE)

/* ---------------------------------------------------------------- Browser */

export class Browser {
  constructor(proc, profile) {
    this.proc = proc;
    this.profile = profile;
    this.nextId = 0;
    this.pending = new Map();
    this.listeners = new Set();
    this.pages = new Map(); // sessionId -> Page
    this.closed = false;
    liveBrowsers.add(this);
  }

  /**
   * Start headless Chrome with a fresh profile under /tmp and connect. Each
   * attempt waits up to connectTimeout ms (SPOKARES_CHROME_CONNECT_MS) for
   * DevTools; a failed attempt is killed and, after a short pause, retried.
   */
  static async launch({ chrome = CHROME, args = [], tries = 3, connectTimeout = CONNECT_MS } = {}) {
    let lastErr;
    for (let attempt = 1; attempt <= tries; attempt++) {
      if (attempt > 1) await sleep(1000 * (attempt - 1));
      const profile = fs.mkdtempSync('/tmp/spokares-chrome-');
      const proc = spawn(
        chrome,
        [
          '--headless=new',
          '--disable-gpu',
          '--hide-scrollbars',
          '--no-first-run',
          '--no-default-browser-check',
          '--disable-extensions',
          '--disable-component-update',
          '--disable-background-networking',
          '--disable-sync',
          '--disable-default-apps',
          '--disable-domain-reliability',
          '--disable-client-side-phishing-detection',
          '--disable-breakpad',
          '--disable-hang-monitor',
          '--disable-popup-blocking',
          '--disable-prompt-on-repost',
          '--disable-features=Translate,OptimizationHints,MediaRouter,DialMediaRouteProvider,ChromeWhatsNewUI,AutofillServerCommunication,CertificateTransparencyComponentUpdater',
          '--metrics-recording-only',
          '--password-store=basic',
          '--use-mock-keychain',
          '--mute-audio',
          '--remote-debugging-port=0',
          `--user-data-dir=${profile}`,
          ...args,
          'about:blank',
        ],
        { stdio: 'ignore', detached: true } // own process group: kill() takes the helpers too
      );
      const browser = new Browser(proc, profile);
      proc.on('exit', () => browser.markClosed('Chrome exited'));
      proc.on('error', (err) => browser.markClosed(`Chrome could not be started: ${err.message}`));
      try {
        await browser.connect(connectTimeout);
        return browser;
      } catch (err) {
        lastErr = new Error(`${err.message} (launch attempt ${attempt} of ${tries})`);
        browser.kill();
      }
    }
    throw lastErr;
  }

  /** Wait (at most timeoutMs in all) for DevToolsActivePort, then open the WebSocket. */
  async connect(timeoutMs = CONNECT_MS) {
    const end = Date.now() + timeoutMs;
    const portFile = path.join(this.profile, 'DevToolsActivePort');
    while (!fs.existsSync(portFile) && Date.now() < end) {
      if (this.closed || this.proc.exitCode !== null || this.proc.signalCode) throw new Error(`${this.closedWhy || `Chrome exited (${this.proc.exitCode ?? this.proc.signalCode})`} before DevTools started`);
      await sleep(100);
    }
    if (!fs.existsSync(portFile)) throw new Error(`Chrome did not start (no DevToolsActivePort after ${Math.round(timeoutMs / 1000)} s)`);
    await sleep(100);
    const [devPort, wsPath] = fs.readFileSync(portFile, 'utf8').trim().split('\n');
    this.ws = new WebSocket(`ws://127.0.0.1:${devPort}${wsPath}`);
    const left = Math.max(5000, end - Date.now());
    await withTimeout(new Promise((resolve, reject) => {
      this.ws.onopen = resolve;
      this.ws.onerror = () => reject(new Error('DevTools WebSocket failed'));
    }), left, 'DevTools WebSocket');
    this.ws.onmessage = (ev) => this.onMessage(JSON.parse(ev.data));
    this.ws.onclose = () => this.markClosed('browser connection closed');
    await this.send('Browser.setDownloadBehavior', { behavior: 'deny' }).catch(() => {});
  }

  /**
   * Chrome has gone (its WebSocket closed or its process exited): fail every
   * command and every page wait still pending, now, with a BrowserClosedError.
   */
  markClosed(why = 'browser connection closed') {
    this.closed = true;
    this.closedWhy = this.closedWhy || why;
    for (const [, p] of this.pending) { clearTimeout(p.timer); p.reject(new BrowserClosedError(`${p.method}: ${why}`)); }
    this.pending.clear();
    if (this.pagesTold) return;
    this.pagesTold = true;
    for (const page of this.pages.values()) page.onEvent({ method: 'Browser.closed', params: { why } });
  }

  onMessage(m) {
    if (m.id) {
      const p = this.pending.get(m.id);
      if (!p) return;
      this.pending.delete(m.id);
      clearTimeout(p.timer);
      if (m.error) p.reject(new Error(`${p.method}: ${m.error.message}`));
      else p.resolve(m.result);
      return;
    }
    if (!m.method) return;
    if (m.sessionId && this.pages.has(m.sessionId)) this.pages.get(m.sessionId).onEvent(m);
    for (const fn of [...this.listeners]) { try { fn(m); } catch {} }
  }

  /** Send a CDP command (to a page's session when sessionId is given). */
  send(method, params = {}, sessionId, timeoutMs = 60000) {
    if (this.closed || !this.ws) return Promise.reject(new BrowserClosedError(`${method}: browser is closed`));
    const id = ++this.nextId;
    const msg = { id, method, params };
    if (sessionId) msg.sessionId = sessionId;
    return new Promise((resolve, reject) => {
      const timer = setTimeout(() => {
        this.pending.delete(id);
        reject(new Error(`${method}: no answer from Chrome after ${timeoutMs} ms`));
      }, timeoutMs);
      this.pending.set(id, { resolve, reject, method, timer });
      try { this.ws.send(JSON.stringify(msg)); } catch (err) { clearTimeout(timer); this.pending.delete(id); reject(err); }
    });
  }

  /** A new isolated browser context (its own cookies and storage). */
  async newContext() {
    return (await this.send('Target.createBrowserContext', { disposeOnDetach: true })).browserContextId;
  }

  /** Open a tab (in a context, if given) and return its Page. */
  async newPage({ context, viewport = DESKTOP } = {}) {
    const params = { url: 'about:blank' };
    if (context) params.browserContextId = context;
    const { targetId } = await this.send('Target.createTarget', params);
    const { sessionId } = await this.send('Target.attachToTarget', { targetId, flatten: true });
    const page = new Page(this, targetId, sessionId);
    this.pages.set(sessionId, page);
    await page.init(viewport);
    return page;
  }

  /** Is Chrome still answering? */
  async alive(ms = 5000) {
    try { await this.send('Browser.getVersion', {}, undefined, ms); return true; } catch { return false; }
  }

  /** Close Chrome: kill its process group and delete the profile. */
  async close() {
    if (!this.closed) { try { await this.send('Browser.close', {}, undefined, 2000); } catch {} }
    this.kill();
  }

  kill() {
    liveBrowsers.delete(this);
    this.markClosed('browser closed');
    try { this.ws?.close(); } catch {}
    if (this.proc?.pid) {
      try { process.kill(-this.proc.pid, 'SIGKILL'); } catch {}
      try { this.proc.kill('SIGKILL'); } catch {}
    }
    // Anything else still using this profile is ours too.
    try { execFileSync('pkill', ['-9', '-f', this.profile], { stdio: 'ignore' }); } catch {}
    try { fs.rmSync(this.profile, { recursive: true, force: true, maxRetries: 3 }); } catch {}
  }
}

/* ------------------------------------------------------------------- Page */

const KEYS = {
  Enter: { key: 'Enter', code: 'Enter', windowsVirtualKeyCode: 13, text: '\r' },
  Tab: { key: 'Tab', code: 'Tab', windowsVirtualKeyCode: 9 },
  Escape: { key: 'Escape', code: 'Escape', windowsVirtualKeyCode: 27 },
  Backspace: { key: 'Backspace', code: 'Backspace', windowsVirtualKeyCode: 8 },
  Delete: { key: 'Delete', code: 'Delete', windowsVirtualKeyCode: 46 },
  ArrowUp: { key: 'ArrowUp', code: 'ArrowUp', windowsVirtualKeyCode: 38 },
  ArrowDown: { key: 'ArrowDown', code: 'ArrowDown', windowsVirtualKeyCode: 40 },
  ArrowLeft: { key: 'ArrowLeft', code: 'ArrowLeft', windowsVirtualKeyCode: 37 },
  ArrowRight: { key: 'ArrowRight', code: 'ArrowRight', windowsVirtualKeyCode: 39 },
  Home: { key: 'Home', code: 'Home', windowsVirtualKeyCode: 36 },
  End: { key: 'End', code: 'End', windowsVirtualKeyCode: 35 },
  Space: { key: ' ', code: 'Space', windowsVirtualKeyCode: 32, text: ' ' },
};
const MODIFIERS = { Alt: 1, Control: 2, Ctrl: 2, Meta: 4, Cmd: 4, Shift: 8 };

export class Page {
  constructor(browser, targetId, sessionId) {
    this.browser = browser;
    this.targetId = targetId;
    this.sessionId = sessionId;
    this.viewport = DESKTOP;
    this.mainFrameId = null;
    this.requests = new Map(); // requestId -> { url, type, frameId }
    this.inflight = new Set();
    this.waiters = new Set();
    this.resetCapture();
    this.resetNav();
  }

  send(method, params = {}, timeoutMs = 60000) {
    return this.browser.send(method, params, this.sessionId, timeoutMs);
  }

  async init(viewport) {
    await this.send('Page.enable');
    await this.send('Runtime.enable');
    await this.send('Log.enable');
    await this.send('Network.enable');
    await this.send('Page.setLifecycleEventsEnabled', { enabled: true }).catch(() => {});
    const tree = await this.send('Page.getFrameTree');
    this.mainFrameId = tree.frameTree.frame.id;
    await this.setViewport(viewport);
  }

  async setViewport(view) {
    this.viewport = view;
    await this.send('Emulation.setDeviceMetricsOverride', { width: view.width, height: view.height, deviceScaleFactor: 1, mobile: !!view.mobile });
    await this.send('Emulation.setTouchEmulationEnabled', { enabled: !!view.mobile }).catch(() => {});
  }

  /** Clear the console, exception, failed-request and dialog lists. */
  resetCapture() {
    this.consoleErrors = [];
    this.consoleWarnings = [];
    this.exceptions = [];
    this.failedRequests = [];
    this.dialogs = [];
    this.externalRequests = [];
  }

  resetNav() {
    this.chain = [];
    this.docResponse = null;
  }

  onEvent(m) {
    const p = m.params || {};
    switch (m.method) {
      case 'Runtime.exceptionThrown': {
        const d = p.exceptionDetails || {};
        const text = (d.exception?.description || d.text || 'exception').split('\n')[0];
        this.exceptions.push({ text: text.slice(0, 300), url: d.url || '', line: d.lineNumber });
        break;
      }
      case 'Runtime.consoleAPICalled': {
        if (p.type !== 'error' && p.type !== 'warning' && p.type !== 'assert') break;
        const text = (p.args || []).map((a) => a.value ?? a.description ?? '').join(' ').replace(/\s+/g, ' ').slice(0, 300);
        const where = p.stackTrace?.callFrames?.[0]?.url || '';
        (p.type === 'warning' ? this.consoleWarnings : this.consoleErrors).push({ text, url: where });
        break;
      }
      case 'Log.entryAdded': {
        const e = p.entry || {};
        // Network failures are captured from the Network domain instead.
        if (e.level === 'error' && e.source !== 'network') this.consoleErrors.push({ text: (e.text || '').slice(0, 300), url: e.url || '', source: e.source });
        break;
      }
      case 'Page.javascriptDialogOpening': {
        this.dialogs.push({ type: p.type, message: (p.message || '').slice(0, 200) });
        this.send('Page.handleJavaScriptDialog', { accept: true }).catch(() => {});
        break;
      }
      case 'Network.requestWillBeSent': {
        const isDoc = p.type === 'Document' && p.frameId === this.mainFrameId;
        if (p.redirectResponse && isDoc) {
          this.chain.push({ url: p.redirectResponse.url, status: p.redirectResponse.status, location: p.request.url });
        }
        this.requests.set(p.requestId, { url: p.request.url, type: p.type, frameId: p.frameId, isDoc, t: Date.now() });
        if (!p.request.url.startsWith('data:')) this.inflight.add(p.requestId);
        break;
      }
      case 'Network.responseReceived': {
        const req = this.requests.get(p.requestId) || {};
        const r = p.response || {};
        if (p.type === 'Document' && p.frameId === this.mainFrameId) {
          this.docResponse = { url: r.url, status: r.status, mimeType: r.mimeType };
        } else if (r.status >= 400) {
          this.failedRequests.push({ url: r.url, status: r.status, type: p.type || req.type || '' });
        }
        break;
      }
      case 'Network.loadingFinished':
        this.inflight.delete(p.requestId);
        break;
      case 'Network.loadingFailed': {
        this.inflight.delete(p.requestId);
        const req = this.requests.get(p.requestId) || {};
        if (!p.canceled && p.errorText !== 'net::ERR_ABORTED' && !req.isDoc) {
          this.failedRequests.push({ url: req.url || '', error: p.errorText, type: p.type || req.type || '', blocked: p.blockedReason || '' });
        }
        break;
      }
      default:
        break;
    }
    for (const w of [...this.waiters]) w(m);
  }

  /**
   * Resolve with the params of the next event `method` matching `pred`.
   * Rejects with a BrowserClosedError at once if Chrome goes away.
   */
  waitForEvent(method, { timeout = 30000, pred = () => true } = {}) {
    if (this.browser.closed) return Promise.reject(new BrowserClosedError(`waiting for ${method}: browser is closed`));
    return new Promise((resolve, reject) => {
      const timer = setTimeout(() => { this.waiters.delete(fn); reject(new Error(`timeout after ${timeout} ms waiting for ${method}`)); }, timeout);
      const fn = (m) => {
        if (m.method === 'Browser.closed') {
          clearTimeout(timer);
          this.waiters.delete(fn);
          reject(new BrowserClosedError(`waiting for ${method}: ${m.params?.why || 'browser is closed'}`));
          return;
        }
        if (m.method === method && pred(m.params || {})) {
          clearTimeout(timer);
          this.waiters.delete(fn);
          resolve(m.params || {});
        }
      };
      this.waiters.add(fn);
    });
  }

  /**
   * Requests that count as "busy": not a long-poll (pending over 5 s) and not
   * WordPress's compression test, which never completes under Playground.
   */
  busyRequests() {
    const now = Date.now();
    return [...this.inflight].filter((id) => {
      const r = this.requests.get(id);
      return r && now - r.t < 5000 && !/[?&]action=(wp-compression-test|heartbeat)\b/.test(r.url);
    });
  }

  /** Wait until no request has been in flight for idleMs (at most timeout ms). */
  async waitForIdle({ idleMs = 500, timeout = 10000 } = {}) {
    const end = Date.now() + timeout;
    let quietSince = this.busyRequests().length ? 0 : Date.now();
    while (Date.now() < end) {
      if (this.browser.closed) return false;
      if (this.busyRequests().length === 0) {
        if (!quietSince) quietSince = Date.now();
        if (Date.now() - quietSince >= idleMs) return true;
      } else {
        quietSince = 0;
      }
      await sleep(50);
    }
    return false;
  }

  /**
   * Navigate and wait for the load event, then for the network to settle.
   * Returns { status, url, chain, mimeType, error, timedOut }: status is the
   * final document's HTTP status, chain the redirects before it. Throws a
   * BrowserClosedError when Chrome is gone (before or during the load), so a
   * dead browser is never mistaken for a page that answered "HTTP 0".
   */
  async goto(url, { timeout = 60000, idle = true } = {}) {
    const gone = () => new BrowserClosedError(`goto ${url}: browser is closed`);
    if (this.browser.closed) throw gone();
    this.resetNav();
    this.inflight.clear();
    const loaded = this.waitForEvent('Page.loadEventFired', { timeout }).then(() => true, () => false);
    let nav;
    try {
      nav = await this.send('Page.navigate', { url }, timeout);
    } catch (err) {
      if (this.browser.closed || err instanceof BrowserClosedError) throw gone();
      return { status: 0, url, chain: this.chain, error: err.message, timedOut: /timeout|no answer/.test(err.message) };
    }
    const ok = nav.errorText && nav.errorText !== 'net::ERR_ABORTED' ? true : await loaded;
    if (this.browser.closed) throw gone();
    if (idle && ok) await this.waitForIdle({ idleMs: 400, timeout: 8000 });
    let href = url;
    try { href = await this.evaluateT(5000, 'location.href'); } catch {}
    if (this.browser.closed) throw gone();
    if (href.startsWith('chrome-error://') && this.docResponse) href = this.docResponse.url;
    return {
      status: this.docResponse?.status ?? 0,
      url: href,
      chain: this.chain.slice(),
      mimeType: this.docResponse?.mimeType || '',
      error: nav.errorText || '',
      timedOut: !ok,
    };
  }

  /** Wait for the next main-frame load (after a click that navigates). */
  waitForNavigation({ timeout = 60000 } = {}) {
    this.resetNav();
    return this.waitForEvent('Page.loadEventFired', { timeout });
  }

  /**
   * Evaluate a function (with JSON-serialisable args) or an expression in
   * the page and return its value (promises are awaited). Gives up after
   * 2 minutes; evaluateT() takes another limit.
   */
  evaluate(fn, ...args) {
    return this.evaluateT(120000, fn, ...args);
  }

  /** evaluate() with a time limit in ms. */
  async evaluateT(timeoutMs, fn, ...args) {
    const expression = typeof fn === 'function' ? `(${fn.toString()})(...${JSON.stringify(args)})` : fn;
    const r = await this.send('Runtime.evaluate', { expression, awaitPromise: true, returnByValue: true, userGesture: true }, timeoutMs);
    if (r.exceptionDetails) {
      throw new Error(`page script: ${(r.exceptionDetails.exception?.description || r.exceptionDetails.text || '').split('\n')[0]}`);
    }
    return r.result.value;
  }

  /** Wait until selector matches (and, with visible, is rendered). */
  async waitFor(selector, { timeout = 15000, visible = false } = {}) {
    const end = Date.now() + timeout;
    while (Date.now() < end) {
      try {
        const found = await this.evaluate((sel, vis) => {
          const el = document.querySelector(sel);
          if (!el) return false;
          if (!vis) return true;
          const r = el.getBoundingClientRect();
          const cs = getComputedStyle(el);
          return r.width > 0 && r.height > 0 && cs.visibility !== 'hidden' && cs.display !== 'none';
        }, selector, visible);
        if (found) return true;
      } catch {} // context replaced during a navigation
      await sleep(100);
    }
    throw new Error(`timeout after ${timeout} ms waiting for ${selector}${visible ? ' (visible)' : ''}`);
  }

  /** Wait until fn(...args) in the page returns something truthy; returns it. */
  async waitForFunction(fn, { timeout = 15000, interval = 100, args = [] } = {}) {
    const end = Date.now() + timeout;
    let last;
    while (Date.now() < end) {
      try { last = await this.evaluate(fn, ...args); if (last) return last; } catch {}
      await sleep(interval);
    }
    throw new Error(`timeout after ${timeout} ms waiting for a page condition`);
  }

  /**
   * Scroll an element into view and click its centre with the mouse.
   *
   * The scroll is instant (behavior 'instant' overrides a page's
   * `scroll-behavior: smooth`, which the theme sets), and the element is
   * measured only once its box has stopped moving (a scroll that was already
   * animating, a sticky header settling, a late layout shift), so the mouse
   * lands on the element and not where it was before the scroll.
   */
  async click(selector, { navigation = false, timeout = 15000 } = {}) {
    await this.waitFor(selector, { timeout, visible: true });
    const box = await this.evaluate(async (sel) => {
      const el = document.querySelector(sel);
      const frame = () => new Promise((r) => { requestAnimationFrame(() => r()); setTimeout(r, 50); });
      const measure = () => { const r = el.getBoundingClientRect(); return { x: r.left + r.width / 2, y: r.top + r.height / 2, key: [r.left, r.top, r.width, r.height].map(Math.round).join() }; };
      el.scrollIntoView({ block: 'center', inline: 'center', behavior: 'instant' });
      let last = measure();
      for (let i = 0, still = 0; i < 40 && still < 2; i++) {
        await frame();
        const now = measure();
        still = now.key === last.key ? still + 1 : 0;
        last = now;
      }
      return { x: last.x, y: last.y };
    }, selector);
    const nav = navigation ? this.waitForNavigation({ timeout: 60000 }) : null;
    const base = { x: box.x, y: box.y, button: 'left', clickCount: 1 };
    await this.send('Input.dispatchMouseEvent', { type: 'mouseMoved', x: box.x, y: box.y });
    await this.send('Input.dispatchMouseEvent', { type: 'mousePressed', ...base });
    await this.send('Input.dispatchMouseEvent', { type: 'mouseReleased', ...base });
    if (nav) {
      await nav;
      await this.waitForIdle({ idleMs: 400, timeout: 8000 });
    }
  }

  /** Focus a field and type text into it (clear: replace what is there). */
  async type(selector, text, { clear = false, timeout = 15000 } = {}) {
    await this.waitFor(selector, { timeout });
    await this.evaluate((sel, clr) => {
      const el = document.querySelector(sel);
      el.scrollIntoView({ block: 'center', behavior: 'instant' });
      el.focus();
      if (clr) {
        if (typeof el.select === 'function') el.select();
        else document.execCommand('selectAll');
      }
      return true;
    }, selector, clear);
    if (clear) await this.press('Backspace');
    if (text) await this.send('Input.insertText', { text });
  }

  /** Press a key, e.g. 'Enter', 'Tab', 'a' or 'Meta+z'. */
  async press(combo) {
    const parts = combo.split('+');
    const name = parts.pop();
    const modifiers = parts.reduce((m, p) => m | (MODIFIERS[p] || 0), 0);
    const k = KEYS[name] || { key: name, code: name.length === 1 ? `Key${name.toUpperCase()}` : name, windowsVirtualKeyCode: name.length === 1 ? name.toUpperCase().charCodeAt(0) : 0, text: name.length === 1 && !modifiers ? name : undefined };
    await this.send('Input.dispatchKeyEvent', { type: k.text ? 'keyDown' : 'rawKeyDown', modifiers, ...k });
    await this.send('Input.dispatchKeyEvent', { type: 'keyUp', modifiers, key: k.key, code: k.code, windowsVirtualKeyCode: k.windowsVirtualKeyCode });
  }

  /** Switch lazy images to eager and wait for every image (full-page shots). */
  async loadImages(timeout = 8000) {
    await this.evaluateT(timeout + 2000, `(async () => {
      document.querySelectorAll('img[loading="lazy"]').forEach(i => { i.loading = 'eager'; });
      await Promise.all([...document.images].map(i => i.decode ? i.decode().catch(() => {}) : null));
      await Promise.all([...document.images].filter(i => !i.complete).map(i => new Promise(r => { i.onload = i.onerror = r; setTimeout(r, 5000); })));
      if (document.fonts) await document.fonts.ready;
      return true; })()`).catch(() => {});
  }

  /** Save a PNG: the full page (default, capped at maxHeight) or the viewport. */
  async screenshot(file, { fullPage = true, maxHeight = 20000 } = {}) {
    let params = { format: 'png' };
    let width = this.viewport.width;
    let height = this.viewport.height;
    if (fullPage) {
      const m = await this.send('Page.getLayoutMetrics');
      height = Math.max(1, Math.min(maxHeight, Math.ceil(m.cssContentSize?.height || m.contentSize.height)));
      params = { format: 'png', captureBeyondViewport: true, clip: { x: 0, y: 0, width, height, scale: 1 } };
    }
    const { data } = await this.send('Page.captureScreenshot', params, 90000);
    fs.mkdirSync(path.dirname(file), { recursive: true });
    fs.writeFileSync(file, Buffer.from(data, 'base64'));
    return { file, width, height };
  }

  /** Cookies of this page's context, as a Cookie header for Node's fetch. */
  async cookieHeader(url) {
    const { cookies } = await this.send('Network.getCookies', url ? { urls: [url] } : {});
    return cookies.map((c) => `${c.name}=${c.value}`).join('; ');
  }

  async close() {
    this.browser.pages.delete(this.sessionId);
    await this.browser.send('Target.closeTarget', { targetId: this.targetId }, undefined, 5000).catch(() => {});
  }
}
