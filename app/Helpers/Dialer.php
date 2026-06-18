<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;

class Dialer
{
    public static function token()
    {
        $url = config('services.dialer.api').'/login';
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

        $url = config('services.dialer.api').$path;
        $res = Http::withToken($token)->get($url);

        return $res->json();
    }

    public static function post($path, $payload = [])
    {
        $token = self::token();

        $url = config('services.dialer.api').$path;
        $res = Http::withToken($token)->post($url, $payload);

        return $res->json();
    }

    public static function uploadCsvCallblast(
        string $tenantId,
        string $campaignId,
        string $filePath
    ) {
        $token = self::token();

        $url = config('services.dialer.api')
            ."/uploadCsvCallblast/{$tenantId}/{$campaignId}";

        $response = Http::withToken($token)
            ->attach(
                'file',
                fopen($filePath, 'r'),
                'customers.csv'
            )
            ->post($url);

        return $response->json();
    }
}
