# Editor workflow map

Current-state UML activity diagrams (one swimlane per actor) for every custom editor and administrator job on the site, with an assessment and recommendations. It was read from the v0.2.0 code on 2026-10-13.

- **Report:** [`index.html`](index.html). Open it in a browser. It's self-contained apart from Google Fonts. It holds the summary, the 18 diagrams, findings with code references, recommendations with "what it changes in the app", a roadmap, and an index of all findings.
- **Diagrams:** [`svg/`](svg/). Standalone SVGs that render on GitHub in light and dark.

| Area | Workflows |
|---|---|
| Tuesday net and meetings | [rota](svg/rota.svg) · [undo](svg/undo.svg) · [netsettings](svg/netsettings.svg) · [meetchange](svg/meetchange.svg) · [meetrules](svg/meetrules.svg) |
| Exercises and events | [eventedit](svg/eventedit.svg) · [eventlife](svg/eventlife.svg) |
| Documents | [docadd](svg/docadd.svg) · [docreplace](svg/docreplace.svg) · [docreview](svg/docreview.svg) · [doctake](svg/doctake.svg) · [tiles](svg/tiles.svg) |
| Page text | [pagetext](svg/pagetext.svg) |
| Accounts and access | [accounts](svg/accounts.svg) |
| Operations | [ssh](svg/ssh.svg) · [release](svg/release.svg) · [weekly](svg/weekly.svg) · [backups](svg/backups.svg) |

## Editing and rebuilding

```sh
python3 wordpress/ux/workflows/build.py      # writes svg/*.svg and index.html (stdlib only)
```

| File | Holds |
|---|---|
| `specs.py` | The diagrams: lanes, nodes, edges, and finding badges (`pain="N3"`) |
| `content.py` | Findings, verdicts, recommendations, themes |
| `wfdiagram.py` | The layout engine. Rows come from the edges. A lane can be several columns wide (`col=1`) so a branch sits beside the main path. `back=True` draws a dashed loop. `rank=False` keeps a branch end level with its decision |
| `build.py` | Renders the report and checks that every badge names a known finding |

When the code changes, update the spec and the finding together, and change the "read from the code on" date in `build.py`.
