<?php

namespace App\Filament\Resources\Organizations\Schemas;


use App\Services\Organization\OrganizationBrandStorage;
use App\Support\UI\BrazilInputMask;
use App\Services\ExternalData\CepLookupService;
use App\Services\ExternalData\CnpjLookupService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;
use Throwable;

class OrganizationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('1. Identificação')
                    ->description('Dados jurídicos e cadastrais da organização.')
                    ->columnSpanFull()
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                        'xl' => 4,
                    ])
                    ->schema([
                        TextInput::make('name')
                            ->label('Nome')
                            ->required()
                            ->maxLength(150)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (?string $state, callable $set, ?string $operation): void {
                                if ($operation === 'create') {
                                    $set('slug', Str::slug((string) $state));
                                }
                            })
                            ->columnSpan(['default' => 1, 'xl' => 2]),

                        TextInput::make('slug')
                            ->label('Identificador')
                            ->required()
                            ->maxLength(150)
                            ->unique(ignoreRecord: true)
                            ->helperText('Usado internamente nas URLs e integrações.')
                            ->columnSpan(['default' => 1, 'xl' => 2]),

                        TextInput::make('legal_name')
                            ->label('Razão social')
                            ->maxLength(200)
                            ->columnSpan(['default' => 1, 'xl' => 2]),

                        TextInput::make('trade_name')
                            ->label('Nome fantasia')
                            ->maxLength(200)
                            ->columnSpan(['default' => 1, 'xl' => 2]),

                        Select::make('person_type')
                            ->label('Tipo de pessoa')
                            ->required()
                            ->default('legal')
                            ->live()
                            ->options([
                                'legal' => 'Pessoa jurídica',
                                'individual' => 'Pessoa física',
                            ]),

                        TextInput::make('document')
                            ->label('CPF/CNPJ')
                            ->mask(BrazilInputMask::cpfCnpj())
                            ->stripCharacters(BrazilInputMask::documentStripCharacters())
                            ->maxLength(20)
                            ->placeholder('00.000.000/0000-00')
                            ->dehydrateStateUsing(
                                fn (?string $state): ?string => preg_replace('/\D+/', '', (string) $state) ?: null
                            )
                            ->unique(ignoreRecord: true)
                            ->suffixAction(
                                Action::make('lookupCnpj')
                                    ->label('Consultar CNPJ')
                                    ->icon(Heroicon::MagnifyingGlass)
                                    ->tooltip('Consultar dados públicos do CNPJ')
                                    ->visible(fn (callable $get): bool => $get('person_type') === 'legal')
                                    ->action(function (?string $state, callable $set): void {
                                        try {
                                            $data = app(CnpjLookupService::class)->lookup((string) $state);

                                            foreach ($data as $field => $value) {
                                                if ($value !== null) {
                                                    $set($field, $value);
                                                }
                                            }

                                            Notification::make()
                                                ->success()
                                                ->title('CNPJ consultado')
                                                ->body('Os dados cadastrais foram preenchidos. Revise antes de salvar.')
                                                ->send();
                                        } catch (Throwable $exception) {
                                            Notification::make()
                                                ->danger()
                                                ->title('Não foi possível consultar o CNPJ')
                                                ->body($exception->getMessage())
                                                ->send();
                                        }
                                    })
                            )
                            ->helperText('Digite o CNPJ e clique na lupa para preencher os dados automaticamente.'),

                        TextInput::make('state_registration')
                            ->label('Inscrição estadual')
                            ->maxLength(30),

                        TextInput::make('municipal_registration')
                            ->label('Inscrição municipal')
                            ->maxLength(30),

                        TextInput::make('registration_status')
                            ->label('Situação cadastral')
                            ->maxLength(50),

                        TextInput::make('cnae')
                            ->label('CNAE principal')
                            ->maxLength(20),

                        DatePicker::make('opened_at')
                            ->label('Data de abertura')
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->closeOnDateSelection(),

                        Select::make('status')
                            ->label('Status no sistema')
                            ->required()
                            ->default('active')
                            ->options([
                                'active' => 'Ativa',
                                'trial' => 'Em avaliação',
                                'suspended' => 'Suspensa',
                                'inactive' => 'Inativa',
                            ]),
                    ]),

                Section::make('2. Contato, endereço e regionalização')
                    ->description('Canais de atendimento, localização e padrões regionais.')
                    ->columnSpanFull()
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                        'xl' => 6,
                    ])
                    ->schema([
                        TextInput::make('email')
                            ->label('E-mail')
                            ->email()
                            ->maxLength(150)
                            ->columnSpan(['default' => 1, 'xl' => 2]),

                        TextInput::make('phone')
                            ->label('Telefone')
                            ->mask(BrazilInputMask::phone())
                            ->stripCharacters(BrazilInputMask::phoneStripCharacters())
                            ->tel()
                            ->maxLength(20)
                            ->columnSpan(['default' => 1, 'xl' => 2]),

                        TextInput::make('whatsapp')
                            ->label('WhatsApp')
                            ->mask(BrazilInputMask::phone())
                            ->stripCharacters(BrazilInputMask::phoneStripCharacters())
                            ->tel()
                            ->maxLength(20)
                            ->columnSpan(['default' => 1, 'xl' => 2]),

                        TextInput::make('postal_code')
                            ->label('CEP')
                            ->mask(BrazilInputMask::postalCode())
                            ->stripCharacters(BrazilInputMask::postalCodeStripCharacters())
                            ->maxLength(10)
                            ->placeholder('00.000-000')
                            ->dehydrateStateUsing(
                                fn (?string $state): ?string => preg_replace('/\D+/', '', (string) $state) ?: null
                            )
                            ->suffixAction(
                                Action::make('lookupCep')
                                    ->label('Consultar CEP')
                                    ->icon(Heroicon::MagnifyingGlass)
                                    ->tooltip('Consultar endereço pelo CEP')
                                    ->action(function (?string $state, callable $set): void {
                                        try {
                                            $data = app(CepLookupService::class)->lookup((string) $state);

                                            foreach ($data as $field => $value) {
                                                if ($value !== null) {
                                                    $set($field, $value);
                                                }
                                            }

                                            Notification::make()
                                                ->success()
                                                ->title('CEP consultado')
                                                ->body('O endereço foi preenchido. Informe o número e revise os dados.')
                                                ->send();
                                        } catch (Throwable $exception) {
                                            Notification::make()
                                                ->danger()
                                                ->title('Não foi possível consultar o CEP')
                                                ->body($exception->getMessage())
                                                ->send();
                                        }
                                    })
                            ),

                        TextInput::make('address')
                            ->label('Logradouro')
                            ->maxLength(200)
                            ->columnSpan(['default' => 1, 'md' => 2, 'xl' => 3]),

                        TextInput::make('address_number')
                            ->label('Número')
                            ->maxLength(20),

                        TextInput::make('address_complement')
                            ->label('Complemento')
                            ->maxLength(100)
                            ->columnSpan(['default' => 1, 'xl' => 2]),

                        TextInput::make('district')
                            ->label('Bairro')
                            ->maxLength(100)
                            ->columnSpan(['default' => 1, 'xl' => 2]),

                        TextInput::make('city')
                            ->label('Cidade')
                            ->maxLength(100)
                            ->columnSpan(['default' => 1, 'xl' => 2]),

                        Select::make('state')
                            ->label('UF')
                            ->searchable()
                            ->options(self::states()),

                        Select::make('timezone')
                            ->label('Fuso horário')
                            ->required()
                            ->default('America/Cuiaba')
                            ->options([
                                'America/Cuiaba' => '(UTC-04:00) Cuiabá',
                                'America/Manaus' => '(UTC-04:00) Manaus',
                                'America/Sao_Paulo' => '(UTC-03:00) Brasília/São Paulo',
                                'America/Rio_Branco' => '(UTC-05:00) Rio Branco',
                            ])
                            ->columnSpan(['default' => 1, 'xl' => 2]),

                        Select::make('locale')
                            ->label('Idioma')
                            ->required()
                            ->default('pt_BR')
                            ->options([
                                'pt_BR' => 'Português (Brasil)',
                            ])
                            ->columnSpan(['default' => 1, 'xl' => 2]),

                        Select::make('currency')
                            ->label('Moeda')
                            ->required()
                            ->default('BRL')
                            ->options([
                                'BRL' => 'Real brasileiro (R$)',
                            ])
                            ->columnSpan(['default' => 1, 'xl' => 2]),
                    ]),

                Section::make('3. Identidade visual e acesso')
                    ->description('Arquivos da marca e personalização visual da organização.')
                    ->columnSpanFull()
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                        'xl' => 6,
                    ])
                    ->schema([
                        FileUpload::make('logo_path')
                            ->label('Logomarca')
                            ->image()
                            ->imageEditor()
                            ->directory('organizations/logos')
                            ->disk(fn (): string => OrganizationBrandStorage::disk())
                            ->visibility('public')
                            ->maxSize(2048)
                            ->preventFilePathTampering()
                            ->columnSpan(['default' => 1, 'xl' => 2]),

                        FileUpload::make('favicon_path')
                            ->label('Ícone (favicon)')
                            ->image()
                            ->directory('organizations/favicons')
                            ->disk(fn (): string => OrganizationBrandStorage::disk())
                            ->visibility('public')
                            ->maxSize(1024)
                            ->preventFilePathTampering()
                            ->columnSpan(['default' => 1, 'xl' => 2]),

                        TextInput::make('primary_color')
                            ->label('Cor primária')
                            ->placeholder('#F59E0B'),

                        TextInput::make('secondary_color')
                            ->label('Cor secundária')
                            ->placeholder('#1F2937'),

                        TextInput::make('domain')
                            ->label('Domínio')
                            ->placeholder('carecalocadora.com.br')
                            ->maxLength(180)
                            ->columnSpan(['default' => 1, 'xl' => 3]),
                    ]),

                Section::make('4. Informações adicionais')
                    ->description('Classificação comercial e observações internas.')
                    ->columnSpanFull()
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                        'xl' => 6,
                    ])
                    ->schema([
                        Select::make('company_size')
                            ->label('Porte da empresa')
                            ->options([
                                'mei' => 'MEI',
                                'micro' => 'Microempresa',
                                'small' => 'Empresa de pequeno porte',
                                'medium' => 'Médio porte',
                                'large' => 'Grande porte',
                            ])
                            ->columnSpan(['default' => 1, 'xl' => 2]),

                        TextInput::make('business_segment')
                            ->label('Segmento')
                            ->maxLength(120)
                            ->columnSpan(['default' => 1, 'xl' => 2]),

                        TagsInput::make('tags')
                            ->label('Tags')
                            ->placeholder('Adicionar classificação')
                            ->columnSpan(['default' => 1, 'xl' => 2]),

                        Textarea::make('notes')
                            ->label('Observações')
                            ->rows(4)
                            ->maxLength(2000)
                            ->columnSpanFull(),

                        Hidden::make('external_data'),
                        Hidden::make('external_data_synced_at'),
                    ]),
            ]);
    }

    /**
     * @return array<string, string>
     */
    private static function states(): array
    {
        return [
            'AC' => 'Acre', 'AL' => 'Alagoas', 'AP' => 'Amapá', 'AM' => 'Amazonas',
            'BA' => 'Bahia', 'CE' => 'Ceará', 'DF' => 'Distrito Federal',
            'ES' => 'Espírito Santo', 'GO' => 'Goiás', 'MA' => 'Maranhão',
            'MT' => 'Mato Grosso', 'MS' => 'Mato Grosso do Sul', 'MG' => 'Minas Gerais',
            'PA' => 'Pará', 'PB' => 'Paraíba', 'PR' => 'Paraná', 'PE' => 'Pernambuco',
            'PI' => 'Piauí', 'RJ' => 'Rio de Janeiro', 'RN' => 'Rio Grande do Norte',
            'RS' => 'Rio Grande do Sul', 'RO' => 'Rondônia', 'RR' => 'Roraima',
            'SC' => 'Santa Catarina', 'SP' => 'São Paulo', 'SE' => 'Sergipe',
            'TO' => 'Tocantins',
        ];
    }
}
