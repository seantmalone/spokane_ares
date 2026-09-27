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
 * Hooks (see DESIGN.md, "Widgets and hooks"):
 *   [data-menu-toggle]            header menu button (aria-expanded, aria-controls)
 *   .term / .term-note            tap-to-define glossary terms
 *   [data-copy="radio|path"]      copy radio settings / a link to my path
 *   [data-ics="weekly-net"]       add the Tuesday net to a calendar (.ics built here)
 *   [data-widget="this-week-strip"]  Home strip: next net, net control, flags, now/next
 *   [data-widget="net-card"]      next-net card (members hub, How it works)
 *   [data-widget="next-net-line"] one-line next net text
 *   [data-widget="coming-up"]     upcoming list (data-days, data-limit, data-types)
 *   [data-widget="meetings-next"] next workshop / training meeting dates (data-ids)
 *   [data-widget="rota"]          <tbody> for the net-control rota (members)
 *   [data-widget="winlink-next"]  next Winlink assignment
 *   [data-widget="winlink-schedule"] <tbody> for the Winlink schedule (members)
 *   [data-start][data-end]        any static dated item: past ones hide/dim, live ones get "Happening now"
 *   [data-doclib]                 document library search + category chips (?q= &cat=)
 *   [data-doc-suggest]            live document suggestions under a search field
 *   [data-toc]                    side index / Contents bar scroll-spy (one IntersectionObserver)
 *   .mnav                         members index: keep the current tab in view on phones; data-fade edge cue
 *   (any page#hash)               deep-link landing: re-settles on the target once fonts/widgets load
 *   [data-trail]                  join trail: ?from=unlicensed|licensed|ares|acs highlights a post
 *   [data-quiz]                   "Is this for me?" quiz (content in <script type="application/json" id="quiz-data">)
 *   #fig1-slip                    Home Fig. 1: the ICS-213 slip travels the arc once
 *
 * Demo helpers: ?today=YYYY-MM-DD (from data.js), ?now=HH:MM (clock for "On the air now"),
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
  function ext(label) { return '<span class="ext"><svg class="icon" aria-hidden="true"><use href="#i-external"/></svg><span class="vh">(' + esc(label) + ')</span></span>'; }
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
  function netWhen(st) {
    if (st.onAir) return '<span class="onair">On the air now</span>';
    if (st.tonight) return 'Tonight, 8:00 PM';
    return esc(F.fmtDate(st.net.date, 'short')) + ', 8:00 PM';
  }
  /* afterLabel: the words "Net control" are already on screen just before this (the strip's glossary term). */
  function netControlHtml(net, withName, afterLabel) {
    var lead = afterLabel ? '' : 'Net control: ';
    if (net.netControl) return lead + '<b>' + esc(net.netControl) + '</b>' + (withName && net.netControlName ? ' <span class="muted">' + esc(net.netControlName) + '</span>' : '');
    if (net.open) return (afterLabel ? 'slot open: ' : 'Net control slot open: ') + '<a href="' + GROUPS + '">volunteer on groups.io' + ext('opens groups.io') + '</a>';
    return lead + 'see the <a href="' + GROUPS_CAL + '">groups.io calendar' + ext('opens groups.io') + '</a>';
  }
  function netFlags(net) {
    var out = [];
    if (net.mode === 'simplex') out.push('Fifth Tuesday: starts on simplex, then moves to W7GBU.');
    if (net.mode === 'winlink') out.push('Winlink night: have paper and pencil ready for the assignment.');
    if (net.gmrsSameDay) out.push('The ACS GMRS net meets at 7:30 PM, before this net.');
    return out;
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
  function pathUrl() {
    var u = new URL(window.location.href);
    var from = u.searchParams.get('from');
    u.hash = 'join';
    if (!from) u.searchParams.delete('from');
    return u.toString();
  }

  /* Copy buttons: data-copy="radio" | "path", or data-copy-text="…" */
  function copyButtons() {
    doc.addEventListener('click', function (e) {
      var b = e.target.closest && e.target.closest('[data-copy], [data-copy-text]');
      if (!b) return;
      var kind = b.getAttribute('data-copy');
      var text = b.getAttribute('data-copy-text') || (kind === 'radio' ? (S ? S.radio.copyText : 'W7GBU 147.300 MHz, +600 kHz offset, 100 Hz tone') : kind === 'path' ? pathUrl() : '');
      if (!text) return;
      var label = b.querySelector('[data-label]') || null;
      copyText(text).then(function () {
        announce(b, kind === 'path' ? 'Link copied. It opens this page with your starting post highlighted.' : 'Copied: ' + text);
        if (label) { var old = label.textContent; label.textContent = 'Copied'; setTimeout(function () { label.textContent = old; }, 1800); }
      }, function () {
        announce(b, 'Could not copy automatically. Select the text and copy it: ' + text);
      });
    });
  }

  /* Add to calendar: a weekly .ics built in the browser (RRULE), no server. */
  function icsButtons() {
    doc.addEventListener('click', function (e) {
      var b = e.target.closest && e.target.closest('[data-ics]');
      if (!b || !F) return;
      var st = netState();
      var d = st.net ? st.net.date.replace(/-/g, '') : '20260929';
      var r = S.radio.primary;
      var lines = [
        'BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//Spokane County ARES-ACS//Tuesday net//EN', 'CALSCALE:GREGORIAN', 'METHOD:PUBLISH',
        'BEGIN:VTIMEZONE', 'TZID:America/Los_Angeles',
        'BEGIN:DAYLIGHT', 'TZOFFSETFROM:-0800', 'TZOFFSETTO:-0700', 'TZNAME:PDT', 'DTSTART:19700308T020000', 'RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=2SU', 'END:DAYLIGHT',
        'BEGIN:STANDARD', 'TZOFFSETFROM:-0700', 'TZOFFSETTO:-0800', 'TZNAME:PST', 'DTSTART:19701101T020000', 'RRULE:FREQ=YEARLY;BYMONTH=11;BYDAY=1SU', 'END:STANDARD',
        'END:VTIMEZONE',
        'BEGIN:VEVENT',
        'UID:tuesday-net-w7gbu@spokares.org',
        'DTSTAMP:' + new Date().toISOString().replace(/[-:]/g, '').replace(/\.\d{3}/, ''),
        'DTSTART;TZID=America/Los_Angeles:' + d + 'T200000',
        'DTEND;TZID=America/Los_Angeles:' + d + 'T210000',
        'RRULE:FREQ=WEEKLY;BYDAY=TU',
        'SUMMARY:Spokane County ARES-ACS Tuesday net',
        'LOCATION:On the air: ' + r.call + ' ' + r.freq + ' MHz\\, ' + r.offset + '\\, ' + r.tone + ' tone',
        'DESCRIPTION:Weekly net on the ' + r.call + ' repeater\\, ' + r.freq + ' MHz (' + r.offset + '\\, ' + r.tone + ' tone). Visitors are welcome to check in after members. Listening needs no license.',
        'END:VEVENT', 'END:VCALENDAR'
      ];
      var blob = new Blob([lines.join('\r\n') + '\r\n'], { type: 'text/calendar;charset=utf-8' });
      var a = doc.createElement('a');
      a.href = URL.createObjectURL(blob);
      a.download = 'spokane-ares-acs-tuesday-net.ics';
      doc.body.appendChild(a); a.click(); doc.body.removeChild(a);
      setTimeout(function () { URL.revokeObjectURL(a.href); }, 2000);
      announce(b, 'Calendar file saved: the Tuesday net, every week at 8:00 PM. Open it to add it to your calendar.');
    });
  }

  /* Home: This-week strip. Slots: [data-slot="as-of|net-when|net-control|net-flag|now-next"] */
  function thisWeekStrip() {
    $$('[data-widget="this-week-strip"]').forEach(function (el) {
      var st = netState();
      if (!st.net) return;
      var slot = function (n) { return el.querySelector('[data-slot="' + n + '"]'); };
      if (slot('as-of')) slot('as-of').textContent = 'as of ' + F.fmtDate(st.today, 'short');
      if (slot('net-when')) slot('net-when').innerHTML = '<span class="strip__big">' + netWhen(st) + '</span>';
      if (slot('net-control')) slot('net-control').innerHTML = netControlHtml(st.net, false, true);
      if (slot('net-flag')) slot('net-flag').textContent = netFlags(st.net).join(' ');
      var nn = slot('now-next');
      if (nn) {
        var items = upcoming({ from: st.today, days: 35, types: ['exercise', 'meeting', 'public-service'] });
        var now = items.filter(function (x) { return x.inProgress; })[0];
        var meeting = items.filter(function (x) { return !x.inProgress && x.type === 'meeting'; })[0];
        var other = items.filter(function (x) { return !x.inProgress && x !== meeting; })[0];
        var pick = [now || other, meeting].filter(Boolean).sort(function (x, y) { return x.inProgress ? -1 : y.inProgress ? 1 : (x.date < y.date ? -1 : x.date > y.date ? 1 : 0); });
        nn.innerHTML = '<ul>' + pick.map(function (x) {
          var t = fmtTimes(x);
          if (x.inProgress) return '<li><span class="now-tag">Happening now</span>' + esc(shortTitle(x.title)) + (x.endDate ? ', through ' + esc(F.fmtDate(x.endDate, 'short')) : '') + '</li>';
          return '<li' + v(x.verify) + '><b class="tnum">' + esc(fmtRange(x.date, x.endDate)) + '</b> ' + esc(shortTitle(x.title)) + (t ? '<span class="muted">, ' + esc(t) + '</span>' : '') + '</li>';
        }).join('') + '</ul>';
      }
    });
  }

  /* Next-net card (members hub, How it works). data-names="true" shows the rota name. */
  function netCards() {
    $$('[data-widget="net-card"]').forEach(function (el) {
      var st = netState();
      if (!st.net) return;
      var names = el.getAttribute('data-names') === 'true';
      var r = S.radio.primary;
      var flags = netFlags(st.net);
      var wl = st.net.winlinkAssignment;
      el.innerHTML =
        '<div class="netcard" data-copy-scope>' +
          '<p class="netcard__when">' + netWhen(st) + '</p>' +
          '<p class="netcard__row"><span>Tuesday net</span><span class="netcard__settings">' + esc(r.call + ' ' + r.freq + ' MHz, ' + r.offset + ', ' + r.tone + ' tone') + '</span></p>' +
          '<p class="netcard__row"><span>' + netControlHtml(st.net, names) + '</span></p>' +
          flags.map(function (f) { return '<p class="netcard__flag" data-verify="true">' + esc(f) + '</p>'; }).join('') +
          (wl ? '<p class="netcard__open">Winlink assignment: ' + esc(wl.task) + ' <span class="muted">(' + esc(wl.form) + ')</span></p>' : '') +
          '<div class="btn-row">' +
            '<button type="button" class="btn btn--line btn--sm" data-copy="radio"><svg class="icon" aria-hidden="true"><use href="#i-copy"/></svg><span data-label>Copy radio settings</span></button>' +
            '<button type="button" class="btn btn--line btn--sm" data-ics="weekly-net"><svg class="icon" aria-hidden="true"><use href="#i-cal-add"/></svg>Add to calendar</button>' +
          '</div>' +
          '<p class="status-msg" role="status" aria-live="polite"></p>' +
        '</div>';
    });
  }

  function nextNetLines() {
    $$('[data-widget="next-net-line"]').forEach(function (el) {
      var st = netState();
      if (!st.net) return;
      var nc = st.net.netControl ? ', net control ' + st.net.netControl : '';
      el.innerHTML = (st.onAir ? '<span class="onair">On the air now</span>' : 'Next net: ' + netWhen(st)) + esc(nc);
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

  /* Next dates for meetings. data-ids="workshop,third-thursday"; or one inline: <span data-next-meeting="workshop"> */
  function meetingsNext() {
    $$('[data-next-meeting]').forEach(function (el) {
      var m = F.nextMeetings(F.today(), 1).filter(function (x) { return x.id === el.getAttribute('data-next-meeting'); })[0];
      if (m && m.dates.length) el.textContent = 'Next: ' + F.fmtDate(m.dates[0], 'short');
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

  /* Rota: fills a <tbody data-widget="rota">. data-names="true" to show names (members pages only). */
  function rota() {
    $$('[data-widget="rota"]').forEach(function (tb) {
      var names = tb.getAttribute('data-names') === 'true';
      var st = netState();
      var today = F.today();
      tb.innerHTML = S.rota.map(function (r) {
        var net = F.netOn(r.date) || {};
        var past = r.date < today;
        var next = st.net && r.date === st.net.date;
        var notes = [];
        if (net.mode === 'simplex') notes.push('Fifth Tuesday: simplex');
        if (net.mode === 'winlink') notes.push('Winlink night');
        if (net.gmrsSameDay) notes.push('ACS GMRS net at 1930');
        var who = r.open ? '<b>Open</b> <a href="#rota-open">I can take it</a>' : esc(r.callsign) + (names && r.name ? ', ' + esc(r.name) : '');
        return '<tr class="' + (past ? 'is-past' : '') + (next ? ' is-next' : '') + '"><td class="num">' + esc(F.fmtDate(r.date, 'short')) + (past ? ' <span class="vh">(past)</span>' : '') + '</td><td>' + who + '</td><td' + v(net.verify || net.gmrsSameDay) + '>' + esc(notes.join(', ')) + '</td></tr>';
      }).join('');
    });
  }

  function winlinkNext() {
    $$('[data-widget="winlink-next"]').forEach(function (el) {
      var today = F.today();
      var a = S.winlink.assignments.filter(function (x) { return x.date >= today; })[0];
      if (!a) return;
      el.innerHTML = '<p><b class="tnum">' + esc(F.fmtDate(a.date, 'short')) + ':</b> ' + esc(a.task) + ' <span class="muted">Form: ' + esc(a.form) + '.</span></p>' +
        '<p class="muted" style="font-size:var(--fs-label);margin-top:6px">' + esc(S.winlink.howTo) + '</p>';
    });
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

  /* Members index: on phones, scroll the current tab into view. */
  function membersNav() {
    var nav = $('.mnav');
    if (!nav) return;
    var cur = $('[aria-current="page"]', nav);
    var list = $('.mnav > ul', nav);
    if (!cur || !list) return;
    if (list.scrollWidth > list.clientWidth) list.scrollLeft = Math.max(0, cur.offsetLeft - 48);
    /* Fade whichever edges hide more tabs, so a phone user can tell the row continues both ways.
       Without JS the CSS default fades the right edge only. */
    function edges() {
      var max = list.scrollWidth - list.clientWidth;
      var l = list.scrollLeft > 4, r = list.scrollLeft < max - 4;
      list.setAttribute('data-fade', max <= 0 ? 'none' : (l ? 'l' : '') + (r ? 'r' : ''));
    }
    var busy = false;
    list.addEventListener('scroll', function () { if (busy) return; busy = true; requestAnimationFrame(function () { busy = false; edges(); }); }, { passive: true });
    window.addEventListener('resize', edges);
    edges();
  }

  /* Join trail: ?from= highlights the right post; the picker and door links update it in place. */
  var trailApi = { set: function () {} };
  function trail() {
    var box = $('[data-trail]');
    if (!box) return;
    var steps = $$('.trail__step', box);
    var START = { unlicensed: 0, licensed: 1, ares: 2, acs: 3 };
    var picks = $$('[data-from]');
    var share = $$('[data-trail-share]');
    function set(from, updateUrl) {
      var start = Object.prototype.hasOwnProperty.call(START, from) ? START[from] : -1;
      steps.forEach(function (s, i) { s.classList.toggle('is-start', i === start); s.classList.toggle('is-done', start > 0 && i < start); });
      picks.forEach(function (p) { if (p.tagName === 'BUTTON') p.setAttribute('aria-pressed', p.getAttribute('data-from') === from ? 'true' : 'false'); });
      share.forEach(function (s) { s.hidden = start === -1; });
      if (updateUrl && window.history && history.replaceState) {
        var u = new URL(window.location.href);
        if (start === -1) u.searchParams.delete('from'); else u.searchParams.set('from', from);
        u.hash = 'join';
        history.replaceState(null, '', u.toString());
      }
    }
    trailApi.set = set;
    picks.forEach(function (p) { if (p.tagName === 'BUTTON') p.addEventListener('click', function () { set(p.getAttribute('data-from'), true); }); });
    /* Door links like index.html?from=licensed#join: stay on the page, no reload. */
    doc.addEventListener('click', function (e) {
      var a = e.target.closest && e.target.closest('a[href*="from="]');
      if (!a) return;
      var u = new URL(a.href, window.location.href);
      if (u.pathname !== window.location.pathname) return;
      e.preventDefault();
      set(u.searchParams.get('from'), true);
      var target = doc.getElementById('join');
      if (target) { target.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'start' }); var h = $('h2', target); if (h) { h.setAttribute('tabindex', '-1'); h.focus({ preventScroll: true }); } }
    });
    set(params.get('from') || '', false);
  }

  /* "Is this for me?" quiz. Content: <script type="application/json" id="quiz-data">. */
  var ART = {
    handheld: '<use href="#k-ground" x="0" y="72" width="120" height="18"/><use href="#k-pine" x="92" y="34" width="14" height="42"/><use href="#k-handheld" x="34" y="4" width="40" height="80"/><use href="#k-waves" x="68" y="2" width="18" height="18"/>',
    cards: '<use href="#k-cards" x="22" y="14" width="76" height="63"/>',
    listen: '<use href="#k-base" x="10" y="14" width="96" height="72"/><use href="#k-waves" x="88" y="12" width="20" height="20"/>',
    field: '<use href="#k-ground" x="0" y="72" width="120" height="18"/><use href="#k-flag" x="10" y="28" width="30" height="45"/><use href="#k-handheld" x="52" y="6" width="38" height="76"/>',
    base: '<use href="#k-base" x="10" y="14" width="96" height="72"/>',
    sign: '<use href="#k-ground" x="0" y="72" width="120" height="18"/><use href="#k-sign" x="36" y="4" width="48" height="72"/><use href="#k-pine" x="94" y="30" width="16" height="48"/>',
    idcard: '<use href="#k-idcard" x="18" y="20" width="84" height="56"/>',
    laptop: '<use href="#k-laptop" x="14" y="20" width="92" height="61"/>',
    storm: '<use href="#k-storm" x="16" y="8" width="88" height="66"/><use href="#k-ground" x="0" y="74" width="120" height="16"/>',
    evening: '<use href="#k-base" x="4" y="18" width="88" height="66"/><use href="#k-moon" x="90" y="6" width="22" height="22"/>',
    weekend: '<use href="#k-ground" x="0" y="72" width="120" height="18"/><use href="#k-sun" x="84" y="6" width="26" height="26"/><use href="#k-handheld" x="30" y="4" width="40" height="80"/>',
    calendar: '<use href="#k-calendar" x="32" y="12" width="56" height="56"/>',
    eoc: '<use href="#k-ground" x="0" y="72" width="120" height="18"/><use href="#k-eoc" x="12" y="14" width="96" height="72"/>'
  };
  function art(key) { return '<svg viewBox="0 0 120 90" aria-hidden="true" focusable="false">' + (ART[key] || ART.sign) + '</svg>'; }

  function quiz() {
    var app = $('[data-quiz]');
    var src = doc.getElementById('quiz-data');
    if (!app || !src) return;
    var Q; try { Q = JSON.parse(src.textContent); } catch (e) { return; }
    var blazes = $$('[data-quiz-blazes] li');
    var st = { i: 0, ans: [] };

    function paint() { blazes.forEach(function (b, k) { b.className = (st.ans[k] ? 'is-done' : '') + (k === st.i ? ' is-now' : ''); }); }

    function renderQ(focus) {
      var q = Q.questions[st.i], sel = st.ans[st.i], picked = null;
      q.answers.forEach(function (a) { if (a.v === sel) picked = a; });
      app.innerHTML =
        '<div class="qcard">' +
          '<div class="qcard__main">' +
            '<p class="q-count">Question ' + (st.i + 1) + ' of ' + Q.questions.length + '</p>' +
            '<h3 class="q-title" id="q-title" tabindex="-1">' + esc(q.q) + '</h3>' +
            (q.hint ? '<p class="q-hint">' + esc(q.hint) + '</p>' : '') +
            '<div class="answers" role="group" aria-labelledby="q-title">' +
              q.answers.map(function (a) { return '<button type="button" class="ans" data-v="' + esc(a.v) + '" aria-pressed="' + (a.v === sel) + '"><span class="ans__tick" aria-hidden="true"></span>' + esc(a.t) + '</button>'; }).join('') +
            '</div>' +
            '<div class="q-nav">' + (st.i > 0 ? '<button type="button" class="btn btn--quiet" data-act="back">Back</button>' : '') +
              '<button type="button" class="btn btn--pine" data-act="next"' + (sel ? '' : ' disabled') + '>' + (st.i === Q.questions.length - 1 ? 'See my first step' : 'Next question') + '</button></div>' +
          '</div>' +
          '<div class="qcard__react"><div class="react-art">' + art(picked ? picked.art : 'sign') + '</div><p class="react-note" aria-live="polite">' + esc(picked ? picked.note : 'Tap an answer to see what it means for you.') + '</p></div>' +
        '</div>';
      paint();
      if (focus) $('#q-title', app).focus({ preventScroll: true });
    }

    function route() {
      var a = {}; Q.questions.forEach(function (q, k) { a[q.id] = st.ans[k]; });
      var licensed = a.license === 'yes';
      var key = licensed ? (a.check === 'fine' ? 'acs' : 'ares') : 'license';
      var also = (!licensed && a.style === 'weather') ? 'skywarn' : null;
      var along = [];
      var L = Q.along;
      if (a.time === 'evenings' || a.time === 'both') along.push(licensed ? L.netLicensed : L.netListen);
      if (a.time === 'weekends' || a.time === 'both') along.push(L.workshop);
      if (a.time === 'varies') along.push(licensed ? L.monthly : L.listenAnytime);
      if (a.style === 'digital') along.push(licensed ? L.winlink : L.winlinkLater);
      if (a.style === 'weather' && licensed) along.push(L.skywarn);
      if (a.where === 'field') along.push(L.events);
      if (a.where === 'home') along.push(L.home);
      if (key === 'acs') along.unshift(L.acsList);
      return { key: key, also: also, along: along.slice(0, 4) };
    }

    function renderResult() {
      var res = route(), r = Q.routes[res.key], alt = res.also ? Q.routes[res.also] : null;
      st.i = Q.questions.length; paint();
      trailApi.set(r.from, true);
      var plan = { license: [1, 0, 0, 0], ares: [2, 1, 0, 0], acs: [2, 1, 0, 0] }[res.key];
      var names = ['Get licensed', 'Join ARES', 'Get connected', 'Qualify for ACS'];
      var mini = '<ol class="minitrail" aria-label="Your posts on the trail">' + names.map(function (n, k) {
        var s = plan[k] === 1 ? 'is-start' : plan[k] === 2 ? 'is-done' : '';
        var word = plan[k] === 1 ? 'Start here' : plan[k] === 2 ? 'Done' : (k === 3 ? 'Optional, later' : 'Then');
        return '<li class="' + s + '"><i aria-hidden="true">' + k + '</i><span>' + esc(n) + ' <span class="muted">(' + word + ')</span></span></li>';
      }).join('') + '</ol>';
      app.innerHTML =
        '<div class="result" data-copy-scope>' +
          '<div class="result__main">' +
            '<p class="result__kicker">Your first step</p>' +
            '<h3 class="result__title" id="res-title" tabindex="-1">' + esc(r.name) + '</h3>' +
            '<dl class="result__facts"><dt>How long</dt><dd' + v(r.verifyTime) + '>' + esc(r.time) + '</dd><dt>First step</dt><dd' + v(r.verifyFirst) + '>' + esc(r.first) + '</dd></dl>' +
            '<a class="btn btn--pine" href="' + esc(r.href) + '">' + esc(r.cta) + (/^https?:/.test(r.href) ? ext('opens ' + r.host) : '') + '</a>' +
            (alt ? '<p class="result__also"><b>' + esc(alt.name) + ':</b> ' + esc(alt.first) + ' <a href="' + esc(alt.href) + '">' + esc(alt.cta) + ext('opens ' + alt.host) + '</a></p>' : '') +
            (res.along.length ? '<div class="result__along"><h4>Along the way</h4><ul>' + res.along.map(function (x) { return '<li>' + esc(x) + '</li>'; }).join('') + '</ul></div>' : '') +
            '<div class="result__share"><button type="button" class="btn btn--line btn--sm" data-copy="path"><svg class="icon" aria-hidden="true"><use href="#i-link"/></svg><span data-label>Copy a link to my path</span></button>' +
              '<a href="#join">See it on the trail</a><a href="' + esc(Q.contactHref) + '">Questions? Contact us</a></div>' +
            '<p class="status-msg" role="status" aria-live="polite"></p>' +
          '</div>' +
          '<div class="result__side">' + art(r.art) + mini +
            '<p style="font-size:var(--fs-label)"><a href="#visit">' + esc(Q.listenFirst) + '</a></p>' +
            '<button type="button" class="btn btn--quiet" data-act="restart">Retake the quiz</button></div>' +
        '</div>';
      $('#res-title', app).focus({ preventScroll: true });
      var top = app.getBoundingClientRect().top;
      if (top < 0 || top > window.innerHeight * 0.6) app.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'start' });
    }

    app.addEventListener('click', function (e) {
      var b = e.target.closest('button');
      if (!b || !app.contains(b)) return;
      if (b.classList.contains('ans')) {
        var q = Q.questions[st.i], val = b.getAttribute('data-v');
        var a = q.answers.filter(function (x) { return x.v === val; })[0];
        st.ans[st.i] = val;
        $$('.ans', app).forEach(function (x) { x.setAttribute('aria-pressed', String(x === b)); });
        var react = $('.qcard__react', app);
        $('.react-art', react).innerHTML = art(a.art);
        $('.react-note', react).textContent = a.note;
        if (!reduceMotion) { react.classList.remove('fade'); void react.offsetWidth; react.classList.add('fade'); }
        $('[data-act="next"]', app).disabled = false;
        paint();
        return;
      }
      var act = b.getAttribute('data-act');
      if (act === 'next') { if (st.i < Q.questions.length - 1) { st.i++; renderQ(true); } else renderResult(); }
      else if (act === 'back') { st.i--; renderQ(true); }
      else if (act === 'restart') { st = { i: 0, ans: [] }; trailApi.set('', true); renderQ(true); }
    });
    /* On phones the quiz waits behind one button, so the join trail stays close to the top. */
    var opener = null;
    function openQuiz() { app.hidden = false; if (opener) { opener.setAttribute('aria-expanded', 'true'); opener.hidden = true; } }
    if (window.matchMedia('(max-width: 720px)').matches) {
      if (!app.id) app.id = 'quiz-app';
      app.hidden = true;
      var intro = $('.quiz__intro');
      if (intro) {
        intro.insertAdjacentHTML('beforeend', '<button type="button" class="btn btn--pine quiz__open" aria-expanded="false" aria-controls="' + app.id + '">Start the quiz</button>');
        opener = $('.quiz__open', intro);
        opener.addEventListener('click', function () { openQuiz(); $('#q-title', app).focus(); });
      }
    }
    $$('a[href="#quiz"]').forEach(function (a) {
      a.addEventListener('click', function () { openQuiz(); setTimeout(function () { var t = $('#q-title') || $('#res-title'); if (t) t.focus({ preventScroll: true }); }, reduceMotion ? 0 : 500); });
    });
    renderQ(false);
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
    run('ics', icsButtons);
    run('toc', toc);
    run('membersNav', membersNav);
    run('doclib', docLibrary);
    run('trail', trail);
    run('fig1', fig1);
    if (!F) return;           /* data-driven widgets need data.js; their static fallbacks stay */
    run('strip', thisWeekStrip);
    run('netCards', netCards);
    run('nextNetLines', nextNetLines);
    run('comingUp', comingUp);
    run('meetingsNext', meetingsNext);
    run('rota', rota);
    run('winlink', winlinkNext);
    run('dated', datedItems);
    run('docSuggest', docSuggest);
    run('quiz', quiz);
  }
  function start() { boot(); run('hashLanding', hashLanding); }
  window.SiteUI = { clock: function () { return F && clock(); }, netState: function () { return F && netState(); }, trail: trailApi };
  if (doc.readyState === 'loading') doc.addEventListener('DOMContentLoaded', start); else start();
})();
