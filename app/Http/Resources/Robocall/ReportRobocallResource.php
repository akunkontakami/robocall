<?php

namespace App\Http\Resources\Robocall;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportRobocallResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'dial_time' => @$this['DialTime'] ? Carbon::parse(@$this['DialTime'])->format('Y-m-d H:i:s') : '',
            'dialed_number' => @$this['DialedNumber'],
            'session_id' => @$this['SessionId'],
            'campaign_id' => @$this['campaign_id'],
            'tenant_id' => @$this['tenant_id'],
            'customer_number' => @$this['CustomerNumber'],
            'customer_name' => @$this['CustomerName'],
            'dial_status' => @$this['DialStatusText'],
            'call_status' => @$this['CallStatusText'],
        ];
    }
}
