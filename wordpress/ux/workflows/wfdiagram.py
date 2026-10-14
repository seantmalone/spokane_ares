"""Swimlane UML activity diagrams as plain SVG, with no dependencies.

A Workflow holds lanes (one per actor), nodes and edges. Rows are worked out
from the forward edges (longest path from the start), so a spec only says
which lane each node sits in. Edges route orthogonally:

  * forward edge, same lane    -> straight down
  * forward edge, other lane   -> down, across, down (the bend sits wherever
                                  the lanes are clear)
  * decision side exit         -> out of the diamond's point, across, down
  * back edge (back=True)      -> out the side, up a gutter, into the side

render(wf, standalone=False) returns an <svg> that styles itself through the
page's CSS classes; standalone=True embeds a light/dark <style> for the
files saved next to the report.
"""

from __future__ import annotations

import html
import math

LANE_W = 214
HEAD_H = 44
ROW_GAP = 34
PAD = 14
FONT = 12
LINE_H = 15
CHAR_W = 6.3

KIND_SIZES = {"start": (22, 22), "end": (26, 26), "final": (24, 24), "merge": (22, 22)}


def wrap(text: str, width_px: float) -> list[str]:
    limit = max(8, int(width_px / CHAR_W))
    out: list[str] = []
    for para in text.split("\n"):
        line = ""
        for word in para.split():
            if not line:
                line = word
            elif len(line) + 1 + len(word) <= limit:
                line += " " + word
            else:
                out.append(line)
                line = word
        out.append(line)
    return out


