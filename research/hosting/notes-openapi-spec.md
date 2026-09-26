# Enhance (orchd) OpenAPI spec as ground truth: raw notes

Research date: 2026-09-25. Lens: the Enhance control-panel OpenAPI spec, which is what Verpex's "Enhance reseller hosting" runs on.
Rules I followed: public sources only, no live API calls, no local credential files.

Tags used below:
- **[C]** CONFIRMED. I saw it in the cited source.
- **[I]** INFERENCE. My reasoning from the sources, not stated in them.

---

## 0. Sources and their dates

| # | Source | Date / freshness |
|---|--------|------------------|
| S1 | https://apidocs.enhance.com/spec/oas3-api.yaml (local copy: `/Users/sean/Projects/spokane_ares/research/hosting/_enhance-oas3-api.yaml`) | HTTP `Last-Modified: Mon, 21 Sep 2026 09:57:23 GMT`. 612,692 bytes. sha256 `e70647d68d5c5bbaf781cd6bd1c3072dde94701895c7965e1e6174eb9e0100f0`. Fresh (4 days old). |
| S2 | https://enhance.com/support/release-notes | Latest entry is 12.25.12, dated "21st September 2026". Fresh. |
| S3 | https://enhance.com/docs/website-tools/nodejs.html | Undated. Mentions the feature release of "22nd September, 2025" (about 12 months old). **Flag: may be slightly stale.** |
| S4 | https://enhance.com/docs/resource-usage/set-per-website-resource-limits.html | Undated. The inode section was added with 12.23.0 (15 Jun 2026), so the page was updated recently. |
| S5 | https://enhance.com/docs/customers/customer-ssh-useful-commands | Undated. **Stale in part**: it says crontab can't be edited from SSH, but S2 12.24.0 (3 Jul 2026) added that. |
| S6 | https://enhance.com/docs/php/ | Undated. |
| S7 | https://community.enhance.com/d/245-git-integration-with-auto-deploy (read through the Flarum JSON API: https://community.enhance.com/api/discussions/245 and `/api/posts?filter[discussion]=245&sort=-createdAt`) | Thread opened 2023-02-21. Last post 2026-09-21. Title still "(LOGGED)". |
| S8 | https://verpex.com/reseller-hosting-enhance | Footer says "© 2026 Verpex". I pulled the plan data from the page's embedded Nuxt payload. Prices are rendered client-side and weren't captured. |

---

## 1. Spec identity (info block) [C, S1]

```yaml
openapi: 3.0.3
info:
  description: orchd API docs
  version: 12.25.12
  title: orchd
```
- There's no `servers:` block. The base URL is the panel host plus `/api` (per the `ss:enhance-api` skill notes; not in the spec itself [I]).
- `securitySchemes` has two entries:
  - `sessionCookie`: apiKey in cookie `id0`
  - `bearerAuth`: http bearer
- 302 paths, 482 operations and 455 component schemas.
- Operation counts by tag: websites 93, servers 82, orgs 44, settings 35, wordpress 31, logins 24, domains 24, apps 23, branding 21, importers 19, emails 18, dns 15, mysql 14, members 12, plans 12, backups 12, postgresql 9, subscriptions 8, email-client 8, joomla 7, migrations 6, ssl 5, customers 4, ftp 4, letsencrypt 3, others fewer.
- Spec version 12.25.12 matches the newest release-notes entry, "12.25.12 – 21st September 2026" [C, S2]. The spec is current.
- **Caveat [C, S2 vs S1]:** 12.25.12 added "customise the DNS resolver configuration for website containers". `grep -i resolver` over the spec finds 0 hits. That feature is UI or CLI only, or the spec lags slightly.

---

## 2. Authoritative enums, copied verbatim from `components/schemas` [C, S1]

