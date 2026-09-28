# Build notes: dev environment and ops (`wordpress/dev/`, `wordpress/ops/`)

Dev-environment and ops builder, 2026-09-26. Built and run against WordPress 7.1.2 on PHP 8.4 in Playground CLI 3.1.55, on port 9424, which is stopped. Every P0 and P1 item in PLAN §6.12 for this builder is done. The published names in §6.2 are unchanged. How to use it: `wordpress/dev/README.md` and `wordpress/ops/README.md`.

## Deviations from PLAN.md (read first)

1. **Screenshots use a Node driver instead of Chrome's `--screenshot` and `npx playwright`.** `dev/shots.mjs` drives the installed Chrome over the DevTools protocol with Node's built-in WebSocket; it uses the standard library only.
   - Full-page shots keep a real 1440×900 or true 390×844 viewport, so sections sized to the window stay the right height. Lazy images are switched to eager in the browser first.
   - It can also select the hero in the block editor and open the sidebar.
   - It reports console errors, invalid blocks and the save button's word.
   - `shots.sh <PORT> [outdir]` is the contract entry point. `shoot.sh` is an alias for the brief's name.
   - File names follow §6.10: `<page>-desktop.png`, `<page>-mobile-390.png`, `members-mobile-390-editor.png`, `admin-<screen>.png`. The pages are named `home`, `how`, `about`, `members`, `documents` and `exercises`.
2. **Logging.** The blueprint adds `WP_DEBUG` true, `WP_DEBUG_LOG` `/spokares-log/debug.log` and `WP_DEBUG_DISPLAY` false. `start.sh` adds a fifth mount, `/tmp/pg-<PORT>-logs` → `/spokares-log`, which holds `setup.log` (one line per setup step) and `debug.log`.
3. **`start.sh` behaviour.**
   - It passes `--php` and `--wp` to the CLI explicitly, read from `preferredVersions` or from `--wp=`/`--php=`. The CLI's own defaults are 8.3 and "latest".
   - It runs with `--workers=1`, per the plugin notes. `SPOKARES_PG_WORKERS` overrides it.
   - It waits for the CLI's `Ready!` line as well as HTTP 200/302, because the CLI answers HTTP while the blueprint is still running.
   - Extra test options: `--blueprint=<file>` and `--mount=<host>:<vfs>`.
4. **The dev login also has `?dev_edit=<post_type>:<slug>`.** It redirects to that item's edit screen, for example `spk_event:set-2026`. The same local, `SPOKARES_DEV` and loopback conditions apply.
5. **`import.php` refuses `dev` mode outside a `local` environment**, even when `dev` is given explicitly. Dev mode publishes every document with the Privacy check ticked. The contract only required refusing to run without a mode.
6. **Removing the sample content lives in `setup/pages.php`** (`spokares_dev_remove_sample_content()`), so the WP-CLI launch run also removes it (§4.2). It deletes the Sample Page and "Hello world!" only while they have never been edited, and the Privacy Policy page only while it is still the untouched draft. `setup.php` calls the same function.
7. **Importer mappings the contract didn't spell out:**
   - data.js `tags` → `spk_keywords`, comma-separated.
   - `extra.json` also carries the meetings' `show_home`, `time_text` ("evenings") and `home_extra`, and the `keepPast` ids. Each has its source file.
   - Slugs for the two undated public-service rows (B gave them none): `lilac-torchlight-parade` and `fun-runs-bike-races`.
   - Past exercises get the slug `past-<date>-<title>`.
   - `spk_main_label` comes from `extra.json` for WSDOT. Otherwise it follows the §6.3 default: "Exercise details on groups.io" for groups.io links, else "Details".
   - The excerpts are B's `<meta name="description">` texts. B's HTML wins over the copy files, and the members hub has no copy-file meta description.
8. **UpdraftPlus is not installed in dev.** §6.10's blueprint lists only Two-Factor, and UpdraftPlus is a backup tool configured on the host.
9. **The build brief's file names differ from the contract; I used the contract's:**

   | Brief | Contract (built) |
   |---|---|
   | `demo-data.json` | `seed/data.json` (+ `seed/extra.json`) |
   | `import-demo.php` | `seed/import.php` |
   | `mu-plugins/dev-login.php` | `mu-plugins/spokares-dev-login.php` |
   | `shoot.sh` | `shots.sh`, plus the `shoot.sh` alias |
