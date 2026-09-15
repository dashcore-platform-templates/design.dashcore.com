# 13. Public pages

The pages a signed-out person reaches: the landing page, pricing, legal, contact. Until now the standard exempted them, which was never a decision. Nobody had written the rules, so the audit was told to look away.

<!-- prompt-end -->

An exemption and a rule look the same from inside a repo. A landing page marked `@ds-audit-ignore-file` reads as permission to do anything, and fifteen sites doing anything is the drift this standard exists to stop. A public page is the first thing anybody sees of a product, and it is the surface most likely to arrive looking like every other generated page on the internet.

Sign-in is not a public page for this purpose. It is in 02 and 11.

## What carries over

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

## What a public page never does

These are the tells of a page nobody decided. Each one is banned on its own, and any two of them together make a page that could belong to anybody.

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

These are not preferences. Every one of them is something a generator reaches for when nobody has said otherwise, and the point of this section is to say otherwise.

Where a brand genuinely requires one, that is allowed, and it goes in the design's `decisions` block with the reason, like every other departure.

## Legal pages

Terms, privacy and anything else long. One column, `prose` width from 05, body face, no hero. Headings step down normally and the page is readable at `md` without a sidebar of contents.

These are not marketing pages and they do not take the landing header. The brand mark and a way back is enough.

## What the audit checks

A public page is audited like any other view for colour, spacing, weights and the minimum text size, because it uses the same roles and the same scale.

One rule does not apply to it, the same way it does not apply to a sign-in view: the page title rule, since a hero is not `x-ds::page-header`.

A public page says so once, at the top:

```blade
{{-- @ds-public --}}
```

A marker rather than a folder name, because public is a decision about who reaches a page and no path spells that reliably. Laravel's own `welcome.blade.php` is the exception and needs no marker, since every app has one and it is always the landing page.

`@ds-audit-ignore-file` on a public page is no longer the expected state. That said the page was outside the standard, and from this section on it is inside it. A file still carrying it predates this section, and the fix is to swap the two markers and work the findings.
