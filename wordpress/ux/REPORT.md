# Volunteer editor UX review: before and after

Final verification of the editor-workflow pass on the spokares.org build (v0.1.1 plus this pass), 2026-09-28. Walked as **Frank**, the Section Emergency Coordinator: in his 70s, comfortable with e-mail, Word and PDFs, not a WordPress user. The role was the ARES Editor with the Net Settings grant (`?dev_login=qa-ares-net`), on a fresh dev site on port 9570. The page version of this report is [index.html](index.html).

## The brief

> Do a workflow review across user journeys for volunteer editors. Walk every step (but only as one user role since we validated permissions). Check fields, instructions, clarity. Foundationally, you need to think hard about this and ask 'does this make sense?' Does the create step have the right guidance? Does edit align with create? Are we using simple, clear terminology? Is it clear from the menus where to go for different types of changes? Make it great. Fix any identified issues.

Owner's taste: clear labels, sensible field order and good defaults rather than more help text. One short hint beats a paragraph, and each thing is said once. The owner's directive, given mid-review: “'Rota' is a good example. Weird word, makes little sense. 'Net Control Schedule' makes sense.”

## Results at a glance

| | Before | After |
|---|---|---|
| First click right (20 tasks) | 18/20 | 19/20 |
| Tasks finished as intended | 17/20 | 20/20 |
| Clicks, all 20 tasks | 120 | 94 |
| Visible “rota” | menu, titles, buttons, Dashboard, guide, public table | none: 86 pages and screens swept, with the Help tabs, the photo window's templates and the scripts' strings |
| Tests | v0.1.1 QA pass | run-all.sh on a fresh site: checks 51/51, PHP 384/384, browser 169/169 · ops/ci.sh --no-zip: 12/12 (PHPCS 0 errors) |

The one first-click miss left is T14. “The ICS 309 log is ready at last” still reads as *add a document*, but the Add screen now says “Already a document: ICS 309 Communications Log (Soon). Open it”, so the job finishes in 7 clicks with no duplicate (13 before).

## Vocabulary (old → new)

