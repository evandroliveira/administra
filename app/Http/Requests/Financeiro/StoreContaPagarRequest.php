<?php

namespace App\Http\Requests\Financeiro;

use Illuminate\Foundation\Http\FormRequest;

class StoreContaPagarRequest extends FormRequest
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
            'descricao' => ['required', 'string', 'max:200'],
            'fornecedor' => ['required', 'string', 'max:200'],
            'valor_original' => ['required', 'numeric', 'gt:0'],
            'valor_juros' => ['nullable', 'numeric', 'min:0'],
            'data_vencimento' => ['required', 'date'],
            'status' => ['nullable', 'in:aberta,parcial,quitada,vencida,cancelada'],
            'observacoes' => ['nullable', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'valor_juros' => $this->input('valor_juros', 0),
            'status' => $this->input('status', 'aberta'),
        ]);
    }
}
