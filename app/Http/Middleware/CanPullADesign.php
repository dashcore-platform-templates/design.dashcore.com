<?php

namespace App\Http\Middleware;

use App\Models\PersonalToken;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Who may pull a design pack.
 *
 * A pack carries the brief, which names the client and says what their product
 * does. That is the one genuinely private thing in an export, and until now
 * anybody who guessed a slug could read it.
 *
 * Two ways through, because two different things pull. A person clicking
 * Download pack has a session. `design:apply` on a laptop has the token from
 * that person's account page, in DESIGN_TOKEN.
 *
 * CI is not one of them and does not need to be: a workflow reports and never
 * pulls. That is why this takes a person's token rather than a repository's,
 * and why there is no way for a repository to read a pack on its own.
 */
class CanPullADesign
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() || PersonalToken::byToken((string) $request->bearerToken()) !== null) {
            return $next($request);
        }

        // Said to a command rather than to a browser, because that is what is
        // almost always on the other end. The site is named so the message
        // works when DESIGN_SOURCE points somewhere other than production.
        $site = rtrim((string) config('app.url'), '/');

        return response()->json([
            'message' => 'This needs your token. Make one at '.$site.'/account and put it in this app\'s .env as DESIGN_TOKEN.',
        ], 401);
    }
}
