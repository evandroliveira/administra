<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Perfil;
use App\Models\Produto;
use App\Models\User;
use Database\Seeders\PerfilUsuarioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CadastrosCrudTest extends TestCase
{
    use RefreshDatabase;

    protected Empresa $empresa;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(PerfilUsuarioSeeder::class);

        $this->empresa = Empresa::query()->where('slug', 'administrar')->firstOrFail();
        $this->admin = $this->criarUsuarioAdminDaEmpresa($this->empresa);
    }

    public function test_cliente_crud_fluxo_basico(): void
    {
        $payloadStore = $this->payloadCliente([
            'email' => 'cliente.crud@example.com',
            'cpf_cnpj' => '44444444444',
            'nome' => 'Cliente CRUD',
        ]);

        $this->actingAs($this->admin)
            ->post(route('clientes.store'), $payloadStore)
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $cliente = Cliente::query()->where('email', 'cliente.crud@example.com')->firstOrFail();

        $this->assertDatabaseHas('clientes', [
            'id' => $cliente->id,
            'empresa_id' => $this->empresa->id,
            'nome' => 'Cliente CRUD',
        ]);

        $payloadUpdate = $this->payloadCliente([
            'nome' => 'Cliente CRUD Alterado',
            'email' => 'cliente.crud@example.com',
            'cpf_cnpj' => '44444444444',
            'limite_credito' => 1500,
        ]);

        $this->actingAs($this->admin)
            ->put(route('clientes.update', $cliente), $payloadUpdate)
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('clientes', [
            'id' => $cliente->id,
            'nome' => 'Cliente CRUD Alterado',
        ]);

        $this->actingAs($this->admin)
            ->delete(route('clientes.destroy', $cliente))
            ->assertRedirect(route('clientes.index'));

        $this->assertDatabaseMissing('clientes', ['id' => $cliente->id]);
    }

    public function test_cliente_bloqueia_email_duplicado_na_mesma_empresa(): void
    {
        Cliente::create([
            'empresa_id' => $this->empresa->id,
            'tipo' => 'PF',
            'nome' => 'Cliente Base',
            'email' => 'repetido@example.com',
            'telefone' => '(11) 91111-1111',
            'cpf_cnpj' => '55555555555',
            'endereco' => 'Rua Base',
            'numero' => '10',
            'bairro' => 'Centro',
            'cidade' => 'Sao Paulo',
            'estado' => 'SP',
            'cep' => '01000000',
            'limite_credito' => 500,
            'credito_disponivel' => 500,
            'percentual_multa_atraso_padrao' => 2,
            'percentual_juros_dia_padrao' => 0.0333,
            'ativo' => true,
        ]);

        $payload = $this->payloadCliente([
            'email' => 'repetido@example.com',
            'cpf_cnpj' => '66666666666',
        ]);

        $this->actingAs($this->admin)
            ->post(route('clientes.store'), $payload)
            ->assertSessionHasErrors(['email']);
    }

    public function test_cliente_permite_email_repetido_em_empresa_diferente(): void
    {
        $empresaSecundaria = Empresa::create([
            'nome' => 'Empresa Secundaria',
            'slug' => 'empresa-secundaria-'.Str::lower(Str::random(6)),
            'email' => 'empresa.secundaria@example.com',
            'ativa' => true,
        ]);

        Cliente::create([
            'empresa_id' => $empresaSecundaria->id,
            'tipo' => 'PF',
            'nome' => 'Cliente Outra Empresa',
            'email' => 'compartilhado@example.com',
            'telefone' => '(11) 92222-2222',
            'cpf_cnpj' => '77777777777',
            'endereco' => 'Rua Outra',
            'numero' => '20',
            'bairro' => 'Centro',
            'cidade' => 'Campinas',
            'estado' => 'SP',
            'cep' => '13000000',
            'limite_credito' => 700,
            'credito_disponivel' => 700,
            'percentual_multa_atraso_padrao' => 2,
            'percentual_juros_dia_padrao' => 0.0333,
            'ativo' => true,
        ]);

        $payload = $this->payloadCliente([
            'email' => 'compartilhado@example.com',
            'cpf_cnpj' => '88888888888',
        ]);

        $this->actingAs($this->admin)
            ->post(route('clientes.store'), $payload)
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('clientes', [
            'empresa_id' => $this->empresa->id,
            'email' => 'compartilhado@example.com',
        ]);
    }

    public function test_produto_crud_fluxo_basico_com_calculo_de_margem(): void
    {
        $payloadStore = $this->payloadProduto([
            'codigo' => 'SKU-CRUD-01',
            'nome' => 'Produto CRUD',
            'preco_custo' => 10,
            'preco_venda' => 20,
        ]);

        $this->actingAs($this->admin)
            ->post(route('produtos.store'), $payloadStore)
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $produto = Produto::query()->where('codigo', 'SKU-CRUD-01')->firstOrFail();

        $this->assertDatabaseHas('produtos', [
            'id' => $produto->id,
            'empresa_id' => $this->empresa->id,
            'margem_lucro' => 50,
        ]);

        $payloadUpdate = $this->payloadProduto([
            'codigo' => 'SKU-CRUD-01',
            'nome' => 'Produto CRUD Alterado',
            'preco_custo' => 20,
            'preco_venda' => 30,
        ]);

        $this->actingAs($this->admin)
            ->put(route('produtos.update', $produto), $payloadUpdate)
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('produtos', [
            'id' => $produto->id,
            'nome' => 'Produto CRUD Alterado',
            'margem_lucro' => 33.33,
        ]);

        $this->actingAs($this->admin)
            ->delete(route('produtos.destroy', $produto))
            ->assertRedirect(route('produtos.index'));

        $this->assertDatabaseMissing('produtos', ['id' => $produto->id]);
    }

    public function test_produto_bloqueia_codigo_duplicado_na_mesma_empresa(): void
    {
        Produto::create([
            'empresa_id' => $this->empresa->id,
            'codigo' => 'SKU-DUP-01',
            'nome' => 'Produto Base',
            'preco_custo' => 10,
            'preco_venda' => 25,
            'margem_lucro' => 60,
            'custo_medio' => 10,
            'estoque_atual' => 20,
            'estoque_minimo' => 3,
            'ativo' => true,
        ]);

        $payload = $this->payloadProduto([
            'codigo' => 'SKU-DUP-01',
            'nome' => 'Produto Novo',
        ]);

        $this->actingAs($this->admin)
            ->post(route('produtos.store'), $payload)
            ->assertSessionHasErrors(['codigo']);
    }

    public function test_produto_permite_codigo_repetido_em_empresa_diferente(): void
    {
        $empresaSecundaria = Empresa::create([
            'nome' => 'Empresa Produto',
            'slug' => 'empresa-produto-'.Str::lower(Str::random(6)),
            'email' => 'empresa.produto@example.com',
            'ativa' => true,
        ]);

        Produto::create([
            'empresa_id' => $empresaSecundaria->id,
            'codigo' => 'SKU-COMP-01',
            'nome' => 'Produto Outra Empresa',
            'preco_custo' => 10,
            'preco_venda' => 22,
            'margem_lucro' => 54.55,
            'custo_medio' => 10,
            'estoque_atual' => 15,
            'estoque_minimo' => 2,
            'ativo' => true,
        ]);

        $payload = $this->payloadProduto([
            'codigo' => 'SKU-COMP-01',
            'nome' => 'Produto Local',
        ]);

        $this->actingAs($this->admin)
            ->post(route('produtos.store'), $payload)
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('produtos', [
            'empresa_id' => $this->empresa->id,
            'codigo' => 'SKU-COMP-01',
            'nome' => 'Produto Local',
        ]);
    }

    private function criarUsuarioAdminDaEmpresa(Empresa $empresa): User
    {
        $token = Str::lower(Str::random(8));

        $user = User::factory()->create([
            'username' => 'cad_admin_'.$token,
            'email' => 'cad.admin.'.$token.'@example.com',
        ]);

        $user->assignRole(Perfil::ADMIN);

        $perfilAdmin = Perfil::query()->where('nome', Perfil::ADMIN)->first();

        $user->usuarioVendas()->create([
            'empresa_id' => $empresa->id,
            'perfil_id' => $perfilAdmin?->id,
            'ativo' => true,
            'data_contratacao' => now()->toDateString(),
        ]);

        return $user;
    }

    private function payloadCliente(array $overrides = []): array
    {
        return array_merge([
            'tipo' => 'PF',
            'nome' => 'Cliente Teste',
            'email' => 'cliente.teste@example.com',
            'telefone' => '(11) 93333-3333',
            'celular' => '(11) 94444-4444',
            'cpf_cnpj' => '99999999999',
            'rg_ie' => '1234567',
            'endereco' => 'Rua Teste',
            'numero' => '123',
            'complemento' => 'Apto 1',
            'bairro' => 'Centro',
            'cidade' => 'Sao Paulo',
            'estado' => 'SP',
            'cep' => '01000000',
            'limite_credito' => 1000,
            'percentual_multa_atraso_padrao' => 2,
            'percentual_juros_dia_padrao' => 0.0333,
            'ativo' => true,
        ], $overrides);
    }

    private function payloadProduto(array $overrides = []): array
    {
        return array_merge([
            'codigo' => 'SKU-TESTE-01',
            'nome' => 'Produto Teste',
            'descricao' => 'Descricao de teste',
            'preco_custo' => 10,
            'preco_venda' => 20,
            'custo_medio' => 10,
            'estoque_atual' => 12,
            'estoque_minimo' => 2,
            'ativo' => true,
        ], $overrides);
    }
}
