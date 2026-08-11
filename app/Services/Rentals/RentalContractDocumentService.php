<?php

namespace App\Services\Rentals;

use App\Models\RentalContract;
use App\Models\RentalContractSignatureRequest;
use Barryvdh\DomPDF\Facade\Pdf;

final class RentalContractDocumentService
{
    public function data(RentalContract $contract, ?RentalContractSignatureRequest $signatureRequest = null): array
    {
        $contract->loadMissing([
            'customer',
            'authorizedContact',
            'responsibleUser',
            'company',
            'branch',
            'items.asset.category',
        ]);

        $signatureRequest ??= $contract->signatureRequests()
            ->where('status', 'signed')
            ->latest('signed_at')
            ->first();

        $logoPath = public_path('images/careca-locadora-logo.png');
        $logo = is_file($logoPath)
            ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath))
            : null;

        return [
            'contract' => $contract,
            'logo' => $logo,
            'signatureRequest' => $signatureRequest,
            'signatureImage' => RentalContractStorage::dataUri($signatureRequest?->signature_path),
        ];
    }

    public function bytes(RentalContract $contract, ?RentalContractSignatureRequest $signatureRequest = null): string
    {
        return Pdf::loadView('pdf.rental-contract', $this->data($contract, $signatureRequest))
            ->setPaper('a4')
            ->output();
    }

    public function hash(RentalContract $contract, ?RentalContractSignatureRequest $signatureRequest = null): string
    {
        return hash('sha256', $this->bytes($contract, $signatureRequest));
    }

    public function filename(RentalContract $contract): string
    {
        return 'contrato-' . preg_replace('/[^A-Za-z0-9_-]+/', '-', $contract->number) . '.pdf';
    }
}
