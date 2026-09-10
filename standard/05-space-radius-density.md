# 05. Spacing, radius, density

## The spacing scale

Eight steps on a 4px base. Every gap, padding and gutter on every screen is one of these.

4, 8, 12, 16, 24, 32, 48, 64

Tailwind: `1 2 3 4 6 8 12 16`. Nothing in between. If a layout seems to need 20 or 40, the layout is wrong, not the scale.

## Density

Two densities. The generator picks one from the brief: comfortable for apps used occasionally or by the public, compact for tools used all day by trained staff. Density sets these values and nothing else:

| Measure | Comfortable | Compact |
|---|---|---|
| Page gutter | 32 | 24 |
| Section gap between regions | 32 | 24 |
| Card and panel padding | 24 | 16 |
| Stat tile padding | 24 | 16 |
| Table row height | 48 | 40 |
| Table cell horizontal padding | 16 | 12 |
| Control height (inputs, buttons) | 40 | 36 |
| Gap between a label and its control | 8 | 6 |
| Gap between form fields | 24 | 16 |
| Nav item height | 40 | 36 |
| Brand block height | 56 | 48 |
| Prose and input text size | 16 | 14 |

UI text stays 14px in both. Density is spacing, not type.

## Radius

Three steps. The generator picks one per project. Each step sets Tailwind's radius variables so every `rounded-*` in Flux follows at once.

| Variable | Sharp | Soft | Round |
|---|---|---|---|
| `--radius-sm` | 2 | 4 | 6 |
| `--radius-md` | 3 | 6 | 8 |
| `--radius-lg` | 4 | 8 | 12 |
| `--radius-xl` | 6 | 12 | 16 |
| `--radius-2xl` | 8 | 16 | 24 |

Values in pixels. `rounded-full` is unaffected and stays a pill or circle. Flux uses `rounded-lg` for buttons, inputs and cards and `rounded-md` for smaller controls, so the step is felt everywhere without touching a component.

Sharp suits dense data and technical work. Soft is the default when the brief gives no reason. Round suits consumer-facing and people-facing apps. Radius never varies within a project: a sharp table beside a round card is the inconsistency the principles call a bug.

## Rules

- Borders are one pixel. Two pixel borders exist only for the accent bar motif and for focus rings.
- Shadows are not used for elevation on surfaces. Cards are marked by border or by hairline division. The only shadows are on overlays: dropdowns, modals and toasts, at one fixed value, `shadow-lg`.
- Max content width in the top nav shell is 80rem. The sidebar shell has none.
- Touch targets are never under 36px in either density.
