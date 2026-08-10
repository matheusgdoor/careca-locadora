<?php

namespace App\Filament\Resources\Assets\Schemas;

use App\Services\Fleet\AssetPhotoStorage;

use App\Models\Asset;
use App\Services\Fleet\AssetClassificationService;
use App\Services\Fleet\VehicleLookupService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Throwable;

class AssetForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('1. Identificação do ativo')
                    ->description('Categoria, prefixo e informações principais.')
                    ->columnSpanFull()
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                        'xl' => 4,
                    ])
                    ->schema([
                        TextInput::make('prefix')
                            ->label('Prefixo')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Gerado automaticamente'),

                        Select::make('category_id')
                            ->label('Categoria')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->relationship(
                                name: 'category',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn (Builder $query): Builder =>
                                    $query->where('status', 'active')
                            ),

                        Select::make('branch_id')
                            ->label('Filial responsável')
                            ->relationship(
                                name: 'branch',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn (Builder $query): Builder =>
                                    $query
                                        ->where('status', 'active')
                                        ->orderBy('trade_name')
                            )
                            ->searchable()
                            ->preload()
                            ->required()
                            ->native(false)
                            ->helperText(
                                'Define em qual loja o veículo ficará disponível para reservas.'
                            ),

TextInput::make('name')
                            ->label('Descrição do ativo')
                            ->required()
                            ->maxLength(180)
                            ->columnSpan([
                                'default' => 1,
                                'md' => 2,
                            ]),

                        Select::make('status')
                            ->label('Status cadastral')
                            ->required()
                            ->default('active')
                            ->options([
                                'active' => 'Ativo',
                                'inactive' => 'Inativo',
                                'sold' => 'Vendido',
                            ]),

                        Select::make('operational_status')
                            ->label('Situação operacional')
                            ->required()
                            ->default('available')
                            ->options([
                                'available' => 'Disponível',
                                'in_use' => 'Em uso',
                                'maintenance' => 'Em manutenção',
                                'blocked' => 'Bloqueado',
                            ]),

                        Select::make('rental_status')
                            ->label('Situação da locação')
                            ->required()
                            ->default('available')
                            ->options([
                                'available' => 'Disponível',
                                'reserved' => 'Reservado',
                                'rented' => 'Locado',
                                'blocked' => 'Bloqueado',
                            ]),
                    ]),

                Section::make('2. Cadastro inteligente pela placa')
                    ->description('Para categorias emplacadas, informe a placa e consulte o serviço externo.')
                    ->columnSpanFull()
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                        'xl' => 4,
                    ])
                    ->schema([
                        TextInput::make('plate')
                            ->label('Placa')
                            ->maxLength(7)
                            ->formatStateUsing(
                                fn (?string $state): ?string =>
                                    filled($state)
                                        ? strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $state) ?? '')
                                        : null
                            )
                            ->dehydrateStateUsing(
                                fn (?string $state): ?string =>
                                    filled($state)
                                        ? strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $state) ?? '')
                                        : null
                            )
                            ->unique(
                                table: Asset::class,
                                column: 'plate',
                                ignoreRecord: true,
                                modifyRuleUsing: fn ($rule) =>
                                    $rule->where(
                                        'organization_id',
                                        auth()->user()?->organization_id
                                    )
                            )
                            ->suffixAction(
                                Action::make('lookupPlate')
                                    ->label('Consultar')
                                    ->icon('heroicon-o-magnifying-glass')
                                    ->tooltip('Consultar placa')
                                    ->action(function (?string $state, callable $set, $record): void {
                                        $organizationId = auth()->user()?->organization_id;

                                        if (blank($organizationId)) {
                                            Notification::make()
                                                ->danger()
                                                ->title('Organização não identificada')
                                                ->send();

                                            return;
                                        }

                                        try {
                                            $result = app(VehicleLookupService::class)
                                                ->lookup(
                                                    organizationId: $organizationId,
                                                    plate: (string) $state,
                                                    assetId: $record?->id,
                                                );

                                            foreach ($result->toAssetData() as $field => $value) {
                                                if ($field === 'external_data' && is_array($value)) {
                                                    $set(
                                                        $field,
                                                        json_encode(
                                                            $value,
                                                            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                                                        )
                                                    );

                                                    continue;
                                                }

                                                $set($field, $value);
                                            }

                                            $classification = app(AssetClassificationService::class)
                                                ->classify(
                                                    organizationId: $organizationId,
                                                    assetData: $result->toAssetData(),
                                                    assetId: $record?->id,
                                                );

                                            $classificationMessage = null;

                                            if ($classification->matched()) {
                                                if (
                                                    blank($record?->category_id)
                                                    && $classification->canApplyAutomatically()
                                                ) {
                                                    $set('category_id', $classification->categoryId);
                                                    $set('meter_type', $classification->meterType);

                                                    $classificationMessage =
                                                        ' Categoria aplicada automaticamente: '
                                                        . $classification->categoryName
                                                        . ' (' . $classification->confidence . '%).';
                                                } else {
                                                    $classificationMessage =
                                                        ' Categoria sugerida: '
                                                        . $classification->categoryName
                                                        . ' (' . $classification->confidence . '%).';
                                                }
                                            }

                                            if (blank($record?->name)) {
                                                $set('name', $result->suggestedName());
                                            }

                                            Notification::make()
                                                ->success()
                                                ->title('Veículo localizado')
                                                ->body(
                                                    'Os dados disponíveis foram preenchidos. Revise antes de salvar.'
                                                    . ($classificationMessage ?? '')
                                                )
                                                ->send();
                                        } catch (Throwable $exception) {
                                            Notification::make()
                                                ->danger()
                                                ->title('Não foi possível consultar a placa')
                                                ->body($exception->getMessage())
                                                ->send();
                                        }
                                    })
                            )
                            ->helperText('Padrão antigo ou Mercosul, sem necessidade de hífen.'),

                        TextInput::make('renavam')
                            ->label('RENAVAM')
                            ->maxLength(20),

                        TextInput::make('chassis')
                            ->label('Chassi')
                            ->maxLength(40),

                        TextInput::make('brand')
                            ->label('Marca')
                            ->maxLength(100),

                        TextInput::make('model')
                            ->label('Modelo')
                            ->maxLength(150),

                        TextInput::make('version')
                            ->label('Versão')
                            ->maxLength(150)
                            ->columnSpan([
                                'default' => 1,
                                'md' => 2,
                            ]),

                        TextInput::make('manufacture_year')
                            ->label('Ano de fabricação')
                            ->numeric()
                            ->minValue(1900)
                            ->maxValue((int) date('Y') + 1),

                        TextInput::make('model_year')
                            ->label('Ano do modelo')
                            ->numeric()
                            ->minValue(1900)
                            ->maxValue((int) date('Y') + 2),

                        TextInput::make('color')
                            ->label('Cor')
                            ->maxLength(50),

                        TextInput::make('fuel_type')
                            ->label('Combustível')
                            ->maxLength(40),

                        TextInput::make('transmission')
                            ->label('Câmbio')
                            ->maxLength(40),

                        TextInput::make('seats')
                            ->label('Lugares')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(100),

                        TextInput::make('metadata.doors')
                            ->label('Quantidade de portas')
                            ->numeric()
                            ->minValue(2)
                            ->maxValue(6)
                            ->placeholder('Ex.: 2'),

                        TextInput::make('metadata.luggage_capacity')
                            ->label('Capacidade de malas')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(20)
                            ->placeholder('Ex.: 2')
                            ->helperText('Quantidade aproximada de malas padrão.'),

                        Toggle::make('metadata.air_conditioning')
                            ->label('Ar-condicionado')
                            ->default(false)
                            ->inline(false),

                        Toggle::make('metadata.power_steering')
                            ->label('Direção assistida')
                            ->default(true)
                            ->inline(false),

                        TextInput::make('old_plate')
                            ->label('Placa anterior')
                            ->maxLength(10),

                        TextInput::make('engine_description')
                            ->label('Motor')
                            ->maxLength(100),

                        TextInput::make('engine_displacement_cc')
                            ->label('Cilindrada')
                            ->numeric()
                            ->suffix('cc'),

                        TextInput::make('engine_power_hp')
                            ->label('Potência')
                            ->numeric()
                            ->suffix('cv'),

                        TextInput::make('axles')
                            ->label('Eixos')
                            ->numeric(),

                        TextInput::make('gross_weight_t')
                            ->label('PBT')
                            ->numeric()
                            ->suffix('t'),

                        TextInput::make('maximum_traction_capacity_t')
                            ->label('CMT')
                            ->numeric()
                            ->suffix('t'),

                        TextInput::make('species')
                            ->label('Espécie')
                            ->maxLength(80),

                        TextInput::make('origin')
                            ->label('Procedência')
                            ->maxLength(80),

                        TextInput::make('segment')
                            ->label('Segmento')
                            ->maxLength(80),

                        TextInput::make('subsegment')
                            ->label('Subsegmento')
                            ->maxLength(100),

                        TextInput::make('registration_city')
                            ->label('Município de registro')
                            ->maxLength(120)
                            ->columnSpan([
                                'default' => 1,
                                'md' => 2,
                            ]),

                        TextInput::make('registration_state')
                            ->label('UF')
                            ->maxLength(2),

                        TextInput::make('external_situation')
                            ->label('Situação externa')
                            ->maxLength(120),
                    ]),

                Section::make('3. Dados FIPE')
                    ->description('Referência de mercado retornada pela consulta da placa.')
                    ->columnSpanFull()
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                        'xl' => 4,
                    ])
                    ->schema([
                        TextInput::make('fipe_code')
                            ->label('Código FIPE')
                            ->maxLength(20),

                        TextInput::make('fipe_description')
                            ->label('Descrição FIPE')
                            ->maxLength(220)
                            ->columnSpan([
                                'default' => 1,
                                'md' => 2,
                            ]),

                        TextInput::make('fipe_value')
                            ->label('Valor FIPE')
                            ->numeric()
                            ->prefix('R$'),

                        TextInput::make('fipe_reference_month')
                            ->label('Mês de referência')
                            ->maxLength(80),

                        TextInput::make('fipe_score')
                            ->label('Score de correspondência')
                            ->numeric()
                            ->suffix('%'),
                    ]),

                Section::make('4. Medidores e propriedade')
                    ->columnSpanFull()
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                        'xl' => 4,
                    ])
                    ->schema([
                        Select::make('meter_type')
                            ->label('Tipo de medidor')
                            ->required()
                            ->default('odometer')
                            ->options([
                                'odometer' => 'Hodômetro',
                                'hourmeter' => 'Horímetro',
                                'both' => 'Hodômetro e horímetro',
                            ]),

                        TextInput::make('current_odometer')
                            ->label('Hodômetro atual')
                            ->numeric()
                            ->suffix('km')
                            ->default(0),

                        TextInput::make('current_hourmeter')
                            ->label('Horímetro atual')
                            ->numeric()
                            ->suffix('h')
                            ->default(0),

                        Select::make('ownership_type')
                            ->label('Propriedade')
                            ->required()
                            ->default('owned')
                            ->options([
                                'owned' => 'Próprio',
                                'leased' => 'Arrendado',
                                'rented' => 'Locado de terceiro',
                                'consigned' => 'Consignado',
                            ]),

                        DatePicker::make('acquisition_date')
                            ->label('Data de aquisição')
                            ->native(false)
                            ->displayFormat('d/m/Y'),

                        TextInput::make('acquisition_value')
                            ->label('Valor de aquisição')
                            ->numeric()
                            ->prefix('R$'),
                    ]),

                Section::make('5. Documentos')
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('documents')
                            ->relationship()
                            ->label('')
                            ->collapsible()
                            ->columns([
                                'default' => 1,
                                'md' => 2,
                                'xl' => 4,
                            ])
                            ->schema([
                                Select::make('type')
                                    ->label('Documento')
                                    ->required()
                                    ->options([
                                        'crlv' => 'CRLV',
                                        'insurance' => 'Seguro',
                                        'ipva' => 'IPVA',
                                        'licensing' => 'Licenciamento',
                                        'antt' => 'ANTT',
                                        'tachograph' => 'Cronotacógrafo',
                                        'civ' => 'CIV',
                                        'cipp' => 'CIPP',
                                        'other' => 'Outro',
                                    ]),

                                TextInput::make('number')
                                    ->label('Número')
                                    ->maxLength(100),

                                DatePicker::make('issued_at')
                                    ->label('Emissão')
                                    ->native(false)
                                    ->displayFormat('d/m/Y'),

                                DatePicker::make('expires_at')
                                    ->label('Vencimento')
                                    ->native(false)
                                    ->displayFormat('d/m/Y'),

                                FileUpload::make('file_path')
                                    ->label('Arquivo')
                                    ->disk('public')
                                    ->directory('fleet/documents')
                                    ->visibility('public')
                                    ->maxSize(10240)
                                    ->columnSpan([
                                        'default' => 1,
                                        'md' => 2,
                                    ]),

                                Textarea::make('notes')
                                    ->label('Observações')
                                    ->rows(2)
                                    ->columnSpan([
                                        'default' => 1,
                                        'md' => 2,
                                    ]),
                            ])
                            ->columnSpanFull(),
                    ]),

                Section::make('6. Fotos')
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('photos')
                            ->relationship()
                            ->label('')
                            ->addActionLabel('Adicionar foto')
                            ->collapsible()
                            ->columns([
                                'default' => 1,
                                'md' => 2,
                                'xl' => 4,
                            ])
                            ->schema([
                                Select::make('type')
                                    ->label('Tipo')
                                    ->required()
                                    ->default('general')
                                    ->options([
                                        'front' => 'Frente',
                                        'rear' => 'Traseira',
                                        'left' => 'Lateral esquerda',
                                        'right' => 'Lateral direita',
                                        'interior' => 'Interior',
                                        'dashboard' => 'Painel',
                                        'engine' => 'Motor',
                                        'tires' => 'Pneus',
                                        'damage' => 'Avaria',
                                        'general' => 'Geral',
                                    ]),

                                FileUpload::make('file_path')
                                    ->label('Foto')
                                    ->required()
                                    ->image()
                                    ->imageEditor()
                                    ->disk(AssetPhotoStorage::disk())
                                    ->directory('fleet/photos')
                                    ->maxSize(5120)
                                    ->columnSpan([
                                        'default' => 1,
                                        'md' => 2,
                                    ]),

                                TextInput::make('caption')
                                    ->label('Descrição')
                                    ->maxLength(200),

                                Toggle::make('is_featured')
                                    ->label('Foto principal'),
                            ])
                            ->columnSpanFull(),
                    ]),

                Section::make('7. Observações e auditoria externa')
                    ->columnSpanFull()
                    ->schema([
                        Textarea::make('notes')
                            ->label('Observações')
                            ->rows(4)
                            ->columnSpanFull(),

                        Hidden::make('external_data')
                            ->dehydrateStateUsing(function (mixed $state): ?array {
                                if (is_array($state)) {
                                    return $state;
                                }

                                if (! is_string($state) || trim($state) === '') {
                                    return null;
                                }

                                $decoded = json_decode($state, true);

                                return is_array($decoded) ? $decoded : null;
                            }),

                        Hidden::make('external_data_synced_at'),
                    ]),
            ]);
    }
}
