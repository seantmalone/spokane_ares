# Verpex Enhance reseller hosting: raw research notes (Verpex's own sources)

- Researched: 2026-09-25 (Verpex status page clock showed 2026-09-26 02:47 UTC at fetch time)
- Scope: Verpex's own public pages, KB, legal terms, status page, and the public Upmind catalogue API that the Verpex pricing widgets call. No credential files were read, and no control-panel (orchd) API was called.
- Labels: **[C]** = CONFIRMED, seen in the cited source. **[I]** = INFERENCE, my reasoning and not stated by the source. **[X]** = CONTRADICTION between sources.
- Freshness key: dates are page `lastmod` values from `https://verpex.com/en-us-sitemap.xml` and `https://kb.verpex.com/sitemap.xml`, or record `created_at`/`updated_at` values from the Upmind catalogue. Anything older than about 12 months (before 2025-09) is flagged **STALE?**.

---

## 0. TL;DR for the SpokARES rebuild

1. **[C]** Verpex's Enhance reseller product has three advertised tiers: E30, E80 and E120. A fourth tier, E150, is orderable in the catalogue but does not appear on the page. All tiers run on the Enhance control panel. Source: https://verpex.com/reseller-hosting-enhance (sitemap lastmod 2026-08-05).
2. **[C]** Enhance server locations offered at checkout are **US - Central, London, Central Europe, Singapore, India**. **There is no US-West/Seattle option for Enhance.** Source: the Upmind catalogue "Server Location" attributes for all four E-plans (location records last updated 2024-09-03, **STALE?** as a record date, but they are the live checkout options). The cPanel products list 9 cities, including Dallas, but none in the US West either.
3. **[C]** Per Verpex's own KB, the Enhance hosting includes PHP version selection and a php.ini editor, SSH (key or password), cron jobs, FTP, MySQL with phpMyAdmin and remote-access IPs, **PostgreSQL** (FAQ), a **Node.js deploy tool**, an app installer for **WordPress and Joomla only**, a WordPress toolkit, DNS record editing, DKIM, local/remote mail routing, Roundcube webmail, automatic backups with selective restore, auto SSL plus custom SSL upload, 301/302 redirects, OPcache, IP allow/block, and Cloudflare and Slack integrations.
4. **[C]** Policy limits that affect the design come from the Verpex AUP and ToS, which cover all services and are not Enhance-specific:
   - multimedia up to 10 GB per account
   - mailboxes up to 20 GB
   - databases over 10 GB count as "excessive"
   - **account-holder software downloads up to 1 GB in total**
   - **"File/Software download sites or repositories are not allowed"**
   - **"All files uploaded to a domain ... must be visible and accessible by visiting that domain"**
   - no file-sync services (NextCloud)
   - **no audio streaming**
   - **no bots, including Telegram bots**
   - 100 emails/hour on shared hosting accounts (the KB page is cPanel-oriented, so this is unconfirmed for Enhance)
5. **[I]** A static site or a light PHP CMS fits the E30 tier easily, with room to spare. The main risks are these:
   - the Dallas-area "US-Central" latency from Spokane, which is fine
   - server IP changes during DDoS mitigation (see section 7), so DNS should stay on Verpex nameservers or use the Enhance Cloudflare sync
   - the email sending cap if the site sends notifications to members, so use an external transactional or list email service
   - keeping the downloads library small and public-linkable, or confirming with Verpex that a members-only document area is allowed

---

## 1. The product page: https://verpex.com/reseller-hosting-enhance

- **Fetch method:** the server-rendered HTML contains a Nuxt payload (`__NUXT_DATA__`). The plan cards are `<upm-widget as="PlanCard_v2">` elements whose prices load client-side from the Upmind API, so they are not in the HTML (`price: null`). I decoded the payload for the text and queried the same public Upmind endpoint the widget uses for the prices (section 2).
- **Dates:** sitemap lastmod **2026-08-05T16:20:23Z**. The old URL https://verpex.com/reseller-control-panel returns a **301** to this page. The cPanel reseller page links to it as "Looking for a more affordable alternative? Check out our new modern reseller plans → HERE" (https://verpex.com/reseller-hosting).
- Page title: "Enhance Reseller Hosting – Custom, Affordable Server and Site Management". Hero heading: "Affordable Reseller Hosting Powered by Enhance".

### 1.1 Hero bullets [C]
- "High-performance cloud hosting with autoscaling and maximum uptime". No uptime number appears anywhere on this page.
- "Host as many websites as you need"
- "Easy one-click website migration from any hosting provider at any time"

### 1.2 USPs above the plans [C]
- "Automatic backups", "Unlimited websites & email accounts", "One-click installs", "Money-back guarantee"
- Billing toggle: Monthly / 12 Months (default) / "36 Months - Save $1000". **[I]** The "Save $1000" label does not match any E-tier's actual 36-month saving; the largest, E120, saves $658.80. It looks like template copy.
- Outro: "Not sure which plan to choose? Consult with our team and we will find the best option for you."

### 1.3 Plan cards as rendered [C]

