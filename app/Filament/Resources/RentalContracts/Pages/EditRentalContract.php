<?php

namespace App\Filament\Resources\RentalContracts\Pages;

use App\Filament\Resources\RentalContracts\RentalContractResource;
use App\Filament\Resources\RentalDeliveries\RentalDeliveryResource;
use App\Filament\Resources\RentalReturns\RentalReturnResource;
use App\Services\Rentals\RentalContractCommunicationService;
use App\Services\Rentals\RentalContractSignatureService;
use App\Services\Rentals\RentalDeliveryService;
use App\Services\Rentals\RentalReturnService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Throwable;

class EditRentalContract extends EditRecord
{
    protected static string $resource = RentalContractResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('contractPdf')
                ->label('Visualizar PDF')
                ->icon('heroicon-o-document-text')
                ->color('gray')
                ->url(fn (): string => route('rental-contracts.pdf', $this->record))
                ->openUrlInNewTab(),

            Action::make('printContract')
                ->label('Imprimir contrato')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->url(fn (): string => route('rental-contracts.pdf', [
                    'contract' => $this->record,
                    'print' => 1,
                ]))
                ->openUrlInNewTab(),

            Action::make('requestSignature')
                ->label('Solicitar assinatura')
                ->icon('heroicon-o-pencil-square')
                ->color('warning')
                ->visible(fn (): bool => in_array($this->record->status, ['draft', 'awaiting_signature'], true))
                ->action(function (): void {
                    $signatureRequest = app(RentalContractSignatureService::class)
                        ->createRequest($this->record);

                    $url = app(RentalContractSignatureService::class)
                        ->url($signatureRequest);

                    Notification::make()
                        ->success()
                        ->title('Link de assinatura criado')
                        ->body('O link é válido por 7 dias. Use E-mail ou WhatsApp para enviá-lo ao cliente.')
                        ->actions([
                            Action::make('abrirAssinatura')
                                ->label('Abrir link')
                                ->url($url, shouldOpenInNewTab: true),
                        ])
                        ->send();

                    $this->reloadRecordPage();
                }),

            Action::make('sendContractEmail')
                ->label('Enviar por e-mail')
                ->icon('heroicon-o-envelope')
                ->color('info')
                ->requiresConfirmation()
                ->action(function (): void {
                    try {
                        if (! $this->record->signatureRequests()
                            ->where('status', 'pending')
                            ->where('expires_at', '>', now())
                            ->exists()) {
                            app(RentalContractSignatureService::class)
                                ->createRequest($this->record);
                            $this->record->refresh();
                        }

                        app(RentalContractCommunicationService::class)
                            ->sendEmail($this->record);

                        Notification::make()
                            ->success()
                            ->title('Contrato enviado por e-mail')
                            ->body((string) $this->record->customer?->email)
                            ->send();
                    } catch (Throwable $exception) {
                        Notification::make()
                            ->danger()
                            ->title('Não foi possível enviar o contrato')
                            ->body($exception->getMessage())
                            ->send();
                    }
                }),

            Action::make('sendContractWhatsapp')
                ->label('Enviar por WhatsApp')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->color('success')
                ->url(fn (): string => route('rental-contracts.whatsapp', $this->record))
                ->openUrlInNewTab(),

            Action::make('generateInvoice')
                ->label('Gerar fatura de locação')
                ->icon('heroicon-o-document-currency-dollar')
                ->color('success')
                ->visible(fn (): bool =>
                    $this->record->status === 'closed'
                    && $this->record->rentalInvoice()->doesntExist()
                )
                ->action(function (): void {
                    $invoice = app(\App\Services\Rentals\RentalInvoiceService::class)
                        ->createFromContract($this->record);

                    Notification::make()
                        ->success()
                        ->title('Fatura de locação gerada')
                        ->body("Fatura {$invoice->number}")
                        ->send();

                    $this->redirect(
                        \App\Filament\Resources\RentalInvoices\RentalInvoiceResource::getUrl('edit', [
                            'record' => $invoice,
                        ])
                    );
                }),

