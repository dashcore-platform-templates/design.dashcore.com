# The Dashcore Standard

This folder is the design standard for every app in the Dashcore fleet. It is two things at once: the rules a person follows when building a screen, and the instructions Claude Fable is given when it generates a design for a new project. Both read the same text, so a rule that cannot be checked against a mockup does not belong here.

The fleet is every app Dashcore runs, about fifteen of them, which have to look and work the same without looking like one client's app repainted. That number is the test a rule has to pass. A rule that holds on fifteen products belongs here; one that suits a single client is a preference and belongs in that project's design file, where the seven decisions live.

Status: draft zero, September 2026. Written whole so John can cut it in one sitting. Nothing in it is settled until he has.

## Changing a rule

Every change is a relaxation or a tightening, and they cost differently.

A **relaxation** lets an app do something it could not before. Nothing that was right becomes wrong, so no existing site has to act.

A **tightening** makes existing screens drift the moment it lands. It only reaches a site that is already on the standard if `design:audit` can check it, because that is the one thing a site runs against its own code. A tightening with no check lives in a document and shapes new work alone. The old screens are never found.

So: **a tightening ships with its audit rule in the same change, or it does not reach the fleet.** If the rule cannot be checked from the text of a file, say so in the changelog entry, so nobody mistakes silence for compliance.

Every change also gets an entry in [`CHANGELOG.md`](CHANGELOG.md), saying what moved and whether an app has to act. The pack carries that file, and the stamp at the top of the exported standard is this text hashed, so a site can tell its copy is behind.

## How it is organised

| File | What it fixes |
|---|---|
| 00-audit.md | What was kept and rejected from the two looks the fleet has today. Background, not rules. |
| 01-principles.md | The short list of things every screen must be true to. |
| 02-shell.md | The two app shells and their regions. |
| 03-type.md | The type scale, weights, roles, and the approved pairings. |
| 04-colour.md | Colour roles, the three neutral temperatures, the accent rules, fixed status colours, and the measured contrast floor. |
| 05-space-radius-density.md | The spacing scale, the three radius steps, and the two densities. |
| 06-components.md | The component inventory, mapped to Flux, with the rule for each. |
| 07-motifs.md | The four structural motifs a project may pick one of. |
| 08-motion-icons.md | Motion limits and the one icon set. |
| 09-decisions.md | The seven things the generator is allowed to decide, the JSON it produces, and the export pack a project receives. |
| 10-email.md | Email, which is the one surface where the rest of this standard cannot apply directly. |
| 11-errors.md | The error pages, first run, and the other screens nobody styles until they are the ones giving an app away. |
| 12-widths.md | The three widths the fleet designs at, and what each shell and component does at each. |

## Fixed versus varied

The standard fixes structure. Shells, scales, components, icons, motion, status colours, contrast. A person or a generator may not change these per project.

The generator varies identity, and only these seven things: accent colour, neutral temperature, type pairing, radius step, density, motif, and the sign-in copy. Everything it may choose from is listed in these files. If a value is not listed, it is not allowed.

## How it becomes the system prompt

Files 01 through 09 are handed to the generator as its instructions, followed by the project brief. File 00 is left out; it is history for people, not rules for the model.

Each file is cut at a `<!-- prompt-end -->` marker. Above it is what the generator reasons from: the principles, the shells in one paragraph, the type scale and pairings, the colour roles and accent rules, density and radius, the motifs, the seven decisions. Below it is what only a person or an agent building a screen acts on: component behaviour, shell geometry, motion, icon sizes, the measured contrast tables, the mapping onto Flux. The generator never builds a screen, so those rules would cost tokens on every call and steer nothing.

Nothing is lost by the cut. The Standard page and the `STANDARD.md` in every exported pack read the whole file, marker and all. `docs/cut-sheet.md` records which rule sits on which side and why.

The schema in 09 is also enforced as the structured output shape, so the model cannot return a decision that is off the list, whatever the prose says.

## Vocabulary

Register means light or dark mode. Surface means any filled rectangle content sits on. Page means the outermost background. Sunken means a surface set slightly back from the page, used for grouped inputs and table heads. Strong, default and muted are the three text weights of colour, not font weight.
