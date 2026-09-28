# Volunteer editor journeys (v0.1.1)

The catalogue for the editor workflow review. Built from PLAN.md §3–4, EDITING-GUIDE.md, the admin screens' code (`plugins/spokares-core/inc/admin-*.php`, `dashboard.php`, `admin-bar.php`, `blocks.php`) and the theme's page patterns. Each journey says what the build offers today. The **Watch** lines are hypotheses from reading the code; confirm or reject them in the walk.

- **Role walked:** ARES Editor with the Net details grant (`?dev_login=qa-ares-net`). A plain ARES Editor sees the same minus Net details and Meeting rules; journeys marked **(grant)** are the difference. For a plain editor the success criterion is "finds out in one step that the webmaster does this".
- **Persona:** Frank, Section Emergency Coordinator, 70s, used to Hostinger's no-code builder, not WordPress. Edits monthly, sometimes on an iPad. Thinks "Tom can't do the 13th", not "update the spk_rota option".
- **Today** is Sun, Sep 27, 2026. The rota screen's 13 rows run Tue, Sep 29 to Tue, Dec 22. Winlink nights are the 2nd and 4th Tuesdays, GMRS the 3rd, simplex the 5th.

## 1. The map Frank has to learn

### Where things show on the public site, and what feeds them

| Public place | What editors can change there | Comes from |
|---|---|---|
| **Home** (`/`) | Hero heading, text, buttons and photo; "What we do" list; "Join in four steps" | Page text (block editor) |
| Home › **Come visit** | "From home" net line; "In person" meetings with "Next: …" and changes | Net details; Regular meetings; meeting place (admin only) |
| **How it works** | All prose, tables, buttons | Page text |
| How it works › **The weekly net** / **Other nets** | Repeater settings box with Copy buttons; Winlink, simplex, GMRS lines | Net details |
| **About ARES & ACS** | All prose; leadership, contact, licensing and training tables; timeline; "Last reviewed" date | Page text; Page review box |
| **For members** (`/members/`, labelled "This week" in the members menu) | Settings bar; **Tuesday net** rota (next 5 Tuesdays); **This week** (next 2 exercises or trainings within 60 days, meeting changes within 14 days); **Most used** (4 tiles); document search | Net details; Net rota; Events; Regular meetings; Hub tiles |
| **Exercises & events** (`/members/exercises/`) | **Next up** (2 exercise cards); **Later this season**; **Winlink assignments**; **Public-service events**; **Past exercises** | Events; Net rota (Winlink columns); Net details (how-to line) |
| **Documents & forms** (`/members/documents/`) | 7 sections, 37 items; search; "Most used" filter; stable links `/docs/<name>/` | Documents |

Events never show on Home. The members pages have no editable words.

### How Frank gets to a screen

| Way in | What it offers (grant role) |
|---|---|
| **Dashboard › Site tasks** | 1 Net rota: status line, **Update the rota**, *Net details*. 2 Events: next event, **Add an event**, *All events*. 3 Meetings: next meeting, **Cancel or move a meeting**, *Meeting rules*. 4 Documents: count and stale count, **Add a document**, **Replace a document**, *Hub tiles*. 5 Page text: *Home · How it works · About ARES & ACS*. Needs attention (amber, when true). "Stuck? webmaster@spokares.org" |
| **Left menu** | Dashboard · **Net rota** (Net rota, Net details) · **Events** (All events, Add event, Regular meetings, Meeting rules) · **Documents** (All documents, Add document, Hub tiles) · **Pages** (All Pages) · **Profile** |
| **Black bar on the public site** | **Update lists** › Net rota, Events, Regular meetings, Documents, Hub tiles; **Edit page** on Home, How it works and About |
| **Under each public list** (signed in) | "Edit this list in Net rota / Events / Regular meetings / Documents / Hub tiles"; "Edit these settings in Net details" under the net facts |
| **Help** (top right of a plugin screen) | A "How to" tab of 3–5 lines and the webmaster address |
| **Printed guide** | EDITING-GUIDE.md, "the five jobs" |

## 2. Journeys

Format: **trigger** in Frank's words · **Start** (expected first click, then alternatives) · **Steps** · **Shows** on the public site · **Done when** · **Watch**.

### A. Getting in and around

