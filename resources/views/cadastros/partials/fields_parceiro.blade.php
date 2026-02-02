<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div class="py-2">
        <span class="block text-xs font-medium text-gray-500 uppercase tracking-wide">Tipo</span>
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 mt-1">
            {{ ucfirst($item->tipo ?? '—') }}
        </span>
    </div>
    
    <div class="py-2">
        <span class="block text-xs font-medium text-gray-500 uppercase tracking-wide">Razão Social</span>
        <span class="block text-sm text-gray-800 mt-1">{{ $item->razao_social ?? '—' }}</span>
    </div>
    
    <div class="py-2">
        <span class="block text-xs font-medium text-gray-500 uppercase tracking-wide">CNPJ/CPF</span>
        <span class="block text-sm text-gray-800 mt-1">{{ $item->cnpj_cpf ?? $item->cpf_cnpj ?? '—' }}</span>
    </div>
    
    <div class="py-2">
        <span class="block text-xs font-medium text-gray-500 uppercase tracking-wide">Percentual Comissão</span>
        <span class="block text-sm text-gray-800 mt-1">
            @if(isset($item->percentual_comissao))
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                    {{ number_format($item->percentual_comissao, 2, ',', '.') }}%
                </span>
            @else
                —
            @endif
        </span>
    </div>
    
    <div class="py-2">
        <span class="block text-xs font-medium text-gray-500 uppercase tracking-wide">Status</span>
        <span class="block mt-1">
            @if(isset($item->ativo) && $item->ativo)
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                    </svg>
                    Ativo
                </span>
            @else
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                    Inativo
                </span>
            @endif
        </span>
    </div>
    
    <div class="py-2">
        <span class="block text-xs font-medium text-gray-500 uppercase tracking-wide">Telefone</span>
        <span class="block text-sm text-gray-800 mt-1">{{ $item->telefone ?? '—' }}</span>
    </div>
    
    <div class="py-2">
        <span class="block text-xs font-medium text-gray-500 uppercase tracking-wide">Celular</span>
        <span class="block text-sm text-gray-800 mt-1">{{ $item->celular ?? '—' }}</span>
    </div>
    
    <div class="py-2 md:col-span-2">
        <span class="block text-xs font-medium text-gray-500 uppercase tracking-wide">Endereço</span>
        <span class="block text-sm text-gray-800 mt-1">
            @if($item->logradouro)
                {{ $item->logradouro }}{{ $item->numero ? ', ' . $item->numero : '' }}{{ $item->complemento ? ' - ' . $item->complemento : '' }}<br>
                {{ $item->bairro ?? '' }}{{ $item->cidade ? ', ' . $item->cidade : '' }}{{ $item->estado ? ' - ' . $item->estado : '' }}
            @else
                —
            @endif
        </span>
    </div>
</div>