<?php

namespace App\Services\Pds;

use Carbon\Carbon;
use App\Helpers\Dialer;
use App\Models\Pds\Pds;
use App\Models\Pds\PdsAgent;
use Illuminate\Support\Facades\DB;
use App\Models\Account\CompanyUser;
use App\Services\Data\TicketService;
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
        $data = Pds::with(["campaign", "agents", "customers", "agents.companyUser", "agents.ext", "spv", "spv.companyUser"])
        ->where("company_id", $companyId)
        ->when($search, fn ($q) => $q->where("pds_name", "LIKE", "%$search%"))
        ->orderBy("created_at", "desc");

        if ($limit == null) {
            return $data->get();
        } else {
            return $data->paginate($limit ?: 10);
        }
    }

    public function getByCampaign($companyId, $search, $filter, $limit)
    {
        $outbounds = (new TicketService())->getOutboundStatus(user()->company_id);

        $statuses = $outbounds;
        $pds = @$filter['pds'];
        $campaigns = @$filter['campaigns'];

        $data = Pds::with(["campaign", "agents", "customers", "agents.companyUser", "agents.ext", "spv", "spv.companyUser"])
        ->withCount(['tickets as ticket_count' => function($q) use ($statuses) {
            $q->whereIn('status', $statuses)->where("is_bucket", 1);
        }])
        ->where("company_id", $companyId)
        ->when($campaigns, fn ($q) => $q->whereIn("marketing_campaign_id", $campaigns))
        ->when($pds, fn ($q) => $q->whereIn("id", $pds))
        ->when($search, fn ($q) => $q->where("pds_name", "LIKE", "%$search%"))
        ->orderBy("created_at", "desc");

        if ($limit === null) {
            $data = $data->get();
        } else {
            $data = $data->paginate($limit ?: 10);
        }

        $data->each(function ($item) use ($outbounds) {
            $item->outbounds = $outbounds;
            return $item;
        });

        return $data;
    }

    public function getByAgent($companyId, $search, $filter, $limit)
    {
        $outbounds = (new TicketService())->getOutboundStatus(user()->company_id);

        $statuses = $outbounds;
        $pds = @$filter['pds'];
        $campaigns = @$filter['campaigns'];
        $spv = @$filter['spv'];
        $agent = @$filter['agent'];

        $data = PdsAgent::with([
            "ext",
            'companyUser',
            'pds.campaign',
            'pds.customers',
            'pds.spv',
            'pds.spv.companyUser'
        ])
        ->withCount(['tickets as ticket_count' => function($q) use ($statuses) {
            $q->whereIn('status', $statuses)
            ->where("is_bucket", 1)
            ->whereColumn('current_agent_id', 'pds_agents.user_id');
        }])
        ->whereHas(
            'pds', fn($q) => $q->where('company_id', $companyId)
                                ->when($campaigns, fn ($q) => $q->whereIn("marketing_campaign_id", $campaigns))
                                ->when($spv, fn ($q) => $q->whereIn("spv_id", $spv))
                                ->when($pds, fn ($q) => $q->whereIn("id", $pds))
        )
        ->whereHas(
            "companyUser", fn ($q) => $q->where("status", "active")
                                        ->when($agent, fn ($q) => $q->whereIn("id", $agent))
        )
        ->when($search, fn ($q) => $q->whereHas('pds', fn($q2) => $q2->where('pds_name', 'LIKE', "%$search%")))
        ->orderBy('created_at', 'desc');

        if ($limit === null) {
            $data = $data->get();
        } else {
            $data = $data->paginate($limit ?: 10);
        }

        $data->each(function ($item) use ($outbounds) {
            $item->outbounds = $outbounds;
            return $item;
        });

        return $data;
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
            'sessions' => $sessions,
            'idle' => $this->idleAgent($sessions->TotalDuration)
        ];
    }

    public function totalIdleAgent($TotalDuration)
    {
        $companyId = user()->company_id;

        $pdsAgent = PdsAgent::where('company_id', $companyId)
            ->pluck('user_id')
            ->toArray();

        $totalCall = DB::table('calls')
            ->whereNotNull('sip')
            ->where('company_id', $companyId)
            ->whereIn('agent_id', $pdsAgent)
            ->sum('total_duration');

        $idleDuration = max(0, $TotalDuration - $totalCall);

        return (object) [
            'time' => gmdate('H:i:s', $idleDuration)
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

            case 'Week':
                $startDate = Carbon::now()->startOfWeek()->toDateString();
                $endDate   = Carbon::now()->endOfWeek()->toDateString();
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
                $duration = $start->diffInSeconds($end, false);
                $summary['TotalDuration'] += max(0, $duration);

            }

            $total = $result['total'] ?? $data->count();
            $perPage = $result['per_page'] ?? $perPage;
            $currentPage = $result['current_page'] ?? $page;

            $page++;
        } while ($currentPage * $perPage < $total);

        $summary['TotalDurationFormatted'] = gmdate("H:i:s", $summary['TotalDuration']);
        $summary['AverageHandling'] = $summary['DataDialed'] > 0
                                    ? gmdate("H:i:s", $summary['TotalDuration'] / $summary['DataDialed'])
                                    : "00:00:00";

        // $summary['DurationCall'] = $summary['DialContacted'] > 0
        //                             ? gmdate("H:i:s", $summary['TotalDuration'] / $summary['DialContacted'])
        //                             : "00:00:00";

        $summary['DurationCall'] = gmdate("H:i:s", $summary['TotalDuration']);

        return (object) $summary;
    }

    public function idleAgent()
    {
        $urlPath = "/agent-idle";
        $page = 1;
        $perPage = 10;
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

            case 'Week':
                $startDate = Carbon::now()->startOfWeek()->toDateString();
                $endDate   = Carbon::now()->endOfWeek()->toDateString();
                break;
        }

        $companyId = user()->company_id;
        $agents = [];
        $totalIdleSeconds = 0;

        try {
            do {

                $query = [
                    'page'       => $page,
                    'per_page'   => $perPage
                ];

                if ($startDate && $endDate) {
                    $query['start_date'] = $startDate;
                    $query['end_date']   = $endDate;
                }

                $result = Dialer::get($urlPath . "?" . http_build_query($query));
                $data = collect($result['data']);

                foreach ($data as $item) {
                    $extensions = DB::table("cms_extension")->where("agent_login", $item['agent'])->pluck("agent_id")->toArray();

                    $isAgent = CompanyUser::where("company_id", $companyId)->whereIn("user_id", $extensions)->first();
                    if ($isAgent) {
                        $agents[] = [
                            'agent'           => $item['agent'],
                            'agent_group'     => $item['agent_group'],
                            'status'          => $item['status'],
                            'ext_number'      => $item['ext_number'],
                            'total_idle_time' => $item['total_idle_time'],
                        ];

                        $totalIdleSeconds += $this->timeToSeconds($item['total_idle_time']);
                    }
                }

                $total = $result['total'] ?? $data->count();
                $perPage = $result['per_page'] ?? $perPage;
                $currentPage = $result['current_page'] ?? $page;

                $page++;
            } while ($currentPage * $perPage < $total);

            $hours = floor($totalIdleSeconds / 3600);
            $minutes = floor(($totalIdleSeconds % 3600) / 60);
            $seconds = $totalIdleSeconds % 60;
            $totalIdleTime = sprintf("%02d:%02d:%02d", $hours, $minutes, $seconds);

            return (object) [
                'time'  => $totalIdleTime
            ];
        } catch (\Throwable $th) {
            return (object) [
                'time'  => '00:00:00'
            ];
        }
    }

    private function timeToSeconds($time)
    {
        [$h, $m, $s] = explode(':', $time);
        return ($h * 3600) + ($m * 60) + $s;
    }

    public function getDashboardMonitoring()
    {
        $urlPath = "/agent-monitoring?status=1";
        $page = 1;
        $perPage = 100;

        $companyId = user()->company_id;
        $agents = [];
        $countAgent = 0;

        do {

            $result = Dialer::get($urlPath);

            $data = collect($result['data']);

            foreach ($data as $item) {
                $agents[] = $item['agent'];

            }

            $total = $result['total'] ?? $data->count();
            $perPage = $result['per_page'] ?? $perPage;
            $currentPage = $result['current_page'] ?? $page;

            $page++;
        } while ($currentPage * $perPage < $total);

        $extensions = DB::table("cms_extension")->whereIn("agent_login", $agents)->pluck("agent_id")->toArray();

        $countAgent = CompanyUser::where("company_id", $companyId)->whereIn("user_id", $extensions)->count();

        return (object) [
            'total' => $countAgent
        ];
    }

    public function listItems()
    {
        $user = user();

        return Pds::select([
            'id',
            'pds_name as value',
            "company_id",
        ])
        ->where("company_id", $user->company_id)
        ->orderBy("pds_name", "asc")->get();
    }
}
