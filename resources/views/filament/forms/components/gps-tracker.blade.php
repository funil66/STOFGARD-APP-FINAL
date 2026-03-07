<div x-data="{
    state: $wire.$entangle('{{ $getStatePath() }}'),
    lat: $wire.$entangle('data.checkin_lat'),
    lng: $wire.$entangle('data.checkin_lng'),
    status: 'Obtendo localização...',
    error: null,
    
    init() {
        if (!navigator.geolocation) {
            this.error = 'Geolocalização não suportada pelo navegador.';
            this.status = '';
            return;
        }

        navigator.geolocation.getCurrentPosition(
            (position) => {
                this.lat = position.coords.latitude;
                this.lng = position.coords.longitude;
                this.status = '📍 Localização capturada com sucesso!';
                this.error = null;
            },
            (error) => {
                this.error = 'Erro ao obter localização: ' + error.message;
                this.status = '';
            },
            { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
        );
    }
}">
    <div class="p-4 bg-gray-50 border rounded-lg dark:bg-gray-800 dark:border-gray-700">
        <div x-show="status" class="flex items-center text-sm font-medium text-green-600 dark:text-green-400">
            <x-heroicon-o-check-circle class="w-5 h-5 mr-2" />
            <span x-text="status"></span>
        </div>

        <div x-show="error" class="flex items-center text-sm font-medium text-red-600 dark:text-red-400">
            <x-heroicon-o-x-circle class="w-5 h-5 mr-2" />
            <span x-text="error"></span>
        </div>

        <div class="mt-2 text-xs text-gray-500">
            Lat: <span x-text="lat || '...'"></span> | Lng: <span x-text="lng || '...'"></span>
        </div>
    </div>
</div>