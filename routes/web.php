<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Cadastros\CategoriaController;
use App\Http\Controllers\Cadastros\ClienteController;
use App\Http\Controllers\Billing\AssinaturaController;
use App\Http\Controllers\Billing\AsaasWebhookController;
use App\Http\Controllers\Cadastros\ProdutoController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmpresaController;
use App\Http\Controllers\Financeiro\ContaPagarController;
use App\Http\Controllers\Financeiro\ContaReceberController;
use App\Http\Controllers\Financeiro\PagamentoPagarController;
use App\Http\Controllers\Financeiro\PagamentoReceberController;
use App\Http\Controllers\Financeiro\PromissoriaController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Relatorios\FaturamentoController;
use App\Http\Controllers\Relatorios\IndexController;
use App\Http\Controllers\Relatorios\InadimplentesController;
use App\Http\Controllers\Relatorios\LucroController;
use App\Http\Controllers\Usuarios\UsuarioEmpresaController;
use App\Http\Controllers\Vendas\VendaController;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }

    return app(AuthenticatedSessionController::class)->create();
});

Route::post('/webhooks/asaas', AsaasWebhookController::class)
    ->withoutMiddleware([ValidateCsrfToken::class])
    ->name('billing.webhooks.asaas');

Route::get('/dashboard', DashboardController::class)->middleware(['auth', 'verified', 'empresa.acesso'])->name('dashboard');

Route::middleware(['auth', 'empresa.acesso'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::middleware('role:admin|gerente|vendedor|recepcao')->group(function () {
        Route::resource('clientes', ClienteController::class);
        Route::get('/assinatura', [AssinaturaController::class, 'show'])->name('assinatura.show');
    });

    Route::middleware('role:admin')->group(function () {
        Route::post('/assinatura/plano', [AssinaturaController::class, 'updatePlan'])->name('assinatura.plano.update');
        Route::post('/assinatura/cobranca', [AssinaturaController::class, 'createCharge'])->name('assinatura.cobranca.store');
        Route::post('/assinatura/cobranca/regenerar', [AssinaturaController::class, 'regenerateCharge'])->name('assinatura.cobranca.regenerate');
        Route::get('/empresa', [EmpresaController::class, 'edit'])->name('empresa.edit');
        Route::match(['put', 'patch'], '/empresa', [EmpresaController::class, 'update'])->name('empresa.update');
        Route::get('/usuarios', [UsuarioEmpresaController::class, 'index'])->name('usuarios.index');
        Route::get('/usuarios/novo', [UsuarioEmpresaController::class, 'create'])->name('usuarios.create');
        Route::post('/usuarios', [UsuarioEmpresaController::class, 'store'])->name('usuarios.store');
        Route::get('/usuarios/{usuario}/editar', [UsuarioEmpresaController::class, 'edit'])->name('usuarios.edit');
        Route::match(['put', 'patch'], '/usuarios/{usuario}', [UsuarioEmpresaController::class, 'update'])->name('usuarios.update');
    });

    Route::middleware('role:admin|gerente')->group(function () {
        Route::resource('categorias', CategoriaController::class)->except(['show']);
        Route::resource('produtos', ProdutoController::class);
        Route::get('/relatorios', IndexController::class)->name('relatorios.index');
        Route::get('/relatorios/faturamento', [FaturamentoController::class, 'index'])->name('relatorios.faturamento');
        Route::get('/relatorios/inadimplentes', [InadimplentesController::class, 'index'])->name('relatorios.inadimplentes');
        Route::get('/relatorios/lucro', [LucroController::class, 'index'])->name('relatorios.lucro');

        Route::prefix('contas')->group(function () {
            Route::get('/receber', [ContaReceberController::class, 'index'])->name('contas.receber.index');
            Route::get('/receber/{contaReceber}', [ContaReceberController::class, 'show'])->name('contas.receber.show');
            Route::post('/receber/{contaReceber}/pagamentos', [PagamentoReceberController::class, 'store'])->name('contas.receber.pagamentos.store');

            Route::get('/pagar', [ContaPagarController::class, 'index'])->name('contas.pagar.index');
            Route::get('/pagar/nova', [ContaPagarController::class, 'create'])->name('contas.pagar.create');
            Route::post('/pagar', [ContaPagarController::class, 'store'])->name('contas.pagar.store');
            Route::get('/pagar/{contaPagar}', [ContaPagarController::class, 'show'])->name('contas.pagar.show');
            Route::post('/pagar/{contaPagar}/pagamentos', [PagamentoPagarController::class, 'store'])->name('contas.pagar.pagamentos.store');
            Route::delete('/pagar/pagamentos/{pagamentoPagar}', [PagamentoPagarController::class, 'destroy'])->name('contas.pagar.pagamentos.destroy');
        });

        Route::get('/promissorias', [PromissoriaController::class, 'index'])->name('promissorias.index');
        Route::get('/promissorias/{promissoria}', [PromissoriaController::class, 'show'])->name('promissorias.show');
    });

    Route::middleware('role:admin|gerente|vendedor')->group(function () {
        Route::get('/vendas', [VendaController::class, 'index'])->name('vendas.index');
        Route::get('/vendas/nova', [VendaController::class, 'create'])->name('vendas.create');
        Route::post('/vendas', [VendaController::class, 'store'])->name('vendas.store');
        Route::get('/vendas/{venda}', [VendaController::class, 'show'])->name('vendas.show');
        Route::patch('/vendas/{venda}/status', [VendaController::class, 'updateStatus'])->name('vendas.status.update');
        Route::post('/vendas/{venda}/nota-fiscal/emitir', [VendaController::class, 'emitirNotaFiscal'])->name('vendas.nota-fiscal.emitir');
        Route::get('/vendas/{venda}/recibo', [VendaController::class, 'receipt'])->name('vendas.recibo');
        Route::get('/vendas/{venda}/promissoria/imprimir', [VendaController::class, 'printPromissoria'])->name('vendas.promissoria.imprimir');
    });
});

require __DIR__.'/auth.php';
