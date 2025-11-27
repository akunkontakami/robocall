<?php

namespace App\Http\Resources\Pds;

use App\Services\Pds\MonitoringPdsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportAgentResource extends JsonResource
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
        $pds = $this->pds;

        $log = (new MonitoringPdsService())->pdsHistoryLogs($pds->pds_name, $start, $end);

        $ticketStatuses = ["Still Thinking", "Incoming", "Disagree", "Callback"];
        $ticketCount = $pds->tickets()
            ->whereIn('status', $ticketStatuses)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');

        return [
            'id' => $this->id,
            'campaign' => $pds->campaign?->name ?? '-',
            'session_start' => $log->SessionStart,
            'session_end' => $log->SessionEnd,
            'start_date' => $log->SessionStart ? Carbon::parse($log->SessionStart)->format("d M Y") : '',
            'start_time' => $log->SessionStart ? Carbon::parse($log->SessionStart)->format("H.i") : '',
            'end_date' => $log->SessionEnd ? Carbon::parse($log->SessionEnd)->format("d M Y") : '',
            'end_time' => $log->SessionEnd ? Carbon::parse($log->SessionEnd)->format("H.i") : '',
            'name' => $pds->pds_name,
            'data_utilize' => $log->DataDialed,
            'still_thinking' => $ticketCount['Still Thinking'] ?? 0,
            'incoming' => $ticketCount['Incoming'] ?? 0,
            'disagree' => $ticketCount['Disagree'] ?? 0,
            'callback' => $ticketCount['Callback'] ?? 0,
            'spv' => $this->pds?->spv?->company_user ? $this->pds?->spv->company_user->name : $this->pds?->spv?->name,
            'agent' => $this->companyUser ? $this->companyUser->name : $this->name
        ];
    }
}
