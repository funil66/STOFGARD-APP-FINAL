<div class="min-h-screen bg-gradient-to-br from-emerald-50 to-teal-100 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl max-w-lg w-full p-8">
        {{-- Token inválido --}}
        @if($invalida)
            <div class="text-center">
                <div class="text-6xl mb-4">🔗</div>
                <h2 class="text-xl font-bold text-gray-800">Link inválido</h2>
                <p class="text-gray-500 mt-2">Este link de avaliação não é válido ou já expirou.</p>
            </div>

        {{-- Já respondida --}}
        @elseif($enviada)
            <div class="text-center">
                <div class="text-6xl mb-4">🎉</div>
                <h2 class="text-xl font-bold text-gray-800">Obrigado pela sua avaliação!</h2>
                <div class="mt-4 flex justify-center gap-1">
                    @for($i = 0; $i <= 10; $i++)
                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-full text-sm font-bold
                            {{ $nota === $i ? 'bg-emerald-500 text-white ring-2 ring-emerald-300' : 'bg-gray-100 text-gray-400' }}">
                            {{ $i }}
                        </span>
                    @endfor
                </div>
                @if($comentario)
                    <p class="mt-4 text-gray-600 italic">"{{ $comentario }}"</p>
                @endif
                <p class="text-gray-400 text-sm mt-4">Sua opinião nos ajuda a melhorar constantemente.</p>
            </div>

        {{-- Formulário de avaliação --}}
        @else
            <div class="text-center mb-6">
                <h2 class="text-2xl font-bold text-gray-800">Como foi sua experiência?</h2>
                <p class="text-gray-500 mt-1">
                    OS: <strong>{{ $avaliacao->ordemServico?->numero_os }}</strong>
                </p>
            </div>

            <form wire:submit="enviar">
                {{-- Seletor de nota NPS --}}
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-3">
                        De 0 a 10, qual a probabilidade de recomendar nosso serviço?
                    </label>
                    <div class="flex justify-center gap-1.5">
                        @for($i = 0; $i <= 10; $i++)
                            <button type="button"
                                wire:click="selecionarNota({{ $i }})"
                                class="w-10 h-10 rounded-full text-sm font-bold transition-all duration-200
                                    {{ $nota === $i
                                        ? ($i >= 9 ? 'bg-emerald-500 text-white ring-2 ring-emerald-300 scale-110'
                                            : ($i >= 7 ? 'bg-amber-500 text-white ring-2 ring-amber-300 scale-110'
                                                : 'bg-red-500 text-white ring-2 ring-red-300 scale-110'))
                                        : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                                {{ $i }}
                            </button>
                        @endfor
                    </div>
                    <div class="flex justify-between mt-2 text-xs text-gray-400 px-1">
                        <span>Pouco provável</span>
                        <span>Muito provável</span>
                    </div>
                </div>

                {{-- Comentário --}}
                <div class="mb-6">
                    <label for="comentario" class="block text-sm font-medium text-gray-700 mb-1">
                        Comentário (opcional)
                    </label>
                    <textarea
                        wire:model="comentario"
                        id="comentario"
                        rows="3"
                        maxlength="2000"
                        placeholder="Conte-nos mais sobre sua experiência..."
                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                    ></textarea>
                </div>

                {{-- Submit --}}
                <button type="submit"
                    @if(is_null($nota)) disabled @endif
                    class="w-full py-3 px-4 rounded-lg font-semibold text-white transition-all
                        {{ is_null($nota)
                            ? 'bg-gray-300 cursor-not-allowed'
                            : 'bg-emerald-600 hover:bg-emerald-700 shadow-lg hover:shadow-xl' }}">
                    <span wire:loading.remove>Enviar Avaliação</span>
                    <span wire:loading>Enviando...</span>
                </button>
            </form>
        @endif
    </div>
</div>
