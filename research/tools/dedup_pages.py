#!/usr/bin/env python3
"""Group mirrored spokares.org HTML files into unique pages by main-content hash.

Joomla serves the same article under many URL aliases (Itemid variants,
`2-uncategorised` paths, http vs https, www vs apex). This collapses them so
each unique page is analysed once.

Usage: python3 dedup_pages.py <raw_dir> <out_json>
"""
import hashlib
import html
import json
import re
import sys
from pathlib import Path

raw = Path(sys.argv[1])
out = Path(sys.argv[2])

MAIN_RE = re.compile(r'<section class="span9" id="main">(.*?)</section>', re.S)
TITLE_RE = re.compile(r"<title>(.*?)</title>", re.S)
BASE_RE = re.compile(r'<base href="([^"]+)"')


def text_of(fragment):
    fragment = re.sub(r"<(script|style)\b.*?</\1>", " ", fragment, flags=re.S)
    # drop the CSRF token / per-request noise that would defeat hashing
    fragment = re.sub(r'name="[0-9a-f]{32}"', "", fragment)
    t = html.unescape(re.sub(r"<[^>]+>", " ", fragment))
    return re.sub(r"\s+", " ", t).strip()


def url_for(path):
    rel = path.relative_to(raw).as_posix()
    host, _, rest = rel.partition("/")
    rest = re.sub(r"\.html$", "", rest).replace("@", "?")
    return f"https://{host}/{rest}"


groups = {}
for f in sorted(raw.rglob("*.html")):
    s = f.read_text(encoding="utf-8", errors="ignore")
    m = MAIN_RE.search(s)
    main_html = m.group(1) if m else s
    body = text_of(main_html)
    h = hashlib.sha1(body.encode()).hexdigest()[:12]
    g = groups.setdefault(h, {
        "hash": h,
        "title": text_of(TITLE_RE.search(s).group(1)) if TITLE_RE.search(s) else "",
        "canonical_base": (BASE_RE.search(s).group(1) if BASE_RE.search(s) else ""),
        "chars": len(body),
        "files": [],
        "urls": [],
    })
    g["files"].append(f.relative_to(raw).as_posix())
    g["urls"].append(url_for(f))

pages = sorted(groups.values(), key=lambda g: (g["title"].lower(), -g["chars"]))
for p in pages:
    # representative file: shortest path is usually the canonical menu URL
    p["representative"] = min(p["files"], key=len)
    p["aliases"] = len(p["files"])
out.write_text(json.dumps(pages, indent=1))
print(f"{sum(len(p['files']) for p in pages)} html files -> {len(pages)} unique pages")
