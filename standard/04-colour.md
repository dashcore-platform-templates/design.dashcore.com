# 04. Colour

Colour is expressed as roles. Components read roles, never raw values. The resolver turns the seven decisions into a hex value for each role per register, and the adapter maps them onto Flux's variables so every Flux component follows.

## Roles

| Role | Job |
|---|---|
| `page` | Outermost background |
| `surface` | Cards, panels, table bodies, header bar |
| `sunken` | Sidebar in light, table heads, grouped inputs |
| `border` | Every divider and outline |
| `border-strong` | Input borders |
| `text-strong` | Titles, table first column, values |
| `text` | Body, UI, nav |
| `text-muted` | Descriptions, labels, secondary cells |
| `accent` | Primary button fill, active indicator, selection |
| `accent-hover` | The above on hover |
| `accent-content` | Accent used as text or icon |
| `accent-soft` | Accent as a background tint |
| `on-accent` | Text on an accent fill |

## The three neutral temperatures

The generator picks one per project. **Pure** is a true grey and suits technical and financial work. **Cool** leans blue and suits software and data. **Warm** leans brown and suits people-facing work, hospitality and health. The scale itself is fixed and the resolver fills it in.

## Accent rules

- One accent. The generator chooses a hue and a saturation; the resolver builds the shades.
- The accent hue must sit at least 30 degrees away from red (0) and amber (40) so it is never read as danger or warning. Green near 140 is allowed but discouraged, since success then loses its meaning.
- Saturation is between 45 and 85 percent in HSL. Under that it reads as a grey; over it, it vibrates on white.
- As a fill, the accent appears on: the primary button, the active nav indicator, the checked state of switches, checkboxes and radios, the selected tab, and progress fills. Nowhere else.
- As text or icon, only `accent-content` is used, so it always passes contrast.
- Never on page or surface backgrounds. `accent-soft` may tint one region per view when the filled-stat motif is chosen, and is otherwise used only behind selected rows and active list items.
- Headings and body text never take the accent.

## Status colours, fixed fleet-wide

These never change per project.

| Status | Light text | Dark text | Fill |
|---|---|---|---|
| Success | green 700 | green 400 | green 700 |
| Warning | amber 700 | amber 400 | amber 700 |
| Danger | red 700 | red 400 | red 600 |
| Info | blue 700 | blue 400 | blue 700 |

Status colours mark status. A green button is not a primary button; a red one is a destructive one. Success, warning and danger appear only on badges, callouts, toasts, form errors, and deltas.

## The contrast floor

- Body text, muted text, labels, and text on filled controls: 4.5:1 or better against the surface they are actually on.
- Icons and borders that are the only signal a control exists: 3:1 or better.
- Decorative borders and dividers, and the resting outline of an input that also has a label and a focus ring: no floor. They are allowed to be quiet.
- Both registers are checked separately. Passing in light says nothing about dark.

<!-- prompt-end -->

## The temperature scales

| Step | Pure | Cool | Warm |
|---|---|---|---|
| 50 | #fafafa | #f8fafc | #fafaf9 |
| 100 | #f5f5f5 | #f1f5f9 | #f5f5f4 |
| 200 | #e5e5e5 | #e2e8f0 | #e7e5e4 |
| 300 | #d4d4d4 | #cbd5e1 | #d6d3d1 |
| 400 | #a3a3a3 | #94a3b8 | #a8a29e |
| 500 | #737373 | #64748b | #78716c |
| 600 | #525252 | #475569 | #57534e |
| 700 | #404040 | #334155 | #44403c |
| 800 | #262626 | #1e293b | #292524 |
| 900 | #171717 | #0f172a | #1c1917 |
| 950 | #0a0a0a | #020617 | #0c0a09 |

These are Tailwind's neutral, slate and stone scales. They are used because they are tuned and because the temperature is the decision, not the individual stops.

## Which step fills which role

| Role | Light | Dark |
|---|---|---|
| `page` | white | 900 |
| `surface` | white | 800 |
| `sunken` | 50 | 950 |
| `border` | 200 | 700 |
| `border-strong` | 300 | 600 |
| `text-strong` | 950 | 50 |
| `text` | 800 | 200 |
| `text-muted` | 500 on page or surface, 600 on sunken or a status tint | 400 |

