<?php

use App\Http\Controllers\AuthenticateSessionController;
use App\Http\Controllers\Dummy\DummyController;
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
        Route::inertia('/pds/dashboard', 'Pds/Index')->name('pds.dashboard');
        Route::inertia('/pds/setup', 'Pds/Setup')->name('pds.setup');
        Route::inertia('/pds/monitoring', 'Pds/Monitoring')->name('pds.monitoring');
        Route::inertia('/pds/report', 'Pds/Report')->name('pds.report');
        Route::inertia('/pds/detail/{id}', 'PdsDetail/Index')->name('pds.detail');
        Route::inertia('/pds/detail/{id}/campaign', 'PdsDetail/Campaign')->name('pds.detail.campaign');
        Route::inertia('/pds/detail/{id}/spv-agent', 'PdsDetail/SpvAgent')->name('pds.detail.spv-agent');

        // robocall
        Route::inertia('/robocall/dashboard', 'Robocall/Index')->name('robocall.dashboard');
        Route::inertia('/robocall/setup', 'Robocall/Setup')->name('robocall.setup');


        Route::get("dummy", [DummyController::class, 'dummy'])->name('dummy');
    });
