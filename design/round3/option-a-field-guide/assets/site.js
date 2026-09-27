/*!
 * Figure One: shared progressive enhancement for every page (option A).
 * =============================================================================
 * Load order at the end of <body>:
 *   <script src="assets/data.js" defer></script>     (../assets/ from members/)
 *   <script src="assets/site.js" defer></script>
 *
 * Every page must read and navigate with JS off. Each module below is ONE
 * self-contained function, so it maps to one WordPress block/pattern or one
 * SSG partial. Modules look for their own hooks and do nothing if absent.
 *
 * Hooks (see DESIGN.md, "Widgets and hooks"). Round 3 removed the Home strip, the net card,
 * the next-net line, the calendar file, the quiz, the join-trail router and the sub-nav
 * tab scroller: none of them has a place in placement-map.md.
 *   [data-menu-toggle]            header menu button (aria-expanded, aria-controls)
 *   .term / .term-note            tap-to-define glossary terms (About, How it works; never on Home)
 *   [data-copy="radio"], [data-copy-text]  copy the radio settings / any text
 *   [data-widget="coming-up"]     upcoming list (data-days, data-limit, data-types)
 *   [data-next-meeting]           inline "Next: Sat, Oct 10" for one meeting id
 *   [data-next-meeting-block]     a .dateblock <time> for one meeting id (Home #visit)
 *   [data-widget="meetings-next"] next workshop / training meeting dates (data-ids)
 *   [data-widget="rota"]          <tbody> for the net-control rota (members hub)
 *   [data-widget="winlink-schedule"] <tbody> for the Winlink schedule (members exercises)
 *   [data-start][data-end]        any static dated item: past ones hide/dim, live ones get "Happening now"
 *   [data-doclib]                 document library search + category chips (?q= &cat=)
 *   [data-doc-suggest]            live document suggestions under a search field
 *   [data-toc]                    side index / Contents bar scroll-spy (one IntersectionObserver)
 *   (any page#hash)               deep-link landing: re-settles on the target once fonts/widgets load
 *   #fig1-slip                    Home Fig. 1: the ICS-213 slip travels the arc once
 *
 * Demo helpers: ?today=YYYY-MM-DD (from data.js), ?now=HH:MM (clock for the rota's next-net row),
 * ?verify=1 (outline [data-verify] facts).
 * =============================================================================
 */
