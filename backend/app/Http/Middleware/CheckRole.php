<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // 1. Cek apakah user MASIH LOGIN
        if (!auth()->check()) {
            // Jika diakses via API
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated. Silakan login.'], 401);
            }
            // Jika diakses via Web
            return redirect()->route('login')->with('error', 'Sesi habis, silakan login kembali.');
        }

        // 2. Cek apakah role sesuai dengan yang diizinkan
        if (!in_array(auth()->user()->role, $roles)) {
            // Jika diakses via API
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Akses ditolak. Role tidak sesuai.'], 403);
            }
            // Jika diakses via Web
            abort(403, 'Unauthorized action: Anda tidak memiliki akses ke halaman ini.');
        }

        return $next($request);
    }
}