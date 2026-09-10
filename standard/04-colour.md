# 04. Colour

Colour is expressed as roles. Components read roles, never raw values. The tokens layer of the JSON resolves each role to a hex value per register and then maps the roles onto Flux's variables so every Flux component follows.

## Roles

| Role | Job | Light | Dark |
|---|---|---|---|
| `page` | Outermost background | white | neutral 900 |
| `surface` | Cards, panels, table bodies, header bar | white | neutral 800 |
| `sunken` | Sidebar (light), table heads, grouped inputs | neutral 50 | neutral 950 |
| `border` | Every divider and outline | neutral 200 | neutral 700 |
| `border-strong` | Input borders, focus-adjacent outlines | neutral 300 | neutral 600 |
| `text-strong` | Titles, table first column, values | neutral 950 | neutral 50 |
| `text` | Body, UI, nav | neutral 800 | neutral 200 |
| `text-muted` | Descriptions, labels, secondary cells | neutral 500 on page or surface; neutral 600 on sunken | neutral 400 |
| `accent` | Primary button fill, active indicator, selection | generated | generated, lightened if needed |
| `accent-hover` | The above on hover | accent darkened one step | accent lightened one step |
| `accent-content` | Accent used as text or icon on `page` | accent, darkened until 4.5:1 on page | accent, lightened until 4.5:1 on page |
| `accent-soft` | Accent as a background tint | accent at 10 percent over surface | accent at 16 percent over surface |
| `on-accent` | Text on an accent fill | white or neutral 950, whichever passes 4.5:1 | same |

"Neutral" means the chosen temperature's scale, below.

## The three neutral temperatures

The generator picks one per project. The scale then fills Flux's eleven zinc slots so every component takes the temperature.

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

These are Tailwind's neutral, slate and stone scales. They are used because they are tuned and because the temperature is the decision, not the individual stops. Tuning the stops later is allowed and changes nothing else in the standard. Pure suits technical and financial work, cool suits software and data, warm suits people-facing, hospitality and health.

## Accent rules

- One accent. It is chosen by the generator as a hue and a saturation; the resolver builds the shades.
- The accent hue must sit at least 30 degrees away from red (0) and amber (40) so it is never read as danger or warning. Green near 140 is allowed but discouraged, since success then loses its meaning.
- Saturation is between 45 and 85 percent in HSL. Under that it reads as a grey; over it, it vibrates on white.
- As a fill, the accent appears on: the primary button, the active nav indicator, the checked state of switches, checkboxes and radios, the selected tab, and progress fills. Nowhere else.
- As text or icon, only `accent-content` is used, so it always passes contrast.
- Never on page or surface backgrounds. `accent-soft` may tint one region per view when the filled-stat motif is chosen, and is otherwise used only behind selected rows and active list items.
- Headings and body text never take the accent.

## Status colours, fixed fleet-wide

These never change per project. Text shades are the ones that pass 4.5:1 in their register, measured. Fills carry white text.

| Status | Light text | Dark text | Fill (both registers) | Soft light | Soft dark |
|---|---|---|---|---|---|
| Success | green 700 #15803d | green 400 #4ade80 | green 700 #15803d | green 50 | green 950 |
| Warning | amber 700 #b45309 | amber 400 #fbbf24 | amber 700 #b45309 | amber 50 | amber 950 |
| Danger | red 700 #b91c1c | red 400 #f87171 | red 600 #dc2626 | red 50 | red 950 |
| Info | blue 700 #1d4ed8 | blue 400 #60a5fa | blue 700 #1d4ed8 | blue 50 | blue 950 |

Measured: green 600 fails as text on white at 3.3:1, and amber 600 at 3.19:1, which is why the text shade is 700. In dark, every 700 shade fails on neutral 800, which is why the text shade is 400. Fills at 700 pass with white text (5.02:1 or better). Red 600 is kept as the danger fill because it passes (4.83:1) and is the shade people recognise.

Status colours mark status. A green button is not a primary button; a red one is a destructive one. Success, warning and danger appear only on badges, callouts, toasts, form errors, and deltas.

## The contrast floor

- Body text, muted text, labels, and text on filled controls: 4.5:1 or better against the surface they are actually on.
- Icons and borders that carry meaning (a status icon, an input outline): 3:1 or better.
- Decorative borders and dividers: no floor. They are allowed to be quiet.
- Both registers are checked separately. Passing in light says nothing about dark.

Measured on the roles above, all three temperatures:

| Pair | Light | Dark |
|---|---|---|
| text-strong on page | 19.8 to 20.2 | 16.7 to 17.2 |
| text on page | 14.6 to 15.2 | 13.9 to 14.5 |
| text on surface | same | 11.9 to 12.1 |
| text-muted on page | 4.74 to 4.80 | 6.9 to 7.1 |
| text-muted on surface | same | 5.7 to 6.0 |
| text-muted on sunken | 4.54 to 4.59 using 500; 6.9 to 7.2 using 600 | n/a, sunken is darker than page |

Two things fall out of the numbers. Muted text at 500 only just passes on white and fails on neutral 100 (4.35:1), so muted text on any sunken surface uses 600 in light. And Flux's default of neutral 500 for muted text in dark fails at 3.07 to 3.19:1 on neutral 800, so the standard sets dark muted text to 400 and the adapter overrides Flux there.

## Mapping onto Flux

The tokens layer writes these into `@theme`:

- `--color-zinc-50` through `--color-zinc-950` take the chosen temperature's scale. This is what rethemes every Flux component.
- `--color-accent` takes `accent`, `--color-accent-content` takes `accent-content`, `--color-accent-foreground` takes `on-accent`. Dark values are set on `.dark`.
- Muted text in dark is corrected with one rule: `.dark { --color-zinc-500: <neutral 400 value>; }` scoped to text utilities, or by the adapter rewriting Flux's muted text class. Which is cleaner is a Phase 3 finding.

The semantic roles themselves are also emitted as `--ds-page`, `--ds-surface`, `--ds-text-muted` and so on, for the three custom components in 06 and for any non-Flux CSS a project writes.
