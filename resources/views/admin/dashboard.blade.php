<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Admin Dashboard</title>
    @if (file_exists(public_path('build/manifest.json')))
        @vite(['resources/css/app.css'])
    @else
        <link rel="stylesheet" href="{{ asset('css/app.css') }}">
        {{-- Fallback local UI tweaks (ensures dashboard styling even when app.css is missing) --}}
        @if (file_exists(public_path('css/stofgard.css')))
            <link rel="stylesheet" href="{{ asset('css/stofgard.css') }}">
        @endif
    @endif
</head>
<body data-page="dashboard" class="bg-gray-50 min-h-screen p-6 fi-page-dashboard">
    <main class="max-w-5xl mx-auto">
        <section class="mb-6">
            {{-- Weather widget --}}
            @include('filament.widgets.dashboard-weather-widget', $weather ?? [])
        </section>

        <section class="mb-6">
            {{-- Shortcuts widget --}}
            @include('filament.widgets.dashboard-shortcuts', $shortcuts ?? [])
        </section>

        <section>
            {{-- Finance widget --}}
            @include('filament.widgets.financeiro-summary', $finance ?? [])
        </section>
    </main>
</body>
</html>