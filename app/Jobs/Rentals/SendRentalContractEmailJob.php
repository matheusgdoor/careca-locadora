<?php

namespace App\Jobs\Rentals;

use App\Models\RentalContract;
use App\Services\Rentals\RentalContractCommunicationService;
use App\Services\Rentals\RentalContractEventService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

final class SendRentalContractEmailJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 180;

    /**
     * @var array<int, int>
     */
    public array $backoff = [30, 120, 300];

    public function __construct(
        public readonly string $contractId,
        public readonly ?string $signatureRequestId = null,
    ) {
        $this->onQueue('emails');
    }

    public function handle(
        RentalContractCommunicationService $communications,
    ): void {
        $communications->sendEmailNow(
            contractId: $this->contractId,
            signatureRequestId: $this->signatureRequestId,
        );
    }

    public function failed(Throwable $exception): void
    {
        $contract = RentalContract::query()
            ->withoutGlobalScopes()
            ->find($this->contractId);

        if (! $contract) {
            return;
        }

        app(RentalContractEventService::class)->record(
            contract: $contract,
            type: 'email_failed',
            channel: 'email',
            metadata: [
                'message' => mb_substr($exception->getMessage(), 0, 1000),
            ],
        );
    }
}
