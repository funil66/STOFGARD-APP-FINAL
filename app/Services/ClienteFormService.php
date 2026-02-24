<?php

namespace App\Services;

use Filament\Forms;
use App\Models\Setting;

class ClienteFormService
{
    public static function getDadosPrincipaisSchema(): array
    {
        return [
            Forms\Components\Section::make('Dados Principais')
                ->schema([
                    Forms\Components\TextInput::make('nome')
                        ->label('Nome / Razão Social')
                        ->required()
                        ->columnSpan(['default' => 'full', 'sm' => 2]),

                    Forms\Components\TextInput::make('documento')
                        ->label('CPF / CNPJ')
                        ->unique(ignoreRecord: true)
                        ->mask(\Filament\Support\RawJs::make(<<<'JS'
                            $input.length > 14 ? '99.999.999/9999-99' : '999.999.999-99'
                        JS))
                        ->columnSpan(['default' => 'full', 'sm' => 1]),

                    Forms\Components\TextInput::make('rg_ie')
                        ->label('RG / Inscrição Estadual')
                        ->columnSpan(['default' => 'full', 'sm' => 1]),
                ])->columns(4),
        ];
    }

    public static function getContatoEnderecoSchema(): array
    {
        return [
            Forms\Components\Section::make('Contato & Endereço')
                ->schema([
                    Forms\Components\TextInput::make('email')
                        ->label('E-mail')
                        ->email()
                        ->columnSpan(['default' => 'full', 'sm' => 2]),

                    Forms\Components\TextInput::make('telefone')
                        ->label('WhatsApp / Telefone')
                        ->mask('(99) 99999-9999')
                        ->required()
                        ->columnSpan(['default' => 'full', 'sm' => 1]),

                    Forms\Components\TextInput::make('cep')
                        ->label('CEP')
                        ->mask('99999-999')
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, Forms\Set $set) {
                            $endereco = EnderecoService::buscarCep($state);
                            if ($endereco) {
                                $set('logradouro', $endereco['logradouro']);
                                $set('bairro', $endereco['bairro']);
                                $set('cidade', $endereco['cidade']);
                                $set('estado', $endereco['estado']);
                            }
                        })
                        ->columnSpan(['default' => 'full', 'sm' => 1]),

                    Forms\Components\TextInput::make('logradouro')
                        ->label('Endereço')
                        ->columnSpan(['default' => 'full', 'sm' => 1]),

                    Forms\Components\TextInput::make('numero')
                        ->label('Número')
                        ->columnSpan(['default' => 'full', 'sm' => 1]),

                    Forms\Components\TextInput::make('bairro')
                        ->label('Bairro')
                        ->columnSpan(['default' => 'full', 'sm' => 1]),

                    Forms\Components\TextInput::make('cidade')
                        ->label('Cidade')
                        ->columnSpan(['default' => 'full', 'sm' => 1]),

                    Forms\Components\TextInput::make('estado')
                        ->label('UF')
                        ->maxLength(2)
                        ->columnSpan(['default' => 'full', 'sm' => 1]),

                    Forms\Components\TextInput::make('complemento')
                        ->label('Complemento')
                        ->columnSpan(['default' => 'full', 'sm' => 1]),
                ])->columns(4),
        ];
    }

    public static function getClassificacaoSchema(): array
    {
        return [
            Forms\Components\Section::make('Classificação & Vínculos')
                ->schema([
                    Forms\Components\Select::make('tipo')
                        ->label('Tipo de Cadastro')
                        ->options(fn() => \App\Models\Categoria::where('tipo', 'cadastro_tipo')
                            ->where('ativo', true)
                            ->pluck('nome', 'slug'))
                        ->required()
                        ->live()
                        ->afterStateUpdated(fn($state, Forms\Set $set) => $state === 'parceiro' ? $set('especialidade', 'Arquiteto') : null)
                        ->columnSpan(['default' => 'full', 'sm' => 1]),

                    Forms\Components\TextInput::make('especialidade')
                        ->label('Ramo de Atividade / Profissão')
                        ->placeholder('Ex: Arquiteto, Advogado, Zelador')
                        ->visible(fn(Forms\Get $get) => in_array($get('tipo'), ['parceiro', 'loja']))
                        ->columnSpan(['default' => 'full', 'sm' => 1]),

                    Forms\Components\Select::make('parent_id')
                        ->label('Loja Vinculada')
                        ->relationship('loja', 'nome', fn(\Illuminate\Database\Eloquent\Builder $query) => $query->where('tipo', 'loja'))
                        ->visible(fn(Forms\Get $get) => $get('tipo') === 'vendedor')
                        ->searchable()
                        ->columnSpan(['default' => 'full', 'sm' => 1]),

                    // CAMPO DE COMISSÃO
                    Forms\Components\TextInput::make('comissao_percentual')
                        ->label('Comissão Padrão (%)')
                        ->numeric()
                        ->suffix('%')
                        ->default(0)
                        ->visible(fn(Forms\Get $get) => in_array($get('tipo'), ['vendedor', 'loja', 'parceiro']))
                        ->helperText('Porcentagem que será aplicada automaticamente nos orçamentos.')
                        ->columnSpan(['default' => 'full', 'sm' => 1]),

                    // CAMPOS EXCLUSIVOS PARA LEAD (Criação de Orçamento Automática)
                    Forms\Components\Select::make('servico_interesse')
                        ->label('Interesse no Serviço')
                        ->options(\App\Services\LeadService::getServicosDisponiveis())
                        ->visible(fn(Forms\Get $get) => $get('tipo') === 'lead')
                        ->searchable()
                        ->preload()
                        ->live()
                        ->columnSpan(['default' => 'full', 'sm' => 1]),

                    Forms\Components\Textarea::make('mensagem_inicial')
                        ->label('Mensagem / Observações Iniciais')
                        ->visible(fn(Forms\Get $get) => $get('tipo') === 'lead')
                        ->columnSpanFull(),
                ])->columns(3),
        ];
    }

    public static function getArquivosSchema(): array
    {
        return [
            Forms\Components\Section::make('Central de Arquivos')
                ->description('Envie fotos, documentos e comprovantes (Máx: 20MB).')
                ->collapsible()
                ->collapsed()
                ->schema([
                    Forms\Components\Toggle::make('pdf_mostrar_documentos')
                        ->label('Exibir Documentos no PDF?')
                        ->helperText('Se marcado, os documentos anexados aparecerão no PDF da ficha cadastral.')
                        ->default(fn() => Setting::get('pdf_mostrar_documentos_global', true))
                        ->columnSpanFull(),

                    Forms\Components\SpatieMediaLibraryFileUpload::make('arquivos')
                        ->label('Anexos (Até 20MB)')
                        ->collection('arquivos')
                        ->multiple()
                        ->disk('public')
                        ->maxSize(20480)
                        ->downloadable()
                        ->openable()
                        ->previewable()
                        ->reorderable()
                        ->columnSpanFull(),
                ]),
        ];
    }

    public static function getQuickSchema(): array
    {
        return [
            Forms\Components\Select::make('tipo')
                ->label('Tipo de Cadastro')
                ->options(fn() => \App\Models\Categoria::where('tipo', 'cadastro_tipo')
                    ->where('ativo', true)
                    ->pluck('nome', 'slug'))
                ->required()
                ->default('cliente')
                ->columnSpan(['default' => 'full', 'sm' => 1]),

            Forms\Components\TextInput::make('nome')
                ->label('Nome / Razão Social')
                ->required()
                ->columnSpan(['default' => 'full', 'sm' => 2]),

            Forms\Components\TextInput::make('documento')
                ->label('CPF / CNPJ')
                ->unique(ignoreRecord: true)
                ->mask(\Filament\Support\RawJs::make(<<<'JS'
                            $input.length > 14 ? '99.999.999/9999-99' : '999.999.999-99'
                        JS))
                ->columnSpan(['default' => 'full', 'sm' => 1]),

            Forms\Components\TextInput::make('telefone')
                ->label('WhatsApp / Telefone')
                ->mask('(99) 99999-9999')
                ->required()
                ->columnSpan(['default' => 'full', 'sm' => 1]),

            Forms\Components\TextInput::make('email')
                ->label('E-mail')
                ->email()
                ->columnSpan(['default' => 'full', 'sm' => 1]),
        ];
    }

    public static function getFullSchema(): array
    {
        return array_merge(
            self::getClassificacaoSchema(),
            self::getDadosPrincipaisSchema(),
            self::getContatoEnderecoSchema(),
            self::getArquivosSchema()
        );
    }
}
