@php
    $record = $getRecord();
    $dataConclusao = $record->data_conclusao;
    $tipoServico = $record->tipo_servico;
    
    // Verificar se é serviço combinado
    $isCombinado = str_contains(strtolower($tipoServico), 'higienização') && str_contains(strtolower($tipoServico), 'impermeabilização');
    
    if ($isCombinado && $dataConclusao) {
        // Duas garantias separadas
        $dataFimHigienizacao = \Carbon\Carbon::parse($dataConclusao)->addDays(90);
        $dataFimImpermeabilizacao = \Carbon\Carbon::parse($dataConclusao)->addDays(365);
        
        $garantiaHigienizacaoAtiva = now()->lte($dataFimHigienizacao);
        $diasRestantesHigienizacao = (int) now()->diffInDays($dataFimHigienizacao, false);
        
        $garantiaImpermeabilizacaoAtiva = now()->lte($dataFimImpermeabilizacao);
        $diasRestantesImpermeabilizacao = (int) now()->diffInDays($dataFimImpermeabilizacao, false);
    } else if ($dataConclusao) {
        // Garantia única
        $diasGarantia = $record->dias_garantia ?? 90;
        $dataFimGarantia = \Carbon\Carbon::parse($dataConclusao)->addDays($diasGarantia);
        $garantiaAtiva = now()->lte($dataFimGarantia);
        $diasRestantes = (int) now()->diffInDays($dataFimGarantia, false);
    }
@endphp

<style>
    @keyframes pulse-green {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.4; }
    }
    .pulse-green {
        animation: pulse-green 2s ease-in-out infinite;
    }
</style>

<div class="flex flex-wrap items-center gap-4">
    @if($dataConclusao)
        @if($isCombinado)
            {{-- Higienização --}}
            <div class="flex items-center gap-2">
                @if($garantiaHigienizacaoAtiva && $diasRestantesHigienizacao >= 0)
                    <div class="pulse-green" style="width: 14px; height: 14px; background-color: #10b981; border-radius: 50%; box-shadow: 0 0 10px rgba(16, 185, 129, 0.8);"></div>
                @else
                    <div style="width: 14px; height: 14px; background-color: #ef4444; border-radius: 50%; box-shadow: 0 0 8px rgba(239, 68, 68, 0.6);"></div>
                @endif
                <div>
                    <div style="font-size: 10px; font-weight: 600; color: #6b7280; text-transform: uppercase; line-height: 1.2;">Higienização</div>
                    <div style="font-size: 16px; font-weight: 700; color: {{ $garantiaHigienizacaoAtiva && $diasRestantesHigienizacao >= 0 ? '#10b981' : '#ef4444' }}; line-height: 1.3;">
                        {{ $diasRestantesHigienizacao >= 0 ? $diasRestantesHigienizacao : abs($diasRestantesHigienizacao) }} dias
                    </div>
                </div>
            </div>

            {{-- Impermeabilização --}}
            <div class="flex items-center gap-2">
                @if($garantiaImpermeabilizacaoAtiva && $diasRestantesImpermeabilizacao >= 0)
                    <div class="pulse-green" style="width: 14px; height: 14px; background-color: #10b981; border-radius: 50%; box-shadow: 0 0 10px rgba(16, 185, 129, 0.8);"></div>
                @else
                    <div style="width: 14px; height: 14px; background-color: #ef4444; border-radius: 50%; box-shadow: 0 0 8px rgba(239, 68, 68, 0.6);"></div>
                @endif
                <div>
                    <div style="font-size: 10px; font-weight: 600; color: #6b7280; text-transform: uppercase; line-height: 1.2;">Impermeabilização</div>
                    <div style="font-size: 16px; font-weight: 700; color: {{ $garantiaImpermeabilizacaoAtiva && $diasRestantesImpermeabilizacao >= 0 ? '#10b981' : '#ef4444' }}; line-height: 1.3;">
                        {{ $diasRestantesImpermeabilizacao >= 0 ? $diasRestantesImpermeabilizacao : abs($diasRestantesImpermeabilizacao) }} dias
                    </div>
                </div>
            </div>
        @else
            {{-- Garantia única --}}
            <div class="flex items-center gap-2">
                @if($garantiaAtiva && $diasRestantes >= 0)
                    <div class="pulse-green" style="width: 14px; height: 14px; background-color: #10b981; border-radius: 50%; box-shadow: 0 0 10px rgba(16, 185, 129, 0.8);"></div>
                @else
                    <div style="width: 14px; height: 14px; background-color: #ef4444; border-radius: 50%; box-shadow: 0 0 8px rgba(239, 68, 68, 0.6);"></div>
                @endif
                <div>
                    <div style="font-size: 10px; font-weight: 600; color: #6b7280; text-transform: uppercase; line-height: 1.2;">
                        {{ $garantiaAtiva && $diasRestantes >= 0 ? 'Garantia Ativa' : 'Garantia Expirada' }}
                    </div>
                    <div style="font-size: 16px; font-weight: 700; color: {{ $garantiaAtiva && $diasRestantes >= 0 ? '#10b981' : '#ef4444' }}; line-height: 1.3;">
                        {{ $diasRestantes >= 0 ? $diasRestantes : abs($diasRestantes) }} dias
                    </div>
                </div>
            </div>
        @endif
    @else
        {{-- Serviço não concluído --}}
        <div class="flex items-center gap-2">
            <div style="width: 14px; height: 14px; background-color: #9ca3af; border-radius: 50%;"></div>
            <div>
                <div style="font-size: 10px; font-weight: 600; color: #6b7280; text-transform: uppercase; line-height: 1.2;">Aguardando Conclusão</div>
                <div style="font-size: 12px; color: #6b7280; line-height: 1.3;">
                    Garantia inicia após conclusão
                </div>
            </div>
        </div>
    @endif
</div>
