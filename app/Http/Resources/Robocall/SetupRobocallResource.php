<?php

namespace App\Http\Resources\Robocall;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SetupRobocallResource extends JsonResource
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
            'date' => Carbon::parse($this->date ?? $this->created_at)->format('Y-m-d'),
            'name' => $this->robocall_name,
            'is_running' => $this->is_running,
            'total_data' => count($this->customers),
            'campaign' => $this->campaign?->name ?? '-',
            'campaign_status' => $this->data_type == 'upload' ? 'active' : ($this->campaign?->status ?? 'non_active'),
        ];
    }
}
