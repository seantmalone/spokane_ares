# spokares.org rebuild: open decisions and blockers

- **Prepared:** 2026-09-25.
- **Paths** are relative to `research/` unless they start with `design/`.
- **Structure of each entry:**
  - **Context:** the facts, cited.
  - **Options.**
  - **Recommendation:** ours, marked as such. It is not a decision until the named people agree.
  - **Who decides / answers.**
  - **Evidence:** file paths and URLs.
- **INFERENCE** marks our reasoning, as opposed to what a source says.
- **Roles used below:**
  - **EC:** Asa Jay Laughton W7TSC, EC/RACES Officer and website admin (staff page, 2026-03-24).
  - **AG7QP:** Frank Hutchison, EWA Section Emergency Coordinator, Spokane AEC Net Manager and host of the interim page.
  - **Trustee:** Dave Carleton K7DSR, FCC trustee of W7GBU.
  - **SCEM:** Spokane County Emergency Management. The staff owner of ACS is the Communications Specialist; the current holder is unknown.
  - **Project owner:** whoever is running this rebuild.

  All names: verify before publishing (`organization.md` §4).

---

## Summary

| ID | Decision / blocker | Deadline or trigger | Blocks | Who decides | Our recommendation (short) |
|---|---|---|---|---|---|
| **D1** | Get InMotion access, take a full backup, handle it forensically | **Now.** The hacked site is still live, and it holds the only copy of 21 files | Library, redirects, DB-sourced data, mail inventory | Account holder (probably the EC), with AG7QP | Take a cPanel full backup and ask InMotion for pre-compromise backups this week. Extract **content only**, offline. Then rotate every credential and replace the Joomla site with a static holding page |
| **D2** | Domain: registrar access and renewal | **2027-03-10** (166 days away) | Everything | Registrant/account holder, with the EC | Get into the GoDaddy account and renew for 2+ years now. Turn on auto-renew, add a second person and a role e-mail, turn on 2FA |
| **D3** | DNS and e-mail move off InMotion | Before InMotion is cancelled; after D1 | Launch, contact path | EC (aliases), technical admin | Move NS to Verpex/Enhance. Role forwarders on Enhance mail. Inventory existing mailboxes first. **The MX is live, not null** |
| **D4** | W7GBU license renewal | Window opens about **2026-11-17**; expires **2027-02-15** | Legal on-air identity for the net, repeater and gateway | Trustee K7DSR | Renew in FCC ULS in late Nov 2026. Put it in a shared renewals register |
| **D5** | Platform (WordPress vs static) and who maintains it | Before build | Build, design implementation | EC and AG7QP with the project owner | **Lean WordPress on Enhance**, with 2 named editors. Choose static only if 2 git-comfortable maintainers exist |
| **D6** | Official name; permission for logo, seal and ARRL emblem | Before content writing and header design | Copy, header, footer | EC with SCEM (and ARRL via SM/SEC for the emblem) | "Spokane County ARES-ACS". SCEM chooses the ACS expansion. Written permission for the county mark; ARRL guidance for the ARES emblem |
| **D7** | What to do with the ag7qp.com interim content | At launch | Migration, cutover | AG7QP with the EC | Move Spokane-owned pages, link Section pages, put a notice on the temporary page, retire the duplicates |
| **D8** | Members-only area on the site, or groups.io | Before IA is final | IA, platform plugins, PII risk | EC and groups.io owner | **groups.io**. No website accounts |
| **D9** | Calendar and net-rota source of truth | Before build | Homepage "This week", Calendar page | AG7QP (Net Manager) and groups.io owner | groups.io calendar. The site shows standing facts plus an upcoming list from its feed. Fallback: a Google Calendar on a role account |
| **D10** | Document library: what to host where, and the AUP | After D1 recovery | Library page | EC, presenters, Verpex | Small public PDF library on Verpex; audio and video on archive.org or YouTube; sensitive items on groups.io. Get Verpex's written OK |
| **D11** | Contact path | Before launch | Join and Contact pages | EC | Role e-mail forwarders with mailto links first; at most one form via authenticated SMTP |
| **D12** | Design direction | Round two of design | Build | EC / leadership, PIO, SCEM for agency-facing text | Hybrid: Open Door skeleton + Field Guide Fig. 1 and quiz + a short Lines Down section, with grafts |
| **D13** | Facts to confirm before anything is published | Before copy freeze | Copy | EC, AG7QP, NV2Z, SCEM | See the checklist |
| **D14** | PII clean-up (roster, archives, repo copies, possible DB exposure) | **Now** for the public roster and repo copies | Trust, legal exposure | EC, AG7QP, project owner | Pull the public roster, decide on Wayback removal, check the DB for applicant PII. The local copies were redacted and git-ignored on 2026-09-25 (see D14) |
| **D15** | Sitemap / IA: the page list, slugs, sources, owners and review dates; the redirect map | Before build and before copy writing | Build, redirects (`redirects.csv`), copy | EC with the editors (D5) | The page inventory in D15: 5 top-level sections, 21 pages, no login, no public status badge, no Terms page |

**Critical path (INFERENCE):**
1. D1, D2 and D14 this week.
2. Then D5, D6 and D8/D9, which unblock the build.
3. Set up the Verpex account and get answers to the Verpex questions, in parallel.
4. D4 in late November.
5. D3 and D7 at cutover.
6. The domain must be renewed well before 2027-03-10. Renewing early costs nothing.

---

## D1. InMotion access, full backup and forensic handling

