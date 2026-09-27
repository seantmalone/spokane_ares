/*!
 * Spokane County ARES-ACS — Round 3, Option C "Open Channel" (simplified)
 * assets/site.js — progressive enhancement shared by every page.
 * =============================================================================
 * Load order on every page (end of <body>):
 *   <script src="assets/data.js"></script>      (members pages: ../assets/…)
 *   <script src="assets/site.js"></script>
 * and in <head>, first thing:  <html class="no-js"> + the one-line class swap
 * shown in _chrome.html (so JS-only styles apply before first paint).
 *
 * Everything here is optional: every page reads and navigates with JS off.
 * No fetch(), no frameworks, ES5 on purpose. Each widget is one function that
 * maps to one WordPress block/pattern or one SSG partial, and finds its markup
 * by a data-widget attribute:
 *
 *   data-widget="week"          This-week card body (members variant; Home no longer uses it)
 *   data-widget="coming-up"     stand-alone "Coming up" list
 *   data-widget="toc"           About: sticky TOC + scroll-spy + mobile disclosure
 *   data-widget="scrollspy"     How it works: sticky "On this page" chip bar
 *   data-widget="doc-library"   Members documents: search, category chips, ?q= ?cat=
 *   data-widget="doc-search"    Members hub: search box with live suggestions
 *   data-widget="rota"          Net-control rota table body
 *   data-widget="winlink"       Winlink assignment table body
 *   data-widget="agenda"        Members exercises: month tabs + type chips + agenda
 * Plus attribute helpers that work anywhere:
 *   [data-next]="net|workshop|winlink-workshop|third-thursday|winlink|event:<id>"
 *        fills the element with the computed date (data-format short|long|day,
 *        data-index / data-offset for the 2nd, 3rd … occurrence)
 *   [data-next-block]="workshop|third-thursday|net|event:<id>"
 *        fills a .date-block's .num and .mon (and datetime on a <time>)
 *   [data-netcontrol]            call sign on net control for the next net (data-offset)
 *   [data-slot="asof"]           "Sat, Sep 26, 2026"
 *   [data-copy="radio"]          copies SPOKARES.radio.copyText (or data-copy-text)
 *   [data-ics="weekly-net"|"event"]  builds an .ics in the browser (see SPO.ics)
 *   .term / .term-def            tap-to-define ("What's that?")
 *   <body data-root="../">       prefix for links rendered by widgets on members/* pages
 *
 * Demo switches (mockup only): ?today=2026-09-29  ?now=2026-09-29T20:15  ?verify=1
 *   documents.html?q=…&cat=…
 * Deep links: the <head> one-liner turns smooth scrolling off when the URL has a
 * #hash; hashLanding() corrects the landing once fonts and widgets settle.
 * Retired in round 3: planner, rsvp, partners, join-path, the members-menu
 * disclosure and [data-visit]. Times are always 12-hour ("8:00 PM").
 * Production: remove SPOKARES.meta.asOf and "now" becomes the real Pacific time.
 * ========================================================================== */
