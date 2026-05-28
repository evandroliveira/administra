<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Administrar - Gestão Inteligente</title>
    @include('partials.vite-assets')
</head>
<body class="bg-gradient-to-br from-blue-50 via-white to-blue-100 min-h-screen flex flex-col">
    <!-- Header -->
    <header class="w-full px-6 py-4 flex items-center justify-between bg-white/80 shadow-sm fixed z-30">
        <div class="flex items-center gap-2">
            <span class="text-blue-700 font-extrabold text-2xl tracking-tight">Administrar</span>
        </div>
        <nav class="hidden md:flex gap-8 text-gray-700 font-medium">
            <a href="#features" class="hover:text-blue-700 transition">Funcionalidades</a>
            <a href="#benefits" class="hover:text-blue-700 transition">Benefícios</a>
            <a href="#pricing" class="hover:text-blue-700 transition">Preço</a>
            <a href="#contact" class="hover:text-blue-700 transition">Contato</a>
        </nav>
        <div class="flex gap-3">
            <a href="{{ route('login') }}" class="px-4 py-2 rounded-lg font-semibold text-blue-700 hover:bg-blue-50 transition">Entrar</a>
            <a href="{{ route('register') }}" class="px-4 py-2 rounded-lg font-semibold bg-blue-700 text-white shadow-lg hover:bg-blue-800 transition">Teste Grátis</a>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="pt-32 pb-20 px-6 flex flex-col-reverse md:flex-row items-center justify-between max-w-7xl mx-auto gap-12">
        <div class="flex-1">
            <h1 class="text-4xl md:text-5xl font-extrabold text-blue-900 mb-6 leading-tight">
                Simplifique a gestão do seu negócio com o <span class="text-blue-700">Administrar</span>
            </h1>
            <p class="text-lg text-gray-700 mb-8">
                Controle financeiro, estoque e cobranças em um só lugar. Mais produtividade, menos complicação.
            </p>
            <div class="flex gap-4">
                <a href="{{ route('register') }}" class="px-7 py-3 rounded-full bg-blue-700 text-white font-bold shadow-lg hover:bg-blue-800 transition">Começar Agora</a>
                <a href="#demo" class="px-7 py-3 rounded-full bg-white border border-blue-700 text-blue-700 font-bold shadow hover:bg-blue-50 transition">Ver Demonstração</a>
            </div>
        </div>
        <div class="flex-1 flex justify-center">
            <img src="{{ asset('images/mockup-admin.png') }}" alt="Interface Administrar" class="w-full max-w-md rounded-3xl shadow-2xl border border-blue-100">
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-20 bg-white">
        <div class="max-w-6xl mx-auto px-6">
            <h2 class="text-3xl font-bold text-blue-900 mb-10 text-center">Funcionalidades Poderosas</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="bg-white rounded-2xl shadow-md p-7 flex flex-col items-center text-center border border-blue-50">
                    <div class="mb-4"><i class="bi bi-credit-card-2-front text-blue-700 text-3xl"></i></div>
                    <h3 class="font-bold text-lg text-blue-900 mb-2">Controle Financeiro</h3>
                    <p class="text-gray-600">Fluxo de caixa inteligente, relatórios e previsões para decisões seguras.</p>
                </div>
                <div class="bg-white rounded-2xl shadow-md p-7 flex flex-col items-center text-center border border-blue-50">
                    <div class="mb-4"><i class="bi bi-people text-blue-700 text-3xl"></i></div>
                    <h3 class="font-bold text-lg text-blue-900 mb-2">Gestão de Clientes</h3>
                    <p class="text-gray-600">Organize contatos, histórico de compras e potencialize o relacionamento.</p>
                </div>
                <div class="bg-white rounded-2xl shadow-md p-7 flex flex-col items-center text-center border border-blue-50">
                    <div class="mb-4"><i class="bi bi-box-seam text-blue-700 text-3xl"></i></div>
                    <h3 class="font-bold text-lg text-blue-900 mb-2">Produtos & Estoque</h3>
                    <p class="text-gray-600">Controle preciso, alertas de estoque mínimo e movimentações detalhadas.</p>
                </div>
                <div class="bg-white rounded-2xl shadow-md p-7 flex flex-col items-center text-center border border-blue-50">
                    <div class="mb-4"><i class="bi bi-file-earmark-text text-blue-700 text-3xl"></i></div>
                    <h3 class="font-bold text-lg text-blue-900 mb-2">Emissão de Boletos</h3>
                    <p class="text-gray-600">Gere boletos bancários de forma simples, rápida e segura.</p>
                </div>
                <div class="bg-white rounded-2xl shadow-md p-7 flex flex-col items-center text-center border border-blue-50">
                    <div class="mb-4"><i class="bi bi-shield-check text-blue-700 text-3xl"></i></div>
                    <h3 class="font-bold text-lg text-blue-900 mb-2">Gestão de Promissórias</h3>
                    <p class="text-gray-600">Administre promissórias, vencimentos e cobranças sem complicação.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Social Proof / Segurança -->
    <section id="benefits" class="py-20 bg-gradient-to-b from-blue-50 to-white">
        <div class="max-w-5xl mx-auto px-6">
            <h2 class="text-3xl font-bold text-blue-900 mb-8 text-center">Segurança, Suporte e Resultados Reais</h2>
            <div class="flex flex-col md:flex-row gap-10 mb-12">
                <div class="flex-1 bg-white rounded-2xl shadow p-8 flex flex-col items-center">
                    <i class="bi bi-shield-lock text-blue-700 text-3xl mb-3"></i>
                    <h3 class="font-bold text-lg mb-2">Segurança de Dados</h3>
                    <p class="text-gray-600 text-center">Criptografia avançada, backups automáticos e proteção total das informações da sua empresa.</p>
                </div>
                <div class="flex-1 bg-white rounded-2xl shadow p-8 flex flex-col items-center">
                    <i class="bi bi-person-lines-fill text-blue-700 text-3xl mb-3"></i>
                    <h3 class="font-bold text-lg mb-2">Suporte Especializado</h3>
                    <p class="text-gray-600 text-center">Equipe pronta para ajudar, com atendimento rápido e humanizado sempre que precisar.</p>
                </div>
            </div>
            <div class="grid md:grid-cols-3 gap-6">
                <div class="bg-white rounded-2xl shadow p-6 flex flex-col items-center">
                    <p class="text-gray-700 italic mb-3">"O Administrar revolucionou a rotina da minha loja. Tudo ficou mais simples e seguro!"</p>
                    <span class="font-bold text-blue-700">Mariana S.</span>
                </div>
                <div class="bg-white rounded-2xl shadow p-6 flex flex-col items-center">
                    <p class="text-gray-700 italic mb-3">"A integração do financeiro com estoque me deu uma visão completa do negócio. Recomendo!"</p>
                    <span class="font-bold text-blue-700">Carlos M.</span>
                </div>
                <div class="bg-white rounded-2xl shadow p-6 flex flex-col items-center">
                    <p class="text-gray-700 italic mb-3">"O suporte é excelente e a plataforma é muito intuitiva. Nunca foi tão fácil gerenciar minha empresa."</p>
                    <span class="font-bold text-blue-700">Fernanda R.</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-blue-900 text-white py-10 mt-auto">
        <div class="max-w-6xl mx-auto px-6 flex flex-col md:flex-row justify-between items-center gap-6">
            <div>
                <span class="font-extrabold text-xl">Administrar</span>
                <p class="text-sm text-blue-200 mt-2">© {{ date('Y') }} Administrar. Todos os direitos reservados.</p>
            </div>
            <nav class="flex gap-6 text-blue-200 text-sm">
                <a href="#features" class="hover:text-white">Funcionalidades</a>
                <a href="#benefits" class="hover:text-white">Benefícios</a>
                <a href="#pricing" class="hover:text-white">Preço</a>
                <a href="#contact" class="hover:text-white">Contato</a>
                <a href="/termos" class="hover:text-white">Termos de Uso</a>
                <a href="/privacidade" class="hover:text-white">Privacidade</a>
            </nav>
            <div class="flex gap-4 mt-4 md:mt-0">
                <a href="#" aria-label="Instagram" class="hover:text-blue-400"><i class="bi bi-instagram text-2xl"></i></a>
                <a href="#" aria-label="LinkedIn" class="hover:text-blue-400"><i class="bi bi-linkedin text-2xl"></i></a>
            </div>
        </div>
    </footer>
</body>
</html>