# spokares.org on WordPress (Option B, "Carry the Message")

This folder is the WordPress build of the new spokares.org for Spokane County ARES-ACS. It takes the round-3 design the owner chose, Option B "Carry the Message" (`design/round3/option-b-lines-down/`), and turns it into a block theme plus a small site plugin. Volunteers keep the changing facts current from wp-admin: the net-control rota, events and exercises, meeting changes, the document library and page text.

- **Status:** built and reviewed on a local WordPress 7.1.2 site; not yet deployed. The code carries version 0.1.0. See [Known limitations and open questions](#known-limitations-and-open-questions) before launch.
- **Decision record and build contract:** [`PLAN.md`](PLAN.md). Where this README and PLAN.md disagree, PLAN.md wins.
- **For volunteer editors:** [`EDITING-GUIDE.md`](EDITING-GUIDE.md), one page covering the five common jobs.
- **Review page:** [`review/index.html`](review/index.html). Open it from disk. It shows the mockup and WordPress side by side for all six pages, the admin screens, and the reviewer scores.

## How it fits together

| Piece | Folder | What it holds |
|---|---|---|
| Theme `spokares` | `theme/spokares/` | The look, with no data: B's colours, fonts and spacing in `theme.json`, B's `site.css` ported to block markup, templates, the header, footer and members sub-nav parts, the page patterns, and two fixed design blocks (the seal lockup and the How it works message-path figure) |
| Site plugin `spokares-core` | `plugins/spokares-core/` | The data and the editing: the Events and Documents post types, the rota, net, meeting and tile settings, the wp-admin screens, the ARES Editor role, the layout lock, and seven server-rendered blocks (`events`, `net`, `meetings`, `docs`, `toc`, `last-reviewed`, `asof`) that print B's markup from that data |
| Must-use plugin `spokares-hardening` | `mu-plugins/` | The security floor, which wp-admin can't switch off: XML-RPC off, user enumeration blocked, security headers, upload checks, two-factor enforcement, SMTP and the old-URL 301/410 map |

The theme renders every list on the server for "today" in Pacific time, and pages are complete without JavaScript. Past events drop off by themselves, "Happening now" is worked out when the page loads, and each fact is kept in one place: the net time on Home, How it works and the members hub all come from one form. There is no build step: plain PHP, JSON, CSS and a few small scripts.

## Repository layout

```
wordpress/
  README.md            this file
  PLAN.md              decisions, content model, admin screens, security, deployment, build contract (§6), risks (§7)
  EDITING-GUIDE.md     the one-page guide for volunteer editors
  theme/spokares/      the block theme (templates/, parts/, patterns/, blocks/, assets/, theme.json, functions.php)
  plugins/spokares-core/   the site plugin (inc/, blocks/, assets/)
  mu-plugins/          spokares-hardening.php + spokares-hardening/ (the must-use plugin)
  dev/                 the local WordPress Playground site: start.sh, stop.sh, blueprint.json, seed/, setup/, checks.sh, shots.sh
  ops/                 release, deploy, monitoring and backup scripts (ci.sh, deploy.sh, check-live.sh, weekly-check.sh, backup-pull.sh)
  tools/redirects/     generator for the old-URL map (never deployed)
  research/            the background notes behind PLAN.md (architecture, components, content model, editor UX, security and hosting)
  build-notes/         what each builder did and where they departed from PLAN.md (theme, plugin, patterns, dev)
  shots/final/         current screenshots of every page and admin screen
  shots/review-*/      screenshots taken by the fidelity and editor reviewers
  review/index.html    the review page
```

## Run it locally

You need Node 22 or newer and network access. You don't need PHP, MySQL or Docker. Google Chrome is only needed for screenshots.

```bash
wordpress/dev/start.sh 9400      # WordPress 7.1.2, PHP 8.4, at http://127.0.0.1:9400/
wordpress/dev/stop.sh 9400       # always stop it when you're done
```

A cold start takes 15 to 40 seconds; the first run also downloads WordPress. The site is rebuilt from `dev/blueprint.json` on every start, with the round-3 content already loaded (37 documents, 20 events, the rota and meetings) and "today" pinned to 2026-09-26 so the pages match the mockup. **To reset, stop and start again.** The theme and plugins are mounted, not copied, so code edits show on the next page load.

**Signing in**

| Account | Quick link | Password sign-in |
|---|---|---|
| Administrator | `http://127.0.0.1:9400/wp-admin/?dev_login=1` | `admin` / `password` at `/wp-login.php` |
| ARES Editor (what volunteers see) | `http://127.0.0.1:9400/wp-admin/?dev_login=editor` | `editor` / `password` |

The `?dev_login=` switch only works on a local site with `SPOKARES_DEV` set, from 127.0.0.1. It lives in `dev/mu-plugins/` and is never deployed. Add `?today=2026-10-25` to any page to see it on another day.

**Checks and screenshots**

```bash
wordpress/dev/checks.sh 9400                    # 50 checks: pages, anchors, /docs links, hardening, 404s, PHP notices
wordpress/dev/shots.sh 9400 wordpress/shots/final   # every page at 1440 and 390, plus the admin screens
wordpress/ops/ci.sh --no-zip                    # release gate: PHP lint, PHPCS WordPress-Extra, Plugin Check, forbidden-code grep
```

`dev/README.md` has the details: other WordPress or PHP versions, logs, the seed and importer, and what the blueprint builds.

## Deploy to Verpex Enhance

The full steps are in PLAN.md §5 and `ops/README.md`. In short:

**One-time setup** (PLAN §5.11, which amends research `security-hosting.md` §11)
1. In Enhance, create a separate customer org for spokares.org. Turn on PHP 8.4, OPcache and Force HTTPS, and panel 2FA for both panel owners. Ask Verpex the §5.10 questions first (web server kind, OPcache timestamps, a web-server deny for `/readme.html`, the AUP OK for the document library).
2. Use the one-click WordPress install with a non-default username and table prefix. Delete Hello Dolly, Akismet, any cache plugin the installer adds, and every theme except `spokares` and one spare default theme. Turn off registration, comments and pings.
3. Put the `wp-config.php` constants in place (PLAN §5.2): `DISALLOW_FILE_EDIT`, `DISALLOW_UNFILTERED_HTML`, `FORCE_SSL_ADMIN`, `WP_ENVIRONMENT_TYPE` production, `WP_POST_REVISIONS` 20 and the four `SPOKARES_SMTP_*` values; file mode 600. **Never** set `DISALLOW_FILE_MODS`, `AUTOMATIC_UPDATER_DISABLED`, `SPOKARES_DEV`, `SPOKARES_TODAY` or `WP_DEVELOPMENT_MODE` there.
4. Tag a release and deploy it: `ops/ci.sh --tag=vX.Y.Z`, then `ops/deploy.sh vX.Y.Z production`. This rsyncs the theme, the plugin and the must-use plugin from a clean export of the tag. Activate the theme and `spokares-core`.
5. Install **Two-Factor** and **UpdraftPlus** (free). Set up TOTP and backup codes for both administrators.
6. Load the content once in production mode, which leaves every document as a Draft with no Privacy tick (`dev/README.md`, "Production seeding"). Then an administrator reviews and publishes each document and clears every "Needs checking" item.
7. Mail: create the `website@spokares.org` mailbox, turn on DKIM, and set DMARC to `p=none`. Test a password reset.
8. Create the two ARES Editor accounts. E-mailed sign-in codes are on from the moment an account exists; set up TOTP and print the backup codes in person.
9. Turn on toolkit auto-updates for core (major), Two-Factor, UpdraftPlus and the spare theme. Add `ops/weekly-check.sh` as a weekly Enhance cron job with a dead-man ping. Set up the uptime keyword check ("Net control" on `/members/`).
10. Run `ops/check-live.sh vX.Y.Z` and the launch gate (PLAN §6.13).

**Every release after that** (`ops/README.md`): bump the version in the eight places `ci.sh` lists, tag, run `ci.sh`, deploy to a fresh Enhance staging clone and test, delete the staging site, deploy to production, and run `check-live.sh`. Roll back by deploying the previous tag. **Never use Enhance "push live"**: it copies the database and would overwrite the editors' rota, events and documents.

**Where structure lives:** templates, parts, CSS and the three members pages live in the theme files, so change them in git, never in the Site Editor. Home, How it works and About live in the database once set up, so for a layout change an administrator edits the page and mirrors the change in the pattern file in the same release.

**Backups:** Enhance snapshots, UpdraftPlus to a role-owned Google Drive (convenience), and a monthly `ops/backup-pull.sh` to the admin's machine. The host holds no credential for that last copy, so an intruder can't delete it.

## Plugins

The D5 limit is four plugins. Security, SMTP, redirects and the old-URL map are our own code.

| Slot | Plugin | Why |
|---|---|---|
| 1 | `spokares-core` (ours) | Content types, admin screens, dynamic blocks, the ARES Editor role, the layout lock, `/docs/<slug>/` links. `Update URI: false`, so WordPress.org can never "update" it |
| 2 | Two-Factor (WordPress.org) | TOTP, e-mailed codes and backup codes. Our must-use plugin enforces it for everyone who can edit |
| 3 | UpdraftPlus (free) | Scheduled off-host backups. Never connect UpdraftCentral or UpdraftVault |
| 4 | empty | No page cache at launch |
| — | `spokares-hardening` (ours, must-use) | Not a slot: it is our own security code, kept where wp-admin can't switch it off |

Rejected: events calendars, ACF and similar field plugins, security suites, form plugins, SMTP plugins, Redirection, page builders and LiteSpeed Cache (PLAN §1.2).

## Known limitations and open questions

**Not yet proven**
- **The launch gate hasn't run.** AG7QP (or the first named editor) should do the five jobs on the dev site with only the Editing guide, inside the target times (PLAN §6.13). The editor reviewer scored editing 6/10 *before* the fixer round; the fixes were checked by the fixer (`checks.sh` 50/50, `ci.sh` 11/11), but no reviewer has re-scored them.
- **Two-factor sign-in** (e-mailed codes, TOTP enrolment, the enforcement floor) can only be tested on a real staging site, not in Playground (click test 18).
- **The ops scripts** were tested against a fake host, never a real Enhance server. The first staging deploy is the real test.

**Ask Verpex before the first deploy** (PLAN §5.10): the web server kind; whether `opcache.validate_timestamps` is on (if not, deployed PHP doesn't load until PHP restarts); a web-server deny for `/readme.html` and `/license.txt` (they show the WordPress version, and our code can't block them); `expose_php=Off`; whether `/wp-content/updraft/` and directory listing are blocked; whether panel WordPress SSO skips 2FA; snapshot retention; and a written AUP OK for the document library.

**Known limitations**
- Editors change words, links, list items and the hero photo on Home, How it works and About. Adding a section, a table row or a new page is administrator work.
- Home never lists events, by design. A new event shows in "Later this season" on Exercises & events, then as a "Next up" card and on the members hub once it is one of the next two.
- An event has no separate "What to bring" field; the "What members do" lines serve for that.
- A published document with a problem (for example a phone number) goes back to Draft, off the site, until it is fixed. Events keep their live version instead. Changing documents to work the same way is deferred.
- A file saved with a Draft document can be reached by anyone who has its exact random web address. The Privacy check must be ticked before it can be uploaded.
- Small fidelity differences remain: abbreviations in event text are plain (no dotted underline), the W1AW/7 link covers the whole event name, the WSDOT extra-form label reads "WSDOT Form 580-020…", the "Radio settings" jump link sits under the How it works title on desktop too, and the 404 and search pages are plain.
- The footer's "Mockup — content to be verified before launch" and "Privacy (coming)" stay until the launch release and a privacy page exist.
- The rota, meetings, net details and tiles are WordPress options. Database backups cover them; a WordPress export (WXR) does not.
- Deferred to after launch (P2): a paste box for the rota, a `/calendar.ics` feed, JSON export of the settings, and `page-sync` to re-apply a page pattern.
- PLAN §6.13 asked for a printable HTML Editing guide with a box for each editor's backup codes. `EDITING-GUIDE.md` covers the content; the printed version and the codes box are made at enrolment.

**Decisions for the owner, the EC and AG7QP** (PLAN §7.2): WordPress as the one source for events, the rota and meetings (D9); who besides the administrators may change Net details and Meeting rules; DOCX/XLSX uploads or PDF only; the members-page heading rule (grey hairline, per the round-3 review); whether to keep showing the alternate repeater; the re-point table for old URLs (D15); who reviews the 37 documents at launch. Round-3 launch blockers are still open: the net-scripts link exposes the roster (D14), `join@` isn't live, the legal-basis text needs SCEM review, and the role addresses (including `webmaster@spokares.org`, which the admin screens show) are still proposals (D11).
