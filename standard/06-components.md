# 06. Components

The component inventory is fixed and is not something a project or a generator chooses. It is Flux's set, free and Pro, plus five custom components: page header, stat tile, stat row, empty state and badge. Page titles go through the page header component, because that is what applies the pairing's second face at title size.

<!-- prompt-end -->

Each entry below is the Flux component and the rule the standard adds: what it is for, what the standard fixes about it, and what it is never used for. A project does not add components; it asks for a change to the standard. The coverage table at the end lists every component Flux ships so nothing is left to guesswork.

## Navigation

- **Sidebar** `flux:sidebar` with `sidebar.header`, `sidebar.nav`, `sidebar.group`, `sidebar.item`. Regions and limits are in 02. Active item styling is set by the motif (07).
- **Navbar** `flux:navbar` and `flux:navbar.item` for the top nav shell only.
- **Navlist and navmenu** `flux:navlist`, `flux:navmenu`. Navlist is the vertical list inside the sidebar; navmenu is the dropdown form of a nav for the top nav shell on small screens. Navmenu never appears in content. Navlist appears in content in one case only: a section with its own destinations, such as settings, where it sits vertically beside the panel it switches and carries one level with no nesting. That is a sub-navigation and never a second main nav, so the sidebar or top nav still says which section you are in.
- **Header** `flux:header` appears below `lg` in the sidebar shell, where it carries the drawer toggle, and always in the top nav shell.
- **Breadcrumbs** `flux:breadcrumbs`. Only at three or more levels deep.
- **Tabs** `flux:tabs`, `flux:tab`, `flux:tab.panel` (Pro). Selected tab is underlined in accent, two pixels, or takes the accent bar when that motif is chosen. Tabs never nest, never exceed six, and never hold a primary action. Segmented variant only for two to three options that behave like a toggle.
- **Command palette** `flux:command` (Pro). One per app, opened by a shortcut and a search icon in the header. Results are grouped under labels in the label style. Never a substitute for navigation the sidebar should carry.

## Text

- **Heading** `flux:heading` at the sizes in 03. Level is set by role in the page, not by importance of the words.
- **Text** `flux:text` for body and descriptions. Muted variant takes `text-muted` with the sunken correction from 04.
- **Subheading** `flux:subheading` is the one-line description under a page title. One sentence.
- **Description, label, legend, error** `flux:description`, `flux:label`, `flux:legend`, `flux:error`. Label above the control in `text`, description below the label in `text-muted`, error below the control in danger text, one line. Legend is a fieldset's heading at the section heading size.

## Actions

- **Button** `flux:button`. Four variants and no others:
  - Primary: accent fill, `on-accent` text. One per view region. Never two in a row.
  - Default: `surface` fill, `border-strong` outline, `text`. Any number.
  - Ghost: no fill, no border, `text`. For toolbars and icon buttons.
  - Danger: danger fill, white text. Only for destructive actions, and never as the default focus in a modal.
  - Sizes follow density: `base` in comfortable, `sm` in compact. Icon buttons are square. `xs` only inside table cells.
- **Button group** `flux:button.group`. Two to four default buttons that act on one thing. Never mixes variants.
- **Link** `flux:link`. `accent-content` colour, underline on hover only. Links are for navigation. Actions are buttons.
- **Dropdown and menu** `flux:dropdown`, `flux:menu`, `menu.item`, `menu.separator`, `menu.submenu`, `menu.radio`, `menu.checkbox`. Items are 14px, icon plus label. Destructive items last, separated, in danger text. One level of submenu at most.
- **Context menu** `flux:context` (Pro). Right-click on a row or card, same rules as menu. Everything in it must also be reachable without a right-click.
- **Popover** `flux:popover` (Pro). Small explanatory content anchored to a control. Never holds a form or a primary action; that is a modal.
- **Tooltip** `flux:tooltip`. Only for icon buttons without a visible label. One short phrase.

## Forms

