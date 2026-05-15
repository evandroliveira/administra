<?php

namespace Database\Seeders;

use App\Models\Empresa;
use App\Models\Perfil;
use App\Models\User;
use App\Models\UsuarioVendas;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PerfilUsuarioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $empresaPadrao = Empresa::firstOrCreate(
            ['slug' => 'administrar'],
            [
                'nome' => 'Administrar',
                'documento' => null,
                'email' => 'admin@system.local',
                'telefone' => null,
                'ativa' => true,
            ]
        );

        $permissoes = [
            'vendas.realizar',
            'relatorios.visualizar',
            'usuarios.gerenciar',
            'financeiro.gerenciar',
            'produtos.editar',
            'clientes.editar',
        ];

        foreach ($permissoes as $permissao) {
            Permission::findOrCreate($permissao, 'web');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $perfisData = [
            [
                'nome' => Perfil::ADMIN,
                'descricao' => 'Administrador do sistema',
                'pode_vender' => true,
                'pode_gerar_relatorios' => true,
                'pode_gerenciar_usuarios' => true,
                'pode_gerenciar_financeiro' => true,
                'pode_editar_produtos' => true,
                'pode_editar_clientes' => true,
                'permissions' => $permissoes,
            ],
            [
                'nome' => Perfil::GERENTE,
                'descricao' => 'Gerente/Supervisor',
                'pode_vender' => true,
                'pode_gerar_relatorios' => true,
                'pode_gerenciar_usuarios' => false,
                'pode_gerenciar_financeiro' => true,
                'pode_editar_produtos' => true,
                'pode_editar_clientes' => true,
                'permissions' => [
                    'vendas.realizar',
                    'relatorios.visualizar',
                    'financeiro.gerenciar',
                    'produtos.editar',
                    'clientes.editar',
                ],
            ],
            [
                'nome' => Perfil::VENDEDOR,
                'descricao' => 'Vendedor',
                'pode_vender' => true,
                'pode_gerar_relatorios' => false,
                'pode_gerenciar_usuarios' => false,
                'pode_gerenciar_financeiro' => false,
                'pode_editar_produtos' => false,
                'pode_editar_clientes' => true,
                'permissions' => [
                    'vendas.realizar',
                    'clientes.editar',
                ],
            ],
            [
                'nome' => Perfil::RECEPCAO,
                'descricao' => 'Recepção/Atendimento',
                'pode_vender' => false,
                'pode_gerar_relatorios' => false,
                'pode_gerenciar_usuarios' => false,
                'pode_gerenciar_financeiro' => false,
                'pode_editar_produtos' => false,
                'pode_editar_clientes' => true,
                'permissions' => [
                    'clientes.editar',
                ],
            ],
        ];

        foreach ($perfisData as $perfilData) {
            $permissions = $perfilData['permissions'];
            unset($perfilData['permissions']);

            Perfil::updateOrCreate(
                ['nome' => $perfilData['nome']],
                $perfilData
            );

            $role = Role::findOrCreate($perfilData['nome'], 'web');
            $role->syncPermissions($permissions);
        }

        $admin = User::updateOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Administrador Sistema',
                'email' => 'admin@system.local',
                'password' => Hash::make('admin123'),
            ]
        );

        $admin->syncRoles([Perfil::ADMIN]);

        $perfilAdmin = Perfil::where('nome', Perfil::ADMIN)->firstOrFail();

        UsuarioVendas::updateOrCreate(
            ['user_id' => $admin->id],
            [
                'empresa_id' => $empresaPadrao->id,
                'perfil_id' => $perfilAdmin->id,
                'ativo' => true,
                'data_contratacao' => now()->toDateString(),
            ]
        );
    }
}
