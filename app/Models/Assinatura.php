<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Assinatura extends Model
{
    protected $table = 'assinaturas';

    protected $fillable = [
        'empresa_id',
        'plano_id',
        'status',
        'gateway',
        'gateway_customer_id',
        'gateway_subscription_id',
        'inicio_vigencia',
        'trial_ends_at',
        'fim_periodo_atual',
        'cancelar_no_fim_periodo',
        'ativa_ate',
    ];

    protected $casts = [
        'inicio_vigencia' => 'date',
        'trial_ends_at' => 'date',
        'fim_periodo_atual' => 'date',
        'cancelar_no_fim_periodo' => 'boolean',
        'ativa_ate' => 'date',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function plano(): BelongsTo
    {
        return $this->belongsTo(Plano::class);
    }

    public function faturas(): HasMany
    {
        return $this->hasMany(Fatura::class)->orderByDesc('vencimento')->orderByDesc('created_at');
    }

    public function eventosWebhook(): HasMany
    {
        return $this->hasMany(EventoWebhook::class);
    }

    public function estaAtiva(): bool
    {
        $hoje = Carbon::today();

        if ($this->status === 'teste') {
            $limiteTeste = $this->trial_ends_at ?? $this->ativa_ate;
            return $limiteTeste === null || $limiteTeste->greaterThanOrEqualTo($hoje);
        }

        if ($this->ativa_ate !== null && $this->ativa_ate->lt($hoje)) {
            return false;
        }

        return $this->status === 'ativa';
    }

    public function precisaRegularizar(): bool
    {
        return in_array($this->status, ['inadimplente', 'suspensa', 'cancelada'], true)
            || ! $this->estaAtiva();
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'teste' => 'Teste',
            'ativa' => 'Ativa',
            'inadimplente' => 'Inadimplente',
            'suspensa' => 'Suspensa',
            'cancelada' => 'Cancelada',
            default => ucfirst((string) $this->status),
        };
    }
}