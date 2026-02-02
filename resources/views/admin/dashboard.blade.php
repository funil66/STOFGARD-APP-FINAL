<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Dashboard Administrativo - StofGard">
    <title>Dashboard | StofGard</title>
    <link href="/css/app.css" rel="stylesheet">
    @livewireStyles
</head>
<body class="bg-gray-50 min-h-screen" data-page="dashboard">
    {{-- Header do Dashboard --}}
    <header class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-5xl mx-auto px-6 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Dashboard</h1>
                    <p class="text-sm text-gray-500">Visão geral do sistema</p>
                </div>
                <div class="flex items-center space-x-4">
                    @auth
                        <span class="text-sm text-gray-600">{{ auth()->user()->name }}</span>
                        <a href="{{ url('/admin') }}" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition-colors">
                            Painel Admin
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </header>

    {{-- Conteúdo Principal --}}
    <main class="max-w-5xl mx-auto p-6">
        {{-- Widget de Clima --}}
        <section class="mb-6">
            <div class="bg-white rounded-lg shadow-sm p-4">
                @include('filament.widgets.dashboard-weather-widget', $weather ?? [])
            </div>
        </section>

        {{-- Widget de Atalhos --}}
        <section class="mb-6">
            <div class="bg-white rounded-lg shadow-sm p-4">
                @include('filament.widgets.dashboard-shortcuts', $shortcuts ?? [])
            </div>
        </section>

        {{-- Widget Financeiro --}}
        <section class="mb-6">
            <div class="bg-white rounded-lg shadow-sm p-4">
                @include('filament.widgets.financeiro-summary', $finance ?? [])
            </div>
        </section>

        {{-- Mensagens de Feedback --}}
        @if(session('success'))
            <div class="mt-4 p-4 bg-green-50 border border-green-200 rounded-lg">
                <p class="text-green-700">{{ session('success') }}</p>
            </div>
        @endif

        @if(session('error'))
            <div class="mt-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                <p class="text-red-700">{{ session('error') }}</p>
            </div>
        @endif
    </main>

    {{-- Footer --}}
    <footer class="bg-white border-t border-gray-200 mt-auto">
        <div class="max-w-5xl mx-auto px-6 py-4">
            <p class="text-sm text-gray-500 text-center">
                © {{ date('Y') }} StofGard. Todos os direitos reservados.
            </p>
        </div>
    </footer>

    @livewireScripts
</body>
</html>