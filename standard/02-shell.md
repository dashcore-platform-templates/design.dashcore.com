# 02. Shells

Two shells. A project uses one, chosen by a person in the brief, never by the generator. Sidebar is for operations tools used for hours at a time with more than six destinations. Top nav is for lighter apps: a portal opened once a week, a public-facing tool, anything with six or fewer destinations. Both share every region except the navigation.

Every main view opens with the **page header**: an optional eyebrow in the label style, the page title, an optional one-line description, and actions on the right, at most one of them primary. The title goes through the `x-ds::page-header` component rather than a bare heading, because that is what applies the pairing's second face at title size. A title set any other way loses the serif or display face entirely.

Content is composed around what the screen actually holds. Three equal columns are allowed only when there are exactly three peers of equal weight.

**Sign-in** is a two-panel page outside the shell. The brand panel carries the generated copy: wordmark, eyebrow, headline, tagline, footer line. The form panel holds the form. On mobile the brand panel collapses to a brand block above the form.

<!-- prompt-end -->

## Sidebar shell

Regions, in order:

- **Sidebar**, 16rem wide, full height, sticky. Surface colour is `sunken` in light and `surface` in dark. Right border one pixel `border`. Contains, top to bottom: brand block, nav groups, spacer, user menu. Collapses to a drawer below `lg`, opened from a header that exists only on mobile.
- **Brand block**. Wordmark or logo on the left, product name in `text-strong`, one optional line of `text-muted` beneath it. Height 3.5rem in comfortable, 3rem in compact. No tagline paragraphs here.
- **Nav groups**. At most two levels: a group heading in the label style from 03, then items. At most eight items visible per group before it scrolls. Each item is an icon plus a label, 14px, `text` colour, `text-strong` when active. Active state is marked by the accent as the chosen motif dictates (07), never by a filled accent background across the whole item.
- **User menu**. Pinned to the bottom. Avatar or initials, name, and a dropdown with settings and sign out. Nothing else lives here.
- **Main**. Everything to the right. Page background `page`. Contains a page header and a content area.

## Top nav shell

- **Header bar**, 3.5rem tall, full width, sticky, `surface` colour with a one pixel bottom `border`. Left to right: brand block, primary nav items inline, spacer, user menu. On mobile the nav items move into a drawer behind a toggle.
- **Main**. Content is constrained to a max width of 80rem and centred. Same page header and content area as the sidebar shell.

## The page header in detail

Padding from the spacing scale for the chosen density.

- Optional eyebrow, label style from 03, `text-muted` or accent per the motif.
- Title, `text-strong`, page-title size from 03, in the pairing's display face if it has one. Always through `x-ds::page-header`.
- Optional one-line description in `text-muted`. One sentence. Not a paragraph.
- Actions on the right, vertically aligned to the title baseline. At most one primary button and two secondary ones. Actions wrap below the title on mobile.
- A one pixel `border` beneath the whole header when the motif is hairline; otherwise the header is separated from content by spacing only.

## The content area

- Padded by the page gutter for the chosen density (05).
- Laid out on a 12 column grid with the section gap from 05. Regions are placed by content: a stat row spans 12, a table spans 12, a form spans 8 with 4 of notes, two related panels split 6 and 6, a detail view is 8 and 4.
- Vertical rhythm is the section gap between regions and the card padding within them. Nothing else.

## Sign-in in detail

Left panel, 40 percent of the width on desktop, carries the brand. Its surface is `sunken` in light and `surface` in dark, with an optional hero image. Right panel, 60 percent, is `page` and holds the form: heading, email, password, remember me, primary button, and the secondary links.

## Rules

- No third shell. If a case needs one, it is a change to the standard, not a project decision.
- The sidebar never holds content, search, or stats. It is navigation and identity.
- Breadcrumbs appear only when the view is three or more levels deep.
- A footer is not part of either shell.
