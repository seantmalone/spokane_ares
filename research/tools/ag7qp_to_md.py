#!/usr/bin/env python3
"""Convert the ag7qp.com (Hostinger/Zyro Website Builder) interim pages to Markdown.

Input : research/external/ag7qp/<slug>.html   (fetched 2026-09-25 from the sitemap)
Output: research/content/interim-ag7qp/<slug>.md

Zyro pages are a stack of <section class="block"> elements under .page__blocks.
Each section holds grid-positioned .layout-element children; they are read in
desktop grid order (--grid-row, then --grid-column). The last section on every
page (id zSiG-O) is the site-wide footer and is dropped.

Personal contact details (phone numbers, personal e-mail addresses) are replaced
with "[redacted: contact detail]". Only institutional / role mailboxes listed in
ALLOWED_EMAILS are kept.

Usage: .venv/bin/python research/tools/ag7qp_to_md.py   (run from repo root)
       .venv/bin/python research/tools/ag7qp_to_md.py --redact-raw
           also rewrites the raw HTML files in place with the same redaction
           (original sha256/size recorded in research/external/ag7qp/fetch-manifest.tsv).
"""
import hashlib
import json
import re
import sys
from pathlib import Path
from urllib.parse import urljoin

from bs4 import BeautifulSoup
from markdownify import markdownify

R = Path("research")
RAW = R / "external" / "ag7qp"
OUT = R / "content" / "interim-ag7qp"
BASE = "https://ag7qp.com/"
FETCHED = "2026-09-25"
FOOTER_ID = "zSiG-O"
REDACTED = "[redacted: contact detail]"

# Institutional or role mailboxes (not an individual's personal address).
ALLOWED_EMAILS = {
    "nws.spokane@noaa.gov",
    "public.education@mil.wa.gov",
    "vec@arrl.org",
    "pendoreillecountyares@gmail.com",
}

EMAIL_RE = re.compile(r"[A-Za-z0-9._%+-]+@[A-Za-z0-9-]+(?:\.[A-Za-z0-9-]+)*\.[A-Za-z]{2,}")
# 10-digit North American numbers with separators: 509-434-4880, (509) 434-4880, 509.434.4880
PHONE_RE = re.compile(r"(?<!\d)(?<!\d\.)(?:\+?1[-.\s]?)?\(?\b\d{3}\)?[-.\s\u00a0]{1,2}\d{3}[-.\s\u00a0]\d{4}\b(?!\d)(?!\.\d)")


# Street addresses that may be private residences (unverified venues).
EXTRA_REDACTIONS = ["4444 E 43rd Ave"]


def redact_text(s):
    for x in EXTRA_REDACTIONS:
        s = s.replace(x, REDACTED)
    s = EMAIL_RE.sub(lambda m: m.group(0) if m.group(0).lower() in ALLOWED_EMAILS else REDACTED, s)
    s = PHONE_RE.sub(REDACTED, s)
    # a link whose text and target were both contact details -> plain marker
    s = re.sub(r"\[" + re.escape(REDACTED) + r"\]\([^)]*" + re.escape(REDACTED) + r"[^)]*\)", REDACTED, s)
    return s


def slug_for(path):
    return path.stem


def sort_key(el):
    """Desktop grid position (row, then column): top-to-bottom, left-to-right."""
    st = el.get("style") or ""
    r = re.search(r"(?<![-\w])--grid-row:(\d+)", st)
    c = re.search(r"(?<![-\w])--grid-column:(\d+)", st)
    m = re.search(r"--m-grid-row:(\d+)", st)
    return (int(r.group(1)) if r else 999, int(c.group(1)) if c else 0, int(m.group(1)) if m else 0)


def img_src(img):
    src = img.get("src") or ""
    # strip Zyro CDN resize prefix -> original asset URL
    return re.sub(r"https://assets\.zyrosite\.com/cdn-cgi/image/[^/]+/", "https://assets.zyrosite.com/", src)


