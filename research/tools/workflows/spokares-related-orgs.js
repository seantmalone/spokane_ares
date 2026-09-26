export const meta = {
  name: 'spokares-related-orgs',
  description: 'Map organizations related to Spokane County ARES/RACES (light map by default; args.deep = deep-profile selected orgs)',
  phases: [
    { title: 'Discover', detail: '5 lenses enumerate related orgs' },
    { title: 'Merge', detail: 'dedup + canonical org list' },
    { title: 'Map', detail: 'one relationships.md from discovery evidence' },
    { title: 'Profile', detail: 'deep mode only: one profile per selected org' },
    { title: 'Verify', detail: 'deep mode only: adversarial fact-check' },
  ],
}

const ROOT = '/Users/sean/Projects/spokane_ares/research'
const TODAY = args.today

const SEED = `
Subject: Spokane County ARES/RACES — website https://www.spokares.org/ (Joomla site, meta description: "Spokane County Amateur Radio Emergency Service, and Radio Amateur Civil Emergency Service").
We are researching it because the site will be completely rebuilt; we need deep context on the organization and every organization it relates to.
Known clues from the homepage nav/links (as of ${TODAY}):
- Pages: About Spokane County ARES/RACES, Amateur Code, ARES/RACES Leadership, Meeting Location, "MOU with IEVHFRA", "W7GBU History", Join ARES/RACES, Welcome Letter, Digital Messaging (cables/drivers, TNCs/modems, packet radio, software), RFI resources (Mel Ming N7GCO), Downloads (digital traffic info, forms, net preambles & scripts, presentations), Membership (amateur training, ham radio links, member login), Operations (forms, Incident Command System, SKYWARN weather spotting, status codes, training courses), weekly net controllers calendar (JCal Pro).
- External links: arrl.org, arrl.org/ares, arrl.org RFI, felge.us digital messaging & tfctools, hamqsl.com solar, spokaneares-acs.groups.io (note "ACS" = likely Auxiliary Communications Service), dhs.gov SAFECOM field operations guides, Spokane County Emergency Management "Volunteer Emergency Worker Application" (spokanecounty.org FormCenter), wastateares.org Washington State Emergency Net.
Tools: load WebSearch/WebFetch with ToolSearch ("select:WebSearch,WebFetch") and/or use curl via Bash. Cite a URL for every factual claim. Distinguish clearly between CONFIRMED facts (seen on a source page, give URL) and INFERENCES. Note the date/freshness of sources — amateur radio leadership changes often.`

const DISCOVER_SCHEMA = {
  type: 'object',
  properties: {
    orgs: {
      type: 'array',
      items: {
        type: 'object',
        properties: {
          name: { type: 'string' },
          aliases: { type: 'array', items: { type: 'string' } },
          url: { type: 'string' },
          category: { type: 'string', description: 'e.g. arrl-hierarchy, government/served-agency, legal-regulatory, local-ham-club, repeater-org, net, neighboring-ares, partner-volunteer-org, training-body, platform/tool, person' },
          relationship: { type: 'string', description: 'how it relates to Spokane County ARES/RACES, specifically' },
          evidence_urls: { type: 'array', items: { type: 'string' } },
          confidence: { type: 'string', enum: ['confirmed', 'likely', 'speculative'] },
        },
        required: ['name', 'category', 'relationship', 'evidence_urls', 'confidence'],
      },
    },
    notes: { type: 'string' },
  },
  required: ['orgs'],
}

