# The Dashcore Standard

**What:** the rules every Dashcore app follows.

**Who reads it:** people building screens, and the model that generates a design.

Both read this same text. A rule you cannot check against a mockup does not belong here.

---

## The line

**Fixed.** Shells, scales, components, icons, motion, status colours, contrast. Nobody changes these per project.

**Varied.** Seven things, and only these:

- accent colour
- neutral temperature
- type pairing
- radius step
- density
- motif
- sign-in copy

Every option is listed in these files. Not listed means not allowed.

---

## Why fifteen

The fleet is every app Dashcore runs, about fifteen.

They have to look like one company without looking like one client's app repainted.

**That number is the test.** A rule that holds across fifteen products belongs here. A rule that suits one client is a preference, and preferences go in that project's seven decisions.

---

## The files

| File | What it fixes |
|---|---|
| `00-audit.md` | What was kept and dropped from the two looks we had. Background, not rules. |
| `01-principles.md` | What every screen must be true to. |
| `02-shell.md` | The two app shells and their regions. |
| `03-type.md` | Type scale, weights, roles, approved pairings. |
| `04-colour.md` | Colour roles, neutral temperatures, accent rules, status colours, contrast floor. |
| `05-space-radius-density.md` | Spacing scale, radius steps, densities. |
| `06-components.md` | Every component, mapped to Flux, with its rule. |
| `07-motifs.md` | The four motifs a project picks one of. |
| `08-motion-icons.md` | Motion limits and the one icon set. |
| `09-decisions.md` | The seven decisions, the JSON, and the export pack. |
| `10-email.md` | Email, the one surface the rest of this cannot reach. |
| `11-errors.md` | Error pages and first run. |
| `12-widths.md` | The three widths we design at. |
| `13-public.md` | Landing pages and anything a signed-out person sees. |

---

## Changing a rule

Two kinds, and they cost differently.

**Relaxation** — an app may now do something it could not. Nothing that was right becomes wrong. Nobody has to act.

**Tightening** — existing screens drift the moment it lands.

A tightening only reaches an app if `design:audit` can check it. That is the one thing an app runs against its own code.

> **A tightening ships with its audit rule in the same change, or it does not reach the fleet.**

If a rule cannot be checked from the text of a file, say so in the changelog entry. Nobody should mistake silence for compliance.

Every change gets an entry in [`CHANGELOG.md`](CHANGELOG.md): what moved, and whether an app has to act.

---

## How it reaches the model

Files **01 to 09** become the generator's instructions, followed by the brief. `00` is left out, it is history for people.

Each file is cut at a `<!-- prompt-end -->` marker.

**Above the marker** — what the generator reasons from. Principles, shells in a paragraph, type scale, colour roles, density, motifs, the seven decisions.

**Below it** — what only a person or an agent building a screen acts on. Component behaviour, shell geometry, motion, contrast tables, the Flux mapping.

The generator never builds a screen, so those rules would cost tokens on every call and steer nothing.

Nothing is lost. The `/standard` page and the `STANDARD.md` in every pack read the whole file, marker and all. `docs/cut-sheet.md` records which rule sits on which side and why.

The schema in `09` is also enforced as the structured output shape, so the model cannot return a decision that is off the list, whatever the prose says.

---

## Words we use

| Word | Means |
|---|---|
| Register | Light or dark mode |
| Surface | Any filled rectangle content sits on |
| Page | The outermost background |
| Sunken | A surface set slightly back from the page, for grouped inputs and table heads |
| Strong / default / muted | The three weights of text colour, not font weight |
