<?php

namespace Database\Factories;

use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Site>
 */
class SiteFactory extends Factory
{
    /** @return array<string,mixed> */
    public function definition(): array
    {
        $word = $this->faker->unique()->domainWord();

        return [
            'name' => Str::headline($word),
            'repository' => "dashcoretech/{$word}.dashcore.com",
            'design_slug' => null,
            // A real token is issued through the model. This is only so a row
            // can exist without one, since the column cannot be null.
            'token_hash' => Site::hash(Str::random(40)),
            'last_reported_at' => null,
        ];
    }
}
