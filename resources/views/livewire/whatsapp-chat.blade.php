<div class="flex flex-col h-[500px] border border-gray-300 dark:border-gray-700 rounded-xl overflow-hidden shadow-sm">
    <div
        class="bg-gray-100 dark:bg-gray-800 p-4 border-b border-gray-300 dark:border-gray-700 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <x-heroicon-o-chat-bubble-left-ellipsis class="w-6 h-6 text-green-500" />
            <h3 class="font-bold text-lg dark:text-white">WhatsApp - {{ $record->nome }}</h3>
        </div>
        <span class="text-xs text-green-500 font-semibold flex items-center gap-1">
            <span class="w-2 h-2 rounded-full bg-green-500 block animate-pulse"></span> Sistema Ativo
        </span>
    </div>

    <div class="flex-1 overflow-y-auto p-4 space-y-3 bg-[url('https://user-images.githubusercontent.com/15075759/28719144-86dc0f70-73b1-11e7-911d-60d70fcded21.png')] bg-repeat"
        style="background-color: #efeae2; background-blend-mode: multiply;" id="chat-container">
        @forelse($messages as $msg)
            <div class="flex {{ $msg->direction === 'out' ? 'justify-end' : 'justify-start' }}">
                <div
                    class="max-w-[80%] p-3 shadow-md relative {{ $msg->direction === 'out' ? 'bg-[#d9fdd3] dark:bg-[#005c4b] text-gray-800 dark:text-gray-100 rounded-l-lg rounded-br-lg' : 'bg-white dark:bg-[#202c33] text-gray-800 dark:text-gray-100 rounded-r-lg rounded-bl-lg' }}">
                    <p class="text-sm whitespace-pre-wrap">{{ $msg->body }}</p>
                    <span class="text-[10px] opacity-70 block text-right mt-1">{{ $msg->created_at->format('H:i') }}</span>
                </div>
            </div>
        @empty
            <div class="flex justify-center mt-10">
                <span class="bg-white/80 dark:bg-black/50 px-3 py-1 rounded-full text-xs text-gray-500 shadow-sm">
                    Nenhuma mensagem registrada. Envie um "Bom dia" para iniciar.
                </span>
            </div>
        @endforelse
    </div>

    <div class="p-3 bg-gray-100 dark:bg-gray-800 border-t border-gray-300 dark:border-gray-700 flex gap-2 items-center">
        <input wire:model="newMessage" wire:keydown.enter="sendMessage" type="text" placeholder="Digite uma mensagem..."
            class="flex-1 rounded-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-4 py-2 focus:ring-2 focus:ring-green-500 focus:border-transparent outline-none">
        <button wire:click="sendMessage" wire:loading.attr="disabled"
            class="bg-green-500 hover:bg-green-600 text-white rounded-full w-10 h-10 flex items-center justify-center transition-colors">
            <div wire:loading.remove wire:target="sendMessage">
                <x-heroicon-s-paper-airplane class="w-5 h-5 -rotate-45 ml-1" />
            </div>
            <div wire:loading wire:target="sendMessage">
                <x-heroicon-o-arrow-path class="w-5 h-5 animate-spin" />
            </div>
        </button>
    </div>
    @error('newMessage') <span class="text-red-500 text-xs px-3 pb-1 bg-gray-100 dark:bg-gray-800">{{ $message }}</span>
    @enderror

    <script>
        document.addEventListener('livewire:initialized', () => {
            const scrollToBottom = () => {
                const chat = document.getElementById('chat-container');
                if (chat) chat.scrollTop = chat.scrollHeight;
            };
            scrollToBottom();
            Livewire.hook('commit', ({ succeed }) => {
                succeed(() => queueMicrotask(scrollToBottom));
            });
        });
    </script>
</div>