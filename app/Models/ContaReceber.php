<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Model;

class ContaReceber extends Model
{
    public const FORMAS_RECEBIMENTO = [
        'conta' => 'Conta a receber',
        'boleto' => 'Boleto bancário',
        'avista' => 'À vista',
        'promissoria' => 'Promissória',
    ];

    protected $table = 'contas_receber';

    protected $fillable = [
        'empresa_id',
        'venda_numero',
        'cliente_id',
        'valor_original',
        'valor_pago',
        'valor_juros',
        'data_vencimento',
        'data_criacao',
        'status',
        'forma_recebimento',
        'gateway',
        'gateway_payment_id',
        'gateway_invoice_url',
        'gateway_checkout_url',
        'gateway_payload',
        'observacoes',
    ];

    protected $casts = [
        'valor_original' => 'decimal:2',
        'valor_pago' => 'decimal:2',
        'valor_juros' => 'decimal:2',
        'data_vencimento' => 'date',
        'data_criacao' => 'datetime',
        'gateway_payload' => 'array',
    ];

    protected static function booted(): void
    {
        static::saved(function (ContaReceber $conta): void {
            $conta->cliente?->recalcularCreditoDisponivel();
        });

        static::deleted(function (ContaReceber $conta): void {
            $conta->cliente?->recalcularCreditoDisponivel();
        });
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function venda(): BelongsTo
    {
        return $this->belongsTo(Venda::class, 'venda_numero', 'numero');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function pagamentos(): HasMany
    {
        return $this->hasMany(PagamentoReceber::class, 'conta_id');
    }

    public function promissoria(): HasOne
    {
        return $this->hasOne(Promissoria::class, 'conta_id');
    }

    public function getSaldoDevedorAttribute(): float
    {
        return max((float) $this->valor_original - (float) $this->valor_pago + (float) $this->valor_juros, 0);
    }

    public function getFormaRecebimentoLabelAttribute(): string
    {
        return self::FORMAS_RECEBIMENTO[$this->forma_recebimento] ?? ucfirst((string) $this->forma_recebimento);
    }

    public function getBoletoUrlAttribute(): string
    {
        return trim((string) ($this->gateway_checkout_url ?: $this->gateway_invoice_url ?: ''));
    }

    public function getPossuiBoletoAttribute(): bool
    {
        return $this->boleto_url !== '';
    }
}
