<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.243-4.243a8 8 0 1111.314 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                Radar Tático - Serviços do Dia
            </h2>
        </div>

        <!-- CSS do Leaflet importado diretamente -->
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>

        <div 
            x-data="mapaRadar({ locais: {{ json_encode($locais) }} })"
            x-init="initMap()"
            class="relative w-full rounded-xl overflow-hidden border border-gray-200 shadow-inner"
            style="height: 400px; z-index: 10;"
        >
            <div id="radar-map" class="w-full h-full absolute inset-0"></div>
        </div>
    </x-filament::section>

    <!-- JS do Leaflet importado no final -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('mapaRadar', (config) => ({
                locais: config.locais,
                map: null,
                initMap() {
                    // Previne duplicação na navegação do Livewire
                    if (this.map !== null) {
                        this.map.remove();
                    }

                    // Aguarda a injeção do objeto L (Leaflet) no window
                    let attempts = 0;
                    const checkLeaflet = setInterval(() => {
                        if (typeof window.L !== 'undefined') {
                            clearInterval(checkLeaflet);
                            this.buildMap();
                        }
                        attempts++;
                        if (attempts > 50) clearInterval(checkLeaflet); // Fallback timeout 5s
                    }, 100);
                },
                buildMap() {
                    // Centro inicial (Fallback para Ribeirão Preto ou Brasil se vazio)
                    let centerLat = this.locais.length > 0 ? this.locais[0].lat : -21.1704;
                    let centerLng = this.locais.length > 0 ? this.locais[0].lng : -47.8103;

                    this.map = L.map('radar-map').setView([centerLat, centerLng], 12);

                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19,
                        attribution: '© OpenStreetMap'
                    }).addTo(this.map);

                    let markers = [];

                    this.locais.forEach(local => {
                        let marker = L.marker([local.lat, local.lng]).addTo(this.map);
                        
                        marker.bindPopup(`
                            <div class="font-sans min-w-[200px]">
                                <strong class="block text-gray-900 text-sm mb-1">OS #${local.id}</strong>
                                <span class="block text-xs text-gray-800 font-semibold mb-1">${local.cliente_nome}</span>
                                <span class="block text-xs text-gray-600">${local.servico_descricao}</span>
                            </div>
                        `);

                        markers.push(marker);
                    });

                    // Se tiver marcadores, ajusta o zoom para focar em todos
                    if (markers.length > 0) {
                        var group = new L.featureGroup(markers);
                        this.map.fitBounds(group.getBounds().pad(0.1));
                    }
                }
            }));
        });
    </script>
</x-filament-widgets::widget>