class Workflow:
    def __init__(self, wid: str, title: str, lanes: list[tuple]):
        """lanes: (id, label, kind[, cols]); kind human | system | external.

        A lane may be several columns wide, so a branch can sit beside the
        main path instead of under it (node option col=1)."""
        self.id = wid
        self.title = title
        self.lanes = [tuple(l) + (1,) * (4 - len(l)) for l in lanes]
        self.lane_ix = {l[0]: i for i, l in enumerate(self.lanes)}
        self.lane_col0 = {}
        c = 0
        for l in self.lanes:
            self.lane_col0[l[0]] = c
            c += l[3]
        self.n_cols = c
        self.nodes: dict[str, dict] = {}
        self.order: list[str] = []
        self.edges: list[dict] = []
        self.badge_href = None  # e.g. "#f-{id}": badges link to their finding

    # -- building ---------------------------------------------------------
    def _add(self, nid, kind, lane, text="", **kw):
        assert nid not in self.nodes, (self.id, nid)
        assert lane in self.lane_ix, (self.id, lane)
        self.nodes[nid] = dict(id=nid, kind=kind, lane=lane, text=text, **kw)
        self.order.append(nid)
        return self

    def start(self, nid, lane, **kw):
        return self._add(nid, "start", lane, **kw)

    def end(self, nid, lane, text="", **kw):
        return self._add(nid, "end", lane, text, **kw)

    def final(self, nid, lane, text="", **kw):
        """Flow final: this branch stops (UML circle with a cross)."""
        return self._add(nid, "final", lane, text, **kw)

    def act(self, nid, lane, text, pain=None, **kw):
        return self._add(nid, "action", lane, text, pain=pain, **kw)

    def dec(self, nid, lane, text, pain=None, **kw):
        return self._add(nid, "decision", lane, text, pain=pain, **kw)

    def merge(self, nid, lane, **kw):
        return self._add(nid, "merge", lane, **kw)

    def e(self, a, b, label=None, exit=None, back=False, side=None, rank=True):
        """rank=False: the edge doesn't push b down a row (lets a branch end
        sit level with its decision)."""
        assert a in self.nodes and b in self.nodes, (self.id, a, b)
        self.edges.append(dict(a=a, b=b, label=label, exit=exit, back=back, side=side, rank=rank))
        return self

    def chain(self, *ids):
        for a, b in zip(ids, ids[1:]):
            self.e(a, b)
        return self

    # -- layout -----------------------------------------------------------
    def _col(self, nid):
        nd = self.nodes[nid]
        return self.lane_col0[nd["lane"]] + nd.get("col", 0)

    def _rows(self):
        rows = {nid: self.nodes[nid].get("row", 0) for nid in self.order}
        fwd = [ed for ed in self.edges if not ed["back"] and ed["rank"]]

        def settle():
            for _ in range(len(self.order) + 2):
                changed = False
                for ed in fwd:
                    if rows[ed["b"]] < rows[ed["a"]] + 1:
                        rows[ed["b"]] = rows[ed["a"]] + 1
                        changed = True
                if not changed:
                    return
            raise ValueError(f"{self.id}: cycle in forward edges")

        settle()
        # Two nodes may not share a column and a row: the later one (in the
        # order the spec added them) moves down, and its successors follow.
        for _ in range(len(self.order) * 6):
            seen, bumped = {}, False
            for nid in self.order:
                key = (self._col(nid), rows[nid])
                if key in seen:
                    rows[nid] += 1
                    bumped = True
                    break
                seen[key] = nid
            if not bumped:
                break
            settle()
        return rows

    def layout(self):
        rows = self._rows()
        n_rows = max(rows.values()) + 1
        geo = {}
        for nid in self.order:
            nd = self.nodes[nid]
            k = nd["kind"]
            if k == "action":
                w = LANE_W - 34
                lines = wrap(nd["text"], w - 18)
                h = max(34, len(lines) * LINE_H + 14)
            elif k == "decision":
                w = LANE_W - 22
                lines = wrap(nd["text"], w - 64)
                h = max(44, len(lines) * LINE_H + 26)
            else:
                w, h = KIND_SIZES[k]
                lines = wrap(nd["text"], LANE_W - 40) if nd["text"] else []
            geo[nid] = dict(w=w, h=h, lines=lines, row=rows[nid], col=self._col(nid))
        row_h = [0] * n_rows
        for nid, g in geo.items():
            extra = len(g["lines"]) * LINE_H + 4 if self.nodes[nid]["kind"] in ("end", "final") and g["lines"] else 0
            row_h[g["row"]] = max(row_h[g["row"]], g["h"] + extra)
        row_y = []
        y = HEAD_H + 22
        for h in row_h:
            row_y.append(y)
            y += (h or 10) + ROW_GAP
        height = y - ROW_GAP + 26
        # Small round nodes centre on the row's main shapes (a decision
        # beside a flow-final, say), ignoring any caption below them.
        core_h = [0] * n_rows
        for nid, g in geo.items():
            if self.nodes[nid]["kind"] in ("action", "decision"):
                core_h[g["row"]] = max(core_h[g["row"]], g["h"])
        for nid, g in geo.items():
            g["cx"] = PAD + g["col"] * LANE_W + LANE_W / 2
            small = self.nodes[nid]["kind"] in ("start", "merge", "end", "final")
            g["top"] = row_y[g["row"]] + max(0, (core_h[g["row"]] - g["h"]) / 2) if small else row_y[g["row"]]
            g["bottom"] = g["top"] + g["h"]
            g["cy"] = g["top"] + g["h"] / 2
        self.geo, self.row_y, self.row_h = geo, row_y, row_h
        self.width = PAD * 2 + LANE_W * self.n_cols
        self.height = height

    # -- routing ----------------------------------------------------------
    def _busy(self, col, row_a, row_b):
        """True when a node sits in column col strictly between two rows."""
        return any(g["col"] == col and row_a < g["row"] < row_b for g in self.geo.values())

    def _occupied(self, col, row):
        return any(g["col"] == col and g["row"] == row for g in self.geo.values())

    def _route(self, ed):
        A, B = self.geo[ed["a"]], self.geo[ed["b"]]
        ka = self.nodes[ed["a"]]["kind"]
        half = ROW_GAP / 2
        left = lambda g: PAD + g["col"] * LANE_W
        if ed["back"]:
            side = ed["side"] or "right"
            sgn = 1 if side == "right" else -1
            ax = A["cx"] + sgn * A["w"] / 2
            bx = B["cx"] + sgn * B["w"] / 2
            gx = (max(left(A), left(B)) + LANE_W - 6) if side == "right" else (min(left(A), left(B)) + 6)
            pts = [(ax, A["cy"]), (gx, A["cy"]), (gx, B["cy"]), (bx, B["cy"])]
            return pts, (gx, (A["cy"] + B["cy"]) / 2, "rot")
        exit_ = ed["exit"]
        if exit_ is None and ka == "decision" and B["col"] != A["col"]:
            exit_ = "right" if B["col"] > A["col"] else "left"
        if exit_ in ("left", "right"):
            sgn = 1 if exit_ == "right" else -1
            sx, sy = A["cx"] + sgn * A["w"] / 2, A["cy"]
            lab = (sx + sgn * 4, sy, "hr" if sgn > 0 else "hl")
            if B["col"] == A["col"]:
                gx = A["cx"] + sgn * (LANE_W / 2 - 7)
                return [(sx, sy), (gx, sy), (gx, B["cy"]), (B["cx"] + sgn * B["w"] / 2, B["cy"])], lab
            if B["row"] == A["row"]:
                return [(sx, sy), (B["cx"] - sgn * B["w"] / 2, B["cy"])], lab
            lo, hi = sorted((A["col"], B["col"]))
            between_clear = not any(self._occupied(c, A["row"]) for c in range(lo + 1, hi))
            if between_clear and not self._occupied(B["col"], A["row"]) and not self._busy(B["col"], A["row"], B["row"]):
                return [(sx, sy), (B["cx"], sy), (B["cx"], B["top"])], lab
            jx = A["cx"] + sgn * LANE_W / 2
            yb = self.row_y[B["row"]] - half
            return [(sx, sy), (jx, sy), (jx, yb), (B["cx"], yb), (B["cx"], B["top"])], lab
        sx, sy = A["cx"], A["bottom"]
        lab = (sx, sy, "v")
        if B["col"] == A["col"] and B["row"] > A["row"]:
            if not self._busy(A["col"], A["row"], B["row"]):
                return [(sx, sy), (B["cx"], B["top"])], lab
            gx = left(A) + LANE_W - 7
            y1, y2 = sy + half - 4, B["top"] - half + 4
            return [(sx, sy), (sx, y1), (gx, y1), (gx, y2), (B["cx"], y2), (B["cx"], B["top"])], lab
        if B["row"] == A["row"]:
            sgn = 1 if B["col"] > A["col"] else -1
            pts = [(A["cx"] + sgn * A["w"] / 2, A["cy"]), (B["cx"] - sgn * B["w"] / 2, B["cy"])]
            return pts, (pts[0][0] + sgn * 4, A["cy"], "hr" if sgn > 0 else "hl")
        src_free = not self._busy(A["col"], A["row"], B["row"])
        dst_free = not self._busy(B["col"], A["row"], B["row"])
        late = src_free or not dst_free
        ybend = (self.row_y[B["row"]] - half) if late else (sy + half)
        return [(sx, sy), (sx, ybend), (B["cx"], ybend), (B["cx"], B["top"])], lab

    # -- drawing ----------------------------------------------------------
    def svg(self, standalone=False):
        self.layout()
        W, H = self.width, self.height
        mid = f"arr-{self.id}"
        o = []
        aria = html.escape(f"UML activity diagram: {self.title}. Swimlanes: " + ", ".join(l[1] for l in self.lanes) + ".")
        style = '' if standalone else f' style="max-width:{W:.0f}px"'
        o.append(f'<svg class="wf"{style} xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {W:.0f} {H:.0f}" width="{W:.0f}" height="{H:.0f}" role="img" aria-label="{aria}">')
        if standalone:
            o.append(f"<style>{STANDALONE_CSS}</style>")
            o.append(f'<rect class="wf-bg" x="0" y="0" width="{W}" height="{H}"/>')
        o.append(f'<defs><marker id="{mid}" viewBox="0 0 10 10" refX="9.5" refY="5" markerWidth="7" markerHeight="7" orient="auto-start-reverse"><path class="wf-arrowhead" d="M0,0 L10,5 L0,10 z"/></marker></defs>')
        for lid, label, lk, cols in self.lanes:
            x = PAD + self.lane_col0[lid] * LANE_W
            lw = LANE_W * cols
            o.append(f'<rect class="wf-lane wf-lane-{lk}" x="{x}" y="{PAD/2}" width="{lw}" height="{H - PAD}"/>')
            o.append(f'<rect class="wf-lanehead wf-lanehead-{lk}" x="{x}" y="{PAD/2}" width="{lw}" height="{HEAD_H - PAD/2}"/>')
            hl = wrap(label, lw - 16)
            base = PAD / 2 + (HEAD_H - PAD / 2) / 2 - (len(hl) - 1) * 7 + 4.5
            for j, ln in enumerate(hl):
                o.append(f'<text class="wf-lanelabel" x="{x + lw/2}" y="{base + j*14:.1f}" text-anchor="middle">{html.escape(ln)}</text>')
        labels = []
        for ed in self.edges:
            pts, lab = self._route(ed)
            d = "M" + " L".join(f"{x:.1f},{y:.1f}" for x, y in pts)
            o.append(f'<path class="wf-edge{" wf-edge-back" if ed["back"] else ""}" d="{d}" marker-end="url(#{mid})"/>')
            if ed["label"]:
                labels.append((ed["label"], lab))
        for nid in self.order:
            o.append(self._node(nid))
        for text, (x, y, mode) in labels:
            t = html.escape(f"[{text}]")
            tw = len(t) * 6.0 + 4
            if mode == "rot":
                o.append(f'<g transform="rotate(-90 {x:.1f} {y:.1f})"><rect class="wf-guardbg" x="{x - tw/2:.1f}" y="{y-9:.1f}" width="{tw:.1f}" height="13" rx="2"/>'
                         f'<text class="wf-guard" x="{x:.1f}" y="{y+1:.1f}" text-anchor="middle">{t}</text></g>')
                continue
            if mode == "v":
                lx, ly, anchor, rx = x + 5, y + 13, "start", x + 3
            elif mode == "hr":
                lx, ly, anchor, rx = x + 2, y - 5, "start", x
            else:
                lx, ly, anchor, rx = x - 2, y - 5, "end", x - tw
                if rx < 2:  # no room left of a lane-edge decision: sit below the line
                    lx, ly, anchor, rx = 4, y + 14, "start", 2
            o.append(f'<rect class="wf-guardbg" x="{rx:.1f}" y="{ly-10:.1f}" width="{tw:.1f}" height="13" rx="2"/>')
            o.append(f'<text class="wf-guard" x="{lx:.1f}" y="{ly:.1f}" text-anchor="{anchor}">{t}</text>')
        o.append("</svg>")
        return "\n".join(o)

    def _node(self, nid):
        nd, g = self.nodes[nid], self.geo[nid]
        k, cx, cy, w, h = nd["kind"], g["cx"], g["cy"], g["w"], g["h"]
        lk = self.lanes[self.lane_ix[nd["lane"]]][2]
        o = []
        if k == "start":
            o.append(f'<circle class="wf-solid" cx="{cx}" cy="{cy}" r="{w/2}"/>')
        elif k == "end":
            o.append(f'<circle class="wf-ring" cx="{cx}" cy="{cy}" r="{w/2}"/><circle class="wf-solid" cx="{cx}" cy="{cy}" r="{w/2-5}"/>')
        elif k == "final":
            r = w / 2
            dd = r * 0.7
            o.append(f'<circle class="wf-ring wf-ringfill" cx="{cx}" cy="{cy}" r="{r}"/><path class="wf-x" d="M{cx-dd},{cy-dd} L{cx+dd},{cy+dd} M{cx-dd},{cy+dd} L{cx+dd},{cy-dd}"/>')
        elif k == "merge":
            o.append(f'<path class="wf-dec" d="M{cx},{cy-h/2} L{cx+w/2},{cy} L{cx},{cy+h/2} L{cx-w/2},{cy} z"/>')
        elif k == "action":
            o.append(f'<rect class="wf-act wf-act-{lk}" x="{cx-w/2}" y="{g["top"]}" width="{w}" height="{h}" rx="9"/>')
        elif k == "decision":
            o.append(f'<path class="wf-dec" d="M{cx},{g["top"]} L{cx+w/2},{cy} L{cx},{g["bottom"]} L{cx-w/2},{cy} z"/>')
        lines = g["lines"]
        if k in ("action", "decision"):
            y0 = cy - (len(lines) - 1) * LINE_H / 2 + 4
            for j, ln in enumerate(lines):
                o.append(f'<text class="wf-text" x="{cx}" y="{y0 + j*LINE_H:.1f}" text-anchor="middle">{html.escape(ln)}</text>')
        elif lines:
            for j, ln in enumerate(lines):
                o.append(f'<text class="wf-cap" x="{cx}" y="{g["bottom"] + 14 + j*LINE_H:.1f}" text-anchor="middle">{html.escape(ln)}</text>')
        if nd.get("pain"):
            bx, by = (cx + w / 2 - 4, g["top"] + 2) if k == "action" else (cx + 22, g["top"] + 4)
            pid = html.escape(str(nd["pain"]))
            badge = f'<circle class="wf-pain" cx="{bx}" cy="{by}" r="10.5"/><text class="wf-paintext" x="{bx}" y="{by+3.8}" text-anchor="middle">{pid}</text>'
            if self.badge_href:
                badge = f'<a href="{self.badge_href.format(id=pid)}"><title>Finding {pid}</title>{badge}</a>'
            o.append(badge)
        return f'<g class="wf-node wf-{k}" data-node="{html.escape(nid)}">' + "".join(o) + "</g>"


