# spokares.org deploy runbook

How our code gets from this repository to the Verpex/Enhance site, and how to launch it. The model is PLAN.md §5.7. Only a clean export of one git commit is deployed: the theme `spokares`, the plugin `spokares-core`, the must-use plugin `spokares-hardening`, and `~/bin/weekly-check.sh`. `dev/`, `tools/`, uploads and the database are never deployed. Nothing in this file is a secret; the secrets are named by file path or GitHub secret name only.

## At a glance

| What | Where |
|---|---|
| CI | `.github/workflows/ci.yml` runs `wordpress/ops/ci.sh` (the full gate) on every push and pull request |
| Deploy | `.github/workflows/deploy.yml` runs on a pushed `v*` tag, or by hand (`workflow_dispatch`, input `ref`, default `main`) |
| Gate before a deploy | the same `ci.yml`, on the exact commit being deployed; the deploy job waits for it |
| Approval | the `production` environment requires **seantmalone** to approve every deploy (Actions > the run > Review deployments > Approve). Only `main` or a `v*` tag can be deployed (the `check-ref` job) |
| Deploy steps | `ops/deploy.sh <ref> production --yes` (rsync over SSH), then the ref's `ops/post-deploy.sh` on the host in one SSH session, then `ops/check-live.sh` |
| Site | Enhance website `2810b31a-7b60-4369-8450-bfe75befb3fd` on the Fire Lizard Host reseller org; server `198.38.90.25`; WordPress in `public_html` |
| Before the DNS cutover | spokares.org still points at the old InMotion host. The new site is reached by pinning the name to the server (below) |
| What is deployed now | `~/.spokares-deployed-tag` on the host: the tag, or `git describe` of a manual deploy's commit |

## Where the secrets live

Secrets exist in exactly two places. Never put them in the repository, a commit message, an issue, or a workflow log.

**Owner's machine:** `/Users/sean/Projects/spokane_ares-secrets/`. The folder is mode 700 and every file in it is 600. It is not a git repository.

| File | What it is |
|---|---|
| `enhance.ini` | Enhance API token (Sysadmin role; not IP-restricted; expires 2027-07-01). **Never** put it in GitHub: an Enhance token can't be limited to one website |
| `wordpress-admin.txt` | The WordPress administrator's login and password |
| `deploy_ed25519`, `deploy_ed25519.pub` | The deploy SSH key (comment `spokares-github-deploy`). It is authorized on the website only |
| `known_hosts` | The server's pinned SSH host key (ed25519, `SHA256:Lc3C+9de8BzMhGc4QTNEGjHKp2U94slaFQAbxK8dgjY`) |
| `ssh_config` | `Host spokares-live`, included from `~/.ssh/config`. The ops scripts use this alias through `wordpress/ops/ops.env` (git-ignored) |
| `verpex-site.md` | The provisioning record: ids, servers, what was configured. It holds no secret values |

**GitHub:** the repository's environment **`production`** (Settings > Environments). Only workflow runs started from `main` or from a `v*` tag may use it, so a branch or a fork can't reach the secrets. Pull requests never get them. No required reviewer is set, because releases deploy automatically on a tag. If you want every deploy to wait for your click, add yourself under Required reviewers; `research/security-hosting.md` §8.2 suggests this.

| Secret | Value comes from |
|---|---|
| `DEPLOY_SSH_KEY` | `deploy_ed25519` (the private key) |
| `DEPLOY_KNOWN_HOSTS` | `known_hosts` |
| `DEPLOY_HOST` | the server IP |
| `DEPLOY_USER` | the website's unix user (see `verpex-site.md`) |
| `DEPLOY_PORT` | `22` |
| `DEPLOY_PATH` | `public_html` |

Set or replace one by piping it in, never by typing the value on the command line:

```bash
gh secret set DEPLOY_SSH_KEY --env production --repo seantmalone/spokane_ares < /Users/sean/Projects/spokane_ares-secrets/deploy_ed25519
```

## Release (the normal way)

