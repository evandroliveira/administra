<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventoWebhook extends Model
{
    protected $table = 'evento_webhooks';

    protected $fillable = [
        'provider',
        'event_type',
        'external_id',
        'empresa_id',
        'assinatura_id',
        'fatura_id',
        'payload',
        'status',
        'erro',
        'processado_em',
    ];

    protected $casts = [
        'payload' => 'array',
        'processado_em' => 'datetime',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function assinatura(): BelongsTo
    {
        return $this->belongsTo(Assinatura::class);
    }

    public function fatura(): BelongsTo
    {
        return $this->belongsTo(Fatura::class);
    }
}