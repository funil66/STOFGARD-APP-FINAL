<x-filament::page>
    {{ $this->form }}

    <div class="flex justify-end mt-4">
        <x-filament::button wire:click="simular" icon="heroicon-m-calculator">
            Simular Apuração
        </x-filament::button>
    </div>

    @if($simulationResults)
        <x-filament::section class="mt-8">
            <x-slot name="heading">
                Resultado da Simulação
            </x-slot>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <x-filament::card>
                    <div class="text-sm text-gray-500">Lucro Líquido</div>
                    <div class="text-2xl font-bold text-success-600">R$
                        {{ number_format($simulationResults['lucro_liquido'], 2, ',', '.') }}</div>
                </x-filament::card>

                <x-filament::card>
                    <div class="text-sm text-gray-500">Reserva de Caixa ({{ $simulationResults['percentual_reserva'] }}%)
                    </div>
                    <div class="text-2xl font-bold text-warning-600">R$
                        {{ number_format($simulationResults['reserva'], 2, ',', '.') }}</div>
                </x-filament::card>

                <x-filament::card>
                    <div class="text-sm text-gray-500">Disponível para Distribuição</div>
                    <div class="text-2xl font-bold text-primary-600">R$
                        {{ number_format($simulationResults['lucro_disponivel'], 2, ',', '.') }}</div>
                </x-filament::card>
            </div>

            <h3 class="text-lg font-medium mb-4">Detalhamento por Sócio</h3>

            <div class="overflow-x-auto border rounded-lg">
                <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th scope="col" class="px-6 py-3">Sócio</th>
                            <th scope="col" class="px-6 py-3">Percentual</th>
                            <th scope="col" class="px-6 py-3">Valor a Receber</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($simulationResults['distribuicao'] as $socio)
                            <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                    {{ $socio['nome'] }}
                                </td>
                                <td class="px-6 py-4">
                                    {{ $socio['percentual'] }}%
                                </td>
                                <td class="px-6 py-4 font-bold text-success-600">
                                    R$ {{ number_format($socio['valor'], 2, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="flex justify-end mt-6">
                <x-filament::button wire:click="processar" color="success" icon="heroicon-m-check-circle" size="lg">
                    Confirmar e Gerar Lançamentos
                </x-filament::button>
            </div>
        </x-filament::section>
    @endif
</x-filament::page>