<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovimentacaoEstoque extends Model
{
    protected $table = 'movimentacao_estoques';

    protected $fillable = [
        'empresa_id',
        'produto_id',
        'tipo',
        'quantidade',
        'estoque_anterior',
        'estoque_posterior',
        'custo_unitario',
        'custo_medio_anterior',
        'custo_medio_posterior',
        'origem_tipo',
        'origem_id',
        'observacao',
    ];

    protected $casts = [
        'custo_unitario' => 'decimal:2',
        'custo_medio_anterior' => 'decimal:2',
        'custo_medio_posterior' => 'decimal:2',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function produto(): BelongsTo
    {
        return $this->belongsTo(Produto::class);
    }
}