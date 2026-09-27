# Build notes: plugin (`spokares-core`, `spokares-hardening`, `tools/redirects`)

Plugin builder, 2026-09-26. Built against WordPress 7.1.2 in Playground on PHP 8.4, then re-checked on PHP 8.3 (no notices with `WP_DEBUG` on in either). All P0 and P1 items in PLAN §6.12 are done; P2 is not started. The published names in §6.2 are unchanged.

## Deviations from PLAN.md (read first)

1. **`spokares/last-reviewed` prints no front-end "Edit this list in …" link.** It is the only view whose screen is a box beside the page, so "Edit this list in Page review box" would read oddly. The admin bar's "Edit page" covers it. The editor-preview hint and the placeholder are printed as specified.
2. **Extra `needs-verify` classes** wherever B had `data-verify`: the rota Note cell (when `spk_nets.needs_check` is set and the week has a rule note), the next-up card, the Later and public-service rows, the past-exercise title span, the library's `doc-title`, and each meeting's line on Home. They are outlined only in the theme's `spk-verify` mode.
3. **The call-sign check also needs a letter.** The §4.4 regex alone accepts "2026", so a word must match it **and** contain `[A-Z]`.
4. **Net details refuses frequencies in 806–869.999 MHz** (the 800 MHz public-safety band), on top of the `^\d{2,3}\.\d{3}$` shape.
5. **The skeleton check:**
   - It ignores attributes that equal the block type's registered default, because the editor drops them on save.
   - It takes the anchor from the `id` on the block's first HTML tag, which wins over a comment `anchor`. Anchors of static blocks live in the HTML, so an `id` edit is caught.
   - The Cover's free attributes are extended with what "Replace image" rewrites: `isDark`, `isUserOverlayColor`, `customOverlayColor`, `overlayColor` and `useFeaturedImage`. This is per the patterns builder's measurement. `dimRatio` stays locked at 0, so the overlay stays invisible.
6. **Page guard, beyond slug and parent.** For non-admins, the `wp_insert_post_data` guard also keeps an already published page published: an editor can't switch Home to draft.
7. **Editor guard extras.**
   - For editors it hides the Slug, Parent and Featured image panels (`removeEditorPanel`). The server keeps slug and parent anyway.
   - The script also works when the editor shows the template around the page. The page's sections are then the Post Content block's children, and core handles the root.
8. **The section chooser is our own radio list inside the Document form** (taxonomy `meta_box_cb => false`), not a separate meta box. The result is still one term, never the tag box.
9. **New internal names**, all prefixed:
   - option `spk_core_version`, which re-syncs the role after a deploy without activation;
   - post meta `spk_pulled` (`['at','by']`, set by "Pull this file now" and shown on the document);
   - per-user transients `spokares_notices_{id}` and `spokares_retain_{id}_{screen}`.
10. **Regular meetings:** a note with neither Cancelled nor Moved to is held back with a sentence. The schema has no "note only" change.
11. **TOC scroll-spy** ports B's own `about.html` script: a scroll listener throttled with requestAnimationFrame, with `aria-current`, `is-read` and the rail. IntersectionObserver is used only to park the phone "On this page" button.
12. **The fail-closed rule** (Two-Factor inactive) is skipped in dev, like enforcement (local + `SPOKARES_DEV`). A dev boot without network access can then still test the editor.
13. **Documents list** sorts by name by default: menu order only makes sense within a section. The section filter is a dropdown. The month dropdown is removed on both lists.
14. **Published items keep a "Save as draft (takes it off the site)" link** in our Save box, so an editor can unpublish without trashing.

## Files

**`plugins/spokares-core/`** (Update URI false, Requires 7.1, PHP 8.3, version 0.1.0):

