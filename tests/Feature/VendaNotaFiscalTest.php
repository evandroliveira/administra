<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Produto;
use App\Models\User;
use App\Models\Venda;
use App\Support\Fiscal\FiscalService;
use Database\Seeders\PerfilUsuarioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class VendaNotaFiscalTest extends TestCase
{
    use RefreshDatabase;

    protected Empresa $empresa;

    protected User $user;

    protected Cliente $cliente;

    protected Produto $produto;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(PerfilUsuarioSeeder::class);

        $this->empresa = Empresa::query()->where('slug', 'administrar')->firstOrFail();
        $this->user = User::factory()->create([
            'username' => 'fiscal_admin',
            'email' => 'fiscal.admin@example.com',
        ]);
        $this->user->assignRole('admin');
        $this->user->usuarioVendas()->create([
            'empresa_id' => $this->empresa->id,
            'perfil_id' => null,
            'ativo' => true,
            'data_contratacao' => now()->toDateString(),
        ]);

        $this->cliente = Cliente::create([
            'empresa_id' => $this->empresa->id,
            'tipo' => 'PF',
            'nome' => 'Cliente Fiscal',
            'email' => 'cliente.fiscal@example.com',
            'telefone' => '(11) 99999-4444',
            'cpf_cnpj' => '12345678909',
            'endereco' => 'Rua Fiscal',
            'numero' => '500',
            'bairro' => 'Centro',
            'cidade' => 'Sao Paulo',
            'estado' => 'SP',
            'cep' => '01000005',
            'limite_credito' => 1000,
            'credito_disponivel' => 1000,
            'ativo' => true,
        ]);

        $this->produto = Produto::create([
            'empresa_id' => $this->empresa->id,
            'codigo' => 'FISC-001',
            'nome' => 'Produto Fiscal',
            'preco_custo' => 10,
            'preco_venda' => 20,
            'margem_lucro' => 50,
            'custo_medio' => 10,
            'estoque_atual' => 10,
            'estoque_minimo' => 1,
            'ativo' => true,
        ]);
    }

    public function test_cria_venda_sem_nota_fiscal_mantem_status_nao_emitir(): void
    {
        $response = $this->actingAs($this->user)->post(route('vendas.store'), $this->payloadVenda());

        $response->assertRedirect();

        $venda = Venda::query()->latest('numero')->firstOrFail();
        $this->assertFalse($venda->emitir_nota_fiscal);
        $this->assertSame('nao_emitir', $venda->status_nota_fiscal);
    }

    public function test_formulario_de_venda_exibe_controle_fiscal_quando_recurso_habilitado(): void
    {
        Config::set('services.fiscal.enabled', true);
        Config::set('services.fiscal.provider', 'mock');

        $this->actingAs($this->user)
            ->get(route('vendas.create'))
            ->assertOk()
            ->assertSee('Nota Fiscal', false)
            ->assertSee('Emitir nota fiscal desta venda', false);
    }

    public function test_cria_venda_ignora_nota_fiscal_quando_funcionalidade_desabilitada(): void
    {
        $response = $this->actingAs($this->user)->post(route('vendas.store'), $this->payloadVenda([
            'emitir_nota_fiscal' => 1,
        ]));

        $response->assertRedirect();

        $venda = Venda::query()->latest('numero')->firstOrFail();
        $this->assertFalse($venda->emitir_nota_fiscal);
        $this->assertSame('nao_emitir', $venda->status_nota_fiscal);
    }

    public function test_cria_venda_com_nota_fiscal_sem_integracao_fica_pendente(): void
    {
        Config::set('services.fiscal.enabled', true);
        Config::set('services.fiscal.provider', '');

        $response = $this->actingAs($this->user)->post(route('vendas.store'), $this->payloadVenda([
            'status' => 'confirmada',
            'emitir_nota_fiscal' => 1,
        ]));

        $response->assertRedirect();

        $venda = Venda::query()->latest('numero')->firstOrFail();
        $this->assertTrue($venda->emitir_nota_fiscal);
        $this->assertSame('pendente', $venda->status_nota_fiscal);
        $this->assertStringContainsString('não configurada', mb_strtolower($venda->nota_fiscal_mensagem));
    }

    public function test_cria_venda_com_nota_fiscal_mock_emite_automaticamente(): void
    {
        Config::set('services.fiscal.enabled', true);
        Config::set('services.fiscal.provider', 'mock');
        Config::set('services.fiscal.series', '9');

        $response = $this->actingAs($this->user)->post(route('vendas.store'), $this->payloadVenda([
            'status' => 'confirmada',
            'emitir_nota_fiscal' => 1,
        ]));

        $response->assertRedirect();

        $venda = Venda::query()->latest('numero')->firstOrFail();
        $this->assertTrue($venda->emitir_nota_fiscal);
        $this->assertSame('emitida', $venda->status_nota_fiscal);
        $this->assertSame('9', $venda->nota_fiscal_serie);
        $this->assertNotEmpty($venda->nota_fiscal_numero);
        $this->assertNotEmpty($venda->nota_fiscal_chave);
        $this->assertNotEmpty($venda->nota_fiscal_protocolo);
        $this->assertNotNull($venda->nota_fiscal_emitida_em);
    }

    public function test_emite_nota_fiscal_manual_a_partir_da_listagem(): void
    {
        Config::set('services.fiscal.enabled', true);
        Config::set('services.fiscal.provider', 'mock');

        $this->actingAs($this->user)->post(route('vendas.store'), $this->payloadVenda([
            'status' => 'confirmada',
        ]));
        $venda = Venda::query()->latest('numero')->firstOrFail();

        $response = $this->actingAs($this->user)->post(route('vendas.nota-fiscal.emitir', $venda), [
            'next' => '/vendas?status=confirmada',
        ]);

        $response->assertRedirect('/vendas?status=confirmada');

        $venda->refresh();
        $this->assertTrue($venda->emitir_nota_fiscal);
        $this->assertSame('emitida', $venda->status_nota_fiscal);
        $this->assertNotEmpty($venda->nota_fiscal_numero);
    }

    public function test_atualizar_status_para_confirmada_emite_nota_pendente_automaticamente(): void
    {
        Config::set('services.fiscal.enabled', true);
        Config::set('services.fiscal.provider', 'mock');
        Config::set('services.fiscal.series', '7');

        $this->actingAs($this->user)->post(route('vendas.store'), $this->payloadVenda([
            'status' => 'pendente',
            'emitir_nota_fiscal' => 1,
        ]));

        $venda = Venda::query()->latest('numero')->firstOrFail();
        $this->assertSame('pendente', $venda->status_nota_fiscal);

        $response = $this->actingAs($this->user)->patch(route('vendas.status.update', $venda), [
            'status' => 'confirmada',
        ]);

        $response->assertRedirect(route('vendas.show', $venda));

        $venda->refresh();
        $this->assertSame('confirmada', $venda->status);
        $this->assertTrue($venda->emitir_nota_fiscal);
        $this->assertSame('emitida', $venda->status_nota_fiscal);
        $this->assertSame('7', $venda->nota_fiscal_serie);
        $this->assertNotEmpty($venda->nota_fiscal_numero);
    }

    public function test_atualizar_status_a_partir_da_listagem_emite_nota_pendente_automaticamente(): void
    {
        Config::set('services.fiscal.enabled', true);
        Config::set('services.fiscal.provider', 'mock');

        $this->actingAs($this->user)->post(route('vendas.store'), $this->payloadVenda([
            'status' => 'pendente',
            'emitir_nota_fiscal' => 1,
        ]));

        $venda = Venda::query()->latest('numero')->firstOrFail();

        $response = $this->actingAs($this->user)->patch(route('vendas.status.update', $venda), [
            'status' => 'confirmada',
            'next' => '/vendas?status=pendente',
        ]);

        $response->assertRedirect('/vendas?status=pendente');

        $venda->refresh();
        $this->assertSame('confirmada', $venda->status);
        $this->assertSame('emitida', $venda->status_nota_fiscal);
        $this->assertNotEmpty($venda->nota_fiscal_numero);
    }

    public function test_atualizar_status_para_confirmada_mantem_nota_pendente_sem_integracao(): void
    {
        Config::set('services.fiscal.enabled', true);
        Config::set('services.fiscal.provider', '');

        $this->actingAs($this->user)->post(route('vendas.store'), $this->payloadVenda([
            'status' => 'pendente',
            'emitir_nota_fiscal' => 1,
        ]));

        $venda = Venda::query()->latest('numero')->firstOrFail();

        $this->actingAs($this->user)->patch(route('vendas.status.update', $venda), [
            'status' => 'confirmada',
        ])->assertRedirect(route('vendas.show', $venda));

        $venda->refresh();
        $this->assertSame('confirmada', $venda->status);
        $this->assertTrue($venda->emitir_nota_fiscal);
        $this->assertSame('pendente', $venda->status_nota_fiscal);
        $this->assertStringContainsString('não configurada', mb_strtolower($venda->nota_fiscal_mensagem));
    }

    public function test_listagem_exibe_status_fiscal_e_acao_de_emissao(): void
    {
        Config::set('services.fiscal.enabled', true);
        Config::set('services.fiscal.provider', 'mock');

        $this->actingAs($this->user)->post(route('vendas.store'), $this->payloadVenda([
            'status' => 'confirmada',
        ]));

        $this->actingAs($this->user)
            ->get(route('vendas.index'))
            ->assertOk()
            ->assertSee('Nota Fiscal', false)
            ->assertSee('Atualizar', false)
            ->assertSee('Emitir NF', false)
            ->assertSee('nao emitir', false);
    }

    public function test_detalhe_da_venda_exibe_formulario_de_atualizacao_de_status(): void
    {
        Config::set('services.fiscal.enabled', true);
        Config::set('services.fiscal.provider', 'mock');

        $this->actingAs($this->user)->post(route('vendas.store'), $this->payloadVenda([
            'status' => 'pendente',
            'emitir_nota_fiscal' => 1,
        ]));

        $venda = Venda::query()->latest('numero')->firstOrFail();

        $this->actingAs($this->user)
            ->get(route('vendas.show', $venda))
            ->assertOk()
            ->assertSee('Atualizar status', false)
            ->assertSee('Salvar status', false);
    }

    public function test_custom_api_usa_header_de_autenticacao_configurado(): void
    {
        Config::set('services.fiscal.enabled', true);
        Config::set('services.fiscal.provider', 'custom_api');
        Config::set('services.fiscal.provider_name', 'ERP Fiscal Interno');
        Config::set('services.fiscal.api_url', 'https://api.exemplo.local/notas');
        Config::set('services.fiscal.token', 'token-fiscal');
        Config::set('services.fiscal.auth_header', 'X-API-Key');
        Config::set('services.fiscal.auth_prefix', '');
        Config::set('services.fiscal.timeout', 7);

        $venda = $this->criarVendaConfirmada();

        Http::fake([
            'https://api.exemplo.local/notas' => Http::response([
                'status' => 'emitida',
                'numero' => '4455',
                'serie' => '2',
                'message' => 'ok',
            ], 200),
        ]);

        $venda = app(FiscalService::class)->requestEmission($venda);

        Http::assertSent(function ($request) use ($venda) {
            $body = $request->data();

            return $request->url() === 'https://api.exemplo.local/notas'
                && $request->hasHeader('X-API-Key', 'token-fiscal')
                && ! $request->hasHeader('Authorization')
                && ($body['sale']['id'] ?? null) === $venda->numero
                && ($body['customer']['id'] ?? null) === $this->cliente->id;
        });

        $this->assertSame('ERP Fiscal Interno', app(FiscalService::class)->providerLabel());
        $this->assertSame('emitida', $venda->status_nota_fiscal);
        $this->assertSame('4455', $venda->nota_fiscal_numero);
        $this->assertSame('2', $venda->nota_fiscal_serie);
    }

    private function payloadVenda(array $override = []): array
    {
        return array_merge([
            'cliente_id' => $this->cliente->id,
            'status' => 'pendente',
            'desconto' => 0,
            'frete' => 0,
            'data_vencimento' => now()->addDays(10)->toDateString(),
            'itens' => [
                [
                    'produto_id' => $this->produto->id,
                    'quantidade' => 1,
                    'preco_unitario' => 20,
                ],
            ],
        ], $override);
    }

    private function criarVendaConfirmada(): Venda
    {
        $this->actingAs($this->user)->post(route('vendas.store'), $this->payloadVenda([
            'status' => 'confirmada',
        ]));

        return Venda::query()->latest('numero')->firstOrFail()->fresh(['empresa', 'cliente', 'itens.produto']);
    }
}