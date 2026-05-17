<?php

namespace App\Http\Requests\Cadastros;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoriaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $empresaId = (int) optional($this->user()?->usuarioVendas)->empresa_id;
        $categoriaId = (int) optional($this->route('categoria'))->id;

        return [
            'nome' => [
                'required',
                'string',
                'max:100',
                Rule::unique('categorias', 'nome')
                    ->where(fn ($query) => $query->where('empresa_id', $empresaId))
                    ->ignore($categoriaId),
            ],
            'descricao' => ['nullable', 'string'],
            'ativo' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nome' => trim((string) $this->input('nome', '')),
            'ativo' => filter_var($this->input('ativo', true), FILTER_VALIDATE_BOOL),
        ]);
    }
}