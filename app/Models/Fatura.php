<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Fatura extends Model
{
    protected $table = 'faturas';

    protected $fillable = [
        'empresa_id',
        'assinatura_id',
        'external_id',
        'descricao',
        'valor',
        'vencimento',
        'pago_em',
        'status',
        'invoice_url',
        'checkout_url',
        'payload',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
        'vencimento' => 'date',
        'pago_em' => 'datetime',
        'payload' => 'array',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function assinatura(): BelongsTo
    {
        return $this->belongsTo(Assinatura::class);
    }

    public function eventosWebhook(): HasMany
    {
        return $this->hasMany(EventoWebhook::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pendente' => 'Pendente',
            'paga' => 'Paga',
            'atrasada' => 'Atrasada',
            'cancelada' => 'Cancelada',
            'estornada' => 'Estornada',
            default => ucfirst((string) $this->status),
        };
    }
}