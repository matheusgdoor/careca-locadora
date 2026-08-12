<?php

namespace App\Filament\Resources\RentalContracts\Schemas;

use App\Support\UI\PremiumFormLayout;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RentalContractForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('1. Identificação do contrato')
                    ->columns(PremiumFormLayout::standard())
                    ->schema([
                        TextInput::make('number')->label('Número')->disabled()->dehydrated(false),

                        Select::make('status')
                            ->label('Status')
                            ->required()
                            ->options([
                                'draft' => 'Rascunho',
                                'awaiting_signature' => 'Aguardando assinatura',
                                'active' => 'Ativo',
                                'closed' => 'Encerrado',
                                'cancelled' => 'Cancelado',
                            ]),

                        Select::make('rental_mode')
                            ->label('Modalidade')
                            ->required()
                            ->options([
                                'daily' => 'Locação diária',
                                'monthly' => 'Locação mensal',
                            ])
                            ->helperText('Define as condições comerciais utilizadas no contrato.'),

                        TextInput::make('contract_version')
                            ->label('Versão')
                            ->numeric()
                            ->minValue(1)
                            ->disabled()
                            ->dehydrated(false),

                        Placeholder::make('customer_display')
                            ->label('Cliente')
                            ->content(fn ($record): string => $record?->customer?->display_name ?? 'Cliente não informado')
                            ->columnSpan(['default' => 1, 'md' => 2]),

                        DateTimePicker::make('starts_at')
                            ->label('Início')
                            ->required()
                            ->seconds(false)
                            ->native(false)
                            ->displayFormat('d/m/Y H:i'),

                        DateTimePicker::make('ends_at')
                            ->label('Término')
                            ->required()
                            ->seconds(false)
                            ->native(false)
                            ->displayFormat('d/m/Y H:i'),

                        DateTimePicker::make('signed_at')
                            ->label('Assinado em')
                            ->seconds(false)
                            ->native(false)
                            ->displayFormat('d/m/Y H:i')
                            ->disabled()
                            ->dehydrated(false),

                        DateTimePicker::make('activated_at')
                            ->label('Ativado em')
                            ->seconds(false)
                            ->native(false)
                            ->displayFormat('d/m/Y H:i'),
                    ]),

                Section::make('2. Condições comerciais')
                    ->description('Campos estruturados para contratos diário e mensal.')
                    ->columns(PremiumFormLayout::standard())
                    ->schema([
                        TextInput::make('billing_day')
                            ->label('Dia de vencimento mensal')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(31)
                            ->helperText('Utilizado na modalidade mensal.'),

                        TextInput::make('included_distance')
                            ->label('Franquia de quilometragem')
                            ->numeric()
                            ->suffix(' km')
                            ->helperText('Deixe vazio para quilometragem livre.'),

                        TextInput::make('extra_distance_value')
                            ->label('Valor do KM excedente')
                            ->numeric()
                            ->prefix('R$')
                            ->minValue(0),

                        Toggle::make('protection_included')
                            ->label('Proteção/seguro incluído'),

                        TextInput::make('protection_deductible')
                            ->label('Franquia de proteção')
                            ->numeric()
                            ->prefix('R$')
                            ->minValue(0),

                        Select::make('fuel_policy')
                            ->label('Regra de combustível')
                            ->required()
                            ->options([
                                'same_level' => 'Devolver no mesmo nível',
                                'full_to_full' => 'Cheio para cheio',
                                'charged_difference' => 'Cobrar diferença',
                            ]),
                    ]),

                Section::make('3. Ativos do contrato')
                    ->schema([
                        Repeater::make('items')
                            ->relationship()
                            ->label('')
                            ->collapsible()
                            ->columns(PremiumFormLayout::repeater())
                            ->schema([
                                Placeholder::make('asset_prefix_display')
                                    ->label('Prefixo')
                                    ->content(fn ($record): string => $record?->asset?->prefix ?? 'Não informado'),

                                Placeholder::make('asset_name_display')
                                    ->label('Ativo')
                                    ->content(fn ($record): string => $record?->asset?->name ?? 'Ativo não informado')
                                    ->columnSpan(['default' => 1, 'md' => 2]),

                                TextInput::make('billing_unit')->label('Unidade')->disabled()->dehydrated(false),
                                TextInput::make('quantity')->label('Quantidade')->disabled()->dehydrated(false),
                                TextInput::make('unit_value')->label('Valor unitário')->prefix('R$')->disabled()->dehydrated(false),
                                TextInput::make('total_value')->label('Total')->prefix('R$')->disabled()->dehydrated(false),
                                TextInput::make('initial_odometer')->label('KM inicial')->numeric()->suffix(' km'),
                                TextInput::make('initial_hourmeter')->label('Horímetro inicial')->numeric()->suffix(' h'),
                            ])
                            ->columnSpanFull(),
                    ]),

                Section::make('4. Valores e condições')
                    ->columns(PremiumFormLayout::standard())
                    ->schema([
                        TextInput::make('subtotal')->label('Subtotal')->prefix('R$')->disabled()->dehydrated(false),
                        TextInput::make('discount_value')->label('Desconto')->prefix('R$')->disabled()->dehydrated(false),
                        TextInput::make('additional_value')->label('Acréscimo')->prefix('R$')->disabled()->dehydrated(false),
                        TextInput::make('deposit_value')->label('Caução')->prefix('R$')->disabled()->dehydrated(false),
                        TextInput::make('total_value')->label('Total')->prefix('R$')->disabled()->dehydrated(false),

                        Textarea::make('terms')
                            ->label('Condições particulares / termos adicionais')
                            ->rows(8)
                            ->columnSpanFull(),

                        Textarea::make('commercial_notes')
                            ->label('Observações comerciais')
                            ->rows(3)
                            ->columnSpanFull(),

                        Textarea::make('operational_notes')
                            ->label('Observações operacionais')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
