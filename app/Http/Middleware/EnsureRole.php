<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Membatasi akses route berdasarkan role pengguna.
 *
 * Contoh pemakaian: `->middleware('role:admin,cs_admin')`
 */
class EnsureRole
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if ($user === null) {
            abort(Response::HTTP_UNAUTHORIZED);
        }

        abort_unless($user->hasRole($roles), Response::HTTP_FORBIDDEN);

        return $next($request);
    }
}
