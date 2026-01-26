<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>FICHA CADASTRAL - {{ $record->nome }}</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #334155; margin: 0; }
        .header { background-color: #1e293b; color: white; padding: 20px; display: flex; align-items: center; justify-content: space-between; }
        .logo { font-size: 24px; font-weight: bold; text-transform: uppercase; }
        .title { font-size: 18px; opacity: 0.9; }
        .section-title { background-color: #f1f5f9; color: #1e293b; padding: 8px; font-weight: bold; margin-top: 20px; border-left: 4px solid #2563EB; font-size: 14px; }
        .row { display: table; width: 100%; margin-top: 10px; }
        .col { display: table-cell; padding: 5px; }
        .label { font-size: 10px; color: #64748b; text-transform: uppercase; font-weight: bold; }
        .value { font-size: 13px; font-weight: 500; border-bottom: 1px solid #e2e8f0; padding-bottom: 2px; }
        .meta { font-size: 11px; color: #9ca3af; }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">STOFGARD</div>
        <div class="title">FICHA CADASTRAL #{{ str_pad($record->id, 6, '0', STR_PAD_LEFT) }}</div>
    </div>

    <div class="section-title">1. DADOS DE IDENTIFICAÇÃO</div>
    <div class="row">
        <div class="col" style="width: 60%">
            <div class="label">Nome / Razão Social</div>
            <div class="value">{{ $record->nome }}</div>
        </div>
        <div class="col">
            <div class="label">Tipo</div>
            <div class="value">{{ strtoupper($record->tipo) }}</div>
        </div>
    </div>

    <div class="section-title">2. DOCUMENTOS & CONTATO</div>
    <div class="row">
        <div class="col" style="width: 35%">
            <div class="label">CPF / CNPJ</div>
            <div class="value">{{ $record->cpf_cnpj ?? '-' }}</div>
        </div>
        <div class="col" style="width: 35%">
            <div class="label">E-mail</div>
            <div class="value">{{ $record->email ?? '-' }}</div>
        </div>
        <div class="col">
            <div class="label">Celular</div>
            <div class="value">{{ $record->celular ?? '-' }}</div>
        </div>
    </div>

    <div class="section-title">3. ENDEREÇO</div>
    <div class="row">
        <div class="col" style="width: 60%">
            <div class="label">Logradouro</div>
            <div class="value">{{ $record->endereco ?? '-' }} @if($record->numero) , {{ $record->numero }} @endif</div>
        </div>
        <div class="col">
            <div class="label">Bairro</div>
            <div class="value">{{ $record->bairro ?? '-' }}</div>
        </div>
    </div>
    <div class="row">
        <div class="col" style="width: 30%">
            <div class="label">Cidade</div>
            <div class="value">{{ $record->cidade ?? '-' }}</div>
        </div>
        <div class="col" style="width: 20%">
            <div class="label">UF</div>
            <div class="value">{{ $record->estado ?? '-' }}</div>
        </div>
        <div class="col" style="width: 20%">
            <div class="label">CEP</div>
            <div class="value">{{ $record->cep ?? '-' }}</div>
        </div>
    </div>

    @if(in_array($record->tipo, ['loja','parceiro','arquiteto','vendedor']))
        <div class="section-title">4. FINANCEIRO</div>
        <div class="row">
            <div class="col" style="width: 30%">
                <div class="label">Comissão</div>
                <div class="value">{{ $record->comissao_percentual ?? 0 }}%</div>
            </div>
            <div class="col">
                <div class="label">Chave Pix</div>
                <div class="value">{{ $record->chave_pix ?? '-' }}</div>
            </div>
        </div>
    @endif

    <div style="margin-top: 40px; text-align: center; color: #94a3b8; font-size: 10px;">
        <div style="border-top: 1px solid #e2e8f0; width: 60%; margin: 0 auto; padding-top: 6px;">Assinatura / Responsável</div>
    </div>
</body>
</html>