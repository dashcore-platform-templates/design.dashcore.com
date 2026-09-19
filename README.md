# design.dashcore.com

**New here? Read [docs/start-here.md](docs/start-here.md).** Ten minutes from signing in to having tested it.

This file is for running it locally and changing it.

---

## What it does

Every new Dashcore project gets a designed, on-standard look on day one, from a short brief, without building it by hand and without it reading as a template.

---

## Three parts

**The standard** fixes structure for every app: two shells, type and spacing scales, colour roles with a measured contrast floor, a rule for every Flux component, one icon set, motion limits, status colours that mean the same thing everywhere.

Lives in [`standard/`](standard/README.md). Doubles as the generator's instructions.

**The generator** reads the standard and a two-sentence brief, then chooses only the seven things the standard leaves open, with a reason for each. It never invents structure.

**The adapter** is a Composer package, [`dashcore/design-kit`](packages/design-kit/README.md). One command applies a design to a Laravel Livewire Flux kit.

---

## Why the package lives here

It changes with the standard, and the two should move in one commit.

Composer cannot install from a subdirectory, so it is published to `dashcoretech/design-kit`. That is a mirror. **Edit it there and the change is lost on the next publish.**

Publishing is automatic:

- push to `master` → mirror updated
- tag `design-kit/vX.Y.Z` → that version released

A release reaches every kit on the next `composer update`, new audit rules included. Rules show up as findings on the board and reporting never fails a build, so there is nothing to stop and wait for. A major means the package's own interface broke, which is rare.

---

## The seven decisions

Everything else is fixed. The shell is a human choice in the brief, never generated.

| Decision | Values |
|---|---|
| accent | a hex, at least 30° from red and amber, saturation 45–85 |
| temperature | pure, cool, warm |
| pairing | one of five approved type pairings |
| radius | sharp, soft, round |
| density | comfortable, compact |
| motif | accent bar, hairline, eyebrow, filled stat |
| copy | sign-in eyebrow, headline, tagline, footer |

---

## The loop

Longer version with what to look for at each stage: [docs/walkthrough.md](docs/walkthrough.md).

1. `/designs/new` — describe the client in two sentences, pick a shell, Generate.
2. Look at the blocks drawn in it. Change any decision, they update live.
3. **Use this design** → paste the prompt into Claude Code in the target repo. The agent installs, applies, builds, commits, and migrates screens one commit at a time.
4. `design/` is committed in that repo. Every later agent session reads it first.
5. Existing app: review the migration commits, one per screen, against the blocks drawn here.

---

## Running it

PHP 8.3 and Node. Herd serves it at `design.dashcore.com.test`.

**Postgres, not SQLite** — including the test suite. Production and CI run Postgres, and the two disagree in ways a SQLite test cannot see. `jsonb` does not keep object key order, which reordered every design on the way out and reached production once.

Herd ships Postgres on port 5433, user `root`, no password:

```bash
psql -h 127.0.0.1 -p 5433 -U root -d postgres -c "create database design_dashcore; create database design_dashcore_test;"
```

```bash
composer install && npm install
cp .env.example .env && php artisan key:generate
php artisan migrate
npm run dev
```

**Generation needs `ANTHROPIC_API_KEY`.** Without it everything else works; generating is a suggest button, never a dependency. Model is `DESIGN_MODEL`, default Claude Fable 5.1 after a three-way comparison on DAS-2016.

```bash
php artisan design:check                      # contrast for every design, both registers
php artisan design:generate "Client" "What it does and who uses it"
```

---

## Who can see what

**Every page needs a session.** The tool lists every Dashcore product and how far off the standard each one is.

**Packs need a token** — yours, from `/account`, or a session for Download pack in a browser. A pack carries the brief, which names the client.

**Reporting needs neither.** A workflow proves which repository it is to GitHub, so nothing is stored in any repo.

Accounts are made at `/people`, which hands you a link to send. The person sets their own password, so nobody passes one around. `php artisan fleet:invite` still works for a first account on an empty database. There is no sign-up form.

---

## Where designs live

Rows in `designs`, one per project.

They were JSON files in the repo until 17 September 2026, which did not survive a deploy once this was hosted. The five that existed then are in `database/seeders/designs` and the migration imports them, so a fresh clone has them too.

---

## Where things are

| Path | What |
|---|---|
| `standard/` | The rules. `00` is the audit, `01` onward are the sections |
| `app/Design/` | Resolver, colour maths, temperature and pairing lists, CSS emitter, the pack |
| `app/Design/Generation/` | Schema, rules pass, the SDK call behind a `DecisionSource` interface |
| `app/Fleet/` | Verifying a GitHub Actions token, so a repo can prove which one it is |
| `resources/prompts/generator.md` | How the model is told to answer |
| `resources/views/components/block/` | The blocks, one component each. A design page draws them; `/blocks` hands them over |
| `app/Design/Blocks.php` | The list, and the prompt that builds each one in a target repo |
| `packages/design-kit/` | The adapter package |
| `docs/` | Start here, the walkthrough, the cut sheet, the discovery note |

---

## Two things before you change anything

**The standard is split.** Each file carries a `<!-- prompt-end -->` marker. The generator reads above it; the `/standard` page and the exported pack read the whole file. Rules only a screen-builder acts on sit below, so they do not cost tokens on every call. [`docs/cut-sheet.md`](docs/cut-sheet.md) records which side each rule is on and why.

**Contrast is measured, not eyeballed.** The resolver checks every text role against the surface it actually sits on, in both registers. A design that fails is not exported. If you change a colour rule, run `design:check`.

---

Tracked in Linear under the design.dashcore.com project.
