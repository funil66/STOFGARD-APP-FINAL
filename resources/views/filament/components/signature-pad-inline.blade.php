<div style="border:1px solid #e5e7eb;border-radius:8px;padding:8px;">
    @php $id = 'signature-canvas-'.uniqid(); @endphp
    <canvas id="{{ $id }}" width="800" height="240" style="width:100%;height:120px;background:#fff;border-radius:4px;display:block;"></canvas>
    <div style="display:flex;justify-content:space-between;margin-top:6px;">
        <button type="button" class="px-2 py-1 signature-clear-btn" data-canvas-id="{{ $id }}" style="background:#fff;border:1px solid #e5e7eb;border-radius:6px;">Limpar</button>
        <small style="color:#666;font-size:11px;">Use mouse ou toque para assinar.</small>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
<script>
(function(){
    try {
        const canvas = document.getElementById('{{ $id }}');
        if (!canvas) return;

        // Ajuste para DPR
        const ratio = Math.max(window.devicePixelRatio || 1, 1);
        canvas.width = canvas.offsetWidth * ratio;
        canvas.height = canvas.offsetHeight * ratio;
        canvas.getContext('2d').scale(ratio, ratio);

        const pad = new SignaturePad(canvas, { backgroundColor: 'rgb(255,255,255)', penColor: 'rgb(0,0,0)' });

        // Botão limpar (delegado)
        const limparBtn = document.querySelector('button.signature-clear-btn[data-canvas-id="{{ $id }}"]');
        if (limparBtn) {
            limparBtn.addEventListener('click', function(){
                pad.clear();
                const input = canvas.closest('form')?.querySelector('input[name="assinatura_base64"]');
                if (input) input.value = '';
            });
        }

        // Ao submeter o formulário, grava o dataURL no hidden input
        const form = canvas.closest('form');
        if (form) {
            form.addEventListener('submit', function(){
                const input = form.querySelector('input[name="assinatura_base64"]');
                if (input) {
                    if (pad.isEmpty()) {
                        input.value = '';
                    } else {
                        input.value = pad.toDataURL();
                    }
                }
            });
        }
    } catch (e) {
        console.error('SignaturePad init failed', e);
    }
})();
</script>
