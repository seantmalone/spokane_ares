# spokares.org ops scripts

These are the release, deploy, monitoring and backup scripts in PLAN.md §5.7–§5.9 and §6.11. GitHub Actions runs `ci.sh` on every push and deploys with `deploy.sh` on a `v*` tag or by hand: see [DEPLOY-RUNBOOK.md](DEPLOY-RUNBOOK.md). All of them are Bash. `deploy.sh`, `check-live.sh` and `backup-pull.sh` need `ops.env`: copy `ops.env.example` and fill it in. It is git-ignored and holds only SSH host aliases, URLs and the backup folder, never passwords.

| Script | Where it runs | What it does |
|---|---|---|
| `ci.sh [--tag=<tag>] [--offline] [--no-zip]` | Your machine or CI | Version agreement, the forbidden-code grep, ABSPATH guards, fresh generated files, JSON/JS parse, PHP lint on 8.3 and 8.4, PHPCS WordPress-Extra (`phpcs.xml`), Plugin Check, then `wordpress/dist/*.zip` + `SHA256SUMS-<v>` |
| `deploy.sh <tag> <staging\|production> [--dry-run] [--flush-rewrites] [--yes]` | Your machine or GitHub Actions (`deploy.yml`) | rsync of a clean export of the tag (or, for a manual CI deploy, a branch or commit): theme, plugin, the must-use plugin and `~/bin/weekly-check.sh`. Then the tag's `post-deploy.sh` on the host in one SSH session |
| `post-deploy.sh <label> [--flush-rewrites]` | The host, piped over SSH by `deploy.sh` | Permissions (755/644); Two-Factor and UpdraftPlus installed and active with auto-updates on; the theme and `spokares-core` active; "Discourage search engines" kept on while `.htaccess` has the PRE-LAUNCH block; `wp rewrite flush` (with `--flush-rewrites`, or when it activated `spokares-core`), `wp cache flush`, and `~/.spokares-deployed-tag` |
| `first-run.sh <tag> <staging\|production> [--preview \| --launch-cleanup] [--yes]` | Your machine, once after the first deploy | `--preview`: read-only report. Default: uploads only `dev/seed/import.php`, its two JSON files and `dev/setup/pages.php` to a private temp folder outside `public_html`, runs the importer in production mode (documents stay Drafts) and the page setup (six pages, hero, front page), then deletes the folder. `--launch-cleanup`: deletes the sample content, Akismet, Hello Dolly and the unused default themes, creates UpdraftPlus's folder, adds the two `.htaccess` rewrite rules, writes `~/.spokares-weekly.env` |
| `weekly-check.sh [--find-only] [--no-ping]` | The host, as a weekly Enhance cron job | Checksums, Two-Factor active, the administrator list, every editor has 2FA, no dev constants, no PHP in uploads, nothing unexpected in plugins/themes/mu-plugins/drop-ins. A success ping goes to the dead-man URL; a failure pings `/fail` and sends an e-mail |
| `check-live.sh <tag> [--staging] [--no-ssh] [--resolve=<ip>] [--url=<url>]` | Your machine (monthly and after deploys) and GitHub Actions | An `rsync -rcn` comparison of the host's code with the tag, the tag's own file checks over SSH, and the §5.11 curl list (status codes, security headers, no listings, the 301/410 map). Before the DNS cutover, `--resolve=<server IP>` pins the curl checks to the new server (and accepts its self-signed certificate) |
| `backup-pull.sh [--staging]` | Your machine, monthly | `wp db export \| gzip` and an rsync of `uploads/` into `SPOKARES_BACKUP_DIR` (an encrypted volume); the host holds no credential for this copy |

## A release, step by step

1. Bump `Version` in all eight places `ci.sh` lists: the theme's `style.css`, `SPOKARES_THEME_VERSION` and `readme.txt`; the plugin's header, constant and `readme.txt`; the must-use plugin's header and constant.
2. Commit, then tag: `git tag -a v0.2.0 -m "…"`.
3. `wordpress/ops/ci.sh --tag=v0.2.0`. Fix anything it reports and re-tag.
4. Clone production to an Enhance staging site. Then run `deploy.sh v0.2.0 staging`, `check-live.sh v0.2.0 --staging` and the §6.13 click tests. Delete the staging site afterwards.
5. `deploy.sh v0.2.0 production`, then `check-live.sh v0.2.0`.
6. Attach `wordpress/dist/*-0.2.0.zip` and `SHA256SUMS-0.2.0` to the tag.

Rollback: deploy the previous tag. **Never** use Enhance "push live". It copies the database and would overwrite the editors' rota, events and documents.

## Notes

- **No PHP needed on your machine.**
  - `ci.sh` lints with WordPress Playground's `php` command.
  - With no local `phpcs`, it runs the PHPCS copy bundled with Plugin Check inside Playground. That copy has WordPress-Extra but not PHPCompatibilityWP, so the lint on 8.3 and 8.4 stands in for the compatibility check.
  - A local `phpcs` is used when present, with PHPCompatibilityWP `8.3-` if that standard is installed.
  - Reports are saved in `$TMPDIR/spokares-cache/`.
- **Plugin Check** always reports `plugin_updater_detected`. That comes from `Update URI: false`, which is deliberate (§5.7), so `ci.sh` ignores it.
- **macOS rsync:** macOS ships openrsync, which has no `--chmod`. So `deploy.sh` doesn't preserve permissions, and `post-deploy.sh` sets 755/644 on the host after syncing.
- **`weekly-check.sh` settings** go in `~/.spokares-weekly.env` on the host. Its header lists them:
  - the expected administrators
  - the expected plugins and themes
  - Enhance's own mu-plugin files and drop-ins, recorded at setup

  The dead-man ping URL goes in the cron command. `check-live.sh` runs the tag's copy of the file checks over SSH, not the host's copy.
- **Ask Verpex before the first deploy** (PLAN §5.10), besides the list there:
  - Is `opcache.validate_timestamps` on for the site's PHP (LSAPI or FPM)? If it is off, the PHP files `deploy.sh` rsyncs don't load until PHP restarts, and `wp cache flush` does not reset the web server's OPcache. Ask how to restart PHP from Enhance, and do it after every deploy.
  - Can `/readme.html` and `/license.txt` be denied at the web server? They show the WordPress version, and every core update puts them back, so deleting them doesn't last. `check-live.sh` prints a NOTE while `/readme.html` answers 200.
  - `expose_php=Off`, so PHP stops sending `X-Powered-By: PHP/8.3.x` on static and error responses too. The must-use plugin already removes it from WordPress's own responses; `check-live.sh` checks `/`.
- **Rewrite rules** are also rebuilt by the plugin itself the first time a new version runs (`spokares_maybe_upgrade()`), so a deploy without `--flush-rewrites` can't leave `/docs/<slug>/` without its rule.
- These scripts were tested against a fake host: a throwaway git clone and tag, a local site folder, and `ssh`/`wp` stubs. They haven't yet run against a real Enhance server, so the first run against staging is the real test.
