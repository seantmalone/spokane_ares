# Enhance control panel: what the official docs say (lens: Enhance docs, feature pages, release notes, API spec)

Researched 2026-09-25 for the spokares.org rebuild, which will run on Verpex Enhance reseller hosting (https://verpex.com/reseller-hosting-enhance).

Only public sources were used. I did not read any local credential file and did not call any live control-panel API. The OpenAPI spec is a public static file; I used it only as documentation.

Labels used in these notes:
- **CONFIRMED**: seen in a cited source.
- **INFERENCE**: my reasoning from the sources.
- **STALE?**: the source is older than about 12 months, or it disagrees with a newer source.

## 0. Sources and how current they are

| Source | URL | Date / freshness |
|---|---|---|
| Enhance docs (VuePress, 239 pages; all downloaded) | https://enhance.com/docs/ | Pages carry **no dates** (VuePress `lastUpdated:false`, no Last-Modified header). Snapshot taken 2026-09-25. Several pages are visibly stale compared with the release notes (see below). |
| Enhance release notes | https://enhance.com/support/release-notes | "Last updated: 21st September 2026", latest = **12.25.12 (21 Sep 2026)**. This is the most reliable dated source. |
| Enhance product roadmap | https://enhance.com/support/product-roadmap | "Last updated: 22nd July 2026" |
| Enhance features page | https://enhance.com/product/features | Undated; © 2026; mentions 1-click OpenClaw (added July 2026), so it was updated recently |
| Enhance technology page | https://enhance.com/product/technology | Undated. Says "PHP versions 5.6 - 8.3", so it is **stale** (8.4 and 8.5 are supported). |
| Enhance security / backups / WP toolkit / architecture / FAQ pages | https://enhance.com/product/security , /product/backups , /product/wordpress-toolkit , /product/architecture , /faqs | Undated, © 2026 |
| orchd OpenAPI spec | https://apidocs.enhance.com/spec/oas3-api.yaml | `info.version: 12.25.12`, Last-Modified: Mon, 21 Sep 2026 |
| Enhance community forum (Flarum). Staff member "Adam" posts there. | community.enhance.com threads cited inline | Each post is dated; dates given inline |
| Verpex product page | https://verpex.com/reseller-hosting-enhance | Marketing only: WordPress toolkit, incremental backups, clustering, containers, cPanel/Plesk importer. It does **not** say which web server, PHP versions, limits or Node.js status Verpex provides. |

---

## 1. Web server: Apache / nginx / LiteSpeed Enterprise / OpenLiteSpeed

- **CONFIRMED: web server is chosen per server, not per site.** From the Application role page: "When installing the application role, you can choose your preferred webserver (Apache, LiteSpeed, OpenLiteSpeed, or Nginx). This selection is per-role, allowing you to run different webservers across your Enhance cluster." https://enhance.com/docs/application-role/
  - The operator can switch it at any time, with 30 seconds to 10 minutes of downtime. Switching to LiteSpeed restarts php-fpm containers as lsphp. https://enhance.com/docs/application-role/switching-application-role.html
  - The API matches this. `setWebserverKind` is `PUT /servers/{server_id}/webserver` (server-level only). Websites get only a read-only `GET /v2/websites/{website_id}/webserver_kind`. WebserverKind enum = `liteSpeed, openLiteSpeed, dummyWebServer, apache, nginx`. https://apidocs.enhance.com/spec/oas3-api.yaml
  - **INFERENCE:** as a Verpex reseller or end customer we **cannot choose** the web server. We get whatever Verpex runs on the app server our site lands on. The Enhance docs do not say which one Verpex uses. Check this with Verpex, or with a read-only look at the website's web server kind in our own panel.
- **CONFIRMED: .htaccess support differs by server kind.** Apache = Full, LiteSpeed = Full, OpenLiteSpeed = **Partial**, Nginx = **None**. https://enhance.com/docs/application-role/webservers.html
  - The "Small hosting provider cluster" guidance recommends Apache or LiteSpeed for full .htaccess support. It says "NGINX and OpenLiteSpeed do not support .htaccess and will require manual intervention". https://enhance.com/docs/technical-guidance/cluster-configuration.html
- **CONFIRMED: PHP SAPI follows the web server.** php-fpm is used on Apache and Nginx; LSPHP on LiteSpeed and OLS. It is reconfigured automatically when the server kind changes. https://enhance.com/product/technology
- **CONFIRMED: caching per server kind.** LS cache is available on LiteSpeed and OLS. FastCGI cache is Nginx only. https://enhance.com/docs/application-role/webservers.html
- **CONFIRMED: Nginx per-domain tools** (under Developer tools, Nginx card): FastCGI cache, cache exclusion paths, purge cache, and "URL rewriting" (a front-controller rewrite to PHP for permalinks). https://enhance.com/docs/website-tools/nginx/fastcgi-caching.html , https://enhance.com/docs/website-tools/nginx/url-rewriting.html . API: `/v2/domains/{id}/nginx_fastcgi`, `/nginx_fastcgi_excluded_paths`, `/v2/domains/{id}/webserver_rewrites`.
- **CONFIRMED: Apache modules enabled by default** include mod_rewrite, headers, expires, deflate, proxy/proxy_fcgi/proxy_http, http2, auth_basic, authn_file, authz_*, security2, and others. These cannot be disabled. https://enhance.com/docs/application-role/apache-configurations.html
- **CONFIRMED: custom vhost config ("Overrides > VirtualHost config")** is "only available to the 'Master Organisation' and 'Resellers'", and only for Apache and Nginx. It can be set per addon, alias or subdomain. https://enhance.com/docs/website-tools/custom-vhosts.html . API `VhostWebserverKind` enum = `apache, nginx`.
  - **INFERENCE:** as a Verpex *reseller* we may have this tool. It does nothing if Verpex runs LiteSpeed or OLS.
- **CONFIRMED: HTTP/3 (QUIC).** UDP 443 is exposed for QUIC on LiteSpeed and OLS (Appcd 1.0.16; the release notes date it "29 May 2024", which looks like a bulk date for older entries). **STALE?** https://enhance.com/support/release-notes
- **CONFIRMED: roadmap (approved, not scheduled)** includes "Enable passthrough of 'real' user IP from known Cloudflare IPs across all web server kinds". **INFERENCE:** if we put Cloudflare's proxy in front, PHP may see Cloudflare IPs rather than visitor IPs. https://enhance.com/support/product-roadmap

## 2. PHP

- **CONFIRMED: versions supported by default** are 5.6, 7.0–7.4, 8.0, 8.1, 8.2, 8.3, 8.4 and 8.5. Legacy 5.2–5.5 is an optional manual install. https://enhance.com/docs/php/php-versions.html , https://enhance.com/product/features . PHP 8.5 was added in 12.13.0 (27 Nov 2025). https://enhance.com/support/release-notes
  - Recent PHP builds (28 Aug 2026): 8.5.10, 8.4.25, 8.3.33, 8.2.33. https://enhance.com/support/release-notes
  - **CONTRADICTED / STALE doc:** php-versions.html says the default is 8.1. The release notes for 12.18.0 (16 Mar 2026) say "Default PHP version for new websites and packages is now 8.3". /product/technology still says "5.6 - 8.3".
- **CONFIRMED: PHP version is set per website** (Advanced > Developer tools > PHP version). Addon domains share the site's PHP version. https://enhance.com/docs/php/end-user/update-sites-php-version.html
  - The package limits which versions are offered. https://enhance.com/docs/php/configure-default-php-version-and-supported-versions.html
- **CONFIRMED: php.ini editor per website**, only if the package has "Allow php.ini editor". It works on 3 levels: global, then server, then website. https://enhance.com/docs/php/php-ini.html
  - Since 12.23.0 (15 Jun 2026), the default `post_max_size` and `upload_max_filesize` are 1000M when no limit is configured. https://enhance.com/support/release-notes
- **CONFIRMED: php-fpm pool overrides per website are "only by the Owner or Reseller (this tool is not available to the end user)".** https://enhance.com/docs/php/
  - They are hidden when the server is LiteSpeed or OLS. On those, LSAPI_CHILDREN can be limited per website instead (12.12.0, Nov 2025). https://enhance.com/support/release-notes
- **CONFIRMED: default extensions:** redis, curl, gd (freetype, xpm, webp for PHP 7.4 and later, AVIF for PHP 8.1 and later), zip, opcache, mbstring, sodium, pdo_sqlite, pdo_mysql, openssl, zlib, exif, tidy, xsl, bcmath, xmlrpc, imap, gmp, intl, imagick, mailparse, pcntl, sockets and ldap. https://enhance.com/docs/php/php-extensions.html
  - The features page also lists Brotli. https://enhance.com/product/features
  - pgsql and pdo_pgsql ship with every PHP 8.x but are disabled by default (2 Apr 2026). https://enhance.com/support/release-notes
- **CONFIRMED: customers can toggle extensions** with the "PHP extension manager" under Developer tools. From 12.21.0 (3 Apr 2026) it lists any extension the host has installed. https://enhance.com/docs/php/end-user/manage-php-extensions.html , https://enhance.com/support/release-notes
  - **INFERENCE:** memcached is **not** in the default list. It would only exist if Verpex compiled it.
- **CONFIRMED: other PHP tools.** An OPcache on/off toggle per website. A "PHP max processes" limit. The IonCube loader can be toggled per site (not on PHP 8.0 or 8.3; not on 8.5 at release). https://enhance.com/product/features , https://enhance.com/docs/website-tools/enabling-ioncube.html
- **CONFIRMED: PHP starts on demand**, so idle sites use about 2 MB of RAM (12.9.0, 28 Jul 2025). https://enhance.com/support/release-notes
- **CONFIRMED: PHP error log** is visible in the UI (Developer tools > php-error.log) and in the home directory. Logging is on by default. https://enhance.com/docs/php/php-logging.html
- **CONFIRMED: outbound internet from PHP containers** works through NAT managed by ufw. https://enhance.com/docs/php/troubleshooting/php-websites-cannot-connect-to-the-outside-internet.html
  - Containers use their own resolv.conf (Google/Cloudflare resolvers when the host uses a local stub). This became customisable in 12.25.12 (21 Sep 2026). https://enhance.com/docs/dns-role/customisation-of-dns-resolver-configuration-for-website-containers.html
  - **INFERENCE:** server-side fetches for a weather widget (NWS API, etc.) should work.

## 3. Node.js (and Python / Ruby)

- **CONFIRMED: Node.js support was added in 12.11.0 (22 Sep 2025).** https://enhance.com/support/release-notes . Docs: https://enhance.com/docs/website-tools/nodejs.html
  - It must be enabled on the hosting package. It defaults to ON for new packages, but packages created before 22 Sep 2025 default to OFF. The API plan field is `persistentAppsAllowed`.
  - Node is installed per website into the home directory with **nvm**, so you can choose from the Node major versions available through nvm. It needs the server to reach github.com.
  - **How apps run:** the "Deploy app" form takes a Mode, a Startup command (e.g. `npm start --production`), a Working directory (relative to home), and a Node version.
    - **Automatic mode** is for production. Enhance starts the app and restarts it if it exits. stdout and stderr go to `persistent_app_<ID>.log` in the home directory, and the log is cycled on each start.
    - **Manual mode:** you start the app yourself via SSH after running `install_nvm_and_node.sh`. Enhance does not restart it and there are no logs.
  - **How apps are proxied:** turn on "Enable proxy" and give a port (1024–65535, unique per website, because each website container has its own network stack) and a path (a sub-path or the whole site). Enhance's web server reverse-proxies to the app.
    - WebSocket proxying arrived in 12.23.0 (15 Jun 2026).
    - When the site root is proxied, `.well-known/acme-challenge` is excluded "on all web server kinds" (12.13.1, 30 Nov 2025).
    - **Only the primary domain is proxied.** Aliases must redirect to the primary domain.
  - Node apps run inside the website container under the same cgroup limits as PHP. You can run several Node apps per website. https://enhance.com/product/features
  - **Gotcha:** Node apps are restored from backup but **not automatically redeployed** after a restore. Automatic redeploy on restore is on the roadmap.
  - API: `/websites/{id}/apps/persistent` (PersistentApp: startMode automatic|manual, command, workingDirectory, nodeVersion, proxyDetails{path, port, allowWebSocketUpgrade}), plus `/websites/{id}/apps/node/versions`. https://apidocs.enhance.com/spec/oas3-api.yaml
- **CONFIRMED: Enhance's own Ghost walkthrough** uses website SSH, `install_nvm_and_node.sh`, `nvm install 22`, and `npm install -g ghost-cli`. This shows npm and global npm installs work inside the container. https://enhance.com/docs/website-tools/install-deploy-ghost.html
- **CONFIRMED: Python is not supported.** It is only on the roadmap under "Approved" (not scheduled), alongside Tomcat and .NET Core. https://enhance.com/support/product-roadmap . The FAQ says .NET is not supported. https://enhance.com/faqs
- **INFERENCE:** Ruby is not mentioned anywhere, so treat it as unsupported. A `python3` binary may exist in the container through the host's /usr/bin (see SSH below), but there is no managed Python app runtime or proxy. Enhance's Node "persistent app" proxy is Node-specific in the UI. The API's PersistentApp takes an arbitrary `command`, but that use is undocumented.

## 4. Static sites

- **INFERENCE (strong):** a static site is simply an Enhance website with files in its document root (`public_html`, per release-note references). Every web server kind serves static files, and PHP only starts on demand, so a pure static or SSG build works on any server kind. There is no special "static site" mode in the docs or API.
  - Sources: https://enhance.com/support/release-notes (12.9.0 on-demand PHP; 12.23.0 "wp-admin button … defaults to the installation in public_html").

## 5. SSH / SFTP / FTP

- **CONFIRMED: website-level SSH into the site's container.** The customer adds an SSH key or password under Developer tools, then runs `ssh username@server_ip`. https://enhance.com/docs/customers/customer-ssh-useful-commands.html
  - Both key and password authentication are supported. https://enhance.com/product/features , API `POST /orgs/{org}/websites/{id}/ssh/keys` and `/ssh/password`.
  - SSH is a **package-level allowance** (forum, Dec 2024). https://community.enhance.com/d/2214-enhance-ssh
- **CONFIRMED: isolation.** "Each website's PHP processes as well as their user level SSH and cron jobs run within an isolated name space … They cannot access the files of other websites … nor can they start listening daemons on the public network interfaces of the host o/s." https://enhance.com/docs/technical-guidance/enhance-terminology.html , https://enhance.com/docs/security/
  - Not a classic chroot, but equivalent: it is a namespace/container with an overlay view of the host.
  - Hardening since then: setuid binaries were hidden from containers (12.21.5, 8 May 2026) and `mailq`/`postqueue` were hidden (12.21.6). https://enhance.com/support/release-notes
- **CONFIRMED: tools available in SSH.**
  - Official docs: `/usr/bin/php` (the site's selected version), `/usr/bin/composer`, `/usr/bin/wp-cli` (with `/usr/bin/wp` as a symlink), `strace`, `top`, `ssh`, `rsync`. https://enhance.com/docs/customers/customer-ssh-useful-commands.html
  - Release notes: zstd, ffmpeg, and a `.my.cnf` so `mysql` works from SSH without a password. **STALE?** These are old Appcd entries dated "29 May 2024". https://enhance.com/support/release-notes
  - `psql` is available when PostgreSQL is used. https://enhance.com/docs/database-role/postgresql/postgresql-management.html
  - `node`/`npm` come through nvm (see section 3).
  - **git:** Enhance staff (Adam, 7 Dec 2024) said customers "have access to directories like /usr/bin from the host o/s (so they can run the PHP CLI, vim, nano, ssh, git, etc)". **STALE?** This is 21 months old. https://community.enhance.com/d/2214-enhance-ssh
- **CONFIRMED: SSH port.** Custom SSH ports are not supported yet (roadmap). The clone and backup features require port 22. https://enhance.com/support/product-roadmap , https://enhance.com/docs/website-tools/cloning-websites.html
- **CONFIRMED (forum, Nov 2025): SFTP is only available through SSH** (same port 22). There is no SFTP-only mode, so you cannot have SFTP without SSH. https://community.enhance.com/d/3206-sftp-and-ftp-domain
  - **INFERENCE:** if Verpex's package disables SSH, there is no SFTP.
- **CONFIRMED: FTP is pure-ftpd** with passive ports 30000–31000. https://enhance.com/docs/application-role/ , https://enhance.com/docs/troubleshooting/unable-to-connect-to-ftp.html
  - A TLS config for pure-ftpd was added in 12.0.20 (25 Feb 2025), which means FTPS. **STALE?** https://enhance.com/support/release-notes
  - Customers can add and manage multiple FTP accounts, each with a home directory relative to the website base (API `NewFtpUser{account,password,homeDir}`). https://enhance.com/product/features , https://apidocs.enhance.com/spec/oas3-api.yaml
- **CONFIRMED: web file manager ("filerd").** It drops to the website user and supports drag-and-drop, bulk operations, unzip with an overwrite option, and recursive chmod. https://enhance.com/docs/application-role/file-manager.html , https://enhance.com/product/features , release notes 12.17.0 and 12.2.0.
- **CONFIRMED: there is no in-panel web terminal.** Staff said in 2023 they might add a customer terminal later (forum, 30 Nov 2023). **STALE?** https://community.enhance.com/d/982-ssh-terminal-in-enhance-panel

## 6. Cron

- **CONFIRMED: a cron editor in the UI** (Developer tools), which the customer controls. https://enhance.com/product/features , https://enhance.com/docs/customers/customer-ssh-useful-commands.html
  - API: `GET/PATCH/DELETE /orgs/{org}/websites/{id}/crontab`. It supports variable lines as well as command lines.
- **CONFIRMED: editing crontab from SSH.** Since 12.24.0 (3 Jul 2026), "Customers can now enable the ability to edit cron jobs with SSH (or any other process running under the website container) via the 'developer tools' section". Before that, the docs said crontab could not be edited from SSH. That doc text is now **STALE**. https://enhance.com/support/release-notes . API `/websites/{id}/container_cron_enabled`.
- **CONFIRMED: cron runs inside the site container** under its resource limits. https://enhance.com/docs/security/
- **CONFIRMED: "Push live" copies cron jobs** (fixed in 12.25.0, 20 Jul 2026). The downloadable backup excludes cron jobs. https://enhance.com/support/release-notes , https://enhance.com/docs/backup-role/end-user/download-backup.html
- **CONFIRMED: WordPress cron.** Enhance swaps WP-Cron for a system cron that runs hourly at a random minute. https://enhance.com/docs/wordpress/admin/wordpress-crons.html
  - **INFERENCE:** Joomla's scheduler should run from a real cron job the same way; there is no Enhance doc for that.

## 7. Git deployment

- **CONFIRMED: NOT RELEASED as of 21 Sep 2026.**
  - The roadmap (22 Jul 2026) lists "Git deploy." under **"Under development"** with no release date. https://enhance.com/support/product-roadmap
  - No release note up to 12.25.12 mentions Git deploy. https://enhance.com/support/release-notes
  - Stub docs pages already exist. They describe attaching a repo during site creation, from "Projects", or on existing sites, with auto-deploy on push. Every how-to section is **empty**, and the page says "If you do not see the Git Deploy tools … this feature is not included in your current hosting package". https://enhance.com/docs/git-deploy/deploying-git-repositries.html , https://enhance.com/docs/git-deploy/
  - The API spec (v12.25.12) has **no** git/repository endpoints. The only "git" string is a `SystemPackageName` enum `[appcd, git]` used for host package upgrades. https://apidocs.enhance.com/spec/oas3-api.yaml
- **INFERENCE: workarounds for now.**
  - (a) Run `git pull` or `git clone` over website SSH. git is on the container's /usr/bin, per the Dec 2024 staff post.
  - (b) Use CI such as GitHub Actions to build, then `rsync`/`scp` over SSH (port 22) into the site. A forum post suggests exactly this. https://community.enhance.com/d/245-git-integration-with-auto-deploy?page=2 (posts dated Apr 2024–Oct 2025)

## 8. Staging, cloning, preview

- **CONFIRMED: staging websites.** They do not use up a website slot. They have all normal features except domain mapping and email. They are noindex by default, marked with an STG tag, and "Push live" can be run to any domain. https://enhance.com/docs/website-tools/staging-websites.html
  - **Requirements:** the package must allow "Staging websites", **and a staging domain must be configured**, and "Resellers are required to set their own staging domain". Its nameservers must be delegated to the Enhance DNS; an A record is not enough. https://enhance.com/docs/platform-settings/platform-domains.html
  - **INFERENCE:** as a Verpex reseller, we must set up a staging domain on nameservers Verpex hosts before staging works.
  - You can "Clone to staging" from the dashboard (12.16.0, Jan 2026). Push live copies cron and IonCube (12.25.0). https://enhance.com/support/release-notes
- **CONFIRMED: cloning** copies files and databases (not email). WordPress search/replace is optional. The package must allow "Website cloning". https://enhance.com/docs/website-tools/cloning-websites.html
- **CONFIRMED: preview domain.** It lets you view a site before DNS cutover. It needs a staging domain and "will be crawled and indexed". https://enhance.com/docs/website-tools/domains/preview-domain.html

## 9. One-click app installer

- **CONFIRMED: installable apps are WordPress, WordPress + WooCommerce, Joomla, and OpenClaw.**
  - Joomla one-click installer: 12.18.0 (16 Mar 2026). "If 'Allow software installer' is enabled on a package, Joomla will automatically be available to the user."
  - OpenClaw: 12.24.0 (3 Jul 2026).
  - Sources: https://enhance.com/support/release-notes , https://enhance.com/product/features ("Install popular applications including WordPress, WordPress + WooCommerce and Joomla"), and API `WebsiteAppKind` enum = `wordpress, joomla, openclaw`.
  - Packages can **restrict the installable apps** (12.24.0). https://enhance.com/support/release-notes
- **CONFIRMED: Joomla toolkit (API).** App info, plus list/create/delete Joomla users, reset password, change username and change email. Release note 12.21.6 mentions a "WordPress/Joomla toolkit". https://apidocs.enhance.com/spec/oas3-api.yaml
  - **INFERENCE:** the Joomla toolkit covers far less than the WordPress one (no plugin/extension manager, updates or SSO seen in the spec).
- **CONFIRMED: roadmap installers**, approved but not scheduled: Moodle, Magento, Laravel and n8n. https://enhance.com/support/product-roadmap
- **CONFIRMED: no Softaculous/Installatron.** The only installer is Enhance's own. The docs contain no mention of Softaculous.

## 10. WordPress toolkit

- **CONFIRMED: WordPress toolkit features.** "supports versions 5.59 - 7.0". The toolkit includes:
  - pre-installing WordPress on new sites
  - plugin manager (including install from a custom URL)
  - theme manager
  - admin lockdown by IP (**Apache only**)
  - user manager
  - SSO into wp-admin
  - core auto-update schedule (minor only or always latest), plus per-plugin and per-theme auto-update
  - debug toggles (WP_DEBUG, WP_DEBUG_LOG, WP_DISPLAY)
  - maintenance mode
  - discovering existing installs
  - multiple installs per site (subdirectories)
  - Sources: https://enhance.com/docs/wordpress/end-user/about.html , https://enhance.com/product/wordpress-toolkit , https://enhance.com/docs/wordpress/end-user/restrict-admin-access.html
- **CONFIRMED: Enhance's WordPress advice** is to use LiteSpeed/OLS with the LSCache plugin (about 10 ms TTFB on cache hits) and to turn OPcache on. https://enhance.com/docs/wordpress/admin/optimising-for-wordpress.html

## 11. Databases

- **CONFIRMED: engines are MySQL, MariaDB or PostgreSQL**, per server.
  - MySQL: 8.0, then **8.4 as the default since 12.21.0 (3 Apr 2026)** because 8.0 reached EOL. The API enum still lists `mysql80, mysql81`.
  - MariaDB: LTS or 11. New installs set the utf8mb4 charset (12.22.0).
  - PostgreSQL 16 (optional role, from 12.22.0, 26 May 2026). PostGIS and pgvector are on by default (12.23.0).
  - Sources: https://enhance.com/docs/database-role/ , https://enhance.com/product/technology , https://enhance.com/support/release-notes , https://enhance.com/docs/database-role/postgresql/
  - **INFERENCE:** which engine Verpex runs is unknown. It is set per server by Verpex.
- **CONFIRMED: customer database tools.** Customers can add databases and users with granular privileges, import/export SQL, and see database sizes. https://enhance.com/docs/database-role/end-user/add-a-database.html , https://enhance.com/product/features
  - Remote MySQL "access hosts" per user (API `createWebsiteMySQLUserAccessHosts`). https://apidocs.enhance.com/spec/oas3-api.yaml
- **CONFIRMED: phpMyAdmin with SSO from the panel.** The phpMyAdmin button only appears once a phpMyAdmin domain is set. **For resellers this is marked "ESSENTIAL"**: "If a PhpMyAdmin domain is not set: Customer's of the Reseller will not be able to access PhpMyAdmin." https://enhance.com/docs/database-role/phpmyadmin.html , https://enhance.com/docs/resellers/configuring-a-reseller-account.html

## 12. Redis / Memcached / page caching

- **CONFIRMED: Redis per website.** "Redis starts inside the site's container and is private for each site." It is off by default. The customer turns it on under Developer tools, gets connection details, and can edit redis.conf. The package must allow Redis (plan field `redisAllowed`). https://enhance.com/docs/website-tools/enabling-redis.html , https://enhance.com/product/features
- **CONFIRMED (absence):** Memcached is not offered. Neither the docs, features page, release notes nor roadmap mention a memcached service, and the memcached PHP extension is not in the default list. https://enhance.com/docs/php/php-extensions.html
- **CONFIRMED: page caching** is Nginx FastCGI cache per domain, or LiteSpeed/OLS with LSCache (an app plugin such as the WordPress LSCache plugin). Apache has no built-in page cache. https://enhance.com/docs/application-role/webservers.html
  - **INFERENCE:** LSCache also has a Joomla extension, which would work if Verpex runs LiteSpeed.
- **CONFIRMED: OPcache** can be toggled per website. https://enhance.com/product/features

## 13. SSL

- **CONFIRMED: automatic Let's Encrypt** for every domain on a site that points at the cluster: aliases, staging, `mail.{domain}`, and service sites. https://enhance.com/docs/security/about-lets-encrypt.html , https://enhance.com/docs/security/end-user/letsencrypt.html
  - Certificates last 3 months and renew 14 days before expiry, with backoff on failure. Customers can request a certificate manually.
  - Third-party certificates can be uploaded. Three days before a third-party certificate expires, Enhance requests a Let's Encrypt certificate. https://enhance.com/docs/security/third-party-ssl.html
  - Force HTTPS can be set per domain (API `/v2/domains/{id}/ssl/force_ssl`). https://apidocs.enhance.com/spec/oas3-api.yaml
- **INFERENCE: no wildcard certificates.** Nothing mentions wildcard Let's Encrypt. Validation appears to be per domain over HTTP (there is a `letsencrypt_preflight` API). "Wildcard subdomains" is an unscheduled roadmap item, and the cPanel importer lists wildcard subdomains as unsupported. https://enhance.com/support/product-roadmap , https://enhance.com/docs/migrations/cpanel-importer.html . A wildcard *DNS record* is allowed (release 10.6, "*.subdomain", 2024, **STALE?**).

## 14. Email

- **CONFIRMED: email stack** is Postfix (SMTP), Dovecot (POP/IMAP) and rspamd (spam filtering). The login is the full email address. https://enhance.com/docs/email-role/
  - Email only works if the website is assigned to an Email role and the package allows "Email accounts". https://enhance.com/docs/email-role/configure-email-functionality.html
- **CONFIRMED: email features for customers** (https://enhance.com/product/features , https://enhance.com/docs/email-role/end-user/add-mailbox.html , https://enhance.com/docs/email-role/end-user/catch-all-email-addresses.html):
  - mailboxes, aliases, and forwarder-only accounts
  - forwarders on a mailbox
  - **auto-responders** with start/end dates, subject and body. API: `/email-client/autoresponders` and `/orgs/{org}/websites/{id}/emails/{addr}/autoresponder`, https://apidocs.enhance.com/spec/oas3-api.yaml
  - catch-all (12.8.0, 22 Jul 2025; one per domain)
  - inbound rspamd filtering with per-mailbox spam thresholds (API `spam_thresholds`) and allow/block lists
  - "Gmail auto-config"
- **CONFIRMED: webmail is Roundcube.** Per-server webmail runs at `https://mail.{customer_domain}`, with password change in Roundcube (12.13.0, Nov 2025). Roundcube SSO from the panel was added in 12.17.0 (Mar 2026) but must be enabled per server. There is also an optional global webmail domain. Roundcube is at 1.6.17 (12.24.1, Jul 2026). https://enhance.com/docs/email-role/webmail.html , https://enhance.com/support/release-notes
- **CONFIRMED: SPF, DKIM, DMARC.**
  - SPF `v=spf1 a mx ~all` is added automatically to new zones.
  - DKIM is a per-domain toggle (Domains > Email authentication).
  - DMARC `p=none` has been added automatically to new zones since 12.25.0 (20 Jul 2026), and can be edited per domain.
  - Sources: https://enhance.com/docs/email-role/email-authentication.html , https://enhance.com/support/release-notes
- **CONFIRMED: sending limits.** Hourly rate limits are set **per server by the hosting provider**, separately for SMTP and for website-generated mail (PHP `mail()` and cron). Messages over the limit are rejected outright, not deferred. The provider can also enable outbound spam scanning and a smart host. https://enhance.com/docs/email-role/email-settings.html . API: `/servers/{id}/spam/smtp_rate_limit_hourly`, `/website_generated_rate_limit_hourly`.
  - **"Per website sending limits with per website and per mailbox overrides" is still "Under development"** (roadmap, Jul 2026).
  - **INFERENCE:** the actual numbers are Verpex's to set and are not published by Enhance.
- **CONFIRMED: PHP `mail()` / sendmail** works when the site is mapped to an email role. "A drop-in sendmail replacement is added to the PHP container which sends through the website's SMTP using a hidden mailbox." https://enhance.com/docs/email-role/
  - **INFERENCE:** contact and join forms can use `mail()` and pick up DKIM/SPF, provided email is hosted on Enhance. Authenticated SMTP from the app is more robust.
- **CONFIRMED: missing today (roadmap, approved):** email piping, sieve/filter rules, virus scanning, spam learning, a queue UI, and whitelisting whole domains. Groupware (calendars/contacts) is also a roadmap item. https://enhance.com/support/product-roadmap

## 15. DNS

- **CONFIRMED: DNS stack and features.** PowerDNS, with a DNS zone editor for customers. The feature page also lists: https://enhance.com/docs/dns-role/about.html , https://enhance.com/product/features
  - DNSSEC per domain
  - DS and CAA records (12.12.0, Nov 2025)
  - zone templates (reseller or admin)
  - a default TTL of 1400 s
  - custom nameservers for resellers (e.g. ns1.brand.com)
  - Cloudflare sync (beta, one-way from Enhance to Cloudflare, with a proxy toggle)
- **CONFIRMED: package limits.** The package may cap the number of DNS records per domain (12.9.3). The "Allow DNS zone editor" allowance is needed. https://enhance.com/support/release-notes , https://enhance.com/docs/website-tools/domains/add-a-domain.html
- **CONFIRMED: DNS is optional.** You can run DNS externally or at Cloudflare; Enhance still issues Let's Encrypt certificates if the domain points at the server. https://enhance.com/product/technology

## 16. Backups

- **CONFIRMED: Enhance backup role.** Incremental, hard-link "time machine" snapshots over SSH. Frequency, retention, allowed hours and concurrency are **set globally by the hosting provider**; they are not per package. Per-package backup settings are only on the roadmap. https://enhance.com/docs/backup-role/admin/backup-settings.html , https://enhance.com/support/product-roadmap
  - An S3-compatible target is optional. S3 backups are full tar.gz archives and **do not allow single-file restore**. https://enhance.com/docs/backup-role/admin/s3-backups.html , release 12.15.0.
- **CONFIRMED: customer self-service** needs package toggles "Backups", "Allow manual backups" and "Allow self restore backups". https://enhance.com/docs/backup-role/end-user/create-backup.html , https://enhance.com/docs/backup-role/end-user/restore-backup.html
  - Restore types: all mailboxes, full website (files and databases), or custom (any combination of files, databases and mailboxes).
  - Restoring **individual files** became possible in 12.15.0 (21 Jan 2026).
  - Backups include DNS data (12.7.0).
- **CONFIRMED: download/upload.** Customers can always download or upload a portable Enhance backup (files and databases, or email). This does not depend on the backup role. Cron jobs are excluded. On LiteSpeed/OLS control panels, downloads are capped at 2 GB. https://enhance.com/docs/backup-role/end-user/download-backup.html
- **CONFIRMED: disaster recovery and archived restores.** Deleted sites can be restored, but only by the admin. https://enhance.com/product/backups
- **INFERENCE:** the actual backup frequency and retention on Verpex are not in the Enhance docs. Enhance suggests about 30 daily snapshots as typical sizing. https://enhance.com/product/backups

## 17. Resource isolation / limits

- **CONFIRMED: container per website.** "Every site runs in its own zero-overhead, lightweight Linux container, even under the same subscription." It uses kernel namespaces and cgroups. PHP, SSH, cron and Node all run inside it. Containers cannot reach each other over IP. https://enhance.com/features , https://enhance.com/docs/security/ , release notes (Appcd 2.4.2, **STALE?** dated 2024).
- **CONFIRMED: hard limits per website.** Memory (OOM kill), vCPU (fractional), IOPS, IO bandwidth, nproc, disk (home directory), inodes (12.23.0, Jun 2026) and swap (12.15.0). They are set on the package and can be overridden per website. https://enhance.com/docs/resource-usage/set-per-website-resource-limits.html , https://enhance.com/docs/packages/system-resource-limits.html
  - nproc must be at least the PHP worker limit plus 5. LSPHP is fixed at 50 children unless an override is set.
- **CONFIRMED + CONTRADICTION: who sets system limits.**
  - Reseller page: "System resource limits (RAM, Swap, CPU, IOPS, IO Bandwidth, Nproc, and Disk Space) are applied per website and inherited directly from the master organisation. A Reseller cannot configure custom system resource limits for their customers." https://enhance.com/docs/resellers/
  - Hard-limits page tip: "Resellers can limit storage and bandwidth but not system resources". But the same page later says "It is possible for the master organisation and a Reseller to override these settings on a per website basis". https://enhance.com/docs/packages/system-resource-limits.html
  - **INFERENCE:** the reseller page is the authoritative and more specific statement. **Verpex's** limits apply to our site and we cannot raise them.
- **CONFIRMED: reseller billing change.** Since 12.25.9 (11 Sep 2026), a reseller's resources are consumed when a client *creates* a resource, not when the reseller subscribes the client. https://enhance.com/support/release-notes

## 18. ModSecurity / WAF / security

- **CONFIRMED: ModSecurity with OWASP CRS.** It is enabled per server by the provider, on Apache and Nginx per the docs; the features page also claims LiteSpeed. Customers can toggle it **per domain** (Website > Security > ModSecurity), but only if the server has it enabled. https://enhance.com/docs/security/modsecurity.html , https://enhance.com/docs/security/end-user/enable-modsecurity.html , https://enhance.com/features
  - The webserver comparison marks OLS "N" for ModSecurity. https://enhance.com/docs/application-role/webservers.html
- **CONFIRMED: other security features.**
  - Brute-force protection for panel logins (IP and email rate limits). Enhance does **not** install fail2ban. https://enhance.com/docs/platform-settings/brute-force-protection.html
  - TOTP 2FA per user. https://enhance.com/docs/security/end-user/two-factor-authentication.html
  - An "IP manager" to allow or block IPs per website. It is .htaccess-based (API `/htaccess/ips`). https://enhance.com/product/features , https://apidocs.enhance.com/spec/oas3-api.yaml
  - The roadmap says a "New redirect manager and IP block/allow manager … will write directly to the web server config to ensure consistency across all web server kinds". **INFERENCE:** the current redirects and IP blocks rely on .htaccess, so they do not work on Nginx. https://enhance.com/support/product-roadmap
- **CONFIRMED: no password-protected directories.** "Ability to password protect websites" is on the roadmap (approved, not scheduled), and the cPanel importer lists "Directory privacy" as unsupported. https://enhance.com/support/product-roadmap , https://enhance.com/docs/migrations/cpanel-importer.html
  - **INFERENCE:** a hand-written `.htaccess` Basic Auth file (`AuthType Basic` plus `.htpasswd`) would likely work on Apache or LiteSpeed, since mod_auth_basic and authn_file are enabled by default. For the member area, use application-level login such as Joomla ACL.

## 19. Logs

- **CONFIRMED: PHP error log** is available to customers (UI and home directory). https://enhance.com/docs/php/end-user/php-logs.html
- **CONFIRMED: no retained raw access logs for customers.** Access logs go to `/var/local/enhance/webserver_logs/{uuid}.log`, a root-only host path. They "are stored and discarded every time the stats processor runs, roughly every 5 minutes". https://enhance.com/docs/technical-guidance/website-access-and-logs.html
  - Forum threads through **28 Aug 2026** show customers still cannot keep access logs without admin-side hacks. https://community.enhance.com/d/2398-website-access-logs , https://community.enhance.com/d/3192-website-logs-user-home-folder (Nov 2025)
  - There is a website metrics endpoint (`getWebsiteMetrics`) and a bandwidth figure. https://apidocs.enhance.com/spec/oas3-api.yaml
  - **INFERENCE:** plan for app-level analytics (e.g. Matomo or Plausible) or Cloudflare analytics if we need traffic data.
- **CONFIRMED: Node app logs** are written to `persistent_app_<ID>.log` in the home directory (automatic mode). https://enhance.com/docs/website-tools/nodejs.html

## 20. Domains: multiple domains, aliases, subdomains

- **CONFIRMED: domain types per website.** A website can have addon, alias and subdomain entries, with www as an implicit alias. Addon domains and subdomains can map to other document roots within the website (the "domain re-mapping tool"). Unlimited aliases are not billed. https://enhance.com/docs/website-tools/domains/add-a-domain.html , https://enhance.com/product/features , https://enhance.com/faqs
  - Package allowances apply ("Number of domain aliases", addon and subdomain counts; API ResourceName includes `domainAliases, addonDomains, subdomains`).
  - WordPress and Joomla can be installed on any mapped domain.
- **CONFIRMED: redirects and primary domain.** 301/302 redirects are available (currently .htaccess-backed, see section 18). The primary domain can be changed. https://enhance.com/docs/website-tools/domains/redirect-management.html
- **CONFIRMED: wildcard subdomains are not supported** (roadmap). https://enhance.com/support/product-roadmap

## 21. Reseller-specific points (we are a Verpex *reseller*)

- **CONFIRMED: resellers have no server access.** "Resellers do not have access to any underlying servers within an Enhance cluster." https://enhance.com/docs/resellers/
  - **INFERENCE:** anything in the docs that says "as root" (for example vhost include files, PECL builds, legacy PHP, the ModSecurity config, retaining logs) is **Verpex-only**.
- **CONFIRMED: first-time setup the reseller must do.**
  - Set a control panel domain (ESSENTIAL).
  - Set a phpMyAdmin domain (ESSENTIAL).
  - Optionally set custom nameservers, a staging domain and a Roundcube domain.
  - Source: https://enhance.com/docs/resellers/configuring-a-reseller-account.html
- **CONFIRMED: package limits for resellers.** A reseller's packages are capped at the reseller's own subscription. "Features which are not enabled on the reseller's subscription cannot be enabled by the reseller". There is no overselling. https://enhance.com/docs/resellers/
  - **INFERENCE:** whatever Verpex leaves out of our reseller plan (SSH, Node.js, Redis, staging, php.ini editor, software installer, etc.) we cannot offer to our own website.
- **CONFIRMED: team access.** Users can be invited as "Super Admin" or as a "Collaborator" (full access to one specific website). This is useful for giving ARES volunteer webmasters access without handing over the reseller login. https://enhance.com/docs/account/end-user/managing-user.html

---

## Quick implications for the spokares.org rebuild (all INFERENCE)

1. **Joomla 3 to modern Joomla or WordPress.** Both have one-click installers (Joomla since Mar 2026). The PHP 8.3 default, PHP up to 8.5, and MySQL 8.4 or MariaDB are all fine for Joomla 5 and WordPress 6.x/7.0.
2. **Static site generator (Hugo/Astro/Eleventy) plus CI deploy over SSH/rsync** works on any web server kind, provided SSH is included in Verpex's plan.
   - Built-in Git deploy is not shipped yet.
   - A JS calendar or downloads list can be static. The member-only area would then need either .htaccess Basic Auth (Apache/LiteSpeed only) or a small PHP or Node component.
3. **Web server kind is Verpex's choice** and it decides .htaccess behaviour (Apache/LiteSpeed fully, OLS partially, Nginx not at all), page caching (LSCache or FastCGI), ModSecurity, and WP admin lockdown (Apache only).
   - **Must confirm with Verpex** before relying on .htaccess rewrites, Basic Auth, or the IP/redirect manager.
4. **Node.js is available but gated.** It is a per-package toggle, runs through nvm, and the proxy covers the primary domain only. It is not auto-redeployed after a restore.
   - No Python or Ruby. Prefer PHP or static output for widest compatibility.
5. **Email.** Roundcube, autoresponders, forwarders, catch-all, DKIM/SPF/DMARC, and PHP `mail()` through the site mailbox are all available.
   - Sending limits are hourly and set per server by Verpex; not published.
   - Missing: sieve filters, email piping and virus scanning.
6. **Backups.** Customer self-restore (including single files since Jan 2026) depends on Verpex's package toggles. Schedule and retention are Verpex-global.
   - The portable download/upload backup is always available. Use it before major changes.
7. **Traffic stats and logs.** No retained raw access logs, so use app or JS analytics.

## Open questions for the Verpex lens (not answerable from Enhance docs)

- Which web server kind do Verpex's Enhance app servers run? (The product page does not say.)
- Which database engine and version do they run: MySQL 8.4 or MariaDB?
- Which package allowances are included in the Verpex Enhance reseller plans?
  - SSH/SFTP
  - Node.js (persistentAppsAllowed)
  - Redis
  - staging and cloning
  - php.ini editor
  - software installer and allowed apps
  - DNS zone editor
  - self-restore backups
  - email accounts and email role placement
- What are Verpex's per-website system limits (RAM, vCPU, IOPS, nproc, inodes), email hourly rate limits, and backup schedule and retention?
- Is ModSecurity enabled on their servers? Is Roundcube SSO enabled?
