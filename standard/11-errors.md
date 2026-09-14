# 11. Error and empty-app pages

The pages a person reaches when something has gone wrong, or before there is anything to see. Laravel ships its own, and a fleet app that has never touched them is showing a framework's page under a product's name.

<!-- prompt-end -->

These pages are rare, so they are the last thing anybody styles and the first thing that gives an app away. They also arrive at the worst moment, when somebody is already stuck.

## The error pages

Laravel renders 404, 419, 429, 500 and 503. All five take the same layout, and only the words change.

Centred in the page, on `page`, with no shell. There is no sidebar and no top nav, because the page that failed may be the one that draws them.

In order: the brand as text, one short heading, one or two sentences, and one default button back to somewhere that works. No illustration, no large number set in the display face, no apology in three paragraphs.

| Code | Heading | What the sentence says |
|---|---|---|
| 404 | Nothing here | The address is wrong or the thing has been removed. Offer the way back. |
| 419 | That page expired | They sat too long. Ask them to try again; nothing was lost. |
| 429 | Too many attempts | Say how long until they can try again, if the number is known. |
| 500 | Something broke | Say it is ours, not theirs, and that it has been recorded. Never show the exception. |
| 503 | Back shortly | Planned work. Say roughly how long if it is known. |

Say what happened in the words a person would use. "Nothing here" rather than "404 Not Found". The code goes in muted text under the button, for somebody reporting it.

**Never show a stack trace, a file path, a query or an exception message on a page a customer can reach.** That rule is not about design.

## Status colour

None of these pages uses `danger`. A red page tells somebody their data is gone. A 404 is a wrong address, and a 500 is our fault and already logged. The heading is `text-strong` and the page is neutral.

The one exception is a page reporting real data loss, and that wording needs a person, not a template.

## First run

The state where the app is working and there is nothing in it yet. It is not an error, and it is where most apps show an empty shell and let the person guess.

Use `x-ds::empty` in the main region, at the full size, with the button that creates the first thing. The sidebar or top nav stays, because it is the map. Never a modal on first load.

Where the app needs several things set up before it is useful, the first-run screen names them as a short list with the state of each, and does not pretend the app is ready.

## Sign-in as a dead end

A person who follows a link while signed out lands on sign-in. After signing in they go where the link pointed, not to the dashboard, because sending them to the dashboard loses what they came for.

If the link has expired or the account cannot reach it, say which, on the sign-in page, in the status role that fits. Never a blank redirect.

## What the adapter ships

`design:apply` publishes the five error views into `resources/views/errors/`, taking the design's own colours and body face. They are ordinary Blade files afterwards and the wording is meant to be edited per product.
