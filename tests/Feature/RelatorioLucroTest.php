<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Produto;
use App\Models\User;
use App\Models\Venda;
use Database\Seeders\PerfilUsuarioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Concerns\InteractsWithGeneratedSpreadsheets;
use Tests\TestCase;

class RelatorioLucroTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithGeneratedSpreadsheets;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_exibe_lucro_por_produto_no_periodo(): void
    {
        [$user, $empresa] = $this->criarUsuarioAdmin();
        $cliente = $this->criarCliente($empresa);
        $vendedor = $user->usuarioVendas;
        $produtoA = $this->criarProduto($empresa, 'LUC-001', 'Produto Margem Alta', 35, 90, 30);
        $produtoB = $this->criarProduto($empresa, 'LUC-002', 'Produto Giro', 20, 60, 30);

        $vendaA = $this->criarVenda($empresa->id, $cliente->id, $vendedor->id, Carbon::create(2026, 5, 10, 10), 'confirmada', 80);
        $vendaA->itens()->create([
            'empresa_id' => $empresa->id,
            'produto_id' => $produtoA->id,
            'quantidade' => 2,
            'preco_unitario' => 90,
            'custo_unitario' => 35,
            'valor_total' => 180,
            'lucro' => 110,
        ]);
        $vendaA->update(['subtotal' => 180, 'total' => 180, 'lucro_total' => 110]);

        $vendaB = $this->criarVenda($empresa->id, $cliente->id, $vendedor->id, Carbon::create(2026, 5, 18, 15), 'concluida', 70);
        $vendaB->itens()->create([
            'empresa_id' => $empresa->id,
            'produto_id' => $produtoB->id,
            'quantidade' => 3,
            'preco_unitario' => 60,
            'custo_unitario' => 20,
            'valor_total' => 180,
            'lucro' => 120,
        ]);
        $vendaB->update(['subtotal' => 180, 'total' => 180, 'lucro_total' => 120]);

        $response = $this->actingAs($user)->get(route('relatorios.lucro', [
            'data_inicio' => '2026-05-01',
            'data_fim' => '2026-05-31',
        ]));

        $response->assertOk()
            ->assertSee('Relatório de Lucro', false)
            ->assertSee('R$ 230,00', false)
            ->assertSee('Produto Margem Alta', false)
            ->assertSee('Produto Giro', false)
            ->assertSee('47,83%', false)
            ->assertSee('52,17%', false);
    }

    public function test_exporta_csv_do_relatorio_de_lucro(): void
    {
        [$user, $empresa] = $this->criarUsuarioAdmin();
        $cliente = $this->criarCliente($empresa);
        $vendedor = $user->usuarioVendas;
        $produto = $this->criarProduto($empresa, 'LUC-003', 'Produto CSV Lucro', 25, 70, 30);

        $venda = $this->criarVenda($empresa->id, $cliente->id, $vendedor->id, Carbon::create(2026, 5, 8, 14), 'confirmada', 45);
        $venda->itens()->create([
            'empresa_id' => $empresa->id,
            'produto_id' => $produto->id,
            'quantidade' => 2,
            'preco_unitario' => 70,
            'custo_unitario' => 25,
            'valor_total' => 140,
            'lucro' => 90,
        ]);
        $venda->update(['subtotal' => 140, 'total' => 140, 'lucro_total' => 90]);

        $response = $this->actingAs($user)->get(route('relatorios.lucro', [
            'data_inicio' => '2026-05-01',
            'data_fim' => '2026-05-31',
            'export' => 'csv',
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('relatorio_lucro_2026-05-01_2026-05-31.csv', $response->headers->get('content-disposition'));

        $conteudo = $response->streamedContent();

        $this->assertStringContainsString('Relatório de Lucro', $conteudo);
        $this->assertStringContainsString('Lucro por Produto', $conteudo);
        $this->assertStringContainsString('Produto CSV Lucro', $conteudo);
    }

    public function test_exporta_xlsx_do_relatorio_de_lucro(): void
    {
        [$user, $empresa] = $this->criarUsuarioAdmin();
        $cliente = $this->criarCliente($empresa);
        $vendedor = $user->usuarioVendas;
        $produto = $this->criarProduto($empresa, 'LUC-003', 'Produto XLSX Lucro', 25, 70, 30);

        $venda = $this->criarVenda($empresa->id, $cliente->id, $vendedor->id, Carbon::create(2026, 5, 8, 14), 'confirmada', 45);
        $venda->itens()->create([
            'empresa_id' => $empresa->id,
            'produto_id' => $produto->id,
            'quantidade' => 2,
            'preco_unitario' => 70,
            'custo_unitario' => 25,
            'valor_total' => 140,
            'lucro' => 90,
        ]);
        $venda->update(['subtotal' => 140, 'total' => 140, 'lucro_total' => 90]);

        $response = $this->actingAs($user)->get(route('relatorios.lucro', [
            'data_inicio' => '2026-05-01',
            'data_fim' => '2026-05-31',
            'export' => 'xlsx',
        ]));

        $response->assertOk();
        $this->assertStringContainsString('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $response->headers->get('content-type'));
        $this->assertStringContainsString('relatorio_lucro_2026-05-01_2026-05-31.xlsx', $response->headers->get('content-disposition'));

        $spreadsheet = $this->openSpreadsheetFromBinary($response->streamedContent());

        $this->assertSame('Resumo', $spreadsheet->getSheet(0)->getTitle());
        $this->assertSame('Lucro por Produto', $spreadsheet->getSheet(1)->getTitle());
        $this->assertSame('Produto XLSX Lucro', $spreadsheet->getSheet(1)->getCell('A4')->getValue());
    }

    public function test_exporta_pdf_do_relatorio_de_lucro(): void
    {
        [$user, $empresa] = $this->criarUsuarioAdmin();
        $cliente = $this->criarCliente($empresa);
        $vendedor = $user->usuarioVendas;
        $produto = $this->criarProduto($empresa, 'LUC-003', 'Produto PDF Lucro', 25, 70, 30);

        $venda = $this->criarVenda($empresa->id, $cliente->id, $vendedor->id, Carbon::create(2026, 5, 8, 14), 'confirmada', 45);
        $venda->itens()->create([
            'empresa_id' => $empresa->id,
            'produto_id' => $produto->id,
            'quantidade' => 2,
            'preco_unitario' => 70,
            'custo_unitario' => 25,
            'valor_total' => 140,
            'lucro' => 90,
        ]);
        $venda->update(['subtotal' => 140, 'total' => 140, 'lucro_total' => 90]);

        $response = $this->actingAs($user)->get(route('relatorios.lucro', [
            'data_inicio' => '2026-05-01',
            'data_fim' => '2026-05-31',
            'export' => 'pdf',
        ]));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('content-type'));
        $this->assertStringContainsString('relatorio_lucro_2026-05-01_2026-05-31.pdf', $response->headers->get('content-disposition'));
        $this->assertStringStartsWith('%PDF', $response->content());
    }

    private function criarUsuarioAdmin(): array
    {
        $this->seed(PerfilUsuarioSeeder::class);

        $empresa = Empresa::query()->where('slug', 'administrar')->firstOrFail();
        $user = User::factory()->create([
            'username' => 'admin_lucro',
            'email' => 'admin.lucro@example.com',
        ]);

        $user->assignRole('admin');
        $user->usuarioVendas()->create([
            'empresa_id' => $empresa->id,
            'perfil_id' => null,
            'ativo' => true,
            'data_contratacao' => now()->toDateString(),
        ]);

        return [$user, $empresa];
    }

    private function criarCliente(Empresa $empresa): Cliente
    {
        return Cliente::create([
            'empresa_id' => $empresa->id,
            'tipo' => 'PF',
            'nome' => 'Cliente Lucro',
            'email' => 'cliente.lucro@example.com',
            'telefone' => '(11) 99999-4444',
            'cpf_cnpj' => '12345678908',
            'endereco' => 'Rua Lucro',
            'numero' => '99',
            'bairro' => 'Centro',
            'cidade' => 'Sao Paulo',
            'estado' => 'SP',
            'cep' => '01000006',
            'limite_credito' => 1000,
            'credito_disponivel' => 1000,
            'ativo' => true,
        ]);
    }

    private function criarProduto(Empresa $empresa, string $codigo, string $nome, float $precoCusto, float $precoVenda, int $estoque): Produto
    {
        return Produto::create([
            'empresa_id' => $empresa->id,
            'codigo' => $codigo,
            'nome' => $nome,
            'preco_custo' => $precoCusto,
            'preco_venda' => $precoVenda,
            'margem_lucro' => 50,
            'custo_medio' => $precoCusto,
            'estoque_atual' => $estoque,
            'estoque_minimo' => 2,
            'ativo' => true,
        ]);
    }

    private function criarVenda(int $empresaId, int $clienteId, int $vendedorId, Carbon $dataVenda, string $status, float $lucroTotal): Venda
    {
        $venda = new Venda([
            'empresa_id' => $empresaId,
            'cliente_id' => $clienteId,
            'vendedor_id' => $vendedorId,
            'status' => $status,
            'subtotal' => 0,
            'desconto' => 0,
            'frete' => 0,
            'total' => 0,
            'lucro_total' => $lucroTotal,
        ]);
        $venda->data_venda = $dataVenda;
        $venda->save();

        return $venda->refresh();
    }
}