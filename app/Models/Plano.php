<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plano extends Model
{
    protected $table = 'planos';

    protected $fillable = [
        'nome',
        'descricao',
        'valor_mensal',
        'limite_usuarios',
        'limite_produtos',
        'permite_promissoria',
        'permite_relatorios_pdf',
        'permite_exportacao_xlsx',
        'ativo',
    ];

    protected $casts = [
        'valor_mensal' => 'decimal:2',
        'permite_promissoria' => 'boolean',
        'permite_relatorios_pdf' => 'boolean',
        'permite_exportacao_xlsx' => 'boolean',
        'ativo' => 'boolean',
    ];

    public function assinaturas(): HasMany
    {
        return $this->hasMany(Assinatura::class);
    }
}