| Plan | Card description | Bullets |
|---|---|---|
| **Reseller E30** | "Supporting up to 30 Hosting accounts..." | 30 Hosting Accounts; **100GB NVMe SSD disk space**; Unlimited Domains; Free SSLs; 24/7 Tech Support; Free Migrations; *Free Domain Registration/Transfer |
| **Reseller E80** | "Supporting up to 80 Hosting accounts..." | 80 Hosting Accounts; **300GB NVMe**; same remaining bullets |
| **Reseller E120** | "Supporting up to 120 Hosting accounts..." | 120 Hosting Accounts; **750GB NVMe**; same remaining bullets |

- Free-domain tooltip: "Free domain not included on monthly billing. Applies to common TLDs (.com, .net, .org, .uk, .in), excludes premium extensions."
- **Not stated on the Enhance cards:** bandwidth, per-site RAM/CPU, inodes, entry processes, email-per-hour, backup retention, uptime percentage, or datacenter list. **[C]** (absence)
- **[C]** The CMS payload holds a hidden `comparativeFeatures` block on each E-plan. It is identical on all three E-plans and copies the cPanel "Reseller 15" table: 15 accounts, 50 GB, 2 GB RAM, 2 vCPU, $22.00/mo renewal, 100 emails/hour, "Cloudlinux (LVE Limits) - per cPanel". **The Enhance page has no `plans_table` ribbon, so this block is not rendered.** **[I]** Treat it as template leftovers and not as Enhance specs.

### 1.4 "Reseller Hosting services include:" [C]
- 30-Day Money-Back Guarantee
- 24/7 Expert Support: "live chat, phone, or the ticket system"
- No Additional Expenses: "free site migrations, SSL certificates, website backups, WordPress caching"

### 1.5 "How Enhance Reseller Hosting Works" [C]
- Custom branding covers logos, colours, fonts and the panel domain.
- Custom packages have exact quotas and features and can be "attach[ed] ... to server groups for automatic website placement".
- Customer management covers adding customers, plan tiers and impersonation.
- The page says you can "Add new accounts ... without needing to change IP's or servers on the fly autoprovisioning of resources" (sic).

### 1.6 "Key Enhance Control Panel Features" [C]

These are generic Enhance claims made on Verpex's page:
- Affordable licensing; custom branding; custom packages with unlimited reseller tiers
- Unified server and website management: "servers, websites, domains, DNS, email, databases, and WordPress"
- Built-in WordPress Toolkit: install, stage, update, SSO, plugin management
- Incremental backups included, with granular restore
- Multi-server clustering "up to 10,000 servers" **[I]** This is a panel-vendor feature. A reseller on Verpex does not control servers.
- "Website and Role Containerisation: Every website and server role runs in its own container"
- Billing integrations: WHMCS, Blesta, HostBill, Upmind
- cPanel and Plesk migrator

### 1.7 "Help Getting Started" [C]
- 24/7 help, including "Guidance on installing Enhance and adding servers to your cluster" (generic copy), "applying custom branding and setting up your panel domain", and "creating packages, reseller accounts, and customer management"

### 1.8 FAQ on the page [C]
- **Resellers:** "Multi-tiered reselling ... no limits on the number of reseller levels. Each reseller can have their own branding, packages, and custom panel domain."
- **Billing:** "WHMCS, Blesta, HostBill, and Upmind. There is also a RESTful API available for custom integrations". **[I]** The page does not say whether Verpex reseller customers get API tokens. Confirm with Verpex or the orchd docs.
- **WordPress Toolkit:** "one-click installs, staging, SSO, plugin management, and auto-update scheduling"
- **cPanel import:** "transfers websites, emails, and passwords either through the UI or via SCP as root. A Plesk importer is also available." **[I]** "SCP as root" is vendor copy. A reseller will not have root.
- **Backups:** "built-in incremental backup system with granular restore ... S3-compatible third-party backup systems can also be configured alongside it." **[I]** Whether a Verpex reseller can configure S3 destinations is unconfirmed.
- **Mobile:** fully responsive

---

## 2. Pricing: live catalogue data behind the page [C]

- **Source:** the public Upmind basket API that the page's `upm-widget` calls. Base URL `https://api.upmind.io/api/basket/products/{id}?with=prices,attributes,attributes.category&currency_code=USD`, which needs the header `Origin: https://verpex.com`. The widget script is https://embed.upmind.app/upm-widget.js. The product IDs come from the page payload.
- Product records were created **2026-03-12** and last updated **2026-08-27**. Price rows were updated 2026-04-08.
- Every product description ends with: "All discounts apply for the first billing term only."
- An auto-applied promotion **"ResellerPanelWelcome"** (created 2026-05-05, `for_new_clients: true`, used 76 times as of 2026-09-24) sets the first-term prices:
  - about $1 for the first month on monthly billing
  - 40% off the first 12-month or 36-month term
  - also 40% off the first 24-month term on E80 and E120, but not on E30

