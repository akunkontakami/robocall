<?php

namespace App\Http\Resources\Pds;

use App\Services\Pds\MonitoringPdsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MonitoringResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $log = (new MonitoringPdsService())->sessionLogs($this->pds_name);
        $activity = (new MonitoringPdsService())->sessionActivities($this->pds_name);

        $agentReady = (new MonitoringPdsService())->getAgentReady($this->agents);

        return [
            'id' => $this->id,
            'date' => Carbon::parse($this->date ?? $this->created_at)->format("Y-m-d"),
            'tenant' => $this->tenant_id,
            'name' => $this->pds_name,
            'spv' => $this->spv?->company_user ? $this->spv->company_user->name : $this->spv?->name,
            'is_running' => $this->is_running,
            'total_agent' => $agentReady,
            'campaign' => $this->campaign?->name ?? '-',
            'data_size' => $log->DataSize,
            'retry' => $log->DialCount,
            'data_dialed' => $log->DataDialed,
            'calls' => $log->DataDialed,
            'contacted' => $log->DialContacted,
            'failed' => $log->DialFailed,
            'answered' => $log->DialAgentAnswered,
            'abandoned' => $log->DialAbandoned,
            'abandoned_rate' => $log->AbandonedRate,
            'abandoned_rate_num' => $log->AbandonedRateNum,
            'call_in_progress' => $activity->DialInProgress,
            'max_abandoned' => $this->call_abandon_rate
        ];
    }
}
