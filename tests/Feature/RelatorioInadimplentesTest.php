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

class RelatorioInadimplentesTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithGeneratedSpreadsheets;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_exibe_titulos_em_atraso_com_taxas_de_promissoria(): void
    {
        [$user, $empresa] = $this->criarUsuarioAdmin();
        $cliente = $this->criarCliente($empresa);
        $vendedor = $user->usuarioVendas;
        $produto = $this->criarProduto($empresa);

        $vendaConta = $this->criarVenda($empresa->id, $cliente->id, $vendedor->id, $produto->id, 90, 20, 'confirmada');
        ContaReceber::create([
            'empresa_id' => $empresa->id,
            'venda_numero' => $vendaConta->numero,
            'cliente_id' => $cliente->id,
            'valor_original' => 90,
            'valor_pago' => 10,
            'valor_juros' => 0,
            'data_vencimento' => Carbon::today()->subDays(8)->toDateString(),
            'status' => 'parcial',
            'observacoes' => 'Conta comum vencida',
        ]);

        $vendaPromissoria = $this->criarVenda($empresa->id, $cliente->id, $vendedor->id, $produto->id, 150, 50, 'concluida');
        $contaPromissoria = ContaReceber::create([
            'empresa_id' => $empresa->id,
            'venda_numero' => $vendaPromissoria->numero,
            'cliente_id' => $cliente->id,
            'valor_original' => 120,
            'valor_pago' => 0,
            'valor_juros' => 0,
            'data_vencimento' => Carbon::today()->subDays(15)->toDateString(),
            'status' => 'vencida',
            'observacoes' => 'Conta promissória vencida',
        ]);

        $promissoria = Promissoria::create([
            'empresa_id' => $empresa->id,
            'conta_id' => $contaPromissoria->id,
            'venda_numero' => $vendaPromissoria->numero,
            'cliente_id' => $cliente->id,
            'valor_entrada' => 30,
            'valor_financiado' => 120,
            'quantidade_parcelas' => 2,
            'intervalo_dias' => 30,
            'primeira_parcela_vencimento' => Carbon::today()->subDays(15)->toDateString(),
            'percentual_multa_atraso' => 4.25,
            'percentual_juros_dia' => 0.12,
            'status' => 'aberta',
            'data_emissao' => now()->subDays(40),
        ]);

        $promissoria->gerarParcelas();
        $parcela = $promissoria->parcelas()->firstOrFail();
        $parcela->update([
            'data_vencimento' => Carbon::today()->subDays(15)->toDateString(),
            'status' => 'vencida',
        ]);
        $promissoria->sincronizar();

        $response = $this->actingAs($user)->get(route('relatorios.inadimplentes'));

        $response->assertOk()
            ->assertSee('Relatório de Inadimplentes', false)
            ->assertSee('Títulos em Atraso', false)
            ->assertSee($promissoria->numero_documento, false)
            ->assertSee('Multa: 4,25%', false)
            ->assertSee('Juros: 0,1200%/dia', false)
            ->assertSee('R$ 200,00', false);
    }

    public function test_exporta_csv_do_relatorio_de_inadimplentes(): void
    {
        [$user, $empresa] = $this->criarUsuarioAdmin();
        $cliente = $this->criarCliente($empresa);
        $vendedor = $user->usuarioVendas;
        $produto = $this->criarProduto($empresa);

        $venda = $this->criarVenda($empresa->id, $cliente->id, $vendedor->id, $produto->id, 110, 25, 'confirmada');
        $conta = ContaReceber::create([
            'empresa_id' => $empresa->id,
            'venda_numero' => $venda->numero,
            'cliente_id' => $cliente->id,
            'valor_original' => 110,
            'valor_pago' => 0,
            'valor_juros' => 0,
            'data_vencimento' => Carbon::today()->subDays(10)->toDateString(),
            'status' => 'vencida',
            'observacoes' => 'Conta CSV vencida',
        ]);

        $promissoria = Promissoria::create([
            'empresa_id' => $empresa->id,
            'conta_id' => $conta->id,
            'venda_numero' => $venda->numero,
            'cliente_id' => $cliente->id,
            'valor_entrada' => 20,
            'valor_financiado' => 90,
            'quantidade_parcelas' => 2,
            'intervalo_dias' => 30,
            'primeira_parcela_vencimento' => Carbon::today()->subDays(10)->toDateString(),
            'percentual_multa_atraso' => 2,
            'percentual_juros_dia' => 0.05,
            'status' => 'aberta',
            'data_emissao' => now()->subDays(20),
        ]);

        $promissoria->gerarParcelas();
        $promissoria->sincronizar();

        $response = $this->actingAs($user)->get(route('relatorios.inadimplentes', ['export' => 'csv']));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('relatorio_inadimplentes.csv', $response->headers->get('content-disposition'));

        $conteudo = $response->streamedContent();

        $this->assertStringContainsString('Relatório de Inadimplentes', $conteudo);
        $this->assertStringContainsString('Títulos em Atraso', $conteudo);
        $this->assertStringContainsString($promissoria->numero_documento, $conteudo);
        $this->assertStringContainsString('Cliente Inadimplente', $conteudo);
    }

    public function test_exporta_xlsx_do_relatorio_de_inadimplentes(): void
    {
        [$user, $empresa] = $this->criarUsuarioAdmin();
        $cliente = $this->criarCliente($empresa);
        $vendedor = $user->usuarioVendas;
        $produto = $this->criarProduto($empresa);

        $venda = $this->criarVenda($empresa->id, $cliente->id, $vendedor->id, $produto->id, 110, 25, 'confirmada');
        $conta = ContaReceber::create([
            'empresa_id' => $empresa->id,
            'venda_numero' => $venda->numero,
            'cliente_id' => $cliente->id,
            'valor_original' => 110,
            'valor_pago' => 0,
            'valor_juros' => 0,
            'data_vencimento' => Carbon::today()->subDays(10)->toDateString(),
            'status' => 'vencida',
            'observacoes' => 'Conta XLSX vencida',
        ]);

        $promissoria = Promissoria::create([
            'empresa_id' => $empresa->id,
            'conta_id' => $conta->id,
            'venda_numero' => $venda->numero,
            'cliente_id' => $cliente->id,
            'valor_entrada' => 20,
            'valor_financiado' => 90,
            'quantidade_parcelas' => 2,
            'intervalo_dias' => 30,
            'primeira_parcela_vencimento' => Carbon::today()->subDays(10)->toDateString(),
            'percentual_multa_atraso' => 2,
            'percentual_juros_dia' => 0.05,
            'status' => 'aberta',
            'data_emissao' => now()->subDays(20),
        ]);

        $promissoria->gerarParcelas();
        $promissoria->sincronizar();

        $response = $this->actingAs($user)->get(route('relatorios.inadimplentes', ['export' => 'xlsx']));

        $response->assertOk();
        $this->assertStringContainsString('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $response->headers->get('content-type'));
        $this->assertStringContainsString('relatorio_inadimplentes.xlsx', $response->headers->get('content-disposition'));

        $spreadsheet = $this->openSpreadsheetFromBinary($response->streamedContent());

        $this->assertSame('Resumo', $spreadsheet->getSheet(0)->getTitle());
        $this->assertSame('Clientes', $spreadsheet->getSheet(1)->getTitle());
        $this->assertSame('Titulos', $spreadsheet->getSheet(2)->getTitle());
        $this->assertSame('Cliente Inadimplente', $spreadsheet->getSheet(2)->getCell('B4')->getValue());
    }

    public function test_exporta_pdf_do_relatorio_de_inadimplentes(): void
    {
        [$user, $empresa] = $this->criarUsuarioAdmin();
        $cliente = $this->criarCliente($empresa);
        $vendedor = $user->usuarioVendas;
        $produto = $this->criarProduto($empresa);

        $venda = $this->criarVenda($empresa->id, $cliente->id, $vendedor->id, $produto->id, 110, 25, 'confirmada');
        $conta = ContaReceber::create([
            'empresa_id' => $empresa->id,
            'venda_numero' => $venda->numero,
            'cliente_id' => $cliente->id,
            'valor_original' => 110,
            'valor_pago' => 0,
            'valor_juros' => 0,
            'data_vencimento' => Carbon::today()->subDays(10)->toDateString(),
            'status' => 'vencida',
            'observacoes' => 'Conta PDF vencida',
        ]);

        $promissoria = Promissoria::create([
            'empresa_id' => $empresa->id,
            'conta_id' => $conta->id,
            'venda_numero' => $venda->numero,
            'cliente_id' => $cliente->id,
            'valor_entrada' => 20,
            'valor_financiado' => 90,
            'quantidade_parcelas' => 2,
            'intervalo_dias' => 30,
            'primeira_parcela_vencimento' => Carbon::today()->subDays(10)->toDateString(),
            'percentual_multa_atraso' => 2,
            'percentual_juros_dia' => 0.05,
            'status' => 'aberta',
            'data_emissao' => now()->subDays(20),
        ]);

        $promissoria->gerarParcelas();
        $promissoria->sincronizar();

        $response = $this->actingAs($user)->get(route('relatorios.inadimplentes', ['export' => 'pdf']));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('content-type'));
        $this->assertStringContainsString('relatorio_inadimplentes.pdf', $response->headers->get('content-disposition'));
        $this->assertStringStartsWith('%PDF', $response->content());
    }

    private function criarUsuarioAdmin(): array
    {
        $this->seed(PerfilUsuarioSeeder::class);

        $empresa = Empresa::query()->where('slug', 'administrar')->firstOrFail();
        $user = User::factory()->create([
            'username' => 'admin_inadimplentes',
            'email' => 'admin.inadimplentes@example.com',
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
            'nome' => 'Cliente Inadimplente',
            'email' => 'cliente.inadimplente@example.com',
            'telefone' => '(11) 99999-5555',
            'cpf_cnpj' => '12345678907',
            'endereco' => 'Rua Inadimplente',
            'numero' => '77',
            'bairro' => 'Centro',
            'cidade' => 'Sao Paulo',
            'estado' => 'SP',
            'cep' => '01000005',
            'limite_credito' => 1000,
            'credito_disponivel' => 1000,
            'ativo' => true,
        ]);
    }

    private function criarProduto(Empresa $empresa): Produto
    {
        return Produto::create([
            'empresa_id' => $empresa->id,
            'codigo' => 'REL-002',
            'nome' => 'Produto Inadimplente',
            'preco_custo' => 40,
            'preco_venda' => 110,
            'margem_lucro' => 63.64,
            'custo_medio' => 40,
            'estoque_atual' => 30,
            'estoque_minimo' => 2,
            'ativo' => true,
        ]);
    }

    private function criarVenda(int $empresaId, int $clienteId, int $vendedorId, int $produtoId, float $total, float $lucro, string $status): Venda
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
        $venda->data_venda = now()->subDays(35);
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