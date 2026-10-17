# 06. Components

The component inventory is fixed and is not something a project or a generator chooses. It is Flux's free set plus five custom components: page header, stat tile, stat row, empty state and badge, and the frame for an app that does not draw its shell with Flux. Page titles go through the page header component, because that is what applies the pairing's second face at title size.

<!-- prompt-end -->

Each entry below is the Flux component and the rule the standard adds: what it is for, what the standard fixes about it, and what it is never used for. A project does not add components; it asks for a change to the standard. The fleet does not install Flux Pro: where an entry below names a Pro component, it is there to say which free one takes its place, and `design:audit` reports a Pro tag on a screen. The coverage table at the end lists every component Flux ships so nothing is left to guesswork.

## Navigation

- **Sidebar** `flux:sidebar` with `sidebar.header`, `sidebar.nav`, `sidebar.group`, `sidebar.item`. Regions and limits are in 02. Active item styling is set by the motif (07).
- **The frame** For an app that does not use Flux, the sidebar shell is `x-ds::shell`, which takes the app's menu as an array and draws 02's regions from it. Its parts are `x-ds::account` for the user menu at the foot of the sidebar, `x-ds::theme-toggle` with `x-ds::theme-script` in the head for a light and dark switch, `x-ds::flash` for the page's status, warning and error messages, and `x-ds::fonts` for the style's faces. The flash is the badge's treatment at page width: the status's soft tint and a two pixel rule in its colour. An app never draws its own sidebar beside it, and `Dashcore\DesignKit\Testing\Shell::assertConforms()` fails a page that does.
- **Navbar** `flux:navbar` and `flux:navbar.item` for the top nav shell only.
- **Navlist and navmenu** `flux:navlist`, `flux:navmenu`. Navlist is the vertical list inside the sidebar; navmenu is the dropdown form of a nav for the top nav shell on small screens. Navmenu never appears in content. Navlist appears in content in one case only: a section with its own destinations, such as settings, where it sits vertically beside the panel it switches and carries one level with no nesting. That is a sub-navigation and never a second main nav, so the sidebar or top nav still says which section you are in.
- **Header** `flux:header` appears below `lg` in the sidebar shell, where it carries the drawer toggle, and always in the top nav shell.
- **Breadcrumbs** `flux:breadcrumbs`. Only at three or more levels deep.
- **Tabs** are Pro and not used. A section with its own destinations takes a navlist beside the panel, as above; two to three options that behave like a toggle take a segmented `flux:radio.group`. Either way the selected item is marked in ink, `text-strong` with a two pixel `text-strong` rule beneath it or beside it, never in accent: the accent marks the page's primary action and the main nav's "you are here", and a selected tab spending it as well puts two accents on one screen. Never more than six, never nested, and never holds a primary action.
- **Command palette** `flux:command` is Pro and not used. Navigation is the sidebar's.

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
  - Danger: an outline. Danger text, a one pixel danger border, no fill; hover takes the danger soft tint. Never a solid red fill: a filled red button is the loudest thing on any screen, and a destructive action is never the one a screen should shout. Only for destructive actions, never as the default focus in a modal, and never the page's primary action. The markup is plain `variant="danger"`; `design:apply` puts the outline in the app's CSS, so nothing is written on a screen.
  - Sizes follow density: `base` in comfortable, `sm` in compact. Icon buttons are square. `xs` only inside table cells.
- **Button group** `flux:button.group`. Two to four default buttons that act on one thing. Never mixes variants.
- **Link** `flux:link`. `accent-content` colour, underline on hover only. Links are for navigation. Actions are buttons.
- **Dropdown and menu** `flux:dropdown`, `flux:menu`, `menu.item`, `menu.separator`, `menu.submenu`, `menu.radio`, `menu.checkbox`. Items are 14px, icon plus label. Destructive items last, separated, in danger text. One level of submenu at most.
- **Context menu** `flux:context` is Pro and not used. A row's or card's actions go in a `flux:dropdown` on a ghost icon button, with the menu rules above.
- **Popover** `flux:popover` is Pro and not used. One phrase is a tooltip; anything longer is a `flux:dropdown`, and a form or a primary action is a modal.
- **Tooltip** `flux:tooltip`. Only for icon buttons without a visible label. One short phrase.

## Forms

