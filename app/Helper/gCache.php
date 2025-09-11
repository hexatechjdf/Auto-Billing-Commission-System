<?php

namespace App\Helper;

use Illuminate\Support\Facades\Cache;

class gCache
{
    public static function get($key, $def = null)
    {
        return Cache::get($key, $def);
    }
    public static function put($key, $value = null)
    {
        return Cache::put($key, $value);
    }

    public static function remember($key, $ttl ,$callback)
    {
        return Cache::remember($key, $ttl,$callback);
    }

    public static function del($key)
    {
        return Cache::forget($key);
    }
}
