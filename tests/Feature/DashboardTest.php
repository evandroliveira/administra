<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\ContaPagar;
use App\Models\ContaReceber;
use App\Models\Empresa;
use App\Models\Perfil;
use App\Models\Produto;
use App\Models\User;
use App\Models\UsuarioVendas;
use App\Models\Venda;
use Database\Seeders\PerfilUsuarioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected Empresa $empresa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(PerfilUsuarioSeeder::class);
        Carbon::setTestNow(Carbon::create(2026, 5, 15, 10, 0, 0));

        $this->empresa = Empresa::query()->where('slug', 'administrar')->firstOrFail();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_admin_exibe_dashboard_analitico_escopado_por_empresa(): void
    {
        $user = $this->criarUsuarioComPerfil(Perfil::ADMIN, $this->empresa);

        $clienteRecorrente = $this->criarCliente($this->empresa, 'Cliente Recorrente');
        $clienteValor = $this->criarCliente($this->empresa, 'Cliente Valor');
        $produtoPulse = $this->criarProduto($this->empresa, 'PULSE-01', 'Pulse Analytics', 20, 80, 1, 4);
        $produtoService = $this->criarProduto($this->empresa, 'SVC-02', 'Service Booster', 15, 60, 12, 3);
        $produtoLegado = $this->criarProduto($this->empresa, 'LEG-03', 'Legado Premium', 25, 95, 8, 2);

        $this->criarVenda($this->empresa, $clienteRecorrente, $user->usuarioVendas, $produtoPulse, Carbon::create(2026, 5, 14, 9), 160, 120, 2);
        $this->criarVenda($this->empresa, $clienteRecorrente, $user->usuarioVendas, $produtoPulse, Carbon::create(2026, 4, 28, 15), 80, 60, 1);
        $this->criarVenda($this->empresa, $clienteValor, $user->usuarioVendas, $produtoService, Carbon::create(2026, 3, 18, 11), 120, 90, 2);
        $this->criarVenda($this->empresa, $clienteValor, $user->usuarioVendas, $produtoLegado, Carbon::create(2025, 8, 9, 13), 95, 70, 1);

        ContaReceber::create([
            'empresa_id' => $this->empresa->id,
            'venda_numero' => 1,
            'cliente_id' => $clienteRecorrente->id,
            'valor_original' => 200,
            'valor_pago' => 50,
            'valor_juros' => 10,
            'data_vencimento' => Carbon::today()->subDays(12)->toDateString(),
            'status' => 'vencida',
            'observacoes' => 'Conta vencida dashboard',
        ]);

        ContaPagar::create([
            'empresa_id' => $this->empresa->id,
            'descricao' => 'Fornecedor estrategico',
            'fornecedor' => 'Fornecedor A',
            'valor_original' => 300,
            'valor_pago' => 40,
            'valor_juros' => 5,
            'data_vencimento' => Carbon::today()->subDays(5)->toDateString(),
            'status' => 'parcial',
            'observacoes' => 'Conta pagar vencida',
        ]);

        $empresaExterna = Empresa::create([
            'nome' => 'Empresa Externa',
            'slug' => 'empresa-externa',
            'documento' => '99999999000199',
            'email' => 'externa@example.com',
            'telefone' => '1133334444',
            'ativa' => true,
        ]);
        $usuarioExterno = $this->criarUsuarioComPerfil(Perfil::ADMIN, $empresaExterna);
        $clienteExterno = $this->criarCliente($empresaExterna, 'Cliente Externo');
        $produtoExterno = $this->criarProduto($empresaExterna, 'EXT-01', 'Produto Externo', 10, 50, 10, 1);
        $this->criarVenda($empresaExterna, $clienteExterno, $usuarioExterno->usuarioVendas, $produtoExterno, Carbon::create(2026, 5, 10, 14), 50, 30, 1);

        $response = $this->actingAs($user)->get(route('dashboard', ['periodo' => 90]));

        $response->assertOk()
            ->assertSee('Analise comercial', false)
            ->assertSee('Saude financeira', false)
            ->assertSee('Reposicao inteligente', false)
            ->assertSee('Pulse Analytics', false)
            ->assertSee('Cliente Recorrente', false)
            ->assertSee('Dez/25', false)
            ->assertSee('Mai/26', false)
            ->assertDontSee('Legado Premium', false)
            ->assertDontSee('Produto Externo', false)
            ->assertDontSee('Cliente Externo', false);
    }

    public function test_dashboard_amplia_janela_para_incluir_historico_antigo(): void
    {
        $user = $this->criarUsuarioComPerfil(Perfil::ADMIN, $this->empresa);
        $cliente = $this->criarCliente($this->empresa, 'Cliente Historico');
        $produtoAtual = $this->criarProduto($this->empresa, 'ATU-01', 'Produto Atual', 18, 75, 10, 2);
        $produtoAntigo = $this->criarProduto($this->empresa, 'LEG-09', 'Legado Premium', 25, 95, 9, 2);

        $this->criarVenda($this->empresa, $cliente, $user->usuarioVendas, $produtoAtual, Carbon::create(2026, 5, 12, 9), 75, 57, 1);
        $this->criarVenda($this->empresa, $cliente, $user->usuarioVendas, $produtoAntigo, Carbon::create(2025, 8, 20, 9), 95, 70, 1);

        $response = $this->actingAs($user)->get(route('dashboard', ['periodo' => 365]));

        $response->assertOk()
            ->assertSee('Legado Premium', false)
            ->assertSee('Ultimos 365 dias', false);
    }

    public function test_recepcao_ve_dashboard_sem_blocos_restritos(): void
    {
        $user = $this->criarUsuarioComPerfil(Perfil::RECEPCAO, $this->empresa);
        $this->criarCliente($this->empresa, 'Cliente Balcao');

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk()
            ->assertSee('Clientes ativos', false)
            ->assertSee('Novo cliente', false)
            ->assertDontSee('Analise comercial', false)
            ->assertDontSee('Saude financeira', false)
            ->assertDontSee('Reposicao inteligente', false)
            ->assertDontSee('Nova venda', false);
    }

    private function criarUsuarioComPerfil(string $perfilNome, Empresa $empresa): User
    {
        $token = Str::lower(Str::random(8));

        $user = User::factory()->create([
            'username' => 'dashboard_'.$token,
            'email' => 'dashboard_'.$token.'@example.com',
        ]);

        $user->assignRole($perfilNome);

        $perfil = Perfil::query()->where('nome', $perfilNome)->first();

        $user->usuarioVendas()->create([
            'empresa_id' => $empresa->id,
            'perfil_id' => $perfil?->id,
            'ativo' => true,
            'data_contratacao' => Carbon::today()->toDateString(),
        ]);

        return $user->refresh();
    }

    private function criarCliente(Empresa $empresa, string $nome): Cliente
    {
        return Cliente::create([
            'empresa_id' => $empresa->id,
            'tipo' => 'PF',
            'nome' => $nome,
            'email' => Str::slug($nome).'.cliente@example.com',
            'telefone' => '(11) 99999-0000',
            'cpf_cnpj' => (string) random_int(10000000000, 99999999999),
            'endereco' => 'Rua Central',
            'numero' => '100',
            'bairro' => 'Centro',
            'cidade' => 'Sao Paulo',
            'estado' => 'SP',
            'cep' => '01000000',
            'limite_credito' => 1500,
            'credito_disponivel' => 1500,
            'ativo' => true,
        ]);
    }

    private function criarProduto(Empresa $empresa, string $codigo, string $nome, float $precoCusto, float $precoVenda, int $estoqueAtual, int $estoqueMinimo): Produto
    {
        return Produto::create([
            'empresa_id' => $empresa->id,
            'codigo' => $codigo,
            'nome' => $nome,
            'preco_custo' => $precoCusto,
            'preco_venda' => $precoVenda,
            'margem_lucro' => 50,
            'custo_medio' => $precoCusto,
            'estoque_atual' => $estoqueAtual,
            'estoque_minimo' => $estoqueMinimo,
            'ativo' => true,
        ]);
    }

    private function criarVenda(Empresa $empresa, Cliente $cliente, UsuarioVendas $vendedor, Produto $produto, Carbon $dataVenda, float $total, float $lucro, int $quantidade): Venda
    {
        $venda = new Venda([
            'empresa_id' => $empresa->id,
            'cliente_id' => $cliente->id,
            'vendedor_id' => $vendedor->id,
            'status' => 'confirmada',
            'subtotal' => $total,
            'desconto' => 0,
            'frete' => 0,
            'total' => $total,
            'lucro_total' => $lucro,
        ]);
        $venda->data_venda = $dataVenda;
        $venda->save();

        $venda->itens()->create([
            'empresa_id' => $empresa->id,
            'produto_id' => $produto->id,
            'quantidade' => $quantidade,
            'preco_unitario' => round($total / $quantidade, 2),
            'custo_unitario' => round(($total - $lucro) / $quantidade, 2),
            'valor_total' => $total,
            'lucro' => $lucro,
        ]);

        return $venda->refresh();
    }
}