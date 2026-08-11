<?php

namespace App\Http\Controllers\Rentals;

use App\Http\Controllers\Controller;
use App\Models\RentalContract;
use App\Services\Rentals\RentalContractDocumentService;
use App\Services\Rentals\RentalContractEventService;
use Illuminate\Http\Response;

final class RentalContractPdfController extends Controller
{
    public function __invoke(
        RentalContract $contract,
        RentalContractDocumentService $documents,
        RentalContractEventService $events,
    ): Response {
        $events->record(
            contract: $contract,
            type: request()->boolean('print') ? 'printed' : 'pdf_viewed',
            channel: 'web',
        );

        return response($documents->bytes($contract), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $documents->filename($contract) . '"',
        ]);
    }
}
