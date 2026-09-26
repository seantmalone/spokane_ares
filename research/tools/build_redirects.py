#!/usr/bin/env python3
"""Build research/redirects.csv: every known old spokares.org URL path -> what the new site does with it.

Inputs (all local):
  research/wayback/cdx-spokares.txt   946 Wayback URLs (original mimetype status timestamp)
  research/pages-index.json           the 2026-09-25 crawl, 445 unique pages (their URLs)
  research/content/live/*.md          Joomla article id and hit counter per article
  the non-SEF article links cited in research/orgs/relationships.md
  (index.php?option=com_content&view=article&id=N)

Targets are the PROPOSED slugs from research/decisions.md D15. They are not
final until D15 is agreed; re-run this script after changing TARGETS/ARTICLE.

Columns:
  old_path     path + query, host-agnostic (applies to spokares.org, www. and mail.)
  status       301 | 410 | keep | none (no rule: the new server's normal 404)
  target       new path for 301/keep (proposed, D15)
  rule         which rule matched (for review)
  hits         Joomla article hit counter where known (includes bots), or the
               OSDownloads download count (documents-inventory.md §5.3)
  first_seen   first Wayback capture (YYYY-MM-DD), blank if only in the crawl
  sources      cdx / crawl / refs
  note

Usage (from the repo root): python3 research/tools/build_redirects.py
"""
import csv
import json
import re
from pathlib import Path
from urllib.parse import unquote, urlsplit

R = Path("research")

# ---- article id -> (status, target, note) ------------------------------------
ARTICLE = {
    1: ("410", "", "Under Construction placeholder"),
    2: ("301", "/about/history/#plan", "ARES/RACES Plan: historical PDF only after the EC decides (D13 #8)"),
    3: ("301", "/about/leadership/", ""),
    4: ("301", "/about/", ""),
    5: ("301", "/about/w7gbu/", ""),
    6: ("301", "/about/history/#amateur-code", ""),
    7: ("301", "/nets/repeaters/", ""),
    8: ("301", "/nets/net-control/", "preamble"),
    9: ("301", "/nets/net-control/", "NCS principles"),
    10: ("301", "/join/requirements/", "1994 ICS module dropped; FEMA IS-100 link lives on the requirements page"),
    11: ("301", "/join/get-licensed/", ""),
    12: ("301", "/privacy/", "Terms page dropped; short disclaimer on /privacy/ and in the footer"),
    13: ("301", "/privacy/", ""),
    20: ("301", "/about/what-we-do/#alert-levels", ""),
    21: ("301", "/library/go-kits/", ""),
    23: ("301", "/join/#get-connected", "groups.io list"),
    24: ("301", "/library/resources/", ""),
    25: ("410", "", "Mentor programs: 301 to /library/ only if the Traffic Handler files are recovered (D1)"),
    26: ("301", "/join/", ""),
    27: ("301", "/nets/#where-we-meet", ""),
    37: ("301", "/about/what-we-do/#skywarn", ""),
    41: ("301", "/join/requirements/", "Welcome Letter (2018) superseded by the ACS Task Book"),
    42: ("301", "/join/get-licensed/", ""),
    61: ("410", "", "news stub"),
    62: ("301", "/library/resources/#rfi", ""),
    68: ("301", "/about/partners/#agreements", "IEVHFRA MOU"),
}

# SEF menu paths (lower-case, without /index.php) -> article id or (status, target, note)
MENU = {
    "about/amateur-code": 6, "about/ares-races-leadership": 3, "about/email-list": 23,
    "about/meeting-location": 27, "about/mou-with-ievhfra": 68, "about/w7gbu-history": 5,
    "about/what-we-do": ("301", "/about/what-we-do/", "2019 page"),
    "contact/join-ares-races": 26, "membership/join-ares-races": 26, "contact/welcome-letter": 41,
    "contact/ares-races-app-form": ("301", "/join/#apply", "2019 BreezingForms application (stored PII on the host); no form on the new site"),
    "contact/ares-races-app-form/view/form": ("301", "/join/#apply", "as above"),
    "contact/rsform-ares-app": ("301", "/join/#apply", "2024 RSForm application; no form on the new site"),
    "contact/contact-form": ("301", "/contact/", "ALFContact (HTTP 500 today)"),
    "membership/amateur-training": 42, "membership/testing-schedule": 11,
    "membership/elmer-resources-mentoring": ("301", "/join/get-licensed/", ""),
    "membership/ham-radio-links": 24,
    "membership/member-login": ("301", "/members/", "no site accounts (D8); /members/ links into groups.io"),
    "operations/equipment-lists": 21, "operations/forms": ("301", "/library/#forms", ""),
    "operations/incident-command-system": 10, "operations/net-control-station": 9,
    "operations/net-preamble": 8, "operations/repeater-etiquette": 7,
    "operations/skywarn-weather-spotting": 37, "operations/status-codes": 20,
    "operations/training-courses": ("301", "/join/requirements/", "FEMA course links"),
    "operations/shelter-in-place": ("410", "", "2018 GSEM video post"),
    "operations/maps-testing": ("410", "", "abandoned FocalPoint test"),
    "forum": ("410", "", "abandoned Kunena forum"), "photos": ("410", "", "abandoned gallery"),
    "digital-messaging/rfi-resources/mel-ming-rfi-resources": 62,
    "2-uncategorised": ("301", "/", "Joomla category listing"),
}

