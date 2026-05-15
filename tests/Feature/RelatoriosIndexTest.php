<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\User;
use Database\Seeders\PerfilUsuarioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RelatoriosIndexTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_exibe_central_de_relatorios_com_links_filtrados(): void
    {
        $user = $this->criarUsuarioComRole('admin', 'admin_central_relatorios', 'admin.central@example.com');

        $response = $this->actingAs($user)->get(route('relatorios.index', [
            'data_inicio' => '2026-05-01',
            'data_fim' => '2026-05-31',
        ]));

        $response->assertOk()
            ->assertSee('Central de Relatórios', false)
            ->assertSee('2026-05-01', false)
            ->assertSee('2026-05-31', false)
            ->assertSee(route('relatorios.faturamento', ['data_inicio' => '2026-05-01', 'data_fim' => '2026-05-31']))
            ->assertSee(route('relatorios.faturamento', ['data_inicio' => '2026-05-01', 'data_fim' => '2026-05-31', 'export' => 'xlsx']))
            ->assertSee(route('relatorios.lucro', ['data_inicio' => '2026-05-01', 'data_fim' => '2026-05-31', 'export' => 'pdf']))
            ->assertSee(route('relatorios.inadimplentes', ['export' => 'csv']));
    }

    public function test_vendedor_nao_acessa_central_de_relatorios(): void
    {
        $user = $this->criarUsuarioComRole('vendedor', 'vendedor_central_relatorios', 'vendedor.central@example.com');

        $this->actingAs($user)
            ->get(route('relatorios.index'))
            ->assertForbidden();
    }

    private function criarUsuarioComRole(string $role, string $username, string $email): User
    {
        $this->seed(PerfilUsuarioSeeder::class);

        $empresa = Empresa::query()->where('slug', 'administrar')->firstOrFail();
        $user = User::factory()->create([
            'username' => $username,
            'email' => $email,
        ]);

        $user->assignRole($role);
        $user->usuarioVendas()->create([
            'empresa_id' => $empresa->id,
            'perfil_id' => null,
            'ativo' => true,
            'data_contratacao' => now()->toDateString(),
        ]);

        return $user;
    }
}