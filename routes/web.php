<?php

use App\Http\Controllers\AuthenticateSessionController;
use App\Http\Controllers\Dummy\DummyController;
use App\Http\Controllers\Pds\DashboardPdsController;
use App\Http\Controllers\Pds\MonitoringPdsController;
use App\Http\Controllers\Pds\PdsDetailController;
use App\Http\Controllers\Pds\ReportPdsController;
use App\Http\Controllers\Pds\SetupPdsController;
use App\Http\Controllers\Robocall\DashboardRobocallController;
use App\Http\Controllers\Robocall\ReportRobocallController;
use App\Http\Controllers\Robocall\RobocallDetailController;
use App\Http\Controllers\Robocall\SetupRobocallController;
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
        Route::get('/pds/dashboard', [DashboardPdsController::class, 'index'])->name('pds.dashboard');
        Route::get('/pds/dashboard/export', [DashboardPdsController::class, 'export'])->name('pds.dashboard.export');
        Route::get('/pds/dashboard/data', [DashboardPdsController::class, 'data'])->name('pds.dashboard.data');
        Route::get('/pds/dashboard/monitoring', [DashboardPdsController::class, 'monitoring'])->name('pds.dashboard.monitoring');

        Route::get('/pds/setup', [SetupPdsController::class, 'index'])->name('pds.setup');
        Route::post('/pds/setup/store', [SetupPdsController::class, 'store'])->name('pds.setup.store');
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
        Route::get('/robocall/dashboard', [DashboardRobocallController::class, 'index'])->name('robocall.dashboard');
        Route::get('/robocall/dashboard/export', [DashboardRobocallController::class, 'export'])->name('robocall.dashboard.export');
        Route::get('/robocall/dashboard/data', [DashboardRobocallController::class, 'data'])->name('robocall.dashboard.data');

        Route::get('/robocall/setup', [SetupRobocallController::class, 'index'])->name('robocall.setup');
        Route::post('/robocall/setup/store', [SetupRobocallController::class, 'store'])->name('robocall.setup.store');
        Route::post('/robocall/setup/start', [SetupRobocallController::class, 'start'])->name('robocall.setup.start');
        Route::post('/robocall/setup/delete', [SetupRobocallController::class, 'delete'])->name('robocall.setup.delete');
        Route::get('/robocall/setup/datatable', [SetupRobocallController::class, 'datatable'])->name('robocall.setup.datatable');
        Route::post('/robocall/setup/delete', [SetupRobocallController::class, 'delete'])->name('robocall.setup.delete');
        Route::post('/robocall/setup/stop', [SetupRobocallController::class, 'stop'])->name('robocall.setup.stop');
        Route::post('/robocall/setup/pause', [SetupRobocallController::class, 'pause'])->name('robocall.setup.pause');
        Route::post('/robocall/setup/release', [SetupRobocallController::class, 'release'])->name('robocall.setup.release');

        Route::get('/robocall/report', [ReportRobocallController::class, 'report'])->name('robocall.report');
        Route::get('/robocall/report/datatable', [ReportRobocallController::class, 'datatable'])->name('robocall.report.datatable');
        Route::get('/robocall/report/export', [ReportRobocallController::class, 'export'])->name('robocall.report.export');

        Route::get('/robocall/detail/{id}', [RobocallDetailController::class, 'index'])->name('robocall.detail');
        Route::post('/robocall/detail/{id}/update', [RobocallDetailController::class, 'update'])->name('robocall.detail.update');
        Route::get('/robocall/detail/{id}/campaign', [RobocallDetailController::class, 'campaign'])->name('robocall.detail.campaign');
        Route::post('/robocall/detail/{id}/assign-campaign', [RobocallDetailController::class, 'assignCampaign'])->name('robocall.detail.assign-campaign');

        Route::get('/robocall/detail/{id}/upload', [RobocallDetailController::class, 'upload'])->name('robocall.detail.upload');
        Route::get('/robocall/detail/{id}/upload-datatable', [RobocallDetailController::class, 'uploadDatatable'])->name('robocall.detail.upload-datatable');
        Route::post('/robocall/detail/{id}/assign-upload', [RobocallDetailController::class, 'assignUpload'])->name('robocall.detail.assign-upload');
        Route::post('/robocall/detail/{id}/upload/delete', [RobocallDetailController::class, 'deleteUpload'])->name('robocall.detail.delete-upload');

        Route::get('/robocall/detail/{id}/status/{campaignId}', [RobocallDetailController::class, 'status'])->name('robocall.detail.status');

        Route::get('dummy', [DummyController::class, 'dummy'])->name('dummy');
    });
