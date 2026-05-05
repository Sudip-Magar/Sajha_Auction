<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureGoogleSession
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!session('google_user')) {
            return redirect()->route('login');
        }

        // If trying to access complete-profile without OTP verified
        if ($request->routeIs('complete.profile') && !session('otp_verified')) {
            return redirect()->route('verify.otp');
        }

        return $next($request);
    }
}