(function () {
  'use strict';

  var doc = document;
  var root = doc.documentElement;
  root.classList.add('js');

  var S = window.SPOKARES || null;
  var F = S && S.fn;
  var params = new URLSearchParams(window.location.search);
  var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var GROUPS = (S && S.links.groupsio.main) || 'https://spokaneares-acs.groups.io/g/main';
  var GROUPS_CAL = (S && S.links.groupsio.calendar) || GROUPS + '/calendar';

  /* ---------------------------------------------------------------- utils */
  function $(sel, ctx) { return (ctx || doc).querySelector(sel); }
  function $$(sel, ctx) { return Array.prototype.slice.call((ctx || doc).querySelectorAll(sel)); }
  function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
  function v(flag) { return flag ? ' data-verify="true"' : ''; }
  function run(name, fn) { try { fn(); } catch (e) { if (window.console) console.warn('[site.js] ' + name + ':', e); } }
  function addDaysIso(isoDate, n) { var d = F.parse(isoDate); d.setDate(d.getDate() + n); return F.iso(d); }
  var DOW = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
  var MON = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

  /* "Sat–Sun, Sep 26–27" or "Tue, Sep 29" */
  function fmtRange(start, end) {
    if (!end || end === start) return F.fmtDate(start, 'short');
    var a = F.parse(start), b = F.parse(end);
    if (a.getMonth() === b.getMonth()) return DOW[a.getDay()] + '–' + DOW[b.getDay()] + ', ' + MON[a.getMonth()] + ' ' + a.getDate() + '–' + b.getDate();
    return F.fmtDate(start, 'short') + ' to ' + F.fmtDate(end, 'short');
  }
  function fmtTimes(item) {
    if (item.displayTime) return item.displayTime.toLowerCase();
    if (!item.time) return '';
    var t = F.fmtTime(item.time);
    if (item.endTime) t += '–' + F.fmtTime(item.endTime);
    return t;
  }

  /* Clock: mockup "today" comes from data.js (asOf or ?today=). The time of day is
     ?now=HH:MM, or the real Pacific clock when the real date equals "today". */
  function laNow() {
    try {
      var parts = {};
      new Intl.DateTimeFormat('en-CA', { timeZone: 'America/Los_Angeles', year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', hourCycle: 'h23' })
        .formatToParts(new Date()).forEach(function (p) { parts[p.type] = p.value; });
      return { iso: parts.year + '-' + parts.month + '-' + parts.day, min: (+parts.hour % 24) * 60 + (+parts.minute) };
    } catch (e) {
      var d = new Date();
      return { iso: F.iso(d), min: d.getHours() * 60 + d.getMinutes() };
    }
  }
  function clock() {
    var today = F.today(), min = null, m = /^(\d{1,2}):(\d{2})$/.exec(params.get('now') || '');
    if (m) min = (+m[1]) * 60 + (+m[2]);
    else { var real = laNow(); if (real.iso === today) min = real.min; }
    return { today: today, min: min };
  }

  /* The next Tuesday net at 20:00 Pacific, rolled to next week after 21:00 on a Tuesday. */
  function netState() {
    var c = clock();
    var net = F.nextNet(c.today);
    if (net && net.date === c.today && c.min !== null && c.min >= 21 * 60) net = F.nextNet(addDaysIso(c.today, 1));
    var onAir = !!(net && net.date === c.today && c.min !== null && c.min >= 20 * 60 && c.min < 21 * 60);
    return { net: net, onAir: onAir, tonight: !!(net && net.date === c.today), today: c.today };
  }
  function isPublicEvent(item) { return !/^shares/.test(item.id || ''); }
  function shortTitle(t) { return String(t || '').replace(/^Spokane County /, ''); }

  /* Upcoming items, with same-day workshops merged. */
  function upcoming(opts) {
    var items = F.upcoming(opts).filter(function (x) { return x && isPublicEvent(x); });
    var out = [];
    items.forEach(function (x) {
      if (x.id && x.id.indexOf('winlink-workshop-') === 0) {
        var host = out.filter(function (y) { return y.date === x.date && y.id && y.id.indexOf('workshop-') === 0; })[0];
        if (host) { host.after = 'Winlink workshop after'; host.afterVerify = true; return; }
      }
      out.push(Object.assign({}, x));
    });
    return out;
  }

  /* ================================================================ modules */

  /* Header menu (phones and tablets). Without JS the nav is a plain visible list. */
  function menu() {
    var btn = $('[data-menu-toggle]');
    if (!btn) return;
    var nav = doc.getElementById(btn.getAttribute('aria-controls'));
    if (!nav) return;
    function set(open) { btn.setAttribute('aria-expanded', open ? 'true' : 'false'); nav.classList.toggle('is-open', open); }
    btn.addEventListener('click', function () { set(btn.getAttribute('aria-expanded') !== 'true'); });
    doc.addEventListener('keydown', function (e) { if (e.key === 'Escape' && btn.getAttribute('aria-expanded') === 'true') { set(false); btn.focus(); } });
    doc.addEventListener('click', function (e) { if (btn.getAttribute('aria-expanded') === 'true' && !nav.contains(e.target) && !btn.contains(e.target)) set(false); });
  }

  /* Reviewer aid */
  function verifyMode() { if (params.get('verify') === '1') root.classList.add('verify-on'); }

  /* Tap-to-define terms. Margin notes (About, >= 1200px) stay open. */
  function terms() {
    var terms = $$('.term[aria-controls]');
    if (!terms.length) return;
    var wide = window.matchMedia('(min-width: 1200px)');
    function sync() {
      terms.forEach(function (t) {
        var margin = wide.matches && t.closest('.prose--margin');
        if (margin) t.setAttribute('aria-expanded', 'true');
        else if (!t.dataset.touched) t.setAttribute('aria-expanded', 'false');
      });
    }
    sync();
    if (wide.addEventListener) wide.addEventListener('change', sync);
    doc.addEventListener('click', function (e) {
      var t = e.target.closest && e.target.closest('.term[aria-controls]');
      if (!t) return;
      if (wide.matches && t.closest('.prose--margin')) { var n = doc.getElementById(t.getAttribute('aria-controls')); if (n) { n.classList.add('fade'); setTimeout(function () { n.classList.remove('fade'); }, 200); } return; }
      t.dataset.touched = '1';
      t.setAttribute('aria-expanded', t.getAttribute('aria-expanded') === 'true' ? 'false' : 'true');
    });
    doc.addEventListener('keydown', function (e) {
      if (e.key !== 'Escape') return;
      var t = doc.activeElement;
      if (t && t.classList && t.classList.contains('term') && t.getAttribute('aria-expanded') === 'true' && !(wide.matches && t.closest('.prose--margin'))) t.setAttribute('aria-expanded', 'false');
    });
  }

  /* Status line helper: nearest .status-msg inside [data-copy-scope], else a shared live region. */
  function announce(from, msg) {
    var scope = from.closest('[data-copy-scope]');
    var out = scope && scope.querySelector('.status-msg');
    if (!out) {
      out = doc.getElementById('site-status');
      if (!out) { out = doc.createElement('p'); out.id = 'site-status'; out.className = 'vh'; out.setAttribute('role', 'status'); doc.body.appendChild(out); }
    }
    out.textContent = '';
    setTimeout(function () { out.textContent = msg; }, 30);
  }
  function copyText(text) {
    if (navigator.clipboard && window.isSecureContext !== false) {
      return navigator.clipboard.writeText(text).catch(function () { return fallbackCopy(text); });
    }
    return fallbackCopy(text);
  }
  function fallbackCopy(text) {
    return new Promise(function (resolve, reject) {
      var ta = doc.createElement('textarea');
      ta.value = text; ta.setAttribute('readonly', ''); ta.style.position = 'fixed'; ta.style.opacity = '0'; ta.style.left = '-9999px';
      doc.body.appendChild(ta); ta.select();
      var ok = false; try { ok = doc.execCommand('copy'); } catch (e) { ok = false; }
      doc.body.removeChild(ta);
      if (ok) resolve(); else reject(new Error('copy failed'));
    });
  }
  /* Copy buttons: data-copy="radio", or data-copy-text="…" */
  function copyButtons() {
    doc.addEventListener('click', function (e) {
      var b = e.target.closest && e.target.closest('[data-copy], [data-copy-text]');
      if (!b) return;
      var kind = b.getAttribute('data-copy');
      var text = b.getAttribute('data-copy-text') || (kind === 'radio' ? (S ? S.radio.copyText : 'W7GBU 147.300 MHz, +600 kHz offset, 100 Hz tone') : '');
      if (!text) return;
      var label = b.querySelector('[data-label]') || null;
      copyText(text).then(function () {
        announce(b, 'Copied: ' + text);
        if (label) { var old = label.textContent; label.textContent = 'Copied'; setTimeout(function () { label.textContent = old; }, 1800); }
      }, function () {
        announce(b, 'Could not copy automatically. Select the text and copy it: ' + text);
      });
    });
  }

  /* Upcoming list as .events with date blocks. */
  function comingUp() {
    $$('[data-widget="coming-up"]').forEach(function (el) {
      var days = +(el.getAttribute('data-days') || 21);
      var limit = +(el.getAttribute('data-limit') || 6);
      var types = (el.getAttribute('data-types') || 'exercise,meeting,public-service,on-air').split(',');
      var today = F.today();
      var items = upcoming({ from: today, days: days, types: types }).slice(0, limit);
      if (!items.length) { el.innerHTML = '<p class="muted">Nothing scheduled in the next ' + days + ' days. See the <a href="' + GROUPS_CAL + '">groups.io calendar</a>.</p>'; return; }
      el.innerHTML = '<ul class="events">' + items.map(function (x) {
        var dm = F.fmtDate(x.date, 'dm');
        var range = x.endDate && x.endDate !== x.date;
        var t = fmtTimes(x);
        return '<li class="event' + (x.inProgress ? ' event--now' : '') + '"' + v(x.verify) + '>' +
          '<span class="dateblock' + (range ? ' dateblock--range' : '') + '" aria-hidden="true"><span class="dateblock__mon">' + dm.mon + '</span><span class="dateblock__day">' + (range ? dm.day + '–' + F.parse(x.endDate).getDate() : dm.day) + '</span><span class="dateblock__dow">' + dm.dow + '</span></span>' +
          '<div class="event__body"><p class="event__title">' + esc(shortTitle(x.title)) + '</p>' +
          '<p class="event__meta"><span class="vh">' + esc(fmtRange(x.date, x.endDate)) + '. </span>' + esc([t, x.where].filter(Boolean).join(', ')) + (x.after ? ' (' + esc(x.after) + ')' : '') + '</p></div></li>';
      }).join('') + '</ul>';
    });
  }

  /* Next dates for meetings. data-ids="workshop,third-thursday"; one inline: <span data-next-meeting="workshop">;
     or a date block: <time class="dateblock" data-next-meeting-block="workshop"> with vh "Next:", __mon, __day, __dow. */
  function meetingsNext() {
    function nextOf(id) { return F.nextMeetings(F.today(), 1).filter(function (x) { return x.id === id; })[0]; }
    $$('[data-next-meeting]').forEach(function (el) {
      var m = nextOf(el.getAttribute('data-next-meeting'));
      if (m && m.dates.length) el.textContent = 'Next: ' + F.fmtDate(m.dates[0], 'short');
    });
    $$('[data-next-meeting-block]').forEach(function (el) {
      var m = nextOf(el.getAttribute('data-next-meeting-block'));
      if (!m || !m.dates.length) return;
      var dm = F.fmtDate(m.dates[0], 'dm');
      el.setAttribute('datetime', m.dates[0]);
      [['mon', dm.mon], ['day', dm.day], ['dow', dm.dow]].forEach(function (p) { var n = el.querySelector('.dateblock__' + p[0]); if (n) n.textContent = p[1]; });
    });
    $$('[data-widget="meetings-next"]').forEach(function (el) {
      var ids = (el.getAttribute('data-ids') || 'workshop,third-thursday').split(',');
      var list = F.nextMeetings(F.today(), 2).filter(function (m) { return ids.indexOf(m.id) !== -1 && m.dates.length; });
      el.innerHTML = '<ul>' + list.map(function (m) {
        var t = m.displayTime ? m.displayTime.toLowerCase() : (m.start ? F.fmtTime(m.start) + (m.end ? '–' + F.fmtTime(m.end) : '') : '');
        return '<li' + v(m.verify) + '><b>' + esc(m.name) + ':</b> ' + esc(m.dates.map(function (d) { return F.fmtDate(d, 'short'); }).join(' and ')) + (t ? ', ' + esc(t) : '') + '</li>';
      }).join('') + '</ul>';
    });
  }

  /* Rota: fills a <tbody data-widget="rota"> (members hub). The row for the next net gets .is-next. */
  function rota() {
    $$('[data-widget="rota"]').forEach(function (tb) {
      var st = netState();
      var today = F.today();
      tb.innerHTML = S.rota.map(function (r) {
        var net = F.netOn(r.date) || {};
        var past = r.date < today;
        var next = st.net && r.date === st.net.date;
        /* Note text comes from data.js (fn.netOn): "Simplex", "Winlink night" or "GMRS net, 7:30 PM". 12-hour times only. */
        var note = net.mode === 'winlink' ? '<a href="exercises.html#winlink-assignments">' + esc(net.note) + '</a>' : esc(net.note || '');
        var who = r.open ? '<a href="#rota-open"><b>Open</b></a>' : esc(r.callsign);   /* call signs only (placement-map §0.9) */
        return '<tr class="' + (past ? 'is-past' : '') + (next ? ' is-next' : '') + '"><td class="num">' + esc(F.fmtDate(r.date, 'day')) + (past ? ' <span class="vh">(past)</span>' : '') + '</td><td>' + who + '</td><td' + v(net.verify) + '>' + note + '</td></tr>';
      }).join('');
    });
  }

  /* Winlink schedule: fills a <tbody data-widget="winlink-schedule"> (members exercises). */
  function winlinkSchedule() {
    $$('[data-widget="winlink-schedule"]').forEach(function (tb) {
      var today = F.today();
      var nextDone = false;
      tb.innerHTML = S.winlink.assignments.map(function (a) {
        var past = a.date < today, next = !past && !nextDone;
        if (next) nextDone = true;
        return '<tr class="' + (past ? 'is-past' : '') + (next ? ' is-next' : '') + '"><td class="num">' + esc(F.fmtDate(a.date, 'short')) + '</td><td>' + esc(a.form) + '</td><td>' + esc(a.task) + '</td></tr>';
      }).join('');
    });
  }

  /* Static dated items: <li data-start="2026-10-03" data-end="2026-10-04"> inside a container with data-past="hide|dim". */
  function datedItems() {
    var today = F ? F.today() : null;
    if (!today) return;
    $$('[data-start]').forEach(function (el) {
      var start = el.getAttribute('data-start'), end = el.getAttribute('data-end') || start;
      var box = el.closest('[data-past]');
      if (end < today) {
        el.classList.add('is-past');
        if (box && box.getAttribute('data-past') === 'hide') el.hidden = true;
      } else if (start <= today && today <= end) {
        el.classList.add('is-now');
        var slot = el.querySelector('[data-now-slot]');
        if (slot && !slot.querySelector('.now-tag')) slot.insertAdjacentHTML('afterbegin', '<span class="now-tag">Happening now</span>');
      }
    });
  }

  /* Document library: filters the static list in place. ?q= and ?cat= keep a view shareable. */
  function docLibrary() {
    $$('[data-doclib]').forEach(function (lib) {
      var form = $('form', lib);
      var input = $('input[name="q"]', lib);
      var chips = $$('[data-cat]', lib).filter(function (c) { return c.tagName === 'BUTTON'; });
      var docs = $$('.doc', lib);
      var groups = $$('.docgroup', lib);
      var count = $('[data-doclib-count]', lib);
      var empty = $('[data-doclib-empty]', lib);
      var total = docs.length;
      var hay = docs.map(function (d) { return (d.textContent + ' ' + (d.getAttribute('data-tags') || '') + ' ' + d.id).toLowerCase(); });
      var state = { q: params.get('q') || '', cat: params.get('cat') || '' };
      if (input) input.value = state.q;

      function apply(updateUrl) {
        var words = state.q.toLowerCase().split(/\s+/).filter(Boolean), shown = 0;
        docs.forEach(function (d, i) {
          var okCat = !state.cat || d.getAttribute('data-cat') === state.cat;
          var okQ = words.every(function (w) { return hay[i].indexOf(w) !== -1; });
          d.hidden = !(okCat && okQ);
          if (!d.hidden) shown++;
        });
        groups.forEach(function (g) { g.hidden = !$$('.doc', g).some(function (d) { return !d.hidden; }); });
        chips.forEach(function (c) { c.setAttribute('aria-pressed', (c.getAttribute('data-cat') || '') === state.cat ? 'true' : 'false'); });
        if (count) {
          var catLabel = state.cat ? (chips.filter(function (c) { return c.getAttribute('data-cat') === state.cat; })[0] || {}).textContent : '';
          count.textContent = (!state.q && !state.cat) ? 'Showing all ' + total + ' documents.' :
            shown + ' of ' + total + ' documents' + (catLabel ? ' in ' + catLabel.replace(/\s*\d+\s*$/, '').trim() : '') + (state.q ? ' matching “' + state.q + '”' : '') + '.';
        }
        if (empty) empty.hidden = shown !== 0;
        if (updateUrl && window.history && history.replaceState) {
          var u = new URL(window.location.href);
          state.q ? u.searchParams.set('q', state.q) : u.searchParams.delete('q');
          state.cat ? u.searchParams.set('cat', state.cat) : u.searchParams.delete('cat');
          u.hash = '';
          history.replaceState(null, '', u.toString());
        }
      }
      var timer;
      if (input) input.addEventListener('input', function () { clearTimeout(timer); timer = setTimeout(function () { state.q = input.value.trim(); apply(true); }, 120); });
      if (form) form.addEventListener('submit', function (e) { e.preventDefault(); state.q = input ? input.value.trim() : ''; apply(true); });
      chips.forEach(function (c) { c.addEventListener('click', function () { state.cat = c.getAttribute('data-cat') || ''; apply(true); }); });
      $$('[data-doclib-reset]', lib).forEach(function (b) { b.addEventListener('click', function () { state = { q: '', cat: '' }; if (input) input.value = ''; apply(true); if (input) input.focus(); }); });
      apply(false);
    });
  }

  /* Live suggestions from data.js under a search field (members hub). The form still submits to documents.html?q= */
  function docSuggest() {
    $$('[data-doc-suggest]').forEach(function (form) {
      var input = $('input[name="q"]', form);
      var out = $('[data-suggest-out]', form.parentNode) || null;
      if (!input || !out) return;
      var base = form.getAttribute('action') || 'documents.html';
      input.addEventListener('input', function () {
        var q = input.value.trim();
        if (q.length < 2) { out.innerHTML = ''; return; }
        var hits = F.documents({ q: q });
        var cats = {}; S.documentCategories.forEach(function (c) { cats[c.id] = c.label; });
        out.innerHTML = hits.length ?
          '<ul class="suggest">' + hits.slice(0, 5).map(function (d) { return '<li><a href="' + base + '#' + esc(d.id) + '">' + esc(d.title) + '<small>' + esc(cats[d.category] || '') + (d.format ? ', ' + esc(d.format) : '') + '</small></a></li>'; }).join('') +
          '<li><a href="' + base + '?q=' + encodeURIComponent(q) + '"><b>See all ' + hits.length + ' results</b></a></li></ul>' :
          '<p class="hint" style="margin-top:8px">No documents match “' + esc(q) + '”. Try a form number or a word like “preamble”.</p>';
      });
    });
  }

  /* Scroll-spy for [data-toc] (side index) and .toc-bar (mobile). One IntersectionObserver. */
  function toc() {
    var navs = $$('[data-toc]');
    if (!navs.length || !('IntersectionObserver' in window)) return;
    var links = [];
    navs.forEach(function (n) { links = links.concat($$('a[href^="#"]', n)); });
    var ids = []; links.forEach(function (a) { var id = a.getAttribute('href').slice(1); if (id && ids.indexOf(id) === -1) ids.push(id); });
    var targets = ids.map(function (id) { return doc.getElementById(id); }).filter(Boolean);
    if (!targets.length) return;
    var nowLabel = $('.toc-bar__now');
    function update() {
      var line = window.innerHeight * 0.3, current = targets[0];
      targets.forEach(function (t) { if (t.getBoundingClientRect().top - line <= 0) current = t; });
      links.forEach(function (a) {
        var on = a.getAttribute('href') === '#' + current.id;
        a.classList.toggle('is-active', on);
        if (on) a.setAttribute('aria-current', 'true'); else a.removeAttribute('aria-current');
      });
      if (nowLabel) { var l = links.filter(function (a) { return a.getAttribute('href') === '#' + current.id; })[0]; nowLabel.textContent = l ? l.textContent.replace(/^\s*\d+\s*/, '') : ''; }
    }
    var io = new IntersectionObserver(update, { rootMargin: '0px 0px -70% 0px', threshold: [0, 1] });
    targets.forEach(function (t) { io.observe(t); });
    /* The observer only fires when a heading crosses its band, so PageDown, End or a long jump can
       skip past it. A rAF-throttled scroll check with the same rule keeps the highlight honest. */
    var busy = false;
    window.addEventListener('scroll', function () { if (busy) return; busy = true; requestAnimationFrame(function () { busy = false; update(); }); }, { passive: true });
    update();
    $$('.toc-bar').forEach(function (bar) { bar.addEventListener('click', function (e) { if (e.target.closest('a')) bar.open = false; }); });
  }

  /* Home Fig. 1: the ICS-213 slip travels the arc once (1.6 s ease-out). The static markup
     already rests it at the EOC, so no-JS and reduced-motion users see the end state. */
  function fig1() {
    var slip = doc.getElementById('fig1-slip');
    if (!slip || reduceMotion) return;
    var path = slip.getAttribute('data-path');
    if (!path || !window.SVGAnimateMotionElement) return;
    var rest = slip.getAttribute('transform');
    slip.removeAttribute('transform');
    var anim = doc.createElementNS('http://www.w3.org/2000/svg', 'animateMotion');
    anim.setAttribute('path', path);
    anim.setAttribute('dur', '1.6s');
    anim.setAttribute('fill', 'freeze');
    anim.setAttribute('calcMode', 'spline');
    anim.setAttribute('keyTimes', '0;1');
    anim.setAttribute('keySplines', '0 0 .58 1');
    anim.setAttribute('begin', 'indefinite');
    slip.appendChild(anim);
    try { setTimeout(function () { anim.beginElement(); }, 500); }
    catch (e) { slip.setAttribute('transform', rest); }
  }

  /* ------------------------------------------------------------------ deep-link landing
     Arriving at page.html#section from another page: the head one-liner turns smooth scrolling off
     while the page loads. Widgets rendered from data.js and the web-font swap change the height of
     everything above the target, so the first jump can land short on long pages. Once fonts and
     images are in, correct the landing (unless the reader has started scrolling), then give in-page
     links their smooth scrolling back. The hash is read at settle time, so page scripts that consume
     it (documents.html#forms becomes ?cat=forms) are left alone. */
  function hashLanding() {
    if (!window.location.hash) { root.style.scrollBehavior = ''; return; }
    var touched = false;
    ['wheel', 'touchstart', 'keydown', 'mousedown'].forEach(function (t) { window.addEventListener(t, function () { touched = true; }, { once: true, passive: true }); });
    function settle() {
      var id = decodeURIComponent(window.location.hash.slice(1));
      var el = id && doc.getElementById(id);
      if (el && !touched) {
        var want = parseFloat(getComputedStyle(el).scrollMarginTop) || 0;
        if (Math.abs(el.getBoundingClientRect().top - want) > 4) el.scrollIntoView({ block: 'start' });
      }
      root.style.scrollBehavior = '';
    }
    var loaded = new Promise(function (r) { if (doc.readyState === 'complete') r(); else window.addEventListener('load', r); });
    Promise.all([loaded, doc.fonts && doc.fonts.ready]).then(function () { setTimeout(settle, 60); });
  }

  /* ------------------------------------------------------------------ boot */
  function boot() {
    run('verify', verifyMode);
    run('menu', menu);
    run('terms', terms);
    run('copy', copyButtons);
    run('toc', toc);
    run('doclib', docLibrary);
    run('fig1', fig1);
    if (!F) return;           /* data-driven widgets need data.js; their static fallbacks stay */
    run('comingUp', comingUp);
    run('meetingsNext', meetingsNext);
    run('rota', rota);
    run('winlink', winlinkSchedule);
    run('dated', datedItems);
    run('docSuggest', docSuggest);
  }
  function start() { boot(); run('hashLanding', hashLanding); }
  window.SiteUI = { clock: function () { return F && clock(); }, netState: function () { return F && netState(); } };
  if (doc.readyState === 'loading') doc.addEventListener('DOMContentLoaded', start); else start();
})();
