<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\ContaReceber;
use App\Models\Empresa;
use App\Models\Perfil;
use App\Models\Promissoria;
use App\Models\User;
use App\Models\Venda;
use Database\Seeders\PerfilUsuarioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Concerns\InteractsWithGeneratedSpreadsheets;
use Tests\TestCase;

class ContaReceberExportacaoTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithGeneratedSpreadsheets;

    protected Empresa $empresa;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PerfilUsuarioSeeder::class);

        $this->empresa = Empresa::query()->where('slug', 'administrar')->firstOrFail();
        $this->admin = $this->criarUsuarioDaEmpresa($this->empresa, Perfil::ADMIN);
    }

    public function test_listagem_exibe_acoes_de_exportacao_com_filtros_atuais(): void
    {
        $this->actingAs($this->admin)
            ->get(route('contas.receber.index', [
                'status' => 'aberta',
                'vencimento_inicio' => '2026-06-01',
                'vencimento_fim' => '2026-06-30',
            ]))
            ->assertOk()
            ->assertSee('Exportar CSV', false)
            ->assertSee('Exportar XLSX', false)
            ->assertSee('Exportar PDF', false)
            ->assertSee('status=aberta&amp;vencimento_inicio=2026-06-01&amp;vencimento_fim=2026-06-30&amp;export=csv', false);
    }

    public function test_exporta_csv_da_listagem_de_contas_receber_filtrada(): void
    {
        $this->criarContaReceber('Cliente Conta Aberta', 'aberta', '2026-06-10', 180, 0, true);
        $this->criarContaReceber('Cliente Conta Quitada', 'quitada', '2026-06-15', 120, 120, false);

        $response = $this->actingAs($this->admin)->get(route('contas.receber.index', [
            'status' => 'aberta',
            'export' => 'csv',
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('contas_receber_aberta.csv', $response->headers->get('content-disposition'));

        $conteudo = $response->streamedContent();

        $this->assertStringContainsString('Listagem de Contas a Receber', $conteudo);
        $this->assertStringContainsString('Cliente Conta Aberta', $conteudo);
        $this->assertStringNotContainsString('Cliente Conta Quitada', $conteudo);
        $this->assertStringContainsString('Status aplicado;Aberta', str_replace('"', '', $conteudo));
    }

    public function test_exporta_xlsx_da_listagem_de_contas_receber(): void
    {
        $conta = $this->criarContaReceber('Cliente Conta XLSX', 'parcial', '2026-06-20', 200, 50, true);

        $response = $this->actingAs($this->admin)->get(route('contas.receber.index', [
            'status' => 'parcial',
            'export' => 'xlsx',
        ]));

        $response->assertOk();
        $this->assertStringContainsString('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $response->headers->get('content-type'));
        $this->assertStringContainsString('contas_receber_parcial.xlsx', $response->headers->get('content-disposition'));

        $spreadsheet = $this->openSpreadsheetFromBinary($response->streamedContent());

        $this->assertSame('Resumo', $spreadsheet->getSheet(0)->getTitle());
        $this->assertSame('Listagem de Contas a Receber', $spreadsheet->getSheet(0)->getCell('A1')->getValue());
        $this->assertSame('Contas a Receber', $spreadsheet->getSheet(1)->getTitle());
        $this->assertSame((string) $conta->id, (string) $spreadsheet->getSheet(1)->getCell('A4')->getValue());
        $this->assertSame('Cliente Conta XLSX', $spreadsheet->getSheet(1)->getCell('B4')->getValue());
    }

    public function test_exporta_pdf_da_listagem_de_contas_receber(): void
    {
        $this->criarContaReceber('Cliente Conta PDF', 'vencida', '2026-06-05', 95, 10, false);

        $response = $this->actingAs($this->admin)->get(route('contas.receber.index', [
            'status' => 'vencida',
            'export' => 'pdf',
        ]));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('content-type'));
        $this->assertStringContainsString('contas_receber_vencida.pdf', $response->headers->get('content-disposition'));
        $this->assertStringStartsWith('%PDF', $response->content());
    }

    private function criarUsuarioDaEmpresa(Empresa $empresa, string $role): User
    {
        $token = Str::lower(Str::random(8));

        $user = User::factory()->create([
            'username' => 'conta_export_'.$role.'_'.$token,
            'email' => 'conta.export.'.$role.'.'.$token.'@example.com',
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

    private function criarContaReceber(string $nomeCliente, string $status, string $vencimento, float $valorOriginal, float $valorPago, bool $comPromissoria): ContaReceber
    {
        $token = Str::lower(Str::random(6));

        $cliente = Cliente::create([
            'empresa_id' => $this->empresa->id,
            'tipo' => 'PF',
            'nome' => $nomeCliente,
            'email' => $token.'@example.com',
            'telefone' => '(11) 98888-0000',
            'cpf_cnpj' => (string) random_int(10000000000, 99999999999),
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

        $venda = Venda::create([
            'empresa_id' => $this->empresa->id,
            'cliente_id' => $cliente->id,
            'vendedor_id' => $this->admin->usuarioVendas->id,
            'status' => 'confirmada',
            'subtotal' => $valorOriginal,
            'desconto' => 0,
            'frete' => 0,
            'total' => $valorOriginal,
            'lucro_total' => $valorOriginal * 0.2,
        ]);

        $conta = ContaReceber::create([
            'empresa_id' => $this->empresa->id,
            'venda_numero' => $venda->numero,
            'cliente_id' => $cliente->id,
            'valor_original' => $valorOriginal,
            'valor_pago' => $valorPago,
            'valor_juros' => 0,
            'data_vencimento' => $vencimento,
            'status' => $status,
            'observacoes' => 'Conta de exportação',
        ]);

        if ($comPromissoria) {
            Promissoria::create([
                'empresa_id' => $this->empresa->id,
                'conta_id' => $conta->id,
                'venda_numero' => $venda->numero,
                'cliente_id' => $cliente->id,
                'valor_entrada' => 0,
                'valor_financiado' => $valorOriginal,
                'quantidade_parcelas' => 2,
                'intervalo_dias' => 30,
                'primeira_parcela_vencimento' => $vencimento,
                'percentual_multa_atraso' => 2,
                'percentual_juros_dia' => 0.0333,
                'status' => 'aberta',
                'observacoes' => 'Promissória de exportação',
            ]);
        }

        return $conta->fresh(['cliente', 'venda', 'promissoria']);
    }
}