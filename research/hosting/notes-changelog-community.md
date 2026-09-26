# Enhance changelog, roadmap, and community notes (for the Verpex Enhance reseller target)

- Lens: Enhance release notes, planned-features roadmap, docs, and the community forum. The goal is to separate what exists now (as of 2026-09-25) from what is only planned, and to list the known limitations and complaints that matter to a hosted customer, not a server admin.
- Researched: 2026-09-25. Public sources only. No local credential files were read and no live control-panel API was called. The OpenAPI spec is the public file at https://apidocs.enhance.com/spec/oas3-api.yaml.
- Labels: **[C]** means confirmed in the cited source. **[I]** means my inference. **[STALE]** marks a source older than about 12 months (before about 2025-09-25), or a docs page that later release notes contradict.
- Verpex caveat: Enhance is the control panel. Verpex, the operator, chooses the web server kind, the enabled PHP versions, the package toggles (Node.js, Redis, staging, SSH, Git Deploy, etc.) and the resource limits. Anything below marked "available in Enhance" still has to be enabled on the Verpex reseller package and then on the sub-package we create. See section 7.

---

## 0. Source inventory (with freshness)

| Source | URL | Date / freshness |
|---|---|---|
| Enhance release notes (primary changelog) | https://enhance.com/support/release-notes | Page says "Last updated: 21st September 2026". Latest entry is **12.25.12 (21 Sep 2026)** |
| Enhance product roadmap ("Upcoming releases") | https://enhance.com/support/product-roadmap | Page says "Last updated: 22nd July 2026" |
| Enhance OpenAPI spec (orchd) | https://apidocs.enhance.com/spec/oas3-api.yaml | `info.version: 12.25.12`. HTTP Last-Modified: Mon, 21 Sep 2026 |
| Enhance docs (VuePress) | https://enhance.com/docs/ | Pages carry no dates. Some content is stale (see section 5) |
| Enhance features page | https://enhance.com/product/features | Undated. Mentions PHP 8.5, so written after Nov 2025 |
| Community forum (Flarum) | https://community.enhance.com/ (read through its public JSON API `/api/discussions`, `/api/posts`) | Threads dated individually below |
| Forum release-notes tag | https://community.enhance.com/t/release-notes | Latest thread "12.25.12 now available", 2026-09-21 |
| Verpex Enhance reseller page | https://verpex.com/reseller-hosting-enhance | Fetched 2026-09-25. Plan tables are JS-rendered and not visible in the static HTML |

No GitHub changelog for Enhance was found. The release-notes page and the forum "Release Notes" tag are the canonical changelog.

---

## 1. Current version snapshot (as of 2026-09-25)

- [C] Latest Enhance release is **12.25.12, 21 Sep 2026**. It adds customisable DNS resolver config for website containers. https://enhance.com/support/release-notes and https://community.enhance.com/d/3853-122512-now-available (2026-09-21)
- [C] The API spec version matches (12.25.12). https://apidocs.enhance.com/spec/oas3-api.yaml
- [C] The release cadence is several point releases a month. In Jul to Sep 2026 there were 12.24.0 (3 Jul), 12.24.1, 12.25.0 (20 Jul), and 12.25.1 through 12.25.12 (23 Jul to 21 Sep). https://enhance.com/support/release-notes
- [C] v12.0.0 (13 Feb 2025) re-architected Enhance to apt/systemd packages. orchd and appcd now share one version number. https://enhance.com/support/release-notes **[STALE date, but it is the baseline architecture]**
- [I] Verpex may lag a few point releases behind, because operators run `apt upgrade` on their own schedule. Don't assume Verpex runs 12.25.12 today; ask them.

---

## 2. Feature status: NOW vs PLANNED

