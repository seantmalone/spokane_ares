export const meta = {
  name: 'spokares-verpex-provision',
  description: 'Create the spokares.org website on the Verpex Enhance reseller account, install WordPress, and build a GitHub Actions deploy pipeline — without re-pointing DNS',
  phases: [
    { title: 'Recon', detail: 'read-only: token scopes, org, subscription, PHP/WP, preview URL, existing sites' },
    { title: 'Provision', detail: 'website + WordPress + deploy key + preview URL, noindex' },
    { title: 'Pipeline', detail: 'GitHub Actions CI + tag/manual deploy, secrets, first deploy' },
    { title: 'Verify', detail: 'independent read-only verification' },
  ],
}

const R = '/Users/sean/Projects/spokane_ares'
const W = `${R}/wordpress`
const SECRETS = '/Users/sean/Projects/spokane_ares-secrets'
const INI = '/Users/sean/Projects/spokane_ares-secrets/enhance.ini'

const CONTEXT = `TASK (owner's words): "Create the Verpex site in my reseller account, install WordPress, and create the CI/CD pipeline from the repo to the live site. The domain will be the same (spokares.org), but it will not be re-pointed until the site is ready to go live."
OWNER DECISIONS: use the Enhance API credentials in ${INI} (a fresh Owner token the owner issued for this project on 2026-09-27; verified: GET /orgs/{org} = 200, org "Fire Lizard Host" = the owner's OWN reseller org; read the token into shell variables, NEVER print it, never copy it into the repo or logs; send it via 'curl -H @-' from printf so it never appears in process args); put the website under the owner's OWN org (not a new customer); deploy on version tags (v*) + manual workflow_dispatch, with checks on every push; PREVIEW: the PRIMARY method is pinning spokares.org to the Enhance server IP (curl --resolve, and a one-line /etc/hosts entry for the owner's browser — give the exact line; certificate warning expected until cutover) because WordPress stores absolute https://spokares.org URLs; ALSO create an Enhance preview domain (POST .../websites/{wid}/preview) only if the reseller already has a staging domain configured — as a quick first look only, and make sure it is noindexed; do not configure a staging domain if none exists (report it instead).
REPO: ${R} (GitHub: seantmalone/spokane_ares, PUBLIC; gh CLI is authenticated). WordPress build: ${W} — theme ${W}/theme/spokares, plugin ${W}/plugins/spokares-core, must-use plugin ${W}/mu-plugins/spokares-hardening, ops scripts ${W}/ops (deploy.sh, ci.sh, check-live.sh, lib.sh, ops.env.example, README.md), deployment/security plan in ${W}/PLAN.md §5 (deploy by rsync of a clean git-tag export; plugins: spokares-core, Two-Factor, UpdraftPlus; hardening; importer production mode leaves documents as drafts). Hosting research: ${R}/research/hosting/verpex-enhance.md and the Enhance OpenAPI spec at ${R}/research/hosting/_enhance-oas3-api.yaml (authoritative for endpoints — grep it).
ENHANCE SKILL: FIRST invoke the Skill tool with skill "ss:enhance-api" and follow it (auth/scoping quirks, websiteId vs domainId, 403 = scope vs 404 = path, no file-upload endpoint so use SSH/rsync, fail2ban: batch SSH commands or use ControlMaster, zsh doesn't word-split variables).
HARD SAFETY RULES:
- Touch ONLY the new spokares.org website. Never modify other websites, customers, the org's settings, or other projects' files (MarkSelleTransfer is read-only: only read the ini).
- NEVER change spokares.org DNS at GoDaddy or its nameservers, never touch the current InMotion host. The live site must be unaffected. Creating an Enhance DNS zone for the domain is fine (it is not authoritative until nameservers change).
- Search engines must not index the new site before launch (Settings > Reading "discourage", plus X-Robots-Tag/robots if practical).
- Secrets (WordPress admin password, SSH private key, API token) live ONLY in ${SECRETS}/ (mkdir -p, chmod 700; files chmod 600) and in GitHub Actions secrets. Nothing secret in the repo, commit messages, workflow logs, or your final output (refer to secrets by file path/secret name).
- Another workflow (a QA pass) is concurrently editing files under ${W}/ and using local ports 9600-9999. Do NOT use those ports (use 9950-9959 only if you need a local Playground, e.g. for ops/ci.sh), do NOT run "git add -A" or commit other people's in-progress changes — stage explicit paths only.
- If anything is ambiguous or risky (e.g. a website for spokares.org already exists, token lacks a scope, creating the site would incur a charge), STOP and report instead of improvising.
Today is ${args.today}.`

