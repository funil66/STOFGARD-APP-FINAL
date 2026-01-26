<x-filament::page>
    <form wire:submit.prevent="save">
        {{ $this->form }}
        
        <div class="mt-6 flex justify-end">
            <x-filament::button type="submit" size="lg" color="primary">
                Salvar Todas as Configurações
            </x-filament::button>
        </div>
    </form>
</x-filament::page>