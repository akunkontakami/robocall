<?php

namespace App\Http\Resources\Pds;

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
        $pds = $this->pds;
        $log = $this->session_log ?: (object) [
            'SessionStart' => null,
            'SessionEnd' => null,
        ];

        $outbounds = collect($this->outbounds ?? [])
            ->map(fn($outbound) => is_array($outbound) ? ($outbound['name'] ?? null) : $outbound)
            ->filter()
            ->values();

        $ticketCount = collect($this->ticket_status_count ?? []);
        $ticketStatus = $outbounds->mapWithKeys(fn($status) => [
            $status => (int) ($ticketCount[$status] ?? 0),
        ])->all();

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
            'data_utilize' => (int) ($this->data_utilize ?? $this->ticket_count ?? array_sum($ticketStatus)),
            'ticket_status' => $ticketStatus,
            'spv' => $this->pds?->spv?->company_user ? $this->pds?->spv->company_user->name : $this->pds?->spv?->name,
            'agent' => $this->companyUser ? $this->companyUser->name : $this->name
        ];
    }
}
