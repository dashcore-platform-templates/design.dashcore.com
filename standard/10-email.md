# 10. Email

Every app in the fleet sends mail, and every one of them currently sends Laravel's default template. An owner gets a portal built to this standard and then an email that looks like nothing to do with it.

<!-- prompt-end -->

Email is the one surface where none of the rest of this standard applies directly. There are no custom properties, no utility classes, no second typeface and no dark register. What survives is the accent, the neutral scale, the voice and the structure. Everything else is rebuilt in inline styles, because that is all an email client will honour.

## What an email client will not do

Read this first, because most email mistakes are a rule from another section applied where it cannot work.

- **No CSS variables.** `var(--ds-accent)` is an empty string in Outlook. Every colour is a resolved hex, written inline on the element.
- **No stylesheet.** No Tailwind, no classes, no `app.css`. Styles are inline attributes.
- **No web fonts.** The pairing's faces do not load. The second face never appears in an email at all.
- **No reliable dark mode.** Clients invert on their own terms and disagree with each other. An email is built in one register.
- **Images are blocked by default.** Nothing an email needs to say may live only in an image, the brand mark included.

## Structure

One column, 600 pixels at most, centred on a `page` background. Nothing beside anything else; a phone is the common case and two columns collapse badly.

In order:

1. **A preheader.** One sentence, hidden in the body, which the inbox list shows beside the subject. Without it the client picks the first words it finds, which is usually the brand name repeated.
2. **The brand**, as text in the body face. Not an image, because images are blocked.
3. **One message.** A short heading saying what happened, then what it means for the reader, in sentences.
4. **One action**, when there is one. A single button. An email with two competing buttons has no action.
5. **A footer.** Who sent it, why this person received it, and how to stop. Muted text, 12px minimum.

An email carries one subject. Two things worth telling somebody are two emails.

## Type

The body face only, with a system fallback stack after it, because the face will not load and something has to render.

| Role | Size | Weight |
|---|---|---|
| Heading | 20px | 600 |
| Body | 16px | 400 |
| Footer | 12px | 400 |

Body text is 16px rather than the 14px of an app screen. Mail is read at arm's length, on a phone, once.

Line height 1.5 on body text. Left aligned, never justified, and never centred beyond a heading.

## Colour

Resolved hex, inline, from the project's own design file.

| Where | Role |
|---|---|
| Page background | `page` |
| Card background | `surface` |
| Body text | `text` |
| Headings | `text-strong` |
| Footer and secondary | `text-muted` |
| Rules and borders | `border` |
| The one button | `accent` filled, `on-accent` text |
| A status line | the matching status role, as text |

The accent appears once, on the button. A status email may use a status colour as text, on the line that carries the status, and nowhere else.

Never assume the reader's background. A client that inverts should still leave the email legible, so no white text on an image and no colour that only works against one backdrop.

## Buttons

A bulletproof button, built as a table cell with the background on the cell rather than on a styled anchor. A CSS-styled link renders as plain text in Outlook, and a primary action that arrives as plain text is a broken email.

The label says the action: "Pay the invoice", not "Click here". The full URL goes in the footer as text too, for the clients that strip the link.

## Voice

The subject line says what happened, in the words the product uses on its own screens. No urgency that is not real, no emoji, no branding language.

The first sentence repeats it in full, because a notification is often read in a preview pane and never opened.

## What the adapter ships

`<x-design-kit::mail>` is the layout, and it reads the resolved values from `design/design.json` rather than from CSS, so an email matches the app without anything being typed twice. Section 06's components are app components and none of them render in an email.
