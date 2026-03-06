<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeadersMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // 1. Mencegah Clickjacking (Halaman tidak boleh dimuat dalam iframe situs lain)
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // 2. Mencegah MIME-sniffing (Memaksa browser mengikuti tipe file yang ditentukan server)
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // 3. Mengontrol informasi pengirim (Referrer) untuk menjaga privasi URL
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // 4. Mencegah serangan XSS (Basic Cross-Site Scripting Protection)
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // 5. Content Security Policy (Hanya mengizinkan sumber data yang dipercaya)
        // Kita gunakan set yang relatif longgar namun aman untuk Laravel standard
        $response->headers->set('Content-Security-Policy', "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net; img-src 'self' data: https:; connect-src 'self' https:;");

        // 6. Permissions Policy (Membatasi fitur browser yang tidak diperlukan)
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), browsing-topics=()');

        return $response;
    }
}
