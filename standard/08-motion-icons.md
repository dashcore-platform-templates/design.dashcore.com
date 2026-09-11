# 08. Motion and icons

Motion marks a state change and nothing else. Icons are Heroicons, and only Heroicons. Nothing in a fleet app is illustrated except an optional hero image on sign-in.

<!-- prompt-end -->

## Motion

| What | Duration | Easing |
|---|---|---|
| Control state: hover, focus, checked | 150ms | ease-out |
| Overlay open and close: dropdown, modal, toast, drawer | 200ms | ease-out in, ease-in out |
| Skeleton shimmer | 1.5s loop | linear |

Hover states change colour, border or underline. They never change opacity, scale or position. A button that fades on hover is on the tells list.

Not used, anywhere: scroll-triggered fades or slides, parallax, cursor-following effects, pulsing dots, glowing borders, auto-playing carousels, animated gradients, count-up numbers.

`prefers-reduced-motion: reduce` turns every duration above to zero except the skeleton, which becomes a static block. This is not optional.

Alpine transitions and Flux's own hover states are the implementation. No animation library is added.

## Icons

Flux ships Heroicons and also resolves Lucide names, which is exactly how a project ends up mixing two sets. Lucide names are not used, and the adapter renames any it finds before deleting the four icon files the starter kit ships.

| Where | Style | Size |
|---|---|---|
| Nav items, buttons, page header actions | outline | 20 |
| Table cells, badges, inline with 14px text | mini | 16 |
| Empty state, callout | outline | 24 |
| Filled and checked states, status indicators | solid | matches context |

Icons take the colour of the text beside them. An icon never carries the accent on its own except inside the active nav item when the motif calls for it.

No emoji in headings, labels, buttons or nav. Emoji are allowed only in content the user typed.

## Imagery

Sign-in may carry one hero image supplied by the client. Empty states use an icon. Dashboards do not have banners.
