<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div
        x-data="{
            signaturePad: null,
            init() {
                // Carrega a lib se necessário
                if (!window.SignaturePad) {
                    let script = document.createElement('script');
                    script.src = '[https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js](https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js)';
                    script.onload = () => this.initPad();
                    document.head.appendChild(script);
                } else {
                    this.initPad();
                }
            },
            initPad() {
                let canvas = this.$refs.canvas;
                if (!canvas) return;
                
                // Ajuste DPI
                const ratio = Math.max(window.devicePixelRatio || 1, 1);
                canvas.width = canvas.offsetWidth * ratio;
                canvas.height = canvas.offsetHeight * ratio;
                canvas.getContext('2d').scale(ratio, ratio);
                
                this.signaturePad = new SignaturePad(canvas, { backgroundColor: 'rgb(255, 255, 255)' });
                
                // Se já tiver valor (edição), carrega
                let existing = $wire.get('{{ $getStatePath() }}');
                if (existing) this.signaturePad.fromDataURL(existing);
                
                // Salva no Livewire ao terminar traço
                this.signaturePad.addEventListener('endStroke', () => {
                    $wire.set('{{ $getStatePath() }}', this.signaturePad.toDataURL());
                });
            },
            clear() {
                this.signaturePad?.clear();
                $wire.set('{{ $getStatePath() }}', null);
            }
        }"
        class="border border-gray-300 rounded-lg p-2 bg-white"
    >
        <canvas x-ref="canvas" style="width: 100%; height: 180px; touch-action: none;" class="border rounded cursor-crosshair"></canvas>
        <div class="flex justify-between mt-2">
            <button type="button" @click="clear()" class="text-xs bg-gray-200 hover:bg-gray-300 px-3 py-1 rounded">Limpar</button>
            <span class="text-xs text-gray-500">Assine no quadro acima</span>
        </div>
    </div>
</x-dynamic-component>