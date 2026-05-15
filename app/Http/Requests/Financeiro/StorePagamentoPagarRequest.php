<?php

namespace App\Http\Requests\Financeiro;

use Illuminate\Foundation\Http\FormRequest;

class StorePagamentoPagarRequest extends FormRequest
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
            'metodo' => ['nullable', 'in:dinheiro,cheque,cartao,pix,transferencia,outro'],
            'observacoes' => ['nullable', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'metodo' => $this->input('metodo', 'transferencia'),
        ]);
    }
}
