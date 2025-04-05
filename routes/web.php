<?php

use App\Http\Controllers\AuthenticateSessionController;
use Illuminate\Support\Facades\Route;

// Route::inertia('/', 'Welcome');

Route::controller(AuthenticateSessionController::class)
     ->as('auth.')
     ->group(function () {
          Route::get('/login', 'index')->name('login.index');
          Route::post('/login', 'store')->name('login.store');
     });
Route::middleware(['app-auth'])
     ->group(function () {
          Route::inertia('/auto-dial', 'AutoDial/Index')->name('auto-dial.index');
          Route::inertia('/pds/dashboard', 'PDS/Index')->name('pds.dashboard');

          // robocall
          Route::inertia('/robocall/dashboard', 'Robocall/Index')->name('robocall.dashboard');
          Route::inertia('/robocall/setup', 'Robocall/Setup')->name('robocall.setup');
     });