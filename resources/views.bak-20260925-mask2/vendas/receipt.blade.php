<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Recibo de Venda #{{ $venda->numero }}</title>
    <style>
        :root {
            color-scheme: light;
            --page-bg: #eef2f6;
            --sheet-bg: #ffffff;
            --ink: #212529;
            --muted: #6c757d;
            --border: #dfe3e8;
            --line: #e9ecef;
            --soft: #f8f9fa;
            --accent: #0d6efd;
            --accent-deep: #0b3d91;
            --accent-soft: #eaf2ff;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            color: var(--ink);
            background:
                radial-gradient(circle at top left, rgba(13, 110, 253, 0.12), transparent 28%),
                linear-gradient(180deg, #f8fafc 0%, var(--page-bg) 100%);
        }

        .page {
            padding: 28px;
        }

        .sheet {
            max-width: 960px;
            margin: 0 auto;
            background: var(--sheet-bg);
            border-radius: 28px;
            overflow: hidden;
            box-shadow: 0 22px 48px rgba(15, 23, 42, 0.12);
        }

        .hero {
            padding: 32px 36px;
            background: linear-gradient(135deg, var(--accent-deep), #112d4e 62%, #1d4ed8 100%);
            color: #ffffff;
        }

        .hero-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.4fr) minmax(280px, 1fr);
            gap: 24px;
            align-items: start;
        }

        .eyebrow {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.16);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .brand h1 {
            margin: 16px 0 10px;
            font-size: 34px;
            line-height: 1.1;
        }

        .hero-copy {
            color: rgba(255, 255, 255, 0.82);
            font-size: 14px;
            line-height: 1.6;
        }

        .company-panel {
            padding: 18px 20px;
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(10px);
        }

        .company-panel strong {
            display: block;
            margin-bottom: 10px;
            font-size: 17px;
        }

        .company-panel div + div {
            margin-top: 6px;
        }

        .content {
            padding: 32px 36px 36px;
        }

        .muted {
            color: var(--muted);
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .box {
            padding: 20px;
            border: 1px solid var(--border);
            border-radius: 20px;
            background: linear-gradient(180deg, #ffffff 0%, var(--soft) 100%);
        }

        .box-label {
            margin-bottom: 10px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--muted);
        }

        .box strong {
            font-size: 18px;
        }

        .box div + div {
            margin-top: 6px;
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
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--muted);
        }

        tbody tr:last-child td {
            border-bottom: 0;
        }

        .num {
            text-align: right;
            white-space: nowrap;
        }

        .total-card {
            margin-top: 20px;
            display: flex;
            justify-content: flex-end;
        }

        .total {
            min-width: 290px;
            padding: 18px 22px;
            border-radius: 22px;
            background: linear-gradient(135deg, var(--accent-soft), #ffffff);
            border: 1px solid rgba(13, 110, 253, 0.18);
            text-align: right;
        }

        .total small {
            display: block;
            margin-bottom: 6px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--muted);
        }

        .total span {
            font-size: 28px;
            font-weight: 700;
            color: var(--accent-deep);
        }

        .signature {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 24px;
            margin-top: 56px;
        }

        .signature-line {
            padding-top: 14px;
            border-top: 2px solid var(--ink);
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

        @media (max-width: 780px) {
            .page {
                padding: 16px;
            }

            .hero,
            .content {
                padding: 24px;
            }

            .hero-grid,
            .grid,
            .signature {
                grid-template-columns: 1fr;
            }

            .total {
                min-width: 0;
                width: 100%;
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
                background: #143b7a;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .box,
            .total,
            th {
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
                <div class="hero-grid">
                    <div class="brand">
                        <span class="eyebrow">Comprovante de pagamento</span>
                        <h1>Recibo de Venda</h1>
                        <div class="hero-copy">Venda #{{ $venda->numero }}</div>
                        <div class="hero-copy">Emitido em {{ now()->format('d/m/Y H:i') }}</div>
                    </div>
                    <div class="company-panel">
                        <strong>{{ $empresa?->nome ?? 'Empresa' }}</strong>
                        @if ($empresa?->documento)
                            <div>Documento: {{ $empresa->documento }}</div>
                        @endif
                        @if ($empresa?->email)
                            <div>{{ $empresa->email }}</div>
                        @endif
                        @if ($empresa?->telefone)
                            <div>{{ $empresa->telefone }}</div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="content">
                <div class="grid">
                    <div class="box">
                        <div class="box-label">Cliente</div>
                        <strong>{{ $venda->cliente->nome }}</strong>
                        <div class="muted">CPF/CNPJ: {{ $venda->cliente->cpf_cnpj }}</div>
                    </div>
                    <div class="box">
                        <div class="box-label">Pagamento</div>
                        <strong>{{ $pagamento->metodo_label }}</strong>
                        <div class="muted">Data: {{ optional($pagamento->data_pagamento)->format('d/m/Y H:i') }}</div>
                        <div class="muted">Valor: R$ {{ number_format((float) $pagamento->valor_total_baixado, 2, ',', '.') }}</div>
                    </div>
                </div>

                <div class="table-shell">
                    <table>
                        <thead>
                            <tr>
                                <th>Produto</th>
                                <th class="num">Quantidade</th>
                                <th class="num">Preço</th>
                                <th class="num">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($venda->itens as $item)
                                <tr>
                                    <td>{{ $item->produto->nome ?? '-' }}</td>
                                    <td class="num">{{ $item->quantidade }}</td>
                                    <td class="num">R$ {{ number_format((float) $item->preco_unitario, 2, ',', '.') }}</td>
                                    <td class="num">R$ {{ number_format((float) $item->valor_total, 2, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="total-card">
                    <div class="total">
                        <small>Total recebido</small>
                        <span>R$ {{ number_format((float) $venda->total, 2, ',', '.') }}</span>
                    </div>
                </div>

                <div class="signature">
                    <div class="signature-line">
                        {{ $venda->cliente->nome }}
                        <span>Cliente</span>
                    </div>
                    <div class="signature-line">
                        {{ $empresa?->nome ?? 'Empresa' }}
                        <span>Recebedor</span>
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
