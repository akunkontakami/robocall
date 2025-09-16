<?php

if (!function_exists('user')) {
    function user(): mixed
    {
        return session(config('services.session-user-prefix'));
    }
}


if (!function_exists('is_request_in')) {
    function is_request_in($allowedUrl, $url)
    {
        foreach ($allowedUrl as $string) {
            if (str_contains($url, $string)) {
                return true;
            }
        }
        return false;
    }
}
