// DEV ONLY. Regression test for QA-104: at 900px and below the members hub
// drew "Tuesday net" above "This week" with `.hub-net { order: -1 }`, while
// the markup kept This week first. `reading-flow: grid-order` made only
// Chrome follow the drawn order; in Firefox and Safari, and for screen
// readers, the headings read This week, then Tuesday net, and Tab went from
// the document search down to "What to bring", then back up to "Copy radio
// settings" (WCAG 1.3.2, 2.4.3).
//
// Owner's decision (2026-09-27): the Tuesday net comes first in the markup
// at EVERY width, with no CSS reordering, and the desktop hub stays as close
// to its current look as practical.
//
// So at each width: the hub grid's first section is the Tuesday net; the
// sections are drawn in their DOM order (each one below, or to the right of,
// the one before it); nothing reorders them in CSS; the heading outline is
// For members, Tuesday net, This week, Exercises, Most used; and Tab from the
// search goes into the Tuesday net and then through the sections in order.
// These are DOM-order checks, which every browser follows, not pixels.

const WIDTHS = [
  { width: 360, height: 780, mobile: true },
  { width: 390, height: 844, mobile: true },
  { width: 768, height: 1024, mobile: false },
  { width: 900, height: 900, mobile: false },
  { width: 901, height: 900, mobile: false },
  { width: 1024, height: 900, mobile: false },
  { width: 1440, height: 900, mobile: false },
];
const OUTLINE = ['H1 For members', 'H2 Tuesday net', 'H2 This week', 'H3 Exercises', 'H2 Most used'];

/** In the page: the hub's sections and headings, their order, and any CSS that reorders them. */
async function hubOrder() {
  await new Promise((r) => requestAnimationFrame(() => requestAnimationFrame(r)));
  const main = document.querySelector('main');
  const grid = main.querySelector('.hub-grid');
  const name = (el) => (el.classList.contains('hub-net') ? 'net' : el.classList.contains('hub-week') ? 'week' : el.className);
  const kids = [...grid.children].map((el) => ({ el, name: name(el), r: el.getBoundingClientRect() }));
  // Each section must be drawn after the one before it: below it, or beside
  // it to the right on the same row.
  const outOfOrder = [];
  for (let i = 1; i < kids.length; i++) {
    const a = kids[i - 1].r;
    const b = kids[i].r;
    const below = b.top >= a.bottom - 1;
    const right = b.left >= a.right - 1 && Math.abs(b.top - a.top) < 2;
    if (!below && !right) outOfOrder.push(`${kids[i].name} (${Math.round(b.left)},${Math.round(b.top)}) is drawn before ${kids[i - 1].name} (${Math.round(a.left)},${Math.round(a.top)})`);
  }
  const reorder = new Set();
  const gs = getComputedStyle(grid);
  if (gs.readingFlow && gs.readingFlow !== 'normal') reorder.add(`.hub-grid { reading-flow: ${gs.readingFlow} }`);
  if (/dense/.test(gs.gridAutoFlow)) reorder.add(`.hub-grid { grid-auto-flow: ${gs.gridAutoFlow} }`);
  if (/flex/.test(gs.display) && /reverse/.test(gs.flexDirection)) reorder.add(`.hub-grid { flex-direction: ${gs.flexDirection} }`);
  for (const k of kids) {
    const cs = getComputedStyle(k.el);
    if (cs.order !== '0') reorder.add(`.hub-${k.name} { order: ${cs.order} }`);
    if (cs.readingOrder && cs.readingOrder !== '0') reorder.add(`.hub-${k.name} { reading-order: ${cs.readingOrder} }`);
    if (/grid/.test(gs.display) && (cs.gridRowStart !== 'auto' || cs.gridColumnStart !== 'auto')) reorder.add(`.hub-${k.name} { grid-area: ${cs.gridRowStart} / ${cs.gridColumnStart} }`);
  }
  return {
    width: window.innerWidth,
    sections: kids.map((k) => k.name),
    at: kids.map((k) => `${k.name}@${Math.round(k.r.left)},${Math.round(k.r.top + window.scrollY)} ${Math.round(k.r.width)}w`).join(' '),
    outOfOrder,
    reorder: [...reorder],
    outline: [...main.querySelectorAll('h1, h2, h3')].map((h) => `${h.tagName} ${h.textContent.trim().replace(/\s+/g, ' ')}`),
  };
}

/** Focus the last stop of the hub head (the search button), then Tab through the hub; the section of each stop. */
async function tabThroughHub(t) {
  await t.evaluate(() => {
    const stops = [...document.querySelectorAll('.hub-head a[href], .hub-head button, .hub-head input')].filter((el) => el.getClientRects().length);
    stops[stops.length - 1].focus();
    return true;
  });
  const seen = [];
  for (let i = 0; i < 30; i++) {
    await t.press('Tab');
    const s = await t.evaluate(() => {
      const el = document.activeElement;
      const sec = el && el.closest('.hub-net, .hub-week, .hub-most');
      if (!sec) return null;
      return { sec: sec.classList.contains('hub-net') ? 'net' : sec.classList.contains('hub-week') ? 'week' : 'most', label: (el.innerText || el.getAttribute('aria-label') || '').trim().replace(/\s+/g, ' ').slice(0, 30) };
    });
    if (!s) break;
    seen.push(s);
    if (s.sec === 'most') break;
  }
  return seen;
}

export const tests = WIDTHS.map((view) => ({
  name: `visitor at ${view.width}px: the members hub has the Tuesday net first in the DOM and draws its sections in DOM order, with no CSS reordering`,
  async run(t) {
    await t.setViewport(view);
    await t.goto('/members/');
    await t.expectStatus(200);
    const s = await t.evaluate(hubOrder);
    const info = `${s.width}px: ${s.at}`;
    t.expect(s.sections, `the hub sections in DOM order (${info})`).toEqual(['net', 'week']);
    t.expect(s.reorder, `CSS reorders the hub (${info})`).toEqual([]);
    t.expect(s.outOfOrder, `the hub is not drawn in its DOM order (${info})`).toEqual([]);
    t.expect(s.outline, `the heading outline (${info})`).toEqual(OUTLINE);

    const tabbed = await tabThroughHub(t);
    const where = tabbed.map((x) => `${x.sec}:${x.label}`).join(' > ');
    t.expect(tabbed.length, `Tab reached the hub sections (${where})`).toBeGreaterThan(2);
    t.expect(tabbed[0].sec, `Tab from the search goes first into the Tuesday net (${where})`).toBe('net');
    const rank = { net: 0, week: 1, most: 2 };
    const backwards = tabbed.filter((x, i) => i > 0 && rank[x.sec] < rank[tabbed[i - 1].sec]);
    t.expect(backwards.map((x) => x.label), `Tab goes back to an earlier section (${where})`).toEqual([]);
    t.expect(tabbed.some((x) => x.sec === 'week'), `Tab reaches This week after the net (${where})`).toBe(true);
    t.expectNoConsoleErrors();
  },
}));
