<?php

use App\Http\Controllers\AuthenticateSessionController;
use Illuminate\Support\Facades\Route;

// Route::inertia('/', 'Welcome');
Route::inertia('/pds/dashboard', 'PDS/Index')->name('pds.dashboard');
Route::inertia('/robocall/dashboard', 'Robocall/Index')->name('robocall.dashboard');

Route::controller(AuthenticateSessionController::class)
     ->as('auth.')
     ->group(function () {
          Route::get('/login','index')->name('login.index');
          Route::post('/login','store')->name('login.store');
     });