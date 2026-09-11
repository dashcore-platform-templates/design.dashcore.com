# Cut sheet for the standard

Marked on 11 September 2026. The Decision column is what will be applied; nothing in the standard has been changed by this file yet. Overrule any line and I will follow the change instead.

Three terms in the Decision column:

- **keep** stays in the prompt the generator reads, and in the document.
- **cut from prompt** moves below a `<!-- prompt-end -->` marker in its own file. It stays in the document, on the Standard page, and in the `STANDARD.md` that ships in every pack for agents building screens. The generator stops paying for it.
- **cut** is deleted outright. Only the pairings row uses this.

The **Enforced by** column is why cutting length does not cost output quality. Rules marked code are enforced by the schema, the resolver, the rules pass or the adapter regardless of what the prose says. Rules marked people are only acted on by someone building a screen, and the generator never builds a screen. Rules marked generator are the ones the model actually reasons from, and almost all of them are kept.

## The three open questions, decided

**4.9, input outlines.** Relax the rule: the focus ring carries the meaning, and a resting input outline may be quiet. The 3:1 floor stays for borders that are the only signal a control exists. Why: an input already announces itself by shape, label, placeholder and a two pixel accent focus ring, and Flux's own inputs sit at the same step. Moving every outline to the 500 grey would be heavier than anything in the fleet and would fight the framework on every screen. The resolver already reports the pair as information rather than a failure, so no code changes.

**6.4, Flux badge colours.** Reversed while applying, on 11 September 2026. The package ships `x-ds::badge` instead of overriding Flux's. Why the reversal: Flux paints badges from Tailwind's palette across six steps per colour, so matching the standard would mean repainting green, amber, red and blue for the whole app, including every tint those steps are used for elsewhere. That is a far bigger change than the few lines the original decision assumed, and it would make an app's greens depend on which Flux component happened to use which step. One small component that reads the status roles is the smaller change. The standard now says status badges use `x-ds::badge` and Flux's badge is not used for status.

**6.5, headings and the display face.** The standard now requires page titles to go through `x-ds::page-header`. Why: Flux's heading does not take the pairing's second face, so a serif or display pairing is invisible without it, which makes one of the seven decisions half inert. The rule is one line in 02 and one in 06.

**6.6, a badge with no status.** Added on 12 September 2026, after the first two migrations of Executive OS both stalled on it. The old rule allowed badges for the four statuses and forbade them for anything else without offering a replacement, so 25 findings sat unfixable and the audit could never reach zero on an app that followed the instructions exactly. A badge given no status is now neutral: sunken background, muted text at the step 04 measures against a quiet surface. Neutral carries categories, roles, plans and types, and also the states the four colours have no room for, which the agent migration found in an outcome set of executed, failed, rejected and pending. Rejected is not danger. The same change makes an unrecognised status render neutral instead of falling back to info, because painting an unknown state blue asserts something untrue about it. Colour on a badge still means status and nothing else, and a count stops being a badge at all, since a number beside a label is text.

**4.10, text on a status tint.** Added on 12 September 2026, from the same migration. 04 sanctions a status tint as a row background and the migration guide recommends it, but muted text at the 500 step measures 4.35:1 on the danger tint and 4.37:1 on the info tint in light, under the floor, with success and warning only scraping past at 4.55 and 4.59. Nothing caught it because `design:check` measured text against page, surface and sunken but never against a tint. No new colour was needed: `text-muted-on-sunken` clears every tint in every design at 5.73:1 or better, so 04 now reads that role as muted text on any quiet surface rather than on sunken alone. Status text on its own tint was never at risk, at 4.79:1 or better, but is measured now too. Both pairs are checked for every status in both registers, in the tool and in the package's copy of the gate, with a test comparing the two lists by name so they cannot drift apart again.

## 01 Principles

| # | Rule | Enforced by | Decision |
|---|---|---|---|
| 1.1 | Content decides the layout; no three equal boxes, no hero, no icon grid | people | keep, it is the anti-generic sentence |
| 1.2 | One accent, at most once per row; never on backgrounds, headings, body | generator + code | keep |
| 1.3 | Structure shared, identity varies in seven things | generator | keep |
| 1.4 | Contrast measured at 4.5:1 both registers | code | keep, one line |
| 1.5 | Spacing from one eight-step scale by density | code + people | keep |
| 1.6 | Copy is concrete, no buzzwords | generator | keep |