| Schema | Values |
|---|---|
| `PhpVersion` | php56, php70, php71, php72, php73, php74, php80, php81, php82, php83, php84, php52, php53, php54, php55, php85 |
| `WebserverKind` | liteSpeed, openLiteSpeed, dummyWebServer, apache, nginx |
| `VhostWebserverKind` (custom vhost files) | apache, nginx |
| `WebsiteAppKind` (1-click installable apps) | wordpress, joomla, openclaw |
| `PersistentAppKind` | generic, openclaw |
| `PersistentAppStartMode` | automatic, manual |
| `MysqlKind` | mysql80, mysql81, mariaDbLts, mariaDb11 |
| `MySQLAuthPlugin` | caching_sha2_password, mysql_native_password |
| `ServerRole` | email, backup, database, application, dns, postgresql |
| `ResourceName` (plan quota resources) | customers, diskspace, domainAliases, forwarders, ftpUsers, mailboxes, mysqlDbs, pageViews, stagingWebsites, transfer, websites, addonDomains, subdomains, dnsRecords, postgresqlDbs |
| `PlanType` | shared, dedicated |
| `WebsiteKind` | normal, controlPanel, phpMyAdmin, roundcube, staging, serverHostname |
| `DomainMappingKind` | primary, preview, addon, alias, subdomain |
| `DnsRecordKind` | A, AAAA, CNAME, TXT, SPF, SRV, NS, MX, PTR, DS, CAA |
| `BackupKind` | manual, automatic, archive |
| `BackupStorageKind` | enhance, s3 |
| `BackupDownloadKind` | website, email |
| `SettingKind` (service override settings) | phpIni, phpFpm, apache, postfix, sged, rspamd, dovecot, websiteBackup, screenshotd, hardDeleteGC, letsencrypt |
| `Role` (control-panel member roles) | Owner, SuperAdmin, Business, SiteAccess, Support, Sysadmin |
| `DaemonKind` | mysqld, pdns, postfixSmtp, postfixSmtps, postfixSubmissions, dovcotImap, dovcotImaps, dovcotPop3, dovcotPop3s, pureFtp |
| `SystemPackageName` | appcd, git |
| `RequireIpsRuleKind` (.htaccess IP rules) | allow, block |
| `WPAutoUpdateCore` | major, minor |
| `ImportKind` | cPanel, plesk, pleskStreaming |
| `CloneStatus` | cloningWebsite, cloningDatabases, cloningHomeDir, succeeded, failed |
| `ActivityKind` | added, removed, cloned, imported, backedUp, errorRaised, backupError |

Notes:
- **PHP [C, S1]:** the enum covers PHP 5.2 through 8.5.
- **PHP [C, S2]:**
  - 12.13.0 (27 Nov 2025) added "PHP 8.5 support". On 8.5, "Ioncube and PECL imagick modules are not supported … pending release". A later entry says imagick is now enabled by default on 8.5.
  - 12.18.0 (16 Mar 2026): "Default PHP version for new websites and packages is now 8.3."
  - 12.2.0 (11 Apr 2025): legacy PHP 5.2–5.5 is optional and needs manual package install by the platform owner. **That entry is older than 12 months.**
  - The latest PHP package drop (28 Aug 2026) lists 8.5.10, 8.4.25, 8.3.33 and 8.2.33.
- **PHP [I]:** the enum doesn't prove Verpex installed every version. Each plan or subscription carries its own `allowedPhpVersions`, `defaultPhpVersion` and `canUse.phpVersions`. Verpex's reseller subscription sets our ceiling.
- **MySQL [C, S2]:** 12.21.0 (3 Apr 2026) says "MySQL 8.4 is now the default version when MySQL is selected due to 8.0 EOL." [I] The `mysql81` enum value probably stands for the 8.x/8.4 line; the spec gives no mapping.
- **Memcached [C, S1]:** no memcached anywhere in the spec (`grep -i memcache` = 0). Only Redis.
- **Other runtimes [C, S1]:** no Python, Ruby or Perl runtime surface in the spec (grep = 0). The only non-PHP runtime is Node.js (section 4).

---

## 3. Plan and subscription resource-limit fields [C, S1]

### `Plan` / `NewPlan` / `UpdatePlan` (schemas)

| Field | What it holds |
|---|---|
| `resources[]` | `{name: ResourceName, total?: int}`. If `total` is missing the quota is unlimited. "A reseller may only sell unlimited quota if the subscription to which itself is subscribed has the same resource with unlimited quota." |
| `allowances[]` | `{name: string}`. The spec does **not** list allowance names; they are opaque strings. |
| `selections[]` | `{name: string, value: string}`. Also opaque. |
| `planType` | shared or dedicated |
| `cgroupLimits` | `CgroupLimits` = `{nproc, memoryLimit, iops, ioBandwidth, virtualCpus, swapLimit?}`, all nullable numbers. **Units are not stated in the spec.** |
| `fsQuotaLimit` | `FsQuotaLimit` = `{totalAvailable (bytes), inodesAvailable?}`, described as "File system quota settings in bytes." |
| PHP | `allowedPhpVersions[]`, `defaultPhpVersion` |
| `redisAllowed` | bool |
| `persistentAppsAllowed` | bool. This is the Node.js / persistent-app gate. |
| `allowedApps[]` | WebsiteAppKind values. "If unset, all current and future apps are allowed." |
| `preinstallWordpressTheme` | string |
| Server groups | `serverGroupIds[]`, `allowServerGroupSelection`, `defaultServerGroupId` |

