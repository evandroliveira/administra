<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\ContaReceber;
use App\Models\Empresa;
use App\Models\Perfil;
use App\Models\Promissoria;
use App\Models\PromissoriaParcela;
use App\Models\User;
use App\Models\Venda;
use Database\Seeders\PerfilUsuarioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Concerns\InteractsWithGeneratedSpreadsheets;
use Tests\TestCase;

class PromissoriaExportacaoTest extends TestCase
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

    public function test_listagem_exibe_acoes_de_exportacao_com_filtro_atual(): void
    {
        $this->actingAs($this->admin)
            ->get(route('promissorias.index', ['status' => 'aberta']))
            ->assertOk()
            ->assertSee('Exportar CSV', false)
            ->assertSee('Exportar XLSX', false)
            ->assertSee('Exportar PDF', false)
            ->assertSee('status=aberta&amp;export=csv', false);
    }

    public function test_exporta_csv_da_listagem_de_promissorias_filtrada(): void
    {
        $this->criarPromissoria('Cliente Promissoria Aberta', 'aberta', 220, 20, 220);
        $this->criarPromissoria('Cliente Promissoria Quitada', 'quitada', 180, 0, 0);

        $response = $this->actingAs($this->admin)->get(route('promissorias.index', [
            'status' => 'aberta',
            'export' => 'csv',
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('promissorias_aberta.csv', $response->headers->get('content-disposition'));

        $conteudo = $response->streamedContent();

        $this->assertStringContainsString('Listagem de Promissórias', $conteudo);
        $this->assertStringContainsString('Cliente Promissoria Aberta', $conteudo);
        $this->assertStringNotContainsString('Cliente Promissoria Quitada', $conteudo);
        $this->assertStringContainsString('Status aplicado;Aberta', str_replace('"', '', $conteudo));
    }

    public function test_exporta_xlsx_da_listagem_de_promissorias(): void
    {
        $promissoria = $this->criarPromissoria('Cliente Promissoria XLSX', 'parcial', 300, 50, 180);

        $response = $this->actingAs($this->admin)->get(route('promissorias.index', [
            'status' => 'parcial',
            'export' => 'xlsx',
        ]));

        $response->assertOk();
        $this->assertStringContainsString('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $response->headers->get('content-type'));
        $this->assertStringContainsString('promissorias_parcial.xlsx', $response->headers->get('content-disposition'));

        $spreadsheet = $this->openSpreadsheetFromBinary($response->streamedContent());

        $this->assertSame('Resumo', $spreadsheet->getSheet(0)->getTitle());
        $this->assertSame('Listagem de Promissórias', $spreadsheet->getSheet(0)->getCell('A1')->getValue());
        $this->assertSame('Promissorias', $spreadsheet->getSheet(1)->getTitle());
        $this->assertSame((string) $promissoria->id, (string) $spreadsheet->getSheet(1)->getCell('A4')->getValue());
        $this->assertSame('Cliente Promissoria XLSX', $spreadsheet->getSheet(1)->getCell('C4')->getValue());
    }

    public function test_exporta_pdf_da_listagem_de_promissorias(): void
    {
        $this->criarPromissoria('Cliente Promissoria PDF', 'vencida', 140, 10, 130);

        $response = $this->actingAs($this->admin)->get(route('promissorias.index', [
            'status' => 'vencida',
            'export' => 'pdf',
        ]));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('content-type'));
        $this->assertStringContainsString('promissorias_vencida.pdf', $response->headers->get('content-disposition'));
        $this->assertStringStartsWith('%PDF', $response->content());
    }

    private function criarUsuarioDaEmpresa(Empresa $empresa, string $role): User
    {
        $token = Str::lower(Str::random(8));

        $user = User::factory()->create([
            'username' => 'prom_export_'.$role.'_'.$token,
            'email' => 'prom.export.'.$role.'.'.$token.'@example.com',
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

    private function criarPromissoria(string $nomeCliente, string $status, float $valorFinanciado, float $valorEntrada, float $saldoRestante): Promissoria
    {
        $token = Str::lower(Str::random(6));

        $cliente = Cliente::create([
            'empresa_id' => $this->empresa->id,
            'tipo' => 'PF',
            'nome' => $nomeCliente,
            'email' => $token.'@example.com',
            'telefone' => '(11) 97777-0000',
            'cpf_cnpj' => (string) random_int(10000000000, 99999999999),
            'endereco' => 'Rua da Promissoria',
            'numero' => '55',
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
            'subtotal' => $valorFinanciado + $valorEntrada,
            'desconto' => 0,
            'frete' => 0,
            'total' => $valorFinanciado + $valorEntrada,
            'lucro_total' => ($valorFinanciado + $valorEntrada) * 0.2,
        ]);

        $conta = ContaReceber::create([
            'empresa_id' => $this->empresa->id,
            'venda_numero' => $venda->numero,
            'cliente_id' => $cliente->id,
            'valor_original' => $valorFinanciado,
            'valor_pago' => $valorFinanciado - $saldoRestante,
            'valor_juros' => 0,
            'data_vencimento' => now()->addDays(30)->toDateString(),
            'status' => $status,
            'observacoes' => 'Conta da promissória',
        ]);

        $promissoria = Promissoria::create([
            'empresa_id' => $this->empresa->id,
            'conta_id' => $conta->id,
            'venda_numero' => $venda->numero,
            'cliente_id' => $cliente->id,
            'valor_entrada' => $valorEntrada,
            'valor_financiado' => $valorFinanciado,
            'quantidade_parcelas' => 2,
            'intervalo_dias' => 30,
            'primeira_parcela_vencimento' => now()->addDays(30)->toDateString(),
            'percentual_multa_atraso' => 2,
            'percentual_juros_dia' => 0.0333,
            'status' => $status,
            'observacoes' => 'Promissória de exportação',
            'data_emissao' => now(),
        ]);

        $primeiraParcela = min($saldoRestante, $valorFinanciado / 2);
        $segundaParcela = max($saldoRestante - $primeiraParcela, 0);

        PromissoriaParcela::create([
            'empresa_id' => $this->empresa->id,
            'promissoria_id' => $promissoria->id,
            'numero' => 1,
            'valor_original' => round($valorFinanciado / 2, 2),
            'valor_pago' => round(($valorFinanciado / 2) - $primeiraParcela, 2),
            'valor_abatimento' => 0,
            'data_vencimento' => now()->addDays(30)->toDateString(),
            'status' => $status === 'quitada' ? 'quitada' : 'aberta',
        ]);

        PromissoriaParcela::create([
            'empresa_id' => $this->empresa->id,
            'promissoria_id' => $promissoria->id,
            'numero' => 2,
            'valor_original' => round($valorFinanciado / 2, 2),
            'valor_pago' => round(($valorFinanciado / 2) - $segundaParcela, 2),
            'valor_abatimento' => 0,
            'data_vencimento' => now()->addDays(60)->toDateString(),
            'status' => $status === 'quitada' ? 'quitada' : 'aberta',
        ]);

        return $promissoria->fresh(['cliente', 'venda', 'parcelas']);
    }
}