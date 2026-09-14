# 12. Widths

The rest of this standard describes screens at one width and then says "on mobile" without saying what that means. Three people read that and pick three widths. This section names them.

<!-- prompt-end -->

## The scale is Tailwind's

`sm` 640, `md` 768, `lg` 1024, `xl` 1280, `2xl` 1536.

Not because they are the right numbers in the abstract, but because Flux is built on them. Naming our own would mean fighting a component library in every app in the fleet, for a set of numbers nobody would notice. The word in this standard is the word a developer types.

## The fleet designs at three widths

Not five. Fifteen admin tools do not earn five layouts, and a width nobody checks is a width that is wrong.

| Name | Range | What it is |
|---|---|---|
| Phone | below `md` | A customer on a phone. One column. |
| Tablet | `md` to below `lg` | A tablet, or a narrow window on a laptop. |
| Desktop | `lg` and up | Staff, and the width every screen here is drawn at. |

Nothing new happens above `lg`. Content is already capped at 80rem by 02, so a large monitor centres the same layout rather than stretching a line of text across it.

A screen is finished when it has been looked at in all three. Not resized past, looked at.

## The shells

**Sidebar** is a drawer below `lg`, opened from a header that exists only below that width. It is not narrowed and it is not hidden. A nav that disappears leaves somebody with no map.

**Top nav** keeps its items inline down to `md`. Below that they move into `flux:navmenu` behind a toggle, and the brand block stays visible.

The two differ on purpose. Five nav items fit across 768px; a 16rem sidebar beside content does not.

## What changes at each width

**Page header.** Actions wrap below the title below `md`. The title never truncates to make room for them.

**Stat row.** Two across below `lg`, four at `lg` and up. Two tiles at phone width is tight and still readable; three is not. If a row has three tiles, it is two and then one, not three squeezed.

**Sign-in.** The brand panel sits above the form below `lg`, carrying the wordmark and the headline only. The tagline and footer line are dropped rather than stacked, because a phone sign-in is a form and everything above it is delay.

**Forms.** Fields are one column below `md`. Two columns are allowed from `md`, where each column still holds a label and its control without wrapping. A two-column form at phone width is two narrow columns, which is worse than long. Below `lg` the form spans the full content width rather than the eight columns 06 gives it, since there is nothing beside it to make room for.

**Cards and regions.** A row of regions becomes one column below `md`. Below `lg` it may stay two if each half still holds its content without wrapping a heading.

## Tables

The hard one, and the answer depends on who opens the screen.

**A table a customer reaches stops being a table below `md`.** Each row becomes a block: the value that identifies the row as its heading, the rest as label and value lines beneath. Six columns at 375px is not a table anybody can read, and sideways scrolling on a phone hides the columns that made it a table.

**A table only staff reach may scroll sideways below `md`**, inside its own container rather than the page. This is allowed because nobody opens it there, and building a stacked view for a screen nobody opens on a phone is work that will not be used.

Which one applies is a per-screen decision and belongs in that screen's commit message. Where it is not obvious, stack it: the cost of stacking a staff table is a little markup, and the cost of scrolling a customer table is a customer who cannot read it.

Neither version drops a column. A column worth showing at `lg` is worth reaching at `md`.

## What the audit can and cannot see

It can see a fixed width or a layout that names no breakpoint at all. It cannot tell you a screen was never opened at phone width, and it cannot judge whether a stacked table reads well.

So this section is checked by looking. The tool shows a design at each of the three widths for that reason.
