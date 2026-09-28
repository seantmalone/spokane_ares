# Editor terminology and menu inventory (v0.1.1)

Every word a volunteer editor meets, where it appears, what it means and what the public site calls the same thing. Then the problems: two words for one thing, one word for two things, WordPress and internal jargon, surprising menu places, and repetition.

**How it was made (2026-09-27).** A fresh dev site on port 9541, signed in as `qa-ares-net`: an ARES Editor with the Net details grant, so it sees everything a volunteer can. The walk opened every menu item, list view, form (for each event kind and document source), Help tab and Screen Options panel. It triggered the notices with real saves on every form: success, error, nothing changed, undo, duplicate, trash and restore, plus the warnings in the block editor. It read all six public pages signed out and signed in, and scanned every translatable string in the theme and plugins for conditional text the walk didn't trigger. Raw data: `ux/glossary-raw.json`. Screenshots: `ux/shots/glossary/` (git-ignored).

Flag codes in the tables point to section 3: **I** inconsistency, **J** jargon, **M** menu structure, **R** repetition, **O** other.

---

## 1. The map as the editor sees it

**Admin menu** (top to bottom; * = Net details grant only)

| Menu | Icon | Submenu |
|---|---|---|
| Dashboard | dashboard | none |
| Net rota | microphone | Net rota · Net details* |
| Events | calendar | All events · Add event · Regular meetings · Meeting rules* |
| Documents | document | All documents · Add document · Hub tiles |
| Pages | page | none (the list of 6 pages) |
| Profile | person | none |
| Collapse Menu | | |

A plain ARES Editor gets the same menu without Net details and Meeting rules. Net rota then has no submenu.

**Admin bar.** In wp-admin: *Spokane County ARES-ACS* (with *Visit Site*), *⌘K* (with *Open command palette*), *Howdy, {name}* (with *Edit Profile* and *Log Out*). On the site: *Dashboard*, *Edit Page* (Home, How it works and About only), and **Update lists** (with Net rota, Events, Regular meetings, Documents and Hub tiles).

**Dashboard: the "Site tasks" card.** It has five numbered sections:
1. **Net rota**: "Posted through Tue, Oct 27 · 2 open slots (Oct 6, Oct 27)", with the button *Update the rota* and the link *Net details*.
2. **Events**: "Happening now: …", "Next: …, Sat, Oct 3 · 10 more", with the button *Add an event* and the link *All events*.
3. **Meetings**: "Next: Second Saturday Workshop, Sat, Oct 10, 9:00 AM", with the button *Cancel or move a meeting* and the link *Meeting rules*.
4. **Documents**: "37 in the library", with the buttons *Add a document* and *Replace a document*, and the link *Hub tiles*.
5. **Page text**: links to Home, How it works and About ARES & ACS.

A **Needs attention** box appears when there is a problem. The card ends with "Stuck? webmaster@spokares.org". The panel has its own buttons: *Move up*, *Move down*, *Show or hide panel*. The page footer says "Thank you for creating with WordPress." and "Version 7.1.2".

**Public site.** The navigation reads Home · How it works · About ARES & ACS · **For Members** · Join the team. The members area has three pages. **For members** has the sections Tuesday net, This week and Most used. **Exercises & events** has Next up, Later this season, Winlink assignments, Public-service events and Past exercises. **Documents & forms** has the library, with the filters All, Most used and the seven sections. Signed-in editors see a link under each list: "Edit this list in {screen}" or "Edit these settings in Net details".

---

## 2. Glossary

### Getting around

