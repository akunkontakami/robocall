<?php

namespace App\Jobs;

use App\Actions\Pds\SetupPdsAction;
use App\Helpers\Dialer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RuntimeException;

class StopPdsJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300;
    public bool $failOnTimeout = true;
    public int $uniqueFor = 1800;

    public function __construct(
        public string $pdsId,
        public string $companyId,
    ) {
    }

    public function backoff(): array
    {
        return [30, 120, 300];
    }

    public function uniqueId(): string
    {
        return $this->companyId.':'.$this->pdsId;
    }

    public function handle(SetupPdsAction $action): void
    {
        $dialerPayload = $action->stopPds(
            $this->pdsId,
            $this->companyId,
            true,
        );

        if (!$dialerPayload) {
            return;
        }

        $dialer = Dialer::post('/pds-stop', $dialerPayload, true);

        if (!empty($dialer['errors']) && is_string($dialer['errors'])) {
            throw new RuntimeException($dialer['errors']);
        }
    }
}
