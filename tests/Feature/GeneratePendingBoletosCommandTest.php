<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\ContaReceber;
use App\Models\Empresa;
use App\Models\Venda;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GeneratePendingBoletosCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_dry_run_lists_only_safe_pending_accounts_without_calling_asaas(): void
    {
        ['empresa' => $empresa, 'cliente' => $clientePendente] = $this->criarEmpresaECliente('pendente');
        ['cliente' => $clienteInconsistente] = $this->criarEmpresaECliente('inconsistente', $empresa);

        $contaPendente = $this->criarConta($empresa, $clientePendente);
        $contaInconsistente = $this->criarConta($empresa, $clienteInconsistente, [
            'gateway_checkout_url' => 'https://example.com/boleto/sem-id',
        ]);

        Http::fake();

        $exitCode = Artisan::call('billing:generate-pending-boletos');
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Cliente pendente', $output);
        $this->assertStringNotContainsString('Cliente inconsistente', $output);
        $this->assertStringContainsString('Dry run', $output);
        $this->assertDatabaseHas('contas_receber', [
            'id' => $contaPendente->id,
            'gateway_payment_id' => null,
        ]);
        $this->assertDatabaseHas('contas_receber', [
            'id' => $contaInconsistente->id,
            'gateway_payment_id' => null,
            'gateway_checkout_url' => 'https://example.com/boleto/sem-id',
        ]);
        Http::assertNothingSent();
    }

    public function test_execute_generates_the_missing_boleto(): void
    {
        Config::set('billing.customer_boleto_provider', 'asaas');
        Config::set('billing.asaas.base_url', 'https://api.asaas.com/v3');

        ['empresa' => $empresa, 'cliente' => $cliente] = $this->criarEmpresaECliente('geracao');
        $conta = $this->criarConta($empresa, $cliente);

        Http::fake([
            'https://api.asaas.com/v3/customers' => Http::response([
                'id' => 'cus_pending_123',
            ]),
            'https://api.asaas.com/v3/payments' => Http::response([
                'id' => 'pay_pending_123',
                'customer' => 'cus_pending_123',
                'value' => '150.00',
                'status' => 'PENDING',
                'dueDate' => now()->addDays(7)->toDateString(),
                'invoiceUrl' => 'https://example.com/fatura/pay_pending_123',
                'bankSlipUrl' => 'https://example.com/boleto/pay_pending_123',
            ]),
        ]);

        $exitCode = Artisan::call('billing:generate-pending-boletos', ['--execute' => true]);
        $output = Artisan::output();

        $this->assertSame(0, $exitCode, $output);
        $this->assertStringContainsString('Conta #'.$conta->id.' processada com sucesso.', $output);
        $this->assertStringContainsString('1 boleto(s) gerado(s), 0 link(s) recuperado(s) e 0 falha(s).', $output);

        $conta->refresh();
        $cliente->refresh();

        $this->assertSame('asaas', $cliente->gateway);
        $this->assertSame('cus_pending_123', $cliente->gateway_customer_id);
        $this->assertSame('asaas', $conta->gateway);
        $this->assertSame('pay_pending_123', $conta->gateway_payment_id);
        $this->assertSame('https://example.com/boleto/pay_pending_123', $conta->boleto_url);
        Http::assertSentCount(2);
        Http::assertSent(function (Request $request) use ($conta): bool {
            return $request->url() === 'https://api.asaas.com/v3/payments'
                && $request['billingType'] === 'BOLETO'
                && $request['externalReference'] === 'conta_receber:'.$conta->id.':empresa:'.$conta->empresa_id;
        });
    }

    public function test_execute_recovers_the_link_for_an_existing_asaas_payment(): void
    {
        Config::set('billing.customer_boleto_provider', 'asaas');
        Config::set('billing.asaas.base_url', 'https://api.asaas.com/v3');

        ['empresa' => $empresa, 'cliente' => $cliente] = $this->criarEmpresaECliente('recuperacao');
        $cliente->update([
            'gateway' => 'asaas',
            'gateway_customer_id' => 'cus_existing_123',
        ]);
        $conta = $this->criarConta($empresa, $cliente, [
            'gateway' => 'asaas',
            'gateway_payment_id' => 'pay_existing_123',
        ]);

        Http::fake([
            'https://api.asaas.com/v3/customers/cus_existing_123' => Http::response([
                'id' => 'cus_existing_123',
            ]),
            'https://api.asaas.com/v3/payments/pay_existing_123' => Http::response([
                'id' => 'pay_existing_123',
                'customer' => 'cus_existing_123',
                'value' => '150.00',
                'status' => 'PENDING',
                'dueDate' => now()->addDays(7)->toDateString(),
                'invoiceUrl' => 'https://example.com/fatura/pay_existing_123',
                'bankSlipUrl' => 'https://example.com/boleto/pay_existing_123',
            ]),
        ]);

        $exitCode = Artisan::call('billing:generate-pending-boletos', ['--execute' => true]);
        $output = Artisan::output();

        $this->assertSame(0, $exitCode, $output);
        $this->assertStringContainsString('0 boleto(s) gerado(s), 1 link(s) recuperado(s) e 0 falha(s).', $output);

        $conta->refresh();

        $this->assertSame('pay_existing_123', $conta->gateway_payment_id);
        $this->assertSame('https://example.com/boleto/pay_existing_123', $conta->boleto_url);
        Http::assertSentCount(2);
        Http::assertSent(function (Request $request): bool {
            return $request->method() === 'PUT'
                && $request->url() === 'https://api.asaas.com/v3/payments/pay_existing_123';
        });
    }

    public function test_limit_reports_remaining_pending_accounts(): void
    {
        ['empresa' => $empresa, 'cliente' => $primeiroCliente] = $this->criarEmpresaECliente('limite-um');
        ['cliente' => $segundoCliente] = $this->criarEmpresaECliente('limite-dois', $empresa);
        $this->criarConta($empresa, $primeiroCliente);
        $this->criarConta($empresa, $segundoCliente);

        Http::fake();

        $exitCode = Artisan::call('billing:generate-pending-boletos', ['--limit' => 1]);
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Lote limitado: 1 de 2 conta(s) selecionada(s). Restam 1 para uma proxima execucao.', $output);
        Http::assertNothingSent();
    }

    /** @return array{empresa: Empresa, cliente: Cliente} */
    private function criarEmpresaECliente(string $sufixo, ?Empresa $empresaExistente = null): array
    {
        $empresa = $empresaExistente ?: Empresa::query()->create([
            'nome' => 'Loja Boletos Pendentes',
            'slug' => 'loja-boletos-pendentes',
            'asaas_boleto_api_key' => 'token-loja-teste',
            'ativa' => true,
        ]);

        $cliente = Cliente::query()->create([
            'empresa_id' => $empresa->id,
            'tipo' => 'PF',
            'nome' => 'Cliente '.$sufixo,
            'email' => 'cliente.'.$sufixo.'@example.com',
            'telefone' => '(11) 98888-4545',
            'cpf_cnpj' => match ($sufixo) {
                'pendente' => '52998224725',
                'limite-dois' => '11144477735',
                default => '24971563792',
            },
            'endereco' => 'Rua dos Boletos',
            'numero' => '100',
            'bairro' => 'Centro',
            'cidade' => 'Sao Paulo',
            'estado' => 'SP',
            'cep' => '01010010',
            'limite_credito' => 1000,
            'credito_disponivel' => 1000,
            'percentual_multa_atraso_padrao' => 2,
            'percentual_juros_dia_padrao' => 0.0333,
            'ativo' => true,
        ]);

        return ['empresa' => $empresa, 'cliente' => $cliente];
    }

    private function criarConta(Empresa $empresa, Cliente $cliente, array $attributes = []): ContaReceber
    {
        $venda = Venda::query()->create([
            'empresa_id' => $empresa->id,
            'cliente_id' => $cliente->id,
            'status' => 'pendente',
            'subtotal' => 150,
            'desconto' => 0,
            'frete' => 0,
            'total' => 150,
        ]);

        return ContaReceber::query()->create(array_merge([
            'empresa_id' => $empresa->id,
            'venda_numero' => $venda->numero,
            'cliente_id' => $cliente->id,
            'valor_original' => 150,
            'valor_pago' => 0,
            'valor_juros' => 0,
            'data_vencimento' => now()->addDays(7)->toDateString(),
            'status' => 'aberta',
            'forma_recebimento' => 'boleto',
        ], $attributes));
    }
}