1. Bump `Version` in all eight places that `ops/ci.sh` checks: the theme's `style.css`, `SPOKARES_THEME_VERSION` and `readme.txt`; the plugin's header, constant and `readme.txt`; the must-use plugin's header and constant.
2. Commit, push `main`, and wait for CI to pass (`gh run watch`).
3. Tag and push the tag:
   ```bash
   git tag -a v0.2.0 -m "v0.2.0: <what changed>"
   git push origin v0.2.0
   ```
4. The **Deploy** workflow starts by itself. It runs `ci.sh --tag=v0.2.0` (the eight versions must equal `0.2.0`), deploys, and checks the site. Watch it with `gh run watch`, or under Actions > Deploy.
5. The release zips are kept on the run as the artifact `release-v0.2.0` for 90 days. To keep them longer, attach them to a GitHub release:
   ```bash
   gh run download <run id> -n release-v0.2.0 -D /tmp/release-v0.2.0
   gh release create v0.2.0 /tmp/release-v0.2.0/*
   ```

If routes, post types or the `/docs/` rule changed, also flush the rewrite rules. Run a manual deploy of the tag with `flush_rewrites` ticked. `spokares-core` also rebuilds them itself the first time a new version runs.

## Manual deploy

From GitHub (the gate and the checks run the same way):

```bash
gh workflow run deploy.yml --repo seantmalone/spokane_ares -f ref=main            # main (the only branch allowed)
# Any other branch, or a bare commit SHA, is refused by the workflow's check-ref job.
gh workflow run deploy.yml --repo seantmalone/spokane_ares -f ref=v0.2.0          # a tag
gh workflow run deploy.yml --repo seantmalone/spokane_ares -f ref=v0.2.0 -f flush_rewrites=true
gh run watch --repo seantmalone/spokane_ares
```

A ref that is not a tag deploys when its eight versions agree with each other. `~/.spokares-deployed-tag` then records `git describe` of the commit, such as `v0.1.0-3-g1a2b3c4d5e6f`.

From the owner's machine (uses `ops.env` and the `spokares-live` alias):

```bash
wordpress/ops/deploy.sh v0.2.0 production --dry-run     # what would change
wordpress/ops/deploy.sh v0.2.0 production               # asks you to type "production"
wordpress/ops/check-live.sh v0.2.0 --resolve=198.38.90.25   # before the cutover; plain after it
```

Deploys never overlap: the workflow's concurrency group `deploy-production` queues the second one.

### What a deploy does on the host

`ops/post-deploy.sh` runs from the deployed commit, in one SSH session. Every step is safe to repeat.

1. Sets permissions on our code: folders 755, files 644.
2. Installs Two-Factor and UpdraftPlus from wordpress.org if they are missing, activates them, and turns on WordPress's own auto-updates for both.
3. Activates the theme `spokares` and the plugin `spokares-core` if they are inactive.
4. Keeps "Discourage search engines" on (`blog_public` 0) while `.htaccess` still has the `spokares PRE-LAUNCH` block.
5. Runs `wp rewrite flush` (when asked, or when it has just activated `spokares-core`) and `wp cache flush`.
6. Records the deploy in `~/.spokares-deployed-tag`.

### What the check does

`ops/check-live.sh` does three things:

- It compares the host's code with the deployed commit (`rsync -rcn`).
- It runs the file checks from `weekly-check.sh --find-only`: no PHP under uploads, and nothing unexpected in plugins, themes or mu-plugins. The expected lists are in `~/.spokares-weekly.env` on the host, or the script's defaults until that file exists.
- It records the upload cap over SSH (PLAN §5.3: 32 MB in php.ini). It reads the CLI's php.ini, which can differ from the web server's, so anything other than 32 MB is a NOTE, not a failure: confirm the real value on Media > Add New.
- It runs the PLAN §5.11 curl list: status codes, security headers, enumeration closed, and the 301/410 map. The security headers are checked on `/`, `/wp-login.php`, `/wp-admin/`, and on `admin-ajax.php` and `admin-post.php`, which run wp-admin's `admin_init` for anyone. Each must carry the whole Content-Security-Policy (`frame-ancestors 'self'`, `base-uri 'self'`, `form-action 'self'`, `object-src 'none'`), not just `frame-ancestors`. The script has no sign-in, so the go-live list checks one signed-in screen by hand.

