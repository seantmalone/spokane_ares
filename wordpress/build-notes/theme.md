# Build notes: theme (`wordpress/theme/spokares/`, excluding `patterns/`)

Theme builder, 2026-09-26. Built against WordPress 7.1.2 in Playground (PHP 8.3 and 8.4 lint clean). All P0 and P1 items in PLAN §6.12 are done.

## Deviations from PLAN.md (published names unchanged)

1. **Mobile menu (P1) is B's drop-down, built without the `navigation-overlay` part.** Core's own overlay (`overlayMenu: "always"`) is restyled in `site.css` §7: the open container is anchored to `.masthead` (`top: 100%`), exactly like B's `.nav__list`. The brand and "Join the team" stay in the bar, and core's "Close" button sits where "Menu" was. Keyboard behaviour is checked: focus is trapped while open, Escape closes, focus returns to "Menu", and a click outside closes. The part file was not created, and `navigation-overlay` is removed from `theme.json` `templateParts`. The P0 full-screen overlay isn't used.
2. **`add_theme_support( 'disable-layout-styles' )`.** In the editor canvas, WordPress's layout CSS (flow margins, flex gaps, the 760px content column) is printed *after* `site.css`, and it overrode B's spacing there, even though `settings.spacing.blockGap` is null. With layout styles off, `site.css` does all the layout, on the front end and in the editor alike. Consequences:
   - A Group's `layout` attribute has no visual effect.
   - Core Buttons get `display: flex` from `site.css` (`.wp-block-buttons`).
   - The Navigation `<nav>` elements are block boxes.
