# 00. Audit of what exists

Two looks are in the fleet today. Neither is the reference going forward, but both taught something, and the generator needs to know what is rejected as much as what is kept.

## The plain Laravel Livewire kit with Flux

Executive OS and roughly thirteen other sites run this untouched. Instrument Sans, Tailwind's zinc greys, an accent that is only neutral-800 in light and white in dark, and the stock sidebar shell. None of it was chosen, which is exactly the problem this standard exists to fix.

Kept as structure:

- Flux's component set. It is well built, accessible, and every fleet app already has it. The standard maps onto Flux rather than replacing it.
- The theming seam. Flux paints almost everything with the zinc scale (510 uses across its components) and three accent variables. Redefining those eleven greys and three accents in `@theme` rethemes every component at once. This is the whole reason a JSON file can restyle a kit.
- Fourteen pixel UI text. Flux uses `text-sm` for nearly all interface text and `text-xs` for labels. The type scale in 03 keeps that base rather than fight it.
- The sidebar shell's regions: brand, nav groups, spacer, user menu. The regions stay; their styling does not.

Rejected by name:

- Instrument Sans. It is the starter kit's default and reads as such.
- The neutral-only accent. An accent that is just an inversion of the greys is not a colour decision, and it cannot mark an active state or a primary action.
- Zinc-500 as muted text in dark mode. Measured at 3.19:1 on zinc-800, it fails the floor. Flux ships it; the standard overrides it.
- The dashboard placeholder layout of three equal boxes in a row. It is the composition the design principles list warns about, and it does not come from any real content.

## Starter OS Plus

Two sites run this: the starter itself and sample.transnfer.com. It was a real design decision, and a strong one: near-black canvas, teal-green accent, Barlow Condensed headings, Bebas Neue stat numbers, Space Mono labels, DM Sans body, dark as the default register. It is retired as the fleet reference because it is one client's mood applied to everyone, and because it lives outside Flux as thirty hand-written component classes that every app then has to maintain.

Kept as structure:

- The habit of a small semantic variable set. OS Plus theme-flips about fifteen variables per register, and every component reads only those. The colour roles in 04 do the same, then map them onto Flux's variables so no parallel component library is needed.
- The metrics row as a hairline grid. Tiles separated by one pixel of border colour rather than floating cards is one of the four motifs in 07.
- Mono uppercase eyebrow labels above titles and on stat tiles. Another of the four motifs.
- The page header as one region: optional eyebrow, title, one line of description, actions on the right. Kept in 02 for both shells.
- A left accent bar on the active nav item. The third motif.
- Two-panel sign-in with brand copy driven by data (`config/brand.php`). Kept, and the generator writes that copy.

Rejected by name:

- Dark as the default register. Light is the default fleet-wide; dark is required but secondary.
- The specific palette and the four specific faces. They are one look, not a system.
- Component classes outside Flux. The standard describes components in Flux's terms and allows exactly three custom ones (06).
- Uppercase display headings at 32 pixels for every page title. Allowed only when the chosen pairing's display face is a condensed or narrow cut.
- Decorative glow and pulse. A pulsing live dot and a glowing card border are decoration for its own sake. Motion in 08 is limited to state changes.

## The design principles list

John's global instructions carry a list of twenty tells that mark generic AI output. The standard treats two as bugs to be measured rather than judged, low-contrast dark mode and inconsistent spacing, and avoids the rest by construction: no gradient hero, no badge above the headline, no glass cards, no scroll fades, no cursor beams, no hover opacity fades, no emoji in headings, no Inter, no Space Grotesk and Instrument Serif, no mixing Heroicons and Lucide.