# 2004-2015 static-site files (lower-case path) -> (status, target, note)
STATIC = {
    "/index.html": ("301", "/", ""), "/index": ("301", "/", ""),
    "/aecs.html": ("301", "/about/leadership/", "2004 staff page"),
    "/staff.html": ("301", "/about/leadership/", ""), "/stafffirst.html": ("301", "/about/leadership/", ""),
    "/stafflist.html": ("301", "/about/leadership/", "still linked from other sites (orgs/relationships.md)"),
    "/aresplan25aug09.html": ("301", "/about/history/#plan", ""),
    "/ares_plan_master-8.09.html": ("301", "/about/history/#plan", ""),
    "/ares_plan_master_7.13.html": ("301", "/about/history/#plan", ""),
    "/theplan.html": ("301", "/about/history/#plan", ""),
    "/emaillist.html": ("301", "/join/#get-connected", ""),
    "/equipmentlists.html": ("301", "/library/go-kits/", ""),
    "/field day 2013.pdf": ("301", "/about/history/", "Field Day record"),
    "/field day 2013.jpg": ("301", "/about/history/", ""),
    "/fieldday2012.pdf": ("301", "/about/history/", "Field Day record"),
    "/incidentcommand.html": ("301", "/join/requirements/", ""),
    "/instructions.html": ("410", "", "instructions for the member phone tree"),
    "/links.html": ("301", "/library/resources/", ""),
    "/memberlist.pdf": ("410", "", "MEMBER LIST: 410 Gone, never redirect (documents-inventory.md §6 Step 7)"),
    "/downloads/ares net roster feb09.doc": ("410", "", "MEMBER ROSTER: 410 Gone, never redirect (documents-inventory.md §6 Step 7)"),
    "/tree.html": ("410", "", "member phone tree (member list)"),
    "/mentorprograms.html": ("410", "", "see article 25"),
    "/net preamble 3-2009.pdf": ("301", "/nets/net-control/", ""),
    "/net preamble 9.1.2007.html": ("301", "/nets/net-control/", ""),
    "/netpreamble.html": ("301", "/nets/net-control/", "requested as late as 2024 (history.md §10 item 4)"),
    "/preamble.html": ("301", "/nets/net-control/", ""),
    "/netcontrol.html": ("301", "/nets/net-control/", ""),
    "/pactor.html": ("301", "/about/history/", "Pactor gateway off air since 2006"),
    "/pictures.html": ("301", "/about/history/", ""),
    "/regcards.html": ("301", "/join/", ""),
    "/repeateretiquette.html": ("301", "/nets/repeaters/", ""),
    "/statuscode.pdf": ("301", "/about/what-we-do/#alert-levels", ""),
    "/testingschedule.html": ("301", "/join/get-licensed/", ""),
    "/thecode.html": ("301", "/about/history/#amateur-code", ""),
    "/w7gbu.html": ("301", "/about/w7gbu/", ""),
    "/ae7rj-email.html": ("410", "", "individual's e-mail image page"),
    "/calendar.html": ("301", "/nets/", "2004 static calendar"),
    "/calendarhead.html": ("301", "/nets/", "2004 static calendar frame"),
    "/calendars.html": ("301", "/nets/", "2004 static calendar list"),
    "/incidentcommand.html.[63": ("none", "", "malformed URL"),
}
PERSON_PAGES = {"/ka7csp.html", "/kb7hdx.html", "/kd7amv.html", "/w7uwc.html", "/wa7lnc.html", "/aa7rt.html"}

