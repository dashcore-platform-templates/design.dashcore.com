# From a brief to a migrated app

> **Read [start-here.md](start-here.md) first** if you have not used this before. It is shorter and covers signing in, the token you need, and the three secrets a repo needs.
>
> Two things on this page changed after it was written: the pages need a sign-in now, and pulling a design needs your own token in `DESIGN_TOKEN`. Everything else still holds.

The whole path, in order, with what to look for at each step. A new project stops after step 6. An app that already exists carries on to step 7.

Two things worth knowing before you start. The viewer works in full without an API key, so generating is a suggest button and never a dependency. And nothing here is one-way: a design is a file you can edit by hand at any point, and applying it twice changes nothing.

---

## 1. Write the brief

Open `/designs/new`.

| Field | What to put |
|---|---|
| Client | Who the app is for |
| What the app does | One or two sentences: what it does and who uses it day to day |
| Shell | Sidebar for a tool used for hours with many destinations. Top nav for a portal opened now and then with six or fewer |
| Brand colour | Only if the client already owns one. It becomes the accent even where it bends the hue rule |
| Notes | Anything that constrains the look: who the audience is, what the screens are mostly made of, a colour already reserved for something, a thing to avoid |

The notes field does the most work. "Older patients see the sign-in page" produced a legibility-first typeface. "Amber is reserved for the rows that want you" produced an accent chosen to be unmistakable against it. A brief with no constraints gets a competent, unsurprising design, which is the fair outcome.

The shell is your choice and is never generated.

## 2. Generate

Twenty to thirty seconds. The standard goes in as a cached prompt, so the second and later generations in a session are cheaper and faster.

You land on the design page with seven decisions filled in, each with a line saying why.

## 3. Read the reasons before the pixels

The reasons are the part to judge. A good one names something from your brief. A weak one describes the colour it chose. If a reason does not hold up, that decision is the one to change.

## 4. Change anything

Every decision on the left is a control. The mockups on the right update as you change them, in whichever register the toggle is on.

- **Accent** takes a picker or a hex. The strip beneath shows what the resolver derived: hover, the text version, the tint, and what sits on top of a filled button.
- **Type pairing** is the five approved ones. Anything else needs a person to look at it first and a line in the standard.
- **Motif** is the structural habit: an accent bar, hairlines instead of cards, uppercase labels, or a filled first stat tile.
- **Sign-in copy** is three fields. Edit the reason under any decision too; the reason a model wrote for a value you have since changed is worse than no reason.

Save writes the file. Revert reloads it. Regenerate re-runs the brief and replaces the decisions, so use it when the brief changed rather than when one value is wrong.

## 5. Check the five screens

Along the top: sign-in, shell, dashboard, settings, components.

- **Sign-in** is where the copy and the second typeface show. If the pairing is a serif or a display face, this is where you see it.
- **Shell** is the regions with an empty state in the middle.
- **Dashboard** is the one to judge hardest: a stat row, a table, a callout, a side panel.
- **Settings** is a form, a fieldset, switches and an error.
- **Components** renders every free Flux component in this design, with the overlays drawn open. If a design is going to break something, it breaks here.

Look at both registers. The light and dark toggle is top right, and a design that is fine in one can be wrong in the other.

The contrast panel at the bottom of the controls is measured, not estimated. A design that fails is not exportable.

## 6. Use this design

The button at the bottom of the controls opens the handoff. The main thing in it is a prompt, already filled in for this design. Paste it into Claude Code opened in the target repo, and the agent installs the adapter, pulls the pack, applies it, builds, adds the CLAUDE snippet, commits, and if the app already has screens, migrates them one commit at a time and ends with a report. Your part is reviewing the commits, which is step 7.

One thing stays with you, once per machine. The package is private, so Composer needs its own GitHub token. The GitHub CLI's credentials are not enough, because Composer runs git non-interactively. The prompt tells the agent that if Composer cannot reach the repository it stops and asks you to run this, and that it never asks for the token itself:

```bash
composer config --global github-oauth.github.com <token>
```

### By hand

The same panel folds the manual path away. The design page serves the pack at `/designs/<slug>/pack.json`: four files, which the apply command in the target repo pulls into `design/` before it changes anything.

| File | Who reads it |
|---|---|
| `design.json` | The apply command, and you |
| `STANDARD.md` | An agent or a person building a screen |
| `CLAUDE.snippet.md` | Paste into the target repo's `CLAUDE.md` |
| `MIGRATION.md` | The agent's procedure for an app that already exists |

In the target repo:

```bash
composer config repositories.design-kit vcs https://github.com/dashcoretech/design-kit.git
composer require dashcore/design-kit
php artisan design:apply https://design.dashcore.com/designs/<slug>/pack.json
npm install
npm run build
```

With `DESIGN_SOURCE` in the kit's `.env` pointing at the design.dashcore.com you use, the slug alone is enough: `php artisan design:apply <slug>`. Download pack on the design page is the same four files as a zip, for anyone offline: unzip it into the repo root and run `php artisan design:apply design/design.json`.

`npm install` matters only on a fresh clone, and it is the step everyone forgets on one.

The fleet is on free Flux, and the standard is written for it. Do not install `livewire/flux-pro`: where section 06 mentions a Pro component, it names the free one to use instead.

