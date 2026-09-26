#!/usr/bin/env python3
"""Redact personal contact details from derived Markdown and from raw HTML copies.

Uses the same e-mail/phone patterns and institutional allow-list as
`ag7qp_to_md.py`, plus:
  - phone numbers written "(208)882-6755" (no space after the area code),
  - one historical home street address (the 2004 Pactor-gateway host),
  - member lists: the 2004 mobilization tree is replaced by a note
    (README rule 4: rosters and member lists are not copied).

Usage (run from the repo root, /Users/sean/Projects/spokane_ares):
  .venv/bin/python research/tools/redact_pii.py archive
      rewrites research/content/archive/*.md in place and adds a
      `redacted:` line to the front matter of every file it changed
  .venv/bin/python research/tools/redact_pii.py html FILE [FILE ...]
      rewrites raw HTML files in place and appends one row per file
      (original sha256, bytes, redaction count) to
      research/external/redaction-manifest.tsv

Both modes are idempotent: a second run changes nothing.
`html_to_md.py` imports `redact_md()` and `member_list_note()`, so a
re-conversion of the Wayback snapshots stays redacted.
"""
import hashlib
import re
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parent))
from ag7qp_to_md import ALLOWED_EMAILS, EMAIL_RE, EXTRA_REDACTIONS, PHONE_RE, REDACTED  # noqa: E402

R = Path("research")
ARCHIVE = R / "content" / "archive"
MANIFEST = R / "external" / "redaction-manifest.tsv"
TODAY = "2026-09-25"

ADDRESS_MARK = "[redacted: home address]"
# Home street addresses of individuals found in the archive (history.md §6).
HOME_ADDRESSES = ["4514 E. Sorel", "4514 E Sorel"]
# "(208)882-6755": PHONE_RE needs a separator after ")"; this catches the rest.
PHONE_PAREN_RE = re.compile(r"\(\d{3}\)\s?\d{3}[-.\s ]\d{4}\b")
# Markdown links whose target is mailto:/tel: -> keep the text, drop the target.
CONTACT_LINK_RE = re.compile(r"\[([^\]]*)\]\((?:mailto|tel):[^)]*\)", re.I)

# Derived files that are member lists: body replaced by a note.
MEMBER_LISTS = {
    "20040416-tree-html.md": "the 2003-2004 Telephone Mobilization Tree (a list of member call signs by calling group)",
}


def member_list_note(what):
    return (f"> **Withheld ({TODAY}).** This page is {what}. Member lists are not copied into this "
            "research folder (`research/README.md`, rule 4). The Wayback capture is cited in the front "
            "matter; the local raw snapshot is git-ignored.\n")


# Institutional addresses found outside ag7qp.com (kept, like ALLOWED_EMAILS).
EXTRA_ALLOWED = {"nna0wa@winlink.org"}  # WA EMD State EOC Winlink station (wa-emd-logistics-comms-annex-2021.txt)


def _email(m):
    e = m.group(0).lower()
    return m.group(0) if (e in ALLOWED_EMAILS or e in EXTRA_ALLOWED) else REDACTED


def redact_text(s):
    """Return (new_text, count) for plain text, Markdown or HTML source."""
    n = 0
    for a in HOME_ADDRESSES + EXTRA_REDACTIONS:
        k = s.count(a)
        if k:
            s = s.replace(a, ADDRESS_MARK if a in HOME_ADDRESSES else REDACTED)
            n += k
    s, k = EMAIL_RE.subn(_email, s); n += k
    s, k = PHONE_RE.subn(REDACTED, s); n += k
    s, k = PHONE_PAREN_RE.subn(REDACTED, s); n += k
    return s, n


def redact_md(md):
    md, n = redact_text(md)

    def link(m):
        txt = m.group(1).strip()
        return REDACTED if (not txt or REDACTED in txt) else f"{txt} {REDACTED}"
    md = CONTACT_LINK_RE.sub(link, md)
    # a link whose text and target were both contact details -> plain marker
    md = re.sub(r"\[" + re.escape(REDACTED) + r"\]\([^)]*" + re.escape(REDACTED) + r"[^)]*\)", REDACTED, md)
    return md, n


def split_front(text):
    if text.startswith("---\n"):
        end = text.find("\n---\n", 4)
        if end != -1:
            return text[: end + 1], text[end + 5:]
    return "", text


def add_front_line(front, line):
    return front + line + "\n" if front else ""


def do_archive():
    total = 0
    for p in sorted(ARCHIVE.glob("*.md")):
        text = p.read_text(encoding="utf-8")
        front, body = split_front(text)
        if p.name in MEMBER_LISTS:
            if "**Withheld (" in body:
                continue
            body = member_list_note(MEMBER_LISTS[p.name])
            front = add_front_line(front, f'redacted: "{TODAY}: member list withheld (tools/redact_pii.py)"')
            p.write_text(front + "---\n" + body, encoding="utf-8")
            print(f"withheld member list: {p}")
            continue
        new, n = redact_md(body)
        if n == 0 and new == body:
            continue
        front = add_front_line(front, f'redacted: "{TODAY}: {n} personal contact detail(s) (tools/redact_pii.py)"')
        p.write_text((front + "---\n" if front else "") + new, encoding="utf-8")
        total += n
        print(f"{n:3d}  {p}")
    print(f"total redactions: {total}")


def do_html(paths):
    rows = []
    if not MANIFEST.exists():
        rows.append("file\toriginal_sha256\toriginal_bytes\tredactions\tdate")
    for name in paths:
        p = Path(name)
        raw = p.read_bytes()
        text = raw.decode("utf-8", errors="ignore")
        new, n = redact_text(text)
        if n == 0:
            print(f"nothing to redact: {p}")
            continue
        rows.append(f"{p.as_posix()}\t{hashlib.sha256(raw).hexdigest()}\t{len(raw)}\t{n}\t{TODAY}")
        p.write_text(new, encoding="utf-8")
        print(f"{n:3d}  {p}")
    if rows:
        with MANIFEST.open("a", encoding="utf-8") as f:
            f.write("\n".join(rows) + "\n")


if __name__ == "__main__":
    if len(sys.argv) >= 2 and sys.argv[1] == "archive":
        do_archive()
    elif len(sys.argv) >= 3 and sys.argv[1] == "html":
        do_html(sys.argv[2:])
    else:
        sys.exit(__doc__)
