<?php

namespace App\Http\Controllers\Pds;

use Inertia\Inertia;
use App\Http\Controllers\Controller;
use Rap2hpoutre\FastExcel\FastExcel;
use App\Services\Pds\SetupPdsService;

class MonitoringPdsController extends Controller
{
    public function monitoring()
    {
        return Inertia::render("Pds/Monitoring");
    }

    public function monitoringData()
    {
        $data = (new MonitoringPdsService())->getMonitoring();

        return response()->json($data);
    }

    public function monitoringDatatable()
    {
        $start_date = request('start_date', now()->format('Y-m-d'));
        $end_date = request('end_date', now()->format('Y-m-d'));

        $data = (new SetupPdsService())->sessionactivity(
            companyId: user()->company_id,
            start_date: $start_date,
            end_date: $end_date,
            limit: request('limit', 10),
        );

        return response()->json($data);
    }

    public function pdsHistoryDatatable()
    {
        $start_date = request('start_date', now()->toDateString());
        $end_date = request('end_date', now()->toDateString());
        $search = request('search');

        $data = (new SetupPdsService())->sessionlog(
            companyId: user()->company_id,
            start_date: $start_date,
            end_date: $end_date,
            limit: request('limit', 10),
            page: request('page', 1),
            search: $search,
        );

        return response()->json($data);
    }

    public function pdsHistoryExport()
    {
        $start_date = request('start_date', now()->toDateString());
        $end_date = request('end_date', now()->toDateString());
        $search = request('search');

        $data = (new SetupPdsService())->sessionlogAll(
            companyId: user()->company_id,
            start_date: $start_date,
            end_date: $end_date,
            search: $search,
        );

        $name = 'pds_history_' . now()->format('Ymd_His') . '.xlsx';

        return (new FastExcel($data))
            ->download($name, function ($row) {
                $row = (object) $row;

                $dialCount = (int) ($row->DialCount ?? 0);
                $dialAbandoned = (int) ($row->DialAbandoned ?? 0);
                $abandonRate = $dialCount ? (($dialAbandoned / $dialCount) * 100) : 0;

                return [
                    'Campaign ID' => $row->campaign_id ?? '-',
                    'Agent Answered' => $row->DialAgentAnswered ?? 0,
                    'Start Time' => $row->SessionStart ?? '-',
                    'End Time' => $row->SessionEnd ?? '-',
                    'Data Size PDS' => $row->DataSize ?? 0,
                    'Data Dialed' => $row->DataDialed ?? 0,
                    'Calls' => $row->DialCount ?? 0,
                    'Call Contacted' => $row->DialContacted ?? 0,
                    'No Answer' => $row->DialFailed ?? 0,
                    'Call Abandoned' => $row->DialAbandoned ?? 0,
                    'Abandon Rate' => number_format($abandonRate, 2) . '%',
                ];
            });
    }
}
