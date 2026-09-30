# spokares.org rebuild

This repository rebuilds [spokares.org](https://www.spokares.org/), the website of Spokane County ARES-ACS. The group is one team of amateur radio emergency volunteers. It serves as an Amateur Radio Emergency Service (ARES) unit of the American Radio Relay League (ARRL), and as the Auxiliary Communication System (ACS) of Spokane County Emergency Management (SCEM).

**Why a rebuild:** the old site runs end-of-life Joomla 3 and was hacked in 2026. Its downloads and contact form return HTTP 500, so the group's current information lives on ag7qp.com for now. The new site is a WordPress block theme, a site plugin and a must-use plugin.

> **Live preview: <https://spokares-org-us2s.staging.firelizardhost.com/>**
>
> This is the Enhance preview domain. It is pre-launch, sends `noindex`, and is temporary: the go-live checklist deletes it after the DNS cutover. Until then, spokares.org still serves the old site.

## At a glance

| | |
|---|---|
| Preview | <https://spokares-org-us2s.staging.firelizardhost.com/> (noindex, pre-launch) |
| Version | 0.2.0 (tags `v0.1.1`, `v0.2.0`) |
| Stack | WordPress 7.1+ (tested 7.1.2), PHP 8.3+ (run on 8.4), no build step |
| Hosting | Verpex Enhance, deployed over SSH from GitHub Actions |
| Design | Round-3 Option B, "Carry the Message" (folder `design/round3/option-b-lines-down/`; B grew out of the round-1 "When the Lines Go Down" concept) |
| Status | Deployed to the preview, not live on spokares.org. Go-live checklist still open. Many facts still need leadership confirmation |
| License | GPLv2 or later (per the theme and plugin `readme.txt`) |

**See it without running anything:** [`wordpress/review/index.html`](wordpress/review/index.html) shows the mockup and the WordPress build side by side for all six pages, and [`wordpress/shots/final/`](wordpress/shots/final/) has current screenshots of every page and admin screen.

Where the status lines in [`research/README.md`](research/README.md) and [`wordpress/README.md`](wordpress/README.md) disagree with this file, this file is current.

## What's in the repo

| Folder | What it holds | Start at |
|---|---|---|
| [`research/`](research/) | The old site's content, 22 years of Wayback history, the organization, hosting analysis, open decisions, the old-URL redirect map, and the Python redaction and redirect scripts in [`tools/`](research/tools/) | [`research/README.md`](research/README.md) |
| [`design/`](design/) | Three judged design rounds, ending in the chosen Option B | [`design/round3/option-b-lines-down/`](design/round3/option-b-lines-down/) |
| [`wordpress/`](wordpress/) | The build: theme, site plugin, must-use plugin, local dev site, tests, QA, ops scripts | [`wordpress/README.md`](wordpress/README.md), [`wordpress/PLAN.md`](wordpress/PLAN.md) |
| [`.github/workflows/`](.github/workflows/) | [`ci.yml`](.github/workflows/ci.yml) (release gate) and [`deploy.yml`](.github/workflows/deploy.yml) (deploy) | |

## Architecture

The site is three parts. Each has one job.

| Part | Path | Job |
|---|---|---|
| Block theme `spokares` | [`wordpress/theme/spokares/`](wordpress/theme/spokares/) | The look, with no data: `theme.json` tokens, `site.css`, templates, parts, patterns, and two design blocks (the seal lockup and the How it works message-path figure) |
| Site plugin `spokares-core` | [`wordpress/plugins/spokares-core/`](wordpress/plugins/spokares-core/) | The data and the editing: Events and Documents post types, net, meeting and tile settings, the wp-admin screens, the ARES Editor role, the layout lock, and seven dynamic blocks |
| Must-use plugin `spokares-hardening` | [`wordpress/mu-plugins/spokares-hardening.php`](wordpress/mu-plugins/spokares-hardening.php) | The security floor, which wp-admin can't switch off: user-enumeration block, security headers, upload checks, two-factor enforcement, SMTP, and the old-URL 301/410 map |

- The data survives a theme change. Security can't be turned off from wp-admin.
- Seven server-rendered blocks print the changing content: `events`, `net`, `meetings`, `docs`, `toc`, `last-reviewed`, `asof`. Lists are worked out for "today" in Pacific time, so pages are complete without JavaScript.
- Six pages: `/`, `/how-it-works/`, `/about/`, `/members/`, `/members/documents/`, `/members/exercises/`. The members pages are theme templates with no editable content.
- There is no build step: plain PHP, JSON, CSS and a few small scripts. No npm install.
- The version string must match in eight places. `wordpress/ops/ci.sh` checks this.
- [`wordpress/PLAN.md`](wordpress/PLAN.md) is the decision record and build contract. Where another doc disagrees, PLAN.md wins.

## Getting started locally

The local site runs in WordPress Playground. You don't need PHP, MySQL or Docker.

Prerequisites: Node 22+, network access on every start, and Google Chrome for screenshots and browser tests.

```sh
wordpress/dev/start.sh 9400            # WordPress 7.1.2, PHP 8.4, http://127.0.0.1:9400/
wordpress/dev/start.sh 9400 --wp=7.2-RC1 --php=8.3   # try a release candidate or another PHP
wordpress/dev/stop.sh 9400             # always stop it when you're done
```

- Sign in as the administrator at `http://127.0.0.1:9400/wp-admin/?dev_login=1`, or as the ARES Editor with `?dev_login=editor`. These links work only on the local dev site.
- The database is in memory and rebuilt from `wordpress/dev/blueprint.json` on every start. To reset, stop and start again.
- The theme and plugins are mounted, not copied. PHP, CSS and pattern edits show on the next reload.
- The seed has 37 documents and 20 events, pinned to "today" = 2026-09-26.

More detail: [`wordpress/dev/README.md`](wordpress/dev/README.md).

## Tests and QA

Tests run against a running dev site. Use your own port, and stop the server afterwards.

```sh
wordpress/dev/start.sh 9600                      # start a dev site for the tests
wordpress/dev/tests/run-all.sh 9600              # checks.sh + PHP tests + browser tests
wordpress/dev/tests/run-php.sh 9600 [filter]     # PHP integration tests only
node wordpress/dev/tests/run-e2e.mjs 9600 [filter]   # browser tests only
wordpress/dev/checks.sh 9600                     # the HTTP checks alone
wordpress/ops/ci.sh --no-zip                     # release gate: PHP lint, PHPCS, Plugin Check, forbidden-code grep
wordpress/dev/stop.sh 9600
```

- Every suite should pass. The last full run's results are in [`wordpress/ux/REPORT.md`](wordpress/ux/REPORT.md). Every QA bug has a regression test.
- After editing the design `data.js` or the redirect CSVs, run `node wordpress/dev/seed/convert.mjs` or `node wordpress/tools/redirects/gen-redirect-map.mjs`. `ci.sh` fails when either output is stale.

| Doc | What it holds |
|---|---|
| [`wordpress/dev/tests/README.md`](wordpress/dev/tests/README.md) | Dev accounts, the PHP and browser test APIs, the QA crawler |
| [`wordpress/qa/REPORT.md`](wordpress/qa/REPORT.md) | QA report (open [`qa/index.html`](wordpress/qa/index.html) for screenshots) |
| [`wordpress/qa/ISSUES.md`](wordpress/qa/ISSUES.md) | The 113 triaged issues |
| [`wordpress/ux/REPORT.md`](wordpress/ux/REPORT.md) | Volunteer-editor UX results; spec in [`ux/SPEC.md`](wordpress/ux/SPEC.md) |

## Releasing and deploying

[`wordpress/ops/DEPLOY-RUNBOOK.md`](wordpress/ops/DEPLOY-RUNBOOK.md) is the single source for releases, manual deploys, rollback, secrets, first run and the go-live checklist. [`wordpress/ops/README.md`](wordpress/ops/README.md) is the script reference. Before running the ops scripts, copy `wordpress/ops/ops.env.example` to `wordpress/ops/ops.env`.

- **CI:** every branch push and pull request runs `wordpress/ops/ci.sh` through `ci.yml`. A tag push runs it as the deploy gate.
- **Release:** bump the eight version strings (listed by `spk_versions` in [`wordpress/ops/lib.sh`](wordpress/ops/lib.sh): headers and constants in `spokares-core.php`, `spokares-hardening.php` and the theme's `style.css`/`functions.php`, plus both `readme.txt` Stable tags). Commit, push `main`, wait for CI, then tag:

  ```sh
  git tag -a vX.Y.Z -m "vX.Y.Z: <what changed>"
  git push origin vX.Y.Z
  ```

  `deploy.yml` starts by itself. It runs the CI gate for that commit, then **waits for seantmalone to approve** the `production` environment (Actions > the run > Review deployments > Approve). It then deploys over SSH with `deploy.sh`, runs `post-deploy.sh` on the host, and finishes with `check-live.sh`.
- **Manual deploy:** only `main` or a `v*` tag can deploy, and it waits for the same approval.

  ```sh
  gh workflow run deploy.yml --repo seantmalone/spokane_ares -f ref=main
  gh run watch --repo seantmalone/spokane_ares
  ```

- **Rollback:** deploy the previous tag with the same command and `-f ref=<tag>`.
- **What ships:** only a clean export of the theme, the site plugin, the must-use plugin and the weekly check script. Never `dev/`, `tools/`, uploads or the database.

> **Never use Enhance "push live"** or restore staging over production. It copies the database and would overwrite what editors have entered.

Before launch, the go-live checklist in the runbook is still open: admin two-factor, mail, off-site backups, the weekly check cron, the DNS cutover, SSL, removing the pre-launch blocks and deleting the preview domain.

## Editing content

Volunteers use the **ARES Editor** role and plain wp-admin forms. They don't need the block editor for most jobs.

| Job | Screen | Target time |
|---|---|---|
| Update the Net Control Schedule | Net Control Schedule | 3 min, monthly |
| Add or change an exercise or event | Exercises & Events | 3 min |
| Cancel or move a meeting | Meetings (opens Cancel or Move a Meeting) | 1 min |
| Add, replace or change a document | Documents | 3 min |
| Change words on a page | Page Text (Home, How it works, About) | 5 min |

Editors change words only. The server refuses layout changes. Templates, parts, CSS and the members pages change only in git. The guide is [`wordpress/EDITING-GUIDE.md`](wordpress/EDITING-GUIDE.md).

## Design history

| Round | What happened | Review |
|---|---|---|
| 1 | 12 homepage concepts, 4 judges. Top three: Field Guide, When the Lines Go Down, Open Door | [`design/REVIEW.md`](design/REVIEW.md), gallery [`concepts/index.html`](design/concepts/index.html) |
| 2 | Those three grown into six-page sites: A Figure One, B Carry the Message, C Open Channel. Scores within 0.04 | [`design/round2/REVIEW.md`](design/round2/REVIEW.md), gallery [`round2/index.html`](design/round2/index.html) |
| 3 | The owner said "way too much info". All three rebuilt from one trimmed copy set under word and height budgets (B: 17,246 words to 3,542) | [`design/round3/REVIEW.md`](design/round3/REVIEW.md), gallery [`round3/index.html`](design/round3/index.html) |

The owner chose **Option B, "Carry the Message"**. Scores and judging notes are in the round reviews.

- The visual spec is [`design/round3/option-b-lines-down/DESIGN.md`](design/round3/option-b-lines-down/DESIGN.md).
- Every fact has one home, set in [`design/round3/_shared/placement-map.md`](design/round3/_shared/placement-map.md).
- The prototypes are static HTML. Open them from disk.
- `design/workflows/*.js` are a record of how each round and later phase ran. They hard-code a macOS path and are not runnable tooling.

## Key documents

| Document | Why read it |
|---|---|
| [`wordpress/PLAN.md`](wordpress/PLAN.md) | Decision record and build contract for the WordPress site |
| [`wordpress/build-notes/`](wordpress/build-notes/) | Why the code differs from PLAN.md (theme, plugin, patterns, dev) |
| [`wordpress/ops/DEPLOY-RUNBOOK.md`](wordpress/ops/DEPLOY-RUNBOOK.md) | Release, deploy, rollback, secrets, go-live |
| [`wordpress/EDITING-GUIDE.md`](wordpress/EDITING-GUIDE.md) | One-page guide for volunteer editors |
| [`research/README.md`](research/README.md) | Research index: rules, key facts, known gaps |
| [`research/decisions.md`](research/decisions.md) | Open decisions D1-D15, who decides, and the "never publish" rule |
| [`research/organization.md`](research/organization.md) | What ARES-ACS is: mission, roles, authority chains, nets |
| [`research/history.md`](research/history.md) | Timeline 2000-2026 and lessons from past site decay |
| [`research/hosting/verpex-enhance.md`](research/hosting/verpex-enhance.md) | What the host can and can't do |
| [`research/redirects.csv`](research/redirects.csv) | Old-URL redirect map (1,188 paths plus 3 host rules) |
| [`research/data/facts.yaml`](research/data/facts.yaml) | 47 site facts with source and status. None confirmed by the group yet |

## Open decisions and deadlines

From [`research/decisions.md`](research/decisions.md), which names who decides each one (mostly the EC, with AG7QP):

- **D1 (now, most urgent):** back up the old InMotion host and decide on forensics. The hacked site still holds the only copy of 21 files.
- **D14 (now):** PII clean-up: pull the public roster, decide on Wayback removal, check the old database.
- **D2 (2027-03-10):** the spokares.org domain expires. Renew early.
- **D4 (window about 2026-11-17, expires 2027-02-15):** W7GBU club license renewal, by trustee K7DSR.
- **D3 (at cutover, after D1):** move DNS and e-mail off InMotion.

## Who to ask

- **Repo owner, GitHub and hosting access, deploy approval:** [seantmalone](https://github.com/seantmalone).
- **Facts about the group** (names, nets, meetings): the Emergency Coordinator (EC) and AG7QP confirm them, per [`research/decisions.md`](research/decisions.md). Until then, treat every fact as unconfirmed.

## Rules for working here

The numbered rules (cited elsewhere as "README rule 4" and so on) are in [`research/README.md`](research/README.md#rules-for-anyone-continuing-this-work). In short:

- Cite sources. Mark your own reasoning as **INFERENCE**. Never invent names, call signs, frequencies or times.
- Never commit personal contact details. Don't open, download or copy rosters or member lists.
- Raw captures of the compromised host stay out of git: `research/raw/`, `research/wayback/snapshots/` and roster files are git-ignored (see [`.gitignore`](.gitignore), decision D14).
- Don't re-crawl www.spokares.org. The host is compromised and answered 406, then timed out. Use `research/` and the Wayback captures instead.
- Secrets never go in the repo. Deploy secrets live in the GitHub `production` environment. `wordpress/ops/ops.env` is git-ignored and holds no secrets.
