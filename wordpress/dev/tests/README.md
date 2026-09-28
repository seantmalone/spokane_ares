# Dev tests and the QA crawl

Everything here is **dev-only**: it lives under `wordpress/dev/` (and writes to `wordpress/qa/`), it needs the dev site from `dev/start.sh`, and it is never shipped. `ops/ci.sh` lints the PHP here on 8.3 and 8.4 and runs PHPCS on it, so keep it to the WordPress coding standards.

```bash
wordpress/dev/start.sh 9600                      # a fresh site (about 20 s)
wordpress/dev/tests/run-all.sh 9600              # checks.sh + PHP tests + browser tests
wordpress/dev/tests/run-php.sh 9600 [filter]     # PHP integration tests only
node wordpress/dev/tests/run-e2e.mjs 9600 [filter]   # browser tests only
node wordpress/dev/qa/crawl.mjs 9600 wordpress/qa/runs/<name> --shots=wordpress/qa/shots/<name>
wordpress/dev/stop.sh 9600
```

Use your own port, and stop the site when you are done. Every script exits 0 on success, 1 on a failure and 2 on bad usage or when the site is not answering.

## The dev accounts (one per user level)

`dev/setup/qa-users.json` is the one list. `setup/setup.php` creates the users at every start, the dev login signs them in, and the PHP and JS helpers read their role keys from it. Every password is `password` and every e-mail is `<login>@example.invalid`.

| Role key | Login | `?dev_login=` | WordPress role |
|---|---|---|---|
| `anonymous` | (none) | (none) | signed out |
| `subscriber` | `qa-subscriber` | `qa-subscriber` | Subscriber |
| `contributor` | `qa-contributor` | `qa-contributor` | Contributor |
| `author` | `qa-author` | `qa-author` | Author |
| `core-editor` | `qa-core-editor` | `qa-core-editor` | Editor (the core role) |
| `ares-editor` | `editor` | `editor` | ARES Editor (`ares_editor`) |
| `ares-net` | `qa-ares-net` | `qa-ares-net` | ARES Editor plus the per-user "Net Settings and Meeting Schedule" grant (`spokares_edit_net_details`) |
| `admin` | `admin` | `1` | Administrator |

Add `?dev_login=<value>` to any URL to sign that user in, for example `/wp-admin/?dev_login=qa-core-editor`. `?dev_login=1` and `?dev_login=editor` work as before.

The site is meant to have only administrators and ARES Editors. The core roles exist in WordPress anyway, so they are tested too: they must get no errors, no access to anything they shouldn't touch, and no way around the layout lock.

## The QA endpoints

`dev/mu-plugins/spokares-dev-qa.php` has the endpoints. `setup/mu.php` loads it, and like the dev login it acts only on a `local` site with `SPOKARES_DEV` set, for a request from 127.0.0.1 or ::1.