| File | What |
|---|---|
| `spokares-core.php` | Bootstrap, constants, activation (role, default options, direct registration, rewrite flush), version re-sync on `init` |
| `inc/helpers.php` | `spokares_today()` (dev: `?today=` then `SPOKARES_TODAY`), option access with defaults (`spokares_opt()`), `spokares_link()` + host map, `spokares_icon()`, `spokares_text()`/`spokares_nbsp()`, `spokares_purge_cache()`, `spokares_is_editor_preview()`, theme-update filter |
| `inc/format.php` | `spokares_fmt_time/_time_range/_date/_date_range/_when`, date slab, ordinals, radio lines (§3.3 table) |
| `inc/guards.php` | `spokares_call_sign()`, `spokares_check_text()` (+ patterns shared with the editor script), confirm-tick helpers, `spokares_clean_url()` |
| `inc/data.php` | Post types, taxonomy, meta (all `show_in_rest` false, typed, sanitised, auth), sort keys kept in step on any meta write, `register_setting` normalisers, purges, comments support removed |
| `inc/schedule.php` | nth weekday, `spokares_net_on()`, `spokares_rota_rows()`, meetings with cancellations and moves (`spokares_next_meetings()`), `spokares_events( $view, $args )` |
| `inc/roles.php` | `ares_editor` (exact cap list), admin caps, `map_meta_cap` for the three members pages, the per-user Net details grant (another user's profile, `promote_users`, own nonce) |
| `inc/blocks.php`, `inc/render.php`, `blocks/*` | Category "ARES", the three script modules, seven blocks (block.json v3, `autoRegister`), shared wrapper, hint, placeholder and edit link, output through a `wp_kses` allowlist |
| `inc/routes.php` | `/docs/<slug>/` → 302 (uploads: safe redirect to our uploads URL; links: stored https only) or 404 |
| `inc/files.php` | Where a file is used, file removal, the `pre_delete_attachment` guard. **Loaded everywhere**, so REST and WP-CLI deletes are guarded too |
| `inc/governance.php` | Skeleton check (`rest_pre_insert_page` + `wp_insert_post_data`), editor settings, allowed blocks, editor-guard enqueue, page slug/parent/status guard |
| `inc/admin-*.php`, `inc/dashboard.php`, `inc/admin-bar.php` | Screens, handlers (admin-post, one nonce and capability each), trimming, Site tasks, Update lists menu, Page review box |
| `assets/js/` | `copy.js`, `doc-library.js`, `toc.js` (ES modules); `admin-forms.js`, `editor-guard.js` (plain scripts on `wp.*`) |
| `assets/css/admin.css` | Admin only |

**`mu-plugins/`**: `spokares-hardening.php` (loader; defines `SPOKARES_HARDENING_VERSION`, `spokares_hard_is_dev()`) and `spokares-hardening/{hardening,enumeration,content-surface,headers,uploads,two-factor,smtp,redirects}.php` plus the generated `redirect-map.php`.

**`tools/redirects/`**: `gen-redirect-map.mjs` (Node stdlib; `--check` for CI) and `repoint.csv`. The re-point table is a **draft for the EC to confirm** (D15): 34 D15 targets mapped to B's pages and anchors; `/privacy/` points to `/`. The generated map has 313 × 301 and 469 × 410 (both roster URLs are 410). Host, `none` and `keep` rows are skipped.

## Verified (Playground, port 9422, seed = round-3 `data.js` through the §6.3 schema)

- **Block output matches the §6.12 acceptance data for 2026-09-26:**
  - Hub: WSDOT "Happening now" and SET. Rota NZ2S / Open / AE7RJ / WA7LNC / Open, with Simplex (linked to `#net-scripts`), Winlink night, GMRS net 7:30 PM and Winlink night.
  - Home: "Next: Sat, Oct 10" and "Next: Thu, Oct 15", the From home card and the In person line.
  - How it works: the three Other nets lines and the settings box.
  - Exercises: 2 cards, 6 Later rows ("SHARES training: for SHARES participants"), 6 Winlink rows, 4 public-service rows (dated first), 8 past rows.
  - Library: 37 rows in 7 groups.
  - `?today=2026-10-02` puts the Oct 6 open slot first. Checked again with the real theme and patterns: 6 pages, one H1 each, no `href="#"`.
- **Editor previews:** `/wp/v2/block-renderer/spokares/*` as the editor prints the plain-text "Edit in wp-admin › …" hint. The last-reviewed, past and tiles placeholders show when there is nothing to print publicly.
- **The layout lock (REST, as `ares_editor`)** on Home, How it works and About built from the real patterns:
  - An unchanged resave, a text edit, and adding or removing a list item all save.
  - Changing a view, a class, an anchor or a lock attribute, or adding a root block, is refused with `spokares_layout_locked` (403).
  - Admins are not locked.
- **Admin, as the editor:**
  - The menu is exactly Dashboard · Net rota · Events (All events, Add event, Regular meetings) · Documents (All documents, Add document, Hub tiles) · Pages · Profile.
  - Media, Posts, Tools and Comments are gone, and `upload.php` redirects.
  - Net details and Meeting rules are refused until an admin ticks the grant on the editor's profile. After that, Net details opens.
- **Click tests 12-16 (§6.13), scripted over HTTP:**
  - Rota: "Frank AG7QP" → AG7QP with a notice; "AE7RJ & NZ2S" → AE7RJ; "Frank" is not saved and the text stays; a phone number in a note is held back and the tick is offered; two sessions editing the same row get the conflict notice; Undo names who and when.
  - Events: no kind → Draft; "hospital net" in tasks and a phone number in the short line → Draft, outlined, text kept, tick offered; the tick publishes; a Training event lands in the hub and in Later; Duplicate gives a draft with the dates cleared; "Tom nv2z" → NV2Z; "Tom" → Draft.
  - Meetings: cancelling Oct 10 → Home "Next: Sat, Nov 14 (Oct 10 cancelled)" and the hub's cancelled line with its note; moving it → "(moved from Oct 10)".
  - Documents:
    - A file without the Privacy tick is refused and the document saved as a Draft.
    - With the tick, the file uploads on save with a random suffix, and `/docs/<slug>/` 302s to it.
    - Replace (box ticked) → the old URL is 404 and the file is gone from disk.
    - Trash (box ticked) → the file is gone.
    - Pull this file now (admin only) → the file is gone, and the document becomes a Draft marked "Soon".
    - A macro DOCX and an external-template DOCX are refused; a clean DOCX is accepted; `.php` is refused.
    - A REST delete of a file a document still uses is refused (`rest_cannot_delete`).
  - Hub tiles: putting slot 4's document into slot 2 without JavaScript swaps the document, words and icon.
  - Net details: tone and weeks changes reach the hub bar, How it works and Home; an 800 MHz frequency and a "hospital net" sentence are held back with the typed text kept.
- **Must-use plugin (logged out):**
  - `/xmlrpc.php` 403.
  - `/wp-json/wp/v2/users` 404; `/wp-json/wp/v2/pages` 401.
  - `/?author=1` 404; `/feed/` 404; oEmbed has no `author_name`.
  - `/index.php?option=com_content&view=article&id=11` → 301 `/about/#licensing`; `/index.php?option=com_x` 410; the roster `.doc` and `/Memberlist.pdf` 410; `/index.php/<spam>` 410; a missing `/wp-content/uploads/…` 404 with no redirect guessing.
  - A lost-password POST for an unknown user or address → `checkemail=confirm`.
  - Security headers on `/`, `/wp-login.php`, `/wp-admin/` (including the logged-out redirect) and REST responses.
  - JPEG EXIF/GPS stripped on upload; editors can't upload PDF in the media window; SVG is refused even for admins.
- **Staging-like boot (no `SPOKARES_DEV`):**
  - New accounts get the Email provider at registration, even while Two-Factor is inactive.
  - Two-Factor inactive → editors get 403 "Sign-in protection is off. Contact the webmaster." and admins see the red notice.
  - With `spokares-core` deactivated, XML-RPC, redirects, 410s, enumeration and headers still work.
  - With Two-Factor active, a user without a provider is sent to `profile.php#two-factor-options`. REST (except `two-factor/1.0`), admin-ajax and async-upload refuse them, and `user-edit.php` is not reachable.
  - An editor with e-mailed codes gets the code screen at sign-in.
- **Front end in Chrome:** the Copy buttons are revealed, `#forms` pre-selects the Forms chip (9 rows), `?q=task book` filters to 3, and the TOC rail and parked button initialise.
- **Forbidden-code grep** (`nopriv`, `register_rest_route`, `$_REQUEST`, `unserialize`, `eval(`, `extract(`): clean. Every PHP file starts with the ABSPATH guard.
- **Not run:** PHPCS and Plugin Check (no local PHP). `ops/ci.sh` should run them.
- **For the Editing guide:** the block editor's save button in 7.1.2 reads **"Save"** (click test 11).

## For the other builders

- **Dev environment:**
  - Start Playground with **`--workers=1`**. With the default (up to 6 workers), writes made in one worker (a login session, a deleted file) aren't seen at once by the others. Headless-Chrome dev logins then fail intermittently, and deleted uploads return 500 instead of 404.
  - In `setup/pages.php`, insert pattern content with **`wp_slash()`** (`wp_insert_post( wp_slash( [ …, 'post_content' => $content ] ) )`). Otherwise WordPress strips the backslashes in `--` (the serializer's escape for `--`) and `card--dawn` becomes `cardu002du002ddawn`.
  - The importer can write options directly. The `register_setting` sanitisers normalise the shapes on `update_option` and drop unknown keys in rota rows.
  - Event sort keys (`spk_sort`, `spk_end_sort`) are recomputed automatically whenever `spk_start`, `spk_end` or `spk_date_mode` is written, so the importer need not compute them.
  - `/docs/<slug>/` needs a rewrite flush after activation; the activation hook does it.
  - My test fixtures (seed, dev-login shim, blueprints) live only in my scratchpad, not in `dev/`.
- **Theme:** the plugin prints the §6.4 markup plus these classes:
  - `rota-tbd`, `spk-edit-hint`, `spk-edit-link`, `hub-meeting-change`;
  - `ex-summary`, `ex-form`, `ex-link`, `ex-status`;
  - `needs-verify` (see deviation 2);
  - `.toast.is-on` (copy module);
  - `.contents-fab.is-parked`, `.toc a.is-active` / `.is-read`, and inline `top`/`left`/`height` on `.toc__rail`.
  - Copy buttons and the fab ship with `hidden`, and the modules reveal them. No front-end CSS ships in the plugin.
- **Integration:** the redirect re-point table (`tools/redirects/repoint.csv`) needs the EC's confirmation (D15). After editing it or `research/redirects.csv`, run `node wordpress/tools/redirects/gen-redirect-map.mjs`.

## Integration round 2 (2026-09-26)

- **Save box id `submitpost`.** Events and documents use our own Save box, and WordPress's `post.js` only binds its submit handling to buttons inside `#submitpost`. Without it, every Publish or Update of a changed event or document raised the browser's "Leave site? Changes you made may not be saved" dialog (reproduced with a real click in Chrome). With the id, core suspends autosave, drops the warning and blocks double clicks. No core CSS targets `#submitpost`.
- **Update button is `name="save"`**, like core's. The notice after an update now reads "Saved. See it on …" instead of "Published. …". Publish (first time) keeps `name="publish"`; a held-back save still reads "Draft saved.".
- **List-screen notices** name the thing: "1 event moved to the Trash.", "4 events deleted for good.", "1 document restored from the Trash." (`bulk_post_updated_messages`).
- **Layout lock: table shape.** `spokares_block_skeleton()` now adds `_shape` for `core/table`: one entry per row, its section and its cell tags (`thead:th,th,th`, `tbody:th,td,td`), read with `WP_HTML_Tag_Processor`. In content-only mode 7.1.2 still shows the Table's Header section and Footer section switches; turning the header off would have saved (and removed the phone stack labels). Now: removing the header, adding a footer or adding a row → 403 `spokares_layout_locked`; cell text edits and list-item add/remove still save (checked over REST and in the block editor as `ares_editor`).
- **Editor guard: only list items can be inserted.** `blockEditor.__unstableCanInsertBlockType` (core uses the same filter to hide template parts) returns false for anything but `core/list-item`. The inserter no longer appears inside the hero Cover or a Buttons group, and Enter at the end of a paragraph adds nothing. Needs `wp-hooks` (added to the script's dependencies). If the filter disappears in a later release, the server lock still refuses the save.
- **No "Edit this list in Documents" under the hub search box** (`spokares/docs` view `search` passes `$with_link = false`); the search box isn't a list.
- **Dev cache-busting:** `spokares_asset_version()` adds the file's modification time to the plugin's script and style versions on a `local` site (as the theme does). Production still uses `SPOKARES_CORE_VERSION`; bump it at release as `ops/README.md` says.
- Still visible to editors, still refused on save: the Cover's Fixed/Repeated background switches and Advanced › HTML element. Hiding them needs admin CSS on labels or a fork of the Cover's inspector.

## Fixer round (2026-09-27)

Every must-fix item from the editor and code reviews, and most should-fix items. Verified on port 9450 (WordPress 7.1.2, PHP 8.4): `checks.sh` 50/50, `ci.sh --no-zip` 11/11 (PHPCS 0 errors, Plugin Check no errors), all three page editors valid, screenshots in `wordpress/shots/final/`.

**Pages (editors)**
- The server keeps a page's slug, parent, author, order, **password**, **publish date**, published status and **template** (`_wp_page_template`, guarded on update/add/delete meta) for non-admins, on every path: REST, the classic form, Quick Edit. `theme_page_templates` drops the three members templates for them (REST then refuses those with 400).
- The block editor hides the Page panel's Status, Publish, Slug, Author, Template, Revisions and Trash rows (`removeEditorPanel( 'post-status' )`), the block inserter, the Styles tab, the Advanced panel, and the inspector's switches and selects (Cover fixed/repeated background and resolution, Table header/footer). The empty "Meta Boxes" drawer is hidden while it holds no box.
- `editor-guard.js` sets an editing mode on **every** block of the page: word blocks content-only, everything else (groups, the Buttons row, spacers, quotes, dynamic blocks) disabled. A button can no longer be dragged, moved or deleted from its row. Button Fill/Outline styles are unregistered for editors.
- Pasting several paragraphs or lines into a paragraph arrives as one paragraph with line breaks (one line in a heading or button), with a notice. List items still split. Word and e-mail HTML is read for its paragraphs; plain text splits on blank lines.
- After a save, a warning when a new item in What we do, the timeline or the message hops has no bold first words.
- Skeleton: the first tag's class (without `wp-…`, the Cover's `is-light`, the Image's `size-…`) and every inline `style` in a block's own HTML now count. kses drops `position`/offsets/`z-index` for non-admins. An emptied button is refused (`spokares_empty_button`). The refusal names Cmd+Z and the Undo arrow.
- Autosaves are never refused (the controller stored the error object as an **empty** autosave); they keep the stored content. A refused save deletes the editor's old autosave.
- Quick Edit and bulk Edit are gone from Pages for editors, and core's `inline-save` and `bulk_edit` handlers are refused for pages (non-admins), events and documents (everyone).

**Events**
- New optional **Where** field (`spk_where`, checked like the other text): on Next up cards, Later this season and public-service rows, and the hub list.
- The form says where each kind shows; after a save of a published event a notice says where it shows now ("On the site now: … Events never show on Home.").
- A published event with a problem (phone or e-mail without the tick, a never-publish word, a bad date) **stays published**: only those fields keep their live values, the typed text comes back in the form for 5 minutes with the tick. Drafts and new events still become Drafts.
- A publish that didn't come through our form (non-admin) re-runs the checks on the stored fields and keeps the event a Draft on a problem.
- The Save box can't be hidden or folded; Screen Options is off on the event and document forms for editors. The "Checking" box is now "Webmaster check: Not marked for the webmaster."
- "Later this season" keeps an event's other links: a link whose words are in the short line is made there (ShakeOut's DYFI), others follow it.

**Documents**
- A document deleted for good (by anyone, or WP-Cron 30 days after Trash) deletes its file when nothing else uses it (`before_delete_post`, in `files.php`, loaded everywhere). With the must-use plugin's attachment 404s this closes "orphaned files public by sequential id".
- "Save as draft (takes it off the lists)" plus a note that an uploaded file keeps its address. New documents go to the end of their section. Publishing with the Privacy check ticked sets an empty Last reviewed to today. Reserved slugs (section ids, `search`, `main`, …) get `-document`.

**Rota and settings**
- The call-sign box is read-only and greyed (never disabled) on Open / Not posted yet rows; clicking or tabbing into it opens it, and typing picks Call sign. "Shows" uses the server's call-sign rule (and says "No call sign: not saved"). Phone layout labels every box; no sideways scroll.
- Undo after an undo reads "Put back what was undone (…)"; the notices name whose save was undone.
- Rota, Undo, Regular meetings and Meeting rules saves take a short lock (`spokares_lock_option()`: an `INSERT IGNORE` row, 30 s stale takeover) and re-read the option fresh.
- Settings handlers read nested values with `spokares_post_str()` (no "Array to string" from crafted arrays).
- Net details: its preview's settings-box line now shows the frequency and tone line; editors without the grant get a sentence naming the webmaster instead of WordPress's 403, and the rota and Dashboard say who to ask. Dashboard: "Add a document" and "Replace a document".

**Other**
- `spokares_opt()` memoised per request (flushed on any write); tiles prime their four documents in one query; `spk_core_version` autoloaded; rewrite rules flushed when the plugin version changes; the plugin maps `create_posts` for posts and pages too (the must-use plugin's copy is no longer the only one).
- Must-use plugin: attachment pages 404 and never redirect to the file (`?attachment_id=`, `?p=`, `?page_id=`, `?attachment=`, pretty URLs, guessed redirects); `X-Powered-By` removed; REST discovery links off for visitors.
- Theme: the "Open position" pill is back (bold words before a link in the roles table, drawn by CSS; the pattern now bolds them); the readiness levels' narrow cells are left out of the no-break rule (3/5/5/3 lines as B); a longer tone gets slightly smaller net-bar type so Copy stays on the line; emoji script and styles off.
- Release gate: `phpcs.xml` knows `spokares_verify_form()` as a nonce check and excludes the generated redirect map; phpcbf run; `ci.sh` stops when `mktemp` fails and passes PHPCS only on a parsed JSON report. `check-live.sh` checks attachment 404s, `X-Powered-By` and notes `/readme.html`.

**Deferred**
- The footer's "Mockup — content to be verified before launch" and "Privacy (coming)" stay: PLAN §2.2 #20 and the placement map keep them until the launch release and a privacy page exist.
- Documents keep the Draft-on-problem rule for published documents (the hold-back is events only): a document's file and Privacy tick make "keep the live version" a larger change.
- `/readme.html` can only be denied at the web server (Ask Verpex, ops README).
