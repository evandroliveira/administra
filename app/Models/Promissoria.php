<?php

namespace App\Models;

use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Promissoria extends Model
{
    protected $table = 'promissorias';

    protected $fillable = [
        'empresa_id',
        'conta_id',
        'venda_numero',
        'cliente_id',
        'valor_entrada',
        'valor_financiado',
        'quantidade_parcelas',
        'intervalo_dias',
        'primeira_parcela_vencimento',
        'percentual_multa_atraso',
        'percentual_juros_dia',
        'status',
        'observacoes',
        'data_emissao',
    ];

    protected $casts = [
        'valor_entrada' => 'decimal:2',
        'valor_financiado' => 'decimal:2',
        'percentual_multa_atraso' => 'decimal:2',
        'percentual_juros_dia' => 'decimal:4',
        'primeira_parcela_vencimento' => 'date',
        'data_emissao' => 'datetime',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function conta(): BelongsTo
    {
        return $this->belongsTo(ContaReceber::class, 'conta_id');
    }

    public function venda(): BelongsTo
    {
        return $this->belongsTo(Venda::class, 'venda_numero', 'numero');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function parcelas(): HasMany
    {
        return $this->hasMany(PromissoriaParcela::class)->orderBy('data_vencimento')->orderBy('numero');
    }

    public function getSaldoDevedorAttribute(): float
    {
        return (float) $this->parcelas->sum('saldo_devedor');
    }

    public function getNumeroDocumentoAttribute(): string
    {
        return sprintf('PRM-%06d', $this->id);
    }

    public function gerarParcelas(): void
    {
        $this->parcelas()->delete();

        $quantidadeParcelas = max((int) $this->quantidade_parcelas, 1);
        $intervaloDias = max((int) $this->intervalo_dias, 1);
        $dataPrimeiraParcela = Carbon::parse($this->primeira_parcela_vencimento);
        $valorTotalCentavos = (int) round((float) $this->valor_financiado * 100);
        $valorBaseCentavos = intdiv($valorTotalCentavos, $quantidadeParcelas);
        $restoCentavos = $valorTotalCentavos % $quantidadeParcelas;

        for ($indice = 1; $indice <= $quantidadeParcelas; $indice++) {
            $valorParcelaCentavos = $valorBaseCentavos + ($indice <= $restoCentavos ? 1 : 0);

            PromissoriaParcela::create([
                'empresa_id' => $this->empresa_id,
                'promissoria_id' => $this->id,
                'numero' => $indice,
                'valor_original' => round($valorParcelaCentavos / 100, 2),
                'valor_pago' => 0,
                'valor_abatimento' => 0,
                'data_vencimento' => $dataPrimeiraParcela->copy()->addDays(($indice - 1) * $intervaloDias)->toDateString(),
                'status' => 'aberta',
            ]);
        }

        $this->refresh();
        $this->sincronizar();
    }

    public function aplicarAbatimento(float $valorAbatimento, ?int $ignorarParcelaId = null): float
    {
        $restante = max($valorAbatimento, 0);
        if ($restante <= 0) {
            return 0;
        }

        $parcelas = $this->parcelas()
            ->when($ignorarParcelaId, fn ($q) => $q->where('id', '!=', $ignorarParcelaId))
            ->get();

        foreach ($parcelas as $parcela) {
            $saldo = $parcela->saldo_devedor;
            if ($saldo <= 0) {
                continue;
            }

            $abatimento = min($saldo, $restante);
            $parcela->valor_abatimento = (float) $parcela->valor_abatimento + $abatimento;
            $parcela->atualizarStatus();
            $parcela->save();

            $restante -= $abatimento;
            if ($restante <= 0) {
                break;
            }
        }

        return $restante;
    }

    public function sincronizar(): void
    {
        $parcelas = $this->parcelas()->get();
        foreach ($parcelas as $parcela) {
            $parcela->atualizarStatus();
            $parcela->save();
        }

        $saldo = (float) $parcelas->sum('saldo_devedor');
        $houveMovimento = $parcelas->contains(fn ($p) => (float) $p->valor_pago > 0 || (float) $p->valor_abatimento > 0);
        $existeVencida = $parcelas->contains(fn ($p) => (float) $p->saldo_devedor > 0 && $p->esta_vencida);

        if ($saldo <= 0) {
            $this->status = 'quitada';
        } elseif ($existeVencida) {
            $this->status = 'vencida';
        } elseif ($houveMovimento) {
            $this->status = 'parcial';
        } else {
            $this->status = 'aberta';
        }

        $proximaParcela = $parcelas->first(fn ($p) => (float) $p->saldo_devedor > 0);
        $ultimaParcela = $parcelas->sortByDesc('numero')->first();

        if ($this->conta) {
            $this->conta->valor_original = $this->valor_financiado;
            $this->conta->valor_pago = max((float) $this->valor_financiado - $saldo, 0);
            $this->conta->status = $this->status;
            if ($proximaParcela) {
                $this->conta->data_vencimento = $proximaParcela->data_vencimento;
            } elseif ($ultimaParcela) {
                $this->conta->data_vencimento = $ultimaParcela->data_vencimento;
            }
            $this->conta->save();
        }

        $this->save();
    }
}