- **Field and fieldset** `flux:field`, `flux:fieldset`. Field wraps one control with its label, description and error. Fieldset groups related fields under a legend. A form of more than eight fields is split into fieldsets. Forms in the content grid span eight columns with four columns of notes or empty space, and go full width and one column below `lg`, as 12 says.
- **Input, textarea, select** `flux:input`, `flux:textarea`, `flux:select`. `surface` fill, `border-strong` outline, radius from the step. Focus is a two pixel accent ring with a two pixel offset in `page`. Placeholder is `text-muted` and is never the only label. Input groups (`input.group`) for a prefix or suffix only, never to join two fields.
- **Select variants** `flux:select` listbox and combobox (Pro), **autocomplete** `flux:autocomplete` (Pro), **pillbox** `flux:pillbox` (Pro). Listbox for up to twenty options, combobox or autocomplete above that, pillbox for many-of-many. The selected pill is `accent-soft` with `accent-content` text, never a filled accent.
- **Checkbox, radio, switch, toggle** `flux:checkbox`, `flux:radio`, `flux:switch`, `flux:toggle`. Checked state is an accent fill. Label to the right. Switch for a setting that takes effect at once; checkbox for a choice saved with the form; toggle for a pressed state in a toolbar. Card variant of checkbox and radio only when each option needs a description.
- **Slider** `flux:slider` (Pro). Only for a value where the rough position matters more than the number, and always paired with the number shown. Track is `border-strong`, fill and thumb are accent.
- **Date picker, time picker, calendar** `flux:date-picker`, `flux:time-picker` (Pro), `flux:calendar` (Pro). Selected day is an accent fill; today is an accent ring; ranges are `accent-soft`. Calendar inline only when the whole view is about a date; otherwise the picker.
- **Colour picker** `flux:color-picker` (Pro). Only in settings that genuinely set a colour, such as a label colour. Never a way for a user to override the design.
- **Editor** `flux:editor` (Pro). Rich text in `--ds-prose` size at the density's prose value. Toolbar is ghost buttons in one row; no font or colour controls, since the standard fixes both. Output renders in the same prose style.
- **Composer** `flux:composer` (Pro). The chat or note input: textarea that grows, ghost attach button, primary send. One per view.
- **File upload and file item** `flux:file-upload`, `flux:file-item` (Pro). Dropzone is `sunken` with a `border-strong` dashed outline, one line of instruction, one default button. Progress uses the accent. Errors in danger text under the item.
- **OTP** `flux:otp`. Six cells, tabular numerals, accent focus ring. Only on the two-factor page.

## Data

- **Table** `flux:table`, `table.columns`, `table.column`, `table.rows`, `table.row`, `table.cell`. Head row sits on `sunken`, 12px labels, `text-muted`. Body rows on `surface`, 14px, separated by one pixel `border`. Row hover is `sunken`. The first column is `text-strong`. Numbers right aligned and tabular. Row height from density. A table with more than twenty-five rows paginates. Tables scroll horizontally inside their own container and never widen the page. Sortable columns show the arrow only on the sorted column.
- **Pagination** `flux:pagination`. Below the table, right aligned.
- **Badge** `x-ds::badge`, not `flux:badge`. With a `status` of success, warning, danger or info it takes that status's soft background and text. Without one it is neutral: sunken background, muted text at the step 04 measures against a quiet surface. Neutral carries categories, roles, plans, types, and any state the four colours do not cover, such as rejected, retired or not configured. Colour on a badge means status and nothing else, so a category never takes one and never the accent, and an unrecognised status renders neutral rather than being painted a colour that would say something untrue. A count is not a badge: a number beside a label is text. One badge per cell. Flux's badge paints from Tailwind's palette across six steps per colour, so it shows a different green from the standard's and matching it would mean repainting four colour families for the whole app. The custom badge reads the roles instead.
- **Avatar** `flux:avatar`, `avatar.group`. Initials on `sunken` when there is no image. Never in accent. Groups overlap by a quarter and stop at four plus a count.
- **Progress** `flux:progress`. Track is `sunken`, fill is accent. Determinate only; for unknown duration use a skeleton or a spinner in a button.
- **Chart** `flux:chart` (Pro). Lines and bars only in the standard's palette: series one is accent, series two is `text-muted`, further series are status colours only when they mean that status. Axes and grid lines are `border`. No gradients under lines, no shadows, no animation beyond the 200ms reveal. Every chart has a title in the label style and a unit.
- **Timeline** `flux:timeline` (Pro). Events in `text` with times in `text-muted` tabular; the line is `border`; the current event marker is accent. For history and audit, never for navigation.
- **Kanban** `flux:kanban` (Pro). Columns on `sunken`, cards on `surface` with the card rules. Column counts in the label style. Drag handles are `text-muted`. Never more than six columns visible.
- **Accordion** `flux:accordion` (Pro). Headings at the section heading size, chevron in `text-muted`, one pixel `border` between items. For reference content; never to hide a form's required fields.
- **Carousel** `flux:carousel` (Pro). Only for a sequence of images or cards the user steps through by choice. Never auto-plays.
- **Skeleton** `flux:skeleton` for loading states longer than 300ms. Shapes match the content they stand in for.

