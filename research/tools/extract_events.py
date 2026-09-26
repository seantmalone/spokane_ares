#!/usr/bin/env python3
"""Extract JCal Pro event pages from the spokares.org mirror into JSON + CSV.

Usage: python3 extract_events.py <raw_dir> <out_prefix>
Writes <out_prefix>.json and <out_prefix>.csv, one row per unique event id.
"""
import csv
import html
import json
import re
import sys
from pathlib import Path

raw = Path(sys.argv[1])
prefix = sys.argv[2]

PATH_RE = re.compile(r"component/jcalpro/(\d+)-([^/]+)/(\d+)-([^/@.]+)")


def clean(fragment):
    fragment = re.sub(r"<(script|style)\b.*?</\1>", " ", fragment, flags=re.S)
    t = html.unescape(re.sub(r"<[^>]+>", " ", fragment))
    return re.sub(r"\s+", " ", t).strip()


def meta(s, prop):
    m = re.search(rf'itemprop="{prop}"\s+content="([^"]*)"', s)
    return html.unescape(m.group(1)) if m else ""


def detail(s, label):
    m = re.search(rf'<span\s+class="labels">{label}:</span>(.*?)</div>', s, re.S)
    return clean(m.group(1)) if m else ""


events = {}
for f in raw.rglob("*.html"):
    m = PATH_RE.search(f.as_posix())
    if not m:
        continue
    cat_id, cat_slug, ev_id, ev_slug = m.groups()
    if ev_id in events:
        events[ev_id]["aliases"] += 1
        continue
    s = f.read_text(encoding="utf-8", errors="ignore")
    title = re.search(r'<h1 itemprop="name">(.*?)</h1>', s, re.S)
    desc = re.search(r'<div class="jcl_event_description[^"]*"[^>]*>(.*?)</div>\s*</div>', s, re.S)
    body = re.search(r'<div class="jcl_event_body[^"]*"[^>]*>(.*?)<div class="jcl_event_detail', s, re.S)
    events[ev_id] = {
        "event_id": int(ev_id),
        "category_id": int(cat_id),
        "category": detail(s, "Categories").rstrip(" *"),
        "category_slug": cat_slug,
        "title": clean(title.group(1)) if title else ev_slug,
        "start": meta(s, "startDate"),
        "date_text": detail(s, "Date"),
        "location": detail(s, "Location"),
        "street": meta(s, "streetAddress"),
        "locality": meta(s, "addressLocality"),
        "latitude": meta(s, "latitude"),
        "longitude": meta(s, "longitude"),
        "description": clean(desc.group(1)) if desc else (clean(body.group(1)) if body else ""),
        "url": f"https://www.spokares.org/index.php/component/jcalpro/{cat_id}-{cat_slug}/{ev_id}-{ev_slug}",
        "source_file": f.relative_to(raw).as_posix(),
        "aliases": 1,
    }

rows = sorted(events.values(), key=lambda e: (e["start"], e["event_id"]))
Path(prefix + ".json").write_text(json.dumps(rows, indent=1))
with open(prefix + ".csv", "w", newline="") as fh:
    w = csv.DictWriter(fh, fieldnames=list(rows[0].keys()))
    w.writeheader()
    w.writerows(rows)
print(f"{len(rows)} unique events")
