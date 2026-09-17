# What changed in the standard

Newest first. Every entry says what moved and whether an app already on the standard has to do anything.

A **relaxation** lets an app do something it could not before. Nothing that was right becomes wrong, so there is nothing to fix.

A **tightening** makes something that was allowed no longer allowed. Existing screens drift the moment it lands. Where the audit can check it, the line says so, and `php artisan design:audit` will find those screens for you.

The full reasoning for each decision is in the cut sheet in the design repository. This file is the short version an app needs.

---

## 17 September 2026

**A repository can report without holding a secret.** Relaxation, no rule changed, and nothing an app has to do. Reporting used to need `DESIGN_TOKEN` in the repository's Actions secrets, which was the only step in setting an app up that needed a browser, which made it the only one an agent could not do, and the one that quietly did not get done. A workflow that asks for `permissions: id-token: write` now gets a short signed document from GitHub naming the repository, the ref and the audience, and reports with that instead. Nothing is stored in the repository and there is nothing to rotate. `DESIGN_TOKEN` still works, so an app that upgrades and changes no workflow keeps reporting exactly as before; this is a minor because nothing that passed stops passing.

Two things follow from it. A repository that can prove itself no longer has to be added to the board first: it appears the first time it reports, so the board shows what is running rather than what somebody remembered to declare. And what is missing is now in a file, which means something can read it: **`php artisan design:connect` is new** and says whether this repository can reach the board at all, naming the workflow that reports, whether that job can prove itself, and whether the step is held to the default branch. Run it whenever the board looks wrong.

**`design:apply` fixes the starter kit's own drift.** No rule changed, and nothing an app has to do. Every kit in the fleet begins from the same Laravel starter kit, so every kit begins with the same lines off the standard: greys where text-muted belongs, a green confirmation where success belongs, one off-scale margin. Those exact lines were fixed by hand in three apps before this, with the same answer each time. `design:apply` now rewrites them, alongside the Lucide renames it already did. Measured on a real untouched starter kit: 19 findings across eight files becomes 7 across three. The seven left are the ones with a decision in them, and they stay with whoever is reading the screen: whether an underlined link should have been a button, and which heading is a section heading rather than a page title. The match is byte for byte, so a line an app has already touched is left alone, because a line somebody edited is a decision rather than drift.

---

## 16 September 2026

**A screen may not restyle the Flux table.** Tightening, and the audit checks it. 06's table styling has been in the applied CSS block since 14 September, and a screen built before that did the job itself, with six arbitrary variant classes reaching into `data-flux-column` and `data-flux-cell`. Its own comment said it belonged in the adapter. Keeping both was worse than either: the screen used `text-muted-on-sunken` for the head and the adapter uses `text-muted`, so the utilities won on the head while the adapter supplied the hover and the first column, and the table matched neither the mockup nor the standard. The audit now reports `[data-flux-table]`, `[data-flux-column]`, `[data-flux-cell]` and `[data-flux-row]` in a view. Measured before shipping it: nothing across the three apps here, because the one screen that had it was fixed first. It is a rule for the next one rather than a bill for anybody.

**A Flux button at `size="sm"` is a tap target finding.** Tightening, and the audit checks it. 05 has always said a touch target is never under 36px, and the rule caught `size="xs"` but not `sm`. Flux's button heights are `h-10` at 40px for `base`, `h-8` at 32px for `sm` and `h-6` at 24px for `xs`, so only `base` clears the floor. The old rule was also inconsistent with itself: a hand-written `h-8` button was already reported while the Flux size that renders as `h-8` was not. 05 now says outright that `base` is the only usable size. Measured across five apps here, it finds 63 in one and 68 in another, which are the same product, and nothing at all in the other three. An inline action in a dense row is a full-height button or it is not a button.

**Three places where 06 and Flux disagreed are settled.** Checked against Flux 2.19 rather than against the note that recorded them, and one of the three turned out not to be a disagreement at all.

**Section headings get a component.** Tightening, and the audit checks it. `flux:heading` cannot make either heading role in 03: `size="lg"` renders 16px at weight 500 where the section heading is 18px at 600, and `size="xl"` renders 24px at 500 where the page title is 24px at 600. The weight is wrong at every size. Its sizes are plain utility classes on the element, so passing a class of your own leaves two competing and the stylesheet order decides rather than you. `x-ds::section-heading` is the role: 18px, 600, body face, for a section heading, a card title or a modal title. It takes the body face and never the second one, because 03 gives the second face the page title, the sign-in headline and stat values, and bars a serif from anything under 24px. The audit reports `flux:heading size="lg"`. The default size is 14px at 500, which is the UI emphasis role, and Flux gets that one right.

