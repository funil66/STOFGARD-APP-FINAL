<div
    x-data="{
        signaturePad: null,
        initPad() {
            // Wait for SignaturePad to be loaded
            if (typeof SignaturePad === 'undefined') {
                setTimeout(() => this.initPad(), 100);
                return;
            }

            this.signaturePad = new SignaturePad(this.$refs.canvas, {
                backgroundColor: 'rgb(255, 255, 255)',
                penColor: 'rgb(0, 0, 0)',
                onEnd: () => {
                    this.save();
                }
            });

            // Evitar que o gesto de rolagem capture os eventos de toque
            try {
                this.$refs.canvas.style.touchAction = 'none';
                this.$refs.canvas.addEventListener('touchstart', (e) => e.preventDefault(), { passive: false });
                this.$refs.canvas.addEventListener('touchmove', (e) => e.preventDefault(), { passive: false });
            } catch (e) {
                // alguns ambientes podem não aceitar opções de listener
            }

            // Garantir que o conteúdo do canvas seja salvo quando o formulário/modal for submetido
            try {
                const form = this.$el.closest('form');
                if (form) {
                    // captura o click no botão submit caso o formulário dispare via botão
                    const submit = form.querySelector('button[type="submit"]');
                    if (submit) {
                        submit.addEventListener('click', () => {
                            this.save();
                        }, true);
                    }

                    // Também captura o evento submit do form
                    form.addEventListener('submit', () => {
                        this.save();
                    });
                }
            } catch (e) {
                // não fatal
            }

            this.resizeCanvas();
        },
        resizeCanvas() {
            if (!this.signaturePad) return;
            const canvas = this.$refs.canvas;
            const ratio = Math.max(window.devicePixelRatio || 1, 1);
            
            // Get current signature
            const signatureData = this.signaturePad.toData();

            canvas.width = canvas.offsetWidth * ratio;
            canvas.height = canvas.offsetHeight * ratio;
            canvas.getContext('2d').scale(ratio, ratio);

            // Restore signature
            this.signaturePad.clear();
            this.signaturePad.fromData(signatureData);
        },
        clear() {
            if (this.signaturePad) {
                this.signaturePad.clear();
            }
            $wire.set('{{ $getStatePath() }}', null);
        },
        save() {
            if (this.signaturePad && !this.signaturePad.isEmpty()) {
                $wire.set('{{ $getStatePath() }}', this.signaturePad.toDataURL());
            } else {
                $wire.set('{{ $getStatePath() }}', null);
            }
        }
    }"
    x-init="() => {
        // Delay init to ensure canvas is rendered and has dimensions
        setTimeout(() => initPad(), 250); 
        window.addEventListener('resize', () => resizeCanvas());
    }"
    class="w-full"
    wire:ignore
>
    <div class="border border-gray-300 rounded-lg overflow-hidden" style="touch-action: none;">
        <canvas x-ref="canvas" class="w-full h-48 bg-white" style="touch-action: none; -webkit-user-select: none; -webkit-tap-highlight-color: transparent;"></canvas>
    </div>

    <div class="flex gap-2 mt-2 items-center justify-between">
        <button type="button" @click="clear()" class="px-3 py-1 text-xs text-red-600 border border-red-600 rounded hover:bg-red-50">
            Limpar Assinatura
        </button>
        <span class="text-xs text-gray-400">Assine acima</span>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
</div>