### 2.1 Node.js application hosting: **AVAILABLE NOW** (since 12.11.0, 22 Sep 2025)
- [C] "Node.js support" was added in **12.11.0 (22 Sep 2025)**. https://enhance.com/support/release-notes
- [C] Docs: apps run *inside the website container* with the same isolation and resource limits as PHP. You can run multiple Node installs per website. You set a startup command (e.g. `npm start --production`) and a working directory relative to $HOME, and you pick a Node major version (nvm). Enhance proxies a path, or the whole site, to a port inside the container. Ports must be 1024 to 65535. https://enhance.com/docs/website-tools/nodejs.html
- [C] Modes: **Automatic** is for production. It auto-starts and auto-restarts on exit, and logs to `~/persistent_app_<ID>.log`, which is cycled on each start. **Manual** means Enhance won't start or restart the app and writes no logs, but it still proxies. You restart an app by switching Automatic → Manual → Automatic. https://enhance.com/docs/website-tools/nodejs.html
- [C] Enhance docs warn: "We recommend that only experienced system administrators install and manage Node applications. Enhance can not offer app related support." https://enhance.com/docs/website-tools/nodejs.html
- [C] Package prerequisite: Node.js must be enabled on the hosting package. It defaults to ON, but packages created before 22 Sep 2025 default to OFF. It needs outbound access to github.com (nvm). https://enhance.com/docs/website-tools/nodejs.html
- [C] **Only the primary domain is proxied.** Other domains must be aliases redirected via .htaccess. Added 12.13.1, 30 Nov 2025. https://enhance.com/support/release-notes and https://enhance.com/docs/website-tools/nodejs.html
- [C] For a Node app on a subdomain, Enhance staff say "This isn't supported, you would need to add it as a separate website" (2025-12-11). https://community.enhance.com/d/3239-restart-nodejs-app-after-deployment
- [C] `.well-known/acme-challenge` is excluded from the proxy, so Let's Encrypt still works when the root is proxied (12.13.1). https://enhance.com/support/release-notes
- [C] **WebSocket proxy support** was added in **12.23.0 (15 Jun 2026)**. The API has `allowWebSocketUpgrade`. https://enhance.com/support/release-notes and https://apidocs.enhance.com/spec/oas3-api.yaml (`PersistentAppProxyDetails`)
- [C] A cgroup race condition, where auto-started Node apps landed outside the correct control group, was fixed in 12.14.2 (6 Jan 2026). https://enhance.com/support/release-notes
- [C] Warning: "Node.js applications are restored but are not automatically deployed after a website backup restore." The roadmap has auto-deploy on restore as "Approved". https://enhance.com/docs/website-tools/nodejs.html and https://enhance.com/support/product-roadmap
- [C] The UI has no environment-variable field. The founder says: "If you need to set environment variables … create a startup script in bash. Or … use a .env file" (2026-01-06). https://community.enhance.com/d/1432-nodejs. The API `PersistentApp` schema has only `startMode`, `command`, `workingDirectory`, `nodeVersion` and `proxyDetails`, with no env field. https://apidocs.enhance.com/spec/oas3-api.yaml
- [C] The API has no restart endpoint. Users toggle `startMode` manual/automatic via `PATCH /websites/{id}/apps/persistent/{app_id}` (community post 2026-07-13). That user also complained that tokens aren't scoped. https://community.enhance.com/d/3239-restart-nodejs-app-after-deployment
- [C] Complaints: the UX is "feels like 2020", with no wizard, no build logs and no git flow (thread 2026-06-16..29). The founder replied "We're working on git integration". https://community.enhance.com/d/3655-everything-feels-half-baked-or-stone-age. Some users call it "very very early days … in any production" (2026-01-05). https://community.enhance.com/d/1432-nodejs
- [I] For SpokARES, Node is viable for a small app or SSR site, but it is **operator-dependent**: the Node.js package toggle, CPU/RAM limits, and an app that must sit on the primary domain of its own Enhance "website". A static or PHP build avoids all of these caveats.

### 2.2 Python / WSGI app hosting: **NOT AVAILABLE, only "Approved" (not in development)**
- [C] The roadmap lists "Python." under **Approved** → "Website features". Approved items are "yet to be assigned development time… An estimated release date is not available." https://enhance.com/support/product-roadmap (updated 22 Jul 2026)
- [C] There is no Python/WSGI mention anywhere in the release notes through 12.25.12. I searched the full page text. https://enhance.com/support/release-notes
- [C] The OpenAPI spec has no Python endpoints. `PersistentAppKind` enum = `generic`, `openclaw`. https://apidocs.enhance.com/spec/oas3-api.yaml
- [C] The forum feature request "Python Apps" (2025-12-22) has no staff reply. https://community.enhance.com/d/3325-python-apps
- [I] A "generic" persistent app could technically run any long-running process, e.g. a Python server on a port, via a startup command. That is unsupported and undocumented, and the Python runtime available inside the container is unverified. **Do not plan on Python.**