10. **The check "a Draft document's `/docs/<slug>/` gives 404" is not in `checks.sh`**, because the dev seed publishes every document. It was verified on a production-mode boot, where every document is a Draft: `/docs/ics-213/` and `/docs/net-scripts/` gave 404.
11. **Ops changes forced by the tooling:**
    - `deploy.sh` uses `rsync -rltz` and then one remote `chmod` pass (755/644), instead of `--chmod`. macOS ships openrsync, which has no `--chmod`.
    - `ci.sh` needs no local PHP:
      - it lints on 8.3 and 8.4 with Playground's `php` command
      - it runs PHPCS WordPress-Extra (ruleset `ops/phpcs.xml`) through the PHPCS copy bundled with Plugin Check, inside Playground
      - it runs Plugin Check through `wp-cli.phar` in Playground

      Plugin Check's copy has no PHPCompatibilityWP. A local `phpcs` with that standard is used when present; otherwise the lint on 8.3 and 8.4 stands in for it.
    - Release zips go to `wordpress/dist/`, which is git-ignored.
12. **Auto-updates stay on in dev**, as in production. WordPress writes "Automatic updates starting…" into `debug.log`, so `checks.sh` counts only real PHP messages (Warning, Notice, Deprecated, Fatal, Parse, database errors).

## Files

**`wordpress/dev/`**

| File | What |
|---|---|
| `start.sh <PORT> [--wp=] [--php=] [--blueprint=] [--mount=]` | Filters the blueprint (drops the theme/plugin steps when their files are missing), mounts the theme, plugin, `mu-plugins/`, `dev/` and logs, waits for the site to be ready, then prints the URL, the sign-in links and the setup log |
| `stop.sh <PORT>` | Kills the listener and the saved PID, and waits until both are gone |
| `blueprint.json` | §6.10 steps 1-8 (no login step), plus the debug constants |
| `setup/mu.php` | Writes the two must-use loaders (dev login; `spokares-hardening` from `/spokares-mu`) |
| `setup/setup.php` | The last step, in its own request: the `editor` user (`ares_editor`, "Test Editor", `editor@example.invalid`), the import (dev), the pages, sample-content removal and a rewrite flush. Each step is try/caught and logged |
| `setup/pages.php` | The six pages from the pattern registry, inserted with `wp_slash()`. The hero photo is copied into the Media Library once (`_spk_seed` = `hero`) and swapped in with the url, `"id":N` and `wp-image-N`. Also the static front page, excerpts, About's `_spk_owner`, and sample-content removal. It is idempotent, and it runs as `wp eval-file pages.php --user=<admin>` at launch |
| `seed/convert.mjs` (`--check`) | data.js → `seed/data.json` (Node vm sandbox, drops `fn`) |
| `seed/data.json` | Generated |
| `seed/extra.json` | Page-text facts, each with its source file |
| `seed/import.php` | `spokares_dev_import( 'dev'\|'production' )`, plus the WP-CLI entry point. It writes only through the §6.3 schema and is idempotent (`spk_seeded`, plus a lookup by slug) |
| `mu-plugins/spokares-dev-login.php` | `?dev_login=1\|editor` and `?dev_edit=`; local + `SPOKARES_DEV` + loopback only |
| `shots.mjs`, `shots.sh`, `shoot.sh` | Screenshots |
| `checks.sh <PORT>` | The §6.10 curl checks: 48 of them |
| `README.md`, `.gitignore` (`shots/`) | |

**`wordpress/ops/`**

`ci.sh`, `deploy.sh`, `weekly-check.sh`, `check-live.sh`, `backup-pull.sh`, `lib.sh` (shared helpers), `phpcs.xml`, `ops.env.example`, `README.md` and `.gitignore` (`ops.env`).

## For the other builders and for integration