`accent-hover` moves the accent's lightness six points. `accent-content` moves it until it reaches 4.5:1 on the page. `accent-soft` is the accent at 10 percent alpha in light and 16 in dark. `on-accent` is white or near-black, whichever passes on the fill; if neither does, the fill itself moves until one does. In dark the accent is lightened until it reaches 3:1 on the dark page before any of that.

## Status colours, measured

| Status | Light text | Dark text | Fill | Soft light | Soft dark |
|---|---|---|---|---|---|
| Success | #15803d | #4ade80 | #15803d | green 50 | green 950 |
| Warning | #b45309 | #fbbf24 | #b45309 | amber 50 | amber 950 |
| Danger | #b91c1c | #f87171 | #dc2626 | red 50 | red 950 |
| Info | #1d4ed8 | #60a5fa | #1d4ed8 | blue 50 | blue 950 |

Green 600 fails as text on white at 3.3:1, and amber 600 at 3.19:1, which is why the text shade is 700. In dark, every 700 shade fails on neutral 800, which is why the text shade is 400. Fills at 700 pass with white text at 5.02:1 or better. Red 600 is kept as the danger fill because it passes at 4.83:1 and is the shade people recognise.

Flux's own badge colours come from Tailwind's palette rather than these, so the adapter overrides them. Otherwise a kit's badges drift from the standard the moment anyone uses one.

## What the floor measures out at

| Pair | Light | Dark |
|---|---|---|
| text-strong on page | 19.8 to 20.2 | 16.7 to 17.2 |
| text on page | 14.6 to 15.2 | 13.9 to 14.5 |
| text on surface | same | 11.9 to 12.1 |
| text-muted on page | 4.74 to 4.80 | 6.9 to 7.1 |
| text-muted on surface | same | 5.7 to 6.0 |
| text-muted on sunken | 4.54 to 4.59 using 500; 6.9 to 7.2 using 600 | n/a, sunken is darker than page |
| text-muted on a status tint | 4.35 to 4.59 using 500, which fails on danger and info; 5.7 or better using 600 | 5.7 to 6.4 |
| status text on its own tint | 4.79 to 6.16 | 5.7 to 9.0 |

Three things fall out of the numbers. Muted text at 500 only just passes on white and fails on neutral 100, so muted text on any sunken surface uses 600 in light. Flux's default of neutral 500 for muted text in dark fails at 3.07 to 3.19:1 on neutral 800, so the standard sets dark muted text to 400 and the adapter overrides Flux there.

And a status tint is a quiet surface like any other. Muted text at 500 measures 4.35:1 on the danger tint and 4.37:1 on the info tint in light, so secondary text on a tinted row takes the same 600 step as on sunken. The role carries the name `text-muted-on-sunken` from where it was first needed; read it as muted text on any quiet surface that is not page or surface. A badge is safe as it stands, because status text on its own tint never drops below 4.79:1. Both pairs are measured for every status, in both registers, so neither can go quietly wrong again.

The resting outline of an input measures about 1.5:1 at the 300 step. That is deliberate, decided on 11 September 2026: the two pixel accent focus ring carries the meaning, and the control already announces itself by shape, label and placeholder. The contrast command reports the pair as information rather than a failure.

## Mapping onto Flux

The adapter writes these into `@theme`:

- `--color-zinc-50` through `--color-zinc-950` take the chosen temperature's scale. This is what rethemes every Flux component.
- `--color-accent` takes `accent`, `--color-accent-content` takes `accent-content`, `--color-accent-foreground` takes `on-accent`. Dark values are set on `.dark`.
- Muted text in dark is corrected with `.dark { --color-zinc-500: <neutral 400 value>; }`.
- Flux's badge colours are overridden to the status roles above.

The semantic roles themselves are also emitted as `--ds-page`, `--ds-surface`, `--ds-text-muted` and so on, for the four custom components in 06 and for any non-Flux CSS a project writes.
