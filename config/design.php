<?php

/*
|--------------------------------------------------------------------------
| Design generator
|--------------------------------------------------------------------------
|
| Phase 2 of design.dashcore.com. The model is a config value on purpose:
| the decision was to build on Opus 5, compare with Sonnet 5 on the
| acceptance test, and move to Fable only if the copy or reasoning is
| visibly better. Change DESIGN_MODEL in .env, nothing else.
|
*/

return [
    'api_key' => env('ANTHROPIC_API_KEY'),

    'model' => env('DESIGN_MODEL', 'claude-opus-5'),

    // Seven constrained picks and three lines of copy do not need deep thought.
    'effort' => env('DESIGN_EFFORT', 'medium'),

    'max_tokens' => (int) env('DESIGN_MAX_TOKENS', 2000),

    // Seconds the HTTP client waits for a reply.
    'timeout' => (int) env('DESIGN_TIMEOUT', 90),

    // Keep the raw model response under storage/app/generations for the first
    // few real projects. Gitignored. Turn off once the generator is trusted.
    'keep_raw' => (bool) env('DESIGN_KEEP_RAW', true),
];