### What the weekly check does

`~/bin/weekly-check.sh` runs on the host from the Enhance cron job (PLAN §5.8). It runs the file checks above, then these WordPress checks:

- core and plugin checksums
- Two-Factor is active
- the administrators are exactly `SPOKARES_ADMINS`
- every user who can edit has a Two-Factor method
- no dev constants (`SPOKARES_DEV`, `SPOKARES_TODAY`, `WP_DEVELOPMENT_MODE`)
- `DISALLOW_UNFILTERED_HTML` and `DISALLOW_FILE_EDIT` are on in `wp-config.php` (PLAN §5.2)
- no Site Editor copies of templates, template parts or global styles in the database (PLAN §5.7, risk 10)

A Site Editor save (Appearance > Editor) stores a copy of the template, part or styles in the database, and that copy silently replaces the theme's file. `check-live.sh` compares files, so it can't see these copies. When the weekly check reports one, open it in Appearance > Editor and use **Reset** (or **Clear customizations** for styles). Then make the change in git, if it was wanted. An empty global-styles record, which the Site Editor creates just by opening, is not reported.

Before the cutover, the workflow pins the curl checks to `DEPLOY_HOST` (`--resolve`, and `-k` for the self-signed certificate). The pin drops by itself once spokares.org resolves to the server, and the real certificate is then verified.

If PHP changes don't seem to take effect after a deploy, OPcache may be stale. The CLI reports `validate_timestamps=On`, but LSAPI could differ. Restart PHP for this website only: `POST /api/v2/websites/2810b31a-7b60-4369-8450-bfe75befb3fd/restart_php` with the token from `enhance.ini`, or use the panel.

## Rollback

Deploy the previous tag:

```bash
gh workflow run deploy.yml --repo seantmalone/spokane_ares -f ref=v0.1.0
```

`--delete` removes files the old tag doesn't have, and only inside our three code folders. A rollback never touches the database, uploads or third-party plugins. If the rolled-back code has different routes, tick `flush_rewrites`.

**Never** use Enhance "push live" or restore a staging site over production. It copies the database and would overwrite the editors' rota, events and documents.

If the site is broken and GitHub is unavailable, run `wordpress/ops/deploy.sh v0.1.0 production` from the owner's machine. For a data problem, restore from the backups in PLAN §5.9: Enhance snapshots, UpdraftPlus, or the monthly `ops/backup-pull.sh` copy.

## Rotating the deploy key

Rotate it once a year, when someone with access leaves, or at once if the key or a GitHub account might be compromised. The key gives a full shell as the website's unix user on this one site, because Enhance can't add `command=` restrictions.

```bash
S=/Users/sean/Projects/spokane_ares-secrets
ssh-keygen -t ed25519 -N '' -C spokares-github-deploy-$(date +%Y%m) -f "$S/deploy_ed25519.new"
chmod 600 "$S"/deploy_ed25519.new*
```

1. Authorize the new public key on the website. Use the panel (Website > SSH > Keys), or `POST /api/orgs/{org}/websites/2810b31a-7b60-4369-8450-bfe75befb3fd/ssh/keys` with body `{"name": "...", "value": "<contents of deploy_ed25519.new.pub>"}`. Read the token from `enhance.ini` into a shell variable and send the header through `curl -H @-`, so the token never appears in the process list or the terminal.
2. Test it: `ssh -i "$S/deploy_ed25519.new" -o IdentitiesOnly=yes -o UserKnownHostsFile="$S/known_hosts" <user>@198.38.90.25 true`.
3. Replace the key: `mv "$S/deploy_ed25519.new" "$S/deploy_ed25519"` and `mv "$S/deploy_ed25519.new.pub" "$S/deploy_ed25519.pub"`. Then update GitHub: `gh secret set DEPLOY_SSH_KEY --env production --repo seantmalone/spokane_ares < "$S/deploy_ed25519"`.
4. Run a manual deploy of the current tag to prove the new key works.
5. Remove the old key. List the keys with `GET .../ssh/keys`, then `DELETE .../ssh/keys/{key_id}` for the old one (the first key was id `0`).

