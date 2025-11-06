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

    public function sessionLogs($campaignId = null)
    {
        $urlPath = "/report/sessionlog";
        $page = 1;
        $perPage = 10;
        $tenantId = user()->tenant_id;

        $startDate = Carbon::today()->startOfDay()->toDateString();
        $endDate = Carbon::today()->endOfDay()->toDateString();

        $summary = [
            'DataDialed' => 0,
            'DataSize' => 0,
            'DialCount' => 0,
            'DataInProgress' => 0,
            'DialAgentAnswered' => 0,
            'DialFailed' => 0,
            'DialContacted' => 0,
            'DialAbandoned' => 0
        ];

        do {

            $query = [
                'page'       => $page,
                'per_page'   => $perPage,
                'tenant_id'  => $tenantId,
                'campaign_id' => $campaignId
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
                $summary['DataSize'] += $item['DataSize'];
                $summary['DialCount'] += $item['DialCount'];
                $summary['DialFailed'] += $item['DialFailed'];
                $summary['DialContacted'] += $item['DialContacted'];
                $summary['DialAbandoned'] += $item['DialAbandoned'];
            }

            $total = $result['total'] ?? $data->count();
            $perPage = $result['per_page'] ?? $perPage;
            $currentPage = $result['current_page'] ?? $page;

            $page++;
        } while ($currentPage * $perPage < $total);

        $AnsweredRate = $summary['DataDialed'] > 0 ? round(($summary['DialAgentAnswered'] / $summary['DataDialed']) * 100, 2) : 0;
        $summary['AnsweredRate'] = $AnsweredRate . "%";

        $AbandonedRate = $summary['DialCount'] > 0 ? round(($summary['DialAbandoned'] / $summary['DialCount']) * 100, 2) : 0;
        $summary['AbandonedRate'] = $AbandonedRate . "%";
        $summary['AbandonedRateNum'] = $AbandonedRate;

        return (object) $summary;
    }

    public function sessionActivities($campaignId = null)
    {
        $urlPath = "/report/sessionactivity";
        $page = 1;
        $perPage = 10;
        $tenantId = user()->tenant_id;

        $startDate = Carbon::today()->startOfDay()->toDateString();
        $endDate = Carbon::today()->endOfDay()->toDateString();

        $summary = [
            'DataInProgress' => 0,
            'DialInProgress' => 0
        ];

        do {

            $query = [
                'page'       => $page,
                'per_page'   => $perPage,
                'tenant_id'  => $tenantId,
                'campaignId' => $campaignId
            ];

            if ($startDate && $endDate) {
                $query['start_date'] = $startDate;
                $query['end_date']   = $endDate;
            }

            $result = Dialer::get($urlPath . "?" . http_build_query($query));

            $data = collect($result['data']);

            foreach ($data as $item) {
                $summary['DataInProgress'] += $item['DataInProgress'];
                $summary['DialInProgress'] += $item['DialInProgress'];
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

    public function pdsHistoryLogs($campaignId, $start = null, $end = null)
    {
        $urlPath = "/report/sessionlog";
        $page = 1;
        $perPage = 10;
        $tenantId = user()->tenant_id;

        if ($start && $end) {
            $startDate = $start;
            $endDate = $end;
        } else {
            $startDate = null;
            $endDate = null;
        }

        $summary = [
            'DataDialed' => 0,
            'DataSize' => 0,
            'DialCount' => 0,
            'DataInProgress' => 0,
            'DialAgentAnswered' => 0,
            'DialFailed' => 0,
            'DialContacted' => 0,
            'DialAbandoned' => 0,
            'SessionStart' => '',
            'SessionEnd' => ''
        ];

        do {

            $query = [
                'page'       => $page,
                'per_page'   => $perPage,
                'tenant_id'  => $tenantId,
                'campaign_id' => $campaignId
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
                $summary['DataSize'] += $item['DataSize'];
                $summary['DialCount'] += $item['DialCount'];
                $summary['DialFailed'] += $item['DialFailed'];
                $summary['DialContacted'] += $item['DialContacted'];
                $summary['DialAbandoned'] += $item['DialAbandoned'];
                $summary['SessionStart'] = $item['SessionStart'];
                $summary['SessionEnd'] = $item['SessionEnd'];
            }

            $total = $result['total'] ?? $data->count();
            $perPage = $result['per_page'] ?? $perPage;
            $currentPage = $result['current_page'] ?? $page;

            $page++;
        } while ($currentPage * $perPage < $total);

        $AnsweredRate = $summary['DataDialed'] > 0 ? round(($summary['DialAgentAnswered'] / $summary['DataDialed']) * 100, 2) : 0;
        $summary['AnsweredRate'] = $AnsweredRate . "%";

        $AbandonedRate = $summary['DialCount'] > 0 ? round(($summary['DialAbandoned'] / $summary['DialCount']) * 100, 2) : 0;
        $summary['AbandonedRate'] = $AbandonedRate . "%";
        $summary['AbandonedRateNum'] = $AbandonedRate;

        return (object) $summary;
    }
}