phase('Recon')
const RECON = {
  type: 'object',
  properties: {
    go: { type: 'boolean', description: 'true only if provisioning can proceed safely' },
    blockers: { type: 'array', items: { type: 'string' } },
    server_host: { type: 'string' },
    org_summary: { type: 'string' },
    subscription_to_use: { type: 'string', description: 'id + plan name + remaining allowances' },
    php_versions: { type: 'array', items: { type: 'string' } },
    wordpress_install_method: { type: 'string' },
    preview_url_method: { type: 'string' },
    ssh_method: { type: 'string' },
    existing_spokares: { type: 'string' },
    token_scopes_ok: { type: 'string' },
    plan: { type: 'string', description: 'exact provisioning steps with endpoints/payloads' },
  },
  required: ['go', 'blockers', 'server_host', 'org_summary', 'subscription_to_use', 'php_versions', 'wordpress_install_method', 'preview_url_method', 'ssh_method', 'existing_spokares', 'token_scopes_ok', 'plan'],
}
const recon = await agent(`${CONTEXT}\n\nYou are RECON. READ-ONLY: make only GET requests to the Enhance API (and read local files). Determine: org details and the subscription/plan in the owner's own org that a new website should use (and its remaining allowances — websites, disk, PHP); whether any website/domain for spokares.org already exists anywhere in the org (if so: blocker); token scope coverage for what we'll need (websites, domains, apps/WordPress installer, SSH keys, PHP settings, DNS zone read, preview/temporary URL) — probe with GETs and interpret 403 vs 404; available PHP versions (want 8.3 or 8.4) and the web server kind if visible; the WordPress installer app endpoint and what WordPress version it installs (PLAN needs WordPress 7.1+); how Enhance provides a PREVIEW/TEMPORARY URL for a site whose domain isn't pointed yet (grep the OpenAPI spec for preview, temporary, staging, alias, subdomain features; check what the org/server supports) — if none, plan the hosts-file / --resolve approach; how SSH/SFTP access works for a website (SSH keys endpoint, unix user, port). Also read ${W}/ops/deploy.sh, ops/README.md, ops/ops.env.example and PLAN.md §5 to note what the deploy script expects. Write a precise provisioning plan. Return structured result.`,
  { label: 'recon', phase: 'Recon', schema: RECON })

if (!recon.go) {
  log(`Recon blocked: ${recon.blockers.join(' | ')}`)
  return { stopped_at: 'recon', recon }
}

