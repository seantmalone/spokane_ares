# Verpex Enhance reseller hosting: capabilities and constraints for the spokares.org rebuild

- **Researched:** 2026-09-25, from public sources only. No credential files were read and no live control-panel API was called.
- **Verified:** 2026-09-25 by three independent skeptics plus a re-check of the one claim that failed; see the Verification section at the end.
- **Target product:** Verpex "Enhance Reseller Hosting" (https://verpex.com/reseller-hosting-enhance), which runs on the Enhance control panel. The current Enhance release is 12.25.12 (21 Sep 2026), and the public orchd API spec is the same version.
- **Inputs:** five raw notes files in this folder (`notes-verpex-product.md`, `notes-enhance-docs.md`, `notes-openapi-spec.md`, `notes-changelog-community.md`, `notes-independent.md`) plus a local copy of the public spec (`_enhance-oas3-api.yaml`).
- **Tags used below:**
  - **[C]** CONFIRMED: seen in the cited source.
  - **[I]** INFERENCE: my reasoning, not stated by the source.
  - **[OLD]** the source is more than about 12 months old (before 2025-09-25).
  - **[STALE]** the source is contradicted by a newer, more authoritative one.
- **Citations:** source keys in square brackets (for example [RN], [SPEC]) are defined in section 7. Every other source is cited by full URL.
- **Precedence when sources disagree:** OpenAPI spec > Enhance docs and release notes > Verpex pages > community forum > third-party reviews. Within a tier, the newer dated source wins. The dated Enhance release notes beat undated docs pages. Each decision is recorded in section 3.2.

---

## 1. Summary

- **What it is.** A white-label reseller account on Verpex's hosted Enhance cluster, which runs on World Host Group infrastructure.
  - We control our own packages, customers and websites through a branded panel [C, VPX] [D-RES].
  - We get no server or root access [C, D-RES].
  - Verpex chooses the web server kind, the database engine, the installed PHP builds, the per-site CPU/RAM limits, the backup schedule and the email rate limits. We cannot change any of them [C, SPEC; D-RES].
- **What a site gets.** Each website is an isolated Linux container [C, FEAT] with:
  - PHP 5.6 to 8.5 (default 8.3) [C, RN]
  - MySQL 8.4 or MariaDB (PostgreSQL 16 optional) [C, RN] [VKB-FAQ]
  - a cron editor, and SSH, SFTP and FTP [C, D-SSH]
  - automatic Let's Encrypt certificates [C]
  - email (Postfix/Dovecot/rspamd with Roundcube) [C, D-MAIL]
  - DNS on PowerDNS [C, FEAT]
  - incremental backups with self-restore [C]

  In other words, it is modern PHP/MySQL shared hosting.
- **It is not an app platform.**
  - Node.js exists but is second-class: a package toggle, primary domain only, no env-var UI, no restart button, and no redeploy after a restore [C, D-NODE].
  - Python, Ruby and Docker are not available [C, RM] [SPEC].
  - There is no native Git deploy as of 12.25.12 [C, RM] [RN] [SPEC].
  - Build locally or in CI, and ship only the finished files.
- **It is not a file host, stream host or bot host.**
  - Verpex's AUP bans download sites and repositories, file storage, audio and live streaming, and bots (Telegram/IRC) [C, AUP] [https://kb.verpex.com/docs/content-restrictions-and-dmca-compliance].
  - It also requires every uploaded file to be "visible and accessible by visiting that domain" [C, AUP 3.5].
- **The web server is unconfirmed and we cannot choose it.** It is probably the LiteSpeed family [I]: Verpex's KB says "We run the LiteSpeed Webserver on all shared and reseller servers" (a cPanel-oriented page, 2026-09-09), and the host in Verpex's own Enhance Node.js guide answers with `Server: LiteSpeed` [C, VKB-SPEC] [C, HTTP header check 2026-09-25]. That header does not distinguish LiteSpeed Enterprise from OpenLiteSpeed.
  - `.htaccess` works fully on Apache and LiteSpeed, only partly on OpenLiteSpeed (rewrites only), and not at all on Nginx [C, D-WS].
  - The panel has no directory password-protection feature [C, RM].
  - Any design that needs .htaccess for security, such as Basic Auth or deny rules on upload folders, is fragile until we know the web server.
- **Email works but needs care.**
  - Hourly send limits are set per server by Verpex [C, D-MAIL]. Verpex publishes 100/hour for shared accounts, a cPanel-oriented page [C, VKB-MAIL], and publishes no figure for Enhance.
  - PHP `mail()` works on Enhance but its sender identity is not under our control. Official sources disagree on the path (section 3.2, item 4), and a 2024 review says Verpex once disabled `mail()` for a reseller, almost certainly on cPanel [C, D-MAIL] [C, RN 11.0.0] [OLD] [C, Trustpilot 2024] [OLD].
  - Forms should send through authenticated SMTP, and bulk member mail must go through an external list service.
- **Operational realities.**
  - Verpex often changes server IP addresses to shake off traffic floods: 68 to 70 of the 77 status incidents between 2 Aug and 23 Sep 2026, depending on how the wording is counted [C, STATUS]. The incidents cover all Verpex products and none is labelled Enhance. Sites on external DNS must update their A records by hand.
  - Raw access logs are discarded about every 5 minutes [C].
  - Host backups are "best effort" [C, TOS].
  - The Enhance roadmap slips often, so design only around features that have already shipped [C, community].
- **Cost and location.**
  - The smallest Enhance tier, E30, lists at $219/yr or $21.99/mo. That is far more capacity than SpokARES needs, and Verpex has no single-site Enhance plan [C, UPM].
  - The nearest region is "US - Central", probably Dallas. There is no US-West option [C, UPM] [I, ASN lookup].

---

## 2. Plan tiers (Verpex Enhance reseller)

### 2.1 Pricing (USD)

Source: [UPM], the live catalogue behind [VPX]. Products were updated 2026-08-27 and prices 2026-04-08. Every product description says "All discounts apply for the first billing term only." [C]

| Tier | Monthly (list) | 12-month (list) | 36-month (list) | First term with auto promo "ResellerPanelWelcome" (new clients only) | On the page? |
|---|---|---|---|---|---|
| Reseller E30 | $21.99 | $219.00 (= $18.25/mo) | $657.00 | $1.00 first month; 12-mo $131.40 ($10.95/mo); 36-mo $394.20 | Yes |
| Reseller E80 | $39.95 | $399.00 (= $33.25/mo) | $1,197.00 | $1.00 first month; 12-mo $239.40; 36-mo $718.20 | Yes |
| Reseller E120 | $54.95 | $549.00 (= $45.75/mo) | $1,647.00 | $1.00 first month; 12-mo $329.40; 36-mo $988.20 | Yes |
| Reseller E150 | $65.83 | $658.00 (= $54.83/mo) | $1,974.00 | none shown | No (orderable in the catalogue only) |

- **Budget at the list price.** That is $219/yr for E30.
  - Verpex's KB FAQ says these are "the standard prices ... not limited to new signups or promotional periods". That is true of the list prices. The catalogue also auto-applies a new-client promotion on top of them for the first term only, so the first invoice will be lower than later ones. See section 3.2, item 1 [C, VKB-FAQ] [C, UPM].
- **Refunds** [C, TOS]:
  - 30-day money-back guarantee, usable once per customer and on the first term only.
  - Prepaid time is not refunded after that.
  - Liability is capped at twice the fees paid in the prior 12 months.

### 2.2 Allowances and limits

| Tier | "Hosting accounts" | Websites | NVMe disk | Bandwidth | Email accounts | Per-site RAM / CPU / IO / inodes |
|---|---|---|---|---|---|---|
| E30 | 30 | "Unlimited websites" (marketing) | 100 GB | Not stated | "Unlimited" | **Not published** |
| E80 | 80 | same | 300 GB | Not stated | "Unlimited" | Not published |
| E120 | 120 | same | 750 GB | Not stated | "Unlimited" | Not published |
| E150 | 150 | same | 1,200 GB | Not stated | "Unlimited" | Not published |

Sources: [VPX] cards and USPs, [UPM]. [C]

**Common to all tiers [C, VPX]:**
- unlimited domains, free SSL, 24/7 support (chat, phone, tickets) and free migrations
- a free domain on non-monthly billing (common TLDs, including .org)
- automatic incremental backups, the WordPress toolkit, "WordPress caching", and a custom-branded panel domain

**What is and isn't published about limits:**
- **Per-site limits are not published for Enhance.** Verpex's FAQ only says "Your plan will include the same or more RAM and CPU as the closest equivalent cPanel plan" [C, VKB-FAQ].
  - The cPanel reseller comparison table gives 2 GB RAM, 2 vCPU, 30 entry processes, 1,024 IOPS and 50 MB/s IO per account [C, VPX-CP].
  - Verpex's own sources disagree on vCPU. The catalogue description of cPanel "Reseller 30", the closest match to E30 (100 GB, 30 accounts), says "1 vCore & 2GB RAM per user"; only Reseller 50 and up say 2 vCores [C, UPM-CP].
  - Treat 2 GB RAM and 1 to 2 vCPU as a probable floor, not a guarantee [I].
- **Fair-use caps from the AUP apply to every tier** [C, AUP]:
  - multimedia up to 10 GB per account
  - mailboxes up to 20 GB
  - a database over 10 GB counts as "excessive"
  - "Software downloads created by the account holder should not exceed 1GB total"
  - no file-download sites, file storage or sync, or offsite-backup use
- **"Hosting accounts" is not defined.** [I] It most likely maps to Enhance's `customers` quota, since the API's ResourceName enum has separate `customers` and `websites` [C, SPEC]. SpokARES needs one customer and one website (plus an optional staging site, which uses no website slot [C, D-STG]).
- **Locations at checkout for every E-tier** [C, UPM]: US - Central, London, Central Europe, Singapore, India. The location records were last updated 2024-09-03 [OLD as a record date], but they are the live options.
- **For context only (not Enhance):**
  - Verpex's cPanel shared "Bronze" plan (1 site, 30 GB) lists at $72/yr [C, https://verpex.com/web-hosting].
  - A "registered charity" can get free hosting; that policy page sits in the cPanel section [C, https://kb.verpex.com/cpanel/charity-hosting].
  - Neither is Enhance, and the brief requires Enhance.

---

## 3. Capability matrix

The **Supported?** column describes what the Enhance platform offers and what is known about Verpex's configuration of it. "Package-gated" means the feature exists but Verpex's reseller package must include it. Features missing from our reseller subscription "cannot be enabled by the reseller" [C, D-RES].

### 3.1 Matrix

| Capability | Supported? | Details / limits | Source |
|---|---|---|---|
| **Web server (kind)** | **Unconfirmed** (probably LiteSpeed family) | [C] Enhance supports Apache, LiteSpeed Enterprise, OpenLiteSpeed (OLS) and Nginx, chosen **per server by the operator**. A website can only *read* its kind (`GET /v2/websites/{id}/webserver_kind`). [C] No Verpex Enhance page names a web server. [C] Verpex's KB (2026-09-09, written about cPanel/CloudLinux) says "We run the LiteSpeed Webserver on all shared and reseller servers". [C] The host in Verpex's Enhance Node.js guide (s4515.fra1.stableserver.net, 192.250.229.225) returned `Server: LiteSpeed` on 2026-09-25. [I] So Verpex probably runs LiteSpeed Enterprise or OLS on Enhance, but the header cannot tell them apart, and that difference decides Full vs Partial `.htaccess`. [C] Community reports LiteSpeed Enterprise 503s under resource pressure (2025-07 to 2026-01). | https://enhance.com/docs/application-role/ ; [D-WS] ; [SPEC] ; [VPX] ; [VKB-SPEC] ; https://kb.verpex.com/enhance/how-to-deploy-a-nodejs-application-on-enhance ; https://community.enhance.com/d/2959-litespeed-enterprise-and-503-error |
| **.htaccess / rewrites / vhost** | **Partial** | [C] .htaccess support is Full on Apache and LiteSpeed, Partial on OLS, and None on Nginx. [C] The OLS detail (RewriteRule only, no `Header`) comes from community posts of Apr/May 2025 [OLD]. [C] Nginx instead gets a per-domain "URL rewriting" front-controller toggle and includes, and `location /` cannot be overridden (staff quoted in the forum, Feb 2026). [C] Custom VirtualHost config is for the master org and resellers (so us) on Apache/Nginx only. [C] The redirect manager and IP allow/block manager write `.htaccess` (`/htaccess`, `/htaccess/ips`). | [D-WS] ; https://enhance.com/docs/website-tools/nginx/url-rewriting.html ; https://enhance.com/docs/website-tools/custom-vhosts.html ; https://community.enhance.com/d/96-ols-reload-for-htaccess-changes ; https://community.enhance.com/d/3403-nginx-vhost-changes-to-location ; https://community.enhance.com/d/3655-everything-feels-half-baked-or-stone-age ; [SPEC] |
| **PHP versions** | **Yes** | [C] 5.6, 7.0 to 7.4 and 8.0 to 8.5 by default (5.2 to 5.5 are optional manual installs). The version is chosen per website; addon domains share it. [C] **The default is 8.3** since 12.18.0 (16 Mar 2026). The latest builds (28 Aug 2026) are 8.5.10, 8.4.25, 8.3.33 and 8.2.33. [C] A package can restrict versions (`allowedPhpVersions`). Verpex's allowed list is unknown. [C] Verpex KB documents per-site PHP selection. | [RN] ; [SPEC] (PhpVersion enum php52 to php85) ; https://enhance.com/docs/php/php-versions.html [STALE on default] ; https://kb.verpex.com/enhance/changing-php-versions-and-settings-in-enhance |
| **PHP config and extensions** | **Yes** (package-gated) | [C] php.ini editor per site, if the package has "Allow php.ini editor". [C] Extension manager: since 12.21.0, any extension the host has installed. The defaults include curl, gd (webp/avif), zip, mbstring, intl, imagick, opcache, redis, sodium, pdo_mysql, pdo_sqlite, exif and bcmath. pgsql ships disabled. No memcached. [C] OPcache and IonCube per-site toggles. [C] `upload_max_filesize`/`post_max_size` default to 1000M (12.23.0). [C] php-fpm pool overrides are owner/reseller only. [C] PHP starts on demand, so an idle site uses about 2 MB RAM (12.9.0, Jul 2025) [OLD]. | https://enhance.com/docs/php/php-ini.html ; https://enhance.com/docs/php/php-extensions.html ; https://enhance.com/docs/php/ ; [RN] ; [FEAT] |
| **Node.js** | **Partial** | [C] Available since 12.11.0 (22 Sep 2025), about 12 months old, and gated by `persistentAppsAllowed`. Node is installed per site with nvm. Apps run in Automatic mode (Enhance restarts them and logs to `~/persistent_app_<ID>.log`) or Manual mode. The web server proxies a path or the whole site to a port between 1024 and 65535. WebSockets arrived in 12.23.0 (Jun 2026). [C] Limits: **primary domain only**; a subdomain app needs its own website; no env-var UI; no restart endpoint (you toggle the mode); **not redeployed after a backup restore**; same cgroup limits as PHP; needs outbound access to github.com. [C] The docs say "only experienced system administrators" should run Node apps. [C] Verpex KB documents a "Deploy Node.js" tool. | [D-NODE] ; [RN] ; [SPEC] (`/websites/{id}/apps/persistent`) ; https://community.enhance.com/d/3239-restart-nodejs-app-after-deployment ; https://community.enhance.com/d/1432-nodejs ; https://redwaterhost.com/blog/host-a-nodejs-app-on-enhance ; https://kb.verpex.com/enhance/how-to-deploy-a-nodejs-application-on-enhance |
| **Python / Ruby / other runtimes** | **No** | [C] Python is only "Approved" on the roadmap (no development time assigned, no date). The spec has no python, ruby or perl surface, and `PersistentAppKind` is `generic, openclaw`. .NET is not supported. [C] The Verpex KB documents Python only for cPanel. [I] A "generic" persistent app runs any start command, so a Python process could run if an interpreter happens to exist in the container. Nothing confirms one does, so treat Python as unsupported. | [RM] ; [SPEC] ; https://enhance.com/faqs ; https://community.enhance.com/d/3325-python-apps |
| **Static hosting** | **Yes** | [I, strong] A static site is just files in the website's `public_html`. Every web server kind serves it, and PHP only starts on demand. [C] No source reports problems serving static files. No special static mode exists. | [RN] (12.9.0, 12.23.0 public_html references) ; [D-WS] |
| **SSH** | **Yes** (package-gated per community posts, not docs) | [C] Per-website SSH with multiple keys or a password. You land **inside the site container**, not on the host. [C] Tools listed in the docs: `php` (the site's version), `composer`, `wp-cli`/`wp`, `rsync`, `ssh`, `strace`, `top`; node/npm come through nvm. [C] New sites get a local MySQL user in `~/.my.cnf` "allowing database access from SSH without needing to enter a password" (RN 10.0, Dec 2023; handling changed in 12.6.0, May 2025) [OLD]. The SSH docs page does not list `mysql`. [C] Enhance staff said git, vim and nano are available from the host `/usr/bin` (Dec 2024) [OLD]. [C] Setuid binaries have been hidden since 12.21.5 (May 2026). [C] **Port 22 only** (custom ports are a roadmap item). No in-panel web terminal [OLD, 2023]. [C] Documented in the Verpex KB. | [D-SSH] ; https://enhance.com/docs/technical-guidance/enhance-terminology.html ; https://community.enhance.com/d/2214-enhance-ssh ; [RM] ; [RN] ; https://kb.verpex.com/enhance/configuring-ssh-access-in-enhance |
| **SFTP / FTP / file manager** | **Yes** | [C] SFTP is available only through the SSH login on port 22; there is no SFTP-only mode (forum, Nov 2025). [I] If Verpex's package has no SSH, there is no SFTP. [C] FTP is pure-ftpd with multiple accounts per site, each restricted to a directory; FTPS since 12.0.20 (Feb 2025) [OLD]; passive ports 30000 to 31000. [C] Web file manager with unzip and recursive chmod. | https://community.enhance.com/d/3206-sftp-and-ftp-domain ; https://enhance.com/docs/troubleshooting/unable-to-connect-to-ftp.html ; https://kb.verpex.com/enhance/managing-ftp-accounts-in-enhance ; https://enhance.com/docs/application-role/file-manager.html |
| **Git deploy** | **No** | [C] Not shipped as of 12.25.12. The roadmap lists it as "Under development" with no date. The docs pages are partial stubs (an intro and section headings with empty bodies), and the spec has no repository, webhook or deploy-key endpoints. [C] On 2026-08-28 the founder called it "about to release"; users were still waiting on 2026-09-21. [C] It will be package-gated when it ships. Workarounds: `git pull` over SSH, or CI build then rsync [I]. | [RM] ; [RN] ; [SPEC] ; https://enhance.com/docs/git-deploy/deploying-git-repositries.html ; https://community.enhance.com/d/245-git-integration-with-auto-deploy-logged ; https://community.enhance.com/d/3814-12256-available |
| **Cron** | **Yes** | [C] Cron editor under Advanced > Developer tools (API `/crontab`). Jobs run inside the site container under its resource limits. [C] Editing the crontab from SSH became possible in **12.24.0 (3 Jul 2026)** behind a toggle (`container_cron_enabled`). [C] Enhance replaces WP-Cron with a system cron that runs **hourly**. [C] Push-live copies cron jobs (12.25.0). The portable backup excludes cron. | https://kb.verpex.com/enhance/managing-your-website-databases-and-backups-in-enhance ; [RN] ; [SPEC] ; https://enhance.com/docs/wordpress/admin/wordpress-crons.html ; [D-SSH] [STALE on SSH crontab] |
| **Databases** | **Yes** | [C] MySQL 8.4 (default since 12.21.0) or MariaDB LTS/11 with utf8mb4. **Verpex picks the engine per server; which one is unknown.** [C] Databases, users with granular privileges, remote access hosts, gzipped dump download, SQL import (API: .sql/.gz/.zip up to **500 MB**). [C] phpMyAdmin with SSO, but only after the reseller sets a phpMyAdmin domain ("ESSENTIAL"). [C] PostgreSQL 16 is an optional server role (12.22.0, May 2026), and Verpex's FAQ says "Enhance fully supports PostgreSQL". [C] From Node, `127.0.0.1` does not reach MySQL; use the unix socket `/run/mysqld/mysqld.sock` (forum). | https://enhance.com/docs/database-role/ ; [RN] ; [SPEC] (`/v2/websites/{websiteId}/mysql/{db_id}/sql`) ; https://enhance.com/docs/database-role/phpmyadmin.html ; https://enhance.com/docs/resellers/configuring-a-reseller-account.html ; [VKB-FAQ] ; https://community.enhance.com/d/3355-nodejs-env |
| **Redis / caching** | **Partial** | [C] Redis runs per site inside the container. It is off by default and gated by `redisAllowed`. A Feb 2026 report says it can crash and does not restart automatically. [C] No Memcached. [C] OPcache per-site toggle. [C] Page cache is LSCache (LiteSpeed/OLS, via an app plugin) or Nginx FastCGI cache (per domain). **Apache has no built-in page cache.** [C] Verpex lists "WordPress caching" as included. | https://enhance.com/docs/website-tools/enabling-redis.html ; [SPEC] ; https://community.enhance.com/d/3435-redis-opcode-cache ; [D-WS] ; https://enhance.com/docs/wordpress/admin/optimising-for-wordpress.html ; [VPX] |
| **App installer** | **Partial** | [C] One-click installs: WordPress, WordPress + WooCommerce, **Joomla (12.18.0, 16 Mar 2026; a password bug was fixed in 12.19.0)** and OpenClaw (12.24.0). Needs "Allow software installer", and packages can restrict apps. No Softaculous. [C] Verpex KB: "Only WordPress and Joomla are supported at this time." [C] **WordPress toolkit**: plugin and theme manager, per-plugin and core auto-updates, users, SSO, debug toggles, maintenance mode, discovery of existing installs, 31 API operations. The wp-admin IP lockdown works on **Apache only**. [C] **Joomla toolkit**: app info and user management only (7 operations). No update or extension management was seen in the spec [I]. [C] Laravel, Moodle and n8n installers are only "Approved". | [RN] ; [SPEC] (`WebsiteAppKind`) ; [FEAT] ; https://enhance.com/docs/wordpress/end-user/about.html ; https://enhance.com/docs/wordpress/end-user/restrict-admin-access.html ; https://kb.verpex.com/enhance/managing-your-website-databases-and-backups-in-enhance ; [RM] |
| **Staging / cloning** | **Partial** | [C] A staging site uses no website slot, is noindex, has no domain mapping or email, and has one-click push-live (to a new or existing site). "Clone to staging" arrived in 12.16.0 (Jan 2026). [C] It needs the "Staging websites" package toggle **and** a reseller-set staging domain whose **nameservers are delegated to Enhance DNS** (an A record is not enough). [C] Cloning copies files and databases, not email. Preview domains need the staging domain and do get indexed. | [D-STG] ; https://enhance.com/docs/platform-settings/platform-domains.html ; https://enhance.com/docs/website-tools/cloning-websites.html ; https://enhance.com/docs/website-tools/domains/preview-domain.html ; [RN] |
| **SSL** | **Yes** | [C] Automatic Let's Encrypt for every domain on a website (including staging sites) and for `mail.{domain}`, **once its DNS points at the cluster**: 90-day certificates renewed 14 days before expiry. Enhance also requests one 3 days before a third-party certificate expires. [C] Custom certificate upload and a per-domain Force HTTPS switch. [I] No wildcard certificates; wildcard subdomains are a roadmap item. | https://enhance.com/docs/security/about-lets-encrypt.html ; https://enhance.com/docs/security/third-party-ssl.html ; https://kb.verpex.com/enhance/installing-an-ssl-certificate-in-enhance ; [SPEC] ; [RM] |
| **Email (mailboxes)** | **Yes** | [C] Postfix, Dovecot and rspamd. Mailboxes, aliases, forwarders, autoresponders with dates, catch-all (Jul 2025) [OLD], per-mailbox spam thresholds and allow/block lists. [C] Roundcube at `https://mail.{domain}`, with panel SSO (12.17.0) off by default per server. [C] SPF `v=spf1 a mx ~all` is added automatically, DKIM is a per-domain toggle, and **DMARC `p=none` is added to new zones since 12.25.0 (20 Jul 2026)**. These defaults apply only to zones hosted on Enhance DNS [I]. [C] Verpex: "Unlimited email accounts", mailboxes up to 20 GB, 50 MB messages. [C] Verpex AUP 3.4: "All outbound mail is scanned by a cloud-based spam filtering system", and Verpex may queue or temporarily reject mail. [C] Missing: sieve filters, piping, virus scanning, mail queue UI. | https://enhance.com/docs/email-role/ ; https://enhance.com/docs/email-role/email-authentication.html ; https://enhance.com/docs/email-role/webmail.html ; [RN] ; [RM] ; [AUP] ; https://kb.verpex.com/emails/what-is-the-limit-on-the-email-attachment-size |
| **Email sending limits** | **Unknown** (value) | [C] Enhance: hourly limits are set **per server by Verpex**, separately for SMTP and for website-generated mail. Over-limit messages are **rejected, not deferred**. Per-site limits are still "Under development". [C] Verpex: "shared hosting accounts have a maximum limit of 100 emails per hour" and "Unlimited email sending is not allowed on any service". The cPanel reseller table also says 100/hour. No Enhance figure is published. [C] **PHP `mail()` (re-checked 2026-09-25):** the undated email-role docs say a site mapped to an email role sends `mail()` through "a drop-in sendmail replacement ... through the website's SMTP using a hidden mailbox". Release notes 11.0.0 (26 Jun 2024) [OLD] say "Website generated emails are now sent from the local MTA rather than being forwarded to the assigned email server", with no email role required. That `mail()` sends as `user@server-hostname` is a **community user's** statement (not staff) in a thread about a self-run cluster with a smart host and no email role (forum, Mar 2026); ⚠️ unverified for Verpex. [C] A 2-star Trustpilot review (NZ reseller, 16 Oct 2024) [OLD] says Verpex "turned off the PHP Mail function but didn't bother to tell anyone". It predates Verpex's Enhance products (catalogue created 2026-03-12), so it almost certainly concerns cPanel [I]. ⚠️ Whether `mail()` is enabled on Verpex Enhance is unverified; confirm with Verpex. | https://enhance.com/docs/email-role/email-settings.html ; [SPEC] (`/servers/{id}/spam/*`, master org only) ; [RM] ; [VKB-MAIL] ; [VPX-CP] ; https://community.enhance.com/d/3494-query-around-php-mail ; https://community.enhance.com/d/3233-wordpress-forms-not-sending-emails-do-i-need-to-enable-the-email-role ; https://www.trustpilot.com/review/www.verpex.com?stars=2 |
| **DNS** | **Yes** (package-gated editor) | [C] PowerDNS zone editor ("Allow DNS zone editor"). Record types A, AAAA, CNAME, TXT, SPF, SRV, NS, MX, PTR, DS and CAA. DNSSEC, default TTL 1400 s, reseller custom nameservers, and a per-plan record cap. [C] One-way Cloudflare sync (beta) with a proxy flag. A forum report says it deletes and re-adds all records on every change. [C] Local/Remote mail routing (for example Google Workspace MX). | https://enhance.com/docs/dns-role/about.html ; [FEAT] ; [SPEC] ; https://enhance.com/docs/dns-role/end-user/dns-cloudflare.html (also: "changes in Cloudflare will not sync with Enhance"; no Cloudflare token on staging domains) ; https://community.enhance.com/d/3749-12250-now-available ; https://kb.verpex.com/enhance/managing-your-domains-in-enhance |
| **Backups / restore** | **Yes** (policy **Unknown**) | [C] Incremental hard-link snapshots. **Frequency and retention are global operator settings**, not visible to us and not published for Enhance. [C] Self-restore (full, email, or a custom mix of files, databases and mailboxes; single files since 12.15.0, Jan 2026) needs the package toggles "Allow manual backups" and "Allow self restore backups". [C] S3-target backups do not allow single-file restores (12.15.0), so that feature depends on Verpex using the native backup role. [C] A portable `.tar.gz` download or upload is available to end users **by default**, independent of the backup role and package backup settings. It excludes cron, and downloads are capped at 2 GB when the panel runs LiteSpeed/OLS. [C] ToS (generic, not Enhance-specific): "We take twice-daily backups of your websites and store them offsite … best effort … your responsibility to keep backups". Retention is not published anywhere. AUP: suspended reseller sub-accounts can be terminated after 60 days, "We do not keep any backups". [C] 2026 Trustpilot: a cPanel reseller's backups would not restore. | https://enhance.com/docs/backup-role/admin/backup-settings.html ; https://enhance.com/docs/backup-role/end-user/restore-backup.html ; https://enhance.com/docs/backup-role/end-user/download-backup.html ; [SPEC] ; [TOS] ; [AUP] ; https://kb.verpex.com/enhance/managing-backups-in-enhance ; https://www.trustpilot.com/review/www.verpex.com?stars=1 |
| **WAF / security** | **Partial** | [C] ModSecurity with OWASP CRS as a per-domain toggle, **only if Verpex enabled it on the server** (docs: Apache/Nginx; features page: also LiteSpeed; not OLS). [C] Per-site containers, setuid binaries hidden, SVG uploads checked for JavaScript (Aug 2026), panel 2FA, brute-force protection. [C] Verpex: Monarx malware protection. [C] **No panel password protection for directories** ("Approved" only); no ClamAV; firewall UI only "Approved". [C] ToS: no guarantee of DDoS defence; buy third-party protection such as Cloudflare. | https://enhance.com/docs/security/modsecurity.html ; [D-WS] ; [RN] ; [VKB-FAQ] ; [RM] ; https://community.enhance.com/d/569-password-protect-website-logged ; [TOS] |
| **Resource limits** | **Unknown** (values) | [C] Hard per-site cgroup limits on memory (OOM kill), fractional vCPU, IOPS and IO bandwidth (processes hang in D state), nproc, disk, inodes (12.23.0, Jun 2026) and swap. They apply to PHP, Node, cron and SSH alike. "Websites that exceed … will break in an unexpected undefined way." [C] Set by the master org (Verpex); resellers cannot change them. [C] Verpex publishes no Enhance numbers (see section 2.2). [C] Inside the container, `free`/`nproc` show the **host's** resources (staff: "This is expected, it's a container rather than a VM"), and one forum user's Next.js build was OOM-killed at about 2 GB, **that user's own package limit on a non-Verpex cluster**, not a Verpex figure (Sep 2026). [C] The per-website limits page also says a reseller can override limits; the reseller page and the spec say no (section 3.2, item 5). | [D-LIM] ; [D-RES] ; [SPEC] (`cgroup_limits` PUT "(Master org only)") ; https://community.enhance.com/d/3837-why-does-a-website-container-see-the-hosts-memory-instead-of-its-package-limit |
| **CDN** | **Partial** | [I] No built-in CDN in the docs, spec or Verpex page. [C] The Cloudflare integration (DNS sync plus proxy flag) is the supported route. The ToS recommends Cloudflare for DDoS, and the AUP "may require the use of a CDN" for downloads. [C] Passing the real visitor IP through from Cloudflare "across all web server kinds" is only a roadmap item, so the app may see Cloudflare IPs [I]. | [SPEC] ; https://enhance.com/docs/dns-role/end-user/dns-cloudflare.html ; [TOS] ; [AUP] ; [RM] |
| **Datacenters** | **Partial** | [C] Enhance checkout offers US - Central, London, Central Europe, Singapore and India. **No US-West.** [I] "US - Central" is probably Dallas: the `usc1` status hostnames resolve to AS36454 WHG-DAL and geolocate to Dallas. Nothing shows those hosts are Enhance nodes, and Verpex never maps the checkout label to `usc1`. [I] "Central Europe" is probably Frankfurt: Verpex's Enhance KB example host is `s4515.fra1.stableserver.net` (AS209341 WHG-FRA). [C] Verpex has use1/use2, can1, mex1 and syd1 hosts for other products; none is offered at Enhance checkout. [C] Verpex runs no datacenters of its own; hosts sit on World Host Group ASNs, and the ToS allows "like for like" moves between providers. [C] The status page has an "Enhance Hosting" component; public data does not show which incidents hit Enhance servers. | [UPM] ; https://stat.ripe.net/data/prefix-overview/data.json?resource=194.39.149.92 ; https://stat.ripe.net/data/maxmind-geo-lite/data.json?resource=194.39.149.92 ; https://kb.verpex.com/enhance/how-to-deploy-a-nodejs-application-on-enhance ; https://status.verpex.com/incidents ; https://bgp.he.net/AS14670 ; https://verpex.com/our-technology ; [TOS] ; [STATUS] |
| **API access** | **Unknown** (for us) | [C] The orchd REST API is at v12.25.12, with 302 paths and 482 operations, using bearer tokens or a session cookie. Org access tokens have roles and IP restriction, and the reseller surface is `/orgs/{org_id}/…`. [C] Verpex says "a RESTful API is also available for custom integrations" but does not say whether resellers get tokens. [C] Tokens cannot be scoped to one website: `NewAccessToken` has roles, expiry and IP restriction but no website list (spec; forum, Jul 2026). [C] The spec also has `POST /orgs/{org_id}/websites/{website_id}/access-tokens` (getSiteAccessToken), which returns a per-site JWT; its purpose is undocumented and it is not a general API key [I]. For site-level isolation, put the site in its own customer org [I]. [C] **No general file-upload or deploy endpoint.** The only upload endpoints are backup `.tar.gz` upload-and-restore, SQL import, and cPanel/Plesk import archives. | [SPEC] ; [VPX] ; [VKB-FAQ] ; https://community.enhance.com/d/3239-restart-nodejs-app-after-deployment |
| **Logs / analytics** | **Partial** | [C] PHP error log in the UI and home directory. [C] **Raw access logs are discarded about every 5 minutes** and customers cannot keep them (still open 2026-08-28). [C] The panel shows bandwidth and hourly hits (`/metrics`). | https://enhance.com/docs/technical-guidance/website-access-and-logs.html ; https://community.enhance.com/d/2398-website-access-logs ; [SPEC] |
| **Outbound HTTP from PHP** | **Yes** | [C] Containers reach the internet through NAT, so server-side API fetches (for example weather) work. Container DNS resolvers became customisable in 12.25.12. | https://enhance.com/docs/php/troubleshooting/php-websites-cannot-connect-to-the-outside-internet.html ; [RN] |
| **Team access** | **Yes** | [C] Users can be invited as Super Admin or as "Collaborator" ("unrestricted access to a specific website"; the roles page says the website(s) granted). Since 12.21.3 Collaborators cannot add or delete Cloudflare tokens via the API. The spec role `SiteAccess` has per-site access. These are control-panel logins, not website member logins [I]. | https://enhance.com/docs/account/end-user/managing-user.html ; [SPEC] |
| **Migration / import** | **Yes** | [C] "Import Website" takes a cPanel backup (files, databases, email, DNS), followed by "Transfer ownership". Verpex offers free, best-effort migrations and aims for 72 hours. | https://kb.verpex.com/importing-and-restoring-a-cPanel-account-into-enhance ; [VKB-MIG] ; [TOS] |
| **Uptime / SLA** | **Partial** | [C] ToS: "endeavour to provide a 99.9% service uptime", with no service credits. The Enhance page states no figure. [C] Third-party monitors (all on cPanel products) measured 99.95% to 100%. | [TOS] ; https://googiehost.com/blog/verpex-reviews/ ; https://www.linuxteck.com/guides/verpex-reviews/ |
| **Long-running daemons, bots, streaming** | **No** | [C] Bots (including Telegram and IRC), audio or live streaming, VPN/proxy, batch processing and crawling are all prohibited. [C] Containers cannot open listening daemons on the host's public interfaces. | [AUP] ; https://kb.verpex.com/docs/content-restrictions-and-dmca-compliance ; https://kb.verpex.com/network/can-i-livestream-audio-or-video ; https://enhance.com/docs/technical-guidance/enhance-terminology.html |

### 3.2 Contradictions and how they were resolved

1. **Is the price introductory?**
   - Verpex KB FAQ (lastmod 2026-06-28): "the standard prices ... not limited to new signups or promotional periods".
   - Live catalogue (updated 2026-08-27): the list prices, plus an auto-applied new-client promotion ("ResellerPanelWelcome") for the "first billing term only".
   - **Not a true contradiction** (revised in verification). The FAQ is accurate about the list prices; the promotion is an extra first-term discount on top. The FAQ does leave out that the price first shown is discounted. Budget at the list price.
2. **Default PHP version.**
   - Docs page: 8.1. Technology page: "5.6 - 8.3".
   - Release notes 12.18.0 (16 Mar 2026): default 8.3. 12.13.0: 8.5 added. The spec enum runs to php85.
   - **Chose the release notes plus spec.** They are dated and newer; the docs pages are undated and stale.
3. **Crontab from SSH.**
   - SSH docs: "not possible".
   - 12.24.0 (3 Jul 2026) and the spec `container_cron_enabled`: possible behind a toggle.
   - **Chose the release notes plus spec.** Flag: Verpex may not run 12.24.0 yet.
4. **How PHP `mail()` is sent.** (Re-checked 2026-09-25 after 2 of 3 skeptics refuted the original wording.)
   - Email-role docs (undated): a site mapped to an email role sends `mail()` through "a drop-in sendmail replacement ... through the website's SMTP using a hidden mailbox".
   - Release notes 11.0.0 (26 Jun 2024) [OLD]: "Website generated emails are now sent from the local MTA rather than being forwarded to the assigned email server for the website." This reads as replacing the model the docs describe, but that is an inference.
   - Community (Mar 2026): one **non-staff** user says `mail()` sends as `user@server-hostname`. The thread is about a self-run cluster with a smart host and no email role, not a provider like Verpex.
   - **Not resolvable from public sources.** The earlier draft overstated the `user@server-hostname` behaviour as fact; it is now marked unverified. What is solid: the sender identity of `mail()` depends on operator configuration we cannot see, and Verpex's AUP lets it block or queue mail without notice. The design uses authenticated SMTP either way, so the plan does not change.
5. **Can a reseller override per-site resource limits?**
   - One docs line says yes.
   - The reseller docs page and a tip on the limits page say no. The spec labels `PUT …/cgroup_limits` and `…/fs_quota_limits` "(Master org only)".
   - **Chose the spec** (highest authority), which agrees with the more specific reseller page. Verpex controls limits.
6. **Uptime.**
   - ToS: 99.9% "endeavour". cPanel comparison table: 99.99%. Enhance page: nothing.
   - **Chose the ToS.** It is the binding legal text and covers all services. The 99.99% figure is cPanel marketing.
7. **Locations.**
   - Old undated KB pages: US-West, South America, "40+ locations".
   - Live checkout: 5 Enhance regions.
   - **Chose checkout.** It is current and orderable. Independent reviews that list US-West or San Francisco conflict with each other and with checkout.
8. **Node.js restart.**
   - Features page: "restart".
   - Node docs, spec (no restart endpoint) and forum: restart means toggling Automatic, then Manual, then Automatic.
   - **Chose the docs plus spec.**
9. **When reseller resources are consumed.**
   - Reseller-package docs: reserved at subscription time.
   - 12.25.9 (11 Sep 2026): consumed when the resource is created.
   - **Chose the release notes.**
10. **Git deploy.**
    - Docs stubs describe the feature.
    - The roadmap says "Under development", no release note mentions it, and the spec has no endpoints.
    - **Chose roadmap, release notes and spec: not available.** Do not design around it.
11. **Hidden plan table on the Verpex page.**
    - The page payload holds a cPanel-style table (15 accounts, 50 GB, LVE limits) that is identical on all E-plans and never rendered.
    - **Chose the rendered cards and the catalogue.** The table is template leftovers.
12. **PostgreSQL.**
    - Verpex FAQ: "fully supports". Enhance: an optional server role since 12.22.0.
    - Both statements can be true, but the FAQ describes Enhance in general, and Verpex's Enhance KB documents only MySQL. Treat PostgreSQL as **unverified** for our servers, and check `canUse.postgresql` in the panel. The recommended stack does not need it.
13. **git inside the SSH container.**
    - The SSH docs do not list it. A staff post (Dec 2024) [OLD] says it is there. The spec tracks `git` as a server system package.
    - **Treat as likely.** The deploy design uses rsync from CI, so it does not depend on git.
14. **App list.**
    - Verpex KB (2026-06-28): "Only WordPress and Joomla".
    - Enhance added OpenClaw on 3 Jul 2026.
    - These are consistent, since the KB predates the release. Irrelevant to SpokARES.

---

## 4. Constraints and gotchas for a developer deploying here

### 4.1 Deploying code and content

- **Don't build on the server.**
  - Container memory limits are hard, and tools inside the container report the host's RAM. Builds (npm, SSGs) get OOM-killed at the package memory limit; one forum user hit this at about 2 GB on their own (non-Verpex) cluster [C, community d/3837]. Verpex's limit is unpublished.
  - Node is package-gated [C, D-NODE].
  - Build locally or in CI (for example GitHub Actions) and ship only the artifacts [I].
- **Deploy over SSH port 22.**
  - `rsync -az --delete` over an SSH key added under the website's Developer tools, or SFTP on the same login [C, D-SSH] [C, community d/3206].
  - FTPS is the fallback if Verpex's package lacks SSH [C].
  - There is no native Git deploy [C, RM].
  - `git pull` over SSH probably works but is unverified [I].
- **No file-upload or deploy API.**
  - The API cannot push individual files. Its only uploads are a whole-site `.tar.gz` restore, SQL import (up to 500 MB) and cPanel/Plesk import [C, SPEC].
  - Don't script deploys through the API. API tokens also cannot be scoped to one website, so never put an org-wide token in CI [C, community d/3239].
- **Custom SSH ports are not supported** [C, RM]. GitHub-hosted runners can reach port 22 on the server IP [I]. Remember that the IP can change (section 4.5), so deploy to a hostname, not a hard-coded IP.
- **Database import.** Three routes:
  - phpMyAdmin, which needs the reseller-level phpMyAdmin domain set first [C]
  - `mysql` over SSH using the provisioned `~/.my.cnf` [C, RN 10.0 and 12.6.0] [OLD]; the SSH docs page does not list the `mysql` client, so verify it exists
  - API SQL upload of .sql/.gz/.zip up to 500 MB [C, SPEC]

  Get the connection host from the panel. From a Node app, `127.0.0.1` fails, and the socket is `/run/mysqld/mysqld.sock` [C, community d/3355].
- **PHP version.**
  - Pick it per website. The default is 8.3 [C, RN], and addon domains share the site's version [C].
  - Target 8.3 as the floor and test on 8.4. Verpex's package decides which versions appear [C, SPEC `allowedPhpVersions`].
  - The php.ini editor needs the "Allow php.ini editor" allowance [C].
  - Upload and post limits default to 1000M [C, RN 12.23.0].

### 4.2 Web-server portability

- **We don't know or control the web server** [C, SPEC]. It is probably LiteSpeed Enterprise or OLS (section 3.1) [I], and those two differ on `.htaccess`. Assume nothing beyond basic front-controller rewrites:
  - Apache or LiteSpeed: `.htaccess` is fully honoured.
  - OLS: `RewriteRule` only, and `Header` is ignored [C, community d/96] [OLD, Apr/May 2025].
  - Nginx: `.htaccess` is ignored. Use Enhance's "URL rewriting" toggle (front controller to `index.php`) plus includes [C, D-WS].
- **Put security headers in application code**, not `.htaccess` [I].
- **The panel's redirect and IP allow/block managers write `.htaccess`** [C, SPEC], so they do nothing on Nginx [I]. Reseller custom-vhost config covers Apache and Nginx only [C].
- **Protected files.**
  - The panel cannot password-protect a directory [C, RM].
  - Manual Basic Auth works on Apache and probably LiteSpeed, but not on OLS or Nginx [C, founder, community d/569] [OLD, Feb 2024; thread still open 2026-06]. The same thread shows a PHP-level Basic Auth workaround that works on OLS and Nginx [C]. Reseller custom-vhost directives could add auth on Apache/Nginx only, and that tool does not exist for LiteSpeed/OLS [C, custom-vhosts doc].
  - Deny rules that CMS plugins write into upload folders only work where `.htaccess` is honoured [I].
  - **Member-only content must be enforced by the application.** Member-only files should be served through an authenticated PHP route, or be accepted as "unlisted, not secret" [I].
- **WordPress wp-admin IP lockdown works on Apache only** [C, https://enhance.com/docs/wordpress/end-user/restrict-admin-access.html].
- **Page caching depends on the server kind** [C, D-WS]:
  - LSCache on LiteSpeed/OLS
  - FastCGI cache on Nginx
  - none on Apache

  Choose caching plugins after the web server is known. The LiteSpeed signals (section 3.1) make LSCache the likely choice [I].

### 4.3 Email

- **Send form mail through authenticated SMTP**, never bare `mail()`. Use either:
  - a real mailbox on spokares.org hosted on Enhance, or
  - a transactional provider.

  Why: we cannot see or control how `mail()` is routed or which sender it uses. The official sources disagree (docs: via the site's SMTP with a hidden mailbox; RN 11.0.0 [OLD]: via the local MTA), and a non-staff forum user reports `user@server-hostname` senders on a self-run cluster [C, D-MAIL] [C, RN] [C, community d/3494] (⚠️ unverified for Verpex; section 3.2, item 4). Verpex's AUP 3.4(d) lets it block or queue mail "with or without notifying" [C, AUP], and a 2024 review says it once disabled `mail()` for a reseller without notice, almost certainly on cPanel [C, Trustpilot 2024] [OLD] [I]. Authenticated SMTP from a real mailbox gives an aligned SPF/DKIM sender whatever the answer.
- **Turn on DKIM** (a per-domain toggle) [C].
  - SPF defaults to `v=spf1 a mx ~all` [C], on zones hosted on Enhance DNS. If an external ESP sends for spokares.org, add its `include:` [I].
  - DMARC `p=none` is added to new zones on 12.25.0 or later [C, RN]. Tighten it after checking reports [I].
- **Hourly caps are per server, and messages over the cap are rejected, not queued** [C, D-MAIL].
  - Verpex's published figure for shared accounts is 100/hour [C, VKB-MAIL].
  - Never send member-wide notices from the host. Use a list service. The AUP also requires an opt-out in non-transactional mail [C, AUP].
- **Missing mail features** [C, RM]: no sieve filters, no piping and no virus scanning. Plan mailbox rules in the mail client.

### 4.4 Policy (Verpex AUP / ToS)

- **AUP 3.5:** "All files uploaded to a domain … must be visible and accessible by visiting that domain, except for hidden files needed to operate the website. We reserve the right to delete files not meeting these criteria without notice." [C, AUP]
  - Keep documents in the web root, behind a login if necessary. Do not keep private archives or backups on the account [I].
  - Get written confirmation that login-gated member PDFs are acceptable (section 6).
- **AUP 3.1:**
  - "Software downloads … should not exceed 1GB total"
  - "File/Software download sites or repositories are not allowed"
  - multimedia up to 10 GB

  A library of club PDFs and presentations is a small documents library, not a software repository [I]. Still, keep large media (videos, audio recordings) on YouTube, archive.org or similar.
- **No net-audio stream and no bots.** A Broadcastify-style stream or a Discord/Telegram bot for net control cannot run here [C, content restrictions]. Embed or link a third-party service instead [I].

### 4.5 Operations

- **IP changes.** Verpex's routine fix for traffic floods is to move a server to a new IP: 68 to 70 of the 77 incidents between Aug 2 and Sep 23, 2026 [C, STATUS]. The incidents cover all Verpex products and none is labelled Enhance, so the rate on Enhance nodes is unknown. The notices say "clients using external name servers, please manually update the DNS records", Several 1-star reviews (Jun to Jul 2026) say reseller IPs change "without prior notice" [C, https://www.trustpilot.com/review/www.verpex.com?stars=1]. A 2-star review ("Grace", CA, 17 Jul 2026, about 20 client accounts) describes a 6-day outage after an "origin IP change during the DDoS mitigation process", resolved once "my Cloudflare A record was updated to match" [C, https://www.trustpilot.com/review/www.verpex.com?stars=2].
  - **Keep spokares.org on Verpex/Enhance nameservers.** This is the only setup the notices say needs no action.
  - The Enhance Cloudflare sync is one-way (Enhance to Cloudflare), so it helps only if Verpex's IP change also rewrites the Enhance zone [C, Cloudflare doc] [I]. ⚠️ Unverified; confirm with Verpex before relying on it.
  - If DNS must stay external, subscribe to status alerts and keep an A-record runbook [I].
- **Backups.**
  - Schedule and retention are Verpex's settings and are not visible to us [C, SPEC] [C, D backup-settings]. The ToS states a generic "twice-daily" offsite backup for all services; retention is not published [C, TOS].
  - Host backups are best effort [C, TOS], and suspended reseller sub-accounts can be deleted after 60 days with no backups kept [C, AUP].
  - Keep an off-host copy of: the repo or theme, periodic DB dumps, uploaded files, and a DNS zone export. One reviewer reports Verpex refused to export DNS records when a customer left [C, Trustpilot 2026].
  - The portable `.tar.gz` backup excludes cron jobs [C].
- **No access-log history.** Raw logs are discarded about every 5 minutes [C]. Use application-level or privacy-friendly JS analytics if traffic data matters [I].
- **Cron runs inside the container under the site's limits** [C]. Heavy jobs share the site's memory and CPU. WordPress scheduled tasks run hourly through the system cron [C].
- **The Enhance version at Verpex may lag.** Operators apply updates on their own schedule [I, RN 12.0.0 apt architecture]. Features dated mid-2026 may be missing until Verpex upgrades: cron from SSH, WebSocket proxy, DMARC default, inode limits, reseller consumption-on-create.
- **Support.**
  - Front-line chat is fast (under 1 minute) [C, GoogieHost], but escalations are slow; one outage had 20 hours between replies [C, Trustpilot 2026].
  - HostAdvice found live chat was answered by a sales agent [C].
  - Keep the stack standard so we rarely need support [I].

### 4.6 One-time reseller setup (before any site works)

- **Set the Control Panel domain** (ESSENTIAL). This is how volunteers log in [C, https://enhance.com/docs/resellers/configuring-a-reseller-account.html] [VKB-MIG].
- **Set the phpMyAdmin domain** (ESSENTIAL). Without it, customers get no phpMyAdmin [C].
- **Optional:** custom nameservers, a Roundcube domain, and a staging domain. Staging only works if the staging domain's nameservers are delegated to Verpex/Enhance DNS [C, D-STG].
- **Create the site:** a package, then a customer, then a website [C, https://kb.verpex.com/enhance/adding-a-new-customer-to-your-enhance-resellers-account].
- **Grant access:** give volunteer webmasters **Collaborator** access to the one website instead of the reseller login [C].

---

## 5. Stack options for the spokares.org rebuild

**Features to carry over:** static info pages, an events calendar (the weekly net-controller schedule), a downloads library (PDF forms, net scripts, presentations), a member login area, contact and join pages, a weather module and an image slider.

**Two lenses:**
1. Does it fit this hosting?
2. Can a small volunteer group of non-developers keep it running for years?

Plugin and extension names below are illustrative examples. They were **not evaluated in this research** and must be vetted separately [I].

### 5.1 At a glance

| Criterion | (a) SSG + CI + rsync | (b) WordPress | (c) Joomla 5.x | (d) Flat-file PHP CMS (Grav/Kirby) | (e) Node.js app |
|---|---|---|---|---|---|
| Fit on Verpex Enhance | **Best**: plain files, any web server, no package toggles except SSH | **Good**: first-class installer and toolkit, any web server | **OK**: installer since Mar 2026, thin toolkit | **OK/weak**: manual install; folder-protection rules depend on .htaccess or vhost | **Poor**: package-gated, primary domain only, no redeploy on restore |
| Non-technical editing | Poor unless a Git-based CMS is added | **Best** | Good (familiar to current editors) | Good (admin panels) | Poor |
| Events calendar | Data file or embed | Plugin | Extension | Plugin (limited) | Custom code |
| Document library | Files in repo, generated index | Media library or plugin | Extension or articles | Pages and media | Custom code |
| Member-only content | **Weak** (needs a PHP gate, Basic Auth or an external service) | Good (users, roles, plugin) | **Best** (native ACL) | OK (login plugin or custom) | Custom code |
| Contact/join forms | PHP handler or third-party form service | Plugin plus SMTP plugin | Core contact plus extension, SMTP in global config | Form plugin with SMTP | Custom code |
| Security/patching burden | **Lowest** | Highest attack surface, but auto-updates in Enhance | Medium, with manual updates | Low-medium | High (npm plus runtime) |
| Volunteer hand-off risk | High (needs a git-literate maintainer) | **Lowest** (largest help pool) | Medium | Medium-high (small community) | Highest |

### 5.2 (a) Static site generator (Astro / Hugo / Eleventy), built locally or in GitHub Actions, deployed with rsync/SFTP

**Hosting fit: excellent.**
- Plain files in `public_html` work on every web server kind [I, strong].
- It needs no PHP, database, Node, Redis or staging toggles. Only SSH/SFTP (or FTPS) is needed for deploys [C, D-SSH].
- It restores cleanly from backups, and builds happen off-server, which avoids container OOMs [C, community d/3837].
- It is also the most portable if we ever leave Verpex [I].

**Volunteer fit: weak.**
- Editing means Markdown in GitHub, or a Git-based CMS front end such as Decap, Sveltia or Pages CMS. Those need GitHub accounts for editors and an OAuth or auth service hosted outside Verpex [I].
- Someone must keep the CI pipeline and deploy key working [I].

**How each feature is handled:**
- **Events calendar:**
  - The net-controller roster as a YAML or CSV data file, rendered into a schedule page plus a generated `.ics`, or
  - an embedded shared calendar (for example Google Calendar) that the net manager edits directly [I].
  - Both need no server code. The embed is the most volunteer-friendly but adds an outside dependency.
- **Document library:** PDFs committed to the repo, with an index page generated at build time [I].
- **Member-only content:** the weak point. Options:
  - (1) A small PHP gate: a session login that streams files from a web-root folder that is denied by rewrite rules. This is custom code, and the deny rule depends on the web server kind.
  - (2) `.htaccess` Basic Auth, which works on Apache/LiteSpeed only [C, community d/569].
  - (3) Move member content to an external platform the group already uses (for example groups.io, common among ham clubs) [I].
- **Contact/join forms:** a roughly 50-line PHP handler using PHPMailer over authenticated SMTP, or a third-party form service [I]. PHP runs on any Enhance site [C].
- **Weather and slider:** a client-side widget, or a PHP cron job that caches an api.weather.gov response (outbound HTTP works) [C] [I].
- **Security/patching:**
  - Almost no server-side attack surface (only the form and gate scripts).
  - Build dependencies are updated at leisure.
  - This is the lowest burden of all the options [I].

### 5.3 (b) WordPress (current release, PHP 8.3+, MySQL/MariaDB)

**Hosting fit: good. It is the best-supported application on Enhance.**
- One-click install [C, RN] [SPEC].
- The toolkit adds [C, https://enhance.com/docs/wordpress/end-user/about.html]:
  - core, per-plugin and per-theme **auto-updates**
  - SSO into wp-admin, a plugin and theme manager, users, debug toggles and maintenance mode
  - clone-to-staging with WordPress search/replace on push-live
- WP-Cron is replaced by an hourly system cron [C].
- Permalinks work on every server kind: `.htaccess` on Apache/LiteSpeed/OLS, or the Nginx "URL rewriting" toggle [C, D-WS].
- Caching is LSCache on LiteSpeed/OLS, FastCGI cache on Nginx, or Verpex's advertised "WordPress caching" [C]. LSCache is the likely fit, since Verpex's hosts appear to run LiteSpeed [I].
- It uses only default-on or shipped features.

**Volunteer fit: best.**
- The block editor is widely known, and the pool of volunteers who have used WordPress is the largest of any option [I].
- Roles (Editor, Author, Subscriber) map onto webmaster, contributors and members [I].
- Volunteer webmasters get Enhance Collaborator access, and SSO into wp-admin, without holding the reseller login [C].

**How each feature is handled:**
- **Events calendar:** an events plugin that supports recurring events (check whether recurrence is in the free tier), or a simple "Net schedule" page or table updated monthly, or an embedded shared calendar [I].
- **Document library:** the media library plus a document or download-manager plugin with categories [I]. If member-only downloads are protected by `.htaccess` deny rules, **that protection only holds on Apache/LiteSpeed**. Confirm the web server before relying on it [C, D-WS] [I].
- **Member-only content:** WordPress user accounts plus a role-based content-restriction plugin. The join flow can create Subscriber accounts after approval [I].
- **Contact/join forms:** a form plugin plus an SMTP plugin that sends through a spokares.org mailbox or an ESP [I]. Use it because `mail()` routing and sender identity are outside our control and unverified on Verpex (section 4.3) [I].
- **Security/patching:** the highest-targeted CMS. Mitigate with:
  - Enhance toolkit auto-updates for core and all plugins [C]
  - a small plugin set (at most about 8), with no commercial "nulled" themes
  - per-domain ModSecurity if Verpex enabled it [C]
  - 2FA for admins [I]
  - wp-admin IP lockdown only on Apache [C]
  - off-host backups

### 5.4 (c) Joomla 5.x (upgrade from Joomla 3)

**Hosting fit: acceptable.**
- One-click installer since 12.18.0 (16 Mar 2026); the launch password bug was fixed in 12.19.0 [C, RN] [community d/3473].
- **The Enhance toolkit only covers Joomla users**: create, delete and password operations. There is no update, extension or SSO management [C, SPEC] [I].
- SEF URLs need `.htaccess` (Apache/LiteSpeed/OLS) or the Nginx front-controller toggle [C, D-WS].
- The Joomla task scheduler needs a real cron entry [I].

**Volunteer fit: medium.**
- Current editors know the Joomla 3 admin, but the Joomla 4/5 admin was redesigned [I].
- The extension ecosystem and pool of helpers are smaller than WordPress's [I].
- **Joomla 3 to 5 is effectively a rebuild.** The template (js_wright) and JCal Pro must be replaced or confirmed compatible. No Enhance tool migrates content [C, SPEC, importers move hosting accounts only] [I].
- [I, not researched] Joomla 6 was scheduled for October 2025. If it has shipped, a rebuild should target whichever major version the Enhance installer lists (`GET /utils/installable-apps` returns versions [C, SPEC]).

**How each feature is handled:**
- **Events:** a calendar extension [I].
- **Documents:** a download extension, or articles that link to PDFs [I].
- **Member-only content:** **native access levels and ACL, the strongest built-in option** [I].
- **Forms:** the core contact component plus an extension for join forms. SMTP is set in Global Configuration [I].
- **Patching:** core and extension updates from the Joomla admin, handled manually by volunteers [I].

### 5.5 (d) Flat-file PHP CMS (Grav, Kirby)

**Hosting fit: workable but fiddly.**
- There is no installer, so it is uploaded by hand [C, SPEC `WebsiteAppKind`].
- Needed PHP extensions (gd, zip, mbstring, curl, openssl) are in Enhance's default list [C, https://enhance.com/docs/php/php-extensions.html].
- The catch is that these CMSs keep content, accounts and config as files inside the web root. They rely on `.htaccess` or Nginx rules to block direct access to those folders [I].
- On Nginx that means reseller custom-vhost rules [C, custom-vhosts doc]; on OLS, rewrite-based blocks only [C].
- With no database, backups are simple, and content can live in git [I].

**Volunteer fit: medium.** The admin panels are pleasant, but the communities are small and fewer volunteers will have seen these tools. Kirby needs a paid licence per site (check the current price) [I].

**How each feature is handled:**
- **Events:** a community plugin or a hand-edited page [I].
- **Documents:** media and pages [I].
- **Member-only content:** Grav's login plugin, or custom code in Kirby [I].
- **Forms:** a form plugin with SMTP [I].
- **Patching:** a small attack surface, with self-update from the admin [I].

**Verdict:** No advantage over (a) or (b) that outweighs the smaller ecosystem and the rules we would have to hand-write for folder protection.

### 5.6 (e) Node.js application

**Hosting fit: poor.** Node is supported, but as a second-class citizen:
- It needs the `persistentAppsAllowed` package toggle [C, SPEC].
- It is proxied on the primary domain only, has no env-var UI and no restart button, and is **not redeployed after a backup restore** [C, D-NODE].
- It shares the container's hard memory limits [C].
- Enhance's own docs say it is for "experienced system administrators" [C].

**Volunteer fit: poor.** Every feature (calendar, documents, members, forms) would be custom code or framework glue. Maintenance means keeping npm dependencies patched [I].

**Verdict: rejected.** Node remains useful only as an off-server build tool for option (a).

### 5.7 Recommendation

**Build spokares.org as a single WordPress site on Verpex Enhance (E30 tier)**, with these choices:
- PHP 8.3 (test 8.4) and MySQL/MariaDB, installed with the Enhance one-click installer.
- Enhance WordPress Toolkit auto-updates **on** for core, plugins and theme.
- A deliberately small plugin set: events with recurrence, content restriction or membership, a form plugin, an SMTP mailer, and optionally a document library.
- Form mail through **authenticated SMTP** from a spokares.org mailbox, or from a transactional ESP.
- **Verpex/Enhance nameservers.** Not external DNS, because Verpex changes server IPs often. Use Cloudflare via the Enhance sync only after Verpex confirms an IP change also updates the Enhance zone (section 4.5).
- Off-host backups.
- No dependence on `.htaccess` for security until the web server kind is confirmed.

**Why:**
1. **Uses only shipped, default features.** PHP, MySQL, the installer, cron, email and SSL are all on by default. It does not depend on anything missing or unreleased: Node, Git deploy, Redis, Python, staging, or `.htaccess`-only security [C, sections 3.1 and 3.2].
2. **Best-supported app on this platform.** WordPress is the only application Enhance gives a full toolkit, including automatic updates. That toolkit directly reduces the patching work that usually makes WordPress risky for volunteer groups [C, WordPress toolkit docs].
3. **Works whatever web server Verpex runs.** Permalinks work under `.htaccess` or the Nginx URL-rewrite toggle, and caching adapts to the server kind [C, D-WS].
4. **Covers every current feature without custom code**, and is the easiest to hand to non-developer successors [I].
5. **Accepted trade-off:** a bigger attack surface than a static site. Auto-updates, a minimal plugin list, ModSecurity (if enabled), 2FA and off-host backups keep it manageable [I].

**Choose differently if:**
- **(a) SSG + CI + rsync** if at least two long-term maintainers are comfortable with git and Markdown, *and* the member area can move to an external platform or a small PHP gate. It gives the lowest security burden and the best portability.
- **(c) Joomla 5/6** if the current editors strongly prefer Joomla and the member area needs fine-grained access levels. Accept the thinner Enhance support and the fact that Joomla 3 to 5 is a rebuild anyway.
- **Not (d) or (e)**, for the reasons above.

### 5.8 Must confirm with Verpex (or in our own panel) before committing

Several of these can be read from our own panel without contacting support: the website's web server kind, the `canUse` flags, the package allowances and the PHP version list.

1. **Package allowances on our reseller subscription.** The build fails without these:
   - "Allow software installer" with WordPress allowed
   - SSH/SFTP
   - the php.ini editor
   - email accounts and an Email-role mapping
   - the DNS zone editor
   - manual and self-restore backups
2. **The web server kind** on the server hosting our site. It decides:
   - whether `.htaccess` protection of member files works
   - whether WP admin IP lockdown works
   - which caching plugin to use
   - in particular, **LiteSpeed Enterprise or OpenLiteSpeed**: Verpex hosts answer `Server: LiteSpeed`, which fits either, and OLS honours only rewrite rules in `.htaccess`
3. **Email:**
   - the hourly limit for SMTP and for website-generated mail on Enhance servers
   - whether `mail()` is enabled
   - whether outbound SMTP to an external relay (ports 587/465) is allowed
4. **AUP 3.5 and 3.1 sign-off:** a login-protected member documents area, plus a public forms and scripts library of about 100 to 500 MB of PDFs, is acceptable.
5. **Backups:** frequency, retention, and whether self-restore is enabled.
6. **Per-site RAM, vCPU, nproc and inode limits** for sub-packages. [I] A small WordPress site is comfortable at 1 GB RAM and 1 vCPU or more.
7. **Which Enhance version Verpex runs** and how quickly it upgrades.
8. **IP-change handling:** whether Enhance servers get the same IP-change DDoS mitigation, and whether the new IP is written into Enhance-hosted zones (and so pushed to Cloudflare by the sync).

---

## 6. Open questions to ask Verpex support

Each entry gives the question, why it matters, and [P] where we can check it ourselves in the panel first.

**Platform configuration**
1. Which web server kind (Apache, LiteSpeed Enterprise, OpenLiteSpeed or Nginx) runs on the Enhance servers in "US - Central"? Your KB says LiteSpeed runs on all reseller servers and your hosts send `Server: LiteSpeed`: is it LiteSpeed Enterprise or OpenLiteSpeed? Will it change without notice? This decides whether `.htaccess` works (deny rules, Basic Auth, WP admin lockdown). The ToS allows "like for like" changes. [P: website > Developer tools, or `GET /v2/websites/{id}/webserver_kind`]
2. Which Enhance version do you run today, and how soon after an Enhance release do you upgrade? Features we might use landed in 12.18 to 12.25.
3. Which database engine and version do you run: MySQL 8.4 or MariaDB? Is the PostgreSQL role installed? [P: `canUse.mysqlKind`, `canUse.postgresql`]
4. Which PHP versions does the reseller subscription allow? Is PHP 8.4 or 8.5 enabled? [P]
5. Which package allowances does our reseller subscription include? [P for most]
   - SSH, the php.ini editor, the software installer (and which apps)
   - staging websites and website cloning
   - Node.js (persistent apps), Redis
   - the DNS zone editor, manual and self-restore backups, email accounts
   - the future Git Deploy toggle
6. Is ModSecurity (OWASP CRS) enabled on your Enhance web servers, so the per-domain toggle works? Is Roundcube SSO enabled?

**Limits**

7. What are the per-website cgroup limits on reseller sub-packages: memory, vCPU, IOPS, IO bandwidth, nproc and inodes? Your FAQ only says "same or more than the closest cPanel plan".
8. For Enhance servers, what are the hourly limits for SMTP-authenticated mail and for website-generated mail? Is `mail()` enabled, and which envelope sender does it use (the site's domain, a hidden mailbox, or the server hostname)? Is outbound mail relayed through MailChannels or SpamExperts? Can a site connect out to a third-party SMTP relay on 587/465?
9. Do "30 Hosting Accounts" on E30 mean 30 Enhance customers or 30 websites? Is the staging-website quota separate?

**Backups and data**

10. What are the Enhance backup frequency and retention? Are backups stored off-server, and in which region? Can resellers add an S3 target?
11. If an account lapses or is suspended, how long do we have to download our data, and will you export the DNS zone on request?

**Policy**

12. AUP 3.5 (file visibility) and 3.1(e)/(i) (downloads): are these acceptable?
    - a login-protected area of club documents served by the CMS
    - a public library of PDF forms, net scripts and presentations, totalling under 1 GB
13. Is there a charity or non-profit programme that applies to the Enhance reseller product? The policy page is in the cPanel section.

**Network and operations**

14. Which city and datacenter is "US - Central" (Dallas?)? Is any US-West location planned for Enhance?
15. Do Enhance servers get the same "change the IP address" DDoS mitigation seen on the status page? Is there advance notice or a status-email subscription per server? When an IP changes, is the new address written into zones hosted on Enhance DNS (so the one-way Cloudflare sync pushes it)? What do you recommend for customers whose DNS is at Cloudflare?
16. Do resellers get Enhance (orchd) API access tokens, and at what role? This isn't needed for the recommended stack, but it is useful for automation.

---

## 7. Sources

Access date is **2026-09-25** unless noted. "Date" is the freshest date signal seen, such as a page date, sitemap lastmod, release date or HTTP Last-Modified. Undated pages are marked as such.

### 7.1 Keyed sources

| Key | URL | Date |
|---|---|---|
| VPX | https://verpex.com/reseller-hosting-enhance | sitemap lastmod 2026-08-05 |
| UPM | https://api.upmind.io/api/basket/products/{id}?with=prices,attributes,attributes.category&currency_code=USD (ids: E30 `80d1639e-237d-4372-83da-64610589e572`, E80 `1e96d298-537d-4e6e-d36b-54e120637085`, E120 `1e96d298-537d-4e6e-d31f-54e120637085`, E150 `0381d780-e72d-4d83-172a-7413569926e5`), the public catalogue behind VPX's widget https://embed.upmind.app/upm-widget.js. It answers only with the header `Origin: https://verpex.com`; the category listing is https://api.upmind.io/api/basket/products?filter[category_id]=6e2e071d-931d-5e46-287c-546028758396 | products updated 2026-08-27; promo created 2026-05-05 |
| VKB-FAQ | https://kb.verpex.com/enhance/enhance-reseller-hosting-frequently-asked-questions | lastmod 2026-06-28 |
| VKB-MIG | https://kb.verpex.com/enhance/from-cpanel-to-enhance-everything-you-need-to-know | lastmod 2026-06-28 |
| VKB-MAIL | https://kb.verpex.com/emails/email-limits (headed "Email Hosting with cPanel") | dateModified 2026-09-09 |
| VKB-SPEC | https://kb.verpex.com/reseller/reseller-hosting-server-specification (cPanel/CloudLinux-oriented) | dateModified 2026-09-09 |
| UPM-CP | cPanel "Reseller 30" in the same Upmind catalogue: https://api.upmind.io/api/basket/products/7831d635-0d82-493e-255a-849e176259e0 (send header `Origin: https://verpex.com`; without it the API returns 404 "Domain not found") | fetched 2026-09-25 |
| VPX-CP | https://verpex.com/reseller-hosting (cPanel reseller comparison table) | 2026 (products updated 2026-04-15) |
| AUP | https://verpex.com/acceptable-usage-policy | undated |
| TOS | https://verpex.com/terms-conditions-of-service | undated |
| STATUS | https://status.verpex.com ; https://status.verpex.com/incidents ; incidents https://status.verpex.com/incident/3077 , /3096 , /3123 , /3143 | accessed 2026-09-26 UTC; incidents 2026-08-02 to 2026-09-23 |
| RN | https://enhance.com/support/release-notes | last updated 2026-09-21 (12.25.12) |
| RM | https://enhance.com/support/product-roadmap | last updated 2026-07-22 |
| SPEC | https://apidocs.enhance.com/spec/oas3-api.yaml (local copy `_enhance-oas3-api.yaml`) | info.version 12.25.12; Last-Modified 2026-09-21 |
| FEAT | https://enhance.com/product/features ; https://enhance.com/features | undated, © 2026 (mentions July 2026 features) |
| D-WS | https://enhance.com/docs/application-role/webservers.html | undated |
| D-NODE | https://enhance.com/docs/website-tools/nodejs.html | undated (feature from 2025-09-22) |
| D-SSH | https://enhance.com/docs/customers/customer-ssh-useful-commands.html | undated; partly [STALE] (crontab) |
| D-RES | https://enhance.com/docs/resellers/ | undated |
| D-LIM | https://enhance.com/docs/resource-usage/set-per-website-resource-limits.html ; https://enhance.com/docs/packages/system-resource-limits.html | undated (updated for 12.23.0, Jun 2026) |
| D-MAIL | https://enhance.com/docs/email-role/ ; https://enhance.com/docs/email-role/email-settings.html | undated; `mail()` path text probably [STALE] |
| D-STG | https://enhance.com/docs/website-tools/staging-websites.html | undated |

### 7.2 Other Verpex sources

These are all undated unless noted.

- **Enhance KB pages** (lastmod 2026-06-28 unless noted):
  - https://kb.verpex.com/enhance/managing-your-website-databases-and-backups-in-enhance
  - https://kb.verpex.com/enhance/changing-php-versions-and-settings-in-enhance
  - https://kb.verpex.com/enhance/configuring-ssh-access-in-enhance
  - https://kb.verpex.com/enhance/how-to-deploy-a-nodejs-application-on-enhance
  - https://kb.verpex.com/enhance/managing-your-mysql-databases-in-enhance
  - https://kb.verpex.com/enhance/managing-your-domains-in-enhance
  - https://kb.verpex.com/enhance/managing-your-email-in-enhance
  - https://kb.verpex.com/enhance/managing-backups-in-enhance
  - https://kb.verpex.com/enhance/installing-an-ssl-certificate-in-enhance
  - https://kb.verpex.com/enhance/managing-ftp-accounts-in-enhance
  - https://kb.verpex.com/enhance/managing-your-wordpress-site-in-enhance
  - https://kb.verpex.com/enhance/adding-a-new-customer-to-your-enhance-resellers-account
  - https://kb.verpex.com/enhance/enhance-reseller-managing-customers-from-the-panel (lastmod 2026-09-09)
  - https://kb.verpex.com/importing-and-restoring-a-cPanel-account-into-enhance (lastmod 2026-09-09)
- **KB index:** https://kb.verpex.com/llms.txt, showing no Enhance Git or Python articles (checked 2026-09).
- **Email attachment limit:** https://kb.verpex.com/emails/what-is-the-limit-on-the-email-attachment-size
- **Content rules:** https://kb.verpex.com/docs/content-restrictions-and-dmca-compliance ; https://kb.verpex.com/network/can-i-livestream-audio-or-video
- **cPanel-section pages:** https://kb.verpex.com/cpanel/charity-hosting ; https://kb.verpex.com/cpanel/hosting-with-verpex-what-you-should-expect
- **Locations, [STALE]:** https://kb.verpex.com/network/server-overview ; https://kb.verpex.com/network/why-hosting-location-matters
- **Company and product pages:** https://verpex.com/our-technology ; https://verpex.com/about ; https://verpex.com/company-details ; https://verpex.com/web-hosting (products updated 2026-09-08) ; https://verpex.com/en-us-sitemap.xml
- **Contacts and support:** https://kb.verpex.com/billing/support-team ; https://kb.verpex.com/billing/money-back-guarantee

### 7.3 Other Enhance official sources

All pages are undated unless a date is given.

- **Web server and PHP:**
  - https://enhance.com/docs/application-role/
  - https://enhance.com/docs/application-role/switching-application-role.html
  - https://enhance.com/docs/application-role/apache-configurations.html
  - https://enhance.com/docs/website-tools/custom-vhosts.html
  - https://enhance.com/docs/website-tools/nginx/url-rewriting.html
  - https://enhance.com/docs/php/php-versions.html [STALE default]
  - https://enhance.com/docs/php/
  - https://enhance.com/docs/php/php-ini.html
  - https://enhance.com/docs/php/php-extensions.html
  - https://enhance.com/docs/php/end-user/manage-php-extensions.html
  - https://enhance.com/docs/php/end-user/php-logs.html
  - https://enhance.com/docs/php/troubleshooting/php-websites-cannot-connect-to-the-outside-internet.html
  - https://enhance.com/docs/dns-role/customisation-of-dns-resolver-configuration-for-website-containers.html
  - https://enhance.com/product/technology [STALE PHP range]
- **Tools, isolation and Git deploy:**
  - https://enhance.com/docs/website-tools/install-deploy-ghost.html
  - https://enhance.com/docs/technical-guidance/enhance-terminology.html
  - https://enhance.com/docs/security/
  - https://enhance.com/docs/troubleshooting/unable-to-connect-to-ftp.html
  - https://enhance.com/docs/application-role/file-manager.html
  - https://enhance.com/docs/git-deploy/
  - https://enhance.com/docs/git-deploy/deploying-git-repositries.html
- **Staging and domains:**
  - https://enhance.com/docs/platform-settings/platform-domains.html
  - https://enhance.com/docs/website-tools/cloning-websites.html
  - https://enhance.com/docs/website-tools/domains/preview-domain.html
  - https://enhance.com/docs/website-tools/domains/add-a-domain.html
  - https://enhance.com/docs/website-tools/domains/redirect-management.html
- **Databases and caching:**
  - https://enhance.com/docs/database-role/
  - https://enhance.com/docs/database-role/phpmyadmin.html
  - https://enhance.com/docs/website-tools/enabling-redis.html
- **WordPress:**
  - https://enhance.com/docs/wordpress/end-user/about.html
  - https://enhance.com/product/wordpress-toolkit
  - https://enhance.com/docs/wordpress/end-user/restrict-admin-access.html
  - https://enhance.com/docs/wordpress/admin/optimising-for-wordpress.html
  - https://enhance.com/docs/wordpress/admin/wordpress-crons.html
- **SSL and migration:**
  - https://enhance.com/docs/security/about-lets-encrypt.html
  - https://enhance.com/docs/security/end-user/letsencrypt.html
  - https://enhance.com/docs/security/third-party-ssl.html
  - https://enhance.com/docs/migrations/cpanel-importer.html
- **Email and DNS:**
  - https://enhance.com/docs/email-role/email-authentication.html
  - https://enhance.com/docs/email-role/webmail.html
  - https://enhance.com/docs/email-role/end-user/add-mailbox.html
  - https://enhance.com/docs/dns-role/about.html
  - https://enhance.com/docs/dns-role/end-user/dns-cloudflare.html
- **Backups:**
  - https://enhance.com/docs/backup-role/admin/backup-settings.html
  - https://enhance.com/docs/backup-role/end-user/create-backup.html
  - https://enhance.com/docs/backup-role/end-user/restore-backup.html
  - https://enhance.com/docs/backup-role/end-user/download-backup.html
  - https://enhance.com/product/backups
- **Security, logs and access:**
  - https://enhance.com/docs/security/modsecurity.html
  - https://enhance.com/docs/security/end-user/enable-modsecurity.html
  - https://enhance.com/docs/platform-settings/brute-force-protection.html
  - https://enhance.com/docs/technical-guidance/website-access-and-logs.html
  - https://enhance.com/docs/account/end-user/managing-user.html
- **Reseller:**
  - https://enhance.com/docs/resellers/configuring-a-reseller-account.html
  - https://enhance.com/docs/resellers/reseller-tools.html
  - https://enhance.com/docs/resellers/create-reseller-package.html [STALE on resource reservation]
- **General:** https://enhance.com/faqs

### 7.4 Enhance community forum

Each thread was read through the public Flarum JSON API, `https://community.enhance.com/api/posts?filter[discussion]=ID`. The dates are the post dates cited.

- **SSH, SFTP and cron:**
  - https://community.enhance.com/d/2214-enhance-ssh (2024-12 [OLD] to 2026-03)
  - https://community.enhance.com/d/3206-sftp-and-ftp-domain (2025-11)
  - https://community.enhance.com/d/3691-12240-now-available (2026-07)
- **Git deploy:**
  - https://community.enhance.com/d/245-git-integration-with-auto-deploy-logged (2023-02 to 2026-09-21)
  - https://community.enhance.com/d/3814-12256-available (2026-08-28 to 2026-09-02)
- **Release threads:**
  - https://community.enhance.com/d/3853-122512-now-available (2026-09-21)
  - https://community.enhance.com/d/3749-12250-now-available (2026-07-22)
- **Platform direction and complaints:**
  - https://community.enhance.com/d/3655-everything-feels-half-baked-or-stone-age (2026-06)
  - https://community.enhance.com/d/3334-expectations-for-enhance-in-2026 (2026-01)
- **Node.js:**
  - https://community.enhance.com/d/3239-restart-nodejs-app-after-deployment (2025-11 to 2026-07)
  - https://community.enhance.com/d/1432-nodejs (2026-01)
  - https://community.enhance.com/d/3355-nodejs-env (2025-11 to 2026-01)
  - https://community.enhance.com/d/3299-nodejs-proxy-support-for-subdomains-and-config-ui-improvements (2025-12)
  - https://community.enhance.com/d/129-nodejs-support-released (2025-09)
- **Python:** https://community.enhance.com/d/3325-python-apps (2025-12)
- **Redis and caching:** https://community.enhance.com/d/3435-redis-opcode-cache (2026-02)
- **Web servers:**
  - https://community.enhance.com/d/2959-litespeed-enterprise-and-503-error (2025-07 to 2026-01)
  - https://community.enhance.com/d/96-ols-reload-for-htaccess-changes (2025-04/05) [OLD]
  - https://community.enhance.com/d/3403-nginx-vhost-changes-to-location (2026-02)
- **Password protection:** https://community.enhance.com/d/569-password-protect-website-logged (2024-02 [OLD] to 2026-06)
- **Access logs:** https://community.enhance.com/d/2398-website-access-logs (to 2026-08-28)
- **Email:**
  - https://community.enhance.com/d/3494-query-around-php-mail (2026-03/04)
  - https://community.enhance.com/d/3233-wordpress-forms-not-sending-emails-do-i-need-to-enable-the-email-role (2025-11)
  - https://community.enhance.com/d/3113-email-sending-limit-per-package (2025-09)
  - https://community.enhance.com/d/3852-email-phishing-incident (2026-09)
  - https://community.enhance.com/d/51-integration-clamav (2022 to 2026-05)
- **Resource limits:** https://community.enhance.com/d/3837-why-does-a-website-container-see-the-hosts-memory-instead-of-its-package-limit (2026-09-10)
- **Joomla installer:** https://community.enhance.com/d/3473-version-12180-now-available (2026-03)
- **PostgreSQL:** https://community.enhance.com/d/3618-enhance-12220-postgresql-support (2026-05/06)

### 7.5 Independent sources

- **Trustpilot:**
  - https://www.trustpilot.com/review/www.verpex.com , plus the 1-star (`?stars=1`) and 2-star (`?stars=2`) filtered views (2025-05 to 2026-09)
  - https://www.trustpilot.com/review/enhance.com (undated extraction)
- **Reviews:**
  - https://hostadvice.com/hosting-company/verpex-hosting-reviews/verpex-hosting-reseller-hosting-review/ (2026-05, cPanel reseller)
  - https://hostnamy.com/verpex-reseller-hosting/ (2025-12-31, cPanel)
  - https://www.linuxteck.com/guides/verpex-reviews/ (2026-08-18, cPanel shared)
  - https://googiehost.com/blog/verpex-reviews/ (2026)
  - https://hostscore.net/review/verpex/ (2026-09-17)
  - https://hostingcanada.org/verpex-review/ (2026-02-23)
  - https://prehost.com/hosting/verpex/ (2026-09)
  - https://hostingexplorers.com/reviews/verpex/ (2026, undated)
- **Tutorial:** https://redwaterhost.com/blog/host-a-nodejs-app-on-enhance (2026-08-30)
- **LowEndTalk:**
  - https://lowendtalk.com/discussion/217622/enhance-control-panel-experience (2026-05)
  - https://lowendtalk.com/discussion/208079/anyone-using-enhance-control-panel-in-production (2025-07) [OLD]
  - https://lowendtalk.com/discussion/198122/anyone-have-experience-with-world-host-group (2024-09) [OLD]
  - https://lowendtalk.com/discussion/217297/migrating-from-regxa-need-litespeed-enterprise-cpanel-india-singapore-under-60-year-india (2026-05)
- **Ownership and network:**
  - https://www.whtop.com/compare/stablepoint,verpex
  - https://www.whtop.com/review/verpex.com
  - https://hostingjournalist.com/news/world-host-group-brand-a2-hosting-rebrands-to-hosting-com (2025)
  - https://bgp.he.net/AS14670 ; https://bgp.he.net/AS51713
  - https://stat.ripe.net/data/prefix-overview/data.json?resource=194.39.149.92 (looked up 2026-09-25)
- **Unavailable:**
  - reddit.com / old.reddit.com returned HTTP 403, so there is no Reddit evidence either way.
  - cybernews.com returned 403, so the claim that Enhance shares a founder with Verpex is unverified.

---

## Verification (2026-09-25)

Three independent skeptics re-checked 29 load-bearing claims (C1 to C29) against public sources only. Each skeptic returned confirmed, refuted or unverifiable per claim.

### Tally

| Result | Claims |
|---|---|
| 3 of 3 confirmed | C1 to C17, C19, C21 to C28 (26 claims) |
| 2 confirmed, 1 refuted | C20 (backups): refuted only on "frequency not published", because the ToS states a generic twice-daily schedule |
| 2 confirmed, 1 unverifiable | C29 (Verpex's Enhance version): already labelled an inference; no public source states the version |
| **1 confirmed, 2 refuted** | **C18 (PHP `mail()`)**: the only claim below two confirmations |

### Re-check of C18 (below the threshold)

Re-checked directly on 2026-09-25:
- https://enhance.com/docs/email-role/ (undated): a site "mapped to an email role ... will be able to send messages using the sendmail binary or PHP mail(). A drop-in sendmail replacement ... sends through the website's SMTP using a hidden mailbox." [C]
- https://enhance.com/support/release-notes, 11.0.0 (26 Jun 2024) [OLD]: "Website generated emails are now sent from the local MTA rather than being forwarded to the assigned email server for the website. This does not require the email role." [C] The skeptics who refuted C18 relied on the docs page alone and did not weigh this dated note.
- https://community.enhance.com/d/3494-query-around-php-mail (post #3, 28 Mar 2026): the `user@server-hostname` statement is by a non-staff user, about a self-run cluster with a smart host and no email role. [C]
- https://www.trustpilot.com/review/www.verpex.com?stars=2 ("Dave", NZ, 16 Oct 2024, 2 stars) [OLD]: "they turned off the PHP Mail function but didn't bother to tell anyone". [C] Verpex's Enhance products were created in the catalogue on 2026-03-12 (Upmind `created_at` for E30 and E150), so the review almost certainly concerns cPanel reseller hosting [I].

**Outcome.** The original C18 overstated two things: that `mail()` sends as `user@server-hostname` (a community claim, now marked ⚠️ unverified — confirm with Verpex), and that Verpex disabled `mail()` on this product (it was cPanel, 2024). The official sources disagree on how `mail()` is routed, so section 3.2, item 4 stays "not resolvable". The recommendation (authenticated SMTP for forms) is unchanged, now justified by the unknown routing and AUP 3.4(d), which lets Verpex block or queue mail "with or without notifying".

### Changes made

**Corrections from C18:** section 1 (email bullet), section 3.1 "Email sending limits" row, section 3.2 item 4, section 4.3 (`mail()` rationale), section 5.3 (forms rationale), section 6 Q8 (now also asks which envelope sender `mail()` uses).

**Corrections and additions from skeptic notes on confirmed claims:**
- **C1:** "the FAQ is contradicted" became "the FAQ is right about list prices; a new-client promo sits on top" (sections 2.1 and 3.2 item 1). Budget advice unchanged.
- **C2:** added that the catalogue's cPanel "Reseller 30" says "1 vCore & 2GB RAM per user", so the floor is 2 GB RAM and 1 to 2 vCPU (section 2.2; new key UPM-CP).
- **C3:** Dallas kept as inference, with the caveat that the `usc1` hosts are not shown to be Enhance nodes; added "Central Europe" is probably Frankfurt [I] (section 3.1 Datacenters).
- **C4:** added Verpex KB "LiteSpeed on all shared and reseller servers" (2026-09-09) and a `Server: LiteSpeed` header from the host in Verpex's Enhance Node.js guide, re-checked 2026-09-25. The web server is now "unconfirmed, probably LiteSpeed family", and the open question is LiteSpeed Enterprise vs OpenLiteSpeed (sections 1, 3.1, 4.2, 5.3, 5.8, 6 Q1; new key VKB-SPEC).
- **C5:** OLS "rewrites only" detail flagged [OLD] (Apr/May 2025); Nginx `location /` limit now cites https://community.enhance.com/d/3403-nginx-vhost-changes-to-location.
- **C8:** noted that a "generic" persistent app could run Python only if an interpreter happens to exist; still unsupported.
- **C9:** "empty stubs" became "partial stubs".
- **C10:** `~/.my.cnf` now cited to release notes 10.0 and 12.6.0 [OLD], not the SSH docs; SSH package-gating attributed to community posts.
- **C13:** PostgreSQL downgraded from "likely" to "unverified" for Verpex (section 3.2 item 12).
- **C16:** automatic Let's Encrypt is conditional on DNS pointing at the cluster.
- **C17:** SPF/DKIM/DMARC defaults apply only to zones on Enhance DNS; added Verpex AUP 3.4 outbound cloud spam filtering.
- **C19:** VKB-MAIL dated 2026-09-09 and noted as a cPanel-headed page.
- **C20:** the portable download is available "by default", not "always"; the ToS twice-daily statement is generic; S3 backups lack single-file restore.
- **C21:** Basic Auth statement dated Feb 2024 [OLD]; added the PHP-level workaround and the custom-vhost option (Apache/Nginx only).
- **C23:** count changed from "69 of about 77" to "68 to 70 of 77"; incidents are not labelled Enhance; the Cloudflare sync is one-way and helps only if Verpex rewrites the Enhance zone (⚠️ unverified — confirm with Verpex). Trustpilot evidence re-checked and quoted (section 4.5).
- **C24:** the 2 GB OOM figure is one forum user's own non-Verpex package limit, not a Verpex number (sections 3.1 and 4.1).
- **C27:** noted the per-site `getSiteAccessToken` JWT endpoint, which is not a scoped API key.
- **C28:** Collaborator can be granted more than one site; no Cloudflare token management since 12.21.3.

**Effect on section 5.** WordPress on E30 is still the recommendation. Three refinements:
- DNS should stay on Verpex/Enhance nameservers. Cloudflare sync is used only after Verpex confirms IP changes update the Enhance zone.
- LSCache is the likely caching choice.
- Form mail through authenticated SMTP is kept for the corrected reasons above.

Two must-confirm items were added or sharpened in section 5.8: LiteSpeed Enterprise vs OpenLiteSpeed (item 2), and IP-change handling (item 8).
