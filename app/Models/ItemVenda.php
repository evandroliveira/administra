<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class ItemVenda extends Model
{
    protected $fillable = [
        'empresa_id',
        'venda_numero',
        'produto_id',
        'quantidade',
        'preco_unitario',
        'custo_unitario',
        'valor_total',
        'lucro',
    ];

    protected $casts = [
        'preco_unitario' => 'decimal:2',
        'custo_unitario' => 'decimal:2',
        'valor_total' => 'decimal:2',
        'lucro' => 'decimal:2',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function venda(): BelongsTo
    {
        return $this->belongsTo(Venda::class, 'venda_numero', 'numero');
    }

    public function produto(): BelongsTo
    {
        return $this->belongsTo(Produto::class);
    }
}
