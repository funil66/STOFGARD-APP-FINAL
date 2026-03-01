<!DOCTYPE html>
<html lang="pt-PT" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>STOFGARD - O Sistema Definitivo para Autónomos</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        .gradient-text {
            background: linear-gradient(to right, #10b981, #3b82f6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
    </style>
</head>

<body class="bg-slate-50 text-slate-900 antialiased">

    <nav
        class="fixed w-full z-50 top-0 transition-all duration-300 bg-white/80 backdrop-blur-md border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20">
                <div class="flex items-center">
                    <span class="text-2xl font-extrabold tracking-tighter text-slate-900">STOF<span
                            class="text-emerald-500">GARD</span></span>
                </div>
                <div class="hidden md:flex space-x-8 items-center">
                    <a href="#funcionalidades"
                        class="text-slate-600 hover:text-emerald-500 font-medium transition">Funcionalidades</a>
                    <a href="#precos" class="text-slate-600 hover:text-emerald-500 font-medium transition">Planos</a>
                    @if (Route::has('filament.admin.auth.login'))
                        @auth
                            <a href="{{ route('filament.admin.pages.dashboard') }}"
                                class="text-slate-600 hover:text-emerald-500 font-medium">Ir para o Painel</a>
                        @else
                            <a href="{{ route('filament.admin.auth.login') }}"
                                class="text-slate-600 hover:text-emerald-500 font-medium transition">Entrar</a>
                            <a href="#precos"
                                class="bg-emerald-500 hover:bg-emerald-600 text-white px-5 py-2.5 rounded-lg font-semibold transition shadow-lg shadow-emerald-500/30">Testar
                                Grátis</a>
                        @endauth
                    @endif
                </div>
            </div>
        </div>
    </nav>

    <section class="pt-32 pb-20 lg:pt-48 lg:pb-32 overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
            <div class="text-center max-w-4xl mx-auto">
                <div
                    class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-100 text-emerald-700 font-semibold text-sm mb-6">
                    <span class="flex h-2 w-2 rounded-full bg-emerald-500"></span>
                    O fim do calote e da desorganização
                </div>
                <h1 class="text-5xl md:text-7xl font-extrabold tracking-tight mb-8">
                    Trabalhas por conta própria?<br>
                    <span class="gradient-text">Gere o teu negócio como um gigante.</span>
                </h1>
                <p class="text-xl text-slate-600 mb-10 max-w-2xl mx-auto">
                    Orçamentos interativos, portal exclusivo para os teus clientes, envio automático por WhatsApp e
                    cobrança dinâmica via PIX. Deixa a burocracia connosco e foca-te em faturar.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="#precos"
                        class="bg-slate-900 hover:bg-slate-800 text-white px-8 py-4 rounded-xl font-bold text-lg transition shadow-xl flex items-center justify-center gap-2">
                        Começar os meus 14 dias Grátis
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                        </svg>
                    </a>
                    <a href="#funcionalidades"
                        class="bg-white hover:bg-slate-50 text-slate-900 border border-slate-200 px-8 py-4 rounded-xl font-bold text-lg transition flex items-center justify-center">
                        Ver como funciona
                    </a>
                </div>
            </div>

            <div class="mt-20 relative mx-auto max-w-5xl">
                <div
                    class="absolute -inset-1 bg-gradient-to-r from-emerald-500 to-blue-500 rounded-2xl blur opacity-30">
                </div>
                <img src="/images/dashboard-reference.png" alt="Ecrã do Sistema STOFGARD"
                    class="relative rounded-xl shadow-2xl border border-slate-200 w-full bg-slate-900 min-h-[400px] object-cover object-top"
                    onerror="this.src='https://placehold.co/1200x600/1e293b/ffffff?text=Ecrã+do+Dashboard+Aqui'">
            </div>
        </div>
    </section>

    <section id="funcionalidades" class="py-24 bg-white border-t border-slate-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-20">
                <h2 class="text-3xl md:text-4xl font-bold mb-4">Um arsenal completo para a tua operação</h2>
                <p class="text-lg text-slate-600">Construído com tecnologia de ponta para que nunca percas tempo (nem
                    dinheiro).</p>
            </div>

            <div class="grid md:grid-cols-3 gap-12">
                <div class="p-8 rounded-2xl bg-slate-50 border border-slate-100 hover:shadow-lg transition">
                    <div
                        class="w-14 h-14 bg-emerald-100 text-emerald-600 rounded-xl flex items-center justify-center mb-6">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                            </path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-3">Máquina de Cobrança Automática</h3>
                    <p class="text-slate-600">Gera faturas e códigos PIX dinâmicos. O sistema envia a cobrança para o
                        WhatsApp do cliente e faz os lembretes de forma automática. O fim da lengalenga de pedir
                        dinheiro.</p>
                </div>

                <div class="p-8 rounded-2xl bg-slate-50 border border-slate-100 hover:shadow-lg transition">
                    <div class="w-14 h-14 bg-blue-100 text-blue-600 rounded-xl flex items-center justify-center mb-6">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z">
                            </path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-3">Blindagem Jurídica (LGPD)</h3>
                    <p class="text-slate-600">Assinaturas eletrónicas em ecrã com captura de IP e Hash SHA-256. A tua
                        Ordem de Serviço ganha validade de título executivo. Dados encriptados e seguros.</p>
                </div>

                <div class="p-8 rounded-2xl bg-slate-50 border border-slate-100 hover:shadow-lg transition">
                    <div
                        class="w-14 h-14 bg-purple-100 text-purple-600 rounded-xl flex items-center justify-center mb-6">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                            </path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-3">Portal White-label para Clientes</h3>
                    <p class="text-slate-600">Oferece um acesso VIP ao teu cliente final. Um portal seguro onde ele
                        aprova orçamentos com um clique, visualiza PDFs e descarrega faturas.</p>
                </div>
            </div>
        </div>
    </section>

    <section id="precos" class="py-24 bg-slate-900 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-bold mb-4">Planos que escalam com o teu negócio</h2>
                <p class="text-lg text-slate-400">Escolhe o nível da tua artilharia e domina o teu mercado.</p>
            </div>

            <div class="grid md:grid-cols-3 gap-8 max-w-6xl mx-auto items-center">

                <div class="bg-slate-800 rounded-2xl p-8 border border-slate-700">
                    <h3 class="text-2xl font-semibold mb-2">START</h3>
                    <p class="text-slate-400 text-sm mb-6">Para quem está a largar as folhas de cálculo.</p>
                    <div class="mb-8">
                        <span class="text-4xl font-bold">R$ 49</span><span class="text-slate-400">/mês</span>
                    </div>
                    <ul class="space-y-4 mb-8">
                        <li class="flex items-center gap-3"><svg class="w-5 h-5 text-emerald-400" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7"></path>
                            </svg> Registo de Clientes Ilimitado</li>
                        <li class="flex items-center gap-3"><svg class="w-5 h-5 text-emerald-400" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7"></path>
                            </svg> Até 30 Orçamentos/mês</li>
                        <li class="flex items-center gap-3"><svg class="w-5 h-5 text-emerald-400" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7"></path>
                            </svg> Controlo de Caixa Base</li>
                        <li class="flex items-center gap-3 text-slate-500"><svg class="w-5 h-5" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"></path>
                            </svg> Automação WhatsApp</li>
                    </ul>
                    <a href="https://wa.me/5511999999999?text=Quero%20assinar%20o%20plano%20START"
                        class="block w-full py-3 px-4 bg-slate-700 hover:bg-slate-600 text-center font-bold rounded-lg transition"
                        target="_blank">Começar no START</a>
                </div>

                <div
                    class="bg-gradient-to-b from-emerald-500 to-emerald-700 rounded-2xl p-8 border border-emerald-400 transform md:-translate-y-4 shadow-2xl shadow-emerald-900/50 relative">
                    <div
                        class="absolute top-0 left-1/2 transform -translate-x-1/2 -translate-y-1/2 bg-slate-900 text-white px-4 py-1 rounded-full text-sm font-bold tracking-wider">
                        MAIS POPULAR
                    </div>
                    <h3 class="text-2xl font-semibold mb-2 text-white">PRO</h3>
                    <p class="text-emerald-100 text-sm mb-6">Para profissionais que exigem tempo e automação.</p>
                    <div class="mb-8">
                        <span class="text-5xl font-bold text-white">R$ 97</span><span
                            class="text-emerald-200">/mês</span>
                    </div>
                    <ul class="space-y-4 mb-8 text-white">
                        <li class="flex items-center gap-3"><svg class="w-5 h-5 text-emerald-200" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7"></path>
                            </svg> Orçamentos e OS Ilimitados</li>
                        <li class="flex items-center gap-3"><svg class="w-5 h-5 text-emerald-200" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7"></path>
                            </svg> Assinatura Digital (Validade Legal)</li>
                        <li class="flex items-center gap-3 font-bold"><svg class="w-5 h-5 text-white" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7"></path>
                            </svg> Emissão Automática de PIX</li>
                        <li class="flex items-center gap-3 font-bold"><svg class="w-5 h-5 text-white" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7"></path>
                            </svg> Automação WhatsApp</li>
                    </ul>
                    <a href="https://wa.me/5511999999999?text=Quero%20assinar%20o%20plano%20PRO"
                        class="block w-full py-4 px-4 bg-slate-900 hover:bg-slate-800 text-emerald-400 text-center font-extrabold rounded-lg transition shadow-lg"
                        target="_blank">Subscrever Plano PRO</a>
                </div>

                <div class="bg-slate-800 rounded-2xl p-8 border border-slate-700">
                    <h3 class="text-2xl font-semibold mb-2">ELITE / ESTÚDIO</h3>
                    <p class="text-slate-400 text-sm mb-6">Para quem tem equipa e precisa do portal de clientes.</p>
                    <div class="mb-8">
                        <span class="text-4xl font-bold">R$ 197</span><span class="text-slate-400">/mês</span>
                    </div>
                    <ul class="space-y-4 mb-8">
                        <li class="flex items-center gap-3"><svg class="w-5 h-5 text-emerald-400" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7"></path>
                            </svg> Tudo do plano PRO</li>
                        <li class="flex items-center gap-3"><svg class="w-5 h-5 text-emerald-400" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7"></path>
                            </svg> Acesso Multi-Utilizador (Equipa)</li>
                        <li class="flex items-center gap-3"><svg class="w-5 h-5 text-emerald-400" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7"></path>
                            </svg> Portal do Cliente Final (White-label)</li>
                        <li class="flex items-center gap-3"><svg class="w-5 h-5 text-emerald-400" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7"></path>
                            </svg> Sem marca de água STOFGARD nos PDFs</li>
                    </ul>
                    <a href="https://wa.me/5511999999999?text=Quero%20assinar%20o%20plano%20ELITE"
                        class="block w-full py-3 px-4 bg-slate-700 hover:bg-slate-600 text-center font-bold rounded-lg transition"
                        target="_blank">Elevar para ELITE</a>
                </div>

            </div>
        </div>
    </section>

    <footer class="bg-slate-950 text-slate-400 py-12 border-t border-slate-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row justify-between items-center">
            <div class="mb-4 md:mb-0">
                <span class="text-xl font-extrabold tracking-tighter text-white">STOF<span
                        class="text-emerald-500">GARD</span></span>
                <p class="text-sm mt-2">Tecnologia Premium para Profissionais e Autónomos.</p>
            </div>
            <div class="flex space-x-6">
                <a href="#" class="hover:text-white transition">Termos de Uso</a>
                <a href="#" class="hover:text-white transition">Política de Privacidade (LGPD)</a>
                <a href="#" class="hover:text-white transition">Suporte</a>
            </div>
        </div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-8 text-sm text-center md:text-left">
            &copy; {{ date('Y') }} STOFGARD. Todos os direitos reservados.
        </div>
    </footer>

</body>

</html>