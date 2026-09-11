# From a brief to a migrated app

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
- **Components** renders every Flux component in this design, free and Pro, with the overlays drawn open. If a design is going to break something, it breaks here.

Look at both registers. The light and dark toggle is top right, and a design that is fine in one can be wrong in the other.

The contrast panel at the bottom of the controls is measured, not estimated. A design that fails is not exportable.

## 6. Download the pack, and apply it

Download pack gives a zip with four files.

| File | Who reads it |
|---|---|
| `design.json` | The apply command, and you |
| `STANDARD.md` | An agent or a person building a screen |
| `CLAUDE.snippet.md` | Paste into the target repo's `CLAUDE.md` |
| `MIGRATION.md` | Only for an app that already exists |

In the target repo:

```bash
unzip ~/Downloads/<client>-design-pack.zip -d .
composer config repositories.design-kit vcs https://github.com/dashcoretech/design-kit.git
composer require dashcore/design-kit:dev-master
php artisan design:apply design/design.json
npm run build
```

Composer needs its own GitHub token once per machine for the private package: `composer config --global github-oauth.github.com <token>`. The GitHub CLI's credentials are not enough, because Composer runs git non-interactively.

`design:apply` prints its plan before writing: the theme block in `app.css`, the fonts, the brand keys in `.env`, the sign-in layout, the logo, and the Lucide icon names renamed to Heroicons. Run it with `--check` first if you want to see the plan without writing. Running it twice changes nothing, and it refuses a design that fails contrast.

Then paste the CLAUDE snippet into the repo's `CLAUDE.md` and commit `design/`. That is what keeps every later session on the design.

**A new project stops here.** Open it: the sign-in page carries the generated copy in the chosen faces, and every Flux component already follows.

## 7. An existing app: migrate the screens

Applying a design rethemes an app. It does not restructure it. An older app still has its own layouts, its own colour utilities typed into views, and its own spacing. That is the work in this step, and on an app of about seventy views it is two to three days.

### 7a. Get the map

```bash
php artisan design:audit
```

Every finding, per file and by rule: colour utilities outside the roles, spacing off the eight-step scale, weights, text under 12px, hardcoded hex, Flux badges where the status roles belong, Lucide icon names, and page titles not going through the page header. `--full` lists them all rather than the first few per file.

A line that is deliberately off-standard can say so, and the audit will leave it alone:

```blade
<div class="p-5"> {{-- @ds-audit-ignore the brand mark is fixed at this size --}}
```

### 7b. The mechanical half, one commit

```bash
php artisan design:audit --fix
```

Weights and off-scale spacing only, because those have no judgment in them. It reports everything else and leaves it alone. Run the app's tests, look at the diff, commit on its own so the next diff is readable.

### 7c. Colours onto the roles

The largest group, and mostly mechanical once you have decided that a given red means danger rather than decoration. A role already flips per register, so a light class and its dark partner collapse into one:

```
text-red-600 dark:text-red-400   →   text-(--ds-danger)
bg-amber-50 dark:bg-amber-950    →   bg-(--ds-warning-soft)
```

A second grey family beside the design's own is the drift itself: fold it into zinc at the same step, or change the design's temperature if the app was right and the design was wrong.

### 7d. The judgment half, one commit per screen

Most-used screens first, screenshot before and after each.

- Titles go through `x-ds::page-header`. This is the one that makes a serif or display pairing appear at all.
- A stat row is two to four tiles inside `x-ds::stat-row`. If there are six, decide which four are the work and move the rest somewhere quieter.
- Status colour belongs on badges, callouts, toasts, form errors and deltas. Not on a stat value, not on a heading.
- Every list gets a loading, empty and error state. A blank region is not a state.
- Cards hold one thing and are never nested.

### 7e. Verify

```bash
php artisan design:audit
php artisan design:check design/design.json
php artisan view:cache && php artisan view:clear
npm run build
```

Then the real test: the app's dashboard beside the tool's dashboard mockup. They should read as the same design.

---

## When something is off

**A decision you disagree with.** Change it on the design page and fix the reason under it. If you disagree with the same thing on three projects, it is the standard that is wrong, not the generator.

**The generator picks something the standard forbids.** The rules pass catches it, corrects it, and writes what it changed into that decision's reason, so it shows up rather than passing silently.

**A component looks wrong in one design.** Check the Components screen in both registers. If it is the component rather than the design, it is a rule missing from section 06.

**The audit flags something that should stay.** Add `@ds-audit-ignore` with the reason on that line. An ignore without a reason is worse than the finding.

**An app needs something the standard has no rule for.** Say so rather than inventing it. Executive OS wanted badges for roles and plans, which the standard forbids without offering an alternative; that is a gap to fill in section 06, not something to work around in one app.
