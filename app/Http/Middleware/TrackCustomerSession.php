<?php

namespace App\Http\Middleware;

use App\Models\CustomerSession;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class TrackCustomerSession
{
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        if (!Auth::check()) {
            return $next($request);
        }

        $token = $request->session()->get('customer_session_token');

        if (!$token) {
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your session has expired or was revoked.',
                ], 401);
            }

            return redirect('/login');
        }

        $session = CustomerSession::where('session_token', $token)
            ->where('customer_id', Auth::id())
            ->first();

        if (!$session) {
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your session has been revoked.',
                ], 401);
            }

            return redirect('/login');
        }

        $session->update([
            'last_activity_at' => now(),
        ]);

        return $next($request);
    }
}