- **Field and fieldset** `flux:field`, `flux:fieldset`. Field wraps one control with its label, description and error. Fieldset groups related fields under a legend. A form of more than eight fields is split into fieldsets. Forms in the content grid span eight columns with four columns of notes or empty space, and go full width and one column below `lg`, as 12 says.
- **Input, textarea, select** `flux:input`, `flux:textarea`, `flux:select`. `surface` fill, `border-strong` outline, radius from the step. Focus is a two pixel accent ring with a two pixel offset in `page`. Placeholder is `text-muted` and is never the only label. Input groups (`input.group`) for a prefix or suffix only, never to join two fields.
- **Select variants** The listbox and combobox variants, `flux:autocomplete` and `flux:pillbox` are Pro and not used. One of many is a plain `flux:select`; many of many is a `flux:checkbox.group`.
- **Checkbox, radio, switch, toggle** `flux:checkbox`, `flux:radio`, `flux:switch`, `flux:toggle`. Checked state is an accent fill. Label to the right. Switch for a setting that takes effect at once; checkbox for a choice saved with the form; toggle for a pressed state in a toolbar. Card variant of checkbox and radio only when each option needs a description.
- **Slider** `flux:slider` is Pro and not used. A number is a `flux:input type="number"`.
- **Date and time** `flux:input` of type `date`, `time` or `datetime-local`. The Pro date picker, time picker and calendar are not used. A range is two inputs, from and to, in one field group.
- **Colour** `flux:input type="color"`, not the Pro colour picker. Only in settings that genuinely set a colour, such as a label colour. Never a way for a user to override the design.
- **Long text** `flux:textarea`, not the Pro editor, in `--ds-prose` size at the density's prose value. Where formatting matters it takes Markdown and renders it in the same prose style.
- **Chat or note input** `flux:textarea` with a ghost attach button and a primary send, not the Pro composer. One per view.
- **File upload** `flux:input type="file"`, not the Pro file upload. One line of instruction in the description; the chosen file named under it in `text-muted`; errors in danger text under the field.
- **OTP** `flux:otp`. Six cells, tabular numerals, accent focus ring. Only on the two-factor page.

## Data

- **Table** `flux:table`, `table.columns`, `table.column`, `table.rows`, `table.row`, `table.cell`. Head row sits on `sunken`, 12px labels, `text-muted`. Body rows on `surface`, 14px, separated by one pixel `border`. Row hover is `sunken`. The first column is `text-strong`. Numbers right aligned and tabular. Row height from density. A table with more than twenty-five rows paginates. Tables scroll horizontally inside their own container and never widen the page. Sortable columns show the arrow only on the sorted column. None of that is written on a screen: `design:apply` puts it in the app's CSS, so a table is a plain `flux:table` and the styling arrives with the package. A view that reaches into `data-flux-column` or `data-flux-cell` to say it again is reported, and it stops matching the moment the adapter moves.
- **Pagination** `flux:pagination`. Below the table, right aligned.
- **Badge** `x-ds::badge`, not `flux:badge`. A tag, not a pill: `radius-sm` corners and a two pixel rule on its leading edge. With a `status` of success, warning, danger or info it takes that status's soft background, its text, and a rule in the same colour as the text. Without one it is neutral: sunken background, muted text at the step 04 measures against a quiet surface, and a `border-strong` rule. Neutral carries categories, roles, plans, types, and any state the four colours do not cover, such as rejected, retired or not configured. Colour on a badge means status and nothing else, so a category never takes one and never the accent, and an unrecognised status renders neutral rather than being painted a colour that would say something untrue. A count is not a badge: a number beside a label is text. One badge per cell. Flux's badge paints from Tailwind's palette across six steps per colour, so it shows a different green from the standard's and matching it would mean repainting four colour families for the whole app. The custom badge reads the roles instead.
- **Avatar** `flux:avatar`, `avatar.group`. Initials on `sunken` when there is no image. Never in accent. Groups overlap by a quarter and stop at four plus a count.
- **Progress** `flux:progress`. Track is `sunken`, fill is accent. Determinate only; for unknown duration use a skeleton or a spinner in a button. A bar measured against a limit, a budget or a quota, may fill in the status it reports instead: `color="red"` over the limit, `amber` close to it, `green` within it. The style turns those three into the status roles. No other colour.
- **Chart** `flux:chart` is Pro and not used, and the standard has no chart yet. Numbers go in a table or stat tiles. A screen that genuinely needs a chart asks for a rule, written against that screen.
- **Hand-drawn SVG**, a sparkline or a diagram. Marks are coloured from the roles so they follow the register: a class that reads one (`stroke-(--ds-danger)`), or `style="stroke: var(--ds-danger)"` where a class cannot reach. Never a hex, and never a presentation attribute with `var()` in it (`stroke="var(…)"`), which some renderers ignore.
- **Code and command output** A `pre` on `sunken`, `text` colour, one pixel `border`, `radius-sm`, 14px in the pairing's mono face if it has one and the system mono otherwise. Never a black terminal block: on a light screen it is the loudest thing there.
- **Timeline** `flux:timeline` is Pro and not used. History is a list: events in `text`, times in `text-muted` tabular, a one pixel `border` line, the current event marked in accent. For history and audit, never for navigation.
- **Kanban** `flux:kanban` is Pro and not used.
- **Folded sections** native `<details>` with a `<summary>`, not the Pro accordion. Heading in `text-strong`, chevron in `text-muted`, one pixel `border` between items. For reference content; never to hide a form's required fields.
- **Carousel** `flux:carousel` is Pro and not used.
- **Skeleton** `flux:skeleton` for loading states longer than 300ms. Shapes match the content they stand in for.

