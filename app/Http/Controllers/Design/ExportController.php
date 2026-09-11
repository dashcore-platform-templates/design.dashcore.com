<?php

namespace App\Http\Controllers\Design;

use App\Design\Design;
use App\Design\Pack;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportController extends Controller
{
    /**
     * The pack as one JSON document: the same four files as the zip, for
     * `php artisan design:apply <slug>` in a kit to pull into design/.
     */
    public function manifest(string $slug): JsonResponse
    {
        abort_unless(Design::exists($slug), 404);

        $design = Design::load($slug);

        return response()->json(Pack::manifest($design, route('designs.show', $slug)), 200, [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    public function json(string $slug): Response
    {
        abort_unless(Design::exists($slug), 404);

        return response(Design::load($slug)->toJson(), 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename="design.json"',
        ]);
    }

    public function pack(string $slug): BinaryFileResponse
    {
        abort_unless(Design::exists($slug), 404);

        $design = Design::load($slug);

        return response()->download(Pack::build($design), Pack::filename($design))->deleteFileAfterSend();
    }
}
