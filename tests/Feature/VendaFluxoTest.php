<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\PagamentoReceber;
use App\Models\Promissoria;
use App\Models\Produto;
use App\Models\User;
use App\Models\Venda;
use Database\Seeders\PerfilUsuarioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendaFluxoTest extends TestCase
{
    use RefreshDatabase;

    public function test_cria_venda_itens_baixa_estoque_e_gera_conta_receber(): void
    {
        $this->seed(PerfilUsuarioSeeder::class);

        $empresa = Empresa::query()->where('slug', 'administrar')->firstOrFail();

        $user = User::factory()->create([
            'username' => 'gerente_teste',
            'email' => 'gerente.teste@example.com',
        ]);

        $user->assignRole('admin');

        $user->usuarioVendas()->create([
            'empresa_id' => $empresa->id,
            'perfil_id' => null,
            'ativo' => true,
            'data_contratacao' => now()->toDateString(),
        ]);

        $cliente = Cliente::create([
            'empresa_id' => $empresa->id,
            'tipo' => 'PF',
            'nome' => 'Cliente Teste',
            'email' => 'cliente.teste@example.com',
            'telefone' => '(11) 99999-0000',
            'cpf_cnpj' => '12345678901',
            'endereco' => 'Rua A',
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

        $produto = Produto::create([
            'empresa_id' => $empresa->id,
            'codigo' => 'SKU-001',
            'nome' => 'Produto Teste',
            'preco_custo' => 10,
            'preco_venda' => 20,
            'margem_lucro' => 50,
            'custo_medio' => 10,
            'estoque_atual' => 10,
            'estoque_minimo' => 1,
            'ativo' => true,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('vendas.store'), [
                'cliente_id' => $cliente->id,
                'status' => 'pendente',
                'desconto' => 5,
                'frete' => 3,
                'data_vencimento' => now()->addDays(10)->toDateString(),
                'itens' => [
                    [
                        'produto_id' => $produto->id,
                        'quantidade' => 2,
                        'preco_unitario' => 20,
                    ],
                ],
            ]);

        $response->assertRedirect();

        $venda = Venda::query()->latest('numero')->firstOrFail();

        $this->assertDatabaseHas('vendas', [
            'numero' => $venda->numero,
            'empresa_id' => $empresa->id,
            'cliente_id' => $cliente->id,
            'subtotal' => 40.00,
            'total' => 38.00,
        ]);

        $this->assertDatabaseHas('item_vendas', [
            'venda_numero' => $venda->numero,
            'produto_id' => $produto->id,
            'quantidade' => 2,
            'valor_total' => 40.00,
        ]);

        $this->assertDatabaseHas('produtos', [
            'id' => $produto->id,
            'estoque_atual' => 8,
        ]);

        $this->assertDatabaseHas('contas_receber', [
            'venda_numero' => $venda->numero,
            'cliente_id' => $cliente->id,
            'valor_original' => 38.00,
            'status' => 'aberta',
        ]);
    }

    public function test_cria_venda_com_promissoria_e_gera_parcelas(): void
    {
        $this->seed(PerfilUsuarioSeeder::class);

        $empresa = Empresa::query()->where('slug', 'administrar')->firstOrFail();

        $user = User::factory()->create([
            'username' => 'gerente_promissoria',
            'email' => 'gerente.promissoria@example.com',
        ]);

        $user->assignRole('admin');

        $user->usuarioVendas()->create([
            'empresa_id' => $empresa->id,
            'perfil_id' => null,
            'ativo' => true,
            'data_contratacao' => now()->toDateString(),
        ]);

        $cliente = Cliente::create([
            'empresa_id' => $empresa->id,
            'tipo' => 'PF',
            'nome' => 'Cliente Promissoria',
            'email' => 'cliente.promissoria@example.com',
            'telefone' => '(11) 99999-1111',
            'cpf_cnpj' => '12345678902',
            'endereco' => 'Rua B',
            'numero' => '200',
            'bairro' => 'Centro',
            'cidade' => 'Sao Paulo',
            'estado' => 'SP',
            'cep' => '01000001',
            'limite_credito' => 1000,
            'credito_disponivel' => 1000,
            'percentual_multa_atraso_padrao' => 2,
            'percentual_juros_dia_padrao' => 0.0333,
            'ativo' => true,
        ]);

        $produto = Produto::create([
            'empresa_id' => $empresa->id,
            'codigo' => 'SKU-PRM-001',
            'nome' => 'Produto Promissoria',
            'preco_custo' => 10,
            'preco_venda' => 20,
            'margem_lucro' => 50,
            'custo_medio' => 10,
            'estoque_atual' => 10,
            'estoque_minimo' => 1,
            'ativo' => true,
        ]);

        $primeiraParcela = now()->addDays(30)->toDateString();

        $response = $this
            ->actingAs($user)
            ->post(route('vendas.store'), [
                'cliente_id' => $cliente->id,
                'status' => 'pendente',
                'desconto' => 0,
                'frete' => 0,
                'gerar_promissoria' => true,
                'valor_entrada' => 8,
                'quantidade_parcelas' => 3,
                'intervalo_dias' => 30,
                'data_primeira_parcela' => $primeiraParcela,
                'observacoes_promissoria' => 'Cliente assina na entrega.',
                'itens' => [
                    [
                        'produto_id' => $produto->id,
                        'quantidade' => 2,
                        'preco_unitario' => 20,
                    ],
                ],
            ]);

        $venda = Venda::query()->latest('numero')->firstOrFail();
        $response->assertRedirect(route('vendas.promissoria.imprimir', ['venda' => $venda, 'auto_print' => 1]));

        $promissoria = Promissoria::query()->where('venda_numero', $venda->numero)->first();

        $this->assertNotNull($promissoria);
        $this->assertDatabaseHas('contas_receber', [
            'venda_numero' => $venda->numero,
            'cliente_id' => $cliente->id,
            'valor_original' => 32.00,
            'status' => 'aberta',
        ]);
        $this->assertEquals(8.0, (float) $promissoria->valor_entrada);
        $this->assertEquals(32.0, (float) $promissoria->valor_financiado);
        $this->assertCount(3, $promissoria->parcelas);
        $this->assertEquals(32.0, (float) $promissoria->parcelas->sum('valor_original'));

        $cliente->refresh();
        $this->assertEquals(968.0, (float) $cliente->credito_disponivel);

        $this->actingAs($user)
            ->get(route('vendas.show', $venda))
            ->assertOk()
            ->assertSee('Promissória', false)
            ->assertSee('Cliente assina na entrega.', false);

        $this->actingAs($user)
            ->get(route('vendas.promissoria.imprimir', $venda))
            ->assertOk()
            ->assertSee('Nota Promissória', false)
            ->assertSee('Cliente Promissoria', false);
    }

    public function test_cria_venda_com_promissoria_usa_taxas_padrao_do_cliente(): void
    {
        $this->seed(PerfilUsuarioSeeder::class);

        $empresa = Empresa::query()->where('slug', 'administrar')->firstOrFail();

        $user = User::factory()->create([
            'username' => 'gerente_taxas',
            'email' => 'gerente.taxas@example.com',
        ]);

        $user->assignRole('admin');

        $user->usuarioVendas()->create([
            'empresa_id' => $empresa->id,
            'perfil_id' => null,
            'ativo' => true,
            'data_contratacao' => now()->toDateString(),
        ]);

        $cliente = Cliente::create([
            'empresa_id' => $empresa->id,
            'tipo' => 'PF',
            'nome' => 'Cliente Taxas',
            'email' => 'cliente.taxas@example.com',
            'telefone' => '(11) 99999-2222',
            'cpf_cnpj' => '12345678903',
            'endereco' => 'Rua C',
            'numero' => '300',
            'bairro' => 'Centro',
            'cidade' => 'Sao Paulo',
            'estado' => 'SP',
            'cep' => '01000002',
            'limite_credito' => 1000,
            'credito_disponivel' => 1000,
            'percentual_multa_atraso_padrao' => 3.5,
            'percentual_juros_dia_padrao' => 0.075,
            'ativo' => true,
        ]);

        $produto = Produto::create([
            'empresa_id' => $empresa->id,
            'codigo' => 'SKU-PRM-002',
            'nome' => 'Produto Taxas',
            'preco_custo' => 10,
            'preco_venda' => 20,
            'margem_lucro' => 50,
            'custo_medio' => 10,
            'estoque_atual' => 10,
            'estoque_minimo' => 1,
            'ativo' => true,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('vendas.store'), [
                'cliente_id' => $cliente->id,
                'status' => 'pendente',
                'desconto' => 0,
                'frete' => 0,
                'gerar_promissoria' => true,
                'valor_entrada' => 5,
                'quantidade_parcelas' => 2,
                'intervalo_dias' => 30,
                'data_primeira_parcela' => now()->addDays(30)->toDateString(),
                'itens' => [
                    [
                        'produto_id' => $produto->id,
                        'quantidade' => 1,
                        'preco_unitario' => 20,
                    ],
                ],
            ]);

        $venda = Venda::query()->latest('numero')->firstOrFail();
        $response->assertRedirect(route('vendas.promissoria.imprimir', ['venda' => $venda, 'auto_print' => 1]));
        $promissoria = Promissoria::query()->where('venda_numero', $venda->numero)->first();

        $this->assertNotNull($promissoria);
        $this->assertEquals(3.5, (float) $venda->percentual_multa_atraso_promissoria);
        $this->assertEquals(0.075, (float) $venda->percentual_juros_dia_promissoria);
        $this->assertEquals(3.5, (float) $promissoria->percentual_multa_atraso);
        $this->assertEquals(0.075, (float) $promissoria->percentual_juros_dia);
    }

    public function test_cria_venda_a_vista_registra_pagamento_e_redireciona_para_recibo(): void
    {
        $this->seed(PerfilUsuarioSeeder::class);

        $empresa = Empresa::query()->where('slug', 'administrar')->firstOrFail();

        $user = User::factory()->create([
            'username' => 'gerente_avista',
            'email' => 'gerente.avista@example.com',
        ]);

        $user->assignRole('admin');

        $user->usuarioVendas()->create([
            'empresa_id' => $empresa->id,
            'perfil_id' => null,
            'ativo' => true,
            'data_contratacao' => now()->toDateString(),
        ]);

        $cliente = Cliente::create([
            'empresa_id' => $empresa->id,
            'tipo' => 'PF',
            'nome' => 'Cliente Avista',
            'email' => 'cliente.avista@example.com',
            'telefone' => '(11) 99999-3333',
            'cpf_cnpj' => '12345678904',
            'endereco' => 'Rua D',
            'numero' => '400',
            'bairro' => 'Centro',
            'cidade' => 'Sao Paulo',
            'estado' => 'SP',
            'cep' => '01000003',
            'limite_credito' => 1000,
            'credito_disponivel' => 1000,
            'percentual_multa_atraso_padrao' => 2,
            'percentual_juros_dia_padrao' => 0.0333,
            'ativo' => true,
        ]);

        $produto = Produto::create([
            'empresa_id' => $empresa->id,
            'codigo' => 'SKU-AV-001',
            'nome' => 'Produto Avista',
            'preco_custo' => 10,
            'preco_venda' => 20,
            'margem_lucro' => 50,
            'custo_medio' => 10,
            'estoque_atual' => 10,
            'estoque_minimo' => 1,
            'ativo' => true,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('vendas.store'), [
                'cliente_id' => $cliente->id,
                'status' => 'pendente',
                'modalidade_pagamento' => 'avista',
                'metodo_pagamento_avista' => 'cartao',
                'desconto' => 0,
                'frete' => 0,
                'itens' => [
                    [
                        'produto_id' => $produto->id,
                        'quantidade' => 1,
                        'preco_unitario' => 20,
                    ],
                ],
            ]);

        $venda = Venda::query()->latest('numero')->firstOrFail();
        $response->assertRedirect(route('vendas.recibo', ['venda' => $venda, 'auto_print' => 1]));

        $conta = $venda->contaReceber()->first();
        $pagamento = PagamentoReceber::query()->where('conta_id', $conta->id)->first();

        $this->assertNotNull($pagamento);
        $this->assertSame('quitada', $conta->refresh()->status);
        $this->assertEquals(20.0, (float) $conta->valor_original);
        $this->assertEquals(20.0, (float) $conta->valor_pago);
        $this->assertSame('cartao', $pagamento->metodo);

        $cliente->refresh();
        $this->assertEquals(1000.0, (float) $cliente->credito_disponivel);

        $this->actingAs($user)
            ->get(route('vendas.recibo', $venda))
            ->assertOk()
            ->assertSee('Recibo de Venda', false)
            ->assertSee('Cartão', false)
            ->assertSee('Cliente Avista', false);
    }
}
