# Verpex Enhance reseller: independent / third-party findings (raw notes)

Lens: third-party reviews, comparison sites, forums (Reddit, WebHostingTalk, LowEndTalk), Trustpilot, and user posts on the Enhance community forum. The question is how Verpex Enhance reseller hosting holds up in practice for the SpokARES rebuild: performance, uptime, support, network, the limits people hit, and whether Node.js or static-site workflows work.

Compiled 2026-09-25. Public sources only. No local credentials were read and no control-panel API was called.

Labels:
- **CONFIRMED**: the claim appears in the cited source.
- **INFERENCE**: my reasoning from the sources.
- **OLD**: the source is more than about 12 months old (before about 2025-09).

---

## 0. Most important caveat: almost no independent source tests Verpex *Enhance* specifically

- Every independent review I found tests Verpex **cPanel** products: shared Bronze/Silver/Gold, the cPanel/WHM reseller, or VPS. **None of them provisioned or benchmarked the Verpex Enhance reseller.**
  - HostAdvice reseller review (May 2026) walks through WHM/cPanel and Softaculous screens and lists cPanel reseller plans only. https://hostadvice.com/hosting-company/verpex-hosting-reviews/verpex-hosting-reseller-hosting-review/ (read through the r.jina.ai reader because direct fetch returned 403). Screenshot URLs are dated cdn.hostadvice.com/2026/05/.
  - Hostnamy reseller review (2025-12-31) covers WHM/cPanel only: "each site gets its own isolated cPanel account", 2 GB LVE RAM and 2 vCPU per account. https://hostnamy.com/verpex-reseller-hosting/
  - LinuxTeck (updated 2026-08-18) tested cPanel shared plans in Columbus, USA. https://www.linuxteck.com/guides/verpex-reviews/
  - intercoolstudio (2026-06-05) is an affiliate review with no tests, and its reseller section covers WHM/cPanel. https://intercoolstudio.com/verpex-review/
- A Trustpilot search for "enhance" on www.verpex.com found no review about the Enhance panel. The only hit used "enhance" as an ordinary verb (2024-01-12, 2 stars). https://www.trustpilot.com/review/www.verpex.com?search=enhance
- The Enhance community forum mentions Verpex only in passing (2024-10-07: "Big companies like Stablepoint and Verpex use [Upmind]"). https://community.enhance.com/d/2017-new-roadmap
- **Reddit could not be read.** reddit.com and old.reddit.com returned HTTP 403 "blocked by network security", both directly and through the reader proxy. WebSearch `site:reddit.com Verpex` returned no Reddit threads. **There is no Reddit evidence either way.**
- **INFERENCE:** Treat real-world data specific to Verpex Enhance as unknown. The sections below cover (a) Verpex as a company and its infrastructure, which is shared across its products, and (b) the Enhance platform as used by other providers. Verpex's own choices, such as package limits, web server, Enhance version, and whether Node.js is enabled, must come from Verpex docs, sales, or a trial account.

---

## 1. Ownership, independence and infrastructure

- **CONFIRMED:** WHTop lists Verpex as "Part of World Host Group", the same parent as Stablepoint. https://www.whtop.com/compare/stablepoint,verpex (retrieved 2026-09-25)
- **CONFIRMED:** World Host Group (WHG) is owned by Oakley Capital. It acquired A2 Hosting in January 2025 and rebranded it hosting.com. The group runs more than 25 brands, including Stablepoint and Mochahost. https://hostingjournalist.com/news/world-host-group-brand-a2-hosting-rebrands-to-hosting-com (2025)
- **CONFIRMED:** GoogieHost reports that Seb de Lemos leads Verpex and is "involved with Enhance.com, a control panel that's gaining traction as an alternative to cPanel". https://googiehost.com/blog/verpex-reviews/ (2026 page, undated)
  - A search-result snippet (theorg/LinkedIn) says he "became an investor in enhance.com". **Not verified directly. Treat it as INFERENCE.** https://theorg.com/org/stablepoint/org-chart/seb-de-lemos
  - **INFERENCE:** Verpex and Enhance may not be at arm's length. Weigh Verpex marketing about Enhance accordingly.