**Context**
- spokares.org runs Joomla 3 (EOL Aug 2023) on InMotion. It **was hacked in 2026**, per the EWA SEC on ag7qp.com. The date and vector are unknown (`history.md` §2.6, §9).
- The served HTML shows no visible or cloaked spam today (`external/spokares-home-as-googlebot.html`).
- **Hack-scope checks, 2026-09-25 (this pass):**
  - **Google Safe Browsing:** the Transparency Report status API returns "no unsafe content" for `spokares.org` and `www.spokares.org`, with every threat flag false; the data is dated 2026-06-14 (`transparencyreport.google.com/transparencyreport/api/v3/safebrowsing/status?site=spokares.org`). **INFERENCE:** the field meanings are read from the public report page; no current listing does not prove the site is clean.
  - **Mail IP and domain blocklists** (DNS queries): the web/mail IP 70.39.146.166 and the SPF IP 70.39.251.92 are **not listed** on SpamCop, Barracuda, PSBL, UCEPROTECT level 1 or Mailspike (each list's 127.0.0.2 test point answered, so the checks were valid). **Spamhaus ZEN/DBL and URIBL refused the queries** (public-resolver block) and SURBL's test point did not answer, so those are **not checked**; use check.spamhaus.org by hand.
  - **Indexed URLs (`site:spokares.org`): not checked.** DuckDuckGo blocked the automated query and Bing returned only off-domain results. Do it by hand in a browser, then in Search Console (below). Look for URLs that the CDX and crawl don't know (`redirects.csv` lists every known path), Japanese/pharma keyword pages, and new sitemaps.
  - **Search Console security issues: not checked** (needs a verified owner).
- The InMotion account is the **only known copy** of:
  - the 46 OSDownloads files (21 are marked for recovery)
  - the web-link targets
  - the JCal data
  - the menu and access settings
  - any mailboxes

  Wayback has none of the library files (`documents-inventory.md` §2, §6; `site-functionality-analysis.md` §0 items 5-6).
- The DB probably also holds **member and applicant PII**:
  - `com_users` accounts
  - possibly BreezingForms (2019) and RSForm Pro (2024) application submissions, whose fields included address, phones, cell carrier and emergency contact (`site-functionality-analysis.md` §1d)

  **INFERENCE:** these tables may still exist, and the attacker may have been able to read them.
- Someone still had admin access in 2026: the staff page was edited 2026-03-24, and JCal rota entries were added for Sep 2026 (`history.md` §2.6).
- The legacy static files still on disk but not in Wayback are:
  - `NIMSFiles.zip`
  - `TrafficHandler.pdf` and `TrafficHandler.mp3`
  - `NetPreamble.pdf`
  - `Net Preamble 9.1.2007.pdf`

  (`documents-inventory.md` §6 Goal.)

**Options**
1. The account holder generates a **cPanel Full Account Backup** (home directory, MySQL, mail, DNS zone) and downloads it off-host.
2. Also ask InMotion support for **older automatic backups from before the compromise**, and how long it keeps them.
3. Do nothing and rebuild from Wayback and ag7qp only. This loses 21 files, the web links and the mail.

**Recommendation: 1 and 2, this week.**
- **Extraction only.** Do not "restore" anything onto the live account.
- **Forensic caution: content only.** Never reuse code, templates, extensions or the DB in the new site:
  - Copy only document files. Never PHP, JS or `.htaccess`.
  - Check that each file's MIME type matches its extension.
  - Scan with `clamscan -r`.
  - Check Office files with `oletools` (`olevba`, `mraptor`). Open them only in a sandbox.
  - Re-export keepers to PDF.
  - Treat any file changed after 2024-09-13 as suspect (`documents-inventory.md` §6 Step 3).
  - Read the DB dump as **data**: run SQL queries offline (the OSDownloads query is in `documents-inventory.md` §6 Step 2, plus `#__content`, `#__menu.access`, `#__weblinks` and `#__jcalpro_*`), then convert results to Markdown. **Do not import the dump into any CMS**, and do not point Enhance's cPanel importer at this account's web files (**INFERENCE:** it would carry the compromised files across).
  - Keep the backup archive **encrypted, off the repo, and off the new host.** Verpex's AUP also forbids using hosting for off-site backups (`hosting/verpex-enhance.md` §2.2, §4.4).
- **After the backup:**
  - Rotate every credential: cPanel, Joomla admins, DB user, FTP/SFTP, every mailbox, and the GoDaddy account.
  - Enable 2FA wherever it is offered.
  - **INFERENCE:** the compromised EOL Joomla should then be replaced with a one-page static "we're moving" notice linking to ag7qp.com and groups.io. That ends the exposure while the new site is built. Keep mail running until D3.
- **PII check:** list any tables holding member or applicant data (row counts only; do not copy the contents). The EC then decides whether notification or advice is warranted. We have not assessed Washington's breach-notification thresholds; get advice.
- **Search visibility (before and after cutover):**
  1. Verify the **Domain property** in Google Search Console with a **DNS TXT record** (it survives the host move), and add Bing Webmaster Tools the same way.
  2. Read *Security & manual actions* and the indexed-pages report; request a review if anything is flagged.
  3. Run the `site:` search by hand and compare it with `redirects.csv`; any spokares.org URL not in that file is a candidate injected page and gets **410**.
  4. After cutover, submit the new sitemap and watch 404s against `redirects.csv` for 4 weeks.

**Who decides / answers**
- The InMotion account holder. This is **unknown**; probably the EC as website admin.
- InMotion support.
- The EC, for the PII question.

**Evidence:** `documents-inventory.md` §6 (Steps 1-4), `site-functionality-analysis.md` §0, §3.2, §3.3, `history.md` §2.6 and §10 item 6.

---

## D2. Domain registrar access and the 2027-03-10 expiry

**Context**
- RDAP (checked 2026-09-25) shows:
  - Registrar **GoDaddy.com, LLC**
  - Registered 2004-03-10
  - **Transferred 2021-11-12**
  - **Last changed 2026-08-12** (the change itself is not shown)
  - **Expires 2027-03-10**
  - Status: client delete/renew/transfer/update prohibited. **INFERENCE:** that is GoDaddy's usual lock set, not a problem in itself.
- Who holds the GoDaddy account is **unknown** (`orgs/_backlog.md`, subject org questions).
- A Wayback capture on 2025-03-15 shows only a `/lander` redirect, 5 days after the renewal anniversary. **INFERENCE:** this may have been a brief lapse (`history.md` §2.5).
- The domain sits in the same account family as a compromised site. **INFERENCE:** the registrar login should be treated as possibly exposed.

**Options**
1. **Keep it at GoDaddy.**
   - Get access.
   - Renew for several years now.
   - Enable auto-renew.
   - Add a second authorised person and a role contact e-mail (for example `admin@spokares.org`, once D3 is done).
   - Enable 2FA.
2. **Transfer to a neutral registrar** under an organisation-owned role account, after access is secured. A transfer adds a year.
3. **Move it to Verpex.** Verpex offers a free domain with non-monthly billing (`hosting/verpex-enhance.md` §2.2). This couples the domain to the host.

**Recommendation:** do option 1 immediately. Consider option 2 once there is a credentials register. Avoid option 3: if hosting lapses, the domain goes with it. Record the registrar, the renewal date and two named holders in a shared renewals register alongside W7GBU (D4) (`history.md` §10 item 8).

**Who decides / answers:** the current registrant or account holder (ask the EC), and the EC.

**Evidence:** RDAP https://rdap.publicinterestregistry.org/rdap/domain/spokares.org (queried 2026-09-25), `orgs/relationships.md` §1, `history.md` §2.5, §9.

---

## D3. DNS and e-mail move

**Context (DNS checked 2026-09-25 with `dig @1.1.1.1`)**

| Record | Value |
|---|---|
| NS | `ns1.inmotionhosting.com`, `ns2.inmotionhosting.com` |
| A | 70.39.146.166 (`www.` and `mail.` point to the apex) |
| MX | **`0 spokares.org.`** |
| TXT | `v=spf1 +mx +a +ip4:70.39.251.92 ~all` |
| DKIM | **Present:** `default._domainkey` TXT `v=DKIM1; k=rsa; p=...` (cPanel's default selector) |
| DMARC | **Absent:** `_dmarc.spokares.org` is NXDOMAIN (also at `ns1.inmotionhosting.com`) |
| PTR of the SPF IP | 70.39.251.92 reverse-resolves to a host under `asajay.com`. **INFERENCE:** a server associated with the EC (Asa Jay Laughton); ask him whether it sends mail for spokares.org before SPF is rewritten |

Re-checked 2026-09-25 against 1.1.1.1, 8.8.8.8 and 9.9.9.9 (all agree).

- **Correction:** the session brief said "MX is a null record". It is not. A null MX is `0 .` (RFC 7505). This is a priority-0 MX pointing at spokares.org itself, so **mail is delivered to InMotion today**, as `site-functionality-analysis.md` §0 item 6 said. `history.md` §2.6 now records this.
- Which mailboxes and forwarders exist is unknown; `webmail.spokares.org` exists.
- A duplicate `mail.spokares.org` vhost served the whole site in 2024 (`site-functionality-analysis.md` §1c).
- Verpex often changes server IPs to shake off traffic floods (68 to 70 of 77 incidents, Aug-Sep 2026). **Only Enhance-hosted DNS needs no manual action** (`hosting/verpex-enhance.md` §4.5).
- Enhance mail limits:
  - Hourly send caps are set per server.
  - `mail()` routing is unverified.
  - Mail over the cap is rejected, not queued.

  So use authenticated SMTP, and never send member-wide mail from the host (`hosting/verpex-enhance.md` §4.3).

**Options**
1. **Verpex/Enhance nameservers**, with the zone rebuilt on Enhance and mail on Enhance: role **forwarders**, and mailboxes only where someone needs one.
2. External DNS (for example Cloudflare). This needs manual A-record updates whenever Verpex changes IP; the one-way sync is unverified.
3. Mail at a separate provider. Not researched.

**Recommendation: option 1.** Sequence:
1. Inventory the cPanel mailboxes and forwarders, and export the zone (D1).
2. Agree the role aliases, for example `ec@`, `webmaster@`, `info@` and `net@`.
3. Build on Enhance using a staging domain.
4. Lower TTLs.
5. Switch NS.
6. Publish Enhance's DKIM key (replacing InMotion's `default._domainkey`), **add a DMARC record** (none exists today; start at `p=none` with a report address on a role mailbox), and rewrite SPF for the new sender (drop `+a`, `+mx` and the InMotion/`asajay.com` IP once their owners confirm nothing else sends for the domain).
   - Also add the **Search Console DNS TXT** verification record to the new zone before NS moves, so verification survives the cutover (D1).
7. Migrate any real mailboxes over IMAP. **INFERENCE:** this is safer than a whole-account cPanel import from a compromised account.
8. Cancel InMotion only after mail is verified and D1 is complete.
9. Drop the `mail.` web vhost.

**Who decides / answers:** the EC (which aliases, and who receives them), the technical admin (execution), and Verpex (Q3 and Q6 below).

**Evidence:** `site-functionality-analysis.md` §0 item 6, §3.6, §7.2; `hosting/verpex-enhance.md` §4.3, §4.5, §5.7; `history.md` §2.6.

---

## D4. W7GBU license renewal (expires 2027-02-15)

**Context**
- FCC data via callook.info, re-checked 2026-09-25:
  - **VALID** CLUB license held by "SPOKANE COUNTY ARES RACES SUPPORT GROUP"
  - Trustee **K7DSR**
  - Previous call KC7SOT
  - Granted 2017-02-15
  - **Expires 2027-02-15**
  - Last action 2024-03-18
- W7GBU identifies:
  - the Tuesday net
  - the 147.300 and 443.400 repeaters (IACC coordinations run to 2029)
  - the W7GBU-10 gateway
- ag7qp.com never mentions W7GBU. The 2026 preamble says "Spokane County ACS Repeater" (`content/interim-ag7qp/README.md`).
- **Renewal rules (checked 2026-09-25 against the eCFR text as published by Cornell LII; ecfr.gov itself blocked the automated check):**
  - **Window:** a renewal must be filed "no later than the expiration date ... and no sooner than 90 days prior to the expiration date" (47 CFR 1.949(a), which 97.21(a)(3) applies). For 2027-02-15 that opens **2026-11-17**.
  - **Who files, and where:** W7GBU replaced the sequential call KC7SOT (callook), so **INFERENCE:** it is a club **vanity** call. 97.21(a)(3)(i) sends vanity renewals to 97.19, and 97.19(a) makes "the person named in a club station license grant" (the trustee, K7DSR) eligible to apply for "the renewal thereof" with the vanity call. **INFERENCE:** the trustee files in ULS himself; a Club Station Call Sign Administrator is needed only for sequential club calls or a trustee change (97.21(a)(1), (a)(3)(iii)). If the trustee is changing, do that first through a CSCSA, signed by a club officer.
  - **Late filing:** 97.21(b) allows renewal "during a 2 year filing grace period", but "unless and until the license grant is renewed, no privileges in this part are conferred": **the station may not operate after 2027-02-15** until the renewal is granted. A timely application keeps operating authority "until the final disposition" only for the cases in 97.21(a)(3)(ii); **INFERENCE:** do not rely on it, file in late November.
  - **Fee:** 47 CFR 1.1102 Table 2 lists **$35** for personal-license renewals, including the Amateur Radio Service. The general exemptions in 47 CFR 1.1116 do not mention amateur, club or RACES stations (the non-profit exemption covers only Special Emergency and Public Safety radio). **INFERENCE:** budget $35, paid by the trustee or the group; confirm in ULS at filing time.

**Options:** the trustee renews in ULS early in the window; or the group changes trustee first, then renews; or the license is allowed to lapse (not credible).

**Recommendation:**
- The trustee confirms **now** that his FCC CORES/FRN login works, and who pays the $35.
- He renews in ULS between **2026-11-17 and early December 2026**, well before 2027-02-15.
- Record it in the shared renewals register with a second person.
- The Repeater Etiquette page still names WA7AQH as trustee. Fix that during content migration.

**Who decides / answers:** trustee K7DSR, and the EC.

**Evidence:** https://callook.info/W7GBU/json, https://www.law.cornell.edu/cfr/text/47/97.21, https://www.law.cornell.edu/cfr/text/47/97.19, https://www.law.cornell.edu/cfr/text/47/1.949, https://www.law.cornell.edu/cfr/text/47/1.1102, https://www.law.cornell.edu/cfr/text/47/1.1116 (all read 2026-09-25), `orgs/relationships.md` §2, `history.md` §2.4 and §10 item 8, `content/live/05-w7gbu-call-sign.md`.

---

## D5. Platform (lean WordPress vs static site generator) and who maintains it

**Context**
- **The site is small and nearly static.**
  - It needs about 12 to 15 pages plus a Library.
  - Edits come a handful of times a year, except the net rota.
  - Once the calendar and members move to groups.io, the only dynamic piece is an optional contact form (`site-functionality-analysis.md` §6).
- **Maintenance history is the main risk, not technology** (`history.md` §10 items 1 and 5):
  - One volunteer webmaster per era.
  - 24 of the 26 Joomla articles were written by W7TSC.
  - About 15 extensions were installed, then abandoned or broken.
- **Likely maintainers:**
  - AG7QP runs ag7qp.com on a **no-code visual builder** (Hostinger/Zyro), and is comfortable with that style (session brief).
  - The EC maintained Joomla 3 from 2017.
  - Nobody has been identified as comfortable with git.
- **Verpex Enhance** (`hosting/verpex-enhance.md` §1, §5):
  - WordPress is the best-supported app, with a one-click installer, a toolkit with auto-updates, SSO, staging, and Collaborator access for volunteers.
  - Node is second-class.
  - There is **no Git deploy**.
  - The web server is unknown (probably LiteSpeed), so `.htaccess` security is not guaranteed.
- **The design review** requires one data source for weekly items, and flags custom JS (the Coverage map, scrollytelling) as hard for volunteers to maintain (`design/REVIEW.md`).

**Options**

| | A. Lean WordPress on Enhance | B. SSG (Hugo/Astro/Eleventy) + Git CMS + CI rsync | C. Stay on a hosted builder (like ag7qp.com) | D. Joomla 5/6 |
|---|---|---|---|---|
| Non-developer editing | **Best** fit for a visual-builder user (block editor) | Weakest: editors need GitHub accounts and a Git CMS; someone must keep CI and the deploy key alive | Easiest for AG7QP | Medium; redesigned admin |
| Security / patching | Moderate: auto-updates, 4 plugins or fewer, 2FA, off-host backups | **Lowest** | Vendor-managed | Highest effort |
| Fit on Verpex | Best-supported | Best (plain files) | Not on Verpex; abandons the hosting decision | OK |
| Redirects, files, forms | Plugin or `.htaccess`; media library; one form + SMTP plugin | Config or stub pages; a ~50-line PHP handler | **Not researched** (redirect and file control unknown) | Extensions again |
| Hand-off risk | **Lowest** | High | Medium (single-vendor lock-in) | Medium |

**Recommendation: A, lean WordPress on Verpex Enhance.** This matches `site-functionality-analysis.md` §7.2 and `hosting/verpex-enhance.md` §5.7. Its configuration:
- a maintained block theme, with no page builder and no commercial theme
- **4 plugins or fewer**: a form, SMTP, redirects, and an optional ICS display
- auto-updates on
- Collaborator access for editors
- off-host backups
- no site member accounts (D8)

**Conditions attached to the recommendation:**
- **Name at least two editors** (INFERENCE: AG7QP plus one other) and one technical admin for the Verpex account and updates.
- Write a one-page runbook.
- Implement the weekly data (net control, events) as **one editable source** (D9), not hand edits on several pages.
- **Choose B instead only if** two committed maintainers are comfortable with GitHub.

**Who decides / answers:** the EC and AG7QP (as likely editors), with the project owner. AG7QP should be asked directly whether the WordPress block editor is acceptable to him.

**Evidence:** `site-functionality-analysis.md` §6-§7, `hosting/verpex-enhance.md` §5.1-§5.8, `history.md` §10, `design/REVIEW.md` ("Weekly data needs one source").

---

## D6. Official name, logo, seal and ARRL emblem permissions

**Context**
- Names in use: "ARES/RACES" (the site title), "ARES-ACS" (2026 usage and the seal) and "ARES/ACS/RACES" (the 2023 MOU).
- **ACS expansions in circulation:**
  - "System": ag7qp.com, the ACS Task Book.
  - "Systems": the county page title.
  - "Services": the county application.
  - "Service" and "System": the 2021 CEMP, which uses both.
  - The group's seal says "SPOKANE AUXILIARY COMMUNICATIONS" (`organization.md` §1).
- The earlier rename was done by search-and-replace and left artifacts such as "ACS Officer ... by the DES" (`history.md` §3, §10 item 10).
- **Marks:**
  - The group's own ARES-ACS seal exists only as a 973 KB PNG; there is no vector.
  - The county mark `SCDEM_Logo.png` is a 2400 px PNG.
  - ARES® is an ARRL mark.
  - The design review requires **written permission for the county seal and the ARRL emblem**, and no government-banner styling (`site-functionality-analysis.md` §5, `design/REVIEW.md`, `design/content-brief.md` §11).

**Options**
1. "Spokane County ARES-ACS", with ACS expanded as SCEM prefers.
2. "Spokane County ARES/ACS/RACES", which keeps the RACES term the FCC rule uses.
3. "Spokane County ARES & ACS".

**Recommendation: option 1, "Spokane County ARES-ACS".**
- It matches current usage, groups.io and the seal. Explain RACES once, as history and legal context.
- **Ask SCEM which ACS expansion it wants**, and use it everywhere.
- Get **written permission** from SCEM for any county mark, and ARRL guidance, through the SM/SEC, for the ARES emblem.
- Make the group's own seal the primary mark, and find or redraw a vector original.
- Rewrite the affected pages rather than substituting words.

**Who decides / answers:** the EC with SCEM (Communications Specialist or Deputy Director); ARRL through SM KA7LJQ or SEC AG7QP for emblem use.

**Evidence:** `organization.md` §1, `orgs/relationships.md` Contradictions 1-2, `history.md` §3, `sources/government/spokane-county-CEMP-2021-03.txt` (lines 1581, 2568, 3558), `design/content-brief.md` §1.

---

## D7. The ag7qp.com interim content: migrate, link, redirect

**Context**
- ag7qp.com is **newer than Joomla** on schedules, preambles, the application, the task book, orientation and Winlink.
- Joomla is still the only source for the staff list, W7GBU, the Plan, etiquette, SKYWARN and the MOU.
- The triage in `content/interim-ag7qp/README.md` is:
  - **MOVE:** the Spokane page, the 3 preambles, the application, the ACS task book, orientation.
  - **PARTIAL:** `ares-acs`, `home`, `contact`, the nets list, `emcomm-groups`, `winlink`, `general-information`.
  - **LINK:** Section pages such as SOP 1b, the ARES task-book guide, go-kits, ICS-213, EWA Section and licensing.
  - **SKIP:** hobby content.
- **Stale or inconsistent items not to copy:**
  - The simplex script sends people to the dead contact form and roster.
  - `emcomm-groups` links the hacked site.
  - Two versions of the application are linked.
  - The SM callsign appears as both KJ7LJQ and KA7LJQ.
  - The name appears as both "Croskey" and "Croskrey".
  - The home page links the SDXA meeting to kbara.org.
  - The October exercise is "TBD" on one page and "Oct 24" on another.
  - Class and CERT dates are past.
  - The ICS-309 link returns 404.
  - Alert Spokane is described as "Code Red" (`content/interim-ag7qp/README.md`, `documents-inventory.md` §2 item 5).

**Options**
1. Migrate the MOVE and PARTIAL material to spokares.org and link the Section pages. At launch, put a notice and link on the temporary page, then remove the Spokane duplicates from ag7qp. **INFERENCE:** a server-side 301 from Zyro may not be possible; not researched.
2. Keep ag7qp.com as Spokane's permanent home and point spokares.org at it. This is a fallback if D5 stalls.
3. Run both in parallel. Not recommended: it creates two sources of truth again (`history.md` §10 item 9).

**Recommendation: option 1**, following the README rule of thumb:
- Anything *about* the Spokane group, or anything it *operates* (nets, meetings, scripts, forms, task book, orientation), moves to spokares.org.
- The SEC's teaching pages stay on ag7qp.com, with one maintainer.
- Fix the stale items during migration.
- Ask AG7QP to update `emcomm-groups`, and ask other sites to update their links (Bonner County, wastateares.org, SRG) (`orgs/relationships.md` §18, §22).

**Who decides / answers:** AG7QP (owner of ag7qp.com; also confirms permission to copy) and the EC.

**Evidence:** `content/interim-ag7qp/README.md`, `orgs/relationships.md` §6, `documents-inventory.md` §5.10.

---

## D8. Members-only area on the site, or groups.io

**Context**
- No page, menu item or download in the crawl or in Wayback was marked registered-only. The crawl was anonymous, so this can't be fully ruled out without a DB check (`site-functionality-analysis.md` §3.3).
- groups.io has been the member hub since at least **2018-03**: 107 members, a calendar, files (the ACSFOG) and 7 subgroups (`history.md` §7, `orgs/relationships.md` §1).
- **Hosting constraints:**
  - The Enhance panel has no directory protection.
  - `.htaccess` protection depends on the unknown web server.
  - "Member-only content must be enforced by the application."
  - AUP 3.5 requires files to be reachable via the domain (`hosting/verpex-enhance.md` §4.2, §4.4).
- **PII history:** a public member list (2015), a public roster (2009), applicant PII stored by online forms (2019 and 2024), and a public roster PDF on ag7qp today (`history.md` §10 item 7).

**Options**
1. **groups.io Files/Wiki** for the roster, ACSFOG, contact lists, SHARES, hospital-channel details and sensitive decks. The site has no accounts.
2. WordPress logins plus a restriction plugin.
3. An SSG plus a small PHP gate.

**Recommendation: option 1.**
- Drop site accounts, which removes the only reason for authentication and the only PII store on the site.
- The public site can link "Members: groups.io".
- If a member portal is wanted later (the Ops Board concept), make it a **link hub** pointing into groups.io, not a login.
- **groups.io facts (anonymous check, 2026-09-25; `organization.md` §12):** subscriptions need moderator approval; the archive and the Wiki are members-only (so a Wiki exists); Files and Wiki redirect anonymous visitors to the login page. The owner is reachable at the role address `main+owner@SpokaneARES-ACS.groups.io`, but who it is remains unknown.
- **Still to verify:** the plan tier, and who owns and moderates the group.

**Who decides / answers:** the EC and the groups.io owner (unknown).

**Evidence:** `site-functionality-analysis.md` §3.3, §6 (D1, D5), §7.3; `hosting/verpex-enhance.md` §4.2; `design/REVIEW.md` (Ops Board, moved to /members).

---

## D9. Calendar and net-rota source of truth

**Context**
- Three calendars exist today:
  - **JCal Pro on Joomla**, which only still carries the NCS rota. Three of its five Sep 2026 entries show 22:00, which is wrong.
  - **groups.io**, the only calendar in the Joomla menu.
  - **Hand-typed lists on ag7qp.com**, the most current (rota to Oct 27, events to Oct 24, Winlink assignments to Dec 22).

  Sources: `site-functionality-analysis.md` §3.1; `content/interim-ag7qp/spokane-county-ares-acs.md`.
- Every design concept needs one feed for net control, simplex weeks and "coming up" (`design/REVIEW.md`).
- Whether the groups.io calendar and its ICS feed are publicly viewable is **still unverified**. On 2026-09-25 the `/g/main/calendar` page returned 200 to an anonymous visitor, but its server HTML holds no events (they load by script), and a guessed `/g/main/ics/feed.ics` returned 404 (`organization.md` §12). **Ask the group owner** (role address `main+owner@SpokaneARES-ACS.groups.io`) whether the calendar is public and for the public ICS URL, **before** D9 is settled.

**Options**
1. **groups.io calendar** as the single source for events and the rota. The site renders standing facts (net and meeting pattern) as static text, plus an "upcoming" list from the groups.io ICS feed, via one ICS plugin or at build time.
2. A Google Calendar owned by a role account (for example `calendar@spokares.org`), edited by the Net Manager and embedded.
3. A WordPress events plugin with recurrence.
4. A single data file (JSON/YAML) edited monthly.

**Recommendation: option 1 if its feed is public; otherwise option 2.**
- Either way, **one place** is edited by the Net Manager, and every page reads from it.
- Enter events in America/Los_Angeles time and check the 2000 net time; never publish 22:00.
- Do not migrate the 2017-2022 JCal history (an optional archive can come from the DB, D1).

**Who decides / answers:** AG7QP (the Net Manager, who keeps the rota today; confirm who entered the JCal entries), and the groups.io owner.

**Evidence:** `site-functionality-analysis.md` §3.1, §7.3; `data/calendar-events.json`; `design/REVIEW.md` ("Where the judges agree").

---

## D10. Document library: hosting vs the Verpex AUP

**Context**
- 76 documents were inventoried (`documents-inventory.md` §3):

  | Action | Count |
  |---|---:|
  | Migrate from ag7qp | 5 |
  | Link to the authoritative source | 23 |
  | Recover, then publish | 16 |
  | Recover, then review | 5 |
  | Retire | 26 |
  | Members-only | 1 |

- File sizes and formats are unknown until recovery. One MP3 (the Traffic Handler) is the likely outlier.
- **Verpex AUP:**
  - Download sites and repositories are banned.
  - "Software downloads ... should not exceed 1GB".
  - Multimedia is capped at 10 GB.
  - Every file must be visible via the domain (AUP 3.1, 3.5).

  **INFERENCE:** a small club documents library fits (`hosting/verpex-enhance.md` §4.4).
- Rights and sensitivity:
  - Presenter permissions are needed for the recovered decks: N7GCO, K7PKT, AD7DD, WA7AQH and N7LL.
  - Six items need a rights or sensitivity call, among them the 800 MHz county radio deck, the WA Guard Cascadia briefing and the Lilac parade briefings (`documents-inventory.md` §3, §6 Step 4, §7).
- Downloads were **never** actually gated; all 66 gate flags are off (`documents-inventory.md` §2 item 6).

**Options**
1. Public PDFs in the WordPress media library, plus a curated Library page organised by task. Audio and video on archive.org or YouTube. Sensitive items on groups.io.
2. Everything on groups.io; the site only links.
3. An external file host (Drive or archive.org) for everything.

**Recommendation: option 1.**
- Measure the total after recovery (expected under 500 MB).
- Get **Verpex's written OK** on AUP 3.1 and 3.5 (Verpex Q4).
- ICS 201/205/213/214 become **links to FEMA** (205 and 214 are now v3.1).
- No e-mail gate.
- Every entry gets a date, version, owner and last-reviewed date.
- Use ISO-dated file names.

**Who decides / answers:** the EC (content and sensitivity), presenters (permission), SCEM (the 800 MHz deck), and Verpex (AUP).

**Evidence:** `documents-inventory.md` (all sections), `hosting/verpex-enhance.md` §4.4, §6 Q12.

---

## D11. Contact path

**Context**
- The ALFContact form returns HTTP 500 and is linked from 5 articles. The 2019 form routed messages by role (Feedback, EC, AECs) (`site-functionality-analysis.md` §1d, §3.4).
- The rule is to publish no personal e-mails (`design/content-brief.md` §11).
- `mail()` on Enhance is unverified, so forms need authenticated SMTP (`hosting/verpex-enhance.md` §4.3).

**Options:** role forwarders with mailto links only; one form plugin with an SMTP plugin; a third-party form service.

**Recommendation:** launch with **role forwarders**, for example `ec@`, `join@` or `info@`, and `webmaster@`, forwarded to the current role holders so a handover means changing one forwarder. Add at most one form, with spam protection, if volunteers want one. **Do not store submissions on the web host.**

**Who decides / answers:** the EC (the alias list and recipients).

**Evidence:** `site-functionality-analysis.md` §3.4, §6 (M3), `hosting/verpex-enhance.md` §4.3.

---

## D12. Design direction

**Context:** 12 homepage concepts were scored by 4 judges (`design/REVIEW.md`). The top three are within 0.4 points of each other:

| Rank | Concept | Composite | Strength | Weakness |
|---|---|---:|---|---|
| 1 | **Field Guide (09)** | 8.21 | Fig. 1 (handheld → W7GBU repeater → county EOC), the "Is this for me?" quiz, "Licensing in plain English" | Soft, edtech tone; no agency door |
| 2 | **When the Lines Go Down (12)** | 7.92 | The ICS-213 message-path story; the strongest "why it matters" | The join path is 6,000 px down; brittle to edit; needs a PIO review |
| 3 | **Open Door (04)** | 7.83 | The "This week" card, "Three ways to visit", Atkinson Hyperlegible type; the UX judge's top pick | The hero is about socialising, not capability; many "verify" chips; a public "Condition Green" badge |

**Options**
1. **The recommended hybrid:**
   - Open Door's skeleton and "This week" card.
   - Field Guide's Fig. 1 and quiz, in a more sober register (no Caveat handwriting; an agency door added).
   - A short Lines Down section linking to a full "How it works" page.
2. **The agency-first alternative:** County Standard's structure, plus the Lines Down section and a light static Coverage map.

**Recommendation: option 1.** Graft in:
- From County Standard: the ARES vs ACS table, the agency door ("Contact the emergency coordinator", Agreements), a sponsor line naming SCEM (without government-banner styling), and an audience-organised footer.
- From Hi-Vis: "Here's what it takes" and "Pick your post", in sentence case.
- From Plain Signal: the 0-3 join path and the `?from=` link.
- From Tuesday 2000: the self-computing next-net line and "Copy radio settings".
- From The Relay: the tap-to-define glossary, and the headline "From Bloomsday to blackouts, we keep Spokane County connected."
- **Ops Board goes to /members** as a link hub (D8).
- **The W7GBU story goes to About.**

**Must fix before any direction ships:**
- Remove or relabel the public "Condition Green" status.
- Feed all weekly data from one source (D9).
- Clear every "verify", "TBD" and placeholder chip.
- Get permission for the county seal and ARRL emblem (D6).
- Commission a photo shoot, with releases and an OPSEC/HIPAA review for hospital and EOC shots.
- Get a PIO or SCEM review of the scenario copy.

**Platform fit (INFERENCE):** on WordPress (D5), build the "This week" card and next-net line as one reusable block or pattern fed by the calendar (D9). Avoid heavy custom JS such as the Coverage map (about 580 lines) and full scrollytelling.

**Who decides / answers:** the EC and leadership. The PIO position is open, so someone must be named for copy review. SCEM reviews agency-facing claims.

**Evidence:** `design/REVIEW.md`, `design/concept-specs.md`, `design/concepts/index.html`, `design/content-brief.md` §10-§11.

---

## D13. Facts to confirm before publishing (content blockers)

**Machine-readable version:** `data/facts.yaml` holds every fact below (and the settled ones) with its value, source, status (`verified`, `multi-source`, `single-source`, `conflict`, `unknown`), owner and last-verified date, plus the D13 row it answers. Update the YAML when an answer comes in; the site should render from it (D9, `history.md` §10 item 2).

| # | Fact | Conflict / gap | Ask | Evidence |
|---|---|---|---|---|
| 1 | Current leadership roster (EC/RO, 4 AECs, PIO) | Last edited 2026-03-24; ag7qp confirms only the SM, SEC and EC | EC | `organization.md` §4 |
| 2 | Public contact after launch, and **where applications go** | W7TSC (staff page) vs AG7QP (interim page). The 2024-11-07 application sends completed forms (with address, phones and emergency contact) to a personal e-mail; the 2026 scripts send contact changes to a personal e-mail or the dead contact form | EC + AG7QP | `orgs/relationships.md` Contradiction 5; `content/interim-ag7qp/docs/*.md` |
| 3 | AG7QP's local role | SEC, and also listed as Spokane AEC Net Manager | AG7QP | Contradiction 6 |
| 4 | Third Thursday start time | 6 PM rests on **one** 2026 source (ag7qp.com). 7 PM: Meeting Location page (edited 2024-08-10), Welcome Letter, JCal 2018-20 and a Spokesman-Review listing for **2025-09-18** | EC / AG7QP | `content/interim-ag7qp/README.md`; `orgs/relationships.md` Contradiction 3 |
| 5 | Simplex net frequency; does the 5th-Tuesday simplex net still run? | Not stated in any 2026 script. 147.48 MHz appears only in the old Plan (a lead, not a fact). JCal 1613 marks 2026-09-29 "NZ2S - Simplex", but the ag7qp rota lists plain "NZ2S". Next 5th Tuesday: 2026-12-29 | AG7QP | `docs/net-preamble-simplex-2026-03-04.md`; `data/calendar-events.json` |
| 6 | W7GBU-10 frequency and site; its future during the 2026-28 911 split | 145.090 (CCB) vs 145.01 | NV2Z | Contradiction 4 |
| 7 | Confirm the **never-publish rule** below with SCEM (Hospital Net channel, SHARES specifics, county and hospital channels) | Default until SCEM answers: **do not publish** | EC / K7MHG, then SCEM | SCEM NDA in `content/interim-ag7qp/docs/ares-membership-application-2024-11-07.md`; `orgs/relationships.md` §14, §19 |
| 8 | Does the ARES/RACES Plan 7.13 get published as historical, or replaced by a current SCEM ESF-2 attachment or ACS SOP? | The live text dates from about 2013 | EC + SCEM | `history.md` §8 |
| 9 | Is a 146.880 alternate-repeater preamble still needed? | The MOU names 146.88; the 2026 script set has none | AG7QP | `documents-inventory.md` §7 Q3 |
| 10 | Is there still no meeting in December? | Seen only for 2019 | EC | `design/content-brief.md` §4 |
| 11 | Does ACS serve City of Spokane incidents since 2020? | Unknown | SCEM | `orgs/relationships.md` §7 |
| 12 | Does a current written RACES Officer appointment exist? | Not found | EC / SCEM | `orgs/relationships.md` unknowns |
| 13 | Spellings: NV2Z's surname; the SM's callsign | "Croskrey"/"Croskey"; KA7LJQ/KJ7LJQ | NV2Z / arrl.org | `content/interim-ag7qp/README.md` |
| 14 | Is the ACS Task Book still v2024-08-09? Update "Code Red" to Regroup | Alert Spokane moved in April 2026 | AG7QP / SCEM | Contradiction 12 |
| 15 | Who owns the 147.30 and 443.40 hardware and the Brownes Mtn site | Unknown | Trustee / SCEM | `orgs/relationships.md` §2 |
| 16 | Is the 1st-Tuesday 1730 staff meeting open to the public? | ag7qp `home`/`contact`: "ARES-ACS Meetings (Open to the public)"; content brief §4 and `organization.md` §9: internal. Until answered, keep it off public pages | EC | `content/interim-ag7qp/home.md`, `contact.md` |
| 17 | Is New Member Orientation a standing 4th-Tuesday 1800 session? | Sources say "usually"; only Sep 2024-Jan 2025 sessions are dated; the Sep-Oct 2026 ag7qp list has no Oct 27 session. Until answered, publish "held regularly; contact us for the next date" | EC / AG7QP | `content/interim-ag7qp/new-members-orientation.md`, `acs-task-book.md`; `site-functionality-analysis.md` §3.1 |
| 18 | The EC's official county title | "EC & RACES Officer" (staff page, MOU, content brief) vs "Emergency Coordinator and ACS Officer" (2026-03-04 scripts) vs "EC/RO" (ag7qp); county page 1813 says RACES "is now incorporated into ACS". Settle with #12 | EC / SCEM | `organization.md` §4.1, §14 |
| 19 | First-visit instructions for 1121 W Gardner Ave: entrance, check-in or escort after hours, parking, accessibility | Nothing in the corpus; the site will invite the public there ("Three ways to visit") | EC / SCEM | `design/REVIEW.md` (Open Door) |
| 20 | Is the groups.io calendar public, and what is its public ICS URL? | Calendar page loads for anonymous users but shows no server-side events; `/g/main/ics/feed.ics` is 404 | groups.io owner (via `main+owner@SpokaneARES-ACS.groups.io`) | D9; `organization.md` §12 |

### Never publish (SCEM FOUO rule; proposed 2026-09-25, SCEM to confirm)

**Source.** The current ARES application (version 2024-11-07) contains an SCEM Emergency Communications Non-Disclosure Agreement. It says information on "Radios and computers in the Communications Room, Trailers and at SCEM stations in the local hospitals ... is For Official Use Only (FOUO)", that FOUO material "includes frequencies and operating procedures, as specified in NCSH 3-3-1", and that it "shall not be released in any manner to the public" (`content/interim-ag7qp/docs/ares-membership-application-2024-11-07.md`).

**Rule for the website, the Library and this research folder's copy:** never publish
1. county, hospital, SCEM, SHARES or public-safety **frequencies, channel numbers or talk-groups**, including the Hospital Net's channel and the 800 MHz system;
2. **operating procedures** taken from SCEM radios, computers, the Comm Room or trailers, and anything from the **ACSFOG** (Spokane ACS Field Operations Guide, members-only on groups.io);
3. **SHARES** station details, the **Region 9 radio cache**, **MNU** configurations and equipment locations, and hospital station locations;
4. the 800 MHz county radio deck (OSD-38) and similar SCEM-sourced decks, unless SCEM clears them in writing (`documents-inventory.md` §7 Q4).

**What stays public:** amateur facts only: the W7GBU repeaters (147.300, 443.400), the IEVHFRA alternate (146.880), the amateur net schedule, the GMRS net once K7DSR agrees, NWS weather radio (162.400) and the Section's HF frequencies (3.985 MHz WSEN), and the *existence* of county equipment and roles described in general terms ("members staff the county Comm Room and mobile units").

**Owner:** the EC asks SCEM to confirm or amend this rule; record the answer and date here. Mirrored in `../design/content-brief.md` §11.

---

## D14. PII clean-up

**Context and items**
1. **The 2026 net roster PDF** (dated 2026-08-04) is publicly linked on ag7qp.com. It was not downloaded; it likely contains member contact data (`content/interim-ag7qp/README.md`, `documents-inventory.md` §6 Step 8).
2. **Permanently archived PII in Wayback:** `spokares.org/Memberlist.pdf` (2015) and `www.spokares.org/Downloads/ARES%20NET%20ROSTER%20FEB09.doc` (2009).
3. **Local copies of those two files** are in this repo, unopened:
   - `wayback/snapshots/spokares.org/Memberlist.pdf`
   - `wayback/snapshots/www.spokares.org/Downloads/ARES_20NET_20ROSTER_20FEB09.doc`

   They are also linked from `content/index.md`.
4. **Unredacted ag7qp copies in this repo** contain personal phone numbers and e-mails. They are byte-identical to the originals (`content/interim-ag7qp/README.md` stale item 12):
   - `sources/government/ag7qp_*.html` (10 files)
   - `external/ag7qp.com.html`
   - `external/ag7qp.com_spokane-county-ares-acs.html`

   The redacted equivalents are in `external/ag7qp/`.
5. **Possible applicant and member PII in the hacked Joomla DB** (D1).
6. The repo's `research/` and `design/` folders are **untracked** in git today (`git status`). Nothing sensitive has been committed yet.
7. **Derived archive Markdown** (`content/archive/*.md`, from the 2004-2015 static pages and the 2009-2015 Plans) held about 60 personal phone numbers and e-mails, a home street address (the 2004 Pactor-gateway host) and a pager number, plus the 2004 Telephone Mobilization Tree (a member list).
8. **Raw layers with personal contacts:** `raw/` (Joomla-cloaked personal e-mails in `raw/by-id/article-11.html`, `article-23.html` and their menu-path duplicates) and `wayback/snapshots/` (the two rosters, plus `TestingSchedule.html`, `Pactor.html`, `Tree.html`, `index.php/about/email-list` and the 2004 staff pages).

**Done on 2026-09-25 (this pass):**
- Items 3, 4 and 8 are **git-ignored** (`/Users/sean/Projects/spokane_ares/.gitignore`: `research/raw/`, `research/wayback/snapshots/`, `research/sources/government/ag7qp_*`, `research/external/ag7qp.com*.html`, plus roster, `Memberlist.pdf` and `.docx` patterns). `git status --ignored` confirms it.
- Item 4: the 12 ag7qp copies were **redacted in place** with the `ag7qp_to_md.py` patterns (`tools/redact_pii.py html`; original sha256 and counts in `external/redaction-manifest.tsv`). The content brief's `AG7QP-x` key now points at `external/ag7qp/`.
- Item 7: `content/archive/*.md` **redacted** (`tools/redact_pii.py archive`: 63 contact details in 16 files, each file's front matter says so); the mobilization tree was replaced by a note. `tools/html_to_md.py` now applies the same redaction, so a re-run stays clean.
- Item 3: the two roster links were removed from `content/index.md` (listed as "withheld", unlinked).
- Also found and redacted: individuals' phones and e-mails in the `.txt` extractions of the COAD minutes (2016, 2017, 2023), the 2021 CEMP and the WA EMD comms annex (29 in all, including one member's personal e-mail). The source PDFs are public county/state publications and were left as published.
- Scratch copies made by earlier phases outside the repo (unredacted ag7qp HTML, `.docx` preamble originals and a roster PDF in the session scratchpad) were deleted.

**Recommendation**
- Ask AG7QP to move the roster to groups.io Files **now**.
- The EC decides whether to ask the Internet Archive to exclude the two archived files. The site owner can request this; confirm the current process.
- Serve **410 Gone** for those two URLs on the new site (`documents-inventory.md` §6 Step 7).
- **Project owner:**
  - ~~Delete or redact the 12 unredacted ag7qp HTML copies~~ (redacted 2026-09-25).
  - ~~Repoint `design/content-brief.md`'s `AG7QP-x` source key~~ (done).
  - Delete the two PII snapshots once the Wayback decision is made (still on disk, git-ignored).
  - ~~Add all of these to `.gitignore`~~ (done). Before the first commit, run `git status --ignored` and check that nothing under `raw/` or `wayback/snapshots/` is staged.
- Settle item 5 as part of D1.

**Who decides / answers:** AG7QP (item 1), the EC (items 2 and 5), the project owner (items 3, 4 and 6).

---

## D15. Sitemap / information architecture: pages, slugs, sources, owners

**Context**
- Three proposals conflict:
  - `site-functionality-analysis.md` §2b: Home, About, Join, Nets & Ops, Calendar, Library, Contact; no accounts.
  - `design/content-brief.md` §10 (as first written): About, What We Do, Join, Calendar, Members, with "some items gated", a Login, a "Status: CONDITION GREEN" strip and a Terms page.
  - `design/REVIEW.md`: the Open Door skeleton with Field Guide's Fig. 1 and quiz, a "How it works" page from When the Lines Go Down, the Ops Board moved to /members, and no public Condition Green.
- D8 drops site accounts; D9 makes the groups.io calendar (or a role-owned Google Calendar) the one calendar source; D10 makes the Library public and ungated; D11 uses role addresses.
- `history.md` §10 items 2 and 4 require an owner and "last reviewed" date on every page, and 301s for known old URLs.

**Recommendation (ours; the EC and editors decide):** five top-level sections plus Contact, 21 pages.

```
[Seal] Spokane County ARES-ACS   About · Nets & meetings · Join · Library · Members   Contact   [Join the team]
Utility line: Tuesday Net · 8:00 PM · W7GBU 147.300 (+) PL 100 · Net control: {from the D9 feed}   (no status badge)
Footer: address · groups.io · Privacy · 3-line disclaimer (volunteer status text reviewed by SCEM) · affiliation marks (with permission, D6)
```

| Slug (proposed) | Page | Purpose | Main source files | Owner (role) | Review |
|---|---|---|---|---|---|
| `/` | Home | Who we are; "This week" card (next net, net control, next meeting); Fig. 1; join path 0-3; quiz; short message-path section; partner line | `content/live/04-*.md`, `design/REVIEW.md`, `data/facts.yaml`, D9 feed | Editors; Net Manager for the weekly card | Card: automatic. Page: quarterly |
| `/about/` | About ARES-ACS | The "two hats" (ARES vs ACS table), mission, RACES as history | `organization.md` §0, §2, §5; `content/live/02-*.md` (mission); `design/content-brief.md` §1 | EC | Yearly, and after D6 |
| `/about/what-we-do/` | What we do | Activities; alert levels **explained** (never a live status); SKYWARN; hospitals in general terms only | `organization.md` §3; `content/live/20-*.md`, `37-*.md`; content brief §3 | EC | Yearly |
| `/about/how-it-works/` | How it works | The ICS-213 message path (Lines Down story) | `design/concepts/12-*`; `design/REVIEW.md` | PIO (open; name a reviewer) + SCEM review | Yearly |
| `/about/leadership/` | Leadership | Role, name, callsign; no personal contacts | `content/live/03-*.md`; `organization.md` §4; `data/facts.yaml` | EC | Quarterly and on any change |
| `/about/partners/` | Who we serve and agreements | Served agencies; IEVHFRA MOU; national ARRL agreements | `organization.md` §6-§7; `content/live/68-*.md` | EC | Yearly (the MOU asks for a yearly contact review) |
| `/about/w7gbu/` | The W7GBU story | Dean Bula; the club license; repeaters in brief | `content/live/05-*.md`; `organization.md` §8 | Trustee | Yearly and at renewal (D4) |
| `/about/history/` | History and archive | Eras, Field Day, the Plan (as a dated historical PDF if D13 #8 allows), the Radio Amateur Code | `history.md`; `content/live/02-*.md`, `06-*.md`; `content/archive/` | Editors | Yearly |
| `/nets/` | Nets & meetings | Weekly net facts; Winlink nights; 5th-Tuesday simplex (no frequency until D13 #5); meetings; `#where-we-meet` with the text address and first-visit notes (D13 #19); upcoming list from the D9 feed; link to the groups.io calendar | `organization.md` §9; `content/interim-ag7qp/docs/net-preamble-*.md`; `data/facts.yaml` | Net Manager | Monthly (rota via the feed) |
| `/nets/net-control/` | Net control and preamble | A public version of the current script and the NCS guide (no roster, no personal addresses) | `docs/net-preamble-2026-03-04.md`; `content/live/08-*.md`, `09-*.md` | Net Manager | At each script revision |
| `/nets/repeaters/` | Repeaters and etiquette | 147.300, 443.400, 146.880 alternate; etiquette (trustee corrected) | `content/live/07-*.md`; `organization.md` §8 | Trustee | Yearly |
| `/join/` | Join | The 0-3 path (`?from=` link), the ARES application, groups.io ("apply; a moderator approves"), FAQ (`#faq`), `#apply`, `#get-connected` | `design/content-brief.md` §5; `organization.md` §10; D11 | EC | Quarterly |
| `/join/requirements/` | Requirements and training | ACS Task Book checklist (New Member → RADO); ARRL levels; FEMA course links | `content/interim-ag7qp/acs-task-book.md`; `organization.md` §10-§11 | EC | Yearly and when the Task Book changes |
| `/join/get-licensed/` | Get licensed | Classes and exam options as links (ARRL exam search, HamStudy), not copied schedules | `content/live/11-*.md`, `42-*.md`; `content/interim-ag7qp/licenses.md`, `license-exams.md` | Training AEC | Twice a year |
| `/join/orientation/` | New Member Orientation | What happens; "held regularly; contact us for the next date" until D13 #17 | `content/interim-ag7qp/new-members-orientation.md` | EC | Quarterly |
| `/library/` | Library | Public documents only, with date, version, owner and last-reviewed date; sections `#forms` (links to FEMA/ARRL), `#net-scripts`, `#presentations`, `#membership`, `#digital`, `#general`, `#plans` | `documents-inventory.md` §3-§6; D10 | Editors + EC | Twice a year |
| `/library/go-kits/` | Go-kits and equipment | Refreshed equipment lists | `content/live/21-*.md` | Logistics AEC | Yearly |
| `/library/resources/` | Digital, Winlink and RFI resources | Pruned link lists (`#digital`, `#rfi`) | `content/live/24-*.md`, `62-*.md`; `site-functionality-analysis.md` §1a #17-#20, #26 | Winlink coordinator | Yearly (link check) |
| `/members/` | Members | A **link hub** into groups.io (Files, calendar, Winlink assignments, ACSFOG, roster). No login and nothing sensitive on the page (D8) | `design/REVIEW.md` (Ops Board); D8 | Editors | Quarterly |
| `/contact/` | Contact | Role addresses (D11), "Contact the emergency coordinator" agency door, meeting place | D11; D13 #2 | EC | Quarterly |
| `/privacy/` | Privacy and disclaimer | Short and accurate: no accounts, no tracking beyond basic logs; the disclaimer replaces the old Terms page | `site-functionality-analysis.md` §1a #31-#32 | Webmaster | Yearly |

**Dropped from the earlier proposals:** the Login and every "gated" item (D8); the "Status: CONDITION GREEN" strip and any live status badge (`design/REVIEW.md`); the Terms page; Calendar as its own top-level page (it lives in `/nets/`, fed by D9); "What We Do" as a top-level item (now under About); the JCal archive, forum, gallery, the 1994 ICS module and Under Construction. The net roster is never a public page or file.

**Redirect map:** `redirects.csv` (built by `tools/build_redirects.py` from `wayback/cdx-spokares.txt`, `pages-index.json` and the article hit counters) maps all 1,188 known old paths to these slugs: 313 × 301, 469 × 410 (the two member lists, the 2004 phone tree, individual staff pages, past JCal events, news posts and dead features), 405 with no rule (old assets and bot probes; the new server's 404), and the home page. The targets are **proposed** until this D-item is agreed; change the tables at the top of the script and re-run it.

**Inbound links to update after launch** (ask each owner; `orgs/relationships.md` §11, §18, §22): the groups.io group information (links www.spokares.org; keep it, but check it lands on the new home page), ag7qp.com `emcomm-groups` (links the hacked site), Bonner County (a dead Wix URL), wastateares.org local contacts, the SRG links, pages still linking `StaffList.html` (redirected to `/about/leadership/`), and county page 1813 (ask SCEM to restore its link).

**Who decides / answers:** the EC with the editors named under D5; SCEM reviews `/about/how-it-works/`, the disclaimer and any agency-facing text.

**Evidence:** `site-functionality-analysis.md` §1a-§2b; `design/content-brief.md` §10; `design/REVIEW.md` (Recommendation, "Public status indicators are a liability"); D8-D11; `history.md` §10.

---

## Questions for Verpex (ask before committing; [P] = can be checked in our own panel first)

Condensed from `hosting/verpex-enhance.md` §5.8 and §6, most blocking first.

1. **Package allowances** on the reseller subscription [P]:
   - the software installer, with WordPress allowed
   - SSH/SFTP
   - the php.ini editor
   - e-mail accounts and the e-mail role
   - the DNS zone editor
   - manual and self-restore backups
   - staging and cloning
2. **Web server kind** in "US - Central": LiteSpeed Enterprise, OpenLiteSpeed, Apache or Nginx? Will it change without notice? This decides `.htaccess` redirects, file protection, wp-admin lockdown and caching [P].
3. **E-mail:**
   - the hourly limits for SMTP-authenticated mail and for site-generated mail
   - whether `mail()` is enabled, and which envelope sender it uses
   - whether outbound SMTP on 587/465 to a third-party relay is allowed
4. **AUP 3.1 / 3.5 sign-off**, in writing: a public library of PDFs, net scripts and presentations under about 500 MB (under 1 GB total). Is any login-gated document area acceptable if we ever need one?
5. **Backups:**
   - frequency, retention and off-server location
   - whether self-restore is enabled
   - whether an S3 target can be added
   - how long we have to export data if an account lapses
   - whether you will export the DNS zone on request
6. **IP changes:**
   - Do Enhance servers get the same "change the IP" DDoS mitigation?
   - Is there advance notice, or a per-server status subscription?
   - Is the new IP written into Enhance-hosted zones?
7. **Per-site limits** for sub-packages: RAM, vCPU, IO, nproc, inodes.
8. **Enhance version** you run, and how quickly you upgrade (features from 12.18 to 12.25 matter) [P for the version].
9. What "30 Hosting Accounts" means on E30 (customers or websites), and whether staging has its own quota.
10. Is there a **charity or non-profit** programme that applies to the Enhance reseller product?
11. Which city and datacenter "US - Central" is. Is a US-West location planned?
12. **(New, from D1/D3)** To migrate only mailboxes from a compromised InMotion cPanel account, do you recommend IMAP sync over the Enhance cPanel importer? Can the importer bring e-mail and DNS **without** web files?

---

## Questions for AG7QP (Frank Hutchison, EWA SEC; interim host)

1. May we **copy** the Spokane content from ag7qp.com to the new site? Do you agree with the MOVE / PARTIAL / LINK triage in `content/interim-ag7qp/README.md`?
2. Will you be **an editor** of the new site? Is the **WordPress block editor** acceptable compared with the Hostinger builder you use now (D5)? Who else could be the second editor?
3. Would you keep the **net-control rota and events in the groups.io calendar** (D9)? Who entered the Sept 2026 JCal rota (three entries show 22:00)? Do you still have Joomla admin access?
4. Please **move the public roster PDF** (2026-08-04) to groups.io now (D14).
5. After launch, should the public contact for the group be you or the EC (W7TSC)? Are you still Spokane's AEC Net Manager as well as SEC?
6. **Confirm facts** (D13):
   - the Third Thursday start time (6 PM?)
   - the simplex frequency
   - whether the Hospital Net channel may be public
   - whether a 146.880 alternate-repeater preamble is still needed
   - whether orientation is still usually on the 4th Tuesday
7. Who will own the **masters** from now on (preambles, application, ACS Task Book)? Could we get clean copies with personal e-mails removed? Which application version is current (2024-11-07 or 2024-09-15)?
8. At launch, would you:
   - add a notice on the temporary page
   - update `emcomm-groups` to the new site
   - fix the stale items (KJ7LJQ, the SDXA link going to kbara.org, the dead ICS-309 link, the "Code Red" wording, the past class and CERT dates)
   - fix the **net-script defects** (`organization.md` §9): the simplex script hard-codes "Net Control Station K7DSR" three times; no script gives the simplex frequency; the simplex script points to a roster "on the Spokane County ARES website", the dead contact form and a report "available on our website"; the weekly and GMRS scripts send contact changes and net reports to personal e-mails (replace with a role address, D11); "meeting every Tuesday at 2000 hours local time on." is missing the repeater; an unclosed "[the Spokane County ACS Repeater."; "locale time"; overlapping GMRS number ranges (100-300, 300-500 ...); and the weekly script says members are called "in groups by call sign suffix" but lists no groups
   - update the application so completed forms go to a role address rather than a personal e-mail (D11, D13 #2)
9. Where is the authoritative source for the **"I am Safe"** file set? Does the Section have an official web presence besides ag7qp.com?
10. Do you know who holds the **InMotion and GoDaddy** logins, and what happened in the hack (date, what was affected)?

## Questions for W7TSC (Asa Jay Laughton, EC/RO and website admin)

1. **Access (D1, D2):**
   - Who holds the **InMotion cPanel** and **GoDaddy** credentials?
   - When does the hosting term end?
   - Can you take a **cPanel full backup** this week, and ask InMotion about pre-compromise backups?
2. **The hack:**
   - When was it discovered, what was affected, and what has been done?
   - Have the passwords been rotated?
   - What changed on the domain record on 2026-08-12?
3. **The DB:**
   - Do the 2019 BreezingForms or 2024 RSForm **submission tables still exist**?
   - Was anything on the site **login-restricted**?
   - What mailboxes and forwarders exist on spokares.org?
4. **Roles:**
   - Will you remain EC/RO and website admin?
   - Is the leadership roster (staff page, 2026-03-24) current?
   - Who will fill the **PIO** role?
   - Is there a **written RACES Officer appointment**?
5. **Naming and SCEM (D6):**
   - Which public name should the site use?
   - Who is SCEM's current ACS contact (the Communications Specialist)?
   - Will you request SCEM approval, seal permission and its preferred ACS expansion?
   - Will you ask SCEM to restore its link from county page 1813?
6. **The Plan:** publish 7.13 as a historical PDF, retire it, or replace it with a current SCEM document?
7. **Library:**
   - Permission to republish presentations (N7GCO, K7PKT, AD7DD, WA7AQH, N7LL).
   - Whether the 800 MHz, WA Guard Cascadia and Lilac parade decks can be public.
   - Do the Traffic Handler PDF and MP3 still exist?
8. **Governance:**
   - Does the group have bylaws, officers, a bank account, or any legal entity besides the Support Group?
   - Does IEVHFRA still hold funds for ARES?
   - The MOU calls for an annual POC review: is it due, and will IEVHFRA post it?

## Questions for others

- **SCEM** (via the EC):
  - Site approval and any required disclaimers.
  - Permission to use the county mark.
  - Preferred ACS expansion.
  - A named contact.
  - A review of the "volunteer status / RCW 38.52" text.
  - Whether ACS serves City of Spokane incidents.
  - Who owns the 147.30 repeater hardware and the comm equipment.
  - Whether the CEMP or ESF-2 has been updated since March 2021.
- **K7DSR** (trustee):
  - The W7GBU renewal timing and whether his FCC login works (D4).
  - Repeater ownership and the site agreement.
  - Whether 443.40 is in ARES use.
  - Whether the WRCY281 GMRS repeater may be listed publicly.
- **NV2Z:**
  - W7GBU-10 frequency, site and host.
  - Surname spelling.
  - The Bloomsday volunteer contact route.
- **groups.io owner** (identity unknown; write to the role address `main+owner@SpokaneARES-ACS.groups.io`):
  - Plan tier (Files/Wiki). A Wiki exists and is members-only (2026-09-25).
  - Whether the calendar is public, and the **public ICS URL** if there is one (D9, D13 #20).
  - Who owns and moderates the group, and who approves new subscriptions.
  - Whether the group information link (www.spokares.org) should stay as is after launch.
