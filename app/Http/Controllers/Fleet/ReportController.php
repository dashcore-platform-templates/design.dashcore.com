<?php

namespace App\Http\Controllers\Fleet;

use App\Design\Standard;
use App\Http\Controllers\Controller;
use App\Models\Site;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Where an app says how far off the standard it is.
 *
 * `php artisan design:audit --report` posts here from a kit's CI. The token
 * says which site is speaking, so a report cannot claim to be somebody else,
 * and a site that was never added here cannot create itself.
 */
class ReportController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $site = Site::byToken((string) $request->bearerToken());

        if ($site === null) {
            // Deliberately the same answer for a missing token and a wrong one.
            return response()->json(['message' => 'Unknown token.'], 401);
        }

        $data = $request->validate([
            'standard_version' => ['nullable', 'string', 'regex:/^[0-9a-f]{6,40}$/'],
            'package_version' => ['nullable', 'string', 'max:40'],
            'design_slug' => ['nullable', 'string', 'regex:/^[a-z0-9-]+$/', 'max:100'],
            // Rule names are the audit's own, and a kit could be running an
            // older package with rules this tool has never heard of, so they
            // are bounded rather than matched against a list.
            'findings' => ['present', 'array', 'max:100'],
            'findings.*' => ['integer', 'min:0', 'max:100000'],
            'files_with_findings' => ['required', 'integer', 'min:0', 'max:100000'],
            'files_checked' => ['required', 'integer', 'min:0', 'max:100000'],
            'reported_at' => ['nullable', 'date'],
        ]);

        /** @var array<string,int> $findings */
        $findings = $data['findings'];

        $report = $site->reports()->create([
            'standard_version' => $data['standard_version'] ?? null,
            'package_version' => $data['package_version'] ?? null,
            'design_slug' => $data['design_slug'] ?? null,
            'findings' => $findings,
            // Summed here rather than taken from the payload, so the number on
            // the page always agrees with the rules beside it.
            'total_findings' => array_sum($findings),
            'files_with_findings' => $data['files_with_findings'],
            'files_checked' => $data['files_checked'],
            // A clock in CI can be wrong, and a report from the future sorts
            // above every real one for ever.
            'reported_at' => now(),
        ]);

        $site->forceFill([
            'last_reported_at' => $report->reported_at,
            'design_slug' => $data['design_slug'] ?? $site->design_slug,
        ])->save();

        // The tool knows what the standard reads today, so it says so on the
        // way back. The app learns it is behind by reporting, with no second
        // call and nobody having to ask.
        return response()->json([
            'standard_version' => Standard::version(),
        ], 201);
    }
}
