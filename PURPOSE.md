# PURPOSE — design.dashcore.com

**design.dashcore.com is the Dashcore Standard and the tool that applies it: it holds the rules a Dashcore-built Livewire Flux app follows, turns a two-sentence brief into a project's design, hands that design to the repository that will use it, and shows the people building those apps how far each one has drifted.**

Design was the part of every project that started from nothing. Flux and the
starter template give a base, but shells, type and colour were rebuilt by hand
each time, and a model asked for a mockup reached for the median and produced
a template. The answer settled on 11 September 2026 (`docs/discovery-handoff.md`)
was to split structure from identity: fix the structure once, in writing, and
leave exactly seven decisions open per project. That written standard, the
generator constrained by it, the package that applies it and the board that
measures it change together, so they live in one app rather than as a folder
inside a product that happens to have a UI.

## What it is for

Its users are the people who design and build Dashcore's Laravel apps, and the
coding agents working for them. Every page needs a session; there is no
sign-up form. In plain words, it does five jobs:

- **Holds the standard.** `standard/` is the rule set in markdown — principles,
  the two shells, type, colour roles with a measured contrast floor, spacing,
  radius and density, a rule per Flux component, motifs, motion and icons,
  email, error pages, widths and public pages — with a changelog that says
  whether an app has to act. The `/standard` and `/changelog` pages draw it.
  The same text, above each file's `<!-- prompt-end -->` marker, is the
  generator's instructions, so the rule a person reads is the rule the model
  obeys.
- **Generates and edits designs.** A design is the seven open decisions
  (accent, neutral temperature, type pairing, radius, density, motif, sign-in
  copy) plus the brief. `/designs/new` takes a brief and a human-chosen shell
  and asks the model for the seven, each with a reason (`app/Design/Generation`,
  behind a `DecisionSource` interface). A design page draws the standard's
  blocks in that design, live, and lets a person change any decision before
  saving. `/designs/compare` sets designs side by side. Generation is a
  suggest button: without an Anthropic key everything else works.
- **Refuses a design that fails contrast.** The resolver measures every text
  role against the surface it sits on, in both registers; a failing design is
  not exported. `php artisan design:check` runs that over every design.
- **Hands a design over.** `designs/{slug}/pack.json` (and `pack.zip`,
  `design.json`) export a pack — the design, the standard, a CLAUDE snippet
  and a migration procedure — that `design:apply` in the target repo pulls.
  The design page's handoff panel gives one prompt an agent can run to install,
  apply, build and migrate screens a commit at a time. `/blocks` hands over the
  screens a design does not decide.
- **Shows drift.** `/fleet` lists every app that reports, the design it uses,
  and its findings by rule over time. `/connect` is how a repository joins.

It also carries the adapter: `packages/design-kit` is the source of the
`dashcore/design-kit` Composer package (published by subtree split to the
`dashcoretech/design-kit` mirror), which provides `design:apply`,
`design:audit` and `design:connect` in a kit. This app runs `design:audit`
against its own screens in CI.

A new feature belongs here if it changes what the standard says, how a design
is chosen or checked, how it reaches a repository, or how drift from it is
measured. It does not belong here if it is about what any one app does.

## What it owns

- **The Dashcore Standard** — `standard/` and its changelog. The version an
  app reports against is this repository's stamp of it.
- **Designs** — rows in `designs`, keyed by slug, holding the brief, the seven
  decisions and the generator's record. They were JSON files in the repo until
  17 September 2026; the seeds in `database/seeders/designs` are imported by
  migration and are no longer the live copy.
- **The adapter package** — `packages/design-kit`. The `dashcoretech/design-kit`
  repository is a build artefact; an edit there is lost or blocks the next
  publish.
- **The drift board** — `sites` (an app on the board, by GitHub repository)
  and `site_reports` (every report, kept, so a trend can be read). A report
  is counts by rule, never the offending lines.
- **Who may use the tool** — its own `users` (Fortify, passkeys, two-factor),
  invited from `/people`, and `personal_tokens`, one per machine, for pulling
  packs from a terminal.

## What it is not for

- **Not the fleet's shared shell.** The admin frame, `dc-*` tokens and design
  rules that Dashcore's own fleet apps render with are `dashcore/ui`
  (`dashcoretech/ui`), a separate package with its own `DESIGN.md`. Nothing in
  this repository requires, imports or references `dashcore/ui`, and nothing
  in `dashcore/ui` references this app or `design-kit`. In the local checkouts
  on 2026-10-04 the fleet apps that take a shared look require `dashcore/ui`,
  and none requires `design-kit`. Both documents describe themselves as rules
  for every Dashcore app; which one governs the fleet's own panels is not
  settled in code. A change to the fleet's frame is a `dashcore/ui` release,
  not a change here.
- **Not an app's own UI.** It decides what a project looks like and checks
  it; the screens themselves are built and owned in that project's repository,
  where the applied `design/` folder is committed.
- **Not a source of truth for code.** It never reads a repository's source.
  It learns about an app only from what that app's CI reports, and from GitHub
  vouching for which repository is speaking.
- **Not the fleet's identity provider.** Its accounts are its own; fleet
  sign-in belongs to `login`. It has not been switched to SSO.
- **Not a security or quality auditor.** Dependency findings belong to
  `security`, outside-in checks of a deployment to `standards`, test suites to
  `tests`. The audit here is visual conformance to the Dashcore Standard only.
- **Not a peer on the bridge.** It does not publish fleet data, read other
  apps' data, or write anything into another app.

## Where it sits in the fleet

Group: **Surface**. It is a deployed, human-facing app, a domain app in the
sense that nothing it does is a thinking app's job — generation is an
optional suggestion, and a design page, an export and a report all work
without the model.

It is on the roster but is not a bridge peer: it carries no `dashcore/bridge`,
holds no fleet credential and has no `services.fleet.*` peers. That is why the
catalogue dropped it from the apps every install must have — an app that
cannot enrol on deploy would be a planned peer that never answers. Nobody in
the fleet reads from it over the bridge, and it calls no fleet app.

Its links to other apps run outside the bridge: a kit's CI posts a report to
`POST /api/reports`, proving its repository with a GitHub Actions OIDC token
(or a per-site token kept for apps that already have one), and a person's
`DESIGN_TOKEN` lets `design:apply` pull a pack. Its only outside dependency is
the Anthropic API, for generation.

It holds no grants today. The live grants are `FleetPlan` in api.dashcore.com
(`/admin/fleet/plan`).

## Rules that follow from its purpose

- **A tightening ships with its audit rule, or it does not reach the fleet.**
  A rule `design:audit` cannot check is said to be unchecked in the changelog,
  so silence is never read as compliance.
- **Seven decisions, and only seven.** Anything the standard does not list is
  not allowed. A preference that suits one project goes in that project's
  decisions, never into the standard. The shell is a human choice, never
  generated.
- **Edit the package here, never in the mirror.**
- **A report carries counts, never source.** Other people's code does not
  land in this database.
- **A pack is not public.** It carries a brief that names the project, so the
  exports need a session or a personal token — never a slug alone, and never a
  repository's credential, because a repository only reports.
- **Contrast is measured.** A colour rule change is followed by `design:check`.

---
*Last reviewed 2026-10-04 against `1cc3a4a` of `master`.*