## 02 Shells

| # | Rule | Enforced by | Decision |
|---|---|---|---|
| 2.1 | Two shells; a person chooses in the brief | code | keep, one line |
| 2.2 | Sidebar 16rem, sticky, sunken in light, surface in dark | people | cut from prompt |
| 2.3 | Brand block contents and heights | people | cut from prompt |
| 2.4 | Nav groups: two levels, eight items, active state by motif | people | keep the motif sentence, cut the rest from prompt |
| 2.5 | User menu pinned bottom, avatar, name, settings, sign out | people | cut from prompt |
| 2.6 | Top nav header 3.5rem, max width 80rem | people | cut from prompt |
| 2.7 | Page header region: eyebrow, title, one line, actions, at most one primary | people | keep one line, so the generator knows there is an eyebrow region to write for |
| 2.8 | Content grid: 12 columns, which spans, three equal only when three peers | people | keep the three-equal sentence, cut the span list from prompt |
| 2.9 | Sign-in two panels, 40/60, copy fields | generator + code | keep the copy part, cut the panel geometry from prompt |
| 2.10 | No third shell; sidebar holds no content; breadcrumbs at three levels; no footer | people | cut from prompt |
| 2.11 | New: page titles go through `x-ds::page-header` | people | add, per 6.5 above |

## 03 Type

| # | Rule | Enforced by | Decision |
|---|---|---|---|
| 3.1 | The scale table, 12 to 36px, weights, Tailwind classes | people + code | keep the table, cut the surrounding sentences from prompt |
| 3.2 | Three weights only, no 700 | people | keep |
| 3.3 | Labels 0.04em; the eyebrow motif makes them uppercase mono | code | cut from prompt |
| 3.4 | Numbers tabular and right aligned | people | keep, one line |
| 3.5 | Second face roles: mono for codes, serif for titles only, display for titles and stats | generator | keep, this is how it picks a pairing |
| 3.6 | Condensed second face may set titles uppercase | code | done, removed with the condensed pairing |
| 3.7 | Fonts from Bunny, weights 400/500/600, fallback stacks | code | cut from prompt |
| 3.8 | The pairings table | generator + code | done, cut to five |
| 3.9 | Excluded faces by name | generator | keep, one line |
| 3.10 | One pairing, nothing under 12px, no italic in UI, prose 65ch | people | keep the 12px line, cut the rest from prompt |

## 04 Colour

| # | Rule | Enforced by | Decision |
|---|---|---|---|
| 4.1 | Roles table: page, surface, sunken, border, text, accent roles | code | keep, it is the vocabulary the reasons are written in |
| 4.2 | Three temperatures and their hex scales | code | keep the names and what each suits, cut the hex table from prompt |
| 4.3 | Accent hue 30 degrees from red and amber; saturation 45 to 85 | code + generator | keep |
| 4.4 | Where the accent may appear as fill, as text, never on backgrounds or headings | people | keep, short |
| 4.5 | Status colours fixed fleet-wide, with the measured shades | code | keep the table, cut the measurement paragraph from prompt |
| 4.6 | Status colours mark status; green is not primary | people | keep, one line |
| 4.7 | Contrast floor: 4.5 text, 3 meaning-carrying, none decorative | code | keep three lines |
| 4.8 | Measured results table and the two findings | code | cut from prompt |
| 4.9 | Input outlines at 1.5:1 against the 3:1 floor | you | decided above: the focus ring carries the meaning |
| 4.10 | Muted text on a status tint is under the floor | the resolver | decided above: the on-sunken step, and both tint pairs measured |
| 4.10 | Mapping onto Flux variables | code | cut from prompt |

## 05 Spacing, radius, density

| # | Rule | Enforced by | Decision |
|---|---|---|---|
| 5.1 | Eight-step scale, nothing between | people + code | keep, one line |
| 5.2 | Density table: gutter, card, row, control, prose | code | keep the table, cut the sentence list from prompt |
| 5.3 | Comfortable for occasional or public, compact for trained staff all day | generator | keep, it decides density |
| 5.4 | Radius steps table and which utilities follow | code | keep the three names and what each suits, cut the pixel table from prompt |
| 5.5 | Radius never varies within a project | people | cut from prompt |
| 5.6 | Borders one pixel, shadows only on overlays, no touch target under 36px | people | cut from prompt |

