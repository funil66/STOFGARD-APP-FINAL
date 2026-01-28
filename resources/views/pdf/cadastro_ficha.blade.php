<!DOCTYPE html>
<html lang='pt-BR'>
<head>
    <meta charset='UTF-8'>
    <title>Ficha Cadastral - {{ $cadastro->nome }}</title>
    <style>
        @page { margin: 0; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; margin: 0; padding: 0; color: #334155; font-size: 12px; line-height: 1.5; }
        
        /* CABEÇALHO */
        .header {
            background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);
            color: white; padding: 40px; height: 80px;
        }
        .header-content { display: table; width: 100%; }
        .logo-container { display: table-cell; vertical-align: middle; width: 50%; }
        .logo-img { max-height: 70px; background: white; padding: 5px; border-radius: 4px; }
        .title-container { display: table-cell; vertical-align: middle; text-align: right; width: 50%; }
        .doc-title { font-size: 24px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; }
    /* CONTEÚDO */
    .container { padding: 40px; }
    
    .section-title {
        color: #1e3a8a; font-size: 11px; text-transform: uppercase; font-weight: bold;
        border-bottom: 2px solid #e2e8f0; margin-top: 25px; margin-bottom: 15px; padding-bottom: 5px;
    }
    .info-grid { width: 100%; display: table; margin-bottom: 10px; }
    .info-row { display: table-row; }
    .info-cell { display: table-cell; padding-bottom: 10px; padding-right: 20px; }
    
    .label { display: block; font-size: 9px; color: #64748b; text-transform: uppercase; margin-bottom: 2px; }
    .value { display: block; font-size: 13px; color: #0f172a; font-weight: 500; }
    
    .badge {
        display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 10px; font-weight: bold; text-transform: uppercase; color: white; background-color: #64748b;
    }
    .badge-cliente { background-color: #0ea5e9; }
    .badge-loja { background-color: #8b5cf6; }
    .badge-vendedor { background-color: #f59e0b; }
    .footer { position: fixed; bottom: 0; left: 0; right: 0; background-color: #f8fafc; padding: 15px 40px; font-size: 9px; text-align: center; color: #94a3b8; border-top: 1px solid #e2e8f0; }
</style>
<body>
    <div class='header'>
        <div class='header-content'>
            <div class='logo-container'>
                @if(file_exists(public_path('images/logo-stofgard.png')))
                    <img src="{{ public_path('images/logo-stofgard.png') }}" alt="logo" class='logo-img'>
                @else
                    <div style='font-size:22px;font-weight:700'>STOFGARD</div>
                @endif
            </div>
            <div class='title-container'>
                <div class='doc-title'>Ficha Cadastral</div>
                <div style='font-size:12px;opacity:0.9'>{{ $cadastro->nome }}</div>
            </div>
        </div>
    </div>

    <div class='container'>
        <div class='section-title'>Identificação</div>
        <div class='info-grid'>
            <div class='info-row'>
                <div class='info-cell' style='width: 60%;'>
                    <span class='label'>Nome / Razão Social</span>
                    <span class='value'>{{ $cadastro->nome }}</span>
                </div>
                <div class='info-cell' style='width: 20%;'>
                    <span class='label'>Tipo</span>
                    <span class='badge badge-{{ $cadastro->tipo }}'>{{ $cadastro->tipo }}</span>
                </div>
                <div class='info-cell' style='width: 20%;'>
                    <span class='label'>Desde</span>
                    <span class='value'>{{ \Carbon\Carbon::parse($cadastro->created_at)->format('d/m/Y') }}</span>
                </div>
            </div>
            <div class='info-row'>
                <div class='info-cell'>
                    <span class='label'>CPF / CNPJ</span>
                    <span class='value'>{{ $cadastro->documento ?? 'Não informado' }}</span>
                </div>
                <div class='info-cell'>
                    <span class='label'>RG / Inscrição Estadual</span>
                    <span class='value'>{{ $cadastro->rg_ie ?? '-' }}</span>
                </div>
            </div>
        </div>

        <div class='section-title'>Canais de Contato</div>
        <div class='info-grid'>
            <div class='info-row'>
                <div class='info-cell' style='width: 40%;'>
                    <span class='label'>WhatsApp / Celular</span>
                    <span class='value'>{{ $cadastro->telefone }}</span>
                </div>
                <div class='info-cell' style='width: 60%;'>
                    <span class='label'>E-mail</span>
                    <span class='value'>{{ $cadastro->email ?? '-' }}</span>
                </div>
            </div>
        </div>

        <div class='section-title'>Endereço Principal</div>
        <div class='info-grid'>
            <div class='info-row'>
                <div class='info-cell' style='width: 80%;'>
                    <span class='label'>Logradouro</span>
                    <span class='value'>{{ $cadastro->logradouro }}, {{ $cadastro->numero }} {{ $cadastro->complemento ? ' - ' . $cadastro->complemento : '' }}</span>
                </div>
                <div class='info-cell' style='width: 20%;'>
                    <span class='label'>CEP</span>
                    <span class='value'>{{ $cadastro->cep }}</span>
                </div>
            </div>
            <div class='info-row'>
                <div class='info-cell'>
                    <span class='label'>Bairro</span>
                    <span class='value'>{{ $cadastro->bairro }}</span>
                </div>
                <div class='info-cell'>
                    <span class='label'>Cidade / UF</span>
                    <span class='value'>{{ $cadastro->cidade }} / {{ $cadastro->estado }}</span>
                </div>
            </div>
        </div>

        @if($cadastro->loja)
        <div class='section-title'>Vínculos Comerciais</div>
        <div class='info-grid'>
            <div class='info-row'>
                <div class='info-cell'>
                    <span class='label'>Vendedor Vinculado à Loja:</span>
                    <span class='value'>{{ $cadastro->loja->nome }}</span>
                </div>
            </div>
        </div>
        @endif
    </div>

    <div class='footer'>{{ env('APP_NAME') }} - Sistema Integrado de Gestão</div>
</body>
</html>
