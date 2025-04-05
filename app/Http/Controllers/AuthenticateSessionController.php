<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Actions\Auth\LoginAction;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class AuthenticateSessionController extends Controller
{
    public function index()
    {
        if (user()) {
            return to_route('pds.dashboard');
        }

        return Inertia::render('Auth/Login');
    }

    public function store(Request $request, LoginAction $loginAction)
    {
        try {
            $loginAction->execute($request);

            return to_route('pds.dashboard');
        } catch (BadRequestException $e) {
            return back()->with(['error' => $e->getMessage()]);
        }
    }

    public function logout(Request $request)
    {
        $message = null;
        if ($request->force) {
            $message = 'You already login in another device';
        }
        session()->flush();
        return to_route('auth.login.index')->with(['error' => $message]);
    }
}
