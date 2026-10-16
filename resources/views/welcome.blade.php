<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <title>Administrar | Sistema de Gestão Financeira, Vendas e Estoque</title>
  <meta name="description" content="Administrar é o sistema de gestão completo para o seu negócio: controle financeiro, fluxo de caixa, clientes, estoque, promissórias e boletos em um só lugar. Teste grátis.">
  <meta name="keywords" content="sistema de gestão, controle financeiro, gestão de vendas, controle de estoque, emissão de boletos, promissórias, ERP para pequenas empresas">
  <meta name="author" content="Evandro Informática">
  <meta name="robots" content="index, follow">
  <link rel="canonical" href="{{ url('/') }}">

  <!-- Open Graph / Facebook -->
  <meta property="og:type" content="website">
  <meta property="og:url" content="{{ url('/') }}">
  <meta property="og:title" content="Administrar | Sistema de Gestão Financeira, Vendas e Estoque">
  <meta property="og:description" content="Gerencie clientes, estoque, produtos, promissórias e boletos com facilidade, segurança e agilidade. Tudo o que sua empresa precisa para crescer.">
  <meta property="og:image" content="{{ asset('images/lojagestao.png') }}">
  <meta property="og:locale" content="pt_BR">
  <meta property="og:site_name" content="Administrar">

  <!-- Twitter Card -->
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="Administrar | Sistema de Gestão Financeira, Vendas e Estoque">
  <meta name="twitter:description" content="Gerencie clientes, estoque, produtos, promissórias e boletos com facilidade, segurança e agilidade.">
  <meta name="twitter:image" content="{{ asset('images/lojagestao.png') }}">

  <meta name="theme-color" content="#0d6efd">
  <link rel="icon" type="image/svg+xml" href="/favicon.svg">
  <link rel="manifest" href="/manifest.webmanifest">

  <!-- Bootstrap 5 CDN -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons (opcional) -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

  <!-- Dados estruturados para mecanismos de busca -->
  <script type="application/ld+json">
  {
    "@@context": "https://schema.org",
    "@@type": "SoftwareApplication",
    "name": "Administrar",
    "applicationCategory": "BusinessApplication",
    "operatingSystem": "Web",
    "url": "{{ url('/') }}",
    "description": "Sistema de gestão completo para controle financeiro, vendas, clientes, estoque, promissórias e boletos.",
    "offers": {
      "@@type": "Offer",
      "price": "49.90",
      "priceCurrency": "BRL",
      "priceSpecification": {
        "@@type": "UnitPriceSpecification",
        "price": "49.90",
        "priceCurrency": "BRL",
        "billingDuration": "P1M"
      }
    },
    "provider": {
      "@@type": "Organization",
      "name": "Evandro Informática",
      "url": "{{ url('/') }}",
      "email": "administrativo@lojagerencia.com.br"
    }
  }
  </script>

  <style>
    .btn-cta {
      background: linear-gradient(90deg, #0d6efd 80%, #20c997 100%);
      border: none;
      color: #fff;
    }
    .btn-cta:hover {
      background: linear-gradient(90deg, #0b5ed7 80%, #198754 100%);
      color: #fff;
    }
    .plano-recurso {
      border-left: 3px solid #198754;
      padding: .65rem .8rem;
      background: #f8f9fa;
      border-radius: 0 .5rem .5rem 0;
    }
    .plano-recurso i {
      color: #198754;
    }
  </style>
</head>
<body style="background-color: #f8f9fa;">

  <!-- ================= NAVBAR ================= -->
  <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm py-3">
    <div class="container">
      <a class="navbar-brand fw-bold text-primary fs-3" href="#">Administrar</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="mainNavbar">
        <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center gap-lg-3">
          <li class="nav-item"><a class="nav-link" href="#funcionalidades">Funcionalidades</a></li>
          <li class="nav-item"><a class="nav-link" href="#solucoes">Soluções</a></li>
          <li class="nav-item"><a class="nav-link" href="#recursos">Recursos Visuais</a></li>
          <li class="nav-item"><a class="nav-link" href="#empresa">A Empresa</a></li>
          <li class="nav-item"><a class="nav-link" href="#preco">PREÇO</a></li>
          <li class="nav-item"><a class="nav-link" href="#contato">Contato</a></li>
        </ul>
        <a href="/login" class="btn btn-cta ms-lg-4 mt-3 mt-lg-0 px-4 py-2 rounded-pill fw-semibold shadow-sm">Acessar Sistema</a>
      </div>
    </div>
  </nav>

  <!-- =============== HERO SECTION =============== -->
  <section class="container py-5">
    <div class="row align-items-center">
      <!-- Coluna Esquerda -->
      <div class="col-lg-6 mb-5 mb-lg-0">
        <h1 class="display-5 fw-bold text-primary mb-4">
          Controle financeiro e gestão completa em um só lugar
        </h1>
        <p class="lead text-secondary mb-4">
          Gerencie clientes, estoque, produtos, promissórias e boletos com facilidade, segurança e agilidade. Tudo o que sua empresa precisa para crescer, sem complicação.
        </p>
        <div class="d-flex gap-3">
          <a href="{{ route('register') }}" class="btn btn-primary btn-lg px-4 rounded-pill shadow">Teste Grátis</a>
          <a href="#funcionalidades" class="btn btn-outline-primary btn-lg px-4 rounded-pill">Ver Funcionalidades</a>
        </div>
      </div>
      <!-- Coluna Direita -->
      <div class="col-lg-6 text-center">
              <video src="/video/comerciante.mp4" class="img-fluid shadow rounded-4 border border-2 border-light" style="max-height: 400px; object-fit: cover;" autoplay loop muted playsinline></video>
      </div>
    </div>
  </section>

  <!-- =============== FUNCIONALIDADES =============== -->
  <section id="funcionalidades" class="bg-light py-5">
    <div class="container">
      <h2 class="text-center fw-bold text-primary mb-5">Funcionalidades do Administrar</h2>
      <div class="row row-cols-1 row-cols-md-3 g-4">
        <div class="col">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
              <i class="bi bi-cash-stack text-primary fs-1 mb-3"></i>
              <h5 class="card-title fw-bold mb-2">Controle Financeiro e Fluxo de Caixa</h5>
              <p class="card-text text-secondary">Acompanhe receitas, despesas e tenha visão total do seu caixa em tempo real.</p>
            </div>
          </div>
        </div>
        <div class="col">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
              <i class="bi bi-person-lines-fill text-primary fs-1 mb-3"></i>
              <h5 class="card-title fw-bold mb-2">Cadastro de Clientes e Produtos</h5>
              <p class="card-text text-secondary">Organize clientes, produtos e histórico de vendas de forma simples e eficiente.</p>
            </div>
          </div>
        </div>
        <div class="col">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
              <i class="bi bi-box-seam text-primary fs-1 mb-3"></i>
              <h5 class="card-title fw-bold mb-2">Controle de Estoque com Alertas</h5>
              <p class="card-text text-secondary">Monitore o estoque, receba alertas automáticos e evite rupturas ou excessos.</p>
            </div>
          </div>
        </div>
        <div class="col">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
              <i class="bi bi-journal-check text-primary fs-1 mb-3"></i>
              <h5 class="card-title fw-bold mb-2">Gestão de Promissórias</h5>
              <p class="card-text text-secondary">Emita, controle vencimentos e acompanhe o status das promissórias facilmente.</p>
            </div>
          </div>
        </div>
        <div class="col">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
              <i class="bi bi-file-earmark-text text-primary fs-1 mb-3"></i>
              <h5 class="card-title fw-bold mb-2">Emissão e Monitoramento de Boletos</h5>
              <p class="card-text text-secondary">Gere boletos bancários e acompanhe pagamentos de forma automatizada e segura.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- =============== SOLUÇÕES =============== -->
  <section id="solucoes" class="container py-5">
    <div class="text-center mb-5">
      <h2 class="fw-bold text-primary">Soluções para organizar sua empresa</h2>
      <p class="lead text-secondary mb-0">Conheça como o Administrar ajuda nas tarefas do dia a dia.</p>
    </div>
    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-4">
      @foreach ([
        'gestao-financeira' => ['icon' => 'bi-cash-stack', 'title' => 'Gestão financeira', 'description' => 'Organize contas, vencimentos, recebimentos e relatórios.'],
        'controle-de-estoque' => ['icon' => 'bi-box-seam', 'title' => 'Controle de estoque', 'description' => 'Cadastre produtos e acompanhe quantidades e estoque mínimo.'],
        'vendas-e-clientes' => ['icon' => 'bi-bag-check', 'title' => 'Vendas e clientes', 'description' => 'Registre vendas e mantenha os dados dos clientes organizados.'],
        'boletos-e-promissorias' => ['icon' => 'bi-receipt', 'title' => 'Boletos e promissórias', 'description' => 'Acompanhe cobranças, vencimentos e valores a receber.'],
      ] as $slug => $solution)
        <div class="col">
          <a href="{{ route('solutions.show', ['slug' => $slug]) }}" class="card border-0 shadow-sm h-100 text-decoration-none">
            <div class="card-body p-4">
              <i class="bi {{ $solution['icon'] }} text-primary fs-2 mb-3" aria-hidden="true"></i>
              <h3 class="h5 card-title fw-bold text-dark">{{ $solution['title'] }}</h3>
              <p class="card-text text-secondary">{{ $solution['description'] }}</p>
              <span class="fw-semibold text-primary">Saiba mais <i class="bi bi-arrow-right" aria-hidden="true"></i></span>
            </div>
          </a>
        </div>
      @endforeach
    </div>
  </section>

  <!-- =============== RECURSOS VISUAIS (GRÁFICOS) =============== -->
  <section id="recursos" class="container py-5">
    <h2 class="text-center fw-bold text-primary mb-5">Poder Analítico em Destaque</h2>
    <div class="row g-4">
      <div class="col-md-6">
        <div class="card border-0 shadow-sm rounded-4 h-100">
          <div class="card-body">
            <h5 class="card-title fw-bold mb-3">Faturamento vs. Boletos Recebidos</h5>
            <img src="{{ asset('images/faturamento-boletos.svg') }}"
                 alt="Ilustração de faturamento, boletos recebidos e gráfico financeiro"
                 class="img-fluid rounded-3 w-100"
                 style="height: 240px; object-fit: cover;">
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="card border-0 shadow-sm rounded-4 h-100">
          <div class="card-body">
            <h5 class="card-title fw-bold mb-3">Saúde do Estoque e Giro de Produtos</h5>
            <img src="{{ asset('images/estoque-giro.svg') }}"
                 alt="Ilustração de produtos organizados em estoque e indicadores de giro"
                 class="img-fluid rounded-3 w-100"
                 style="height: 240px; object-fit: cover;">
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- =============== SOBRE A EMPRESA =============== -->
  <section id="empresa" class="bg-light py-5">
    <div class="container">
      <div class="row align-items-center">
        <!-- Texto Institucional -->
        <div class="col-md-6 mb-4 mb-md-0">
          <h2 class="fw-bold text-primary mb-3">Evandro Informática</h2>
          <p class="lead text-secondary mb-3">
            A Evandro Informática é a desenvolvedora do sistema Administrar. Com sólida experiência em desenvolvimento de software, oferecemos infraestrutura segura, proteção de dados e um compromisso real com o sucesso do seu negócio.
          </p>
          <ul class="list-unstyled mb-3">
            <li class="mb-2"><i class="bi bi-shield-lock text-success me-2"></i> Infraestrutura robusta e segura</li>
            <li class="mb-2"><i class="bi bi-people text-primary me-2"></i> Suporte técnico humano e ágil</li>
            <li class="mb-2"><i class="bi bi-award text-warning me-2"></i> Mais de 10 anos de experiência em soluções digitais</li>
          </ul>
        </div>
        <!-- Imagem Institucional -->
        <div class="col-md-6 text-center">
          <img src="https://images.unsplash.com/photo-1519389950473-47ba0277781c?auto=format&fit=crop&w=500&q=80"
               alt="Tecnologia e Segurança"
               class="img-fluid rounded-4 shadow">
        </div>
      </div>
    </div>
  </section>

  <!-- =============== PREÇO =============== -->
  <section id="preco" class="py-5">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-8">
          <div class="card border-0 shadow-sm">
            <div class="card-body p-4 p-lg-5">
              <div class="text-center">
                <span class="badge text-bg-primary mb-3">Um plano completo</span>
                <h2 class="fw-bold text-primary mb-3">Tudo o que seu negócio precisa, em um só plano</h2>
                <div class="mb-3">
                  <span class="display-4 fw-bold text-dark">R$ 49,90</span>
                  <span class="text-secondary">/ mês</span>
                </div>
                <p class="text-secondary mb-4">Sem planos complicados: todos os recursos abaixo já estão incluídos.</p>
              </div>

              <div class="row g-2 mb-4">
                <div class="col-md-6"><div class="plano-recurso h-100"><i class="bi bi-check-lg me-2"></i>Dashboard com indicadores do negócio</div></div>
                <div class="col-md-6"><div class="plano-recurso h-100"><i class="bi bi-check-lg me-2"></i>Cadastro de clientes, produtos e categorias</div></div>
                <div class="col-md-6"><div class="plano-recurso h-100"><i class="bi bi-check-lg me-2"></i>Até 1.000 produtos cadastrados</div></div>
                <div class="col-md-6"><div class="plano-recurso h-100"><i class="bi bi-check-lg me-2"></i>Controle de estoque e estoque mínimo</div></div>
                <div class="col-md-6"><div class="plano-recurso h-100"><i class="bi bi-check-lg me-2"></i>Registro e acompanhamento de vendas</div></div>
                <div class="col-md-6"><div class="plano-recurso h-100"><i class="bi bi-check-lg me-2"></i>Contas a pagar e a receber</div></div>
                <div class="col-md-6"><div class="plano-recurso h-100"><i class="bi bi-check-lg me-2"></i>Boletos para cobranças de clientes*</div></div>
                <div class="col-md-6"><div class="plano-recurso h-100"><i class="bi bi-check-lg me-2"></i>Promissórias e acompanhamento de vencimentos</div></div>
                <div class="col-md-6"><div class="plano-recurso h-100"><i class="bi bi-check-lg me-2"></i>Relatórios de faturamento, lucro e inadimplência</div></div>
                <div class="col-md-6"><div class="plano-recurso h-100"><i class="bi bi-check-lg me-2"></i>Exportação de relatórios em PDF e Excel</div></div>
                <div class="col-md-6"><div class="plano-recurso h-100"><i class="bi bi-check-lg me-2"></i>Acesso para até 5 usuários</div></div>
                <div class="col-md-6"><div class="plano-recurso h-100"><i class="bi bi-check-lg me-2"></i>Suporte e atualizações da plataforma</div></div>
              </div>

              <p class="small text-secondary mb-4">* A emissão de boletos depende da configuração da integração de cobrança da empresa.</p>

              <div class="row g-3">
                <div class="col-md-6">
                  <div class="border rounded p-3 h-100">
                    <div class="fw-bold text-primary mb-2"><i class="bi bi-calendar-check me-2"></i>Condição</div>
                    <p class="text-secondary mb-0">Mensalidade de R$ 49,90 com cobrança recorrente mensal.</p>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="border rounded p-3 h-100">
                    <div class="fw-bold text-primary mb-2"><i class="bi bi-credit-card-2-front me-2"></i>Pagamento</div>
                    <p class="text-secondary mb-0">As opções de pagamento são apresentadas no checkout da assinatura.</p>
                  </div>
                </div>
              </div>
              <div class="text-center mt-4">
                <a href="{{ route('register') }}" class="btn btn-primary btn-lg px-5 rounded-pill shadow">Começar agora</a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- =============== CONTATO =============== -->
  <section id="contato" class="bg-light py-5">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-8">
          <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4 p-lg-5">
              <div class="text-center mb-4">
                <h2 class="fw-bold text-primary mb-2">Fale com a gente</h2>
                <p class="text-secondary mb-0">Envie sua dúvida. Nossa equipe responderá pelo e-mail informado.</p>
              </div>

              @if (session('contact_success'))
                <div class="alert alert-success" role="status">{{ session('contact_success') }}</div>
              @endif

              @if ($errors->any())
                <div class="alert alert-danger" role="alert">
                  <p class="fw-semibold mb-2">Confira os campos do formulário:</p>
                  <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                      <li>{{ $error }}</li>
                    @endforeach
                  </ul>
                </div>
              @endif

              <form method="POST" action="{{ route('contact.store') }}">
                @csrf
                <div class="row g-3">
                  <div class="col-md-6">
                    <label for="contact-name" class="form-label">Nome</label>
                    <input id="contact-name" name="name" type="text" class="form-control" value="{{ old('name') }}" maxlength="120" autocomplete="name" required>
                  </div>
                  <div class="col-md-6">
                    <label for="contact-email" class="form-label">E-mail</label>
                    <input id="contact-email" name="email" type="email" class="form-control" value="{{ old('email') }}" maxlength="255" autocomplete="email" required>
                  </div>
                  <div class="col-12">
                    <label for="contact-subject" class="form-label">Assunto</label>
                    <input id="contact-subject" name="subject" type="text" class="form-control" value="{{ old('subject') }}" maxlength="150" required>
                  </div>
                  <div class="col-12">
                    <label for="contact-message" class="form-label">Mensagem</label>
                    <textarea id="contact-message" name="message" class="form-control" rows="5" maxlength="5000" required>{{ old('message') }}</textarea>
                  </div>
                  <div class="col-12 d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3">
                    <span class="small text-secondary">Ou escreva para <a href="mailto:administrativo@lojagerencia.com.br">administrativo@lojagerencia.com.br</a></span>
                    <button type="submit" class="btn btn-primary px-4 rounded-pill">
                      <i class="bi bi-send me-2"></i>Enviar mensagem
                    </button>
                  </div>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- =============== FOOTER =============== -->
  <footer class="bg-dark text-white py-4 mt-5">
    <div class="container d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
      <div>
        <span class="fw-bold">Administrar</span> &copy; {{ date('Y') }} Evandro Informática. Todos os direitos reservados.
      </div>
      <div>
        <a href="#funcionalidades" class="text-white-50 text-decoration-none me-3">Funcionalidades</a>
        <a href="#empresa" class="text-white-50 text-decoration-none me-3">A Empresa</a>
        <a href="#contato" class="text-white-50 text-decoration-none me-3">Contato</a>
        <a href="{{ route('legal.privacidade') }}" class="text-white-50 text-decoration-none">Política de Privacidade</a>
      </div>
      <div>
        <a href="mailto:administrativo@lojagerencia.com.br" class="text-white-50 text-decoration-none"><i class="bi bi-envelope me-1"></i> administrativo@lojagerencia.com.br</a>
      </div>
    </div>
  </footer>

  <!-- Bootstrap JS Bundle -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>