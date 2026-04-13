<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificado de Garantia - OS {{ $os->numero_os ?? '' }}</title>
    <style>
        @page {
            margin: 0;
        }

        @php
            $primary = $config->pdf_color_primary ?? '#2563eb';
            $darkBg = '#374151';
            $text = $config->pdf_color_text ?? '#1f2937';

            $companyIdentity = function_exists('company_pdf_identity') ? company_pdf_identity() : [];

            $empresaNomeFantasia = $companyIdentity['empresa_nome'] ?? $config->empresa_nome ?? null;
            $empresaDoc = $companyIdentity['empresa_cnpj'] ?? $config->empresa_cnpj ?? $config->cnpj ?? null;
            $empresaTelefone = $companyIdentity['empresa_telefone'] ?? $config->empresa_telefone ?? $config->telefone_sistema ?? null;
            $empresaEmail = $companyIdentity['empresa_email'] ?? $config->empresa_email ?? $config->email_sistema ?? null;

            $logoPath = $companyIdentity['empresa_logo'] ?? $config->empresa_logo ?? null;
            if ($logoPath && !file_exists($logoPath)) {
                $logoPath = storage_path('app/public/' . ltrim($logoPath, '/'));
            }

            $cliente = $os?->cliente;
            $clienteNome = $cliente?->nome;
            $clienteDoc = $cliente?->cpf_cnpj ?? $cliente?->documento;
            $clienteTel = $cliente?->telefone;
            $idParceiro = $os?->id_parceiro;

            $clienteEndereco = trim(collect([
                $cliente?->logradouro,
                $cliente?->numero,
                $cliente?->complemento,
                $cliente?->bairro,
                $cliente?->cidade,
                $cliente?->estado,
                $cliente?->cep,
            ])->filter(fn($value) => filled($value))->implode(', '));

            $garantiaDias = (int) ($garantia->dias_garantia ?? 0);
            $garantiaInicio = $garantia->data_inicio ?? $os?->data_conclusao ?? $os?->updated_at;
            $garantiaFim = $garantia->data_fim ?? (filled($garantiaInicio) ? \Carbon\Carbon::parse($garantiaInicio)->addDays($garantiaDias) : null);

            $perfilGarantia = $os?->perfilGarantia;
            $termosGarantia = $perfilGarantia?->termos_legais;

            $itensServico = collect($os?->itens ?? []);
            $tiposServicoCobertos = $itensServico
                ->map(function ($item) {
                    $tipo = $item->servico_tipo ?? null;

                    if (blank($tipo) && filled($item->observacoes)) {
                        $observacao = trim((string) $item->observacoes);
                        $servico = \App\Services\ServiceTypeManager::get($observacao);
                        if ($servico) {
                            $tipo = $observacao;
                        }
                    }

                    return $tipo;
                })
                ->filter(fn ($value) => filled($value))
                ->unique()
                ->values();

            if ($tiposServicoCobertos->isEmpty() && filled($os?->tipo_servico)) {
                $tiposServicoCobertos = collect([$os->tipo_servico]);
            }

            $garantiasDaOs = collect($os?->garantias ?? []);

            $garantiasPorServico = $tiposServicoCobertos->map(function ($tipoServico) use ($garantiasDaOs, $os, $garantiaInicio, $garantiaFim, $garantiaDias) {
                $garantiaTipo = $garantiasDaOs->firstWhere('tipo_servico', $tipoServico);
                $servicoInfo = \App\Services\ServiceTypeManager::get($tipoServico) ?? [];

                $perfil = null;
                if (!empty($servicoInfo['perfil_garantia_id'])) {
                    $perfil = \App\Models\PerfilGarantia::find($servicoInfo['perfil_garantia_id']);
                }

                if (!$perfil && $os?->perfilGarantia && $os->tipo_servico === $tipoServico) {
                    $perfil = $os->perfilGarantia;
                }

                $dias = (int) ($garantiaTipo?->dias_garantia
                    ?? $perfil?->dias_garantia
                    ?? $servicoInfo['dias_garantia']
                    ?? $garantiaDias
                    ?? 0);

                $inicio = $garantiaTipo?->data_inicio ?? $garantiaInicio;
                $fim = $garantiaTipo?->data_fim ?? (filled($inicio) && $dias > 0 ? \Carbon\Carbon::parse($inicio)->addDays($dias) : $garantiaFim);

                return [
                    'tipo' => $tipoServico,
                    'label' => \App\Services\ServiceTypeManager::getLabel($tipoServico),
                    'descricao' => $servicoInfo['descricao_pdf'] ?? null,
                    'perfil' => $perfil,
                    'dias' => $dias,
                    'inicio' => $inicio,
                    'fim' => $fim,
                    'termos' => $perfil?->termos_legais,
                    'titulo_termos' => $perfil?->titulo_termos_garantia ?: 'TERMOS E CONDIÇÕES LEGAIS DE GARANTIA',
                ];
            })->values();

            $prazoDestaque = $garantiasPorServico->max('dias') ?: $garantiaDias;

            $tituloCertificado = $perfilGarantia?->titulo_certificado ?: 'CERTIFICADO DE GARANTIA';
            $subtituloCertificado = $perfilGarantia?->subtitulo_certificado ?: null;
            $tituloTermos = $perfilGarantia?->titulo_termos_garantia ?: 'TERMOS E CONDIÇÕES LEGAIS DE GARANTIA';
            $textoRodape = $perfilGarantia?->texto_rodape_certificado ?: 'Este documento atesta a qualidade do serviço prestado. Não possui valor fiscal.';

            $dataGeracaoCertificado = now()->format('d/m/Y H:i:s');
            $hashCertificado = null;
            $urlValidacaoCertificado = null;
            $qrCertificadoSvg = null;

            try {
                if (method_exists(\App\Services\DigitalSealService::class, 'buildSealData')) {
                    $seloDigital = \App\Services\DigitalSealService::buildSealData('garantia', (string) ($os->id ?? $garantia->id ?? '0'));
                    $dataGeracaoCertificado = $seloDigital['generated_at'] ?? $dataGeracaoCertificado;
                    $hashCertificado = $seloDigital['hash'] ?? null;
                    $urlValidacaoCertificado = $seloDigital['validation_url'] ?? null;
                    $qrCertificadoSvg = $seloDigital['qr_base64'] ?? null;
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Falha ao gerar selo digital no certificado de garantia', [
                    'erro' => $e->getMessage(),
                    'os_id' => $os->id ?? null,
                    'garantia_id' => $garantia->id ?? null,
                ]);
            }

            $hasSeloDigital = filled($qrCertificadoSvg) && filled($hashCertificado) && filled($urlValidacaoCertificado);
        @endphp

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica', Arial, sans-serif;
            font-size: 9px;
            color: {{ $text }};
            line-height: 1.25;
            margin: 0;
            padding: 0 0.8cm;
        }

        .page-frame {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
        }

        .page-frame>thead,
        .page-frame>tfoot {
            background: transparent;
        }

        .page-frame>thead {
            display: table-header-group;
        }

        .page-frame>tfoot {
            display: table-footer-group;
        }

        .page-frame>thead>tr>td,
        .page-frame>tfoot>tr>td,
        .page-frame>tbody>tr>td {
            border: none;
            padding: 0;
        }

        .header-spacer {
            height: 3.1cm;
        }

        .footer-spacer {
            height: 2.4cm;
        }

        .header {
            position: fixed;
            top: 0;
            left: 0.8cm;
            right: 0.8cm;
            height: 2.8cm;
            padding-top: 0.2cm;
            border-bottom: 2px solid #d1d5db;
            background: white;
            z-index: 1000;
            text-align: center;
        }

        .footer {
            position: fixed;
            bottom: 0;
            left: 0.8cm;
            right: 0.8cm;
            height: 2.2cm;
            background: white;
            z-index: 1000;
            padding-top: 2px;
        }

        .logo-img {
            max-width: 170px;
            max-height: 52px;
            margin-bottom: 2px;
        }

        .brand-name {
            font-size: 13px;
            font-weight: 700;
            color: #111827;
            text-transform: uppercase;
            letter-spacing: 0.35px;
            margin-bottom: 1px;
        }

        .company-info {
            font-size: 8px;
            color: #4b5563;
            line-height: 1.5;
        }

        .quadro {
            border: 1.8px solid #9ca3af;
            border-radius: 6px;
            margin-bottom: 8px;
            overflow: hidden;
            page-break-inside: avoid;
            background: #fff;
        }

        .quadro-certificado {
            border: 2px solid {{ $darkBg }};
            border-radius: 10px;
            margin-bottom: 10px;
            overflow: hidden;
            background: linear-gradient(to bottom, #ffffff 0%, #f8fafc 100%);
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
            page-break-inside: avoid;
        }

        .quadro-titulo {
            background: {{ $darkBg }};
            color: #fff;
            padding: 5px 8px;
            font-weight: 700;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.35px;
            text-align: center;
        }

        .quadro-corpo {
            padding: 8px;
            font-size: 8.7px;
        }

        .certificado-title {
            font-size: 24px;
            font-weight: 800;
            line-height: 1;
            text-align: center;
            color: #111827;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            margin-bottom: 5px;
            text-shadow: 0 1px 0 #ffffff;
        }

        .certificado-subtitle {
            text-align: center;
            font-size: 9px;
            color: #4b5563;
            margin-bottom: 5px;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.4px;
        }

        .dados-os {
            width: 58%;
            margin-left: auto;
            border: 1.8px solid #9ca3af;
            border-radius: 6px;
            padding: 7px;
            background: #ffffff;
            line-height: 1.35;
            font-size: 8.8px;
            box-shadow: inset 0 0 0 1px #f3f4f6;
        }

        .dados-os strong {
            color: {{ $darkBg }};
            text-transform: uppercase;
            font-size: 7.8px;
            letter-spacing: 0.25px;
        }

        .cliente-linha {
            margin-bottom: 2px;
            font-size: 9px;
        }

        .cliente-linha strong {
            color: #111827;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }

        table thead th {
            background: #f3f4f6;
            border: 1px solid #d1d5db;
            padding: 4px;
            text-align: left;
            font-size: 8.8px;
            color: #111827;
            text-transform: uppercase;
        }

        table tbody td {
            border: 1px solid #e5e7eb;
            padding: 5px 4px;
            font-size: 8.4px;
            vertical-align: top;
        }

        .servico-meta {
            margin-bottom: 5px;
            padding: 6px;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            background: #f9fafb;
            font-size: 8.4px;
            line-height: 1.35;
        }

        .data-destaque {
            margin-bottom: 5px;
            background: #eff6ff;
            border: 1.5px solid {{ $primary }};
            border-left: 6px solid {{ $primary }};
            border-radius: 4px;
            padding: 6px 8px;
            font-size: 8.4px;
            color: #1e3a8a;
            font-weight: 700;
        }

        .termos-box {
            border: 1px solid #d1d5db;
            border-left: 5px solid {{ $primary }};
            border-radius: 4px;
            padding: 7px;
            background: #fafafa;
            font-size: 8.2px;
            line-height: 1.3;
        }

        .termos-box p {
            margin: 0 0 4px 0;
        }

        .termos-box ul,
        .termos-box ol {
            margin: 4px 0 4px 14px;
            padding: 0;
        }

        .quadro-rodape {
            border: 1.6px solid #d1d5db;
            border-radius: 6px;
            text-align: left;
            padding: 5px 8px;
            background: #f9fafb;
            font-size: 8px;
            color: #374151;
            line-height: 1.2;
        }

        .rodape-meta {
            font-weight: 700;
            color: #111827;
            margin-bottom: 2px;
        }

        .rodape-flex {
            width: 100%;
            border-collapse: collapse;
        }

        .rodape-flex td {
            border: none;
            padding: 0;
            vertical-align: middle;
        }

        .rodape-qr {
            width: 64px;
            text-align: center;
            padding-right: 8px;
        }

        .rodape-qr img {
            display: block;
            width: 56px;
            height: 56px;
            margin: 0 auto;
        }

        .rodape-texto {
            font-size: 7.8px;
        }

        .rodape-micro {
            margin-top: 1px;
            font-size: 7px;
            color: #6b7280;
        }
    </style>
</head>

<body>
    <div class="header">
        @if($logoPath && file_exists($logoPath))
            <img src="data:image/png;base64,{{ base64_encode(file_get_contents($logoPath)) }}" alt="Logo" class="logo-img">
        @endif

        @if(filled($empresaNomeFantasia))
            <div class="brand-name">{{ $empresaNomeFantasia }}</div>
        @endif

        <div class="company-info">
            @if(filled($empresaDoc)){{ $empresaDoc }}@endif
            @if(filled($empresaDoc) && (filled($empresaTelefone) || filled($empresaEmail))) • @endif
            @if(filled($empresaTelefone)){{ $empresaTelefone }}@endif
            @if(filled($empresaTelefone) && filled($empresaEmail)) • @endif
            @if(filled($empresaEmail)){{ $empresaEmail }}@endif
        </div>
    </div>

    <div class="footer">
            <!-- DIGITAL_SEAL_SLOT -->
        <div class="quadro-rodape">
            <table class="rodape-flex">
                <tr>
                    <td class="rodape-qr">
                        @if($hasSeloDigital)
                            <img src="data:image/svg+xml;base64,{{ $qrCertificadoSvg }}" alt="QR Code de validação">
                        @endif
                    </td>
                    <td class="rodape-texto">
                        <div class="rodape-meta">
                            Emissão: {{ now()->format('d/m/Y') }}
                            @php
                                $dataFinalRodape = collect($garantiasPorServico)->pluck('fim')->filter()->sort()->last() ?? $garantiaFim;
                            @endphp
                            @if($dataFinalRodape)
                                | Validade até: {{ \Carbon\Carbon::parse($dataFinalRodape)->format('d/m/Y') }}
                            @endif
                            | Status: {{ ucfirst($garantia->status ?? 'ativa') }}
                        </div>
                        <div>{{ $textoRodape }}</div>
                        <div class="rodape-micro">Gerado em {{ $dataGeracaoCertificado }}</div>
                        @if($hasSeloDigital)
                            <div class="rodape-micro">Selo de validação: {{ $hashCertificado }}</div>
                            <div class="rodape-micro" data-digital-seal="embedded">Validação online: {{ $urlValidacaoCertificado }}</div>
                        @endif
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <table class="page-frame">
        <thead>
            <tr>
                <td><div class="header-spacer"></div></td>
            </tr>
        </thead>
        <tfoot>
            <tr>
                <td><div class="footer-spacer"></div></td>
            </tr>
        </tfoot>
        <tbody>
            <tr>
                <td>
                    <div class="quadro-certificado">
                        <div class="quadro-corpo">
                            <div class="certificado-title">{{ $tituloCertificado }}</div>
                            @if(filled($subtituloCertificado))
                                <div class="certificado-subtitle">{{ $subtituloCertificado }}</div>
                            @endif

                            <div class="dados-os">
                                <strong>Ordem de Serviço:</strong> #{{ $os->numero_os ?? '-' }}<br>
                                @if(filled($idParceiro))
                                    <strong>ID Parceiro:</strong> {{ $idParceiro }}<br>
                                @endif
                                <strong>Data do Serviço:</strong>
                                @if($os?->data_conclusao)
                                    {{ \Carbon\Carbon::parse($os->data_conclusao)->format('d/m/Y') }}
                                @else
                                    -
                                @endif
                                <br>
                                <strong>Prazo de Garantia:</strong>
                                @if($prazoDestaque > 0)
                                    {{ $prazoDestaque }} dias
                                    @if(($garantiasPorServico->count() ?? 0) > 1)
                                        (conforme serviço)
                                    @endif
                                @else
                                    -
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="quadro">
                        <div class="quadro-titulo">Dados do Cliente</div>
                        <div class="quadro-corpo">
                            @if(filled($clienteNome))
                                <div class="cliente-linha"><strong>Nome:</strong> {{ strtoupper($clienteNome) }}</div>
                            @endif
                            @if(filled($clienteTel))
                                <div class="cliente-linha"><strong>Telefone:</strong> {{ $clienteTel }}</div>
                            @endif
                            @if(filled($clienteDoc))
                                <div class="cliente-linha"><strong>Documento:</strong> {{ $clienteDoc }}</div>
                            @endif
                            @if(filled($clienteEndereco))
                                <div class="cliente-linha"><strong>Endereço:</strong> {{ $clienteEndereco }}</div>
                            @endif
                        </div>
                    </div>

                    <div class="quadro">
                        <div class="quadro-titulo">Serviço e Itens Cobertos pela Garantia</div>
                        <div class="quadro-corpo">
                            @php
                                $gruposItens = $itensServico->groupBy(function ($item) use ($os) {
                                    $tipo = $item->servico_tipo ?? null;

                                    if (blank($tipo) && filled($item->observacoes)) {
                                        $observacao = trim((string) $item->observacoes);
                                        $servico = \App\Services\ServiceTypeManager::get($observacao);
                                        if ($servico) {
                                            $tipo = $observacao;
                                        }
                                    }

                                    return $tipo ?: ($os->tipo_servico ?? 'servico');
                                });
                            @endphp

                            @if($garantiasPorServico->isNotEmpty())
                                @foreach($garantiasPorServico as $servicoGarantia)
                                    <div class="servico-meta" style="margin-bottom: 10px;">
                                        <strong>Tipo do Serviço:</strong> {{ $servicoGarantia['label'] ?? '-' }}
                                        @if(filled($servicoGarantia['descricao']))
                                            <br><strong>Descrição:</strong> {{ $servicoGarantia['descricao'] }}
                                        @endif
                                        <br><strong>Prazo de Garantia:</strong> {{ ($servicoGarantia['dias'] ?? 0) > 0 ? $servicoGarantia['dias'] . ' dias' : '-' }}
                                    </div>

                                    @php
                                        $itensDoTipo = $gruposItens->get($servicoGarantia['tipo'], collect());
                                    @endphp

                                    @if($itensDoTipo->isNotEmpty())
                                        <table style="margin-top: 6px; margin-bottom: 10px;">
                                            <thead>
                                                <tr>
                                                    <th style="width: 70%;">Descrição do Item</th>
                                                    <th style="width: 15%; text-align: center;">Quantidade</th>
                                                    <th style="width: 15%; text-align: center;">Unidade</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($itensDoTipo as $item)
                                                    <tr>
                                                        <td>{{ $item->descricao ?? $item->nome ?? $item->item ?? 'Item' }}</td>
                                                        <td style="text-align: center;">{{ $item->quantidade ?? 1 }}</td>
                                                        <td style="text-align: center;">{{ strtoupper($item->unidade_medida ?? $item->unidade ?? 'UN') }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    @endif
                                @endforeach
                            @elseif($itensServico->isNotEmpty())
                                <table>
                                    <thead>
                                        <tr>
                                            <th style="width: 70%;">Descrição do Item</th>
                                            <th style="width: 15%; text-align: center;">Quantidade</th>
                                            <th style="width: 15%; text-align: center;">Unidade</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($itensServico as $item)
                                            <tr>
                                                <td>{{ $item->descricao ?? $item->nome ?? $item->item ?? 'Item' }}</td>
                                                <td style="text-align: center;">{{ $item->quantidade ?? 1 }}</td>
                                                <td style="text-align: center;">{{ strtoupper($item->unidade_medida ?? $item->unidade ?? 'UN') }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @else
                                <div style="font-size: 9px; color: #6b7280;">Sem itens cadastrados para esta ordem de serviço.</div>
                            @endif
                        </div>
                    </div>

                    <div class="quadro">
                        <div class="quadro-titulo">{{ $tituloTermos }}</div>
                        <div class="quadro-corpo">
                            <div class="data-destaque">
                                Prazo de Garantia em Destaque: {{ $prazoDestaque > 0 ? $prazoDestaque . ' dias' : '-' }}
                            </div>

                            <div class="termos-box">
                                @if($garantiasPorServico->isNotEmpty())
                                    @foreach($garantiasPorServico as $servicoGarantia)
                                        <p style="margin-bottom: 4px;"><strong>{{ $servicoGarantia['label'] }}:</strong>
                                            @if($servicoGarantia['inicio'])
                                                {{ \Carbon\Carbon::parse($servicoGarantia['inicio'])->format('d/m/Y') }}
                                            @else
                                                -
                                            @endif
                                            @if($servicoGarantia['fim'])
                                                até {{ \Carbon\Carbon::parse($servicoGarantia['fim'])->format('d/m/Y') }}
                                            @endif
                                            ({{ $servicoGarantia['dias'] ?? 0 }} dias)
                                        </p>
                                    @endforeach
                                    @php
                                        $termoPrincipal = $garantiasPorServico
                                            ->pluck('termos')
                                            ->filter(fn($termo) => filled(strip_tags((string) $termo)))
                                            ->first();
                                    @endphp
                                    @if(filled(strip_tags((string) $termoPrincipal)))
                                        {!! $termoPrincipal !!}
                                    @elseif(filled(strip_tags((string) $termosGarantia)))
                                        {!! $termosGarantia !!}
                                    @else
                                        <p><strong>Cobertura:</strong> Garantia de {{ $garantiaDias }} dias corridos para os itens descritos neste documento.</p>
                                        <p><strong>Exclusões:</strong> Não cobre mau uso, desgaste natural, acidentes e eventos externos à execução do serviço.</p>
                                    @endif
                                @elseif(filled(strip_tags((string) $termosGarantia)))
                                    {!! $termosGarantia !!}
                                @else
                                    <p><strong>Cobertura:</strong> Este certificado confere garantia de {{ $garantiaDias }} dias corridos, contados a partir da data de execução do serviço, nos itens descritos neste documento.</p>
                                    <p><strong>Exclusões:</strong> Não cobre danos por mau uso, ação de terceiros, produtos não homologados, desgaste natural, acidentes e eventos externos à execução do serviço.</p>
                                    <p><strong>Validade:</strong> A garantia possui validade jurídica mediante quitação integral do orçamento correspondente.</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
</body>

</html>
