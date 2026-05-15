<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

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

    public function usuariosVendas(): HasMany
    {
        return $this->hasMany(UsuarioVendas::class);
    }
}
