<?php

/*
|--------------------------------------------------------------------------
| Design generator
|--------------------------------------------------------------------------
|
| Phase 2 of design.dashcore.com. The model is a config value on purpose.
| Built on Opus 5, then compared on one brief against Sonnet 5 and Fable 5.1
| (DAS-2016, 11 Sep 2026): Fable gave the sharpest reasons and resolved the
| split the others left open, so it is the default. A generation happens a
| few times per project, so the price difference is cents. Change
| DESIGN_MODEL in .env, nothing else.
|
*/

return [
    'api_key' => env('ANTHROPIC_API_KEY'),

    'model' => env('DESIGN_MODEL', 'claude-fable-5-1'),

    // Seven constrained picks and three lines of copy do not need deep thought.
    'effort' => env('DESIGN_EFFORT', 'medium'),

    'max_tokens' => (int) env('DESIGN_MAX_TOKENS', 2000),

    // Seconds the HTTP client waits for a reply.
    'timeout' => (int) env('DESIGN_TIMEOUT', 90),

    // Keep the raw model response under storage/app/generations for the first
    // few real projects. Gitignored. Turn off once the generator is trusted.
    'keep_raw' => (bool) env('DESIGN_KEEP_RAW', true),
];