3. **`theme.json` `styles`** also sets `spacing.blockGap: "0px"` and `elements.button` to B's base pill (red, 1.5px border, 10px 22px padding, Schibsted 600 16px). The core button element style can then never fight `site.css`.
4. **Editor style order:** `block_editor_settings_all` (priority 20, `inc/setup.php`) moves entries with `__unstableType: 'theme'` to the end of `$settings['styles']`. It touches no other key. **Plugin builder:** if your own `block_editor_settings_all` filter replaces `styles`, keep these entries.
5. **Seal images:** added `seal-ares-acs-92.png` and `seal-ares-acs-138.png` (a `srcset` for the 46px and 44px seals). The 400px file (215 KB) is kept but no longer loaded. The favicon fallback uses the 138px file.
6. **Footer:** "Privacy (coming)" is a paragraph beside the Navigation block, not the last `<li>` as in B. Integration round 1 wrapped both in a Group `footer__nav` that flows them as one inline run, so the note wraps with the links as in B (checked at 390, 768 and 1440: every link lands on B's pixel position).
7. **Short pages:** `.wp-site-blocks` is a flex column with a min-height of one window, so the footer sits at the window bottom on a short page (404, an empty members list) and is never pushed below it. This replaces B's members `min-height`, which the review flagged.
8. **"Later this season" and other B tables** are unchanged. On About, the stacked-table labels come from the header row ("What it means", where B typed "Means").

## Files

| File | What it is |
|---|---|
| `style.css` | Header only (Version 0.1.0, Requires at least 7.1, Requires PHP 8.3, Tested up to 7.1, Update URI false) |
| `theme.json` | §6.7 tokens: 19 colours, 3 gradients, 2 self-hosted families (variable woff2), 9 fixed sizes, spacing 10-70, `custom.radius`, `custom.mast-h`. Every editor design control is off (colour, typography, spacing, dimensions incl. `minWidth`, border, shadow, background incl. `gradient`, position, lightbox, `blockVisibility.allowEditing`, layout editing) |
| `assets/css/site.css` | B's `site.css` + the page `<style>` blocks, retargeted to block markup. Section numbers follow B's; unused round-2 sections are dropped (listed in the header comment) |
| `assets/fonts/` | `crimson-pro-roman.woff2`, `crimson-pro-italic.woff2` (wght 200-900), `schibsted-grotesk-roman.woff2` (wght 400-900). From the google/fonts repo, subset with fonttools to Latin + Latin Extended, all OpenType features kept. OFL texts included |
| `assets/img/` | `1920x1200sunset.jpg` (pattern preview + page-setup source), seal 400/138/92 |
| `templates/` | `index`, `front-page`, `page`, `page-members`, `page-documents`, `page-exercises`, `404`, `search`, as in §6.7. All eight parse as valid blocks (checked with `wp.blocks.parse` in wp-admin) |
| `parts/` | `header` (brand + Buttons `masthead__actions` > Button `btn--red masthead__join` + Navigation `nav` (the four links, then Buttons `site-header__actions` > Button `btn--red site-header__join`); two Join buttons so the markup order is the drawn order at every width, QA-103), `footer`, `members-nav` |
| `blocks/brand/` | `spokares-theme/brand`, `variant` header/footer. The block's wrapper attributes sit on the `<a>` itself (`a.brand` / `a.footer__seal`). `inserter: false` |
| `blocks/message-path/` | `spokares-theme/message-path`: inline SVG with the "relay, ends at the EOC" state baked in (hidden layers removed). No 147.300 MHz label; in-picture "A scenario" pill kept. `multiple: false`; `className` is supported (the pattern adds `needs-verify`) |
| `functions.php`, `inc/setup.php`, `inc/head.php`, `inc/render-filters.php` | Filters 1-9, the pattern categories, core and remote patterns off, the page excerpt feeding the meta description, `spk-verify`, the font preloads, the favicon fallback |
| `screenshot.png`, `readme.txt` | 1200×900 Home; credits and where things live |

## Render filters (all server-side)

1. `render_block_core/table`: for `table--stack`, adds `data-label` to each body `<td>`. The label comes from the header cell in the same column, counting `<th scope="row">` cells, so the first data column is right.
2. `render_block_core/group`: `steps` and `levels` get `role="list"`; `steps__item` and `level` get `role="listitem"`.
3. External links in paragraph, list item, heading, table, button, navigation link and quote output get class `ext` + `<span class="vh"> (opens {name})</span>`. Links that already carry `ext` are skipped. `{name}` is the host without `www.`, with `spokaneares-acs.groups.io` → "groups.io" and `app.leg.wa.gov` → "leg.wa.gov". Filter: `spokares_theme_link_host_name`.
4. `render_block_core/navigation-link`: `aria-current="page"` when the path matches; an `is-section` link also matches every path below it. Never on search or 404 pages, or for links with a `#`.
9. A no-break space between a digit and AM/PM/MHz/kHz/Hz, in text nodes only.

## For the patterns builder

- Your markup matched the vocabulary with no changes needed. Please keep to these points:
  - Don't rely on a Group's `layout` (it has no effect now).
  - Bold lead-ins are the first `<strong>` in the item.
  - The "Optional" pill is a `p.tag.tag--line` directly after `h3.steps__title` (the step is a two-column grid).
  - The first table column uses `<th scope="row">`.
  - `hasFixedLayout:false` isn't needed (the theme forces auto layout) but is harmless.
- A List item without a bold lead-in renders as plain text in the label's place (tested in stops, lines, seq and years). Stops share the red line whatever their number.
- About: a Buttons block that directly follows a `seq` list is indented under the step, as in B's join path.

## For the plugin builder

- `site.css` styles every §6.4 class. Additions:
  - `.rota-tbd` (muted).
  - `.hub-meeting-change` (amber cue). The `.is-view-next` wrapper owns the top rule and spacing.
  - `.spk-edit-hint` (amber dashed box) and `.spk-edit-link` (small, dashed underline).
  - Empty states are plain `.m-line`. `.is-view-next-up` has a 24px top margin whether it holds cards or an empty line.
- With the real plugin active (default options, no events or docs), every page rendered correctly with no PHP notices: the net bar with its Copy button, the rota with "Not yet published", the empty states, the TOC and the edit hints in the editor preview.
- The TOC wrapper is stretched to the full height of its column (`.longread__aside > .wp-block-spokares-toc { height: 100% }`), so `.toc--side` sticks for the whole page.

## For integration and the dev environment

- **Deep links:** all eight PLAN §6.13 #20 links cold-load exactly at their scroll margin (96px at 1440, 88px at 390, measured after the smooth scroll ends). No theme JavaScript is needed.
- **Admin bar:** the header sits under the admin bar at 1440 (32px) and at 700 (46px). At 390 there is no gap once the admin bar scrolls away.
- **Editor canvas:** fonts, hero, grids, buttons, spacing and the server previews match the front end. In post-only mode the editor shows the page title above the page's own H1. If the plugin opens pages in template-locked mode, the canvas shows the real header and footer and that duplicate title goes away. Optional; not a theme setting.
- **`?verify=1`:** needs a logged-in user with `edit_pages`. It outlines `.needs-verify` (amber-ink on light surfaces, amber on night).
- **Dev cache-busting:** `site.css` uses `filemtime` as its version on a `local` site, and `SPOKARES_THEME_VERSION` elsewhere.
- **Tested in a scratch Playground on port 9421** (not shipped, not in `dev/`). I used stub `spokares/*` blocks printing the §6.4 markup filled with round-3 data, and also ran with the real plugin. Shots at 1440 and a true 390 (Playwright + installed Chrome) match B's `shots/` except for the changes listed in PLAN §2.2 and §2.3 and the deviations above. Home at 1440 is 2,902px tall, the same as B's measurement. No horizontal scroll on any page at 360, 390, 1019, 1020 or 1100.

## Integration round 2 (2026-09-26)

Measured against B element by element (headless Chrome, 390, 768 and 1440). Home and Documents match B row for row (the only pixel differences are anti-aliasing and the seal's smaller source image); the other pages differ only by PLAN §2.2/§2.3 changes.

- **About tables** sat about 5px high: B's `.table-wrap` took `1.1em` of the 20px prose size, but the core Table figure's own em is the table's 15.5px. Now `calc(var(--fs-prose) * 1.1)`.
- **About, phones:**
  - The unit leadership table's call-sign column was 115px (B: 148px, the width of its "Open position" pill), so "AA7RT, W7UWC" wrapped. `.roles:has(+ .roles) td:last-child { min-width: 148px }`; the section table after it keeps 32%.
  - Task-book level cells are 31px tall, as B's (they hold the 28px marker).
  - Definitions sit 2px below their term, as B's `dl` row gap.
- **Editor canvas: the hero H1 no longer grows by an empty line** when the Cover is selected. Core outlines editable blocks with an absolutely positioned `::after`, and after `text-wrap: balance` text Chrome gave it a line of its own. Rich-text headings with `has-editable-outline` are grid containers in the editor only; the line breaks are unchanged (checked rect by rect).
- Unchanged by design: the "Stand by" readiness card is one line taller (the no-break space in "162.400 MHz"); About's link row wraps with 4px less gap than B's flex row.
