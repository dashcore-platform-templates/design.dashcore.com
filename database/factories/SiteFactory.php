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
        $host = $this->faker->unique()->domainWord().'.dashcore.com';

        return [
            'name' => Str::headline(explode('.', $host)[0]),
            'host' => $host,
            'design_slug' => null,
            // A real token is issued through the model. This is only so a row
            // can exist without one, since the column cannot be null.
            'token_hash' => Site::hash(Str::random(40)),
            'last_reported_at' => null,
        ];
    }
}
