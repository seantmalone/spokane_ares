# spokares.org dev environment

A throwaway local copy of the site in WordPress Playground (PLAN.md §6.10). It needs Node 22+ and, for screenshots, Google Chrome. You don't need PHP, MySQL or Docker. The database lives in memory and is rebuilt from `blueprint.json` on every start.

## Start, stop, reset

```bash
wordpress/dev/start.sh 9400            # WordPress 7.1.2, PHP 8.4, http://127.0.0.1:9400/
wordpress/dev/start.sh 9400 --wp=7.2-RC1 --php=8.3   # try a release candidate or another PHP
wordpress/dev/stop.sh 9400
```

A cold start takes about 15–40 seconds. It needs network access: every start installs Two-Factor from WordPress.org, and the first run also downloads WordPress 7.1.2. **To reset, restart:** stop, then start again. Everything is rebuilt.

- `start.sh` waits until the blueprint has finished and the site answers, then prints the setup log.
- Logs:
  - `/tmp/pg-<PORT>.log`: Playground's own output.
  - `/tmp/pg-<PORT>-logs/setup.log`: one line per setup step.
  - `/tmp/pg-<PORT>-logs/debug.log`: PHP notices. `WP_DEBUG` is on, and nothing is displayed on the page.
- Use one port per person or agent. Always stop your server when you are done.
- Options for tests: `--blueprint=<file>` runs another blueprint (copy `blueprint.json` and add steps). `--mount=<host>:<vfs>` adds a folder (repeatable).
- Playground runs with `--workers=1`. With several workers, a sign-in or a deleted file isn't seen by the other workers straight away. `SPOKARES_PG_WORKERS=n` overrides it.

## Signing in

| Link | Who |
|---|---|
| `http://127.0.0.1:<PORT>/wp-admin/?dev_login=1` | `admin` (Administrator) |
| `http://127.0.0.1:<PORT>/wp-admin/?dev_login=editor` | `editor` ("Test Editor", role ARES Editor) |
| `…/wp-admin/?dev_login=editor&dev_edit=spk_event:set-2026` | Signs in, then opens that item's edit screen. Also works with `spk_document:ics-213` and `page:home` |

`?dev_login=` works on any URL, and it drops itself from the address after signing in. Both accounts also work at `/wp-login.php` with the password `password`.

