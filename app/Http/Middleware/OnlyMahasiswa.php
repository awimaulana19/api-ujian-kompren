<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OnlyMahasiswa
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (request()->is('api*')) {
            if ($request->user()->roles != 'mahasiswa') {
                return response()->json([
                    'success' => false,
                    'message' => 'Token Tidak Valid, Anda Bukan Mahasiswa',
                    'data' => null
                ], 404);
            }

            if (!$request->user()->is_verification) {
                if ($request->user()->tolak) {
                    return response()->json([
                        'success' => false,
                        'message' => 'di_tolak',
                        'data' => $request->user()
                    ]);
                }

                return response()->json([
                    'success' => false,
                    'message' => 'belum_verifikasi',
                    'data' => $request->user()
                ]);
            }

            return $next($request);
        } else {
            if (Auth::user()->roles != 'mahasiswa') {
                return redirect('/login');
            }

            return $next($request);
        }
    }
}
