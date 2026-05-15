<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class PagamentoReceber extends Model
{
    public const METODOS = [
        'dinheiro' => 'Dinheiro',
        'cheque' => 'Cheque',
        'cartao' => 'Cartão',
        'pix' => 'Pix',
        'transferencia' => 'Transferência',
        'outro' => 'Outro',
    ];

    protected $table = 'pagamentos_receber';

    protected $fillable = [
        'empresa_id',
        'conta_id',
        'promissoria_parcela_id',
        'data_pagamento',
        'valor',
        'valor_abatimento',
        'valor_multa',
        'valor_juros',
        'metodo',
        'observacoes',
    ];

    protected $casts = [
        'data_pagamento' => 'datetime',
        'valor' => 'decimal:2',
        'valor_abatimento' => 'decimal:2',
        'valor_multa' => 'decimal:2',
        'valor_juros' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::created(function (PagamentoReceber $pagamento): void {
            if ($pagamento->promissoriaParcela) {
                $parcela = $pagamento->promissoriaParcela;
                $parcela->valor_pago = (float) $parcela->valor_pago + (float) $pagamento->valor;
                $parcela->atualizarStatus();
                $parcela->save();

                if ((float) $pagamento->valor_abatimento > 0 && $parcela->promissoria) {
                    $parcela->promissoria->aplicarAbatimento((float) $pagamento->valor_abatimento, $parcela->id);
                }

                if ($parcela->promissoria) {
                    $parcela->promissoria->sincronizar();
                }
                return;
            }

            $conta = $pagamento->conta;
            if (! $conta) {
                return;
            }

            $conta->valor_pago = (float) $conta->valor_pago + (float) $pagamento->valor + (float) $pagamento->valor_abatimento;
            $conta->status = $conta->saldo_devedor <= 0 ? 'quitada' : 'parcial';
            $conta->save();
        });
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function conta(): BelongsTo
    {
        return $this->belongsTo(ContaReceber::class, 'conta_id');
    }

    public function promissoriaParcela(): BelongsTo
    {
        return $this->belongsTo(PromissoriaParcela::class, 'promissoria_parcela_id');
    }

    public function getValorTotalRecebidoAttribute(): float
    {
        return (float) $this->valor + (float) $this->valor_multa + (float) $this->valor_juros;
    }

    public function getValorTotalBaixadoAttribute(): float
    {
        return (float) $this->valor + (float) $this->valor_abatimento;
    }

    public function getMetodoLabelAttribute(): string
    {
        return self::METODOS[$this->metodo] ?? ucfirst((string) $this->metodo);
    }
}
