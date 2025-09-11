<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\LocationSetting;
use App\Models\User;
use Illuminate\Http\Request;

class AutoAuthController extends Controller
{

    protected const VIEW = 'autoauth';

    public function authChecking(Request $req)
    {

        if ($req->ajax()) {
            if ($req->has('location') && $req->has('token')) {
                $location = $req->location;
                $with     = 'token';
                $user     = User::with($with)->where('location_id', $req->location)->first();
                $isNew    = false;
                if (! $user) {
                    $user              = new User();
                    $user->name        = 'Location User';
                    $user->email       = $location . '@autoauth.net';
                    $user->password    = bcrypt('autoauth_' . $location);
                    $user->location_id = $location;
                    $user->ghl_api_key = '-';
                    $isNew             = true;
                    $user->save();
                }
                $user->ghl_api_key = $req->token;
                if (! $isNew) {
                    $user->save();
                }

                $res                = new \stdClass;
                $res->user_id       = $user->id;
                $res->location_id   = $user->location_id ?? null;
                $res->is_crm        = false;
                $res->token         = $user->ghl_api_key;
                $token              = $user->{$with} ?? null;
                $res->crm_connected = false;

                if ($token) {
                    // request()->code = $token;
                    list($tokenx, $token) = \CRM::go_and_get_token($token->refresh_token, 'refresh', $user->id, $token);
                    $res->crm_connected   = $tokenx && $token;
                }
                if (! $res->crm_connected) {
                    $res->crm_connected = \CRM::ConnectOauth($req->location, $res->token, false, $user->id);
                }
                if ($res->crm_connected) {
                    if (\Auth::check()) {
                        \Auth::logout();
                        sleep(1);
                        //return response()->json(['logout user']);
                    }
                    \Auth::login($user);
                    if ($isNew) {
                        $this->getAndSaveLocationTimeZone($user);
                    }
                }

                $res->is_crm   = $res->crm_connected;
                $res->token_id = encrypt($res->user_id);
                $res->route    = route('location.home');

                return response()->json($res);
            }
        }
        return response()->json(['status' => 'invalid request']);
    }

    protected function getAndSaveLocationTimeZone(User $user)
    {
        $locationDetails = \CRM::getLocation($user->token, $user->location_id);

        if ($locationDetails) {
            $locationTimeZone = $locationDetails->timezone ?? null;

            if ($locationTimeZone) {
                LocationSetting::updateOrCreate(
                    ['location_id' => $user->location_id],
                    [
                        'timezone' => $locationTimeZone,
                        'user_id'  => $user->id,
                    ]
                );
            }
        }
    }

    public function connect()
    {
        return view(self::VIEW . '.connect');
    }

    public function authError()
    {
        return view(self::VIEW . '.error');
    }
}
