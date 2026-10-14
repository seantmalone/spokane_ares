#!/usr/bin/env python3
"""Build the editor workflow map.

    python3 wordpress/ux/workflows/build.py [--artifact PATH]

Writes, next to this file:
  svg/<workflow>.svg   standalone diagrams (light and dark)
  index.html           the report, self-contained

--artifact PATH also writes the report body without the document skeleton,
for publishing as a claude.ai Artifact.

Stdlib only. Diagrams come from specs.py; the evaluation from content.py.
"""

from __future__ import annotations

import argparse
import html
import os
import sys

sys.dont_write_bytecode = True
HERE = os.path.dirname(os.path.abspath(__file__))
sys.path.insert(0, HERE)

import content  # noqa: E402
import specs  # noqa: E402
import wfdiagram  # noqa: E402

ORDER = {
    "net": ["rota", "undo", "netsettings", "meetchange", "meetrules"],
    "events": ["eventedit", "eventlife"],
    "docs": ["docadd", "docreplace", "docreview", "doctake", "tiles"],
    "pages": ["pagetext"],
    "access": ["accounts"],
    "ops": ["ssh", "release", "weekly", "backups"],
}
VERDICT_CLASS = {"Sound": "v-ok", "Needs work": "v-warn", "Risky": "v-bad"}
SEV_CLASS = {"High": "s-high", "Medium": "s-med", "Low": "s-low", "Good": "s-good"}
ST_CLASS = {"Fixed in 0.3.0": "st-fixed", "Partly": "st-part", "Owner": "st-owner", "Open": "st-open", "Not changed": "st-open", "By design": "st-fixed", "Good": "st-fixed"}
PRI_ORDER = {"Now": 0, "Next": 1, "Later": 2}

e = html.escape


def diagram_tokens(v: dict) -> str:
    return "".join(f"--d-{k}:{val};" for k, val in v.items())


