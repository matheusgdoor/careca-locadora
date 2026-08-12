<?php

namespace App\Services\Rentals;

use App\Jobs\Rentals\SendRentalContractEmailJob;
use App\Models\RentalContract;
use App\Models\RentalContractSignatureRequest;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

final class RentalContractCommunicationService
{
    public function __construct(
        private readonly RentalContractDocumentService $documents,
        private readonly RentalContractSignatureService $signatures,
        private readonly RentalContractEventService $events,
    ) {
    }

    public function sendEmail(
        RentalContract $contract,
        ?RentalContractSignatureRequest $signatureRequest = null,
    ): void {
        $contract->loadMissing('customer');

        $email = $contract->customer?->email;

        if (blank($email)) {
            throw new RuntimeException(
                'O cliente não possui e-mail cadastrado.'
            );
        }

        $signatureRequest ??= $this->latestPendingRequest($contract);

        SendRentalContractEmailJob::dispatch(
            $contract->id,
            $signatureRequest?->id,
        );

        $this->events->record(
            contract: $contract,
            type: 'email_queued',
            channel: 'email',
            recipient: $email,
            signatureRequest: $signatureRequest,
        );
    }

    public function sendEmailNow(
        string $contractId,
        ?string $signatureRequestId = null,
    ): void {
        $contract = RentalContract::query()
            ->withoutGlobalScopes()
            ->with('customer')
            ->findOrFail($contractId);

        $email = $contract->customer?->email;

        if (blank($email)) {
            throw new RuntimeException(
                'O cliente não possui e-mail cadastrado.'
            );
        }

        $signatureRequest = null;

        if (filled($signatureRequestId)) {
            $signatureRequest = RentalContractSignatureRequest::query()
                ->where('rental_contract_id', $contract->id)
                ->find($signatureRequestId);
        }

        $signatureRequest ??= $this->latestPendingRequest($contract);

        $link = $signatureRequest
            ? $this->signatures->url($signatureRequest)
            : null;

        $pdf = $this->documents->bytes($contract);
        $filename = $this->documents->filename($contract);

        $html = view('emails.rental-contract', [
            'contract' => $contract,
            'customer' => $contract->customer,
            'signatureUrl' => $link,
        ])->render();

        Mail::html(
            $html,
            function (Message $message) use (
                $email,
                $contract,
                $pdf,
                $filename
            ): void {
                $message
                    ->to($email)
                    ->subject(
                        "Contrato de locação {$contract->number}"
                    )
                    ->attachData(
                        $pdf,
                        $filename,
                        ['mime' => 'application/pdf']
                    );
            }
        );

        $this->events->record(
            contract: $contract,
            type: 'sent',
            channel: 'email',
            recipient: $email,
            signatureRequest: $signatureRequest,
        );
    }

    public function whatsappUrl(
        RentalContract $contract,
        ?RentalContractSignatureRequest $signatureRequest = null,
    ): string {
        $contract->loadMissing('customer');

        $phone = preg_replace(
            '/\D+/',
            '',
            (string) (
                $contract->customer?->whatsapp
                ?: $contract->customer?->phone
            )
        );

        if (blank($phone)) {
            throw new RuntimeException(
                'O cliente não possui WhatsApp/telefone cadastrado.'
            );
        }

        if (strlen($phone) <= 11) {
            $phone = '55' . $phone;
        }

        $signatureRequest ??= $this->latestPendingRequest($contract);

        $link = $signatureRequest
            ? $this->signatures->url($signatureRequest)
            : route('rental-contracts.pdf', $contract);

        $text = "Olá, {$contract->customer?->display_name}. "
            . "Segue o contrato de locação {$contract->number} "
            . "da Careca Locadora. "
            . (
                $signatureRequest
                    ? "Para revisar e assinar eletronicamente, acesse: {$link}"
                    : "Para visualizar o contrato, acesse: {$link}"
            );

        $this->events->record(
            contract: $contract,
            type: 'sent',
            channel: 'whatsapp',
            recipient: $phone,
            signatureRequest: $signatureRequest,
        );

        return 'https://wa.me/' . $phone
            . '?text=' . rawurlencode($text);
    }

    private function latestPendingRequest(
        RentalContract $contract,
    ): ?RentalContractSignatureRequest {
        return $contract->signatureRequests()
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->latest()
            ->first();
    }
}