### `Subscription` (what our Verpex reseller account or a customer actually holds)
- Carries the same `resources` (as `UsedResource` = name, total, usage).
- Also carries `allowances`, `selections`, `allowedPhpVersions`, `defaultPhpVersion`, `redisAllowed`, `persistentAppsAllowed`, `allowedApps` and `serverGroups`.

### `Website.canUse` (`CanUse` schema)
- Per-website effective capability flags: `ftp`, `fileManager`, `phpVersions[]`, `redis`, `modSec`, `backup`, `mysqlKind`, `persistentApps`, `roundcubeSso`, `postgresql`.
- **[I]** This is the single best runtime probe of "what does this site actually get", once someone with credentials calls `GET /orgs/{org_id}/websites/{website_id}`.

### Per-website overrides (master org only per the spec)
- `GET|PUT /orgs/{org_id}/websites/{website_id}/cgroup_limits`. The PUT summary says "(Master org only)".
- `GET|PUT /orgs/{org_id}/websites/{website_id}/fs_quota_limits`. The PUT summary says "(Master org only)". GET returns `FsQuotaInfo {totalAvailable, used, inodesAvailable, inodesUsed}`.
- `GET|PUT /websites/{website_id}/lsphp_settings`: `{lsapiChildren}`. "Only for websites on Litespeed or Openlitespeed".
- **[C, S4]:** the docs say the owner or a reseller can override limits per website via "Overrides". **This conflicts with the spec's "(Master org only)" for the PUT.** Treat per-site overrides as something Verpex controls.

### Units and semantics from the docs [C, S4]
- **Memory limit:** OOM killer when hit.
- **Virtual CPUs:** 1 = one core at 100%; fractional values allowed.
- **IOPS and IO bandwidth:** processes hang in D state when hit.
- **nproc:** counts PHP workers, cron, SSH and forks. "For LSPHP this is currently set to 50." Set it at least 5 above the PHP worker count.
- **Hard disk and Inode:** hard limits.
- **Swap limit:** exists.
- Limits "apply to all processes belonging to that website … PHP workers, … cron jobs or anything the customer executes via SSH."

### Bandwidth and metrics
- `GET /orgs/{org_id}/subscriptions/{subscription_id}/bandwidth`: current-month bytes, cached 12h.
- `GET /orgs/{org_id}/websites/{website_id}/metrics`: hourly `MetricsEntry {bytesReceived, bytesSent, uniqueHits, botHits, totalHits}`. Defaults to the last 24h.
- `PUT /orgs/{org_id}/subscriptions/{subscription_id}/calculate-resource-usage`

### Verpex plan tiers (context, not spec) [C, S8]
From the embedded page data: "Reseller E30" (30 Hosting Accounts, 100GB NVMe SSD), "Reseller E80" (80 accounts, 300GB NVMe) and "Reseller E120" (120 accounts, 750GB NVMe). All three list "Unlimited Domains", "Free SSLs", "Free Migrations" and "24/7 Tech Support". Marketing copy also says "Unlimited websites & email accounts", "Automatic backups", "Incremental Backups Included", "S3-compatible third-party backup systems can also be configured". The page shows no CPU, RAM, inode, PHP or Node limits.

---

## 4. Capability surfaces: endpoint evidence [C, S1 unless noted]

### 4.1 Websites, PHP, web server
- `POST /orgs/{org_id}/websites`: create a website (`NewWebsite {domain, subscriptionId, phpVersion, serverGroupId, wordPressAdminCredentials, …}`). The query param `kind` takes a WebsiteKind. For `staging`, "the subscription must include stagingWebsites resource".
- `GET|PATCH|DELETE /orgs/{org_id}/websites/{website_id}`. `UpdateWebsite {phpVersion, status, isSuspended, tags, subscriptionId, orgId}`.
  - The description says PATCH can also toggle SSH via an `ssh` boolean, but **the `UpdateWebsite` schema has no `ssh` property**. This is a spec inconsistency.
