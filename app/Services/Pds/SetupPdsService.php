<?php

namespace App\Services\Pds;

use App\Helpers\Dialer;
use App\Models\Account\CompanyUser;
use App\Models\Pds\Pds;
use App\Models\Pds\PdsAgent;
use App\Services\Data\TicketService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SetupPdsService
{
    /**
     * Create a new class instance.
     */
    public function __construct() {}

    public function get($companyId, $search, $filter, $limit)
    {
        $data = Pds::with(['campaign', 'agents', 'customers', 'agents.companyUser', 'agents.ext', 'spv', 'spv.companyUser'])
            ->where('company_id', $companyId)
            ->when($search, fn($q) => $q->where('pds_name', 'LIKE', "%$search%"))
            ->orderBy('created_at', 'desc');

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

        $data = Pds::with(['campaign', 'agents', 'customers', 'agents.companyUser', 'agents.ext', 'spv', 'spv.companyUser'])
            ->withCount(['tickets as ticket_count' => function ($q) use ($statuses) {
                $q->whereIn('status', $statuses)->where('is_bucket', 1);
            }])
            ->where('company_id', $companyId)
            ->when($campaigns, fn($q) => $q->whereIn('marketing_campaign_id', $campaigns))
            ->when($pds, fn($q) => $q->whereIn('id', $pds))
            ->when($search, fn($q) => $q->where('pds_name', 'LIKE', "%$search%"))
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

    public function getByAgent($companyId, $search, $filter, $limit)
    {
        $outbounds = (new TicketService())->getOutboundStatus(user()->company_id);

        $statuses = $outbounds;
        $pds = @$filter['pds'];
        $campaigns = @$filter['campaigns'];
        $spv = @$filter['spv'];
        $agent = @$filter['agent'];

        $data = PdsAgent::with([
            'ext',
            'companyUser',
            'pds.campaign',
            'pds.customers',
            'pds.spv',
            'pds.spv.companyUser',
        ])
            ->withCount(['tickets as ticket_count' => function ($q) use ($statuses) {
                $q->whereIn('status', $statuses)
                    ->where('is_bucket', 1)
                    ->whereColumn('current_agent_id', 'pds_agents.user_id');
            }])
            ->whereHas(
                'pds',
                fn($q) => $q->where('company_id', $companyId)
                    ->when($campaigns, fn($q) => $q->whereIn('marketing_campaign_id', $campaigns))
                    ->when($spv, fn($q) => $q->whereIn('spv_id', $spv))
                    ->when($pds, fn($q) => $q->whereIn('id', $pds))
            )
            ->whereHas(
                'companyUser',
                fn($q) => $q->where('status', 'active')
                    ->when($agent, fn($q) => $q->whereIn('id', $agent))
            )
            ->when($search, fn($q) => $q->whereHas('pds', fn($q2) => $q2->where('pds_name', 'LIKE', "%$search%")))
            ->orderBy('created_at', 'desc');
        // dump($data->toSql(), $data->getBindings());

        if ($limit === null) {
            $data = $data->get();
        } else {
            $data = $data->paginate($limit ?: 10);
        }

        $this->hydrateAgentReportMetrics($data, $outbounds, $companyId);

        return $data;
    }

    private function hydrateAgentReportMetrics($data, array $outbounds, $companyId)
    {
        $items = method_exists($data, 'getCollection') ? $data->getCollection() : $data;

        if ($items->isEmpty()) {
            return $data;
        }

        $start = request('created_start');
        $end = request('created_end');

        $sessionLogs = $items->pluck('pds')
            ->filter()
            ->unique('id')
            ->mapWithKeys(function ($pds) use ($start, $end) {
                return [
                    $pds->id => (new MonitoringPdsService())->pdsHistoryLogs($pds->pds_name, $start, $end),
                ];
            });

        $campaignIds = $items->pluck('pds.marketing_campaign_id')->filter()->unique()->values();
        $agentIds = $items->pluck('user_id')->filter()->unique()->values();

        $ticketStatusCounts = DB::table('tickets')
            ->select([
                'marketing_campaign_id',
                'current_agent_id',
                'status',
                DB::raw('COUNT(*) as total'),
            ])
            ->where('company_id', $companyId)
            ->whereIn('marketing_campaign_id', $campaignIds)
            ->whereIn('current_agent_id', $agentIds)
            ->whereIn('status', $outbounds)
            ->where('is_bucket', 1)
            ->groupBy('marketing_campaign_id', 'current_agent_id', 'status')
            ->get()
            ->groupBy(fn($row) => $row->marketing_campaign_id . '__' . $row->current_agent_id)
            ->map(fn($rows) => $rows->pluck('total', 'status')->all());

        $items->each(function ($item) use ($outbounds, $sessionLogs, $ticketStatusCounts) {
            $campaignId = $item->pds?->marketing_campaign_id;
            $ticketStatus = $ticketStatusCounts->get($campaignId . '__' . $item->user_id, []);

            $item->outbounds = $outbounds;
            $item->session_log = $sessionLogs->get($item->pds_id);
            $item->ticket_status_count = $ticketStatus;
            $item->data_utilize = (int) ($item->ticket_count ?? array_sum($ticketStatus));

            return $item;
        });

        if (method_exists($data, 'setCollection')) {
            $data->setCollection($items);
        }

        return $data;
    }

    public function getAll($companyId)
    {
        return Pds::with(['campaign', 'agents', 'customers', 'agents.companyUser', 'spv', 'spv.companyUser'])
            ->where('company_id', $companyId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function find($companyId, $id, $all = [0, 1])
    {
        return Pds::with(['campaign', 'spv', 'spv.companyUser', 'agents', 'agents.companyUser', 'customers'])
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

            ++$page;
        } while ($currentPage * $perPage < $total);

        return $allData->values();
    }

    public function getDashboardData()
    {
        $sessions = $this->sessionLogs();

        return (object) [
            'sessions' => $sessions,
            'idle' => $this->totalIdleAgent($sessions->TotalDuration),
        ];
    }

    public function totalIdleAgent($TotalDuration)
    {
        $companyId = user()->company_id;

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

        $pdsAgent = PdsAgent::where('company_id', $companyId)
            ->pluck('user_id')
            ->toArray();

        $totalCall = DB::table('calls')
            ->whereNotNull('sip')
            ->where('company_id', $companyId)
            ->whereIn('agent_id', $pdsAgent)
            ->when($startDate && $endDate, function ($query) use ($startDate, $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate]);
            })
            ->sum('total_duration');

        $idleDuration = max(0, $TotalDuration - $totalCall);

        return (object) [
            'time' => gmdate('H:i:s', $idleDuration),
        ];
    }

    public function sessionLogs()
    {
        $urlPath = '/report/sessionlog';
        $page = 1;
        $perPage = 10;
        $tenantId = user()->tenant_id;
        $campaignId = request()->pds;
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

            $result = Dialer::get($urlPath . '?' . http_build_query($query));
            $data = collect($result['data']);

            foreach ($data as $item) {
                $summary['DataSize'] += $item['DataSize'];
                $summary['DataDialed'] += $item['DataDialed'];
                $summary['DialCount'] += $item['DialCount'];
                $summary['DialFailed'] += $item['DialFailed'];
                $summary['DialContacted'] += $item['DialContacted'];
                $summary['DialAgentAnswered'] += $item['DialAgentAnswered'];
                $summary['DialAbandoned'] += $item['DialAbandoned'];

                $start = Carbon::parse($item['SessionStart']);
                $end = Carbon::parse($item['SessionEnd']);
                $duration = $start->diffInSeconds($end, false);
                $summary['TotalDuration'] += max(0, $duration);
            }

            $total = $result['total'] ?? $data->count();
            $perPage = $result['per_page'] ?? $perPage;
            $currentPage = $result['current_page'] ?? $page;

            ++$page;
        } while ($currentPage * $perPage < $total);

        $summary['TotalDurationFormatted'] = gmdate('H:i:s', $summary['TotalDuration']);
        $summary['AverageHandling'] = $summary['DataDialed'] > 0
            ? gmdate('H:i:s', $summary['TotalDuration'] / $summary['DataDialed'])
            : '00:00:00';

        // $summary['DurationCall'] = $summary['DialContacted'] > 0
        //                             ? gmdate("H:i:s", $summary['TotalDuration'] / $summary['DialContacted'])
        //                             : "00:00:00";

        $summary['DurationCall'] = gmdate('H:i:s', $summary['TotalDuration']);

        return (object) $summary;
    }

    public function idleAgent()
    {
        $urlPath = '/agent-idle';
        $page = 1;
        $perPage = 10;
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

        $companyId = user()->company_id;
        $agents = [];
        $totalIdleSeconds = 0;

        try {
            do {
                $query = [
                    'page' => $page,
                    'per_page' => $perPage,
                ];

                if ($startDate && $endDate) {
                    $query['start_date'] = $startDate;
                    $query['end_date'] = $endDate;
                }

                $result = Dialer::get($urlPath . '?' . http_build_query($query));
                $data = collect($result['data']);

                foreach ($data as $item) {
                    $extensions = DB::table('cms_extension')->where('agent_login', $item['agent'])->pluck('agent_id')->toArray();

                    $isAgent = CompanyUser::where('company_id', $companyId)->whereIn('user_id', $extensions)->first();
                    if ($isAgent) {
                        $agents[] = [
                            'agent' => $item['agent'],
                            'agent_group' => $item['agent_group'],
                            'status' => $item['status'],
                            'ext_number' => $item['ext_number'],
                            'total_idle_time' => $item['total_idle_time'],
                        ];

                        $totalIdleSeconds += $this->timeToSeconds($item['total_idle_time']);
                    }
                }

                $total = $result['total'] ?? $data->count();
                $perPage = $result['per_page'] ?? $perPage;
                $currentPage = $result['current_page'] ?? $page;

                ++$page;
            } while ($currentPage * $perPage < $total);

            $hours = floor($totalIdleSeconds / 3600);
            $minutes = floor(($totalIdleSeconds % 3600) / 60);
            $seconds = $totalIdleSeconds % 60;
            $totalIdleTime = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);

            return (object) [
                'time' => $totalIdleTime,
            ];
        } catch (\Throwable $th) {
            return (object) [
                'time' => '00:00:00',
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
        $urlPath = '/agent-monitoring?status=1';
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

            ++$page;
        } while ($currentPage * $perPage < $total);

        $extensions = DB::table('cms_extension')->whereIn('agent_login', $agents)->where('agent_group', '!=', 1)->pluck('agent_id')->toArray();

        $countAgent = CompanyUser::where('company_id', $companyId)->whereIn('user_id', $extensions)->count();

        return (object) [
            'total' => $countAgent,
        ];
    }

    public function listItems()
    {
        $user = user();

        return Pds::select([
            'id',
            'pds_name as value',
            'company_id',
        ])
            ->where('company_id', $user->company_id)
            ->orderBy('pds_name', 'asc')->get();
    }

    public function sessionactivity($companyId, $start_date, $end_date, $limit = 10, $page = 1)
    {
        $query = http_build_query([
            'page' => $page,
            'per_page' => $limit,
            'tenant_id' => user()->tenant_id,
            'start_date' => $start_date,
            'end_date' => $end_date,
        ]);

        $dialer = Dialer::get('/report/sessionactivity?' . $query);

        return $dialer;
    }

    public function sessionlog($companyId, $start_date, $end_date, $limit = 10, $page = 1, $search = null)
    {
        $query = [
            'page' => $page,
            'per_page' => $limit,
            'tenant_id' => user()->tenant_id,
            'start_date' => $start_date,
            'end_date' => $end_date,
        ];

        if ($search) {
            $query['campaign_id'] = $search;
        }

        $dialer = Dialer::get('/report/sessionlog?' . http_build_query($query));

        return $dialer;
    }

    public function sessionlogAll($companyId, $start_date, $end_date, $search = null)
    {
        $page = 1;
        $perPage = 100;
        $allData = collect();

        do {
            $query = [
                'page' => $page,
                'per_page' => $perPage,
                'tenant_id' => user()->tenant_id,
                'start_date' => $start_date,
                'end_date' => $end_date,
            ];

            if ($search) {
                $query['campaign_id'] = $search;
            }

            $result = Dialer::get('/report/sessionlog?' . http_build_query($query));
            $data = collect($result['data'] ?? []);
            $allData = $allData->merge($data);

            $total = $result['total'] ?? $data->count();
            $perPage = $result['per_page'] ?? $perPage;
            $currentPage = $result['current_page'] ?? $page;

            ++$page;
        } while ($currentPage * $perPage < $total);

        return $allData;
    }

    public function sessionByCampaign($companyId, $start_date, $end_date, $campaignId = null, $pdsId = null, $search = null, $limit = null, $page = null)
    {
        $page = max((int) ($page ?: 1), 1);
        $limit = (int) ($limit ?: 10);
        $id = $companyId ?: '';

        // Normalize campaignId to single value (take first if array)
        if (is_array($campaignId)) {
            $campaignId = $campaignId[0] ?? null;
        }

        // Support multiple PDS IDs from filter
        $pdsIds = is_array($pdsId) ? array_filter($pdsId) : array_filter([$pdsId]);
        $multiplePds = count($pdsIds) > 1;

        $selectedPdsList = collect();
        if (!empty($pdsIds)) {
            $selectedPdsList = Pds::query()
                ->select(['id', 'pds_name', 'marketing_campaign_id'])
                ->whereIn('id', $pdsIds)
                ->when($companyId, fn($q) => $q->where('company_id', $companyId));
            $selectedPdsList = $selectedPdsList->get();
        } elseif ($campaignId) {
            // Filter by campaign (marketing_campaign_id) -> resolve matching PDS names
            $selectedPdsList = Pds::query()
                ->select(['id', 'pds_name', 'marketing_campaign_id'])
                ->where('marketing_campaign_id', $campaignId)
                ->when($companyId, fn($q) => $q->where('company_id', $companyId));
            $selectedPdsList = $selectedPdsList->get();
        }

        $selectedPds = $selectedPdsList->first();
        $resolvedCampaignId = $campaignId ?: $selectedPds?->marketing_campaign_id;
        $shouldFilterFromLocalQuery = (bool) $resolvedCampaignId;
        $response = [];
        $dialerRows = collect();

        // pds_name (PDS) == campaign_id on the dialer side
        $pdsNames = $selectedPdsList->pluck('pds_name')->filter()->all();
        if (empty($pdsNames)) {
            $pdsNames = [null];
        }

        foreach ($pdsNames as $pdsName) {
            // With multiple PDSes, always do full pagination to aggregate all data
            if ($shouldFilterFromLocalQuery || $multiplePds) {
                $dialerPage = 1;
                $dialerPerPage = 100;

                do {
                    $query = [
                        'page'       => $dialerPage,
                        'per_page'   => $dialerPerPage,
                        'tenant_id'  => user()->tenant_id,
                        'start_date' => $start_date,
                        'end_date'   => $end_date,
                        'limit'      => $limit,
                    ];

                    if ($pdsName) {
                        $query['campaign_id'] = $pdsName;
                    }

                    $result = Dialer::get('/report/sessionlog?' . http_build_query($query));
                    $rows = collect($result['data'] ?? []);
                    $dialerRows = $dialerRows->merge($rows);

                    $total = $result['total'] ?? $rows->count();
                    $dialerPerPage = $result['per_page'] ?? $dialerPerPage;
                    $currentPage = $result['current_page'] ?? $dialerPage;

                    ++$dialerPage;
                } while ($currentPage * $dialerPerPage < $total);
            } else {
                $query = [
                    'page'       => $page,
                    'per_page'   => $limit,
                    'tenant_id'  => user()->tenant_id,
                    'start_date' => $start_date,
                    'end_date'   => $end_date,
                    'limit'      => $limit,
                ];

                if ($pdsName) {
                    $query['campaign_id'] = $pdsName;
                }

                if ($search) {
                    $query['campaign_id'] = $search;
                }

                $result = Dialer::get('/report/sessionlog?' . http_build_query($query));
                $dialerRows = $dialerRows->merge(collect($result['data'] ?? []));
                $response = $result;
            }
        }

        // Build PDS name lookup for multi-PDS rows (pds_name == campaign_id on dialer)
        $pdsNameLookup = $selectedPdsList->pluck('pds_name', 'pds_name')->filter();

        $data = $dialerRows->map(function ($row) use ($companyId, $start_date, $end_date, $resolvedCampaignId, $selectedPds, $multiplePds, $pdsNameLookup) {
            $dataSize     = $row['DataSize'] ?? 0;
            $dataUtilize  = $row['DataDialed'] ?? 0;
            $contacted    = $row['DialContacted'] ?? 0;
            $abandoned    = $row['DialAbandoned'] ?? 0;
            $sessionStart = $row['SessionStart'] ?? $start_date;
            $sessionEnd   = $row['SessionEnd']   ?? $end_date;

            // ambil hanya bagian tanggal (YYYY-MM-DD)
            $tanggalStart = date('Y-m-d', strtotime($sessionStart));
            $tanggalEnd   = date('Y-m-d', strtotime($sessionEnd));
            // set jam tetap: 06:00:00 dan 21:00:00
            $sessionStart = $tanggalStart . ' 06:00:00';
            $sessionEnd   = $tanggalEnd   . ' 23:00:00';

            $CustomerId = isset($row['CustomerId'])
                ? array_filter(array_map('trim', explode(',', $row['CustomerId'])))
                : [];

            // === QUERY (fixed, 2 tahap, hindari row explosion / memory exhausted) ===

            // 1) Cari ticket_id unik yang match kondisi calls (TANPA join, hindari cross-multiplication)
            $matchedTicketIds = DB::table('calls as ca')
                ->where('ca.start_at', '>=', $sessionStart)
                ->where('ca.start_at', '<=', $sessionEnd)
                ->when(!empty($CustomerId), function ($q) use ($CustomerId) {
                    $q->whereIn('ca.ticket_id', $CustomerId);
                })
                ->distinct()
                ->pluck('ca.ticket_id');


            $matchedCallTotal = $matchedTicketIds->count();

            $customerIds = array_values(array_filter(array_map('strval', $CustomerId)));
            $matchedIdsArr = array_values(array_filter(array_map('strval', $matchedTicketIds->all())));
            $matchedIdsLookup = array_fill_keys(array_map('strtolower', $matchedIdsArr), true);
            $noStatusCount = 0;
            foreach ($customerIds as $cid) {
                if (!isset($matchedIdsLookup[strtolower(trim($cid))])) {
                    $noStatusCount++;
                }
            }

            // 2) Ambil status TERBARU per ticket_id, hanya untuk ticket yang matched di atas
            $ticketStatus = collect();
            if ($matchedCallTotal > 0) {
                $ticketStatus = DB::table('ticket_histories as th')
                    ->whereIn('th.ticket_id', $matchedTicketIds)
                    ->select('th.status', 'th.ticket_id')
                    ->orderByDesc('th.created_at')
                    ->get()
                    ->unique('ticket_id')
                    ->values();
            }
            $ticketStatus = $ticketStatus->groupBy('status')->map(function ($group) {
                return $group->count();
            });

            $campaignNormalizer = function ($rawStatus) {
                $canonicalMap = [
                    'Promised to Pay (PTP)' => ['Promised to Pay (PTP)', 'Promised to Pay', 'PTP', 'ptp'],
                    'Call Back'             => ['Call Back', 'Callback', 'CallBack', 'CALL BACK', 'call back'],
                    'BP Partial'            => ['BP Partial', 'Bp Partial', 'BPPartial', 'Hold Date', 'bp partial'],
                    'NBP-A'                 => ['NBP-A', 'NBP A', 'NBPA', 'nbp-a', 'nbpa'],
                    'NBP-B (Salah Sambung)' => ['NBP-B (Salah Sambung)', 'NBP-B', 'NBP B', 'NBPB', 'Salah Sambung', 'nbp-b', 'nbpb'],
                    'NBP-C (Invalid Number)' => ['NBP-C (Invalid Number)', 'NBP-C', 'NBP C', 'NBPC', 'Invalid Number', 'nbp-c', 'nbpc'],
                    'Paid in Confins'       => ['Paid in Confins', 'Paid In Confins', 'PaidinConfins', 'paid in confins'],
                    'KP'                    => ['KP', 'Kp', 'kp'],
                    'Visit Request'         => ['Visit Request', 'VisitRequest', 'VR', 'visit request'],
                    'Visit Request - Contacted' => ['Visit Request - Contacted', 'Visit Request-Contacted', 'VR - Contacted', 'Contacted', 'Visit Request Contacted', 'visit request - contacted'],
                ];
                $normRaw = mb_strtolower(trim($rawStatus));
                foreach ($canonicalMap as $canonical => $variants) {
                    foreach ($variants as $v) {
                        if (mb_strtolower(trim($v)) === $normRaw) {
                            return $canonical;
                        }
                    }
                }
                return $rawStatus;
            };

            $normalized = collect();
            foreach ($ticketStatus as $rawKey => $count) {
                $canonicalKey = $campaignNormalizer($rawKey);
                if ($normalized->has($canonicalKey)) {
                    $normalized[$canonicalKey] += $count;
                } else {
                    $normalized[$canonicalKey] = $count;
                }
            }
            $ticketStatus = $normalized->toArray();

            $duration = 0;
            if (!empty($row['SessionStart']) && !empty($row['SessionEnd'])) {
                $duration = max(
                    0,
                    Carbon::parse($row['SessionStart'])->diffInSeconds(Carbon::parse($row['SessionEnd']), true)
                );
            }

            // When multiple PDS selected, use row's campaign_id from API instead of single PDS name
            if ($multiplePds) {
                $campaignName = $pdsNameLookup[$row['campaign_id'] ?? ''] ?? ($row['campaign_id'] ?? null);
            } else {
                $campaignName = $selectedPds?->pds_name ?? ($row['campaign_id'] ?? null);
            }

            return [
                'campaign'       => $campaignName,
                'name'           => $campaignName,
                'session_start'  => $row['SessionStart'] ?? null,
                'session_end'    => $row['SessionEnd'] ?? null,
                'total_agent'    => null,
                'data_size'      => $dataSize,
                'data_utilize'   => $dataUtilize,
                'data_unutilize' => max($dataSize - $dataUtilize, 0),
                'attempt'        => $row['DialCount'] ?? 0,
                'contacted'      => $contacted,
                'uncontacted'    => max($dataUtilize - $contacted - $abandoned, 0),
                'abandoned'      => $abandoned,
                'ticket_status'  => $ticketStatus,
                'no_status'      => $noStatusCount,
                'duration_pds'   => gmdate('H:i:s', $duration),
                '_matched_call_total' => $matchedCallTotal + $noStatusCount,
            ];
        });

        $needLocalPagination = $shouldFilterFromLocalQuery || $multiplePds;

        if ($needLocalPagination) {
            $data = $data->filter(fn($row) => (int) ($row['_matched_call_total'] ?? 0) > 0)->values();
            $total = $data->count();
            $lastPage = max((int) ceil($total / $limit), 1);
            $currentPage = min($page, $lastPage);
            $from = $total > 0 ? (($currentPage - 1) * $limit) + 1 : 0;
            $to = $total > 0 ? min($currentPage * $limit, $total) : 0;

            $data = $data
                ->slice(($currentPage - 1) * $limit, $limit)
                ->map(function ($row) {
                    unset($row['_matched_call_total']);

                    return $row;
                })
                ->values();

            return [
                'data'          => $data,
                'current_page'  => $currentPage,
                'last_page'     => $lastPage,
                'from'          => $from,
                'to'            => $to,
                'total'         => $total,
                'per_page'      => $limit,
            ];
        }

        $data = $data->map(function ($row) {
            unset($row['_matched_call_total']);

            return $row;
        })->values();

        return [
            'data'          => $data,
            'current_page'  => $response['current_page'] ?? $page,
            'last_page'     => $response['last_page'] ?? 1,
            'from'          => $response['from'] ?? null,
            'to'            => $response['to'] ?? null,
            'total'         => $response['total'] ?? $data->count(),
            'per_page'      => $response['per_page'] ?? $limit,
        ];
    }

    public function getByAgents($companyId, $search, $filter, $limit)
{
    $outbounds = (new TicketService())->getOutboundStatus($companyId);

    $start_date = @$filter['created_start'] ?: request('created_start', now()->toDateString());
    $end_date = @$filter['created_end'] ?: request('created_end', now()->toDateString());

    $normalizeFilterIds = function ($value) {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_array($value)) {
            $clean = array_values(array_filter($value, function ($v) {
                if ($v === null || $v === '') {
                    return false;
                }
                if (is_string($v) && trim($v) === '') {
                    return false;
                }
                return true;
            }));
            return count($clean) ? $clean : null;
        }
        $trimmed = trim((string) $value);
        if ($trimmed === '') {
            return null;
        }
        return [$trimmed];
    };

    $pdsIds = $normalizeFilterIds(@$filter['pds']);
    if (!$pdsIds) {
        $pdsIds = $normalizeFilterIds(request('filter.pds'));
    }
    $campaignIds = $normalizeFilterIds(@$filter['campaigns']);
    if (!$campaignIds) {
        $campaignIds = $normalizeFilterIds(request('filter.campaigns'));
    }
    $spvIds = $normalizeFilterIds(@$filter['spv']);
    if (!$spvIds) {
        $spvIds = $normalizeFilterIds(request('filter.spv'));
    }
    $agentIds = $normalizeFilterIds(@$filter['agent']);
    if (!$agentIds) {
        $agentIds = $normalizeFilterIds(request('filter.agent'));
    }

    $needFilterPdsSide = $pdsIds || $campaignIds || $spvIds;

    $strIds = function ($arr) {
        if (!$arr) {
            return [];
        }
        return array_map('strval', $arr);
    };
    $pdsIdsStr = $strIds($pdsIds);
    $campaignIdsStr = $strIds($campaignIds);
    $spvIdsStr = $strIds($spvIds);
    $agentIdsStr = $strIds($agentIds);

    $filterPdsByRelOrId = function ($q) use (
        $companyId,
        $pdsIds,
        $pdsIdsStr,
        $campaignIds,
        $campaignIdsStr,
        $spvIds,
        $spvIdsStr
    ) {
        $q->where('company_id', $companyId);
        if ($campaignIds) {
            $q->whereIn('marketing_campaign_id', $campaignIdsStr);
        }
        if ($spvIds) {
            $q->whereIn('spv_id', $spvIdsStr);
        }
        if ($pdsIds) {
            $q->whereIn('id', $pdsIdsStr);
        }
    };

    $filterCollectionByPds = function ($collection, $pdsKey = 'pds_id') use ($pdsIdsStr) {
        if (!$pdsIdsStr || !$collection) {
            return $collection;
        }
        return $collection->filter(function ($item) use ($pdsIdsStr, $pdsKey) {
            if (is_array($item)) {
                $val = $item[$pdsKey] ?? null;
            } else {
                $val = $item->{$pdsKey} ?? null;
            }
            if ($val === null) {
                return true;
            }
            return in_array((string) $val, $pdsIdsStr, true);
        })->values();
    };

    // === TAHAP 1: kalau ada filter pds/campaign/spv, ambil CustomerId (ticket_id) dari Dialer API ===
    // Sama pola dengan sessionByCampaign: resolve PDS -> pds_name -> panggil /report/sessionlog
    // per pds_name -> kumpulkan field CustomerId -> jadi daftar ticket_id yang eligible.
    $eligibleTicketIds = null;
    if ($needFilterPdsSide) {
        $selectedPdsList = \App\Models\Pds\Pds::query()
            ->select(['id', 'pds_name'])
            ->where('company_id', $companyId)
            ->when($pdsIdsStr, fn($q) => $q->whereIn('id', $pdsIdsStr))
            ->when($campaignIdsStr, fn($q) => $q->whereIn('marketing_campaign_id', $campaignIdsStr))
            ->when($spvIdsStr, fn($q) => $q->whereIn('spv_id', $spvIdsStr))
            ->get();

        $pdsNames = $selectedPdsList->pluck('pds_name')->filter()->unique()->values()->all();

        $ticketIdMap = []; // pakai map utk dedupe

        foreach ($pdsNames as $pdsName) {
            $dialerPage = 1;
            $dialerPerPage = 100;

            do {
                $query = [
                    'page'        => $dialerPage,
                    'per_page'    => $dialerPerPage,
                    'tenant_id'   => user()->tenant_id,
                    'start_date'  => $start_date,
                    'end_date'    => $end_date,
                    'campaign_id' => $pdsName,
                ];

                $result = Dialer::get('/report/sessionlog?' . http_build_query($query));
                $rows = collect($result['data'] ?? []);

                foreach ($rows as $row) {
                    if (!empty($row['CustomerId'])) {
                        $ids = array_filter(array_map('trim', explode(',', $row['CustomerId'])));
                        foreach ($ids as $tid) {
                            $ticketIdMap[$tid] = true;
                        }
                    }
                }

                $total = $result['total'] ?? $rows->count();
                $dialerPerPage = $result['per_page'] ?? $dialerPerPage;
                $currentPage = $result['current_page'] ?? $dialerPage;

                ++$dialerPage;
            } while ($currentPage * $dialerPerPage < $total);
        }

        // kalau tidak ada pds_name yang match filter, atau tidak ada CustomerId sama sekali,
        // ini sengaja jadi array kosong -> whereIn kosong -> hasil aggQuery memang kosong (bukan bug)
        $eligibleTicketIds = array_keys($ticketIdMap);
    }

    // === TAHAP 2: query agregasi, filter pakai ticket_id hasil API (bukan join pds_agents/pds) ===
    $aggQuery = DB::table('ticket_histories as th')
        ->joinSub(
            DB::table('ticket_histories')
                ->select('ticket_id', DB::raw('MAX(created_at) as last_created'))
                ->where('company_id', $companyId)
                ->where('created_at', '>=', $start_date . ' 06:00:00')
                ->where('created_at', '<=', $end_date . ' 23:59:59')
                ->groupBy('ticket_id'),
            'last',
            fn($join) => $join->on('last.ticket_id', '=', 'th.ticket_id')
                ->on('last.last_created', '=', 'th.created_at')
        )
        ->join('calls as ca', 'th.id', '=', 'ca.ticket_history_id')
        ->where('th.company_id', $companyId)
        ->where('th.created_at', '>=', $start_date . ' 06:00:00')
        ->where('th.created_at', '<=', $end_date . ' 23:59:59')
        ->when($eligibleTicketIds !== null, fn($q) => $q->whereIn('th.ticket_id', $eligibleTicketIds))
        ->when($agentIds, fn($q) => $q->whereIn('ca.agent_id', $agentIdsStr))
        ->select([
            'ca.agent_id as user_id',
            DB::raw('DATE(th.created_at) as ticket_date'),
            DB::raw("COUNT(DISTINCT CASE WHEN th.status = 'Promised to Pay (PTP)' THEN th.ticket_id END) as PTP"),
            DB::raw("COUNT(DISTINCT CASE WHEN th.status = 'Call Back' THEN th.ticket_id END) as CallBack"),
            DB::raw("COUNT(DISTINCT CASE WHEN th.status = 'BP Partial' THEN th.ticket_id END) as BPPartial"),
            DB::raw("COUNT(DISTINCT CASE WHEN th.status = 'NBP-A' THEN th.ticket_id END) as NBPA"),
            DB::raw("COUNT(DISTINCT CASE WHEN th.status = 'NBP-B (Salah Sambung)' THEN th.ticket_id END) as NBPB"),
            DB::raw("COUNT(DISTINCT CASE WHEN th.status = 'NBP-C (Invalid Number)' THEN th.ticket_id END) as NBPC"),
            DB::raw("COUNT(DISTINCT CASE WHEN th.status = 'Paid in Confins' THEN th.ticket_id END) as PaidinConfins"),
            DB::raw("COUNT(DISTINCT CASE WHEN th.status = 'KP' THEN th.ticket_id END) as KP"),
            DB::raw("COUNT(DISTINCT CASE WHEN th.status = 'Visit Request' THEN th.ticket_id END) as VisitRequest"),
            DB::raw("COUNT(DISTINCT CASE WHEN th.status = 'Visit Request - Contacted' THEN th.ticket_id END) as VisitRequestContacted"),
            DB::raw("COUNT(DISTINCT th.ticket_id) as data_contacted"),
        ])
        ->groupBy(DB::raw('DATE(th.created_at)'), 'ca.agent_id');
        dump($aggQuery->toSql(), $aggQuery->getBindings());
    $aggRowsList = $aggQuery->get();
    $aggByDateUser = [];
    $aggUserIds = [];
    foreach ($aggRowsList as $row) {
        $userId = $row->user_id;
        $date = $row->ticket_date;
        $aggByDateUser[$date . '_' . $userId] = $row;
        if (!in_array($userId, $aggUserIds, true)) {
            $aggUserIds[] = $userId;
        }
    }
    $aggUserIds = collect($aggUserIds)->filter()->values();
    $aggDates = $aggRowsList->pluck('ticket_date')->unique()->sort()->values()->all();

    $baseQuery = PdsAgent::with([
        'ext',
        'companyUser',
        'pds.campaign',
        'pds.customers',
        'pds.spv',
        'pds.spv.companyUser',
    ])
        ->whereIn('pds_agents.user_id', $aggUserIds)
        ->whereHas('pds', $filterPdsByRelOrId)
        ->when($search, fn($q) => $q->whereHas('pds', fn($q2) => $q2->where('pds_name', 'LIKE', "%{$search}%")))
        ->orderBy('pds_agents.created_at', 'desc');

    $data = $baseQuery->get();
    $items = $data;
    $items = $filterCollectionByPds($items);

    if ($items->isEmpty() && $aggUserIds->isNotEmpty()) {
        $fallbackAgents = PdsAgent::with([
            'ext',
            'companyUser',
            'pds.campaign',
            'pds.customers',
            'pds.spv',
            'pds.spv.companyUser',
        ])
            ->whereIn('pds_agents.user_id', $aggUserIds)
            ->whereHas('pds', $filterPdsByRelOrId)
            ->orderBy('pds_agents.created_at', 'desc')
            ->get();

        $fallbackAgents = $filterCollectionByPds($fallbackAgents);

        $seenUser = [];
        foreach ($fallbackAgents as $fa) {
            if (isset($seenUser[$fa->user_id])) {
                continue;
            }
            $seenUser[$fa->user_id] = true;
            $items->push($fa);
        }
    }

    $pdsIdsPluck = $items->pluck('pds_id')->filter()->unique()->values();
    $sessionLogs = [];
    if ($pdsIdsPluck->isNotEmpty()) {
        $pdsList = \App\Models\Pds\Pds::whereIn('id', $pdsIdsPluck)->get()->keyBy('id');
        foreach ($aggDates as $d) {
            foreach ($pdsList as $pdsId => $pdsItem) {
                $sessionLogs[$pdsId . '_' . $d] = (new MonitoringPdsService())->pdsHistoryLogs($pdsItem->pds_name, $d, $d);
            }
        }
    }

    $fixedStatusOrder = [
        'PTP' => 'PTP',
        'CallBack' => 'CallBack',
        'BPPartial' => 'BP Partial',
        'NBPA' => 'NBP-A',
        'NBPB' => 'NBP-B (Salah Sambung)',
        'NBPC' => 'NBP-C (Invalid Number)',
        'PaidinConfins' => 'Paid in Confins',
        'KP' => 'KP',
        'VisitRequest' => 'Visit Request',
        'VisitRequestContacted' => 'Visit Request - Contacted',
    ];

    $keyEquivalents = [
        'PTP' => ['Promised to Pay (PTP)', 'Promised to Pay', 'PTP'],
        'CallBack' => ['Call Back', 'Callback', 'CallBack', 'CALL BACK'],
        'BPPartial' => ['BP Partial', 'Bp Partial', 'BPPartial', 'Hold Date'],
        'NBPA' => ['NBP-A', 'NBP A', 'NBPA'],
        'NBPB' => ['NBP-B (Salah Sambung)', 'NBP-B', 'NBP B', 'NBPB', 'Salah Sambung'],
        'NBPC' => ['NBP-C (Invalid Number)', 'NBP-C', 'NBP C', 'NBPC', 'Invalid Number'],
        'PaidinConfins' => ['Paid in Confins', 'Paid In Confins', 'PaidinConfins'],
        'KP' => ['KP', 'Kp', 'kp'],
        'VisitRequest' => ['Visit Request', 'VisitRequest', 'VR', 'visit request'],
        'VisitRequestContacted' => ['Visit Request - Contacted', 'Visit Request-Contacted', 'VR - Contacted', 'Contacted', 'Visit Request Contacted'],
    ];

    $queryStatusOrder = array_keys($fixedStatusOrder);
    $outboundsFiltered = collect($outbounds)->filter(function ($name) use ($keyEquivalents) {
        $normName = mb_strtolower(trim($name));
        foreach ($keyEquivalents as $canonicalKey => $variants) {
            foreach ($variants as $variant) {
                if (mb_strtolower(trim($variant)) === $normName) {
                    return true;
                }
            }
        }
        return false;
    })->values()->all();

    $buildRow = function ($agg) use ($queryStatusOrder, $fixedStatusOrder, $keyEquivalents, $outboundsFiltered) {
        $ptp = (int) ($agg->PTP ?? 0);
        $callback = (int) ($agg->CallBack ?? 0);
        $bpp = (int) ($agg->BPPartial ?? 0);
        $nbpa = (int) ($agg->NBPA ?? 0);
        $nbpb = (int) ($agg->NBPB ?? 0);
        $nbpc = (int) ($agg->NBPC ?? 0);
        $pic = (int) ($agg->PaidinConfins ?? 0);
        $kp = (int) ($agg->KP ?? 0);
        $vr = (int) ($agg->VisitRequest ?? 0);
        $vrc = (int) ($agg->VisitRequestContacted ?? 0);
        $dataContacted = (int) ($agg->data_contacted ?? 0);

        $valueMap = [
            'PTP' => $ptp,
            'CallBack' => $callback,
            'BPPartial' => $bpp,
            'NBPA' => $nbpa,
            'NBPB' => $nbpb,
            'NBPC' => $nbpc,
            'PaidinConfins' => $pic,
            'KP' => $kp,
            'VisitRequest' => $vr,
            'VisitRequestContacted' => $vrc,
        ];

        $noStatus = max($dataContacted - array_sum($valueMap), 0);

        $displayNames = [];
        foreach ($outboundsFiltered as $name) {
            foreach ($keyEquivalents as $canonicalKey => $variants) {
                foreach ($variants as $variant) {
                    if (mb_strtolower(trim($variant)) === mb_strtolower(trim($name))) {
                        $displayNames[$canonicalKey] = $name;
                        break 2;
                    }
                }
            }
        }

        $ticketStatus = [];
        foreach ($queryStatusOrder as $alias) {
            $displayName = $displayNames[$alias] ?? $fixedStatusOrder[$alias] ?? $alias;
            $ticketStatus[$displayName] = (int) ($valueMap[$alias] ?? 0);
        }
        $ticketStatus['No Status'] = $noStatus;

        $outboundsFinal = array_values($displayNames);
        if (empty($outboundsFinal)) {
            $outboundsFinal = collect($queryStatusOrder)
                ->map(fn($alias) => $fixedStatusOrder[$alias] ?? $alias)
                ->values()
                ->all();
        }
        $outboundsFinal[] = 'No Status';

        return [
            'ptp' => $ptp, 'callback' => $callback, 'bpp' => $bpp, 'nbpa' => $nbpa,
            'nbpb' => $nbpb, 'nbpc' => $nbpc, 'pic' => $pic, 'kp' => $kp,
            'vr' => $vr, 'vrc' => $vrc, 'noStatus' => $noStatus,
            'dataContacted' => $dataContacted, 'valueMap' => $valueMap,
            'ticketStatus' => $ticketStatus, 'outboundsFinal' => $outboundsFinal,
        ];
    };

    $expanded = collect([]);
    foreach ($items as $item) {
        $pdsId = $item->pds_id;
        $pdsName = '';
        $campaignName = '';
        $spvName = '';
        $pdsObj = $item->pds ?? null;
        if ($pdsObj) {
            $pdsName = $pdsObj->pds_name ?? '';
            $campaignName = $pdsObj->campaign?->name ?? '';
            $spvName = $pdsObj->spv?->company_user?->name ?? $pdsObj->spv?->name ?? '';
        }
        $agentName = $item->company_user?->name ?? $item->companyUser?->name ?? '';

        foreach ($aggDates as $date) {
            $compositeKey = $date . '_' . $item->user_id;
            $agg = $aggByDateUser[$compositeKey] ?? null;
            if (!$agg) {
                continue;
            }

            $r = $buildRow($agg);

            $sLog = $sessionLogs[$pdsId . '_' . $date] ?? null;
            if (!$sLog) {
                $sLog = (object) [
                    'SessionStart' => $date . ' 06:00:00',
                    'SessionEnd' => $date . ' 23:59:59',
                ];
            }

            $newId = is_object($item) && isset($item->id) ? ($item->id . '_' . $date) : ('row_' . $item->user_id . '_' . $date);
            $obj = (object) [
                'id' => $newId,
                'date' => $date,
                'pds_id' => $pdsId,
                'user_id' => $item->user_id,
                'created_at' => $item->created_at ?? now(),
                'PTP' => $r['ptp'],
                'CallBack' => $r['callback'],
                'BPPartial' => $r['bpp'],
                'NBPA' => $r['nbpa'],
                'NBPB' => $r['nbpb'],
                'NBPC' => $r['nbpc'],
                'PaidinConfins' => $r['pic'],
                'KP' => $r['kp'],
                'VisitRequest' => $r['vr'],
                'VisitRequestContacted' => $r['vrc'],
                'NoStatus' => $r['noStatus'],
                'data_contacted' => $r['dataContacted'],
                'data_utilize' => (int) ($r['dataContacted'] > 0 ? $r['dataContacted'] : array_sum($r['valueMap'])),
                'companyUser' => (object) ['name' => $agentName ?: ('Agent ' . substr($item->user_id, 0, 8))],
                'company_user' => (object) ['name' => $agentName ?: ('Agent ' . substr($item->user_id, 0, 8))],
                'pds' => (object) [
                    'pds_name' => $pdsName ?: 'Unknown PDS',
                    'campaign' => (object) ['name' => $campaignName ?: '-'],
                    'spv' => (object) [
                        'name' => $spvName ?: '-',
                        'company_user' => (object) ['name' => $spvName ?: '-'],
                    ],
                ],
                'session_log' => $sLog,
                'outbounds' => $r['outboundsFinal'],
                'ticket_status_count' => $r['ticketStatus'],
            ];

            $expanded->push($obj);
        }
    }
    $items = $expanded;
    $items = $filterCollectionByPds($items);

    if ($items->isEmpty() && $aggUserIds->isNotEmpty()) {
        $companyUsers = DB::table('company_users')
            ->whereIn('user_id', $aggUserIds)
            ->select('user_id', 'name')
            ->get()
            ->keyBy('user_id');

        $allowedPdsQuery = \App\Models\Pds\Pds::with(['campaign', 'spv', 'spv.companyUser'])
            ->where($filterPdsByRelOrId)
            ->orderBy('created_at', 'desc');

        $userToPds = collect([]);
        if ($needFilterPdsSide) {
            $userToPds = DB::table('pds_agents as pa')
                ->joinSub(
                    $allowedPdsQuery->select('id as lp_id'),
                    'lp',
                    'lp.lp_id',
                    '=',
                    'pa.pds_id'
                )
                ->whereIn('pa.user_id', $aggUserIds->all())
                ->select('pa.user_id', 'pa.pds_id')
                ->get()
                ->keyBy('user_id');
        }

        $pdsListForFallback = $allowedPdsQuery->get()->keyBy('id');
        $defaultPds = $pdsListForFallback->first();

        if ($needFilterPdsSide && !$defaultPds) {
        } else {
            foreach ($aggDates as $date) {
                foreach ($aggUserIds as $userId) {
                    $compositeKey = $date . '_' . $userId;
                    $agg = $aggByDateUser[$compositeKey] ?? null;
                    if (!$agg) {
                        continue;
                    }

                    $cu = $companyUsers->get($userId);
                    $agentName = $cu ? $cu->name : ('Agent ' . substr($userId, 0, 8));

                    $mappedPdsId = null;
                    if (isset($userToPds[$userId])) {
                        $mappedPdsId = $userToPds[$userId]->pds_id;
                    }
                    if ($needFilterPdsSide && !$mappedPdsId) {
                        continue;
                    }

                    $selectedPds = $defaultPds;
                    if ($mappedPdsId && isset($pdsListForFallback[$mappedPdsId])) {
                        $selectedPds = $pdsListForFallback[$mappedPdsId];
                    }

                    $pdsName = $selectedPds->pds_name ?? 'Without PDS';
                    $campaignName = $selectedPds->campaign?->name ?? '-';
                    $spvName = $selectedPds->spv?->company_user?->name ?? $selectedPds->spv?->name ?? '-';
                    $pdsId = $selectedPds->id ?? null;

                    $r = $buildRow($agg);

                    $sLog = null;
                    if ($pdsId && isset($sessionLogs[$pdsId . '_' . $date])) {
                        $sLog = $sessionLogs[$pdsId . '_' . $date];
                    }
                    if (!$sLog) {
                        $sLog = (object) [
                            'SessionStart' => $date . ' 06:00:00',
                            'SessionEnd' => $date . ' 23:59:59',
                        ];
                    }

                    $obj = (object) [
                        'id' => 'fb_' . $userId . '_' . $date,
                        'date' => $date,
                        'pds_id' => $pdsId,
                        'user_id' => $userId,
                        'created_at' => now(),
                        'PTP' => $r['ptp'],
                        'CallBack' => $r['callback'],
                        'BPPartial' => $r['bpp'],
                        'NBPA' => $r['nbpa'],
                        'NBPB' => $r['nbpb'],
                        'NBPC' => $r['nbpc'],
                        'PaidinConfins' => $r['pic'],
                        'KP' => $r['kp'],
                        'VisitRequest' => $r['vr'],
                        'VisitRequestContacted' => $r['vrc'],
                        'NoStatus' => $r['noStatus'],
                        'data_contacted' => $r['dataContacted'],
                        'data_utilize' => (int) ($r['dataContacted'] > 0 ? $r['dataContacted'] : array_sum($r['valueMap'])),
                        'companyUser' => (object) ['name' => $agentName],
                        'company_user' => (object) ['name' => $agentName],
                        'pds' => (object) [
                            'pds_name' => $pdsName,
                            'campaign' => (object) ['name' => $campaignName],
                            'spv' => (object) [
                                'name' => $spvName,
                                'company_user' => (object) ['name' => $spvName],
                            ],
                        ],
                        'session_log' => $sLog,
                        'outbounds' => $r['outboundsFinal'],
                        'ticket_status_count' => $r['ticketStatus'],
                    ];
                    $items->push($obj);
                }
            }
        }
    }

    $items = $filterCollectionByPds($items);

    if ($limit === null) {
        $data = $items;
    } else {
        $perPage = (int) ($limit > 0 ? $limit : 10);
        $page = (int) request('page', 1);
        $total = $items->count();
        $sliceOffset = ($page - 1) * $perPage;
        $pageItems = $items->slice($sliceOffset, $perPage)->values();
        $data = new \Illuminate\Pagination\LengthAwarePaginator(
            $pageItems,
            $total,
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }

    return $data;
}
}
