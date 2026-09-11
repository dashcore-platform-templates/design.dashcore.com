# Cut sheet for the standard

One line per rule. Mark the last column keep, cut or change (with a note), and I apply the marks. Nothing here is edited until you say so.

The **Enforced by** column is the important one for the worry about output quality:

- **code** means the schema, the resolver, the rules pass or the adapter already enforces it. Cutting the prose changes nothing the generator can produce; it only changes what a person reads.
- **generator** means the model reads this sentence to make one of the seven decisions. These are the ones that affect output. Cut carefully, and we A/B the ExecutiveOS brief afterwards.
- **people** means only a human or an agent building a screen acts on it. It costs prompt tokens for no generation benefit; the generator does not build screens. Safe to cut from the prompt, even if kept in the doc for humans.

Prompt cost today: files 01 to 09 are about 17,000 tokens per generation. Rules marked people are roughly half of that.

Recommendations in the last column are mine; the decision is yours.

## 01 Principles

| # | Rule | Enforced by | Recommend |
|---|---|---|---|
| 1.1 | Content decides the layout; no three equal boxes, no hero, no icon grid | people | keep, it is the anti-generic sentence |
| 1.2 | One accent, at most once per row; never on backgrounds, headings, body | generator + code | keep |
| 1.3 | Structure shared, identity varies in seven things | generator | keep |
| 1.4 | Contrast measured at 4.5:1 both registers | code | keep, one line |
| 1.5 | Spacing from one eight-step scale by density | code (adapter) + people | keep |
| 1.6 | Copy is concrete, no buzzwords | generator | keep |

## 02 Shells

| # | Rule | Enforced by | Recommend |
|---|---|---|---|
| 2.1 | Two shells; a person chooses in the brief | code (schema) | keep, one line |
| 2.2 | Sidebar 16rem, sticky, sunken in light, surface in dark | people | cut from prompt, keep in doc |
| 2.3 | Brand block contents and heights | people | cut from prompt |
| 2.4 | Nav groups: two levels, eight items, active state by motif | people | keep the motif sentence only |
| 2.5 | User menu pinned bottom, avatar, name, settings, sign out | people | cut from prompt |
| 2.6 | Top nav header 3.5rem, max width 80rem | people | cut from prompt |
| 2.7 | Page header region: eyebrow, title, one line, actions, at most one primary | people | keep, it defines the component |
| 2.8 | Content grid rules: 12 columns, which spans, three equal only when three peers | people | keep 1.1's spirit here, cut the span list |
| 2.9 | Sign-in two panels, 40/60, copy fields | generator (copy) + code | keep the copy part |
| 2.10 | No third shell; sidebar holds no content; breadcrumbs at three levels; no footer | people | cut from prompt |

## 03 Type

| # | Rule | Enforced by | Recommend |
|---|---|---|---|
| 3.1 | The scale table (12 to 36px, weights, Tailwind classes) | people + code (adapter) | keep the table, cut the sentences around it |
| 3.2 | Three weights only, no 700 | people | keep |
| 3.3 | Labels 0.04em; eyebrow motif makes them uppercase mono | code | cut from prompt |
| 3.4 | Numbers tabular and right aligned | people | keep, one line |
| 3.5 | Second face roles: mono for codes, serif for titles only, display for titles and stats | generator | keep, this is how it picks a pairing |
| 3.6 | Condensed second face may set titles uppercase | code | cut if pairing 5 goes |
| 3.7 | Fonts from Bunny, weights 400/500/600, fallback stacks | code (adapter) | cut from prompt |
| 3.8 | The pairings table | generator + code | keep, trimmed to 4 to 6 |
| 3.9 | Excluded faces by name | generator | keep, one line |
| 3.10 | One pairing, nothing under 12px, no italic in UI, prose 65ch | people | keep the 12px line, cut the rest from prompt |

## 04 Colour

| # | Rule | Enforced by | Recommend |
|---|---|---|---|
| 4.1 | Roles table (page, surface, sunken, border, text, accent roles) | code (resolver) | keep, it is the vocabulary |
| 4.2 | Three temperatures and their hex scales | code | keep the names and what each suits; cut the hex table from the prompt |
| 4.3 | Accent hue 30 degrees from red and amber; saturation 45 to 85 | code (rules pass) + generator | keep |
| 4.4 | Where the accent may appear as fill, as text, never on backgrounds or headings | people | keep, short |
| 4.5 | Status colours fixed fleet-wide, with the measured shades | code | keep the table, cut the measurement paragraph from the prompt |
| 4.6 | Status colours mark status; green is not primary | people | keep, one line |
| 4.7 | Contrast floor 4.5 text, 3 meaning-carrying, none decorative | code | keep three lines |
| 4.8 | Measured results table and the two findings (500 fails on sunken, dark muted 400) | code | cut from prompt, keep in doc |
| 4.9 | Open for the cut: input outlines at 1.5:1 vs the 3:1 floor | you | decide: relax to "focus ring carries meaning" (my pick) or move outlines to the 500 step |
| 4.10 | Mapping onto Flux variables | code (adapter) | cut from prompt, keep in doc |

