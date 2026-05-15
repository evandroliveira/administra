<?php

namespace App\Http\Requests\Financeiro;

use Illuminate\Foundation\Http\FormRequest;

class StorePagamentoReceberRequest extends FormRequest
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
     * @return array<string, array<int, string>|string>
     */
    public function rules(): array
    {
        return [
            'valor' => ['required', 'numeric', 'gt:0'],
            'valor_abatimento' => ['nullable', 'numeric', 'min:0'],
            'valor_multa' => ['nullable', 'numeric', 'min:0'],
            'valor_juros' => ['nullable', 'numeric', 'min:0'],
            'metodo' => ['nullable', 'in:dinheiro,cheque,cartao,pix,transferencia,outro'],
            'promissoria_parcela_id' => ['nullable', 'integer', 'exists:promissoria_parcelas,id'],
            'observacoes' => ['nullable', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'valor_abatimento' => $this->input('valor_abatimento', 0),
            'valor_multa' => $this->input('valor_multa', 0),
            'valor_juros' => $this->input('valor_juros', 0),
            'metodo' => $this->input('metodo', 'dinheiro'),
        ]);
    }
}
