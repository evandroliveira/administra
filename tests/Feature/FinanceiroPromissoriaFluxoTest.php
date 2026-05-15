<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\ContaPagar;
use App\Models\ContaReceber;
use App\Models\Empresa;
use App\Models\PagamentoPagar;
use App\Models\Perfil;
use App\Models\Promissoria;
use App\Models\PromissoriaParcela;
use App\Models\User;
use App\Models\Venda;
use Database\Seeders\PerfilUsuarioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class FinanceiroPromissoriaFluxoTest extends TestCase
{
    use RefreshDatabase;

    protected Empresa $empresa;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(PerfilUsuarioSeeder::class);

        $this->empresa = Empresa::query()->where('slug', 'administrar')->firstOrFail();
        $this->admin = $this->criarUsuarioDaEmpresa($this->empresa, Perfil::ADMIN);
    }

    public function test_pagamento_conta_receber_atualiza_status_parcial_e_quitada(): void
    {
        $cliente = $this->criarCliente('financeiro.receber@example.com', '20000000001');
        $venda = $this->criarVenda($cliente, 150);
        $conta = $this->criarContaReceber($cliente, $venda, 150);

        $cliente->refresh();
        $this->assertEquals(850.0, (float) $cliente->credito_disponivel);

        $this->actingAs($this->admin)
            ->postJson(route('contas.receber.pagamentos.store', $conta), [
                'valor' => 50,
                'metodo' => 'pix',
                'observacoes' => 'Entrada parcial',
            ])
            ->assertCreated();

        $conta->refresh();
        $cliente->refresh();

        $this->assertSame('parcial', $conta->status);
        $this->assertEquals(50.0, (float) $conta->valor_pago);
        $this->assertEquals(100.0, $conta->saldo_devedor);
        $this->assertEquals(900.0, (float) $cliente->credito_disponivel);

        $this->actingAs($this->admin)
            ->postJson(route('contas.receber.pagamentos.store', $conta), [
                'valor' => 100,
                'metodo' => 'transferencia',
                'observacoes' => 'Quitacao final',
            ])
            ->assertCreated();

        $conta->refresh();
        $cliente->refresh();

        $this->assertSame('quitada', $conta->status);
        $this->assertEquals(150.0, (float) $conta->valor_pago);
        $this->assertEquals(0.0, $conta->saldo_devedor);
        $this->assertEquals(1000.0, (float) $cliente->credito_disponivel);

        $this->assertDatabaseCount('pagamentos_receber', 2);
    }

    public function test_pagamento_conta_pagar_e_estorno_recalculam_status(): void
    {
        $conta = ContaPagar::create([
            'empresa_id' => $this->empresa->id,
            'descricao' => 'Fornecedor de teste',
            'fornecedor' => 'Fornecedor XPTO',
            'valor_original' => 100,
            'valor_pago' => 0,
            'valor_juros' => 0,
            'data_vencimento' => now()->addDays(10)->toDateString(),
            'status' => 'aberta',
        ]);

        $primeiroPagamentoResponse = $this->actingAs($this->admin)
            ->postJson(route('contas.pagar.pagamentos.store', $conta), [
                'valor' => 40,
                'metodo' => 'pix',
                'observacoes' => 'Primeira baixa',
            ])
            ->assertCreated();

        $primeiroPagamentoId = $primeiroPagamentoResponse->json('pagamento.id');

        $conta->refresh();
        $this->assertSame('parcial', $conta->status);
        $this->assertEquals(40.0, (float) $conta->valor_pago);
        $this->assertEquals(60.0, $conta->saldo_devedor);

        $segundoPagamentoResponse = $this->actingAs($this->admin)
            ->postJson(route('contas.pagar.pagamentos.store', $conta), [
                'valor' => 60,
                'metodo' => 'transferencia',
                'observacoes' => 'Quitacao',
            ])
            ->assertCreated();

        $segundoPagamentoId = $segundoPagamentoResponse->json('pagamento.id');

        $conta->refresh();
        $this->assertSame('quitada', $conta->status);
        $this->assertEquals(100.0, (float) $conta->valor_pago);
        $this->assertEquals(0.0, $conta->saldo_devedor);

        $segundoPagamento = PagamentoPagar::query()->findOrFail($segundoPagamentoId);

        $this->actingAs($this->admin)
            ->deleteJson(route('contas.pagar.pagamentos.destroy', $segundoPagamento))
            ->assertOk();

        $conta->refresh();
        $this->assertSame('parcial', $conta->status);
        $this->assertEquals(40.0, (float) $conta->valor_pago);
        $this->assertEquals(60.0, $conta->saldo_devedor);

        $primeiroPagamento = PagamentoPagar::query()->findOrFail($primeiroPagamentoId);

        $this->actingAs($this->admin)
            ->deleteJson(route('contas.pagar.pagamentos.destroy', $primeiroPagamento))
            ->assertOk();

        $conta->refresh();
        $this->assertSame('aberta', $conta->status);
        $this->assertEquals(0.0, (float) $conta->valor_pago);
        $this->assertEquals(100.0, $conta->saldo_devedor);
    }

    public function test_pagamento_promissoria_com_abatimento_sincroniza_parcelas_e_conta(): void
    {
        $cliente = $this->criarCliente('promissoria@example.com', '20000000002');
        $venda = $this->criarVenda($cliente, 200);
        $conta = $this->criarContaReceber($cliente, $venda, 200);
        $promissoria = $this->criarPromissoria($cliente, $venda, $conta, 200);

        $cliente->refresh();
        $this->assertEquals(800.0, (float) $cliente->credito_disponivel);

        $parcela1 = PromissoriaParcela::create([
            'empresa_id' => $this->empresa->id,
            'promissoria_id' => $promissoria->id,
            'numero' => 1,
            'valor_original' => 100,
            'valor_pago' => 0,
            'valor_abatimento' => 0,
            'data_vencimento' => now()->addDays(30)->toDateString(),
            'status' => 'aberta',
        ]);

        $parcela2 = PromissoriaParcela::create([
            'empresa_id' => $this->empresa->id,
            'promissoria_id' => $promissoria->id,
            'numero' => 2,
            'valor_original' => 100,
            'valor_pago' => 0,
            'valor_abatimento' => 0,
            'data_vencimento' => now()->addDays(60)->toDateString(),
            'status' => 'aberta',
        ]);

        $this->actingAs($this->admin)
            ->postJson(route('contas.receber.pagamentos.store', $conta), [
                'valor' => 100,
                'valor_abatimento' => 50,
                'promissoria_parcela_id' => $parcela1->id,
                'metodo' => 'pix',
                'observacoes' => 'Pagamento com abatimento',
            ])
            ->assertCreated();

        $parcela1->refresh();
        $parcela2->refresh();
        $promissoria->refresh();
        $conta->refresh();
        $cliente->refresh();

        $this->assertSame('quitada', $parcela1->status);
        $this->assertEquals(100.0, (float) $parcela1->valor_pago);
        $this->assertEquals(0.0, $parcela1->saldo_devedor);

        $this->assertSame('parcial', $parcela2->status);
        $this->assertEquals(50.0, (float) $parcela2->valor_abatimento);
        $this->assertEquals(50.0, $parcela2->saldo_devedor);

        $this->assertSame('parcial', $promissoria->status);
        $this->assertEquals(50.0, $promissoria->saldo_devedor);

        $this->assertSame('parcial', $conta->status);
        $this->assertEquals(150.0, (float) $conta->valor_pago);
        $this->assertEquals(50.0, $conta->saldo_devedor);
        $this->assertSame($parcela2->data_vencimento->toDateString(), $conta->data_vencimento->toDateString());
        $this->assertEquals(950.0, (float) $cliente->credito_disponivel);
    }

    public function test_pagamento_promissoria_rejeita_parcela_de_outra_conta(): void
    {
        $clienteA = $this->criarCliente('promissoria.a@example.com', '20000000003');
        $vendaA = $this->criarVenda($clienteA, 100);
        $contaA = $this->criarContaReceber($clienteA, $vendaA, 100);

        $clienteB = $this->criarCliente('promissoria.b@example.com', '20000000004');
        $vendaB = $this->criarVenda($clienteB, 120);
        $contaB = $this->criarContaReceber($clienteB, $vendaB, 120);
        $promissoriaB = $this->criarPromissoria($clienteB, $vendaB, $contaB, 120);

        $parcelaB = PromissoriaParcela::create([
            'empresa_id' => $this->empresa->id,
            'promissoria_id' => $promissoriaB->id,
            'numero' => 1,
            'valor_original' => 120,
            'valor_pago' => 0,
            'valor_abatimento' => 0,
            'data_vencimento' => now()->addDays(30)->toDateString(),
            'status' => 'aberta',
        ]);

        $this->actingAs($this->admin)
            ->postJson(route('contas.receber.pagamentos.store', $contaA), [
                'valor' => 50,
                'promissoria_parcela_id' => $parcelaB->id,
                'metodo' => 'pix',
            ])
            ->assertUnprocessable();
    }

    private function criarUsuarioDaEmpresa(Empresa $empresa, string $role): User
    {
        $token = Str::lower(Str::random(8));

        $user = User::factory()->create([
            'username' => 'fin_'.$role.'_'.$token,
            'email' => 'fin.'.$role.'.'.$token.'@example.com',
        ]);

        $user->assignRole($role);

        $perfil = Perfil::query()->where('nome', $role)->first();

        $user->usuarioVendas()->create([
            'empresa_id' => $empresa->id,
            'perfil_id' => $perfil?->id,
            'ativo' => true,
            'data_contratacao' => now()->toDateString(),
        ]);

        return $user;
    }

    private function criarCliente(string $email, string $documento): Cliente
    {
        return Cliente::create([
            'empresa_id' => $this->empresa->id,
            'tipo' => 'PF',
            'nome' => 'Cliente '.Str::lower(Str::random(6)),
            'email' => $email,
            'telefone' => '(11) 98888-0000',
            'cpf_cnpj' => $documento,
            'endereco' => 'Rua Financeiro',
            'numero' => '100',
            'bairro' => 'Centro',
            'cidade' => 'Sao Paulo',
            'estado' => 'SP',
            'cep' => '01000000',
            'limite_credito' => 1000,
            'credito_disponivel' => 1000,
            'percentual_multa_atraso_padrao' => 2,
            'percentual_juros_dia_padrao' => 0.0333,
            'ativo' => true,
        ]);
    }

    private function criarVenda(Cliente $cliente, float $total): Venda
    {
        return Venda::create([
            'empresa_id' => $this->empresa->id,
            'cliente_id' => $cliente->id,
            'vendedor_id' => $this->admin->usuarioVendas->id,
            'status' => 'pendente',
            'subtotal' => $total,
            'desconto' => 0,
            'frete' => 0,
            'total' => $total,
            'lucro_total' => $total * 0.2,
        ]);
    }

    private function criarContaReceber(Cliente $cliente, Venda $venda, float $valor): ContaReceber
    {
        return ContaReceber::create([
            'empresa_id' => $this->empresa->id,
            'venda_numero' => $venda->numero,
            'cliente_id' => $cliente->id,
            'valor_original' => $valor,
            'valor_pago' => 0,
            'valor_juros' => 0,
            'data_vencimento' => now()->addDays(15)->toDateString(),
            'status' => 'aberta',
            'observacoes' => 'Conta de teste',
        ]);
    }

    private function criarPromissoria(Cliente $cliente, Venda $venda, ContaReceber $conta, float $valorFinanciado): Promissoria
    {
        return Promissoria::create([
            'empresa_id' => $this->empresa->id,
            'conta_id' => $conta->id,
            'venda_numero' => $venda->numero,
            'cliente_id' => $cliente->id,
            'valor_entrada' => 0,
            'valor_financiado' => $valorFinanciado,
            'quantidade_parcelas' => 2,
            'intervalo_dias' => 30,
            'primeira_parcela_vencimento' => now()->addDays(30)->toDateString(),
            'percentual_multa_atraso' => 2,
            'percentual_juros_dia' => 0.0333,
            'status' => 'aberta',
            'observacoes' => 'Promissoria de teste',
        ]);
    }
}
