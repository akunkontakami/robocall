<?php

namespace App\Http\Controllers\Robocall;

use App\Http\Controllers\Controller;
use App\Http\Resources\Robocall\ReportRobocallResource;
use App\Services\Robocall\SetupRobocallService;
use Inertia\Inertia;

class ReportRobocallController extends Controller
{
    public function report()
    {
        return Inertia::render('ReportRobocall/Index');
    }

    public function datatable()
    {
        return ReportRobocallResource::collection(
            (new SetupRobocallService())->callLogsReport(
                companyId: user()->company_id,
                search: request('search', ''),
                filter: request('filter', []),
                limit: request('limit', 10),
            )
        );
    }
}
