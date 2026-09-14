# What changed in the standard

Newest first. Every entry says what moved and whether an app already on the standard has to do anything.

A **relaxation** lets an app do something it could not before. Nothing that was right becomes wrong, so there is nothing to fix.

A **tightening** makes something that was allowed no longer allowed. Existing screens drift the moment it lands. Where the audit can check it, the line says so, and `php artisan design:audit` will find those screens for you.

The full reasoning for each decision is in the cut sheet in the design repository. This file is the short version an app needs.

---

## 14 September 2026

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