OSD_CAT = {
    "net-preambles-and-scripts": "/library/#net-scripts", "presentations": "/library/#presentations",
    "forms-and-information": "/library/#membership", "general": "/library/#general",
    "digital-traffic-information-and-files": "/library/#digital", "ics-forms": "/library/#forms",
}
OSD_FILE = {  # alias -> (target, hits, note)
    "ares-races-membership-appplication": ("/join/#apply", "280", "most-downloaded file; A-26 links it"),
    "welcome-letter": ("/join/requirements/", "119", "superseded by the ACS Task Book"),
    "auxcomm-flyer-june-2019": ("/library/", "17", "retire or archive"),
    "ics-201-incident-briefing": ("/library/#forms", "", "LINK to FEMA"),
    "ics-205-incident-radio-communications-plan": ("/library/#forms", "", "LINK to FEMA (v3.1)"),
    "ics-213-general-message-form": ("/library/#forms", "", "LINK to FEMA"),
    "ics-214-activity-log": ("/library/#forms", "", "LINK to FEMA (v3.1)"),
    "lilac-festival-armed-forces-torchlight-parade-briefing": ("/library/#presentations", "", "sensitivity call pending (OSD-18, D10)"),
    "cross-band-repeaters-2017": ("/library/#presentations", "", ""),
    "traffic-handler-pdf": ("", "", "410 unless recovered"),
    "traffic-handler-mp3": ("", "", "410 unless recovered"),
}

PROBE_RE = re.compile(r"^/(\.well-known|wp-|xmlrpc|administrator|installation|cgi-bin|joomla/|cpanel_magic_revision|"
                      r"(?:cli|tmp|bin|logs|language|includes|layouts|cache|components|libraries|modules|plugins)(?:/|$)|"
                      r"robots\.txt|sitemap\.xml|ads\.txt|app-ads\.txt|favicon\.ico)", re.I)
ASSET_RE = re.compile(r"^/(images|media|templates|modules|plugins|components|cache|libraries|graphics|menugraphics|menugraphcis)/"
                      r"|\.(gif|png|jpe?g|css|js|svg|xml|mso|thmx)$|_files/", re.I)


def article_hits():
    hits = {}
    for p in (R / "content" / "live").glob("*.md"):
        t = p.read_text(encoding="utf-8")
        i = re.search(r"joomla_article_id: (\d+)", t)
        h = re.search(r'"hits": (\d+)', t)
        if i:
            hits[int(i.group(1))] = h.group(1) if h else ""
    return hits


def from_article(aid, hits, rule):
    st, tg, note = ARTICLE.get(aid, ("410", "", f"unknown article {aid}"))
    return st, tg, rule, hits.get(aid, ""), note


def classify(path, hits):
    """Return (status, target, rule, hits, note)."""
    raw = path
    p, _, q = path.partition("?")
    pl = unquote(p).lower().rstrip("/") or "/"
    if pl == "/":
        if q.startswith("locale="):
            return "none", "", "cpanel-webmail-locale", "", "cPanel webmail language switch on the mail/webmail vhost"
        return "keep", "/", "home", "", ""
    if PROBE_RE.search(pl):
        return "none", "", "probe-or-server-file", "", "bot probe or server file; the new site supplies its own robots.txt/sitemap.xml"
    if pl in PERSON_PAGES:
        return "410", "", "static-person-page", "", "2004-08 individual staff page (held personal e-mail)"
    if pl in STATIC:
        st, tg, note = STATIC[pl]
        return st, tg, "static-2004-2015", "", note
    if ASSET_RE.search(pl):
        return "none", "", "asset", "", "old image/stylesheet/template asset"
    if not pl.startswith("/index.php"):
        return "none", "", "unmatched", "", "review"
    rest = pl[len("/index.php"):].lstrip("/")
    if rest == "":
        m = re.search(r"(?:^|&)id=(\d+)", q)
        if "option=com_content" in q and m:
            return from_article(int(m.group(1)), hits, "non-sef-article")
        if "option=com_jcalpro" in q:
            return "301", "/nets/", "jcal-view", "", "non-SEF calendar view"
        if "format=feed" in q:
            return "410", "", "joomla-feed", "", "RSS/Atom feed of the news blog"
        return "301", "/", "joomla-index", "", ""
    if rest.startswith("component/mailto"):
        return "410", "", "joomla-mailto", "", "'e-mail this link' popup (spam vector)"
    if "jcalpro" in rest or rest.startswith("calendars"):
        if re.search(r"jcalpro/(?:\d+-[^/]+/)?\d+-[^/]+$", rest) and "location" not in rest and "categor" not in rest:
            return "410", "", "jcal-event", "", "individual past event"
        if "location-3" in rest or "location/3" in rest:
            return "301", "/nets/#where-we-meet", "jcal-location", "", "DEM building"
        return "301", "/nets/", "jcal-view", "", "calendar view/category; events now come from the D9 feed"
    if rest.startswith("downloads"):
        parts = rest.split("/")
        cat = parts[1] if len(parts) > 1 else ""
        alias = parts[-1]
        if alias in OSD_FILE:
            tg, h, note = OSD_FILE[alias]
            return ("301" if tg else "410"), tg, "osdownloads-file", h, note
        if cat == "mentoring":
            return "410", "", "osdownloads-category", "", "category deleted by 2024; 301 to /library/ if files recovered"
        return "301", OSD_CAT.get(cat, "/library/"), "osdownloads-category", "", "OSDownloads (HTTP 500 today)"
    # news posts /index.php/NN-alias (but not the "2-uncategorised" category)
    m = re.match(r"^(\d+)-", rest)
    if m and not rest.startswith("2-uncategorised"):
        aid = int(m.group(1))
        if aid in ARTICLE:
            return from_article(aid, hits, "sef-article-id")
        return "410", "", "news-post", "", "2017-2019 news post; content kept in research/content/archive/ (or 301 to /about/history/ if republished)"
    # menu paths, possibly with a /2-uncategorised/NN-alias tail
    m = re.search(r"2-uncategorised/(\d+)-", rest)
    if m:
        return from_article(int(m.group(1)), hits, "uncategorised-article")
    for key in sorted(MENU, key=len, reverse=True):
        if rest == key or rest.startswith(key + "/"):
            v = MENU[key]
            if isinstance(v, int):
                return from_article(v, hits, "menu-path")
            st, tg, note = v
            return st, tg, "menu-path", "", note
    if rest.startswith("digital-messaging"):
        return "301", "/library/resources/#digital", "menu-path", "", "digital messaging link lists"
    return "none", "", "unmatched", "", "review"