def page_css() -> str:
    var_map = {k: f"var(--d-{k})" for k in wfdiagram._LIGHT}
    light = dict(wfdiagram._LIGHT, bg="#ffffff")
    dark = dict(wfdiagram._DARK, bg="#171b20")
    return f"""
/* Layout: one reading column for prose; diagrams and tables break out wider and scroll inside their own frame. */
:root{{
  --bg:#f4f3ef; --ink:#1c2127; --muted:#59616b; --line:#d8d5cd; --card:#ffffff; --sunk:#ecebe5;
  --link:#1f4fa3; --signal:#c2410c; --ok:#1d7a4a; --warn:#9a5b00; --bad:#b42318;
  --ok-bg:#e3f2ea; --warn-bg:#fbefd9; --bad-bg:#fbe4e1;
  --f-display:"Barlow Semi Condensed","Arial Narrow",system-ui,sans-serif;
  --f-body:"Atkinson Hyperlegible",system-ui,-apple-system,"Segoe UI",sans-serif;
  --f-mono:"JetBrains Mono",ui-monospace,SFMono-Regular,Menlo,monospace;
  {diagram_tokens(light)}
}}
@media (prefers-color-scheme: dark){{:root:not([data-theme="light"]){{
  --bg:#111418; --ink:#e6e8eb; --muted:#9aa3ad; --line:#2c323a; --card:#171b20; --sunk:#1d2228;
  --link:#8db4ff; --signal:#fb923c; --ok:#5fd39a; --warn:#f2b14e; --bad:#ff8f80;
  --ok-bg:#15301f; --warn-bg:#33270f; --bad-bg:#3a1714; color-scheme:dark;
  {diagram_tokens(dark)}
}}}}
:root[data-theme="dark"]{{
  --bg:#111418; --ink:#e6e8eb; --muted:#9aa3ad; --line:#2c323a; --card:#171b20; --sunk:#1d2228;
  --link:#8db4ff; --signal:#fb923c; --ok:#5fd39a; --warn:#f2b14e; --bad:#ff8f80;
  --ok-bg:#15301f; --warn-bg:#33270f; --bad-bg:#3a1714; color-scheme:dark;
  {diagram_tokens(dark)}
}}
*{{box-sizing:border-box}}
body{{margin:0;background:var(--bg);color:var(--ink);font:16px/1.6 var(--f-body)}}
.page{{max-width:1180px;margin:0 auto;padding-block:28px 72px;padding-left:max(16px,env(safe-area-inset-left,0px));padding-right:max(16px,env(safe-area-inset-right,0px))}}
.prose{{max-width:72ch}}
a{{color:var(--link)}}
a:focus-visible,button:focus-visible{{outline:2px solid var(--signal);outline-offset:2px}}
h1,h2,h3{{font-family:var(--f-display);line-height:1.15;text-wrap:balance;margin:0}}
h1{{font-size:clamp(30px,5vw,44px);font-weight:700;letter-spacing:-.01em}}
h2{{font-size:28px;font-weight:700;margin-top:56px;padding-top:14px;border-top:2px solid var(--ink)}}
h3{{font-size:22px;font-weight:600}}
.eyebrow{{font:600 12px/1.4 var(--f-mono);letter-spacing:.08em;text-transform:uppercase;color:var(--muted)}}
.lede{{font-size:18px;margin:14px 0 0}}
code,.mono{{font-family:var(--f-mono);font-size:.86em}}
code{{background:var(--sunk);padding:1px 5px;border-radius:4px}}
.toc{{display:flex;flex-wrap:wrap;gap:6px 18px;margin:22px 0 0;padding:12px 0;border-block:1px solid var(--line);font-size:15px}}
.toc a{{text-decoration:none}} .toc a:hover{{text-decoration:underline}}
.tally{{display:flex;flex-wrap:wrap;gap:10px;margin:22px 0 6px}}
.tally span{{display:inline-flex;align-items:baseline;gap:8px;padding:6px 12px;border-radius:999px;font-weight:700;font-variant-numeric:tabular-nums}}
.tally b{{font:700 22px/1 var(--f-display)}}
.v-ok{{background:var(--ok-bg);color:var(--ok)}} .v-warn{{background:var(--warn-bg);color:var(--warn)}} .v-bad{{background:var(--bad-bg);color:var(--bad)}}
.pill{{display:inline-block;padding:2px 10px;border-radius:999px;font:700 12px/1.6 var(--f-body);letter-spacing:.02em;white-space:nowrap}}
.now-list{{margin:10px 0 0;padding:0;list-style:none;display:grid;gap:8px}}
.now-list li{{display:grid;grid-template-columns:auto 1fr;gap:12px;align-items:baseline;padding:10px 14px;background:var(--card);border:1px solid var(--line);border-radius:8px}}
.now-list li > *{{min-width:0}}
.legend{{display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:8px 22px;margin:14px 0 0;font-size:14.5px}}
.legend div{{display:flex;gap:10px;align-items:center}}
.legend svg{{flex:none}}
.wfsec{{margin-top:44px}}
.wfhead{{display:flex;flex-wrap:wrap;align-items:center;gap:8px 14px}}
.wfmeta{{color:var(--muted);font-size:14.5px;margin:4px 0 0}}
.assess{{margin:12px 0 0}}
figure.dia{{margin:16px 0 0;background:var(--d-bg);border:1px solid var(--line);border-radius:10px;overflow:hidden}}
figure.dia .scroll{{overflow-x:auto;-webkit-overflow-scrolling:touch}}
figure.dia figcaption{{padding:8px 14px;border-top:1px solid var(--line);font-size:13.5px;color:var(--muted)}}
svg.wf{{display:block;width:100%;height:auto;margin:0 auto}}
@media (max-width:760px){{svg.wf{{width:auto;max-width:none!important}}}}
{wfdiagram.css_rules(var_map, "svg.wf ")}
svg.wf a .wf-pain{{cursor:pointer}} svg.wf a:hover .wf-pain{{stroke:var(--ink);stroke-width:2}}
.cols{{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1.25fr);gap:18px 28px;margin-top:18px}}
@media (max-width:860px){{.cols{{grid-template-columns:1fr}}}}
.cols h4{{margin:0 0 8px;font:700 12px/1.4 var(--f-mono);letter-spacing:.08em;text-transform:uppercase;color:var(--muted)}}
.flist,.rlist{{list-style:none;margin:0;padding:0;display:grid;gap:12px}}
.flist li{{scroll-margin-top:16px}}
.flist li:target{{outline:2px solid var(--signal);outline-offset:4px;border-radius:4px}}
.fid{{display:inline-grid;place-items:center;min-width:30px;height:22px;padding:0 6px;border-radius:11px;background:var(--signal);color:var(--card);font:700 11.5px/1 var(--f-body);margin-right:6px;vertical-align:1px}}
.ftitle{{font-weight:700}}
.fdetail{{margin:4px 0 0;font-size:15px}}
.ev{{margin:4px 0 0;font:12.5px/1.5 var(--f-mono);color:var(--muted);overflow-wrap:anywhere}}
.s-high{{background:var(--bad-bg);color:var(--bad)}} .s-med{{background:var(--warn-bg);color:var(--warn)}} .s-low{{background:var(--sunk);color:var(--muted)}} .s-good{{background:var(--ok-bg);color:var(--ok)}}
.st{{display:block;margin:6px 0 0;font-size:14px}}
.st .pill{{margin-right:6px}}
.st-fixed{{background:var(--ok-bg);color:var(--ok)}} .st-part{{background:var(--warn-bg);color:var(--warn)}} .st-owner{{background:var(--sunk);color:var(--ink);box-shadow:inset 0 0 0 1px var(--line)}} .st-open{{background:var(--bad-bg);color:var(--bad)}}
.rlist li{{padding:12px 14px;background:var(--card);border:1px solid var(--line);border-radius:8px}}
.rtop{{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:6px}}
.p-now{{background:var(--signal);color:var(--card)}} .p-next{{background:var(--sunk);color:var(--ink);box-shadow:inset 0 0 0 1px var(--line)}} .p-later{{background:transparent;color:var(--muted);box-shadow:inset 0 0 0 1px var(--line)}}
.eff{{font:600 12px/1.6 var(--f-mono);color:var(--muted)}}
.change{{margin:8px 0 0;padding-top:8px;border-top:1px dashed var(--line);font-size:14.5px}}
.change b{{font:700 11.5px/1.4 var(--f-mono);letter-spacing:.06em;text-transform:uppercase;color:var(--muted);margin-right:6px}}
.themes{{display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:14px;margin-top:16px}}
.themes article{{background:var(--card);border:1px solid var(--line);border-radius:10px;padding:14px 16px}}
.themes h3{{font-size:19px;margin-bottom:6px}}
.themes p{{margin:0;font-size:15.5px}}
.tablewrap{{overflow-x:auto;margin-top:14px;border:1px solid var(--line);border-radius:10px;background:var(--card)}}
table{{border-collapse:collapse;width:100%;font-size:14.5px}}
th,td{{text-align:left;vertical-align:top;padding:9px 12px;border-bottom:1px solid var(--line)}}
th{{font:700 12px/1.4 var(--f-mono);letter-spacing:.06em;text-transform:uppercase;color:var(--muted);background:var(--sunk);white-space:nowrap}}
tr:last-child td{{border-bottom:0}}
td.nw{{white-space:nowrap}}
.keep{{margin:12px 0 0;padding-left:20px}} .keep li{{margin:4px 0}}
footer{{margin-top:56px;padding-top:14px;border-top:1px solid var(--line);font-size:14px;color:var(--muted)}}
@media (prefers-reduced-motion:no-preference){{html{{scroll-behavior:smooth}}}}
"""


