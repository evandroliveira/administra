<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    protected $fillable = [
        'empresa_id',
        'tipo',
        'nome',
        'email',
        'telefone',
        'celular',
        'cpf_cnpj',
        'rg_ie',
        'endereco',
        'numero',
        'complemento',
        'bairro',
        'cidade',
        'estado',
        'cep',
        'limite_credito',
        'credito_disponivel',
        'percentual_multa_atraso_padrao',
        'percentual_juros_dia_padrao',
        'ativo',
    ];

    protected $casts = [
        'limite_credito' => 'decimal:2',
        'credito_disponivel' => 'decimal:2',
        'percentual_multa_atraso_padrao' => 'decimal:2',
        'percentual_juros_dia_padrao' => 'decimal:4',
        'ativo' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Cliente $cliente): void {
            if ((float) $cliente->credito_disponivel <= 0) {
                $cliente->credito_disponivel = $cliente->limite_credito;
            }
        });

        static::saved(function (Cliente $cliente): void {
            $cliente->recalcularCreditoDisponivel();
        });
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function vendas(): HasMany
    {
        return $this->hasMany(Venda::class);
    }

    public function contasReceber(): HasMany
    {
        return $this->hasMany(ContaReceber::class);
    }

    public function promissorias(): HasMany
    {
        return $this->hasMany(Promissoria::class);
    }

    public function getCreditoUtilizadoAttribute(): float
    {
        return (float) $this->contasReceber()
            ->whereNotIn('status', ['quitada', 'cancelada'])
            ->get()
            ->sum(fn (ContaReceber $conta) => $conta->saldo_devedor);
    }

    public function recalcularCreditoDisponivel(bool $save = true): float
    {
        $creditoDisponivel = max((float) $this->limite_credito - $this->credito_utilizado, 0);
        $creditoDisponivel = round($creditoDisponivel, 2);

        $this->credito_disponivel = $creditoDisponivel;

        if ($save && $this->exists) {
            static::query()
                ->whereKey($this->getKey())
                ->update(['credito_disponivel' => $creditoDisponivel]);
        }

        return $creditoDisponivel;
    }
}
