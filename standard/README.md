# The Dashcore Standard

This folder is the design standard for every app in the Dashcore fleet. It is two things at once: the rules a person follows when building a screen, and the instructions Claude Fable is given when it generates a design for a new project. Both read the same text, so a rule that cannot be checked against a mockup does not belong here.

Status: draft zero, September 2026. Written whole so John can cut it in one sitting. Nothing in it is settled until he has.

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

## Fixed versus varied

The standard fixes structure. Shells, scales, components, icons, motion, status colours, contrast. A person or a generator may not change these per project.

The generator varies identity, and only these seven things: accent colour, neutral temperature, type pairing, radius step, density, motif, and the sign-in copy. Everything it may choose from is listed in these files. If a value is not listed, it is not allowed.

## How it becomes the system prompt

Files 01 through 09 are concatenated in order and handed to Claude Fable as its instructions, followed by the project brief. File 00 is left out; it is history for people, not rules for the model. The schema in 09 is also enforced as the structured output shape, so the model cannot return a decision that is off the list.

## Vocabulary

Register means light or dark mode. Surface means any filled rectangle content sits on. Page means the outermost background. Sunken means a surface set slightly back from the page, used for grouped inputs and table heads. Strong, default and muted are the three text weights of colour, not font weight.
