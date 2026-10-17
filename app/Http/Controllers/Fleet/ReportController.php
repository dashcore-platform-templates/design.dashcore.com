<?php

namespace App\Http\Controllers\Fleet;

use App\Design\Standard;
use App\Fleet\GitHubIdentity;
use App\Http\Controllers\Controller;
use App\Models\Site;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Where an app says how far off the standard it is.
 *
 * `php artisan design:audit --report` posts here from a kit's CI. Whatever it
 * carries has to prove which repository it is, so a report cannot claim to be
 * somebody else's.
 *
 * Two ways in, on purpose. A GitHub Actions token proves the repository
 * without anybody pasting a secret anywhere, and a repository proving itself
 * that way joins the board by reporting rather than being declared first. The
 * per-site token stays because every app in the fleet is using one today, and
 * a gate that turns fifteen apps off is not a gate.
 */
class ReportController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $site = $this->speaker((string) $request->bearerToken());

        if ($site === null) {
            // Deliberately the same answer for a missing token and a wrong one.
            return response()->json(['message' => 'Unknown token.'], 401);
        }

        $data = $request->validate([
            'standard_version' => ['nullable', 'string', 'regex:/^[0-9a-f]{6,40}$/'],
            'package_version' => ['nullable', 'string', 'max:40'],
            'design_slug' => ['nullable', 'string', 'regex:/^[a-z0-9-]+$/', 'max:100'],
            'style' => ['nullable', 'string', 'regex:/^[a-z0-9-]+$/', 'max:40'],
            'allowance' => ['nullable', 'integer', 'min:0', 'max:100000'],
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
            'style' => $data['style'] ?? null,
            'allowance' => $data['allowance'] ?? null,
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
            // A style replaces a design rather than sitting beside one, so the
            // report says which and the site follows it either way.
            'style' => $data['style'] ?? (isset($data['design_slug']) ? null : $site->style),
        ])->save();

        // The tool knows what the standard reads today, so it says so on the
        // way back. The app learns it is behind by reporting, with no second
        // call and nobody having to ask.
        return response()->json([
            'standard_version' => Standard::version(),
        ], 201);
    }

    /**
     * Which repository is speaking, or null.
     *
     * A GitHub Actions token is tried first, because it is the one that can
     * create a row: it names a repository GitHub signed for, so accepting it
     * from a repository nobody added here is accepting a fact rather than a
     * claim. The board then shows what is running instead of what somebody
     * remembered to declare.
     *
     * The per-site token cannot do that and never will. It says "whoever holds
     * this", which is only an identity because we issued it to one repository,
     * so an unrecognised one stays a refusal.
     */
    private function speaker(string $bearer): ?Site
    {
        $repository = GitHubIdentity::repository($bearer);

        if ($repository !== null) {
            return Site::firstOrCreate(
                ['repository' => $repository],
                // The name is the repository until somebody gives it a better
                // one. Guessing a product name out of a slug reads worse than
                // the thing it actually is.
                ['name' => $repository],
            );
        }

        return Site::byToken($bearer);
    }
}