def main():
    hits = article_hits()
    seen = {}
    for line in (R / "wayback" / "cdx-spokares.txt").read_text().splitlines():
        f = line.split()
        if len(f) < 4:
            continue
        s = urlsplit(f[0])
        host = (s.hostname or "").lower()
        if host not in ("spokares.org", "www.spokares.org", "mail.spokares.org"):
            continue  # cPanel service hosts: see HOST_ROWS
        path = (s.path or "/") + ("?" + s.query if s.query else "")
        d = seen.setdefault(path, {"first": f[3][:8], "src": set()})
        d["first"] = min(d["first"], f[3][:8])
        d["src"].add("cdx")
    for page in json.loads((R / "pages-index.json").read_text()):
        for u in page["urls"]:
            s = urlsplit(u)
            path = (s.path or "/") + ("?" + s.query if s.query else "")
            seen.setdefault(path, {"first": "", "src": set()})["src"].add("crawl")
    for aid in (7, 11):  # non-SEF links cited in orgs/relationships.md
        path = f"/index.php?option=com_content&view=article&id={aid}"
        seen.setdefault(path, {"first": "", "src": set()})["src"].add("refs")

    rows = []
    for path, d in seen.items():
        st, tg, rule, h, note = classify(path, hits)
        fs = d["first"]
        rows.append([path, st, tg, rule, h, f"{fs[:4]}-{fs[4:6]}-{fs[6:8]}" if fs else "", "+".join(sorted(d["src"])), note])
    order = {"410": 0, "301": 1, "keep": 2, "none": 3}
    rows.sort(key=lambda r: (order.get(r[1], 9), r[2], r[0]))

    HOST_ROWS = [
        ["host:mail.spokares.org", "301", "https://www.spokares.org/$path", "host", "", "2024-09-10", "cdx",
         "duplicate vhost served the whole site in 2024; drop it (D3 step 9) or 301 everything to the canonical host"],
        ["host:spokares.org / http://", "301", "https://www.spokares.org/$path", "host", "", "", "cdx+crawl",
         "one canonical host over HTTPS; www vs apex is a D15 detail"],
        ["host:webmail. cpanel. webdisk. autodiscover. cpcalendars. cpcontacts.", "none", "", "host", "", "2023-08", "cdx",
         "InMotion cPanel service names; they end with the InMotion account (D3). Do not recreate as web redirects"],
    ]
    out = R / "redirects.csv"
    with out.open("w", newline="", encoding="utf-8") as fh:
        w = csv.writer(fh)
        w.writerow(["old_path", "status", "target", "rule", "hits", "first_seen", "sources", "note"])
        w.writerows(HOST_ROWS)
        w.writerows(rows)
    from collections import Counter
    print(out, len(rows) + len(HOST_ROWS), "rows", Counter(r[1] for r in rows), Counter(r[3] for r in rows if r[3] == "unmatched"))


if __name__ == "__main__":
    main()