## Surfaces and feedback

- **Card** `flux:card`. `surface`, one pixel `border`, radius from the step, padding from density. A card holds one thing. Cards are never nested.
- **Callout** `flux:callout`, `callout.heading`, `callout.text`, `callout.link`. Status colours only, soft style, an icon, one heading line, one text line, at most one action. A neutral callout uses `sunken` and `text` for notes that are not a status. The action is `callout.link`, an inline underlined link; Flux has no `callout.button`.
- **Modal** `flux:modal`, `modal.trigger`, `modal.close`. Max width 32rem for confirmations, 48rem for forms. Title, body, actions right-aligned with the primary last. Closes on escape and backdrop. Flyout variant slides from the end edge for detail views; never both on one screen.
- **Toast** `flux:toast`. Bottom right, status coloured, dismisses itself after five seconds unless it has an action. Flux fixes the width at 384px, dropping to 320px below `sm` inside a group. That is Flux's number rather than ours and it is fine; it is written down here so nobody reopens it as a disagreement.
- **Separator** `flux:separator`. One pixel `border`. Used between regions only when the hairline motif is chosen; otherwise regions are separated by the section gap. With text only to label a group of items.
- **Profile** `flux:profile`. The user block at the bottom of the sidebar or end of the header: avatar, name, chevron. Nothing else.
- **Brand** `flux:brand`. The brand block from 02. Logo or initial in an accent square, name in `text-strong`.
- **Spacer, container, main, aside, footer** `flux:spacer`, `flux:container`, `flux:main`, `flux:aside`, `flux:footer`. Layout primitives only. Container width is 80rem in the top nav shell. Footer is not part of either shell; aside is the chat or detail panel beside main and is 27rem wide.

## Custom components

The five components every fleet app needs that Flux does not ship, or does not ship on the standard's colours. They ship in the `dashcore/design-kit` package as Blade components and read only the `--ds-*` role variables and `config('brand')`.

- **Page header** `x-ds::page-header`. Eyebrow, title, description, actions, as defined in 02. Every page title goes through it. A bare `flux:heading` does not take the pairing's second face, so a serif or display pairing is invisible on any screen that skips this component.
- **Section heading** `x-ds::section-heading`, not `flux:heading`. The 18px, weight 600 role from 03, for a section heading, a card title or a modal title, in the body face. Flux cannot make it: `size="lg"` renders 16px at weight 500 and `size="xl"` renders 24px at 500 where 03 asks for 600, so its two largest sizes miss both of the standard's heading roles, and on weight every size misses. Its sizes are plain utility classes on the element, so passing a class of your own leaves two competing and the stylesheet order decides. `flux:heading` at the default size is 14px at 500, which is the UI emphasis role, and that one is right.
- **Stat tile** `x-ds::stat`. Label in the label style, value in the stat size and the display face if the pairing has one, an optional delta in success or danger text with an arrow icon, an optional one-line footnote in `text-muted`.
- **Stat row** `x-ds::stat-row`. The grid for two to four tiles, laid out by motif (07): a one pixel grid for hairline, the section gap otherwise.
- **Empty state** `x-ds::empty`. Centred in its region: one Heroicon at 24px in `text-muted`, one line saying what belongs here, one line saying how to add it, one default button. Never an illustration. Two sizes. The default fills a whole region and stands 320px tall. `size="compact"` drops the minimum height and the button, for a panel inside a split region, where the full size pushes everything below it off the screen.
- **Badge** `x-ds::badge`. One of the four statuses in soft style from the `--ds-*` roles with a leading rule in the status colour, or neutral when no status is given.

