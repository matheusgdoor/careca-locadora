<?php

use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');
Route::inertia('/reservar', 'public/reserve')->name('public.reserve');
Route::inertia('/categorias', 'public/categories')->name('public.categories');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';

Route::middleware([
    'auth',
    \App\Http\Middleware\ResolveTenantContext::class,
])->get(
    '/financeiro/faturas-de-locacao/{invoice}/pdf',
    \App\Http\Controllers\Finance\RentalInvoicePdfController::class
)->name('rental-invoices.pdf');


Route::middleware('auth')->group(function (): void {
    Route::get(
        '/app/rental-deliveries/{delivery}/checklist-pdf',
        \App\Http\Controllers\Rentals\RentalDeliveryChecklistPdfController::class
    )->name('rental-deliveries.checklist-pdf');

    Route::get(
        '/app/rental-returns/{rentalReturn}/checklist-pdf',
        \App\Http\Controllers\Rentals\RentalReturnChecklistPdfController::class
    )->name('rental-returns.checklist-pdf');
});

Route::middleware('auth')->group(function (): void {
    Route::get(
        '/app/purchase-orders/{purchaseOrder}/pdf',
        \App\Http\Controllers\Procurement\PurchaseOrderPdfController::class
    )->name('purchase-orders.pdf');

    Route::get(
        '/app/service-orders/{serviceOrder}/pdf',
        \App\Http\Controllers\Procurement\ServiceOrderPdfController::class
    )->name('service-orders.pdf');
});

Route::get('/vantagens', function () {
    return \Inertia\Inertia::render('public/advantages');
})->name('public.advantages');

Route::get('/filiais', function () {
    $organizationId = config('careca-public.organization_id');

    $branches = \App\Models\Branch::query()
        ->withoutGlobalScopes()
        ->where('organization_id', $organizationId)
        ->orderBy('name')
        ->get()
        ->map(fn (\App\Models\Branch $branch): array => [
            'id' => $branch->id,
            'name' => $branch->name,
            'address' => $branch->address,
            'number' => $branch->number,
            'neighborhood' => $branch->neighborhood,
            'city' => $branch->city,
            'state' => $branch->state,
            'phone' => $branch->phone,
            'whatsapp' => $branch->whatsapp,
        ])
        ->values()
        ->all();

    return \Inertia\Inertia::render('public/branches', [
        'branches' => $branches,
    ]);
})->name('public.branches');

Route::get('/categoria/{category}', function (string $category) {
    return \Inertia\Inertia::render('public/category-vehicles', [
        'categoryId' => $category,
    ]);
})->name('public.categories.vehicles');

Route::get('/veiculos/{asset}', function (string $asset) {
    return \Inertia\Inertia::render('public/vehicle-show', [
        'assetId' => $asset,
    ]);
})->name('public.vehicles.show');

require __DIR__.'/customer.php';

/* Careca Locadora 19.0.0A - Contrato Premium e assinatura eletrônica */
\Illuminate\Support\Facades\Route::middleware('auth')->group(function (): void {
    \Illuminate\Support\Facades\Route::get(
        '/app/rental-contracts/{contract}/pdf',
        \App\Http\Controllers\Rentals\RentalContractPdfController::class
    )->name('rental-contracts.pdf');

    \Illuminate\Support\Facades\Route::get(
        '/app/rental-contracts/{contract}/whatsapp',
        \App\Http\Controllers\Rentals\RentalContractWhatsappController::class
    )->name('rental-contracts.whatsapp');
});

\Illuminate\Support\Facades\Route::middleware('signed')->group(function (): void {
    \Illuminate\Support\Facades\Route::get(
        '/contratos/assinatura/{signatureRequest}',
        [\App\Http\Controllers\Rentals\PublicRentalContractSignatureController::class, 'show']
    )->name('public.contract-signature.show');

    \Illuminate\Support\Facades\Route::post(
        '/contratos/assinatura/{signatureRequest}',
        [\App\Http\Controllers\Rentals\PublicRentalContractSignatureController::class, 'store']
    )->name('public.contract-signature.store');

    \Illuminate\Support\Facades\Route::get(
        '/contratos/assinatura/{signatureRequest}/pdf',
        [\App\Http\Controllers\Rentals\PublicRentalContractSignatureController::class, 'pdf']
    )->name('public.contract-signature.pdf');
});
