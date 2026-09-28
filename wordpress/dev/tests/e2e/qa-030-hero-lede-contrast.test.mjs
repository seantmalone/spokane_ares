// DEV ONLY. Regression test for QA-030: on Home, the hero's intro text
// (.hero__lede, #E4E9F2, regular weight, about 21.6px on a phone) sits on the
// Cover photo with only the overlay .wp-block-cover.hero::before between them.
// That overlay is a left-to-right gradient (.88 -> .62 -> .12 night), so the
// right third of the photo is almost bare. At 1024 and 1440 the lede stays in
// the dark left part; on a phone and a tablet the text runs the full width,
// across the light sky. Before the fix the background under the lede, with the
// text hidden, gave (5th-percentile pixel of the worst line) 3.04:1 at 360,
// 2.94:1 at 390 and 4.06:1 at 768, below the 4.5:1 that WCAG 2.2 SC 1.4.3 asks
// of normal-size text (21.6px regular is under the 24px "large text" line).
//
// The test does what a contrast checker would do for text over a photo: open
// Home at each width, wait for the photo and the fonts, hide the lede's text,
// take a viewport screenshot, and sample every other pixel under each line of
// the lede. The lightest 5% of a line's pixels are allowed to dip below the
// bar (photo noise, the odd bright speck); the 5th-percentile pixel must still
// give 4.5:1 against the text colour (3:1 if the lede is ever large text:
// >= 24px, or >= 18.66px and bold). It uses the seeded Home photo, the one the
// site ships with.

import zlib from 'node:zlib';

const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

/** WCAG relative luminance and contrast ratio of two [r, g, b] colours. */
function luminance([r, g, b]) {
  const f = (v) => {
    const c = v / 255;
    return c <= 0.03928 ? c / 12.92 : ((c + 0.055) / 1.055) ** 2.4;
  };
  return 0.2126 * f(r) + 0.7152 * f(g) + 0.0722 * f(b);
}
function contrast(a, b) {
  const x = luminance(a);
  const y = luminance(b);
  return (Math.max(x, y) + 0.05) / (Math.min(x, y) + 0.05);
}

/** A minimal PNG decoder (8-bit RGB or RGBA, not interlaced): what Chrome's screenshots are. */
function decodePng(buf) {
  let p = 8;
  let w = 0;
  let h = 0;
  let colorType = 0;
  const idat = [];
  while (p < buf.length) {
    const len = buf.readUInt32BE(p);
    const type = buf.toString('ascii', p + 4, p + 8);
    const data = buf.subarray(p + 8, p + 8 + len);
    if (type === 'IHDR') {
      w = data.readUInt32BE(0);
      h = data.readUInt32BE(4);
      if (data[8] !== 8 || data[12] !== 0) throw new Error('unexpected PNG format');
      colorType = data[9];
    } else if (type === 'IDAT') idat.push(data);
    else if (type === 'IEND') break;
    p += 12 + len;
  }
  const raw = zlib.inflateSync(Buffer.concat(idat));
  const bpp = colorType === 6 ? 4 : 3;
  const stride = w * bpp;
  const out = Buffer.alloc(h * stride);
  for (let y = 0; y < h; y++) {
    const filter = raw[y * (stride + 1)];
    for (let x = 0; x < stride; x++) {
      const a = raw[y * (stride + 1) + 1 + x];
      const left = x >= bpp ? out[y * stride + x - bpp] : 0;
      const up = y ? out[(y - 1) * stride + x] : 0;
      const ul = y && x >= bpp ? out[(y - 1) * stride + x - bpp] : 0;
      let v;
      if (filter === 0) v = a;
      else if (filter === 1) v = a + left;
      else if (filter === 2) v = a + up;
      else if (filter === 3) v = a + ((left + up) >> 1);
      else {
        const pp = left + up - ul;
        const pa = Math.abs(pp - left);
        const pb = Math.abs(pp - up);
        const pc = Math.abs(pp - ul);
        v = a + (pa <= pb && pa <= pc ? left : pb <= pc ? up : ul);
      }
      out[y * stride + x] = v & 255;
    }
  }
  return { w, h, px: (x, y) => { const i = y * stride + x * bpp; return [out[i], out[i + 1], out[i + 2]]; } };
}

/**
 * In the page: scroll the lede into view, hide its text, and return its text
 * colour, font size and weight, and the client rect of each line.
 */
