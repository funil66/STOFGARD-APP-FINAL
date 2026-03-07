<div x-data="{
        loading: false,
        done: @js($getRecord() && $getRecord()->checkin_at !== null),
        error: null,
        getLocation() {
            this.loading = true;
            this.error = null;
            
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        // Injeta os dados nas variáveis ocultas do formulário do Livewire
                        $wire.set('data.checkin_latitude', position.coords.latitude.toString());
                        $wire.set('data.checkin_longitude', position.coords.longitude.toString());
                        
                        // Formata a data para MySQL (YYYY-MM-DD HH:MM:SS)
                        let now = new Date();
                        let formattedDate = now.toISOString().slice(0, 19).replace('T', ' ');
                        $wire.set('data.checkin_at', formattedDate);
                        
                        this.done = true;
                        this.loading = false;
                    },
                    (error) => {
                        this.error = 'Erro ao aceder ao GPS. Verifica as permissões do telemóvel.';
                        this.loading = false;
                    },
                    { enableHighAccuracy: true, timeout: 10000 }
                );
            } else {
                this.error = 'O teu navegador não suporta GPS.';
                this.loading = false;
            }
        }
    }"
    class="flex flex-col gap-3 p-4 bg-slate-50 dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm">
    <div class="flex items-center justify-between">
        <div>
            <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200">Localização no Terreno</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400">Regista a tua chegada ao local do serviço.</p>
        </div>
        <div class="p-2 bg-indigo-100 text-indigo-600 rounded-lg dark:bg-indigo-500/20 dark:text-indigo-400">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
            </svg>
        </div>
    </div>

    <template x-if="!done && !loading">
        <button type="button" x-on:click="getLocation()"
            class="mt-2 w-full sm:w-auto bg-indigo-600 text-white px-5 py-2.5 rounded-xl font-bold hover:bg-indigo-700 shadow-md inline-flex items-center justify-center gap-2 transition">
            Iniciar Serviço (Check-in GPS)
        </button>
    </template>

    <template x-if="loading">
        <div class="mt-2 inline-flex items-center gap-2 text-indigo-600 font-medium">
            <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor"
                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                </path>
            </svg>
            A calibrar satélites...
        </div>
    </template>

    <template x-if="done">
        <div
            class="mt-2 text-sm text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-500/10 p-3 rounded-xl border border-emerald-200 dark:border-emerald-500/20 font-medium flex items-start gap-2">
            <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <div>
                Check-in validado com sucesso!
                <div class="text-xs text-emerald-600 dark:text-emerald-500 mt-1"
                    x-text="`Guarda a Ordem de Serviço para fixar a coordenada no servidor.`"></div>
            </div>
        </div>
    </template>

    <template x-if="error">
        <div class="text-rose-500 text-sm font-medium mt-2" x-text="error"></div>
    </template>
</div>