## Surfaces and feedback

- **Card** `flux:card`. `surface`, one pixel `border`, radius from the step, padding from density. A card holds one thing. Cards are never nested.
- **Callout** `flux:callout`, `callout.heading`, `callout.text`, `callout.button`. Status colours only, soft style, an icon, one heading line, one text line, at most one action. A neutral callout uses `sunken` and `text` for notes that are not a status.
- **Modal** `flux:modal`, `modal.trigger`, `modal.close`. Max width 32rem for confirmations, 48rem for forms. Title, body, actions right-aligned with the primary last. Closes on escape and backdrop. Flyout variant slides from the end edge for detail views; never both on one screen.
- **Toast** `flux:toast`. Bottom right, status coloured, dismisses itself after five seconds unless it has an action.
- **Separator** `flux:separator`. One pixel `border`. Used between regions only when the hairline motif is chosen; otherwise regions are separated by the section gap. With text only to label a group of items.
- **Profile** `flux:profile`. The user block at the bottom of the sidebar or end of the header: avatar, name, chevron. Nothing else.
- **Brand** `flux:brand`. The brand block from 02. Logo or initial in an accent square, name in `text-strong`.
- **Spacer, container, main, aside, footer** `flux:spacer`, `flux:container`, `flux:main`, `flux:aside`, `flux:footer`. Layout primitives only. Container width is 80rem in the top nav shell. Footer is not part of either shell; aside is the chat or detail panel beside main and is 27rem wide.

## Custom components

The five components every fleet app needs that Flux does not ship, or does not ship on the standard's colours. They ship in the `dashcore/design-kit` package as Blade components and read only the `--ds-*` role variables and `config('brand')`.

- **Page header** `x-ds::page-header`. Eyebrow, title, description, actions, as defined in 02. Every page title goes through it. A bare `flux:heading` does not take the pairing's second face, so a serif or display pairing is invisible on any screen that skips this component.
- **Stat tile** `x-ds::stat`. Label in the label style, value in the stat size and the display face if the pairing has one, an optional delta in success or danger text with an arrow icon, an optional one-line footnote in `text-muted`.
- **Stat row** `x-ds::stat-row`. The grid for two to four tiles, laid out by motif (07): a one pixel grid for hairline, the section gap otherwise.
- **Empty state** `x-ds::empty`. Centred in its region: one Heroicon at 24px in `text-muted`, one line saying what belongs here, one line saying how to add it, one default button. Never an illustration. Two sizes. The default fills a whole region and stands 320px tall. `size="compact"` drops the minimum height and the button, for a panel inside a split region, where the full size pushes everything below it off the screen.
- **Badge** `x-ds::badge`. One of the four statuses in soft style from the `--ds-*` roles, or neutral when no status is given.

## Rules

- No component is styled per page. If a page needs a different table, the standard is wrong or the page is.
- Loading, empty and error states are designed for every list and every form, using skeleton, empty state and callout. A blank region is never a state.
- Icon-only buttons have a tooltip and an accessible label.
- Every component renders in both registers and is checked in the Components mockup screen before a design is exported.

