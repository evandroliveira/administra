<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <style>
        @page {
            margin: 144px 28px 52px 28px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #111827;
            line-height: 1.45;
        }

        header {
            position: fixed;
            top: -122px;
            left: 0;
            right: 0;
            height: 108px;
        }

        footer {
            position: fixed;
            bottom: -38px;
            left: 0;
            right: 0;
            font-size: 9px;
            color: #6b7280;
        }

        h1 {
            margin: 0;
            font-size: 24px;
        }

        h2 {
            margin: 0 0 10px 0;
            font-size: 15px;
            color: #0f172a;
        }

        .subtitle {
            margin-top: 6px;
            color: #475569;
        }

        .empresa {
            margin-top: 6px;
            font-weight: bold;
            color: #0f172a;
        }

        .header-table,
        .footer-table,
        .summary,
        .summary-cards,
        .section-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .header-table td,
        .footer-table td {
            vertical-align: top;
        }

        .header-shell {
            border: 1px solid #dbe4f0;
            border-radius: 18px;
            overflow: hidden;
            background: #ffffff;
        }

        .header-band {
            height: 8px;
            background: #0d6efd;
        }

        .header-body {
            padding: 14px 16px 12px 16px;
        }

        .logo-cell {
            width: 94px;
        }

        .logo-box {
            width: 76px;
            height: 76px;
            border: 1px solid #dbe4f0;
            border-radius: 20px;
            background: #ffffff;
            overflow: hidden;
            text-align: center;
            box-sizing: border-box;
        }

        .logo-box img {
            width: 76px;
            height: 76px;
            object-fit: contain;
        }

        .meta-cell {
            width: 196px;
            text-align: right;
        }

        .meta-box {
            border: 1px solid #dbe4f0;
            background: #f8fbff;
            border-radius: 14px;
            padding: 10px 12px;
            font-size: 9px;
            line-height: 1.55;
            color: #475569;
        }

        .eyebrow {
            display: inline-block;
            padding: 3px 8px;
            margin-bottom: 8px;
            border-radius: 999px;
            background: #eaf2ff;
            color: #0d4fd7;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }


        .summary td,
        .summary-cards td,
        .section-table th,
        .section-table td {
            border: 1px solid #dbe4f0;
            padding: 7px 8px;
            vertical-align: top;
            word-wrap: break-word;
            overflow-wrap: anywhere;
        }

        .summary td:first-child {
            width: 38%;
            font-weight: bold;
            background: #f8fafc;
            color: #334155;
        }

        .summary {
            margin-top: 4px;
        }

        .summary-cards {
            margin: 0 0 14px 0;
        }

        .summary-cards td {
            width: 33.33%;
            background: #f8fbff;
            border-radius: 14px;
            padding: 10px 12px;
        }

        .summary-cards .card-title {
            display: block;
            margin-bottom: 5px;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #64748b;
        }

        .summary-cards .card-value {
            font-size: 12px;
            font-weight: bold;
            color: #0f172a;
        }

        .section-table th {
            background: #edf4ff;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #36506c;
        }

        .section {
            margin-top: 18px;
            border: 1px solid #dbe4f0;
            border-radius: 18px;
            overflow: hidden;
            background: #ffffff;
        }

        .section-header {
            padding: 12px 14px 0 14px;
        }

        .section-body {
            padding: 0 14px 14px 14px;
        }

        .section-kicker {
            margin-bottom: 6px;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #64748b;
        }

        .num {
            text-align: right;
        }

        .muted {
            color: #6b7280;
        }

        .footer-table td:last-child {
            text-align: right;
        }

        .footer-table {
            border-top: 1px solid #dbe4f0;
            padding-top: 6px;
        }

        thead {
            display: table-header-group;
        }

        tr {
            page-break-inside: avoid;
        }

        .no-border {
            border: 0 !important;
        }
    </style>
</head>
<body>
    <header>
        <div class="header-shell">
            <div class="header-band"></div>
            <div class="header-body">
                <table class="header-table">
                    <tr>
                        <td class="logo-cell">
                            <div class="logo-box">
                                <img src="{{ $logoDataUri }}" alt="Logo da empresa">
                            </div>
                        </td>
                        <td>
                            <div class="eyebrow">Relatório executivo</div>
                            <h1>{{ $title }}</h1>
                            <div class="subtitle">{{ $subtitle }}</div>
                            @if ($empresaNome)
                                <div class="empresa">{{ $empresaNome }}</div>
                            @endif
                            @if ($empresaDocumento || $empresaContato)
                                <div class="subtitle">
                                    {{ $empresaDocumento ?: '' }}@if ($empresaDocumento && $empresaContato) | @endif{{ $empresaContato ?: '' }}
                                </div>
                            @endif
                        </td>
                        <td class="meta-cell">
                            <div class="meta-box">
                                <strong>Documento interno</strong><br>
                                Emitido em {{ $generatedAt->format('d/m/Y H:i') }}<br>
                                Formato PDF para impressão e compartilhamento
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </header>

    <footer>
        <table class="footer-table">
            <tr>
                <td>{{ $empresaNome ?: 'Administrar' }} | {{ $title }}</td>
                <td>Gerado em {{ $generatedAt->format('d/m/Y H:i') }}</td>
            </tr>
        </table>
    </footer>

    <main>
        <table class="summary-cards">
            <tr>
                <td class="no-border">
                    <span class="card-title">Documento</span>
                    <span class="card-value">{{ $title }}</span>
                </td>
                <td class="no-border">
                    <span class="card-title">Período ou contexto</span>
                    <span class="card-value">{{ $subtitle }}</span>
                </td>
                <td class="no-border">
                    <span class="card-title">Gerado em</span>
                    <span class="card-value">{{ $generatedAt->format('d/m/Y H:i') }}</span>
                </td>
            </tr>
        </table>

        <table class="summary">
            <tbody>
                @foreach ($summaryRows as $row)
                    <tr>
                        <td>{{ $row[0] }}</td>
                        <td>{{ $row[1] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        @foreach ($sections as $section)
            <div class="section">
                <div class="section-header">
                    <div class="section-kicker">Bloco analítico</div>
                    <h2>{{ $section['title'] }}</h2>
                </div>
                <div class="section-body">
                    <table class="section-table">
                        <thead>
                            <tr>
                                @foreach ($section['columns'] as $column)
                                    <th class="{{ $column['class'] ?? '' }}" @if (! empty($column['width'])) style="width: {{ $column['width'] }}" @endif>{{ $column['label'] }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($section['rows'] as $row)
                                <tr>
                                    @foreach ($row as $index => $cell)
                                        <td class="{{ $section['columns'][$index]['class'] ?? '' }}">{{ $cell }}</td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ count($section['columns']) }}" class="muted">Sem dados para este bloco.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach
    </main>
</body>
</html>