phase('Provision')
const PROV = {
  type: 'object',
  properties: {
    ok: { type: 'boolean' },
    website_id: { type: 'string' }, domain_id: { type: 'string' },
    server_ip: { type: 'string' }, unix_user: { type: 'string' }, ssh_port: { type: 'string' }, web_root: { type: 'string' },
    php_version: { type: 'string' }, wordpress_version: { type: 'string' },
    preview_url: { type: 'string', description: 'or hosts-file instructions' },
    secrets_files: { type: 'array', items: { type: 'string' } },
    notes: { type: 'string' },
  },
  required: ['ok', 'website_id', 'server_ip', 'unix_user', 'ssh_port', 'web_root', 'php_version', 'wordpress_version', 'preview_url', 'secrets_files', 'notes'],
}
const prov = await agent(`${CONTEXT}\n\nYou are the PROVISIONER. Recon results and plan:\n${JSON.stringify(recon, null, 1)}\n\nExecute: (1) create the spokares.org website in the owner's own org on the recon-chosen subscription with PHP ${'8.3 or 8.4 (prefer the newest supported by both WordPress 7.1 and the host)'}; capture websiteId/domainId, server IP, unix user, web root. (2) Install WordPress (Enhance installer if available, else via SSH + wp-cli if present, else via the official zip over SSH): site title "Spokane County ARES-ACS", a strong generated admin password for user "admin-spokares" (admin email: use a role-neutral placeholder the owner can change, e.g. the owner's account email is NOT to be used — use webmaster@spokares.org and note it is not live yet), timezone America/Los_Angeles, permalinks /%postname%/, "discourage search engines" ON. Save the admin credentials to ${SECRETS}/wordpress-admin.txt (600). (3) Generate an ed25519 deploy key pair at ${SECRETS}/deploy_ed25519 (no passphrase, comment "spokares-github-deploy"), authorize its public key for the website's SSH access via the API, and test SSH (one batched command: whoami; pwd; php -v; which wp rsync). Record the host key fingerprint (ssh-keyscan) to ${SECRETS}/known_hosts. (4) Set up the preview URL per recon (or document the hosts-file line). Verify the fresh WordPress answers through the preview route (curl) with HTTP 200 and the site title, and that wp-admin login page loads. (5) Do NOT install our theme/plugins yet (the pipeline does that). Consider mail: if Enhance created mail service for spokares.org on this server, make sure it won't swallow outbound mail to @spokares.org addresses before cutover (note what you find; don't create mailboxes). Write ${SECRETS}/verpex-site.md with all non-secret facts + where the secrets are. Return structured result (never include secret values).`,
  { label: 'provision', phase: 'Provision', schema: PROV })

if (!prov || !prov.ok) {
  log('Provisioning did not complete')
  return { stopped_at: 'provision', recon, prov }
}

phase('Pipeline')
const PIPE = {
  type: 'object',
  properties: {
    ok: { type: 'boolean' },
    workflow_files: { type: 'array', items: { type: 'string' } },
    secrets_set: { type: 'array', items: { type: 'string' }, description: 'secret NAMES only' },
    commit: { type: 'string' },
    first_deploy_run_url: { type: 'string' },
    first_deploy_result: { type: 'string' },
    site_state: { type: 'string', description: 'what is live on the preview URL after the first deploy' },
    runbook: { type: 'string', description: 'path to the deploy runbook' },
    notes: { type: 'string' },
  },
  required: ['ok', 'workflow_files', 'secrets_set', 'commit', 'first_deploy_run_url', 'first_deploy_result', 'site_state', 'runbook', 'notes'],
}
const APPROVAL = `OWNER APPROVAL (given directly by the owner in the conversation on 2026-09-27, answering an explicit question that listed these actions): "Yes, build and deploy" — push .github/workflows (ci.yml + deploy.yml) to main on the public repo; store the deploy SSH private key and host details as secrets in a GitHub 'production' environment; deploy the theme/plugins over SSH; install Two-Factor + UpdraftPlus; create the six pages (documents as drafts). The owner also chose to KEEP the Enhance preview domain up, so deploy promptly: the stock site currently exposes the admin username until spokares-hardening is live.`
const pipe = await agent(`${CONTEXT}\n\n${APPROVAL}\n\nYou are the PIPELINE ENGINEER. Provisioned site (non-secret facts):\n${JSON.stringify(prov, null, 1)}\nSecrets are in ${SECRETS}/ (deploy_ed25519, known_hosts, wordpress-admin.txt, verpex-site.md).\n\nBuild the CI/CD pipeline on GitHub Actions for seantmalone/spokane_ares, reusing ${W}/ops/ci.sh and ${W}/ops/deploy.sh (adapt them minimally for non-interactive CI if needed; keep local use working; keep PLAN.md §5's model: deploy a clean export of a git ref, code only — theme, spokares-core, spokares-hardening — never the dev/ folder, never user uploads/DB):\n- .github/workflows/ci.yml: on push and pull_request — run ops/ci.sh (use --offline if Playground-dependent steps are impractical in Actions, but try to run the full gate; cache npm) and fail on errors. Least-privilege permissions (contents: read).\n- .github/workflows/deploy.yml: on push of tags v* AND workflow_dispatch (input: ref, default main) — job uses a GitHub Environment "production" (create it via gh api), runs the CI gate first, then deploys over SSH with secrets (set via gh secret set --env production: DEPLOY_SSH_KEY from ${SECRETS}/deploy_ed25519, DEPLOY_KNOWN_HOSTS, DEPLOY_HOST, DEPLOY_USER, DEPLOY_PORT, DEPLOY_PATH — pipe files into gh, never echo values), uses StrictHostKeyChecking with the pinned known_hosts, rsyncs with --delete only inside our three code directories, then runs post-deploy steps over one batched SSH session (wp-cli if available: activate theme spokares + plugin spokares-core; install+activate Two-Factor and UpdraftPlus from wordpress.org if missing; flush caches/rewrite; keep "discourage search engines" on until launch), then runs ops/check-live.sh against the PREVIEW URL (${prov.preview_url}) if feasible. No secrets in logs (never set -x around secrets). concurrency group so deploys don't overlap. Pin third-party actions to commit SHAs.\n- First-run site setup: after the first deploy, create the site structure on the server (pages Home/How it works/About/members pages from the patterns, front page, navigation, the importer in PRODUCTION mode per PLAN §5 so documents stay drafts, the ARES Editor role exists) — reuse ${W}/dev/setup and dev/seed logic if appropriate but NEVER deploy dev-only code (dev-login, test endpoints) to the server; if you need a one-off setup script, run it via wp eval-file over SSH from a temp path and delete it afterwards.\nCommit ONLY the files you created/changed (explicit git add paths — a QA workflow is editing other files concurrently), with a clear message, and push to main. Then trigger the first deploy with workflow_dispatch (gh workflow run deploy.yml -f ref=main), watch it (gh run watch), fix failures (iterating commits touching only your files), until the preview URL serves the spokares theme with the six pages. Write ${W}/ops/DEPLOY-RUNBOOK.md: how to release (tag), how to deploy manually, rollback, rotating the deploy key, where secrets live, the go-live checklist (DNS cutover steps at GoDaddy, SSL issuance after cutover, turn indexing on, mail considerations) — and commit it. Return structured result (secret NAMES only).`,
  { label: 'pipeline', phase: 'Pipeline', schema: PIPE })

