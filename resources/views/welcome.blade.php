<!DOCTYPE html>
<html lang="pt-BR" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AUTONOMIA ILIMITADA - Gestão para Autônomos</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .gradient-text {
            background: linear-gradient(to right, #10b981, #3b82f6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-900 antialiased">
    <nav class="fixed w-full z-50 top-0 bg-white/90 backdrop-blur-md border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="h-20 flex items-center justify-between">
                <a href="/" class="text-2xl font-extrabold tracking-tighter text-slate-900">
                    AUTONOMIA <span class="text-emerald-500">ILIMITADA</span>
                </a>

                <div class="hidden md:flex items-center gap-7">
                    <a href="#funcionalidades" class="text-slate-600 hover:text-emerald-500 font-medium transition">Funcionalidades</a>
                    <a href="#precos" class="text-slate-600 hover:text-emerald-500 font-medium transition">Planos</a>
                    <a href="{{ route('cliente.portal') }}" class="text-slate-600 hover:text-emerald-500 font-medium transition">Área do Cliente</a>
                    <a href="{{ route('empresa.login') }}" class="text-slate-600 hover:text-emerald-500 font-medium transition">Login Empresa</a>
                    <a href="{{ route('registro.empresa') }}" class="bg-emerald-500 hover:bg-emerald-600 text-white px-5 py-2.5 rounded-lg font-semibold transition shadow-lg shadow-emerald-500/30">Testar Grátis</a>
                </div>

                <details class="md:hidden relative">
                    <summary class="list-none cursor-pointer inline-flex items-center justify-center w-10 h-10 rounded-lg border border-slate-300 bg-white text-slate-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </summary>
                    <div class="absolute right-0 mt-2 w-64 rounded-xl border border-slate-200 bg-white shadow-xl p-3 space-y-1">
                        <a href="#funcionalidades" class="block px-3 py-2 rounded-lg text-sm text-slate-700 hover:bg-slate-100">Funcionalidades</a>
                        <a href="#precos" class="block px-3 py-2 rounded-lg text-sm text-slate-700 hover:bg-slate-100">Planos</a>
                        <a href="{{ route('cliente.portal') }}" class="block px-3 py-2 rounded-lg text-sm text-slate-700 hover:bg-slate-100">Área do Cliente</a>
                        <a href="{{ route('empresa.login') }}" class="block px-3 py-2 rounded-lg text-sm text-slate-700 hover:bg-slate-100">Login Empresa</a>
                        <a href="{{ route('registro.empresa') }}" class="block px-3 py-2 rounded-lg text-sm text-white bg-emerald-600 hover:bg-emerald-700">Criar empresa</a>
                    </div>
                </details>
            </div>
        </div>
    </nav>

    <section class="pt-32 pb-16 lg:pt-44 lg:pb-24 overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-4xl mx-auto">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-100 text-emerald-700 font-semibold text-sm mb-6">
                    <span class="flex h-2 w-2 rounded-full bg-emerald-500"></span>
                    Controle financeiro e operacional em um só lugar
                </div>
                <h1 class="text-4xl sm:text-5xl lg:text-7xl font-extrabold tracking-tight mb-8">
                    Seu negócio no controle total,<br>
                    <span class="gradient-text">sem planilha e sem caos.</span>
                </h1>
                <p class="text-lg sm:text-xl text-slate-600 mb-10 max-w-3xl mx-auto">
                    Cadastros, ordens de serviço, cobrança por PIX/Boleto/Cartão e portal do cliente. Tudo preparado para empresas de serviço que precisam escalar com segurança.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="{{ route('registro.empresa') }}" class="bg-slate-900 hover:bg-slate-800 text-white px-8 py-4 rounded-xl font-bold text-lg transition shadow-xl flex items-center justify-center gap-2">
                        Começar meus {{ env('TRIAL_DAYS', 14) }} dias grátis
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                        </svg>
                    </a>
                    <a href="{{ route('empresa.login') }}" class="bg-white hover:bg-slate-100 text-slate-900 border border-slate-200 px-8 py-4 rounded-xl font-bold text-lg transition">
                        Já tenho conta
                    </a>
                </div>
            </div>

            <div class="mt-16 relative mx-auto max-w-5xl">
                <div class="absolute -inset-1 bg-gradient-to-r from-emerald-500 to-blue-500 rounded-2xl blur opacity-30"></div>
                <img src="/images/dashboard-reference.png" alt="Tela do sistema AUTONOMIA ILIMITADA"
                    class="relative rounded-xl shadow-2xl border border-slate-200 w-full bg-slate-900 min-h-[320px] md:min-h-[420px] object-cover object-top"
                    onerror="this.src='https://placehold.co/1200x600/1e293b/ffffff?text=Dashboard+AUTONOMIA'">
            </div>
        </div>
    </section>

    <section id="funcionalidades" class="py-20 bg-white border-t border-slate-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-bold mb-4">Um front-office completo para sua operação</h2>
                <p class="text-lg text-slate-600">Projetado para vender mais, cobrar melhor e reduzir retrabalho.</p>
            </div>
            <div class="grid md:grid-cols-3 gap-8">
                <article class="p-7 rounded-2xl bg-slate-50 border border-slate-100 hover:shadow-lg transition">
                    <div class="w-14 h-14 bg-emerald-100 text-emerald-600 rounded-xl flex items-center justify-center mb-5">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <h3 class="text-xl font-bold mb-2">Cobrança e assinatura</h3>
                    <p class="text-slate-600">Checkout integrado com Cartão, PIX, Boleto e PayPal para ativar clientes sem fricção.</p>
                </article>
                <article class="p-7 rounded-2xl bg-slate-50 border border-slate-100 hover:shadow-lg transition">
                    <div class="w-14 h-14 bg-blue-100 text-blue-600 rounded-xl flex items-center justify-center mb-5">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                    </div>
                    <h3 class="text-xl font-bold mb-2">Fluxos seguros</h3>
                    <p class="text-slate-600">Verificação por código de e-mail no cadastro e recuperação de senha empresarial sem suporte manual.</p>
                </article>
                <article class="p-7 rounded-2xl bg-slate-50 border border-slate-100 hover:shadow-lg transition">
                    <div class="w-14 h-14 bg-purple-100 text-purple-600 rounded-xl flex items-center justify-center mb-5">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2zm11-8h.01M8 12h4"></path></svg>
                    </div>
                    <h3 class="text-xl font-bold mb-2">Subdomínio automático</h3>
                    <p class="text-slate-600">Cada empresa entra com seu próprio endereço no padrão empresa.autonomia.app.br.</p>
                </article>
            </div>
        </div>
    </section>

    <section id="precos" class="py-20 bg-slate-900 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-14">
                <h2 class="text-3xl md:text-4xl font-bold mb-4">Planos para cada estágio da operação</h2>
                <p class="text-lg text-slate-400">Comece rápido e escale quando quiser.</p>
            </div>

            <div class="grid md:grid-cols-3 gap-8 max-w-6xl mx-auto items-stretch">
                <div class="bg-slate-800 rounded-2xl p-8 border border-slate-700 flex flex-col">
                    <h3 class="text-2xl font-semibold mb-2">START</h3>
                    <p class="text-slate-400 text-sm mb-6">Ideal para operação enxuta.</p>
                    <div class="mb-8"><span class="text-4xl font-bold">R$ {{ env('PLAN_START_PRICE', 49) }}</span><span class="text-slate-400">/mês</span></div>
                    <ul class="space-y-3 mb-8 text-sm text-slate-100 flex-1">
                        <li>• Cadastro de clientes</li>
                        <li>• Até {{ env('PLAN_START_OS_LIMIT', 30) }} OS/mês</li>
                        <li>• Fluxo comercial completo</li>
                    </ul>
                    <a href="{{ route('registro.empresa', ['plano' => 'start']) }}" class="block w-full py-3 px-4 bg-slate-700 hover:bg-slate-600 text-center font-bold rounded-lg transition">Começar no START</a>
                </div>

                <div class="bg-gradient-to-b from-emerald-500 to-emerald-700 rounded-2xl p-8 border border-emerald-400 shadow-2xl shadow-emerald-900/40 relative flex flex-col">
                    <div class="absolute top-0 left-1/2 -translate-x-1/2 -translate-y-1/2 bg-slate-900 text-white px-4 py-1 rounded-full text-xs font-bold tracking-wider">MAIS POPULAR</div>
                    <h3 class="text-2xl font-semibold mb-2">PRO</h3>
                    <p class="text-emerald-100 text-sm mb-6">Para empresas em crescimento.</p>
                    <div class="mb-8"><span class="text-5xl font-bold">R$ {{ env('PLAN_PRO_PRICE', 97) }}</span><span class="text-emerald-200">/mês</span></div>
                    <ul class="space-y-3 mb-8 text-sm text-white flex-1">
                        <li>• OS ilimitadas</li>
                        <li>• Assinatura eletrônica</li>
                        <li>• Automação de cobrança</li>
                    </ul>
                    <a href="{{ route('registro.empresa', ['plano' => 'pro']) }}" class="block w-full py-4 px-4 bg-slate-900 hover:bg-slate-800 text-emerald-300 text-center font-extrabold rounded-lg transition shadow-lg">Assinar plano PRO</a>
                </div>

                <div class="bg-slate-800 rounded-2xl p-8 border border-slate-700 flex flex-col">
                    <h3 class="text-2xl font-semibold mb-2">ELITE</h3>
                    <p class="text-slate-400 text-sm mb-6">Para operação avançada com equipe.</p>
                    <div class="mb-8"><span class="text-4xl font-bold">R$ {{ env('PLAN_ELITE_PRICE', 197) }}</span><span class="text-slate-400">/mês</span></div>
                    <ul class="space-y-3 mb-8 text-sm text-slate-100 flex-1">
                        <li>• Tudo do PRO</li>
                        <li>• Multiusuários</li>
                        <li>• Portal white-label</li>
                    </ul>
                    <a href="{{ route('registro.empresa', ['plano' => 'elite']) }}" class="block w-full py-3 px-4 bg-slate-700 hover:bg-slate-600 text-center font-bold rounded-lg transition">Ir para ELITE</a>
                </div>
            </div>

            <div class="mt-8 text-center">
                <a href="https://wa.me/{{ env('COMMERCIAL_WHATSAPP', '5511999999999') }}?text=Quero%20falar%20com%20o%20comercial" class="text-slate-300 hover:text-white underline font-medium" target="_blank" rel="noopener">
                    Precisa de ajuda para decidir? Falar com o comercial
                </a>
            </div>
        </div>
    </section>

    <footer class="bg-slate-950 text-slate-400 py-10 border-t border-slate-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row justify-between items-center gap-4">
            <div class="text-center md:text-left">
                <span class="text-xl font-extrabold tracking-tighter text-white">AUTONOMIA <span class="text-emerald-500">ILIMITADA</span></span>
                <p class="text-sm mt-1">Tecnologia para serviços com escala e controle.</p>
            </div>
            <div class="flex flex-wrap justify-center gap-5 text-sm">
                <a href="#" class="hover:text-white transition">Termos de Uso</a>
                <a href="#" class="hover:text-white transition">Política de Privacidade</a>
                <a href="{{ route('empresa.login') }}" class="hover:text-white transition">Login</a>
            </div>
        </div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-6 text-sm text-center md:text-left">
            &copy; {{ date('Y') }} AUTONOMIA ILIMITADA. Todos os direitos reservados.
        </div>
    </footer>
</body>
</html>