<?php

use App\Http\Controllers\AuthenticateSessionController;
use App\Http\Controllers\Dummy\DummyController;
use App\Http\Controllers\Pds\PdsDetailController;
use App\Http\Controllers\Pds\SetupPdsController;
use Illuminate\Support\Facades\Route;

// Route::inertia('/', 'Welcome');

Route::controller(AuthenticateSessionController::class)
    ->as('auth.')
    ->group(function () {
        Route::get('/login', 'index')->name('login.index');
        Route::post('/login', 'store')->name('login.store');
        Route::get('/logout', 'logout')->name('logout');
    });
Route::middleware(['app-auth'])
    ->group(function () {
        Route::inertia('/auto-dial', 'AutoDial/Index')->name('auto-dial.index');
        Route::inertia('/pds/dashboard', 'Pds/Index')->name('pds.dashboard');
        Route::get('/pds/setup', [SetupPdsController::class, 'index'])->name('pds.setup');
        Route::post('/pds/setup/store', [SetupPdsController::class, 'store'])->name('pds.setup.store');
        Route::post('/pds/setup/start', [SetupPdsController::class, 'start'])->name('pds.setup.start');
        Route::post('/pds/setup/start', [SetupPdsController::class, 'start'])->name('pds.setup.start');
        Route::post('/pds/setup/delete', [SetupPdsController::class, 'delete'])->name('pds.setup.delete');
        Route::get('/pds/setup/datatable', [SetupPdsController::class, 'datatable'])->name('pds.setup.datatable');

        Route::inertia('/pds/monitoring', 'Pds/Monitoring')->name('pds.monitoring');
        Route::inertia('/pds/report', 'Pds/Report')->name('pds.report');
        Route::get('/pds/detail/{id}', [PdsDetailController::class, 'index'])->name('pds.detail');
        Route::get('/pds/detail/{id}/campaign', [PdsDetailController::class, 'campaign'])->name('pds.detail.campaign');
        Route::get('/pds/detail/{id}/spv-agent', [PdsDetailController::class, 'spvAgent'])->name('pds.detail.spv-agent');

        // robocall
        Route::inertia('/robocall/dashboard', 'Robocall/Index')->name('robocall.dashboard');
        Route::inertia('/robocall/setup', 'Robocall/Setup')->name('robocall.setup');


        Route::get("dummy", [DummyController::class, 'dummy'])->name('dummy');
    });