**A1 Sign in.** "I need to get into the website."
- Start: bookmark `spokares.org/wp-admin`.
- Steps: username and password → 6-digit code from the phone app (or the e-mailed code) → lands on Dashboard.
- Done when: he reaches Site tasks without help. Ten failed tries from one connection lock sign-in for 15 minutes ("Too many failed sign-ins. Wait 15 minutes.").
- Watch: the code screen is the Two Factor plugin's wording, not ours. Dev sign-in skips 2FA, so check this one on staging.

**A2 Set up two-factor on a new phone.** "I got a new iPhone. The code app is empty."
- Start: Profile (left menu), or your name at top right.
- Steps: Two-Factor Options → set up the authenticator app (scan the QR code) → enter a code → Update Profile. Backup codes stay on the printed sheet.
- Done when: the next sign-in takes the new phone's code.
- Watch: Two-Factor Options uses the plugin's own labels, not ours. The Help tab says "Two-Factor Options: turn on a second step when you sign in"; nothing tells him to remove the old phone.

**A3 Sign in without the phone.** "Left my phone at home."
- Steps: on the code screen choose the e-mailed code, or type a printed backup code, then tell the webmaster.
- Done when: he finds the other method on the code screen.

**A4 Sign out.** "I'm on the library computer."
- Steps: your name, top right → Log Out.
- Done when: he's on the sign-in screen. On an iPad the menu opens by tap.

**A5 Change my name, e-mail or password.**
- Start: Profile.
- Steps: change the field → Update Profile. The Help tab explains "Display name publicly as".
- Watch: "Display name publicly as" hints that the name shows publicly, but it only shows to other editors ("Last saved … by").

**A6 Find help.** "I'm stuck on this screen."
- Start: Help (top right) → the "How to" tab; the printed guide; "Stuck? webmaster@spokares.org" at the bottom of the Dashboard.
- Done when: he finds the tab, or knows where to e-mail, within a minute.
- Watch: the Help button is small and top-right, easy to miss. The block editor has no "How to" tab.

**A7 Is my change live?** "Did that go through? Can members see it?"
- Start: the notice after saving: "Saved. See it on For members" (rota, tiles), "Saved. See it on Home" (meetings), "Published. See it on Exercises & events" plus "On the site now: Later this season on Exercises & events…" (events), "See it on Documents & forms" (documents), "See it on How it works" (Net details), "Page updated." (pages). Also each screen's **View on site**, and the Save box's "Status: Published: on the site / Draft: not on the site".
- Done when: he can see the change in the public place within one click, and knows drafts never show.
- Watch: meetings link to Home, but the change also shows on For members (only within 14 days of the date). Page saves say "Page updated." with the block editor's own "View Page" link.

**A8 Go from the public site to the right screen.** "I'm looking at the members' page and the Tuesday list is wrong."
- Start: "Edit this list in Net rota" under the list, or Update lists › Net rota in the black bar.
- Done when: one click lands on the screen that holds that fact.
- Watch: the link goes to the list screen, not to the one item (an event link opens All events, not that event).

### B. Tuesday net: net control schedule (Net rota)

**B1 Post the next months' net control.** "Here's who's running net control through December."
- Start: Dashboard › **Update the rota**; or Net rota in the menu; or "Edit this list in Net rota" on For members.
- Steps: per Tuesday, type the call sign (typing picks "Call sign"), or choose Open or Not posted yet; on Winlink nights type the assignment and pick the Form → **Save rota**.
- Shows: For members › Tuesday net (next 5 Tuesdays); Winlink assignments on Exercises & events.
- Done when: in 3 minutes; the Shows column matches; the notice links to For members; names typed with a call sign are cut to the call sign and he's told.
- Watch: the admin state "Not posted yet" appears as "Not yet published" in Shows and on the site: two names for one state. "Rota" is a British word; US hams say "net control schedule". A monthly job near a quarter's end crosses the 13-row window (see B7).

**B2 Swap one net controller.** "Randy's taking the 13th instead of Tom."
- Start: as B1.
- Steps: find Tue, Oct 13 → replace the call sign → Save rota. Only changed rows are saved.
- Done when: that one row changes; if he types "Randy NZ2S", only NZ2S is stored and the notice says so; if he types only "Randy", the row isn't saved and stays outlined red with his text kept.

