<!DOCTYPE html>
<html lang='pt-BR'>
<head>
    <meta charset='utf-8'>
    <title>Ficha Cadastral - {{ $record->nome }}</title>
    <style>
        @page { margin: 100px 25px 50px 25px; }
        body { font-family: Helvetica, Arial, sans-serif; font-size: 11px; color: #333; margin: 0; }
        
        /* Cabeçalho Fixo */
        header { position: fixed; top: -80px; left: 0; right: 0; height: 80px; border-bottom: 2px solid #1e3a8a; }
        .logo { float: left; width: 30%; color: #1e3a8a; font-weight: bold; font-size: 24px; padding-top: 20px; }
        .info-empresa { float: right; width: 50%; text-align: right; font-size: 10px; line-height: 1.2; padding-top: 10px; }
        
        /* Rodapé Fixo */
        footer { position: fixed; bottom: -30px; left: 0; right: 0; height: 30px; border-top: 1px solid #ccc; text-align: center; font-size: 9px; color: #777; padding-top: 5px; }
        
        /* Títulos */
        h1 { font-size: 16px; text-transform: uppercase; color: #1e3a8a; margin: 0 0 5px 0; }
        .section-title { background-color: #f3f4f6; color: #111; font-weight: bold; padding: 5px 10px; border-left: 4px solid #1e3a8a; margin-top: 20px; margin-bottom: 10px; text-transform: uppercase; font-size: 12px; }
        
        /* Tabelas */
        table { width: 100%; border-collapse: collapse; }
        th { width: 25%; text-align: left; background-color: #fff; border-bottom: 1px solid #ddd; padding: 6px; color: #555; font-weight: bold; font-size: 10px; vertical-align: top; }
        td { text-align: left; border-bottom: 1px solid #ddd; padding: 6px; color: #000; vertical-align: top; }
        
        /* Badges e Utilitários */
        .badge { padding: 2px 6px; border-radius: 3px; color: #fff; font-size: 9px; font-weight: bold; text-transform: uppercase; display: inline-block; }
        .bg-cliente { background-color: #3b82f6; }
        .bg-parceiro { background-color: #10b981; }
        .bg-loja { background-color: #f59e0b; }
        .text-center { text-align: center; }
    </style>
</head>
<body>
    <header>
        <div class='logo'>STOFGARD</div>
        <div class='info-empresa'>
            <strong>STOFGARD APP SYSTEM</strong><br>
            Ribeirão Preto - SP<br>
            Documento Oficial
        </div>
    </header>
<footer>
    Stofgard App - Impresso por {{ auth()->user()->name ?? 'Sistema' }} em {{ date('d/m/Y H:i') }}
</footer>
    <div class='text-center' style='margin-bottom: 20px;'>
    <h1>Ficha Cadastral</h1>
    <span style='font-size: 10px; color: #666;'>#ID: {{ str_pad($record->id, 6, '0', STR_PAD_LEFT) }}</span>
</div>
<div class='section-title'>1. Dados de Identificação</div>
<table>
    <tr>
        <th>Nome Completo / Razão:</th>
        <td colspan='3' style='font-size: 12px; font-weight: bold;'>{{ $record->nome }}</td>
    </tr>
    <tr>
        <th>Tipo de Perfil:</th>
        <td>
            @php
                $colors = ['cliente' => 'bg-cliente', 'parceiro' => 'bg-parceiro', 'loja' => 'bg-loja'];
                $bg = $colors[$record->tipo] ?? 'bg-cliente';
            @endphp
            <span class='badge {{ $bg }}'>{{ $record->tipo }}</span>
        </td>
        <th>CPF / CNPJ:</th>
        <td>{{ $record->cpf_cnpj ?? 'Não Informado' }}</td>
    </tr>
</table>
<div class='section-title'>2. Localização e Contato</div>
<table>
    <tr>
        <th>Endereço:</th>
        <td colspan='3'>
            {{ $record->endereco }}, {{ $record->numero }}
            @if($record->complemento) - {{ $record->complemento }} @endif
            - {{ $record->bairro }}
        </td>
    </tr>
    <tr>
        <th>Cidade / UF:</th>
        <td>{{ $record->cidade }} / {{ $record->estado }}</td>
        <th>CEP:</th>
        <td>{{ $record->cep }}</td>
    </tr>
    <tr>
        <th>Celular (WhatsApp):</th>
        <td>{{ $record->celular }}</td>
        <th>E-mail:</th>
        <td>{{ $record->email }}</td>
    </tr>
</table>
@if(in_array($record->tipo, ['parceiro', 'loja', 'arquiteto']))
<div class='section-title'>3. Dados Financeiros (Comissão)</div>
<table>
    <tr>
        <th>Comissão Pactuada:</th>
        <td style='color: #1e3a8a; font-weight: bold;'>{{ $record->comissao_percentual ?? 0 }}%</td>
        <th>Chave Pix:</th>
        <td>{{ $record->chave_pix ?? '-' }}</td>
    </tr>
    <tr>
        <th>Dados Bancários:</th>
        <td colspan='3'>{!! nl2br(e($record->dados_bancarios)) !!}</td>
    </tr>
</table>
@endif
<div style='margin-top: 60px; text-align: center; color: #999; font-size: 10px;'>
    <div style='border-top: 1px solid #ccc; width: 60%; margin: 0 auto; padding-top: 5px;'>
        Assinatura / Responsável
    </div>
</div>