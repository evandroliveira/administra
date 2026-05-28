<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login | Administrar</title>
  <!-- Bootstrap 5 CDN -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body style="background-color: #f8f9fa;">

  <!-- CONTAINER PRINCIPAL -->
  <div class="container-fluid p-0">
    <div class="row g-0 vh-100">

      <!-- LADO ESQUERDO: BRANDING (Visível apenas em telas grandes) -->
      <div class="col-lg-6 d-none d-lg-flex align-items-center justify-content-center bg-primary text-white">
        <div class="text-center px-5">
          <div class="mb-4">
            <span class="fw-bold display-3">Administrar</span>
          </div>
          <h2 class="fw-semibold mb-3">A gestão inteligente do seu negócio começa aqui</h2>
          <p class="lead mb-4">
            Segurança, agilidade e controle total de clientes, estoque e finanças.<br>
            Tudo em um só lugar.
          </p>
          <img src="https://images.unsplash.com/photo-1515168833906-d2a3b82b302c?auto=format&fit=crop&w=600&q=80"
            alt="Comerciante atendendo cliente"
            class="img-fluid rounded shadow"
            style="max-width: 350px;">
        </div>
      </div>

      <!-- LADO DIREITO: FORMULÁRIO DE LOGIN -->
      <div class="col-lg-6 d-flex align-items-center justify-content-center bg-light">
        <div class="w-100" style="max-width: 400px;">
          <div class="p-4 p-md-5 shadow-sm bg-white rounded">
            <h1 class="fw-bold mb-2 text-primary">Acessar o Sistema</h1>
            <p class="mb-4 text-secondary">Insira suas credenciais para continuar</p>
            <form>
              <div class="form-floating mb-3">
                <input type="email" class="form-control" id="loginEmail" placeholder="nome@empresa.com" autocomplete="username" required>
                <label for="loginEmail">E-mail</label>
              </div>
              <div class="form-floating mb-4">
                <input type="password" class="form-control" id="loginPassword" placeholder="Senha" autocomplete="current-password" required>
                <label for="loginPassword">Senha</label>
              </div>
              <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" value="" id="rememberMe">
                  <label class="form-check-label" for="rememberMe">
                    Lembrar-me
                  </label>
                </div>
                <a href="#" class="text-decoration-none text-primary small">Esqueceu sua senha?</a>
              </div>
              <button type="submit" class="btn btn-primary btn-lg w-100 shadow-sm">Entrar</button>
            </form>
            <div class="text-center text-muted mt-4 small">
              Desenvolvido com tecnologia e segurança por Evandro Informática
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>

  <!-- Bootstrap JS Bundle -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>