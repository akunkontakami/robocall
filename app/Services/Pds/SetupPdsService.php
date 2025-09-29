<?php

namespace App\Services\Pds;

use Carbon\Carbon;
use App\Helpers\Dialer;
use App\Models\Pds\Pds;
use Illuminate\Pagination\LengthAwarePaginator;

class SetupPdsService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function get($companyId, $search, $filter, $limit)
    {
        return Pds::with(["campaign", "agents", "customers", "agents.companyUser", "spv", "spv.companyUser"])
        ->where("company_id", $companyId)
        ->when($search, fn ($q) => $q->where("pds_name", "LIKE", "%$search%"))
        ->orderBy("created_at", "desc")
        ->paginate($limit ?: 10);
    }

    public function getAll($companyId)
    {
        return Pds::with(["campaign", "agents", "customers", "agents.companyUser", "spv", "spv.companyUser"])
        ->where("company_id", $companyId)
        ->orderBy("created_at", "desc")
        ->get();
    }

    public function find($companyId, $id, $all = [0, 1])
    {
        return Pds::with(["campaign", "spv", "spv.companyUser", "agents", "agents.companyUser", "customers"])
        ->where("company_id", $companyId)
        ->whereIn("is_running", $all)
        ->where("id", $id)
        ->firstOrFail();
    }

    public function getAllIvr()
    {
        $urlPath = "/ivr/index";
        $page = 1;
        $allData = collect();
        $perPage = 10;

        do {
            $result = Dialer::get($urlPath . "?page={$page}&per_page={$perPage}");

            $data = collect($result['data'])->map(function ($item) {
                return [
                    'value' => $item['vdn'],
                    'label' => $item['description'],
                ];
            });

            $allData = $allData->merge($data);

            $total = $result['total'] ?? $data->count();
            $perPage = $result['per_page'] ?? $perPage;
            $currentPage = $result['current_page'] ?? $page;

            $page++;
        } while ($currentPage * $perPage < $total);

        return $allData->values();
    }

    public function getAllRoute()
    {
        $urlPath = "/outbound-route/index";
        $page = 1;
        $allData = collect();
        $perPage = 10;

        do {
            $result = Dialer::get($urlPath . "?page={$page}&per_page={$perPage}");

            $data = collect($result['data'])->map(function ($item) {
                return [
                    'value' => $item['route_id'],
                    'label' => $item['route_desc'],
                ];
            });

            $allData = $allData->merge($data);

            $total = $result['total'] ?? $data->count();
            $perPage = $result['per_page'] ?? $perPage;
            $currentPage = $result['current_page'] ?? $page;

            $page++;
        } while ($currentPage * $perPage < $total);

        return $allData->values();
    }

    public function getDashboardData()
    {
        $sessions = $this->sessionLogs();


        return (object) [
            'sessions' => $sessions
        ];
    }

    public function sessionLogs()
    {
        $urlPath = "/report/sessionlog";
        $page = 1;
        $perPage = 10;
        $tenantId = user()->tenant_id;
        $campaignId = request()->pds;
        $period = request()->period;

        $startDate = null;
        $endDate   = null;

        switch ($period) {
            case 'Today':
                $startDate = Carbon::today()->startOfDay()->toDateString();
                $endDate   = Carbon::today()->endOfDay()->toDateString();
                break;

            case 'Month':
                $startDate = Carbon::now()->startOfMonth()->toDateString();
                $endDate   = Carbon::now()->endOfMonth()->toDateString();
                break;

            case '3Month':
                $startDate = Carbon::now()->subMonths(3)->startOfDay()->toDateString();
                $endDate   = Carbon::now()->endOfDay()->toDateString();
                break;
        }

        $summary = [
            'DataSize'        => 0,
            'DataDialed'      => 0,
            'DialCount'       => 0,
            'DialFailed'      => 0,
            'DialContacted'   => 0,
            'DialAgentAnswered' => 0,
            'DialAbandoned'   => 0,
            'TotalDuration'     => 0,
        ];

        do {

            $query = [
                'page'       => $page,
                'per_page'   => $perPage,
                'tenant_id'  => $tenantId,
            ];

            if ($campaignId) $query['campaign_id'] = $campaignId;

            if ($startDate && $endDate) {
                $query['start_date'] = $startDate;
                $query['end_date']   = $endDate;
            }

            $result = Dialer::get($urlPath . "?" . http_build_query($query));

            $data = collect($result['data']);

            foreach ($data as $item) {
                $summary['DataSize']        += $item['DataSize'];
                $summary['DataDialed']      += $item['DataDialed'];
                $summary['DialCount']       += $item['DialCount'];
                $summary['DialFailed']      += $item['DialFailed'];
                $summary['DialContacted']   += $item['DialContacted'];
                $summary['DialAgentAnswered'] += $item['DialAgentAnswered'];
                $summary['DialAbandoned']   += $item['DialAbandoned'];

                $start = \Carbon\Carbon::parse($item['SessionStart']);
                $end   = \Carbon\Carbon::parse($item['SessionEnd']);
                $summary['TotalDuration'] += $end->diffInSeconds($start);
            }

            $total = $result['total'] ?? $data->count();
            $perPage = $result['per_page'] ?? $perPage;
            $currentPage = $result['current_page'] ?? $page;

            $page++;
        } while ($currentPage * $perPage < $total);

        $summary['TotalDurationFormatted'] = gmdate("H.i.s", $summary['TotalDuration']);

        return (object) $summary;
    }
}