            Action::make('openInvoice')
                ->label('Abrir fatura de locação')
                ->icon('heroicon-o-document-currency-dollar')
                ->visible(fn (): bool => $this->record->rentalInvoice()->exists())
                ->action(function (): void {
                    $invoice = $this->record->rentalInvoice()->firstOrFail();

                    $this->redirect(
                        \App\Filament\Resources\RentalInvoices\RentalInvoiceResource::getUrl('edit', [
                            'record' => $invoice,
                        ])
                    );
                }),

            Action::make('awaitingSignature')
                ->label('Marcar aguardando assinatura')
                ->icon('heroicon-o-paper-airplane')
                ->visible(fn (): bool => $this->record->status === 'draft')
                ->action(function (): void {
                    $this->record->update(['status' => 'awaiting_signature']);

                    Notification::make()
                        ->success()
                        ->title('Contrato aguardando assinatura')
                        ->send();

                    $this->reloadRecordPage();
                }),

            Action::make('activate')
                ->label('Ativar contrato')
                ->icon('heroicon-o-play')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (): bool => in_array($this->record->status, ['draft', 'awaiting_signature'], true))
                ->action(function (): void {
                    $now = now();

                    $this->record->update([
                        'status' => 'active',
                        'signed_at' => $this->record->signed_at ?? $now,
                        'activated_at' => $now,
                    ]);

                    Notification::make()
                        ->success()
                        ->title('Contrato ativado')
                        ->send();

                    $this->reloadRecordPage();
                }),

            Action::make('startDelivery')
                ->label('Iniciar entrega')
                ->icon('heroicon-o-truck')
                ->color('warning')
                ->visible(fn (): bool =>
                    $this->record->status === 'active'
                    && $this->record->delivery()->doesntExist()
                )
                ->action(function (): void {
                    $delivery = app(RentalDeliveryService::class)
                        ->createFromContract($this->record);

                    Notification::make()
                        ->success()
                        ->title('Checklist de entrega criado')
                        ->body("Entrega {$delivery->number}")
                        ->send();

                    $this->redirect(
                        RentalDeliveryResource::getUrl('edit', [
                            'record' => $delivery,
                        ])
                    );
                }),

            Action::make('openDelivery')
                ->label('Abrir entrega')
                ->icon('heroicon-o-truck')
                ->visible(fn (): bool => $this->record->delivery()->exists())
                ->action(function (): void {
                    $delivery = $this->record->delivery()->firstOrFail();

                    $this->redirect(
                        RentalDeliveryResource::getUrl('edit', [
                            'record' => $delivery,
                        ])
                    );
                }),

            Action::make('startReturn')
                ->label('Iniciar devolução')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('info')
                ->visible(fn (): bool =>
                    $this->record->status === 'active'
                    && $this->record->delivery?->status === 'completed'
                    && $this->record->rentalReturn()->doesntExist()
                )
                ->action(function (): void {
                    $return = app(RentalReturnService::class)
                        ->createFromContract($this->record);

                    Notification::make()
                        ->success()
                        ->title('Checklist de devolução criado')
                        ->body("Devolução {$return->number}")
                        ->send();

                    $this->redirect(
                        RentalReturnResource::getUrl('edit', [
                            'record' => $return,
                        ])
                    );
                }),

            Action::make('openReturn')
                ->label('Abrir devolução')
                ->icon('heroicon-o-arrow-uturn-left')
                ->visible(fn (): bool => $this->record->rentalReturn()->exists())
                ->action(function (): void {
                    $return = $this->record->rentalReturn()->firstOrFail();

                    $this->redirect(
                        RentalReturnResource::getUrl('edit', [
                            'record' => $return,
                        ])
                    );
                }),
        ];
    }

    private function reloadRecordPage(): void
    {
        $this->record->refresh();

        $this->redirect(
            RentalContractResource::getUrl('edit', [
                'record' => $this->record,
            ])
        );
    }
}
