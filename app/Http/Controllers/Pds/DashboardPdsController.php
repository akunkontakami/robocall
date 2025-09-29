<?php

namespace App\Http\Controllers\Pds;

use App\Http\Controllers\Controller;
use App\Services\Pds\SetupPdsService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardPdsController extends Controller
{
    public function index()
    {
        return Inertia::render('Pds/Index', [
            'pds_list' => (new SetupPdsService())->getAll(user()->company_id)
        ]);
    }

    public function data()
    {
        $data = (new SetupPdsService())->getDashboardData();

        return response()->json($data);
    }

    public function monitoring()
    {
        $data = (new SetupPdsService())->getDashboardMonitoring();

        return response()->json($data);
    }
}
