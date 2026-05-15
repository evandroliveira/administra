<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nota Promissória #{{ $promissoria->id }}</title>
    <style>
        body { font-family: Georgia, serif; margin: 0; background: #f3f4f6; color: #111827; }
        .sheet { max-width: 920px; margin: 24px auto; background: #fff; padding: 36px; box-shadow: 0 14px 32px rgba(15, 23, 42, 0.14); }
        .header { display: flex; justify-content: space-between; gap: 24px; border-bottom: 2px solid #111827; padding-bottom: 18px; }
        .title { font-size: 32px; margin: 0; }
        .subtitle { color: #6b7280; margin-top: 6px; }
        .card-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px; margin: 24px 0; }
        .card { border: 1px solid #d1d5db; padding: 14px; }
        .label { font-size: 12px; text-transform: uppercase; color: #6b7280; letter-spacing: .05em; }
        .value { font-size: 18px; font-weight: 700; margin-top: 6px; }
        table { width: 100%; border-collapse: collapse; margin-top: 24px; }
        th, td { border-bottom: 1px solid #e5e7eb; padding: 10px 8px; text-align: left; }
        th { font-size: 12px; text-transform: uppercase; color: #6b7280; }
        .text-block { margin-top: 28px; line-height: 1.7; }
        .signature { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 28px; margin-top: 56px; }
        .signature-line { border-top: 1px solid #111827; padding-top: 10px; text-align: center; }
        @media print {
            body { background: #fff; }
            .sheet { margin: 0; box-shadow: none; max-width: none; }
        }
    </style>
</head>
<body>
    <div class="sheet">
        <div class="header">
            <div>
                <h1 class="title">Nota Promissória</h1>
                <div class="subtitle">Venda #{{ $venda->numero }} | Promissória #{{ $promissoria->id }} | Emitida em {{ optional($promissoria->data_emissao)->format('d/m/Y') }}</div>
                @if ($empresa)
                    <div class="subtitle"><strong>Credor:</strong> {{ $empresa->nome }}@if($empresa->documento) - {{ $empresa->documento }}@endif</div>
                @endif
            </div>
            <div>
                <div class="label">Valor financiado</div>
                <div class="value">R$ {{ number_format((float) $promissoria->valor_financiado, 2, ',', '.') }}</div>
            </div>
        </div>

        <div class="card-grid">
            <div class="card"><div class="label">Cliente</div><div class="value">{{ $promissoria->cliente->nome }}</div></div>
            <div class="card"><div class="label">Entrada</div><div class="value">R$ {{ number_format((float) $promissoria->valor_entrada, 2, ',', '.') }}</div></div>
            <div class="card"><div class="label">Multa</div><div class="value">{{ number_format((float) $promissoria->percentual_multa_atraso, 2, ',', '.') }}%</div></div>
            <div class="card"><div class="label">Juros ao dia</div><div class="value">{{ number_format((float) $promissoria->percentual_juros_dia, 4, ',', '.') }}%</div></div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Parcela</th>
                    <th>Vencimento</th>
                    <th>Valor</th>
                    <th>Pago</th>
                    <th>Abatimento</th>
                    <th>Saldo</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($promissoria->parcelas as $parcela)
                    <tr>
                        <td>{{ $parcela->numero }}/{{ $promissoria->quantidade_parcelas }}</td>
                        <td>{{ optional($parcela->data_vencimento)->format('d/m/Y') }}</td>
                        <td>R$ {{ number_format((float) $parcela->valor_original, 2, ',', '.') }}</td>
                        <td>R$ {{ number_format((float) $parcela->valor_pago, 2, ',', '.') }}</td>
                        <td>R$ {{ number_format((float) $parcela->valor_abatimento, 2, ',', '.') }}</td>
                        <td>R$ {{ number_format((float) $parcela->saldo_devedor, 2, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="text-block">
            Declaro que pagarei ao credor acima identificado o valor financiado desta promissória, conforme parcelas e vencimentos descritos neste documento.
            @if ($promissoria->observacoes)
                <br><br><strong>Observações:</strong> {{ $promissoria->observacoes }}
            @endif
        </div>

        <div class="signature">
            <div class="signature-line">
                {{ $promissoria->cliente->nome }}<br>
                Cliente
            </div>
            <div class="signature-line">
                {{ $empresa?->nome ?? 'Credor' }}<br>
                Credor
            </div>
        </div>
    </div>

    @if ($autoPrint)
        <script>window.print();</script>
    @endif
</body>
</html>
