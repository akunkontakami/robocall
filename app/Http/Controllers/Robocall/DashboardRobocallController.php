<?php

namespace App\Http\Controllers\Robocall;

use App\Exports\RobocallDashboardExport;
use App\Http\Controllers\Controller;
use App\Services\Robocall\SetupRobocallService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class DashboardRobocallController extends Controller
{
    public function index()
    {
        return Inertia::render('Robocall/Index', [
            'robocall_list' => (new SetupRobocallService())->getAll(user()->company_id),
        ]);
    }

    public function data()
    {
        $data = (new SetupRobocallService())->getDashboardData();

        return response()->json($data);
    }

    public function export(Request $request)
    {
        $collection = [];
        $service = new SetupRobocallService();

        $data = $service->getDashboardData();
        $sessions = $data->sessions;

        $collection['data_size'] = number_format($sessions->DataSize ?? 0);
        $collection['total_call'] = number_format($sessions->DataDialed ?? 0);
        $collection['redial'] = number_format($sessions->DialCount ?? 0);
        $collection['answer'] = number_format($sessions->DialAgentAnswered);
        $collection['no_answer'] = number_format($sessions->DialFailed);
        $collection['abandon'] = number_format($sessions->DialAbandoned);
        $collection['answer_rate'] = number_format($sessions->DialCount > 0 ? $sessions->DialAbandoned / $sessions->DialCount * 100 : 0).'%';
        $collection['no_answer_rate'] = number_format($sessions->DialCount > 0 ? $sessions->DialFailed / $sessions->DialCount * 100 : 0).'%';
        $collection['abandon_rate'] = number_format($sessions->DialCount > 0 ? $sessions->DialAbandoned / $sessions->DialCount * 100 : 0).'%';
        $collection['avg'] = $sessions->AverageHandling;
        $collection['total_duration'] = $sessions->DurationCall;

        $data = [
            $collection,
        ];

        $filename = 'robocall_dashboard_'.now()->format('Ymd_His').'.xlsx';

        return Excel::download(new RobocallDashboardExport($data), $filename);
    }
}
