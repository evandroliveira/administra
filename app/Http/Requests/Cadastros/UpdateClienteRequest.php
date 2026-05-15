<?php

namespace App\Http\Requests\Cadastros;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClienteRequest extends FormRequest
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
        $clienteId = (int) optional($this->route('cliente'))->id;

        return [
            'tipo' => ['required', 'in:PF,PJ'],
            'nome' => ['required', 'string', 'max:200'],
            'email' => [
                'required',
                'email',
                Rule::unique('clientes', 'email')
                    ->where(fn ($query) => $query->where('empresa_id', $empresaId))
                    ->ignore($clienteId),
            ],
            'telefone' => ['required', 'string', 'max:20'],
            'celular' => ['nullable', 'string', 'max:20'],
            'cpf_cnpj' => [
                'required',
                'string',
                'max:20',
                Rule::unique('clientes', 'cpf_cnpj')
                    ->where(fn ($query) => $query->where('empresa_id', $empresaId))
                    ->ignore($clienteId),
            ],
            'rg_ie' => ['nullable', 'string', 'max:20'],
            'endereco' => ['required', 'string'],
            'numero' => ['required', 'string', 'max:10'],
            'complemento' => ['nullable', 'string', 'max:100'],
            'bairro' => ['required', 'string', 'max:100'],
            'cidade' => ['required', 'string', 'max:100'],
            'estado' => ['required', 'string', 'size:2'],
            'cep' => ['required', 'string', 'max:10'],
            'limite_credito' => ['nullable', 'numeric', 'min:0'],
            'percentual_multa_atraso_padrao' => ['nullable', 'numeric', 'min:0'],
            'percentual_juros_dia_padrao' => ['nullable', 'numeric', 'min:0'],
            'ativo' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'tipo' => $this->input('tipo', 'PF'),
            'limite_credito' => $this->input('limite_credito', 0),
            'percentual_multa_atraso_padrao' => $this->input('percentual_multa_atraso_padrao', 2),
            'percentual_juros_dia_padrao' => $this->input('percentual_juros_dia_padrao', 0.0333),
            'ativo' => filter_var($this->input('ativo', true), FILTER_VALIDATE_BOOL),
        ]);
    }
}
