# 01. Principles

Six things every screen in the fleet is true to. Each one is checkable.

1. **The content decides the layout.** A screen is composed around the tasks and data it actually holds. Three equal boxes in a row, a hero with a badge above it, or a grid of icon tiles are not layouts, they are the absence of one. Check: can each region be named by what it does for the user.

2. **One accent, spent carefully.** Every project has one accent colour. It marks the primary action, the active nav item, links, focus, and selection, and it appears at most once per row of content. Backgrounds, headings and body text never take it. Check: count the accent uses in a viewport; if any row has two, one is wrong.

3. **Structure is shared, identity is not.** Two Dashcore apps share the same shell regions, scales, components, icons and motion. They differ in accent, neutral temperature, type pairing, radius, density, one motif, and their sign-in words. Check: side by side, two apps should be recognisably the same product family and not mistaken for each other.

4. **Contrast is measured, not eyeballed.** Body and muted text sit at 4.5:1 or better on the surface they are actually on, in both registers. The figures are in 04. Check: run the contrast script on the resolved tokens; it fails the build if any role fails.

5. **Spacing comes from one scale.** Every gap, padding and gutter is a value from the eight-step scale in 05 and is decided by density, not by the person laying out the page. Check: no spacing value on a screen is outside the scale.

6. **Copy is concrete.** Headings say what a thing is. Empty states say what to do. Sign-in copy is about the client, not about software. No word from the buzzword list in the design principles. Check: read every string aloud to someone outside the project.
