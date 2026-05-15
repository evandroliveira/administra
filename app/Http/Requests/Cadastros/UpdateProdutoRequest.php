<?php

namespace App\Http\Requests\Cadastros;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProdutoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string|\Illuminate\Validation\Rules\Unique>|string>
     */
    public function rules(): array
    {
        $empresaId = (int) optional($this->user()?->usuarioVendas)->empresa_id;
        $produtoId = (int) optional($this->route('produto'))->id;

        return [
            'codigo' => [
                'required',
                'string',
                'max:50',
                Rule::unique('produtos', 'codigo')
                    ->where(fn ($query) => $query->where('empresa_id', $empresaId))
                    ->ignore($produtoId),
            ],
            'nome' => ['required', 'string', 'max:200'],
            'descricao' => ['nullable', 'string'],
            'categoria_id' => ['nullable', 'integer', 'exists:categorias,id'],
            'preco_custo' => ['required', 'numeric', 'gt:0'],
            'preco_venda' => ['required', 'numeric', 'gt:0'],
            'custo_medio' => ['nullable', 'numeric', 'min:0'],
            'estoque_atual' => ['nullable', 'integer', 'min:0'],
            'estoque_minimo' => ['nullable', 'integer', 'min:0'],
            'ativo' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'custo_medio' => $this->input('custo_medio', 0),
            'estoque_atual' => $this->input('estoque_atual', 0),
            'estoque_minimo' => $this->input('estoque_minimo', 10),
            'ativo' => filter_var($this->input('ativo', true), FILTER_VALIDATE_BOOL),
        ]);
    }
}
