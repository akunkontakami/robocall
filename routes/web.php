<?php

use App\Http\Controllers\AuthenticateSessionController;
use App\Http\Controllers\Dummy\DummyController;
use App\Http\Controllers\Pds\DashboardPdsController;
use App\Http\Controllers\Pds\MonitoringPdsController;
use App\Http\Controllers\Pds\PdsDetailController;
use App\Http\Controllers\Pds\ReportPdsController;
use App\Http\Controllers\Pds\SetupPdsController;
use Illuminate\Support\Facades\Route;

// Route::inertia('/', 'Welcome');
Route::get('/', fn () => redirect()->route('auth.login.index'));

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
        Route::get('/pds/dashboard', [DashboardPdsController::class, 'index'])->name('pds.dashboard');
        Route::get('/pds/dashboard/export', [DashboardPdsController::class, 'export'])->name('pds.dashboard.export');
        Route::get('/pds/dashboard/data', [DashboardPdsController::class, 'data'])->name('pds.dashboard.data');
        Route::get('/pds/dashboard/monitoring', [DashboardPdsController::class, 'monitoring'])->name('pds.dashboard.monitoring');

        Route::get('/pds/setup', [SetupPdsController::class, 'index'])->name('pds.setup');
        Route::post('/pds/setup/store', [SetupPdsController::class, 'store'])->name('pds.setup.store');
        Route::post('/pds/setup/start', [SetupPdsController::class, 'start'])->name('pds.setup.start');
        Route::post('/pds/setup/start', [SetupPdsController::class, 'start'])->name('pds.setup.start');
        Route::post('/pds/setup/delete', [SetupPdsController::class, 'delete'])->name('pds.setup.delete');
        Route::post('/pds/setup/stop', [SetupPdsController::class, 'stop'])->name('pds.setup.stop');
        Route::post('/pds/setup/release', [SetupPdsController::class, 'release'])->name('pds.setup.release');
        Route::get('/pds/setup/datatable', [SetupPdsController::class, 'datatable'])->name('pds.setup.datatable');

        Route::get('/pds/monitoring', [MonitoringPdsController::class, 'monitoring'])->name('pds.monitoring');
        Route::get('/pds/monitoring/data', [MonitoringPdsController::class, 'monitoringData'])->name('pds.monitoring.data');
        Route::get('/pds/monitoring/live-datatable', [MonitoringPdsController::class, 'monitoringDatatable'])->name('pds.monitoring.datatable');
        Route::get('/pds/monitoring/history-datatable', [MonitoringPdsController::class, 'pdsHistoryDatatable'])->name('pds.monitoring.history-datatable');
        Route::get('/pds/monitoring/history-export', [MonitoringPdsController::class, 'pdsHistoryExport'])->name('pds.monitoring.history-export');

        Route::get('/pds/report', [ReportPdsController::class, 'report'])->name('pds.report');
        Route::get('/pds/report/campaign-datatable', [ReportPdsController::class, 'campaignDatatable'])->name('pds.report.campaign-datatable');
        Route::get('/pds/report/agent-datatable', [ReportPdsController::class, 'agentDatatable'])->name('pds.report.agent-datatable');
        Route::get('/pds/report/tracking-datatable', [ReportPdsController::class, 'trackingDatatable'])->name('pds.report.tracking-datatable');
        Route::get('/pds/report/tracking-export', [ReportPdsController::class, 'trackingExport'])->name('pds.report.tracking-export');
        Route::get('/pds/report/agent-export', [ReportPdsController::class, 'agentExport'])->name('pds.report.agent-export');
        Route::get('/pds/report/campaign-export', [ReportPdsController::class, 'campaignExport'])->name('pds.report.campaign-export');

        Route::get('/pds/detail/{id}', [PdsDetailController::class, 'index'])->name('pds.detail');
        Route::post('/pds/detail/{id}/update', [PdsDetailController::class, 'update'])->name('pds.detail.update');
        Route::get('/pds/detail/{id}/campaign', [PdsDetailController::class, 'campaign'])->name('pds.detail.campaign');
        Route::post('/pds/detail/{id}/assign', [PdsDetailController::class, 'assign'])->name('pds.detail.assign');
        Route::get('/pds/detail/{id}/spv-agent', [PdsDetailController::class, 'spvAgent'])->name('pds.detail.spv-agent');

        // robocall
        Route::inertia('/robocall/dashboard', 'Robocall/Index')->name('robocall.dashboard');
        Route::inertia('/robocall/setup', 'Robocall/Setup')->name('robocall.setup');

        Route::get('dummy', [DummyController::class, 'dummy'])->name('dummy');
    });
