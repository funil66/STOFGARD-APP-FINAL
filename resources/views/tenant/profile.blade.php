<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $config->empresa_nome ?? $tenant->name }} - Link na Bio</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Tailwind CSS (via CDN for standalone view) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        primary: {
                            50: '#f0f9ff',
                            100: '#e0f2fe',
                            500: '#0ea5e9',
                            600: '#0284c7',
                            700: '#0369a1',
                            900: '#0c4a6e',
                        }
                    }
                }
            }
        }
    </script>
</head>

<body
    class="antialiased bg-slate-50 text-slate-900 font-sans selection:bg-primary-500 selection:text-white min-h-screen flex flex-col items-center py-10 px-4">

    <!-- Container Principal do Link na Bio -->
    <div class="w-full max-w-md mx-auto flex flex-col gap-6">

        <!-- Header / Perfil -->
        <div
            class="flex flex-col items-center text-center gap-4 bg-white p-8 rounded-3xl shadow-sm border border-slate-100">
            <!-- Logo ou Avatar Padrão -->
            <div
                class="w-24 h-24 rounded-full overflow-hidden bg-slate-100 flex items-center justify-center border-4 border-white shadow-lg">
                @if($config && $config->empresa_logo)
                    <img src="{{ Storage::url($config->empresa_logo) }}" alt="Logo {{ $config->empresa_nome }}"
                        class="w-full h-full object-cover">
                @else
                    <svg class="w-12 h-12 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                @endif
            </div>

            <div class="space-y-1">
                <h1 class="text-2xl font-bold text-slate-800 tracking-tight">
                    {{ $config->empresa_nome ?? $tenant->name }}
                </h1>
                <p class="text-sm text-slate-500 font-medium">
                    {{ $config->descricao ?? 'Especialista em serviços de alta qualidade.' }}
                </p>
            </div>
        </div>

        <!-- Links / CTA Secundários -->
        <div class="flex flex-col gap-3">
            @if($config && $config->empresa_telefone)
                @php
                    $telefoneStr = preg_replace('/[^0-9]/', '', $config->empresa_telefone);
                    $whatsappMsg = "Olá! Gostaria de saber mais sobre os serviços de " . ($config->empresa_nome ?? $tenant->name) . ". Vim pelo seu Link na Bio.";
                    $whatsappUrl = "https://wa.me/55{$telefoneStr}?text=" . urlencode($whatsappMsg);
                @endphp
                <a href="{{ $whatsappUrl }}" target="_blank"
                    class="w-full group relative flex items-center justify-center gap-3 bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-600 hover:to-emerald-700 text-white p-4 rounded-2xl font-semibold shadow-md hover:shadow-lg transition-all transform hover:-translate-y-0.5">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.878-.788-1.46-1.761-1.633-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 0 0-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z" />
                    </svg>
                    Falar no WhatsApp
                </a>
            @endif

            <a href="{{ route('agendamento.publico', ['slug' => $slug]) }}"
                class="w-full group relative flex items-center justify-center gap-3 bg-white hover:bg-slate-50 text-slate-800 p-4 rounded-2xl font-semibold shadow-sm border border-slate-200 hover:border-slate-300 transition-all transform hover:-translate-y-0.5">
                <svg class="w-6 h-6 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                Agendamento Online
            </a>
        </div>

        <!-- Grade de Serviços -->
        @if($servicos->count() > 0)
            <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100 flex flex-col gap-4">
                <h2 class="text-sm font-bold text-slate-400 uppercase tracking-wider mb-2">Nossos Serviços</h2>

                <div class="grid grid-cols-2 gap-3">
                    @foreach($servicos as $servico)
                        <div
                            class="flex flex-col items-center justify-center p-4 bg-slate-50 rounded-2xl border border-slate-100 text-center gap-2 hover:bg-primary-50 hover:border-primary-100 transition-colors cursor-default">
                            <div
                                class="w-10 h-10 rounded-full bg-white shadow-sm flex items-center justify-center text-primary-500">
                                <!-- TODO: usar ícone correto se existir no array/DB, senão fallback -->
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                            <span class="text-xs font-semibold text-slate-700">{{ $servico['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Rodapé Viral Autonomia Ilimitada -->
        <div class="mt-8 text-center pb-8">
            <a href="https://autonomiailimitada.com.br" target="_blank"
                class="inline-flex items-center gap-2 text-xs font-medium text-slate-400 hover:text-primary-600 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
                Desenvolvido por Autonomia Ilimitada
            </a>
        </div>

    </div>

</body>

</html>