**B3 Mark a net open.** "Tom can't do the 20th and nobody's stepped up."
- Steps: Tue, Oct 20 → choose **Open** (the call-sign box greys out) → Save rota.
- Shows: "Open" on For members, linked to the open-slot line.
- Done when: he chooses Open, not a blank box or "Not posted yet".
- Watch: "Open" is ambiguous (open net? open to anyone?). The only hint is the Help tab's "Open (ask for a volunteer)".

**B4 Set or change a Winlink assignment.** "For the 22nd, everyone sends a Radiogram with their New Year's resolution."
- Steps: Tue, Dec 22 (Winlink night) → Winlink assignment text → Form: Other… → type "ARRL Radiogram" → Save rota.
- Shows: Exercises & events › Winlink assignments (next 8).
- Done when: the row shows the task and form; the Winlink boxes appear only on Winlink rows.
- Watch: the Winlink assignments show on Exercises & events but are edited on Net rota; nothing on the Events screens points there.

**B5 No net, or a special note, on one Tuesday.** "No net on Nov 3. Everyone's at the county exercise."
- Steps: Tue, Nov 3 → Note "No net: county exercise" → Save rota.
- Shows: the Note column on For members.
- Done when: members can tell there's no net that night.
- Watch: the net-control choices have no "No net" state. With a note, the row still says Open, a call sign or "Not yet published".

**B6 Take back a save.** "I just put everyone on the wrong week."
- Steps: **Undo last save (name, when)** at the bottom → the rows from the last save come back. **Put back what was undone** reverses it.
- Done when: he finds Undo without help and understands that it covers the last save by anyone.

**B7 Post further ahead than 13 Tuesdays.** "December 29 isn't on here."
- Steps: "Show 13 more" under the table → fill the row → Save rota. The cap is 52 weeks.
- Done when: he finds "Show 13 more" below the table.
- Watch: the link sits below 13 rows, near the Save button.

### C. Net facts (Net details, grant)

**C1 Change the repeater's tone, offset or frequency.** "The repeater tone changed to 103.5."
- Start: Dashboard › Net rota › *Net details*; or Net rota › Net details in the menu; or "Edit these settings in Net details" under the settings bar.
- Steps: The repeater › Tone → 103.5 Hz → check "Preview: what the site will say" → **Save net details** → confirm.
- Shows: the For members settings bar and Copy buttons; How it works › The weekly net; Home › From home.
- Done when: every place updates from one save, and the preview shows it before saving.
- Watch: he may look under Pages › How it works, where the numbers show but can't be edited. The club call W7GBU is greyed out for him, with an explanation.

**C2 Change the net time.** "The Tuesday net starts at 7:30 now."
- Steps: Net time (every Tuesday) → Save net details → confirm.
- Shows: everywhere the net time prints, including the Net rota screen's top line.

**C3 Change which Tuesdays are Winlink, simplex or GMRS, or the GMRS time.** "GMRS is moving to 7:00."
- Steps: Which Tuesdays → tick the weeks; "ACS GMRS net … at" time → Save.
- Shows: How it works › Other nets; the rota's week labels.
- Watch: "A week ticked twice counts as simplex first, then Winlink, then GMRS" is a rule he has to remember.

**C4 Show or hide the alternate repeater.**
- Steps: The alternate repeater → Frequency, Offset, Tone, "Show on How it works" → Save.

**C5 Change the Winlink how-to line or the open-slot line.**
- Steps: Sentences → edit → Save. The preview shows each sentence.
- Shows: above the Winlink assignments; under the rota.

**C6 (plain editor) Find who changes net facts.**
- Done when: the rota screen's line "Repeater details, the net time and the Winlink, simplex and GMRS weeks are on Net details: ask the webmaster" answers it. A Net details URL gives "Ask the webmaster".

### D. Exercises and events (Events)

