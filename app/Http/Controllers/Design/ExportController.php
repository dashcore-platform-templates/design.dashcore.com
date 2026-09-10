<?php

namespace App\Http\Controllers\Design;

use App\Design\Design;
use App\Design\Pack;
use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportController extends Controller
{
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
