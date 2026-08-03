<?php

namespace App\Services\Data;

use App\Models\Data\OutboundStatus;
use App\Models\Data\Ticket;
use App\Models\Pds\PdsAgent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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

        $offices = request('offices', []);
        $type = Str::upper(request('type'));

        $riskMap = [
            'low_risk' => 'LOW',
            'medium_risk' => 'MEDIUM',
            'high_risk' => 'HIGH',
        ];

        $riskCriteria = collect(request('risk_criteria', []))
            ->map(function ($item) use ($riskMap) {
                return [
                    'risk' => $riskMap[$item['risk']] ?? null,
                    'number' => $item['number'] ?? null,
                ];
            })
            ->filter(fn ($item) => $item['risk'] && $item['number'])
            ->values()
            ->toArray();

        $agents = PdsAgent::query()
            ->where('pds_id', $pdsId)
            ->pluck('user_id')
            ->toArray();

        return Ticket::query()
            ->select([
                'tickets.status',
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
                DB::raw('
                    MAX(
                        (
                            SELECT COUNT(*)
                            FROM ticket_additional_phones
                            WHERE ticket_additional_phones.customer_number = tickets.customer_number
                        )
                    ) as max_additional_phone
                '),
            ])
            ->leftJoin('company_users', function ($join) {
                $join->on('company_users.user_id', '=', 'tickets.current_agent_id');
            })
            ->withWhereHas('dataBucket')
            ->where('tickets.company_id', $companyId)
            ->whereNotNull('outbound_data_upload_id')
            ->where('tickets.type', 'outbound')
            ->where('marketing_campaign_id', $campaignId)
            ->where(function ($q) use ($agents) {
                $q->whereIn('current_agent_id', $agents)
                ->orWhereNull('current_agent_id');
            })
            ->when($type === 'BRANCH', function ($q) use ($offices) {
                $q->whereIn(
                    DB::raw("JSON_VALUE(bucket, '$.OFFICE_NAME')"),
                    $offices
                );
            })
            ->where('company_users.type', $type)
            ->whereRaw("COALESCE(JSON_VALUE(bucket, '$.CUSTOMER_NAME'), '') <> ?", ['-'])
            ->where(function ($q) {
                $customerName = "COALESCE(JSON_VALUE(bucket, '$.CUSTOMER_NAME'), '')";
                $categoryDistribution = "COALESCE(JSON_VALUE(bucket, '$.category_distribution'), '')";

                $q->whereRaw("$categoryDistribution <> ?", ['KP'])
                ->orWhere(function ($w) {
                    $w->whereNull('is_blocked')
                    ->orWhere('is_blocked', '<>', 1);
                })
                ->orWhereIn('tickets.status', [
                    'Paid in Confins',
                    'Visit Request',
                    'Visit Request BP(2x)',
                    'Visit Request BP 2X',
                    'VISIT REQUEST BP(2x)',
                    'Auto Visit Request',
                    'AUTO VISIT REQUEST',
                    'KP',
                    'VISIT REQUEST (UNCONTACT)',
                    'VISIT REQUEST (CONTACT)',
                    'VISIT REQUEST (UNCONTACTED)',
                    'VISIT REQUEST (CONTACTED)',
                    'CASE REQUEST',
                    'Case Request',
                ]);
            })
            ->where(function ($q) use ($riskCriteria) {
                $risk = "COALESCE(JSON_VALUE(bucket, '$.DR_RISK'), '')";
                $odDays = "COALESCE(JSON_VALUE(bucket, '$.OVERDUE_DAYS'), '')";

                if (count($riskCriteria) > 0) {
                    $q->where(function ($r) use ($riskCriteria, $risk, $odDays) {
                        foreach ($riskCriteria as $criteria) {
                            $r->orWhere(function ($x) use ($criteria, $risk, $odDays) {
                                $x->whereRaw("$risk = ?", [$criteria['risk']])
                                ->whereRaw("CAST($odDays AS UNSIGNED) = ?", [$criteria['number']]);
                            });
                        }
                    });
                }
            })
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('ticket_histories')
                    ->whereColumn('ticket_histories.ticket_id', 'tickets.id')
                    ->whereIn('ticket_histories.status', ['Promised to Pay (PTP)','Paid in Confins','KP']);
            })
            ->groupBy('tickets.status')
            ->get()
            ->each(function ($row) {
                $row->id = $row->status;
                $row->value = $row->status.' - '.$row->total;
                $row->max_mobile = (int) $row->max_mobile;
                $row->max_additional_phone = (int) $row->max_additional_phone;

                return $row;
            });
    }

    public function getCustByTicket($companyId, $campaignId = '', $pdsId = '', $status = [], $selectedMobiles = [], $selectedAdditionals = [], $selectedRiskCriteria = [])
    {
        if (!$pdsId || !$campaignId) {
            return [];
        }

        $offices = request('offices', []);
        $type = Str::upper(request('type'));

        $riskMap = [
            'low_risk' => 'LOW',
            'medium_risk' => 'MEDIUM',
            'high_risk' => 'HIGH',
        ];

        $riskCriteria = collect($selectedRiskCriteria)
            ->flatMap(function ($numbers, $riskKey) use ($riskMap) {
                return collect($numbers)->map(function ($number) use ($riskMap, $riskKey) {
                    return [
                        'risk' => $riskMap[$riskKey] ?? null,
                        'number' => $number,
                    ];
                });
            })
            ->filter(fn ($item) => $item['risk'] && $item['number'])
            ->values()
            ->toArray();

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
            ->leftJoin('company_users', function ($join) {
                $join->on('company_users.user_id', '=', 'tickets.current_agent_id');
            })
            ->where('tickets.company_id', $companyId)
            ->whereNotNull('outbound_data_upload_id')
            ->where('tickets.type', 'outbound')
            ->whereIn('tickets.status', $status)
            ->where('marketing_campaign_id', $campaignId)
            ->where(function ($q) use ($agents) {
                $q->whereIn('current_agent_id', $agents)
                ->orWhereNull('current_agent_id');
            })
            ->when($type === 'BRANCH', function ($q) use ($offices) {
                $q->whereIn(
                    DB::raw("JSON_VALUE(bucket, '$.OFFICE_NAME')"),
                    $offices
                );
            })
            ->where('company_users.type', $type)
            ->whereRaw("COALESCE(JSON_VALUE(bucket, '$.CUSTOMER_NAME'), '') <> ?", ['-'])
            ->where(function ($q) {
                $customerName = "COALESCE(JSON_VALUE(bucket, '$.CUSTOMER_NAME'), '')";
                $categoryDistribution = "COALESCE(JSON_VALUE(bucket, '$.category_distribution'), '')";

                // $q->where(function ($w) use ($customerName, $categoryDistribution) {
                //     $w->whereRaw("$customerName <> ?", ['-'])
                // })

                $q->whereRaw("$categoryDistribution <> ?", ['KP'])
                ->orWhere(function ($w) {
                    $w->whereNull('is_blocked')
                    ->orWhere('is_blocked', '<>', 1);
                })
                ->orWhereIn('tickets.status', [
                    'Paid in Confins',
                    'Visit Request',
                    'Visit Request BP(2x)',
                    'Visit Request BP 2X',
                    'VISIT REQUEST BP(2x)',
                    'Auto Visit Request',
                    'AUTO VISIT REQUEST',
                    'KP',
                    'VISIT REQUEST (UNCONTACT)',
                    'VISIT REQUEST (CONTACT)',
                    'VISIT REQUEST (UNCONTACTED)',
                    'VISIT REQUEST (CONTACTED)',
                    'CASE REQUEST',
                    'Case Request',
                ]);
            })
            ->where(function ($q) use ($riskCriteria) {
                $risk = "COALESCE(JSON_VALUE(bucket, '$.DR_RISK'), '')";
                $odDays = "COALESCE(JSON_VALUE(bucket, '$.OVERDUE_DAYS'), '')";

                if (count($riskCriteria) > 0) {
                    $q->where(function ($r) use ($riskCriteria, $risk, $odDays) {
                        foreach ($riskCriteria as $criteria) {
                            $r->orWhere(function ($x) use ($criteria, $risk, $odDays) {
                                $x->whereRaw("$risk = ?", [$criteria['risk']])
                                ->whereRaw("CAST($odDays AS UNSIGNED) = ?", [$criteria['number']]);
                            });
                        }
                    });
                }
            })
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('ticket_histories')
                    ->whereColumn('ticket_histories.ticket_id', 'tickets.id')
                    ->where('ticket_histories.status', 'Promised to Pay (PTP)');
            })
            ->select('tickets.*')
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
                            $phone = '0'.substr($phone, 2);
                        } elseif (str_starts_with($phone, '8')) {
                            $phone = '0'.$phone;
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

    public function getOffices($companyId, $campaignId = '', $pdsId = '')
    {
        if (!$pdsId || !$campaignId) {
            return [];
        }

        $agents = PdsAgent::query()
            ->where('pds_id', $pdsId)
            ->pluck('user_id')
            ->toArray();

        return Ticket::query()
            ->selectRaw("
                DISTINCT JSON_VALUE(bucket, '$.OFFICE_NAME') as office_name
            ")
            ->withWhereHas('dataBucket')
            ->where('company_id', $companyId)
            ->whereNotNull('outbound_data_upload_id')
            ->where('type', 'outbound')
            ->where('marketing_campaign_id', $campaignId)
            ->where(function ($q) use ($agents) {
                $q->whereIn('current_agent_id', $agents)
                ->orWhereNull('current_agent_id');
            })
            ->whereNotNull(DB::raw("JSON_VALUE(bucket, '$.OFFICE_NAME')"))
            ->whereRaw("JSON_VALUE(bucket, '$.OFFICE_NAME') <> ''")
            ->orderBy('office_name')
            ->get()
            ->map(function ($row) {
                return [
                    'id' => $row->office_name,
                    'value' => $row->office_name,
                ];
            });
    }
}
