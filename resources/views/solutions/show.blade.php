<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ $solution['title'] }}</title>
  <meta name="description" content="{{ $solution['description'] }}">
  <meta name="robots" content="index, follow">
  <link rel="canonical" href="{{ route('solutions.show', ['slug' => $slug]) }}">
  <meta property="og:type" content="website">
  <meta property="og:url" content="{{ route('solutions.show', ['slug' => $slug]) }}">
  <meta property="og:title" content="{{ $solution['title'] }}">
  <meta property="og:description" content="{{ $solution['description'] }}">
  <meta property="og:image" content="{{ asset('images/lojagestao.png') }}">
  <meta property="og:locale" content="pt_BR">
  <meta property="og:site_name" content="Administrar">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="{{ $solution['title'] }}">
  <meta name="twitter:description" content="{{ $solution['description'] }}">
  <meta name="twitter:image" content="{{ asset('images/lojagestao.png') }}">
  <meta name="theme-color" content="#0d6efd">
  <link rel="icon" type="image/svg+xml" href="/favicon.svg">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
  <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm py-3">
    <div class="container">
      <a class="navbar-brand fw-bold text-primary fs-3" href="{{ route('home') }}">Administrar</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#solutionNavbar" aria-controls="solutionNavbar" aria-expanded="false" aria-label="Abrir menu">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="solutionNavbar">
        <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-3">
          <li class="nav-item"><a class="nav-link" href="{{ route('home') }}#solucoes">Soluções</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('home') }}#preco">Plano e preço</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('home') }}#contato">Contato</a></li>
        </ul>
        <a href="{{ route('register') }}" class="btn btn-primary ms-lg-4 mt-3 mt-lg-0 px-4 py-2 rounded-pill fw-semibold">Conhecer o sistema</a>
      </div>
    </div>
  </nav>

  <main>
    <section class="bg-white py-5">
      <div class="container py-lg-4">
        <nav aria-label="Navegação estrutural" class="mb-4">
          <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Início</a></li>
            <li class="breadcrumb-item"><a href="{{ route('home') }}#solucoes">Soluções</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $solution['eyebrow'] }}</li>
          </ol>
        </nav>
        <div class="row align-items-center g-5">
          <div class="col-lg-8">
            <p class="text-primary fw-semibold text-uppercase small mb-2">{{ $solution['eyebrow'] }} para pequenas empresas</p>
            <h1 class="display-5 fw-bold text-dark mb-4">{{ $solution['heading'] }}</h1>
            <p class="lead text-secondary mb-4">{{ $solution['intro'] }}</p>
            <div class="d-flex flex-wrap gap-3">
              <a href="{{ route('register') }}" class="btn btn-primary btn-lg px-4 rounded-pill">Conhecer o Administrar</a>
              <a href="{{ route('home') }}#preco" class="btn btn-outline-primary btn-lg px-4 rounded-pill">Ver plano e preço</a>
            </div>
          </div>
          <div class="col-lg-4 text-center">
            <div class="d-inline-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-circle" style="width: 180px; height: 180px;">
              <i class="bi {{ $solution['icon'] }}" style="font-size: 5rem;" aria-hidden="true"></i>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="container py-5">
      <div class="text-center mb-4">
        <h2 class="fw-bold text-primary">Como o Administrar ajuda na rotina</h2>
        <p class="text-secondary">Recursos para organizar as tarefas e acompanhar a operação da empresa.</p>
      </div>
      <div class="row g-4">
        @foreach ($solution['benefits'] as $benefit)
          <div class="col-md-6">
            <article class="card border-0 shadow-sm h-100">
              <div class="card-body p-4">
                <h3 class="h5 fw-bold"><i class="bi bi-check-circle-fill text-success me-2" aria-hidden="true"></i>{{ $benefit['title'] }}</h3>
                <p class="text-secondary mb-0">{{ $benefit['description'] }}</p>
              </div>
            </article>
          </div>
        @endforeach
      </div>
    </section>

    <section class="bg-white py-5">
      <div class="container">
        <div class="row justify-content-center">
          <div class="col-lg-9">
            <h2 class="fw-bold text-primary mb-4">Um fluxo simples para começar</h2>
            <ol class="list-group list-group-numbered list-group-flush">
              @foreach ($solution['steps'] as $step)
                <li class="list-group-item bg-transparent py-3">{{ $step }}</li>
              @endforeach
            </ol>
          </div>
        </div>
      </div>
    </section>

    <section class="container py-5">
      <div class="row justify-content-center">
        <div class="col-lg-9">
          <h2 class="fw-bold text-primary mb-4">Perguntas frequentes</h2>
          @foreach ($solution['faqs'] as $faq)
            <article class="border-bottom py-3">
              <h3 class="h5 fw-semibold">{{ $faq['question'] }}</h3>
              <p class="text-secondary mb-0">{{ $faq['answer'] }}</p>
            </article>
          @endforeach
        </div>
      </div>
    </section>

    <section class="bg-primary text-white py-5">
      <div class="container text-center">
        <h2 class="fw-bold">Veja como o Administrar pode ajudar sua empresa</h2>
        <p class="mb-4">Conheça os recursos incluídos no plano e escolha se faz sentido para sua operação.</p>
        <a href="{{ route('register') }}" class="btn btn-light btn-lg px-4 rounded-pill text-primary fw-semibold">Começar cadastro</a>
      </div>
    </section>

    <section class="container py-5">
      <h2 class="fw-bold text-primary mb-4">Conheça também</h2>
      <div class="row g-3">
        @foreach ($relatedSolutions as $relatedSlug => $related)
          <div class="col-md-4">
            <a href="{{ route('solutions.show', ['slug' => $relatedSlug]) }}" class="card border-0 shadow-sm h-100 text-decoration-none">
              <div class="card-body">
                <h3 class="h5 fw-bold text-dark">{{ $related['eyebrow'] }}</h3>
                <p class="text-secondary mb-0">{{ $related['intro'] }}</p>
              </div>
            </a>
          </div>
        @endforeach
      </div>
    </section>
  </main>

  <footer class="bg-dark text-white py-4">
    <div class="container d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
      <span><strong>Administrar</strong> &copy; {{ date('Y') }} Evandro Informática.</span>
      <div>
        <a href="{{ route('home') }}" class="text-white-50 text-decoration-none me-3">Início</a>
        <a href="{{ route('legal.privacidade') }}" class="text-white-50 text-decoration-none me-3">Privacidade</a>
        <a href="{{ route('home') }}#contato" class="text-white-50 text-decoration-none">Contato</a>
      </div>
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