`design:apply` prints its plan before writing: the four pack files under `design/`, the theme block in `app.css`, the fonts, the brand keys in `.env`, the sign-in layout, the logo, and the Lucide icon names renamed to Heroicons. It also offers to delete the kit's three stock sign-in layouts under `layouts/auth/`, which nothing references once the sign-in layout is the two-panel one. Run it with `--check` first if you want to see the plan without writing. Running it twice changes nothing, and it refuses a design that fails contrast.

Then paste the CLAUDE snippet into the repo's `CLAUDE.md` and commit `design/`. That is what keeps every later session on the design.

**A new project stops here.** Open it: the sign-in page carries the generated copy in the chosen faces, and every Flux component already follows.

## 7. An existing app: review the migration

Applying a design rethemes an app. It does not restructure it. An older app still has its own headings, its own colour utilities typed into views, its own spacing and its own badges. Bringing the screens over is a judgment job, and the prompt from step 6 makes it the agent's, with you reviewing one commit per screen. On Executive OS, about seventy views, the first agent pass brought the audit from 326 findings to 42 in one session. What was left was judgment calls and one gap in the standard.

### 7a. Look at the map

```bash
php artisan design:audit
```

Every finding, per file and by rule: colour utilities outside the roles, spacing off the eight-step scale, weights, text under 12px, hardcoded hex, Flux badges where the status roles belong, Flux Pro components, Lucide icon names, and page titles not going through the page header. `--full` lists them all rather than the first few per file. You are not fixing anything here, only seeing the shape of the work: which screens carry most of it, and whether anything in the list is deliberate.

Two kinds of file are not migration targets. A public page, a landing page or a legal document, sits outside the app standard. It still takes the fonts and the neutral scale from the theme, and if it should otherwise stay as it is, say so at the top of the file and the audit skips it:

```blade
{{-- @ds-audit-ignore-file public landing page, composed outside the app standard --}}
```

A single line that is deliberately off says the same without `-file`:

```blade
<div class="p-5"> {{-- @ds-audit-ignore the brand mark is fixed at this size --}}
```

### 7b. What the agent does

It follows `design/MIGRATION.md`, which the pack put in the repo: the mechanical pass in a commit of its own, then one screen per commit, most-used first, checking each with `design:audit` on that file before committing. MIGRATION.md is the whole procedure, the order, the rewrite for each kind of finding, the five components with their props, and how to check a screen, so there is nothing to brief beyond the prompt. Commit first if the tree is not clean, so every diff from here is readable.

### 7c. Review each commit

The commits arrive one screen at a time. For each, open the screen in both registers and check the things a text scan cannot:

- The title is in the page header, in the pairing's second face. This is the one that makes a serif or display pairing appear at all.
- A stat row is two to four tiles. If there were six, the agent chose which four are the work. Agree or disagree.
- Status colour is on badges, callouts, toasts, form errors and deltas. Not on a stat value, not on a heading.
- Every list has a loading, empty and error state.
- Cards hold one thing and are never nested.

### 7d. Read the report

The agent ends with what is left and why. Expect two kinds of leftover. Deliberate exceptions, each carrying an ignore with its reason. And gaps in the standard: Executive OS wanted badges for roles and plans, which the standard forbids without offering an alternative. A gap is filled in section 06, never worked around in one app.

### 7e. Verify

```bash
php artisan design:audit
php artisan design:check design/design.json
npm run build
```

Then the real test: the app's dashboard beside the tool's dashboard mockup. They should read as the same design.

---

## 8. Later: the standard changes

Steps 1 to 7 set an app up, and setting up happens once. When a rule changes afterwards, do not run them again. Installing the adapter, replacing the sign-in layout and pasting the CLAUDE snippet are all one-time, and repeating them is noise.

An update is three commands in the app:

```bash
composer update dashcore/design-kit
php artisan design:update <slug>
```

The first has to come first, because the components and the audit rules live in the package. `design:update` then re-applies the design, tells you whether the standard moved, and runs the audit against the rules as they now read. It refuses to run on an app that has no `design/design.json`, since that app was never set up.

The top of `design/STANDARD.md` carries a stamp, which is the standard's text hashed. When it differs from the served one, apply says so by name:

```
WARN  The standard moved since you last applied: deadbeef is now bd3837a1.
```

`design/CHANGELOG.md` then says what moved. Each entry says whether it is a relaxation or a tightening, and whether the audit can check it.

That distinction is the whole thing. A relaxation lets an app do something new, so nothing that was right becomes wrong and there is nothing to fix. A tightening makes existing screens drift, and it only finds those screens if the audit can check it. Where an entry says the audit cannot, a person has to look, because nothing will report it.

Work the findings the way step 7 says: one screen per commit, with you reviewing each.

## When something is off

**A decision you disagree with.** Change it on the design page and fix the reason under it. If you disagree with the same thing on three projects, it is the standard that is wrong, not the generator.

**The generator picks something the standard forbids.** The rules pass catches it, corrects it, and writes what it changed into that decision's reason, so it shows up rather than passing silently.

**A component looks wrong in one design.** Check the Components screen in both registers. If it is the component rather than the design, it is a rule missing from section 06.

**The audit flags something that should stay.** Add `@ds-audit-ignore` with the reason on that line. An ignore without a reason is worse than the finding.

**The audit keeps reporting a page that is not an app screen.** A landing page or a legal document is outside the app standard. Put `@ds-audit-ignore-file` with the reason at the top and the audit skips the file, listing it as skipped so nobody forgets it is there.

**An app needs something the standard has no rule for.** Say so rather than inventing it. Executive OS wanted badges for roles and plans, which the standard forbids without offering an alternative; that is a gap to fill in section 06, not something to work around in one app.