**`callout.button` never existed.** No rule changed, 06 named a component that is not there. Flux ships four callout parts, `index`, `heading`, `text` and `link`, and Flux Pro adds none. Anyone following 06 literally got a Blade error. The action on a callout is `callout.link`, an inline underlined link, and "at most one action" is unchanged.

**The toast width was not a disagreement.** No rule changed. 06 has never specified a toast width, so Flux's 384px, dropping to 320px below `sm` inside a group, contradicts nothing. The number is now written into 06 so it does not get reopened as one.

---

## 15 September 2026

**Public pages are governed rather than exempted.** Tightening, and the audit checks most of it. Section 13 is new. A landing page used to carry `@ds-audit-ignore-file`, which was never a decision: nobody had written the rules, so the audit was told to look away, and from inside a repo an exemption and a rule look the same. 13 says what carries over from the app standard (the roles, both registers, the pairing, the spacing scale, the icon set, the three widths, the voice) and what does not (the shell, density, `x-ds::page-header`). It adds the one type step the app scale does not have, a hero at 48px, capped there. It also names ten things a public page never does, each of them something a generator reaches for when nobody has said otherwise: gradients as backgrounds or on text, emoji in headings, a badge above the hero, glassmorphism, grain over a gradient, three icon boxes in a row, scroll fades and cursor beams, serif italic accents, and copy that would survive swapping the product name. A public page now says `@ds-public` instead of opting out, which keeps every rule except the page title, since a hero is not a page header. Laravel's `welcome.blade.php` needs no marker. Swapping the two markers on an existing landing page will produce findings, and that is the point.

---

## 14 September 2026

**An app can ask whether the standard moved.** No rule changed. `php artisan design:check --against=<slug>` compares the stamp in `design/STANDARD.md` with the version the tool is publishing now, and says which. Until this, `design:apply` was the only thing that reported it, so finding out meant re-applying, which is a change made to answer a question. It writes nothing and never changes the exit code, because half the entries in this file are relaxations with nothing to do.

**The Flux table is brought onto 06.** Tightening, and the audit cannot check it, but you get it for nothing on the next `composer update`. 06 has always said the head row sits on sunken at 12px in muted, with body rows at 14px and the row height from density. Flux ships a 14px head in body colour with no fill, so every app using `flux:table` drifted the same way, and the first agent build of a new product corrected it per screen and then said plainly that it belonged in the adapter. It is now in the applied CSS block, which is the same argument the badge override rests on: one fix every app inherits, rather than fifteen apps each fixing it differently. The rules sit outside any `@layer`, because unlayered styles beat Tailwind's utilities without `!important`, and a test checks they stay there.

**Accent text is derived against the accent tint.** Tightening, and the audit cannot check it, but `design:check` now can. Found by the first agent build of a new product. 04 claimed accent text "always passes contrast", which was false: `accent-content` was derived against the page, and nothing measured it against the tint 04 itself puts behind every selected row and active list item. Measured across all eight designs, 10 of 16 pairs failed, worst 3.40:1 in dark against a floor of 4.5, while every design reported passing. It is now derived against the tint, which is the worst of the three surfaces accent text lands on, so passing there means passing on page and surface too. Secondary text on that tint takes `text-muted-on-sunken`, the same role a status tint already used. Both pairs are measured in the tool and in the package's copy of the gate, so 46 pairs are checked per design instead of 42. Every design still passes and most light values did not move; the dark values are lighter than before. This is the same fault as the status tint one below, in the same file, and it went unnoticed for longer.

**Form fields break at `md`, not `lg`.** Relaxation, correcting the entry below on the day it was written. 12 first said forms are one column below `lg`, which would have made a tablet show one narrow column with empty space beside it. Building the mockups against the new canvas showed it immediately. Fields are one column below `md` and may be two from `md`, where each column still holds a label and its control without wrapping. The phone case, which was the point, is unchanged.

