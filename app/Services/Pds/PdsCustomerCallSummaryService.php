<?php

namespace App\Services\Pds;

use Illuminate\Support\Facades\DB;

class PdsCustomerCallSummaryService
{
    public function getByPdsIds(int $companyId, array $pdsIds): array
    {
        $pdsIds = array_values(array_filter(array_unique($pdsIds)));

        if (!count($pdsIds)) {
            return [];
        }

        $rows = DB::table('pds_customers')
            ->join('calls', function ($join) {
                $join->on('calls.phone', '=', 'pds_customers.phone')
                    ->whereColumn('calls.company_id', 'pds_customers.company_id');
            })
            ->where('pds_customers.company_id', $companyId)
            ->whereIn('pds_customers.pds_id', $pdsIds)
            ->select([
                'pds_customers.pds_id',
                DB::raw("SUM(CASE WHEN calls.call_status = 1 THEN 1 ELSE 0 END) as answered_call"),
                DB::raw("SUM(CASE WHEN calls.call_status = 2 THEN 1 ELSE 0 END) as busy_call"),
                DB::raw("SUM(CASE WHEN calls.call_status = 3 THEN 1 ELSE 0 END) as noanswer_call"),
                DB::raw("SUM(CASE WHEN calls.call_status = 4 THEN 1 ELSE 0 END) as abandoned_call"),
                DB::raw("COUNT(DISTINCT CASE WHEN calls.call_status = 1 THEN pds_customers.ticket_id END) as answered_customer"),
                DB::raw("COUNT(DISTINCT CASE WHEN calls.call_status = 2 THEN pds_customers.ticket_id END) as busy_customer"),
                DB::raw("COUNT(DISTINCT CASE WHEN calls.call_status = 3 THEN pds_customers.ticket_id END) as noanswer_customer"),
                DB::raw("COUNT(DISTINCT CASE WHEN calls.call_status = 4 THEN pds_customers.ticket_id END) as abandoned_customer"),
            ])
            ->groupBy('pds_customers.pds_id')
            ->get();

        $map = [];

        foreach ($rows as $row) {
            $map[$row->pds_id] = [
                'answered' => [
                    'call' => (int) $row->answered_call,
                    'customer' => (int) $row->answered_customer,
                ],
                'busy' => [
                    'call' => (int) $row->busy_call,
                    'customer' => (int) $row->busy_customer,
                ],
                'noanswer' => [
                    'call' => (int) $row->noanswer_call,
                    'customer' => (int) $row->noanswer_customer,
                ],
                'abandoned' => [
                    'call' => (int) $row->abandoned_call,
                    'customer' => (int) $row->abandoned_customer,
                ],
            ];
        }

        return $map;
    }
}