# Pages whose text boxes lay out columns with runs of spaces (proportional font);
# those runs are turned into " | " separators so the columns stay legible.
TABULAR_PAGES = {"nets-in-the-inland-northwest", "pota-sota", "programming-radios"}
COL = "QQCOLQQ"


def mark_columns(comp):
    for s in comp.find_all(string=True):
        if re.search(r"[ \u00a0\t]{3,}", s):
            s.replace_with(re.sub(r"[ \u00a0\t]{3,}", COL, s))


def unmark_columns(md):
    out = []
    for line in md.split("\n"):
        line = re.sub(rf"(?:\s*{COL})+\s*", " | ", line)
        line = re.sub(r"^(\s*(?:\*\*)?)\s*\|\s*", r"\1", line)
        line = re.sub(r"\s*\|\s*(\\?)$", r"\1", line)
        out.append(line)
    return "\n".join(out)


def element_md(el, tabular=False):
    comp = el.select_one(".layout-element__component")
    if comp is None:
        return ""
    cls = " ".join(comp.get("class", []))
    if "GridTextBox" in cls:
        for a in comp.find_all("a"):
            href = a.get("href") or ""
            txt = a.get_text(" ", strip=True)
            is_mail = href.lower().startswith("mailto:")
            personal_mail = is_mail and href[7:].split("?")[0].lower() not in ALLOWED_EMAILS
            if personal_mail or href.lower().startswith("tel:") or REDACTED in href:
                # drop the link target; keep a name if the link text was a name
                if not txt or REDACTED in txt or EMAIL_RE.fullmatch(txt) or PHONE_RE.fullmatch(txt):
                    a.replace_with(REDACTED)
                else:
                    a.replace_with(f"{txt} {REDACTED}")
                continue
            if href and not href.startswith(("mailto:", "#", "javascript:")):
                a["href"] = urljoin(BASE, href)
        if tabular:
            mark_columns(comp)
        md = markdownify(str(comp), heading_style="ATX", bullets="-", strip=["span", "u"], newline_style="BACKSLASH")
        return unmark_columns(md) if tabular else md
    if "GridImage" in cls:
        img = comp.find("img")
        if not img:
            return ""
        alt = img.get("alt") or Path(img_src(img)).stem
        md = f"![{alt}]({img_src(img)})"
        link = comp.find_parent("a") or el.find("a")
        if link and link.get("href"):
            md = f"[{md}]({urljoin(BASE, link['href'])})"
        return md
    if "GridButton" in cls:
        href = comp.get("href") or (comp.find("a") or {}).get("href", "")
        return f"[Button: {comp.get_text(' ', strip=True)}]({urljoin(BASE, href)})" if href else f"[Button: {comp.get_text(' ', strip=True)}]"
    if "GridForm" in cls:
        fields = [x.get_text(" ", strip=True) for x in comp.select("label")] or [comp.get_text(" | ", strip=True)]
        return "> _[Web form on ag7qp.com — fields: " + " | ".join(f for f in fields if f) + "]_"
    if "GridEmbed" in cls:
        return "> _[Embedded widget (custom HTML/script) — content not present in served HTML]_"
    return ""  # GridShape etc.


def clean(md):
    md = md.replace(" ", " ")
    md = md.replace(" ", " ")
    # dash-only "rules" typed into text boxes would otherwise turn the line above
    # into a setext heading; make them real thematic breaks
    md = re.sub(r"(?m)^[ \t]*-{5,}[ \t]*\\?[ \t]*$", "\n* * *\n", md)
    md = re.sub(r"[ \t]+\\\n", "\\\n", md)
    md = re.sub(r"\\\n(?=[ \t]*\n)", "\n", md)  # hard break right before a blank line
    md = re.sub(r"[ \t]+\n", "\n", md)
    md = re.sub(r"\n{3,}", "\n\n", md)
    md = md.strip()
    return md[:-1].rstrip() if md.endswith("\\") else md