## 06 Components

| # | Rule | Enforced by | Decision |
|---|---|---|---|
| 6.1 | Every Flux entry, free and Pro, about 45 lines | people | cut from prompt. The single biggest saving, and the generator never builds a screen |
| 6.2 | The four custom components | people + package | cut from prompt |
| 6.3 | Coverage table | people | cut from prompt |
| 6.4 | Flux badge colours are not the standard's status colours | you | decided above: the package overrides them |
| 6.5 | Flux headings do not take the display face | you | decided above: titles go through the page header |
| 6.6 | Badges are status only, with no rule for categories | you | decided above: no status means neutral |

## 07 Motifs

| # | Rule | Enforced by | Decision |
|---|---|---|---|
| 7.1 | One motif per project, never two | code | keep |
| 7.2 | Accent bar: what it does, what it forbids, what it suits | generator + people | keep name and suits, cut the does and forbids from prompt |
| 7.3 | Hairline | generator + people | same. Leave "suits compact and sharp" as guidance rather than a hard rule: the model already reasons from it, and hard-wiring motif to radius makes designs more uniform, not better |
| 7.4 | Eyebrow | generator + people | same as 7.2 |
| 7.5 | Filled stat | generator + people | same as 7.2 |
| 7.6 | Defaults: accent bar for sidebar, filled stat for top nav | generator | keep |

## 08 Motion and icons

| # | Rule | Enforced by | Decision |
|---|---|---|---|
| 8.1 | Durations table; hover changes colour, not opacity | people | cut from prompt |
| 8.2 | The not-used list: scroll fades, cursor effects, pulsing, glow, carousels, count-ups | people | cut from prompt |
| 8.3 | Reduced motion honoured | people | cut from prompt |
| 8.4 | Heroicons only, the size table, no emoji | people + code | keep the one Heroicons line, cut the sizes from prompt |
| 8.5 | Imagery: one hero on sign-in, nothing else illustrated | people | cut from prompt |

## 09 Decisions and JSON

| # | Rule | Enforced by | Decision |
|---|---|---|---|
| 9.1 | Brief fields | code | keep |
| 9.2 | The seven decisions table with how to reason | generator | keep, this is the core of the prompt |
| 9.3 | Constraints: no other, two briefs differ in three, brand colour wins | generator + code | keep |
| 9.4 | JSON example and adapter behaviour | code | cut from prompt |
| 9.5 | The export pack | people | cut from prompt |

## Pairings, 03 — decided 11 September 2026

Cut to five. The ids stay as they were, because every design file and exported pack stores one, so a retired pairing leaves a gap rather than renumbering.

| # | Pairing | Outcome |
|---|---|---|
| P1 | IBM Plex Sans + Plex Mono | kept |
| P2 | Source Sans 3 + Source Serif 4 | kept |
| P3 | Manrope + JetBrains Mono | kept |
| P4 | Public Sans + Fraunces | retired, overlapped 2 and 7. Northline moved to 7 |
| P5 | Archivo + Archivo Narrow | retired, only condensed face. The tool's own design moved to 1 |
| P6 | Figtree + Geist Mono | retired, softer 3 |
| P7 | Hanken Grotesk + Newsreader | kept |
| P8 | Red Hat Text + Display | retired, never chosen while 1 exists |
| P9 | Albert Sans + Bricolage Grotesque | retired, the fleet is admin tools |
| P10 | Atkinson Hyperlegible Next + Spline Sans Mono | kept |

## What applying this does

1. A `<!-- prompt-end -->` marker goes into each of 01 to 09. `Standard::prompt()` stops at it; the Standard page and the exported `STANDARD.md` keep reading the whole file.
2. The two rules added by 6.5 go into 02 and 06.
3. The badge override from 6.4 goes into the package's apply block, with a test.
4. The ExecutiveOS brief runs again on Fable, and the seven decisions and their reasons go side by side against the saved run. Any reason that got weaker means that rule comes back above the marker.

Nothing that the schema, the resolver or the rules pass enforces can change as a result of any of this. Expected prompt after the cut: six to eight thousand tokens, down from about seventeen.
