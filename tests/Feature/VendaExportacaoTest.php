<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\ContaReceber;
use App\Models\Empresa;
use App\Models\Produto;
use App\Models\User;
use App\Models\Venda;
use Database\Seeders\PerfilUsuarioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithGeneratedSpreadsheets;
use Tests\TestCase;

class VendaExportacaoTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithGeneratedSpreadsheets;

    protected Empresa $empresa;

    protected User $user;

    protected Produto $produto;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PerfilUsuarioSeeder::class);

        $this->empresa = Empresa::query()->where('slug', 'administrar')->firstOrFail();
        $this->user = User::factory()->create([
            'username' => 'admin_exportacoes_vendas',
            'email' => 'admin.exportacoes.vendas@example.com',
        ]);

        $this->user->assignRole('admin');
        $this->user->usuarioVendas()->create([
            'empresa_id' => $this->empresa->id,
            'perfil_id' => null,
            'ativo' => true,
            'data_contratacao' => now()->toDateString(),
        ]);

        $this->produto = Produto::create([
            'empresa_id' => $this->empresa->id,
            'codigo' => 'EXP-VENDAS-001',
            'nome' => 'Produto Exportacao Vendas',
            'preco_custo' => 20,
            'preco_venda' => 50,
            'margem_lucro' => 60,
            'custo_medio' => 20,
            'estoque_atual' => 100,
            'estoque_minimo' => 1,
            'ativo' => true,
        ]);
    }

    public function test_listagem_exibe_acoes_de_exportacao_com_filtro_atual(): void
    {
        $this->actingAs($this->user)
            ->get(route('vendas.index', ['status' => 'confirmada']))
            ->assertOk()
            ->assertSee('Exportar CSV', false)
            ->assertSee('Exportar XLSX', false)
            ->assertSee('Exportar PDF', false)
            ->assertSee('status=confirmada&amp;export=csv', false);
    }

    public function test_exporta_csv_da_listagem_de_vendas_filtrada_por_status(): void
    {
        $this->criarVendaViaFluxo('Cliente CSV Confirmada', 'confirmada', 80);
        $this->criarVendaViaFluxo('Cliente CSV Cancelada', 'cancelada', 65);

        $response = $this->actingAs($this->user)->get(route('vendas.index', [
            'status' => 'confirmada',
            'export' => 'csv',
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('vendas_confirmada.csv', $response->headers->get('content-disposition'));

        $conteudo = $response->streamedContent();

        $this->assertStringContainsString('Listagem de Vendas', $conteudo);
        $this->assertStringContainsString('Cliente CSV Confirmada', $conteudo);
        $this->assertStringNotContainsString('Cliente CSV Cancelada', $conteudo);
        $this->assertStringContainsString('Status aplicado;Confirmada', str_replace('"', '', $conteudo));
    }

    public function test_exporta_xlsx_da_listagem_de_vendas(): void
    {
        $venda = $this->criarVendaViaFluxo('Cliente XLSX Vendas', 'confirmada', 120);

        $response = $this->actingAs($this->user)->get(route('vendas.index', [
            'status' => 'confirmada',
            'export' => 'xlsx',
        ]));

        $response->assertOk();
        $this->assertStringContainsString('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $response->headers->get('content-type'));
        $this->assertStringContainsString('vendas_confirmada.xlsx', $response->headers->get('content-disposition'));

        $spreadsheet = $this->openSpreadsheetFromBinary($response->streamedContent());

        $this->assertSame('Resumo', $spreadsheet->getSheet(0)->getTitle());
        $this->assertSame('Listagem de Vendas', $spreadsheet->getSheet(0)->getCell('A1')->getValue());
        $this->assertSame('Vendas', $spreadsheet->getSheet(1)->getTitle());
        $this->assertSame((string) $venda->numero, (string) $spreadsheet->getSheet(1)->getCell('A4')->getValue());
        $this->assertSame('Cliente XLSX Vendas', $spreadsheet->getSheet(1)->getCell('C4')->getValue());
    }

    public function test_exporta_pdf_da_listagem_de_vendas(): void
    {
        $this->criarVendaViaFluxo('Cliente PDF Vendas', 'concluida', 90);

        $response = $this->actingAs($this->user)->get(route('vendas.index', [
            'status' => 'concluida',
            'export' => 'pdf',
        ]));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('content-type'));
        $this->assertStringContainsString('vendas_concluida.pdf', $response->headers->get('content-disposition'));
        $this->assertStringStartsWith('%PDF', $response->content());
    }

    public function test_listagem_filtra_por_forma_de_recebimento_e_exibe_badge_boleto(): void
    {
        $this->criarVendaComFormaRecebimento('Cliente Lista Boleto', 'boleto', 95, 'https://example.com/boleto/lista-001');
        $this->criarVendaComFormaRecebimento('Cliente Lista Conta', 'conta', 65);

        $this->actingAs($this->user)
            ->get(route('vendas.index', ['forma_recebimento' => 'boleto']))
            ->assertOk()
            ->assertSee('Boleto bancário', false)
            ->assertSee('Cliente Lista Boleto', false)
            ->assertSee('Abrir boleto', false)
            ->assertDontSee('Cliente Lista Conta', false);
    }

    private function criarVendaViaFluxo(string $nomeCliente, string $status, float $precoUnitario): Venda
    {
        $cliente = Cliente::create([
            'empresa_id' => $this->empresa->id,
            'tipo' => 'PF',
            'nome' => $nomeCliente,
            'email' => strtolower(str_replace(' ', '.', $nomeCliente)).'@example.com',
            'telefone' => '(11) 99999-1212',
            'cpf_cnpj' => (string) random_int(10000000000, 99999999999),
            'endereco' => 'Rua das Exportacoes',
            'numero' => '42',
            'bairro' => 'Centro',
            'cidade' => 'Sao Paulo',
            'estado' => 'SP',
            'cep' => '01010000',
            'limite_credito' => 1000,
            'credito_disponivel' => 1000,
            'ativo' => true,
        ]);

        $this->actingAs($this->user)->post(route('vendas.store'), [
            'cliente_id' => $cliente->id,
            'status' => $status,
            'desconto' => 0,
            'frete' => 0,
            'data_vencimento' => now()->addDays(10)->toDateString(),
            'itens' => [[
                'produto_id' => $this->produto->id,
                'quantidade' => 2,
                'preco_unitario' => $precoUnitario,
            ]],
        ])->assertRedirect();

        return Venda::query()->latest('numero')->firstOrFail();
    }

    private function criarVendaComFormaRecebimento(string $nomeCliente, string $formaRecebimento, float $valorTotal, ?string $boletoUrl = null): Venda
    {
        $cliente = Cliente::create([
            'empresa_id' => $this->empresa->id,
            'tipo' => 'PF',
            'nome' => $nomeCliente,
            'email' => strtolower(str_replace(' ', '.', $nomeCliente)).'.forma@example.com',
            'telefone' => '(11) 99999-3434',
            'cpf_cnpj' => (string) random_int(10000000000, 99999999999),
            'endereco' => 'Rua das Formas',
            'numero' => '77',
            'bairro' => 'Centro',
            'cidade' => 'Sao Paulo',
            'estado' => 'SP',
            'cep' => '01020000',
            'limite_credito' => 1000,
            'credito_disponivel' => 1000,
            'ativo' => true,
        ]);

        $venda = Venda::create([
            'empresa_id' => $this->empresa->id,
            'cliente_id' => $cliente->id,
            'vendedor_id' => $this->user->usuarioVendas->id,
            'status' => 'confirmada',
            'subtotal' => $valorTotal,
            'desconto' => 0,
            'frete' => 0,
            'total' => $valorTotal,
            'lucro_total' => $valorTotal * 0.2,
        ]);

        ContaReceber::create([
            'empresa_id' => $this->empresa->id,
            'venda_numero' => $venda->numero,
            'cliente_id' => $cliente->id,
            'valor_original' => $valorTotal,
            'valor_pago' => 0,
            'valor_juros' => 0,
            'data_vencimento' => now()->addDays(10)->toDateString(),
            'status' => 'aberta',
            'forma_recebimento' => $formaRecebimento,
            'gateway_checkout_url' => $boletoUrl,
            'observacoes' => 'Conta vinculada a teste de listagem',
        ]);

        return $venda->fresh(['cliente', 'contaReceber']);
    }
}