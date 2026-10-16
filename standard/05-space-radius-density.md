# 05. Spacing, radius, density

## The spacing scale

Eight steps on a 4px base, and nothing in between: 4, 8, 12, 16, 24, 32, 48, 64. If a layout seems to need 20 or 40, the layout is wrong, not the scale.

## Density

Two densities. Comfortable is for apps used occasionally or by the public. Compact is for tools used all day by trained staff, and only then. Density sets spacing, never type: UI text stays 14px in both.

| Measure | Comfortable | Compact |
|---|---|---|
| Page gutter and section gap | 32 | 24 |
| Card, panel and stat tile padding | 24 | 16 |
| Table row height | 44 | 44 |
| Control height | 40 | 36 |
| Gap between form fields | 24 | 16 |
| Prose and input text size | 16 | 14 |

Table rows are 44px in both densities. A row holds one line of 14px text and, at most, a 40px control, so the height is set by what sits in it rather than by the density; compact tightens the cell padding instead.

## Radius

Three steps. **Sharp** suits dense data and technical work. **Soft** is the default when the brief gives no reason. **Round** suits consumer-facing and people-facing apps. Radius never varies within a project: a sharp table beside a round card is the inconsistency the principles call a bug.

<!-- prompt-end -->

## The density table in full

| Measure | Comfortable | Compact |
|---|---|---|
| Page gutter | 32 | 24 |
| Section gap between regions | 32 | 24 |
| Card and panel padding | 24 | 16 |
| Stat tile padding | 24 | 16 |
| Table row height | 44 | 44 |
| Table cell horizontal padding | 16 | 12 |
| Control height (inputs, buttons) | 40 | 36 |
| Gap between a label and its control | 8 | 6 |
| Gap between form fields | 24 | 16 |
| Nav item height | 40 | 36 |
| Brand block height | 56 | 48 |
| Prose and input text size | 16 | 14 |

## The radius steps

Each step sets Tailwind's radius variables so every `rounded-*` in Flux follows at once.

| Variable | Sharp | Soft | Round |
|---|---|---|---|
| `--radius-sm` | 2 | 4 | 6 |
| `--radius-md` | 3 | 6 | 8 |
| `--radius-lg` | 4 | 8 | 12 |
| `--radius-xl` | 6 | 12 | 16 |
| `--radius-2xl` | 8 | 16 | 24 |

Values in pixels. `rounded-full` is unaffected and stays a pill or circle. Flux uses `rounded-lg` for buttons, inputs and cards and `rounded-md` for smaller controls, so the step is felt everywhere without touching a component.

## Rules

- Borders are one pixel. Two pixel borders exist only for the accent bar motif and for focus rings.
- Shadows are not used for elevation on surfaces. Cards are marked by border or by hairline division. The only shadows are on overlays: dropdowns, modals and toasts, at one fixed value, `shadow-lg`.
- Max content width in the top nav shell is 80rem. The sidebar shell has none.
- Touch targets are never under 36px in either density. Flux's only button size that clears this is `base`, at 40px: `sm` is 32px and `xs` is 24px, so neither is used. An inline action in a dense row is `base` like any other button, or it is not a button.
