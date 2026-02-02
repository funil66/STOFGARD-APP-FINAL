<div class="space-y-6">
    {{-- Link de Voltar --}}
    <div>
        <a href="{{ route('cadastros.show', ['uuid' => $uuid]) }}" class="inline-flex items-center text-sm text-blue-600 hover:text-blue-800 transition-colors">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
            Voltar para visualização
        </a>
    </div>

    {{-- Cabeçalho --}}
    <div class="border-b border-gray-200 pb-4">
        <h1 class="text-2xl font-bold text-gray-800">Editar: {{ $model->nome }}</h1>
        <p class="text-sm text-gray-500 mt-1">Atualize as informações do cadastro abaixo</p>
    </div>

    {{-- Formulário --}}
    <form wire:submit.prevent="save" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 space-y-6">
        {{-- Nome --}}
        <div>
            <label for="nome" class="block text-sm font-medium text-gray-700 mb-1">Nome *</label>
            <input 
                wire:model.defer="nome" 
                id="nome"
                name="nome" 
                type="text"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                placeholder="Nome completo"
            >
            @error('nome') 
                <span class="text-red-600 text-sm mt-1 block">{{ $message }}</span> 
            @enderror
        </div>

        {{-- Email e Telefone --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input 
                    wire:model.defer="email" 
                    id="email"
                    name="email" 
                    type="email"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                    placeholder="email@exemplo.com"
                >
                @error('email') 
                    <span class="text-red-600 text-sm mt-1 block">{{ $message }}</span> 
                @enderror
            </div>

            <div>
                <label for="telefone" class="block text-sm font-medium text-gray-700 mb-1">Telefone</label>
                <input 
                    wire:model.defer="telefone" 
                    id="telefone"
                    name="telefone" 
                    type="tel"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                    placeholder="(00) 00000-0000"
                >
            </div>
        </div>

        {{-- Tipo de Cadastro --}}
        <div>
            <label for="cadastroTipo" class="block text-sm font-medium text-gray-700 mb-1">Tipo de Cadastro</label>
            <select 
                wire:model="cadastroTipo" 
                id="cadastroTipo"
                name="cadastroTipo" 
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors bg-white"
            >
                <option value="cliente">Cliente</option>
                <option value="loja">Loja</option>
                <option value="vendedor">Vendedor</option>
            </select>
        </div>

        {{-- Campos específicos para Cliente --}}
        @if($type === 'cliente')
            <div class="pt-4 border-t border-gray-100">
                <h3 class="text-sm font-semibold text-gray-700 mb-4">Dados do Cliente</h3>
                <div>
                    <label for="cpf_cnpj" class="block text-sm font-medium text-gray-700 mb-1">CPF/CNPJ</label>
                    <input 
                        wire:model.defer="cpf_cnpj" 
                        id="cpf_cnpj"
                        name="cpf_cnpj" 
                        type="text"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                        placeholder="000.000.000-00"
                    >
                </div>
            </div>
        @else
            {{-- Campos específicos para Parceiro --}}
            <div class="pt-4 border-t border-gray-100 space-y-4">
                <h3 class="text-sm font-semibold text-gray-700 mb-4">Dados do Parceiro</h3>
                
                <div>
                    <label for="tipo" class="block text-sm font-medium text-gray-700 mb-1">Tipo</label>
                    <select 
                        wire:model="tipo" 
                        id="tipo"
                        name="tipo" 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors bg-white"
                    >
                        <option value="loja">Loja</option>
                        <option value="vendedor">Vendedor</option>
                    </select>
                </div>

                <div>
                    <label for="razao_social" class="block text-sm font-medium text-gray-700 mb-1">Razão Social</label>
                    <input 
                        wire:model.defer="razao_social" 
                        id="razao_social"
                        name="razao_social" 
                        type="text"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                        placeholder="Razão social da empresa"
                    >
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="cnpj_cpf" class="block text-sm font-medium text-gray-700 mb-1">CNPJ/CPF</label>
                        <input 
                            wire:model.defer="cnpj_cpf" 
                            id="cnpj_cpf"
                            name="cnpj_cpf" 
                            type="text"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                            placeholder="00.000.000/0000-00"
                        >
                    </div>

                    <div>
                        <label for="percentual_comissao" class="block text-sm font-medium text-gray-700 mb-1">Percentual de Comissão (%)</label>
                        <input 
                            wire:model.defer="percentual_comissao" 
                            id="percentual_comissao"
                            name="percentual_comissao" 
                            type="number"
                            step="0.01"
                            min="0"
                            max="100"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                            placeholder="0.00"
                        >
                    </div>
                </div>
            </div>
        @endif

        {{-- Upload de Arquivo --}}
        <div class="pt-4 border-t border-gray-100">
            <label class="block text-sm font-medium text-gray-700 mb-2">Adicionar Novo Arquivo</label>
            <div class="flex items-center justify-center w-full">
                <label class="flex flex-col items-center justify-center w-full h-32 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100 transition-colors">
                    <div class="flex flex-col items-center justify-center pt-5 pb-6">
                        <svg class="w-8 h-8 mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                        </svg>
                        <p class="mb-2 text-sm text-gray-500"><span class="font-semibold">Clique para enviar</span> ou arraste</p>
                        <p class="text-xs text-gray-400">PDF, DOC, PNG, JPG (MAX. 10MB)</p>
                    </div>
                    <input type="file" wire:model="newUpload" name="newUpload" class="hidden">
                </label>
            </div>
            @error('newUpload') 
                <div class="text-red-600 text-sm mt-2">{{ $message }}</div> 
            @enderror
        </div>

        {{-- Botão de Salvar --}}
        <div class="pt-6 border-t border-gray-100 flex items-center justify-end gap-4">
            <a href="{{ route('cadastros.show', ['uuid' => $uuid]) }}" 
               class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                Cancelar
            </a>
            <button 
                type="submit" 
                class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors flex items-center"
            >
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                Salvar Alterações
            </button>
        </div>
    </form>

    {{-- Mensagem de Sucesso --}}
    @if(session('success'))
        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <p class="text-green-700">{{ session('success') }}</p>
            </div>
        </div>
    @endif
</div>