- `GET /v2/websites/{website_id}/webserver_kind`: read-only for a site.
- `GET|PUT /servers/{server_id}/webserver`: changing the web server is server-level (MO/Verpex only).
- `POST /servers/{server_id}/webserver/litespeed/password`
- `GET|POST|DELETE /websites/{website_id}/php_extensions`, `GET /websites/{website_id}/available_php_extensions`, `GET /websites/{website_id}/built_in_php_extensions`
- `GET /websites/{website_id}/php_error_log`: last 256KB.
- `POST /v2/websites/{website_id}/restart_php`
- `GET|PUT /v2/websites/{website_id}/ioncube`
- `GET|PUT|DELETE /orgs/{org_id}/websites/{website_id}/settings/{setting_kind}/{setting_key}`: per-website overrides for `phpIni`, `phpFpm`, `apache`, etc. The value is `ServiceSettingValue`.
- `GET /servers/{server_id}/php/fpm/{website_id}`: MO only.
- `GET|PUT|DELETE /v2/domains/{domain_id}/vhost`: custom vhost, `{contents, webserver: apache|nginx}`.
- `GET|PUT|DELETE /v2/domains/{domain_id}/webserver_rewrites`: `{path, destinationFile}`.
- `GET|PATCH /orgs/{org_id}/websites/{website_id}/htaccess`: RewriteCond/RewriteRule chains managed by line number.
- `GET|PUT /orgs/{org_id}/websites/{website_id}/htaccess/ips`: `Require ip` allow/block in `public_html/.htaccess`.
- Nginx FastCGI cache:
  - `GET|PUT|DELETE /v2/domains/{domain_id}/nginx_fastcgi`
  - `GET|POST|DELETE /v2/domains/{domain_id}/nginx_fastcgi_excluded_paths`
- `GET /websites/{website_id}/container_ip`: "container IP and host IP … for database connections".
- `GET /orgs/{org_id}/websites/{website_id}/server_domains`
- `POST /orgs/{org_id}/websites/{website_id}/preview`: preview domain.
- [C, S2] 12.9.0 (28 Jul 2025): PHP now starts on demand, so idle websites use about 2MB RAM. **Older than 12 months.**
- [C, S2] 12.23.0 (15 Jun 2026): "Default post_max_size and upload_max_filesize now 1000M if no specific limit is configured."

### 4.2 Installable apps (1-click)
- `GET /utils/installable-apps` (global) and `GET /orgs/{org_id}/subscriptions/{subscription_id}/installable-apps` (per subscription). Each returns `InstallableWebsiteApp {app, version, isLatest, description, size}`.
- `POST|GET /orgs/{org_id}/websites/{website_id}/apps`: `NewWebsiteApp {app, version, path, adminUsername, adminPassword, adminEmail, domainId}`. "if the installed app is WordPress, this endpoint will enable PHP".
- `DELETE /orgs/{org_id}/websites/{website_id}/apps/{app_id}`
- **WordPress toolkit (31 ops).** All paths below are under `/orgs/{org_id}/websites/{website_id}/apps/{app_id}/wordpress/`:
  - `version`, `themes`, `plugins`, `users`, `users/{user_id}/sso`, `users/default`, `wp-config`, `info`
  - `/v2/apps/{app_id}/wordpress/url` and `/v2/apps/{app_id}/wordpress/maintenance-mode` sit outside that prefix.
  - `GET /utils/wordpress/latest`
- **Joomla (7 ops):**
  - `GET …/apps/{app_id}/joomla/info`
  - `GET|POST …/joomla/users`
  - `DELETE …/joomla/users/{username}`
  - `PUT …/joomla/users/{username}/password|username|email`
  - [C, S2] 12.18.0 (16 Mar 2026): "Joomla one click installer. If 'Allow software installer' is enabled on a package…"