- **Start the site with `wordpress/dev/start.sh <PORT>`.**
  - Sign in with `?dev_login=1` (admin) or `?dev_login=editor` on any URL.
  - `?dev_edit=spk_document:ics-213` jumps to an edit screen.
  - The seed matches round 3 for 2026-09-26. `?today=` works as the plugin describes.
  - Theme, plugin and must-use edits are live. Page content and the seed need a restart.
- **The editor sees a false "never publish" warning (plugin; the dashboard shows it for About).** The Site tasks card says "About ARES & ACS may contain something we never publish. Check it." The cause:
  - `spokares_check_text()` runs `wp_strip_all_tags()`, which joins adjacent table cells: the contacts table becomes "…partnersec@spokares.orgThis website…".
  - So the `@spokares\.org\b` exemption doesn't match (there is no word boundary before "This"), and the e-mail pattern fires.

  Fix: replace tags with a space before stripping, or drop the `\b` in the exemption. The editor guard's JS copy of the patterns probably needs the same fix.
- **PHPCS WordPress-Extra, full codebase (from `ci.sh`): 40 errors and 288 warnings.** Every error is in `spokares-core`. The theme, the must-use plugin and `dev/` have none.
  - Most warnings are array and `=` alignment (phpcbf can fix them), plus the patterns' `include` (PEAR "use require").
  - `WordPress.Security.NonceVerification.Missing` (28):
    - admin-meetings.php 124, 360
    - admin-net.php 316-318
    - admin-rota.php 244-245, 413
    - admin-site.php 78
    - admin-tiles.php 122

    These are probably helpers that read `$_POST` after the handler has checked the nonce. Pass the values in, or add a `phpcs:ignore` with a reason.
  - `I18n.MissingTranslatorsComment`: admin-common.php 433. Plugin Check also reports this as an ERROR.
  - Short ternaries: admin-documents.php 110, admin-events.php 325, format.php 28.
  - `count()` in a loop condition: admin-events.php 386.
  - Yoda condition: admin-meetings.php 20.
  - Bracket the increment: admin-meetings.php 423.
  - Array spacing: data.php 60, admin-events.php 620/630/705, admin-meetings.php 331.

  The report is at `$TMPDIR/spokares-cache/phpcs-report.txt` after a `ci.sh` run.
- **Plugin Check on `spokares-core`: 2 errors and 29 warnings.**
  - `plugin_updater_detected` is expected (`Update URI: false`), and `ci.sh` ignores it.
  - The other is the translators comment above.
  - The warnings are the nonce ones.
