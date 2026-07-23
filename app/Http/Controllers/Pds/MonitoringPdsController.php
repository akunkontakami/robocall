<?php

namespace App\Http\Controllers\Pds;

use Inertia\Inertia;
use App\Http\Controllers\Controller;
use Rap2hpoutre\FastExcel\FastExcel;
use App\Services\Pds\SetupPdsService;
use App\Http\Resources\Pds\PdsHistoryResource;

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
        return PdsHistoryResource::collection(
            (new SetupPdsService())->get(
                companyId: user()->company_id,
                search: request('search', ''),
                filter: request('filter', []),
                limit: request('limit', 10),
            )
        );
    }

    public function pdsHistoryExport()
    {
        $data = PdsHistoryResource::collection(
            (new SetupPdsService())->get(
                companyId: user()->company_id,
                search: request('search', ''),
                filter: request('filter', []),
                limit: null
            )
        );

        $arrayData = $data->toArray(request());
        $name = 'pds_history_' . now()->format('Ymd_His') . '.xlsx';

        return (new FastExcel(collect($arrayData)))
            ->download($name, function ($row) {
                $row = (object) $row;
                return [
                    'PDS' => $row->name,
                    'Agent Ready' => $row->total_agent,
                    'Start Time' => $row->session_start,
                    'End Time' => $row->session_end,
                    'Data Size PDS' => $row->data_size,
                    'Data Utilize' => $row->data_utilize,
                    'Calls' => $row->calls,
                    'Call Contacted' => $row->contacted,
                    'Call UnContacted' => $row->uncontacted,
                    'Call Abandon' => $row->abandoned,
                    'Abandon Rate' => $row->abandoned_rate
                ];
            });
    }
}