(function (win, doc) {
  'use strict';

  var S = win.SPOKARES;
  var SPO = win.SPO = win.SPO || {};
  var html = doc.documentElement;
  if (!/\bjs\b/.test(html.className)) html.className = (html.className.replace(/\bno-js\b/, '') + ' js').replace(/^\s+|\s+$/g, '');

  /* --------------------------------------------------------------- helpers */
  function $(sel, root) { return (root || doc).querySelector(sel); }
  function $$(sel, root) { return Array.prototype.slice.call((root || doc).querySelectorAll(sel)); }
  function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
  function param(name) { var m = new RegExp('[?&]' + name + '=([^&#]*)').exec(win.location.search || ''); return m ? decodeURIComponent(m[1].replace(/\+/g, ' ')) : null; }
  function pad(n) { return (n < 10 ? '0' : '') + n; }
  function root() { return (doc.body && doc.body.getAttribute('data-root')) || ''; }
  function attr(v) { return esc(v); }
  function each(list, fn) { for (var i = 0; i < list.length; i++) fn(list[i], i); }
  SPO.$ = $; SPO.$$ = $$; SPO.esc = esc; SPO.param = param;

  var F = S && S.fn;
  function parse(iso) { var p = String(iso).split('-'); return new Date(+p[0], +p[1] - 1, +(p[2] || 1)); }
  function isoOf(d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); }
  function addDays(iso, n) { var d = parse(iso); d.setDate(d.getDate() + n); return isoOf(d); }
  var DAY = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
  var DAYLONG = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
  var MON = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
  var MONLONG = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

  function fmt(iso, style) {
    var d = parse(iso);
    switch (style) {
      case 'long': return DAYLONG[d.getDay()] + ', ' + MONLONG[d.getMonth()] + ' ' + d.getDate();
      case 'long-year': return DAYLONG[d.getDay()] + ', ' + MONLONG[d.getMonth()] + ' ' + d.getDate() + ', ' + d.getFullYear();
      case 'day': return MON[d.getMonth()] + ' ' + d.getDate();
      case 'dow': return DAY[d.getDay()];
      case 'month': return MONLONG[d.getMonth()] + ' ' + d.getFullYear();
      case 'short-year': return DAY[d.getDay()] + ', ' + MON[d.getMonth()] + ' ' + d.getDate() + ', ' + d.getFullYear();
      default: return DAY[d.getDay()] + ', ' + MON[d.getMonth()] + ' ' + d.getDate();
    }
  }
  /* "Sat–Sun, Sep 26–27" / "Wed–Wed, Nov 18–25" / "Fri, Dec 4" */
  function fmtRange(start, end) {
    if (!end || end === start) return fmt(start);
    var a = parse(start), b = parse(end);
    var days = DAY[a.getDay()] + '\u2013' + DAY[b.getDay()] + ', ';
    if (a.getMonth() === b.getMonth()) return days + MON[a.getMonth()] + ' ' + a.getDate() + '\u2013' + b.getDate();
    return days + MON[a.getMonth()] + ' ' + a.getDate() + '\u2013' + MON[b.getMonth()] + ' ' + b.getDate();
  }
  function fmtDayRange(start, end) {
    if (!end || end === start) return fmt(start, 'day');
    var a = parse(start), b = parse(end);
    if (a.getMonth() === b.getMonth()) return MON[a.getMonth()] + ' ' + a.getDate() + '\u2013' + b.getDate();
    return MON[a.getMonth()] + ' ' + a.getDate() + '\u2013' + MON[b.getMonth()] + ' ' + b.getDate();
  }
  /* Always 12-hour ("8:00 PM", "noon"), members pages included (round-3 rule).
     The clock argument is kept so old call sites still work; it is ignored. */
  function time(hhmm) {
    if (!hhmm) return '';
    return F ? F.fmtTime(hhmm) : hhmm;
  }
  function timeRange(a, b, clock) {
    if (!a) return '';
    return b ? time(a, clock) + '\u2013' + time(b, clock) : time(a, clock);
  }
  SPO.fmt = fmt; SPO.fmtRange = fmtRange; SPO.time = time;

  /* ------------------------------------------------------------ now + nets */
  /* Mock: date = SPOKARES.fn.today() (asOf or ?today=), time = noon unless ?now=.
     Production (no meta.asOf): the real clock in America/Los_Angeles. */
  SPO.now = function () {
    var p = param('now');
    if (p && /^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}/.test(p)) return { date: p.slice(0, 10), minutes: (+p.slice(11, 13)) * 60 + (+p.slice(14, 16)) };
    if (S && S.meta && S.meta.asOf) return { date: F.today(), minutes: 12 * 60 };
    try {
      var parts = new Intl.DateTimeFormat('en-CA', { timeZone: 'America/Los_Angeles', year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', hour12: false }).formatToParts(new Date());
      var o = {}; each(parts, function (x) { o[x.type] = x.value; });
      return { date: o.year + '-' + o.month + '-' + o.day, minutes: (+o.hour % 24) * 60 + (+o.minute) };
    } catch (e) { var d = new Date(); return { date: isoOf(d), minutes: d.getHours() * 60 + d.getMinutes() }; }
  };
  /* The next Tuesday net, rolling over after 9 PM on a Tuesday. state: 'on-air' | 'tonight' | null */
  SPO.nextNet = function () {
    var n = SPO.now(), d = parse(n.date), from = n.date;
    if (d.getDay() === 2 && n.minutes >= 21 * 60) from = addDays(n.date, 1);
    var net = F.nextNet(from);
    var state = null;
    if (net && net.date === n.date) state = n.minutes >= 20 * 60 ? 'on-air' : 'tonight';
    return { net: net, state: state };
  };
  SPO.netsFrom = function (count) {
    var first = SPO.nextNet().net, out = [];
    for (var i = 0; first && i < count; i++) out.push(F.netOn(addDays(first.date, 7 * i)));
    return out;
  };
  function meetingDates(id, n) {
    var list = F.nextMeetings(SPO.now().date, n || 3);
    for (var i = 0; i < list.length; i++) if (list[i].id === id) return list[i].dates;
    return [];
  }
  function meeting(id) { for (var i = 0; i < S.meetings.length; i++) if (S.meetings[i].id === id) return S.meetings[i]; return null; }
  function eventById(id) { for (var i = 0; i < S.events.length; i++) if (S.events[i].id === id) return S.events[i]; return null; }
  function nextWinlink() {
    var t = SPO.now().date;
    for (var i = 0; i < S.winlink.assignments.length; i++) if (S.winlink.assignments[i].date >= t) return S.winlink.assignments[i];
    return null;
  }
  SPO.meetingDates = meetingDates; SPO.nextWinlink = nextWinlink;

  /* Short event titles for compact lists.
     Production: add a `short` field to each event record instead. */
  var SHORT = {
    'wsdot-2026': 'ARES-ACS and WSDOT exercise',
    'set-2026': 'Simulated Emergency Test',
    'shakeout-2026': 'Great ShakeOut',
    'commex-2026': 'State COMMEX',
    'srd-2026': 'SKYWARN Recognition Day',
    'w1aw7-2026': 'W1AW/7 week',
    'wfd-2027': 'Winter Field Day',
    'bloomsday-2027': 'Bloomsday',
    'climb-2027': 'Climb for the Cure'
  };
  function shortTitle(item) { return SHORT[item.id] || item.title; }
  function isPublic(item) {
    var rec = item.id && eventById(item.id);
    return !/^shares/.test(item.id || '') && !item.optional && !(rec && rec.optional);
  }

  /* ------------------------------------------------------------- live region */
  var liveEl;
  function announce(msg, el) {
    var target = el || liveEl;
    if (!target) {
      liveEl = doc.createElement('p'); liveEl.className = 'sr-only'; liveEl.setAttribute('aria-live', 'polite'); doc.body.appendChild(liveEl); target = liveEl;
    }
    target.textContent = '';
    setTimeout(function () { target.textContent = msg; }, 30);
    if (target.__t) clearTimeout(target.__t);
    if (target !== liveEl) target.__t = setTimeout(function () { target.textContent = ''; }, 6000);
  }
  SPO.announce = announce;
  function statusNear(el) {
    var scope = el.closest('[data-status-scope]') || el.parentNode;
    return $('.status-msg', scope) || (el.closest('section') && $('.status-msg', el.closest('section')));
  }

  /* ---------------------------------------------------------- mobile menu */
  function menu() {
    each($$('.menu-btn[aria-controls]'), function (btn) {
      var nav = doc.getElementById(btn.getAttribute('aria-controls'));
      if (!nav) return;
      function set(open) {
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        btn.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
        nav.classList.toggle('is-open', open);
      }
      set(false);
      btn.addEventListener('click', function () {
        var open = btn.getAttribute('aria-expanded') !== 'true';
        set(open);
        if (open) { var first = $('a', nav); if (first) first.focus(); }
      });
      doc.addEventListener('keydown', function (e) { if (e.key === 'Escape' && nav.classList.contains('is-open')) { set(false); btn.focus(); } });
      doc.addEventListener('click', function (e) { if (nav.classList.contains('is-open') && !nav.contains(e.target) && !btn.contains(e.target)) set(false); });
      nav.addEventListener('click', function (e) { if (e.target.closest('a')) set(false); });
    });
  }

  /* ------------------------------------------------------ tap-to-define */
  function terms() {
    doc.addEventListener('click', function (e) {
      var t = e.target.closest('.term');
      if (!t) return;
      var def = doc.getElementById(t.getAttribute('aria-controls'));
      if (!def) return;
      var open = t.getAttribute('aria-expanded') !== 'true';
      t.setAttribute('aria-expanded', open ? 'true' : 'false');
      def.classList.toggle('is-open', open);
    });
    doc.addEventListener('keydown', function (e) {
      if (e.key !== 'Escape') return;
      var t = doc.activeElement && doc.activeElement.closest && doc.activeElement.closest('.term[aria-expanded="true"]');
      if (t) t.click();
    });
  }
  /* Markup for a term, for widgets that render their own text */
  function termHTML(id, label, def, verify) {
    return '<button type="button" class="term" aria-expanded="false" aria-controls="' + id + '">' + esc(label) + '</button>' +
      '<span class="term-def" id="' + id + '" role="note"' + (verify ? ' data-verify="true"' : '') + '><b>' + esc(label) + '</b>' + def + '</span>';
  }
  SPO.termHTML = termHTML;

  /* ------------------------------------------------------------- copy */
  function copyText(text) {
    return new Promise(function (resolve, reject) {
      function fallback() {
        try {
          var ta = doc.createElement('textarea');
          ta.value = text; ta.setAttribute('readonly', ''); ta.style.position = 'fixed'; ta.style.opacity = '0'; ta.style.top = '0';
          doc.body.appendChild(ta); ta.select(); ta.setSelectionRange(0, text.length);
          var ok = doc.execCommand('copy'); doc.body.removeChild(ta);
          ok ? resolve() : reject();
        } catch (err) { reject(err); }
      }
      if (navigator.clipboard && win.isSecureContext) navigator.clipboard.writeText(text).then(resolve, fallback);
      else fallback();
    });
  }
  SPO.copyText = copyText;
  function copyButtons() {
    doc.addEventListener('click', function (e) {
      var b = e.target.closest('[data-copy]');
      if (!b) return;
      e.preventDefault();
      var text = b.getAttribute('data-copy') === 'radio' ? S.radio.copyText : (b.getAttribute('data-copy-text') || '');
      var st = statusNear(b);
      copyText(text).then(function () { announce('Copied: ' + text, st); },
        function () { announce('Couldn\u2019t copy. Select the settings text and copy it by hand.', st); });
    });
  }

  /* -------------------------------------------------------- calendar (.ics) */
  var VTZ = ['BEGIN:VTIMEZONE', 'TZID:America/Los_Angeles',
    'BEGIN:DAYLIGHT', 'TZOFFSETFROM:-0800', 'TZOFFSETTO:-0700', 'TZNAME:PDT', 'DTSTART:19700308T020000', 'RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=2SU', 'END:DAYLIGHT',
    'BEGIN:STANDARD', 'TZOFFSETFROM:-0700', 'TZOFFSETTO:-0800', 'TZNAME:PST', 'DTSTART:19701101T020000', 'RRULE:FREQ=YEARLY;BYMONTH=11;BYDAY=1SU', 'END:STANDARD',
    'END:VTIMEZONE'];
  function icsEsc(s) { return String(s || '').replace(/\\/g, '\\\\').replace(/;/g, '\\;').replace(/,/g, '\\,').replace(/\r?\n/g, '\\n'); }
  function fold(line) { var out = [], s = line; while (s.length > 74) { out.push(s.slice(0, 74)); s = ' ' + s.slice(74); } out.push(s); return out.join('\r\n'); }
  function stamp(iso, hhmm) { return iso.replace(/-/g, '') + (hhmm ? 'T' + hhmm.replace(':', '') + '00' : ''); }
  /* ev: {id, title, date, start, end, allDay, endDate, weekly, where, desc} */
  SPO.icsText = function (ev) {
    var now = new Date();
    var dtstamp = now.getUTCFullYear() + pad(now.getUTCMonth() + 1) + pad(now.getUTCDate()) + 'T' + pad(now.getUTCHours()) + pad(now.getUTCMinutes()) + '00Z';
    var L = ['BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//Spokane County ARES-ACS//spokares.org//EN', 'CALSCALE:GREGORIAN', 'METHOD:PUBLISH'].concat(VTZ);
    L.push('BEGIN:VEVENT', 'UID:' + (ev.id || 'event') + '-' + stamp(ev.date) + '@spokares.org', 'DTSTAMP:' + dtstamp);
    if (ev.allDay || !ev.start) {
      L.push('DTSTART;VALUE=DATE:' + stamp(ev.date), 'DTEND;VALUE=DATE:' + stamp(addDays(ev.endDate || ev.date, 1)));
    } else {
      L.push('DTSTART;TZID=America/Los_Angeles:' + stamp(ev.date, ev.start), 'DTEND;TZID=America/Los_Angeles:' + stamp(ev.endDate || ev.date, ev.end || ev.start));
    }
    if (ev.weekly) L.push('RRULE:FREQ=WEEKLY;BYDAY=' + ['SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA'][parse(ev.date).getDay()]);
    L.push('SUMMARY:' + icsEsc(ev.title));
    if (ev.where) L.push('LOCATION:' + icsEsc(ev.where));
    if (ev.desc) L.push('DESCRIPTION:' + icsEsc(ev.desc));
    L.push('END:VEVENT', 'END:VCALENDAR');
    return L.map(fold).join('\r\n') + '\r\n';
  };
  SPO.gcalUrl = function (ev) {
    var dates = (ev.allDay || !ev.start) ? stamp(ev.date) + '/' + stamp(addDays(ev.endDate || ev.date, 1)) : stamp(ev.date, ev.start) + '/' + stamp(ev.endDate || ev.date, ev.end || ev.start);
    var u = 'https://calendar.google.com/calendar/render?action=TEMPLATE&text=' + encodeURIComponent(ev.title) + '&dates=' + dates + '&ctz=America/Los_Angeles';
    if (ev.where) u += '&location=' + encodeURIComponent(ev.where);
    if (ev.desc) u += '&details=' + encodeURIComponent(ev.desc);
    if (ev.weekly) u += '&recur=' + encodeURIComponent('RRULE:FREQ=WEEKLY');
    return u;
  };
  SPO.downloadIcs = function (ev) {
    var blob = new Blob([SPO.icsText(ev)], { type: 'text/calendar;charset=utf-8' });
    var url = URL.createObjectURL(blob), a = doc.createElement('a');
    a.href = url; a.download = 'spokane-ares-acs-' + (ev.id || 'event') + '.ics';
    doc.body.appendChild(a); a.click(); doc.body.removeChild(a);
    setTimeout(function () { URL.revokeObjectURL(url); }, 4000);
  };
  var NET_WHERE = function () { return 'On the air: ' + S.radio.copyText; };
  function weeklyNetEvent() {
    var nn = SPO.nextNet().net;
    return { id: 'tuesday-net', title: 'Spokane County ARES-ACS Tuesday net', date: nn.date, start: '20:00', end: '21:00', weekly: true, where: NET_WHERE(),
      desc: 'Every Tuesday at 8:00 PM on ' + S.radio.copyText + '. Listening needs no license.' };
  }
  function eventFromAttrs(el) {
    return { id: el.getAttribute('data-id') || 'event', title: el.getAttribute('data-title') || 'Spokane County ARES-ACS', date: el.getAttribute('data-date'),
      endDate: el.getAttribute('data-end-date') || null, start: el.getAttribute('data-start') || null, end: el.getAttribute('data-end') || null,
      allDay: el.getAttribute('data-all-day') === 'true', where: el.getAttribute('data-where') || '', desc: el.getAttribute('data-desc') || '' };
  }
  function icsButtons() {
    doc.addEventListener('click', function (e) {
      var b = e.target.closest('[data-ics]');
      if (!b || !S) return;
      var kind = b.getAttribute('data-ics'), ev = null;
      if (kind === 'weekly-net') ev = weeklyNetEvent();
      else if (kind === 'event' && b.getAttribute('data-date')) ev = eventFromAttrs(b);
      if (!ev) return;
      e.preventDefault();
      SPO.downloadIcs(ev);
      announce('Calendar file downloaded: ' + ev.title + ', ' + fmt(ev.date, 'long') + '.', statusNear(b));
    });
  }

  /* ------------------------------------------------------ date slot fillers */
  function nextDateFor(key, index) {
    index = index || 0;
    if (key === 'net') { var nn = SPO.nextNet().net; return nn ? addDays(nn.date, 7 * index) : null; }
    if (key === 'winlink') { var w = nextWinlink(); return w ? w.date : null; }
    if (key.indexOf('event:') === 0) { var ev = eventById(key.slice(6)); return ev ? ev.start : null; }
    var ds = meetingDates(key, index + 1);
    return ds[index] || null;
  }
  SPO.nextDateFor = nextDateFor;
  function dateSlots() {
    each($$('[data-next]'), function (el) {
      var key = el.getAttribute('data-next'), idx = +(el.getAttribute('data-index') || el.getAttribute('data-offset') || 0);
      var d = nextDateFor(key, idx);
      if (!d) return;
      var style = el.getAttribute('data-format') || 'short';
      if (key.indexOf('event:') === 0 && style === 'range') { var ev = eventById(key.slice(6)); el.textContent = fmtRange(ev.start, ev.end); }
      else el.textContent = fmt(d, style);
      if (el.tagName === 'TIME') el.setAttribute('datetime', d);
    });
    each($$('[data-next-block]'), function (el) {
      var d = nextDateFor(el.getAttribute('data-next-block'), +(el.getAttribute('data-index') || 0));
      if (!d) return;
      var p = parse(d), num = $('.num', el), mon = $('.mon', el);
      if (num) num.textContent = p.getDate();
      if (mon) mon.textContent = MON[p.getMonth()];
      if (el.tagName === 'TIME') el.setAttribute('datetime', d);
    });
    each($$('[data-netcontrol]'), function (el) {
      var off = +(el.getAttribute('data-netcontrol') || 0), nets = SPO.netsFrom(off + 1), n = nets[off];
      if (!n) return;
      el.textContent = n.netControl ? n.netControl : (n.open ? 'open' : 'on groups.io');
    });
    each($$('[data-slot="asof"]'), function (el) { el.textContent = fmt(SPO.now().date, 'short-year'); });
  }

  /* ---------------------------------------------------- net description */
  var GROUPS = function () { return S.links.groupsio.main; };
  function netControlHTML(net, withTerm) {
    var label = withTerm ? termHTML('t-netcontrol', 'Net control', 'The station that runs a net, an organized on-air meeting. Net control calls stations in turn so everyone gets heard.') : 'Net control';
    if (net.netControl) return '<p>' + label + ': <b class="callsign">' + esc(net.netControl) + '</b></p>';
    if (net.open) return '<p class="net-flag net-flag--open">' + label + ' slot open: <a href="' + GROUPS() + '">volunteer on groups.io<span class="sr-only"> (opens groups.io)</span></a></p>';
    return '<p>' + label + ': see <a href="' + GROUPS() + '">groups.io</a></p>';
  }
  function netFlagsHTML(net, clock) {
    var out = '';
    if (net.mode === 'simplex') out += '<p class="net-flag" data-verify="true">Fifth Tuesday: the net starts on simplex, then moves to W7GBU.</p>';
    if (net.mode === 'winlink') out += '<p class="net-flag" data-verify="true">Winlink night: have paper and pencil ready' + (net.winlinkAssignment ? '. Assignment: ' + esc(net.winlinkAssignment.task) : '.') + '</p>';
    if (net.gmrsSameDay) out += '<p class="net-flag" data-verify="true">Third Tuesday: the ACS GMRS net meets at ' + time('19:30', clock) + ', before this net.</p>';
    return out;
  }
  function dateBlock(iso, cls) {
    var d = parse(iso);
    return '<p class="date-block' + (cls ? ' ' + cls : '') + '" aria-hidden="true"><span class="dow">' + DAY[d.getDay()] + '</span><span class="num">' + d.getDate() + '</span><span class="mon">' + MON[d.getMonth()] + '</span></p>';
  }
  SPO.dateBlock = dateBlock;

  /* ------------------------------------------------------ coming-up rows */
  function comingItems(opts) {
    var days = opts.days || 28, limit = opts.limit || 3;
    var types = opts.types || ['exercise', 'meeting', 'training'];
    var list = F.upcoming({ from: SPO.now().date, days: days, types: types }).filter(function (x) { return opts.includePrivate || isPublic(x); });
    var byDay = [], map = {};
    each(list, function (x) {
      var k = x.inProgress ? 'now:' + x.id : x.date;
      if (!map[k]) { map[k] = { date: x.date, items: [] }; byDay.push(map[k]); }
      map[k].items.push(x);
    });
    return byDay.slice(0, limit);
  }
  function itemLine(x, clock) {
    var t = x.type === 'meeting' ? (x.id.indexOf('third-thursday') === 0 ? 'Third Thursday training meeting' : x.title) : shortTitle(x);
    var when = x.time ? timeRange(x.time, x.endTime, clock) : (x.displayTime ? x.displayTime.toLowerCase() : '');
    return t + (when ? ', ' + when : '');
  }
  function itemHref(x, variant) {
    var r = root();
    if (variant === 'members') return x.type === 'meeting' ? 'exercises.html#upcoming' : 'exercises.html#' + (x.id === 'set-2026' || x.id === 'shakeout-2026' ? x.id : 'upcoming');
    if (x.type === 'meeting') return r + 'index.html#visit';
    return r + 'how-it-works.html#exercises';
  }
  function comingListHTML(opts) {
    var groups = comingItems(opts), clock = opts.clock, out = '';
    each(groups, function (g) {
      var first = g.items[0], rest = g.items.slice(1), now = first.inProgress;
      var whenTxt = now ? 'Now' : fmt(g.date);
      var small = now ? 'Happening now, through ' + fmt(first.endDate || first.date) : rest.map(function (x) { return itemLine(x, clock); }).join('; ');
      var verify = g.items.some(function (x) { return x.verify; });
      out += '<li' + (verify ? ' data-verify="true"' : '') + '><a href="' + itemHref(first, opts.variant) + '">' +
        '<span class="when">' + esc(whenTxt) + '</span><span class="what">' + esc(itemLine(first, clock)) + (small ? '<small>' + esc(small) + '</small>' : '') + '</span></a></li>';
    });
    return out;
  }

  /* ------------------------------------------------------ week card widget */
  function weekCard(el) {
    var variant = el.getAttribute('data-variant') || 'home', clock = el.getAttribute('data-clock') || (variant === 'members' ? '24' : '12');
    var withTerms = el.getAttribute('data-terms') === 'true';
    var nn = SPO.nextNet(), net = nn.net;
    if (!net) return;
    var card = el.closest('.week-card'), head = card && $('.week-head', card);
    if (head) {
      var live = $('.live', head); if (live) live.parentNode.removeChild(live);
      if (nn.state) {
        var s = doc.createElement('span');
        s.className = 'live' + (nn.state === 'on-air' ? ' live--on-air' : '');
        s.textContent = nn.state === 'on-air' ? 'On the air now' : 'Tonight, ' + time('20:00', clock);
        head.appendChild(s);
      }
    }
    var w7 = withTerms ? termHTML('t-w7gbu', 'W7GBU', 'The group\u2019s call sign and repeater, honoring Ira \u201cDean\u201d Bula.') : 'W7GBU';
    var r = S.radio.primary;
    var netBlock =
      '<div class="net">' + dateBlock(net.date) +
        '<div><h3 class="net-title">Tuesday net, ' + time('20:00', clock) + '<span class="sr-only">, ' + fmt(net.date, 'long') + '</span></h3>' +
        '<div class="net-lines">' + netControlHTML(net, withTerms) + netFlagsHTML(net, clock) +
        '<p>' + esc(variant === 'members' ? 'Members check in at least once a month.' : 'Visitors are welcome to check in.') + '</p></div></div>' +
      '</div>' +
      '<div class="settings" data-status-scope>' +
        '<p class="freq-line"><span class="freq">' + w7 + ' ' + r.freq + ' MHz</span>, ' + r.offset + ', ' + r.tone + ' tone</p>' +
        '<button type="button" class="btn btn--primary btn--small" data-copy="radio"><svg class="icon" aria-hidden="true"><use href="#i-copy"/></svg>Copy radio settings</button>' +
        '<a class="btn btn--outline btn--small" data-ics="weekly-net" href="' + S.links.groupsio.calendar + '"><svg class="icon" aria-hidden="true"><use href="#i-calendar"/></svg>Add to calendar</a>' +
        '<p class="status-msg" role="status"></p>' +
      '</div>';

    if (variant !== 'members') {
      el.innerHTML = netBlock +
        '<div class="coming"><h3>Coming up</h3><ul class="coming-list">' + comingListHTML({ days: 42, limit: +(el.getAttribute('data-limit') || 3), clock: clock, variant: variant }) + '</ul></div>';
      return;
    }
    /* members: next net | rota | coming up */
    var scripts = '<p class="tiny" style="margin-top:10px"><a href="documents.html#net-preamble-weekly">Weekly script</a>' +
      (net.mode === 'simplex' ? '&emsp;<a href="documents.html#net-preamble-simplex">Simplex script</a>' : '') +
      (net.gmrsSameDay ? '&emsp;<a href="documents.html#net-preamble-gmrs">GMRS script</a>' : '') + '</p>';
    var rota = '';
    each(SPO.netsFrom(4), function (n) {
      var note = n.mode === 'simplex' ? 'Simplex' : (n.mode === 'winlink' ? 'Winlink night' : (n.gmrsSameDay ? 'GMRS net ' + time('19:30', '24') : ''));
      rota += '<li' + (n.mode !== 'repeater' || n.gmrsSameDay ? ' data-verify="true"' : '') + '><span class="when">' + fmt(n.date) + '</span><span>' +
        (n.netControl ? '<span class="who callsign">' + esc(n.netControl) + '</span>' : (n.open ? '<a class="open" href="' + GROUPS() + '">Open: volunteer on groups.io</a>' : 'See groups.io')) +
        (note ? '<small class="muted"> ' + esc(note) + '</small>' : '') + '</span></li>';
    });
    var wl = nextWinlink(), ex = F.upcoming({ from: SPO.now().date, days: 120, types: ['exercise'] })[0];
    var ws = meetingDates('workshop', 1)[0], tt = meetingDates('third-thursday', 1)[0], wm = meeting('workshop');
    var rows = [];   /* {k: sort key, h: html}; "Now" sorts first, then by date */
    if (ex) rows.push({ k: ex.inProgress ? '0' : ex.date, h: '<li><a href="exercises.html#' + (ex.id === 'set-2026' || ex.id === 'shakeout-2026' ? ex.id : 'next-up') + '"><span class="when">' + (ex.inProgress ? 'Now' : fmt(ex.date)) + '</span><span class="what">' + esc(shortTitle(ex)) + '<small>' + (ex.inProgress ? 'Happening now, through ' + fmt(ex.endDate || ex.date) : 'Exercise') + '</small></span></a></li>' });
    if (wl) rows.push({ k: wl.date, h: '<li><a href="#winlink"><span class="when">' + fmt(wl.date) + '</span><span class="what">Winlink assignment<small>' + esc(wl.form) + ', ' + time('17:00', '24') + '\u2013' + time('21:00', '24') + '</small></span></a></li>' });
    if (ws) rows.push({ k: ws, h: '<li><a href="exercises.html#upcoming"><span class="when">' + fmt(ws) + '</span><span class="what">Second Saturday Workshop<small>' + timeRange(wm.start, wm.end, '24') + ', Winlink workshop after</small></span></a></li>' });
    if (tt) rows.push({ k: tt, h: '<li data-verify="true"><a href="exercises.html#upcoming"><span class="when">' + fmt(tt) + '</span><span class="what">Third Thursday training meeting<small>Evening, at SCEM</small></span></a></li>' });
    rows.sort(function (a, b) { return a.k < b.k ? -1 : (a.k > b.k ? 1 : 0); });
    var list = rows.map(function (x) { return x.h; }).join('');
    el.innerHTML =
      '<div class="week-col">' + netBlock + scripts + '</div>' +
      '<div class="week-col"><div class="coming"><h3>Net control, next four Tuesdays</h3><ul class="mini-rota">' + rota + '</ul>' +
        '<p class="tiny muted" style="margin-top:8px">' + esc(S.rotaMeta.openSlotAction) + '</p></div></div>' +
      '<div class="week-col"><div class="coming"><h3>Coming up</h3><ul class="coming-list">' + list + '</ul></div></div>';
  }

  function comingUpWidget(el) {
    el.innerHTML = comingListHTML({ days: +(el.getAttribute('data-days') || 28), limit: +(el.getAttribute('data-limit') || 4), clock: el.getAttribute('data-clock') || '12', variant: el.getAttribute('data-variant') || 'home' });
  }

  /* -------------------------------------------- scroll-spy (TOC + chip bar) */
  function spy(links, onChange) {
    var targets = [];
    each(links, function (a) {
      var id = (a.getAttribute('href') || '').split('#')[1], t = id && doc.getElementById(id);
      if (t) targets.push({ el: t, link: a });
    });
    if (!targets.length || !('IntersectionObserver' in win)) return;
    var current = null;
    /* The current target is the last one whose top has passed the reading line (38% down
       the viewport). Works for headings (About TOC) and whole sections (chip bar) alike;
       the observer is only the trigger, so a section's trailing edge can't win. */
    function pickCurrent() {
      var line = Math.min(400, Math.max(120, win.innerHeight * 0.38)), best = null;
      each(targets, function (t) { if (t.el.getBoundingClientRect().top < line) best = t; });
      if (best && best !== current) { current = best; onChange(best); }
    }
    var io = new IntersectionObserver(pickCurrent, { rootMargin: '-80px 0px -62% 0px', threshold: 0 });
    each(targets, function (t) { io.observe(t.el); });
    pickCurrent();
    /* The observer only reports changes, so an instant jump (a deep link on load, reduced
       motion, the back button) can skip past a target without crossing the band.
       Re-pick once scrolling settles, and on hashchange/load. */
    var timer;
    function later() { clearTimeout(timer); timer = setTimeout(pickCurrent, 140); }
    win.addEventListener('scroll', later, { passive: true });
    win.addEventListener('hashchange', later);
    win.addEventListener('load', later);
    later();
  }
  SPO.spy = spy;

  function toc(el) {
    var list = $('.toc-list', el), src = el.getAttribute('data-for') ? $(el.getAttribute('data-for')) : $('.prose');
    if (list && !list.children.length && src) {
      var out = '', open = false;
      each($$('h2[id], h3[id]', src), function (h) {
        var c = h.cloneNode(true);
        each($$('.anchor, .term-def, .sr-only', c), function (x) { x.parentNode.removeChild(x); });
        var txt = (h.getAttribute('data-toc') || c.textContent).replace(/\s+/g, ' ').trim();
        if (h.tagName === 'H2') { out += (open ? '</ol></li>' : (out ? '</li>' : '')) + '<li><a href="#' + h.id + '">' + esc(txt) + '</a>'; open = false; }
        else { out += (open ? '' : '<ol>') + '<li><a href="#' + h.id + '">' + esc(txt) + '</a></li>'; open = true; }
      });
      out += open ? '</ol></li>' : '</li>';
      list.innerHTML = out;
    }
    var details = $('.toc-details', el), cur = $('.current', el);
    var mq = win.matchMedia('(min-width: 1024px)');
    function sync() { if (details) { if (mq.matches) details.setAttribute('open', ''); else details.removeAttribute('open'); } }
    sync();
    if (mq.addEventListener) mq.addEventListener('change', sync); else if (mq.addListener) mq.addListener(sync);
    el.addEventListener('click', function (e) { if (e.target.closest('a') && !mq.matches && details) details.removeAttribute('open'); });
    /* Phones: opening "Jump to section" scrolls its list to the current section */
    var panel = $('.toc-panel', el);
    if (details && panel) details.addEventListener('toggle', function () {
      if (!details.open || mq.matches) return;
      var a = $('.toc-list a[aria-current="true"]', el);
      if (!a) return;
      var r = a.getBoundingClientRect(), box = panel.getBoundingClientRect();
      panel.scrollTop += r.top - box.top - panel.clientHeight / 3;
    });
    var links = $$('.toc-list a', el);
    spy(links, function (t) {
      each(links, function (a) { a.removeAttribute('aria-current'); });
      each($$('.toc-list li.is-open', el), function (li) { li.classList.remove('is-open'); });
      t.link.setAttribute('aria-current', 'true');
      var top = t.link.closest('.toc-list > li');
      if (top) top.classList.add('is-open');
      if (cur) cur.textContent = t.link.textContent;
      if (mq.matches) {                          /* keep the active item visible inside a long sticky TOC */
        var r = t.link.getBoundingClientRect(), box = el.getBoundingClientRect();
        if (r.top < box.top + 40 || r.bottom > box.bottom - 40) el.scrollTop += r.top - box.top - box.height / 3;
      }
    });
  }

  function scrollspyBar(el) {
    var links = $$('a[href^="#"]', el), scroller = $('.pillbar-scroll', el) || el;
    spy(links, function (t) {
      each(links, function (a) { a.removeAttribute('aria-current'); a.classList.remove('is-current'); });
      t.link.setAttribute('aria-current', 'true'); t.link.classList.add('is-current');
      var l = t.link.offsetLeft - scroller.clientWidth / 2 + t.link.offsetWidth / 2;
      scroller.scrollLeft = Math.max(0, l);
    });
  }

  /* ------------------------------------------ pill bars: fades + active pill */
  function pillbars() {
    each($$('.pillbar'), function (bar) {
      var sc = $('.pillbar-scroll', bar);
      if (!sc) return;
      function upd() {
        var max = sc.scrollWidth - sc.clientWidth;
        bar.classList.toggle('fade-l', sc.scrollLeft > 4);
        bar.classList.toggle('fade-r', max > 4 && sc.scrollLeft < max - 4);
      }
      /* Centre the current pill in the scrolling row. Runs again once the web font has
         loaded, because Atkinson is narrower than the fallback the first pass measured. */
      function centre() {
        var cur = $('[aria-current="page"]', sc);
        if (!cur || sc.scrollWidth <= sc.clientWidth) return upd();
        var r = cur.getBoundingClientRect(), b = sc.getBoundingClientRect();
        sc.scrollLeft += (r.left - b.left) - (b.width - r.width) / 2;
        upd();
      }
      bar.__centre = centre;
      sc.addEventListener('scroll', upd, { passive: true });
      win.addEventListener('resize', upd);
      centre();
      if (doc.fonts && doc.fonts.ready) doc.fonts.ready.then(centre);
    });
  }

  /* Members sub-nav (round 3): a static list of five pills, no JS needed.
     Kept as a no-op so page scripts that call it keep working. */
  SPO.syncSubnav = function () {};

  /* Re-centre after a page script moves aria-current (e.g. the Forms filter on Documents) */
  SPO.centrePill = function () { each($$('.pillbar'), function (b) { if (b.__centre) b.__centre(); }); };

  /* --------------------------------------------------- document library */
  var ICON_FOR = function (d) {
    var f = (d.format || '').toLowerCase();
    if (d.status === 'groupsio') return 'i-people';
    if (/form/.test(f)) return 'i-form';
    if (/course|video/.test(f)) return 'i-book';
    if (/web page/.test(f)) return 'i-link';
    if (/software/.test(f)) return 'i-radio';
    return 'i-doc';
  };
  function sourceBadge(d) {
    var lbl = d.source && d.source.label || '';
    switch (d.status) {
      case 'current': return ['badge--ours', 'Spokane County ARES-ACS'];
      case 'page': return ['badge--ours', 'Spokane County ARES-ACS'];
      case 'groupsio': return ['badge--members', 'Members-only on groups.io'];
      case 'section': return ['badge--section', 'EWA Section (ag7qp.com)'];
      case 'recovering': return ['badge--coming', 'Coming back'];
      case 'historical': return ['badge--history', 'Historical'];
      case 'official':
        if (/FEMA|DHS/.test(lbl)) return ['badge--official', 'Official: FEMA'];
        if (/ARRL/.test(lbl)) return ['badge--official', 'ARRL'];
        if (/Spokane County/.test(lbl)) return ['badge--official', 'Official: Spokane County'];
        if (/NWS/.test(lbl)) return ['badge--official', 'Official: NWS'];
        return ['badge--official', lbl && !/being chosen/.test(lbl) ? 'Official: ' + lbl.replace(/ \(.*\)$/, '') : 'Official source'];
    }
    return ['badge--section', lbl || 'Link'];
  }
  function opensText(d) {
    var host = '';
    try { host = d.href && /^https?:/.test(d.href) ? new URL(d.href).hostname.replace(/^www\./, '') : ''; } catch (e) { host = ''; }
    switch (d.status) {
      case 'current': return 'Download ' + d.format;
      case 'groupsio': return 'Opens groups.io (members only)';
      case 'recovering': return 'Not available yet';
      case 'historical': return d.href ? 'Kept for the record' : 'Not online';
      case 'page': return d.href && d.href !== '#' ? 'Page on this site' : 'Page coming';
      default: return d.href ? (host ? 'Opens ' + host : 'Opens the source') : 'Source being chosen';
    }
  }
  function versionText(v) {
    if (!v) return '';
    if (/^\d{4}-\d{2}-\d{2}$/.test(v)) return 'Updated ' + fmt(v, 'day') + ', ' + v.slice(0, 4);
    return v;
  }
  SPO.docRowHTML = function (d) {
    var b = sourceBadge(d), ext = d.href && /^https?:/.test(d.href), title = esc(d.title);
    var linkText = title + '<span class="sr-only">' + (d.status === 'current' ? ' (' + esc(d.format) + (d.version ? ', version ' + esc(d.version) : '') + ')' : ext ? ' (' + esc(opensText(d).toLowerCase()) + ')' : '') + '</span>';
    var t = d.href ? '<a href="' + attr(d.href) + '"' + (ext ? ' rel="noopener"' : '') + '>' + linkText + (ext ? '<svg class="icon ext" aria-hidden="true"><use href="#i-external"/></svg>' : '') + '</a>' : title;
    var extra = '';
    if (d.howTo) extra += '<a href="' + attr(d.howTo.href) + '" rel="noopener">' + esc(d.howTo.label) + '</a>';
    if (d.extra) extra += (extra ? '&emsp;' : '') + '<a href="' + attr(d.extra.href) + '" rel="noopener">' + esc(d.extra.label) + '</a>';
    return '<li class="doc-row" id="' + attr(d.id) + '" data-cat="' + attr(d.category) + '" data-status="' + attr(d.status) + '"' + (d.verify ? ' data-verify="true"' : '') + '>' +
      '<span class="doc-icon" aria-hidden="true"><svg class="icon"><use href="#' + ICON_FOR(d) + '"/></svg></span>' +
      '<div class="doc-main"><h3 class="doc-title">' + t + '</h3><p class="doc-desc">' + esc(d.description) + '</p>' +
        '<p class="doc-meta"><span class="badge badge--format">' + esc(d.format) + '</span>' + (d.version ? '<span>' + esc(versionText(d.version)) + '</span>' : '') + (d.author ? '<span>by <span class="callsign">' + esc(d.author) + '</span></span>' : '') + '</p>' +
        (d.note || extra ? '<p class="doc-note">' + (d.note ? esc(d.note) : '') + (d.note && extra ? ' ' : '') + extra + '</p>' : '') +
      '</div>' +
      '<div class="doc-go"><span class="badge ' + b[0] + '">' + esc(b[1]) + '</span><span>' + esc(opensText(d)) + '</span></div></li>';
  };
  function docLibrary(el) {
    var input = $('input[name="q"]', el), chipsBox = $('[data-doc-chips]', el), count = $('.result-count', el), listBox = $('[data-docs]', el), empty = $('[data-docs-empty]', el);
    var hideComing = $('[data-hide-coming]', el);
    if (!listBox) return;
    var cats = S.documentCategories, state = { q: param('q') || '', cat: param('cat') || '' };
    var h = (win.location.hash || '').slice(1);
    cats.forEach(function (c) { if (c.id === h) state.cat = c.id; });
    if (state.cat && !cats.some(function (c) { return c.id === state.cat; })) state.cat = '';
    if (input) input.value = state.q;

    if (listBox.getAttribute('data-render') !== 'false') {
      var out = '';
      cats.forEach(function (c) {
        var docs = F.documents({ category: c.id });
        out += '<section class="doc-cat" id="' + c.id + '" aria-labelledby="h-' + c.id + '"><h2 id="h-' + c.id + '">' + esc(c.label) + '</h2><p>' + esc(c.blurb) + '</p><ul class="doc-list">' + docs.map(SPO.docRowHTML).join('') + '</ul></section>';
      });
      listBox.innerHTML = out;
    }
    if (chipsBox) {
      var ch = '<button type="button" class="fchip" data-cat="">All <span class="count">' + S.documents.length + '</span></button>';
      cats.forEach(function (c) { ch += '<button type="button" class="fchip" data-cat="' + c.id + '">' + esc(c.label) + ' <span class="count">' + F.documents({ category: c.id }).length + '</span></button>'; });
      chipsBox.innerHTML = ch;
      chipsBox.addEventListener('click', function (e) { var b = e.target.closest('.fchip'); if (!b) return; state.cat = b.getAttribute('data-cat'); run(true); });
    }
    function syncUrl() {
      var qs = [];
      if (state.q) qs.push('q=' + encodeURIComponent(state.q));
      if (state.cat) qs.push('cat=' + state.cat);
      try { win.history.replaceState(null, '', (qs.length ? '?' + qs.join('&') : win.location.pathname.split('/').pop()) + (win.location.hash && !state.cat ? win.location.hash : '')); } catch (e) { /* file:// */ }
    }
    function run(user) {
      var hits = F.documents({ q: state.q, category: state.cat || null });
      if (hideComing && hideComing.checked) hits = hits.filter(function (d) { return d.status !== 'recovering'; });
      var ids = {}; hits.forEach(function (d) { ids[d.id] = true; });
      $$('.doc-row', listBox).forEach(function (r) { r.classList.toggle('is-hidden', !ids[r.id]); });
      $$('.doc-cat', listBox).forEach(function (s) { s.hidden = !$('.doc-row:not(.is-hidden)', s); });
      if (chipsBox) $$('.fchip', chipsBox).forEach(function (b) { b.setAttribute('aria-pressed', b.getAttribute('data-cat') === state.cat ? 'true' : 'false'); });
      var catLabel = state.cat ? cats.filter(function (c) { return c.id === state.cat; })[0].label : '';
      var msg = !hits.length ? 'Nothing matches' + (state.q ? ' \u201c' + state.q + '\u201d' : '') + (catLabel ? ' in ' + catLabel : '') + '.'
        : state.q ? hits.length + (hits.length === 1 ? ' document matches' : ' documents match') + ' \u201c' + state.q + '\u201d' + (catLabel ? ' in ' + catLabel : '') + '.'
        : catLabel ? hits.length + ' documents in ' + catLabel + '.' : 'Showing all ' + hits.length + ' documents.';
      if (count) count.textContent = msg;
      if (empty) empty.hidden = !!hits.length;
      if (user) syncUrl();
    }
    if (input) {
      var form = input.form, t;
      input.addEventListener('input', function () { clearTimeout(t); t = setTimeout(function () { state.q = input.value.trim(); run(true); }, 160); });
      if (form) form.addEventListener('submit', function (e) { e.preventDefault(); state.q = input.value.trim(); run(true); });
    }
    if (hideComing) hideComing.addEventListener('change', function () { run(true); });
    var clear = $('[data-docs-clear]', el);
    if (clear) clear.addEventListener('click', function (e) { e.preventDefault(); state.q = ''; state.cat = ''; if (input) input.value = ''; run(true); if (input) input.focus(); });
    run(false);
    var target = h && doc.getElementById(h);
    if (target && target.classList.contains('doc-row')) { target.classList.add('is-target'); setTimeout(function () { target.scrollIntoView({ block: 'center' }); }, 60); }
    else if (state.cat && h === state.cat) { setTimeout(function () { var t2 = doc.getElementById(state.cat); if (t2) t2.scrollIntoView(); }, 60); }
  }

  /* Members hub search with live suggestions */
  function docSearch(el) {
    var input = $('input[name="q"]', el), box = $('.suggest', el);
    if (!input) return;
    if (!box) { box = doc.createElement('ul'); box.className = 'suggest'; box.hidden = true; el.appendChild(box); }
    box.id = box.id || 'suggest-' + Math.random().toString(36).slice(2, 7);
    box.setAttribute('aria-label', 'Matching documents');
    input.setAttribute('aria-describedby', box.id);
    var action = (input.form && input.form.getAttribute('action')) || 'documents.html';
    input.addEventListener('input', function () {
      var q = input.value.trim();
      if (q.length < 2) { box.hidden = true; box.innerHTML = ''; return; }
      var hits = F.documents({ q: q });
      box.innerHTML = hits.slice(0, 5).map(function (d) {
        return '<li><a href="' + action + '#' + d.id + '">' + esc(d.title) + '<small>' + esc(d.format) + (d.version ? ', ' + esc(d.version) : '') + '</small></a></li>';
      }).join('') + '<li><a href="' + action + '?q=' + encodeURIComponent(q) + '"><b>' + (hits.length ? 'See all ' + hits.length + ' results' : 'No matches. Search the whole library') + '</b></a></li>';
      box.hidden = false;
    });
  }

  /* ------------------------------------------------------ rota + winlink */
  /* Round 3: call signs only (no names), the Note column uses data.js netOn().note
     ("Simplex", "Winlink night", "GMRS net, 7:30 PM"); Winlink nights link to the
     assignments. Columns: Tuesday | Net control | Note. */
  function rotaTable(el) {
    var today = SPO.now().date, next = SPO.nextNet().net, out = '';
    var showPast = el.getAttribute('data-past') !== 'false';
    each(S.rota, function (r) {
      var past = r.date < today && (!next || r.date < next.date), isNext = next && r.date === next.date;
      if (past && !showPast) return;
      var n = F.netOn(r.date) || {}, note = n.note || '';
      var noteHTML = note === 'Winlink night' ? '<a href="' + root() + 'members/exercises.html#winlink-assignments">Winlink night</a>' : esc(note);
      out += '<tr class="' + (past ? 'is-past' : '') + (isNext ? ' is-next' : '') + '"' + (n.verify && !past ? ' data-verify="true"' : '') + '>' +
        '<th scope="row" data-label="Tuesday">' + fmt(r.date) + (isNext ? ' <span class="tag tag--net">Next</span>' : '') + '</th>' +
        '<td data-label="Net control">' + (r.callsign ? '<span class="callsign">' + esc(r.callsign) + '</span>' : 'Open') + '</td>' +
        '<td data-label="Note">' + noteHTML + (past ? ' (past)' : '') + '</td></tr>';
    });
    el.innerHTML = out;
  }
  function winlinkTable(el) {
    var today = SPO.now().date, nx = nextWinlink(), out = '';
    each(S.winlink.assignments, function (a) {
      var past = a.date < today, isNext = nx && nx.date === a.date;
      out += '<tr class="' + (past ? 'is-past' : '') + (isNext ? ' is-next' : '') + '"><th scope="row" data-label="Date">' + fmt(a.date) + (isNext ? ' <span class="tag tag--net">Next</span>' : '') + '</th>' +
        '<td data-label="Assignment">' + esc(a.task) + '</td><td data-label="Form">' + esc(a.form) + (past ? ' (past)' : '') + '</td></tr>';
    });
    el.innerHTML = out;
  }

  /* ------------------------------------------------------------ agenda */
  var TYPE_LABEL = { exercise: 'Exercise', training: 'Training', meeting: 'Meeting', net: 'Net', gmrs: 'Net', 'public-service': 'Public service', 'on-air': 'On-air event' };
  function agenda(el) {
    var days = +(el.getAttribute('data-days') || 240), clock = el.getAttribute('data-clock') || '24';
    var tabsBox = $('[data-agenda-tabs]', el), chipsBox = $('[data-agenda-filters]', el), listBox = $('[data-agenda-list]', el) || el;
    var all = F.upcoming({ from: SPO.now().date, days: days, types: ['exercise', 'training', 'meeting', 'public-service', 'on-air', 'net'] });
    var months = [], byMonth = {};
    each(all, function (x) {
      var k = (x.inProgress ? SPO.now().date : x.date).slice(0, 7);
      if (!byMonth[k]) { byMonth[k] = { key: k, items: [], nets: 0 }; months.push(byMonth[k]); }
      if (x.type === 'net' && x.mode === 'repeater' && !x.open) { byMonth[k].nets++; return; }
      byMonth[k].items.push(x);
    });
    function detailHTML(x) {
      var ev = eventById(x.id), mt = null;
      if (x.type === 'meeting') each(S.meetings, function (m) { if (x.id.indexOf(m.id + '-') === 0) mt = m; });
      var rows = '';
      var what = ev ? ev.summary : mt ? mt.summary : x.type === 'net' ? (x.modeLabel + '. ' + (x.netControl ? 'Net control ' + x.netControl + '.' : x.open ? 'Net control slot open: volunteer on groups.io.' : '')) : '';
      if (what) rows += '<dt>What</dt><dd>' + esc(what) + '</dd>';
      rows += '<dt>Where</dt><dd>' + esc(x.where || (x.type === 'net' ? 'On the air, W7GBU' : 'On the air or at your station')) + '</dd>';
      if (ev && ev.tasks) rows += '<dt>Tasks</dt><dd><ol>' + ev.tasks.map(function (t) { return '<li>' + esc(t) + '</li>'; }).join('') + '</ol></dd>';
      if (ev && ev.links) rows += '<dt>Links</dt><dd>' + ev.links.map(function (l) { return '<a href="' + attr(l.href) + '" rel="noopener">' + esc(l.label) + '</a>'; }).join('&emsp;') + '</dd>';
      if (ev && ev.contact) rows += '<dt>Contact</dt><dd>' + esc(ev.contact.role) + ', <span class="callsign">' + esc(ev.contact.callsign) + '</span></dd>';
      var cal = '<button type="button" class="btn btn--quiet btn--small" data-ics="event" data-id="' + attr(x.id) + '" data-title="' + attr(x.title) + '" data-date="' + x.date + '"' +
        (x.endDate ? ' data-end-date="' + x.endDate + '"' : '') + (x.time ? ' data-start="' + x.time + '"' + (x.endTime ? ' data-end="' + x.endTime + '"' : '') : ' data-all-day="true"') +
        ' data-where="' + attr(x.where || '') + '"><svg class="icon" aria-hidden="true"><use href="#i-calendar"/></svg>Add to calendar</button>';
      return '<div class="agenda-body"><dl>' + rows + '</dl><p style="margin-top:12px" data-status-scope>' + cal + ' <span class="status-msg" role="status"></span></p></div>';
    }
    function itemHTML(x) {
      var when = x.time ? timeRange(x.time, x.endTime, clock) : (x.displayTime || (x.endDate ? fmtRange(x.date, x.endDate) : 'All day'));
      var tag = x.inProgress ? '<span class="tag tag--now">Happening now</span>' : '<span class="tag tag--' + (x.type === 'gmrs' ? 'net' : x.type) + '">' + esc(TYPE_LABEL[x.type] || x.type) + '</span>';
      var title = x.type === 'net' ? (x.mode !== 'repeater' ? 'Tuesday net, ' + (x.mode === 'simplex' ? 'simplex (5th Tuesday)' : 'Winlink night') : 'Tuesday net') + (x.open ? ': net-control slot open' : '') : x.title;
      return '<li class="agenda-item' + (x.inProgress ? ' is-now' : '') + '" id="' + attr(x.id) + '" data-type="' + attr(x.type === 'gmrs' ? 'net' : x.type) + '"' + (x.verify ? ' data-verify="true"' : '') + '><details><summary>' +
        dateBlock(x.date, 'date-block--sm' + (x.type === 'net' || x.type === 'meeting' ? ' date-block--quiet' : '')) +
        '<span><span class="agenda-title">' + esc(title) + '</span><span class="agenda-meta">' + esc(x.endDate ? fmtRange(x.date, x.endDate) + (x.time ? ', ' + when : ', all day') : fmt(x.date) + ', ' + when) + '</span></span>' +
        '<span class="agenda-side">' + tag + '<svg class="icon" aria-hidden="true"><use href="#i-chevron-down"/></svg></span></summary>' + detailHTML(x) + '</details></li>';
    }
    var out = '';
    each(months, function (m) {
      out += '<section class="agenda-month" id="m-' + m.key + '" data-month="' + m.key + '"><h3>' + fmt(m.key + '-01', 'month') + '</h3><ol class="agenda">' + m.items.map(itemHTML).join('') + '</ol>' +
        (m.nets ? '<p class="agenda-also">Also every Tuesday: the net at ' + time('20:00', clock) + ' on W7GBU (' + m.nets + ' more this month).</p>' : '') + '</section>';
    });
    listBox.innerHTML = out;
    var state = { month: '', type: '' };
    function run() {
      each($$('.agenda-month', listBox), function (s) {
        var mOk = !state.month || s.getAttribute('data-month') === state.month, any = false;
        each($$('.agenda-item', s), function (li) { var ok = mOk && (!state.type || li.getAttribute('data-type') === state.type || (state.type === 'training' && li.getAttribute('data-type') === 'meeting')); li.classList.toggle('is-hidden', !ok); if (ok) any = true; });
        s.hidden = !mOk || (!any && !!state.type);
      });
      if (tabsBox) each($$('.date-tab', tabsBox), function (b) { b.setAttribute('aria-pressed', b.getAttribute('data-month') === state.month ? 'true' : 'false'); });
      if (chipsBox) each($$('.fchip', chipsBox), function (b) { b.setAttribute('aria-pressed', b.getAttribute('data-type') === state.type ? 'true' : 'false'); });
    }
    if (tabsBox) {
      tabsBox.innerHTML = '<button type="button" class="date-tab" data-month=""><b>All</b><small>Next ' + months.length + ' months</small></button>' + months.map(function (m) {
        return '<button type="button" class="date-tab" data-month="' + m.key + '"><b>' + MON[+m.key.slice(5) - 1] + '</b><small>' + (m.items.length + m.nets) + ' items</small></button>';
      }).join('');
      tabsBox.addEventListener('click', function (e) { var b = e.target.closest('.date-tab'); if (b) { state.month = b.getAttribute('data-month'); run(); } });
    }
    if (chipsBox) {
      var types = [['', 'All'], ['exercise', 'Exercises'], ['training', 'Training & meetings'], ['net', 'Nets'], ['public-service', 'Public service'], ['on-air', 'On-air events']];
      chipsBox.innerHTML = types.map(function (t) { return '<button type="button" class="fchip" data-type="' + t[0] + '">' + t[1] + '</button>'; }).join('');
      chipsBox.addEventListener('click', function (e) { var b = e.target.closest('.fchip'); if (b) { state.type = b.getAttribute('data-type'); run(); } });
    }
    run();
    var h = (win.location.hash || '').slice(1), t = h && doc.getElementById(h);
    if (t && t.classList.contains('agenda-item')) { var d = $('details', t); if (d) d.open = true; }
  }

  /* ------------------------------------------------------ deep-link landing
     Arriving at page.html#section from another page: the <head> one-liner turns
     smooth scrolling off while the page loads. Widgets rendered from data.js and
     the web-font swap change the height of everything above the target, so the
     first jump can land short. Once fonts and images are in, correct the landing
     (unless the reader has started scrolling), then give in-page links their
     smooth scrolling back. Ported from option A (round 2, site.js). */
  function hashLanding() {
    if (!win.location.hash || !win.Promise) { html.style.scrollBehavior = ''; return; }
    var touched = false;
    each(['wheel', 'touchstart', 'keydown', 'mousedown'], function (t) { win.addEventListener(t, function () { touched = true; }, { once: true, passive: true }); });
    function settle() {
      var id = decodeURIComponent(win.location.hash.slice(1)), el = id && doc.getElementById(id);
      if (el && !touched) {
        var want = (parseFloat(getComputedStyle(el).scrollMarginTop) || 0) + (parseFloat(getComputedStyle(html).scrollPaddingTop) || 0);
        if (Math.abs(el.getBoundingClientRect().top - want) > 4) el.scrollIntoView({ block: 'start' });
      }
      html.style.scrollBehavior = '';
    }
    var loaded = new Promise(function (r) { if (doc.readyState === 'complete') r(); else win.addEventListener('load', r); });
    Promise.all([loaded, doc.fonts && doc.fonts.ready]).then(function () { setTimeout(settle, 60); });
  }

  /* ------------------------------------------------------------- verify */
  function verifyMode() { if (param('verify') === '1') html.classList.add('verify-on'); }

  /* --------------------------------------------------------------- boot */
  var WIDGETS = {
    'week': weekCard, 'coming-up': comingUpWidget,
    'toc': toc, 'scrollspy': scrollspyBar, 'doc-library': docLibrary, 'doc-search': docSearch,
    'rota': rotaTable, 'winlink': winlinkTable, 'agenda': agenda
  };
  SPO.widgets = WIDGETS;
  function boot() {
    verifyMode(); menu(); terms(); copyButtons(); icsButtons(); pillbars();
    var needsData = { week: 1, 'coming-up': 1, 'doc-library': 1, 'doc-search': 1, rota: 1, winlink: 1, agenda: 1 };
    var order = ['week', 'coming-up', 'toc', 'scrollspy', 'doc-library', 'doc-search', 'rota', 'winlink', 'agenda'];
    each(order, function (name) {
      each($$('[data-widget="' + name + '"]'), function (el) {
        if (needsData[name] && !S) return;
        try { WIDGETS[name](el); } catch (err) { if (win.console) console.warn('SPO widget "' + name + '" kept its fallback:', err); }
      });
    });
    if (S) { try { dateSlots(); } catch (e2) { /* keep fallbacks */ } }
    hashLanding();
  }
  if (doc.readyState === 'loading') doc.addEventListener('DOMContentLoaded', boot); else boot();
})(window, document);
