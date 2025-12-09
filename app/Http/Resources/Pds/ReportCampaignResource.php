<?php

namespace App\Http\Resources\Pds;

use App\Services\Pds\MonitoringPdsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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

        $duration =gmdate("H:i:s", $log->Duration);
        // if ($log->SessionStart && $log->SessionEnd) {
        //     $startTime = Carbon::parse($log->SessionStart);
        //     $endTime = Carbon::parse($log->SessionEnd);
        //     $duration = $endTime->diff($startTime)->format('%H:%I:%S');
        // }

        $ticketStatuses = $this->outbounds;
        $ticketCount = $this->tickets()
            ->whereIn('status', $ticketStatuses)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');

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
