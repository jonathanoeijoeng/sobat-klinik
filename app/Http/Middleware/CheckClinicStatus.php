<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class CheckClinicStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    // app/Http/Middleware/CheckClinicStatus.php

    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        // Jika user tidak punya klinik atau admin sistem (optional check)
        if (!$user || !$user->clinic) {
            return $next($request);
        }

        $clinic = $user->clinic;
        $today = now()->startOfDay();

        // Logic pengecekan active_until
        if ($clinic->active_until && $today->gt($clinic->active_until)) {
            // Logout user atau arahkan ke halaman "Inactive"
            Auth::logout();

            return redirect()->route('login')->with(
                'error',
                "Akses dihentikan. Masa aktif {$clinic->name} telah berakhir pada " .
                    $clinic->active_until->format('d/m/Y')
            );
        }

        return $next($request);
    }
}