| URL | Answer (JSON) |
|---|---|
| `/?dev_qa=whoami&caps=edit_pages,edit_post:62` | the signed-in user: `id`, `login`, `roles`, `caps` (the primitive caps it has), and `can` (`current_user_can()` for each cap you ask about; `cap:ID` checks a meta cap against an object) |
| `/?dev_qa=ids` | WordPress and PHP versions, plus IDs by slug: `pages` (by path), `events`, `documents`, `doc_cats`, and `users` (by login); `status` (`events` and `documents`: post status by slug); `media.hero` (the Home hero photo's attachment ID). Use these instead of hard-coded IDs |
| `/?dev_qa=unlock` | deletes every post edit lock (`_edit_lock`), so the next user who opens the block editor doesn't get the "someone else is editing" dialog |
| `/?dev_qa=accounts` | the accounts from `qa-users.json`, each with its user ID |
| `/wp-admin/admin-post.php?action=spokares_dev_tests[&filter=…][&list=1]` | runs the PHP tests (below) |

## PHP integration tests

The tests are the files `dev/tests/php/*-test.php`. They run **inside** the dev WordPress, in one request to `admin-post.php`, which is the context the plugin's save handlers run in: `is_admin()` is true, the admin includes are loaded, and it is not an Ajax request. `run-php.sh` prints PASS, FAIL, ERROR or SKIP for each test, and then any PHP messages the run wrote to the debug log.

```bash
wordpress/dev/tests/run-php.sh 9600                 # all
wordpress/dev/tests/run-php.sh 9600 rota            # "file::test name" contains "rota" (any case)
wordpress/dev/tests/run-php.sh 9600 accounts-test.php:: --list
```

A test file uses the framework's namespace, so the helpers need no prefix:

```php
<?php
/**
 * Regression tests for … (what bug, which PLAN section).
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

test(
	'an ARES Editor cannot change a page layout over REST',
	function () {
		as_role( 'ares-editor' );
		$home = page_id( 'home' );
		$res  = rest( 'POST', '/wp/v2/pages/' . $home, array( 'content' => '<!-- wp:paragraph --><p>x</p><!-- /wp:paragraph -->' ) );
		expect_wp_error( $res, 'spokares_layout_locked', 403 );
	}
);
```

Name files `<area>-test.php`, for example `rota-test.php` or `layout-lock-test.php`, and write one `test()` per behaviour. Keep closures anonymous and don't use file-scope variables, so PHPCS's prefix rule stays happy. The framework is `dev/tests/lib/framework.php`.

**Isolation.** Each test:

- starts signed out, with clean `$_GET`, `$_POST` and `$_REQUEST`
- restores the database tables afterwards (posts, postmeta, options, users, usermeta, terms, comments and their meta): rows the test added are deleted, changed rows are put back, and deleted rows are re-inserted
- deletes the files of any attachments the test added
- flushes the caches

So a test can save, create and delete freely. A PHP fatal ends the whole request. The runner still reports the tests so far and the fatal, but that test's data is **not** cleaned up, so restart the site afterwards.

**PHP notices fail a test.** Any warning, notice or deprecation raised while a test runs fails it. Register the test with `test( 'name', fn, array( 'allow_notices' => true ) )` to allow them.

**Redirects and `wp_die()`.** While the tests run, `wp_redirect()` and `wp_die()` throw instead of exiting. So a handler's "redirect, then exit" or its refusal ends the handler, not the request. `post_form()`, `get_action()` and `call_request()` catch these and return them. Anywhere else, an unexpected redirect or `wp_die()` fails the test with its message.

### API

| Helper | What it does |
|---|---|
| `test( $name, $fn, $opts = [] )` | register a test (`$opts['allow_notices']`) |
| `assert_true( $v )`, `assert_false( $v )` | exactly `true` / `false` |
| `assert_same( $expected, $actual )`, `assert_not_same( … )` | `===` / `!==` |
| `assert_equals( $expected, $actual )` | `==` |
| `assert_contains( $needle, $haystack )`, `assert_not_contains( … )` | substring of a string, or a value in an array (strict) |
| `assert_matches( $regex, $string )` | `preg_match` |
| `assert_count( $n, $array )` | `count()` |
| `expect_wp_error( $v, $code = '', $status = 0 )` | a `WP_Error`, or a `WP_REST_Response` that holds an error, optionally with that code and `data.status`; returns the `WP_Error` |
| `expect_not_wp_error( $v )` | not an error; returns `$v` |
| `expect_die( $fn, $status = 0 )` | `$fn` must call `wp_die()`; returns `['message', 'status']` |
| `expect_redirect( $fn, $contains = '' )` | `$fn` must redirect; returns the location |
| `fail( $msg )`, `skip( $why )` | end the test |
| Every assertion takes a last `$message` argument | it is put in front of the failure text |
| `as_role( $key )` | `wp_set_current_user()` with the dev account for a role key (`anonymous` signs out); returns the `WP_User` |
| `as_anonymous()` | sign out |
| `user_id( $key )`, `accounts()`, `role_keys()` | the dev accounts |
| `page_id( 'home' )`, `page_id( 'members/documents' )` | a page ID by path |
| `post_id( 'spk_event', 'set-2026' )` | a post ID by type and slug |
| `create_post( $args )` | `wp_insert_post()`; defaults to a published post (removed after the test) |
| `post_form( $action, $fields, $opts )` | submits a plugin form as `admin-post.php` would: `$_POST` = `$fields` + `action` + a **valid nonce for the current user** (`$opts['nonce']`: `true`, `false` for none, or a string; `nonce_action`; `get` for query args), then `do_action( "admin_post_$action" )`. Returns `redirect`, `status`, `die`, `output` and `returned` |
| `get_action( $action, $query, $opts )` | the same, for GET row actions (Duplicate, Pull this file), with the nonce in the query |
| `call_request( $method, $get, $post, $fn )` | runs any callable with those superglobals, and catches redirects and `wp_die()` |
| `rest( $method, $route, $params = [], $query = [] )` | `rest_do_request()` as the current user. GET/DELETE send the params as the query; other methods send them as a JSON body. Returns a `WP_REST_Response` (`->get_status()`, `->get_data()`) |

Nonces: a form's nonce action is usually its `admin-post` action (`spokares_verify_form( $action, $cap )` checks the capability, then `check_admin_referer( $action )`). `post_form()` makes the nonce for the user who is current at the time, so call `as_role()` first.

## Browser end-to-end tests

The tests are the files `dev/tests/e2e/*.test.mjs`. `run-e2e.mjs` drives the installed Chrome over the DevTools protocol with the Node standard library only (`dev/lib/cdp.mjs`, shared with `shots.mjs` and the crawler). Each Chrome launch attempt waits up to `SPOKARES_CHROME_CONNECT_MS` (default 60000) for DevTools, three attempts in all. When Chrome dies, everything waiting on it fails at once with a `BrowserClosedError`, and `goto` throws rather than answering "HTTP 0"; the runner starts a new Chrome for the next test.

- Each test gets its own browser context, so it starts with fresh cookies, signed in as its `role` (default `anonymous`).
- A test fails when it throws, when it runs past its `timeout` (default 90 s), or when PHP writes a warning, notice, deprecation or fatal to the dev logs during it (set `allowPhpNotices: true` to allow that).
- A failing test prints the page URL and the page's console errors, and saves a screenshot to `wordpress/qa/e2e-failures/<file>--<test>.png`. The folder is cleared at the start of each run.

```bash
node wordpress/dev/tests/run-e2e.mjs 9600
node wordpress/dev/tests/run-e2e.mjs 9600 rota        # "file::test name" contains "rota"
node wordpress/dev/tests/run-e2e.mjs 9600 --list
```

```js
// dev/tests/e2e/rota.test.mjs
export const tests = [
  {
    name: 'saving the rota shows a success notice',
    role: 'ares-editor',              // any role key; omit for a visitor
    // viewport: 'phone',             // 390x844 instead of 1440x900
    // timeout: 120000, allowPhpNotices: false, skip: 'why'
    async run(t) {
      await t.goto('/wp-admin/admin.php?page=spokares-rota');
      await t.type('input[name="rows[2026-10-06][call]"]', 'KD7ABC', { clear: true });
      await t.clickAndWait('#submit');
      await t.expectVisible('.notice-success');
      t.expectNoConsoleErrors();
    },
  },
];
```

A file can also `export const tests = { 'name': async (t) => { … } }`.

### The `t` object

| Member | What it does |
|---|---|
| `t.goto(path)` | opens a path (or full URL), waits for the load and for the network to settle; returns `{ status, url, chain }` and sets `t.status` |
| `t.loginAs(roleKey)` | signs this context in (the `role` field does this for you) |
| `t.click(sel)`, `t.clickAndWait(sel)` | a real mouse click at the element's centre, after an instant scroll into view (it overrides the theme's smooth scrolling) and once the element has stopped moving; `clickAndWait` also waits for the page it opens |
| `t.type(sel, text, { clear })` | focuses the field and types (`clear: true` replaces the field's text) |
| `t.press('Enter')`, `t.press('Meta+z')` | a key press |
| `t.waitFor(sel, { visible, timeout })`, `t.waitForText(text, { selector })`, `t.waitForFunction(fn)`, `t.waitForNavigation()` | waits |
| `t.evaluate(fn, ...args)` | runs `fn` in the page with JSON arguments; returns its value |
| `t.text(sel)`, `t.texts(sel)`, `t.count(sel)`, `t.exists(sel)`, `t.attr(sel, name)`, `t.url()` | reads from the page |
| `t.expect(value, label?)` | `.toBe`, `.toEqual`, `.toContain`, `.toMatch`, `.toBeTruthy`, `.toBeFalsy`, `.toBeGreaterThan`, `.toBeLessThan`, `.toHaveLength`, and each with `.not.` |
| `t.expectStatus(code)`, `t.expectText(sel, text)`, `t.expectCount(sel, n)`, `t.expectVisible(sel)`, `t.expectHidden(sel)` | page expectations |
| `t.expectNoConsoleErrors()`, `t.expectNoFailedRequests()`, `t.expectNoPhpErrors()` | nothing went wrong so far |
| `t.facts()` | the crawler's page facts: overflow, admin notices, a11y lists and links (`dev/lib/inspect.mjs`) |
| `t.blockEditor()` | waits for the block editor. Returns blocks, invalid blocks, warnings, `templateLock`, `codeEditingEnabled`, the blocks left in the default editing mode, the inserter, the Welcome Guide and notices |
| `t.whoami(caps)`, `t.ids`, `t.unlockPosts()` | the QA endpoints |
| `t.setViewport('phone' \| 'desktop')`, `t.screenshot(name)`, `t.page` | viewport, screenshot, and the raw `Page` from `dev/lib/cdp.mjs` |

Before a test opens a page in the block editor, call `await t.unlockPosts()`. Otherwise an earlier visit's edit lock can bring up the "locked" dialog.

## run-all.sh

`run-all.sh <PORT>` runs three steps, then prints a summary:

1. `checks.sh`, the 50 HTTP checks. It runs first because it fails on any PHP notice logged since the site started.
2. `run-php.sh`.
3. `run-e2e.mjs`.

Every step runs even if an earlier one failed. The script exits 0 only when all three pass.

## The QA crawl

```bash
node wordpress/dev/qa/crawl.mjs <PORT> <outdir> [--roles=a,b] [--shots=<dir>] [--no-mobile]
     [--max-public=40] [--max-admin=100] [--match=a,b] [--merge] [--summary-only] [--quiet]
```

For each role, the crawler starts a fresh Chrome profile and signs in with `?dev_login=`. `--roles` takes role keys and defaults to all eight. It visits three kinds of URL.

**public**
- The six pages, a 404, search with results and with none, and the sign-in and lost-password pages (anonymous only). Then every same-origin page linked from those, breadth first.
- `/docs/<slug>/` and upload links are checked over HTTP only, for status and `Location`.
- Every `#fragment` link is checked against the ids on its target page, which covers the event anchors. Every published upcoming or undated event must have its anchor on Exercises & events; Draft events aren't listed there, so they aren't expected.

**admin**
- The Dashboard, every `#adminmenu` link (submenus too), and the admin-bar and Dashboard links.
- The known screens the role's capabilities allow:
  - Profile
  - Net Control Schedule, Net Settings, Cancel or Move a Meeting, Meeting Schedule, Most Used and ARES site settings
  - the Events and Documents lists, their add screens, and the edit screens for SET and ICS-213
  - Library sections and Pages
  - the block editor for Home, How it works, About and For members
  - Posts, Categories, Tags, Pattern Categories, Patterns (synced patterns), Media, Add Media File, the old uploader (`media-upload.php`), Edit Media for the Home hero, Comments, Users, Site Editor, Themes, Customizer, Widgets, Menus, Plugins, Tools, Site Health, Import, Export and Settings
  - Screens that PLAN §4 keeps for administrators (Posts, Categories, Tags, Patterns, Media, the uploaders, another user's file, Tools, Settings) are listed under `manage_options`, so a role that can open one shows as a leak even when core would let it in

**forbidden**
- The same known screens the role's capabilities do **not** allow. `?dev_qa=whoami` decides which list a screen goes in.
- Every plugin `admin-post.php` action, requested without a nonce. Every role must be refused.
- Read-only REST probes, and one write probe: `POST /wp/v2/blocks` (a synced pattern; administrators only). Anything it manages to create is deleted at once, and a copy left behind is a `probe-cleanup` warning.

Each forbidden attempt gets one outcome:

| Outcome | Meaning |
|---|---|
| `denied` | a 403, 400 or 401, or a `wp_die()` page. A `wp_die()` page sent with HTTP 500 is still `denied`, with a `denial-status` warning |
| `login` | sent to the sign-in page |
| `redirected` | sent to another screen, for example the Dashboard |
| `not-found` | a 404 |
| `ok` | the screen opened: a `access-leak` **error** |
| `error` | the refusal failed: a `access-error` **error** |

For every URL it records:

- HTTP status, final URL, redirects and `<title>`
- a full-page screenshot at 1440 wide. Public pages also get a true 390×844 phone shot. The editors are shot at the window size.
- uncaught exceptions and console errors
- failed same-origin and external requests
- JavaScript dialogs, which are accepted automatically
- **new PHP messages** in `/tmp/pg-<PORT>-logs/debug.log` and `/tmp/pg-<PORT>.log` written during that URL (the crawl runs one URL at a time against one worker, so they belong to it)
- horizontal overflow, with the elements where it starts
- content wider than its own box (`overflow-inner`): a long word or link running out of a column or cell over its neighbour, which the page-width check misses because the theme clips `.wp-site-blocks`. Visually hidden boxes, absolutely placed children and deliberate negative margins (`.prose .bleed`) are left out. It is a warning on public pages and the plugin's screens, info on core screens, and it is checked at both widths
- WordPress admin error and warning notices
- in the block editor: invalid blocks and block warnings, plus the lock signals for users without `edit_theme_options` (`templateLock` not `contentOnly`, the code editor, the inserter, page blocks left in full editing mode, the Welcome Guide)
- site policy checks: Trash, Add New or Quick Edit offered to non-administrators on Pages, Events or Documents, and the Bulk actions list: Move to Trash on Pages and Documents, bulk Edit on all three
- in the block editor, for non-administrators on a page: the page card's ⋮ Actions menu is opened and read (View, Rename, …). Order or Trash in it is an `editor-card` error
- basic accessibility:
  - images without `alt`
  - links and buttons with no accessible name
  - form fields without a label
  - duplicate ids
  - on public pages: no `<h1>` or more than one, no `<main>`, no `lang`, and `href="#"`

Findings have a severity: `error`, `warning` or `info`. Accessibility findings are warnings on public pages and the plugin's own screens, and info on core screens.

It writes three files:

- `<outdir>/report.json`: `{ "<role>": [ { url, key, kind: public|admin|forbidden, source, expect, status, finalUrl, redirects, title, access, denial, shots: { desktop, mobile }, blockEditor, findings: [ { type, severity, message } ] } ] }`. The shot paths are absolute.
- `<outdir>/meta.json`: the run's settings, the versions, and each role's login and shots folder.
- `<outdir>/summary.md`: totals per role, the access matrix (screen × role), errors and warnings grouped across roles, and every URL per role.

Screenshots go to `<shots>/<role>/<screen>-desktop.png` and `-mobile-390.png`. Forbidden attempts are named `forbidden-<screen>`. Each role's folder is emptied at the start of its crawl. The crawl's exit code doesn't depend on its findings: read the summary. It exits 1 when a role couldn't be crawled (Chrome wouldn't start for it, or died and couldn't be restarted). The report is still written, and that role has a `(role)` error record.

- A full crawl of all eight roles takes about 30 to 40 minutes.
- `--match=editor-,net-rota` visits only the URLs whose screen key or URL contains one of those words. Use it to re-check a few screens after a fix.
- `--merge` adds the run to an existing `<outdir>/report.json` instead of replacing it: records for the same role and URL are replaced, the rest are kept, and `summary.md` is rewritten. The roles' other screenshots are kept too. For example, `--roles=core-editor --match=pages --merge --shots=wordpress/qa/shots/after` updates an "after" run in place.
- `--summary-only` rewrites `summary.md` from an existing `report.json` without crawling.
- Chrome runs with a fresh profile under `/tmp` for each role. It is restarted, and signed in again, if a page hangs or Chrome dies. A visit that Chrome died during is thrown away and tried again, never recorded as HTTP 0. Every Chrome the crawler starts is killed when it exits.

The baseline run is in `wordpress/qa/runs/baseline/` (shots in `wordpress/qa/shots/baseline/`); the run after the fix pass is in `wordpress/qa/runs/final/` (shots in `wordpress/qa/shots/final/`). `wordpress/qa/REPORT.md` and `wordpress/qa/index.html` compare the two.

## Files

| File | What |
|---|---|
| `setup/qa-users.json` | the dev accounts, one per user level |
| `mu-plugins/spokares-dev-qa.php` | the QA endpoints and the PHP test endpoint (loaded by `setup/mu.php`) |
| `mu-plugins/spokares-dev-login.php` | `?dev_login=<login>` for every account in `qa-users.json` |
| `lib/cdp.mjs` | the Chrome driver: `Browser`, `Page` (goto, click, type, press, evaluate, waitFor, screenshot, console, network, dialogs) |
| `lib/site.mjs` | the accounts, `loginAs`, the QA endpoints, and `LogTail` for the PHP logs |
| `lib/inspect.mjs` | the in-page checks: `domFacts`, `blockEditorFacts`, `siteEditorFacts` |
| `qa/crawl.mjs` | the crawler |
| `tests/lib/framework.php`, `tests/lib/class-signal.php` | the PHP test framework |
| `tests/php/*-test.php` | the PHP tests (`accounts-test.php` and `framework-test.php` are samples) |
| `tests/e2e/*.test.mjs` | the browser tests (`sample.test.mjs`) |
| `tests/php/prior-*-test.php`, `tests/e2e/prior-*.test.mjs` | regression tests for the nine "Fixer round" bugs in `build-notes/plugin.md` (page guard, page template, Quick Edit, Save box, rota call-sign box, paste, event Where, orphaned files, CI gate). `prior-ci-gate.test.mjs` needs no browser: it runs `ops/ci.sh --offline --no-zip` with stand-in `phpcs`, `php` and `npx` |
| `tests/php/qa-*-test.php`, `tests/e2e/qa-*.test.mjs` | regression tests from the 2026-09-27 QA sweep: `qa-NNN-…` for one issue, `qa-<group>-…` for a fix group's issues (`admin`, `front`, `security`, `theme`, `cross`). `wordpress/qa/ISSUES.md` and `issues.json` name the test for each issue |
| `tests/run-php.sh`, `tests/run-e2e.mjs`, `tests/run-all.sh` | the runners |
