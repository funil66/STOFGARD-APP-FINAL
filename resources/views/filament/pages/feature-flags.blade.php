<x-filament-panels::page>
    <div class="space-y-4">
        <div class="mb-6">
            <h2 class="text-2xl font-bold" style="color:#1f2937;">Feature Flags</h2>
            <p class="text-sm" style="color:#6b7280;">Gerencie recursos habilitados para cada cliente. Esta página é uma interface de administração simples — selecione um cadastro para editar flags específicas.</p>
        </div>

        <div class="rounded-xl p-6" style="background:white;">
            <h3 class="font-semibold mb-3">Flags disponíveis</h3>
            <ul class="space-y-2 text-sm text-gray-700">
                @foreach($this->getFlags() as $flag)
                    <li>
                        <strong>{{ $flag['label'] }}</strong> — {{ $flag['description'] }}
                    </li>
                @endforeach
n            </ul>
        </div>

        <p class="text-xs text-gray-500">Nota: a edição por cliente ainda deve ser feita na página de Edição do Cadastro. Esta página serve como referência e ponto de entrada para futuras ferramentas de bulk.</p>
    </div>
</x-filament-panels::page>
