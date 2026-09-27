# spokares.org on WordPress: security, plugins and hosting fit

- **Prepared:** 2026-09-26, for the Option B ("Carry the Message") WordPress theme build.
- **Scope:** which plugins to install (no more than 4), how to harden WordPress on Verpex Enhance, forms and mail, backups, caching, updates for a custom theme and plugin that nobody auto-updates, and the coding rules for `spokares` (theme) and `spokares-core` (site plugin).
- **Builds on:** `research/hosting/verpex-enhance.md` (the host), `research/decisions.md` (D1 hack, D5 platform, D8 no member logins, D10 library, D11 contact path, D13 never-publish rule, D15 redirects).
- **Tags:**
  - **[C]** confirmed in the cited source.
  - **[T]** tested in WordPress Playground on 2026-09-26: WordPress 7.1.2, PHP 8.4.25, Two-Factor 0.16.0. The test is in `reference/test-blueprint.json`, and the results are in section 4.3.
  - **[I]** inference: my reasoning, not stated by a source.
- **Current versions (checked 2026-09-26):**
  - WordPress 7.1.2 is current. WordPress 7.0 and 7.1 support PHP 7.4 to 8.5, and 7.0 dropped PHP 7.2 and 7.3 [C, WP-API] [C, WP-PHP].
  - PHP 8.3 gets security fixes only, until 2027-12-31. PHP 8.4 has active support until 2026-12-31 and security fixes until 2028-12-31 [C, PHP].

---

## 0. Recommendations in brief

1. **Third-party plugins: 2 required, 1 conditional, 1 slot held in reserve.**
   - **Two-Factor**, from the WordPress.org team, for everyone who can log in.
   - **UpdraftPlus (free)**, for off-host backups.
   - **LiteSpeed Cache**, *only* if the server turns out to be LiteSpeed *and* measured speed needs it. It is not installed at launch.
   - The 4th slot stays empty on purpose.
   - Everything else goes in `spokares-core`:
     - SMTP settings
     - the optional form
     - the redirect and 410 map
     - hardening
     - making 2FA mandatory
     - content types for events, the rota and documents
2. **No security suite** (Wordfence, Solid, AIOS), **no form plugin**, **no SMTP plugin**, **no redirect plugin** and **no events plugin.** Each one would add more attack surface and more to maintain than the few lines of our own code that do the same job (section 2.3).
3. **Launch with no web form.** Use role `mailto:` links, as D11 and Option B's design already do. If a join-interest form is wanted later, build it in `spokares-core` as specified in section 5.2:
   - honeypot and time trap
   - rate limit
   - fixed recipient
   - no auto-reply
   - nothing stored
   - Turnstile only if spam appears
4. **Send all mail through authenticated SMTP** from a real `website@spokares.org` mailbox on Enhance.
   - A ~30-line `phpmailer_init` hook does this [T]. The password lives in `wp-config.php` constants, not in the database.
   - Turn on DKIM and add DMARC.
5. **Editors get WordPress Editor accounts only. They get no Enhance panel access.**
   - Enhance "Collaborator" access gives "unrestricted access" to the website: files, database, and single sign-on into any WordPress user.
   - This **changes D5's "Collaborator access for editors"**.
6. **Two-factor authentication is mandatory** for every WordPress account and every Enhance panel account.
   - `spokares-core` redirects anyone who can edit but has no 2FA to their profile until they set it up.
   - The Two-Factor plugin cannot enforce 2FA by itself; its enforcement issues #845 and #846 are still open [C, 2FA-GH].
7. **Settings in `wp-config.php`:**
   - `DISALLOW_FILE_EDIT` and `DISALLOW_UNFILTERED_HTML` set to true.
   - `WP_DEBUG_LOG` set to a path outside the web root when it is used at all.
   - `WP_ENVIRONMENT_TYPE` set to `production`.
   - **Never set `DISALLOW_FILE_MODS`.** It turns off WordPress's background auto-updater [C, WP core source] [T].
8. **Harden in PHP, not in `.htaccess`.** The web server is unconfirmed (probably LiteSpeed Enterprise or OpenLiteSpeed), and OpenLiteSpeed ignores `Header` directives. The tested module `reference/hardening.php` [T]:
   - closes XML-RPC completely and turns off application passwords
   - blocks anonymous user enumeration
   - turns off comments and pings
   - allows only PDF, JPEG, PNG and WebP uploads
   - sends security headers
   - limits login attempts
   - enforces 2FA

   Two `RewriteRule` lines (section 4.4) are the only `.htaccess` hardening, because rewrites work on Apache, LiteSpeed and OpenLiteSpeed.
9. **Auto-updates on for WordPress core (majors too), all third-party plugins and the one spare default theme**, through the Enhance toolkit toggles. Put PHP on 8.4 now, and plan a yearly PHP bump each December.
10. **Deploy the custom theme and plugin from git.**
    - Deploy tagged releases only: lint, run Plugin Check, then `rsync --delay-updates` over SSH to staging, then to production.
    - Headers use `Update URI` so WordPress.org can never "update" them.
    - Rollback means redeploying the previous tag.
    - Section 8.2 has the runbook.
11. **Backups in three layers:**
    - Enhance snapshots, whose retention we still have to ask Verpex about.
    - UpdraftPlus to a Google Drive owned by a role account: the database weekly, uploads monthly, never connected to UpdraftCentral.
    - A quarterly offline copy plus a restore drill.
12. **Cheap monitoring instead of a security suite.** A weekly Enhance cron job runs `wp core verify-checksums`, plugin checksums, a hash manifest of our own code and an administrator-list check, and sends mail only on failure. Add an external uptime and keyword monitor.

---

## 1. Threat model

- **What happened before (INFERENCE from `decisions.md` D1 and `history.md` §10):**
  - The Joomla 3 site was hacked in 2026, three years after Joomla 3 reached end of life (Aug 2023).
  - It ran about 15 abandoned extensions.
  - One volunteer held all the knowledge.
  - The likely causes were unpatched code and extension sprawl, not a missing firewall. So the plan puts most of its weight on:
    - patching that happens with nobody in the loop
    - a small codebase
    - strong logins
    - backups that live off the host