**D1 Add an exercise.** "The SET is Saturday, Oct 3. Here's what members do, the groups.io post and the ARRL forms."
- Start: Dashboard › **Add an event**; or Events › Add event.
- Steps: **Kind**: Exercise (box above the name; no default) → name → **When**: On a date, First day, All day ticked (untick for Start and End times) → Short line for lists → Where → What members do (one task per line) → Main link (web address and button words) → More links (up to 3) → Extra form (a document) → After it ends: Keep in Past exercises (ticked) → **Publish**.
- Shows: Later this season, then a Next up card once it's one of the next two exercises; This week on For members within 60 days.
- Done when: in 3 minutes; the notice says where it shows now; the card shows the tasks and links.
- Watch: the kind's "Where it shows" note is a 3-line paragraph. "Never publish…" appears in the Help tab, at the foot of the form and in the guide. The "Webmaster check" side box tells editors "Not marked for the webmaster.", which means nothing to Frank. "Extra form" lists every published document by title.

**D2 Add a training.** "Winlink class, Sat Nov 7, 9 to noon, at the county EOC. Bring a laptop."
- Steps: Kind: Training → name → date, untick All day, 9:00 AM–12:00 PM → Where "County EOC" → Short line "Bring a laptop." → Publish.
- Shows: Later this season; This week on For members when due.
- Done when: the times show as "9:00 AM–noon".

**D3 Add an on-the-air event.** "Kids Day, Sat Jan 9, from your own station."
- Steps: Kind: On the air → name → date → Where "From your own station" → Main link → Publish.
- Shows: Later this season only.
- Watch: the kind is "On the air", but on the site these sit under "Later this season" with no label saying so.

**D4 Add a public-service event.** "Bloomsday needs radio operators; sign up through NV2Z."
- Steps: Kind: Public service → name → When: On a date, **Date not posted yet** or **As requested** (public service only) → Volunteer through (call sign) → Publish. Main link, More links and tasks are hidden for this kind.
- Shows: Exercises & events › Public-service events.

**D5 Add something that isn't one of the four kinds.** "Holiday potluck on Dec 12 instead of the workshop." / "Annual meeting at the EOC, Nov 21."
- Expected today: none of Exercise, Training, On the air or Public service fits. The regular-meeting screen only cancels or moves rule dates.
- Done when: he knows where it goes, or that it's page text, groups.io or a webmaster job, within 1 minute.
- Watch: **gap.** He'll likely file it under Training, or give up.

**D6 Change an event's date, time or place.** "The State COMMEX moved a week, to Oct 31."
- Start: Dashboard › Events › *All events* (a text link beside the Add button); or Events › All events; or "Edit this list in Events" on Exercises & events.
- Steps: Upcoming view (default, soonest first) → click the name → change First day → **Update**.
- Done when: in 1–2 minutes; the notice says where it shows.
- Watch: Documents has a **Replace a document** button, but Events has no "Change an event" button, only the small All events link. How it works' page text also names the October exercises ("the Simulated Emergency Test, the Great ShakeOut and the State COMMEX"), so a change can leave the page text out of step.

**D7 Postpone with no new date.** "SET's postponed; new date TBD."
- Steps: open it → When: **Date not posted yet** → Update.
- Shows: "Date not posted" at the end of Later this season.
- Watch: members don't see "postponed"; the event just moves to the bottom.

**D8 Call off an event.** "We're not doing ShakeOut this year."
- Steps: open it → **Move to Trash**, or **Save as draft (takes it off the site)**. The list also has a Trash row action.
- Shows: it disappears from every list.
- Done when: he picks one action without hesitating, and understands that members won't see a "cancelled" notice.
- Watch: two ways to do one thing, and neither tells members. Meetings show "(Oct 10 cancelled)"; events show nothing.

**D9 Remove a mistaken event; restore it.** "I added SET twice." / "I trashed the wrong one."
- Steps: All events → hover → Trash. To restore: Trash view → Restore. It comes back as a **Draft** (the notice says so) → Drafts → open → Publish.
- Done when: he restores it and sees it live again.

**D10 Repeat last year's event.** "Same SET as last year, new date."
- Steps: All events → Past view → hover → **Duplicate** → the draft copy has no dates → set the date, check the words → Publish.
- Done when: he finds Duplicate (it only shows on hover; on an iPad it needs a tap on the row).

**D11 Set the date of an undated event.** "The Lilac Parade is Saturday, May 15. Sign up through NV2Z."
- Steps: All events → Upcoming (undated events are listed there) → open "Lilac Festival Armed Forces Torchlight Parade" → When: On a date → May 15, 2027 → Update.
- Shows: Public-service events, now dated.

