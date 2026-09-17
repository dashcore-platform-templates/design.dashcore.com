# Start here

**What it is:** one place that decides how every Dashcore app looks, and a board showing how far off each one is.

**Where:** https://design.dashcore.com

**Why it exists:** fifteen apps, fifteen slightly different greys. This picks the rules once and tells you who is drifting.

---

## Before you open it

You need two things. The first has to be given to you.

| Thing | Who | How long |
|---|---|---|
| An account | John runs `php artisan fleet:invite "Travis" <email>` | 1 min |
| A token, if you want to run commands | You make it, once signed in | 1 min |

No sign-up form. Accounts are made by someone who already has one.

---

## Look at it first (5 minutes)

Sign in, then walk these in order.

**`/designs`** — every design. One card each.

**`/designs/dashcore`** — open one. Left is the seven decisions. Right is live mockups.
- Change the accent. The mockups move.
- Nothing saves until you press Save.

**`/standard`** — the rules themselves. Thirteen sections.

**`/changelog`** — what moved and whether an app has to do anything.

**`/fleet`** — every app and how far off it is.

**`/connect`** — how a repo gets onto that board.

That is the whole product. There is nothing hidden behind a menu.

---

## The idea, in four lines

1. A design is **seven decisions**. Accent, neutral warmth, type pairing, radius, density, motif, sign-in copy.
2. Everything else is **fixed by the standard** and not up for debate per app.
3. An app **pulls a pack** and applies it. Same bytes every time.
4. The app **reports what it found** back to the board.

---

## Test it yourself

Pick any repo. This does not touch production anything.

### 1. Make your token

`/account` → **Tokens** → **New token** → name it after your laptop.

Put it in that app's `.env`:

```
DESIGN_TOKEN=dsn_…
```

Yours, not the repo's. One token covers every app you work on. **Never in CI.**

### 2. Pull a design

```bash
php artisan design:apply harbor-point
```

Writes theme variables, fonts, the sign-in layout and icons.

### 3. See what is off the standard

```bash
php artisan design:audit
```

A list per file. Nothing is auto-fixed that has a decision in it.

### 4. Check it can reach the board

```bash
php artisan design:connect
```

Reads the repo's own files and says what is missing. Registers nothing.

---

## Putting a repo on the board

Three secrets, **all at repository level**. The org is on the Free plan, so org-level secrets do not reach private repos. Do not try to set them on the org.

| Secret | What it is for |
|---|---|
| `COMPOSER_GITHUB_TOKEN` | Installing `dashcore/design-kit`, which is a private repo |
| `FLUX_USERNAME` | Flux Pro licence |
| `FLUX_LICENSE_KEY` | Flux Pro licence |

For the first one: a fine-grained personal access token with **read access to Contents** on `dashcoretech/design-kit`. Nothing else.

Then in the workflow that runs the tests:

```yaml
permissions:
  id-token: write
  contents: read
```

```yaml
- name: Report to design.dashcore.com
  if: github.ref_name == github.event.repository.default_branch
  env:
    DESIGN_SOURCE: https://design.dashcore.com
  run: php artisan design:audit --report
```

Push to the default branch. The row appears on `/fleet`.

**No secret for reporting.** GitHub vouches for the repo each run. That is what `id-token: write` buys.

Keep `contents: read`. Naming any permission turns the defaults off, and without it the job cannot check itself out.

---

## When something goes wrong

The three you are most likely to see.

**`This needs your token`**
→ No `DESIGN_TOKEN` in `.env`. Make one at `/account`.

**`404` on a zipball for `dashcore/design-kit`**
→ No `COMPOSER_GITHUB_TOKEN` on that repo.

**`There is no design called X`**
→ That app is on a design the tool does not hold. Open `/designs` → **Import** and paste the app's `design/design.json`. It comes back under the same name, so nothing is repainted.

---

## What is not done

Said plainly so you do not go looking.

- **ExecutiveOS is not on the board yet.** Its CI is red for the `COMPOSER_GITHUB_TOKEN` above, and its design needs importing.
- **Only one app reports so far.** The board is thin because the fleet has not been connected, not because nothing is drifting.
- **Fourteen repos still need those three secrets.** One at a time, by hand, while the org is on Free.
- **Email and error pages have rules but no built examples** in most apps.

---

## How it actually works

Skip unless you want it.

**Designs are rows in a database.** They used to be files in the repo, which did not survive a deploy.

**A pack is four files** served as one JSON document: the design, the standard, a CLAUDE snippet, and a migration procedure. Byte for byte identical every pull, so a repo that pulls twice sees no change.

**Two different credentials, on purpose:**
- *Yours* — pulls a design. Lives on your laptop. Covers every app.
- *GitHub's* — proves which repo is reporting. Minted per CI run. Nothing stored anywhere.

A repo never holds a credential for this. If one leaked out of a CI log it still could not read anybody's design.

**The tool holds itself to its own standard.** `composer standard:check` runs in its CI against its own screens. If a rule is too annoying to follow, we find out here first.

---

## Where the rest is

| File | What |
|---|---|
| `README.md` | Running it locally |
| `standard/` | The thirteen rule sections |
| `standard/CHANGELOG.md` | What moved, newest first |
| `docs/walkthrough.md` | The long version, brief to migrated app |
| `packages/design-kit/README.md` | The package every app installs |
