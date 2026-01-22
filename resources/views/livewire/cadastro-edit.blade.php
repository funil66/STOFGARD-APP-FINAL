<div>
    <a href="{{ route('cadastros.show', ['uuid' => $uuid]) }}" class="text-sm text-blue-600 underline">&larr; Voltar</a>

    <h1 class="text-2xl font-bold mt-4">Editar: {{ $model->nome }}</h1>

    <form wire:submit.prevent="save" class="bg-white p-4 rounded shadow mt-4">
        <div class="mb-2">
            <label class="block text-sm font-medium text-gray-700">Nome</label>
            <input wire:model.defer="nome" name="nome" class="mt-1 block w-full border rounded px-3 py-2">
            @error('nome') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium">Email</label>
                <input wire:model.defer="email" name="email" class="mt-1 block w-full border rounded px-3 py-2">
                @error('email') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium">Telefone</label>
                <input wire:model.defer="telefone" name="telefone" class="mt-1 block w-full border rounded px-3 py-2">
            </div>
        </div>

        <div class="mt-4">
            <label class="block text-sm font-medium">Tipo de Cadastro</label>
            <select wire:model="cadastroTipo" name="cadastroTipo" class="mt-1 block w-full border rounded px-3 py-2">
                <option value="cliente">Cliente</option>
                <option value="loja">Loja</option>
                <option value="vendedor">Vendedor</option>
            </select>
        </div>

        @if($type === 'cliente')
            <div class="mt-4">
                <label class="block text-sm font-medium">CPF/CNPJ</label>
                <input wire:model.defer="cpf_cnpj" name="cpf_cnpj" class="mt-1 block w-full border rounded px-3 py-2">
            </div>
        @else
            <div class="mt-4">
                <label class="block text-sm font-medium">Tipo</label>
                <select wire:model="tipo" name="tipo" class="mt-1 block w-full border rounded px-3 py-2">
                    <option value="loja">Loja</option>
                    <option value="vendedor">Vendedor</option>
                </select>
            </div>

            <div class="mt-4">
                <label class="block text-sm font-medium">Razão Social</label>
                <input wire:model.defer="razao_social" name="razao_social" class="mt-1 block w-full border rounded px-3 py-2">
            </div>

            <div class="grid grid-cols-2 gap-4 mt-4">
                <div>
                    <label class="block text-sm font-medium">CNPJ/CPF</label>
                    <input wire:model.defer="cnpj_cpf" name="cnpj_cpf" class="mt-1 block w-full border rounded px-3 py-2">
                </div>

                <div>
                    <label class="block text-sm font-medium">Percentual Comissão</label>
                    <input wire:model.defer="percentual_comissao" name="percentual_comissao" class="mt-1 block w-full border rounded px-3 py-2">
                </div>
            </div>
        @endif

        <div class="mt-4">
            <label class="block text-sm font-medium">Novo Arquivo</label>
            <input type="file" wire:model="newUpload" name="newUpload">
            @error('newUpload') <div class="text-red-600 text-sm">{{ $message }}</div> @enderror
        </div>

        <div class="mt-4">
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">Salvar</button>
        </div>
    </form>

    @if(session('success'))
        <div class="mt-4 text-green-600">{{ session('success') }}</div>
    @endif
</div>
