<?php

namespace App\Http\Middleware;

use App\Helper\Dropshipzone;
use App\Models\DropshipzoneToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Auth;
use Carbon\Carbon;
class LocationMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            if (loginUser()->role == 2) {
                return $next($request);
            }
            return redirect()->route("admin.settings"); // old-route: without-s
        }
        return redirect()->route("login");
    }
}