def convert(path, nav):
    html = path.read_text(encoding="utf-8", errors="ignore")
    soup = BeautifulSoup(html, "html.parser")
    for junk in soup(["script", "style", "noscript"]):
        junk.decompose()
    title = soup.title.get_text(strip=True) if soup.title else path.stem
    title = re.sub(r"\s*\|\s*Eastern Washington Section Emergency Coordinator\s*$", "", title)
    canon = soup.find("link", rel="canonical")
    url = canon["href"] if canon and canon.get("href") else urljoin(BASE, "" if path.stem == "home" else path.stem)
    parts = []
    for sec in soup.select(".page__blocks > section"):
        if sec.get("id") == FOOTER_ID:
            continue
        els = sorted(sec.select(".block-layout > .layout-element"), key=sort_key)
        chunk = [element_md(e, path.stem in TABULAR_PAGES) for e in els]
        chunk = [clean(c) for c in chunk if c and c.strip()]
        if chunk:
            parts.append("\n\n".join(chunk))
    body = redact_text("\n\n---\n\n".join(parts))
    meta = {
        "title": title,
        "source_url": url,
        "fetched": FETCHED,
        "raw_html": str(path),
        "nav_parent": nav.get(url.rstrip("/").split("/")[-1] if path.stem != "home" else "", None),
        "site": "ag7qp.com (Hostinger Website Builder; sitemap lastmod 2026-09-23)",
    }
    fm = ["---"] + [f"{k}: {json.dumps(v)}" for k, v in meta.items()] + ["---", ""]
    note = ("> Converted from the ag7qp.com interim page. Site-wide header nav and footer omitted. "
            f"Personal phone numbers / e-mail addresses replaced with \"{REDACTED}\".\n")
    return "\n".join(fm) + note + "\n" + clean(body) + "\n"


def nav_map():
    """slug -> parent menu label, from the desktop header nav of the home page."""
    soup = BeautifulSoup((RAW / "home.html").read_text(encoding="utf-8"), "html.parser")
    nav = soup.select_one(".block-header-layout-desktop nav")
    out = {}
    for li in nav.select("li"):
        a = li.find("a")
        if not a:
            continue
        slug = a.get("href", "").strip("/")
        parent_ul = li.find_parent("ul")
        parent_li = parent_ul.find_parent("li") if parent_ul else None
        parent = parent_li.find("a").get_text(strip=True) if parent_li and parent_li.find("a") else "(top level)"
        out.setdefault(slug, parent)
    return out


def redact_raw():
    rows = ["file\tsource_url\toriginal_sha256\toriginal_bytes\tredactions"]
    for p in sorted(RAW.glob("*.html")):
        raw = p.read_bytes()
        text = raw.decode("utf-8", errors="ignore")
        if REDACTED in text:
            print(f"skip (already redacted): {p}")
            continue
        new, n1 = EMAIL_RE.subn(lambda m: m.group(0) if m.group(0).lower() in ALLOWED_EMAILS else REDACTED, text)
        new, n2 = PHONE_RE.subn(REDACTED, new)
        for x in EXTRA_REDACTIONS:
            n2 += new.count(x)
            new = new.replace(x, REDACTED)
        url = urljoin(BASE, "" if p.stem == "home" else p.stem)
        rows.append(f"{p.name}\t{url}\t{hashlib.sha256(raw).hexdigest()}\t{len(raw)}\t{n1 + n2}")
        p.write_text(new, encoding="utf-8")
    if len(rows) > 1:
        (RAW / "fetch-manifest.tsv").write_text("\n".join(rows) + "\n")


if __name__ == "__main__":
    OUT.mkdir(parents=True, exist_ok=True)
    nav = nav_map()
    for p in sorted(RAW.glob("*.html")):
        (OUT / f"{slug_for(p)}.md").write_text(convert(p, nav), encoding="utf-8")
        print("wrote", OUT / f"{slug_for(p)}.md")
    if "--redact-raw" in sys.argv:
        redact_raw()