- **Openclaw** (1-click AI agent app): `/websites/{website_id}/openclaw/*` (info, sso, devices, approve, device_auth_enabled, rotate_token, provider_keys). [C, S2] added in 12.24.0 (3 Jul 2026).
- No Drupal, Ghost, Grav or static-site-generator installer in the app enum. [C, S1] (The docs do have a manual "install-deploy-ghost" guide under website-tools; it wasn't read.)

### 4.3 Node.js and persistent apps (long-running processes)
- `POST|GET /websites/{website_id}/apps/persistent`: "Create an application that will be started automatically within the website container and restarted if it fails. Set a port to proxy from the web server to the running application."
- `PATCH|GET|DELETE /websites/{website_id}/apps/persistent/{app_id}`. GET returns the log.
- Schemas:
  - `PersistentApp {startMode: automatic|manual, command, workingDirectory?, nodeVersion?, proxyDetails?}`
  - `PersistentAppProxyDetails {path, port, allowWebSocketUpgrade?}`
  - `NodeVersion` = "default" | "stable" | semver
- Node.js endpoints:
  - `POST /websites/{website_id}/apps/node`: install nvm plus default stable node.
  - `GET /websites/{website_id}/apps/node/possible_versions`
  - `POST|GET /websites/{website_id}/apps/node/versions`
  - `PUT /websites/{website_id}/apps/node/versions/default`
- Gating: `Plan.persistentAppsAllowed`, `CanUse.persistentApps`.
- From the Node.js docs [C, S3]:
  - "Node.js must be enabled on the user's hosting package. This defaults to ON. … If a hosting package was created prior to … (22nd September, 2025) Node.js will be defaulted OFF."
  - "only the primary domain is proxied". Ports must be 1024–65535 and unique per website. The path must be unique.
  - Automatic mode restarts on exit and logs to `persistent_app_ID.log`.
  - "Node.js applications are restored but are not automatically deployed after a website backup restore."
  - "subject to the same isolation and resource limits as PHP applications".
  - Needs outbound github.com.
- [C, S2]: 12.11.0 (22 Sep 2025) "Node.js support"; 12.23.0 (15 Jun 2026) "Node.js websocket proxy support"; 12.13.1 (30 Nov 2025) excludes `.well-known/acme-challenge` from the proxy.

### 4.4 Cron
- `GET|PATCH|DELETE /orgs/{org_id}/websites/{website_id}/crontab`: "Get/Update/Delete user's crontab".
  - Body is `UpdateCrontabFullListing`: items of `{cronCmd:{lineNumber, expr}}` or `{variable:{lineNumber,key,val}}`.
  - "users are able to update only cron expressions and environment variables."
- `GET|PUT /websites/{website_id}/container_cron_enabled`: "controls whether or not a website can change its own crontab from inside the container." Note: the PUT summary was copy-pasted as "Set backups disabled status".
- [C, S2]: 12.24.0 (3 Jul 2026) lets customers edit cron from SSH via "developer tools". 12.25.0 (20 Jul 2026): "Push live" now copies cron jobs. This makes S5's "not possible to edit crontab from SSH" statement **stale**.

### 4.5 SSH, SFTP, FTP
- SSH keys:
  - `GET|POST /orgs/{org_id}/websites/{website_id}/ssh/keys`: `NewSshKey {value, name}`, appended to `~/.ssh/authorized_keys`.
  - `PATCH|DELETE /orgs/{org_id}/websites/{website_id}/ssh/keys/{key_id}`
- `POST /orgs/{org_id}/websites/{website_id}/ssh/password`: sets the SSH password for the site's unix user.
- `Website.unixUser`: "used for ssh shells, prefixing website databases and database users".
- FTP users:
  - `GET|POST /orgs/{org_id}/websites/{website_id}/ftp/users`: `NewFtpUser {account, password, homeDir}`. The account "will get appended with website's primary domain" (`username@domain.com`). Optional `?createHome=true`.
  - `PATCH|DELETE /orgs/{org_id}/websites/{website_id}/ftp/users/{username}`
  - `DaemonKind` includes `pureFtp`.
- There is **no dedicated SFTP endpoint or daemon enum**.
  - [I] SFTP goes over the site's SSH daemon, which runs inside the website's PHP container (PATCH website description: "SSH daemon … in the `PhpCd` service").
  - [C, S5] In SSH "you are within the customer's containerised PHP environment". Available binaries include `/usr/bin/php`, `/usr/bin/composer`, `/usr/bin/wp-cli`, `ssh` and `rsync`.

### 4.6 Git
- The **only** git mention in the spec is `SystemPackageName: [appcd, git]`, used by MO-only `GET|PUT /servers/{server_id}/packages/update`. That's a server-side package, not a customer git-deploy feature. [C, S1]
- There's no repository, deploy-key or webhook endpoint. [C, S1]
- [C, S7] Feature request "Git integration with auto deploy (LOGGED)". Staff replied 2023-03-25: "open feature request … no current estimated release date". Users were still asking on 2026-09-21. **No git deploy feature exists as of 2026-09-21.**
- [I] The `git` CLI may exist inside the container, since git is a managed system package on servers. That's not confirmed; S5 doesn't list it. The practical plan: build locally or in CI, then deploy via `rsync` over SSH (rsync is confirmed in S5) or SFTP/FTP.

### 4.7 Databases
- MySQL:
  - `GET|POST /orgs/{org_id}/websites/{website_id}/mysql-dbs` and `DELETE …/mysql-dbs/{db_name}`
  - Users: `GET|POST …/mysql-users`, `PUT|DELETE …/mysql-users/{username}`, `PUT …/privileges`, `POST|DELETE …/access-hosts` (remote access hosts)
  - `GET …/mysql-dbs/{db_name}/sso` and `GET …/phpmyadmin` (phpMyAdmin SSO)
  - `GET …/mysql-dbs/{db_name}/sql`: gzipped dump.
  - `POST /v2/websites/{websiteId}/mysql/{db_id}/sql`: upload .sql/.gz/.zip, "max allowed size is 500 MB".
- PostgreSQL (9 ops): `…/postgresql-dbs` and `…/postgresql-users` (+ privileges). Gated by `CanUse.postgresql` and the `ServerRole` postgresql. [C, S2] Added in 12.22.0 (26 May 2026). 12.23.0 enabled PostGIS and PGVector by default.

### 4.8 Redis and caching
- `GET|PUT /v2/websites/{website_id}/redis`: bool. Gated by `Plan.redisAllowed` and `CanUse.redis`.
- No memcached (section 2).
- Nginx FastCGI cache (4.1). LiteSpeed LSAPI settings (section 3).

### 4.9 Backups
- `GET|POST /orgs/{org_id}/websites/{website_id}/backups`.
  - POST takes `?includeEmails=false` by default and a `BackupOptions {description}` body.
  - "Backups consists of … home directory, … mysql databases, and … emails." "Email backups are not done automatically."
- `GET|PUT|DELETE /orgs/{org_id}/websites/{website_id}/backups/{backup_id}`. PUT restores with `BackupRestoreOptions {restoreFiles, restoreOnlyFiles[], restoreEmails[], restoreAllEmails, restoreDatabases[], restorePostgresqlDatabases[]}`, which is granular.
- `GET …/backups/{backup_id}/restore_status` and `GET …/backups/{backup_id}/directory_tree`
- `GET /orgs/{org_id}/websites/{website_id}/status/backup`
- `GET /websites/{website_id}/backup/download`: streamed tar.gz.
- `POST /websites/{website_id}/backup/upload`: "Upload and restore a .tar.gz archive".
- `GET|PUT /websites/{website_id}/backups_disabled`: "master org level setting".
- `Backup.kind`: manual | automatic | archive. `storageKind`: enhance | s3.
- S3 remote storage is **platform-level (MO only)**: `POST|GET|PATCH|DELETE /v2/settings/backup/remote_storage/s3`.
- The spec exposes **no** schedule or retention fields. The `websiteBackup` SettingKind exists, but its keys aren't enumerated. [I] Frequency and retention are Verpex's platform settings, and we can't see them from the spec.
- MO-only global endpoints: `GET /backups`, `PUT|DELETE /backups/{server_id}/{website_id}`, `GET /backups/{website_id}/restore/log`.

### 4.10 SSL / TLS
- `POST /v2/domains/{domain_id}/letsencrypt`, `POST /v2/domains/{domain_id}/letsencrypt_mail` (mail.* cert), `POST /v2/domains/{domain_id}/letsencrypt_preflight`
- `GET|POST /v2/domains/{domain_id}/ssl`: upload a custom cert and key.
- `GET|POST /v2/domains/{domain_id}/mail_ssl`
- `PUT /v2/domains/{domain_id}/ssl/force_ssl`

### 4.11 Email
- Mailboxes and forwarders:
  - `POST /orgs/{org_id}/websites/{website_id}/domains/{domain_id}/emails`: `NewEmail {username, mailboxName, mailboxPassword, aliases[], forwarders[], quota, isCatchAll}`. "If a password is supplied, a mailbox is created. Otherwise, forwarders must be specified."
  - `GET /orgs/{org_id}/websites/{website_id}/emails`
  - `GET|PATCH|DELETE …/emails/{email_address}`: `UpdateEmail` adds `blacklist[]`, `whitelist[]` and `hasMailbox`.
- Autoresponders:
  - `GET|POST|DELETE …/emails/{email_address}/autoresponder`: `NewAutoresponder {startDate, endDate?, enabled, subject, body}`.
  - Also `/email-client/autoresponders[/{id}]`.
- Forwarders for mailbox users: `GET|PUT /email-client/forwarders`.
- Spam:
  - `GET|PUT /websites/{website_id}/emails/{email_address}/spam_thresholds`: `{greylist?, reject, spambox}`.
  - Server-level (MO): `/servers/{server_id}/spam/ip_whitelist`, `/spam/smtp_rate_limit_hourly`, `/spam/website_generated_rate_limit_hourly`, `/email/spam/outbound_scanning`.
- DKIM and SPF:
  - `GET|PUT /websites/{website_id}/domains/{domain_name}/email-auth`: `{dkim, dkimPublicKey}`.
  - `GET …/email-auth/validate`: `{dkimIsValid, spfIsValid, …}`.
  - DMARC: [C, S2] 12.25.0 (20 Jul 2026) "Default DMARC added for all new zones … 'p=none'".
- `GET|PUT …/domains/{domain_id}/local_remote`: treat mail as local or remote (e.g. Google Workspace/M365 MX).
- Webmail: `GET …/emails/{email_address}/sso` (Roundcube SSO) and `GET …/emails/{email_address}/client-conf`.
- `ResourceName` includes `mailboxes` and `forwarders`.

### 4.12 DNS
- `GET|PATCH /orgs/{org_id}/websites/{website_id}/domains/{domain_id}/dns-zone`
- `POST …/dns-zone/records` and `PATCH|DELETE …/dns-zone/records/{record_id}`: `NewDnsRecord {kind, name, value, ttl?, proxy?}`, where `proxy` = Cloudflare proxy.
- `POST|DELETE …/dns-zone/dnssec`
- `GET …/dns-status`, `…/dns-query`
- Cloudflare integration: `/orgs/{org_id}/cloudflare*` and `/orgs/{org_id}/domains/{domain_id}/cloudflare*`.
- MO-only: `GET|POST|PATCH|DELETE /dns/third-party-providers` and `/v2/settings/dns/default-records`.
- Reseller nameservers: `POST|PATCH|DELETE /orgs/{org_id}/name-servers[/{domain}]`.
- `ResourceName` has `dnsRecords` (per-plan limit).

### 4.13 WAF / mod_security
- Per domain: `GET|PUT /v2/domains/{domain_id}/modsec_status` (`{enabled}`). `CanUse.modSec` flag.
- Server-level (MO):
  - `/v2/servers/{server_id}/modsec_status`
  - `/v2/servers/{server_id}/modsec_conf`
  - `GET|POST /v2/servers/{server_id}/owasp` (OWASP CRS version and upgrade)
- Login-policy IP/email allow/deny lists: `/settings/orchd/login-policy/*` (platform, MO).

### 4.14 Staging, clone, push-live
- `POST /orgs/{org_id}/websites?kind=staging`: needs the `stagingWebsites` resource.
- `POST /orgs/{org_id}/websites/clone`: "Clone website or push live". Body `WebsiteCloneRequest {sourceWebsiteId, destWebsiteId?, newWebsite?, excludePaths[], includeDatabases[], includeDatabaseUsers[], deleteFilesFromDestination, syncPhpVersion, runWpSearchReplace?}`.
- `GET /orgs/{org_id}/websites/clone[/{clone_id}[/log]]`
- `POST /orgs/{org_id}/websites/{website_id}/push-live`
- `GET|POST /orgs/{org_id}/staging-domain`: reseller sets the staging suffix.
- [C, S2] 12.9.0: staging domains are noindex by default. **Older than 12 months.**

### 4.15 Control-panel access and automation
- `GET|POST /orgs/{org_id}/access_tokens` and `PATCH|DELETE /orgs/{org_id}/access_tokens/{token_id}`: `NewAccessToken {roles[], tokenExpires?, friendlyName?, allowedIps[], ipRestricted?}`.
- Members: `/orgs/{org_id}/members*`. Roles include `SiteAccess` with `siteAccesses[]` website ids.
- [I] These are **control-panel** logins. They don't provide a public-website "member login area"; the site's own software has to do that.
- `POST /orgs/{org_id}/websites/{website_id}/access-tokens`: website-scoped JWT for a SiteAccess member.

### 4.16 Migration / import
- Importers for cPanel and Plesk: `/orgs/{org_id}/import/server/*` and `/v2/orgs/{org_id}/import/*` (upload analysis, `ImportKind` cPanel|plesk|pleskStreaming).
- No Joomla-to-X content migration exists; these importers move hosting accounts. [C, S1]

---

## 5. MO-only vs reseller-accessible [C, S1 descriptions, plus I]
- Every `/servers/*`, `/settings/*`, `/v2/settings/*`, `/backups` (global), `/migrations`, `/dns/third-party-providers` and `/licence` endpoint is platform-level. [I] Only Verpex, the master org, can use them. So as a Verpex reseller we **cannot**:
  - pick the web server kind
  - change OWASP/modsec config beyond the per-domain toggle
  - set S3 backup targets
  - change SMTP rate limits
  - set per-website cgroup or FS-quota overrides (master org only)
  - install legacy PHP
- Reseller/org-level endpoints live under `/orgs/{org_id}/…`, `/websites/{website_id}/…` and `/v2/domains/{domain_id}/…`.
  - Descriptions typically say: "Session holder must be at least a `SuperAdmin` in this org or a parent org, or be a member in this org that has access to the website."

---

## 6. What this means for the SpokARES rebuild [I unless marked]

1. **PHP 8.2–8.5 on LiteSpeed, OpenLiteSpeed, Apache or Nginx is the safe core.** Default PHP is 8.3 [C, S2 12.18.0]. The PHP enum still lists php56–php81 [C, S1], but availability is plan- and platform-dependent. Don't design around PHP < 8.2.
2. **The web server is unknown and not ours to choose.** Verpex picks it per server [C, S1]. Build so the site works under both Apache-style `.htaccess` (Apache/LiteSpeed) and Nginx. Or use the API-level `webserver_rewrites` and `htaccess` endpoints and avoid depending on server-specific rewrite syntax. Verify with `GET /v2/websites/{website_id}/webserver_kind`, or read it in the UI once there's an account.
3. **A static site, or a PHP CMS with MySQL/MariaDB, is the lowest-risk target.**
   - WordPress has deep first-class tooling [C, S1].
   - Joomla has a 1-click installer and user-management endpoints [C, S1; S2 12.18.0].
   - The current site is Joomla 3, which needs a real Joomla 3→4/5 migration either way. Enhance only offers a fresh install; the version comes from the installable-apps listing.
4. **Node.js is possible but a second-class option:**
   - persistent app plus reverse proxy
   - primary domain only
   - needs `persistentAppsAllowed` on the plan
   - same cgroup limits
   - not auto-redeployed after a backup restore [C, S3]
   - Prefer static builds (Astro/Eleventy/Hugo output) served directly, or PHP. Use Node only as an optional build step off-server.
5. **No git-push deploy [C, S1, S7].** Deploy by CI or local build, then `rsync` over SSH keys (rsync confirmed, S5) or SFTP/FTP. Cron is available for scheduled jobs such as a weather cache refresh or calendar ICS pulls [C, S1].
6. **Events calendar, downloads library and member area** have to live in the app layer (a CMS plugin or custom code), backed by MySQL/MariaDB (or PostgreSQL if Verpex enabled it; check `canUse.postgresql`). Nothing in Enhance itself provides them.
7. **Email for contact/join forms:**
   - Local mailboxes, forwarders, autoresponders, DKIM/SPF validation and default DMARC p=none are all available [C].
   - Website-generated mail is rate-limited hourly at the server level by Verpex [C, S1, MO-only]. Prefer authenticated SMTP from a site mailbox over `mail()`.
8. **Security:**
   - Per-domain ModSecurity toggle [C].
   - Let's Encrypt plus force-HTTPS [C].
   - `.htaccess` IP allow/deny [C].
   - Redis object cache if `redisAllowed` [C].
   - The Cloudflare proxy flag is on DNS records [C].
9. **Staging:** a `staging` website kind plus clone/push-live, if the plan grants `stagingWebsites` [C]. That's useful for a CMS rebuild before cutover.
10. **Backups:**
    - Built-in incremental backups with granular restore and tar.gz download/upload [C].
    - Emails are excluded from manual backups by default [C].
    - Schedule and retention are set by Verpex and not visible in the spec. Ask Verpex, or check the panel.
11. **Unknowns to verify in the actual panel or with Verpex** (not answerable from the spec):
    - actual web server kind
    - which PHP versions our reseller subscription allows
    - `persistentAppsAllowed`, `redisAllowed` and `postgresql` availability
    - cgroup limits: memory, vCPU, nproc, IOPS, inodes
    - backup frequency and retention
    - the website-generated mail hourly rate limit
    - whether `git` exists inside the SSH container

---

## 7. How I did it
- `curl -sS https://apidocs.enhance.com/spec/oas3-api.yaml -o /Users/sean/Projects/spokane_ares/research/hosting/_enhance-oas3-api.yaml`
- I parsed the file with PyYAML in a scratch venv and dumped every `components.schemas` item that has an `enum`. I listed every path and method with its operationId and summary, and printed the relevant schemas and descriptions.
- To regenerate the endpoint list:
  `python3 -c "import yaml;d=yaml.safe_load(open('_enhance-oas3-api.yaml'));[print(m.upper(),p,v[m].get('operationId')) for p,v in d['paths'].items() for m in v if m in('get','post','put','patch','delete')]"`
- I cross-checked release-note dates by curl-ing https://enhance.com/support/release-notes and grepping the text.
