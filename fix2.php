<?php
$path = 'resources/views/pdf/orcamento.blade.php';
$content = file_get_contents($path);

// First find where the rogue \n                    <div class="company-info"> starts
$start = strpos($content, '<div class="company-info">');
// Find the <table class="page-frame">
$table_start = strpos($content, '<table class="page-frame">', $start);
// Find the </thead>
$thead_end = strpos($content, '</thead>', $table_start) + strlen('</thead>');

$bad_portion = substr($content, $start - 25, $thead_end - ($start - 25));

$fixed_thead = <<<HTML

    <!-- MAIN CONTENT (Flows inside margins) -->
    <table class="page-frame">
        <thead>
            <tr>
                <td><!-- FIXED HEADER -->
    @if(\$headerBlock)
        @php \$data = \$headerBlock['data'] ?? []; @endphp
        <div class="header"
            style="justify-content: {{ (\$data['alignment'] ?? 'left') === 'center' ? 'center' : 'space-between' }};
                   flex-direction: {{ (\$data['alignment'] ?? 'left') === 'center' ? 'column' : 'row' }};
                   align-items: {{ (\$data['alignment'] ?? 'left') === 'center' ? 'center' : 'flex-start' }};">
            
            @if((\$data['show_logo'] ?? true))
                <div class="header-left" style="{{ (\$data['alignment'] ?? 'left') === 'center' ? 'text-align:center; max-width:100%;' : '' }}">
                    @php
                        \$logoPath = \$config->empresa_logo ?? null;
                        if (\$logoPath && !file_exists(\$logoPath)) \$logoPath = storage_path('app/public/' . \$logoPath);
                    @endphp
                    @if(\$logoPath && file_exists(\$logoPath))
                        <img src="data:image/png;base64,{{ base64_encode(file_get_contents(\$logoPath)) }}" alt="Logo" class="logo-img">
                    @else
                        <div style="font-size: 16px; font-weight: bold; color: {{ \$primary }}; margin-bottom: 8px;">
                            {{ \$config->nome_sistema ?? 'Empresa' }}
                        </div>
                    @endif
                    <div class="company-info">
                        {{ \$config->empresa_cnpj ?? '' }}<br>
                        {{ \$config->empresa_telefone ?? '' }}<br>
                        {{ \$config->empresa_email ?? '' }}
                    </div>
                </div>
            @endif

            @if((\$data['show_dates'] ?? true))
                <div class="header-right" style="{{ (\$data['alignment'] ?? 'left') === 'center' ? 'margin-top:10px; width:100%; text-align:center;' : '' }}">
                    <div style="font-size: 10px; opacity: 0.9;">ORÇAMENTO</div>
                    <div class="numero-orcamento">{{ \$orcamento->numero ?? \$orcamento->numero_orcamento }}</div>
                    <div class="datas">
                        @if(!empty(\$orcamento->id_parceiro))
                            <span style="font-weight: bold; color: yellow;">ID Parceiro: {{ \$orcamento->id_parceiro }}</span><br>
                        @endif
                        Emissão: {{ \$orcamento->data_orcamento ? \Carbon\Carbon::parse(\$orcamento->data_orcamento)->format('d/m/Y') : now()->format('d/m/Y') }}<br>
                        Validade: {{ \$orcamento->data_validade ? \Carbon\Carbon::parse(\$orcamento->data_validade)->format('d/m/Y') : '' }}
                    </div>
                </div>
            @endif
        </div>
    @endif</td>
            </tr>
        </thead>
HTML;

$new_content = str_replace($bad_portion, $fixed_thead, $content);
file_put_contents($path, $new_content);