- **CONFIRMED (independent network data):** Server hostnames on Verpex's status page resolve to IPs announced by **WHG Hosting Services Ltd** ASNs (RIPEstat and bgp.he.net, looked up 2026-09-25):

  | Hostname | IP | ASN |
  |---|---|---|
  | s3491.use1.stableserver.net | 195.250.26.2 | AS14670 WHG-USE1 |
  | s1043.use1.mysecurecloudhost.com | 185.181.252.170 | AS14670 WHG-USE1 |
  | s1120.usc1.mysecurecloudhost.com | 194.39.149.92 | AS36454 **WHG-DAL** (so "usc1" is Dallas) |
  | s17445.use2.stableserver.net | 65.181.120.52 | AS211912 WHG-USE2 |
  | s2920.can1.stableserver.net | 192.250.237.55 | AS36218 WHG-CAN |
  | s3421.mex1.stableserver.net | 209.42.29.82 | AS211126 WHG-MEX |

  - UK network: AS51713 WHG-LON.
  - Sources: https://bgp.he.net/AS51713 , https://bgp.he.net/AS14670 , https://stat.ripe.net/data/prefix-overview/data.json?resource=195.250.26.2
- **CONFIRMED:** WHTop gives Verpex's ASNs as AS51713 (UK), AS14670 (US), AS36218 (Canada), AS199404 (India) and AS216180 (Australia), with upstreams Arelion and GTT and peering at LINX. It also says Verpex is "distributed across Linode, DigitalOcean, Leaseweb, AWS, Google Cloud", which is probably stale marketing. https://www.whtop.com/review/verpex.com
- **INFERENCE:** Verpex runs on shared WHG/Stablepoint infrastructure ("stableserver.net" hostnames), not its own. From a Spokane point of view:
  - North American regions seen: use1 (US East), use2, usc1 (Dallas), can1, mex1.
  - **No US-West region appeared in any status incident.** Hostnamy's list includes San Francisco, but that list may be stale.
  - Dallas is probably the closest. For a small, mostly static site the latency is fine.
  - Confirm which regions the Enhance product offers; that is not in independent sources.
- **CONFIRMED (LET 2024-09, OLD):** A user reports that WHG brands share support staff ("same customer support staff / at-least for live chat / L1, L2") and use Upmind billing. Another user says "I avoid any hosting group that has this many brands". https://lowendtalk.com/discussion/198122/anyone-have-experience-with-world-host-group

## 2. Uptime and incident history

- **CONFIRMED:** Verpex's status page (status.verpex.com) has a separate component called **"Enhance Hosting"**. It was "Operational" when retrieved on 2026-09-25/26. https://status.verpex.com/
  - This is a vendor page, but its incident log is factual.
- **CONFIRMED:** From 2026-08-02 to 2026-09-23 the page published about **77 incidents** (44 in August, 33 from Sept 1 to 23). **69 of them say the infrastructure team changed a server's IP address**.
  - Most are titled "Hosting Performance Degradation" on a named server, for example s4936.fra1 or s3491.use1.
  - s3491.use1 alone appears on 09-05, 09-20, 09-21 and 09-22.
  - I indexed incident IDs 2980 to 3144 by script from https://status.verpex.com/incident/{id}.
