# 14. The Dashcore style

Dashcore's own apps take one fixed style, named `dashcore`. It is not a choice a generated design can make, and nothing below is an option for one.

<!-- prompt-end -->

## Who takes it

Every app in the fleet catalogue: `Catalogue::APPS` in `dashcore/bridge`, 24 apps besides this one. They are tools for the people who run Dashcore, and they should read as one product. A client project never takes it; a client gets its own seven decisions.

Everything in 01 to 13 holds for these apps exactly as for any other. The style fills in the seven decisions, and makes four exceptions to the rules a generated design is held to. The exceptions are listed below with the reason for each, and they belong to this style alone.

## The seven decisions

| Decision | Value |
|---|---|
| `accent` | `#4a4438` in light, `#d9cbb0` in dark. Exception 1. |
| `temperature` | Its own warm set, given as roles below rather than a scale. Exception 2. |
| `pairing` | Inter for the body, Fraunces as the serif second face. Exception 3. |
| `radius` | Sharp, capped at 4px. Exception 4. |
| `density` | `comfortable` |
| `motif` | `accent-bar` |
| `copy` | Each app writes its own. The sign-in words are about what that app is for, so they are the one decision the fleet does not share. |

Shell: `sidebar` for every app with a signed-in area. website is the public site and follows 13.

## The exceptions

**1. A near-ink accent.** 04 asks a generated accent for 45 to 85 percent saturation, because under that it reads as grey. Here that is the point: the accent marks the one primary action and the main nav's "you are here", and it does that by weight rather than hue. `#4a4438` sits below the band. The dark accent is stated rather than derived, because 04's derivation lightens toward a hue this accent barely has.

**2. Its own neutrals.** 04's warm temperature is Tailwind's stone, with a white page. This style keeps a warm, off-white page and a white surface, so panels lift off the page without a shadow. The roles are given directly, below, and are measured like any design's.

**3. Inter and Fraunces.** 03 excludes Inter by name, because a generated design that reaches for it reads as the starter kit, and retired its Fraunces pairing on 11 September 2026. Neither reason applies to a style a person chose and the whole fleet already wears. Fraunces follows 03's serif rule like any second face: page title and sign-in headline only, never under 24px, never at 700.

**4. A 4px ceiling.** Sharp in 05 still rounds to 8px at `2xl` and leaves `rounded-full` alone. This style caps every radius variable at 4px and clamps `rounded-full` too. A shape that really is a circle keeps it: a status dot, a spinner, a radio, a switch.

## Roles

| Role | Light | Dark |
|---|---|---|
| `page` | `#f7f4ef` | `#151412` |
| `surface` | `#ffffff` | `#1d1b18` |
| `sunken` | `#efebe2` | `#11100e` |
| `border` | `#d8d2c7` | `#2e2b26` |
| `border-strong` | `#b8af9f` | `#454039` |
| `text-strong` | `#1c1b19` | `#ece7dd` |
| `text` | `#1c1b19` | `#ece7dd` |
| `text-muted` | `#6b6560` | `#a39c90` |
| `text-muted-on-sunken` | `#6b6560` | `#aaa397` |
| `accent` | `#4a4438` | `#d9cbb0` |
| `accent-hover` | `#33301f` | `#e8dcc4` |
| `accent-content` | `#4a4438` | `#d9cbb0` |
| `accent-soft` | accent at 10% | accent at 16% |
| `on-accent` | `#ffffff` | `#1c1b19` |

`text` and `text-strong` are one ink. Weight and size separate a title from body here, not a second shade.

Status colours are the fleet's, from 04, unchanged.

## The Flux scale

Flux paints its components from the zinc scale, so the style stands its own neutrals in for zinc, each step the role nearest it:

| Step | 50 | 100 | 200 | 300 | 400 | 500 | 600 | 700 | 800 | 900 | 950 |
|---|---|---|---|---|---|---|---|---|---|---|---|
| Value | `#f7f4ef` | `#efebe2` | `#d8d2c7` | `#b8af9f` | `#9b948a` | `#6b6560` | `#454039` | `#2e2b26` | `#1d1b18` | `#151412` | `#11100e` |

## What the floor measures out at

Measured by the same check as every design, with the accent tint laid over `surface` as 04 measures it. The style cannot be built if any text pair fails.

| Pair | Light | Dark |
|---|---|---|
| text on page | 15.69 | 14.94 |
| text on surface | 17.21 | 13.94 |
| text-muted on page | 5.24 | 6.76 |
| text-muted on surface | 5.74 | 6.31 |
| text-muted-on-sunken on sunken | 4.83 | 7.60 |
| text-muted-on-sunken on the accent tint | 4.87 | 4.73 |
| text-muted-on-sunken on a status tint, lowest | 5.25 | 5.87 |
| accent-content on the accent tint | 8.18 | 7.39 |
| on-accent on accent | 9.65 | 10.75 |
| status text on page, lowest | 4.57 | 6.66 |
| status text on its own tint, lowest | 4.79 | 5.78 |

Two values moved from the look the fleet wore under `dashcore/ui`, both because of this table. The light page was `#f5f2ec`, where success and warning text measure 4.49:1, under the floor; `#f7f4ef` clears it and is hard to tell apart by eye. And in dark, muted text on the accent tint measured 4.35:1, so muted text on a quiet surface takes a lighter step there, `#aaa397`.

## What it leaves to the standard

On 16 October 2026 `dashcore/ui`'s own rules and this standard were set side by side and every difference settled once. Where `dashcore/ui` differed on something the standard fixes for every app, either the standard's rule stands or `dashcore/ui`'s became the rule for every app. Nothing in this section overrides 01 to 13 except the four exceptions above.

- **The standard's rule stands:** the type scale in 03, including the 12px floor; status colours in 04; the sidebar at 16rem on `sunken` in 02.
- **`dashcore/ui`'s became everyone's:** a selected tab in ink, a destructive action as an outline, 44px table rows, and the badge as a tag with a leading rule. They are in 04 to 07 now, for every design.
