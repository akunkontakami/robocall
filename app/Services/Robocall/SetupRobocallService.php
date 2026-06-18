<?php

namespace App\Services\Robocall;

use App\Helpers\Dialer;
use App\Models\Robocall\Robocall;
use App\Models\Robocall\RobocallFile;
use Illuminate\Support\Carbon;

class SetupRobocallService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
    }

    public function get($companyId, $search, $filter, $limit)
    {
        $data = Robocall::with(['campaign', 'customers'])
        ->where('company_id', $companyId)
        ->when($search, fn ($q) => $q->where('robocall_name', 'LIKE', "%$search%"))
        ->orderBy('created_at', 'desc');

        if ($limit == null) {
            return $data->get();
        } else {
            return $data->paginate($limit ?: 10);
        }
    }

    public function getAll($companyId)
    {
        return Robocall::with(['campaign', 'customers'])
        ->where('company_id', $companyId)
        ->orderBy('created_at', 'desc')
        ->get();
    }

    public function getUploads($companyId, $id, $search, $filter, $limit)
    {
        $data = RobocallFile::with(['customers'])
        ->where('company_id', $companyId)
        ->where('robocall_id', $id)
        ->when($search, fn ($q) => $q->where('robocall_name', 'LIKE', "%$search%"))
        ->orderBy('created_at', 'desc');

        if ($limit == null) {
            return $data->get();
        } else {
            return $data->paginate($limit ?: 10);
        }
    }

    public function find($companyId, $id, $all = [0, 1])
    {
        return Robocall::with(['campaign', 'customers'])
        ->where('company_id', $companyId)
        ->whereIn('is_running', $all)
        ->where('id', $id)
        ->firstOrFail();
    }

    public function getAllIvr()
    {
        $urlPath = '/ivr/index';
        $page = 1;
        $allData = collect();
        $perPage = 10;

        do {
            $result = Dialer::get($urlPath."?page={$page}&per_page={$perPage}");

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

            ++$page;
        } while ($currentPage * $perPage < $total);

        return $allData->values();
    }

    public function getAllRoute()
    {
        $urlPath = '/outbound-route/index';
        $page = 1;
        $allData = collect();
        $perPage = 10;

        do {
            $result = Dialer::get($urlPath."?page={$page}&per_page={$perPage}");

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

            ++$page;
        } while ($currentPage * $perPage < $total);

        return $allData->values();
    }

    public function sessionLogs()
    {
        $urlPath = '/report/sessionlog';
        $page = 1;
        $perPage = 10;
        $tenantId = user()->tenant_id;
        $campaignId = request()->robocall;
        $period = request()->period;

        $startDate = null;
        $endDate = null;

        switch ($period) {
            case 'Today':
                $startDate = Carbon::today()->startOfDay()->toDateString();
                $endDate = Carbon::today()->endOfDay()->toDateString();
                break;

            case 'Month':
                $startDate = Carbon::now()->startOfMonth()->toDateString();
                $endDate = Carbon::now()->endOfMonth()->toDateString();
                break;

            case 'Week':
                $startDate = Carbon::now()->startOfWeek()->toDateString();
                $endDate = Carbon::now()->endOfWeek()->toDateString();
                break;
        }

        $summary = [
            'DataSize' => 0,
            'DataDialed' => 0,
            'DialCount' => 0,
            'DialFailed' => 0,
            'DialContacted' => 0,
            'DialAgentAnswered' => 0,
            'DialAbandoned' => 0,
            'TotalDuration' => 0,
        ];

        do {
            $query = [
                'page' => $page,
                'per_page' => $perPage,
                'tenant_id' => $tenantId,
            ];

            if ($campaignId) {
                $query['campaign_id'] = $campaignId;
            }

            if ($startDate && $endDate) {
                $query['start_date'] = $startDate;
                $query['end_date'] = $endDate;
            }

            $result = Dialer::get($urlPath.'?'.http_build_query($query));
            $data = collect($result['data']);

            foreach ($data as $item) {
                $summary['DataSize'] += $item['DataSize'];
                $summary['DataDialed'] += $item['DataDialed'];
                $summary['DialCount'] += $item['DialCount'];
                $summary['DialFailed'] += $item['DialFailed'];
                $summary['DialContacted'] += $item['DialContacted'];
                $summary['DialAgentAnswered'] += $item['DialAgentAnswered'];
                $summary['DialAbandoned'] += $item['DialAbandoned'];
            }

            $total = $result['total'] ?? $data->count();
            $perPage = $result['per_page'] ?? $perPage;
            $currentPage = $result['current_page'] ?? $page;

            ++$page;
        } while ($currentPage * $perPage < $total);

        return (object) $summary;
    }

    public function getDashboardData()
    {
        $sessions = $this->sessionLogs();

        return (object) [
            'sessions' => $sessions,
        ];
    }

    public function callLogsReport($companyId, $search, $filter, $limit = 10)
    {
        $urlPath = '/report/call-log';
        $page = request()->page ?? 1;
        $perPage = $limit;
        $tenantId = $companyId;

        $campaigns = $filter['campaigns'] ?? [];


        $start = request()->created_start;
        $end = request()->created_end;

        if ($start && $end) {
            $startDate = $start;
            $endDate = $end;
        } else {
            $startDate = null;
            $endDate = null;
        }

        $query = [
            'page' => $page,
            'per_page' => $perPage,
            'tenant_id' => $tenantId,
            'search' => $search,
        ];

        if (!empty($campaigns)) {
            $query['campaign_id'] = implode(', ', $campaigns);
        }

        if ($startDate && $endDate) {
            $query['start_date'] = $startDate;
            $query['end_date'] = $endDate;
        }

        $result = Dialer::get($urlPath.'?'.http_build_query($query));

        $data = collect($result['data']);

        return $data;
    }

    public function callLogsReportExport($companyId, $search = null, $filter = [])
    {
        $urlPath = '/report/call-log';
        $tenantId = $companyId;

        $campaigns = $filter['campaigns'] ?? [];

        $start = request()->created_start;
        $end = request()->created_end;

        $allData = collect();

        $page = 1;
        $lastPage = 1;

        do {
            $query = [
                'page' => $page,
                'per_page' => 10,
                'tenant_id' => $tenantId,
                'search' => $search,
            ];

            if (!empty($campaigns)) {
                $query['campaign_id'] = implode(',', $campaigns);
            }

            if ($start && $end) {
                $query['start_date'] = $start;
                $query['end_date'] = $end;
            }

            $result = Dialer::get($urlPath.'?'.http_build_query($query));

            $allData = $allData->merge($result['data'] ?? []);

            $lastPage = $result['last_page'] ?? 1;

            ++$page;
        } while ($page <= $lastPage);

        return $allData;
    }

    public function listItems()
    {
        $user = user();

        return Robocall::select([
            'id',
            'robocall_name as value',
            'company_id',
        ])
        ->where('company_id', $user->company_id)
        ->orderBy('robocall_name', 'asc')->get();
    }
}