## Rules

- No component is styled per page. If a page needs a different table, the standard is wrong or the page is.
- Loading, empty and error states are designed for every list and every form, using skeleton, empty state and callout. A blank region is never a state.
- Icon-only buttons have a tooltip and an accessible label.
- Every component renders in both registers and is checked in the Components mockup screen before a design is exported.

## Coverage

Every component Flux ships as of 2.19, with where its rule lives. Free unless marked Pro, and a Pro row is not used: the next section names what replaces it. Internal helpers Flux uses to build other components are listed so the table is complete, not because a project uses them.

| Component | Tier | Rule |
|---|---|---|
| accordion | Pro, not used | Data |
| accent | free | internal, the accent wrapper Flux uses |
| aside | free | Surfaces |
| autocomplete | Pro, not used | Forms, select variants |
| avatar | free | Data |
| badge | free | Data |
| brand | free | Surfaces |
| breadcrumbs | free | Navigation |
| button, button.group | free | Actions |
| calendar | Pro, not used | Forms, date and time |
| callout | free | Surfaces |
| card | free | Surfaces |
| carousel | Pro, not used | Data |
| chart | Pro, not used | Data |
| checkbox | free | Forms |
| color-picker | Pro, not used | Forms |
| command | Pro, not used | Navigation |
| composer | Pro, not used | Forms |
| container | free | Surfaces |
| context | Pro, not used | Actions |
| date-picker | Pro, not used | Forms |
| description | free | Text |
| dropdown | free | Actions |
| editor | Pro, not used | Forms |
| error | free | Text |
| field, fieldset | free | Forms |
| file-upload, file-item | Pro, not used | Forms |
| flag | free | internal, country flags for phone inputs; not used |
| footer | free | Surfaces, not part of either shell |
| header | free | Navigation |
| heading | free | Text |
| icon | free | 08 |
| input | free | Forms |
| kanban | Pro, not used | Data |
| label, legend | free | Text |
| link | free | Actions |
| main | free | Surfaces |
| menu | free | Actions |
| modal | free | Surfaces |
| navbar | free | Navigation |
| navlist, navmenu | free | Navigation |
| otp | free | Forms |
| pagination | free | Data |
| pillbox | Pro, not used | Forms, select variants |
| popover | Pro, not used | Actions |
| profile | free | Surfaces |
| progress | free | Data |
| radio | free | Forms |
| select (listbox, combobox) | free; the Pro variants are not used | Forms |
| separator | free | Surfaces |
| sidebar | free | Navigation |
| skeleton | free | Data |
| slider | Pro, not used | Forms |
| spacer | free | Surfaces |
| subheading | free | Text |
| switch | free | Forms |
| tab, tabs | Pro, not used | Navigation |
| table | free | Data |
| text | free | Text |
| textarea | free | Forms |
| time-picker | Pro, not used | Forms |
| timeline | Pro, not used | Data |
| toast | free | Surfaces |
| toggle | free | Forms |
| tooltip | free | Actions |
| with-container, with-field, with-inline-field, with-reversed-inline-field, with-tooltip | free | internal wrappers |

## Flux Pro

The fleet does not install Flux Pro. Every app is on free Flux, so a fresh clone and a CI run install without a licence, and no screen depends on a component one app has and the next does not. `design:audit` reports a Pro tag on a screen, with the replacement below, and `design:apply` warns when it finds Pro installed.

| Pro | Use instead |
|---|---|
| tab, tabs | a navlist beside the panel, or a segmented `flux:radio.group` for two or three options |
| calendar, date-picker, time-picker | `flux:input` of that type |
| color-picker | `flux:input type="color"` |
| file-upload, file-item | `flux:input type="file"` |
| popover, context | `flux:dropdown`, or `flux:tooltip` for one phrase |
| autocomplete, select listbox and combobox | `flux:select` |
| pillbox | `flux:checkbox.group` |
| accordion | native `<details>` sections, each with a heading |
| editor, composer | `flux:textarea` |
| slider | `flux:input type="number"` |
| timeline | a list, as in Data above |

Carousel, chart, command and kanban have no replacement. No screen in the fleet needs one. If a screen ever needs a chart or a kanban, that is a rule to write then, against the real screen, rather than a guess now.
