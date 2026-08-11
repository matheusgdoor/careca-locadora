<?php

namespace App\Filament\Resources\Organizations\Tables;

use App\Services\Organization\OrganizationBrandStorage;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OrganizationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('logo_path')
                    ->label('Logo')
                    ->state(fn ($record): ?string => OrganizationBrandStorage::url($record->logo_path))
                    ->defaultImageUrl(
                        fn ($record): ?string =>
                            (string) $record->id === (string) config('careca-public.organization_id')
                                ? asset('images/careca-locadora-logo.png')
                                : null
                    )
                    ->imageWidth(110)
                    ->imageHeight(64)
                    ->extraImgAttributes([
                        'class' => 'object-contain rounded-lg bg-zinc-900/60 p-1',
                        'loading' => 'lazy',
                    ]),

                TextColumn::make('name')
                    ->label('Nome')
                    ->description(fn ($record): ?string => $record->trade_name ?: $record->legal_name)
                    ->searchable(['name', 'trade_name', 'legal_name'])
                    ->sortable()
                    ->weight('medium')
                    ->limit(48),

                TextColumn::make('document')
                    ->label('CPF/CNPJ')
                    ->formatStateUsing(fn (?string $state): string => self::formatDocument($state))
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('city')
                    ->label('Localidade')
                    ->formatStateUsing(
                        fn (?string $state, $record): string =>
                            collect([$state, $record->state])->filter()->implode(' / ') ?: '—'
                    )
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('email')
                    ->label('E-mail')
                    ->searchable()
                    ->toggleable()
                    ->limit(36),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'trial' => 'warning',
                        'suspended' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => 'Ativa',
                        'trial' => 'Em avaliação',
                        'suspended' => 'Suspensa',
                        default => 'Inativa',
                    }),

                TextColumn::make('created_at')
                    ->label('Criada em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'active' => 'Ativa',
                        'trial' => 'Em avaliação',
                        'suspended' => 'Suspensa',
                        'inactive' => 'Inativa',
                    ]),
                SelectFilter::make('state')
                    ->label('UF')
                    ->options([
                        'MT' => 'Mato Grosso',
                        'RO' => 'Rondônia',
                        'PA' => 'Pará',
                        'AM' => 'Amazonas',
                        'GO' => 'Goiás',
                        'MS' => 'Mato Grosso do Sul',
                        'SP' => 'São Paulo',
                        'PR' => 'Paraná',
                    ]),
            ])
            ->recordActions([
                EditAction::make()->label('Editar'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('Excluir selecionadas'),
                ]),
            ])
            ->defaultSort('name');
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
