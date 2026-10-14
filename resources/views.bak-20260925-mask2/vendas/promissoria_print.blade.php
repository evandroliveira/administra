<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nota Promissória #{{ $promissoria->id }}</title>
    <style>
        :root {
            color-scheme: light;
            --page-bg: #eff3f8;
            --sheet-bg: #ffffff;
            --ink: #1f2933;
            --muted: #6c757d;
            --border: #dfe3e8;
            --line: #e9ecef;
            --soft: #f8f9fa;
            --accent: #198754;
            --accent-deep: #0f5132;
            --accent-soft: #eaf7ef;
            --warning-soft: #fff4db;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background:
                radial-gradient(circle at top right, rgba(25, 135, 84, 0.12), transparent 28%),
                linear-gradient(180deg, #f8fafc 0%, var(--page-bg) 100%);
            color: var(--ink);
        }

        .page {
            padding: 28px;
        }

        .sheet {
            max-width: 980px;
            margin: 0 auto;
            background: var(--sheet-bg);
            border-radius: 28px;
            overflow: hidden;
            box-shadow: 0 22px 48px rgba(15, 23, 42, 0.12);
        }

        .hero {
            padding: 34px 38px;
            background: linear-gradient(135deg, var(--accent-deep), #115e59 58%, #198754 100%);
            color: #ffffff;
        }

        .header {
            display: grid;
            grid-template-columns: minmax(0, 1.6fr) minmax(250px, 0.8fr);
            gap: 24px;
            align-items: start;
        }

        .eyebrow {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.14);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .title {
            margin: 16px 0 10px;
            font-size: 36px;
            line-height: 1.08;
        }

        .subtitle {
            margin-top: 6px;
            color: rgba(255, 255, 255, 0.82);
            line-height: 1.6;
        }

        .summary-amount {
            padding: 18px 20px;
            border-radius: 22px;
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(10px);
        }

        .summary-amount .label {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.72);
        }

        .summary-amount .value {
            margin-top: 8px;
            font-size: 32px;
            font-weight: 700;
        }

        .content {
            padding: 32px 38px 38px;
        }

        .card-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
            margin: 0 0 24px;
        }

        .card {
            padding: 18px;
            border: 1px solid var(--border);
            border-radius: 20px;
            background: linear-gradient(180deg, #ffffff 0%, var(--soft) 100%);
        }

        .label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--muted);
            letter-spacing: 0.12em;
        }

        .value {
            font-size: 20px;
            font-weight: 700;
            margin-top: 8px;
        }

        .table-shell {
            border: 1px solid var(--border);
            border-radius: 22px;
            overflow: hidden;
            background: #ffffff;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 14px 16px;
            text-align: left;
            border-bottom: 1px solid var(--line);
        }

        th {
            background: var(--soft);
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--muted);
            letter-spacing: 0.1em;
        }

        tbody tr:last-child td {
            border-bottom: 0;
        }

        .num {
            text-align: right;
            white-space: nowrap;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .badge-success {
            background: var(--accent-soft);
            color: var(--accent-deep);
        }

        .badge-warning {
            background: var(--warning-soft);
            color: #8a5a00;
        }

        .badge-secondary {
            background: #edf2f7;
            color: #495057;
        }

        .text-block {
            margin-top: 24px;
            padding: 22px 24px;
            border-radius: 22px;
            background: linear-gradient(135deg, var(--accent-soft), #ffffff);
            border: 1px solid rgba(25, 135, 84, 0.15);
            line-height: 1.75;
        }

        .text-block strong {
            color: var(--accent-deep);
        }

        .signature {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 28px;
            margin-top: 56px;
        }

        .signature-line {
            border-top: 2px solid var(--ink);
            padding-top: 14px;
            text-align: center;
            font-size: 14px;
        }

        .signature-line span {
            display: block;
            margin-top: 6px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--muted);
        }

        @media (max-width: 860px) {
            .page {
                padding: 16px;
            }

            .hero,
            .content {
                padding: 24px;
            }

            .header,
            .card-grid,
            .signature {
                grid-template-columns: 1fr;
            }
        }

        @media print {
            body {
                background: #fff;
            }

            .page {
                padding: 0;
            }

            .sheet {
                margin: 0;
                max-width: none;
                border-radius: 0;
                box-shadow: none;
            }

            .hero {
                background: #146c43;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .card,
            .text-block,
            th,
            .badge {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="sheet">
            <div class="hero">
                <div class="header">
                    <div>
                        <span class="eyebrow">Documento de cobrança</span>
                        <h1 class="title">Nota Promissória</h1>
                        <div class="subtitle">Venda #{{ $venda->numero }} | Promissória #{{ $promissoria->id }} | Emitida em {{ optional($promissoria->data_emissao)->format('d/m/Y') }}</div>
                        @if ($empresa)
                            <div class="subtitle"><strong>Credor:</strong> {{ $empresa->nome }}@if($empresa->documento) - {{ $empresa->documento }}@endif</div>
                        @endif
                    </div>
                    <div class="summary-amount">
                        <div class="label">Valor financiado</div>
                        <div class="value">R$ {{ number_format((float) $promissoria->valor_financiado, 2, ',', '.') }}</div>
                    </div>
                </div>
            </div>

            <div class="content">
                <div class="card-grid">
                    <div class="card"><div class="label">Cliente</div><div class="value">{{ $promissoria->cliente->nome }}</div></div>
                    <div class="card"><div class="label">Entrada</div><div class="value">R$ {{ number_format((float) $promissoria->valor_entrada, 2, ',', '.') }}</div></div>
                    <div class="card"><div class="label">Multa</div><div class="value">{{ number_format((float) $promissoria->percentual_multa_atraso, 2, ',', '.') }}%</div></div>
                    <div class="card"><div class="label">Juros ao dia</div><div class="value">{{ number_format((float) $promissoria->percentual_juros_dia, 4, ',', '.') }}%</div></div>
                </div>

                <div class="table-shell">
                    <table>
                        <thead>
                            <tr>
                                <th>Parcela</th>
                                <th>Vencimento</th>
                                <th class="num">Valor</th>
                                <th class="num">Pago</th>
                                <th class="num">Abatimento</th>
                                <th class="num">Saldo</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($promissoria->parcelas as $parcela)
                                @php
                                    $statusParcela = $parcela->saldo_devedor <= 0
                                        ? ['label' => 'Quitada', 'class' => 'badge-success']
                                        : ($parcela->valor_pago > 0
                                            ? ['label' => 'Parcial', 'class' => 'badge-warning']
                                            : ['label' => 'Aberta', 'class' => 'badge-secondary']);
                                @endphp
                                <tr>
                                    <td>{{ $parcela->numero }}/{{ $promissoria->quantidade_parcelas }}</td>
                                    <td>{{ optional($parcela->data_vencimento)->format('d/m/Y') }}</td>
                                    <td class="num">R$ {{ number_format((float) $parcela->valor_original, 2, ',', '.') }}</td>
                                    <td class="num">R$ {{ number_format((float) $parcela->valor_pago, 2, ',', '.') }}</td>
                                    <td class="num">R$ {{ number_format((float) $parcela->valor_abatimento, 2, ',', '.') }}</td>
                                    <td class="num">R$ {{ number_format((float) $parcela->saldo_devedor, 2, ',', '.') }}</td>
                                    <td><span class="badge {{ $statusParcela['class'] }}">{{ $statusParcela['label'] }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="text-block">
                    Declaro que pagarei ao credor acima identificado o valor financiado desta promissória, conforme parcelas e vencimentos descritos neste documento.
                    @if ($promissoria->observacoes)
                        <br><br><strong>Observações:</strong> {{ $promissoria->observacoes }}
                    @endif
                </div>

                <div class="signature">
                    <div class="signature-line">
                        {{ $promissoria->cliente->nome }}
                        <span>Cliente</span>
                    </div>
                    <div class="signature-line">
                        {{ $empresa?->nome ?? 'Credor' }}
                        <span>Credor</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if ($autoPrint)
        <script>
            window.addEventListener('load', function () {
                window.print();
            });
        </script>
    @endif
</body>
</html>
