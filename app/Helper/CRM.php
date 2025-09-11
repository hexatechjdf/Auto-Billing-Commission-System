<?php
namespace App\Helper;

use App\Helper\gCache;
use App\Models\CrmToken;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use stdClass;

class CRM
{

    protected static $base_url = 'https://services.leadconnectorhq.com/';
    protected static $version  = '2021-07-28';
    protected static $crm      = CrmToken::class;
    public static $lang_com    = 'Company';
    public static $lang_loc    = 'Location';

    protected static $userType = ['Company' => 'company_id', 'Location' => 'location_id'];

    public static $scopes = "locations.readonly contacts.write contacts.readonly oauth.write oauth.readonly saas/location.read saas/location.write saas/company.read companies.readonly locations.write products.readonly products/prices.readonly products/collection.readonly invoices.readonly invoices.write marketplace-installer-details.readonly payments/orders.readonly"; // locations.write saas/company.write

    protected static $no_token       = 'No Token';
    protected static $no_record      = 'No Data';
    protected static $cacheTokenTime = 3 * 60;

    public static function getDefault($key, $def = '')
    {
        $def = supersetting($key, $def);
        //uncomment above function and change with desired function
        return $def;
    }

    public static function getTokenByLocation($location_id)
    {
        return self::getCrmToken(['location_id' => $location_id]);
    }

    public static function getUpdatedToken($location_id, $user_id = null, $bypassConnect = false)
    {
        $today     = now()->toDateString();
        $token     = self::getTokenByLocation($location_id);
        $tokenDate = '';
        if ($token) {
            $tokenDate = $token->updated_at->toDateString();
        }

        if ($today !== $tokenDate && ! $bypassConnect) {
            if (! $user_id) {
                $user_id = $token->user_id ?? 1;
            }
            $token = self::getLocationAccessToken($user_id, $location_id);

        }
        return $date;
    }

    public static function getCrmToken($where = [])
    {

        $key = '';
        if (isset($where['location_id'])) {
            $key = 'loc_' . $where['location_id'];
        } elseif (isset($where['company_id'])) {
            $key = 'comp_' . $where['company_id'];
        } else {
            $key = json_encode($where);
        }

// gCache::del('loc_iH97EKkQZYraKdjvKRN0');

        // gCache::del($key);
        return gCache::remember($key, self::$cacheTokenTime, function () use ($where) {
            return static::$crm::where($where)->first();
        });

    }

    public static function rememberToken($token)
    {
        if ($token) {
            $key = $token->user_type == self::$lang_loc ? 'loc_' . $token->location_id : 'comp_' . $token->company_id;
            gCache::put($key, $token, self::$cacheTokenTime);
        }
    }

    public static function saveCrmToken($code, $company_id, $loc = null)
    {
        $where = []; //['user_id' => $company_id];
        $type  = $code->userType;
        if ($type == self::$lang_loc) {
            $where['location_id'] = $code->locationId ?? '';
        }
        $cmpid = $code->companyId ?? "";
        if (! empty($cmpid)) {
            $where['company_id'] = $cmpid;
        }
        $already = true;
        if (! $loc) {
            $already = false;
            $loc     = self::getCrmToken($where);
            if (! $loc) {
                $loc              = new static::$crm();
                $loc->location_id = $code->locationId ?? '';
                $loc->user_type   = $type;
                $loc->company_id  = $cmpid;
                $loc->user_id     = $company_id;
                $loc->crm_user_id = $code->user_id ?? '';
            }
        }
        // dd('zee', $already, $code);
        $loc->expires_in    = $code->expires_in ?? 0;
        $loc->access_token  = $code->access_token;
        $loc->refresh_token = $code->refresh_token;

        $loc->save();

        self::rememberToken($loc);
        if ($already) {
            $loc->refresh(); // check it I think no function exist with this name
        }
        return $loc;
    }

    public static function makeCall($url, $method = 'get', $data = null, $headers = [], $json = true)
    {
        $curl           = curl_init();
        $methodl        = strtolower($method);
        $is_key_headers = array_is_list($headers);
        if (! $is_key_headers) {
            $headers1 = [];
            foreach ($headers as $key => $t) {
                $headers1[] = $key . ': ' . (is_array($t) ? json_encode($t) : $t);
            }
            $headers = $headers1;
        }
        $jsonheader = 'content-type: application/json';
        if (! empty($data)) {
            if ((is_array($data) || is_object($data))) {
                if ($json) {
                    $data = json_encode($data);
                } else {
                    $data = json_decode(json_encode($data), true);
                    $data = http_build_query($data);
                }
            }
            if ($json) {
                $headers[] = $jsonheader;
            }
            if ($methodl != 'get') {
                curl_setopt_array($curl, [CURLOPT_POSTFIELDS => $data]);
            } else {
                $url = static::urlFix($url) . $data;
            }
        }

        curl_setopt_array($curl, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => "",
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => strtoupper($method),
            CURLOPT_HTTPHEADER     => $headers,
        ]);

        $response = curl_exec($curl);
        $err      = curl_error($curl);
        curl_close($curl);
        if ($err != '') {
            $response = $err;
        }

