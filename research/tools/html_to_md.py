#!/usr/bin/env python3
"""Convert crawled spokares.org HTML into clean Markdown for reference/migration.

Live articles come from raw/by-id/article-N.html (the complete Joomla article
set, fetched by id); their public menu URLs are looked up in pages-index.json.
Archived pages come from wayback/manifest.tsv (timestamp, url, file).

Archived pages are passed through redact_pii.redact_md() (personal phones,
e-mails, one home address) and member lists are replaced by a note, so a
re-run keeps content/archive/ free of personal contact details. Roster
binaries are listed in content/index.md without a link.

Usage: .venv/bin/python research/tools/html_to_md.py   (run from repo root)
"""
import json
import re
import sys
from pathlib import Path
from urllib.parse import urljoin

sys.path.insert(0, str(Path(__file__).resolve().parent))
from redact_pii import MEMBER_LISTS, TODAY, member_list_note, redact_md  # noqa: E402

ROSTER_URL_RE = re.compile(r"memberlist|roster", re.I)

from bs4 import BeautifulSoup
from markdownify import markdownify

R = Path("research")
OUT = R / "content"
CRAWLED = "2026-09-25"


def slugify(s):
    return re.sub(r"[^a-z0-9]+", "-", s.lower()).strip("-")[:60] or "page"


def absolutize(node, base):
    for tag, attr in (("a", "href"), ("img", "src")):
        for el in node.find_all(tag):
            if el.get(attr) and not el[attr].startswith(("mailto:", "#", "javascript:")):
                el[attr] = urljoin(base, el[attr])


def to_md(node):
    for junk in node.select("script, style, form, .pager, .icons, .btn-group, .jcl_toolbar"):
        junk.decompose()
    md = markdownify(str(node), heading_style="ATX", bullets="-")
    md = re.sub(r"\n{3,}", "\n\n", md)
    return re.sub(r"[ \t]+\n", "\n", md).strip() + "\n"


def front(meta):
    lines = ["---"] + [f"{k}: {json.dumps(v)}" for k, v in meta.items()] + ["---", ""]
    return "\n".join(lines)


index_rows = []

# ---- live articles -------------------------------------------------------
pages = json.loads((R / "pages-index.json").read_text())
live_dir = OUT / "live"
live_dir.mkdir(parents=True, exist_ok=True)
for f in sorted((R / "raw/by-id").glob("article-*.html"), key=lambda p: int(re.search(r"(\d+)", p.stem).group(1))):
    aid = int(re.search(r"(\d+)", f.stem).group(1))
    soup = BeautifulSoup(f.read_text(encoding="utf-8", errors="ignore"), "html.parser")
    body = soup.select_one("div.item-page")
    if not body:
        continue
    h = body.find(["h1", "h2"])
    title = h.get_text(" ", strip=True) if h else f"Article {aid}"
    # public URLs: pages whose alias URLs mention "/<id>-" or whose title matches
    urls = sorted({u for p in pages for u in p["urls"]
                   if re.search(rf"/{aid}-[a-z]", u) or p["title"].strip() == title}, key=len)
    head_link = h.find("a") if h else None
    base = urljoin("https://www.spokares.org/", head_link["href"]) if head_link and head_link.get("href") else (urls[0] if urls else "https://www.spokares.org/")
    info = body.select_one("dl.article-info")
    info_text = info.get_text(" ", strip=True) if info else ""
    dates = {k: m.group(1) for k in ("Published", "Last Updated", "Created")
             if (m := re.search(rf"{k}:\s*(\d{{1,2}} \w+ \d{{4}})", info_text))}
    hits = re.search(r"Hits:\s*(\d+)", info_text)
    if hits:
        dates["hits"] = int(hits.group(1))
    if info:
        info.decompose()
    absolutize(body, base)
    md = to_md(body)
    name = f"{aid:02d}-{slugify(title)}.md"
    meta = {"title": title, "joomla_article_id": aid, "source_url": base,
            "alias_urls": len(urls), "joomla_info": dates, "crawled": CRAWLED, "source_file": f.as_posix()}
    (live_dir / name).write_text(front(meta) + f"# {title}\n\n" + md)
    index_rows.append(("live", name, title, base, len(md)))

# ---- archived (Wayback) pages -------------------------------------------
arch_dir = OUT / "archive"
arch_dir.mkdir(parents=True, exist_ok=True)
manifest = R / "wayback/manifest.tsv"
for line in manifest.read_text().splitlines():
    ts, url, path = line.split("\t")
    if "calendar" in url.lower() and ("date=" in url or "/day" in url or "/week" in url or "/month" in url):
        continue  # calendar navigation views: no unique content
    p = R / "wayback" / path
    if not p.exists():
        continue
    raw = p.read_bytes()
    if raw[:4] == b"%PDF" or url.lower().endswith((".pdf", ".doc", ".ics")):
        if ROSTER_URL_RE.search(url):  # member list / roster: never link it (README rule 4)
            index_rows.append(("archive-binary (withheld)", "member list: not linked (README rule 4; `decisions.md` D14)",
                               url.rsplit("/", 1)[-1], url, 0))
        else:
            index_rows.append(("archive-binary", p.relative_to(R).as_posix(), url.rsplit("/", 1)[-1], url, len(raw)))
        continue
    soup = BeautifulSoup(raw.decode("utf-8", errors="ignore"), "html.parser")
    body = soup.select_one("div.item-page") or soup.select_one("#main") or soup.body
    if not body:
        continue
    t = soup.title.get_text(" ", strip=True) if soup.title else ""
    absolutize(body, url)
    md = to_md(body)
    if len(md) < 40:
        continue
    name = f"{ts[:8]}-{slugify(re.sub(r'^https?://[^/]+/', '', url))}.md"
    meta = {"title": t, "source_url": url, "wayback_timestamp": ts,
            "wayback_url": f"https://web.archive.org/web/{ts}/{url}", "source_file": p.as_posix()}
    if name in MEMBER_LISTS:
        md = member_list_note(MEMBER_LISTS[name])
        meta["redacted"] = f"{TODAY}: member list withheld (tools/redact_pii.py)"
    else:
        md, n_red = redact_md(md)
        if n_red:
            meta["redacted"] = f"{TODAY}: {n_red} personal contact detail(s) (tools/redact_pii.py)"
    (arch_dir / name).write_text(front(meta) + md)
    index_rows.append(("archive", name, t, url, len(md)))

# ---- index ---------------------------------------------------------------
lines = ["# Extracted content index", "",
         f"Generated by `research/tools/html_to_md.py` from the {CRAWLED} crawl.", "",
         "| Set | File | Title | Source URL | Size |", "|---|---|---|---|---|"]
for kind, name, title, url, n in sorted(index_rows):
    if kind == "archive-binary (withheld)":
        lines.append(f"| {kind} | {name} | {title} | {url} | – |")
        continue
    link = f"{'live' if kind == 'live' else 'archive'}/{name}" if kind != "archive-binary" else f"../{name}"
    lines.append(f"| {kind} | [{name}]({link}) | {title.replace('|', '/')} | {url} | {n} |")
(OUT / "index.md").write_text("\n".join(lines) + "\n")
counts = {k: sum(1 for r in index_rows if r[0] == k) for k in ("live", "archive", "archive-binary")}
print(counts)
