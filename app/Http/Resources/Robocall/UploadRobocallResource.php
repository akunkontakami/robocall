<?php

namespace App\Http\Resources\Robocall;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UploadRobocallResource extends JsonResource
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
            'name' => $this->file_name,
            'total_data' => count($this->customers),
            'progress' => '100%',
        ];
    }
}
