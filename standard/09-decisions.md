# 09. Decisions and the JSON

This is the contract between the generator and everything downstream. The generator receives a brief and returns exactly the seven decisions below, each from its allowed list, plus a one-line reason for each. The resolver turns the decisions into tokens. The viewer and the adapter read only the tokens.

## The brief

Written by a person. The generator reads it; it does not fill it in.

| Field | Required | Notes |
|---|---|---|
| `client` | yes | Who the app is for. |
| `product` | yes | One or two sentences: what the app does and who uses it day to day. |
| `shell` | yes | `sidebar` or `top-nav`. A person decides this, per 02. |
| `brand_colour` | no | A hex the client already owns. If given, the accent is derived from it rather than chosen. |
| `logo` | no | A path to an SVG or PNG. Shown in the brand block and on sign-in. |
| `notes` | no | Anything else: an existing website, a mood, a thing to avoid. |

## The seven decisions

| Decision | Allowed values | How the generator should reason |
|---|---|---|
| `accent` | A hex. Hue at least 30 degrees from 0 and from 40; HSL saturation 45 to 85 percent. If `brand_colour` is given, use it or the nearest value that satisfies the rules. | The one colour that will mark every primary action. Read the client's field and audience. Avoid the hue every competitor in that field uses. |
| `temperature` | `pure`, `cool`, `warm` | Pure for finance and technical work. Cool for software and data. Warm for people, hospitality, health. Should sit well beside the accent. |
| `pairing` | An id from the approved list in 03 | Match the second face's kind to the content: mono when rows carry codes, serif when trust and calm matter, display when the client has some personality. |
| `radius` | `sharp`, `soft`, `round` | Sharp for dense data. Soft when there is no reason. Round for consumer and people-facing. |
| `density` | `comfortable`, `compact` | Compact only when trained staff use it all day. |
| `motif` | `accent-bar`, `hairline`, `eyebrow`, `filled-stat` | See 07 for what each suits. Never pick eyebrow with a pairing that has no mono face unless the brief is technical. |
| `copy` | `eyebrow` (up to 4 words, optional), `headline` (up to 8 words), `tagline` (one sentence, up to 16 words), `footer` (optional; defaults to a copyright line) | About the client and what the user came to do. No software words, no buzzwords, no exclamation marks. |

Each decision carries a `why`: one sentence, in plain language, that a person can agree or disagree with. The viewer shows it under the control.

## Constraints the generator must respect

- Every value comes from the lists above. There is no `other`.
- The seven decisions are the whole output. No extra colours, no extra fonts, no layout suggestions, no component changes.
- Two briefs that differ should produce decisions that differ in at least three of the seven, unless the briefs are near-identical.
- When the brief gives a `brand_colour` that fails the accent rules, the generator says so in `why` and returns the nearest passing value.

<!-- prompt-end -->

## The JSON

Two layers. `decisions` is what the generator wrote and a person may edit. `tokens` is what the resolver derived and nobody edits by hand.

```json
{
  "schema": "dashcore-design/1",
  "generated_at": "2026-09-11T10:00:00Z",
  "brief": {
    "client": "Harbor Point Physical Therapy",
    "product": "Front-desk and clinician scheduling for a three-location PT practice. Used all day by reception and therapists.",
    "shell": "sidebar",
    "brand_colour": null,
    "logo": null,
    "notes": "Older patients see the sign-in page. Nothing that looks like a bank."
  },
  "decisions": {
    "accent":      { "value": "#0f766e", "why": "A deep teal reads as clinical without being cold, and is far from the reds and ambers used for alerts." },
    "temperature": { "value": "warm", "why": "Warm greys soften a screen used by patients and staff in a care setting." },
    "pairing":     { "value": 10, "why": "Atkinson Hyperlegible Next for an older audience; the mono second face handles appointment codes." },
    "radius":      { "value": "soft", "why": "No reason in the brief to go sharp or round." },
    "density":     { "value": "compact", "why": "Reception uses it all day and works from the schedule table." },
    "motif":       { "value": "eyebrow", "why": "A mono pairing and a schedule full of codes suit uppercase labels." },
    "copy": {
      "eyebrow":  "Harbor Point",
      "headline": "Today's schedule, all three clinics.",
      "tagline":  "Sign in to see who is coming in and who is running late.",
      "footer":   null,
      "why": "Speaks to what reception opens the app to do."
    }
  },
  "tokens": {
    "fonts": {
      "sans":    { "family": "Atkinson Hyperlegible Next", "bunny": "atkinson-hyperlegible-next", "weights": [400, 500, 600], "fallback": "ui-sans-serif, system-ui, sans-serif" },
      "second":  { "family": "Spline Sans Mono", "bunny": "spline-sans-mono", "weights": [400, 500], "kind": "mono", "fallback": "ui-monospace, monospace" }
    },
    "radius": { "sm": "4px", "md": "6px", "lg": "8px", "xl": "12px", "2xl": "16px" },
    "density": {
      "gutter": "24px", "section": "24px", "card": "16px", "row": "44px", "control": "36px", "prose": "14px"
    },
    "neutral": {
      "50": "#fafaf9", "100": "#f5f5f4", "200": "#e7e5e4", "300": "#d6d3d1", "400": "#a8a29e",
      "500": "#78716c", "600": "#57534e", "700": "#44403c", "800": "#292524", "900": "#1c1917", "950": "#0c0a09"
    },
    "light": {
      "page": "#ffffff", "surface": "#ffffff", "sunken": "#fafaf9",
      "border": "#e7e5e4", "border-strong": "#d6d3d1",
      "text-strong": "#0c0a09", "text": "#292524", "text-muted": "#78716c", "text-muted-on-sunken": "#57534e",
      "accent": "#0f766e", "accent-hover": "#115e59", "accent-content": "#0f766e", "accent-soft": "#0f766e1a", "on-accent": "#ffffff"
    },
    "dark": {
      "page": "#1c1917", "surface": "#292524", "sunken": "#0c0a09",
      "border": "#44403c", "border-strong": "#57534e",
      "text-strong": "#fafaf9", "text": "#e7e5e4", "text-muted": "#a8a29e",
      "accent": "#14b8a6", "accent-hover": "#2dd4bf", "accent-content": "#2dd4bf", "accent-soft": "#14b8a629", "on-accent": "#0c0a09"
    },
    "status": {
      "success": { "text-light": "#15803d", "text-dark": "#4ade80", "fill": "#15803d" },
      "warning": { "text-light": "#b45309", "text-dark": "#fbbf24", "fill": "#b45309" },
      "danger":  { "text-light": "#b91c1c", "text-dark": "#f87171", "fill": "#dc2626" },
      "info":    { "text-light": "#1d4ed8", "text-dark": "#60a5fa", "fill": "#1d4ed8" }
    },
    "contrast": {
      "passes": true,
      "lowest": { "pair": "text-muted on page, light", "ratio": 4.8 }
    }
  }
}
```

