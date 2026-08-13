<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;

class Dialer
{
    public static function token()
    {
        $url = config('services.dialer.api').'/login';
        $response = Http::connectTimeout(10)->timeout(30)->post($url, [
            'email' => config('services.dialer.email'),
            'password' => config('services.dialer.password'),
        ]);

        $response->throw();

        $res = $response->json();

        return $res['token'];
    }

    public static function get($path)
    {
        $token = self::token();

        $url = config('services.dialer.api').$path;
        $res = Http::withToken($token)->connectTimeout(10)->timeout(30)->get($url);

        return $res->json();
    }

    public static function post($path, $payload = [], $throwOnFailure = false)
    {
        $token = self::token();

        $url = config('services.dialer.api').$path;
        $res = Http::withToken($token)->connectTimeout(10)->timeout(30)->post($url, $payload);

        logger($url);
        logger($res);
        if ($throwOnFailure) {
            $res->throw();
        }

        return $res->json();
    }
}