## 05 Spacing, radius, density

| # | Rule | Enforced by | Recommend |
|---|---|---|---|
| 5.1 | Eight-step scale, nothing between | people + adapter | keep, one line |
| 5.2 | Density table (gutter, card, row, control, prose) | code | keep the table, cut the sentence list |
| 5.3 | Comfortable for occasional or public, compact for trained staff all day | generator | keep, it decides density |
| 5.4 | Radius steps table and which utilities follow | code | keep the three names and what each suits; cut the pixel table from the prompt |
| 5.5 | Radius never varies within a project | people | cut from prompt |
| 5.6 | Borders one pixel, shadows only on overlays, no touch target under 36px | people | cut from prompt |

## 06 Components

| # | Rule | Enforced by | Recommend |
|---|---|---|---|
| 6.1 | Every entry, free and Pro, about 45 lines | people (agent building screens) | cut the whole section from the prompt; it is the biggest saving and the generator never builds a screen. Keep in full in the doc and in STANDARD.md for agents |
| 6.2 | Four custom components | people + package | same |
| 6.3 | Coverage table | people | same |
| 6.4 | Flux badge colours are not the standard's status colours | you | decide: package overrides Flux badge colours (my pick) or add x-ds::badge |
| 6.5 | Flux headings do not take the display face; page header does | you | decide: rule that titles use x-ds::page-header (my pick) |

## 07 Motifs

| # | Rule | Enforced by | Recommend |
|---|---|---|---|
| 7.1 | One motif per project, never two | code (schema) | keep |
| 7.2 | Accent bar: what it does, forbids, suits | generator + people | keep the suits line, shorten the rest |
| 7.3 | Hairline | generator + people | same; add "suits compact and sharp" was already there and the generator ignored it once, so state it as a rule not a suggestion if you care |
| 7.4 | Eyebrow | generator + people | same |
| 7.5 | Filled stat | generator + people | same |
| 7.6 | Defaults: accent bar for sidebar, filled stat for top nav | generator | keep |

## 08 Motion and icons

| # | Rule | Enforced by | Recommend |
|---|---|---|---|
| 8.1 | Durations table, hover changes colour not opacity | people | cut from prompt |
| 8.2 | The not-used list: scroll fades, cursor effects, pulsing, glow, carousels, count-ups | people | cut from prompt |
| 8.3 | Reduced motion honoured | people | cut from prompt |
| 8.4 | Heroicons only, the size table, no emoji | people + adapter | cut from prompt, keep the one Heroicons line |
| 8.5 | Imagery: one hero on sign-in, nothing else illustrated | people | cut from prompt |

## 09 Decisions and JSON

| # | Rule | Enforced by | Recommend |
|---|---|---|---|
| 9.1 | Brief fields | code | keep |
| 9.2 | Seven decisions table with "how to reason" | generator | keep, this is the core of the prompt |
| 9.3 | Constraints: no other, two briefs differ in three, brand colour wins | generator + code | keep |
| 9.4 | JSON example and adapter behaviour | code | cut from prompt, keep in doc |
| 9.5 | The export pack | people | cut from prompt |

## Pairings, 03 — decided 11 September 2026

Cut to five. The ids stay as they were, because every design file and exported pack stores one, so a retired pairing leaves a gap rather than renumbering.

| # | Pairing | Outcome |
|---|---|---|
| P1 | IBM Plex Sans + Plex Mono | kept |
| P2 | Source Sans 3 + Source Serif 4 | kept |
| P3 | Manrope + JetBrains Mono | kept |
| P4 | Public Sans + Fraunces | retired, overlapped 2 and 7. Northline moved to 7 |
| P5 | Archivo + Archivo Narrow | retired, only condensed face. The tool own design moved to 1 |
| P6 | Figtree + Geist Mono | retired, softer 3 |
| P7 | Hanken Grotesk + Newsreader | kept |
| P8 | Red Hat Text + Display | retired, never chosen while 1 exists |
| P9 | Albert Sans + Bricolage Grotesque | retired, the fleet is admin tools |
| P10 | Atkinson Hyperlegible Next + Spline Sans Mono | kept |

## What happens after your marks

1. I apply the cuts to the markdown. Rules marked "cut from prompt, keep in doc" move below a `<!-- prompt-end -->` marker in each file so the Standard page shows them and the generator does not read them.
2. `Pairings.php` is trimmed to match; sample designs on dropped pairings are repointed.
3. The ExecutiveOS brief runs again on Fable, dry, and I post the before and after decisions side by side on DAS-2016. If any decision or reason got worse, we put that rule back and run once more.
4. Estimated prompt after the cut: 6,000 to 8,000 tokens, down from 17,000, with every generator-facing rule intact.
