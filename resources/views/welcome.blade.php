<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Administrar | Evandro Informática</title>
  <!-- Bootstrap 5 CDN -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons (opcional) -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
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
          <a href="#preco" class="btn btn-primary btn-lg px-4 rounded-pill shadow">Teste Grátis</a>
          <a href="#funcionalidades" class="btn btn-outline-primary btn-lg px-4 rounded-pill">Ver Funcionalidades</a>
        </div>
      </div>
      <!-- Coluna Direita -->
      <div class="col-lg-6 text-center">
             <img src="https://images.unsplash.com/photo-1515168833906-d2a3b82b302c?auto=format&fit=crop&w=600&q=80"
               alt="Comerciante atendendo cliente"
               class="img-fluid shadow rounded-4 border border-2 border-light"
               style="max-height: 400px; object-fit: cover;">
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

  <!-- =============== RECURSOS VISUAIS (GRÁFICOS) =============== -->
  <section id="recursos" class="container py-5">
    <h2 class="text-center fw-bold text-primary mb-5">Poder Analítico em Destaque</h2>
    <div class="row g-4">
      <div class="col-md-6">
        <div class="card border-0 shadow-sm rounded-4 h-100">
          <div class="card-body">
            <h5 class="card-title fw-bold mb-3">Faturamento vs. Boletos Recebidos</h5>
            <img src="https://images.unsplash.com/photo-1461749280684-dccba630e2f6?auto=format&fit=crop&w=400&q=80"
                 alt="Gráfico de Barras"
                 class="img-fluid rounded">
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="card border-0 shadow-sm rounded-4 h-100">
          <div class="card-body">
            <h5 class="card-title fw-bold mb-3">Saúde do Estoque e Giro de Produtos</h5>
            <img src="https://images.unsplash.com/photo-1504384308090-c894fdcc538d?auto=format&fit=crop&w=400&q=80"
                 alt="Gráfico de Pizza"
                 class="img-fluid rounded">
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
        <a href="#" class="text-white-50 text-decoration-none">Política de Privacidade</a>
      </div>
      <div>
        <a href="mailto:contato@evandroinformatica.com.br" class="text-white-50 text-decoration-none"><i class="bi bi-envelope me-1"></i> contato@evandroinformatica.com.br</a>
      </div>
    </div>
  </footer>

  <!-- Bootstrap JS Bundle -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>