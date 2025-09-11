<?php
namespace App\Http\Middleware;

use Auth;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            if (loginUser()->role == 1) {
                return $next($request);
            }
            // return redirect()->route("location.contacts.index");
            // return redirect()->route("admin.settings");
        }
        return redirect()->route("login");
    }
}
