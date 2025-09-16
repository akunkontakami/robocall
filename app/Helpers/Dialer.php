<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;

class Dialer {
    protected static $url = 'https://dev-pbx.kontakami.com/ctb/api';

    public static function token()
    {
        $url = self::$url . '/login';
        $response = Http::post($url, [
            'email' => config('services.dialer.email'),
            'password' => config('services.dialer.password'),
        ]);

        $res = $response->json();

        return $res['token'];
    }

    public static function get($path)
    {
        $token = self::token();

        $url = self::$url . $path;
        $res = Http::withToken($token)->get($url);

        return $res->json();
    }

    public static function post($path, $payload = [])
    {
        $token = self::token();

        $url = self::$url . $path;
        $res = Http::withToken($token)->post($url, $payload);

        return $res->json();
    }
}