### 2.3 Git deploy: **NOT RELEASED YET, "Under development", announced as imminent**
- [C] The roadmap lists "Git deploy." under **Under development**: "assigned development time… A release date is not available." https://enhance.com/support/product-roadmap (22 Jul 2026)
- [C] The founder (Adam), 2026-08-28: "We are about to release a huge feature, Github integration and auto-deploy." https://community.enhance.com/d/3814-12256-available
- [C] It is still absent from release notes through 12.25.12 (21 Sep 2026). https://enhance.com/support/release-notes
- [C] The long-running request thread "Git integration with auto deploy (LOGGED)" dates from 2023-02-21. Its last post is **2026-09-21**: "WE are also waiting for this very long." https://community.enhance.com/d/245-git-integration-with-auto-deploy-logged
- [C] **Docs stubs are already published**, with sections still empty. "Git deploy" covers "Configuring Git Deploy on a package". "Deploying Git Repositries" covers linking a repo during site creation, from a "Projects" page, or on an existing site's "Git Deploy" page, with auto-deploy on push and one repo deployable to multiple staging/production environments. It also says: "If you do not see the Git Deploy tools … this feature is not included in your current hosting package." https://enhance.com/docs/git-deploy/ and https://enhance.com/docs/git-deploy/deploying-git-repositries.html
- [C] The founder separates this from Node: "Git integration (automatic tracking of a particular branch/tag) is a completely separate feature to Node.js support" (2026-01-06). https://community.enhance.com/d/3334-expectations-for-enhance-in-2026
- [C] Workaround today: `git pull` over SSH. A user says "I know you can pull via the SSH" (2026-04-13). https://community.enhance.com/d/245-git-integration-with-auto-deploy-logged. Another says "We regularly update the app using git pull" (2025-11-23). https://community.enhance.com/d/3239-restart-nodejs-app-after-deployment
- [C] The API spec has `SystemPackageName` enum `[appcd, git]`, so git is a tracked system package on servers. https://apidocs.enhance.com/spec/oas3-api.yaml
- [I] Since appcd 4.0.0 (Jul 2024), "PHP containers now mirror the file system of the host o/s … Software installed on the host o/s can now be accessed within the PHP container" (https://enhance.com/support/release-notes **[STALE date]**). So a host-installed `git` is very likely usable over customer SSH. Verify on Verpex.
- [I] When Git Deploy ships it will be a **package-gated feature**, so Verpex must enable it on the reseller plan. Plan the SpokARES deploy pipeline so it doesn't *depend* on native Git Deploy. Use rsync/SFTP from CI, or SSH + `git pull`. Switch to native Git Deploy if and when Verpex exposes it.

### 2.4 Staging, cloning, push-live: **AVAILABLE NOW** (package + staging-domain gated)
- [C] A staging site doesn't consume a website allowance. It has all website features "except domain mapping and emails", is noindex by default, and robots.txt is excluded from clone and push-live. "Resellers are required to set their own staging domain." The staging domain's nameservers must be delegated to the cluster. The package must allow "Staging websites". https://enhance.com/docs/website-tools/staging-websites.html
- [C] Push staging live to a new or existing live site without deleting staging (10.6, 2 Apr 2024 **[STALE]**). You can WordPress search-replace during push/clone (10.8.0 **[STALE]**). https://enhance.com/support/release-notes
- [C] Recent: "clone to staging" from the website dashboard hero section (12.16.0, 28 Jan 2026). "Push live" now copies cron jobs and IonCube (12.25.0, 20 Jul 2026). Staging domains are noindex by default (12.9.0, 28 Jul 2025). https://enhance.com/support/release-notes
- [C] Cloning needs the package to allow "Website cloning". Email accounts are not cloned. https://enhance.com/docs/website-tools/cloning-websites.html
- [C] Complaints: staging domains not resolving (threads to 2026-06-07). Staging falls back to the live URL. https://community.enhance.com/d/985-staging-domain-doesnt-resolve and https://community.enhance.com/d/3480-staging-site-shows-live-site-url
- [I] As a reseller we would need our own staging domain with NS delegated to Verpex's nameservers, or else just use a second website or subdomain as a manual staging site.

### 2.5 Redis: **AVAILABLE NOW** (per-website, package-gated)
- [C] Redis is per-website, disabled by default, and toggled at Advanced → Developer tools. The package must allow Redis. The user gets connection details and can edit redis.conf. https://enhance.com/docs/website-tools/enabling-redis.html
- [C] "Redis starts inside the site's container and is private for each site." https://enhance.com/product/features
- [C] Redis was introduced around Aug/Sep 2023 (appcd 1.8.0 "Preparation for Redis support"). https://enhance.com/support/release-notes **[STALE date, feature still current]**
- [C] 12.22.0 (26 May 2026): `dump.rdb` is excluded from website cloning. https://enhance.com/support/release-notes
- [C] Complaint (2026-02-24/25): "redis is crashing randomly and won't auto restart… enhance doesn't monitor none of the critical services." That user runs external Redis for critical sites. https://community.enhance.com/d/3435-redis-opcode-cache
- [C] Opcode cache is a per-site toggle. Per the thread it is not on by default on older PHP; it is compiled in and on by default in PHP 8.5. https://community.enhance.com/d/3435-redis-opcode-cache and https://enhance.com/product/features
- [I] SpokARES doesn't need Redis. Don't depend on it.

### 2.6 Web servers / LiteSpeed / .htaccess: **AVAILABLE, but the kind is chosen by Verpex**
- [C] Supported kinds are Apache, LiteSpeed (Enterprise, paid), OpenLiteSpeed and Nginx. The kind is chosen per server by the operator. https://enhance.com/docs/application-role/ and https://enhance.com/docs/application-role/webservers.html
- [C] **.htaccess support: Apache = Full, LiteSpeed = Full, OpenLiteSpeed = Partial, Nginx = None.** LS caching works on LiteSpeed and OLS. FastCGI cache is Nginx only. Custom vhosts work on Apache, LiteSpeed and Nginx, but not OLS. https://enhance.com/docs/application-role/webservers.html
- [C] On OLS, .htaccess honours rewrite rules only, not `Header` directives (community, 2025-05-01). https://community.enhance.com/d/96-ols-reload-for-htaccess-changes. Request for fuller OLS .htaccess support: 2026-07-05. https://community.enhance.com/d/3692-htaccess-support-for-openlitespeed
- [C] LiteSpeed commercial default was bumped to **6.3.7** in **12.25.11 (14 Sep 2026)**. https://enhance.com/support/release-notes
- [C] 12.12.0 (20 Nov 2025): on LiteSpeed/OLS, admins can limit `LSAPI_CHILDREN` per website. PHP-FPM settings are hidden for LS/OLS. https://enhance.com/support/release-notes
- [C] Known issue: **LiteSpeed Enterprise 503s** under load or resource limits. Users report it happens on LSE but not on the same site under OLS (thread 2025-07 to 2026-01-08). https://community.enhance.com/d/2959-litespeed-enterprise-and-503-error. A user lists an "auto fix 503 errors feature of LiteSpeed" as a blocker (2026-09-02). https://community.enhance.com/d/3814-12256-available
- [C] Nginx: the founder says "no .htaccess support"; you can't override the `location /` block, only add includes (2026-01-06, 2026-06-18). https://community.enhance.com/d/3334-expectations-for-enhance-in-2026 and https://community.enhance.com/d/3655-everything-feels-half-baked-or-stone-age
- [C] A Verpex marketing page (search snippet) says its reseller plans "run on LiteSpeed". That snippet may describe the cPanel reseller product, not Enhance. https://verpex.com/reseller-hosting (via web search 2026-09-25). **Unverified for the Enhance product.**
- [I] **Design for portability.** Keep the site working with no reliance on .htaccess beyond basic rewrites and redirects. Put security headers in app code (PHP) or a `<meta>`, or rely on a static build. That way it works on OLS or Nginx if Verpex runs them. Ask Verpex which web server kind the Enhance reseller servers use.

### 2.7 PHP versions: **8.4 and 8.5 AVAILABLE NOW**
- [C] **PHP 8.5 support added in 12.13.0 (27 Nov 2025).** At launch, IonCube and PECL imagick weren't supported on 8.5. The operator must enable "PHP 8.5" on the package. https://enhance.com/support/release-notes
- [C] imagick was enabled by default on PHP 8.5, and IMAP by default on 8.4 and 8.5 (12.15.0, 21 Jan 2026). https://enhance.com/support/release-notes
- [C] Latest PHP packages (28 Aug 2026): **8.5.10, 8.4.25, 8.3.33, 8.2.33**. https://enhance.com/support/release-notes
- [C] Supported by default: 5.6, 7.0 to 7.4, 8.0 to 8.5. 5.2 to 5.5 are optional legacy (12.2.0, Apr 2025). https://enhance.com/product/features and https://enhance.com/support/release-notes. The API `PhpVersion` enum includes php52..php85. https://apidocs.enhance.com/spec/oas3-api.yaml
- [C] The default PHP version for new websites and packages is **8.3** (12.18.0, 16 Mar 2026). https://enhance.com/support/release-notes
- [C] 12.21.0 (3 Apr 2026): customers can enable any extension the operator has installed. Extensions persist across migration. Incompatible extensions are auto-disabled on version change. All PHP 8.x packages ship `pgsql`/`pdo_pgsql` (disabled by default). https://enhance.com/support/release-notes
- [C] 12.23.0 (15 Jun 2026): default `post_max_size`/`upload_max_filesize` is **1000M** when no limit is configured. https://enhance.com/support/release-notes
- [C] 12.9.0 (28 Jul 2025): PHP SAPI starts on demand, so idle sites use about 2 MB RAM instead of about 60 MB. https://enhance.com/support/release-notes
- [C] Per-website php.ini directives exist. php-fpm overrides are owner/reseller only. https://enhance.com/docs/php/ and https://enhance.com/product/features
- [I] Which PHP versions Verpex exposes is Verpex's choice. Target PHP 8.3 as the floor (the Enhance default) and test on 8.4 and 8.5.

### 2.8 Databases: MySQL/MariaDB **now**; PostgreSQL **now (optional role)**
- [C] 12.22.0 (26 May 2026): operators can optionally install a PostgreSQL role. `pgsql`/`pdo_pgsql` are auto-enabled when a customer creates a PG database. https://enhance.com/support/release-notes and https://community.enhance.com/d/3618-enhance-12220-postgresql-support
- [C] PostgreSQL is **16** (Ubuntu 24.04). The founder says Ubuntu 26.04 (PG 18) support is coming "soon" (2026-06-18). https://community.enhance.com/d/3655-everything-feels-half-baked-or-stone-age
- [C] MySQL 8.4 is now the default when MySQL is selected (12.21.0, 3 Apr 2026). The MariaDB LTS install sets utf8mb4 (12.22.0). phpMyAdmin with SSO. https://enhance.com/support/release-notes
- [I] PostgreSQL availability depends on whether Verpex installed the role. Assume MySQL/MariaDB only.

### 2.9 Email: **AVAILABLE NOW** (if Verpex installed the Email role and the package allows it)
- [C] The Email role is Dovecot (POP/IMAP), Postfix (SMTP) and rspamd (spam). You get mailboxes and forwarder-only accounts. https://enhance.com/docs/email-role/
- [C] Features: mailboxes, aliases and forwarders, auto-responders, catch-all (12.8.0, 22 Jul 2025), inbound rspamd filtering with allow/block lists, SPF and DKIM, Gmail auto-config, and Roundcube webmail with SSO. https://enhance.com/product/features and https://enhance.com/docs/email-role/end-user/catch-all-email-addresses.html
- [C] **Default DMARC `p=none`** is added to all new zones (12.25.0, 20 Jul 2026). The default SPF is `v=spf1 a mx ~all`. DKIM is enabled per domain. https://enhance.com/support/release-notes and https://enhance.com/docs/email-role/email-authentication.html
- [C] Per-server Roundcube lives at `https://mail.{customer_domain}`. Password change is in Roundcube (12.13.0, 27 Nov 2025). Roundcube SSO arrived in 12.17.0 (2 Mar 2026). Roundcube auto-updates (currently 1.6.17 per 12.24.1). https://enhance.com/support/release-notes and https://enhance.com/docs/email-role/webmail.html
- [C] Outbound rate limiting, outbound spam scanning, and blocking of messages over the limit (sender gets an error, no deferral). https://enhance.com/docs/email-role/email-settings.html
- [C] **NOT available (roadmap "Approved")**: email piping, mail filtering rules (sieve) in the panel or webmail, virus scanning, HAM/SPAM learning, email queue UI, domain-level allow/block lists. Domain allow/block and per-website sending limits are "Under development". https://enhance.com/support/product-roadmap
- [C] Feature request for HTML auto-replies (2023-11 to 2024-10, no staff reply) **[STALE]**. https://community.enhance.com/d/972-out-of-office-autoresponder
- [C] ClamAV/virus scanning has been requested since 2022. It is still missing as of 2026-05-30. https://community.enhance.com/d/51-integration-clamav
- [C] **PHP mail() deliverability gotcha.** Website mail goes out through the local MTA (since 11.0.0, Jun 2024 **[STALE date]**). Community (2026-03-28): "Enhance wordpress installations / PHPmail, by default, send email as <username>@<server-hostname> … they will flatout reject your emails." The advice is an SMTP plugin or library that logs into a real mailbox, or a transactional provider. https://community.enhance.com/d/3494-query-around-php-mail
- [I] **For the SpokARES contact/join forms, send via authenticated SMTP (a real mailbox on the domain, or a transactional provider), not bare `mail()`.** Ask Verpex whether their Enhance plan includes the Email role, or whether mail stays with the club's current provider.

### 2.10 Backups: **AVAILABLE NOW, recently improved**
- [C] Incremental backups use the Enhance Backup role (hard-link snapshots over SSH). S3-compatible backups are optional. https://enhance.com/docs/backup-role/admin/about and https://enhance.com/product/features
- [C] **Individual file restore** from Enhance backups arrived in 12.15.0 (21 Jan 2026). S3 backups are tagged "S3" and do *not* allow granular file restores. https://enhance.com/support/release-notes
- [C] **Download/upload a full website backup** (tar.gz, streamed) arrived in 12.10.0 (27 Aug 2025). It is available to end users by default, independent of the backup role. The download excludes cron jobs. On OLS/LiteSpeed control panels, downloads are limited to 2 GB. https://enhance.com/docs/backup-role/end-user/download-backup.html
- [C] Restore types are all mailboxes, full website (files + DBs, no email), or custom (any combination). DB users are only restored if all DBs are selected. https://enhance.com/docs/backup-role/end-user/restore-backup.html
- [C] 12.12.0 (20 Nov 2025): backups can be disabled per website (override). 12.22.0: DB size is shown in the backup listing. 12.25.9 (11 Sep 2026) fixed a regression in the UI for granular restore "which could cause inadvertent restoration of files". https://enhance.com/support/release-notes
- [C] Frequency and retention are global settings set by the operator: min/max backup age, retention, allowed hours. https://enhance.com/docs/backup-role/admin/backup-settings.html
- [C] Roadmap "Approved" (not yet built): exclude directories from backup, backup settings per package with per-website overrides, FTP/SSH backup targets. https://enhance.com/support/product-roadmap
- [I] Backup frequency and retention on Verpex are Verpex's settings, so ask. Keep our own off-host copy of the repo and DB dumps anyway.

### 2.11 Cron, SSH, SFTP/FTP
- [C] Cron jobs are managed in the UI (Developer tools). 12.9.0 fixed cron entries being overwritten when the crontab had comments or env vars. https://enhance.com/support/release-notes
- [C] **12.24.0 (3 Jul 2026)**: "Customers can now enable the ability to edit cron jobs with SSH … via the 'developer tools' section." API: `/websites/{id}/container_cron_enabled`. https://enhance.com/support/release-notes and https://apidocs.enhance.com/spec/oas3-api.yaml
- [C] SSH access uses key or password auth and lands inside the website container. Tools available include `/usr/bin/php` (the site's version), `composer`, `wp-cli`, `top`, `ssh` and `rsync`. https://enhance.com/docs/customers/customer-ssh-useful-commands.html and https://enhance.com/product/features
- [C] SSH can be turned on or off per package. Per-site override needs root hacks on the server (community, 2026-03-25). https://community.enhance.com/d/2214-enhance-ssh
- [C] FTP is pure-ftpd with TLS (12.0.20 fix). FTP accounts are managed in the UI. https://enhance.com/docs/application-role/ and https://enhance.com/support/release-notes. Let's Encrypt for FTP is still a request (thread to 2026-05-26). https://community.enhance.com/d/2600-add-request-lets-encrypt-ssl-for-ftp
- [I] SFTP works over the customer SSH login (port 22) when SSH is enabled on the package. This is not confirmed in a current docs page. Ask Verpex whether SSH is enabled on reseller sub-packages.

### 2.12 Password-protecting directories, IP allow/block, redirects
- [C] **There is no UI to password-protect a website or directory.** The roadmap lists "Ability to password protect websites" as **Approved** (not in development). https://enhance.com/support/product-roadmap
- [C] The founder says the manual `.htaccess`/`.htpasswd` Basic Auth "method is fine for Apache and (I think) LiteSpeed. It won't work in OpenLiteSpeed or Nginx" (2024-02-12 **[STALE, still open as of 2026-06-17]**). https://community.enhance.com/d/569-password-protect-website-logged
- [C] There is an IP manager (allow/block IPs) and a 301/302 redirect manager. https://enhance.com/product/features and https://enhance.com/docs/website-tools/domains/redirect-management.html. Both API endpoints work on `.htaccess` (`/orgs/{org}/websites/{id}/htaccess` "Endpoints for managing .htaccess rewrites"; `/htaccess/ips`). https://apidocs.enhance.com/spec/oas3-api.yaml
- [C] The roadmap "Approved" item is a "New redirect manager and IP block/allow manager. This will write directly to the web server config to ensure consistency across all web server kinds." The founder: "The redirect manager is one of the oldest parts of the panel and needs some work, coming soon" (2026-06-16). https://enhance.com/support/product-roadmap and https://community.enhance.com/d/3655-everything-feels-half-baked-or-stone-age
- [I] The current redirect and IP managers are .htaccess-based, so they are unreliable on Nginx or OLS. **The SpokARES member area must use application-level auth**: PHP sessions, or a CMS login. Don't use HTTP Basic Auth via .htaccess, and don't use a panel feature.

### 2.13 Logs
- [C] Website access logs are processed into stats and **discarded roughly every 5 minutes**. Path: `/var/local/enhance/webserver_logs/{uuid}.log`. https://enhance.com/docs/technical-guidance/website-access-and-logs.html
- [C] Users have complained about this since 2025 and it is still open (2026-08-28: "Is there already a solution to keep access logs for at least 30 days?"). https://community.enhance.com/d/2398-website-access-logs. See also https://community.enhance.com/d/2368-webserver-access-logs-no-history
- [C] PHP error log location is shown in the UI. "Improved PHP logs" is on the Approved roadmap. https://enhance.com/support/product-roadmap
- [I] Don't count on server access logs for analytics or auditing. Use app-level logging or privacy-friendly JS analytics if needed.

### 2.14 One-click apps
- [C] WordPress (with a full WordPress Toolkit), WordPress + WooCommerce, and **Joomla** (one-click installer added 12.18.0, 16 Mar 2026) when the package has "Allow software installer". OpenClaw was added in 12.24.0 (3 Jul 2026). API `WebsiteAppKind` = `wordpress`, `joomla`, `openclaw`. https://enhance.com/support/release-notes and https://apidocs.enhance.com/spec/oas3-api.yaml
- [C] There was a Joomla password-length bug at launch: Joomla needs 12 characters but the UI allowed 10. It was addressed in 12.19.0 ("Joomla installation now allows 10 character passwords"). https://community.enhance.com/d/3473-version-12180-now-available and https://enhance.com/support/release-notes
- [C] Packages can restrict installable apps (12.24.0). https://enhance.com/support/release-notes
- [C] Roadmap "Approved": Laravel, Moodle, Magento and n8n auto-installers. https://enhance.com/support/product-roadmap
- [I] Joomla toolkit depth (updates, extension management) is unclear. The release notes mention "WordPress/Joomla toolkit" tasks in 12.21.6 (10 May 2026), but no Joomla toolkit docs page was found. If SpokARES stays on Joomla (upgraded to 5.x), the one-click installer is available, but a Joomla 3 → 5 migration is a manual job.

### 2.15 Security (customer-relevant)
- [C] Each website runs in an isolated container, and SSH and cron run in the same container as PHP. https://enhance.com/docs/security/
- [C] 12.21.5 (8 May 2026): known setuid binaries are hidden from website containers (LPE mitigations). 12.21.6: `mailq`/`postqueue` are hidden. 12.25.3 (1 Aug 2026): SVG uploads are checked for JavaScript. 12.25.5: improved network isolation for ephemeral containers. https://enhance.com/support/release-notes
- [C] Available: ModSecurity (OWASP) per domain, Let's Encrypt auto-SSL, 2FA and Force HTTPS. https://enhance.com/product/features
- [C] Roadmap "Approved" (missing): firewall management UI, WordPress anti-malware lockdown, and a plugin framework for third-party tools. There is no built-in malware scanner (see the ClamAV thread). https://enhance.com/support/product-roadmap
- [C] Community: API tokens aren't scoped per website ("enhance doesnt have scoped key yet") (2026-07-13). https://community.enhance.com/d/3239-restart-nodejs-app-after-deployment. 12.8.0 (22 Jul 2025) added IP lockdown for access tokens and editable token roles. https://enhance.com/support/release-notes

### 2.16 DNS
- [C] PowerDNS clusters. DS/CAA records (12.12.0, Nov 2025). DNSSEC. Cloudflare sync (which wipes and re-adds all records per change, per a community report on 2026-07-22). Third-party DNS hooks (12.25.0, 20 Jul 2026). https://enhance.com/support/release-notes, https://enhance.com/product/features and https://community.enhance.com/d/3749-12250-now-available
- [C] Roadmap "Approved": wildcard subdomains, AXFR from trusted IPs, bind zone import. https://enhance.com/support/product-roadmap

---

## 3. Roadmap snapshot (verbatim categories, updated 22 Jul 2026)

Source: https://enhance.com/support/product-roadmap

**Under development** (dev time assigned, no date): Git deploy · CPU graphing · white/black-list an entire email domain · per-website sending limits with per-website/per-mailbox overrides · identification of bulk senders · stop/rollback a migration between servers · share Backup roles between clusters.

**Approved (no dev time assigned). Items relevant to us:**
- Website: **Python**, Tomcat, .NET Core, auto-installers (Moodle, Magento, Laravel, n8n), new redirect/IP manager, WP templates, file-manager trash, WP anti-malware, performance analysis, **password protect websites**, per-site CPU/mem/IO graphs, improved PHP logs, phpMyAdmin per web server, global WP manager, search-indexing toggle, **wildcard subdomains**.
- Server: OLS admin customisations persisting; groupware; uninstall Backup role.
- Email: domain allow/block, **email piping**, queue UI, routing auto-detect, **virus scanning**, HAM/SPAM learning, **sieve filtering rules**, change outgoing IP in UI, Roundcube contacts on migration, mail SSL status.
- Architecture: other distros, LXC/Virtuozzo, IPv6-only, **custom SSH ports**, ARM, Cloudflare real-IP passthrough on all web servers, a framework for custom PHP modules, a task queue.
- Backups: exclude directories, FTP/SSH targets, per-package settings, **auto-deploy Node app on restore**.
- Security: firewall UI, brute-force protection for non-Enhance services, SAML for the master org, audit logging.

Community note: the roadmap **no longer shows target dates**. Per a user on 2026-07-11 ("You even removed the target dates from the roadmap"). https://community.enhance.com/d/3691-12240-now-available

---

## 4. Known limitations and common complaints (hosted-customer view)

1. **Slow delivery of long-requested features** is the dominant forum theme in 2026. Git deploy (requested 2023), password protection (2023), ClamAV (2022), access-log retention, OLS config persistence and the redirect manager are all cited. Users were angry that OpenClaw shipped (unplanned) ahead of roadmap items (thread 2026-07-03..20). https://community.enhance.com/d/3691-12240-now-available. The founder's response is to stress stability and security over speed (2026-08-28). https://community.enhance.com/d/3814-12256-available
2. Node.js UX: no wizard, no git, no env-var UI, restart only by toggling mode, primary-domain-only proxy, subdomain needs a separate website. https://community.enhance.com/d/3655-everything-feels-half-baked-or-stone-age and https://community.enhance.com/d/3239-restart-nodejs-app-after-deployment
3. Nginx: no .htaccess, and you can't override `location /`. OLS: only partial .htaccess (rewrites only). https://enhance.com/docs/application-role/webservers.html and https://community.enhance.com/d/96-ols-reload-for-htaccess-changes
4. LiteSpeed Enterprise 503s under resource pressure. https://community.enhance.com/d/2959-litespeed-enterprise-and-503-error
5. Redis in the container can crash without auto-restart, and there is no service monitoring. https://community.enhance.com/d/3435-redis-opcode-cache
6. Access logs are discarded about every 5 minutes. https://community.enhance.com/d/2398-website-access-logs
7. PHP mail() sends from the server hostname, so it is poorly deliverable without an SMTP plugin or library. https://community.enhance.com/d/3494-query-around-php-mail
8. Staging domain DNS resolution problems. https://community.enhance.com/d/985-staging-domain-doesnt-resolve
9. "Advanced" menu sprawl. Node.js and Developer tools are hidden under Advanced, and a "Quick Links" section was added in 12.25.0 because users couldn't find tools. https://community.enhance.com/d/3749-12250-now-available
10. Resource limits are hard. "Websites that exceed their given system resource limits will break in an unexpected undefined way." The OOM killer, CPU throttling and IOPS hangs apply to PHP, Node, cron and SSH alike. https://enhance.com/docs/resource-usage/set-per-website-resource-limits.html. An inode limit per website was added in 12.23.0 (15 Jun 2026). https://enhance.com/support/release-notes

---

## 5. Reseller-specific facts (our position as a Verpex Enhance reseller)

- [C] "Resellers can limit storage and bandwidth but not system resources." So CPU, RAM, IOPS and nproc come from Verpex's package, and we can't raise them for our own sites. https://enhance.com/docs/packages/system-resource-limits.html
- [C] Resellers can brand the panel, set custom control-panel, phpMyAdmin, **staging** and Roundcube domains, set custom nameservers, and create custom packages within their own limits. https://enhance.com/docs/resellers/reseller-tools.html
- [C] **12.25.9 (11 Sep 2026):** reseller resources are now consumed when the client *creates* a resource, not at subscription time. https://enhance.com/support/release-notes. (The reseller-package doc still describes the old behaviour; see section 6.)
- [C] Custom virtual-host config (Apache/Nginx) is available to the Master Organisation and Resellers only. https://enhance.com/docs/website-tools/custom-vhosts.html
- [C] php-fpm overrides per website are Owner/Reseller only, not end users. https://enhance.com/docs/php/
- [C] No Verpex-specific threads were found on the Enhance forum (searched "verpex"). https://community.enhance.com/ (API search)
- [C, unverified third party] A web-search snippet says Enhance "is developed by the same founder behind Verpex". Source: https://cybernews.com/best-web-hosting/verpex-hosting-review/ (HTTP 403 on fetch, could not verify). Treat as unconfirmed.

---

## 6. Docs vs release-notes discrepancies (stale docs to ignore)

| Topic | Docs say | Release notes say (newer) |
|---|---|---|
| Crontab over SSH | "it is not possible to edit crontab from the customer's SSH environment" (https://enhance.com/docs/customers/customer-ssh-useful-commands.html) | 12.24.0 (3 Jul 2026) lets customers enable SSH crontab editing via Developer tools |
| PHP mail() path | "A drop-in sendmail replacement … sends through the website's SMTP using a hidden mailbox" (https://enhance.com/docs/email-role/) | 11.0.0 (26 Jun 2024): "Website generated emails are now sent from the local MTA". The community confirms mail() goes out as user@server-hostname (2026-03) |
| Reseller resource reservation | "resources are immediately reserved … when a reseller subscribes a customer" (https://enhance.com/docs/resellers/create-reseller-package.html) | 12.25.9 (11 Sep 2026): consumed on resource creation |
| Git deploy | Docs stubs exist (https://enhance.com/docs/git-deploy/deploying-git-repositries.html) | Not yet released as of 12.25.12. Roadmap: Under development |
| Node.js restart | Features page: "View logs, update mode, restart" (https://enhance.com/product/features) | Docs: restart = toggle Automatic→Manual→Automatic. There is no restart API endpoint |

---

## 7. Implications for the SpokARES rebuild (all [I] unless noted)

1. **The safest target is a static site, or PHP 8.3+ with MySQL/MariaDB.** It needs only a web root, optionally PHP, and optionally one DB. It works on every web server kind Verpex might run, needs no package toggles beyond the defaults, and survives backup/restore without redeploy steps (unlike Node).
2. **Avoid a Python backend.** It is not supported now and not in development.
3. **Node.js is possible but fragile for a volunteer org.** It needs a package toggle, lives on the primary domain of its own "website", has no env UI and no auto-redeploy on restore, and depends on Verpex's CPU/RAM limits. Use it only if we need a server runtime that PHP can't provide.
4. **Deployment:** assume no native Git Deploy yet. Use CI → rsync/SFTP, or SSH + `git pull`. Re-evaluate once Git Deploy ships and Verpex enables it on the package.
5. **Member login area:** implement auth in the application, not via .htaccess Basic Auth, because there is no panel password-protect feature and .htaccess auth fails on OLS or Nginx.
6. **Forms and email:** use authenticated SMTP (mailbox on the club domain, or a transactional provider). Set SPF, DKIM and DMARC. The DMARC default is p=none.
7. **Events calendar, downloads, weather:** implement as static or PHP features, or client-side embeds. Don't rely on cron editing over SSH, which is package/UI gated. The UI cron editor is fine.
8. **Portability:** keep .htaccess use minimal (rewrites only), put security headers in app code, and don't depend on server access logs.
9. **Backups:** the Enhance backups (granular file restore since Jan 2026, downloadable tar.gz) are good. Still keep the repo plus periodic DB dumps off-host.

## 8. Open questions for Verpex (can't be answered from Enhance public sources)
- Which web server kind (Apache / LiteSpeed Enterprise / OLS / Nginx) do the Enhance reseller servers run?
- Which Enhance version are they on today, and how fast do they apply updates?
- Which package toggles are on for resellers: Node.js, Redis, staging, cloning, SSH, software installer, (future) Git Deploy, PHP 8.4/8.5, PostgreSQL?
- Is the Email role included? Is a smart host configured? Is outbound port 25/587 open?
- What are the backup frequency and retention? Is there an S3 secondary?
- What are the per-website CPU/RAM/IOPS/inode limits on reseller sub-packages?