phase('Verify')
const verify = await agent(`${CONTEXT}\n\n${APPROVAL}\n\nYou are the INDEPENDENT VERIFIER (read-only; do not change the server or repo — only report). Provisioning: ${JSON.stringify(prov)}. Pipeline: ${JSON.stringify(pipe)}.\nCheck and report PASS/FAIL with evidence for each: (1) the preview URL serves the new WordPress site with the spokares theme: all six pages 200, correct titles, no PHP errors/notices in page output, assets load; wp-admin login page loads; (2) search indexing is discouraged (reading option / robots meta / X-Robots-Tag); (3) security headers and hardening from spokares-hardening are active (compare with ${W}/ops/check-live.sh expectations); dev-only code (dev-login, test runners, ?dev_login) is NOT present on the server (try ?dev_login=1 — must do nothing); (4) GitHub: ci.yml and deploy.yml exist, latest runs green, deploy triggers are only tags v* + workflow_dispatch, secrets exist in the production environment (names only), actions pinned to SHAs, least-privilege permissions, no secret values in any committed file (grep the repo history of the new commits) or in the Actions logs (inspect the latest deploy log for leaked values/paths); (5) the CURRENT live spokares.org is unaffected: its nameservers and A/MX records are unchanged (dig NS/A/MX spokares.org — compare with ${R}/research/README.md facts: GoDaddy registrar, InMotion host) and https://www.spokares.org/ still responds as before; (6) only one new website was created and nothing else in the org changed (GET the websites list and compare with recon: ${recon.org_summary}); (7) secrets files in ${SECRETS} are chmod 600/700 and ${SECRETS} is outside the repo and not committed. Write ${SECRETS}/verification.md and return a <=25 line summary: what's live where, how to reach the preview and wp-admin (without secret values), pipeline status, any FAILs and their fixes, and the go-live steps remaining.`,
  { label: 'verify', phase: 'Verify' })

return { recon: { server: recon.server_host, subscription: recon.subscription_to_use, preview: recon.preview_url_method }, provision: prov, pipeline: pipe, verify }
