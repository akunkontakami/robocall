<?php

namespace App\Http\Controllers\Robocall;

use App\Exports\ReportRobocallExport;
use App\Http\Controllers\Controller;
use App\Http\Resources\Robocall\ReportRobocallResource;
use App\Services\Robocall\SetupRobocallService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class ReportRobocallController extends Controller
{
    public function report()
    {
        return Inertia::render('ReportRobocall/Index', [
            'campaigns' => (new SetupRobocallService())->listItems(),
        ]);
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

    public function export(Request $request)
    {
        $dataCollection = ReportRobocallResource::collection(
            (new SetupRobocallService())->callLogsReportExport(
                companyId: user()->company_id,
                search: request('search', '')
            )
        );

        $data = $dataCollection->toArray($request);

        $filename = 'report_robocall_'.now()->format('Ymd_His').'.xlsx';

        return Excel::download(new ReportRobocallExport($data), $filename);
    }
}
