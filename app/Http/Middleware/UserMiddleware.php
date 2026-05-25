<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class UserMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::guard('web')->check()) {
            return redirect()->route('user.login')->with('inactive_user_error', 'Please login first.');
        }

        $user = Auth::guard('web')->user();

        if ($user && ! $user->isActiveStatus()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('user.login')
                ->with('inactive_user_error', 'Your account has been marked inactive by the admin.');
        }

        return $next($request);
    }
}
