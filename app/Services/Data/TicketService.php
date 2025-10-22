<?php

namespace App\Services\Data;

use App\Models\Data\Ticket;
use App\Models\Pds\PdsAgent;
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

    public function getStatus($companyId, $campaignId = '', $pdsId = '')
    {
        if (!$pdsId || !$campaignId) return [];

        $agents = PdsAgent::query()->where("pds_id", $pdsId)->pluck("user_id")->toArray();

        return Ticket::select('status', DB::raw('COUNT(*) as total'))
        ->withWhereHas("dataBucket")
        ->where("company_id", $companyId)
        // ->whereNotNull("customer_id")
        ->whereNotNull("outbound_data_upload_id")
        ->where("type", "outbound")
        ->where("marketing_campaign_id", $campaignId)
        ->where(function ($q) use ($agents) {
            // $q->whereIn("current_agent_id", $agents)->orWhereNull("current_agent_id");
            $q->whereNull("current_agent_id");
        })
        ->groupBy("status")
        ->get()->each(function ($row) {
            $row->id = $row->status;
            $row->value = $row->status . " - " . $row->total;
            return $row;
        });
    }

    public function getCustByTicket($companyId, $campaignId = '', $pdsId = '', $status = [])
    {
        if (!$pdsId || !$campaignId) return [];

        $agents = PdsAgent::query()->where("pds_id", $pdsId)->pluck("user_id")->toArray();

        return Ticket::withWhereHas("dataBucket")
            ->where("company_id", $companyId)
            // ->whereNotNull("customer_id")
            ->whereNotNull("outbound_data_upload_id")
            ->where("type", "outbound")
            ->whereIn("status", $status)
            ->where("marketing_campaign_id", $campaignId)
            ->where(function ($q) use ($agents) {
                // $q->whereIn("current_agent_id", $agents)->orWhereNull("current_agent_id");
                $q->whereNull("current_agent_id");
            })
            ->get()
            ->map(function ($ticket) {
                $json = json_decode(optional($ticket->dataBucket)->data, true);
                $phone = $json["mobile_phone"] ?? null;

                if ($phone && str_starts_with($phone, "62")) {
                    $phone = "0" . substr($phone, 2);
                }

                return [
                    "customer_id" => $ticket->id,
                    "phone" => $phone,
                ];
            })
            ->filter(fn ($row) => !empty($row["phone"]))
            ->values()
            ->toArray();
    }
}