# Colors for the standalone files. The report page styles the same classes
# from its own tokens.
_LIGHT = dict(bg="#fbfaf7", fg="#1e2328", muted="#5d6670", line="#c9ccd1", human="#ffffff", humanhead="#e7edf6",
              system="#f3f1ec", systemhead="#e9e4d8", external="#f7f2f6", externalhead="#ece0ea",
              acthuman="#ffffff", actsystem="#fffdf8", actexternal="#fffafe", pain="#c2410c", paintext="#ffffff")
_DARK = dict(bg="#15181c", fg="#e6e8eb", muted="#9aa3ad", line="#3a4048", human="#1b1f24", humanhead="#243246",
             system="#1d1c19", systemhead="#33302a", external="#1f1a1f", externalhead="#3a2c38",
             acthuman="#20252b", actsystem="#25231f", actexternal="#271f27", pain="#fb923c", paintext="#1a1208")


def css_rules(v: dict, scope: str = "") -> str:
    s = scope
    return (
        f"{s}.wf-bg{{fill:{v['bg']}}}"
        f"{s}.wf-lane{{stroke:{v['line']};stroke-width:1}}"
        f"{s}.wf-lane-human{{fill:{v['human']}}}{s}.wf-lane-system{{fill:{v['system']}}}{s}.wf-lane-external{{fill:{v['external']}}}"
        f"{s}.wf-lanehead{{stroke:{v['line']}}}{s}.wf-lanehead-human{{fill:{v['humanhead']}}}{s}.wf-lanehead-system{{fill:{v['systemhead']}}}{s}.wf-lanehead-external{{fill:{v['externalhead']}}}"
        f"{s}.wf-lanelabel{{fill:{v['fg']};font:600 12.5px system-ui,-apple-system,sans-serif;letter-spacing:.02em}}"
        f"{s}.wf-act{{stroke:{v['fg']};stroke-width:1.2}}{s}.wf-act-human{{fill:{v['acthuman']}}}{s}.wf-act-system{{fill:{v['actsystem']}}}{s}.wf-act-external{{fill:{v['actexternal']}}}"
        f"{s}.wf-dec{{fill:{v['acthuman']};stroke:{v['fg']};stroke-width:1.2}}"
        f"{s}.wf-solid{{fill:{v['fg']}}}{s}.wf-ring{{fill:none;stroke:{v['fg']};stroke-width:1.4}}{s}.wf-ringfill{{fill:{v['bg']}}}{s}.wf-x{{stroke:{v['fg']};stroke-width:1.4}}"
        f"{s}.wf-text{{fill:{v['fg']};font:12px system-ui,-apple-system,sans-serif}}"
        f"{s}.wf-cap{{fill:{v['muted']};font:italic 11.5px system-ui,-apple-system,sans-serif}}"
        f"{s}.wf-edge{{fill:none;stroke:{v['fg']};stroke-width:1.2}}{s}.wf-edge-back{{stroke-dasharray:5 3}}"
        f"{s}.wf-arrowhead{{fill:{v['fg']}}}"
        f"{s}.wf-guardbg{{fill:{v['bg']};opacity:.92}}"
        f"{s}.wf-guard{{fill:{v['muted']};font:11px ui-monospace,SFMono-Regular,Menlo,monospace}}"
        f"{s}.wf-pain{{fill:{v['pain']}}}{s}.wf-paintext{{fill:{v['paintext']};font:700 10.5px system-ui,-apple-system,sans-serif}}"
    )


STANDALONE_CSS = css_rules(_LIGHT) + "@media (prefers-color-scheme: dark){" + css_rules(_DARK) + "}"