- **Content points for the architect:**
  1. §6.10 has the importer set `spk_keep_past` only on `wsdot-2026`. So SET, ShakeOut and COMMEX won't appear in "Past exercises" after they end, although the event form ticks "Keep in Past exercises" by default (§3.4). The SET edit screen shows the box unticked.
  2. The WSDOT card's "Extra form" prints the document title from data.js, "WSDOT Form 580-020, Road and Bridge Assessment". B's card said "WSDOT 580-020, …".
  3. The dashboard shows "37 not reviewed in 12 months", because data.js has no review dates.
  4. In the block editor, the page title "Home" appears above the hero (the theme's open item; visible in `admin-editor-home.png`).
- **Ops on the real host:**
  - `weekly-check.sh` needs `~/.spokares-weekly.env`: the expected admins, plugins and themes, and Enhance's own mu-plugins and drop-ins recorded at setup.
  - The dead-man ping URL goes in the cron command.
  - None of the ops scripts has run against a real Enhance server yet.

## Verified

All runs used WordPress 7.1.2 and PHP 8.4.25 in Playground CLI 3.1.55, on port 9424.

1. **Cold `start.sh`:** ready in 16 s, every setup step logged, no `FAILED` or `SKIP`. The debug log has no PHP messages; the only lines are the auto-update notes.
2. **`checks.sh`: 48 passed, 0 failed.**
   - The six pages are 200, each with one `<h1>`, no `href="#"`, and every §2.4 anchor: 6 on Home, 16 on How it works, 24 on About, 6 on the hub, 17 on Documents and 9 on Exercises.
   - The Home hero is Media Library image 61, with `wp-image-61` and `srcset`.
   - `/members/` shows "Net control" and the NZ2S row. The library shows 37 documents. No edit links appear when logged out.
   - `/docs/ics-213/` 302s to FEMA over https. A "Soon" document and an unknown slug give 404.
   - Must-use plugin: XML-RPC 403, users 404, pages 401, `?author=1` 404, feed 404, oEmbed without the author, Joomla 301, `com_x` 410, roster 410, lost password → `checkemail=confirm`.
   - `?dev_login=editor` works. `?today=2026-10-02` puts the open slot first.
3. **Stored pages equal the patterns** (read over REST, `context=edit`). The only differences are kses's `/>` → ` />` and the intended hero swap. `card--dawn` and the other `--` class names survive.
4. **The block editor, as the editor, on Home:** 47 blocks, all valid. The hero Cover is selected and the Block sidebar is open. The save button reads "Save". No Welcome Guide.
5. **Screenshots:** 25 in `wordpress/shots/dev/`, with no console errors. I looked at them next to B's `shots/`.
   - Home at 1440 is 2,902 px tall, the same as B. Documents is 3,760 px, the same as B's full-length shot. About at 390 is 11,668 px against B's 11,705.
   - Hub, Exercises and Documents show the §6.12 acceptance data.
   - The admin screens show the §3.4 editor menu, the seeded rota (13 Tuesdays, Winlink rows), events (Upcoming 12, Past 8) and tiles.
   - The Net details and Meeting rules screens were taken as admin.
6. **Idempotency (a second `setup.php` run):** the same counts (20 events, 37 documents, 6 pages, 1 attachment) and an unchanged `spk_rota` hash. `spokares_dev_login_allowed()` is false for 203.0.113.9 and true for 127.0.0.1.
7. **Production mode** (environment `staging`, no `SPOKARES_DEV`):
   - `wp eval-file import.php` with no mode is refused, and `… dev` is refused.
   - Real WP-CLI (Playground's `wp-cli` step) then ran `wp eval-file import.php production` and `wp eval-file pages.php --user=admin`. The result:
     - 37 documents are Draft, none with the Privacy tick, and slugs are kept (`ics-213` is a draft).
     - 9 "needs checking" events are Draft; 11 events are published.
     - The Winlink workshop and Third Thursday meetings are inactive, and the workshop's Home extra is empty.
     - The alternate repeater is hidden. Tiles, `spk_seeded` and `spk_db_version` are set.
     - The six pages exist with the hero swapped, and the Sample Page and Privacy Policy draft are removed.
   - Every page is 200, draft `/docs/` links give 404, and there are no PHP notices.
8. **Ops scripts:**
   - `ci.sh` on the working tree: versions, forbidden-code grep, ABSPATH guards, data.json and redirect-map freshness, JSON and JS all pass. The PHP 8.3 and 8.4 lint of 80 files is clean. PHPCS and Plugin Check fail as listed above. The zips build.
   - `ci.sh --tag=v0.1.0 --offline` on a throwaway clone and tag in the scratchpad passes.
   - `deploy.sh` (dry run and real, including a production run with `--yes`) and `check-live.sh` ran against a fake host (local folder, `ssh`/`wp` stubs):
     - a wrong-version tag is refused
     - `--delete` removes stale theme files while Enhance's own mu-plugin file stays
     - permissions end up 755/644, `weekly-check.sh` lands in `~/bin`, and the tag is recorded
     - `check-live` passes after a deploy, and catches a modified plugin file, an added theme file and a PHP-named upload
   - `weekly-check.sh` catches PHP in uploads, an unexpected plugin, a drop-in and the wrong admin list. `backup-pull.sh` refuses a suspiciously small dump, and stores a gzip-checked 600 dump plus uploads.
   - `check-live.sh --no-ssh` against the dev site: 19 pass. The 2 expected failures need the real server: the `.htaccess` rule that refuses PHP under uploads, and UpdraftPlus's folder.
9. **The server is stopped:** no listener and no Playground processes on 9424. No headless Chrome is left over.

Not verified: the ops scripts against a real Enhance host; PHPCompatibilityWP, which isn't available without a local PHP toolchain.

## QA round: tooling, seed and ops fixes (2026-09-27)

- **Clicks land on the element (QA-005).** The theme sets `html { scroll-behavior: smooth }` for visitors without a reduced-motion preference, and headless Chrome has none. So `scrollIntoView()` only started an animation, and `Page.click()` (`t.click`, `t.clickAndWait`, the crawler) pressed the mouse at the element's position before the scroll. It hit `<html>` or another control. `Page.click()` now scrolls with `behavior: 'instant'` and measures the element only once its box has held still for two frames. `Page.type()` scrolls instantly too. The driver doesn't emulate reduced motion, so screenshots still show the page as visitors get it. Test: `dev/tests/e2e/qa-005-smooth-scroll-click.test.mjs`.
- **Chrome that is slow, won't start or dies (QA-068).** `dev/lib/cdp.mjs`:
  - `Browser.launch()` gives each attempt `SPOKARES_CHROME_CONNECT_MS` (default 60000; it was a fixed 20 s for the port file and 10 s for the WebSocket) and tries three times.
  - When Chrome's process exits or its WebSocket closes, every pending command and page wait fails at once with a `BrowserClosedError`, and `Page.goto()` throws one instead of answering "HTTP 0".
  - The crawler restarts Chrome and signs in again before any visit, HTTP check or REST probe when Chrome has died. It throws a visit away, and retries it, when Chrome died during it. If Chrome can't be restarted, the role stops.
  - The crawl exits 1 when a role couldn't start or stopped (the report is still written); findings still never change the exit code.

  Test: `dev/tests/e2e/qa-068-crawler-resilience.test.mjs` runs the real crawler behind a stand-in Chrome that is slow, exits at once, or is killed mid-crawl.
- **Crawler coverage (QA-109).**
  - `domFacts()` reports `innerOverflow`: content that sticks out of its own box, for example a long link running out of a grid column over the next one. It is reported even when the page doesn't scroll sideways (`.wp-site-blocks` clips it). Clipped and visually hidden boxes (the phone tables' `<thead>`), absolutely placed children and deliberate negative margins (`.prose .bleed`) are left out. The crawler adds `overflow-inner` findings (warning on public and plugin screens, info on core's). A pasted 90-character link in the "Later this season" table is found (321 px over its 588 px column at 1440); the seeded site gives none.
  - The event-anchor check expects anchors for **published** events only: `?dev_qa=ids` now returns `status` (events and documents by slug) and `media.hero`.
  - New known screens, each held to the site's policy (`manage_options`): Categories, Tags, Pattern Categories, Patterns (`edit.php?post_type=wp_block`), `media-upload.php`, and Edit Media for the Home hero (uploaded by an administrator).
  - A REST write probe, `POST /wp/v2/blocks`. Anything it manages to create is deleted at once (force), and a copy left behind is reported.
  - Policy checks now cover Bulk actions: Move to Trash on Pages and Documents, and bulk Edit on all three lists.
  - For non-admins, the crawler opens the block editor page card's ⋮ menu and reports Order or Trash (`editor-card`).
  - The first run after these changes reported `media-upload.php` opening for the Author, the core Editor and the ARES Editor, and the core Editor's Categories and Tags screens (QA-042).
- **Seed: Most used (QA-108).** `extra.json` `mostUsed` applies the PLAN §2.3 swap to the library's Most used flags, as the tile slots already did: ICS 214 in, ICS 309 (Soon, no file) out. The importer applies it in both modes. `data.json` stays generated.
- **Ops (QA-110).** `check-live.sh` checks all four §5.3 CSP directives, not just `frame-ancestors`, on `/`, `/wp-login.php`, `/wp-admin/`, and on `admin-ajax.php` and `admin-post.php`, which run `admin_init` signed out (the path QA-007 broke). Over SSH it records the upload cap, and anything other than 32 MB is a NOTE: the CLI's php.ini can differ from the web server's. `weekly-check.sh` fails when `DISALLOW_UNFILTERED_HTML` or `DISALLOW_FILE_EDIT` is off, and on any `wp_template` or `wp_template_part` post or any `wp_global_styles` post with styles in it. The empty marker the Site Editor creates when it opens doesn't count. Its PHP snippets were run inside the dev site with fixtures: nothing on the seeded site, templates, parts and styled global styles reported, the empty marker ignored. The runbook's go-live list gained the signed-in CSP check and the upload-cap record.
