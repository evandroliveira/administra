<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\ContaPagar;
use App\Models\ContaReceber;
use App\Models\Empresa;
use App\Models\Perfil;
use App\Models\Produto;
use App\Models\User;
use App\Models\Venda;
use Database\Seeders\PerfilUsuarioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class EscopoEmpresaTest extends TestCase
{
    use RefreshDatabase;

    protected Empresa $empresaA;
    protected Empresa $empresaB;
    protected User $adminEmpresaA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(PerfilUsuarioSeeder::class);

        $this->empresaA = Empresa::query()->where('slug', 'administrar')->firstOrFail();

        $token = Str::lower(Str::random(8));
        $this->empresaB = Empresa::create([
            'nome' => 'Empresa '.$token,
            'slug' => 'empresa-'.$token,
            'email' => 'empresa.'.$token.'@example.com',
            'ativa' => true,
        ]);

        $this->adminEmpresaA = $this->criarUsuarioDaEmpresa($this->empresaA, Perfil::ADMIN);
    }

    public function test_admin_nao_acessa_cliente_de_outra_empresa(): void
    {
        $clienteEmpresaB = $this->criarCliente($this->empresaB, 'cliente.escopo1@example.com', '11111111111');

        $this->actingAs($this->adminEmpresaA)
            ->get(route('clientes.show', $clienteEmpresaB))
            ->assertForbidden();
    }

    public function test_admin_nao_acessa_produto_de_outra_empresa(): void
    {
        $produtoEmpresaB = $this->criarProduto($this->empresaB, 'SKU-ESC-01');

        $this->actingAs($this->adminEmpresaA)
            ->get(route('produtos.show', $produtoEmpresaB))
            ->assertForbidden();
    }

    public function test_admin_nao_acessa_venda_de_outra_empresa(): void
    {
        $clienteEmpresaB = $this->criarCliente($this->empresaB, 'cliente.escopo2@example.com', '22222222222');

        $vendaEmpresaB = Venda::create([
            'empresa_id' => $this->empresaB->id,
            'cliente_id' => $clienteEmpresaB->id,
            'status' => 'pendente',
            'subtotal' => 100,
            'desconto' => 0,
            'frete' => 0,
            'total' => 100,
            'lucro_total' => 20,
        ]);

        $this->actingAs($this->adminEmpresaA)
            ->get(route('vendas.show', $vendaEmpresaB))
            ->assertForbidden();
    }

    public function test_admin_nao_acessa_conta_receber_de_outra_empresa(): void
    {
        $clienteEmpresaB = $this->criarCliente($this->empresaB, 'cliente.escopo3@example.com', '33333333333');

        $vendaEmpresaB = Venda::create([
            'empresa_id' => $this->empresaB->id,
            'cliente_id' => $clienteEmpresaB->id,
            'status' => 'pendente',
            'subtotal' => 120,
            'desconto' => 10,
            'frete' => 0,
            'total' => 110,
            'lucro_total' => 15,
        ]);

        $contaReceberEmpresaB = ContaReceber::create([
            'empresa_id' => $this->empresaB->id,
            'venda_numero' => $vendaEmpresaB->numero,
            'cliente_id' => $clienteEmpresaB->id,
            'valor_original' => 110,
            'valor_pago' => 0,
            'valor_juros' => 0,
            'data_vencimento' => now()->addDays(15)->toDateString(),
            'status' => 'aberta',
        ]);

        $this->actingAs($this->adminEmpresaA)
            ->get(route('contas.receber.show', $contaReceberEmpresaB))
            ->assertForbidden();
    }

    public function test_admin_nao_acessa_conta_pagar_de_outra_empresa(): void
    {
        $contaPagarEmpresaB = ContaPagar::create([
            'empresa_id' => $this->empresaB->id,
            'descricao' => 'Despesa de teste',
            'fornecedor' => 'Fornecedor Externo',
            'valor_original' => 500,
            'valor_pago' => 0,
            'valor_juros' => 0,
            'data_vencimento' => now()->addDays(7)->toDateString(),
            'status' => 'aberta',
        ]);

        $this->actingAs($this->adminEmpresaA)
            ->get(route('contas.pagar.show', $contaPagarEmpresaB))
            ->assertForbidden();
    }

    private function criarUsuarioDaEmpresa(Empresa $empresa, string $role): User
    {
        $token = Str::lower(Str::random(8));

        $user = User::factory()->create([
            'username' => 'escopo_'.$role.'_'.$token,
            'email' => 'escopo.'.$role.'.'.$token.'@example.com',
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

    private function criarCliente(Empresa $empresa, string $email, string $documento): Cliente
    {
        return Cliente::create([
            'empresa_id' => $empresa->id,
            'tipo' => 'PF',
            'nome' => 'Cliente '.Str::lower(Str::random(6)),
            'email' => $email,
            'telefone' => '(11) 98888-0000',
            'cpf_cnpj' => $documento,
            'endereco' => 'Rua Escopo',
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
    }

    private function criarProduto(Empresa $empresa, string $codigo): Produto
    {
        return Produto::create([
            'empresa_id' => $empresa->id,
            'codigo' => $codigo,
            'nome' => 'Produto '.Str::lower(Str::random(5)),
            'preco_custo' => 10,
            'preco_venda' => 20,
            'margem_lucro' => 50,
            'custo_medio' => 10,
            'estoque_atual' => 15,
            'estoque_minimo' => 2,
            'ativo' => true,
        ]);
    }
}
