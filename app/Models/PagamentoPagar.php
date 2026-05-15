<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class PagamentoPagar extends Model
{
    protected $table = 'pagamentos_pagar';

    protected $fillable = [
        'empresa_id',
        'conta_id',
        'data_pagamento',
        'valor',
        'metodo',
        'observacoes',
    ];

    protected $casts = [
        'data_pagamento' => 'datetime',
        'valor' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::created(function (PagamentoPagar $pagamento): void {
            $conta = $pagamento->conta;
            if (! $conta) {
                return;
            }

            $conta->valor_pago = (float) $conta->valor_pago + (float) $pagamento->valor;
            $conta->status = $conta->saldo_devedor <= 0 ? 'quitada' : 'parcial';
            $conta->save();
        });

        static::deleted(function (PagamentoPagar $pagamento): void {
            $conta = $pagamento->conta;
            if (! $conta) {
                return;
            }

            $conta->valor_pago = max((float) $conta->valor_pago - (float) $pagamento->valor, 0);
            if ((float) $conta->valor_pago <= 0) {
                $conta->status = 'aberta';
            } elseif ($conta->saldo_devedor <= 0) {
                $conta->status = 'quitada';
            } else {
                $conta->status = 'parcial';
            }
            $conta->save();
        });
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function conta(): BelongsTo
    {
        return $this->belongsTo(ContaPagar::class, 'conta_id');
    }
}
