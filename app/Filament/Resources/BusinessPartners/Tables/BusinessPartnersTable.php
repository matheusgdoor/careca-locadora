<?php

namespace App\Filament\Resources\BusinessPartners\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BusinessPartnersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Código')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('display_name')
                    ->label('Nome')
                    ->description(function ($record): ?string {
                        $display = trim((string) $record->display_name);
                        $legal = trim((string) $record->legal_name);

                        return filled($legal) && $legal !== $display
                            ? $legal
                            : null;
                    })
                    ->searchable(['legal_name', 'trade_name'])
                    ->sortable()
                    ->limit(48)
                    ->tooltip(fn ($record): string => (string) $record->display_name),

                TextColumn::make('roles')
                    ->label('Papéis')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => collect($state ?? [])
                        ->map(fn (string $role): string => match ($role) {
                            'customer' => 'Cliente',
                            'supplier' => 'Fornecedor',
                            'carrier' => 'Transportador',
                            'service_provider' => 'Prestador',
                            default => $role,
                        })
                        ->implode(', ')),

                TextColumn::make('document')
                    ->label('CPF/CNPJ')
                    ->formatStateUsing(
                        fn (?string $state): string => self::formatDocument($state)
                    )
                    ->searchable(),

                TextColumn::make('email')
                    ->label('E-mail')
                    ->searchable()
                    ->limit(36)
                    ->tooltip(fn (?string $state): ?string => $state)
                    ->toggleable(),

                TextColumn::make('credit_limit')
                    ->label('Limite')
                    ->money('BRL')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'blocked' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => 'Ativo',
                        'blocked' => 'Bloqueado',
                        default => 'Inativo',
                    }),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'active' => 'Ativo',
                        'inactive' => 'Inativo',
                        'blocked' => 'Bloqueado',
                    ]),
            ])
            ->recordActions([
                EditAction::make()->label('Editar'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('Excluir selecionados'),
                ]),
            ])
            ->defaultSort('legal_name');
    }

    private static function formatDocument(?string $document): string
    {
        $digits = preg_replace('/\D+/', '', (string) $document);

        if (strlen($digits) === 14) {
            return preg_replace(
                '/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/',
                '$1.$2.$3/$4-$5',
                $digits
            ) ?? $digits;
        }

        if (strlen($digits) === 11) {
            return preg_replace(
                '/^(\d{3})(\d{3})(\d{3})(\d{2})$/',
                '$1.$2.$3-$4',
                $digits
            ) ?? $digits;
        }

        return $digits ?: '—';
    }
}
