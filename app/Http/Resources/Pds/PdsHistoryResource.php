<?php

namespace App\Http\Resources\Pds;

use App\Services\Pds\MonitoringPdsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PdsHistoryResource extends JsonResource
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

        return [
            'id' => $this->id,
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
            'answered' => $log->DialAgentAnswered,
            'abandoned_rate' => $log->AbandonedRate,
            'abandoned_rate_num' => $log->AbandonedRateNum
        ];
    }
}
