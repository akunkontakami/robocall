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

    public function getStatus($companyId)
    {
        return Ticket::select('status', DB::raw('COUNT(*) as total'))
        ->where("company_id", $companyId)
        ->whereNotNull("customer_id")
        ->where("type", "outbound")
        ->groupBy("status")
        ->get()->each(function ($row) {
            $row->id = $row->status;
            $row->value = $row->status . " - " . $row->total;
            return $row;
        });
    }
}
