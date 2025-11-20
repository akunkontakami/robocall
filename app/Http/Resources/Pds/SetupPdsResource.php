<?php

namespace App\Http\Resources\Pds;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SetupPdsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'date' => Carbon::parse($this->date ?? $this->created_at)->format("Y-m-d"),
            'tenant' => $this->tenant_id,
            'name' => $this->pds_name,
            'spv' => $this->spv,
            'agents' => $this->agents,
            'is_running' => $this->is_running,
            'total_agent' => count($this->agents),
            'total_data' => count($this->customers),
            'campaign' => $this->campaign?->name ?? '-',
            'campaign_status' => $this->campaign?->status ?? 'non_active',
        ];
    }
}
