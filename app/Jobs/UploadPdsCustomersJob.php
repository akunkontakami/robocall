<?php

namespace App\Jobs;

use App\Helpers\Dialer;
use App\Models\Pds\PdsCustomer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RuntimeException;

class UploadPdsCustomersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300;
    public bool $failOnTimeout = true;
    public function __construct(
        public string $pdsId,
        public string $tenantId,
        public string $campaignId,
    ) {
    }

    public function backoff(): array
    {
        return [30, 120, 300];
    }

    public function handle(): void
    {
        $customers = PdsCustomer::query()
            ->where('pds_id', $this->pdsId)
            ->get(['ticket_id', 'phone'])
            ->map(fn (PdsCustomer $customer) => [
                'customer_id' => $customer->ticket_id,
                'phone' => $customer->phone,
            ])
            ->values()
            ->toArray();

        $dialer = Dialer::post('/campaign-dialer/uploadJsonPDS', [
            'tenant_id' => $this->tenantId,
            'campaign_id' => $this->campaignId,
            'data' => $customers,
        ], true);


        if (!empty($dialer['errors']) && is_string($dialer['errors'])) {
            throw new RuntimeException($dialer['errors']);
        }
    }
}
