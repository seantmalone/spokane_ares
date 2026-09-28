// DEV ONLY. Functions that run INSIDE a page (through Page.evaluate) and
// return plain data: layout overflow, admin notices, wp_die pages, basic
// accessibility signals, links, and the block editor's state. Used by
// dev/qa/crawl.mjs and dev/tests/run-e2e.mjs. Each function must be
// self-contained (it is serialised with toString()).

/**
 * Facts about the loaded document. opts: { isPublic, vw }.
 */
export function domFacts(opts) {
  const out = {};
  const body = document.body;
  const text = (el) => (el ? (el.innerText || el.textContent || '') : '').replace(/\s+/g, ' ').trim();
  const rendered = (el) => {
    const r = el.getBoundingClientRect();
    if (r.width === 0 && r.height === 0) return false;
    const cs = getComputedStyle(el);
    return cs.visibility !== 'hidden' && cs.display !== 'none';
  };
  const hiddenFromAT = (el) => !!el.closest('[aria-hidden="true"], [hidden], [inert]');
  const desc = (el) => {
    let s = el.tagName.toLowerCase();
    if (el.id) s += `#${el.id}`;
    const cls = [...el.classList].filter((c) => !/^(wp-block-|has-|is-layout|wp-container|wp-elements)/.test(c)).slice(0, 3);
    if (cls.length) s += `.${cls.join('.')}`;
    const t = text(el).slice(0, 40);
    return t ? `${s} "${t}"` : s;
  };
  const accName = (el) => {
    const aria = (el.getAttribute('aria-label') || '').trim();
    if (aria) return aria;
    const lb = el.getAttribute('aria-labelledby');
    if (lb) {
      const s = lb.split(/\s+/).map((id) => document.getElementById(id)).filter(Boolean).map((n) => n.textContent.trim()).join(' ').trim();
      if (s) return s;
    }
    const own = (el.textContent || '').replace(/\s+/g, ' ').trim();
    if (own) return own;
    for (const i of el.querySelectorAll('img[alt], [role="img"][aria-label], svg[aria-label]')) {
      const a = (i.getAttribute('alt') || i.getAttribute('aria-label') || '').trim();
      if (a) return a;
    }
    const svgTitle = el.querySelector('svg title');
    if (svgTitle && svgTitle.textContent.trim()) return svgTitle.textContent.trim();
    const title = (el.getAttribute('title') || '').trim();
    if (title) return title;
    if (el.tagName === 'INPUT') return (el.value || '').trim();
    return '';
  };

  out.title = document.title;
  out.url = location.href;
  out.contentType = document.contentType;
  out.bodyClass = body ? body.className : '';
  out.lang = document.documentElement.getAttribute('lang') || '';
  out.bodyTextLength = text(body).length;
  out.isErrorPage = !!body && body.id === 'error-page';
  out.dieText = out.isErrorPage ? text(document.querySelector('.wp-die-message') || body).slice(0, 300) : '';
  out.isLoginPage = !!body && body.classList.contains('login') && !!document.querySelector('#loginform, #lostpasswordform, .login form');
  out.criticalError = /There has been a critical error on (this|your) website/i.test(text(body));
  out.isBlockEditor = !!body && body.classList.contains('block-editor-page');
  out.isSiteEditor = !!body && body.classList.contains('site-editor-php');
  out.isAdmin = !!body && body.classList.contains('wp-admin');
  out.h1 = [...document.querySelectorAll('h1')].map((h) => text(h).slice(0, 80));
  out.hasMain = !!document.querySelector('main, [role="main"]');
  out.hrefHash = document.querySelectorAll('a[href="#"]').length;

  // Horizontal overflow: the page scrolls sideways at this width.
  const vw = document.documentElement.clientWidth || opts.vw;
  const sw = Math.max(document.documentElement.scrollWidth, body ? body.scrollWidth : 0);
  out.overflow = { scrollWidth: sw, viewport: vw, offenders: [] };
  if (sw > vw + 1 && body) {
    const clips = (el) => {
      for (let p = el.parentElement; p && p !== body; p = p.parentElement) {
        const ox = getComputedStyle(p).overflowX;
        if (ox !== 'visible') return true;
      }
      return false;
    };
    const offenders = [];
    for (const el of body.querySelectorAll('*')) {
      const r = el.getBoundingClientRect();
      if (r.right <= vw + 1 || r.width === 0 || getComputedStyle(el).position === 'fixed') continue;
      const pr = el.parentElement ? el.parentElement.getBoundingClientRect() : { right: 0 };
      if (pr.right > vw + 1) continue; // report where the overflow starts
      if (clips(el)) continue;
      offenders.push({ el: desc(el), right: Math.round(r.right), width: Math.round(r.width) });
      if (offenders.length >= 6) break;
    }
    out.overflow.offenders = offenders;
  }

  // Overflow inside the page: an element's content sticking out of its own
  // box (a long word or link in a grid column running over the next column,
  // a nowrap row wider than its cell). The page as a whole need not scroll
  // sideways for this (the theme clips .wp-site-blocks), so the check above
  // misses it. For each box whose content is wider than it (scrollWidth >
  // clientWidth, overflow-x visible), name the direct child or text that
  // sticks out past its right edge. Scroll containers and clipped boxes, the
  // hidden and absolutely placed children, and deliberate negative right
  // margins (the theme's .prose .bleed) are left out. Not run in the editors.
  out.innerOverflow = [];
  const scope = out.isBlockEditor || out.isSiteEditor ? null : (out.isAdmin ? document.querySelector('#wpbody-content') : body);
  if (scope) {
    const shown = (cs) => cs.display !== 'none' && cs.visibility !== 'hidden' && Number(cs.opacity) !== 0;
    // Visually hidden on purpose: inside a clip / clip-path box, or a 1 px
    // box that hides its overflow (the stacked tables' <thead> on phones).
    const hiddenByAncestor = (el) => {
      for (let p = el.parentElement; p && p !== document.documentElement; p = p.parentElement) {
        const pcs = getComputedStyle(p);
        if ((pcs.clip && pcs.clip !== 'auto') || (pcs.clipPath && pcs.clipPath !== 'none') || !shown(pcs)) return true;
        if (pcs.overflowX !== 'visible' && (p.clientWidth <= 1 || p.clientHeight <= 1)) return true;
      }
      return false;
    };
    for (const el of scope.querySelectorAll('*')) {
      if (el.clientWidth <= 1 || el.scrollWidth <= el.clientWidth + 2) continue;
      if (el.closest('svg, #wpadminbar, [aria-hidden="true"], .screen-reader-text, .vh')) continue;
      const cs = getComputedStyle(el);
      if (cs.overflowX !== 'visible' || cs.position === 'fixed' || !shown(cs) || hiddenByAncestor(el)) continue;
      const box = el.getBoundingClientRect();
      const edge = box.left + el.clientLeft + el.clientWidth;
      let worst = null;
      for (const node of el.childNodes) {
        let r;
        let what;
        let allowed = 0;
        if (node.nodeType === 3) {
          if (!node.textContent.trim()) continue;
          const range = document.createRange();
          range.selectNodeContents(node);
          r = range.getBoundingClientRect();
          what = `text "${node.textContent.replace(/\s+/g, ' ').trim().slice(0, 40)}"`;
        } else if (node.nodeType === 1) {
          const ccs = getComputedStyle(node);
          if (!shown(ccs) || ccs.position === 'absolute' || ccs.position === 'fixed') continue;
          r = node.getBoundingClientRect();
          if (r.width === 0 && r.height === 0) continue;
          allowed = Math.max(0, -(parseFloat(ccs.marginRight) || 0)); // a bleed on purpose
          what = desc(node);
        } else {
          continue;
        }
        const by = r.right - edge - allowed;
        if (by > 4 && (!worst || by > worst.by)) worst = { by, what }; // a few px is an icon's edge, not a spill
      }
      if (worst) out.innerOverflow.push({ el: desc(el), child: worst.what, by: Math.round(worst.by), width: Math.round(el.clientWidth) });
    }
    out.innerOverflow = out.innerOverflow.slice(0, 12);
  }

  // WordPress admin notices (visible ones).
  out.notices = [];
  for (const n of document.querySelectorAll('#wpbody-content .notice, #wpbody-content div.error, #wpbody-content div.updated')) {
    if (!rendered(n)) continue;
    const type = n.matches('.notice-error, div.error') ? 'error' : n.matches('.notice-warning') ? 'warning' : n.matches('.notice-success, div.updated') ? 'success' : 'info';
    out.notices.push({ type, text: text(n).slice(0, 240) });
  }

  // Accessibility signals.
  const a11y = { imgNoAlt: [], emptyLinks: [], emptyButtons: [], unlabeled: [], duplicateIds: [] };
  for (const img of document.querySelectorAll('img:not([alt])')) {
    if (!hiddenFromAT(img)) a11y.imgNoAlt.push((img.getAttribute('src') || '').split('/').pop().slice(0, 80) || desc(img));
  }
  for (const a of document.querySelectorAll('a[href]')) {
    if (hiddenFromAT(a) || !rendered(a)) continue;
    if (!accName(a)) a11y.emptyLinks.push(`${desc(a)} → ${(a.getAttribute('href') || '').slice(0, 80)}`);
  }
  for (const b of document.querySelectorAll('button, [role="button"], input[type="button"]')) {
    if (hiddenFromAT(b) || !rendered(b)) continue;
    if (!accName(b)) a11y.emptyButtons.push(desc(b));
  }
  for (const f of document.querySelectorAll('input, select, textarea')) {
    const type = (f.getAttribute('type') || '').toLowerCase();
    if (['hidden', 'submit', 'button', 'reset', 'image'].includes(type)) continue;
    if (hiddenFromAT(f) || !rendered(f)) continue;
    const labelled = [...(f.labels || [])].some((l) => l.textContent.trim())
      || (f.getAttribute('aria-label') || '').trim()
      || (f.getAttribute('aria-labelledby') || '').split(/\s+/).some((id) => document.getElementById(id)?.textContent.trim())
      || (f.getAttribute('title') || '').trim();
    if (!labelled) a11y.unlabeled.push(`${desc(f)}${f.name ? ` [name=${f.name}]` : ''}${f.placeholder ? ' (placeholder only)' : ''}`);
  }
  const idCount = new Map();
  // Hidden inputs are left out: wp_nonce_field() prints id="_wpnonce" in every form.
  for (const el of document.querySelectorAll('[id]:not(input[type="hidden"])')) idCount.set(el.id, (idCount.get(el.id) || 0) + 1);
  for (const [id, n] of idCount) if (n > 1 && id) a11y.duplicateIds.push(`${id} ×${n}`);
  out.a11y = a11y;

  // Links, split by where they are.
  const same = (href) => { try { return new URL(href, location.href).origin === location.origin; } catch { return false; } };
  const abs = (a) => { try { return new URL(a.getAttribute('href'), location.href).href; } catch { return ''; } };
  out.links = [];
  out.adminMenu = [];
  out.adminBar = [];
  out.external = 0;
  for (const a of document.querySelectorAll('a[href]')) {
    const raw = a.getAttribute('href') || '';
    if (/^(mailto|tel|javascript|data):/i.test(raw)) continue;
    const href = abs(a);
    if (!href) continue;
    if (!same(href)) { out.external++; continue; }
    if (a.closest('#adminmenu')) out.adminMenu.push(href);
    else if (a.closest('#wpadminbar')) out.adminBar.push(href);
    else out.links.push(href);
  }
  out.links = [...new Set(out.links)];
  out.adminMenu = [...new Set(out.adminMenu)];
  out.adminBar = [...new Set(out.adminBar)];
  out.ids = opts.isPublic ? [...idCount.keys()] : [];
  return out;
}

