# Handoff: Dashcore design system — problem discovery

This is the note that started design.dashcore.com, exactly as it was handed to the first Claude Code session on 11 September 2026, with Travis's notes appended underneath. It is kept here as the example of the discovery step: problem in the author's words then refined, the unresolved pushback, what is needed before building, the open questions to grill from, and the norms to respect. The grilling skill was run against the open questions; the standard, the viewer and the generator followed from those rounds.

## How the note was produced

The note itself came out of a short claude.ai chat, before any repo was open. The shape of that chat is the method's first step and is worth copying:

1. John typed the problem raw, in his own words, and said "let's discuss, no brainstorm":

   > For every project part of a lifecycle and the hard one is the design. If we have multiple projects, it's hard to come up with a new design mockup without a system. If you try Claude it's hard to generate one without looking templated or generic. So a platform/os for design system for Dashcore would be a great help to scaffold one for every new project we're gonna build.

2. Claude pushed back rather than agreeing: generic output is a missing-constraints problem, a design system is the output of decisions not a generator of them, and the proposal bundles structural reuse (already covered by Starter OS Plus) with per-project identity (the actual hard part). It ended with one question: is there a single Dashcore screen John actually likes?

3. John asked for the problem statement to be reworded twice until it read as he would say it. That reworded text is the "Problem statement" section below. The pushback became the "Pushback raised so far" section, unresolved on purpose.

4. John pointed at Matt Pocock's grilling skill. Claude recognised that its own pushback was the first grilling question in disguise and asked the second: what did John actually do, step by step, the last time he designed a screen.

5. John said "let's hand this off to Claude Code, I'll continue there". The chat was condensed into the note below and pasted into a Claude Code session in the new repo, where the grilling skill ran the rounds.

So the tool split is: claude.ai for the argument that produces the note, Claude Code in the repo for the grill and everything after. Nothing in the chat was a template; the reusable part is the sequence: raw statement, pushback, reword, name the open questions, hand off.

---

## Context
John does client software development under Dashcore (Laravel 13 + Livewire/Volt 4 +
Tailwind v4, Flux UI). Existing reusable base: Starter OS Plus
(starter-os-plus.dashcore.com) — clone-based Laravel starter with feature manifest,
invite-only auth, branded sign-in driven by `config/brand.php`.

## Problem statement (John's words, refined)
Design is currently the hard part of every project lifecycle, and we start it from
nothing each time. Flux and the Starter OS template give a base, but the structure
still has to be built by hand: shells, typography, colors. AI-generated mockups come
out templated/generic. Proposed fix: a Dashcore design system / platform to scaffold
the design layer for every new project.

## Pushback raised so far (unresolved)
- Generic AI output is a missing-constraints problem, not a missing-platform problem.
  With nothing specific to be specific about, the model reaches for the median.
- A design system is the *output* of design decisions, not a generator of them.
  Building the platform before the taste exists encodes taste not yet formed.
- The proposal bundles two things that should be split:
  1. Structural reuse (shells, layout, auth, table/form patterns) — Starter OS Plus
     already covers most of this; `config/brand.php` is the existing seam for
     per-client identity. Extension, not new project.
  2. Per-project visual identity (why app B isn't a recolor of app A) — the actual
     hard part. A constrained decisions doc (type pair, spacing scale, density rule,
     structural motif) may do more than a platform.
- Capacity: John's week is fully committed to Executive OS; a new platform costs build
  time and that trade is Travis's call.

## John's stated needs before building anything
Requirements nailed down, dedicated design time, and a reference to design against.

## Open questions (grill from here)
1. Is there one existing Dashcore screen John actually likes the look of? If yes it's
   the reference and this gets cheap; if no, no platform manufactures it.
2. Step by step, what did John actually do the last time he designed a screen for a
   Dashcore project? (Not the ideal — what happened.)
3. Where does the time actually go: shells, typography/color choices, or
   per-screen layout?
4. Is the deliverable a platform, a Starter OS Plus extension, or a document?

## Engineering norms to respect
Simplest working path first; extra machinery must earn its keep by what the end user
can do without an engineer. Don't branch into other repos inline — capture cross-repo
work as follow-up tickets (DAS prefix in Linear).

---

Here's additional notes from Travis:

"We're gonna make a new app called design.dashcore.com that is the whole design framework for the Dashcore fleet"

"Think of it like a huge mock up for all our views

we have 15 different sites that need to all look and work the same
 "

"ok. See what you can build in this design system.

Start super small. We will test it against our starter kits. It should export something as a json
 "

---

## What happened next, for the record

The first grill round found that open question 1 had a candidate answer (Starter OS Plus already had a designed look) and John rejected it as the reference. The goal was reshaped into a standard plus a constrained generator, the grill restarted from round one against the new goal, and every later decision is logged on Linear issue DAS-2016. The pushback in this note held: the constraint (the standard) is what stopped the generated output being generic, and "a constrained decisions doc" became the seven-decision schema.
