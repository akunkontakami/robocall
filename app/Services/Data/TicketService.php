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

        $agents = PdsAgent::query()
            ->where('pds_id', $pdsId)
            ->pluck('user_id')
            ->toArray();

        return Ticket::query()
            ->select([
                'status',
                DB::raw('COUNT(*) as total'),
                DB::raw("
                    MAX(
                        CASE
                            WHEN JSON_VALUE(bucket, '$.MOBILE_PHONE') IS NULL
                                OR JSON_VALUE(bucket, '$.MOBILE_PHONE') = ''
                            THEN 0
                            ELSE
                                LENGTH(JSON_VALUE(bucket, '$.MOBILE_PHONE'))
                                - LENGTH(REPLACE(JSON_VALUE(bucket, '$.MOBILE_PHONE'), ',', '')) + 1
                        END
                    ) as max_mobile
                "),
                DB::raw("
                    MAX(
                        (
                            SELECT COUNT(*)
                            FROM ticket_additional_phones
                            WHERE ticket_additional_phones.customer_number = tickets.customer_number
                        )
                    ) as max_additional_phone
                "),
            ])
            ->withWhereHas('dataBucket')
            ->where('company_id', $companyId)
            ->whereNotNull('outbound_data_upload_id')
            ->where('type', 'outbound')
            ->where('marketing_campaign_id', $campaignId)
            ->where(function ($q) use ($agents) {
                $q->whereIn('current_agent_id', $agents)
                ->orWhereNull('current_agent_id');
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
            ->groupBy('status')
            ->get()
            ->each(function ($row) {
                $row->id = $row->status;
                $row->value = $row->status . ' - ' . $row->total;
                $row->max_mobile = (int) $row->max_mobile;
                $row->max_additional_phone = (int) $row->max_additional_phone;

                return $row;
            });
    }

    public function getCustByTicket($companyId, $campaignId = '', $pdsId = '', $status = [], $selectedMobiles = [], $selectedAdditionals = [])
    {
        if (!$pdsId || !$campaignId) {
            return [];
        }

        $agents = PdsAgent::query()
            ->where('pds_id', $pdsId)
            ->pluck('user_id')
            ->toArray();

        $mobileIndexes = collect($selectedMobiles)
            ->map(function ($label) {
                preg_match('/(\d+)$/', $label, $matches);

                return isset($matches[1]) ? ((int) $matches[1] - 1) : null;
            })
            ->filter(fn ($index) => $index !== null && $index >= 0)
            ->unique()
            ->values()
            ->toArray();

        $additionalIndexes = collect($selectedAdditionals)
            ->map(function ($label) {
                preg_match('/(\d+)$/', $label, $matches);

                return isset($matches[1]) ? ((int) $matches[1] - 1) : null;
            })
            ->filter(fn ($index) => $index !== null && $index >= 0)
            ->unique()
            ->values()
            ->toArray();

        $data = Ticket::withWhereHas('dataBucket')
            ->with('additionalPhones')
            ->where('company_id', $companyId)
            ->whereNotNull('outbound_data_upload_id')
            ->where('type', 'outbound')
            ->whereIn('status', $status)
            ->where('marketing_campaign_id', $campaignId)
            ->where(function ($q) use ($agents) {
                $q->whereIn('current_agent_id', $agents)
                ->orWhereNull('current_agent_id');
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

        return $data
            ->flatMap(function ($ticket) use ($mobileIndexes, $additionalIndexes) {
                $json = json_decode(optional($ticket->dataBucket)->data, true);

                $rawPhones = $json['MOBILE_PHONE'] ?? '';
                $phones = collect(explode(',', $rawPhones))
                    ->map(fn ($phone) => trim($phone))
                    ->filter(fn ($phone) => !empty($phone))
                    ->values();

                $additionalPhones = $ticket->additionalPhones
                    ->pluck('phone')
                    ->map(fn ($phone) => trim($phone))
                    ->filter(fn ($phone) => !empty($phone))
                    ->values();

                $selectedMainPhones = collect($mobileIndexes)
                    ->map(fn ($index) => $phones->get($index))
                    ->filter(fn ($phone) => !empty($phone));

                $selectedAdditionalPhones = collect($additionalIndexes)
                    ->map(fn ($index) => $additionalPhones->get($index))
                    ->filter(fn ($phone) => !empty($phone));

                $selectedPhones = $selectedMainPhones
                    ->merge($selectedAdditionalPhones)
                    ->map(function ($phone) {
                        if (str_starts_with($phone, '62')) {
                            $phone = '0' . substr($phone, 2);
                        } elseif (str_starts_with($phone, '8')) {
                            $phone = '0' . $phone;
                        }


                        return $phone;
                    })
                    ->unique()
                    ->values();

                return $selectedPhones->map(function ($phone) use ($ticket) {
                    return [
                        'customer_id' => $ticket->id,
                        'phone' => $phone,
                    ];
                });
            })
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