## Coverage

Every component Flux ships as of 2.19, with where its rule lives. Free unless marked Pro. Internal helpers Flux uses to build other components are listed so the table is complete, not because a project uses them.

| Component | Tier | Rule |
|---|---|---|
| accordion | Pro | Data |
| accent | free | internal, the accent wrapper Flux uses |
| aside | free | Surfaces |
| autocomplete | Pro | Forms, select variants |
| avatar | free | Data |
| badge | free | Data |
| brand | free | Surfaces |
| breadcrumbs | free | Navigation |
| button, button.group | free | Actions |
| calendar | Pro | Forms, date and time |
| callout | free | Surfaces |
| card | free | Surfaces |
| carousel | Pro | Data |
| chart | Pro | Data |
| checkbox | free | Forms |
| color-picker | Pro | Forms |
| command | Pro | Navigation |
| composer | Pro | Forms |
| container | free | Surfaces |
| context | Pro | Actions |
| date-picker | Pro | Forms |
| description | free | Text |
| dropdown | free | Actions |
| editor | Pro | Forms |
| error | free | Text |
| field, fieldset | free | Forms |
| file-upload, file-item | Pro | Forms |
| flag | free | internal, country flags for phone inputs; not used |
| footer | free | Surfaces, not part of either shell |
| header | free | Navigation |
| heading | free | Text |
| icon | free | 08 |
| input | free | Forms |
| kanban | Pro | Data |
| label, legend | free | Text |
| link | free | Actions |
| main | free | Surfaces |
| menu | free | Actions |
| modal | free | Surfaces |
| navbar | free | Navigation |
| navlist, navmenu | free | Navigation |
| otp | free | Forms |
| pagination | free | Data |
| pillbox | Pro | Forms, select variants |
| popover | Pro | Actions |
| profile | free | Surfaces |
| progress | free | Data |
| radio | free | Forms |
| select (listbox, combobox) | free, Pro variants | Forms |
| separator | free | Surfaces |
| sidebar | free | Navigation |
| skeleton | free | Data |
| slider | Pro | Forms |
| spacer | free | Surfaces |
| subheading | free | Text |
| switch | free | Forms |
| tab, tabs | Pro | Navigation |
| table | free | Data |
| text | free | Text |
| textarea | free | Forms |
| time-picker | Pro | Forms |
| timeline | Pro | Data |
| toast | free | Surfaces |
| toggle | free | Forms |
| tooltip | free | Actions |
| with-container, with-field, with-inline-field, with-reversed-inline-field, with-tooltip | free | internal wrappers |

## An app that has not installed Flux Pro yet

Nineteen rows above are Pro. Dashcore's licence covers every site in the fleet, so Pro is the expected end state and a site should install it. Eleven projects have not yet, Executive OS among them, and a new site starts on the free package before anyone applies the licence.

Such an app is still on the standard. It is behind on an install, not outside the rules, and its screens are not exceptions.

Eleven of the nineteen have a free stand-in, and the rule for each is the Pro one, unchanged. Only the component differs.

| Pro | Without it |
|---|---|
| tab, tabs | a navlist beside the panel, as above |
| calendar, date-picker, time-picker | `flux:input` of that type |
| file-upload, file-item | `flux:input type="file"` |
| popover, context | `flux:dropdown` |
| autocomplete, pillbox | `flux:select` |
| accordion | stacked sections, each with a heading |
| editor | `flux:textarea` |

The other eight have no stand-in and need none: carousel, chart, color-picker, command, composer, kanban, slider and timeline. No screen in the fleet uses one. If a screen ever needs a chart or a kanban, that is a rule to write then, against the real screen, rather than a guess now.

A screen built on a stand-in is on the standard. Do not mark it as an exception, and do not rebuild it when the app installs Pro, unless the Pro component does something the screen actually needs. Installing Pro is worth doing on its own; rewriting working screens afterwards is not.
