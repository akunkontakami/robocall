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
        Route::post('/pds/setup/stop', [SetupPdsController::class, 'stop'])->name('pds.setup.stop');
        Route::post('/pds/setup/release', [SetupPdsController::class, 'release'])->name('pds.setup.release');
        Route::get('/pds/setup/datatable', [SetupPdsController::class, 'datatable'])->name('pds.setup.datatable');

        Route::inertia('/pds/monitoring', 'Pds/Monitoring')->name('pds.monitoring');
        Route::inertia('/pds/report', 'Pds/Report')->name('pds.report');
        Route::get('/pds/detail/{id}', [PdsDetailController::class, 'index'])->name('pds.detail');
        Route::post('/pds/detail/{id}/update', [PdsDetailController::class, 'update'])->name('pds.detail.update');
        Route::get('/pds/detail/{id}/campaign', [PdsDetailController::class, 'campaign'])->name('pds.detail.campaign');
        Route::post('/pds/detail/{id}/assign', [PdsDetailController::class, 'assign'])->name('pds.detail.assign');
        Route::get('/pds/detail/{id}/spv-agent', [PdsDetailController::class, 'spvAgent'])->name('pds.detail.spv-agent');

        // robocall
        Route::inertia('/robocall/dashboard', 'Robocall/Index')->name('robocall.dashboard');
        Route::inertia('/robocall/setup', 'Robocall/Setup')->name('robocall.setup');


        Route::get("dummy", [DummyController::class, 'dummy'])->name('dummy');
    });