The `contrast` block is written by the resolver after it measures every text role against its surface in both registers. A file with `passes: false` is not exported.

## The export pack

The viewer exports a zip, not a bare JSON, because two different things consume it: an artisan command that does the mechanical part, and an agent coder that does the judgment part and keeps the project on standard after day one. The pack has four files:

| File | For | Contents |
|---|---|---|
| `design/design.json` | The command and the agent | The file above. |
| `design/STANDARD.md` | The agent | Every section of this standard but the audit, 01 to 13, concatenated and whole. |
| `design/CLAUDE.snippet.md` | The target repo's CLAUDE.md | A short block to paste in: before building or changing any screen, read `design/design.json` and `design/STANDARD.md`, use only the roles, components and motif they name, and run the contrast check. |
| `design/MIGRATION.md` | Existing apps only | The rejected-by-name list from the audit as a checklist: replace placeholder layouts, swap Lucide names, lift hardcoded colours onto roles, adopt the three custom components, fix dark muted text. |

The `design/` folder is committed to the target repo. From then on every agent session in that repo builds against it without anyone restating the design.

For a new project: unzip into the fresh kit, run the command below, commit `design/`. For an existing Flux app: unzip, run the command for the retheme, then work through `MIGRATION.md` with an agent.

## What the adapter does with it

In a fresh Laravel Livewire Flux kit, one artisan command reads the file and:

- Writes `--color-zinc-50` through `950` from `tokens.neutral`, `--color-accent`, `--color-accent-content`, `--color-accent-foreground` for light in `@theme` and for dark on `.dark`, the `--radius-*` variables, `--font-sans` and `--font-mono` or `--font-display`, and the `--ds-*` roles, into `resources/css/app.css` between two marker comments.
- Replaces the Bunny font entry in `vite.config.js` with the two faces and their weights.
- Writes `copy` into the `.env` keys that `config/brand.php` reads.
- Copies the logo to `public/` if one was given.
- Removes the four Lucide-named icon files the kit ships.

Nothing else in the kit changes. Running it twice with the same file is a no-op.

## A style instead of a design

A Dashcore app takes the `dashcore` style (14) rather than a design of its own. Its `design/design.json` names the style and holds nothing else:

```json
{
  "schema": "dashcore-design/1",
  "style": "dashcore"
}
```

`php artisan design:apply --style=dashcore` writes that file, and points the app at the package rather than copying anything out of it:

- Two lines in `resources/css/app.css`, straight after Tailwind: an import of the style's stylesheet from `vendor/dashcore/design-kit/dist/`, and the `@source` line for the package's views. An applied design block, or `dashcore/ui`'s two lines, are removed.
- The frame's script in `resources/js/app.js`, in place of `dashcore/ui`'s.
- The two faces, where a design's would go.
- `DESIGN_MOTIF`, `DESIGN_SECOND_KIND` and `DESIGN_CONDENSED` in `.env`. Sign-in copy is the app's own and is left alone.

The style's values live in the package and nowhere in the app, so a release of `dashcore/design-kit` changes every app on the style at once, with nothing to re-apply. `design:update` on an app on a style re-applies it and audits.

## The allowance

An app joins the standard where it is, not where it should be. `php artisan design:audit --baseline` records what the audit finds that day, by rule, in `design/allowance.json`, and from then on `design:audit` fails only when a rule's count rises above it. The file is committed.

```json
{
  "known": ["Flux badge", "hardcoded colour", "…every rule the package knew"],
  "rules": { "hardcoded colour": 14, "spacing off the scale": 31 }
}
```

The allowance only shrinks. Recording it again keeps the lower count for every rule, so a screen fixed stays fixed, and raising a count is an edit to the file that a reviewer sees. A rule the package knew and the app had none of is held at zero. A rule a newer release added, which `known` does not list, is allowed at whatever it finds until the app records it, so a new rule never turns a build red on the day it ships. `--report` never fails, allowance or not.
