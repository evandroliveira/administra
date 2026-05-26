<?php

namespace Database\Seeders;
use Spatie\Permission\Models\Permission;

use App\Models\Empresa;
use App\Models\Perfil;
use App\Models\User;
use App\Models\UsuarioVendas;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PerfilUsuarioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Garante que a permissão 'vendas.realizar' existe
        Permission::firstOrCreate([
            'name' => 'vendas.realizar',
            'guard_name' => 'web',
        ]);
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

        $profiles = Perfil::ensureCanonicalProfilesAndRoles();

        $admin = User::updateOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Administrador Sistema',
                'email' => 'admin@system.local',
                'password' => Hash::make('admin123'),
            ]
        );

        $admin->syncRoles([Perfil::ADMIN]);

        $perfilAdmin = $profiles[Perfil::ADMIN];

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
