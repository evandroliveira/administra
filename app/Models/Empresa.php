<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Empresa extends Model
{
    protected $fillable = [
        'nome',
        'slug',
        'documento',
        'email',
        'telefone',
        'logo',
        'ativa',
    ];

    protected $casts = [
        'ativa' => 'boolean',
    ];

    public function clientes(): HasMany
    {
        return $this->hasMany(Cliente::class);
    }

    public function categorias(): HasMany
    {
        return $this->hasMany(Categoria::class);
    }

    public function produtos(): HasMany
    {
        return $this->hasMany(Produto::class);
    }

    public function vendas(): HasMany
    {
        return $this->hasMany(Venda::class);
    }

    public function contasReceber(): HasMany
    {
        return $this->hasMany(ContaReceber::class);
    }

    public function contasPagar(): HasMany
    {
        return $this->hasMany(ContaPagar::class);
    }

    public function pagamentosReceber(): HasMany
    {
        return $this->hasMany(PagamentoReceber::class);
    }

    public function pagamentosPagar(): HasMany
    {
        return $this->hasMany(PagamentoPagar::class);
    }

    public function promissorias(): HasMany
    {
        return $this->hasMany(Promissoria::class);
    }

    public function parcelasPromissoria(): HasMany
    {
        return $this->hasMany(PromissoriaParcela::class);
    }

    public function usuariosVendas(): HasMany
    {
        return $this->hasMany(UsuarioVendas::class);
    }

    public function assinaturas(): HasMany
    {
        return $this->hasMany(Assinatura::class);
    }

    public function assinaturaAtual(): HasOne
    {
        return $this->hasOne(Assinatura::class)->latestOfMany();
    }

    public function faturas(): HasMany
    {
        return $this->hasMany(Fatura::class);
    }

    public function eventosWebhook(): HasMany
    {
        return $this->hasMany(EventoWebhook::class);
    }
}
