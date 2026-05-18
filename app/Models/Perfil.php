<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class Perfil extends Model
{
    public const ADMIN = 'admin';
    public const GERENTE = 'gerente';
    public const VENDEDOR = 'vendedor';
    public const RECEPCAO = 'recepcao';

    protected $table = 'perfis';

    protected $fillable = [
        'nome',
        'descricao',
        'pode_vender',
        'pode_gerar_relatorios',
        'pode_gerenciar_usuarios',
        'pode_gerenciar_financeiro',
        'pode_editar_produtos',
        'pode_editar_clientes',
    ];

    protected $casts = [
        'pode_vender' => 'boolean',
        'pode_gerar_relatorios' => 'boolean',
        'pode_gerenciar_usuarios' => 'boolean',
        'pode_gerenciar_financeiro' => 'boolean',
        'pode_editar_produtos' => 'boolean',
        'pode_editar_clientes' => 'boolean',
    ];

    public static function canonicalDefinitions(): array
    {
        return [
            self::ADMIN => [
                'descricao' => 'Administrador do sistema',
                'pode_vender' => true,
                'pode_gerar_relatorios' => true,
                'pode_gerenciar_usuarios' => true,
                'pode_gerenciar_financeiro' => true,
                'pode_editar_produtos' => true,
                'pode_editar_clientes' => true,
                'permissions' => [
                    'vendas.realizar',
                    'relatorios.visualizar',
                    'usuarios.gerenciar',
                    'financeiro.gerenciar',
                    'produtos.editar',
                    'clientes.editar',
                ],
            ],
            self::GERENTE => [
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
            self::VENDEDOR => [
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
            self::RECEPCAO => [
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
    }

    public static function ensureCanonicalProfilesAndRoles(): array
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $definitions = static::canonicalDefinitions();
        $permissions = collect($definitions)
            ->flatMap(fn (array $definition) => $definition['permissions'])
            ->unique()
            ->values();

        foreach ($permissions as $permissionName) {
            Permission::findOrCreate($permissionName, 'web');
        }

        $profiles = [];

        foreach ($definitions as $name => $definition) {
            $profilePermissions = $definition['permissions'];
            unset($definition['permissions']);

            $profiles[$name] = static::query()->updateOrCreate(
                ['nome' => $name],
                ['nome' => $name, ...$definition]
            );

            $role = Role::findOrCreate($name, 'web');
            $role->syncPermissions($profilePermissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $profiles;
    }

    public static function ensureCanonicalProfile(string $name): self
    {
        $profiles = static::ensureCanonicalProfilesAndRoles();

        return $profiles[$name] ?? static::query()->where('nome', $name)->firstOrFail();
    }

    public function usuariosVendas(): HasMany
    {
        return $this->hasMany(UsuarioVendas::class);
    }
}
