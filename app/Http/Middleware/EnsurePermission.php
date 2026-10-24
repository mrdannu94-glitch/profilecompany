<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Membatasi akses route berdasarkan permission pengguna.
 *
 * Contoh pemakaian: `->middleware('permission:manage_users,manage_roles')`
 */
class EnsurePermission
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        if ($user === null) {
            abort(Response::HTTP_UNAUTHORIZED);
        }

        abort_unless($user->hasPermission($permissions), Response::HTTP_FORBIDDEN);

        return $next($request);
    }
}