| Plan | Disk (NVMe) | "Hosting accounts" | Monthly list | 1st month | 12-mo list (=/mo) | 12-mo 1st term (=/mo) | 24-mo list | 36-mo list (=/mo) | 36-mo 1st term |
|---|---|---|---|---|---|---|---|---|---|
| E30 | 100 GB | 30 | **$21.99** | $1.00 | $219.00 ($18.25) | $131.40 ($10.95) | $438.00 (no promo) | $657.00 ($18.25) | $394.20 |
| E80 | 300 GB | 80 | **$39.95** | $1.00 | $399.00 ($33.25) | $239.40 ($19.95) | $798 → $478.80 | $1,197.00 ($33.25) | $718.20 |
| E120 | 750 GB | 120 | **$54.95** | $1.00 | $549.00 ($45.75) | $329.40 ($27.45) | $1,098 → $658.80 | $1,647.00 ($45.75) | $988.20 |
| E150 *(unlisted on page)* | 1,200 GB | 150 | $65.83 | — | $658.00 ($54.83) | — | $1,316.00 | $1,974.00 | — |

- **[C]** E150 is orderable in the catalogue: product `0381d780-e72d-4d83-172a-7413569926e5`, `clients_can_order=1`, `hide_catalog=false`, in the "Reseller Hosting" category (`?filter[products_category_id]=6e2e071d-931d-5e46-287c-546028758396`). It is not rendered on the page.
- **[X]** The KB FAQ (https://kb.verpex.com/enhance/enhance-reseller-hosting-frequently-asked-questions, lastmod 2026-06-28) says: "Is this introductory pricing only? **No.** These are the standard prices for our Enhance plans and are not limited to new signups or promotional periods." The live catalogue shows a new-client promotion and "discounts apply for the first billing term only". **[I]** Budget for the renewal (list) price. For E30 that is $219/yr or $21.99/mo.
- **[C]** The KB FAQ also says: "What is the catch? ... Because Enhance eliminates per-account cPanel licensing fees, we have significantly more flexibility on pricing."
- **[C]** Refund policy from the ToS (https://verpex.com/terms-conditions-of-service): "If you cancel before your service end date you will not be entitled to a refund of monies paid." The 30-day money-back guarantee can be used **once per customer** and covers the first term only.

### 2.1 cPanel reseller for comparison [C]

Sources: https://verpex.com/reseller-hosting and the Upmind catalogue. Products were updated 2026-04-15.

| Plan | Accounts | Disk | Monthly list (first) | 12-mo list (first) | Renewal/mo |
|---|---|---|---|---|---|
| Reseller 15 | 15 cPanel | 50 GB | $22.00 ($19.80) | $264 ($205.92 = $17.16/mo) | $22 |
| Reseller 30 | 30 | 100 GB | $49.00 ($24.50) | $588 ($246.96) | $49 |
| Reseller 60 | 60 | 180 GB | $69.00 ($31.05) | $828 ($331.20) | $69 |
| Reseller 100 | 100 | 300 GB | $109.00 ($39.24) | $1,308 ($863.28) | $109 |

- **[C]** The catalogue also lists unadvertised cPanel tiers: Reseller 25, 50, 75, 120, 150, 200, 250 and 300.
- **cPanel comparison table**, rendered on /reseller-hosting, applies to all tiers:
  - per cPanel account: 2 GB RAM, 2 vCPU, 30 entry processes, unlimited inodes, 1,024 IOPS, 50 MB/s IO, CageFS
  - bandwidth, email accounts and databases unlimited
  - daily backups
  - uptime guarantee "99.99%"
  - email: 50 MB attachments, **hourly sending limit 100**, outbound spam filtering
  - **[X]** The Upmind descriptions say "1 vCore & 2GB RAM per user" for R15/R30 and "2 vCores" for R60/R100.
- **Stack:** cPanel/WHM, CloudLinux, LiteSpeed, Softaculous (300+ scripts, including Joomla), Backuply, Monarx, SitePad, WP-CLI, MailChannels or SpamExperts for outbound mail, Anycast DNS

### 2.2 Enhance vs cPanel at Verpex [I, from the above]

| | Enhance | cPanel |
|---|---|---|
| Cost per account | about $0.73/acct/mo at E30 list | about $1.47/acct/mo at R15 list |
| Disk | 100 GB at E30 | 50 GB at R15 |
| Locations | 5 regions | 9 cities |
| Published per-account limits | none | yes |
| App installer | WordPress and Joomla only (KB) | 300+ Softaculous scripts |
| WordPress tooling | Enhance WP toolkit | Softaculous |
| Other | branded CP URL, Cloudflare sync | LSCache/LiteSpeed |

---

## 3. Datacenter locations

- **Enhance checkout options [C]:** "US - Central", "London", "Central Europe", "Singapore", "India". The attribute category is "Server Location". They are identical for E30, E80, E120 and E150, and the records were last updated 2024-09-03 (**STALE?** as a record date, but these are the current checkout choices). Source: Upmind catalogue as above.
- **cPanel products [C]:** "Dallas, TX", "Buffalo, NY", "London, UK", "Singapore, SG", "Frankfurt, DE", "Sydney, AU", "Toronto, CA", "Mexico, MX", "Mumbai, IN". Marketing copy reads "9 locations: Dallas, New York, Toronto, Mexico City, London, Frankfurt, Mumbai, Singapore and Sydney" (https://verpex.com/reseller-hosting, https://verpex.com/about).
- **[X] Older KB pages list other locations:**
  - https://kb.verpex.com/network/server-overview (undated, **STALE?**) lists "US-West, US-East, Canada, Australia, London, Singapore, India, Central Europe, South America".
  - https://kb.verpex.com/network/why-hosting-location-matters (undated, **STALE?**) claims "more than 40 locations".
  - Neither matches any current checkout option. **No current US-West location is orderable** for Enhance or for cPanel.
- **[I] What "US - Central" probably is:** status-page hostnames use `usc1` (for example `s1116.usc1.mysecurecloudhost.com`), and the cPanel list's only central-US city is Dallas. So "US - Central" is probably Dallas-area, but this is not confirmed for Enhance.
- **[I] Latency:** Spokane to Dallas is roughly 40–60 ms RTT, which is fine for this site. Ask Verpex for the exact city if it matters.
- **[C]** Verpex does not run its own datacenters. It uses "cloud providers like Linode, DigitalOcean, and LeaseWeb", and "used to" run shared/reseller hosting on AWS/GCP but stopped because of bandwidth costs. Source: https://verpex.com/our-technology (undated, cPanel-centric).
- **[C]** The ToS reserves the right to move you between locations and providers "on a like for like basis" and to change the hosting environment. Source: https://verpex.com/terms-conditions-of-service.

---

## 4. Enhance features documented in Verpex's KB

The Verpex KB is built on Mintlify. Appending `.md` to a page URL returns its markdown. The `/enhance/*` pages have sitemap lastmod **2026-06-28** (probably a docs redeploy date, not an authoring date). Two pages have lastmod **2026-09-09**: "managing customers from the panel" and "importing cPanel account".

### Overview and FAQ
https://kb.verpex.com/enhance/enhance-reseller-hosting-frequently-asked-questions **[C]**
- Verpex migrates cPanel accounts to Enhance for you. Self-import is also possible.
- Monarx security and a built-in WordPress Toolkit are included.
- **Custom-branded control panel URL.**
- Each customer gets its own control panel.
- **Roundcube webmail.** IMAP/SMTP settings are unchanged.
- "Your plan will include the same or more RAM and CPU as the closest equivalent cPanel plan." **[I]** That implies at least about 2 GB RAM and 1–2 vCPU per customer account, but Verpex does not publish a number for Enhance.
- **"Does Enhance support PostgreSQL? Yes. Enhance fully supports PostgreSQL"**
- Integrations: WHMCS, Blesta, HostBill, Upmind, and a RESTful API.
- Support is the same for Enhance and cPanel resellers. 30-day money-back guarantee.

### From cPanel to Enhance
https://kb.verpex.com/enhance/from-cpanel-to-enhance-everything-you-need-to-know **[C]**
- The page opens "You've just been moved onto Enhance". **[I]** Verpex has been migrating some cPanel resellers onto Enhance.
- **Terminology:** WHM becomes the "Primary Panel", cPanel accounts become "Customers", Packages stay Packages, and "log in as" becomes "Impersonate".
- **Packages** define "disk space, bandwidth, number of websites, email accounts, databases, and a range of feature toggles".
- **Adding a customer takes three steps:** customer, then package, then website.
- **Customer login:** "no port-based logins or redirects". You set a **Control Panel Domain** under Settings → Platform (for example cp.yourdomain.com) and point its DNS to the server.
- **Per-customer menu:** Websites, Emails, **Logs** ("server and website logs"), Packages, **Users** (additional users with access to the customer account), and **Integrations** ("Connect Cloudflare, Slack, and other services").
- **Self-service imports** bring over "files, databases, emails, DNS" automatically.
- The page also names "built-in Cloudflare syncing" and "real-time Slack notifications".

### Website tabs
https://kb.verpex.com/enhance/managing-your-website-databases-and-backups-in-enhance **[C]**
- **Apps:** "Only **WordPress and Joomla** are supported at this time. You can install other applications manually."
- **Files:** file manager. **Databases:** MySQL plus phpMyAdmin. **Emails. Domains:** subdomains, addon domains, aliases, document root, DNS. **Analytics:** bandwidth and visitor stats for today, last week and last month.
- **Backups:** restore Email, Website files, or a Custom mix that can include databases.
- **Security:** SSL, plus allow or block IP addresses.
- **Advanced:**
  - Redirects (301/302)
  - **Optimization:** "Enable Opcode caching"
  - FTP
  - **Developer tools:** "PHP settings and **cron jobs** ... SSH connection details"

### PHP
https://kb.verpex.com/enhance/changing-php-versions-and-settings-in-enhance **[C]**
- PHP version is chosen per website under Advanced → Developer tools.
- A php.ini editor lets you add directives as text or boolean values.
- There is a "Restart PHP container" step. **[I]** This is consistent with per-site containers.

### SSH
https://kb.verpex.com/enhance/configuring-ssh-access-in-enhance **[C]**
- SSH key manager (multiple keys) and SSH password authentication are both available per website. The panel shows a login command.

### Node.js
https://kb.verpex.com/enhance/how-to-deploy-a-nodejs-application-on-enhance **[C]**
- Upload the app to `~/my-app`, SSH in (the example uses `ssh -p 22 username@<ip>` with host `s4515`), use the panel's **"Deploy Node.js" tool**, then run `npm install`. The sample app listens on `process.env.PORT` and binds `0.0.0.0`, noted as "(required for proxying)".
- **[I]** Node apps run behind a reverse proxy with a panel-assigned port.

### MySQL
https://kb.verpex.com/enhance/managing-your-mysql-databases-in-enhance **[C]**
- DB users with per-database privileges.
- **"Remote access IP"** per user.
- phpMyAdmin; SQL upload/import and download/export.

### Domains
https://kb.verpex.com/enhance/managing-your-domains-in-enhance **[C]**
- Document root, **DKIM toggle**, mail routing (Local or Remote), and DNS record add/edit/delete.
- Subdomains and aliases (parked domains), optionally redirecting to the primary domain.

### Email
https://kb.verpex.com/enhance/managing-your-email-in-enhance **[C]**
- Per mailbox: display name, **mailbox size**, password, forwarders, spam settings, autoresponders.
- The panel shows IMAP/POP/SMTP settings.

### Backups
https://kb.verpex.com/enhance/managing-backups-in-enhance **[C]**
- A "list of automatic backups generated for your account", with Restore options Email, Website, or Custom.
- **Retention and frequency for Enhance are not stated.** For comparison:
  - The cPanel KB says JetBackup, twice daily, "Backups are kept for 30 days" (https://kb.verpex.com/cpanel/hosting-with-verpex-what-you-should-expect, undated).
  - The ToS says "We take twice-daily backups ... store them offsite ... Backups are a best effort service ... It is your responsibility to keep backups of your own website."

### SSL
https://kb.verpex.com/enhance/installing-an-ssl-certificate-in-enhance **[C]**
- The free SSL "is installed automatically". Custom CRT and key upload is under Security → "Install custom SSL".
- The Verpex tech page names AutoSSL/Let's Encrypt or Comodo, in a cPanel context.

### FTP and file manager
- https://kb.verpex.com/enhance/managing-ftp-accounts-in-enhance **[C]**: FTP accounts can be limited to a directory.
- https://kb.verpex.com/enhance/using-the-file-manager-in-enhance **[C]**: edit, compress/uncompress, and chmod.

### WordPress
https://kb.verpex.com/enhance/managing-your-wordpress-site-in-enhance **[C]**
- Plugins and themes, **admin access restricted by IP**, users, SSO, WP_DEBUG toggles, maintenance mode, and version management.

### Reseller management
- https://kb.verpex.com/enhance/adding-a-new-customer-to-your-enhance-resellers-account **[C]**: Settings → Platform for the CP URL, then Settings → Packages, then Customers → Add Customer, then Impersonate → Add Website (empty site or WordPress).
- https://kb.verpex.com/enhance/enhance-reseller-managing-customers-from-the-panel (lastmod 2026-09-09) **[C]**:
  - clients.verpex.com has a **"My Customers"** section with list, Manage (SSO), Impersonate, Suspend, Delete and Create customer.
  - The page contains anchors pointing to `kb.hosting.com/docs/enhance-reseller-managing-customers-from-hostingcom-panel`. **[I]** The doc was adapted from hosting.com's KB, which suggests a shared platform or group tooling.
- https://kb.verpex.com/importing-and-restoring-a-cPanel-account-into-enhance (lastmod 2026-09-09, screenshots dated 2026-09-09) **[C]**:
  - Create the customer first, then Websites → Import Website → cPanel → upload the backup file, then Import, then **Transfer ownership** to the customer.
  - Warning: "Do not create the domain or website ... before starting this process".

### Not mentioned for Enhance in Verpex's KB [C] (absence)
- **Git deployment.** The cPanel KB has "How to Host Git Repositories with cPanel".
- **Python apps.** The cPanel KB has "Python Applications".
- Redis/Memcached, the number of PHP versions, inode limits, per-site CPU/RAM, and **the email sending limit on Enhance**.

---

## 5. Limits and policies (legal and KB)

### 5.1 Acceptable Usage Policy
https://verpex.com/acceptable-usage-policy (undated on the page) **[C]**

**Prohibited activities (1.5):**
- hacking, phishing, spam, botnets, port scanning, malware
- privacy violations
- **"Operating VPN or proxy services"**

**Resource usage (2.2–2.3):**
- No "excessive" consumption. Verpex may limit, suspend or terminate "with or without notice".
- Verpex "reserve[s] the right to impose limits on the number of email accounts, inbox size, database size, account disk usage, bandwidth ..."

**Fair use (3.1):**
- (a) **Multimedia ... should not exceed 10GB in total per hosting account**
- (b) **Email ... should not exceed 20GB per mailbox**
- (c) **a database using more than 10GB** is excessive
- (d) local backups kept to a minimum
- (e) **"Software downloads created by the account holder should not exceed 1GB total. We may require the use of a CDN service for download distribution."**
- (f) Resellers with "unlimited" disk using more than 400 GB must keep each sub-account at or under 50 GB
- (g) no offsite backup use
- (h) no file storage or sync (NextCloud, OwnCloud and similar)
- (i) **"File/Software download sites or repositories are not allowed"**

**Mailbox usage (3.4):**
- SMTP is "designed for day-to-day communication".
- "All outbound mail is scanned by a cloud-based spam filtering system."
- "Mailboxes are intended for direct use by you as the hosting plan owner and should not be resold".
- Verpex may queue, reject or block mail "for any reason".

**File visibility (3.5):**
- **"All files uploaded to a domain on our servers must be visible and accessible by visiting that domain, except for hidden files needed to operate the website. We reserve the right to delete files not meeting these criteria without notice."**
- **[I]** This matters for a members-only downloads area and for private file storage. Keep documents web-served, even if behind a login, and ask Verpex to confirm that an authenticated area counts as "accessible by visiting that domain".

**Email (4.x):** zero tolerance for spam; a clear opt-out is required in non-transactional mail; CAN-SPAM and GDPR apply.

**Resellers (5.1):** "we reserve the right to terminate the suspended account after 60 days. **We do not keep any backups.**"

**Grace period (6.2):** "Where possible ... 24 to 72 hours to resolve fair use violations before suspending".

### 5.2 Terms & Conditions of Service
https://verpex.com/terms-conditions-of-service (undated) **[C]**
- **Entity:** Verpex Limited, England and Wales, company no. 11878098.
- **Purpose of Services:**
  - "Batch processing, video encoding/transcoding, web crawling/spidering, archiving and online backup systems and any system for purposes other than hosting a website are not permitted on our shared or reseller hosting servers."
- **"Unlimited" allowances** are subject to fair use. You may be told to add a CDN or upgrade.
- **Migration:** "best-effort ... We aim to migrate websites within 72 hours". Email, DNS and domain migration is not warranted.
- **Environment changes:** Verpex may change the hosting environment or provider "like for like".
- **DDoS:** "We make no guarantee to defend your website from a denial of service attack ... purchase a DDOS mitigation service from a third party such as Cloudflare."
- **Uptime:**
  - "We endeavour to provide a **99.9%** service uptime, excluding planned or emergency ... maintenance". An engineer engages within 30 minutes.
  - No service credits are described.
  - Upstream outages such as AWS are excluded from the 99.9% calculation.
  - **[X]** The cPanel comparison table says "99.99%" (https://verpex.com/reseller-hosting). The Enhance page gives no number.
- **Backups:** twice daily, offsite, "usually in the same geographic region (but not necessarily the same country)", best effort, and the customer is responsible for their own backups.
- **Suspension:** "If your service has been suspended or goes overdue, we may no longer retain a copy of your data or website. **Your website IP address may also change.**"
- **Termination:** cancel before the renewal date in the client area. **Prepaid months are not refunded.**
- **Money-back:** 30 days, one use per customer, first term only. Domains and servers are excluded.
- **Price changes:** notified by email. "In most cases, changing the price on the website for new customers will not affect the price for existing customers."
- **Liability:** capped at twice the fees paid in the prior 12 months.

### 5.3 Content restrictions
https://kb.verpex.com/docs/content-restrictions-and-dmca-compliance (now at /docs/content-restrictions-and-dmca-compliance per the KB index) **[C]**

Forbidden:
- "Any bots, including IRC or other social bots e.g. **Telegram bots**"
- warez and phishing
- "Bulk mailing"
- "**Audio streaming**" and video "tube" sites
- IP scanners and brute-force tools
- unapproved financial sites, pharma and lottery sites
- "**File Storage**", "**File upload websites**", "Mobile app downloads", and NextCloud/OpenCloud

Other points:
- "We support HTTP streaming on shared servers; however, live streaming is not supported."
- "Most of our infrastructure is in the US ... DMCA".
- https://kb.verpex.com/network/can-i-livestream-audio-or-video **[C]**: no audio or video streaming and no proxy servers on shared servers.
- **[I]** Do not host a live net audio stream (for example a repeater or net audio feed) on this plan. Embed or link a third-party service such as Broadcastify instead. A Telegram or Discord bot for net-control notifications cannot run here either.

### 5.4 Email limits
https://kb.verpex.com/emails/email-limits (undated, cPanel-oriented) **[C]**
- "shared hosting accounts have a maximum limit of **100 emails per hour**"
- "**Unlimited email sending is not allowed on any service.**"
- Scripts that exceed the limit are disabled until support is contacted.
- For bulk mail, "we recommend using third-party services such as Mailchimp, MadMimi".
- Higher limits can be requested but are "not guaranteed".
- **[X]** A web-search snippet attributed to the old URL `kb.verpex.com/docs/email-limits` said the limit "is removed for managed cloud servers and cloud web hosting". That URL now returns 404, and the current page says unlimited sending is not allowed on any service. Treat the snippet as stale.
- **[I]** Whether the 100/hour figure applies to Enhance accounts is unconfirmed, since the page talks about cPanel. The cPanel reseller comparison table also shows "Hourly Sending Limit 100".
- **Attachment size:** 50 MB per message, including headers. Source: https://kb.verpex.com/emails/what-is-the-limit-on-the-email-attachment-size.
- **Outbound mail:** "sent via MailChannels or SpamExperts" (https://verpex.com/reseller-hosting, cPanel context).

### 5.5 Other KB limits
These are all cPanel/CloudLinux-oriented and undated. **[C]**
- https://kb.verpex.com/network/cloudlinux-limits: up to 4 GB RAM and up to 2 vCPU depending on package; IO 50–250 MB/s; IOPS 1,024–5,200.
- https://kb.verpex.com/network/disk-space-limits: "It cannot be employed for backup storage or file archiving", plus fair-use limits on mail storage.
- https://kb.verpex.com/reseller/reseller-hosting-server-specification: 2 GB RAM and 2 vCPUs per cPanel on standard resellers and 4 GB on Premium; NVMe, 128 GB+ RAM, AMD EPYC; all servers whitelabel; free custom nameservers; Anycast DNS.
- https://kb.verpex.com/reseller/can-i-oversell-on-my-reseller-plan: overselling is allowed, "up to 500GB disk space and bandwidth".
- **[I]** None of these are published for the Enhance product.

---

## 6. White-label, support, company

### White-label [C]
- Enhance: logos, colours, fonts and panel domain (page), and a Control Panel Domain setting (KB).
- Verpex reseller servers use hostnames and nameservers that do not mention Verpex (https://kb.verpex.com/reseller/reseller-hosting-server-specification, cPanel context).
- Nameservers given in the migration KB: `ns1`–`ns4.mysecurecloudhost.com` (https://kb.verpex.com/moving/website-migration-process, cPanel context).

### Support [C]
- 24/7 live chat, phone and tickets.
- Phone: UK 020-3095-4271, USA +1 518 250 6389 (https://kb.verpex.com/billing/support-team).
- Status page: https://status.verpex.com.

### Company [C]
- Verpex was "Founded in 2018 ... 50+ employees ... five countries". It says it hosts "300,000+ websites across 9 global server locations". The **2024** timeline entry reads: "Launched AI Sitebuilder and affordable reseller hosting powered by Enhance Control Panel." (https://verpex.com/about)
- "We are Verpex hosting Ltd, part of the **World Host Group**" (https://verpex.com/company-details).
- The ToS names "Verpex Limited", company 11878098.

### Charity hosting [C]
Source: https://kb.verpex.com/cpanel/charity-hosting (undated, cPanel section).
- "we will provide any **registered charity** with completely free web hosting". Proof of registration is required.
- "For non-profit organisations ... contact us and we can discuss your options".
- **[I]** If SpokARES, or a sponsoring 501(c)(3) or county entity, qualifies, a free plan might be possible. It would likely be cPanel and not Enhance, so check with Verpex.

---

## 7. Status page and operations
https://status.verpex.com and https://status.verpex.com/incidents, fetched 2026-09-26 UTC **[C]**

- **Components:** "Hosting Platforms" includes a separate **"Enhance Hosting"** component, alongside cPanel Hosting, VPS hosting, Plesk hosting and Windows. It was Operational at fetch time.
- **Incident history:** 142 resolved incidents parsed across 9 pages.
  - By month: Mar 2026: 4, Apr: 9, May: 14, Jun: 19, Jul: 20, Aug: 43, Sep (to 24th): 33.
  - Almost all are titled "Hosting Performance Degradation", often naming a server such as `s1116.usc1.mysecurecloudhost.com`.
  - Hostname region tags seen: use1 32, usc1 19, sgp1 18, bom1 9, lon1 8, fra1 8, mex1 6, can1 6, use2 2, syd1 2. No US-West tag appears.
- **Typical mitigation, verbatim (incident/3123, Sep 17 2026; 3096, Sep 6; 3077, Aug 30):**
  - "our Infrastructure Team has **changed the IP address** to restore functionality ... For clients using our name servers, no action is required. However, for clients using **external name servers, please manually update the DNS records**".
  - The Aug 30 incident describes the same process as an "IP Split".
- **[I]** I could not determine from public data which incidents hit Enhance servers and which hit cPanel servers.
- **[I] DNS design implication:** if DNS stays at an external provider (for example the current registrar or Cloudflare DNS), expect occasional forced A-record changes after traffic floods. There are two ways to handle this:
  - use Verpex/Enhance nameservers, or
  - use the Enhance Cloudflare integration ("built-in Cloudflare syncing" per the KB) so the records stay in sync.
  - Also combine this with the ToS DDoS stance, which says to buy third-party mitigation such as Cloudflare.

---

## 8. Other Verpex Enhance offerings: shared or non-reseller [C]
- Only one Enhance URL exists in the sitemap: https://verpex.com/reseller-hosting-enhance. Guessed URLs such as /enhance-hosting and /web-hosting-enhance return 200 but render the 404 template.
- The Upmind public catalogue "Web Hosting" category contains only Bronze, Silver, Gold, Basic, Plus and Premium, all cPanel. "Managed Servers" contains Managed Linux Server D4–D32. "Java Hosting" contains Bronze–Gold.
- **There is no Enhance shared (non-reseller) plan at Verpex.** The VPS and managed server pages (/managed-cloud-servers, /vps-hosting, /managed-linux-vps-hosting, /managed-vps-hosting) do not mention Enhance.
- **Shared cPanel plans for context** (https://verpex.com/web-hosting, https://verpex.com/shared-web-hosting; products updated 2026-09-08):

| Plan | Websites | Disk | Monthly list | 12-mo list |
|---|---|---|---|---|
| Bronze | 1 | 30 GB | $6.00 | $72 |
| Silver | 100 | 50 GB | $9.99 | $120 |
| Gold | unlimited | 100 GB | $14.90 | $180 |

  - All run on LiteSpeed with cPanel and daily backups ("$21.06 value"), with 9 locations.
  - FAQ: "Bronze (1 vCPU, 1 GB RAM) ... Silver and Gold (2 vCPU, 2 GB RAM)" (https://verpex.com/web-hosting).

---

## 9. Implications for the SpokARES rebuild [I]
1. **Architecture fit.** A static site (for example SSG output), or a PHP app with MySQL/PostgreSQL on the Enhance site container, is within what the KB documents. Node.js works through the Deploy Node.js tool but runs as a long-lived process in a shared environment. Prefer a static build plus small PHP or serverless-free endpoints unless Node is needed.
2. **Joomla path.** The Enhance app installer supports Joomla (and WordPress), so a Joomla 4/5 rebuild is feasible. A cPanel backup from the current host could be imported through "Import Website", but the current site is Joomla 3 on js_wright, so an in-place upgrade or rebuild is still required.
3. **Calendar (JCal Pro replacement).** No server-side cron limits are published, but cron exists under Developer tools. A PHP or static-generated calendar is fine.
4. **Downloads library.**
   - Keep PDFs well under the AUP's 1 GB "software downloads" and 10 GB multimedia caps. Serve them from the site so each file is "visible and accessible by visiting that domain".
   - Do not build a general "file upload site" for members.
   - Ask Verpex whether files behind the member login are acceptable under AUP 3.5.
5. **Member email and notifications.** Plan for 100 msgs/hour or fewer from the host (to be confirmed for Enhance). Send net reminders or bulk member mail through an external ESP (for example Mailchimp or an SMTP relay), and set up DKIM through the Enhance toggle plus SPF/DMARC.
6. **Weather module and slider.** Use client-side embeds or a periodic cron fetch. Running an always-on bot or proxy is not allowed.
7. **Latency and region.** Choose "US - Central" at checkout, since there is no US-West option.
8. **Backups.** Keep our own off-host backups (via git repo, SFTP or DB dumps over SSH). The ToS makes host backups best-effort, and the AUP says suspended reseller sub-accounts are deleted after 60 days with no backups kept.
9. **Budget.** E30 renews at $219/yr, or $21.99/mo on monthly billing. The first term can be cheaper under the auto-applied new-client promotion.

## 10. Open questions for Verpex support (not answered by public sources)
- Exact city and provider for Enhance "US - Central" (Dallas?).
- Enhance per-site/customer RAM, CPU, process and inode limits, and whether packages expose them.
- Email hourly sending limit on Enhance. Whether SMTP relay to an external ESP is allowed. Outbound filter (MailChannels?).
- Enhance backup frequency and retention (30 days as on cPanel?), and whether resellers can add S3 backup targets.
- Available PHP versions on Enhance. Git deploy support. Python. Redis.
- Whether reseller-level access to the Enhance (orchd) REST API is provided, and at what scope.
- Whether a members-only (authenticated) document area complies with AUP 3.5 file visibility.
- Whether the SpokARES entity qualifies for charity or non-profit hosting, and whether that can be Enhance-based.
- Whether E150 is a supported, sellable tier (it is in the catalogue but not on the page).

## 11. Source list with dates
| Source | Date signal |
|---|---|
| https://verpex.com/reseller-hosting-enhance | sitemap lastmod 2026-08-05; fetched 2026-09-25 |
| https://verpex.com/reseller-control-panel | 301 → above |
| https://embed.upmind.app/upm-widget.js (widget) and https://api.upmind.io/api/basket/products/{id} | E-products created 2026-03-12, updated 2026-08-27; promo created 2026-05-05 |
| https://verpex.com/reseller-hosting | cPanel products updated 2026-04-15 |
| https://verpex.com/web-hosting, /shared-web-hosting, /wordpress-hosting, /cloud-web-hosting | products updated 2026-09-08 |
| https://verpex.com/acceptable-usage-policy | undated |
| https://verpex.com/terms-conditions-of-service | undated |
| https://verpex.com/our-technology | undated (cPanel-centric) |
| https://verpex.com/about, /company-details | undated (timeline runs to 2025) |
| https://kb.verpex.com/enhance/* (16 pages) | lastmod 2026-06-28; two pages 2026-09-09 |
| https://kb.verpex.com/emails/email-limits, /emails/what-is-the-limit-on-the-email-attachment-size | undated |
| https://kb.verpex.com/docs/content-restrictions-and-dmca-compliance, /network/can-i-livestream-audio-or-video | undated |
| https://kb.verpex.com/network/server-overview, /network/why-hosting-location-matters | undated, **STALE?** (location lists do not match current checkout) |
| https://kb.verpex.com/cpanel/charity-hosting | undated |
| https://kb.verpex.com/cpanel/hosting-with-verpex-what-you-should-expect | undated (cPanel backup retention 30 days) |
| https://status.verpex.com, /incidents | live, 2026-09-26 UTC |
| https://www.webhostingtalk.com/showthread.php?t=1895067 | Apr 2023, **STALE**; no Enhance content |
| Third-party reviews (cybernews, hostadvice) | returned 403 to fetch; not used |