| Before | After |
|---|---|
| Net rota, rota, Update the rota, Save rota | Net Control Schedule; Update the schedule; Save |
| Open, open slot | Volunteer needed |
| (no state: a note on a “Not posted yet” row) | No net |
| Not yet published | Not posted yet |
| Net details | Net Settings |
| Put back what was undone | Redo |
| Hub tiles, tiles, slot | Most Used; button 1–4 |
| Show under “Most used” | Also list under Most used |
| Events (menu) | Exercises & Events |
| Kind | Type of event |
| Short line for lists | Short description |
| Main link | Button: Web address, Words on the button |
| Extra form | Document members need (form); “You’ll need:” (public card) |
| Duplicate | Make a copy |
| Regular meetings (under Events) | Meetings › Cancel or Move a Meeting |
| Meeting rules | Meeting Schedule |
| Active; On Home and the members hub | Show on the site |
| (no way to say it) | Takes effect on; Postponed; Cancelled |
| Library section | Section |
| Not available yet (“Soon”) | Not ready yet (shows “Soon”) |
| Position in section (lower shows first) | Place in section (At the top / After “…” / At the end) |
| Replace a document | Replace or change a document |
| Update (button) | Save |
| Save as draft (takes it off the site); Move to Trash | Take it off the site |
| Status: Published: on the site | On the site · Not on the site (draft) · Not saved yet |
| held back | not saved (“Saved, except …”) |
| Pages, Page text, Edit Page | Page Text; Edit Page Text |
| Excerpt, Rename, Add note | (hidden: the webmaster's job) |
| Open Media Library, Select or Upload Media, Media Library, Upload files, Attachment Details | Choose a photo, Upload a photo, Photos on the site, This photo (verifier) |
| Howdy, …; ⌘K; “Thank you for creating with WordPress” | (the name only; no command palette; no credit) |
| Authentication Code, TOTP, Recovery Code | the code from my phone app; e-mail me a code; a printed backup code |
| administrator (in editor text) | the webmaster |

Internal names (option names, slugs, CSS classes, anchors, file names) keep the old words on purpose, so data, links and tests stay intact.

## Menu

| Before | After |
|---|---|
| **Dashboard** | **Dashboard** |
| **Net rota** (Net rota · Net details) | **Net Control Schedule** (Net Control Schedule · Net Settings*) |
| **Events** (All events · Add event · Regular meetings · Meeting rules) | **Exercises & Events** (All Events · Add an Event) |
| **Documents** (All documents · Add document · Hub tiles) | **Meetings** (Cancel or Move a Meeting · Meeting Schedule*) |
| **Pages** (All Pages (six pages; the three members pages don't open)) | **Documents** (All Documents · Add a Document · Most Used) |
| **Profile** | **Page Text** ((Home, How it works, About ARES & ACS)) |
|  | **Profile** |

\* Net Settings and Meeting Schedule need the grant. A plain ARES Editor sees the same menu without them, and meets a plain sentence (“ask the webmaster”) where they would be.

**Dashboard before:** 1 Net rota: Update the rota, Net details · 2 Events: Add an event, All events · 3 Meetings: Cancel or move a meeting, Meeting rules · 4 Documents: “37 in the library”, Add a document, Replace a document, Hub tiles · 5 Page text: Home · How it works · About ARES & ACS

**Dashboard after:** Net Control Schedule: “Posted through Tue, Oct 27 · Volunteer needed: Oct 6 and Oct 27”, Update the schedule, Net Settings · Exercises & Events: Add an event, Change or cancel an event · Meetings: Cancel or move a meeting, Meeting Schedule · Documents: “37 documents · 3 marked Soon”, Add a document, Replace or change a document, Most Used · Page Text: Home · How it works · About ARES & ACS · Needs attention first, when there is something

## First click and clicks, the 20 tasks

A first click passes when it is the matching Dashboard control, menu item or on-page link. Clicks count clicks and picks, not typing or scrolling.

| # | Task | First click before | First click after | Clicks before → after | Outcome before → after | What made the difference |
|---|---|---|---|---|---|---|
| T1 | Randy takes net control on Oct 13 (NZ2S) (B2) | ✓ Update the rota (hesitated at “rota”) | ✓ Update the schedule | 3 → 3 | Done → Done | “Saved Oct 13. See it on the For members page”. Undo sits beside “Last saved” in the title line. |
| T2 | Tom can't do Oct 20; nobody has offered (B3) | ✓ Net rota (menu) | ✓ Update the schedule | 3 → 3 | Done → Done | “Volunteer needed” is the choice, the public tag and the Dashboard's word. |
| T3 | No Tuesday net on Nov 3 (B5) | ✓ Net rota (menu) | ✓ Update the schedule | 4 → 4 | Partial: no “No net” state; the site said “Not yet published \| No net …” → Done | New “No net” choice. The notice adds “The For members page lists the next five Tuesdays.” |
| T4 | Net control through Dec 29 (B1, B7) | ✓ Net rota (menu) | ✓ Update the schedule | 18 → 11 | Done after retyping: “Show 13 more” lost the typing → Done | “Show 13 more Tuesdays” opens rows in place and keeps the typing. One notice: “Saved 8 Tuesdays, Nov 10 – Dec 29.” |
| T5 | State COMMEX moves to Sat, Oct 31 (D6) | ✓ All events | ✓ Change or cancel an event | 4 → 4 | Done, two notices → Done | A weekday shows beside First day. One notice says where it shows. |
| T6 | Not doing the Great ShakeOut (D8) | ✓ All events (hesitated: draft or Trash?) | ✓ Change or cancel an event | 3 → 4 | Done, but it silently vanished → Done | Tick Cancelled: members see “Cancelled” until the date passes. |
| T7 | Winlink class, Sat Nov 7, 9 to noon, County EOC (D2) | ✓ Add an event | ✓ Add an event | 10 → 10 | Done; the On-the-air line and the groups.io placeholder confused → Done | “Training or meeting”; the site shows “9:00 AM–noon”. |
| T8 | Lilac Parade on Sat, May 15 via NV2Z (D11) | ✓ All events | ✓ Change or cancel an event | 7 → 6 | Done after a wrong-year save hid it → Done | The weekday (“Fri”) flags the wrong year before saving. Starting from Add an event, “Already an event: … Open it” now catches it. |
| T9 | Oct 10 workshop is off (E1) | ✓ Cancel or move a meeting | ✓ Cancel or move a meeting | 4 → 4 | Done; the Dashboard still named Oct 10 → Done | “Saved: Sat, Oct 10 cancelled.” The Dashboard's Next skips it. |
| T10 | Holiday potluck instead of the Dec 12 workshop (E1, D5) | ✓ Cancel or move a meeting | ✓ Cancel or move a meeting | 5 → 3 | Partial: a note alone was refused → Done | A note on its own saves: “Saved: Sat, Dec 12 note posted.” |
| T11 | From January, Third Thursday moves to the 4th Thursday (E5) | ✓ Meeting rules (hesitated: “rules” read as bylaws) | ✓ Meeting Schedule (a slight pull toward “Cancel or move”) | 5 → 5 | Failed: no start date; reverted → Done | “Takes effect on” Jan 1: “Saved. From Fri, Jan 1, 2027: … first on Thu, Jan 28.” |
| T12 | Repeater tone is now 103.5 (C1) | ✓ Net details | ✓ Net Settings | 3 → 3 | Done → Done | “Saved: tone 103.5 Hz (was 100 Hz).” No confirm step. |
| T13 | Here's the new net script PDF (F4) | ✓ Replace a document | ✓ Replace or change a document | 5 → 7 | Done, but the site kept the old source and version → Done | “Upload a file instead”, the fresh “I checked this file” tick, the new version. The row shows “PDF Oct 2026”. |
| T14 | ICS 309 is ready; put it with the forms (F3) | ✗ Add a document (miss) | ✗ Add a document (still the natural click) | 13 → 7 | Done after publishing a duplicate and trashing it → Done, no duplicate | Typing “ICS 309” shows “Already a document: ICS 309 Communications Log (Soon). Open it”. The Dashboard's “3 marked Soon” is a direct route. |
| T15 | FEMA IS-100 with the training material (F2) | ✓ Add a document | ✓ Add a document | 7 → 2 | Done, but it duplicated a FEMA courses link → Done: it's already on the site | Typing the course shows “Already on the site: IS-100.c, under FEMA courses. Open it”. Adding it anyway takes 7 clicks and isn't held back. |
| T16 | Take down the Powerpole guide (F7) | ✓ Documents (menu) | ✓ Replace or change a document | 3 → 3 | Done → Done | The list's cue line says how; “Take it off the site” gives “… is off the site. Undo”. |
| T17 | ICS 309 in the ICS 214's place under Most used (F11) | ✗ Show under “Most used” on the document (miss) | ✓ Most Used | 10 → 3 | Done after a wrong button name went live → Done | Picking ICS 309 empties the old words; the placeholder shows what prints. |
| T18 | AA7RT replaces K7MHG as Hospital Coordinator (G4) | ✓ Page text › About (a guess) | ✓ Page Text › About ARES & ACS | 4 → 3 | Done; Table-block jargon in the sidebar → Done | “Saved. It’s on the site now.” The sidebar starts closed except for About's review box. |
| T19 | Add the Lilac Parade to Home (G1) | ✓ Page text › Home | ✓ Page Text › Home | 3 → 3 | Done → Done | Unchanged, and still easy. |
| T20 | New SET photo on Home (G6) | ✓ Page text › Home | ✓ Page Text › Home | 6 → 6 | Done, but the editor stayed “unsaved” → Done, editor clean | Replace offers “Choose a photo” and “Upload” (was 5 choices). With a description: Replace › Choose a photo › Upload a photo › describe › Select: 9 clicks. |

Clicks went up in three tasks, on purpose. T6 calls an event off with a tick and Save instead of the Trash, so members see “Cancelled”. T13 adds the fresh privacy tick and the version, which the old walk skipped and left stale on the site. T20 with a description goes through the photo window (9 clicks), while the quick Upload path is still 6.

## Other journeys (after)

| Journey | What Frank did | What happened |
|---|---|---|
| B2 | Name typed with the call (“Randy NZ2S”), then a name only | Oct 27 saved as NZ2S (“names aren’t posted”); Nov 17 not saved, outlined, “Type a call sign, like NZ2S, not a name.” One amber notice. |
| B4 | Winlink assignment with Other… form | 4 clicks; shows under Winlink assignments with “ARRL Radiogram”. |
| B6 | Undo, then Redo | Both in the title line: “Undone: Dec 22 is back as it was.” / “Redone: …”. |
| C2, C3 | Net time 7:30 PM; a short frequency | “Saved: net time 7:30 PM (was 8:00 PM).” A bad frequency says why in place of its hint. |
| D4 | Public service event | “As requested” appears only for Public service; “Volunteer through (call sign)”. |
| D7 | Postpone the SET, then put it back | “It shows as Postponed on the Exercises & events page.” |
| D10 | Same event next year | “Make a copy” in the Save box and the row: “Copied. Set the new date, then Publish.” Taking the copy off: “… is in the Trash.” (verifier fix) |
| D13 | A phone number in Where | Amber “Saved, except Where: it has a phone number. …” (verifier: was red), the tick “This is a public agency number. Publish it.” under the field. |
| E2, E3 | Move Oct 15 to Oct 14; take back Oct 10's cancel | One tick un-cancels and clears its note: “Sat, Oct 10 is back on; Thu, Oct 15 moved to Wed, Oct 14.” |
| F1, F8 | Upload a new file and put it first | 8 clicks; Place in section “At the top” (was: guess each neighbour's number). |
| F10 | A Most Used document's options | “It’s button 1 on the Most Used screen.” replaces the tick. |
| — | “Here's the new net script” from the Most Used screen | “Replace or change it” opens the net script's form in 1 click. |
| G9 | About's review box | Only on About, open on arrival; no move or fold buttons (verifier). |

## Screens, before and after

Before: `shots/walk-*` (the v0.1.1 walk). After: `shots/final/` (this walk). Both folders are git-ignored, and regenerating them takes one fresh dev site.

### Dashboard

Numbered badges, “Net rota”, “Hub tiles”, “Howdy,”, ⌘K and the WordPress credit are gone; each section is named after its screen, with an add and a change button.

| Before | After |
|---|---|
| [![before](shots/walk-pages/02b-dashboard-full.png)](shots/walk-pages/02b-dashboard-full.png) | [![after](shots/final/s01-dashboard.png)](shots/final/s01-dashboard.png) |

### Net Control Schedule

“Net rota” → “Net Control Schedule”. The Shows column is gone; four choices (Call sign box, Volunteer needed, No net, Not posted yet); “ICS-213 (usual)”; Save.

| Before | After |
|---|---|
| [![before](shots/walk-rota-net/02b-rota-full.png)](shots/walk-rota-net/02b-rota-full.png) | [![after](shots/final/s02-net-control-schedule.png)](shots/final/s02-net-control-schedule.png) |

### Show 13 more (before: typing lost)

Rows open in place and keep what was typed.

| Before | After |
|---|---|
| [![before](shots/walk-rota-net/09-b7-after-show13more-typing-lost.png)](shots/walk-rota-net/09-b7-after-show13more-typing-lost.png) | [![after](shots/final/t04-typed-all.png)](shots/final/t04-typed-all.png) |

### Net Settings

Preview box and confirm step gone; a “Members see” line under each group; one select per Tuesday of the month.

| Before | After |
|---|---|
| [![before](shots/walk-rota-net/40b-net-details-full.png)](shots/walk-rota-net/40b-net-details-full.png) | [![after](shots/final/s03-net-settings.png)](shots/final/s03-net-settings.png) |

### Exercises & Events list

Columns When · Event · Type of event · Where it shows; no Trash row action, bulk actions, row ticks or Screen Options for editors.

| Before | After |
|---|---|
| [![before](shots/walk-events/02-all-events-upcoming.png)](shots/walk-events/02-all-events-upcoming.png) | [![after](shots/final/s04-events-list.png)](shots/final/s04-events-list.png) |

### Add an Event

Type of event first and required; Event name labelled; one short hint; Save draft / Publish.

| Before | After |
|---|---|
| [![before](shots/walk-events/10-add-event-blank.png)](shots/walk-events/10-add-event-blank.png) | [![after](shots/final/s05-event-add.png)](shots/final/s05-event-add.png) |

### Add an Event: name check (new)

“Already an event: Lilac Festival Armed Forces Torchlight Parade (Sat, May 15, 2027). Open it”.

| Before | After |
|---|---|
| [![before](shots/walk-events/10-add-event-blank.png)](shots/walk-events/10-add-event-blank.png) | [![after](shots/final/t08b-already-an-event.png)](shots/final/t08b-already-an-event.png) |

### Edit Event

Same fields and order as Add; Cancelled under When; Save, Make a copy, Take it off the site.

| Before | After |
|---|---|
| [![before](shots/walk-events/30-edit-state-commex-open.png)](shots/walk-events/30-edit-state-commex-open.png) | [![after](shots/final/s06-event-edit-set.png)](shots/final/s06-event-edit-set.png) |

### Cancel or Move a Meeting

Own Meetings menu; “Note (shown with the date)”; a note on its own is allowed.

| Before | After |
|---|---|
| [![before](shots/walk-events/80-meetings-screen.png)](shots/walk-events/80-meetings-screen.png) | [![after](shots/final/s07-meetings-cancel-move.png)](shots/final/s07-meetings-cancel-move.png) |

### Meeting Schedule

“Meeting rules” → “Meeting Schedule”; one “Show on the site” tick; “Takes effect on”.

| Before | After |
|---|---|
| [![before](shots/walk-events/91-meeting-rules.png)](shots/walk-events/91-meeting-rules.png) | [![after](shots/final/s08-meeting-schedule.png)](shots/final/s08-meeting-schedule.png) |

### Documents list

Cue line, site order on one page, Source column, a Soon view; Screen Options gone for editors.

| Before | After |
|---|---|
| [![before](shots/walk-docs-tiles/02-all-documents.png)](shots/walk-docs-tiles/02-all-documents.png) | [![after](shots/final/s09-documents-list.png)](shots/final/s09-documents-list.png) |

### Add a Document

Labelled name; “Section (required)”; “I checked this file” unlocks the chooser; Short note (60 characters).

| Before | After |
|---|---|
| [![before](shots/walk-docs-tiles/10-add-empty.png)](shots/walk-docs-tiles/10-add-empty.png) | [![after](shots/final/s10-document-add.png)](shots/final/s10-document-add.png) |

### Add a Document: name check

“Already a document: ICS 309 Communications Log (Soon). Open it”; course links are found too (verifier).

| Before | After |
|---|---|
| [![before](shots/walk-docs-tiles/10-add-empty.png)](shots/walk-docs-tiles/10-add-empty.png) | [![after](shots/final/t14-add-name-check.png)](shots/final/t14-add-name-check.png) |

### Add a Document: a course that's already listed

Before: IS-100 was published again next to FEMA courses. After: typing it says “Already on the site: IS-100.c, under FEMA courses. Open it” (verifier).

| Before | After |
|---|---|
| [![before](shots/walk-docs-tiles/33-public-training-section.png)](shots/walk-docs-tiles/33-public-training-section.png) | [![after](shots/final/t15b-already-on-the-site.png)](shots/final/t15b-already-on-the-site.png) |

### Edit Document (net script)

“Upload a file instead” on a link; the tick names what was checked; Take it off the site.

| Before | After |
|---|---|
| [![before](shots/walk-docs-tiles/51-net-script-edit.png)](shots/walk-docs-tiles/51-net-script-edit.png) | [![after](shots/final/t13-net-script-open.png)](shots/final/t13-net-script-open.png) |

### Most Used

“Hub tiles” → “Most Used”; rows are buttons 1–4 with “Replace or change it”.

| Before | After |
|---|---|
| [![before](shots/walk-docs-tiles/80-hub-tiles.png)](shots/walk-docs-tiles/80-hub-tiles.png) | [![after](shots/final/s12-most-used.png)](shots/final/s12-most-used.png) |

### Page Text list

Three pages, Title only; no Screen Options (verifier).

| Before | After |
|---|---|
| [![before](shots/walk-dashboard-pages/14-pages-list.png)](shots/walk-dashboard-pages/14-pages-list.png) | [![after](shots/final/s13-page-text-list.png)](shots/final/s13-page-text-list.png) |

### Home photo: Replace menu

Five choices → “Choose a photo” and “Upload” (verifier renamed Open Media Library).

| Before | After |
|---|---|
| [![before](shots/walk-dashboard-pages/26b-photo-replace-menu.png)](shots/walk-dashboard-pages/26b-photo-replace-menu.png) | [![after](shots/final/t20-replace-menu.png)](shots/final/t20-replace-menu.png) |

### Photo window

“Choose a photo”, “Upload a photo”, “Photos on the site”, “This photo”, “Describe the photo in a few words”; no Edit Image (verifier).

| Before | After |
|---|---|
| [![before](shots/walk-dashboard-pages/26c-media-window.png)](shots/walk-dashboard-pages/26c-media-window.png) | [![after](shots/final/t20-media-window.png)](shots/final/t20-media-window.png) |

### Profile

No Nickname or capabilities rows; “Name shown to other editors”.

| Before | After |
|---|---|
| [![before](shots/walk-dashboard-pages/07-profile-full.png)](shots/walk-dashboard-pages/07-profile-full.png) | [![after](shots/final/s14-profile.png)](shots/final/s14-profile.png) |

### For members (public, signed in)

“Volunteer needed” tags; “Edit this list in Net Control Schedule”; Most used buttons.

| Before | After |
|---|---|
| [![before](shots/walk-rota-net/04-members-signed-in.png)](shots/walk-rota-net/04-members-signed-in.png) | [![after](shots/final/p04-members-signed-in.png)](shots/final/p04-members-signed-in.png) |

### Exercises & events (public, signed in)

Cancelled and Postponed tags; “You’ll need:” on the Next up cards.

| Before | After |
|---|---|
| [![before](shots/walk-events/05-public-exercises-signed-in.png)](shots/walk-events/05-public-exercises-signed-in.png) | [![after](shots/final/p05-exercises-signed-in.png)](shots/final/p05-exercises-signed-in.png) |

### iPad: Net Control Schedule

The menu keeps its labels at 820 px; sticky Save.

| Before | After |
|---|---|
| [![before](shots/walk-rota-net/31-ipad-rota-top.png)](shots/walk-rota-net/31-ipad-rota-top.png) | [![after](shots/final/i02-net-control-schedule.png)](shots/final/i02-net-control-schedule.png) |

### Phone: Net Control Schedule

One card per Tuesday with a 16 px date; Save stays at the bottom of the window.

| Before | After |
|---|---|
| [![before](shots/walk-rota-net/31b-phone-rota-full.png)](shots/walk-rota-net/31b-phone-rota-full.png) | [![after](shots/final/m02-net-control-schedule.png)](shots/final/m02-net-control-schedule.png) |

## Findings disposition

The five walkers raised 114 findings: NET 24, EVT 26, DOC 19, PG 20 and FC 25. The spec (`SPEC.md` §6) records a decision for each. 95 were accepted, 12 accepted in part and 7 accepted with a change, so every finding led to a change. The parts not taken, with the reason for each, are in §6: a second undo system for Net Settings, a Save box above the fields, autofocus on search, and hiding the focal point.

**Fixed by the verifier in this pass** (from the fresh walk):

| Fix | Why | Files |
|---|---|---|
| Add an Event checks the name | “Already an event: … (date). Open it”, as Add a Document does, so the Lilac Parade isn't added twice (T8 from Add). Create now guides like it does for documents. | admin-events.php, admin-events.js, admin-events.css |
| Add a Document's name check knows course links | Typing “FEMA IS-100” or the course title says “Already on the site: IS-100.c, under FEMA courses. Open it” (T15 was adding a duplicate). Name-only drafts left by WordPress's autosave are skipped. | admin-documents.php, admin-documents.js |
| “Open it” leaves cleanly | Found in T14: clicking “Open it” asked “Leave site?” and left an “ICS 309” draft behind. The focus now stays in the name box and the leave prompt is off for that link. | admin-documents.js, admin-events.js |
| Photo window in plain words | Replace › “Choose a photo”; tabs “Upload a photo” and “Photos on the site”; side panel “This photo”; “Search photos”; no Edit Image. The refusal sentence and the guide use the same words. | governance.php, editor-guard.js |
| No WordPress chrome on the lists | No Screen Options on the Exercises & Events, Documents and Page Text lists for editors; the events list has no bulk actions (its only one was “Move to Trash”) and no row ticks. | admin-trim.php, admin-events.php |
| Save box and Page review box can't be moved or folded | Their Move up, Move down and fold buttons are gone for editors, as on the Dashboard. | admin.css |
| A partly saved event is amber | “Saved, except Where: …” was red on events and amber everywhere else; now amber, and a warning takes the focus when there's no error. | admin-events.php, admin-forms.js |
| A draft taken off says so | “… is in the Trash.” for a draft or a fresh copy (it was never on the site), as documents already did. | admin-events.php |
| Help uses the tick's words | Documents Help: tick “I checked …” instead of “the privacy check”. | admin-common.php |

Tests for these fixes: `dev/tests/php/ux-verifier-polish-test.php` (6) and `dev/tests/e2e/ux-verifier-polish.test.mjs` (4). The wording changes in `qa-003`, `qa-019`, `qa-054`, `ux-events-meetings` and `ux-page-text-editor` swap the words and the notice colour; no check was weakened.

**Left open, for the owner:**

- Home's page text calls groups.io “the members’ hub for files and the calendar”. It's page content that editors can change on the Page Text screen, so it was left for the owner.
- The Two-Factor section of Profile keeps the plugin's own long sentences (“Configure a primary two-factor method…”, “Enable Authenticator App”, “Recommended”). The sign-in code screen was already reworded.
- The block editor's own controls remain: Document Overview, Zoom Out, and the word count in the sidebar. They are WordPress's controls, and none of them can break the page.
- The public card label “You’ll need:” was an implementer's choice. The owner hasn't picked it.
- The meeting is still named “Third Thursday training meeting” after it moves to the 4th Thursday. That is content, and it can be renamed on its Meeting Schedule card.
- On Most Used, a new button's words default to the document's full name (“ICS 309 Communications Log”) unless words are typed. The placeholder shows the name first.
- On an iPad in portrait, the Winlink assignment box shows two lines, so a long assignment scrolls inside it.
- If Frank types a name on an Add screen and then leaves another way, WordPress's autosave can still leave a draft. Only the “Open it” path is covered.

## Test results

- dev/tests/run-all.sh 9570 on a fresh site, after every fix: checks.sh 51 passed, 0 failed; PHP integration 384 passed, 0 failed; browser (e2e) 169 passed, 0 failed (1,096 s). run-all: all passed.
- ops/ci.sh --no-zip: 12 passed, 0 failed. Versions, forbidden code, ABSPATH guards, seed data, redirect map, JSON and JS parse, PHP 8.3 and 8.4 lint, PHPCS WordPress-Extra with 0 errors and 15 warnings (the baseline count), Plugin Check with no errors, and the “no visible rota” guard.
- New in this pass: dev/tests/php/ux-verifier-polish-test.php (6 tests) and dev/tests/e2e/ux-verifier-polish.test.mjs (4 tests), both passing.
- “rota” sweep: 86 pages and screens as a visitor, as the grant editor and as a plain editor. It covered every same-origin public page, every editor screen, the three block editors and their canvases, the sign-in page and the 404 page, including Help tabs, templates and script strings. It found no visible “rota”; the word appears only in identifiers (`spokares-rota`, `#rota`, `rota[…]`, `spk-rota-*`).

## Guide and plan

`EDITING-GUIDE.md` now matches the screens word for word. The photo step reads “Replace › Choose a photo … Upload a photo … Select”, and Job 2 tells Frank to click **Open it** when the event already exists. Its six screenshots in `shots/final/` were regenerated with `dev/shots.sh` and checked against their alt text. `PLAN.md` §3.4 and §4.2/§4.4 record the name checks, the photo-window words, the lists without WordPress chrome and the amber partial save.
