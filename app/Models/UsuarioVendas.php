<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class UsuarioVendas extends Model
{
    protected $table = 'usuario_vendas';

    protected $fillable = [
        'user_id',
        'empresa_id',
        'perfil_id',
        'telefone',
        'endereco',
        'cidade',
        'estado',
        'cep',
        'ativo',
        'data_contratacao',
    ];

    protected $casts = [
        'ativo' => 'boolean',
        'data_contratacao' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function perfil(): BelongsTo
    {
        return $this->belongsTo(Perfil::class);
    }
}