- **CONFIRMED:** Standard wording, from incident 3143 on 2026-09-23 (https://status.verpex.com/incident/3143):
  - Trigger: "higher than normal internet traffic to server s4936.fra1.stableserver.net"
  - Fix: "our Infrastructure Team has changed the IP address to restore functionality … For clients using our name servers, no action is required. However, for clients using external name servers, please manually update the DNS records for your affected domains."
- **CONFIRMED:** No incident in that window mentions "Enhance" by name. The named servers look like cPanel "s####" hosts. **It is unknown whether Enhance servers get the same treatment.** INFERENCE: probably yes, because it is a network-level mitigation.
- **CONFIRMED (Trustpilot, 2026):** Several resellers complain about unannounced IP changes:
  - 2026-07-15 (CO): "They constantly change IP addresses without prior notice", with 15+ clients lost.
  - 2026-06-11 (IN): an IP change during an outage took 30+ client sites offline. The reviewer cites Cloudflare logs showing "88%" uptime, which Verpex disputes.
  - 2026-07-12 (PH): an unannounced IP change.
  - Verpex's replies describe IP changes as DDoS/security mitigation.
  - Source: https://www.trustpilot.com/review/www.verpex.com?stars=1
- **CONFIRMED (Trustpilot 2-star, 2026-07-17, CA):** A 6-day website and email outage after infrastructure changes, with "20 hours" between support responses and L1 support pointing at Cloudflare instead of the origin IP. https://www.trustpilot.com/review/www.verpex.com?stars=2
- **CONFIRMED (third-party monitors):**
  - GoogieHost: 99.95% average over 41 months (2022 to 2026), 99.99% in both 2024 and 2025. https://googiehost.com/blog/verpex-reviews/
  - LinuxTeck: "100% uptime during the monitored period, with 0 incidents" (cPanel shared, Columbus). https://www.linuxteck.com/guides/verpex-reviews/
  - HostScore: 99.95% measured. Page updated 2026-09-17. The title says 79.7% but the body says 82.2%, so the page is inconsistent. https://hostscore.net/review/verpex/
  - prehost: 100% during its test. https://prehost.com/hosting/verpex/ (Sept 2026)
  - HostingCanada: 99.99% uptime and 196 ms TTFB. https://hostingcanada.org/verpex-review/ (2026-02-23)
- **INFERENCE:** Single-site monitors show good uptime. The fleet-wide status log and the reseller complaints show a **recurring pattern of DDoS-driven IP changes**.
  - **Practical rule for SpokARES:** if spokares.org DNS points at the Verpex/Enhance server with a hard-coded A record in external DNS (Cloudflare, a registrar), the site can go dark until someone updates the record.
  - Mitigations:
    - Use Verpex/Enhance nameservers, which update automatically.
    - Or subscribe to status emails and have a documented runbook for updating the A record.

## 3. Performance (independent tests, mostly cPanel shared)

- LinuxTeck (2026-08-18, cPanel shared, Columbus):
  - PageSpeed 90 mobile / 99 desktop; GTmetrix A with 632 ms LCP.
  - k6 test with 50 VUs: "33,430 total requests with 0 HTTP failures" in 27 minutes, 24.11 req/s, 135 ms P95.
  - Entry processes capped at 30 (Bronze/Silver) and 35 (Gold); "can surface as intermittent errors under load".
  - https://www.linuxteck.com/guides/verpex-reviews/
- GoogieHost:
  - k6 with 50 VUs for 30 minutes: 34,211 requests, 16 failed.
  - TTFB 3 ms from Mumbai, 203 ms from US East, 346 ms from São Paulo.
  - https://googiehost.com/blog/verpex-reviews/
- prehost (Sept 2026):
  - TTFB averaged 1.5 s across 13 sites; 23.2% "Poor" (>1800 ms).
  - Con: "Higher-tier plans needed for resource-intensive websites".
  - https://prehost.com/hosting/verpex/
- VPSBenchmarks (VPS only, not Enhance): tests Nov 2024 to Apr 2026, scores 68 to 73; provisioning ranged "90s" to "72000s". https://www.vpsbenchmarks.com/hosters/verpex
- HostAdvice (May 2026, cPanel reseller): ran CPU, memory, disk and GTmetrix benchmarks and concluded "the infrastructure is solid". https://hostadvice.com/hosting-company/verpex-hosting-reviews/verpex-hosting-reseller-hosting-review/
- LET, about the Enhance platform in general:
  - 2026-05: one ex-user says "tried it, had somehow worst performance and speed than with cpanel and directadmin with the same hw stack and lsws, email limits impossible to force while keeping everything counted, s3 backups were totally broken … finally give up and move everything to DA". https://lowendtalk.com/discussion/217622/enhance-control-panel-experience
  - 2025-07: another user "never were able to achieve same speed on website benchmark … on Enhance than with cpanel-directadmin, it was slower, maybe because it was docker". https://lowendtalk.com/discussion/208079/anyone-using-enhance-control-panel-in-production
  - Others in the same threads report "300 sites … around 6 servers", "2.5 years now. No issues", "We. Love. It."
- **INFERENCE:** Performance is fine for a small club site. Enhance-specific performance at Verpex is untested by third parties.

## 4. Support quality

- **CONFIRMED:** Trustpilot for www.verpex.com shows 4.7/5 from 1,675 reviews (628 in the last 12 months). The distribution is **90% 5-star and 6% 1-star**. Recent 5-star reviews are short "fast support" notes. https://www.trustpilot.com/review/www.verpex.com (retrieved 2026-09-25)
  - INFERENCE: That bimodal pattern usually means reviews are solicited through support-chat invitations. Read the 1- to 3-star reviews for substance.
- **CONFIRMED:** Recurring 1- to 3-star themes in 2025-2026 (https://www.trustpilot.com/review/www.verpex.com?stars=1 , ...&page=2 , ?stars=2 , ?stars=3):
  - L1 support "kept blaming DNS propagation instead of investigating" (2026-07-12).
  - "Support team is completely unhelpful" (2026-06-08).
  - Backups on an Ultimate Reseller plan "completely non-functional since January 2026"; the reviewer adds "A backup you cannot restore is not a backup" and "Backuply used instead of advertised JetBackup" (2026-07-25, cPanel reseller).
  - Automatic malware scanner "deleted files without warning"; anti-bot system "blocked 50% of legitimate users"; "AI control panel with bugs" (2026-06-18).
  - "Suspended without warning; website offline 4 days" (2026-05-13).
  - Must pay to reactivate an expired service to download your own files (2026-03-09) and to migrate after a domain renewal (2026-09-09).
  - Email accounts "disabled indefinitely" (2025-08-15); emails landing in spam (2025-06-27).
  - "email deliverability broken for 4 months" after migration (2025-05-27).
  - "Reseller hosting overly complicated; confusing dashboards" (2025-10-30).
- **CONFIRMED (2-star, 2024-10-16 NZ, OLD):** A reseller says PHP mail() was **disabled without warning**, affecting "150 websites" that then needed SMTP. The quote: "We also will not warn you if all forms within your websites will not work". https://www.trustpilot.com/review/www.verpex.com?stars=2
- **CONFIRMED (HostAdvice, May 2026):** "The live chat response came from a sales agent … The absence of a dedicated technical support tier is worth noting." Chat first asks for email, name and a support PIN. https://hostadvice.com/hosting-company/verpex-hosting-reviews/verpex-hosting-reseller-hosting-review/
- **CONFIRMED (GoogieHost, 11 contacts over 6 months):** live chat 9/10 (under 1 minute), tickets 8/10, email 5/10 ("8-14 hours delay"), phone 6/10. https://googiehost.com/blog/verpex-reviews/
- **CONFIRMED (prehost, Sept 2026):** "Slower ticket support response times noted". https://prehost.com/hosting/verpex/
- **CONFIRMED (LET 2026-05-15):** A buyer shortlisting Verpex Silver writes: "'Suspended without notice, no refund' Trustpilot cluster from 2025-2026 is making me nervous", with ~$36 in year 1 and ~$120/yr on renewal. https://lowendtalk.com/discussion/217297/migrating-from-regxa-need-litespeed-enterprise-cpanel-india-singapore-under-60-year-india
- **OLD:** WHTop user reviews (2021-2023) average 1/10. One from 2023-07-19 says reseller plans "don't disclose the information on CPU usage" until after purchase. https://www.whtop.com/review/verpex.com
- **OLD:** WHT 2023-04 thread on the Verpex reseller: "the limit is 200 cpanels", and the unlimited plan was called "overpitched". https://www.webhostingtalk.com/showthread.php?t=1895067
- **INFERENCE:** Front-line chat is fast, but complex or infrastructure problems escalate slowly and inconsistently. For a volunteer group with no sysadmin on call:
  - keep the stack simple;
  - keep independent backups;
  - do not depend on support to fix non-standard setups.

## 5. Limits people hit

### Email
- **CONFIRMED:** Verpex shared/cPanel plans allow 100 emails per hour per account.
  - Search-result snippet from Verpex KB: "maximum hourly limit of 100 emails … removed for managed cloud servers". https://kb.verpex.com/docs/email-limits — **that URL returned 404 on 2026-09-25.**
  - Hosting Explorers repeats "100 messages per hour per account" and lists "Email send limit constrains high-volume automation workflows" as a con. https://hostingexplorers.com/reviews/verpex/
  - **Whether the Enhance reseller has the same limit is unknown.**
- **CONFIRMED (Enhance platform, community 2025-09):**
  - "Enhance only allows a global hourly email limit, applied equally to all accounts" (set per server by the provider).
  - Another provider calls it "useless … doesn't permanently block".
  - Per-package limits were still a feature request.
  - https://community.enhance.com/d/3113-email-sending-limit-per-package
  - Older: in 2023, a provider noted that the per-IP limit counts server-originated PHP mail across all domains. https://community.enhance.com/d/155-mail-sending-rate-limit
- **CONFIRMED (community 2026-09-23):** "Enhance is very limited (out of the box) regards to email … filtering sliders … seem to be more of a cosmetic thing". https://community.enhance.com/d/3852-email-phishing-incident
- **CONFIRMED (community 2025-11):** WordPress contact forms "appear to send but no messages are actually delivered". Replies say you need a relay or smarthost plus SPF/DKIM, and that "Phpmail() without configuration, it will probably never make it through proper spam filters". https://community.enhance.com/d/3233-wordpress-forms-not-sending-emails-do-i-need-to-enable-the-email-role
- **INFERENCE for SpokARES:**
  - Traffic will be low (join and contact forms, occasional notices), so hourly caps are not a practical problem.
  - The real risks are **deliverability** and **PHP mail() silently failing or being disabled**.
  - Send form mail through authenticated SMTP (a Verpex mailbox or a transactional provider), with SPF, DKIM and DMARC set up.
  - Do not bulk-email the whole membership from the web host. Use a list service.

### CPU, RAM, processes and inodes
- **CONFIRMED (Enhance platform):** Per-website containers are limited by cgroups. Providers choose the values:
  - 1263 (2024-02, OLD): some run "unlimited" I/O.
  - 558 (2024-12, OLD): "We never use unlimited in any cgroups limits".
  - https://community.enhance.com/d/558-nproc-limit-on-every-account , https://community.enhance.com/d/1263-you-guys-limit-io-or-nah
- **CONFIRMED (community 2026-09-10):** Inside a container, `free -m` and `nproc` show the **host's** resources, not the package limit. "a Next.js build OOMs … at around 2 GB which is the package limit". Enhance: "This is expected, it's a container rather than a VM". https://community.enhance.com/d/3837-why-does-a-website-container-see-the-hosts-memory-instead-of-its-package-limit
- **CONFIRMED (community 2026-01):** A bug in which "Node apps run outside cgroup with unlimited resources" was reported and later marked RELEASED (fixed). https://community.enhance.com/d/3338-bug-node-apps-runoutside-cgroup-with-unlimited-resources-released
- **CONFIRMED:** Inode limits were requested (2025-02) and the thread is marked "[RELEASED]". https://community.enhance.com/d/598-innode-limitations-released , https://community.enhance.com/d/2429-inode-limits-logged
- **CONFIRMED:** Verpex's per-site values for Enhance (CPU, RAM, inodes, processes) are **not in any independent source**.
  - The Hostnamy and LinuxTeck numbers (2 GB, 2 vCPU, 30-35 EP) are cPanel/CloudLinux LVE.
- **INFERENCE:** Do builds (npm, static site generator) **off the server**, locally or in CI. Container memory limits are invisible to tools and can OOM at around 2 GB. Deploy only the built artifacts.

### Commercial and lock-in
- **CONFIRMED:** Renewal jumps are the most common con:
  - Bronze $3 renews at $6; Silver ~$36 in year 1 becomes ~$120/yr.
  - "up to 90% higher" (HostScore); "up to 10x" (Hostnamy).
  - Sources: https://googiehost.com/blog/verpex-reviews/ , https://hostscore.net/review/verpex/ , https://hostnamy.com/verpex-reseller-hosting/
- **CONFIRMED (Trustpilot 2026-03):**
  - Cannot edit DNS without a hosting plan, and "export of existing DNS records refused" when leaving.
  - After expiry: "told must pay full month to reactivate server" to get files.
  - https://www.trustpilot.com/review/www.verpex.com?stars=1
  - INFERENCE: keep the site source in git and keep our own DNS zone export, so leaving never depends on Verpex.

## 6. Node.js, static-site and deploy workflows (Enhance platform; user reports)

- **CONFIRMED:** Node.js hosting shipped in **Enhance 12.11.0 on 2025-09-22**, after about 2.5 years of delays ("Adam was confident to delivery in June 2024! 10 month later... nothing!"). https://community.enhance.com/d/129-nodejs-support-released , https://community.enhance.com/d/1432-nodejs
  - Earlier, pm2 did not work; "Enhance will start and monitor the app automatically". Containers "can't open a port to the wider internet"; the web server proxies to the app port (Adam, 2024-08-05).
- **CONFIRMED:** How it works, from an independent host's tutorial (redwaterhost, 2026-08-30) https://redwaterhost.com/blog/host-a-nodejs-app-on-enhance :
  - Location: Websites > Advanced > Node.js > Deploy app.
  - Settings: working directory relative to $HOME, startup command, Node version, a port in 1024-65535, a proxy path.
  - Automatic mode restarts the app on exit. Logs go to `persistent_app_ID.log`.
  - **There is no panel UI for environment variables.** Load them from a file in your start script. Next.js standalone "does not read `.env` at all".
  - SSH sessions and the app run in **separate containers**, so `pgrep node | xargs kill` "fail[s] silently".
  - "Only the primary domain proxies to the app; aliases don't reach it".
  - "Backups restore files but don't redeploy the app".
  - vhost regeneration overwrites hand edits.
- **CONFIRMED (community):**
  - A Node app on a **subdomain requires creating that subdomain as a separate website** (2025-12, 2026-06). https://community.enhance.com/d/3299-nodejs-proxy-support-for-subdomains-and-config-ui-improvements , https://community.enhance.com/d/3655-everything-feels-half-baked-or-stone-age
  - There are no start/stop/restart buttons. One user restarts through the Enhance API as a workaround (2026-07-13). https://community.enhance.com/d/3239-restart-nodejs-app-after-deployment
  - MySQL from Node: `127.0.0.1` fails because it is the container's loopback. Use the unix socket `/run/mysqld/mysqld.sock`, or the 10.169.x.x host-link IP plus a remote-access grant (Adam, 2025-11 and 2026-01). https://community.enhance.com/d/3355-nodejs-env
  - Env-var problems made n8n impractical: "very very early days to use enhance node in any production" (2026-01-05), and the user moved to Docker. https://community.enhance.com/d/1432-nodejs
  - An Enhance user on Node (2026-06-20): "I still can't / won't host my NodeJS apps via Enhance because it would just a be a pain and restrictive". Adam agrees that the main gap is Git/GitHub pulls without SSH. https://community.enhance.com/d/3655-everything-feels-half-baked-or-stone-age
  - PostgreSQL is offered at version 16 (Ubuntu 24.04 default), which one user criticised (2026-06).
- **CONFIRMED: there is no built-in Git deploy yet.**
  - Feature thread "Git integration with auto deploy (LOGGED)" dates from 2023-02. The latest post (2026-09-21) says "WE are also waiting for this very long". https://community.enhance.com/d/245-git-integration-with-auto-deploy-logged
  - Adam, 2026-08-28: "We are about to release a huge feature, Github integration and auto-deploy." https://community.enhance.com/d/3814-12-25-6-available
  - The latest release as of 2026-09-21 is 12.25.12, and none of the 12.25.x notes mention Git. https://community.enhance.com/d/3853-12-25-12-now-available
  - Workaround users mention: "I know you can pull via the SSH" (2026-04-13).
- **CONFIRMED (Enhance 12.24.0, 2026-07-03):** "Customers can now enable the ability to edit cron jobs with SSH … via the 'developer tools' section". https://community.enhance.com/d/3691-12-24-0-now-available
- **CONFIRMED:** nginx customisation is limited: basic rewrites in the UI, config includes allowed, but "you can't currently override the 'Location / {...}' block". The redirect manager "needs some work, coming soon" (Adam, 2026-06-18). https://community.enhance.com/d/3655-everything-feels-half-baked-or-stone-age
- **CONFIRMED (Trustpilot enhance.com):** 4.8/5 from 58 reviews, mostly hosting providers. One 3-star review (undated in extraction): "lack of basic features like NodeJS hosting, PHP module manager or even retaining OpenLitespeed configuration makes it only usable for simple cases". https://www.trustpilot.com/review/enhance.com
- **CONFIRMED (LET 2026-05):** "you do need more sysadmin skills than either cpanel/da". https://lowendtalk.com/discussion/217622/enhance-control-panel-experience
- **Static sites:** No source reports a problem serving plain static files from an Enhance website's document root (public_html). This is ordinary web-server behaviour.
  - INFERENCE: A static build (HTML/CSS/JS plus PDFs) is the lowest-risk option on Verpex Enhance. It is immune to the Node.js rough edges, needs no build RAM on the server, and restores cleanly from backups.
  - Deploy options: SFTP or rsync over SSH, or `git pull` over SSH. None needs the not-yet-shipped Git UI.
  - **Confirm SSH/SFTP is enabled in the Verpex Enhance package.**
- **Platform pace (not Verpex-specific):** Several paying providers complain that features are slow to arrive and roadmaps slip:
  - "enhance had 5 members now that only 3 left" (2026-01-18, unverified user claim).
  - "Enhance won't stick to roadmaps once voted on" (2026-07-19).
  - Enhance replies that it is a "4 year established product with thousands of customers and hundreds of thousands of websites" (2026-08-28).
  - Sources: https://community.enhance.com/d/245-git-integration-with-auto-deploy-logged , https://community.enhance.com/d/3691-12-24-0-now-available , https://community.enhance.com/d/3814-12-25-6-available
  - INFERENCE: Do not design around features that are announced but not released.

## 7. Datacenter / region options seen in independent sources

- HostAdvice (May 2026): "8 locations, including London, the U.S., Australia, Canada, and India" (cPanel reseller checkout).
- prehost (Sept 2026): 9 locations: Buffalo, Dallas, Frankfurt, London, Mexico City, Mumbai, Singapore, Sydney, Toronto.
- VPSBenchmarks: 9 DCs in AU, CA, DE, IN, MX, NL, SG, UK, US.
- GoogieHost: 14 locations, including "US East/West/Central".
- Hostnamy (2025-12): "12+ locations" including San Francisco and São Paulo.
- **Conflicting. INFERENCE:** The current US options are probably Buffalo/NY (use1), a second US East (use2), and Dallas (usc1). A US West region is uncertain.
- **Which of these the Enhance reseller offers is not in any independent source.**

## 8. Implications for the SpokARES rebuild (all INFERENCE)

1. **Build static, deploy artifacts.**
   - Generate the site off-server with any SSG or plain HTML, and upload over SFTP/rsync.
   - This avoids Enhance Node.js limits: no env-var UI, primary domain only, no restart button, no Git UI yet, build OOMs.
   - Put dynamic needs such as the net-controller schedule and the member area in a small PHP layer or a third-party service, not a long-running Node app.
2. **DNS strategy against IP changes.**
   - Use Verpex/Enhance nameservers. Or, if Cloudflare is required, subscribe to status.verpex.com and document an A-record update runbook.
   - This is the most frequent real-world failure mode in the 2026 evidence.
3. **Email.**
   - Send form mail through authenticated SMTP with SPF/DKIM/DMARC.
   - Never rely on PHP mail().
   - Use a list provider for member-wide notices.
4. **Backups.** Keep off-Verpex backups: git for the site source and periodic exports of DB, uploads and DNS. There are 2026 complaints of reseller backups failing to restore, and of exit or access friction after expiry.
5. **Support expectations.** Fast L1 chat, slow escalation. Keep the stack standard so support is rarely needed.
6. **Verify with Verpex directly** (no independent data exists):
   - Enhance region list, especially US West
   - per-site CPU, RAM, inode and process limits
   - email hourly cap
   - whether Node.js, SSH, SFTP and cron are enabled in reseller packages
   - web server (LiteSpeed, OpenLiteSpeed, nginx or Apache), because that decides whether `.htaccess` works
   - which Enhance version Verpex runs and how quickly it updates

## Sources that failed or were unavailable
- reddit.com / old.reddit.com: HTTP 403, both direct and through r.jina.ai. No Reddit data.
- lowendtalk.com: 403 to WebFetch and curl; read successfully through https://r.jina.ai/ .
- trustpilot.com: 403 to curl; WebFetch worked. Per-review quotes are as extracted by WebFetch's summariser; spot-check them before quoting publicly.
- techradar.com/computing/software/verpex: paywall/portal page with no review content.
- kb.verpex.com/docs/email-limits: 404 on 2026-09-25 (search snippet only).
- community.enhance.com: the HTML is JS-only, so posts were read through the public Flarum JSON API (`/api/posts?filter[discussion]=ID`).