const LENSES = [
  { key: 'arrl-hierarchy', prompt: `LENS: ARRL/ARES organizational hierarchy. Map the full chain from ARRL national -> ARRL Northwestern Division -> ARRL Eastern Washington Section (Section Manager, Section Emergency Coordinator, District Emergency Coordinators) -> Washington State ARES structure (wastateares.org, districts; which district is Spokane in?) -> Spokane County Emergency Coordinator. Include ARES programs relevant to a county group (ARES Task Book, ARES Connect, ARES Standards, ARES levels of training, Public Service Honor Roll, NTS / National Traffic System and its Washington/Northwest nets, Washington State Emergency Net). Identify current office holders where findable (with dates).` },
  { key: 'government', prompt: `LENS: government, legal and served-agency relationships. RACES is a government program: find how RACES works in Washington State (Washington Emergency Management Division, RCW 38.52 emergency worker program, WAC 118-04 emergency worker registration, FCC 47 CFR 97.407 RACES rules). Find Spokane County Department of Emergency Management / Spokane County Emergency Management (and its relationship to the City of Spokane — historically "Greater Spokane Emergency Management" / "Spokane County DEM"), the Volunteer Emergency Worker registration, EOC, Auxiliary Communications Service (ACS/AUXCOMM), CISA/DHS SAFECOM & AUXCOMM training, FEMA ICS/NIMS courses. Also any hospital/public-health comms (Spokane Regional Health District, Inland Northwest Health Services / hospital ham nets), American Red Cross (Inland Northwest chapter), Salvation Army, NWS Spokane (SKYWARN), CERT/Citizen Corps.` },
  { key: 'local-ham', prompt: `LENS: local amateur radio ecosystem in Spokane / Inland Northwest. Find: IEVHFRA (Inland Empire VHF Radio Amateurs — what the MOU with Spokane ARES covers, repeaters they operate), the W7GBU callsign (whose is it, history), Spokane Radio Amateurs / other Spokane clubs (e.g. Spokane Valley, Hamfest orgs, KBARA, Kootenai Amateur Radio Society, Spokane DX Association, Inland Northwest amateur groups), repeater systems used by Spokane ARES and their nets (weekly net frequencies, simplex nets), packet/Winlink infrastructure (RMS gateways, BBS nodes in Spokane), and neighbouring county ARES groups (Stevens, Pend Oreille, Lincoln, Whitman, Adams, Kootenai County ID ARES, Idaho Section). Include people prominently associated (e.g. Mel Ming N7GCO) only as context.` },
  { key: 'self-web-presence', prompt: `LENS: Spokane County ARES/RACES's own footprint OUTSIDE spokares.org. Find: the groups.io group (spokaneares-acs.groups.io) and what's publicly visible, any Facebook/X/YouTube/QRZ pages, ARRL section newsletters mentioning Spokane ARES, news articles (Spokesman-Review, KREM, KXLY etc.) about Spokane ARES/RACES deployments (e.g. wildfires, ice storm, windstorm 2015, Firestorm 1991, Bloomsday/Lilac Bloomsday Run support, Spokane Marathon, hospital drills, Cascadia Rising, Simulated Emergency Test), ARRL Field Day participation, historical names of the group, the callsign(s) it uses, its current Emergency Coordinator. Also check Wayback Machine (web.archive.org) for history of spokares.org and any prior domains.` },
  { key: 'tools-training', prompt: `LENS: resources, tools and training bodies referenced by or relevant to Spokane County ARES/RACES: felge.us (whose site is it, what is the digital messaging / tfctools material — likely Winlink/Fldigi/NBEMS/Flmsg), Winlink/Amateur Radio Safety Foundation, NBEMS/fldigi, packet radio in WA state (WA state ARES digital nets, WL2K), ICS-213/ICS-214/ICS-309 and Washington-specific forms, ARRL EC-001/EC-016 courses, FEMA IS-100/200/700/800, AUXC training (CISA), SKYWARN spotter training by NWS Spokane, SAFECOM Field Operations Guide / AUXFOG, hamqsl solar data. Identify which of these a county ARES/RACES site should link to and which are authoritative sources.` },
]


const PROFILE_SCHEMA = {
  type: 'object',
  properties: {
    slug: { type: 'string' },
    file: { type: 'string' },
    one_line: { type: 'string' },
    key_facts: { type: 'array', items: { type: 'object', properties: { claim: { type: 'string' }, source: { type: 'string' } }, required: ['claim', 'source'] } },
    open_questions: { type: 'array', items: { type: 'string' } },
  },
  required: ['slug', 'file', 'one_line', 'key_facts', 'open_questions'],
}
const VERIFY_SCHEMA = {
  type: 'object',
  properties: {
    slug: { type: 'string' },
    confirmed: { type: 'number' },
    refuted: { type: 'number' },
    unverifiable: { type: 'number' },
    corrections: { type: 'array', items: { type: 'string' } },
  },
  required: ['slug', 'confirmed', 'refuted', 'unverifiable', 'corrections'],
}

