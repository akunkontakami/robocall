<?php

namespace App\Console\Commands;

use App\Helpers\Dialer;
use App\Models\Pds\Pds;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CheckPdsRunningStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-pds-running-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tenantIds = Pds::where('is_running', 1)
            ->distinct()
            ->pluck('tenant_id')
            ->toArray();

        $campaignIds = Pds::where('is_running', 1)
            ->distinct()
            ->pluck('pds_name')
            ->toArray();

        $urlPath = '/campaign-dialer/index';
        foreach ($tenantIds as $tenantId) {
            $page = 1;
            $perPage = 10;

            do {
                $query = [
                    'page' => $page,
                    'per_page' => $perPage,
                    'tenant_id' => $tenantId,
                ];

                $result = Dialer::get($urlPath.'?'.http_build_query($query));

                $data = collect($result['data']);

                foreach ($data as $item) {
                    if (Str::lower($item['IsRunning']) == 'stopped' && in_array($item['campaign_id'], $campaignIds)) {
                        Pds::where('pds_name', $item['campaign_id'])->where('tenant_id', $tenantId)->update([
                            'is_running' => 0,
                        ]);
                    }
                }

                $total = $result['total'] ?? $data->count();
                $perPage = $result['per_page'] ?? $perPage;
                $currentPage = $result['current_page'] ?? $page;

                ++$page;
            } while ($currentPage * $perPage < $total);
        }
    }
}
