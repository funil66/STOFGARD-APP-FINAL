<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div class="py-2">
        <span class="block text-xs font-medium text-gray-500 uppercase tracking-wide">Email</span>
        <span class="block text-sm text-gray-800 mt-1">{{ $item->email ?? '—' }}</span>
    </div>

    <div class="py-2">
        <span class="block text-xs font-medium text-gray-500 uppercase tracking-wide">Telefone</span>
        <span class="block text-sm text-gray-800 mt-1">{{ $item->telefone ?? '—' }}</span>
    </div>

    <div class="py-2">
        <span class="block text-xs font-medium text-gray-500 uppercase tracking-wide">Celular</span>
        <span class="block text-sm text-gray-800 mt-1">{{ $item->celular ?? '—' }}</span>
    </div>

    <div class="py-2">
        <span class="block text-xs font-medium text-gray-500 uppercase tracking-wide">CPF/CNPJ</span>
        <span class="block text-sm text-gray-800 mt-1">{{ $item->documento ?? $item->cpf_cnpj ?? '—' }}</span>
    </div>

    <div class="py-2">
        <span class="block text-xs font-medium text-gray-500 uppercase tracking-wide">CEP</span>
        <span class="block text-sm text-gray-800 mt-1">{{ $item->cep ?? '—' }}</span>
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