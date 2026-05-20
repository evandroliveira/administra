<?php

namespace App\Http\Requests\Vendas;

use App\Models\PagamentoReceber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVendaRequest extends FormRequest
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
            'cliente_id' => ['required', 'integer', 'exists:clientes,id'],
            'status' => ['nullable', 'in:pendente,confirmada,concluida,cancelada'],
            'desconto' => ['nullable', 'numeric', 'min:0'],
            'frete' => ['nullable', 'numeric', 'min:0'],
            'modalidade_pagamento' => ['nullable', 'in:conta,boleto,avista,promissoria'],
            'metodo_pagamento_avista' => ['nullable', Rule::in(array_keys(PagamentoReceber::METODOS))],
            'emitir_nota_fiscal' => ['nullable', 'boolean'],
            'gerar_promissoria' => ['nullable', 'boolean'],
            'valor_entrada' => ['nullable', 'numeric', 'min:0'],
            'quantidade_parcelas' => ['nullable', 'integer', 'min:1'],
            'intervalo_dias' => ['nullable', 'integer', 'min:1'],
            'data_primeira_parcela' => ['nullable', 'date'],
            'percentual_multa_atraso' => ['nullable', 'numeric', 'min:0'],
            'percentual_juros_dia' => ['nullable', 'numeric', 'min:0'],
            'observacoes_promissoria' => ['nullable', 'string'],
            'data_entrega' => ['nullable', 'date'],
            'data_vencimento' => ['nullable', 'date'],
            'observacoes' => ['nullable', 'string'],
            'itens' => ['required', 'array', 'min:1'],
            'itens.*.produto_id' => ['required', 'integer', 'exists:produtos,id'],
            'itens.*.quantidade' => ['required', 'integer', 'min:1'],
            'itens.*.preco_unitario' => ['nullable', 'numeric', 'gt:0'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'status' => $this->input('status', 'pendente'),
            'desconto' => $this->input('desconto', 0),
            'frete' => $this->input('frete', 0),
            'modalidade_pagamento' => $this->input('modalidade_pagamento'),
            'metodo_pagamento_avista' => $this->input('metodo_pagamento_avista', 'dinheiro'),
            'emitir_nota_fiscal' => filter_var($this->input('emitir_nota_fiscal', false), FILTER_VALIDATE_BOOL),
            'gerar_promissoria' => filter_var($this->input('gerar_promissoria', false), FILTER_VALIDATE_BOOL),
            'valor_entrada' => $this->input('valor_entrada', 0),
            'quantidade_parcelas' => $this->input('quantidade_parcelas', 1),
            'intervalo_dias' => $this->input('intervalo_dias', 30),
        ]);
    }
}
