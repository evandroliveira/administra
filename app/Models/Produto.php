<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Produto extends Model
{
    protected $fillable = [
        'empresa_id',
        'codigo',
        'nome',
        'descricao',
        'categoria_id',
        'preco_custo',
        'preco_venda',
        'margem_lucro',
        'custo_medio',
        'estoque_atual',
        'estoque_minimo',
        'ativo',
        'imagem',
    ];

    protected $casts = [
        'preco_custo' => 'decimal:2',
        'preco_venda' => 'decimal:2',
        'margem_lucro' => 'decimal:2',
        'custo_medio' => 'decimal:2',
        'ativo' => 'boolean',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    public function itensVenda(): HasMany
    {
        return $this->hasMany(ItemVenda::class);
    }

    public function imagens(): HasMany
    {
        return $this->hasMany(ProdutoImagem::class)->orderBy('ordem')->orderBy('id');
    }

    public function movimentacoesEstoque(): HasMany
    {
        return $this->hasMany(MovimentacaoEstoque::class)->latest('created_at')->latest('id');
    }

    public function getCustoBaseEstoqueAttribute(): string
    {
        return (float) $this->custo_medio > 0
            ? (string) $this->custo_medio
            : (string) $this->preco_custo;
    }
}
