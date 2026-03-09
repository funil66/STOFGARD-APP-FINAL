<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Formulário de Filtros -->
        <form wire:submit.prevent="gerarRelatorio">
            {{ $this->form }}
        </form>

        <!-- Botões de Exportação -->
        <div class="responsive-btn-group">
            <x-filament::button wire:click="exportarPDF" icon="heroicon-o-document-arrow-down" color="danger">
                Exportar PDF
            </x-filament::button>
            <x-filament::button wire:click="exportarExcel" icon="heroicon-o-table-cells" color="success">
                Exportar Excel
            </x-filament::button>
        </div>

        <!-- Relatório de Serviços -->
        @if($this->form->getState()['relatorio'] === 'servicos' && !empty($dadosRelatorio))
            <div class="responsive-grid-stats">
                <!-- Cards de Resumo -->
                <x-filament::section>
                    <div class="text-center">
                        <div class="text-4xl font-bold text-primary-600">{{ $dadosRelatorio['total'] }}</div>
                        <div class="text-sm text-gray-600 mt-2">Total de Serviços</div>
                    </div>
                </x-filament::section>

                <x-filament::section>
                    <div class="text-center">
                        <div class="text-4xl font-bold text-success-600">{{ $dadosRelatorio['concluidos'] }}</div>
                        <div class="text-sm text-gray-600 mt-2">Concluídos</div>
                    </div>
                </x-filament::section>

                <x-filament::section>
                    <div class="text-center">
                        <div class="text-4xl font-bold text-warning-600">{{ $dadosRelatorio['em_andamento'] }}</div>
                        <div class="text-sm text-gray-600 mt-2">Em Andamento</div>
                    </div>
                </x-filament::section>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 md:gap-6">
                <x-filament::section>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-green-600">
                            R$ {{ number_format($dadosRelatorio['valor_total'], 2, ',', '.') }}
                        </div>
                        <div class="text-sm text-gray-600 mt-2">Valor Total</div>
                    </div>
                </x-filament::section>

                <x-filament::section>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-blue-600">
                            R$ {{ number_format($dadosRelatorio['ticket_medio'], 2, ',', '.') }}
                        </div>
                        <div class="text-sm text-gray-600 mt-2">Ticket Médio</div>
                    </div>
                </x-filament::section>
            </div>

            <!-- Tabela de Serviços Recentes -->
            @if($dadosRelatorio['servicos']->count() > 0)
                <x-filament::section>
                    <x-slot name="heading">
                        Últimos Serviços
                    </x-slot>

                    <div class="responsive-table-container overflow-x-auto w-full">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th class="px-4 py-3 text-left">Data</th>
                                    <th class="px-4 py-3 text-left">Cliente</th>
                                    <th class="px-4 py-3 text-left">Tipo</th>
                                    <th class="px-4 py-3 text-left">Status</th>
                                    <th class="px-4 py-3 text-right">Valor</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($dadosRelatorio['servicos'] as $servico)
                                    <tr>
                                        <td class="px-4 py-3">{{ $servico->created_at->format('d/m/Y') }}</td>
                                        <td class="px-4 py-3">{{ $servico->cadastro?->nome ?? $servico->cliente->nome ?? 'N/A' }}
                                        </td>
                                        <td class="px-4 py-3">{{ $servico->tipo }}</td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full
                                                            @if($servico->status === 'concluido') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                                            @elseif($servico->status === 'cancelado') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                                            @else bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                                            @endif">
                                                {{ ucfirst($servico->status) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-right font-medium">
                                            R$ {{ number_format($servico->valor_total, 2, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-filament::section>
            @endif
        @endif

        <!-- Relatório Financeiro -->
        @if($this->form->getState()['relatorio'] === 'financeiro' && !empty($dadosRelatorio))
            <div class="responsive-grid-stats">
                <x-filament::section>
                    <div class="text-center">
                        <div class="text-4xl font-bold text-green-600">
                            R$ {{ number_format($dadosRelatorio['receitas_pagas'], 2, ',', '.') }}
                        </div>
                        <div class="text-sm text-gray-600 mt-2">Receitas Pagas</div>
                        <div class="text-xs text-gray-500 mt-1">
                            Pendentes: R$ {{ number_format($dadosRelatorio['receitas_pendentes'], 2, ',', '.') }}
                        </div>
                    </div>
                </x-filament::section>

                <x-filament::section>
                    <div class="text-center">
                        <div class="text-4xl font-bold text-red-600">
                            R$ {{ number_format($dadosRelatorio['despesas_pagas'], 2, ',', '.') }}
                        </div>
                        <div class="text-sm text-gray-600 mt-2">Despesas Pagas</div>
                        <div class="text-xs text-gray-500 mt-1">
                            Pendentes: R$ {{ number_format($dadosRelatorio['despesas_pendentes'], 2, ',', '.') }}
                        </div>
                    </div>
                </x-filament::section>

                <x-filament::section>
                    <div class="text-center">
                        <div
                            class="text-4xl font-bold {{ $dadosRelatorio['saldo'] >= 0 ? 'text-blue-600' : 'text-red-600' }}">
                            R$ {{ number_format($dadosRelatorio['saldo'], 2, ',', '.') }}
                        </div>
                        <div class="text-sm text-gray-600 mt-2">Saldo</div>
                    </div>
                </x-filament::section>
            </div>

            <!-- Transações Recentes -->
            @if($dadosRelatorio['transacoes']->count() > 0)
                <x-filament::section>
                    <x-slot name="heading">
                        Transações Recentes
                    </x-slot>

                    <div class="responsive-table-container overflow-x-auto w-full">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th class="px-4 py-3 text-left">Data</th>
                                    <th class="px-4 py-3 text-left">Cadastro</th>
                                    <th class="px-4 py-3 text-left">Descrição</th>
                                    <th class="px-4 py-3 text-left">Tipo</th>
                                    <th class="px-4 py-3 text-left">Status</th>
                                    <th class="px-4 py-3 text-right">Valor</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($dadosRelatorio['transacoes'] as $transacao)
                                    <tr>
                                        <td class="px-4 py-3">
                                            {{ \Carbon\Carbon::parse($transacao->data_vencimento)->format('d/m/Y') }}</td>
                                        <td class="px-4 py-3">
                                            {{ $transacao->cadastro?->nome ?? ($transacao->cliente->nome ?? 'N/A') }}</td>
                                        <td class="px-4 py-3">{{ $transacao->descricao }}</td>
                                        <td class="px-4 py-3">
                                            <span
                                                class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full
                                                            {{ $transacao->tipo === 'receita' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' }}">
                                                {{ ucfirst($transacao->tipo) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span
                                                class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full
                                                            {{ $transacao->status === 'pago' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' }}">
                                                {{ ucfirst($transacao->status) }}
                                            </span>
                                        </td>
                                        <td
                                            class="px-4 py-3 text-right font-medium {{ $transacao->tipo === 'receita' ? 'text-green-600' : 'text-red-600' }}">
                                            {{ $transacao->tipo === 'receita' ? '+' : '-' }} R$
                                            {{ number_format($transacao->valor, 2, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-filament::section>
            @endif
        @endif

        <!-- Relatório de Clientes -->
        @if($this->form->getState()['relatorio'] === 'clientes' && !empty($dadosRelatorio))
            <div class="responsive-grid-stats">
                <x-filament::section>
                    <div class="text-center">
                        <div class="text-4xl font-bold text-primary-600">{{ $dadosRelatorio['total'] }}</div>
                        <div class="text-sm text-gray-600 mt-2">Total de Clientes</div>
                    </div>
                </x-filament::section>

                <x-filament::section>
                    <div class="text-center">
                        <div class="text-4xl font-bold text-success-600">{{ $dadosRelatorio['novos_periodo'] }}</div>
                        <div class="text-sm text-gray-600 mt-2">Novos no Período</div>
                    </div>
                </x-filament::section>

                <x-filament::section>
                    <div class="text-center">
                        <div class="text-4xl font-bold text-info-600">{{ $dadosRelatorio['com_servicos'] }}</div>
                        <div class="text-sm text-gray-600 mt-2">Com Serviços</div>
                    </div>
                </x-filament::section>

                <x-filament::section>
                    <div class="text-center">
                        <div class="text-4xl font-bold text-warning-600">{{ $dadosRelatorio['inativos'] }}</div>
                        <div class="text-sm text-gray-600 mt-2">Sem Serviços</div>
                    </div>
                </x-filament::section>
            </div>

            <!-- Top Clientes -->
            @if($dadosRelatorio['top_clientes']->count() > 0)
                <x-filament::section>
                    <x-slot name="heading">
                        Top 10 Clientes (Por Quantidade de Serviços)
                    </x-slot>

                    <div class="responsive-table-container overflow-x-auto w-full">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th class="px-4 py-3 text-left">#</th>
                                    <th class="px-4 py-3 text-left">Cliente</th>
                                    <th class="px-4 py-3 text-left">Telefone</th>
                                    <th class="px-4 py-3 text-center">Serviços</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($dadosRelatorio['top_clientes'] as $index => $cliente)
                                    <tr>
                                        <td class="px-4 py-3 font-bold">{{ $index + 1 }}</td>
                                        <td class="px-4 py-3">{{ $cliente['nome'] ?? 'N/A' }}</td>
                                        <td class="px-4 py-3">{{ $cliente['telefone'] ?? 'N/A' }}</td>
                                        <td class="px-4 py-3 text-center">
                                            <span
                                                class="inline-flex items-center px-3 py-1 text-sm font-semibold rounded-full bg-primary-100 text-primary-800 dark:bg-primary-900 dark:text-primary-200">
                                                {{ $cliente['total_servicos'] ?? 0 }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-filament::section>
            @endif
        @endif

        <!-- Relatório de Estoque -->
        @if($this->form->getState()['relatorio'] === 'estoque' && !empty($dadosRelatorio))
            <div class="responsive-grid-stats">
                <x-filament::section>
                    <div class="text-center">
                        <div class="text-4xl font-bold text-primary-600">{{ $dadosRelatorio['total_itens'] }}</div>
                        <div class="text-sm text-gray-600 mt-2">Total de Itens</div>
                    </div>
                </x-filament::section>

                <x-filament::section>
                    <div class="text-center">
                        <div class="text-4xl font-bold {{ $dadosRelatorio['total_abaixo_minimo'] > 0 ? 'text-red-600' : 'text-green-600' }}">
                            {{ $dadosRelatorio['total_abaixo_minimo'] }}
                        </div>
                        <div class="text-sm text-gray-600 mt-2">Abaixo do Mínimo</div>
                    </div>
                </x-filament::section>

                <x-filament::section>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-blue-600">
                            R$ {{ number_format($dadosRelatorio['valor_total_interno'], 2, ',', '.') }}
                        </div>
                        <div class="text-sm text-gray-600 mt-2">Valor Interno (Custo)</div>
                    </div>
                </x-filament::section>

                <x-filament::section>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-green-600">
                            R$ {{ number_format($dadosRelatorio['valor_total_venda'], 2, ',', '.') }}
                        </div>
                        <div class="text-sm text-gray-600 mt-2">Valor Venda (Potencial)</div>
                    </div>
                </x-filament::section>
            </div>

            @if($dadosRelatorio['abaixo_minimo']->count() > 0)
                <x-filament::section>
                    <x-slot name="heading">
                        ⚠️ Itens Abaixo do Mínimo
                    </x-slot>
                    <div class="responsive-table-container overflow-x-auto w-full">
                        <table class="w-full text-sm">
                            <thead class="bg-red-50 dark:bg-red-900/20">
                                <tr>
                                    <th class="px-4 py-3 text-left">Item</th>
                                    <th class="px-4 py-3 text-center">Qtd Atual</th>
                                    <th class="px-4 py-3 text-center">Mínimo</th>
                                    <th class="px-4 py-3 text-left">Local</th>
                                    <th class="px-4 py-3 text-right">Preço Venda</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($dadosRelatorio['abaixo_minimo'] as $item)
                                    <tr class="bg-red-50/50 dark:bg-red-900/10">
                                        <td class="px-4 py-3 font-medium">{{ $item['item'] }}</td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="inline-flex items-center px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                                {{ number_format($item['quantidade'], 1) }} {{ $item['unidade'] }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-center text-gray-500">{{ number_format($item['minimo_alerta'], 1) }} {{ $item['unidade'] }}</td>
                                        <td class="px-4 py-3">{{ $item['local'] }}</td>
                                        <td class="px-4 py-3 text-right">R$ {{ number_format($item['preco_venda'], 2, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-filament::section>
            @endif

            @if($dadosRelatorio['consumo_periodo']->count() > 0)
                <x-filament::section>
                    <x-slot name="heading">
                        📊 Consumo no Período
                    </x-slot>
                    <div class="responsive-table-container overflow-x-auto w-full">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th class="px-4 py-3 text-left">Produto</th>
                                    <th class="px-4 py-3 text-center">Total Consumido</th>
                                    <th class="px-4 py-3 text-center">Nº de OS</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($dadosRelatorio['consumo_periodo'] as $consumo)
                                    <tr>
                                        <td class="px-4 py-3 font-medium">{{ $consumo['item'] }}</td>
                                        <td class="px-4 py-3 text-center">
                                            {{ number_format($consumo['total_consumido'], 1) }} {{ $consumo['unidade'] }}
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                {{ $consumo['total_os'] }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-filament::section>
            @endif
        @endif

        <!-- Relatório de Comissões -->
        @if($this->form->getState()['relatorio'] === 'comissoes' && !empty($dadosRelatorio))
            <div class="responsive-grid-stats">
                <x-filament::section>
                    <div class="text-center">
                        <div class="text-4xl font-bold text-primary-600">{{ $dadosRelatorio['qtd_comissoes'] }}</div>
                        <div class="text-sm text-gray-600 mt-2">Total de Comissões</div>
                    </div>
                </x-filament::section>

                <x-filament::section>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-green-600">
                            R$ {{ number_format($dadosRelatorio['total_geral'], 2, ',', '.') }}
                        </div>
                        <div class="text-sm text-gray-600 mt-2">Valor Total</div>
                    </div>
                </x-filament::section>

                <x-filament::section>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-blue-600">
                            R$ {{ number_format($dadosRelatorio['total_pago'], 2, ',', '.') }}
                        </div>
                        <div class="text-sm text-gray-600 mt-2">Total Pago</div>
                    </div>
                </x-filament::section>

                <x-filament::section>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-yellow-600">
                            R$ {{ number_format($dadosRelatorio['total_pendente'], 2, ',', '.') }}
                        </div>
                        <div class="text-sm text-gray-600 mt-2">Total Pendente</div>
                    </div>
                </x-filament::section>
            </div>

            @if($dadosRelatorio['por_parceiro']->count() > 0)
                <x-filament::section>
                    <x-slot name="heading">
                        👥 Comissões por Parceiro
                    </x-slot>
                    <div class="responsive-table-container overflow-x-auto w-full">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th class="px-4 py-3 text-left">Parceiro</th>
                                    <th class="px-4 py-3 text-left">Tipo</th>
                                    <th class="px-4 py-3 text-center">Qtd</th>
                                    <th class="px-4 py-3 text-right">Total</th>
                                    <th class="px-4 py-3 text-right">Pago</th>
                                    <th class="px-4 py-3 text-right">Pendente</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($dadosRelatorio['por_parceiro'] as $parceiro)
                                    <tr>
                                        <td class="px-4 py-3 font-medium">{{ $parceiro['nome'] }}</td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                                                {{ ucfirst($parceiro['tipo']) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-center">{{ $parceiro['qtd'] }}</td>
                                        <td class="px-4 py-3 text-right font-semibold">R$ {{ number_format($parceiro['total'], 2, ',', '.') }}</td>
                                        <td class="px-4 py-3 text-right text-green-600">R$ {{ number_format($parceiro['pago'], 2, ',', '.') }}</td>
                                        <td class="px-4 py-3 text-right text-yellow-600">R$ {{ number_format($parceiro['pendente'], 2, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-filament::section>
            @endif

            @if($dadosRelatorio['lista']->count() > 0)
                <x-filament::section>
                    <x-slot name="heading">
                        📝 Últimas Comissões
                    </x-slot>
                    <div class="responsive-table-container overflow-x-auto w-full">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th class="px-4 py-3 text-left">Data</th>
                                    <th class="px-4 py-3 text-left">Parceiro</th>
                                    <th class="px-4 py-3 text-left">Descrição</th>
                                    <th class="px-4 py-3 text-left">Status</th>
                                    <th class="px-4 py-3 text-right">Valor</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($dadosRelatorio['lista'] as $comissao)
                                    <tr>
                                        <td class="px-4 py-3">{{ $comissao['data'] }}</td>
                                        <td class="px-4 py-3">{{ $comissao['parceiro'] }}</td>
                                        <td class="px-4 py-3 truncate max-w-[200px]">{{ $comissao['descricao'] }}</td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full
                                                {{ $comissao['status'] === 'Paga' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' }}">
                                                {{ $comissao['status'] }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-right font-medium">
                                            R$ {{ number_format($comissao['valor'], 2, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-filament::section>
            @endif
        @endif
    </div>
</x-filament-panels::page>