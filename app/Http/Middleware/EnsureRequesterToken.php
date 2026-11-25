<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class EnsureRequesterToken
{
    public const COOKIE_NAME = 'satudata_requester_token';

    public function handle(Request $request, Closure $next): Response
    {
        $token = (string) $request->cookie(self::COOKIE_NAME, '');

        if ($token === '' || strlen($token) < 16) {
            $token = Str::random(40);
            Cookie::queue(self::COOKIE_NAME, $token, 525600);
        }

        $request->attributes->set('requester_token', $token);

        return $next($request);
    }
}