def legend_svg(kind: str) -> str:
    c = 'fill="none" stroke="currentColor" stroke-width="1.3"'
    shapes = {
        "start": '<circle cx="14" cy="12" r="8" fill="currentColor"/>',
        "end": f'<circle cx="14" cy="12" r="9" {c}/><circle cx="14" cy="12" r="5" fill="currentColor"/>',
        "final": f'<circle cx="14" cy="12" r="9" {c}/><path d="M8,6 L20,18 M8,18 L20,6" stroke="currentColor" stroke-width="1.3"/>',
        "action": f'<rect x="2" y="3" width="24" height="18" rx="5" {c}/>',
        "decision": f'<path d="M14,2 L26,12 L14,22 L2,12 z" {c}/>',
        "back": '<path d="M2,12 L22,12" stroke="currentColor" stroke-width="1.3" stroke-dasharray="4 3"/><path d="M20,8 L26,12 L20,16 z" fill="currentColor"/>',
        "badge": '<circle cx="14" cy="12" r="10" fill="var(--signal)"/><text x="14" y="16" text-anchor="middle" font-size="10" font-weight="700" fill="var(--card)">N3</text>',
        "lane": '<rect x="2" y="2" width="11" height="20" fill="var(--d-humanhead)" stroke="var(--line)"/><rect x="13" y="2" width="11" height="20" fill="var(--d-systemhead)" stroke="var(--line)"/>',
    }
    return f'<svg width="28" height="24" viewBox="0 0 28 24" aria-hidden="true">{shapes[kind]}</svg>'


