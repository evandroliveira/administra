<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class PromissoriaParcela extends Model
{
    protected $table = 'promissoria_parcelas';

    protected $fillable = [
        'empresa_id',
        'promissoria_id',
        'numero',
        'valor_original',
        'valor_pago',
        'valor_abatimento',
        'data_vencimento',
        'status',
        'observacoes',
    ];

    protected $casts = [
        'valor_original' => 'decimal:2',
        'valor_pago' => 'decimal:2',
        'valor_abatimento' => 'decimal:2',
        'data_vencimento' => 'date',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function promissoria(): BelongsTo
    {
        return $this->belongsTo(Promissoria::class);
    }

    public function pagamentos(): HasMany
    {
        return $this->hasMany(PagamentoReceber::class, 'promissoria_parcela_id');
    }

    public function getSaldoDevedorAttribute(): float
    {
        return max((float) $this->valor_original - (float) $this->valor_pago - (float) $this->valor_abatimento, 0);
    }

    public function getDiasAtrasoAttribute(): int
    {
        if ($this->saldo_devedor <= 0 || ! $this->data_vencimento) {
            return 0;
        }

        $dias = Carbon::today()->diffInDays(Carbon::parse($this->data_vencimento), false) * -1;
        return max($dias, 0);
    }

    public function getEstaVencidaAttribute(): bool
    {
        return $this->dias_atraso > 0;
    }

    public function calcularEncargosAtraso(): array
    {
        if ($this->dias_atraso <= 0 || $this->saldo_devedor <= 0 || ! $this->promissoria) {
            return [0.0, 0.0];
        }

        $base = $this->saldo_devedor;
        $multa = round($base * ((float) $this->promissoria->percentual_multa_atraso / 100), 2);
        $juros = round($base * ((float) $this->promissoria->percentual_juros_dia / 100) * $this->dias_atraso, 2);

        return [$multa, $juros];
    }

    public function atualizarStatus(): void
    {
        if ($this->saldo_devedor <= 0) {
            $this->status = 'quitada';
            return;
        }

        if ($this->esta_vencida) {
            $this->status = 'vencida';
            return;
        }

        if ((float) $this->valor_pago > 0 || (float) $this->valor_abatimento > 0) {
            $this->status = 'parcial';
            return;
        }

        $this->status = 'aberta';
    }
}