If the server's host key ever changes, CI and local SSH both refuse to connect (`StrictHostKeyChecking yes`). Treat this as an incident until Verpex confirms the change. Then check the new fingerprint through the panel or support, write the new `known_hosts` line, and update `DEPLOY_KNOWN_HOSTS` the same way.

The Enhance API token isn't used by the pipeline. After the project, IP-restrict it or revoke it in the panel.

## Looking at the site before the cutover

**Primary: the real site under its real name.** Add this one line to `/etc/hosts` (with `sudo`), then flush the DNS cache:

```
198.38.90.25 spokares.org www.spokares.org
```

```bash
sudo dscacheutil -flushcache; sudo killall -HUP mDNSResponder
```

Open https://spokares.org/ and https://spokares.org/wp-login.php. The browser warns about the certificate until the cutover, because the server has only a self-signed one for spokares.org. The old site sends no HSTS, so you can click through. Firefox with strict DNS-over-HTTPS ignores `/etc/hosts`. **Remove the line at the cutover.** From the command line, no hosts edit is needed:

```bash
curl -sk -A 'Mozilla/5.0' --resolve spokares.org:443:198.38.90.25 https://spokares.org/
```

**Secondary: a quick first look with a valid certificate.** https://spokares-org-us2s.staging.firelizardhost.com/ is Enhance's preview domain. It sends noindex. A `PRE-LAUNCH` block in `wp-config.php` makes WordPress use the preview host's own URLs there.

## One-time setup: `ops/first-run.sh`

It has three modes. Always run `--preview` first; it changes nothing.

```bash
wordpress/ops/first-run.sh main production --preview          # read-only: what is there, what a run would do
wordpress/ops/first-run.sh main production                    # the site structure (asks you to type "production")
wordpress/ops/first-run.sh main production --launch-cleanup   # the launch clean-up, a separate decision
```

**Default run (done 2026-09-27 from `main`).** It uploads only four files to a private temp folder outside `public_html`, runs them with `wp eval-file`, and deletes the folder: `dev/seed/import.php` with its `data.json` and `extra.json`, and `dev/setup/pages.php`. Dev-only code (the dev login, the QA and test code) never leaves the machine.

- **Importer, production mode:** all 37 documents are Drafts with no Privacy tick. 9 of the 20 events (those marked "Needs checking") are Drafts. `spk_seeded` is now set, so the importer won't run again.
- **Pages:** Home, How it works, About ARES & ACS, For members, Documents & forms, Exercises & events. The Home hero comes from the Media Library, and Home is the static front page. Navigation is inline in the theme's parts, so there are no menus to create. The `ares_editor` role exists; `spokares-core` creates it on activation.
- WordPress's sample content (Sample Page, "Hello world!", the Privacy Policy draft) is kept by this mode.

**`--launch-cleanup` (not run yet: the owner decides).** It uploads nothing and does five things on the host:

- deletes the untouched sample content
- deletes Akismet, Hello Dolly and every default theme except the spare `twentytwentyfive`, only while they are inactive
- lets UpdraftPlus create its deny-all `wp-content/updraft/` folder
- adds the two rewrite rules from `research/security-hosting.md` §4.4 above `# BEGIN WordPress`: no PHP under uploads, no direct `wp-includes/*.php`. The old file is kept as `~/.spokares-htaccess.before-first-run`
- writes `~/.spokares-weekly.env`

Until it runs, the deploy workflow's check step reports exactly three expected failures (27 checks pass). A deploy run therefore shows red even though the deploy step itself succeeded:

- the host file checks: Akismet, `hello.php` and the extra default themes are present
- `/wp-content/uploads/x.php` gives 404, not 403: the `.htaccess` rule is missing
- `/wp-content/updraft/` gives 404, not 403: the folder doesn't exist yet

After `--launch-cleanup`, run a manual deploy; the check step should then pass.

Both modes are safe to run again.

## Owner to-do now (before launch)

