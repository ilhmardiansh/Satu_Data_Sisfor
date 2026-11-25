<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckIdleSession
{
    public const IDLE_SECONDS = 1800;

    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();

            if (! $user->aktif) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')
                    ->with('error', 'Akun Anda dinonaktifkan. Hubungi Admin prodi.');
            }

            $lastActivity = $request->session()->get('last_activity_at');
            $now = now()->timestamp;

            if (is_int($lastActivity) && ($now - $lastActivity) >= self::IDLE_SECONDS) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')
                    ->with('notice', 'Sesi Anda berakhir karena tidak ada aktivitas selama 30 menit. Silakan masuk kembali.');
            }

            $request->session()->put('last_activity_at', $now);
        }

        return $next($request);
    }
}
