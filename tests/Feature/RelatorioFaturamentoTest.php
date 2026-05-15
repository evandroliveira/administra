<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\ContaReceber;
use App\Models\Empresa;
use App\Models\Promissoria;
use App\Models\Produto;
use App\Models\User;
use App\Models\Venda;
use Database\Seeders\PerfilUsuarioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Concerns\InteractsWithGeneratedSpreadsheets;
use Tests\TestCase;

class RelatorioFaturamentoTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithGeneratedSpreadsheets;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_exibe_resumo_e_promissorias_no_relatorio_de_faturamento(): void
    {
        [$user, $empresa] = $this->criarUsuarioAdmin();
        $cliente = $this->criarCliente($empresa);
        $vendedor = $user->usuarioVendas;
        $produto = $this->criarProduto($empresa);

        $vendaAvista = $this->criarVenda($empresa->id, $cliente->id, $vendedor->id, $produto->id, Carbon::create(2026, 5, 10, 10), 120, 40, 'confirmada');
        $vendaPromissoria = $this->criarVenda($empresa->id, $cliente->id, $vendedor->id, $produto->id, Carbon::create(2026, 5, 12, 15), 200, 60, 'concluida');
        $conta = ContaReceber::create([
            'empresa_id' => $empresa->id,
            'venda_numero' => $vendaPromissoria->numero,
            'cliente_id' => $cliente->id,
            'valor_original' => 150,
            'valor_pago' => 0,
            'valor_juros' => 0,
            'data_vencimento' => '2026-06-12',
            'status' => 'aberta',
            'observacoes' => 'Conta da promissória',
        ]);

        $promissoria = Promissoria::create([
            'empresa_id' => $empresa->id,
            'conta_id' => $conta->id,
            'venda_numero' => $vendaPromissoria->numero,
            'cliente_id' => $cliente->id,
            'valor_entrada' => 50,
            'valor_financiado' => 150,
            'quantidade_parcelas' => 3,
            'intervalo_dias' => 30,
            'primeira_parcela_vencimento' => '2026-06-12',
            'percentual_multa_atraso' => 3.5,
            'percentual_juros_dia' => 0.075,
            'status' => 'aberta',
            'observacoes' => 'Promissória do relatório',
            'data_emissao' => '2026-05-12 15:00:00',
        ]);

        $response = $this->actingAs($user)->get(route('relatorios.faturamento', [
            'data_inicio' => '2026-05-01',
            'data_fim' => '2026-05-31',
        ]));

        $response->assertOk()
            ->assertSee('Relatório de Faturamento', false)
            ->assertSee('R$ 320,00', false)
            ->assertSee('R$ 100,00', false)
            ->assertSee('Cliente Relatorio', false)
            ->assertSee($promissoria->numero_documento, false)
                ->assertSee('0,0750%/dia', false);
    }

    public function test_exporta_csv_do_relatorio_de_faturamento(): void
    {
        [$user, $empresa] = $this->criarUsuarioAdmin();
        $cliente = $this->criarCliente($empresa);
        $vendedor = $user->usuarioVendas;
        $produto = $this->criarProduto($empresa);

        $venda = $this->criarVenda($empresa->id, $cliente->id, $vendedor->id, $produto->id, Carbon::create(2026, 5, 20, 9), 180, 70, 'confirmada');
        $conta = ContaReceber::create([
            'empresa_id' => $empresa->id,
            'venda_numero' => $venda->numero,
            'cliente_id' => $cliente->id,
            'valor_original' => 120,
            'valor_pago' => 0,
            'valor_juros' => 0,
            'data_vencimento' => '2026-06-20',
            'status' => 'aberta',
            'observacoes' => 'Conta teste CSV',
        ]);

        Promissoria::create([
            'empresa_id' => $empresa->id,
            'conta_id' => $conta->id,
            'venda_numero' => $venda->numero,
            'cliente_id' => $cliente->id,
            'valor_entrada' => 60,
            'valor_financiado' => 120,
            'quantidade_parcelas' => 2,
            'intervalo_dias' => 30,
            'primeira_parcela_vencimento' => '2026-06-20',
            'percentual_multa_atraso' => 2,
            'percentual_juros_dia' => 0.05,
            'status' => 'aberta',
            'data_emissao' => '2026-05-20 09:00:00',
        ]);

        $response = $this->actingAs($user)->get(route('relatorios.faturamento', [
            'data_inicio' => '2026-05-01',
            'data_fim' => '2026-05-31',
            'export' => 'csv',
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('relatorio_faturamento_2026-05-01_2026-05-31.csv', $response->headers->get('content-disposition'));

        $conteudo = $response->streamedContent();

        $this->assertStringContainsString('Relatório de Faturamento', $conteudo);
        $this->assertStringContainsString('Cliente Relatorio', $conteudo);
        $this->assertStringContainsString('Vendas por dia', $conteudo);
        $this->assertStringContainsString('Promissórias', $conteudo);
    }

    public function test_exporta_xlsx_do_relatorio_de_faturamento(): void
    {
        [$user, $empresa] = $this->criarUsuarioAdmin();
        $cliente = $this->criarCliente($empresa);
        $vendedor = $user->usuarioVendas;
        $produto = $this->criarProduto($empresa);

        $venda = $this->criarVenda($empresa->id, $cliente->id, $vendedor->id, $produto->id, Carbon::create(2026, 5, 20, 9), 180, 70, 'confirmada');
        $conta = ContaReceber::create([
            'empresa_id' => $empresa->id,
            'venda_numero' => $venda->numero,
            'cliente_id' => $cliente->id,
            'valor_original' => 120,
            'valor_pago' => 0,
            'valor_juros' => 0,
            'data_vencimento' => '2026-06-20',
            'status' => 'aberta',
            'observacoes' => 'Conta teste XLSX',
        ]);

        Promissoria::create([
            'empresa_id' => $empresa->id,
            'conta_id' => $conta->id,
            'venda_numero' => $venda->numero,
            'cliente_id' => $cliente->id,
            'valor_entrada' => 60,
            'valor_financiado' => 120,
            'quantidade_parcelas' => 2,
            'intervalo_dias' => 30,
            'primeira_parcela_vencimento' => '2026-06-20',
            'percentual_multa_atraso' => 2,
            'percentual_juros_dia' => 0.05,
            'status' => 'aberta',
            'data_emissao' => '2026-05-20 09:00:00',
        ]);

        $response = $this->actingAs($user)->get(route('relatorios.faturamento', [
            'data_inicio' => '2026-05-01',
            'data_fim' => '2026-05-31',
            'export' => 'xlsx',
        ]));

        $response->assertOk();
        $this->assertStringContainsString('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $response->headers->get('content-type'));
        $this->assertStringContainsString('relatorio_faturamento_2026-05-01_2026-05-31.xlsx', $response->headers->get('content-disposition'));

        $spreadsheet = $this->openSpreadsheetFromBinary($response->streamedContent());

        $this->assertSame('Resumo', $spreadsheet->getSheet(0)->getTitle());
        $this->assertSame('Relatório de Faturamento', $spreadsheet->getSheet(0)->getCell('A1')->getValue());
        $this->assertSame('Vendas por Dia', $spreadsheet->getSheet(1)->getTitle());
        $this->assertSame('Cliente Relatorio', $spreadsheet->getSheet(2)->getCell('C4')->getValue());
    }

    public function test_exporta_pdf_do_relatorio_de_faturamento(): void
    {
        [$user, $empresa] = $this->criarUsuarioAdmin();
        $empresa->update([
            'logo' => 'data:image/svg+xml;base64,'.base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="120" height="120" viewBox="0 0 120 120"><rect width="120" height="120" rx="22" fill="#0f172a"/><circle cx="60" cy="60" r="40" fill="#f59e0b" opacity="0.2"/><text x="60" y="74" text-anchor="middle" font-size="42" font-family="Arial" fill="#ffffff" font-weight="700">AD</text></svg>'),
        ]);
        $cliente = $this->criarCliente($empresa);
        $vendedor = $user->usuarioVendas;
        $produto = $this->criarProduto($empresa);

        $venda = $this->criarVenda($empresa->id, $cliente->id, $vendedor->id, $produto->id, Carbon::create(2026, 5, 20, 9), 180, 70, 'confirmada');
        $conta = ContaReceber::create([
            'empresa_id' => $empresa->id,
            'venda_numero' => $venda->numero,
            'cliente_id' => $cliente->id,
            'valor_original' => 120,
            'valor_pago' => 0,
            'valor_juros' => 0,
            'data_vencimento' => '2026-06-20',
            'status' => 'aberta',
            'observacoes' => 'Conta teste PDF',
        ]);

        Promissoria::create([
            'empresa_id' => $empresa->id,
            'conta_id' => $conta->id,
            'venda_numero' => $venda->numero,
            'cliente_id' => $cliente->id,
            'valor_entrada' => 60,
            'valor_financiado' => 120,
            'quantidade_parcelas' => 2,
            'intervalo_dias' => 30,
            'primeira_parcela_vencimento' => '2026-06-20',
            'percentual_multa_atraso' => 2,
            'percentual_juros_dia' => 0.05,
            'status' => 'aberta',
            'data_emissao' => '2026-05-20 09:00:00',
        ]);

        $response = $this->actingAs($user)->get(route('relatorios.faturamento', [
            'data_inicio' => '2026-05-01',
            'data_fim' => '2026-05-31',
            'export' => 'pdf',
        ]));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('content-type'));
        $this->assertStringContainsString('relatorio_faturamento_2026-05-01_2026-05-31.pdf', $response->headers->get('content-disposition'));
        $this->assertStringStartsWith('%PDF', $response->content());
    }

    private function criarUsuarioAdmin(): array
    {
        $this->seed(PerfilUsuarioSeeder::class);

        $empresa = Empresa::query()->where('slug', 'administrar')->firstOrFail();
        $user = User::factory()->create([
            'username' => 'admin_relatorios',
            'email' => 'admin.relatorios@example.com',
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
            'nome' => 'Cliente Relatorio',
            'email' => 'cliente.relatorio@example.com',
            'telefone' => '(11) 99999-7777',
            'cpf_cnpj' => '12345678906',
            'endereco' => 'Rua Relatorio',
            'numero' => '10',
            'bairro' => 'Centro',
            'cidade' => 'Sao Paulo',
            'estado' => 'SP',
            'cep' => '01000004',
            'limite_credito' => 1000,
            'credito_disponivel' => 1000,
            'ativo' => true,
        ]);
    }

    private function criarProduto(Empresa $empresa): Produto
    {
        return Produto::create([
            'empresa_id' => $empresa->id,
            'codigo' => 'REL-001',
            'nome' => 'Produto Relatorio',
            'preco_custo' => 50,
            'preco_venda' => 120,
            'margem_lucro' => 58.33,
            'custo_medio' => 50,
            'estoque_atual' => 30,
            'estoque_minimo' => 2,
            'ativo' => true,
        ]);
    }

    private function criarVenda(int $empresaId, int $clienteId, int $vendedorId, int $produtoId, Carbon $dataVenda, float $total, float $lucro, string $status): Venda
    {
        $venda = new Venda([
            'empresa_id' => $empresaId,
            'cliente_id' => $clienteId,
            'vendedor_id' => $vendedorId,
            'status' => $status,
            'subtotal' => $total,
            'desconto' => 0,
            'frete' => 0,
            'total' => $total,
            'lucro_total' => $lucro,
        ]);
        $venda->data_venda = $dataVenda;
        $venda->save();

        $venda->itens()->create([
            'empresa_id' => $empresaId,
            'produto_id' => $produtoId,
            'quantidade' => 1,
            'preco_unitario' => $total,
            'preco_custo_unitario' => $total - $lucro,
            'lucro_item' => $lucro,
        ]);

        return $venda->refresh();
    }
}
