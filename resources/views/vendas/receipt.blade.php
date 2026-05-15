<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Recibo de Venda #{{ $venda->numero }}</title>
    <style>
        body { font-family: Georgia, serif; color: #1f2937; margin: 0; background: #f3f4f6; }
        .sheet { max-width: 860px; margin: 24px auto; background: #fff; padding: 32px; box-shadow: 0 12px 28px rgba(15, 23, 42, 0.12); }
        .top { display: flex; justify-content: space-between; gap: 24px; border-bottom: 2px solid #111827; padding-bottom: 18px; margin-bottom: 24px; }
        .brand h1 { margin: 0; font-size: 28px; }
        .muted { color: #6b7280; }
        .grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; margin-bottom: 24px; }
        .box { border: 1px solid #d1d5db; padding: 16px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        th, td { border-bottom: 1px solid #e5e7eb; padding: 10px 8px; text-align: left; }
        th { text-transform: uppercase; font-size: 12px; letter-spacing: .05em; color: #6b7280; }
        .total { display: flex; justify-content: flex-end; font-size: 22px; font-weight: 700; margin-top: 12px; }
        .signature { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 24px; margin-top: 56px; }
        .signature-line { border-top: 1px solid #111827; padding-top: 10px; text-align: center; }
        @media print {
            body { background: #fff; }
            .sheet { margin: 0; box-shadow: none; max-width: none; }
        }
    </style>
</head>
<body>
    <div class="sheet">
        <div class="top">
            <div class="brand">
                <h1>Recibo de Venda</h1>
                <div class="muted">Venda #{{ $venda->numero }}</div>
                <div class="muted">Emitido em {{ now()->format('d/m/Y H:i') }}</div>
            </div>
            <div>
                <strong>{{ $empresa?->nome ?? 'Empresa' }}</strong><br>
                @if ($empresa?->documento)
                    <span class="muted">Documento: {{ $empresa->documento }}</span><br>
                @endif
                @if ($empresa?->email)
                    <span class="muted">{{ $empresa->email }}</span><br>
                @endif
                @if ($empresa?->telefone)
                    <span class="muted">{{ $empresa->telefone }}</span>
                @endif
            </div>
        </div>

        <div class="grid">
            <div class="box">
                <strong>Cliente</strong><br>
                {{ $venda->cliente->nome }}<br>
                <span class="muted">CPF/CNPJ: {{ $venda->cliente->cpf_cnpj }}</span>
            </div>
            <div class="box">
                <strong>Pagamento</strong><br>
                Método: {{ $pagamento->metodo_label }}<br>
                Data: {{ optional($pagamento->data_pagamento)->format('d/m/Y H:i') }}<br>
                Valor: R$ {{ number_format((float) $pagamento->valor_total_baixado, 2, ',', '.') }}
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Produto</th>
                    <th>Quantidade</th>
                    <th>Preço</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($venda->itens as $item)
                    <tr>
                        <td>{{ $item->produto->nome ?? '-' }}</td>
                        <td>{{ $item->quantidade }}</td>
                        <td>R$ {{ number_format((float) $item->preco_unitario, 2, ',', '.') }}</td>
                        <td>R$ {{ number_format((float) $item->valor_total, 2, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="total">Total recebido: R$ {{ number_format((float) $venda->total, 2, ',', '.') }}</div>

        <div class="signature">
            <div class="signature-line">
                {{ $venda->cliente->nome }}<br>
                Cliente
            </div>
            <div class="signature-line">
                {{ $empresa?->nome ?? 'Empresa' }}<br>
                Recebedor
            </div>
        </div>
    </div>

    @if ($autoPrint)
        <script>window.print();</script>
    @endif
</body>
</html>