        return $response;
    }

    public static function directConnect()
    {
        // for location level connectivity where auto auth is not present then change gohighlevel with leadconnectorhq for only subaccounts
        return 'https://marketplace.gohighlevel.com/oauth/chooselocation?' . self::baseConnect();
    }

    public static function baseConnect()
    {
        //'https://onrecord.cloud/authorization/crm/oauth/callback';
        $callbackurl = route('crm.oauth_callback');
        // dd($callbackurl);
        $client_id = static::getDefault('crm_client_id');
        return "response_type=code&redirect_uri=" . urlencode($callbackurl) . "&client_id=" . $client_id . "&scope=" . urlencode(static::$scopes);
    }
    public static function ConnectOauth($main_id, $token, $is_company = false, $user_id = null)
    {
        $tokenx = false;

        if (! empty($token)) {
            $loc       = $main_id;
            $type      = $is_company ? self::$lang_com : self::$lang_loc;
            $auth_type = self::$userType[$type];
            $locurl    = static::$base_url . "oauth/authorize?" . ($auth_type) . "=" . $loc . "&userType=" . $type . '&' . self::baseConnect();

            $red = self::makeCall($locurl, 'POST', null, [
                'Authorization: Bearer ' . $token,
                //'Version: 2021-04-15'
            ]);
            //dd($red);

            $red = json_decode($red);
            if ($red && property_exists($red, 'redirectUrl')) {
                $url   = $red->redirectUrl;
                $parts = parse_url($url);
                parse_str($parts['query'], $query);
                $code                 = $query['code'] ?? '';
                list($tokenx, $token) = self::go_and_get_token($code, '', $user_id);
            }

        }

        return $tokenx;
    }

    public static function getLocationAccessToken($user_id, $location_id, $token = null, $retries = 0, $dbUserId = null)
    {
        if (! $token) {
            $token = self::getCrmToken(['user_id' => $user_id, 'user_type' => self::$lang_com]);
        }

        $resp = null;
        if ($token) {

            $response = self::makeCall(static::$base_url . "oauth/locationToken", 'POST', "companyId=" . $token->company_id . "&locationId=" . $location_id, [
                "Accept: application/json",
                "Authorization: Bearer " . $token->access_token,
                "Content-Type: application/x-www-form-urlencoded",
                "Version: " . static::$version,
            ], false);
            $resp = json_decode($response);
            if ($resp && property_exists($resp, 'access_token')) {

                $modifiedUserId = $dbUserId ?: $user_id; // just for need to save the dbUserId on newly created user instead of companyUserId (if passed);
                $resp           = self::saveCrmToken($resp, $modifiedUserId, null);

            } else if (self::isExpired($resp) && $retries == 0) {
                list($is_refresh, $token) = self::getRefreshToken($user_id, $token, true);
                if ($is_refresh) {

                    return self::getLocationAccessToken($user_id, $location_id, $token, $retries + 1, $dbUserId);
                }
            }
        }

        return $resp;
    }

    public static function go_and_get_token($code, $type = "", $company_id = null, $loc = null)
    {

        // if ($type == 'reconnect') {
        //     $oldtype = $type;
        //     $type = '';
        // } else if (!empty($type)) {
        //     $type = '1';
        //     $oldtype = $type;
        // }
        $status = false;
        $error  = [$status, 'Unable to update'];
        if (is_string($code)) {
            $code = self::crm_token($code, $type);
            //$type = $oldtype ?? $type;
            $code = json_decode($code);
        }

        // dd($code, $company_id);

        if ($code) {

            if (! $company_id) {
                return $error;
            }
            if (property_exists($code, 'access_token')) {
                return [true, self::saveCrmToken($code, $company_id, $loc)];
            }

            if (property_exists($code, 'error_description')) {
                if (strpos($code->error_description, 'refresh token is invalid') !== false) {
                    try {

                        if ($loc) {
                            // $loc->delete();
                        }
                    } catch (\Throwable $th) {
                        //throw $th;
                    }

                }
                $error = [$status, $code->error_description];
            }
        }
        return $error;
    }

    public static function urlFix($url)
    {
        return (strpos($url, '?') !== false) ? '&' : '?';
    }

    public static function getRefreshToken($company_id, $location, $is_company = false)
    {

        $loc_time   = 30;
        $type       = $is_company ? self::$lang_com : self::$lang_loc;
        $user       = ! $is_company ? ($location->location_id ?? $company_id) : $company_id;
        $loc_block  = cache()->lock($type . '_token_refresh' . $user, $loc_time);
        $is_refresh = false;
        $code       = $location->refresh_token;
        try {
            list($is_refresh, $location) = $loc_block->block($loc_time, function () use ($code, $company_id, $location) {
                try {
                    $location->refresh();
                    if ($code != $location->refresh_token) {
                        return [true, $location];
                    }
                    return self::go_and_get_token($code, '1', $company_id, $location);
                } catch (\Throwable $th) {
                    //throw $th;
                }
                return [false, null];
            });
        } catch (\Exception $e) {

        }
        return [$is_refresh, $location];
    }

    public static function agencyV2($company_id, $urlmain = '', $method = 'get', $data = '', $headers = [], $json = false, $token = null, $retries = 0)
    {
        if (! $company_id) {
            return self::$no_record;
        }
        $url = $urlmain;
        if ($token) {
            $company = $token;
        } else {
            $company = self::getCrmToken($company_id);
        }
        $access_token = $company->access_token ?? null;
        if (! $access_token || empty($access_token)) {
            return self::$no_token;
        }
        $main_url           = static::$base_url;
        $headers['Version'] = static::$version;
        //$companyId = $location->company_id;
        //$methodl = strtolower($method);
        $headers['Authorization'] = 'Bearer ' . $access_token;
        $url1                     = $main_url . $url;
        $cd                       = self::makeCall($url1, $method, $data, $headers, $json);
        $bd                       = json_decode($cd);
        if (self::isExpired($bd) && $retries == 0) {
            list($is_refresh, $token) = self::getRefreshToken($company_id, $company, true);
            if ($is_refresh) {
                return self::agencyV2($company_id, $url, $method, $data, $headers, $json, $token, $retries + 1);
            }
        }
        return $bd;
    }
    public static function getAgencyToken($company_id)
    {
        return static::getCrmToken(['user_id' => $company_id, 'user_type' => self::$lang_com]);
    }
    public static function getLocationToken($company_id, $location = '')
    {

        $data = ['user_id' => $company_id, 'user_type' => self::$lang_loc];
        if ($location != '') {
            $data['location_id'] = $location;
        }
        return static::getCrmToken($data);
    }
    public static function connectLocation($company_id, $location, $companyToken = null)
    {
        $token = null;
        if (! $companyToken) {
            $companyToken = static::getAgencyToken($company_id);
        }

        if ($companyToken) {
            $token = static::getLocationAccessToken($company_id, $location, $companyToken);
        }
        return $token;
    }

    // public function createUserInDBIfNotExist($locationId)
    // {
    //     $with  = 'token';
    //     $user  = User::with($with)->where('location_id', $locationId)->first();
    //     $isNew = false;
    //     if (! $user) {
    //         $user              = new User();
    //         $user->name        = 'Location User';
    //         $user->email       = $locationId . '@autoauth.net';
    //         $user->password    = bcrypt('autoauth_' . $locationId);
    //         $user->location_id = $locationId;
    //         $user->ghl_api_key = '-';
    //         $isNew             = true;
    //         $user->save();
    //     }

    //     //TODO: if need api_key then may be we need to connectOauth with api_key or get api_key else where
    //     // $user->ghl_api_key = $req->token;
    //     // if (! $isNew) {
    //     //     $user->save();
    //     // }
    //     return [$isNew, $user];
    // }

    public static function evp_bytes_to_key($password, $salt)
    {
        $key           = '';
        $iv            = '';
        $derived_bytes = '';
        $previous      = '';

        // Concatenate MD5 results until we generate enough key material (32 bytes key + 16 bytes IV = 48 bytes)
        while (strlen($derived_bytes) < 48) {
            $previous = md5($previous . $password . $salt, true);
            $derived_bytes .= $previous;
        }

        // Split the derived bytes into the key (first 32 bytes) and IV (next 16 bytes)
        $key = substr($derived_bytes, 0, 32);
        $iv  = substr($derived_bytes, 32, 16);

        return [
            $key,
            $iv,
        ];
    }

    public static function decryptSSO($payload, $ssoKey = '')
    {
        if (empty($ssoKey)) {
            $ssoKey = env('SSO_KEY', null);
        }

        if (! $ssoKey) {
            return null;
        }
        $ciphertext = base64_decode($payload);

        if (substr($ciphertext, 0, 8) !== "Salted__") {
            return null;
        }
        $salt           = substr($ciphertext, 8, 8);
        $ciphertext     = substr($ciphertext, 16);
        list($key, $iv) = self::evp_bytes_to_key($ssoKey, $salt);
        $decrypted      = openssl_decrypt($ciphertext, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
        if ($decrypted === false) {
            return null;
        } else {
            return json_decode($decrypted, true);
        }
    }

    public static function crmV2Loc($company_id = null, $location_id, $urlmain = '', $method = 'get', $data = '', $token = '', $json = true)
    {
        if (! $company_id) {
            return self::$no_record;
        }

        $token = static::getLocationToken($company_id, $location_id);

        // dd($token);

        if (! $token) {
            $token = static::connectLocation($company_id, $location_id);
        }
        if (empty($token) || is_null($token)) {
            return self::$no_token;
        }
        return self::crmV2($company_id, $urlmain, $method, $data, [], $json, $location_id, $token);
    }

    public static function isExpired($bd)
    {
        $status = false;
        try {
            $error = $bd->error ?? "";
            if (! is_string($error)) {
                $error = '';
            }
            $message = $bd->message ?? $bd->error_description ?? "";
            if (! is_string($message)) {
                $message = '';
            }
            $status = (strtolower($error) == 'unauthorized' && stripos(($error), 'authclass') === false) || (isset($message) && strtolower($message) == 'invalid jwt');
        } catch (\Exception $e) {

        }

        return $status;
    }

    public static function crmV2($company_id, $urlmain = '', $method = 'get', $data = '', $headers = [], $json = false, $location_id = '', $location = null, $retries = 0)
    {
        $url = $urlmain;
        if (! $company_id) {
            return self::$no_record;
        }

        if (! $location) {

            $location = self::getLocationToken($company_id, $location_id);
            if (! $location) {
                return self::$no_record;
            }
        }

        $main_url           = static::$base_url;
        $headers['Version'] = static::$version;
        $access_token       = $location->access_token ?? null;

        if (! $access_token) {
            return self::$no_token;
        }
        $location_id = $location->location_id ?? '';
        $company_id  = $location->company_id ?? '';
        $methodl     = strtolower($method);
        if ((strpos($url, 'templates') !== false || (strpos($url, 'tags') !== false && strpos($url, 'contacts') === false) || strpos($url, 'custom') !== false || strpos($url, 'tasks/search') !== false) && strpos($url, 'locations/') === false) {
            if (strpos($url, 'custom-fields') !== false) {
                $url = str_replace('-fields', 'Fields', $url);
            }

            if (strpos($url, 'custom-values') !== false) {
                $url = str_replace('-values', 'Values', $url);
            }
            $url = 'locations/' . $location_id . '/' . $url;
        } else if ($methodl == 'get') {
            $urlap = self::urlFix($url);
            if (strpos($url, 'location_id=') === false && strpos($url, 'locationId=') === false && strpos($url, 'locations/') === false) {

                if (strpos($url, 'opportunities/search') !== false) {
                    $url .= $urlap . 'location_id=' . $location_id;
                } else {
                    $isinnot = true;
                    $uri     = ['users', 'opportunities', 'conversations', 'links', 'opportunities', 'notes', 'appointments', 'tasks', 'free-slots'];
                    foreach ($uri as $k) {
                        if (strpos($url, $k) != false) {
                            $isinnot = false;
                        }
                    }
                    if ($isinnot) {
                        $url .= $urlap . 'locationId=' . $location_id;
                    }
                }
            }
        }

        if (strpos($url, 'contacts') !== false) {
            if (strpos($url, 'q=') !== false) {
                $url = str_replace('q=', 'query=', $url);
            }
            if (strpos($url, 'lookup') !== false) {
                $url = str_replace('lookup', 'search/duplicate', $url);
                if (strpos($url, 'phone=') !== false) {
                    $url = str_replace('phone=', 'number=', $url);
                }
            }
        }
        $lastsl = '/';
        $sep    = '?';
        $slash  = explode($sep, $url);
        if (strpos($url, 'customFields') === false) {
            if (count($slash) > 1) {
                $urlpart   = $slash[0];
                $lastindex = substr($urlpart, -1);
                if ($lastindex != $lastsl) {
                    $urlpart .= $lastsl;
                }
                $url = $urlpart . $sep . $slash[1];
            } else {
                $lastindex = substr($url, -1);
                if ($lastindex != $lastsl) {
                    $url .= $lastsl;
                    $urlmain .= $lastsl;
                }
            }
        }
        $headers['Authorization'] = 'Bearer ' . $access_token;

        if ($json) {
            // $headers['Content-Type'] = "application/json";
        }
        $url1 = $main_url . $url;
        // $usertype = $location->user_type;
        $dat = '';
        if (! empty($data)) {
            if (! is_string($data)) {
                $dat = json_encode($data);
            } else {
                $dat = $data;
            }
            try {
                $dat = json_decode($dat) ?? null;
            } catch (\Exception $e) {
                $dat = (object) $data;
            }
            if (property_exists($dat, 'company_id')) {
                unset($dat->company_id);
            }
            if (property_exists($dat, 'customField')) {
                $dat->customFields = $dat->customField;
                unset($dat->customField);
            }

            if ($methodl == 'post') {
                $uri      = ['businesses', 'calendars', 'contacts', 'conversations', 'links', 'opportunities', 'contacts/bulk/business'];
                $matching = str_replace('/', '', $urlmain);
                foreach ($uri as $k) {
                    if ($matching == $k) {
                        if (! property_exists($dat, 'locationId')) {
                            $dat->locationId = $location_id;
                        }
                    }
                }
            }
            if ($methodl == 'put' && strpos($url, 'contacts') !== false) {
                if (property_exists($dat, 'locationId')) {
                    unset($dat->locationId);
                }
                if (property_exists($dat, 'gender')) {
                    unset($dat->gender);
                }
            }

        }

        if (strpos($url1, 'status') !== false) {
            //dd($url1, $method, $dat, $headers,$json);
        }
        // dd($url1, $method, $dat, $headers, $json);
        $cd = self::makeCall($url1, $method, $dat, $headers, $json);

        $bd = json_decode($cd);
        if (self::isExpired($bd) && $retries == 0) {
            list($is_refresh, $location1) = self::getRefreshToken($company_id, $location, false);
            //dd($is_refresh, $location1,$location);
            if (! $is_refresh && $location) {
                $cmpid     = $location->user_id ?? $company_id;
                $getAgency = static::getAgencyToken($cmpid);
                if ($getAgency) {
                    $location1 = static::connectLocation($cmpid, $location->location_id, $getAgency);
                    if ($location && $location1) {
                        $is_refresh = true;
                    }
                }
            }

            if ($is_refresh) {
                return self::crmV2($company_id, $url, $method, $data, $headers, $json, $location_id, $location1, $retries + 1);
            }

            // if (self::ConnectOauth($company)) {
            //     return self::crmV2($company_id, $urlmain, $method, $data, $headers, $json,$location_id,null,$retries+1);
            // }

        }
        return $bd;
    }

    public static function crm_token($code = '', $method = '')
    {
        $md = empty($method) ? 'code' : 'refresh_token';
        if (empty($code)) {
            return $md . ' is required';
        }
        $url                   = static::$base_url . 'oauth/token';
        $data                  = [];
        $data['client_id']     = static::getDefault('crm_client_id');
        $data['client_secret'] = static::getDefault('crm_client_secret');
        $data[$md]             = $code;
        $data['grant_type']    = empty($method) ? 'authorization_code' : 'refresh_token';
        $headers               = ['content-type: application/x-www-form-urlencoded'];
        return self::makeCall($url, 'POST', $data, $headers, false);
    }

    public static function pushCRMProduct($company_id, $location_id, App\Models\Product $product, $token = null, $retries = 0)
    {
        if (! isset($product->crm_product_id)) {
            $product_res = self::createProduct($company_id, $location_id, $product);
        } else {
            $product_res      = new stdClass;
            $product_res->_id = $product->crm_product_id;
        }

        if ($product_res && property_exists($product_res, '_id')) {
            // Create price
            $price_res = self::createPrice($company_id, $location_id, $product_res->_id, $product);
            if ($price_res && property_exists($price_res, '_id')) {
                return [
                    'crm_product_id' => $product_res->_id,
                    'crm_price_id'   => $price_res->_id,
                ];
            }
        }
        return [
            'crm_product_id' => null,
            'crm_price_id'   => null,
        ];
    }

    // public function createProduct($company_id, $location, App\Models\Product $product)
    // {
    //     $url          = static::$base_url . 'products/';
    //     $product_data = [
    //         "name"        => $product->product_name,
    //         "locationId"  => $location,
    //         "description" => $product->product_description,
    //         "productType" => "DIGITAL",
    //     ];
    //     $product_response = self::crmV2($company_id, $url, 'POST', json_encode($product_data), [], false, $location);
    //     return json_decode($product_response);
    // }

    // public function createPrice($company_id, $location_id, $product_id, App\Product $product)
    // {
    //     $update = true;
    //     if (isset($product->crm_price_id)) {
    //         $price_resp      = new stdClass;
    //         $price_resp->_id = $product->crm_price_id;
    //         $update          = false;
    //     } else {
    //         $recurring_array = [];
    //         $price_data      = self::preparePriceData($location_id, $recurring_array, $product);
    //         $price_url       = static::$base_url . "products/{$product_id}/price";
    //         $price_response  = self::crmV2($company_id, $price_url, 'POST', json_encode($price_data), [], false, $location_id);
    //         $price_resp      = json_decode($price_response);
    //     }

    //     if ($price_resp && property_exists($price_resp, '_id') && $update) {
    //         $product->crm_product_id = $product_id;
    //         $product->crm_price_id   = $price_resp->_id;
    //         $product->save();
    //     }
    //     return $price_resp;
    // }
    // public function preparePriceData($location_id, $recurring_array, App\Product $product)
    // {
    //     return [
    //         "name"              => $product->product_name,
    //         "description"       => $product->product_description,
    //         "currency"          => $product->product_currency,
    //         "amount"            => max(0.01, floatval($product->product_cost)),
    //         "type"              => $product->product_type == 0 ? "one_time" : 'recurring',
    //         "recurring"         => $recurring_array,
    //         "availableQuantity" => $product->available_stock(),
    //         "locationId"        => $location_id,
    //         "totalCycles"       => 1,
    //         "trialPeriod"       => ($product->have_trial == 0) ? 0 : $product->trial_period,
    //         "setupFee"          => floatval($product->setup),
    //     ];
    // }

    public static function getCompany($user)
    {
        $def       = ['', ''];
        $token     = $user->token ?? null;
        $status    = false;
        $type      = '';
        $detail    = '';
        $load_more = false;
        if ($token) {
            $type = $token->user_type;

            $query = 'companies/' . $token->company_id;

            if ($type !== self::$lang_com) {
                return $def;
            } else {
                $detail = self::agencyV2($user->id, $query, 'get', '', [], false, $token);
            }
            // dd($detail);
            try {
                if ($detail && property_exists($detail, 'company')) {
                    return [$detail->company->name, $detail->company->id];
                }
            } catch (\Throwable $th) {
                //throw $th;
            }

        }
        return ['', ''];
    }

    public static function getLocation($token, $location_id = null)
    {
        $def = ['', ''];
        if (! $token) {
            if (! $location_id) {
                return $def;
            }
            $token = self::getCrmToken(['location_id' => $location_id]);
        }
        $status    = false;
        $type      = '';
        $detail    = '';
        $load_more = false;
        if ($token) {
            $type   = $token->user_type;
            $query  = 'locations/' . $token->location_id;
            $detail = self::agencyV2($token->user_id, $query, 'get', '', [], false, $token);
            try {
                $obj = strtolower($type);
                if ($detail && property_exists($detail, strtolower($obj))) {
                    // $def = [$detail->{$obj}->name, $detail->{$obj}->id];
                    $def = $detail->{$obj};
                }
            } catch (\Throwable $th) {
                //throw $th;
            }
        }
        return $def;
    }

    public static function fetchLocations($request)
    {
        $user      = auth()->user();
        $token     = $user->token ?? null;
        $status    = false;
        $message   = 'Connect to Agency First';
        $type      = '';
        $detail    = '';
        $load_more = false;
        if ($token) {
            $type  = $token->user_type;
            $query = '';
            $limit = $request->query('limit', 100);

            if ($request->has('page')) {
                if ($request->page < 1) {
                    $request->page = 0;
                } else {
                    $request->page = $request->page - 1;
                }
                $query .= 'skip=' . ($limit * $request->page);
            }

            $query = 'locations/search?' . $query . '&companyId=' . $token->company_id . '&limit=' . $limit;

            if ($type !== self::$lang_com) {
                return response()->json(['status' => $status, 'message' => $message, 'type' => $type, 'detail' => $detail, 'loadMore' => $load_more]);
            } else {
                $detail = self::agencyV2($user->id, $query, 'get', '', [], false, $token);
            }
            if ($detail && property_exists($detail, 'locations')) {

                $detail       = $detail->locations;
                $load_more    = count($detail) == $limit;
                $ids          = collect($detail)->pluck('id')->toArray();
                $exist_tokens = static::$crm::pluck('location_id')->toArray();
                foreach ($detail as $det) {
                    $det->already_exist = in_array($det->id, $exist_tokens);
                }
                $status = true;
            }
        }
        return [$status, $message, $detail, $load_more];
        // return response()->json(['status' => $status, 'message' => $message, 'type' => $type, 'detail' => $detail, 'loadMore' => $load_more]);
    }

    // public static function fetchInventories($locationId, Request $request = null)
    // {
    //     $user      = auth()->user();
    //     $token     = $user->token ?? null;
    //     $status    = false;
    //     $message   = 'Connect to Agency First';
    //     $type      = '';
    //     $detail    = '';
    //     $load_more = false;

    //     if ($token) {
    //         $type   = $token->user_type;
    //         $limit  = 100;
    //         $offset = 0;

    //         if ($request->has('offset')) {
    //             if ($request->offset >= 1) {
    //                 $offset = $request->offset;
    //             }
    //         }

    //         $query = 'products/inventory?&altId=' . $locationId . '&altType=location' . '&limit=' . $limit . '&offset=' . $offset;

    //         if ($type !== self::$lang_com) {
    //             return response()->json(['status' => $status, 'message' => $message, 'type' => $type, 'detail' => $detail, 'loadMore' => $load_more]);
    //         } else {
    //             $detail = self::agencyV2($user->id, $query, 'get', '', [], false, $token);
    //         }

    //         if ($detail && property_exists($detail, 'inventory')) {

    //             $detail    = $detail->inventory;
    //             $load_more = count($detail) > $limit - 1;
    //             $ids       = collect($detail)->pluck('id')->toArray();
    //             // $exist_tokens = static::$crm::pluck('location_id')->toArray();
    //             // foreach ($detail as $det) {
    //             //     $det->already_exist = in_array($det->id, $exist_tokens);
    //             // }
    //             $status = true;
    //         }
    //     }

    //     return [$status, $message, $detail, $load_more];
    // }

    public static function fetchInventories($locationId, $request = [])
    {
                                 //Important TODO:
        $user  = auth()->user(); // TODO: get the admin user instead of login user (because using in job) or pass user fonm job
        $token = $user->token ?? null;

        // Default response structure
        $response = [
            'status'      => false,
            'message'     => 'Connect to Agency First',
            'inventories' => [],
            'loadMore'    => false,
            'type'        => $token->user_type ?? null,
        ];

        // If no token, return default response immediately
        if (! $token) {
            return $response;
        }

        $type   = $token->user_type;
        $limit  = 100;
        $offset = (int) ($request['offset'] ?? 0);

        // If user type is not allowed, return early
        if ($type !== self::$lang_com) {
            $response['type'] = $type;
            return $response;
        }

        // Build API query
        $query = sprintf(
            'products/inventory?altId=%s&altType=location&limit=%d&offset=%d',
            $locationId,
            $limit,
            $offset
        );

        // Call API (may throw exception → handled in controller)
        $apiResponse = self::agencyV2($user->id, $query, 'get', '', [], false, $token);

        // If API returned an error
        if (! $apiResponse || isset($apiResponse->error)) {
            $errorMsg = data_get($apiResponse, 'error.message', 'Unable to fetch inventory');
            Log::error("Inventory API Error", [
                'locationId' => $locationId,
                'error'      => $errorMsg,
            ]);
            $response['message'] = $errorMsg;
            return $response;
        }

        // If inventory exists in response
        if (! empty($apiResponse->inventory)) {
            $inventoryData           = $apiResponse->inventory;
            $response['status']      = true;
            $response['inventories'] = $inventoryData;
            $response['loadMore']    = count($inventoryData) >= $limit;
            $response['message']     = 'Inventories fetched successfully';
            //TODO: add already exist flage
        } else {
            // No inventory found
            $response['message'] = 'No inventory found for this location';
        }

        return $response;
    }

    public static function authChecking($req)
    {
        $authuser = loginUser();
        if ($req->has('location')) {
            self::getLocationAccessToken($authuser->id, $req->location);
            return [true, 'Successfully Added'];
        }
        return [false, 'invalid request'];
    }

    public static function getTransaction($locationId, $messageId, $token)
    {
        $status = false;
        if ($token) {
            $query      = 'conversations/locations/' . $locationId . '/messages' . '/' . $messageId . '/transcription';
            $detail     = self::crmV2($token->user_id ?? 1, $query, 'get', '', [], false, $locationId, $token);
            $transcript = '';
            if ($detail && is_array($detail) && count($detail) > 0) {
                foreach ($detail as $dt) {
                    $transcript .= ($dt->startTime ?? "") . ' - Speaker ' . $dt->mediaChannel . ' : ' . $dt->transcript . ' \n ';
                }
            }

            try {
                if ($detail->error) {
                    return [$status, null];
                }
            } catch (\Exception $e) {

            }
            //dd( $transcript,$detail);

            return [true, $transcript];
        }
        return [$status, null];
    }

    public static function getLocationContact($locationId, $contactId, $token = null)
    {
        try {
            $token = $token ?? getLocationToken($locationId);

            if (! $token) {

                return false;
            }

            $endPoint = 'contacts/' . $contactId;
            $response = self::crmV2($token->user_id, $endPoint, 'GET', '', [], false, $locationId, $token);

            return $response->contact ?? null;

        } catch (\Exception $e) {
            throw $e->getMessage();
        }

        return null;
    }

    public static function updateContact($contactId, $payload, $locationId, $token = null, $responseFull = false)
    {
        try {

            $token = $token ?? getLocationToken($locationId);

            if (! $token) {
                return false;
            }

            $endPoint = 'contacts/' . $contactId;

            $detail = self::crmV2($token->user_id, $endPoint, 'put', $payload, [], true, $locationId, $token);
            if (! $responseFull) {
                return checkError($detail);
            }
            return $detail;

        } catch (\Exception $e) {
            // throw $e->getMessage();
        }
        return false;
    }

    public static function createContact($payload, $locationId, $token = null)
    {
        if (! $locationId) {
            return false;
        }

        try {
            $token = $token ?? getLocationToken($locationId);

            if (! $token) {
                return false;
            }

            $query    = 'contacts/';
            $response = self::crmV2($token->user_id, $query, 'POST', $payload, [], true, $locationId, $token);

            return $response ?? null;

        } catch (\Exception $e) {
            // throw $e->getMessage();
        }

        return null;

    }

    // public static function createFirstContact($data, $searchBy, $locationId, $token)
    // {
    //     $contacts = self::searchContact($searchBy, $locationId, $token);
    //     $contact  = $contacts[0] ?? null;
    //     if (! $contact) {
    //         $contact = self::createContact($data, $locationId, $token);
    //     }
    //     return $contact;
    // }

    public static function searchContact($locationId, $filters = [], $token = null, $page = 1, $pageLimit = 100)
    {

        if (! $locationId) {
            return [];
        }

        $token = $token ?? getLocationToken($locationId);

        if (! $token) {
            return [];
        }

        $contacts = [];

        try {

            $payload = [
                "locationId" => $locationId,
                "page"       => $page,
                "pageLimit"  => $pageLimit,
                "filters"    => $filters,
            ];

            $endPoint = 'contacts/search';

            $response = self::crmV2($token->user_id, $endPoint, 'POST', $payload, [], true, $locationId, $token);
            $contacts = $response->contacts ?? [];

        } catch (\Exception $e) {
            // throw $e->getMessage();
        }

        return $contacts;

    }

    public static function getLocationCalenders($locationId, $token = null)
    {
        if (! $locationId) {
            return [];
        }

        $token     = $token ?? getLocationToken($locationId); // getTokenByLocation($locationId);
        $calenders = [];

        // dd($token);

        try {

            $endPoint = "calendars?locationId=$locationId";

            $response = self::crmV2($token->user_id, $endPoint, 'GET', '', [], false, $locationId, $token);

            if ($response && property_exists($response, 'calendars')) {
                $calenders = $response->calendars ?? null;

                // $calenders = collect($calenders)->toArray(); //->pluck('name', 'id')
            }

        } catch (\Exception $e) {
            // throw $e->getMessage();
        }

        return $calenders;
    }

    public static function createLocationBlockSlot($locationId, $payload, $token = null)
    {
        if (! $locationId) {
            return [];
        }

        $token = $token ?? getLocationToken($locationId);

        try {

            $endPoint = "calendars/events/block-slots";

            $response = self::crmV2($token->user_id, $endPoint, 'POST', $payload, [], true, $locationId, $token); //json true

            // dd($response);
            return $response;

            // if ($response && property_exists($response, 'id')) {
            //     $blockSlotId = $response->id ?? null;
            // }

        } catch (\Exception $e) {
            // throw $e->getMessage();
        }

        return null;
    }

    public static function addContactTag($contactId, $locationId, $token, $payload)
    {
        $status = false;
        if ($token) {
            $endPoint = 'contacts/' . $contactId . '/tags';

            $detail = self::crmV2($token->user_id, $endPoint, 'POST', $payload, [], true, $locationId, $token);

            // Log::info("addContactTag for location $locationId", ['detail' => $detail]);

            return checkError($detail);

        }
        return false;
    }

    public static function createContactNote($contactId, $locationId, $token, $payload, $responseFull = false)
    {

        $token = $token ?? getLocationToken($locationId);

        if (! $token) {
            return false;
        }

        $endPoint = "contacts/{$contactId}/notes";

        $detail = self::crmV2($token->user_id, $endPoint, 'POST', $payload, [], true, $locationId, $token);

        if (! $responseFull) {
            return checkError($detail);
        }
        return $detail;
    }

    public static function getContactNotes($contactId, $locationId, $token = null, $responseFull = false)
    {

        $token = $token ?? getLocationToken($locationId);
        $notes = [];

        // dd($token);

        try {

            $endPoint = "contacts/{$contactId}/notes";

            $response = self::crmV2($token->user_id, $endPoint, 'GET', '', [], false, $locationId, $token);

            if (! $responseFull) {
                if ($response && property_exists($response, 'notes')) {
                    $notes = $response->notes ?? null;
                } else {
                    $notes = null;
                }
                return $notes;
            }

            return $response;

        } catch (\Exception $e) {
            // throw $e->getMessage();
        }
    }

    public static function createCalenderAppointment($locationId, $payload, $token = null)
    {
        if (! $locationId) {
            return false;
        }

        try {
            $token = $token ?? getLocationToken($locationId);

            if ($token) {
                $endPoint = "calendars/events/appointments";

                $response = self::crmV2($token->user_id, $endPoint, 'POST', $payload, [], true, $locationId, $token);
                return $response;
            }

        } catch (\Exception $e) {
            // dd('in catch:', $e->getMessage());
            // throw $e->getMessage();
        }
        return false;
    }

    public static function updateCalenderAppointment($locationId, $appointmentId, $payload, $token = null)
    {
        if (! $locationId) {
            return false;
        }

        try {
            $token = $token ?? getLocationToken($locationId);

            if ($token) {
                $endPoint = "calendars/events/appointments/$appointmentId";

                $response = self::crmV2($token->user_id, $endPoint, 'PUT', $payload, [], true, $locationId, $token);
                return $response;
            }

        } catch (\Exception $e) {
            // dd('in catch:', $e->getMessage());
            // throw $e->getMessage();
        }
        return false;
    }

    public static function deleteCalenderEvent($locationId, $eventId, $payload, $token = null)
    {
        if (! $locationId) {
            return false;
        }

        try {
            $token = $token ?? getLocationToken($locationId);

            if ($token) {
                $endPoint = "calendars/events/$eventId";

                $response = self::crmV2($token->user_id, $endPoint, 'DELETE', $payload, [], true, $locationId, $token);
                return $response;
            }

        } catch (\Exception $e) {
            // dd('in catch:', $e->getMessage());
            // throw $e->getMessage();
        }
        return false;
    }

    public static function searchUsers($locationId, $token = null, string $params = '')
    {
        if (! $locationId) {
            return [];
        }

        $token = $token ?? getLocationToken($locationId); // getTokenByLocation($locationId);
                                                          // dd($token);
        $users = [];

        // dd($token);

        try {

            $endPoint = "users/search?companyId=$token->company_id";

            if (! empty($params)) {
                $endPoint .= "&{$params}";
            }

            $response = self::crmV2($token->user_id, $endPoint, 'GET', '', [], false, $locationId, $token);

            if ($response && property_exists($response, 'users')) {
                $users = $response->users ?? null;

                // $calenders = collect($users)->toArray(); //->pluck('name', 'id')
            }

        } catch (\Exception $e) {
            // throw $e->getMessage();
        }

        return $users;
    }

    public static function createUser($locationId, $token = null, array $params = [])
    {
        if (! $locationId) {
            return [];
        }

        $token  = $token ?? getLocationToken($locationId); // getTokenByLocation($locationId);
        $userId = null;

        try {

            $endPoint = "users";

            $payload = [
                "companyId"   => $token->company_id,
                "firstName"   => $params['firstName'],
                "lastName"    => $params['lastName'],
                "email"       => $params['email'],
                "password"    => "User@1234",
                "type"        => "account",
                "role"        => "user",
                "locationIds" => [$locationId],
            ];

            $response = self::crmV2($token->user_id, $endPoint, 'POST', $payload, [], true, $locationId, $token);
            return $response;

            if ($response && property_exists($response, 'id')) {
                $userId = $response->id ?? null;
            }

        } catch (\Exception $e) {
            // throw $e->getMessage();
        }

        return $userId;
    }

    public static function getLocationCustomFields($locationId, $token = null)
    {
        if (! $locationId) {
            return [];
        }

        $token             = $token ?? getLocationToken($locationId);
        $finalCustomFields = [];

        try {

            $endPoint = "locations/$locationId/customFields?model=contact";

            $response = self::crmV2($token->user_id, $endPoint, 'GET', '', [], false, $locationId, $token);

            if ($response && property_exists($response, 'customFields')) {
                $customFields = $response->customFields ?? null;

                $finalCustomFields = collect($customFields)->pluck('id', 'name')->toArray();
            }

        } catch (\Exception $e) {
            // throw $e->getMessage();
        }

        return $finalCustomFields;
    }

}