// Deep mode: pass args.deep = [canonical org objects from research/orgs/_canonical-orgs.json]
// and args.hierarchy_sketch to profile + fact-check only those orgs, skipping discovery.
function deepProfile(orgs, hierarchy_sketch) {
  const merged = { hierarchy_sketch }
  return pipeline(orgs,
  o => agent(`${SEED}\n\nWrite a deep research profile of ONE organization as it relates to Spokane County ARES/RACES.\n\nORG: ${JSON.stringify(o)}\n\nOverall relationship map (for context):\n${merged.hierarchy_sketch}\n\nResearch it thoroughly on the web (visit its own site plus independent sources). Then WRITE the profile with the Write tool to exactly: ${ROOT}/orgs/${o.slug}.md\n\nFile format (Markdown):\n# <Name>\n> one-line summary\n\n**Tier:** ${o.tier} · **Category:** ... · **Website:** ... · **Researched:** ${TODAY}\n\n## What it is\n## Relationship to Spokane County ARES/RACES  (be specific: authority, sponsorship, MOU, overlap of membership, shared infrastructure, reporting lines, served-agency relationship)\n## Key people / roles (with source + date seen; flag anything that may be stale)\n## Key facts (bulleted, each with [source](url))\n## Relevance to the website rebuild (what the new spokares.org should say about / link to this org; recommended canonical URL(s))\n## Open questions / unverified\n## Sources (list of URLs with access date)\n\nMark inferences explicitly as "(inference)". Do not invent names, callsigns, frequencies, or dates. Return the structured summary.`,
    { label: `profile:${o.slug}`, phase: 'Profile', schema: PROFILE_SCHEMA }),
  (p, o) => p && agent(`You are an adversarial fact-checker. Read the profile at ${ROOT}/orgs/${o.slug}.md (use the Read tool). For EVERY concrete factual claim (names, callsigns, titles, dates, frequencies, legal citations, relationships, URLs), try to REFUTE it by independently checking sources on the web (ToolSearch "select:WebSearch,WebFetch", or curl). Default to skepticism: if you cannot find support, it is unverifiable. Then EDIT the file in place: fix claims that are wrong (with the correct sourced fact), mark unsupported claims with "⚠️ unverified", and append a section:\n\n## Verification (${TODAY})\n- confirmed: N, corrected: N, unverifiable: N\n- list each correction and each unverifiable claim\n\nAlso check that every URL in the Sources section actually resolves (note dead links). Return the counts.`,
    { label: `verify:${o.slug}`, phase: 'Verify', schema: VERIFY_SCHEMA }).then(v => ({ ...p, verify: v })),
  )
}

if (args && Array.isArray(args.deep) && args.deep.length) {
  log(`Deep mode: profiling ${args.deep.length} org(s): ${args.deep.map(o => o.slug).join(', ')}`)
  const deep = await deepProfile(args.deep, args.hierarchy_sketch || '')
  return { deep: args.deep.map((o, i) => ({ slug: o.slug, profile: deep[i] })) }
}

phase('Discover')
const discovered = await parallel(LENSES.map(l => () =>
  agent(`${SEED}\n\n${l.prompt}\n\nReturn every organization/program/body you find in this lens that genuinely relates to Spokane County ARES/RACES, with how it relates and evidence URLs. Be exhaustive within the lens; mark confidence honestly.`,
    { label: `discover:${l.key}`, phase: 'Discover', schema: DISCOVER_SCHEMA })))

const allOrgs = discovered.filter(Boolean).flatMap(d => d.orgs)
log(`Discovered ${allOrgs.length} raw org mentions across ${discovered.filter(Boolean).length} lenses`)

