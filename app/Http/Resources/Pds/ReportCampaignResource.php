<?php

namespace App\Http\Resources\Pds;

use App\Services\Pds\MonitoringPdsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class ReportCampaignResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $start = @$request->created_start;
        $end = @$request->created_end;

        $log = (new MonitoringPdsService())->pdsHistoryLogs($this->pds_name, $start, $end);
        $agentReady = (new MonitoringPdsService())->getAgentReady($this->agents);

        $duration = '00:00:00';
        if ($log->SessionStart && $log->SessionEnd) {
            $duration = gmdate('H:i:s', max(0, Carbon::parse($log->SessionEnd)
                ->diffInSeconds(Carbon::parse($log->SessionStart))));
        }

        $ticketStatuses = $this->outbounds;
        $ticketCount = DB::table('ticket_histories as th')
            ->where('th.company_id', $this->company_id)
            ->joinSub(
                DB::table('ticket_histories')
                    ->select('ticket_id', DB::raw('MAX(created_at) as last_created'))
                    ->where('company_id', $this->company_id)
                    ->when($start && $end, fn($q) => $q->whereBetween('created_at', [$start, $end]))
                    ->groupBy('ticket_id'),
                'last',
                fn($join) => $join->on('last.ticket_id', '=', 'th.ticket_id')
                    ->on('last.last_created', '=', 'th.created_at')
            )
            ->join('tickets', 'tickets.id', '=', 'th.ticket_id')
            ->where('tickets.marketing_campaign_id', $this->marketing_campaign_id)
            ->when($start && $end, fn($q) => $q->whereBetween('th.created_at', [$start, $end]))
            ->whereIn('th.status', $ticketStatuses)
            ->selectRaw('th.status, COUNT(DISTINCT th.ticket_id) as total')
            ->groupBy('th.status')
            ->pluck('total', 'th.status');

        return [
            'id' => $this->id,
            'campaign' => $this->campaign?->name ?? '-',
            'session_start' => $log->SessionStart,
            'session_end' => $log->SessionEnd,
            'start_date' => $log->SessionStart ? Carbon::parse($log->SessionStart)->format("d M Y") : '',
            'start_time' => $log->SessionStart ? Carbon::parse($log->SessionStart)->format("H.i") : '',
            'end_date' => $log->SessionEnd ? Carbon::parse($log->SessionEnd)->format("d M Y") : '',
            'end_time' => $log->SessionEnd ? Carbon::parse($log->SessionEnd)->format("H.i") : '',
            'name' => $this->pds_name,
            'total_agent' => $agentReady,
            'data_size' => $log->DataSize,
            'data_utilize' => $log->DataDialed,
            'calls' => $log->DialCount,
            'contacted' => $log->DialContacted,
            'uncontacted' => $log->DialFailed,
            'abandoned' => $log->DialAbandoned,
            'unutilize' => $log->DataSize - $log->DataDialed,
            'answered' => $log->DialAgentAnswered,
            'abandoned_rate' => $log->AbandonedRate,
            'abandoned_rate_num' => $log->AbandonedRateNum,
            'duration_pds' => $duration,
            'ticket_status' => $ticketCount
        ];
    }
}