**D12 Keep or drop an exercise under Past exercises.** "Don't list last spring's drill under past exercises."
- Steps: All events → Past → open → untick **Keep in Past exercises** → Update.
- Shows: Past exercises (8 newest).
- Watch: the only way to say "remove from Past exercises" is a tick box labelled "After it ends:".

**D13 Recover from a held-back save.** "It says 'Saved as a draft, so it's off the site'."
- Steps: read the red-outlined field → fix it, or tick "This is a public agency number. Publish it." → Publish.
- Done when: he fixes it and publishes without losing his typing.

### E. Regular meetings (Events › Regular meetings; Meeting rules is grant only)

**E1 Cancel one meeting date.** "The Oct 10 workshop is off; the room isn't available."
- Start: Dashboard › **Cancel or move a meeting**; or Events › Regular meetings; or "Edit this list in Regular meetings" on Home or For members.
- Steps: Second Saturday Workshop, Sat, Oct 10 → tick **Cancelled** → Note "Room unavailable" → **Save**.
- Shows: Home "Next: Sat, Nov 14 (Oct 10 cancelled)"; For members › This week from 14 days before.
- Done when: in 1 minute; the notice links to Home.
- Watch: meetings live under the **Events** menu while the Dashboard calls them "Meetings". The Winlink workshop isn't listed; a line under the table explains why, in webmaster terms ("Meeting rules › 'On Home and the members hub'").

**E2 Move one meeting date.** "October's Third Thursday session moves to Wednesday the 14th."
- Steps: Third Thursday training meeting, Thu, Oct 15 → **Moved to** 2026-10-14 → Save.
- Shows: Home "Next: Wed, Oct 14 (moved from Oct 15)".
- Watch: "Moved to" takes a date only. A new time or room for one date can only go in the Note.

**E3 Take back a cancellation.** "Never mind, the room's free after all."
- Steps: untick Cancelled (clear the note) → Save.
- Watch: a note with neither Cancelled nor Moved to is refused ("tick Cancelled or fill in Moved to, then add the note"), so the note has to be cleared too.

**E4 Change the time or room for one date.** "Nov 14 workshop starts at 10 this time."
- Expected today: Note only ("Starts 10:00 AM"), with neither box ticked, and that is refused (see E3).
- Watch: **gap.** He can't give a one-off time change without cancelling or moving the date.

**E5 Change a meeting's regular day, week or time (grant).** "Starting in January, Third Thursday moves to the fourth Thursday."
- Start: Dashboard › Meetings › *Meeting rules*; or Events › Meeting rules; or "Cancel or move one date" ↔ "Meeting rules" cross-links.
- Steps: that meeting's box → Week of the month: untick 3rd, tick 4th → **Save meeting rules** → confirm.
- Shows: Home and For members, straight away.
- Watch: **gap.** A rule has no "starting from" date. Saving in September moves October and November too, so "starting in January" needs a calendar reminder.

**E6 Skip months, stop showing, or add a regular meeting (grant).** "No workshop in July and August." / "Stop listing the Winlink workshop." / "New monthly VE session."
- Steps: Skip these months; untick Active or "On Home and the members hub"; or fill in the blank "Add a meeting" box → Save meeting rules.
- Watch: there's no delete, only "Active". "Time words" and "Extra words on Home" need an example on screen.

**E7 Change where meetings are held.** "The workshop's moving to the new EOC building."
- Expected today: administrators only (Settings › ARES site). No editor screen mentions the place or who changes it.
- Done when: he finds out in one step that it's the webmaster's job.
- Watch: **gap** in signposting.

### F. Documents and forms (Documents)

**F1 Upload a new file into the right section.** "Put this Go-kit checklist PDF with the readiness stuff."
- Start: Dashboard › **Add a document**; or Documents › Add document.
- Steps: Document name → **Library section** (7 choices; required) → Where the file is: **Upload a file** → tick the **Privacy check** (this unlocks the file chooser) → Choose File (PDF, DOCX, XLSX, JPEG, PNG) → Version or date → Short note (8 words or fewer) → **Publish** (the upload happens now).
- Shows: Documents & forms in that section, at the end; `/docs/<name>/`.
- Done when: in 3 minutes; the file opens from the site.
- Watch: the disabled Choose File until the tick. "Library section" versus the site's "Documents & forms" headings. On an iPad, a PDF from Mail has to be in Files first.