- **What we defend against, most likely first [I]:**
  1. Automated exploitation of a known plugin or theme vulnerability. The defences are auto-updates and fewer plugins.
  2. Credential stuffing and brute force on `wp-login.php` and `xmlrpc.php`. The defences are 2FA, closing XML-RPC and a login throttle.
  3. Account takeover through a compromised editor's email or password. The defences are 2FA, and `DISALLOW_UNFILTERED_HTML` so a hijacked Editor cannot plant JavaScript that would run in an administrator's browser.
  4. Malicious uploads: a web shell, HTML or SVG with script, or a booby-trapped PDF recovered from the hacked site. The defences are the MIME allowlist, the no-PHP-in-uploads rewrite, and D1's offline scanning before any recovered file is uploaded.
  5. The host losing data. Host backups are "best effort", and a suspended account can be deleted after 60 days with no backups kept. The defence is backups off the host.
  6. Staff turnover: the one technical person leaves. The defences are a runbook, two named admins, and a site that keeps patching itself for years with nobody watching.
  7. Publishing mistakes: FOUO frequencies or personal contact details (D13's never-publish rule, D14). The defence is an optional pre-publish warning in `spokares-core` (section 9.4).
- **What the site does *not* have, which shrinks the attack surface [I]:**
  - no member accounts (D8)
  - no comments
  - no e-commerce
  - no stored form submissions (D11)
  - no personal data beyond 2 to 4 staff accounts

---

## 2. Plugin set (no more than 4)

### 2.1 Recommended

Facts come from the WordPress.org plugin API on 2026-09-26 [C, WP-API], and vulnerability history from NVD [C, NVD].

| # | Plugin | Status | Why | Facts | Configuration |
|---|---|---|---|---|---|
| 1 | **Two-Factor** (`two-factor`) | **Required** | Authenticator-app (TOTP) codes, email codes and backup codes. Maintained under the WordPress.org account, with security reports handled through WordPress's HackerOne programme. No upsell. | v0.16.0 (27 Mar 2026). 100k+ installs. 0.17.0-rc was tagged 2026-09-25, and GitHub commits were landing on 2026-09-26. The readme's "Tested up to 6.9.9" lags, but it ran cleanly on 7.1.2 [T]. **0.17.0 fixes "regular passwords can't bypass the two-factor requirement for REST API and XML-RPC requests"**. Our hardening already closes both paths (section 4.3). No NVD entry for this plugin. | Settings → Two-Factor: keep TOTP, email codes and backup codes. Each user sets up TOTP and prints backup codes. `spokares-core` enforces this (section 3). Auto-update on. |
| 2 | **UpdraftPlus** (`updraftplus`, free) | **Required** | Scheduled off-host backups with retention, and a restore that volunteers can use. The free version writes to Dropbox, Google Drive, Amazon S3 (or compatible), FTP, email and others; Backblaze B2 native, SFTP, OneDrive and database encryption are paid [C, UDP]. | v1.26.8 (21 Sep 2026). 4M+ installs. **History:** CVE-2026-10795 (8.1): an authentication bypass in the remote-communications (UpdraftCentral) channel, up to 1.26.4, that allows "forge[d] arbitrary RPC commands ... as the connected administrator". CVE-2024-10957 (8.8): PHP object injection, 1.23.8 to 1.24.11 [C, NVD]. | **Never connect UpdraftCentral or UpdraftVault.** Destination: Google Drive on a role Google account with 2FA. Database weekly, keep 8. Uploads monthly, keep 3. Plugins, themes and core are *not* backed up, because they reinstall from WordPress.org and git. Keep "delete local backup after upload" on. Auto-update on. |
| 3 | **LiteSpeed Cache** (`litespeed-cache`) | **Conditional. Not at launch** | Enhance's own advice for WordPress is LiteSpeed or OpenLiteSpeed with LSCache, for "~10ms TTFB for cache hits" [C, ENH-OPT]. It is only worth having if the site is slow. | v7.9.1 (1 Sep 2026). 7M+ installs. **History:** three CVSS 9.8 flaws in 2024: CVE-2024-28000 (privilege escalation), CVE-2024-44000 (account takeover through an exposed debug log) and CVE-2024-50550. Also CVE-2026-3375 (stored XSS through REST, 2026-05) and an unauthenticated SSRF up to 7.9 (2026-09) [C, NVD]. | Install only after two things: (a) the panel shows LiteSpeed or OLS, and (b) the uncached home page is measured above about 800 ms. If installed: page cache only; QUIC.cloud, image optimisation, crawler and debug log all off; purge on save of the event, rota and document types (section 7). |
| 4 | *(reserved)* | Empty | Held back for a real need that turns up after launch, for example FluentSMTP if the team wants an email log in the dashboard, or Redirection if editors must manage redirects themselves. | See section 2.3 for what each would cost. | Decide at the 3-month review. |

### 2.2 Built into `spokares-core` instead of plugins

| Function | Why not a plugin | Size | Reference |
|---|---|---|---|
| SMTP sending | Credentials stay in `wp-config.php`, not in the database. No admin UI to misconfigure. No email log holding message contents (the email logs of SMTP plugins have themselves been XSS targets; see section 2.3). | ~30 lines | `reference/smtp.php` [T] |
| Hardening (XML-RPC, application passwords, enumeration, comments, uploads, headers, login throttle, 2FA enforcement) | Nothing in it needs configuring, and it works whatever the web server. | ~150 lines | `reference/hardening.php` [T] |
| Redirects and 410s from `research/redirects.csv` (313 × 301, 469 × 410) | The map is fixed at launch. A generated PHP array matched early in `parse_request` is fast and needs no database. **It must match the query string too.** Without it, WordPress serves the home page with **200** for old Joomla URLs such as `/index.php?option=com_content&...`, including any spam URLs injected during the hack [I, from how WordPress routes unknown query vars]. | ~60 lines plus the generated data | spec only |
| Events, the net-control rota, documents | Custom post types with structured fields and locked templates give editors the simplest screens, with no events-plugin upsells and no scheduled-job sprawl. | Per the theme plan | other workstreams |
| Optional join or contact form | Section 5.2 | ~150 lines | spec only |

### 2.3 Considered and rejected

| Plugin or kind | Reason [C where cited, otherwise I] |
|---|---|
| **Wordfence, Solid Security, All-In-One WP Security** (security suites) | They overlap with 2FA and the hardening module. They are heavy: a firewall plugin runs before WordPress (`auto_prepend_file`) on every request, inside a container with unpublished CPU and RAM limits. They produce notification noise for volunteers and have large codebases of their own. The site's real risks (section 1) are handled more cheaply. |
| **Contact Form 7, WPForms Lite, Fluent Forms** | We need one small form at most. These ecosystems are a frequent CVE source through their add-ons: NVD lists 265 CVEs since 2023 matching "Contact Form 7" and 84 matching "WPForms", most in third-party add-ons, including unauthenticated PHP object injection and arbitrary file upload in 2025-26 [C, NVD]. A form plugin also stores submissions in the database by default, and D11 says store nothing. |
| **Post SMTP** | CVE-2025-11833 (9.8, missing capability check, up to 3.6.0) and CVE-2023-6875 (9.8) [C, NVD]. Its email log keeps message contents. |
| **WP Mail SMTP, FluentSMTP** | Fine if an admin UI is wanted (FluentSMTP: CVE-2024-9511, 9.8, object injection, up to 2.2.82; CVE-2026-16636, 7.2, stored XSS in email logs [C, NVD]). The built-in hook does the same job with no UI and no log. This is the reserve-slot candidate. |
| **Redirection** | Well maintained (v5.10.1, 2M+ installs), but the map is static. Use it only if editors must add redirects without the technical admin. |
| **Limit Login Attempts Reloaded** | Built around its cloud upsell. 30 lines of our own code do the job [T]. |
| **WP 2FA (Melapress)** | It has role enforcement built in, which is a genuine plus. But CVE-2026-15372 (7.5, a 2FA bypass fixed in 4.1.0) [C, NVD], and it is bigger than Two-Factor. Second choice if our enforcement shim is ever unwanted. |
| **Akismet** | Only matters with comments or forms, and it sends content to a third party. Turnstile (section 5.2) covers the one-form case. |
| **Any events or calendar plugin** | Replaced by custom post types. D9's groups.io or Google calendar remains an option for members. |

---

## 3. Accounts, roles and access

**The Enhance panel is the master key.** A panel user with write access to the website can:
- get a single sign-on URL for any WordPress user (`GET .../wordpress/users/{user_id}/sso`, "Session holder must have write access to the website") [C, SPEC]
- edit `wp-config.php` values [C, SPEC]
- use the file manager, the database and backups [C, hosting §3.1]

[I] Panel single sign-on almost certainly sets the WordPress session directly, so it **bypasses WordPress 2FA**. Treat panel access as full administrator access.

| Who | WordPress role | Enhance panel | 2FA |
|---|---|---|---|
| Technical admin (the project owner or named successor) | Administrator | Reseller or customer owner | TOTP on WordPress **and** on the panel [C, panel 2FA: FEAT] |
| Break-glass admin (the EC, W7TSC, or whoever D5 names) | Administrator, rarely used | A second panel owner, so the account never depends on one person | TOTP on both, with backup codes kept offline |
| Editors (AG7QP plus one) | **Editor** | **None.** This changes D5's "Collaborator access for editors". Collaborator is "unrestricted access to a specific website" [C, hosting §3.1 Team access] | TOTP, enforced |
| Members and the public | none: registration off, no subscribers (D8) | none | n/a |

**What the Editor role already cannot do [C, WP core `schema.php` and `site-editor.php`]:**
- install or update plugins or themes
- edit users
- open the Site Editor or change templates, template parts or navigation (these need `edit_theme_options`, which only Administrator has)

The layout is locked by capability, not by convention.

**What the Editor role can do by default that we remove:** `unfiltered_html`, which lets a user post raw `<script>`. `DISALLOW_UNFILTERED_HTML` removes it for every role, administrators included [C, core `capabilities.php`] [T: admin `unfiltered_html=false` with the constant, `true` without it]. It also removes `edit_css` (Additional CSS), which we don't want editors using anyway.

**Enforcing 2FA.** The Two-Factor plugin has no role enforcement; issues #255, #845 and #846 are open [C, 2FA-GH]. `reference/hardening.php` §8 does it instead:
- any user with `edit_posts` who has no 2FA provider is redirected from every admin screen to `profile.php#two-factor-options`
- AJAX requests are left alone
- it uses `Two_Factor_Core::is_user_using_two_factor()` [C, plugin source] [T, the method exists and returns false for the fresh admin]

**Account hygiene [I]:**
- usernames are not callsigns or display names
- email is a role address where possible (`webmaster@`)
- passwords are generated and kept in a password manager
- remove an editor's account (reassign their content) on the day they step down
- the weekly check (section 10) mails a change in the administrator list

**Other access:**
- **Verpex billing account:** owned through a role email, 2FA on, recorded in the credentials register that D2 recommends [I].
- **SSH:** one ed25519 key per person, with a passphrase, added under Website → Developer tools; remove it when that person leaves. There is no custom SSH port [C, hosting §3.1].

---

## 4. WordPress hardening

### 4.1 Install-time choices (Enhance one-click installer)

- **PHP 8.4.**
  - Active support until 2026-12-31, security fixes until 2028-12-31 [C, PHP]. Enhance's default is 8.3 [C, hosting §3.1].
  - Test the theme and plugin on 8.3, 8.4 and 8.5, all supported by WordPress 7.x [C, WP-PHP].
  - Turn OPcache on [C, ENH-OPT].
- **Admin username:** not `admin`. **Admin email:** `webmaster@spokares.org`.
- **Table prefix:** not `wp_`, if the installer allows it (the core hardening guide suggests this [C, WP-HARD]). A small gain; don't fight the installer over it.
- **After install:**
  - delete Hello Dolly, Akismet and every theme except `spokares` and one current default theme (the fallback)
  - Settings → General: "Anyone can register" **off**
  - Settings → Discussion: pingbacks and comments **off**
- **Force HTTPS** per domain in Enhance. Siteurl and home use `https://` and one canonical host (D15 decides www or apex).

### 4.2 `wp-config.php`

| Constant | Value | Why |
|---|---|---|
| `DISALLOW_FILE_EDIT` | `true` | Removes the dashboard file editor, a classic post-login path to code execution [C, WP-CFG] [C, WP-HARD] |
| `DISALLOW_UNFILTERED_HTML` | `true` | No role can post raw script (section 3) [C, core] [T] |
| `DISALLOW_FILE_MODS` | **leave unset** | It also turns off the background auto-updater: `WP_Automatic_Updater::is_disabled()` returns true when file modifications are disallowed [C, core `class-wp-automatic-updater.php`] [T: `auto_updater_disabled=true`, `update_plugins=false`]. Auto-updates matter more to us than blocking installs, and only the two administrators can install anyway. |
| `AUTOMATIC_UPDATER_DISABLED` | leave unset | Same reason |
| `WP_AUTO_UPDATE_CORE` | leave to the Enhance toolkit, set to **major** | The toolkit's `autoUpdateCore` has two values, `major` and `minor` [C, SPEC]. Choose major (section 8.1). |
| `FORCE_SSL_ADMIN` | `true` | [C, WP-CFG] |
| `WP_ENVIRONMENT_TYPE` | `production`; `staging` on staging; `local` in Playground | Lets `spokares-core` behave differently per environment (for example, no SMTP on staging) [C, WP-CFG] |
| `WP_DEBUG` / `WP_DEBUG_DISPLAY` | `false` | |
| `WP_DEBUG_LOG` | `false`, or a **path outside `public_html`** when debugging | The default `wp-content/debug.log` is inside the web root, and `.htaccess` cannot be relied on to hide it (OLS and Nginx). CVE-2024-44000 in LiteSpeed Cache was an account takeover through exactly this kind of public debug log [C, NVD] [I on the lesson] |
| `SPOKARES_SMTP_HOST`, `_PORT`, `_USER`, `_PASS` | see section 5.3 | Mail credentials, kept out of the database and git |
| `WP_POST_REVISIONS` | `20` | Keeps the database small (a convenience, not a security measure) |
| Salts | unique, from the installer | Rotate all eight after any suspected compromise; this logs everyone out [I] |

**Permissions:**
- files 644, folders 755, `wp-config.php` 600 [C, WP-HARD recommends 400 or 440; I: 600 because PHP runs as the site user inside the container].
- **Keep `wp-config.php` in `public_html`.** Core allows it one level up [C, WP-HARD], but the Enhance toolkit reads and writes its values through the API [C, SPEC] and discovers installs [C, ENH-WP]. Moving it risks breaking the toolkit, for little gain on a host where PHP always parses `.php` [I].

### 4.3 The hardening module (`reference/hardening.php`), tested

Copy it into `spokares-core` as `includes/hardening.php`. The site plugin agent owns the final file. What it does, and what the Playground test showed on WordPress 7.1.2 / PHP 8.4.25 with Two-Factor 0.16.0 active:

| # | Measure | Test result [T] |
|---|---|---|
| 1 | **XML-RPC closed.** A request to `xmlrpc.php` gets 403 before the server starts. The `xmlrpc_enabled` filter alone is not enough: it "does not control whether pingbacks or other custom endpoints that don't require authentication are enabled" [C, WP-XMLRPC]. `X-Pingback` is removed. | `xmlrpc_enabled=false`, `xmlrpc_methods=0`. The early-exit branch was reviewed, not exercised over HTTP. |
| 2 | **Application passwords off** (`wp_is_application_passwords_available`) | `false` |
| 3 | **Comments and pings closed** everywhere | `comments_open=false` |
| 4 | **No anonymous user enumeration:** the `/wp/v2/users` routes are removed for visitors who are not logged in, the users sitemap is removed, author archives return 404, and login errors are generic | anonymous `/wp/v2/users` **404**; `/wp/v2/users/1` **404**; logged-in admin **200** (the block editor still works); sitemap providers `posts,taxonomies`; `login_errors` generic |
| 5 | **Upload allowlist:** PDF, JPEG, PNG and WebP only. No SVG, HTML, Office documents or archives. D10 already re-exports documents to PDF | `mimes=pdf,jpg\|jpeg\|jpe,png,webp` |
| 6 | **Security headers from PHP** (`send_headers`): `nosniff`, `Referrer-Policy`, `X-Frame-Options`, `Permissions-Policy`, and a CSP limited to directives that cannot break block markup (`frame-ancestors 'self'; base-uri 'self'; form-action 'self'; object-src 'none'`). HSTS starts at 1 day; raise it to 1 year after a month with no problems, with no `preload` and no `includeSubDomains` until every subdomain is on HTTPS. | Values returned as specified; the headers themselves are not observable in the CLI test. Check on staging with `curl -I`. |
| 7 | **Login throttle:** 10 failures from one IP within 15 minutes lock that IP out for 15 minutes, even with the right password. It uses `REMOTE_ADDR` only. | Correct password → user 1. After 10 failures → `spokares_locked`. A different IP → user 1. |
| 8 | **2FA enforcement** (section 3) | Two-Factor detected; `is_user_using_two_factor(1)=false`, so the redirect would fire |
| 9 | `wp_generator` meta removed | — |

**Not done, on purpose [I]:**
- **Renaming `wp-login.php`.** Obscurity that breaks password-reset links and the toolkit's single sign-on.
- **Blocking the REST API for anonymous visitors.** The front end may use it.
- **Turning off core sitemaps.** They help search engines, and D1 needs Search Console.

### 4.4 `.htaccess`: only rewrite rules, which work on Apache, LiteSpeed and OLS

OpenLiteSpeed honours only `RewriteRule` in `.htaccess`, not `Header`, `<Files>` or `Require` [C, hosting §4.2]. Nginx ignores `.htaccess` entirely. Keep `.htaccess` to rewrites, placed *above* the `# BEGIN WordPress` block:

```apache
# spokares: refuse PHP anywhere under uploads (web shells) and direct hits on wp-includes PHP.
RewriteEngine On
RewriteRule ^wp-content/uploads/.*\.(php[0-9]?|phtml|phar)$ - [F,L,NC]
RewriteRule ^wp-includes/[^/]+\.php$ - [F,L]
```

- The `wp-includes` rule is from the core hardening guide [C, WP-HARD]. The uploads rule is [I].
- Check both on staging with `curl -I https://.../wp-content/uploads/x.php`: expect 403, not 404 or 200.
- **Enhance toggle to check [I, unverified]:** the Enhance API's WordPress settings include an undocumented boolean `disallowNonWpPhp` [C, SPEC `WpSettings`]. The name suggests it blocks PHP files that are not part of WordPress, which is exactly the web-shell defence.
  - If the panel shows a matching switch, turn it on in staging first and confirm the site still works.
  - Ask Verpex what it does (section 12).
- **What does not work here:**
  - The Enhance wp-admin IP lockdown works on Apache only [C, ENH-RAA].
  - Panel directory passwords do not exist [C, hosting §3.1].
  - ModSecurity (OWASP CRS) "can only be enabled on Apache and Nginx" [C, ENH-MODSEC]. On a LiteSpeed server we should expect **no WAF**, which is one more reason for a small attack surface.
  - If ModSecurity is available, turn it on in staging and test saving in the block editor. CRS keeps its WordPress false-positive exclusions in a separate plugin, and Enhance does not say it loads it [C, CRS-WP] [C, ENH-MODSEC].

### 4.5 Uploads and the Library

- **Recovered files first go through D1's offline pipeline:** MIME check, `clamscan`, `oletools`, then re-export to PDF. Only then does anyone upload them. Never upload an original from the hacked account.
- **Size:** Enhance defaults `upload_max_filesize` and `post_max_size` to 1000M [C, hosting §3.1]. Lower both to 32M in the php.ini editor. AUP: multimedia up to 10 GB, and no file-host use [C, hosting §2.2].
- **Enhance's SVG script check (Aug 2026)** doesn't matter here, since SVG uploads are refused anyway [C, hosting §3.1].
- **Filenames:** WordPress sanitises them. ISO-dated names are D10's convention.

---

## 5. Forms and mail

### 5.1 Launch: no form

Option B's pages use only `mailto:` role addresses: `join@`, `ec@` and `webmaster@` (`design/round3/option-b-lines-down/about.html` §Contact). That matches D11, which says role forwarders first, at most one form, and never store submissions. **Zero form code means zero form attack surface.**

- Render the addresses through core's `antispambot()` to slow down harvesting [I].
- Enhance's rspamd filters what comes in [C, hosting §3.1].

### 5.2 If a join-interest form is added later: the specification for `spokares-core`

- **Transport:** a normal POST to `admin-post.php` with `action=spokares_join` (`admin_post_nopriv_` plus `admin_post_`), then a 303 redirect to a thank-you anchor. No REST endpoint is needed.
- **Fields:**
  - name (max 100 characters)
  - callsign (optional, validated against `^[A-Z0-9/]{3,10}$` after uppercasing)
  - email (`is_email()`)
  - one interest choice (allowlisted values)
  - message (max 2,000 characters, `sanitize_textarea_field`)

  Reject anything over the limits, and strip CR and LF from every single-line field.
- **Anti-spam, with no third party by default [I]:**
  1. A honeypot field hidden with CSS.
  2. A time trap: a hidden `t` value holding `time()` plus an HMAC made with `wp_hash()`. Reject a submission if it arrives in under 4 seconds or is older than 2 hours. **Do not use a WordPress nonce here.** Every logged-out visitor gets the same nonce ("does not prevent guests from CSRF attacks"), and a nonce lasts 12 to 24 hours [C, WP-NONCE]. It would also break once pages are cached.
  3. A per-IP rate limit of 5 an hour, and a site-wide limit of 20 an hour, both in transients. This keeps the site far below Verpex's mail cap, which is published as 100/hour for shared accounts. Over the cap, messages are **rejected, not queued** [C, hosting §4.3].
  4. **Only if spam gets through:** Cloudflare Turnstile.
     - It is free: up to 20 widgets and unlimited challenges [C, CF-PLANS].
     - It needs no Cloudflare DNS: it "can be embedded into any website without sending traffic through Cloudflare" [C, CF-TS].
     - It "does not access, store, or transmit user communications, form entries" [C, CF-TS].
     - Server-side `siteverify` is mandatory. Tokens are single-use and valid for 300 seconds [C, CF-SV]. Call it with `wp_remote_post()` with a 10-second timeout, and **fail closed** (show a "please email join@" fallback).
- **Delivery:**
  - The recipient is a fixed role address read from a constant or option, **never taken from the request**.
  - `Reply-To` is set only if `is_email()` passes.
  - The subject is fixed text plus the sanitised name.
  - **No auto-reply to the submitter.** An auto-reply turns the form into a relay that sends mail to any address someone types in [I].
- **Storage:** none. No database row, no log of the contents (D11). If `wp_mail()` fails, show the `mailto:` fallback and log only the error code.
- **Accessibility and privacy:** real labels and a visible error summary, and one sentence on the page about where the message goes.

### 5.3 Authenticated SMTP (`reference/smtp.php`), tested

Why: `mail()` routing and its sender identity on Verpex Enhance are unverified. Verpex's AUP lets it block or queue mail without notice. A community thread reports WordPress forms that "appear to send but no messages are actually delivered" when relying on `mail()` [C, hosting §3.1, §4.3; notes-independent].

- **Mailbox:** create `website@spokares.org` on Enhance, used only for sending, with a long random password. Put the four constants in `wp-config.php`.
  - Host: `mail.spokares.org`, the name on Enhance's Let's Encrypt certificate for mail [C, hosting §3.1 SSL].
  - Port 587 with STARTTLS (or 465). Confirm the values in the panel's client-configuration screen.
- **The hook:**
  - sets `isSMTP`, the host, port, authentication and TLS, and a 15-second timeout
  - sets `setFrom(user, 'Spokane County ARES-ACS website', false)` and `Sender`, so the envelope and header senders are aligned (the third argument is `false`, as the `phpmailer_init` reference notes advise [C, WP-PHPM])
  - logs `wp_mail_failed`

  [T: `mailer=smtp host=mail.spokares.org port=587 secure=tls auth=true from=website@spokares.org sender=website@spokares.org`]. Actual delivery can only be tested on the real host.
- **DNS (D3):**
  - Turn on Enhance DKIM for the domain.
  - Keep SPF at Enhance's `v=spf1 a mx ~all` after InMotion is removed.
  - Add `_dmarc` with `p=none; rua=mailto:dmarc@spokares.org` (a role mailbox), and move to `quarantine` after 4 to 6 weeks of clean reports [C, hosting §4.3] [I on the timeline].
  - Role *forwarders* to Gmail and other providers break SPF on the forwarded hop, so DMARC must pass on **DKIM** alignment. That is why DKIM is mandatory [I]. Check with a real forwarded test message.
- **What else uses this path:** password resets, 2FA email codes, update notices and the weekly integrity mail. It is a few messages a week.
- **Never send bulk member mail from the site.** Use groups.io (D8) [C, hosting §4.3].

---

## 6. Backups and recovery

| Layer | What | Where | Frequency and retention | Notes |
|---|---|---|---|---|
| 1 | Enhance snapshots | Verpex, off-server | The ToS says "twice-daily ... best effort"; retention is **not published** [C, hosting §3.1] | Confirm self-restore is enabled and ask about retention (section 12). A suspended account can be deleted after 60 days "We do not keep any backups" [C, hosting §4.5]. |
| 2 | UpdraftPlus: database plus `uploads` | Google Drive of a role Google account (`spokares.webmaster@...`) with 2FA; Updraft gets a dedicated folder | Database weekly (keep 8); uploads monthly (keep 3) | No UpdraftCentral or Vault connection. Free-version database dumps are not encrypted [C, UDP], so the Drive account is sensitive: it holds password hashes and TOTP secrets from `wp_usermeta` [I]. Only the two admins can reach it. |
| 3 | Offline copy | The technical admin's encrypted drive | Quarterly: the Enhance portable `.tar.gz`, plus a git tag of the theme and plugin | The portable backup excludes cron jobs, so keep the cron lines in the runbook [C, hosting §4.5] |

- **Restore drill, twice a year [I]:**
  - Restore the latest Layer 2 set into a fresh Playground or staging site.
  - Check that events, the rota, documents and users came back.
  - Record the date in the runbook.

  A backup that has never been restored is not a backup.
- **AUP:** do not keep backup archives on the hosting account (AUP 3.5 and the "offsite-backup use" ban) [C, hosting §2.2, §4.4]. Updraft's "delete local copy after upload" setting takes care of that.
- **Zero-plugin alternative** (if the owner wants only 1 plugin) [I]:
  - The technical admin's machine pulls the backups itself:
    - `ssh site 'cd public_html && wp db export - | gzip' > db-$(date +%F).sql.gz`
    - `rsync -a site:public_html/wp-content/uploads/ uploads/`
  - It is better against ransomware, because the host holds no credentials to the backup store.
  - But it depends on one person's machine, so it is the fallback, not the default.

---

## 7. Caching and performance

- **Launch without a page cache [I].** The reasons:
  - It is a small site with low traffic.
  - PHP starts on demand, and OPcache is on.
  - The theme renders time-sensitive content: the next net, net control this week, upcoming exercises. A page cache would show stale data unless it is purged on every save *and* expires at midnight.
  - LiteSpeed Cache's 2024-26 vulnerability record (section 2.1) is a real cost.
- **Measure after launch.** Take the home page time-to-first-byte from outside (for example with an uptime monitor). If it is consistently above about 800 ms, or traffic spikes during an activation, add LSCache as the conditional plugin, configured as in section 2.1:
  - a public TTL of 1 hour for pages with the next-net line
  - purge on `save_post` for the event, rota and document types, done by `spokares-core` calling `do_action( 'litespeed_purge_all' )` if the plugin is present [I]
- **Theme rules that keep caching optional [I]:**
  - Compute "next net" and "upcoming" on the server at render time from the stored data.
  - Put an ISO timestamp in a `data-` attribute, so a small script can re-evaluate "today" or "tonight" in the browser if the HTML is ever cached.
  - Never print per-user data on public pages.
- **Self-host the fonts.**
  - Option B loads Crimson Pro and Schibsted Grotesk from Google Fonts (`design/round3/option-b-lines-down/*.html` `<link>`s). Both are SIL OFL [C, GF-OFL], so ship the WOFF2 files in the theme through `theme.json` `fontFace`.
  - This removes the only third-party request, keeps the privacy page's "no tracking" statement true, and lets the CSP stay `'self'`-only [I].
- **Embeds:** a video link or a `youtube-nocookie.com` embed only, and no other third-party scripts.

---

## 8. Updates

### 8.1 Third-party code: patched automatically

| Component | Setting | Mechanism |
|---|---|---|
| WordPress core | **Auto, major** (Enhance toolkit `autoUpdateCore: major`) [C, SPEC] | [I] The toolkit's per-plugin (`enabled`/`disabled`) and per-theme (`on`/`off`) toggles look like WordPress's own auto-update flags, so updates run through WordPress's background updater. Enhance replaces WP-Cron with an **hourly** system cron [C, hosting §3.1]. That is why `DISALLOW_FILE_MODS` must stay unset (section 4.2). |
| Two-Factor, UpdraftPlus (and LSCache if added) | **Auto** | Toolkit toggle, or Plugins → "Enable auto-updates" |
| Spare default theme | **Auto** | — |
| PHP | Manual, **every December**: test on staging, then switch | PHP 8.4 security support ends 2028-12-31 [C, PHP] |
| Enhance platform | Verpex | Its upgrade lag is unknown [C, hosting §4.5] |

**Why major core updates are automatic too [I]:**
- Two majors shipped in 2026: 7.0 in May and 7.1 in August [C, WP-PHP links].
- A volunteer site that waits for someone to click "update" ends up years behind, which is how Joomla 3 was left.
- The custom theme uses stable APIs (`theme.json`, block templates, `register_block_type` with `block.json`, custom post types, post meta).
- The risk of a major breaking something is covered by the pre-release check below and by rollback: Enhance snapshots, or WP-CLI `wp core update --version=X --force`.

**Pre-release check, about 2 weeks before each major [I]:**
- The technical admin boots the dev blueprint with `--wp=beta` (or the RC) and clicks through the pages and the editor screens.
- If something breaks, ship a theme fix before release day.

### 8.2 The custom theme and plugin (never auto-updated): git-based deploy

**Repository layout (already set by the project):** `wordpress/theme/spokares/` and `wordpress/plugins/spokares-core/`, versioned in git with the rest of `spokane_ares`.

**Headers:**
- Plugin: `Update URI: false`. Any value other than a WordPress.org URL stops WordPress updating a plugin from WordPress.org, and "`Update URI: false` also works" [C, WP-UPDURI].
- Theme: `Update URI: false`. Theme support for the header arrived in 6.1.0 [C, core `class-wp-theme.php` "@since 6.1.0 Added `Update URI` header"].
  - WordPress now sends `UpdateURI` for themes to the update API and handles non-WordPress.org hosts in `wp_update_themes()` [C, core `update.php` 7.1].
  - Whether the WordPress.org API honours it for themes is not documented [I].
  - Belt and braces: `spokares-core` filters `site_transient_update_themes` to unset the `spokares` stylesheet.
  - The slugs `spokares` (theme) and `spokares-core` (plugin) are **not taken** on WordPress.org today [C, WP-API "Theme not found" / "Plugin not found", 2026-09-26].
- `Requires at least: 7.0`, `Requires PHP: 8.3`, `Tested up to: 7.1`, and `Version:` bumped on every release. Enqueued assets use `filemtime()` or the version constant for cache-busting.

**Release runbook (technical admin) [I, built on hosting §4.1: rsync over SSH on port 22, no Git deploy, no deploy API]:**

```bash
# 0. Clean tree on a signed-off tag only.
git switch main && git pull && git status --porcelain   # must print nothing
git tag -a spokares-v1.4.0 -m "..." && git push --tags

# 1. Checks (locally or in CI with no deploy secrets).
composer run lint           # PHPCS: WordPress-Extra + PHPCompatibilityWP (testVersion 8.3-)
npm ci && npm run build     # only if blocks use @wordpress/scripts; ships build/ not src/
npx @wp-playground/cli@3.1.55 server ... # dev blueprint: click through; run Plugin Check:
#   wp plugin check spokares-core   (Plugin Check 2.1.0 [C, WP-API])

# 2. Staging first (Enhance staging site, or a second website on a staging subdomain).
rsync -rlptz --delete --delay-updates --chmod=D755,F644 \
  --exclude-from=wordpress/.distignore -e "ssh -i ~/.ssh/spokares_deploy" \
  wordpress/theme/spokares/ STAGE_USER@STAGE_HOST:public_html/wp-content/themes/spokares/
rsync ...same flags... wordpress/plugins/spokares-core/ STAGE_USER@STAGE_HOST:public_html/wp-content/plugins/spokares-core/
ssh STAGE 'cd public_html && wp core verify-checksums && wp plugin verify-checksums --all --exclude=spokares-core'

# 3. Production: the same two rsyncs to the live site, then:
ssh LIVE 'cd public_html && wp cache flush;   # add: wp rewrite flush  (only when a post-type slug changed)
  cd wp-content && find themes/spokares plugins/spokares-core -type f -print0 | sort -z | xargs -0 sha256sum > ~/.spokares-manifest.sha256'

# 4. Roll back = check out the previous tag and repeat step 3.
```

**Notes on the runbook:**
- **`-rlptz`, not `-a`.** The site user can't set owner or group [I]. `--delay-updates` puts all files in place at the end, which avoids a half-deployed theme.
- **`.distignore`** keeps `node_modules/`, `src/`, `tests/`, `composer.*`, `package*.json`, `.git*`, `phpcs.xml*` and any `*.md` out of production.
- **Ship no Composer vendor code in production** [I]. Anything we don't auto-update has to stay tiny.
- **Database changes** in `spokares-core`:
  - additive only, versioned with an `spokares_core_db_version` option
  - run once on `plugins_loaded` when the version changes
  - never destructive without a backup taken the same day [I]
- **The hash manifest** (`~/.spokares-manifest.sha256`, outside the web root) feeds the weekly integrity check (section 10). A dotfile is covered by the AUP exception for "hidden files needed to operate the website" [C, hosting §4.4].
- **CI:** if CI ever deploys, use a GitHub Environment with a required reviewer and a separate SSH key. Never an Enhance API token, since tokens cannot be scoped to one website [C, hosting §3.1 API].

---

## 9. Coding standards for `spokares` and `spokares-core`

### 9.1 Tooling

- **PHP_CodeSniffer with WordPress Coding Standards 3.4.1** (released 2026-07-27) [C, WPCS-GH]:
  - `WordPress-Extra` ruleset, which includes the `WordPress.Security.*` sniffs for escaping, nonces, sanitisation and `ValidatedSanitizedInput`
  - plus `PHPCompatibilityWP` with `testVersion 8.3-`
  - The build fails on any error. `// phpcs:ignore` needs a reason in the comment.
- **Plugin Check** (`plugin-check` 2.1.0) run against `spokares-core` in Playground before every release [C, WP-API].
- **Optional:** PHPStan level 5 with `szepeviktor/phpstan-wordpress` [I].
- **No local PHP exists on this Mac** (`which php` finds nothing). Options:
  - run PHPCS in CI (GitHub-hosted runners have PHP), or
  - `brew install php composer` on the technical admin's machine, or
  - `npx @wp-playground/cli php` for quick checks [C, CLI help].

### 9.2 Rules (each is a review checklist item)

1. **Guard every PHP file:** `defined( 'ABSPATH' ) || exit;`.
2. **Escape late, at output:**
   - `esc_html()` for text
   - `esc_attr()` for attributes
   - `esc_url()` for links
   - `wp_kses_post()` for trusted rich text
   - `wp_json_encode()` for data passed to JavaScript
   - `esc_html__()` / `esc_attr_e()` for translatable strings

   This includes block `render.php` files, which print editor-supplied attributes: use `get_block_wrapper_attributes()`. Never `echo` a raw meta value.
3. **Sanitise on input:**
   - `sanitize_text_field`, `sanitize_textarea_field`, `sanitize_email`, `absint`, `esc_url_raw`
   - allowlists for enumerations
   - `wp_unslash()` before sanitising superglobals

   Give every `register_post_meta()` a `type`, `single`, `sanitize_callback`, `auth_callback` (for example `current_user_can( 'edit_post', $object_id )`) and `show_in_rest` with a schema.
4. **Check capabilities, not roles:**
   - `current_user_can( 'edit_post', $id )`, or a custom capability mapped by `map_meta_cap => true` on each custom post type
   - never `in_array( 'editor', $user->roles )`
   - Nonces are not authorisation: "Protect your functions using `current_user_can()`" [C, WP-NONCE].
5. **Nonces on every state change by a logged-in user:**
   - admin forms: `wp_nonce_field()` + `check_admin_referer()`
   - AJAX: `check_ajax_referer()`
   - REST uses the `wp_rest` nonce automatically through `apiFetch`

   Public forms use the HMAC time token in section 5.2 instead.
6. **REST routes:**
   - Every `register_rest_route()` has a `permission_callback`. Since 5.5, leaving it out triggers a notice, and public read-only routes must say `__return_true` explicitly [C, WP-REST].
   - Every argument has `validate_callback` and `sanitize_callback` [C, WP-REST].
   - A public route returns only published data.
7. **Queries:**
   - use `WP_Query`, `get_posts` and the meta API
   - any direct `$wpdb` call uses `$wpdb->prepare()` with placeholders, and `$wpdb->esc_like()` for `LIKE`
   - no string-built SQL
8. **Forbidden:**
   - `eval`, `create_function`, `extract`, `unserialize` on anything that isn't ours (use JSON)
   - `file_get_contents`/`curl` for URLs (use `wp_remote_get` with a timeout)
   - `$_REQUEST`
   - writing files at runtime
   - `allow_url_include`-style includes
   - shelling out
9. **Output formats beyond HTML:**
   - An ICS feed (if the theme publishes one) must escape `\`, `;`, `,` and newlines in TEXT values, fold lines at 75 octets, and never let a field contain raw CRLF. Otherwise an editor's text can inject calendar properties [I from RFC 5545 §3.3.11].
   - JSON goes through `wp_json_encode`.
10. **Admin UI:**
    - menu pages declare a capability
    - use the Settings API with `register_setting( ..., array( 'sanitize_callback' => ... ) )`
    - no `admin_post_nopriv_` handler except the public form
11. **Editor lock-down, which doubles as hard-to-break editing [I]:**
    - custom post type templates with `template_lock` (`all` or `contentOnly`)
    - `allowed_block_types_all` limited to the blocks each screen needs, with `core/html` and `core/shortcode` excluded
    - `theme.json` with custom colours, font sizes and spacing turned off
12. **Prefix everything** with `spokares_` / `SPOKARES_`. One text domain per package.
13. **Dependencies:** no runtime Composer or npm packages in PHP. JavaScript is built with `@wordpress/scripts` and uses only the `wp.*` globals WordPress provides.

### 9.3 Reviews

- Each release gets a second pair of eyes on the diff, even a quick one, before the tag [I].
- A yearly security read-through of all custom PHP, timed with the December PHP bump [I].

### 9.4 Optional: a publishing-safety check

`spokares-core` can warn, not block, on `save_post` when content matches configurable patterns:
- county, hospital or SHARES terms, and `800 MHz`, from D13's never-publish list
- phone-number shapes
- non-role `@` addresses

The warning is an admin notice: "This page may contain something the never-publish rule forbids (D13): ...". This catches the most likely *editorial* security failure [I].

---

## 10. Monitoring and incident response

**A weekly Enhance cron job** (Website → Developer tools → Cron; it runs inside the container with `wp-cli` [C, hosting §3.1 SSH/Cron]):

```bash
cd ~/public_html && {
  wp core verify-checksums --quiet &&
  wp plugin verify-checksums --all --exclude=spokares-core --quiet &&
  (cd wp-content && sha256sum -c --quiet ~/.spokares-manifest.sha256) &&
  [ "$(wp user list --role=administrator --field=user_login | sort | tr '\n' ' ')" = "EXPECTED_ADMIN_1 EXPECTED_ADMIN_2 " ]
} || wp eval 'wp_mail( "webmaster@spokares.org", "spokares.org weekly integrity check FAILED", "Log in over SSH and re-run the checks by hand. See the runbook." );'
```

- It is quiet on success and sends one email on failure.
- Checksums catch modified core and plugin files. The manifest catches changes to our own code, the one thing the WordPress.org checksums cannot cover [I].

**Also:**
- **Uptime monitoring:** an external monitor with a free tier, run with a **keyword** check on the home page (for example "Spokane County ARES-ACS") every 5 minutes, plus TLS-expiry alerts to `webmaster@`. It also catches Verpex IP changes that break DNS [C, hosting §4.5 on IP changes] [I on the method].
- **Search Console** (Domain property, DNS TXT, per D1): watch "Security issues" and new URLs that are not in `redirects.csv`.
- **Verpex** includes Monarx malware protection [C, hosting §3.1]. Ask how its alerts reach us (section 12).
- **No long-term access logs:** raw logs are discarded about every 5 minutes [C, hosting §4.5]. The login throttle and 2FA don't rely on logs.

**If compromise is suspected (the runbook page) [I]:**
1. Turn on maintenance mode from the Enhance toolkit [C, ENH-WP].
2. Take a portable backup for forensics and keep it off the host.
3. Rotate:
   - the Enhance panel and SSH keys
   - WordPress passwords, with 2FA re-enrolled
   - the eight salts
   - the SMTP mailbox password
   - Google Drive and the Verpex login
4. Restore from the last Layer 2 set that is known to be good, into a *fresh* WordPress: core from WordPress.org, plugins reinstalled from WordPress.org, and the theme and plugin from the git tag. Never restore the site's files from the compromised copy.
5. Re-run the integrity checks and review the administrator list.
6. Ask Search Console for a review if the site was flagged.
7. Write down what happened in `decisions.md`.

---

## 11. One-time setup checklist (in order)

1. Enhance: a separate customer org for spokares.org. PHP 8.4 and OPcache on. **Force HTTPS** on. Panel 2FA for both panel owners.
2. Read the **web server kind** and check for the `disallowNonWpPhp` toggle, and the ModSecurity toggle (Apache or Nginx only).
3. One-click WordPress install (non-default username and table prefix). Remove the extras. Registration, comments and pings off.
4. Put the `wp-config.php` constants in place (section 4.2), with `wp-config.php` at 600.
5. Upload the theme and `spokares-core` over rsync (section 8.2) and activate them. Add the two rewrite rules (section 4.4).
6. Install **Two-Factor** and set up TOTP plus backup codes for both admins. Create the editor accounts; the enforcement shim walks them through 2FA on first login.
7. Create the `website@` mailbox. Add the SMTP constants. Turn on DKIM. Add DMARC `p=none`. Send a password reset to each role forwarder to test delivery.
8. Install **UpdraftPlus**, connect a role Google Drive, set the schedule, and run and restore once into Playground.
9. Turn on the toolkit auto-updates: core "major", every plugin, the spare theme.
10. Weekly integrity cron, manifest, uptime monitor, Search Console.
11. Check the headers and 403s on staging and live (`curl -I`); `/?author=1` should be 404; `/wp-json/wp/v2/users` should be 404 when logged out; `xmlrpc.php` should be 403.
12. Write the one-page runbook: who holds which keys, the release steps, restore steps and the incident steps.

---

## 12. Questions to add to the Verpex list

These add to `decisions.md` "Questions for Verpex".

1. What does the WordPress setting **`disallowNonWpPhp`** (API `WpSettings`) do, and is it exposed in the panel for our package?
2. How does toolkit **auto-update** run: WordPress's own background updater (so `DISALLOW_FILE_MODS` must stay unset), or WP-CLI on Enhance's schedule? At what time does the hourly WP-Cron replacement run?
3. Does panel **WordPress single sign-on** create a session directly (bypassing WordPress 2FA plugins)? Is there an audit log of SSO use?
4. Is **ModSecurity** available on our server? If it is, which CRS version, and are the WordPress exclusions loaded?
5. **Monarx:** is it active on Enhance servers, and how do detections reach the customer?
6. For the SMTP host: are `mail.spokares.org` port 587 (STARTTLS) and 465 (TLS) reachable from inside the website container? Do forwarders use SRS?

---

## 13. Sources

All were accessed 2026-09-26 unless noted. Hosting facts marked "hosting §x" come from `research/hosting/verpex-enhance.md` (researched and verified 2026-09-25), which carries its own citations.

| Key | Source |
|---|---|
| WP-API | WordPress.org APIs: core `https://api.wordpress.org/core/version-check/1.7/` (7.1.2 current); plugins `https://api.wordpress.org/plugins/info/1.2/?action=plugin_information&request[slug]=<slug>` for two-factor, updraftplus, litespeed-cache, wp-mail-smtp, fluent-smtp, post-smtp, contact-form-7, wpforms-lite, fluentform, redirection, limit-login-attempts-reloaded, wordfence, simple-cloudflare-turnstile, wp-2fa, two-factor-authentication, plugin-check; themes `https://api.wordpress.org/themes/info/1.2/?action=theme_information&request[slug]=spokares` |
| WP-PHP | https://make.wordpress.org/core/handbook/references/php-compatibility-and-wordpress-versions/ (7.0, 7.1 rows; release posts https://wordpress.org/news/2026/05/armstrong/ and https://wordpress.org/news/2026/08/mary-lou/) |
| PHP | https://www.php.net/supported-versions.php ; https://www.php.net/releases/active.php (8.3.35 and 8.4.26 released 24 Sep 2026) |
| WP-CFG | https://developer.wordpress.org/advanced-administration/wordpress/wp-config/ |
| WP-HARD | https://developer.wordpress.org/advanced-administration/security/hardening/ |
| WP-REST | https://developer.wordpress.org/rest-api/extending-the-rest-api/adding-custom-endpoints/ |
| WP-NONCE | https://developer.wordpress.org/apis/security/nonces/ |
| WP-XMLRPC | https://developer.wordpress.org/reference/hooks/xmlrpc_enabled/ |
| WP-PHPM | https://developer.wordpress.org/reference/hooks/phpmailer_init/ |
| WP-UPDURI | https://make.wordpress.org/core/2021/06/29/introducing-update-uri-plugin-header-in-wordpress-5-8/ |
| Core source | https://github.com/WordPress/WordPress/tree/7.1-branch: `wp-includes/update.php` (theme `UpdateURI` handling), `wp-includes/class-wp-theme.php` ("@since 6.1.0 Added `Update URI` header"), `wp-includes/load.php` (`wp_is_file_mod_allowed`), `wp-admin/includes/class-wp-automatic-updater.php` (`is_disabled`), `wp-includes/capabilities.php` (`DISALLOW_UNFILTERED_HTML`), `wp-admin/includes/schema.php` (Editor gets `unfiltered_html`; only Administrator gets `edit_theme_options`), `wp-admin/site-editor.php` |
| 2FA-GH | https://github.com/WordPress/two-factor (releases 0.15.0, 0.16.0, 0.17.0-rc; issues #255, #845, #846, open 2026-09-25) ; `class-two-factor-core.php` (`is_user_using_two_factor`) |
| NVD | NVD CVE API 2.0, keyword searches, 2026-09-26: https://services.nvd.nist.gov/rest/json/cves/2.0?keywordSearch=... (LiteSpeed Cache; Post SMTP; WP Mail SMTP; FluentSMTP; UpdraftPlus; Contact Form 7; WPForms; WP 2FA; Redirection). IDs cited: CVE-2024-28000, CVE-2024-44000, CVE-2024-50550, CVE-2026-3375, CVE-2026-84761, CVE-2025-11833, CVE-2023-6875, CVE-2024-9511, CVE-2026-16636, CVE-2026-10795, CVE-2024-10957, CVE-2026-15372 |
| UDP | UpdraftPlus readme (WordPress.org plugin API `sections.description`): free and paid storage destinations, database encryption paid |
| SPEC | Enhance orchd OpenAPI 12.25.12, local copy `research/hosting/_enhance-oas3-api.yaml`: `.../wordpress/users/{user_id}/sso`, `.../wordpress/wp-config`, `WpSettings` (`autoUpdateCore`, `disallowNonWpPhp`, `loginAccess`), `WPPluginAutoUpdateStatus`, `WPThemeAutoUpdateStatus` |
| ENH-WP | https://enhance.com/docs/wordpress/end-user/about.html ; https://enhance.com/product/wordpress-toolkit |
| ENH-RAA | https://enhance.com/docs/wordpress/end-user/restrict-admin-access.html ("only available if you are running Apache web servers") |
| ENH-OPT | https://enhance.com/docs/wordpress/admin/optimising-for-wordpress.html |
| ENH-MODSEC | https://enhance.com/docs/security/modsecurity.html ("can only be enabled on Apache and Nginx web servers") |
| FEAT | https://enhance.com/product/features (panel 2FA, per-domain ModSecurity) |
| CRS-WP | https://github.com/coreruleset/wordpress-rule-exclusions-plugin (last push 2026-09-22); CRS v4.29.0 (2026-08-17) |
| CF-TS | https://developers.cloudflare.com/turnstile/ |
| CF-SV | https://developers.cloudflare.com/turnstile/get-started/server-side-validation/ |
| CF-PLANS | https://developers.cloudflare.com/turnstile/plans/ |
| WPCS-GH | https://github.com/WordPress/WordPress-Coding-Standards/releases (3.4.1, 2026-07-27) |
| GF-OFL | https://github.com/google/fonts/tree/main/ofl/crimsonpro and https://github.com/google/fonts/tree/main/ofl/schibstedgrotesk (both ship `OFL.txt`) |
| CLI help | `npx @wp-playground/cli@3.1.55 --help` (commands `server`, `run-blueprint`, `php`) |
| Test | `wordpress/research/reference/test-blueprint.json`, run with `run-blueprint --mount=<reference>:/wordpress/wp-content/mu-plugins --define-bool DISALLOW_FILE_EDIT true --define-bool DISALLOW_UNFILTERED_HTML true --define SPOKARES_SMTP_* ...` (no server or port used); a second run with `DISALLOW_FILE_MODS true` gave `auto_updater_disabled=true`, `update_plugins=false` |
