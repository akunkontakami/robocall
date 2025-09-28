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
        return Inertia::render('Pds/Index');
    }

    public function data()
    {
        $data = (new SetupPdsService())->getDashboardData();

        return response()->json($data);
    }
}
