# Start here

**What:** one place that decides how every Dashcore app looks, and a board showing how far off each one is.

**Where:** https://design.dashcore.com

**Why:** fifteen apps, fifteen slightly different greys.

---

## You need two things

| Thing | Who gives it |
|---|---|
| An account | Someone who already has one, with `php artisan fleet:invite` |
| A token, only if you run commands | You, at `/account` |

No sign-up form.

---

## Click these, in order

| Page | What you get |
|---|---|
| `/designs` | Every design, one card each |
| `/designs/dashcore` | Seven decisions on the left, live mockups on the right |
| `/standard` | The rules, 13 sections |
| `/changelog` | What moved, and whether you have to act |
| `/fleet` | Every app, and how far off it is |
| `/connect` | How a repo joins that board |

On a design page: change the accent, watch the mockups move. Nothing saves until you press Save.

That is the whole product.

---

## The idea

1. A design is **seven decisions**.
2. Everything else is **fixed**.
3. An app **pulls a pack** and applies it.
4. The app **reports what it found**.

---

## Try it

Any repo. Nothing here touches production.

**1. Token**

`/account` → Tokens → New token. Then in that app's `.env`:

```
DESIGN_TOKEN=dsn_…
```

Yours, not the repo's. Covers every app you work on. Never in CI.

**2. Pull a design**

```bash
php artisan design:apply harbor-point
```

**3. See what is off**

```bash
php artisan design:audit
```

**4. Can it reach the board?**

```bash
php artisan design:connect
```

Reads the repo's files and says what is missing. Registers nothing.

---

## Putting a repo on the board

**Three secrets, all repository level.** We are on the Free plan, so org-level secrets do not reach private repos. Do not try the org.

| Secret | For |
|---|---|
| `COMPOSER_GITHUB_TOKEN` | Installing `dashcore/design-kit`, a private repo |
| `FLUX_USERNAME` | Flux Pro licence |
| `FLUX_LICENSE_KEY` | Flux Pro licence |

The first is a fine-grained token with **read access to Contents** on `dashcoretech/design-kit`. Nothing else.

**Then two blocks** in the workflow that runs the tests:

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

Push to the default branch. The row appears.

**No secret for reporting.** GitHub vouches for the repo each run.

Keep `contents: read`. Naming any permission turns the defaults off.

---

## Three errors you will hit

| Message | Means |
|---|---|
| `This needs your token` | No `DESIGN_TOKEN` in `.env` |
| `404` on a `design-kit` zipball | No `COMPOSER_GITHUB_TOKEN` on that repo |
| `There is no design called X` | Import the app's `design/design.json` at `/designs` |

---

## Not done yet

- **ExecutiveOS is not on the board.** Needs a secret and a design import.
- **One app reports so far.** The board is thin because the fleet is not connected, not because nothing is drifting.
- **Fourteen repos need those three secrets.** By hand, while we are on Free.

---

## How it works

Skip unless you want it.

**Designs are database rows.** They were files once. Files did not survive a deploy.

**A pack is four files** as one JSON document: the design, the standard, a CLAUDE snippet, a migration procedure. Identical bytes every pull.

**Two credentials, on purpose:**

- *Yours* — pulls a design. On your laptop. Every app.
- *GitHub's* — proves which repo is reporting. Minted per run. Stored nowhere.

A repo never holds a credential for this.

**The tool follows its own standard.** `composer standard:check` runs against its own screens in CI.

---

## Everything else

| File | About |
|---|---|
| `README.md` | Running it locally |
| `standard/` | The 13 rule sections |
| `standard/README.md` | How the rules work and how to change one |
| `standard/CHANGELOG.md` | What moved, newest first |
| `docs/cut-sheet.md` | Why each rule was decided that way |
| `docs/walkthrough.md` | The long version |
| `docs/discovery-handoff.md` | Why this exists at all |
