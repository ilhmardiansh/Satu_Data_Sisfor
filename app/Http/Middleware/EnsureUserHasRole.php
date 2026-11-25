<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        if (! $user) {
            return redirect()->route('login');
        }

        $allowedRoles = [];
        foreach ($roles as $roleGroup) {
            foreach (preg_split('/[,|]/', $roleGroup, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $role) {
                $allowedRoles[] = trim($role);
            }
        }

        if (! in_array($user->role, $allowedRoles, true)) {
            if ($request->expectsJson()) {
                abort(403, 'Akses ditolak.');
            }

            return response()->view('panel.forbidden', [
                'allowedRoles' => $allowedRoles,
            ], 403);
        }

        return $next($request);
    }
}
