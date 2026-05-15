<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class ContaPagar extends Model
{
    protected $table = 'contas_pagar';

    protected $fillable = [
        'empresa_id',
        'descricao',
        'fornecedor',
        'valor_original',
        'valor_pago',
        'valor_juros',
        'data_vencimento',
        'data_criacao',
        'status',
        'observacoes',
    ];

    protected $casts = [
        'valor_original' => 'decimal:2',
        'valor_pago' => 'decimal:2',
        'valor_juros' => 'decimal:2',
        'data_vencimento' => 'date',
        'data_criacao' => 'datetime',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function pagamentos(): HasMany
    {
        return $this->hasMany(PagamentoPagar::class, 'conta_id');
    }

    public function getSaldoDevedorAttribute(): float
    {
        return max((float) $this->valor_original - (float) $this->valor_pago + (float) $this->valor_juros, 0);
    }
}
