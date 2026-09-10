# 07. Motifs

A motif is one structural habit that runs through a whole project and gives it a recognisable hand without changing the components. The generator picks exactly one. A project never combines two.

Each motif is defined by where it appears and what it forbids, so it can be checked in the dashboard mockup.

## 1. Accent bar

The active nav item carries a two pixel accent bar on its leading edge, full item height, and its label turns `text-strong`. No background fill on the active item. The same bar marks the selected row in a table and the active tab. Page header has no bottom border.

Forbids: filled active nav items, accent-soft row selection.

Suits: sidebar shell, dense tools, projects with a saturated accent that would overwhelm as a fill.

## 2. Hairline

Regions and rows are separated by one pixel `border` lines instead of card boxes. Cards lose their border and radius and sit flush; the grid between stat tiles is a one pixel line of `border` with tiles on `surface` inside it. Page header has a one pixel bottom border. Tables have no outer border, only row lines.

Forbids: bordered cards, shadows anywhere except overlays, gaps between stat tiles.

Suits: data-heavy screens, compact density, sharp radius. The closest of the four to the retired OS Plus grid, kept because it earned it.

## 3. Eyebrow

Every page title and every stat tile carries an uppercase label above it: 12px, 0.08em spacing, weight 500, `text-muted`, set in the pairing's mono face if it has one and the body face otherwise. Table heads are set the same way. Section headings inside cards are replaced by an eyebrow.

Forbids: mixed-case labels, section headings at 18px inside cards.

Suits: pairings with a mono second face, operations and technical work, anything with reference codes.

## 4. Filled stat

The stat row is the one place the accent is allowed as a surface. The first tile is filled `accent` with `on-accent` text; the rest are `accent-soft` with `text-strong` values. Active nav items take an `accent-soft` background with `accent-content` text. No accent bars anywhere.

Forbids: accent bars, accent-soft anywhere outside nav and the stat row.

Suits: top nav shell, comfortable density, round radius, people-facing apps where one warm block of colour reads as welcome rather than alarm.

## Rules

- The motif is chosen once. A screen that does not have a nav, a table or a stat row simply shows less of it.
- A motif never adds a component or a colour. It only redistributes what the standard already allows.
- If neither the brief nor the generator has a reason to prefer one, the default is accent bar for the sidebar shell and filled stat for the top nav shell.