- **Two-factor for the administrator, now.** Sign in through the `/etc/hosts` line or the preview domain; wp-admin sends you to your profile's Two-Factor section. Set up TOTP and print the backup codes. Until then the account (whose name the hardening now hides) is protected by its password only. Use TOTP rather than Email: the admin address `webmaster@spokares.org` is a placeholder.
- ~~Decide on `first-run.sh ... --launch-cleanup`.~~ Done 2026-09-27 (sample content, Akismet, Hello Dolly and spare themes removed; .htaccess hardening block incl. readme.html/license.txt denied); deploy run 36340121133 green, check-live 31/0.

## Go-live checklist

The owner decides when. Work top to bottom.

### Before the cutover

- [ ] The launch gate (PLAN §6.13) and the content review (§5.11). An administrator reviews and publishes each document, ticking its Privacy check, and clears or drafts every "Needs checking" item. The footer mock line is removed in the launch release.
- [ ] Two-factor: the administrator sets up TOTP and prints backup codes (PLAN §5.4). Editors are created and enrolled in person. wp-admin sends any account without 2FA to its Two-Factor settings.
- [ ] The administrator's e-mail is a real mailbox, not the placeholder `webmaster@spokares.org` (Users > Profile).
- [ ] Mail for WordPress (PLAN §5.1): create the `website@spokares.org` mailbox wherever spokares.org mail will live. Put `SPOKARES_SMTP_HOST/PORT/USER/PASS` in `wp-config.php` on the host, never in git. Send a test password reset.
- [ ] UpdraftPlus: connect a role-owned Google Drive. Set the database backup weekly (keep 8) and uploads monthly (keep 3). Confirm `/wp-content/updraft/` returns 403.
- [ ] The weekly integrity cron (PLAN §5.8). This is an Enhance cron job, not crontab: `SPOKARES_PING_URL=https://hc-ping.com/<uuid> ~/bin/weekly-check.sh`. Also set up the dead-man service. Run `~/bin/weekly-check.sh --no-ping` once over SSH: it must say "all clear". It fails if `wp-config.php` lacks `DISALLOW_UNFILTERED_HTML` or `DISALLOW_FILE_EDIT`, or if the Site Editor has stored a copy of a template or the styles.
- [ ] `wp-config.php` has every PLAN §5.2 constant: `DISALLOW_FILE_EDIT`, `DISALLOW_UNFILTERED_HTML`, `FORCE_SSL_ADMIN`, `WP_ENVIRONMENT_TYPE` `production`, `WP_DEBUG` false, `WP_POST_REVISIONS` 20, and file mode 600.
- [ ] Upload cap (PLAN §5.3): in the site's PHP settings, `upload_max_filesize` is 32M and `post_max_size` is at least 32M. Signed in as an administrator, Media > Add New says "Maximum upload file size: 32 MB". Record both values here, and compare them with the NOTE or PASS line `check-live.sh` prints.
- [ ] Headers on a signed-in screen (QA-007). Signed in, open the Dashboard, then DevTools > Network > the `wp-admin/` document > Response headers. `Content-Security-Policy` must list `frame-ancestors 'self'; base-uri 'self'; form-action 'self'; object-src 'none'`, not `frame-ancestors` alone.
- [ ] Run `wordpress/ops/first-run.sh <tag> production --preview`, then `--launch-cleanup` (above), if that hasn't been done yet.
- [ ] Tag the launch release (for example `v1.0.0`) and let it deploy. `check-live.sh` should be all PASS.
- [ ] About a day ahead, lower the TTL of the spokares.org records to 300 at the DNS host that is authoritative now: **InMotion's** nameservers `ns1/ns2.inmotionhosting.com`. The domain is registered at GoDaddy, but GoDaddy isn't serving its DNS.

### Mail: decide before touching DNS

Today spokares.org mail goes to InMotion. The MX record is `0 spokares.org.`, which means "the apex host". `mail.spokares.org` is a CNAME to the apex. **Changing the apex A record therefore moves mail too.** On the new server, mail would go to the Enhance app server, where no mailboxes exist. Enhance treats spokares.org as a *remote* mail domain on purpose.

- **To keep mail at InMotion (the smallest change):** first add `mail.spokares.org A 70.39.146.166`, replacing the CNAME. Then set MX to `0 mail.spokares.org.` and wait out the TTL. Only then change the apex A record.
- **To move mail to Enhance:** create the mailboxes on Enhance and migrate them first (the `ss:enhance-api` email playbook). Set spokares.org to *local* on the Enhance mail server. Only then move MX.

