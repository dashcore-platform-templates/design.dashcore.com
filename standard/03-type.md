# 03. Type

## The scale

Flux sets nearly all interface text at 14px, so the scale is built around that rather than against it. Sizes in pixels; line height as a ratio.

| Role | Size | Line height | Weight | Tailwind |
|---|---|---|---|---|
| Label, eyebrow, table head | 12 | 1.33 | 500 | `text-xs font-medium` |
| UI text, table cells, nav, controls | 14 | 1.5 | 400 | `text-sm` |
| UI emphasis | 14 | 1.5 | 500 | `text-sm font-medium` |
| Prose and form inputs, comfortable density | 16 | 1.5 | 400 | `text-base` |
| Section heading, card title | 18 | 1.33 | 600 | `text-lg font-semibold` |
| Page title | 24 | 1.25 | 600 | `text-2xl font-semibold` |
| Stat value | 30 | 1 | 600 | `text-3xl font-semibold tabular-nums` |
| Sign-in headline | 36 | 1.1 | 600 | `text-4xl font-semibold` |

Three weights only: 400, 500, 600. Bold at 700 is not used; it is louder than any layout here needs. Nothing is set below 12px, ever, including in badges and table heads. Numbers in tables and stat tiles are always tabular and right aligned.

## Roles for the two faces

Every pairing is a body face and a second face. The body face carries all UI text, prose, forms, tables and nav. The second face is one of three kinds and has a fixed job:

- **A mono face** is used for labels and eyebrows when the eyebrow motif is chosen, for IDs, codes and reference numbers in tables, and optionally for stat values. Never for body or headings.
- **A serif face** is used for the page title and the sign-in headline only. Never for body, UI, or anything under 24px.
- **A display sans face** (a narrow, condensed or expressive cut) is used for the page title, stat values and the sign-in headline. Never for body or UI.

No face is set uppercase at title size.

## The approved pairings

The generator picks one, a person may change it, and there is nothing else. Approved means someone has looked at the pairing rendered, at every size, in both registers, on the Components screen. A face nobody has seen is not on this list.

Five is not a limit on how different two projects can be. The seven decisions give 360 structural combinations against fifteen apps in the fleet, before the accent is counted at all.

Marked with what kind of project each suits, so the generator has something to reason from besides mood.

| # | Body | Second face | Kind | Suits |
|---|---|---|---|---|
| 1 | IBM Plex Sans | IBM Plex Mono | mono | Operations, logistics, anything with IDs and codes in every row. Neutral and slightly technical. |
| 2 | Source Sans 3 | Source Serif 4 | serif | Clinics, law, accounting, professional services. Quiet and trustworthy. |
| 3 | Manrope | JetBrains Mono | mono | Modern product tools, dashboards with lots of numbers. Rounder and warmer than 1. |
| 7 | Hanken Grotesk | Newsreader | serif | Consulting, editorial, strategy. Newsreader at title size gives an authored feel. |
| 10 | Atkinson Hyperlegible Next | Spline Sans Mono | mono | Healthcare, older or mixed-ability users, anything where legibility beats character. |

Excluded by name, so the generator never reaches for them: Inter, Instrument Sans, Space Grotesk, Instrument Serif, Barlow Condensed, Bebas Neue, Space Mono, DM Sans. The first four are on the tells list or are the starter default. The last four are the retired OS Plus set.

<!-- prompt-end -->

## Ids, and what was retired

Ids are stored in every design file and exported pack, so they are stable: a retired pairing leaves a gap rather than renumbering the ones after it. All five faces exist on Bunny Fonts.

Retired on 11 September 2026, after the first three projects: Public Sans with Fraunces and Red Hat in two cuts both overlapped the serif and safe-corporate ground that 2 and 7 already hold; Figtree with Geist Mono was a softer 3; Archivo Narrow was the only condensed face and carried an uppercase-title rule that existed for it alone; Albert Sans with Bricolage Grotesque was the one with personality, and the fleet is admin tools. Adding a pairing back is an entry in `App\Design\Pairings` and a row above, once someone has looked at it.

The resolver still carries a condensed flag on the second face, in case such a pairing returns. No pairing sets it today, which is why the uppercase-title rule is gone.

## Labels

Labels are set with letter spacing of 0.04em. When the eyebrow motif is chosen (07) labels are uppercase with 0.08em spacing and set in the pairing's mono face if it has one.

## Loading

Fonts load through the starter kit's Vite font pipeline from Bunny Fonts, with weights 400, 500 and 600 only, and the second face at only the weights its role needs. Every face has a real fallback stack. Font files are not committed to the repo. A kit with no Vite fonts array loads them from a link in the head partial instead; the adapter handles both.

## Rules

- One pairing per project. Never a third face.
- No italic in UI. Italic is allowed only in prose the user wrote.
- Line length in prose is capped at 65 characters (`max-w-prose`).