| Term | Where it appears | What it means | Public-site word | Flag |
|---|---|---|---|---|
| Dashboard | menu; admin bar on the site | the start screen with Site tasks | (none) | |
| Site tasks | Dashboard card title; Dashboard Help | the job list with a button for each job | (none) | |
| Update lists | admin bar on the site | menu of the list screens | (none) | |
| Help → "How to" | tab on every custom screen | 3-5 lines per screen plus the webmaster's address | (none) | M8 |
| Help → "Overview", "Managing Pages" | Pages list (WordPress's own text) | posts, "chronological blog stream", "Parent", "metadata" | (none) | J10 |
| Screen Options: Columns, Pagination, Number of items per page, View mode: Compact view / Extended view | Events, Documents and Pages lists | WordPress list settings | (none) | J9 |
| Bulk actions, Apply, Select All | Events, Documents and Pages lists | act on several rows at once | (none) | J9 |
| View on site / View / View Page / Visit Site | h1 buttons, row actions, block editor, admin bar | open the public page | (none) | I13 |
| See it on {page} | end of every success notice | a link to where the change shows | page names | |
| Edit this list in {screen} / Edit these settings in Net details | under each list on the public site (signed in) | shortcut to the admin screen | (none) | |
| Edit in wp-admin › {screen} | block editor canvas (Home: Net details, Regular meetings; How it works: Net details ×2) | the same shortcut, as plain text | (none) | J7 |
| Howdy, {name} / Edit Profile / Log Out | admin bar | account menu | (none) | J12 |
| ⌘K Open command palette | admin bar | WordPress search-anything | (none) | J12 |
| Collapse Menu | bottom of menu | shrinks the menu | (none) | J12 |
| Thank you for creating with WordPress. / Version 7.1.2 | footer of every admin screen | WordPress credit | (none) | J12 |
| Stuck? webmaster@spokares.org | Dashboard, every Help tab | who to ask | Contact (footer) | |
| webmaster / administrator / Emergency Coordinator | notices and hints | the person who can change what an editor can't | (none) | I8 |

### Tuesday net

| Term | Where it appears | What it means | Public-site word | Flag |
|---|---|---|---|---|
| **Net rota** | menu (both levels), h1, Dashboard heading, admin bar, "Edit this list in Net rota" | who runs net control each Tuesday, plus the Winlink assignment and a note | "Net control, next five Tuesdays" (For members › Tuesday net); "Winlink assignments" (Exercises & events) | I1, J1, M3 |
| Update the rota / Save rota | Dashboard button / rota button | open / save the rota | (none) | I1, I7 |
| Posted through {date} · N open slots ({dates}) | Dashboard | how far ahead net control is filled | (none) | |
| Nothing posted for the coming Tuesdays. / The rota runs out in under 3 weeks. | Dashboard, Needs attention | rota warnings | (none) | |
| Tuesday · Net control · Shows · Winlink assignment · Form · Note | rota columns | columns | Tuesday · Net control · Note; Date · Assignment · Form | |
| Call sign / Open / **Not posted yet** | rota choices per Tuesday | the three states of a Tuesday | call sign / "Open" / "**Not yet published**" | I2 |
| Shows: "**Not yet published**" | rota Shows column | what the public sees for "Not posted yet" | "Not yet published" | I2 |
| Simplex / Winlink night / GMRS net, 7:30 PM | rota row tags | special Tuesdays (set in Net details) | Note column: Simplex, Winlink night, GMRS net, 7:30 PM | |
| — none — / Other… / Form name | rota Form dropdown | Winlink form type (ICS-213, DYFI…) | Form | I6 |
| Showing 13 Tuesdays from this week. Show 13 more | under the rota | load more weeks | (none) | |
| Undo last save ({name}, {when}) / Put back what was undone | rota button | one-level undo and redo | (none) | |
| Last saved {when} by {name} | rota h1 line | last save stamp | (none) | |
| Saved N Tuesday(s). / {date}: saved AG7QP only. One call sign per Tuesday; names are never stored. / Nothing changed, so nothing was saved. / Undone: … / Put back: … | rota notices | save results | (none) | |
| **Net details** | Net rota submenu, Dashboard link, "Edit these settings in Net details" | repeater frequency, offset, tone, net time; which Tuesdays are Winlink, simplex or GMRS; two sentences | Tuesday net bar ("8:00 PM W7GBU 147.300 MHz…"), "radio settings", "Other nets", "From home" card | M3 |
| The repeater · The alternate repeater · Which Tuesdays · Sentences · Preview: what the site will say | Net details section headings | form sections | (none) | |
| Repeater call sign · Frequency (MHz) · Offset · Tone / No tone · Net time (every Tuesday) · Show on How it works | Net details fields | radio facts | "W7GBU 147.300 MHz, +600 kHz, 100 Hz"; "Alternate" | |
| Only an administrator can change it | Net details, call-sign hint and Help | the call sign is locked | (none) | I8 |
| Winlink nights · Simplex nights · ACS GMRS net / GMRS net weeks · 1st–5th | Net details, Which Tuesdays | week-of-month patterns | "Winlink nights: 2nd and 4th Tuesdays", "Fifth Tuesdays: …", "ACS GMRS net: …" | |
| How to answer a Winlink assignment / Open-slot line | Net details, Sentences | two sentences shown on the site | the sentence above the Winlink table; "Open slot? Tell the Net Manager…" | M3 |
| For members: the settings bar / How it works: the settings box / Home: the "From home" card / How it works: "Other nets" / The Copy buttons copy | Net details preview labels | where each fact prints | same names | |
| Save these changes? They show on the site at once. | confirm dialog: Net details, Meeting rules | last check before saving | (none) | |
| members hub | Net details Help ("print on the members hub, How it works and Home") | the For members page | "For members" / "For Members"; and on Home "the members' hub" = **groups.io** | I3 |

### Events

| Term | Where it appears | What it means | Public-site word | Flag |
|---|---|---|---|---|
| Events / All events / Add event / Edit event / **Add an event** | menu, h1, title button / Dashboard button | the event list and form | "Exercises & events" page | I12, M7 |
| Upcoming · Past · Drafts · (Trash) | Events list views; "All events" opens Upcoming | list filters | Next up, Later this season / Past exercises | M7 |
| When · Event name · Kind · Status · Needs checking | Events list columns | columns | When · What / Event · When | J3, I9 |
| Happening now · Upcoming · Past · Draft | Status column | where an event is in time | "Happening now" | |
| Edit · Duplicate · Trash · View on site | row actions | row actions | (none) | |
| Restore · Delete Permanently · Empty Trash · "Delete permanently" | Trash view | WordPress trash | (none) | J9 |
| **Kind (pick one first)**: Exercise · Training · On the air · Public service | event form, first box | event type; it decides which fields show and where the event appears | never named; the headings are Next up, Later this season, Public-service events and Past exercises | J3 |
| On the air: an on-air event members join from their own stations… | under the kind choice, always | explains one of the four kinds | (none) | R7 |
| Where it shows: Exercises & events › Later this season, until … Never on Home. | blue note after a kind is picked (4 versions, up to about 55 words) | where the event will appear | Next up / Later this season / This week / Public-service events | R2, R5 |
| Event name, as members will see it | title placeholder | the name | event title | |
| When: On a date / Date not posted yet / As requested | event form | date mode | "Date not posted", "As requested" | I2 |
| First day · Last day (optional) · All day · Start · End | event form | dates and times | "Sat–Sun, Sep 26–27", "All day", "All weekend" | |
| Short line for lists (90 characters) | event form | one-line summary | text after the name, e.g. ": optional shifts" | I11 |
| Where (optional) | event form | a place, or "From your own station" | "Where:" | I11 |
| What members do (one task per line; shown on the Next up card) | event form, Exercise only | task list | the bullets on the Next up card | |
| Main link (shown as the button): Web address (copy it from your browser's address bar) · Button words | event form | the card's button | the button, default "Exercise details on groups.io" | R4, I15 |
| More links (up to 3): Words · Web address … · + Add a link | event form | extra links | links after the text | R4, I15 |
| **Extra form** | event form, Exercise only; dropdown of *all* published documents | a library document to bring or use | "Extra form: {document}" | I6 |
| Volunteer through (call sign) | event form, Public service only | contact call sign | "Volunteer through" column | |
| After it ends: Keep in Past exercises | event form, Exercise only (ticked by default) | list it under Past exercises afterwards | "Past exercises" | |
| Never publish: no county, hospital, SHARES or 800 MHz channels; no names, phones or e-mails. | red box at the foot of the form | the rules | (none) | R3 |
| Copied as a draft. Set the new date, then Publish. | after Duplicate | copy made | (none) | O2 |
| On the site now: {places}. Events never show on Home. | info notice after every save of a published event | where it shows now | same place names | R1, R2 |
| Saved, and the event is still on the site as it was, but these changes were **held back** (your words are kept in the form): | error notice on a published event with a problem | the change was not published | (none) | J4 |
| The place has a phone number. If it is public, tick the box beside it … / This is a public agency number. Publish it. | error notice / confirm tick | the phone check | (none) | I11 |
| Pick a kind first. / Type the event name. / Pick the first day. / … | field errors | what's missing | (none) | |

### Meetings

| Term | Where it appears | What it means | Public-site word | Flag |
|---|---|---|---|---|
| Meetings | Dashboard heading only | recurring in-person meetings | "In person" (Home), "Next meeting:" (For members) | M1 |
| Cancel or move a meeting | Dashboard button | open the one-date change screen | (none) | I5 |
| **Regular meetings** | Events submenu, admin bar, "Edit this list in Regular meetings", h1 "Regular meetings: cancel or move one date" | **one-off** cancellations or moves of a date | "Next: Sat, Nov 14 (Oct 10 cancelled)"; "Sat, Oct 10: Second Saturday Workshop cancelled." | I5, M1, M2 |
| Meeting · Date · Cancelled · Moved to · Note | Regular meetings columns | one row per upcoming date | "(moved from …)", "cancelled" | |
| Not listed: {meeting}. The site doesn't show it (Meeting rules › "On Home and the members hub")… | foot of Regular meetings | a hidden meeting can't be changed here | (none) | I3, O1 |
| **Meeting rules** | Events submenu*, Dashboard link*, h1 | the **regular** pattern: week of month, day, times | the meeting lines on Home | I5, M2 |
| Cancel or move one date | Meeting rules h1 button | link to Regular meetings | (none) | R6 |
| Name · Week of the month · Day · Start · End · Time words (instead of the times on Home, e.g. "evenings") · Extra words on Home (after the time) · Skip these months · Add a meeting | Meeting rules fields | the rule | "Third Thursday training meeting, evenings" | I15 |
| On Home and the members hub / Active | Meeting rules checkboxes (side by side) | shown on the site / generates dates at all | (none) | I3 |

### Documents

| Term | Where it appears | What it means | Public-site word | Flag |
|---|---|---|---|---|
| Documents / All documents / Add document / **Add a document** / **Replace a document** | menu, h1 / Dashboard buttons | the library list and form | "Documents & forms" | I12, M6 |
| All · Mine · Published · Drafts | Documents list views (WordPress's own; differ from Events) | list filters | All / Most used / sections | I12 |
| Document · Section · Source · Format · Version · Most used · Tile slot · Reviewed · Needs checking | Documents list columns | columns | Document · Format · Source | I4, I9, J2 |
| File / File (none yet) / Link / Soon / Privacy not checked | Source column | where the file is | "Soon", source name | |
| All sections / Filter by section | list filter | narrow by section | section filter buttons | |
| Mark reviewed today | bulk action and a button on the form | set Last reviewed to today | (none) | I10 |
| Document name | title placeholder | the name | document title | |
| Basics | heading on the form (no matching heading below) | first group of fields | (none) | |
| Library section (required; pick one) | form; 7 choices | which library heading it goes under | Net operations, Join & task books, Forms, Training, Go-kits & readiness, Winlink & digital, Reference | I6 |
| Where the file is: Upload a file · Link to another site · Not available yet ("Soon") | form | source | "Soon" | |
| Privacy check: no personal phones, … (required to publish; tick it to unlock the file chooser) | form | the privacy check, which also unlocks the upload | (none) | R3 |
| File (it uploads when you click Save draft or Publish) · PDF, DOCX, XLSX, JPEG or PNG. | form | upload | Format column | |
| Current file: {name} · {size} · Open / Replace with / Remove the old file from the web (recommended) | form after an upload | replace in place | (same link) | |
| Version or date | form | version label | shown after the format ("PDF v3") | |
| Short note (8 words or fewer) | form (hard limit 60 characters) | one-line note | text under the name | O3 |
| More options: Source shown on the page · Format · "How to" link (Words, Web address) · Show under "Most used" · Position in section (lower shows first) · Who keeps it current · Last reviewed | folded section of the form | extra settings | Source, Format, "How to write one", Most used filter | I4, I10 |
| Hub tile: slot N, "…". Change tiles on Documents › Hub tiles. | More options | this document is a tile | (none) | J2 |
| Save as draft (**takes it off the lists**) | Save box on a published document | unpublish | (none) | I7 |
| An uploaded file stays at its web address. To take it off the web too, Move to Trash with "Also remove its file from the web" ticked … | Save box on a published upload | unpublishing a document doesn't pull its file | (none) | |
| The short note has 14 words; the library reads best with 8 or fewer. + …longer than 60 characters. | two notices for one field | length checks | (none) | O3, R1 |
| Marked 1 document reviewed today. | notice after the bulk action | done | (none) | |
| **Hub tiles** | Documents submenu, Dashboard link, admin bar, h1 "Hub tiles: the four "Most used" tiles on For members", "Edit this list in Hub tiles" | the 4 shortcut tiles on For members | "**Most used**" (For members) | I3, I4, J2, M4 |
| Slot · Document · Words on the tile · Icon (Script, Form, Log, Book) · "(now slot N)" · Save tiles | Hub tiles | tile settings | the tile labels ("Net script", "ICS-213") | J2, I6 |

### Pages (Home, How it works, About)

| Term | Where it appears | What it means | Public-site word | Flag |
|---|---|---|---|---|
| Pages | menu | list of the 6 pages | page names | M5 |
| Page text | Dashboard heading | the 3 editable pages | (none) | M5 |
| Edit Page | admin bar on Home, How it works and About | open the block editor | (none) | |
| Home — Front Page · "— Documents & forms (Child of For members)" · Author "admin" · "Published 2026/09/27 at 6:35 pm" · Filter by date / All dates | Pages list (WordPress) | page properties | (none) | J10 |
| View Pages · Undo · Redo · Document Overview · "{Page} · Page" · View Page · View · Zoom Out · Settings · **Save** · Options | block editor top bar | editor controls | (none) | J8, I7 |
| Page / Block tabs · Edit excerpt · "197 words, 1 minute read time." · "Last edited 13 minutes ago." · Content | block editor sidebar | page panel | (none) | J6, J8 |
| ⋮ Actions → View · Rename | the page card's menu | open or rename | (none) | |
| Options → Top toolbar, Distraction free, Spotlight mode, Fullscreen mode, Visual editor, Code editor, Keyboard shortcuts, Copy all blocks, Help, Welcome Guide, Preferences | block editor ⋮ | WordPress editor settings | (none) | J8 |
| Paragraph: "Start with the basic building block of all narrative." · Typography | Block tab with a paragraph selected | block description | (none) | J8 |
| Page review: Last reviewed: not yet · Page owner [Emergency Coordinator] · Mark reviewed today when I save | side box on **all three** pages | review stamp; only About prints it | "Last reviewed …", "Page owner: …" (About only) | I10, O4 |
| Page updated. View Page | snackbar after Save | saved | (none) | |
| Updating failed. Layout changes need an administrator. Undo the last change … | error on a layout change | the lock | (none) | I8 |
| This page may contain something we never publish: … / This page's **excerpt**, the description search engines show, … / A list item has no bold first words … / A list item is empty … / A link doesn't start with https:// … / Pasted as one paragraph with line breaks … | warnings after Save or paste (editor-guard) | page checks | (none) | J6 |
| Last reviewed appears here once a date is set in the Page review box. | About canvas, before a date is set | placeholder | "Last reviewed" | |

### Saving, status and checks (all forms)

| Term | Where it appears | What it means | Public-site word | Flag |
|---|---|---|---|---|
| Save (box title) · Status: New: not saved yet / Draft: not on the site / Published: on the site | side box on events and documents | publish state | (none) | J5 |
| Save draft · Publish · **Update** | Save box | WordPress lifecycle | (none) | I7, J5 |
| Save · Save rota · Save net details · Save meeting rules · Save tiles | settings screens | save | (none) | I7 |
| Save as draft (takes it off the **site**) | Save box, published event | unpublish | (none) | I7 |
| Move to Trash / Trash | Save box, row action | delete (can be undone) | (none) | |
| Draft saved. Drafts never show on the site. + Saved as a draft, so it's off the site: … | two notices on a save with problems | same fact twice | (none) | R1 |
| Published. / Saved. See it on {page} | core-style notice | saved | page name | R1 |
| Saved, except what is outlined in red. / Saved, except the fields outlined in red. / Saved, except the words outlined in red. | settings screens | partial save | (none) | I7 |
| **Needs checking** | Events and Documents list column ("Yes"); "(webmaster only)" boxes are for admins | the webmaster's private flag | (none) | I9 |
| **Webmaster check**: Marked for the webmaster to check. / Not marked for the webmaster. | side box on events and documents | the same flag, read-only | (none) | I9 |
| **Needs attention** | Dashboard | the editor's own to-dos (short rota, page warnings) | (none) | I9 |

### Account

| Term | Where it appears | What it means | Public-site word | Flag |
|---|---|---|---|---|
| Profile / Update Profile | menu, button | your account | (none) | |
| Username · First Name · Last Name · Nickname (required) · Display name publicly as · Email (required) · New Password / Set New Password · Sessions / Log Out Everywhere Else | profile (WordPress) | account fields | (none) | J11, I14 |
| Two-Factor Options · Authenticator App · Recovery Codes · Email · Primary Method | profile (Two Factor plugin) | sign-in codes | (none) | J11 |
| Additional Capabilities — Capabilities: `spokares_edit_net_details` | profile, bottom | the raw internal grant key | (none) | J11 |
| Help: "… (an app code or a **security key**)" | profile Help | 2-step options | (none) | J11 |

---

## 3. Problems

### 3.1 Inconsistencies

- **I1. "Net rota" is the admin's word; the public never uses it.** The public page says "Tuesday net" and "Net control, next five Tuesdays", and Frank says "net control". "Rota" is British. It appears in the menu (twice), h1, Dashboard ("Update the rota"), button ("Save rota"), admin bar and edit links. Sources: `admin-common.php:22,23,30`, `admin-rota.php:76,215`, `dashboard.php:181,202`, `admin-bar.php:25`, `blocks.php:71`. **Use "Net control".**
- **I2. The same state has two names.** Rota choice "Not posted yet" (`admin-rota.php:145`) vs Shows column and public "Not yet published" (`admin-common.php:523`, `admin-rota.php:38`, `render.php:549`). Event choice "Date not posted yet" vs list and public "Date not posted" (`format.php:231`). **Use "Not posted yet" everywhere.**
- **I3. The For members page has five names, and "hub" means two things.** The names: "For members" (h1s, "See it on For members"); "For Members" (header and footer nav, `parts/header.html:18`, `parts/footer.html:11`); "members hub" (Net details Help, Regular meetings Help, Meeting rules' "On Home and the members hub", "Not listed" line; `admin-common.php:279,286`, `admin-meetings.php:137,413,493`); "Hub" in "Hub tiles"; and "the members page" (Editing guide). Meanwhile the public Home calls **groups.io** "the members' hub for files and the calendar" (`patterns/home-join.php:60`). **Use "For members" (lower-case m, in the nav too) and retire "hub".**
- **I4. "Most used" is two separate lists.** The four tiles on For members (Hub tiles screen) and the "Most used" filter on Documents & forms (the "Show under 'Most used'" tick on each document). The Documents list shows them side by side as the columns "Most used" and "Tile slot". The Editing guide asks editors to keep the two in step by hand. **Decide on one concept.** Either the filter follows the tiles, or one of the two gets another name.
- **I5. The meeting screens are named backwards.** "Regular meetings" edits the *exceptions* (one date cancelled or moved). "Meeting rules" edits the *regular* pattern. Around them sit "Meetings" (Dashboard), "Cancel or move a meeting" (Dashboard button) and "Cancel or move one date" (Meeting rules button). **Name them by the job:** "Cancel or move a meeting" and "Meeting times".
- **I6. "Form" means four things.** The Winlink form type in the rota (ICS-213, DYFI); "Extra form" on an exercise, which is any library document (the dropdown lists all 37: Go-kit guide, videos, courses); the library section "Forms"; and the tile icon "Form". Help text also calls the admin screens "forms" ("Open the item and use its form"). **Rename "Extra form" to "Extra document"** in admin and on the public card (`admin-events.php:744`, `render.php:342`).
- **I7. Every screen saves with a different button.** Save rota · Save net details · Save · Save meeting rules · Save tiles · Save draft / Publish / **Update** (events, documents) · Save (block editor). Taking something off the site is "Save as draft (takes it off the site)" for events and "(takes it off the lists)" for documents (`admin-common.php:412,417`). The partial-save notice has three wordings. **Use "Save" on settings screens; "Publish" then "Save" on events and documents (drop "Update"); "Take it off the site" for unpublishing.**
- **I8. One person, three titles.** Editor-facing text says "only an administrator can change it" (`admin-net.php:281`, `admin-common.php:281`) and "Layout changes need an administrator" (`governance.php:374`), but "ask the webmaster" everywhere else. **Say "the webmaster".**
- **I9. Three near-identical phrases.** "Needs checking" (a list column on Events and Documents), "Webmaster check" (a side box: "Marked for the webmaster to check.") and "Needs attention" (the Dashboard's to-do box). The first two are the webmaster's private flag, which editors can't change. The seed data shows "Yes" on 9 of 20 events. "Needs attention" is the editor's own list. **Hide the webmaster flag from editors** (column and box) and keep "Needs attention".
- **I10. The same idea has different labels, and a box does nothing on two pages.** Pages have "Page owner" and documents have "Who keeps it current". The review stamp is "Page review" / "Last reviewed" / "Mark reviewed today when I save" on pages and "Last reviewed" / "Mark reviewed today" / "Reviewed" column on documents. The Page review box sits on **all three** pages, but only About prints the date (the Editing guide says "About only").
- **I11. Error messages name fields differently from their labels.** Label "Where" → error "The place has a phone number"; "Button words" → "the button text"; "Words" → "the link text"; "What members do" → "the task list"; "Source shown on the page" → "the source" (`admin-events.php:18-23`, `admin-documents.php:179-184`). **Errors should quote the label.**
- **I12. "Add" is worded two ways, and the two lists behave differently.** "Add event"/"Add document" (menu, h1) vs "Add an event"/"Add a document" (Dashboard). The Events list has custom views (Upcoming · Past · Drafts); the Documents list has WordPress's (All · **Mine** · Published · Drafts). "Mine" means nothing in a shared library.
- **I13. Five phrasings for "open the page on the site".** "View on site", "View", "View Page", "Visit Site" and "See it on …".
- **I14. Email is spelled two ways.** "E-mail" in our text and the Editing guide; "Email" in WordPress's profile and on the public button "Email the Emergency Coordinator".
- **I15. "Words" alone is a vague label.** It is used for link text ("Words" under More links, "“How to” link: Words"), next to "Button words", "Words on the tile", "Time words" and "Extra words on Home".

### 3.2 Jargon

- **J1. rota.** See I1.
- **J2. hub, tiles, slot, "Tile slot".** None of these appear on the public site, which says "Most used". Sources: `admin-tiles.php:50,58,72`, `admin-documents.php:927,934,1034`, `dashboard.php:321`, `admin-bar.php:29`.
- **J3. Kind.** "Kind (pick one first)" on the form and the "Kind" column on the list. The public site never names it. **Use "Type of event".**
- **J4. held back.** "these changes were held back (your words are kept in the form)" (`admin-events.php:451`). **Plainer:** "Your changes aren't on the site yet: fix the part outlined in red and save again."
- **J5. Draft / Publish / Update / Status.** The WordPress life cycle. It is mostly glossed well ("Draft: not on the site"), but "Update" and "Save draft" remain.
- **J6. excerpt.** The "Edit excerpt" panel, "This page's excerpt, the description search engines show…" and the Dashboard's "The excerpt of Home …". **Use "search-engine description".**
- **J7. wp-admin.** "Edit in wp-admin › Net details" / "› Regular meetings" in the block-editor canvas on Home and How it works (`blocks.php:119`). It is not a link, and a plain editor can't open Net details. **Use "Changed on the Net details screen"**, or show it only to users who can open that screen.
- **J8. Block-editor chrome.** Document Overview, Zoom Out, Block tab, "Start with the basic building block of all narrative.", Typography, Code editor, Spotlight mode, Distraction free, Copy all blocks, Welcome Guide, Preferences, "Content" panel, read-time count.
- **J9. List-table chrome.** Screen Options (Columns / Pagination / Number of items per page / View mode: Compact view, Extended view), Bulk actions, Apply, "Delete Permanently" and "Delete permanently" (both spellings on one screen), Empty Trash. Screen readers also hear "Filter posts list", "Posts list" and "Posts list navigation" on the Events and Documents lists.
- **J10. The Pages list is untouched WordPress.** "Home — Front Page", "(Child of For members)", Author "admin", "Published 2026/09/27 at 6:35 pm" (not house style), "Filter by date", and Help tabs that talk about posts, "the chronological blog stream", "Parent" pages and "the Bulk actions menu to edit the metadata" (bulk edit is removed for editors). The three members pages show only "View", with no hint that they are fed by Net rota, Events and Documents.
- **J11. Profile.** "Additional Capabilities: spokares_edit_net_details" (the raw internal key; the only visible "spk"), "Nickname (required)", "Display name publicly as", "Sessions / Log Out Everywhere Else", "Primary Method". The Help promises "a security key", which isn't offered: the methods are Authenticator App, Recovery Codes and Email.
- **J12. WordPress branding and chrome.** "Howdy,", "⌘K Open command palette", "Collapse Menu", and the footer "Thank you for creating with WordPress. Version 7.1.2".
- **J13. Denied-page wording.** Library sections by URL: "You need a higher level of permission. Sorry, you are not allowed to manage terms in this taxonomy." This is low priority, because no menu links there.

Clean: no editor-facing "post", "slug", "taxonomy" or "spk" outside J10, J11 and J13. "Offset", "Tone", "ACS", "SHARES" and "EOC" are domain words the audience knows.

### 3.3 Menu structure

- **M1. Meetings are filed under Events.** The Dashboard gives Meetings its own job (3), and the site shows meetings on Home and For members, never on Exercises & events. "The October meeting is cancelled" gives Frank no menu word to find. **Give Meetings its own top-level item**, or put it next to Net rota as a "Schedules" group.
- **M2. "Regular meetings" and "Meeting rules" are near-duplicates, adjacent, and backwards** (I5).
- **M3. The Net rota screen holds more than its name says.** "Net rota" appears as both parent and first child. The screen also holds the **Winlink assignments**, which the public shows on Exercises & events, not For members. The Winlink "how to answer" sentence and the open-slot line live in Net details. Nothing in the menu says where Winlink assignments are edited.
- **M4. "Hub tiles" is under Documents,** which is logical, but it is named for nothing on the site (I3, I4, J2).
- **M5. Pages has three names and three dead ends.** The menu says "Pages", the Dashboard "Page text", the admin bar "Edit Page". The Pages list shows six pages, and three of them are dead ends (only View).
- **M6. "Replace a document" (Dashboard) opens the plain All documents list,** with no cue to click a name and then use "Replace with".
- **M7. "All events" opens the "Upcoming" view.** There is no "All" view.
- **M8. The Events and Documents list screens show the Add form's Help** ("Pick the kind first…", "Tick the Privacy check first…"), not help for the list.

### 3.4 Repetition (the owner's pet hate)

- **R1. Two or three notices per save.**
  - Event published: "On the site now: Later this season on Exercises & events. Events never show on Home." plus "Published. See it on Exercises & events".
  - Event or document saved with a problem: "Saved as a draft, so it's off the site: …" plus "Draft saved. Drafts never show on the site."
  - Document short note: a warning ("14 words; … 8 or fewer"), an error ("longer than 60 characters") and "Draft saved".
- **R2. "Never on Home" is said every time.** It ends all four "Where it shows" notes and every event save notice.
- **R3. The never-publish list repeats on every screen.** It appears on the Net details intro and Help; the events form's red box and Help; the document Privacy check and Help; and in the rota intro and Help ("Call signs only").
- **R4. "Web address (copy it from your browser's address bar)"** is the full label on every link box: 4 on the event form, 2 on the document form.
- **R5. The "Where it shows" note for an Exercise is about 55 words.**
- **R6. The two meeting screens point at each other 3-4 times each:** the h1 button, the intro, the Help and the foot line.
- **R7. "On the air: an on-air event…" shows under the kind choice whatever kind is picked.** It explains one of the four.

### 3.5 Other things the inventory turned up

- **O1. The Dashboard's next meeting can be one the site hides.** "Meetings: Next: …" counts every *active* meeting, including ones not shown on the site. After Oct 10's Second Saturday Workshop was cancelled, it read "Next: Winlink workshop, Sat, Oct 10, 12:30 PM". Regular meetings calls that meeting "Not listed … a change here would appear nowhere". The cause: `dashboard.php` uses `spokares_next_meetings()`, which filters on `active` only (`schedule.php:193`), not on "On Home and the members hub".
- **O2. Duplicate opens the copy with a red "Pick the first day."** It appears before Frank has touched anything, next to the info notice "Copied as a draft. Set the new date, then Publish."
- **O3. The short note has two limits.** The label says "8 words or fewer", but the box stops at 60 characters (`maxlength`), silently, and the save error says "longer than 60 characters".
- **O4. The Page review box does nothing on two pages.** It appears on Home and How it works, where ticking "Mark reviewed today when I save" has no effect on the site (I10).

---

## 4. One word per concept (proposal)

| Concept | Use | Retire |
|---|---|---|
| Who runs each Tuesday net | **Net control** (menu); "Net control and Winlink assignments" (screen title) | Net rota, rota, Update the rota, Save rota |
| A Tuesday or event with nothing set | **Not posted yet** | Not yet published, Date not posted |
| The /members/ page | **For members** | For Members, members hub, hub, the members page |
| The four shortcut tiles | **Most used** (screen: "Most used documents") | Hub tiles, tiles, slot, Tile slot |
| One-date meeting change | **Cancel or move a meeting** | Regular meetings |
| The regular meeting pattern | **Meeting times** | Meeting rules |
| Event category | **Type** | Kind |
| A library item attached to an exercise | **Extra document** | Extra form |
| Saving a published item | **Save** | Update |
| Unpublishing | **Take it off the site** | Save as draft (takes it off the site / lists) |
| The person who can do more | **the webmaster** | administrator |
| Webmaster's private flag | (hide from editors) | Needs checking, Webmaster check |
| Page or document owner | **Kept current by** | Page owner, Who keeps it current |
| Meta description | **Search-engine description** | excerpt |
| Link to an admin screen from the editor canvas | "Changed on the {screen} screen" | Edit in wp-admin › |
