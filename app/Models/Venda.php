<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Model;

class Venda extends Model
{
    protected $primaryKey = 'numero';

    protected $fillable = [
        'empresa_id',
        'cliente_id',
        'vendedor_id',
        'data_entrega',
        'status',
        'subtotal',
        'desconto',
        'frete',
        'total',
        'percentual_multa_atraso_promissoria',
        'percentual_juros_dia_promissoria',
        'emitir_nota_fiscal',
        'status_nota_fiscal',
        'nota_fiscal_numero',
        'nota_fiscal_serie',
        'nota_fiscal_chave',
        'nota_fiscal_protocolo',
        'nota_fiscal_url_pdf',
        'nota_fiscal_url_xml',
        'nota_fiscal_mensagem',
        'nota_fiscal_payload',
        'nota_fiscal_emitida_em',
        'lucro_total',
        'observacoes',
        'cancelamento_motivo',
    ];

    protected $casts = [
        'data_venda' => 'datetime',
        'data_entrega' => 'date',
        'subtotal' => 'decimal:2',
        'desconto' => 'decimal:2',
        'frete' => 'decimal:2',
        'total' => 'decimal:2',
        'percentual_multa_atraso_promissoria' => 'decimal:2',
        'percentual_juros_dia_promissoria' => 'decimal:4',
        'emitir_nota_fiscal' => 'boolean',
        'nota_fiscal_payload' => 'array',
        'nota_fiscal_emitida_em' => 'datetime',
        'lucro_total' => 'decimal:2',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function vendedor(): BelongsTo
    {
        return $this->belongsTo(UsuarioVendas::class, 'vendedor_id');
    }

    public function itens(): HasMany
    {
        return $this->hasMany(ItemVenda::class, 'venda_numero', 'numero');
    }

    public function contaReceber(): HasOne
    {
        return $this->hasOne(ContaReceber::class, 'venda_numero', 'numero');
    }

    public function promissoria(): HasOne
    {
        return $this->hasOne(Promissoria::class, 'venda_numero', 'numero');
    }

    public function notaFiscalEmitida(): bool
    {
        return $this->status_nota_fiscal === 'emitida';
    }

    public function podeEmitirNotaFiscal(): bool
    {
        return in_array((string) $this->status, ['confirmada', 'concluida'], true)
            && ! $this->notaFiscalEmitida();
    }
}