function hideLede() {
  const el = document.querySelector('.hero__lede');
  if (!el) return { found: false };
  el.scrollIntoView({ block: 'center', behavior: 'instant' });
  // getComputedStyle() is live: read the colour before the text is hidden.
  const cs = getComputedStyle(el);
  const color = cs.color;
  const size = parseFloat(cs.fontSize);
  const weight = parseInt(cs.fontWeight, 10) || 400;
  const rects = [];
  const walker = document.createTreeWalker(el, NodeFilter.SHOW_TEXT);
  for (let n = walker.nextNode(); n; n = walker.nextNode()) {
    if (!n.nodeValue.trim()) continue;
    const range = document.createRange();
    range.selectNodeContents(n);
    for (const q of range.getClientRects()) rects.push({ x: q.left, y: q.top, w: q.width, h: q.height });
  }
  const style = document.createElement('style');
  style.id = 'qa030-hide';
  style.textContent = '.hero__lede, .hero__lede * { color: transparent !important; text-shadow: none !important; text-decoration-color: transparent !important; caret-color: transparent !important; }';
  document.head.appendChild(style);
  return {
    found: true,
    color,
    size,
    weight,
    text: el.textContent.trim().slice(0, 50),
    rects,
    vw: window.innerWidth,
  };
}

/** Measure the lede at one width; returns { lines, bar, worst, info }. */
async function measure(t, width) {
  const view = width < 768 ? { width, height: 844, mobile: true } : { width, height: 900, mobile: false };
  await t.setViewport(view);
  await t.goto('/');
  await t.expectStatus(200);
  await t.page.loadImages();
  const info = await t.evaluate(hideLede);
  t.expect(info.found, `${width} wide: .hero__lede on Home`).toBe(true);
  t.expect(info.rects.length, `${width} wide: lines of the lede`).toBeGreaterThan(0);
  await sleep(200);
  const { data } = await t.page.send('Page.captureScreenshot', { format: 'png' });
  await t.evaluate(() => document.getElementById('qa030-hide')?.remove());
  const img = decodePng(Buffer.from(data, 'base64'));
  t.expect(img.w, `${width} wide: screenshot width (device scale 1)`).toBe(info.vw);

  const fg = info.color.match(/[\d.]+/g).slice(0, 3).map(Number);
  const large = info.size >= 24 || (info.size >= 18.66 && info.weight >= 700);
  const bar = large ? 3 : 4.5;
  const lines = [];
  for (const r of info.rects) {
    const ratios = [];
    for (let y = Math.max(0, Math.floor(r.y)); y < Math.min(img.h, r.y + r.h); y += 2) {
      for (let x = Math.max(0, Math.floor(r.x)); x < Math.min(img.w, r.x + r.w); x += 2) ratios.push(contrast(fg, img.px(x, y)));
    }
    if (!ratios.length) continue;
    ratios.sort((a, b) => a - b);
    lines.push({
      p5: +ratios[Math.floor(ratios.length * 0.05)].toFixed(2),
      median: +ratios[Math.floor(ratios.length * 0.5)].toFixed(2),
      below: Math.round((100 * ratios.filter((v) => v < bar).length) / ratios.length),
    });
  }
  t.expect(lines.length, `${width} wide: sampled lines of the lede`).toBeGreaterThan(0);
  const worst = Math.min(...lines.map((l) => l.p5));
  return { lines, bar, worst, info };
}

function describe(width, m) {
  const per = m.lines.map((l, i) => `line ${i + 1}: ${l.p5}:1 (median ${l.median}:1, ${l.below}% of pixels under ${m.bar}:1)`).join('; ');
  return `${width} wide: the Home hero lede (${m.info.color}, ${m.info.size}px/${m.info.weight}) has ${m.worst}:1 at its worst line's 5th-percentile pixel, needs ${m.bar}:1 — ${per}`;
}

const WIDTHS = [
  [360, 'a small phone'],
  [390, 'a phone'],
  [768, 'a tablet'],
  [1024, 'a small laptop'],
  [1440, 'a desktop'],
];

export const tests = WIDTHS.map(([width, what]) => ({
  name: `visitor: the Home hero lede keeps WCAG AA contrast over the photo at ${width} wide (${what})`,
  timeout: 120000,
  async run(t) {
    const m = await measure(t, width);
    t.log(describe(width, m));
    t.expect(m.worst >= m.bar, describe(width, m)).toBe(true);
    t.expectNoConsoleErrors();
  },
}));
