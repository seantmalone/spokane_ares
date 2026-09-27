# Build notes: patterns

Owner: patterns builder. Writes only `wordpress/theme/spokares/patterns/*.php`.
Checked against WordPress 7.1.2 / PHP 8.4 in Playground, with the real `spokares` theme and `spokares-core` plugin active.

## Deviations from the contract (read first)

1. **The three editable pages leave out attributes that equal the block default.** §6.9 says "self-closing with an explicit `view`". But when the block editor saves a page, it drops every attribute that equals its default. Measured: `{"view":"home"}` on `spokares/meetings` comes back as no attributes. So Home's `spokares/meetings` has no `view` (the default is `home`), and About's `spokares/toc` has no `labels` (the default is exactly the contract's labels). Every other dynamic block on Home, How it works and About sets a view that isn't the default, so it is written out. The members bodies (`members-*`) keep every view written out, as the contract says, because editors never save them (they live in templates). The plugin's skeleton check already ignores attributes that equal their default (`governance.php`), so both spellings pass. This choice makes the page patterns match the editor's own serialization exactly.
2. **`needs-verify` on the message-path block** is set with `"className":"needs-verify"`. The theme block declares `className` support and prints its wrapper attributes on the `<figure>`, so the editor keeps the class.
3. **The levels group has `"ariaLabel":"Readiness levels, from normal to deployed"`** (B's `aria-label`; Group supports `ariaLabel` in 7.1). Together with theme filter 2 it gives the list a name. No other extra attributes.
4. **B inline markup is kept where it survives the editor's rich-text round trip** (verified): `<span class="vh">Step N: </span>` in the four step titles; `<span class="vh">Level N: </span>` in the task-book row headers; `<span class="h3-note">` in "ACS (optional)" and "ACS Position Task Book (2024-08-09)"; `<span class="nowrap">` around IS-120, IS-235 and (S-RADO); `Spokane County Emergency Management<br><small>Director: the Sheriff</small>` in the chain (the contract's `<br>`, plus B's `<small>`, which B styles `display:block`). `<abbr title>` everywhere B had it.

## Files (28)

- Home: `home-hero`, `home-what-we-do`, `dawn-hinge`, `home-visit`, `home-join`; page `page-home`.
- How it works: `how-opener` (its section plus the `dawn-sky how-dawn` spacer), `how-nets`, `how-activation`, `how-messages`, `how-exercises`, `how-roles`; page `page-how-it-works`.
- About: `about-head` (the `dawn-sky about-sky` spacer plus the head section), `about-body` (the TOC aside plus a main column that includes the nine chapter files), chapters `about-ares-acs`, `about-legal-basis`, `about-who-we-serve`, `about-leadership`, `about-joining`, `about-training`, `about-licensing`, `about-history`, `about-contact`; page `page-about`.
- Members bodies: `members-hub`, `members-documents`, `members-exercises`.

Each header has Title, Slug `spokares/<file>`, Categories, **Inserter: no**, Viewport Width 1440 and Description. The page patterns add `Post Types: page` and have no `Block Types`. Page and body files build their markup with `include __DIR__ . '/<section>.php'`, so each section's markup exists in one file only. No `wp:pattern` block, no `metadata.patternName` and no `templateLock` anywhere.

## Verified

- **Validity and round trip:** the block editor parses every pattern and every created page with no invalid blocks and no missing block types. For Home, How it works and About, re-serializing in the editor gives back the stored content unchanged (only a kses `/>` → ` />` difference).
- **Editor saves under the real lock** (plugin active, as `ares_editor`, over REST):
  - A text edit, an added stop in a list, a full editor re-serialization and a hero-photo replacement all save.
  - Moving a section, changing a class and changing a dynamic block's `view` are refused with `spokares_layout_locked`.
  - Through the block editor itself (`savePost`), a paragraph, a list item and a table cell were edited on each of the three pages. Each save succeeded ("Page updated."), and `<abbr>` and the hidden `vh` text were kept.
- **Front end** (real theme and plugin):
  - Each of the six pages has one `<h1>`, every §2.4 anchor and no `href="#"`.
  - The About TOC prints B's nine entries exactly.
  - Table labels and `role="list"` are added by the theme filters.
  - The debug log stays empty with `WP_DEBUG` on.
- **Inserter:** all 28 patterns come back from REST with `inserter:false`. None appears in the inserter or the new-page starter window. A control pattern with the inserter on does appear, so the test works.
- **Words:** a word-level diff against B's built pages shows only three kinds of difference: text the plugin prints, numerals that are now CSS counters, and figcaption position. The new visible words are "Radio settings" (§2.3) and "Open position." (§2.2 #17). Word counts from the patterns alone are Home 189, How 797, About 1,170, hub 12, Documents 22 and Exercises 57. With the plugin's output added, the pages come to B's counts of 251 / 877 / 1,239.
- **`needs-verify`:** every static `data-verify` in B's three public pages has a counterpart, placed on the smallest block. The footer's flag belongs to the theme. Flags on dynamic lines belong to the plugin.

## For the plugin builder

- **Hero photo replacement (measured by driving Cover › Replace › Upload in 7.1.2).** Replacing the photo changes `url`, `id` and `sizeSlug`. It clears `focalPoint` and `useFeaturedImage`. It sets `isDark` (false for a light photo), `customOverlayColor` (the photo's average colour) and `isUserOverlayColor:false`, and adds the class `is-light`. `governance.php` already allows all of these. Please also allow **`overlayColor`**: if the average colour exactly matches a palette colour, Gutenberg stores that colour's slug in `overlayColor` instead of `customOverlayColor`.
- Every block type the patterns use is in `spokares_allowed_block_types()`: cover, group, heading, paragraph, buttons, button, list, list-item, spacer, separator, table, quote, `spokares/*` and `spokares-theme/message-path`.

## For the dev builder (page setup)

- Read content from the registry as §6.10 says. The hero Cover has no `id`, and the theme URL (`get_theme_file_uri( 'assets/img/1920x1200sunset.jpg' )`) appears twice: in the comment's `"url"` and in `<img src>`. The verified swap:
  - replace both URLs;
  - insert `"id":N,` right after the `"url":"…",` value;
  - change `class="wp-block-cover__image-background"` to `class="wp-block-cover__image-background wp-image-N"`.
  The result parses as valid and re-serializes to itself.
- Page titles saved without `unfiltered_html` are stored as `About ARES &amp; ACS`, which is normal.

## For the theme builder (CSS the patterns rely on)

- **Table cells can't carry classes** (a `<td class="call">` makes the table invalid). B's `.roles td.call` must become `.roles td:last-child`. All tables set `"hasFixedLayout":false`, so core's `has-fixed-layout` class is not printed.
- **`.hero.is-light`:** after a light photo is swapped in, core's `.wp-block-cover.is-light` rule turns the text dark. Keep the hero text white whatever `is-light` says.
- **Counters:** `ol.seq` on About is split around the application button. It is `ol.seq` (item 1), then Buttons, then `<ol start="2" class="… seq">`. The counter must honour `start`, for example with `counter(list-item)`. Task-book numerals come from a counter on `.tb tbody tr`. Hops, levels and steps (from 0) are CSS counters, as in §2.2 #3.
- **Rules from B's page styles** that the markup still uses: `.prose .h3-note` (**still missing from `site.css`**; the markup has `<span class="h3-note">(optional)</span>` and `(2024-08-09)`), `.nowrap`, `.chain li small`, and `.chains__team strong`. B's rule was `.chains__team b`; the pattern uses `<strong>` because the editor turns `<b>` into `<strong>`. `.linkrow` puts its links next to each other with no wrapper, to fit the theme's `.prose .linkrow a + a` rule; B's `<span>` wrapper was dropped.
- **Seen in the side-by-side with B's shots** (1440 and 390; real theme and plugin; theme CSS as of 19:50):
  - Red buttons show a square red box. The link itself is a pill (radius 999px), but the bare `.btn--red` selector also matches the Button block's wrapper `<div class="wp-block-button btn--red">`, and the contract puts the class on that wrapper. Scope the rule, e.g. `a.btn--red, button.btn--red, .wp-block-button.btn--red > .wp-block-button__link`. This also affects the header's Join button.
  - Join step 1: "One form." sits beside "Join ARES". `.steps__item` is a wrapping flex row, and `.steps__text { max-width: 54ch }` shrinks the flex basis, so a short line fits beside the title. B puts it underneath.
  - About definitions: "Auxiliary Communications" drifts down. `.def__text` spans two auto rows, so its height is split between them. `grid-template-rows: auto 1fr` fixes it.
  - About `.bleed` tables: `margin-right: -308px` is applied, but the figure stays 664px wide (the parent's width), so it doesn't extend to the right as in B. Something sets the figure's width.
  - How it works: each heading sits closer under its `.redline` than in B.
  - Everything else (surfaces, hero, stops, dawn hinge, Come visit card and meetings, steps rail, opener grid and figure, netbox, run-sheet cue, levels, roles table, TOC rail, chains figure, members hub skeleton) matches B's layout. No horizontal scroll at 390 on any page.
- `how-open__line` uses `<br>` (per the contract) instead of B's `span{display:block}`.
- `.how-close` is a Group holding a paragraph "Ready?" and a Buttons block.
- Paragraphs with anchors print `class` before `id`, and the levels group prints `aria-label` before `class` (7.1.2's serializer order). This only matters to anyone hand-editing the markup.
- External links in patterns are plain `<a href>`. Theme filter 3 adds `ext` and the hidden "(opens …)" text. Checked: the groups.io link gets "(opens groups.io)".

## Rules for whoever edits these files later

- Keep each page pattern exactly as the 7.1.2 editor would save it. Leave out default-valued attributes, write the anchor both as `"anchor"` in the comment and as `id` in the HTML, and write `--` inside comment JSON as `--`. The quick test: open the created page in the editor as admin; saving without changes must not change the stored markup.
- Top-level blocks of Home, How it works and About, and every dynamic or theme block on them, carry `"lock":{"move":true,"remove":true}`. The About chapters are nested inside the locked `about-body`, so they carry no lock. The members bodies carry no locks.
- Pattern text is literal English block markup, with no gettext calls, so an administrator can paste a section's markup into the code editor (§6.9). Core translates pattern titles with the theme's text domain.
- Bump the theme `Version` when a pattern file is added or renamed. WordPress caches pattern headers by version outside `WP_DEVELOPMENT_MODE=theme`.
