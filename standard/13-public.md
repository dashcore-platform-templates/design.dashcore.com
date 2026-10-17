# 13. Public pages

The pages a signed-out person reaches: the landing page, pricing, legal, contact. They are not part of what this standard unifies. The standard is for the signed-in back end, the screens a team works in every day, and a public page keeps its own brand.

<!-- prompt-end -->

A public website, a careers page, a product's marketing pages: each of those has an audience and a brand of its own, and holding them to the back end's style would be the wrong job for this standard. What the fleet shares is the experience inside, so that is what it unifies.

Sign-in is not a public page for this purpose. It is the door to the back end, on the roles and the scale, and it is in 02 and 11.

## Advice, not rules

The rest of this section is advice for anyone building a public page from a design. None of it is audited. It is kept because a public page is the surface most likely to arrive looking like every other generated page on the internet, and these are the things that make it look that way.

## What carries over, when a page is built from a design

Everything that is the brand rather than the furniture.

- **The accent and the neutral ramp**, through the `--ds-*` roles exactly as an app screen uses them. Both registers come for free, so a public page is themed rather than painted.
- **The type pairing**, both faces, with the second face doing what 07 says the motif does.
- **The spacing scale and the radii** from 05.
- **The icon set** from 08. One set, the app's set.
- **The three widths** from 12, and the 80rem content cap from 02.
- **The voice.** Concrete sentences about what the product does. The same rule as everywhere else.

## What does not

- **The shell.** No sidebar, no app top nav. A public page has its own header and footer, and they are not the app's.
- **Density.** Row heights, control heights and table rules are app things. A public page is read, not worked in.
- **`x-ds::page-header`.** That component is the app's page title region. A landing page has a hero, which is a different thing with a different size.

## The shape

In order, and short:

1. **A header.** The brand mark, at most four links, and one action. The action is Sign in, or Get started where there is a signup.
2. **One hero.** One heading, one sentence under it, one button. Nothing above the heading.
3. **What it does.** Three or four claims, each one concrete and each one tied to something real. A screenshot of the actual product beats an illustration of an idea.
4. **One more action**, at the bottom, the same action as the hero.
5. **A footer.** Legal, contact, status, and who owns the thing.

A page that needs more than this is usually a page that has not decided what it is for.

## Type at public scale

03's scale is built for app screens and stops at `text-2xl`. A hero needs more, and this is the one place it is allowed.

| Role | Size | Weight |
|---|---|---|
| Hero heading | 48px, 36px below `md` | 600 |
| Hero sentence | 18px | 400 |
| Section heading | 30px | 600 |
| Body | 16px | 400 |

Nothing goes above 48px. A heading larger than that is a poster, and a poster is not a product page.

## What to avoid

These are the tells of a page nobody decided. Any one of them weakens a page, and any two of them together make a page that could belong to anybody.

- No gradient as a background, and never a purple-to-blue one.
- No gradient fill on text. A heading is one colour.
- No emoji in a heading.
- No badge, pill or chip above the hero heading.
- No glassmorphism: no blurred translucent cards over a busy ground.
- No grain, noise or mesh over a gradient.
- No three icon boxes in a row as the way features are shown.
- No fade-in on scroll, no cursor-following beam, no element that moves because the page loaded. 08 governs motion here too, and motion needs a reason.
- No serif italic used as an accent on a sans page.
- No copy that could be about any product. If the sentence would survive swapping the product name, rewrite it.

Every one of them is something a generator reaches for when nobody has said otherwise, and the point of this section is to say otherwise.

## Legal pages

Terms, privacy and anything else long. One column, body face, no hero, and the 65-character line cap from 03, which is `max-w-prose`. Headings step down normally and the page is readable at `md` without a sidebar of contents.

`--ds-prose` is not that cap. It is the prose type size from 05, and using it as a width gives a column 14 pixels wide.

These are not marketing pages and they do not take the landing header. The brand mark and a way back is enough.

## What the audit checks

Nothing. A public page says so once, at the top, and the audit leaves the whole file alone:

```blade
{{-- @ds-public --}}
```

A marker rather than a folder name, because public is a decision about who reaches a page and no path spells that reliably. Laravel's own `welcome.blade.php` is the exception and needs no marker, since every app has one and it is always the landing page.

The marker is a statement, not a loophole: it says a page is for signed-out people. A back-end screen carrying it is drift the audit cannot see, so it goes on public pages only. `@ds-audit-ignore-file` stays for files outside the standard for another reason, a PDF or an email, with that reason beside it.
