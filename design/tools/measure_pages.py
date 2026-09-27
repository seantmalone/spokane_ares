#!/usr/bin/env python3
"""Measure information load and repetition across a design option's pages.

For each page: visible words in <main>, rendered height at 1440px and 390px,
h2 section count, CTA count (buttons + .btn links). Across pages: repeated
8-word shingles (same sentence fragments on several pages) and how many pages
repeat key facts.

Usage: .venv/bin/python design/tools/measure_pages.py design/round2/option-a-field-guide [...]
       add --json to print machine-readable output.
"""
import json
import re
import subprocess
import sys
import tempfile
from collections import defaultdict
from pathlib import Path

from bs4 import BeautifulSoup

CHROME = "/Applications/Google Chrome.app/Contents/MacOS/Google Chrome"
PAGES = ["index.html", "how-it-works.html", "about.html",
         "members/index.html", "members/documents.html", "members/exercises.html"]
KEY_FACTS = {
    "repeater 147.300": r"147\.3(00)?",
    "meeting address": r"1121 W\.? Gardner",
    "Tuesday net time": r"(8:00 ?PM|2000)",
    "ARES vs ACS explainer": r"ARES.{0,80}ACS.{0,80}(both|differen|two)",
    "join steps": r"(step \d|four steps|0.?3)",
    "county application": r"(Volunteer Emergency Worker|background check)",
    "ICS-213": r"ICS-?213",
    "task book": r"task ?book",
    "WSDOT exercise": r"WSDOT",
    "groups.io": r"groups\.io",
}


def height(page: Path, width: int) -> int:
    """Rendered scrollHeight via a same-origin file:// iframe wrapper."""
    wrapper = f"""<!doctype html><html><body style="margin:0">
<iframe id=f src="{page.as_uri()}" style="width:{width}px;height:900px;border:0"></iframe>
<script>
document.getElementById('f').onload = () => setTimeout(() => {{
  const d = document.getElementById('f').contentDocument;
  document.body.setAttribute('data-h', Math.max(d.documentElement.scrollHeight, d.body.scrollHeight));
}}, 1500);
</script></body></html>"""
    with tempfile.NamedTemporaryFile("w", suffix=".html", delete=False) as fh:
        fh.write(wrapper)
    out = subprocess.run(
        [CHROME, "--headless=new", "--disable-gpu", "--allow-file-access-from-files",
         f"--window-size={width},900", "--virtual-time-budget=5000", "--dump-dom", Path(fh.name).as_uri()],
        capture_output=True, text=True, timeout=90).stdout
    m = re.search(r'data-h="(\d+)"', out)
    return int(m.group(1)) if m else -1


def text_of(page: Path):
    soup = BeautifulSoup(page.read_text(encoding="utf-8", errors="ignore"), "html.parser")
    main = soup.find("main") or soup.body
    for junk in main.select("script, style, noscript, [hidden], [aria-hidden=true]"):
        junk.decompose()
    text = re.sub(r"\s+", " ", main.get_text(" ", strip=True))
    ctas = len(main.select("button, a.btn, a[class*=btn], a[class*=button], a[class*=cta]"))
    return text, len(main.find_all("h2")), ctas


def shingles(text, n=8):
    w = re.findall(r"[a-z0-9']+", text.lower())
    return {" ".join(w[i:i + n]) for i in range(len(w) - n + 1)}


def measure(opt: Path):
    rows, texts = [], {}
    for p in PAGES:
        f = opt / p
        if not f.exists():
            continue
        text, h2, ctas = text_of(f)
        texts[p] = text
        rows.append({"page": p, "words": len(text.split()), "h2": h2, "ctas": ctas,
                     "h_desktop": height(f, 1440), "h_mobile": height(f, 390)})
    owners = defaultdict(set)
    for p, t in texts.items():
        for s in shingles(t):
            owners[s].add(p)
    repeated = {s: pg for s, pg in owners.items() if len(pg) > 1}
    facts = {k: sorted(p for p, t in texts.items() if re.search(rx, t, re.I)) for k, rx in KEY_FACTS.items()}
    return {"option": opt.name, "pages": rows,
            "total_words": sum(r["words"] for r in rows),
            "repeated_shingles": len(repeated),
            "repeated_shingle_ratio": round(len(repeated) / max(1, len(owners)), 3),
            "key_fact_page_counts": {k: len(v) for k, v in facts.items()},
            "key_fact_pages": facts}


if __name__ == "__main__":
    args = [a for a in sys.argv[1:] if not a.startswith("--")]
    results = [measure(Path(a).resolve()) for a in args]
    if "--json" in sys.argv:
        print(json.dumps(results, indent=1))
    else:
        for r in results:
            print(f"\n== {r['option']}  total words {r['total_words']}  repeated 8-word shingles {r['repeated_shingles']} ({r['repeated_shingle_ratio']:.1%})")
            print(f"  {'page':<26}{'words':>7}{'h2':>5}{'ctas':>6}{'desk px':>9}{'mob px':>8}")
            for x in r["pages"]:
                print(f"  {x['page']:<26}{x['words']:>7}{x['h2']:>5}{x['ctas']:>6}{x['h_desktop']:>9}{x['h_mobile']:>8}")
            print("  key facts on N pages:", ", ".join(f"{k}={v}" for k, v in r["key_fact_page_counts"].items()))
