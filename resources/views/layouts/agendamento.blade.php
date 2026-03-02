<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agendar Serviço — {{ $config?->empresa_nome ?? $tenant->name }}</title>
    <meta name="description"
        content="Agende online com {{ $config?->empresa_nome ?? $tenant->name }}. Rápido, fácil e seguro.">

    {{-- Google Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    {{-- Tailwind via CDN para a página pública --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: '{{ $config?->cor_primaria_cliente ?? "#6366f1" }}',
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>

    {{-- CSS dinâmico com cor da empresa --}}
    <style>
        :root {
            --cor-brand:
                {{ $config?->cor_primaria_cliente ?? '#6366f1' }}
            ;
        }

        .bg-brand {
            background-color: var(--cor-brand);
        }

        .text-brand {
            color: var(--cor-brand);
        }

        .border-brand {
            border-color: var(--cor-brand);
        }

        .ring-brand {
            --tw-ring-color: var(--cor-brand);
        }

        /* Animações */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(8px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in {
            animation: fadeIn 0.3s ease-out;
        }

        /* Scrollbar estilizada */
        ::-webkit-scrollbar {
            width: 6px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f5f9;
        }

        ::-webkit-scrollbar-thumb {
            background: var(--cor-brand);
            border-radius: 3px;
        }
    </style>

    @livewireStyles
</head>

<body class="bg-gray-50 min-h-screen font-sans">

    {{-- Header --}}
    <header class="bg-white shadow-sm border-b border-gray-100">
        <div class="max-w-2xl mx-auto px-4 py-4 flex items-center gap-3">
            @if($config?->logo_cliente_path)
                <img src="{{ Storage::url($config->logo_cliente_path) }}" alt="{{ $config?->empresa_nome }}"
                    class="h-10 w-auto object-contain">
            @else
                <div class="h-10 w-10 rounded-xl bg-brand flex items-center justify-center">
                    <span class="text-white font-bold text-lg">
                        {{ strtoupper(substr($config?->empresa_nome ?? $tenant->name, 0, 1)) }}
                    </span>
                </div>
            @endif

            <div>
                <h1 class="font-semibold text-gray-900 text-sm leading-tight">
                    {{ $config?->empresa_nome ?? $tenant->name }}
                </h1>
                <p class="text-xs text-gray-500">Agendamento Online</p>
            </div>
        </div>
    </header>

    {{-- Conteúdo principal --}}
    <main class="max-w-2xl mx-auto px-4 py-8">
        {{ $slot }}
    </main>

    {{-- Footer --}}
    <footer class="text-center py-8 text-xs text-gray-400">
        <p>Agendamento seguro powered by <span class="font-medium text-gray-500">Autonomia Ilimitada</span></p>
    </footer>

    @livewireScripts

    {{-- Auto-refresh do Livewire para verificar pagamento PIX --}}
    <script>
        document.addEventListener('livewire:initialized', () => {
            // Polling a cada 5s para verificar pagamento PIX quando no step 4
            setInterval(() => {
                const component = Livewire.getByName('agendamento-publico');
                if (component && component.getData().step === 4) {
                    component.call('verificarPagamento');
                }
            }, 5000);

            // Toast de cópia do PIX
            document.addEventListener('click', (e) => {
                const btn = e.target.closest('[data-copy]');
                if (!btn) return;
                const texto = btn.dataset.copy;
                navigator.clipboard.writeText(texto).then(() => {
                    const original = btn.textContent;
                    btn.textContent = '✅ Copiado!';
                    setTimeout(() => btn.textContent = original, 2000);
                });
            });
        });
    </script>
</body>

</html>