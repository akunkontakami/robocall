<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): string|null
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $session = user();
        $flashProperties = [];
        $authProperties = [];

        if ($errorFlash = $request->session()->get('error')) {
            $flashProperties['error'] = $errorFlash;
        }
        if ($successFlash = $request->session()->get('success')) {
            $flashProperties['success'] = $successFlash;
        }
        if ($session) {
            $authProperties['user'] = [
                ...collect($session),
            ];
        }

        return [
            ...parent::share($request),
            'auth' => [
                ...$authProperties,
            ],
            'flash' => [
                ...$flashProperties,
            ],
        ];
    }
}
