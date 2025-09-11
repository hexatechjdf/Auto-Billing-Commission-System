<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyGhlSsoToken
{

    public function handle(Request $request, Closure $next)
    {

      

        // Get the GHL SSO token from the request header
        $ghlSsoToken = $request->header('ghlAuthorization');

        if (! $ghlSsoToken) {
            return response()->json(['error' => 'GHL SSO token missing'], 401);
        }

        $validSSO = \App\Helper\CRM::decryptSSO($ghlSsoToken);

        if (! $validSSO) {
            return response()->json(['error' => 'Invalid GHL SSO token'], 401);
        }

        $ssoLocationId = $validSSO['activeLocation'];

        if (! assertLocationUserLogin($ssoLocationId)) {
            return response()->json(['error' => 'User with this locatonId not exist'], 401);
        }

        // Share with all views
        // View::share('validSSO', $validSSO);

        $request->attributes->add(compact('validSSO')); // Add data to request attributes for controller access

        return $next($request); // If the token is valid, continue with the request

    }
}