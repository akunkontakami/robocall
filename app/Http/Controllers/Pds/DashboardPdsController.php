<?php

namespace App\Http\Controllers\Pds;

use App\Exports\PdsDashboardExport;
use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use App\Services\Pds\SetupPdsService;

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

    public function export(Request $request)
    {
        $collection = [];
        $service = new SetupPdsService();

        $data = $service->getDashboardData();
        $sessions = $data->sessions;
        $monitor  = $service->getDashboardMonitoring();

        $collection['agent_ready'] = number_format($monitor->total ?? 0);
        $collection['data_size'] = number_format($sessions->DataSize ?? 0);
        $collection['total_call'] = number_format($sessions->DataDialed ?? 0);
        $collection['redial'] = number_format($sessions->DialCount ?? 0);
        $collection['duration'] = $sessions->TotalDurationFormatted;
        $collection['answer'] = number_format($sessions->DialAgentAnswered);
        $collection['no_answer'] = number_format($sessions->DialFailed);
        $collection['abandon'] = number_format($sessions->DialAbandoned);
        $collection['answer_rate'] = number_format($sessions->DialCount > 0 ? $sessions->DialAbandoned / $sessions->DialCount * 100 : 0) . "%";
        $collection['no_answer_rate'] = number_format($sessions->DialCount > 0 ? $sessions->DialFailed / $sessions->DialCount * 100 : 0) . "%";
        $collection['abandon_rate'] = number_format($sessions->DialCount > 0 ? $sessions->DialAbandoned / $sessions->DialCount * 100 : 0) . "%";
        $collection['avg'] = $sessions->AverageHandling;
        $collection['total_duration'] = $sessions->DurationCall;
        $collection['idle'] = $data->idle->time;


        $data = [
            $collection
        ];

        $filename = 'pds_dashboard_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(new PdsDashboardExport($data), $filename);
    }
}
