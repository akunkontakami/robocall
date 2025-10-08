<?php

namespace App\Http\Controllers\Pds;

use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\Pds\MonitoringPdsService;

class MonitoringPdsController extends Controller
{
    public function monitoring()
    {
        return Inertia::render("Pds/Monitoring");
    }

    public function report()
    {
        return Inertia::render("Pds/Report");
    }

    public function monitoringData()
    {
        $data = (new MonitoringPdsService())->getMonitoring();

        return response()->json($data);
    }
}
