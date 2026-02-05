<div style="border:1px solid #e5e7eb;border-radius:8px;padding:8px;">
    @php $id = 'signature-canvas-'.uniqid(); @endphp
    <canvas id="{{ $id }}" width="800" height="240" style="width:100%;height:min(120px, 25vh);background:#fff;border-radius:4px;display:block;"></canvas>
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

        const signaturePad = new SignaturePad(canvas, {
            backgroundColor: 'rgb(255, 255, 255)'
        });

        document.querySelector(`[data-canvas-id="{{ $id }}"]`).addEventListener('click', () => {
            signaturePad.clear();
        });
    } catch (e) {
        console.error('Erro ao inicializar o SignaturePad:', e);
    }
})();
</script>
