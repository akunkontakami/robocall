<?php

namespace App\Services\Data;

use App\Models\Data\Ticket;
use Illuminate\Support\Facades\DB;

class TicketService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function getStatus($companyId, $campaignId = '', $spvId = '')
    {
        if (!$spvId || !$campaignId) return [];
        return Ticket::select('status', DB::raw('COUNT(*) as total'))
        ->withWhereHas("dataBucket")
        ->where("company_id", $companyId)
        ->whereNotNull("customer_id")
        ->whereNotNull("outbound_data_upload_id")
        ->where("type", "outbound")
        ->where("marketing_campaign_id", $campaignId)
        ->where("spv_id", $spvId)
        ->groupBy("status")
        ->get()->each(function ($row) {
            $row->id = $row->status;
            $row->value = $row->status . " - " . $row->total;
            return $row;
        });
    }

    public function getCustByTicket($companyId, $campaignId = '', $spvId = '', $status = [])
    {
        if (!$spvId || !$campaignId) return [];
        return Ticket::withWhereHas("dataBucket")
            ->where("company_id", $companyId)
            ->whereNotNull("customer_id")
            ->whereNotNull("outbound_data_upload_id")
            ->where("type", "outbound")
            ->whereIn("status", $status)
            ->where("marketing_campaign_id", $campaignId)
            ->where("spv_id", $spvId)
            ->get()
            ->map(function ($ticket) {
                $json = json_decode(optional($ticket->dataBucket)->data, true);
                return [
                    "customer_id" => $ticket->customer_id,
                    "phone" => $json["mobile_phone"] ?? null,
                ];
            })
            ->filter(fn ($row) => !empty($row["phone"]))
            ->values()
            ->toArray();
    }
}