**F2 Add a link to an official source.** "Point members to FEMA's IS-100 course with the training material."
- Steps: name → section Training → **Link to another site** → web address → Privacy check → Publish. "Source shown on the page" is filled in from the address (under More options).
- Shows: Training section, with the source domain.
- Watch: the Privacy check ("no personal phones, home addresses…") is also required for a link to fema.gov.

**F3 Turn a "Soon" placeholder into a real file.** "The ICS 309 log is ready at last. Here's the PDF."
- Steps: All documents (sorted by title) → ICS 309 Communications Log → Where the file is: Upload a file → Privacy check → Choose File → Update.
- Done when: it stops saying "Soon" on the site.
- Watch: "Not available yet ('Soon')" is a third choice among "where the file is"; he may not recognise it as a placeholder.

**F4 Replace a file with a new version.** "Here's the new net script."
- Start: Dashboard › **Replace a document** (opens All documents) → click the name.
- Steps: **Replace with** → Choose File (leave "Remove the old file from the web (recommended)" ticked) → change Version or date → Update.
- Shows: the same row and the same `/docs/` link; the old file is off the web.
- Watch: every seeded document is a link or "Soon"; none has an uploaded file, so no document shows the Replace with box yet. "Net scripts" is a link to ag7qp.com. His first "new version" means switching "Where the file is" to Upload a file. Does the screen make that obvious?

**F5 Change a link's web address.** "The FEMA forms page moved."
- Steps: open it → web address → Update.

**F6 Change a document's name, note, version or section.**
- Steps: open it → change → Update. The link name (slug) is admin-only and fixed once published, so bookmarks survive.

**F7 Retire a document; restore it.** "The Powerpole guide is out of date. Take it down."
- Steps: open it → **Move to Trash** ("Also remove its file from the web" ticked by default). Or **Save as draft (takes it off the lists)**, where an uploaded file stays at its web address and the box says so.
- Restore: Trash view → Restore → it comes back as a draft; if its file was removed: "Upload the file again, then Publish."
- Watch: the list has no Trash row action for documents (events have one), so removing means opening it first. Draft versus Trash differ in whether the file stays on the web; that's a lot to weigh for "take it down".

**F8 Reorder documents in a section.** "Put the Go-kit guide first under readiness."
- Expected today: open each document → More options → **Position in section (lower shows first)** → a number → Update, per document.
- Done when: the order on Documents & forms matches what he wanted.
- Watch: **likely the hardest journey.** The list sorts by title, has no Position column and no drag, so he has to guess the neighbours' numbers.

**F9 Mark documents reviewed.** "I checked all the forms; they're current."
- Steps: All documents → section filter → tick rows → Bulk actions: **Mark reviewed today** → Apply. Or on the form: More options → Mark reviewed today.
- Shows: nothing public; the Dashboard's "not reviewed in 12 months" count and the red Reviewed column.

**F10 Include a document in the library's "Most used" filter.**
- Steps: More options → **Show under "Most used"** → Update.
- Watch: "Most used" names two different things: this filter and the four hub tiles (F11). The guide asks him to keep them in step by hand.

**F11 Change the four quick tiles on the members page.** "Swap the ICS 214 button for the ICS 309."
- Start: Dashboard › Documents › *Hub tiles*; or Documents › Hub tiles; or "Edit this list in Hub tiles" under Most used.
- Steps: the ICS 214 slot → Document: ICS 309 → Words on the tile → Icon (Script, Form, Log, Book) → **Save tiles**. Picking a document already in another slot swaps the two.
- Shows: For members › Most used.
- Watch: the admin says "Hub tiles", the site says "Most used". The list offers only published, privacy-checked documents with a file or link, so a "Soon" ICS 309 isn't offered until F3 is done.

**F12 Attach a form to an exercise.** See D1 (Extra form). Watch: it's edited on the event, not the document.

### G. Page text (Home, How it works, About)

**G1 Change a sentence.** "Add the Lilac Parade to the community events we help with on the front page."
- Start: Dashboard › Page text › **Home**; or Edit page in the black bar while viewing Home; or Pages › All Pages › Home.
- Steps: click into "Bloomsday, fun runs and bike races." → type → **Save** (top right) → "Page updated."
- Done when: in 5 minutes, without touching layout.
- Watch: Pages › All Pages lists six pages, but three of them (the members pages) don't open, with no explanation why. The block editor's toolbar, the ⋮ menus and the right-hand panel are all WordPress.

