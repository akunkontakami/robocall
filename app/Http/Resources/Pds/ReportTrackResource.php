<?php

namespace App\Http\Resources\Pds;

use App\Services\Pds\MonitoringPdsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportTrackResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $filter = $request->filter;
        $start = @$filter['created_start'];
        $end = @$filter['created_end'];

        $log = (new MonitoringPdsService())->pdsHistoryLogs($this->pds_name, $start, $end);
        $Utilize = $log->DataSize - $log->DataDialed;
        $UtilizeCallRatio = $log->DataDialed > 0 ? round(($log->DialCount / $log->DataDialed), 2) : 0;
        $UtilizePercentage = $log->DataSize > 0 ? round(($log->DialCount / $log->DataSize) * 100, 2) : 0;
        $ContactedPercentage = $Utilize > 0 ? round(($log->DialContacted / $Utilize) * 100, 2) : 0;
        $UncontactedPercentage = $Utilize > 0 ? round(($log->DialFailed / $Utilize) * 100, 2) : 0;

        $UnutilizePercentage = $log->DataSize > 0 ? round(($Utilize / $log->DataSize) * 100, 2) : 0;

        $duration = null;
        if ($log->SessionStart && $log->SessionEnd) {
            $startTime = Carbon::parse($log->SessionStart);
            $endTime = Carbon::parse($log->SessionEnd);
            $duration = $endTime->diff($startTime)->format('%H:%I:%S');
        }

        $ticketStatuses = ["Still Thinking", "Incoming", "Disagree", "Callback"];
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
            'total_agent' => count($this->agents),
            'data_size' => $log->DataSize,
            'data_utilize' => $log->DataDialed,
            'calls' => $log->DialCount,
            'contacted' => $log->DialContacted,
            'uncontacted' => $log->DialFailed,
            'abandoned' => $log->DialAbandoned,
            'data_utilize' => $log->DataDialed,
            'unutilize' => $Utilize,
            'answered' => $log->DialAgentAnswered,
            'abandoned_rate' => $log->AbandonedRate,
            'abandoned_rate_num' => $log->AbandonedRateNum,
            'duration_pds' => $duration,
            'still_thinking' => $ticketCount['Still Thinking'] ?? 0,
            'incoming' => $ticketCount['Incoming'] ?? 0,
            'disagree' => $ticketCount['Disagree'] ?? 0,
            'callback' => $ticketCount['Callback'] ?? 0,
            'utilize_percentage' => "$UtilizePercentage%",
            'utilize_call_ratio' => $UtilizeCallRatio,
            'unutilize_percentage' => "$UnutilizePercentage%",
            'contacted_percentage' => "$ContactedPercentage%",
            'uncontacted_percentage' => "$UncontactedPercentage%",
        ];
    }
}