def status_html(fid: str) -> str:
    st = getattr(content, "STATUS", {}).get(fid)
    if not st:
        return ""
    return f'<span class="st"><span class="pill {ST_CLASS[st[0]]}">{e(st[0])}</span>{e(st[1])}</span>'


def fid_link(fid: str) -> str:
    return f'<a href="#f-{fid}" class="mono">{fid}</a>'


def render_body(link_badges: bool = True) -> tuple[str, dict]:
    wfs = {w.id: w for w in specs.WORKFLOWS}
    notes = content.WORKFLOW_NOTES
    assert set(wfs) == set(notes), set(wfs) ^ set(notes)
    ordered = [wid for area, _ in content.AREAS for wid in ORDER[area]]
    assert sorted(ordered) == sorted(wfs), "ORDER misses a workflow"
    # every badge must be a known finding (a badge for a finding owned by
    # another workflow links there)
    for wid, w in wfs.items():
        for nd in w.nodes.values():
            if nd.get("pain"):
                assert nd["pain"] in content.FINDINGS, (wid, nd["pain"])
    owner = {}
    for wid in ordered:
        for f in notes[wid]["findings"]:
            owner.setdefault(f, wid)
    missing = set(content.FINDINGS) - set(owner)

    svgs = {}
    o = []
    o.append('<title>Editor Workflow Map</title>\n<meta name="claude:viewport-fit-sides" content="cover">')
    o.append('<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>'
             '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Atkinson+Hyperlegible:ital,wght@0,400;0,700;1,400&family=Barlow+Semi+Condensed:wght@600;700&family=JetBrains+Mono:wght@400;600;700&display=swap">')
    o.append(f"<style>{page_css()}</style>")
    o.append('<div class="page">')
    counts = {"Sound": 0, "Needs work": 0, "Risky": 0}
    for wid in ordered:
        counts[notes[wid]["verdict"]] += 1
    n_find = sum(1 for f in content.FINDINGS.values() if f[0] != "Good")
    o.append('<header class="prose">'
             '<p class="eyebrow">spokares.org · WordPress build v0.2.0 · current state, read from the code on 2026-10-13</p>'
             '<h1>Editor Workflow Map</h1>'
             f'<p class="lede">Every custom job an editor or administrator does on the new site, drawn as a UML activity diagram with one swimlane per actor, then assessed. {len(ordered)} workflows, {n_find} findings, and recommendations that each say what would change in the app.</p>'
             '</header>')
    stat = getattr(content, "STATUS", {})
    if stat:
        nfix = sum(1 for v in stat.values() if v[0] == "Fixed in 0.3.0")
        nown = sum(1 for v in stat.values() if v[0] == "Owner")
        npart = sum(1 for v in stat.values() if v[0] == "Partly")
        o.append(f'<p class="prose" style="margin-top:14px;padding:10px 14px;border:1px solid var(--line);border-radius:8px;background:var(--card)"><strong>Status, 2026-10-13:</strong> v0.3.0 fixes {nfix} findings; {npart} are partly done and {nown} wait on the owner (mail, the weekly cron, a second key holder). Each finding shows its status. The diagrams and verdicts below still describe v0.2.0, the state the review read.</p>')
    o.append('<div class="tally" aria-label="Verdicts">'
             f'<span class="v-ok"><b>{counts["Sound"]}</b> sound</span>'
             f'<span class="v-warn"><b>{counts["Needs work"]}</b> need work</span>'
             f'<span class="v-bad"><b>{counts["Risky"]}</b> risky</span></div>')
    toc = "".join(f'<a href="#area-{a}">{e(t)}</a>' for a, t in content.AREAS)
    o.append(f'<nav class="toc" aria-label="Contents"><a href="#summary">Summary</a>{toc}<a href="#themes">Themes</a><a href="#roadmap">Roadmap</a><a href="#index">All findings</a></nav>')

    # Summary
    o.append('<section id="summary"><h2>Summary</h2><div class="prose">')
    o.append("<p>The screens are, on the whole, well designed for the people they were built for. They give one form per fact and plain words, the server refuses what it must, and every save says where the change shows. Net Settings, Meeting Schedule and the page-text lock are the strongest. The problems sit around the screens rather than in them.</p>")
    o.append("<p><strong>The headline:</strong> live content doesn't go through those screens. Net control, meetings and events are entered by the webmaster over SSH (" + fid_link("O1") + "). That skips every check and leaves the schedule's Undo able to wipe the edits (" + fid_link("N3") + "). The single biggest improvement is a process change: AG7QP and the EC enter what they already write, on accounts made for them. After that come the gaps at the ends of each lifecycle. Postponed events nobody revisits, documents whose Undo can't bring the file back, a restore nobody has rehearsed.</p>")
    o.append("</div>")
    now = [(wid, r) for wid in ordered for r in notes[wid]["recs"] if r[0] == "Now"]
    now.sort(key=lambda t: t[0] != "ssh")  # the headline first, then area order
    o.append('<h3 style="margin-top:22px">Do now, before the DNS cutover</h3><ul class="now-list">')
    for wid, (_, eff, rec, _chg) in now:
        o.append(f'<li><span class="pill p-now">Now</span><span>{e(rec)} <a href="#wf-{wid}">{e(wfs[wid].title)}</a> <span class="eff">· effort {eff}</span></span></li>')
    o.append("</ul></section>")

    # Legend
    o.append('<section id="legend"><h2>Reading the diagrams</h2><div class="legend">')
    for k, t in [("lane", "Swimlane: one column per actor (people blue, systems sand, outside parties mauve)"),
                 ("start", "Start"), ("action", "Action"), ("decision", "Decision; [guards] on the branches"),
                 ("end", "End of the activity"), ("final", "This branch stops here (flow final)"),
                 ("back", "Loop back (dashed)"), ("badge", "Finding: click to jump to it")]:
        o.append(f"<div>{legend_svg(k)}<span>{e(t)}</span></div>")
    o.append("</div><p class=\"prose\" style=\"color:var(--muted);font-size:14.5px;margin-top:12px\">Diagrams show what the code does today, not what the guide says. Default WordPress screens (profile, sign-in, Users list) appear only where the site changes how they behave.</p></section>")

    # Areas
    for area, area_title in content.AREAS:
        o.append(f'<section id="area-{area}"><h2>{e(area_title)}</h2>')
        for wid in ORDER[area]:
            w, n = wfs[wid], notes[wid]
            if link_badges:
                w.badge_href = "#f-{id}"
            svg = w.svg(standalone=False)
            w.badge_href = None
            svgs[wid] = w.svg(standalone=True)
            vc = VERDICT_CLASS[n["verdict"]]
            o.append(f'<article class="wfsec" id="wf-{wid}"><div class="wfhead"><h3>{e(w.title)}</h3><span class="pill {vc}">{e(n["verdict"])}</span></div>')
            o.append(f'<p class="wfmeta">{e(n["meta"])}</p><p class="assess prose">{e(n["assess"])}</p>')
            lanes = ", ".join(l[1] for l in w.lanes)
            o.append(f'<figure class="dia"><div class="scroll">{svg}</div><figcaption>Current state. Swimlanes: {e(lanes)}. Source: <code>specs.py</code> → <code>svg/{wid}.svg</code></figcaption></figure>')
            o.append('<div class="cols"><div><h4>Findings</h4><ul class="flist">')
            for fid in n["findings"]:
                sev, title, detail, ev = content.FINDINGS[fid]
                anchor = f' id="f-{fid}"' if owner[fid] == wid else ""
                o.append(f'<li{anchor}><span class="fid">{fid}</span><span class="pill {SEV_CLASS[sev]}">{sev}</span> <span class="ftitle">{e(title)}</span>'
                         f'<p class="fdetail">{e(detail)}</p><p class="ev">{e(ev)}</p>{status_html(fid)}</li>')
            o.append('</ul></div><div><h4>Recommendations and what they change</h4><ul class="rlist">')
            for pri, eff, rec, chg in n["recs"]:
                o.append(f'<li><div class="rtop"><span class="pill p-{pri.lower()}">{pri}</span><span class="eff">effort {eff}</span></div>{e(rec)}'
                         f'<p class="change"><b>In the app</b>{e(chg)}</p></li>')
            o.append("</ul></div></div></article>")
        o.append("</section>")

    # Themes
    o.append('<section id="themes"><h2>Themes across the workflows</h2><div class="themes">')
    for t, b in content.THEMES:
        body = e(b)
        for fid in content.FINDINGS:
            body = body.replace(f"({fid})", f'(<a href="#f-{fid}">{fid}</a>)').replace(f"({fid},", f'(<a href="#f-{fid}">{fid}</a>,').replace(f" {fid})", f' <a href="#f-{fid}">{fid}</a>)')
        o.append(f"<article><h3>{e(t)}</h3><p>{body}</p></article>")
    o.append("</div><h3 style=\"margin-top:26px\">Reviewed and fine as it is</h3><ul class=\"keep prose\">")
    for k in content.KEEP:
        o.append(f"<li>{e(k)}</li>")
    o.append("</ul></section>")

    # Roadmap
    o.append('<section id="roadmap"><h2>Roadmap</h2><p class="prose">Every recommendation in one place. <strong>Now</strong>: before the DNS cutover. <strong>Next</strong>: the next minor release. <strong>Later</strong>: when convenient. Effort is S (under a day), M (a few days), L (more).</p>')
    rows = [(PRI_ORDER[r[0]], ordered.index(wid), wid, r) for wid in ordered for r in notes[wid]["recs"]]
    rows.sort(key=lambda t: (t[0], t[1]))
    o.append('<div class="tablewrap"><table><thead><tr><th>When</th><th>Effort</th><th>Recommendation</th><th>Workflow</th><th>Findings</th></tr></thead><tbody>')
    for _, _, wid, (pri, eff, rec, _chg) in rows:
        fl = " ".join(fid_link(f) for f in notes[wid]["findings"])
        o.append(f'<tr><td class="nw"><span class="pill p-{pri.lower()}">{pri}</span></td><td class="nw mono">{eff}</td><td>{e(rec)}</td><td><a href="#wf-{wid}">{e(wfs[wid].title)}</a></td><td class="nw">{fl}</td></tr>')
    o.append("</tbody></table></div></section>")

    # Index
    o.append('<section id="index"><h2>All findings</h2><div class="tablewrap"><table><thead><tr><th>Id</th><th>Severity</th><th>Finding</th><th>Workflow</th><th>Status</th><th>Evidence</th></tr></thead><tbody>')
    for fid, (sev, title, _d, ev) in content.FINDINGS.items():
        wid = owner.get(fid)
        wl = f'<a href="#wf-{wid}">{e(wfs[wid].title)}</a>' if wid else "—"
        o.append(f'<tr><td class="nw"><a href="#f-{fid}" class="mono">{fid}</a></td><td class="nw"><span class="pill {SEV_CLASS[sev]}">{sev}</span></td><td>{e(title)}</td><td>{wl}</td><td>{status_html(fid)}</td><td class="ev">{e(ev)}</td></tr>')
    o.append("</tbody></table></div></section>")

    o.append('<footer><p>Sources: <code>plugins/spokares-core/inc/*.php</code> and <code>assets/js</code>, <code>mu-plugins/spokares-hardening/</code>, <code>ops/</code>, <code>.github/workflows/</code>, <code>EDITING-GUIDE.md</code>, <code>ux/journeys.md</code>, <code>ux/SPEC.md</code>, and the GitHub environment settings. Paths are relative to <code>wordpress/</code>, and <code>inc/</code> means <code>plugins/spokares-core/inc/</code>.</p>'
             '<p>Rebuild: <code>python3 wordpress/ux/workflows/build.py</code>. Edit the diagrams in <code>specs.py</code> and the assessment in <code>content.py</code>.</p></footer>')
    o.append("</div>")
    if missing:
        print("note: findings not attached to any workflow:", sorted(missing), file=sys.stderr)
    return "\n".join(o), svgs


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--artifact", help="also write the body-only page here")
    a = ap.parse_args()
    body, svgs = render_body()
    os.makedirs(os.path.join(HERE, "svg"), exist_ok=True)
    for wid, s in svgs.items():
        with open(os.path.join(HERE, "svg", f"{wid}.svg"), "w") as f:
            f.write(s + "\n")
    page = ('<!doctype html>\n<html lang="en"><head><meta charset="utf-8">'
            '<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">\n'
            + body.replace("</style>", "</style>\n</head><body>", 1) + "\n</body></html>\n")
    with open(os.path.join(HERE, "index.html"), "w") as f:
        f.write(page)
    if a.artifact:
        with open(a.artifact, "w") as f:
            f.write(body + "\n")
    print(f"wrote {len(svgs)} diagrams, index.html" + (f", {a.artifact}" if a.artifact else ""))


if __name__ == "__main__":
    main()