**G2 Change a button's words or link.** "The 'How to apply' button should go to the new form."
- Steps: click the button → change the words; link icon in the small toolbar → paste the address → Save.

**G3 Add or remove a list item.** "Add 2026 to the timeline on About."
- Steps: cursor at the end of an item → Enter → type, **bold the first words** (they become the item's title) → Save.
- Watch: the bold-first-words rule exists only in the guide.

**G4 Change a table cell.** "K7MHG is stepping down as Hospital Coordinator; AA7RT takes over."
- Start: Dashboard › Page text › About → the leadership table → the call-sign cell → type → Save.
- Shows: About › Organization and leadership.

**G5 Add a table row, a section or a new page.** "Add a row for our new AEC." / "We need a Privacy page."
- Expected today: the webmaster's job. Saving a structural change gives "Layout changes need an administrator…".
- Done when: he learns it before spending time on it.
- Watch: a table offers no row controls, and nothing on the page says rows are admin-only.

**G6 Change the Home photo.** "Use this SET photo on the front page."
- Steps: Home → click the photo → **Replace** → Upload (media window) → choose the file → alternative text on the right → Save.
- Done when: the new photo shows, with alt text.
- Watch: the iPad camera roll gives HEIC files, and only JPEG, PNG and WebP are allowed (Safari usually converts; confirm). Focal point is offered; the overlay isn't.

**G7 Paste from Word or e-mail.**
- Steps: paste one paragraph at a time; several at once arrive as one paragraph with line breaks, and a message says so.

**G8 Undo.** "I messed up the About page."
- Before saving: Ctrl+Z (Cmd+Z) or the Undo arrow. After saving: the webmaster restores a revision.
- Watch: the editor keeps no history he can restore himself.

**G9 Mark About as reviewed.** "I read the whole About page; it's current."
- Steps: About → Page review box (right) → tick "Mark reviewed today when I save" → Save. The date shows under the title.
- Watch: the Page review box also appears on Home and How it works, where no date shows publicly.

**G10 Change the search-engine description or the page's name.**
- Steps: the Page panel → Excerpt; or the ⋮ menu by the page's name → Rename.
- Watch: "Excerpt" and "Rename" are WordPress words; is this a Frank journey at all?

**G11 Respond to "This page may contain something we never publish".**
- Steps: read the yellow message → change the text → Save again. The Dashboard lists the page under Needs attention until it's clean.

### H. Not a site job (success = he finds out fast)

| Id | Frank says | Answer today | Where he learns it |
|---|---|---|---|
| H1 | "Post a news item: thanks to everyone who worked the fire." | No news on the site (groups.io) | Nowhere on screen |
| H2 | "We need a new page for the hospital program." | Webmaster | Guide; "Layout changes need an administrator" |
| H3 | "Change the footer / menu / groups.io link at the top." | Webmaster (theme parts) | Guide only |
| H4 | "Change the meeting place." | Webmaster (E7) | Nowhere on screen |
| H5 | "Publish the member roster / the hospital net channel." | Never publish | Guide "Please don't"; forms refuse the common ones |
| H6 | (plain editor) "The repeater tone changed." | Webmaster, or the named Net details editor | Rota screen line; Dashboard line |

## 3. Cross-cutting questions for the walk

These are hypotheses from the code, not verified findings.

1. **One thing, several names.** "Not posted yet" (rota choice) and "Not yet published" (Shows, site). "For members", "members hub", "hub" and the members menu's "This week". "Hub tiles" (admin) and "Most used" (site), while "Most used" is also a library filter. "Meetings" (Dashboard) and "Regular meetings" (menu, under Events).
2. **Words Frank wouldn't use.** Rota, Kind, Library section, Hub, Draft, Publish or Update, Move to Trash, Excerpt, Slug, Soon, Extra form, "Short line for lists".
3. **Create versus edit.** Documents has Add and Replace buttons, though at launch every document is a link or "Soon", so no Replace with box exists yet (F4). Events has Add plus a text link. Rota and meetings use one button for both. Should every task have "add" and "change" entry points of equal weight?
4. **Say it once.** Kind-places paragraph, Help tab, "Never publish" footer line, Privacy check wording and the guide repeat each other. The "Webmaster check" box shows editors a sentence about nothing.
5. **Silent removals.** Called-off or postponed events vanish without a notice to members, while meetings say "cancelled".
6. **Gaps.** One-off gatherings (D5), a one-date time change (E4), a rule change with a start date (E5), the meeting place (E7) and reordering documents (F8).
7. **Menu logic.** Winlink assignments (shown on Exercises & events) are edited under Net rota. Meetings (shown on Home) sit under Events. Is "where it shows" or "what it is" the better guide for Frank?
8. **iPad.** Rota table layout, hover-only row actions (Duplicate, Trash), file pickers and HEIC photos, the block editor toolbar.

## 4. Twenty first-click tasks

Plain task statements with no WordPress or menu words. Use them in order, with the site state as seeded. Task 17 needs task 14 done first. The **Expected first click** column is for the tester only; don't show it to the participant.

| # | Task (read aloud to the participant) | Expected first click | Journey |
|---|---|---|---|
| 1 | Randy is taking net control on October 13 instead of Tom. Randy's call is NZ2S. | Dashboard › Update the rota | B2 |
| 2 | Tom can't do net control on October 20, and nobody has offered to fill in yet. | Dashboard › Update the rota | B3 |
| 3 | There's no Tuesday net on November 3. Everyone will be at the county exercise. | Dashboard › Update the rota | B5 |
| 4 | Here's who runs the Tuesday net for the rest of the year: Nov 10 AE7RJ, Nov 17 WA7LNC, Nov 24 NZ2S, Dec 1 AG7QP, Dec 8 K7MHG, Dec 15 WA7LNC, Dec 22 NV2Z, Dec 29 NZ2S. | Dashboard › Update the rota (then Show 13 more) | B1, B7 |
| 5 | The State COMMEX got pushed back a week, to Saturday, October 31. | Dashboard › All events (or Events › All events) | D6 |
| 6 | We're not doing the Great ShakeOut this year. | Dashboard › All events | D8 |
| 7 | We're running a Winlink class on Saturday, November 7, from 9:00 to noon at the county EOC. Bring a laptop. | Dashboard › Add an event | D2 |
| 8 | The Lilac Parade is set for Saturday, May 15. Radio volunteers sign up through NV2Z. | Dashboard › All events | D11 |
| 9 | The October 10 Saturday workshop is off. The room isn't available. | Dashboard › Cancel or move a meeting | E1 |
| 10 | Instead of the December 12 workshop, we're having a holiday potluck at the same time and place. | Dashboard › Cancel or move a meeting (then no home for the potluck) | E1, D5 |
| 11 | Starting in January, the Third Thursday session moves to the fourth Thursday of the month. | Dashboard › Meeting rules (plain editor: learns it's the webmaster's) | E5 |
| 12 | The repeater's tone is now 103.5. | Dashboard › Net details (plain editor: learns it's the webmaster's) | C1 |
| 13 | Here's the updated net script as a PDF. Use it in place of the old one. | Dashboard › Replace a document | F4 |
| 14 | The ICS 309 communications log is ready at last. Here's the PDF; put it with the other forms. | Dashboard › Replace a document, or Documents › All documents (it exists as "Soon") | F3 |
| 15 | Members should be able to find FEMA's IS-100 course with the other training material: https://training.fema.gov/is/courseoverview.aspx?code=IS-100.c | Dashboard › Add a document | F2 |
| 16 | The Anderson Powerpole guide is out of date. Take it down. | Dashboard › Replace a document (or Documents › All documents) | F7 |
| 17 | Members reach for the ICS 309 more than the ICS 214 now. Put it in the ICS 214's place under "Most used" for members. | Dashboard › Hub tiles | F11 |
| 18 | K7MHG is stepping down as Hospital Coordinator. AA7RT takes over. | Dashboard › Page text › About | G4 |
| 19 | The front of the website says we help with Bloomsday, fun runs and bike races. Add the Lilac Parade. | Dashboard › Page text › Home | G1 |
| 20 | Use this photo from the SET on the front of the website instead of the current one. | Dashboard › Page text › Home | G6 |

A first click counts as a pass when it's the expected Dashboard control, the matching left-menu item or the matching black-bar or under-list link.
