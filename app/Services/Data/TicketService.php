<?php

namespace App\Services\Data;

use App\Models\Data\OutboundStatus;
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
    }

    public function getStatus($companyId, $campaignId = '', $pdsId = '')
    {
        if (!$pdsId || !$campaignId) {
            return [];
        }

        $agents = PdsAgent::query()->where('pds_id', $pdsId)->pluck('user_id')->toArray();

        return Ticket::select('status', DB::raw('COUNT(*) as total'))
        ->withWhereHas('dataBucket')
        ->where('company_id', $companyId)
        // ->whereNotNull("customer_id")
        ->whereNotNull('outbound_data_upload_id')
        ->where('type', 'outbound')
        ->where('marketing_campaign_id', $campaignId)
        ->where(function ($q) use ($agents) {
            $q->whereIn('current_agent_id', $agents)->orWhereNull('current_agent_id');
        })
        ->where(function ($q) {
            $customerName = "COALESCE(JSON_VALUE(bucket, '$.CUSTOMER_NAME'), '')";
            $categoryDistribution = "COALESCE(JSON_VALUE(bucket, '$.category_distribution'), '')";

            $q->whereRaw("$customerName <> ?", ['-'])
            ->whereRaw("$categoryDistribution <> ?", ['KP'])
            // ->whereNotIn('status', ['Visit Request', 'VISIT REQUEST (UNCONTACT)', 'VISIT REQUEST (CONTACT)', 'VISIT REQUEST (UNCONTACTED)', 'VISIT REQUEST (CONTACTED)', 'CASE REQUEST', 'Case Request'])
            ->where(function ($w) {
                $w->whereNull('is_blocked')
                    ->orWhere('is_blocked', '<>', 1);
            });
        })
        ->groupBy('status')
        ->get()->each(function ($row) {
            $row->id = $row->status;
            $row->value = $row->status.' - '.$row->total;

            return $row;
        });
    }

    public function getCustByTicket($companyId, $campaignId = '', $pdsId = '', $status = [])
    {
        if (!$pdsId || !$campaignId) {
            return [];
        }

        $agents = PdsAgent::query()->where('pds_id', $pdsId)->pluck('user_id')->toArray();

        $data = Ticket::withWhereHas('dataBucket')
            ->where('company_id', $companyId)
            // ->whereNotNull("customer_id")
            ->whereNotNull('outbound_data_upload_id')
            ->where('type', 'outbound')
            ->whereIn('status', $status)
            ->where('marketing_campaign_id', $campaignId)
            ->where(function ($q) use ($agents) {
                $q->whereIn('current_agent_id', $agents)->orWhereNull('current_agent_id');
            })
            ->where(function ($q) {
                $customerName = "COALESCE(JSON_VALUE(bucket, '$.CUSTOMER_NAME'), '')";
                $categoryDistribution = "COALESCE(JSON_VALUE(bucket, '$.category_distribution'), '')";

                $q->whereRaw("$customerName <> ?", ['-'])
                ->whereRaw("$categoryDistribution <> ?", ['KP'])
                ->where(function ($w) {
                    $w->whereNull('is_blocked')
                        ->orWhere('is_blocked', '<>', 1);
                });
            })
            ->get();

        return $data->map(function ($ticket) {
                $json = json_decode(optional($ticket->dataBucket)->data, true);
                $phone = $json['MOBILE_PHONE'] ?? null;
                $phones = explode(",", $phone);
                $phone = @$phones[0] ?: '';

                if ($phone && str_starts_with($phone, '62')) {
                    $phone = '0'.substr($phone, 2);
                }

                return [
                    'customer_id' => $ticket->id,
                    'phone' => $phone,
                ];
            })
            ->filter(fn ($row) => !empty($row['phone']))
            ->values()
            ->toArray();
    }

    public function getOutboundStatus($companyId)
    {
        $status = [];
        $data = OutboundStatus::with([
            'sub' => function ($query) {
                $query->select('id', 'parent_id', 'name', 'submit_without_fill', 'status_category');
            },
            'sub.sub' => function ($query) {
                $query->select('id', 'parent_id', 'name', 'submit_without_fill', 'status_category');
            },
        ])
                    ->where('company_id', $companyId)
                    ->orderBy('sorting', 'asc')
                    ->select('id', 'parent_id', 'name', 'submit_without_fill', 'status_category')
                    ->where('status', 'active')
                    ->whereNull('parent_id')
                    ->get();

        $this->collectStatusNames($data, $status);

        return $status;
    }

    private function collectStatusNames($items, array &$status)
    {
        foreach ($items as $item) {
            $status[] = $item->name;

            if ($item->sub && $item->sub->count()) {
                $this->collectStatusNames($item->sub, $status);
            }
        }
    }
}