/**
 * Wait for the block editor (and, for pages, the template and its server
 * previews), then report: blocks, invalid blocks, warnings, the lock settings,
 * blocks left in the default editing mode, the rendering mode, the Welcome
 * Guide and the editor's error notices.
 */
export async function blockEditorFacts(timeoutMs) {
  const t0 = Date.now();
  const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
  const out = { ready: false };
  while (Date.now() - t0 < timeoutMs) {
    try {
      const count = window.wp.data.select('core/block-editor').getBlocks().length;
      // A new, empty post has no blocks: then wait for the editor to say it is ready.
      const edReady = window.wp.data.select('core/editor').__unstableIsEditorReady?.();
      if (count || (edReady && Date.now() - t0 > 3000)) { out.ready = true; break; }
    } catch (e) {}
    if (document.querySelector('.editor-post-locked-modal')) break;
    await sleep(250);
  }
  if (out.ready) {
    while (Date.now() - t0 < timeoutMs) {
      const doc = document.querySelector('iframe[name="editor-canvas"]')?.contentDocument;
      let hasBlocks = true;
      try { hasBlocks = window.wp.data.select('core/block-editor').getBlocks().length > 0; } catch (e) {}
      const ok = doc && doc.body && (!hasBlocks || doc.querySelector('.wp-block-cover, .wp-block-post-content > *, .is-root-container > *'))
        && !doc.querySelector('.components-spinner, .block-editor-block-list__block.is-loading')
        && !/Loading…/.test(doc.body.innerText);
      if (ok) break;
      await sleep(250);
    }
    await sleep(1200);
  }
  out.lockedModal = !!document.querySelector('.editor-post-locked-modal');
  out.loadMs = Date.now() - t0;
  if (!out.ready) return out;
  const be = wp.data.select('core/block-editor');
  const all = [];
  const walk = (id) => be.getBlocks(id).forEach((b) => { all.push(b); walk(b.clientId); });
  walk('');
  out.blocks = all.length;
  out.invalid = all.filter((b) => b.isValid === false).map((b) => b.name);
  const canvas = document.querySelector('iframe[name="editor-canvas"]')?.contentDocument;
  const warns = [...document.querySelectorAll('.block-editor-warning'), ...(canvas ? canvas.querySelectorAll('.block-editor-warning') : [])];
  out.warnings = warns.length;
  out.warningTexts = warns.map((w) => w.textContent.replace(/\s+/g, ' ').trim().slice(0, 150)).slice(0, 5);
  const settings = be.getSettings();
  out.templateLock = settings.templateLock ?? null;
  out.codeEditingEnabled = settings.codeEditingEnabled ?? null;
  out.canLockBlocks = settings.canLockBlocks ?? null;
  const modeOf = (id) => { try { return be.getBlockEditingMode(id); } catch (e) { return '?'; } };
  const post = wp.data.select('core/editor').getCurrentPost?.() || {};
  out.postType = post.type || '';
  // Blocks of the page itself (inside Post Content when the template is shown).
  const pc = all.find((b) => b.name === 'core/post-content');
  const pageBlocks = [];
  const walkPage = (id) => be.getBlocks(id).forEach((b) => { pageBlocks.push(b); walkPage(b.clientId); });
  walkPage(pc ? pc.clientId : '');
  out.pageBlocks = pageBlocks.length;
  out.defaultModeBlocks = pageBlocks.filter((b) => modeOf(b.clientId) === 'default').map((b) => b.name);
  out.mode = wp.data.select('core/editor').getRenderingMode?.() || '?';
  out.welcomeGuide = !!document.querySelector('.edit-post-welcome-guide, .editor-welcome-guide, .components-guide');
  const visible = (el) => { const r = el.getBoundingClientRect(); return r.width > 0 && r.height > 0; };
  out.inserterVisible = [...document.querySelectorAll('.editor-document-tools__inserter-toggle, .edit-post-header-toolbar__inserter-toggle')].some(visible);
  try {
    out.notices = wp.data.select('core/notices').getNotices().filter((n) => n.status === 'error' || n.status === 'warning').map((n) => ({ status: n.status, text: String(n.content || '').slice(0, 200) }));
  } catch (e) { out.notices = []; }
  out.save = [...document.querySelectorAll('.editor-header button, .edit-post-header button')].map((b) => b.textContent.trim()).filter((t) => /^(Save|Update|Publish|Save draft)$/.test(t));
  return out;
}

/** Wait for the Site Editor to draw (a best effort; returns what it saw). */
export async function siteEditorFacts(timeoutMs) {
  const t0 = Date.now();
  const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
  let ready = false;
  while (Date.now() - t0 < timeoutMs) {
    const shell = document.querySelector('.edit-site-layout, .edit-site');
    const busy = document.querySelector('.edit-site-layout .components-spinner, .edit-site-canvas-loader');
    if (shell && !busy) { ready = true; break; }
    await sleep(250);
  }
  await sleep(2000);
  let notices = [];
  try { notices = wp.data.select('core/notices').getNotices().filter((n) => n.status === 'error').map((n) => String(n.content || '').slice(0, 200)); } catch (e) {}
  return { ready, loadMs: Date.now() - t0, notices };
}
