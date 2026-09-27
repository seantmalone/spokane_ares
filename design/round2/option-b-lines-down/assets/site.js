/*!
 * Spokane County ARES-ACS | Round 2, Option B: "Carry the Message"
 * site.js: progressive enhancement shared by every page.
 *
 * Rules
 *  - Every page reads and navigates with this file absent. Widgets replace a
 *    static fallback that already sits in the HTML.
 *  - Load order at the end of <body>: assets/data.js, then assets/site.js
 *    (members pages: ../assets/…). No fetch(); works from file://.
 *  - Each widget below is one self-contained function that maps to one
 *    WordPress block/pattern or one SSG partial. The data-* hook it reads is
 *    named in its comment. See DESIGN.md, "JavaScript hooks".
 *  - Demo params: ?today=YYYY-MM-DD (from data.js), ?now=HH:MM (Pacific time
 *    of day, e.g. ?today=2026-09-29&now=20:15 shows "On the air now"),
 *    ?from=unlicensed|licensed|ares|acs (join path), ?q= and ?cat= (library),
 *    ?verify=1 (outline every [data-verify]).
 */
(function () {
  'use strict';

  var doc = document, root = doc.documentElement;
  root.classList.add('js');
  var S = window.SPOKARES || null;
  var F = S && S.fn;
  var GROUPS = S ? S.links.groupsio.main : 'https://spokaneares-acs.groups.io/g/main';
  var DAYLONG = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
  var DAY = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
  var MON = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

  /* ------------------------------------------------------------ helpers */
  function $(sel, ctx) { return (ctx || doc).querySelector(sel); }
  function $$(sel, ctx) { return Array.prototype.slice.call((ctx || doc).querySelectorAll(sel)); }
  function esc(s) { return String(s == null ? '' : s).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); }
  function param(name) { try { return new URLSearchParams(window.location.search).get(name); } catch (e) { return null; } }
  function rootPath() { return doc.body.getAttribute('data-root') || ''; }
  function verifyAttr(on) { return on ? ' data-verify="true"' : ''; }
  function d(iso) { return F.parse(iso); }
  function dayLong(iso) { var x = d(iso); return DAYLONG[x.getDay()] + ', ' + MON[x.getMonth()] + ' ' + x.getDate(); }
  function dayShort(iso) { var x = d(iso); return DAY[x.getDay()] + ', ' + MON[x.getMonth()] + ' ' + x.getDate(); }
  function addDays(iso, n) { var x = d(iso); x.setDate(x.getDate() + n); return F.iso(x); }
  function t24(hhmm) { return hhmm ? hhmm.replace(':', '') : ''; }
  function time(hhmm, fmt) { return fmt === '24' ? t24(hhmm) : F.fmtTime(hhmm); }
  function range(a, b, fmt) {
    if (!a) return '';
    if (!b) return time(a, fmt);
    return time(a, fmt) + (fmt === '24' ? '–' : ' to ') + time(b, fmt);
  }
  function slot(el, name, html) { var t = el.querySelector('[data-slot="' + name + '"]'); if (t) { t.innerHTML = html; } return t; }

  /* Mockup clock: the date comes from data.js (?today= override); the time of
     day from ?now=HH:MM, else the real time in America/Los_Angeles. */
  function clock() {
    var mins = null, p = param('now');
    if (p && /^\d{1,2}:\d{2}$/.test(p)) { var a = p.split(':'); mins = (+a[0]) * 60 + (+a[1]); }
    else {
      try {
        var parts = new Intl.DateTimeFormat('en-US', { timeZone: 'America/Los_Angeles', hour: '2-digit', minute: '2-digit', hourCycle: 'h23' }).format(new Date()).split(':');
        mins = (+parts[0]) * 60 + (+parts[1]);
      } catch (e) { var n = new Date(); mins = n.getHours() * 60 + n.getMinutes(); }
    }
    return { date: F.today(), mins: mins };
  }

  /* The next weekly net. Tuesday 20:00-21:00 = on the air now; after 21:00
     it rolls to the following Tuesday. */
  function netNow() {
    var c = clock(), net = F.nextNet(c.date), live = false;
    if (net && net.date === c.date) {
      if (c.mins >= 21 * 60) net = F.nextNet(addDays(c.date, 1));
      else if (c.mins >= 20 * 60) live = true;
    }
    return { net: net, live: live };
  }
  function netControl(net, opts) {
    opts = opts || {};
    if (net.netControl) return (opts.label === false ? '' : 'Net control ') + esc(net.netControl);
    if (net.open) return 'Net control slot open: <a href="' + GROUPS + '" class="ext" rel="noopener">volunteer on groups.io<span class="vh"> (opens groups.io)</span></a>';
    return 'Net control: see <a href="' + GROUPS + '" class="ext" rel="noopener">groups.io<span class="vh"> (opens groups.io)</span></a>';
  }
  function netVariant(net) {
    if (net.mode === 'simplex') return '<span data-verify="true">Fifth Tuesday: the net starts on simplex, then moves to the W7GBU repeater.</span>';
    if (net.mode === 'winlink') {
      var a = net.winlinkAssignment;
      return '<span data-verify="true">Winlink night: have paper and pencil ready.</span>' + (a ? ' Assignment: ' + esc(a.task) : '');
    }
    return '';
  }
  function gmrsNote(net, fmt) { return net.gmrsSameDay ? '<span data-verify="true">ACS GMRS net at ' + (fmt === '24' ? '1930' : '7:30 PM') + ' before the main net.</span>' : ''; }

  /* Global status toast (one live region for copy and calendar actions). */
  var toastEl = null, toastTimer = null;
  function toast(msg) {
    if (!toastEl) {
      toastEl = doc.createElement('div');
      toastEl.className = 'toast';
      toastEl.setAttribute('role', 'status');
      toastEl.setAttribute('aria-live', 'polite');
      doc.body.appendChild(toastEl);
    }
    toastEl.textContent = msg;
    toastEl.classList.add('is-on');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(function () { toastEl.classList.remove('is-on'); }, 3600);
  }

  /* ------------------------------------------------------------ 1. Primary nav (mobile menu)
     Hook: .nav__toggle[aria-controls] inside .nav. */
  function primaryNav() {
    var t = $('.nav__toggle'); if (!t) return;
    var list = doc.getElementById(t.getAttribute('aria-controls'));
    function set(open) { t.setAttribute('aria-expanded', open ? 'true' : 'false'); }
    t.addEventListener('click', function () { set(t.getAttribute('aria-expanded') !== 'true'); });
    doc.addEventListener('keydown', function (e) { if (e.key === 'Escape' && t.getAttribute('aria-expanded') === 'true') { set(false); t.focus(); } });
    doc.addEventListener('click', function (e) { if (t.getAttribute('aria-expanded') === 'true' && !e.target.closest('.nav')) set(false); });
    if (list) $$('a', list).forEach(function (a) { a.addEventListener('click', function () { set(false); }); });
  }

  /* ------------------------------------------------------------ 2. Utility bar next-net line
     Hook: [data-widget="next-net-line"] (the static fallback is the standing pattern). */
  function nextNetLine() {
    $$('[data-widget="next-net-line"]').forEach(function (el) {
      var n = netNow(), net = n.net; if (!net) return;
      var where = net.mode === 'simplex'
        ? '<span class="u-more" data-verify="true">Fifth Tuesday: starts on simplex, then W7GBU 147.300 MHz.</span>'
        : '<span class="u-more">on W7GBU 147.300 MHz.</span>';
      var html;
      if (n.live) {
        html = '<b><span class="live-dot" aria-hidden="true"></span>On the air now:</b> <span class="u-long">the Tuesday net </span>' +
          '<span class="u-long">on W7GBU 147.300 MHz.</span><span class="u-short">on 147.300</span> <span class="u-more">' + netControl(net) + '.</span>';
      } else {
        html = '<b class="u-label">Next net:</b> <span class="u-long">' + dayLong(net.date) + ', 8:00 PM</span><span class="u-short">' + dayShort(net.date) + ', 8:00 PM</span>' +
          (net.mode === 'simplex' ? '. ' : ' ') + where + ' <span class="u-more">' + netControl(net) + '.</span>';
      }
      el.innerHTML = html;
    });
  }

  /* ------------------------------------------------------------ 3. Net card
     Hook: [data-widget="net-card"] with optional data-format="24".
     Slots: [data-slot="label"] ("Next net:" or "On the air now:"), "when"
     (the date and time), "netcontrol", "note" (simplex, Winlink or GMRS; hidden when empty).
     data-nc-label="false" drops the "Net control " prefix (for a card with its own "Net control" label). */
  function netCards() {
    $$('[data-widget="net-card"]').forEach(function (el) {
      var fmt = el.getAttribute('data-format') || '12';
      var n = netNow(), net = n.net; if (!net) return;
      slot(el, 'label', n.live ? '<span class="live-dot" aria-hidden="true"></span>On the air now:' : 'Next net:');
      slot(el, 'when', n.live ? 'the Tuesday net, on W7GBU 147.300 MHz' : (fmt === '24' ? dayShort(net.date) + ', 2000' : dayLong(net.date) + ', 8:00 PM'));
      slot(el, 'netcontrol', netControl(net, { label: el.getAttribute('data-nc-label') !== 'false' }));
      var note = [netVariant(net), gmrsNote(net, fmt)].filter(Boolean).join(' ');
      var noteEl = slot(el, 'note', note);
      if (noteEl) noteEl.hidden = !note;
      el.setAttribute('data-net-date', net.date);
    });
  }

  /* ------------------------------------------------------------ 4. Mini next-net (members sidebar)
     Hook: [data-widget="next-net-mini"] with slots "main", "sub", "flag". */
  function nextNetMini() {
    $$('[data-widget="next-net-mini"]').forEach(function (el) {
      var n = netNow(), net = n.net; if (!net) return;
      slot(el, 'main', n.live ? 'On the air now' : dayShort(net.date) + ', 2000');
      slot(el, 'sub', net.netControl ? 'Net control ' + esc(net.netControl) : (net.open ? 'Net control slot open' : 'Net control: see groups.io'));
      var flag = net.mode === 'simplex' ? '<span data-verify="true">Simplex first (5th Tuesday)</span>' : (net.mode === 'winlink' ? '<span data-verify="true">Winlink night</span>' : '');
      var f = slot(el, 'flag', flag); if (f) f.hidden = !flag;
    });
  }

  /* ------------------------------------------------------------ 5. Upcoming events list
     Hook: <ul class="events" data-widget="upcoming" data-days="30" data-limit="4"
       data-types="exercise,meeting,training" data-exclude="shares" data-merge="workshops"
       data-format="12|24" data-link="members/exercises.html">
     Renders .event rows with a date slab; multi-day events in progress read
     "Happening now". Past items never appear. */
  function mergeWorkshops(items, fmt) {
    var out = [], byDate = {};
    items.forEach(function (it) {
      if (it.type === 'meeting' && /^winlink-workshop-/.test(it.id) && byDate[it.date]) { byDate[it.date].after = 'Winlink workshop after, ' + range(it.time, it.endTime, fmt); return; }
      if (it.type === 'meeting' && /^workshop-/.test(it.id)) byDate[it.date] = it;
      out.push(it);
    });
    return out;
  }
  function eventRow(it, fmt, link) {
    var x = d(it.date), multi = it.endDate && it.endDate !== it.date;
    var slab = '<span class="date-slab' + (multi ? ' date-slab--range' : '') + '" aria-hidden="true"><span class="date-slab__mon">' + MON[x.getMonth()] + '</span>' +
      '<span class="date-slab__day">' + x.getDate() + (multi ? '–' + d(it.endDate).getDate() : '') + '</span>' +
      '<span class="date-slab__dow">' + DAY[x.getDay()] + (multi ? '–' + DAY[d(it.endDate).getDay()] : '') + '</span></span>';
    var dateText = multi ? DAY[x.getDay()] + '–' + DAY[d(it.endDate).getDay()] + ', ' + MON[x.getMonth()] + ' ' + x.getDate() + '–' + d(it.endDate).getDate() : dayShort(it.date);
    var when = it.time ? range(it.time, it.endTime, fmt) : (it.displayTime ? '<span data-verify="true">' + (it.displayTime === 'Evening' ? 'Evening' : esc(it.displayTime)) + '</span>' : (multi ? 'All weekend' : 'All day'));
    var where = it.type === 'meeting' ? ', Spokane County Emergency Management' : '';
    var title = link ? '<a href="' + rootPath() + link + '">' + esc(it.title) + '</a>' : esc(it.title);
    return '<li class="event"' + verifyAttr(it.verify) + '>' + slab + '<span class="event__body"><span class="event__title">' + title + '</span>' +
      '<span class="event__meta"><span class="vh">' + dateText + '. </span>' + when + where + (it.after ? '. <span data-verify="true">' + esc(it.after) + '</span>' : '') + '</span>' +
      (it.inProgress ? '<span class="event__now"><span class="tag tag--now">Happening now</span></span>' : '') + '</span></li>';
  }
  function upcoming() {
    $$('[data-widget="upcoming"]').forEach(function (el) {
      var days = +el.getAttribute('data-days') || 30, limit = +el.getAttribute('data-limit') || 0;
      var types = (el.getAttribute('data-types') || '').split(',').filter(Boolean);
      var excl = (el.getAttribute('data-exclude') || '').split(',').filter(Boolean);
      var fmt = el.getAttribute('data-format') || '12', link = el.getAttribute('data-link');
      var items = F.upcoming({ days: days, types: types.length ? types : null }).filter(function (it) {
        return !excl.some(function (x) { return it.id.indexOf(x) !== -1; });
      });
      if (el.getAttribute('data-merge') === 'workshops') items = mergeWorkshops(items, fmt);
      if (limit) items = items.slice(0, limit);
      if (!items.length) { el.innerHTML = '<li class="event"><span class="event__meta">Nothing on the calendar in the next ' + days + ' days. The Tuesday net runs every week.</span></li>'; return; }
      el.innerHTML = items.map(function (it) { return eventRow(it, fmt, link); }).join('');
    });
  }

  /* ------------------------------------------------------------ 6. Net-control rota
     Hook: <tbody data-widget="rota" data-weeks="6" data-names="true" data-past="1">
     (three columns: date th, net control, notes) or <ul data-widget="rota" data-variant="list">.
     Names appear only where data-names="true" (the members rota table). */
  function rota() {
    $$('[data-widget="rota"]').forEach(function (el) {
      var weeks = +el.getAttribute('data-weeks') || 4, names = el.getAttribute('data-names') === 'true';
      var past = +el.getAttribute('data-past') || 0, list = el.getAttribute('data-variant') === 'list';
      var today = F.today(), next = netNow().net, rows = [];
      var start = addDays(next ? next.date : today, -7 * past);
      for (var i = 0; i < weeks + past; i++) {
        var iso = addDays(start, 7 * i), net = F.netOn(iso); if (!net) continue;
        var isPast = iso < (next ? next.date : today), isNext = next && iso === next.date;
        var who = net.netControl ? '<b>' + esc(net.netControl) + '</b>' + (names && net.netControlName ? ', ' + esc(net.netControlName) : '')
          : (net.open ? '<span class="tag tag--open">Open</span> <a href="' + GROUPS + '" class="ext" rel="noopener">Volunteer on groups.io<span class="vh"> (opens groups.io)</span></a>'
            : '<span class="muted">Not yet published</span>');
        var notes = [];
        if (net.mode === 'simplex') notes.push('<span data-verify="true">Fifth Tuesday: simplex</span>');
        if (net.mode === 'winlink') notes.push('Winlink night');
        if (net.gmrsSameDay) notes.push('<span data-verify="true">ACS GMRS net at 1930</span>');
        if (isPast) notes.push('Past');
        var cls = isPast ? 'is-past' : (isNext ? 'is-next' : '');
        if (list) rows.push('<li class="' + cls + '"><time datetime="' + iso + '">' + dayShort(iso) + '</time> ' + who + (notes.length ? ' <span class="muted small">' + notes.join(', ') + '</span>' : '') + '</li>');
        else rows.push('<tr class="' + cls + '"><th scope="row"><time datetime="' + iso + '">' + dayShort(iso) + '</time></th><td data-label="Net control">' + who + '</td><td data-label="Notes">' + (notes.join(', ') || '<span class="muted">Weekly net</span>') + '</td></tr>');
      }
      el.innerHTML = rows.join('');
    });
  }

  /* ------------------------------------------------------------ 7. Winlink assignments
     Hooks: [data-widget="winlink-next"] (slots "date", "task", "form") and
     <tbody data-widget="winlink-table" data-past="1"> (Date / Assignment / Form). */
  function winlink() {
    if (!S.winlink) return;
    var today = F.today(), list = S.winlink.assignments;
    var upcomingA = list.filter(function (a) { return a.date >= today; });
    $$('[data-widget="winlink-next"]').forEach(function (el) {
      var a = upcomingA[0]; if (!a) return;
      slot(el, 'date', dayShort(a.date) + ', 1700 to 2100');
      slot(el, 'task', esc(a.task));
      slot(el, 'form', esc(a.form));
    });
    $$('[data-widget="winlink-table"]').forEach(function (el) {
      var past = +el.getAttribute('data-past') || 0;
      var pastRows = past ? list.filter(function (a) { return a.date < today; }).slice(-past) : [];
      var rows = pastRows.concat(upcomingA).map(function (a, i) {
        var isPast = a.date < today, isNext = a === upcomingA[0];
        return '<tr class="' + (isPast ? 'is-past' : (isNext ? 'is-next' : '')) + '"><th scope="row"><time datetime="' + a.date + '">' + dayShort(a.date) + '</time></th>' +
          '<td data-label="Assignment">' + esc(a.task) + (isPast ? ' <span class="muted">(past)</span>' : '') + '</td><td data-label="Form">' + esc(a.form) + '</td></tr>';
      });
      el.innerHTML = rows.join('');
    });
  }

  /* ------------------------------------------------------------ 8. Next meetings and "as of"
     Hooks: [data-widget="asof"] → "Schedule as of Sat, Sep 26, 2026."
            [data-widget="next-meeting" data-id="third-thursday"] → "Thu, Oct 15". */
  function misc() {
    $$('[data-widget="asof"]').forEach(function (el) { var t = F.today(), x = d(t); el.textContent = 'Schedule as of ' + dayShort(t) + ', ' + x.getFullYear() + '.'; });
    var meetings = F.nextMeetings(F.today(), 2);
    $$('[data-widget="next-meeting"]').forEach(function (el) {
      var id = el.getAttribute('data-id'), m = meetings.filter(function (x) { return x.id === id; })[0];
      if (m && m.dates.length) el.textContent = dayShort(m.dates[0]);
    });
  }

  /* ------------------------------------------------------------ 9. Copy radio settings
     Hook: any <button data-copy> (default text: SPOKARES.radio.copyText), or
     data-copy-text="…" for other text. Clipboard API, textarea fallback, toast. */
  function fallbackCopy(text) {
    var ta = doc.createElement('textarea');
    ta.value = text; ta.setAttribute('readonly', ''); ta.style.position = 'fixed'; ta.style.top = '-1000px';
    doc.body.appendChild(ta); ta.select();
    var ok = false; try { ok = doc.execCommand('copy'); } catch (e) { ok = false; }
    doc.body.removeChild(ta); return ok;
  }
  function copyText(text, done) {
    if (navigator.clipboard && window.isSecureContext) navigator.clipboard.writeText(text).then(function () { done(true); }, function () { done(fallbackCopy(text)); });
    else done(fallbackCopy(text));
  }
  function copyButtons() {
    doc.addEventListener('click', function (e) {
      var b = e.target.closest('[data-copy]'); if (!b) return;
      var text = b.getAttribute('data-copy-text') || (S ? S.radio.copyText : 'W7GBU 147.300 MHz, +600 kHz offset, 100 Hz tone');
      copyText(text, function (ok) { toast(ok ? 'Copied: ' + text : 'Copy did not work here. The settings are: ' + text); });
    });
  }

  /* ------------------------------------------------------------ 10. Add to calendar (.ics built in the browser)
     Hooks: <button data-ics="weekly-net"> (weekly RRULE from the next net) and
     <button data-ics="event" data-event-id="set-2026"> (one event from data.js). */
  var VTZ = ['BEGIN:VTIMEZONE', 'TZID:America/Los_Angeles', 'BEGIN:DAYLIGHT', 'TZOFFSETFROM:-0800', 'TZOFFSETTO:-0700', 'TZNAME:PDT', 'DTSTART:19700308T020000', 'RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=2SU', 'END:DAYLIGHT',
    'BEGIN:STANDARD', 'TZOFFSETFROM:-0700', 'TZOFFSETTO:-0800', 'TZNAME:PST', 'DTSTART:19701101T020000', 'RRULE:FREQ=YEARLY;BYMONTH=11;BYDAY=1SU', 'END:STANDARD', 'END:VTIMEZONE'];
  function icsEsc(s) { return String(s).replace(/\\/g, '\\\\').replace(/;/g, '\\;').replace(/,/g, '\\,').replace(/\n/g, '\\n'); }
  function stamp() { var n = new Date(); function p(v) { return (v < 10 ? '0' : '') + v; } return n.getUTCFullYear() + p(n.getUTCMonth() + 1) + p(n.getUTCDate()) + 'T' + p(n.getUTCHours()) + p(n.getUTCMinutes()) + '00Z'; }
  function icsFile(lines) { return ['BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//Spokane County ARES-ACS//Website//EN', 'CALSCALE:GREGORIAN', 'METHOD:PUBLISH'].concat(VTZ, lines, ['END:VCALENDAR']).join('\r\n'); }
  function download(name, body) {
    var blob = new Blob([body], { type: 'text/calendar;charset=utf-8' }), a = doc.createElement('a');
    a.href = URL.createObjectURL(blob); a.download = name; doc.body.appendChild(a); a.click();
    setTimeout(function () { URL.revokeObjectURL(a.href); a.remove(); }, 1500);
  }
  function calendarButtons() {
    doc.addEventListener('click', function (e) {
      var b = e.target.closest('[data-ics]'); if (!b || !F) return;
      e.preventDefault();
      if (b.getAttribute('data-ics') === 'weekly-net') {
        var net = netNow().net, ds = net.date.replace(/-/g, '');
        download('spokane-ares-acs-tuesday-net.ics', icsFile(['BEGIN:VEVENT', 'UID:tuesday-net@spokares.org', 'DTSTAMP:' + stamp(),
          'DTSTART;TZID=America/Los_Angeles:' + ds + 'T200000', 'DURATION:PT1H', 'RRULE:FREQ=WEEKLY;BYDAY=TU',
          'SUMMARY:' + icsEsc('Spokane County ARES-ACS Tuesday net'), 'LOCATION:' + icsEsc('W7GBU 147.300 MHz, +600 kHz, 100 Hz tone'),
          'DESCRIPTION:' + icsEsc('Weekly directed net. Visitors are welcome to check in after members. Some weeks run as a simplex or Winlink net; see the website.'), 'END:VEVENT']));
        toast('Calendar file ready: a weekly event for Tuesdays at 8:00 PM. Open it to add the net to your calendar.');
      } else {
        var id = b.getAttribute('data-event-id'), ev = S.events.filter(function (x) { return x.id === id; })[0]; if (!ev) return;
        var lines = ['BEGIN:VEVENT', 'UID:' + ev.id + '@spokares.org', 'DTSTAMP:' + stamp(), 'SUMMARY:' + icsEsc(ev.title)];
        if (ev.time) {
          var s = ev.start.replace(/-/g, '') + 'T' + t24(ev.time) + '00';
          lines.push('DTSTART;TZID=America/Los_Angeles:' + s, ev.endTime ? 'DTEND;TZID=America/Los_Angeles:' + ev.start.replace(/-/g, '') + 'T' + t24(ev.endTime) + '00' : 'DURATION:PT1H');
        } else {
          lines.push('DTSTART;VALUE=DATE:' + ev.start.replace(/-/g, ''), 'DTEND;VALUE=DATE:' + addDays(ev.end || ev.start, 1).replace(/-/g, ''));
        }
        if (ev.where) lines.push('LOCATION:' + icsEsc(ev.where));
        if (ev.summary) lines.push('DESCRIPTION:' + icsEsc(ev.summary));
        lines.push('END:VEVENT');
        download(ev.id + '.ics', icsFile(lines));
        toast('Calendar file ready: ' + ev.title + '.');
      }
    });
  }

  /* ------------------------------------------------------------ 11. Tap-to-define terms
     Hook: button.term[aria-controls] followed by span.term-def#id. */
  function terms() {
    $$('.term').forEach(function (b) {
      b.setAttribute('aria-expanded', 'false');
      b.addEventListener('click', function () { b.setAttribute('aria-expanded', b.getAttribute('aria-expanded') === 'true' ? 'false' : 'true'); });
    });
    doc.addEventListener('keydown', function (e) {
      if (e.key !== 'Escape') return;
      var open = doc.activeElement && doc.activeElement.classList && doc.activeElement.classList.contains('term') ? doc.activeElement : null;
      if (open) open.setAttribute('aria-expanded', 'false');
    });
  }

  /* ------------------------------------------------------------ 12. Join path (?from=)
     Hook: [data-join-path] containing input[name="from"] radios, li[data-step="0".."3"],
     [data-step-keep], [data-share] (with [data-share-url], button[data-share-copy]) and
     an optional live region [data-join-announce]. Syncs [data-interest-form] radios. */
  function joinPath() {
    var box = $('[data-join-path]'); if (!box) return;
    var radios = $$('input[name="from"]', box), steps = $$('[data-step]', box);
    var keep = $('[data-step-keep]', box), share = $('[data-share]', box), shareUrl = $('[data-share-url]', box), copyBtn = $('[data-share-copy]', box), live = $('[data-join-announce]', box);
    var NAMES = { '0': 'Get licensed', '1': 'Join ARES', '2': 'Get connected', '3': 'Qualify for ACS' };
    var STATES = { unlicensed: { next: '0', done: [] }, licensed: { next: '1', done: ['0'] }, ares: { next: '3', done: ['0', '1', '2'] }, acs: { next: null, done: ['0', '1', '2', '3'] } };
    var FORM_STAGE = { unlicensed: 'unlicensed', licensed: 'licensed', ares: 'ares', acs: 'ares' };
    var current = null;
    function link(key) { var u = window.location.href.split('#')[0].split('?')[0]; return u + '?from=' + key + '#join'; }
    function apply(key, announce) {
      var s = STATES[key]; if (!s) return;
      current = key;
      steps.forEach(function (li) { var k = li.getAttribute('data-step'); li.classList.toggle('is-next', s.next === k); li.classList.toggle('is-done', s.done.indexOf(k) !== -1); });
      if (keep) keep.hidden = key !== 'acs';
      if (share) { share.hidden = false; if (shareUrl) shareUrl.textContent = 'index.html?from=' + key + '#join'; }
      if (live && announce) live.textContent = s.next ? 'Your next step is step ' + s.next + ': ' + NAMES[s.next] + '.' : 'You are already on the team. Members pages are linked below.';
      var f = $('[data-interest-form] input[name="stage"][value="' + FORM_STAGE[key] + '"]'); if (f) f.checked = true;
    }
    radios.forEach(function (r) {
      r.addEventListener('change', function () {
        if (!r.checked) return; apply(r.value, true);
        try { history.replaceState(null, '', link(r.value)); } catch (e) { /* file:// may refuse */ }
      });
    });
    if (copyBtn) copyBtn.addEventListener('click', function () { if (!current) return; var t = link(current); copyText(t, function (ok) { toast(ok ? 'Link copied. It opens this page with your next step highlighted.' : t); }); });
    var p = (param('from') || '').toLowerCase();
    /* index.html#members is the members' door on Home: show the "already on the team" card. */
    var toMembers = !STATES[p] && window.location.hash === '#members' && keep;
    if (toMembers) p = 'acs';
    if (STATES[p]) { radios.forEach(function (r) { r.checked = r.value === p; }); apply(p, false); }
    if (toMembers) keep.scrollIntoView({ block: 'center' });
  }

  /* ------------------------------------------------------------ 13. Interest form (the one form on the site)
     Hook: form[data-interest-form] + a sibling [data-form-done] (tabindex="-1").
     Honeypot field name="website": if filled, the submission is silently dropped.
     Nothing is stored on this host. The no-JS action is a mailto to the role address. */
  function interestForm() {
    $$('[data-interest-form]').forEach(function (f) {
      var done = f.parentNode.querySelector('[data-form-done]');
      f.addEventListener('submit', function (e) {
        e.preventDefault();
        if (f.elements.website && f.elements.website.value) return;
        if (f.checkValidity && !f.checkValidity()) { if (f.reportValidity) f.reportValidity(); return; }
        var name = ((f.elements.name && f.elements.name.value) || '').trim().split(/\s+/)[0];
        if (done) {
          var n = done.querySelector('[data-done-name]'); if (n) n.textContent = name ? ', ' + name : '';
          f.hidden = true; done.hidden = false; done.focus();
        }
      });
    });
  }

  /* ------------------------------------------------------------ 14. Story (How it works)
     Hook: [data-story] with article.step[data-state] (optional data-status, data-lit)
     and svg[data-story-figure]; optional [data-story-status], .msgpath-legend li[data-hop].
     One IntersectionObserver; no scroll listeners, no scroll-linked animation. */
  function story() {
    var st = $('[data-story]'); if (!st || !('IntersectionObserver' in window)) return;
    var fig = $('[data-story-figure]', st), status = $('[data-story-status]', st);
    var steps = $$('article[data-state]', st), hops = $$('.msgpath-legend [data-hop]', st);
    if (!fig || !steps.length) return;
    function show(step) {
      fig.setAttribute('data-state', step.getAttribute('data-state'));
      steps.forEach(function (s) { s.classList.toggle('is-active', s === step); });
      if (status && step.hasAttribute('data-status')) status.textContent = step.getAttribute('data-status');
      var lit = +step.getAttribute('data-lit') || 0;
      hops.forEach(function (h) { h.classList.toggle('is-lit', +h.getAttribute('data-hop') <= lit); });
    }
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) { if (en.isIntersecting) show(en.target); });
    }, { rootMargin: '-45% 0px -45% 0px' });
    steps.forEach(function (s) { io.observe(s); });
    show(steps[0]);
  }

  /* ------------------------------------------------------------ 15. TOC active section + mobile Contents button
     Hook: nav[data-toc] with a[href^="#"]; optional details[data-toc-mobile] and
     button[data-contents-fab] (hidden in HTML; shown here). */
  function toc() {
    var navs = $$('[data-toc]'); if (!navs.length || !('IntersectionObserver' in window)) return;
    var links = []; navs.forEach(function (n) { links = links.concat($$('a[href^="#"]', n)); });
    var ids = {}; links.forEach(function (a) { var id = a.getAttribute('href').slice(1); if (doc.getElementById(id)) ids[id] = true; });
    function activate(id) {
      links.forEach(function (a) { var on = a.getAttribute('href') === '#' + id; a.classList.toggle('is-active', on); if (on) a.setAttribute('aria-current', 'true'); else a.removeAttribute('aria-current'); });
    }
    var io = new IntersectionObserver(function (entries) { entries.forEach(function (en) { if (en.isIntersecting) activate(en.target.id); }); }, { rootMargin: '-15% 0px -75% 0px' });
    Object.keys(ids).forEach(function (id) { io.observe(doc.getElementById(id)); });
    var fab = $('[data-contents-fab]'), det = $('[data-toc-mobile]');
    if (fab && det) {
      fab.hidden = false;
      fab.addEventListener('click', function () { det.open = true; det.scrollIntoView({ block: 'start' }); var s = $('summary', det); if (s) s.focus(); });
    }
  }

  /* ------------------------------------------------------------ 16. Documents library (?q= and ?cat=)
     Hook: [data-doc-library] containing form[data-doc-search] (input name="q"),
     chips button[data-cat-chip=""|"forms"…] with aria-pressed, rows [data-doc="<id>"],
     groups [data-doc-group], [data-doc-count] (aria-live) and [data-doc-empty].
     Matching uses SPOKARES.fn.documents so the site and the data agree. */
  function docLibrary() {
    var lib = $('[data-doc-library]'); if (!lib) return;
    var form = $('[data-doc-search]', lib), input = form ? $('input[name="q"]', form) : null;
    var rows = $$('[data-doc]', lib), groups = $$('[data-doc-group]', lib), chips = $$('[data-cat-chip]', lib);
    var count = $('[data-doc-count]', lib), empty = $('[data-doc-empty]', lib);
    var cats = {}; (S.documentCategories || []).forEach(function (c) { cats[c.id] = c.label; });
    var state = { q: param('q') || '', cat: param('cat') || '' };
    if (!state.cat && window.location.hash === '#forms') state.cat = 'forms';
    if (input) input.value = state.q;
    function apply(updateUrl) {
      var ids = {}; F.documents({ q: state.q, category: state.cat || null }).forEach(function (x) { ids[x.id] = true; });
      var n = 0;
      rows.forEach(function (r) { var on = !!ids[r.getAttribute('data-doc')]; r.hidden = !on; if (on) n++; });
      groups.forEach(function (g) { g.hidden = !$$('[data-doc]', g).some(function (r) { return !r.hidden; }); });
      chips.forEach(function (c) { c.setAttribute('aria-pressed', c.getAttribute('data-cat-chip') === state.cat ? 'true' : 'false'); });
      var where = state.cat ? ' in ' + (cats[state.cat] || state.cat) : '';
      if (count) count.textContent = state.q ? n + (n === 1 ? ' document matches' : ' documents match') + ' “' + state.q + '”' + where + '.' : (state.cat ? n + ' documents' + where + '.' : 'Showing all ' + n + ' documents.');
      if (empty) { empty.hidden = n > 0; var eq = $('[data-doc-empty-q]', empty); if (eq) eq.textContent = state.q; }
      if (updateUrl) {
        try {
          var u = new URL(window.location.href); u.hash = '';
          if (state.q) u.searchParams.set('q', state.q); else u.searchParams.delete('q');
          if (state.cat) u.searchParams.set('cat', state.cat); else u.searchParams.delete('cat');
          history.replaceState(null, '', u.toString());
        } catch (e) { /* ignore */ }
      }
    }
    var timer = null;
    if (input) input.addEventListener('input', function () { clearTimeout(timer); timer = setTimeout(function () { state.q = input.value.trim(); apply(true); }, 160); });
    if (form) form.addEventListener('submit', function (e) { e.preventDefault(); state.q = input.value.trim(); apply(true); });
    chips.forEach(function (c) { c.addEventListener('click', function (e) { e.preventDefault(); state.cat = c.getAttribute('data-cat-chip'); apply(true); }); });
    $$('[data-doc-clear]', lib).forEach(function (b) { b.addEventListener('click', function (e) { e.preventDefault(); state.q = ''; state.cat = ''; if (input) input.value = ''; apply(true); if (input) input.focus(); }); });
    apply(false);
  }

  /* ------------------------------------------------------------ 17. Search preview (members hub)
     Hook: form[data-doc-preview] (action="documents.html", input name="q") + ul.search-preview[data-preview-list]. */
  function searchPreview() {
    $$('[data-doc-preview]').forEach(function (form) {
      var input = $('input[name="q"]', form), out = $('[data-preview-list]', form.parentNode) || $('[data-preview-list]');
      if (!input || !out) return;
      input.addEventListener('input', function () {
        var q = input.value.trim(); if (q.length < 2) { out.hidden = true; out.innerHTML = ''; return; }
        var hits = F.documents({ q: q }), top = hits.slice(0, 5);
        out.innerHTML = top.map(function (x) { return '<li><a href="documents.html#' + esc(x.id) + '"><span>' + esc(x.title) + '</span><span class="fmt">' + esc(x.format) + '</span></a></li>'; }).join('') +
          '<li><a href="documents.html?q=' + encodeURIComponent(q) + '"><span><b>See all ' + hits.length + ' results</b></span></a></li>';
        out.hidden = false;
      });
    });
  }

  /* ------------------------------------------------------------ 18. Generic chip filter (members exercises)
     Hook: [data-filter-scope] with button[data-filter=""|"exercise"…] and items [data-type]. */
  function chipFilter() {
    $$('[data-filter-scope]').forEach(function (scope) {
      var chips = $$('[data-filter]', scope), items = $$('[data-type]', scope);
      chips.forEach(function (c) {
        c.addEventListener('click', function () {
          var v = c.getAttribute('data-filter');
          chips.forEach(function (x) { x.setAttribute('aria-pressed', x === c ? 'true' : 'false'); });
          items.forEach(function (i) { i.hidden = v && (' ' + i.getAttribute('data-type') + ' ').indexOf(' ' + v + ' ') === -1; });
        });
      });
    });
  }

  /* ------------------------------------------------------------ 19. Members sub-nav: keep the current item in view on narrow screens */
  function memberNav() {
    var cur = $('.member-nav a[aria-current="page"]'); if (!cur) return;
    var sc = cur.closest('.member-nav__inner');
    if (sc && sc.scrollWidth > sc.clientWidth + 4) sc.scrollLeft = Math.max(0, cur.offsetLeft - 16);
  }

  /* ------------------------------------------------------------ 20. Reviewer aid */
  function verifyAid() { if (param('verify') === '1') root.classList.add('verify-on'); }

  /* ------------------------------------------------------------ boot */
  function run(fn) { try { fn(); } catch (e) { if (window.console) console.warn('[site.js]', fn.name, e); } }
  function boot() {
    run(primaryNav); run(terms); run(copyButtons); run(verifyAid); run(memberNav); run(chipFilter);
    if (F) { run(nextNetLine); run(netCards); run(nextNetMini); run(upcoming); run(rota); run(winlink); run(misc); run(calendarButtons); run(docLibrary); run(searchPreview); }
    run(joinPath); run(interestForm); run(story); run(toc);
  }
  if (doc.readyState === 'loading') doc.addEventListener('DOMContentLoaded', boot); else boot();
})();
