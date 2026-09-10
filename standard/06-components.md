# 06. Components

The inventory is Flux's, plus three custom components every fleet app needs and Flux does not ship. Each entry is the Flux component and the rule the standard adds. A project does not add components; it asks for a change to the standard.

## Navigation

- **Sidebar** `flux:sidebar` with `sidebar.header`, `sidebar.nav`, `sidebar.group`, `sidebar.item`. Regions and limits are in 02. Active item styling is set by the motif (07).
- **Navbar** `flux:navbar` and `flux:navbar.item` for the top nav shell only.
- **Header** `flux:header` appears on mobile in the sidebar shell and always in the top nav shell.
- **Breadcrumbs** `flux:breadcrumbs`. Only at three or more levels deep.
- **Tabs** `flux:tabs`. Selected tab is underlined in accent, two pixels. Tabs never nest.

## Text

- **Heading** `flux:heading` at the sizes in 03. Level is set by role in the page, not by importance of the words.
- **Text** `flux:text` for body and descriptions. Muted variant takes `text-muted` with the sunken correction from 04.
- **Subheading** `flux:subheading` is the one-line description under a page title. One sentence.

## Actions

- **Button** `flux:button`. Four variants and no others:
  - Primary: accent fill, `on-accent` text. One per view region. Never two in a row.
  - Default: `surface` fill, `border-strong` outline, `text`. Any number.
  - Ghost: no fill, no border, `text`. For toolbars and icon buttons.
  - Danger: danger fill, white text. Only for destructive actions, and never as the default focus in a modal.
  - Sizes follow density: `base` in comfortable, `sm` in compact. Icon buttons are square.
- **Link** `flux:link`. `accent-content` colour, underline on hover only. Links are for navigation. Actions are buttons.
- **Dropdown and menu** `flux:dropdown`, `flux:menu`. Items are 14px, icon plus label. Destructive items last, separated, in danger text.

## Forms

- **Field, label, description, error** `flux:field` and children. Label above the control, 12px, `text`. Description below the label in `text-muted`. Error below the control in danger text, one line.
- **Input, textarea, select** `flux:input`, `flux:textarea`, `flux:select`. `surface` fill, `border-strong` outline, radius from the step. Focus is a two pixel accent ring with a two pixel offset in `page`. Placeholder is `text-muted` and is never the only label.
- **Checkbox, radio, switch** `flux:checkbox`, `flux:radio`, `flux:switch`. Checked state is an accent fill. Label to the right.
- **Fieldset** `flux:fieldset` groups related fields under a section heading. A form of more than eight fields is split into fieldsets.
- Forms in the content grid span eight columns with four columns of notes or empty space. Full-width forms are for mobile only.

## Data

- **Table** `flux:table`. Head row sits on `sunken`, 12px labels, `text-muted`. Body rows on `surface`, 14px, separated by one pixel `border`. Row hover is `sunken`. The first column is `text-strong`. Numbers right aligned and tabular. Row height from density. A table with more than twenty-five rows paginates. Tables scroll horizontally inside their own container and never widen the page.
- **Pagination** `flux:pagination`. Below the table, right aligned.
- **Badge** `flux:badge`. Status only, soft style: status soft background, status text. Never for categories or counts, and never in accent. One badge per cell.
- **Avatar** `flux:avatar`. Initials on `sunken` when there is no image. Never in accent.
- **Skeleton** `flux:skeleton` for loading states longer than 300ms.

## Surfaces and feedback

- **Card** `flux:card`. `surface`, one pixel `border`, radius from the step, padding from density. A card holds one thing. Cards are never nested.
- **Callout** `flux:callout`. Status colours only, soft style, an icon, one heading line, one text line, at most one action.
- **Modal** `flux:modal`. Max width 32rem for confirmations, 48rem for forms. Title, body, actions right-aligned with the primary last. Closes on escape and backdrop.
- **Toast** `flux:toast`. Bottom right, status coloured, dismisses itself after five seconds unless it has an action.
- **Separator** `flux:separator`. One pixel `border`. Used between regions only when the hairline motif is chosen; otherwise regions are separated by the section gap.
- **Tooltip** `flux:tooltip`. Only for icon buttons without a visible label.

## Custom components

The three components every fleet app needs that Flux does not ship. They live in the starter kit as Blade components and read only the `--ds-*` role variables.

- **Page header** `x-ds-page-header`. Eyebrow, title, description, actions, as defined in 02.
- **Stat tile** `x-ds-stat`. Label in the label style, value in the stat size and the display face if the pairing has one, an optional delta in success or danger text with an arrow icon, an optional one-line footnote in `text-muted`. Tiles sit in a row of two to four. Their surface treatment is set by the motif (07).
- **Empty state** `x-ds-empty`. Centred in its region: one Heroicon at 24px in `text-muted`, one line saying what belongs here, one line saying how to add it, one default button. Never an illustration.

## Rules

- No component is styled per page. If a page needs a different table, the standard is wrong or the page is.
- Loading, empty and error states are designed for every list and every form, using skeleton, empty state and callout. A blank region is never a state.
- Icon-only buttons have a tooltip and an accessible label.
