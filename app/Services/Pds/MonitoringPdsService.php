<?php

namespace App\Services\Pds;

use Carbon\Carbon;
use App\Helpers\Dialer;
use App\Models\Pds\Pds;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Models\Account\CompanyUser;
use Illuminate\Pagination\LengthAwarePaginator;

class MonitoringPdsService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function sessionLogs()
    {
        $urlPath = "/report/sessionlog";
        $page = 1;
        $perPage = 10;
        $tenantId = user()->tenant_id;

        $startDate = Carbon::today()->startOfDay()->toDateString();
        $endDate = Carbon::today()->endOfDay()->toDateString();

        $summary = [
            'DataDialed' => 0,
            'DataInProgress' => 0,
            'DialAgentAnswered' => 0
        ];

        do {

            $query = [
                'page'       => $page,
                'per_page'   => $perPage,
                'tenant_id'  => $tenantId,
            ];

            if ($startDate && $endDate) {
                $query['start_date'] = $startDate;
                $query['end_date']   = $endDate;
            }

            $result = Dialer::get($urlPath . "?" . http_build_query($query));

            $data = collect($result['data']);

            foreach ($data as $item) {
                $summary['DataDialed'] += $item['DataDialed'];
                $summary['DialAgentAnswered'] += $item['DialAgentAnswered'];
            }

            $total = $result['total'] ?? $data->count();
            $perPage = $result['per_page'] ?? $perPage;
            $currentPage = $result['current_page'] ?? $page;

            $page++;
        } while ($currentPage * $perPage < $total);

        $AnsweredRate = $summary['DataDialed'] > 0 ? round(($summary['DialAgentAnswered'] / $summary['DataDialed']) * 100, 2) : 0;
        $summary['AnsweredRate'] = $AnsweredRate . "%";

        return (object) $summary;
    }

    public function sessionActivities()
    {
        $urlPath = "/report/sessionactivity";
        $page = 1;
        $perPage = 10;
        $tenantId = user()->tenant_id;

        $startDate = Carbon::today()->startOfDay()->toDateString();
        $endDate = Carbon::today()->endOfDay()->toDateString();

        $summary = [
            'DataInProgress' => 0,
        ];

        do {

            $query = [
                'page'       => $page,
                'per_page'   => $perPage,
                'tenant_id'  => $tenantId,
            ];

            if ($startDate && $endDate) {
                $query['start_date'] = $startDate;
                $query['end_date']   = $endDate;
            }

            $result = Dialer::get($urlPath . "?" . http_build_query($query));

            $data = collect($result['data']);

            foreach ($data as $item) {
                $summary['DataInProgress'] += $item['DataInProgress'];
            }

            $total = $result['total'] ?? $data->count();
            $perPage = $result['per_page'] ?? $perPage;
            $currentPage = $result['current_page'] ?? $page;

            $page++;
        } while ($currentPage * $perPage < $total);

        return (object) $summary;
    }

    public function campaignDialer()
    {
        $urlPath = "/campaign-dialer/index";
        $page = 1;
        $perPage = 10;
        $tenantId = user()->tenant_id;

        $startDate = Carbon::today()->startOfDay()->toDateString();
        $endDate   = Carbon::today()->endOfDay()->toDateString();

        $summary = [
            'Active' => 0,
            'Paused' => 0,
            'Finished' => 0
        ];

        do {

            $query = [
                'page'       => $page,
                'per_page'   => $perPage,
                'tenant_id'  => $tenantId,
            ];

            if ($startDate && $endDate) {
                $query['start_date'] = $startDate;
                $query['end_date']   = $endDate;
            }

            $result = Dialer::get($urlPath . "?" . http_build_query($query));

            $data = collect($result['data']);

            foreach ($data as $item) {
                if (Str::lower($item['IsRunning']) == 'running') {
                    $summary['Active'] += 1;
                }

                if (Str::lower($item['IsRunning']) == 'paused' || Str::lower($item['IsRunning']) == 'pause') {
                    $summary['Paused'] += 1;
                }

                if (Str::lower($item['IsRunning']) == 'stopped') {
                    $summary['Finished'] += 1;
                }
            }

            $total = $result['total'] ?? $data->count();
            $perPage = $result['per_page'] ?? $perPage;
            $currentPage = $result['current_page'] ?? $page;

            $page++;
        } while ($currentPage * $perPage < $total);

        return (object) $summary;
    }

    public function getMonitoring()
    {
        return (object) [
            'sessions' => $this->sessionLogs(),
            'dialer' => $this->campaignDialer(),
            'progress' => $this->sessionActivities()
        ];
    }
}