There is also one QA account per user level, created at every start from `setup/qa-users.json`: `?dev_login=qa-subscriber`, `qa-contributor`, `qa-author`, `qa-core-editor` (WordPress's own Editor role) and `qa-ares-net` (an ARES Editor with the Net Settings and Meeting Schedule grant). The password is `password` for all of them. The crawler and the tests use them: see [`tests/README.md`](tests/README.md).

The switch lives in `mu-plugins/spokares-dev-login.php` and acts only when all of these hold:

- the environment type is `local`
- `SPOKARES_DEV` is true
- the request comes from 127.0.0.1 or ::1

It is never shipped.

Dev-only extras, all switched on by `SPOKARES_DEV`:

- `SPOKARES_TODAY` pins "today" to 2026-09-26, the date of the round-3 data.
- `?today=YYYY-MM-DD` on any page shows another day. For example, `/members/?today=2026-10-02` puts the Oct 6 open slot first.
- Two-factor enforcement is skipped.

## What the blueprint builds

1. Constants:
   - `WP_ENVIRONMENT_TYPE` local, `SPOKARES_DEV`, `SPOKARES_TODAY`
   - `WP_DEVELOPMENT_MODE` theme, so pattern and theme.json edits show on the next reload
   - `DISALLOW_FILE_EDIT`, `DISALLOW_UNFILTERED_HTML`
   - `WP_DEBUG`, with the log in `/spokares-log`
2. Site options:
   - title "Spokane County ARES-ACS"; the tagline
   - time zone Los Angeles; date "M j, Y"; time "g:i A"; week starts Sunday
   - permalinks `/%postname%/`
   - registration, comments and pings off
3. The Two-Factor plugin from WordPress.org, activated.
4. `setup/mu.php` writes three one-line loaders into `wp-content/mu-plugins/`: the dev login, the QA endpoints and `spokares-hardening`, all read from the mounted repo. Edits to the must-use plugin are live.
5. Activates the `spokares` theme and the `spokares-core` plugin. `start.sh` drops either step when its files are missing.
6. `setup/setup.php` (a new request, after `init`):
   - creates the `editor` user and the QA users (`setup/qa-users.json`)
   - runs `seed/import.php` in `dev` mode
   - runs `setup/pages.php`
   - deletes WordPress's sample content
   - flushes the rewrite rules

   A step that fails is logged as `FAILED` and skipped, and the boot carries on.

The theme, the plugin, the must-use plugin and this folder are **mounted**, not copied, so edits to PHP, CSS, patterns or JS show on the next page load. Two kinds of change need a restart: page content copied from patterns at setup, and anything the importer writes.

## The seed

- `seed/data.json` is generated from `design/round3/_shared/assets/data.js`:
  - `node wordpress/dev/seed/convert.mjs`: regenerate it after editing data.js, and commit the result.
  - `--check`: fail if data.json is stale (for CI).
- `seed/extra.json` holds the facts the round-3 pages show as page text only. Each entry names its source file:
  - the two undated public-service rows
  - the SET short line and its Extra form (the WA State Field Situation Report, which B linked from task 1), and the WSDOT button words
  - the Home meeting extras
  - the hub tile slots, and the same PLAN §2.3 swap for the library's Most used flags (`mostUsed`: ICS 214 in, ICS 309 out)
  - page excerpts (B's meta descriptions) and About's page owner
- `seed/import.php` writes both through the §6.3 schema only. Every run:
  - creates 7 library sections and 37 documents
  - creates 20 events: 10 dated, 2 undated public-service and 8 past
  - writes the `spk_*` options: the rota (net control plus Winlink), nets, radio, meetings, site and the four tiles

  It is idempotent: it does nothing once `spk_seeded` is set, and it looks items up by slug before inserting.
  - **`dev`**: everything is published and privacy-checked (and marked reviewed today, as publishing with the Privacy check does), so the pages match Option B. It is refused outside a `local` site.
  - **`production`**: every document is a Draft with no Privacy tick, and events marked "Needs checking" are Drafts. Meetings marked "Needs checking" are inactive, the alternate repeater is hidden and the workshop's Home extra is empty. Rota rows before today are skipped.
- `setup/pages.php` creates the six pages. Home, How it works and About are copied from the theme's whole-page patterns. For members, Documents & forms and Exercises & events are left empty, because their templates hold everything. It also:
  - copies the sunset photo into the Media Library once (`_spk_seed` = `hero`) and points Home's hero at it (`"id":N`, `wp-image-N`, so the front end prints `srcset`)
  - sets the static front page, the excerpts and About's owner

  It is idempotent: an existing page is left as it is.

### Production seeding (once, at launch)

`dev/` is never deployed. Copy `seed/` and `setup/` to the host for the run, then delete them:

```bash
rsync -a wordpress/dev/seed wordpress/dev/setup LIVE:spokares-seed/
ssh LIVE 'cd public_html && wp eval-file ~/spokares-seed/seed/import.php production \
  && wp eval-file ~/spokares-seed/setup/pages.php --user=<an administrator> \
  && rm -rf ~/spokares-seed'
```

`import.php` refuses to run outside a local site without an explicit mode, and it refuses `dev` there. After the import, an administrator reviews and publishes each document, ticking its Privacy check, and clears the "Needs checking" items (PLAN §5.11).

## Screenshots

```bash
wordpress/dev/shots.sh <PORT> [outdir] [--only=front|admin]   # default outdir: wordpress/dev/shots/
```

`shots.mjs` drives the installed Chrome over the DevTools protocol with Node's built-in WebSocket, so there is nothing to install. Set `CHROME=` to use another Chrome binary. It writes:

- `<page>-desktop.png`: a full page at 1440 wide, laid out in a 1440×900 window, so sections sized to the window stay the right height
- `<page>-mobile-390.png`: a full page in a true 390×844 phone viewport

  Pages: `home`, `how`, `about`, `members`, `documents`, `exercises`.
- `members-mobile-390-editor.png`: `/members/` signed in as the editor (the admin-bar gap check)
- `admin-*.png`. As the editor:
  - `dashboard`, `net-rota`, `events`, `event-add`, `event-set`, `meetings`
  - `documents`, `document-ics-213`, `tiles`
  - `editor-home`: the Home page in the block editor, with the hero selected and the block sidebar open
  - `editor-how`, `editor-about`: How it works and About in the block editor

  As admin: `net-details`, `meeting-rules`.

Each line of output names the final URL and title, and lists any browser console errors. For the three `editor-*` shots it also reports the block count (the template's blocks plus the page's own), any invalid blocks, the save button's words, the editor's rendering mode (pages open with the template shown, `template-locked`) and whether the Welcome Guide appeared. It waits for the template and its server previews (no "Loading…" left) before the capture. Lazy images are switched to eager in the browser before a full-page capture. `shoot.sh` is an alias.

## Checks

```bash
wordpress/dev/checks.sh <PORT>
```

It exits 1 on any failure. It checks:

- The six pages: status 200, one `<h1>`, no `href="#"`, and every anchor in the placement map §2.4 (plus `open-slot` and `weekly-net`).
- The Home hero comes from the Media Library, and `/members/` shows "Net control" and the seeded rota. The library shows 37 documents, and no edit links appear when logged out.
- `/docs/`:
  - a published document gives 302 to an https address
  - a "Soon" document or an unknown slug gives 404
- The must-use plugin, logged out:
  - `/xmlrpc.php` 403
  - `/wp-json/wp/v2/users` 404; `/wp-json/wp/v2/pages` 401
  - `/?author=1` 404; `/feed/` 404; oEmbed has no `author_name`
  - the old Joomla URL 301; `option=com_x` 410; the roster URL 410
  - lost password for an unknown user gives `checkemail=confirm`
- Attachment pages: `/?attachment_id=N` and `/?p=N` for the hero photo's attachment give 404 with no redirect (a deleted document's file can't be found by its number, PLAN §5.5).
- The dev switches (`?dev_login`, `?today`).
- No PHP warnings, notices or deprecations in either log, and no setup step failed or was skipped.

The case "a Draft document's `/docs/<slug>/` gives 404" can't be checked here, because the dev seed publishes every document. It was checked on a production-mode boot, where every document is a Draft: `/docs/ics-213/` gave 404.

## Tests and the QA crawl

```bash
wordpress/dev/tests/run-all.sh <PORT>        # checks.sh + PHP integration tests + browser tests
node wordpress/dev/qa/crawl.mjs <PORT> wordpress/qa/runs/<name> --shots=wordpress/qa/shots/<name>
```

- `tests/run-php.sh` runs `tests/php/*-test.php` inside WordPress, through the dev-only endpoint in `mu-plugins/spokares-dev-qa.php`.
- `tests/run-e2e.mjs` runs `tests/e2e/*.test.mjs` in Chrome.
- `qa/crawl.mjs` visits every public page, admin screen and forbidden screen as every user level. For each one it records a screenshot and its findings: errors, PHP messages, overflow, accessibility, and access leaks.

All of it is described in [`tests/README.md`](tests/README.md).

## Files

| File | What |
|---|---|
| `start.sh`, `stop.sh` | Start or stop Playground on a port, with the mounts |
| `blueprint.json` | The site recipe (no login step, so front-end shots are logged out) |
| `setup/mu.php` | Writes the must-use loaders |
| `setup/setup.php` | Last blueprint step: editor and QA users, import, pages, sample content, rewrite flush |
| `setup/pages.php` | The six pages and the hero photo (also `wp eval-file` at launch) |
| `seed/convert.mjs`, `seed/data.json` | data.js to JSON |
| `seed/extra.json` | Page-text facts, with sources |
| `seed/import.php` | The importer (`dev` / `production`) |
| `mu-plugins/spokares-dev-login.php` | `?dev_login=` and `?dev_edit=`, local and loopback only |
| `mu-plugins/spokares-dev-qa.php` | QA endpoints (`?dev_qa=whoami\|ids\|unlock\|accounts`) and the PHP test endpoint, local and loopback only |
| `setup/qa-users.json` | The dev accounts, one per user level (created by `setup/setup.php`) |
| `lib/cdp.mjs`, `lib/site.mjs`, `lib/inspect.mjs` | The shared Chrome driver, site helpers and in-page checks |
| `qa/crawl.mjs` | The QA crawl (all roles × all pages) |
| `tests/` | The PHP and browser test runners, the tests and `run-all.sh` ([README](tests/README.md)) |
| `shots.sh`, `shots.mjs`, `shoot.sh` | Screenshots |
| `checks.sh` | The checks above |