phase('Merge')
const MERGE_SCHEMA = {
  type: 'object',
  properties: {
    orgs: {
      type: 'array',
      items: {
        type: 'object',
        properties: {
          slug: { type: 'string', description: 'kebab-case filename slug, unique' },
          name: { type: 'string' },
          aliases: { type: 'array', items: { type: 'string' } },
          urls: { type: 'array', items: { type: 'string' } },
          category: { type: 'string' },
          relationship_summary: { type: 'string' },
          tier: { type: 'string', enum: ['core', 'related', 'peripheral'] },
          research_questions: { type: 'array', items: { type: 'string' } },
          evidence_urls: { type: 'array', items: { type: 'string' } },
        },
        required: ['slug', 'name', 'category', 'relationship_summary', 'tier', 'research_questions'],
      },
    },
    dropped: { type: 'array', items: { type: 'string' }, description: 'mentions dropped as irrelevant or duplicates, with reason' },
    hierarchy_sketch: { type: 'string', description: 'text sketch of how the orgs nest/relate' },
  },
  required: ['orgs', 'dropped', 'hierarchy_sketch'],
}
const merged = await agent(`${SEED}\n\nBelow are raw organization mentions gathered by 5 independent research lenses (JSON). Merge duplicates (same org under different names/aliases), drop things that are not genuinely related, and produce a canonical list. Group trivially small items (e.g. individual FEMA IS courses) into one umbrella entry (e.g. "FEMA ICS/NIMS training") rather than separate entries. Tier: core = directly governs/sponsors/partners with Spokane ARES/RACES (ARRL/ARES chain, Spokane County EM, WA EMD RACES, IEVHFRA, etc.); related = regularly interacts or is linked; peripheral = tools/resources/context. Keep the list focused: aim for roughly 12-25 entries. For each, list the open research questions a profiler should answer.\n\nRAW:\n${JSON.stringify(allOrgs)}`,
  { label: 'merge-orgs', phase: 'Merge', schema: MERGE_SCHEMA })

log(`Canonical orgs: ${merged.orgs.length} (dropped ${merged.dropped.length} mentions)`)

phase('Map')
const mapDoc = await agent(`${SEED}\n\nYou are writing the WRAP-UP relationship map for this research. Do NOT start new deep research: the evidence was already gathered by 5 discovery agents (below). You may do at most ~8 quick web checks, only to resolve a direct contradiction about a CORE-tier relationship. Write with the Write tool to ${ROOT}/orgs/relationships.md:\n\n# Spokane County ARES/RACES — Related Organizations\n> Researched ${TODAY}. Light pass: relationships and links from discovery evidence; not every fact independently re-verified. See _backlog.md to go deeper on any org.\n\n## Relationship map\n(an ASCII/indented tree of the authority/sponsorship/partnership structure: ARRL -> Division -> Section -> WA ARES district -> county; the government RACES/emergency-worker chain; served agencies; local ham/repeater orgs; neighbours; tools/training)\n\n## Core organizations\n## Related organizations\n## Peripheral (tools, training, references)\nFor EACH org (grouped by tier): ### <Name> (aliases) — then: **What:** one line · **Relationship:** specific (authority, sponsorship, MOU, served agency, shared infrastructure, membership overlap) · **Links:** canonical URL(s) · **Confidence:** confirmed/likely/speculative with 1-3 evidence URLs · **For the rebuild:** what the new site should say/link · **Stale-risk:** anything (names, office holders, frequencies) that changes often.\n\n## People & office holders seen (table: role | name/callsign | org | source URL | date seen) — mark all as \"verify before publishing\".\n\n## Contradictions & unknowns\n\n## Dropped mentions (and why)\n\nCanonical org list (JSON):\n${JSON.stringify(merged.orgs)}\n\nHierarchy sketch:\n${merged.hierarchy_sketch}\n\nDropped:\n${JSON.stringify(merged.dropped)}\n\nRaw discovery evidence from all lenses (JSON):\n${JSON.stringify(allOrgs)}\n\nReturn a <=15 line plain-text summary of the structure and the 5 most important relationships for the rebuild.`,
  { label: 'map:relationships', phase: 'Map' })

return {
  summary: mapDoc,
  hierarchy_sketch: merged.hierarchy_sketch,
  dropped: merged.dropped,
  canonical_orgs: merged.orgs,
  raw_discovery: allOrgs,
}