SPF must list whatever sends as @spokares.org. Enhance's own zone uses `v=spf1 +a +mx include:spf.mysecurecloudhost.com ~all`. Keep InMotion's `ip4:70.39.251.92` while InMotion still sends mail. Turn on DKIM. Start DMARC at `p=none`.

### The cutover

Pick **one** of the two:

- **A. Change only the web records (recommended while mail stays at InMotion).** At InMotion's DNS (cPanel > Zone Editor for spokares.org), after the mail steps above, set `spokares.org A 198.38.90.25`. `www` is a CNAME to the apex, so it follows. Nothing changes at GoDaddy.
- **B. Move the whole zone to Enhance.** First make the Enhance zone for spokares.org complete: MX and SPF per the mail decision, and every other record the domain uses. It was auto-created and has MX `mail.spokares.org` pointing at the Enhance mail server. Then at **GoDaddy** go to My Products > Domains > spokares.org > DNS > Nameservers > Change > "I'll use my own nameservers", and enter `ns3.firelizardhost.com` and `ns4.firelizardhost.com`. Check with `dig +norecurse @ns3.firelizardhost.com spokares.org SOA`: the answer must carry the `aa` flag.

Then check that `dig +short spokares.org` shows `198.38.90.25` through 1.1.1.1 and 8.8.8.8.

### Right after the cutover

- [ ] SSL: issue Let's Encrypt for spokares.org and www. Use the panel (Website > SSL/TLS), or `POST /api/v2/domains/f8fecd7e-2bd1-40a7-9e8f-a73250438eaa/letsencrypt` (`/letsencrypt_preflight` first). Then force HTTPS: `PUT /api/v2/domains/f8fecd7e-2bd1-40a7-9e8f-a73250438eaa/ssl/force_ssl`. Until then `http://` also serves 200.
- [ ] Turn indexing on:
  - Remove the `# BEGIN spokares PRE-LAUNCH noindex` block from `public_html/.htaccess`.
  - Remove the `PRE-LAUNCH preview host` block from `wp-config.php`.
  - Untick Settings > Reading > "Discourage search engines" (`wp option update blog_public 1`).
  - The pipeline stops enforcing `blog_public 0` once the `.htaccess` block is gone.
- [ ] Delete the Enhance preview domain `spokares-org-us2s.staging.firelizardhost.com`: preview domains get crawled.
- [ ] Remove the `/etc/hosts` line on every machine that has it.
- [ ] Run a manual deploy of the launch tag. The check step now runs unpinned, with a verified certificate. Run `wordpress/ops/check-live.sh <tag>` locally too.
- [ ] HSTS: the must-use plugin sends `max-age=86400`. After a clean month, set `SPOKARES_HSTS_MAX_AGE` to `31536000` in `wp-config.php`.
- [ ] Search Console: verify the domain and submit `/wp-sitemap.xml`. Uptime and keyword monitor on `/members/` for "Net control".
- [ ] Update the comment in `wordpress/ops/ops.env`: it warns that the name still points at the old host.
- [ ] Revoke or IP-restrict the Enhance API token in `enhance.ini`.
- [ ] Afterwards, retire the InMotion hosting. Keep it until mail has moved, if mail moves.

## Troubleshooting

| Symptom | Likely cause and fix |
|---|---|
| `kex_exchange_identification: Connection reset` | fail2ban blocked rapid reconnects. The workflow multiplexes one connection. Locally, keep `ControlMaster` in `ssh_config` and wait a few minutes |
| SSH exit 254 with no output for about a minute | The site's container restarted after a php.ini change. Wait and retry |
| `Host key verification failed` | The host key changed. See "Rotating the deploy key" |
| Deploy job waits or is refused on the environment | Only `main` and `v*` tags may use `production`. Dispatch from `main` (`gh workflow run` uses the default branch) |
| `secret DEPLOY_... is not set` | Set it in the `production` environment (above) |
| Gate fails on versions | A `v*` tag must equal all eight version strings. Fix, commit, delete and re-push the tag |
| New PHP code not live | Restart PHP for the website (above) |
