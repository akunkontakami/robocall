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

class StartPdsJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300;
    public bool $failOnTimeout = true;
    public int $uniqueFor = 1800;

    public function __construct(
        public string $pdsId,
        public string $companyId,
        public array $settings,
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
        $dialerPayload = $action->preparePdsStart(
            $this->pdsId,
            $this->companyId,
            $this->settings,
        );

        if (!$dialerPayload) {
            return;
        }

        $dialer = Dialer::post('/pds-start', $dialerPayload, true);

        $error = $this->dialerError($dialer);

        if ($error !== null) {
            throw new RuntimeException($error);
        }

        $action->markPdsStarted(
            $this->pdsId,
            $this->companyId,
            $this->settings,
        );
    }

    private function dialerError(?array $response): ?string
    {
        if ($response === null) {
            return 'Dialer returned an empty response';
        }

        foreach (['errors', 'error'] as $key) {
            if (!empty($response[$key])) {
                return is_string($response[$key])
                    ? $response[$key]
                    : json_encode($response[$key]);
            }
        }

        if (($response['success'] ?? null) === false) {
            return is_string($response['message'] ?? null)
                ? $response['message']
                : 'Dialer failed to start PDS';
        }

        $message = $response['message'] ?? null;
        if (is_string($message) && str_contains(strtolower($message), 'failed')) {
            return $message;
        }

        return null;
    }
}