**The fleet has named widths.** Tightening, and the audit cannot check it. Section 12 is new. The standard described what happens on a small screen four times and never said how small: section 02 said "on mobile" three times and named a breakpoint once. Three people read that and pick three widths, which is drift written into the standard itself. The scale is Tailwind's, because Flux is built on it and naming our own would mean fighting a component library in every app. The fleet designs at three widths and not five: phone below `md`, tablet from `md` to below `lg`, desktop at `lg` and up. Nothing new happens above that, since 02 already caps content at 80rem. The sidebar is a drawer below `lg` and the top nav collapses below `md`, which differ on purpose, because five nav items fit across 768px and a 16rem sidebar beside content does not. Those three "on mobile" phrases in 02 now name their width. A table a customer reaches stops being a table below `md`; a staff-only table may scroll sideways instead, and which applies is a per-screen decision that belongs in that screen's commit message.

**The tap target floor is checked.** No rule changed, and the audit now checks it. Section 05 has always said a tap target is never under 36px, which is the compact control height, but nothing enforced it. The audit reports an interactive element sized under that by its own class attribute, and a Flux button at `size="xs"`, which is about 24px. It reads the element's own classes rather than the line, so an icon at 20px inside a 40px button is not a finding. It found nothing on either app tested, so the fleet already clears the floor.

**Email has a section.** Tightening, and the audit cannot check it. Section 10 is new. Every app in the fleet was sending Laravel's default template, so a member got a portal built to this standard and then an email that looked like nothing to do with it. Email is the one surface where the rest of the standard cannot apply: no custom properties, no utility classes, no web fonts, no second typeface and no dark register. What carries over is the accent, the neutral scale, the voice and the structure, all of it in inline hex. The audit does not see mail templates, so a person has to look.

**Error pages and first run have a section.** Tightening, and the audit checks part of it. Section 11 is new. Laravel's own 404, 419, 429, 500 and 503 were untouched everywhere, which is a framework's page under a product's name, arriving at the moment somebody is already stuck. None of them uses the danger colour, because a red page tells somebody their data is gone. The audit walks `resources/views/errors/` like any other view, so colour and spacing there are checked, but it cannot tell you the pages were never written.

**A badge with no status is neutral.** Tightening, and the audit checks it. Roles, plans, types, categories and any state the four status colours do not cover now take `<x-ds::badge>` with no status. Colour on a badge means status and nothing else. An unrecognised status renders neutral instead of falling back to info. A count is not a badge at all. The audit reports every `flux:badge`.

**A count marker takes the accent.** Tightening, and the audit checks it. An unread count on a nav item or a notification bell fills with the accent and uses `on-accent` text at `text-xs`. It is never red, because red says something has gone wrong and unread items have not. The audit reports a red fill as a colour off the roles.

**Muted text on a status tint uses the 600 step.** Tightening, not checkable by the audit. Section 04 allows a status tint behind a row, and muted text at the 500 step measures 4.35:1 on the danger tint, under the floor. Use `text-(--ds-text-muted-on-sunken)` on any tinted row. `design:check` now measures both tint pairs for every status, so a design cannot ship below the floor.

**The empty state has a compact size.** Relaxation, not checkable. `size="compact"` drops the 320 pixel minimum and the button, for a panel inside a split region. The full size is unchanged.

**A navlist may appear in content.** Relaxation, not checkable. A section with its own destinations, such as settings, may put one vertically beside the panel it switches, one level deep and with no nesting. It is a sub-navigation and never a second main nav.

**An app without Flux Pro is on the standard.** Relaxation, not checkable. Section 06 names a free stand-in for eleven of the nineteen Pro components, and the rule for each is the Pro rule unchanged. The Dashcore licence covers every site, so installing Pro is still the expected end state, but a site that has not yet is behind on an install rather than outside the rules.

**The fleet is defined.** No rule changed. The word appears throughout and was never explained, so the pack now says what it means and why fifteen sites is the test a rule has to pass.

---

## 12 September 2026

**Zinc is the design, not drift.** No rule changed, but the guidance was contradicting itself. `design:apply` redefines the whole zinc scale as the chosen neutral ramp, so zinc utilities already resolve to the design and the audit is right to ignore them. The migration guide used to list them among the rewrites, which made a clean file look dirty. Moving zinc onto the roles is optional polish, not a finding.

**Public pages are exempted before the mechanical pass.** No rule changed, an ordering bug in the migration guide. The pass used to run over everything first and exempt the public pages afterwards, so it rewrote the pages it was about to exclude.
