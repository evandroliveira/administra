<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProdutoImagem extends Model
{
    protected $table = 'produto_imagens';

    protected $fillable = [
        'empresa_id',
        'produto_id',
        'imagem',
        'ordem',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function produto(): BelongsTo
    {
        return $this->belongsTo(Produto::class);
    }

    public function getPreviewUrlAttribute(): ?string
    {
        $path = trim((string) $this->imagem);

        if ($path === '') {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->url($path);
        }

        if (file_exists(public_path($path))) {
            return asset($path);
        }

        return null;
    }
}