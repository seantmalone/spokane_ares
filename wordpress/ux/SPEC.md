# Volunteer editor UX spec (v0.3, final)

The decisions from the 2026-09-27 workflow review: five walkers (net, events and meetings, documents and Most Used, dashboard and pages, cold first-click) walked every editor journey as `qa-ares-net`, the ARES Editor with the Net details grant, for Frank. v0.3 takes in two critiques of v0.2: Frank reading the spec, and a WordPress developer checking it against the code. Section 8 answers both. This spec is the build contract. Inputs: `ux/journeys.md`, `ux/glossary.md` and the walkers' findings (section 6 lists every one, with its decision).

**The owner's directive, applied everywhere:** "'Rota' is a good example. Weird word, makes little sense. 'Net Control Schedule' makes sense." So "rota" leaves every piece of text a person sees, and every other term gets the same test: *would a ham volunteer say it out loud?*

## 0. Rules

| # | Rule |
|---|---|
| R1 | **Plain names.** A screen is named after the public page it feeds or after Frank's own words ("Net Control Schedule", "Cancel or Move a Meeting"). No WordPress, internal or web-designer words in text an editor or visitor reads: *rota, hub, tiles, slot, kind, card, library (in editor text), held back, excerpt, slug, wp-admin, administrator*, and "Update" as a save button. |
| R2 | **Casing, and naming a place in a sentence.** Screen names are Title Case wherever they appear: "Net Control Schedule", "Net Settings", "Exercises & Events", "Cancel or Move a Meeting", "Meeting Schedule", "Most Used", "Page Text". "Net Control Schedule" is a proper name and keeps its capitals everywhere, public text included. Public page names keep the site's own casing: "Home", "For members", "Exercises & events", "Documents & forms", "How it works". Case alone never tells two things apart: **a sentence that names a screen or a page adds the word** ("It’s button 2 on the Most Used screen", "See it on the For members page"). Only menu-path link labels use the bare name ("Edit this list in Net Control Schedule", the admin bar's Update lists items). Everything else is sentence case: buttons, labels, hints, notices. |
| R3 | **Say each thing once.** Each save gives one notice. A field gets at most one short hint. A rule lives in one place: field hint, Help or notice, never two. Help never restates a label, hint, link or notice the screen already shows; a screen whose fields say it all gets only "Stuck?". |
| R4 | **Required, not optional.** A required field says "(required)" and the browser stops Publish on it (**Save draft** skips the check with `formnovalidate`). No field says "(optional)". |
| R5 | **Create = edit.** Add and Edit show the same fields in the same order with the same labels. Only two things differ. The Save box: Add has **Save draft** and **Publish**; a published item has **Save** plus links. And what exists only once an item is saved: *Current file*, the event's *Cancelled* tick, *Make a copy*. Every settings screen (Net Control Schedule, Net Settings, Cancel or Move a Meeting, Meeting Schedule, Most Used) is one form for both, so parity there is automatic. |
| R6 | **Say what happened and where.** Every save ends in one notice that names what changed and links to where it shows. A problem gets a red outline and one sentence under the field that says what to do. The top notice only names the fields ("Not saved: Oct 20 (outlined in red)."). |
| R7 | **Never lose typing.** Every settings form (`data-spk-guard`) warns before you leave with unsaved changes. Typing that wasn't saved stays until the next save. |
| R8 | **Hide what editors can't change.** Webmaster-only flags ("Needs checking", "Webmaster check") and controls are hidden from editors, not shown read-only. The one exception is the repeater call sign, shown as plain text because members see it. |
| R9 | **Identifiers stay.** Option names (`spk_rota` …), meta keys, PHP functions, CSS classes, block names and attribute values (`"view":"rota"`, its `enum`), HTML anchors (`#rota`, `#open-slot`, `#quick-links`), admin page slugs (`spokares-rota` …), capabilities (`spokares_edit_rota` …), form field names (`rota[…]`), file names (screenshots included), test file names and the tests' `SCREENS` keys don't change. Only text a person reads or hears changes, aria-labels included. |
| R10 | **One source per screen name.** `spokares_edit_screen()` holds each screen's name. The top-level menu labels, the admin bar's Update lists items, "Edit this list in …" and the editor hints read it, and every `admin.php?page=` screen prints its h1 with `esc_html( spokares_screen_title( $key ) )` (admin-common.php: WordPress's page title, or the same name when a test draws the page directly). The names can't drift apart. |

---

## 1. Vocabulary

One term per concept. "Retired" words must not appear in editor-facing text, public text or the guide.

| Concept | Term | Retired |
|---|---|---|
| Who runs each Tuesday net (screen and list) | **Net Control Schedule** | Net rota, rota, the rota, Update the rota, Save rota, rota's Note column |
| A Tuesday nobody has taken, where we ask for a volunteer | **Volunteer needed**: the admin choice, the public tag (it links to the line under the table) and the Dashboard ("Volunteer needed: Oct 6, Oct 20") | Open, Open slot, open slots |
| The line under the public table that says how to volunteer | **Asking for volunteers** (Net Settings field) | Open-slot line |
| A Tuesday with no net | **No net** (new choice, and the public cell) | a note on a "Not posted yet" row |
| A Tuesday not decided yet | **Not posted yet** (admin, public and Dashboard) | Not yet published, Not posted |
| Changing the schedule | **Update the schedule** (Dashboard button); **Fill in more Tuesdays** (Needs attention link) | Post net control, Post or change net control |
| Taking back an Undo | **Redo** | Put it back, Put back what was undone |
| The Winlink form on a Tuesday | **Form**; the blank choice reads **ICS-213 (usual)** | — none —, Not named (ICS-213), a second "ICS-213" choice |
| The repeater, net times, which Tuesdays, the two wording lines | **Net Settings** | Net details |
| The /members/ page | **the For members page** | members hub, hub, the members page |
| The four document buttons on the For members page | **Most Used** (screen); each row is a **button**, numbered 1–4 left to right | Hub tiles, tiles, slot, Tile slot, Slot 1, Members page shortcuts |
| Library filter item on the Documents & forms page | **Also list under Most used** (per-document tick; the four buttons' documents are always listed) | Show under "Most used" |
| The Events screens (public page "Exercises & events") | **Exercises & Events** (menu, list title); an item is an **event** | Events (as the menu name) |
| Event category | **Type of event** (form, list column, errors): Exercise · Training or meeting · On the air (from home stations) · Public service | Kind, "(pick one first)", "(no kind)" |
| The events list column of places | **Where it shows** | Shows, Status |
| The top section of the Exercises & events page | **Next up** | Next up card |
| One-line summary of an event | **Short description** | Short line for lists |
| An event's main link | **Button**: **Web address** · **Words on the button** | Main link (shown as the button), Button words |
| A document an exercise needs | **Document members need** | Extra form |
| Event with no date because it moved | **Postponed** (a When choice) | (no way to say it) |
| Event called off | **Cancelled** (tick under When; public tag "Cancelled") | Move to Trash as the way to call it off |
| Event with no date yet | **Date not posted yet** (admin and public) | Date not posted |
| Public service "whenever asked" | **As requested** (a When choice, shown only for Public service) | As requested (Public service only) |
| Making next year's copy | **Make a copy** (Save box and row action) | Duplicate |
| One-date meeting change screen | **Cancel or Move a Meeting** (menu parent **Meetings**) | Regular meetings, "Regular meetings: cancel or move one date", Cancel or move one date |
| The regular meeting pattern | **Meeting Schedule** | Meeting rules, Save meeting rules |
| The date a pattern change starts | **Takes effect on** (on every card, the Add card too) | Changes start, Starts |
| A meeting's words instead of times | **Time as words** (placeholder "evenings") | Time words (instead of the times on Home, e.g. “evenings”) |
| A meeting's shown/hidden switch | **Show on the site** (one tick) | On Home and the members hub, Active |
| A one-day meeting change (time, room) | **Note** (column "Note (shown with the date)") | one-day notice |
| Documents, counted | **{n} documents**; a name match is **Already a document: … Open it** | in the library, Already in the library |
| Document section | **Section** | Library section, "(required; pick one)" |
| A document that isn't ready | **Not ready yet (shows “Soon”)**; public tag **Soon** | Not available yet ("Soon") |
| A document's order in its section | **Place in section** (At the top / After “…” / At the end) | Position in section (lower shows first) |
| Owner of a document | **Owner** (not public) | Who keeps it current |
| Owner of a page (printed on About) | **Page owner** (the box uses the public page's own words) | (unchanged) |
| Replacing a file | **Replace or change a document** (Dashboard); **Replace or change it** (Most Used rows) | Replace a document, Change or remove a document |
| Saving | **Save** (every form and the Save box of a published item) | Update, Save rota, Save net details, Save tiles, Save meeting rules |
| Unpublishing | **Take it off the site** (moves it to the Trash; Undo brings it back) | Save as draft (takes it off the site / the lists), "Also remove its file from the web" |
| Item state | **On the site** · **Not on the site (draft)** · **Not saved yet** | Status: Published: on the site / Draft: not on the site / New: not saved yet |
| A field that wasn't saved | **not saved** ("Saved, except …") | held back |
| The person who can do more | **the webmaster** | administrator (in editor text) |
| Webmaster's own flag | hidden from editors | Needs checking, Webmaster check (for editors) |
| The block editor's page list and editor | **Page Text** (menu, list title); **Edit Page Text** (admin bar) | Pages, Edit Page, Page text |
| The page's search-engine description | hidden from editors (webmaster's job) | excerpt, Edit excerpt |
| The sign-in codes | **the code from my phone app** · **e-mail me a code** · **a printed backup code**; each box is **Code** | TOTP, Authentication Code, Verification Code, Recovery Code, Primary Method |
| The screen where a fact lives, in the editor's block hints | "Change this on the {Screen} screen." | Edit in wp-admin › {screen} |

Kept on purpose: *Dashboard, Site tasks, Needs attention, Update lists, Profile, Publish, Save draft, Undo, Trash (the list view), Winlink assignment, Volunteer through, Soon, See it, View on site*. They are plain words Frank already uses, or the public site's own words. The public header's "For Members" button is a design element and stays as it is. The public Documents & forms page keeps its own "Search the library" label: that is the visitors' page, not editor text.

---

## 2. Menu structure (ARES Editor with the grant; * = grant only)

| Order | Menu (icon) | Submenu | Opens |
|---|---|---|---|
| 1 | Dashboard (dashboard) | none | Site tasks |
| 2 | **Net Control Schedule** (microphone) | Net Control Schedule · Net Settings* | `admin.php?page=spokares-rota` |
| 3 | **Exercises & Events** (calendar) | All Events · Add an Event | `edit.php?post_type=spk_event` |
| 4 | **Meetings** (groups) | Cancel or Move a Meeting · Meeting Schedule* | `admin.php?page=spokares-meetings` |
| 5 | **Documents** (document) | All Documents · Add a Document · Most Used | `edit.php?post_type=spk_document` |
| 6 | **Page Text** (page) | none: lists Home, How it works, About ARES & ACS | `edit.php?post_type=page` |
| 7 | Profile (person) | none | |

A plain ARES Editor sees the same menu. "Net Control Schedule" and "Meetings" then have no submenu, and each opens its one screen. The admin page slugs stay the same (R9). Only the parent of the two meeting screens moves, from Events to Meetings. For the grant holder, Net Settings sits under Net Control Schedule; that is accepted (plain editors see only the one item), and a "Tuesday net" parent is not invented without the owner. For editors the menu keeps its labels down to 783px: WordPress's `auto-fold` is turned off for non-admins, so an iPad in portrait no longer shows bare icons.

**Why this menu.** Each top-level item is one kind of real-world change, named the way Frank says it. The first-click results support it:
- Frank's first click was right on 18 of 20 tasks, and the Dashboard carried almost all of them, so the Dashboard keeps its role as the start screen. The menu is the second route in, and its names now match the Dashboard's section headings one to one.
- He hesitated at "rota" on all four net tasks (T1–T4). "Net Control Schedule" is the owner's term.
- He hesitated at "Meeting rules" (T11), because he read "rules" as bylaws. Meetings also sat under Events although the site never shows them there (EVT-5, FC-8). They now get their own item with task names.
- T17 failed because "Most used" meant two things and "Hub tiles" meant nothing. They are now one concept with one name (§3.7).
- The Pages list showed six pages, three of them dead ends, and "Page text" had three names. It is now one name, "Page Text", and three pages.

The admin bar's **Update lists** menu on the public site offers, from `spokares_edit_screen()` (R10): Net Control Schedule · Exercises & Events · Meetings · Documents · Most Used. Signed-in editors see a link under each public list: "Edit this list in {name}", or "Edit these settings in Net Settings" under the net facts.

---

## 3. Per-screen spec

Notation: **Field** | label as shown | input | req | default | the one hint (if any).

### 3.1 Dashboard › Site tasks

```
Site tasks
┌ Needs attention (amber; only when there is something) ───────────────────────────────┐
│ • The Net Control Schedule runs out in under 3 weeks. Fill in more Tuesdays           │
│ • How it works has what looks like a phone number (509-555-0142). Open the page       │
└───────────────────────────────────────────────────────────────────────────────────────┘
Net Control Schedule
  Posted through Tue, Dec 29 · Volunteer needed: Oct 6, Oct 20 · Not posted yet: Nov 3
  [Update the schedule]   Net Settings*
  Repeater and net times: ask the webmaster.        (plain editor, instead of the link)
Exercises & Events
  Happening now: ARES-ACS / WSDOT exercise
  Next: <Simulated Emergency Test>, Sat, Oct 3 · 10 more        (the name links to its form)
  [Add an event]  [Change or cancel an event]
Meetings
  Next: Second Saturday Workshop, Sat, Oct 10, 9:00 AM
  [Cancel or move a meeting]   Meeting Schedule*
Documents
  37 documents · <4 marked Soon> · <3 not reviewed in 12 months>
  [Add a document]  [Replace or change a document]   Most Used
Page Text
  Home · How it works · About ARES & ACS
Stuck? webmaster@spokares.org
```

- No number badges: they read as steps to do in order. The five section headings are the screen names.
- **Needs attention** is printed first, above the sections. Each line ends in a link to the place that fixes it:
  - "The Net Control Schedule runs out in under 3 weeks." → **Fill in more Tuesdays** (the Net Control Schedule screen).
  - "{Page} has what looks like {what} ({match})." → **Open the page**. {match} is the first matched text, at most 60 characters.
  - Administrators only: the "Needs checking" line (its links read Exercises & Events, Documents, Net Settings, Meeting Schedule), and "The search-engine description of {Page} has what looks like {what} ({match})." (editors can't see or edit the description, PG-7).
- **Net Control Schedule status:** read all stored rows for the next 52 Tuesdays.
  - A Tuesday counts as *posted* when its state is Call sign, Volunteer needed or No net.
  - "Posted through {last posted Tuesday, 'short'}". If there is a Volunteer needed Tuesday on or before that date, add " · Volunteer needed: {dates, 'day'}". If there is a Not posted yet Tuesday before that date, add " · Not posted yet: {dates}".
  - With nothing posted: "Nothing posted for the coming Tuesdays."
  - The button says **Update the schedule**: the heading already names it.
- **Events:** "Happening now" and "Next" skip cancelled events. The "Next:" event's name links to its form. The secondary button **Change or cancel an event** opens the list.
- **Meetings:** "Next:" counts only meetings shown on the site (`active && show_home`), after cancellations and moves.
- **Documents:** "{n} documents". "{n} marked Soon" links to `edit.php?post_type=spk_document&spk_source=soon` and appears only when n > 0. "{n} not reviewed in 12 months" links to `edit.php?post_type=spk_document&orderby=spk_reviewed&order=asc` (oldest first) and appears only when n > 0. The secondary button is **Replace or change a document** (opens the list): "replace" is the monthly net-script job.
- **Page Text** links: Home · How it works · About ARES & ACS. The link words stay the page titles.
- The panel can't be collapsed or moved by editors: it is never drawn `closed`, and its handle buttons are hidden. When "Add a document" and "Replace or change a document" wrap onto two lines, they line up (flex, gap 8px 12px).
- Help: "Changes show on the site as soon as you save."
- Account with no tasks: "This account can’t change anything on the site. …" stays, and the last line reads "Need to change the Net Control Schedule, events or documents? Ask the webmaster:".

### 3.2 Net Control Schedule (`spokares-rota`)

```
Net Control Schedule  [View on site]   Last saved Sun, Sep 27, 7:11 PM by Frank (Oct 13 and Oct 20) · Undo
Tuesday net, 8:00 PM, W7GBU 147.300 MHz. Call signs only, no names.
Repeater and net times: ask the webmaster.                      (plain editor only)

Tuesday          Net control                                                          Winlink assignment       Form                 Note
Tue, Sep 29      ( ) [Call sign   ] ( ) Volunteer needed ( ) No net (•) Not posted yet
Simplex
Tue, Oct 13      (•) [NZ2S        ] ( ) Volunteer needed ( ) No net ( ) Not posted yet [2-line box           ] [ICS-213 (usual) ▾] [            ]
Winlink night
…
[Show 13 more Tuesdays]
                                                                                                                        [Save]
```

| Part | Spec |
|---|---|
| Title line | h1 from `get_admin_page_title()`: **Net Control Schedule**. **View on site** → `/members/#rota` (the anchor stays, R9). The last-saved line reads "Last saved {D, M j, g:i A} by {name} ({dates the save changed})", where dates is `and`-joined 'day' dates, or "{n} Tuesdays, {first} – {last}" when there are more than 3. Then **· Undo**, a link-styled submit of the undo form. After an undo the line reads "Undone {when} by {name} ({dates}) · Redo". |
| Lede | "Tuesday net, {time}, {call} {freq}. Call signs only, no names." Unchanged. This is the only place the call-sign rule is stated on the screen. |
| Plain editor line | "Repeater and net times: ask the webmaster." |
| Columns (≥783px) | Tuesday 12% · Net control 28% · Winlink assignment 32% · Form 12% · Note 16%. **No Shows column**, and no screen-reader status line for it. |
| Tuesday cell | Date ('short'), then the rule tag (Simplex / Winlink night / GMRS net, 7:30 PM) on its own line. At ≤782px (cards) the date is a 16px heading. |
| Net control | Four radios in one fieldset (legend for screen readers: "Net control on {date}"). The first radio has no visible word (screen-reader label "Call sign"). Its box sits right after it, with placeholder **Call sign**, `maxlength` 40, a datalist of past calls and room for 6 characters. Then **Volunteer needed** (value `open`), **No net** (value `none`), **Not posted yet** (value `tbd`). Typing in the box picks the first radio. The box is white unless Volunteer needed or No net is chosen; then it is grey and read-only until clicked (clicking wakes it). On No net, the row's Winlink boxes grey out too. |
| Live check | A red line under the box, and only while the typed text contains no call sign: **"Type a call sign, like NZ2S, not a name."** |
| Winlink assignment | Shown on Winlink-night rows (or a row that already has one): a 2-row `textarea`, `maxlength` 120. |
| Form | A select: **ICS-213 (usual)** (value '') · ICS-213RR · DYFI · Welfare Message / Quick Health & Welfare · Other…. There is no separate "ICS-213" choice. A row stored as 'ICS-213' shows "ICS-213 (usual)" selected and keeps its stored value: the save treats a posted '' as unchanged when the stored form is 'ICS-213' (§4.1). Other… reveals a text box with placeholder "Form name". |
| Note | Text, `maxlength` 80. |
| Rows | The server draws all 52 rows. Rows past `weeks` (default 13) get `hidden`, except a row with an error or unsaved typing. **Show 13 more Tuesdays** is a link to `?weeks=n+13` (the fallback) that the script turns into an in-place reveal: it shows the next 13 and updates the hidden `weeks` input so the redirect after Save keeps them showing. When all 52 show, the link is replaced by "That’s as far ahead as you can post." |
| Changed rows | After a save or an undo, the rows it changed are tinted for one page view. |
| Save bar | A single **Save** (primary). At ≤1024px the bar sticks to the bottom of the window. |
| Leave guard | `data-spk-guard` on the form: leaving, Show 13 more (fallback), Undo or a menu click with unsaved changes asks the browser's "Leave site?". |

**Server rules.** State values are `call | open | none | tbd`. A `none` row is stored even with no note. It counts as posted, never needs a volunteer, and the public list prints "No net". Only changed rows are saved. A name with a call sign is cut to the call sign, and a row whose text has no call sign isn't saved. The conflict check is unchanged. The phone and e-mail check on Note and Winlink assignment is unchanged.

**Messages.** One notice per save. "See it on the For members page" is the link (to `/members/#rota`).

| Case | Notice (type) |
|---|---|
| Saved, all within the next five Tuesdays | "Saved Oct 13." + "See it on the For members page" (success) |
| Saved, some beyond | "Saved 8 Tuesdays, Nov 10 – Dec 29. The For members page lists the next five Tuesdays." + "See it on the For members page" (success) |
| Name cut to a call sign | Added to the saved text: "Saved Oct 13: NZ2S (names aren’t posted)." + link |
| Some saved, some not | "Saved Oct 13. Not saved: Oct 20 (outlined in red)." (warning) |
| None saved | "Not saved: Oct 20 (outlined in red)." (error) |
| Nothing changed | "Nothing changed, so nothing was saved." (info) |
| Undo | "Undone: Oct 13 and Oct 20 are back as they were." (success; **Redo** lives in the title line only) |
| Redo | "Redone: Oct 13 and Oct 20 are back as they were saved." (success) |
| Nothing to undo | "There is nothing to undo." (info) |

Under-field sentences (one each, no date in them):
- **No call sign:** "Type a call sign, like NZ2S, not a name."
- **Someone else saved first:** "{name} changed this Tuesday while you were editing. Your entry wasn’t saved."
- **Phone or e-mail** in the Note or assignment: `spokares_problem_sentence()` (§4.7), e.g. "It has a phone number. Take it out, or tick the box if it’s a public agency number." The tick keeps its existing words.
- **Never-publish words:** "It mentions a hospital net, which we never publish. Take it out."
- **Too long:** "Keep it to {n} characters."

Help (1 line): "Winlink assignments and forms show on the Exercises & events page; notes show on the For members page."

### 3.3 Net Settings (`spokares-net-details`, grant only)

```
Net Settings  [View on site]   Last saved Sun, Sep 27, 7:40 PM by Frank
The Tuesday net
  Net time (required)       [ 8:00 PM ]
  Repeater call sign        W7GBU (the webmaster changes this)
  Frequency (MHz) (required)[147.300]   Three digits after the point, like 147.300.
  Offset                    [+600 kHz ▾]
  Tone                      [100 Hz ▾]
  Members see               8:00 PM · W7GBU 147.300 MHz, +600 kHz, 100 Hz
Alternate repeater
  Show on How it works      [x]
  Frequency (MHz)           [146.880]
  Offset / Tone             [−600 kHz ▾] [100 Hz ▾]
  Members see               Alternate · 146.880 MHz, −600 kHz, 100 Hz tone      ("Not shown on How it works." when unticked)
Which Tuesdays
  1st Tuesday [Regular net ▾]   2nd [Winlink night ▾]   3rd [GMRS net ▾]   4th [Winlink night ▾]   5th [Starts on simplex ▾]
  GMRS net time             [ 7:30 PM ]
  Members see               Winlink nights: 2nd and 4th Tuesdays; … / Fifth Tuesdays: … / ACS GMRS net: …
Wording on the site
  How to answer a Winlink assignment [textarea]   Shown above the Winlink assignments on the Exercises & events page.
  Asking for volunteers     [text]                Shown under the Net Control Schedule on the For members page; the “Volunteer needed” tags link to it.
                                                                                   [Save]
```

- h1 from `get_admin_page_title()`: **Net Settings**. **View on site** → How it works `#weekly-net`.
- **Removed:** the red banner; the "Preview: what the site will say" box, with its Copy-text, settings-box and From-home lines; the confirm dialog; the "A week ticked twice…" sentence.
- **Repeater call sign:** plain text for non-admins. Administrators keep the input, with its existing hint.
- **Frequency:** `type="text" inputmode="decimal"` with **no** `pattern` attribute, so there is no browser bubble. A live check runs on input and blur, and one line replaces the hint while there is a problem:
  - "Three digits after the point, like 147.300." (format)
  - "Not an amateur frequency; the site keeps {stored}." (band)

  The server check is unchanged.
- **Members see** lines are drawn on the server from `spokares_net_preview_lines()` (bar, alt, winlink, simplex, gmrs) and redrawn live by the script, as the preview was.
- **Which Tuesdays:** five selects (`nets[week][1..5]`) with the values `regular | winlink | simplex | gmrs`, labelled Regular net · Winlink night · Starts on simplex · GMRS net. The server maps them onto `winlink_nth`, `simplex_nth` and `gmrs_nth`, so a week can hold only one kind of net. The per-field conflict check (`orig`) keeps comparing the three arrays.
- **Last saved:** a new option `spk_net_saved` holds the stamp `{at, by}`, written on every save that changed something.
- Save button **Save**. **Leave guard** on the form.

| Case | Notice |
|---|---|
| Changed | "Saved: tone 103.5 Hz (was 100 Hz); net time 7:30 PM (was 8:00 PM)." + "See it on the How it works page" (success). The names are lower-case labels. The two wording lines only say "Winlink how-to wording changed" / "asking-for-volunteers line changed". |
| Nothing changed | "Nothing changed, so nothing was saved." (info) |
| Partly saved | "Saved, except the frequency (outlined in red)." (warning), plus the line under the field. |
| Not saved | "Not saved: the frequency (outlined in red)." (error) |

Under-field sentences:
- **Frequency:** "Not an amateur frequency; the site keeps 147.300." / "Three digits after the point, like 147.300."
- **Time:** "Type a time, like 8:00 PM."
- **Conflict:** "Someone else changed this while you were editing. Check it and save again."
- **Call sign** (a non-admin POST): "The webmaster changes the repeater call sign."

Help (1 line): "These settings print on the For members, How it works and Home pages at once."

A plain editor who opens the URL sees: "Net Settings (the repeater, the net times and which Tuesdays are Winlink, simplex or GMRS) are changed by the webmaster, or by someone the Emergency Coordinator has named. Ask the webmaster: webmaster@spokares.org."

### 3.4 Exercises & Events

**List** (`edit.php?post_type=spk_event`; h1 "Exercises & Events"; button "Add an Event")
- The views stay: Upcoming (default) · Past · Drafts · Trash.
- Columns: **When** · **Event** · **Type of event** · **Where it shows**. **Needs checking** is shown to administrators only.
- **Where it shows** replaces Status. It lists where the event is right now, joined with " · ":
  - The Exercises & events page's sections by name: "Next up", "Later this season", "Public-service events", "Past exercises"; and "For members page" when it is in This week there.
  - "Not listed" when it is published but in no list.
  - "Draft (not on the site)" for a draft, "In the Trash" in the Trash view.
  - A running event gets a bold "Happening now" first; a cancelled one gets "Cancelled" first.
- Row actions: Edit · Make a copy · View on site. "Make a copy" is the old Duplicate, relabelled. The Trash row action goes, as it already has for documents: an event is taken off the site from its own form, where the Save box says what that does.
- Help: "Called off? Open it and tick Cancelled under When." · "Same event next year? Open last year’s and click Make a copy."
- Empty view: "No events here." (the `not_found` label).

**Add / Edit** (h1 "Add an Event" / "Edit Event"). Fields in this order:

| # | Field | Input | Req | Default | Hint / notes |
|---|---|---|---|---|---|
| 1 | **Type of event** | radios: Exercise · Training or meeting · On the air (from home stations) · Public service | yes (`required`) | none | **No hint** on Add or Edit: Frank picks a type by what the event is, and the save notice says where it went. The "On the air: …" sentence is removed. The red outline sits outside the legend. |
| 2 | **Event name** | core title box, with a visible label above it and no placeholder | yes | | |
| 3 | **When** | radios: On a date · Postponed · Date not posted yet · As requested | | On a date | **As requested** is shown only while Public service is picked (`data-kinds="public-service"`). If it is chosen and the type changes, Date not posted yet is chosen instead (as today). |
| 3a | First day · Last day | date inputs; a live weekday ("Sat") after each | First day: yes when On a date | | |
| 3b | All day · Start · End | as today | | All day ticked | |
| 3c | **Cancelled** | checkbox | | off | Shown only on a published event, when On a date. Hint: "Members see “Cancelled” until the date passes." |
| 4 | **Where** | text 80 | | | "A place, or “From your own station”." |
| 5 | **Short description** | text 90 | | | Not for Public service (`data-kinds="exercise,training,on-air"`). No hint. |
| 6 | **What members do (one task per line)** | textarea | | | Exercise only. |
| 7 | **Button** (legend): **Web address** · **Words on the button** | text `inputmode="url"`, placeholder "https://…" · text 60 | | | Web address hint, once: "Copy it from your browser’s address bar." Words on the button placeholder: "Details", or "Exercise details on groups.io" while the address is on `spokaneares-acs.groups.io`. Not for Public service. |
| 8 | **More links (up to 3)**: Words · Web address · + Add a link | as today, but the address is labelled plainly "Web address" | | | Not for Public service. |
| 9 | **Document members need** | select: — none — / published documents | | none | Exercise only. **No hint.** |
| 10 | **Volunteer through (call sign)** | text | | | Public service only. |
| 11 | **List under Past exercises after it ends** | checkbox | | ticked on Add | Exercise only. For a month- or year-precision date, a hint beside First day: "The site shows “Oct 2022”." |
| — | Foot line (plain grey, not a red box) | "Never publish county, hospital, SHARES or 800 MHz channels, or names, phones or e-mails." | | | |

More rules:
- A web address with no scheme gets `https://` added on blur (script), and on the server (§4.7).
- The **Webmaster check** box is shown to administrators only. The **Save** box is in §3.9.
- An event copied with **Make a copy** opens with no error text: First day is outlined only. The problems show from its first save on.

| Case | One notice ("See it" links to the event on the Exercises & events page) |
|---|---|
| Published, shown | "Published. It shows under Later this season on the Exercises & events page. See it" (success). The places come from the Where it shows logic. |
| Saved, shown | "Saved. It shows under Next up on the Exercises & events page and in This week on the For members page. See it" |
| Saved, cancelled | "Saved. Members see “Cancelled” until Sat, Oct 17. See it" |
| Saved, postponed | "Saved. It shows as Postponed on the Exercises & events page. See it" |
| Saved, past date | "Saved, but Fri, May 15, 2026 has passed, so no list shows it. Check the year." (warning) |
| Saved, ended and not kept | "Saved. It isn’t listed anywhere now: it has ended and isn’t kept under Past exercises." (info) |
| Draft saved | "Draft saved. It isn’t on the site until you Publish." (info) |
| Publish refused, one problem | "Not published yet: pick the type of event, then click Publish." (error; no green notice) |
| Publish refused, several problems | "Not published yet. Fix the boxes outlined in red, then click Publish." |
| Published event, a field not saved | "Saved, except Where: it has a phone number. If it’s a public agency number, tick the box under Where and click Save. Everything else is on the site now." (warning, amber like every other screen's partial save; changed by the verifier from error). It names fields by their labels. This is true to the code: `spokares_save_event()` skips only the problem fields' meta and saves the rest. The typed text stays in the form until the next save of that event. |
| Name cut from Volunteer through | appended: "(saved NV2Z only; names aren’t posted)" |
| Copied | "Copied. Set the new date, then Publish." (info) |
| Taken off the site (Trash) | "“Great ShakeOut” is off the site." + core's Undo |
| Restored | "“Great ShakeOut” is back on the site." Restore brings back the previous status, as Undo does. |

Under-field errors use the field's own label: "Pick the type of event." · "Type the event name." · "Pick the first day." · "The last day is before the first day." · "The end time must be after the start time." · "Type a web address, like https://www.arrl.org/…" · "Each extra link needs its words."

### 3.5 Meetings

**Cancel or Move a Meeting** (`spokares-meetings`; h1 from `get_admin_page_title()`: "Cancel or Move a Meeting"; for the grant, the h1 button **Meeting Schedule**; **View on site** → Home)
- Columns: Meeting · Date · Cancelled · Moved to · **Note (shown with the date)**. Only shown meetings are listed. The "Not listed: …" line and the foot cross-link are deleted.
- **A note on its own** is saved as a one-date change (change kind `note`). Home: "Next: Sat, Nov 14 (Starts at 10:00 AM this time)". For members, within 14 days: "Sat, Nov 14: Second Saturday Workshop. Starts at 10:00 AM this time."
- **Un-cancelling is one tick.** When Cancelled is unticked and Moved to is empty on a row stored as cancelled or moved, the script clears its Note. The server fallback: if the posted note equals the stored note and neither box is set, the change is removed.
- **Moved to the same date:** refused with "Pick a different date. For a new time or room on the same day, use the Note alone."
- Save button **Save**. **Leave guard** on the form.
- Empty state: "No regular meetings are shown on the site." For the grant, followed by a "Meeting Schedule" link.

| Case | Notice ("See it on the Home page" is the link) |
|---|---|
| Saved | "Saved: Sat, Oct 10 cancelled." + link; several: "Saved: Sat, Oct 10 cancelled; Thu, Oct 15 moved to Wed, Oct 14." + link. Other phrasings: "Sat, Nov 14 note posted", "Sat, Oct 10 is back on". |
| Error | "Not saved: Thu, Oct 15 (outlined in red)." One notice; the sentence sits under the row only. |
| Nothing changed | "Nothing changed, so nothing was saved." |

Help (1 line): for the grant, "Regular days and times are on the Meeting Schedule screen. The meeting place: ask the webmaster."; for a plain editor, "Regular days and times, and the meeting place: ask the webmaster."

**Meeting Schedule** (`spokares-meeting-rules`, grant; h1 from `get_admin_page_title()`: "Meeting Schedule"; the h1 button **Cancel or Move a Meeting**)
- The lede is removed; the h1 button says the rest.
- One card per meeting, in this order: Name · Week of the month · Day · Start · End · **Time as words** (placeholder "evenings", no hint) · Extra words on Home (after the time) · Skip these months · **[x] Show on the site** · **Takes effect on** [date].
  - **Show on the site** is one tick that sets both `show_home` and `active`. A stored meeting that is active but hidden shows it unticked.
  - **Takes effect on** has the hint "Leave empty for now". It sits last, after the tick, away from Start and End, so it can't be read as a third time.
- A card with a pending change shows one line under its legend: "From {D, M j, Y}: {name}, {weeks and day}, {time or time as words}." with the tick "[ ] Drop this change".
- The **Add a meeting** card has the same fields and labels: Show on the site ticked, and **Takes effect on** with the same hint.
- No confirm dialog. Save button **Save**. Leave guard on the form.

On save:
- If a card's pattern fields changed and Takes effect on is later than today, the change is stored as `next` (§4.3) and today's pattern stays.
- Any cancel, move or note on a date that is no longer a meeting date, under the pattern in effect on that date, is removed and named in the notice.

| Case | Notice |
|---|---|
| Changed now | "Saved. Next Second Saturday Workshop: Sat, Nov 14." + "See it on the Home page" |
| Changed from a date | "Saved. From Fri, Jan 1, 2027: Fourth Thursday training meeting, first on Thu, Jan 28." |
| Orphan removed | appended: "Oct 15 is no longer a meeting date, so its move to Oct 14 was removed." |
| Saved, not shown | "Saved. VE testing session isn’t on the site: tick Show on the site." |
| Errors | "Saved, except {meeting} (outlined in red)." with the sentence in the card (existing sentences, "the webmaster" for "an administrator"). |

Help: none beyond "Stuck?" (R3: every former line restated a label or hint on the screen).

### 3.6 Documents

**List** (h1 "Documents"; button "Add a Document")
- One line above the table: "Click a name to change it, give it a new file or take it off the site."
- Every document on one page (per page 200), **in site order**: section order, then place, then title. This is the default sort, done in PHP on the query result; a clicked column sort still works (Reviewed stays sortable, and the Dashboard links to it).
- Columns: **Document** · **Section** · **Source** · **Version** · **Reviewed**. **Needs checking** is shown to administrators only.
  - Source values: File · File (none yet) · Link · Soon · "Links (4)" for a document with course links. The red "Privacy not checked" flag stays.
- Views: All · Published · Drafts · **Soon ({n})** (`spk_source=soon`) · Trash. "Mine" is hidden for editors.
- The Section filter and the bulk action "Mark reviewed today" stay.
- Help: none beyond "Stuck?" (the line above the table is the help).

**Add / Edit** (h1 "Add a Document" / "Edit Document"):

| # | Field | Input | Req | Default | Hint / notes |
|---|---|---|---|---|---|
| 1 | **Document name** | core title box, with a visible label and no placeholder | yes | | On Add, when the typed name matches an existing document (case-insensitive, ignoring punctuation), one line under it: "Already a document: ICS 309 Communications Log (Soon). Open it" |
| 2 | **Section** | radios (7) | yes | none | No "Basics" heading. |
| 3 | **Where the file is** | radios: Upload a file · Link to another site · Not ready yet (shows “Soon”) | | Upload a file | On a stored Link, the first radio reads "Upload a file instead". |
| 3a | Upload, no file yet | privacy tick **"I checked this file: no personal phone numbers, home addresses, personal e-mails or member lists, and no county, hospital, SHARES or 800 MHz details."** then **File** [Choose File], locked until ticked | tick: yes to publish | | File hint: "PDF, Word (.docx), Excel (.xlsx), JPEG or PNG." |
| 3b | Upload, has a file | "Current file: {original name}, uploaded {M j} · {size} · Open"; then **Replace with** [Choose File], locked until the tick **"I checked the new file: …(same list)."** | | | The "Remove the old file" tick is kept for administrators only; for editors it is always on. |
| 3c | Link | **Web address** (text `inputmode="url"`), hint "Copy it from your browser’s address bar."; **Site name shown** (placeholder: the host name, filled on save when empty); **Format** (select); tick **"I checked the page it links to: …(same list)."** | address: yes | | "Site name shown" and "Format" appear only here. |
| 3d | Soon | nothing else; no tick | | | The server stores the privacy flag as done, since nothing is published. |
| 4 | **Version or date** | text 30, placeholder "v3 or Sept 2026" | | | Sits right under the file/link box. Choosing a new file focuses and selects it. |
| 5 | **Short note (60 characters)** | text 60 | | | A live "{n} left" appears in the last 10 characters. No word warning. |
| — | Course links (editors, a document with course links) | read-only line: "Course links: IS-100.c, IS-200.c, IS-700.b, IS-800.d (the webmaster changes these)." | | | |
| 6 | ▸ **More options** | “How to” link (Words · Web address) · **Also list under Most used** · **Place in section** [At the top / After “…” / At the end] · **Owner** · **Last reviewed** [Mark reviewed today] | | Place: "At the end" for a new document | For a document that is one of the four buttons, the tick is replaced by one line: "It’s button 2 on the Most Used screen." ("the Most Used screen" links there); such a document is always listed. Place resets to "At the end" when the section changes. |
| — | ▸ Admin only | unchanged | | | |

- **Privacy rule (one rule, create = edit):** a fresh tick is needed whenever something new goes public: a new document, a new file, a new or changed web address, or a switch from Soon or Link to a file. Name, note or version edits need no tick. The script unticks and relocks when the source, file or address changes. The server refuses the new file or address without the tick. The stored `spk_privacy_ok` stays for what is already live.
- **Place in section:** the value is `top`, `end` or the id of the document to follow. On save the server renumbers that section 10, 20, 30 … with direct `menu_order` updates (`$wpdb->update` plus `clean_post_cache`, never `wp_update_post`, which would re-run this form's save hooks on the neighbours). A new document, or one moved to another section, goes where "Place" says, "At the end" by default, whatever its auto-draft status.
- **Link → Upload:** when the stored source was Link and it becomes Upload, "Site name shown" is cleared.
- **Upload names:** the uploaded file's original name is kept in the new meta `spk_file_name`, for display. The stored file keeps its random suffix.
- The **Webmaster check** box is shown to administrators only.

| Case | One notice ("See it on the Documents & forms page" is the link) |
|---|---|
| Published / Saved | "Published." + link / "Saved." + link |
| Replaced | "Saved. The new file is on the site and the old one is off the web." + link |
| Draft saved | "Draft saved. It isn’t on the site until you Publish." |
| Not published | one problem: "Not published yet: tick “I checked this file”, then click Publish." · several: "Not published yet. Fix the boxes outlined in red, then click Publish." (no green notice) |
| File refused, published document | "Saved, except the new file (outlined in red)." (warning) |
| Taken off the site | "“A guide to Anderson Powerpoles” is off the site." + Undo |
| Restored | "“X” is back on the site." / file removed: "“X” is back as a draft: upload the file again, then Publish." (one notice only) |

Under-field sentences:
- **Old Word file:** ".doc files aren’t accepted. In Word, use File › Save As › PDF, then choose it again."
- **Other type:** "Use a PDF, Word (.docx), Excel (.xlsx), JPEG or PNG file."
- **No tick:** "Tick “I checked this file” first, then choose the file again."
- **Web address:** "Type a web address, like https://training.fema.gov/…"

Help (form): "New version? Choose the new file (under Replace with, or Upload a file instead of a link), tick “I checked …” and click Save." (the tick's own first words, not "the privacy check")

### 3.7 Most Used (`spokares-tiles`)

```
Most Used  [View on site]
The four buttons under Most used on the For members page, left to right.
 Button  Document                                   Words on the button          Icon
 1       [Net scripts: weekly, simplex and GMRS ▾]  [Net script     ]            [Script ▾]
         Replace or change it
 2       [ICS 213 General Message ▾]                [ICS-213        ]            [Form ▾]
         Replace or change it
 …                                                                                [Save]
```
- h1 from `get_admin_page_title()`: **Most Used**. **View on site** → `/members/#quick-links`.
- Row headers are 1–4. Options that are already a button read "(now button 2)".
- Under each row's document select, a small link **Replace or change it** opens that document's form (`post.php?post={id}&action=edit`). It follows the selected document (each option carries `data-edit`; the script updates the link) and is hidden when the row has no document. This is the door for "here's the new net script".
- aria-labels: "Document for button {n}", "Words on button {n}", "Icon for button {n}".
- Picking a document that is already a button swaps the two, as today.
- Picking a different document that is **not** a swap empties "Words on the button". The placeholder then shows the new document's name, which the site prints when the words are empty. The server does the same: if the document changed and the words didn't, it clears the words.
- The foot sentence is removed. Save button **Save**. The form has the leave guard.
- Notice: "Saved." + "See it on the For members page" / "Saved, except button 2’s words (outlined in red)."
- Help (1 line): "Only published documents with a file or a link are offered."
- The Documents & forms page's **Most used** filter always includes the four buttons' documents, plus any document ticked "Also list under Most used".

### 3.8 Page Text and the block editor

**List** (menu and h1 "Page Text")
- Editors see Home, How it works and About ARES & ACS only; the three members pages are left out of the query. The columns are Title only: no checkboxes, Author, Date, date filter or "— Front Page" state.
- Help, one line: "Words only. The members pages are built on the Net Control Schedule, Exercises & Events, Meetings and Documents screens."
- The admin bar shows **Edit Page Text**. For non-admins the page type's labels carry the names: `menu_name`, `name` and `all_items` "Page Text", `edit_item` "Edit Page Text" (the admin bar's edit node reads it), `item_updated` "Saved. It’s on the site now."

**Editor**, for editors:
- The settings sidebar is closed on open, except on About, where the Page review box lives. The "Excerpt" and "Content" panels are removed.
- Hidden: the ⋮ Options menu, the page card's Rename, the block menu's "Add note", and the toolbar's **Unlink** while a Button is selected. The pencil in the link box stays.
- Hidden: list Indent/Outdent, the heading-level switcher and the sidebar's heading levels.
- On the hero photo, Replace offers Open Media Library and Upload only; "Use featured image", "Embed video from URL" and "Reset" are hidden.
- In the media window: Title, Caption, Description, File URL, "Copy URL to clipboard" and the type and date filters are hidden. "Alt Text" reads "Describe the photo in a few words".
- A photo save must leave the editor clean: no "Leave site?" after "Saved" (find the attribute that changes after upload).

| Case (after Save) | Message |
|---|---|
| Saved | snackbar "Saved. It’s on the site now." (the page `item_updated` label) |
| Layout change refused | "Not saved: only the webmaster can add, move or restyle parts of a page. Click the Undo arrow (top left) until that part is back, then Save." |
| Button emptied | "Not saved: a button needs its words. Type them back, or click the Undo arrow (top left), then Save." |
| Button lost its link | "Not saved: a button needs a link. Click the button, then the pencil in the box under it, paste the address and press Enter." |
| Hero photo removed | "Not saved: the Home photo can be replaced but not removed. Click the Undo arrow (top left), then Replace › Open Media Library." |
| Never-publish text (warning; the page is saved) | "On the site now: this page has what looks like a phone number (509-555-0142). If it isn’t public, take it out and Save." |
| A link that shows its address | "A link shows its web address instead of words: “https://www.arrl.org/ares”. Select the words people should click, then add the link." |
| HEIC photo | "Photos must be JPEG, PNG or WebP. On an iPad, pick the photo from Photo Library, which converts it." |
| Lead-in, empty item, bad link, pasting | as today |

**Page review box:** on About only. The field "Page owner" keeps its words, which the public line uses, and loses its placeholder. "Mark reviewed today when I save" is unchanged.

The dynamic blocks' hint in the canvas (`spokares_block_tail()`):
- For an editor who can open the screen: "Change this on the {Screen} screen." ({Screen} from `spokares_edit_screen()`).
- Otherwise: "The webmaster sets this."
- About's review line: "Change this in the Page review box."

### 3.9 Shared parts: Save box, admin bar, Help, sign-in, profile

**Save box** (events and documents; box title "Save"):
- Status line, with no "Status:" prefix: "On the site" · "Not on the site (draft)" · "Not saved yet".
- Buttons:
  - Published: **Save** (primary; `name="save"` stays).
  - Otherwise: **Save draft** (`formnovalidate`) and **Publish**.
- Links, one per line:
  - Any links the caller passes, e.g. events' **Make a copy**.
  - **Take it off the site** (the trash link) on a published item, or **Move to Trash** on a draft. For documents, taking an item off the site always takes its file off the web too; administrators keep "Pull this file now".
- "Save as draft (takes it off …)", its paragraph and the "Also remove its file from the web" tick are removed.

**Admin bar and footer** (non-admins):
- No "Howdy," (just the name).
- No ⌘K command palette.
- No footer "Thank you for creating with WordPress." or version.
- No "— WordPress" in the browser tab: "Net Control Schedule ‹ Spokane County ARES-ACS".
- Update lists items come from `spokares_edit_screen()` (R10).

**Sign-in and two-factor:**
- Once the phone app is set up it becomes the primary method, and the Primary Method row is hidden for editors.
- Wording:
  - "Use your authenticator app for time-based one-time passwords (TOTP)" → "Use the code from my phone app"
  - "Send a code to your email" → "E-mail me a code"
  - "Use a recovery code" → "Use a printed backup code"
  - "Authentication Code:", "Verification Code:" and "Recovery Code:" → "Code:"
  - The one-typo lockout text → "That code didn’t work. Wait for the next code in the app, then type it."
- Login screen: the club seal instead of the W, linking home, with the text "Spokane County ARES-ACS"; the browser tab reads "Log In ‹ Spokane County ARES-ACS".

**Profile** (non-admins):
- No "Additional Capabilities" row.
- The Nickname row is hidden, and the nickname is kept as First + Last.
- "Display name publicly as" reads "Name shown to other editors".
- Help:
  - "Name: how your name shows to the other editors."
  - "E-mail: where password resets and sign-in codes go. It is never shown on the site."
  - "New password: click Set New Password, then Update Profile."
  - "New phone? Under Authenticator App click Reset authenticator app, scan the code with the new phone, type its 6 digits and click Verify."

**Help tabs** (the "How to" tab) keep "Stuck? webmaster@spokares.org". A screen with no lines still gets the tab with just that line.

**The grant on a user's profile** (administrators see it): the row is "Net Settings and Meeting Schedule"; the tick reads "May change the repeaters’ frequency, offset and tone (not the club’s call sign), the net times and weeks, and the Meeting Schedule."; its note reads "Only for someone the Emergency Coordinator has named. Everyone with the ARES Editor role can already change the Net Control Schedule, events, meetings and documents."

### 3.10 Public site wording (signed out unless noted)

| Where | Now | Becomes |
|---|---|---|
| For members › Net control cell | "Open" tag; "Not yet published" | **Volunteer needed** tag (still links to `#open-slot`); **Not posted yet**; new **No net** |
| For members › line under the table | "Open slot? Tell the Net Manager (AG7QP) on the net or on groups.io." (content) | "Can you take a Tuesday marked “Volunteer needed”? Tell the Net Manager (AG7QP) on the net or on groups.io." (default, dev seed, and a one-off step on the live site, §7) |
| For members › Note cell of a No net row | rule tag; note | the row's own note only |
| For members › `<meta name="description">` (the page excerpt, stored in the database) | "…the Tuesday net rota…" | "This week’s exercises, the Net Control Schedule and the most-used documents for Spokane County ARES-ACS members." (dev seed and a one-off step on the live site, §7) |
| For members heading and anchor | "Tuesday net", `#rota` | unchanged: the visible text never said "rota", and the anchor is an identifier (R9) used by the redirect map, `tools/redirects/repoint.csv`, `dev/checks.sh` and qa-104 |
| Exercises & events › Winlink assignments | every stored task | skips No net Tuesdays |
| Undated event | "Date not posted" | **Date not posted yet**; new **Postponed** |
| Exercises & events › Next up card, an exercise's document | "Extra form: …" | **You’ll need: …** (the form's "Document members need", said as members read it) |
| Cancelled event | (vanished) | its row stays, with a **Cancelled** tag after When, until the date passes. Not in Next up, never in Past exercises. |
| Meeting with a note only | (not possible) | Home "Next: Sat, Nov 14 (Starts at 10:00 AM this time)"; For members "Sat, Nov 14: Second Saturday Workshop. Starts at 10:00 AM this time." |
| Document row with a How-to link | note hidden | note, then the How-to link on the next line |
| Documents & forms › Most used filter | ticked documents only | + the four buttons' documents |
| Signed-in "Edit this list in …" | Net rota / Events / Regular meetings / Documents / Hub tiles; "Edit these settings in Net details" | Net Control Schedule / Exercises & Events / Meetings / Documents / Most Used; "Edit these settings in Net Settings" |
| Editor-only placeholders | "Hub tiles: none set."; "No other nets are set in Net details." | "Most used: no buttons set."; "No other nets are set on the Net Settings screen." |
| Block descriptions and labels (admins, block settings panel) | net: "The weekly net: settings, rota and Winlink. Edit it in Net rota and Net details.", "Weeks (rota)"; docs: "…the hub tiles. Edit them in Documents and Hub tiles."; meetings: "…Edit them in Regular meetings."; asof: "…for the members hub." | net: "The weekly net: its settings, the Net Control Schedule and Winlink assignments. Edit them on the Net Control Schedule and Net Settings screens.", "Weeks shown"; docs: "The document library, its search box and the Most Used buttons. Edit them on the Documents and Most Used screens."; meetings: "Regular meetings with their next dates, cancellations and moves. Edit them on the Meetings screens."; asof: "Today’s date, for the For members page." Never touch the `enum` or `default`. |
| Theme template description (Site Editor, admins) | "…from Events and the Net rota in wp-admin." | "…from Exercises & Events and the Net Control Schedule in wp-admin." |
| Plugin header (Plugins screen, admins) | "Events, the net rota, meetings, …" | "Events, the Net Control Schedule, meetings, documents and the dynamic blocks for spokares.org. Deactivating it hides the lists; the data stays." |

---

## 4. Data contract (the groups build against this)

1. **Net control row state:** `call | open | none | tbd`; `open` is shown as "Volunteer needed" everywhere.
   - `spokares_normalize_rota_row()` (helpers.php) accepts `none` and clears `call` for it; `spokares_sanitize_opt_rota()` (data.php) keeps it. `spokares_rota_hash()` is unchanged.
   - The public `spokares_rota_who()` prints `<a class="tag tag--open" href="#open-slot">Volunteer needed</a>` for `open`, `<span class="rota-tbd rota-none">No net</span>` for `none` and `<span class="rota-tbd">Not posted yet</span>` for `tbd` (the theme's existing `.rota-tbd` style covers both, so no theme CSS changes). `spokares_rota_note()` prints only the row's note for `none`.
   - **Form:** `spokares_winlink_forms()` is unchanged (it still lists 'ICS-213', so a stored 'ICS-213' is never "Other…"). The select prints every form except 'ICS-213' after the '' option "ICS-213 (usual)", and selects '' when the value is '' or 'ICS-213'. In `spokares_handle_save_rota()`, a posted '' is replaced by 'ICS-213' when the row's stored form is 'ICS-213', before the hash comparison, so untouched rows stay unchanged and stored values stay.
2. **Changed-rows tint:** a per-user transient `spokares_rota_changed_{user}` holds the dates, set on save, undo and redo, and read-and-deleted on the next view.
3. **Meetings:**
   - A change `kind` is `cancelled | moved | note`. The sanitizer accepts `note`.
   - `spokares_next_meetings()` treats a `note` change as a normal occurrence `{date:d, orig:d, kind:'note', note}` and lists it in `changes`.
   - A meeting may carry `next = {from:Y-m-d, name, nth[], weekday, start, end, time_text, home_extra, skip_months[]}` ("Takes effect on" is `from`). `spokares_normalize_meeting()` merges `next` into the rule and drops it once `from <= today`.
   - `spokares_meeting_on()` and `spokares_meeting_rule_dates()` use the pattern in effect on each date: two segments split at `next.from`.
   - `spokares_next_meetings()` returns in `meeting` the rule as it is in effect on the first returned date (id, `show_home` and `active` from the stored rule). Home and For members lines therefore describe the right pattern without render changes.
   - "Shown" = `active && show_home`. The Meeting Schedule's one tick writes both.
4. **Events:**
   - New meta `spk_cancelled` ('1' or absent). New `spk_date_mode` value `postponed`.
   - `spokares_event_data()` (format.php) adds `'cancelled' => bool`, and accepts `postponed`, which counts as undated.
   - `spokares_fmt_when()` (format.php) for `row`/`card`: `postponed` prints "Postponed", `not-posted` prints "Date not posted yet".
   - `spokares_events()` (schedule.php): `next-up` and `past` exclude cancelled events; `later`, `public-service` and `upcoming` keep them.
   - Renders (render.php) add `<span class="tag tag--line">Cancelled</span>` after the When text of a cancelled event's row, card or For members line.
   - Both meta keys are registered with the event meta, `spk_file_name` with the document meta (data.php).
   - The display labels of `spokares_event_kinds()` (data.php): `training` → "Training or meeting", `on-air` → "On the air (from home stations)". The keys don't change.
   - An `as-requested` mode saved with a type other than Public service is stored as `not-posted` (as today).
5. **Documents:**
   - `spk_file_name`: the original upload name.
   - The library's `most_used` (render.php `spokares_library_documents()`) is `spk_most_used` or the document is one of the four `spk_tiles`.
   - `spokares_document_title_cell()` prints the note, then the How-to link.
   - List filter `spk_source=soon`.
6. **Net Settings stamp:** option `spk_net_saved` = `{at, by}`.
7. **Guards (`guards.php`):**
   - `spokares_clean_url()` adds `https://` to a scheme-less address whose first part has a dot and no spaces (`www.arrl.org/kids-day`).
   - `spokares_check_text()` hits gain `match`: the first matched text, trimmed, at most 60 characters.
   - New `spokares_problem_sentence( array $check ): string` gives the under-field sentence for a `spokares_check_field()` result:
     - block: "It mentions {what}, which we never publish. Take it out."
     - phone: "It has a phone number. Take it out, or tick the box if it’s a public agency number."
     - e-mail: "It has an e-mail address. Take it out, or tick the box if it’s a public agency address."
     - both: "It has a phone number and an e-mail address. Take them out, or tick the box if they’re public agency contacts."

   Every form uses it for phone, e-mail and never-publish problems.
8. **Notices:**
   - `spokares_add_notice()` sets `$GLOBALS['spokares_notice_queued'] = true`.
   - For `spk_event` and `spk_document`, the events file's `redirect_post_location` filter drops core's `message` argument when that flag is set, so each save shows exactly one notice.
   - `spokares_draft_saved_message()` is deleted from `admin-common.php`; the events file replaces it.
9. **Save box:** `spokares_render_save_box( WP_Post $post, array $links = array() )`, where each link is `['label'=>…, 'url'=>…]`. The old `$trash_note` parameter is removed.
10. **Script config:**
    - `window.spokaresAdmin` shrinks to `{ today, weekdays, bands }`.
    - Each area adds its own object from its own PHP file (`window.spokaresRota`, `spokaresNet`, `spokaresEvents`, `spokaresDocs`) with `wp_add_inline_script` on its own handle.
    - `admin-forms.js` keeps the shared parts: the problem links (`problems()`), `asks()`, the new **leave guard** for `form[data-spk-guard]`, and the Net Control Schedule and Net Settings code. `confirms()` goes (no screen confirms any more).
    - Events and meetings code moves to the new `assets/js/admin-events.js`; documents and Most Used code to the new `assets/js/admin-documents.js`. Each is enqueued by its owner, after `spokares-admin-forms`, and carries its own copies of the small helpers it needs (`$`, `$$`, `spares()`).
11. **Help keys:** `spokares-rota`, `spokares-net-details`, `spokares-meetings`, `spokares-meeting-rules`, `spokares-tiles`, `spokares-site`, `spk_event` (form), `spk_event_list`, `spk_document` (form), `spk_document_list`, `page_list`, `dashboard`, `dashboard-none`, `profile`. `spokares_cpt_help()` picks `*_list` on `edit` screens. A key with no lines prints only "Stuck?".
12. **Screen names (`spokares_edit_screen()`, keys unchanged, one new key):**

    | Key | Name | URL | Capability |
    |---|---|---|---|
    | `rota` | Net Control Schedule | `admin.php?page=spokares-rota` | `spokares_edit_rota` |
    | `net-details` | Net Settings | `admin.php?page=spokares-net-details` | `spokares_edit_net_details` |
    | `events` | Exercises & Events | `edit.php?post_type=spk_event` | `edit_spk_events` |
    | `meetings` | Meetings | `admin.php?page=spokares-meetings` | `spokares_edit_rota` |
    | `meeting-rules` (new) | Meeting Schedule | `admin.php?page=spokares-meeting-rules` | `spokares_edit_net_details` |
    | `documents` | Documents | `edit.php?post_type=spk_document` | `edit_spk_documents` |
    | `tiles` | Most Used | `admin.php?page=spokares-tiles` | `edit_spk_documents` |
    | `page-review` | Page review box | '' | `edit_pages` |

---

## 5. EDITING-GUIDE.md changes

Keep it the owner's way: short steps, each thing said once, the screens' own words. In running text, say "the X screen" or "the X page" (R2). Rewrite:

- **Jobs table** (line 9 onward):

  | # | Job | How often | Time |
  |---|---|---|---|
  | 1 | Update the Net Control Schedule | Monthly | 3 min |
  | 2 | Add or change an exercise or event | As needed | 3 min |
  | 3 | Cancel or move a meeting | As needed | 1 min |
  | 4 | Add, replace or change a document | As needed | 3 min |
  | 5 | Change words on a page | Now and then | 5 min |

- **Signing in**, step 3: "Type the code from the app on your phone. No phone? Click **E-mail me a code**. Neither works? Click **Use a printed backup code**, then tell the webmaster."
- **Dashboard** (lines 27–31): name the sections as they appear ("how far ahead the Net Control Schedule is posted, the next event, the next meeting"). Needs attention sits at the top. Alt text: "The Dashboard's Site tasks card: Net Control Schedule with Update the schedule, Exercises & Events, Meetings, Documents with Replace or change a document, and Page Text." The shortcut sentence: "…every list has a small link under it, such as “Edit this list in Net Control Schedule”."
- **Job 1: Update the Net Control Schedule** (lines 35–51), Dashboard › **Update the schedule**:
  - Type the call sign, or choose **Volunteer needed**, **No net** or **Not posted yet**.
  - Winlink nights: type the assignment and pick the form (**ICS-213 (usual)** unless another is named).
  - Click **Save**. The message names the Tuesdays and links to the For members page.
  - Bullets:
    - Call signs only.
    - **Show 13 more Tuesdays** keeps your typing.
    - **Undo** is next to "Last saved" at the top; **Redo** takes the undo back.
  - Alt text: "The Net Control Schedule screen: one row per Tuesday with a call-sign box and Volunteer needed, No net and Not posted yet, then Winlink assignment, Form and Note."
  - Drop the Shows-column bullet and the "Note … No net" example.
- **Job 2:**
  - Steps: Add an event · **Type of event** · Name · When · Publish. The message says where it shows.
  - Bullets:
    - **Change one:** Dashboard › **Change or cancel an event**.
    - **Next year:** open last year's › **Make a copy**.
    - **Called off:** tick **Cancelled** under When.
    - **Postponed:** choose **Postponed**.
    - **Added by mistake:** **Take it off the site** (Undo brings it back).
  - Alt text uses Type of event, Button and Document members need, and Save (not Update).
  - Delete the "Events never show on Home" paragraph and the link-box paragraph.
- **Job 3:**
  - Tick **Cancelled** or pick the new date under **Moved to**. A different time or room that day: type it in the **Note**, like "Starts at 10:00 AM this time". To take back a cancellation, untick **Cancelled**.
  - "If your menu has Meeting Schedule: change a meeting's regular day or time there, with **Takes effect on** for a change from a later date."
- **Job 4, add:** Section · Where the file is · tick "I checked this file" · Choose File · Version · Short note (60 characters) · Publish. Use the regenerated `shots/final/admin-document-ics-213.png` (the same form, R5) instead of `shots/review-editor/32-doc-add-filled.png`, and drop the "(Taken during the editor review…)" line.

  Everything else in Job 4:
  - **Replace or change:** Dashboard › **Replace or change a document** › click its name › choose the new file (Replace with, or **Upload a file instead** for a link) › tick › version › **Save**. For a Most Used document (the net script), the Most Used screen's **Replace or change it** link goes straight to it.
  - **Take one down:** **Take it off the site**.
  - **Most Used:** Documents › **Most Used**. Pick a document for each button and click **Save**.
  - **Delete** the "keep the Most used filter in step" paragraph.
- **Job 5:**
  - A button's link: "click the button; the box under it shows where it goes: click the pencil, paste the new address, press Enter, then Save."
  - Photo: Replace › Upload; describe the photo in the window.
  - Messages: use the new wording from §3.8.
  - Delete the excerpt and Rename paragraphs.
  - Page review: "About: tick **Mark reviewed today when I save**."
  - One line for one-off notices (Frank's gap, §8): "**A one-off notice** (“no nets over the holidays”): mark those Tuesdays **No net** and add a Note. Anything else, like “Field Day photos are up”, has no place on the site yet: ask the webmaster."
- **Please don't:** "**Net Settings** (repeater, tone, net weeks) and the **Meeting Schedule** are changed by the webmaster or someone the Emergency Coordinator names."
- **Screenshots:** keep the file names (R9). The verifier regenerates them after integration (§7) and the alt texts above use the new words.

PLAN.md is updated to record these decisions: §0 row 7 and §2.2 row 24 (screen names), §3.4 (the menu line and each screen), §3.5–3.6, §4.2 ("labels are the words editors use", now with the new names) and §4.4. Add one line to §4.2: "Screens use plain spoken names (Net Control Schedule, Most Used …); internal identifiers (`spk_rota`, `spokares-rota`, `#rota`, `view:"rota"`, `spk_tiles`) keep their old names on purpose."

---

## 6. Findings disposition

Walker ids:
- **NET** = net-control and Net details walker
- **EVT** = events and meetings walker
- **DOC** = documents and tiles walker
- **PG** = dashboard and pages walker
- **FC** = first-click walker

The order is the walkers' own order. Rows changed in v0.3 are marked (v0.3).

| Id | Finding | Decision | Change / reason |
|---|---|---|---|
| NET-1 | "Show 13 more" reloads and loses typing (blocker) | Accepted | All rows drawn and revealed in place; leave guard on every settings form (§3.2, R7) |
| NET-2 | "Rota" everywhere | Accepted (owner directive) (v0.3) | "Net Control Schedule" menu, title, links, Dashboard and guide; Dashboard button "Update the schedule"; Save "Save"; the `#rota` anchor and every other identifier stay (R9) |
| NET-3 | Saved notice doesn't name dates; For members shows only five | Accepted | Notice names dates and says "The For members page lists the next five Tuesdays" when beyond |
| NET-4 | Call-sign box grey on Not posted yet | Accepted | White box, placeholder "Call sign"; grey only for Volunteer needed / No net |
| NET-5 | "Not posted yet" vs "Not yet published" | Accepted | "Not posted yet" everywhere, public and Dashboard too |
| NET-6 | Shows column repeats and misleads | Accepted | Column removed; one red line under the box when there's no call sign |
| NET-7 | No "No net" choice | Accepted | Fourth choice; public "No net"; counts as posted |
| NET-8 | Winlink and Note text truncated | Accepted | 2-line assignment box; new column widths |
| NET-9 | Undo discards typing, says who not what, big button by Save | Accepted (v0.3) | "Last saved … (dates) · Undo" in the title line; notice names dates; changed rows tinted; leave guard. After an undo the title line offers **Redo** (Word's word), in the title line only |
| NET-10 | Net details preview far away and says it five ways | Accepted | "Members see" line under each section; preview box and Copy / From-home lines removed |
| NET-11 | A week ticked twice is silently ignored | Accepted | One select per week |
| NET-12 | "Net details" vague; parent repeats as child | Accepted, modified | "Net Settings" (the screen also sets which Tuesdays and two wording lines, so "Repeater & net times" undersells it); parent "Net Control Schedule" per the directive |
| NET-13 | Read-only call-sign box looks editable; "administrator" | Accepted | Plain text "W7GBU (the webmaster changes this)" |
| NET-14 | Field order doesn't match thinking | Accepted | Four sections: The Tuesday net / Alternate repeater / Which Tuesdays / Wording on the site |
| NET-15 | Confirm every save; "Saved." when nothing changed; no what-changed; no Last saved | Accepted | No confirm; "(was …)" notice; "Nothing changed"; Last saved line. An Undo for Net Settings is rejected: the "(was …)" notice is the way back, and a second undo system adds weight |
| NET-16 | Frequency: browser bubble, silent preview, error said five ways | Accepted | Live one-line check, no `pattern`; one notice plus the line; banner removed |
| NET-17 | Name-only error said three times | Accepted | "Not saved: Oct 20 (outlined in red)." plus one line under the box |
| NET-18 | Name + call gives two notices | Accepted | One green: "Saved Oct 13: NZ2S (names aren’t posted)." |
| NET-19 | Phone-in-note message says what's wrong, not what to do | Accepted | `spokares_problem_sentence()` under the box; the notice names the date only |
| NET-20 | Dashboard "Posted through" stops at 13 rows, hides gaps | Accepted | Reads 52 weeks; "Volunteer needed" and "Not posted yet" lists |
| NET-21 | Help: empty line, rule said four times, "Open" meaning | Accepted (v0.3) | "Volunteer needed"; one Help line (where Winlink and notes show); the call-sign rule stays in the lede only |
| NET-22 | Form "— none —" really means ICS-213 | Accepted (v0.3) | The blank choice is "ICS-213 (usual)" and the duplicate "ICS-213" choice goes; stored values stay (§4.1) |
| NET-23 | iPad and phone cards long; Save at the bottom | Accepted | Sticky Save ≤1024px; 16px date; per-card labels hidden |
| NET-24 | Help and hints say "members hub", "rota"; rule said three times | Accepted | "the For members page"; Asking-for-volunteers hint reworded; amateur rule in the field hint only |
| EVT-1 | Held-back typing lost after one page view | Accepted | Kept until the next save of that event |
| EVT-2 | Held-back notice false; three notices contradict | Accepted | One red notice naming fields; "Everything else is on the site now."; "held back" retired |
| EVT-3 | New meeting starts hidden; two ticks for one idea | Accepted | One "Show on the site" tick, on by default; notice when not shown |
| EVT-4 | Dashboard "Next:" names a hidden meeting | Accepted in part | Counts shown meetings only. Deleting the hidden Winlink workshop rule is rejected: it's the webmaster's content, and with one tick it is simply "not shown" and appears nowhere |
| EVT-5 | Meeting screens named backwards, under Events; menu says "Events" | Accepted | Meetings › Cancel or Move a Meeting · Meeting Schedule; menu "Exercises & Events" (submenu "All Events", "Add an Event", not "Add one") |
| EVT-6 | Un-cancelling refused with the wrong advice | Accepted | Unticking clears the note; server fallback; message under the row only |
| EVT-7 | No one-date time or room change | Accepted | A note on its own is a one-date change; column "Note (shown with the date)" |
| EVT-8 | Schedule change can't start in January; orphan moves; vague confirm | Accepted (v0.3) | Per-card "Takes effect on" date; orphans removed and named; confirm replaced by a notice that states the next dates |
| EVT-9 | Short line offered but never shown for public service | Accepted | Hidden for Public service |
| EVT-10 | Postponed or called off is never shown to members | Accepted, modified | A "Postponed" When choice, and a "Cancelled" tick under When (prints "Cancelled", not in Next up). A tick rather than a Save-box link: it's a fact about the event and is undone the same way |
| EVT-11 | Restore brings it back hidden; Trash view status "Draft" | Accepted | Restore to the previous status; Where it shows "In the Trash" |
| EVT-12 | Duplicate is hover-only; copy opens with an error | Accepted | "Make a copy" in the Save box and row actions; no error text until the first save |
| EVT-13 | Two or three notices per save; "Never on Home" repeated | Accepted (v0.3) | One notice; "Never on Home" deleted; no type line at all (the notice says where it went) |
| EVT-14 | "Kind (pick one first)"; the On the air line always shows | Accepted | "Type of event (required)" in the form, list and errors; the On the air line removed; outline fixed |
| EVT-15 | Publish without a type gives red plus green | Accepted | `required` in the browser; one red notice |
| EVT-16 | "Please enter a URL." for www…; label repeated four times | Accepted | Script and server add https://; label "Web address"; hint once |
| EVT-17 | Button-words placeholder wrong for most links | Accepted (v0.3) | "Details" / groups.io switch. The group is "Button" and the field "Words on the button", the same words as the Most Used screen (R4: no "(optional)") |
| EVT-18 | Where hint wrong; "Short line for lists"; uneven optional markers | Accepted | "Short description"; Where hint shortened; required markers only |
| EVT-19 | "Needs checking" red Yes; Status repeats the view | Accepted (v0.3) | Admin-only flag and box; a "Where it shows" column replaces Status |
| EVT-20 | Dashboard has no equal "change an event" route | Accepted | "Next:" name links to its form; "Change or cancel an event" button |
| EVT-21 | Save box speaks WordPress | Accepted, modified | "Save"; one removal link "Take it off the site" (Trash, Undo) replaces both "Save as draft (takes it off the site)" and "Move to Trash"; status lines; "Make a copy" |
| EVT-22 | "After it ends:" + "Keep in Past exercises"; month-only dates look like the 1st | Accepted | "List under Past exercises after it ends"; precision hint |
| EVT-23 | "Not listed…" webmaster talk; note destination unclear | Accepted in part | Line deleted; note column header. The optional "No meeting in Dec" row is rejected: it adds a row to maintain, and skipped months are set on the Meeting Schedule screen |
| EVT-24 | iPad: icon-only menu, hover actions, Publish at the bottom | Accepted in part | Menu keeps its labels to 783px; "Make a copy" in the Save box. A Save box above the fields or a second Publish is rejected: on a narrow screen the form ends in its Save box, where a form's button belongs, and a second button repeats it |
| EVT-25 | A potluck fits no type | Accepted | "Training or meeting"; the potluck can also be a note on Dec 12 (EVT-7) |
| EVT-26 | List shows the form's Help; nothing on change or cancel | Accepted | Separate list and form Help keys; new list lines; form Help has "Stuck?" only |
| DOC-1 | Changing a tile's document keeps the old words (blocker) | Accepted | Words emptied on a non-swap change, placeholder is the document name; server clears too |
| DOC-2 | New documents land at the top | Accepted | "Place in section", default "At the end", applied whatever the auto-draft status |
| DOC-3 | Reordering needs number guessing | Accepted | "Place in section" dropdown; server renumbers by 10s; resets on section change |
| DOC-4 | Word limit vs silent 60-character cut | Accepted | "Short note (60 characters)" + "{n} left"; word warning dropped |
| DOC-5 | "Hub tiles" vs the site's "Most used" | Accepted, modified | Screen "Most Used", rows are "buttons" 1–4 (the owner lists "tiles" as jargon, so not "Most used tiles"). "Members page shortcuts" is rejected (§8, implementation 7) |
| DOC-6 | Two separate "Most used" lists | Accepted | The filter always includes the four buttons; the tick is "Also list under Most used"; the guide paragraph is deleted |
| DOC-7 | "Replace a document" lands on a 2-page A–Z list | Accepted (v0.3) | "Replace or change a document"; one page in site order; cue line; five columns; each Most Used row links to its document |
| DOC-8 | Replacing a linked net script keeps the old source | Accepted | "Upload a file instead"; Site name cleared on Link→Upload; Site name and Format shown for links only; Help line |
| DOC-9 | On Edit a new file skips the privacy check | Accepted | Fresh tick for anything new going public (§3.6) |
| DOC-10 | Retiring offers four controls; draft leaves the file live | Accepted | "Take it off the site" (Trash + file off the web); draft link, paragraph and tick removed; one restore notice |
| DOC-11 | Failed publish: red + green + repeats | Accepted | One red notice; field sentences only under fields |
| DOC-12 | Privacy check is a list of nouns; wrong hint for links | Accepted | One sentence per source ("I checked this file / the new file / the page it links to"); no tick for Soon |
| DOC-13 | Note hidden when a How-to link exists | Accepted | Both print: the note, then the link |
| DOC-14 | DOCX/XLSX jargon; .doc not explained | Accepted | "Word (.docx), Excel (.xlsx)"; .doc sentence says how to save as PDF |
| DOC-15 | Replace feedback thin; random file name; version stale | Accepted | Replace notice; original name kept; Version under the chooser, focused on a new file. It is not auto-cleared, since Frank may keep it |
| DOC-16 | FEMA courses look "Soon"; course links invisible | Accepted | Read-only course-links line; Source "Links (4)" |
| DOC-17 | Webmaster check and Needs checking are noise | Accepted | Administrators only |
| DOC-18 | Name has no label; "Basics"; "Library section"; version format; link-only fields shown for uploads | Accepted in part | Label, no Basics, "Section (required)", placeholder, link-only fields. Pre-selecting the section from a filtered list is rejected: Frank starts Add from the Dashboard, not a filtered list |
| DOC-19 | List Help is the form's; "Mine"; stray drafts | Accepted in part | Cue line; "Mine" hidden. Hiding drafts with no section is rejected: it hides real work, and the row says Draft |
| PG-1 | Guide sends Frank to Unlink; linkless button saves | Accepted | Guide fixed; server refuses a button that lost its link; Unlink hidden on buttons |
| PG-2 | Photo "Reset" removes the hero | Accepted | Server refuses an empty hero; Replace menu trimmed |
| PG-3 | 2FA asks for the e-mail code first; three names for one code | Accepted | TOTP primary when set; Primary Method hidden; plain labels |
| PG-4 | Site tasks panel can be collapsed for good | Accepted | Never closed; handle buttons hidden |
| PG-5 | Structural controls offered, then refused at Save | Accepted | Indent/Outdent, heading level controls hidden; new refusal wording, "the webmaster" |
| PG-6 | Never-publish warning reads as "not live yet"; no match; Needs attention low and unlinked | Accepted | "On the site now: … ({match})"; Needs attention first, each line linked |
| PG-7 | Editor chrome overload | Accepted | Sidebar closed except About; Excerpt and Content panels removed (description checks become admin-only); Options, Rename, Add note hidden |
| PG-8 | Pages list is raw WordPress with dead rows | Accepted | "Page Text", three pages, trimmed columns, one Help line; the admin bar says "Edit Page Text" |
| PG-9 | Page review box does nothing on two pages | Accepted in part | About only; placeholder removed. Renaming "Page owner" to "Kept current by" is rejected: the public About line says "Page owner", and the box uses the same words |
| PG-10 | Save leaves no lasting sign it's live | Accepted in part | "Saved. It’s on the site now." Changing core's Save button states is rejected (core UI) |
| PG-11 | "Edit in wp-admin ›" jargon, not a link | Accepted (v0.3) | "Change this on the {Screen} screen." / "The webmaster sets this." |
| PG-12 | One wrong code reads like a lockout | Accepted | "That code didn’t work. Wait for the next code in the app, then type it." |
| PG-13 | Profile: raw capability, Nickname, Help promises a security key | Accepted in part | Capability row hidden; nickname hidden and kept; Help "New phone?". Moving "Update Profile", and the plugin removing the old phone before the new one is verified, are rejected: both are core or Two-Factor behaviour; the Help line gives the safe order |
| PG-14 | Dashboard Help restates the screen; editor Help goes to wordpress.org | Accepted | One Dashboard line; the Options menu (with its Help) hidden |
| PG-15 | Login screen is all WordPress | Accepted | Seal, home link, club name |
| PG-16 | HEIC refusal is WordPress wording | Accepted | Plain message with the iPad tip |
| PG-17 | Media window fields do nothing; alt text asked twice | Accepted | Fields hidden; "Describe the photo in a few words" |
| PG-18 | A link can show its address as its words | Accepted | Warning after Save |
| PG-19 | Howdy, ⌘K, WordPress footer | Accepted | Removed for non-admins |
| PG-20 | Documents buttons misaligned when wrapped | Accepted | Flex row with gap |
| FC-1 | Show 13 more loses typing | Accepted | = NET-1 |
| FC-2 | "Rota" terms | Accepted (v0.3) | = NET-2. The menu is "Net Control Schedule" (the directive), the Dashboard button "Update the schedule" (its own proposal, which the implementation critique repeated), Save "Save" |
| FC-3 | No-net Tuesday reads "Not yet published" | Accepted | = NET-5, NET-7 |
| FC-4 | Add document duplicates a "Soon" item | Accepted | Name-match line on Add ("Already a document: …"); Dashboard "{n} marked Soon" with the Soon view |
| FC-5 | "Most used" means two things; "Hub tiles" | Accepted | = DOC-5, DOC-6 |
| FC-6 | Tile words stay when the document changes | Accepted, modified | = DOC-1: words emptied with the name as placeholder, rather than filled with the name and selected, because the site already falls back to the name and there's less to edit |
| FC-7 | Meeting change from January impossible | Accepted, modified (v0.3) | = EVT-8, with a "Takes effect on" date per card rather than a month dropdown |
| FC-8 | Meeting screen names and menu | Accepted | = EVT-5; public link "Edit this list in Meetings"; tick "Show on the site" |
| FC-9 | Potluck note refused | Accepted | = EVT-7 |
| FC-10 | "Replace a document" lands on a plain list; linked script has no Replace | Accepted in part (v0.3) | = DOC-7, DOC-8 ("Upload a file instead", "Replace or change it" on Most Used). Autofocus on search is rejected: it pops the iPad keyboard, and one page in site order makes search unneeded |
| FC-11 | Hint names Save draft/Publish on Edit; old source and version persist | Accepted | The "(it uploads when …)" hint is removed; = DOC-8, DOC-15 |
| FC-12 | Past date saved with no weekday and a vague notice | Accepted | Live weekday; one "has passed … Check the year." notice |
| FC-13 | Kind box overload | Accepted | = EVT-14, EVT-13 |
| FC-14 | Double notices; publish without the tick | Accepted | One notice; the privacy tick is `required` for Publish |
| FC-15 | Two removal routes; members never told | Accepted, modified | = EVT-10, EVT-21, DOC-10. The "Moved from Sat, Oct 24" line for two weeks is rejected: the new date is what members need, and it adds a state to keep |
| FC-16 | Needs checking noise | Accepted | = EVT-19, DOC-17 |
| FC-17 | Dashboard status lines say the change didn't take | Accepted | = NET-20, EVT-4 |
| FC-18 | Empty position sorts first | Accepted | = DOC-2 |
| FC-19 | Privacy check differs on Add and Edit | Accepted | = DOC-9, DOC-12 |
| FC-20 | Editor dirty after photo upload | Accepted | Investigate and fix (§3.8) |
| FC-21 | Block editor sidebar jargon; Replace menu | Accepted in part | = PG-7, PG-2, PG-17. Hiding the focal point is rejected: it's a real control for the hero crop, and the closed sidebar already removes the clutter |
| FC-22 | Meeting notice links Home only; no Undo | Accepted in part | The notice names the dates and what happened. An Undo on meetings is rejected: un-cancelling is now one tick |
| FC-23 | Net details says "administrator"; the ticked-twice rule | Accepted | = NET-13, NET-11 |
| FC-24 | iPad menu icons; rota boxes cut off | Accepted | = EVT-24, NET-8; the call-sign box fits 6 characters |
| FC-25 | Number badges suggest an order; About is a guess | Accepted in part | Badges removed. The "About (leaders, joining)" link text is rejected: T18 passed, and it would give one page's link a description the others don't |

---

## 7. Implementation and verification

Five groups own non-overlapping files. Tests (`dev/tests/**`, `dev/checks.sh`) are shared: each group updates the assertions for its own strings and behaviour with targeted edits, and never weakens a behavioural check. The exact change lists are returned to the orchestrator with this spec.

| Group | Owns |
|---|---|
| 1 Navigation, Dashboard, shared chrome and public rendering | `inc/admin-common.php`, `inc/dashboard.php`, `inc/admin-bar.php`, `inc/admin-trim.php`, `inc/blocks.php`, `inc/render.php`, `inc/guards.php`, `inc/roles.php`, `spokares-core.php`, `blocks/{asof,docs,meetings,net}/block.json`, `theme/spokares/inc/setup.php`, `mu-plugins/spokares-hardening/two-factor.php` |
| 2 Net Control Schedule and Net Settings | `inc/admin-rota.php`, `inc/admin-net.php`, `assets/js/admin-forms.js`, `assets/css/admin.css` |
| 3 Exercises & Events, Meetings and the data layer | `inc/admin-events.php`, `inc/admin-meetings.php`, `inc/schedule.php`, `inc/helpers.php`, `inc/data.php`, `inc/format.php`, new `assets/js/admin-events.js`, new `assets/css/admin-events.css` |
| 4 Documents and Most Used | `inc/admin-documents.php`, `inc/admin-tiles.php`, new `assets/js/admin-documents.js`, new `assets/css/admin-documents.css` |
| 5 Page Text, the block editor, the guide, the plan, seed and ops | `inc/admin-pages.php`, `inc/governance.php`, `assets/js/editor-guard.js`, `EDITING-GUIDE.md`, `PLAN.md`, `ops/ci.sh`, `ops/DEPLOY-RUNBOOK.md`, `dev/seed/extra.json`, `dev/seed/import.php` |

Nobody edits `theme/spokares/patterns/members-hub.php`, `mu-plugins/spokares-hardening/redirect-map.php`, `tools/redirects/repoint.csv` or `dev/seed/data.json` (generated from the design source): the `#rota` anchor and `view:"rota"` stay (R9).

**The "rota" guard** (group 5, in `ops/ci.sh`; it replaces a check per string). Fail when either finds anything:
- `grep -rnIiE "(__|_e|_n|_x|esc_html__|esc_html_e|esc_attr__|esc_attr_e)\(\s*'[^']*\brota\b" plugins theme`
- `grep -nwiE '"(title|description|label)".*\brota\b' plugins/spokares-core/blocks/*/block.json`, and `sed -E 's/[A-Za-z0-9_-]*rota[A-Za-z0-9_-]*\.png//g' EDITING-GUIDE.md | grep -nwi rota` (the screenshot file `admin-net-rota.png` keeps its name, R9; block.json's `"default"` and `enum` hold the identifier).

And in `dev/checks.sh`: the /members/ page's `<meta name="description">` must not contain "rota".

**One-off step on the live site** (group 5 adds it to `ops/DEPLOY-RUNBOOK.md`, under the release that ships this; a code deploy can't change stored content):
- `wp post update $(wp post list --post_type=page --name=members --field=ID) --post_excerpt="This week’s exercises, the Net Control Schedule and the most-used documents for Spokane County ARES-ACS members."`
- If Net Settings › **Asking for volunteers** still reads "Open slot? Tell the Net Manager (AG7QP) on the net or on groups.io.", change it there (or with `wp option patch update spk_nets open_slot_line "…"`) to "Can you take a Tuesday marked “Volunteer needed”? Tell the Net Manager (AG7QP) on the net or on groups.io."

**Done when:**
- `dev/tests/run-all.sh <port>` passes on each group's own port, and again after integration.
- `ops/ci.sh --no-zip` passes after integration, with the rota guard.
- A grep of the rendered HTML of every editor screen and public page finds "rota" only in identifiers (R9).
- The guide's screenshots are regenerated with the same file names: `dev/shots.sh <PORT> <tmpdir> --only=admin`, then copy `admin-dashboard.png`, `admin-net-rota.png`, `admin-event-set.png`, `admin-meetings.png`, `admin-editor-home.png` and `admin-document-ics-213.png` into `shots/final/`, and check each alt text against its picture.
- A short re-walk of first-click tasks T1, T3, T4, T10, T11, T14 and T17 passes, plus "here's the new net script" from the Dashboard and from the Most Used screen.

---

## 8. Critique responses (v0.3)

### Frank

| # | Critique | Response |
|---|---|---|
| 1 | "Open slot" breaks R1 and names the same thing as the public "Open" tag | **Accepted.** "Volunteer needed" everywhere: the radio, the public tag, the Dashboard ("Volunteer needed: Oct 6, Oct 20"). The Net Settings field is "Asking for volunteers", and its default line now says "Volunteer needed" too. The stored value stays `open` (R9). |
| 2 | Screen and page names differ only by a capital | **Accepted.** R2: a sentence that names a place adds "screen" or "page" ("It’s button 2 on the Most Used screen"). Case alone never tells two things apart. |
| 3 | "See it on For members" is broken English | **Accepted.** "See it on the For members page", "…the Home page", "…the How it works page", "…the Documents & forms page"; the event notice is Frank's sentence. |
| 4 | "Next up card" | **Accepted.** "Next up". |
| 5 | No door for "here's the new net script" | **Accepted.** Dashboard button "Replace or change a document"; each Most Used row has a "Replace or change it" link to its document's form. |
| 6 | Help repeats the screen | **Accepted**, and applied to every screen (R3). Net Settings keeps one line; Net Control Schedule keeps one line (Winlink and notes, which also answers implementation 4); Cancel or Move a Meeting keeps only the regular-times line; Most Used keeps only the "offered" line; Meeting Schedule now has none. "One-day notice" is gone from the spec and the guide. |
| 7 | "Changes start" reads like a third time box | **Accepted.** "Takes effect on", hint "Leave empty for now", placed last on the card, on the Add card too. |
| 8 | The Type hint is a third "where it shows" | **Accepted.** No type hint on Add or Edit; R5's exception for it is gone. |
| 9 | Two ICS-213s in the Form select | **Accepted.** "ICS-213 (usual)" first, no second "ICS-213"; stored values stay (§4.1). |
| 10 | "Put it back" | **Accepted.** "Redo". |
| 11 | "Button words" vs "Words on the button" | **Accepted.** "Words on the button" on both screens; the group is "Button". |
| 12 | "Extra form" reads like a second ICS form | **Accepted.** "Document members need", no hint. |
| 13 | "Shows" reads like a noun | **Accepted.** "Where it shows". |
| 14 | "Post net control" is an odd command | **Accepted.** "Fill in more Tuesdays". |
| 15 | "3 not reviewed in 12 months" can't be acted on | **Accepted, linked.** It opens the Documents list sorted by Reviewed, oldest first (the column is already sortable). Kept rather than dropped: review dates are part of PLAN's governance, and the link makes it a job. |
| 16 | "library" is a third name | **Accepted.** "37 documents", "Already a document: … Open it"; the Extra form hint is gone (12). The public page's own "Search the library" stays: it is the visitors' page. |
| 17 | "As requested (Public service only)" | **Accepted.** Shown only when Public service is picked, no parenthetical. |
| 18 | "Time words (instead of …)" carries its own hint | **Accepted.** "Time as words", placeholder "evenings". |
| 19 | Guide "People named for it:" | **Accepted.** "If your menu has Meeting Schedule:". |
| 20 | Gap: nowhere for a one-off notice | **Not Home.** Home's layout is locked and has no notice spot, so editing its words to carry an announcement would misuse the hero. Net breaks are covered: **No net** with a Note. Meeting changes: a Note. For anything else the guide says, in one line in Job 5, to ask the webmaster. **Question for the owner:** do you want a one-line notice on Home (or on the For members page) that editors can set and that expires on a date? It is a new feature, so it is not in this build. |
| Works | Menu, Cancel or Move a Meeting, Make a copy, Take it off the site, No net, Not posted yet, sign-in wording, one notice per save | Kept as is. |

### Implementation

| # | Critique | Response |
|---|---|---|
| 1 | Leave every stored or linked identifier alone, including `id="rota"` | **Accepted.** R9 now keeps `#rota` (v0.2 had renamed it to `#net-control`). View on site and "See it on the For members page" still go to `/members/#rota`. The redirect map, `repoint.csv`, `checks.sh`, qa-104 and `members-hub.php` are untouched. |
| 2 | The /members/ excerpt in the database says "rota" | **Accepted.** `dev/seed/extra.json` changes, and the runbook gets the one-off `wp post update` (§7). The Asking-for-volunteers line gets the same treatment. |
| 3 | The list of strings to change | **Accepted** and extended: the Dashboard's no-tasks line, roles.php, the net block's `description` and `"Weeks (rota)"` label only (never the `enum`), setup.php and the plugin header are all in the group lists. |
| 4 | Exact wording (one casing; menu, h1, Dashboard, admin bar, edit link "Net Control Schedule"; Needs attention line; Help line; "That’s as far ahead as you can post."; the Net Settings hint) | **Accepted**, with three adjustments. The Dashboard button is "Update the schedule", as proposed. The Save button stays **Save**, not "Save schedule": every form in the spec (Net Settings, both meeting screens, Most Used, and the Save box of a published event or document) says Save, the notice names what was saved, and one screen with a noun on its button would be the odd one out. The Net Settings hint also says "Volunteer needed" (Frank 1), and the Help line covers both Winlink and notes in one line (Frank 6). |
| 5 | Re-shoot the guide's screenshots, keep the file names, fix the guide lines | **Accepted** and widened: every admin screenshot the guide embeds changes, so all six are regenerated (§7). The guide's line-by-line changes are in §5. |
| 6 | Two tests check the visible wording | **Accepted:** `e2e/sample.test.mjs:34` (group 2) and `php/qa-front-095-096-net-tails-test.php:112` → 'Edit this list in Net Control Schedule' (group 1), its Net Settings pair updated to 'Edit these settings in Net Settings'. qa-104's `id="rota"` check is untouched. |
| 7 | Don't rename "Hub tiles" to "Most used"; use "Members page shortcuts"; keep the aria-label "Words for slot %d" | **Rejected, both parts.** The clash it describes is gone: v0.2 already made the four buttons and the library filter one concept (the filter always lists the buttons' documents, the tick is "Also list under Most used", and the guide's lines 103–105 that explained the difference are deleted). Frank reads the screen as "Most Used" (his critique 2 and 5), and T17 failed on the two-names problem this solves. "Slot" is a retired word (R1) and aria-labels are text a person hears (R9), so they become "Words on button {n}" (and "Document for button {n}", "Icon for button {n}"). qa-028 can't pass silently: `tabTo()` asserts the field was found (`toBeTruthy`), and group 4 updates its selector to `input[aria-label="Words on button 1"]`. |
| 8 | "Kind" → "Type of event" everywhere at once; the held-back sentence; the editor hint | **Accepted:** "Type of event" in the form, list column, errors and guide, in one group. **Held-back sentence rejected as worded:** "The event on the site is unchanged" is false. `spokares_save_event()` skips only the problem fields' meta (`$held_keys`) and saves everything else, so the spec's "Saved, except Where: … Everything else is on the site now." stays. **Editor hint accepted:** "Change this on the {Screen} screen." |
| 9 | Define each screen name once | **Accepted** as R10: the menu, admin bar and edit links read `spokares_edit_screen()`, and every plugin screen's h1 is `get_admin_page_title()`. A `meeting-rules` key is added for Meeting Schedule. |
| 10 | One cheap guard | **Accepted, corrected.** As proposed it would always fail: `grep -nwi rota` matches block.json's `"default": "rota"` and `enum`, and the guide's `admin-net-rota.png`. §7 narrows it to block.json's title, description and label lines and strips `.png` names from the guide. The `checks.sh` meta-description check is added. |
| 11 | Update PLAN.md, and record the rule | **Accepted** (§5, last paragraph). |
| 12 | Net Settings under the Net Control Schedule parent | **Accepted** as is; no "Tuesday net" parent without the owner. |
| Avoid | Renaming the anchor or block value, a gettext filter or glossary option, a "(formerly rota)" note, a banned-word crawler, renaming test files or SCREENS keys, editing code comments or readmes | **Accepted.** None is in the spec. The verifier's grep of rendered pages is a one-time check, not a new crawler. |
| Verdict | "About 25 strings plus the guide" | The critique read the directive, not v0.2: the spec also carries the walkers' accepted findings (new states, one notice per save, the Save box, the JS split). Its code facts (identifiers, the stored excerpt, the screenshots, the tests, the guard) are all taken in above. |
